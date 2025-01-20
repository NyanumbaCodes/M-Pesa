<?php


return [
    'env' => env('MPESA_ENV', 'sandbox'),
    'username'=>env('MPESA_USERNAME'),
    'consumer_key' => env('MPESA_CONSUMER_KEY'),
    'consumer_secret' => env('MPESA_CONSUMER_SECRET'),
    'security_credential' => env('MPESA_SECURITY_CREDENTIAL'),
    'shortcode' => env('MPESA_SHORTCODE'),
    'passkey' => env('MPESA_PASSKEY'),
    'c2b_confirmation'=>env('MPESA_C2B_CONFIRMATION'),
    'c2b_validation'=> env('MPESA_C2B_VALIDATION'),
    'transaction_result'=> env('MPESA_TRANSACTION_RESULT'),
    'transaction_timeout'=> env('MPESA_TRANSACTION_TIMEOUT'),
    'b2c_result'=> env('MPESA_B2C_RESULT'),
    'b2c_timeout'=> env('MPESA_B2C_TIMEOUT'),
    'b2c_topup_result'=> env('MPESA_B2C_TOPUP_RESULT'),
    'b2c_topup_timeout'=> env('MPESA_B2C_TOPUP_TIMEOUT'),
    'balance_result'=> env('MPESA_BALANCE_RESULT'),
    'balance_timeout'=> env('MPESA_BALANCE_TIMEOUT'),
    'reversal_result'=> env('MPESA_REVERSAL_RESULT'),
    'reversal_timeout'=> env('MPESA_REVERSAL_TIMEOUT'),
    'tax_result'=> env('MPESA_TAX_RESULT'),
    'tax_timeout'=> env('MPESA_TAX_TIMEOUT'),
    'ratiba_callback'=> env('MPESA_RATIBA_CALLBACK'),
];
