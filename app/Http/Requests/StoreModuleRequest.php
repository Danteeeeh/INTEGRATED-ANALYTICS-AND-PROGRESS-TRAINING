<?php

namespace App\Http\Requests;

use App\Models\Module;
use Illuminate\Foundation\Http\FormRequest;

class StoreModuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Module::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'course_id' => 'required|exists:courses,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'objectives' => 'nullable|string',
            'position' => 'nullable|integer|min:0',
            'is_required' => 'nullable|boolean',
            'status' => 'required|in:draft,published,archived',
        ];
    }

    public function messages(): array
    {
        return [
            'course_id.required' => 'Course is required',
            'course_id.exists' => 'Selected course does not exist',
            'title.required' => 'Module title is required',
            'status.in' => 'Status must be draft, published, or archived',
        ];
    }
}
