<?php
session_start();
require_once 'db.php';
require_once 'site-links.php';

$isAuthenticated = isset($_SESSION['user_id']);
$androidApkPath = __DIR__ . DIRECTORY_SEPARATOR . "C:\Users\PC\AndroidStudioProjects\MyGenarationLoveGod" . DIRECTORY_SEPARATOR . 'app-release.apk';
$androidApkAvailable = is_file($androidApkPath);
$user = null;
$dailyVerse = getDailyVerse();
$unreadNotifications = 0;

// Prefer real community media uploaded through the gallery. The bundled files are
// retained as a fallback until photos or videos have been uploaded.
$uploadedMedia = glob(__DIR__ . '/gallery_uploads/*.{jpg,jpeg,png,webp,mp4,webm,ogv}', GLOB_BRACE) ?: [];
usort($uploadedMedia, static fn (string $left, string $right): int => filemtime($right) <=> filemtime($left));
$uploadedPhotos = [];
$uploadedVideos = [];
foreach ($uploadedMedia as $mediaFile) {
  $relativePath = 'gallery_uploads/' . basename($mediaFile);
  if (in_array(strtolower(pathinfo($mediaFile, PATHINFO_EXTENSION)), ['mp4', 'webm', 'ogv'], true)) {
    $uploadedVideos[] = $relativePath;
  } else {
    $uploadedPhotos[] = $relativePath;
  }
}
$splashPhoto = $uploadedPhotos[0] ?? 'assets/backgrounds/mglg-bg-01.jpeg';
$splashVideo = $uploadedVideos[0] ?? 'assets/backgrounds/mglg-video.mp4';
$heroPhotos = $uploadedPhotos ?: array_map(static fn (int $index): string => 'assets/backgrounds/mglg-bg-' . str_pad((string) $index, 2, '0', STR_PAD_LEFT) . '.jpeg', range(1, 21));

$showWelcomeSplash = $isAuthenticated && !empty($_SESSION['show_welcome_splash']);
if ($isAuthenticated) {
  unset($_SESSION['show_welcome_splash']);
}

try {
    $pdo = initializeDatabase();
  if ($isAuthenticated) {
    $stmt = $pdo->prepare('SELECT id, full_name, email, phone_number, student_status, institution, level_of_education, course, area_of_specialization, occupation, residence, home_town, home_church, pastor_phone, profile_picture, member_code, is_admin FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if ($user && !(int) $user['is_admin']) {
      notifyDailyWord($pdo);
    }
  }
    $events = $pdo->query('SELECT title, description, event_date, location FROM events WHERE event_date >= NOW() ORDER BY event_date ASC LIMIT 6')->fetchAll();
    $gallery = $pdo->query('SELECT image_path, caption FROM gallery ORDER BY created_at DESC LIMIT 12')->fetchAll();
    $directory = $pdo->query('SELECT name, category, location, contact FROM directory ORDER BY category, name')->fetchAll();
  if ($isAuthenticated) {
    $notificationCount = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL');
    $notificationCount->execute([$_SESSION['user_id']]);
    $unreadNotifications = (int) $notificationCount->fetchColumn();
  }
} catch (Exception $e) {
    $error = $e->getMessage();
}

if ($isAuthenticated && !$user) {
    session_destroy();
    header('Location: index.php');
    exit;
}

