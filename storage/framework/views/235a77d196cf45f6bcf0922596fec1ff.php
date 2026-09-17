

<?php $__env->startSection('title', 'Record Attendance'); ?>
<?php $activeNav = 'attendance'; ?>

<?php $__env->startSection('content'); ?>
<div class="user-page">
    <?php if (isset($component)) { $__componentOriginal8fc5d82814dad270c8dc67128a2a98d5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-page-header','data' => ['title' => 'Record Attendance','subtitle' => 'Mark attendance for students in a class session.','icon' => 'fa-clipboard-user']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Record Attendance','subtitle' => 'Mark attendance for students in a class session.','icon' => 'fa-clipboard-user']); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <a href="<?php echo e(route('admin.attendance.index')); ?>" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
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

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-calendar-check"></i> Session Details</h3>
        </div>
        <div class="user-panel-body">
            <form action="<?php echo e(route('admin.attendance.store')); ?>" method="POST">
                <?php echo csrf_field(); ?>

                <div class="form-grid">
                    <div class="form-field">
                        <label>Class <span class="required">*</span></label>
                        <select name="class_id" required id="classSelect">
                            <option value="">— Select class —</option>
                            <?php $__currentLoopData = $classes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $class): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($class->id); ?>" <?php if(old('class_id', $preselectedClassId) == $class->id): echo 'selected'; endif; ?>><?php echo e($class->code); ?> — <?php echo e($class->course?->title); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <span class="field-error"><?php echo e($errors->first('class_id')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Virtual Class (optional)</label>
                        <select name="virtual_class_id">
                            <option value="">— None —</option>
                            <?php $__currentLoopData = $virtualClasses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $virtualClass): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($virtualClass->id); ?>" <?php if(old('virtual_class_id') == $virtualClass->id): echo 'selected'; endif; ?>><?php echo e($virtualClass->title); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <span class="field-error"><?php echo e($errors->first('virtual_class_id')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Date <span class="required">*</span></label>
                        <input type="date" name="attendance_date" value="<?php echo e(old('attendance_date', now()->format('Y-m-d'))); ?>" required>
                        <span class="field-error"><?php echo e($errors->first('attendance_date')); ?></span>
                    </div>

                    <div class="form-field">
                        <label>Session Title</label>
                        <input type="text" name="session_title" value="<?php echo e(old('session_title')); ?>" placeholder="e.g. Week 3 lecture">
                        <span class="field-error"><?php echo e($errors->first('session_title')); ?></span>
                    </div>
                </div>

                <div class="form-section">
                    <div class="modal-section-title">
                        <i class="fa-solid fa-users"></i> Students
                    </div>

                    <?php if($students->count() > 0): ?>
                        <div class="user-table-wrap">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Status</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $__currentLoopData = $students; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $student): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr>
                                            <td>
                                                <div class="user-info">
                                                    <div class="user-name"><?php echo e($student->name); ?></div>
                                                    <div class="user-email"><?php echo e($student->email); ?></div>
                                                </div>
                                                <input type="hidden" name="student_ids[]" value="<?php echo e($student->id); ?>">
                                            </td>
                                            <td>
                                                <select name="statuses[]" aria-label="Attendance status for <?php echo e($student->name); ?>">
                                                    <?php $__currentLoopData = ['present' => 'Present', 'late' => 'Late', 'absent' => 'Absent', 'excused' => 'Excused']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <option value="<?php echo e($value); ?>" <?php if(old("records.{$index}.status", 'present') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" name="notes[]" value="<?php echo e(old('notes.'.$index)); ?>" placeholder="Optional note" aria-label="Notes for <?php echo e($student->name); ?>">
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <?php if (isset($component)) { $__componentOriginalb3aac3110999f7435f4950a3fe8df251 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb3aac3110999f7435f4950a3fe8df251 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-empty-state','data' => ['icon' => 'fa-users','title' => 'No students available','description' => 'Select a class with enrolled students, then save the page to load the roster.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-empty-state'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => 'fa-users','title' => 'No students available','description' => 'Select a class with enrolled students, then save the page to load the roster.']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb3aac3110999f7435f4950a3fe8df251)): ?>
<?php $attributes = $__attributesOriginalb3aac3110999f7435f4950a3fe8df251; ?>
<?php unset($__attributesOriginalb3aac3110999f7435f4950a3fe8df251); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb3aac3110999f7435f4950a3fe8df251)): ?>
<?php $component = $__componentOriginalb3aac3110999f7435f4950a3fe8df251; ?>
<?php unset($__componentOriginalb3aac3110999f7435f4950a3fe8df251); ?>
<?php endif; ?>
                    <?php endif; ?>
                </div>

                <div class="form-actions user-actions">
                    <a href="<?php echo e(route('admin.attendance.index')); ?>" class="btn btn-secondary">
                        <i class="fa-solid fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-save"></i> Save Attendance
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\admin\attendance\create.blade.php ENDPATH**/ ?>