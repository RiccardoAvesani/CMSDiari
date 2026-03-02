<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages\CreateOrder;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Resources\Orders\Schemas\OrderForm;
use App\Filament\Resources\Orders\Schemas\OrderInfolist;
use App\Filament\Resources\Orders\Tables\OrdersTable;
use App\Models\Order;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $navigationLabel = 'Ordini';

    protected static ?int $navigationSort = 40;

    protected static ?string $modelLabel = 'Ordine';

    protected static ?string $pluralModelLabel = 'Ordini';

    public static function form(Schema $schema): Schema
    {
        return OrderForm::configureSchema($schema);
    }

    public static function table(Table $table): Table
    {
        return OrdersTable::configureTable($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return OrderInfolist::configureSchema($schema);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        /** @var User|null $user */
        $user = Auth::user();

        if ($user?->role && str_starts_with($user->role, 'external')) {
            $schoolIds = $user->schools()->pluck('schools.id');
            $query->whereIn('school_id', $schoolIds);
        }

        return $query;
    }

    public static function canViewAny(): bool
    {
        return (bool) Auth::user();
    }

    private static function isAdminOrInternal(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return str_starts_with((string) $user->role, 'admin')
            || in_array((string) $user->role, ['internal|redattore', 'internal|grafico'], true);
    }

    public static function canCreate(): bool
    {
        return self::isAdminOrInternal(Auth::user());
    }

    public static function canEdit($record): bool
    {
        return self::isAdminOrInternal(Auth::user());
    }

    public static function canDelete($record): bool
    {
        return self::isAdminOrInternal(Auth::user());
    }

    public static function canView($record): bool
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        if (self::isAdminOrInternal($user)) {
            return true;
        }

        $schoolIds = $user->schools()->pluck('schools.id');

        return Order::query()
            ->whereKey($record->getKey())
            ->whereIn('school_id', $schoolIds)
            ->exists();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'create' => CreateOrder::route('/create'),
            'view' => ViewOrder::route('/{record}'),
            'edit' => EditOrder::route('/{record}/edit'),
        ];
    }
}
