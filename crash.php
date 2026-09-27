<?php
session_start();
if(!isset($_SESSION['balance']))$_SESSION['balance']=1000.00;
?>
<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>Crash — DROP</title><link rel="stylesheet" href="assets/css/style.css?v=1.1.0"></head>
<body><div class="app">
<header class="topbar"><a class="brand" href="index.php"><span class="brand-mark">D</span>DROP</a><div class="balance"><span>₽</span><b id="balance"><?=number_format((float)$_SESSION['balance'],2,'.',' ')?></b></div></header>
<main class="page game-page">
<div class="game-header"><div><span class="eyebrow">GAME 01</span><h1>Crash</h1></div><span class="muted">VIRTUAL</span></div>
<section class="chart card" id="crashChart"><div class="chart-grid"></div><div class="chart-line" id="chartLine"></div><div class="multiplier" id="multiplier">15</div></section>
<section class="bet-panel card">
<div class="bet-row"><input class="input" id="amount" type="number" min="1" max="100000" step="0.01" value="25" inputmode="decimal"></div>
<div class="quick"><button data-amount="10">10</button><button data-amount="25">25</button><button data-amount="50">50</button><button data-amount="100">100</button></div>
<div class="action-row"><button class="btn btn-primary" id="betBtn">Поставить</button><button class="btn btn-secondary" id="cashout" disabled>Забрать</button></div>
<p class="game-status muted" id="status">Приём ставок...</p>
</section>
<div class="stats"><div class="stat"><b id="roundStake">0.00</b><span>Ставка</span></div><div class="stat"><b id="liveWin">0.00</b><span>Текущая выплата</span></div><div class="stat"><b id="lastCrash">—</b><span>Последний краш</span></div></div>
<div class="notice" style="margin-top:12px">15 секунд на ставку → автоматический раунд → случайный краш → следующий раунд.</div>
</main>
<nav class="bottom-nav"><a href="index.php"><span>⌂</span>Главная</a><a class="active" href="crash.php"><span>↗</span>Crash</a><a href="upgrade.php"><span>◆</span>Upgrade</a><a href="profile.php"><span>●</span>Профиль</a></nav>
</div>
<script src="assets/js/app.js?v=1.1.0"></script>
<script>
let poll=null, phase='betting', myBet=null;
const $=id=>document.getElementById(id);
function balance(v){$('balance').textContent=Number(v).toLocaleString('ru-RU',{minimumFractionDigits:2,maximumFractionDigits:2});}
function stateClass(s){const c=$('crashChart');c.classList.remove('waiting','live','lost','win');c.classList.add(s);}
function resetVisual(){ $('chartLine').style.transform='none'; $('liveWin').textContent='0.00';}
function render(d){
  phase=d.phase;
  if(d.balance!==undefined)balance(d.balance);
  if(d.phase==='betting'){
    stateClass('waiting');resetVisual();
    $('multiplier').textContent=d.countdown;
    $('status').textContent=d.bet!==null?'Ставка принята. Ждём старт.':'Приём ставок · '+d.countdown+' сек.';
    $('status').className='game-status muted';
    $('betBtn').disabled=d.bet!==null;
    $('cashout').disabled=true;
    $('roundStake').textContent=d.bet===null?'0.00':Number(d.bet).toFixed(2);
    myBet=d.bet;
    return;
  }
  if(d.phase==='running'){
    stateClass('live');
    const m=Number(d.multiplier);
    $('multiplier').textContent=m.toFixed(2)+'x';
    $('chartLine').style.transform='skewY(-13deg) rotate(-2deg) scaleX('+Math.min(1.15,0.2+m/8)+')';
    $('status').textContent=d.bet!==null?'Раунд идёт · успей забрать':'Раунд идёт';
    $('status').className='game-status muted';
    $('betBtn').disabled=true;
    $('cashout').disabled=d.bet===null;
    $('roundStake').textContent=d.bet===null?'0.00':Number(d.bet).toFixed(2);
    $('liveWin').textContent=d.bet===null?'0.00':(Number(d.bet)*m).toFixed(2);
    myBet=d.bet;
    return;
  }
  stateClass('lost');resetVisual();
  const m=Number(d.multiplier);
  $('multiplier').textContent=m.toFixed(2)+'x';
  $('lastCrash').textContent=m.toFixed(2)+'x';
  $('status').textContent='КРАШ · следующий раунд скоро';
  $('status').className='game-status danger';
  $('betBtn').disabled=true;$('cashout').disabled=true;
  $('liveWin').textContent='0.00';
  setTimeout(()=>{},0);
}
async function pollState(){
  try{const r=await fetch('api/crash.php?state='+Date.now(),{cache:'no-store'});const d=await r.json();if(d.ok)render(d);}
  catch(e){$('status').textContent='Соединение с игрой потеряно.';$('status').className='game-status danger';}
}
$('betBtn').onclick=async()=>{
  const amount=Number($('amount').value);
  if(!Number.isFinite(amount)||amount<1){$('status').textContent='Введите ставку от 1.';$('status').className='game-status danger';return;}
  $('betBtn').disabled=true;
  try{
    const r=await fetch('api/crash.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'bet',amount})});
    const d=await r.json();if(!d.ok)throw new Error(d.message||'Ставка не принята');render(d);
  }catch(e){$('betBtn').disabled=false;$('status').textContent=e.message;$('status').className='game-status danger';}
};
$('cashout').onclick=async()=>{
  $('cashout').disabled=true;
  try{
    const r=await fetch('api/crash.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'cashout'})});
    const d=await r.json();
    if(d.balance!==undefined)balance(d.balance);
    if(d.ok){stateClass('win');$('multiplier').textContent=Number(d.multiplier).toFixed(2)+'x';$('liveWin').textContent=Number(d.payout).toFixed(2);$('status').textContent='Выплата зачислена.';$('status').className='game-status success';}
    else{stateClass('lost');$('multiplier').textContent=Number(d.multiplier||0).toFixed(2)+'x';$('status').textContent=d.message||'Краш.';$('status').className='game-status danger';}
    await pollState();
  }catch(e){$('status').textContent=e.message;$('status').className='game-status danger';}
};
pollState();poll=setInterval(pollState,250);
</script></body></html>