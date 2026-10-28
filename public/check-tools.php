<?php
header('Content-Type: application/json');
echo json_encode([
    'php_version' => PHP_VERSION,
    'gd' => extension_loaded('gd'),
    'curl' => extension_loaded('curl'),
    'allow_url_fopen' => ini_get('allow_url_fopen'),
]);
