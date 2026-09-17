<?php

namespace App\Http\Requests;

use App\Models\Course;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $course = $this->route('course');

        return $course && ($this->user()?->can('update', $course) ?? false);
    }

    public function rules(): array
    {
        $course = $this->route('course');
        $courseId = $course instanceof Course ? $course->id : $course;

        return [
            'code' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('courses', 'code')->ignore($courseId)],
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'objectives' => 'nullable|string',
            'syllabus' => 'nullable|string',
            'prerequisites' => 'nullable|string',
            'credits' => 'nullable|integer|min:1|max:10',
            'duration_weeks' => 'nullable|integer|min:1',
            'academic_period_id' => 'nullable|exists:academic_periods,id',
            'category_id' => 'nullable|exists:course_categories,id',
            'thumbnail' => 'nullable|string|max:255',
            'status' => 'sometimes|required|in:draft,published,archived',
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'This course code already exists',
            'status.in' => 'Status must be draft, published, or archived',
        ];
    }
}
