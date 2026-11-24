{{-- Wraps the shared admin Media Management form (create / edit views) in the
     vendor panel chrome. Those views extend $formLayout, which is this file. --}}
@extends('vendor.layout')
@section('title', $formHeading ?? 'Media')
@section('crumb')
    <a href="{{ route('vendor.inventory') }}">Inventory</a> / {{ $formHeading ?? 'Media' }}
@endsection
@section('heading', $formHeading ?? 'Media')
