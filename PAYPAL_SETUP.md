# PayPal Payment Integration Guide

## Overview

Your Euodia store now supports PayPal payments alongside the existing PayUnit Mobile Money integration. The PayPal implementation uses a **no-API** approach that doesn't require complex OAuth setup.

## How It Works

### Architecture

- **includes/paypal.php** - PayPal handler class with payment creation and verification
- **uploads/paypal_redirect.php** - Redirect page showing payment confirmation
- **uploads/paypal_return.php** - Handles successful/failed payment returns
- **uploads/paypal_ipn.php** - IPN (Instant Payment Notification) handler for server-to-server verification
- **uploads/checkout.php** - Updated with payment method selection (PayUnit or PayPal)

### Payment Flow

1. User selects **PayPal** as payment method in checkout
2. Clicks **Proceed to Payment**
3. Redirected to `paypal_redirect.php` (demo page)
4. Completes payment (demo shows success button)
5. Returns to `paypal_return.php` which processes the order
6. Order saved to database and user redirected to success page

## Setup Instructions

### For Demo/Sandbox Mode (Current)

The system is currently configured for **demo/sandbox testing**. No additional setup needed - just test the PayPal flow!

### For Production (Live PayPal Payments)

To enable real PayPal payments, follow these steps:

#### 1. Create PayPal Developer Account

- Go to [PayPal Developers](https://developer.paypal.com)
- Sign up or log in
- Create a Merchant Account

#### 2. Get Credentials

- Navigate to **Dashboard** → **Apps & Credentials**
- Under **Sandbox**, use your test credentials
- Under **Live**, use your production credentials

#### 3. Get Your Business Email

- Go to **Account Settings**
- Copy your **Business Email**

#### 4. Update includes/paypal.php

Update these values:

```php
private $client_id = "YOUR_PAYPAL_CLIENT_ID";
private $business_email = "YOUR_PAYPAL_BUSINESS_EMAIL";
private $is_sandbox = true; // Set to false for live
```

#### 5. Configure IPN (Instant Payment Notification)

PayPal will notify your server about payments:

- Log in to [PayPal Merchant Center](https://www.paypal.com/)
- Go to **Account Settings** → **Notifications** → **IPN Settings**
- Set **IPN URL** to: `https://yourdomain.com/uploads/paypal_ipn.php`
- Select events:
  - Payment Completed
  - Payment Pending
  - Payment Failed
  - Recurring Payment
  - Click **Save**

#### 6. Test in Sandbox

- Set `is_sandbox = true` in paypal.php
- Use [PayPal Sandbox](https://sandbox.paypal.com) to test
- Create test buyer and seller accounts

#### 7. Go Live

- Update paypal.php:
  - Set `is_sandbox = false`
  - Add production credentials
  - Update URLs to use https://www.paypal.com instead of sandbox

## Database Requirements

Make sure your `orders` table has these columns:

```sql
ALTER TABLE orders ADD COLUMN payment_method VARCHAR(50) DEFAULT 'PayUnit';
ALTER TABLE orders ADD COLUMN status VARCHAR(50) DEFAULT 'Pending';
```

## Features

✅ **Dual Payment Methods** - Users can choose PayUnit or PayPal
✅ **Secure Transaction IDs** - Each order gets unique TX ID
✅ **IPN Verification** - Server-side payment verification
✅ **Order Tracking** - All payments stored in database
✅ **Demo Mode** - Test without live credentials

## Testing

### Test Payment Methods

**PayUnit:**
- Simulates mobile money payment
- Mock MTN MoMo / Orange Money

**PayPal Demo:**
- Simulates PayPal checkout
- Demo success page
- Full order flow testing

### Test Cases

1. **Add to Cart** → Select Product
2. **Checkout** → Enter Payment Email
3. **Select PayPal** → Click Proceed
4. **Demo Page** → Click Success Button
5. **Success Page** → Verify Order Created

## Troubleshooting

### IPN Not Firing

- Check firewall allows POST from 216.113.188.* - 216.113.191.* (PayPal range)
- Verify IPN URL is publicly accessible (not localhost)
- Check server logs for errors
- Test IPN manually from PayPal dashboard

### Payment Not Processing

- Verify transaction ID is unique
- Check database `orders` table exists
- Confirm user session exists
- Review error logs

### Redirect Loop

- Clear browser cookies
- Check cart cookie handling in checkout.php
- Verify payment method form is submitting correctly

## Security Notes

⚠️ **For Production:**

1. Use HTTPS only (not HTTP)
2. Validate all IPN signatures with PayPal
3. Never commit credentials to version control
4. Use environment variables for sensitive data
5. Implement rate limiting on IPN handler
6. Log all payment transactions
7. Keep PayPal SDK updated

## Invoice/Receipt

Users receive:
- Transaction ID
- Order confirmation email
- PayPal receipt (for PayPal payments)
- Order details page (success.php)

## Support

For PayPal integration support, visit:
- PayPal Developer Docs: https://developer.paypal.com/docs
- IPN Guide: https://developer.paypal.com/docs/classic/ipn/integration-guide/
- Community: https://paypal-community.com

---

**Last Updated:** April 15, 2026
**Euodia Store Version:** 1.0
