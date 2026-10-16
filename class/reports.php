<?php
require_once __DIR__ . '/db.php';

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
            events.reviewed_at, locations.name, users.full_name AS reporter
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
        return $stmt->fetchAll();
    }

    public function submit($user_id, $location_id, $form, $occurred_at) {
        $stmt = $this->connect()->prepare("INSERT INTO events
            (location_id, user_id, type, message, report_type, occurred_at, contact_phone, contact_email, house_landmark, status)
            SELECT id, ?, 'report', ?, ?, ?, ?, ?, ?, 'pending' FROM locations WHERE id = ? AND active = 1");
        $stmt->execute([$user_id, $form['message'], $form['report_type'], $occurred_at,
            $form['contact_phone'], $form['contact_email'], $form['house_landmark'], $location_id]);
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
