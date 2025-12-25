<?php

// Read current data
$data = json_decode(file_get_contents('distance.json'), true);
$delays = ['fast' => 1000, 'slow' => 30000]; // Default values

if ($data && isset($data['delays'])) {
    $delays = $data['delays'];
}

$currentTime = time();
$response = ['code' => 200];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Return current delay configuration
    $response['delays'] = $delays;
    $response['message'] = 'Current delay configuration retrieved';

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'set') {
        $fastDelay = (int)($_POST['fast_delay'] ?? $delays['fast']);
        $slowDelay = (int)($_POST['slow_delay'] ?? $delays['slow']);

        // Validate delay values (reasonable ranges)
        if ($fastDelay < 100 || $fastDelay > 60000) {
            $response['code'] = 400;
            $response['message'] = 'Fast delay must be between 100ms and 60000ms';
        } elseif ($slowDelay < 1000 || $slowDelay > 300000) {
            $response['code'] = 400;
            $response['message'] = 'Slow delay must be between 1000ms and 300000ms';
        } else {
            $newDelays = ['fast' => $fastDelay, 'slow' => $slowDelay];

            // Update the data with new delays
            $data['delays'] = $newDelays;
            file_put_contents('distance.json', json_encode($data));

            $response['delays'] = $newDelays;
            $response['message'] = 'Delay configuration updated successfully';
        }

    } elseif ($action === 'reset') {
        // Reset to default values
        $defaultDelays = ['fast' => 1000, 'slow' => 30000];
        $data['delays'] = $defaultDelays;
        file_put_contents('distance.json', json_encode($data));

        $response['delays'] = $defaultDelays;
        $response['message'] = 'Delay configuration reset to defaults';

    } else {
        $response['code'] = 400;
        $response['message'] = 'Invalid action. Use "set" or "reset"';
    }

} else {
    $response['code'] = 405;
    $response['message'] = 'Method not allowed. Use GET to retrieve or POST to update delay configuration';
}

echo json_encode($response);
