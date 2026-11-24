@extends('layouts.student')

@section('title', 'Exam Results')
@php
    $activeNav = 'exams';
    $pageTitle = 'Exam Results';
    $pageIcon = '<i class="fa-solid fa-file-alt"></i>';
@endphp

@section('content')
    <div class="container py-8">
        <div class="mb-8">
            <a href="{{ route('student.courses.exams.show', [$course, $exam]) }}" class="text-blue-600 hover:text-blue-800 mb-4 inline-block">
                <i class="fa-solid fa-arrow-left mr-2"></i> Back to Exam
            </a>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Exam Results</h1>
            <p class="text-gray-600">{{ $exam->title }} - Attempt #{{ $attempt->attempt_number }}</p>
        </div>

        @if(!$showResults)
            <div class="bg-yellow-50 border border-yellow-200 p-6 rounded-lg mb-6">
                <div class="flex items-center">
                    <i class="fa-solid fa-clock text-yellow-600 mr-3 text-2xl"></i>
                    <div>
                        <div class="font-semibold text-yellow-800 text-lg">Results Not Available Yet</div>
                        <div class="text-yellow-700">
                            @if($exam->result_visibility === 'after_grading')
                                Your results will be available after grading is complete.
                            @elseif($exam->result_visibility === 'after_all_submissions')
                                Your results will be available after all students have submitted.
                            @elseif($exam->result_visibility === 'after_date' && $exam->results_release_date)
                                Your results will be available on {{ $exam->results_release_date->format('F d, Y g:i A') }}.
                            @else
                                Results are not available for this exam.
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @else
            @php
                $showScore = (bool) $exam->show_score;
                $showReview = (bool) $exam->allow_review;
                $showAnswerKey = $showReview && (bool) $exam->show_correct_answers;
            @endphp

            <!-- Score Summary -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                @if($showScore)
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="text-center p-4 {{ $attempt->is_passed === null ? 'bg-gray-50' : ($attempt->is_passed ? 'bg-green-50' : 'bg-red-50') }} rounded-lg">
                            <div class="text-sm text-gray-600 mb-1">Score</div>
                            <div class="text-4xl font-bold {{ $attempt->is_passed === null ? 'text-gray-700' : ($attempt->is_passed ? 'text-green-600' : 'text-red-600') }}">
                                {{ $attempt->score_percent }}%
                            </div>
                        </div>
                        <div class="text-center p-4 bg-gray-50 rounded-lg">
                            <div class="text-sm text-gray-600 mb-1">Status</div>
                            <div class="text-2xl font-semibold {{ $attempt->is_passed === null ? 'text-gray-700' : ($attempt->is_passed ? 'text-green-600' : 'text-red-600') }}">
                                @if($attempt->is_passed === null)
                                    Pending grading
                                @elseif($attempt->is_passed)
                                    Passed
                                @else
                                    Failed
                                @endif
                            </div>
                        </div>
                        <div class="text-center p-4 bg-gray-50 rounded-lg">
                            <div class="text-sm text-gray-600 mb-1">Time Spent</div>
                            <div class="text-2xl font-semibold text-gray-900">
                                {{ floor($attempt->time_spent_seconds / 60) }}m {{ $attempt->time_spent_seconds % 60 }}s
                            </div>
                        </div>
                    </div>
                @else
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="text-center p-4 bg-gray-50 rounded-lg">
                            <div class="text-sm text-gray-600 mb-1">Score</div>
                            <div class="text-2xl font-semibold text-gray-900">Hidden by your instructor</div>
                            <div class="text-sm text-gray-600 mt-2">Your answers were submitted successfully.</div>
                        </div>
                        <div class="text-center p-4 bg-gray-50 rounded-lg">
                            <div class="text-sm text-gray-600 mb-1">Time Spent</div>
                            <div class="text-2xl font-semibold text-gray-900">
                                {{ floor($attempt->time_spent_seconds / 60) }}m {{ $attempt->time_spent_seconds % 60 }}s
                            </div>
                        </div>
                    </div>
                @endif

                <div class="mt-6 pt-6 border-t border-gray-200">
                    <div class="flex justify-between text-sm text-gray-600">
                        <div>
                            <span class="font-medium">Submitted:</span>
                            {{ $attempt->submitted_at ? $attempt->submitted_at->format('M d, Y g:i A') : 'N/A' }}
                        </div>
                        <div>
                            <span class="font-medium">Attempt #:</span>
                            {{ $attempt->attempt_number }}
                        </div>
                    </div>
                </div>
            </div>

            @if($showReview)
                <!-- Question Breakdown -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Question Breakdown</h2>
                    <div class="space-y-4">
                        @foreach($attempt->answers as $index => $answer)
                            @php
                                $snap = $answer->snapshotArray();
                                $qtype = $snap['question_type'] ?? $answer->question?->question_type;
                                $pending = $answer->is_correct === null;
                                $correctAnswers = $showAnswerKey ? $answer->correctAnswerTexts() : [];
                            @endphp
                            <div class="p-4 rounded-lg {{ $pending ? 'bg-yellow-50 border border-yellow-200' : ($answer->is_correct ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200') }}">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="flex items-center">
                                        <span class="bg-gray-200 text-gray-700 px-3 py-1 rounded-full text-sm font-medium mr-3">
                                            Q{{ $index + 1 }}
                                        </span>
                                        @if($pending)
                                            <span class="text-yellow-700 font-medium">
                                                <i class="fa-solid fa-hourglass-half mr-1"></i> Pending review
                                            </span>
                                        @elseif($answer->is_correct)
                                            <span class="text-green-600 font-medium">
                                                <i class="fa-solid fa-check-circle mr-1"></i> Correct
                                            </span>
                                        @else
                                            <span class="text-red-600 font-medium">
                                                <i class="fa-solid fa-times-circle mr-1"></i> Incorrect
                                            </span>
                                        @endif
                                    </div>
                                    @if($showScore)
                                        <div class="text-sm text-gray-600">
                                            @if($pending || $answer->points_awarded === null)
                                                Pending
                                            @else
                                                {{ $answer->points_awarded }} / {{ rtrim(rtrim(number_format((float) ($snap['points'] ?? $answer->question?->default_points ?? 1), 2), '0'), '.') }} pts
                                            @endif
                                        </div>
                                    @endif
                                </div>
                                {{-- Frozen at attempt start (§14) so later Test Bank edits
                                     cannot rewrite this review. --}}
                                <div class="text-gray-700 mb-2">{{ $answer->renderedQuestionText() }}</div>
                                @if($answer->answer_text)
                                    @php
                                        $picked = $answer->answer_text;
                                        // Multiple-choice submissions store the choice id;
                                        // recover the wording the student actually chose.
                                        $labelled = is_numeric($picked) && $snap
                                            ? ($answer->renderedChoices()->firstWhere('id', (int) $picked)['text'] ?? null)
                                        : null;
                                    @endphp
                                    <div class="text-sm text-gray-600">
                                        <span class="font-medium">Your Answer:</span>
                                        {{ $labelled ?? (is_array(json_decode($answer->answer_text)) ? implode(', ', json_decode($answer->answer_text)) : $answer->answer_text) }}
                                    </div>
                                @endif
                                @if($showAnswerKey && $correctAnswers !== [])
                                    <div class="text-sm text-green-700 mt-2">
                                        <span class="font-medium">Correct Answer:</span>
                                        {{ implode(', ', $correctAnswers) }}
                                    </div>
                                @elseif($showAnswerKey && $qtype === 'essay')
                                    <div class="text-sm text-gray-600 mt-2">
                                        <span class="font-medium">Correct Answer:</span> graded by the instructor
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-start">
                        <i class="fa-solid fa-eye-slash text-gray-500 mr-3 mt-1 text-xl"></i>
                        <div>
                            <div class="font-semibold text-gray-900">Review not available</div>
                            <div class="text-sm text-gray-600">Your instructor has not enabled answer review for this exam.</div>
                        </div>
                    </div>
                </div>
            @endif
        @endif
    </div>

    {{-- The attempt is stored server-side now, so the browser safety copy is
         obsolete. Clearing it here - not on the attempt page - is what makes a
         cancelled submit confirmation harmless: nothing is removed until the
         student has actually landed on the submitted results. --}}
    @if($attempt->submitted_at)
        <script>
            try {
                localStorage.removeItem('examAnswers_{{ $attempt->id }}');
            } catch (e) {
                // Storage may be blocked; the attempt itself is already stored.
            }
        </script>
    @endif
@endsection
