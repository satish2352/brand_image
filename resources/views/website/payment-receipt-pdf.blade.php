<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 13px;
            color: #000;
        }

        .header-table {
            width: 100%;
            border-bottom: 2px solid #000;
            margin-bottom: 15px;
        }

        .header-table td {
            vertical-align: middle;
        }

        .header-title {
            font-size: 22px;
            font-weight: bold;
            text-align: right;
        }

        .info-table {
            width: 100%;
            margin-bottom: 15px;
        }

        .info-table td {
            vertical-align: top;
            padding: 3px 0;
        }

        .badge {
            background: #28a745;
            color: #fff;
            padding: 3px 8px;
            font-size: 12px;
            border-radius: 4px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .items-table th,
        .items-table td {
            border: 1px solid #000;
            padding: 6px;
            text-align: center;
        }

        .items-table th {
            background: #f2f2f2;
        }

        .total-table {
            width: 35%;
            margin-left: auto;
            margin-top: 15px;
            border-collapse: collapse;
        }

        .total-table td {
            border: 1px solid #000;
            padding: 6px;
        }

        .total-table .bold {
            font-weight: bold;
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
            font-size: 14px;
            font-weight: 700;
            border-top: 2px solid #000;
        }

        .summary-row .summary-label,
        .summary-row .summary-value{
            background: #eaf7ee;
            font-size: 12px;
            font-weight: 700;
            /* border-top: 2px solid #000; */
        }

        /* Space between items & totals */
        .tbody-spacer td {
            height: 18px;        /* adjust space here */
            border: none !important;
            background: transparent;
        }
    </style>
</head>

<body>

    @php $order = $items->first(); @endphp

    <!-- HEADER -->
    <table class="header-table">
        <tr>
            <td>
                <img src="{{ public_path('asset/theamoriginalalf/images/logo.png') }}" height="45">
            </td>
            <td class="header-title">
                RECEIPT
            </td>
        </tr>
    </table>

    <!-- INFO -->
    {{-- <table class="info-table">
        <tr>
            <td width="60%">
                <b>Location:</b> {{ $order->common_stdiciar_name }}<br><br>

                <b>Status:</b>
                <span class="badge">PAID</span><br><br>

                <b>Date:</b> {{ now()->format('d M Y') }}
                &nbsp;&nbsp;
                <b>Campaign name:</b> {{ $order->campaign_name ?? '-' }}
            </td>

            <td width="22%" style="line-height:0.8; word-break:break-word;">
                <b>Issued To:</b><br><br>

                Name: {{ auth('website')->user()->name }}<br><br>
                Mobile No: {{ auth('website')->user()->mobile_number ?? '-' }}<br><br>
                Email: {{ auth('website')->user()->email }}
            </td>
        </tr>
    </table> --}}

    <table class="info-table">
        <tr>
            <!-- LEFT META -->
            <td width="60%">
                <table width="100%">
                    <tr>
                        <td width="45"><b>Location</b></td>
                        <td width="10">:</td>
                        <td>{{ $order->common_stdiciar_name }}</td>
                    </tr>
                    <tr>
                        <td><b>Status</b></td>
                        <td>:</td>
                        <td><span class="badge">PAID</span></td>
                    </tr>
                    <tr>
                        <td><b>Date</b></td>
                        <td>:</td>
                        <td>
                            {{ now()->format('d M Y') }}
                            &nbsp;&nbsp;
                            <b>Campaign name:</b> {{ $order->campaign_name ?? '-' }}
                        </td>
                    </tr>
                </table>
            </td>

            <!-- RIGHT (ISSUED TO – UNCHANGED) -->
            <td width="22%" style="line-height:0.8; word-break:break-word;">
                <b>Issued To:</b><br><br>

                Name: {{ auth('website')->user()->name }}<br><br>
                Mobile No: {{ auth('website')->user()->mobile_number ?? '-' }}<br><br>
                Email: {{ auth('website')->user()->email }}
            </td>
        </tr>
    </table>

    <!-- ITEMS -->
    <table class="items-table">
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
            @foreach ($items as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item->common_stdiciar_name }}</td>
                    <td>
                        {{ $item->media_title }}<br>
                        {{ $item->width }} × {{ $item->height }}
                    </td>
                    <td>
                        {{ \Carbon\Carbon::parse($item->from_date)->format('d M Y') }}
                    </td>

                    <td>
                        {{ \Carbon\Carbon::parse($item->to_date)->format('d M Y') }}
                    </td>
                    <td>₹{{ number_format($item->price, 2) }}</td>
                    <td>₹{{ number_format($item->price, 2) }}</td>
                </tr>
            @endforeach

            {{-- SPACE ROW --}}
            <tr class="tbody-spacer">
                <td colspan="7"></td>
            </tr>
        </tbody>
        <tfoot>
            <tr class="summary-row">
                <td colspan="5" class="no-border"></td>
                <td class="summary-label">Subtotal</td>
                <td class="summary-value">₹{{ number_format($order->total_amount, 2) }}</td>
            </tr>

            <tr class="summary-row">
                <td colspan="5" class="no-border"></td>
                <td class="summary-label">GST (18%)</td>
                <td class="summary-value">₹{{ number_format($order->gst_amount, 2) }}</td>
            </tr>

            <tr class="grand-total-row">
                <td colspan="5" class="no-border"></td>
                <td class="summary-label">Grand Total</td>
                <td class="summary-value">₹{{ number_format($order->grand_total, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <!-- TOTAL -->
    {{-- <table class="total-table">
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
    </table> --}}

</body>

</html>
