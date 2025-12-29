@extends('website.layout')

@section('title', 'Invoice Details')
<style>
    .invoice-ui-card {
    background: transparent;
    display: flex;
    justify-content: center;
}

.invoice-ui-card .invoice-card {
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    border-radius: 6px;
    transition: 0.3s;
}

  .invoice-wrapper {
  
    padding: 40px 0;
}

.invoice-card {
     width: 210mm;
    height: auto;
    margin: auto;
    background: #fff;
    padding: 30px;
    font-family: Arial, sans-serif;
    font-size: 14px;
    color: #333;

    
}

.invoice-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 15px;
    border-bottom: 2px solid #000;
    margin-bottom: 20px;
}

.logo {
    height: 55px;
}

.invoice-meta {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
}

/* ===== TABLE ===== */
.invoice-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

.invoice-table th,
.invoice-table td {
    border: 1.5px solid #000;
    padding: 10px;
    text-align: center;
    vertical-align: middle;
}

.invoice-table th {
    background: #f1f1f1;
    font-weight: bold;
}

/* ===== TOTAL BOX ===== */
.invoice-total {
    width: 35%;
    margin-left: auto;
    margin-top: 20px;
}

.invoice-total table {
    width: 100%;
    border-collapse: collapse;
}

.invoice-total td {
    border: 1.5px solid #000;
    padding: 10px;
}

.invoice-total .grand td {
    font-weight: bold;
    background: #f1f1f1;
}

/* ===== BADGES ===== */
.paid {
    background: #28a745;
    color: #fff;
    padding: 3px 10px;
    border-radius: 4px;
    font-size: 12px;
}

.muted {
    color: #666;
}

/* ===== ACTIONS ===== */
.invoice-actions {
    margin-top: 30px;
    display: flex;
    gap: 10px;
}

/* ===== PRINT FIXES ===== */
@page {
    size: A4;
    margin: 12mm;
}

@media print {

    .page-border {
        display: block;
        position: fixed;
        top: 0mm;
        left: 0mm;
        right: 0mm;
        bottom: 0mm;
        border: 2px solid #000;
        z-index: 1;
        pointer-events: none;
        padding: 20px;
    }

    /* REMOVE CARD BORDER / SHADOW */
    .invoice-ui-card .invoice-card {
        box-shadow: none !important;
        border-radius: 0 !important;
    }

    .invoice-card {
        border: none !important;
        position: relative;
        z-index: 2;
        padding: 20px;
    }

    nav, header, footer, .invoice-actions {
        display: none !important;
    }

    .invoice-table,
    .invoice-total {
        page-break-inside: avoid;
    }

    .invoice-table th,
    .invoice-table td,
    .invoice-total td {
        border: 1.5px solid #000 !important;
    }
}


</style>

@section('content')
<div class="invoice-wrapper">
    <div class="invoice-ui-card">
        <div class="invoice-card">
<div class="page-border">
        {{-- HEADER --}}
        <div class="invoice-header">
            <img src="{{ asset('asset/theamoriginalalf/images/logo.png') }}" class="logo">
            <h2>INVOICE</h2>
        </div>

        <div class="invoice-meta">
            <div>
                <p><strong>Location:</strong> {{ $items->first()->common_stdiciar_name ?? '-' }}</p>
                <p><strong>Status:</strong> <span class="paid">PAID</span></p>
                <p><strong>Date:</strong> {{ \Carbon\Carbon::now()->format('d M Y') }}</p>
            </div>

            <div class="text-end">
                <p><strong>Issued To</strong></p>
                <p>{{ auth()->guard('website')->user()->name }}</p>
                <p class="muted">{{ auth()->guard('website')->user()->email }}</p>
            </div>
        </div>

        {{-- ITEMS --}}
        <table class="invoice-table">
            <thead>
                <tr>
                    <th>Sr. No.</th>
                    <th>Location</th>
                    <th>Media / Size</th>
                    <th>Unit Price</th>
                    <th>Qty</th>
                    <th>Total</th>
                </tr>
            </thead>

            <tbody>
                @php $subTotal = 0; @endphp
                @foreach($items as $i => $item)
                    @php
                        // $lineTotal = $item->price * $item->qty;
                        $lineTotal = $item->price; // already total_price
                        $subTotal += $lineTotal;
                    @endphp
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $item->common_stdiciar_name }}</td>
                        <td>
                            {{ $item->media_title }} <br>
                            <small>{{ $item->width }} × {{ $item->height }}</small>
                        </td>
                        <td>₹ {{ number_format($item->price,2) }}</td>
                        <td>{{ $item->qty }}</td>
                        <td>₹ {{ number_format($lineTotal,2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- TOTALS --}}
        @php
            $tax = round($subTotal * 0.10, 2);
            $grand = $subTotal + $tax;
        @endphp

        <div class="invoice-total">
            <table>
                <tr>
                    <td>Subtotal</td>
                    <td>₹ {{ number_format($subTotal,2) }}</td>
                </tr>
                <tr>
                    <td>Tax (10%)</td>
                    <td>₹ {{ number_format($tax,2) }}</td>
                </tr>
                <tr class="grand">
                    <td>Total</td>
                    <td>₹ {{ number_format($grand,2) }}</td>
                </tr>
            </table>
        </div>

        {{-- ACTIONS --}}
        <div class="invoice-actions">
            <a href="{{ route('campaign.list') }}" class="btn btn-secondary">Back</a>
            <button onclick="window.print()" class="btn btn-primary">Print Invoice</button>
             <button onclick="downloadInvoice()" class="btn btn-success">
        Download PDF
    </button>
        </div>
        </div>
    </div>
    </div>
</div>
<script>
function downloadInvoice() {
    const originalTitle = document.title;
    document.title = "Invoice_" + new Date().getTime();
    window.print();
    document.title = originalTitle;
}
</script>

@endsection
