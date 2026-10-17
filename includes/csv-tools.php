<?php if (!isset($csvKind) || empty($_SESSION['USER_ID'])) { http_response_code(404); exit; } ?>
<div class="card-body border-bottom">
    <a class="btn btn-outline-primary" href="<?= BASE_URL ?>/csv.php?kind=<?= $csvKind ?>">Download <?= $csvKind ?> CSV</a>
    <?php if ($_SESSION['ROLE'] === 'admin'): ?>
    <form method="post" enctype="multipart/form-data" action="<?= BASE_URL ?>/csv.php?kind=<?= $csvKind ?>" class="mt-3">
        <label class="form-label" for="csv-<?= $csvKind ?>">Import a SmartSlope <?= $csvKind ?> CSV (maximum 2 MB / 2000 rows)</label>
        <div class="d-flex flex-wrap gap-2"><input id="csv-<?= $csvKind ?>" class="form-control w-auto" type="file" name="csv_file" accept=".csv,text/csv" required><button class="btn btn-primary">Import CSV</button></div>
        <p class="small text-muted mt-2 mb-0">Use the site's downloaded format. Existing records are skipped. Imported readings are manual historical records, not new live weather assessments.</p>
    </form>
    <?php endif; ?>
</div>
