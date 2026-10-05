# Quiz/Assignment/Exam Permission System

## Overview
This document outlines the permission system for quizzes, assignments, and exams in the LMS system. The permissions follow a role-based access control (RBAC) model where different user roles have different capabilities.

## User Roles
- **Student**: Can view and attempt/submission quizzes, assignments, and exams
- **Instructor**: Can create, edit, manage, grade, and control all aspects of quizzes, assignments, and exams
- **System Admin**: Has full access to all operations across the system

## Permission Matrix

### Quiz Permissions

| Operation | Student | Instructor | System Admin |
|-----------|---------|------------|--------------|
| View quiz | ✅ | ✅ | ✅ |
| Create quiz | ❌ | ✅ | ✅ |
| Edit quiz | ❌ | ✅ | ✅ |
| Open/Publish quiz | ❌ | ✅ | ✅ |
| Close quiz | ❌ | ✅ | ✅ |
| Reopen quiz | ❌ | ✅ | ✅ |
| Extend deadline | ❌ | ✅ | ✅ |
| Manually reset attempt | ❌ | ✅ | ✅ |
| View attempts | ❌ | ✅ | ✅ |
| Grade attempts | ❌ | ✅ | ✅ |
| Override grade | ❌ | ✅ | ✅ |
| Delete quiz | ❌ | ✅ | ✅ |
| Manage settings | ❌ | ✅ | ✅ |
| Attempt quiz | ✅ | ❌ | ❌ |

### Assignment Permissions

| Operation | Student | Instructor | System Admin |
|-----------|---------|------------|--------------|
| View assignment | ✅ | ✅ | ✅ |
| Create assignment | ❌ | ✅ | ✅ |
| Edit assignment | ❌ | ✅ | ✅ |
| Open/Publish assignment | ❌ | ✅ | ✅ |
| Close assignment | ❌ | ✅ | ✅ |
| Reopen assignment | ❌ | ✅ | ✅ |
| Allow resubmission | ❌ | ✅ | ✅ |
| Extend deadline | ❌ | ✅ | ✅ |
| Manually reset attempt | ❌ | ✅ | ✅ |
| View submissions | ❌ | ✅ | ✅ |
| Grade submissions | ❌ | ✅ | ✅ |
| Override grade | ❌ | ✅ | ✅ |
| Delete assignment | ❌ | ✅ | ✅ |
| Manage settings | ❌ | ✅ | ✅ |
| Submit assignment | ✅ | ❌ | ❌ |
| Resubmit assignment | ✅ | ❌ | ❌ |

### Exam Permissions

| Operation | Student | Instructor | System Admin |
|-----------|---------|------------|--------------|
| View exam | ✅ | ✅ | ✅ |
| Create exam | ❌ | ✅ | ✅ |
| Edit exam | ❌ | ✅ | ✅ |
| Open/Publish exam | ❌ | ✅ | ✅ |
| Close exam | ❌ | ✅ | ✅ |
| Reopen exam | ❌ | ✅ | ✅ |
| Extend deadline | ❌ | ✅ | ✅ |
| Manually reset attempt | ❌ | ✅ | ✅ |
| View attempts | ❌ | ✅ | ✅ |
| Grade attempts | ❌ | ✅ | ✅ |
| Override grade | ❌ | ✅ | ✅ |
| Delete exam | ❌ | ✅ | ✅ |
| Manage settings | ❌ | ✅ | ✅ |
| Attempt exam | ✅ | ❌ | ❌ |

## Policy Files

### QuizPolicy.php
Located at: `app/Policies/QuizPolicy.php`

Methods:
- `viewAny()` - Check if user can view quizzes list
- `view()` - Check if user can view specific quiz
- `create()` - Check if user can create quiz
- `update()` - Check if user can edit quiz
- `delete()` - Check if user can delete quiz
- `grade()` - Check if user can grade quiz attempts
- `attempt()` - Check if student can attempt quiz
- `publish()` - Check if user can publish quiz
- `close()` - Check if user can close quiz
- `reopen()` - Check if user can reopen closed quiz
- `extendDeadline()` - Check if user can grant deadline extensions
- `resetAttempt()` - Check if user can reset student attempts
- `manageSettings()` - Check if user can manage quiz settings

### AssignmentPolicy.php
Located at: `app/Policies/AssignmentPolicy.php`

Methods:
- `viewAny()` - Check if user can view assignments list
- `view()` - Check if user can view specific assignment
- `create()` - Check if user can create assignment
- `update()` - Check if user can edit assignment
- `delete()` - Check if user can delete assignment
- `grade()` - Check if user can grade assignment submissions
- `submit()` - Check if student can submit assignment
- `resubmit()` - Check if student can resubmit assignment
- `publish()` - Check if user can publish assignment
- `close()` - Check if user can close assignment
- `reopen()` - Check if user can reopen closed assignment
- `extendDeadline()` - Check if user can grant deadline extensions
- `allowResubmission()` - Check if user can allow resubmissions
- `resetAttempt()` - Check if user can reset student submissions
- `manageSettings()` - Check if user can manage assignment settings

