<?php
//================================
// ログ
//================================
// ログを取る
//ini_set('log_errors','on');
// ログの出力ファイルをphp.logに指定
//ini_set('error_log','php.log');

//================================
// デバッグ
//================================
// デバッグフラグ（ネットにアップする際はfalseに。ユーザーが操作するたびにログに吐き出されてしまわないようにする）
$debug_flg = false;
// デバッグログ関数
function debug($str){
  global $debug_flg;
  if(!empty($debug_flg)){
    error_log('デバッグ：'.$str);
  }
}

//================================
// セッション準備・セッション有効期限を延ばす
//================================
// セッションファイルの置き場を変更（/var/tmp/以下に置くことで30日は削除されなくなる。デフォルトでは24分で削除）
session_save_path("/var/tmp/");
// gcが削除する、セッションの有効期限を設定（30日以上経っているものだけ、100分の1の確率で削除。確率は変更してしまうとDB負荷がかかるので、デフォルトのまま）
ini_set('session.gc_maxlifetime', 60*60*24*30);
// クッキー自体の有効期限を30日に延ばす。ブラウザを閉じても削除されないようにするため
ini_set('session.cookie_lifetime', 60*60*24*30);
// セッションを使う
session_start();
// セキュリティ対策。現在のセッションIDを、新しく生成したセッションIDと置き換え（セッションIDを知った悪意あるユーザーによるなりすましを防止）
session_regenerate_id();

//================================
// 画面表示処理開始ログ吐き出し関数
//================================
function debugLogStart(){
  debug('>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> 画面表示処理開始');
  debug('セッションID：'.session_id());
  debug('セッション変数の中身：'.print_r($_SESSION,true));
  debug('現在日時タイムスタンプ：'.time());
  // サインイン日時があり、サインイン有効期限がある場合
  if(!empty($_SESSION['signin_date']) && !empty($_SESSION['signin_limit'])){
    debug( 'サインイン期限日時タイムスタンプ：'.( $_SESSION['signin_date'] + $_SESSION['signin_limit'] ) );
// 参考：
// 以下のようにデバッグされる（特にセッション変数の中身の$_SESSIONは、Arrayの形となることに注意）
// [24-Aug-2019 11:12:33 Asia/Tokyo] デバッグ：>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> 画面表示処理開始
// [24-Aug-2019 11:12:33 Asia/Tokyo] デバッグ：セッションID：kbmgcm6q245fpcaijd1ag5ufkl
// [24-Aug-2019 11:12:33 Asia/Tokyo] デバッグ：セッション変数の中身：Array
// (
//     [signin_date] => 1566612736
//     [signin_limit] => 3600
//     [user_id] => 1
// )
//
// [24-Aug-2019 11:12:33 Asia/Tokyo] デバッグ：現在日時タイムスタンプ：1566612753
// [24-Aug-2019 11:12:33 Asia/Tokyo] デバッグ：サインイン期限日時タイムスタンプ：1566616336
  }
}

//================================
// 定数
//================================
// エラーメッセージ・成功メッセージを定数に設定
define('MSG01','入力必須です');
define('MSG02','Emailの形式で入力してください');
define('MSG03','パスワード（再入力）が合っていません');
define('MSG04','半角英数字のみご利用いただけます');
define('MSG05','6文字以上で入力してください');
define('MSG06','200文字以内で入力してください');
define('MSG07','エラーが発生しました。しばらく経ってからやり直してください。');
define('MSG08','そのEmailは既に登録されています');
define('MSG09','メールアドレスまたはパスワードが違います');
define('MSG10','電話番号の形式が違います');
define('MSG11','郵便番号の形式が違います');
define('MSG12','古いパスワードが違います');
define('MSG13','古いパスワードと同じです');
define('MSG14','文字で入力してください');
define('MSG15','正しくありません');
define('MSG16','有効期限が切れています');
define('MSG17','半角数字のみご利用いただけます');
define('MSG18','120才以内で入力してください');
define('SUC01', 'パスワードを変更しました');
define('SUC02', 'プロフィールを変更しました');
define('SUC03', 'メールを送信しました');
define('SUC04', '登録しました');
define('SUC05', '楽しくお話ししてみよう！');

