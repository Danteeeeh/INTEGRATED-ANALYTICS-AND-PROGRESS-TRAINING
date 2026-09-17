@extends('layouts.app')

@section('body-class', 'role-registrar')

@section('sidebar')
    @include('components.registrar-sidebar', ['activeNav' => $activeNav ?? 'dashboard'])
@endsection
