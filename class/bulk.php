<?php
require_once __DIR__ . '/db.php';

class bulk_records extends db {
    public function apply($kind, $action, $ids) {
        $marks = implode(',', array_fill(0, count($ids), '?'));
        if ($kind === 'locations') {
            $sql = $action === 'delete'
                ? "DELETE FROM locations WHERE id IN ($marks) AND NOT EXISTS (SELECT 1 FROM events WHERE events.location_id = locations.id)"
                : 'UPDATE locations SET active = ' . ($action === 'activate' ? '1' : '0') . " WHERE id IN ($marks)";
        } else {
            $sql = "DELETE FROM events WHERE id IN ($marks) AND type = ?";
            $ids[] = $kind === 'readings' ? 'reading' : 'report';
        }
        $stmt = $this->connect()->prepare($sql); $stmt->execute($ids);
        return $stmt->rowCount();
    }
}
