<?php

namespace App\Models;

use App\Traits\HasStatusSoftDelete;
use App\Models\Concerns\Blameable;
use App\Models\Concerns\HasStatiMetaData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Page extends Model
{
    use Blameable;
    use HasStatusSoftDelete;
    use HasStatiMetaData;

    protected $table = 'pages';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_DELETED = 'deleted';
    public const STATUS_RESTORED = self::STATUS_ACTIVE;

    public const STATI_META = [
        self::STATUS_ACTIVE => ['label' => 'Attiva', 'color' => 'success'],
        self::STATUS_DELETED => ['label' => 'Eliminata', 'color' => 'danger'],
    ];

    protected $fillable = [
        'page_type_id',
        'template_id',
        'order_id',
        'school_id',

        'position',
        'sort',

        'description',
        'structure',

        'page_type_description',
        'page_type_space',
        'page_type_max_pages',
        'page_type_icon_url',
        'page_type_structure',

        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'structure' => 'array',
        'page_type_space' => 'decimal:2',
        'page_type_max_pages' => 'integer',
        'page_type_structure' => 'array',
        'position' => 'integer',
        'sort' => 'integer',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class, 'template_id');
    }

    public function pageType(): BelongsTo
    {
        return $this->belongsTo(PageType::class, 'page_type_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }
}
