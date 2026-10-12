<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Question extends Model
{
    protected $guarded = ['id'];

    // Answer keys never leave the server.
    protected $hidden = ['correct', 'explanation'];

    protected $casts = [
        'options' => 'array',
        'correct' => 'array',
        'is_active' => 'boolean',
    ];

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class);
    }

    /** Server-side scoring of a single answer. Returns points earned. */
    public function score(mixed $answer): int
    {
        $correct = array_map('strval', (array) $this->correct);

        return match ($this->type) {
            'single' => is_scalar($answer) && in_array((string) $answer, $correct, true) ? $this->points : 0,
            'multiple' => (function () use ($answer, $correct) {
                $given = array_map('strval', array_values((array) $answer));
                sort($given);
                sort($correct);

                return $given === $correct ? $this->points : 0;
            })(),
            'short' => (function () use ($answer, $correct) {
                $norm = fn ($v) => preg_replace('/\s+/', ' ', mb_strtolower(trim((string) $v)));

                return in_array($norm($answer), array_map($norm, $correct), true) ? $this->points : 0;
            })(),
            default => 0,
        };
    }
}
