<?php

namespace App\Filament\Resources\Campaigns;

use App\Filament\Resources\Campaigns\Pages\CreateCampaign;
use App\Filament\Resources\Campaigns\Pages\EditCampaign;
use App\Filament\Resources\Campaigns\Pages\ListCampaigns;
use App\Filament\Resources\Campaigns\Pages\ViewCampaign;
use App\Filament\Resources\Campaigns\Schemas\CampaignForm;
use App\Filament\Resources\Campaigns\Schemas\CampaignInfolist;
use App\Filament\Resources\Campaigns\Tables\CampaignTable;
use App\Models\Campaign;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class CampaignResource extends Resource
{
    protected static ?string $model = Campaign::class;

    protected static ?string $recordTitleAttribute = 'description';

    protected static ?string $navigationLabel = 'Campagne';

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'Campagna';

    protected static ?string $pluralModelLabel = 'Campagne';

    public static function form(Schema $schema): Schema
    {
        return CampaignForm::configureSchema($schema);
    }

    public static function table(Table $table): Table
    {
        return CampaignTable::configureTable($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CampaignInfolist::configureSchema($schema);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        /** @var User|null $user */
        $user = Auth::user();

        if ($user?->role && str_starts_with($user->role, 'external')) {
            $schoolIds = $user->schools()->pluck('schools.id');
            $query->whereHas('orders', fn(Builder $q) => $q->whereIn('school_id', $schoolIds));
        }

        return $query;
    }

    public static function canViewAny(): bool
    {
        return (bool) Auth::user();
    }

    public static function canCreate(): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        return str_starts_with((string) $user->role, 'admin')
            || in_array((string) $user->role, ['internal|redattore', 'internal|grafico'], true);
    }

    public static function canEdit($record): bool
    {
        return self::canCreate();
    }

    public static function canDelete($record): bool
    {
        return self::canCreate();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCampaigns::route('/'),
            'create' => CreateCampaign::route('/create'),
            'view' => ViewCampaign::route('/{record}'),
            'edit' => EditCampaign::route('/{record}/edit'),
        ];
    }
}
