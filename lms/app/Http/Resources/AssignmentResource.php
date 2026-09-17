<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'instructions' => $this->instructions,
            'points' => $this->points,
            'submission_type' => $this->submission_type,
            'due_date' => $this->due_date?->toDateTimeString(),
            'allow_late' => $this->allow_late,
            'late_submission_deduction_percent' => $this->late_submission_deduction_percent,
            'max_attempts' => $this->max_attempts,
            'allow_resubmission' => $this->allow_resubmission,
            'resubmission_deadline' => $this->resubmission_deadline?->toDateTimeString(),
            'availability_from' => $this->availability_from?->toDateTimeString(),
            'availability_until' => $this->availability_until?->toDateTimeString(),
            'status' => $this->status,
            'class_id' => $this->class_id,
            'class' => new ClassResource($this->whenLoaded('class')),
            'module_id' => $this->module_id,
            'module' => new ModuleResource($this->whenLoaded('module')),
            'lesson_id' => $this->lesson_id,
            'lesson' => new LessonResource($this->whenLoaded('lesson')),
            'rubric_id' => $this->rubric_id,
            'rubric' => new RubricResource($this->whenLoaded('rubric')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'created_by' => $this->created_by,
            'attachments_count' => $this->whenCounted('attachments'),
            'submissions_count' => $this->whenCounted('submissions'),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
