<?php
  session_start();
  require('dbconnect.php');
  require('other.php');

  if (isset($_SESSION['user_id']) && $_SESSION['time'] + 3600 > time()) {
    // ログインしている
    $_SESSION['time'] = time();
    if ($_POST['sw']=='add') {
      addBooking($db);
    } elseif ($_POST['sw']=='del') {
      dellBooking($db);
      echo sendmail('','','','','','');
    } elseif ($_POST['sw']=='info') {
      getLessonInfo($db);
    }
  } else {
    // ログインしていない
   header('Location: login.php');
  }
  $db=null;
  exit();


// echo htmlspecialchars($user['name'], ENT_QUOTES, "utf-8");

function addBooking($db) {
  $stmt = $db->prepare('insert into booking (user_id,calendar_id) values (? , ?)');
  $stmt->execute(array($_POST['user_id'],$_POST['calendar_id'] ));
  // $stmt = $db->prepare('CALL doBooking(? , ?)');
  // $stmt->bindParam(1, $_POST['user_id'], PDO::PARAM_INT);
  // $stmt->bindParam(2, $_POST['calendar_id'], PDO::PARAM_INT);
  // $stmt->execute();
  return;
}

function dellBooking($db) {
  $stmt = $db->prepare('delete from booking where id=?');
  $stmt->execute(array($_POST['booking_id']));
  return;
}




?>
