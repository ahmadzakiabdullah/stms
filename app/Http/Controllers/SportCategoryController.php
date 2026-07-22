<?php

namespace App\Http\Controllers;

use App\Actions\SportCategories\CreateSportCategory;
use App\Actions\SportCategories\DeleteSportCategory;
use App\Actions\SportCategories\UpdateSportCategory;
use App\Http\Requests\SportCategory\StoreSportCategoryRequest;
use App\Http\Requests\SportCategory\UpdateSportCategoryRequest;
use App\Models\Sport;
use App\Models\SportCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SportCategoryController extends Controller
{
    public function index(): Response
    {
        $user = Auth::user();

        // Defensive queries
        $categories = $this->safePaginatedQuery(function () {
            return SportCategory::with('sport', 'organization')
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString();
        });

        $sports = $this->safeCollectionQuery(function () {
            return Sport::query()
                ->orderBy('name')
                ->get(['id', 'name', 'slug']);
        });

        return Inertia::render('SportCategories/Index', [
            'categories' => $categories,
            'sports' => $sports,
        ]);
    }

    public function store(StoreSportCategoryRequest $request, CreateSportCategory $action): RedirectResponse
    {
        Gate::authorize('create', SportCategory::class);

        $action->handle($request->validated());

        return redirect()->route('sport-categories.index')
            ->with('success', 'Sport category created successfully.');
    }

    public function update(UpdateSportCategoryRequest $request, SportCategory $sportCategory, UpdateSportCategory $action): RedirectResponse
    {
        Gate::authorize('update', $sportCategory);

        $action->handle($sportCategory, $request->validated());

        return redirect()->route('sport-categories.index')
            ->with('success', 'Sport category updated successfully.');
    }

    public function destroy(SportCategory $sportCategory, DeleteSportCategory $action): RedirectResponse
    {
        Gate::authorize('delete', $sportCategory);

        $action->handle($sportCategory);

        return redirect()->route('sport-categories.index')
            ->with('success', 'Sport category deleted successfully.');
    }
}
