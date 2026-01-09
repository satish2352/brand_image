@extends('website.layout')
{{-- @extends('layouts.invoice') --}}

@section('title', 'Receipt')

@section('content')
<style>
.invoice-wrapper{
    display:flex;
    justify-content:center;
    padding:30px 0;
}
.invoice-card{
    width:210mm;
    background:#fff;
    padding:18mm;
    font-family: Arial, sans-serif;
    font-size:14px;
    color:#000;
    box-shadow:0 10px 25px rgba(0,0,0,.15);
    margin-top: 5rem;
}

.user-details p{
    line-height: 0.5;
}

/* HEADER */
.invoice-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
}
.invoice-header h2{
    margin:0;
    font-weight:700;
}
.hr-line{
    border-top:2px solid #000;
    margin:10px 0 15px;
}

/* META */
.invoice-meta{
    display:flex;
    justify-content:space-between;
}
.badge-paid{
    background:#28a745;
    color:#fff;
    padding:3px 10px;
    font-size:12px;
    border-radius:4px;
}

/* TABLE */
.invoice-table{
    width:100%;
    border-collapse:collapse;
    margin-top:20px;
}
.invoice-table th,
.invoice-table td{
    border:1px solid #000;
    padding:8px;
    text-align:center;
}
.invoice-table th{
    background:#f2f2f2;
}

/* TOTAL */
.invoice-total{
    width:38%;
    margin-left:auto;
    margin-top:15px;
}
.invoice-total td{
    border:1px solid #000;
    padding:8px;
}
.invoice-total .bold{
    font-weight:bold;
}

/* ACTIONS */
.invoice-actions{
    margin-top:20px;
    display:flex;
    gap:10px;
}

/* PRINT */
@media print{
    nav,footer,.invoice-actions{display:none!important;}
    .invoice-card{box-shadow:none;}
}

@media print {

    html, body {
        width: 210mm;
        height: 297mm;
        margin: 0;
        padding: 0;
        background: #fff;
    }

    body * {
        visibility: hidden;
    }

    .invoice-wrapper,
    .invoice-wrapper * {
        visibility: visible;
    }

    .invoice-wrapper {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }

    .invoice-actions {
        display: none !important;
    }
}

</style>

<div class="invoice-wrapper">
<div class="invoice-card">

{{-- HEADER --}}
<div class="invoice-header">
    <img src="{{ asset('asset/theamoriginalalf/images/logo.png') }}" height="45">
    <h2>RECEIPT</h2>
</div>
<div class="hr-line"></div>

{{-- META --}}
<div class="invoice-meta">
    <div>
        <p><b>Location:</b> {{ $items->first()->common_stdiciar_name }}</p>
        <p><b>Status:</b> <span class="badge-paid">PAID</span></p>
        <p>
            <b>Date:</b> {{ now()->format('d M Y') }}
            &nbsp;&nbsp;
            <b>Campaignname:</b> {{ $items->first()->campaign_name ?? '-' }}
        </p>
    </div>

    <div class="user-details">
        <p><b>Issued To:</b></p>
        <p>Name: {{ auth('website')->user()->name }}</p>
        <p>Mobile No: {{ auth('website')->user()->mobile_number ?? '-' }}</p>
        <p>Email: {{ auth('website')->user()->email }}</p>
    </div>
</div>

{{-- TABLE --}}
<table class="invoice-table">
<thead>
<tr>
    <th>Sr. No.</th>
    <th>Location</th>
    <th>Media / Size</th>
    <th>From Date</th>
    <th>To Date</th>
    <th>Unit Price</th>
    <th>Total</th>
</tr>
</thead>
<tbody>
{{-- @php $subtotal = 0; @endphp
@foreach($items as $i=>$item)
@php $subtotal += $item->price; @endphp --}}

@foreach($items as $i => $item)
<tr>
    <td>{{ $i + 1 }}</td>
    <td>{{ $item->common_stdiciar_name }}</td>
    <td>
        {{ $item->media_title }}<br>
        <small>{{ $item->width }} × {{ $item->height }}</small>
    </td>
    <td>
        {{ \Carbon\Carbon::parse($item->from_date)->format('d M Y') }}
    </td>

    <td>
        {{ \Carbon\Carbon::parse($item->to_date)->format('d M Y') }}
    </td>
    <td>₹ {{ number_format($item->price, 2) }}</td>
    <td>₹ {{ number_format($item->price, 2) }}</td>
</tr>
@endforeach
</tbody>
</table>

{{-- TOTAL --}}
{{-- <div class="invoice-total">
<table width="100%">
<tr>
    <td>Subtotal</td>
    <td>₹ {{ number_format($subtotal,2) }}</td>
</tr>
<tr>
    <td>GST(18%)</td>
    <td>₹ {{ number_format($subtotal*0.18,2) }}</td>
</tr>
<tr class="bold">
    <td>Total</td>
    <td>₹ {{ number_format($subtotal*1.18,2) }}</td>
</tr>
</table>
</div> --}}
@php
    $order = $items->first();
@endphp
<div class="invoice-total">
<table width="100%">
<tr>
    <td>Subtotal</td>
    <td>₹ {{ number_format($order->total_amount, 2) }}</td>
</tr>
<tr>
    <td>GST (18%)</td>
    <td>₹ {{ number_format($order->gst_amount, 2) }}</td>
</tr>
<tr class="bold">
    <td>Total</td>
    <td>₹ {{ number_format($order->grand_total, 2) }}</td>
</tr>
</table>
</div>

{{-- ACTIONS --}}
<div class="invoice-actions">
    <a href="{{ route('campaign.list') }}" class="btn btn-secondary">Back</a>
    <button onclick="window.print()" class="btn btn-primary">Print Receipt</button>
    <a href="{{ route('invoice.download', base64_encode($orderId)) }}"
   class="btn btn-success">Download PDF</a>
</div>

</div>
</div>
@endsection
