<?php
session_start();
if(!isset($_SESSION['balance'])) $_SESSION['balance']=1000.00;
?>
<!doctype html>
<html lang="ru"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>Crash — DROP</title><link rel="stylesheet" href="assets/css/style.css?v=1.0.0"></head>
<body><div class="app">
<header class="topbar"><a class="brand" href="index.php"><span class="brand-mark">D</span>DROP</a><div class="balance"><span>₽</span><b id="balance"><?=number_format((float)$_SESSION['balance'],2,'.',' ')?></b></div></header>
<main class="page game-page">
<div class="game-header"><div><span class="eyebrow">GAME 01</span><h1>Crash</h1></div><span class="muted">VIRTUAL</span></div>
<section class="chart card live" id="crashChart"><div class="chart-line" id="chartLine"></div><div class="multiplier" id="multiplier">1.00x</div></section>
<section class="bet-panel card">
<div class="bet-row"><input class="input" id="amount" name="amount" type="number" min="1" max="100000" step="1" value="25" inputmode="decimal"></div>
<div class="quick"><button data-amount="10">10</button><button data-amount="25">25</button><button data-amount="50">50</button><button data-amount="100">100</button></div>
<div class="action-row"><button class="btn btn-primary" id="start">Начать</button><button class="btn btn-secondary" id="cashout" disabled>Забрать</button></div>
<p class="game-status muted" id="status">Ставка списывается только после запуска раунда.</p>
</section>
<div class="stats"><div class="stat"><b id="roundStake">0.00</b><span>Ставка</span></div><div class="stat"><b id="liveWin">0.00</b><span>Текущая выплата</span></div><div class="stat"><b id="lastCrash">—</b><span>Последний краш</span></div></div>
<div class="notice" style="margin-top:12px">Игра работает на виртуальном балансе. Перезагрузка страницы не превращает виртуальные кредиты в реальные деньги.</div>
</main>
<nav class="bottom-nav"><a href="index.php"><span>⌂</span>Главная</a><a class="active" href="crash.php"><span>↗</span>Crash</a><a href="upgrade.php"><span>◆</span>Upgrade</a><a href="profile.php"><span>●</span>Профиль</a></nav>
</div>
<script src="assets/js/app.js?v=1.0.0"></script>
<script>
let timer=null,round=null,lastTs=0;
const $=id=>document.getElementById(id);
function setBalance(v){$('balance').textContent=Number(v).toLocaleString('ru-RU',{minimumFractionDigits:2,maximumFractionDigits:2});}
function stop(){if(timer){clearInterval(timer);timer=null;}}
function setChartState(state){const chart=$('crashChart');chart.classList.remove('live','lost','win');chart.classList.add(state);}
$('start').onclick=async()=>{
  stop();
  const amount=Number($('amount').value);
  if(!Number.isFinite(amount)||amount<1){$('status').textContent='Введите ставку от 1.';$('status').className='game-status danger';return;}
  $('start').disabled=true;$('cashout').disabled=true;$('status').className='game-status muted';$('status').textContent='Запускаем раунд…';
  try{
    const r=await fetch('api/crash.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'start',amount})});
    const d=await r.json();
    if(!d.ok) throw new Error(d.message||'Не удалось начать раунд');
    round=d;setBalance(d.balance);setChartState('live');$('roundStake').textContent=d.amount.toFixed(2);$('liveWin').textContent=d.amount.toFixed(2);$('lastCrash').textContent='—';
    $('cashout').disabled=false;$('status').textContent='Забери выплату до краша.';$('status').className='game-status muted';
    lastTs=performance.now();
    timer=setInterval(async()=>{
      const elapsed=(performance.now()-lastTs)/1000;
      const m=Math.min(d.crash_at,1+elapsed*0.72+elapsed*elapsed*0.035);
      $('multiplier').textContent=m.toFixed(2)+'x';$('liveWin').textContent=(d.amount*m).toFixed(2);
      $('chartLine').style.transform='skewY(' + (-13+Math.min(10,elapsed*1.5)) + 'deg) rotate(-2deg)';
      if(m>=d.crash_at){
        stop();
        $('cashout').disabled=true;
        $('multiplier').textContent=d.crash_at.toFixed(2)+'x';
        $('lastCrash').textContent=d.crash_at.toFixed(2)+'x';
        $('status').textContent='Краш. Ставка потеряна.';
        $('status').className='game-status danger';
        $('liveWin').textContent='0.00';
        setChartState('lost');
        try{
          const rr=await fetch('api/crash.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'crash'})});
          const dd=await rr.json();
          if(dd.balance!==undefined)setBalance(dd.balance);
        }catch(e){}
        round=null;
        $('start').disabled=false;
      }
    },60);
  }catch(e){$('start').disabled=false;$('status').textContent=e.message;$('status').className='game-status danger';}
};
$('cashout').onclick=async()=>{
  if(!round)return;
  $('cashout').disabled=true;stop();
  try{
    const r=await fetch('api/crash.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'cashout'})});
    const d=await r.json();
    if(!d.ok) throw new Error(d.message||'Не удалось забрать выплату');
    setBalance(d.balance);setChartState('win');$('multiplier').textContent=d.multiplier.toFixed(2)+'x';$('lastCrash').textContent=d.multiplier.toFixed(2)+'x';$('status').textContent='Выплата зачислена.';$('status').className='game-status success';$('start').disabled=false;$('liveWin').textContent=d.payout.toFixed(2);round=null;
  }catch(e){$('status').textContent=e.message;$('status').className='game-status danger';$('start').disabled=false;round=null;setChartState('lost');}
};
</script></body></html>