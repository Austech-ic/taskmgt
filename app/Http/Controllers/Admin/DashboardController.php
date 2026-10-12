<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiLog;
use App\Models\AssessmentAttempt;
use App\Models\AuditLog;
use App\Models\Contract;
use App\Models\Dispute;
use App\Models\JobPost;
use App\Models\Organization;
use App\Models\PaymentTransaction;
use App\Models\PayoutRequest;
use App\Models\ProfessionalProfile;
use App\Models\Project;
use App\Models\User;
use App\Services\Ledger;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Ledger $ledger)
    {
        $days = collect(range(13, 0))->map(fn ($d) => now()->subDays($d)->toDateString());
        $signups = User::where('created_at', '>=', now()->subDays(14)->startOfDay())->get(['created_at'])->groupBy(fn ($u) => $u->created_at->toDateString());

        return view('admin.dashboard', [
            'kpis' => [
                'approved' => ProfessionalProfile::whereIn('status', ['approved', 'conditionally_approved'])->count(),
                'users' => User::count(),
                'orgs' => Organization::count(),
                'jobs' => JobPost::open()->count(),
                'projects' => Project::where('status', 'open')->count(),
                'contracts' => Contract::where('status', 'active')->count(),
                'gmv' => (int) PaymentTransaction::where('status', 'succeeded')->where('purpose', 'milestone_funding')->sum('amount_minor'),
                'revenue' => $ledger->account('revenue:commission')->balance() + $ledger->account('revenue:unlocks')->balance() + $ledger->account('revenue:subscriptions')->balance(),
                'pass_rate' => ($total = AssessmentAttempt::whereIn('status', ['passed', 'not_passed'])->count()) ? round(AssessmentAttempt::where('status', 'passed')->count() / $total * 100) : null,
                'with_verified' => ($ap = ProfessionalProfile::whereIn('status', ['approved', 'conditionally_approved'])->count()) ? round(ProfessionalProfile::whereIn('status', ['approved', 'conditionally_approved'])->whereHas('profileSkills', fn ($q) => $q->whereNotNull('verified_level'))->count() / $ap * 100) : null,
            ],
            'queues' => [
                ['Assessments to review', AssessmentAttempt::where('status', 'under_review')->count(), 'admin.attempts.index', 'clipboard-check', ['reviewer', 'admin']],
                ['Profiles to review', ProfessionalProfile::whereIn('status', ['under_review', 'conditionally_approved'])->count(), 'admin.talent.index', 'user-check', ['admin']],
                ['Open disputes', Dispute::where('status', '!=', 'resolved')->count(), 'admin.disputes.index', 'scale', ['finance', 'admin']],
                ['Payouts to process', PayoutRequest::whereIn('status', ['requested', 'approved'])->count(), 'admin.finance.payouts', 'banknote', ['finance', 'admin']],
                ['Companies to verify', Organization::whereNull('verified_at')->count(), 'admin.organizations.index', 'building-2', ['admin']],
            ],
            'chart' => $days->map(fn ($d) => ['date' => $d, 'count' => $signups->get($d)?->count() ?? 0]),
            'ledgerBalanced' => $ledger->isBalanced(),
            'aiCalls' => AiLog::where('created_at', '>=', now()->subDays(30))->count(),
            'recent' => AuditLog::with('user')->latest('id')->take(8)->get(),
        ]);
    }

    public function audit(Request $request)
    {
        $logs = AuditLog::with('user')
            ->when($request->action, fn ($q, $a) => $q->where('action', 'like', $a.'%'))
            ->latest('id')->paginate(40)->withQueryString();
        $actions = AuditLog::select('action')->distinct()->orderBy('action')->pluck('action');

        return view('admin.audit', compact('logs', 'actions'));
    }
}
