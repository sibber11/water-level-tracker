<?php

require __DIR__ . '/firebase.php';

const FAST_MODE_EXPIRY = 7 * 60; // 7 minutes

// Read current data
$data = json_decode(file_get_contents('distance.json'), true);
if (!$data) {
    $data = [
        'distance' => '0',
        'timestamp' => null,
        'fast' => false,
        'last_low_notification' => null,
        'last_high_notification' => null,
        'fast_mode_expiry' => null
    ];
}

$currentTime = time();
$response = ['code' => 200];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Check current fast mode status
    $isFastMode = $data['fast_mode_expiry'] && $currentTime < $data['fast_mode_expiry'];
    $response['fast_mode_active'] = $isFastMode;
    $response['fast_mode_expiry'] = $data['fast_mode_expiry'];
    $response['time_remaining'] = $isFastMode ? $data['fast_mode_expiry'] - $currentTime : 0;

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'start') {
        // Trigger fast mode for 7 minutes
        $data['fast_mode_expiry'] = $currentTime + FAST_MODE_EXPIRY; // 7 minutes from now
        $response['message'] = 'Fast mode activated for 7 minutes';
        $response['fast_mode_expiry'] = $data['fast_mode_expiry'];

    } elseif ($action === 'stop') {
        // Stop fast mode immediately
        $data['fast_mode_expiry'] = null;
        $response['message'] = 'Fast mode stopped';

    } else {
        $response['code'] = 400;
        $response['message'] = 'Invalid action. Use "start" or "stop"';
    }

    // Save updated data
    if ($response['code'] === 200) {
        file_put_contents('distance.json', json_encode($data));
    }

} else {
    $response['code'] = 405;
    $response['message'] = 'Method not allowed. Use GET to check status or POST to control fast mode';
}

echo json_encode($response);
