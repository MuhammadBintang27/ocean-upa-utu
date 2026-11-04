<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Passage extends Model
{
    protected $fillable = ['section_id', 'title', 'body', 'sort'];

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }
}
