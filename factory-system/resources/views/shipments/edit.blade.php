@extends('layouts.app')
@section('title', __('shipments.edit'))
@section('page-title', __('shipments.shipments'))

@section('content')
<x-page-header :title="__('shipments.edit')" :description="$shipment->shipment_number" :back="route('shipments.show', $shipment)" />
<form method="POST" action="{{ route('shipments.update', $shipment) }}" class="max-w-4xl">@method('PUT') @include('shipments._form')</form>
@endsection
