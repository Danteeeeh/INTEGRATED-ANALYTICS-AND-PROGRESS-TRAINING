@props([
    'course' => null,
    'studentId' => null,
    'showDetailed' => true,
    'allowInteraction' => true,
])

<div class="module-progress-tracker" style="margin-top: 24px;">
    <!-- Overall Progress -->
    <div class="form-card">
        <h3><i class="fa-solid fa-chart-pie"></i> Course Progress</h3>
        
        @php
            $studentId = $studentId ?? auth()->id();
            $modules = $course->modules()->with('lessons')->orderBy('position')->get();
            
            $totalModules = $modules->count();
            $completedModules = 0;
            $totalLessons = 0;
            $completedLessons = 0;
            
            foreach($modules as $module) {
                $moduleProgress = \App\Models\ModuleProgress::where('module_id', $module->id)
                    ->where('student_id', $studentId)
                    ->first();
                
                if($moduleProgress && $moduleProgress->status === 'completed') {
                    $completedModules++;
                }
                
                $totalLessons += $module->lessons->count();
                $completedLessons += $moduleProgress?->lessons_completed ?? 0;
            }
            
            $overallProgress = $totalModules > 0 ? ($completedModules / $totalModules) * 100 : 0;
            $lessonProgress = $totalLessons > 0 ? ($completedLessons / $totalLessons) * 100 : 0;
        @endphp
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-top: 16px;">
            <!-- Circular Progress -->
            <div style="text-align: center; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; color: white;">
                <div style="position: relative; width: 120px; height: 120px; margin: 0 auto 12px;">
                    <svg viewBox="0 0 36 36" style="width: 100%; height: 100%; transform: rotate(-90deg);">
                        <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                              fill="none" stroke="rgba(255,255,255,0.3)" stroke-width="3"/>
                        <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                              fill="none" stroke="white" stroke-width="3"
                              stroke-dasharray="{{ $overallProgress }}, 100"
                              style="transition: stroke-dasharray 0.5s ease;"/>
                    </svg>
                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 1.5rem; font-weight: 700;">
                        {{ number_format($overallProgress, 0) }}%
                    </div>
                </div>
                <div style="font-size: 0.9rem; opacity: 0.9;">{{ $completedModules }}/{{ $totalModules }} Modules</div>
            </div>
            
            <!-- Stats -->
            <div style="display: flex; flex-direction: column; gap: 12px; justify-content: center;">
                <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: #f8fafc; border-radius: 8px;">
                    <div style="width: 40px; height: 40px; background: #dbeafe; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-book-open" style="color: #1e40af; font-size: 1.2rem;"></i>
                    </div>
                    <div>
                        <div style="font-weight: 600; color: #1e293b;">{{ $completedLessons }}/{{ $totalLessons }}</div>
                        <div style="font-size: 0.85rem; color: #64748b;">Lessons Completed</div>
                    </div>
                </div>
                
                <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: #f8fafc; border-radius: 8px;">
                    <div style="width: 40px; height: 40px; background: #dcfce7; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-cube" style="color: #16a34a; font-size: 1.2rem;"></i>
                    </div>
                    <div>
                        <div style="font-weight: 600; color: #1e293b;">{{ $completedModules }}/{{ $totalModules }}</div>
                        <div style="font-size: 0.85rem; color: #64748b;">Modules Completed</div>
                    </div>
                </div>
                
                <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: #f8fafc; border-radius: 8px;">
                    <div style="width: 40px; height: 40px; background: #fef3c7; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                        <i class="fa-solid fa-clock" style="color: #d97706; font-size: 1.2rem;"></i>
                    </div>
                    <div>
                        <div style="font-weight: 600; color: #1e293b;">{{ number_format($lessonProgress, 1) }}%</div>
                        <div style="font-size: 0.85rem; color: #64748b;">Lesson Progress</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($showDetailed)
        <!-- Detailed Module Progress -->
        <div class="form-card" style="margin-top: 24px;">
            <h3><i class="fa-solid fa-list-check"></i> Module Details</h3>
            
            <div style="margin-top: 16px;">
                @foreach($modules as $index => $module)
                    @php
                        $moduleProgress = \App\Models\ModuleProgress::where('module_id', $module->id)
                            ->where('student_id', $studentId)
                            ->first();
                        
                        $moduleStatus = $moduleProgress?->status ?? 'not_started';
                        $modulePercent = $moduleProgress?->progress_percent ?? 0;
                        $moduleLessonsCompleted = $moduleProgress?->lessons_completed ?? 0;
                        $moduleTotalLessons = $module->lessons->count();
                        
                        $isLocked = $index > 0 && !\App\Models\ModuleProgress::where('module_id', $modules[$index-1]->id)
                            ->where('student_id', $studentId)
                            ->where('status', 'completed')
                            ->exists();
                    @endphp
                    
                    <div class="module-progress-item" 
                         style="padding: 16px; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 12px; background: white; {{ $isLocked ? 'opacity: 0.6;' : '' }}">
                        
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">
                            <div style="flex: 1;">
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                                    @if($isLocked)
                                        <i class="fa-solid fa-lock" style="color: #64748b;"></i>
                                    @elseif($moduleStatus === 'completed')
                                        <i class="fa-solid fa-check-circle" style="color: #16a34a;"></i>
                                    @elseif($moduleStatus === 'in_progress')
                                        <i class="fa-solid fa-spinner fa-spin" style="color: #3b82f6;"></i>
                                    @else
                                        <i class="fa-solid fa-circle" style="color: #cbd5e1;"></i>
                                    @endif
                                    
                                    <span style="font-weight: 600; color: #1e293b;">{{ $module->title }}</span>
                                </div>
                                
                                <div style="font-size: 0.85rem; color: #64748b;">
                                    {{ $moduleLessonsCompleted }}/{{ $moduleTotalLessons }} lessons • {{ number_format($modulePercent, 1) }}% complete
                                </div>
                            </div>
                            
                            <div style="text-align: right; min-width: 100px;">
                                <div style="font-weight: 600; color: {{ $moduleStatus === 'completed' ? '#16a34a' : $moduleStatus === 'in_progress' ? '#3b82f6' : '#64748b' }};">
                                    {{ ucfirst(str_replace('_', ' ', $moduleStatus)) }}
                                </div>
                                @if($moduleProgress?->completed_at)
                                    <div style="font-size: 0.75rem; color: #64748b;">
                                        {{ $moduleProgress->completed_at->format('M d, Y') }}
                                    </div>
                                @endif
                            </div>
                        </div>
                        
                        <!-- Progress Bar -->
                        <div style="margin-bottom: 12px;">
                            <div style="background: #e2e8f0; border-radius: 4px; height: 8px; overflow: hidden;">
                                <div style="background: {{ $moduleStatus === 'completed' ? '#16a34a' : $moduleStatus === 'in_progress' ? '#3b82f6' : '#cbd5e1' }}; height: 100%; width: {{ $modulePercent }}%; transition: width 0.5s ease;"></div>
                            </div>
                        </div>
                        
                        <!-- Lesson Breakdown -->
                        @if($moduleTotalLessons > 0)
                            <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                                @foreach($module->lessons()->orderBy('position')->get() as $lesson)
                                    @php
                                        $lessonProgress = \App\Models\LessonProgress::where('lesson_id', $lesson->id)
                                            ->where('student_id', $studentId)
                                            ->first();
                                        $lessonCompleted = $lessonProgress?->completed ?? false;
                                    @endphp
                                    
                                    <div class="lesson-indicator" 
                                         style="width: 24px; height: 24px; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 600; cursor: {{ $allowInteraction && !$isLocked ? 'pointer' : 'not-allowed' }}; 
                                                background: {{ $lessonCompleted ? '#16a34a' : '#f1f5f9' }}; color: {{ $lessonCompleted ? 'white' : '#64748b' }};"
                                         title="{{ $lesson->title }}"
                                         @if($allowInteraction && !$isLocked)
                                            onclick="goToLesson({{ $lesson->id }})"
                                         @endif>
                                        @if($lessonCompleted)
                                            <i class="fa-solid fa-check"></i>
                                        @else
                                            {{ $loop->iteration }}
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        
                        <!-- Action Buttons -->
                        @if($allowInteraction && !$isLocked)
                            <div style="display: flex; gap: 8px; margin-top: 12px; padding-top: 12px; border-top: 1px solid #f1f5f9;">
                                @if($moduleStatus === 'not_started')
                                    <button type="button" onclick="startModule({{ $module->id }})" class="btn-add" style="flex: 1; padding: 8px 16px; border-radius: 6px; font-size: 0.85rem; cursor: pointer;">
                                        <i class="fa-solid fa-play"></i> Start Module
                                    </button>
                                @elseif($moduleStatus === 'in_progress')
                                    <button type="button" onclick="continueModule({{ $module->id }})" class="btn-add" style="flex: 1; padding: 8px 16px; border-radius: 6px; font-size: 0.85rem; cursor: pointer;">
                                        <i class="fa-solid fa-play"></i> Continue
                                    </button>
                                @else
                                    <button type="button" onclick="reviewModule({{ $module->id }})" class="btn-modal-cancel" style="flex: 1; padding: 8px 16px; border-radius: 6px; font-size: 0.85rem; cursor: pointer;">
                                        <i class="fa-solid fa-redo"></i> Review
                                    </button>
                                @endif
                                
                                <button type="button" onclick="viewModuleDetails({{ $module->id }})" class="btn-modal-cancel" style="padding: 8px 16px; border-radius: 6px; font-size: 0.85rem; cursor: pointer;">
                                    <i class="fa-solid fa-info-circle"></i> Details
                                </button>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Progress Timeline -->
    <div class="form-card" style="margin-top: 24px;">
        <h3><i class="fa-solid fa-stream"></i> Progress Timeline</h3>
        
        <div style="margin-top: 16px; position: relative; padding-left: 20px;">
            <!-- Timeline Line -->
            <div style="position: absolute; left: 7px; top: 0; bottom: 0; width: 2px; background: #e2e8f0;"></div>
            
            @php
                $recentProgress = \App\Models\LessonProgress::where('student_id', $studentId)
                    ->whereHas('lesson', function($q) use ($course) {
                        $q->whereHas('module', function($q) use ($course) {
                            $q->where('course_id', $course->id);
                        });
                    })
                    ->with('lesson.module')
                    ->orderBy('updated_at', 'desc')
                    ->limit(5)
                    ->get();
            @endphp
            
            @if($recentProgress->isNotEmpty())
                @foreach($recentProgress as $progress)
                    <div style="position: relative; margin-bottom: 24px;">
                        <!-- Timeline Dot -->
                        <div style="position: absolute; left: -20px; top: 0; width: 16px; height: 16px; border-radius: 50%; background: {{ $progress->completed ? '#16a34a' : '#3b82f6' }}; border: 3px solid white; box-shadow: 0 0 0 2px {{ $progress->completed ? '#16a34a' : '#3b82f6' }};"></div>
                        
                        <div style="padding: 12px; background: #f8fafc; border-radius: 8px; margin-left: 8px;">
                            <div style="font-weight: 600; color: #1e293b; margin-bottom: 4px;">
                                {{ $progress->lesson->title }}
                            </div>
                            <div style="font-size: 0.85rem; color: #64748b; margin-bottom: 4px;">
                                {{ $progress->lesson->module->title }}
                            </div>
                            <div style="font-size: 0.8rem; color: #94a3b8;">
                                {{ $progress->completed ? 'Completed' : 'In Progress' }} • {{ $progress->updated_at->diffForHumans() }}
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <div style="padding: 20px; text-align: center; color: #64748b;">
                    <i class="fa-solid fa-hourglass-start" style="font-size: 2rem; margin-bottom: 8px;"></i>
                    <div>Start your learning journey to see progress here</div>
                </div>
            @endif
        </div>
    </div>

    <!-- Achievement Badges -->
    <div class="form-card" style="margin-top: 24px;">
        <h3><i class="fa-solid fa-trophy"></i> Achievements</h3>
        
        @php
            $badges = \App\Models\BadgeAward::where('user_id', $studentId)
                ->whereHas('badge', function($q) use ($course) {
                    $q->where('badgeable_type', \App\Models\Course::class)
                      ->where('badgeable_id', $course->id);
                })
                ->with('badge')
                ->get();
        @endphp
        
        <div style="margin-top: 16px;">
            @if($badges->isNotEmpty())
                <div style="display: flex; flex-wrap: wrap; gap: 12px;">
                    @foreach($badges as $award)
                        <div style="text-align: center; padding: 16px; background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-radius: 12px; min-width: 100px;">
                            <div style="font-size: 2rem; margin-bottom: 8px;">{{ $award->badge->icon ?? '🏆' }}</div>
                            <div style="font-weight: 600; color: #92400e; font-size: 0.9rem;">{{ $award->badge->name }}</div>
                            <div style="font-size: 0.75rem; color: #b45309; margin-top: 4px;">{{ $award->issued_at->format('M d, Y') }}</div>
                        </div>
                    @endforeach
                </div>
            @else
                <div style="padding: 20px; text-align: center; color: #64748b; background: #f8fafc; border-radius: 8px;">
                    <i class="fa-solid fa-trophy" style="font-size: 2rem; margin-bottom: 8px;"></i>
                    <div>Complete modules to earn achievements</div>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    function startModule(moduleId) {
        // Navigate to first lesson of the module
        window.location.href = `/student/courses/{{ $course->id }}/modules/${moduleId}/lessons`;
    }

    function continueModule(moduleId) {
        // Navigate to next incomplete lesson
        window.location.href = `/student/courses/{{ $course->id }}/modules/${moduleId}/continue`;
    }

    function reviewModule(moduleId) {
        // Navigate to module overview
        window.location.href = `/student/courses/{{ $course->id }}/modules/${moduleId}`;
    }

    function goToLesson(lessonId) {
        window.location.href = `/student/courses/{{ $course->id }}/lessons/${lessonId}`;
    }

    function viewModuleDetails(moduleId) {
        // Show module details modal
        alert('Module details for ' + moduleId);
    }

    // Animate progress bars on load
    document.addEventListener('DOMContentLoaded', function() {
        const progressBars = document.querySelectorAll('[style*="width:"]');
        progressBars.forEach(bar => {
            const width = bar.style.width;
            bar.style.width = '0%';
            setTimeout(() => {
                bar.style.width = width;
            }, 100);
        });
    });
</script>