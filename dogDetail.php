<?php

// 共通変数・関数ファイルを読込み
require('function.php');

debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debug('「　イッヌ詳細ページ　');
debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debugLogStart();

// サインイン認証なし（サインインしていない人も見られる画面なので）

//================================
// イッヌ詳細画面処理
//================================

// 画面表示用データ取得
//================================
// イッヌIDのGETパラメータを取得
$d_id = (!empty($_GET['d_id'])) ? $_GET['d_id'] : '';
// DBからイッヌデータを取得
$viewData = getDogOne($d_id);
// DBから連絡掲示板データを取得
$boardData = getBoard($d_id);

// パラメータ改ざんチェック
//================================
// 改ざんされている（URLをいじくった）場合。DBに、GETパラメータに合致するイッヌIDが無ければ、正しいイッヌデータが取れないのでトップページへ遷移させる
if(empty($viewData)){
  error_log('エラー発生:指定ページに不正な値が入りました。GETパラメータに合致するイッヌIDがDBにありません。トップページへ遷移します。');
  // トップページへ遷移
  header("Location:index.php");
  exit;
}
debug('取得したイッヌのDBデータ：'.print_r($viewData,true));
debug('取得した掲示板のDBデータ：'.print_r($boardData,true));

// POST送信時処理
//================================
// POST送信された場合（name="submit"）
if(!empty($_POST['submit'])){
  debug('POST送信があります。');

  // サインイン認証
  require('auth.php');

  // すでに掲示板を作成済みの場合
  // 作成済みの掲示板を表示（ メッセージを受けた人がユーザーの場合 または、メッセージをした人がユーザーの場合 ）
  if( $boardData[0]['read_user'] === $_SESSION['user_id'] || $boardData[0]['comment_user'] === $_SESSION['user_id'] ) {
    debug('自分の掲示板情報：'.print_r($boardData,true));
    $_SESSION['msg_success'] = SUC05;
    debug('すでに作成済みの連絡掲示板へ遷移します。');
    //連絡掲示板へ遷移
    header("Location:msg.php?m_id=".$boardData[0]['id']);
    exit;
  // まだ掲示板を作成していない場合
  }else{
    // 掲示板を新規作成、DB接続
    try {
      $dbh = dbConnect();
      // boardテーブルに、レコードを挿入
      $sql = 'INSERT INTO board (comment_user, read_user, dog_id, create_date) VALUES (:c_uid, :r_uid, :d_id, :date)';
      // プレースホルダに値を割り当て
      $data = array(':c_uid' => $viewData['user_id'], ':r_uid' => $_SESSION['user_id'], ':d_id' => $d_id, ':date' => date('Y-m-d H:i:s'));
      // クエリ実行
      $stmt = queryPost($dbh, $sql, $data);

      // クエリ成功の場合
      if($stmt){
        $_SESSION['msg_success'] = SUC05;
        debug('連絡掲示板へ遷移します。');
        // 連絡掲示板へ遷移
        header("Location:msg.php?m_id=".$dbh->lastInsertID());
        exit;
        }

    } catch (Exception $e) {
        error_log('エラー発生:' . $e->getMessage());
        $err_msg['common'] = MSG07;
    }
  }
}
debug('画面表示処理終了 <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<');
?>

<!-- head -->
<?php
  $siteTitle = 'イッヌ詳細';
  require('head.php');
?>

  <body class="page-dogDetail page-1colum">

    <!-- ヘッダー -->
    <?php
      require('header.php');
    ?>

    <!-- メインコンテンツ -->
    <div id="contents" class="site-width">

      <!-- Main -->
      <section id="main" >

        <!-- カテゴリー、名前、お気に入りボタン活性非活性 -->
        <div class="title">
          <span class="badge"><?php echo sanitize($viewData['category']); ?></span>
          <?php echo sanitize($viewData['name']);
            // お気に入りボタン・活性非活性
            // サインインしている場合、お気に入り機能を使える
            if(isset($_SESSION['user_id']) && isSignin()){
              echo '<i class="fa fa-paw icn-recommend js-click-recommend ';
                // DBにてお気に入り登録している場合、お気に入りアイコンに色をつける
                if(isRecommend($_SESSION['user_id'], $viewData['id'])){ echo 'active'; }
                // data属性からイッヌのIDを取得
              echo '" aria-hidden="true" data-dogid="'.sanitize($viewData['id']).'">お気に入り登録！</i>';
            // サインインしていない場合、お気に入り機能を使えず、disabled
            }else{
              echo '<i class="fa fa-paw icn-recommend aria-hidden="true" disabled="disabled" style="cursor: not-allowed">サインインするとクリックできるよ！</i>';
            }
          ?>
        </div>
        <!-- 画像表示 -->
        <div class="dog-img-container">
          <div class="img-main">
            <img src="<?php echo showImg(sanitize($viewData['pic1'])); ?>" alt="メイン画像：<?php echo sanitize($viewData['name']); ?>" id="js-switch-img-main">
          </div>
          <div class="img-sub">
            <img src="<?php echo showImg(sanitize($viewData['pic1'])); ?>" alt="画像1：<?php echo sanitize($viewData['name']); ?>" class="js-switch-img-sub">
            <img src="<?php echo showImg(sanitize($viewData['pic2'])); ?>" alt="画像2：<?php echo sanitize($viewData['name']); ?>" class="js-switch-img-sub">
            <img src="<?php echo showImg(sanitize($viewData['pic3'])); ?>" alt="画像3：<?php echo sanitize($viewData['name']); ?>" class="js-switch-img-sub">
          </div>
        </div>
        <!-- コメント表示 -->
        <div class="dog-detail">
          <p><?php echo sanitize($viewData['comment']); ?></p>
        </div>
        <!-- 遷移ボタン -->
        <div class="dog-play">
          <div class="item-left">
            <a href="index.php<?php echo appendGetParam(array('d_id')); ?>">&lt; イッヌ一覧に戻る</a>
          </div>
          <form action="" method="post">
            <div class="item-right">
              <?php
                echo getErrMsg('common');
              ?>
              <div class="btn-container">
              <?php
                // ユーザーによる、場合分け遷移ボタン
                // ユーザーのイッヌの場合、編集画面へ遷移するボタンを表示
                if(!empty($_SESSION) && $_SESSION['user_id'] === $viewData['user_id']){
              ?>
                <a href="registDog.php<?php echo '?d_id='.$d_id; ?>">
                  <input value="編集する！" class="btn btn-primary">
                </a>
              <?php
                // ユーザーのイッヌでない場合、掲示板画面へ遷移するボタンを表示
                }elseif(!empty($_SESSION) && $_SESSION['user_id'] !== $viewData['user_id']){
              ?>
                  <input type="submit" value="声をかけてみる！" name="submit" class="btn btn-primary">
              <?php
                // サインインしていない場合、サインインページへ遷移するボタンを表示
                }else{
              ?>
                <a href="signin.php">
                  <input value="サインインページへ" class="btn btn-primary">
                </a>
              <?php
                // 場合分け遷移ボタン終了
                }
              ?>
              </div>
            </div>
          </form>
          <!-- 年れい表示 -->
          <div class="item-right">
            <p class="dogage"><?php echo sanitize(number_format($viewData['dogage'])); ?> 才</p>
          </div>
        </div>

      </section>

    </div>

    <!-- footer -->
    <?php
      require('footer.php');
    ?>
