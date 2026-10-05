@extends('layouts.student')

@section('title', 'Confirm Exam Start')
@php
    $activeNav = 'exams';
    $pageTitle = 'Confirm Exam Start';
    $pageIcon = '<i class="fa-solid fa-file-alt"></i>';
@endphp

@section('content')
    <div class="container py-8 max-w-2xl">
        <div class="mb-8">
            <a href="{{ route('student.courses.exams.show', [$course, $exam]) }}" class="text-blue-600 hover:text-blue-800 mb-4 inline-block">
                <i class="fa-solid fa-arrow-left mr-2"></i> Back to Exam
            </a>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Start Exam</h1>
            <p class="text-gray-600">Please review the exam details before starting</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">{{ $exam->title }}</h2>

            @if($exam->description)
                <p class="text-gray-700 mb-6">{{ $exam->description }}</p>
            @endif

            <div class="space-y-4 mb-6">
                @if($exam->duration_minutes)
                    <div class="flex items-center p-4 bg-blue-50 rounded-lg">
                        <i class="fa-solid fa-clock text-blue-600 mr-3 text-xl"></i>
                        <div>
                            <div class="font-semibold text-gray-900">Time Limit</div>
                            <div class="text-gray-600">{{ $exam->duration_minutes }} minutes</div>
                        </div>
                    </div>
                @endif

                @if($exam->questions_count ?? $exam->questions()->count())
                    <div class="flex items-center p-4 bg-blue-50 rounded-lg">
                        <i class="fa-solid fa-list-ol text-blue-600 mr-3 text-xl"></i>
                        <div>
                            <div class="font-semibold text-gray-900">Number of Questions</div>
                            <div class="text-gray-600">{{ $exam->questions_count ?? $exam->questions()->count() }}</div>
                        </div>
                    </div>
                @endif

                @if($exam->attempt_limit)
                    <div class="flex items-center p-4 bg-blue-50 rounded-lg">
                        <i class="fa-solid fa-redo text-blue-600 mr-3 text-xl"></i>
                        <div>
                            <div class="font-semibold text-gray-900">Attempts Allowed</div>
                            <div class="text-gray-600">{{ $exam->attempt_limit }} attempt(s)</div>
                        </div>
                    </div>
                @endif

                @if($exam->passing_score_percent)
                    <div class="flex items-center p-4 bg-blue-50 rounded-lg">
                        <i class="fa-solid fa-check-circle text-blue-600 mr-3 text-xl"></i>
                        <div>
                            <div class="font-semibold text-gray-900">Passing Score</div>
                            <div class="text-gray-600">{{ $exam->passing_score_percent }}%</div>
                        </div>
                    </div>
                @endif
            </div>

            @if($exam->auto_submit_on_timeout)
                <div class="bg-yellow-50 border border-yellow-200 p-4 rounded-lg mb-6">
                    <div class="flex items-start">
                        <i class="fa-solid fa-exclamation-triangle text-yellow-600 mr-2 mt-1"></i>
                        <div>
                            <div class="font-semibold text-yellow-800">Auto-Submit Enabled</div>
                            <div class="text-sm text-yellow-700">Your answers will be automatically submitted when the time expires.</div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="bg-red-50 border border-red-200 p-4 rounded-lg mb-6">
            <div class="flex items-start">
                <i class="fa-solid fa-exclamation-circle text-red-600 mr-2 mt-1"></i>
                <div>
                    <div class="font-semibold text-red-800">Important</div>
                    <ul class="text-sm text-red-700 mt-2 space-y-1">
                        <li>• Once you start, the timer will begin immediately</li>
                        <li>• You cannot pause or restart the exam</li>
                        <li>• Make sure you have a stable internet connection</li>
                        <li>• Do not refresh the page during the exam</li>
                    </ul>
                </div>
            </div>
        </div>

        <form action="{{ route('student.courses.exams.attempt.start', [$course, $exam]) }}" method="POST">
            @csrf
            <div class="flex gap-4">
                <a href="{{ route('student.courses.exams.show', [$course, $exam]) }}"
                   class="flex-1 bg-gray-200 text-gray-800 px-6 py-3 rounded-lg hover:bg-gray-300 transition-colors font-medium text-center">
                    Cancel
                </a>
                <button type="submit"
                        class="flex-1 bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition-colors font-medium">
                    <i class="fa-solid fa-play mr-2"></i> Start Exam
                </button>
            </div>
        </form>
    </div>
@endsection
