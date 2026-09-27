<?php
session_start();
if(!isset($_SESSION['balance']))$_SESSION['balance']=1000.00;
?>
<!doctype html><html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>Crash — DROP</title><link rel="stylesheet" href="assets/css/style.css?v=1.2.0"></head>
<body><div class="app">
<header class="topbar"><a class="brand" href="index.php"><span class="brand-mark">D</span>DROP</a><div class="balance"><span>₽</span><b id="balance"><?=number_format((float)$_SESSION['balance'],2,'.',' ')?></b></div></header>
<main class="page game-page">
<div class="game-header"><div><span class="eyebrow">GAME 01</span><h1>Crash</h1></div><span class="muted">VIRTUAL</span></div>
<section class="chart card waiting" id="crashChart">
  <div class="chart-grid"></div>
  <div class="chart-line" id="chartLine"></div>
  <div class="chart-glow"></div>
  <div class="multiplier" id="multiplier">15</div>
</section>
<section class="bet-panel card">
<div class="bet-row"><input class="input" id="amount" type="number" min="1" max="100000" step="0.01" value="25" inputmode="decimal"></div>
<div class="quick"><button data-amount="10">10</button><button data-amount="25">25</button><button data-amount="50">50</button><button data-amount="100">100</button></div>
<div class="action-row"><button class="btn btn-primary" id="betBtn">Поставить</button><button class="btn btn-secondary" id="cashout" disabled>Забрать</button></div>
<p class="game-status muted" id="status">Приём ставок...</p>
</section>
<div class="stats"><div class="stat"><b id="roundStake">0.00</b><span>Ставка</span></div><div class="stat"><b id="liveWin">0.00</b><span>Текущая выплата</span></div><div class="stat"><b id="lastCrash">—</b><span>Последний краш</span></div></div>
<div class="notice" style="margin-top:12px">15 секунд на ставку → плавный рост → можно забрать выплату → раунд продолжится до реального краша.</div>
</main>
<nav class="bottom-nav"><a href="index.php"><span>⌂</span>Главная</a><a class="active" href="crash.php"><span>↗</span>Crash</a><a href="upgrade.php"><span>◆</span>Upgrade</a><a href="profile.php"><span>●</span>Профиль</a></nav>
</div>
<script src="assets/js/app.js?v=1.2.0"></script>
<script>
let poll=null, phase='betting', myBet=null, raf=null;
let syncStartedAt=0, syncClientAt=0, currentMultiplier=1.01, crashValue=null, cashedOut=false;
const $=id=>document.getElementById(id);

