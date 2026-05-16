@extends('layouts.app')
@section('title', __('products.edit'))
@section('page-title', __('ui.modules.inventory'))

@section('content')
<x-page-header :title="__('products.edit')" :description="$product->name" :back="route('products.show', $product)" />
<form method="POST" action="{{ route('products.update', $product) }}" enctype="multipart/form-data" class="max-w-5xl">
    @method('PUT')
    @include('products._form')
</form>
@endsection
