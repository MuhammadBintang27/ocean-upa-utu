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
    }

    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1]);
    }
}
