@extends('layouts.app')
@section('title', __('admin.edit_user'))
@section('page-title', __('admin.edit_user'))

@section('content')
<x-page-header :title="__('admin.edit_user')" :description="$user->name" :back="route('admin.users.index')" />
<form method="POST" action="{{ route('admin.users.update', $user) }}" class="max-w-4xl">
    @method('PUT')
    <x-card>@include('admin.users._form')</x-card>
</form>
@endsection
