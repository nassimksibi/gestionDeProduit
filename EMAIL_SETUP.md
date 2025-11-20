# Email Configuration Guide

To send real emails to customers, you need to configure a real SMTP server in your `.env` file.

## Current Configuration

Currently, your `MAILER_DSN` is set to `smtp://localhost:1025` which is for local testing with Mailpit.

## Setting Up Real Email

### Option 1: Gmail SMTP (Recommended for Testing)

1. Enable 2-Step Verification on your Gmail account
2. Generate an App Password:
   - Go to: https://myaccount.google.com/apppasswords
   - Create an app password for "Mail"
   - Copy the 16-character password
3. Update your `.env` file:

```env
MAILER_DSN=smtp://smtp.gmail.com:587?encryption=tls&auth_mode=login&username=your-email@gmail.com&password=your-16-char-app-password
MAILER_FROM_EMAIL=your-email@gmail.com
MAILER_FROM_NAME=Your App Name
```

**Important:** Replace:
- `your-email@gmail.com` with your actual Gmail address
- `your-16-char-app-password` with the app password you generated
- `Your App Name` with your desired sender name

### Option 2: Outlook/Hotmail SMTP

```env
MAILER_DSN=smtp://smtp-mail.outlook.com:587?encryption=tls&auth_mode=login&username=your-email@outlook.com&password=your-password
MAILER_FROM_EMAIL=your-email@outlook.com
MAILER_FROM_NAME=Your App Name
```

### Option 3: Custom SMTP Server

```env
MAILER_DSN=smtp://smtp.yourdomain.com:587?encryption=tls&auth_mode=login&username=your-username&password=your-password
MAILER_FROM_EMAIL=noreply@yourdomain.com
MAILER_FROM_NAME=Your App Name
```

### Option 4: SendGrid (Recommended for Production)

1. Sign up at https://sendgrid.com
2. Create an API key
3. Update your `.env`:

```env
MAILER_DSN=smtp://smtp.sendgrid.net:587?encryption=tls&auth_mode=login&username=apikey&password=your-sendgrid-api-key
MAILER_FROM_EMAIL=noreply@yourdomain.com
MAILER_FROM_NAME=Your App Name
```

### Option 5: Mailgun

1. Sign up at https://www.mailgun.com
2. Get your SMTP credentials
3. Update your `.env`:

```env
MAILER_DSN=smtp://smtp.mailgun.org:587?encryption=tls&auth_mode=login&username=your-mailgun-username&password=your-mailgun-password
MAILER_FROM_EMAIL=noreply@yourdomain.com
MAILER_FROM_NAME=Your App Name
```

### Option 6: Mailjet (Recommended for Email Verification)

1. Sign up at https://www.mailjet.com
2. Go to Account Settings → API Keys
3. Copy your API Key and Secret Key
4. Verify your sender email in Mailjet dashboard
5. Update your `.env`:

```env
MAILER_DSN=smtp://in-v3.mailjet.com:587?encryption=tls&auth_mode=login&username=YOUR_API_KEY&password=YOUR_SECRET_KEY
MAILER_FROM_EMAIL=noreply@yourdomain.com
MAILER_FROM_NAME=Your App Name
```

**Note:** For detailed Mailjet setup instructions, see [MAILJET_SETUP.md](MAILJET_SETUP.md)

## Testing Email Configuration

After updating your `.env` file:

1. Clear the cache:
   ```bash
   php bin/console cache:clear
   ```

2. Test by registering a new customer - they should receive the verification email (if email verification is enabled).

3. Check your email logs if emails aren't being sent:
   ```bash
   tail -f var/log/dev.log
   ```

## Important Notes

- Never commit your `.env` file with real credentials to version control
- For production, use a professional email service like SendGrid or Mailgun
- Gmail has sending limits (500 emails/day for free accounts)
- Make sure your SMTP server allows connections from your server's IP address

