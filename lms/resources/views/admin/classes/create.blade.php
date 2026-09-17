@extends('layouts.admin-sms')

@section('title', 'Create Class')

@php($activeNav = 'classes')

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title"><i class="fa-solid fa-chalkboard-user"></i> Create Class</h2>
        <div class="page-actions">
            <a href="{{ route('admin.classes.index') }}" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Back to classes</a>
        </div>
    </div>
@endsection

@section('content')
    @include('admin.classes._form')
@endsection
