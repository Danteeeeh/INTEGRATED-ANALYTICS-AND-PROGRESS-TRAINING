@extends('layouts.admin-sms')

@section('title', 'Admin Dashboard')
@php
    $activeNav = 'dashboard';
    $pageTitle = 'Dashboard';
    $pageIcon = '<i class="fa-solid fa-gauge"></i>';
@endphp

@section('content')
    <!-- Info Cards Row -->
    <div class="info-row">
        <x-sms-info-card 
            label="Students" 
            :name="auth()->user()->name ?? 'Admin User'"
            :details="['Student ID: ' . (auth()->user()->id ?? 'N/A'), '4th Year']"
            icon="fa-solid fa-user-graduate"
        />
        
        <x-sms-info-card 
            label="Adviser" 
            name="Dr. Smith"
            :details="['Section: 41018']"
            buttonText="Go to Profile"
            buttonLink="#"
            icon="fa-solid fa-chalkboard-user"
        />
        
        <x-sms-info-card 
            label="Current Semester" 
            status="Pending"
            amount="Php 6,000"
            buttonText="Check Summary"
            buttonLink="#"
        />
    </div>

    <!-- Chart Card -->
    <x-sms-chart-card 
        title="Data Report" 
        subtitle="Report For The Month"
        buttonText="Go to Report"
        buttonLink="#"
        chartId="reportChart"
    />

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
        <x-sms-info-card 
            label="Total Students" 
            :amount="$stats['total_students'] ?? 0"
            icon="fa-solid fa-users"
        />
        
        <x-sms-info-card 
            label="Total Instructors" 
            :amount="$stats['total_instructors'] ?? 0"
            icon="fa-solid fa-chalkboard-user"
        />
        
        <x-sms-info-card 
            label="Total Courses" 
            :amount="$stats['total_courses'] ?? 0"
            icon="fa-solid fa-book"
        />
    </div>

@endsection

@push('scripts')
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
@endpush