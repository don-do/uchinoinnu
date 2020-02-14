<?php

// 共通変数・関数ファイルを読込み
require('function.php');

debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debug('「　パスワード再発行認証キー入力ページ　');
debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debugLogStart();

// サインイン認証なし（サインインできない人が使う画面なので）

// SESSIONに認証キーがあるか確認。無ければリダイレクト
if(empty($_SESSION['auth_key'])){
  // パスワード再発行メール送信ページ（認証キー発行ページ）へ遷移
  header("Location:passRemindSend.php");
  exit;
}

//================================
// パスワード再発行認証キー入力画面処理
//================================
// POST送信された場合
if(!empty($_POST)){
  debug('POST送信があります。');
  debug('POST情報：'.print_r($_POST,true));

  // POST送信された情報を代入。キーをname属性に設定
  $auth_key = $_POST['token'];

  // 未入力チェック（変数にて送信内容をチェック。キーにてエラーメッセージを送信）
  validRequired($auth_key, 'token');

  // 未入力チェックOKであれば、後続のバリデーション
  if(empty($err_msg)){
    debug('未入力チェックOK。固定長・半角チェックへ');

    // 固定長チェック
    validLength($auth_key, 'token');
    // 半角チェック
    validHalf($auth_key, 'token');

    if(empty($err_msg)){
      debug('バリデーションOK。');

      // 認証チェック。POST送信された認証キーの情報と、SESSIONの情報を比較
      if($auth_key !== $_SESSION['auth_key']){
        $err_msg['common'] = MSG15; // エラーメッセージ（define('MSG15', '正しくありません');）
      }
      // 認証チェック。現在日時が、有効期限未満かどうか
      if(time() > $_SESSION['auth_key_limit']){
        $err_msg['common'] = MSG16; // エラーメッセージ（define('MSG16', '有効期限が切れています');）
      }

      if(empty($err_msg)){
        debug('認証OK。');

        $pass = makeRandKey(); // 新しいパスワードを生成

        try {
          $dbh = dbConnect();
          // usersテーブルの、passwordカラムを更新
          $sql = 'UPDATE users SET password = :pass WHERE email = :email AND delete_flg = 0';
          // プレースホルダに値を割り当て
          $data = array(':email' => $_SESSION['auth_email'], ':pass' => password_hash($pass, PASSWORD_DEFAULT));
          // クエリ実行
          $stmt = queryPost($dbh, $sql, $data);

          // クエリ成功の場合
          if($stmt){
            debug('クエリ成功。');

            // ユーザーに、メールを送信
            $from = 'idoharu.com@gmail.com';
            $to = $_SESSION['auth_email'];
            $subject = '【パスワード再発行のお知らせ】｜ウチのイッヌ';
            $comment = <<<EOT
本メールアドレス宛にパスワードの再発行を致しました。
下記のURLにて再発行パスワードをご入力頂き、サインインください。

サインインページ：https://uchinoinnu.idoharu.com/signin.php
再発行パスワード：{$pass}
※ご希望の場合、サインイン後にパスワードをご変更いただけます。


ご不明な点がありましたら、「ウチのイッヌ」カスタマーセンター
までお問い合わせください。

////////////////////////////////////////////////////////////////////////////////
「ウチのイッヌ」カスタマーセンター
URL https://idoharu.com/index.php/contact/
E-mail idoharu.com@gmail.com
////////////////////////////////////////////////////////////////////////////////
EOT;
            sendMail($from, $to, $subject, $comment);

            // セッション削除
            session_unset();
            $_SESSION['msg_success'] = SUC03; // 成功メッセージ（define('SUC03', 'メールを送信しました');）
            debug('セッション変数の中身：'.print_r($_SESSION,true));

            // サインインページへ遷移
            header("Location:signin.php");
            exit;

          // クエリ成功でない場合
          }else{
            debug('クエリに失敗しました。');
            $err_msg['common'] = MSG07; // エラーメッセージ（define('MSG07','エラーが発生しました。しばらく経ってからやり直してください。');）
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
  $siteTitle = 'パスワード再発行認証';
  require('head.php');
?>

  <body class="page-passRemindReceive page-1colum">

    <!-- ヘッダー -->
    <?php
      require('header.php');
    ?>

    <!-- jsにて、メッセージ表示 -->
    <p id="js-show-msg" style="display:none;" class="msg-slide">
      <?php
        // passRemindSend.php（パスワード再発行メール 送信ページ）より遷移時、define('SUC03', 'メールを送信しました'); のメッセージを表示
        echo getSessionFlash('msg_success');
        // 参考：
        // 以下、passRemindSend.phpのSUC03の該当箇所。
        // 「debug('クエリ成功。DB登録あり。');
        // $_SESSION['msg_success'] = SUC03; // 成功メッセージ（define('SUC03', 'メールを送信しました');）

        // $auth_key = makeRandKey(); // 認証キー生成」
      ?>
    </p>

    <!-- メインコンテンツ -->
    <div id="contents" class="site-width">

      <!-- Main -->
      <section id="main" >

        <div class="form-container">

          <form action="" method="post" class="form">
            <!-- 案内 -->
            <p>ご指定のメールアドレスお送りした【パスワード再発行認証】メール内にある「認証キー」をご入力ください。</p>
            <div class="area-msg">
              <?php
                // エラーメッセージ
                echo getErrMsg('common');
              ?>
            </div>
            <!-- 認証キー入力フォーム -->
            <label class="<?php if(!empty($err_msg['token'])) echo 'err'; ?>">
              認証キー
              <input type="text" name="token" value="<?php echo getFormData('token'); ?>">
            </label>
            <div class="area-msg">
              <?php
                // エラーメッセージ
                echo getErrMsg('token');
              ?>
            </div>
            <!-- 送信ボタン -->
            <div class="btn-container">
              <input type="submit" class="btn btn-mid" value="再発行する">
            </div>
          </form>

        </div>
        <!-- パスワード再発行メール 送信ページへ -->
        <a href="passRemindSend.php">&lt; パスワード再発行メールを再度送信する</a>

      </section>

    </div>

    <!-- footer -->
    <?php
      require('footer.php');
    ?>
