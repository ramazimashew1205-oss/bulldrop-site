<?php
session_start();require __DIR__.'/config/database.php';
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  $username=trim($_POST['username']??'');$password=$_POST['password']??'';
  if(!preg_match('/^[p{L}p{N}_-]{3,32}$/u',$username))$error='Логин: 3–32 символа, буквы, цифры, _ или -.';
  elseif(strlen($password)<6)$error='Пароль должен быть не короче 6 символов.';
  else{
    try{$hash=password_hash($password,PASSWORD_DEFAULT);$s=$pdo->prepare('INSERT INTO users(username,password_hash,role,balance) VALUES(?,?,?,?)');$s->execute([$username,$hash,'user',1000]);$_SESSION['user_id']=$pdo->lastInsertId();$_SESSION['user']=$username;$_SESSION['role']='user';$_SESSION['balance']=1000.00;header('Location: profile.php');exit;}
    catch(PDOException $e){$error='Этот логин уже занят.';}
  }
}
?>
<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><title>Регистрация — DROP</title><link rel="stylesheet" href="assets/css/style.css?v=1.0.0"></head>
<body><div class="app"><main class="page" style="padding-top:60px"><section class="card" style="padding:22px"><a class="brand" href="index.php"><span class="brand-mark">D</span>DROP</a><span class="eyebrow" style="margin-top:28px">ACCOUNT</span><h1 style="margin:7px 0 5px">Регистрация</h1><p class="muted">Создай виртуальный аккаунт с балансом 1 000 ₽.</p><?php if($error):?><p class="danger"><?=$error?></p><?php endif;?><form method="post"><input class="input" name="username" placeholder="Логин" autocomplete="username" required><input class="input" style="margin-top:8px" name="password" type="password" placeholder="Пароль от 6 символов" autocomplete="new-password" required><button class="btn btn-primary wide" style="margin-top:10px">Создать аккаунт</button></form><a class="btn btn-secondary wide" style="margin-top:8px" href="login.php">Уже есть аккаунт</a></section></main></div></body></html>