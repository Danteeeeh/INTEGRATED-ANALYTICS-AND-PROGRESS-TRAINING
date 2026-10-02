<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Shared notification endpoints used by the bell across all roles.
 * Ownership is enforced directly (user_id must match the requester).
 */
class NotificationController extends Controller
{
    public function markRead(Request $request, int $id): JsonResponse
    {
        $notification = Notification::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $notification) {
            return response()->json(['ok' => false], 404);
        }

        $notification->update([
            'read_at' => now(),
            'is_read' => true,
        ]);

        return response()->json(['ok' => true]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        Notification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
                'is_read' => true,
            ]);

        return response()->json(['ok' => true]);
    }
}
