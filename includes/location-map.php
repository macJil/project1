<?php
if (!isset($locations, $showMap) || empty($_SESSION['USER_ID'])) {
    http_response_code(404);
    exit;
}
$mapLocations = [];
foreach ($locations as $place) {
    if (!empty($place['active'])) $mapLocations[] = $place;
}
?>
<div id="location-map" data-base="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>" data-selected="<?= (int) ($selectedLocation['id'] ?? 0) ?>" data-mode="<?= $mapMode ?? 'dashboard' ?>" aria-label="Map of Barangay Irisan"></div>
<div class="map-caption">
    <p id="map-status" role="status" class="mb-2">Loading the local map…</p>
    <p id="map-selection" class="mb-2">Choose a saved location marker or click inside the Irisan boundary.</p>
    <?php if (!in_array($mapMode ?? '', ['edit', 'report'], true)): ?>
    <a id="open-location" class="btn btn-sm btn-primary" hidden>View saved readings here</a>
    <a class="btn btn-sm btn-outline-secondary" href="?">Show all recent readings</a>
    <?php endif; ?>
    <p id="address-status" class="small text-muted mb-0">Mapped addresses are approximate. Address data © OpenStreetMap contributors via Photon; detailed lookup needs internet.</p>
    <p class="small mt-2 mb-0">Latest saved risk: <span class="risk-badge risk-low">Low · green</span> <span class="risk-badge risk-normal">Normal · blue</span> <span class="risk-badge risk-medium">Medium · amber</span> <span class="risk-badge risk-high">High · red</span> · Grey: no reading. Check the assessment time before using a saved status.</p>
    <noscript>Enable JavaScript to select a point on the map. Saved readings remain available below.</noscript>
</div>
<script id="map-locations" type="application/json"><?= json_encode($mapLocations, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE) ?></script>
