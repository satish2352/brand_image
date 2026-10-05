{{-- RoadStar audience for one hoarding — display only, shared by the website
     Media Details page and the admin media details page.

     $audience: RoadStarSyncService::forDisplay()['audience'] (the latest
     roadstar_audience_data row, read from MySQL — never a live API call).
     Only values RoadStar's POST /sitedata returns are shown; a section RoadStar
     did not send is left out, never filled in. --}}
@php
    $rsNum = fn($v) => $v === null ? '–' : (is_float($v) && floor($v) != $v ? number_format($v, 2) : number_format((float) $v));
    $rsTables = [
        'effective_frequency'         => ['Effective Frequency (OTS)', '', 'People who had the opportunity to see the site at least 1, 2, … 5 times.'],
        'weekday_weekend_impressions' => ['Weekday vs Weekend', '', 'Average impressions on weekdays (Mon–Fri) and weekends (Sat–Sun).'],
        'day_wise_avg_impressions'    => ['Average Impressions by Day', '', null],
        'month_wise_impressions'      => ['Impressions by Month', '', null],
        'age_groups'                  => ['Age Group', '%', null],
        'gender'                      => ['Gender', '%', null],
        'mobile_affluence'            => ['Mobile Affluence', '%', 'Approximate price band of the phone that provided the location data.'],
        'mobile_brands'               => ['Mobile Phone Brand', '%', null],
    ];
@endphp

<style>
    .rs-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 14px; }
    .rs-tile { border: 1px solid #E5E7EB; border-radius: 10px; padding: 14px; background: #fff; min-width: 0; }
    .rs-label { font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px; }
    .rs-value { font-size: 22px; font-weight: 700; color: #0F172A; line-height: 1.2; }
    .rs-note { font-size: 12px; color: #64748B; margin-top: 6px; }
    .rs-table { width: 100%; font-size: 13px; border-collapse: collapse; }
    .rs-table td, .rs-table th { padding: 5px 6px; border-bottom: 1px dashed #E5E7EB; }
    .rs-table td:last-child { text-align: right; font-weight: 600; }
    .rs-scroll { overflow-x: auto; }
    .rs-hours td, .rs-hours th { text-align: center; white-space: nowrap; border: 1px solid #E5E7EB; padding: 4px 6px; font-size: 12px; }
    .rs-hours th { color: #475569; font-weight: 600; }
    .rs-bar { height: 6px; background: #F1F5F9; border-radius: 999px; overflow: hidden; margin-top: 3px; }
    .rs-bar span { display: block; height: 100%; background: #00929c; }
</style>

<div class="rs-note mb-2">
    Data period: <strong>{{ $audience['period_start'] }} → {{ $audience['period_end'] }}</strong>
    · fetched {{ $audience['fetched_at'] }}
</div>

<div class="rs-grid mb-3">
    <div class="rs-tile">
        <div class="rs-label">Unique Reach</div>
        <div class="rs-value">{{ $rsNum($audience['unique_reach']) }}</div>
        <div class="rs-note">Unique people with a chance to see the site.</div>
    </div>
    <div class="rs-tile">
        <div class="rs-label">Impressions</div>
        <div class="rs-value">{{ $rsNum($audience['impressions']) }}</div>
        <div class="rs-note">Total views, including repeat views.</div>
    </div>
    <div class="rs-tile">
        <div class="rs-label">Frequency</div>
        <div class="rs-value">{{ $audience['frequency'] !== null ? number_format($audience['frequency'], 2) : '–' }}</div>
        <div class="rs-note">Average exposures per person.</div>
    </div>
</div>

<div class="rs-grid mb-3">
    @foreach ($rsTables as $column => [$title, $unit, $note])
        @if (!empty($audience[$column]))
            @php
                $rsMax = $unit === '%' ? 100 : max(1, ...array_map(fn($p) => (float) ($p['value'] ?? 0), $audience[$column]));
            @endphp
            <div class="rs-tile">
                <div class="rs-label">{{ $title }}</div>
                <table class="rs-table">
                    @foreach ($audience[$column] as $pair)
                        <tr>
                            <td>
                                {{ str_replace('_', ' ', $pair['label']) }}
                                @if ($pair['value'] !== null)
                                    <div class="rs-bar"><span style="width: {{ min(100, round((float) $pair['value'] / $rsMax * 100)) }}%"></span></div>
                                @endif
                            </td>
                            <td>{{ $rsNum($pair['value']) }}{{ $pair['value'] !== null ? $unit : '' }}</td>
                        </tr>
                    @endforeach
                </table>
                @if ($note)
                    <div class="rs-note">{{ $note }}</div>
                @endif
            </div>
        @endif
    @endforeach
</div>

@if (!empty($audience['hourly_avg_impressions']))
    <div class="rs-tile mb-3">
        <div class="rs-label">Average Impressions by Hour</div>
        <div class="rs-scroll">
            <table class="rs-hours">
                <tr>
                    @foreach ($audience['hourly_avg_impressions'] as $pair)
                        <th>{{ str_pad($pair['label'], 2, '0', STR_PAD_LEFT) }}:00</th>
                    @endforeach
                </tr>
                <tr>
                    @foreach ($audience['hourly_avg_impressions'] as $pair)
                        <td>{{ $rsNum($pair['value']) }}</td>
                    @endforeach
                </tr>
            </table>
        </div>
    </div>
@endif

@if (!empty($audience['date_wise_impressions']))
    <details class="rs-tile mb-2">
        <summary class="rs-label mb-0" style="cursor:pointer">Impressions by Date ({{ count($audience['date_wise_impressions']) }} days)</summary>
        <table class="rs-table mt-2">
            @foreach ($audience['date_wise_impressions'] as $pair)
                <tr><td>{{ $pair['label'] }}</td><td>{{ $rsNum($pair['value']) }}</td></tr>
            @endforeach
        </table>
    </details>
@endif

<div class="rs-note">Source: RoadStar Audience API. Age, gender, mobile affluence and mobile brand are percentages of people exposed to the site.</div>
