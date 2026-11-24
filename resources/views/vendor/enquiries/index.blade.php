@extends('vendor.layout')
@section('title', 'Enquiries')
@section('crumb', 'Vendor Panel / Enquiries')
@section('heading', 'Enquiries')
@php $vendorSlug = auth('vendor')->user()->slug; @endphp

@section('actions')
    <a href="{{ route('vendor-site.contact', $vendorSlug) }}" target="_blank" rel="noopener" class="vp-btn vp-btn-g">
        <i class="fa-solid fa-arrow-up-right-from-square"></i>View Contact Page</a>
@endsection

@section('content')
    @php
        use App\Models\VendorEnquiry;
        $tabs = ['' => ['All', $total]] + collect(VendorEnquiry::STATUSES)->mapWithKeys(fn($l, $k) => [$k => [$l, (int) ($counts[$k] ?? 0)]])->all();
    @endphp

    <div class="vp-alert vp-alert-info">
        Enquiries sent from the Contact page of your website
        (<a href="{{ route('vendor-site.contact', $vendorSlug) }}" target="_blank" rel="noopener">{{ preg_replace('#^https?://#', '', route('vendor-site.contact', $vendorSlug)) }}</a>).
        @if ($unread) You have <strong>{{ $unread }} unread</strong>. @endif
    </div>

    <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:center;justify-content:space-between">
        <nav class="vp-tabs" aria-label="Enquiry status">
            @foreach ($tabs as $key => [$label, $count])
                <a href="{{ route('vendor.enquiries', array_filter(['status' => $key, 'q' => request('q')])) }}"
                    class="vp-tab {{ (string) $status === (string) $key ? 'active' : '' }}">{{ $label }} · {{ $count }}</a>
            @endforeach
        </nav>
        <form method="GET" action="{{ route('vendor.enquiries') }}" style="margin:0">
            @if ($status)<input type="hidden" name="status" value="{{ $status }}">@endif
            <label for="eq" class="sr-only">Search enquiries</label>
            <input id="eq" type="search" name="q" value="{{ request('q') }}" class="vp-input" style="width:280px" placeholder="Search name, phone, email, message">
        </form>
    </div>

    @if ($enquiries->isEmpty() && !$selected)
        <section class="vp-card vp-card-pad" style="text-align:center;padding:48px 24px">
            <div style="width:60px;height:60px;margin:0 auto 12px;border-radius:16px;background:#FFF7ED;color:#C2410C;display:grid;place-items:center;font-size:24px"><i class="fa-regular fa-envelope-open"></i></div>
            <h2 class="vp-h2">No enquiries {{ $status || request('q') ? 'match these filters' : 'yet' }}</h2>
            <p class="vp-muted" style="margin:6px 0 0">When someone sends the form on your website’s Contact page, it appears here.</p>
        </section>
    @else
        <div style="display:flex;flex-wrap:wrap;gap:20px;align-items:flex-start">
            {{-- ================= LIST ================= --}}
            <section class="vp-card" style="flex:1 1 320px;padding:8px;min-width:0" aria-label="Enquiry list">
                @foreach ($enquiries as $e)
                    @php
                        $on = $selected && $selected->id === $e->id;
                        [$bg, $fg] = VendorEnquiry::COLORS[$e->status] ?? ['#F1F5F9', '#334155'];
                    @endphp
                    <a href="{{ route('vendor.enquiries', array_filter(['open' => $e->id, 'status' => $status, 'q' => request('q'), 'page' => request('page')])) }}"
                        style="display:block;padding:14px;border-radius:10px;text-decoration:none;color:#0F172A;margin-top:4px;{{ $on ? 'background:#FFF7ED;border:1px solid #FDBA74' : 'border:1px solid transparent' }}">
                        <div style="display:flex;justify-content:space-between;align-items:center;gap:8px">
                            <span style="font-size:14px;font-weight:{{ $e->read_at ? '600' : '800' }}">
                                @unless ($e->read_at)<span style="display:inline-block;width:8px;height:8px;border-radius:999px;background:#F97316;margin-right:6px" aria-label="Unread"></span>@endunless{{ $e->name }}
                            </span>
                            <span class="vp-badge" style="background:{{ $bg }};color:{{ $fg }}">{{ VendorEnquiry::STATUSES[$e->status] ?? $e->status }}</span>
                        </div>
                        <div style="font-size:13px;color:#334155;margin-top:4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $e->message }}</div>
                        <div class="vp-muted" style="font-size:12px;margin-top:4px">
                            {{ $e->media ? ($e->media->hoarding_code ?: $e->media->media_code) . ' · ' : '' }}{{ $e->created_at->format('d M Y, h:i A') }}
                        </div>
                    </a>
                @endforeach
                @if ($enquiries->hasPages())
                    <div style="padding:10px 6px">{{ $enquiries->links('pagination::bootstrap-4') }}</div>
                @endif
            </section>

            {{-- ================= DETAIL ================= --}}
            @if ($selected)
                @php [$sbg, $sfg] = VendorEnquiry::COLORS[$selected->status] ?? ['#F1F5F9', '#334155']; @endphp
                <section class="vp-card" style="flex:999 1 560px;min-width:0;overflow:hidden">
                    <div style="padding:20px 24px;border-bottom:1px solid #F1F5F9;display:flex;flex-wrap:wrap;gap:12px;justify-content:space-between;align-items:flex-start">
                        <div>
                            <h2 class="vp-h2">{{ $selected->name }}</h2>
                            <div class="vp-muted" style="margin-top:4px">Received {{ $selected->created_at->format('d M Y, h:i A') }} · {{ $selected->created_at->diffForHumans() }}</div>
                        </div>
                        <span class="vp-badge" style="background:{{ $sbg }};color:{{ $sfg }};font-size:13px;padding:6px 14px">{{ VendorEnquiry::STATUSES[$selected->status] }}</span>
                    </div>

                    <div style="padding:20px 24px;display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px 24px">
                        @foreach ([
                            'Mobile' => $selected->mobile,
                            'Email ID' => $selected->email,
                            'Company / Brand' => $selected->company ?: '—',
                            'City' => $selected->city ?: '—',
                        ] as $k => $v)
                            <div><div class="vp-muted" style="font-size:12px;font-weight:600">{{ $k }}</div>
                                <div style="font-size:15px;font-weight:700;margin-top:4px;word-break:break-word">{{ $v }}</div></div>
                        @endforeach
                        <div style="grid-column:1/-1">
                            <div class="vp-muted" style="font-size:12px;font-weight:600">Interested in</div>
                            <div style="font-size:15px;font-weight:700;margin-top:4px">
                                @if ($selected->media)
                                    <a href="{{ route('vendor.media.show', $selected->media->id) }}">{{ $selected->media->display_code }} · {{ $selected->media->media_title }}</a>
                                @else
                                    General enquiry
                                @endif
                            </div>
                        </div>
                        <div style="grid-column:1/-1">
                            <div class="vp-muted" style="font-size:12px;font-weight:600">Message</div>
                            <div style="margin-top:6px;padding:14px 16px;border-radius:10px;background:#F8FAFC;border:1px solid #E5E7EB;font-size:14px;line-height:1.6;white-space:pre-line">{{ $selected->message }}</div>
                        </div>
                    </div>

                    <div style="padding:16px 24px;border-top:1px solid #F1F5F9;display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between;background:#FCFCFD">
                        <div style="display:flex;flex-wrap:wrap;gap:10px">
                            <a href="tel:+{{ $selected->phone_intl }}" class="vp-btn vp-btn-o"><i class="fa-solid fa-phone"></i>Call</a>
                            <a href="https://wa.me/{{ $selected->phone_intl }}?text={{ rawurlencode('Hi ' . $selected->name . ', thank you for your enquiry to ' . auth('vendor')->user()->vendor_name . '.') }}"
                                target="_blank" rel="noopener" class="vp-btn" style="background:#16A34A;color:#fff"><i class="fa-brands fa-whatsapp"></i>WhatsApp</a>
                            <a href="mailto:{{ $selected->email }}?subject={{ rawurlencode('Re: your enquiry to ' . auth('vendor')->user()->vendor_name) }}" class="vp-btn vp-btn-g"><i class="fa-regular fa-envelope"></i>Email</a>
                        </div>
                        <form method="POST" action="{{ route('vendor.enquiries.status', $selected->id) }}" style="margin:0;display:flex;gap:8px;align-items:center">
                            @csrf
                            <input type="hidden" name="return_status" value="{{ $status }}">
                            <input type="hidden" name="return_q" value="{{ request('q') }}">
                            <label for="es" class="vp-muted" style="font-size:13px;margin:0">Status</label>
                            <select id="es" name="status" class="vp-input" style="width:auto;min-height:40px">
                                @foreach (VendorEnquiry::STATUSES as $k => $l)<option value="{{ $k }}" @selected($selected->status === $k)>{{ $l }}</option>@endforeach
                            </select>
                            <button type="submit" class="vp-btn vp-btn-n vp-btn-sm" style="min-height:40px">Update</button>
                        </form>
                    </div>
                </section>
            @endif
        </div>
    @endif
@endsection
