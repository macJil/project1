<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../includes/address.php';

class reports extends db {
    public function account_contact($user_id) {
        $stmt = $this->connect()->prepare('SELECT phone, email FROM users WHERE id = ?');
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    }

    public function get_reports($status = 'all', $user_id = null) {
        $sql = "SELECT events.id, events.location_id, events.user_id, events.message,
            events.contact_phone, events.contact_email, events.house_landmark,
            events.report_type, events.occurred_at, events.created_at, events.status,
            events.reviewed_at, events.provider_payload,
            (SELECT risk_level FROM events AS reading WHERE reading.location_id = locations.id
                AND reading.type = 'reading' AND reading.archived = 0
                ORDER BY reading.observed_at DESC, reading.id DESC LIMIT 1) AS risk_level, locations.name, locations.purok, locations.landmark, locations.lat, locations.lng, users.full_name AS reporter
            FROM events JOIN locations ON locations.id = events.location_id
            LEFT JOIN users ON users.id = events.user_id WHERE events.type = 'report'";
        $params = [];
        if ($status !== 'all') {
            $sql .= ' AND events.status = ?';
            $params[] = $status;
        }
        if ($user_id !== null) {
            $sql .= ' AND events.user_id = ?';
            $params[] = $user_id;
        }
        $stmt = $this->connect()->prepare($sql . ' ORDER BY events.created_at DESC, events.id DESC');
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['name'] = location_label($row);
            $snapshot = json_decode($row['provider_payload'] ?? '', true)['report_location'] ?? null;
            $row['map_note'] = 'Linked location (older report)';
            if (is_array($snapshot) && isset($snapshot['lat'], $snapshot['lng'], $snapshot['address'])) {
                $row['lat'] = $snapshot['lat'];
                $row['lng'] = $snapshot['lng'];
                $row['name'] = $snapshot['address'];
                $row['map_note'] = 'Location saved when this report was submitted';
            }
            unset($row['provider_payload']);
        }
        return $rows;
    }

    public function submit($user_id, $location_id, $form, $occurred_at) {
        $query = $this->connect()->prepare('SELECT * FROM locations WHERE id = ? AND active = 1');
        $query->execute([$location_id]);
        $place = $query->fetch();
        if (!$place) return false;
        $snapshot = json_encode(['report_location' => ['lat' => $place['lat'], 'lng' => $place['lng'], 'address' => location_label($place)]]);
        $stmt = $this->connect()->prepare("INSERT INTO events
            (location_id, user_id, type, message, report_type, occurred_at, contact_phone, contact_email, house_landmark, status, provider_payload)
            SELECT id, ?, 'report', ?, ?, ?, ?, ?, ?, 'pending', ? FROM locations WHERE id = ? AND active = 1");
        $stmt->execute([$user_id, $form['message'], $form['report_type'], $occurred_at,
            $form['contact_phone'], $form['contact_email'], $form['house_landmark'], $snapshot, $location_id]);
        return $stmt->rowCount() === 1;
    }

    public function find($id) {
        $stmt = $this->connect()->prepare("SELECT id FROM events WHERE id = ? AND type = 'report'");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function set_status($id, $status, $admin_id) {
        $stmt = $this->connect()->prepare("UPDATE events SET status = ?, reviewed_by = ?, reviewed_at = ?
            WHERE id = ? AND type = 'report'");
        $stmt->execute([$status, $status === 'pending' ? null : $admin_id,
            $status === 'pending' ? null : gmdate('Y-m-d H:i:s'), $id]);
    }

    public function delete($id) {
        $stmt = $this->connect()->prepare("DELETE FROM events WHERE id = ? AND type = 'report'");
        $stmt->execute([$id]);
        return $stmt->rowCount() === 1;
    }
}
