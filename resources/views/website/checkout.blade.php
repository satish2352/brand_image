@extends('website.layout')

@section('title', 'Checkout')

@section('content')

    <!-- breadcrumb-section -->

    <div class="container-fluid about-banner-img g-0">
        <div class="row">
            <!-- Desktop Image -->
            <div class="col-md-12 d-none d-md-block">
                <img src="{{ asset('assets/img/contactus1.png') }}" alt="About Banner" class="img-fluid">
            </div>

            <!-- Mobile Image -->
            <div class="col-md-12 d-block d-md-none">
                <img src="{{ asset('assets/img/contactusmobileview.png') }}" alt="About Banner" class="img-fluid">
            </div>
        </div>
    </div>
    <!-- end breadcrumb section -->

    <div class="container my-5">

        <h3 class="mb-4">Checkout</h3>

        {{-- ORDER ITEMS --}}
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="mb-3">Order Summary</h5>

                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Location</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>

                        @foreach ($items as $item)
                            <tr>
                                <td>{{ $item->area_name }} {{ $item->facing }}</td>
                                <td class="text-end">₹ {{ number_format($item->price, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- PRICE BREAKUP --}}
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="mb-3">Payment Details</h5>

                <div class="d-flex justify-content-between">
                    <span>Sub Total</span>
                    <strong>₹ {{ number_format($subTotal, 2) }}</strong>
                </div>

                <div class="d-flex justify-content-between">
                    <span>GST ({{ $gstRate }}%)</span>
                    <strong>₹ {{ number_format($gstAmount, 2) }}</strong>
                </div>

                <hr>

                <div class="d-flex justify-content-between fs-5">
                    <span>Total Payable</span>
                    <strong class="text-success">
                        ₹ {{ number_format($grandTotal, 2) }}
                    </strong>
                </div>
            </div>
        </div>

        {{-- PAY BUTTON --}}
        <div class="d-flex justify-content-end">
            <button id="payBtn" class="btn btn-success btn-lg">
                Pay ₹ {{ number_format($grandTotal, 2) }} with Razorpay
            </button>

        </div>
    </div>

    <form id="paymentForm" method="POST" action="{{ route('payment.success') }}">
        @csrf
        <input type="hidden" name="razorpay_payment_id">
        <input type="hidden" name="razorpay_signature">
    </form>

    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>

    <script>
        document.getElementById('payBtn').onclick = function() {

            fetch("{{ route('checkout.pay') }}", {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": "{{ csrf_token() }}",
                        "Accept": "application/json"
                    }
                })
                .then(res => res.json())
                .then(data => {

                    var options = {
                        key: data.key,
                        amount: data.amount,
                        currency: "INR",
                        order_id: data.order_id,

                        handler: function(response) {
                            document.querySelector(
                                'input[name=razorpay_payment_id]'
                            ).value = response.razorpay_payment_id;

                            document.querySelector(
                                'input[name=razorpay_signature]'
                            ).value = response.razorpay_signature;

                            document.getElementById('paymentForm').submit();
                        }
                    };

                    new Razorpay(options).open();
                });
        };
    </script>
@endsection
