<?php

namespace App\Models;

use App\Support\Labels;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentAttempt extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'question_ids' => 'array',
        'answers' => 'array',
        'component_scores' => 'array',
        'integrity_flags' => 'array',
        'started_at' => 'datetime',
        'expires_at' => 'datetime',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function questions()
    {
        $ids = $this->question_ids ?? [];

        return Question::whereIn('id', $ids)->get()->sortBy(fn ($q) => array_search($q->id, $ids))->values();
    }

    public function isOpen(): bool
    {
        return $this->status === 'in_progress' && now()->lt($this->expires_at);
    }

    public function secondsLeft(): int
    {
        return max(0, now()->diffInSeconds($this->expires_at, false));
    }

    public function statusLabel(): string
    {
        return Labels::ATTEMPT_STATUS[$this->status] ?? $this->status;
    }
}
