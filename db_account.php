<?php
  session_start();
  require('dbconnect.php');
  if (isset($_SESSION['user_id']) && $_SESSION['time'] + 3600 > time()) {
    // ログインしている
    $_SESSION['time'] = time();
    $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
    // $stmt->execute(array($_POST['password'],$_POST['user_id'] ));
    $stmt->execute(array($_POST['password'],$_SESSION['user_id']  ));
    // $stmt = $db->prepare("UPDATE users SET password = '111' WHERE id = 3");
    // $stmt->execute();
    $_SESSION['password']=$_POST['password'];
  } else {
    // ログインしていない
   header('Location: login.php');
  }
  $db=null;
  exit();


?>
