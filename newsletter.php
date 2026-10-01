<link rel="stylesheet" href="theme.css"><script src="theme.js?v=20260929-light-only"></script>
<?php
require_once 'db.php';
$pdo = initializeDatabase();
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
    } else {
        $stmt = $pdo->prepare('INSERT IGNORE INTO newsletter_subscribers (email) VALUES (?)');
        $stmt->execute([$email]);
        $message = 'You are subscribed to MGLG updates.';
    }
}
?><!doctype html><html lang="en"><head><link rel="icon" type="image/png" href="assets/mglg-favicon.png"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Updates | MGLG</title><style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#f6f0e7;color:#27221d;font-family:Georgia,serif;padding:24px}.box{width:min(480px,100%);padding:34px;background:#fffaf2;border:1px solid #dfd2c0}h1{font-size:3rem;line-height:.95;font-weight:normal;margin:12px 0}p{color:#746b61;line-height:1.6}input{width:100%;padding:12px;box-sizing:border-box;border:1px solid #dfd2c0;font:inherit}.button{margin-top:12px;border:0;border-radius:3px;padding:12px 16px;background:#633e25;color:#fff;font-weight:bold;cursor:pointer}.message{margin-top:18px;padding:12px;background:#e5f0e9;color:#315c4a}.back{display:block;margin-top:22px;color:#633e25}</style></head><body><main class="box"><div style="color:#315c4a;font:700 .75rem 'Trebuchet MS',sans-serif;letter-spacing:.15em;text-transform:uppercase">Stay connected</div><h1>Receive MGLG updates.</h1><p>Get event news, community stories, and opportunities to grow in faith.</p><form method="post"><input type="email" name="email" placeholder="Your email address" required><button class="button" type="submit">Subscribe</button></form><?php if ($message) : ?><div class="message"><?php echo htmlspecialchars($message); ?></div><?php endif; ?><a class="back" href="dashboard.php">&larr; Return to dashboard</a></main></body></html>
