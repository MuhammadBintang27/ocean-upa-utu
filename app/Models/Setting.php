<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['is_open', 'max_violations', 'shuffle_questions', 'practice_open', 'practice_round'];

    protected function casts(): array
    {
        return ['is_open' => 'boolean', 'shuffle_questions' => 'boolean', 'practice_open' => 'boolean', 'practice_round' => 'integer', 'opened_at' => 'datetime'];
    }

    /** The practice round runs only while the real exam is closed. */
    public function practiceRunning(): bool
    {
        return $this->practice_open && ! $this->is_open;
    }

    protected static function booted(): void
    {
        // The first section's clock starts for everyone at the moment the admin opens the exam.
        static::saving(function (Setting $setting) {
            if ($setting->isDirty('is_open') && $setting->is_open) {
                $setting->opened_at = now();
                $setting->practice_open = false; // the real exam ends the practice round
            }
        });
        static::saved(fn () => \Illuminate\Support\Facades\Cache::forget('exam:state'));
    }

    /**
     * [exam open, practice running, practice round], cached so the waiting-room poll never opens a DB connection
     * (the host refuses more than ~20 new connections per second).
     */
    public static function state(): array
    {
        return \Illuminate\Support\Facades\Cache::remember('exam:state', 3, function () {
            $s = static::current();

            return [$s->is_open, $s->practiceRunning(), (int) $s->practice_round];
        });
    }

    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1]);
    }
}
