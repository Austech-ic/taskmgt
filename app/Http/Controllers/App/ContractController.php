<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Contract;
use App\Models\Deliverable;
use App\Models\Dispute;
use App\Models\Milestone;
use App\Models\Review;
use App\Models\User;
use App\Notifications\PlatformNotification;
use App\Services\ContractService;
use App\Services\Payments\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ContractController extends Controller
{
    public function __construct(protected ContractService $contracts) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $asClient = workspace() === 'hirer';

        return view('app.contracts.index', [
            'contracts' => Contract::where($asClient ? 'client_id' : 'professional_id', $user->id)->with(['milestones', 'client', 'professional'])->latest()->paginate(15),
            'asClient' => $asClient,
        ]);
    }

    public function show(Request $request, Contract $contract)
    {
        abort_unless($contract->involves($request->user()) || $request->user()->hasStaffRole('finance', 'admin'), 403);
        $contract->load(['milestones.deliverables.user', 'milestones.transactions', 'client', 'professional.profile', 'disputes.raiser', 'reviews.reviewer', 'project']);
        $user = $request->user();

        return view('app.contracts.show', [
            'contract' => $contract,
            'isClient' => $user->id === $contract->client_id,
            'isPro' => $user->id === $contract->professional_id,
            'service' => $this->contracts,
            'myReview' => $contract->reviews->firstWhere('reviewer_id', $user->id),
            'canReview' => $contract->milestones->contains('status', 'released') || $contract->status === 'completed',
            'maxRevisions' => (int) setting('max_revisions'),
            'disputeReasons' => Dispute::REASONS,
        ]);
    }

    public function accept(Request $request, Contract $contract)
    {
        abort_unless($request->user()->id === $contract->professional_id && $contract->status === 'pending_acceptance', 403);
        $request->validate(['agree' => 'accepted']);
        $contract->update(['professional_accepted_at' => now(), 'status' => 'active']);
        AuditLog::record('contract.accepted', $contract);
        $contract->client->notify(new PlatformNotification('Contract accepted 🤝', $contract->professional->name.' accepted “'.$contract->title.'”. Fund the first milestone to kick off.', route('app.contracts.show', $contract), 'handshake'));

        return back()->with('success', 'Contract accepted. Work starts once the client funds a milestone 💪');
    }

    public function fund(Request $request, Contract $contract, Milestone $milestone, PaymentService $payments)
    {
        $this->milestoneOf($contract, $milestone);
        abort_unless($request->user()->id === $contract->client_id, 403);
        if ($contract->status !== 'active') {
            return back()->with('error', 'The professional needs to accept the contract before milestones can be funded.');
        }
        if (! in_array($milestone->status, ['pending', 'funding_pending'], true)) {
            return back()->with('info', 'This milestone is already funded.');
        }

        $tx = $milestone->transactions()->where('status', 'pending')->latest()->first();
        $amount = $this->contracts->fundingAmount($milestone);
        if (! $tx || $tx->amount_minor !== $amount || $tx->provider !== setting('payment_provider')) {
            $tx?->update(['status' => 'failed']);
            $tx = $payments->create($request->user(), 'milestone_funding', $amount, $contract->currency, $milestone);
        }
        $milestone->update(['status' => 'funding_pending']);

        return redirect()->route('payments.checkout', $tx);
    }

    public function deliver(Request $request, Contract $contract, Milestone $milestone)
    {
        $this->milestoneOf($contract, $milestone);
        abort_unless($request->user()->id === $contract->professional_id, 403);
        if (! in_array($milestone->status, ['funded', 'revision_requested'], true)) {
            return back()->with('error', 'You can submit work once the milestone is funded.');
        }
        $data = $request->validate([
            'note' => 'required|string|min:10|max:5000',
            'link' => 'nullable|url|max:500',
            'file' => 'nullable|file|max:20480|mimes:zip,pdf,png,jpg,jpeg,gif,webp,txt,md,doc,docx,xls,xlsx,ppt,pptx,fig,sketch,mp4,mov',
        ]);

        $deliverable = new Deliverable([
            'note' => $data['note'],
            'link' => $data['link'] ?? null,
            'version' => $milestone->deliverables()->count() + 1,
        ]);
        if ($request->hasFile('file')) {
            $deliverable->file_path = $request->file('file')->store('deliverables/'.$contract->id, 'local');
            $deliverable->file_name = $request->file('file')->getClientOriginalName();
        }
        $deliverable->milestone_id = $milestone->id;
        $deliverable->user_id = $request->user()->id;
        $deliverable->save();

        $milestone->update(['status' => 'submitted', 'submitted_at' => now()]);
        $contract->client->notify(new PlatformNotification('Work submitted 📦', $contract->professional->name.' delivered “'.$milestone->title.'”. Review it against the acceptance criteria.', route('app.contracts.show', $contract), 'package-check'));

        return back()->with('success', 'Delivered! The client has been notified.');
    }

    public function approve(Request $request, Contract $contract, Milestone $milestone)
    {
        $this->milestoneOf($contract, $milestone);
        abort_unless($request->user()->id === $contract->client_id, 403);
        if ($milestone->status !== 'submitted') {
            return back()->with('error', 'Only submitted work can be approved.');
        }
        $milestone->deliverables()->first()?->update(['review_status' => 'accepted']);
        $milestone->update(['status' => 'accepted', 'accepted_at' => now()]);

        try {
            $this->contracts->release($milestone->fresh(), $request->user());
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Approved & released 🎉 Thanks for paying on time.');
    }

    public function revise(Request $request, Contract $contract, Milestone $milestone)
    {
        $this->milestoneOf($contract, $milestone);
        abort_unless($request->user()->id === $contract->client_id && $milestone->status === 'submitted', 403);
        $data = $request->validate(['review_note' => 'required|string|min:10|max:3000']);
        $max = (int) setting('max_revisions');
        if ($milestone->revisions_used >= $max) {
            return back()->with('error', "You’ve used all {$max} revision requests on this milestone. If something’s still off, open a dispute.");
        }
        $milestone->deliverables()->first()?->update(['review_status' => 'revision_requested', 'review_note' => $data['review_note']]);
        $milestone->update(['status' => 'revision_requested', 'revisions_used' => $milestone->revisions_used + 1]);
        $contract->professional->notify(new PlatformNotification('Revision requested ✏️', 'The client asked for changes on “'.$milestone->title.'”.', route('app.contracts.show', $contract), 'pencil'));

        return back()->with('success', 'Revision requested.');
    }

    public function dispute(Request $request, Contract $contract)
    {
        $this->party($request, $contract);
        $data = $request->validate([
            'milestone_id' => 'required|integer',
            'reason' => 'required|in:'.implode(',', array_keys(Dispute::REASONS)),
            'details' => 'required|string|min:30|max:5000',
        ]);
        $milestone = $contract->milestones()->findOrFail($data['milestone_id']);
        if (! in_array($milestone->status, ['funded', 'submitted', 'revision_requested'], true)) {
            return back()->with('error', 'Disputes can be opened on funded milestones that haven’t been released.');
        }

        $dispute = Dispute::create(['contract_id' => $contract->id, 'milestone_id' => $milestone->id, 'raised_by' => $request->user()->id, 'reason' => $data['reason'], 'details' => $data['details']]);
        $milestone->update(['status' => 'disputed']);
        $contract->update(['status' => 'disputed']);
        AuditLog::record('dispute.opened', $dispute);

        $other = $request->user()->id === $contract->client_id ? $contract->professional : $contract->client;
        $other->notify(new PlatformNotification('Dispute opened ⚖️', 'A dispute was opened on “'.$milestone->title.'”. Funds are paused while a Devhail officer reviews it.', route('app.contracts.show', $contract), 'scale'));
        User::whereIn('staff_role', ['finance', 'admin', 'super_admin'])->get()->each->notify(new PlatformNotification('New dispute', $contract->reference.' · '.Dispute::REASONS[$data['reason']], route('admin.disputes.show', $dispute), 'scale'));

        return back()->with('success', 'Dispute opened. The amount is on hold and a neutral officer will review the evidence.');
    }

    public function review(Request $request, Contract $contract)
    {
        $this->party($request, $contract);
        $user = $request->user();
        abort_if(Review::where('contract_id', $contract->id)->where('reviewer_id', $user->id)->exists(), 422, 'You already reviewed this contract.');
        abort_unless($contract->milestones()->where('status', 'released')->exists() || $contract->status === 'completed', 422, 'Reviews open after the first payment is released.');

        $isClient = $user->id === $contract->client_id;
        $data = $request->validate([
            'quality' => ($isClient ? 'required' : 'nullable').'|integer|between:1,5',
            'timeliness' => ($isClient ? 'required' : 'nullable').'|integer|between:1,5',
            'communication' => 'required|integer|between:1,5',
            'professionalism' => 'required|integer|between:1,5',
            'scope_compliance' => ($isClient ? 'required' : 'nullable').'|integer|between:1,5',
            'on_time' => 'nullable|boolean',
            'comment' => 'required|string|min:20|max:2000',
        ]);
        $scores = collect($data)->only(['quality', 'timeliness', 'communication', 'professionalism', 'scope_compliance'])->filter();

        Review::create($data + [
            'contract_id' => $contract->id,
            'reviewer_id' => $user->id,
            'reviewee_id' => $isClient ? $contract->professional_id : $contract->client_id,
            'overall' => round($scores->avg(), 2),
            'on_time' => $isClient ? $request->boolean('on_time') : null,
        ]);

        return back()->with('success', 'Review posted. That’s how trust gets built 💜');
    }

    public function download(Request $request, Contract $contract, Deliverable $deliverable)
    {
        abort_unless($contract->involves($request->user()) || $request->user()->hasStaffRole('finance', 'admin'), 403);
        abort_unless($deliverable->milestone->contract_id === $contract->id && $deliverable->file_path && Storage::disk('local')->exists($deliverable->file_path), 404);

        return Storage::disk('local')->download($deliverable->file_path, $deliverable->file_name);
    }

    protected function party(Request $request, Contract $contract): void
    {
        abort_unless($contract->involves($request->user()), 403);
    }

    protected function milestoneOf(Contract $contract, Milestone $milestone): void
    {
        abort_unless($milestone->contract_id === $contract->id, 404);
    }
}
