<footer id="footer">
  Copyright <a href="http://localhost:8888/uchinoinnu/index.php">ウチのイッヌ</a>. All Rights Reserved.
</footer>

<script src="js/vendor/jquery-3.4.1.min.js"></script>
<script>
  $(function(){

    // フッターを最下部に固定
    var $ftr = $('#footer');
    if( window.innerHeight > $ftr.offset().top + $ftr.outerHeight() ){
      $ftr.attr({'style': 'position:fixed; top:' + (window.innerHeight - $ftr.outerHeight()) +'px;' });
    }
    // POST送信完了時などに、メッセージを表示
    var $jsShowMsg = $('#js-show-msg');
    var msg = $jsShowMsg.text();
    if(msg.replace(/^[\s　]+|[\s　]+$/g, "").length){
      $jsShowMsg.slideToggle('slow');
      setTimeout(function(){ $jsShowMsg.slideToggle('slow'); }, 5000);
    }
    
    // 画像ライブプレビュー
    var $areaDrop = $('.drop-area');
    var $fileInput = $('.input-file');
    // ドラッグオーバーしたとき
    $areaDrop.on('dragover', function(e){
      e.stopPropagation();
      e.preventDefault();
      $(this).css('border', '3px #ccc dashed');
    });
    // ドラッグして離れたとき
    $areaDrop.on('dragleave', function(e){
      e.stopPropagation();
      e.preventDefault();
      $(this).css('border', 'none');
    });
    // 画像をドロップしたとき
    $fileInput.on('change', function(e){
      // ドラッグオーバーしたときに表示されるborderを消す
      $areaDrop.css('border', 'none');

      var file = this.files[0], // files配列の[0]で、ドラッグ&ドロップしたinput要素の画像ファイルの情報を取得
          $img = $(this).siblings('.prev-img'), // $fileInputをjQueryの形式にすることで、siblings()を使用できる。input要素の兄弟要素である、.prev-imgの付いたimg要素を、jQueryオブジェクトとして取得
          fileReader = new FileReader();   // ファイルを読み込むための、FileReaderオブジェクトを生成

      // 画像ファイルの情報の読み込みが完了したあとのイベントを設定
      fileReader.onload = function(event) {// 画像ファイルの情報が引数eventに入ってくる
        // 画像ファイルの情報を、img要素のsrc属性に設定（input要素の兄弟要素）。display:none;で非表示となっているimgタグを.show()で表示
        $img.attr('src', event.target.result).show();
      };

      // 画像ファイルの情報を、DataURLに変換し文字列として扱えるようにする
      fileReader.readAsDataURL(file);

    });
    
    // テキストエリアカウント
    var $countUp = $('#js-count'),
        $countView = $('#js-count-view');
    // keyupされたあとのイベントを設定
    $countUp.on('keyup', function(e){
      // カウント表示部分のhtmlを書き換え。$countUp（テキストエリア部分）をjQueryの形式にすることで、.val()を使用できる。中身の文字列を取得し、文字列の長さ・文字数を取得し、htmlを書き換える
      $countView.html($(this).val().length);
    });
    
    // 画像切替
    var $switchImgSubs = $('.js-switch-img-sub'),
        $switchImgMain = $('#js-switch-img-main');
    $switchImgSubs.on('click',function(e){
      $switchImgMain.attr('src',$(this).attr('src'));
    });
    
    // お気に入り登録・削除
    var $recommend,
        recommendDogId;
    $recommend = $('.js-click-recommend') || null; // null値にて、変数の中身が空と明示（undefinedが入らないようにする）
    // dogDetail.phpのdata属性・キーdogidから、イッヌのIDを取得
    recommendDogId = $recommend.data('dogid') || null;
    // !== null を判定することで、0もtrueとする。数値の0がfalseと判定されてしまわないため。例外的ではあるが、dogsテーブルのidカラム（イッヌのid）が0の場合の想定をしたため、undefinedとnullの両方を判定した
    if(recommendDogId !== undefined && recommendDogId !== null){
      $recommend.on('click',function(){
        var $this = $(this);
        $.ajax({
          type: "POST",
          url: "ajaxRecommend.php", // 送信先ファイル
          data: { dogId : recommendDogId} // キーdogId : 値recommendDogId(イッヌのid)
        }).done(function( data ){
//          console.log('Ajax Success'); // 開発・動作確認用コード
          $this.toggleClass('active'); // お気に入りの色を変える
        }).fail(function( msg ) {
//          console.log('Ajax Error'); // 開発・動作確認用コード
        });
      });
    }

    // SPメニュー
    $('.js-toggle-sp-menu').on('click', function () {
      $(this).toggleClass('active');
      $('.js-toggle-sp-menu-target').toggleClass('active');
    });

  });
</script>
</body>
</html>