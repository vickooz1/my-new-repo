<?php
session_start();
require_once 'db.php';
require_once 'mail.php';

if (empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

try {
    $pdo = initializeDatabase();
    $admin = $pdo->prepare('SELECT full_name, is_admin FROM users WHERE id = ?');
    $admin->execute([$_SESSION['user_id']]);
    $admin = $admin->fetch();
    if (!$admin || !(int) $admin['is_admin']) {
        http_response_code(403);
        exit('Access denied. This page is for administrators only.');
    }

      $adminMessage = '';
      if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        if ($action === 'add_event') {
          $title = trim($_POST['title'] ?? '');
          $description = trim($_POST['description'] ?? '');
          $eventDate = trim($_POST['event_date'] ?? '');
          $location = trim($_POST['location'] ?? '');
          $parsedDate = DateTime::createFromFormat('Y-m-d\TH:i', $eventDate);
          if ($title === '' || !$parsedDate) {
            $adminMessage = 'Please provide an event title and valid date.';
          } else {
            $stmt = $pdo->prepare('INSERT INTO events (title, description, event_date, location) VALUES (?, ?, ?, ?)');
            $stmt->execute([$title, $description, $parsedDate->format('Y-m-d H:i:s'), $location]);
            $eventNotification = $title . ' on ' . $parsedDate->format('l, d F Y \a\t H:i') . ($location !== '' ? ' at ' . $location : '') . '.';
            notifyMembers($pdo, 'New event published', $eventNotification);
            $adminMessage = 'Event published successfully.';
          }
        } elseif ($action === 'add_photo') {
          $caption = trim($_POST['caption'] ?? '');
          $photoCategory = $_POST['photo_category'] ?? 'other';
          $allowedPhotoCategories = ['worship', 'fellowship', 'outreach', 'events', 'meetings', 'other'];
          if (!in_array($photoCategory, $allowedPhotoCategories, true)) {
            $photoCategory = 'other';
          }
          $allowedTypes = [
            'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif',
            'video/mp4' => 'mp4', 'video/webm' => 'webm', 'video/ogg' => 'ogv',
          ];
          $photos = $_FILES['photo'] ?? null;
          $uploadedCount = 0;
          $failedCount = 0;
          $uploadDir = __DIR__ . '/gallery_uploads';
          if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
              $adminMessage = 'The gallery_uploads folder could not be created. Create it on the server and grant the web server write permission.';
            }
          }

          if ($adminMessage !== '') {
            // Preserve the filesystem error instead of replacing it with a generic upload message.
          } elseif (!$photos || !isset($photos['tmp_name']) || !is_array($photos['tmp_name'])) {
            $adminMessage = 'Please select at least one JPG, PNG, WEBP, GIF, MP4, WEBM, or OGG file.';
          } elseif (!is_writable($uploadDir)) {
            $adminMessage = 'The gallery_uploads folder is not writable by the web server. Update its permissions on the deployed server.';
          } elseif (count($photos['tmp_name']) > 25) {
            $adminMessage = 'Please upload no more than 25 images at a time. Smaller batches prevent hosting request limits from stopping the admin page.';
          } else {
            $stmt = $pdo->prepare('INSERT INTO gallery (image_path, caption, category) VALUES (?, ?, ?)');
            foreach ($photos['tmp_name'] as $index => $temporaryPath) {
              $uploadError = $photos['error'][$index] ?? UPLOAD_ERR_NO_FILE;
              if ($uploadError !== UPLOAD_ERR_OK || !is_uploaded_file($temporaryPath)) {
                $failedCount++;
                continue;
              }
              $fileType = mime_content_type($temporaryPath);
              if (!isset($allowedTypes[$fileType])) {
                $failedCount++;
                continue;
              }
              $fileName = 'gallery_' . bin2hex(random_bytes(8)) . '.' . $allowedTypes[$fileType];
              if (move_uploaded_file($temporaryPath, $uploadDir . '/' . $fileName)) {
                $stmt->execute(['gallery_uploads/' . $fileName, $caption, $photoCategory]);
                $uploadedCount++;
              } else {
                $failedCount++;
              }
            }
            $adminMessage = $uploadedCount . ' photo(s) added to the gallery.';
            if ($failedCount > 0) {
              $adminMessage .= ' ' . $failedCount . ' file(s) were skipped. Accepted formats: JPG, PNG, WEBP, GIF, MP4, WEBM, and OGG.';
            }
            if ($uploadedCount > 0) {
              notifyMembers($pdo, 'New photos added', $uploadedCount . ' new photo(s) are now available in the MGLG gallery.');
            }
          }
        } elseif ($action === 'delete_event' || $action === 'delete_photo') {
          $id = (int) ($_POST['id'] ?? 0);
          $table = $action === 'delete_event' ? 'events' : 'gallery';
          $stmt = $pdo->prepare("DELETE FROM $table WHERE id = ?");
          $stmt->execute([$id]);
          $adminMessage = 'Item removed successfully.';
        } elseif ($action === 'add_directory') {
          $name = trim($_POST['name'] ?? '');
          $category = $_POST['category'] ?? '';
          $location = trim($_POST['location'] ?? '');
          $contact = trim($_POST['contact'] ?? '');
          if ($name === '' || !in_array($category, ['church', 'campus'], true) || $location === '') {
            $adminMessage = 'Please complete the directory entry.';
          } else {
            $stmt = $pdo->prepare('INSERT INTO directory (name, category, location, contact) VALUES (?, ?, ?, ?)');
            $stmt->execute([$name, $category, $location, $contact]);
            $adminMessage = 'Directory entry added.';
          }
        } elseif ($action === 'delete_directory') {
          $stmt = $pdo->prepare('DELETE FROM directory WHERE id = ?');
          $stmt->execute([(int) ($_POST['id'] ?? 0)]);
          $adminMessage = 'Directory entry removed.';
        } elseif ($action === 'send_update') {
          $subject = trim($_POST['email_subject'] ?? '');
          $body = trim($_POST['email_body'] ?? '');
          if ($subject === '' || $body === '' || preg_match('/[\r\n]/', $subject)) {
            $adminMessage = 'Please provide a subject and message without line breaks in the subject.';
          } else {
            $memberEmails = $pdo->query('SELECT email FROM users WHERE is_admin = 0 AND email IS NOT NULL AND email <> \'\'')->fetchAll(PDO::FETCH_COLUMN);
            $memberEmails = array_values(array_filter($memberEmails, static function ($email) {
              return filter_var($email, FILTER_VALIDATE_EMAIL);
            }));
            if (!$memberEmails) {
              $adminMessage = 'There are no member email addresses to update.';
            } else {
              $sent = sendAppMail($memberEmails[0], $subject, $body, array_slice($memberEmails, 1));
              $adminMessage = $sent
                ? 'Update sent to ' . count($memberEmails) . ' member(s).'
                : 'The update could not be sent. Check XAMPP Sendmail, use a valid Gmail App Password, and make sure the sender address matches the authenticated Gmail account.';
            }
          }
        } elseif ($action === 'reply_volunteer') {
          $volunteerEmail = strtolower(trim($_POST['volunteer_email'] ?? ''));
          $subject = trim($_POST['reply_subject'] ?? '');
          $body = trim($_POST['reply_body'] ?? '');
          if (!filter_var($volunteerEmail, FILTER_VALIDATE_EMAIL) || $subject === '' || $body === '' || preg_match('/[\r\n]/', $subject)) {
            $adminMessage = 'Please provide a valid volunteer email, subject, and message without line breaks in the subject.';
          } else {
            $sent = sendAppMail($volunteerEmail, $subject, $body);
            if ($sent) {
              $memberLookup = $pdo->prepare('SELECT id FROM users WHERE LOWER(email) = ? AND is_admin = 0 LIMIT 1');
              $memberLookup->execute([$volunteerEmail]);
              $memberId = $memberLookup->fetchColumn();
              if ($memberId) {
                $notification = $pdo->prepare('INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)');
                $notification->execute([$memberId, $subject, $body]);
              }
            }
            $adminMessage = $sent
              ? 'Reply sent to ' . $volunteerEmail . '.'
              : 'The reply could not be sent. Check XAMPP Sendmail and the configured sender account.';
          }
        }
      }

    $totalUsers = (int) $pdo->query('SELECT COUNT(*) FROM users WHERE is_admin = 0')->fetchColumn();
    $users = $pdo->query('SELECT member_code, full_name, email, phone_number, student_status, institution, level_of_education, course, area_of_specialization, occupation, residence, home_town, home_church, pastor_phone, created_at FROM users WHERE is_admin = 0 ORDER BY created_at DESC')->fetchAll();
      $events = $pdo->query('SELECT id, title, description, event_date, location FROM events ORDER BY event_date ASC')->fetchAll();
      $gallery = $pdo->query('SELECT id, image_path, caption, category FROM gallery ORDER BY created_at DESC')->fetchAll();
      $directory = $pdo->query('SELECT id, name, category, location, contact FROM directory ORDER BY category, name')->fetchAll();
      $volunteers = $pdo->query('SELECT full_name, email, phone_number, interest, offer, created_at FROM volunteers ORDER BY created_at DESC LIMIT 20')->fetchAll();
      $subscribers = $pdo->query('SELECT email, created_at FROM newsletter_subscribers ORDER BY created_at DESC LIMIT 20')->fetchAll();
} catch (Throwable $exception) {
    http_response_code(500);
    exit('Could not load the admin dashboard.');
}
?>
<!doctype html>
<html lang="en">
<head><link rel="icon" type="image/png" href="assets/mglg-favicon.png">
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>MGLG Admin Dashboard</title>
  <link rel="stylesheet" href="theme.css">
  <style>
    * { box-sizing:border-box; } body { margin:0; background:#0f172a; color:#e2e8f0; font-family:Arial,sans-serif; }
    .wrap { max-width:1200px; margin:36px auto; padding:0 22px; } .top { display:flex; justify-content:space-between; gap:16px; align-items:center; margin-bottom:24px; }
    .card { background:#172033; border-radius:18px; padding:22px; box-shadow:0 14px 36px rgba(0,0,0,.2); } .count { font-size:2.4rem; font-weight:700; color:#a5b4fc; margin:8px 0 0; }
    .actions a { display:inline-block; color:#fff; background:#475569; text-decoration:none; border-radius:9px; padding:10px 13px; margin-left:8px; }
    .table-wrap { overflow-x:auto; margin-top:22px; } table { width:100%; border-collapse:collapse; min-width:850px; } th,td { text-align:left; padding:13px 10px; border-bottom:1px solid #334155; } th { color:#a5b4fc; } td { color:#dbeafe; }
    .code { color:#c4b5fd; font-weight:bold; } .muted { color:#94a3b8; }
    .message { padding:12px 14px; margin:18px 0; background:#14532d; color:#dcfce7; border-radius:9px; }
    .manage-grid { display:grid; grid-template-columns:1fr 1fr; gap:22px; margin-top:22px; }
    .manage-form { display:grid; gap:10px; margin-top:16px; }
    .manage-form label { color:#cbd5e1; font-weight:bold; font-size:.9rem; }
    .manage-form input, .manage-form textarea { width:100%; padding:11px 12px; border:1px solid #475569; border-radius:8px; background:#0f172a; color:#fff; font:inherit; }
    .manage-form textarea { min-height:80px; resize:vertical; }
    .manage-form button, .remove { border:0; border-radius:8px; padding:10px 13px; background:#6366f1; color:#fff; font-weight:bold; cursor:pointer; }
    .remove { background:#7f1d1d; font-size:.8rem; }
    .content-list { display:grid; gap:10px; margin-top:18px; }
    .content-item { display:flex; justify-content:space-between; align-items:center; gap:12px; padding:12px; background:#0f172a; border:1px solid #334155; border-radius:9px; }
    .content-item small { display:block; color:#94a3b8; margin-top:4px; }
    .gallery-thumb { width:58px; height:48px; object-fit:cover; border-radius:5px; }
    .upload-status { margin:8px 0 0; color:var(--muted); font-size:.85rem; }
    .submission-list { display:grid; gap:8px; margin-top:14px; }
    .submission { padding:10px 12px; border-bottom:1px solid #334155; }
    body { background:#f5f2eb; color:#18241f; }
    .card { background:#fffaf2; border:1px solid rgba(31,97,74,.16); box-shadow:0 14px 36px rgba(22,49,38,.08); }
    .count, th { color:#1f614a; }
    .actions a, .manage-form button { background:#633e25; }
    .table-wrap table th, .table-wrap table td, .submission { border-color:#dfd2c0; }
    td, .muted, .content-item small, .upload-status { color:#65716a; }
    .code { color:#633e25; }
    .message { background:#e2efe7; color:#1f614a; }
    .manage-form label { color:#315c4a; }
    .manage-form input, .manage-form textarea { border-color:#dfd2c0; background:#fff; color:#18241f; }
    .content-item { background:#f8fcf9; border-color:#dfd2c0; }
    @media (max-width:800px) { .manage-grid { grid-template-columns:1fr; } .top { align-items:flex-start; flex-direction:column; } }
  </style>
</head>
<body>
  <main class="wrap">
    <div class="top">
      <div><h1>MGLG Admin Dashboard</h1><p class="muted">Welcome, <?php echo htmlspecialchars($admin['full_name']); ?>.</p></div>
      <div class="actions"><a href="dashboard.php">My dashboard</a><a href="logout.php">Logout</a></div>
    </div>
    <section class="card"><div class="muted">Registered and paid members</div><div class="count"><?php echo $totalUsers; ?></div></section>
    <?php if ($adminMessage !== '') : ?><div class="message"><?php echo htmlspecialchars($adminMessage); ?></div><?php endif; ?>
    <div class="manage-grid">
      <section class="card">
        <h2>Email members</h2>
        <p class="muted">Send an event update to all registered members from the organisation email address.</p>
        <form class="manage-form" method="post">
          <input type="hidden" name="action" value="send_update" />
          <label for="email-subject">Subject</label><input id="email-subject" name="email_subject" maxlength="150" required />
          <label for="email-body">Message</label><textarea id="email-body" name="email_body" required maxlength="10000" placeholder="Share the event details, date, time, and location."></textarea>
          <button type="submit">Send to all members</button>
        </form>
      </section>
      <section class="card">
        <h2>Publish an event</h2>
        <form class="manage-form" method="post">
          <input type="hidden" name="action" value="add_event" />
          <label for="title">Event name</label><input id="title" name="title" required maxlength="150" />
          <label for="event_date">Date and time</label><input id="event_date" type="datetime-local" name="event_date" required />
          <label for="location">Location</label><input id="location" name="location" maxlength="150" />
          <label for="description">Description</label><textarea id="description" name="description"></textarea>
          <button type="submit">Publish event</button>
        </form>
        <div class="content-list">
          <?php foreach ($events as $event) : ?><div class="content-item"><div><strong><?php echo htmlspecialchars($event['title']); ?></strong><small><?php echo htmlspecialchars(date('d M Y, H:i', strtotime($event['event_date']))); ?><?php echo $event['location'] ? ' · ' . htmlspecialchars($event['location']) : ''; ?></small></div><form method="post"><input type="hidden" name="action" value="delete_event" /><input type="hidden" name="id" value="<?php echo (int) $event['id']; ?>" /><button class="remove" type="submit">Remove</button></form></div><?php endforeach; ?>
        </div>
      </section>
      <section class="card">
        <h2>Add gallery photo</h2>
        <form class="manage-form" method="post" enctype="multipart/form-data">
          <input type="hidden" name="action" value="add_photo" />
          <label for="photo">Photos or videos</label><input id="photo" type="file" name="photo[]" accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/ogg" multiple required /><p class="upload-status" id="uploadStatus">Select up to 25 photos or videos per upload, below 100 MB total.</p>
          <label for="caption">Caption</label><input id="caption" name="caption" maxlength="150" />
          <label for="photo-category">Category</label><select id="photo-category" name="photo_category"><option value="worship">Worship</option><option value="fellowship">Fellowship</option><option value="outreach">Outreach</option><option value="events">Events</option><option value="meetings">Meetings</option><option value="other">Other</option></select>
          <button type="submit">Add photos</button>
        </form>
        <div class="content-list">
          <?php foreach ($gallery as $photo) : ?><div class="content-item"><div><img class="gallery-thumb" src="<?php echo htmlspecialchars($photo['image_path']); ?>" alt="<?php echo htmlspecialchars($photo['caption'] ?: 'Gallery photo'); ?>" /><small><?php echo htmlspecialchars(ucfirst($photo['category'] ?: 'Other')); ?></small></div><form method="post"><input type="hidden" name="action" value="delete_photo" /><input type="hidden" name="id" value="<?php echo (int) $photo['id']; ?>" /><button class="remove" type="submit">Remove</button></form></div><?php endforeach; ?>
        </div>
      </section>
    </div>
    <div class="manage-grid">
      <section class="card"><h2>Add directory place</h2><form class="manage-form" method="post"><input type="hidden" name="action" value="add_directory" /><label for="place-name">Name</label><input id="place-name" name="name" required /><label for="category">Category</label><select id="category" name="category" required><option value="church">Church</option><option value="campus">Campus</option></select><label for="place-location">Location</label><input id="place-location" name="location" required /><label for="place-contact">Contact</label><input id="place-contact" name="contact" /><button type="submit">Add place</button></form><div class="content-list"><?php foreach ($directory as $place) : ?><div class="content-item"><div><strong><?php echo htmlspecialchars($place['name']); ?></strong><small><?php echo ucfirst(htmlspecialchars($place['category'])); ?> · <?php echo htmlspecialchars($place['location']); ?></small></div><form method="post"><input type="hidden" name="action" value="delete_directory" /><input type="hidden" name="id" value="<?php echo (int) $place['id']; ?>" /><button class="remove" type="submit">Remove</button></form></div><?php endforeach; ?></div></section>
      <section class="card"><h2>Volunteer requests</h2><div class="submission-list"><?php if (!$volunteers) : ?><p class="muted">No volunteer requests yet.</p><?php endif; ?><?php foreach ($volunteers as $volunteer) : ?><div class="submission"><strong><?php echo htmlspecialchars($volunteer['full_name']); ?></strong><small><?php echo htmlspecialchars($volunteer['interest'] ?: 'General interest'); ?> · <?php echo htmlspecialchars($volunteer['phone_number']); ?> · <?php echo htmlspecialchars($volunteer['email']); ?></small><?php if (!empty($volunteer['offer'])) : ?><p><?php echo nl2br(htmlspecialchars($volunteer['offer'])); ?></p><?php endif; ?><form class="reply-form" method="post"><input type="hidden" name="action" value="reply_volunteer" /><input type="hidden" name="volunteer_email" value="<?php echo htmlspecialchars($volunteer['email']); ?>" /><label>Reply subject</label><input name="reply_subject" value="MGLG volunteer request" maxlength="150" required /><label>Message to <?php echo htmlspecialchars($volunteer['full_name']); ?></label><textarea name="reply_body" rows="4" placeholder="Write your response..." required></textarea><button type="submit">Send reply</button></form></div><?php endforeach; ?></div><h2 style="margin-top:28px">Newsletter subscribers</h2><div class="submission-list"><?php if (!$subscribers) : ?><p class="muted">No subscribers yet.</p><?php endif; ?><?php foreach ($subscribers as $subscriber) : ?><div class="submission"><?php echo htmlspecialchars($subscriber['email']); ?></div><?php endforeach; ?></div></section>
    </div>
    <section class="card table-wrap">
      <h2>Member directory</h2>
      <table>
        <thead><tr><th>Member ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Category</th><th>Education / Work details</th><th>Home details</th><th>Joined</th></tr></thead>
        <tbody>
        <?php foreach ($users as $member) : ?>
          <tr>
            <td class="code"><?php echo htmlspecialchars($member['member_code'] ?: '—'); ?></td>
            <td><?php echo htmlspecialchars($member['full_name']); ?></td>
            <td><?php echo htmlspecialchars($member['email']); ?></td>
            <td><?php echo htmlspecialchars($member['phone_number']); ?></td>
            <td><?php echo $member['student_status'] === 'yes' ? 'Student' : 'Non-student'; ?></td>
            <td><?php echo htmlspecialchars($member['student_status'] === 'yes' ? ('Institution: ' . ($member['institution'] ?: '—') . ' / Level: ' . ($member['level_of_education'] ?: '—') . ' / Course: ' . ($member['course'] ?: '—')) : ('Specialization: ' . ($member['area_of_specialization'] ?: '—') . ' / Occupation: ' . ($member['occupation'] ?: '—'))); ?></td>
            <td><?php echo htmlspecialchars(($member['student_status'] === 'no' ? 'Area of residence: ' . ($member['residence'] ?: '—') . ' / ' : '') . 'Home town: ' . ($member['home_town'] ?: '—') . ' / Home church: ' . ($member['home_church'] ?: '—') . ($member['pastor_phone'] ? ' / Pastor: ' . $member['pastor_phone'] : '')); ?></td>
            <td><?php echo htmlspecialchars($member['created_at']); ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </section>
  </main>
  <script src="theme.js?v=20260929-light-only"></script>
  <script>
    const photoInput = document.getElementById('photo');
    const uploadStatus = document.getElementById('uploadStatus');
    const galleryForm = photoInput?.closest('form');
    const maxUploadFiles = 25;
    const maxUploadBytes = 100 * (1024 ** 2);
    photoInput?.addEventListener('change', () => {
      const fileCount = photoInput.files.length;
      const totalSize = Array.from(photoInput.files).reduce((sum, file) => sum + file.size, 0);
      const totalSizeGb = totalSize / (1024 ** 3);
      const tooManyFiles = fileCount > maxUploadFiles;
      const tooLarge = totalSize > maxUploadBytes;
      uploadStatus.textContent = tooManyFiles
        ? `You selected ${fileCount} files. Select no more than ${maxUploadFiles} images at a time.`
        : tooLarge
          ? `This selection is about ${totalSizeGb.toFixed(2)} GB. Upload a smaller batch.`
          : `${fileCount} file(s) selected. Upload in batches if needed.`;
      uploadStatus.style.color = tooManyFiles || tooLarge ? '#a52d2d' : '';
    });
    galleryForm?.addEventListener('submit', (event) => {
      const totalSize = Array.from(photoInput.files).reduce((sum, file) => sum + file.size, 0);
      if (photoInput.files.length > maxUploadFiles || totalSize > maxUploadBytes) {
        event.preventDefault();
        uploadStatus.textContent = 'Please select a smaller batch before uploading.';
        uploadStatus.style.color = '#a52d2d';
      }
    });
  </script>
</body>
</html>
