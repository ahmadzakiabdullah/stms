<?php

namespace App\Http\Controllers;

use App\Actions\Sports\CreateSport;
use App\Actions\Sports\DeleteSport;
use App\Actions\Sports\UpdateSport;
use App\Http\Requests\Sport\StoreSportRequest;
use App\Http\Requests\Sport\UpdateSportRequest;
use App\Models\Sport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SportController extends Controller
{
    public function index(): Response
    {
        // Defensive query (prevent 500 on unmigrated prod DB)
        $sports = $this->safePaginatedQuery(function () {
            return Sport::with('organization', 'categories')
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString();
        });

        return Inertia::render('Sports/Index', [
            'sports' => $sports,
        ]);
    }

    public function store(StoreSportRequest $request, CreateSport $action): RedirectResponse
    {
        Gate::authorize('create', Sport::class);

        $action->handle($request->validated());

        return redirect()->route('sports.index')
            ->with('success', 'Sport created successfully.');
    }

    public function update(UpdateSportRequest $request, Sport $sport, UpdateSport $action): RedirectResponse
    {
        Gate::authorize('update', $sport);

        $action->handle($sport, $request->validated());

        return redirect()->route('sports.index')
            ->with('success', 'Sport updated successfully.');
    }

    public function destroy(Sport $sport, DeleteSport $action): RedirectResponse
    {
        Gate::authorize('delete', $sport);

        $action->handle($sport);

        return redirect()->route('sports.index')
            ->with('success', 'Sport deleted successfully.');
    }
}
