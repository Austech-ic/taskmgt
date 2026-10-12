<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Conversation;
use App\Models\Dispute;
use App\Services\ContractService;
use Illuminate\Http\Request;

class DisputeController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.disputes.index', [
            'disputes' => Dispute::with(['contract', 'milestone', 'raiser'])->when($request->query('status', 'open') !== 'all', fn ($q) => $q->where('status', $request->query('status', 'open') === 'resolved' ? '=' : '!=', 'resolved'))->latest()->paginate(25)->withQueryString(),
        ]);
    }

    public function show(Dispute $dispute, ContractService $contracts)
    {
        $dispute->load(['contract.client', 'contract.professional', 'contract.milestones.deliverables', 'milestone', 'raiser', 'resolver']);
        $conversation = Conversation::whereHas('users', fn ($q) => $q->where('users.id', $dispute->contract->client_id))
            ->whereHas('users', fn ($q) => $q->where('users.id', $dispute->contract->professional_id))->with('messages.user')->first();

        return view('admin.disputes.show', [
            'dispute' => $dispute,
            'messages' => $conversation?->messages ?? collect(),
            'breakdown' => $dispute->milestone ? $contracts->breakdown($dispute->milestone) : null,
        ]);
    }

    public function resolve(Request $request, Dispute $dispute, ContractService $contracts)
    {
        abort_if($dispute->status === 'resolved', 422, 'Already resolved.');
        $data = $request->validate([
            'outcome' => 'required|in:release,refund,split',
            'release_amount' => 'required_if:outcome,split|nullable|string|max:20',
            'resolution' => 'required|string|min:20|max:5000',
        ]);
        $contracts->resolveDispute($dispute, $data['outcome'], to_minor($data['release_amount'] ?? null), $data['resolution'], $request->user());

        return redirect()->route('admin.disputes.index')->with('success', 'Dispute resolved and both parties notified. Refunds to the client are processed through the payment provider dashboard.');
    }

    public function contracts(Request $request)
    {
        return view('admin.contracts', [
            'contracts' => Contract::with(['client', 'professional', 'milestones'])->when($request->status, fn ($q, $s) => $q->where('status', $s))
                ->when($request->q, fn ($q, $t) => $q->where(fn ($w) => $w->where('reference', 'like', "%$t%")->orWhere('title', 'like', "%$t%")))->latest()->paginate(25)->withQueryString(),
        ]);
    }
}
