@extends('layouts.admin')

@section('title', 'Check Available Scores')

@php
    $activeNav = 'subjects';
    $pageTitle = 'Check Available Scores';
    $pageIcon = '<i class="fa-solid fa-clipboard-check"></i>';
@endphp

@section('content')
    @include('subjects.scores')
@endsection