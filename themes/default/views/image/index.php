    <link rel="stylesheet" type="text/css" href="<?php echo $this->_stylePath . '/css/list.css';?>" />
    <!-- 导航面包屑开始 -->
	<?php $this->renderPartial('/layouts/nav',array('navs'=>$navs));?>
	<!-- 导航面包屑结束 -->
	
	<div id="content" class="clear">
		<div class="content_left">
            <!-- 搜索 -->
            <?php $form = $this->beginWidget('CActiveForm', array( 'method'=>'get','action'=>$this->createUrl('image/index'), 'htmlOptions' => array('class' => 'search_form clear')));?>
            <dl class="category">
                <dt><?php echo Yii::t('common','Catagorys');?></dt>
                <dd>
                    <select class="cat_select">
                        <option value='0'>==所有==</option>
                        <?php foreach((array)$this->_catalog as $cate):?>
                        <option value="<?php echo $cate->id;?>"><?php echo $cate->catalog_name;?></option>		
                        <?php endforeach;?>
                    </select>                    
                    <input type="hidden" id="catalogId" name="catalog_id" value="<?php echo Yii::app()->request->getParam('catalogId') ?>"/>
                    <input type="hidden" name="order" value="<?php echo $order; ?>"/>
                    <span class="loading" style="display:none;">loading...</span>
                    <input type="submit" name="submit" class="search_btn" value="搜索"/>
                </dd>                
            </dl>            
		    <div class="order_box">
				<a <?php if($order == 'view_count'): ?>class="current" <?php endif;?> href="<?php echo $this->createUrl('post/index',array('order'=>'view_count', 'catalog_id'=>$catalog ? $catalog->id : 0));?>">热度排行</a> 
				<a <?php if($order == 'id'): ?>class="current" <?php endif;?> href="<?php echo $this->createUrl('post/index',array('order'=>'id', 'catalog_id' => $catalog ? $catalog->id : 0));?>">最新发表</a>                 
			</div>
            <?php $this->endWidget();?>
            
			<ul class="content_list">
			<?php foreach((array)$datalist as $post):?>
				<?php $post_tags = $post->tags?explode(',',$post->tags):array(); $tags_len = count($post_tags);?>	
				<li class="list_box clear">
					<div class="list_head">
						<a href="<?php echo $this->createUrl('post/index', array('catalog_id'=>$post->catalog->id));?>"><?php echo $post->catalog->catalog_name;?></a>									
					</div>
					<div class="list_body">
						<h2><a href="<?php echo $this->createUrl('image/view', array('id'=>$post->id));?>"><?php echo CHtml::encode($post->title);?></a></h2>
						<p class="view_info">
							<span><?php echo Yii::t('common','Copy From')?>：  <em><?php echo $post->copy_from?"<a href='".$post->copy_url."' target='_blank'>".$post->copy_from."</a>":Yii::t('common','System Manager');?></em></span>
							<?php if($tags_len > 0):?>
							<span class="tags">
								<?php $i = 1; foreach((array)$post_tags as $ptag):?>
								<em><a href="<?php echo $this->createUrl('tag/index',array('tag'=>$ptag));?>"><?php echo $ptag;?></a></em>
								<?php $i++;?>
								<?php endforeach;?>								
							</span>
							<?php endif;?>
							<span class="views fa">&nbsp;&nbsp;<em><?php echo $post->view_count;?></em></span>
						</p>
						<p class="content_info clear">
							<?php if(file_exists($post->attach_thumb)):?>
							<a class="content_cover" alt="<?php echo CHtml::encode($post->title);?>" title="<?php echo CHtml::encode($post->title);?>" href="<?php echo $this->createUrl('image/view', array('id'=>$post->id));?>"><img alt="<?php echo $post->tags;?>" src="<?php echo $post->attach_thumb;?>" /></a>
							<?php endif;?>								
							<?php echo $post->introduce?$post->introduce:'...';?>
						</p>
						<a href="<?php echo $this->createUrl('image/view', array('id'=>$post->id));?>" class="continue_read"><?php echo Yii::t('common','Read More');?></a>
					</div>
				</li>			
				<?php endforeach;?>
				
			
			<!-- 分页开始 -->
			<div id="page">	
				<?php $this->renderPartial('/layouts/pager',array('pagebar'=>$pagebar));?>		
			</div>
			<!-- 分页结束 -->
			
		</div>
		
		<!-- 右侧内容开始 -->
		<?php $this->renderPartial('right',array('last_images'=>$last_images));?>	
		<!-- 右侧内容结束 -->
		
	</div>	
    <script type="text/javascript">
    $(function(){
        $('.search_form').delegate('.cat_select','change',function(){
            var id = $(this).val();
            var url =  "<?php echo $this->createUrl('image/ajax');?>";
            var sel = $(this);
            var val = $(this).val();
            $(this).nextAll('.cat_select').remove();
            if(id <= 0) {
                return false;
            }
            $('.loading').show();
            $.getJSON(url, {'act':'catChildren', 'catalog_id':id}, function(data){                
                if(data && data.length > 0) {
                    var html = '<select class="cat_select">'
                        + '<option value="0">==<?php echo Yii::t('admin', 'Select Category'); ?>==</option>';                
                        $.each(data, function(i, item){                    
                            html += '<option value="'+item.id+'">'+item.catalog_name+'</option>';
                        });
                    $(sel).after(html);
                }
                $('.loading').hide();
            });
            $('#catalogId').val(val);            
        });
    });
</script>
