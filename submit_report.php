<?php
require 'config.php'; require 'functions.php';
if($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location:report-a-problem'); exit; }
verify_csrf();
$name = trim($_POST['name'] ?? '');
$details = trim($_POST['details'] ?? '');
$email = trim($_POST['email'] ?? '');
if ($name === '' || strlen($name) > 120 || $details === '' || strlen($details) > 20000 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
	http_response_code(422);
	exit('Please provide a name, a valid email, and problem details.');
}

$stmt = $pdo->prepare("INSERT INTO reports(name,email,type,subject,message) VALUES(?,?,?,?,?)");
$stmt->execute([$name, $email, 'Issue', 'Problem report', $details]);
$reportId = (int)$pdo->lastInsertId();
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Problem Report Saved · CS3B</title><link rel="stylesheet" href="assets/style.css"></head><body>
<main class="container narrow"><section class="hero small"><p class="eyebrow">REPORT #<?=e($reportId)?></p><h1>Problem report saved</h1><p>Your report is now in the officer dashboard. Continue to send the email notification.</p></section>
<form id="email-report" class="card form" action="https://formsubmit.co/alibangbanglawrencejohn_bscs@plmun.edu.ph" method="post">
<input type="hidden" name="_subject" value="CS3B Bulletin problem report #<?=e($reportId)?>">
<input type="hidden" name="name" value="<?=e($name)?>">
<input type="hidden" name="details" value="<?=e($details)?>">
<input type="hidden" name="email" value="<?=e($email)?>">
<button class="primary" type="submit">Continue to email notification</button></form></main>
<script>document.getElementById('email-report').requestSubmit();</script></body></html>
