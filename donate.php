<?php
require_once 'db.php';
require_once 'mpesa.php';
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = (int) ($_POST['amount'] ?? 0);
    $phoneNumber = trim($_POST['phone_number'] ?? '');
    if ($amount < 10 || $phoneNumber === '') {
        $message = 'Please enter a valid phone number and a donation of at least KSh 10.';
    } else {
        try {
            $pdo = getDbConnection();
            $payment = initiateMpesaStkPush($phoneNumber, $amount, '0350287553970', 'MGLG movement support donation');
            $stmt = $pdo->prepare('INSERT INTO donations (checkout_request_id, phone_number, amount, account_reference) VALUES (?, ?, ?, ?)');
            $stmt->execute([$payment['CheckoutRequestID'], normalizeMpesaPhone($phoneNumber), $amount, '0350287553970']);
            $message = 'M-Pesa prompt sent to ' . normalizeMpesaPhone($phoneNumber) . '. Enter your PIN to complete the donation.';
        } catch (Throwable $exception) {
            $message = 'Donation prompt could not be sent: ' . $exception->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <link rel="icon" type="image/png" href="assets/mglg-favicon.png">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Donations | My Generation Loves God</title>
  <meta name="description" content="Support My Generation Loves God (MGLG) via M-Pesa. Partner with us to reach, mentor, and disciple university and college students across Kenya.">
  <link rel="stylesheet" href="theme.css">
  <link rel="stylesheet" href="mglg-pages.css?v=20260929-home-nav">
  <style>
    .donation-shell { max-width:680px; }
    .payment-steps { margin:20px 0; padding:18px; border-left:4px solid var(--gold); background:var(--mint); color:var(--green); line-height:1.65; border-radius:0 12px 12px 0; }
    .payment-steps strong { display:block; color:var(--brown); }
    .give-form { display:grid; gap:12px; padding:24px; border:1px solid var(--line); border-radius:18px; background:var(--card); backdrop-filter:blur(8px); box-shadow:0 8px 24px rgba(22,49,38,.05); }
    .give-form label { color:var(--brown); font-size:.82rem; font-weight:800; }
    .give-form input { width:100%; padding:13px; border:1px solid var(--line); border-radius:8px; background:#fff; color:var(--ink); font:inherit; }
    .give-form button { margin-top:8px; border:0; border-radius:10px; padding:14px; background:var(--brown); color:#fff; font-weight:800; cursor:pointer; transition:background .2s ease, transform .2s ease; }
    .give-form button:hover { background:#54331d; transform:translateY(-1px); }
    .payment-message { margin-top:20px; padding:16px; border-left:4px solid var(--green); background:var(--mint); color:var(--green); overflow-wrap:anywhere; border-radius:0 10px 10px 0; }
  </style>
</head>
<body>
  <div class="page-shell">
    <header class="page-header">
      <a class="brand" href="dashboard.php">My Generation Loves God</a>
      <?php $activeNav = 'donations'; include 'site-nav.php'; ?>
    </header>

    <main class="page-main donation-shell">
      <div class="eyebrow eyebrow--xs">Give with purpose</div>
      <h1 class="page-title--xs">Support the movement.</h1>
      <p class="lead lead--xs">Your generosity helps MGLG reach, disciple, and empower students and young people across Kenya.</p>

      <div class="payment-steps">
        <strong>M-Pesa support</strong>
        Your phone will receive an automatic payment prompt.<br>
        Paybill: <strong>247247</strong><br>
        Business number: <strong>0350287553970</strong>
      </div>

      <form class="give-form" method="post">
        <label for="phone_number">M-Pesa phone number</label>
        <input id="phone_number" name="phone_number" type="tel" placeholder="07XX XXX XXX" required>

        <label for="amount">Donation amount in KSh</label>
        <input id="amount" name="amount" type="number" min="10" placeholder="Amount in KSh" required>

        <button type="submit">Send M-Pesa prompt</button>
      </form>

      <?php if ($message) : ?>
        <div class="payment-message"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>
    </main>

    <?php include 'site-footer.php'; ?>
  </div>

  <?php include 'site-cross-background.php'; ?>
  <script src="theme.js?v=20260929-light-only"></script>
</body>
</html>
