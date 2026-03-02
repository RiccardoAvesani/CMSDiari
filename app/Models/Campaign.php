<?php

namespace App\Models;

use App\Traits\HasStatusSoftDelete;
use App\Models\Concerns\Blameable;
use App\Models\Concerns\HasStatiMetaData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use Blameable;
    use HasStatusSoftDelete;
    use HasStatiMetaData;

    public const STATUS_PLANNED = 'planned';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_DELETED = 'deleted';
    public const STATUS_RESTORED = self::STATUS_PLANNED;

    public const STATI_META = [
        self::STATUS_PLANNED => ['label' => 'Pianificata', 'color' => 'warning'],
        self::STATUS_ACTIVE => ['label' => 'Attiva', 'color' => 'success'],
        self::STATUS_COMPLETED => ['label' => 'Completata', 'color' => 'info'],
        self::STATUS_DELETED => ['label' => 'Eliminata', 'color' => 'danger'],
    ];

    protected $fillable = [
        'year',
        'description',
        'status',
        'created_by',
        'updated_by',
        'sort',
    ];

    protected $casts = [
        'sort' => 'integer',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'campaign_id');
    }
}
