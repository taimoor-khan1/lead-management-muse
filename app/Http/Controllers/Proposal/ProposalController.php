<?php

namespace App\Http\Controllers\Proposal;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Proposal;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ProposalController extends Controller
{
    public function index(Request $request)
    {
        $query = Proposal::with('lead')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhereHas('lead', fn ($l) => $l->where('business_name', 'like', "%{$search}%"));
            });
        }

        $proposals = $query->paginate(15)->withQueryString();
        $proposals->getCollection()->transform(function ($proposal) {
            $proposal->public_url = url('/p/'.$proposal->token);

            return $proposal;
        });

        return Inertia::render('Proposals/Index', [
            'proposals' => $proposals,
            'filters' => $request->only(['status', 'search']),
            'statuses' => ['draft', 'sent', 'accepted', 'declined'],
        ]);
    }

    public function create()
    {
        return Inertia::render('Proposals/Create', [
            'leads' => Lead::orderBy('business_name')->get(['id', 'business_name', 'contact_person', 'city']),
            'services' => Service::where('active', true)->orderBy('name')->get(['id', 'name', 'description', 'price']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateProposal($request);

        $totals = $this->totals($data['items'], $data['discount'] ?? 0);

        $proposal = Proposal::create([
            'lead_id' => $data['lead_id'],
            'token' => $this->uniqueToken(),
            'title' => $data['title'],
            'status' => 'draft',
            'subtotal' => $totals['subtotal'],
            'discount' => $totals['discount'],
            'total' => $totals['total'],
            'valid_until' => $data['valid_until'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        foreach ($data['items'] as $item) {
            $proposal->items()->create([
                'service_id' => $item['service_id'] ?? null,
                'name' => $item['name'],
                'qty' => $item['qty'],
                'price' => $item['price'],
            ]);
        }

        return redirect()->route('proposals.show', $proposal);
    }

    public function show(Proposal $proposal)
    {
        $proposal->load(['lead', 'items']);
        $proposal->public_url = url('/p/'.$proposal->token);

        return Inertia::render('Proposals/Show', [
            'proposal' => $proposal,
        ]);
    }

    public function edit(Proposal $proposal)
    {
        $proposal->load('items');

        return Inertia::render('Proposals/Edit', [
            'proposal' => $proposal,
            'leads' => Lead::orderBy('business_name')->get(['id', 'business_name', 'contact_person', 'city']),
            'services' => Service::where('active', true)->orderBy('name')->get(['id', 'name', 'description', 'price']),
        ]);
    }

    public function update(Request $request, Proposal $proposal)
    {
        $data = $this->validateProposal($request);

        $totals = $this->totals($data['items'], $data['discount'] ?? 0);

        $proposal->update([
            'lead_id' => $data['lead_id'],
            'title' => $data['title'],
            'subtotal' => $totals['subtotal'],
            'discount' => $totals['discount'],
            'total' => $totals['total'],
            'valid_until' => $data['valid_until'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        $proposal->items()->delete();
        foreach ($data['items'] as $item) {
            $proposal->items()->create([
                'service_id' => $item['service_id'] ?? null,
                'name' => $item['name'],
                'qty' => $item['qty'],
                'price' => $item['price'],
            ]);
        }

        return redirect()->route('proposals.show', $proposal);
    }

    public function destroy(Proposal $proposal)
    {
        $proposal->delete();

        return redirect()->route('proposals.index');
    }

    public function send(Proposal $proposal)
    {
        $proposal->update(['status' => 'sent']);

        return redirect()->route('proposals.show', $proposal);
    }

    protected function validateProposal(Request $request): array
    {
        return $request->validate([
            'lead_id' => ['required', 'exists:leads,id'],
            'title' => ['required', 'string', 'max:255'],
            'valid_until' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.service_id' => ['nullable', 'exists:services,id'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
        ]);
    }

    protected function totals(array $items, $discount): array
    {
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += $item['qty'] * $item['price'];
        }
        $subtotal = round($subtotal, 2);
        $discount = round(min((float) $discount, $subtotal), 2);

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => round($subtotal - $discount, 2),
        ];
    }

    protected function uniqueToken(): string
    {
        do {
            $token = Str::random(32);
        } while (Proposal::where('token', $token)->exists());

        return $token;
    }
}
