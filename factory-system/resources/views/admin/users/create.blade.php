@extends('layouts.app')
@section('title', __('admin.create_user'))
@section('page-title', __('admin.create_user'))

@section('content')
<x-page-header :title="__('admin.create_user')" :back="route('admin.users.index')" />
<form method="POST" action="{{ route('admin.users.store') }}" class="max-w-4xl">
    <x-card>@include('admin.users._form')</x-card>
</form>
@endsection
