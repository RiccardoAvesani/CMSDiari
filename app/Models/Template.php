<?php

namespace App\Models;

use App\Traits\HasStatusSoftDelete;
use App\Models\Concerns\Blameable;
use App\Models\Concerns\HasStatiMetaData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Template extends Model
{
    use Blameable;
    use HasStatusSoftDelete;
    use HasStatiMetaData;

    protected $table = 'templates';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_DELETED = 'deleted';
    public const STATUS_RESTORED = self::STATUS_ACTIVE;

    public const STATI_META = [
        self::STATUS_ACTIVE => ['label' => 'Attivo', 'color' => 'success'],
        self::STATUS_DELETED => ['label' => 'Eliminato', 'color' => 'danger'],
    ];

    protected $fillable = [
        'template_type_id',
        'order_id',
        'school_id',

        'description',
        'structure',
        'size',
        'constraints',
        'max_pages',

        'sort',
        'is_custom_finale',
        'is_giustificazioni',
        'is_permessi',
        'is_visite',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'structure' => 'array',
        'constraints' => 'array',
        'max_pages' => 'integer',
        'sort' => 'integer',
        'is_custom_finale' => 'boolean',
        'is_giustificazioni' => 'boolean',
        'is_permessi' => 'boolean',
        'is_visite' => 'boolean',
    ];

    public function templateType(): BelongsTo
    {
        return $this->belongsTo(TemplateType::class, 'template_type_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class, 'template_id')->orderBy('sort');
    }
}
