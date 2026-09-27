<?php
session_start();
if(!isset($_SESSION['balance']))$_SESSION['balance']=1000.00;
if(!isset($_SESSION['user']))$_SESSION['user']='Игрок';
if(isset($_POST['reset'])){
  $_SESSION['balance']=1000.00;unset($_SESSION['crash_round']);
  if(isset($_SESSION['user_id'])){try{require __DIR__.'/config/database.php';$s=$pdo->prepare('UPDATE users SET balance=1000 WHERE id=?');$s->execute([$_SESSION['user_id']]);}catch(Throwable $e){}}
  header('Location: profile.php');exit;
}
$user=$_SESSION['user'];$history=$_SESSION['history']??[];
?>
<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><title>Профиль — DROP</title><link rel="stylesheet" href="assets/css/style.css?v=1.0.0"></head>
<body><div class="app">
<header class="topbar"><a class="brand" href="index.php"><span class="brand-mark">D</span>DROP</a><div class="balance"><span>₽</span><?=number_format((float)$_SESSION['balance'],2,'.',' ')?></div></header>
<main class="page">
<section class="card" style="padding:20px">
<div style="display:flex;align-items:center;gap:13px"><div class="avatar" style="width:58px;height:58px;font-size:22px"><?=htmlspecialchars(function_exists('mb_substr') ? mb_strtoupper(mb_substr($user,0,1)) : strtoupper(substr($user,0,1)))?></div><div><span class="eyebrow">PROFILE</span><h1 style="margin:4px 0 0;font-size:26px"><?=htmlspecialchars($user)?></h1><span class="muted" style="font-size:12px">Виртуальный аккаунт</span></div></div>
<div class="profile-grid"><div class="stat"><b><?=number_format((float)$_SESSION['balance'],2,'.',' ')?></b><span>Баланс</span></div><div class="stat"><b><?=count($history)?></b><span>Раундов</span></div></div>
<div class="action-row"><a class="btn btn-secondary" href="history.php">История</a><a class="btn btn-primary" href="crash.php">Играть</a></div>
<form method="post" style="margin-top:8px"><button class="btn btn-secondary wide" name="reset" value="1">Сбросить демо-баланс</button></form>
<a class="btn logout-btn wide" href="logout.php">↪ Выйти из аккаунта</a>
</section>
<section class="section-head"><h2>Правила демо</h2></section>
<div class="notice">Все ставки и выплаты виртуальные. Платёжных систем и вывода реальных денег в проекте нет.</div>
</main>
<nav class="bottom-nav"><a href="index.php"><span>⌂</span>Главная</a><a href="crash.php"><span>↗</span>Crash</a><a href="upgrade.php"><span>◆</span>Upgrade</a><a class="active" href="profile.php"><span>●</span>Профиль</a></nav>
</div></body></html>