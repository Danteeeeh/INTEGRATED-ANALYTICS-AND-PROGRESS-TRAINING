<?php $__env->startSection('title', 'LMS Analytics'); ?>
<?php
    $activeNav = 'analytics';
    $pageTitle = 'LMS Analytics Dashboard';
    $pageIcon = '<i class="fa-solid fa-chart-line"></i>';
?>

<?php $__env->startSection('page-title-bar'); ?>
    <div class="page-title-bar">
        <h2 class="page-title">
            <i class="fa-solid fa-chart-line"></i>
            LMS Analytics Dashboard
        </h2>
        <div style="display: flex; gap: 8px;">
            <select id="periodFilter" onchange="updateAnalytics()" style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px;">
                <option value="7">Last 7 Days</option>
                <option value="30" selected>Last 30 Days</option>
                <option value="90">Last 90 Days</option>
                <option value="365">Last Year</option>
            </select>
            <button type="button" onclick="exportAnalytics()" class="btn-add" style="display: inline-flex; align-items: center; padding: 8px 16px; border-radius: 6px; cursor: pointer;">
                <i class="fa-solid fa-download"></i> Export
            </button>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <!-- Overview Stats -->
    <div class="info-row">
        <?php if (isset($component)) { $__componentOriginal08ba9eaac8edb771afebece768bd7e27 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal08ba9eaac8edb771afebece768bd7e27 = $attributes; } ?>
