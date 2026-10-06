@extends('layouts.instructor')

@php
    $activeNav = 'subjects';
    $pageTitle = 'Check Available Scores';
    $pageIcon = '<i class="fa-solid fa-clipboard-check"></i>';
@endphp

@section('title', $pageTitle)

@section('content')
    @include('subjects.scores')
@endsection