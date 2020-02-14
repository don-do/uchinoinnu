<?php

//共通変数・関数ファイルを読込み
require('function.php');

debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debug('「　トップページ　');
debug('「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「「');
debugLogStart();

// サインイン認証なし（サインインしていない人も見られる画面なので）

//================================
// トップ画面処理
//================================

// 画面表示用データ取得
//================================
// GETパラメータを取得
//----------------------------------
// カレントページ
$currentPageNum = (!empty($_GET['p'])) ? $_GET['p'] : 1; // デフォルトは、１ページ目
// カテゴリー
$category = (!empty($_GET['c_id'])) ? $_GET['c_id'] : '';
// ソート
$sort = (!empty($_GET['sort'])) ? $_GET['sort'] : '';

// 表示するイッヌの件数を取得
//----------------------------------
// 表示件数
$listSpan = 10; // デフォルトは、20件表示
// 現在の表示レコード先頭を算出
$currentMinNum = (($currentPageNum-1)*$listSpan); // 1ページ目なら(1-1)*10 = 0 、 ２ページ目なら(2-1)*10 = 10

// DBデータを取得
//----------------------------------
// DBからイッヌデータを取得
$dbDogData = getDogList($currentMinNum, $category, $sort);
// DBからカテゴリーデータを取得
$dbCategoryData = getCategory();
// DBからソートデータを取得
$dbSortData = getSort();
debug('イッヌデータ：'.print_r($dbDogData,true));
debug('カテゴリーデータ：'.print_r($dbCategoryData,true));
debug('ソートデータ：'.print_r($dbSortData,true));

debug('画面表示処理終了 <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<');
?>

<!-- head -->
<?php
  $siteTitle = 'HOME';
  require('head.php');
?>

  <body class="page-top page-2colum">

    <!-- ヘッダー -->
    <?php
      require('header.php');
    ?>

    <!-- メインコンテンツ -->
    <div id="contents" class="site-width">

      <!-- Main -->
      <section id="main" >

      <?php
        // イッヌの登録が0件だった場合。画像表示・ページネーションなし
        if(empty($dbDogData['data'])){
      ?>
        <!-- 件数表示 -->
        <div class="search-title">
          <div class="search-left">
            イッヌは見つかりませんでした。
          </div>
          <br>
          <div class="search-right">
            <span class="num">0</span> - <span class="num">0</span>件 / <span class="num">0</span>件中
          </div>
        </div>

      <?php
        // イッヌの登録が1件以上あった場合
        }else{
      ?>
        <!-- 件数表示 -->
        <div class="search-title">
          <div class="search-left">
            <span class="total-num"><?php echo sanitize($dbDogData['total']); ?></span>件のイッヌが見つかりました
          </div>
          <br>
          <div class="search-right">
            <span class="num"><?php echo (!empty($dbDogData['data'])) ? $currentMinNum+1 : 0; ?></span> - <span class="num"><?php echo $currentMinNum+count($dbDogData['data']); ?></span>件 / <span class="num"><?php echo sanitize($dbDogData['total']); ?></span>件中
          </div>
        </div>
        <!-- 画像表示 -->
        <div class="panel-list">
          <?php
            foreach($dbDogData['data'] as $key => $val):
          ?>
            <a href="dogDetail.php<?php echo (!empty(appendGetParam())) ? appendGetParam().'&d_id='.$val['id'] : '?d_id='.$val['id']; ?>" class="panel">
              <div class="panel-head">
                <img src="<?php echo sanitize($val['pic1']); ?>" alt="<?php echo sanitize($val['name']); ?>">
              </div>
              <div class="panel-body">
                <p class="panel-title"><?php echo sanitize($val['name']); ?> <span class="dogage"><?php echo sanitize(number_format($val['dogage'])); ?> 才</span></p>
              </div>
            </a>
          <?php
            endforeach;
          ?>
        </div>
        <!-- ページネーション表示 -->
        <?php pagination($currentPageNum, $dbDogData['total_page'], '&c_id='.$category, '&sort='.$sort); ?>
      <?php
        // 件数・画像・ページネーション表示処理終了
        }
      ?>

      </section>

      <!-- サイドバー -->
      <?php
        require('sidebar_index.php');
      ?>
    </div>

    <!-- footer -->
    <?php
      require('footer.php');
    ?>
