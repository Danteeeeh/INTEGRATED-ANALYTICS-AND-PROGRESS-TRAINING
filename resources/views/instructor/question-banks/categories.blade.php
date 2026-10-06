@php
    $routePrefix = auth()->user()->isAdmin() ? 'admin.' : 'instructor.';
    $layout = auth()->user()->isAdmin() ? 'layouts.admin' : 'layouts.instructor';
@endphp
@extends($layout)

@section('title', 'Test Bank — Categories')
@php $activeNav = 'question_banks'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="Test Bank"
        subtitle="Topic categories used to group questions and to set selection quotas."
        icon="fa-tag"
    >
        <x-slot name="actions">
            <a href="{{ route($routePrefix.'test_bank.index') }}" class="btn btn-secondary"><i class="fa-solid fa-list-check"></i> All Questions</a>
        </x-slot>
    </x-user-page-header>

    @include('instructor.question-banks._tabs', ['activeTab' => 'categories'])

    @if(session('success'))
        <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> {{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i> {{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <i class="fa-solid fa-circle-exclamation"></i>
            {{ $errors->first() }}
        </div>
    @endif

    <div class="user-panel">
        <div class="user-panel-head"><h2><i class="fa-solid fa-plus"></i> New Category</h2></div>

        <form method="POST" action="{{ route($routePrefix.'question_banks.categories.store') }}" style="display:flex;flex-wrap:wrap;gap:14px;align-items:flex-end;padding:4px 0 8px">
            @csrf

            <label style="flex:1 1 200px;display:flex;flex-direction:column;gap:6px">
                <span style="font-size:.8rem;font-weight:600;color:#64748b">Name</span>
                <input class="form-control" name="name" value="{{ old('name') }}" placeholder="e.g. Computer Hardware" required maxlength="120">
            </label>

            <label style="flex:1 1 240px;display:flex;flex-direction:column;gap:6px">
                <span style="font-size:.8rem;font-weight:600;color:#64748b">Course <em style="font-weight:400">(blank = shared)</em></span>
                <select class="form-control" name="course_id">
                    <option value="">Shared across all courses</option>
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" @selected(old('course_id') == $course->id)>{{ $course->code }} — {{ $course->title }}</option>
                    @endforeach
                </select>
            </label>

            <label style="flex:2 1 280px;display:flex;flex-direction:column;gap:6px">
                <span style="font-size:.8rem;font-weight:600;color:#64748b">Description</span>
                <input class="form-control" name="description" value="{{ old('description') }}" placeholder="Optional — what belongs in this topic" maxlength="500">
            </label>

            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add Category</button>
        </form>
    </div>

    <div class="user-panel">
        <form class="user-toolbar" method="GET" action="{{ route($routePrefix.'question_banks.categories.index') }}">
            <input class="form-control" name="q" value="{{ request('q') }}" placeholder="Search categories..." aria-label="Search categories">

            <select class="form-control" name="course_id" aria-label="Filter course">
                <option value="">All Courses</option>
                @foreach($courses as $course)
                    <option value="{{ $course->id }}" @selected(request('course_id') == $course->id)>{{ $course->code }} — {{ $course->title }}</option>
                @endforeach
            </select>

            <select class="form-control" name="scope" aria-label="Filter scope">
                <option value="">All Scopes</option>
                <option value="global" @selected(request('scope') === 'global')>Shared Only</option>
                <option value="mine" @selected(request('scope') === 'mine')>Created by Me</option>
            </select>

            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Filter</button>
            @if(request()->hasAny(['q', 'course_id', 'scope']))
                <a href="{{ route($routePrefix.'question_banks.categories.index') }}" class="btn btn-secondary">Reset</a>
            @endif
        </form>

        @if($categories->isEmpty())
            <div class="empty-state">
                <i class="fa-solid fa-tag"></i>
                <p>No categories match these filters.</p>
            </div>
        @else
            {{-- Cards rather than a table: each category carries its own edit form,
                 and a <form> may not legally wrap table rows. --}}
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:16px">
                @foreach($categories as $category)
                    <div style="border:1px solid #e2e8f0;border-radius:12px;padding:16px;background:#fff;display:flex;flex-direction:column;gap:12px">
                        <div style="display:flex;justify-content:space-between;align-items:center;gap:10px">
                            <strong style="font-size:.95rem;color:#0f172a">{{ $category->name }}</strong>
                            <span style="font-size:.78rem;color:#64748b;background:#f1f5f9;padding:3px 9px;border-radius:99px">
                                {{ $category->questions_count }} question(s)
                                · {{ $category->active_questions_count }} live
                            </span>
                        </div>

                        <form method="POST" action="{{ route($routePrefix.'question_banks.categories.update', $category) }}" style="display:flex;flex-direction:column;gap:10px">
                            @csrf
                            @method('PUT')

                            <label style="display:flex;flex-direction:column;gap:5px">
                                <span style="font-size:.78rem;font-weight:600;color:#64748b">Name</span>
                                {{-- Validation is reported once at the top of the page: an
                                     @error block here would repeat under every card. --}}
                                <input class="form-control" name="name" value="{{ old('name', $category->name) }}" required maxlength="120">
                            </label>

                            <label style="display:flex;flex-direction:column;gap:5px">
                                <span style="font-size:.78rem;font-weight:600;color:#64748b">Course <em style="font-weight:400">(blank = shared)</em></span>
                                <select class="form-control" name="course_id">
                                    <option value="">Shared across all courses</option>
                                    @foreach($courses as $course)
                                        <option value="{{ $course->id }}" @selected($category->course_id == $course->id)>{{ $course->code }} — {{ $course->title }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <label style="display:flex;flex-direction:column;gap:5px">
                                <span style="font-size:.78rem;font-weight:600;color:#64748b">Description</span>
                                <input class="form-control" name="description" value="{{ old('description', $category->description) }}" maxlength="500">
                            </label>

                            <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-top:2px">
                                <span style="font-size:.75rem;color:#94a3b8">{{ $category->creator?->name ?? '—' }}{{ $category->created_by === auth()->id() ? ' (you)' : '' }}</span>
                                <span style="display:flex;gap:8px">
                                    <button type="submit" class="btn btn-primary" style="padding:7px 14px"><i class="fa-solid fa-floppy-disk"></i> Save</button>
                                </span>
                            </div>
                        </form>

                        <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;border-top:1px solid #f1f5f9;padding-top:10px">
                            <a href="{{ route($routePrefix.'test_bank.index', ['category_id' => $category->id]) }}" class="btn btn-secondary" style="padding:7px 14px">
                                <i class="fa-solid fa-list-check"></i> View questions
                            </a>

                            {{-- Delete sits in its own form so it cannot submit the
                                 edit fields above by accident. --}}
                            <form method="POST" action="{{ route($routePrefix.'question_banks.categories.destroy', $category) }}"
                                  onsubmit="return confirm('Delete this category? Questions using it must be reassigned first.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-icon btn-danger" title="Delete"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="user-pagination" style="margin-top:18px">
                {{ $categories->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
