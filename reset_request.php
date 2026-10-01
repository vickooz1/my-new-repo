<link rel="stylesheet" href="theme.css"><script src="theme.js?v=20260929-light-only"></script>
<?php
session_start();
require_once 'db.php';
require_once 'mail.php';
$pdo = initializeDatabase();
$message = 'If that email is registered, a password reset link has been sent.';
$localResetUrl = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lastRequest = (int) ($_SESSION['password_reset_requested_at'] ?? 0);
    if (time() - $lastRequest < 60) {
        $message = 'Please wait one minute before requesting another reset link.';
    }
    $email = strtolower(trim($_POST['email'] ?? ''));
    if ($message === 'If that email is registered, a password reset link has been sent.' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['password_reset_requested_at'] = time();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE LOWER(email) = ? LIMIT 1');
        $stmt->execute([$email]);
        $userId = $stmt->fetchColumn();
        if ($userId) {
            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $stmt = $pdo->prepare('DELETE FROM password_reset_tokens WHERE user_id = ? OR expires_at < NOW()');
            $stmt->execute([$userId]);
            $stmt = $pdo->prepare('INSERT INTO password_reset_tokens (user_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))');
            $stmt->execute([$userId, $tokenHash]);
            $resetUrl = 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . '/reset_password.php?token=' . urlencode($token);
            $mailBody = "Hello,\n\nWe received a request to reset your MGLG password.\n\n"
                . "Use this secure link to choose a new password:\n$resetUrl\n\n"
                . "This link expires in one hour and can only be used once.\n"
                . "If you did not request this, you can ignore this email.\n\n"
                . "My Generation Loves God (MGLG)";
            sendAppMail($email, 'MGLG password reset link', $mailBody);
            $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
            $isLocalHost = str_starts_with($host, 'localhost') || str_starts_with($host, '127.0.0.1');
            if (getenv('APP_ENV') === 'local' || $isLocalHost) {
                $message = 'A fresh reset link was created for local testing.';
                $localResetUrl = $resetUrl;
            }
        }
    }
}
?><!doctype html><html lang="en"><head><link rel="icon" type="image/png" href="assets/mglg-favicon.png"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Reset password | MGLG</title><style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#f6f0e7;color:#27221d;font-family:Georgia,serif;padding:24px}.box{width:min(440px,100%);padding:32px;background:#fffaf2;border:1px solid #dfd2c0}h1{font-size:2.5rem;font-weight:normal;margin:0 0 10px}p{color:#746b61;line-height:1.5}label{display:block;margin:20px 0 7px;font:700 .8rem 'Trebuchet MS',sans-serif;color:#633e25}input{width:100%;padding:12px;border:1px solid #dfd2c0;box-sizing:border-box;font:inherit}.button{margin-top:16px;border:0;border-radius:3px;padding:12px 16px;background:#633e25;color:#fff;font-weight:bold;cursor:pointer}.message{margin-top:18px;padding:12px;background:#e5f0e9;color:#315c4a;overflow-wrap:anywhere}.reset-link{display:block;margin-top:12px;color:#633e25;font-weight:bold;word-break:break-all}.back{display:block;margin-top:22px;color:#633e25}</style></head><body><main class="box"><h1>Reset your password</h1><p>Enter your email and we will send a secure reset link. For your security, the same message is shown whether or not the email is registered.</p><form method="post"><label for="email">Email address</label><input id="email" name="email" type="email" autocomplete="email" required><button class="button" type="submit">Send reset link</button></form><div class="message"><?php echo htmlspecialchars($message); ?><?php if ($localResetUrl) : ?><a class="reset-link" href="<?php echo htmlspecialchars($localResetUrl); ?>">Open the fresh reset link</a><?php endif; ?></div><a class="back" href="index.php">Back to sign in</a></main></body></html>
