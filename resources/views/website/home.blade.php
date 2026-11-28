@extends('website.layout')

@section('title', 'Home')

@section('content')

    @include('website.index')

@endsection

@section('scripts')
    {{-- Browsers restore the previous scroll position on reload, so refreshing
         part-way down this page reopens it below the hero and the headline is
         never seen. This is a landing page — a refresh should start at the top.
         Skipped when the URL carries a hash so /#mediaSearch still works. --}}
    <script>
        if ('scrollRestoration' in history && !window.location.hash) {
            history.scrollRestoration = 'manual';
            window.addEventListener('load', function () {
                window.scrollTo(0, 0);
            });
        }
    </script>

    {{-- Hero map panel grows as it scrolls into view and shrinks on the way back
         out. Applied from JS rather than CSS so that with scripting off the
         panel simply renders at full size instead of being stuck scaled down. --}}
    <script>
        (function () {
            const panel = document.querySelector('.bi-hero-panel');
            if (!panel) return;

            // Decorative only — leave it alone for anyone who asked for less motion.
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

            const MAX = 1;              // scale once it has been scrolled into place

            // How small it starts. A phone-width panel shrunk to two thirds would
            // render the framed map page unreadably small, so the effect is much
            // gentler there than on a desktop where there is room to spare.
            function startScale() {
                return window.innerWidth < 768 ? 0.88 : 0.66;
            }
            let ticking = false;

            panel.style.transformOrigin = 'center center';
            panel.style.willChange = 'transform';
            // Short transition smooths the jumps a mouse wheel produces without
            // lagging behind a trackpad's continuous scroll.
            panel.style.transition = 'transform .12s linear';

            function update() {
                ticking = false;

                const rect = panel.getBoundingClientRect();
                const vh = window.innerHeight;

                // The panel already sits about half-way up the screen on load, so
                // anchoring the range to "enters the viewport" would finish the
                // effect within ~150px. Instead it runs from roughly its resting
                // position up to the top of the screen, giving a visible ramp.
                const start = vh * 0.6;
                const end = vh * 0.05;
                const raw = (start - rect.top) / (start - end);
                const progress = Math.min(1, Math.max(0, raw));

                const min = startScale();
                panel.style.transform = 'scale(' + (min + (MAX - min) * progress).toFixed(4) + ')';
            }

            function onScroll() {
                if (ticking) return;
                ticking = true;
                requestAnimationFrame(update);
            }

            window.addEventListener('scroll', onScroll, { passive: true });
            window.addEventListener('resize', onScroll);
            update();
        })();
    </script>

    {{-- Feature 4: the first search from the home page lands on the new Explore page.
         Scoped to the home page only — the existing /search page is untouched. --}}
    <script>
        $(function () {
            $('#searchForm').on('submit', function (e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                const params = $(this).serialize();
                window.location.href = "{{ route('website.explore') }}" + (params ? '?' + params : '');
            });
        });
    </script>
@endsection
