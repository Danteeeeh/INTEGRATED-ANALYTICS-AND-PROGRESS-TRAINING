<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\AssignmentAttachment;
use App\Models\AssignmentSubmission;
use App\Models\AuditLog;
use App\Models\Grade;
use App\Models\GradeHistory;
use App\Models\Rubric;
use App\Models\RubricAssessment;
use App\Models\SubmissionFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignmentService
{
    public function createAssignment(array $data): Assignment
    {
        return DB::transaction(function () use ($data) {
            $assignment = Assignment::create([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'instructions' => $data['instructions'] ?? null,
                'points' => $data['points'] ?? 100,
                'due_date' => $data['due_date'] ?? null,
                'availability_from' => $data['availability_from'] ?? null,
                'availability_until' => $data['availability_until'] ?? null,
                'lesson_id' => $data['lesson_id'] ?? null,
                'course_id' => $data['course_id'] ?? null,
                'class_id' => $data['class_id'] ?? null,
                'rubric_id' => $data['rubric_id'] ?? null,
                'allow_resubmission' => $data['allow_resubmission'] ?? false,
                'max_resubmissions' => $data['max_resubmissions'] ?? 0,
                'allow_late_submission' => $data['allow_late_submission'] ?? false,
                'late_penalty' => $data['late_penalty'] ?? 0,
                'status' => $data['status'] ?? 'draft',
                'created_by' => auth()->id(),
            ]);

            // Handle attachments
            if (isset($data['attachments']) && is_array($data['attachments'])) {
                foreach ($data['attachments'] as $attachment) {
                    AssignmentAttachment::create([
                        'assignment_id' => $assignment->id,
                        'file_name' => $attachment['file_name'],
                        'file_path' => $attachment['file_path'],
                        'file_size' => $attachment['file_size'] ?? null,
                        'file_type' => $attachment['file_type'] ?? null,
                    ]);
                }
            }

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'create',
                'resource' => 'assignment',
                'resource_id' => $assignment->id,
                'details' => ['title' => $assignment->title],
            ]);

            return $assignment;
        });
    }

    public function updateAssignment(Assignment $assignment, array $data): Assignment
    {
        return DB::transaction(function () use ($assignment, $data) {
            $oldValues = $assignment->toArray();

            $assignment->update([
                'title' => $data['title'] ?? $assignment->title,
                'description' => $data['description'] ?? $assignment->description,
                'instructions' => $data['instructions'] ?? $assignment->instructions,
                'points' => $data['points'] ?? $assignment->points,
                'due_date' => $data['due_date'] ?? $assignment->due_date,
                'availability_from' => $data['availability_from'] ?? $assignment->availability_from,
                'availability_until' => $data['availability_until'] ?? $assignment->availability_until,
                'lesson_id' => $data['lesson_id'] ?? $assignment->lesson_id,
                'course_id' => $data['course_id'] ?? $assignment->course_id,
                'class_id' => $data['class_id'] ?? $assignment->class_id,
                'rubric_id' => $data['rubric_id'] ?? $assignment->rubric_id,
                'allow_resubmission' => $data['allow_resubmission'] ?? $assignment->allow_resubmission,
                'max_resubmissions' => $data['max_resubmissions'] ?? $assignment->max_resubmissions,
                'allow_late_submission' => $data['allow_late_submission'] ?? $assignment->allow_late_submission,
                'late_penalty' => $data['late_penalty'] ?? $assignment->late_penalty,
                'status' => $data['status'] ?? $assignment->status,
            ]);

            // Handle attachments
            if (isset($data['attachments']) && is_array($data['attachments'])) {
                // Remove existing attachments not in the new list
                $existingAttachmentIds = array_column($data['attachments'], 'id');
                AssignmentAttachment::where('assignment_id', $assignment->id)
                    ->whereNotIn('id', $existingAttachmentIds)
                    ->delete();

                // Add/update attachments
                foreach ($data['attachments'] as $attachment) {
                    if (isset($attachment['id'])) {
                        AssignmentAttachment::where('id', $attachment['id'])->update([
                            'file_name' => $attachment['file_name'],
                            'file_path' => $attachment['file_path'],
                            'file_size' => $attachment['file_size'] ?? null,
                            'file_type' => $attachment['file_type'] ?? null,
                        ]);
                    } else {
                        AssignmentAttachment::create([
                            'assignment_id' => $assignment->id,
                            'file_name' => $attachment['file_name'],
                            'file_path' => $attachment['file_path'],
                            'file_size' => $attachment['file_size'] ?? null,
                            'file_type' => $attachment['file_type'] ?? null,
                        ]);
                    }
                }
            }

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'update',
                'resource' => 'assignment',
                'resource_id' => $assignment->id,
                'details' => [
                    'old_values' => $oldValues,
                    'new_values' => $assignment->toArray(),
                ],
            ]);

            return $assignment->fresh();
        });
    }

    public function submitAssignment(Assignment $assignment, array $data): AssignmentSubmission
    {
        return DB::transaction(function () use ($assignment, $data) {
            $studentId = auth()->id();

            // Check if assignment is available for submission
            if ($assignment->availability_from && now()->lt($assignment->availability_from)) {
                throw ValidationException::withMessages([
                    'assignment' => 'This assignment is not yet available for submission.',
                ]);
            }

            if ($assignment->availability_until && now()->gt($assignment->availability_until)) {
                throw ValidationException::withMessages([
                    'assignment' => 'This assignment is no longer available for submission.',
                ]);
            }

            // Check existing submissions
            $existingSubmission = AssignmentSubmission::where('assignment_id', $assignment->id)
                ->where('student_id', $studentId)
                ->latest()
                ->first();

            if ($existingSubmission && ! $assignment->allow_resubmission) {
                throw ValidationException::withMessages([
                    'assignment' => 'You have already submitted this assignment and resubmission is not allowed.',
                ]);
            }

            if ($existingSubmission && $assignment->allow_resubmission) {
                $resubmissionCount = AssignmentSubmission::where('assignment_id', $assignment->id)
                    ->where('student_id', $studentId)
                    ->count();

                if ($resubmissionCount >= $assignment->max_resubmissions) {
                    throw ValidationException::withMessages([
                        'assignment' => 'You have reached the maximum number of resubmissions.',
                    ]);
                }
            }

            // Determine if late submission
            $isLate = false;
            $latePenalty = 0;
            if ($assignment->due_date && now()->gt($assignment->due_date)) {
                if (! $assignment->allow_late_submission) {
                    throw ValidationException::withMessages([
                        'assignment' => 'Late submissions are not allowed for this assignment.',
                    ]);
                }
                $isLate = true;
                $latePenalty = $assignment->late_penalty;
            }

            $submission = AssignmentSubmission::create([
                'assignment_id' => $assignment->id,
                'student_id' => $studentId,
                'content' => $data['content'] ?? null,
                'is_late' => $isLate,
                'late_penalty' => $latePenalty,
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);

            // Handle file uploads
            if (isset($data['files']) && is_array($data['files'])) {
                foreach ($data['files'] as $file) {
                    SubmissionFile::create([
                        'submission_id' => $submission->id,
                        'file_name' => $file['file_name'],
                        'file_path' => $file['file_path'],
                        'file_size' => $file['file_size'] ?? null,
                        'file_type' => $file['file_type'] ?? null,
                    ]);
                }
            }

            AuditLog::create([
                'user_id' => $studentId,
                'action' => 'submit',
                'resource' => 'assignment_submission',
                'resource_id' => $submission->id,
                'details' => [
                    'assignment_id' => $assignment->id,
                    'is_late' => $isLate,
                ],
            ]);

            return $submission;
        });
    }

    public function gradeSubmission(AssignmentSubmission $submission, array $data): AssignmentSubmission
    {
        return DB::transaction(function () use ($submission, $data) {
            $oldGrade = $submission->grade;

            $submission->update([
                'grade' => $data['grade'],
                'feedback' => $data['feedback'] ?? null,
                'graded_by' => auth()->id(),
                'graded_at' => now(),
                'status' => 'graded',
            ]);

            // Create or update grade record
            $grade = Grade::updateOrCreate(
                [
                    'student_id' => $submission->student_id,
                    'gradable_type' => Assignment::class,
                    'gradable_id' => $submission->assignment_id,
                ],
                [
                    'points' => $data['grade'],
                    'max_points' => $submission->assignment->points,
                    'percentage' => ($data['grade'] / $submission->assignment->points) * 100,
                    'graded_by' => auth()->id(),
                    'graded_at' => now(),
                ]
            );

            // Record grade history
            GradeHistory::create([
                'student_id' => $submission->student_id,
                'grade_id' => $grade->id,
                'gradable_type' => Assignment::class,
                'gradable_id' => $submission->assignment_id,
                'previous_grade' => $oldGrade,
                'new_grade' => $data['grade'],
                'modified_by' => auth()->id(),
                'reason' => $data['reason'] ?? 'Assignment graded',
            ]);

            // Handle rubric assessment if provided
            if (isset($data['rubric_assessment']) && $submission->assignment->rubric_id) {
                $this->saveRubricAssessment($submission, $data['rubric_assessment']);
            }

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'grade',
                'resource' => 'assignment_submission',
                'resource_id' => $submission->id,
                'details' => [
                    'previous_grade' => $oldGrade,
                    'new_grade' => $data['grade'],
                ],
            ]);

            return $submission->fresh();
        });
    }

    protected function saveRubricAssessment(AssignmentSubmission $submission, array $assessmentData): void
    {
        // Delete existing assessments
        RubricAssessment::where('submission_id', $submission->id)->delete();

        foreach ($assessmentData as $criterionId => $levelId) {
            RubricAssessment::create([
                'rubric_id' => $submission->assignment->rubric_id,
                'submission_id' => $submission->id,
                'criterion_id' => $criterionId,
                'level_id' => $levelId,
                'assessed_by' => auth()->id(),
            ]);
        }
    }

    public function deleteAssignment(Assignment $assignment): bool
    {
        return DB::transaction(function () use ($assignment) {
            $assignment->delete();

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'delete',
                'resource' => 'assignment',
                'resource_id' => $assignment->id,
                'details' => ['title' => $assignment->title],
            ]);

            return true;
        });
    }
}
