<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'instructions' => $this->instructions,
            'time_limit_minutes' => $this->time_limit_minutes,
            'attempt_limit' => $this->attempt_limit,
            'passing_score_percent' => $this->passing_score_percent,
            'shuffle_questions' => $this->shuffle_questions,
            'shuffle_choices' => $this->shuffle_choices,
            'allow_navigation' => $this->allow_navigation,
            'auto_save_seconds' => $this->auto_save_seconds,
            'auto_submit_on_timeout' => $this->auto_submit_on_timeout,
            'result_visibility' => $this->result_visibility,
            'review_allowed' => $this->review_allowed,
            'show_correct_answers' => $this->show_correct_answers,
            'availability_from' => $this->availability_from?->toDateTimeString(),
            'availability_until' => $this->availability_until?->toDateTimeString(),
            'status' => $this->status,
            'class_id' => $this->class_id,
            'class' => new ClassResource($this->whenLoaded('class')),
            'module_id' => $this->module_id,
            'module' => new ModuleResource($this->whenLoaded('module')),
            'lesson_id' => $this->lesson_id,
            'lesson' => new LessonResource($this->whenLoaded('lesson')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'created_by' => $this->created_by,
            'questions_count' => $this->whenCounted('questions'),
            'attempts_count' => $this->whenCounted('attempts'),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
