@extends('layouts.app')

@php
    $activeNav = $activeNav ?? 'dashboard';
@endphp

@section('body-class', 'role-admin')

@section('sidebar')
    @include('components.admin-sidebar', ['activeNav' => $activeNav])
@endsection
