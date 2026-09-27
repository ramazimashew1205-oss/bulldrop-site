<?php
session_start();
if (!isset($_SESSION['balance'])) $_SESSION['balance'] = 1000.00;
if (!isset($_SESSION['user'])) $_SESSION['user'] = 'Игрок';
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>DROP — игровая платформа</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app">
<header class="topbar">
  <a class="brand" href="index.php"><span class="brand-mark">D</span><span>DROP</span></a>
  <div class="balance"><span>₽</span> <?=number_format($_SESSION['balance'], 2, '.', ' ') ?></div>
</header>
<main class="page">
  <section class="hero card">
    <div>
      <span class="eyebrow">DEMO MODE</span>
      <h1>Играй.<br><em>Поднимай.</em></h1>
      <p>Crash и Upgrade в одной минималистичной игровой платформе.</p>
      <a class="btn btn-primary" href="crash.php">Играть в Crash</a>
    </div>
    <div class="hero-orb"><span>2.41x</span></div>
  </section>
  <section class="section-head"><h2>Игры</h2><span>2 режима</span></section>
  <section class="games-grid">
    <a class="game-card card" href="crash.php"><div class="game-icon crash-icon">↗</div><div><strong>Crash</strong><small>Поймай множитель</small></div><span class="arrow">→</span></a>
    <a class="game-card card" href="upgrade.php"><div class="game-icon upgrade-icon">◆</div><div><strong>Upgrade</strong><small>Попробуй улучшить предмет</small></div><span class="arrow">→</span></a>
  </section>
  <section class="section-head"><h2>Аккаунт</h2></section>
  <a class="profile-card card" href="profile.php">
    <div class="avatar">R</div><div><strong><?=htmlspecialchars($_SESSION['user'])?></strong><small>Демо-профиль</small></div><span class="arrow">→</span>
  </a>
</main>
<nav class="bottom-nav">
  <a class="active" href="index.php"><span>⌂</span>Главная</a><a href="crash.php"><span>↗</span>Crash</a><a href="upgrade.php"><span>◆</span>Upgrade</a><a href="profile.php"><span>●</span>Профиль</a>
</nav>
</div>
</body>
</html>