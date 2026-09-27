<?php
session_start();
if (!isset($_SESSION['balance'])) $_SESSION['balance']=1000.00;
if (!isset($_SESSION['user'])) $_SESSION['user']='Игрок';
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>DROP — игровая платформа</title><link rel="stylesheet" href="assets/css/style.css?v=1.0.0">
</head>
<body>
<div class="app">
<header class="topbar">
<a class="brand" href="index.php"><span class="brand-mark">D</span><span>DROP</span></a>
<div class="balance"><span>₽</span><?=number_format((float)$_SESSION['balance'],2,'.',' ')?></div>
</header>
<main class="page">
<section class="hero card">
<div class="hero-copy">
<span class="eyebrow">DEMO PLATFORM</span>
<h1>Играй.<br><em>Лови.</em><br>Забирай.</h1>
<p>Две быстрые мини-игры с виртуальными кредитами. Никаких реальных платежей — только демо-режим.</p>
<a class="btn btn-primary" href="crash.php">Играть в Crash</a>
</div>
<div class="hero-orb"><span>2.41x</span></div>
</section>

<section class="section-head"><h2>Игры</h2><span>2 режима</span></section>
<section class="games-grid">
<a class="game-card card" href="crash.php">
<div class="game-icon crash-icon">↗</div><div><strong>Crash</strong><small>Забери ставку до краша</small></div><span class="arrow">→</span>
</a>
<a class="game-card card" href="upgrade.php">
<div class="game-icon upgrade-icon">◆</div><div><strong>Upgrade</strong><small>Улучши виртуальный предмет</small></div><span class="arrow">→</span>
</a>
</section>

<section class="section-head"><h2>Аккаунт</h2><span>Демо</span></section>
<a class="profile-card card" href="profile.php">
<div class="avatar"><?=htmlspecialchars(mb_strtoupper(mb_substr($_SESSION['user'],0,1)))?></div>
<div><strong><?=htmlspecialchars($_SESSION['user'])?></strong><small>Баланс и история игр</small></div>
<span class="arrow">→</span>
</a>
</main>
<nav class="bottom-nav">
<a class="active" href="index.php"><span>⌂</span>Главная</a>
<a href="crash.php"><span>↗</span>Crash</a>
<a href="upgrade.php"><span>◆</span>Upgrade</a>
<a href="profile.php"><span>●</span>Профиль</a>
</nav>
</div>
</body></html>