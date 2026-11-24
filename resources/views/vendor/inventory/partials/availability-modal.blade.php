{{-- Update Availability modal (PRD 2.3). Any button with .js-availability and
     data-url opens it with that media's form. --}}
<div class="modal fade" id="availabilityModal" tabindex="-1" role="dialog" aria-labelledby="availabilityTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width:600px">
        <div class="modal-content" id="availabilityBody">
            <div style="padding:40px;text-align:center" class="vp-muted">Loading…</div>
        </div>
    </div>
</div>
<script>
    $(document).on('click', '.js-availability', function () {
        $('#availabilityBody').html('<div style="padding:40px;text-align:center" class="vp-muted">Loading…</div>');
        $('#availabilityModal').modal('show');
        $.get(this.dataset.url, function (html) {
            $('#availabilityBody').html(html);
        }).fail(function () {
            $('#availabilityBody').html('<div style="padding:40px;text-align:center;color:#B91C1C">Could not load this media. Please refresh and try again.</div>');
        });
    });
    $(document).on('change', '#availabilityBody input[name="status"]', function () {
        const needsDates = this.value === 'booked' || this.value === 'blocked';
        $('#availabilityBody .js-dates').toggle(needsDates);
        $('#availabilityBody .js-dates input').prop('required', needsDates);
        $('#availabilityBody .js-status-card').css({ borderColor: '#CBD5E1', borderWidth: '1px', background: '#fff' });
        $(this).closest('.js-status-card').css({ borderColor: '#F97316', borderWidth: '2px', background: '#FFF7ED' });
    });
</script>
