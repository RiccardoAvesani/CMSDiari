<?php

namespace App\Filament\Resources\Settings;

use App\Filament\Resources\Settings\Pages\CreateSetting;
use App\Filament\Resources\Settings\Pages\EditSetting;
use App\Filament\Resources\Settings\Pages\ListSettings;
use App\Filament\Resources\Settings\Pages\ViewSetting;
use App\Filament\Resources\Settings\Schemas\SettingForm;
use App\Filament\Resources\Settings\Schemas\SettingInfolist;
use App\Filament\Resources\Settings\Tables\SettingsTable;
use App\Models\Setting;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class SettingResource extends Resource
{
    protected static ?string $model = Setting::class;

    protected static ?string $recordTitleAttribute = 'description';

    protected static ?int $navigationSort = 90;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Impostazioni';
    protected static ?string $modelLabel = 'Impostazione';
    protected static ?string $pluralModelLabel = 'Impostazioni';

    public static function form(Schema $schema): Schema
    {
        return SettingForm::configureSchema($schema);
    }

    public static function table(Table $table): Table
    {
        return SettingsTable::configureTable($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SettingInfolist::configureSchema($schema);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function canViewAny(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        $role = (string) ($user->role ?? '');

        return str_starts_with($role, 'admin') || str_starts_with($role, 'internal');
    }

    public static function canCreate(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        $role = (string) ($user->role ?? '');

        return str_starts_with($role, 'admin');
    }


    public static function canEdit($record): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return str_starts_with((string) $user->role, 'admin')
            || ((string) $user->role === User::ROLE_INTERNAL_REDATTORE);
    }

    public static function canDelete($record): bool
    {
        return self::canEdit($record);
    }

    public static function defaultValues(): array
    {
        return [
            'ETB_SYNC_INTERVAL_MINUTES' => 60,
            'INVITATION_EXPIRY_DAYS' => 30,
            'COLLECTION_PERIOD_DAYS' => 30,
            'ANNOTATION_PERIOD_DAYS' => 30,
            'COLLECTION_GRACE_DAYS' => 3,
            'MAX_CORRECTION_CYCLES' => 3,
            'AUTOSAVE_SECONDS' => 15,
            'MAX_UPLOAD_MB' => 20,
            'IMAGE_MIN_DPI' => 300,
            'IMAGE_ALLOWED_FORMATS' => ['jpg', 'jpeg', 'png', 'tif', 'tiff'],
        ];
    }

    public static function getValueType(string $name): string
    {
        return match ($name) {
            'IMAGE_ALLOWED_FORMATS' => 'array',
            default => 'int',
        };
    }

    public static function normalizeValueForForm(string $name, mixed $value): mixed
    {
        $type = self::getValueType($name);

        if ($type === 'array') {
            if (is_array($value)) {
                return $value;
            }

            if (is_string($value)) {
                $value = trim($value);

                if ($value === '') {
                    return [];
                }

                $decoded = json_decode($value, true);

                return is_array($decoded) ? $decoded : [];
            }

            return [];
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return $value;
    }

    public static function normalizeValueForSave(string $name, mixed $value): mixed
    {
        $type = self::getValueType($name);

        if ($type === 'array') {
            if (is_array($value)) {
                return array_values($value);
            }

            if (is_string($value)) {
                $value = trim($value);

                if ($value === '') {
                    return [];
                }

                $decoded = json_decode($value, true);

                return is_array($decoded) ? array_values($decoded) : [];
            }

            return [];
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return $value;
    }

    public static function formatValueForTable(string $name, mixed $value): string
    {
        $type = self::getValueType($name);

        if ($type === 'array') {
            $arr = self::normalizeValueForForm($name, $value);

            if (is_array($arr)) {
                return implode(', ', array_map(static fn($v): string => (string) $v, $arr));
            }

            return '-';
        }

        if (is_scalar($value) || $value === null) {
            $s = trim((string) ($value ?? ''));

            return $s !== '' ? $s : '-';
        }

        $encoded = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $encoded !== false && $encoded !== '' ? $encoded : '-';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSettings::route('/'),
            'create' => CreateSetting::route('/create'),
            'view' => ViewSetting::route('/{record}'),
            'edit' => EditSetting::route('/{record}/edit'),
        ];
    }
}
