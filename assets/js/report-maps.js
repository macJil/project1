// Each dialog gets its own map once Bootstrap has made it visible.
document.querySelectorAll('.modal').forEach(dialog => {
    dialog.addEventListener('shown.bs.modal', () => {
        const element = dialog.querySelector('.report-map');
        if (!element) return;
        const status = dialog.querySelector('.report-map-status');
        if (element.reportMap) {
            element.reportMap.invalidateSize();
            return;
        }
        const lat = Number(element.dataset.lat), lng = Number(element.dataset.lng);
        if (element.dataset.lat === '' || element.dataset.lng === '' ||
            !Number.isFinite(lat) || !Number.isFinite(lng) || Math.abs(lat) > 90 || Math.abs(lng) > 180) {
            status.textContent = 'This older report has no valid coordinates. Check its written location details.';
            return;
        }
        if (typeof L === 'undefined') {
            status.textContent = 'Leaflet is missing. Check assets/vendor/leaflet/.';
            return;
        }
        const map = L.map(element, {minZoom: 12, maxZoom: 16, scrollWheelZoom: false});
        element.reportMap = map;
        // Show the saved report point immediately; a failed boundary request must not hide it.
        map.setView([lat, lng], 15);
        L.tileLayer(element.dataset.base + '/assets/map-tiles/{z}/{x}/{y}.png', {
            minZoom: 12, maxZoom: 16, maxNativeZoom: 15, noWrap: true,
            attribution: 'Map data © OpenStreetMap contributors · local tiles'
        }).on('tileerror', () => {
            status.textContent = 'Some local tiles are missing. Report point: ' + lat + ', ' + lng;
        }).addTo(map);
        const colors = {low: '#285333', normal: '#284d69', medium: '#b07812', high: '#8d3029'};
        const color = colors[element.dataset.risk] || '#566151';
        L.circleMarker([lat, lng], {radius: 8, color: color, fillColor: color, fillOpacity: 1})
            .addTo(map).bindTooltip('Reported location');
        requestAnimationFrame(() => map.invalidateSize());
        fetch(element.dataset.base + '/assets/map/irisan.geojson')
            .then(response => {
                if (!response.ok) throw new Error('Boundary unavailable');
                return response.json();
            })
            .then(data => L.geoJSON(data, {interactive: false,
                style: {color: '#705139', weight: 2, fillOpacity: 0.05}}).addTo(map))
            .catch(() => { status.textContent += ' · Boundary unavailable; the report point is still shown.'; });
    });
});
