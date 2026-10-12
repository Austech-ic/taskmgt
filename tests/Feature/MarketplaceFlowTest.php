<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Dispute;
use App\Models\Milestone;
use App\Models\PaymentTransaction;
use App\Models\PayoutRequest;
use App\Models\Project;
use App\Models\Proposal;
use App\Services\Ledger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPlatform();
    }

    /** Client posts → pro proposes → client hires → pro accepts. */
    protected function contract(): array
    {
        $client = $this->hirer();
        $pro = $this->talent();

        $this->actingAs($client)->post(route('app.projects.store'), [
            'title' => 'Build a salon booking site', 'description' => str_repeat('We need a booking website for our salon. ', 3),
            'currency' => 'NGN', 'budget_min' => '500000', 'budget_max' => '1000000', 'contract_type' => 'fixed',
            'experience_level' => 'mid', 'status' => 'open',
        ])->assertRedirect();
        $project = Project::first();

        $this->actingAs($pro)->post(route('app.projects.propose', $project), [
            'cover_letter' => str_repeat('I have built three booking systems. ', 3), 'duration_days' => 21,
            'milestones' => [['title' => 'Design', 'amount' => '400000'], ['title' => 'Build', 'amount' => '600000']],
        ])->assertRedirect(route('app.proposals.index'));
        $proposal = Proposal::first();
        $this->assertSame(100_000_000, $proposal->amount_minor);

        $this->actingAs($client)->post(route('app.projects.hire.store', [$project, $proposal]), [
            'scope' => 'Booking site as described in the proposal.', 'acceptance_criteria' => 'Customers can book on mobile.',
            'milestones' => [['title' => 'Design', 'amount' => '400000'], ['title' => 'Build', 'amount' => '600000']], 'agree' => 1,
        ])->assertRedirect();
        $contract = Contract::first();
        $this->assertSame('pending_acceptance', $contract->status);

        // Funding is blocked until the professional accepts.
        $this->actingAs($client)->post(route('app.contracts.fund', [$contract, $contract->milestones->first()]))->assertSessionHas('error');

        $this->actingAs($pro)->post(route('app.contracts.accept', $contract), ['agree' => 1]);
        $this->assertSame('active', $contract->fresh()->status);

        return [$client, $pro, $contract->fresh('milestones')];
    }

    protected function fund($client, Contract $contract, Milestone $m, string $outcome = 'success'): PaymentTransaction
    {
        $this->actingAs($client)->post(route('app.contracts.fund', [$contract, $m]))->assertRedirect();
        $tx = PaymentTransaction::latest('id')->first();
        $this->assertSame('funding_pending', $m->fresh()->status);
        $this->actingAs($client)->post(route('payments.sandbox.complete', $tx), ['outcome' => $outcome]);
        $this->actingAs($client)->get(route('payments.callback', $tx))->assertRedirect();

        return $tx->fresh();
    }

    public function test_full_milestone_lifecycle_with_commission_and_balanced_ledger(): void
    {
        [$client, $pro, $contract] = $this->contract();
        $m1 = $contract->milestones[0];

        $tx = $this->fund($client, $contract, $m1);
        $this->assertSame('succeeded', $tx->status);
        $this->assertSame(40_000_000, $tx->amount_minor);
        $this->assertSame('funded', $m1->fresh()->status);

        // Client cannot approve before delivery.
        $this->actingAs($client)->post(route('app.contracts.approve', [$contract, $m1]))->assertSessionHas('error');

        $this->actingAs($pro)->post(route('app.contracts.deliver', [$contract, $m1]), ['note' => 'Designs attached, see Figma.', 'link' => 'https://figma.com/x'])->assertRedirect();
        $this->assertSame('submitted', $m1->fresh()->status);

        $this->actingAs($client)->post(route('app.contracts.approve', [$contract, $m1]))->assertRedirect();
        $this->assertSame('released', $m1->fresh()->status);

        $ledger = app(Ledger::class);
        $this->assertSame(34_000_000, $ledger->balanceOf($pro)); // ₦400k − 15%
        $this->assertSame(6_000_000, $ledger->account('revenue:commission')->balance());
        $this->assertSame(0, $ledger->account('escrow:held')->balance());
        $this->assertTrue($ledger->isBalanced());
    }

    public function test_failed_payment_does_not_fund_the_milestone(): void
    {
        [$client, , $contract] = $this->contract();
        $m1 = $contract->milestones[0];
        $tx = $this->fund($client, $contract, $m1, 'fail');
        $this->assertSame('failed', $tx->status);
        $this->assertSame('pending', $m1->fresh()->status);
        $this->assertSame(0, app(Ledger::class)->account('escrow:held')->balance());
    }

    public function test_only_the_payer_can_complete_a_sandbox_payment(): void
    {
        [$client, $pro, $contract] = $this->contract();
        $this->actingAs($client)->post(route('app.contracts.fund', [$contract, $contract->milestones[0]]));
        $tx = PaymentTransaction::first();
        $this->actingAs($pro)->post(route('payments.sandbox.complete', $tx), ['outcome' => 'success'])->assertForbidden();
    }

    public function test_dispute_split_settles_correctly(): void
    {
        [$client, $pro, $contract] = $this->contract();
        $m2 = $contract->milestones[1];
        $this->fund($client, $contract, $m2);

        $this->actingAs($client)->post(route('app.contracts.dispute', $contract), [
            'milestone_id' => $m2->id, 'reason' => 'quality', 'details' => 'The booking flow does not work on Android at all, checked twice.',
        ])->assertRedirect();
        $this->assertSame('disputed', $m2->fresh()->status);
        $dispute = Dispute::first();

        $officer = $this->staff('finance');
        $this->actingAs($officer)->post(route('admin.disputes.resolve', $dispute), [
            'outcome' => 'split', 'release_amount' => '300000', 'resolution' => 'Half the scope was delivered and verified on staging.',
        ])->assertRedirect();

        $ledger = app(Ledger::class);
        $this->assertSame(25_500_000, $ledger->balanceOf($pro));               // ₦300k − 15%
        $this->assertSame(4_500_000, $ledger->account('revenue:commission')->balance());
        $this->assertSame(0, $ledger->account('escrow:held')->balance());
        $this->assertSame(30_000_000, $dispute->fresh()->refund_minor);
        $this->assertTrue($ledger->isBalanced());
        $this->assertSame('active', $contract->fresh()->status);
    }

    public function test_payout_requires_two_officers(): void
    {
        [$client, $pro, $contract] = $this->contract();
        $m1 = $contract->milestones[0];
        $this->fund($client, $contract, $m1);
        $this->actingAs($pro)->post(route('app.contracts.deliver', [$contract, $m1]), ['note' => 'Delivered, please review.']);
        $this->actingAs($client)->post(route('app.contracts.approve', [$contract, $m1]));

        $this->actingAs($pro)->post(route('app.earnings.payout'), ['amount' => '340000', 'bank_name' => 'GTBank', 'account_number' => '0123456789', 'account_name' => 'Pro'])->assertSessionHas('success');
        $payout = PayoutRequest::first();

        $a = $this->staff('finance');
        $b = $this->staff('finance');
        $this->actingAs($a)->post(route('admin.finance.payouts.action', $payout), ['action' => 'approve']);
        $this->actingAs($a)->post(route('admin.finance.payouts.action', $payout), ['action' => 'pay', 'payout_reference' => 'TRF-1'])->assertStatus(422);
        $this->actingAs($b)->post(route('admin.finance.payouts.action', $payout), ['action' => 'pay', 'payout_reference' => 'TRF-1'])->assertRedirect();

        $this->assertSame('paid', $payout->fresh()->status);
        $this->assertSame(0, app(Ledger::class)->balanceOf($pro));
        $this->assertTrue(app(Ledger::class)->isBalanced());
    }

    public function test_outsiders_cannot_see_contracts(): void
    {
        [, , $contract] = $this->contract();
        $this->actingAs($this->talent())->get(route('app.contracts.show', $contract))->assertForbidden();
    }
}
