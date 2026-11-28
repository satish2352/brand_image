{{-- Cascade for partials/location-fields: State → District → City → Area.

     $selected = ['state' => .., 'district' => .., 'city' => .., 'area' => ..]
     is restored on load (old input after a failed submit, or the saved record
     on the Edit page).

     Whenever the state or city changes - by the user or once the initial
     chain has loaded - it fires `media:location-changed` on document, which
     the pages use to (re)generate the hoarding media code. --}}
<script>
    $(function() {
        const csrf = "{{ csrf_token() }}";
        const initial = @json($selected ?? []);

        const $state = $('#state_id');
        const $district = $('#district_id');
        const $city = $('#city_id');
        const $area = $('#area_id');

        function notify() {
            $(document).trigger('media:location-changed');
        }

        // Replace a select's options and pick `selected` if it is in the list.
        // 'change.select2' only refreshes the Select2 display - it does not run
        // the cascade handlers below, so filling a list never clears its children.
        function fill($select, label, items, nameKey, selected) {
            $select.empty().append($('<option>').val('').text('Select ' + label));
            (items || []).forEach(function(item) {
                $select.append($('<option>').val(item.id).text(item[nameKey]));
            });
            $select.val(selected ? String(selected) : '');
            if ($select.val() === null) $select.val('');
            $select.trigger('change.select2');
            return $select.val();
        }

        function reset($select, label) {
            fill($select, label, [], null, '');
        }

        // Each loader ignores a response whose parent has changed since the
        // request went out, so a quick double change cannot show stale options.
        function loadDistricts(stateId, selected) {
            return $.post("{{ route('ajax.districts') }}", { _token: csrf, state_id: stateId })
                .then(function(rows) {
                    if ($state.val() != stateId) return '';
                    return fill($district, 'District', rows, 'district_name', selected);
                });
        }

        function loadCities(districtId, selected) {
            return $.post("{{ route('ajax.cities') }}", { _token: csrf, district_id: districtId })
                .then(function(rows) {
                    if ($district.val() != districtId) return '';
                    return fill($city, 'City', rows, 'city_name', selected);
                });
        }

        function loadAreas(cityId, selected) {
            return $.post("{{ route('ajax.areas') }}", { _token: csrf, city_id: cityId })
                .then(function(rows) {
                    if ($city.val() != cityId) return '';
                    return fill($area, 'Area', rows, 'area_name', selected);
                });
        }

        /* ---------- user changes ---------- */
        $state.on('change', function() {
            reset($district, 'District');
            reset($city, 'City');
            reset($area, 'Area');
            if (this.value) loadDistricts(this.value);
            notify();
        });

        $district.on('change', function() {
            reset($city, 'City');
            reset($area, 'Area');
            if (this.value) loadCities(this.value);
            notify();
        });

        $city.on('change', function() {
            reset($area, 'Area');
            if (this.value) loadAreas(this.value);
            notify();
        });

        /* ---------- initial load (restore saved / old values) ---------- */
        $.get("{{ route('ajax.states') }}")
            .then(function(rows) {
                const stateId = fill($state, 'State', rows, 'state_name', initial.state);
                return stateId ? loadDistricts(stateId, initial.district) : '';
            })
            .then(function(districtId) {
                return districtId ? loadCities(districtId, initial.city) : '';
            })
            .then(function(cityId) {
                return cityId ? loadAreas(cityId, initial.area) : '';
            })
            .always(notify);
    });
</script>
