<?php

namespace App\Mail;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvitationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public Invitation $invitation;

    public string $openUrl;

    public ?string $pixelUrl;

    public function __construct(Invitation $invitation)
    {
        // Voglio che questa mail finisca sempre nella coda "mail".
        $this->onQueue('mail');

        $this->invitation = $invitation;

        $this->openUrl = route('invitation.open', [
            'accessToken' => $invitation->access_token,
        ]);

        $this->pixelUrl = $invitation->open_token
            ? route('invitation.pixel', ['openToken' => $invitation->open_token])
            : null;
    }

    public function build(): static
    {
        $subject = $this->invitation->subject ?: (config('app.name') . ' - Invito');

        return $this->subject($subject)
            ->markdown('emails.invitation', [
                'invitation' => $this->invitation,
                'openUrl' => $this->openUrl,
                'pixelUrl' => $this->pixelUrl,
                'expiryDays' => (int) (config('cmsdiari.invitation_expiry_days') ?? 30),
            ]);
    }
}
