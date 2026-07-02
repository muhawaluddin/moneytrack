<?php

namespace App\Observers;

use App\Models\Account;
use App\Models\Space;
use Illuminate\Database\Eloquent\Model;

class SpaceDataObserver
{
    public function saved(Model $model): void
    {
        if ($this->isShared($model)) {
            $this->bump($model);
        }
    }

    public function deleted(Model $model): void
    {
        if ($this->isShared($model)) {
            $this->bump($model);
        }
    }

    private function isShared(Model $model): bool
    {
        if (! $model->space_id) {
            return false;
        }

        return ! $model instanceof Account
            || $model->visibility === 'shared'
            || $model->getOriginal('visibility') === 'shared';
    }

    private function bump(Model $model): void
    {
        Space::whereKey($model->space_id)->where('type', 'family')->increment('sync_version');
    }
}
