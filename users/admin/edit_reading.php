<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../class/events.php';
require_once __DIR__ . '/../../class/weather_readings.php';
if (empty($_SESSION['USER_ID'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}
if (($_SESSION['ROLE'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Access denied.');
}
$pageTitle = 'Edit reading';
$activePage = 'readings';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$reading = null;
$error = '';
$fields = [
    'rainfall_1h' => ['1-hour rainfall (mm)', 0, 99999.99],
    'rainfall_24h' => ['24-hour rainfall (mm)', 0, 99999.99],
    'rainfall_72h' => ['72-hour rainfall (mm)', 0, 99999.99],
    'temperature' => ['Temperature (°C)', -100, 100],
    'humidity' => ['Humidity (%)', 0, 100],
    'wind_speed' => ['Wind (km/h)', 0, 9999.99]
];
$form = [];
$riskOptions = ['low', 'normal', 'medium', 'high'];
try {
    $reading = $id ? (new event())->get_dialog($id) : null;
    if (!$reading) {
        http_response_code(404);
        $error = 'Reading not found.';
    } else {
        $form = $reading;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $values = [];
            $chosenRisk = filter_input(INPUT_POST, 'risk_level');
            $form['risk_level'] = $chosenRisk;
            if (!in_array($chosenRisk, $riskOptions, true)) $error = 'Choose a valid risk status.';
            foreach ($fields as $field => [$label, $min, $max]) {
                $form[$field] = trim((string) filter_input(INPUT_POST, $field));
                if ($form[$field] === '' && !str_starts_with($field, 'rainfall_')) {
                    $values[$field] = null;
                    continue;
                }
                $value = filter_var($form[$field], FILTER_VALIDATE_FLOAT);
                if ($value === false || !is_finite($value) || $value < $min || $value > $max) {
                    $error = 'Enter valid numeric values within the displayed ranges.';
                } else {
                    $values[$field] = round($value, 2);
                }
            }
            if (!$error) {
                $risk = (new RiskAnalyzer())->analyze($values['rainfall_1h'], $values['rainfall_24h'], $values['rainfall_72h']);
                if (!$risk) $error = 'Rainfall totals must satisfy 1 hour ≤ 24 hours ≤ 72 hours.';
            }
            if (!$error) {
                $observed = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $reading['observed_at'] ?? '', new DateTimeZone('UTC'));
                $window_end = $observed && $observed->format('Y-m-d H:i:s') === $reading['observed_at']
                    ? gmdate('Y-m-d H:i:s', intdiv($observed->getTimestamp(), 3600) * 3600) : null;
                (new weather_readings())->update($id, $values, $chosenRisk, $window_end, $_SESSION['USER_ID']);
                $_SESSION['reading_notice'] = 'Reading updated. The selected risk status was saved; its observation time was kept.';
                header('Location: ' . BASE_URL . '/readings.php?dialog_id=' . $id, true, 303);
                exit;
            }
        }
    }
} catch (PDOException $exception) {
    error_log('SmartSlope reading edit: ' . $exception->getMessage());
    http_response_code(503);
    $error = 'Reading data is unavailable. Please try again later.';
}
require_once __DIR__ . '/../../includes/header.php';
?>
<main class="page-shell">
    <div class="page-heading"><div><span class="eyebrow">Administrator management</span><h1>Edit reading</h1></div><a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/readings.php">Back to readings</a></div>
    <?php if ($error): ?><p class="alert alert-danger" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <?php if ($reading): ?>
    <section class="card"><div class="card-body">
        <h2 class="h4"><?= htmlspecialchars($reading['name'], ENT_QUOTES, 'UTF-8') ?> · Reading #<?= (int) $reading['id'] ?></h2>
        <p>Observed (UTC): <?= htmlspecialchars($reading['observed_at'] ?? 'Unavailable', ENT_QUOTES, 'UTF-8') ?>. Editing keeps this time and the original provider response, marks the values as administrator-edited, and keeps the calculated rainfall category separate from your selected risk status.</p>
        <form method="post" action="?id=<?= (int) $reading['id'] ?>"><div class="row g-3">
        <?php foreach ($fields as $field => [$label, $min, $max]): ?><div class="col-md-6"><div class="form-floating"><input class="form-control" type="number" step="0.01" min="<?= $min ?>" max="<?= $max ?>" id="<?= $field ?>" name="<?= $field ?>" placeholder="<?= $label ?>" value="<?= htmlspecialchars($form[$field] ?? '', ENT_QUOTES, 'UTF-8') ?>" <?= str_starts_with($field, 'rainfall_') ? 'required' : '' ?>><label for="<?= $field ?>"><?= $label ?></label></div><p class="small text-muted mt-1">Range: <?= $min ?> to <?= $max ?><?= str_starts_with($field, 'rainfall_') ? '' : ' · optional' ?></p></div><?php endforeach; ?>
        </div><div class="form-floating mb-3"><select class="form-select" name="risk_level" id="risk_level" required><?php foreach ($riskOptions as $option): ?><option value="<?= $option ?>" <?= ($form['risk_level'] ?? '') === $option ? 'selected' : '' ?>><?= ucfirst($option) ?></option><?php endforeach; ?></select><label for="risk_level">Risk status (administrator-selected)</label></div><button type="submit" class="btn btn-primary">Save changes</button></form>
    </div></section>
    <?php endif; ?>
</main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
