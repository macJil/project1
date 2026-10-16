// Initialize after Bootstrap shows the dialog, so Leaflet can measure its width.
let reportBoundary;
document.addEventListener('shown.bs.modal', async event => {
    const element = event.target.querySelector('.report-map');
    if (!element) return;
    const status = event.target.querySelector('.report-map-status');
    if (element.reportMap) {
        element.reportMap.invalidateSize();
        return;
    }
    if (element.dataset.lat === '' || element.dataset.lng === '' || typeof L === 'undefined') {
        status.textContent = 'A map is unavailable for this older report. Check its written location details.';
        return;
    }
    const lat = Number(element.dataset.lat), lng = Number(element.dataset.lng);
    if (!Number.isFinite(lat) || !Number.isFinite(lng) || lat < -90 || lat > 90 || lng < -180 || lng > 180) {
        status.textContent = 'This report has invalid map coordinates.';
        return;
    }
    try {
        if (!reportBoundary) reportBoundary = fetch(element.dataset.base + '/assets/map/irisan.geojson').then(response => {
            if (!response.ok) throw new Error('Boundary unavailable');
            return response.json();
        });
        const data = await reportBoundary;
        if (element.reportMap) { element.reportMap.invalidateSize(); return; }
        const map = L.map(element, {minZoom: 12, maxZoom: 16, scrollWheelZoom: false});
        element.reportMap = map;
        const boundary = L.geoJSON(data, {interactive: false, style: {color: '#705139', weight: 2, fillOpacity: 0.05}}).addTo(map);
        const bounds = boundary.getBounds();
        map.setMaxBounds(bounds.pad(0.2));
        L.tileLayer(element.dataset.base + '/assets/map-tiles/{z}/{x}/{y}.png', {
            minZoom: 12, maxZoom: 16, maxNativeZoom: 15, bounds: bounds, noWrap: true,
            attribution: 'Map data © OpenStreetMap contributors · local tiles'
        }).on('tileerror', () => { status.textContent = 'Some local tiles are missing. Report coordinates: ' + lat + ', ' + lng; }).addTo(map);
        L.circleMarker([lat, lng], {radius: 8, color: '#705139', fillColor: '#e8c47a', fillOpacity: 1}).addTo(map);
        map.setView([lat, lng], 15);
        map.invalidateSize();
    } catch (error) {
        reportBoundary = null;
        status.textContent = 'Local map unavailable. Report coordinates: ' + lat + ', ' + lng;
    }
});
