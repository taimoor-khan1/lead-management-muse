<?php

namespace App\Http\Controllers\Campaign;

use App\Http\Controllers\Controller;
use App\Jobs\SendEmailJob;
use App\Jobs\WhatsappSendJob;
use App\Models\Campaign;
use App\Models\EmailSend;
use App\Models\EmailTemplate;
use App\Models\Lead;
use App\Support\EmailVariables;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CampaignController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Campaigns/Index', [
            'campaigns' => Campaign::with('template:id,name')
                ->withCount('leads')
                ->withCount(['leads as sent_leads_count' => function ($q) {
                    $q->where('campaign_leads.status', 'sent');
                }])
                ->latest()
                ->paginate(15),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Campaigns/Create', [
            'leads' => Lead::orderBy('business_name')
                ->get(['id', 'business_name', 'city', 'industry', 'contact_person', 'phone', 'email']),
            'templates' => EmailTemplate::orderBy('name')->get(['id', 'name', 'subject']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'channel' => 'required|in:email,whatsapp',
            'email_template_id' => 'required_if:channel,email|nullable|exists:email_templates,id',
            'whatsapp_template' => 'required_if:channel,whatsapp|nullable|string',
            'lead_ids' => 'required|array|min:1',
            'lead_ids.*' => 'exists:leads,id',
        ]);

        $campaign = Campaign::create([
            'name' => $data['name'],
            'channel' => $data['channel'],
            'email_template_id' => $data['email_template_id'] ?? null,
            'whatsapp_template' => $data['whatsapp_template'] ?? null,
            'status' => 'draft',
        ]);

        $campaign->leads()->syncWithoutDetaching(
            array_fill_keys(array_unique($data['lead_ids']), ['status' => 'pending'])
        );

        return redirect()->route('campaigns.show', $campaign);
    }

    public function show(Campaign $campaign): Response
    {
        $campaign->load('template:id,name,subject');

        $rows = $campaign->leads()
            ->orderBy('business_name')
            ->get()
            ->map(fn (Lead $lead) => [
                'id' => $lead->id,
                'business_name' => $lead->business_name,
                'city' => $lead->city,
                'contact_person' => $lead->contact_person,
                'email' => $lead->email,
                'status' => $lead->pivot->status,
                'sent_at' => $lead->pivot->sent_at,
                'error' => $lead->pivot->error,
            ]);

        $opens = 0;
        $clicks = 0;

        if ($campaign->channel === 'email') {
            $agg = EmailSend::whereIn('lead_id', $rows->pluck('id'))
                ->where('email_template_id', $campaign->email_template_id)
                ->selectRaw('COALESCE(SUM(opens), 0) as opens, COALESCE(SUM(clicks), 0) as clicks')
                ->first();

            $opens = (int) $agg->opens;
            $clicks = (int) $agg->clicks;
        }

        return Inertia::render('Campaigns/Show', [
            'campaign' => $campaign,
            'rows' => $rows,
            'stats' => [
                'total' => $rows->count(),
                'sent' => $rows->where('status', 'sent')->count(),
                'failed' => $rows->where('status', 'failed')->count(),
                'opens' => $opens,
                'clicks' => $clicks,
            ],
        ]);
    }

    public function destroy(Campaign $campaign): RedirectResponse
    {
        $campaign->delete();

        return redirect()->route('campaigns.index');
    }

    public function launch(Request $request, Campaign $campaign): RedirectResponse
    {
        if (! in_array($campaign->status, ['draft', 'scheduled'], true)) {
            return redirect()->back()->withErrors([
                'campaign' => 'Only draft campaigns can be launched.',
            ]);
        }

        $data = $request->validate([
            'lead_ids' => 'nullable|array',
            'lead_ids.*' => 'exists:leads,id',
        ]);

        // Allow attaching extra leads at launch time (idempotent).
        if (! empty($data['lead_ids'])) {
            $campaign->leads()->syncWithoutDetaching(
                array_fill_keys(array_unique($data['lead_ids']), ['status' => 'pending'])
            );
        }

        $campaign->status = 'sending';
        $campaign->save();

        $template = $campaign->channel === 'email'
            ? EmailTemplate::find($campaign->email_template_id)
            : null;

        $pending = $campaign->leads()
            ->wherePivot('status', 'pending')
            ->get();

        foreach ($pending as $lead) {
            $campaignLeadId = (int) DB::table('campaign_leads')
                ->where('campaign_id', $campaign->id)
                ->where('lead_id', $lead->id)
                ->value('id');

            if ($campaign->channel === 'whatsapp') {
                WhatsappSendJob::dispatch($campaignLeadId);
                continue;
            }

            if (! $template || ! $lead->email) {
                DB::table('campaign_leads')->where('id', $campaignLeadId)->update([
                    'status' => 'failed',
                    'error' => ! $template ? 'No email template on campaign.' : 'Lead has no email address.',
                    'updated_at' => now(),
                ]);
                continue;
            }

            $send = EmailSend::create([
                'lead_id' => $lead->id,
                'email_template_id' => $template->id,
                'subject' => EmailVariables::replace($template->subject, $lead),
                'body' => EmailVariables::replace($template->body, $lead),
                'status' => 'queued',
                'tracking_token' => Str::random(40),
            ]);

            SendEmailJob::dispatch($send->id, $campaignLeadId);
        }

        $stillPending = $campaign->leads()->wherePivot('status', 'pending')->count();

        if ($stillPending === 0) {
            $campaign->status = 'completed';
            $campaign->save();
        }

        return redirect()->route('campaigns.show', $campaign);
    }
}
