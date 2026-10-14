<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\EmailSend;
use App\Models\Lead;
use App\Models\Proposal;
use App\Models\Task;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(): Response
    {
        $stages = ['new', 'contacted', 'replied', 'proposal', 'won', 'lost'];
        $funnel = [];
        foreach ($stages as $stage) {
            $funnel[] = ['stage' => $stage, 'count' => Lead::where('stage', $stage)->count()];
        }

        $industryCounts = Lead::whereNotNull('industry')
            ->where('industry', '!=', '')
            ->select('industry', DB::raw('count(*) as count'))
            ->groupBy('industry')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $campaignStats = Campaign::orderByDesc('id')->get()->map(function (Campaign $campaign) {
            $leadIds = DB::table('campaign_leads')->where('campaign_id', $campaign->id)->pluck('lead_id');

            return [
                'name' => $campaign->name,
                'status' => $campaign->status,
                'sent' => DB::table('campaign_leads')
                    ->where('campaign_id', $campaign->id)
                    ->where('status', 'sent')->count(),
                'opens' => EmailSend::whereIn('lead_id', $leadIds)->sum('opens'),
                'clicks' => EmailSend::whereIn('lead_id', $leadIds)->sum('clicks'),
            ];
        });

        $proposalStats = Proposal::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        return Inertia::render('Report/Reports', [
            'funnel' => $funnel,
            'industryCounts' => $industryCounts,
            'campaignStats' => $campaignStats,
            'proposalStats' => $proposalStats,
            'wonRevenue' => (float) Proposal::where('status', 'accepted')->sum('total'),
            'tasksStats' => [
                'open' => Task::where('done', false)->count(),
                'overdue' => Task::where('done', false)
                    ->whereNotNull('due_date')
                    ->whereDate('due_date', '<', now()->toDateString())->count(),
                'done' => Task::where('done', true)->count(),
            ],
        ]);
    }
}
