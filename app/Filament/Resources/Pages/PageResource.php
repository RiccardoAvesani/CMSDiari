<?php

declare(strict_types=1);

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Filament\Resources\Pages\Pages\ViewPage;
use App\Filament\Resources\Pages\Schemas\PageForm;
use App\Filament\Resources\Pages\Schemas\PageInfolist;
use App\Filament\Resources\Pages\Tables\PagesTable;
use App\Models\Page;
use App\Models\User;
use App\Models\Order;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static ?string $recordTitleAttribute = 'description';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Pagine';
    protected static ?string $modelLabel = 'Pagina';
    protected static ?string $pluralModelLabel = 'Pagine';

    public static function form(Schema $schema): Schema
    {
        return PageForm::configureSchema($schema);
    }

    public static function table(Table $table): Table
    {
        return PagesTable::configureTable($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PageInfolist::configureSchema($schema);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        /** @var User|null $user */
        $user = Auth::user();

        if ($user?->role && str_starts_with((string) $user->role, 'external')) {
            $schoolIds = $user->schools()->pluck('schools.id');
            $query->whereIn('school_id', $schoolIds);
        }

        return $query->orderBy('sort');
    }

    public static function canViewAny(): bool
    {
        return (bool) Auth::user();
    }

    public static function canCreate(): bool
    {
        // Le pagine vengono generate dal Sistema, una per Tipologia Pagina ammessa dal Modello Generico,
        // ma è possibile crearne altre fino al raggiungimento di 'max_pages'.
        return true;
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

        if (! $record instanceof Page) {
            return false;
        }

        $schoolIds = $user->schools()->pluck('schools.id')->all();

        if (! Page::query()->whereKey($record->getKey())->whereIn('school_id', $schoolIds)->exists()) {
            return false;
        }

        return self::canExternalEditValues($record->order);
    }

    public static function canDelete($record): bool
    {
        return self::canEdit($record);
    }

    public static function canView($record): bool
    {
        return self::canEdit($record);
    }

    public static function canExternalEditValues(?Order $order): bool
    {
        if (! $order) {
            return false;
        }

        $now = Carbon::now();

        if (($order->status ?? null) === Order::STATUS_COLLECTION) {
            if (! $order->deadlinecollection) {
                return true;
            }

            return $now->lessThanOrEqualTo(Carbon::parse($order->deadlinecollection));
        }

        // External: NON più modificabile in Correzione/Annotation.
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'view' => ViewPage::route('/{record}'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }
}
