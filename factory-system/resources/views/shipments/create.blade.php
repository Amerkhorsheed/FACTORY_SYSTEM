@extends('layouts.app')
@section('title', __('shipments.create'))
@section('page-title', __('shipments.shipments'))

@section('content')
<x-page-header :title="__('shipments.create')" :back="route('shipments.index')" />
<form method="POST" action="{{ route('shipments.store') }}" class="max-w-4xl">@include('shipments._form')</form>
@endsection
