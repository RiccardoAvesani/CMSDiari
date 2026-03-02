<?php

namespace App\Models;

use App\Traits\HasStatusSoftDelete;
use App\Models\Concerns\Blameable;
use App\Models\Concerns\HasStatiMetaData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PageType extends Model
{
    use Blameable;
    use HasStatusSoftDelete;
    use HasStatiMetaData;

    protected $table = 'page_types';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_DELETED = 'deleted';
    public const STATUS_RESTORED = self::STATUS_ACTIVE;

    public const STATI_META = [
        self::STATUS_ACTIVE => ['label' => 'Attiva', 'color' => 'success'],
        self::STATUS_DELETED => ['label' => 'Eliminata', 'color' => 'danger'],
    ];

    protected $fillable = [
        'description',
        'space',
        'max_pages',
        'structure',
        'icon_url',
        'sort',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'space' => 'decimal:2',
        'max_pages' => 'integer',
        'structure' => 'array',
        'sort' => 'integer',
    ];

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class, 'page_type_id');
    }

    public function templateTypePageTypes(): HasMany
    {
        return $this->hasMany(TemplateTypePageType::class, 'page_type_id');
    }
}
