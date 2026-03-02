<?php

namespace App\Filament\Resources\Schools;

use App\Filament\Resources\Schools\Pages\CreateSchool;
use App\Filament\Resources\Schools\Pages\EditSchool;
use App\Filament\Resources\Schools\Pages\ListSchools;
use App\Filament\Resources\Schools\Pages\ViewSchool;
use App\Filament\Resources\Schools\Schemas\SchoolForm;
use App\Filament\Resources\Schools\Schemas\SchoolInfolist;
use App\Filament\Resources\Schools\Tables\SchoolsTable;
use App\Models\School;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SchoolResource extends Resource
{
    protected static ?string $model = School::class;

    protected static ?string $recordTitleAttribute = 'description';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Scuole';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $modelLabel = 'Scuola';

    protected static ?string $pluralModelLabel = 'Scuole';

    public static function form(Schema $schema): Schema
    {
        return SchoolForm::configureSchema($schema);
    }

    public static function table(Table $table): Table
    {
        return SchoolsTable::configureTable($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SchoolInfolist::configureSchema($schema);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSchools::route('/'),
            'create' => CreateSchool::route('/create'),
            'view' => ViewSchool::route('/{record}'),
            'edit' => EditSchool::route('/{record}/edit'),
        ];
    }
}
