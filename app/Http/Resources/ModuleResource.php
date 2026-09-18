<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ModuleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'position' => $this->position,
            'learning_objectives' => $this->learning_objectives,
            'completion_requirements' => $this->completion_requirements,
            'prerequisites' => $this->prerequisites,
            'is_published' => $this->is_published,
            'course_id' => $this->course_id,
            'course' => new CourseResource($this->whenLoaded('course')),
            'lessons_count' => $this->whenCounted('lessons'),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
