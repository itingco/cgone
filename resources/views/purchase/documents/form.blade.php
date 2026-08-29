@extends('layouts.app')
@section('title',($record->exists?'Edit ':'New ').$title)
@section('content')
@php $module = 'purchase'; @endphp
@include('documents.form-core')
@endsection
