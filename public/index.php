<?php

session_start();

// Simple routing system
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);

// Remove leading slash and project directory if needed
$path = ltrim($path, '/');
if (strpos($path, 'php_fcm/') === 0) {
    $path = substr($path, 8);
}

// Route handling
if ($path === '' || $path === 'index.php') {
    // Main web interface
    handleWebInterface();
} elseif ($path === 'api/receive-data') {
    // Redirect to receive-data.php
    require __DIR__ . './../receive-data.php';
    exit;
} elseif ($path === 'api/get-distance') {
    // Redirect to get-distance.php
    require __DIR__ . './../get-distance.php';
    exit;
} elseif ($path === 'api/fast-mode') {
    // Redirect to fast-mode-control.php
    require __DIR__ . './../fast-mode-control.php';
    exit;
} elseif ($path === 'api/subscribe') {
    // Redirect to subscribe.php
    require __DIR__ . './../subscribe.php';
    exit;
} elseif ($path === 'api/delay-config') {
    // Redirect to delay-config.php
    require __DIR__ . './../delay-config.php';
    exit;
} else {
    // 404 Not Found
    http_response_code(404);
    echo json_encode(['error' => 'Endpoint not found', 'code' => 404]);
}

function handleWebInterface() {
    // Check if this is an API request (expects JSON)
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    if (strpos($accept, 'application/json') !== false) {
        echo json_encode([
            'message' => 'Distance Tracker API',
            'endpoints' => [
                'GET /api/get-distance' => 'Get current distance data and system status',
                'POST /api/receive-data' => 'Receive distance data and send notifications',
                'GET /api/fast-mode' => 'Check fast mode status',
                'POST /api/fast-mode' => 'Control fast mode (action=start|stop)',
                'GET /api/subscribe?token=TOKEN' => 'Subscribe device to notifications',
                'GET /api/delay-config' => 'Get current delay configuration',
                'POST /api/delay-config' => 'Set delay configuration (action=set|reset)'
            ]
        ]);
        return;
    }

    // Web interface HTML
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Distance Tracker</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
            .section { margin: 20px 0; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
            .form-group { margin: 10px 0; }
            label { display: block; margin-bottom: 5px; }
            input, button { padding: 8px; margin: 5px 0; }
            button { background: #007bff; color: white; border: none; cursor: pointer; }
            button:hover { background: #0056b3; }
            .status { padding: 10px; margin: 10px 0; border-radius: 3px; }
            .success { background: #d4edda; color: #155724; }
            .error { background: #f8d7da; color: #721c24; }
            .info { background: #d1ecf1; color: #0c5460; }
        </style>
    </head>
    <body>
        <h1>Distance Tracker Control Panel</h1>

        <div class="section">
            <h2>Send Distance Data</h2>
            <form id="distanceForm">
                <div class="form-group">
                    <label for="distance">Distance (cm):</label>
                    <input type="number" id="distance" name="distance" step="0.1" placeholder="Enter distance" required>
                </div>
                <button type="submit">Send Distance Data</button>
            </form>
            <div id="distanceResponse" class="status" style="display: none;"></div>
        </div>

        <div class="section">
            <h2>Fast Mode Control</h2>
            <button onclick="checkFastMode()">Check Status</button>
            <button onclick="startFastMode()">Start Fast Mode (7 min)</button>
            <button onclick="stopFastMode()">Stop Fast Mode</button>
            <div id="fastModeResponse" class="status" style="display: none;"></div>
        </div>

        <div class="section">
            <h2>Delay Configuration</h2>
            <div class="form-group">
                <label for="fastDelay">Fast Mode Delay (ms):</label>
                <input type="number" id="fastDelay" name="fastDelay" min="100" max="60000" placeholder="1000">
                <small>Delay between readings in fast mode (100-60000ms)</small>
            </div>
            <div class="form-group">
                <label for="slowDelay">Slow Mode Delay (ms):</label>
                <input type="number" id="slowDelay" name="slowDelay" min="1000" max="300000" placeholder="30000">
                <small>Delay between readings in slow mode (1000-300000ms)</small>
            </div>
            <button onclick="loadDelays()">Load Current Delays</button>
            <button onclick="saveDelays()">Save Delays</button>
            <button onclick="resetDelays()">Reset to Defaults</button>
            <div id="delayResponse" class="status" style="display: none;"></div>
        </div>

        <div class="section">
            <h2>Device Subscription</h2>
            <div class="form-group">
                <label for="deviceToken">Device Token:</label>
                <input type="text" id="deviceToken" name="deviceToken" placeholder="Enter FCM device token">
            </div>
            <button onclick="subscribeDevice()">Subscribe Device</button>
            <div id="subscribeResponse" class="status" style="display: none;"></div>
        </div>

        <script>
            function showResponse(elementId, message, type = 'info') {
                const element = document.getElementById(elementId);
                element.textContent = message;
                element.className = `status ${type}`;
                element.style.display = 'block';
                setTimeout(() => element.style.display = 'none', 5000);
            }

            document.getElementById('distanceForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const distance = document.getElementById('distance').value;

                fetch('/api/receive-data', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `distance=${encodeURIComponent(distance)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.code === 200) {
                        showResponse('distanceResponse', `Data sent successfully. Delay: ${data.delay}ms`, 'success');
                    } else {
                        showResponse('distanceResponse', `Error: ${data.message}`, 'error');
                    }
                })
                .catch(error => {
                    showResponse('distanceResponse', `Error: ${error.message}`, 'error');
                });
            });

            function checkFastMode() {
                fetch('/api/fast-mode')
                .then(response => response.json())
                .then(data => {
                    if (data.fast_mode_active) {
                        const remaining = Math.floor(data.time_remaining / 60);
                        showResponse('fastModeResponse', `Fast mode active. ${remaining} minutes remaining.`, 'success');
                    } else {
                        showResponse('fastModeResponse', 'Fast mode is not active.', 'info');
                    }
                })
                .catch(error => {
                    showResponse('fastModeResponse', `Error: ${error.message}`, 'error');
                });
            }

            function startFastMode() {
                fetch('/api/fast-mode', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=start'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.code === 200) {
                        showResponse('fastModeResponse', data.message, 'success');
                    } else {
                        showResponse('fastModeResponse', `Error: ${data.message}`, 'error');
                    }
                })
                .catch(error => {
                    showResponse('fastModeResponse', `Error: ${error.message}`, 'error');
                });
            }

            function stopFastMode() {
                fetch('/api/fast-mode', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=stop'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.code === 200) {
                        showResponse('fastModeResponse', data.message, 'success');
                    } else {
                        showResponse('fastModeResponse', `Error: ${data.message}`, 'error');
                    }
                })
                .catch(error => {
                    showResponse('fastModeResponse', `Error: ${error.message}`, 'error');
                });
            }

            function loadDelays() {
                fetch('/api/delay-config')
                .then(response => response.json())
                .then(data => {
                    if (data.code === 200) {
                        document.getElementById('fastDelay').value = data.delays.fast;
                        document.getElementById('slowDelay').value = data.delays.slow;
                        showResponse('delayResponse', 'Current delays loaded successfully.', 'success');
                    } else {
                        showResponse('delayResponse', `Error: ${data.message}`, 'error');
                    }
                })
                .catch(error => {
                    showResponse('delayResponse', `Error: ${error.message}`, 'error');
                });
            }

            function saveDelays() {
                const fastDelay = document.getElementById('fastDelay').value;
                const slowDelay = document.getElementById('slowDelay').value;

                if (!fastDelay || !slowDelay) {
                    showResponse('delayResponse', 'Please enter both delay values.', 'error');
                    return;
                }

                fetch('/api/delay-config', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=set&fast_delay=${encodeURIComponent(fastDelay)}&slow_delay=${encodeURIComponent(slowDelay)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.code === 200) {
                        showResponse('delayResponse', data.message, 'success');
                    } else {
                        showResponse('delayResponse', `Error: ${data.message}`, 'error');
                    }
                })
                .catch(error => {
                    showResponse('delayResponse', `Error: ${error.message}`, 'error');
                });
            }

            function resetDelays() {
                if (!confirm('Are you sure you want to reset delays to default values?')) {
                    return;
                }

                fetch('/api/delay-config', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=reset'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.code === 200) {
                        document.getElementById('fastDelay').value = data.delays.fast;
                        document.getElementById('slowDelay').value = data.delays.slow;
                        showResponse('delayResponse', data.message, 'success');
                    } else {
                        showResponse('delayResponse', `Error: ${data.message}`, 'error');
                    }
                })
                .catch(error => {
                    showResponse('delayResponse', `Error: ${error.message}`, 'error');
                });
            }

            function subscribeDevice() {
                const token = document.getElementById('deviceToken').value;
                if (!token) {
                    showResponse('subscribeResponse', 'Please enter a device token.', 'error');
                    return;
                }

                fetch(`/api/subscribe?token=${encodeURIComponent(token)}`)
                .then(response => response.text())
                .then(data => {
                    showResponse('subscribeResponse', data, 'success');
                })
                .catch(error => {
                    showResponse('subscribeResponse', `Error: ${error.message}`, 'error');
                });
            }
        </script>
    </body>
    </html>
    <?php
}