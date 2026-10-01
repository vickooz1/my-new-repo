<?php
session_start();
require_once 'db.php';

$message = '';
if (isset($_SESSION['error'])) {
  $message = $_SESSION['error'];
  unset($_SESSION['error']);
}

if (isset($_SESSION['success'])) {
  $message = $_SESSION['success'];
  unset($_SESSION['success']);
}

if (isset($_SESSION['user_id'])) {
  header('Location: dashboard.php');
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head><link rel="icon" type="image/png" href="assets/mglg-favicon.png">
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>My Generation Loves God</title>
  <style>
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: Inter, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      background: radial-gradient(circle at 12% 8%, rgba(111,177,140,.22), transparent 30%), linear-gradient(135deg,#f8f5ef,#f2f7f2 55%,#f5f0e7);
      color: #18241f;
      min-height: 100vh;
      overflow-x: hidden;
    }
    .scene { position:fixed; inset:0; z-index:0; overflow:hidden; pointer-events:none; perspective:900px; }
    .scene::before { content:""; position:absolute; inset:-30%; background:radial-gradient(circle at 50% 50%, rgba(111,177,140,.18), transparent 33%); filter:blur(24px); animation:glow-drift 12s ease-in-out infinite alternate; }
    .scene-grid { position:absolute; width:160%; height:72%; left:-30%; bottom:-42%; background-image:linear-gradient(rgba(31,97,74,.10) 1px, transparent 1px), linear-gradient(90deg, rgba(31,97,74,.10) 1px, transparent 1px); background-size:52px 52px; transform:rotateX(64deg) rotateZ(-7deg); transform-origin:center top; mask-image:linear-gradient(to bottom, transparent, #000 42%, transparent 92%); }
    .orb { position:absolute; border-radius:50%; background:linear-gradient(135deg, rgba(255,255,255,.88), rgba(111,177,140,.16) 45%, rgba(199,138,45,.08)); box-shadow:inset 12px 12px 22px rgba(255,255,255,.7), inset -16px -16px 28px rgba(31,97,74,.08), 0 24px 54px rgba(22,49,38,.12); backdrop-filter:blur(2px); animation:float 9s ease-in-out infinite; }
    .orb-one { width:260px; height:260px; top:7%; left:7%; }
    .orb-two { width:150px; height:150px; right:9%; top:17%; animation-delay:-3s; }
    .orb-three { width:210px; height:210px; right:16%; bottom:4%; animation-delay:-6s; }
    @keyframes float { 0%,100% { transform:translate3d(0,0,0) rotate(0deg); } 50% { transform:translate3d(0,-24px,38px) rotate(9deg); } }
    @keyframes glow-drift { from { transform:translate3d(-4%,-3%,0) scale(.9); } to { transform:translate3d(5%,4%,0) scale(1.15); } }
    .login-page { position:relative; z-index:1; display:grid; place-items:center; min-height:100vh; padding:24px; }
    .login-card { width:min(420px, 100%); background:rgba(255,250,242,.94); border:1px solid rgba(31,97,74,.16); border-radius:18px; padding:36px 32px; box-shadow:0 24px 70px rgba(22,49,38,.12); backdrop-filter:blur(18px); }
    .logo { font-size:2rem; font-weight:700; letter-spacing:-0.04em; margin-bottom:10px; }
    .subtitle { margin:0 0 28px; color:#65716a; line-height:1.6; }
    .login-form { display:grid; gap:18px; }
    .hidden-form { display:none; }
    .login-form label, .login-form legend { display:block; font-size:0.9rem; margin-bottom:8px; color:#315c4a; }
    .login-form input, .login-form select { width:100%; border:1px solid rgba(31,97,74,.2); border-radius:10px; background:#fff; color:#18241f; padding:14px 16px; font-size:1rem; outline:none; }
    .student-field { border:1px solid rgba(31,97,74,.18); border-radius:10px; padding:12px 14px; display:grid; gap:10px; }
    .radio-option { display:flex; align-items:center; gap:8px; color:#315c4a; }
    .conditional-field { display:grid; gap:8px; }
    .hidden { display:none; }
    .login-form input:focus, .login-form select:focus { border-color:#1f614a; background:#f7fbf8; }
    .login-form button { width:100%; padding:14px 16px; border:none; border-radius:10px; background:#633e25; color:#fff; font-size:1rem; font-weight:600; cursor:pointer; }
    .login-footer { margin-top:24px; display:flex; justify-content:space-between; align-items:center; font-size:0.95rem; color:#65716a; }
    .login-footer a { color:#633e25; text-decoration:none; }
    .login-footer a:hover { text-decoration:underline; }
    .alert { padding:10px 12px; border-radius:10px; margin-bottom:16px; background:#e2efe7; color:#18241f; }
    .payment-note { display:grid; gap:4px; padding:12px; border:1px solid rgba(31,97,74,.18); border-radius:12px; color:#315c4a; font-size:0.88rem; line-height:1.4; }
    .logo-box { text-align:center; margin-bottom:12px; }
    .logo-box img { width:120px; height:auto; border-radius:16px; }
    @media (max-width:480px) { .login-card { padding:28px 22px; } }
    @media (prefers-reduced-motion:reduce) { .scene::before, .orb { animation:none; } }
  </style>
</head>
<body>
  <div class="scene" aria-hidden="true">
    <div class="scene-grid"></div>
    <div class="orb orb-one"></div>
    <div class="orb orb-two"></div>
    <div class="orb orb-three"></div>
  </div>
  <main class="login-page">
    <div class="login-card">
      <div class="logo-box">
        <?php include 'logo.php'; ?>
      </div>
      <div class="logo">My Generation Loves God</div>
      <p class="subtitle" id="formSubtitle">Welcome back. Please log in to continue.</p>

      <?php if (!empty($message)) : ?>
        <div class="alert"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <form id="loginForm" class="login-form active-form" action="auth.php" method="post" autocomplete="off">
        <input type="hidden" name="action" value="login" />
        <label for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="Enter your Gmail" required />

        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Enter your password" required minlength="6" />

        <button type="submit">Sign In</button>
        <a href="reset_request.php" style="text-align:right;color:#d3c9ff;text-decoration:none;font-size:.9rem;">Forgot password?</a>
      </form>

      <form id="registerForm" class="login-form hidden-form" action="auth.php" method="post" autocomplete="off">
        <input type="hidden" name="action" value="register" />
        <label for="fullName">Full Name</label>
        <input type="text" id="fullName" name="full_name" placeholder="Enter your full name" required />

        <label for="regEmail">Email</label>
        <input type="email" id="regEmail" name="email" placeholder="Enter your email" required />

        <label for="phoneNumber">Phone Number</label>
        <input type="tel" id="phoneNumber" name="phone_number" placeholder="Enter your phone number" required />

        <label for="password">Password</label>
        <input type="password" id="regPassword" name="password" placeholder="Create a password" required minlength="6" />

        <fieldset class="student-field">
          <legend>Are you a student?</legend>
          <label class="radio-option">
            <input type="radio" name="student_status" value="yes" checked />
            Yes
          </label>
          <label class="radio-option">
            <input type="radio" name="student_status" value="no" />
            No
          </label>
        </fieldset>

        <div id="institutionGroup" class="conditional-field">
          <label for="institution">Institution</label>
          <input type="text" id="institution" name="institution" placeholder="Enter your institution" />
          <label for="course">Course</label>
          <input type="text" id="course" name="course" placeholder="Enter your course" />
        </div>

        <div id="residenceGroup" class="conditional-field hidden">
          <label for="occupation">Occupation</label>
          <input type="text" id="occupation" name="occupation" placeholder="Enter your occupation" />
          <label for="residence">Area of Residence</label>
          <input type="text" id="residence" name="residence" placeholder="Enter your area of residence" />
        </div>

        <label for="homeTown">Home Town</label>
        <input type="text" id="homeTown" name="home_town" placeholder="Enter your home town" required />

        <label for="homeChurch">Home Church</label>
        <input type="text" id="homeChurch" name="home_church" placeholder="Enter your home church" required />

        <label for="pastorPhone">Pastor's Phone Number <span style="font-weight:normal;color:#a8a4cb;">(optional)</span></label>
        <input type="tel" id="pastorPhone" name="pastor_phone" placeholder="Enter pastor's phone number" />

        <div class="payment-note">
          <strong id="feeLabel">Registration fee: KSh 200</strong>
          <span></span>
        </div>

        <button type="submit">Pay and Create Account</button>
      </form>

      <div class="login-footer">
        <span id="toggleText">Not registered?</span>
        <a href="#" id="toggleLink">Create account</a>
      </div>
    </div>
  </main>

  <script>
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    const toggleLink = document.getElementById('toggleLink');
    const toggleText = document.getElementById('toggleText');
    const formSubtitle = document.getElementById('formSubtitle');
    const studentInputs = document.querySelectorAll('input[name="student_status"]');
    const institutionGroup = document.getElementById('institutionGroup');
    const residenceGroup = document.getElementById('residenceGroup');
    const institutionField = document.getElementById('institution');
    const courseField = document.getElementById('course');
    const occupationField = document.getElementById('occupation');
    const residenceField = document.getElementById('residence');
    const feeLabel = document.getElementById('feeLabel');

    function switchForm(mode) {
      if (mode === 'register') {
        loginForm.classList.add('hidden-form');
        registerForm.classList.remove('hidden-form');
        toggleText.textContent = 'Already have an account?';
        toggleLink.textContent = 'Sign in';
        formSubtitle.textContent = 'Create your account to get started.';
      } else {
        registerForm.classList.add('hidden-form');
        loginForm.classList.remove('hidden-form');
        toggleText.textContent = 'Not registered?';
        toggleLink.textContent = 'Create account';
        formSubtitle.textContent = 'Welcome back. Please log in to continue.';
      }
    }

    function toggleStudentFields() {
      const selectedValue = document.querySelector('input[name="student_status"]:checked')?.value;

      if (selectedValue === 'yes') {
        institutionGroup.classList.remove('hidden');
        residenceGroup.classList.add('hidden');
        institutionField.required = true;
        courseField.required = true;
        occupationField.required = false;
        residenceField.required = false;
        feeLabel.textContent = 'Registration fee: KSh 200';
      } else {
        institutionGroup.classList.add('hidden');
        residenceGroup.classList.remove('hidden');
        institutionField.required = false;
        courseField.required = false;
        occupationField.required = true;
        residenceField.required = true;
        feeLabel.textContent = 'Registration fee: KSh 500';
      }
    }

    toggleLink.addEventListener('click', (event) => {
      event.preventDefault();
      const mode = registerForm.classList.contains('hidden-form') ? 'register' : 'login';
      switchForm(mode);
    });

    studentInputs.forEach((input) => {
      input.addEventListener('change', toggleStudentFields);
    });

    toggleStudentFields();
    if (new URLSearchParams(window.location.search).get('mode') === 'register') {
      switchForm('register');
    }
  </script>
</body>
</html>
