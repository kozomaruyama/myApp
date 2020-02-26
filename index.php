<?php

  session_start();
  require('dbconnect.php');

  if (isset($_SESSION['user_id']) && $_SESSION['time'] + 3600 > time()) {
    // ログインしている
    $_SESSION['time'] = time();
  } else {
      // ログインしていない
     header('Location: login.php');
     exit();
  }

  $weekdayText = array ("月","火","水","木","金","土","日");

?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>ホームページで使うカレンダー（祝日つき）（PHPプログラムのベース）</title>
    <meta charset="utf-8">
    <meta name="description" content="ホームページで使うカレンダー（祝日つき）（PHPプログラムのベース）">
    <meta name="author" content="shimamura">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="./css/style.css">
    <script type="text/javascript" src="./js/script.js"></script>

    <!-- jQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <!-- jQuery UI -->
    <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>


    <link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css">

    <link href="https://use.fontawesome.com/releases/v5.0.8/css/all.css" rel="stylesheet">
<!--
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css">
-->
</head>
<body>

<?php
//-------------------------------------------------------------------------------------------------
//
//-------------------------------------------------------------------------------------------------
$bookings = array();
$records=$db->prepare("select a.id,a.day from calendar as a, booking as b where a.id=b.calendar_id and b.user_id=?");
// $bookings->execute(array($_SESSION['user_id']));
$records->execute(array($_SESSION['user_id']));
while ($record = $records->fetch()) {
  $bookings = $bookings + array($record["day"]=>$record);
}
//-------------------------------------------------------------------------------------------------
//
//-------------------------------------------------------------------------------------------------
$courseData = array();
$records=$db->prepare("SELECT * from course where id=?");
$records->execute(array($_SESSION['course_id']));
$courseData = $records->fetch();
//-------------------------------------------------------------------------------------------------
//
//-------------------------------------------------------------------------------------------------
function getCalendarData($d, $day, $course)
{
  // カレンダーデータ
  $c= array();
  // １件のカレンダーデータ
  $calenderData = array();
  $records=$d->prepare("SELECT a.*,concat(date_format(a.day,'%c月%e日'),'(',(case date_format(a.day,'%w') when 1 then '月' when 2 then '火' when 3 then '水' when 4 then '木' when 5 then '金' when 6 then '金' when 7 then '日' else '?' end),')' ) as dayF,b.count from calendar a left outer join (select calendar_id, count(*) as count from booking group by calendar_id) b on a.id=b.calendar_id where a.status>0 and a.day=? and a.course_id=? order by a.time ");
  // $records=$d->prepare("SELECT * from calendar where day=? and course_id=? order by time ");
  $records->execute(array($day,$course));
  while ($record = $records->fetch()) {
      array_push($c, $record);
  }
  return $c;
}
//-------------------------------------------------------------------------------------------------
//
//-------------------------------------------------------------------------------------------------
function getBookingData($d)
{
  // カレンダーデータ
  $c= array();
  // １件のカレンダーデータ
  $records=$d->prepare("SELECT * from booking where user_id=?");
  $records->execute(array($_SESSION['user_id']));
  while ($record = $records->fetch()) {
    $c = $c + array($record["calendar_id"]=>$record);
      //array_push($c, array($record["calendar_id"]=>$record));
  }
//  echo $day .",". $class ."," . count($c);
  //echo "a=".$c[0][0];
  return $c;
}

