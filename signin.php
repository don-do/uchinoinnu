<?php

// 共通変数・関数ファイルを読込み
require('function.php');

debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debug('「　サインインページ　');
debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debugLogStart();

// サインイン認証
require('auth.php');

//================================
// サインイン画面処理
//================================
// POST送信された場合
if(!empty($_POST)){
  debug('POST送信があります。');

  // 「POST送信されたユーザー情報」を代入。キーをname属性に設定
  $email = $_POST['email'];
  $pass = $_POST['pass'];
  // サインイン保持にチェックがあれば、変数に代入
  $pass_save = (!empty($_POST['pass_save']));
  // $pass_save = !empty($_POST['pass_save']) ? true : false; と同意

  // 未入力チェック（変数にて送信内容をチェック。キーにてエラーメッセージを送信）
  validRequired($email, 'email');
  validRequired($pass, 'pass');

  // 未入力チェックOKであれば、後続のバリデーション
  if(empty($err_msg)){
    debug('未入力チェックOK。email形式・email最大文字数、パスワード半角英数・最大最小文字数チェックへ');

    // emailの形式チェック
    validEmail($email, 'email');
    // emailの最大文字数チェック
    validMaxLen($email, 'email');

    // パスワードの半角英数字チェック
    validHalf($pass, 'pass');
    // パスワードの最大文字数チェック
    validMaxLen($pass, 'pass');
    // パスワードの最小文字数チェック
    validMinLen($pass, 'pass');

    }

  // emailとパスワードのチェックがOKであれば、DB接続
  if(empty($err_msg)){
    debug('バリデーションOKです。');

    try {
      $dbh = dbConnect();
      // usersテーブルから、「password,id」カラムの順で値を取得。後続処理でpassword_verify()内のarray_shiftにより、「入力したpassword」と「DB検索したpassword」を照合するため
      $sql = 'SELECT password,id FROM users WHERE email = :email AND delete_flg = 0';
      // プレースホルダに値を割り当て
      $data = array(':email' => $email);
      // クエリ実行
      $stmt = queryPost($dbh, $sql, $data);
      // クエリ実行結果の値を取得
      $result = $stmt->fetch(PDO::FETCH_ASSOC);

      debug('クエリ結果の中身：'.print_r($result,true));

      // クエリ成功かつ、「入力したpassword」と「DB検索したpassword」一致の場合
      if(!empty($result) && password_verify($pass, array_shift($result))){
        // 「 〜 && password_verify($pass, $result['password'])){ 」でもOK
        debug('パスワードがマッチしました。');

        // サインイン有効期限を設定（デフォルト：１時間）
        $sesLimit = 60*60;
        // 最終サインイン日時を、現在日時に更新
        $_SESSION['signin_date'] = time(); // time()「1970年1月1日 00:00:00 を0とし、1秒経過するごとに1ずつ増加させた値が入る」

        // サインイン保持にチェックがある場合
        if($pass_save){
          debug('サインイン保持にチェックがあります。');
          // サインイン有効期限を、30日後にしてセット
          $_SESSION['signin_limit'] = $sesLimit * 24 * 30;

        // サインイン保持にチェックが無い場合
        }else{
          debug('サインイン保持にチェックはありません。');
          // サインイン有効期限を1時間後にセット。サインイン保持しないため
          $_SESSION['signin_limit'] = $sesLimit;
        }
        // ユーザーIDを格納。
        $_SESSION['user_id'] = $result['id']; // 「try {〜 DB接続後」の、「クエリ実行結果の値 $result = $stmt->fetch(PDO::FETCH_ASSOC); 」

        debug('セッション変数の中身：'.print_r($_SESSION,true));
        debug('マイページへ遷移します。');
        // マイページへ遷移
        header("Location:mypage.php");
        exit;

      // クエリ成功かつ、「入力したpassword」と「DB検索したpassword」一致でない場合
      }else{
        debug('password_verify($pass, array_shift($result) → パスワードがアンマッチです。');
        $err_msg['common'] = MSG09; // エラーメッセージ（define('MSG09', 'メールアドレスまたはパスワードが違います');）
      }

    } catch (Exception $e) {
      error_log('エラー発生:'.$e->getMessage());
      $err_msg['common'] = MSG07; // エラーメッセージ（define('MSG07','エラーが発生しました。しばらく経ってからやり直してください。');）
    }
  }
}
debug('画面表示処理終了 <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<');
?>

<!-- head -->
<?php
  $siteTitle = 'サインイン';
  require('head.php');
?>

  <body class="page-signin page-1colum">

    <!-- ヘッダー -->
    <?php
      require('header.php');
    ?>

    <!-- jsにて、メッセージ表示 -->
    <p id="js-show-msg" style="display:none;" class="msg-slide">
      <?php
        // passRemindReceive.php（パスワード再発行の認証ページ）より遷移時、define('SUC03', 'メールを送信しました'); のメッセージ表示
        echo getSessionFlash('msg_success');
        // 参考：
        // 以下、passRemindReceive.phpの該当箇所。
        // 「session_unset();
        // $_SESSION['msg_success'] = SUC03; // 成功メッセージ（define('SUC03', 'メールを送信しました');）
        // debug('セッション変数の中身：'.print_r($_SESSION,true));

        // サインインページへ遷移
        // header("Location:signin.php");
        // exit;」
      ?>
    </p>

    <!-- メインコンテンツ -->
    <div id="contents" class="site-width">

      <!-- Main -->
      <section id="main" >

        <div class="form-container">

          <form action="" method="post" class="form">
            <!-- タイトル -->
            <h2 class="title">サインイン</h2>
            <div class="area-msg">
              <?php
                // エラーメッセージ
                echo getErrMsg('common');
              ?>
            </div>
            <!-- email入力フォーム -->
            <label class="<?php if(!empty($err_msg['email'])) echo 'err'; ?>">
              メールアドレス
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
              パスワード
              <input type="password" name="pass" value="<?php if(!empty($_POST['pass'])) echo $_POST['pass']; ?>">
            </label>
            <div class="area-msg">
              <?php
                // エラーメッセージ
                echo getErrMsg('pass');
              ?>
            </div>
            <!-- サインイン保持のチェックボックス -->
            <label>
              <input type="checkbox" name="pass_save">次回サインインを省略する
            </label>
            <!-- 送信ボタン -->
            <div class="btn-container">
              <input type="submit" class="btn btn-mid" value="サインイン">
            </div>
            <!-- パスワード再発行ページへ -->
            <a href="passRemindSend.php">パスワードを忘れた場合はこちら</a>
         </form>

       </div>

      </section>

    </div>

    <!-- footer -->
    <?php
      require('footer.php');
    ?>
