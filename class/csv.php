<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../includes/address.php';

class csv_data extends db {
    public function columns($kind) {
        $columns = ['format', 'name', 'house_info', 'address', 'latitude', 'longitude'];
        return array_merge($columns, $kind === 'locations'
            ? ['susceptibility', 'active']
            : ['observed_at_utc', 'rainfall_1h', 'rainfall_24h', 'rainfall_72h', 'temperature', 'humidity', 'wind_speed', 'risk_status']);
    }

    public function download($kind) {
        $sql = $kind === 'locations' ? 'SELECT * FROM locations ORDER BY id'
            : "SELECT locations.name, locations.purok, locations.landmark, locations.lat, locations.lng,
                events.observed_at, events.rainfall_1h, events.rainfall_24h, events.rainfall_72h,
                events.temperature, events.humidity, events.wind_speed, events.risk_level
                FROM events JOIN locations ON locations.id = events.location_id
                WHERE events.type = 'reading' AND events.archived = 0 ORDER BY events.observed_at DESC, events.id DESC";
        $rows = $this->connect()->query($sql);
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="smartslope-' . $kind . '.csv"');
        header('Cache-Control: no-store');
        $out = fopen('php://output', 'w');
        fputcsv($out, $this->columns($kind), ',', '"', '');
        foreach ($rows as $row) {
            $values = ['smartslope-' . $kind . '-v1', $row['name'], $row['purok'], $row['landmark'], $row['lat'], $row['lng']];
            $fields = $kind === 'locations' ? ['susceptibility', 'active']
                : ['observed_at', 'rainfall_1h', 'rainfall_24h', 'rainfall_72h', 'temperature', 'humidity', 'wind_speed', 'risk_level'];
            foreach ($fields as $field) $values[] = $row[$field];
            // Prefix text cells so spreadsheet software treats them as text. Import removes this prefix.
            foreach ([1, 2, 3] as $index) $values[$index] = "'" . ($values[$index] ?? '');
            fputcsv($out, $values, ',', '"', '');
        }
        fclose($out);
    }

    public function import($kind, $file) {
        $handle = fopen($file, 'r');
        $columns = $this->columns($kind);
        $header = fgetcsv($handle, 0, ',', '"', '');
        if ($header) $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        if ($header !== $columns) {
            fclose($handle);
            throw new InvalidArgumentException('Use a SmartSlope ' . $kind . ' CSV download with its original columns.');
        }
        $rows = [];
        $line = 1;
        while (($values = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            $line++;
            if ($values === [null]) continue;
            if (count($values) !== count($columns) || $values[0] !== 'smartslope-' . $kind . '-v1') {
                fclose($handle);
                throw new InvalidArgumentException('Invalid CSV row ' . $line . '. Nothing was imported.');
            }
            foreach ([1, 2, 3] as $index) {
                if (str_starts_with($values[$index], "'")) $values[$index] = substr($values[$index], 1);
            }
            $row = array_combine($columns, $values);
            $valid = $row['name'] !== '' && $row['name'] === address_text($row['name'], 150)
                && $row['house_info'] === address_text($row['house_info'], 100)
                && $row['address'] === address_text($row['address'], 255)
                && is_numeric($row['latitude']) && is_numeric($row['longitude'])
                && inside_irisan(round((float) $row['latitude'], 6), round((float) $row['longitude'], 6));
            if ($kind === 'locations') {
                $valid = $valid && in_array($row['active'], ['0', '1'], true)
                    && in_array($row['susceptibility'], ['unknown','low','moderate','high','very_high','debris_flow'], true);
            } else {
                $date = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $row['observed_at_utc'], new DateTimeZone('UTC'));
                $valid = $valid && ($row['observed_at_utc'] === '' || ($date && $date->format('Y-m-d H:i:s') === $row['observed_at_utc']))
                    && in_array($row['risk_status'], ['low','normal','medium','high'], true);
                foreach (['rainfall_1h'=>[0,99999.99], 'rainfall_24h'=>[0,99999.99], 'rainfall_72h'=>[0,99999.99],
                    'temperature'=>[-999.99,999.99], 'humidity'=>[0,100], 'wind_speed'=>[0,9999.99]] as $field => $range) {
                    if ($row[$field] !== '') $valid = $valid && is_numeric($row[$field]) && $row[$field] >= $range[0] && $row[$field] <= $range[1];
                }
            }
            if (!$valid || count($rows) >= 2000) {
                fclose($handle);
                throw new InvalidArgumentException('Check row ' . $line . ': valid Irisan coordinates and field values are required (maximum 2000 rows). Nothing was imported.');
            }
            $rows[] = $row;
        }
        fclose($handle);
        if (!$rows) throw new InvalidArgumentException('The CSV contains no records.');
        $pdo = $this->connect();
        $added = $skipped = 0;
        $pdo->beginTransaction();
        try {
            foreach ($rows as $row) {
                $lat = round((float) $row['latitude'], 6); $lng = round((float) $row['longitude'], 6);
                $find = $pdo->prepare('SELECT id, active FROM locations WHERE lat = ? AND lng = ?');
                $find->execute([$lat, $lng]); $place = $find->fetch();
                if ($kind === 'locations' && $place) { $skipped++; continue; }
                if ($kind === 'readings' && $place && !$place['active']) { $skipped++; continue; }
                if (!$place) {
                    $insert = $pdo->prepare('INSERT INTO locations (name,purok,landmark,lat,lng,susceptibility,active) VALUES (?,?,?,?,?,?,?)');
                    $insert->execute([$row['name'],$row['house_info'],$row['address'],$lat,$lng,$row['susceptibility'] ?? 'unknown',$row['active'] ?? 1]);
                    $place = ['id'=>$pdo->lastInsertId()];
                }
                if ($kind === 'locations') { $added++; continue; }
                $find = $pdo->prepare("SELECT id FROM events WHERE location_id = ? AND type = 'reading' AND observed_at <=> ?");
                $find->execute([$place['id'],$row['observed_at_utc'] === '' ? null : $row['observed_at_utc']]);
                if ($find->fetch()) { $skipped++; continue; }
                $insert = $pdo->prepare("INSERT INTO events (location_id,type,observed_at,rainfall_1h,rainfall_24h,rainfall_72h,temperature,humidity,wind_speed,risk_level,source,adjustment_log)
                    VALUES (?,'reading',?,?,?,?,?,?,?,?,'manual',?)");
                $insert->execute([$place['id'],$row['observed_at_utc'] === '' ? null : $row['observed_at_utc'],$row['rainfall_1h'] === '' ? null : $row['rainfall_1h'],
                    $row['rainfall_24h'] === '' ? null : $row['rainfall_24h'],$row['rainfall_72h'] === '' ? null : $row['rainfall_72h'],
                    $row['temperature'] === '' ? null : $row['temperature'],$row['humidity'] === '' ? null : $row['humidity'],
                    $row['wind_speed'] === '' ? null : $row['wind_speed'],$row['risk_status'],
                    json_encode(['imported_by'=>$_SESSION['USER_ID'], 'imported_at_utc'=>gmdate('Y-m-d H:i:s')])]);
                $added++;
            }
            $pdo->commit();
        } catch (Throwable $error) {
            $pdo->rollBack();
            throw $error;
        }
        return "$added imported; $skipped skipped (existing records or inactive locations).";
    }
}