function balance(v){$('balance').textContent=Number(v).toLocaleString('ru-RU',{minimumFractionDigits:2,maximumFractionDigits:2});}
function stateClass(s){const c=$('crashChart');c.classList.remove('waiting','live','lost','win');c.classList.add(s);}
function resetVisual(){
  $('chartLine').style.transform='skewY(-13deg) rotate(-2deg) scaleX(.08)';
  $('liveWin').textContent='0.00';
}
function smoothMultiplier(){
  if(phase!=='running')return;
  const elapsed=Math.max(0,(performance.now()-syncClientAt)/1000 + syncStartedAt);
  let m=Math.exp(elapsed/2.05);
  if(crashValue!==null)m=Math.min(m,Number(crashValue));
  currentMultiplier=m;

  $('multiplier').textContent=m.toFixed(2)+'x';
  const visual=Math.min(1.35,Math.max(.08,.08 + Math.log(Math.max(1,m))*0.32));
  $('chartLine').style.transform='skewY(-13deg) rotate(-2deg) scaleX('+visual+')';

  if(myBet!==null && !cashedOut) $('liveWin').textContent=(Number(myBet)*m).toFixed(2);
  else $('liveWin').textContent='0.00';

  raf=requestAnimationFrame(smoothMultiplier);
}
function startSmooth(d){
  cancelAnimationFrame(raf);
  phase='running';
  crashValue=d.crash_value!==undefined?Number(d.crash_value):crashValue;
  const serverNow=Number(d.server_time_ms||Date.now());
  const started=Number(d.started_at_ms||serverNow);
  syncStartedAt=Math.max(0,(serverNow-started)/1000);
  syncClientAt=performance.now();
  smoothMultiplier();
}
function render(d){
  phase=d.phase;
  if(d.balance!==undefined)balance(d.balance);

  if(d.phase==='betting'){
    cancelAnimationFrame(raf);
    stateClass('waiting');resetVisual();
    $('multiplier').textContent=d.countdown;
    $('status').textContent=d.bet!==null?'Ставка принята. Ждём старт.':'Приём ставок · '+d.countdown+' сек.';
    $('status').className='game-status muted';
    $('betBtn').disabled=d.bet!==null;
    $('cashout').disabled=true;
    $('roundStake').textContent=d.bet===null?'0.00':Number(d.bet).toFixed(2);
    $('liveWin').textContent='0.00';
    myBet=d.bet;cashedOut=false;crashValue=null;
    return;
  }

  if(d.phase==='running'){
    stateClass(cashedOut?'win':'live');
    myBet=d.bet;
    cashedOut=Boolean(d.cashed_out);
    crashValue=d.crash_value!==undefined?Number(d.crash_value):crashValue;

    if(d.cashout_multiplier!==null && d.cashout_multiplier!==undefined){
      $('status').textContent='Забрано на '+Number(d.cashout_multiplier).toFixed(2)+'x · раунд продолжается';
      $('status').className='game-status success';
    }else{
      $('status').textContent=d.bet!==null?'Раунд идёт · успей забрать':'Раунд идёт';
      $('status').className='game-status muted';
    }

    $('betBtn').disabled=true;
    $('cashout').disabled=d.bet===null || cashedOut;
    $('roundStake').textContent=d.bet===null?'0.00':Number(d.bet).toFixed(2);
    startSmooth(d);
    return;
  }

  cancelAnimationFrame(raf);
  stateClass('lost');
  const m=Number(d.multiplier);
  $('multiplier').textContent=m.toFixed(2)+'x';
  $('lastCrash').textContent=m.toFixed(2)+'x';
  $('status').textContent=d.cashed_out
    ? 'КРАШ на '+m.toFixed(2)+'x · ваша выплата уже забрана'
    : 'КРАШ · следующий раунд скоро';
  $('status').className=d.cashed_out?'game-status success':'game-status danger';
  $('betBtn').disabled=true;$('cashout').disabled=true;
  $('liveWin').textContent='0.00';
  myBet=null;cashedOut=false;crashValue=null;
}

async function pollState(){
  try{
    const r=await fetch('api/crash.php?state='+Date.now(),{cache:'no-store'});
    const d=await r.json();
    if(d.ok)render(d);
  }catch(e){
    $('status').textContent='Соединение с игрой потеряно.';
    $('status').className='game-status danger';
  }
}

$('betBtn').onclick=async()=>{
  const amount=Number($('amount').value);
  if(!Number.isFinite(amount)||amount<1){
    $('status').textContent='Введите ставку от 1.';
    $('status').className='game-status danger';
    return;
  }
  $('betBtn').disabled=true;
  try{
    const r=await fetch('api/crash.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'bet',amount})});
    const d=await r.json();
    if(!d.ok)throw new Error(d.message||'Ставка не принята');
    render(d);
  }catch(e){
    $('betBtn').disabled=false;$('status').textContent=e.message;$('status').className='game-status danger';
  }
};

$('cashout').onclick=async()=>{
  $('cashout').disabled=true;
  try{
    const r=await fetch('api/crash.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'cashout'})});
    const d=await r.json();
    if(d.balance!==undefined)balance(d.balance);

    if(d.ok){
      myBet=null;cashedOut=true;
      $('roundStake').textContent='0.00';
      $('liveWin').textContent=Number(d.payout).toFixed(2);
      stateClass('win');
      $('status').textContent='Забрано на '+Number(d.cashout_multiplier).toFixed(2)+'x · раунд продолжается';
      $('status').className='game-status success';
      startSmooth(d);
      $('cashout').disabled=true;
    }else{
      stateClass('lost');
      $('multiplier').textContent=Number(d.multiplier||0).toFixed(2)+'x';
      $('status').textContent=d.message||'Краш.';
      $('status').className='game-status danger';
      await pollState();
    }
  }catch(e){
    $('status').textContent=e.message;$('status').className='game-status danger';
  }
};

pollState();
poll=setInterval(pollState,500);
</script></body></html>