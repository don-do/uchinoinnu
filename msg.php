<?php

// 共通変数・関数ファイルを読込み
require('function.php');

debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debug('「　連絡掲示板ページ　');
debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debugLogStart();

// サインイン認証なし（サインインしていないと表示できない画面なので）

//================================
// 連絡掲示板画面処理
//================================
$partnerUserId = '';
$partnerUserInfo = '';
$myUserInfo = '';
$dogInfo = '';
$viewData = '';

// 画面表示用データ取得
//================================

// GETパラメータを取得
$m_id = (!empty($_GET['m_id'])) ? $_GET['m_id'] : '';
// DBから、掲示板とメッセージデータを取得
$viewData = getMsgsAndboard($m_id);
debug('取得したDBデータ：'.print_r($viewData,true));

// パラメータ改ざんチェック
//================================
// 改ざんされている（URLをいじくった）場合。DBに、GETパラメータに合致する掲示板IDが無ければ、正しい掲示板データを取れないのでマイページへ遷移
if(empty($viewData)){
  error_log('エラー発生:指定ページに不正な値が入りました。GETパラメータに合致する掲示板IDがDBにありません。マイページへ遷移します。');
  // マイページへ遷移
  header("Location:mypage.php");
  exit;
}

// 掲示板は、削除されていない場合
if($viewData !== 1){

  // イッヌ情報を取得
  $dogInfo = getDogOne($viewData['dog_id']);
  debug('取得したDBデータ：'.print_r($dogInfo,true));
    // イッヌ情報が入っていない場合
    if(empty($dogInfo)){
      error_log('エラー発生:イッヌ情報が取得できませんでした');
      // マイページへ遷移
      header("Location:mypage.php");
      exit;
    }

  // $viewDataから相手のユーザーIDを取り出す
    // メッセージを送った側と受け取った側の両方の情報を変数に入れる
    $communicationUserIds[] = $viewData['comment_user'];
    $communicationUserIds[] = $viewData['read_user'];
    // 自分のユーザーIDが$communicationUserIdsに有るかを検索し、配列のキーを取ってくる。falseではない場合、自分のユーザーIDが有った場合中の処理へ
    if(($key = array_search($_SESSION['user_id'], $communicationUserIds)) !== false) {
    // 取り出したキーを添え字とした配列の中身、つまり自分のユーザーIDを取り除く。相手のユーザーIDだけが残る
      unset($communicationUserIds[$key]);
    }
    // 先頭の配列から取り出した、相手のユーザーIDを変数に格納
    $partnerUserId = array_shift($communicationUserIds);
    debug('取得した相手のユーザーID：'.$partnerUserId);
  // 相手のユーザーIDが入っている場合
  if(isset($partnerUserId)){
    // DBから取引相手のユーザー情報を取得
    $partnerUserInfo = getUser($partnerUserId);
  }
  debug('取得した$partnerUserInfoのユーザデータ（dogDetail.phpから「相手が投稿したイッヌデータ」にPOSTすると、相手のデータ。dogDetail.phpから、「自分が投稿したイッヌデータ」にPOSTすると、自分のデータ）：'.print_r($partnerUserInfo,true));
    // ユーザー情報が無かった場合
    if(empty($partnerUserInfo)){
      error_log('エラー発生:ユーザー情報が取得できませんでした');
      // マイページへ遷移
      header("Location:mypage.php");
      exit;
    }

  // DBから自分のユーザー情報を取得
  $myUserInfo = getUser($_SESSION['user_id']);
  debug('取得した$myUserInfoのユーザデータ（自分のデータ）：'.print_r($myUserInfo,true));
    // 自分のユーザー情報が無かった場合
    if(empty($myUserInfo)){
      error_log('エラー発生:自分のユーザー情報が取得できませんでした');
      // マイページへ遷移
      header("Location:mypage.php");
      exit;
    }
}

