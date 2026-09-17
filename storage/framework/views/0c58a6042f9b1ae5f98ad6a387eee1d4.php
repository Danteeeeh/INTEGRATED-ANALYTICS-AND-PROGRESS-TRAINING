<?php $__env->startSection('title', 'Gradebook'); ?>
<?php
    $activeNav = 'gradebook';
    $pageTitle = 'Class Gradebook';
    $pageIcon = '<i class="fa-solid fa-chart-bar"></i>';
?>

<?php $__env->startSection('content'); ?>
<div class="user-page">
    <?php if (isset($component)) { $__componentOriginal8fc5d82814dad270c8dc67128a2a98d5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8fc5d82814dad270c8dc67128a2a98d5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-page-header','data' => ['title' => ''.e($class->course->title).' — Gradebook','subtitle' => 'Enter grades and review student performance.','icon' => 'fa-chart-bar']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => ''.e($class->course->title).' — Gradebook','subtitle' => 'Enter grades and review student performance.','icon' => 'fa-chart-bar']); ?>
         <?php $__env->slot('actions', null, []); ?> 
            <button type="button" onclick="exportGrades()" class="btn btn-secondary">
                <i class="fa-solid fa-download"></i> Export
            </button>
            <button type="button" onclick="showBulkGradeModal()" class="btn btn-primary">
                <i class="fa-solid fa-edit"></i> Bulk Grade
            </button>
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

    <!-- Grade Overview Stats -->
    <div class="user-stat-grid">
        <?php if (isset($component)) { $__componentOriginal539478ab10802a5a905ec5e50f354013 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal539478ab10802a5a905ec5e50f354013 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-stat-card','data' => ['label' => 'Total Students','value' => ''.e($students->total()).'','icon' => 'fa-users']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Total Students','value' => ''.e($students->total()).'','icon' => 'fa-users']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-stat-card','data' => ['label' => 'Grade Items','value' => ''.e($gradeItems->count()).'','icon' => 'fa-tasks']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Grade Items','value' => ''.e($gradeItems->count()).'','icon' => 'fa-tasks']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-stat-card','data' => ['label' => 'Class Average','value' => ''.e(number_format($gradeItems->flatMap(fn ($item) => $item->grades)->avg('score_percent') ?? 0, 1)).'%','icon' => 'fa-chart-line']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Class Average','value' => ''.e(number_format($gradeItems->flatMap(fn ($item) => $item->grades)->avg('score_percent') ?? 0, 1)).'%','icon' => 'fa-chart-line']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-stat-card','data' => ['label' => 'Categories','value' => ''.e($gradeCategories->count()).'','icon' => 'fa-layer-group']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-stat-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Categories','value' => ''.e($gradeCategories->count()).'','icon' => 'fa-layer-group']); ?>
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

    <!-- Grade Categories -->
    <?php if($gradeCategories->isNotEmpty()): ?>
        <div class="user-panel gradebook-panel">
            <div class="user-panel-head"><h3><i class="fa-solid fa-layer-group"></i> Grade Categories</h3></div>
            <div class="user-panel-body" style="padding-top:15px">
                <?php $__currentLoopData = $gradeCategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="user-toolbar" style="margin-bottom:10px;justify-content:space-between">
                        <div>
                            <strong><?php echo e($category->name); ?></strong>
                            <div class="user-email"><?php echo e($category->items->count()); ?> items • <?php echo e($category->weight); ?>% weight</div>
                        </div>
                        <div style="text-align:right">
                            <div style="color:var(--user-accent);font-weight:700"><?php echo e(number_format($category->items->flatMap(fn ($item) => $item->grades)->avg('score_percent') ?? 0, 1)); ?>%</div>
                            <div class="user-email">Average</div>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Gradebook Table -->
    <div class="user-panel gradebook-panel">
        <div class="user-panel-head"><h3><i class="fa-solid fa-table"></i> Student Grades</h3></div>

        <!-- Filters -->
        <div class="user-toolbar">
            <select id="categoryFilter" onchange="filterGrades()" class="form-control">
                <option value="">All Categories</option>
                <?php $__currentLoopData = $gradeCategories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($category->id); ?>"><?php echo e($category->name); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>

            <select id="statusFilter" onchange="filterGrades()" class="form-control">
                <option value="">All Status</option>
                <option value="graded">Graded</option>
                <option value="ungraded">Ungraded</option>
                <option value="released">Released</option>
            </select>

            <input type="text" id="studentSearch" placeholder="Search students..." onkeyup="filterGrades()" class="form-control" style="flex:1">
        </div>

        <div class="user-panel-body">
        <!-- Grades Table -->
        <div class="user-table-wrap gradebook-table-wrap">
            <table class="table gradebook-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <?php $__currentLoopData = $gradeItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <th>
                                <div style="font-size:0.85rem;font-weight:600"><?php echo e($item->name); ?></div>
                                <div class="user-email"><?php echo e($item->max_points); ?> pts</div>
                            </th>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <th>Total</th>
                        <th>%</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $students; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $enrollment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr class="student-row" data-student="<?php echo e($enrollment->student->name); ?>" style="border-bottom: 1px solid #e2e8f0;">
                            <td style="padding: 12px;">
                                <div style="font-weight: 600; color: #1e293b;"><?php echo e($enrollment->student->name); ?></div>
                                <div style="font-size: 0.85rem; color: #64748b;"><?php echo e($enrollment->student->email); ?></div>
                            </td>
                            
                            <?php $__currentLoopData = $gradeItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <td style="padding: 12px; text-align: center;">
                                    <?php
                                        $grade = $item->grades->where('student_id', $enrollment->student_id)->first();
                                    ?>
                                    
                                    <?php if($grade): ?>
                                        <div style="font-weight: 600; color: <?php echo e($grade->score_percent >= 70 ? '#16a34a' : ($grade->score_percent >= 50 ? '#d97706' : '#dc2626')); ?>;">
                                            <?php echo e($grade->points); ?>/<?php echo e($item->max_points); ?>

                                        </div>
                                        <div style="font-size: 0.75rem; color: #64748b;"><?php echo e(number_format($grade->score_percent, 1)); ?>%</div>
                                        <?php if(!$item->is_released): ?>
                                            <span style="background: #fef3c7; color: #d97706; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem;">
                                                Hidden
                                            </span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <button type="button" onclick="enterGrade(<?php echo e($item->id); ?>, <?php echo e($enrollment->student_id); ?>)" 
                                                class="btn-modal-cancel" style="padding: 4px 8px; border-radius: 4px; font-size: 0.8rem;">
                                            Enter
                                        </button>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            
                            <?php
                                $totalPoints = $gradeItems->sum('max_points');
                                $earnedPoints = $gradeItems->flatMap(fn ($item) => $item->grades)
                                    ->where('student_id', $enrollment->student_id)
                                    ->sum('points');
                                $totalPercent = $totalPoints > 0 ? ($earnedPoints / $totalPoints) * 100 : 0;
                            ?>
                            
                            <td style="padding: 12px; text-align: center; font-weight: 600;">
                                <?php echo e($earnedPoints); ?>/<?php echo e($totalPoints); ?>

                            </td>
                            <td style="padding: 12px; text-align: center; font-weight: 600; color: <?php echo e($totalPercent >= 70 ? '#16a34a' : ($totalPercent >= 50 ? '#d97706' : '#dc2626')); ?>;">
                                <?php echo e(number_format($totalPercent, 1)); ?>%
                            </td>
                            <td style="padding: 12px; text-align: center;">
                                <button type="button" onclick="showStudentGrades(<?php echo e($enrollment->student_id); ?>)" 
                                        class="btn-add" style="padding: 6px 12px; border-radius: 6px; font-size: 0.85rem;">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div style="margin-top: 16px;">
            <?php echo e($students->links()); ?>

        </div>
        </div>
    </div>
