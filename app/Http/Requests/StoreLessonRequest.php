<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('lessons.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'module_id' => 'required|exists:modules,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'objectives' => 'nullable|string',
            'content' => 'nullable|string',
            'duration_minutes' => 'nullable|integer|min:0',
            'position' => 'nullable|integer|min:0',
            'lesson_type' => 'required|in:text,video,audio,pdf,document,presentation,external',
            'external_url' => 'nullable|string|max:500|required_if:lesson_type,external',
            'is_required' => 'nullable|boolean',
            'availability_from' => 'nullable|date',
            'availability_until' => 'nullable|date|after_or_equal:availability_from',
            'completion_rules' => 'nullable',
            'status' => 'required|in:draft,published,archived',
        ];
    }

    public function messages(): array
    {
        return [
            'module_id.required' => 'Module is required',
            'module_id.exists' => 'Selected module does not exist',
            'title.required' => 'Lesson title is required',
            'lesson_type.required' => 'Lesson type is required',
            'lesson_type.in' => 'Invalid lesson type',
            'external_url.required_if' => 'External URL is required when lesson type is external',
            'status.in' => 'Status must be draft, published, or archived',
        ];
    }
}
