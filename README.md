# Local Docker Setup

1. Copy `.env.example` to `.env` and change the local database and admin passwords.
2. Build and start the app and database:

```powershell
Copy-Item .env.example .env
docker compose up --build
```

Open http://localhost:8080 (or the port set by `APP_PORT`). MySQL imports `database.sql` when its data volume is first created; uploaded profile and gallery media are kept in separate Docker volumes. Set SMTP and M-Pesa values in `.env` to enable those integrations.

Stop the services with `docker compose down`. To also erase the local database and uploaded media, run `docker compose down --volumes`.

# InfinityFree Deployment Guide

This project is ready to be uploaded to InfinityFree.

## 1. Upload your files
Upload the contents of this folder to your InfinityFree account's public_html folder.

If your project is inside a subfolder, the final URL will be:
- https://yourdomain.com/login/index.php

## 2. Create the MySQL database
In InfinityFree, go to the control panel and create a MySQL database.

Use the exact details from your hosting account. Do not assume the host is `localhost`; some providers give a remote hostname such as `sql123.example.com`.
- Database host: the host shown by your provider
- Database username
- Database password
- Database name
- Database port: usually `3306`

Then import the SQL from database.sql.

## 3. Update the database settings
Configure these server environment variables with your hosting database credentials:

```text
DB_HOST=the_host_shown_by_your_provider
DB_PORT=3306
DB_USER=your_db_username
DB_PASS=your_db_password
DB_NAME=your_db_name
```

If your hosting plan does not support environment variables, edit the defaults at the top of `db.php` after uploading. Never use the local XAMPP values (`root` with an empty password) on production hosting.

## 4. Make upload folders writable
Create both `uploads` and `gallery_uploads` in the project root and make sure they are writable by the web server. The gallery form stores image files on the deployed server; it does not upload them to the database. Upload gallery images in batches of 25 or fewer, with each batch below 100 MB, because shared hosts enforce request-size and execution-time limits.

## 5. Open the app
After upload, visit:
- https://yourdomain.com/index.php

If you placed everything in a subfolder named login, use:
- https://yourdomain.com/login/index.php

## 6. Configure Safaricom Daraja STK Push

Set these server environment variables before enabling registration payments:

```text
MPESA_ENVIRONMENT=production
MPESA_CONSUMER_KEY=your_daraja_consumer_key
MPESA_CONSUMER_SECRET=your_daraja_consumer_secret
MPESA_BUSINESS_SHORT_CODE=your_pochi_till_or_paybill_number
MPESA_PASSKEY=your_stk_passkey
MPESA_CALLBACK_URL=https://yourdomain.com/login/payment_callback.php
MPESA_TRANSACTION_TYPE=CustomerPayBillOnline
ADMIN_EMAIL=admin@example.com
```

For MGLG support donations, use `MPESA_BUSINESS_SHORT_CODE=247247`. The donation page sends the supplied business number `0350287553970` as the account reference and prompts the donor's phone through Daraja STK Push. `MPESA_CONSUMER_KEY`, `MPESA_CONSUMER_SECRET`, `MPESA_PASSKEY`, and a public HTTPS `MPESA_CALLBACK_URL` must still be configured.

The registration form displays Pochi la Biashara `07`, but Daraja STK Push requires the Pochi business shortcode or till number in `MPESA_BUSINESS_SHORT_CODE`; a phone number cannot be used as that API field. The callback URL must be publicly reachable over HTTPS. Test with `MPESA_ENVIRONMENT=sandbox` first.

`ADMIN_EMAIL` controls access to `admin.php`. Set it to your own registered email address; after signing in, the user dashboard will show an **Admin dashboard** link. The admin page shows the paid-member count plus each member's name, email, phone number, and generated Member ID.

## 7. Configure email delivery

The application uses PHPMailer over authenticated SMTP. Upload the `vendor/phpmailer` folder and `mail.php`. On hosting that supports environment variables, configure:

```text
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_ENCRYPTION=tls
SMTP_USERNAME=your-sender@gmail.com
SMTP_PASSWORD=your-gmail-app-password-without-spaces
MAIL_FROM=your-sender@gmail.com
MAIL_FROM_NAME=My Generation Loves God
```

If the host does not support environment variables, copy `mail-config.example.php` to `mail-config.php`, replace the placeholders, and upload it. Do not commit or publicly expose `mail-config.php`; protect it with the host's file permissions or an `.htaccess` rule. The `MAIL_FROM` address must match the authenticated SMTP account.

### Schedule the daily verse

The daily verse is now sent by `daily-verse-cron.php`; opening the dashboard is no longer required. Set a long random `DAILY_VERSE_CRON_TOKEN` alongside the SMTP settings. In the InfinityFree control panel's **Cron Jobs** section, create a daily web cron for:

```text
daily-verse-cron.php?token=your-long-random-secret
```

Run it after midnight in the time zone configured for the database. The endpoint returns `403` when the token is missing or incorrect. A failed SMTP attempt is logged and remains eligible for a later retry; inspect the PHP error log for the SMTP error text.
The same daily task also posts one in-app "Happy New Month" notification to all registered users on the first day of each month.

### SMTP details used by this project

The configured sender is `thegenerationlovesgod@gmail.com`. The App Password belongs in `mail-config.php` or the hosting environment only, never in this README. Gmail SMTP uses port `587` with `tls` encryption. Restart Apache after changing local configuration, then upload `mail-config.php` separately to the deployed server.

For legacy local scripts that still use PHP `mail()`, XAMPP Sendmail can be configured separately:

1. Edit `C:\xampp\sendmail\sendmail.ini`:

```ini
smtp_server=smtp.gmail.com
smtp_port=587
smtp_ssl=tls
auth_username=thegenerationlovesGod@gmail.com
auth_password=your16characterapppassword
force_sender=thegenerationlovesGod@gmail.com
```

3. In `C:\xampp\php\php.ini`, set:

```ini
sendmail_path="C:\xampp\sendmail\sendmail.exe" -t
```

4. Restart Apache from the XAMPP Control Panel. Do not use the normal Gmail account password; use the App Password with spaces removed. If Sendmail logs `Username and Password not accepted`, revoke the old App Password, create a new one, and replace `auth_password`. On production hosting, use the host's SMTP settings instead of these local XAMPP paths.
