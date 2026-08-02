<?php

namespace App\Http\Controllers;

use App\Models\Organization;
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
        $actionRequiredCount = $user->unreadNotifications()
            ->where('data->type', 'new_registration')
            ->count();

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
                ['value' => 'new_registration', 'label' => 'New registration'],
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
