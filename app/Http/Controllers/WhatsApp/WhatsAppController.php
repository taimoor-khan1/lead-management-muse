<?php

namespace App\Http\Controllers\WhatsApp;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\WhatsappMessage;
use App\Models\WhatsappTemplate;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WhatsAppController extends Controller
{
    public function index(WhatsAppService $service)
    {
        $threads = Lead::query()
            ->whereHas('whatsappMessages')
            ->with(['whatsappMessages' => fn ($q) => $q->latest()->take(1)])
            ->get()
            ->sortByDesc(fn ($lead) => optional($lead->whatsappMessages->first())->created_at)
            ->values()
            ->map(fn ($lead) => [
                'id' => $lead->id,
                'business_name' => $lead->business_name,
                'contact_person' => $lead->contact_person,
                'phone' => $lead->phone,
                'stage' => $lead->stage,
                'latest_message' => $lead->whatsappMessages->first() ? [
                    'body' => $lead->whatsappMessages->first()->body,
                    'direction' => $lead->whatsappMessages->first()->direction,
                    'created_at' => $lead->whatsappMessages->first()->created_at,
                ] : null,
            ]);

        return Inertia::render('WhatsApp/Index', [
            'threads' => $threads,
            'stub' => $service->isStub(),
        ]);
    }

    public function show(Lead $lead, WhatsAppService $service)
    {
        return Inertia::render('WhatsApp/Thread', [
            'lead' => $lead->only('id', 'business_name', 'contact_person', 'phone', 'stage', 'city'),
            'messages' => $lead->whatsappMessages()
                ->orderBy('created_at')
                ->get()
                ->map(fn ($m) => $m->only('id', 'direction', 'body', 'status', 'wa_message_id', 'created_at')),
            'templates' => WhatsappTemplate::orderBy('name')->get()->map(
                fn ($t) => $t->only('id', 'name', 'language', 'body')
            ),
            'stub' => $service->isStub(),
        ]);
    }

    public function send(Request $request, WhatsAppService $service)
    {
        $data = $request->validate([
            'lead_id' => ['required', 'exists:leads,id'],
            'template_name' => ['nullable', 'string', 'required_without:body'],
            'body' => ['nullable', 'string', 'required_without:template_name', 'max:4000'],
            'language' => ['nullable', 'string', 'max:10'],
        ]);

        $lead = Lead::findOrFail($data['lead_id']);

        if (empty($lead->phone)) {
            return back()->withErrors(['send' => 'This lead has no phone number.']);
        }

        try {
            if (! empty($data['template_name'])) {
                $result = $service->sendTemplateMessage(
                    $lead->phone,
                    $data['template_name'],
                    $data['language'] ?? 'en'
                );
                $template = WhatsappTemplate::where('name', $data['template_name'])->first();
                $body = $template?->body ?? '[Template: '.$data['template_name'].']';
            } else {
                $result = $service->sendTextMessage($lead->phone, $data['body']);
                $body = $data['body'];
            }
        } catch (\Throwable $e) {
            return back()->withErrors(['send' => $e->getMessage()]);
        }

        WhatsappMessage::create([
            'lead_id' => $lead->id,
            'phone' => $lead->phone,
            'direction' => 'out',
            'body' => $body,
            'wa_message_id' => $result['id'] ?? null,
            'status' => ($result['stub'] ?? false) ? 'stubbed' : 'sent',
        ]);

        $lead->update(['last_contacted_at' => now()]);

        return back()->with('status', 'Message sent.');
    }
}
