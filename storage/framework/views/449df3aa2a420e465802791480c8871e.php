<?php $__env->startSection('title', 'Student Dashboard'); ?>
<?php
    $activeNav = 'dashboard';
    $pageTitle = 'Student Dashboard';
    $pageIcon = '<i class="fa-solid fa-user-graduate"></i>';
    $stats ??= [];
    $continue = $stats['continue_learning'] ?? null;
    $progressData = $stats['course_progress'] ?? [];
?>

<?php $__env->startSection('page-title-bar'); ?>
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-user-graduate"></i>
            Student Dashboard
        </h2>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="learning-shell">
    
    <?php if (isset($component)) { $__componentOriginal8fc5d82814dad270c8dc67128a2a98d5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-page-header','data' => ['title' => 'Good day, '.e(auth()->user()->name ?? 'Student').'','subtitle' => 'Pick up where you left off — here\'s what needs your attention today.','icon' => 'fa-graduation-cap','kicker' => 'Student learning space']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Good day, '.e(auth()->user()->name ?? 'Student').'','subtitle' => 'Pick up where you left off — here\'s what needs your attention today.','icon' => 'fa-graduation-cap','kicker' => 'Student learning space']); ?>
         <?php $__env->slot('meta', null, []); ?> 
            <span class="live-dot"></span>
            <span><?php echo e(now()->format('l, F j, Y')); ?></span>
            <?php if($stats['current_period'] ?? null): ?>
                <span>·</span>
                <span><?php echo e($stats['current_period']->name ?? ''); ?></span>
            <?php endif; ?>
         <?php $__env->endSlot(); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <a href="<?php echo e(route('student.courses.index')); ?>" class="btn btn-secondary"><i class="fa-solid fa-book-open"></i> Browse courses</a>
            <a href="<?php echo e(route('student.enrollments.index')); ?>" class="btn btn-secondary"><i class="fa-solid fa-user-plus"></i> Enrollments</a>
            <a href="<?php echo e(route('student.classes.index')); ?>" class="btn btn-secondary"><i class="fa-solid fa-graduation-cap"></i> My grades</a>
            <button onclick="refreshDashboard()" class="btn btn-secondary"><i class="fa-solid fa-sync-alt"></i> Refresh</button>
         <?php $__env->endSlot(); ?>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5)): ?>
<?php $attributes = $__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5; ?>
<?php unset($__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8fc5d82814dad270c8dc67128a2a98d5)): ?>
<?php $component = $__componentOriginal8fc5d82814dad270c8dc67128a2a98d5; ?>
<?php unset($__componentOriginal8fc5d82814dad270c8dc67128a2a98d5); ?>
<?php endif; ?>

    
    <section class="dash-search">
        <div class="search-container">
            <div class="search-input-wrapper">
                <i class="fa-solid fa-search search-icon"></i>
                <input type="text" id="globalSearch" placeholder="Search your courses, assignments, quizzes..." class="search-input">
                <select id="searchType" class="search-type">
                    <option value="all">All</option>
                    <option value="courses">Courses</option>
                    <option value="assignments">Assignments</option>
                    <option value="quizzes">Quizzes</option>
                </select>
            </div>
            <div id="searchResults" class="search-results hidden"></div>
        </div>
    </section>

    
    <div class="dash-quick">
        <div class="qa-grid">
            <a href="<?php echo e(route('student.courses.index')); ?>" class="qa-card">
                <i class="fa-solid fa-book-open"></i>
                <div><span class="qa-label">Browse courses</span><span class="qa-sub">Explore new subjects</span></div>
            </a>
            <a href="<?php echo e(route('student.classes.index')); ?>" class="qa-card">
                <i class="fa-solid fa-building-columns"></i>
                <div><span class="qa-label">My classes</span><span class="qa-sub"><?php echo e($stats['active_enrollments'] ?? 0); ?> active</span></div>
            </a>
            <a href="<?php echo e(route('student.enrollments.index')); ?>" class="qa-card">
                <i class="fa-solid fa-user-plus"></i>
                <div><span class="qa-label">Enrollments</span><span class="qa-sub"><?php echo e($stats['total_enrollments'] ?? 0); ?> total</span></div>
            </a>
            <a href="<?php echo e(route('student.classes.index')); ?>" class="qa-card">
                <i class="fa-solid fa-file-lines"></i>
                <div><span class="qa-label">My grades</span><span class="qa-sub"><?php echo e($stats['total_grades'] ?? 0); ?> released</span></div>
            </a>
        </div>
    </div>

    
    <?php $overall = (float)($stats['overall_progress'] ?? 0); ?>
    <div class="user-stat-grid">
        <?php if (isset($component)) { $__componentOriginal539478ab10802a5a905ec5e50f354013 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal539478ab10802a5a905ec5e50f354013 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-stat-card','data' => ['label' => 'My Courses','value' => ''.e($stats['my_courses'] ?? 0).'','icon' => 'fa-book','valueId' => 'stat-courses-value','trend' => ''.e(($stats['active_enrollments'] ?? 0) > 0 ? 'Active' : 'New').'','footer' => ''.e($stats['completed_enrollments'] ?? 0).' completed']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'My Courses','value' => ''.e($stats['my_courses'] ?? 0).'','icon' => 'fa-book','valueId' => 'stat-courses-value','trend' => ''.e(($stats['active_enrollments'] ?? 0) > 0 ? 'Active' : 'New').'','footer' => ''.e($stats['completed_enrollments'] ?? 0).' completed']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal539478ab10802a5a905ec5e50f354013)): ?>
<?php $attributes = $__attributesOriginal539478ab10802a5a905ec5e50f354013; ?>
<?php unset($__attributesOriginal539478ab10802a5a905ec5e50f354013); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal539478ab10802a5a905ec5e50f354013)): ?>
<?php $component = $__componentOriginal539478ab10802a5a905ec5e50f354013; ?>
<?php unset($__componentOriginal539478ab10802a5a905ec5e50f354013); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginal539478ab10802a5a905ec5e50f354013 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal539478ab10802a5a905ec5e50f354013 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-stat-card','data' => ['label' => 'Overall Progress','value' => ''.e(round($overall)).'%','icon' => 'fa-chart-line','trend' => 'Live']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Overall Progress','value' => ''.e(round($overall)).'%','icon' => 'fa-chart-line','trend' => 'Live']); ?>
             <?php $__env->slot('footer', null, []); ?> <i class="fa-solid fa-fire" style="color:#fbbf24"></i> <span id="stat-streak-value"><?php echo e($stats['learning_streak'] ?? 0); ?></span>-day streak <?php $__env->endSlot(); ?>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal539478ab10802a5a905ec5e50f354013)): ?>
