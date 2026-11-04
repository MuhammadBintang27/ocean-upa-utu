<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends Model
{
    protected $fillable = ['name', 'directions', 'sort', 'duration_minutes', 'correct_points', 'wrong_points', 'blank_points'];

    protected function casts(): array
    {
        return ['correct_points' => 'float', 'wrong_points' => 'float', 'blank_points' => 'float'];
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
