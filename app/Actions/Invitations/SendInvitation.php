<?php

namespace App\Actions\Invitations;

use App\Mail\InvitationMail;
use App\Models\Invitation;
use Illuminate\Contracts\Mail\Mailable as MailableContract;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use RuntimeException;

class SendInvitation
{
    public function handle(Invitation $invitation): Invitation
    {
        $invitation->refresh();

        // 1) Inviabile SOLO in "ready" (expired resta come storico e non si reinvia)
        if ($invitation->status !== Invitation::STATUS_READY) {
            throw new RuntimeException('Invito non inviabile: consentito solo nello stato "ready".');
        }

        // 2) Non inviabile dopo che l'Utente è stato registrato/attivato (o link disabilitato)
        if (! $invitation->canBeOpened()) {
            throw new RuntimeException('Invito non inviabile (Utente già attivo/eliminato o Invito non apribile).');
        }

        $now = now();

        $expiryDays = $this->getInvitationExpiryDays(); // default: 30 giorni
        $expiresAt = $now->copy()->addDays($expiryDays);

        // 3) Access Token
        if (empty($invitation->access_token)) {
            $invitation->access_token = $this->uniqueToken('access_token');
        }

        // 4) Pixel Token
        if (empty($invitation->open_token)) {
            $invitation->open_token = $this->uniqueToken('open_token');
        }

        if (blank($invitation->subject)) {
            $invitation->subject = config('app.name') . ' - Invito';
        }

        if (blank($invitation->message)) {
            $invitation->message = 'Sei stato invitato ad accedere alla piattaforma. Usa il link qui sotto per completare la registrazione.';
        }

        $invitation->forceFill([
            'status' => Invitation::STATUS_INVITED,
            'sent_at' => $now,
            'expires_at' => $expiresAt,
        ])->save();

        /** @var MailableContract $mailable */
        $mailable = (new InvitationMail($invitation))
            ->onQueue('mail');

        Mail::to($invitation->email)->queue($mailable);

        return $invitation->refresh();
    }

    private function getInvitationExpiryDays(): int
    {
        return (int) (config('cmsdiari.invitation_expiry_days') ?? 30);
    }

    private function uniqueToken(string $column): string
    {
        do {
            $token = Str::random(64);
        } while (Invitation::query()->where($column, $token)->exists());

        return $token;
    }
}