//-------------------------------------------------------------------------------------------------
//
//-------------------------------------------------------------------------------------------------
function getBookingInfo($d)
{

  $records = $d->prepare("SELECT * from users where id=?");
  $records->execute(array($_SESSION['user_id']));
  $user = $records->fetch();

  $records = $d->prepare("SELECT * from course where id=?");
  $records->execute(array($user['course_id']));
  $course = $records->fetch();

  $records = $d->prepare('SELECT min(b.day) as day, min(b.time) as time from booking as a, calendar as b where a.calendar_id=b.id and a.status=1 and b.course_id=? and a.user_id=?');
  $records->execute(array($course['id'],$_SESSION['user_id']));
  $booking1 = $records->fetch();
  $records = $d->prepare('SELECT Count(*) as useTimes from booking as a, calendar as b where a.calendar_id=b.id and a.status=-1 and b.course_id=? and a.user_id=?');
  $records->execute(array($course['id'],$_SESSION['user_id']));
  $booking2 = $records->fetch();

  $returnText = array("nextDay"=>$booking1["day"],"nextTime"=>$booking1["time"], "useTimes"=>$booking2["useTimes"],"allTimes"=>$course["times"]);

  return $returnText;
}

  //Control 日付作成処理__construct
  // １ヶ月分の日付を格納
  $days = array();
  // １年分の日付を格納
  $cals = array();
  //今月の最終日を格納
  $lastday = date("Y/n/t");

  //祝日設定処理
  $conf_horiday = true;
  if ($conf_horiday) {
    $horidays = array();
    $horiname = array();
    // 内閣府ホームページの"国民の祝日について"よりデータを取得する
    $res = file_get_contents('https://www8.cao.go.jp/chosei/shukujitsu/syukujitsu.csv');
    $res = mb_convert_encoding($res, "UTF-8", "SJIS");
    $pieces = explode("\r\n", $res);
    $dummy = array_shift($pieces);
    $dummy = array_pop($pieces);

    foreach ($pieces as $key => $value) {
      $temp = explode(',', $value);
      $horidays[] = $temp[0];  //日付を設定
      $horiname[] = $temp[1];  //祝日名を設定
    }
  }

  for ($i = 0; $i <= 365; $i++) {
    //日付を１日ずつ増や2していく mktime(hour, minute, second, month, day, year)
    $day = (string) date('Y/n/j', mktime(0, 0, 0, date('m'), date('1') + $i, date('Y')));

    //日付を格納する
    $days[$i]['day'] = $day;
    //祝日を設定する
    if ($conf_horiday) {
      $ind = array_search($day,$horidays);
      if ($ind !== false){
        $days[$i]['hori'] = $horiname[$ind];
      } else {
        $days[$i]['hori'] = '';
      }
    } else {
      $days[$i]['hori'] = '';
    }
    //その他必要な処理をここに追加する
    //$days[$i]['hoge'] = '';
    if ($day == $lastday){
      //月末日の処理
      //次の月末日で更新する
      $target_day = date("Y/n/1", strtotime($lastday));
      $lastday = date("Y/n/t",strtotime($target_day . "+1 month"));
      //月ごとに格納する
      $cals[] = $days;
      $days = array();
    }
  }
?>

<header>
  <div class=header-container>
    <div class="header-info">
      <?php
      $bookingInfo= getBookingInfo($db);
      // コース名を表示する
      echo "<h2>".$courseData['name']." コース</h2>";
      if ($bookingInfo["nextDay"]=="") {
        echo "<h5>次回：予約なし</h5>";
      } else {
        $nextTime = new DateTime($bookingInfo["nextDay"]);
        echo "<h5>次回：".$nextTime->format('n').'月'.$nextTime->format('d')."日(".$weekdayText[$nextTime->format('w')-1].") ".$bookingInfo["nextTime"]."～</h5>";
      }
      echo "<h5>".$bookingInfo["useTimes"]."回/全".$bookingInfo["allTimes"]."回</h5>";
      ?>
    </div>
    <div class="header-icon">
      <!-- <a href="account.php"><img alt="利用者情報へのリンク" src="./img/user.png" width="50" height="50"></img></a> -->
      <img class="open account-icon" data-target='account' alt="利用者情報へのリンク" src="./img/user.png"></img>
      <!-- <button class='open' data-target='account' ></button> -->
      <?php
      // ログインユーザー名を表示する
      echo "<h4>".$_SESSION["user_name"]."</h4>";
      ?>
    </div>
  </div>
</header>

  <div class="container">

