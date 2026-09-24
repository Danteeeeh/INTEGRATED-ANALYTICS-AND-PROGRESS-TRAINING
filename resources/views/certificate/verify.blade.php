@extends('layouts.app')

@section('title', 'Certificate Verification')

@section('content')
<div class="user-page" style="max-width:720px;margin:0 auto">
    @if($valid ?? false)
        <x-user-page-header
            title="Certificate Verified"
            subtitle="This certificate is authentic and was issued by {{ config('app.name') }}."
            icon="fa-shield-check"
            kicker="Public verification"
        >
            <x-slot name="meta">
                <span class="user-status active"><i class="fa-solid fa-circle-check"></i> Valid</span>
            </x-slot>
        </x-user-page-header>

        <div class="user-panel">
            <div class="user-panel-head">
                <h3><i class="fa-solid fa-certificate"></i> Certificate Details</h3>
            </div>
            <div class="user-panel-body">
                <div class="form-grid">
                    <div class="form-field">
                        <label>Certificate Number</label>
                        <div><strong>{{ $certificate['certificate_number'] ?? '—' }}</strong></div>
                    </div>
                    <div class="form-field">
                        <label>Issued To</label>
                        <div>{{ $certificate['student_name'] ?? '—' }}</div>
                    </div>
                    <div class="form-field">
                        <label>Course</label>
                        <div>{{ $certificate['course_title'] ?? '—' }}</div>
                    </div>
                    <div class="form-field">
                        <label>Issue Date</label>
                        <div>{{ optional($certificate['issue_date'] ?? null)?->format('F j, Y') ?? $certificate['issue_date'] ?? '—' }}</div>
                    </div>
                    @if(($certificate['final_grade'] ?? null) !== null)
                        <div class="form-field">
                            <label>Final Grade</label>
                            <div>{{ $certificate['final_grade'] }}%</div>
                        </div>
                    @endif
                    <div class="form-field">
                        <label>Status</label>
                        <div><x-user-status-badge status="{{ $certificate['status'] ?? 'issued' }}" /></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="user-toolbar">
            <i class="fa-solid fa-circle-check" style="color:var(--user-success)"></i>
            <span>This certificate has been verified as authentic.</span>
        </div>
    @else
        <x-user-page-header
            title="Certificate Not Found"
            subtitle="We could not verify a certificate with this code."
            icon="fa-shield-xmark"
            kicker="Public verification"
        >
            <x-slot name="meta">
                <span class="user-status"><i class="fa-solid fa-xmark"></i> Invalid</span>
            </x-slot>
        </x-user-page-header>

        <div class="user-panel">
            <div class="user-panel-body">
                <x-user-empty-state
                    icon="fa-shield-xmark"
                    title="Verification failed"
                    description="The verification code you provided does not match any issued certificate. Please check the code and try again."
                />
            </div>
        </div>
    @endif

    <div class="user-actions" style="justify-content:center">
        <a href="{{ url('/') }}" class="btn btn-secondary"><i class="fa-solid fa-home"></i> Back to Home</a>
    </div>
</div>
@endsection
