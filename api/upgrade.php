<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
if(!isset($_SESSION['balance']))$_SESSION['balance']=1000.00;
$input=json_decode(file_get_contents('php://input'),true) ?: $_POST;
$amount=round((float)($input['amount']??0),2);
if($amount<1){echo json_encode(['ok'=>false,'message'=>'Минимальная ставка — 1.'],JSON_UNESCAPED_UNICODE);exit;}
if($amount>$_SESSION['balance']){echo json_encode(['ok'=>false,'message'=>'Недостаточно виртуальных кредитов.'],JSON_UNESCAPED_UNICODE);exit;}
$success=mt_rand(1,100)<=25;
$payout=$success?round($amount*2.5,2):0;
$_SESSION['balance']=round($_SESSION['balance']-$amount+$payout,2);
$result=$success?'win':'loss';
$_SESSION['history'][]=['game'=>'Upgrade','amount'=>$amount,'multiplier'=>$success?2.5:0,'result'=>$result,'created_at'=>date('Y-m-d H:i:s')];
if(count($_SESSION['history'])>50)array_shift($_SESSION['history']);
if(isset($_SESSION['user_id'])){
  try{
    require __DIR__.'/../config/database.php';
    $s=$pdo->prepare('UPDATE users SET balance=? WHERE id=?');$s->execute([$_SESSION['balance'],$_SESSION['user_id']]);
    $s=$pdo->prepare('INSERT INTO game_history(user_id,game,amount,multiplier,result) VALUES(?,?,?,?,?)');$s->execute([$_SESSION['user_id'],'Upgrade',$amount,$success?2.5:0,$result]);
  }catch(Throwable $e){}
}
echo json_encode(['ok'=>true,'success'=>$success,'amount'=>$amount,'payout'=>$payout,'balance'=>$_SESSION['balance'],'message'=>$success?'Успех!':'Неудача.'],JSON_UNESCAPED_UNICODE|JSON_PRESERVE_ZERO_FRACTION);