@extends('errors.layout')

@section('title', __('errors.404.title'))
@section('code', '404')
@section('heading', __('errors.404.title'))
@section('message', __('errors.404.message'))
@section('href', route('dashboard'))
@section('action', __('errors.404.action'))
