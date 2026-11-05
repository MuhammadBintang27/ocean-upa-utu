<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['is_open', 'max_violations', 'shuffle_questions'];

    protected function casts(): array
    {
        return ['is_open' => 'boolean', 'shuffle_questions' => 'boolean', 'opened_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        // The first section's clock starts for everyone at the moment the admin opens the exam.
        static::saving(function (Setting $setting) {
            if ($setting->isDirty('is_open') && $setting->is_open) {
                $setting->opened_at = now();
            }
        });
        static::saved(fn () => \Illuminate\Support\Facades\Cache::forget('exam:open'));
    }

    /** Cached so the waiting-room poll never opens a DB connection (shared hosting allows only 50). */
    public static function isOpen(): bool
    {
        return \Illuminate\Support\Facades\Cache::remember('exam:open', 3, fn () => static::current()->is_open);
    }

    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1]);
    }
}
