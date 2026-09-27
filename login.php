<?php
session_start();require __DIR__.'/config/database.php';
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $username=trim($_POST['username']??'');$password=$_POST['password']??'';
  $s=$pdo->prepare('SELECT id,username,password_hash,role,balance FROM users WHERE username=? LIMIT 1');$s->execute([$username]);$u=$s->fetch();
  if($u && password_verify($password,$u['password_hash'])){
    session_regenerate_id(true);$_SESSION['user_id']=$u['id'];$_SESSION['user']=$u['username'];$_SESSION['role']=$u['role'];$_SESSION['balance']=(float)$u['balance'];header('Location: profile.php');exit;
  }$error='Неверный логин или пароль.';
}
?>
<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><title>Вход — DROP</title><link rel="stylesheet" href="assets/css/style.css?v=1.0.0"></head>
<body><div class="app"><main class="page" style="padding-top:60px"><section class="card" style="padding:22px"><a class="brand" href="index.php"><span class="brand-mark">D</span>DROP</a><span class="eyebrow" style="margin-top:28px">ACCOUNT</span><h1 style="margin:7px 0 5px">Вход</h1><p class="muted">Продолжи игру с сохранённым балансом.</p><?php if($error):?><p class="danger"><?=$error?></p><?php endif;?><form method="post"><input class="input" name="username" placeholder="Логин" autocomplete="username" required><input class="input" style="margin-top:8px" name="password" type="password" placeholder="Пароль" autocomplete="current-password" required><button class="btn btn-primary wide" style="margin-top:10px">Войти</button></form><a class="btn btn-secondary wide" style="margin-top:8px" href="register.php">Создать аккаунт</a></section></main></div></body></html>