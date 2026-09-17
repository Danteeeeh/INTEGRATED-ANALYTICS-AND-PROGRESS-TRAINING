<?php $__env->startSection('title', 'Search'); ?>
<?php
    $activeNav = '';
    $resultTotal = ($courses?->count() ?? 0) + ($classes?->count() ?? 0) + ($lessons?->count() ?? 0) + ($materials?->count() ?? 0);
?>

<?php $__env->startSection('content'); ?>
    <section class="search-page-hero">
        <div>
            <span class="dash-hero-kicker"><i class="fa-solid fa-magnifying-glass"></i> Command search</span>
            <h1><?php echo e($search ? 'Search results' : 'Search the LMS'); ?></h1>
            <p><?php echo e($search ? 'Showing matches for “' . $search . '”.' : 'Find courses, classes, lessons, and learning materials from one place.'); ?></p>
        </div>
        <span class="search-result-count"><?php echo e($resultTotal); ?> <?php echo e(Str::plural('result', $resultTotal)); ?></span>
    </section>

    <form class="search-page-form" action="<?php echo e(route('admin.search')); ?>" method="GET" role="search">
        <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
        <input type="search" name="search" value="<?php echo e($search); ?>" placeholder="Search courses, classes, lessons, or materials..." aria-label="Search LMS" autofocus>
        <button type="submit"><i class="fa-solid fa-arrow-right"></i> Search</button>
    </form>

    <?php if(!$search): ?>
        <section class="search-empty-state">
            <i class="fa-solid fa-compass"></i>
            <h2>Start searching</h2>
            <p>Type a keyword above to search the academic content available to you.</p>
        </section>
    <?php else: ?>
        <div class="search-results-grid">
            <section class="search-result-panel">
                <header><h2><i class="fa-solid fa-book"></i> Courses</h2><span><?php echo e($courses->count()); ?></span></header>
                <?php $__empty_1 = true; $__currentLoopData = $courses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $course): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <a class="search-result-item" href="<?php echo e(route('admin.courses.show', $course)); ?>">
                        <span class="search-result-icon blue"><i class="fa-solid fa-book-open"></i></span>
                        <span><strong><?php echo e($course->title); ?></strong><small><?php echo e($course->code); ?> · <?php echo e($course->status); ?></small></span>
                        <i class="fa-solid fa-chevron-right arrow"></i>
                    </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <p class="search-no-results">No courses matched.</p>
                <?php endif; ?>
            </section>

            <section class="search-result-panel">
                <header><h2><i class="fa-solid fa-school"></i> Classes</h2><span><?php echo e($classes->count()); ?></span></header>
                <?php $__empty_1 = true; $__currentLoopData = $classes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $class): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <a class="search-result-item" href="<?php echo e(route('admin.classes.show', $class)); ?>">
                        <span class="search-result-icon cyan"><i class="fa-solid fa-school"></i></span>
                        <span><strong><?php echo e($class->code); ?></strong><small><?php echo e($class->course?->title ?? 'Class'); ?> · <?php echo e($class->schedule ?? 'No schedule'); ?></small></span>
                        <i class="fa-solid fa-chevron-right arrow"></i>
                    </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <p class="search-no-results">No classes matched.</p>
                <?php endif; ?>
            </section>

            <section class="search-result-panel">
                <header><h2><i class="fa-solid fa-book-open-reader"></i> Lessons</h2><span><?php echo e($lessons->count()); ?></span></header>
                <?php $__empty_1 = true; $__currentLoopData = $lessons; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $lesson): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <a class="search-result-item" href="<?php echo e(route('admin.courses.show', $lesson->module?->course)); ?>">
                        <span class="search-result-icon violet"><i class="fa-solid fa-file-lines"></i></span>
                        <span><strong><?php echo e($lesson->title); ?></strong><small><?php echo e($lesson->module?->course?->title ?? 'Course lesson'); ?></small></span>
                        <i class="fa-solid fa-chevron-right arrow"></i>
                    </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <p class="search-no-results">No lessons matched.</p>
                <?php endif; ?>
            </section>

            <section class="search-result-panel">
                <header><h2><i class="fa-solid fa-paperclip"></i> Materials</h2><span><?php echo e($materials->count()); ?></span></header>
                <?php $__empty_1 = true; $__currentLoopData = $materials; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $material): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <a class="search-result-item" href="<?php echo e(route('admin.courses.show', $material->lesson?->module?->course)); ?>">
                        <span class="search-result-icon amber"><i class="fa-solid fa-paperclip"></i></span>
                        <span><strong><?php echo e($material->title); ?></strong><small><?php echo e($material->lesson?->title ?? 'Lesson material'); ?></small></span>
                        <i class="fa-solid fa-chevron-right arrow"></i>
                    </a>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <p class="search-no-results">No materials matched.</p>
                <?php endif; ?>
            </section>
        </div>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\admin\search.blade.php ENDPATH**/ ?>