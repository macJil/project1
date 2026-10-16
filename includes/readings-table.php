<?php
// This section is rendered by the authenticated dashboard or readings page.
if (!isset($readings)) {
    http_response_code(404);
    exit;
}
?>
<div class="table-responsive">
    <table class="table align-middle">
        <caption class="visually-hidden">Saved weather readings; categories are historical results, not a current warning.</caption>
        <thead><tr><th scope="col">Location</th><th scope="col">Saved category</th><th scope="col">Rainfall mm<br>1h / 24h / 72h</th><th scope="col">Observed (UTC)</th><th scope="col">Details</th></tr></thead>
        <tbody>
        <?php foreach ($readings as $reading): ?>
            <tr>
                <td class="fw-semibold"><?= htmlspecialchars($reading['name'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><span class="risk-badge risk-<?= htmlspecialchars($reading['risk_level'] ?? 'unavailable', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($reading['risk_level'] ?? 'unavailable', ENT_QUOTES, 'UTF-8') ?></span></td>
                <td><?= htmlspecialchars(($reading['rainfall_1h'] ?? '—') . ' / ' . ($reading['rainfall_24h'] ?? '—') . ' / ' . ($reading['rainfall_72h'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars($reading['observed_at'] ?? 'Unavailable', ENT_QUOTES, 'UTF-8') ?></td>
                <td><a class="btn btn-sm btn-outline-primary" href="?<?= isset($selectedLocation['id']) ? 'location_id=' . (int) $selectedLocation['id'] . '&amp;' : '' ?>dialog_id=<?= (int) $reading['id'] ?>#reading-details" aria-label="Details for reading <?= (int) $reading['id'] ?>">Details</a>
                    <?php if (($_SESSION['ROLE'] ?? '') === 'admin'): ?>
                    <a class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL ?>/users/admin/edit_reading.php?id=<?= (int) $reading['id'] ?>">Edit</a>
                    <form class="d-inline" method="post" action="<?= BASE_URL ?>/readings.php" onsubmit="return confirm('Permanently delete this reading?');"><input type="hidden" name="id" value="<?= (int) $reading['id'] ?>"><input type="hidden" name="action" value="delete"><button class="btn btn-sm btn-outline-danger" type="submit">Delete</button></form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$readings): ?><tr><td colspan="5" class="empty-cell">No saved weather readings available.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php if ($detail): ?>
<section class="card reading-details m-3" id="reading-details" tabindex="-1" aria-labelledby="detail-title">
    <div class="card-header d-flex justify-content-between align-items-center"><h2 id="detail-title">Reading #<?= (int) $detail['id'] ?></h2><a class="btn btn-sm btn-outline-secondary" href="?<?= isset($selectedLocation['id']) ? 'location_id=' . (int) $selectedLocation['id'] : '' ?>">Close details</a></div>
    <div class="card-body"><dl class="detail-list">
        <div><dt>Location</dt><dd><?= htmlspecialchars($detail['name'], ENT_QUOTES, 'UTF-8') ?></dd></div>
        <div><dt>Observed (UTC)</dt><dd><?= htmlspecialchars($detail['observed_at'] ?? 'Unavailable', ENT_QUOTES, 'UTF-8') ?></dd></div>
        <div><dt>Temperature</dt><dd><?= $detail['temperature'] === null ? 'Unavailable' : htmlspecialchars($detail['temperature'], ENT_QUOTES, 'UTF-8') . ' °C' ?></dd></div>
        <div><dt>Humidity</dt><dd><?= $detail['humidity'] === null ? 'Unavailable' : htmlspecialchars($detail['humidity'], ENT_QUOTES, 'UTF-8') . '%' ?></dd></div>
        <div><dt>Wind speed</dt><dd><?= $detail['wind_speed'] === null ? 'Unavailable' : htmlspecialchars($detail['wind_speed'], ENT_QUOTES, 'UTF-8') . ' km/h' ?></dd></div>
        <div><dt>Source</dt><dd><?= htmlspecialchars($detail['source'] ?? 'Unavailable', ENT_QUOTES, 'UTF-8') ?></dd></div>
    </dl><p class="prototype-note mt-3 mb-0">This is a saved observation. Its category does not establish a current landslide warning.</p></div>
</section>
<?php endif; ?>
