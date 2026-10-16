<?php
require_once __DIR__ . '/db.php';

class location extends db {
    public function get_locations($active_only = true) {
        $sql = 'SELECT * FROM locations';
        if ($active_only) {
            $sql .= ' WHERE active = 1';
        }
        return $this->connect()->query($sql . ' ORDER BY name, id')->fetchAll();
    }

    public function get_location($id) {
        $stmt = $this->connect()->prepare('SELECT * FROM locations WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
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
        return $stmt->fetch();
    }

    public function save_point($lat, $lng) {
        // Reuse the unique coordinate pair if another request saved it first.
        $stmt = $this->connect()->prepare('INSERT INTO locations (name, lat, lng)
            VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)');
        $stmt->execute(['Irisan location ' . $lat . ', ' . $lng, $lat, $lng]);
        return $this->find_point($lat, $lng);
    }

    public function set_active($id, $active) {
        // Deactivate instead of deleting: the schema would also delete linked events.
        $stmt = $this->connect()->prepare('UPDATE locations SET active = ? WHERE id = ?');
        $stmt->execute([$active, $id]);
    }
}
