<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class InvitationPublicController extends Controller
{
    public function open(string $accessToken)
    {
        /** @var Invitation $invitation */
        $invitation = Invitation::query()
            ->where('access_token', $accessToken)
            ->firstOrFail();

        if (! $invitation->canBeOpened()) {
            abort(SymfonyResponse::HTTP_GONE, 'Invito non più valido.');
        }

        $invitation->markExpiredIfNeeded();
        if ($invitation->status === Invitation::STATUS_EXPIRED) {
            abort(SymfonyResponse::HTTP_GONE, 'Invito scaduto.');
        }

        if ($invitation->status === Invitation::STATUS_READY) {
            abort(SymfonyResponse::HTTP_NOT_FOUND);
        }

        $invitation->markReceived('link');

        return view('invitations.accept', [
            'accessToken' => $accessToken,
        ]);
    }

    public function pixel(string $openToken)
    {
        $invitation = Invitation::query()
            ->where('open_token', $openToken)
            ->first();

        if ($invitation instanceof Invitation) {
            if ($invitation->canBeOpened()) {
                $invitation->markExpiredIfNeeded();

                if ($invitation->status !== Invitation::STATUS_EXPIRED) {
                    $invitation->markReceived('pixel');
                }
            }
        }

        $gif = base64_decode('R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==');

        return response($gif, 200)->withHeaders([
            'Content-Type' => 'image/gif',
            'Content-Length' => (string) strlen($gif),
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
