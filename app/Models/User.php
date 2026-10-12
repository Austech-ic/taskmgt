<?php

namespace App\Models;

use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasFactory, MustVerifyEmail, Notifiable {
        notify as protected traitNotify;
    }

    public const STAFF_ROLES = [
        'reviewer' => 'Assessment reviewer',
        'finance' => 'Finance & disputes',
        'admin' => 'Platform administrator',
        'super_admin' => 'Super administrator',
    ];

    protected $guarded = ['id', 'staff_role', 'status', 'email_verified_at'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'password' => 'hashed',
            'is_talent' => 'boolean',
            'is_hirer' => 'boolean',
            'notification_prefs' => 'array',
        ];
    }

    /** In-app notification is stored first; a failing mail server must never break the request. */
    public function notify($instance): void
    {
        try {
            $this->traitNotify($instance);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function profile(): HasOne
    {
        return $this->hasOne(ProfessionalProfile::class);
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'organization_members')->withPivot('role')->withTimestamps();
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(AssessmentAttempt::class);
    }

    public function clientProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'client_id');
    }

    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class)->withPivot('last_read_at');
    }

    public function payoutRequests(): HasMany
    {
        return $this->hasMany(PayoutRequest::class);
    }

    public function organization(): ?Organization
    {
        return $this->organizations->first();
    }

    public function isStaff(): bool
    {
        return $this->staff_role !== null;
    }

    public function hasStaffRole(string ...$roles): bool
    {
        if ($this->staff_role === 'super_admin') {
            return true;
        }

        return in_array($this->staff_role, $roles, true);
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function avatarUrl(): string
    {
        if ($this->avatar_path) {
            return Storage::disk('public')->url($this->avatar_path);
        }

        return self::initialsAvatar($this->name, $this->email);
    }

    /** Self-contained SVG avatar (no third-party requests, works offline and on any host). */
    public static function initialsAvatar(string $name, string $seed = ''): string
    {
        $palette = [['#6d4aff', '#ffffff'], ['#c6ff3d', '#0e0e14'], ['#ff6b4a', '#ffffff'], ['#48c3ff', '#0e0e14'], ['#ffcf3d', '#0e0e14'], ['#0e0e14', '#c6ff3d']];
        [$bg, $fg] = $palette[crc32($seed ?: $name) % count($palette)];
        $initials = collect(preg_split('/\s+/', trim(preg_replace('/[^\p{L}\s]/u', '', $name))))->filter()->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->take(2)->implode('');
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64" fill="'.$bg.'"/>'
            .'<text x="50%" y="50%" dy=".35em" text-anchor="middle" font-family="Arial,Helvetica,sans-serif" font-size="26" font-weight="700" fill="'.$fg.'">'.e($initials ?: '?').'</text></svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    public function initials(): string
    {
        return collect(explode(' ', $this->name))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('');
    }

    public function firstName(): string
    {
        return explode(' ', trim($this->name))[0];
    }

    public function unreadMessagesCount(): int
    {
        return Message::query()
            ->join('conversation_user', 'conversation_user.conversation_id', '=', 'messages.conversation_id')
            ->where('conversation_user.user_id', $this->id)
            ->where('messages.user_id', '!=', $this->id)
            ->where(function ($q) {
                $q->whereNull('conversation_user.last_read_at')
                    ->orWhereColumn('messages.created_at', '>', 'conversation_user.last_read_at');
            })->count();
    }
}
