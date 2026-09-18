<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasPermission('enrollments.update');
    }

    public function rules(): array
    {
        return [
            'status' => 'sometimes|required|in:pending,active,completed,dropped,suspended',
            'final_grade' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'Status must be pending, active, completed, dropped, or suspended',
            'final_grade.numeric' => 'Grade must be a number',
            'final_grade.min' => 'Grade cannot be less than 0',
            'final_grade.max' => 'Grade cannot exceed 100',
        ];
    }
}