//================================
// グローバル変数
//================================
// エラーメッセージ格納用の配列
$err_msg = array();

//================================
// バリデーション関数
//================================

// バリデーション関数（未入力チェック）
function validRequired($str, $key){
  // 空文字の場合（数値の0はOK。年齢（年れい）フォームなどを考慮）
  if($str === ''){
    global $err_msg;
    $err_msg[$key] = MSG01; // エラーメッセージ（define('MSG01','入力必須です');）
  }
}
//バリデーション関数（Email形式チェック）
function validEmail($str, $key){
  // Emailの形式（簡易的な正規表現）でない場合
  if(!preg_match("/^([a-zA-Z0-9])+([a-zA-Z0-9\._-])*@([a-zA-Z0-9_-])+([a-zA-Z0-9\._-]+)+$/", $str)){
    global $err_msg;
    $err_msg[$key] = MSG02; // エラーメッセージ（define('MSG02','Emailの形式で入力してください');）
  }
}
//バリデーション関数（Email重複チェック）
function validEmailDup($email){
  global $err_msg;
  try {
    $dbh = dbConnect();
    // usersテーブルから、レコードの数を取得
    $sql = 'SELECT count(*) FROM users WHERE email = :email AND delete_flg = 0';
    // プレースホルダに値を割り当て
    $data = array(':email' => $email);
    // クエリ実行
    $stmt = queryPost($dbh, $sql, $data);
    // クエリ結果の値を取得
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    // 配列の1つ目だけを取り出して、結果があるかどうかを判定
    if(!empty(array_shift($result))){
      $err_msg['email'] = MSG08; // エラーメッセージ（define('MSG08','そのEmailは既に登録されています');）
    }
  } catch (Exception $e) {
    error_log('エラー発生:'.$e->getMessage());
    $err_msg['common'] = MSG07; // エラーメッセージ（define('MSG07','エラーが発生しました。しばらく経ってからやり直してください。');）
  }
}
// バリデーション関数（同値チェック）
function validMatch($str1, $str2, $key){
  if($str1 !== $str2){
    global $err_msg;
    $err_msg[$key] = MSG03; // エラーメッセージ（define('MSG03','パスワード（再入力）が合っていません');）
  }
}
// バリデーション関数（最小文字数チェック）
function validMinLen($str, $key, $min = 6){
  if(mb_strlen($str) < $min){
    global $err_msg;
    $err_msg[$key] = MSG05; // エラーメッセージ（define('MSG05','6文字以上で入力してください');）
  }
}
// バリデーション関数（最大文字数チェック）
function validMaxLen($str, $key, $max = 200){
  if(mb_strlen($str) > $max){
    global $err_msg;
    $err_msg[$key] = MSG06; // エラーメッセージ（define('MSG06','200文字以内で入力してください');）
  }
}
// バリデーション関数（最大年齢チェック）
function validMaxAge($str, $key, $max = 121){
  if($str > $max){
    global $err_msg;
    $err_msg[$key] = MSG18; // エラーメッセージ（define('MSG18','120才以内で入力してください');）
  }
}
// バリデーション関数（半角英数字チェック）
function validHalf($str, $key){
  // 半角英数字でない場合
  if(!preg_match("/^[a-zA-Z0-9]+$/", $str)){
    global $err_msg;
    $err_msg[$key] = MSG04; // エラーメッセージ（define('MSG04','半角英数字のみご利用いただけます');）
  }
}
// バリデーション関数（電話番号形式チェック）
function validTel($str, $key){
  // ハイフン無しで、10桁か11桁でない場合
  if(!preg_match("/^(0{1}\d{9,10})$/", $str)){
    global $err_msg;
    $err_msg[$key] = MSG10; // エラーメッセージ（define('MSG10','電話番号の形式が違います');）
  }
}
// バリデーション関数（郵便番号形式チェック）
function validZip($str, $key){
  // ハイフン無しで、7桁でない場合
  if(!preg_match("/^\d{7}$/", $str)){
    global $err_msg;
    $err_msg[$key] = MSG11; // エラーメッセージ（define('MSG11','郵便番号の形式が違います');）
  }
}
// バリデーション関数（半角数字チェック）
function validNumber($str, $key){
  // 半角数字でない場合
  if(!preg_match("/^[0-9]+$/", $str)){
    global $err_msg;
    $err_msg[$key] = MSG17; // エラーメッセージ（define('MSG17','半角数字のみご利用いただけます');）
  }
}
// バリデーション関数（固定長チェック）
function validLength($str, $key, $len = 8){
  if( mb_strlen($str) !== $len ){
    global $err_msg;
    $err_msg[$key] = $len . MSG14; // エラーメッセージ（define('MSG14','文字で入力してください');）
  }
}
// バリデーション関数（パスワードチェック）
function validPass($str, $key){
  // 半角英数字チェック
  validHalf($str, $key);
  // 最大文字数チェック
  validMaxLen($str, $key);
  // 最小文字数チェック
  validMinLen($str, $key);
}
// バリデーション関数（selectboxチェック）
function validSelect($str, $key){
  // 半角数字でない場合（selectboxをチェックせず、未入力の場合）
  if(!preg_match("/^[0-9]+$/", $str)){
    global $err_msg;
    $err_msg[$key] = MSG01; // エラーメッセージ（define('MSG01','入力必須です');）
  }
}
// エラーメッセージ表示関数
function getErrMsg($key){
  global $err_msg;
  if(!empty($err_msg[$key])){
    return $err_msg[$key];
  }
}

