<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TermsOfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TermsOfServiceController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', TermsOfService::class);

        $termsList = TermsOfService::with('creator')
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('admin.terms-of-service.index', compact('termsList'));
    }

    public function create(): View
    {
        $this->authorize('create', TermsOfService::class);

        return view('admin.terms-of-service.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', TermsOfService::class);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'version' => 'required|string|max:50',
            'is_active' => 'boolean',
            'effective_date' => 'nullable|date',
        ]);

        $validated['created_by'] = $request->user()->id;
        $validated['is_active'] = $request->boolean('is_active', false);

        $terms = TermsOfService::create($validated);

        session()->flash('success', 'Terms of Service created successfully.');

        return redirect()->route('admin.terms_of_service.show', $terms);
    }

    public function show(TermsOfService $termsOfService): View
    {
        $this->authorize('view', $termsOfService);

        $termsOfService->load(['creator', 'acceptances.user']);

        return view('admin.terms-of-service.show', compact('termsOfService'));
    }

    public function edit(TermsOfService $termsOfService): View
    {
        $this->authorize('update', $termsOfService);

        return view('admin.terms-of-service.edit', compact('termsOfService'));
    }

    public function update(Request $request, TermsOfService $termsOfService): RedirectResponse
    {
        $this->authorize('update', $termsOfService);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'version' => 'required|string|max:50',
            'is_active' => 'boolean',
            'effective_date' => 'nullable|date',
        ]);

        $validated['is_active'] = $request->boolean('is_active', $termsOfService->is_active);

        $termsOfService->update($validated);

        session()->flash('success', 'Terms of Service updated successfully.');

        return redirect()->route('admin.terms_of_service.show', $termsOfService);
    }

    public function destroy(TermsOfService $termsOfService): RedirectResponse
    {
        $this->authorize('delete', $termsOfService);

        try {
            $termsOfService->delete();
            session()->flash('success', 'Terms of Service deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.terms_of_service.index');
    }

    public function activate(TermsOfService $termsOfService): RedirectResponse
    {
        $this->authorize('update', $termsOfService);

        // Deactivate all other active terms
        TermsOfService::where('is_active', true)
            ->where('id', '!=', $termsOfService->id)
            ->update(['is_active' => false]);

        $termsOfService->update([
            'is_active' => true,
            'effective_date' => $termsOfService->effective_date ?? now(),
        ]);

        session()->flash('success', 'Terms of Service activated successfully.');

        return back();
    }

    public function deactivate(TermsOfService $termsOfService): RedirectResponse
    {
        $this->authorize('update', $termsOfService);

        $termsOfService->update(['is_active' => false]);

        session()->flash('success', 'Terms of Service deactivated successfully.');

        return back();
    }
}

