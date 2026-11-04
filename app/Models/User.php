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

    protected $fillable = ['name', 'username', 'school', 'password', 'plain_password', 'is_admin'];

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
}
