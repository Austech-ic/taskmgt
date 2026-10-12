@extends('layouts.base')
@section('title', 'Test payment')
@section('body')
<div class="install-shell">
    <div class="card card-pop" style="max-width:460px">
        <div class="row-between"><span class="sticker sticker-coral">TEST MODE</span><span class="small muted mono">{{ $tx->reference }}</span></div>
        <h2 class="mt-2">Sandbox checkout</h2>
        <p class="muted">This is {{ setting('site_name') }}’s test payment page. No real money moves. An admin can switch to Paystack in Settings → Payments.</p>
        <div class="card card-soft mb-2">
            <div class="small muted">{{ $tx->purposeLabel() }}</div>
            <div style="font:800 2rem var(--display)" class="money">{{ money($tx->amount_minor, $tx->currency) }}</div>
        </div>
        <form method="POST" action="{{ route('payments.sandbox.complete', $tx) }}" class="grid-2">
            @csrf
            <button name="outcome" value="fail" class="btn btn-danger btn-lg">Simulate failure</button>
            <button name="outcome" value="success" class="btn btn-accent btn-lg">Pay (test)</button>
        </form>
    </div>
</div>
@endsection
