{{-- Styles and loader for the Location Insights panel (#media-insights), shared by the
     Brand Adda media details page and the vendor website's media page. --}}
<style>
    /* Location Insights: shared by the media details page and the vendor website's media page. */
    #media-insights { --ins-ink: #0F172A; --ins-muted: #64748B; --ins-line: #E5E7EB; --ins-soft: #F8FAFC; }
    .insights-card { border-radius: 14px; }
    .ins-loading { color: var(--ins-muted); font-size: 14px; padding: 12px 0; }

    .ins-head { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: flex-start; gap: 10px 16px; margin-bottom: 18px; }
    .ins-title { margin: 0 0 4px; font-size: 22px; font-weight: 800; color: var(--ins-ink); }
    .ins-sub { margin: 0; font-size: 13px; color: var(--ins-muted); max-width: 640px; line-height: 1.5; }
    .ins-sub-inline { font-weight: 500; color: var(--ins-muted); font-size: 12px; }
    .ins-stamp { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; color: #475569; background: var(--ins-soft); border: 1px solid var(--ins-line); border-radius: 999px; padding: 5px 12px; white-space: nowrap; }

    .ins-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; }
    .ins-tile { background: #fff; border: 1px solid var(--ins-line); border-radius: 14px; padding: 18px; min-width: 0; display: flex; flex-direction: column; gap: 10px; box-shadow: 0 1px 2px rgba(15, 23, 42, .04); }
    .ins-half { grid-column: span 2; }
    .ins-tile--wide { grid-column: 1 / -1; }
    .ins-label { display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; margin: 0; }
    .ins-kpi-name { display: inline-flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 700; color: var(--ins-ink); line-height: 1.3; }
    .ins-ico { width: 34px; height: 34px; flex: none; border-radius: 10px; display: grid; place-items: center; font-size: 15px; color: var(--c); background: color-mix(in srgb, var(--c) 12%, #fff); }

    .ins-value { font-size: 28px; font-weight: 800; color: var(--ins-ink); line-height: 1.1; display: flex; align-items: baseline; flex-wrap: wrap; gap: 6px; }
    .ins-value small { font-size: 14px; font-weight: 600; color: var(--ins-muted); }
    .ins-value--text { font-size: 20px; }
    .ins-value--na { font-size: 16px; font-weight: 600; color: #94A3B8; }
    .ins-lvl { font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 999px; align-self: center; }
    .ins-lvl--high { background: #DCFCE7; color: #166534; } .ins-lvl--mid { background: #FEF3C7; color: #92400E; } .ins-lvl--low { background: #FEE2E2; color: #991B1B; }
    .ins-meter { display: grid; grid-template-columns: repeat(10, 1fr); gap: 3px; }
    .ins-meter span { height: 8px; border-radius: 3px; background: #EEF2F7; }
    .ins-meter span.on { background: var(--c); }
    .ins-note { margin: auto 0 0; font-size: 12px; color: var(--ins-muted); line-height: 1.5; }
    .ins-facts { margin: 0; display: grid; gap: 4px; font-size: 13px; }
    .ins-facts div { display: flex; justify-content: space-between; gap: 8px; border-top: 1px dashed var(--ins-line); padding-top: 4px; }
    .ins-facts dt { font-weight: 500; color: var(--ins-muted); } .ins-facts dd { margin: 0; font-weight: 600; color: var(--ins-ink); }

    .ins-badge { font-size: 11px; font-weight: 700; padding: 3px 9px; border-radius: 999px; white-space: nowrap; }
    .ins-badge--est { background: #FFEDD5; color: #9A3412; }
    .ins-badge--part { background: #FEF3C7; color: #92400E; }
    .ins-badge--ok { background: #DCFCE7; color: #166534; }
    .ins-badge--na { background: #F1F5F9; color: #475569; }

    /* Audience shares */
    .ins-shares { list-style: none; margin: 0; padding: 0; display: grid; gap: 12px; }
    .ins-share-row { display: flex; justify-content: space-between; font-size: 14px; color: var(--ins-ink); margin-bottom: 5px; }
    .ins-dot { display: inline-block; width: 9px; height: 9px; border-radius: 50%; margin-right: 8px; vertical-align: 1px; }
    .ins-track { height: 8px; border-radius: 999px; background: #EEF2F7; overflow: hidden; }
    .ins-track span { display: block; height: 100%; border-radius: 999px; }
    .ins-track--thin { height: 5px; margin-top: 6px; }
    .ins-eg { font-size: 12px; color: var(--ins-muted); margin-top: 4px; }

    /* Recommendations */
    .ins-sector { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 9px 0; border-top: 1px solid #F1F5F9; }
    .ins-label + .ins-sector { border-top: 0; }
    .ins-sector-main { flex: 1; min-width: 0; }
    .ins-sector-name { font-size: 14px; font-weight: 600; color: var(--ins-ink); }
    .ins-fit { font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 999px; white-space: nowrap; }
    .ins-fit.ins-fit--strong { background: #DCFCE7; color: #166534; } .ins-track span.ins-fit--strong { background: #16A34A; }
    .ins-fit.ins-fit--mid { background: #FEF3C7; color: #92400E; } .ins-track span.ins-fit--mid { background: #F59E0B; }
    .ins-fit.ins-fit--low { background: #F1F5F9; color: #475569; } .ins-track span.ins-fit--low { background: #94A3B8; }

    /* Landmarks */
    .ins-table-wrap { overflow: auto; max-height: 440px; border: 1px solid var(--ins-line); border-radius: 10px; }
    .ins-table { width: 100%; font-size: 13px; border-collapse: collapse; }
    .ins-table th { position: sticky; top: 0; background: var(--ins-soft); color: #475569; font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: .03em; text-align: left; padding: 10px 12px; border-bottom: 1px solid var(--ins-line); white-space: nowrap; }
    .ins-table td { padding: 10px 12px; border-bottom: 1px solid #F1F5F9; vertical-align: top; color: #334155; }
    .ins-table tbody tr:last-child td { border-bottom: 0; }
    .ins-table tbody tr:hover td { background: #FFFBF5; }
    .ins-strong { font-weight: 600; color: var(--ins-ink) !important; }
    .ins-cat { display: inline-block; font-size: 12px; font-weight: 600; padding: 2px 9px; border-radius: 999px; background: #F1F5F9; color: #334155; white-space: nowrap; }
    .ins-dist { font-weight: 700; color: var(--ins-ink); }
    .ins-right { text-align: right !important; } .ins-nowrap { white-space: nowrap; }
    .ins-muted { color: var(--ins-muted) !important; }
    .ins-more.d-none { display: none; }
    .ins-show-sm { display: none; }
    .ins-empty { font-size: 14px; color: #475569; padding: 8px 0; }
    .ins-link { align-self: flex-start; background: none; border: 0; color: #C2410C; font-weight: 700; font-size: 13px; padding: 4px 0; cursor: pointer; }
    .ins-tagged { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; padding-top: 12px; border-top: 1px dashed var(--ins-line); }
    .ins-tagged-label { font-size: 12px; font-weight: 700; color: #475569; margin-right: 4px; }
    .ins-chip { display: inline-flex; align-items: center; gap: 5px; background: #FFF7ED; color: #9A3412; border: 1px solid #FED7AA; border-radius: 999px; padding: 3px 11px; font-size: 12px; font-weight: 600; }

    /* Admin bar */
    .ins-admin { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 8px 16px; background: #FFF7ED; border: 1px solid #FED7AA; border-radius: 10px; padding: 10px 14px; font-size: 13px; margin-bottom: 16px; }
    .ins-text-danger { color: #B91C1C; }
    .ins-refresh-btn { background: #F97316; color: #fff; border: 0; border-radius: 8px; padding: 8px 14px; font-weight: 700; font-size: 13px; cursor: pointer; }
    .ins-refresh-btn:disabled { opacity: .6; cursor: wait; }

    @media (max-width: 1199px) { .ins-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } .ins-half { grid-column: 1 / -1; } }
    @media (max-width: 576px) {
        .ins-grid { grid-template-columns: 1fr; }
        .ins-hide-sm { display: none; }
        .ins-show-sm { display: table; margin-top: 4px; font-weight: 600; }   /* category under the place name */
        .ins-sub-inline { display: block; margin-top: 2px; }
        .ins-value { font-size: 24px; }
    }
</style>

<script>
    /* Location insights: load the panel (DB only), then wire Refresh
       (admin, may spend API credits) and "Show all places". */
    document.addEventListener('DOMContentLoaded', function() {
        const box = document.getElementById('media-insights');
        if (!box) return;

        function load() {
            fetch(box.dataset.url, { headers: { 'Accept': 'application/json' } })
                .then(r => r.json().then(data => ({ ok: r.ok, data })))
                .then(({ ok, data }) => {
                    box.innerHTML = ok && data.html
                        ? data.html
                        : '<h4 class="mb-2">Location Insights</h4><div class="ins-value ins-value--na">Not Available</div>'
                          + '<div class="ins-note">' + (data.message ? escapeHtml(data.message) : 'Please try again later.') + '</div>';
                })
                .catch(() => {
                    box.innerHTML = '<h4 class="mb-2">Location Insights</h4><div class="ins-value ins-value--na">Not Available</div>'
                        + '<div class="ins-note">Could not load location insights.</div>';
                });
        }

        function escapeHtml(s) {
            const d = document.createElement('div');
            d.textContent = s;
            return d.innerHTML;
        }

        box.addEventListener('click', function(e) {
            if (e.target.closest('#insightsShowAll')) {
                box.querySelectorAll('.ins-more').forEach(tr => tr.classList.remove('d-none'));
                e.target.closest('#insightsShowAll').remove();
                return;
            }

            const btn = e.target.closest('#insightsRefreshBtn');
            if (!btn) return;

            const msg = document.getElementById('insightsMsg');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span> Refreshing…';
            msg.className = 'mt-1';
            msg.textContent = '';

            fetch(btn.dataset.url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
            })
            .then(r => r.json().then(data => ({ status: r.status, data })))
            .then(({ status, data }) => {
                msg.textContent = data.message || (status === 429 ? 'Too many refreshes. Please wait a minute.' : 'Refresh failed.');
                msg.className = 'mt-1 ' + (data.ok ? 'text-success' : 'text-danger');
                if (data.ok) {
                    setTimeout(load, 1200);
                } else {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh Insights';
                }
            })
            .catch(() => {
                msg.textContent = 'Could not reach the server. Please try again.';
                msg.className = 'mt-1 text-danger';
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh Insights';
            });
        });

        load();
    });
</script>
