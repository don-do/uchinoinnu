<section id="sidebar" class="sidebar_index">

  <form name="" method="get">

    <!-- タイトル・カテゴリー検索 -->
    <h1 class="title">イッヌの種類</h1>
    <div class="selectbox">
      <span class="icn_select"></span>
      <select name="c_id" id="">
        <option value="0" <?php if(getFormData('c_id',true) == 0 ){ echo 'selected'; } ?> >選べるよ！</option>
        <?php
          foreach($dbCategoryData as $key => $val){
        ?>
          <option value="<?php echo $val['id'] ?>" <?php if(getFormData('c_id',true) == $val['id'] ){ echo 'selected'; } ?> >
            <?php echo $val['name']; ?>
          </option>
        <?php
          }
        ?>
      </select>
    </div>
    <!-- タイトル・年れい順並べ替え -->
    <h1 class="title">年れい順</h1>
    <div class="selectbox">
      <span class="icn_select"></span>
      <select name="sort">
        <option value="0" <?php if(getFormData('sort',true) == 0 ){ echo 'selected'; } ?> >並べ替えられるよ！</option>
        <?php
          foreach($dbSortData as $key => $val){
        ?>
          <option value="<?php echo $val['id'] ?>" <?php if(getFormData('sort',true) == $val['id'] ){ echo 'selected'; } ?> >
            <?php echo $val['name']; ?>
          </option>
        <?php
          }
        ?>
      </select>
    </div>
    <!-- 検索開始ボタン -->
    <input type="submit" value="けんさく！">

  </form>

</section>