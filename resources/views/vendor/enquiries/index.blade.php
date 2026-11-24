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
        use Illuminate\Support\Str;
        $tabs = ['' => ['All', $total]] + collect(VendorEnquiry::STATUSES)->mapWithKeys(fn($l, $k) => [$k => [$l, (int) ($counts[$k] ?? 0)]])->all();
        $vendorName = auth('vendor')->user()->vendor_name;
        $waText = fn($name) => rawurlencode('Hi ' . $name . ', thank you for your enquiry to ' . $vendorName . '.');
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

    @if ($enquiries->isEmpty())
        <section class="vp-card vp-card-pad" style="text-align:center;padding:48px 24px">
            <div style="width:60px;height:60px;margin:0 auto 12px;border-radius:16px;background:#FFF7ED;color:#C2410C;display:grid;place-items:center;font-size:24px"><i class="fa-regular fa-envelope-open"></i></div>
            <h2 class="vp-h2">No enquiries {{ $status || request('q') ? 'match these filters' : 'yet' }}</h2>
            <p class="vp-muted" style="margin:6px 0 0">When someone sends the form on your website’s Contact page, it appears here.</p>
        </section>
    @else
        <section class="vp-card" style="overflow:hidden" aria-label="Enquiries">
            <div class="vp-table-wrap">
                <table class="vp-table">
                    <thead>
                        <tr>
                            <th>Sr. No.</th>
                            <th>Received</th>
                            <th>Name</th>
                            <th>Mobile</th>
                            <th>Email ID</th>
                            <th>Company / City</th>
                            <th>Interested In</th>
                            <th style="min-width:220px">Message</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($enquiries as $e)
                            @php
                                [$bg, $fg] = VendorEnquiry::COLORS[$e->status] ?? ['#F1F5F9', '#334155'];
                                $openUrl = route('vendor.enquiries', array_filter(['open' => $e->id, 'status' => $status, 'q' => request('q'), 'page' => request('page')]));
                            @endphp
                            <tr class="eq-row" data-href="{{ $openUrl }}" tabindex="0" role="link" aria-label="Open enquiry from {{ $e->name }}"
                                style="{{ $e->read_at ? '' : 'background:#FFFBF5' }}">
                                <td class="nowrap">{{ $enquiries->firstItem() + $loop->index }}</td>
                                <td class="nowrap">
                                    {{ $e->created_at->format('d M Y') }}
                                    <div class="vp-muted" style="font-size:12px">{{ $e->created_at->format('h:i A') }}</div>
                                </td>
                                <td class="nowrap" style="font-weight:{{ $e->read_at ? '600' : '800' }}">
                                    @unless ($e->read_at)
                                        <span title="Unread" aria-label="Unread" style="display:inline-block;width:8px;height:8px;border-radius:999px;background:#F97316;margin-right:6px"></span>
                                    @endunless{{ $e->name }}
                                </td>
                                <td class="nowrap"><a href="tel:+{{ $e->phone_intl }}" style="color:#0F172A">{{ $e->mobile }}</a></td>
                                <td class="nowrap"><a href="mailto:{{ $e->email }}">{{ $e->email }}</a></td>
                                <td>
                                    {{ $e->company ?: '—' }}
                                    @if ($e->city)<div class="vp-muted" style="font-size:12px">{{ $e->city }}</div>@endif
                                </td>
                                <td class="nowrap">
                                    @if ($e->media)
                                        <a href="{{ route('vendor.media.show', $e->media->id) }}">{{ $e->media->hoarding_code ?: $e->media->media_code }}</a>
                                        <div class="vp-muted" style="font-size:12px">{{ Str::limit($e->media->media_title, 24) }}</div>
                                    @else
                                        <span class="vp-muted">General</span>
                                    @endif
                                </td>
                                <td style="color:#334155">{{ Str::limit($e->message, 70) }}</td>
                                <td class="nowrap"><span class="vp-badge" style="background:{{ $bg }};color:{{ $fg }}">{{ VendorEnquiry::STATUSES[$e->status] ?? $e->status }}</span></td>
                                <td class="nowrap">
                                    <div style="display:flex;gap:6px">
                                        <a href="{{ $openUrl }}" class="vp-ib" aria-label="View enquiry from {{ $e->name }}" title="View"><i class="fa-regular fa-eye"></i></a>
                                        <a href="https://wa.me/{{ $e->phone_intl }}?text={{ $waText($e->name) }}" target="_blank" rel="noopener" class="vp-ib"
                                            aria-label="WhatsApp {{ $e->name }}" title="WhatsApp" style="color:#16A34A !important"><i class="fa-brands fa-whatsapp"></i></a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="vp-pagination">
                <span class="vp-muted">Showing {{ $enquiries->firstItem() }}–{{ $enquiries->lastItem() }} of {{ $enquiries->total() }} enquiries</span>
                {{ $enquiries->links('pagination::bootstrap-4') }}
            </div>
        </section>
    @endif

    {{-- ================= DETAIL POPUP (View / ?open=ID) ================= --}}
    @if ($selected)
        @php [$sbg, $sfg] = VendorEnquiry::COLORS[$selected->status] ?? ['#F1F5F9', '#334155']; @endphp
        <div class="modal fade" id="enquiryModal" tabindex="-1" role="dialog" aria-labelledby="enquiryTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                <div class="modal-content" style="overflow:hidden">
                    <div style="padding:20px 24px;border-bottom:1px solid #F1F5F9;display:flex;flex-wrap:wrap;gap:12px;justify-content:space-between;align-items:flex-start">
                        <div>
                            <h2 class="vp-h2" id="enquiryTitle">{{ $selected->name }}</h2>
                            <div class="vp-muted" style="margin-top:4px">Received {{ $selected->created_at->format('d M Y, h:i A') }} · {{ $selected->created_at->diffForHumans() }}</div>
                        </div>
                        <div style="display:flex;gap:10px;align-items:center">
                            <span class="vp-badge" style="background:{{ $sbg }};color:{{ $sfg }};font-size:13px;padding:6px 14px">{{ VendorEnquiry::STATUSES[$selected->status] }}</span>
                            <button type="button" class="vp-ib" data-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
                        </div>
                    </div>

                    <table class="vp-table" style="font-size:14px">
                        <tbody>
                            @foreach ([
                                'Mobile' => $selected->mobile,
                                'Email ID' => $selected->email,
                                'Company / Brand' => $selected->company ?: '—',
                                'City' => $selected->city ?: '—',
                            ] as $k => $v)
                                <tr>
                                    <th style="width:180px;background:#F8FAFC;padding-left:24px">{{ $k }}</th>
                                    <td style="font-weight:600;word-break:break-word">{{ $v }}</td>
                                </tr>
                            @endforeach
                            <tr>
                                <th style="background:#F8FAFC;padding-left:24px">Interested In</th>
                                <td style="font-weight:600">
                                    @if ($selected->media)
                                        <a href="{{ route('vendor.media.show', $selected->media->id) }}">{{ collect([$selected->media->display_code, $selected->media->media_title])->filter()->implode(' · ') }}</a>
                                    @else
                                        General enquiry
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th style="background:#F8FAFC;padding-left:24px;vertical-align:top">Message</th>
                                <td style="line-height:1.6;white-space:pre-line">{{ $selected->message }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <div style="padding:16px 24px;border-top:1px solid #F1F5F9;display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between;background:#FCFCFD">
                        <div style="display:flex;flex-wrap:wrap;gap:10px">
                            <a href="tel:+{{ $selected->phone_intl }}" class="vp-btn vp-btn-o"><i class="fa-solid fa-phone"></i>Call</a>
                            <a href="https://wa.me/{{ $selected->phone_intl }}?text={{ $waText($selected->name) }}" target="_blank" rel="noopener" class="vp-btn"
                                style="background:#16A34A;color:#fff"><i class="fa-brands fa-whatsapp"></i>WhatsApp</a>
                            <a href="mailto:{{ $selected->email }}?subject={{ rawurlencode('Re: your enquiry to ' . $vendorName) }}" class="vp-btn vp-btn-g"><i class="fa-regular fa-envelope"></i>Email</a>
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
                </div>
            </div>
        </div>
    @endif
@endsection

@section('styles')
    <style>
        .eq-row { cursor: pointer; transition: background .12s; }
        .eq-row:hover, .eq-row:focus { background: #FFF7ED !important; }
        .eq-row:focus { outline: 2px solid #F97316; outline-offset: -2px; }
    </style>
@endsection

@section('scripts')
    <script>
        // Click (or Enter on) a row to open that enquiry; links inside the row keep their own action.
        $(document).on('click', '.eq-row', function (e) {
            if (e.target.closest('a, button, input, select, label')) return;
            window.location = this.dataset.href;
        });
        $(document).on('keydown', '.eq-row', function (e) {
            if (e.key === 'Enter' && e.target === this) window.location = this.dataset.href;
        });
    </script>
    @if ($selected)
        <script>
            // View / ?open=ID shows that enquiry straight away; closing it drops ?open from the address.
            $(function () {
                $('#enquiryModal').appendTo('body').modal('show').on('hidden.bs.modal', function () {
                    const url = new URL(window.location.href);
                    url.searchParams.delete('open');
                    history.replaceState(null, '', url.toString());
                });
            });
        </script>
    @endif
@endsection
