<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasStatusSoftDelete
{
    public function softDelete(): bool
    {
        return $this->update([
            'status' => static::deletedStatusValue(),
        ]);
    }

    public function restore(): bool
    {
        return $this->update([
            'status' => static::restoredStatusValue(),
        ]);
    }

    /**
     * Sovrascrivo il metodo delete() nativo per maggiore sicurezza:
     * qualsiasi chiamata a delete() diventerà automaticamente una soft delete basata sullo stato.
     * In questo modo blocchiamo cancellazioni fisiche involontarie dal DB.
     */
    public function delete()
    {
        return $this->softDelete();
    }

    /**
     * Metodo per la cancellazione fisica, qualora fosse espressamente necessaria.
     */
    public function forceDelete()
    {
        return parent::delete();
    }

    public function isDeleted(): bool
    {
        return $this->status === static::deletedStatusValue();
    }

    public function getIsDeletedAttribute(): bool
    {
        return $this->isDeleted();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', '!=', static::deletedStatusValue());
    }

    public function scopeOnlyDeleted(Builder $query): Builder
    {
        return $query->where('status', static::deletedStatusValue());
    }

    protected static function deletedStatusValue(): string
    {
        if (defined(static::class . '::STATUS_DELETED')) {
            /** @var string $value */
            $value = constant(static::class . '::STATUS_DELETED');

            return $value;
        }

        return 'deleted';
    }

    protected static function restoredStatusValue(): string
    {
        if (defined(static::class . '::STATUS_RESTORED')) {
            /** @var string $value */
            $value = constant(static::class . '::STATUS_RESTORED');

            return $value;
        }

        if (defined(static::class . '::STATUS_ACTIVE')) {
            /** @var string $value */
            $value = constant(static::class . '::STATUS_ACTIVE');

            return $value;
        }

        return 'active';
    }
}
