<?php

namespace App\Models;

use App\Models\Invitation;
use App\Models\School;
use App\Traits\HasStatusSoftDelete;
use App\Models\Concerns\Blameable;
use App\Models\Concerns\HasStatiMetaData;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Yebor974\Filament\RenewPassword\Contracts\RenewPasswordContract;
use Yebor974\Filament\RenewPassword\Traits\RenewPassword;

class User extends Authenticatable implements FilamentUser, HasName, HasAvatar, RenewPasswordContract, MustVerifyEmail
{
    use HasFactory;
    use Notifiable;
    use MustVerifyEmailTrait;
    use RenewPassword;
    use HasStatusSoftDelete;
    use Blameable;
    use HasStatiMetaData;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_BLOCKED = 'blocked';
    public const STATUS_DELETED = 'deleted';
    public const STATUS_RESTORED = self::STATUS_ACTIVE;

    public const ROLE_ADMIN_ADMIN = 'admin|admin';
    public const ROLE_INTERNAL_REDATTORE = 'internal|redattore';
    public const ROLE_INTERNAL_GRAFICO = 'internal|grafico';
    public const ROLE_EXTERNAL_REFERENTE = 'external|referente';
    public const ROLE_EXTERNAL_COLLABORATORE = 'external|collaboratore';

    public const STATI_META = [
        self::STATUS_ACTIVE => ['label' => 'Attivo', 'color' => 'success'],
        self::STATUS_BLOCKED => ['label' => 'Bloccato', 'color' => 'warning'],
        self::STATUS_DELETED => ['label' => 'Eliminato', 'color' => 'danger'],
    ];

    public const ROLES_META = [
        self::ROLE_ADMIN_ADMIN => ['label' => 'Admin Gestionale', 'color' => 'danger'],
        self::ROLE_INTERNAL_REDATTORE => ['label' => 'Redattore', 'color' => 'info'],
        self::ROLE_INTERNAL_GRAFICO => ['label' => 'Grafico', 'color' => 'info'],
        self::ROLE_EXTERNAL_REFERENTE => ['label' => 'Referente Scuola', 'color' => 'success'],
        self::ROLE_EXTERNAL_COLLABORATORE => ['label' => 'Collaboratore', 'color' => 'success'],
    ];

    protected $fillable = [
        'first_name',
        'last_name',
        'born_at',
        'company',
        'avatar_url',
        'email',
        'password',

        'role',
        'status',

        'force_renew_password',
        'last_password_renew_at',

        'created_by',
        'updated_by',
        'sort',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'born_at' => 'date',
            'last_password_renew_at' => 'datetime',
            'password' => 'hashed',
            'force_renew_password' => 'boolean',
            'sort' => 'integer',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function getFilamentName(): string
    {
        if (! empty($this->first_name) && ! empty($this->last_name)) {
            return trim($this->first_name . ' ' . $this->last_name);
        }

        return (string) $this->email;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        if (! $this->avatar_url) {
            return null;
        }

        if (str_starts_with($this->avatar_url, 'http')) {
            return $this->avatar_url;
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        return $disk->url($this->avatar_url);
    }

    public function schools(): BelongsToMany
    {
        return $this->belongsToMany(School::class, 'school_user');
    }

    public function acceptedInvitation(): HasOne
    {
        return $this->hasOne(Invitation::class, 'user_id');
    }

    public function fullName(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $fullName = trim((string) ($this->first_name ?? '') . ' ' . (string) ($this->last_name ?? ''));

                return $fullName !== '' ? $fullName : (string) ($this->email ?? '-');
            },
        );
    }

    public function roleName(): Attribute
    {
        return Attribute::make(
            get: fn(): string => self::roleLabel($this->role),
        );
    }

    public static function formatUserName(?User $user): string
    {
        if (! $user) {
            return '-';
        }

        $fullName = trim((string) ($user->full_name ?? ''));
        if ($fullName !== '') {
            return $fullName;
        }

        $fullName = trim((string) ($user->first_name ?? '') . ' ' . (string) ($user->last_name ?? ''));
        if ($fullName !== '') {
            return $fullName;
        }

        return $user->email ?? '-';
    }

    public function isInternal(): Attribute
    {
        return Attribute::make(
            get: fn(): bool => self::isInternalRole($this->role) || self::isAdminRole($this->role),
        );
    }

    public function isExternal(): Attribute
    {
        return Attribute::make(
            get: fn(): bool => self::isExternalRole($this->role),
        );
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeInternal(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->where('role', 'like', 'internal|%')
                ->orWhere('role', 'like', 'admin|%');
        });
    }

    public function scopeExternal(Builder $query): Builder
    {
        return $query->where('role', 'like', 'external|%');
    }

    public function scopeByRole(Builder $query, string $role): Builder
    {
        return $query->where('role', $role);
    }

    public static function rolesMeta(): array
    {
        return self::ROLES_META;
    }

    public static function roleLabels(): array
    {
        $labels = [];

        foreach (self::rolesMeta() as $role => $meta) {
            $label = $meta['label'] ?? null;

            if (! is_string($role) || trim($role) === '') {
                continue;
            }

            if (! is_string($label) || trim($label) === '') {
                $label = $role;
            }

            $labels[$role] = $label;
        }

        return $labels;
    }

    public static function roleColors(): array
    {
        $colors = [];

        foreach (self::rolesMeta() as $role => $meta) {
            $color = $meta['color'] ?? null;

            if (! is_string($role) || trim($role) === '') {
                continue;
            }

            if (! is_string($color) || trim($color) === '') {
                $color = 'gray';
            }

            $colors[$role] = $color;
        }

        return $colors;
    }

    public static function roleOptions(): array
    {
        return self::roleLabels();
    }

    public static function roleLabel(?string $role): string
    {
        if ($role === null) {
            return '-';
        }

        return self::roleLabels()[$role] ?? $role;
    }

    public static function roleColor(?string $role): string
    {
        if ($role === null) {
            return 'gray';
        }

        return self::roleColors()[$role] ?? 'gray';
    }

    public static function isAdminRole(?string $role): bool
    {
        if ($role === null) {
            return false;
        }

        return str_starts_with($role, 'admin');
    }

    public static function isInternalRole(?string $role): bool
    {
        if ($role === null) {
            return false;
        }

        return str_starts_with($role, 'internal');
    }

    public static function isExternalRole(?string $role): bool
    {
        if ($role === null) {
            return false;
        }

        return str_starts_with($role, 'external');
    }

    public static function canAdminOrInternal(?self $user = null): bool
    {
        /** @var User|null $user */
        $user = $user ?? Auth::user();

        if (! $user instanceof self) {
            return false;
        }

        return self::isAdminRole($user->role) || self::isInternalRole($user->role);
    }

    public static function blankToNull(mixed $state): mixed
    {
        if ($state === null) {
            return null;
        }

        if (is_string($state)) {
            $state = trim($state);

            return $state === '' ? null : $state;
        }

        return $state;
    }
}
