<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Notification::class);

        $query = Notification::with(['user', 'notifiable']);

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('channel')) {
            $query->where('channel', $request->channel);
        }

        if ($request->filled('read_status')) {
            if ($request->read_status === 'unread') {
                $query->unread();
            } elseif ($request->read_status === 'read') {
                $query->read();
            }
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%");
            });
        }

        $notifications = $query->orderByDesc('created_at')->paginate(20);
        $users = User::orderBy('last_name')->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'email']);

        $unreadCount = Notification::unread()->count();
        $totalCount = Notification::count();

        return view('admin.notifications.index', compact(
            'notifications',
            'users',
            'unreadCount',
            'totalCount'
        ));
    }

    public function markRead(Request $request, Notification $notification): RedirectResponse
    {
        $this->authorize('update', $notification);

        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
            session()->flash('success', 'Notification marked as read.');
        } else {
            $notification->update(['read_at' => null]);
            session()->flash('success', 'Notification marked as unread.');
        }

        return back();
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $this->authorize('update', Notification::class);

        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'notification_ids' => 'nullable|array',
            'notification_ids.*' => 'exists:notifications,id',
        ]);

        $query = Notification::query();

        if ($request->filled('user_id')) {
            $query->where('user_id', $validated['user_id']);
        }

        if ($request->filled('notification_ids')) {
            $query->whereIn('id', $validated['notification_ids']);
        }

        $marked = $query->unread()->update(['read_at' => now()]);

        session()->flash('success', "{$marked} notification(s) marked as read successfully.");

        return back();
    }

    public function preferences(Request $request): View
    {
        $this->authorize('view', UserPreference::class);

        $userId = $request->filled('user_id') ? $request->user_id : $request->user()->id;
        $user = User::findOrFail($userId);

        $preferences = $this->preferenceMap($user->id);

        $users = User::orderBy('last_name')->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'email']);

        return view('admin.notifications.preferences', compact('preferences', 'user', 'users'));
    }

    public function updatePreferences(Request $request): RedirectResponse
    {
        $this->authorize('update', UserPreference::class);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'email_notifications' => 'boolean',
            'push_notifications' => 'boolean',
            'sms_notifications' => 'boolean',
            'notify_on_assignment' => 'boolean',
            'notify_on_quiz' => 'boolean',
            'notify_on_grade' => 'boolean',
            'notify_on_announcement' => 'boolean',
            'notify_on_discussion' => 'boolean',
            'notify_on_message' => 'boolean',
            'notify_on_attendance' => 'boolean',
            'daily_digest' => 'boolean',
            'weekly_summary' => 'boolean',
            'quiet_hours_start' => 'nullable|date_format:H:i',
            'quiet_hours_end' => 'nullable|date_format:H:i',
        ]);

        $userId = $validated['user_id'];

        $booleanKeys = [
            'email_notifications',
            'push_notifications',
            'sms_notifications',
            'notify_on_assignment',
            'notify_on_quiz',
            'notify_on_grade',
            'notify_on_announcement',
            'notify_on_discussion',
            'notify_on_message',
            'notify_on_attendance',
            'daily_digest',
            'weekly_summary',
        ];

        foreach ($booleanKeys as $key) {
            UserPreference::updateOrCreate(
                ['user_id' => $userId, 'key' => $key],
                ['value' => $request->boolean($key, false) ? '1' : '0']
            );
        }

        foreach (['quiet_hours_start', 'quiet_hours_end'] as $key) {
            $value = $validated[$key] ?? null;
            if ($value === null || $value === '') {
                UserPreference::where('user_id', $userId)->where('key', $key)->delete();
            } else {
                UserPreference::updateOrCreate(
                    ['user_id' => $userId, 'key' => $key],
                    ['value' => $value]
                );
            }
        }

        session()->flash('success', 'Notification preferences updated successfully.');

        return redirect()->route('admin.notifications.preferences', ['user_id' => $userId]);
    }

    /**
     * Build a key/value preference map with defaults for the given user.
     *
     * @return array<string, bool|string|null>
     */
    protected function preferenceMap(int $userId): array
    {
        $defaults = [
            'email_notifications' => true,
            'push_notifications' => true,
            'sms_notifications' => false,
            'notify_on_assignment' => true,
            'notify_on_quiz' => true,
            'notify_on_grade' => true,
            'notify_on_announcement' => true,
            'notify_on_discussion' => true,
            'notify_on_message' => true,
            'notify_on_attendance' => false,
            'daily_digest' => false,
            'weekly_summary' => true,
            'quiet_hours_start' => null,
            'quiet_hours_end' => null,
        ];

        $stored = UserPreference::where('user_id', $userId)->pluck('value', 'key')->toArray();

        foreach ($defaults as $key => $default) {
            if (! array_key_exists($key, $stored)) {
                continue;
            }

            $raw = $stored[$key];
            if ($raw === null || $raw === '') {
                $defaults[$key] = null;

                continue;
            }

            if (is_bool($default)) {
                $defaults[$key] = in_array(strtolower((string) $raw), ['1', 'true', 'on', 'yes'], true);
            } else {
                $defaults[$key] = $raw;
            }
        }

        return $defaults;
    }
}
