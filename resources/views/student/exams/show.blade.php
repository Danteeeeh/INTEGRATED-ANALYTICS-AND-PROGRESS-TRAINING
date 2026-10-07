@extends('layouts.student')

@section('title', 'Exam Details')
@php
    $activeNav = 'exams';
    $pageTitle = 'Exam Details';
    $pageIcon = '<i class="fa-solid fa-file-alt"></i>';
@endphp

@section('content')
    <div class="container py-8">
        <div class="mb-8">
            <a href="{{ route('student.courses.exams.index', $course) }}" class="text-blue-600 hover:text-blue-800 mb-4 inline-block">
                <i class="fa-solid fa-arrow-left mr-2"></i> Back to Exams
            </a>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ $exam->title }}</h1>
            <p class="text-gray-600">Course: {{ $course->title }}</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <div class="flex justify-between items-start mb-4">
                <div>
                    @if($exam->status === 'published')
                        <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium mb-2 inline-block">
                            Published
                        </span>
                    @else
                        <span class="bg-gray-100 text-gray-800 px-3 py-1 rounded-full text-sm font-medium mb-2 inline-block">
                            {{ ucfirst($exam->status) }}
                        </span>
                    @endif
                </div>
                @if($isOverdue)
                    <span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-sm font-medium">
                        Overdue
                    </span>
                @endif
            </div>

            @if($exam->description)
                <p class="text-gray-700 mb-6">{{ $exam->description }}</p>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                @if($exam->duration_minutes)
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="text-sm text-gray-600 mb-1">Duration</div>
                        <div class="text-lg font-semibold text-gray-900">{{ $exam->duration_minutes }} minutes</div>
                    </div>
                @endif
                @if($exam->attempt_limit)
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="text-sm text-gray-600 mb-1">Attempt Limit</div>
                        <div class="text-lg font-semibold text-gray-900">{{ $exam->attempt_limit }} attempt(s)</div>
                    </div>
                @endif
                @if($exam->passing_score_percent)
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="text-sm text-gray-600 mb-1">Passing Score</div>
                        <div class="text-lg font-semibold text-gray-900">{{ $exam->passing_score_percent }}%</div>
                    </div>
                @endif
                @if($exam->questions_count ?? $exam->questions()->count())
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <div class="text-sm text-gray-600 mb-1">Questions</div>
                        <div class="text-lg font-semibold text-gray-900">{{ $exam->questions_count ?? $exam->questions()->count() }}</div>
                    </div>
                @endif
            </div>

            @if($effectiveDeadline)
                <div class="bg-yellow-50 border border-yellow-200 p-4 rounded-lg mb-6">
                    <div class="flex items-center">
                        <i class="fa-solid fa-clock text-yellow-600 mr-2"></i>
                        <div>
                            <div class="font-semibold text-yellow-800">Deadline</div>
                            <div class="text-sm text-yellow-700">{{ $effectiveDeadline->format('F d, Y g:i A') }}</div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Previous Attempts -->
        @if($myAttempts->count() > 0)
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Your Attempts</h2>
                <div class="space-y-3">
                    @foreach($myAttempts as $attempt)
                        <div class="flex justify-between items-center p-4 bg-gray-50 rounded-lg">
                            <div>
                                <div class="font-medium text-gray-900">Attempt #{{ $attempt->attempt_number }}</div>
                                <div class="text-sm text-gray-600">
                                    {{ $attempt->submitted_at ? $attempt->submitted_at->format('M d, Y g:i A') : 'In progress' }}
                                </div>
                            </div>
                            <div class="text-right">
                                @if($attempt->status === 'submitted' || $attempt->status === 'auto_submitted')
                                    <div class="text-lg font-semibold {{ $attempt->is_passed ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $attempt->score_percent }}%
                                    </div>
                                    <div class="text-sm text-gray-600">
                                        {{ $attempt->is_passed ? 'Passed' : 'Failed' }}
                                    </div>
                                @else
                                    <div class="text-sm text-gray-600">{{ ucfirst($attempt->status) }}</div>
                                @endif
                            </div>
                            <a href="{{ route('student.courses.exams.attempts.show', [$course, $exam, $attempt]) }}"
                               class="text-blue-600 hover:text-blue-800 ml-4">
                                View Details
                            </a>
                        </div>
                    @endforeach
                </div>
                {{ $myAttempts->links() }}
            </div>
        @endif

        <!-- Action Buttons -->
        <div class="flex gap-4">
            @php $hasQuestions = $exam->questions->isNotEmpty(); @endphp

            @unless($hasQuestions)
                {{-- An exam with nothing on it cannot be started, and the
                     controller refuses it — so the button must not be offered. --}}
                <button disabled class="bg-gray-400 text-white px-6 py-3 rounded-lg cursor-not-allowed font-medium">
                    <i class="fa-solid fa-hourglass-half mr-2"></i> No Questions Yet
                </button>
            @elseif($inProgressAttempt)
                <a href="{{ route('student.courses.exams.attempt.start', [$course, $exam]) }}"
                   class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                    <i class="fa-solid fa-play mr-2"></i> Continue Attempt
                </a>
            @elseif($exam->available() && !$isOverdue)
                @if($exam->attempt_limit && $myAttempts->count() >= $exam->attempt_limit)
                    <button disabled class="bg-gray-400 text-white px-6 py-3 rounded-lg cursor-not-allowed font-medium">
                        <i class="fa-solid fa-ban mr-2"></i> Attempt Limit Reached
                    </button>
                @else
                    <a href="{{ route('student.courses.exams.confirm', [$course, $exam]) }}"
                       class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition-colors font-medium">
                        <i class="fa-solid fa-play mr-2"></i> Start Exam
                    </a>
                @endif
            @elseif($isOverdue)
                <button disabled class="bg-gray-400 text-white px-6 py-3 rounded-lg cursor-not-allowed font-medium">
                    <i class="fa-solid fa-clock mr-2"></i> Exam Overdue
                </button>
            @else
                <button disabled class="bg-gray-400 text-white px-6 py-3 rounded-lg cursor-not-allowed font-medium">
                    <i class="fa-solid fa-clock mr-2"></i> Not Available
                </button>
            @endif
        </div>
    </div>
@endsection