<?php

  //View 表示処理
  //$weeklavel = array("日", "月", "火", "水", "木", "金", "土");
  //echo $weeklavel[$ww];
  foreach ($cals as $key => $mm) {
    foreach ($mm as $key => $dd) {
      //月を表示する
      $dayD = new DateTime($dd['day']);
      echo '<h4>'.$dayD->format('Y').'年'.$dayD->format('n').'月</h3>';
      break;
    }
?>
    <div class="table-responsive">
      <!-- table class="table table-bordered" style="table-layout:fixed;" -->
      <table class="table table-bordered">
        <thead>
          <tr>
            <th class="sun"><span class="text-danger">日</span></th>
            <th class="week">月</th>
            <th class="week">火</th>
            <th class="week">水</th>
            <th class="week">木</th>
            <th class="week">金</th>
            <th class="sat"><span class="text-info">土</span></th>
          </tr>
        </thead>
        <tbody>
          <tr>
<?php

    $infoText = "";
    $boxStyle = "";

    $j = 0;
    $first = true;

    $bookings = array();
    $bookings = getBookingData($db);

    foreach ($mm as $key => $dd) {
      $dayD = new DateTime($dd['day']);
      $ww = $dayD->format('w');

//      $ymd = '\''.$dayD->format('Y').'/'.$dayD->format('n').'/'.$dayD->format('j').'\'';
      if ($first){
        //月の初めの開始位置を設定する
        for ($j = 0; $j < $ww; $j++) {
          //$jはこの後も使用する
          echo '<td class="none" ></td>';
        }
        $first = false;
      }

      //
      $cellStatusClass = "";
      $dayStatus = 0;

      $calendars = array();
      $calendars = getCalendarData($db,$dayD->format('Y-m-d'),$_SESSION['course_id']);


      //
      if ($dd['hori']){
        //祝日
        // echo '<td class="hol"><span class="text-danger">'.$dayD->format('j')."(".$dd['hori'].')</span>';
        echo '<td class="hol"><span class="text-danger">'.$dayD->format('j').'</span>';
      } elseif($j == 0) {
        //日曜日
        echo '<td class="sun"><span class="text-danger">'.$dayD->format('j').'</span>';
      } elseif($j == 6) {
        //土曜日
        echo '<td class="sat"><span class="text-info">'.$dayD->format('j').'</span>';
      } else {
        //その他平日
        echo '<td class="week" ><div>'.$dayD->format('j').'</div>';
      }

      $infoText="";

      foreach ($calendars as $value) {
        if ($value["status"] == 99) {
          $isOpen = "close";
        } else {
          $isOpen = "open";
        }
        if (array_key_exists($value["id"], $bookings)) {
          // echo "<button class='open entry' onclick='js_href(".$bookings[$value["id"]][0].",".$value["id"].",".$_SESSION['user_id'].")'>".$value["sname"]."</button>";
          echo "<button class='entry ".$isOpen."' data-target='del' data-status'".$value["status"]." data-day='".$value["dayF"]." ".$value["time"]."～' data-user_id='".$_SESSION['user_id']."' data-calendar_id='0' data-booking_id='".$bookings[$value["id"]][0]."'>予".$value["time"]."</button>";
        } else if ($value["count"]>3) {
          echo "<button class='purple close' >満</button>";
        } else {
          // echo "<button class='open navy' onclick='js_href(0,".$bookings[$value["id"]][2].",".$_SESSION['user_id'].")'>".$value["sname"]."</button>";
          $zan=4-$value["count"];
          echo "<button class='navy ".$isOpen."' data-target='addNew' data-status'".$value["status"]."' data-day='".$value["dayF"]." ".$value["time"]."～' data-user_id='".$_SESSION['user_id']."' data-calendar_id='".$value["id"]."' data-booking_id='0'>空".$value["time"]."</button>";
        }

      }

      echo '</td>';

      $j = $j + 1;
      if ($j >= 7){
        //土曜日で折り返す
        echo '</tr><tr>';
        $j = 0;
      }
    }  //月ごとの foreach ここまで
?>
          </tr>
        </tbody>
      </table>
    </div><!-- table-responsive end -->
<?php
  }  //１年分の foreach ここまで
  echo "<h4>※空：予約可能です。</h4>";
  echo "<h4>※予：既に予約されています。</h4>";
  echo "<h4>※満：満員で予約不可です。</h4>";
