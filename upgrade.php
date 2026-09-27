<?php
session_start();
require __DIR__.'/config/database.php';
if(!isset($_SESSION['user_id'])){header('Location: login.php');exit;}
if(!isset($_SESSION['balance']))$_SESSION['balance']=1000.00;
$userId=(int)$_SESSION['user_id'];
try{
  $s=$pdo->prepare('SELECT username,balance,is_blocked FROM users WHERE id=? LIMIT 1');$s->execute([$userId]);$u=$s->fetch();
  if(!$u || (int)$u['is_blocked']===1){session_destroy();header('Location: login.php');exit;}
  $_SESSION['user']=$u['username'];$_SESSION['balance']=(float)$u['balance'];
}catch(Throwable $e){}
$items=[
 ['name'=>'Iron Wolf','price'=>50,'icon'=>'🧤'],
 ['name'=>'Arctic Camo','price'=>100,'icon'=>'❄️'],
 ['name'=>'Living Flame','price'=>250,'icon'=>'🔥'],
 ['name'=>'Neon Phantom','price'=>500,'icon'=>'⚡'],
];
$source=$items[0];
?>
<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><title>Upgrade — BULLDROP</title><link rel="stylesheet" href="assets/css/style.css?v=2.4.0"></head>
<body><div class="app">
<header class="topbar"><a class="brand" href="index.php"><span class="brand-mark">B</span><span>BULLDROP</span></a><div class="balance"><span>₽</span><b id="balance"><?=number_format((float)$_SESSION['balance'],2,'.',' ')?></b></div></header>
<main class="page game-page">
<div class="game-header"><div><span class="eyebrow">GAME 02</span><h1>Upgrade</h1></div><span class="muted" id="chanceTop">50.00% WIN</span></div>

<section class="upgrade-machine card">
  <div class="machine-pipes pipe-left"></div><div class="machine-pipes pipe-right"></div>
  <div class="machine">
    <div class="machine-ring ring-outer"></div>
    <div class="machine-ring ring-mid"></div>
    <div class="machine-ring ring-inner"></div>
    <div class="chance-orbit" id="chanceOrbit"><span class="chance-dot"></span></div>
    <div class="machine-core">
      <span class="machine-label">CHANCE</span>
      <strong id="chance">50.00%</strong>
      <span class="machine-state" id="machineState">READY</span>
    </div>
    <div class="machine-bolt b1"></div><div class="machine-bolt b2"></div><div class="machine-bolt b3"></div><div class="machine-bolt b4"></div>
  </div>
  <div class="target-row">
    <button class="item-card selected" data-price="50" data-name="Iron Wolf"><span class="item-art">🧤</span><span><b>Iron Wolf</b><small>50 ₽</small></span><i>YOUR ITEM</i></button>
    <div class="swap-arrow">→</div>
    <button class="item-card target" data-price="100" data-name="Arctic Camo"><span class="item-art">❄️</span><span><b id="targetName">Arctic Camo</b><small id="targetPrice">100 ₽</small></span><i>TARGET</i></button>
  </div>
  <div class="target-options">
    <?php foreach(array_slice($items,1) as $item): ?>
      <button class="target-option" data-price="<?=$item['price']?>" data-name="<?=htmlspecialchars($item['name'])?>"><?=$item['icon']?> <b><?=htmlspecialchars($item['name'])?></b><small><?=$item['price']?> ₽</small></button>
    <?php endforeach; ?>
  </div>
</section>

<section class="card upgrade-controls">
  <div class="control-head"><span>МНОЖИТЕЛЬ</span><strong id="multiplierView">×2.00</strong></div>
  <div class="multiplier-buttons">
    <button data-mult="2">×2</button><button data-mult="3">×3</button><button data-mult="10">×10</button><button data-mult="26">×26</button>
  </div>
  <div class="control-head amount-head"><span>ВАШ ПРЕДМЕТ</span><strong><span id="sourcePrice">50</span> ₽</strong></div>
  <div class="upgrade-actions"><input class="input" id="amount" type="number" min="1" step="0.01" value="50" inputmode="decimal"><button class="btn btn-primary" id="upgrade">Апгрейд</button></div>
  <div class="promo-row"><input class="input" id="promo" placeholder="Промокод"><button class="btn btn-secondary" id="applyPromo">Apply</button></div>
  <p class="game-status muted" id="result">Шанс рассчитывается по соотношению стоимости предметов.</p>
