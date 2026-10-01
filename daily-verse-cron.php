<?php

require_once __DIR__ . '/db.php';

$configuredToken = getenv('DAILY_VERSE_CRON_TOKEN') ?: (defined('DAILY_VERSE_CRON_TOKEN') ? DAILY_VERSE_CRON_TOKEN : '');
$providedToken = $_GET['token'] ?? '';

if ($configuredToken === '' || !is_string($providedToken) || !hash_equals($configuredToken, $providedToken)) {
    http_response_code(403);
    exit('Forbidden');
}

try {
    $pdo = initializeDatabase();
    $dailyWordSent = notifyDailyWord($pdo);
    notifyNewMonthUsers($pdo);
    if ($dailyWordSent) {
        echo 'Daily verse processed.';
        exit;
    }

    http_response_code(500);
    echo 'Daily verse email was not sent. Check the PHP error log.';
} catch (Throwable $exception) {
    error_log('Daily verse cron failed: ' . $exception->getMessage());
    http_response_code(500);
    echo 'Daily verse task failed. Check the PHP error log.';
}
