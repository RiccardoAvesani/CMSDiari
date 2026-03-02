<?php

namespace App\Models;

use App\Models\Concerns\Blameable;
use App\Models\Concerns\HasStatiMetadata;
use App\Traits\HasStatusSoftDelete;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class Invitation extends Model
{
    use Blameable;
    use HasStatusSoftDelete;
    use HasStatiMetadata;

    public const STATUS_READY = 'ready';
    public const STATUS_INVITED = 'invited';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_REGISTERED = 'registered';
    public const STATUS_DELETED = 'deleted';
    public const STATUS_RESTORED = self::STATUS_READY;

    public const STATI_META = [
        self::STATUS_READY => ['label' => 'Pronto', 'color' => 'primary'],
        self::STATUS_INVITED => ['label' => 'Inviato', 'color' => 'info'],
        self::STATUS_RECEIVED => ['label' => 'Ricevuto', 'color' => 'warning'],
        self::STATUS_EXPIRED => ['label' => 'Scaduto', 'color' => 'danger'],
        self::STATUS_REGISTERED => ['label' => 'Registrato', 'color' => 'success'],
        self::STATUS_ACTIVE => ['label' => 'Aperto', 'color' => 'success'],
        self::STATUS_DELETED => ['label' => 'Eliminato', 'color' => 'danger'],
    ];

    protected $fillable = [
        'school_id',
        'order_id',
        'user_id',
        'email',
        'subject',
        'message',
        'access_token',
        'open_token',
        'role',
        'status',
        'sent_at',
        'received_at',
        'received_via',
        'expires_at',
        'registered_at',
        'sort',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'received_at' => 'datetime',
        'expires_at' => 'datetime',
        'registered_at' => 'datetime',
        'sort' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $invitation) {
            // token “non segreto” ma non indovinabile
            if (empty($invitation->access_token)) {
                do {
                    $t = Str::random(64);
                } while (self::where('access_token', $t)->exists());
                $invitation->access_token = $t;
            }

            // open token per pixel
            if (empty($invitation->open_token)) {
                do {
                    $t = Str::random(64);
                } while (self::where('open_token', $t)->exists());
                $invitation->open_token = $t;
            }
        });
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function canBeOpened(): bool
    {
        return ! in_array($this->status, [
            self::STATUS_REGISTERED,
            self::STATUS_ACTIVE,
            self::STATUS_DELETED,
        ], true) && $this->user_id === null;
    }

    public function isExpired(): bool
    {
        if ($this->expires_at === null) {
            return false;
        }

        $expiresAt = $this->expires_at instanceof Carbon
            ? $this->expires_at
            : Carbon::parse($this->expires_at);

        return now()->greaterThan($expiresAt);
    }

    public function markExpiredIfNeeded(): void
    {
        if (! $this->isExpired()) {
            return;
        }

        if (in_array($this->status, [
            self::STATUS_EXPIRED,
            self::STATUS_REGISTERED,
            self::STATUS_ACTIVE,
        ], true)) {
            return;
        }

        $this->forceFill(['status' => self::STATUS_EXPIRED])->save();
    }

    public function markReceived(string $via): void
    {
        if (! in_array($via, ['pixel', 'link'], true)) {
            $via = 'link';
        }

        if (! in_array($this->status, [
            self::STATUS_INVITED,
            self::STATUS_RECEIVED,
        ], true)) {
            return;
        }

        if ($this->status !== self::STATUS_RECEIVED) {
            $this->forceFill(['status' => self::STATUS_RECEIVED])->save();
        }

        if ($this->received_at === null) {
            $this->forceFill([
                'received_at' => now(),
                'received_via' => $via,
            ])->save();
        }
    }

    public function restore(): bool
    {
        return $this->update(['status' => $this->getRestoredStatus()]);
    }

    public function getRestoredStatus(): string
    {
        if (! empty($this->user_id)) {
            return self::STATUS_ACTIVE;
        }

        if (! empty($this->registered_at)) {
            return self::STATUS_REGISTERED;
        }

        if (! empty($this->sent_at)) {
            return self::STATUS_INVITED;
        }

        return self::STATUS_READY;
    }
}