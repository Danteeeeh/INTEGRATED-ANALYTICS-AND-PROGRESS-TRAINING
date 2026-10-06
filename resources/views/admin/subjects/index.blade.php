@extends('layouts.admin')

@section('title', 'LMS Subjects')

@php
    $activeNav = 'subjects';
    $pageTitle = 'LMS Subjects';
    $pageIcon = '<i class="fa-solid fa-book-open"></i>';
@endphp

@section('content')
    @include('subjects.index')
@endsection