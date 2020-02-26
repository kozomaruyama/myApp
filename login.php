<?php

require('dbconnect.php');

session_start();

// クッキーに保存された情報を読み込み自動的にログインする
if (!empty($_COOKIE['code']) != '') {
  $_POST['code'] = $_COOKIE['code'];
  $_POST['password'] = $_COOKIE['password'];
  $_POST['save'] = 'on';
}

if (!empty($_POST)) {
  // ログインの処理
  if ($_POST['code'] != '' && $_POST['password'] != '') {
    $login = $db->prepare('select * from users where code=? and password=?');
//    $login->execute(array($_POST['email'],sha1($_POST['password'])));
    $login->execute(array($_POST['code'],$_POST['password']));
    $user = $login->fetch();
    if ($user) {
      // ログイン成功
      $_SESSION['user_id'] = $user['id'];
      $_SESSION['user_name'] = $user['name'];
      $_SESSION['user_code'] = $user['code'];
      $_SESSION['email'] = $user['email'];
      $_SESSION['course_id'] = $user['course_id'];
      $_SESSION['password'] = $user['password'];
      $_SESSION['time'] = time();
      // ログイン情報をクッキーに保存する
      if (!empty($_POST['save']) == 'on') {
        setcookie('code', $_POST['code'], time()+60*60*24*14);
        setcookie('password', $_POST['password'], time()+60*60*24*14);
      }
      // index.phpへリダイレクトさせる
      header('Location: index.php') ;
      // 処理を終える
      // exit();
    } else {
      // ログインできなかった場合は[failed]エラーとする
      $error['login'] = 'failed';
    }
  } else {
    // [email]と[password]が入力されていない場合は[blank]エラーとする
    $error['login'] = 'blank';
  }
}

?>

<!DOCTYPE html>
<html rang="ja">
<head>
  <meta charset="utf-8">
  <meta title="認証">
  <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
  <div id='lead'>
    <p>メールアドレスとパスワードを入力しログインしてください</p>
  </div>
  <form action='' method='post'>
    <dl>
      <dt>メールアドレス</dt>
      <dd>
        <input type="text" name="code" size="35" maxlength="255" value="<?php echo htmlspecialchars(!empty($_POST['email']), ENT_QUOTES); ?>"/>
        <?php if (isset($error['login']) && $error['login'] == 'blank'): ?>
          <p class="error">* メールアドレスとパスワードを入力下さい</p>
        <?php endif; ?>
        <?php if (isset($error['login']) && $error['login'] == 'failed'): ?>
          <p class="error">* ログインに失敗しました。入力したメールアドレスとパスワードをご確認下さい。</p>
        <?php endif; ?>
      </dd>
      <dt>パスワード</dt>
      <dd>
        <input type='password' name='password' size='35' maxlength='255' value="<?php echo htmlspecialchars(!empty($_POST['password']), ENT_QUOTES); ?>"/>
      </dd>
      <dt>ログイン情報の記録</dt>
      <dd>
        <input id='save' type='checkbox' name='save' value='on'><label for='save'>次回からは自動でログインする</label>
      </dd>
    </dl>
    <div><input type="submit" value="ログインする" /></div>
  </form>
</body>
