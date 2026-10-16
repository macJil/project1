<?php
require_once __DIR__ . '/class/RiskAnalyzer.php';
$pageTitle = 'Methodology';
$activePage = 'methodology';
require_once __DIR__ . '/includes/header.php';
?>
<main class="page-shell">
    <div class="page-heading"><div><span class="eyebrow">SmartSlope · Irisan</span><h1>Sources &amp; methodology</h1></div></div>
    <section class="card mb-4"><div class="card-body">
        <h2 class="h4">Weather data and rainfall windows</h2>
        <p>SmartSlope uses <a href="https://open-meteo.com/en/docs">Open-Meteo weather model data</a>, not physical sensors installed in Irisan. Nearby points can share a weather-model grid cell. Clicking a point or pressing Refresh requests weather and saves a new reading only after the response passes the required checks.</p>
        <p>The API precipitation field includes rain, showers, and snow. We use it as the rainfall indicator. Each hourly value covers the preceding hour. The 1-, 24-, and 72-hour totals end at the last completed hour, using UTC. Future values are not included. Missing values are not treated as zero.</p>
        <p>Observations and rainfall-window timestamps are displayed in UTC. A current assessment needs complete rainfall totals and a rainfall window less than three hours old. Older or legacy records remain available as history. Offline tiles and saved readings work without internet; fresh weather does not.</p>
    </div></section>
    <section class="card mb-4"><div class="card-header"><h2>Prototype rainfall rules</h2></div>
        <div class="table-responsive"><table class="table"><thead><tr><th>Category</th><th>1 hour (mm)</th><th>24 hours (mm)</th><th>72 hours (mm)</th></tr></thead><tbody>
        <?php foreach (RiskAnalyzer::THRESHOLDS as $category => $limits): ?>
        <tr><td><?= ucfirst($category) ?></td><?php foreach ($limits as $limit): ?><td>≥ <?= $limit ?></td><?php endforeach; ?></tr>
        <?php endforeach; ?>
        </tbody></table></div>
        <div class="card-body"><p>Use the highest category where at least one threshold is reached. Values below every threshold are labeled low. These rules retain SmartSlope's <code>prototype-1</code> thresholds; they are not calibrated or validated landslide thresholds for Irisan. The label “normal” is a prototype category, not a statement that conditions are safe.</p>
        <p class="mb-0">A low category does not mean the slope is safe. This rainfall-only screening does not account for slope stability, geology, drainage, or physical sensors. It is not an official warning, prediction, or evacuation instruction.</p></div>
    </section>
    <section class="card"><div class="card-body"><h2 class="h4">How records are identified</h2><p><strong>API:</strong> weather-model inputs from Open-Meteo. <strong>Calculated:</strong> rainfall totals and the prototype category. <strong>User-submitted:</strong> location names and landmarks. Map outlines and saved labels do not establish landslide susceptibility.</p><p>The original provider response, rainfall-window end, and rule version are saved in existing event columns. Administrator edits are labeled separately and keep the original observation time. Resident reports do not automatically raise or lower the calculated rainfall category. An administrator may select a different saved risk status; the panel identifies it separately from the calculated category. Mapped addresses from Photon/OpenStreetMap describe nearby mapped features and may be approximate. No new tables or migrations are required. Forecast, soil-moisture, and susceptibility adjustments are not part of this phase's calculation.</p><a class="btn btn-outline-primary" href="<?= $home ?>">Back to dashboard</a></div></section>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
