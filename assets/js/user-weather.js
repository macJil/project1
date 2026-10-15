(function () {
    'use strict';

    const locationSelect = document.getElementById('weather-location');
    const statusMessage = document.getElementById('weather-status');
    const dashboard = document.getElementById('weather-dashboard');
    const historyBody = document.getElementById('weather-hourly-rows');
    const retryButton = document.getElementById('weather-retry');

    if (!locationSelect || !statusMessage || !dashboard || !historyBody) {
        return;
    }

    let activeRequest = null;

    function available(value) {
        return value !== null && value !== undefined && value !== '' && Number.isFinite(Number(value));
    }

    function number(value, digits) {
        return available(value) ? Number(value).toFixed(digits === undefined ? 1 : digits) : 'â€”';
    }

    function localTime(value) {
        if (!value) return 'Unavailable';
        const text = String(value);
        // Open-Meteo's local wall times have no offset; retrieval times include one.
        if (/(?:Z|[+-]\d{2}:?\d{2})$/i.test(text)) {
            const parsed = new Date(text);
            if (!Number.isNaN(parsed.getTime())) {
                return parsed.toLocaleString('en-PH', { timeZone: 'Asia/Manila', hour12: false }) + ' PHT';
            }
        }
        return text.replace('T', ' ') + ' PHT';
    }

    function setStatus(message, kind) {
        statusMessage.className = 'alert alert-' + (kind || 'info');
        statusMessage.textContent = message;
    }

    function clearHistory(message) {
        historyBody.replaceChildren();
        const row = document.createElement('tr');
        const cell = document.createElement('td');
        cell.colSpan = 8;
        cell.className = 'text-center text-muted';
        cell.textContent = message;
        row.appendChild(cell);
        historyBody.appendChild(row);
        document.getElementById('weather-hourly-summary').textContent = message;
    }

    function setRisk(rainfall) {
        const level = rainfall.risk_level;
        const alert = document.getElementById('risk-alert');
        const classByRisk = {
            LOW: 'success',
            NORMAL: 'primary',
            MEDIUM: 'warning',
            HIGH: 'danger'
        };
        const alertClass = classByRisk[level] || 'secondary';

        alert.className = 'alert alert-' + alertClass;
        alert.textContent = level
            ? 'Prototype landslide-risk status: ' + level
            : 'Prototype landslide-risk status: unavailable (rainfall data is incomplete).';

        document.getElementById('rainfall-1h').textContent = available(rainfall.one_hour_mm)
            ? number(rainfall.one_hour_mm, 2) + ' mm' : 'Unavailable';
        document.getElementById('rainfall-24h').textContent = available(rainfall.twenty_four_hours_mm)
            ? number(rainfall.twenty_four_hours_mm, 2) + ' mm' : 'Unavailable';
        document.getElementById('rainfall-72h').textContent = available(rainfall.seventy_two_hours_mm)
            ? number(rainfall.seventy_two_hours_mm, 2) + ' mm' : 'Unavailable';
        document.getElementById('rainfall-observed-at').textContent = localTime(rainfall.observed_at);
    }

    function setCurrent(current, latestHourly, retrievedAt) {
        const intervalMinutes = available(current.interval)
            ? Math.round(Number(current.interval) / 60) + '-minute interval'
            : 'current interval';

        document.getElementById('weather-temperature').textContent = available(current.temperature_2m)
            ? number(current.temperature_2m) + ' Â°C' : 'Unavailable';
        document.getElementById('weather-feels-like').textContent = available(current.apparent_temperature)
            ? number(current.apparent_temperature) + ' Â°C' : 'Unavailable';
        document.getElementById('weather-humidity').textContent = available(current.relative_humidity_2m)
            ? number(current.relative_humidity_2m, 0) + '%' : 'Unavailable';
        document.getElementById('weather-precipitation').textContent = available(current.precipitation)
            ? number(current.precipitation, 2) + ' mm (' + intervalMinutes + ')' : 'Unavailable';
        document.getElementById('weather-rain-showers').textContent =
            number(current.rain, 2) + ' mm rain / ' + number(current.showers, 2) + ' mm showers';
        document.getElementById('weather-wind').textContent =
            number(current.wind_speed_10m) + ' km/h / ' + number(current.wind_gusts_10m) + ' km/h gusts';
        document.getElementById('weather-cloud-cover').textContent = available(current.cloud_cover)
            ? number(current.cloud_cover, 0) + '%' : 'Unavailable';
        document.getElementById('weather-soil-shallow').textContent = available(latestHourly.soil_moisture_0_to_1cm)
            ? number(latestHourly.soil_moisture_0_to_1cm, 3) + ' mÂ³/mÂ³' : 'Unavailable';
        document.getElementById('weather-soil-deep').textContent = available(latestHourly.soil_moisture_27_to_81cm)
            ? number(latestHourly.soil_moisture_27_to_81cm, 3) + ' mÂ³/mÂ³' : 'Unavailable';
        document.getElementById('weather-code').textContent = available(current.weather_code)
            ? String(Math.round(Number(current.weather_code))) : 'Unavailable';
        document.getElementById('weather-current-time').textContent = localTime(current.time);
        document.getElementById('weather-retrieved-at').textContent = localTime(retrievedAt);
    }

    function appendCell(row, value) {
        const cell = document.createElement('td');
        cell.textContent = value === null || value === undefined ? 'â€”' : String(value);
        row.appendChild(cell);
    }

    function setHistory(readings, retrievedAt) {
        historyBody.replaceChildren();
        if (!Array.isArray(readings) || readings.length === 0) {
            clearHistory('The API returned no hourly history for this location.');
            return;
        }

        readings.slice().reverse().forEach(function (reading) {
            const row = document.createElement('tr');
            appendCell(row, localTime(reading.time));
            appendCell(row, number(reading.temperature_2m));
            appendCell(row, number(reading.relative_humidity_2m, 0));
            appendCell(row, number(reading.precipitation, 2));
            appendCell(row, number(reading.rain, 2));
            appendCell(row, number(reading.soil_moisture_0_to_1cm, 3));
            appendCell(row, number(reading.soil_moisture_27_to_81cm, 3));
            appendCell(row, available(reading.weather_code)
                ? String(Math.round(Number(reading.weather_code))) : 'â€”');
            historyBody.appendChild(row);
        });

        document.getElementById('weather-hourly-summary').textContent =
            readings.length + ' hourly readings loaded. API response retrieved ' + localTime(retrievedAt) + '.';
    }

    function showError(errorCode) {
        const messages = {
            authentication_required: 'Your login session expired. Sign in again to load weather data.',
            invalid_csrf_token: 'The page security token expired. Reload the page, then try again.',
            invalid_location_id: 'Select a valid location and try again.',
            location_not_found: 'That location is not available in Barangay Irisan.',
            location_coordinates_invalid: 'This location needs valid latitude and longitude coordinates.',
            weather_data_unavailable: 'The weather API returned incomplete current data. Try again shortly.',
            weather_history_unavailable: 'No hourly weather history was returned for this location.',
            weather_service_unavailable: 'Weather data could not be loaded. Check the database connection, PHP outbound HTTPS access, and try again.'
        };
        setStatus(messages[errorCode] || 'Weather data could not be loaded. Please try again.', 'warning');
        dashboard.hidden = true;
        if (retryButton) retryButton.hidden = false;
        clearHistory('No weather readings loaded.');
    }

    async function loadWeather() {
        const locationId = locationSelect.value;
        if (activeRequest) {
            activeRequest.abort();
        }

        if (!locationId) {
            dashboard.hidden = true;
            if (retryButton) retryButton.hidden = true;
            setStatus('Choose a location to load its current weather and rainfall risk status.', 'info');
            clearHistory('Choose a location above to load its hourly readings.');
            return;
        }

        activeRequest = new AbortController();
        dashboard.hidden = true;
        if (retryButton) retryButton.hidden = true;
        setStatus('Loading current weather API data for the selected locationâ€¦', 'info');
        clearHistory('Loading hourly weather readingsâ€¦');

        try {
            const response = await fetch('../../api/weather.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                },
                body: new URLSearchParams({
                    location_id: locationId,
                    csrf_token: document.getElementById('weather-csrf-token').value
                }),
                signal: activeRequest.signal
            });

            const payload = await response.json();
            if (!response.ok || !payload.data) {
                showError(payload.error || 'weather_service_unavailable');
                return;
            }
            if (locationSelect.value !== locationId) return;

            const data = payload.data;
            const zone = data.location.purok_zone ? ' â€” ' + data.location.purok_zone : '';
            document.getElementById('weather-location-name').textContent = data.location.street_name + zone;
            document.getElementById('weather-coordinates').textContent =
                ' (' + Number(data.location.latitude).toFixed(5) + ', ' + Number(data.location.longitude).toFixed(5) + ')';
            setRisk(data.rainfall);
            setCurrent(data.current, data.latest_hourly, data.retrieved_at);
            setHistory(data.hourly, data.retrieved_at);
            dashboard.hidden = false;
            setStatus('Weather readings loaded for ' + data.location.street_name + zone + '.', 'success');
        } catch (error) {
            if (error.name === 'AbortError') return;
            showError('weather_service_unavailable');
        }
    }

    locationSelect.addEventListener('change', loadWeather);
    if (retryButton) retryButton.addEventListener('click', loadWeather);
    if (locationSelect.value) {
        loadWeather();
    } else if (locationSelect.options.length > 0) {
        clearHistory('No location with valid coordinates is ready for weather readings.');
    }
})();