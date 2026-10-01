<?php
require_once 'db.php';
require_once 'mail.php';

header('Content-Type: application/json');

function callbackItemValue(array $callback, string $name): ?string
{
    foreach (($callback['CallbackMetadata']['Item'] ?? []) as $item) {
        if (($item['Name'] ?? '') === $name && isset($item['Value'])) {
            return (string) $item['Value'];
        }
    }
    return null;
}

function createMemberCode(PDO $pdo): string
{
    do {
        $code = 'MGLG-' . strtoupper(bin2hex(random_bytes(4)));
        $check = $pdo->prepare('SELECT 1 FROM users WHERE member_code = ? LIMIT 1');
        $check->execute([$code]);
    } while ($check->fetchColumn());
    return $code;
}

$payload = json_decode(file_get_contents('php://input'), true);
$callback = $payload['Body']['stkCallback'] ?? null;

if (!is_array($callback) || empty($callback['CheckoutRequestID'])) {
    http_response_code(400);
    echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Invalid callback payload']);
    exit;
}

try {
    $pdo = initializeDatabase();
    $donationStmt = $pdo->prepare('SELECT * FROM donations WHERE checkout_request_id = ? FOR UPDATE');
    $donationStmt->execute([$callback['CheckoutRequestID']]);
    $donation = $donationStmt->fetch();
    if ($donation) {
        $status = (int) ($callback['ResultCode'] ?? 1) === 0 ? 'paid' : 'failed';
        $receipt = callbackItemValue($callback, 'MpesaReceiptNumber');
        $paidAmount = callbackItemValue($callback, 'Amount');
        if ($status === 'paid' && ($paidAmount === null || (int) $paidAmount !== (int) $donation['amount'])) {
            $status = 'failed';
        }
        $stmt = $pdo->prepare('UPDATE donations SET status = ?, mpesa_receipt = ? WHERE id = ?');
        $stmt->execute([$status, $receipt, $donation['id']]);
        echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Donation status recorded']);
        exit;
    }
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('SELECT * FROM pending_registrations WHERE checkout_request_id = ? FOR UPDATE');
    $stmt->execute([$callback['CheckoutRequestID']]);
    $pending = $stmt->fetch();

    if (!$pending || $pending['status'] !== 'pending') {
        $pdo->commit();
        echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Callback already processed']);
        exit;
    }

    if ((int) ($callback['ResultCode'] ?? 1) !== 0) {
        $stmt = $pdo->prepare('UPDATE pending_registrations SET status = ? WHERE id = ?');
        $stmt->execute(['failed', $pending['id']]);
        $pdo->commit();
        echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Payment failure recorded']);
        exit;
    }

    $paidAmount = callbackItemValue($callback, 'Amount');
    if ($paidAmount === null || (int) $paidAmount !== (int) $pending['amount']) {
        $stmt = $pdo->prepare('UPDATE pending_registrations SET status = ? WHERE id = ?');
        $stmt->execute(['failed', $pending['id']]);
        $pdo->commit();
        echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Unexpected payment amount recorded']);
        exit;
    }

    $registration = json_decode($pending['registration_data'], true);
    if (!is_array($registration)) {
        throw new RuntimeException('Invalid pending registration data.');
    }

    $memberCode = createMemberCode($pdo);
    $adminEmail = strtolower(trim(getenv('ADMIN_EMAIL') ?: ''));
    $isAdmin = $adminEmail !== '' && strtolower($registration['email']) === $adminEmail ? 1 : 0;
    $stmt = $pdo->prepare('INSERT INTO users (full_name, email, phone_number, student_status, institution, course, occupation, residence, home_town, home_church, pastor_phone, password_hash, member_code, is_admin) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $registration['full_name'], $registration['email'], $registration['phone_number'],
        $registration['student_status'], $registration['institution'], $registration['course'] ?? null, $registration['occupation'] ?? null, $registration['residence'],
        $registration['home_town'] ?? null, $registration['home_church'] ?? null, $registration['pastor_phone'] ?? null,
        $registration['password_hash'], $memberCode, $isAdmin,
    ]);

    $receipt = callbackItemValue($callback, 'MpesaReceiptNumber');
    $stmt = $pdo->prepare('UPDATE pending_registrations SET status = ?, mpesa_receipt = ? WHERE id = ?');
    $stmt->execute(['paid', $receipt, $pending['id']]);
    $pdo->commit();

    $welcomeMessage = "Hello {$registration['full_name']},\n\n"
        . "Welcome to My Generation Loves God! We are glad you have joined our community.\n\n"
        . "Your unique member code is: {$memberCode}\n\n"
        . "You can sign in here: http://localhost/login/index.php\n\n"
        . "We look forward to growing in faith, building meaningful community, and living with purpose together.\n\n"
        . "Blessings,\nMy Generation Loves God";
    if (!sendAppMail($registration['email'], 'Welcome to My Generation Loves God', $welcomeMessage)) {
        error_log('Welcome email could not be sent to ' . $registration['email']);
    }

    echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Payment and registration completed']);
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('M-Pesa callback error: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Callback processing failed']);
}
