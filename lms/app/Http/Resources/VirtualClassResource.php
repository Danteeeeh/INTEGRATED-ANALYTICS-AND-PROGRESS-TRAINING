<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VirtualClassResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'meeting_date' => $this->meeting_date->toDateTimeString(),
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'meeting_provider' => $this->meeting_provider,
            'meeting_url' => $this->meeting_url,
            'meeting_id' => $this->meeting_id,
            'meeting_password' => $this->meeting_password,
            'recurrence' => $this->recurrence ?? null,
            'status' => $this->status,
            'course_id' => $this->course_id,
            'course' => new CourseResource($this->whenLoaded('course')),
            'class_id' => $this->class_id,
            'class' => new ClassResource($this->whenLoaded('class')),
            'instructor_id' => $this->instructor_id,
            'instructor' => new UserResource($this->whenLoaded('instructor')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'created_by' => $this->created_by,
            'attendees_count' => $this->whenCounted('attendees'),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
