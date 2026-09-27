<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require __DIR__.'/../config/database.php';
if(!isset($_SESSION['user_id'])){http_response_code(401);echo json_encode(['ok'=>false,'message'=>'Авторизуйтесь.'],JSON_UNESCAPED_UNICODE);exit;}
$id=(int)$_SESSION['user_id'];
try{
 $s=$pdo->prepare('SELECT balance,is_blocked FROM users WHERE id=? LIMIT 1');$s->execute([$id]);$u=$s->fetch();
 if(!$u||(int)$u['is_blocked']===1){echo json_encode(['ok'=>false,'message'=>'Доступ запрещён.'],JSON_UNESCAPED_UNICODE);exit;}
 $input=json_decode(file_get_contents('php://input'),true)?:$_POST;
 $amount=round((float)($input['amount']??0),2);$target=round((float)($input['target_price']??0),2);
 if($amount<1){echo json_encode(['ok'=>false,'message'=>'Минимальная стоимость — 1 ₽.'],JSON_UNESCAPED_UNICODE);exit;}
 if($target<=$amount){$target=round($amount+0.01,2);}
 $balance=(float)$u['balance'];if($amount>$balance){echo json_encode(['ok'=>false,'message'=>'Недостаточно виртуальных кредитов.'],JSON_UNESCAPED_UNICODE);exit;}
 $chance=min(95,max(0.01,($amount/$target)*100));
 $roll=random_int(1,100000)/1000;
 $success=$roll<=$chance;
 $payout=$success?$target:0;
 $newBalance=round($balance-$amount+$payout,2);
 $pdo->beginTransaction();
 $s=$pdo->prepare('UPDATE users SET balance=? WHERE id=?');$s->execute([$newBalance,$id]);
 $result=$success?'win':'loss';
 $s=$pdo->prepare('INSERT INTO game_history(user_id,game,amount,multiplier,result) VALUES(?,?,?,?,?)');$s->execute([$id,'Upgrade',$amount,round($target/$amount,2),$result]);
 $pdo->commit();
 $_SESSION['balance']=$newBalance;
 echo json_encode(['ok'=>true,'success'=>$success,'chance'=>round($chance,2),'roll'=>$roll,'amount'=>$amount,'target_price'=>$target,'payout'=>$payout,'balance'=>$newBalance],JSON_UNESCAPED_UNICODE|JSON_PRESERVE_ZERO_FRACTION);
}catch(Throwable $e){
 if(isset($pdo)&&$pdo->inTransaction())$pdo->rollBack();
 http_response_code(500);echo json_encode(['ok'=>false,'message'=>'Ошибка сервера.'],JSON_UNESCAPED_UNICODE);
}