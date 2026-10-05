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
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            font-weight: 700;
            color: white;
            transition: all 0.3s ease;
            position: relative;
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
            height: 6px;
            background: #e2e8f0;
            border-radius: 3px;
            overflow: hidden;
            margin-top: 8px;
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
    </style>

    <div class="quiz-container" id="quizContainer">
        <!-- Focus Mode Button -->
        <button type="button" class="focus-btn" onclick="toggleFocusMode()">
            <i class="fa-solid fa-compress"></i> Exit Focus Mode
        </button>

        <!-- Quiz Info Bar -->
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 24px; border-radius: 16px; color: white; margin-bottom: 24px; box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
                <div style="flex: 1; min-width: 200px;">
                    <div style="font-size: 1.3rem; font-weight: 700; margin-bottom: 6px;">{{ $quiz->title }}</div>
                    <div style="font-size: 0.95rem; opacity: 0.9;">{{ $inProgress->answers->count() }} Questions • Attempt {{ $inProgress->attempt_number }}</div>
                </div>

                @if($quiz->time_limit_minutes)
                    <div style="text-align: center;">
                        <div style="font-size: 0.85rem; opacity: 0.9; margin-bottom: 4px;">Time Remaining</div>
                        <div class="timer-circle normal" id="timerCircle">{{ $quiz->time_limit_minutes }}:00</div>
                        <div class="timer-progress-bar">
                            <div class="timer-progress-fill" id="timerProgress" style="width: 100%;"></div>
                        </div>
                    </div>
                @endif

                <div style="text-align: center;">
                    <div style="font-size: 0.85rem; opacity: 0.9; margin-bottom: 4px;">Progress</div>
                    <div id="progress" style="font-size: 1.8rem; font-weight: 700;">0/{{ $inProgress->answers->count() }}</div>
                    <div style="font-size: 0.8rem; opacity: 0.8; margin-top: 4px;">
                        {{ round((0 / $inProgress->answers->count()) * 100) }}% Complete
                    </div>
                </div>

                <button type="button" onclick="toggleFocusMode()"
                        style="background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.3); color: white; padding: 12px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.2s;">
                    <i class="fa-solid fa-expand"></i> Focus Mode
                </button>
            </div>
        </div>

    <!-- Question Navigation -->
    <div class="form-card" style="margin-bottom: 24px; background: white; border-radius: 16px; box-shadow: 0 4px 16px rgba(0,0,0,0.08); padding: 24px;">
        <h3 style="margin: 0 0 16px 0; color: #1e293b; font-size: 1.1rem;"><i class="fa-solid fa-list-ol" style="color: #3b82f6; margin-right: 8px;"></i> Question Navigation</h3>
        <div id="questionNav" style="display: flex; flex-wrap: wrap; gap: 10px; margin-top: 16px;">
            @foreach($inProgress->answers as $index => $answer)
                <button type="button"
                        onclick="goToQuestion({{ $index }})"
                        class="question-nav-btn"
                        data-question="{{ $index }}"
                        style="width: 44px; height: 44px; border: 2px solid #e2e8f0; border-radius: 10px; background: white; cursor: pointer; font-weight: 600; color: #64748b; transition: all 0.25s ease; font-size: 1rem;">
                    {{ $index + 1 }}
                </button>
            @endforeach
        </div>
        <div style="display: flex; gap: 20px; margin-top: 16px; font-size: 0.85rem; color: #64748b; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 6px;">
                <div style="width: 20px; height: 20px; background: linear-gradient(135deg, #3b82f6, #2563eb); border-radius: 6px;"></div>
                <span style="font-weight: 500;">Current</span>
            </div>
            <div style="display: flex; align-items: center; gap: 6px;">
                <div style="width: 20px; height: 20px; background: linear-gradient(135deg, #22c55e, #16a34a); border-radius: 6px;"></div>
                <span style="font-weight: 500;">Answered</span>
            </div>
            <div style="display: flex; align-items: center; gap: 6px;">
                <div style="width: 20px; height: 20px; background: #f1f5f9; border: 2px solid #e2e8f0; border-radius: 6px;"></div>
                <span style="font-weight: 500;">Unanswered</span>
            </div>
        </div>
    </div>

    <!-- Quiz Form -->
    <form action="{{ route('student.courses.quizzes.attempt.store', [$course, $quiz]) }}"
          method="POST" id="quizForm">
        @csrf

        @foreach($inProgress->answers as $index => $quizAnswer)
            <div class="question-card" id="question-{{ $index }}"
                 style="display: {{ $index === 0 ? 'block' : 'none' }}; margin-bottom: 24px;">

                <div class="form-card" style="background: white; border-radius: 16px; box-shadow: 0 4px 16px rgba(0,0,0,0.08); padding: 28px;">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
                        <h3 style="margin: 0; display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                            <span style="background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; padding: 8px 16px; border-radius: 24px; font-size: 0.9rem; font-weight: 600;">
                                Question {{ $index + 1 }}
                            </span>
                            <span style="background: #f1f5f9; color: #64748b; padding: 6px 14px; border-radius: 16px; font-size: 0.85rem; font-weight: 500;">
                                {{ ($quizAnswer->question->question_type === 'multiple_choice' ? 'Multiple Choice' :
                               ($quizAnswer->question->question_type === 'true_false' ? 'True/False' :
                               ($quizAnswer->question->question_type === 'multiple_answer' ? 'Multiple Answer' :
                               ($quizAnswer->question->question_type === 'short_answer' ? 'Short Answer' :
                               ($quizAnswer->question->question_type === 'essay' ? 'Essay' : 'Question'))))) }}
                            </span>
                        </h3>
                        <span style="background: linear-gradient(135deg, #fef3c7, #fde68a); color: #d97706; padding: 6px 14px; border-radius: 16px; font-size: 0.85rem; font-weight: 600;">
                            {{ $quizAnswer->question->quizzes->find($quiz->id)?->pivot->points ?? $quizAnswer->question->default_points ?? 1 }} pts
                        </span>
                    </div>

                    <div style="font-size: 1.15rem; color: #1e293b; margin-bottom: 24px; line-height: 1.7; font-weight: 500;">
                        {!! $quizAnswer->question->question_text !!}
                    </div>

                    @if($quizAnswer->question->question_type === 'multiple_choice' || $quizAnswer->question->question_type === 'true_false')
                        <div style="display: flex; flex-direction: column; gap: 14px;">
                            @foreach($quizAnswer->question->choices as $choice)
                                <label class="choice-label"
                                       style="display: flex; align-items: center; padding: 18px 20px; border: 2px solid #e2e8f0; border-radius: 12px; cursor: pointer; transition: all 0.25s ease; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                                    <input type="radio"
                                           name="answers[{{ $quizAnswer->question->id }}]"
                                           value="{{ $choice->id }}"
                                           {{ old('answers.'.$quizAnswer->question->id) == $choice->id ? 'checked' : '' }}
                                           onchange="markAnswered({{ $index }})"
                                           style="width: 22px; height: 22px; margin-right: 14px; accent-color: #3b82f6; cursor: pointer;">
                                    <span style="flex: 1; font-size: 1rem; color: #334155;">{!! $choice->choice_text !!}</span>
                                </label>
                            @endforeach
                        </div>

                    @elseif($quizAnswer->question->question_type === 'multiple_answer')
                        <div style="display: flex; flex-direction: column; gap: 14px;">
                            @foreach($quizAnswer->question->choices as $choice)
                                <label class="choice-label"
                                       style="display: flex; align-items: center; padding: 18px 20px; border: 2px solid #e2e8f0; border-radius: 12px; cursor: pointer; transition: all 0.25s ease; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                                    <input type="checkbox"
                                           name="answers[{{ $quizAnswer->question->id }}][]"
                                           value="{{ $choice->id }}"
                                           {{ in_array($choice->id, old('answers.'.$quizAnswer->question->id, [])) ? 'checked' : '' }}
                                           onchange="markAnswered({{ $index }})"
                                           style="width: 22px; height: 22px; margin-right: 14px; accent-color: #3b82f6; cursor: pointer;">
                                    <span style="flex: 1; font-size: 1rem; color: #334155;">{!! $choice->choice_text !!}</span>
                                </label>
                            @endforeach
                        </div>

                    @elseif($quizAnswer->question->question_type === 'short_answer' || $quizAnswer->question->question_type === 'essay')
                        <div>
                            <textarea name="answers[{{ $quizAnswer->question->id }}]"
                                      rows="{{ $quizAnswer->question->question_type === 'essay' ? '10' : '5' }}"
                                      placeholder="Enter your answer here..."
                                      oninput="markAnswered({{ $index }})"
                                      style="width: 100%; padding: 16px; border: 2px solid #e2e8f0; border-radius: 12px; font-family: inherit; font-size: 1rem; resize: vertical; transition: border-color 0.2s; line-height: 1.6;">{{ old('answers.'.$quizAnswer->question->id) }}</textarea>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach

        <!-- Navigation Buttons -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 32px; gap: 16px;">
            <button type="button" id="prevBtn" onclick="prevQuestion()"
                    style="flex: 1; padding: 14px 28px; border-radius: 12px; cursor: pointer; background: #f1f5f9; color: #64748b; border: 2px solid #e2e8f0; font-weight: 600; font-size: 1rem; transition: all 0.2s;">
                <i class="fa-solid fa-arrow-left"></i> Previous
            </button>

            <button type="button" id="nextBtn" onclick="nextQuestion()"
                    style="flex: 1; padding: 14px 28px; border-radius: 12px; cursor: pointer; background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; border: none; font-weight: 600; font-size: 1rem; transition: all 0.2s; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); display: flex;">
                Next <i class="fa-solid fa-arrow-right"></i>
            </button>
        </div>

        <!-- Submit Button -->
        <div style="text-align: center; margin-top: 32px; padding-top: 32px; border-top: 2px solid #e2e8f0;">
            <button type="submit" onclick="return confirmSubmit()"
                    style="padding: 16px 40px; border-radius: 12px; cursor: pointer; font-size: 1.1rem; font-weight: 700; background: linear-gradient(135deg, #22c55e, #16a34a); color: white; border: none; box-shadow: 0 4px 16px rgba(34, 197, 94, 0.3); transition: all 0.2s;">
                <i class="fa-solid fa-check-circle"></i> Submit Quiz
            </button>
            <div style="font-size: 0.9rem; color: #64748b; margin-top: 12px; font-weight: 500;">
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

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            updateNavigation();
            startTimer();
            loadSavedAnswers();

            // Auto-save every 30 seconds
            setInterval(autoSave, 30000);
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