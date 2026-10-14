<?php

namespace App\Http\Controllers\Email;

use App\Http\Controllers\Controller;
use App\Jobs\SendEmailJob;
use App\Models\EmailSend;
use App\Models\EmailTemplate;
use App\Models\Lead;
use App\Support\EmailVariables;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class EmailSendController extends Controller
{
    public function index(Request $request): Response
    {
        $query = EmailSend::with('lead:id,business_name,email,contact_person')->latest();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhereHas('lead', function ($l) use ($search) {
                        $l->where('business_name', 'like', "%{$search}%")
                            ->orWhere('contact_person', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        return Inertia::render('Emails/Index', [
            'sends' => $query->paginate(15)->withQueryString(),
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    public function compose(Request $request): Response
    {
        return Inertia::render('Emails/Compose', [
            'leads' => Lead::query()
                ->whereNotNull('email')
                ->select('id', 'business_name', 'contact_person', 'email', 'city', 'industry', 'suggested_service')
                ->orderBy('business_name')
                ->get(),
            'templates' => EmailTemplate::orderBy('name')->get(['id', 'name', 'subject', 'body']),
            'selectedLeadId' => (int) $request->query('lead_id'),
            'selectedTemplateId' => (int) $request->query('template_id'),
        ]);
    }

    public function send(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'lead_id' => 'required|exists:leads,id',
            'email_template_id' => 'nullable|exists:email_templates,id',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        $lead = Lead::findOrFail($data['lead_id']);

        if (! $lead->email) {
            return redirect()->back()->withErrors([
                'lead_id' => 'Selected lead has no email address.',
            ]);
        }

        $send = EmailSend::create([
            'lead_id' => $lead->id,
            'email_template_id' => $data['email_template_id'] ?? null,
            'subject' => EmailVariables::replace($data['subject'], $lead),
            'body' => EmailVariables::replace($data['body'], $lead),
            'status' => 'queued',
            'tracking_token' => Str::random(40),
            'scheduled_at' => $data['scheduled_at'] ?? null,
        ]);

        if (! empty($data['scheduled_at'])) {
            $send->status = 'scheduled';
            $send->save();
            SendEmailJob::dispatch($send->id)->delay($send->scheduled_at);
        } else {
            SendEmailJob::dispatch($send->id);
        }

        return redirect()->route('emails.index');
    }
}
