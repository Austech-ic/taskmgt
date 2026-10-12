<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('website')->nullable();
            $table->string('industry')->nullable();
            $table->string('size', 20)->nullable();
            $table->string('city')->nullable();
            $table->string('country', 80)->default('Nigeria');
            $table->text('about')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('registration_number')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->unsignedInteger('unlock_credits')->default(0); // pay-per-contact credits
            $table->timestamps();
        });

        Schema::create('organization_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20)->default('recruiter'); // owner, admin, recruiter
            $table->timestamps();
            $table->unique(['organization_id', 'user_id']);
        });

        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('tagline')->nullable();
            $table->unsignedBigInteger('price_minor')->default(0);
            $table->string('currency', 3)->default('NGN');
            $table->string('interval', 10)->default('month'); // month, year, custom
            $table->unsignedInteger('contact_unlocks')->default(0);
            $table->unsignedInteger('recruiter_seats')->default(1);
            $table->unsignedInteger('active_jobs')->default(1);
            $table->json('features')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_custom')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('pending'); // pending, active, expired, cancelled
            $table->unsignedInteger('unlocks_remaining')->default(0);
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamps();
        });

        Schema::create('contact_unlocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // recruiter who unlocked
            $table->foreignId('professional_profile_id')->constrained()->cascadeOnDelete();
            $table->string('source', 20); // subscription, credit
            $table->timestamps();
            $table->unique(['organization_id', 'professional_profile_id']);
        });

        Schema::create('contact_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('professional_profile_id')->constrained()->cascadeOnDelete();
            $table->string('subject');
            $table->text('message');
            $table->string('status', 20)->default('pending'); // pending, accepted, declined
            $table->timestamps();
        });

        Schema::create('shortlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('shortlist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shortlist_id')->constrained()->cascadeOnDelete();
            $table->foreignId('professional_profile_id')->constrained()->cascadeOnDelete();
            $table->text('note')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->timestamps();
            $table->unique(['shortlist_id', 'professional_profile_id']);
        });

        Schema::create('saved_searches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('filters');
            $table->timestamps();
        });

        Schema::create('job_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('posted_by')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('summary')->nullable();
            $table->longText('description');
            $table->text('responsibilities')->nullable();
            $table->text('benefits')->nullable();
            $table->string('employment_type', 20)->default('full_time'); // full_time, part_time, contract, temporary, internship
            $table->string('work_arrangement', 20)->default('remote');
            $table->string('seniority', 20)->default('mid'); // junior, mid, senior, lead
            $table->string('location')->nullable();
            $table->string('currency', 3)->default('NGN');
            $table->unsignedBigInteger('salary_min')->nullable();
            $table->unsignedBigInteger('salary_max')->nullable();
            $table->boolean('show_salary')->default(true);
            $table->unsignedSmallInteger('openings')->default(1);
            $table->boolean('require_verified')->default(false);
            $table->date('deadline')->nullable();
            $table->string('status', 20)->default('open'); // draft, open, closed
            $table->unsignedInteger('views')->default(0);
            $table->timestamps();
        });

        Schema::create('job_post_skill', function (Blueprint $table) {
            $table->foreignId('job_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_required')->default(true);
            $table->primary(['job_post_id', 'skill_id']);
        });

        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('professional_profile_id')->constrained()->cascadeOnDelete();
            $table->text('cover_letter')->nullable();
            $table->string('cv_path')->nullable();
            $table->unsignedBigInteger('expected_salary')->nullable();
            // new, shortlisted, contacted, interview, offer, hired, closed
            $table->string('stage', 20)->default('new')->index();
            $table->text('internal_notes')->nullable();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamps();
            $table->unique(['job_post_id', 'professional_profile_id']);
        });

        Schema::create('application_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('from_stage', 20)->nullable();
            $table->string('to_stage', 20);
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['application_events', 'applications', 'job_post_skill', 'job_posts', 'saved_searches', 'shortlist_items', 'shortlists', 'contact_requests', 'contact_unlocks', 'subscriptions', 'plans', 'organization_members', 'organizations'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
