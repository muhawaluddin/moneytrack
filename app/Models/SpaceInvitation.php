<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpaceInvitation extends Model
{
    protected $fillable = ['space_id', 'invited_by', 'email', 'role', 'token_hash', 'expires_at', 'accepted_at'];
    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'accepted_at' => 'datetime'];
    }

    public function space()
    {
        return $this->belongsTo(Space::class);
    }

    public function inviter()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }
}
