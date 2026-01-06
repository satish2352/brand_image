@extends('website.layout')

@section('title', 'Receipt')

@section('content')
<style>
/* ================= SCREEN ================= */
.invoice-wrapper {
    display: flex;
    justify-content: center;
    padding: 30px 0;
}

.invoice-card {
    width: 210mm;
    background: #fff;
    padding: 20mm;
    font-family: Arial, sans-serif;
    font-size: 14px;
    color: #000;
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}

/* HEADER */
.invoice-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 2px solid #000;
    padding-bottom: 10px;
}

.logo {
    height: 50px;
}

/* META */
.invoice-meta {
    display: flex;
    justify-content: space-between;
    margin: 15px 0;
}

.text-right {
    text-align: right;
}

.badge-paid {
    background: #28a745;
    color: #fff;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 12px;
}

/* TABLE */
.invoice-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

.invoice-table th,
.invoice-table td {
    border: 1px solid #000;
    padding: 8px;
    text-align: center;
}

.invoice-table th {
    background: #f2f2f2;
}

/* TOTAL */
.invoice-total {
    width: 40%;
    margin-left: auto;
    margin-top: 15px;
}

.invoice-total td {
    border: 1px solid #000;
    padding: 8px;
}

.invoice-total .grand {
    font-weight: bold;
    background: #f2f2f2;
}

/* ACTION BUTTONS */
.invoice-actions {
    margin-top: 20px;
    display: flex;
    gap: 10px;
}

/* ================= PRINT ================= */
@page {
    size: A4;
    margin: 0;
}

@media print {

    html, body {
        width: 210mm;
        height: 297mm;
        margin: 0;
        padding: 0;
        background: #fff;
    }

    /* REMOVE WEBSITE UI */
    nav, header, footer, .invoice-actions {
        display: none !important;
    }

    .invoice-wrapper {
        padding: 0 !important;
        margin: 0 !important;
        display: block !important;
    }

    .invoice-card {
        width: 100%;
        padding: 15mm;
        box-shadow: none !important;
        border: none !important;
        page-break-inside: avoid;
    }

    .invoice-table,
    .invoice-total {
        page-break-inside: avoid;
    }
}

</style>
<div class="invoice-wrapper" >

    <div class="invoice-card">

        {{-- HEADER --}}
        <div class="invoice-header">
            <img src="{{ asset('asset/theamoriginalalf/images/logo.png') }}" class="logo">
            <h2>RECEIPT</h2>
        </div>

        {{-- META --}}
        <div class="invoice-meta">
            <div>
                <p><strong>Location:</strong> {{ $items->first()->common_stdiciar_name ?? '-' }}</p>
                <p><strong>Status:</strong> <span class="badge-paid">PAID</span></p>
                <p><strong>Date:</strong> {{ now()->format('d M Y') }}</p>
            </div>

            <div class="text-right">
                <p><strong>Issued To</strong></p>
                <p>{{ auth()->guard('website')->user()->name }}</p>
                <p class="muted">{{ auth()->guard('website')->user()->email }}</p>
            </div>
        </div>

        {{-- TABLE --}}
        <table class="invoice-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Location</th>
                    <th>Media / Size</th>
                    <th>Unit Price</th>
                    {{-- <th>Qty</th> --}}
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @php $subTotal = 0; @endphp
                @foreach($items as $i => $item)
                    @php
                        $lineTotal = $item->price;
                        $subTotal += $lineTotal;
                    @endphp
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $item->common_stdiciar_name }}</td>
                        <td>
                            {{ $item->media_title }}<br>
                            <small>{{ $item->width }} × {{ $item->height }}</small>
                        </td>
                        <td>₹ {{ number_format($item->price, 2) }}</td>
                        {{-- <td>{{ $item->qty }}</td> --}}
                        <td>₹ {{ number_format($lineTotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- TOTAL --}}
        <div class="invoice-total">
            <table>
                <tr class="grand">
                    <td>Total Amount</td>
                    <td>₹ {{ number_format($subTotal, 2) }}</td>
                </tr>
            </table>
        </div>

        {{-- ACTIONS --}}
        <div class="invoice-actions">
            <a href="{{ route('campaign.list') }}" class="btn btn-secondary">Back</a>
            <button onclick="window.print()" class="btn btn-primary">Print</button>
        </div>

    </div>
</div>

@endsection
