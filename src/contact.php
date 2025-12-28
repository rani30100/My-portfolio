<?php
header('Content-Type: application/json');

echo json_encode([
    'step' => 'file reached',
    'env' => [
        'MAIL_HOST' => $_ENV['MAIL_HOST'] ?? 'NOT_SET',
        'MAIL_USERNAME' => $_ENV['MAIL_USERNAME'] ?? 'NOT_SET',
        'MAIL_PORT' => $_ENV['MAIL_PORT'] ?? 'NOT_SET'
    ]
]);

exit;
