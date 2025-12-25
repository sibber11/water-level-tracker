<?php

// **Key Point**: Load Composer's autoloader for the necessary classes.
require __DIR__ . '/vendor/autoload.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging\MessageSent;

// --- Configuration ---

// 1. **Define the Path to Your Service Account File**
// **Important**: This must match the file you used in Python.
$serviceAccountPath = __DIR__ . '/service-account.json';

/**
 * Send a push notification via Firebase Cloud Messaging
 *
 * @param string $deviceToken The FCM device registration token
 * @param string $title The notification title
 * @param string $body The notification body text
 * @param array<string, string> $customData Optional custom data payload (key-value pairs, all values must be strings)
 * @return array<string, bool|string> Returns ['success' => true, 'messageId' => string] on success
 * @throws \Throwable on error
 */
function sendFcmNotification(string $deviceToken, string $title, string $body, array $customData = []): array
{
    global $serviceAccountPath;

    // Initialize Firebase App
    $factory = (new Factory)
        ->withServiceAccount($serviceAccountPath);

    $messaging = $factory->createMessaging();

    // Build the Notification Message
    $notification = Notification::create($title, $body);

    // Build the Final Message Object with notification and custom data
    $messageBuilder = CloudMessage::new()
        ->toToken($deviceToken)
        ->withNotification($notification);

    // Only add data if provided
    if (!empty($customData)) {
        $messageBuilder = $messageBuilder->withData($customData);
    }

    // Send the Message
    /** @var MessageSent $result */
    $result = $messaging->send($messageBuilder);

    $messageId = $result->getMessageId();

    return [
        'success' => true,
        'messageId' => $messageId,
    ];
}

/**
 * Subscribe to a topic
 *
 * @param string $topic The topic to subscribe to
 * @param callable $callback The callback function to call when a message is received
 */
function subscribeToTopic(string $topic, $token)
{
    global $serviceAccountPath;

    $factory = (new Factory)
        ->withServiceAccount($serviceAccountPath);

    $messaging = $factory->createMessaging();

    $messaging->subscribeToTopic($topic, $token);
}

function sendMessageToTopic(string $topic, string $title, string $body, array $customData = [])
{
    global $serviceAccountPath;
    
    $factory = (new Factory)
        ->withServiceAccount($serviceAccountPath);
        
    $messaging = $factory->createMessaging();

    $message = CloudMessage::new()
        ->toTopic($topic)
        ->withNotification(Notification::create($title, $body))
        ->withData($customData);

    $result = $messaging->send($message);


    $_SESSION['data'] = json_encode($result);
}