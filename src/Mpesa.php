<?php

namespace NyanumbaCodes\Mpesa;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Http;

class Mpesa
{
    private $baseUrl;
    private $consumerKey;
    private $consumerSecret;


    public function __construct()
    {
        $this->baseUrl = config('mpesa.env') === 'production'
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';

        $this->consumerKey = config('mpesa.consumer_key');
        $this->consumerSecret = config('mpesa.consumer_secret');
    }

    /**
     * Generates the Security Credentials
     * @param mixed $password
     * @return string
     */
    public function generateSecurityCredential($password)
    {
        $publicKey = file_get_contents(public_path('mpesa/cert.cer'));

        openssl_public_encrypt($password, $encrypted, $publicKey, OPENSSL_PKCS1_PADDING);

        return base64_encode($encrypted);
    }
    /**
     * Generates Originator Conversation ID
     * @return string
     */
    public function generateOriginatorConversationID()
    {
        return 'VAPOR-' . strtoupper(str()->random(8)) . '-' . time();
    }
    /**
     * Authorization
     * Gives you a time bound access token to call allowed APIs. (Normally Expires in 3600 seconds)
     * @throws \Exception
     * @return mixed
     */
    public function authorize()
    {
        $response = Http::withBasicAuth($this->consumerKey, $this->consumerSecret)
            ->get("{$this->baseUrl}/oauth/v1/generate?grant_type=client_credentials");

        if ($response->successful()) {
            return $response->json()['access_token'];
        }

        throw new Exception('Failed to authenticate with MPESA API.');
    }
    /**
     * Dynamic QR
     * Generates a dynamic M-PESA QR Code.
     * @param mixed $merchant_name
     * @param mixed $ref
     * @return mixed
     */
    public function dynamicQr($merchant_name, $ref,)
    {
        $url = "{$this->baseUrl}/mpesa/qrcode/v1/generate";
        $token = $this->authorize();
        $response = Http::withToken($token)->post($url, [
            "MerchantName" => $merchant_name,
            "RefNo" => $ref,
            "Amount" => 1,
            "TrxCode" => "BG",
            "CPI" => config('mpesa.shortcode'),
            "Size" => "500"
        ]);


        return $response->json();
    }
    /**
     * M-Pesa Express Simulate
     * Initiates online payment on behalf of a customer.
     * @param mixed $amount
     * @param mixed $phoneNumber
     * @param mixed $accountReference
     * @param mixed $transactionDesc
     * @return mixed
     */
    public function stkPush($amount, $phoneNumber, $accountReference, $transactionDesc)
    {
        $token = $this->authorize();
        $timestamp = now()->format('YmdHis');
        $password = base64_encode(config('mpesa.shortcode') . config('mpesa.passkey') . $timestamp);

        $response = Http::withToken($token)
            ->post(
                "{$this->baseUrl}/mpesa/stkpush/v1/processrequest",
                [
                    'BusinessShortCode' => config('mpesa.shortcode'),
                    'Password' => $password,
                    'Timestamp' => $timestamp,
                    'TransactionType' => 'CustomerPayBillOnline',
                    'Amount' => $amount,
                    'PartyA' => $phoneNumber,
                    'PartyB' => config('mpesa.shortcode'),
                    'PhoneNumber' => $phoneNumber,
                    'CallBackURL' => route('mpesa.callback'),
                    'AccountReference' => $accountReference,
                    'TransactionDesc' => $transactionDesc,
                ]
            );

        return $response->json();
    }
    /**
     * M-Pesa Express Query
     * Check the status of a Lipa Na M-Pesa Online Payment.
     * @param mixed $checkoutRequestId
     * @return mixed
     */
    public function stkQuery($checkoutRequestId)
    {
        $url = "{$this->baseUrl}/mpesa/stkpushquery/v1/query";
        $token = $this->authorize();
        $timestamp = Carbon::now()->format('YmdHis');
        $response = Http::withToken($token)->post($url, [
            "BusinessShortCode" => config('mpesa.shortcode'),
            "Password" => $this->generateSecurityCredential(config('mpesa.shortcode') . config('mpesa.passkey') . $timestamp),
            "Timestamp" => $timestamp,
            "CheckoutRequestID" => $checkoutRequestId
        ]);

        return $response->json();
    }
    /**
     * Customer To Business Register URL
     * Register validation and confirmation URLs on M-Pesa
     * @param mixed $responseType
     * @return mixed
     */
    public function c2bRegisterUrl($responseType)
    {
        $token = $this->authorize();
        $response = Http::withToken($token)->post(
            "{$this->baseUrl}/mpesa/c2b/v1/registerurl",
            [
                "ShortCode" => config('mpesa.shortcode'),
                "ResponseType" => $responseType,
                "ConfirmationURL" => config('mpesa.c2b_confirmation'),
                "ValidationURL" => config('mpesa.c2b_validation')
            ]
        );
        return $response->json();
    }

