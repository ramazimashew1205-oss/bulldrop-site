<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if(!isset($_SESSION['balance'])) $_SESSION['balance']=1000.00;

function out($data){echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_PRESERVE_ZERO_FRACTION);exit;}
function now(){return microtime(true);}
function balance_save($v){
  $_SESSION['balance']=round((float)$v,2);
  if(isset($_SESSION['user_id'])){
    try{require __DIR__.'/../config/database.php';$s=$pdo->prepare('UPDATE users SET balance=? WHERE id=?');$s->execute([$_SESSION['balance'],$_SESSION['user_id']]);}catch(Throwable $e){}
  }
}
function add_history($game,$amount,$multiplier,$result){
  if(!isset($_SESSION['history']))$_SESSION['history']=[];
  $_SESSION['history'][]=['game'=>$game,'amount'=>$amount,'multiplier'=>$multiplier,'result'=>$result,'created_at'=>date('Y-m-d H:i:s')];
  if(count($_SESSION['history'])>50)array_shift($_SESSION['history']);
  if(isset($_SESSION['user_id'])){
    try{require __DIR__.'/../config/database.php';$s=$pdo->prepare('INSERT INTO game_history(user_id,game,amount,multiplier,result) VALUES(?,?,?,?,?)');$s->execute([$_SESSION['user_id'],$game,$amount,$multiplier,$result]);}catch(Throwable $e){}
  }
}
function ensure_cycle(){
  if(empty($_SESSION['crash_cycle'])){
    $t=now();
    $_SESSION['crash_cycle']=[
      'phase'=>'betting',
      'betting_until'=>$t+15,
      'started_at'=>null,
      'crash_at'=>null,
      'crash_value'=>null,
      'bet'=>null,
      'crash_handled'=>false
    ];
  }
}
function random_crash(){
  // Random value in cents: 1.01x ... 500.00x, never forced to an integer.
  return mt_rand(101,50000)/100;
}
function state(){
  ensure_cycle();
  $c=&$_SESSION['crash_cycle'];
  $t=now();

  if($c['phase']==='betting' && $t >= $c['betting_until']){
    $c['phase']='running';
    $c['started_at']=$t;
    $c['crash_value']=random_crash();
    $c['crash_at']=$t + max(0.55, log($c['crash_value'])*2.05);
  }

  if($c['phase']==='running'){
    $elapsed=max(0,$t-$c['started_at']);
    $m=round(exp($elapsed/2.05),2);
    if($t >= $c['crash_at'] || $m >= $c['crash_value']){
      $c['phase']='crashed';
      $c['crashed_at']=$t;
      $c['multiplier']=$c['crash_value'];
      if(!$c['crash_handled'] && $c['bet']!==null){
        add_history('Crash',$c['bet'],$c['crash_value'],'loss');
        $c['crash_handled']=true;
      }
    }
  }

  if($c['phase']==='crashed' && $t >= ($c['crashed_at']+2.0)){
    $c=[
      'phase'=>'betting',
      'betting_until'=>$t+15,
      'started_at'=>null,
      'crash_at'=>null,
      'crash_value'=>null,
      'bet'=>null,
      'crash_handled'=>false
    ];
  }

  $c=$_SESSION['crash_cycle'];
  if($c['phase']==='betting'){
    out(['ok'=>true,'phase'=>'betting','countdown'=>max(0,ceil($c['betting_until']-$t)),'bet'=>$c['bet'],'balance'=>$_SESSION['balance']]);
  }
  if($c['phase']==='running'){
    $elapsed=max(0,$t-$c['started_at']);
    $m=min($c['crash_value'],round(exp($elapsed/2.05),2));
    out(['ok'=>true,'phase'=>'running','multiplier'=>$m,'bet'=>$c['bet'],'balance'=>$_SESSION['balance']]);
  }
  out(['ok'=>true,'phase'=>'crashed','multiplier'=>$c['crash_value'],'bet'=>$c['bet'],'balance'=>$_SESSION['balance']]);
}

$input=json_decode(file_get_contents('php://input'),true) ?: $_POST;
$action=$input['action']??'state';

if($action==='state')state();

if($action==='bet'){
  ensure_cycle();
  $c=&$_SESSION['crash_cycle'];
  $t=now();
  if($c['phase']!=='betting')out(['ok'=>false,'message'=>'Приём ставок уже закрыт.','phase'=>$c['phase']]);
  if($c['bet']!==null)out(['ok'=>false,'message'=>'Ставка на этот раунд уже установлена.']);
  $amount=round((float)($input['amount']??0),2);
  if($amount<1)out(['ok'=>false,'message'=>'Минимальная ставка — 1.']);
  if($amount>$_SESSION['balance'])out(['ok'=>false,'message'=>'Недостаточно виртуальных кредитов.']);
  $c['bet']=$amount;
  balance_save($_SESSION['balance']-$amount);
  out(['ok'=>true,'phase'=>'betting','bet'=>$amount,'countdown'=>max(0,ceil($c['betting_until']-$t)),'balance'=>$_SESSION['balance']]);
}

if($action==='cashout'){
  ensure_cycle();
  $c=&$_SESSION['crash_cycle'];
  $t=now();
  if($c['phase']!=='running' || $c['bet']===null)out(['ok'=>false,'message'=>'Сейчас нельзя забрать выплату.']);
  $m=min($c['crash_value'],round(exp(max(0,$t-$c['started_at'])/2.05),2));
  if($t >= $c['crash_at'] || $m >= $c['crash_value']){
    $c['phase']='crashed';$c['crashed_at']=$t;$c['multiplier']=$c['crash_value'];
    if(!$c['crash_handled']){add_history('Crash',$c['bet'],$c['crash_value'],'loss');$c['crash_handled']=true;}
    out(['ok'=>false,'crashed'=>true,'multiplier'=>$c['crash_value'],'balance'=>$_SESSION['balance'],'message'=>'Краш на '.$c['crash_value'].'x.']);
  }
  $payout=round($c['bet']*$m,2);
  balance_save($_SESSION['balance']+$payout);
  add_history('Crash',$c['bet'],$m,'cashout');
  $c=[
    'phase'=>'crashed','crashed_at'=>$t,'bet'=>null,'crash_value'=>$c['crash_value'],'crash_at'=>$c['crash_at'],
    'started_at'=>$c['started_at'],'multiplier'=>$m,'crash_handled'=>true
  ];
  out(['ok'=>true,'multiplier'=>$m,'payout'=>$payout,'balance'=>$_SESSION['balance']]);
}

state();