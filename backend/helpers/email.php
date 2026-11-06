<?php

declare(strict_types=1);

/**
 * Sends a transactional email through Brevo's HTTPS API.
 * Never log API keys, verification codes, or full email bodies.
 */
function send_transactional_email(string $recipientEmail, string $subject, string $textBody): bool
{
    $config = require dirname(__DIR__) . '/config/brevo.php';

    error_log(
        'CMU Brevo diagnostic: key fingerprint=' .
        substr(hash('sha256', (string)$config['api_key']), 0, 12) .
        ', sender_valid=' .
        (filter_var($config['sender_email'], FILTER_VALIDATE_EMAIL) ? 'YES' : 'NO')
    );

    if ($config['api_key'] === '' || $config['sender_email'] === '') {
        error_log('Brevo email configuration is incomplete.');
        return false;
    }

    if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    if (!function_exists('curl_init')) {
        error_log('PHP cURL extension is not enabled.');
        return false;
    }

    $payload = json_encode([
        'sender' => [
            'name' => $config['sender_name'],
            'email' => $config['sender_email'],
        ],
        'to' => [['email' => $recipientEmail]],
        'subject' => $subject,
        'textContent' => $textBody,
    ], JSON_THROW_ON_ERROR);

    $handle = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt_array($handle, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            'accept: application/json',
            'content-type: application/json',
            'api-key: ' . $config['api_key'],
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 25,
    ]);

    $response = curl_exec($handle);
    $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
    $curlError = curl_errno($handle);
    curl_close($handle);

    if ($response === false || $curlError !== 0) {
        error_log('Brevo request failed at transport level.');
        return false;
    }

    if ($status < 200 || $status >= 300) {
        error_log('Brevo email request rejected with HTTP status ' . $status);
        return false;
    }

    return true;
}