    /**
     * Customer To Business Simulation
     * Simulate a Transaction between a Customer MSIDSN and Business Short Code (Paybill or Buy Goods Till)
     * @param mixed $amount
     * @param mixed $phoneNumber
     * @param mixed $billRef
     * @param mixed $commandId
     * @return mixed
     */
    public function c2bSimulate($amount, $phoneNumber, $billRef, $commandId = 'CustomerPayBillOnline')
    {

        $token = $this->authorize();

        $response = Http::withToken($token)->post(
            "{$this->baseUrl}/mpesa/c2b/v1/simulate",
            [
                'ShortCode' => config('mpesa.shortcode'),
                'CommandID' => $commandId,
                'Amount' => $amount,
                'Msisdn' => $phoneNumber,
                'BillRefNumber' => $billRef
            ]
        );

        return $response->json();
    }
    /**
     * Business To Customer (B2C)
     * Transact between an M-Pesa short code to a phone number registered on M-Pesa
     * @param mixed $phone
     * @param mixed $amount
     * @param mixed $remarks
     * @return mixed
     */
    public function b2cPayment($phone, $amount, $remarks = 'Salary Payment')
    {
        $token = $this->authorize();

        $securityCredential = config('mpesa.security_credential'); // Encrypted initiator password

        $response = Http::withToken($token)->post(
            "{$this->baseUrl}/mpesa/b2c/v3/paymentrequest",
            [
                "OriginatorConversationID" => $this->generateOriginatorConversationID(),
                'InitiatorName' => 'Test Api',
                'SecurityCredential' => $this->generateSecurityCredential($securityCredential),
                'CommandID' => 'SalaryPayment', // Options: BusinessPayment, SalaryPayment, PromotionPayment
                'Amount' => $amount,
                'PartyA' => config('mpesa.shortcode'),
                'PartyB' => $phone,
                'Remarks' => $remarks,
                'QueueTimeOutURL' => route('b2c.timeout'),
                'ResultURL' => route('b2c.result'),
                'Occasion' => $remarks
            ]
        );

        return $response->json();
    }
    /**
     * Transaction Status
     * Check the status of a transaction
     * @param mixed $initiator
     * @param mixed $securityCredential
     * @param mixed $transactionId
     * @param mixed $originatorConversationId
     * @return mixed
     */
    public function transactionStatus($initiator, $securityCredential, $transactionId, $originatorConversationId)
    {
        $url = "{$this->baseUrl}/mpesa/transactionstatus/v1/query";
        $token = $this->authorize();

        $response = Http::withToken($token)->post(
            $url,
            [
                "Initiator" => $initiator,
                "SecurityCredential" => $this->generateSecurityCredential($securityCredential),
                "CommandID" => "TransactionStatusQuery",
                "TransactionID" => $transactionId,
                "OriginatorConversationID" => $originatorConversationId,
                "PartyA" => config('mpesa.shortcode'),
                "IdentifierType" => "4",
                "ResultURL" => route('transaction.result'),
                "QueueTimeOutURL" => route('transaction.timeout'),
                "Remarks" => "OK",
                "Occasion" => "OK",
            ]
        );

        return $response->json();
    }
    /**
     * Account Balance
     * Enquire the balance on an M-Pesa BuyGoods (Till Number)
     * @return mixed
     */
    public function accountBalance()
    {
        $token = $this->authorize();

        $securityCredential = config('mpesa.security_credential'); // Encrypted initiator password

        $response = Http::withToken($token)->post(
            "{$this->baseUrl}/mpesa/accountbalance/v1/query",
            [
                "Initiator" => "testapiuser",
                "SecurityCredential" => $this->generateSecurityCredential($securityCredential),
                "CommandID" => "AccountBalance",
                "PartyA" => config('mpesa.shortcode'),
                "IdentifierType" => "4",
                "Remarks" => "OK",
                "QueueTimeOutURL" => config('mpesa.balance_timeout'),
                "ResultURL" => config('mpesa.balance_result'),
            ]
        );

        return $response->json();
    }
    /**
     * Reversals
     * Reverses an M-Pesa transaction.
     * @param mixed $transactionId
     * @param mixed $amount
     * @param mixed $remarks
     * @return mixed
     */
    public function reversal($transactionId, $amount, $remarks = "OK")
    {
        $token = $this->authorize();
        $securityCredential = config('mpesa.security_credential');

        $response = Http::withToken($token)->post(
            "{$this->baseUrl}/mpesa/reversal/v1/request",
            [
                "Initiator" => "TestInit610",
                "SecurityCredential" => $this->generateSecurityCredential($securityCredential),
                "CommandID" => "TransactionReversal",
                "TransactionID" => $transactionId,
                "Amount" => $amount,
                "ReceiverParty" => config('mpesa.shortcode'),
                "RecieverIdentifierType" => "11",
                "ResultURL" => config('mpesa.reversal_result'),
                "QueueTimeOutURL" => config('mpesa.reversal_timeout'),
                "Remarks" => $remarks,
                "Occasion" => $remarks
            ]
        );

        return $response->json();
    }
    /**
     * Tax Remittance
     * This API enables businesses to remit tax to Kenya Revenue Authority (KRA).
     * @param mixed $amount
     * @param mixed $prn
     * @param mixed $remarks
     * @return mixed
     */
    public function taxRemittance($amount, $prn, $remarks = "OK")
    {
        $token = $this->authorize();
        $securityCredential = config('mpesa.security_credential');

        $response = Http::withToken($token)->post(
            "{$this->baseUrl}/mpesa/b2b/v1/remittax",
            [
                "Initiator" => "TaxPayer",
                "SecurityCredential" => $this->generateSecurityCredential($securityCredential),
                "CommandID" => "PayTaxToKRA",
                "SenderIdentifierType" => "4",
                "RecieverIdentifierType" => "4",
                "Amount" => $amount,
                "PartyA" => config('mpesa.shortcode'),
                "PartyB" => "572572",
                "AccountReference" => $prn,
                "Remarks" => $remarks,
                "QueueTimeOutURL" => config('mpesa.tax_timeout'),
                "ResultURL" => config('mpesa.tax_result'),
            ]
        );

        return $response->json();
    }

