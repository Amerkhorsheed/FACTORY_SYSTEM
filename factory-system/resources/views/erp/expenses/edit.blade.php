@extends('layouts.app')
@section('title', __('expenses.edit'))
@section('page-title', __('expenses.expenses'))

@section('content')
<x-page-header :title="__('expenses.edit')" :description="$expense->category" :back="route('erp.expenses.show', $expense)" />
<form method="POST" action="{{ route('erp.expenses.update', $expense) }}" class="max-w-4xl">@method('PUT') @include('erp.expenses._form')</form>
@endsection