$message = '';
if ($isAuthenticated && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $phoneNumber = trim($_POST['phone_number'] ?? '');
    $studentStatus = $_POST['student_status'] ?? 'yes';
    $institution = trim($_POST['institution'] ?? '');
    $levelOfEducation = trim($_POST['level_of_education'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $areaOfSpecialization = trim($_POST['area_of_specialization'] ?? '');
    $occupation = trim($_POST['occupation'] ?? '');
    $residence = trim($_POST['residence'] ?? '');
    $homeTown = trim($_POST['home_town'] ?? '');
    $homeChurch = trim($_POST['home_church'] ?? '');
    $pastorPhone = trim($_POST['pastor_phone'] ?? '');

    if ($fullName === '' || $phoneNumber === '') {
        $message = 'Please enter your full name and phone number.';
    } else {
        try {
            $pdo = initializeDatabase();
            $profilePicturePath = $user['profile_picture'] ?? null;

            if (!empty($_FILES['profile_photo']['name'])) {
                $uploadDir = __DIR__ . '/uploads';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                $fileTmp = $_FILES['profile_photo']['tmp_name'];
                $fileName = $_FILES['profile_photo']['name'];
                $fileType = mime_content_type($fileTmp);

                if (!in_array($fileType, $allowedTypes, true)) {
                    throw new Exception('Only JPG, PNG, WEBP, and GIF images are allowed.');
                }

                if ($_FILES['profile_photo']['size'] > 2 * 1024 * 1024) {
                    throw new Exception('Profile image must be 2MB or smaller.');
                }

                $extension = pathinfo($fileName, PATHINFO_EXTENSION);
                $targetFile = $uploadDir . '/profile_' . $_SESSION['user_id'] . '.' . strtolower($extension);
                move_uploaded_file($fileTmp, $targetFile);
                $profilePicturePath = 'uploads/profile_' . $_SESSION['user_id'] . '.' . strtolower($extension);
            }

            if ($profilePicturePath !== null) {
                $stmt = $pdo->prepare('UPDATE users SET full_name = ?, phone_number = ?, student_status = ?, institution = ?, level_of_education = ?, course = ?, area_of_specialization = ?, occupation = ?, residence = ?, home_town = ?, home_church = ?, pastor_phone = ?, profile_picture = ? WHERE id = ?');
                $stmt->execute([$fullName, $phoneNumber, $studentStatus, $institution, $levelOfEducation, $course, $areaOfSpecialization, $occupation, $residence, $homeTown, $homeChurch, $pastorPhone, $profilePicturePath, $_SESSION['user_id']]);
            } else {
                $stmt = $pdo->prepare('UPDATE users SET full_name = ?, phone_number = ?, student_status = ?, institution = ?, level_of_education = ?, course = ?, area_of_specialization = ?, occupation = ?, residence = ?, home_town = ?, home_church = ?, pastor_phone = ? WHERE id = ?');
                $stmt->execute([$fullName, $phoneNumber, $studentStatus, $institution, $levelOfEducation, $course, $areaOfSpecialization, $occupation, $residence, $homeTown, $homeChurch, $pastorPhone, $_SESSION['user_id']]);
            }

            $message = 'Your details were updated successfully.';
            $user['full_name'] = $fullName;
            $user['phone_number'] = $phoneNumber;
            $user['student_status'] = $studentStatus;
            $user['institution'] = $institution;
            $user['level_of_education'] = $levelOfEducation;
            $user['course'] = $course;
            $user['area_of_specialization'] = $areaOfSpecialization;
            $user['occupation'] = $occupation;
            $user['residence'] = $residence;
            $user['home_town'] = $homeTown;
            $user['home_church'] = $homeChurch;
            $user['pastor_phone'] = $pastorPhone;
            if ($profilePicturePath !== null) {
                $user['profile_picture'] = $profilePicturePath;
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head><link rel="icon" type="image/png" href="assets/mglg-favicon.png">
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>MGLG Dashboard</title>
  <link rel="stylesheet" href="theme.css">
  <style>
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: Inter, Arial, sans-serif;
      background: linear-gradient(135deg, #0f172a, #312e81);
      color: #fff;
      min-height: 100vh;
    }
    .wrap { max-width: 1100px; margin: 40px auto; padding: 24px; }
    .topbar { display:flex; justify-content:space-between; align-items:center; gap:16px; margin-bottom:24px; }
    .dashboard-grid { display:grid; gap:24px; grid-template-columns:1fr; }
    .card { background: rgba(15, 23, 42, 0.9); border-radius: 20px; padding: 24px; box-shadow: 0 16px 40px rgba(0,0,0,0.25); }
    .btn { display:inline-block; padding: 10px 16px; border:1px solid var(--brown); border-radius:10px; background:var(--brown); color:#fff; text-decoration:none; cursor:pointer; font-weight:700; font-family:'Trebuchet MS',sans-serif; transition:background .2s ease, transform .2s ease; }
    .btn:hover { background:#54331d; transform:translateY(-1px); }
    .btn.secondary { background:transparent; border:1px solid var(--brown); color:var(--brown); }
    .btn.secondary:hover { background:rgba(109,71,44,.08); }
    form { display:grid; gap:14px; }
    label { font-weight:600; color:#cbd5e1; }
    input, select { width:100%; padding:12px 14px; border-radius:10px; border:1px solid #475569; background:#0f172a; color:#fff; }
    .msg { padding:10px 12px; border-radius:10px; margin-bottom:16px; background:#1e293b; }
    .muted { color:#94a3b8; }
    .section-title { margin-top: 0; margin-bottom: 12px; color: #e2e8f0; }
    .org-text { line-height: 1.8; color: #dbeafe; }
    .profile-card { display:flex; align-items:center; gap:16px; margin-bottom:20px; padding:16px; border-radius:16px; background: rgba(255,255,255,0.06); }
    .avatar { width:84px; height:84px; border-radius:50%; display:grid; place-items:center; background:linear-gradient(135deg,var(--green),#3d6f5a); font-size:2rem; font-weight:700; color:#fff; overflow:hidden; }
    .avatar img { width:100%; height:100%; object-fit:cover; }
    .profile-details h4 { margin:0 0 6px; font-size:1.1rem; }
    .profile-details p { margin:0; color:#cbd5e1; }
    .hidden { display:none; }
    .info-list { display:grid; gap:10px; margin-top: 16px; }
    .info-item { padding: 12px 14px; border-radius: 12px; background: rgba(255,255,255,0.06); }
    .info-item strong { color: #f8fafc; }
    .pill { display:inline-block; padding:6px 14px; border-radius:999px; background:rgba(99,62,37,.14); color:var(--brown); border:1px solid rgba(99,62,37,.28); font-size:0.8rem; font-weight:700; margin-bottom:12px; letter-spacing:.04em; font-family:'Trebuchet MS',sans-serif; }
    body.dark .pill { background:rgba(228,185,135,.15); color:#e4b987; border-color:rgba(228,185,135,.3); }
    body.dark .btn { background:var(--brown); color:#1d2925; border-color:var(--brown); }
    body.dark .btn.secondary { background:transparent; border-color:#e4b987; color:#e4b987; }
    :root { --ink:#27221d; --paper:#f6f0e7; --card:#fffaf2; --brown:#633e25; --green:#315c4a; --muted:#746b61; --line:#dfd2c0; }
    body { background:var(--paper); color:var(--ink); font-family:Georgia, 'Times New Roman', serif; position:relative; overflow-x:clip; }
    body::before { content:''; position:fixed; inset:0; z-index:-2; pointer-events:none; background:radial-gradient(circle at 12% 8%, rgba(49,92,74,.18), transparent 28%), radial-gradient(circle at 88% 18%, rgba(201,144,46,.16), transparent 25%), linear-gradient(115deg, transparent 0 47%, rgba(49,92,74,.07) 47.2%, transparent 47.5% 100%), repeating-linear-gradient(90deg, transparent 0 47px, rgba(49,92,74,.035) 48px, transparent 49px), repeating-linear-gradient(0deg, transparent 0 47px, rgba(201,144,46,.035) 48px, transparent 49px); background-size:auto, auto, auto, 100% 100%, 100% 100%; animation:background-pan 28s linear infinite; }
    body::after { content:''; position:fixed; width:520px; height:520px; right:-210px; top:180px; z-index:-1; pointer-events:none; border:1px solid rgba(49,92,74,.18); border-radius:50%; box-shadow:0 0 0 28px rgba(49,92,74,.05), 0 0 0 72px rgba(201,144,46,.05); animation:background-drift 18s ease-in-out infinite alternate; }
    @keyframes background-drift { from { transform:translate3d(0,0,0) rotate(0deg); } to { transform:translate3d(-28px,34px,0) rotate(8deg); } }
    @keyframes background-pan { from { background-position:0 0, 0 0, 0 0, 0 0, 0 0; } to { background-position:0 0, 0 0, 0 0, 48px 24px, -24px 48px; } }
    .wrap { max-width:1240px; padding:0 32px 72px; margin:auto; }
    .dashboard-header {
      position: sticky;
      top: 0;
      z-index: 1000;
      display: flex;
      align-items: center;
      gap: 20px;
      padding: 12px 32px;
      margin: 0 -32px;
      border-bottom: 1px solid var(--line);
      background: rgba(246, 240, 231, 0.95);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      box-shadow: 0 4px 18px rgba(39, 34, 29, 0.08);
    }
    body.dark .dashboard-header {
      background: rgba(29, 41, 37, 0.95);
      border-bottom-color: var(--line);
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.35);
    }
    .dashboard-header .brand { display:block; flex:0 0 auto; width:clamp(200px,28vw,285px); height:64px; background:url('WhatsApp_Image_2026-06-11_at_10.19.34_AM-removebg-preview.png') left center / contain no-repeat; font-size:0; text-decoration:none; }
    .dashboard-header .page-nav { display:flex; flex:1; flex-wrap:wrap; justify-content:flex-end; gap:4px; }
    .dashboard-header .page-nav a { border-radius:999px; color:#355246; font:700 .77rem Inter,Arial,sans-serif; padding:9px 13px; text-decoration:none; transition:background .2s ease,color .2s ease,transform .2s ease; }
    .dashboard-header .page-nav a:hover { background:#e2efe7; color:var(--green); transform:translateY(-2px); }
    .dashboard-header .page-nav a.is-active { background:var(--green); color:#fff; }
    body.dark .dashboard-header .page-nav a { color:#e4b987; }
    body.dark .dashboard-header .mobile-nav nav { background:#263832; border-color:#496158; }
    body.dark .dashboard-header .mobile-nav a { color:#f6f0e7; }
    .dashboard-header .mobile-nav { display:none; position:relative; }
    .dashboard-header .mobile-nav summary { padding:10px 13px; border:1px solid var(--line); border-radius:8px; color:var(--green); cursor:pointer; font:800 .74rem Inter,Arial,sans-serif; list-style:none; }
    .dashboard-header .mobile-nav summary::-webkit-details-marker { display:none; }
    .dashboard-header .mobile-nav nav { position:absolute; z-index:20; right:0; top:45px; display:grid; min-width:190px; padding:8px; border:1px solid var(--line); border-radius:12px; background:var(--paper); box-shadow:0 20px 55px rgba(22,49,38,.1); }
    .dashboard-header .mobile-nav a { padding:11px 12px; border-radius:7px; color:var(--ink); font:700 .84rem Inter,Arial,sans-serif; text-decoration:none; }
    .dashboard-header .mobile-nav a.is-active, .dashboard-header .mobile-nav a:hover { background:#e2efe7; color:var(--green); }
    .top-actions { display:flex; flex:0 0 auto; align-items:center; gap:8px; }
    .top-actions .notification-icon { position:relative; width:42px; height:42px; display:grid; place-items:center; border:1px solid var(--brown); border-radius:50%; background:rgba(255,250,242,.94); color:var(--brown); font-size:1.1rem; text-decoration:none; box-shadow:0 8px 20px rgba(39,34,29,.12); }
    .top-actions .notification-icon:hover { background:var(--green); color:#fff; transform:translateY(-2px); }
    .top-actions .profile-icon { width:44px; height:44px; display:grid; place-items:center; overflow:hidden; border:2px solid var(--brown); border-radius:50%; background:var(--green); color:#fff; font:700 1rem 'Trebuchet MS',sans-serif; text-decoration:none; box-shadow:0 8px 20px rgba(39,34,29,.16); transition:transform .2s ease, background .2s ease, color .2s ease, box-shadow .2s ease; }
    .top-actions .profile-icon img { width:100%; height:100%; object-fit:cover; }
    .top-actions .profile-icon:hover { background:var(--green); color:#fff; transform:translateY(-2px); }
    .top-actions .notification-dot { border-color:var(--paper); }
    .topbar { display:flex; justify-content:space-between; align-items:center; gap:16px; padding:12px 0 14px; border-bottom:1px solid var(--line); margin-bottom:20px; }
    .topbar h2 { font-size:clamp(1.35rem,2.6vw,1.85rem); line-height:1.2; font-weight:700; letter-spacing:-.02em; color:var(--ink); margin:0 0 6px; }
    .welcome-message { margin:0; max-width:660px; padding:8px 14px; border-left:3px solid var(--green); border-radius:0 8px 8px 0; background:#e3efe8; color:var(--green); font-size:0.92rem; line-height:1.45; }
    .topbar .muted { margin:6px 0 0; font-size:0.82rem; line-height:1.4; color:var(--muted); }
    .hero-project { display:grid; grid-template-columns:1.1fr .9fr; gap:clamp(24px,5vw,60px); align-items:center; padding:clamp(44px,6vw,76px) clamp(22px,4vw,48px); position:relative; overflow:hidden; isolation:isolate; background-image:radial-gradient(circle at 75% 35%, rgba(201,144,46,.12), transparent 22%), linear-gradient(135deg, rgba(227,239,232,.72), transparent 55%), repeating-linear-gradient(135deg, transparent 0 28px, rgba(49,92,74,.04) 29px, transparent 30px); background-size:auto, auto, 62px 62px; animation:hero-texture 22s linear infinite; }
    .hero-backdrop { position:absolute; inset:0; z-index:-1; overflow:hidden; background:#173a2c; }
    .hero-backdrop::after { content:''; position:absolute; inset:0; background:linear-gradient(90deg, rgba(16,43,33,.88), rgba(16,43,33,.62) 48%, rgba(16,43,33,.3)); }
    .hero-video, .hero-photo { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; object-position:center; }
    .hero-video { opacity:.88; }
    .hero-photo { display:none; opacity:.65; transition:opacity .8s ease; }
    .hero-project::before, .hero-project::after { content:''; position:absolute; z-index:0; width:190px; height:190px; background:url('WhatsApp_Image_2026-06-11_at_10.19.34_AM-removebg-preview.png') center / contain no-repeat; opacity:.1; pointer-events:none; filter:saturate(.7); }
    .hero-project::before { top:5%; left:43%; animation:logo-drift-one 13s ease-in-out infinite alternate; }
    .hero-project::after { right:8%; bottom:-24%; width:260px; height:260px; opacity:.07; animation:logo-drift-two 17s ease-in-out infinite alternate-reverse; }
    .hero-project > div:not(.hero-backdrop) { position:relative; z-index:1; }
    @keyframes logo-drift-one { from { transform:translate3d(-28px, 10px, 0) rotate(-8deg); } to { transform:translate3d(38px, -22px, 0) rotate(12deg); } }
    @keyframes logo-drift-two { from { transform:translate3d(25px, 0, 0) rotate(10deg) scale(.9); } to { transform:translate3d(-35px, -35px, 0) rotate(-12deg) scale(1.08); } }
    @keyframes hero-texture { from { background-position:0 0, 0 0, 0 0; } to { background-position:0 0, 0 0, 62px 62px; } }
    .hero-project h1 { max-width:650px; margin:10px 0 18px; font-size:clamp(3rem,7vw,6.4rem); line-height:.96; font-weight:normal; letter-spacing:-.04em; color:#f7d486; text-shadow:0 3px 24px rgba(0,0,0,.4); }
    .hero-project p { max-width:540px; color:#fffaf2; font-size:1.08rem; line-height:1.75; text-shadow:0 2px 12px rgba(0,0,0,.35); }
    .hero-label { color:#f7d486; font-family:'Trebuchet MS',sans-serif; font-size:.75rem; font-weight:bold; letter-spacing:.15em; text-transform:uppercase; }
    .hero-logo { min-height:360px; display:grid; place-items:center; border:1px solid rgba(255,250,242,.65); border-radius:16px; background:linear-gradient(145deg, rgba(255,250,242,.96), rgba(227,239,232,.9)); position:relative; perspective:900px; transition:transform .35s ease, box-shadow .35s ease; box-shadow:0 18px 44px rgba(0,0,0,.18); }
    .hero-logo:before { content:''; position:absolute; inset:16px; border:1px solid var(--line); border-radius:10px; }
    .hero-logo:hover { transform:rotateX(3deg) rotateY(-5deg) translateY(-6px); box-shadow:22px 28px 0 rgba(49,92,74,.1); }
    .hero-logo img { position:relative; width:min(82%,390px); mix-blend-mode:multiply; transition:transform .35s ease; }
    .hero-logo:hover img { transform:translateZ(24px) scale(1.04); }
    .project-links { display:flex; flex-wrap:wrap; gap:12px; margin-top:24px; }
    .project-links a, .btn { border-radius:3px; background:var(--brown); color:#fff; text-decoration:none; padding:11px 16px; font-family:'Trebuchet MS',sans-serif; font-weight:bold; }
    .project-links a.secondary, .btn.secondary { background:transparent; color:var(--brown); border:1px solid var(--brown); }
    .android-app-status { align-self:center; color:var(--muted); font:600 .9rem 'Trebuchet MS',sans-serif; }
    .project-section { padding:24px 0 10px; }
    .project-section h3 { margin:0 0 18px; color:var(--ink); font-size:2.2rem; font-weight:normal; }
    .project-cards { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:28px; }
    .project-card { min-height:165px; padding:22px; border:1px solid var(--line); background:rgba(255,250,242,.88); transition:transform .25s ease, box-shadow .25s ease, border-color .25s ease; }
    .project-card:hover { transform:translateY(-7px); border-color:#bfd5c8; box-shadow:0 16px 30px rgba(49,92,74,.13); }
    .project-card strong { color:var(--brown); font-family:'Trebuchet MS',sans-serif; font-size:.75rem; letter-spacing:.1em; }
    .project-card h4 { margin:32px 0 8px; font-size:1.35rem; font-weight:normal; }
    .project-card p { margin:0; color:var(--muted); line-height:1.5; }
    .project-card a { display:inline-block; margin-top:16px; color:var(--brown); font:700 .78rem 'Trebuchet MS',sans-serif; text-decoration:none; }
    .movement-foundation { padding:18px 0 12px; }
    .foundation-intro { display:grid; grid-template-columns:1fr 1.4fr; gap:28px; align-items:start; margin-bottom:18px; }
    .foundation-intro blockquote { margin:0; padding:20px 22px; border-left:4px solid var(--green); background:#e3efe8; color:var(--green); font-size:1.35rem; line-height:1.45; }
    .foundation-intro blockquote cite { display:block; margin-top:10px; color:var(--brown); font:700 .72rem 'Trebuchet MS',sans-serif; letter-spacing:.1em; text-transform:uppercase; }
    .foundation-copy h3 { margin:0 0 10px; color:var(--ink); font-size:2.2rem; font-weight:normal; }
    .foundation-copy p { margin:0; color:var(--muted); line-height:1.7; }
    .foundation-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; }
    .foundation-item { padding:18px; border:1px solid var(--line); background:var(--card); }
    .foundation-item strong { display:block; color:var(--brown); font:700 .75rem 'Trebuchet MS',sans-serif; letter-spacing:.08em; text-transform:uppercase; }
    .foundation-item h4 { margin:20px 0 7px; font-size:1.2rem; font-weight:normal; }
    .foundation-item p { margin:0; color:var(--muted); font-size:.9rem; line-height:1.5; }
    .mission-vision { display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-top:14px; }
    .mission-vision article { padding:20px; border:1px solid #bfd5c8; background:#e3efe8; }
    .mission-vision strong { color:var(--green); font:700 .75rem 'Trebuchet MS',sans-serif; letter-spacing:.12em; text-transform:uppercase; }
    .mission-vision p { margin:9px 0 0; color:var(--muted); line-height:1.6; }
    .card { border:1px solid var(--line); border-radius:14px; background:var(--card); box-shadow:0 8px 24px rgba(39,34,29,.06); }
    .section-title { color:var(--ink); font-weight:normal; }
    .org-text, .profile-details p { color:var(--muted); }
    .info-list { padding:18px; background:#e3efe8; border:1px solid #bfd5c8; }
    .info-item, .profile-card { border-radius:0; background:#f8fcf9; }
    .info-item strong { color:var(--brown); }
    .contact-bar { display:grid; grid-template-columns:repeat(4,1fr); gap:20px; border:1px solid #bfd5c8; background:#e3efe8; padding:22px; margin-top:44px; color:var(--muted); font-family:'Trebuchet MS',sans-serif; font-size:.85rem; }
    .contact-bar strong { display:block; color:var(--brown); font-size:.7rem; letter-spacing:.1em; text-transform:uppercase; margin-bottom:5px; }
    .event-gallery { display:grid; grid-template-columns:1fr; gap:40px; margin-top:52px; }
    .event-list { display:grid; gap:10px; }
    .event-item { padding:20px 22px; border:1px solid var(--line); border-left:4px solid var(--green); border-radius:10px; background:var(--card); transition:transform .2s ease, box-shadow .2s ease, border-color .2s ease; }
    .event-item:hover { transform:translateY(-4px); border-left-color:#c9902e; box-shadow:0 12px 26px rgba(39,34,29,.12); }
    .event-item time { color:var(--green); font-family:'Trebuchet MS',sans-serif; font-size:.72rem; font-weight:bold; letter-spacing:.08em; text-transform:uppercase; }
    .event-item h4 { margin:7px 0 5px; font-size:1.25rem; font-weight:normal; }
    .event-item p { margin:0; color:var(--muted); line-height:1.45; }
    .gallery-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:18px; }
    .gallery-item { min-width:0; overflow:hidden; margin:0; background:var(--card); border:1px solid var(--line); border-radius:12px; transition:transform .2s ease, box-shadow .2s ease, border-color .2s ease; }
    .gallery-item:hover { transform:translateY(-4px); border-color:var(--green); box-shadow:0 12px 26px rgba(39,34,29,.12); }
    .gallery-item img, .gallery-item video { display:block; width:100%; aspect-ratio:4/3; object-fit:cover; }
    .dashboard-grid > .card { transition:transform .2s ease, box-shadow .2s ease; }
    .dashboard-grid > .card:hover { transform:translateY(-4px); box-shadow:0 20px 42px rgba(39,34,29,.16); }
    .gallery-item figcaption { padding:8px; color:var(--muted); font-size:.8rem; }
    .gallery-item .save-photo { display:block; padding:8px; color:var(--brown); font:700 .78rem 'Trebuchet MS',sans-serif; text-decoration:none; }
    .notification-list { display:grid; gap:8px; margin:0 0 30px; }
    .notification { padding:14px 16px; border-left:3px solid var(--green); background:#e3efe8; }
    .notification strong { display:block; color:var(--brown); }
    .notification p { margin:5px 0 0; color:var(--muted); }
    .notification time { display:block; margin-top:6px; color:var(--muted); font-size:.78rem; }
    .quiz-shell { display:grid; gap:20px; grid-template-columns:1.25fr .75fr; }
    .quiz-form { display:grid; gap:16px; }
    .quiz-question { padding:16px; border:1px solid var(--line); background:var(--card); }
    .quiz-question legend { padding:0; color:var(--ink); font-weight:bold; }
    .quiz-options { display:grid; gap:8px; margin-top:12px; }
    .quiz-option { display:flex; align-items:center; gap:9px; color:var(--muted); }
    .quiz-option input { accent-color:var(--green); }
    .quiz-submit { border:0; border-radius:3px; padding:12px 16px; background:var(--brown); color:#fff; font-weight:bold; cursor:pointer; }
    .quiz-stats { display:grid; gap:10px; align-content:start; }
    .quiz-stat { padding:14px; background:#e3efe8; border:1px solid #bfd5c8; }
    .quiz-stat strong { display:block; color:var(--brown); font-size:1.35rem; }
    .quiz-history { margin:0; padding:0; list-style:none; display:grid; gap:8px; }
    .quiz-history li { display:flex; justify-content:space-between; color:var(--muted); }
    .empty-state { color:var(--muted); font-style:italic; }
    .social-links { display:flex; flex-wrap:wrap; gap:6px 12px; }
    .social-links a { color:var(--brown); font-weight:bold; text-decoration:none; }
    .social-links a:hover { text-decoration:underline; }
    .contact-actions { display:flex; flex-wrap:wrap; gap:8px; }
    .contact-actions a { color:var(--brown); font-weight:bold; text-decoration:none; }
    .contact-actions .call-button { display:inline-block; padding:9px 11px; border:1px solid var(--brown); background:var(--brown); color:#fff; font-size:.78rem; }
    .utility-links { display:flex; flex-wrap:wrap; gap:10px; margin:24px 0; font-family:'Trebuchet MS',sans-serif; }
    .utility-links a { padding:10px 13px; border:1px solid var(--brown); border-radius:3px; background:transparent; color:var(--brown); text-decoration:none; font:700 .78rem 'Trebuchet MS',sans-serif; cursor:pointer; }
    .directory-list { display:grid; grid-template-columns:repeat(2,1fr); gap:10px; }
    .directory-item { padding:16px; border:1px solid var(--line); background:var(--card); }
    .directory-item strong { color:var(--brown); }
    .directory-item small { display:block; color:var(--muted); margin-top:5px; }
    body.dark { --ink:#f6f0e7; --paper:#1d2925; --card:#263832; --muted:#c1cfc6; --line:#496158; --brown:#e4b987; --green:#9ac8aa; }
    body.dark .info-list, body.dark .contact-bar { background:#263832; border-color:#496158; }
    body.dark .info-item { background:#1d2925; }
    body.dark .welcome-message { background:#263832; color:#c1cfc6; }
    body.dark .hero-logo img { mix-blend-mode:normal; }
    .welcome-splash { position:fixed; inset:0; z-index:1000; display:grid; place-items:center; overflow:hidden; isolation:isolate; background:#102b21; color:#fff; transition:opacity .5s ease,visibility .5s ease; }
    .splash-media { position:absolute; inset:0; z-index:0; overflow:hidden; }
    .splash-media video, .splash-media img { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; object-position:center; opacity:.54; }
    .splash-media img { z-index:0; }
    .splash-media video { z-index:1; }
    .splash-media::after { content:''; position:absolute; inset:0; z-index:2; background:linear-gradient(135deg,rgba(9,27,20,.78),rgba(19,58,43,.62)); }
    .welcome-splash::selection { background:rgba(247,212,134,.82); color:#102b21; }
    .welcome-splash::before { content:''; position:absolute; inset:-30%; z-index:1; background-image:linear-gradient(rgba(255,255,255,.08) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.08) 1px,transparent 1px); background-size:48px 48px; transform:perspective(700px) rotateX(63deg); opacity:.45; }
    .welcome-splash::after { content:''; position:absolute; inset:0; z-index:3; background:linear-gradient(90deg, rgba(16,43,33,.96) 0 50%, rgba(23,58,44,.96) 50%); transform:scaleX(0); transform-origin:center; }
    .welcome-splash.is-splitting::after { animation:splash-curtain 1s cubic-bezier(.76,0,.24,1) .15s both; }
    .welcome-splash.is-leaving { opacity:0; visibility:hidden; }
    .splash-content { position:relative; z-index:2; display:grid; justify-items:center; gap:18px; padding:28px; text-align:center; animation:splash-enter .75s cubic-bezier(.16,1,.3,1) both; }
    /* The supplied logo is a square PNG with transparent padding. Keep its native
       square canvas here so the two animated halves stay aligned and the mark is
       not compressed into the previous wide, short frame. */
    .splash-mark { position:relative; width:min(360px,76vw); aspect-ratio:1; filter:drop-shadow(0 20px 28px rgba(0,0,0,.34)); }
    .splash-logo-half { position:absolute; inset:0; overflow:hidden; }
    .splash-logo-half img { position:absolute; width:100%; height:100%; object-fit:contain; max-width:none; }
    .splash-logo-half--left { clip-path:inset(0 50% 0 0); transform-origin:right center; }
    .splash-logo-half--right { clip-path:inset(0 0 0 50%); transform-origin:left center; }
    .welcome-splash.is-splitting .splash-logo-half--left { animation:logo-part-left .9s cubic-bezier(.76,0,.24,1) both; }
    .welcome-splash.is-splitting .splash-logo-half--right { animation:logo-part-right .9s cubic-bezier(.76,0,.24,1) both; }
    .splash-flare { position:absolute; top:50%; left:50%; width:18px; aspect-ratio:1; border-radius:50%; background:#f7d486; box-shadow:0 0 36px 12px rgba(247,212,134,.9),0 0 120px 42px rgba(139,99,255,.45); transform:translate(-50%,-50%) scale(0); }
    .welcome-splash.is-splitting .splash-flare { animation:splash-flare .7s ease-out .18s both; }
    .splash-kicker { margin:0; color:#e8c892; font:700 .74rem 'Trebuchet MS',sans-serif; letter-spacing:.19em; text-transform:uppercase; }
    .splash-content h1 { margin:0; color:#fff; font-size:clamp(1.9rem,5vw,3.5rem); font-weight:normal; line-height:1; }
    .splash-skip { border:1px solid rgba(255,255,255,.45); border-radius:999px; background:rgba(255,255,255,.1); color:#fff; padding:9px 14px; font:700 .72rem 'Trebuchet MS',sans-serif; cursor:pointer; transition:background .2s ease,transform .2s ease; }
    .splash-skip:hover { background:rgba(255,255,255,.2); transform:translateY(-2px); }
    .welcome-splash.is-splitting .splash-kicker, .welcome-splash.is-splitting .splash-content h1, .welcome-splash.is-splitting .splash-skip { animation:splash-copy-out .35s ease both; }
    @keyframes splash-enter { from { opacity:0; transform:translateY(22px) scale(.94); } to { opacity:1; transform:translateY(0) scale(1); } }
    @keyframes logo-part-left { to { opacity:0; transform:translateX(-44vw) rotate(-9deg) scale(.94); } }
    @keyframes logo-part-right { to { opacity:0; transform:translateX(44vw) rotate(9deg) scale(.94); } }
    @keyframes splash-flare { 35% { transform:translate(-50%,-50%) scale(10); opacity:1; } to { transform:translate(-50%,-50%) scale(24); opacity:0; } }
    @keyframes splash-curtain { to { transform:scaleX(1); } }
    @keyframes splash-copy-out { to { opacity:0; transform:translateY(10px); } }
    body.dark::before { background:radial-gradient(circle at 12% 8%, rgba(154,200,170,.12), transparent 28%), radial-gradient(circle at 88% 18%, rgba(228,185,135,.1), transparent 25%), repeating-linear-gradient(90deg, transparent 0 47px, rgba(154,200,170,.025) 48px, transparent 49px), repeating-linear-gradient(0deg, transparent 0 47px, rgba(228,185,135,.025) 48px, transparent 49px); }
    @media (prefers-reduced-motion: reduce) { body::before, body::after, .hero-project, .hero-project::before, .hero-project::after, .notification-dot, .splash-content, .welcome-splash.is-splitting::after, .welcome-splash.is-splitting .splash-logo-half--left, .welcome-splash.is-splitting .splash-logo-half--right, .welcome-splash.is-splitting .splash-flare, .welcome-splash.is-splitting .splash-kicker, .welcome-splash.is-splitting .splash-content h1, .welcome-splash.is-splitting .splash-skip, .event-item, .gallery-item, .dashboard-grid > .card { animation:none; transition:none; } }
    @media (max-width:900px) { .hero-project { grid-template-columns:1fr; padding:48px 24px; } .gallery-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } .project-cards, .contact-bar, .quiz-shell, .directory-list { grid-template-columns:1fr; } .dashboard-grid { grid-template-columns:minmax(0, 1fr); } .dashboard-grid > .card { min-width:0; } }
    @media (max-width:700px) { .hero-video { display:none; } .hero-photo { display:block; } .hero-backdrop::after { background:linear-gradient(90deg, rgba(16,43,33,.84), rgba(16,43,33,.42)); } .hero-project { min-height:520px; } .hero-logo { display:none; } .gallery-grid { grid-template-columns:1fr; } .menu-bar { left:12px; } .menu-panel { grid-template-columns:1fr; gap:14px; } .menu-bar summary { padding:10px 12px; font-size:0; } .menu-bar summary::before { margin:0; font-size:1rem; } .site-nav { top:14px; left:58px; right:130px; justify-content:flex-start; } .top-actions { top:14px; right:12px; gap:4px; } .top-actions .profile-icon, .top-actions .notification-icon { width:38px; height:38px; } }
    @media (max-width:700px) {
      .dashboard-header { gap:10px; padding:10px 16px; margin:0 -16px; background:rgba(246,240,231,.96); backdrop-filter:blur(16px); -webkit-backdrop-filter:blur(16px); }
      body.dark .dashboard-header { background:rgba(29,41,37,.96); }
      .dashboard-header .page-nav--desktop { display:none; }
      .dashboard-header .mobile-nav { display:block; }
      .dashboard-header .brand { width:clamp(170px, 48vw, 220px); height:52px; }
      .top-actions { position:static; margin-left:auto; gap:4px; }
      .topbar { padding-top:14px; }
    }
  </style>
</head>
<body>
  <?php if ($showWelcomeSplash) : ?>
    <section class="welcome-splash" id="welcomeSplash" aria-label="Welcome to My Generation Loves God">
      <div class="splash-media" aria-hidden="true">
        <video autoplay muted loop playsinline poster="<?php echo htmlspecialchars($splashPhoto); ?>"><source src="<?php echo htmlspecialchars($splashVideo); ?>" type="<?php echo htmlspecialchars('video/' . pathinfo($splashVideo, PATHINFO_EXTENSION)); ?>"></video>
        <img src="<?php echo htmlspecialchars($splashPhoto); ?>" alt="">
      </div>
      <div class="splash-content">
        <div class="splash-mark" role="img" aria-label="My Generation Loves God logo">
          <span class="splash-logo-half splash-logo-half--left" aria-hidden="true"><img src="WhatsApp_Image_2026-06-11_at_10.19.34_AM-removebg-preview.png" alt=""></span>
          <span class="splash-logo-half splash-logo-half--right" aria-hidden="true"><img src="WhatsApp_Image_2026-06-11_at_10.19.34_AM-removebg-preview.png" alt=""></span>
          <span class="splash-flare" aria-hidden="true"></span>
        </div>
        <p class="splash-kicker">Welcome to the movement</p>
        <h1>My Generation Loves God</h1>
        <button class="splash-skip" id="skipSplash" type="button">Skip intro</button>
      </div>
    </section>
  <?php endif; ?>
  <div class="wrap">
    <header class="dashboard-header">
      <a class="brand" href="dashboard.php">My Generation Loves God</a>
      <?php $activeNav = 'home'; include 'site-nav.php'; ?>
      <div class="top-actions">
        <?php if ($isAuthenticated) : ?>
          <a class="profile-icon" href="#account" aria-label="Open my profile" title="My profile"><?php if (!empty($user['profile_picture'])) : ?><img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Your profile picture"><?php else : ?><span aria-hidden="true"><?php echo htmlspecialchars(strtoupper(substr($user['full_name'], 0, 1))); ?></span><?php endif; ?></a>
          <a class="notification-icon<?php echo $unreadNotifications > 0 ? ' has-unread' : ''; ?>" href="notifications.php" aria-label="View notifications<?php echo $unreadNotifications > 0 ? ' (' . $unreadNotifications . ' unread)' : ''; ?>" title="Notifications<?php echo $unreadNotifications > 0 ? ' - ' . $unreadNotifications . ' unread' : ''; ?>"><span aria-hidden="true">&#128276;</span><?php if ($unreadNotifications > 0) : ?><span class="notification-dot" aria-hidden="true"></span><?php endif; ?></a>
        <?php else : ?><a class="btn secondary" href="index.php?login=1">Sign in</a><?php endif; ?>
      </div>
    </header>
    <div class="topbar">
      <div>
        <h2><?php echo $isAuthenticated ? 'Welcome, ' . htmlspecialchars($user['full_name']) : 'My Generation Loves God'; ?></h2>

      </div>
      <div>
        <?php if ($isAuthenticated) : ?>
          <?php if (!empty($user['is_admin'])) : ?><a class="btn" href="admin.php">Admin dashboard</a><?php endif; ?>
          <a class="btn secondary" href="logout.php">Logout</a>
        <?php endif; ?>
      </div>
    </div>

    <section class="hero-project">
      <div class="hero-backdrop" aria-hidden="true">
        <video class="hero-video" autoplay muted loop playsinline poster="<?php echo htmlspecialchars($heroPhotos[0]); ?>">
          <source src="<?php echo htmlspecialchars($splashVideo); ?>" type="<?php echo htmlspecialchars('video/' . pathinfo($splashVideo, PATHINFO_EXTENSION)); ?>">
        </video>
        <img class="hero-photo" src="<?php echo htmlspecialchars($heroPhotos[0]); ?>" alt="">
      </div>
      <div>
        <div class="hero-label">Welcome to My Generation Loves God</div>
        <h1>Igniting a generation for Christ.</h1>
        <p>Grow in faith, connect with the MGLG community, and live out 1 Timothy 4:12 on your campus and in your community.</p>
        <div class="project-links"><a href="about.php">Learn about MGLG</a><a class="secondary" href="#account">View my account</a><?php if ($androidApkAvailable) : ?><a href="downloads/mglg-android.apk" download>Download Android app</a><?php else : ?><span class="android-app-status">Android app download coming soon</span><?php endif; ?></div>
      </div>
      <div class="hero-logo"><img src="WhatsApp_Image_2026-06-11_at_10.19.34_AM-removebg-preview.png" alt="My Generation Loves God" /></div>
    </section>

    <section class="project-section" id="start-here">
      <h3>Start here</h3>
      <div class="project-cards">
        <article class="project-card"><strong>LEARN</strong><h4>About MGLG</h4><p>Discover who we are, our verse, our vision and our mission.</p><a href="about.php">Read about us &rarr;</a></article>
        <article class="project-card"><strong>GROW</strong><h4>Take the Bible quiz</h4><p>Test your knowledge and grow in the Word with a daily quiz.</p><a href="bible-quiz.php">Play today&rsquo;s quiz &rarr;</a></article>
        <article class="project-card"><strong>JOIN</strong><h4>Get involved</h4><p>Volunteer, join a campus chapter, partner or support the movement.</p><a href="get-involved.php">Find your place &rarr;</a></article>
      </div>
    </section>

    <div class="utility-links"><a href="donate.php">Give through M-Pesa</a><a href="newsletter.php">Join newsletter</a><a href="volunteer.php">Volunteer</a></div>

    <section class="project-section">
      <h3>Today&rsquo;s word</h3>
      <div class="project-card"><strong>DAILY VERSE</strong><h4><?php echo date('l, d F Y'); ?></h4><p>&ldquo;<?php echo htmlspecialchars($dailyVerse['text']); ?>&rdquo; &mdash; <?php echo htmlspecialchars($dailyVerse['reference']); ?></p></div>
    </section>

    <section class="event-gallery" id="gallery">
      <div>
        <h3 class="section-title">Upcoming events</h3>
        <div class="event-list">
          <?php if (!$events) : ?><p class="empty-state">New MGLG events will appear here soon.</p><?php endif; ?>
          <?php foreach ($events as $event) : ?><article class="event-item"><time datetime="<?php echo htmlspecialchars(date('c', strtotime($event['event_date']))); ?>"><?php echo htmlspecialchars(date('D, d M Y · H:i', strtotime($event['event_date']))); ?></time><h4><?php echo htmlspecialchars($event['title']); ?></h4><?php if ($event['location']) : ?><p><?php echo htmlspecialchars($event['location']); ?></p><?php endif; ?><?php if ($event['description']) : ?><p><?php echo htmlspecialchars($event['description']); ?></p><?php endif; ?></article><?php endforeach; ?>
        </div>
      </div>
      <div>
        <h3 class="section-title">Life at MGLG</h3>
        <div class="gallery-grid">
          <?php if (!$gallery) : ?><p class="empty-state">Photos from our community will appear here soon.</p><?php endif; ?>
          <?php foreach ($gallery as $photo) : ?><figure class="gallery-item"><?php $mediaPath = (string) $photo['image_path']; $isVideo = in_array(strtolower(pathinfo($mediaPath, PATHINFO_EXTENSION)), ['mp4', 'webm', 'ogv', 'ogg'], true); ?><?php if ($isVideo) : ?><video controls preload="metadata"><source src="<?php echo htmlspecialchars($mediaPath); ?>"><span>Your browser does not support video playback.</span></video><?php else : ?><img src="<?php echo htmlspecialchars($mediaPath); ?>" alt="<?php echo htmlspecialchars($photo['caption'] ?: 'MGLG community photo'); ?>" /><?php endif; ?><?php if ($photo['caption']) : ?><figcaption><?php echo htmlspecialchars($photo['caption']); ?></figcaption><?php endif; ?><a class="save-photo" href="<?php echo htmlspecialchars($mediaPath); ?>" download>Save <?php echo $isVideo ? 'video' : 'photo'; ?></a></figure><?php endforeach; ?>
        </div>
      </div>
    </section>

    <?php if ($directory) : ?><section class="project-section"><h3>Find your community</h3><div class="directory-list"><?php foreach ($directory as $place) : ?><div class="directory-item"><strong><?php echo htmlspecialchars($place['name']); ?></strong><small><?php echo ucfirst(htmlspecialchars($place['category'])); ?> &middot; <?php echo htmlspecialchars($place['location']); ?><?php echo $place['contact'] ? ' &middot; ' . htmlspecialchars($place['contact']) : ''; ?></small></div><?php endforeach; ?></div></section><?php endif; ?>

    <div class="dashboard-grid" id="account">
      <?php if ($isAuthenticated) : ?>
      <div class="card" id="mission">
        <h3 class="section-title">Your profile</h3>
        <div class="profile-card">
          <div class="avatar">
            <?php if (!empty($user['profile_picture'])) : ?>
              <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile picture" />
            <?php else : ?>
              <?php
                $initials = strtoupper(substr($user['full_name'], 0, 1));
                echo htmlspecialchars($initials);
              ?>
            <?php endif; ?>
          </div>
          <div class="profile-details">
            <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
            <p><?php echo htmlspecialchars($user['email']); ?></p>
            <button class="btn secondary" id="toggleProfileEditor" type="button">Edit profile</button>
          </div>
        </div>
        <?php if (!empty($message)) : ?>
          <div class="msg"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div id="profileEditor" class="hidden">
          <form method="post" enctype="multipart/form-data">
            <label>Full name</label>
            <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required />

            <label>Email</label>
            <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled />

            <label>Phone number</label>
            <input type="text" name="phone_number" value="<?php echo htmlspecialchars($user['phone_number']); ?>" required />

            <label>Profile picture</label>
            <input type="file" name="profile_photo" accept="image/*" />

            <label>Are you a student?</label>
            <select name="student_status">
              <option value="yes" <?php echo $user['student_status'] === 'yes' ? 'selected' : ''; ?>>Yes</option>
              <option value="no" <?php echo $user['student_status'] === 'no' ? 'selected' : ''; ?>>No</option>
            </select>

            <label id="institutionLabel">Institution</label>
            <input type="text" name="institution" id="institution" value="<?php echo htmlspecialchars($user['institution'] ?? ''); ?>" />

            <label id="educationLevelLabel">Level of education</label>
            <input type="text" name="level_of_education" id="educationLevel" value="<?php echo htmlspecialchars($user['level_of_education'] ?? ''); ?>" placeholder="e.g. Diploma, Degree, Master's" />

            <label id="courseLabel">Course</label>
            <input type="text" name="course" id="course" value="<?php echo htmlspecialchars($user['course'] ?? ''); ?>" />

            <label id="specializationLabel">Area of specialization</label>
            <input type="text" name="area_of_specialization" id="specialization" value="<?php echo htmlspecialchars($user['area_of_specialization'] ?? ''); ?>" placeholder="e.g. Finance, Teaching, IT" />

            <label id="occupationLabel">Occupation</label>
            <input type="text" name="occupation" id="occupation" value="<?php echo htmlspecialchars($user['occupation'] ?? ''); ?>" />

            <label id="residenceLabel">Area of residence</label>
            <input type="text" name="residence" id="residence" value="<?php echo htmlspecialchars($user['residence'] ?? ''); ?>" />

            <label>Home town</label>
            <input type="text" name="home_town" value="<?php echo htmlspecialchars($user['home_town'] ?? ''); ?>" required />

            <label>Home church</label>
            <input type="text" name="home_church" value="<?php echo htmlspecialchars($user['home_church'] ?? ''); ?>" required />

            <label>Pastor's phone number (optional)</label>
            <input type="tel" name="pastor_phone" value="<?php echo htmlspecialchars($user['pastor_phone'] ?? ''); ?>" />

            <button class="btn" type="submit">Save Changes</button>
          </form>
        </div>
      </div>
      <?php else : ?>
      <div class="card" id="mission">
        <span class="pill">MGLG</span>
        <h3 class="section-title">Join us through</h3>
        <div class="project-links">
          <a class="btn" href="volunteer.php">Join the movement</a>
          <a class="secondary" href="index.php?login=1&amp;mode=register">Create an account</a>
        </div>
      </div>
      <?php endif; ?>

      <div class="card">
        <span class="pill">My Generation Loves God</span>
        <h3 class="section-title">Mission &amp; Vision</h3>
        <div class="info-list">
          <div class="info-item"><strong>Mission:</strong> To reach, disciple, and empower students in colleges and universities across Kenya through evangelism, fellowship, mentorship, and interdenominational unity.</div>
          <div class="info-item"><strong>Vision:</strong> To raise a generation of Christ-centered students who love God deeply and transform campuses, institutions, and nations through the power of the Gospel.</div>
          <div class="info-item"><strong>Contact:</strong> 0720 123 124 / 0775 605 060</div>
          <div class="info-item"><strong>Email:</strong> thegenerationlovesgod@gmail.com</div>
        </div>
      </div>
    </div>

    <section class="contact-bar" id="contact" aria-label="MGLG contact information">
      <div><strong>Talk to us</strong><span class="contact-actions"><a class="call-button" href="tel:0720123124">Call 0720 123 124</a><a class="call-button" href="tel:0775605060">Call 0775 605 060</a></span></div>
      <div><strong>Write to us</strong><span class="contact-actions"><a href="mailto:thegenerationlovesgod@gmail.com">Email MGLG</a></span></div>
      <div><strong>Address &amp; Location</strong>P.O. Box 20100-200, Nairobi<br />Grace and Power Ministry Church<br />Nairobi, Kenya</div>
      <div><strong>Our links</strong><span class="social-links"><?php foreach ($siteLinks as $siteLink) : ?><a href="<?php echo htmlspecialchars($siteLink['url']); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($siteLink['label']); ?></a><?php endforeach; ?></span></div>
    </section>
  </div>

  <script>
    const welcomeSplash = document.getElementById('welcomeSplash');
    const dismissSplash = (immediately = false) => {
      if (!welcomeSplash || welcomeSplash.classList.contains('is-leaving')) return;
      if (immediately || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        welcomeSplash.classList.add('is-leaving');
        window.setTimeout(() => welcomeSplash.remove(), 500);
        return;
      }
      welcomeSplash.classList.add('is-splitting');
      window.setTimeout(() => welcomeSplash.classList.add('is-leaving'), 950);
      window.setTimeout(() => welcomeSplash.remove(), 1500);
    };
    document.getElementById('skipSplash')?.addEventListener('click', () => dismissSplash(true));
    if (welcomeSplash) {
      const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      window.setTimeout(() => dismissSplash(reduceMotion), reduceMotion ? 250 : 2100);
    }
    const heroPhoto = document.querySelector('.hero-photo');
    if (heroPhoto) {
      const heroPhotos = <?php echo json_encode($heroPhotos, JSON_UNESCAPED_SLASHES); ?>;
      let heroPhotoIndex = 0;
      window.setInterval(() => {
        heroPhoto.style.opacity = '0';
        window.setTimeout(() => {
          heroPhotoIndex = (heroPhotoIndex + 1) % heroPhotos.length;
          heroPhoto.src = heroPhotos[heroPhotoIndex];
          heroPhoto.style.opacity = '.65';
        }, 800);
      }, 6500);
    }
    const studentStatus = document.querySelector('select[name="student_status"]');
    const institutionField = document.getElementById('institution');
    const educationLevelField = document.getElementById('educationLevel');
    const courseField = document.getElementById('course');
    const specializationField = document.getElementById('specialization');
    const occupationField = document.getElementById('occupation');
    const residenceField = document.getElementById('residence');
    const institutionLabel = document.getElementById('institutionLabel');
    const educationLevelLabel = document.getElementById('educationLevelLabel');
    const courseLabel = document.getElementById('courseLabel');
    const specializationLabel = document.getElementById('specializationLabel');
    const occupationLabel = document.getElementById('occupationLabel');
    const residenceLabel = document.getElementById('residenceLabel');
    const toggleProfileEditor = document.getElementById('toggleProfileEditor');
    const profileEditor = document.getElementById('profileEditor');

    function toggleFields() {
      if (!studentStatus) return;
      if (studentStatus.value === 'yes') {
        institutionField.style.display = 'block';
        educationLevelField.style.display = 'block';
        courseField.style.display = 'block';
        specializationField.style.display = 'none';
        occupationField.style.display = 'none';
        residenceField.style.display = 'none';
        institutionLabel.style.display = 'block';
        educationLevelLabel.style.display = 'block';
        courseLabel.style.display = 'block';
        specializationLabel.style.display = 'none';
        occupationLabel.style.display = 'none';
        residenceLabel.style.display = 'none';
      } else {
        institutionField.style.display = 'none';
        educationLevelField.style.display = 'none';
        courseField.style.display = 'none';
        specializationField.style.display = 'block';
        occupationField.style.display = 'block';
        residenceField.style.display = 'block';
        institutionLabel.style.display = 'none';
        educationLevelLabel.style.display = 'none';
        courseLabel.style.display = 'none';
        specializationLabel.style.display = 'block';
        occupationLabel.style.display = 'block';
        residenceLabel.style.display = 'block';
      }
    }

    toggleProfileEditor?.addEventListener('click', () => {
      profileEditor.classList.toggle('hidden');
    });

    studentStatus?.addEventListener('change', toggleFields);
    toggleFields();

    const notificationIcon = document.querySelector('.notification-icon');
    const notificationDot = notificationIcon?.querySelector('.notification-dot');
    const notificationLabel = 'View notifications';
    async function refreshNotificationIndicator() {
      if (!notificationIcon) return;
      try {
        const response = await fetch('notification_count.php', { credentials: 'same-origin', cache: 'no-store' });
        if (!response.ok) return;
        const data = await response.json();
        const unread = Number(data.unread) || 0;
        notificationIcon.classList.toggle('has-unread', unread > 0);
        notificationIcon.setAttribute('aria-label', unread > 0 ? `${notificationLabel} (${unread} unread)` : notificationLabel);
        notificationIcon.title = unread > 0 ? `Notifications - ${unread} unread` : 'Notifications';
        if (unread > 0 && !notificationIcon.querySelector('.notification-dot')) {
          const dot = document.createElement('span');
          dot.className = 'notification-dot';
          dot.setAttribute('aria-hidden', 'true');
          notificationIcon.appendChild(dot);
        } else if (unread === 0) {
          notificationIcon.querySelector('.notification-dot')?.remove();
        }
      } catch (error) {
        // Keep the current indicator if the background check is unavailable.
      }
    }
    refreshNotificationIndicator();
    window.setInterval(refreshNotificationIndicator, 15000);
  </script>
</body>
</html>