    /**
     * Business Pay Bill
     * This API enables you to pay bills directly from your business account to a pay bill number, or a paybill store.
     * @param mixed $paybill
     * @param mixed $amount
     * @param mixed $ref
     * @param mixed $requester
     * @param mixed $remarks
     * @return mixed
     */
    public function businessPaybill($paybill, $amount, $ref, $requester = null, $remarks)
    {
        $token = $this->authorize();
        $securityCredential = config('mpesa.security_credential');

        $response = Http::withToken($token)->post(
            "{$this->baseUrl}/mpesa/b2b/v1/paymentrequest",
            [
                "Initiator" => "API_Usename",
                "SecurityCredential" => $this->generateSecurityCredential($securityCredential),
                "CommandID" => "BusinessPayBill",
                "SenderIdentifierType" => "4",
                "RecieverIdentifierType" => "4",
                "Amount" => $amount,
                "PartyA" => config('mpesa.shortcode'),
                "PartyB" => $paybill,
                "AccountReference" => $ref,
                "Requester" => $requester,
                "Remarks" => $remarks,
                "QueueTimeOutURL" => config('mpesa.b2b_timeout'),
                "ResultURL" => config('mpesa.b2b_result'),
            ]
        );

        return $response->json();
    }
    /**
     * Business Buy Goods
     * This API enables you to pay for goods and services directly from your business account to a till number, merchant store number or Merchant HO.
     * @param mixed $short_code
     * @param mixed $amount
     * @param mixed $ref
     * @param mixed $requester
     * @param mixed $remarks
     * @return mixed
     */
    public function businessBuyGoods($short_code, $amount, $ref, $requester = null, $remarks)
    {
        $token = $this->authorize();
        $securityCredential = config('mpesa.security_credential');
        $response = Http::withToken($token)->post(
            "{$this->baseUrl}/mpesa/b2b/v1/paymentrequest",
            [
                "Initiator" => "API_Usename",
                "SecurityCredential" => $this->generateSecurityCredential($securityCredential),
                "CommandID" => "BusinessBuyGoods",
                "SenderIdentifierType" => "4",
                "RecieverIdentifierType" => "4",
                "Amount" => $amount,
                "PartyA" => config('mpesa.shortcode'),
                "PartyB" => $short_code,
                "AccountReference" => $ref,
                "Requester" => $requester,
                "Remarks" => $remarks,
                "QueueTimeOutURL" => config('mpesa.b2b_timeout'),
                "ResultURL" => config('mpesa.b2b_result'),
            ]
        );

        return $response->json();
    }
    /**
     * B2B Express CheckOut
     * This API enables merchants to initiate USSD Push to till enabling their fellow merchants to pay from their own till numbers to the vendors paybill.
     * @param mixed $short_code
     * @param mixed $vendor_name
     * @param mixed $amount
     * @param mixed $refId
     * @return mixed
     */
    public function b2bExpress($short_code, $vendor_name, $amount, $refId)
    {
        $token = $this->authorize();
        $response = Http::withToken($token)->post(
            "{$this->baseUrl}/v1/ussdpush/get-msisdn",
            [
                "primaryShortCode" => $short_code,
                "receiverShortCode" => config('mpesa.shortcode'),
                "amount" => $amount,
                "paymentRef" => $refId,
                "callbackUrl" => config('mpesa.b2b_express_callback'),
                "partnerName" => $vendor_name,
                "RequestRefID" => $refId,
            ]
        );

        return $response->json();
    }
    /**
     * B2C Account Top Up
     * This API enables you to load funds to a B2C shortcode directly for disbursement.
     * @param mixed $amount
     * @param mixed $short_code
     * @param mixed $ref
     * @param mixed $requester
     * @param mixed $remarks
     * @return mixed
     */
    public function b2cTopup($amount, $short_code, $ref, $requester, $remarks)
    {
        $token = $this->authorize();
        $response = Http::withToken($token)->post(
            "{$this->baseUrl}/mpesa/b2b/v1/paymentrequest",
            [
                "Initiator" => "testapi",
                "SecurityCredential" => "IAJVUHDGj0yDU3aop/WI9oSPhkW3DVlh7EAt3iRyymTZhljpzCNnI/xFKZNooOf8PUFgjmEOihUnB24adZDOv3Ri0Citk60LgMQnib0gjsoc9WnkHmGYqGtNivWE20jyIDUtEKLlPr3snV4d/H54uwSRVcsATEQPNl5n3+EGgJFIKQzZbhxDaftMnxQNGoIHF9+77tfIFzvhYQen352F4D0SmiqQ91TbVc2Jdfx/wd4HEdTBU7S6ALWfuCCqWICHMqCnpCi+Y/ow2JRjGYHdfgmcY8pP5oyH25uQk1RpWV744aj2UROjDrxTnE7a6tDN6G/dA21MXKaIsWJT/JyyXg==",
                "CommandID" => "BusinessPayToBulk",
                "SenderIdentifierType" => "4",
                "RecieverIdentifierType" => "4",
                "Amount" => $amount,
                "PartyA" => config('mpesa.shortcode'),
                "PartyB" => $short_code,
                "AccountReference" => $ref,
                "Requester" => $requester,
                "Remarks" => $remarks,
                "QueueTimeOutURL" => config('mpesa.b2c_topup_timeout'),
                "ResultURL" => config('mpesa.b2c_topup_result')
            ]
        );

        return $response->json();
    }
    /**
     * M-Pesa Ratiba
     * This is an API that allows third party integrators to facilitate creation of M-Pesa standing order on their digital channels
     * @param mixed $name
     * @param mixed $start
     * @param mixed $end
     * @param mixed $amount
     * @param mixed $ref
     * @param mixed $desc
     * @param int $frequency
     * @return mixed
     */
    public function createStandingOrder($name, $start, $end, $amount, $ref, $desc, int $frequency)
    {
        $token = $this->authorize();

        $start_date = Carbon::parse($start)->format('Ymd');
        $end_date = Carbon::parse($end)->format('Ymd');

        $response = Http::withToken($token)->post(
            "{$this->baseUrl}/standingorder/v1/createStandingOrderExternal",
            [
                "StandingOrderName" => $name,
                "StartDate" => $start_date,
                "EndDate" => $end_date,
                "BusinessShortCode" => config('mpesa.shortcode'),
                "TransactionType" => "Standing Order Customer Pay Bill",
                "ReceiverPartyIdentifierType" => "4",
                "Amount" => $amount,
                "PartyA" => "254708374149",
                "CallBackURL" => config('mpesa.ratiba_callback'),
                "AccountReference" => $ref,
                "TransactionDesc" => $desc,
                "Frequency" => "{$frequency}",
            ]
        );

        return $response->json();
    }
}
