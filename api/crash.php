<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if(!isset($_SESSION['balance'])) $_SESSION['balance']=1000.00;

function out($data){
  echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_PRESERVE_ZERO_FRACTION);
  exit;
}
function now(){return microtime(true);}
function balance_save($v){
  $_SESSION['balance']=round((float)$v,2);
  if(isset($_SESSION['user_id'])){
    try{
      require __DIR__.'/../config/database.php';
      $s=$pdo->prepare('UPDATE users SET balance=? WHERE id=?');
      $s->execute([$_SESSION['balance'],$_SESSION['user_id']]);
    }catch(Throwable $e){}
  }
}
function add_history($game,$amount,$multiplier,$result){
  if(!isset($_SESSION['history']))$_SESSION['history']=[];
  $_SESSION['history'][]=[
    'game'=>$game,
    'amount'=>$amount,
    'multiplier'=>$multiplier,
    'result'=>$result,
    'created_at'=>date('Y-m-d H:i:s')
  ];
  if(count($_SESSION['history'])>50)array_shift($_SESSION['history']);
  if(isset($_SESSION['user_id'])){
    try{
      require __DIR__.'/../config/database.php';
      $s=$pdo->prepare('INSERT INTO game_history(user_id,game,amount,multiplier,result) VALUES(?,?,?,?,?)');
      $s->execute([$_SESSION['user_id'],$game,$amount,$multiplier,$result]);
    }catch(Throwable $e){}
  }
}
function new_cycle($t){
  return [
    'phase'=>'betting',
    'betting_until'=>$t+15,
    'started_at'=>null,
    'crash_at'=>null,
    'crash_value'=>null,
    'bet'=>null,
    'cashed_out'=>false,
    'cashout_multiplier'=>null,
    'crashed_at'=>null,
    'crash_handled'=>false
  ];
}
function ensure_cycle(){
  if(empty($_SESSION['crash_cycle'])) $_SESSION['crash_cycle']=new_cycle(now());
}
function random_crash(){
  /*
   * Crash distribution:
   * many rounds finish around 1.x–3.x,
   * 5x+ happens regularly,
   * very large values such as 50x/127x/200x are rare.
   * Result is always in hundredths, never forced to an integer.
   */
  $u=mt_rand(1,999999)/1000000;
  $m=1/(1-$u);
  $m=min(500.00,$m);
  return max(1.01,round($m,2));
}
function crash_delay($multiplier){
  return max(0.55,log($multiplier)*2.05);
}
function state(){
  ensure_cycle();
  $c=&$_SESSION['crash_cycle'];
  $t=now();

  if($c['phase']==='betting' && $t >= $c['betting_until']){
    $c['phase']='running';
    $c['started_at']=$t;
    $c['crash_value']=random_crash();
    $c['crash_at']=$t+crash_delay($c['crash_value']);
  }

  if($c['phase']==='running'){
    $elapsed=max(0,$t-$c['started_at']);
    $m=round(exp($elapsed/2.05),2);
    if($t >= $c['crash_at'] || $m >= $c['crash_value']){
      $c['phase']='crashed';
      $c['crashed_at']=$t;
      $c['multiplier']=$c['crash_value'];

      if(!$c['crash_handled'] && $c['bet']!==null && !$c['cashed_out']){
        add_history('Crash',$c['bet'],$c['crash_value'],'loss');
      }
      $c['crash_handled']=true;
    }
  }

  if($c['phase']==='crashed' && $t >= ($c['crashed_at']+2.0)){
    $c=new_cycle($t);
  }

  if($c['phase']==='betting'){
    out([
      'ok'=>true,
      'phase'=>'betting',
      'countdown'=>max(0,ceil($c['betting_until']-$t)),
      'bet'=>$c['bet'],
      'cashed_out'=>false,
      'balance'=>$_SESSION['balance'],
      'server_time_ms'=>round($t*1000)
    ]);
  }

  if($c['phase']==='running'){
    $elapsed=max(0,$t-$c['started_at']);
    $m=min($c['crash_value'],round(exp($elapsed/2.05),2));
    out([
      'ok'=>true,
      'phase'=>'running',
      'multiplier'=>$m,
      'bet'=>$c['bet'],
      'cashed_out'=>$c['cashed_out'],
      'cashout_multiplier'=>$c['cashout_multiplier'],
      'crash_value'=>$c['crash_value'],
      'started_at_ms'=>round($c['started_at']*1000),
      'server_time_ms'=>round($t*1000),
      'balance'=>$_SESSION['balance']
    ]);
  }

  out([
    'ok'=>true,
    'phase'=>'crashed',
    'multiplier'=>$c['crash_value'],
    'bet'=>null,
    'cashed_out'=>$c['cashed_out'],
    'cashout_multiplier'=>$c['cashout_multiplier'],
    'balance'=>$_SESSION['balance']
  ]);
}

