<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$pdo = initializeDatabase();
$markRead = $pdo->prepare('UPDATE notifications SET read_at = CURRENT_TIMESTAMP WHERE user_id = ? AND read_at IS NULL');
$markRead->execute([$_SESSION['user_id']]);
$stmt = $pdo->prepare('SELECT title, message, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 30');
$stmt->execute([$_SESSION['user_id']]);
$notifications = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en"><head><link rel="icon" type="image/png" href="assets/mglg-favicon.png"><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Notifications | MGLG</title><link rel="stylesheet" href="mglg-pages.css?v=20260929-home-nav"><style>
.notification-list { display:grid; gap:12px; max-width:760px; margin-top:34px; }
.notification { padding:20px; background:var(--card); border:1px solid var(--line); border-left:4px solid var(--green); }
.notification strong { display:block; color:var(--brown); font-size:1.1rem; }
.notification p { margin:7px 0 0; }
.notification time { display:block; margin-top:10px; color:var(--muted); font:700 .72rem 'Trebuchet MS',sans-serif; letter-spacing:.06em; text-transform:uppercase; }
.empty-state { padding:24px; background:#e3efe8; color:var(--green); border-left:4px solid var(--green); }
</style></head><body><div class="page-shell"><header class="page-header"><a class="brand" href="dashboard.php">My Generation Loves God</a><?php $activeNav = 'notifications'; include 'site-nav.php'; ?></header><main class="page-main"><div class="eyebrow">MGLG community</div><h1>Notifications.</h1><p class="lead">Stay connected with updates from My Generation Loves God.</p><div class="notification-list"><?php if (!$notifications) : ?><div class="empty-state">New updates from MGLG will appear here.</div><?php endif; ?><?php foreach ($notifications as $notification) : ?><article class="notification"><strong><?php echo htmlspecialchars($notification['title']); ?></strong><p><?php echo htmlspecialchars($notification['message']); ?></p><time><?php echo htmlspecialchars(date('d M Y, H:i', strtotime($notification['created_at']))); ?></time></article><?php endforeach; ?></div></main><footer class="footer">My Generation Loves God (MGLG) | 1 Timothy 4:12 | Igniting a Generation for Christ</footer></div></body></html>
