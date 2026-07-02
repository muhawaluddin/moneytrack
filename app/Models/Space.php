<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Space extends Model
{
    protected $fillable = ['owner_id', 'name', 'type', 'color'];
    protected function casts(): array
    {
        return ['sync_version' => 'integer'];
    }

    public function bumpSyncVersion(): void
    {
        static::whereKey($this->id)->increment('sync_version');
        $this->sync_version = (int) $this->sync_version + 1;
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members()
    {
        return $this->belongsToMany(User::class)->withPivot('role', 'joined_at')->withTimestamps();
    }

    public function accounts()
    {
        return $this->hasMany(Account::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function monthlyClosings()
    {
        return $this->hasMany(MonthlyClosing::class);
    }

    public function financialGoals()
    {
        return $this->hasMany(FinancialGoal::class);
    }

    public function invitations()
    {
        return $this->hasMany(SpaceInvitation::class);
    }

    public function roleFor(User $user): ?string
    {
        return $this->members->firstWhere('id', $user->id)?->pivot->role;
    }

    public function canManage(User $user): bool
    {
        return in_array($this->roleFor($user), ['owner', 'manager'], true);
    }

    public function visibleAccounts(User $user)
    {
        return $this->type === 'family'
            ? $this->accounts()->where('visibility', 'shared')
            : $this->accounts()->where('user_id', $user->id);
    }
}
