<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait Blameable
{
    protected static function bootBlameable(): void
    {
        static::creating(function (Model $model): void {
            $userId = Auth::id();

            if (! $userId) {
                return;
            }

            if (! User::query()->whereKey($userId)->exists()) {
                return;
            }

            if (blank($model->getAttribute('created_by'))) {
                $model->setAttribute('created_by', $userId);
            }

            $model->setAttribute('updated_by', $userId);
        });

        static::updating(function (Model $model): void {
            $userId = Auth::id();

            if (! $userId) {
                return;
            }

            if (! User::query()->whereKey($userId)->exists()) {
                return;
            }

            $model->setAttribute('updated_by', $userId);
        });
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
