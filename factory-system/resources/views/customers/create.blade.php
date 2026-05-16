@extends('layouts.app')
@section('title', __('customers.create'))
@section('page-title', __('ui.modules.customers'))

@section('content')
<x-page-header :title="__('customers.create')" :back="route('customers.index')" />
<form method="POST" action="{{ route('customers.store') }}" class="max-w-5xl">@include('customers._form')</form>
@endsection
