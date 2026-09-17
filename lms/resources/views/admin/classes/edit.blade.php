@extends('layouts.admin-sms')

@section('title', 'Edit Class')

@php($activeNav = 'classes')

@section('page-title-bar')
    <div class="page-title-bar">
        <h2 class="page-title"><i class="fa-solid fa-pen-to-square"></i> Edit Class</h2>
        <div class="page-actions">
            <a href="{{ route('admin.classes.show', $class) }}" class="btn btn-secondary"><i class="fa-solid fa-eye"></i> View class</a>
        </div>
    </div>
@endsection

@section('content')
    @include('admin.classes._form', ['class' => $class])
@endsection
