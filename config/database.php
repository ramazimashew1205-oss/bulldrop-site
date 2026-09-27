<?php
$host='localhost'; $db='drop_demo'; $user='root'; $pass='';
$dsn="mysql:host=$host;dbname=$db;charset=utf8mb4";
$options=[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false];
try{$pdo=new PDO($dsn,$user,$pass,$options);}catch(PDOException $e){http_response_code(500);exit('Database connection error.');}