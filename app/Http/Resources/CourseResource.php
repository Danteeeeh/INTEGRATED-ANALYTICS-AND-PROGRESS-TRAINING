<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'title' => $this->title,
            'description' => $this->description,
            'objectives' => $this->objectives,
            'syllabus' => $this->syllabus,
            'prerequisites' => $this->prerequisites,
            'duration_weeks' => $this->duration_weeks,
            'credits' => $this->credits,
            'thumbnail' => $this->thumbnail,
            'status' => $this->status,
            'category' => $this->when($this->relationLoaded('category'), function () {
                return new CourseCategoryResource($this->category);
            }),
            'category_id' => $this->category_id,
            'academic_period' => $this->when($this->relationLoaded('academicPeriod'), function () {
                return new AcademicPeriodResource($this->academicPeriod);
            }),
            'academic_period_id' => $this->academic_period_id,
            'creator' => $this->when($this->relationLoaded('creator'), function () {
                return new UserResource($this->creator);
            }),
            'created_by' => $this->created_by,
            'modules_count' => $this->whenCounted('modules'),
            'classes_count' => $this->whenCounted('classes'),
            'enrollments_count' => $this->whenCounted('enrollments'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
