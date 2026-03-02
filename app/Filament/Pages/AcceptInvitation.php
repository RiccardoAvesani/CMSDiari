<?php

namespace App\Filament\Pages;

use App\Models\Invitation;
use App\Models\User;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Pages\SimplePage;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Throwable;

class AcceptInvitation extends SimplePage implements HasForms
{
    use InteractsWithForms;

    public string $accessToken;

    public ?Invitation $invitation = null;

    public array $data = [];

    public bool $hasProblem = false;

    public string $problemTitle = 'Invito non valido';

    public string $problemBody = 'Questo Invito non è più utilizzabile. Puoi chiudere la pagina o richiedere un nuovo Invito.';

    public function getView(): string
    {
        return 'filament.pages.accept-invitation';
    }

    public function getTitle(): string
    {
        return 'Registrazione';
    }

    public function getHeading(): string
    {
        return 'Registrazione';
    }

    public function getSubheading(): ?string
    {
        return 'Inserisci i tuoi dati per completare la registrazione. Dopo la verifica dell’e-mail potrai accedere all’Applicazione.';
    }

    public function mount(string $accessToken): void
    {
        $this->accessToken = $accessToken;

        $this->invitation = Invitation::query()
            ->where('access_token', $accessToken)
            ->first();

        if (! $this->invitation || $this->invitation->status !== Invitation::STATUS_READY) {
            $this->problem('Invito non trovato', 'Il link che hai aperto non è valido. Puoi chiudere la pagina o richiedere un nuovo Invito.');
            return;
        }

        $this->form->fill([
            'first_name' => null,
            'last_name' => null,
            'email' => $this->invitation->email,
            'password' => null,
        ]);
    }

    protected function problem(string $title, string $body): void
    {
        $this->hasProblem = true;
        $this->problemTitle = $title;
        $this->problemBody = $body;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components($this->getFormComponents());
    }

    protected function getFormComponents(): array
    {
        return [
            TextInput::make('first_name')
                ->label('Nome')
                ->required()
                ->maxLength(255),

            TextInput::make('last_name')
                ->label('Cognome')
                ->required()
                ->maxLength(255),

            TextInput::make('email')
                ->label('E-mail / Username')
                ->email()
                ->required()
                ->maxLength(255)
                ->unique(table: User::class, column: 'email'),

            TextInput::make('password')
                ->label('Password')
                ->password()
                ->required()
                ->rule(PasswordRule::defaults()),
        ];
    }

    public function submit()
    {
        if ($this->hasProblem || ! $this->invitation) {
            return null;
        }

        $data = $this->form->getState();

        try {
            DB::transaction(function () use ($data): void {
                $invitation = Invitation::query()->whereKey($this->invitation->id)->first();

                if (! $invitation || $invitation->status !== Invitation::STATUS_READY) {
                    $this->problem('Invito non trovato', 'Il link che hai aperto non è valido. Puoi chiudere la pagina o richiedere un nuovo Invito.');
                    return;
                }

                $user = new User();
                $user->first_name = $data['first_name'];
                $user->last_name = $data['last_name'];
                $user->email = $data['email'];
                $user->password = Hash::make($data['password']);
                $user->role = $invitation->role;
                $user->status = User::STATUS_BLOCKED ?? 'blocked';

                User::withoutEvents(function () use ($user): void {
                    $user->save();
                });

                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'created_by' => $user->id,
                        'updated_by' => $user->id,
                    ]);

                if ($invitation->school_id) {
                    $user->schools()->attach($invitation->school_id);
                }

                $invitation->forceFill([
                    'user_id' => $user->id,
                    'status' => Invitation::STATUS_REGISTERED,
                    'registered_at' => now(),
                ])->save();

                $user->sendEmailVerificationNotification();
            });

            return redirect()
                ->route('verification.sent')
                ->with('status', 'Registrazione completata. Controlla la tua casella e-mail per confermare l’indirizzo.');
        } catch (Throwable $e) {
            Notification::make()
                ->title('Errore')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return null;
        }
    }
}
