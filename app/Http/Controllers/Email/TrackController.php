<?php

namespace App\Http\Controllers\Email;

use App\Http\Controllers\Controller;
use App\Models\EmailSend;
use Illuminate\Http\Request;

/**
 * Public (no auth) open/click tracking endpoints used by the
 * tracking pixel and rewritten links injected into outgoing emails.
 */
class TrackController extends Controller
{
    public function open(string $token)
    {
        $send = EmailSend::where('tracking_token', $token)->firstOrFail();

        $send->increment('opens');

        if (! $send->opened_at) {
            $send->opened_at = now();

            if (in_array($send->status, ['queued', 'sending', 'sent'], true)) {
                $send->status = 'opened';
            }

            $send->save();
        }

        return response(
            base64_decode('R0lGODlhAQABAIAAAP///////yH5BAEKAAEALAAAAAABAAEAAAICTAEAOw=='),
            200,
            ['Content-Type' => 'image/gif', 'Cache-Control' => 'no-store']
        );
    }

    public function click(string $token, Request $request)
    {
        $send = EmailSend::where('tracking_token', $token)->firstOrFail();

        $send->increment('clicks');

        if (! $send->clicked_at) {
            $send->clicked_at = now();
        }

        $send->status = 'clicked';
        $send->save();

        $url = (string) $request->query('url', '');

        if (! preg_match('#^https?://#i', $url)) {
            return redirect('/');
        }

        return redirect()->away($url);
    }
}
