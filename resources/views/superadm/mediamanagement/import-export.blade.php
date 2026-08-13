@extends('superadm.layout.master')

@section('content')
    @php
        /**
         * Icon for a media category, matched on the category name so a renamed or
         * newly added master still picks up something meaningful. Order matters:
         * the first keyword found wins, so "Digital Wall painting" resolves to the
         * paint roller and not to the screen.
         */
        $categoryIcon = function ($name) {
            $needle = strtolower((string) $name);

            $map = [
                'fa-plane-departure' => ['airport'],
                'fa-scroll' => ['wrap'],
                'fa-paint-roller' => ['paint'],
                'fa-rectangle-ad' => ['hoarding', 'billboard'],
                'fa-building' => ['office', 'corporate'],
                'fa-bus-simple' => ['shelter'],
                'fa-bus' => ['transit', 'transmit', 'bus'],
                'fa-train-subway' => ['metro', 'train', 'rail'],
                'fa-taxi' => ['cab', 'taxi', 'auto', 'rickshaw'],
                'fa-bag-shopping' => ['mall'],
                'fa-road-bridge' => ['gantry', 'bridge', 'flyover'],
                'fa-shop' => ['kiosk', 'booth'],
                'fa-sign-hanging' => ['sign'],
                'fa-tv' => ['digital', 'led', 'screen', 'dooh'],
                'fa-tower-observation' => ['pole'],
                'fa-flag' => ['banner', 'flex'],
            ];

            foreach ($map as $icon => $keywords) {
                foreach ($keywords as $keyword) {
                    if (str_contains($needle, $keyword)) {
                        return $icon;
                    }
                }
            }

            return 'fa-bullhorn';
        };
    @endphp

    <style>
        .ie-page {
            --ie-primary: #7460ee;
            --ie-primary-dark: #5a45e0;
            --ie-primary-soft: rgba(116, 96, 238, .10);
            --ie-border: #e7eaf3;
            --ie-muted: #8d97ad;
            --ie-heading: #3e5569;
        }

        /* ---------- page header ---------- */
        .ie-head {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 22px;
        }

        .ie-head h4 {
            margin: 0;
            font-weight: 600;
            color: var(--ie-heading);
        }

        .ie-head .ie-head-sub {
            margin: 5px 0 0;
            font-size: 13px;
            color: var(--ie-muted);
        }

        /* ---------- tabs ---------- */
        .ie-page .ie-tabs {
            border-bottom: 1px solid var(--ie-border);
            flex-wrap: wrap;
        }

        .ie-page .ie-tabs .nav-link {
            border: 0;
            border-bottom: 2px solid transparent;
            border-radius: 8px 8px 0 0;
            padding: .75rem 1.2rem;
            font-weight: 500;
            font-size: 14px;
            color: #67757c;
            background: transparent;
        }

        .ie-page .ie-tabs .nav-link i {
            margin-right: 7px;
        }

        .ie-page .ie-tabs .nav-link:hover {
            color: var(--ie-primary);
            background: #f8f8fe;
        }

        .ie-page .ie-tabs .nav-link.active {
            color: var(--ie-primary);
            background: transparent;
            border-color: transparent transparent var(--ie-primary);
        }

        /* ---------- step panels ---------- */
        .ie-step {
            border: 1px solid var(--ie-border);
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 1px 3px rgba(16, 24, 40, .04);
            margin-bottom: 24px;
        }

        .ie-step-head {
            display: flex;
            gap: 14px;
            padding: 20px 22px 0;
        }

        .ie-step-num {
            flex: 0 0 32px;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--ie-primary);
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .ie-step-title {
            margin: 4px 0 5px;
            font-size: 16px;
            font-weight: 600;
            color: var(--ie-heading);
        }

        .ie-step-sub {
            margin: 0;
            font-size: 13px;
            line-height: 1.65;
            color: var(--ie-muted);
        }

        .ie-step-body {
            padding: 18px 22px 22px;
        }

        /* ---------- collapsible detail ---------- */
        .ie-more {
            margin-top: 10px;
            font-size: 13px;
        }

        .ie-more > summary {
            cursor: pointer;
            color: var(--ie-primary);
            font-weight: 500;
            outline: none;
            list-style: none;
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }

        .ie-more > summary::-webkit-details-marker {
            display: none;
        }

        .ie-more[open] > summary .fa-chevron-down {
            transform: rotate(180deg);
        }

        .ie-more > summary .fa-chevron-down {
            font-size: 10px;
            transition: transform .18s ease;
        }

        .ie-more-body {
            margin: 10px 0 0;
            padding: 12px 14px;
            border-left: 3px solid var(--ie-primary-soft);
            background: #fafbfe;
            border-radius: 0 8px 8px 0;
            color: #67757c;
            line-height: 1.7;
        }

        /* ---------- category cards ---------- */
        .ie-cat {
            position: relative;
            height: 100%;
            display: flex;
            flex-direction: column;
            border: 1px solid var(--ie-border);
            border-radius: 12px;
            background: #fff;
            padding: 20px 14px 14px;
            text-align: center;
            cursor: pointer;
            transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease, background .18s ease;
        }

        .ie-cat:hover {
            border-color: #c6bcf8;
            box-shadow: 0 8px 20px rgba(116, 96, 238, .13);
            transform: translateY(-2px);
        }

        .ie-cat:focus {
            outline: none;
            border-color: var(--ie-primary);
            box-shadow: 0 0 0 3px rgba(116, 96, 238, .18);
        }

        .ie-cat.is-selected {
            border-color: var(--ie-primary);
            background: #faf9ff;
            box-shadow: 0 0 0 3px rgba(116, 96, 238, .16);
        }

        .ie-cat-icon {
            width: 54px;
            height: 54px;
            margin: 0 auto 12px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 21px;
            background: var(--ie-primary-soft);
            color: var(--ie-primary);
            transition: background .18s ease, color .18s ease;
        }

        .ie-cat.is-selected .ie-cat-icon {
            background: var(--ie-primary);
            color: #fff;
        }

        .ie-cat-name {
            flex: 1 1 auto;
            display: block;
            font-size: 13.5px;
            font-weight: 600;
            line-height: 1.45;
            color: var(--ie-heading);
            word-break: break-word;
        }

        .ie-cat-check {
            position: absolute;
            top: 10px;
            right: 11px;
            font-size: 16px;
            color: var(--ie-primary);
            opacity: 0;
            transform: scale(.6);
            transition: opacity .18s ease, transform .18s ease;
        }

        .ie-cat.is-selected .ie-cat-check {
            opacity: 1;
            transform: scale(1);
        }

        .ie-cat-actions {
            display: grid;
            gap: 8px;
            margin-top: 16px;
        }

        .ie-cat-actions .btn {
            font-size: 12.5px;
            font-weight: 500;
            padding: .5rem .4rem;
            border-radius: 8px;
            box-shadow: none;
            white-space: nowrap;
        }

        .ie-cat-actions .btn i {
            margin-right: 6px;
        }

        .ie-grid-hint {
            margin: 4px 0 0;
            font-size: 12.5px;
            color: var(--ie-muted);
        }

        /* ---------- chosen category banner ---------- */
        .ie-chosen {
            display: none;
            align-items: flex-start;
            gap: 12px;
            padding: 13px 16px;
            margin-bottom: 20px;
            border: 1px solid #cfc7f7;
            border-radius: 10px;
            background: #f7f5ff;
            font-size: 13px;
            color: #4a3fa8;
            line-height: 1.6;
        }

        .ie-chosen.is-visible {
            display: flex;
        }

        .ie-chosen > i {
            font-size: 15px;
            margin-top: 2px;
            color: var(--ie-primary);
        }

        /* ---------- form fields ---------- */
        .ie-field {
            margin-bottom: 22px;
        }

        .ie-label {
            display: block;
            margin-bottom: 8px;
            font-size: 13.5px;
            font-weight: 600;
            color: var(--ie-heading);
        }

        .ie-label .ie-optional {
            font-weight: 400;
            color: var(--ie-muted);
        }

        .ie-page .form-control {
            border-radius: 8px;
            border-color: #dfe3ee;
            font-size: 13.5px;
            color: #4f5d73;
        }

        .ie-page .form-control:focus {
            border-color: var(--ie-primary);
            box-shadow: 0 0 0 3px rgba(116, 96, 238, .12);
        }

        .ie-file {
            width: 100%;
            padding: 11px 12px;
            font-size: 13px;
            color: #67757c;
            background: #fbfcfe;
            border: 1px dashed #cfd6e4;
            border-radius: 10px;
            cursor: pointer;
        }

        .ie-file:hover {
            border-color: var(--ie-primary);
            background: #faf9ff;
        }

        .ie-file:focus {
            outline: none;
            border-color: var(--ie-primary);
            box-shadow: 0 0 0 3px rgba(116, 96, 238, .12);
        }

        .ie-file::file-selector-button {
            margin-right: 13px;
            padding: .45rem 1rem;
            font-size: 12.5px;
            font-weight: 500;
            color: #fff;
            background: var(--ie-primary);
            border: 0;
            border-radius: 8px;
            cursor: pointer;
        }

        .ie-file::-webkit-file-upload-button {
            margin-right: 13px;
            padding: .45rem 1rem;
            font-size: 12.5px;
            font-weight: 500;
            color: #fff;
            background: var(--ie-primary);
            border: 0;
            border-radius: 8px;
            cursor: pointer;
        }

        .ie-file-name {
            display: none;
            align-items: center;
            gap: 8px;
            margin-top: 9px;
            font-size: 12.5px;
            font-weight: 500;
            color: #1c9c6b;
        }

        .ie-file-name.is-visible {
            display: flex;
        }

        .ie-hint {
            display: block;
            margin-top: 9px;
            font-size: 12.5px;
            line-height: 1.65;
            color: var(--ie-muted);
        }

        .ie-note {
            margin-top: 10px;
            padding: 11px 14px;
            border: 1px solid #ffe1b0;
            border-radius: 9px;
            background: #fffaf1;
            font-size: 12.5px;
            line-height: 1.65;
            color: #8a6116;
        }

        .ie-page code {
            padding: 1px 5px;
            font-size: 92%;
            background: #f2f3f8;
            border-radius: 4px;
            color: #5a6a85;
        }

        /* ---------- mode picker ----------
           A flex row rather than Bootstrap's custom-control: that puts the
           description inside an inline <label>, and a block of text inside an
           inline box escaped the card and overlapped the option below it. */
        .ie-mode {
            position: relative;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            width: 100%;
            padding: 14px 16px;
            margin-bottom: 10px;
            border: 1px solid var(--ie-border);
            border-radius: 10px;
            cursor: pointer;
            transition: border-color .15s ease, background .15s ease, box-shadow .15s ease;
        }

        .ie-mode:hover {
            border-color: #c6bcf8;
        }

        .ie-mode.is-active,
        .ie-mode:has(.ie-mode-input:checked) {
            border-color: var(--ie-primary);
            background: #faf9ff;
            box-shadow: 0 0 0 3px rgba(116, 96, 238, .12);
        }

        /* The real control: kept in the page for the keyboard and the form, with
           the visible circle drawn next to it so every browser agrees on how a
           chosen option looks. */
        .ie-mode-input {
            position: absolute;
            width: 1px;
            height: 1px;
            opacity: 0;
            margin: 0;
            pointer-events: none;
        }

        .ie-mode-dot {
            position: relative;
            flex: 0 0 auto;
            width: 18px;
            height: 18px;
            margin-top: 1px;
            border: 2px solid #cfd6e4;
            border-radius: 50%;
            background: #fff;
            transition: border-color .15s ease, box-shadow .15s ease;
        }

        .ie-mode:hover .ie-mode-dot {
            border-color: var(--ie-primary);
        }

        .ie-mode-input:checked ~ .ie-mode-dot {
            border-color: var(--ie-primary);
        }

        .ie-mode-input:checked ~ .ie-mode-dot::after {
            content: "";
            position: absolute;
            top: 3px;
            right: 3px;
            bottom: 3px;
            left: 3px;
            border-radius: 50%;
            background: var(--ie-primary);
        }

        .ie-mode-input:focus ~ .ie-mode-dot {
            box-shadow: 0 0 0 3px rgba(116, 96, 238, .25);
        }

        /* Says which one is chosen in words, not only by colour. */
        .ie-mode-flag {
            display: none;
            margin-left: 8px;
            padding: 1px 8px;
            border-radius: 20px;
            font-size: 10.5px;
            font-weight: 600;
            letter-spacing: .04em;
            text-transform: uppercase;
            background: var(--ie-primary);
            color: #fff;
            vertical-align: 2px;
        }

        .ie-mode-input:checked ~ .ie-mode-text .ie-mode-flag {
            display: inline-block;
        }

        .ie-mode-text {
            flex: 1 1 auto;
            min-width: 0;
        }

        .ie-mode-title {
            display: block;
            font-size: 13.5px;
            font-weight: 600;
            color: var(--ie-heading);
        }

        .ie-mode-sub {
            display: block;
            margin-top: 4px;
            font-size: 12.5px;
            font-weight: 400;
            line-height: 1.65;
            color: var(--ie-muted);
        }

        /* ---------- buttons ---------- */
        .ie-page .btn {
            border-radius: 8px;
            font-weight: 500;
        }

        .ie-submit {
            padding: .7rem 1.6rem;
            font-size: 14px;
            font-weight: 600;
        }

        .ie-submit i {
            margin-right: 8px;
        }

        .ie-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }

        /* ---------- reference tables ---------- */
        .ie-card {
            height: 100%;
            border: 1px solid var(--ie-border);
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 1px 3px rgba(16, 24, 40, .04);
        }

        .ie-card-head {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 16px 20px;
            border-bottom: 1px solid var(--ie-border);
        }

        .ie-card-head h5 {
            margin: 0;
            font-size: 15px;
            font-weight: 600;
            color: var(--ie-heading);
        }

        .ie-count {
            padding: 2px 9px;
            border-radius: 20px;
            font-size: 11.5px;
            font-weight: 600;
            background: #f2f3f8;
            color: #6c7a91;
        }

        .ie-count.ie-count-req {
            background: #fdeeee;
            color: #d9534f;
        }

        .ie-scroll {
            max-height: 420px;
            overflow: auto;
        }

        .ie-page .ie-scroll .table {
            margin: 0;
            font-size: 13px;
        }

        .ie-page .ie-scroll .table thead th {
            position: sticky;
            top: 0;
            z-index: 2;
            padding: 10px 14px;
            background: #f7f8fc;
            border-top: 0;
            border-bottom: 1px solid var(--ie-border);
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: var(--ie-muted);
            white-space: nowrap;
        }

        .ie-page .ie-scroll .table td {
            padding: 9px 14px;
            vertical-align: top;
            border-color: #f0f2f7;
            color: #67757c;
        }

        .ie-page .ie-scroll .table td b {
            color: var(--ie-heading);
        }

        /* ---------- checklist ---------- */
        .ie-checklist {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(min(260px, 100%), 1fr));
            gap: 10px 22px;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .ie-checklist li {
            display: flex;
            gap: 9px;
            font-size: 13px;
            line-height: 1.6;
            color: #67757c;
        }

        .ie-checklist li i {
            margin-top: 3px;
            font-size: 12px;
            color: #1c9c6b;
        }

        /* ---------- export ---------- */
        .ie-filters {
            padding: 6px 0 0;
        }

        .ie-subhead {
            display: flex;
            align-items: center;
            gap: 9px;
            margin-bottom: 16px;
            font-size: 14px;
            font-weight: 600;
            color: var(--ie-heading);
        }

        .ie-subhead i {
            color: var(--ie-primary);
        }

        .ie-page .ie-records .table thead th {
            padding: 10px 12px;
            background: #f7f8fc;
            border-top: 0;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: var(--ie-muted);
            white-space: nowrap;
        }

        .ie-page .ie-records .table td {
            padding: 9px 12px;
            font-size: 13px;
            color: #67757c;
            vertical-align: middle;
        }

        .ie-divider {
            height: 1px;
            margin: 26px 0 18px;
            background: var(--ie-border);
            border: 0;
        }

        /* ---------- record picker pager ---------- */
        /* ---------- record picker : tick box ----------
           The native box is kept for behaviour but drawn by us, so a selected
           row carries the same green tick the pager uses. */
        .ie-pick {
            position: relative;
            display: inline-flex;
            align-items: center;
            margin: 0;
            cursor: pointer;
        }

        .ie-pick input {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }

        .ie-pick-box {
            display: inline-block;
            position: relative;
            width: 18px;
            height: 18px;
            border: 1.5px solid #c3cad4;
            border-radius: 5px;
            background: #fff;
            transition: background .15s, border-color .15s;
        }

        .ie-pick:hover .ie-pick-box {
            border-color: #1aa053;
        }

        .ie-pick input:checked+.ie-pick-box {
            background: #1aa053;
            border-color: #1aa053;
        }

        .ie-pick input:checked+.ie-pick-box::after {
            content: "\2713";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            line-height: 16px;
            text-align: center;
        }

        .ie-pick input:focus-visible+.ie-pick-box {
            box-shadow: 0 0 0 3px rgba(26, 160, 83, .25);
        }

        /* Beats the theme's row-hover colour, so a picked row stays green
           while the pointer is over it. */
        .ie-records tbody tr.is-picked-row,
        .ie-records tbody tr.is-picked-row:hover {
            background: #eefaf2 !important;
        }

        .ie-records tbody tr.is-picked-row td:first-child {
            box-shadow: inset 3px 0 0 #1aa053;
        }

        .ie-pager {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 16px;
            padding-top: 14px;
            border-top: 1px solid var(--ie-border);
        }

        .ie-pager-info {
            flex: 1 1 220px;
        }

        .ie-pager-size {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12.5px;
            color: var(--ie-muted);
        }

        .ie-pager-size select {
            width: auto;
            min-width: 74px;
            border-radius: 8px;
            font-size: 12.5px;
        }

        .ie-pager-nav {
            display: flex;
            align-items: center;
            gap: 5px;
            flex-wrap: wrap;
        }

        .ie-pager-nav .btn {
            min-width: 34px;
            padding: .3rem .5rem;
            font-size: 12.5px;
        }

        .ie-pager-pages {
            display: flex;
            align-items: center;
            gap: 5px;
            flex-wrap: wrap;
        }

        .ie-pager-pages .btn.is-current {
            background: var(--ie-primary);
            border-color: var(--ie-primary);
            color: #fff;
            font-weight: 600;
        }

        /* A page whose rows are selected carries a tick, so the pager itself
           shows where the current selection lives. */
        .ie-pager-pages .btn-page {
            position: relative;
            overflow: visible;
        }

        .ie-pager-pages .btn-page.is-picked,
        .ie-pager-pages .btn-page.is-partial {
            border-color: #1aa053;
            color: #14803f;
            font-weight: 600;
        }

        .ie-pager-pages .btn-page.is-picked {
            background: #e8f7ee;
        }

        .ie-pager-pages .btn-page.is-picked.is-current,
        .ie-pager-pages .btn-page.is-partial.is-current {
            background: var(--ie-primary);
            border-color: #1aa053;
            color: #fff;
        }

        .ie-pager-pages .btn-page.is-picked::after,
        .ie-pager-pages .btn-page.is-partial::after {
            content: "\2713";
            position: absolute;
            top: -6px;
            right: -6px;
            width: 15px;
            height: 15px;
            line-height: 15px;
            border-radius: 50%;
            background: #1aa053;
            color: #fff;
            font-size: 9px;
            font-weight: 700;
            text-align: center;
            box-shadow: 0 0 0 2px #fff;
        }

        .ie-pager-pages .btn-page.is-partial::after {
            background: #f0a020;
        }

        .ie-pager-legend {
            display: none;
            align-items: center;
            gap: 6px;
            margin-top: 6px;
            font-size: 11.5px;
            color: var(--ie-muted);
        }

        .ie-pager-legend.is-visible {
            display: flex;
        }

        .ie-pager-legend .ie-legend-tick {
            display: inline-block;
            width: 14px;
            height: 14px;
            line-height: 14px;
            border-radius: 50%;
            background: #1aa053;
            color: #fff;
            font-size: 9px;
            font-weight: 700;
            text-align: center;
        }

        .ie-pager-legend .ie-legend-tick.is-partial {
            background: #f0a020;
        }

        .ie-pager-gap {
            padding: 0 2px;
            color: var(--ie-muted);
            font-size: 12.5px;
        }

        /* ---------- small screens ---------- */
        @media (max-width: 575.98px) {
            .ie-step-head {
                padding: 16px 16px 0;
            }

            .ie-step-body {
                padding: 14px 16px 18px;
            }

            .ie-head .btn,
            .ie-submit {
                width: 100%;
            }

            .ie-actions .btn {
                flex: 1 1 100%;
            }

            .ie-page .ie-tabs .nav-link {
                padding: .65rem .9rem;
                font-size: 13px;
            }
        }
    </style>

    <div class="row ie-page">
        <div class="col-12">
            <div class="card">
                <div class="card-body">

                    {{-- FLASH --}}
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- HEADER --}}
                    <div class="ie-head">
                        <div>
                            <h4>Bulk Data — Import &amp; Export</h4>
                            <p class="ie-head-sub">
                                Add media in bulk from a spreadsheet, or download your inventory as Excel / CSV.
                            </p>
                        </div>
                        <a href="{{ route('media.list') }}" class="btn btn-outline-secondary">
                            <i class="fa-solid fa-arrow-left mr-1"></i> Back to Media List
                        </a>
                    </div>

                    {{-- TABS --}}
                    <ul class="nav nav-tabs ie-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link {{ $activeTab === 'import' ? 'active' : '' }}" data-toggle="tab"
                                href="#tab-import" role="tab">
                                <i class="fa-solid fa-file-import"></i> Import Data
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $activeTab === 'export' ? 'active' : '' }}" data-toggle="tab"
                                href="#tab-export" role="tab">
                                <i class="fa-solid fa-file-export"></i> Export Data
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content pt-4">

                        {{-- ==========================================================
                             IMPORT TAB
                        =========================================================== --}}
                        <div class="tab-pane fade {{ $activeTab === 'import' ? 'show active' : '' }}" id="tab-import"
                            role="tabpanel">

                            {{-- STEP 1 : PICK A CATEGORY & DOWNLOAD ITS TEMPLATE --}}
                            <div class="ie-step">
                                <div class="ie-step-head">
                                    <div class="ie-step-num">1</div>
                                    <div>
                                        <h5 class="ie-step-title">Choose a media category</h5>
                                        <p class="ie-step-sub">
                                            Select what you are uploading, then download that category's ready-made
                                            template. It already has the right columns, filled-in examples and a
                                            <b>Master Reference</b> sheet, so you only need to add your own rows.
                                        </p>
                                        <details class="ie-more">
                                            <summary>
                                                What's inside the template?
                                                <i class="fa-solid fa-chevron-down"></i>
                                            </summary>
                                            <p class="ie-more-body">
                                                Four sheets: the exact <b>header row</b> the importer expects, two
                                                <b>example rows</b> filled for your category, a
                                                <b>column-by-column instruction</b> sheet, and a
                                                <b>Master Reference</b> sheet listing every valid State, District, City,
                                                Area, Vendor, Category, Illumination, Area Type, Highway and Landmark
                                                value in the system — copy the names from there and validation will pass
                                                the first time.
                                            </p>
                                        </details>
                                    </div>
                                </div>

                                <div class="ie-step-body">
                                    <div class="row">
                                        @forelse ($options['categories'] as $category)
                                            <div class="col-12 col-sm-6 col-md-4 col-xl-3 mb-3">
                                                <div class="ie-cat" role="button" tabindex="0"
                                                    data-category-id="{{ $category->id }}"
                                                    data-category-name="{{ $category->category_name }}"
                                                    aria-pressed="false">
                                                    <i class="fa-solid fa-circle-check ie-cat-check"></i>

                                                    <div class="ie-cat-icon">
                                                        <i class="fa-solid {{ $categoryIcon($category->category_name) }}"></i>
                                                    </div>

                                                    <span class="ie-cat-name">{{ $category->category_name }}</span>

                                                    <div class="ie-cat-actions">
                                                        <a href="{{ route('media.import.template', ['category' => $category->id]) }}"
                                                            class="btn btn-outline-primary ie-cat-template"
                                                            title="Download the Excel template for {{ $category->category_name }}">
                                                            <i class="fa-solid fa-download"></i> Download Template
                                                        </a>
                                                        <button type="button" class="btn btn-primary ie-cat-import"
                                                            title="Upload a filled file for {{ $category->category_name }}">
                                                            <i class="fa-solid fa-upload"></i> Import Data
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="col-12">
                                                <div class="alert alert-warning mb-0">
                                                    No categories found. Please add a category first.
                                                </div>
                                            </div>
                                        @endforelse
                                    </div>

                                    @if (count($options['categories']))
                                        <p class="ie-grid-hint">
                                            <i class="fa-solid fa-circle-info mr-1"></i>
                                            Tap a card to select it — tap the highlighted card again to clear the
                                            selection. Choosing a category is optional if your file already fills the
                                            <b>Category</b> column.
                                        </p>
                                    @endif
                                </div>
                            </div>

                            {{-- STEP 2 : UPLOAD THE FILLED FILE --}}
                            <div class="ie-step">
                                <div class="ie-step-head">
                                    <div class="ie-step-num">2</div>
                                    <div>
                                        <h5 class="ie-step-title">Upload your filled file</h5>
                                        <p class="ie-step-sub">
                                            Attach the completed template. Every row is checked first — you will see a
                                            preview and an error report before anything is saved.
                                        </p>
                                    </div>
                                </div>

                                <div class="ie-step-body">
                                    <form action="{{ route('media.import.preview') }}" method="POST"
                                        enctype="multipart/form-data" id="importForm">
                                        @csrf

                                        <input type="hidden" name="category_id" id="importCategoryId" value="">

                                        <div id="importCategoryBadge" class="ie-chosen">
                                            <i class="fa-solid fa-circle-check"></i>
                                            <span>
                                                Importing under <b id="importCategoryName"></b> — any row that leaves the
                                                <b>Category</b> cell blank will use this category.
                                            </span>
                                        </div>

                                        <div class="ie-field">
                                            <label class="ie-label" for="importFile">
                                                Excel / CSV file <span class="text-danger">*</span>
                                            </label>
                                            <input type="file" name="file" id="importFile" class="ie-file"
                                                accept=".xlsx,.xls,.csv" required>
                                            <span class="ie-file-name">
                                                <i class="fa-solid fa-circle-check"></i>
                                                <span class="ie-file-name-text"></span>
                                            </span>
                                            <small class="ie-hint">
                                                Accepted formats: .xlsx, .xls, .csv &nbsp;·&nbsp; up to 10 MB
                                                &nbsp;·&nbsp; up to
                                                {{ number_format(\App\Http\Services\Superadm\MediaImportExportService::MAX_ROWS) }}
                                                rows per file.
                                            </small>
                                        </div>

                                        <div class="ie-field">
                                            <label class="ie-label" for="importImagesZip">
                                                Images ZIP <span class="ie-optional">(optional)</span>
                                            </label>
                                            <input type="file" name="images_zip" id="importImagesZip" class="ie-file"
                                                accept=".zip">
                                            <span class="ie-file-name">
                                                <i class="fa-solid fa-circle-check"></i>
                                                <span class="ie-file-name-text"></span>
                                            </span>
                                            <small class="ie-hint">
                                                Put every picture your sheet names into one .zip and upload it here. In
                                                the <b>Image URLs</b> column write only the file name — for example
                                                <code>site-front.jpg, site-side.jpg</code> — and it is matched to the file
                                                of that name inside the ZIP. The <code>.jpg</code> may be left off when
                                                the name is unique. &nbsp;·&nbsp; up to
                                                {{ round(config('fileConstants.IMAGE_IMPORT_ZIP_MAX_KB') / 1024) }} MB
                                                &nbsp;·&nbsp; JPG, PNG, WebP.
                                            </small>
                                            <div class="ie-note">
                                                <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                                                A path from your own computer such as
                                                <code>C:\Users\You\Downloads\image1.jpg</code> will not work — the server
                                                cannot reach your drive. Use the ZIP, or a direct
                                                <code>https://</code> link to the image.
                                            </div>
                                        </div>

                                        <div class="ie-field">
                                            <label class="ie-label">What should happen to each row</label>

                                            <label class="ie-mode" for="modeInsert">
                                                <input type="radio" id="modeInsert" name="mode" value="insert"
                                                    class="ie-mode-input" checked>
                                                <span class="ie-mode-dot" aria-hidden="true"></span>
                                                <span class="ie-mode-text">
                                                    <span class="ie-mode-title">Add new records only<span
                                                            class="ie-mode-flag">Selected</span></span>
                                                    <span class="ie-mode-sub">
                                                        Every row becomes a new media record — use this to add several
                                                        faces at one site. A row that is identical to something already
                                                        in the inventory is reported and skipped, so nothing gets
                                                        duplicated by accident.
                                                    </span>
                                                </span>
                                            </label>

                                            <label class="ie-mode" for="modeUpsert">
                                                <input type="radio" id="modeUpsert" name="mode" value="upsert"
                                                    class="ie-mode-input">
                                                <span class="ie-mode-dot" aria-hidden="true"></span>
                                                <span class="ie-mode-text">
                                                    <span class="ie-mode-title">Add new and update existing<span
                                                            class="ie-mode-flag">Selected</span></span>
                                                    <span class="ie-mode-sub">
                                                        Use this to change media you already have — for example to
                                                        revise prices. A row is matched by its <b>Hoarding Code</b> when
                                                        the file has one, otherwise by its <b>Vendor and GPS
                                                        position</b>, and that record is updated in place. Only a
                                                        position nothing is recorded at yet becomes a new record.
                                                    </span>
                                                </span>
                                            </label>

                                            <div class="ie-note" style="border-color:#cfc7f7; background:#f7f5ff; color:#4a3fa8;">
                                                <i class="fa-solid fa-circle-info mr-1"></i>
                                                <b>To change media that is already in the system:</b> export it from the
                                                Export tab, edit the cells you need in that file, keep the
                                                <b>Hoarding Code</b> column exactly as it is, and upload it here with
                                                <b>Add new and update existing</b> selected. Blank cells and cells
                                                holding <code>-</code> are treated as empty, and any column your file
                                                does not have is left exactly as it is.
                                                <div class="mt-2">
                                                    <b>Pictures:</b> whatever the <b>Image URLs</b> cell lists becomes
                                                    that record's gallery — change a name and the old picture is
                                                    deleted and the new one saved in its place. Leave the cell empty
                                                    to keep the existing pictures untouched.
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Filled in by the size guard below; a file too large for the
                                             server never leaves the browser. --}}
                                        <div class="alert alert-danger" id="importSizeAlert" style="display:none"></div>

                                        <button type="submit" class="btn btn-primary ie-submit" id="importSubmit">
                                            <i class="fa-solid fa-magnifying-glass"></i> Validate &amp; Preview
                                        </button>
                                        <small class="ie-hint">
                                            Nothing is saved yet — the next screen shows a preview and a downloadable
                                            error log before anything is published.
                                        </small>
                                    </form>
                                </div>
                            </div>

                            {{-- FIELD REFERENCE --}}
                            <div class="row">
                                <div class="col-lg-6 mb-4">
                                    <div class="ie-card">
                                        <div class="ie-card-head">
                                            <h5>Columns you must fill</h5>
                                            <span class="ie-count ie-count-req">{{ count($requiredColumns) }}</span>
                                        </div>
                                        <div class="ie-scroll">
                                            <table class="table table-borderless">
                                                <thead>
                                                    <tr>
                                                        <th style="width:35%">Column</th>
                                                        <th>How to fill it</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($requiredColumns as $column)
                                                        <tr>
                                                            <td><b>{{ $column['label'] }}</b></td>
                                                            <td>{{ $column['help'] }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-6 mb-4">
                                    <div class="ie-card">
                                        <div class="ie-card-head">
                                            <h5>Columns you can leave blank</h5>
                                            <span class="ie-count">{{ count($optionalColumns) }}</span>
                                        </div>
                                        <div class="ie-scroll">
                                            <table class="table table-borderless">
                                                <thead>
                                                    <tr>
                                                        <th style="width:35%">Column</th>
                                                        <th>How to fill it</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($optionalColumns as $column)
                                                        <tr>
                                                            <td>{{ $column['label'] }}</td>
                                                            <td>{{ $column['help'] }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- PRE-PUBLISH CHECKS --}}
                            <div class="ie-card">
                                <div class="ie-card-head">
                                    <h5>What we check before publishing</h5>
                                </div>
                                <div class="ie-step-body">
                                    <ul class="ie-checklist">
                                        <li>
                                            <i class="fa-solid fa-check"></i>
                                            <span>Every mandatory field is present.</span>
                                        </li>
                                        <li>
                                            <i class="fa-solid fa-check"></i>
                                            <span>Master names exist and match each other — State → District → City →
                                                Area, plus Vendor, Category, Illumination, Area Type, Highway and
                                                Landmarks.</span>
                                        </li>
                                        <li>
                                            <i class="fa-solid fa-check"></i>
                                            <span>Width, Height, Price and GPS coordinates are valid numbers within
                                                range.</span>
                                        </li>
                                        <li>
                                            <i class="fa-solid fa-check"></i>
                                            <span>Category specific fields are filled — Mall Name, Airport Zone, Transit
                                                details and so on.</span>
                                        </li>
                                        <li>
                                            <i class="fa-solid fa-check"></i>
                                            <span>Hoarding Code and Media Code are not repeated in the file and not
                                                already taken.</span>
                                        </li>
                                        <li>
                                            <i class="fa-solid fa-check"></i>
                                            <span>Rows sharing a Vendor and GPS position with an existing record are
                                                flagged for a quick look, but still imported — one site can carry
                                                several media faces.</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        {{-- ==========================================================
                             EXPORT TAB
                        =========================================================== --}}
                        <div class="tab-pane fade {{ $activeTab === 'export' ? 'show active' : '' }}" id="tab-export"
                            role="tabpanel">

                            {{-- POST, not GET: a large "export selected" list would overflow the URL --}}
                            <form action="{{ route('media.export') }}" method="POST" id="exportForm">
                                @csrf
                                <input type="hidden" name="ids" id="selectedIds">

                                <div class="ie-filters">
                                    <div class="ie-subhead">
                                        <i class="fa-solid fa-filter"></i> Narrow down what you export
                                    </div>

                                    <div class="row">
                                        <div class="col-12 col-sm-6 col-lg-4 col-xl-3 ie-field">
                                            <label class="ie-label">State</label>
                                            <select name="state_id" id="f_state" class="form-control">
                                                <option value="">All States</option>
                                                @foreach ($options['states'] as $state)
                                                    <option value="{{ $state->id }}"
                                                        {{ ($filters['state_id'] ?? '') == $state->id ? 'selected' : '' }}>
                                                        {{ $state->state_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-12 col-sm-6 col-lg-4 col-xl-3 ie-field">
                                            <label class="ie-label">District</label>
                                            <select name="district_id" id="f_district" class="form-control">
                                                <option value="">All Districts</option>
                                            </select>
                                        </div>

                                        <div class="col-12 col-sm-6 col-lg-4 col-xl-3 ie-field">
                                            <label class="ie-label">City / Town</label>
                                            <select name="city_id" id="f_city" class="form-control">
                                                <option value="">All Cities</option>
                                            </select>
                                        </div>

                                        <div class="col-12 col-sm-6 col-lg-4 col-xl-3 ie-field">
                                            <label class="ie-label">Category (Media Type)</label>
                                            <select name="category_id" class="form-control">
                                                <option value="">All Categories</option>
                                                @foreach ($options['categories'] as $category)
                                                    <option value="{{ $category->id }}"
                                                        {{ ($filters['category_id'] ?? '') == $category->id ? 'selected' : '' }}>
                                                        {{ $category->category_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-12 col-sm-6 col-lg-4 col-xl-3 ie-field">
                                            <label class="ie-label">Vendor / Owner</label>
                                            <select name="vendor_id" class="form-control">
                                                <option value="">All Vendors</option>
                                                @foreach ($options['vendors'] as $vendor)
                                                    <option value="{{ $vendor->id }}"
                                                        {{ ($filters['vendor_id'] ?? '') == $vendor->id ? 'selected' : '' }}>
                                                        {{ $vendor->vendor_name }} ({{ $vendor->vendor_code }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-12 col-sm-6 col-lg-4 col-xl-3 ie-field">
                                            <label class="ie-label">Illumination</label>
                                            <select name="illumination_id" class="form-control">
                                                <option value="">All</option>
                                                @foreach ($options['illuminations'] as $illumination)
                                                    <option value="{{ $illumination->id }}"
                                                        {{ ($filters['illumination_id'] ?? '') == $illumination->id ? 'selected' : '' }}>
                                                        {{ $illumination->illumination_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-12 col-sm-6 col-lg-4 col-xl-3 ie-field">
                                            <label class="ie-label">Area Type</label>
                                            <select name="areatype_id" class="form-control">
                                                <option value="">All</option>
                                                @foreach ($options['areatypes'] as $areatype)
                                                    <option value="{{ $areatype->id }}"
                                                        {{ ($filters['areatype_id'] ?? '') == $areatype->id ? 'selected' : '' }}>
                                                        {{ $areatype->areatype_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-12 col-sm-6 col-lg-4 col-xl-3 ie-field">
                                            <label class="ie-label">Highway</label>
                                            <select name="highway_id" class="form-control">
                                                <option value="">All</option>
                                                @foreach ($options['highways'] as $highway)
                                                    <option value="{{ $highway->id }}"
                                                        {{ ($filters['highway_id'] ?? '') == $highway->id ? 'selected' : '' }}>
                                                        {{ $highway->highway_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-12 col-sm-6 col-lg-4 col-xl-3 ie-field">
                                            <label class="ie-label">Status</label>
                                            <select name="status" class="form-control">
                                                <option value="">All</option>
                                                <option value="1"
                                                    {{ ($filters['status'] ?? '') === '1' ? 'selected' : '' }}>Active
                                                </option>
                                                <option value="0"
                                                    {{ ($filters['status'] ?? '') === '0' ? 'selected' : '' }}>Inactive
                                                </option>
                                            </select>
                                        </div>

                                        <div class="col-12 col-sm-6 col-lg-4 col-xl-3 ie-field">
                                            <label class="ie-label">Media Type</label>
                                            <input type="text" name="media_type" class="form-control"
                                                placeholder="e.g. Unipole" value="{{ $filters['media_type'] ?? '' }}">
                                        </div>

                                        <div class="col-12 col-sm-6 col-lg-4 col-xl-3 ie-field">
                                            <label class="ie-label">Search (Code / Title)</label>
                                            <input type="text" name="hoarding_code" class="form-control"
                                                placeholder="e.g. HD000007" value="{{ $filters['hoarding_code'] ?? '' }}">
                                        </div>

                                        <div class="col-12 col-sm-6 col-lg-4 col-xl-3 ie-field">
                                            <label class="ie-label">File Format</label>
                                            <select name="format" class="form-control">
                                                <option value="xlsx">Excel (.xlsx)</option>
                                                <option value="csv">CSV (.csv)</option>
                                            </select>
                                        </div>

                                        <div class="col-6 col-sm-6 col-lg-3 col-xl-3 ie-field">
                                            <label class="ie-label">Price From</label>
                                            <input type="number" name="min_price" class="form-control" min="0"
                                                step="any" value="{{ $filters['min_price'] ?? '' }}">
                                        </div>

                                        <div class="col-6 col-sm-6 col-lg-3 col-xl-3 ie-field">
                                            <label class="ie-label">Price To</label>
                                            <input type="number" name="max_price" class="form-control" min="0"
                                                step="any" value="{{ $filters['max_price'] ?? '' }}">
                                        </div>

                                        <div class="col-6 col-sm-6 col-lg-3 col-xl-3 ie-field">
                                            <label class="ie-label">Created From</label>
                                            <input type="date" name="from_date" class="form-control"
                                                value="{{ $filters['from_date'] ?? '' }}">
                                        </div>

                                        <div class="col-6 col-sm-6 col-lg-3 col-xl-3 ie-field">
                                            <label class="ie-label">Created To</label>
                                            <input type="date" name="to_date" class="form-control"
                                                value="{{ $filters['to_date'] ?? '' }}">
                                        </div>
                                    </div>
                                </div>

                                <div class="ie-actions">
                                    <button type="submit" class="btn btn-primary ie-submit" id="btnExportAll">
                                        <i class="fa-solid fa-download"></i> Export Matching Records
                                    </button>
                                    <button type="button" class="btn btn-outline-primary" id="btnLoadRecords">
                                        <i class="fa-solid fa-list-check mr-1"></i> Load Records To Select
                                    </button>
                                    <button type="button" class="btn btn-outline-primary" id="btnExportSelected" disabled>
                                        <i class="fa-solid fa-file-arrow-down mr-1"></i>
                                        Export Selected (<span id="selectedCount">0</span>)
                                    </button>
                                    <a href="{{ route('media.import-export', ['tab' => 'export']) }}"
                                        class="btn btn-outline-secondary">
                                        <i class="fa-solid fa-rotate-left mr-1"></i> Reset Filters
                                    </a>
                                </div>

                                <small class="ie-hint">
                                    With no filters applied, <b>Export Matching Records</b> exports the complete media
                                    database. Every export includes location, commercial, GPS and media specification
                                    details.
                                </small>
                            </form>

                            {{-- RECORD PICKER --}}
                            <div id="recordPicker" class="ie-records" style="display:none;">
                                <hr class="ie-divider">

                                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3"
                                    style="gap:10px;">
                                    <div class="ie-subhead mb-0">
                                        <i class="fa-solid fa-table-list"></i>
                                        Matching Records (<span id="recordTotal">0</span>)
                                    </div>
                                    <div class="ie-actions">
                                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnSelectPage">
                                            Select all on this page
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                            id="btnClearSelection">
                                            Clear selection
                                        </button>
                                    </div>
                                </div>

                                <div class="table-responsive" style="max-height:460px; overflow:auto;">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th style="width:44px;">
                                                    <label class="ie-pick" title="Select every row on this page">
                                                        <input type="checkbox" id="checkAll">
                                                        <span class="ie-pick-box"></span>
                                                    </label>
                                                </th>
                                                <th style="width:70px;">Sr No.</th>
                                                <th>Hoarding Code</th>
                                                <th>Media Title</th>
                                                <th>Category</th>
                                                <th>City</th>
                                                <th>Area</th>
                                                <th>Vendor</th>
                                                <th>Price</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="recordBody"></tbody>
                                    </table>
                                </div>

                                <div class="ie-pager">
                                    <div class="ie-pager-info">
                                        <small class="text-muted" id="recordPageInfo"></small>
                                        <div class="ie-pager-legend" id="recordPagerLegend">
                                            <span class="ie-legend-tick">&#10003;</span> all rows selected
                                            <span class="ie-legend-tick is-partial">&#10003;</span> some rows selected
                                        </div>
                                    </div>

                                    <div class="ie-pager-size">
                                        <label class="mb-0" for="recordPerPage">Rows per page</label>
                                        <select id="recordPerPage" class="form-control form-control-sm">
                                            <option value="25">25</option>
                                            <option value="50" selected>50</option>
                                            <option value="100">100</option>
                                            <option value="200">200</option>
                                        </select>
                                    </div>

                                    <nav class="ie-pager-nav" aria-label="Matching records pages">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnFirstPage"
                                            title="First page">
                                            <i class="fa-solid fa-angles-left"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnPrevPage">
                                            <i class="fa-solid fa-chevron-left"></i>
                                        </button>
                                        <span id="recordPages" class="ie-pager-pages"></span>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnNextPage">
                                            <i class="fa-solid fa-chevron-right"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnLastPage"
                                            title="Last page">
                                            <i class="fa-solid fa-angles-right"></i>
                                        </button>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(function () {

            /* ============ CASCADING LOCATION FILTERS ============ */
            const DISTRICTS = @json($options['districts']);
            const CITIES = @json($options['cities']);

            const preset = {
                district: "{{ $filters['district_id'] ?? '' }}",
                city: "{{ $filters['city_id'] ?? '' }}"
            };

            function fillDistricts(stateId, selected) {
                let html = '<option value="">All Districts</option>';
                DISTRICTS.filter(d => !stateId || String(d.state_id) === String(stateId))
                    .forEach(d => {
                        html += `<option value="${d.id}" ${String(d.id) === String(selected) ? 'selected' : ''}>${d.district_name}</option>`;
                    });
                $('#f_district').html(html);
            }

            function fillCities(districtId, selected) {
                let html = '<option value="">All Cities</option>';
                CITIES.filter(c => !districtId || String(c.district_id) === String(districtId))
                    .forEach(c => {
                        html += `<option value="${c.id}" ${String(c.id) === String(selected) ? 'selected' : ''}>${c.city_name}</option>`;
                    });
                $('#f_city').html(html);
            }

            fillDistricts($('#f_state').val(), preset.district);
            fillCities($('#f_district').val(), preset.city);

            $('#f_state').on('change', function () {
                fillDistricts($(this).val(), '');
                fillCities('', '');
                resetPicker();
            });

            $('#f_district').on('change', function () {
                fillCities($(this).val(), '');
                resetPicker();
            });

            $('#exportForm').on('change', 'select, input', function () {
                if (this.id !== 'selectedIds') resetPicker();
            });

            /* ============ IMPORT : CATEGORY SELECTION ============
               The chosen category is shown by highlighting its card, so there is no
               separate "change" control — picking another card switches, and picking
               the highlighted card again clears the choice. */
            function selectCategory(card, scrollToForm) {
                $('.ie-cat').removeClass('is-selected').attr('aria-pressed', 'false');

                if (!card) {
                    $('#importCategoryId').val('');
                    $('#importCategoryBadge').removeClass('is-visible');
                    return;
                }

                card.addClass('is-selected').attr('aria-pressed', 'true');
                $('#importCategoryId').val(card.data('category-id'));
                $('#importCategoryName').text(card.data('category-name'));
                $('#importCategoryBadge').addClass('is-visible');

                if (scrollToForm) {
                    const target = $('#importForm');
                    if (target.length) {
                        $('html, body').animate({ scrollTop: target.offset().top - 90 }, 300);
                    }
                    $('#importFile').focus();
                }
            }

            $(document).on('click', '.ie-cat', function (e) {
                // The template link is a plain download — never treat it as a selection.
                if ($(e.target).closest('.ie-cat-template').length) return;

                const card = $(this);
                const viaImportButton = $(e.target).closest('.ie-cat-import').length > 0;

                if (card.hasClass('is-selected') && !viaImportButton) {
                    selectCategory(null);
                    return;
                }

                selectCategory(card, true);
            });

            $(document).on('keydown', '.ie-cat', function (e) {
                if (e.key === 'Enter' || e.key === ' ' || e.key === 'Spacebar') {
                    e.preventDefault();
                    $(this).trigger('click');
                }
            });

            /* ============ IMPORT : CHOSEN FILE FEEDBACK ============ */
            $('#importFile, #importImagesZip').on('change', function () {
                const box = $(this).closest('.ie-field').find('.ie-file-name');
                const file = this.files && this.files[0];

                if (!file) {
                    box.removeClass('is-visible').find('.ie-file-name-text').text('');
                    return;
                }

                box.addClass('is-visible')
                    .find('.ie-file-name-text')
                    .text(file.name + ' — ' + (file.size / 1048576).toFixed(2) + ' MB');
            });

            /* ============ IMPORT : DUPLICATE-HANDLING MODE ============
               Each option is a <label>, so clicking anywhere in the card selects
               it without help; this only moves the highlight. */
            $('input[name="mode"]').on('change', function () {
                $('.ie-mode').removeClass('is-active');
                $(this).closest('.ie-mode').addClass('is-active');
            }).filter(':checked').trigger('change');

            /* ============ IMPORT : SIZE GUARD ============
               The sheet and the ZIP travel to the server in one POST, so an
               oversized file is not rejected until the whole thing has been
               uploaded — minutes of waiting on a slow connection before an
               error appears, and if the upload outruns the server's
               max_input_time the request is cut off mid-transfer and the
               browser shows only a connection reset, with no clue as to why.
               Checking the size the moment the file is chosen turns all of
               that into an immediate, readable message. These limits mirror
               the server's own rules in MediaImportExportController::preview()
               and must be kept in step with them. */
            const MAX_SHEET_KB = 10240; // 'file' => max:10240
            const MAX_ZIP_KB = {{ (int) config('fileConstants.IMAGE_IMPORT_ZIP_MAX_KB') }};

            function overSizeMessage(input, maxKb, label) {
                const file = input.files && input.files[0];

                if (!file || file.size / 1024 <= maxKb) {
                    return null;
                }

                return label + ' is ' + (file.size / 1048576).toFixed(1) + ' MB, over the '
                    + Math.round(maxKb / 1024) + ' MB limit. Please split it into smaller '
                    + 'batches and import them one after another.';
            }

            function checkImportSizes() {
                const problems = [
                    overSizeMessage(document.getElementById('importFile'), MAX_SHEET_KB, 'The Excel / CSV file'),
                    overSizeMessage(document.getElementById('importImagesZip'), MAX_ZIP_KB, 'The images ZIP'),
                ].filter(Boolean);

                $('#importSizeAlert').html(problems.join('<br>')).toggle(problems.length > 0);

                return problems.length === 0;
            }

            $('#importFile, #importImagesZip').on('change', checkImportSizes);

            /* ============ IMPORT : GUARD AGAINST DOUBLE SUBMIT ============ */
            $('#importForm').on('submit', function (e) {
                // Nothing is worth uploading if the server is going to refuse it.
                if (!checkImportSizes()) {
                    e.preventDefault();
                    $('#importSizeAlert')[0].scrollIntoView({ behavior: 'smooth', block: 'center' });

                    return;
                }

                $('#importSubmit')
                    .prop('disabled', true)
                    .html('<i class="fa-solid fa-spinner fa-spin"></i> Validating…');
            });

            /* ============ EXPORT : RECORD PICKER ============
               Every page is fetched from the server — only the rows of the
               current page are ever sent to the browser. */
            const COLSPAN = 10;
            const selected = new Set();
            // Ids seen on each visited page, so the pager can show which pages
            // the current selection covers. Cleared whenever the page contents
            // could change (new filters, different page size).
            const pageIds = new Map();
            let currentPage = 1;
            let lastPage = 1;

            function resetPicker() {
                selected.clear();
                pageIds.clear();
                currentPage = 1;
                $('#recordPicker').hide();
                $('#recordBody').empty();
                $('#recordPages').empty();
                $('#recordPageInfo').text('');
                $('#recordPagerLegend').removeClass('is-visible');
                syncSelection();
            }

            function syncSelection() {
                $('#selectedCount').text(selected.size);
                $('#btnExportSelected').prop('disabled', selected.size === 0);
                $('#selectedIds').val(Array.from(selected).join(','));
                markPagerSelection();
            }

            /**
             * 'all' / 'partial' / 'none' for a page we have already loaded;
             * pages never visited stay unmarked because their ids are unknown.
             */
            function pageSelectionState(page) {
                const ids = pageIds.get(page);
                if (!ids || !ids.length) return 'none';

                const picked = ids.filter(id => selected.has(id)).length;
                if (picked === 0) return 'none';
                return picked === ids.length ? 'all' : 'partial';
            }

            /**
             * Re-stamps the tick on the page buttons without rebuilding them,
             * so ticking a row updates the pager straight away.
             */
            function markPagerSelection() {
                let marked = 0;

                $('#recordPages .btn-page').each(function () {
                    const state = pageSelectionState(Number($(this).data('page')));

                    $(this)
                        .toggleClass('is-picked', state === 'all')
                        .toggleClass('is-partial', state === 'partial');

                    if (state === 'all') {
                        $(this).attr('title', 'All rows on this page are selected');
                        marked++;
                    } else if (state === 'partial') {
                        $(this).attr('title', 'Some rows on this page are selected');
                        marked++;
                    } else {
                        $(this).removeAttr('title');
                    }
                });

                $('#recordPagerLegend').toggleClass('is-visible', marked > 0);
            }

            /**
             * Page buttons around the current page: 1 … 4 5 [6] 7 8 … 24.
             */
            function renderPager(current, last) {
                const wanted = new Set([1, last, current]);
                for (let offset = 1; offset <= 2; offset++) {
                    if (current - offset >= 1) wanted.add(current - offset);
                    if (current + offset <= last) wanted.add(current + offset);
                }

                const pages = Array.from(wanted).filter(p => p >= 1 && p <= last).sort((a, b) => a - b);

                let html = '';
                let previous = 0;
                pages.forEach(page => {
                    if (previous && page - previous > 1) {
                        html += '<span class="ie-pager-gap">…</span>';
                    }
                    html += `<button type="button" class="btn btn-sm btn-outline-secondary btn-page`
                        + `${page === current ? ' is-current' : ''}" data-page="${page}">${page}</button>`;
                    previous = page;
                });

                $('#recordPages').html(html);
                markPagerSelection();

                $('#btnFirstPage, #btnPrevPage').prop('disabled', current <= 1);
                $('#btnLastPage, #btnNextPage').prop('disabled', current >= last);
            }

            function loadRecords(page) {
                const params = $('#exportForm').serializeArray()
                    .filter(f => !['ids', 'format', '_token'].includes(f.name) && f.value !== '');
                params.push({ name: 'page', value: page });
                params.push({ name: 'per_page', value: $('#recordPerPage').val() });

                $('#recordBody').html(`<tr><td colspan="${COLSPAN}" class="text-center py-4">Loading…</td></tr>`);
                $('#recordPicker').show();

                $.get("{{ route('media.export.records') }}", $.param(params), function (res) {
                    if (!res.status) {
                        $('#recordBody').html(`<tr><td colspan="${COLSPAN}" class="text-center text-danger py-4">Could not load records</td></tr>`);
                        return;
                    }

                    currentPage = res.current_page;
                    lastPage = res.last_page;

                    // Remember this page's ids first — the pager reads them to
                    // decide whether the page gets a tick.
                    pageIds.set(currentPage, res.data.map(row => String(row.id)));

                    $('#recordTotal').text(res.total);
                    renderPager(currentPage, lastPage);

                    if (!res.data.length) {
                        $('#recordPageInfo').text('No records match these filters');
                        $('#recordBody').html(`<tr><td colspan="${COLSPAN}" class="text-center py-4">No media records match these filters</td></tr>`);
                        return;
                    }

                    // Serial numbers continue across pages, so row 51 on page 2
                    // reads 51 and not 1.
                    const from = res.from || ((currentPage - 1) * (res.per_page || 50) + 1);
                    const to = from + res.data.length - 1;

                    $('#recordPageInfo').text(
                        `Showing ${from}–${to} of ${res.total} record(s) · page ${currentPage} of ${lastPage}`
                    );

                    let html = '';
                    res.data.forEach((row, index) => {
                        const isPicked = selected.has(String(row.id));
                        const checked = isPicked ? 'checked' : '';
                        html += `<tr class="${isPicked ? 'is-picked-row' : ''}">
                            <td>
                                <label class="ie-pick">
                                    <input type="checkbox" class="row-check" value="${row.id}" ${checked}>
                                    <span class="ie-pick-box"></span>
                                </label>
                            </td>
                            <td>${from + index}</td>
                            <td>${row.hoarding_code || '—'}</td>
                            <td>${row.media_title || row.category_name || '—'}</td>
                            <td>${row.category_name}</td>
                            <td>${row.city_name}</td>
                            <td>${row.area_name}</td>
                            <td>${row.vendor_name}</td>
                            <td>₹ ${Number(row.price).toLocaleString('en-IN')}</td>
                            <td>${row.status}</td>
                        </tr>`;
                    });

                    $('#recordBody').html(html);
                    // Returning to a page that is already fully selected must
                    // come back with its header box ticked too.
                    $('#checkAll').prop('checked', pageSelectionState(currentPage) === 'all');
                    markPagerSelection();
                }).fail(function () {
                    $('#recordBody').html(`<tr><td colspan="${COLSPAN}" class="text-center text-danger py-4">Could not load records</td></tr>`);
                });
            }

            $('#btnLoadRecords').on('click', () => loadRecords(1));
            $('#btnFirstPage').on('click', () => currentPage > 1 && loadRecords(1));
            $('#btnLastPage').on('click', () => currentPage < lastPage && loadRecords(lastPage));
            $('#btnPrevPage').on('click', () => currentPage > 1 && loadRecords(currentPage - 1));
            $('#btnNextPage').on('click', () => currentPage < lastPage && loadRecords(currentPage + 1));
            $(document).on('click', '.btn-page', function () {
                const page = Number($(this).data('page'));
                if (page !== currentPage) loadRecords(page);
            });

            // Changing the page size restarts from page one; the selection is
            // kept, since it is held by record id and not by row position.
            $('#recordPerPage').on('change', function () {
                // A different page size reshuffles which record sits on which
                // page, so the remembered page contents no longer apply.
                pageIds.clear();
                if ($('#recordPicker').is(':visible')) loadRecords(1);
            });

            // Keeps the green row highlight on the rows whose box is ticked.
            function paintRows() {
                $('.row-check').each(function () {
                    $(this).closest('tr').toggleClass('is-picked-row', this.checked);
                });
            }

            $(document).on('change', '.row-check', function () {
                this.checked ? selected.add(this.value) : selected.delete(this.value);
                $(this).closest('tr').toggleClass('is-picked-row', this.checked);
                $('#checkAll').prop('checked', pageSelectionState(currentPage) === 'all');
                syncSelection();
            });

            $('#checkAll, #btnSelectPage').on('click', function () {
                const check = this.id === 'btnSelectPage' ? true : $('#checkAll').is(':checked');
                $('.row-check').each(function () {
                    this.checked = check;
                    check ? selected.add(this.value) : selected.delete(this.value);
                });
                if (this.id === 'btnSelectPage') $('#checkAll').prop('checked', true);
                paintRows();
                syncSelection();
            });

            $('#btnClearSelection').on('click', function () {
                selected.clear();
                $('.row-check').prop('checked', false);
                $('#checkAll').prop('checked', false);
                paintRows();
                syncSelection();
            });

            // Selected export sends ids; the plain export must never carry them.
            $('#btnExportSelected').on('click', function () {
                syncSelection();
                $('#exportForm').submit();
            });

            $('#btnExportAll').on('click', function () {
                $('#selectedIds').val('');
            });
        });
    </script>
@endsection
