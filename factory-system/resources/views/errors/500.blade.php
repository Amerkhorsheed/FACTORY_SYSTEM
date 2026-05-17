@extends('errors.layout')

@section('title', __('errors.500.title'))
@section('code', '500')
@section('heading', __('errors.500.title'))
@section('message', __('errors.500.message'))
@section('href', url('/'))
@section('action', __('errors.500.action'))
