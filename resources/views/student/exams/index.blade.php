@extends('layouts.student')

@section('title', 'Exams')
@php
    $activeNav = 'exams';
    $pageTitle = 'Exams';
    $pageIcon = '<i class="fa-solid fa-file-alt"></i>';
@endphp

@section('content')
    <div class="container py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Exams</h1>
            <p class="text-gray-600">Course: {{ $course->title }}</p>
        </div>

        @if($exams->count() === 0)
            <div class="bg-white rounded-lg shadow-sm p-8 text-center">
                <i class="fa-solid fa-file-alt text-gray-300 text-6xl mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-700 mb-2">No Exams Available</h3>
                <p class="text-gray-500">There are no exams scheduled for this course yet.</p>
            </div>
        @else
            <div class="grid gap-6">
                @foreach($exams as $exam)
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ $exam->title }}</h3>
                                <p class="text-gray-600 mb-3">{{ $exam->description ?? 'No description provided' }}</p>
                            </div>
                            @if($exam->status === 'published')
                                <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">
                                    Published
                                </span>
                            @else
                                <span class="bg-gray-100 text-gray-800 px-3 py-1 rounded-full text-sm font-medium">
                                    {{ ucfirst($exam->status) }}
                                </span>
                            @endif
                        </div>

                        <div class="flex flex-wrap gap-4 text-sm text-gray-600 mb-4">
                            @if($exam->duration_minutes)
                                <div>
                                    <i class="fa-solid fa-clock mr-1"></i>
                                    {{ $exam->duration_minutes }} minutes
                                </div>
                            @endif
                            @if($exam->attempt_limit)
                                <div>
                                    <i class="fa-solid fa-redo mr-1"></i>
                                    {{ $exam->attempt_limit }} attempt(s)
                                </div>
                            @endif
                            @if($exam->passing_score_percent)
                                <div>
                                    <i class="fa-solid fa-check-circle mr-1"></i>
                                    {{ $exam->passing_score_percent }}% to pass
                                </div>
                            @endif
                        </div>

                        <div class="flex justify-between items-center pt-4 border-t border-gray-200">
                            <div class="text-sm text-gray-500">
                                @if($exam->availability_from && $exam->availability_until)
                                    Available: {{ $exam->availability_from->format('M d, Y') }} - {{ $exam->availability_until->format('M d, Y') }}
                                @elseif($exam->availability_from)
                                    Available from: {{ $exam->availability_from->format('M d, Y') }}
                                @elseif($exam->availability_until)
                                    Available until: {{ $exam->availability_until->format('M d, Y') }}
                                @else
                                    No availability restrictions
                                @endif
                            </div>
                            <a href="{{ route('student.courses.exams.show', [$course, $exam]) }}"
                               class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors font-medium">
                                View Exam
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            {{ $exams->links() }}
        @endif
    </div>
@endsection
