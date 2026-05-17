@extends('errors.layout')

@section('title', __('errors.503.title'))
@section('code', '503')
@section('heading', __('errors.503.title'))
@section('message', __('errors.503.message'))
@section('href', url()->current())
@section('action', __('errors.503.action'))
