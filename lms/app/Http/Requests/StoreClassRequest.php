<?php

namespace App\Http\Requests;

use App\Models\ClassModel;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ClassModel::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|max:50|unique:classes,code',
            'course_id' => 'required|exists:courses,id',
            'instructor_id' => 'required|exists:users,id',
            'academic_period_id' => 'nullable|exists:academic_periods,id',
            'schedule' => 'nullable|string|max:255',
            'room' => 'nullable|string|max:100',
            'capacity' => 'nullable|integer|min:1',
            'status' => 'required|in:active,inactive,cancelled',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Class code is required',
            'code.unique' => 'This class code already exists',
            'course_id.required' => 'Course is required',
            'course_id.exists' => 'Selected course does not exist',
            'instructor_id.required' => 'Instructor is required',
            'instructor_id.exists' => 'Selected instructor does not exist',
            'status.in' => 'Status must be active, inactive, or cancelled',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $instructorId = $this->input('instructor_id');
            $instructor = User::find($instructorId);

            if ($instructor && ! $instructor->isInstructor()) {
                $validator->errors()->add('instructor_id', 'Selected user must be an instructor');
            }
        });
    }
}
