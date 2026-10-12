<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Contract;
use App\Models\Dispute;
use App\Models\Milestone;
use App\Models\Proposal;
use App\Models\User;
use App\Notifications\PlatformNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ContractService
{
    public function __construct(protected Ledger $ledger) {}

    /** Basis points of the commission borne by the client and by the professional. */
    public function shares(Contract $contract): array
    {
        $bps = $contract->commission_bps;

        return match ($contract->commission_payer) {
            'client' => ['client' => $bps, 'professional' => 0],
            'split' => ['client' => intdiv($bps, 2), 'professional' => $bps - intdiv($bps, 2)],
            default => ['client' => 0, 'professional' => $bps],
        };
    }

    /** What the client pays to fund a milestone (milestone amount + any client-side fee). */
    public function fundingAmount(Milestone $milestone): int
    {
        $shares = $this->shares($milestone->contract);

        return $milestone->amount_minor + Commission::of($milestone->amount_minor, $shares['client']);
    }

    /** @return array{funding:int, client_fee:int, professional_fee:int, commission:int, payout:int} */
    public function breakdown(Milestone $milestone, ?int $releaseMinor = null): array
    {
        $amount = $releaseMinor ?? $milestone->amount_minor;
        $shares = $this->shares($milestone->contract);
        $clientFee = Commission::of($amount, $shares['client']);
        $proFee = Commission::of($amount, $shares['professional']);

        return [
            'funding' => $this->fundingAmount($milestone),
            'client_fee' => $clientFee,
            'professional_fee' => $proFee,
            'commission' => $clientFee + $proFee,
            'payout' => $amount - $proFee,
        ];
    }

    public function createFromProposal(Proposal $proposal, array $data): Contract
    {
        $project = $proposal->project;

        return DB::transaction(function () use ($proposal, $project, $data) {
            $milestones = collect($data['milestones'])->filter(fn ($m) => filled($m['title'] ?? null) && to_minor($m['amount'] ?? null) > 0)->values();
            if ($milestones->isEmpty()) {
                throw new RuntimeException('Add at least one milestone with an amount.');
            }

            $contract = Contract::create([
                'reference' => 'DHC-'.strtoupper(Str::random(6)),
                'project_id' => $project->id,
                'proposal_id' => $proposal->id,
                'client_id' => $project->client_id,
                'professional_id' => $proposal->profile->user_id,
                'title' => $project->title,
                'scope' => $data['scope'],
                'acceptance_criteria' => $data['acceptance_criteria'] ?? $project->acceptance_criteria,
                'currency' => $project->currency,
                'amount_minor' => $milestones->sum(fn ($m) => to_minor($m['amount'])),
                'commission_bps' => (int) setting('commission_bps'),
                'commission_payer' => setting('commission_payer'),
                'status' => 'pending_acceptance',
                'client_accepted_at' => now(),
            ]);

            foreach ($milestones as $i => $m) {
                $contract->milestones()->create([
                    'position' => $i + 1,
                    'title' => $m['title'],
                    'description' => $m['description'] ?? null,
                    'amount_minor' => to_minor($m['amount']),
                    'due_on' => $m['due_on'] ?? null,
                ]);
            }

            $proposal->update(['status' => 'accepted']);
            $project->proposals()->where('id', '!=', $proposal->id)->where('status', 'submitted')->update(['status' => 'declined']);
            $project->update(['status' => 'in_progress']);

            AuditLog::record('contract.created', $contract, ['amount' => $contract->amount_minor]);
            $contract->professional->notify(new PlatformNotification(
                'You got the gig 🎉',
                "Your proposal for “{$project->title}” was accepted. Review and accept the contract to lock it in.",
                route('app.contracts.show', $contract),
                'handshake'
            ));

            return $contract;
        });
    }

    public function release(Milestone $milestone, ?User $actor = null): void
    {
        DB::transaction(function () use ($milestone) {
            $milestone = Milestone::whereKey($milestone->id)->lockForUpdate()->first();
            if (! in_array($milestone->status, ['accepted', 'submitted', 'funded', 'revision_requested', 'disputed'], true)) {
                throw new RuntimeException('This milestone is not in a releasable state.');
            }
            $this->postRelease($milestone, $milestone->amount_minor, 0);
            $milestone->update(['status' => 'released', 'accepted_at' => $milestone->accepted_at ?? now(), 'released_at' => now()]);
        });

        $this->maybeComplete($milestone->contract->fresh('milestones'));
        $b = $this->breakdown($milestone);
        $milestone->contract->professional->notify(new PlatformNotification(
            'Payment released 🤑',
            money($b['payout'], $milestone->contract->currency)." from “{$milestone->title}” just landed in your Devhail earnings.",
            route('app.earnings'),
            'banknote'
        ));
    }

    /** Ledger entries for releasing $releaseMinor to the professional and refunding $refundMinor of funding to the client. */
    protected function postRelease(Milestone $milestone, int $releaseMinor, int $refundMinor): void
    {
        $contract = $milestone->contract;
        $currency = $contract->currency;
        $funding = $this->fundingAmount($milestone);
        $b = $this->breakdown($milestone, $releaseMinor);
        $refund = $funding - ($releaseMinor + $b['client_fee']);

        $lines = [[$this->ledger->account('escrow:held', $currency), $funding, 0]];
        if ($b['payout'] > 0) {
            $lines[] = [$this->ledger->payableAccount($contract->professional, $currency), 0, $b['payout']];
        }
        if ($b['commission'] > 0) {
            $lines[] = [$this->ledger->account('revenue:commission', $currency), 0, $b['commission']];
        }
        if ($refund > 0) {
            $lines[] = [$this->ledger->account('platform:cash', $currency), 0, $refund];
        }

        $this->ledger->post("Milestone settled · {$contract->reference} #{$milestone->position}", $lines, $milestone, $currency);
        AuditLog::record('milestone.settled', $milestone, ['released' => $releaseMinor, 'refunded' => $refund, 'commission' => $b['commission']]);
    }

    public function resolveDispute(Dispute $dispute, string $outcome, ?int $releaseMinor, string $resolution, User $officer): void
    {
        $milestone = $dispute->milestone;

        DB::transaction(function () use ($dispute, $outcome, $releaseMinor, $resolution, $officer, $milestone) {
            $refund = null;
            if ($milestone && $milestone->isFunded()) {
                $release = match ($outcome) {
                    'release' => $milestone->amount_minor,
                    'refund' => 0,
                    'split' => max(0, min((int) $releaseMinor, $milestone->amount_minor)),
                };
                $this->postRelease($milestone, $release, 0);
                $refund = $this->fundingAmount($milestone) - ($release + $this->breakdown($milestone, $release)['client_fee']);
                $milestone->update([
                    'status' => $release > 0 ? 'released' : 'refunded',
                    'released_at' => $release > 0 ? now() : null,
                ]);
                $releaseMinor = $release;
            }

            $dispute->update([
                'status' => 'resolved',
                'outcome' => $outcome,
                'release_minor' => $releaseMinor,
                'refund_minor' => $refund,
                'resolution' => $resolution,
                'resolved_by' => $officer->id,
                'resolved_at' => now(),
            ]);

            $contract = $dispute->contract;
            if ($contract->status === 'disputed' && ! $contract->disputes()->where('status', '!=', 'resolved')->exists()) {
                $contract->update(['status' => 'active']);
            }
            AuditLog::record('dispute.resolved', $dispute, ['outcome' => $outcome, 'release' => $releaseMinor, 'refund' => $refund]);
        });

        $this->maybeComplete($dispute->contract->fresh('milestones'));
        foreach ([$dispute->contract->client, $dispute->contract->professional] as $party) {
            $party->notify(new PlatformNotification('Dispute resolved', 'A Devhail officer has resolved the dispute on '.$dispute->contract->reference.'. Tap for the full decision.', route('app.contracts.show', $dispute->contract), 'scale'));
        }
    }

    public function maybeComplete(Contract $contract): void
    {
        $open = $contract->milestones->whereNotIn('status', ['released', 'refunded'])->count();
        if ($open === 0 && $contract->status !== 'completed') {
            $contract->update(['status' => 'completed', 'completed_at' => now()]);
            $contract->project->update(['status' => 'completed']);
            foreach ([$contract->client, $contract->professional] as $party) {
                $party->notify(new PlatformNotification('Contract complete ✅', "“{$contract->title}” is wrapped. Drop a review — it powers the trust layer.", route('app.contracts.show', $contract), 'party-popper'));
            }
        }
    }
}
