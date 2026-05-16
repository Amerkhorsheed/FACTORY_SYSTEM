@extends('layouts.app')
@section('title', __('orders.create'))
@section('page-title', __('ui.modules.orders'))

@section('content')
<x-page-header :title="__('orders.create')" :back="route('orders.index')" />
<form method="POST" action="{{ route('orders.store') }}" class="max-w-5xl">@include('orders._form')</form>
@endsection
