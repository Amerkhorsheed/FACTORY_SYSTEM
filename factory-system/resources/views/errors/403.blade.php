@extends('errors.layout')

@section('title', __('errors.403.title'))
@section('code', '403')
@section('heading', __('errors.403.title'))
@section('message', __('errors.403.message'))
@section('href', route('dashboard'))
@section('action', __('errors.403.action'))
