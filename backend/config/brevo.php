<?php

declare(strict_types=1);

$secretsPath = __DIR__ . '/secrets.php';

$secrets = is_file($secretsPath)
    ? require $secretsPath
    : [];

return [
    'api_key' => $secrets['BREVO_API_KEY']
        ?? (getenv('BREVO_API_KEY') ?: ''),

    'sender_email' => $secrets['BREVO_SENDER_EMAIL']
        ?? (getenv('BREVO_SENDER_EMAIL') ?: ''),

    'sender_name' => 'CMU Enrollment System',
];