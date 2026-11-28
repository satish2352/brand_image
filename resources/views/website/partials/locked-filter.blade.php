{{-- One of the four location levels a shared shortlist fixes.

     Rendered disabled, so the browser does not submit it — and that is fine,
     because SharedLinkController recomputes these from the link's own rows on
     every request rather than reading them off the POST. Nothing here is a
     security boundary; it is what the client is being told about the shortlist
     they were sent.

     One value  → that value, selected, and the controller applies it as a real
                  filter.
     Several    → "All N <plural>", because the shortlist straddles them and
                  there is nothing to choose between; the link's own ids are
                  already the boundary.

     @param string      $name     the filter field (category_id, state_id, …)
     @param string|null $id       DOM id the form's JS expects, or null
     @param array       $options  id => label, from the shortlisted media
     @param string      $plural   what to call several of them
--}}
<select name="{{ $name }}" @if ($id) id="{{ $id }}" @endif class="form-select bg-light" disabled
    aria-describedby="biLockedNote" title="Set by the shortlist shared with you">
    @if (count($options) === 1)
        @foreach ($options as $optionId => $optionLabel)
            <option value="{{ $optionId }}" selected>{{ $optionLabel }}</option>
        @endforeach
    @else
        <option value="" selected>All {{ count($options) }} {{ $plural }}</option>
    @endif
</select>
