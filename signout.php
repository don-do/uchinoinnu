<?php
//共通変数・関数ファイルを読込み
require('function.php');

debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debug('「　サインアウトページ　');
debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debugLogStart();

debug('サインアウトします。');

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

debug('サインインページへ遷移します。');
// サインインページへ遷移
header("Location:signin.php");
exit;