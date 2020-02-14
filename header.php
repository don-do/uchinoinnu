<header>
  <div class="site-width">
    <!-- サイトロゴ -->
    <a href="index.php"><img src="img/uchinoinnu-logo.png" alt="ウチのイッヌ" class="img-logo"></a>
      <!-- SPハンバーガーメニュー -->
      <div class="menu-trigger js-toggle-sp-menu">
        <span></span>
        <span></span>
        <span></span>
      </div>
    <nav class="nav-menu js-toggle-sp-menu-target">
      <ul class="menu">
        <?php
          // 未サインインの場合のメニュー
          if(empty($_SESSION['user_id'])){
        ?>
            <li class="menu-item"><a href="signup.php" class="btn btn-primary menu-linkUser">ユーザー登録</a></li>
            <li class="menu-item"><a href="signin.php" class="menu-linkSign" >サインイン</a></li>
        <?php
          }else{
          // サインイン済みの場合のメニュー
        ?>
            <li class="menu-item"><a href="mypage.php" class="menu-linkUser" >マイページ</a></li>
            <li class="menu-item"><a href="signout.php" class="menu-linkSign" >サインアウト</a></li>
        <?php
          }
        ?>
      </ul>
    </nav>
  </div>
</header>