### ExamPolicy.php
Located at: `app/Policies/ExamPolicy.php`

Methods:
- `viewAny()` - Check if user can view exams list
- `view()` - Check if user can view specific exam
- `create()` - Check if user can create exam
- `update()` - Check if user can edit exam
- `delete()` - Check if user can delete exam
- `grade()` - Check if user can grade exam attempts
- `attempt()` - Check if student can attempt exam
- `publish()` - Check if user can publish exam
- `close()` - Check if user can close exam
- `reopen()` - Check if user can reopen closed exam
- `extendDeadline()` - Check if user can grant deadline extensions
- `resetAttempt()` - Check if user can reset student attempts
- `manageSettings()` - Check if user can manage exam settings

### QuizAttemptPolicy.php
Located at: `app/Policies/QuizAttemptPolicy.php`

Methods:
- `viewAny()` - Check if user can view quiz attempts list
- `view()` - Check if user can view specific quiz attempt
- `create()` - Check if student can create quiz attempt
- `delete()` - Check if user can delete quiz attempt
- `grade()` - Check if user can grade quiz attempt
- `review()` - Check if user can review quiz attempt

### ExamAttemptPolicy.php
Located at: `app/Policies/ExamAttemptPolicy.php`

Methods:
- `viewAny()` - Check if user can view exam attempts list
- `view()` - Check if user can view specific exam attempt
- `create()` - Check if student can create exam attempt
- `delete()` - Check if user can delete exam attempt
- `grade()` - Check if user can grade exam attempt
- `review()` - Check if user can review exam attempt

## Implementation Details

### Permission Checks in Controllers
Controllers use Laravel's authorization gates to check permissions:

```php
// Example in QuizController
public function grantExtension(Request $request, Course $course, Quiz $quiz)
{
    $this->authorize('extendDeadline', $quiz);
    // ... rest of the method
}
```

### Instructor Management Logic
Instructors can only manage quizzes, assignments, and exams for:
- Classes they are assigned to instruct
- Courses where they instruct at least one class
- Items they created themselves

### Student Access Logic
Students can only:
- View items for classes they are enrolled in
- Attempt/submit items for their enrolled classes
- View their own attempts/submissions

### Overdue Handling
The system respects deadline extensions:
- Overdue detection considers active extensions
- Students with extensions won't see items as overdue
- Instructors can grant/revoke extensions individually

## Database Tables for Extensions

### quiz_extensions
- `quiz_id` - Reference to quiz
- `student_id` - Student receiving extension
- `extended_until` - New deadline
- `reason` - Reason for extension
- `granted_by` - Instructor who granted it

### assignment_extensions
- `assignment_id` - Reference to assignment
- `student_id` - Student receiving extension
- `extended_until` - New deadline
- `reason` - Reason for extension
- `granted_by` - Instructor who granted it

### exam_extensions
- `exam_id` - Reference to exam
- `student_id` - Student receiving extension
- `extended_until` - New deadline
- `reason` - Reason for extension
- `granted_by` - Instructor who granted it

## Usage Examples

### Granting Extension (Instructor)
```php
// In controller
public function grantExtension(Request $request, Course $course, Quiz $quiz)
{
    $this->authorize('extendDeadline', $quiz); // Permission check
    
    QuizExtension::updateOrCreate(
        ['quiz_id' => $quiz->id, 'student_id' => $studentId],
        ['extended_until' => $newDeadline, 'reason' => $reason, 'granted_by' => auth()->id()]
    );
}
```

### Checking Overdue Status
```php
// In model
public function isOverdueForStudent(int $studentId): bool
{
    $extension = QuizExtension::where('quiz_id', $this->id)
        ->where('student_id', $studentId)
        ->where('extended_until', '>=', now())
        ->first();

    if ($extension) {
        return false; // Has active extension
    }

    return $this->isOverdue();
}
```

## Security Notes

1. **Double Authorization**: Controllers check both policy authorization and ownership/instructor relationship
2. **Extension Isolation**: Extensions are per-student and don't affect other students
3. **Deadline Enforcement**: The system blocks attempts/submissions after deadlines (respecting extensions)
4. **Grade Protection**: Only instructors and admins can override grades
5. **Attempt Reset**: Only instructors and admins can reset student attempts (use with caution)

## Future Enhancements

Potential additions:
- Bulk extension grants (for multiple students at once)
- Extension templates (common reasons)
- Extension audit log
- Automatic extension suggestions based on patterns
- Permission delegation (TAs with limited permissions)
