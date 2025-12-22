@extends('website.layout')

@section('content')
<div class="container text-center my-5">
    <h2 class="text-success">Payment Successful 🎉</h2>
    <p>Your order has been placed successfully.</p>
    <a href="{{ url('/') }}" class="btn btn-primary mt-3">
        Back to Home
    </a>
</div>
@endsection
