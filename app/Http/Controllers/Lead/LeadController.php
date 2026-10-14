<?php

namespace App\Http\Controllers\Lead;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lead\StoreLeadRequest;
use App\Http\Requests\Lead\UpdateLeadRequest;
use App\Models\Lead;
use App\Models\LeadNote;
use App\Models\User;
use App\Services\GoogleSheetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeadController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Lead::query()->with('owner');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('business_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        foreach (['industry', 'city', 'website_status', 'stage'] as $filter) {
            if ($value = $request->input($filter)) {
                $query->where($filter, $value);
            }
        }

        return Inertia::render('Leads/Index', [
            'leads' => $query->latest()->paginate(20)->withQueryString(),
            'filters' => $request->only(['search', 'industry', 'city', 'website_status', 'stage']),
            'stats' => [
                'total' => Lead::count(),
                'new' => Lead::where('stage', 'new')->count(),
                'contacted' => Lead::where('stage', 'contacted')->count(),
                'won' => Lead::where('stage', 'won')->count(),
            ],
            'industries' => Lead::whereNotNull('industry')->where('industry', '!=', '')->distinct()->orderBy('industry')->pluck('industry'),
            'cities' => Lead::whereNotNull('city')->where('city', '!=', '')->distinct()->orderBy('city')->pluck('city'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Leads/Create', [
            'users' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreLeadRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['owner_id'] = $data['owner_id'] ?? $request->user()->id;
        $data['source'] = $data['source'] ?? 'manual';

        $lead = Lead::create($data);

        return redirect()->route('leads.show', $lead)->with('message', 'Lead created.');
    }

    public function show(Lead $lead): Response
    {
        // NOTE: leads has both a `notes` text column and a notes() relation,
        // so the relation is passed separately to avoid the attribute/relation collision.
        $lead->load(['owner', 'tasks', 'emailSends.template', 'whatsappMessages', 'proposals']);

        return Inertia::render('Leads/Show', [
            'lead' => $lead,
            'leadNotes' => LeadNote::where('lead_id', $lead->id)->with('user')->latest()->get(),
        ]);
    }

    public function edit(Lead $lead): Response
    {
        return Inertia::render('Leads/Edit', [
            'lead' => $lead,
            'users' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateLeadRequest $request, Lead $lead): RedirectResponse
    {
        $lead->update($request->validated());

        return redirect()->route('leads.show', $lead)->with('message', 'Lead updated.');
    }

    public function destroy(Lead $lead): RedirectResponse
    {
        $lead->delete();

        return redirect()->route('leads.index')->with('message', 'Lead deleted.');
    }

    public function import(): Response
    {
        return Inertia::render('Leads/Import');
    }

    public function importStore(Request $request, GoogleSheetService $service): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        try {
            $rows = $service->parseCsv($request->file('file')->getRealPath());
            $result = $service->importRows($rows, 'csv');
        } catch (\Throwable $e) {
            return back()->with('error', 'Could not parse CSV: '.$e->getMessage());
        }

        return redirect()->route('leads.index')
            ->with('message', "Import complete: {$result['imported']} imported, {$result['skipped']} skipped.");
    }

    public function syncSheet(GoogleSheetService $service): RedirectResponse
    {
        $result = $service->syncLeads();

        return back()->with('message', $result['message']);
    }
}
