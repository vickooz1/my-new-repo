<?php

// Copy this file to mail-config.php on the server and replace the placeholders.
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-sender@gmail.com');
define('SMTP_PASSWORD', 'your-gmail-app-password');
define('SMTP_ENCRYPTION', 'tls');
define('MAIL_FROM', 'your-sender@gmail.com');
define('MAIL_FROM_NAME', 'My Generation Loves God');
// Use a long random value. It protects the scheduled daily-verse URL.
define('DAILY_VERSE_CRON_TOKEN', 'replace-with-a-long-random-secret');
