@extends('layouts.instructor')

@php
    $activeNav = 'subjects';
    $pageTitle = 'LMS Subjects';
    $pageIcon = '<i class="fa-solid fa-book-open"></i>';
@endphp

@section('title', $pageTitle)

@section('content')
    @include('subjects.index')
@endsection