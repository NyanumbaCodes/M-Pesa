# M-Pesa Integration for Laravel

A robust PHP library for integrating Safaricom's M-Pesa API with Laravel applications. This package simplifies payments, QR code generation, and transaction management.

## Features

* Dynamic QR Code Generation
* STK Push (Customer Payment)
* C2B Simulations
* B2C Transactions
* Transaction Status Checks
* Account Balance Inquiry
* Reversals
* Secure Authentication

## Installation

Install via Composer:

```bash
composer require nyanumba-codes/mpesa

```

## Configuration

First Run the Installation:

```bash
php artisan mpesa:install {environment}

```
The Environment value can either be left blank, or written `sandbox` for The Sandbox Certificate file to be downloaded into the public folder or `production` for the Production Certificate to be downloaded.

```bash
# For Sandbox 

php artisan mpesa:install

# or

php artisan mpesa:install sandbox

```

```bash
# For Production

php artisan mpesa:install production

```

Ensure the Safaricom public certificate (cert.cer) is stored securely under `public/mpesa`.

After this you may proceed to update your .env file. This one is rather longer this time round because all the MPESA APIs have been covered by this application. I have even separated all the Callbacks, Timeouts and Result URL to allow for development maleability.

```bash

# Environment (sandbox or production)
MPESA_ENV=sandbox

# Consumer credentials
MPESA_CONSUMER_KEY=your_consumer_key_here
MPESA_CONSUMER_SECRET=your_consumer_secret_here

# Security credential
MPESA_USERNAME=your_username_here
MPESA_SECURITY_CREDENTIAL=your_security_credential_here

# Shortcode and passkey
MPESA_SHORTCODE=your_shortcode_here
MPESA_PASSKEY=your_passkey_here

# C2B (Customer to Business) URLs
MPESA_C2B_CONFIRMATION=https://yourdomain.com/api/c2b/confirmation
MPESA_C2B_VALIDATION=https://yourdomain.com/api/c2b/validation

# Transaction status callback URLs
MPESA_TRANSACTION_RESULT=https://yourdomain.com/api/transaction/result
MPESA_TRANSACTION_TIMEOUT=https://yourdomain.com/api/transaction/timeout

# B2C (Business to Customer) URLs
MPESA_B2C_RESULT=https://yourdomain.com/api/b2c/result
MPESA_B2C_TIMEOUT=https://yourdomain.com/api/b2c/timeout

# B2C Top-Up URLs
MPESA_B2C_TOPUP_RESULT=https://yourdomain.com/api/b2c/topup/result
MPESA_B2C_TOPUP_TIMEOUT=https://yourdomain.com/api/b2c/topup/timeout

# Account balance inquiry URLs
MPESA_BALANCE_RESULT=https://yourdomain.com/api/balance/result
MPESA_BALANCE_TIMEOUT=https://yourdomain.com/api/balance/timeout

# Reversal request URLs
MPESA_REVERSAL_RESULT=https://yourdomain.com/api/reversal/result
MPESA_REVERSAL_TIMEOUT=https://yourdomain.com/api/reversal/timeout

# Tax inquiry URLs
MPESA_TAX_RESULT=https://yourdomain.com/api/tax/result
MPESA_TAX_TIMEOUT=https://yourdomain.com/api/tax/timeout

# Ratiba callback URL (if applicable)
MPESA_RATIBA_CALLBACK=https://yourdomain.com/api/ratiba/callback

```

## Usage

Initialize the class where you wish to use it:

```php
use NyanumbaCodes\Mpesa\Mpesa;

$mpesa = new Mpesa();

```

### 1. Generate Dynamic QR Code

```php
$response = $mpesa->dynamicQr('MerchantName', 'Ref123');

```

### 2. Process STK Push (MPESA Express Simulate)

```php

$response = $mpesa->stkPush(100, '254700000000', 'AccountRef', 'TransactionDesc');
```

### 3. Process STK Push (MPESA Express Simulate)

```php

$response = $mpesa->transactionStatus('TransactionID', 'originatorConversationID');

```

### 4. Perform C2B Simulation

```php

$response = $mpesa->c2bSimulate(100, '254700000000', 'Ref123');

```

### 5. Handle Reversals

```php

$response = $mpesa->reversal('TransactionID', 100);

```

## License

This package is open-source and licensed under the MIT License.
