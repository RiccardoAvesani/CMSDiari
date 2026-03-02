<?php

namespace App\Filament\Resources\TemplateTypes;

use App\Filament\Resources\TemplateTypes\Pages\CreateTemplateType;
use App\Filament\Resources\TemplateTypes\Pages\EditTemplateType;
use App\Filament\Resources\TemplateTypes\Pages\ListTemplateTypes;
use App\Filament\Resources\TemplateTypes\Pages\ViewTemplateType;
use App\Filament\Resources\TemplateTypes\Schemas\TemplateTypeForm;
use App\Filament\Resources\TemplateTypes\Schemas\TemplateTypeInfolist;
use App\Filament\Resources\TemplateTypes\Tables\TemplateTypesTable;
use App\Models\TemplateType;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TemplateTypeResource extends Resource
{
    protected static ?string $model = TemplateType::class;

    protected static ?string $recordTitleAttribute = 'description';

    protected static ?int $navigationSort = 62;

    protected static ?string $navigationLabel = 'Modelli Generici';
    protected static ?string $modelLabel = 'Modello Generico';
    protected static ?string $pluralModelLabel = 'Modelli Generici';

    public static function form(Schema $schema): Schema
    {
        return TemplateTypeForm::configureSchema($schema);
    }

    public static function table(Table $table): Table
    {
        return TemplateTypesTable::configureTable($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TemplateTypeInfolist::configureSchema($schema);
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
        return self::canViewAny();
    }

    public static function canEdit($record): bool
    {
        return self::canViewAny();
    }

    public static function canDelete($record): bool
    {
        return self::canViewAny();
    }

    public static function canView($record): bool
    {
        return self::canViewAny();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTemplateTypes::route('/'),
            'create' => CreateTemplateType::route('/create'),
            'view' => ViewTemplateType::route('/{record}'),
            'edit' => EditTemplateType::route('/{record}/edit'),
        ];
    }
}
