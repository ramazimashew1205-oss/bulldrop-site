<?php session_start();if(!isset($_SESSION['balance']))$_SESSION['balance']=1000.00;?>
<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><title>Upgrade — DROP</title><link rel="stylesheet" href="assets/css/style.css?v=1.0.0"></head>
<body><div class="app">
<header class="topbar"><a class="brand" href="index.php"><span class="brand-mark">D</span>DROP</a><div class="balance"><span>₽</span><b id="balance"><?=number_format((float)$_SESSION['balance'],2,'.',' ')?></b></div></header>
<main class="page game-page"><div class="game-header"><div><span class="eyebrow">GAME 02</span><h1>Upgrade</h1></div><span class="muted">25% WIN</span></div>
<section class="card" style="padding:14px"><div class="item-preview" id="item">◆</div><div class="stats"><div class="stat"><b>25%</b><span>Шанс успеха</span></div><div class="stat"><b>2.50x</b><span>Выплата</span></div><div class="stat"><b id="stakeView">25</b><span>Ставка</span></div></div>
<div style="margin-top:12px"><input class="input" id="amount" type="number" min="1" value="25" inputmode="decimal"></div>
<div class="quick"><button data-amount="10">10</button><button data-amount="25">25</button><button data-amount="50">50</button><button data-amount="100">100</button></div>
<button class="btn btn-primary wide" id="upgrade" style="margin-top:10px">Улучшить предмет</button><p class="game-status muted" id="result">Только виртуальные кредиты.</p></section>
<div class="notice" style="margin-top:12px">При успехе выплата составляет 2.50× от ставки. При неудаче ставка списывается.</div>
</main>
<nav class="bottom-nav"><a href="index.php"><span>⌂</span>Главная</a><a href="crash.php"><span>↗</span>Crash</a><a class="active" href="upgrade.php"><span>◆</span>Upgrade</a><a href="profile.php"><span>●</span>Профиль</a></nav></div>
<script src="assets/js/app.js?v=1.0.0"></script><script>
const amount=$('amount'),balance=$('balance'),result=$('result'),item=$('item'),stakeView=$('stakeView');
amount.oninput=()=>stakeView.textContent=Number(amount.value||0).toFixed(0);
function setBalance(v){balance.textContent=Number(v).toLocaleString('ru-RU',{minimumFractionDigits:2,maximumFractionDigits:2});}
document.getElementById('upgrade').onclick=async()=>{
 const stake=Number(amount.value);
 if(!Number.isFinite(stake)||stake<1){result.textContent='Введите ставку от 1.';result.className='game-status danger';return;}
 const btn=document.getElementById('upgrade');btn.disabled=true;result.className='game-status muted';result.textContent='Проверяем шанс…';
 try{const r=await fetch('api/upgrade.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({amount:stake})});const d=await r.json();if(!d.ok)throw new Error(d.message||'Ошибка');
 setBalance(d.balance);item.textContent=d.success?'★':'◆';result.textContent=d.success?'Успех! Выплата зачислена.':'Неудача. Ставка списана.';result.className=d.success?'game-status success':'game-status danger';
 }catch(e){result.textContent=e.message;result.className='game-status danger';}finally{btn.disabled=false;}
};
function $(id){return document.getElementById(id)}
</script></body></html>