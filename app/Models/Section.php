<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends Model
{
    protected $fillable = ['name', 'directions', 'sort', 'duration_minutes', 'correct_points', 'wrong_points', 'blank_points', 'is_practice'];

    protected function casts(): array
    {
        return ['correct_points' => 'float', 'wrong_points' => 'float', 'blank_points' => 'float', 'is_practice' => 'boolean'];
    }

    /** The scored sections, in exam order. */
    public function scopeExam($query)
    {
        return $query->where('is_practice', false)->orderBy('sort')->orderBy('id');
    }

    /** Sections of the practice round (never scored), or of the real exam. */
    public function scopeMode($query, bool $practice)
    {
        return $query->where('is_practice', $practice)->orderBy('sort')->orderBy('id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('sort')->orderBy('id');
    }

    public function passages(): HasMany
    {
        return $this->hasMany(Passage::class)->orderBy('sort')->orderBy('id');
    }
}
