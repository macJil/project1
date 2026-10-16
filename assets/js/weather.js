// Fetch once per click, then reload saved data. A normal page load never saves.
const weatherButton = document.getElementById('refresh-weather');
if (weatherButton) {
    const mapBox = document.getElementById('location-map');
    const message = document.getElementById('weather-status');
    const savedPanel = document.getElementById('saved-assessment');
    let selected = mapBox.dataset.selected !== '0' ? {location_id: mapBox.dataset.selected} : null;

    async function refreshWeather() {
        if (!selected || (mapBox.dataset.busy && mapBox.dataset.busy !== '0')) return;
        mapBox.dataset.busy = '1';
        weatherButton.disabled = true;
        message.textContent = 'Fetching weather and saving the reading…';
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 20000);
        try {
            const response = await fetch(mapBox.dataset.base + '/api/readings.php', {
                method: 'POST', body: new URLSearchParams(selected), signal: controller.signal
            });
            const data = await response.json();
            if (!response.ok || !data.location_id) throw new Error(data.error || 'Weather could not be saved.');
            window.location.href = '?location_id=' + data.location_id;
        } catch (error) {
            message.textContent = (error.name === 'AbortError' ? 'The request timed out. Reload saved readings before retrying.' : error.message)
                + ' Existing readings were kept. You can use “View saved readings here” for an existing location.';
        } finally {
            clearTimeout(timeout);
            mapBox.dataset.busy = '0';
            weatherButton.disabled = false;
        }
    }
    document.addEventListener('location-selected', event => {
        selected = event.detail;
        document.getElementById('assessment-location').textContent = document.getElementById('map-selection').textContent;
        // Avoid showing the previous location's assessment beside a new point.
        savedPanel.hidden = String(selected.location_id || '') !== mapBox.dataset.selected;
        weatherButton.disabled = false;
        refreshWeather();
    });
    weatherButton.addEventListener('click', refreshWeather);
    const expires = Number(savedPanel.dataset.expires);
    if (expires > 0) {
        setTimeout(() => {
            const badge = document.getElementById('current-category');
            if (badge) badge.hidden = true;
            document.getElementById('assessment-state').textContent = 'Outdated data · current assessment unavailable';
        }, Math.max(0, expires * 1000 - Date.now()));
    }
}
