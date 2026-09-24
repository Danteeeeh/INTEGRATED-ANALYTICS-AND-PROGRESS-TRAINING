<?php

namespace App\Http\Requests;

use App\Models\ClassModel;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        $class = $this->route('class');

        return $class instanceof ClassModel
            && ($this->user()?->can('update', $class) ?? false);
    }

    public function rules(): array
    {
        $class = $this->route('class');
        $classId = $class instanceof ClassModel ? $class->id : $class;

        return [
            'code' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('classes', 'code')->ignore($classId)],
            'course_id' => 'sometimes|required|exists:courses,id',
            'instructor_id' => 'sometimes|required|exists:users,id',
            'academic_period_id' => 'nullable|exists:academic_periods,id',
            'schedule' => 'nullable|string|max:255',
            'room' => 'nullable|string|max:100',
            'capacity' => 'nullable|integer|min:1',
            'status' => 'sometimes|required|in:active,inactive,cancelled',
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'This class code already exists',
            'status.in' => 'Status must be active, inactive, or cancelled',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $instructorId = $this->input('instructor_id');
            if ($instructorId) {
                $instructor = User::find($instructorId);
                if ($instructor && ! $instructor->isInstructor()) {
                    $validator->errors()->add('instructor_id', 'Selected user must be an instructor');
                }
            }
        });
    }
}
