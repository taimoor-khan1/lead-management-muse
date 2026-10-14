<?php

namespace App\Http\Controllers\Proposal;

use App\Http\Controllers\Controller;
use App\Models\Proposal;
use Illuminate\Http\Request;

class PublicProposalController extends Controller
{
    public function show(string $token)
    {
        $proposal = Proposal::with(['lead', 'items'])->where('token', $token)->firstOrFail();

        return view('proposals.public', ['proposal' => $proposal]);
    }

    public function accept(string $token)
    {
        $proposal = Proposal::with('lead')->where('token', $token)->firstOrFail();

        if (! in_array($proposal->status, ['accepted', 'declined'], true)) {
            $proposal->update(['status' => 'accepted']);
            $proposal->lead?->update(['stage' => 'won']);
        }

        return redirect()->route('proposals.public', $token);
    }

    public function decline(Request $request, string $token)
    {
        $proposal = Proposal::with('lead')->where('token', $token)->firstOrFail();

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! in_array($proposal->status, ['accepted', 'declined'], true)) {
            $notes = $proposal->notes;
            if (! empty($data['reason'])) {
                $notes = trim(($notes ? $notes."\n\n" : '').'Decline reason: '.$data['reason']);
            }
            $proposal->update(['status' => 'declined', 'notes' => $notes]);
        }

        return redirect()->route('proposals.public', $token);
    }
}
