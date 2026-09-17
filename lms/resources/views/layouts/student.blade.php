@extends('layouts.app')

@section('body-class', 'role-student')

@section('sidebar')
    @include('components.student-sidebar', ['activeNav' => $activeNav ?? 'dashboard'])
@endsection
