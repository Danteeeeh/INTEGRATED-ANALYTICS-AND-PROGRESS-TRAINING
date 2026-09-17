@extends('layouts.admin')

@section('title', $certificate->certificate_number ?? 'Certificate')
@php $activeNav = 'certificates'; @endphp

@section('content')
<div class="user-page">
    <x-user-page-header
        title="{{ $certificate->certificate_number ?? 'Certificate' }}"
        subtitle="Certificate details and verification information."
        icon="fa-certificate"
    >
        <x-slot name="meta">
            <x-user-status-badge status="{{ $certificate->status }}" />
            @if($certificate->status === 'revoked')
                <span class="user-status">Revoked {{ $certificate->revoked_at?->format('M j, Y') }}</span>
            @endif
        </x-slot>
        <x-slot name="actions">
            <a href="{{ route('admin.certificates.download', $certificate) }}" class="btn btn-primary"><i class="fa-solid fa-download"></i> Download</a>
            <a href="{{ route('admin.certificates.edit', $certificate) }}" class="btn btn-secondary"><i class="fa-solid fa-pen"></i> Edit</a>
            <a href="{{ route('admin.certificates.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back</a>
        </x-slot>
    </x-user-page-header>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-info-circle"></i> Certificate Details</h3>
        </div>
        <div class="user-panel-body">
            <div class="form-grid">
                <div class="form-field">
                    <label>Certificate Number</label>
                    <div><strong>{{ $certificate->certificate_number }}</strong></div>
                </div>
                <div class="form-field">
                    <label>Verification Code</label>
                    <div><code>{{ $certificate->verification_code }}</code></div>
                </div>
                <div class="form-field">
                    <label>Student</label>
                    <div>{{ $certificate->student?->name ?? $certificate->student_name_display ?? '—' }}</div>
                </div>
                <div class="form-field">
                    <label>Course</label>
                    <div>{{ $certificate->course_name_display ?? $certificate->course?->title ?? '—' }}</div>
                </div>
                <div class="form-field">
                    <label>Class</label>
                    <div>{{ $certificate->class?->code ?? '—' }}</div>
                </div>
                <div class="form-field">
                    <label>Issued At</label>
                    <div>{{ $certificate->issued_at?->format('M j, Y g:i A') ?? '—' }}</div>
                </div>
                <div class="form-field">
                    <label>Completion Date</label>
                    <div>{{ $certificate->completion_date?->format('M j, Y') ?? '—' }}</div>
                </div>
                <div class="form-field">
                    <label>Final Grade</label>
                    <div>{{ $certificate->final_grade !== null ? $certificate->final_grade.'%' : '—' }}</div>
                </div>
                <div class="form-field">
                    <label>Template</label>
                    <div>{{ $certificate->template_name ?? 'Standard' }}</div>
                </div>
                <div class="form-field">
                    <label>Status</label>
                    <div><x-user-status-badge status="{{ $certificate->status }}" /></div>
                </div>
                @if($certificate->revoke_reason)
                    <div class="form-field full">
                        <label>Revoke Reason</label>
                        <div>{{ $certificate->revoke_reason }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="user-panel">
        <div class="user-panel-head">
            <h3><i class="fa-solid fa-shield-check"></i> Public Verification</h3>
        </div>
        <div class="user-panel-body">
            <p class="user-email">Anyone can verify this certificate by visiting the public verification page with the verification code:</p>
            <div class="user-toolbar">
                <input class="form-control" readonly value="{{ url('/certificate/verify/' . $certificate->verification_code) }}" aria-label="Verification URL">
                <a class="btn btn-secondary" href="{{ url('/certificate/verify/' . $certificate->verification_code) }}" target="_blank" rel="noopener">
                    <i class="fa-solid fa-up-right-from-square"></i> Open
                </a>
            </div>
        </div>
    </div>

    <div class="user-actions">
        @if($certificate->status === 'issued')
            <form action="{{ route('admin.certificates.destroy', $certificate) }}" method="POST" onsubmit="return confirm('Revoke this certificate?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger"><i class="fa-solid fa-ban"></i> Revoke</button>
            </form>
        @endif
    </div>
</div>
@endsection
