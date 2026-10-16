// Local Leaflet, local boundary, and local image tiles. No online map provider.
const mapElement = document.getElementById('location-map');
if (mapElement) {
    const status = document.getElementById('map-status');
    const selection = document.getElementById('map-selection');
    const openLocation = document.getElementById('open-location');
    const base = mapElement.dataset.base;
    const locations = JSON.parse(document.getElementById('map-locations').textContent);
    const editing = mapElement.dataset.mode === 'edit';
    const reporting = mapElement.dataset.mode === 'report';
    let chosenMarker;
    let addressRequest = 0;

    // Same polygon-edge check as PHP; the server checks again before saving.
    function insideIrisan(lat, lng, points) {
        let inside = false;
        let previous = points.length - 1;
        for (let i = 0; i < points.length; i++) {
            const [x, y] = points[i];
            const [oldX, oldY] = points[previous];
            if ((y > lat) !== (oldY > lat) && lng < (oldX - x) * (lat - y) / (oldY - y) + x) {
                inside = !inside;
            }
            previous = i;
        }
        return inside;
    }

    if (typeof L === 'undefined') {
        status.textContent = 'Leaflet is missing. Check assets/vendor/leaflet/.';
    } else {
        fetch(base + '/assets/map/irisan.geojson')
            .then(response => {
                if (!response.ok) throw new Error('Boundary unavailable');
                return response.json();
            })
            .then(data => {
                const points = data.features[0].geometry.coordinates[0];
                const map = L.map(mapElement, {minZoom: 12, maxZoom: 16, maxBoundsViscosity: 1});
                const boundary = L.geoJSON(data, {
                    interactive: false,
                    style: {color: '#705139', weight: 3, fillOpacity: 0.07}
                }).addTo(map);
                const bounds = boundary.getBounds();
                map.setMaxBounds(bounds.pad(0.15));
                map.fitBounds(bounds, {padding: [20, 20], maxZoom: 14});
                L.tileLayer(base + '/assets/map-tiles/{z}/{x}/{y}.png', {
                    minZoom: 12, maxZoom: 16, maxNativeZoom: 15,
                    bounds: bounds, noWrap: true,
                    attribution: 'Map data © OpenStreetMap contributors · Irisan local tiles'
                }).on('tileerror', () => {
                    status.textContent = 'Some map images are missing. Copy assets/map-tiles/12, 13, 14 and 15 from SmartSlope sf2.';
                }).addTo(map);
                status.textContent = 'Offline map · Brown outline: Irisan study boundary.';

                async function selectPoint(lat, lng, place = null, refresh = false) {
                    if (mapElement.dataset.busy === '1') return;
                    if (!insideIrisan(lat, lng, points)) {
                        selection.textContent = 'Select a point inside the brown Irisan boundary. Your previous selection is unchanged.';
                        return;
                    }
                    if (chosenMarker) map.removeLayer(chosenMarker);
                    chosenMarker = L.circleMarker([lat, lng], {bubblingMouseEvents: false, radius: 9, color: '#705139', fillColor: '#e8c47a', fillOpacity: 1}).addTo(map);
                    const request = ++addressRequest;
                    selection.textContent = (place ? (place.address_label || place.name) : 'Barangay Irisan, Baguio City, Philippines')
                        + ' · ' + lat.toFixed(6) + ', ' + lng.toFixed(6);
                    if (editing || reporting) {
                        document.getElementById('lat').value = lat.toFixed(6);
                        document.getElementById('lng').value = lng.toFixed(6);
                        if (reporting) document.getElementById('report-location').value = place ? place.id : '';
                    } else {
                        openLocation.hidden = !place;
                        if (place) openLocation.href = '?location_id=' + place.id;
                        else selection.textContent += '. Weather will be saved for these coordinates; an administrator can rename the location later.';

                    }
                    if (!refresh) return;
                    if (!editing && !reporting) {
                        mapElement.dataset.busy = 'address';
                        document.getElementById('refresh-weather').disabled = true;
                        document.getElementById('saved-assessment').hidden = true;
                    }
                    const addressStatus = document.getElementById('address-status');
                    if (addressStatus) addressStatus.textContent = 'Looking up the mapped address…';
                    const nameInput = editing ? document.getElementById('name') : null;
                    const landmarkInput = editing ? document.getElementById('landmark') : null;
                    if (editing) {
                        nameInput.value = place ? place.name : 'Irisan location ' + lat.toFixed(6) + ', ' + lng.toFixed(6);
                        landmarkInput.value = place ? (place.landmark || '') : '';
                        document.getElementById('purok').value = place ? (place.purok || '') : '';
                    }
                    const oldName = nameInput ? nameInput.value : '';
                    const oldLandmark = landmarkInput ? landmarkInput.value : '';
                    let address = '';
                    try {
                        const response = await fetch(base + '/api/address.php?' + new URLSearchParams({lat: lat.toFixed(6), lng: lng.toFixed(6)}));
                        const data = await response.json();
                        if (request !== addressRequest) return; // Ignore a slower result for an older click.
                        if (response.ok && data.address) {
                            address = data.address;
                            selection.textContent = address + ' · ' + lat.toFixed(6) + ', ' + lng.toFixed(6);
                            if (editing && nameInput.value === oldName && landmarkInput.value === oldLandmark) {
                                nameInput.value = data.name;
                                landmarkInput.value = address;
                            }
                        }
                    } catch (error) { /* Offline: retain the selected coordinates and saved label. */ }
                    if (request !== addressRequest) return;
                    if (addressStatus) addressStatus.textContent = address
                        ? 'Approximate mapped address · OpenStreetMap / Photon. Check house and street details.'
                        : 'Detailed address unavailable. Saved labels and coordinates remain usable.';
                    if (!editing && !reporting) mapElement.dataset.busy = '';
                    if (!editing && !reporting) document.dispatchEvent(new CustomEvent('location-selected', {
                        detail: place ? {location_id: place.id} : {lat: lat.toFixed(6), lng: lng.toFixed(6)}
                    }));

                }

                locations.forEach(place => {
                    if (place.lat === null || place.lng === null) return;
                    const lat = Number(place.lat), lng = Number(place.lng);
                    if (!Number.isFinite(lat) || !Number.isFinite(lng) || !insideIrisan(lat, lng, points)) return;
                    const label = document.createElement('span');
                    label.textContent = place.address_label || place.name;
                    L.circleMarker([lat, lng], {bubblingMouseEvents: false, radius: 6, color: '#205b43', fillOpacity: 0.8})
                        .addTo(map).bindTooltip(label).on('click', () => selectPoint(lat, lng, place, true));
                    if (!editing && place.id == mapElement.dataset.selected) selectPoint(lat, lng, place);
                });
                if (editing || (reporting && !document.getElementById('report-location').value)) {
                    const lat = document.getElementById('lat').value, lng = document.getElementById('lng').value;
                    if (lat !== '' && lng !== '') selectPoint(Number(lat), Number(lng));
                }
                if (reporting) {
                    document.getElementById('report-location').addEventListener('change', event => {
                        const place = locations.find(item => String(item.id) === event.target.value);
                        if (place) selectPoint(Number(place.lat), Number(place.lng), place, true);
                        else {
                            addressRequest++;
                            document.getElementById('lat').value = '';
                            document.getElementById('lng').value = '';
                            if (chosenMarker) map.removeLayer(chosenMarker);
                            selection.textContent = 'Choose a saved location or click inside Irisan.';
                        }
                    });
                }
                map.on('click', event => selectPoint(event.latlng.lat, event.latlng.lng, null, true));
            })
            .catch(() => {
                status.textContent = 'The local map could not load. Check assets/map/irisan.geojson and reload the page.';
            });
    }
}
