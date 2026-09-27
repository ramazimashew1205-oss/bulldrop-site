<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
if(!isset($_SESSION['balance']))$_SESSION['balance']=1000.00;
echo json_encode(['ok'=>true,'balance'=>round((float)$_SESSION['balance'],2)],JSON_UNESCAPED_UNICODE|JSON_PRESERVE_ZERO_FRACTION);