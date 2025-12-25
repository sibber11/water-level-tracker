<?php

require __DIR__ . '/firebase.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use GET to subscribe.', 'code' => 405]);
    exit;
}

$topic = "distance";
$token = $_GET['token'] ?? null;

if (!$token) {
    http_response_code(400);
    echo json_encode(['error' => 'Token parameter is required', 'code' => 400]);
    exit;
}

try {
    subscribeToTopic($topic, $token);

    // Set CORS headers
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');

    echo json_encode(['message' => "Subscribed to topic: $topic", 'code' => 200]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Subscription failed: ' . $e->getMessage(), 'code' => 500]);
}
