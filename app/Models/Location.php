<?php

namespace App\Models;

use App\Traits\HasStatusSoftDelete;
use App\Models\Concerns\Blameable;
use App\Models\Concerns\HasStatiMetaData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use Blameable;
    use HasStatusSoftDelete;
    use HasStatiMetaData;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_DELETED = 'deleted';
    public const STATUS_RESTORED = self::STATUS_ACTIVE;

    public const STATI_META = [
        self::STATUS_ACTIVE => ['label' => 'Attiva', 'color' => 'success'],
        self::STATUS_DELETED => ['label' => 'Eliminata', 'color' => 'danger'],
    ];

    protected $fillable = [
        'school_id',
        'description',
        'address',
        'sort',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'sort' => 'integer',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class, 'location_id');
    }
}
