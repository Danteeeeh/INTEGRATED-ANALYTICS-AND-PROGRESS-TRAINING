<?php $__env->startSection('title', 'Admin Dashboard'); ?>
<?php
    $activeNav = 'dashboard';
    $pageTitle = 'Dashboard';
    $pageIcon = '<i class="fa-solid fa-gauge"></i>';
?>

<?php $__env->startSection('content'); ?>
    <!-- Info Cards Row -->
    <div class="info-row">
        <?php if (isset($component)) { $__componentOriginal08ba9eaac8edb771afebece768bd7e27 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal08ba9eaac8edb771afebece768bd7e27 = $attributes; } ?>
<?php $component = App\View\Components\SmsInfoCard::resolve(['label' => 'Students','name' => auth()->user()->name ?? 'Admin User','details' => ['Student ID: ' . (auth()->user()->id ?? 'N/A'), '4th Year'],'icon' => 'fa-solid fa-user-graduate'] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('sms-info-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\SmsInfoCard::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal08ba9eaac8edb771afebece768bd7e27)): ?>
<?php $attributes = $__attributesOriginal08ba9eaac8edb771afebece768bd7e27; ?>
<?php unset($__attributesOriginal08ba9eaac8edb771afebece768bd7e27); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal08ba9eaac8edb771afebece768bd7e27)): ?>
<?php $component = $__componentOriginal08ba9eaac8edb771afebece768bd7e27; ?>
<?php unset($__componentOriginal08ba9eaac8edb771afebece768bd7e27); ?>
<?php endif; ?>
        
        <?php if (isset($component)) { $__componentOriginal08ba9eaac8edb771afebece768bd7e27 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal08ba9eaac8edb771afebece768bd7e27 = $attributes; } ?>
<?php $component = App\View\Components\SmsInfoCard::resolve(['label' => 'Adviser','name' => 'Dr. Smith','details' => ['Section: 41018'],'buttonText' => 'Go to Profile','buttonLink' => '#','icon' => 'fa-solid fa-chalkboard-user'] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('sms-info-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\SmsInfoCard::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal08ba9eaac8edb771afebece768bd7e27)): ?>
<?php $attributes = $__attributesOriginal08ba9eaac8edb771afebece768bd7e27; ?>
<?php unset($__attributesOriginal08ba9eaac8edb771afebece768bd7e27); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal08ba9eaac8edb771afebece768bd7e27)): ?>
<?php $component = $__componentOriginal08ba9eaac8edb771afebece768bd7e27; ?>
<?php unset($__componentOriginal08ba9eaac8edb771afebece768bd7e27); ?>
<?php endif; ?>
        
        <?php if (isset($component)) { $__componentOriginal08ba9eaac8edb771afebece768bd7e27 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal08ba9eaac8edb771afebece768bd7e27 = $attributes; } ?>
<?php $component = App\View\Components\SmsInfoCard::resolve(['label' => 'Current Semester','status' => 'Pending','amount' => 'Php 6,000','buttonText' => 'Check Summary','buttonLink' => '#'] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('sms-info-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\SmsInfoCard::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal08ba9eaac8edb771afebece768bd7e27)): ?>
<?php $attributes = $__attributesOriginal08ba9eaac8edb771afebece768bd7e27; ?>
<?php unset($__attributesOriginal08ba9eaac8edb771afebece768bd7e27); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal08ba9eaac8edb771afebece768bd7e27)): ?>
<?php $component = $__componentOriginal08ba9eaac8edb771afebece768bd7e27; ?>
<?php unset($__componentOriginal08ba9eaac8edb771afebece768bd7e27); ?>
<?php endif; ?>
    </div>

    <!-- Chart Card -->
    <?php if (isset($component)) { $__componentOriginalc5756af7bc354f298e0f9a254050898b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc5756af7bc354f298e0f9a254050898b = $attributes; } ?>
