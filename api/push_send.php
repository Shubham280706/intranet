<?php
/**
 * Push notification helper functions
 * Use: sendPushToUser($userId, $title, $body, $url);
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

function sendPushToUser($userId, $title, $body, $url = '/intranet/dashboard.php') {
    try {
        global $pdo;
        error_log("sendPushToUser called: userId=$userId, title=$title");

        // Get all push subscriptions for this user
        $stmt = $pdo->prepare(
            "SELECT endpoint, p256dh, auth FROM push_subscriptions WHERE user_id = ?"
        );
        $stmt->execute([$userId]);
        $subscriptions = $stmt->fetchAll();

        if (empty($subscriptions)) {
            error_log("No push subscriptions found for user $userId");
            return false;
        }
        error_log("Found " . count($subscriptions) . " subscriptions for user $userId");

        // VAPID authentication
        $auth = [
            'VAPID' => [
                'subject' => 'mailto:admin@workspace.local',
                'publicKey' => VAPID_PUBLIC_KEY,
                'privateKey' => VAPID_PRIVATE_KEY,
            ],
        ];

        // Create WebPush instance
        $webPush = new WebPush($auth);

        // Prepare notification payload
        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'url' => $url,
            'tag' => 'workspace-' . time(),
        ]);

        // Queue notification for each subscription
        $count = 0;
        foreach ($subscriptions as $sub) {
            try {
                $subscription = Subscription::create([
                    'endpoint' => $sub['endpoint'],
                    'keys' => [
                        'p256dh' => $sub['p256dh'],
                        'auth' => $sub['auth']
                    ]
                ]);

                $webPush->queueNotification($subscription, $payload);
                $count++;
            } catch (Exception $e) {
                error_log("Error queueing notification: " . $e->getMessage());
                // Delete invalid subscription
                deletePushSubscription($sub['endpoint']);
            }
        }

        // Send all queued notifications
        if ($count > 0) {
            $response = $webPush->flush();

            // Log results
            foreach ($response as $result) {
                if ($result->isSuccess()) {
                    error_log("Push notification sent successfully");
                } else {
                    error_log("Push notification failed: " . $result->getResponse()->getReasonPhrase());
                    // Delete expired subscription
                    if ($result->getResponse()->getStatusCode() === 410) {
                        deletePushSubscription($result->getEndpoint());
                    }
                }
            }

            return true;
        }

        return false;
    } catch (Exception $e) {
        error_log("Push notification error: " . $e->getMessage());
        return false;
    }
}

function deletePushSubscription($endpoint) {
    try {
        global $pdo;
        $stmt = $pdo->prepare("DELETE FROM push_subscriptions WHERE endpoint = ?");
        $stmt->execute([$endpoint]);
        return true;
    } catch (Exception $e) {
        error_log("Error deleting subscription: " . $e->getMessage());
        return false;
    }
}

function broadcastPushToAll($title, $body, $url = '/intranet/dashboard.php') {
    try {
        global $pdo;

        // Get all unique users with subscriptions
        $stmt = $pdo->query(
            "SELECT DISTINCT user_id FROM push_subscriptions"
        );
        $users = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $sent = 0;
        foreach ($users as $userId) {
            if (sendPushToUser($userId, $title, $body, $url)) {
                $sent++;
            }
        }

        return $sent;
    } catch (Exception $e) {
        error_log("Broadcast push error: " . $e->getMessage());
        return 0;
    }
}
