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
    <!-- Quiz Info Bar -->
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; border-radius: 12px; color: white; margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div>
                <div style="font-size: 1.1rem; font-weight: 600; margin-bottom: 4px;">{{ $quiz->title }}</div>
                <div style="font-size: 0.9rem; opacity: 0.9;">{{ $inProgress->answers->count() }} Questions</div>
            </div>
            
            @if($quiz->time_limit_minutes)
                <div style="text-align: center;">
                    <div style="font-size: 0.85rem; opacity: 0.9;">Time Remaining</div>
                    <div id="timer" style="font-size: 1.5rem; font-weight: 700;">{{ $quiz->time_limit_minutes }}:00</div>
                </div>
            @endif
            
            <div style="text-align: center;">
                <div style="font-size: 0.85rem; opacity: 0.9;">Progress</div>
                <div id="progress" style="font-size: 1.5rem; font-weight: 700;">0/{{ $inProgress->answers->count() }}</div>
            </div>
        </div>
    </div>

    <!-- Question Navigation -->
    <div class="form-card" style="margin-bottom: 24px;">
        <h3><i class="fa-solid fa-list-ol"></i> Question Navigation</h3>
        <div id="questionNav" style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 16px;">
            @foreach($inProgress->answers as $index => $answer)
                <button type="button" 
                        onclick="goToQuestion({{ $index }})"
                        class="question-nav-btn"
                        data-question="{{ $index }}"
                        style="width: 40px; height: 40px; border: 2px solid #cbd5e1; border-radius: 8px; background: white; cursor: pointer; font-weight: 600; color: #64748b; transition: all 0.2s;">
                    {{ $index + 1 }}
                </button>
            @endforeach
        </div>
        <div style="display: flex; gap: 16px; margin-top: 12px; font-size: 0.85rem; color: #64748b;">
            <div style="display: flex; align-items: center; gap: 4px;">
                <div style="width: 16px; height: 16px; background: #3b82f6; border-radius: 4px;"></div>
                <span>Current</span>
            </div>
            <div style="display: flex; align-items: center; gap: 4px;">
                <div style="width: 16px; height: 16px; background: #22c55e; border-radius: 4px;"></div>
                <span>Answered</span>
            </div>
            <div style="display: flex; align-items: center; gap: 4px;">
                <div style="width: 16px; height: 16px; background: #f1f5f9; border-radius: 4px;"></div>
                <span>Unanswered</span>
            </div>
        </div>
    </div>

    <!-- Quiz Form -->
    <form action="{{ route('student.courses.quizzes.attempts.store', [$course, $quiz]) }}" 
          method="POST" id="quizForm">
        @csrf
        
        @foreach($inProgress->answers as $index => $quizAnswer)
            <div class="question-card" id="question-{{ $index }}" 
                 style="display: {{ $index === 0 ? 'block' : 'none' }}; margin-bottom: 24px;">
                
                <div class="form-card">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 16px;">
                        <h3 style="margin: 0;">
                            <span style="background: #3b82f6; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; margin-right: 8px;">
                                Question {{ $index + 1 }}
                            </span>
                            {{ $quizAnswer->question->question_type === 'multiple_choice' ? 'Multiple Choice' : 
                               $quizAnswer->question->question_type === 'true_false' ? 'True/False' : 
                               $quizAnswer->question->question_type === 'multiple_answer' ? 'Multiple Answer' : 
                               $quizAnswer->question->question_type === 'short_answer' ? 'Short Answer' : 
                               $quizAnswer->question->question_type === 'essay' ? 'Essay' : 'Question' }}
                        </h3>
                        <span style="background: #fef3c7; color: #d97706; padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">
                            {{ $quizAnswer->question->quizzes->find($quiz->id)?->pivot->points ?? $quizAnswer->question->default_points ?? 1 }} pts
                        </span>
                    </div>
                    
                    <div style="font-size: 1.1rem; color: #1e293b; margin-bottom: 20px; line-height: 1.6;">
                        {!! $quizAnswer->question->question_text !!}
                    </div>
                    
                    @if($quizAnswer->question->question_type === 'multiple_choice' || $quizAnswer->question->question_type === 'true_false')
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            @foreach($quizAnswer->question->choices as $choice)
                                <label class="choice-label" 
                                       style="display: flex; align-items: center; padding: 16px; border: 2px solid #e2e8f0; border-radius: 8px; cursor: pointer; transition: all 0.2s; background: white;">
                                    <input type="radio" 
                                           name="answers[{{ $quizAnswer->question->id }}]" 
                                           value="{{ $choice->id }}"
                                           {{ old('answers.'.$quizAnswer->question->id) == $choice->id ? 'checked' : '' }}
                                           onchange="markAnswered({{ $index }})"
                                           style="width: 20px; height: 20px; margin-right: 12px; accent-color: #3b82f6;">
                                    <span style="flex: 1;">{!! $choice->choice_text !!}</span>
                                </label>
                            @endforeach
                        </div>
                    
                    @elseif($quizAnswer->question->question_type === 'multiple_answer')
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            @foreach($quizAnswer->question->choices as $choice)
                                <label class="choice-label" 
                                       style="display: flex; align-items: center; padding: 16px; border: 2px solid #e2e8f0; border-radius: 8px; cursor: pointer; transition: all 0.2s; background: white;">
                                    <input type="checkbox" 
                                           name="answers[{{ $quizAnswer->question->id }}][]" 
                                           value="{{ $choice->id }}"
                                           {{ in_array($choice->id, old('answers.'.$quizAnswer->question->id, [])) ? 'checked' : '' }}
                                           onchange="markAnswered({{ $index }})"
                                           style="width: 20px; height: 20px; margin-right: 12px; accent-color: #3b82f6;">
                                    <span style="flex: 1;">{!! $choice->choice_text !!}</span>
                                </label>
                            @endforeach
                        </div>
                    
                    @elseif($quizAnswer->question->question_type === 'short_answer' || $quizAnswer->question->question_type === 'essay')
                        <div>
                            <textarea name="answers[{{ $quizAnswer->question->id }}]" 
                                      rows="{{ $quizAnswer->question->question_type === 'essay' ? '8' : '4' }}"
                                      placeholder="Enter your answer here..."
                                      onchange="markAnswered({{ $index }})"
                                      style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-family: inherit; resize: vertical;">{{ old('answers.'.$quizAnswer->question->id) }}</textarea>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
        
        <!-- Navigation Buttons -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 24px;">
            <button type="button" id="prevBtn" onclick="prevQuestion()" 
                    class="btn-modal-cancel" style="padding: 12px 24px; border-radius: 8px; cursor: pointer; disabled: true;">
                <i class="fa-solid fa-arrow-left"></i> Previous
            </button>
            
            <button type="button" id="nextBtn" onclick="nextQuestion()" 
                    class="btn-add" style="padding: 12px 24px; border-radius: 8px; cursor: pointer;">
                Next <i class="fa-solid fa-arrow-right"></i>
            </button>
        </div>
        
        <!-- Submit Button -->
        <div style="text-align: center; margin-top: 24px; padding-top: 24px; border-top: 1px solid #e2e8f0;">
            <button type="submit" onclick="return confirmSubmit()" 
                    class="btn-add" style="padding: 14px 32px; border-radius: 8px; cursor: pointer; font-size: 1.1rem; font-weight: 600; background: #22c55e;">
                <i class="fa-solid fa-check-circle"></i> Submit Quiz
            </button>
            <div style="font-size: 0.85rem; color: #64748b; margin-top: 8px;">
                Make sure you have answered all questions before submitting
            </div>
        </div>
    </form>

    <!-- Auto-save Warning -->
    <div id="autoSaveWarning" style="display: none; position: fixed; bottom: 20px; right: 20px; background: #fef3c7; color: #d97706; padding: 12px 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
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
            document.getElementById('prevBtn').disabled = currentQuestion === 0;
            document.getElementById('nextBtn').style.display = currentQuestion === totalQuestions - 1 ? 'none' : 'inline-flex';
            
            // Update question navigation buttons
            for (let i = 0; i < totalQuestions; i++) {
                const btn = document.querySelector(`[data-question="${i}"]`);
                if (btn) {
                    btn.style.borderColor = '#cbd5e1';
                    btn.style.background = 'white';
                    btn.style.color = '#64748b';
                    
                    if (i === currentQuestion) {
                        btn.style.borderColor = '#3b82f6';
                        btn.style.background = '#3b82f6';
                        btn.style.color = 'white';
                    } else if (answeredQuestions.has(i)) {
                        btn.style.borderColor = '#22c55e';
                        btn.style.background = '#22c55e';
                        btn.style.color = 'white';
                    }
                }
            }
            
            // Update progress
            document.getElementById('progress').textContent = answeredQuestions.size + '/' + totalQuestions;
        }

        function startTimer() {
            @if($quiz->time_limit_minutes)
                if (timeRemaining > 0) {
                    timerInterval = setInterval(function() {
                        timeRemaining--;
                        
                        const minutes = Math.floor(timeRemaining / 60);
                        const seconds = timeRemaining % 60;
                        document.getElementById('timer').textContent = 
                            minutes.toString().padStart(2, '0') + ':' + seconds.toString().padStart(2, '0');
                        
                        // Warning when 5 minutes remaining
                        if (timeRemaining === 300) {
                            alert('You have 5 minutes remaining!');
                        }
                        
                        // Warning when 1 minute remaining
                        if (timeRemaining === 60) {
                            alert('You have 1 minute remaining!');
                        }
                        
                        // Auto-submit when time expires
                        if (timeRemaining <= 0) {
                            clearInterval(timerInterval);
                            alert('Time has expired! Your answers will be auto-submitted.');
                            document.getElementById('quizForm').submit();
                        }
                    }, 1000);
                }
            @endif
        }

        function autoSave() {
            const formData = new FormData(document.getElementById('quizForm'));
            localStorage.setItem('quizAnswers_' + {{ $inProgress->id }}, JSON.stringify(Object.fromEntries(formData)));
            
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
                        if (el.type === 'radio' || el.type === 'checkbox') {
                            el.checked = el.value == value || (Array.isArray(value) && value.includes(el.value));
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
        document.querySelectorAll('.choice-label input').forEach(input => {
            input.addEventListener('change', function() {
                document.querySelectorAll('.choice-label').forEach(label => {
                    label.style.borderColor = '#e2e8f0';
                    label.style.background = 'white';
                });
                
                if (this.checked) {
                    this.closest('.choice-label').style.borderColor = '#3b82f6';
                    this.closest('.choice-label').style.background = '#eff6ff';
                }
            });
        });
    </script>
@endsection