<?php $component = App\View\Components\SmsChartCard::resolve(['title' => 'Data Report','subtitle' => 'Report For The Month','buttonText' => 'Go to Report','buttonLink' => '#','chartId' => 'reportChart'] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('sms-chart-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\SmsChartCard::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc5756af7bc354f298e0f9a254050898b)): ?>
<?php $attributes = $__attributesOriginalc5756af7bc354f298e0f9a254050898b; ?>
<?php unset($__attributesOriginalc5756af7bc354f298e0f9a254050898b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc5756af7bc354f298e0f9a254050898b)): ?>
<?php $component = $__componentOriginalc5756af7bc354f298e0f9a254050898b; ?>
<?php unset($__componentOriginalc5756af7bc354f298e0f9a254050898b); ?>
<?php endif; ?>

    <!-- Tables Row -->
    <div class="tables-row">
        <x-sms-table-card 
            title="Students"
            :headers="['Name', 'Data', 'Data1', 'Status']"
            :rows="[
                ['Juan Dela Cruz', 'Active', '2024-01-15', '<span class="badge-active">Active</span>'],
                ['Maria Santos', 'Inactive', '2024-01-20', '<span class="badge-inactive">Inactive</span>'],
                ['Jose Rizal', 'Active', '2024-01-25', '<span class="badge-active">Active</span>']
            ]"
        />
        
        <x-sms-table-card 
            title="Courses"
            :headers="['Name', 'Data', 'Data1', 'Status']"
            :rows="[
                ['BS Information Technology', 'Active', '4th Year', '<span class="badge-active">Active</span>'],
                ['BS Computer Science', 'Inactive', '3rd Year', '<span class="badge-inactive">Inactive</span>'],
                ['BS Information Systems', 'Active', '2nd Year', '<span class="badge-active">Active</span>']
            ]"
        />
    </div>

    <!-- Statistics Overview -->
    <div class="info-row">
        <?php if (isset($component)) { $__componentOriginal08ba9eaac8edb771afebece768bd7e27 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal08ba9eaac8edb771afebece768bd7e27 = $attributes; } ?>
<?php $component = App\View\Components\SmsInfoCard::resolve(['label' => 'Total Students','amount' => $stats['total_students'] ?? 0,'icon' => 'fa-solid fa-users'] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('sms-info-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\SmsInfoCard::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal08ba9eaac8edb771afebece768bd7e27)): ?>
<?php $attributes = $__attributesOriginal08ba9eaac8edb771afebece768bd7e27; ?>
<?php unset($__attributesOriginal08ba9eaac8edb771afebece768bd7e27); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal08ba9eaac8edb771afebece768bd7e27)): ?>
<?php $component = $__componentOriginal08ba9eaac8edb771afebece768bd7e27; ?>
<?php unset($__componentOriginal08ba9eaac8edb771afebece768bd7e27); ?>
<?php endif; ?>
        
        <?php if (isset($component)) { $__componentOriginal08ba9eaac8edb771afebece768bd7e27 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal08ba9eaac8edb771afebece768bd7e27 = $attributes; } ?>
<?php $component = App\View\Components\SmsInfoCard::resolve(['label' => 'Total Instructors','amount' => $stats['total_instructors'] ?? 0,'icon' => 'fa-solid fa-chalkboard-user'] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('sms-info-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\SmsInfoCard::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal08ba9eaac8edb771afebece768bd7e27)): ?>
<?php $attributes = $__attributesOriginal08ba9eaac8edb771afebece768bd7e27; ?>
<?php unset($__attributesOriginal08ba9eaac8edb771afebece768bd7e27); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal08ba9eaac8edb771afebece768bd7e27)): ?>
<?php $component = $__componentOriginal08ba9eaac8edb771afebece768bd7e27; ?>
<?php unset($__componentOriginal08ba9eaac8edb771afebece768bd7e27); ?>
<?php endif; ?>
        
        <?php if (isset($component)) { $__componentOriginal08ba9eaac8edb771afebece768bd7e27 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal08ba9eaac8edb771afebece768bd7e27 = $attributes; } ?>
<?php $component = App\View\Components\SmsInfoCard::resolve(['label' => 'Total Courses','amount' => $stats['total_courses'] ?? 0,'icon' => 'fa-solid fa-book'] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('sms-info-card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\SmsInfoCard::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal08ba9eaac8edb771afebece768bd7e27)): ?>
<?php $attributes = $__attributesOriginal08ba9eaac8edb771afebece768bd7e27; ?>
<?php unset($__attributesOriginal08ba9eaac8edb771afebece768bd7e27); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal08ba9eaac8edb771afebece768bd7e27)): ?>
<?php $component = $__componentOriginal08ba9eaac8edb771afebece768bd7e27; ?>
<?php unset($__componentOriginal08ba9eaac8edb771afebece768bd7e27); ?>
<?php endif; ?>
    </div>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
// Initialize Chart.js
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('reportChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Students',
                    data: [65, 59, 80, 81, 56, 55],
                    fill: false,
                    borderColor: '#2563eb',
                    tension: 0.1
                }, {
                    label: 'Courses',
                    data: [28, 48, 40, 19, 86, 27],
                    fill: false,
                    borderColor: '#22c55e',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
});
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.admin-sms', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\admin\dashboard-sms.blade.php ENDPATH**/ ?>