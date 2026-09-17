<?php

namespace App\Http\Requests;

use App\Models\Course;
use Illuminate\Foundation\Http\FormRequest;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Course::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|max:50|unique:courses,code',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'objectives' => 'nullable|string',
            'syllabus' => 'nullable|string',
            'prerequisites' => 'nullable|string',
            'duration_weeks' => 'nullable|integer|min:1',
            'academic_period_id' => 'nullable|exists:academic_periods,id',
            'category_id' => 'nullable|exists:course_categories,id',
            'thumbnail' => 'nullable|string|max:255',
            'status' => 'required|in:draft,published,archived',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Course code is required',
            'code.unique' => 'This course code already exists',
            'title.required' => 'Course title is required',
            'status.in' => 'Status must be draft, published, or archived',
        ];
    }
}
