<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../class/reports.php';
require_once __DIR__ . '/../../class/locations.php';
require_once __DIR__ . '/../../includes/map-boundary.php';
if (empty($_SESSION['USER_ID'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}
if (($_SESSION['ROLE'] ?? '') !== 'user') {
    http_response_code(403);
    exit('Access denied.');
}
$pageTitle = 'Ground reports';
$activePage = 'report';
$showMap = true;
$mapMode = 'report';
$location = new location();
$reportModel = new reports();
$locations = $history = [];
$selectedLocation = null;
$types = ['cracks' => 'Ground cracks', 'landslide' => 'Observed soil movement', 'drainage' => 'Drainage / flooding', 'other' => 'Other observation'];
$form = ['location_id' => '', 'lat' => '', 'lng' => '', 'message' => '', 'report_type' => 'other',
    'occurred_at' => (new DateTimeImmutable('now', new DateTimeZone('Asia/Manila')))->format('Y-m-d\TH:i'),
    'contact_phone' => '', 'contact_email' => '', 'house_landmark' => ''];
$error = '';
$notice = $_SESSION['report_notice'] ?? '';
unset($_SESSION['report_notice']);
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        foreach ($form as $field => $value) $form[$field] = trim((string) filter_input(INPUT_POST, $field));
        $id = filter_var($form['location_id'], FILTER_VALIDATE_INT);
        $lat = filter_var($form['lat'], FILTER_VALIDATE_FLOAT);
        $lng = filter_var($form['lng'], FILTER_VALIDATE_FLOAT);
        $occurred = preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $form['occurred_at'])
            ? DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $form['occurred_at'], new DateTimeZone('Asia/Manila')) : false;
        if ($form['message'] === '' || strlen($form['message']) > 5000 || !isset($types[$form['report_type']])) {
            $error = 'Choose a report type and describe your observation (up to 5000 bytes).';
        } elseif ($form['contact_phone'] === '' || $form['contact_email'] === '' || strlen($form['house_landmark']) > 255
            || (!preg_match('/^[0-9+() .-]{7,20}$/', $form['contact_phone']))
            || ((strlen($form['contact_email']) > 254 || !filter_var($form['contact_email'], FILTER_VALIDATE_EMAIL)))) {
            $error = 'Check the contact details and landmark length.';
        } elseif (!$occurred || $occurred->format('Y-m-d\TH:i') !== $form['occurred_at'] || $occurred->getTimestamp() > time() + 300) {
            $error = 'Enter a valid observation time in Manila time, not a future time.';
        } else {
            if ($form['location_id'] !== '') {
                $selectedLocation = $id ? $location->get_location($id) : null;
                if (!$selectedLocation || !$selectedLocation['active']) $error = 'Choose an active location.';
            } elseif ($lat === false || $lng === false || !inside_irisan(round($lat, 6), round($lng, 6))) {
                $error = 'Select a location or click a point inside Irisan.';
            } else {
                $selectedLocation = $location->find_point(round($lat, 6), round($lng, 6));
                if ($selectedLocation && !$selectedLocation['active']) $error = 'That location is inactive. Choose another active location.';
                if (!$selectedLocation) $selectedLocation = $location->save_point(round($lat, 6), round($lng, 6));
            }
            if (!$error && !inside_irisan((float) $selectedLocation['lat'], (float) $selectedLocation['lng'])) {
                $error = 'Select a location inside Irisan.';
            }
            if (!$error) {
                $location->remember_address($selectedLocation['id'], $selectedLocation['lat'], $selectedLocation['lng']);
                $saved = $reportModel->submit($_SESSION['USER_ID'], $selectedLocation['id'], $form,
                    $occurred->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s'));
                if ($saved) {
                    $_SESSION['report_notice'] = 'Report submitted for administrator review.';
                    header('Location: ' . BASE_URL . '/users/user/submit_report.php', true, 303);
                    exit;
                }
                $error = 'The location is no longer active. Choose another location.';
            }
        }
    } else {
        $contact = $reportModel->account_contact($_SESSION['USER_ID']);
        $form['contact_phone'] = $contact['phone'] ?? '';
        $form['contact_email'] = $contact['email'] ?? '';
        $id = filter_input(INPUT_GET, 'location_id', FILTER_VALIDATE_INT);
        if ($id) {
            $selectedLocation = $location->get_location($id);
            if ($selectedLocation && $selectedLocation['active']) $form['location_id'] = $id;
            else $selectedLocation = null;
        }
    }
    $locations = $location->get_locations();
    if (!$selectedLocation && $form['location_id'] !== '') {
        foreach ($locations as $place) {
            if ((string) $place['id'] === (string) $form['location_id']) $selectedLocation = $place;
        }
    }
    $history = $reportModel->get_reports('all', $_SESSION['USER_ID']);
} catch (PDOException $exception) {
    error_log('SmartSlope report: ' . $exception->getMessage());
    http_response_code(503);
    $error = 'Report data is unavailable. Please try again later.';
}
require_once __DIR__ . '/../../includes/header.php';
?>
<main class="page-shell">
    <div class="page-heading"><div><span class="eyebrow">Community observations</span><h1>Ground reports</h1><p class="page-subtitle">Describe what you observed. Reports are user-submitted information, not verified warnings.</p></div></div>
    <?php if ($notice): ?><p class="alert alert-success" role="status"><?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <?php if ($error): ?><p class="alert alert-danger" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <div class="row g-4 mb-4">
        <div class="col-lg-6"><section class="card"><div class="card-header"><h2>Report location</h2></div><?php require __DIR__ . '/../../includes/location-map.php'; ?></section></div>
        <div class="col-lg-6"><section class="card"><div class="card-body">
            <form method="post" action="<?= BASE_URL ?>/users/user/submit_report.php">
                <input type="hidden" id="lat" name="lat" value="<?= htmlspecialchars($form['lat'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" id="lng" name="lng" value="<?= htmlspecialchars($form['lng'], ENT_QUOTES, 'UTF-8') ?>">
                <div class="form-floating mb-3"><select id="report-location" name="location_id" class="form-select"><option value="">Choose on the map or select a saved location</option>
                    <?php foreach ($locations as $place): ?><option value="<?= (int) $place['id'] ?>" <?= (string) $form['location_id'] === (string) $place['id'] ? 'selected' : '' ?>><?= htmlspecialchars($place['address_label'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?>
                </select><label for="report-location">Saved location</label></div>
                <div class="form-floating mb-3"><select id="report-type" name="report_type" class="form-select" required><?php foreach ($types as $value => $label): ?><option value="<?= $value ?>" <?= $form['report_type'] === $value ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select><label for="report-type">Observation type</label></div>
                <div class="form-floating mb-3"><textarea class="form-control" id="message" name="message" placeholder="Observation" style="height:130px" maxlength="5000" required><?= htmlspecialchars($form['message'], ENT_QUOTES, 'UTF-8') ?></textarea><label for="message">What did you observe?</label></div>
                <div class="form-floating mb-3"><input class="form-control" type="datetime-local" id="occurred-at" name="occurred_at" value="<?= htmlspecialchars($form['occurred_at'], ENT_QUOTES, 'UTF-8') ?>" required><label for="occurred-at">Observed at (Manila time)</label></div>
                <?php foreach (['house_landmark' => ['Nearby house / landmark (optional)', 255, 'text'], 'contact_phone' => ['Contact number', 20, 'tel'], 'contact_email' => ['Email', 254, 'email']] as $field => [$label, $length, $type]): ?>
                <div class="form-floating mb-3"><input class="form-control" id="<?= $field ?>" name="<?= $field ?>" type="<?= $type ?>" maxlength="<?= $length ?>" <?= $field !== 'house_landmark' ? 'required' : '' ?> placeholder="<?= $label ?>" value="<?= htmlspecialchars($form[$field], ENT_QUOTES, 'UTF-8') ?>"><label for="<?= $field ?>"><?= $label ?></label></div>
                <?php endforeach; ?>
                <p class="small text-muted">Contact details are visible to administrators. This page is not monitored as an emergency service.</p>
                <button id="submit-report" type="submit" class="btn btn-primary">Submit report</button>
            </form>
        </div></section></div>
    </div>
    <section class="card"><div class="card-header"><h2>Your reports</h2></div><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Location</th><th>Observation</th><th>Observed (UTC)</th><th>Status</th></tr></thead><tbody>
    <?php foreach ($history as $report): ?><tr><td><?= htmlspecialchars($report['name'], ENT_QUOTES, 'UTF-8') ?></td><td class="report-message"><?= nl2br(htmlspecialchars($report['message'] ?? '', ENT_QUOTES, 'UTF-8')) ?></td><td><?= htmlspecialchars($report['occurred_at'] ?? $report['created_at'], ENT_QUOTES, 'UTF-8') ?></td><td><?= ucfirst($report['status']) ?></td></tr><?php endforeach; ?>
    <?php if (!$history): ?><tr><td colspan="4" class="empty-cell">You have not submitted any reports yet.</td></tr><?php endif; ?>
    </tbody></table></div></section>
</main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
