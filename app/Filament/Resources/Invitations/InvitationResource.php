<?php

namespace App\Filament\Resources\Invitations;

use App\Filament\Resources\Invitations\Pages\CreateInvitation;
use App\Filament\Resources\Invitations\Pages\EditInvitation;
use App\Filament\Resources\Invitations\Pages\ListInvitations;
use App\Filament\Resources\Invitations\Pages\ViewInvitation;
use App\Filament\Resources\Invitations\Schemas\InvitationForm;
use App\Filament\Resources\Invitations\Schemas\InvitationInfolist;
use App\Filament\Resources\Invitations\Tables\InvitationsTable;
use App\Models\Invitation;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class InvitationResource extends Resource
{
    protected static ?string $model = Invitation::class;

    protected static ?string $recordTitleAttribute = 'email';

    protected static ?string $navigationLabel = 'Inviti';

    protected static ?int $navigationSort = 12;

    protected static ?string $modelLabel = 'Invito';

    protected static ?string $pluralModelLabel = 'Inviti';

    public static function form(Schema $schema): Schema
    {
        return InvitationForm::configureSchema($schema);
    }

    public static function table(Table $table): Table
    {
        return InvitationsTable::configureTable($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return InvitationInfolist::configureSchema($schema);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        /** @var User|null $user */
        $user = Auth::user();

        if ($user?->role === 'external|referente') {
            $schoolIds = $user->schools()->pluck('schools.id');
            $query->whereIn('school_id', $schoolIds);
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvitations::route('/'),
            'create' => CreateInvitation::route('/create'),
            'view' => ViewInvitation::route('/{record}'),
            'edit' => EditInvitation::route('/{record}/edit'),
        ];
    }
}
