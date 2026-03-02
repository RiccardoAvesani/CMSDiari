<?php

namespace App\Models;

use App\Traits\HasStatusSoftDelete;
use App\Models\Concerns\Blameable;
use App\Models\Concerns\HasStatiMetaData;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TemplateType extends Model
{
    use HasFactory;
    use Blameable;
    use HasStatusSoftDelete;
    use HasStatiMetaData;

    protected $table = 'template_types';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_DELETED = 'deleted';

    public const STATI_META = [
        self::STATUS_ACTIVE => ['label' => 'Attivo', 'color' => 'success'],
        self::STATUS_DELETED => ['label' => 'Eliminato', 'color' => 'danger'],
    ];

    public const SIZE_S = 'S';
    public const SIZE_M = 'M';
    public const SIZE_L = 'L';
    public const SIZE_XL = 'XL';

    protected $fillable = [
        'description',
        'size',
        'constraints',
        'structure',
        'max_pages',
        'sort',
        'status',
        'is_custom_finale',
        'is_giustificazioni',
        'is_permessi',
        'is_visite',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'constraints' => 'array',
        'structure' => 'array',
        'max_pages' => 'integer',
        'sort' => 'integer',
        'is_custom_finale' => 'boolean',
        'is_giustificazioni' => 'boolean',
        'is_permessi' => 'boolean',
        'is_visite' => 'boolean',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(TemplateTypePageType::class, 'template_type_id')->orderBy('sort');
    }

    public function pageTypes(): BelongsToMany
    {
        return $this->belongsToMany(
            PageType::class,
            'template_type_page_type',
            'template_type_id',
            'page_type_id'
        )
            ->withPivot('position')
            ->withTimestamps()
            ->orderBy('sort');
    }

    public function templates(): HasMany
    {
        return $this->hasMany(Template::class, 'template_type_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function sizeOptions(): array
    {
        return [
            self::SIZE_S => 'S - Testi Brevi',
            self::SIZE_M => 'M - Testi Standard',
            self::SIZE_L => 'L - Testi Estesi',
            self::SIZE_XL => 'XL - Testi Extra Large',
        ];
    }

    public static function defaultConstraintsForSize(?string $size): array
    {
        $size = $size ?? self::SIZE_M;

        return match ($size) {
            self::SIZE_S => [
                'title' => 30,
                'subtitle' => 50,
                'text_short' => 500,
                'text_main' => 1000,
                'note' => 1000,
                'calendar_line' => 150,
            ],
            self::SIZE_M => [
                'title' => 50,
                'subtitle' => 100,
                'text_short' => 800,
                'text_main' => 2000,
                'note' => 2000,
                'calendar_line' => 300,
            ],
            self::SIZE_L => [
                'title' => 100,
                'subtitle' => 150,
                'text_short' => 1200,
                'text_main' => 3000,
                'note' => 3000,
                'calendar_line' => 600,
            ],
            self::SIZE_XL => [
                'title' => 150,
                'subtitle' => 200,
                'text_short' => 1500,
                'text_main' => 4000,
                'note' => 4000,
                'calendar_line' => 800,
            ],
            default => self::defaultConstraintsForSize(self::SIZE_M),
        };
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function softDelete(): void
    {
        $this->status = self::STATUS_DELETED;
        $this->save();
    }

    public function restore(): void
    {
        $this->status = self::STATUS_ACTIVE;
        $this->save();
    }

    public function getIsDeletedAttribute(): bool
    {
        return $this->status === self::STATUS_DELETED;
    }
}
