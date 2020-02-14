<?php

// 共通変数・関数ファイルを読込み
require('function.php');

debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debug('「　パスワード変更ページ　');
debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debugLogStart();

// サインイン認証
require('auth.php');

//================================
// パスワード変更画面処理
//================================
// DBからユーザーデータを取得
$userData = getUser($_SESSION['user_id']);
debug('取得したユーザー情報：'.print_r($userData,true));

// POST送信された場合
if(!empty($_POST)){
  debug('POST送信があります。');
// 緊急時、ユーザーのパスワードデバッグ用（本来は、ログレベル・関数などでオンオフを操作できるように）
// debug('POST情報：'.print_r($_POST,true));

  // POST送信された情報を代入。キーをname属性に設定
  $pass_old = $_POST['pass_old'];
  $pass_new = $_POST['pass_new'];
  $pass_new_re = $_POST['pass_new_re'];

  // 未入力チェック（変数にて送信内容をチェック。キーにてエラーメッセージを送信）
  validRequired($pass_old, 'pass_old');
  validRequired($pass_new, 'pass_new');
  validRequired($pass_new_re, 'pass_new_re');

  // 未入力チェックOKであれば、後続のバリデーション
  if(empty($err_msg)){
    debug('未入力チェックOK。古いパスワード、新しいパスワードの半角英数・最大・最小文字数チェックへ');

    // 古いパスワードのチェック
    validPass($pass_old, 'pass_old');
    // 新しいパスワードのチェック
    validPass($pass_new, 'pass_new');

    // 入力された古いパスワードと、DBに登録してあるパスワードを照合
    if(!password_verify($pass_old, $userData['password'])){
      $err_msg['pass_old'] = MSG12; // エラーメッセージ（define('MSG12', '古いパスワードが違います');）
    }

    // 入力された古いパスワードと、新しいパスワードが同じ場合、エラーメッセージを表示
    if($pass_old === $pass_new){
      $err_msg['pass_new'] = MSG13; //エラーメッセージ（define('MSG13', '古いパスワードと同じです');）
    }

    // 新しいパスワードとパスワード再入力が合っているかチェック
    validMatch($pass_new, $pass_new_re, 'pass_new_re');

    if(empty($err_msg)){
      debug('バリデーションOK。');

      try {
        $dbh = dbConnect();
        // usersテーブルの、passwordカラムを更新
        $sql = 'UPDATE users SET password = :pass WHERE id = :id';
        // プレースホルダに値を割り当て
        $data = array(':id' => $_SESSION['user_id'], ':pass' => password_hash($pass_new, PASSWORD_DEFAULT));
        // クエリ実行
        $stmt = queryPost($dbh, $sql, $data);

        // クエリ成功の場合
        if($stmt){
          $_SESSION['msg_success'] = SUC01; // 成功メッセージ（define('SUC01', 'パスワードを変更しました');）

          // ユーザーに、メールを送信
          $username = ($userData['username']) ? $userData['username'] : '（お名前は、まだ入力されていません。）';
          $from = 'idoharu.com@gmail.com';
          $to = $userData['email'];
          $subject = 'パスワード変更のお知らせ｜ウチのイッヌ';
          $comment = <<<EOT
{$username}　さん

いつもウチのイッヌをお使いいただき、
まことにありがとうございます。

パスワードが変更されましたので、お知らせいたします。

ご不明な点がありましたら、「ウチのイッヌ」カスタマーセンター
までお問い合わせください。

////////////////////////////////////////////////////////////////////////////////
「ウチのイッヌ」カスタマーセンター
URL https://idoharu.com/index.php/contact/
E-mail idoharu.com@gmail.com
////////////////////////////////////////////////////////////////////////////////
EOT;
          sendMail($from, $to, $subject, $comment);

          // マイページへ遷移
          header("Location:mypage.php");
          exit;
        }

      } catch (Exception $e) {
        error_log('エラー発生:'.$e->getMessage());
        $err_msg['common'] = MSG07; // エラーメッセージ（define('MSG07','エラーが発生しました。しばらく経ってからやり直してください。');）
      }
    }
  }
}
?>

<!-- head -->
<?php
  $siteTitle = 'パスワード変更';
  require('head.php');
?>

  <body class="page-passEdit page-2colum page-signined">

    <style>
      .form{
        margin-top: 50px;
      }
    </style>

    <!-- ヘッダー -->
    <?php
      require('header.php');
    ?>

    <!-- メインコンテンツ -->
    <div id="contents" class="site-width">
      <h1 class="page-title">パスワード変更</h1>

      <!-- Main -->
      <section id="main" >

        <div class="form-container">

          <form action="" method="post" class="form">
            <div class="area-msg">
              <?php
                // エラーメッセージ
                echo getErrMsg('common');
              ?>
            </div>
            <!-- 古いパスワード入力フォーム -->
            <label class="<?php if(!empty($err_msg['pass_old'])) echo 'err'; ?>">
              古いパスワード
              <input type="password" name="pass_old" value="<?php echo getFormData('pass_old'); ?>">
            </label>
            <div class="area-msg">
              <?php
                // エラーメッセージ
                echo getErrMsg('pass_old');
              ?>
            </div>
            <!-- 新しいパスワード入力フォーム -->
            <label class="<?php if(!empty($err_msg['pass_new'])) echo 'err'; ?>">
              新しいパスワード
              <input type="password" name="pass_new" value="<?php echo getFormData('pass_new'); ?>">
            </label>
            <div class="area-msg">
              <?php
                // エラーメッセージ
                echo getErrMsg('pass_new');
              ?>
            </div>
            <!-- 新しいパスワード再入力フォーム -->
            <label class="<?php if(!empty($err_msg['pass_new_re'])) echo 'err'; ?>">
              新しいパスワード（再入力）
              <input type="password" name="pass_new_re" value="<?php echo getFormData('pass_new_re'); ?>">
            </label>
            <div class="area-msg">
              <?php
                // エラーメッセージ
                echo getErrMsg('pass_new_re');
              ?>
            </div>
            <!-- 送信ボタン -->
            <div class="btn-container">
              <input type="submit" class="btn btn-mid" value="変更！">
            </div>
          </form>

        </div>

      </section>

      <!-- サイドバー -->
      <?php
        require('sidebar_mypage.php');
      ?>
    </div>

    <!-- footer -->
    <?php
      require('footer.php');
    ?>
