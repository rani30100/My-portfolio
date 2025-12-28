<?php
header('Content-Type: application/json');
echo json_encode([
    'status' => 'php reached',
    'MAIL_HOST' => getenv('MAIL_HOST') ?: 'NOT SET',
]);
exit;
