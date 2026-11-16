# SmartSlope IoT Prototype (ESP32)

Bench prototype that sends real sensor readings into the existing SmartSlope web app.
Full chain: **sensor -> api/device.php -> events table -> existing dashboard/map**.

## Files
- `smartslope_device.ino` — ESP32 firmware (soil moisture x2, tilt, battery).
- `../api/device.php` — device endpoint (shared-key auth, server-side risk screen).
- `../class/device_readings.php` — DB class following the repo's `db` pattern.
- `../database/migration_device.sql` — adds `source='device'`, `tilt_deg`, `battery_v`.

## Install (XAMPP)
1. Run `database/migration_device.sql` once in phpMyAdmin (after `schema.sql`).
2. Copy `api/device.php` and `class/device_readings.php` into the repo.
3. Set `DEVICE_API_KEY` in `api/device.php` and `API_KEY` in the firmware to the same value.
4. Point `ENDPOINT` in the firmware at your host, e.g. `http://192.168.1.x/project1/api/device.php`.

## Wiring
| Device pin | Connects to |
|---|---|
| GPIO34 | Capacitive soil probe A (shallow, 9-27 cm) |
| GPIO35 | Capacitive soil probe B (deep, 27-81 cm) |
| GPIO32 | Battery voltage divider (1:1 resistors) |
| 21 / 22 | MPU6050 SDA / SCL |
| 5V/GND | Sensors + MPU6050 |

## Calibrate
1. Upload the sketch, open Serial Monitor (115200).
2. Note raw values with the probe **dry in air**, then **in a cup of water**.
3. Adjust the 1500.0 offsets in `readSoil()` to map dry->0.0 and wet->1.0.

## Test without hardware (curl)
```bash
curl -X POST http://localhost/project1/api/device.php \
  -H "Content-Type: application/json" \
  -d '{"api_key":"CHANGE_THIS_DEVICE_KEY","location_id":1,"soil_9_27":0.85,"soil_27_81":0.62,"tilt_deg":1.2,"battery_v":3.9}'
```
Expected: `{"ok":true,"event_id":...,"risk_level":"high"}` (soil >= 0.8 -> high).
The reading then appears on the dashboard/map for that location.

## Bench demo for the defense
1. Dry soil -> green point on the map.
2. Pour water into the cup -> next POST flips the point yellow/red.
3. Tilt the board > 3 degrees -> medium; > 5 degrees -> high.

## Notes / limits
- Prototype only: shared-key auth, simple threshold risk screen, no OTA updates.
- The `events` table stays unchanged apart from the migration; the web app's
  RiskAnalyzer (rainfall rules) is untouched and can merge device + weather data later.
