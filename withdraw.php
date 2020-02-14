<?php

// 共通変数・関数ファイルを読込み
require('function.php');

debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debug('「　退会ページ　');
debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debugLogStart();

// サインイン認証（退会ページも、サインインしていないと見られないページのため）
require('auth.php');

//================================
// 退会画面処理
//================================
// POST送信された場合（name="submit"）
if(!empty($_POST)){
  debug('POST送信があります。');
  try {
    $dbh = dbConnect();
    // usersテーブル・dogsテーブルのレコードを、論理削除
    $sql1 = 'UPDATE users SET delete_flg = 1 WHERE id = :us_id';
    $sql2 = 'UPDATE dogs SET delete_flg = 1 WHERE user_id = :us_id';
    // プレースホルダに値を割り当て
    $data = array(':us_id' => $_SESSION['user_id']);
    // クエリ実行
    $stmt1 = queryPost($dbh, $sql1, $data);
    $stmt2 = queryPost($dbh, $sql2, $data);

    // クエリ成功の場合
    if($stmt1 && $stmt2){
    // セッション・クッキーを削除（サインアウトする）
        // セッション変数を全て解除
        $_SESSION = array();
        if(isset($_COOKIE[session_name()])) {
        // （クライアント・ブラウザ側）クッキーとして記録されているセッション（ID）を削除
            setcookie(session_name(), '', time()-42000, '/');
        // setcookie(Cookie名, Cookie値, 有効日時（過去に設定し、削除）, パス（ドメイン配下すべて）)
        }
        // （サーバー側）セッションID・変数を削除
        session_destroy();
      debug('セッション変数の中身：'.print_r($_SESSION,true));
      debug('トップページへ遷移します。');
      // トップページへ遷移
      header("Location:index.php");
      exit;

    // クエリ成功でない場合
    }else{
      debug('クエリが失敗しました。');
      $err_msg['common'] = MSG07; // エラーメッセージ（define('MSG07','エラーが発生しました。しばらく経ってからやり直してください。');）
    }

  } catch (Exception $e) {
    error_log('エラー発生:'.$e->getMessage());
    $err_msg['common'] = MSG07; // エラーメッセージ（define('MSG07','エラーが発生しました。しばらく経ってからやり直してください。');）
  }
}
debug('画面表示処理終了 <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<');
?>

<!-- head -->
<?php
  $siteTitle = '退会';
  require('head.php');
?>

  <body class="page-1colum">

    <style>
      .form .btn{
        float: none;
      }
      .form{
        text-align: center;
        margin: 0 0 0 200px;
      }
      @media screen and (max-width: 767px) {
        .form {
          width: 105%;
          margin-left: 5px;
        }
      }
      #sidebar{
        margin-top: 88px;
        min-height: 500px;
      }
      #sidebar > a {
        display: block;
        margin-bottom: 15px;
      }
    </style>

    <!-- ヘッダー -->
    <?php
      require('header.php');
    ?>

    <!-- メインコンテンツ -->
    <div id="contents" class="site-width">

      <!-- Main -->
      <section id="main" style="width: 75%;">

        <div class="form-container">

          <form action="" method="post" class="form">
            <!-- タイトル -->
            <h2 class="title">退会</h2>
            <div class="area-msg">
              <?php
                // エラーメッセージ
                echo getErrMsg('common');
              ?>
            </div>
            <!-- 送信ボタン -->
            <div class="btn-container">
              <input type="submit" class="btn btn-mid" value="退会する" name="submit">
            </div>
          </form>

        </div>
        <!-- マイページへ -->
        <a href="mypage.php">&lt; マイページに戻る</a>

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
