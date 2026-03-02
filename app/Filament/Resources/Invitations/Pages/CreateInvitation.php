<?php

namespace App\Filament\Resources\Invitations\Pages;

use App\Filament\Resources\Invitations\InvitationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInvitation extends CreateRecord
{
    protected static string $resource = InvitationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $role = (string) ($data['role'] ?? '');

        if (! str_starts_with($role, 'external_')) {
            $data['school_id'] = null;
        }

        return $data;
    }
}
