@extends('layouts.sms')

@section('sidebar')
    @include('components.admin-sidebar', ['activeNav' => $activeNav ?? 'dashboard'])
@endsection

@section('notifications')
    <x-sms-notifications :notifications="[]" />
@endsection
