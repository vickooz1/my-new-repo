<link rel="stylesheet" href="theme.css"><link rel="stylesheet" href="mglg-pages.css?v=20260929-home-nav"><script src="theme.js?v=20260929-light-only"></script>
<?php
require_once 'db.php';
require_once 'mail.php';
$pdo = initializeDatabase();
$message = '';
$interestOptions = ['Bible Study', 'Worship', 'Media', 'Evangelism', 'Prayer', 'Youth Leadership', 'Community Outreach', 'Creative Arts'];
$selectedInterests = $_POST['interests'] ?? [];
if (!is_array($selectedInterests)) {
    $selectedInterests = [];
}
$selectedInterests = array_values(array_intersect($interestOptions, $selectedInterests));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone_number'] ?? '');
    $offer = trim($_POST['offer'] ?? '');
    $interest = implode(', ', $selectedInterests);
    if ($fullName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $phone === '' || ($interest === '' && $offer === '')) {
        $message = 'Please complete your contact details and choose an interest or describe what you can offer.';
    } else {
        $stmt = $pdo->prepare('INSERT INTO volunteers (full_name, email, phone_number, interest, offer) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$fullName, $email, $phone, $interest, $offer]);
        $mailBody = "New volunteer request\n\n"
            . "Name: {$fullName}\n"
            . "Email: {$email}\n"
            . "Phone: {$phone}\n"
            . "Interested in: {$interest}\n"
            . "What they can offer:\n{$offer}\n";
        $mailSent = sendAppMail('thegenerationlovesgod@gmail.com', 'New MGLG volunteer request', $mailBody);
        $message = 'Thank you. The MGLG team will contact you soon.';
        if (!$mailSent) {
            $message .= ' Your request was saved, but the email could not be sent right now.';
        }
    }
}
?>
    <!doctype html>
    <html lang="en">
    <head><link rel="icon" type="image/png" href="assets/mglg-favicon.png">
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title>Join the Movement | MGLG</title>
        <link rel="stylesheet" href="theme.css"><link rel="stylesheet" href="mglg-pages.css?v=20260929-home-nav">
        <script src="theme.js?v=20260929-light-only"></script>
        <style>
            body { margin:0; min-height:100vh; display:block; background:#f6f0e7; color:#27221d; font-family:Georgia,serif; padding:0; }
            .box { position:relative; z-index:1; width:min(520px,calc(100% - 32px)); margin:calc(var(--header-h) + 32px) auto 24px; padding:34px; background:#fffaf2; border:1px solid #dfd2c0; }
            h1 { font-size:3rem; line-height:.95; font-weight:normal; margin:12px 0; }
            p { color:#746b61; line-height:1.6; }
            .form { display:grid; gap:8px; }
            .form > label { margin-top:8px; color:#633e25; font:700 .78rem 'Trebuchet MS',sans-serif; }
            .form input { padding:12px; border:1px solid #dfd2c0; background:#fff; font:inherit; }
            .interest-list { display:grid; gap:10px; margin:8px 0; }
            .interest-option { display:flex!important; align-items:center; gap:10px; margin:0!important; color:#27221d!important; font:inherit!important; }
            .interest-option input { width:18px; height:18px; margin:0; }
            .button { margin-top:12px; border:0; border-radius:3px; padding:12px 16px; background:#633e25; color:#fff; font-weight:bold; cursor:pointer; }
            .message { margin-top:18px; padding:12px; background:#e5f0e9; color:#315c4a; }
            .back { display:block; margin-top:22px; color:#633e25; }
        </style>
    </head>
    <body>
<?php include 'site-media-background.php'; ?>
        <div class="page-shell"><header class="page-header"><a class="brand" href="dashboard.php">My Generation Loves God</a><?php $activeNav = 'opportunities'; include 'site-nav.php'; ?></header></div>
        <main class="box">
            <div style="color:#315c4a;font:700 .75rem 'Trebuchet MS',sans-serif;letter-spacing:.15em;text-transform:uppercase">Join the movement</div>
            <h1>Join the movement.</h1>
            <p>Bring your gifts to campus outreach, worship, media, mentorship, or events.</p>
            <form class="form" method="post">
                <label for="name">Full name</label>
                <input id="name" name="full_name" required>
                <label for="email">Email</label>
                <input id="email" name="email" type="email" required>
                <label for="phone">Phone number</label>
                <input id="phone" name="phone_number" required>
                <label for="offer">What can you offer?</label>
                <textarea id="offer" name="offer" rows="5" maxlength="5000" placeholder="Optional: tell us about your skills, time, experience, or resources."></textarea>
                <label>I'm interested in:</label>
                <div class="interest-list">
                    <?php foreach ($interestOptions as $option) : ?>
                        <label class="interest-option">
                            <input type="checkbox" name="interests[]" value="<?php echo htmlspecialchars($option); ?>" <?php echo in_array($option, $selectedInterests, true) ? 'checked' : ''; ?> />
                            <?php echo htmlspecialchars($option); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <button class="button" type="submit">Join the movement</button>
            </form>
            <?php if ($message) : ?><div class="message"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
            <a class="back" href="index.php">Back to sign in</a>
        </main>
    </body>
    </html>
