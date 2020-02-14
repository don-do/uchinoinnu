<?php

// 共通変数・関数ファイルを読込み
require('function.php');

debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debug('「　イッヌ登録ページ　');
debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debugLogStart();

// サインイン認証
require('auth.php');

//================================
// イッヌ登録画面処理
//================================

// 画面表示用データ取得
//================================
// GETデータを格納
$d_id = (!empty($_GET['d_id'])) ? $_GET['d_id'] : '';
// DBからペットのデータを取得
$dbFormData = (!empty($d_id)) ? getDog($_SESSION['user_id'], $d_id) : '';
// 新規登録画面か、編集画面かの判別用フラグ
$edit_flg = (empty($dbFormData)) ? false : true;
// DBからカテゴリーデータを取得
$dbCategoryData = getCategory();
// DBからソートデータを取得
$dbSortData = getSort();

// パラメータ改ざんチェック
//================================
// GETパラメータはあるが、改ざんされている（URLをいじくった）場合。DBに、ユーザーIDに合致するイッヌIDが無ければ正しいイッヌデータが取れないのでマイページへ遷移させる
if(!empty($d_id) && empty($dbFormData)){
  debug('エラー発生:指定ページに不正な値が入りました。GETパラメータのイッヌIDが違います。マイページへ遷移します。');
  // マイページへ遷移
  header("Location:mypage.php");
  exit;
}
debug('イッヌID：'.$d_id);
debug('フォーム用DBデータ：'.print_r($dbFormData,true));
debug('カテゴリーデータ：'.print_r($dbCategoryData,true));
debug('ソートデータ：'.print_r($dbSortData,true));

