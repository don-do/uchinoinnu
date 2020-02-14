<?php

// 共通変数・関数ファイルを読込み
require('function.php');

debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debug('「　イッヌ登録消去ページ　');
debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debugLogStart();

// サインイン認証
require('auth.php');

//================================
// イッヌ登録消去画面処理
//================================
// GETデータを格納
$d_id = (!empty($_GET['d_id'])) ? $_GET['d_id'] : '';

// POST送信された場合（name="submit"）
if(!empty($_POST['submit'])){
  debug('POST送信があります。');
  try {
    $dbh = dbConnect();
    // dogsテーブルのレコードを論理削除
    $sql = 'UPDATE dogs SET delete_flg = 1 WHERE id = :d_id';
    // プレースホルダに値を割り当て
    $data = array(':d_id' => $d_id);
    // クエリ実行
    $stmt = queryPost($dbh, $sql, $data);

    // クエリ成功の場合
    if($stmt){
      debug('セッション変数の中身：'.print_r($_SESSION,true));
      // マイページへ遷移
      debug('マイページへ遷移します。');
      header("Location:mypage.php");
      exit;

    // クエリ成功でない場合
    }else{
      debug('クエリが失敗しました。');
      $err_msg['common'] = MSG07; // エラーメッセージ（'エラーが発生しました。しばらく経ってからやり直してください。'）
    }

  } catch (Exception $e) {
    error_log('エラー発生:'.$e->getMessage());
    $err_msg['common'] = MSG07; // エラーメッセージ（'エラーが発生しました。しばらく経ってからやり直してください。'）
  }
}
debug('画面表示処理終了 <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<');
?>

<!-- head -->
<?php
  $siteTitle = 'イッヌの登録を消去する';
  require('head.php');
?>

  <body class="page-withdraw page-1colum">

    <style>
      .form .btn{
        float: none;
      }
      .form{
        text-align: center;
      }
    </style>

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
             <!-- タイトル -->
            <h2 class="title">このイッヌの登録を<br>消去しますか？</h2>
            <div class="area-msg">
              <?php
                // エラーメッセージ
                echo getErrMsg('common');
              ?>
            </div>
            <!-- 送信ボタン -->
            <div class="btn-container">
              <input type="submit" class="btn btn-mid" value="このイッヌの登録を消去" name="submit" style="max-width: 220px; ">
            </div>
          </form>

        </div>
        <!-- マイページへ -->
        <a href="mypage.php">&lt; マイページに戻る</a>
        <!-- 当画面遷移前の、イッヌ編集ページへ -->
        <a href="registDog.php<?php echo '?d_id='.$d_id; ?>" style="float: right;">&gt; 今の編集ページに戻る</a>
      </section>

    </div>

    <!-- footer -->
    <?php
      require('footer.php');
    ?>
