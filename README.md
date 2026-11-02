# College Event Management System
A PHP + MySQL college event portal with Admin and Student roles.

## Requirements
- PHP 8.1+ with PDO MySQL, MySQL 5.7+/MariaDB 10.4+
- Apache (XAMPP/WAMP/LAMP)
- Composer (optional; only needed if you install PHPMailer for SMTP)

## Setup (XAMPP on Windows)
1. Extract `College-Event-Management-System.zip` into `C:\xampp\htdocs\`.
2. Start Apache and MySQL in XAMPP.
3. Open phpMyAdmin at `http://localhost/phpmyadmin`.
4. Import `database/college_events.sql`.
5. Edit `config/config.php`: set DB host/name/user/password and `APP_URL`.
6. Open `http://localhost/College-Event-Management-System/`.
7. Register a user. To make the first admin, run this SQL after registering:
   `UPDATE users SET role='admin' WHERE email='your-email@example.com';`
8. Sign in and create departments/events from the admin area.

## Default workflow
- Student registers and signs in.
- Student may submit an event proposal; it is pending until an admin approves it.
- Student registers for a published event and enters a payment reference if a fee is charged.
- Admin reviews registrations/payments and marks payment as verified.
- A confirmed registration receives a QR pass and can print a participation certificate after an admin marks attendance.

## Email notifications
The app logs notification messages to `storage/mail.log` by default, so it works without SMTP.
For real email:
1. Install PHPMailer: `composer require phpmailer/phpmailer`
2. Set `MAIL_MODE` to `smtp` and configure SMTP values in `config/config.php`.
3. Configure an app password with your mail provider. Do not commit real credentials.

## QR passes
The QR image is rendered in the browser using QRCode.js from a CDN. For offline use, download the library and serve it locally. QR contents are an opaque registration token, not personal information. Staff should validate the token against the registration record while signed in.

## Payment integration
This version records payment references and supports admin verification. It does not collect card details or claim that money has been transferred. For live payments, integrate a provider such as Razorpay/Stripe using server-side order creation, signature verification, webhooks, and test-mode validation.

## Hosting / deployment checklist
- Use a supported PHP version and managed MySQL database.
- Point a domain to the server and enable HTTPS (Let's Encrypt).
- Set `APP_ENV` to `production`, `APP_DEBUG` to `false`, and `APP_URL` to the HTTPS URL.
- Use a dedicated database user with only required privileges.
- Keep `config/config.php`, `.env`, backups and logs outside public web access where possible.
- Disable directory listing; protect `config/`, `database/`, and `storage/` with server rules.
- Set PHP `display_errors=Off`; log errors privately.
- Use HTTPS-only, Secure, HttpOnly, SameSite cookies.
- Back up database and uploaded files daily; test restoration.
- Restrict upload size and file types; scan uploaded files on production hosting.
- Add rate limiting/WAF, monitoring, and regular dependency updates.
- Never store passwords, card numbers, CVV, or payment gateway secrets in source control.

## Security notes
- Passwords use `password_hash()` / `password_verify()`.
- SQL uses PDO prepared statements.
- Forms use CSRF tokens.
- Student data pages are scoped to the signed-in user.
- Admin routes require the admin role.
- Event proposal ownership is enforced.
- Output is escaped with `htmlspecialchars()`.
- Production deployment must use HTTPS and secure server configuration.

## Bilingual project summary
### English
The College Event Management System digitizes event proposals, approvals, registrations, payment tracking, QR entry passes, certificates and notifications. Students can manage their own activities, while administrators control approvals and college-wide records.

### हिन्दी
College Event Management System कॉलेज के कार्यक्रमों को ऑनलाइन मैनेज करने वाला वेब एप्लिकेशन है। इसमें विद्यार्थी इवेंट प्रस्ताव भेज सकते हैं, कार्यक्रमों में रजिस्टर कर सकते हैं और QR पास प्राप्त कर सकते हैं। Admin इवेंट approve करता है, पेमेंट verify करता है और रिपोर्ट मैनेज करता है।

## Recent maintenance and improvements
- Session inactivity checks are shared by the application bootstrap and apply to authenticated pages.
- Logout now uses POST with a CSRF token.
- Paid-event QR passes are shown only after an administrator verifies payment.
- Attendance can only be recorded for registrations whose payment is verified or not required.
- Administrators can use `checkin.php` to validate a QR token (or a token pasted from a QR reader) and record attendance once.
- Event listings show upcoming events in date order and include search, department filtering and pagination.
- Event proposals receive server-side validation for dates, times, fees and departments.
- Registration errors distinguish duplicate registrations from unexpected database errors.
- CSV exports guard against spreadsheet formula injection and include a UTF-8 BOM.

## Notes for this version
- The check-in page accepts a QR reader that types the QR content into the token field. A built-in camera scanner is not included yet. Check-in is blocked before the scheduled event start.
- Payment verification remains a manual admin workflow. A payment reference is not proof that money was received; connect a payment provider and verify signed server notifications before using this for real payments.
- Update `APP_URL`, database credentials and `APP_ENV` in `config/config.php` before deployment. Do not use the sample local database credentials on a public server.
