<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiscussionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'discussion_type' => $this->discussion_type,
            'is_pinned' => $this->is_pinned,
            'is_locked' => $this->is_locked,
            'status' => $this->status,
            'course_id' => $this->course_id,
            'course' => new CourseResource($this->whenLoaded('course')),
            'class_id' => $this->class_id,
            'class' => new ClassResource($this->whenLoaded('class')),
            'module_id' => $this->module_id,
            'module' => new ModuleResource($this->whenLoaded('module')),
            'lesson_id' => $this->lesson_id,
            'lesson' => new LessonResource($this->whenLoaded('lesson')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'created_by' => $this->created_by,
            'posts_count' => $this->whenCounted('posts'),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
