<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassModelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'course' => $this->when($this->relationLoaded('course'), function () {
                return new CourseResource($this->course);
            }),
            'course_id' => $this->course_id,
            'instructor' => $this->when($this->relationLoaded('instructor'), function () {
                return new UserResource($this->instructor);
            }),
            'instructor_id' => $this->instructor_id,
            'academic_period' => $this->when($this->relationLoaded('academicPeriod'), function () {
                return new AcademicPeriodResource($this->academicPeriod);
            }),
            'academic_period_id' => $this->academic_period_id,
            'schedule' => $this->schedule,
            'room' => $this->room,
            'capacity' => $this->capacity,
            'status' => $this->status,
            'enrollments_count' => $this->whenCounted('enrollments'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
