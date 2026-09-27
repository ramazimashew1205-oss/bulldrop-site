<?php
session_start();
$rows=$_SESSION['history']??[];
if(isset($_SESSION['user_id'])){
  try{require __DIR__.'/config/database.php';$s=$pdo->prepare('SELECT game,amount,multiplier,result,created_at FROM game_history WHERE user_id=? ORDER BY id DESC LIMIT 50');$s->execute([$_SESSION['user_id']]);$dbRows=$s->fetchAll();if($dbRows)$rows=$dbRows;}catch(Throwable $e){}
}
?>
<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><title>История — DROP</title><link rel="stylesheet" href="assets/css/style.css?v=1.0.0"></head>
<body><div class="app"><header class="topbar"><a class="brand" href="index.php"><span class="brand-mark">D</span>DROP</a><a class="btn btn-secondary" href="profile.php" style="min-height:38px;padding:0 13px">Профиль</a></header>
<main class="page"><div class="game-header"><div><span class="eyebrow">ACTIVITY</span><h1>История</h1></div><span class="muted"><?=count($rows)?> записей</span></div>
<section class="card" style="padding:14px"><?php if(!$rows):?><div class="empty">Пока нет завершённых игр.<br>Запусти Crash или Upgrade.</div><?php else:foreach($rows as $r):?><div class="table-row"><div><b><?=htmlspecialchars($r['game'])?></b><small class="muted" style="display:block;margin-top:4px"><?=htmlspecialchars($r['created_at'])?></small></div><div style="text-align:right"><b class="<?=in_array($r['result'],['win','cashout'])?'success':'danger'?>"><?=htmlspecialchars($r['result'])?></b><small class="muted" style="display:block;margin-top:4px"><?=number_format((float)$r['amount'],2,'.',' ')?> ₽<?php if($r['multiplier']!==null):?> · <?=number_format((float)$r['multiplier'],2,'.',' ')?>x<?php endif;?></small></div></div><?php endforeach;endif;?></section>
</main>
<nav class="bottom-nav"><a href="index.php"><span>⌂</span>Главная</a><a href="crash.php"><span>↗</span>Crash</a><a href="upgrade.php"><span>◆</span>Upgrade</a><a class="active" href="profile.php"><span>●</span>Профиль</a></nav>
</div></body></html>