<?php

// 共通変数・関数ファイルを読込み
require('function.php');

debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debug('「　パスワード再発行メール 送信ページ　');
debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debugLogStart();

// サインイン認証なし（サインインできない人が使う画面なので）

//================================
// パスワード再発行メール 送信画面処理
//================================
// POST送信された場合
if(!empty($_POST)){
  debug('POST送信があります。');
  debug('POST情報：'.print_r($_POST,true));

  // POST送信された情報を代入。キーをname属性に設定
  $email = $_POST['email'];

  // 未入力チェック（変数にて送信内容をチェック。キーにてエラーメッセージを送信）
  validRequired($email, 'email');

  // 未入力チェックOKであれば、後続のバリデーション
  if(empty($err_msg)){
    debug('未入力チェックOK。email形式・最大文字数チェックへ');

    // emailの形式チェック
    validEmail($email, 'email');
    // emailの最大文字数チェック
    validMaxLen($email, 'email');

    if(empty($err_msg)){
      debug('バリデーションOK。');

      try {
        $dbh = dbConnect();
        // usersテーブルから、レコードを取得
        $sql = 'SELECT id, username, age, tel, zip, addr, email, password, signin_time, pic, delete_flg, create_date, update_date FROM users WHERE email = :email AND delete_flg = 0';
        // プレースホルダに値を割り当て
        $data = array(':email' => $email);
        // クエリ実行
        $stmt = queryPost($dbh, $sql, $data);
        // クエリ結果の値を取得
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // クエリ成功かつ、EmailがDBに登録されている場合
        if($stmt && array_shift($result)){
          debug('クエリ成功。DB登録あり。');
          $_SESSION['msg_success'] = SUC03; // 成功メッセージ（define('SUC03', 'メールを送信しました');）

          $auth_key = makeRandKey(); // 認証キー生成

          // ユーザーに、メールを送信
          $from = 'idoharu.com@gmail.com';
          $to = $email;
          $subject = '【パスワード再発行認証のお知らせ】｜ウチのイッヌ';
          $comment = <<<EOT
いつもウチのイッヌをお使いいただき、
まことにありがとうございます。


ウチのイッヌのパスワードを再設定するには、
パスワード再設定を依頼されたコンピューター端末より
下記のURLにて、認証キーをご入力頂くとパスワードが再発行されます。

パスワード再発行認証キー入力ページ：https://uchinoinnu.idoharu.com/passRemindReceive.php
認証キー：{$auth_key}
※認証キーの有効期限は30分となります


なお、認証キーを再発行されたい場合は下記ページより再度再発行をお願い致します。
https://uchinoinnu.idoharu.com/passRemindSend.php

ご不明な点がありましたら、「ウチのイッヌ」カスタマーセンター
までお問い合わせください。

////////////////////////////////////////////////////////////////////////////////
「ウチのイッヌ」カスタマーセンター
URL https://idoharu.com/index.php/contact/
E-mail idoharu.com@gmail.com
////////////////////////////////////////////////////////////////////////////////
EOT;
          sendMail($from, $to, $subject, $comment);

          // 認証に必要な情報をセッションへ保存
          $_SESSION['auth_key'] = $auth_key;
          $_SESSION['auth_email'] = $email;
          $_SESSION['auth_key_limit'] = time()+(60*30); // 制限時間。現在時刻より30分後のUNIXタイムスタンプを入れる
          debug('セッション変数の中身：'.print_r($_SESSION,true));

          // 認証キー入力ページへ遷移
          header("Location:passRemindReceive.php");
          exit;
        // 「クエリ成功かつ、EmailがDBに登録されている状態」で無い場合
        }else{
          debug('クエリに失敗したか、DBに登録のないEmailが入力されました。');
          $err_msg['common'] = MSG07; // エラーメッセージ（define('MSG07','エラーが発生しました。しばらく経ってからやり直してください。');）
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
  $siteTitle = 'パスワード再発行メール送信';
  require('head.php');
?>

  <body class="page-passRemindSend page-1colum">

    <!-- ヘッダー -->
    <?php
      require('header.php');
    ?>

    <!-- メインコンテンツ -->
    <div id="contents" class="site-width">

      <!-- Main -->
      <section id="main" >

        <div class="form-container">

          <form action="" method="post" class="form">
            <!-- 案内 -->
            <p>ご指定のメールアドレス宛に、パスワード再発行用のURLと認証キーをお送り致します。</p>
            <div class="area-msg">
              <?php
                // エラーメッセージ
                echo getErrMsg('common');
              ?>
            </div>
            <!-- Email入力フォーム -->
            <label class="<?php if(!empty($err_msg['email'])) echo 'err'; ?>">
              Email
              <input type="text" name="email" value="<?php echo getFormData('email'); ?>">
            </label>
            <div class="area-msg">
              <?php
                // エラーメッセージ
                echo getErrMsg('email');
              ?>
            </div>
            <!-- 送信ボタン -->
            <div class="btn-container">
              <input type="submit" class="btn btn-mid" value="送信する">
            </div>
          </form>

        </div>
        <!-- サインインページへ -->
        <a href="mypage.php">&lt; サインインページに戻る</a>

      </section>

    </div>

    <!-- footer -->
    <?php
      require('footer.php');
    ?>