//================================
// サインイン認証
//================================
function isSignin(){
  // サインインしている場合
  if( !empty($_SESSION['signin_date']) ){
    debug('サインイン済みユーザーです。');

    // 最終サインイン日時＋有効期限を、現在日時が超えていた場合
    if( ($_SESSION['signin_date'] + $_SESSION['signin_limit']) < time()){
      debug('サインイン有効期限オーバーです。');

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
      return false;

    // 最終サインイン日時＋有効期限より、現在日時が少ない場合
    }else{
      debug('サインイン有効期限以内です。');
      return true;
    }

  // サインインしていない場合
  }else{
    debug('未サインインユーザーです。');
    return false;
  }
}

//================================
// データベース
//================================
// DB接続関数
function dbConnect(){
  // DBへの接続準備
  // データベースで使用する文字コードを指定
  $dsn = 'mysql:dbname=xxxxxxxx;host=localhost;charset=utf8';
  $user = 'xxxxxxxx';
  $password = 'xxxxxxxx';
  $options = array(
    // SQL実行失敗時には例外をスロー
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    // デフォルトの動的プレースホルダを使用。静的プレースホルダでは、getMyMsgsAndboard()でエラーになる。複文SQL・同名プレースホルダのため。
    // PDO::ATTR_EMULATE_PREPARES => false,
    // デフォルトフェッチモードを連想配列形式に設定
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    // バッファクエリを使用(一度に結果セットをすべて取得し、サーバー負荷を軽減)
    // SELECTで得た結果に対しても、常にPDOStatement::rowCountメソッドを使えるようになる（直近のSQLステートメントによって作用した、行数を返す）
    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
  );
  // PDOオブジェクト生成（DBへ接続）
  $dbh = new PDO($dsn, $user, $password, $options);
  return $dbh;
}
function queryPost($dbh, $sql, $data){
  // クエリー作成
  $stmt = $dbh->prepare($sql);
  // プレースホルダに値を割り当て、SQL文を実行
  if(!$stmt->execute($data)){
    debug('クエリに失敗しました。');
    debug('失敗したSQL：'.print_r($stmt,true));
    $err_msg['common'] = MSG07; // エラーメッセージ（define('MSG07','エラーが発生しました。しばらく経ってからやり直してください。');）
    return 0;
  }
  debug('クエリ成功。');
  return $stmt;
}
function getUser($u_id){
  debug('ユーザー情報を取得します。');
  try {
    $dbh = dbConnect();
    // usersテーブルから、レコードを取得
    $sql = 'SELECT id, username, age, tel, zip, addr, email, password, signin_time, pic, delete_flg, create_date, update_date FROM users WHERE id = :u_id AND delete_flg = 0';
    // プレースホルダ割り当て
    $data = array(':u_id' => $u_id);
    // クエリ実行
    $stmt = queryPost($dbh, $sql, $data);

    // クエリ結果のデータを、１レコード返却
    if($stmt){
      return $stmt->fetch(PDO::FETCH_ASSOC);
    // クエリ成功しなかった場合
    }else{
      return false;
    }

  } catch (Exception $e) {
    error_log('エラー発生:'.$e->getMessage());
  }
}
function getDog($u_id, $d_id){
  debug('イッヌ情報を取得します。');
  debug('ユーザーID：'.$u_id);
  debug('イッヌID：'.$d_id);
  try {
    $dbh = dbConnect();
    // dogsテーブルから、レコードを取得
    $sql = 'SELECT  id, name, category_id, comment, dogage, pic1, pic2, pic3, user_id, delete_flg, create_date, update_date FROM dogs WHERE user_id = :u_id AND id = :d_id AND delete_flg = 0';
    // プレースホルダに値を割り当て
    $data = array(':u_id' => $u_id, ':d_id' => $d_id);
    // クエリ実行
    $stmt = queryPost($dbh, $sql, $data);

    if($stmt){
      // クエリ結果のデータを、１レコード返却
      return $stmt->fetch(PDO::FETCH_ASSOC);
    }else{
      return false;
    }

  } catch (Exception $e) {
    error_log('エラー発生:'.$e->getMessage());
  }
}
function getDogList($currentMinNum = 1, $category, $sort, $span = 10){
  debug('イッヌ情報を取得します。');
  try {
    $dbh = dbConnect();

    // 総レコード数・総ページ数を作成
    // dogsテーブルから、（カテゴリーに合う・ソート順に合う）idレコードを取得
    $sql = 'SELECT id FROM dogs WHERE delete_flg = 0';
    if(!empty($category)) $sql .= ' AND category_id = '.$category;
    if(!empty($sort)){
      switch($sort){
        case 1:
          $sql .= ' ORDER BY dogage ASC'; // 若年イッヌから並べる
          break;
        case 2:
          $sql .= ' ORDER BY dogage DESC'; // 年配イッヌから並べる
          break;
      }
    }
    // プレースホルダへの値の割り当て無し
    $data = array();
    // クエリ実行
    $stmt = queryPost($dbh, $sql, $data);
    $rst['total'] = $stmt->rowCount(); //総レコード数
    $rst['total_page'] = ceil($rst['total']/$span); //総ページ数
    if(!$stmt){
      return false;
    }

    // ページング時の、トップ画面上部の表示件数・トップ画面内コンテンツを作成
    // dogsテーブルから、（カテゴリーに合う・ソート順に合う）全レコードを取得
    $sql = 'SELECT id, name, category_id, comment, dogage, pic1, pic2, pic3, user_id, delete_flg, create_date, update_date FROM dogs WHERE delete_flg = 0';
    if(!empty($category)) $sql .= ' AND category_id ='.$category;
    if(!empty($sort)){
      switch($sort){
        case 1:
          $sql .= ' ORDER BY dogage ASC'; // 若年イッヌから並べる
          break;
        case 2:
          $sql .= ' ORDER BY dogage DESC'; // 年配イッヌから並べる
          break;
      }
    }
    $sql .= ' LIMIT :span OFFSET :currentMinNum'; // 10までの値。最小の値を始点とする
    debug('getDogListページング時のSQL：'.$sql);
    // クエリ実行
    $stmt = $dbh->prepare($sql);
    // プレースホルダに値を割り当て
    $stmt->bindValue(':span', (int)$span, PDO::PARAM_INT);
    $stmt->bindValue(':currentMinNum', (int)$currentMinNum, PDO::PARAM_INT);
    // $stmt実行
    if($stmt->execute()){
      // クエリ結果のデータを、全レコード格納
      $rst['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
      return $rst;
    }else{
      return false;
    }

  } catch (Exception $e) {
    error_log('エラー発生:'.$e->getMessage());
  }
}
function getDogOne($d_id){
  debug('イッヌ情報を取得します。');
  debug('イッヌID：'.$d_id);
  try {
    $dbh = dbConnect();
    // dogsテーブルを基に、categoryテーブルを外部結合し、レコードを取得
    $sql = 'SELECT d.id, d.name, d.comment, d.dogage, d.pic1, d.pic2, d.pic3, d.user_id, d.create_date, d.update_date, c.name AS category FROM dogs AS d LEFT JOIN category AS c ON d.category_id = c.id WHERE d.id = :d_id AND d.delete_flg = 0 AND c.delete_flg = 0';
    // プレースホルダに値を割り当て
    $data = array(':d_id' => $d_id);
    // クエリ実行
    $stmt = queryPost($dbh, $sql, $data);

    if($stmt){
      // クエリ結果のデータを、１レコード返却
      return $stmt->fetch(PDO::FETCH_ASSOC);
    }else{
      return false;
    }

  } catch (Exception $e) {
    error_log('エラー発生:'.$e->getMessage());
  }
}
function getMydogs($u_id){
  debug('自分のイッヌ情報を取得します。');
  debug('ユーザーID：'.$u_id);
  try {
    $dbh = dbConnect();
    // dogsテーブルから、レコードを取得
    $sql = 'SELECT id, name, category_id, comment, dogage, pic1, pic2, pic3, user_id, delete_flg, create_date, update_date FROM dogs WHERE user_id = :u_id AND delete_flg = 0';
    // プレースホルダに値を割り当て
    $data = array(':u_id' => $u_id);
    // クエリ実行
    $stmt = queryPost($dbh, $sql, $data);

    if($stmt){
      // クエリ結果のデータを、全レコード返却
      return $stmt->fetchAll();
    }else{
      return false;
    }

  } catch (Exception $e) {
    error_log('エラー発生:'.$e->getMessage());
  }
}
function getMsgsAndboard($id){
  debug('msg情報を取得します。');
  debug('掲示板ID：'.$id);
  try {
    $dbh = dbConnect();
    // boardテーブルから、レコードを取得
    $sql = 'SELECT id, comment_user, read_user, dog_id, delete_flg, create_date, update_date FROM board WHERE id = :id';
    // プレースホルダに値を割り当て
    $data = array(':id' => $id);
    // クエリ実行
    $stmt = queryPost($dbh, $sql, $data);
    $rst = $stmt->fetch(PDO::FETCH_ASSOC);
    debug ('掲示板テーブルから取得したdbデータ:' .print_r($rst,true));
    $delete_flg = $rst['delete_flg'];
    debug ('掲示板テーブルのdelete-flg:' .print_r($delete_flg,true));

    // 掲示板があれば、メッセージを取得
    if(!empty($rst) && (int)$delete_flg === 0){
      debug ('掲示板情報あり。メッセージ取得');
      // msgテーブルから、レコードを取得（古いメッセージを先頭に）
      $sql = 'SELECT id, board_id, send_date, to_user, from_user, msg, delete_flg, create_date, update_date FROM message WHERE board_id = :id AND delete_flg = 0 ORDER BY send_date ASC';
      // プレースホルダに値を割り当て
      $data = array(':id' => $rst['id']);
      //クエリ実行
      $stmt = queryPost($dbh, $sql, $data);
      $rst['msg'] = $stmt->fetchAll();
    // 掲示板が無ければ、1を返す
    }elseif((int)$delete_flg === 1){
      debug ('掲示板情報なし。1をリターン');
      return 1;
    }
    if($stmt){
      // クエリ結果の全データを返却
      return $rst;
    }else{
      return false;
    }

  } catch (Exception $e) {
    error_log('エラー発生:'.$e->getMessage());
  }
}
function getMyMsgsAndboard($u_id){
  debug('自分のmsg情報を取得します。');
  try {
    $dbh = dbConnect();
    // boardテーブルから、レコードを取得
    $sql = 'SELECT id, comment_user, read_user, dog_id, delete_flg, create_date, update_date FROM board AS b WHERE b.comment_user = :id OR b.read_user = :id AND b.delete_flg = 0';
    // プレースホルダに値を割り当て
    $data = array(':id' => $u_id);
    // クエリ実行
    $stmt = queryPost($dbh, $sql, $data);
    $rst = $stmt->fetchAll();
    debug('getMyMsgsAndboardの掲示板ID：'.print_r($rst,true));
    if(!empty($rst)){
      foreach($rst as $key => $val){
        // messageテーブルから、レコードを取得
        $sql = 'SELECT id, board_id, send_date, to_user, from_user, msg, delete_flg, create_date, update_date FROM message WHERE board_id = :id AND delete_flg = 0 ORDER BY send_date DESC';
        // プレースホルダに値を割り当て
        $data = array(':id' => $val['id']);
        // クエリ実行
        $stmt = queryPost($dbh, $sql, $data);
        $rst[$key]['msg'] = $stmt->fetchAll();
      }
    }

    if($stmt){
      // クエリ結果の、全データを返却
      return $rst;
    }else{
      return false;
    }

  } catch (Exception $e) {
    error_log('エラー発生:'.$e->getMessage());
  }
}
function getBoard($d_id){
  debug('該当イッヌの掲示板を取得します。');
  debug('イッヌID：'.$d_id);
  try {
    $dbh = dbConnect();
    // boardテーブルから、レコードを取得
    $sql = 'SELECT id, comment_user, read_user, dog_id, delete_flg, create_date, update_date FROM board WHERE dog_id = :d_id AND delete_flg = 0';
    // プレースホルダに値を割り当て
    $data = array(':d_id' => $d_id);
    // クエリ実行
    $stmt = queryPost($dbh, $sql, $data);
    $rst = $stmt->fetchAll();

    if($stmt){
      // クエリ結果の、全データを返却
      return $rst;
    }else{
      return false;
    }

  } catch (Exception $e) {
    error_log('エラー発生:'.$e->getMessage());
  }
}
function getCategory(){
  debug('カテゴリー情報を取得します。');
  try {
    $dbh = dbConnect();
    // categoryテーブルから、レコードを取得
    $sql = 'SELECT id, name, delete_flg, create_date, update_date FROM category';
    // プレースホルダなし
    $data = array();
    // クエリ実行
    $stmt = queryPost($dbh, $sql, $data);

    if($stmt){
      // クエリ結果の、全データを返却
      return $stmt->fetchAll();
    }else{
      return false;
    }

  } catch (Exception $e) {
    error_log('エラー発生:'.$e->getMessage());
  }
}
function getSort(){
  debug('ソート情報を取得します。');
  try {
    $dbh = dbConnect();
    // sortテーブルから、レコードを取得
    $sql = 'SELECT id, name, delete_flg, create_date, update_date FROM sort';
    // プレースホルダなし
    $data = array();
    // クエリ実行
    $stmt = queryPost($dbh, $sql, $data);

    if($stmt){
      // クエリ結果の、全データを返却
      return $stmt->fetchAll();
    }else{
      return false;
    }

  } catch (Exception $e) {
    error_log('エラー発生:'.$e->getMessage());
  }
}
function isRecommend($u_id, $d_id){
  debug('お気に入り情報があるか確認します。');
  debug('ユーザーID：'.$u_id);
  debug('イッヌID：'.$d_id);
  try {
    $dbh = dbConnect();
    // recommendテーブルから、レコードを取得（個々に取得しようとすると、「Column 'カラム名' in field list is ambiguous」エラーとなるため、「*」で取得）
    $sql = 'SELECT * FROM recommend WHERE dog_id = :d_id AND user_id = :u_id';
    // プレースホルダに値を割り当て
    $data = array(':u_id' => $u_id, ':d_id' => $d_id);
    // クエリ実行
    $stmt = queryPost($dbh, $sql, $data);

    if($stmt->rowCount()){
      debug('お気に入りです');
      return true;
    }else{
      debug('特に気に入ってません');
      return false;
    }

  } catch (Exception $e) {
    error_log('エラー発生:'.$e->getMessage());
  }
}
function getMyRecommend($u_id){
  debug('自分のお気に入り情報を取得します。');
  debug('ユーザーID：'.$u_id);
  try {
    $dbh = dbConnect();
    // recommendテーブルを基に、dogsテーブルを外部結合し、レコードを取得（個々に取得しようとすると、「Column 'カラム名' in field list is ambiguous」エラーとなるため、「*」で取得）
    $sql = 'SELECT * FROM recommend AS r LEFT JOIN dogs AS d ON r.dog_id = d.id WHERE r.user_id = :u_id';
    // プレースホルダに値を割り当て
    $data = array(':u_id' => $u_id);
    // クエリ実行
    $stmt = queryPost($dbh, $sql, $data);

    if($stmt){
      // クエリ結果の全データを返却
      return $stmt->fetchAll();
    }else{
      return false;
    }

  } catch (Exception $e) {
    error_log('エラー発生:'.$e->getMessage());
  }
}

//================================
// メール送信
//================================
function sendMail($from, $to, $subject, $comment){
  if(!empty($to) && !empty($subject) && !empty($comment)){
    // 文字化け防止の設定（お決まりパターン）
    mb_language("Japanese"); // 現在使っている言語を設定
    mb_internal_encoding("UTF-8"); // 内部の日本語のエンコーディング方式を設定

    // メールを送信（送信結果はtrueかfalseで返ってくる）
    $result = mb_send_mail($to, $subject, $comment, "From: ".$from);
    // 送信結果を判定
    if ($result) {
      debug('メールを送信しました。');
    } else {
      debug('【エラー発生】メールの送信に失敗しました。');
    }
  }
}

//================================
// その他
//================================
// サニタイズ
function sanitize($str){
  return htmlspecialchars($str,ENT_QUOTES);
}
// フォーム入力保持
function getFormData($str, $flg = false){
  if($flg){
    $method = $_GET;
  }else{
    $method = $_POST;
  }
  global $dbFormData;
  global $err_msg;
  // ユーザーデータがある場合
  if(!empty($dbFormData)){
    // フォームのエラーがある場合
    if(!empty($err_msg[$str])){
      // POSTにデータがある場合
      if(isset($method[$str])){
        return sanitize($method[$str]);
      // POSTにデータがない場合（エラーがあるはずなので、基本ありえないが。）、DBの情報を表示
      }else{
        return sanitize($dbFormData[$str]);
      }
    // ユーザーデータがあるがフォームのエラーが無く、POSTにデータがあり、DBの情報と違う場合
    }else{
      if(isset($method[$str]) && $method[$str] !== $dbFormData[$str]){
        return sanitize($method[$str]);
      // ユーザーデータがあるがフォームのエラーが無く、POSTにデータが無かったり、POSTデータはDBの情報と同じだったりする場合
      }else{
        return sanitize($dbFormData[$str]);
      }
    }
  // ユーザーデータが無い場合
  }else{
    if(isset($method[$str])){
      return sanitize($method[$str]);
    }
  }
}
// sessionを１回だけ取得できる
function getSessionFlash($key){
  if(!empty($_SESSION[$key])){
    $data = $_SESSION[$key];
    $_SESSION[$key] = '';
    return $data;
  }
}
// 認証キー生成
function makeRandKey($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJLKMNOPQRSTUVWXYZ0123456789';
    $str = '';
    for ($i = 0; $i < $length; ++$i) {
        $str .= $chars[mt_rand(0, 61)];
    }
    return $str;
}
// 画像処理
function uploadImg($file, $key){
  debug('画像アップロード処理開始');
  debug('FILE情報：'.print_r($file,true));

  if(isset($file['error']) && is_int($file['error'])){
    try {
      // バリデーション
      //「UPLOAD_ERR_OK」などの定数は、phpでファイルアップロード時に自動的に定義される
      // 定数には値として0や1などの数値が入っている
      switch($file['error']){
          case UPLOAD_ERR_OK: // OK
              break;
          case UPLOAD_ERR_NO_FILE: // ファイル未選択の場合
              throw new RuntimeException('ファイルが選択されていません');
          case UPLOAD_ERR_INI_SIZE: // php.ini定義の、最大サイズが超過した場合
              throw new RuntimeException('ファイルサイズが大きすぎます(php.ini定義の最大サイズを超過)');
          case UPLOAD_ERR_FORM_SIZE: // フォーム定義の、最大サイズ超過した場合
              throw new RuntimeException('ファイルサイズが大きすぎます(フォーム定義の最大サイズを超過)');
          default: // その他の場合
              throw new RuntimeException('その他のエラーが発生しました');
      }
      // $fileの中の値は、ブラウザ側で偽装可能なので、MIMEタイプを自前でチェック
      $type = @exif_imagetype($file['tmp_name']); // exif_imagetype関数は「IMAGETYPE_GIF」「IMAGETYPE_JPEG」などの定数を返す
      // MIMEタイプがいずれかに該当するか。第三引数には、必ずtrueを設定。厳密にチェックしてくれる
      if(!in_array($type, [IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG], true)){
          throw new RuntimeException('画像形式が未対応です');
      }
      // ファイルデータからSHA-1ハッシュを使ってファイル名を決定し、ファイルを保存（同じ名前のファイルがアップロードされても、区別できるように）
      // image_type_to_extension関数で、ファイルの拡張子を取得
      $path = 'uploads/'.sha1_file($file['tmp_name']).image_type_to_extension($type);
      if (!move_uploaded_file($file['tmp_name'], $path)) { // ファイルを移動
          throw new RuntimeException('ファイル保存時にエラーが発生しました');
      }

      // 保存したファイルパスの、パーミッション（権限）を変更
      chmod($path, 0644);
      debug('ファイルは正常にアップロードされました');
      debug('ファイルパス：'.$path);
      return $path;

    } catch (RuntimeException $e) {
      debug($e->getMessage());
      global $err_msg;
      $err_msg[$key] = $e->getMessage();
    }
  }
}
//ページング
// $currentPageNum : 現在のページ数
// $totalPageNum : 総ページ数
// $link $link2 : 検索結果用GETパラメータリンク（カテゴリーとソートで使う）
// $pageColNum : ページネーション表示数
function pagination( $currentPageNum, $totalPageNum, $link = '', $link2 = '', $pageColNum = 5){
  // 現在のページが、総ページ数と同じ　かつ　総ページ数が表示項目数以上なら、左にリンク４個出す
  if( $currentPageNum == $totalPageNum && $totalPageNum > $pageColNum){
    $minPageNum = $currentPageNum - 4;
    $maxPageNum = $currentPageNum;
  // 現在のページが、総ページ数の１ページ前なら、左にリンク３個、右に１個出す
  }elseif( $currentPageNum == ($totalPageNum-1) && $totalPageNum > $pageColNum){
    $minPageNum = $currentPageNum - 3;
    $maxPageNum = $currentPageNum + 1;
  // 現ページが2の場合は左にリンク１個、右にリンク３個だす。
  }elseif( $currentPageNum == 2 && $totalPageNum > $pageColNum){
    $minPageNum = $currentPageNum - 1;
    $maxPageNum = $currentPageNum + 3;
  // 現ページが1の場合は左に何も出さない。右に５個出す。
  }elseif( $currentPageNum == 1 && $totalPageNum > $pageColNum){
    $minPageNum = $currentPageNum;
    $maxPageNum = 5;
  // 総ページ数が表示項目数より少ない場合は、総ページ数をループのMax、ループのMinを１に設定
  }elseif($totalPageNum < $pageColNum){
    $minPageNum = 1;
    $maxPageNum = $totalPageNum;
  // それ以外は左に２個出す。
  }else{
    $minPageNum = $currentPageNum - 2;
    $maxPageNum = $currentPageNum + 2;
  }

  echo '<div class="pagination">';
    echo '<ul class="pagination-list">';
      if($currentPageNum != 1){
        echo '<li class="list-item"><a href="?p=1'.$link.$link2.'">&lt;</a></li>';
      }
      for($i = $minPageNum; $i <= $maxPageNum; $i++){
        echo '<li class="list-item ';
        if($currentPageNum == $i ){ echo 'active'; }
        echo '"><a href="?p='.$i.$link.$link2.'">'.$i.'</a></li>';
      }
      if($currentPageNum != $maxPageNum && $maxPageNum > 1){
        echo '<li class="list-item"><a href="?p='.$maxPageNum.$link.$link2.'">&gt;</a></li>';
      }
    echo '</ul>';
  echo '</div>';
}
// 画像表示用関数
function showImg($path){
  if(empty($path)){
    return 'img/noimg.png';
  }else{
    return $path;
  }
}
// GETパラメータ付与
function appendGetParam($arr_del_key = array()){ // $arr_del_key : 付与から取り除きたいGETパラメータのキー
  if(!empty($_GET)){
    $str = '?';
    foreach($_GET as $key => $val){
      // 取り除きたいパラメータじゃない場合（取り除きたいパラメータは入って来れない）
      if(!in_array($key,$arr_del_key,true)){
        // urlにくっつけるパラメータを生成
        $str .= $key.'='.$val.'&';
      }
    }
    $str = mb_substr($str, 0, -1, "UTF-8");
    return $str;
  }
}