// POST送信時処理
//================================
// POST送信された場合
if(!empty($_POST)){
  debug('POST送信があります。');
  debug('POST情報：'.print_r($_POST,true));
  debug('FILE情報：'.print_r($_FILES,true));

  // 「POST送信されたイッヌ情報」を代入。キーをname属性に設定
  $name = $_POST['name'];
  $category = $_POST['category_id'];
  $dogage = $_POST['dogage'];
  $comment = $_POST['comment'];
  // 画像をアップロードし、パスを格納
  $pic1 = ( !empty($_FILES['pic1']['name']) ) ? uploadImg($_FILES['pic1'],'pic1') : '';
  // 画像をPOSTしてないが、既にDBに登録されている場合、DBのパスを入れる（POSTはフォームに反映されないので）
  $pic1 = ( empty($pic1) && !empty($dbFormData['pic1']) ) ? $dbFormData['pic1'] : $pic1;
  $pic2 = ( !empty($_FILES['pic2']['name']) ) ? uploadImg($_FILES['pic2'],'pic2') : '';
  $pic2 = ( empty($pic2) && !empty($dbFormData['pic2']) ) ? $dbFormData['pic2'] : $pic2;
  $pic3 = ( !empty($_FILES['pic3']['name']) ) ? uploadImg($_FILES['pic3'],'pic3') : '';
  $pic3 = ( empty($pic3) && !empty($dbFormData['pic3']) ) ? $dbFormData['pic3'] : $pic3;

  // DBに情報が無く、新規入力の場合
  if(empty($dbFormData)){
    // 未入力チェック
    validRequired($name, 'name');
      if(empty($err_msg)){
      // 最大文字数チェック
      validMaxLen($name, 'name');
      }
    // セレクトボックスチェック
    validSelect($category, 'category_id');
    // 最大文字数チェック
    validMaxLen($comment, 'comment', 200);
    //未入力チェック
    validRequired($dogage, 'dogage');
      if(empty($err_msg)){
      //半角数字チェック
      validNumber($dogage, 'dogage');
      }
    //未入力チェック
    validRequired($pic1, 'pic1');

  // 更新する場合
  }else{
    // DBの情報と、POSTした内容が異なる場合にバリデーションを行う
    if($dbFormData['name'] !== $name){
    // 未入力チェック
    validRequired($name, 'name');
      if(empty($err_msg)){
      //最大文字数チェック
      validMaxLen($name, 'name');
      }
    }
    // DBの情報と、POSTした内容が異なる場合にバリデーションを行う
    if($dbFormData['category_id'] !== $category){
    // セレクトボックスチェック
    validSelect($category, 'category_id'); // エラーメッセージ（define('MSG01','入力必須です');）
    }
    // DBの情報と、POSTした内容が異なる場合にバリデーションを行う
    if($dbFormData['comment'] !== $comment){
    // 最大文字数チェック
    validMaxLen($comment, 'comment', 200);
    }
    // DBの情報と、POSTした内容が異なる場合にバリデーションを行う（DBデータはstring型で送られて来るので、キャスト）
    if((int)$dbFormData['dogage'] !== $dogage){
    // 未入力チェック
    validRequired($dogage, 'dogage');
      if(empty($err_msg)){
      //半角数字チェック
      validNumber($dogage, 'dogage');
      }
    }
    // DBの情報と、POSTした内容が異なる場合にバリデーションを行う
    if($dbFormData['pic1'] !== $pic1){
    // 未入力チェック
    validRequired($pic1, 'pic1');
    }
  }

  // バリデーションOKであれば、DB接続
  if(empty($err_msg)){
    debug('バリデーションOKです。');

    try {
      $dbh = dbConnect();
      // 編集画面の場合は、dogsテーブルのレコードを更新
      if($edit_flg){
        debug('DB更新です。');
        $sql = 'UPDATE dogs SET name = :name, category_id = :category, dogage = :dogage, comment = :comment, pic1 = :pic1, pic2 = :pic2, pic3 = :pic3 WHERE user_id = :u_id AND id = :d_id';
        // プレースホルダに値を割り当て
        $data = array(':name' => $name , ':category' => $category, ':dogage' => $dogage, ':comment' => $comment, ':pic1' => $pic1, ':pic2' => $pic2, ':pic3' => $pic3, ':u_id' => $_SESSION['user_id'], ':d_id' => $d_id);
      // 新規登録画面の場合は、dogsテーブルにレコードを挿入
      }else{
        debug('DB新規登録です。');
        $sql = 'INSERT INTO dogs (name, category_id, dogage, comment, pic1, pic2, pic3, user_id, create_date ) values (:name, :category, :dogage, :comment,  :pic1, :pic2, :pic3, :u_id, :date)';
        // プレースホルダに値を割り当て
        $data = array(':name' => $name , ':category' => $category, ':dogage' => $dogage, ':comment' => $comment, ':pic1' => $pic1, ':pic2' => $pic2, ':pic3' => $pic3, ':u_id' => $_SESSION['user_id'], ':date' => date('Y-m-d H:i:s'));
      }
      debug('SQL：'.$sql);
      debug('プレースホルダに割り当てたデータ：'.print_r($data,true));
      // クエリ実行
      $stmt = queryPost($dbh, $sql, $data);

      // クエリ成功の場合
      if($stmt){
        $_SESSION['msg_success'] = SUC04; // 成功メッセージ（define('SUC04', '登録しました');）
        debug('マイページへ遷移します。');
        //マイページへ遷移
        header("Location:mypage.php");
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
  $siteTitle = (!$edit_flg) ? 'イッヌ登録' : 'イッヌ編集';
  require('head.php');
?>

  <body class="page-registDog page-2colum page-signined">
    <style>
      @media screen and (max-width: 767px) {
        .dragdrop {
          font-size: 10px;
      }
    }
    </style>

    <!-- ヘッダー -->
    <?php
      require('header.php');
    ?>

    <!-- メインコンテンツ -->
    <div id="contents" class="site-width">
      <h1 class="page-title"><?php echo (!$edit_flg) ? 'イッヌを登録する' : 'イッヌを編集する'; ?></h1>

      <!-- Main -->
      <section id="main" >

        <div class="form-container">

          <form action="" method="post" class="form" enctype="multipart/form-data" style="width:100%;box-sizing:border-box;">
            <div class="area-msg">
              <?php
                // エラーメッセージ
                echo getErrMsg('common');
              ?>
            </div>
            <!-- イッヌ名入力フォーム -->
            <label class="<?php if(!empty($err_msg['name'])) echo 'err'; ?>">
              イッヌ名<span class="label-require">必須</span>
              <input type="text" name="name" value="<?php echo getFormData('name'); ?>">
            </label>
            <div class="area-msg">
              <?php
                // エラーメッセージ
                echo getErrMsg('name');
              ?>
            </div>
            <!-- カテゴリー選択セレクトボックス -->
            <label class="<?php if(!empty($err_msg['category_id'])) echo 'err'; ?>">
              イッヌの種類<span class="label-require">必須</span>
              <select name="category_id">
                <option>選んでね！</option>
                <?php
                  foreach($dbCategoryData as $key => $val){
                ?>
                  <option value="<?php echo $val['id'] ?>" <?php if(getFormData('category_id') == $val['id'] ){ echo 'selected'; } ?> >
                    <?php echo $val['name']; ?>
                  </option>
                <?php
                  }
                ?>
              </select>
            </label>
            <div class="area-msg">
              <?php
                // エラーメッセージ
                echo getErrMsg('category_id');
              ?>
            </div>
            <!-- 詳細入力フォーム -->
            <label class="<?php if(!empty($err_msg['comment'])) echo 'err'; ?>">
              詳細
              <textarea name="comment" id="js-count" cols="30" rows="10" style="height:150px;"><?php echo getFormData('comment'); ?></textarea>
            </label>
            <p class="counter-text"><span id="js-count-view">0</span>/200文字</p>
            <div class="area-msg">
              <?php
                // エラーメッセージ
                echo getErrMsg('comment');
              ?>
            </div>
            <!-- 年れい入力フォーム -->
            <label style="text-align:left;" class="<?php if(!empty($err_msg['dogage'])) echo 'err'; ?>">
              年れい<span class="label-require">必須</span>
              <div class="form-group">
                <input type="number" name="dogage" style="width:150px" placeholder="2" value="<?php echo getFormData('dogage'); ?>"><span class="option">才</span>
              </div>
            </label>
            <div class="area-msg">
              <?php
                // エラーメッセージ
                echo getErrMsg('dogage');
              ?>
            </div>
            <!-- イッヌ画像投稿フォーム -->
            <div style="overflow:hidden;">
              <div class="imgDrop-container">
                画像1<span class="label-require">必須</span>
                <label class="drop-area <?php if(!empty($err_msg['pic1'])) echo 'err'; ?>">
                  <input type="hidden" name="MAX_FILE_SIZE" value="3145728">
                  <input type="file" name="pic1" class="input-file">
                  <img src="<?php echo getFormData('pic1'); ?>" alt="" class="prev-img" style="<?php if(empty(getFormData('pic1'))) echo 'display:none;' ?>">
                    <p class="dragdrop">ドラッグ＆ドロップ</p>
                </label>
                <div class="area-msg">
                  <?php
                    // エラーメッセージ
                    echo getErrMsg('pic1');
                  ?>
                </div>
              </div>
              <div class="imgDrop-container">
                画像２
                <label class="drop-area <?php if(!empty($err_msg['pic2'])) echo 'err'; ?>">
                  <input type="hidden" name="MAX_FILE_SIZE" value="3145728">
                  <input type="file" name="pic2" class="input-file">
                  <img src="<?php echo getFormData('pic2'); ?>" alt="" class="prev-img" style="<?php if(empty(getFormData('pic2'))) echo 'display:none;' ?>">
                    <p class="dragdrop">ドラッグ＆ドロップ</p>
                </label>
                <div class="area-msg">
                  <?php
                    // エラーメッセージ
                    echo getErrMsg('pic2');
                  ?>
                </div>
              </div>
              <div class="imgDrop-container">
                画像３
                <label class="drop-area <?php if(!empty($err_msg['pic3'])) echo 'err'; ?>">
                  <input type="hidden" name="MAX_FILE_SIZE" value="3145728">
                  <input type="file" name="pic3" class="input-file">
                  <img src="<?php echo getFormData('pic3'); ?>" alt="" class="prev-img" style="<?php if(empty(getFormData('pic3'))) echo 'display:none;' ?>">
                    <p class="dragdrop">ドラッグ＆ドロップ</p>
                </label>
                <div class="area-msg">
                  <?php
                    // エラーメッセージ
                    echo getErrMsg('pic3');
                  ?>
                </div>
              </div>
            </div>
            <!-- 送信ボタン -->
            <div class="btn-container">
              <input type="submit" class="btn btn-mid" value="<?php echo (!$edit_flg) ? '登録する' : '更新する'; ?>">
            </div>
            <?php
              // 編集画面の場合、イッヌ登録削除ページへの遷移ボタンを表示
              if(!empty($edit_flg)){
            ?>
              <a href="registDogDel.php<?php echo '?d_id='.$d_id; ?>">
                <div class="btn-container">
                  <input class="btn btn-mid registdel" value="登録の削除へ">
                </div>
              </a>
            <?php
              }
            ?>
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