</div>

    <!-- Quick Grade Entry Modal -->
    <div id="gradeModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: relative; max-width: 500px; margin: 100px auto; background: white; border-radius: 12px; padding: 24px;">
            <h3 style="margin: 0 0 16px 0;"><i class="fa-solid fa-edit"></i> Enter Grade</h3>
            
            <form id="gradeForm" onsubmit="saveGrade(event)">
                <?php echo csrf_field(); ?>
                <input type="hidden" id="gradeItemId" name="grade_item_id">
                <input type="hidden" id="studentId" name="student_id">
                
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px;">Points</label>
                    <input type="number" id="points" name="points" step="0.1" min="0" required
                           style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                    <div id="maxPointsInfo" style="font-size: 0.85rem; color: #64748b; margin-top: 4px;"></div>
                </div>
                
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px;">Feedback</label>
                    <textarea id="feedback" name="feedback" rows="4"
                              style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; resize: vertical;"
                              placeholder="Provide feedback to the student..."></textarea>
                </div>
                
                <!-- Rubric Integration -->
                <div id="rubricSection" style="margin-bottom: 16px; display: none;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px;">
                        <i class="fa-solid fa-list-check"></i> Rubric Assessment
                    </label>
                    <div id="rubricCriteria" style="padding: 12px; background: #f8fafc; border-radius: 6px;"></div>
                </div>
                
                <div style="display: flex; gap: 8px; margin-top: 16px;">
                    <button type="submit" class="btn-add" style="flex: 1; padding: 10px 20px; border-radius: 6px; cursor: pointer;">
                        <i class="fa-solid fa-save"></i> Save Grade
                    </button>
                    <button type="button" onclick="closeGradeModal()" class="btn-modal-cancel" style="flex: 1; padding: 10px 20px; border-radius: 6px; cursor: pointer;">
                        <i class="fa-solid fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bulk Grade Modal -->
    <div id="bulkGradeModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
        <div style="position: relative; max-width: 600px; margin: 100px auto; background: white; border-radius: 12px; padding: 24px; max-height: 80vh; overflow-y: auto;">
            <h3 style="margin: 0 0 16px 0;"><i class="fa-solid fa-edit"></i> Bulk Grade Entry</h3>
            
            <form id="bulkGradeForm" onsubmit="saveBulkGrades(event)">
                <?php echo csrf_field(); ?>
                
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px;">Grade Item</label>
                    <select id="bulkGradeItem" name="grade_item_id" required onchange="loadBulkStudents()"
                            style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                        <option value="">Select Grade Item</option>
                        <?php $__currentLoopData = $gradeItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($item->id); ?>"><?php echo e($item->title); ?> (<?php echo e($item->max_points); ?> pts)</option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                
                <div id="bulkStudentsContainer" style="display: none;">
                    <label style="display: block; font-weight: 600; margin-bottom: 8px;">Student Grades</label>
                    <div id="bulkStudentsList" style="max-height: 300px; overflow-y: auto;"></div>
                </div>
                
                <div style="display: flex; gap: 8px; margin-top: 16px;">
                    <button type="submit" class="btn-add" style="flex: 1; padding: 10px 20px; border-radius: 6px; cursor: pointer;">
                        <i class="fa-solid fa-save"></i> Save All Grades
                    </button>
                    <button type="button" onclick="closeBulkGradeModal()" class="btn-modal-cancel" style="flex: 1; padding: 10px 20px; border-radius: 6px; cursor: pointer;">
                        <i class="fa-solid fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function enterGrade(gradeItemId, studentId) {
            document.getElementById('gradeItemId').value = gradeItemId;
            document.getElementById('studentId').value = studentId;
            
            // Get grade item info
            const gradeItem = <?php echo e(json_encode($gradeItems)); ?>.find(item => item.id === gradeItemId);
            if (gradeItem) {
                document.getElementById('maxPointsInfo').textContent = `Max points: ${gradeItem.max_points}`;
                document.getElementById('points').max = gradeItem.max_points;
            }
            
            // Load existing grade if any
            // This would typically be done via AJAX
            
            // Check if rubric is available
            if (gradeItem && gradeItem.rubric_id) {
                loadRubric(gradeItem.rubric_id);
            }
            
            document.getElementById('gradeModal').style.display = 'block';
        }

        function closeGradeModal() {
            document.getElementById('gradeModal').style.display = 'none';
            document.getElementById('gradeForm').reset();
        }

        function saveGrade(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            
            fetch('<?php echo e(route('instructor.classes.gradebook.grades.store', $class)); ?>', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'text/html'
                },
                body: formData,
                redirect: 'follow'
            })
            .then(function() {
                closeGradeModal();
                location.reload();
            })
            .catch(function() {
                event.target.submit();
            });
        }

        function showBulkGradeModal() {
            document.getElementById('bulkGradeModal').style.display = 'block';
        }

        function closeBulkGradeModal() {
            document.getElementById('bulkGradeModal').style.display = 'none';
            document.getElementById('bulkGradeForm').reset();
            document.getElementById('bulkStudentsContainer').style.display = 'none';
        }

        function loadBulkStudents() {
            const gradeItemId = document.getElementById('bulkGradeItem').value;
            if (!gradeItemId) return;
            
            const students = <?php echo e(json_encode($students->items())); ?>;
            const container = document.getElementById('bulkStudentsList');
            container.innerHTML = '';
            
            students.forEach(enrollment => {
                const studentDiv = document.createElement('div');
                studentDiv.style.cssText = 'padding: 12px; border: 1px solid #e2e8f0; border-radius: 6px; margin-bottom: 8px;';
                studentDiv.innerHTML = `
                    <div style="font-weight: 600; margin-bottom: 8px;">${enrollment.student.name}</div>
                    <input type="number" name="grades[${enrollment.student_id}]" 
                           placeholder="Points" step="0.1" min="0"
                           style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;">
                    <textarea name="feedback[${enrollment.student_id}]" rows="2" placeholder="Feedback"
                              style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px; margin-top: 8px; resize: vertical;"></textarea>
                `;
                container.appendChild(studentDiv);
            });
            
            document.getElementById('bulkStudentsContainer').style.display = 'block';
        }

        function saveBulkGrades(event) {
            event.preventDefault();

            const form = event.target;
            const gradeItemId = document.getElementById('bulkGradeItem').value;
            if (!gradeItemId) {
                alert('Please select a grade item first.');
                return;
            }

            const formData = new FormData(form);
            const submitBtn = form.querySelector('button[type="submit"]');
            const original = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...';

            fetch('<?php echo e(route('instructor.classes.gradebook.grades.bulk', $class)); ?>', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'text/html'
                },
                body: formData,
                redirect: 'follow'
            })
            .then(function() {
                closeBulkGradeModal();
                location.reload();
            })
            .catch(function() {
                submitBtn.disabled = false;
                submitBtn.innerHTML = original;
                form.submit();
            });
        }

        function showStudentGrades(studentId) {
            window.location.href = '<?php echo e(url('/instructor/classes/' . $class->id . '/gradebook/students')); ?>/' + studentId;
        }

        function filterGrades() {
            const categoryFilter = document.getElementById('categoryFilter').value;
            const statusFilter = document.getElementById('statusFilter').value;
            const studentSearch = document.getElementById('studentSearch').value.toLowerCase();
            
            const rows = document.querySelectorAll('.student-row');
            
            rows.forEach(row => {
                const studentName = row.dataset.student.toLowerCase();
                const showByStudent = !studentSearch || studentName.includes(studentSearch);
                
                // Add category and status filtering logic here
                row.style.display = showByStudent ? '' : 'none';
            });
        }

        function exportGrades() {
            window.location.href = '<?php echo e(route('instructor.classes.gradebook.export', $class)); ?>';
        }

        function loadRubric(rubricId) {
            // Load rubric criteria via AJAX
            // This would populate the rubric section with criteria and levels
            document.getElementById('rubricSection').style.display = 'block';
        }

        // Close modals on outside click
        window.onclick = function(event) {
            if (event.target.id === 'gradeModal') {
                closeGradeModal();
            }
            if (event.target.id === 'bulkGradeModal') {
                closeBulkGradeModal();
            }
        }
    </script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.instructor', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\instructor\gradebook\index.blade.php ENDPATH**/ ?>