</section>
<div class="notice" style="margin-top:12px">В игре используются только виртуальные кредиты. Результат рассчитывается на сервере, а рулетка показывает анимацию уже после получения результата.</div>
</main>
<nav class="bottom-nav"><a href="index.php"><span>⌂</span>Главная</a><a href="crash.php"><span>↗</span>Crash</a><a class="active" href="upgrade.php"><span>◆</span>Upgrade</a><a href="profile.php"><span>●</span>Профиль</a></nav></div>
<script>
const $=id=>document.getElementById(id);
let sourcePrice=50,targetPrice=100;
const amount=$('amount');
function chance(){return Math.min(95,Math.max(0.01,(sourcePrice/targetPrice)*100));}
function render(){
 const c=chance();$('chance').textContent=c.toFixed(2)+'%';$('chanceTop').textContent=c.toFixed(2)+'% WIN';$('sourcePrice').textContent=Number(sourcePrice).toFixed(0);
 $('multiplierView').textContent='×'+(targetPrice/sourcePrice).toFixed(2);
 document.documentElement.style.setProperty('--upgrade-chance',c);
}
document.querySelectorAll('.target-option').forEach(b=>b.onclick=()=>{
 document.querySelectorAll('.target-option').forEach(x=>x.classList.remove('active'));b.classList.add('active');
 targetPrice=Number(b.dataset.price);$('targetName').textContent=b.dataset.name;$('targetPrice').textContent=targetPrice+' ₽';render();
});
document.querySelectorAll('.multiplier-buttons button').forEach(b=>b.onclick=()=>{
 const m=Number(b.dataset.mult);targetPrice=Math.max(sourcePrice*m,sourcePrice+0.01);
 $('targetName').textContent='Target ×'+m;$('targetPrice').textContent=targetPrice.toFixed(0)+' ₽';render();
});
amount.oninput=()=>{sourcePrice=Math.max(1,Number(amount.value)||1);render();};
$('applyPromo').onclick=()=>{$('result').textContent='Промокоды пока недоступны в демо-режиме.';$('result').className='game-status warning';};
$('upgrade').onclick=async()=>{
 const stake=Number(amount.value);if(!Number.isFinite(stake)||stake<1){$('result').textContent='Введите стоимость от 1 ₽.';return;}
 sourcePrice=stake;render();const btn=$('upgrade');btn.disabled=true;$('machineState').textContent='ROLLING';$('result').textContent='Рулетка запускается…';$('result').className='game-status muted';
 document.querySelector('.upgrade-machine').classList.add('rolling');
 try{
  const r=await fetch('api/upgrade.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({amount:stake,target_price:targetPrice})});
  const d=await r.json();if(!d.ok)throw new Error(d.message||'Ошибка');
  setTimeout(()=>finish(d),900);
 }catch(e){btn.disabled=false;$('machineState').textContent='ERROR';$('result').textContent=e.message;$('result').className='game-status danger';document.querySelector('.upgrade-machine').classList.remove('rolling');}
};
function finish(d){
 document.querySelector('.upgrade-machine').classList.remove('rolling');$('balance').textContent=Number(d.balance).toLocaleString('ru-RU',{minimumFractionDigits:2,maximumFractionDigits:2});
 $('machineState').textContent=d.success?'SUCCESS':'FAILED';
 $('result').textContent=d.success?'Успех! Предмет улучшен.':'Неудача. Стоимость исходного предмета списана.';
 $('result').className=d.success?'game-status success':'game-status danger';
 document.querySelector('.machine').classList.toggle('success',d.success);document.querySelector('.machine').classList.toggle('failed',!d.success);
 $('upgrade').disabled=false;
}
render();
</script></body></html>