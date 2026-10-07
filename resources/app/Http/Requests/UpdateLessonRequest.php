<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        $lesson = $this->route('lesson');

        return $lesson && ($this->user()?->hasPermission('lessons.update') ?? false);
    }

    public function rules(): array
    {
        return [
            'module_id' => 'sometimes|required|exists:modules,id',
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'objectives' => 'nullable|string',
            'content' => 'nullable|string',
            'duration_minutes' => 'nullable|integer|min:0',
            'position' => 'nullable|integer|min:0',
            'lesson_type' => 'sometimes|required|in:text,video,audio,pdf,document,presentation,external',
            'external_url' => 'nullable|string|max:500|required_if:lesson_type,external',
            'is_required' => 'nullable|boolean',
            'availability_from' => 'nullable|date',
            'availability_until' => 'nullable|date|after_or_equal:availability_from',
            'completion_rules' => 'nullable',
            'status' => 'sometimes|required|in:draft,published,archived',
        ];
    }

    public function messages(): array
    {
        return [
            'module_id.exists' => 'Selected module does not exist',
            'title.required' => 'Lesson title is required',
            'lesson_type.in' => 'Invalid lesson type',
            'external_url.required_if' => 'External URL is required when lesson type is external',
            'status.in' => 'Status must be draft, published, or archived',
        ];
    }
}
