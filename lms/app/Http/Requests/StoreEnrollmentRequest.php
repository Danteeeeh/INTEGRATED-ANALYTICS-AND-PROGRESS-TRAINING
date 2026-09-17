<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('enrollments.create');
    }

    public function rules(): array
    {
        return [
            'class_id' => 'required|exists:classes,id',
            'student_id' => 'required|exists:users,id',
            'status' => 'sometimes|required|in:pending,active,completed,dropped,suspended',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'class_id.required' => 'Class is required',
            'class_id.exists' => 'Selected class does not exist',
            'student_id.required' => 'Student is required',
            'student_id.exists' => 'Selected student does not exist',
            'status.in' => 'Status must be pending, active, completed, dropped, or suspended',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $studentId = $this->input('student_id');

            $student = User::find($studentId);
            if ($student && ! $student->isStudent()) {
                $validator->errors()->add('student_id', 'Selected user must be a student');
            }
        });
    }
}
