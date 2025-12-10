<?php
/**
 * ============================================================================
 * EMAIL.PHP - Email Configuration & Constants
 * ============================================================================
 * 
 * File ini menyimpan configuration constants untuk email system.
 * Email dikirim menggunakan PHP mail() function dengan SMTP relay via XAMPP sendmail.
 * 
 * SMTP SETUP REQUIREMENTS:
 * 1. Configure php.ini:
 *    - sendmail_path = "C:\xampp\sendmail\sendmail.exe -t"
 * 2. Configure sendmail.ini (C:\xampp\sendmail\sendmail.ini):
 *    - smtp_server = smtp.gmail.com
 *    - smtp_port = 587
 *    - auth_username = bookez.web@gmail.com
 *    - auth_password = [App Password 16 karakter]
 *    - force_sender = bookez.web@gmail.com
 * 3. Restart Apache setelah config changes
 * 
 * TROUBLESHOOTING:
 * - Error "Username and Password not accepted" → Generate new App Password
 * - Wrong sender email → Check force_sender di sendmail.ini
 * - Email not sending → Check sendmail.log di C:\xampp\sendmail\
 * 
 * REFERENCE DOCS:
 * - temp/SETUP_EMAIL_COMPLETE.md - Complete email setup guide
 * - temp/FIX_EMAIL_SENDER_CONFIG.md - Troubleshooting sender issues
 * - temp/test_email.php - Test script untuk verify SMTP connection
 * 
 * @package BookEZ
 * @version 1.0
 * @author PBL-Perpustakaan Team
 */

/**
 * ============================================================================
 * EMAIL SENDER CONFIGURATION
 * ============================================================================
 * 
 * Constants untuk email sender identity.
 * 
 * CRITICAL HIERARCHY (highest to lowest priority):
 * 1. sendmail.ini → force_sender (overrides ALL From: headers)
 * 2. PHP code → From: header (dari constants ini)
 * 3. php.ini → sendmail_from (fallback)
 * 4. sendmail.ini → auth_username (SMTP auth)
 * 
 * CURRENT SENDER: bookez.web@gmail.com
 * - Gmail account dengan 2FA enabled
 * - Menggunakan App Password untuk SMTP authentication
 */

/**
 * Email address yang akan muncul sebagai sender
 * MUST match force_sender di sendmail.ini untuk consistency
 * @const string
 */
define('MAIL_FROM_ADDRESS', 'bookez.web@gmail.com');

/**
 * Display name untuk sender (muncul di email client)
 * Format: "BookEZ - Sistem Peminjaman Ruangan" <bookez.web@gmail.com>
 * @const string
 */
define('MAIL_FROM_NAME', 'BookEZ - Sistem Peminjaman Ruangan');

/**
 * Reply-To email address
 * Email balasan dari user akan dikirim ke alamat ini
 * @const string
 */
define('MAIL_REPLY_TO', 'bookez.web@gmail.com');

/**
 * ============================================================================
 * EMAIL TEMPLATES
 * ============================================================================
 * 
 * Reusable components untuk email content.
 */

/**
 * Standard email signature
 * Ditambahkan di akhir setiap email untuk consistency
 * Format: Plain text dengan line breaks (\n)
 * @const string
 */
define('MAIL_SIGNATURE', "\n\nTerima kasih,\nBookEZ Team\nSistem Peminjaman Ruangan Politeknik Negeri Jakarta");
