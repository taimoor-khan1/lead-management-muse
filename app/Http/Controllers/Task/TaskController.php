<?php

namespace App\Http\Controllers\Task;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function index(Request $request): Response
    {
        $filter = $request->query('filter', 'all');
        if (! in_array($filter, ['all', 'open', 'overdue', 'done'], true)) {
            $filter = 'all';
        }

        $query = $request->user()->tasks()
            ->with('lead:id,business_name')
            ->orderByRaw('due_date IS NULL')
            ->orderBy('due_date')
            ->latest('id');

        match ($filter) {
            'open' => $query->where('done', false),
            'overdue' => $query->where('done', false)
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<', now()->toDateString()),
            'done' => $query->where('done', true),
            default => null,
        };

        return Inertia::render('Task/Tasks', [
            'filter' => $filter,
            'tasks' => $query->get(),
            'leadOptions' => Lead::orderBy('business_name')->get(['id', 'business_name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'lead_id' => 'nullable|exists:leads,id',
            'due_date' => 'nullable|date',
        ]);

        $request->user()->tasks()->create($data);

        return redirect()->route('tasks.index');
    }

    public function toggle(Request $request, Task $task): RedirectResponse
    {
        abort_unless($task->user_id === $request->user()->id, 403);

        $task->update(['done' => ! $task->done]);

        return back();
    }

    public function destroy(Request $request, Task $task): RedirectResponse
    {
        abort_unless($task->user_id === $request->user()->id, 403);

        $task->delete();

        return back();
    }
}
