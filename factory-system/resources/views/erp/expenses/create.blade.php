@extends('layouts.app')
@section('title', __('expenses.create'))
@section('page-title', __('expenses.expenses'))

@section('content')
<x-page-header :title="__('expenses.create')" :back="route('erp.expenses.index')" />
<form method="POST" action="{{ route('erp.expenses.store') }}" class="max-w-4xl">@include('erp.expenses._form')</form>
@endsection
