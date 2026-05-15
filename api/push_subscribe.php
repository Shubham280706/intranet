<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth_check.php';

requireLogin();

$uid = currentUserId();
$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    jsonResponse(['error' => 'Invalid JSON'], 400);
}

// Validate required fields
$endpoint = $input['endpoint'] ?? '';
$p256dh = $input['keys']['p256dh'] ?? '';
$auth = $input['keys']['auth'] ?? '';

if (!$endpoint || !$p256dh || !$auth) {
    jsonResponse(['error' => 'Missing required fields: endpoint, p256dh, auth'], 400);
}

try {
    $pdo = getDB();

    // Delete existing subscription for this endpoint (prevents duplicates)
    $stmt = $pdo->prepare("DELETE FROM push_subscriptions WHERE endpoint = ?");
    $stmt->execute([$endpoint]);

    // Insert new subscription
    $stmt = $pdo->prepare(
        "INSERT INTO push_subscriptions (user_id, endpoint, p256dh, auth)
         VALUES (?, ?, ?, ?)"
    );
    $stmt->execute([$uid, $endpoint, $p256dh, $auth]);

    jsonResponse([
        'success' => true,
        'message' => 'Push subscription saved',
        'subscriptionId' => (int)$pdo->lastInsertId()
    ]);
} catch (PDOException $e) {
    error_log('Push subscription error: ' . $e->getMessage());
    jsonResponse(['error' => 'Failed to save subscription'], 500);
}
