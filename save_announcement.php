<?php
require 'config.php'; require 'functions.php'; require_officer();
if($_SERVER['REQUEST_METHOD']!=='POST') exit;
verify_csrf(); $id=(int)($_POST['id']??0); $title=trim($_POST['title']??'');$cat=$_POST['category']??'General';$caption=trim($_POST['caption']??'');$status=$_POST['status']??'draft';$url=trim($_POST['video_url']??'');
$image=upload_file('image','uploads/images',['image/jpeg','image/png','image/webp'],5*1024*1024);
$video=upload_file('video','uploads/videos',['video/mp4','video/webm','video/ogg'],30*1024*1024);
if($id){
 $old=$pdo->prepare("SELECT * FROM announcements WHERE id=?");$old->execute([$id]);$o=$old->fetch();
 $image=$image?:$o['image_path']; $video=$video?:$o['video_path'];
 $s=$pdo->prepare("UPDATE announcements SET title=?,category=?,caption=?,image_path=?,video_path=?,video_url=?,status=?,updated_at=NOW() WHERE id=?");
 $s->execute([$title,$cat,$caption,$image,$video,$url,$status,$id]);
}else{
 $s=$pdo->prepare("INSERT INTO announcements(title,category,caption,image_path,video_path,video_url,status,author_id) VALUES(?,?,?,?,?,?,?,?)");
 $s->execute([$title,$cat,$caption,$image,$video,$url,$status,$_SESSION['user']['id']]);
}
header('Location:dashboard');exit;
