@extends('layouts.app')

@section('body-class', 'role-instructor')

@section('sidebar')
    @include('components.instructor-sidebar', ['activeNav' => $activeNav ?? 'dashboard'])
@endsection
