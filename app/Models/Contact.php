<?php

namespace App\Models;

use App\Traits\HasStatusSoftDelete;
use App\Models\Concerns\Blameable;
use App\Models\Concerns\HasStatiMetadata;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Contact extends Model
{
    use Blameable;
    use HasStatusSoftDelete;
    use HasStatiMetadata;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_DELETED = 'deleted';
    public const STATUS_RESTORED = self::STATUS_ACTIVE;

    public const STATI_META = [
        self::STATUS_ACTIVE => ['label' => 'Attivo', 'color' => 'success'],
        self::STATUS_DELETED => ['label' => 'Eliminato', 'color' => 'danger'],
    ];

    protected $fillable = [
        'location_id',
        'first_name',
        'last_name',
        'telephone',
        'email',
        'sort',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'sort' => 'integer',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Location::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'contactuser', 'contactid', 'userid');
    }

    public function fullName(): string
    {
        $fullName = trim((string) ($this->firstname ?? '') . ' ' . (string) ($this->lastname ?? ''));

        if ($fullName !== '') {
            return $fullName;
        }

        if (! empty($this->email)) {
            return (string) $this->email;
        }

        return '-';
    }
}
