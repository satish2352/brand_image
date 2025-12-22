@extends('website.layout')

@section('title', 'Checkout')

@section('content')
<div class="container my-5">
    <h3>Checkout</h3>

    <p><strong>Total Amount:</strong> ₹ {{ number_format($total, 2) }}</p>

    <button id="payBtn" class="btn btn-success">
        Pay with Razorpay
    </button>
</div>

<form id="paymentForm" method="POST" action="{{ route('payment.success') }}">
    @csrf
    <input type="hidden" name="razorpay_payment_id">
    <input type="hidden" name="razorpay_signature">
</form>

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
    document.getElementById('payBtn').onclick = function () {

    fetch("{{ route('checkout.pay') }}", {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": "{{ csrf_token() }}",
            "Accept": "application/json"
        }
    })
    .then(res => res.json())
    .then(data => {

        if (data.error) {
            alert(data.error);
            return;
        }

        var options = {
            key: data.key,
            amount: data.amount,
            currency: "INR",
            order_id: data.order_id,

            handler: function (response) {

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
{{-- <script>
document.getElementById('payBtn').onclick = function () {

    // STEP 1️⃣ CREATE ORDER
    fetch("{{ route('checkout.place') }}", {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": "{{ csrf_token() }}",
            "Accept": "application/json"
        }
    })
    .then(res => res.json())
    .then(orderResponse => {

        if (orderResponse.error) {
            alert(orderResponse.error);
            return;
        }

        // STEP 2️⃣ CREATE RAZORPAY ORDER
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
                name: "Media Booking",
                description: "Media Advertisement Payment",
                handler: function (response) {

                    document.querySelector(
                        'input[name=razorpay_payment_id]'
                    ).value = response.razorpay_payment_id;

                    document.querySelector(
                        'input[name=razorpay_signature]'
                    ).value = response.razorpay_signature;

                    document.getElementById('paymentForm').submit();
                }
            };

            var rzp = new Razorpay(options);
            rzp.open();
        });
    });
};
</script> --}}
@endsection
