<?php $__env->startSection('title', 'Quiz Attempt'); ?>
<?php
    $activeNav = 'quizzes';
    $pageTitle = 'Taking Quiz';
    $pageIcon = '<i class="fa-solid fa-edit"></i>';
?>

<?php $__env->startSection('page-title-bar'); ?>
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-edit"></i>
            <?php echo e($quiz->title); ?> - Attempt <?php echo e($inProgress->attempt_number); ?>

        </h2>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <!-- Quiz Info Bar -->
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; border-radius: 12px; color: white; margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <div>
                <div style="font-size: 1.1rem; font-weight: 600; margin-bottom: 4px;"><?php echo e($quiz->title); ?></div>
                <div style="font-size: 0.9rem; opacity: 0.9;"><?php echo e($inProgress->answers->count()); ?> Questions</div>
            </div>
            
            <?php if($quiz->time_limit_minutes): ?>
                <div style="text-align: center;">
                    <div style="font-size: 0.85rem; opacity: 0.9;">Time Remaining</div>
                    <div id="timer" style="font-size: 1.5rem; font-weight: 700;"><?php echo e($quiz->time_limit_minutes); ?>:00</div>
                </div>
            <?php endif; ?>
            
            <div style="text-align: center;">
                <div style="font-size: 0.85rem; opacity: 0.9;">Progress</div>
                <div id="progress" style="font-size: 1.5rem; font-weight: 700;">0/<?php echo e($inProgress->answers->count()); ?></div>
            </div>
        </div>
    </div>

    <!-- Question Navigation -->
    <div class="form-card" style="margin-bottom: 24px;">
        <h3><i class="fa-solid fa-list-ol"></i> Question Navigation</h3>
        <div id="questionNav" style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 16px;">
            <?php $__currentLoopData = $inProgress->answers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $answer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <button type="button" 
                        onclick="goToQuestion(<?php echo e($index); ?>)"
                        class="question-nav-btn"
                        data-question="<?php echo e($index); ?>"
                        style="width: 40px; height: 40px; border: 2px solid #cbd5e1; border-radius: 8px; background: white; cursor: pointer; font-weight: 600; color: #64748b; transition: all 0.2s;">
                    <?php echo e($index + 1); ?>

                </button>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
    <form action="<?php echo e(route('student.courses.quizzes.attempts.store', [$course, $quiz])); ?>" 
          method="POST" id="quizForm">
        <?php echo csrf_field(); ?>
        
        <?php $__currentLoopData = $inProgress->answers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $quizAnswer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="question-card" id="question-<?php echo e($index); ?>" 
                 style="display: <?php echo e($index === 0 ? 'block' : 'none'); ?>; margin-bottom: 24px;">
                
                <div class="form-card">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 16px;">
                        <h3 style="margin: 0;">
                            <span style="background: #3b82f6; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; margin-right: 8px;">
                                Question <?php echo e($index + 1); ?>

                            </span>
                            <?php echo e($quizAnswer->question->question_type === 'multiple_choice' ? 'Multiple Choice' : 
                               $quizAnswer->question->question_type === 'true_false' ? 'True/False' : 
                               $quizAnswer->question->question_type === 'multiple_answer' ? 'Multiple Answer' : 
                               $quizAnswer->question->question_type === 'short_answer' ? 'Short Answer' : 
                               $quizAnswer->question->question_type === 'essay' ? 'Essay' : 'Question'); ?>

                        </h3>
                        <span style="background: #fef3c7; color: #d97706; padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">
                            <?php echo e($quizAnswer->question->quizzes->find($quiz->id)?->pivot->points ?? $quizAnswer->question->default_points ?? 1); ?> pts
                        </span>
                    </div>
                    
                    <div style="font-size: 1.1rem; color: #1e293b; margin-bottom: 20px; line-height: 1.6;">
                        <?php echo $quizAnswer->question->question_text; ?>

                    </div>
                    
                    <?php if($quizAnswer->question->question_type === 'multiple_choice' || $quizAnswer->question->question_type === 'true_false'): ?>
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <?php $__currentLoopData = $quizAnswer->question->choices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $choice): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <label class="choice-label" 
                                       style="display: flex; align-items: center; padding: 16px; border: 2px solid #e2e8f0; border-radius: 8px; cursor: pointer; transition: all 0.2s; background: white;">
                                    <input type="radio" 
                                           name="answers[<?php echo e($quizAnswer->question->id); ?>]" 
                                           value="<?php echo e($choice->id); ?>"
                                           <?php echo e(old('answers.'.$quizAnswer->question->id) == $choice->id ? 'checked' : ''); ?>

                                           onchange="markAnswered(<?php echo e($index); ?>)"
                                           style="width: 20px; height: 20px; margin-right: 12px; accent-color: #3b82f6;">
                                    <span style="flex: 1;"><?php echo $choice->choice_text; ?></span>
                                </label>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    
                    <?php elseif($quizAnswer->question->question_type === 'multiple_answer'): ?>
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <?php $__currentLoopData = $quizAnswer->question->choices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $choice): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <label class="choice-label" 
                                       style="display: flex; align-items: center; padding: 16px; border: 2px solid #e2e8f0; border-radius: 8px; cursor: pointer; transition: all 0.2s; background: white;">
                                    <input type="checkbox" 
                                           name="answers[<?php echo e($quizAnswer->question->id); ?>][]" 
                                           value="<?php echo e($choice->id); ?>"
                                           <?php echo e(in_array($choice->id, old('answers.'.$quizAnswer->question->id, [])) ? 'checked' : ''); ?>

                                           onchange="markAnswered(<?php echo e($index); ?>)"
                                           style="width: 20px; height: 20px; margin-right: 12px; accent-color: #3b82f6;">
                                    <span style="flex: 1;"><?php echo $choice->choice_text; ?></span>
                                </label>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    
                    <?php elseif($quizAnswer->question->question_type === 'short_answer' || $quizAnswer->question->question_type === 'essay'): ?>
                        <div>
                            <textarea name="answers[<?php echo e($quizAnswer->question->id); ?>]" 
                                      rows="<?php echo e($quizAnswer->question->question_type === 'essay' ? '8' : '4'); ?>"
                                      placeholder="Enter your answer here..."
                                      onchange="markAnswered(<?php echo e($index); ?>)"
                                      style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-family: inherit; resize: vertical;"><?php echo e(old('answers.'.$quizAnswer->question->id)); ?></textarea>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        
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
        let totalQuestions = <?php echo e($inProgress->answers->count()); ?>;
        let answeredQuestions = new Set();
        let timeRemaining = <?php echo e($quiz->time_limit_minutes ? $quiz->time_limit_minutes * 60 : 0); ?>;
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
            <?php if($quiz->time_limit_minutes): ?>
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
            <?php endif; ?>
        }

        function autoSave() {
            const formData = new FormData(document.getElementById('quizForm'));
            localStorage.setItem('quizAnswers_' + <?php echo e($inProgress->id); ?>, JSON.stringify(Object.fromEntries(formData)));
            
            // Show auto-save notification
            const warning = document.getElementById('autoSaveWarning');
            warning.style.display = 'block';
            setTimeout(() => {
                warning.style.display = 'none';
            }, 2000);
        }

        function loadSavedAnswers() {
            const saved = localStorage.getItem('quizAnswers_' + <?php echo e($inProgress->id); ?>);
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
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.student', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\student\quizzes\attempt.blade.php ENDPATH**/ ?>