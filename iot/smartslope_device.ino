// SmartSlope ESP32 prototype — reads soil moisture + tilt, POSTs to api/device.php
// Board: ESP32 DevKit. Libraries: MPU6050_light (Library Manager).
// Wiring: soil probe A -> GPIO34, soil probe B -> GPIO35,
//         battery divider -> GPIO32, MPU6050 -> SDA 21 / SCL 22.

#include <WiFi.h>
#include <HTTPClient.h>
#include <Wire.h>
#include <MPU6050_light.h>

const char* WIFI_SSID = "YOUR_WIFI";
const char* WIFI_PASS = "YOUR_PASS";
const char* ENDPOINT  = "http://YOUR_HOST/project1/api/device.php";
const char* API_KEY   = "CHANGE_THIS_DEVICE_KEY";   // must match api/device.php
const int   LOCATION_ID = 1;                        // Irisan pilot point
const unsigned long POST_INTERVAL_MS = 15UL * 60UL * 1000UL; // 15 minutes

MPU6050 mpu(Wire);

float readSoil(int pin) {
  // Capacitive sensor: calibrate the raw range per unit before field use!
  int raw = analogRead(pin); // 0..4095 (12-bit ADC)
  float moisture = 1.0 - (raw - 1500.0) / 1500.0;
  return constrain(moisture, 0.0, 1.0); // 0 = dry, 1 = saturated
}

void connectWifi() {
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASS);
  unsigned long start = millis();
  while (WiFi.status() != WL_CONNECTED && millis() - start < 20000) {
    delay(500);
    Serial.print('.');
  }
  Serial.println(WiFi.status() == WL_CONNECTED ? " WiFi OK" : " WiFi FAILED");
}

void setup() {
  Serial.begin(115200);
  analogReadResolution(12);
  Wire.begin();
  mpu.begin();
  mpu.calcGyroOffsets(); // keep the board still during boot
  connectWifi();
}

void loop() {
  mpu.update();
  static unsigned long last = 0;
  if (millis() - last < POST_INTERVAL_MS) return;
  last = millis();

  float soilA = readSoil(34); // shallow probe (9-27 cm)
  float soilB = readSoil(35); // deep probe (27-81 cm)
  float tilt  = fabs(mpu.getAngleX());
  float battery = analogRead(32) * (3.3f / 4095.0f) * 2.0f; // 1:1 divider

  if (WiFi.status() != WL_CONNECTED) connectWifi();

  HTTPClient http;
  http.begin(ENDPOINT);
  http.addHeader("Content-Type", "application/json");
  http.setTimeout(10000);
  String body = String("{\"api_key\":\"") + API_KEY + "\",\"location_id\":" + LOCATION_ID +
                ",\"soil_9_27\":"  + String(soilA, 3) +
                ",\"soil_27_81\":" + String(soilB, 3) +
                ",\"tilt_deg\":"   + String(tilt, 2) +
                ",\"battery_v\":"  + String(battery, 2) + "}";
  int code = http.POST(body);
  Serial.printf("POST %u -> %d\n", (unsigned)(millis() / 1000), code);
  Serial.println(body);
  http.end();

  // Deep sleep between readings to save the solar battery.
  esp_sleep_enable_timer_wakeup((uint64_t) POST_INTERVAL_MS * 1000ULL);
  esp_deep_sleep_start();
}
