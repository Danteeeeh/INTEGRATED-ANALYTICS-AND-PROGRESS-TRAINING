@extends('layouts.student')

@php
    $activeNav = 'subjects';
    $pageTitle = 'My Subjects';
    $pageIcon = '<i class="fa-solid fa-book-open"></i>';
@endphp

@section('title', $pageTitle)

@section('content')
    @include('subjects.index')
@endsection