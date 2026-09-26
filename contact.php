<?php require 'config.php'; require 'functions.php'; ?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Contact Officers · CS3B</title><link rel="stylesheet" href="assets/style.css"></head><body>
<header class="top"><div><strong>CS3B : Bulletin</strong><span>2026 – 2027</span></div><nav><a href="./">Home</a><a href="contact">Contact Officers</a><a href="login">Officer Login</a></nav></header>
<main class="container narrow"><section class="hero small"><p class="eyebrow">CONTACT THE OFFICERS</p><h1>Submit a concern, issue, suggestion, or report.</h1><p>Your submission will be visible only to authorized officers/admins.</p></section>
<form class="card form" action="submit-report" method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>">
<label>Name<input name="name" maxlength="120" required></label><label>Email<input type="email" name="email" maxlength="190" required></label>
<label>Type<select name="type" required><option>Concern</option><option>Issue</option><option>Report</option><option>Suggestion</option><option>Other</option></select></label>
<label>Subject<input name="subject" maxlength="200" required></label><label>Message<textarea name="message" rows="8" maxlength="5000" required></textarea></label>
<label>Attachment (optional)<input type="file" name="attachment" accept=".jpg,.jpeg,.png,.pdf"></label><button class="primary" type="submit">Submit Report</button></form></main></body></html>
