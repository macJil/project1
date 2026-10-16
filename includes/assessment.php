<?php
if (!isset($assessment) || empty($_SESSION['USER_ID'])) {
    http_response_code(404);
    exit;
}
?>
<section class="card h-100" aria-labelledby="assessment-title">
    <div class="card-header"><h2 id="assessment-title">Reading summary &amp; risk analyzer</h2></div>
    <div class="card-body">
        <p id="assessment-location" class="fw-semibold"><?= htmlspecialchars($selectedLocation['address_label'] ?? 'Select a point on the map', ENT_QUOTES, 'UTF-8') ?></p>
        <p id="weather-status" role="status">Click inside Irisan to fetch and save weather. Internet is required.</p>
        <button type="button" id="refresh-weather" class="btn btn-primary mb-3" <?= !$selectedLocation ? 'disabled' : '' ?>>Refresh weather</button>
        <div id="saved-assessment" data-expires="<?= (int) ($assessment['expires_at'] ?? 0) ?>">
        <p id="assessment-state" class="fw-semibold"><?php
            if ($assessment['status'] === 'current') echo $assessment['adjusted'] ? 'Recent reading · administrator-selected risk status' : 'Recent provider data · calculated rainfall category';
            elseif ($assessment['status'] === 'outdated') echo 'Outdated data · current assessment unavailable';
            elseif ($assessment['status'] === 'incomplete') echo 'Incomplete or legacy data · current assessment unavailable';
            else echo 'No saved reading for this location.';
        ?></p>
        <?php if ($assessment['status'] === 'current'): ?>
            <p id="current-category"><span class="risk-badge risk-<?= $assessment['category'] ?>"><?= ucfirst($assessment['category']) ?></span></p>
        <?php endif; ?>
        <?php if ($assessment['adjusted']): ?><p class="small">Calculated rainfall category: <?= htmlspecialchars($assessment['calculated_category'], ENT_QUOTES, 'UTF-8') ?>. The risk status was selected by an administrator.</p><?php endif; ?>
        <span class="eyebrow">Saved rainfall totals (mm)</span>
        <div class="rainfall-grid">
        <?php foreach ([1, 24, 72] as $hours): ?>
            <div><span><?= $hours ?> hour<?= $hours > 1 ? 's' : '' ?></span><strong><?= htmlspecialchars($latest['rainfall_' . $hours . 'h'] ?? '—', ENT_QUOTES, 'UTF-8') ?></strong></div>
        <?php endforeach; ?>
        </div>
        <?php if ($latest): ?>
        <dl class="detail-list mt-3">
            <div><dt>Temperature</dt><dd><?= htmlspecialchars($latest['temperature'] ?? '—', ENT_QUOTES, 'UTF-8') ?> °C</dd></div>
            <div><dt>Humidity</dt><dd><?= htmlspecialchars($latest['humidity'] ?? '—', ENT_QUOTES, 'UTF-8') ?> %</dd></div>
            <div><dt>Wind</dt><dd><?= htmlspecialchars($latest['wind_speed'] ?? '—', ENT_QUOTES, 'UTF-8') ?> km/h</dd></div>
        </dl>
        <p class="small mt-3 mb-1">Observed (UTC): <?= htmlspecialchars($latest['observed_at'] ?? 'Unavailable', ENT_QUOTES, 'UTF-8') ?></p>
        <p class="small">Rainfall window end (UTC): <?= htmlspecialchars($latest['rainfall_window_end'] ?? 'Unavailable', ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        </div>
        <p class="prototype-note mb-0"><?= ($latest['source'] ?? '') === 'manual' ? 'Administrator-edited values; original observation time retained.' : 'API weather from <a href="https://open-meteo.com/">Open-Meteo</a>;' ?> prototype assessment, not a measured landslide warning. Low does not mean safe. <a href="<?= BASE_URL ?>/methodology.php">Methodology</a></p>
    </div>
</section>
