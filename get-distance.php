<?php

// Read current distance data
$data = json_decode(file_get_contents('distance.json'), true);

if (!$data) {
    $data = [
        'distance' => '0',
        'timestamp' => null,
        'fast' => false,
        'last_low_notification' => null,
        'last_high_notification' => null,
        'fast_mode_expiry' => null,
        'delays' => ['fast' => 1000, 'slow' => 30000]
    ];
}

$currentTime = time();
$response = ['code' => 200];

// Format the response data
$response['distance'] = [
    'value' => $data['distance'],
    'timestamp' => $data['timestamp'],
    'last_updated' => $data['timestamp'] ? date('Y-m-d H:i:s', $data['timestamp']) : null,
    'age_seconds' => $data['timestamp'] ? $currentTime - $data['timestamp'] : null
];

$response['fast_mode'] = [
    'active' => $data['fast_mode_expiry'] && $currentTime < $data['fast_mode_expiry'],
    'expiry_timestamp' => $data['fast_mode_expiry'],
    'time_remaining_seconds' => $data['fast_mode_expiry'] && $currentTime < $data['fast_mode_expiry']
        ? $data['fast_mode_expiry'] - $currentTime
        : 0
];

$response['notifications'] = [
    'last_low_water' => $data['last_low_notification'],
    'last_high_water' => $data['last_high_notification'],
    'last_low_formatted' => $data['last_low_notification'] ? date('Y-m-d H:i:s', $data['last_low_notification']) : null,
    'last_high_formatted' => $data['last_high_notification'] ? date('Y-m-d H:i:s', $data['last_high_notification']) : null
];

$response['configuration'] = [
    'delays' => $data['delays'] ?? ['fast' => 1000, 'slow' => 30000]
];

$response['message'] = 'Distance data retrieved successfully';

echo json_encode($response);

