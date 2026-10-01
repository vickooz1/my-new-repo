<?php

if (is_file(__DIR__ . '/mail-config.php')) {
    require_once __DIR__ . '/mail-config.php';
}

require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/Exception.php';
require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;

function sendAppMail(string $to, string $subject, string $body, array $bcc = []): bool
{
    $username = trim((string) (getenv('SMTP_USERNAME') ?: (defined('SMTP_USERNAME') ? SMTP_USERNAME : '')));
    $password = (string) (getenv('SMTP_PASSWORD') ?: (defined('SMTP_PASSWORD') ? SMTP_PASSWORD : ''));
    $sender = trim((string) (getenv('MAIL_FROM') ?: (defined('MAIL_FROM') ? MAIL_FROM : $username)));

    if (!filter_var($to, FILTER_VALIDATE_EMAIL) || !filter_var($sender, FILTER_VALIDATE_EMAIL) || $username === '' || $password === '') {
        error_log('SMTP mail failed: SMTP_USERNAME, SMTP_PASSWORD, MAIL_FROM, or recipient address is missing or invalid.');
        return false;
    }

    $mailer = new PHPMailer(true);
    $mailer->isSMTP();
    $mailer->Host = getenv('SMTP_HOST') ?: (defined('SMTP_HOST') ? SMTP_HOST : 'smtp.gmail.com');
    $mailer->Port = (int) (getenv('SMTP_PORT') ?: (defined('SMTP_PORT') ? SMTP_PORT : 587));
    $mailer->SMTPAuth = true;
    $mailer->Username = $username;
    $mailer->Password = $password;
    $mailer->SMTPSecure = strtolower((string) (getenv('SMTP_ENCRYPTION') ?: (defined('SMTP_ENCRYPTION') ? SMTP_ENCRYPTION : 'tls'))) === 'ssl'
        ? PHPMailer::ENCRYPTION_SMTPS
        : PHPMailer::ENCRYPTION_STARTTLS;
    $mailer->CharSet = 'UTF-8';
    $mailer->setFrom($sender, getenv('MAIL_FROM_NAME') ?: (defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'My Generation Loves God'));
    $mailer->addAddress($to);

    foreach ($bcc as $address) {
        if (filter_var($address, FILTER_VALIDATE_EMAIL) && strcasecmp($address, $to) !== 0) {
            $mailer->addBCC($address);
        }
    }

    $mailer->Subject = $subject;
    $mailer->Body = wordwrap($body, 70);
    $mailer->isHTML(false);

    try {
        return $mailer->send();
    } catch (Throwable $exception) {
        error_log('SMTP mail failed: ' . $exception->getMessage());
        return false;
    }
}
