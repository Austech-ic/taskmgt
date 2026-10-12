<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            $table->string('format', 30)->default('mixed'); // knowledge, practical, mixed
            $table->unsignedSmallInteger('duration_minutes')->default(30);
            $table->unsignedSmallInteger('question_count')->default(10);
            $table->unsignedTinyInteger('pass_mark')->default(70); // percent
            $table->unsignedSmallInteger('retake_days')->default(14);
            $table->unsignedSmallInteger('validity_months')->default(24);
            $table->boolean('requires_review')->default(false); // practical work → human reviewer
            $table->text('practical_brief')->nullable();
            $table->text('ai_policy')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->string('status', 20)->default('draft'); // draft, published, retired
            $table->timestamps();
        });

        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20)->default('single'); // single, multiple, short
            $table->text('body');
            $table->text('code_snippet')->nullable();
            $table->json('options')->nullable(); // [{key, text}]
            $table->json('correct'); // keys, or accepted answers for short
            $table->unsignedTinyInteger('points')->default(1);
            $table->unsignedTinyInteger('difficulty')->default(2); // 1-5
            $table->text('explanation')->nullable();
            $table->string('topic')->nullable();
            $table->unsignedInteger('exposure_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('source', 20)->default('human'); // human, ai_draft
            $table->timestamps();
        });

        Schema::create('assessment_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('assessment_version')->default(1);
            $table->json('question_ids');
            $table->json('answers')->nullable();
            $table->json('component_scores')->nullable();
            $table->text('practical_submission')->nullable();
            $table->string('practical_url')->nullable();
            // in_progress, submitted, under_review, passed, not_passed, voided
            $table->string('status', 20)->default('in_progress')->index();
            $table->unsignedInteger('score')->default(0);
            $table->unsignedInteger('max_score')->default(0);
            $table->unsignedTinyInteger('percentage')->default(0);
            $table->unsignedTinyInteger('level_awarded')->nullable();
            $table->json('integrity_flags')->nullable();
            $table->unsignedSmallInteger('focus_losses')->default(0);
            $table->text('feedback')->nullable();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reviewer_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_attempts');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('assessments');
    }
};
