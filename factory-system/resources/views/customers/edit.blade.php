@extends('layouts.app')
@section('title', __('customers.edit'))
@section('page-title', __('ui.modules.customers'))

@section('content')
<x-page-header :title="__('customers.edit')" :description="$customer->name" :back="route('customers.show', $customer)" />
<form method="POST" action="{{ route('customers.update', $customer) }}" class="max-w-5xl">@method('PUT') @include('customers._form')</form>
@endsection