<?php $component = App\View\Components\SmsInfoCard::resolve(['label' => 'Total Students','amount' => $stats['total_students'],'icon' => 'fa-solid fa-users'] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
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
<?php $component = App\View\Components\SmsInfoCard::resolve(['label' => 'Active Courses','amount' => $stats['active_classes'],'icon' => 'fa-solid fa-book'] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
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
<?php $component = App\View\Components\SmsInfoCard::resolve(['label' => 'Completion Rate','name' => number_format($stats['completion_rate'], 1) . '%','icon' => 'fa-solid fa-graduation-cap'] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
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
<?php $component = App\View\Components\SmsInfoCard::resolve(['label' => 'Avg Grade','name' => number_format($stats['average_grade'], 1) . '%','icon' => 'fa-solid fa-chart-bar'] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
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

    <!-- Learning Analytics -->
    <div class="form-card">
        <h3><i class="fa-solid fa-chart-line"></i> Learning Analytics</h3>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px; margin-top: 16px;">
            <div style="padding: 16px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; color: white;">
                <div style="font-size: 0.9rem; opacity: 0.9; margin-bottom: 8px;">Avg. Completion Time</div>
                <div style="font-size: 1.8rem; font-weight: 700;"><?php echo e(number_format($stats['learning_analytics']['average_time_to_complete'], 1)); ?> days</div>
            </div>
            
            <div style="padding: 16px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); border-radius: 12px; color: white;">
                <div style="font-size: 0.9rem; opacity: 0.9; margin-bottom: 8px;">Student Retention</div>
                <div style="font-size: 1.8rem; font-weight: 700;"><?php echo e(number_format($stats['learning_analytics']['student_retention_rate'], 1)); ?>%</div>
            </div>
            
            <div style="padding: 16px; background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); border-radius: 12px; color: white;">
                <div style="font-size: 0.9rem; opacity: 0.9; margin-bottom: 8px;">Most Popular Course</div>
                <div style="font-size: 1.2rem; font-weight: 700;"><?php echo e($stats['learning_analytics']['most_popular_courses'][0]['title'] ?? 'N/A'); ?></div>
                <div style="font-size: 0.85rem; opacity: 0.9; margin-top: 4px;"><?php echo e($stats['learning_analytics']['most_popular_courses'][0]['enrollments'] ?? 0); ?> enrollments</div>
            </div>
            
            <div style="padding: 16px; background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); border-radius: 12px; color: white;">
                <div style="font-size: 0.9rem; opacity: 0.9; margin-bottom: 8px;">Top Category</div>
                <div style="font-size: 1.2rem; font-weight: 700;"><?php echo e(collect($stats['learning_analytics']['completion_by_category'])->sortByDesc('rate')->first()['name'] ?? 'N/A'); ?></div>
                <div style="font-size: 0.85rem; opacity: 0.9; margin-top: 4px;"><?php echo e(number_format(collect($stats['learning_analytics']['completion_by_category'])->sortByDesc('rate')->first()['rate'] ?? 0, 1)); ?>% completion</div>
            </div>
        </div>
    </div>

    <!-- Engagement Metrics -->
    <div class="form-card">
        <h3><i class="fa-solid fa-users"></i> Engagement Metrics</h3>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-top: 16px;">
            <div style="text-align: center; padding: 20px; background: #f8fafc; border-radius: 8px;">
                <div style="font-size: 2rem; font-weight: 700; color: #3b82f6;"><?php echo e($stats['engagement_metrics']['content_consumption']['total_lessons_viewed']); ?></div>
                <div style="font-size: 0.9rem; color: #64748b; margin-top: 4px;">Lessons Viewed</div>
            </div>
            
            <div style="text-align: center; padding: 20px; background: #f8fafc; border-radius: 8px;">
                <div style="font-size: 2rem; font-weight: 700; color: #16a34a;"><?php echo e($stats['engagement_metrics']['content_consumption']['total_videos_watched']); ?></div>
                <div style="font-size: 0.9rem; color: #64748b; margin-top: 4px;">Videos Watched</div>
            </div>
            
            <div style="text-align: center; padding: 20px; background: #f8fafc; border-radius: 8px;">
                <div style="font-size: 2rem; font-weight: 700; color: #d97706;"><?php echo e($stats['engagement_metrics']['forum_participation']['total_posts']); ?></div>
                <div style="font-size: 0.9rem; color: #64748b; margin-top: 4px;">Forum Posts</div>
            </div>
            
            <div style="text-align: center; padding: 20px; background: #f8fafc; border-radius: 8px;">
                <div style="font-size: 2rem; font-weight: 700; color: #8b5cf6;"><?php echo e(number_format($stats['engagement_metrics']['virtual_class_attendance']['attendance_rate'], 1)); ?>%</div>
                <div style="font-size: 0.9rem; color: #64748b; margin-top: 4px;">Class Attendance</div>
            </div>
        </div>
        
        <!-- Daily Active Users Chart -->
        <div style="margin-top: 24px;">
            <h4 style="margin-bottom: 12px;">Daily Active Users (Last 30 Days)</h4>
            <div id="dailyActiveUsersChart" style="height: 300px; background: #f8fafc; border-radius: 8px; display: flex; align-items: flex-end; padding: 20px; gap: 4px;">
                <?php $__currentLoopData = $stats['engagement_metrics']['daily_active_users']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $date => $count): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $dauMax = max(collect($stats['engagement_metrics']['daily_active_users'])->values()->all());
                        $dauHeight = $count > 0 && $dauMax > 0 ? round(($count / $dauMax) * 100, 1) : 5;
                    ?>
                    <div style="flex: 1; background: #3b82f6; border-radius: 4px 4px 0 0; transition: height 0.3s; position: relative; min-height: 20px; height: <?php echo e($dauHeight); ?>%;"
                         title="<?php echo e($date); ?>: <?php echo e($count); ?> users">
                        <div style="position: absolute; bottom: -25px; left: 50%; transform: translateX(-50%); font-size: 0.7rem; color: #64748b; white-space: nowrap;">
                            <?php echo e(\Carbon\Carbon::parse($date)->format('M d')); ?>

                        </div>
                        <?php if($count > 0): ?>
                            <div style="position: absolute; top: -25px; left: 50%; transform: translateX(-50%); font-size: 0.75rem; font-weight: 600; color: #3b82f6;">
                                <?php echo e($count); ?>

                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    </div>

    <!-- Performance Trends -->
    <div class="form-card">
        <h3><i class="fa-solid fa-chart-bar"></i> Performance Trends</h3>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; margin-top: 16px;">
            <!-- Grade Distribution -->
            <div>
                <h4 style="margin-bottom: 12px;">Grade Distribution</h4>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <?php $__currentLoopData = ['A' => '#16a34a', 'B' => '#22c55e', 'C' => '#eab308', 'D' => '#f97316', 'F' => '#ef4444']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $grade => $color): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="width: 30px; font-weight: 600;"><?php echo e($grade); ?></span>
                            <div style="flex: 1; height: 24px; background: #f1f5f9; border-radius: 4px; overflow: hidden;">
                                <div style="height: 100%; background: <?php echo e($color); ?>; width: <?php echo e(($stats['performance_trends']['grade_distribution'][strtolower($grade)] ?? 0) / max(array_sum($stats['performance_trends']['grade_distribution']), 1) * 100); ?>%; transition: width 0.5s ease;"></div>
                            </div>
                            <span style="width: 50px; text-align: right; font-size: 0.85rem;"><?php echo e($stats['performance_trends']['grade_distribution'][strtolower($grade)] ?? 0); ?></span>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
            
            <!-- Improvement Rate -->
            <div>
                <h4 style="margin-bottom: 12px;">Monthly Improvement</h4>
                <div style="text-align: center; padding: 24px; background: <?php echo e($stats['performance_trends']['improvement_rate'] >= 0 ? '#dcfce7' : '#fee2e2'); ?>; border-radius: 8px;">
                    <div style="font-size: 3rem; font-weight: 700; color: <?php echo e($stats['performance_trends']['improvement_rate'] >= 0 ? '#16a34a' : '#dc2626'); ?>;">
                        <?php echo e($stats['performance_trends']['improvement_rate'] >= 0 ? '+' : ''); ?><?php echo e(number_format($stats['performance_trends']['improvement_rate'], 1)); ?>%
                    </div>
                    <div style="font-size: 0.9rem; color: #64748b; margin-top: 8px;">
                        <?php echo e($stats['performance_trends']['improvement_rate'] >= 0 ? 'Improvement' : 'Decline'); ?> vs last month
                    </div>
                </div>
            </div>
        </div>
        
        <!-- At-Risk Students -->
        <div style="margin-top: 24px;">
            <h4 style="margin-bottom: 12px;">At-Risk Students (<?php echo e(count($stats['performance_trends']['at_risk_students'])); ?>)</h4>
            <?php if(!empty($stats['performance_trends']['at_risk_students'])): ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 12px;">
                    <?php $__currentLoopData = $stats['performance_trends']['at_risk_students']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $student): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div style="padding: 12px; background: #fee2e2; border-radius: 8px; border-left: 4px solid #ef4444;">
                            <div style="font-weight: 600; color: #1e293b;"><?php echo e($student['student']['name']); ?></div>
                            <div style="font-size: 0.85rem; color: #64748b; margin-top: 4px;"><?php echo e($student['student']['email']); ?></div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            <?php else: ?>
                <div style="padding: 20px; text-align: center; color: #64748b; background: #f8fafc; border-radius: 8px;">
                    <i class="fa-solid fa-check-circle" style="font-size: 2rem; margin-bottom: 8px; color: #16a34a;"></i>
                    No at-risk students
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Top Performers -->
        <div style="margin-top: 24px;">
            <h4 style="margin-bottom: 12px;">Top Performers</h4>
            <?php if(!empty($stats['performance_trends']['top_performers'])): ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 12px;">
                    <?php $__currentLoopData = $stats['performance_trends']['top_performers']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $performer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div style="padding: 12px; background: #dcfce7; border-radius: 8px; border-left: 4px solid #16a34a;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div style="font-weight: 600; color: #1e293b;"><?php echo e($performer['name']); ?></div>
                                <div style="font-size: 1.2rem; font-weight: 700; color: #16a34a;"><?php echo e(number_format($performer['average_grade'], 1)); ?>%</div>
                            </div>
                            <div style="font-size: 0.85rem; color: #64748b; margin-top: 4px;"><?php echo e($performer['total_grades']); ?> graded items</div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            <?php else: ?>
                <div style="padding: 20px; text-align: center; color: #64748b; background: #f8fafc; border-radius: 8px;">
                    No performance data available
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Course Performance -->
    <div class="form-card">
        <h3><i class="fa-solid fa-book"></i> Course Performance</h3>
        
        <div style="margin-top: 16px;">
            <h4 style="margin-bottom: 12px;">Most Popular Courses</h4>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f8fafc;">
                            <th style="padding: 12px; text-align: left; border-bottom: 2px solid #e2e8f0;">Course</th>
                            <th style="padding: 12px; text-align: center; border-bottom: 2px solid #e2e8f0;">Enrollments</th>
                            <th style="padding: 12px; text-align: center; border-bottom: 2px solid #e2e8f0;">Completion Rate</th>
                            <th style="padding: 12px; text-align: center; border-bottom: 2px solid #e2e8f0;">Trend</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $stats['learning_analytics']['most_popular_courses']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $course): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr style="border-bottom: 1px solid #e2e8f0;">
                                <td style="padding: 12px; font-weight: 600;"><?php echo e($course['title']); ?></td>
                                <td style="padding: 12px; text-align: center;"><?php echo e($course['enrollments']); ?></td>
                                <td style="padding: 12px; text-align: center;">
                                    <?php
                                        $cr = $course['completion_rate'];
                                        $crBg = $cr >= 70 ? '#dcfce7' : ($cr >= 50 ? '#fef3c7' : '#fee2e2');
                                        $crFg = $cr >= 70 ? '#16a34a' : ($cr >= 50 ? '#d97706' : '#dc2626');
                                    ?>
                                    <span style="background: <?php echo e($crBg); ?>; color: <?php echo e($crFg); ?>; padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">
                                        <?php echo e(number_format($course['completion_rate'], 1)); ?>%
                                    </span>
                                </td>
                                <td style="padding: 12px; text-align: center;">
                                    <i class="fa-solid fa-arrow-up" style="color: #16a34a;"></i>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div style="margin-top: 24px;">
            <h4 style="margin-bottom: 12px;">Completion by Category</h4>
            <div style="display: flex; flex-wrap: wrap; gap: 16px;">
                <?php $__currentLoopData = $stats['learning_analytics']['completion_by_category']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div style="flex: 1; min-width: 200px; padding: 16px; background: #f8fafc; border-radius: 8px;">
                        <div style="font-weight: 600; color: #1e293b; margin-bottom: 8px;"><?php echo e($category['name']); ?></div>
                        <div style="margin-bottom: 8px;">
                            <div style="background: #e2e8f0; border-radius: 4px; height: 8px; overflow: hidden;">
                                <div style="background: #3b82f6; height: 100%; width: <?php echo e($category['rate']); ?>%;"></div>
                            </div>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.85rem; color: #64748b;">
                            <span><?php echo e($category['completed']); ?>/<?php echo e($category['total']); ?></span>
                            <span><?php echo e(number_format($category['rate'], 1)); ?>%</span>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    </div>

    <!-- System Health -->
    <div class="form-card">
        <h3><i class="fa-solid fa-server"></i> System Health</h3>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-top: 16px;">
            <div style="text-align: center; padding: 20px; background: #f8fafc; border-radius: 8px;">
                <div style="font-size: 2rem; font-weight: 700; color: <?php echo e($stats['database_status'] === 'healthy' ? '#16a34a' : '#ef4444'); ?>;">
                    <?php echo e($stats['database_status'] === 'healthy' ? '✓' : '✗'); ?>

                </div>
                <div style="font-size: 0.9rem; color: #64748b; margin-top: 4px;">Database</div>
            </div>
            
            <div style="text-align: center; padding: 20px; background: #f8fafc; border-radius: 8px;">
                <div style="font-size: 2rem; font-weight: 700; color: #3b82f6;"><?php echo e($stats['storage_usage']['percentage']); ?>%</div>
                <div style="font-size: 0.9rem; color: #64748b; margin-top: 4px;">Storage Used</div>
            </div>
            
            <div style="text-align: center; padding: 20px; background: #f8fafc; border-radius: 8px;">
                <div style="font-size: 2rem; font-weight: 700; color: #16a34a;"><?php echo e($stats['total_notifications']); ?></div>
                <div style="font-size: 0.9rem; color: #64748b; margin-top: 4px;">Notifications</div>
            </div>
            
            <div style="text-align: center; padding: 20px; background: #f8fafc; border-radius: 8px;">
                <div style="font-size: 2rem; font-weight: 700; color: #d97706;"><?php echo e($stats['unread_notifications']); ?></div>
                <div style="font-size: 0.9rem; color: #64748b; margin-top: 4px;">Unread</div>
            </div>
        </div>
    </div>

    <script>
        function updateAnalytics() {
            const period = document.getElementById('periodFilter').value;
            // Reload page with period parameter
            window.location.href = `/admin/analytics?period=${period}`;
        }

        function exportAnalytics() {
            // Export analytics data
            const data = {
                period: document.getElementById('periodFilter').value,
                stats: <?php echo e(json_encode($stats)); ?>

            };
            
            const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `lms_analytics_${new Date().toISOString().split('T')[0]}.json`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }

        // Animate charts on load
        document.addEventListener('DOMContentLoaded', function() {
            // Animate grade distribution bars
            const gradeBars = document.querySelectorAll('[style*="background: #"]');
            gradeBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0%';
                setTimeout(() => {
                    bar.style.width = width;
                }, 100);
            });
        });
    </script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin-sms', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\lms\resources\views\admin\analytics.blade.php ENDPATH**/ ?>