<?php $attributes = $__attributesOriginal539478ab10802a5a905ec5e50f354013; ?>
<?php unset($__attributesOriginal539478ab10802a5a905ec5e50f354013); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal539478ab10802a5a905ec5e50f354013)): ?>
<?php $component = $__componentOriginal539478ab10802a5a905ec5e50f354013; ?>
<?php unset($__componentOriginal539478ab10802a5a905ec5e50f354013); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginal539478ab10802a5a905ec5e50f354013 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal539478ab10802a5a905ec5e50f354013 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-stat-card','data' => ['label' => 'Average Grade','value' => ''.e(number_format($stats['average_grade'] ?? 0, 1)).'%','icon' => 'fa-star','trend' => 'GPA '.e(number_format($stats['gpa'] ?? 0, 2)).'','footer' => ''.e(($stats['highest_grade'] ?? 0) > 0 ? 'Best '.number_format($stats['highest_grade'], 1).'%' : 'No grades yet').'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Average Grade','value' => ''.e(number_format($stats['average_grade'] ?? 0, 1)).'%','icon' => 'fa-star','trend' => 'GPA '.e(number_format($stats['gpa'] ?? 0, 2)).'','footer' => ''.e(($stats['highest_grade'] ?? 0) > 0 ? 'Best '.number_format($stats['highest_grade'], 1).'%' : 'No grades yet').'']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal539478ab10802a5a905ec5e50f354013)): ?>
<?php $attributes = $__attributesOriginal539478ab10802a5a905ec5e50f354013; ?>
<?php unset($__attributesOriginal539478ab10802a5a905ec5e50f354013); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal539478ab10802a5a905ec5e50f354013)): ?>
<?php $component = $__componentOriginal539478ab10802a5a905ec5e50f354013; ?>
<?php unset($__componentOriginal539478ab10802a5a905ec5e50f354013); ?>
<?php endif; ?>
        <?php if (isset($component)) { $__componentOriginal539478ab10802a5a905ec5e50f354013 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal539478ab10802a5a905ec5e50f354013 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-stat-card','data' => ['label' => 'Upcoming Tasks','value' => ''.e(($stats['upcoming_assignments'] ?? collect())->count() + ($stats['upcoming_quizzes'] ?? collect())->count()).'','icon' => 'fa-bell','valueId' => 'stat-tasks-value','trend' => ''.e(($stats['overdue_assignments'] ?? collect())->count() > 0 ? 'Due soon' : 'On track').'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Upcoming Tasks','value' => ''.e(($stats['upcoming_assignments'] ?? collect())->count() + ($stats['upcoming_quizzes'] ?? collect())->count()).'','icon' => 'fa-bell','valueId' => 'stat-tasks-value','trend' => ''.e(($stats['overdue_assignments'] ?? collect())->count() > 0 ? 'Due soon' : 'On track').'']); ?>
             <?php $__env->slot('footer', null, []); ?> <i class="fa-solid fa-triangle-exclamation" style="color:#fb7185"></i> <span id="stat-overdue-value"><?php echo e(($stats['overdue_assignments'] ?? collect())->count()); ?></span> overdue <?php $__env->endSlot(); ?>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal539478ab10802a5a905ec5e50f354013)): ?>
