<?php
session_start();
require_once 'db.php';

$pdo = initializeDatabase();
$isAuthenticated = isset($_SESSION['user_id']);
$message = '';
$error = '';
$categories = [
    'attachment' => 'Attachment / internship',
    'job' => 'Job opportunity',
    'idea' => 'Idea / collaboration',
    'other' => 'Other support',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$isAuthenticated) {
        $error = 'Please sign in to share a request or opportunity.';
    } else {
        $postType = $_POST['post_type'] ?? '';
        $category = $_POST['category'] ?? '';
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $contactEmail = strtolower(trim($_POST['contact_email'] ?? ''));
        $location = trim($_POST['location'] ?? '');

        if (!in_array($postType, ['seeking', 'offering'], true) || !array_key_exists($category, $categories) || $title === '' || $description === '' || !filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please complete the type, category, title, details, and a valid contact email.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO opportunities (user_id, post_type, category, title, description, contact_email, location) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([(int) $_SESSION['user_id'], $postType, $category, $title, $description, $contactEmail, $location ?: null]);
            $message = 'Your post is now visible to the MGLG community.';
        }
    }
}

$posts = $pdo->query(
    'SELECT o.post_type, o.category, o.title, o.description, o.contact_email, o.location, o.created_at, u.full_name
     FROM opportunities o
     INNER JOIN users u ON u.id = o.user_id
     ORDER BY o.created_at DESC
     LIMIT 50'
)->fetchAll();
?>
<!doctype html>
<html lang="en">
<head><link rel="icon" type="image/png" href="assets/mglg-favicon.png">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Opportunities | MGLG</title>
  <link rel="stylesheet" href="mglg-pages.css?v=20260929-home-nav">
  <link rel="stylesheet" href="theme.css">
  <style>
    .opportunity-layout { display:grid; grid-template-columns:minmax(280px,.8fr) minmax(0,1.2fr); gap:26px; margin-top:32px; align-items:start; }
    .opportunity-form, .opportunity-post { padding:25px; border:1px solid var(--line); border-radius:16px; background:var(--card); box-shadow:0 8px 24px rgba(22,49,38,.05); }
    .opportunity-form { position:sticky; top:20px; }
    .opportunity-form label { display:block; margin:15px 0 6px; color:var(--green); font-size:.79rem; font-weight:800; }
    .opportunity-form input, .opportunity-form select, .opportunity-form textarea { width:100%; padding:11px 12px; border:1px solid var(--line); border-radius:8px; background:#fff; color:var(--ink); font:inherit; }
    .opportunity-form textarea { min-height:130px; resize:vertical; }
    .opportunity-list { display:grid; gap:14px; }
    .opportunity-post { border-left:4px solid var(--gold); }
    .opportunity-post.is-offering { border-left-color:var(--green); }
    .opportunity-meta { display:flex; flex-wrap:wrap; gap:8px; align-items:center; color:var(--muted); font-size:.8rem; }
    .opportunity-tag { padding:5px 8px; border-radius:999px; background:var(--mint); color:var(--green); font-weight:800; text-transform:capitalize; }
    .opportunity-post h2 { margin:15px 0 8px; font-size:1.45rem; }
    .opportunity-post p { margin:0; white-space:pre-line; }
    .opportunity-contact { display:inline-block; margin-top:15px; color:var(--brown); font-weight:800; overflow-wrap:anywhere; }
    .notice { margin:18px 0; padding:13px 15px; border-radius:9px; background:var(--mint); color:var(--green); }
    .notice.error { background:#fbe8e5; color:#9c3428; }
    .empty { padding:32px; border:1px dashed var(--line); border-radius:16px; color:var(--muted); text-align:center; }
    @media (max-width:800px) { .opportunity-layout { grid-template-columns:1fr; } .opportunity-form { position:static; } }
  </style>
</head>
<body>
  <div class="page-shell">
    <header class="page-header"><a class="brand" href="dashboard.php">My Generation Loves God</a><?php $activeNav = 'opportunities'; include 'site-nav.php'; ?></header>
    <main class="page-main">
      <div class="eyebrow eyebrow--xs">MGLG community board</div>
      <h1 class="page-title--xs">Help one another grow.</h1>
      <p class="lead lead--xs">Looking for an attachment, a job, support for an idea, or a collaborator? Share your need or post an opportunity here.</p>
      <?php if ($message) : ?><div class="notice"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
      <?php if ($error) : ?><div class="notice error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
      <div class="opportunity-layout">
        <aside class="opportunity-form">
          <h2>Share a post</h2>
          <?php if (!$isAuthenticated) : ?>
            <p>Sign in to request support or offer an opportunity.</p>
            <a class="button" href="index.php?login=1">Sign in</a>
          <?php else : ?>
            <form method="post">
              <label for="post_type">I am</label>
              <select id="post_type" name="post_type" required><option value="seeking">Seeking support / an opportunity</option><option value="offering">Offering an opportunity</option></select>
              <label for="category">Category</label>
              <select id="category" name="category" required><?php foreach ($categories as $value => $label) : ?><option value="<?php echo $value; ?>"><?php echo htmlspecialchars($label); ?></option><?php endforeach; ?></select>
              <label for="title">Short title</label>
              <input id="title" name="title" maxlength="150" required placeholder="e.g. Seeking an IT attachment in Nairobi">
              <label for="description">Details</label>
              <textarea id="description" name="description" maxlength="5000" required placeholder="Explain what you need or are offering, requirements, and useful next steps."></textarea>
              <label for="location">Location (optional)</label>
              <input id="location" name="location" maxlength="150" placeholder="e.g. Nairobi or Remote">
              <label for="contact_email">Contact email</label>
              <input id="contact_email" name="contact_email" type="email" maxlength="100" required placeholder="you@example.com">
              <button class="button" type="submit">Publish post</button>
            </form>
          <?php endif; ?>
        </aside>
        <section class="opportunity-list" aria-label="Community opportunities">
          <?php if (!$posts) : ?><div class="empty">No posts yet. Be the first to ask for support or share an opportunity.</div><?php endif; ?>
          <?php foreach ($posts as $post) : ?>
            <article class="opportunity-post<?php echo $post['post_type'] === 'offering' ? ' is-offering' : ''; ?>">
              <div class="opportunity-meta"><span class="opportunity-tag"><?php echo $post['post_type'] === 'offering' ? 'Offering' : 'Seeking'; ?></span><span><?php echo htmlspecialchars($categories[$post['category']]); ?></span><span>·</span><span><?php echo htmlspecialchars($post['full_name']); ?></span><span>·</span><time datetime="<?php echo htmlspecialchars($post['created_at']); ?>"><?php echo htmlspecialchars(date('d M Y', strtotime($post['created_at']))); ?></time></div>
              <h2><?php echo htmlspecialchars($post['title']); ?></h2>
              <p><?php echo htmlspecialchars($post['description']); ?></p>
              <?php if ($post['location']) : ?><div class="opportunity-meta" style="margin-top:12px">Location: <?php echo htmlspecialchars($post['location']); ?></div><?php endif; ?>
              <a class="opportunity-contact" href="mailto:<?php echo htmlspecialchars($post['contact_email']); ?>">Contact: <?php echo htmlspecialchars($post['contact_email']); ?></a>
            </article>
          <?php endforeach; ?>
        </section>
      </div>
    </main>
    <?php include 'site-footer.php'; ?>
  </div>
  <?php include 'site-cross-background.php'; ?>
  <script src="theme.js?v=20260929-light-only"></script>
</body>
</html>
