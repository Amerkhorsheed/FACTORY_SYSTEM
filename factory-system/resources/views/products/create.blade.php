@extends('layouts.app')
@section('title', __('products.create'))
@section('page-title', __('ui.modules.inventory'))

@section('content')
<x-page-header :title="__('products.create')" :back="route('products.index')" />
<form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data" class="max-w-5xl">
    @include('products._form')
</form>
@endsection
