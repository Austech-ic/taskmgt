<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            // milestone_funding, contact_credits, subscription
            $table->string('purpose', 30);
            $table->nullableMorphs('payable');
            $table->unsignedBigInteger('amount_minor');
            $table->string('currency', 3)->default('NGN');
            $table->string('provider', 20); // paystack, sandbox
            $table->string('provider_reference')->nullable();
            $table->string('status', 20)->default('pending'); // pending, succeeded, failed, refunded
            $table->json('metadata')->nullable();
            $table->json('provider_payload')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ledger_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // e.g. platform:cash, escrow:held, user:12:payable
            $table->string('name');
            $table->string('type', 20); // asset, liability, revenue, expense
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('currency', 3)->default('NGN');
            $table->timestamps();
        });

        // Append-only double-entry ledger. Corrections are made with reversing entries.
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->uuid('journal_id')->index();
            $table->foreignId('ledger_account_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('debit_minor')->default(0);
            $table->unsignedBigInteger('credit_minor')->default(0);
            $table->string('currency', 3)->default('NGN');
            $table->string('memo');
            $table->nullableMorphs('source');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('payout_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount_minor');
            $table->string('currency', 3)->default('NGN');
            $table->string('bank_name');
            $table->string('account_number', 20);
            $table->string('account_name');
            $table->string('status', 20)->default('requested'); // requested, approved, paid, rejected
            $table->string('payout_reference')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['payout_requests', 'ledger_entries', 'ledger_accounts', 'payment_transactions'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
