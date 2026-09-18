<?php $__env->startSection('title', 'Class Details'); ?>

<?php
    $activeNav = 'classes';
    $enrolledCount = (int) ($class->enrolled_count ?? $class->enrollments->where('status', '!=', 'dropped')->count());
    $capacity = $class->capacity ?? $class->max_students;
    $availableSeats = $capacity ? max(0, $capacity - $enrolledCount) : null;
    $courseTitle = $class->course?->title ?? $class->course?->name ?? 'Course not assigned';
?>

<?php $__env->startSection('page-title-bar'); ?>
    <div class="page-title-bar">
        <h2 class="page-title"><i class="fa-solid fa-chalkboard-user"></i> <?php echo e($class->name); ?></h2>
        <div class="page-actions">
            <a href="<?php echo e(route('admin.classes.edit', $class)); ?>" class="btn btn-primary"><i class="fa-solid fa-pen-to-square"></i> Edit class</a>
            <a href="<?php echo e(route('admin.classes.index')); ?>" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <style>
        .class-details { max-width:1100px; margin:0 auto; }
        .class-details .detail-hero { display:flex; align-items:center; gap:15px; margin-bottom:18px; padding:21px 23px; border:1px solid var(--dash-line); border-radius:16px; background:radial-gradient(circle at 90% 10%,rgba(98,201,245,.24),transparent 40%),linear-gradient(135deg,rgba(23,58,168,.92),rgba(10,16,32,.97)); box-shadow:var(--bcp-shadow); }
        .class-details .detail-icon { display:grid; place-items:center; width:50px; height:50px; flex:none; border-radius:14px; color:#fff; background:linear-gradient(135deg,#22d3ee,#0e7490); box-shadow:0 10px 24px rgba(34,211,238,.26); }
        .class-details .detail-hero h3 { margin:0; color:#fff; font-size:1.08rem; }
        .class-details .detail-hero p { margin:4px 0 0; color:#c8d9f8; font-size:.76rem; }
        .class-details .detail-code { margin-left:auto; padding:7px 12px; border:1px solid rgba(98,201,245,.28); border-radius:999px; color:#cceeff; background:rgba(98,201,245,.1); font-size:.72rem; font-weight:800; }
        .class-details .detail-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; margin-bottom:18px; }
        .class-details .metric { padding:15px; background:var(--dash-surface); border:1px solid var(--dash-line); border-radius:13px; box-shadow:var(--bcp-shadow); }
        .class-details .metric-label { display:block; color:var(--dash-muted); font-size:.64rem; font-weight:800; letter-spacing:.08em; text-transform:uppercase; }
        .class-details .metric-value { display:block; margin-top:7px; color:var(--dash-text); font-size:1rem; font-weight:800; }
        .class-details .detail-card { overflow:hidden; margin-bottom:18px; background:var(--dash-surface); border:1px solid var(--dash-line); border-radius:15px; box-shadow:var(--bcp-shadow); }
        .class-details .detail-card-head { display:flex; align-items:center; justify-content:space-between; gap:10px; padding:15px 19px; border-bottom:1px solid var(--dash-line); background:linear-gradient(90deg,rgba(77,143,240,.1),transparent); }
        .class-details .detail-card-head h3 { display:flex; align-items:center; gap:8px; margin:0; color:var(--dash-text); font-size:.8rem; letter-spacing:.05em; text-transform:uppercase; }
        .class-details .detail-card-head h3 i { color:var(--dash-cyan); }
        .class-details .detail-card-body { padding:18px 19px; }
        .class-details .meta-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:0 25px; }
        .class-details .meta-row { display:flex; justify-content:space-between; gap:15px; padding:12px 0; border-bottom:1px solid var(--dash-line); font-size:.76rem; }
        .class-details .meta-row dt { color:var(--dash-muted); }
        .class-details .meta-row dd { margin:0; color:var(--dash-text); font-weight:700; text-align:right; }
        .class-details .description { margin-top:18px; padding:14px; color:var(--dash-text); background:rgba(77,143,240,.05); border:1px solid var(--dash-line); border-radius:10px; font-size:.78rem; line-height:1.55; }
        .class-details .status-chip { display:inline-flex; align-items:center; gap:5px; padding:4px 9px; border-radius:999px; font-size:.65rem; font-weight:800; }
        .class-details .status-active { color:#6ee7b7; background:rgba(16,185,129,.13); }
        .class-details .status-inactive { color:#fcd34d; background:rgba(251,191,36,.13); }
        .class-details .status-cancelled { color:#fda4af; background:rgba(244,63,94,.13); }
        .class-details .table-wrap { overflow-x:auto; }
        .class-details table { width:100%; border-collapse:collapse; min-width:680px; }
        .class-details th { padding:10px 14px; color:var(--dash-muted); background:rgba(77,143,240,.05); border-bottom:1px solid var(--dash-line); font-size:.64rem; letter-spacing:.07em; text-align:left; text-transform:uppercase; }
        .class-details td { padding:12px 14px; color:var(--dash-text); border-bottom:1px solid var(--dash-line); font-size:.75rem; }
        .class-details tr:last-child td { border-bottom:0; }
        .class-details .empty-row { padding:28px; color:var(--dash-muted); text-align:center; }
        @media(max-width:780px) { .class-details .detail-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } .class-details .meta-grid { grid-template-columns:1fr; } }
        @media(max-width:520px) { .class-details .detail-hero { align-items:flex-start; } .class-details .detail-code { display:none; } .class-details .detail-grid { grid-template-columns:1fr 1fr; } }
    </style>

    <div class="class-details">
        <section class="detail-hero">
            <span class="detail-icon"><i class="fa-solid fa-chalkboard-user"></i></span>
            <div>
                <h3><?php echo e($class->name); ?></h3>
                <p><?php echo e($courseTitle); ?> · <?php echo e($class->academicPeriod?->name ?? 'No academic period'); ?></p>
            </div>
            <span class="detail-code"><?php echo e($class->code); ?></span>
        </section>

        <div class="detail-grid">
            <div class="metric"><span class="metric-label">Enrolled</span><span class="metric-value"><?php echo e($enrolledCount); ?><?php echo e($capacity ? ' / '.$capacity : ''); ?></span></div>
            <div class="metric"><span class="metric-label">Available seats</span><span class="metric-value"><?php echo e($availableSeats ?? '—'); ?></span></div>
            <div class="metric"><span class="metric-label">Instructor</span><span class="metric-value"><?php echo e($class->instructor?->full_name ?? 'Unassigned'); ?></span></div>
            <div class="metric"><span class="metric-label">Status</span><span class="metric-value"><span class="status-chip status-<?php echo e($class->status); ?>"><i class="fa-solid fa-circle"></i> <?php echo e(ucfirst($class->status)); ?></span></span></div>
        </div>

        <section class="detail-card">
            <div class="detail-card-head"><h3><i class="fa-solid fa-circle-info"></i> Class information</h3></div>
            <div class="detail-card-body">
                <dl class="meta-grid">
                    <div class="meta-row"><dt>Course</dt><dd><?php echo e($courseTitle); ?></dd></div>
                    <div class="meta-row"><dt>Academic period</dt><dd><?php echo e($class->academicPeriod?->code ?? '—'); ?></dd></div>
                    <div class="meta-row"><dt>Instructor email</dt><dd><?php echo e($class->instructor?->email ?? '—'); ?></dd></div>
                    <div class="meta-row"><dt>Schedule</dt><dd><?php echo e($class->schedule ?: 'Not specified'); ?></dd></div>
                    <div class="meta-row"><dt>Room / location</dt><dd><?php echo e($class->room ?: 'Not specified'); ?></dd></div>
                    <div class="meta-row"><dt>Enrollment capacity</dt><dd><?php echo e($capacity ?? 'Unlimited'); ?></dd></div>
                </dl>
                <?php if($class->description): ?>
                    <div class="description"><?php echo e($class->description); ?></div>
                <?php endif; ?>
            </div>
        </section>

        <section class="detail-card">
            <div class="detail-card-head"><h3><i class="fa-solid fa-users"></i> Enrolled students</h3><span class="status-chip status-active"><?php echo e($class->enrollments->count()); ?> records</span></div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Student</th><th>Email</th><th>Status</th><th>Enrolled</th><th>Grade</th></tr></thead>
                    <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $class->enrollments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $enrollment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php ($statusClass = in_array($enrollment->status, ['active','completed']) ? 'status-active' : ($enrollment->status === 'dropped' ? 'status-cancelled' : 'status-inactive')); ?>
                        <tr>
                            <td><?php echo e($enrollment->student?->full_name ?? '—'); ?></td>
                            <td><?php echo e($enrollment->student?->email ?? '—'); ?></td>
                            <td><span class="status-chip <?php echo e($statusClass); ?>"><?php echo e(ucfirst($enrollment->status)); ?></span></td>
                            <td><?php echo e($enrollment->enrolled_at?->format('M d, Y') ?? '—'); ?></td>
                            <td><?php echo e($enrollment->final_grade ?? '—'); ?></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="5" class="empty-row">No students enrolled in this class yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin-sms', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\admin\classes\show.blade.php ENDPATH**/ ?>