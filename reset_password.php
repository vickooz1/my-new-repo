<link rel="stylesheet" href="theme.css"><script src="theme.js?v=20260929-light-only"></script>
<?php
session_start();
require_once 'db.php';
$pdo = initializeDatabase();
$token = $_GET['token'] ?? $_POST['token'] ?? '';
$tokenHash = hash('sha256', $token);
$stmt = $pdo->prepare('SELECT id, user_id FROM password_reset_tokens WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW() LIMIT 1');
$stmt->execute([$tokenHash]);
$reset = $stmt->fetch();
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $reset) {
    $password = $_POST['password'] ?? '';
    $passwordConfirmation = $_POST['password_confirmation'] ?? '';
    if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/\d/', $password)) {
        $message = 'Password must be at least 8 characters and include uppercase, lowercase, and a number.';
    } elseif (!hash_equals($password, $passwordConfirmation)) {
        $message = 'The password confirmation does not match.';
    } else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('UPDATE users SET password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
            $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $reset['user_id']]);
            $stmt = $pdo->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL');
            $stmt->execute([$reset['user_id']]);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $message = 'We could not update your password. Please request a new reset link.';
            $reset = null;
        }
        if ($message !== '') {
            // Keep the reset form available when the transaction fails.
        } else {
            session_regenerate_id(true);
            unset($_SESSION['user_id']);
            $_SESSION['success'] = 'Password updated. You can now sign in.';
            header('Location: index.php');
            exit;
        }
    }
} elseif (!$reset) {
    $message = 'This reset link is invalid or has expired.';
}
?><!doctype html><html lang="en"><head><link rel="icon" type="image/png" href="assets/mglg-favicon.png"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Choose password | MGLG</title><style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#f6f0e7;color:#27221d;font-family:Georgia,serif;padding:24px}.box{width:min(440px,100%);padding:32px;background:#fffaf2;border:1px solid #dfd2c0}h1{font-size:2.5rem;font-weight:normal;margin:0 0 10px}label{display:block;margin:20px 0 7px;font:700 .8rem 'Trebuchet MS',sans-serif;color:#633e25}input{width:100%;padding:12px;border:1px solid #dfd2c0;box-sizing:border-box;font:inherit}.hint{color:#746b61;font-size:.85rem;line-height:1.5}.button{margin-top:16px;border:0;border-radius:3px;padding:12px 16px;background:#633e25;color:#fff;font-weight:bold;cursor:pointer}.message{margin-top:18px;padding:12px;background:#fce7e7;color:#8a2525;overflow-wrap:anywhere}.back{display:block;margin-top:22px;color:#633e25}</style></head><body><main class="box"><h1>Choose a new password</h1><?php if ($reset) : ?><p class="hint">Use at least 8 characters with an uppercase letter, a lowercase letter, and a number.</p><form method="post"><input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>"><label for="password">New password</label><input id="password" name="password" type="password" autocomplete="new-password" minlength="8" required><label for="password_confirmation">Confirm new password</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required><button class="button" type="submit">Update password</button></form><?php endif; ?><?php if ($message) : ?><div class="message"><?php echo htmlspecialchars($message); ?></div><?php endif; ?><?php if (!$reset) : ?><p class="hint">Request a new reset link if this link has expired or was already used.</p><?php endif; ?><a class="back" href="reset_request.php">Request a new link</a><a class="back" href="index.php">Back to sign in</a></main></body></html>
