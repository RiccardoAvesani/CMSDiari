<?php

namespace App\Filament\Resources\Templates;

use App\Filament\Resources\Templates\Pages\CreateTemplate;
use App\Filament\Resources\Templates\Pages\EditTemplate;
use App\Filament\Resources\Templates\Pages\ListTemplates;
use App\Filament\Resources\Templates\Pages\ViewTemplate;
use App\Filament\Resources\Templates\Schemas\TemplateForm;
use App\Filament\Resources\Templates\Schemas\TemplateInfolist;
use App\Filament\Resources\Templates\Tables\TemplatesTable;
use App\Models\Template;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TemplateResource extends Resource
{
    protected static ?string $model = Template::class;

    protected static ?string $recordTitleAttribute = 'description';

    protected static ?int $navigationSort = 65;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Modelli Compilati';
    protected static ?string $modelLabel = 'Modello Compilato';
    protected static ?string $pluralModelLabel = 'Modelli Compilati';

    public static function form(Schema $schema): Schema
    {
        return TemplateForm::configureSchema($schema);
    }

    public static function table(Table $table): Table
    {
        return TemplatesTable::configureTable($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TemplateInfolist::configureSchema($schema);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        $role = (string) ($user->role ?? '');

        if (str_starts_with($role, 'external')) {
            $schoolIds = $user->schools()->pluck('schools.id')->all();

            return $query->whereIn('school_id', $schoolIds);
        }

        return $query;
    }

    public static function canViewAny(): bool
    {
        return (bool) Auth::user();
    }

    public static function canCreate(): bool
    {
        // I Modelli Compilati vengono creati da Ordini o dal Sistema.
        return false;
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

        if (! str_starts_with($role, 'external')) {
            return false;
        }

        $schoolIds = $user->schools()->pluck('schools.id')->all();

        return Template::query()
            ->whereKey($record->getKey())
            ->whereIn('school_id', $schoolIds)
            ->exists();
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
            'index' => ListTemplates::route('/'),
            'create' => CreateTemplate::route('/create'),
            'view' => ViewTemplate::route('/{record}'),
            'edit' => EditTemplate::route('/{record}/edit'),
        ];
    }
}
