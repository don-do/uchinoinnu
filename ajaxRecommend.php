<?php
// 共通変数・関数ファイルを読込み
require('function.php');

debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debug('「　お気に入り(Ajax処理)　');
debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debugLogStart();

//================================
// お気に入り(Ajax処理)
//================================
// dogDetail.phpのdata属性（キーdogid）のあるiタグのクリックから、footer.phpのAjax処理へ
// footer.phpのAjax処理（キーdogId）にて、POST送信が渡って来る
//--------------------------------------------------------------------

// POST送信があり、ユーザーIDがあり、サインインしている場合
if(isset($_POST['dogId']) && isset($_SESSION['user_id']) && isSignin()){
  // キーdogIdの値は、イッヌのIdレコード。改修などで例外的に「0」が入る可能性も考慮し、isset()を使用。
  debug('POST送信があります。');
  $d_id = $_POST['dogId'];
  debug('イッヌID：'.$d_id);
  try {
    $dbh = dbConnect();
    // recommendテーブルから、レコードを取得（個々に取得しようとすると、「Column 'カラム名' in field list is ambiguous」エラーとなるため、「*」で取得）
    $sql = 'SELECT * FROM recommend WHERE dog_id = :d_id AND user_id = :u_id';
    // プレースホルダに値を割り当て
    $data = array(':u_id' => $_SESSION['user_id'], ':d_id' => $d_id);
    // クエリ実行
    $stmt = queryPost($dbh, $sql, $data);
    $resultCount = $stmt->rowCount();
    debug('お気に入り登録「0」なら未登録なので登録処理へ。それ以外は登録済みなので削除処理へ：'.$resultCount);
      // レコードが１件でもある場合
      if(!empty($resultCount)){
        // recommendテーブルから、レコードを削除
        $sql = 'DELETE FROM recommend WHERE dog_id = :d_id AND user_id = :u_id';
        // プレースホルダに値を割り当て
        $data = array(':u_id' => $_SESSION['user_id'], ':d_id' => $d_id);
        // クエリ実行
        $stmt = queryPost($dbh, $sql, $data);
      // レコードが１件も無い場合
      }else{
        // recommendテーブルに、レコードを挿入
        $sql = 'INSERT INTO recommend (dog_id, user_id, create_date) VALUES (:d_id, :u_id, :date)';
        // プレースホルダに値を割り当て
        $data = array(':u_id' => $_SESSION['user_id'], ':d_id' => $d_id, ':date' => date('Y-m-d H:i:s'));
        // クエリ実行
        $stmt = queryPost($dbh, $sql, $data);
      }

  } catch (Exception $e) {
    error_log('エラー発生:'.$e->getMessage());
  }
}
debug('お気に入り(Ajax処理)終了 <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<');
?>