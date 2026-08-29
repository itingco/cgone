@extends('layouts.app')
@section('title',($record->exists?'Edit ':'New ').$title)
@section('content')
@php $module = 'sales'; @endphp
@include('documents.form-core')
@endsection
