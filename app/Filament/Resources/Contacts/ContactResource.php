<?php

namespace App\Filament\Resources\Contacts;

use App\Filament\Resources\Contacts\Pages\CreateContact;
use App\Filament\Resources\Contacts\Pages\EditContact;
use App\Filament\Resources\Contacts\Pages\ListContacts;
use App\Filament\Resources\Contacts\Pages\ViewContact;
use App\Filament\Resources\Contacts\Schemas\ContactForm;
use App\Filament\Resources\Contacts\Schemas\ContactInfolist;
use App\Filament\Resources\Contacts\Tables\ContactsTable;
use App\Models\Contact;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static ?string $recordTitleAttribute = 'full_name';

    protected static ?string $navigationLabel = 'Contatti';

    protected static ?int $navigationSort = 22;

    protected static ?string $modelLabel = 'Contatto';

    protected static ?string $pluralModelLabel = 'Contatti';

    public static function formSchema(Schema $schema): Schema
    {
        return ContactForm::configureSchema($schema);
    }

    public static function table(Table $table): Table
    {
        return ContactsTable::configureTable($table);
    }

    public static function infolistSchema(Schema $schema): Schema
    {
        return ContactInfolist::configureSchema($schema);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        /** @var User|null $user */
        $user = Auth::user();

        $role = (string) ($user?->role ?? '');

        if (str_starts_with($role, 'external')) {
            $schoolIds = $user?->schools()->pluck('schools.id')->all() ?? [];

            $query->whereHas('location', function (Builder $q) use ($schoolIds): void {
                $q->whereIn('schoolid', $schoolIds);
            });
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContacts::route('/'),
            'create' => CreateContact::route('/create'),
            'view' => ViewContact::route('/{record}'),
            'edit' => EditContact::route('/{record}/edit'),
        ];
    }
}
