<?php

require __DIR__ . '/firebase.php';

// Check if it is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Invalid request method', 'code' => 405]);
    exit;
}

$data = $_POST['distance'] ?? null;
if (!$data) {
    http_response_code(400);
    echo json_encode(['message' => 'Distance parameter is required', 'code' => 400]);
    exit;
}

$readOldData = json_decode(file_get_contents('distance.json'), true);
$delays = ['fast' => 1000, 'slow' => 30000]; // Default values
$lastLowNotification = null;
$lastHighNotification = null;
$fastModeExpiry = null;

if ($readOldData) {
    $lastLowNotification = $readOldData['last_low_notification'];
    $lastHighNotification = $readOldData['last_high_notification'];
    $fastModeExpiry = $readOldData['fast_mode_expiry'];
    $delays = $readOldData['delays'] ?? $delays; // Use configured delays or defaults
}

$newDistance = $data;
$newTimestamp = time();
// Check if fast mode is active (fast_mode_expiry exists and hasn't expired)
$fast = $fastModeExpiry && $newTimestamp < $fastModeExpiry;

// Calculate delay based on distance
$distance = (float)$data;

// Send notifications (only once every 5 minutes)
$currentTime = time();
$notificationSent = false;

$notification = [
    'distance' => $distance,
    'timestamp' => $currentTime,
];

if ($distance < 20 && (!$lastLowNotification || $currentTime - $lastLowNotification >= 300)) {
    sendMessageToTopic('distance', 'Water level is Low', 'Start the motor to fill the tank', $notification);
    $lastLowNotification = $currentTime;
    $notificationSent = true;
}

if ($distance > 120 && (!$lastHighNotification || $currentTime - $lastHighNotification >= 300)) {
    sendMessageToTopic('distance', 'Water level is High', 'Stop the motor to prevent overflow', $notification);
    $lastHighNotification = $currentTime;
    $notificationSent = true;
}

// Store the data in a file
file_put_contents('distance.json', json_encode([
    'distance' => $data,
    'timestamp' => time(),
    'fast' => $fast ?? false,
    'last_low_notification' => $lastLowNotification ?? null,
    'last_high_notification' => $lastHighNotification ?? null,
    'fast_mode_expiry' => $fastModeExpiry ?? null,
    'delays' => $delays,
]));

// Return JSON response with delay
echo json_encode([
    'code' => 200,
    'delay' => $fast ? $delays['fast'] : $delays['slow']
]);
