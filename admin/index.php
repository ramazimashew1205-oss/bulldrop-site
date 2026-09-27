<?php
session_start();
require __DIR__.'/../config/database.php';
if(!isset($_SESSION['user_id']) || ($_SESSION['role']??'user')!=='admin'){header('Location: ../login.php');exit;}
$count=(int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$games=(int)$pdo->query('SELECT COUNT(*) FROM game_history')->fetchColumn();
?>
<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><title>Admin — DROP</title><link rel="stylesheet" href="../assets/css/style.css?v=1.0.0"></head>
<body><div class="app"><header class="topbar"><a class="brand" href="../index.php"><span class="brand-mark">D</span>DROP</a><a class="btn btn-secondary" href="../logout.php" style="min-height:38px;padding:0 13px">Выйти</a></header>
<main class="page"><div class="game-header"><div><span class="eyebrow">ADMIN</span><h1>Панель</h1></div></div><div class="stats"><div class="stat"><b><?=$count?></b><span>Пользователей</span></div><div class="stat"><b><?=$games?></b><span>Игр</span></div><div class="stat"><b>v1.0.0</b><span>Релиз</span></div></div><section class="card" style="padding:18px;margin-top:12px"><p class="muted" style="margin:0">Админ-панель доступна только аккаунтам с ролью <b>admin</b>.</p></section></main></div></body></html>