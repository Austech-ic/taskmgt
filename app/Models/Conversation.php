<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Conversation extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['last_message_at' => 'datetime'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('last_read_at');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->oldest();
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function context(): MorphTo
    {
        return $this->morphTo();
    }

    public function otherParticipant(User $me): ?User
    {
        return $this->users->firstWhere('id', '!=', $me->id);
    }

    /** Find or create a 1:1 conversation between two users. */
    public static function between(User $a, User $b, ?string $subject = null, ?Model $context = null): self
    {
        $existing = self::whereHas('users', fn ($q) => $q->where('users.id', $a->id))
            ->whereHas('users', fn ($q) => $q->where('users.id', $b->id))
            ->when($context, fn ($q) => $q->where('context_type', $context->getMorphClass())->where('context_id', $context->getKey()))
            ->first();

        if ($existing) {
            return $existing;
        }

        $conversation = self::create([
            'subject' => $subject,
            'context_type' => $context?->getMorphClass(),
            'context_id' => $context?->getKey(),
            'last_message_at' => now(),
        ]);
        $conversation->users()->attach([$a->id, $b->id]);

        return $conversation;
    }
}