$input=json_decode(file_get_contents('php://input'),true) ?: $_POST;
$action=$input['action']??'state';

if($action==='state') state();

if($action==='bet'){
  ensure_cycle();
  $c=&$_SESSION['crash_cycle'];
  $t=now();

  if($c['phase']!=='betting') out(['ok'=>false,'message'=>'Приём ставок уже закрыт.','phase'=>$c['phase']]);
  if($c['bet']!==null) out(['ok'=>false,'message'=>'Ставка на этот раунд уже установлена.']);

  $amount=round((float)($input['amount']??0),2);
  if($amount<1) out(['ok'=>false,'message'=>'Минимальная ставка — 1.']);
  if($amount>$_SESSION['balance']) out(['ok'=>false,'message'=>'Недостаточно виртуальных кредитов.']);

  $c['bet']=$amount;
  balance_save($_SESSION['balance']-$amount);

  out([
    'ok'=>true,
    'phase'=>'betting',
    'bet'=>$amount,
    'countdown'=>max(0,ceil($c['betting_until']-$t)),
    'balance'=>$_SESSION['balance']
  ]);
}

if($action==='cashout'){
  ensure_cycle();
  $c=&$_SESSION['crash_cycle'];
  $t=now();

  if($c['phase']!=='running' || $c['bet']===null || $c['cashed_out']){
    out(['ok'=>false,'message'=>'Сейчас нельзя забрать выплату.']);
  }

  $m=min($c['crash_value'],round(exp(max(0,$t-$c['started_at'])/2.05),2));

  if($t >= $c['crash_at'] || $m >= $c['crash_value']){
    $c['phase']='crashed';
    $c['crashed_at']=$t;
    $c['multiplier']=$c['crash_value'];
    if(!$c['crash_handled']){
      add_history('Crash',$c['bet'],$c['crash_value'],'loss');
      $c['crash_handled']=true;
    }
    out([
      'ok'=>false,
      'crashed'=>true,
      'multiplier'=>$c['crash_value'],
      'balance'=>$_SESSION['balance'],
      'message'=>'Краш на '.$c['crash_value'].'x.'
    ]);
  }

  $payout=round($c['bet']*$m,2);
  $original_bet=$c['bet'];
  balance_save($_SESSION['balance']+$payout);
  add_history('Crash',$original_bet,$m,'cashout');

  // Cashout pays immediately, but DOES NOT end the round.
  $c['cashed_out']=true;
  $c['cashout_multiplier']=$m;
  $c['bet']=null;

  out([
    'ok'=>true,
    'phase'=>'running',
    'multiplier'=>$m,
    'cashout_multiplier'=>$m,
    'payout'=>$payout,
    'cashed_out'=>true,
    'crash_value'=>$c['crash_value'],
    'started_at_ms'=>round($c['started_at']*1000),
    'server_time_ms'=>round($t*1000),
    'balance'=>$_SESSION['balance']
  ]);
}

state();
