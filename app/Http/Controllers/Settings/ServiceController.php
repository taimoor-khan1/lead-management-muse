<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index()
    {
        return redirect()->route('settings.index');
    }

    public function store(Request $request)
    {
        $data = $this->validateService($request);

        Service::create($data + ['currency' => 'PKR']);

        return redirect()->route('settings.index');
    }

    public function update(Request $request, Service $service)
    {
        $service->update($this->validateService($request));

        return redirect()->route('settings.index');
    }

    public function destroy(Service $service)
    {
        $service->delete();

        return redirect()->route('settings.index');
    }

    protected function validateService(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
        ]);
        $data['active'] = $request->boolean('active', true);

        return $data;
    }
}
