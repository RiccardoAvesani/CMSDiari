<?php

namespace App\Models;

use App\Traits\HasStatusSoftDelete;
use App\Models\Concerns\Blameable;
use App\Models\Concerns\HasStatiMetaData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use Blameable;
    use HasStatusSoftDelete;
    use HasStatiMetaData;

    public const STATUS_NEW = 'new';
    public const STATUS_COLLECTION = 'collection';
    public const STATUS_DRAFT = 'draft';
    public const STATUS_ANNOTATION = 'annotation';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PRODUCTION = 'production';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_DELETED = 'deleted';
    public const STATUS_RESTORED = self::STATUS_NEW;

    public const STATI_META = [
        self::STATUS_NEW => ['label' => 'Nuovo', 'color' => 'info'],
        self::STATUS_COLLECTION => ['label' => 'In raccolta', 'color' => 'warning'],
        self::STATUS_DRAFT => ['label' => 'Bozza', 'color' => 'gray'],
        self::STATUS_ANNOTATION => ['label' => 'In correzione', 'color' => 'primary'],
        self::STATUS_APPROVED => ['label' => 'Approvato', 'color' => 'success'],
        self::STATUS_PRODUCTION => ['label' => 'In produzione', 'color' => 'info'],
        self::STATUS_COMPLETED => ['label' => 'Completato', 'color' => 'success'],
        self::STATUS_DELETED => ['label' => 'Eliminato', 'color' => 'danger'],
    ];

    protected $fillable = [
        'external_id',
        'campaign_id',
        'school_id',
        'template_id',
        'quantity',
        'deadline_collection',
        'deadline_annotation',
        'status',
        'created_by',
        'updated_by',
        'sort',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'deadline_collection' => 'datetime',
        'deadline_annotation' => 'datetime',
        'sort' => 'integer',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'campaign_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class, 'template_id');
    }
}
