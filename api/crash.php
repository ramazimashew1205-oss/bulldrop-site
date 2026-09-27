<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
if(!isset($_SESSION['balance']))$_SESSION['balance']=1000.00;
$input=json_decode(file_get_contents('php://input'),true) ?: $_POST;
$action=$input['action'] ?? 'start';

function json_out($data){echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_PRESERVE_ZERO_FRACTION);exit;}
function history_add($game,$amount,$multiplier,$result){
  $_SESSION['history'][]=['game'=>$game,'amount'=>$amount,'multiplier'=>$multiplier,'result'=>$result,'created_at'=>date('Y-m-d H:i:s')];
  if(count($_SESSION['history'])>50)array_shift($_SESSION['history']);
  if(isset($_SESSION['user_id'])){
    try{
      require __DIR__.'/../config/database.php';
      $s=$pdo->prepare('INSERT INTO game_history(user_id,game,amount,multiplier,result) VALUES(?,?,?,?,?)');
      $s->execute([$_SESSION['user_id'],$game,$amount,$multiplier,$result]);
    }catch(Throwable $e){}
  }
}
function save_balance($balance){
  $_SESSION['balance']=round($balance,2);
  if(isset($_SESSION['user_id'])){
    try{
      require __DIR__.'/../config/database.php';
      $s=$pdo->prepare('UPDATE users SET balance=? WHERE id=?');
      $s->execute([$_SESSION['balance'],$_SESSION['user_id']]);
    }catch(Throwable $e){}
  }
}
function current_multiplier($started){
  $t=max(0,microtime(true)-(float)$started);
  return round(1 + $t*0.72 + $t*$t*0.035,2);
}

if($action==='start'){
  if(!empty($_SESSION['crash_round']))json_out(['ok'=>false,'message'=>'Сначала завершите текущий раунд.']);
  $amount=round((float)($input['amount']??0),2);
  if($amount<1)json_out(['ok'=>false,'message'=>'Минимальная ставка — 1.']);
  if($amount>$_SESSION['balance'])json_out(['ok'=>false,'message'=>'Недостаточно виртуальных кредитов.']);
  $crashAt=round(1.15+pow(mt_rand()/mt_getrandmax(),2)*5.35,2);
  save_balance($_SESSION['balance']-$amount);
  $_SESSION['crash_round']=['amount'=>$amount,'crash_at'=>$crashAt,'started_at'=>microtime(true)];
  json_out(['ok'=>true,'amount'=>$amount,'crash_at'=>$crashAt,'balance'=>$_SESSION['balance']]);
}

if($action==='crash'){
  $round=$_SESSION['crash_round']??null;
  if(!$round)json_out(['ok'=>false,'message'=>'Активного раунда нет.','already_resolved'=>true,'balance'=>$_SESSION['balance']]);
  $m=current_multiplier($round['started_at']);
  if($m < $round['crash_at'])json_out(['ok'=>false,'message'=>'Раунд ещё продолжается.','still_running'=>true]);
  $crash=$round['crash_at'];
  unset($_SESSION['crash_round']);
  history_add('Crash',$round['amount'],$crash,'loss');
  json_out(['ok'=>true,'crashed'=>true,'multiplier'=>$crash,'balance'=>$_SESSION['balance'],'message'=>'Краш. Ставка потеряна.']);
}

if($action==='cashout'){
  $round=$_SESSION['crash_round']??null;
  if(!$round)json_out(['ok'=>false,'message'=>'Активного раунда нет.']);
  $m=current_multiplier($round['started_at']);
  if($m>=$round['crash_at']){
    $crash=$round['crash_at'];unset($_SESSION['crash_round']);
    history_add('Crash',$round['amount'],$crash,'loss');
    json_out(['ok'=>false,'message'=>'Слишком поздно — краш на '.$crash.'x.','crashed'=>true,'balance'=>$_SESSION['balance']]);
  }
  $payout=round($round['amount']*$m,2);
  save_balance($_SESSION['balance']+$payout);
  unset($_SESSION['crash_round']);
  history_add('Crash',$round['amount'],$m,'cashout');
  json_out(['ok'=>true,'multiplier'=>$m,'payout'=>$payout,'balance'=>$_SESSION['balance']]);
}
json_out(['ok'=>false,'message'=>'Неизвестное действие.']);