<?php
session_start();
require_once 'db.php';
require_once 'mpesa.php';

function redirectToDashboard()
{
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'register') {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phoneNumber = trim($_POST['phone_number'] ?? '');
        $studentStatus = $_POST['student_status'] ?? 'yes';
        $institution = trim($_POST['institution'] ?? '');
        $course = trim($_POST['course'] ?? '');
        $occupation = trim($_POST['occupation'] ?? '');
        $residence = trim($_POST['residence'] ?? '');
        $homeTown = trim($_POST['home_town'] ?? '');
        $homeChurch = trim($_POST['home_church'] ?? '');
        $pastorPhone = trim($_POST['pastor_phone'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($fullName === '' || $email === '' || $phoneNumber === '' || $password === '') {
            $_SESSION['error'] = 'Please fill in all required fields.';
            header('Location: index.php?login=1');
            exit;
        }

        try {
            $pdo = getDbConnection();
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? UNION SELECT id FROM pending_registrations WHERE email = ? AND status = ? LIMIT 1');
            $stmt->execute([$email, $email, 'pending']);
            if ($stmt->fetch()) {
                throw new RuntimeException('An account or pending payment already exists for this email.');
            }

            $amount = $studentStatus === 'yes' ? 200 : 500;
            $payment = initiateMpesaStkPush($phoneNumber, $amount, 'MGLG-' . substr(md5($email . microtime(true)), 0, 10));
            $registrationData = json_encode([
                'full_name' => $fullName,
                'email' => $email,
                'phone_number' => $phoneNumber,
                'student_status' => $studentStatus,
                'institution' => $institution,
                'course' => $course,
                'occupation' => $occupation,
                'residence' => $residence,
                'home_town' => $homeTown,
                'home_church' => $homeChurch,
                'pastor_phone' => $pastorPhone,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);
            $stmt = $pdo->prepare('INSERT INTO pending_registrations (checkout_request_id, email, amount, registration_data) VALUES (?, ?, ?, ?)');
            $stmt->execute([$payment['CheckoutRequestID'], $email, $amount, $registrationData]);
            $_SESSION['success'] = 'STK prompt sent. Enter your M-Pesa PIN to pay KSh ' . $amount . '. Your account will be created after payment is confirmed.';
            header('Location: index.php?login=1');
            exit;
        } catch (Exception $e) {
            $_SESSION['error'] = 'Registration failed: ' . $e->getMessage();
            header('Location: index.php?login=1');
            exit;
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'login') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            $_SESSION['error'] = 'Please enter your email and password.';
            header('Location: index.php?login=1');
            exit;
        }

        try {
            $pdo = getDbConnection();
            $stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['show_welcome_splash'] = true;
                redirectToDashboard();
            }

            $_SESSION['error'] = 'Invalid email or password.';
            header('Location: index.php?login=1');
            exit;
        } catch (Exception $e) {
            $_SESSION['error'] = 'Login failed: ' . $e->getMessage();
            header('Location: index.php?login=1');
            exit;
        }
    }
}

header('Location: index.php?login=1');
exit;
