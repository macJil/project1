<?php if (!isset($bulkKind) || ($_SESSION['ROLE'] ?? '') !== 'admin') { http_response_code(404); exit; } ?>
<form id="bulk-<?= $bulkKind ?>" class="p-3 d-flex flex-wrap gap-2 align-items-center" method="post" action="<?= BASE_URL ?>/users/admin/bulk.php" onsubmit="return confirm('Apply this action to the selected rows? Deletion is permanent.');">
    <input type="hidden" name="kind" value="<?= $bulkKind ?>">
    <?php if ($bulkKind === 'locations'): ?>
    <select class="form-select w-auto" name="action" aria-label="Selected locations action"><option value="deactivate">Deactivate selected</option><option value="activate">Activate selected</option><option value="delete">Delete selected (unlinked only)</option></select>
    <button class="btn btn-outline-danger">Apply to selected</button>
    <?php else: ?><input type="hidden" name="action" value="delete"><button class="btn btn-outline-danger">Delete selected</button><?php endif; ?>
    <span class="small text-muted">Select up to 500 rows.</span>
</form>
