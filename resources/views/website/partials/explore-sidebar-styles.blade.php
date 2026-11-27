{{-- Filter sidebar styles shared by /explore and the vendor website's map page
     (website/vendor-site/map.blade.php), so both sidebars look identical.
     Plain CSS: include it inside a <style> block. --}}
        /* ---------------- LEFT FILTER SIDEBAR ---------------- */
        .explore-sidebar {
            width: 320px;
            min-width: 320px;
            /* soft brand-warm theme tint instead of plain white */
            background: rgba(249, 115, 22, 0.12);
            border-right: 1px solid rgba(249, 115, 22, 0.12);
            display: flex;
            flex-direction: column;
        }

        .explore-sidebar-head {
            padding: 16px 18px 10px;
            background: rgba(249, 115, 22, 0.12);
            border-bottom: 1px solid rgba(249, 115, 22, 0.12);
        }

        .explore-sidebar-head .title {
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 2px;
            color: #F97316;
            text-transform: uppercase;
        }

        /* The head's title is a button so a phone can open and close the filter
           list. It is styled to look like the plain heading it replaced, and
           above 991px it does nothing at all. */
        .explore-filter-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            width: 100%;
            padding: 0;
            border: 0;
            background: none;
            text-align: left;
            cursor: default;
        }

        .explore-filter-caret {
            display: none;
            font-size: 22px;
            line-height: 1;
            color: #F97316;
            transition: transform .2s ease;
        }

        .explore-showing {
            margin-top: 8px;
            font-size: 12px;
            color: #0F172A;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: flex;
            justify-content: space-between;
            align-items: baseline;
        }

        .explore-showing b {
            font-size: 18px;
            color: #0F172A;
        }

        .explore-sidebar-body {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 6px 18px 80px;
        }

        /* each menu = a clean white card on the warm sidebar (no divider lines) */
        .exp-group {
            background: #FFFFFF;
            border: 1px solid rgba(249, 115, 22, 0.12);
            border-radius: 10px;
            padding: 12px 14px;
            margin-bottom: 10px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
        }

        .exp-group-title {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1.5px;
            color: #F97316;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .grp-filter,
        .exp-search-input {
            width: 100%;
            border: 1px solid #E5E7EB;
            border-radius: 6px;
            padding: 7px 10px;
            font-size: 13px;
            margin-bottom: 8px;
            outline: none;
        }

        /* clear (cancel) icon inside the search bar */
        .exp-search-wrap {
            position: relative;
        }

        .exp-search-wrap .exp-search-input {
            padding-right: 30px;
        }

        .exp-search-clear {
            position: absolute;
            top: 50%;
            right: 8px;
            transform: translateY(-50%);
            margin-top: -4px;
            /* offset the input's 8px bottom margin */
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: #F97316;
            color: #FFFFFF;
            font-size: 14px;
            line-height: 18px;
            text-align: center;
            cursor: pointer;
            display: none;
        }

        .exp-search-clear:hover {
            background: #F97316;
        }

        .exp-search-wrap.has-text .exp-search-clear {
            display: block;
        }

        .grp-filter:focus,
        .exp-search-input:focus {
            border-color: #F97316;
        }

        .exp-options {
            max-height: 168px;
            overflow-y: auto;
            padding-right: 4px;
        }

        .exp-options::-webkit-scrollbar {
            width: 6px;
        }

        .exp-options::-webkit-scrollbar-thumb {
            background: #E5E7EB;
            border-radius: 3px;
        }

        .exp-check {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 5px 4px;
            margin: 0;
            font-size: 14px;
            color: #0F172A;
            font-weight: 400;
            cursor: pointer;
            border-radius: 5px;
        }

        .exp-check:hover {
            background: rgba(249, 115, 22, 0.12);
        }

        /* force native checkbox to render (some global CSS hides them) */
        .exp-check input[type="checkbox"] {
            -webkit-appearance: checkbox !important;
            -moz-appearance: checkbox !important;
            appearance: auto !important;
            width: 16px !important;
            height: 16px !important;
            min-width: 16px;
            flex: 0 0 16px;
            margin: 0 !important;
            position: static !important;
            opacity: 1 !important;
            accent-color: #F97316;
            cursor: pointer;
        }

        .exp-check.hidden {
            display: none;
        }

        .exp-empty-opt {
            font-size: 12px;
            color: rgba(15, 23, 42, 0.55);
            padding: 4px;
        }

        /* range / date filter rows */
        /* stack From / To dates so each gets full width (side-by-side was too
           narrow in the sidebar and the date text/calendar icon overlapped) */
        .exp-range {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .exp-range-field {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .exp-range-field label {
            font-size: 11px;
            color: #0F172A;
            font-weight: 600;
            margin: 0;
        }

        .exp-range-input {
            width: 100%;
            box-sizing: border-box;
            max-width: 100%;
            border: 1px solid #E5E7EB;
            border-radius: 6px;
            padding: 7px 9px;
            font-size: 13px;
            outline: none;
        }

        .exp-range-input:focus {
            border-color: #F97316;
        }

        /* dual-handle range slider (budget + media size) */
        .exp-slider-labels {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            font-weight: 600;
            color: #0F172A;
            margin-bottom: 4px;
        }

        .exp-slider {
            position: relative;
            width: 100%;
            height: 26px;
        }

        .exp-slider input[type=range] {
            -webkit-appearance: none;
            appearance: none;
            position: absolute;
            width: 100%;
            height: 6px;
            top: 8px;
            margin: 0;
            background: none;
            pointer-events: none;
        }

        /* single-handle variant (radius) — the pointer-events juggling above is
           only there so two overlapping thumbs stay grabbable; with one thumb it
           just makes the track dead, so give the input its clicks back */
        .exp-slider.single input[type=range] {
            pointer-events: auto;
            cursor: pointer;
        }

        /* radius needs a town to measure from, so it greys out until there is one */
        .exp-slider.single.is-disabled {
            opacity: .45;
            pointer-events: none;
        }

        .exp-slider-hint {
            font-size: 11px;
            color: #0F172A;
            margin-top: 2px;
        }

        .exp-slider input[type=range]::-webkit-slider-runnable-track {
            height: 6px;
            background: #E5E7EB;
            border-radius: 3px;
        }

        .exp-slider input[type=range]::-moz-range-track {
            height: 6px;
            background: #E5E7EB;
            border-radius: 3px;
        }

        .exp-slider input[type=range]::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            pointer-events: auto;
            width: 16px;
            height: 16px;
            background: #F97316;
            border-radius: 50%;
            cursor: pointer;
            border: 2px solid #FFFFFF;
            box-shadow: 0 0 4px rgba(15, 23, 42, 0.3);
            margin-top: -5px;
            position: relative;
            z-index: 5;
        }

        .exp-slider input[type=range]::-moz-range-thumb {
            pointer-events: auto;
            width: 16px;
            height: 16px;
            background: #F97316;
            border-radius: 50%;
            cursor: pointer;
            border: 2px solid #FFFFFF;
            box-shadow: 0 0 4px rgba(15, 23, 42, 0.3);
        }

        .exp-slider-fill {
            position: absolute;
            height: 6px;
            background: #F97316;
            top: 8px;
            border-radius: 3px;
            z-index: 2;
        }

        /* sticky footer buttons */
        .explore-sidebar-foot {
            position: sticky;
            bottom: 0;
            background: rgba(249, 115, 22, 0.12);
            border-top: 1px solid rgba(249, 115, 22, 0.12);
            padding: 10px 18px;
            display: flex;
            gap: 8px;
        }

        .btn-clear-all {
            flex: 1;
            background: #F97316;
            border: none;
            padding: 10px;
            border-radius: 6px;
            font-weight: 700;
            cursor: pointer;
            color: #FFFFFF;
            box-shadow: 0 2px 6px rgba(249, 115, 22, 0.35);
            transition: background .15s ease;
        }

        .btn-clear-all:hover {
            background: #F97316;
        }

