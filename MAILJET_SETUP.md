# Mailjet Email Verification Setup

This guide explains how to configure Mailjet for email verification in your Symfony application.

## What's Been Implemented

The email verification system includes:
- Email verification token generation on registration
- Verification email sending via Mailjet
- Email verification link with 24-hour expiration
- Resend verification email functionality
- Security check preventing unverified users from logging in

## Mailjet Configuration

### Step 1: Create a Mailjet Account

1. Sign up at [https://www.mailjet.com](https://www.mailjet.com)
2. Verify your email address
3. Complete your account setup

### Step 2: Get Your Mailjet API Credentials

1. Log in to your Mailjet account
2. Go to **Account Settings** → **API Keys**
3. You'll see your **API Key** and **Secret Key**
4. Copy both keys (you'll need them for the DSN)

### Step 3: Configure Your .env File

Add or update the following in your `.env` file:

```env
# Mailjet SMTP Configuration
MAILER_DSN=smtp://in-v3.mailjet.com:587?encryption=tls&auth_mode=login&username=YOUR_API_KEY&password=YOUR_SECRET_KEY

# Email Settings
MAILER_FROM_EMAIL=noreply@yourdomain.com
MAILER_FROM_NAME=Your App Name
```

**Important:** Replace:
- `YOUR_API_KEY` with your Mailjet API Key
- `YOUR_SECRET_KEY` with your Mailjet Secret Key
- `noreply@yourdomain.com` with a verified sender email in Mailjet
- `Your App Name` with your desired sender name

### Step 4: Verify Your Sender Email in Mailjet

1. In Mailjet dashboard, go to **Senders & Domains**
2. Click **Add Sender**
3. Enter your email address (the one you set in `MAILER_FROM_EMAIL`)
4. Verify the email address by clicking the link in the verification email Mailjet sends

### Step 5: Run the Database Migration

Execute the migration to add verification fields to the Customer table:

```bash
php bin/console doctrine:migrations:migrate
```

### Step 6: Clear Cache

Clear the Symfony cache to ensure new configuration is loaded:

```bash
php bin/console cache:clear
```

## How It Works

### Registration Flow

1. User registers with email and password
2. System generates a unique verification token (valid for 24 hours)
3. Verification email is sent via Mailjet
4. User account is created but marked as unverified (`isVerified = false`)
5. User cannot log in until email is verified

### Verification Flow

1. User clicks verification link in email
2. System validates the token
3. If valid and not expired, user's email is marked as verified
4. User can now log in

### Resend Verification

If the verification email is lost or expired:
1. User goes to `/resend-verification`
2. Enters their email address
3. System generates a new token and sends a new verification email

## Testing

### Test Email Verification

1. Register a new account
2. Check your email (and spam folder) for the verification email
3. Click the verification link
4. Try to log in - it should work now

### Test Unverified Login

1. Register a new account
2. **Don't** verify the email
3. Try to log in - you should see an error message asking you to verify your email

### Test Resend Verification

1. Register a new account
2. Go to `/resend-verification`
3. Enter your email
4. Check for the new verification email

## Troubleshooting

### Emails Not Sending

1. **Check Mailjet Dashboard**: Go to Mailjet → Statistics to see if emails are being sent
2. **Verify API Keys**: Make sure your API Key and Secret Key are correct in `.env`
3. **Check Sender Verification**: Ensure your sender email is verified in Mailjet
4. **Check Logs**: Look at `var/log/dev.log` for error messages

### Verification Link Not Working

1. **Check Token Expiration**: Tokens expire after 24 hours
2. **Use Resend**: Request a new verification email if the link expired
3. **Check URL**: Make sure the verification URL is accessible (not blocked by firewall)

### Users Can't Log In After Verification

1. **Check Database**: Verify that `is_verified` is set to `1` in the database
2. **Clear Cache**: Run `php bin/console cache:clear`
3. **Check Logs**: Look for errors in `var/log/dev.log`

## Mailjet Free Tier Limits

- **6,000 emails/month** (200 emails/day)
- **200 emails/day** sending limit
- Perfect for development and small applications

## Production Considerations

1. **Domain Authentication**: Set up SPF, DKIM, and DMARC records for better deliverability
2. **Email Templates**: Customize email templates in `templates/emails/verification.html.twig`
3. **Rate Limiting**: Consider implementing rate limiting for resend verification requests
4. **Monitoring**: Set up monitoring for email delivery rates

## Alternative: Using Mailjet API (Advanced)

If you prefer using Mailjet's REST API instead of SMTP, you can install the Mailjet PHP SDK:

```bash
composer require mailjet/mailjet-apiv3-php
```

Then create a custom mailer service. However, the SMTP approach (current implementation) is simpler and works well with Symfony's Mailer component.

## Security Notes

- Verification tokens are cryptographically secure (64-character hex strings)
- Tokens expire after 24 hours
- Tokens are single-use (removed after successful verification)
- Unverified users cannot access protected routes