// POST送信時処理
//================================
// POST送信された場合
if(!empty($_POST)){
  debug('POST送信があります。');

  //  サインイン認証
  require('auth.php');

  //  バリデーションチェック
  $msg = (isset($_POST['msg'])) ? $_POST['msg'] : '';
  //  最大文字数チェック
  validMaxLen($msg, 'msg', 200);
  //  未入力チェック
  validRequired($msg, 'msg');

  // メッセージ内容のチェックがOKであれば、DB接続
  if(empty($err_msg)){
    debug('バリデーションOKです。');

    try {
      $dbh = dbConnect();
      // messageテーブルに、レコードを挿入
      $sql = 'INSERT INTO message (board_id, send_date, to_user, from_user, msg, create_date) VALUES (:b_id, :send_date, :to_user, :from_user, :msg, :date)';
      // プレースホルダに値を割り当て
      $data = array(':b_id' => $m_id, ':send_date' => date('Y-m-d H:i:s'), ':to_user' => $partnerUserId, ':from_user' => $_SESSION['user_id'], ':msg' => $msg, ':date' => date('Y-m-d H:i:s'));
      // クエリ実行
      $stmt = queryPost($dbh, $sql, $data);

      // クエリ成功の場合
      if($stmt){
        // POSTをクリア
        $_POST = array();
        debug('連絡掲示板へ遷移します。');
        // 自分自身のページに遷移
        header("Location: " . $_SERVER['PHP_SELF'] .'?m_id='.$m_id);
        exit;
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
  $siteTitle = '連絡掲示板';
  require('head.php');
?>

  <body class="page-msg page-1colum">
    <style>
      .area-msg {
        padding: 0 0 15px 0;
        color: red;
      }
    </style>

    <!-- ヘッダー -->
    <?php
      require('header.php');
    ?>

    <!-- jsにて、メッセージ表示 -->
    <p id="js-show-msg" style="display:none;" class="msg-slide">
      <?php
        // dogDetail.php（イッヌ詳細ページ）より遷移時、define('SUC05', '楽しくお話ししてみよう！'); のメッセージを表示
        echo getSessionFlash('msg_success');
        // 参考：
        // 以下、dogDetail.phpのSUC03の該当箇所。2カ所
        // 【1つ目（既存の掲示板がある場合）】
        // $_SESSION['msg_success'] = SUC05;
        // debug('すでに作成済みの連絡掲示板へ遷移します。');
        //連絡掲示板へ遷移
        // header("Location:msg.php?m_id=".$boardData[0]['id']);
        // 【2つ目（掲示板を新規作成する場合）】
        // $_SESSION['msg_success'] = SUC05;
        // debug('連絡掲示板へ遷移します。');
        // 連絡掲示板へ遷移
        // header("Location:msg.php?m_id=".$dbh->lastInsertID());
        // exit;
      ?>
    </p>

    <!-- メインコンテンツ -->
    <div id="contents" class="site-width">

      <!-- Main -->
      <section id="main" >

        <div class="msg-info">
          <?php
            // 1ではない。つまり、掲示板がある場合
            if($viewData !== 1){
          ?>
              <!-- 相手の情報 -->
              <div class="avatar-img">
                <img src="<?php echo showImg(sanitize($partnerUserInfo['pic'])); ?>" alt="" class="avatar"><br>
              </div>
              <div class="avatar-info">
                <?php echo sanitize($partnerUserInfo['username']).' '.sanitize($partnerUserInfo['age']).'歳' ?><br>
                〒<?php // 初期値に0が入るため、if( !empty($partnerUserInfo['zip']) ){で、DBの情報が0以外であれば表示
                                                if( !empty($partnerUserInfo['zip']) ){
                                                  echo sanitize(substr($partnerUserInfo['zip'],0,3)) . "-" . sanitize(substr($partnerUserInfo['zip'],3));
                                                }else{
                                                  echo ' 未登録';
                                                }
                  ?><br>
                <?php echo sanitize($partnerUserInfo['addr']); ?><br>
                TEL：<?php // 初期値に0が入るため、if( !empty($partnerUserInfo['zip']) ){で、DBの情報が0以外であれば表示
                                                if( !empty($partnerUserInfo['tel']) ){
                                                  echo sanitize(substr($partnerUserInfo['tel'],0,3) . "-" . substr($partnerUserInfo['tel'],3,4) . "-" . substr($partnerUserInfo['tel'],7,4));
                                                }else{
                                                  echo ' 未登録';
                                                }
                  ?>
              </div>
              <!-- イッヌの情報 -->
              <div class="dog-info">
                <div class="left">
                  このイッヌについて<br>
                  <img src="<?php echo showImg(sanitize($dogInfo['pic1'])); ?>" alt="" height="70px" width="auto" >
                </div>
                <div class="right">
                  <?php echo sanitize($dogInfo['name']); ?><br>
                  年れい：<span class="dogage"><?php echo number_format(sanitize($dogInfo['dogage'])); ?>才</span><br>
                  会話を始めた日：<?php echo date('Y/m/d', strtotime(sanitize($viewData['create_date']))); ?>
                </div>
              </div>
          <?php
            // 掲示板が無い場合
            }else{
          ?>
              <p>掲示板はありません。</p>
          <?php
            }
          ?>
        </div>

        <div class="area-board" id="js-scroll-bottom">
          <?php
            // メッセージがある場合
            if(!empty($viewData['msg'])){
              // メッセージ情報を展開
              foreach($viewData['msg'] as $key => $val){
                // 送信者メッセージかつ、送信者メッセージが相手のものの場合
                if(!empty($val['from_user']) && $val['from_user'] == $partnerUserId){
          ?>
                  <!-- 掲示板左側のメッセージ情報 -->
                  <div class="msg-cnt msg-left">
                    <div class="avatar">
                      <img src="<?php echo sanitize(showImg($partnerUserInfo['pic'])); ?>" alt="" class="avatar">
                    </div>
                    <p class="msg-inrTxt">
                      <span class="triangle"></span>
                      <?php echo sanitize($val['msg']); ?>
                    </p>
                    <div style="font-size:.5em;"><?php echo sanitize($val['send_date']); ?></div>
                  </div>
              <?php
                // 上記以外。送信者メッセージが自分のものの場合
                }else{
              ?>
                  <!-- 掲示板右側のメッセージ情報 -->
                  <div class="msg-cnt msg-right">
                    <div class="avatar">
                      <img src="<?php echo sanitize(showImg($myUserInfo['pic'])); ?>" alt="" class="avatar">
                    </div>
                    <p class="msg-inrTxt">
                      <span class="triangle"></span>
                      <?php echo sanitize($val['msg']); ?>
                    </p>
                    <div style="font-size:.5em;text-align:right;"><?php echo sanitize($val['send_date']); ?></div>
                  </div>
          <?php
                }
              }
            // 1と合致。つまり、掲示板が無い場合
            }elseif($viewData === 1){
          ?>
              <p style="text-align:center;line-height:20;">掲示板はありません。</p>
          <?php
            // 掲示板があり、メッセージが無い場合。
            }else{
          ?>
              <p style="text-align:center;line-height:20;">メッセージ投稿はまだありません</p>
          <?php
            }
          ?>
        </div>

        <div class="area-send-msg">
          <form action="" method="post">
            <!-- メッセージ入力フォーム -->
            <label class="<?php if(!empty($err_msg['msg'])) echo 'err'; ?>">
             <textarea name="msg" id="js-count" cols="30" rows="3"><?php echo getFormData('msg'); ?></textarea>
            </label>
            <!-- 文字数カウント -->
            <p class="counter-text"><span id="js-count-view">0</span>/200文字</p>
            <div class="area-msg">
              <?php
                // エラーメッセージ
                echo getErrMsg('msg');
              ?>
            </div>
            <!-- 送信ボタン -->
            <input type="submit" value="送信！" class="btn btn-send">
          </form>
        </div>

      </section>

      <script src="js/vendor/jquery-3.4.1.min.js"></script>
      <script>
        $(function(){
          // $('#js-scroll-bottom')にてjQueryオブジェクトを取得
          $('#js-scroll-bottom').animate({scrollTop: $('#js-scroll-bottom')[0].scrollHeight}, 'fast');
          // $('#js-scroll-bottom')[0]ではjQueryオブジェクトを配列で取得し、[0]にて配列の0番目の要素を取得
          // .scrollHeightによって、スクロール分を含めた全体の高さを取得
          // その取得した高さをscrollTop: でscrollTopプロパティにセットすることで、animate()関数によりスクロール位置を一番下に移動させて表示
        });
      </script>

    </div>

    <!-- footer -->
    <?php
      require('footer.php');
    ?>
