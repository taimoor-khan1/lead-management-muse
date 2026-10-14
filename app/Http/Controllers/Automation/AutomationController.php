<?php

namespace App\Http\Controllers\Automation;

use App\Http\Controllers\Controller;
use App\Models\Automation;
use App\Models\EmailTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Inertia\Inertia;
use Inertia\Response;

class AutomationController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Automation/Automations', [
            'automations' => Automation::latest()->get(),
            'emailTemplates' => EmailTemplate::orderBy('name')->get(['id', 'name']),
            'flash' => fn () => session('status'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Automation::create($this->validated($request));

        return redirect()->route('automations.index');
    }

    public function update(Request $request, Automation $automation): RedirectResponse
    {
        $automation->update($this->validated($request));

        return redirect()->route('automations.index');
    }

    public function destroy(Automation $automation): RedirectResponse
    {
        $automation->delete();

        return back();
    }

    public function run(): RedirectResponse
    {
        Artisan::call('app:evaluate-automations');

        return back()->with('status', trim(Artisan::output()));
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'trigger' => 'required|in:no_reply_days,lead_created,stage_changed',
            'action' => 'required|in:create_task,send_email,change_stage',
            'config' => 'nullable|array',
            'active' => 'boolean',
        ]);
    }
}
