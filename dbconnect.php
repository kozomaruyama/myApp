<?php

// 定数の定義
define('DB_HOST', 'localhost');
define('DB_USER', 'dbuser');
define('DB_PASS', 'kozo1234');
define('DB_NAME', 'school');

try {
  // MySQLへの接続
  $db = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS);
} catch (PDOException $e){
  echo 'DB接続エラー: ' , $e->getmessage();
}

?>
