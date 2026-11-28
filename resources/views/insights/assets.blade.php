{{-- Styles and loader for the Location Insights panel (#media-insights), shared by the
     Brand Adda media details page and the vendor website's media page. --}}
<style>
    .insights-card { border-radius: 12px; }
    .insights-card h4 { font-weight: 700; color: #0F172A; }
    .ins-loading { color: #64748B; font-size: 14px; padding: 12px 0; }
    .ins-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 14px; }
    .ins-tile { border: 1px solid #E5E7EB; border-radius: 10px; padding: 14px; background: #fff; min-width: 0; }
    .ins-tile--wide { grid-column: 1 / -1; }
    .ins-label { font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px; display: flex; justify-content: space-between; gap: 6px; align-items: flex-start; }
    .ins-value { font-size: 22px; font-weight: 700; color: #0F172A; line-height: 1.2; }
    .ins-value small { font-size: 13px; font-weight: 500; color: #64748B; }
    .ins-value--na { font-size: 16px; color: #94A3B8; }
    .ins-note { font-size: 12px; color: #64748B; margin-top: 6px; }
    .ins-empty { font-size: 14px; color: #475569; padding: 8px 0; }
    .ins-badge { font-size: 10.5px; font-weight: 600; padding: 2px 8px; border-radius: 999px; white-space: nowrap; }
    .ins-badge--est { background: rgba(249,115,22,.12); color: #C2410C; }
    .ins-badge--part { background: #FEF3C7; color: #92400E; }
    .ins-badge--ok { background: #DCFCE7; color: #166534; }
    .ins-badge--na { background: #F1F5F9; color: #64748B; }
    .ins-bar { height: 6px; background: #F1F5F9; border-radius: 999px; overflow: hidden; margin-top: 8px; }
    .ins-bar span { display: block; height: 100%; background: #F97316; }
    .ins-chip { display: inline-block; background: #0F172A; color: #fff; border-radius: 999px; padding: 3px 10px; font-size: 12px; margin: 0 4px 4px 0; }
    .ins-sector { display: flex; justify-content: space-between; align-items: center; font-size: 13px; padding: 5px 0; gap: 8px; }
    .ins-table { width: 100%; font-size: 13px; border-collapse: collapse; }
    .ins-table th { color: #475569; font-weight: 600; border-bottom: 1px solid #E5E7EB; padding: 6px 8px; white-space: nowrap; }
    .ins-table td { border-bottom: 1px dashed #E5E7EB; padding: 6px 8px; vertical-align: top; }
    .ins-muted { color: #64748B; }
    .ins-link { background: none; border: 0; color: #F97316; font-weight: 600; font-size: 13px; padding: 6px 0; }
    .ins-admin { background: #FFF7ED; border: 1px solid #FED7AA; border-radius: 10px; padding: 10px 14px; font-size: 13px; }
    .ins-refresh-btn { background: #F97316; color: #fff; border: 0; border-radius: 6px; padding: 7px 14px; font-weight: 600; font-size: 13px; }
    .ins-refresh-btn:disabled { opacity: .6; cursor: wait; }
    .ins-attribution { font-size: 11px; text-align: right; margin-top: 10px; }
    .ins-attribution a { color: #64748B; }
    @media (max-width: 576px) { .ins-grid { grid-template-columns: 1fr; } }
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
