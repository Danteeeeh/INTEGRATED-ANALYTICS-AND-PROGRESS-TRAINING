<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\Question;
use App\Models\QuestionChoice;

class QuizExportService
{
    /**
     * Export quiz questions to plain text format with asterisks marking correct answers
     */
    public function exportToPlainText(Quiz $quiz): string
    {
        $questions = QuizQuestion::where('quiz_id', $quiz->id)
            ->with('question.choices')
            ->orderBy('position')
            ->get();

        $output = "Quiz: {$quiz->title}\n";
        $output .= "Total Questions: {$questions->count()}\n";
        $output .= str_repeat('=', 60) . "\n\n";

        foreach ($questions as $index => $quizQuestion) {
            $question = $quizQuestion->question;
            $questionNumber = $index + 1;

            $output .= "{$questionNumber}. {$question->question_text}\n";
            $output .= "Type: {$question->question_type}\n";
            $output .= "Points: {$question->default_points}\n";
            $output .= "Difficulty: {$question->difficulty}\n";

            if (in_array($question->question_type, ['multiple_choice', 'multiple_answer', 'true_false'], true)) {
                $choices = $question->choices()->orderBy('position')->get();
                $choiceLetters = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'];

                foreach ($choices as $choiceIndex => $choice) {
                    $letter = $choiceLetters[$choiceIndex] ?? chr(97 + $choiceIndex);
                    $asterisk = $choice->is_correct ? '*' : '';
                    $output .= "{$letter}. {$choice->choice_text}{$asterisk}\n";
                }
            } elseif ($question->question_type === 'identification' || $question->question_type === 'short_answer') {
                $output .= "Answer: [Fill in the blank]\n";
            } elseif ($question->question_type === 'essay') {
                $output .= "Answer: [Essay response]\n";
            }

            if (! empty($question->explanation)) {
                $output .= "Explanation: {$question->explanation}\n";
            }

            $output .= "\n";
        }

        return $output;
    }

    /**
     * Export quiz questions to CSV format
     */
    public function exportToCsv(Quiz $quiz): string
    {
        $questions = QuizQuestion::where('quiz_id', $quiz->id)
            ->with('question.choices')
            ->orderBy('position')
            ->get();

        $output = "question_text,question_type,choice_1,choice_2,choice_3,choice_4,correct_answer,points,difficulty,explanation\n";

        foreach ($questions as $quizQuestion) {
            $question = $quizQuestion->question;

            $row = [
                $this->escapeCsv($question->question_text),
                $question->question_type,
            ];

            $choices = $question->choices()->orderBy('position')->take(4)->get();
            $choiceLetters = ['a', 'b', 'c', 'd'];
            $correctAnswer = '';

            foreach ($choiceLetters as $index => $letter) {
                if (isset($choices[$index])) {
                    $choice = $choices[$index];
                    $row[] = $this->escapeCsv($choice->choice_text);
                    if ($choice->is_correct) {
                        $correctAnswer = $letter;
                    }
                } else {
                    $row[] = '';
                }
            }

            $row[] = $correctAnswer;
            $row[] = $question->default_points;
            $row[] = $question->difficulty;
            $row[] = $this->escapeCsv($question->explanation ?? '');

            $output .= implode(',', $row) . "\n";
        }

        return $output;
    }

    /**
     * Escape CSV values by wrapping in quotes if needed
     */
    protected function escapeCsv(string $value): string
    {
        if (str_contains($value, ',') || str_contains($value, '"') || str_contains($value, "\n")) {
            return '"' . str_replace('"', '""', $value) . '"';
        }
        return $value;
    }

    /**
     * Export quiz with student answers
     */
    public function exportWithStudentAnswers(Quiz $quiz, int $studentId): string
    {
        $questions = QuizQuestion::where('quiz_id', $quiz->id)
            ->with('question.choices')
            ->orderBy('position')
            ->get();

        $attempt = \App\Models\QuizAttempt::where('quiz_id', $quiz->id)
            ->where('student_id', $studentId)
            ->latest()
            ->first();

        $output = "Quiz: {$quiz->title}\n";
        $output .= "Student Answers Export\n";
        $output .= str_repeat('=', 60) . "\n\n";

        foreach ($questions as $index => $quizQuestion) {
            $question = $quizQuestion->question;
            $questionNumber = $index + 1;

            $output .= "{$questionNumber}. {$question->question_text}\n";

            if (in_array($question->question_type, ['multiple_choice', 'multiple_answer', 'true_false'], true)) {
                $choices = $question->choices()->orderBy('position')->get();
                $choiceLetters = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'];

                foreach ($choices as $choiceIndex => $choice) {
                    $letter = $choiceLetters[$choiceIndex] ?? chr(97 + $choiceIndex);
                    $asterisk = $choice->is_correct ? '*' : '';
                    $output .= "{$letter}. {$choice->choice_text}{$asterisk}\n";
                }
            }

            // Show student's answer if available
            if ($attempt) {
                $answer = \App\Models\QuizAnswer::where('quiz_attempt_id', $attempt->id)
                    ->where('question_id', $question->id)
                    ->first();

                if ($answer) {
                    $output .= "\nStudent Answer: ";
                    if ($answer->answer_text) {
                        $output .= $answer->answer_text;
                    } else {
                        $selectedChoices = \App\Models\QuizAnswerChoice::where('quiz_answer_id', $answer->id)
                            ->with('questionChoice')
                            ->get();
                        $selectedLetters = [];
                        foreach ($selectedChoices as $selected) {
                            $choiceIndex = $selected->questionChoice->position - 1;
                            $selectedLetters[] = $choiceLetters[$choiceIndex] ?? chr(97 + $choiceIndex);
                        }
                        $output .= implode(', ', $selectedLetters);
                    }
                    $output .= $answer->is_correct ? ' (Correct)' : ' (Incorrect)';
                } else {
                    $output .= "\nStudent Answer: Not answered";
                }
            }

            $output .= "\n\n";
        }

        return $output;
    }
}
