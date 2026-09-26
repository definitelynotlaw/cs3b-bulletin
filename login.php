<?php
require 'config.php'; require 'functions.php';
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf(); $s=$pdo->prepare("SELECT * FROM users WHERE email=? LIMIT 1"); $s->execute([trim($_POST['email']??'')]); $u=$s->fetch();
 if($u && password_verify($_POST['password']??'', $u['password'])){ $_SESSION['user']=['id'=>$u['id'],'name'=>$u['name'],'email'=>$u['email'],'role'=>$u['role']]; header('Location:dashboard'); exit; }
 $error='Invalid email or password.';
}
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Officer Login · CS3B</title><link rel="stylesheet" href="assets/style.css"></head><body><main class="container narrow"><section class="hero small"><p class="eyebrow">CS3B : BULLETIN</p><h1>Officer Login</h1></section><form class="card form" method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><?php if($error): ?><div class="alert"><?=$error?></div><?php endif; ?><label>Email<input type="email" name="email" required></label><label>Password<input type="password" name="password" required></label><button class="primary">Login</button><a href="./">← Back to bulletin</a></form></main></body></html>
