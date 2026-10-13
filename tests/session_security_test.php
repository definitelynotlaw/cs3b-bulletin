<?php
$expectSecure = in_array('--https', $argv, true);
$_SERVER['HTTPS'] = $expectSecure ? 'on' : 'off';
require __DIR__ . '/../functions.php';

$cookieParams = session_get_cookie_params();
if ($cookieParams['secure'] !== $expectSecure) {
    throw new RuntimeException('Secure cookie setting does not match request scheme.');
}
if (!$cookieParams['httponly'] || strtolower((string)$cookieParams['samesite']) !== 'lax') {
    throw new RuntimeException('Expected HttpOnly and SameSite=Lax cookie attributes.');
}
if (ini_get('session.use_strict_mode') !== '1' || ini_get('session.use_only_cookies') !== '1') {
    throw new RuntimeException('Expected strict, cookie-only PHP sessions.');
}

echo $expectSecure ? "HTTPS session settings passed.\n" : "HTTP development session settings passed.\n";
session_destroy();