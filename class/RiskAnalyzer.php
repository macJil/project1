<?php
// These are the original SmartSlope prototype thresholds, not validated warnings.
class RiskAnalyzer {
    const VERSION = 'prototype-1';
    const MAX_AGE = 10800; // Three hours, measured from the rainfall window end.
    const THRESHOLDS = [
        'high' => [50, 100, 150],
        'medium' => [25, 50, 100],
        'normal' => [10, 25, 50]
    ];

    public function analyze($one_hour, $day, $three_days) {
        $totals = [$one_hour, $day, $three_days];
        foreach ($totals as $value) {
            if (!is_numeric($value) || !is_finite((float) $value) || $value < 0 || $value > 99999.99) return null;
        }
        if ($one_hour > $day || $day > $three_days) return null;
        foreach (self::THRESHOLDS as $category => $limits) {
            for ($i = 0; $i < 3; $i++) {
                if ($totals[$i] >= $limits[$i]) return $category;
            }
        }
        return 'low';
    }

    public function assess($reading, $now = null) {
        $result = ['status' => 'unavailable', 'category' => null, 'expires_at' => null];
        if (!$reading) return $result;
        $result['status'] = 'incomplete';
        $category = $this->analyze($reading['rainfall_1h'], $reading['rainfall_24h'], $reading['rainfall_72h']);
        $observed = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $reading['observed_at'] ?? '', new DateTimeZone('UTC'));
        $end = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $reading['rainfall_window_end'] ?? '', new DateTimeZone('UTC'));
        $now = $now ?? time();
        if (!$category || !$observed || !$end
            || $observed->format('Y-m-d H:i:s') !== $reading['observed_at']
            || $end->format('Y-m-d H:i:s') !== $reading['rainfall_window_end']
            || $observed->getTimestamp() > $now + 300
            || $end->getTimestamp() !== intdiv($observed->getTimestamp(), 3600) * 3600
            || ($reading['rule_version'] ?? '') !== self::VERSION) return $result;
        $result['category'] = $category;
        $result['expires_at'] = $end->getTimestamp() + self::MAX_AGE;
        $result['status'] = $now >= $result['expires_at'] ? 'outdated' : 'current';
        return $result;
    }
}
