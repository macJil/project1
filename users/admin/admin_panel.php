<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../class/locations.php';
require_once __DIR__ . '/../../includes/map-boundary.php';
if (empty($_SESSION['USER_ID'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}
if (($_SESSION['ROLE'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Access denied.');
}
$pageTitle = 'Location management';
$activePage = 'admin';
$showMap = true;
$mapMode = 'edit';
$location = new location();
$locations = [];
$form = ['id' => 0, 'name' => '', 'purok' => '', 'landmark' => '', 'lat' => '', 'lng' => ''];
$error = '';
$notice = $_SESSION['location_notice'] ?? '';
unset($_SESSION['location_notice']);
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = filter_input(INPUT_POST, 'action');
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if ($id === false || $id === null || $id < 0 || ($id && !$location->get_location($id))) {
            $error = 'Location not found.';
        } elseif ($action === 'save') {
            $form['id'] = $id;
            foreach (['name', 'purok', 'landmark', 'lat', 'lng'] as $field) {
                $form[$field] = trim((string) filter_input(INPUT_POST, $field));
            }
            $lat = filter_var($form['lat'], FILTER_VALIDATE_FLOAT);
            $lng = filter_var($form['lng'], FILTER_VALIDATE_FLOAT);
            if ($form['name'] === '' || strlen($form['name']) > 150 || strlen($form['purok']) > 100 || strlen($form['landmark']) > 255) {
                $error = 'Enter a location name and keep each field within its length limit.';
            } elseif ($lat === false || $lng === false || !inside_irisan(round($lat, 6), round($lng, 6))) {
                $error = 'Choose valid coordinates inside the Irisan boundary.';
            } else {
                $location->save_location($id, $form['name'], $form['purok'], $form['landmark'], round($lat, 6), round($lng, 6));
                $_SESSION['location_notice'] = 'Location saved.';
            }
        } elseif (in_array($action, ['deactivate', 'activate'], true) && $id > 0) {
            $location->set_active($id, $action === 'activate' ? 1 : 0);
            $_SESSION['location_notice'] = $action === 'activate' ? 'Location activated.' : 'Location deactivated. Its readings and reports were kept.';
        } else {
            $error = 'Choose a valid location action.';
        }
        if (!$error) {
            header('Location: ' . BASE_URL . '/users/admin/admin_panel.php', true, 303);
            exit;
        }
    } else {
        $editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
        if ($editId) {
            $saved = $location->get_location($editId);
            if ($saved) $form = $saved;
            else $error = 'Location not found.';
        }
    }
    $locations = $location->get_locations(false);
} catch (PDOException $exception) {
    error_log('SmartSlope locations: ' . $exception->getMessage());
    if (($exception->errorInfo[1] ?? 0) == 1062) {
        $error = 'These coordinates already belong to another location. Edit or reactivate that location instead.';
        $locations = $location->get_locations(false);
    } else {
        http_response_code(503);
        $error = 'Location data is unavailable. Check your database connection and try again.';
    }
}
require_once __DIR__ . '/../../includes/header.php';
?>
<main class="page-shell">
    <div class="page-heading"><div><span class="eyebrow">SmartSlope · Irisan</span><h1>Location management</h1><p class="page-subtitle">Add named places inside Irisan. Coordinates can be selected on the local map.</p></div></div>
    <?php if ($notice): ?><p class="alert alert-success" role="status"><?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <?php if ($error): ?><p class="alert alert-danger" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <div class="row g-4 mb-4">
        <div class="col-lg-7"><section class="card"><div class="card-header"><h2>Irisan location picker</h2></div><?php require __DIR__ . '/../../includes/location-map.php'; ?></section></div>
        <div class="col-lg-5"><section class="card"><div class="card-header"><h2><?= $form['id'] ? 'Edit location' : 'Add location' ?></h2></div><div class="card-body">
            <form method="post" action="<?= BASE_URL ?>/users/admin/admin_panel.php">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= (int) $form['id'] ?>">
                <?php foreach (['name' => 'Location name', 'purok' => 'Purok (optional)', 'landmark' => 'Street / landmark (optional)'] as $field => $label): ?>
                <div class="form-floating mb-3"><input class="form-control" id="<?= $field ?>" name="<?= $field ?>" placeholder="<?= $label ?>" maxlength="<?= $field === 'name' ? 150 : ($field === 'purok' ? 100 : 255) ?>" value="<?= htmlspecialchars($form[$field] ?? '', ENT_QUOTES, 'UTF-8') ?>" <?= $field === 'name' ? 'required' : '' ?>><label for="<?= $field ?>"><?= $label ?></label></div>
                <?php endforeach; ?>
                <div class="row g-2">
                <?php foreach (['lat' => 'Latitude', 'lng' => 'Longitude'] as $field => $label): ?>
                <div class="col-sm-6"><div class="form-floating mb-3"><input class="form-control" type="number" step="0.000001" id="<?= $field ?>" name="<?= $field ?>" placeholder="<?= $label ?>" required value="<?= htmlspecialchars($form[$field] ?? '', ENT_QUOTES, 'UTF-8') ?>"><label for="<?= $field ?>"><?= $label ?></label></div></div>
                <?php endforeach; ?>
                </div>
                <p class="small text-muted">Names and landmarks are entered by the administrator. Offline map clicks do not look up street or house addresses. New locations keep susceptibility as unknown.</p>
                <div class="location-actions"><button class="btn btn-primary" type="submit">Save location</button><a class="btn btn-outline-secondary" href="<?= BASE_URL ?>/users/admin/admin_panel.php">Clear / cancel</a></div>
            </form>
        </div></section></div>
    </div>
    <section class="card"><div class="card-header"><h2>All locations</h2></div><div class="table-responsive"><table class="table align-middle">
        <thead><tr><th>Name</th><th>Purok / landmark</th><th>Coordinates</th><th>Status</th><th>Actions</th></tr></thead><tbody>
        <?php foreach ($locations as $place): ?><tr>
            <td><?= htmlspecialchars($place['name'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars(implode(' · ', array_filter([$place['purok'], $place['landmark']])), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars(($place['lat'] ?? '—') . ', ' . ($place['lng'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= $place['active'] ? 'Active' : 'Inactive' ?></td>
            <td><div class="location-actions"><a class="btn btn-sm btn-outline-primary" href="?edit=<?= (int) $place['id'] ?>">Edit</a>
                <form method="post" action="<?= BASE_URL ?>/users/admin/admin_panel.php"><input type="hidden" name="id" value="<?= (int) $place['id'] ?>"><input type="hidden" name="action" value="<?= $place['active'] ? 'deactivate' : 'activate' ?>"><button class="btn btn-sm btn-outline-secondary" type="submit"><?= $place['active'] ? 'Deactivate' : 'Activate' ?></button></form>
            </div></td>
        </tr><?php endforeach; ?>
        <?php if (!$locations): ?><tr><td colspan="5" class="empty-cell">No locations available.</td></tr><?php endif; ?>
        </tbody></table></div>
        <div class="card-body"><p class="small mb-0">Deactivating hides a place from dashboard selection and keeps linked readings and reports. Report review, reading changes, and CSV tools arrive in later phases.</p></div>
    </section>
</main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
