<?php
require 'functions.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	http_response_code(405);
	header('Allow: POST');
	exit('Method not allowed.');
}
verify_csrf();

$_SESSION = [];
if (ini_get('session.use_cookies')) {
	$cookieParams = session_get_cookie_params();
	setcookie(session_name(), '', [
		'expires' => time() - 42000,
		'path' => $cookieParams['path'] ?: '/',
		'domain' => $cookieParams['domain'],
		'secure' => $cookieParams['secure'],
		'httponly' => $cookieParams['httponly'],
		'samesite' => $cookieParams['samesite'] ?? 'Lax',
	]);
}
session_destroy();
header('Location:./');
exit;