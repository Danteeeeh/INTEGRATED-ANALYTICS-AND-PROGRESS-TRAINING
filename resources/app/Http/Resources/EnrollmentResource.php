<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnrollmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student' => $this->when($this->relationLoaded('student'), function () {
                return new UserResource($this->student);
            }),
            'student_id' => $this->student_id,
            'class' => $this->when($this->relationLoaded('class'), function () {
                return new ClassResource($this->class);
            }),
            'class_id' => $this->class_id,
            'status' => $this->status,
            'final_grade' => $this->final_grade,
            'notes' => $this->notes,
            'enrolled_at' => $this->enrolled_at?->format('Y-m-d H:i:s'),
            'completed_at' => $this->completed_at?->format('Y-m-d H:i:s'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
