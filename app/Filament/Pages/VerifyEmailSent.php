<?php

namespace App\Filament\Pages;

use Filament\Pages\SimplePage;

class VerifyEmailSent extends SimplePage
{
    public function getView(): string
    {
        return 'filament.pages.verify-email-sent';
    }

    public function getTitle(): string
    {
        return 'Email di verifica inviata';
    }

    public function getHeading(): string
    {
        return 'Email di verifica inviata';
    }
}
