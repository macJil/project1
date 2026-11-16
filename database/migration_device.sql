-- SmartSlope IoT migration: allow device-sourced readings.
-- Run once on an existing installation (new installs: run schema.sql first, then this).
USE smartslope_mvp;

ALTER TABLE events
  MODIFY source ENUM('openmeteo','manual','device') DEFAULT 'openmeteo',
  ADD COLUMN tilt_deg DECIMAL(5,2) NULL AFTER soil_moisture_27_81cm,
  ADD COLUMN battery_v DECIMAL(4,2) NULL AFTER tilt_deg;
