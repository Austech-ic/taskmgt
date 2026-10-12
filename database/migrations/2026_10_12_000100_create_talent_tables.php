<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skill_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon', 40)->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('skill_category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('synonyms')->nullable(); // comma separated, used by search
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('professional_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->string('headline')->nullable();
            $table->text('bio')->nullable();
            $table->string('city')->nullable();
            $table->string('country', 80)->default('Nigeria');
            $table->string('timezone', 60)->default('Africa/Lagos');
            $table->string('availability', 30)->default('open_to_work'); // open_to_work, contract, freelance, unavailable
            $table->string('work_arrangement', 20)->default('remote'); // remote, hybrid, onsite, any
            $table->unsignedTinyInteger('years_experience')->default(0);
            $table->string('currency', 3)->default('NGN');
            $table->unsignedBigInteger('rate_min')->nullable(); // minor units / month or hour
            $table->unsignedBigInteger('rate_max')->nullable();
            $table->string('rate_unit', 10)->default('month'); // hour, month, project
            $table->boolean('show_rate')->default(false);
            $table->string('languages')->nullable();
            $table->string('primary_role')->nullable();
            $table->string('github_url')->nullable();
            $table->string('gitlab_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('portfolio_url')->nullable();
            $table->string('behance_url')->nullable();
            $table->string('cv_path')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 40)->nullable();
            $table->boolean('allow_direct_contact')->default(true);
            $table->boolean('is_public')->default(true);
            // draft, under_review, awaiting_assessment, conditionally_approved, approved, suspended
            $table->string('status', 30)->default('draft')->index();
            $table->text('review_notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('identity_verified_at')->nullable();
            $table->timestamp('portfolio_verified_at')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->unsignedInteger('profile_views')->default(0);
            $table->timestamps();
        });

        Schema::create('profile_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('professional_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('self_level')->default(2); // 1-5 self-reported
            $table->unsignedTinyInteger('years')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->unsignedTinyInteger('verified_level')->nullable(); // set only by a passed assessment
            $table->unsignedTinyInteger('verified_score')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('verification_expires_at')->nullable();
            $table->unsignedBigInteger('assessment_attempt_id')->nullable();
            $table->timestamps();
            $table->unique(['professional_profile_id', 'skill_id']);
        });

        Schema::create('experiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('professional_profile_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 20)->default('work'); // work, education
            $table->string('title');
            $table->string('organization');
            $table->string('location')->nullable();
            $table->date('started_on')->nullable();
            $table->date('ended_on')->nullable();
            $table->boolean('is_current')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('portfolio_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('professional_profile_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('role')->nullable();
            $table->text('description')->nullable();
            $table->string('technologies')->nullable();
            $table->text('outcome')->nullable();
            $table->string('url')->nullable();
            $table->string('repo_url')->nullable();
            $table->string('image_path')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('certifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('professional_profile_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('issuer');
            $table->date('issued_on')->nullable();
            $table->date('expires_on')->nullable();
            $table->string('credential_id')->nullable();
            $table->string('credential_url')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['certifications', 'portfolio_items', 'experiences', 'profile_skills', 'professional_profiles', 'skills', 'skill_categories'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
