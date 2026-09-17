<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'gradable_type' => 'required|string',
            'gradable_id' => 'required|integer',
            'student_id' => 'required|exists:users,id',
            'body' => 'required|string',
            'rating' => 'nullable|integer|min:1|max:5',
            'feedback_type' => 'nullable|string|in:general,constructive,praise,suggestion,question',
            'tags' => 'nullable|array',
            'is_private' => 'boolean',
        ]);

        $validated['author_id'] = auth()->id();
        $validated['is_private'] = $validated['is_private'] ?? true;

        if (isset($validated['tags']) && is_array($validated['tags'])) {
            $validated['tags'] = json_encode($validated['tags']);
        }

        $feedback = Feedback::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Feedback submitted successfully',
            'data' => $feedback,
        ], 201);
    }

    public function destroy(Feedback $feedback): JsonResponse
    {
        $this->authorize('delete', $feedback);

        $feedback->delete();

        return response()->json([
            'success' => true,
            'message' => 'Feedback deleted successfully',
        ]);
    }

    public function markRead(Request $request, Feedback $feedback): JsonResponse
    {
        $this->authorize('update', $feedback);

        $feedback->update([
            'read_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Feedback marked as read',
        ]);
    }
}
