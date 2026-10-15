@extends('layouts.app')

@php
    $activeNav = $activeNav ?? 'dashboard';
@endphp

@section('body-class', 'role-aa')

@section('sidebar')
    @include('components.aa-sidebar', ['activeNav' => $activeNav])
@endsection
