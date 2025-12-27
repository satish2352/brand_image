@extends('website.layout')

@section('title', 'My Cart')

@section('content')


<div class="container my-5">

  <div class="d-flex justify-content-end">
  
  </div>


    <h3 class="mb-4">My Cart</h3>

    {{-- Success Message --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- Empty Cart --}}
    @if($items->count() === 0)
        <p>Your cart is empty.</p>
        <a href="{{ url('/') }}" class="btn btn-primary">Go Back</a>
    @else

    <table class="table table-bordered text-center align-middle">
        <thead>
            <tr>
                <th>Media</th>
                <th>Price</th>
                <th width="180">Quantity</th>
                <th>Total</th>
                <th>Action</th>
            </tr>
        </thead>

        <tbody>
        @php $grandTotal = 0; @endphp

        @foreach($items as $item)
            @php
                $total = $item->price * $item->qty;
                $grandTotal += $total;
            @endphp

            <tr>
                <td>
                    {{ $item->media_title ?? $item->category_name }}
                </td>

                <td>
                    ₹ {{ number_format($item->price, 2) }}
                </td>

                <td>
                    <form method="POST"
                          action="{{ route('cart.update') }}"
                          class="d-flex justify-content-center align-items-center">
                        @csrf

                        <input type="hidden" name="item_id" value="{{ $item->id }}">

                        {{-- <button type="submit"
                                name="qty"
                                value="{{ $item->qty - 1 }}"
                                class="btn btn-sm btn-secondary">−</button> --}}

                        <input type="text"
                               value="{{ $item->qty }}"
                               class="form-control text-center mx-2"
                               style="width:50px"
                               readonly>

                        {{-- <button type="submit"
                                name="qty"
                                value="{{ $item->qty + 1 }}"
                                class="btn btn-sm btn-secondary">+</button> --}}
                    </form>
                </td>

                <td>
                    ₹ {{ number_format($total, 2) }}
                </td>

                <td>
                    <a href="{{ route('cart.remove', encrypt($item->id)) }}"
                       class="btn btn-sm btn-danger">
                        Remove
                    </a>
                </td>
            </tr>
        @endforeach
        </tbody>

        <tfoot>
            <tr>
                <th colspan="3">Grand Total</th>
                <th colspan="2">
                    ₹ {{ number_format($grandTotal, 2) }}
                </th>
            </tr>
        </tfoot>
    </table>

    <div class="text-end mt-3">
        <a href="{{ url('/') }}" class="btn btn-outline-secondary">
            Continue Shopping
        </a>

<form action="{{ route('checkout.create') }}" method="POST" class="d-inline">
    @csrf
    <button type="submit" class="btn btn-success">
        Proceed to Checkout
    </button>
</form>
@if($items->count() > 0)
<button type="button"
    class="btn btn-success"
    data-bs-toggle="modal"
    data-bs-target="#campaignModal">
    Campaign
</button>
@endif

        {{-- <button type="button"
        class="btn btn-success"
        data-bs-toggle="modal"
        data-bs-target="#campaignModal">
    Campaign
</button> --}}
    </div>

    @endif
</div>


<!-- Campaign Modal -->
<div class="modal fade" id="campaignModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <form method="POST" action="{{ route('campaign.store') }}">
  
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title">Create Campaign</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Campaign Name</label>
                        <input type="text"
                               name="campaign_name"
                               class="form-control"
                               placeholder="Enter campaign name"
                               required>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit"
                            class="btn btn-success">
                        Save
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

@endsection
