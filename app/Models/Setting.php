<?php

namespace App\Models;

use App\Traits\HasStatusSoftDelete;
use App\Models\Concerns\Blameable;
use App\Models\Concerns\HasStatiMetaData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Setting extends Model
{
    use Blameable;
    use HasStatusSoftDelete;
    use HasStatiMetaData;

    protected $table = 'settings';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_DELETED = 'deleted';
    public const STATUS_RESTORED = self::STATUS_ACTIVE;

    public const ENV_DEVELOPMENT = 'development';
    public const ENV_PREVIEW = 'preview';
    public const ENV_PRODUCTION = 'production';

    public const PERMISSION_1 = '1';
    public const PERMISSION_2 = '2';
    public const PERMISSION_3 = '3';
    public const PERMISSION_4 = '4';
    public const PERMISSION_5 = '5';
    public const PERMISSION_6 = '6';

    public const STATI_META = [
        self::STATUS_ACTIVE => ['label' => 'Attiva', 'color' => 'success'],
        self::STATUS_DELETED => ['label' => 'Eliminata', 'color' => 'danger'],
    ];

    protected $fillable = [
        'description',
        'instructions',
        'is_active',
        'value',
        'environment',
        'permission',
        'user_id',
        'status',
        'sort',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'value' => 'array',
        'sort' => 'integer',
        'permission' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function permissionOptions(): array
    {
        return [
            self::PERMISSION_1 => '1 — Visibile da Admin, modificabile da Admin',
            self::PERMISSION_2 => '2 — Visibile da Internal, modificabile da Admin',
            self::PERMISSION_3 => '3 — Visibile da Internal, modificabile da Admin + Internal|redattore',
            self::PERMISSION_4 => '4 — Visibile da tutti, modificabile da Admin + Internal|redattore',
            self::PERMISSION_5 => '5 — Visibile da tutti, modificabile da Admin + Internal|redattore + External|referente',
            self::PERMISSION_6 => '6 — Visibile e modificabile da ciascuno, ma legata ad un Utente',
        ];
    }

    public static function environmentOptions(): array
    {
        return [
            self::ENV_DEVELOPMENT => 'Sviluppo',
            self::ENV_PREVIEW => 'Collaudo',
            self::ENV_PRODUCTION => 'Produzione',
        ];
    }
}
