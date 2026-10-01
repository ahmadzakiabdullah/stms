<?php

namespace App\Http\Controllers;

use App\Models\EventParticipant;
use App\Models\Organization;
use App\Models\Result;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $isSuperAdmin = $user->hasRole('super-admin');
        $defaultTab = $isSuperAdmin ? 'action' : 'inbox';
        $filters = $request->validate([
            'tab' => 'nullable|string|in:action,inbox',
            'status' => 'nullable|string|in:all,unread,read',
            'type' => 'nullable|string|max:100',
            'organization_id' => 'nullable|uuid|exists:organizations,id',
        ]);

        $tab = $filters['tab'] ?? $defaultTab;
        if (! $isSuperAdmin) {
            $tab = 'inbox';
        }

        $status = $filters['status'] ?? ($tab === 'action' ? 'unread' : 'all');
        $query = $user->notifications()->latest();

        if ($tab === 'action') {
            $query->where('data->type', 'new_registration');
        }

        if ($status === 'unread') {
            $query->whereNull('read_at');
        } elseif ($status === 'read') {
            $query->whereNotNull('read_at');
        }

        if (! empty($filters['type'])) {
            $query->where('data->type', $filters['type']);
        }

        if ($isSuperAdmin && ! empty($filters['organization_id'])) {
            $query->where('data->organization_id', $filters['organization_id']);
        }

        $notifications = $query->paginate(20)->withQueryString();

        // Older database notifications contain only the English participant
        // name. Enrich the current page from live records so the locale
        // switch also works for notifications created before name_ms existed.
        $notificationItems = collect($notifications->items());
        $eventParticipants = EventParticipant::query()
            ->whereIn('id', $notificationItems->pluck('data.event_participant_id')->filter()->values())
            ->with('participant:id,name,name_ms')
            ->get()
            ->keyBy('id');
        $results = Result::query()
            ->whereIn('id', $notificationItems->pluck('data.result_id')->filter()->values())
            ->with([
                'match.homeParticipant:id,name,name_ms',
                'match.awayParticipant:id,name,name_ms',
                'winner:id,name,name_ms',
            ])
            ->get()
            ->keyBy('id');

        $notifications->setCollection($notificationItems->map(function ($notification) use ($eventParticipants, $results) {
            $data = $notification->data ?? [];
            $eventParticipant = $eventParticipants->get($data['event_participant_id'] ?? null);
            $result = $results->get($data['result_id'] ?? null);

            if ($eventParticipant?->participant) {
                $data['faculty_name_ms'] = $eventParticipant->participant->name_ms;
                $data['faculty_name'] = $eventParticipant->participant->name;
            }

            if ($result?->match) {
                $data['home_name_ms'] = $result->match->homeParticipant?->name_ms;
                $data['away_name_ms'] = $result->match->awayParticipant?->name_ms;
                $data['winner_name_ms'] = $result->winner?->name_ms;
            }

            $notification->data = $data;

            return $notification;
        }));

        $actionRequiredQuery = $user->notifications()
            ->where('data->type', 'new_registration')
            ->whereNull('read_at');

        if ($isSuperAdmin && ! empty($filters['organization_id'])) {
            $actionRequiredQuery->where('data->organization_id', $filters['organization_id']);
        }

        $actionRequiredCount = $actionRequiredQuery->count();

        if (request()->wantsJson()) {
            return response()->json([
                'notifications' => $notifications->items(),
                'unread_count' => $user->unreadNotifications()->count(),
                'has_more' => $notifications->hasMorePages(),
            ]);
        }

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
            'filters' => [
                'tab' => $tab,
                'status' => $status,
                'type' => $filters['type'] ?? '',
                'organization_id' => $isSuperAdmin ? ($filters['organization_id'] ?? '') : '',
            ],
            'counts' => [
                'action_required' => $actionRequiredCount,
                'unread' => $user->unreadNotifications()->count(),
            ],
            'isSuperAdmin' => $isSuperAdmin,
            'organizations' => $isSuperAdmin
                ? Organization::query()->active()->orderBy('name')->get(['id', 'name'])
                : [],
            'notificationTypes' => [
                ['value' => 'confirmed', 'label' => 'Registration approved'],
                ['value' => 'rejected', 'label' => 'Registration rejected'],
                ['value' => 'result_recorded', 'label' => 'Result recorded'],
                ['value' => 'result_updated', 'label' => 'Result updated'],
                ['value' => 'result_removed', 'label' => 'Result removed'],
            ],
        ]);
    }

    public function unreadCount(): JsonResponse
    {
        return response()->json([
            'count' => Auth::user()->unreadNotifications()->count(),
        ]);
    }

    public function markAsRead(string $id): JsonResponse|RedirectResponse
    {
        $notification = Auth::user()->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', 'Notification marked as read.');
    }

    public function markAllAsRead(): RedirectResponse
    {
        Auth::user()->unreadNotifications->markAsRead();

        return redirect()->back()->with('success', 'All notifications marked as read.');
    }
}
