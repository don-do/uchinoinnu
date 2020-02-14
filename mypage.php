<?php
// 共通変数・関数ファイルを読込み
require('function.php');

debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debug('「　マイページ　');
debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debugLogStart();

//サインイン認証
require('auth.php');

//================================
// マイページ画面処理
//================================

// 画面表示用データ取得
//================================

// 自分のユーザーIDを取得
$u_id = $_SESSION['user_id'];
// DBから、イッヌデータを取得
$dogData = getMydogs($u_id);
// DBから、連絡掲示板データを取得
$boardData = getMyMsgsAndboard($u_id);
// DBから、お気に入りデータを取得
$recommendData = getMyRecommend($u_id);

// DBからきちんとデータがすべて取れているかのチェックは行わず、データが取れなければ何も表示しない
//================================

debug('取得したイッヌデータ：'.print_r($dogData,true));
debug('取得した掲示板データ：'.print_r($boardData,true));
debug('取得したお気に入りデータ：'.print_r($recommendData,true));

debug('画面表示処理終了 <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<');
?>

<!-- head -->
<?php
  $siteTitle = 'マイページ';
  require('head.php');
?>

  <body class="page-mypage page-2colum page-signined">
    <style>
      #main{
        border: none !important;
      }
      .list{
        margin-bottom: 30px;
      }
    </style>

    <!-- ヘッダー -->
    <?php
      require('header.php');
    ?>

    <!-- jsにて、メッセージ表示 -->
    <p id="js-show-msg" style="display:none;" class="msg-slide">
      <?php
        // profEdit.php（プロフィール編集ページ）より遷移時、 define('SUC02', 'プロフィールを変更しました'); のメッセージを表示
        // registDog.php（イッヌ登録ページ）より遷移時、define('SUC04', '登録しました'); のメッセージを表示
        echo getSessionFlash('msg_success');
      ?>
    </p>

    <!-- メインコンテンツ -->
    <div id="contents" class="site-width">

      <h1 class="page-title">おうち</h1>

      <!-- Main -->
      <section id="main" >

        <section class="list panel-list">
          <!-- ユーザーが登録したイッヌ（タイトル・画像） -->
          <h2 class="title" style="margin-bottom:15px;">
            ウチのイッヌ（編集できるよ）
          </h2>
          <?php
            if(!empty($dogData)):
              foreach($dogData as $key => $val):
          ?>
              <a href="registDog.php<?php echo '?d_id='.$val['id']; ?>" class="panel">
                <div class="panel-head">
                  <img src="<?php echo showImg(sanitize($val['pic1'])); ?>" alt="<?php echo sanitize($val['name']); ?>">
                </div>
                <div class="panel-body">
                  <p class="panel-title"><?php echo sanitize($val['name']); ?> <span class="dogage"><?php echo sanitize(number_format($val['dogage'])); ?> 才</span></p>
                </div>
              </a>
          <?php
              endforeach;
            endif;
          ?>
        </section>

        <section class="list list-table">
          <!-- ユーザーが利用した掲示板（タイトル・日時・相手・メッセージ） -->
          <h2 class="title">
            メッセージの履歴
          </h2>
          <table class="table">
            <thead>
              <tr>
                <th>最新送信日時</th>
                <th>お話しした人</th>
                <th>メッセージ</th>
              </tr>
            </thead>
            <tbody>
            <?php
              if(!empty($boardData)){
                foreach($boardData as $key => $val){
                  if(!empty($val['msg'])){
                    $msg = array_shift($val['msg']);
                    // 掲示板IDから相手のユーザー情報を取得
                    $viewData = getMsgsAndboard($val['id']);
                    debug('掲示板IDから取得したDBデータ：'.print_r($viewData,true));
                    // viewDataから相手のユーザーIDを取り出す
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
                    debug('掲示板データから取得したコメント相手のID：'.$partnerUserId);
                    // 相手のユーザーIDが入っている場合
                    if(isset($partnerUserId)){
                      // DBから取引相手のユーザー情報を取得
                      $partnerUserInfo = getUser($partnerUserId);
                    }
                    debug('取得した$partnerUserInfoのユーザデータ（dogDetail.phpから、相手が投稿したイッヌデータにPOSTすると相手のデータ。dogDetail.phpから、自分が投稿したイッヌデータにPOSTすると自分のデータ）：'.print_r($partnerUserInfo,true));
            ?>
                    <tr>
                      <td><?php echo sanitize(date('Y.m.d H:i:s',strtotime($msg['send_date']))); ?></td>
                      <td><a href="msg.php?m_id=<?php echo sanitize($val['id']); ?>"><?php echo mb_substr(sanitize($partnerUserInfo['username']),0,40); ?>...</a></td>
                      <td><a href="msg.php?m_id=<?php echo sanitize($val['id']); ?>"><?php echo mb_substr(sanitize($msg['msg']),0,15); ?>...</a></td>
                    </tr>
                <?php
                  }else{
                ?>
                    <tr>
                      <td>--</td>
                      <td><a href="msg.php?m_id=<?php echo sanitize($val['id']); ?>"><?php echo mb_substr(sanitize($partnerUserInfo['username']),0,40); ?>...</a></td>
                      <td><a href="msg.php?m_id=<?php echo sanitize($val['id']); ?>">まだメッセージはありません</a></td>
                    </tr>
            <?php
                  }
                }
              }
            ?>
            </tbody>
          </table>
        </section>

        <section class="list panel-list">
          <!-- ユーザーのお気に入り（タイトル・画像） -->
          <h2 class="title" style="margin-bottom:15px;">
            お気に入り
          </h2>
          <?php
            if(!empty($recommendData)):
              foreach($recommendData as $key => $val):
          ?>
                <a href="dogDetail.php<?php echo (!empty(appendGetParam())) ? appendGetParam().'&d_id='.$val['id'] : '?d_id='.$val['id']; ?>" class="panel">
                  <div class="panel-head">
                    <img src="<?php echo showImg(sanitize($val['pic1'])); ?>" alt="<?php echo sanitize($val['name']); ?>">
                  </div>
                  <div class="panel-body">
                    <p class="panel-title"><?php echo sanitize($val['name']); ?> <span class="dogage"><?php echo sanitize(number_format($val['dogage'])); ?> 才</span></p>
                  </div>
                </a>
          <?php
              endforeach;
            endif;
          ?>
        </section>

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
