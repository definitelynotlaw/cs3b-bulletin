<?php
require 'config.php'; require 'functions.php';
if($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location:contact'); exit; }
verify_csrf();
$attachment=upload_file('attachment','uploads/reports',['image/jpeg','image/png','application/pdf'],5*1024*1024);
$stmt=$pdo->prepare("INSERT INTO reports(name,email,type,subject,message,attachment_path) VALUES(?,?,?,?,?,?)");
$stmt->execute([trim($_POST['name']??''),trim($_POST['email']??''),$_POST['type']??'Other',trim($_POST['subject']??''),trim($_POST['message']??''),$attachment]);
header('Location:contact?sent=1'); exit;
