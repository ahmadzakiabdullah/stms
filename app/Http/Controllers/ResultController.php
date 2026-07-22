<?php

namespace App\Http\Controllers;

use App\Actions\Results\DeleteResult;
use App\Actions\Results\StoreResult;
use App\Actions\Results\UpdateResult;
use App\Http\Requests\Result\StoreResultRequest;
use App\Http\Requests\Result\UpdateResultRequest;
use App\Models\Event;
use App\Models\Fixture;
use App\Models\Participant;
use App\Models\Result;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ResultController extends Controller
{
    public function index(): Response
    {
        $dataLoadFailed = false;

        $results = $this->safePaginatedQuery(function () {
            return Result::with(['match.event', 'winner'])
                ->orderByDesc('created_at')
                ->paginate(15)
                ->withQueryString();
        }, function () use (&$dataLoadFailed) {
            $dataLoadFailed = true;
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15, 1, [
                'path' => request()->url(),
            ]);
        });

        $response = Inertia::render('Results/Index', [
            'results' => $results,
            'matches' => Fixture::query()->orderByDesc('match_number')->get(['id', 'match_number', 'status']),
            'participants' => Participant::query()->active()->orderBy('name')->get(['id', 'name', 'slug']),
            'events' => Event::query()->orderBy('name')->get(['id', 'name', 'slug']),
        ]);

        if ($dataLoadFailed) {
            $response->with('error', 'Failed to load some data. Please run "php artisan migrate" on the server (database may be out of date).');
        }

        return $response;
    }

    public function store(StoreResultRequest $request, StoreResult $action): RedirectResponse
    {
        Gate::authorize('create', Result::class);

        $action->handle(
            auth()->user()->organization,
            $request->validated()
        );

        return redirect()->route('results.index')
            ->with('success', 'Result recorded successfully.');
    }

    public function update(UpdateResultRequest $request, Result $result, UpdateResult $action): RedirectResponse
    {
        Gate::authorize('update', $result);

        $action->handle(
            auth()->user()->organization,
            $result->id,
            $request->validated()
        );

        return redirect()->route('results.index')
            ->with('success', 'Result updated successfully.');
    }

    public function destroy(Result $result, DeleteResult $action): RedirectResponse
    {
        Gate::authorize('delete', $result);

        $action->handle(
            auth()->user()->organization,
            $result->id
        );

        return redirect()->route('results.index')
            ->with('success', 'Result deleted successfully.');
    }
}
