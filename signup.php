<?php

// 共通変数・関数ファイルを読込み
require('function.php');

debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debug('「　ユーザー登録ページ　');
debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debugLogStart();

//================================
// サインアップ画面処理
//================================
// POST送信された場合
if(!empty($_POST)){

  // 「POST送信されたユーザー情報」を代入。キーをname属性に設定
  $email = $_POST['email'];
  $pass = $_POST['pass'];
  $pass_re = $_POST['pass_re'];

  // 未入力チェック（変数にて送信内容をチェック。キーにてエラーメッセージを送信）
  validRequired($email, 'email');
  validRequired($pass, 'pass');
  validRequired($pass_re, 'pass_re');

  // 未入力チェックOKであれば、後続のバリデーション
  if(empty($err_msg)){

    // emailの形式チェック
    validEmail($email, 'email');
    // emailの最大文字数チェック
    validMaxLen($email, 'email');
    // email重複チェックはDB接続負荷があるため、形式チェック・最大文字数チェックでエラーが無い場合にチェック
    if(empty($err_msg['email'])){
    // emailの重複チェック
    validEmailDup($email);
    }

    // パスワード半角英数字チェック
    validHalf($pass, 'pass');
    // パスワード最大文字数チェック
    validMaxLen($pass, 'pass');
    // パスワード最小文字数チェック
    validMinLen($pass, 'pass');

    // emailとパスワードのチェックがOKであれば、後続のバリデーション
    if(empty($err_msg)){

      // パスワードとパスワード再入力が一致しているかチェック
      validMatch($pass, $pass_re, 'pass_re');

      // パスワードとパスワード再入力が一致していれば、DB接続
      if(empty($err_msg)){

        try {
          $dbh = dbConnect();
          // usersテーブルに、レコードを挿入
          $sql = 'INSERT INTO users (email,password,signin_time,create_date) VALUES(:email,:pass,:signin_time,:create_date)';
          // プレースホルダに値を割り当て
          $data = array(':email' => $email, ':pass' => password_hash($pass, PASSWORD_DEFAULT),
                        ':signin_time' => date('Y-m-d H:i:s'),
                        ':create_date' => date('Y-m-d H:i:s'));
          // クエリ実行
          $stmt = queryPost($dbh, $sql, $data);

          // クエリ成功の場合
          if($stmt){
            // サインイン有効期限（デフォルトは１時間）を設定
            $sesLimit = 60*60;
            // 最終サインイン日時を現在日時にし、有効期限を設定
            $_SESSION['signin_date'] = time();
            $_SESSION['signin_limit'] = $sesLimit;
            // ユーザーIDを格納
            $_SESSION['user_id'] = $dbh->lastInsertId();

            debug('セッション変数の中身：'.print_r($_SESSION,true));
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
}
?>

<!-- head -->
<?php
  $siteTitle = 'ユーザー登録';
  require('head.php');
?>

  <body class="page-signup page-1colum">

    <!-- ヘッダー -->
    <?php
      require('header.php');
    ?>

    <!-- メインコンテンツ -->
    <div id="contents" class="site-width">

      <!-- Main -->
      <section id="main">

        <div class="form-container">

          <form action="" method="post" class="form">
            <!-- タイトル -->
            <p class="title">ユーザー登録をして、<br>ウチのイッヌを伝えよう</p>
            <div class="area-msg">
              <?php
                // エラーメッセージ
                echo getErrMsg('common');
              ?>
            </div>
            <!-- email入力フォーム -->
            <label class="<?php if(!empty($err_msg['email'])) echo 'err'; ?>">
              Email
              <input type="text" name="email" value="<?php if(!empty($_POST['email'])) echo $_POST['email']; ?>">
            </label>
            <div class="area-msg">
              <?php
                // エラーメッセージ
                echo getErrMsg('email');
              ?>
            </div>
            <!-- password入力フォーム -->
            <label class="<?php if(!empty($err_msg['pass'])) echo 'err'; ?>">
              パスワード <span style="font-size:12px">※英数字６文字以上</span>
              <input type="password" name="pass" value="<?php if(!empty($_POST['pass'])) echo $_POST['pass']; ?>">
            </label>
            <div class="area-msg">
              <?php
                // エラーメッセージ
                echo getErrMsg('pass');
              ?>
            </div>
            <!-- password再入力フォーム -->
            <label class="<?php if(!empty($err_msg['pass_re'])) echo 'err'; ?>">
              パスワード（再入力）
              <input type="password" name="pass_re" value="<?php if(!empty($_POST['pass_re'])) echo $_POST['pass_re']; ?>">
            </label>
            <div class="area-msg">
              <?php
                // エラーメッセージ
                echo getErrMsg('pass_re');
              ?>
            </div>
            <!-- 送信ボタン -->
            <div class="btn-container">
              <input type="submit" class="btn btn-mid" value="登録する">
            </div>
          </form>
        </div>

      </section>

    </div>

    <!-- footer -->
    <?php
      require('footer.php');
    ?>