?>
  </div><!-- container end -->


  <!-- モーダルダイヤログの処理 -->
  <!-- 予約 -->
  <div id="addNew" class="modal js-modal">
    <div class="modal__bg js-modal-close"></div>
    <div class="modal__content">
      <p id='dialog_add_day'></p>
      <p>予約しますか？</p>
      <div style="display:inline-flex">
      <input type='button' button id='js-modal-enter' class='navy' value="はい">
      <input type='button' button id='js-modal-Cancel' class='navy' value="いいえ">
      </div>
    </div><!--modal__inner-->
  </div><!--modal-->
  <!-- 予約キャンセル -->
  <div id="del" class="modal js-modal">
    <div class="modal__bg js-modal-close"></div>
    <div class="modal__content">
      <p id='dialog_dell_day'></p>
      <p>予約を<span style="color:red">取消</span>しますか？</p>
      <div style="display:inline-flex">
      <input type='button' button id='js-modal-del' class='navy' value="はい">
      <input type='button' button id='js-modal-Cancel' class='navy' value="いいえ">
      </div>
    </div>
    </div><!--modal__inner-->
  </div><!--modal-->
  <!-- アカウント -->
  <div id="account" class="modal js-modal">
    <div class="modal__bg js-modal-close"></div>
    <div class="modal__content">
      <p id='dialog_add_day'></p>
      <?php
        echo "<H1>パスワードの変更</H1>";
        // echo "<H2>（".$_SESSION["email"]."）</H2>";
        echo "<p><span>現パスワード</span><input type='password' id='pass'></p>";
        echo "<p><span>新パスワード</span><input type='password' id='newPass1'></p>";
        echo "<p><span>新パスワード（確認）</span><input type='password' id='newPass2'></p>";
      ?>
      <div style="display:inline-flex">
      <input type='button' button id='js-modal-account' class='navy' value="はい">
      <input type='button' button id='js-modal-Cancel' class='navy' value="いいえ">
      </div>
    </div><!--modal__inner-->
  </div><!--modal-->


</body>

<script>
<!-- モーダルウインドウの表示 -->

$calendar_id=0;
$booking_id=0;

if ("<?php echo $_SESSION['password']; ?>" == "") {
  window.location.href = 'login.php';
} else {
  $pass="<?php echo $_SESSION['password']; ?>";
}

$(function(){
	// $('.js-modal-open').each(function(){
	$('.open').each(function(){
		$(this).on('click',function(){
      $calendar_id = $(this).data('calendar_id');
      $booking_id = $(this).data('booking_id');
      document.getElementById("dialog_add_day").textContent=$(this).data('day');
      document.getElementById("dialog_dell_day").textContent=$(this).data('day');
			var target = $(this).data('target');
			var modal = document.getElementById(target);
			$(modal).fadeIn();
			return false;
		});
	});
	$('#js-modal-enter').on('click',function(){
    var data = {"sw": 'add', "user_id": "<?php echo $_SESSION['user_id']; ?>", "calendar_id": $calendar_id }
    $.ajax({
            type: 'post',
            url: "http://localhost/wordpress/myapp/db_booking.php",
            data: data,
            success: function(result){
              window.location.reload();
            }
    });
		$('.js-modal').fadeOut();
		return false;
	});
  $('#js-modal-del').on('click',function(){
    var data = {"sw": 'del', "booking_id": $booking_id }
    $.ajax({
            type: 'post',
            url: "http://localhost/wordpress/myapp/db_booking.php",
            data: data,
            success: function(result){
              window.location.reload();
            }
    });
    $('.js-modal').fadeOut();
    return false;
  });
  $('#js-modal-account').on('click',function(){
    if ($('#pass').val()==$pass) {
      if ($('#newPass1').val()==$('#newPass2').val()) {
        if ($('#newPass1').val()!="") {
          var data = {"sw": 'upd', "password": $('#newPass1').val() }
          $.ajax({
            type: 'post',
            url: "http://localhost/wordpress/myapp/db_account.php",
            data: data,
            success: function(result){
              window.location.reload();
            }
          });
          $('.js-modal').fadeOut();
          return false;
        } else {
          alert("パスワードは空にはできません。");
        }
      } else {
        alert("新パスワードと新パスワード（確認用）が一致しません。");
      }
    } else {
      alert("現パスワードが違っています。");
    }
  });
  $('#js-modal-Cancel,#js-modal-dellCancel').on('click',function(){
    $('.js-modal').fadeOut();
    return false;
  });

});
</script>

</body>
</html>
