<?php
$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';
$dbName = getenv('DB_NAME') ?: 'login_system';
$dbPort = getenv('DB_PORT') ?: '3306';

require_once __DIR__ . '/mail.php';

function getDbConnection()
{
    global $dbHost, $dbUser, $dbPass, $dbName, $dbPort;

    $pdo = new PDO("mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
}

function initializeDatabase()
{
    $pdo = getDbConnection();
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            phone_number VARCHAR(20) NOT NULL,
            student_status ENUM('yes', 'no') NOT NULL DEFAULT 'yes',
            institution VARCHAR(150) DEFAULT NULL,
            level_of_education VARCHAR(100) DEFAULT NULL,
            course VARCHAR(150) DEFAULT NULL,
            area_of_specialization VARCHAR(150) DEFAULT NULL,
            occupation VARCHAR(150) DEFAULT NULL,
            residence VARCHAR(150) DEFAULT NULL,
            home_town VARCHAR(150) DEFAULT NULL,
            home_church VARCHAR(150) DEFAULT NULL,
            pastor_phone VARCHAR(20) DEFAULT NULL,
            password_hash VARCHAR(255) NOT NULL,
            member_code VARCHAR(30) DEFAULT NULL,
            is_admin TINYINT(1) NOT NULL DEFAULT 0,
            profile_picture VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS daily_verse_mail_log (
            verse_date DATE NOT NULL PRIMARY KEY,
            status ENUM('sent', 'failed') NOT NULL,
            attempts INT UNSIGNED NOT NULL DEFAULT 0,
            last_error VARCHAR(255) DEFAULT NULL,
            sent_at TIMESTAMP NULL DEFAULT NULL,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS pending_registrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            checkout_request_id VARCHAR(100) NOT NULL UNIQUE,
            email VARCHAR(100) NOT NULL,
            amount INT NOT NULL,
            registration_data JSON NOT NULL,
            mpesa_receipt VARCHAR(100) DEFAULT NULL,
            status ENUM('pending', 'paid', 'failed') NOT NULL DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(150) NOT NULL,
            description TEXT DEFAULT NULL,
            event_date DATETIME NOT NULL,
            location VARCHAR(150) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS gallery (
            id INT AUTO_INCREMENT PRIMARY KEY,
            image_path VARCHAR(255) NOT NULL,
            caption VARCHAR(150) DEFAULT NULL,
            category VARCHAR(30) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
    try {
        $pdo->exec("ALTER TABLE gallery ADD COLUMN category VARCHAR(30) DEFAULT NULL AFTER caption");
    } catch (PDOException $exception) {
        // Existing installations already have this column.
    }
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(150) NOT NULL,
            message TEXT NOT NULL,
            read_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (user_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
    $notificationColumns = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notifications'")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('read_at', $notificationColumns, true)) {
        $pdo->exec('ALTER TABLE notifications ADD COLUMN read_at TIMESTAMP NULL DEFAULT NULL AFTER message');
    }
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS opportunities (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            post_type ENUM('seeking', 'offering') NOT NULL,
            category ENUM('attachment', 'job', 'idea', 'other') NOT NULL,
            title VARCHAR(150) NOT NULL,
            description TEXT NOT NULL,
            contact_email VARCHAR(100) NOT NULL,
            location VARCHAR(150) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (post_type, category, created_at),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS bible_quiz_attempts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            quiz_date DATE NOT NULL,
            score TINYINT UNSIGNED NOT NULL,
            total_questions TINYINT UNSIGNED NOT NULL DEFAULT 10,
            answers JSON NOT NULL,
            completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_daily_quiz (user_id, quiz_date),
            INDEX (user_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $pdo->exec("CREATE TABLE IF NOT EXISTS newsletter_subscribers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(100) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS volunteers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        phone_number VARCHAR(20) NOT NULL,
        interest VARCHAR(150) NOT NULL,
        offer TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $volunteerColumns = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'volunteers'")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('offer', $volunteerColumns, true)) {
        $pdo->exec('ALTER TABLE volunteers ADD COLUMN offer TEXT DEFAULT NULL AFTER interest');
    }
    $pdo->exec("CREATE TABLE IF NOT EXISTS directory (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        category ENUM('church', 'campus') NOT NULL,
        location VARCHAR(150) NOT NULL,
        contact VARCHAR(100) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS password_reset_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token_hash CHAR(64) NOT NULL UNIQUE,
        expires_at DATETIME NOT NULL,
        used_at DATETIME DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (user_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS donations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        checkout_request_id VARCHAR(100) NOT NULL UNIQUE,
        phone_number VARCHAR(20) NOT NULL,
        amount INT NOT NULL,
        account_reference VARCHAR(50) NOT NULL,
        mpesa_receipt VARCHAR(100) DEFAULT NULL,
        status ENUM('pending', 'paid', 'failed') NOT NULL DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Apply non-destructive upgrades for databases created by earlier versions.
    $columns = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('member_code', $columns, true)) {
        $pdo->exec('ALTER TABLE users ADD COLUMN member_code VARCHAR(30) DEFAULT NULL AFTER password_hash');
    }
    if (!in_array('is_admin', $columns, true)) {
        $pdo->exec('ALTER TABLE users ADD COLUMN is_admin TINYINT(1) NOT NULL DEFAULT 0 AFTER member_code');
    }
    $userColumns = [
        'level_of_education' => 'VARCHAR(100) DEFAULT NULL AFTER institution',
        'course' => 'VARCHAR(150) DEFAULT NULL AFTER institution',
        'area_of_specialization' => 'VARCHAR(150) DEFAULT NULL AFTER course',
        'occupation' => 'VARCHAR(150) DEFAULT NULL AFTER course',
        'home_town' => 'VARCHAR(150) DEFAULT NULL AFTER residence',
        'home_church' => 'VARCHAR(150) DEFAULT NULL AFTER home_town',
        'pastor_phone' => 'VARCHAR(20) DEFAULT NULL AFTER home_church',
    ];
    foreach ($userColumns as $column => $definition) {
        if (!in_array($column, $columns, true)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN `$column` $definition");
        }
    }
    $pendingColumns = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pending_registrations'")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('mpesa_receipt', $pendingColumns, true)) {
        $pdo->exec('ALTER TABLE pending_registrations ADD COLUMN mpesa_receipt VARCHAR(100) DEFAULT NULL AFTER registration_data');
    }
    $indexes = $pdo->query("SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('uniq_users_member_code', $indexes, true)) {
        $pdo->exec('ALTER TABLE users ADD UNIQUE KEY uniq_users_member_code (member_code)');
    }

    // Keep the organisation administrator available without requiring paid registration.
    $adminEmail = strtolower(trim(getenv('ADMIN_EMAIL') ?: 'thegenerationlovesgod@gmail.com'));
    $adminPasswordHash = password_hash(getenv('ADMIN_PASSWORD') ?: 'MygenerationlovesGod254', PASSWORD_DEFAULT);
    $adminCode = 'MGLG-ADMIN';
    $stmt = $pdo->prepare('SELECT id FROM users WHERE LOWER(email) = ? LIMIT 1');
    $stmt->execute([$adminEmail]);
    if ($stmt->fetchColumn()) {
        $stmt = $pdo->prepare('UPDATE users SET password_hash = ?, member_code = ?, is_admin = 1 WHERE LOWER(email) = ?');
        $stmt->execute([$adminPasswordHash, $adminCode, $adminEmail]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO users (full_name, email, phone_number, password_hash, member_code, is_admin) VALUES (?, ?, ?, ?, ?, 1)');
        $stmt->execute(['My Generation Loves God Admin', $adminEmail, 'N/A', $adminPasswordHash, $adminCode]);
    }

    return $pdo;
}

function notifyMembers(PDO $pdo, string $title, string $message): void
{
    $members = $pdo->query('SELECT id, email FROM users WHERE is_admin = 0')->fetchAll();
    if (!$members) {
        return;
    }

    $stmt = $pdo->prepare('INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)');
    $emails = [];
    foreach ($members as $member) {
        $stmt->execute([(int) $member['id'], $title, $message]);
        if (filter_var($member['email'], FILTER_VALIDATE_EMAIL)) {
            $emails[] = $member['email'];
        }
    }

    if ($emails) {
        sendAppMail($emails[0], $title, $message, array_slice($emails, 1));
    }
}

function notifyNewMonthUsers(PDO $pdo): void
{
    $today = new DateTimeImmutable('today');
    if ($today->format('j') !== '1') {
        return;
    }

    $monthStart = $today->format('Y-m-01');
    $nextMonth = $today->modify('first day of next month')->format('Y-m-d');
    $title = 'Happy New Month';
    $existing = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE title = ? AND created_at >= ? AND created_at < ?');
    $existing->execute([$title, $monthStart, $nextMonth]);
    if ((int) $existing->fetchColumn() > 0) {
        return;
    }

    $message = 'Welcome to ' . $today->format('F Y') . '. May God guide you through a month of growth, purpose, and fellowship.';
    $users = $pdo->query('SELECT id FROM users')->fetchAll(PDO::FETCH_COLUMN);
    $notification = $pdo->prepare('INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)');
    foreach ($users as $userId) {
        $notification->execute([(int) $userId, $title, $message]);
    }
}

function notifyDailyWord(PDO $pdo): bool
{
    $dailyVerse = getDailyVerse();
    $title = "Today's word";
    $message = '"' . $dailyVerse['text'] . '" - ' . $dailyVerse['reference'];

    // Keep the in-app notification, but do not let a failed SMTP attempt prevent
    // later retries on the same day.
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE title = ? AND message = ? AND DATE(created_at) = CURDATE()');
    $stmt->execute([$title, $message]);
    if ((int) $stmt->fetchColumn() === 0) {
        $members = $pdo->query('SELECT id FROM users WHERE is_admin = 0')->fetchAll();
        $notification = $pdo->prepare('INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)');
        foreach ($members as $member) {
            $notification->execute([(int) $member['id'], $title, $message]);
        }
    }

    $delivery = $pdo->prepare("SELECT status FROM daily_verse_mail_log WHERE verse_date = CURDATE()");
    $delivery->execute();
    if ($delivery->fetchColumn() === 'sent') {
        return true;
    }

    $emails = $pdo->query("SELECT email FROM users WHERE is_admin = 0 AND email <> ''")->fetchAll(PDO::FETCH_COLUMN);
    $emails = array_values(array_filter($emails, static fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL)));
    if (!$emails) {
        return true;
    }

    $sent = sendAppMail($emails[0], $title, $message, array_slice($emails, 1));
    $status = $sent ? 'sent' : 'failed';
    $error = $sent ? null : 'SMTP delivery failed; see the PHP error log.';
    $log = $pdo->prepare(
        "INSERT INTO daily_verse_mail_log (verse_date, status, attempts, last_error, sent_at)
         VALUES (CURDATE(), ?, 1, ?, CASE WHEN ? = 'sent' THEN CURRENT_TIMESTAMP ELSE NULL END)
         ON DUPLICATE KEY UPDATE status = VALUES(status), attempts = attempts + 1,
            last_error = VALUES(last_error), sent_at = CASE WHEN VALUES(status) = 'sent' THEN CURRENT_TIMESTAMP ELSE sent_at END"
    );
    $log->execute([$status, $error, $status]);
    return $sent;
}

function getDailyVerse(): array
{
    $verses = [
        ['text' => 'Trust in the Lord with all your heart and lean not on your own understanding.', 'reference' => 'Proverbs 3:5'],
        ['text' => 'I can do all things through Christ who strengthens me.', 'reference' => 'Philippians 4:13'],
        ['text' => 'The Lord is my shepherd; I shall not want.', 'reference' => 'Psalm 23:1'],
        ['text' => 'Be strong and courageous. Do not be afraid; do not be discouraged, for the Lord your God will be with you wherever you go.', 'reference' => 'Joshua 1:9'],
        ['text' => 'Cast all your anxiety on him because he cares for you.', 'reference' => '1 Peter 5:7'],
        ['text' => 'This is the day the Lord has made; let us rejoice and be glad in it.', 'reference' => 'Psalm 118:24'],
        ['text' => 'The Lord is good, a refuge in times of trouble. He cares for those who trust in him.', 'reference' => 'Nahum 1:7'],
    ];

    $verseIndex = ((int) hexdec(substr(sha1(date('Y-m-d')), 0, 8)) + 1) % count($verses);
    return $verses[$verseIndex];
}
