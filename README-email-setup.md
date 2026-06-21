# Nilora Resort — Contact Form Email Setup

This guide covers two things:
1. Getting a Microsoft 365 app password for `booking@nilora.com.au`
2. Uploading everything to cPanel so the contact form sends real emails

---

## 1. Create a Microsoft 365 App Password

Microsoft 365 doesn't allow plain SMTP passwords unless you create a dedicated
**app password** (or enable SMTP AUTH for the mailbox in the admin centre).

### Option A — App password (recommended if MFA is enabled)

1. Sign in to **https://myaccount.microsoft.com** as `booking@nilora.com.au`.
2. Go to **Security info** → **+ Add sign-in method** → choose **App password**.
3. Give it a name like `Nilora Website Form`.
4. Copy the generated password — you can only see it once.

### Option B — Enable SMTP AUTH in the Microsoft 365 Admin Centre

If MFA is not enabled on the mailbox you can use the regular mailbox password,
but you must first allow SMTP AUTH:

1. Sign in to **https://admin.microsoft.com** as a global administrator.
2. Go to **Users → Active users** → click `booking@nilora.com.au`.
3. Open the **Mail** tab → **Manage email apps**.
4. Tick **Authenticated SMTP** → **Save changes**.
5. Allow up to 60 minutes for the change to propagate.

---

## 2. Paste the password into process-form.php

Open `process-form.php` and find these two lines near the top:

```php
const SMTP_USERNAME = 'booking@nilora.com.au';   // already correct
const SMTP_PASSWORD = 'YOUR_APP_PASSWORD_HERE';  // ← replace this
```

Replace `YOUR_APP_PASSWORD_HERE` with the app password you copied above.
**Save the file.**

---

## 3. Upload to cPanel (public_html)

Upload the following files/folders, keeping the exact same structure:

```
public_html/
├── NILORA_HEHETRYLANG.html   ← the website file
├── process-form.php           ← email handler
└── PHPMailer/
    └── src/
        ├── PHPMailer.php
        ├── SMTP.php
        └── Exception.php
```

### How to upload via cPanel File Manager

1. Log in to your cPanel account and open **File Manager**.
2. Navigate to `public_html` (or the subfolder where the site lives).
3. Click **Upload** and upload:
   - `NILORA_HEHETRYLANG.html`
   - `process-form.php`
4. Create the folder `PHPMailer/src/` using the **New Folder** button.
5. Upload the three PHPMailer files into `PHPMailer/src/`.

### How to upload via FTP (FileZilla etc.)

1. Connect to your host using FTP/SFTP credentials from cPanel.
2. Drag the entire folder structure above into `public_html`.

---

## 4. Test the form

1. Open the website in a browser.
2. Fill in the contact form and click **Send Message**.
3. You should see a green success banner and receive an email at
   `booking@nilora.com.au` within a few seconds.
4. Reply-To on the email will be set to the visitor's address, so you can
   reply directly from your inbox.

---

## Troubleshooting

| Symptom | Likely cause | Fix |
|---|---|---|
| Red error banner after submit | SMTP credentials wrong or SMTP AUTH not enabled | Re-check password and Option B above |
| No error but no email received | Spam folder | Check junk/spam in Outlook |
| `process-form.php` 404 | File not uploaded or wrong directory | Confirm path matches HTML file location |
| PHP error on page | PHP < 7.4 or PHPMailer files missing | Contact host to confirm PHP version; re-upload PHPMailer |

For server-level errors, check the PHP error log in cPanel under
**Logs → Error Log**.
