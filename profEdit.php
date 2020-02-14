<?php

// 共通変数・関数ファイルを読込み
require('function.php');

debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debug('「　プロフィール編集ページ　');
debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debugLogStart();

// サインイン認証
require('auth.php');

//================================
// プロフィール編集画面処理
//================================
// DBからユーザーデータを取得
$dbFormData = getUser($_SESSION['user_id']);
debug('取得したユーザー情報：'.print_r($dbFormData,true));

// POST送信された場合
if(!empty($_POST)){
  debug('POST送信があります。');
  debug('POST情報：'.print_r($_POST,true));
  debug('FILE情報：'.print_r($_FILES,true));

  // 「POST送信されたユーザー情報」を代入。キーをname属性に設定
  $username = $_POST['username'];
  $tel = (!empty($_POST['tel'])) ? $_POST['tel'] : 0; // 空で送信されてきたら0を入れる。後続のバリデーションにひっかからないようにするため
  $zip = (!empty($_POST['zip'])) ? $_POST['zip'] : 0; // 空で送信されてきたら0を入れる。後続のバリデーションにひっかからないようにするため
  $addr = $_POST['addr'];
  $age = (!empty($_POST['age'])) ? $_POST['age'] : 0; // 空で送信されてきたら0を入れる。後続のバリデーションにひっかからないようにするため
  $email = $_POST['email'];
  // 画像をアップロードし、パスを格納
  $pic = ( !empty($_FILES['pic']['name']) ) ? uploadImg($_FILES['pic'],'pic') : '';
  // 画像をPOSTしてないが、既にDBに登録されている場合、DBのパスを入れる（DBの登録内容は、POSTに反映されないので。DBに無ければPOSTした内容を反映）
  $pic = ( empty($pic) && !empty($dbFormData['pic']) ) ? $dbFormData['pic'] : $pic;

  // DBの情報と、POSTした内容が異なる場合にバリデーションを行う
  if($dbFormData['username'] !== $username){
    // 名前の最大文字数チェック
    validMaxLen($username, 'username');
  }
  // DBの情報と、POSTした内容が異なる場合にバリデーションを行う（DBデータはstring型で送られて来るので、キャスト）
  if((int)$dbFormData['tel'] !== $tel){
    // 初期値に0が入っている場合があるので、0以外が入っていたら正規表現にて判定
    if($tel !== 0){
    // TEL形式チェック
    validTel($tel, 'tel');
    }
  }
  // DBの情報と、POSTした内容が異なる場合にバリデーションを行う
  if($dbFormData['addr'] !== $addr){
    //住所の最大文字数チェック
    validMaxLen($addr, 'addr');
  }
  // DBの情報と、POSTした内容が異なる場合にバリデーションを行う（DBデータはstring型で送られて来るので、キャスト）
  if( (int)$dbFormData['zip'] !== $zip){
    // 初期値に0が入っている場合があるので、0以外が入っていたら正規表現にて判定
    if($zip !== 0){
    // 郵便番号形式チェック
    validZip($zip, 'zip');
    }
  }
  // DBの情報と、POSTした内容が異なる場合にバリデーションを行う（DBデータはstring型で送られて来るので、キャスト）
  if((int)$dbFormData['age'] !== $age){
    // 年齢の最大数チェック
    validMaxAge($age, 'age');
    // validNumberの正規表現の判定は、0-9まで判定できる。「0以外が入っていたら正規表現にて判定」とする必要がない（ただ、空文字は引っかかるので、年齢に0を入れている）
    // 年齢の半角数字チェック
    validNumber($age, 'age');
  }
  // DBの情報と、POSTした内容が異なる場合にバリデーションを行う
  if($dbFormData['email'] !== $email){
    // emailの未入力チェック
    validRequired($email, 'email');

    // 未入力チェックOKであれば、後続のバリデーション
    if(empty($err_msg)){
      // emailの形式チェック
      validEmail($email, 'email');
      // emailの最大文字数チェック
      validMaxLen($email, 'email');
      // email重複チェックはDB接続負荷があるため、形式チェック・最大文字数チェックでエラーが無い場合にチェック
      if(empty($err_msg['email'])){
      //emailの重複チェック
      validEmailDup($email);
      }
    }
  }

  // バリデーションOKであれば、DB接続
  if(empty($err_msg)){
    debug('バリデーションOKです。');

    try {
      $dbh = dbConnect();
      // usersテーブルの、レコードを更新
      $sql = 'UPDATE users SET username = :u_name, age = :age, tel = :tel, zip = :zip, addr = :addr, email = :email, pic = :pic WHERE id = :u_id';
      // プレースホルダに値を割り当て
      $data = array(':u_name' => $username , ':age' => $age, ':tel' => $tel, ':zip' => $zip, ':addr' => $addr, ':email' => $email, ':pic' => $pic, ':u_id' => $dbFormData['id']);
      // クエリ実行
      $stmt = queryPost($dbh, $sql, $data);

      // クエリ成功の場合
      if($stmt){
        $_SESSION['msg_success'] = SUC02; // 成功メッセージ（define('SUC02', 'プロフィールを変更しました');）
        debug('マイページへ遷移します。');
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
debug('画面表示処理終了 <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<');
?>

<!-- head -->
<?php
  $siteTitle = 'プロフィール編集';
  require('head.php');
?>

  <body class="page-profEdit page-2colum page-signined">

  <!-- ヘッダー -->
  <?php
    require('header.php');
  ?>

  <!-- メインコンテンツ -->
  <div id="contents" class="site-width">
    <h1 class="page-title">プロフィール編集</h1>

    <!-- Main -->
    <section id="main" >

      <div class="form-container">

        <form action="" method="post" class="form" enctype="multipart/form-data">
          <div class="area-msg">
            <?php
              // エラーメッセージ
              echo getErrMsg('common');
            ?>
          </div>
          <!-- 名前入力フォーム -->
          <label class="<?php if(!empty($err_msg['username'])) echo 'err'; ?>">
            名前
            <input type="text" name="username" value="<?php echo getFormData('username'); ?>">
          </label>
          <div class="area-msg">
            <?php
              // エラーメッセージ
              echo getErrMsg('username');
            ?>
          </div>
          <!-- TEL入力フォーム -->
          <label class="<?php if(!empty($err_msg['tel'])) echo 'err'; ?>">
            TEL<span style="font-size:12px;margin-left:5px;">※ハイフン無しでご入力ください</span>
            <input type="text" name="tel" value="<?php // 初期値に0が入るため、if( !empty(getFormData('tel')) ){で、DBの情報が0以外であれば表示
                                                 if( !empty(getFormData('tel')) ){ echo getFormData('tel'); } ?>">
          </label>
          <div class="area-msg">
            <?php
              // エラーメッセージ
              echo getErrMsg('tel');
            ?>
          </div>
          <!-- 郵便番号入力フォーム -->
          <label class="<?php if(!empty($err_msg['zip'])) echo 'err'; ?>">
            郵便番号<span style="font-size:12px;margin-left:5px;">※ハイフン無しでご入力ください</span>
            <input type="text" name="zip" value="<?php // 初期値に0が入るため、if( !empty(getFormData('zip')) ){で、DBの情報が0以外であれば表示
                                                 if( !empty(getFormData('zip')) ){ echo getFormData('zip'); } ?>">
          </label>
          <div class="area-msg">
            <?php
              // エラーメッセージ
              echo getErrMsg('zip');
            ?>
          </div>
          <!-- 住所入力フォーム -->
          <label class="<?php if(!empty($err_msg['addr'])) echo 'err'; ?>">
            住所
            <input type="text" name="addr" value="<?php echo getFormData('addr'); ?>">
          </label>
          <div class="area-msg">
            <?php
              // エラーメッセージ
              echo getErrMsg('addr');
            ?>
          </div>
          <!-- 年齢入力フォーム -->
          <label style="text-align:left;" class="<?php if(!empty($err_msg['age'])) echo 'err'; ?>">
            年齢
            <input type="number" name="age" value="<?php // 初期値に0が入るため、if( !empty(getFormData('age')) ){で、DBの情報が0以外であれば表示
                                                   if( !empty(getFormData('age')) ){ echo getFormData('age'); } ?>">
          </label>
          <div class="area-msg">
            <?php
              // エラーメッセージ
              echo getErrMsg('age');
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
          <!-- プロフィール画像投稿フォーム -->
          プロフィール画像
          <label class="drop-area <?php if(!empty($err_msg['pic'])) echo 'err'; ?>" style="height:250px;line-height:250px;">
            <input type="hidden" name="MAX_FILE_SIZE" value="3145728">
            <input type="file" name="pic" class="input-file" style="height:370px;">
            <img src="<?php echo getFormData('pic'); ?>" alt="" class="prev-img" style="<?php if(empty(getFormData('pic'))) echo 'display:none;' ?>">
              ドラッグ＆ドロップ
          </label>
          <div class="area-msg">
            <?php
              // エラーメッセージ
              echo getErrMsg('pic');
            ?>
          </div>
          <!-- 送信ボタン -->
          <div class="btn-container">
            <input type="submit" class="btn btn-mid" value="変更する">
          </div>
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
