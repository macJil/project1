-- SmartSlope sample data
-- Import database/schema.sql first, then run this file once.
-- Sample resident password for both accounts: user123

USE smartslope_mvp;

-- Add two resident accounts. INSERT IGNORE makes this section safe to re-run.
INSERT IGNORE INTO users (full_name, username, email, phone, password, role) VALUES
    ('Maria Santos', 'maria.santos', 'maria.santos@example.test', '+639171234567', '$2y$12$w4CIqsaWXpT6pu/l1Q4VteZE1xfz/pcKxLCiOaT4vJuBfKTHuHf8G', 'user'),
    ('Jose Reyes', 'jose.reyes', 'jose.reyes@example.test', '+639189876543', '$2y$12$w4CIqsaWXpT6pu/l1Q4VteZE1xfz/pcKxLCiOaT4vJuBfKTHuHf8G', 'user');

-- Add two Irisan locations. Latitude/longitude pairs are unique.
INSERT IGNORE INTO locations (name, purok, landmark, lat, lng, susceptibility, active) VALUES
    ('Irisan Creek crossing', 'Purok 3', 'Near the creek footbridge', 16.420650, 120.558900, 'high', 1),
    ('Irisan hillside homes', 'Purok 5', 'Above the covered court', 16.422100, 120.560300, 'very_high', 1);

-- Five example events: three weather readings and two resident reports.
-- The SELECT statements resolve IDs by stable names instead of assuming auto-increment values.
INSERT INTO events (
    location_id, type, rainfall_1h, rainfall_24h, rainfall_72h,
    rainfall_forecast_24h, precipitation_probability_24h,
    soil_moisture_9_27cm, soil_moisture_27_81cm, risk_level,
    temperature, humidity, wind_speed, weather_code, observed_at, source
)
SELECT id, 'reading', 2.40, 18.60, 46.20, 12.30, 75, 0.2840, 0.3010,
       'normal', 19.80, 88.00, 5.70, 61, '2026-10-15 02:00:00', 'openmeteo'
FROM locations WHERE name = 'Irisan pilot point'
LIMIT 1;

INSERT INTO events (
    location_id, type, rainfall_1h, rainfall_24h, rainfall_72h,
    rainfall_forecast_24h, precipitation_probability_24h,
    soil_moisture_9_27cm, soil_moisture_27_81cm, risk_level,
    temperature, humidity, wind_speed, weather_code, observed_at, source
)
SELECT id, 'reading', 8.10, 52.40, 118.70, 34.80, 90, 0.3860, 0.4120,
       'medium', 18.20, 94.00, 8.40, 65, '2026-10-16 04:00:00', 'openmeteo'
FROM locations WHERE name = 'Irisan Creek crossing'
LIMIT 1;

INSERT INTO events (
    location_id, type, rainfall_1h, rainfall_24h, rainfall_72h,
    rainfall_forecast_24h, precipitation_probability_24h,
    soil_moisture_9_27cm, soil_moisture_27_81cm, risk_level,
    temperature, humidity, wind_speed, weather_code, observed_at, source
)
SELECT id, 'reading', 14.70, 89.30, 161.50, 48.60, 95, 0.4480, 0.4710,
       'high', 17.50, 96.00, 11.20, 65, '2026-10-17 03:30:00', 'openmeteo'
FROM locations WHERE name = 'Irisan hillside homes'
LIMIT 1;

INSERT INTO events (
    location_id, user_id, type, message, contact_phone, contact_email,
    house_landmark, status, report_type, occurred_at, source
)
SELECT l.id, u.id, 'report', 'Small soil movement noticed beside the drainage channel.',
       u.phone, u.email, 'Beside the creek footbridge', 'pending', 'soil_movement',
       '2026-10-17 06:15:00', 'manual'
FROM locations AS l
JOIN users AS u ON u.username = 'maria.santos'
WHERE l.name = 'Irisan Creek crossing'
LIMIT 1;

INSERT INTO events (
    location_id, user_id, type, message, contact_phone, contact_email,
    house_landmark, status, report_type, occurred_at, reviewed_by, reviewed_at, source
)
SELECT l.id, u.id, 'report', 'A shallow crack was observed along the roadside slope.',
       u.phone, u.email, 'Above the covered court', 'reviewed', 'ground_crack',
       '2026-10-16 09:40:00', a.id, '2026-10-16 11:00:00', 'manual'
FROM locations AS l
JOIN users AS u ON u.username = 'jose.reyes'
JOIN users AS a ON a.username = 'admin'
WHERE l.name = 'Irisan hillside homes'
LIMIT 1;
