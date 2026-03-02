<?php

namespace App\Models;

use App\Traits\HasStatusSoftDelete;
use App\Models\Concerns\Blameable;
use App\Models\Concerns\HasStatiMetaData;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class School extends Model
{
    use Blameable;
    use HasStatusSoftDelete;
    use HasStatiMetaData;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_DELETED = 'deleted';
    public const STATUS_RESTORED = self::STATUS_ACTIVE;

    public const STATUS_META = [
        self::STATUS_ACTIVE => ['label' => 'Attiva', 'color' => 'success'],
        self::STATUS_DELETED => ['label' => 'Eliminata', 'color' => 'danger'],
    ];

    protected $fillable = [
        'external_id',
        'description',
        'codice_fiscale',
        'status',
        'created_by',
        'updated_by',
        'sort',
    ];

    protected $casts = [
        'sort' => 'integer',
    ];

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'school_user');
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class, 'school_id');
    }

    public function contacts(): HasManyThrough
    {
        return $this->hasManyThrough(
            Contact::class,   // modello finale
            Location::class,  // modello intermedio
            'school_id',      // FK su locations che punta a schools
            'location_id',    // FK su contacts che punta a locations
            'id',             // PK su schools
            'id',             // PK su locations
        );
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'school_id');
    }
}
