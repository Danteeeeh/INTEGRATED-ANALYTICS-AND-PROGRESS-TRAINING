<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'content' => $this->content,
            'position' => $this->position,
            'lesson_type' => $this->lesson_type,
            'estimated_duration' => $this->estimated_duration,
            'is_required' => $this->is_required,
            'is_published' => $this->is_published,
            'completion_rules' => $this->completion_rules,
            'module_id' => $this->module_id,
            'module' => new ModuleResource($this->whenLoaded('module')),
            'materials_count' => $this->whenCounted('materials'),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
