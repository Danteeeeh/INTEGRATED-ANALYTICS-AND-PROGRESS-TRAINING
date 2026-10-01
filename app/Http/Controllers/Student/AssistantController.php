<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\AIAssistantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssistantController extends Controller
{
    public function __construct(private AIAssistantService $assistant) {}

    public function index(): View
    {
        return view('student.assistant.index', [
            'activeNav' => 'assistant',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:500',
        ]);

        $reply = $this->assistant->respond($request->user(), $validated['message']);

        return response()->json(['reply' => $reply]);
    }
}
