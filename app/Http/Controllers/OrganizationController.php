<?php

namespace App\Http\Controllers;

use App\Actions\Organizations\CreateOrganization;
use App\Actions\Organizations\DeleteOrganization;
use App\Actions\Organizations\UpdateOrganization;
use App\Http\Requests\Organization\StoreOrganizationRequest;
use App\Http\Requests\Organization\UpdateOrganizationRequest;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    public function index(): Response
    {
        // Defensive query
        $organizations = $this->safePaginatedQuery(function () {
            return Organization::with('parent')
                ->withCount('sessions')
                ->with('latestSession')
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString();
        });

        return Inertia::render('Organizations/Index', [
            'organizations' => $organizations,
        ]);
    }

    public function store(StoreOrganizationRequest $request, CreateOrganization $action): RedirectResponse
    {
        Gate::authorize('create', Organization::class);

        $action->handle($request->validated());

        return redirect()->route('organizations.index')
            ->with('success', 'Organization created successfully.');
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization, UpdateOrganization $action): RedirectResponse
    {
        Gate::authorize('update', $organization);

        $action->handle($organization, $request->validated());

        return redirect()->route('organizations.index')
            ->with('success', 'Organization updated successfully.');
    }

    public function destroy(Organization $organization, DeleteOrganization $action): RedirectResponse
    {
        Gate::authorize('delete', $organization);

        $action->handle($organization);

        return redirect()->route('organizations.index')
            ->with('success', 'Organization deleted successfully.');
    }
}
