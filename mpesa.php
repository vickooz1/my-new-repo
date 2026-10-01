<?php

function mpesaConfig($name)
{
    $value = getenv($name);

    if ($value === false || trim($value) === '') {
        throw new RuntimeException('Missing M-Pesa configuration: ' . $name);
    }

    return trim($value);
}

function normalizeMpesaPhone($phoneNumber)
{
    $digits = preg_replace('/\D+/', '', $phoneNumber);

    if (substr($digits, 0, 1) === '0') {
        $digits = '254' . substr($digits, 1);
    } elseif (substr($digits, 0, 3) !== '254') {
        $digits = '254' . $digits;
    }

    if (!preg_match('/^2547\d{8}$/', $digits)) {
        throw new InvalidArgumentException('Enter a valid Kenyan Safaricom phone number.');
    }

    return $digits;
}

function mpesaRequest($url, $headers, $body = null)
{
    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => $headers,
    ]);

    if ($body !== null) {
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body));
    }

    $response = curl_exec($curl);
    $error = curl_error($curl);
    $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($response === false || $error !== '') {
        throw new RuntimeException('Unable to reach Safaricom: ' . $error);
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Safaricom returned an invalid response.');
    }

    if ($statusCode < 200 || $statusCode >= 300) {
        throw new RuntimeException($decoded['errorMessage'] ?? 'Safaricom rejected the payment request.');
    }

    return $decoded;
}

function initiateMpesaStkPush($phoneNumber, $amount, $accountReference, $transactionDescription = 'MGLG account registration')
{
    $consumerKey = mpesaConfig('MPESA_CONSUMER_KEY');
    $consumerSecret = mpesaConfig('MPESA_CONSUMER_SECRET');
    $shortcode = trim(getenv('MPESA_BUSINESS_SHORT_CODE') ?: '247247');
    $passkey = mpesaConfig('MPESA_PASSKEY');
    $callbackUrl = mpesaConfig('MPESA_CALLBACK_URL');
    $environment = strtolower(getenv('MPESA_ENVIRONMENT') ?: 'sandbox');
    $baseUrl = $environment === 'production'
        ? 'https://api.safaricom.co.ke'
        : 'https://sandbox.safaricom.co.ke';

    $auth = base64_encode($consumerKey . ':' . $consumerSecret);
    $tokenResponse = mpesaRequest($baseUrl . '/oauth/v1/generate?grant_type=client_credentials', [
        'Authorization: Basic ' . $auth,
    ]);

    $timestamp = date('YmdHis');
    $password = base64_encode($shortcode . $passkey . $timestamp);
    $response = mpesaRequest($baseUrl . '/mpesa/stkpush/v1/processrequest', [
        'Authorization: Bearer ' . $tokenResponse['access_token'],
        'Content-Type: application/json',
    ], [
        'BusinessShortCode' => $shortcode,
        'Password' => $password,
        'Timestamp' => $timestamp,
        'TransactionType' => getenv('MPESA_TRANSACTION_TYPE') ?: 'CustomerPayBillOnline',
        'Amount' => (int) $amount,
        'PartyA' => normalizeMpesaPhone($phoneNumber),
        'PartyB' => $shortcode,
        'PhoneNumber' => normalizeMpesaPhone($phoneNumber),
        'CallBackURL' => $callbackUrl,
        'AccountReference' => $accountReference,
        'TransactionDesc' => $transactionDescription,
    ]);

    if (empty($response['CheckoutRequestID'])) {
        throw new RuntimeException($response['CustomerMessage'] ?? 'Safaricom did not start the payment prompt.');
    }

    return $response;
}