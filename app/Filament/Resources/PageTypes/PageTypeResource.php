<?php

namespace App\Filament\Resources\PageTypes;

use App\Filament\Resources\PageTypes\Pages\CreatePageType;
use App\Filament\Resources\PageTypes\Pages\EditPageType;
use App\Filament\Resources\PageTypes\Pages\ListPageTypes;
use App\Filament\Resources\PageTypes\Pages\ViewPageType;
use App\Filament\Resources\PageTypes\Schemas\PageTypeForm;
use App\Filament\Resources\PageTypes\Schemas\PageTypeInfolist;
use App\Filament\Resources\PageTypes\Tables\PageTypesTable;
use App\Models\PageType;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PageTypeResource extends Resource
{
    protected static ?string $model = PageType::class;

    protected static ?string $recordTitleAttribute = 'description';

    protected static ?int $navigationSort = 60;

    protected static ?string $navigationLabel = 'Tipologie Pagina';
    protected static ?string $modelLabel = 'Tipologia Pagina';
    protected static ?string $pluralModelLabel = 'Tipologie Pagina';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    public static function form(Schema $schema): Schema
    {
        return PageTypeForm::configureSchema($schema);
    }

    public static function table(Table $table): Table
    {
        return PageTypesTable::configureTable($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PageTypeInfolist::configureSchema($schema);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function canViewAny(): bool
    {
        return (bool) Auth::user();
    }

    public static function canCreate(): bool
    {
        return self::canViewAny();
    }

    public static function canEdit($record): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        $role = (string) ($user->role ?? '');

        if (str_starts_with($role, 'admin') || str_starts_with($role, 'internal')) {
            return true;
        }

        if (str_starts_with($role, 'external')) {
            $schoolIds = $user->schools()->pluck('schools.id');

            return PageType::query()
                ->whereKey($record->getKey())
                ->whereIn('school_id', $schoolIds)
                ->exists();
        }

        return false;
    }

    public static function canDelete($record): bool
    {
        return self::canEdit($record);
    }

    public static function canView($record): bool
    {
        return self::canEdit($record);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPageTypes::route('/'),
            'create' => CreatePageType::route('/create'),
            'view' => ViewPageType::route('/{record}'),
            'edit' => EditPageType::route('/{record}/edit'),
        ];
    }
}
