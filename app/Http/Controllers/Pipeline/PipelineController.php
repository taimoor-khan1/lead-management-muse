<?php

namespace App\Http\Controllers\Pipeline;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PipelineController extends Controller
{
    public const STAGES = [
        'new' => 'New',
        'contacted' => 'Contacted',
        'replied' => 'Replied',
        'proposal' => 'Proposal',
        'won' => 'Won',
        'lost' => 'Lost',
    ];

    public function index(): Response
    {
        $leads = Lead::latest()
            ->get(['id', 'business_name', 'industry', 'city', 'phone', 'suggested_service', 'stage']);

        $columns = [];
        foreach (self::STAGES as $key => $label) {
            $columns[] = [
                'key' => $key,
                'label' => $label,
                'leads' => $leads->where('stage', $key)->values()->all(),
            ];
        }

        return Inertia::render('Pipeline', [
            'columns' => $columns,
        ]);
    }

    public function move(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lead_id' => ['required', 'exists:leads,id'],
            'stage' => ['required', 'in:' . implode(',', array_keys(self::STAGES))],
        ]);

        $lead = Lead::findOrFail($data['lead_id']);

        $updates = ['stage' => $data['stage']];
        if ($data['stage'] === 'contacted' && is_null($lead->last_contacted_at)) {
            $updates['last_contacted_at'] = now();
        }
        $lead->update($updates);

        return response()->json(['ok' => true]);
    }
}
