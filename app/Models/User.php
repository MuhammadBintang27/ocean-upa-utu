<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    /** Avatar key => label; files live in public/image/avatars/{key}.svg */
    public const AVATARS = [
        'penyu' => 'Penyu', 'lumba' => 'Lumba-lumba', 'gurita' => 'Gurita', 'kepiting' => 'Kepiting',
        'buntal' => 'Ikan Buntal', 'lebah' => 'Lebah', 'sapi' => 'Sapi', 'kelapa' => 'Kelapa Muda',
    ];

    protected $fillable = ['name', 'username', 'school', 'password', 'plain_password', 'is_admin', 'avatar'];

    /** Mark this participant as present in the waiting room (refreshed by its status poll). */
    public static function markInLobby(int $id): void
    {
        // shortcut: presence lives in the cache for 30s, written at most once per 30s; move to DB if the cache driver changes to array
        if (! \Illuminate\Support\Facades\Cache::has("lobby:{$id}")) {
            \Illuminate\Support\Facades\Cache::put("lobby:{$id}", true, 30);
        }
    }

    /** Participants whose waiting-room page polled in the last ~30s. */
    public static function inLobby(): \Illuminate\Support\Collection
    {
        return self::where('is_admin', false)->orderBy('name')->get(['id', 'name', 'school', 'avatar'])
            ->filter(fn (self $u) => \Illuminate\Support\Facades\Cache::has("lobby:{$u->id}"))->values();
    }

    public function avatarUrl(): ?string
    {
        return isset(self::AVATARS[$this->avatar]) ? asset("image/avatars/{$this->avatar}.svg") : null;
    }

    protected $hidden = ['password', 'plain_password', 'remember_token', 'device_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'exam_reset' => 'boolean',
        ];
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(Attempt::class);
    }

    public function violationLogs(): HasMany
    {
        return $this->hasMany(ViolationLog::class)->latest('id');
    }
}
