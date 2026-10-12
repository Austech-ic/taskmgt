<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('description');
            $table->text('deliverables')->nullable();
            $table->text('acceptance_criteria')->nullable();
            $table->string('currency', 3)->default('NGN');
            $table->unsignedBigInteger('budget_min')->nullable();
            $table->unsignedBigInteger('budget_max')->nullable();
            $table->string('contract_type', 10)->default('fixed'); // fixed, hourly
            $table->string('experience_level', 20)->default('mid');
            $table->unsignedTinyInteger('min_verified_level')->nullable();
            $table->date('deadline')->nullable();
            $table->text('ip_terms')->nullable();
            $table->string('visibility', 20)->default('public');
            $table->string('status', 20)->default('open'); // draft, open, in_progress, completed, cancelled
            $table->unsignedInteger('views')->default(0);
            $table->timestamps();
        });

        Schema::create('project_skill', function (Blueprint $table) {
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->primary(['project_id', 'skill_id']);
        });

        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('professional_profile_id')->constrained()->cascadeOnDelete();
            $table->text('cover_letter');
            $table->text('approach')->nullable();
            $table->text('assumptions')->nullable();
            $table->unsignedBigInteger('amount_minor');
            $table->unsignedSmallInteger('duration_days')->default(14);
            $table->json('milestones')->nullable(); // [{title, amount_minor, days}]
            $table->string('status', 20)->default('submitted'); // submitted, shortlisted, accepted, declined, withdrawn
            $table->timestamps();
            $table->unique(['project_id', 'professional_profile_id']);
        });

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('proposal_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('professional_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->longText('scope');
            $table->text('acceptance_criteria')->nullable();
            $table->string('currency', 3)->default('NGN');
            $table->unsignedBigInteger('amount_minor');
            $table->unsignedInteger('commission_bps')->default(1500); // 15.00%
            $table->string('commission_payer', 20)->default('professional'); // professional, client, split
            // pending_acceptance, active, completed, disputed, cancelled
            $table->string('status', 30)->default('pending_acceptance');
            $table->timestamp('client_accepted_at')->nullable();
            $table->timestamp('professional_accepted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(1);
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('amount_minor');
            $table->date('due_on')->nullable();
            // pending, funding_pending, funded, submitted, revision_requested, accepted, released, disputed, refunded
            $table->string('status', 30)->default('pending')->index();
            $table->unsignedTinyInteger('revisions_used')->default(0);
            $table->timestamp('funded_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
        });

        Schema::create('deliverables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('milestone_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('version')->default(1);
            $table->text('note');
            $table->string('link')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('review_status', 20)->default('pending'); // pending, accepted, revision_requested
            $table->text('review_note')->nullable();
            $table->timestamps();
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewee_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('quality')->nullable();
            $table->unsignedTinyInteger('timeliness')->nullable();
            $table->unsignedTinyInteger('communication');
            $table->unsignedTinyInteger('professionalism');
            $table->unsignedTinyInteger('scope_compliance')->nullable();
            $table->decimal('overall', 3, 2);
            $table->boolean('on_time')->nullable();
            $table->text('comment')->nullable();
            $table->text('response')->nullable();
            $table->string('status', 20)->default('published'); // published, hidden, flagged
            $table->timestamps();
            $table->unique(['contract_id', 'reviewer_id']);
        });

        Schema::create('disputes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('milestone_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('raised_by')->constrained('users')->cascadeOnDelete();
            $table->string('reason', 40);
            $table->text('details');
            $table->string('status', 20)->default('open'); // open, under_review, resolved
            $table->string('outcome', 30)->nullable(); // release, refund, split
            $table->unsignedBigInteger('release_minor')->nullable();
            $table->unsignedBigInteger('refund_minor')->nullable();
            $table->text('resolution')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['disputes', 'reviews', 'deliverables', 'milestones', 'contracts', 'proposals', 'project_skill', 'projects'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
