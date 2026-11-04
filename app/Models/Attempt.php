<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Attempt extends Model
{
    protected $fillable = ['user_id', 'section_id', 'started_at', 'finished_at'];

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'finished_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }

    public function deadline(): Carbon
    {
        return $this->started_at->copy()->addMinutes($this->section->duration_minutes);
    }

    /** Closes the attempt if its time is up. Returns true when the attempt is (now) finished. */
    public function closeIfExpired(): bool
    {
        if ($this->finished_at) {
            return true;
        }
        if (now()->greaterThanOrEqualTo($this->deadline())) {
            $this->update(['finished_at' => $this->deadline()]);
            return true;
        }
        return false;
    }

    public function secondsUsed(): ?int
    {
        return $this->finished_at ? (int) $this->started_at->diffInSeconds($this->finished_at) : null;
    }
}
