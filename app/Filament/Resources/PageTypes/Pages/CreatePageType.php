<?php

namespace App\Filament\Resources\PageTypes\Pages;

use App\Filament\Resources\PageTypes\PageTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePageType extends CreateRecord
{
    protected static string $resource = PageTypeResource::class;
}
