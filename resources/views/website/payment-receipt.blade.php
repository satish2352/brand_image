@extends('website.layout')
{{-- @extends('layouts.invoice') --}}

@section('title', 'Receipt')

@section('content')
    <style>
        
        .invoice-wrapper {
            display: flex;
            justify-content: center;
            padding: 30px 0;
        }

        .invoice-card {
            width: 240mm;
            background: #fff;
            padding: 18mm;
            font-family: Arial, sans-serif;
            font-size: 14px;
            color: #000;
            box-shadow: 0 10px 25px rgba(0, 0, 0, .15);
            margin-top: 5rem;
        }

        .user-details p {
            line-height: 0.5;
        }

        /* HEADER */
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .invoice-header h2 {
            margin: 0;
            font-weight: 700;
        }

        .hr-line {
            border-top: 2px solid #000;
            margin: 10px 0 15px;
        }

        /* META */
        .invoice-meta {
            display: flex;
            justify-content: space-between;
        }

        .badge-paid {
            background: #28a745;
            color: #fff;
            padding: 3px 10px;
            font-size: 12px;
            border-radius: 4px;
        }

        /* TABLE */
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
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
            width: 38%;
            margin-left: auto;
            margin-top: 15px;
        }

        .invoice-total td {
            border: 1px solid #000;
            padding: 8px;
        }

        .invoice-total .bold {
            font-weight: bold;
        }

        /* ACTIONS */
        .invoice-actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }

        /* PRINT */
        @media print {

            nav,
            footer,
            .invoice-actions {
                display: none !important;
            }

            @page {
                size: A4;
                margin: 8mm;
                /* change from default to small */
            }

            /* FULL WIDTH USAGE */
            .invoice-wrapper {
                justify-content: flex-start;
                padding: 0;
            }

            .invoice-card {
                width: 100%;
                margin: 0;
                padding: 6mm;
                /* earlier 18mm was too much */
                box-shadow: none;
            }
        }

        @media print {

            html,
            body {
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

        .meta-line {
            display: flex;
            align-items: flex-start;
            margin-bottom: 6px;
        }

        .meta-label {
            width: 60px;
            font-weight: bold;
        }

        .meta-colon {
            width: 10px;
            font-weight: bold;
        }

        .meta-value {
            flex: 1;
        }

        /* REMOVE borders from empty left area */
        .no-border {
            border: none !important;
            background: transparent !important;
        }

        /* Summary rows */
        .summary-row td {
            font-weight: 600;
            font-size: 14px;
        }

        /* Label column */
        .summary-label {
            text-align: right;
            background: #fafafa;
            border-left: 1px solid #000;
        }

        /* Value column */
        .summary-value {
            text-align: center;
            background: #fafafa;
        }

        /* Grand Total emphasis (NOT full width) */
        .grand-total-row .summary-label,
        .grand-total-row .summary-value {
            background: #eaf7ee;
            font-size: 15px;
            font-weight: 700;
            border-top: 2px solid #000;
        }

        /* Space between items & totals */
        .tbody-spacer td {
            height: 18px;
            /* adjust space here */
            border: none !important;
            background: transparent;
        }

        /* ===========================
   MOBILE RESPONSIVE
=========================== */

.table-responsive {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

@media (max-width: 992px) {

    .invoice-card {
        width: 100%;
        padding: 12px;
        margin-top: 2rem;
    }

    .invoice-header {
        /* flex-direction: column; */
        gap: 10px;
        text-align: center;
        margin-top: 1rem;
    }

    .invoice-header img {
        height: 40px;
    }

    .invoice-meta {
        /* flex-direction: column; */
        gap: 15px;
    }

    .user-details p {
        line-height: 1.4;
        font-size: 13px;
    }

    .invoice-table {
        min-width: 900px; /* forces scroll instead of squeeze */
        font-size: 12px;
    }

    .invoice-table th,
    .invoice-table td {
        padding: 6px;
        white-space: inherit;
    }

    .summary-label,
    .summary-value {
        font-size: 13px;
    }

    .grand-total-row .summary-label,
    .grand-total-row .summary-value {
        font-size: 14px;
    }

    /* .invoice-actions {
        flex-direction: column;
    } */

    .invoice-actions .btn {
        width: 100%;
    }
}

@media (max-width: 576px) {

    .invoice-card {
        padding: 10px;
    }

    .meta-label {
        width: 50px;
        font-size: 12px;
    }

    .meta-value {
        font-size: 12px;
    }

    .badge-paid {
        font-size: 11px;
        padding: 2px 8px;
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
                    {{-- <p><b>Location:</b> {{ $items->first()->common_stdiciar_name }}</p>
                    <p><b>Status:</b> <span class="badge-paid">PAID</span></p>
                    <p>
                        <b>Date:</b> {{ now()->format('d M Y') }}
                        &nbsp;&nbsp;
                        <b>Campaign name:</b> {{ $items->first()->campaign_name ?? '-' }}
                    </p> --}}
                    {{-- <p class="meta-line">
                        <span class="meta-label">Location</span>
                        <span class="meta-colon">:</span>
                        <span class="meta-value">{{ $items->first()->common_stdiciar_name }}</span>
                    </p> --}}
                    <p class="meta-line">
                        <span class="meta-label">Date</span>
                        <span class="meta-colon">:</span>
                        <span class="meta-value">
                            {{ now()->format('d M Y') }}

                            @if (!empty($items->first()->campaign_name))
                                &nbsp;&nbsp;&nbsp;
                                <b>Campaign name:</b> {{ $items->first()->campaign_name }}
                            @endif
                        </span>
                    </p>
                    <p class="meta-line">
                        <span class="meta-label">Status</span>
                        <span class="meta-colon">:</span>
                        <span class="meta-value"><span class="badge-paid">PAID</span></span>
                    </p>

                    {{-- <p class="meta-line">
                            <span class="meta-label">Date</span>
                            <span class="meta-colon">:</span>
                            <span class="meta-value">
                                {{ now()->format('d M Y') }}
                                &nbsp;&nbsp;&nbsp;
                                <b>Campaign name:</b> {{ $items->first()->campaign_name ?? '-' }}
                            </span>
                        </p> --}}


                </div>

                <div class="user-details">
                    <p><b>Issued To:</b></p>
                    <p>Name: {{ auth('website')->user()->name }}</p>
                    <p>Mobile No: {{ auth('website')->user()->mobile_number ?? '-' }}</p>
                    <p>Email: {{ auth('website')->user()->email }}</p>
                </div>
            </div>

            {{-- TABLE --}}
            <div class="table-responsive">
            <table class="invoice-table">
                <thead>
                    <tr>
                        <th>Sr. No.</th>
                        <th>Location</th>
                        <th>Media </th>
                        <th>Size</th> 
                        <th>From Date</th>
                        <th>To Date</th>
                        <th>Total Days</th>
                      
                    </tr>
                </thead>
                <tbody>
                    {{-- @php $subtotal = 0; @endphp
@foreach ($items as $i => $item)
@php $subtotal += $item->price; @endphp --}}

                    @foreach ($items as $i => $item)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $item->area_name }} {{ $item->facing }}</td>
                            <td>
                                {{ $item->media_title }}
                            </td>
                             <td> <small>{{ $item->width }} × {{ $item->height }}</small></td>
                            <td>
                                {{ \Carbon\Carbon::parse($item->from_date)->format('d M Y') }}
                            </td>

                            <td>
                                {{ \Carbon\Carbon::parse($item->to_date)->format('d M Y') }}
                            </td>
                            <td>{{ number_format($item->total_days) }}</td>
                            
                           
                        </tr>
                    @endforeach

                    {{-- SPACE ROW --}}
                    <tr class="tbody-spacer">
                        <td colspan="7"></td>
                    </tr>
                </tbody>
                <tfoot>
                    @php
                        $order = $items->first();
                    @endphp
                    <tr class="summary-row">
                        <td colspan="5" class="no-border"></td>
                        <td class="summary-label">Subtotal</td>
                        <td class="summary-value">₹ {{ number_format($order->total_amount, 2) }}</td>
                    </tr>

                    <tr class="summary-row">
                        <td colspan="5" class="no-border"></td>
                        <td class="summary-label">GST (18%)</td>
                        <td class="summary-value">₹ {{ number_format($order->gst_amount, 2) }}</td>
                    </tr>

                    <tr class="grand-total-row">
                        <td colspan="5" class="no-border"></td>
                        <td class="summary-label">Grand Total</td>
                        <td class="summary-value">₹ {{ number_format($order->grand_total, 2) }}</td>
                    </tr>
                </tfoot>

            </table>
            </div>

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
            {{-- @php
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
            </div> --}}

            {{-- ACTIONS --}}
            <div class="invoice-actions">
                <a href="{{ route('campaign.list') }}" class="btn btn-secondary">Back</a>
                <button onclick="window.print()" class="btn btn-primary">Print Receipt</button>
                <a href="{{ route('invoice.download', base64_encode($orderId)) }}" class="btn btn-success">Download PDF</a>
            </div>

        </div>
    </div>
@endsection
