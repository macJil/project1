<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../includes/address.php';

class location extends db {
    public function get_locations($active_only = true) {
        // Markers use the latest unarchived saved reading, including admin overrides.
        $sql = "SELECT locations.*, (SELECT risk_level FROM events
            WHERE location_id = locations.id AND type = 'reading' AND archived = 0
            ORDER BY observed_at DESC, id DESC LIMIT 1) AS risk_level FROM locations";
        if ($active_only) {
            $sql .= ' WHERE active = 1';
        }
        $rows = $this->connect()->query($sql . ' ORDER BY name, id')->fetchAll();
        foreach ($rows as &$row) $row['address_label'] = location_label($row);
        return $rows;
    }

    public function get_location($id) {
        $stmt = $this->connect()->prepare('SELECT * FROM locations WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) $row['address_label'] = location_label($row);
        return $row;
    }

    public function save_location($id, $name, $purok, $landmark, $lat, $lng) {
        if ($id) {
            $stmt = $this->connect()->prepare('UPDATE locations SET name = ?, purok = ?, landmark = ?, lat = ?, lng = ? WHERE id = ?');
            $stmt->execute([$name, $purok, $landmark, $lat, $lng, $id]);
        } else {
            $stmt = $this->connect()->prepare('INSERT INTO locations (name, purok, landmark, lat, lng) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$name, $purok, $landmark, $lat, $lng]);
        }
    }

    public function find_point($lat, $lng) {
        $stmt = $this->connect()->prepare('SELECT * FROM locations WHERE lat = ? AND lng = ?');
        $stmt->execute([$lat, $lng]);
        $row = $stmt->fetch();
        if ($row) $row['address_label'] = location_label($row);
        return $row;
    }

    public function save_point($lat, $lng) {
        // Reuse the unique coordinate pair if another request saved it first.
        $stmt = $this->connect()->prepare('INSERT INTO locations (name, lat, lng)
            VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)');
        $cached = $_SESSION['map_addresses'][address_key($lat, $lng)] ?? null;
        $stmt->execute([$cached['name'] ?? ('Irisan location ' . $lat . ', ' . $lng), $lat, $lng]);
        $place = $this->find_point($lat, $lng);
        $this->remember_address($place['id'], $lat, $lng);
        return $this->find_point($lat, $lng);
    }

    public function remember_address($id, $lat, $lng) {
        $cached = $_SESSION['map_addresses'][address_key($lat, $lng)] ?? null;
        if (!$cached) return;
        // Do not replace a manually entered landmark or a moved location's address.
        $stmt = $this->connect()->prepare("UPDATE locations SET landmark = ?
            WHERE id = ? AND lat = ? AND lng = ? AND (landmark IS NULL OR landmark = '')");
        $stmt->execute([$cached['address'], $id, $lat, $lng]);
    }

    public function set_active($id, $active) {
        // Deactivate instead of deleting: the schema would also delete linked events.
        $stmt = $this->connect()->prepare('UPDATE locations SET active = ? WHERE id = ?');
        $stmt->execute([$active, $id]);
    }
}
