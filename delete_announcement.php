<?php
require 'config.php'; require 'functions.php'; require_officer();
$id=(int)($_GET['id']??0);$s=$pdo->prepare("DELETE FROM announcements WHERE id=?");$s->execute([$id]);header('Location:dashboard');exit;
