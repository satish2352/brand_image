{{-- RoadStar card actions. Calls our own admin routes with the page's CSRF
     token; RoadStar itself is only ever called server-side. --}}
<script>
    $(function () {
        const $card = $('#roadstarCard');
        if (!$card.length) {
            return;
        }

        const urls = {
            map: $card.data('map-url'),
            register: $card.data('register-url'),
            sync: $card.data('sync-url'),
            test: $card.data('test-url'),
        };
        const labels = {
            map: 'Saving mapping…',
            register: 'Registering in RoadStar…',
            sync: 'Fetching RoadStar data…',
            test: 'Testing connection…',
        };

        function notify(ok, message, reload) {
            const done = function () { if (reload) { window.location.reload(); } };
            if (window.Swal) {
                Swal.fire({ icon: ok ? 'success' : 'error', text: message || (ok ? 'Done.' : 'Request failed.') }).then(done);
            } else {
                alert(message);
                done();
            }
        }

        $card.on('click', '.roadstar-action', function () {
            const $btn = $(this);
            const action = $btn.data('action');
            const siteId = $.trim($('#roadstarSiteId').val());

            if (action === 'map' && siteId === '' &&
                !confirm('Remove the RoadStar mapping for this hoarding?')) {
                return;
            }
            if (action === 'register' &&
                !confirm('Register this hoarding in RoadStar as "' + (siteId || $('#roadstarSiteId').attr('placeholder')) + '" with its latitude/longitude?')) {
                return;
            }

            const original = $btn.text();
            $card.find('.roadstar-action').prop('disabled', true);
            $btn.text(labels[action]);

            $.ajax({
                url: urls[action],
                method: action === 'test' ? 'GET' : 'POST',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json' },
                data: (action === 'map' || action === 'register') ? { roadstar_site_id: siteId } : {},
            }).done(function (res) {
                notify(res.ok, res.message, action !== 'test');
            }).fail(function (xhr) {
                const res = xhr.responseJSON || {};
                const message = xhr.status === 429
                    ? 'Too many requests; please wait a minute and try again.'
                    : (res.message || 'Request failed (HTTP ' + xhr.status + ').');
                // A sync that reached RoadStar still changed the stored status.
                notify(false, message, action === 'sync' && xhr.status !== 429);
            }).always(function () {
                $card.find('.roadstar-action').prop('disabled', false);
                $btn.text(original);
            });
        });
    });
</script>