<?php $attributes = $__attributesOriginal539478ab10802a5a905ec5e50f354013; ?>
<?php unset($__attributesOriginal539478ab10802a5a905ec5e50f354013); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal539478ab10802a5a905ec5e50f354013)): ?>
<?php $component = $__componentOriginal539478ab10802a5a905ec5e50f354013; ?>
<?php unset($__componentOriginal539478ab10802a5a905ec5e50f354013); ?>
<?php endif; ?>
    </div>

    
    <?php
        $assessment = $stats['performance_assessment'] ?? null;
        $assessmentStatus = $assessment['status'] ?? 'insufficient_data';
    ?>
    <?php if($assessment): ?>
        <section class="student-assessment student-assessment-<?php echo e($assessmentStatus); ?>" aria-labelledby="student-assessment-title">
            <div class="student-assessment-header">
                <div class="student-assessment-title-wrap">
                    <span class="student-assessment-icon" aria-hidden="true"><i class="fa-solid fa-wand-magic-sparkles"></i></span>
                    <div>
                        <span class="student-assessment-eyebrow">AI-assisted learning check-in</span>
                        <h2 id="student-assessment-title">Your performance assessment</h2>
                        <p><?php echo e($assessment['summary']); ?></p>
                    </div>
                </div>
                <div class="student-assessment-status" aria-label="Assessment status: <?php echo e($assessment['label']); ?>">
                    <strong><?php echo e($assessment['label']); ?></strong>
                    <span><?php echo e($assessment['confidence']); ?>% data coverage</span>
                </div>
            </div>

            <div class="student-assessment-body">
                <div class="student-assessment-score">
                    <div class="student-risk-meter" style="--risk-score: <?php echo e($assessment['risk_score']); ?>%" role="img" aria-label="Risk score <?php echo e($assessment['risk_score']); ?> out of 100">
                        <span><strong><?php echo e($assessment['risk_score']); ?></strong><small>risk score</small></span>
                    </div>
                    <div>
                        <strong><?php echo e($assessmentStatus === 'at_risk' ? 'A little support can help' : ($assessmentStatus === 'on_track' ? 'You are building momentum' : 'Let’s build your baseline')); ?></strong>
                        <p>Signals are based on your recent course activity and are meant to guide your next step—not label your ability.</p>
                    </div>
                </div>

                <div class="student-assessment-signals" aria-label="Performance signals">
                    <?php $__currentLoopData = $assessment['signals']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $signal): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="student-signal student-signal-<?php echo e($signal['status']); ?>">
                            <span class="student-signal-dot" aria-hidden="true"></span>
                            <div><strong><?php echo e($signal['label']); ?></strong><span><?php echo e($signal['value']); ?></span><small><?php echo e($signal['detail']); ?></small></div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>

                <div class="student-assessment-columns">
                    <div>
                        <h3><i class="fa-solid fa-magnifying-glass-chart" aria-hidden="true"></i> What we noticed</h3>
                        <ul class="student-assessment-list">
                            <?php $__currentLoopData = $assessment['reasons']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reason): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li><i class="fa-solid fa-circle-info" aria-hidden="true"></i><span><?php echo e($reason); ?></span></li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    </div>
                    <div>
                        <h3><i class="fa-solid fa-route" aria-hidden="true"></i> Recommended next steps</h3>
                        <div class="student-recommendations">
                            <?php $__currentLoopData = $assessment['recommendations']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $recommendation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div class="student-recommendation">
                                    <span><i class="fa-solid <?php echo e($recommendation['icon']); ?>" aria-hidden="true"></i></span>
                                    <div><strong><?php echo e($recommendation['title']); ?></strong><small><?php echo e($recommendation['detail']); ?></small></div>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

    
    <?php if(($stats['overdue_assignments'] ?? collect())->isNotEmpty()): ?>
        <div class="overdue-strip">
            <div class="overdue-head">
                <span class="overdue-icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
                <div>
                    <h4><?php echo e(($stats['overdue_assignments'] ?? collect())->count()); ?> overdue assignment<?php echo e(($stats['overdue_assignments'] ?? collect())->count() > 1 ? 's' : ''); ?></h4>
                    <p>Submit these to avoid grade penalties.</p>
                </div>
            </div>
            <ul class="overdue-list">
                <?php $__currentLoopData = ($stats['overdue_assignments'] ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $assignment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li>
                        <a href="<?php echo e($assignment->class?->id ? route('student.classes.show', $assignment->class_id) : '#'); ?>">
                            <span class="od-name"><?php echo e($assignment->title); ?></span>
                            <span class="od-meta"><?php echo e($assignment->class?->course?->title ?? ''); ?> · due <?php echo e($assignment->due_date?->diffForHumans()); ?></span>
                        </a>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    <?php endif; ?>

    
    <?php if($continue && isset($continue['course'])): ?>
        <div class="continue-strip">
            <div class="continue-card">
                <div class="cc-icon"><i class="fa-solid fa-book-open"></i></div>
                <div class="cc-body">
                    <span class="cc-kicker">Continue learning</span>
                    <h4 class="cc-title"><?php echo e($continue['course']->title); ?></h4>
                    <p class="cc-sub"><?php echo e($continue['course']->code ?? ''); ?> · last accessed <?php echo e($continue['last_accessed'] ? $continue['last_accessed']->diffForHumans() : 'recently'); ?></p>
                </div>
                <div class="cc-progress">
                    <div class="dash-progress"><span style="width: <?php echo e($continue['progress'] ?? 0); ?>%"></span></div>
                    <div class="dash-progress-label"><?php echo e(round($continue['progress'] ?? 0)); ?>% complete</div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="dash-grid">
        
        <section class="dash-panel">
            <div class="dash-panel-head">
                <h4><i class="fa-solid fa-bell"></i> Upcoming this week</h4>
                <span class="panel-count"><?php echo e(($stats['upcoming_assignments'] ?? collect())->count() + ($stats['upcoming_quizzes'] ?? collect())->count()); ?></span>
            </div>
            <ul class="dash-list">
                <?php $__empty_1 = true; $__currentLoopData = ($stats['upcoming_assignments'] ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $assignment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <li class="dash-list-item">
                        <a class="dash-list-link" href="<?php echo e($assignment->class_id ? route('student.classes.show', $assignment->class_id) : '#'); ?>">
                            <span class="dash-list-icon"><i class="fa-solid fa-tasks"></i></span>
                            <div class="dash-list-body">
                                <p class="dash-list-title"><?php echo e($assignment->title); ?></p>
                                <p class="dash-list-sub"><?php echo e($assignment->class?->course?->title ?? ''); ?></p>
                            </div>
                            <div class="dash-list-meta">
                                <span class="dash-meta-chip m-amber">Due <?php echo e($assignment->due_date?->format('M j')); ?></span>
                                <span class="dash-list-date"><?php echo e($assignment->due_date?->diffForHumans()); ?></span>
                            </div>
                        </a>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <li class="dash-list-empty"><i class="fa-solid fa-calendar-check"></i> No upcoming assignments</li>
                <?php endif; ?>
                <?php $__empty_1 = true; $__currentLoopData = ($stats['upcoming_quizzes'] ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $quiz): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <li class="dash-list-item">
                        <a class="dash-list-link" href="<?php echo e($quiz->class_id ? route('student.classes.show', $quiz->class_id) : '#'); ?>">
                            <span class="dash-list-icon i-violet"><i class="fa-solid fa-question-circle"></i></span>
                            <div class="dash-list-body">
                                <p class="dash-list-title"><?php echo e($quiz->title); ?></p>
                                <p class="dash-list-sub">Quiz · <?php echo e($quiz->class?->course?->title ?? ''); ?></p>
                            </div>
                            <div class="dash-list-meta">
                                <span class="dash-meta-chip m-blue">Opens <?php echo e($quiz->availability_from?->format('M j')); ?></span>
                                <span class="dash-list-date"><?php echo e($quiz->availability_from?->diffForHumans()); ?></span>
                            </div>
                        </a>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <?php endif; ?>
                <?php if(($stats['upcoming_assignments'] ?? [])->isEmpty() && ($stats['upcoming_quizzes'] ?? [])->isEmpty()): ?>
                    <li class="dash-list-empty"><i class="fa-solid fa-mug-hot"></i> All caught up — nothing due soon</li>
                <?php endif; ?>
            </ul>
        </section>

        
        <section class="dash-panel">
            <div class="dash-panel-head">
                <h4><i class="fa-solid fa-chart-simple"></i> My course progress</h4>
                <span class="panel-count"><?php echo e(count($progressData)); ?></span>
            </div>
            <ul class="dash-list">
                <?php $__empty_1 = true; $__currentLoopData = $progressData; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <li class="dash-list-item">
                        <a class="dash-list-link" href="<?php echo e(($item['course']->id ?? null) ? route('student.courses.show', $item['course']->id) : '#'); ?>">
                            <span class="dash-list-icon i-green"><i class="fa-solid fa-book"></i></span>
                            <div class="dash-list-body">
                                <p class="dash-list-title"><?php echo e($item['course']->title); ?></p>
                                <p class="dash-list-sub"><?php echo e($item['course']->code ?? ''); ?></p>
                            </div>
                            <div class="dash-list-meta">
                                <div class="dash-progress"><span style="width: <?php echo e($item['progress']); ?>%"></span></div>
                                <span class="dash-progress-label"><?php echo e(round($item['progress'])); ?>%</span>
                            </div>
                        </a>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <li class="dash-list-empty"><i class="fa-solid fa-book-open"></i> Enroll in a course to start learning</li>
                <?php endif; ?>
            </ul>
        </section>
    </div>

    
    <div class="dash-grid">
        <section class="dash-panel">
            <div class="dash-panel-head">
                <h4><i class="fa-solid fa-award"></i> My achievements</h4>
                <span class="panel-count"><?php echo e(($stats['total_certificates'] ?? 0) + ($stats['total_badges'] ?? 0)); ?></span>
            </div>
            <div style="padding:14px">
                <div class="achieve-row">
                    <div class="achieve-tile">
                        <span class="at-icon"><i class="fa-solid fa-certificate"></i></span>
                        <span class="at-count"><?php echo e($stats['total_certificates'] ?? 0); ?></span>
                        <span class="at-label">Certificates</span>
                    </div>
                    <div class="achieve-tile">
                        <span class="at-icon badge"><i class="fa-solid fa-medal"></i></span>
                        <span class="at-count"><?php echo e($stats['total_badges'] ?? 0); ?></span>
                        <span class="at-label">Badges</span>
                    </div>
                    <div class="achieve-tile">
                        <span class="at-icon" style="background:radial-gradient(circle at 30% 25%,#22d3ee,#0e7490);box-shadow:0 8px 20px rgba(34,211,238,.28)"><i class="fa-solid fa-video"></i></span>
                        <span class="at-count"><?php echo e($stats['total_virtual_classes'] ?? 0); ?></span>
                        <span class="at-label">Virtual Classes</span>
                    </div>
                </div>
                <?php if(($stats['badges'] ?? collect())->isNotEmpty()): ?>
                    <div class="earned-badges">
                        <span class="earned-badges-title"><i class="fa-solid fa-medal"></i> Recently earned</span>
                        <div class="earned-badges-row">
                            <?php $__currentLoopData = ($stats['badges'] ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $badgeAward): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <span class="earned-badge" title="<?php echo e($badgeAward->badge?->description ?? ''); ?>">
                                    <i class="fa-solid <?php echo e($badgeAward->badge?->icon ?: 'fa-award'); ?>"></i>
                                    <?php echo e($badgeAward->badge?->name ?? 'Badge'); ?>

                                </span>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="dash-panel">
            <div class="dash-panel-head">
                <h4><i class="fa-solid fa-bullhorn"></i> Announcements</h4>
                <span class="panel-count"><?php echo e($stats['total_announcements'] ?? 0); ?></span>
            </div>
            <ul class="announce-feed">
                <?php $__empty_1 = true; $__currentLoopData = ($stats['announcements'] ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $announcement): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <li class="announce-item">
                        <div class="announce-date">
                            <span class="ad-day"><?php echo e($announcement->publish_at?->format('j')); ?></span>
                            <span class="ad-mon"><?php echo e($announcement->publish_at?->format('M')); ?></span>
                        </div>
                        <div class="announce-body">
                            <p class="announce-title"><?php echo e($announcement->title); ?></p>
                            <p><?php echo e(Str::limit($announcement->body ?? $announcement->content ?? '', 90)); ?></p>
                        </div>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <li class="dash-list-empty"><i class="fa-solid fa-bullhorn"></i> No announcements yet</li>
                <?php endif; ?>
            </ul>
        </section>
    </div>

    
    <div class="dash-grid">
        <section class="dash-panel">
            <div class="dash-panel-head">
                <h4><i class="fa-solid fa-graduation-cap"></i> Recent grades</h4>
                <span class="panel-count"><?php echo e($stats['total_grades'] ?? 0); ?></span>
            </div>
            <ul class="dash-list">
                <?php $__empty_1 = true; $__currentLoopData = ($stats['recent_grades'] ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $grade): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <li class="dash-list-item">
                        <a class="dash-list-link" href="<?php echo e($grade->item?->class_id ? route('student.classes.show', $grade->item->class_id) : '#'); ?>">
                            <span class="dash-list-icon i-amber"><i class="fa-solid fa-file-lines"></i></span>
                            <div class="dash-list-body">
                                <p class="dash-list-title"><?php echo e($grade->item?->name); ?></p>
                                <p class="dash-list-sub"><?php echo e($grade->item?->class?->course?->title ?? ''); ?></p>
                            </div>
                            <div class="dash-list-meta">
                                <span class="dash-meta-chip <?php echo e(($grade->score_percent ?? 0) >= 75 ? 'm-green' : 'm-rose'); ?>"><?php echo e(number_format($grade->score_percent ?? 0, 1)); ?>%</span>
                                <span class="dash-list-date"><?php echo e($grade->graded_at?->diffForHumans()); ?></span>
                            </div>
                        </a>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <li class="dash-list-empty"><i class="fa-solid fa-file-lines"></i> No grades released yet</li>
                <?php endif; ?>
            </ul>
        </section>

        <section class="dash-panel">
            <div class="dash-panel-head">
                <h4><i class="fa-solid fa-video"></i> Upcoming virtual classes</h4>
                <span class="panel-count"><?php echo e($stats['total_virtual_classes'] ?? 0); ?></span>
            </div>
            <ul class="dash-list">
                <?php $__empty_1 = true; $__currentLoopData = ($stats['upcoming_virtual_classes'] ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $vc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <li class="dash-list-item">
                        <a class="dash-list-link" href="<?php echo e($vc->class_id ? route('student.virtual_classes.show', ['class' => $vc->class_id, 'virtualClass' => $vc->id]) : '#'); ?>">
                            <span class="dash-list-icon i-violet"><i class="fa-solid fa-video"></i></span>
                            <div class="dash-list-body">
                                <p class="dash-list-title"><?php echo e($vc->title); ?></p>
                                <p class="dash-list-sub"><?php echo e($vc->class?->course?->title ?? ''); ?></p>
                            </div>
                            <div class="dash-list-meta">
                                <span class="dash-meta-chip m-blue"><?php echo e($vc->start_time?->format('M j, g:i A')); ?></span>
                                <span class="dash-list-date"><?php echo e($vc->start_time?->diffForHumans()); ?></span>
                            </div>
                        </a>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <li class="dash-list-empty"><i class="fa-solid fa-video"></i> No upcoming virtual classes</li>
                <?php endif; ?>
            </ul>
        </section>
    </div>

    
    <div class="dash-grid">
        <section class="dash-panel">
            <div class="dash-panel-head">
                <h4><i class="fa-solid fa-question-circle"></i> Recent quiz attempts</h4>
                <span class="panel-count"><?php echo e(($stats['recent_quiz_attempts'] ?? collect())->count()); ?></span>
            </div>
            <ul class="dash-list">
                <?php $__empty_1 = true; $__currentLoopData = ($stats['recent_quiz_attempts'] ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $attempt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <li class="dash-list-item">
                        <?php
                            $quizCourse = $attempt->quiz?->class?->course;
                            $attemptHref = $attempt->quiz && $attempt->quiz->class_id
                                ? route('student.courses.quizzes.attempts.show', ['course' => $attempt->quiz->class->course_id, 'quiz' => $attempt->quiz_id, 'attempt' => $attempt->id])
                                : '#';
                        ?>
                        <a class="dash-list-link" href="<?php echo e($attemptHref); ?>">
                            <span class="dash-list-icon i-violet"><i class="fa-solid fa-clipboard-check"></i></span>
                            <div class="dash-list-body">
                                <p class="dash-list-title"><?php echo e($attempt->quiz?->title ?? 'Quiz'); ?></p>
                                <p class="dash-list-sub"><?php echo e($quizCourse?->title ?? ''); ?> · Attempt <?php echo e($attempt->attempt_number ?? 1); ?></p>
                            </div>
                            <div class="dash-list-meta">
                                <?php if(isset($attempt->is_passed)): ?>
                                    <span class="dash-meta-chip <?php echo e($attempt->is_passed ? 'm-green' : 'm-rose'); ?>"><?php echo e($attempt->is_passed ? 'Passed' : 'Retake'); ?></span>
                                <?php endif; ?>
                                <span class="dash-list-date"><?php echo e($attempt->created_at?->diffForHumans()); ?></span>
                            </div>
                        </a>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <li class="dash-list-empty"><i class="fa-solid fa-clipboard-check"></i> No quiz attempts yet</li>
                <?php endif; ?>
            </ul>
        </section>

        <section class="dash-panel">
            <div class="dash-panel-head">
                <h4><i class="fa-solid fa-comment-dots"></i> Recent feedback</h4>
                <span class="panel-count"><?php echo e(($stats['recent_feedback'] ?? collect())->count()); ?></span>
            </div>
            <ul class="dash-list">
                <?php $__empty_1 = true; $__currentLoopData = ($stats['recent_feedback'] ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $feedback): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <li class="dash-list-item">
                        <span class="dash-list-icon i-green"><i class="fa-solid fa-comment-dots"></i></span>
                        <div class="dash-list-body">
                            <p class="dash-list-title"><?php echo e(Str::limit($feedback->body ?? 'Feedback', 60)); ?></p>
                            <p class="dash-list-sub"><?php echo e($feedback->author?->name ?? 'Instructor'); ?> · <?php echo e($feedback->created_at?->diffForHumans()); ?></p>
                        </div>
                        <div class="dash-list-meta">
                            <?php if(($feedback->rating ?? 0) > 0): ?>
                                <span class="dash-meta-chip m-amber"><i class="fa-solid fa-star"></i> <?php echo e($feedback->rating); ?>/5</span>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <li class="dash-list-empty"><i class="fa-solid fa-comment-dots"></i> No feedback yet</li>
                <?php endif; ?>
            </ul>
        </section>
    </div>

    
    <section class="dash-analytics">
        <div class="dash-panel-head">
            <h4><i class="fa-solid fa-chart-pie"></i> My Performance Analytics</h4>
            <div class="analytics-controls">
                <select id="analyticsType" class="analytics-select">
                    <option value="grades" selected>Grades</option>
                    <option value="quizzes">Quiz Performance</option>
                    <option value="progress">Course Progress</option>
                </select>
                <button onclick="loadAnalytics()" class="analytics-btn"><i class="fa-solid fa-sync-alt"></i> Load</button>
            </div>
        </div>
        <div class="analytics-content">
            <div id="analytics-toast" class="analytics-toast"></div>
            <div class="analytics-grid">
                <div class="analytics-card analytics-card-wide">
                    <h5><i class="fa-solid fa-chart-line"></i> <span id="analyticsTitle">Grade Trend</span></h5>
                    <div id="analyticsChart" class="chart-container">
                        <div class="chart-placeholder"><i class="fa-solid fa-chart-bar"></i><p>Loading analytics…</p></div>
                    </div>
                </div>
                <div class="analytics-card">
                    <h5><i class="fa-solid fa-clipboard-check"></i> Summary</h5>
                    <div id="analyticsSummary" class="analytics-summary">
                        <div class="chart-placeholder"><i class="fa-solid fa-chart-simple"></i><p>Select a metric above</p></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        // Real-time dashboard refresh
        async function refreshDashboard() {
            const refreshBtn = document.querySelector('[onclick="refreshDashboard()"]');
            const originalContent = refreshBtn.innerHTML;
            refreshBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Refreshing...';
            refreshBtn.disabled = true;
            try {
                await fetch('<?php echo e(route('student.dashboard.clear-cache')); ?>', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>',
                        'Content-Type': 'application/json',
                    },
                });
                window.location.reload();
            } catch (error) {
                console.error('Error refreshing dashboard:', error);
                refreshBtn.innerHTML = originalContent;
                refreshBtn.disabled = false;
                if (typeof LMS !== 'undefined' && LMS.toast) {
                    LMS.toast('Error refreshing dashboard. Please try again.', 'error');
                } else {
                    alert('Error refreshing dashboard. Please try again.');
                }
            }
        }

        // Auto-refresh stats every 30s
        setInterval(async () => {
            try {
                const response = await fetch('<?php echo e(route('student.dashboard.real-time-stats')); ?>');
                if (!response.ok) throw new Error('Bad status: ' + response.status);
                const data = await response.json();
                if (data.success) {
                    const s = data.data;
                    const tasksEl = document.getElementById('stat-tasks-value');
                    if (tasksEl && s.upcoming_tasks !== undefined) tasksEl.textContent = s.upcoming_tasks;
                    const overdueEl = document.getElementById('stat-overdue-value');
                    if (overdueEl && s.overdue_count !== undefined) overdueEl.textContent = s.overdue_count;
                    const streakEl = document.getElementById('stat-streak-value');
                    if (streakEl && s.learning_streak !== undefined) streakEl.textContent = s.learning_streak;
                }
            } catch (error) {
                console.error('Error fetching real-time stats:', error);
            }
        }, 30000);

        // Search
        const searchInput = document.getElementById('globalSearch');
        const searchType = document.getElementById('searchType');
        const searchResults = document.getElementById('searchResults');
        let searchTimeout;

        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            if (query.length < 2) {
                searchResults.classList.add('hidden');
                return;
            }
            searchResults.innerHTML = '<div class="search-loading"><i class="fa-solid fa-spinner fa-spin"></i> Searching...</div>';
            searchResults.classList.remove('hidden');
            searchTimeout = setTimeout(() => performSearch(query), 300);
        });

        async function performSearch(query) {
            const type = searchType.value;
            try {
                const response = await fetch(`<?php echo e(route('student.dashboard.search')); ?>?query=${encodeURIComponent(query)}&type=${type}`);
                const data = await response.json();
                if (data.success) {
                    if (data.data.length > 0) {
                        displaySearchResults(data.data, type);
                    } else {
                        searchResults.innerHTML = '<div class="search-no-results">No results found</div>';
                        searchResults.classList.remove('hidden');
                    }
                } else {
                    throw new Error(data.error || 'Search failed');
                }
            } catch (error) {
                console.error('Search error:', error);
                searchResults.innerHTML = '<div class="search-no-results">Error: ' + error.message + '</div>';
                searchResults.classList.remove('hidden');
            }
        }

        function typeBadge(result, type) {
            if (type !== 'all') return '';
            let inferred = 'courses';
            if (result.due_date) inferred = 'assignments';
            else if (result.availability_from) inferred = 'quizzes';
            const map = {
                'courses': ['Course', 'fa-book', 'type-course'],
                'assignments': ['Assignment', 'fa-tasks', 'type-assignment'],
                'quizzes': ['Quiz', 'fa-question-circle', 'type-quiz'],
            };
            const [label, icon, cls] = map[inferred];
            return `<span class="type-badge ${cls}"><i class="fa-solid ${icon}"></i> ${label}</span>`;
        }

        function displaySearchResults(results, type) {
            if (results.length === 0) {
                searchResults.innerHTML = '<div class="search-no-results">No results found</div>';
                searchResults.classList.remove('hidden');
                return;
            }
            let html = '<div class="search-results-list">';
            results.forEach(result => {
                let href = '#';
                if (type === 'courses' || (result.title && result.code && !result.due_date)) {
                    href = result.id ? `<?php echo e(url('student/courses')); ?>/${result.id}` : '#';
                } else if (type === 'assignments' || result.due_date) {
                    href = result.id ? `<?php echo e(url('student/courses')); ?>` : '#';
                } else if (type === 'quizzes' || result.availability_from) {
                    href = result.id ? `<?php echo e(url('student/courses')); ?>` : '#';
                }
                html += `
                    <a href="${href}" class="search-result-item">
                        <div class="search-result-title">${result.title} ${typeBadge(result, type)}</div>
                        <div class="search-result-details">
                            ${result.code ? `<span>${result.code}</span>` : ''}
                            ${result.course_title ? `<span>${result.course_title}</span>` : ''}
                            ${result.class_code ? `<span>${result.class_code}</span>` : ''}
                            ${result.due_date ? `<span>Due ${result.due_date}</span>` : ''}
                            ${result.availability_from ? `<span>Opens ${result.availability_from}</span>` : ''}
                        </div>
                    </a>
                `;
            });
            html += '</div>';
            searchResults.innerHTML = html;
            searchResults.classList.remove('hidden');
        }

        document.addEventListener('click', (e) => {
            if (!e.target.closest('.search-container')) searchResults.classList.add('hidden');
        });

        // Analytics
        let chartInstances = {};
        function destroyChart(id) {
            if (chartInstances[id]) { chartInstances[id].destroy(); delete chartInstances[id]; }
        }

        async function loadAnalytics() {
            const type = document.getElementById('analyticsType').value;
            const button = document.querySelector('#analyticsType').closest('.analytics-controls').querySelector('.analytics-btn');
            button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Loading...';
            button.disabled = true;
            try {
                const response = await fetch(`<?php echo e(route('student.dashboard.analytics')); ?>?type=${type}`);
                const data = await response.json();
                if (data.success) {
                    displayAnalytics(data.data, type);
                    const toast = document.getElementById('analytics-toast');
                    if (toast) {
                        toast.textContent = `${type.charAt(0).toUpperCase() + type.slice(1)} analytics loaded`;
                        toast.classList.add('show');
                        setTimeout(() => toast.classList.remove('show'), 3000);
                    }
                } else {
                    throw new Error(data.error || 'Failed to load analytics');
                }
            } catch (error) {
                console.error('Analytics error:', error);
                if (typeof LMS !== 'undefined' && LMS.toast) {
                    LMS.toast('Error loading analytics: ' + error.message, 'error');
                } else {
                    alert('Error loading analytics: ' + error.message);
                }
            } finally {
                button.innerHTML = '<i class="fa-solid fa-sync-alt"></i> Load';
                button.disabled = false;
            }
        }

        function displayAnalytics(data, type) {
            const title = document.getElementById('analyticsTitle');
            const container = document.getElementById('analyticsChart');
            const summary = document.getElementById('analyticsSummary');
            destroyChart('analyticsChart');

            if (type === 'grades') {
                title.textContent = 'Grade Trend';
                container.innerHTML = '<canvas id="analyticsCanvas"></canvas>';
                chartInstances['analyticsChart'] = new Chart(document.getElementById('analyticsCanvas'), {
                    type: 'bar',
                    data: {
                        labels: data.labels.length ? data.labels : ['No data'],
                        datasets: [{
                            label: 'Score %',
                            data: data.scores.length ? data.scores : [0],
                            backgroundColor: 'rgba(52, 211, 153, 0.6)',
                            borderColor: '#34d399',
                            borderWidth: 1.5,
                            borderRadius: 6,
                            maxBarThickness: 46
                        }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: { callbacks: { label: (c) => ` ${c.parsed.y}%` } }
                        },
                        scales: {
                            y: { beginAtZero: true, max: 100, ticks: { color: '#98a7c4', callback: (v) => v + '%' }, grid: { color: 'rgba(153,174,214,.12)' } },
                            x: { grid: { display: false }, ticks: { color: '#c7d4ec', maxRotation: 30 } }
                        }
                    }
                });
                summary.innerHTML = `
                    <div class="summary-item"><span class="summary-label">Average</span><span class="summary-value">${data.average}%</span></div>
                    <div class="summary-item"><span class="summary-label">Total Grades</span><span class="summary-value">${data.total_grades}</span></div>
                `;
            } else if (type === 'quizzes') {
                title.textContent = 'Quiz Performance';
                container.innerHTML = '<canvas id="analyticsCanvas"></canvas>';
                chartInstances['analyticsChart'] = new Chart(document.getElementById('analyticsCanvas'), {
                    type: 'bar',
                    data: {
                        labels: data.labels.length ? data.labels : ['No data'],
                        datasets: [{
                            label: 'Score %',
                            data: data.scores.length ? data.scores : [0],
                            backgroundColor: 'rgba(139, 92, 246, 0.55)',
                            borderColor: '#8b5cf6',
                            borderWidth: 1.5,
                            borderRadius: 6,
                            maxBarThickness: 46
                        }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: { callbacks: { label: (c) => ` ${c.parsed.y}%` } }
                        },
                        scales: {
                            y: { beginAtZero: true, max: 100, ticks: { color: '#98a7c4', callback: (v) => v + '%' }, grid: { color: 'rgba(153,174,214,.12)' } },
                            x: { grid: { display: false }, ticks: { color: '#c7d4ec', maxRotation: 30 } }
                        }
                    }
                });
                summary.innerHTML = `
                    <div class="summary-item"><span class="summary-label">Attempts</span><span class="summary-value">${data.total_attempts}</span></div>
                    <div class="summary-item"><span class="summary-label">Best Score</span><span class="summary-value">${data.best_score}%</span></div>
                `;
            } else if (type === 'progress') {
                title.textContent = 'Course Progress';
                container.innerHTML = '<canvas id="analyticsCanvas"></canvas>';
                chartInstances['analyticsChart'] = new Chart(document.getElementById('analyticsCanvas'), {
                    type: 'bar',
                    data: {
                        labels: data.labels.length ? data.labels : ['No data'],
                        datasets: [{
                            label: 'Progress %',
                            data: data.progress.length ? data.progress : [0],
                            backgroundColor: 'rgba(98, 201, 245, 0.55)',
                            borderColor: '#62c9f5',
                            borderWidth: 1.5,
                            borderRadius: 6,
                            maxBarThickness: 46
                        }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false, indexAxis: 'y',
                        plugins: {
                            legend: { display: false },
                            tooltip: { callbacks: { label: (c) => ` ${c.parsed.x}%` } }
                        },
                        scales: {
                            x: { beginAtZero: true, max: 100, ticks: { color: '#98a7c4', callback: (v) => v + '%' }, grid: { color: 'rgba(153,174,214,.12)' } },
                            y: { grid: { display: false }, ticks: { color: '#c7d4ec' } }
                        }
                    }
                });
                summary.innerHTML = `
                    <div class="summary-item"><span class="summary-label">Overall</span><span class="summary-value">${data.overall}%</span></div>
                    <div class="summary-item"><span class="summary-label">Courses</span><span class="summary-value">${data.total_courses}</span></div>
                `;
            }
        }

        // Auto-load grades analytics
        document.addEventListener('DOMContentLoaded', function() {
            const button = document.querySelector('.analytics-btn');
            if (button) { button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Loading...'; button.disabled = true; }
            fetch(`<?php echo e(route('student.dashboard.analytics')); ?>?type=grades`)
                .then(r => r.json())
                .then(data => { if (data.success) displayAnalytics(data.data, 'grades'); })
                .catch(err => console.error('Initial analytics error:', err))
                .finally(() => {
                    if (button) { button.innerHTML = '<i class="fa-solid fa-sync-alt"></i> Load'; button.disabled = false; }
                });
        });
    </script>

    <style>
        /* Quick action row */
        .dash-quick { margin-bottom: 18px; }
        .qa-card { position: relative; overflow: hidden; }
        .qa-card::after { content:""; position:absolute; right:-18px; bottom:-24px; width:64px; height:64px; border-radius:50%; background:radial-gradient(circle,rgba(98,201,245,.16),transparent 70%); pointer-events:none; }
        .qa-card:nth-child(2) i { color:#6ee7b7; background:rgba(16,185,129,.13); }
        .qa-card:nth-child(3) i { color:#fcd34d; background:rgba(251,191,36,.13); }
        .qa-card:nth-child(4) i { color:#fda4af; background:rgba(244,63,94,.13); }

        /* Stat tile accent glows per tile */
        .dash-stat:nth-child(1)::after { background:radial-gradient(circle,rgba(59,130,246,.20),transparent 70%); }
        .dash-stat:nth-child(2)::after { background:radial-gradient(circle,rgba(34,211,238,.18),transparent 70%); }
        .dash-stat:nth-child(3)::after { background:radial-gradient(circle,rgba(139,92,246,.18),transparent 70%); }
        .dash-stat:nth-child(4)::after { background:radial-gradient(circle,rgba(251,191,36,.18),transparent 70%); }
        .dash-stat-sep { color:rgba(153,174,214,.4); margin:0 2px; }

        /* Hero grid overlay */
        .dash-hero { background:
            radial-gradient(circle at 88% 12%, rgba(77,143,240,.38), transparent 42%),
            linear-gradient(135deg, rgba(23,58,168,.88), rgba(10,16,32,.97));
        }
        .dash-hero::after {
            content:""; position:absolute; inset:0;
            background-image:
                linear-gradient(rgba(98,201,245,.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(98,201,245,.05) 1px, transparent 1px);
            background-size: 34px 34px;
            -webkit-mask-image: radial-gradient(circle at 78% 30%, #000 0%, transparent 62%);
            mask-image: radial-gradient(circle at 78% 30%, #000 0%, transparent 62%);
            pointer-events:none;
        }
        .dash-hero > div { position:relative; z-index:2; }

        /* Panel-head accent underline */
        .dash-panel-head { position:relative; }
        .dash-panel-head::after {
            content:""; position:absolute; left:18px; right:auto; bottom:-1px;
            width:52px; height:2px; border-radius:2px;
            background:linear-gradient(90deg,#4d8ff0,#62c9f5);
        }
        .dash-panel-head h4 { position:relative; }

        /* Friendlier empty states */
        .dash-list-empty { padding:34px 14px; }
        .dash-list-empty i { font-size:1.6rem; }

        /* Earned badges list */
        .earned-badges { margin-top: 14px; padding-top: 12px; border-top: 1px solid rgba(153,174,214,.1); }
        .earned-badges-title { display: flex; align-items: center; gap: 6px; color: var(--dash-muted, #98a7c4); font-size: .66rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
        .earned-badges-title i { color: #fcd34d; }
        .earned-badges-row { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
        .earned-badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 5px 11px; border-radius: 999px;
            background: rgba(139,92,246,.12); border: 1px solid rgba(139,92,246,.22);
            color: #c4b5fd; font-size: .7rem; font-weight: 700;
        }
        .earned-badge i { font-size: .72rem; }

        /* Search */
        .dash-search { margin: 20px 0; }
        .search-container { position: relative; max-width: 800px; margin: 0 auto; }
        .search-input-wrapper {
            position: relative; display: flex; align-items: center;
            background: var(--dash-surface, #151c2c);
            border: 1px solid var(--dash-line, rgba(153,174,214,.18));
            border-radius: 12px; padding: 12px 16px;
            box-shadow: 0 2px 8px rgba(3,8,20,.25);
            transition: border-color .2s, box-shadow .2s;
        }
        .search-input-wrapper:focus-within { border-color: rgba(98,201,245,.45); box-shadow: 0 0 0 3px rgba(77,143,240,.15); }
        .search-icon { color: var(--dash-muted, #98a7c4); margin-right: 12px; }
        .search-input { flex: 1; border: none; outline: none; font-size: 16px; padding: 8px 0; background: transparent; color: var(--dash-text, #eef4ff); }
        .search-input::placeholder { color: #7f91b0; }
        .search-type {
            border: 1px solid var(--dash-line, rgba(153,174,214,.18));
            background: var(--dash-bg, #101625); color: var(--dash-text, #eef4ff);
            padding: 8px 12px; border-radius: 8px; margin-left: 12px; cursor: pointer; font-size: 14px;
        }
        .search-results {
            position: absolute; top: 100%; left: 0; right: 0;
            background: var(--dash-surface-raised, #1b2437);
            border: 1px solid var(--dash-line, rgba(153,174,214,.18));
            border-radius: 12px; margin-top: 8px;
            box-shadow: 0 12px 32px rgba(3,8,20,.45);
            max-height: 400px; overflow-y: auto; z-index: 100;
        }
        .search-results.hidden { display: none; }
        .search-results-list { padding: 8px 0; }
        .search-result-item {
            display: block; padding: 12px 16px; border-bottom: 1px solid rgba(153,174,214,.08);
            cursor: pointer; transition: background-color 0.2s; text-decoration: none; color: inherit;
        }
        .search-result-item:hover { background-color: rgba(77,143,240,.1); }
        .search-result-title { font-weight: 600; color: var(--dash-text, #eef4ff); margin-bottom: 4px; }
        .search-result-details { font-size: 14px; color: var(--dash-muted, #98a7c4); display: flex; gap: 12px; flex-wrap: wrap; }
        .search-no-results, .search-loading { padding: 20px; text-align: center; color: var(--dash-muted, #98a7c4); }
        .type-badge {
            display: inline-flex; align-items: center; gap: 5px; padding: 2px 9px; border-radius: 999px;
            font-size: 11px; font-weight: 750; letter-spacing: .03em; margin-left: 8px; vertical-align: middle;
        }
        .type-badge.type-course { background: rgba(59,130,246,.14); color: #93c5fd; }
        .type-badge.type-assignment { background: rgba(251,191,36,.13); color: #fcd34d; }
        .type-badge.type-quiz { background: rgba(139,92,246,.14); color: #c4b5fd; }

        /* Clickable dash-list items */
        .dash-list-link {
            display: flex; align-items: center; gap: 12px; flex: 1;
            min-width: 0; text-decoration: none; color: inherit;
        }
        .dash-list-item:hover .dash-list-link .dash-list-title { color: var(--bcp-cyan-400, #62c9f5); }

        /* Overdue strip */
        .overdue-strip {
            margin: 0 0 22px; padding: 16px 18px;
            background: rgba(244,63,94,.08);
            border: 1px solid rgba(244,63,94,.25);
            border-radius: 14px;
        }
        .overdue-head { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
        .overdue-icon {
            width: 40px; height: 40px; display: grid; place-items: center;
            background: rgba(244,63,94,.15); color: #fb7185; border-radius: 10px; font-size: 18px;
        }
        .overdue-head h4 { margin: 0; color: #fda4af; font-size: .9rem; font-weight: 800; }
        .overdue-head p { margin: 2px 0 0; color: var(--dash-muted, #98a7c4); font-size: .78rem; }
        .overdue-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 6px; }
        .overdue-list a {
            display: flex; justify-content: space-between; align-items: center; gap: 12px;
            padding: 8px 12px; border-radius: 8px; background: rgba(244,63,94,.06);
            text-decoration: none; color: inherit; transition: background .16s;
        }
        .overdue-list a:hover { background: rgba(244,63,94,.12); }
        .od-name { font-size: .82rem; font-weight: 700; color: #eef4ff; }
        .od-meta { font-size: .72rem; color: #fb7185; font-weight: 600; }

        /* AI-assisted performance assessment */
        .student-assessment {
            margin: 0 0 22px;
            overflow: hidden;
            border: 1px solid var(--dash-line, rgba(153,174,214,.18));
            border-radius: 18px;
            background: var(--dash-surface, #151c2c);
            box-shadow: 0 14px 34px rgba(3,8,20,.16);
        }
        .student-assessment-on_track { --assessment-accent:#34d399; --assessment-soft:rgba(52,211,153,.12); --assessment-border:rgba(52,211,153,.28); }
        .student-assessment-at_risk { --assessment-accent:#fbbf24; --assessment-soft:rgba(251,191,36,.12); --assessment-border:rgba(251,191,36,.30); }
        .student-assessment-insufficient_data { --assessment-accent:#62c9f5; --assessment-soft:rgba(98,201,245,.12); --assessment-border:rgba(98,201,245,.28); }
        .student-assessment-header {
            display:flex; align-items:flex-start; justify-content:space-between; gap:18px;
            padding:20px 22px; border-bottom:1px solid var(--assessment-border);
            background:linear-gradient(110deg,var(--assessment-soft),transparent 68%);
        }
        .student-assessment-title-wrap { display:flex; align-items:flex-start; gap:12px; min-width:0; }
        .student-assessment-icon {
            display:grid; flex:0 0 auto; place-items:center; width:38px; height:38px;
            border:1px solid var(--assessment-border); border-radius:12px;
            color:var(--assessment-accent); background:var(--assessment-soft);
        }
        .student-assessment-eyebrow { display:block; color:var(--assessment-accent); font-size:.62rem; font-weight:850; letter-spacing:.11em; text-transform:uppercase; }
        .student-assessment h2 { margin:3px 0 0; color:var(--dash-text,#eef4ff); font-size:1.03rem; letter-spacing:-.02em; }
        .student-assessment-header p { max-width:680px; margin:6px 0 0; color:var(--dash-muted,#98a7c4); font-size:.76rem; line-height:1.5; }
        .student-assessment-status { display:flex; flex:0 0 auto; flex-direction:column; align-items:flex-end; gap:4px; text-align:right; }
        .student-assessment-status strong { color:var(--assessment-accent); font-size:.72rem; letter-spacing:.09em; }
        .student-assessment-status span { color:var(--dash-muted,#98a7c4); font-size:.65rem; }
        .student-assessment-body { padding:18px 22px 22px; }
        .student-assessment-score { display:flex; align-items:center; gap:16px; padding:14px; border:1px solid var(--assessment-border); border-radius:14px; background:var(--assessment-soft); }
        .student-risk-meter {
            display:grid; flex:0 0 auto; place-items:center; width:76px; height:76px; border-radius:50%;
            background:conic-gradient(var(--assessment-accent) var(--risk-score),rgba(153,174,214,.16) 0);
        }
        .student-risk-meter > span { display:grid; place-items:center; align-content:center; width:58px; height:58px; border-radius:50%; background:var(--dash-surface,#151c2c); text-align:center; }
        .student-risk-meter strong { color:var(--dash-text,#eef4ff); font-size:1.15rem; line-height:1; }
        .student-risk-meter small { margin-top:3px; color:var(--dash-muted,#98a7c4); font-size:.52rem; text-transform:uppercase; letter-spacing:.05em; }
        .student-assessment-score > div:last-child { min-width:0; }
        .student-assessment-score > div:last-child strong { color:var(--dash-text,#eef4ff); font-size:.83rem; }
        .student-assessment-score > div:last-child p { margin:5px 0 0; color:var(--dash-muted,#98a7c4); font-size:.7rem; line-height:1.45; }
        .student-assessment-signals { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:9px; margin-top:14px; }
        .student-signal { display:flex; align-items:flex-start; gap:8px; min-width:0; padding:11px; border:1px solid var(--dash-line,rgba(153,174,214,.18)); border-radius:11px; background:rgba(77,143,240,.035); }
        .student-signal-dot { flex:0 0 auto; width:7px; height:7px; margin-top:4px; border-radius:50%; background:var(--dash-muted,#98a7c4); box-shadow:0 0 0 3px rgba(153,174,214,.10); }
        .student-signal-good .student-signal-dot { background:#34d399; box-shadow:0 0 0 3px rgba(52,211,153,.14); }
        .student-signal-watch .student-signal-dot { background:#fbbf24; box-shadow:0 0 0 3px rgba(251,191,36,.14); }
        .student-signal-risk .student-signal-dot { background:#fb7185; box-shadow:0 0 0 3px rgba(251,113,133,.14); }
        .student-signal div { display:flex; min-width:0; flex-direction:column; gap:3px; }
        .student-signal strong { overflow:hidden; color:var(--dash-muted,#98a7c4); font-size:.61rem; text-overflow:ellipsis; white-space:nowrap; text-transform:uppercase; letter-spacing:.04em; }
        .student-signal span { color:var(--dash-text,#eef4ff); font-size:.83rem; font-weight:800; }
        .student-signal small { color:var(--dash-muted,#98a7c4); font-size:.61rem; line-height:1.3; }
        .student-assessment-columns { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:18px; margin-top:18px; }
        .student-assessment-columns h3 { display:flex; align-items:center; gap:7px; margin:0 0 10px; color:var(--dash-text,#eef4ff); font-size:.78rem; }
        .student-assessment-columns h3 i { color:var(--assessment-accent); }
        .student-assessment-list { display:flex; flex-direction:column; gap:8px; margin:0; padding:0; list-style:none; }
        .student-assessment-list li { display:flex; align-items:flex-start; gap:8px; color:var(--dash-muted,#98a7c4); font-size:.7rem; line-height:1.45; }
        .student-assessment-list li i { flex:0 0 auto; margin-top:2px; color:var(--assessment-accent); }
        .student-recommendations { display:flex; flex-direction:column; gap:8px; }
        .student-recommendation { display:flex; align-items:flex-start; gap:9px; padding:9px 10px; border:1px solid var(--dash-line,rgba(153,174,214,.18)); border-radius:10px; background:rgba(77,143,240,.035); }
        .student-recommendation > span { display:grid; flex:0 0 auto; place-items:center; width:26px; height:26px; border-radius:8px; color:var(--assessment-accent); background:var(--assessment-soft); font-size:.68rem; }
        .student-recommendation > div { display:flex; min-width:0; flex-direction:column; gap:3px; }
        .student-recommendation strong { color:var(--dash-text,#eef4ff); font-size:.7rem; }
        .student-recommendation small { color:var(--dash-muted,#98a7c4); font-size:.64rem; line-height:1.35; }
        body.light-mode .student-assessment { box-shadow:0 14px 34px rgba(31,52,88,.10); }
        body.light-mode .student-signal,.light-mode .student-recommendation { background:rgba(36,73,198,.035); }
        .student-assessment:focus-within { border-color:var(--assessment-accent); }

        /* Analytics */
        .dash-analytics { margin: 30px 0; padding: 20px; background: var(--dash-surface, #151c2c); border: 1px solid var(--dash-line, rgba(153,174,214,.18)); border-radius: 16px; box-shadow: var(--bcp-shadow, 0 18px 40px rgba(3,8,20,.3)); }
        .dash-analytics .dash-panel-head { padding: 0 0 16px; border-bottom: 1px solid var(--dash-line, rgba(153,174,214,.18)); margin-bottom: 20px; }
        .analytics-toast {
            display: none; margin-bottom: 16px; padding: 10px 14px; border-radius: 10px;
            background: rgba(16,185,129,.12); border: 1px solid rgba(16,185,129,.3); color: #6ee7b7; font-size: .8rem; font-weight: 650;
        }
        .analytics-toast.show { display: block; animation: fadeInOut 3s ease; }
        @keyframes fadeInOut { 0% { opacity: 0; transform: translateY(-4px); } 10%, 85% { opacity: 1; transform: translateY(0); } 100% { opacity: 0; } }
        .analytics-controls { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
        .analytics-select {
            padding: 8px 12px; border: 1px solid var(--dash-line, rgba(153,174,214,.18)); border-radius: 8px;
            background: var(--dash-bg, #101625); color: var(--dash-text, #eef4ff); cursor: pointer; font-size: 14px;
        }
        .analytics-btn {
            padding: 8px 16px; background: linear-gradient(135deg, #2449c6, #173aa8); color: white;
            border: 1px solid #3159d1; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 650;
            transition: background-color 0.2s, transform .16s;
        }
        .analytics-btn:hover:not(:disabled) { background: linear-gradient(135deg, #4d8ff0, #2449c6); transform: translateY(-1px); }
        .analytics-btn:disabled { opacity: .7; cursor: default; }
        .analytics-content { margin-top: 20px; }
        .analytics-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px;
        }
        .analytics-card {
            background: rgba(77,143,240,.04); border: 1px solid var(--dash-line, rgba(153,174,214,.18));
            border-radius: 12px; padding: 20px;
        }
        .analytics-card-wide { grid-column: 1 / -1; }
        .analytics-card h5 { margin: 0 0 16px 0; font-size: 15px; font-weight: 700; color: var(--dash-text, #eef4ff); display: flex; align-items: center; gap: 8px; }
        .analytics-card h5 i { color: var(--bcp-cyan-400, #62c9f5); }
        .chart-container { position: relative; height: 280px; display: flex; flex-direction: column; align-items: center; justify-content: center; }
        .chart-container canvas { max-height: 100%; }
        .chart-placeholder { text-align: center; color: var(--dash-muted, #98a7c4); }
        .chart-placeholder i { font-size: 48px; margin-bottom: 12px; color: rgba(153,174,214,.35); }
        .chart-placeholder p { margin: 0; font-size: 14px; }
        .analytics-summary { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; width: 100%; }
        .summary-item { display: flex; flex-direction: column; gap: 4px; }
        .summary-label { font-size: 11px; color: var(--dash-muted, #98a7c4); font-weight: 650; text-transform: uppercase; letter-spacing: .05em; }
        .summary-value { font-size: 22px; font-weight: 800; color: var(--dash-text, #eef4ff); }

        @media (max-width: 900px) {
            .student-assessment-signals { grid-template-columns:repeat(3,minmax(0,1fr)); }
        }
        @media (max-width: 640px) {
            .analytics-grid { grid-template-columns: 1fr; }
            .dash-analytics { padding: 16px; }
            .overdue-list a { flex-direction: column; align-items: flex-start; gap: 4px; }
            .qa-grid { grid-template-columns: 1fr; }
            .continue-card { flex-wrap: wrap; }
            .continue-card .cc-progress { width: 100%; }
            .student-assessment-header { flex-direction:column; }
            .student-assessment-status { align-items:flex-start; text-align:left; }
            .student-assessment-body { padding:14px; }
            .student-assessment-signals { grid-template-columns:repeat(2,minmax(0,1fr)); }
            .student-assessment-columns { grid-template-columns:1fr; gap:14px; }
        }
        @media (max-width: 420px) {
            .student-assessment-title-wrap { gap:9px; }
            .student-assessment-header { padding:16px; }
            .student-assessment-score { align-items:flex-start; flex-direction:column; }
            .student-assessment-signals { grid-template-columns:1fr; }
        }
    </style>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.student', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\student\dashboard.blade.php ENDPATH**/ ?>