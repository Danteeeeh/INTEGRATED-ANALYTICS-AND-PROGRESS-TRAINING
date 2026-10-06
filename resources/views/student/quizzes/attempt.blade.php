@extends('layouts.student')

@section('title', 'Quiz Attempt')
@php
    $activeNav = 'quizzes';
    $pageTitle = 'Taking Quiz';
    $pageIcon = '<i class="fa-solid fa-edit"></i>';
@endphp

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-edit"></i>
            {{ $quiz->title }} - Attempt {{ $inProgress->attempt_number }}
        </h2>
    </div>
@endsection

@section('content')
    <style>
        .quiz-container {
            transition: all 0.3s ease;
            max-width: 1200px;
            margin: 0 auto;
        }

        .quiz-container.focus-mode {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 9999;
            background: #f8fafc;
            overflow-y: auto;
            padding: 20px;
            max-width: none;
        }

        .quiz-container.focus-mode .focus-btn {
            display: block;
        }

        .focus-btn {
            display: none;
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            background: #ef4444;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .focus-btn:hover {
            background: #dc2626;
        }

        .timer-circle {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 700;
            color: white;
            transition: all 0.3s ease;
            position: relative;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .timer-circle.warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            animation: pulse 1s infinite;
        }

        .timer-circle.danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            animation: pulse 0.5s infinite;
        }

        .timer-circle.normal {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        @keyframes slideDown {
            from { transform: translateX(-50%) translateY(-100%); opacity: 0; }
            to { transform: translateX(-50%) translateY(0); opacity: 1; }
        }

        @keyframes slideUp {
            from { transform: translateX(-50%) translateY(0); opacity: 1; }
            to { transform: translateX(-50%) translateY(-100%); opacity: 0; }
        }

        .timer-progress-bar {
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 10px;
        }

        .timer-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981, #059669);
            transition: width 1s linear, background 0.3s ease;
        }

        .timer-progress-fill.warning {
            background: linear-gradient(90deg, #f59e0b, #d97706);
        }

        .timer-progress-fill.danger {
            background: linear-gradient(90deg, #ef4444, #dc2626);
        }

        .choice-label {
            position: relative;
        }

        .choice-label:hover {
            border-color: #3b82f6 !important;
            background: #f8fafc !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15) !important;
        }

        .choice-label input:checked + span {
            color: #1e293b;
            font-weight: 600;
        }

        .choice-label input:checked {
            accent-color: #3b82f6;
        }

        .question-card {
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            .timer-circle {
                width: 70px;
                height: 70px;
                font-size: 1.5rem;
            }
        }
    </style>

    <div class="quiz-container" id="quizContainer">
        <!-- Focus Mode Button -->
        <button type="button" class="focus-btn" onclick="toggleFocusMode()">
            <i class="fa-solid fa-compress"></i> Exit Focus Mode
        </button>

        <!-- Quiz Info Bar -->
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 28px; border-radius: 20px; color: white; margin-bottom: 28px; box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 24px;">
                <div style="flex: 1; min-width: 250px;">
                    <div style="font-size: 1.5rem; font-weight: 800; margin-bottom: 8px; line-height: 1.3;">{{ $quiz->title }}</div>
                    <div style="font-size: 1rem; opacity: 0.95; font-weight: 500;">
                        <i class="fa-solid fa-list-ol" style="margin-right: 6px;"></i>
                        {{ $inProgress->answers->count() }} Questions
                        <span style="margin: 0 8px; opacity: 0.5;">•</span>
                        <i class="fa-solid fa-redo" style="margin-right: 6px;"></i>
                        Attempt {{ $inProgress->attempt_number }}
                    </div>
                </div>

                @if($quiz->time_limit_minutes)
                    <div style="text-align: center; min-width: 140px;">
                        <div style="font-size: 0.9rem; opacity: 0.95; margin-bottom: 8px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Time Remaining</div>
                        <div class="timer-circle normal" id="timerCircle">{{ $quiz->time_limit_minutes }}:00</div>
                        <div class="timer-progress-bar">
                            <div class="timer-progress-fill" id="timerProgress" style="width: 100%;"></div>
                        </div>
                    </div>
                @endif

                <div style="text-align: center; min-width: 120px;">
                    <div style="font-size: 0.9rem; opacity: 0.95; margin-bottom: 8px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Progress</div>
                    <div id="progress" style="font-size: 2rem; font-weight: 800; line-height: 1;">0/{{ $inProgress->answers->count() }}</div>
                    <div style="font-size: 0.85rem; opacity: 0.9; margin-top: 6px; font-weight: 500;">
                        {{ $inProgress->answers->count() > 0 ? round((0 / $inProgress->answers->count()) * 100) : 0 }}&percnt; Complete
                    </div>
                </div>

                <button type="button" onclick="toggleFocusMode()"
                        style="background: rgba(255,255,255,0.25); border: 2px solid rgba(255,255,255,0.4); color: white; padding: 14px 24px; border-radius: 10px; cursor: pointer; font-weight: 700; font-size: 0.95rem; transition: all 0.2s; backdrop-filter: blur(10px);">
                    <i class="fa-solid fa-expand" style="margin-right: 8px;"></i> Focus Mode
                </button>
            </div>
        </div>

    <!-- Question Navigation -->
    <div class="form-card" style="margin-bottom: 28px; background: white; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); padding: 28px; border: 1px solid #e2e8f0;">
        <h3 style="margin: 0 0 20px 0; color: #1e293b; font-size: 1.25rem; font-weight: 700; display: flex; align-items: center;">
            <i class="fa-solid fa-list-ol" style="color: #3b82f6; margin-right: 10px; font-size: 1.1rem;"></i>
            Question Navigation
        </h3>
        <div id="questionNav" style="display: flex; flex-wrap: wrap; gap: 12px; margin-top: 20px;">
            @foreach($inProgress->answers as $index => $answer)
                <button type="button"
                        onclick="goToQuestion({{ $index }})"
                        class="question-nav-btn"
                        data-question="{{ $index }}"
                        style="width: 48px; height: 48px; border: 2px solid #e2e8f0; border-radius: 12px; background: white; cursor: pointer; font-weight: 700; color: #64748b; transition: all 0.25s ease; font-size: 1.1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                    {{ $index + 1 }}
                </button>
            @endforeach
        </div>
        <div style="display: flex; gap: 24px; margin-top: 20px; font-size: 0.9rem; color: #64748b; flex-wrap: wrap; padding-top: 20px; border-top: 1px solid #f1f5f9;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 24px; height: 24px; background: linear-gradient(135deg, #3b82f6, #2563eb); border-radius: 8px; box-shadow: 0 2px 4px rgba(59, 130, 246, 0.3);"></div>
                <span style="font-weight: 600; color: #1e293b;">Current</span>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 24px; height: 24px; background: linear-gradient(135deg, #22c55e, #16a34a); border-radius: 8px; box-shadow: 0 2px 4px rgba(34, 197, 94, 0.3);"></div>
                <span style="font-weight: 600; color: #1e293b;">Answered</span>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="width: 24px; height: 24px; background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 8px;"></div>
                <span style="font-weight: 600; color: #1e293b;">Unanswered</span>
            </div>
        </div>
    </div>

    <!-- Quiz Form -->
    <form action="{{ route('student.courses.quizzes.attempt.store', [$course, $quiz]) }}"
          method="POST" id="quizForm">
        @csrf
        <input type="hidden" name="_token" value="{{ csrf_token() }}">

        @foreach($inProgress->answers as $index => $quizAnswer)
            <div class="question-card" id="question-{{ $index }}"
                 style="display: {{ $index === 0 ? 'block' : 'none' }}; margin-bottom: 24px;">

                <div class="form-card" style="background: white; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); padding: 32px; border: 1px solid #e2e8f0;">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
                        <h3 style="margin: 0; display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
                            <span style="background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; padding: 10px 20px; border-radius: 30px; font-size: 0.95rem; font-weight: 700; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);">
                                Question {{ $index + 1 }}
                            </span>
                            <span style="background: #f1f5f9; color: #64748b; padding: 8px 16px; border-radius: 20px; font-size: 0.9rem; font-weight: 600; border: 1px solid #e2e8f0;">
                                {{ ($quizAnswer->question->question_type === 'multiple_choice' ? 'Multiple Choice' :
                               ($quizAnswer->question->question_type === 'true_false' ? 'True/False' :
                               ($quizAnswer->question->question_type === 'multiple_answer' ? 'Multiple Answer' :
                               ($quizAnswer->question->question_type === 'short_answer' ? 'Short Answer' :
                               ($quizAnswer->question->question_type === 'essay' ? 'Essay' : 'Question'))))) }}
                            </span>
                        </h3>
                        <span style="background: linear-gradient(135deg, #fef3c7, #fde68a); color: #d97706; padding: 8px 16px; border-radius: 20px; font-size: 0.9rem; font-weight: 700; box-shadow: 0 2px 8px rgba(217, 119, 6, 0.2);">
                            <i class="fa-solid fa-star" style="margin-right: 6px;"></i>
                            {{ $quizAnswer->question->quizzes->find($quiz->id)?->pivot->points ?? $quizAnswer->question->default_points ?? 1 }} pts
                        </span>
                    </div>

                    <div style="font-size: 1.25rem; color: #1e293b; margin-bottom: 28px; line-height: 1.8; font-weight: 600; padding: 20px; background: #f8fafc; border-radius: 16px; border-left: 4px solid #3b82f6;">
                        {!! $quizAnswer->question->question_text !!}
                    </div>

                    @if($quizAnswer->question->question_type === 'multiple_choice' || $quizAnswer->question->question_type === 'true_false')
                        <div style="display: flex; flex-direction: column; gap: 16px;">
                            @foreach($quizAnswer->question->choices as $choice)
                                <label class="choice-label"
                                       style="display: flex; align-items: center; padding: 20px 24px; border: 2px solid #e2e8f0; border-radius: 14px; cursor: pointer; transition: all 0.3s ease; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
                                    <input type="radio"
                                           name="answers[{{ $quizAnswer->question->id }}]"
                                           value="{{ $choice->id }}"
                                           {{ old('answers.'.$quizAnswer->question->id) == $choice->id ? 'checked' : '' }}
                                           onchange="markAnswered({{ $index }})"
                                           style="width: 24px; height: 24px; margin-right: 16px; accent-color: #3b82f6; cursor: pointer;">
                                    <span style="flex: 1; font-size: 1.05rem; color: #334155; line-height: 1.6;">{!! $choice->choice_text !!}</span>
                                </label>
                            @endforeach
                        </div>

                    @elseif($quizAnswer->question->question_type === 'multiple_answer')
                        <div style="display: flex; flex-direction: column; gap: 16px;">
                            @foreach($quizAnswer->question->choices as $choice)
                                <label class="choice-label"
                                       style="display: flex; align-items: center; padding: 20px 24px; border: 2px solid #e2e8f0; border-radius: 14px; cursor: pointer; transition: all 0.3s ease; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
                                    <input type="checkbox"
                                           name="answers[{{ $quizAnswer->question->id }}][]"
                                           value="{{ $choice->id }}"
                                           {{ in_array($choice->id, old('answers.'.$quizAnswer->question->id, [])) ? 'checked' : '' }}
                                           onchange="markAnswered({{ $index }})"
                                           style="width: 24px; height: 24px; margin-right: 16px; accent-color: #3b82f6; cursor: pointer;">
                                    <span style="flex: 1; font-size: 1.05rem; color: #334155; line-height: 1.6;">{!! $choice->choice_text !!}</span>
                                </label>
                            @endforeach
                        </div>

                    @elseif($quizAnswer->question->question_type === 'short_answer' || $quizAnswer->question->question_type === 'essay')
                        <div>
                            <textarea name="answers[{{ $quizAnswer->question->id }}]"
                                      rows="{{ $quizAnswer->question->question_type === 'essay' ? '12' : '6' }}"
                                      placeholder="Enter your answer here..."
                                      oninput="markAnswered({{ $index }})"
                                      style="width: 100%; padding: 20px; border: 2px solid #e2e8f0; border-radius: 14px; font-family: inherit; font-size: 1.05rem; resize: vertical; transition: all 0.2s; line-height: 1.7; background: #f8fafc;">{{ old('answers.'.$quizAnswer->question->id) }}</textarea>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach

        <!-- Navigation Buttons -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 36px; gap: 20px;">
            <button type="button" id="prevBtn" onclick="prevQuestion()"
                    style="flex: 1; padding: 16px 32px; border-radius: 14px; cursor: pointer; background: #f1f5f9; color: #64748b; border: 2px solid #e2e8f0; font-weight: 700; font-size: 1.05rem; transition: all 0.2s; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                <i class="fa-solid fa-arrow-left" style="margin-right: 8px;"></i> Previous
            </button>

            <button type="button" id="nextBtn" onclick="nextQuestion()"
                    style="flex: 1; padding: 16px 32px; border-radius: 14px; cursor: pointer; background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; border: none; font-weight: 700; font-size: 1.05rem; transition: all 0.2s; box-shadow: 0 4px 16px rgba(59, 130, 246, 0.35); display: flex; align-items: center; justify-content: center;">
                Next <i class="fa-solid fa-arrow-right" style="margin-left: 8px;"></i>
            </button>
        </div>

        <!-- Submit Button -->
        <div style="text-align: center; margin-top: 40px; padding-top: 40px; border-top: 2px solid #e2e8f0;">
            <button type="submit" onclick="return confirmSubmit()"
                    style="padding: 18px 48px; border-radius: 14px; cursor: pointer; font-size: 1.15rem; font-weight: 800; background: linear-gradient(135deg, #22c55e, #16a34a); color: white; border: none; box-shadow: 0 6px 20px rgba(34, 197, 94, 0.35); transition: all 0.2s;">
                <i class="fa-solid fa-check-circle" style="margin-right: 10px;"></i> Submit Quiz
            </button>
            <div style="font-size: 0.95rem; color: #64748b; margin-top: 14px; font-weight: 600;">
                <i class="fa-solid fa-info-circle" style="margin-right: 6px;"></i>
                Make sure you have answered all questions before submitting
            </div>
        </div>
    </form>

    <!-- Auto-save Warning -->
    <div id="autoSaveWarning" style="display: none; position: fixed; bottom: 24px; right: 24px; background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 14px 24px; border-radius: 12px; box-shadow: 0 8px 24px rgba(16, 185, 129, 0.3); font-weight: 600; z-index: 10000;">
        <i class="fa-solid fa-save"></i> Answers auto-saved
    </div>

    <script>
        let currentQuestion = 0;
        let totalQuestions = {{ $inProgress->answers->count() }};
        let answeredQuestions = new Set();
        let timeRemaining = {{ $quiz->time_limit_minutes ? $quiz->time_limit_minutes * 60 : 0 }};
        let timerInterval;

        // Refresh CSRF token periodically (every 30 minutes)
        async function refreshCsrfToken() {
            try {
                const response = await fetch('/refresh-csrf', {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                if (response.ok) {
                    const data = await response.json();
                    document.querySelector('meta[name="csrf-token"]').setAttribute('content', data.token);
                    document.querySelector('input[name="_token"]').value = data.token;
                }
            } catch (error) {
                console.error('Failed to refresh CSRF token:', error);
            }
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            updateNavigation();
            startTimer();
            loadSavedAnswers();

            // Auto-save every 30 seconds
            setInterval(autoSave, 30000);

            // Refresh CSRF token every 30 minutes
            setInterval(refreshCsrfToken, 1800000);
        });

        function goToQuestion(index) {
            // Hide current question
            document.getElementById('question-' + currentQuestion).style.display = 'none';

            // Show new question
            currentQuestion = index;
            document.getElementById('question-' + currentQuestion).style.display = 'block';

            updateNavigation();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function nextQuestion() {
            if (currentQuestion < totalQuestions - 1) {
                goToQuestion(currentQuestion + 1);
            }
        }

        function prevQuestion() {
            if (currentQuestion > 0) {
                goToQuestion(currentQuestion - 1);
            }
        }

        function markAnswered(index) {
            answeredQuestions.add(index);
            updateNavigation();
            autoSave();
        }

        function updateNavigation() {
            // Update navigation buttons
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');

            prevBtn.disabled = currentQuestion === 0;
            prevBtn.style.opacity = currentQuestion === 0 ? '0.5' : '1';
            prevBtn.style.cursor = currentQuestion === 0 ? 'not-allowed' : 'pointer';

            nextBtn.style.display = currentQuestion === totalQuestions - 1 ? 'none' : 'flex';

            // Update question navigation buttons
            for (let i = 0; i < totalQuestions; i++) {
                const btn = document.querySelector(`[data-question="${i}"]`);
                if (btn) {
                    btn.style.borderColor = '#e2e8f0';
                    btn.style.background = 'white';
                    btn.style.color = '#64748b';
                    btn.style.boxShadow = 'none';

                    if (i === currentQuestion) {
                        btn.style.borderColor = '#3b82f6';
                        btn.style.background = 'linear-gradient(135deg, #3b82f6, #2563eb)';
                        btn.style.color = 'white';
                        btn.style.boxShadow = '0 4px 12px rgba(59, 130, 246, 0.3)';
                    } else if (answeredQuestions.has(i)) {
                        btn.style.borderColor = '#22c55e';
                        btn.style.background = 'linear-gradient(135deg, #22c55e, #16a34a)';
                        btn.style.color = 'white';
                        btn.style.boxShadow = '0 4px 12px rgba(34, 197, 94, 0.3)';
                    }
                }
            }

            // Update progress
            document.getElementById('progress').textContent = answeredQuestions.size + '/' + totalQuestions;
            const progressPercent = Math.round((answeredQuestions.size / totalQuestions) * 100);
            const progressText = document.querySelector('#progress').nextElementSibling;
            if (progressText) {
                progressText.textContent = progressPercent + '% Complete';
            }
        }

        function startTimer() {
            @if($quiz->time_limit_minutes)
                const totalTime = {{ $quiz->time_limit_minutes * 60 }};
                if (timeRemaining > 0) {
                    timerInterval = setInterval(function() {
                        timeRemaining--;

                        const minutes = Math.floor(timeRemaining / 60);
                        const seconds = timeRemaining % 60;
                        const timerCircle = document.getElementById('timerCircle');
                        const timerProgress = document.getElementById('timerProgress');

                        timerCircle.textContent = minutes.toString().padStart(2, '0') + ':' + seconds.toString().padStart(2, '0');

                        // Update progress bar
                        const progressPercent = (timeRemaining / totalTime) * 100;
                        timerProgress.style.width = progressPercent + '%';

                        // Timer color states
                        timerCircle.classList.remove('normal', 'warning', 'danger');
                        timerProgress.classList.remove('warning', 'danger');

                        if (timeRemaining <= 60) {
                            timerCircle.classList.add('danger');
                            timerProgress.classList.add('danger');
                        } else if (timeRemaining <= 300) {
                            timerCircle.classList.add('warning');
                            timerProgress.classList.add('warning');
                        } else {
                            timerCircle.classList.add('normal');
                        }

                        // Warning when 5 minutes remaining
                        if (timeRemaining === 300) {
                            showNotification('⚠️ You have 5 minutes remaining!', 'warning');
                        }

                        // Warning when 1 minute remaining
                        if (timeRemaining === 60) {
                            showNotification('⚠️ You have 1 minute remaining!', 'danger');
                        }

                        // Warning when 30 seconds remaining
                        if (timeRemaining === 30) {
                            showNotification('⚠️ Only 30 seconds left!', 'danger');
                        }

                        // Auto-submit when time expires
                        if (timeRemaining <= 0) {
                            clearInterval(timerInterval);
                            showNotification('⏰ Time has expired! Your answers will be auto-submitted.', 'danger');
                            setTimeout(() => {
                                document.getElementById('quizForm').submit();
                            }, 2000);
                        }
                    }, 1000);
                }
            @endif
        }

        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                left: 50%;
                transform: translateX(-50%);
                background: ${type === 'danger' ? '#ef4444' : type === 'warning' ? '#f59e0b' : '#10b981'};
                color: white;
                padding: 16px 24px;
                border-radius: 12px;
                font-weight: 600;
                box-shadow: 0 8px 24px rgba(0,0,0,0.2);
                z-index: 10001;
                animation: slideDown 0.3s ease;
            `;
            notification.textContent = message;
            document.body.appendChild(notification);

            setTimeout(() => {
                notification.style.animation = 'slideUp 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        function toggleFocusMode() {
            const container = document.getElementById('quizContainer');
            container.classList.toggle('focus-mode');

            if (container.classList.contains('focus-mode')) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        }

        function autoSave() {
            const formData = new FormData(document.getElementById('quizForm'));
            const answers = {};

            // Convert FormData to proper object handling arrays
            for (const [key, value] of formData.entries()) {
                if (key.endsWith('[]')) {
                    const cleanKey = key.slice(0, -2);
                    if (!answers[cleanKey]) {
                        answers[cleanKey] = [];
                    }
                    answers[cleanKey].push(value);
                } else {
                    answers[key] = value;
                }
            }

            localStorage.setItem('quizAnswers_' + {{ $inProgress->id }}, JSON.stringify(answers));

            // Show auto-save notification
            const warning = document.getElementById('autoSaveWarning');
            warning.style.display = 'block';
            setTimeout(() => {
                warning.style.display = 'none';
            }, 2000);
        }

        function loadSavedAnswers() {
            const saved = localStorage.getItem('quizAnswers_' + {{ $inProgress->id }});
            if (saved) {
                const answers = JSON.parse(saved);
                for (const [key, value] of Object.entries(answers)) {
                    const elements = document.querySelectorAll(`[name="${key}"]`);
                    elements.forEach(el => {
                        if (el.type === 'radio') {
                            el.checked = el.value == value;
                        } else if (el.type === 'checkbox') {
                            el.checked = Array.isArray(value) ? value.includes(el.value) : el.value == value;
                        } else {
                            el.value = value;
                        }
                    });
                }
            }
        }

        function confirmSubmit() {
            const unanswered = totalQuestions - answeredQuestions.size;
            if (unanswered > 0) {
                return confirm(`You have ${unanswered} unanswered question(s). Are you sure you want to submit?`);
            }
            return confirm('Are you sure you want to submit your quiz? This action cannot be undone.');
        }

        // Prevent auto-save from interfering with form submission
        document.getElementById('quizForm').addEventListener('submit', function(e) {
            // Remove localStorage before submitting to avoid conflicts
            localStorage.removeItem('quizAnswers_' + {{ $inProgress->id }});
        });

        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowRight' && !e.ctrlKey) {
                e.preventDefault();
                nextQuestion();
            } else if (e.key === 'ArrowLeft' && !e.ctrlKey) {
                e.preventDefault();
                prevQuestion();
            }
        });

        // Style radio/checkbox selections
        document.querySelectorAll('.choice-label').forEach(label => {
            label.addEventListener('mouseenter', function() {
                if (!this.querySelector('input').checked) {
                    this.style.borderColor = '#3b82f6';
                    this.style.background = '#eff6ff';
                }
            });

            label.addEventListener('mouseleave', function() {
                if (!this.querySelector('input').checked) {
                    this.style.borderColor = '#e2e8f0';
                    this.style.background = 'white';
                }
            });
        });

        document.querySelectorAll('.choice-label input').forEach(input => {
            input.addEventListener('change', function() {
                const parent = this.closest('.choice-label');
                const allLabels = document.querySelectorAll('.choice-label');

                if (this.type === 'radio') {
                    allLabels.forEach(label => {
                        label.style.borderColor = '#e2e8f0';
                        label.style.background = 'white';
                        label.style.boxShadow = '0 2px 4px rgba(0,0,0,0.02)';
                    });
                }

                if (this.checked) {
                    parent.style.borderColor = '#3b82f6';
                    parent.style.background = 'linear-gradient(135deg, #eff6ff, #dbeafe)';
                    parent.style.boxShadow = '0 4px 12px rgba(59, 130, 246, 0.2)';
                }
            });
        });
    </script>
@endsection