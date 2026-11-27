{{-- The vendor website's map, shared by the home page and the Map page: OpenStreetMap tiles, orange
     clusters with the count, and a teardrop pin per site coloured by availability (the selected one
     in navy, bouncing). Needs leaflet.css and MarkerCluster.css in the page head. --}}
<style>
    .vs-legend { position: absolute; left: 12px; bottom: 12px; z-index: 500; background: rgba(255,255,255,.96); border: 1px solid #E5E7EB; border-radius: 12px;
        padding: 10px 12px; display: grid; grid-template-columns: repeat(2, auto); gap: 6px 16px; font-size: 12px; font-weight: 600; box-shadow: 0 6px 16px rgba(15,23,42,.12); }
    .vs-legend span { display: inline-flex; align-items: center; gap: 8px; }
    .vs-legend i { width: 12px; height: 12px; border-radius: 999px; display: inline-block; }
    .vs-pin { background: transparent; border: 0; }
    .vs-pin.is-selected { z-index: 1000 !important; }
    .vs-pin.is-selected svg { animation: vsBounce .7s ease infinite alternate; transform-origin: center bottom; }
    @keyframes vsBounce { from { transform: translateY(0); } to { transform: translateY(-7px); } }
    @media (prefers-reduced-motion: reduce) { .vs-pin.is-selected svg { animation: none; } }
    .vs-cluster { background: transparent; }
    .vs-cluster div { width: 100%; height: 100%; border-radius: 50%; background: #F97316; border: 3px solid #fff; box-shadow: 0 2px 6px rgba(15,23,42,.35);
        display: flex; align-items: center; justify-content: center; color: #0F172A; font: 700 14px 'Montserrat', sans-serif; }
    .vs-pop { width: 220px; font-family: 'Montserrat', sans-serif; }
    .vs-pop img { width: 100%; height: 110px; object-fit: cover; border-radius: 8px; display: block; margin-bottom: 8px; }
    .vs-pop a.vs-btn { min-height: 40px; margin-top: 8px; font-size: 13px; color: #0F172A; width: 100%; }
    .leaflet-popup-content { margin: 12px; }
</style>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
<script>
    window.VsMap = {
        colours: { available: '#16A34A', booked: '#2563EB', blocked: '#DC2626', ask: '#D97706' },
        esc: function (s) { const d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; },
        pin: function (color, big) {
            const w = big ? 40 : 30, h = big ? 54 : 42;
            const svg = '<svg width="' + w + '" height="' + h + '" viewBox="0 0 32 44" xmlns="http://www.w3.org/2000/svg" style="overflow:visible;filter:drop-shadow(0 3px 3px rgba(15,23,42,.4))">'
                + '<path d="M16 0C7.16 0 0 7.16 0 16c0 11.5 16 28 16 28s16-16.5 16-28C32 7.16 24.84 0 16 0z" fill="' + color + '" stroke="#fff" stroke-width="2.5"/>'
                + '<circle cx="16" cy="16" r="6" fill="#fff"/></svg>';
            return L.divIcon({ className: 'vs-pin' + (big ? ' is-selected' : ''), html: svg, iconSize: [w, h], iconAnchor: [w / 2, h], popupAnchor: [0, -h + 8] });
        },
        /* points: id, lat, lng, title, meta, price, img, url, status. Returns map, cluster and markers by id. */
        create: function (el, points, opts) {
            const esc = this.esc, self = this;
            const map = L.map(el, { scrollWheelZoom: !!opts.wheel }).setView([19.0, 75.0], 7);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap contributors' }).addTo(map);
            const cluster = L.markerClusterGroup({
                showCoverageOnHover: false, spiderfyOnMaxZoom: true, maxClusterRadius: 40,
                iconCreateFunction: function (c) {
                    const n = c.getChildCount(), size = n < 10 ? 36 : (n < 100 ? 42 : 50);
                    return L.divIcon({ html: '<div>' + n + '</div>', className: 'vs-cluster', iconSize: L.point(size, size) });
                }
            });
            const markers = {};
            points.forEach(function (p) {
                const photo = p.img ? '<img src="' + esc(p.img) + '" alt="">' : '';
                const mk = L.marker([p.lat, p.lng], { icon: self.pin(self.colours[p.status] || '#F97316', false), title: p.title, alt: p.title })
                    .bindPopup('<div class="vs-pop">' + photo + '<strong>' + esc(p.title) + '</strong><br><span style="color:#475569">' + esc(p.meta)
                        + '</span><br><span style="font-weight:800;color:#C2410C">' + esc(p.price) + '</span><a class="vs-btn vs-btn-o" href="' + esc(p.url) + '">View details</a></div>');
                mk._plain = mk.options.icon;
                mk._big = self.pin('#0F172A', true);
                mk.on('click', function () { opts.onPin(p.id); });
                markers[p.id] = mk;
                cluster.addLayer(mk);
            });
            map.addLayer(cluster);
            return { map: map, cluster: cluster, markers: markers };
        }
    };
</script>
