<?php

namespace App\Http\Controllers;

use App\Models\EmailSend;
use App\Models\Lead;
use App\Models\Proposal;
use App\Models\Task;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $stages = ['new', 'contacted', 'replied', 'proposal', 'won', 'lost'];

        $stageCounts = Lead::selectRaw('stage, COUNT(*) as count')
            ->groupBy('stage')
            ->pluck('count', 'stage');

        $pipelineSummary = [];
        foreach ($stages as $stage) {
            $pipelineSummary[$stage] = (int) ($stageCounts[$stage] ?? 0);
        }

        $wonProposalsRevenue = (float) Proposal::where('status', 'accepted')->sum('total');

        return Inertia::render('Dashboard', [
            'kpis' => [
                'total_leads' => Lead::count(),
                'contacted' => Lead::whereIn('stage', ['contacted', 'replied', 'proposal'])->count(),
                'replied' => Lead::whereIn('stage', ['replied', 'proposal', 'won'])->count(),
                'won' => Lead::where('stage', 'won')->count(),
                'revenue_pkr' => $wonProposalsRevenue,
            ],
            'pipeline_summary' => $pipelineSummary,
            'recent_leads' => Lead::latest()
                ->limit(8)
                ->get(['id', 'business_name', 'industry', 'city', 'stage', 'created_at']),
            'upcoming_tasks' => Task::with(['lead:id,business_name'])
                ->where('done', false)
                ->orderBy('due_date')
                ->limit(8)
                ->get(['id', 'title', 'due_date', 'lead_id', 'done']),
            'recent_sends' => EmailSend::with(['lead:id,business_name'])
                ->latest()
                ->limit(8)
                ->get(['id', 'lead_id', 'subject', 'status', 'created_at', 'sent_at']),
        ]);
    }
}
