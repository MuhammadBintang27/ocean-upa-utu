<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Question extends Model
{
    public const CHOICES = ['a', 'b', 'c', 'd'];

    protected $fillable = ['section_id', 'passage_id', 'sort', 'text', 'option_a', 'option_b', 'option_c', 'option_d', 'answer'];

    protected $hidden = ['answer'];

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function passage(): BelongsTo
    {
        return $this->belongsTo(Passage::class);
    }
}
