<?php
//================================
// サインイン認証（自動サインアウト）
//================================
// サインインしている場合
if( !empty($_SESSION['signin_date']) ){
  debug('サインイン済みユーザーです。');

  // 「最終サインイン日時＋有効期限」よりも、現在日時（UNIXタイムスタンプ）の方が多かった場合（期限過ぎ・自動サインアウト）
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
    // サインインページへ遷移
    header("Location:signin.php");
    return;

  // 「最終サインイン日時＋有効期限」より、現在日時（UNIXタイムスタンプ）が多く無かった場合（期限内）
  }else{
    debug('サインイン有効期限以内です。');
    // 最終サインイン日時を、現在日時に更新
    $_SESSION['signin_date'] = time();
    // $_SERVER['PHP_SELF']によって、ドメインからのパスを返す。たとえば「/uchinoinnu/signin.php」のように返ってくる
    // そしてbasename()でファイル名だけを取り出し、signin.phpと一致していた場合、以下の処理
    if(basename($_SERVER['PHP_SELF']) === 'signin.php'){
      // 現在実行中のスクリプトファイル名がsignin.phpと一致
      debug('マイページへ遷移します。');
      // マイページへ遷移
      header("Location:mypage.php");
      return;
    }
  }
// サインインしていない場合（サインインページへ）
}else{
  debug('未サインインユーザーです。');
  // $_SERVER['PHP_SELF']によって、ドメインからのパスを返す
  // そしてbasename()でファイル名だけを取り出し、signin.phpと一致していない場合
  // （現在実行中のスクリプトファイル名がsignin.phpでない場合）
  if(basename($_SERVER['PHP_SELF']) !== 'signin.php'){
    // サインインページへ遷移
    header("Location:signin.php");
    return;
  }
}