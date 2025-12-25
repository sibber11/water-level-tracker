#define SOUND_SPEED 0.034

#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>

// Distance sensor variables
const int trigPin = 32;
const int echoPin = 35;

long duration;
float distanceCm;

const char* ssid = "TP-Link_C9D0";
const char* password = "14647383";

//Your Domain name with URL path or IP address with path
const char* serverName = "http://192.168.0.189:8080/receive-data.php";

// the following variables are unsigned longs because the time, measured in
// milliseconds, will quickly become a bigger number than can be stored in an int.
unsigned long lastTime = 0;
// Timer set to 10 minutes (600000)
//unsigned long timerDelay = 600000;
// Set timer to 5 seconds (5000)
unsigned long timerDelay = 5000;

void setup() {
  Serial.begin(115200);

  // Initialize distance sensor pins
  pinMode(trigPin, OUTPUT);
  pinMode(echoPin, INPUT);

  // Connect to WiFi
  WiFi.begin(ssid, password);
  Serial.println("Connecting");
  while(WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println("");
  Serial.print("Connected to WiFi network with IP Address: ");
  Serial.println(WiFi.localIP());

  Serial.println("Timer set to 5 seconds (timerDelay variable), it will take 5 seconds before publishing the first reading.");
}

void loop() {
  // Read distance sensor continuously
  digitalWrite(trigPin, LOW);
  delayMicroseconds(2);
  digitalWrite(trigPin, HIGH);
  delayMicroseconds(10);
  digitalWrite(trigPin, LOW);
  duration = pulseIn(echoPin, HIGH);
  distanceCm = duration * SOUND_SPEED / 2;

  Serial.print("Distance: ");
  Serial.println(distanceCm);

  // Send HTTP POST request at specified intervals
  if ((millis() - lastTime) > timerDelay) {
    //Check WiFi connection status
    if(WiFi.status()== WL_CONNECTED){
      WiFiClient client;
      HTTPClient http;

      // Your Domain name with URL path or IP address with path
      http.begin(client, serverName);

      // If you need Node-RED/server authentication, insert user and password below
      //http.setAuthorization("REPLACE_WITH_SERVER_USERNAME", "REPLACE_WITH_SERVER_PASSWORD");

      // Specify content-type header
      http.addHeader("Content-Type", "application/x-www-form-urlencoded");
      // Data to send with HTTP POST - include the actual distance measurement
      String httpRequestData = "distance=" + String(distanceCm);
      // Send HTTP POST request
      int httpResponseCode = http.POST(httpRequestData);

      Serial.print("HTTP Response code: ");
      Serial.println(httpResponseCode);

      // Parse JSON response to get delay
      if (httpResponseCode > 0) {
        String payload = http.getString();
        Serial.println("Response payload:");
        Serial.println(payload);

        // Parse JSON
        StaticJsonDocument<200> doc;
        DeserializationError error = deserializeJson(doc, payload);

        if (!error) {
          if (doc.containsKey("delay")) {
            timerDelay = doc["delay"];
            Serial.print("Updated timerDelay to: ");
            Serial.println(timerDelay);
          }
          if (doc.containsKey("message")) {
            Serial.println(doc["message"].as<String>());
          }
        } else {
          Serial.print("deserializeJson() failed: ");
          Serial.println(error.c_str());
        }
      }

      // Free resources
      http.end();
    }
    else {
      Serial.println("WiFi Disconnected");
    }
    lastTime = millis();
  }

  // Small delay between distance readings
  delay(1000);
}