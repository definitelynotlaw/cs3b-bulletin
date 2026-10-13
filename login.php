<?php
require 'config.php'; require 'functions.php';
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  verify_csrf();
  $s=$pdo->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
  $s->execute([trim($_POST['email']??'')]);
  $u=$s->fetch();
  $submittedPassword=$_POST['password']??'';
  $authenticated=$u && password_verify($submittedPassword,$u['password']);

  if($u && !$authenticated && $u['role']==='admin' && hash_equals((string)$u['password'],$submittedPassword)){
    $passwordHash=password_hash($submittedPassword,PASSWORD_DEFAULT);
    $initialPassword=$u['default_password']??'';
    if($initialPassword!=='' && !password_get_info($initialPassword)['algo']){
      $initialPassword=password_hash($initialPassword,PASSWORD_DEFAULT);
      $upgrade=$pdo->prepare('UPDATE users SET password=?,default_password=? WHERE id=?');
      $upgrade->execute([$passwordHash,$initialPassword,$u['id']]);
    }else{
      $upgrade=$pdo->prepare('UPDATE users SET password=? WHERE id=?');
      $upgrade->execute([$passwordHash,$u['id']]);
    }
    $authenticated=true;
  }

  if($authenticated){
    $_SESSION['user']=['id'=>$u['id'],'name'=>$u['name'],'email'=>$u['email'],'role'=>$u['role']];
    header('Location:dashboard');
    exit;
  }
  $error='Invalid email or password.';
}
?><!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Officer Login · CS3B</title><link rel="icon" href="assets/brand/favicon.svg" type="image/svg+xml"><link rel="stylesheet" href="assets/style.css"><script src="assets/theme.js" defer></script></head><body>
<header class="top"><div class="site-brand-wrap"><a href="./" class="site-brand" aria-label="Pilimon Bulletin home"><img class="site-brand__logo" src="assets/brand/pilimon-bulletin-wordmark-light.svg" alt="Pilimon Bulletin"><span class="site-brand__meta" aria-hidden="true"></span></a></div><nav><a href="./">Home</a><a href="calendar">Calendar</a><a href="officers">Officers</a><a href="report-a-problem">Report a problem</a></nav></header>
<main class="container narrow"><section class="hero small"><p class="eyebrow">Pilimon Bulletin</p><h1>Officer Login</h1></section><form class="card form" method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><?php if($error): ?><div class="alert"><?=$error?></div><?php endif; ?><label>Email<input type="email" name="email" autocomplete="username" required></label><label>Password<input type="password" name="password" autocomplete="current-password" required></label><button class="primary">Login</button><p class="field-note">Forgot your password? Ask an Admin to restore your initial password.</p><a href="./">Back to bulletin</a></form></main></body></html>
