<?php
/**
 *  添加文章采集
 * 
 * @author        GoldHan.zhao <326196998@qq.com>
 * @copyright     Copyright (c) 2014-2016. All rights reserved.
 */

class PostCreateAction extends CAction
{	
	public function run(){        
        $model = new SpiderSetting();        
        if ( isset( $_POST['SpiderSetting'] ) ) {
            $this->_startSpider($_POST['SpiderSetting']);
            exit;
        }
        $criteria = new CDbCriteria();
        $criteria->addColumnCondition(array('type' => $this->controller->_type_ids['post']));
        $criteria->addCondition('total_page > cur_page');
        //可以采集的站点
        $settings = $model->findAll($criteria);
        $sites = array('0' => '==请选择站点==');
        if($settings) {
            foreach($settings as $v) {
                $sites[$v->id] = $v->site;
            }
        } else {
            $this->controller->message('error', Yii::t('admin', 'No Enable Site Data'));
        }                 
        $this->controller->render( 'postcreate', array ( 'model' => $model , 'sites' => $sites) );
	}
    
    /**
     * 开始采集
     * 
     * @param array $data
     */
    private function _startSpider($data = array())
    {        
        Yii::import('admin.extensions.simple_html_dom',true);
        set_time_limit(3600);
        echo "<style>"
                . "body{ "
                . "font-family:Monaco, DejaVu Sans Mono, Bitstream Vera Sans Mono, Consolas, Courier New, monospace; "
                . "font-size:14px; "
                . "line-height:1.8em; "
                . "background-color:#000000; "
                . "padding:20px;"
                . "color:#FFFFFF;}"
                . "</style>";        
        $site_id = $data['id'];
        $site = SpiderSetting::model()->findByPk($site_id);
        if(!$site) {
            $this->_stopError('无效的采集站点');
        }
        echo "<br/>--------采集第{$site->cur_page}页[start]--------";
        //默认是第一页
        if ($site->cur_page <= 0) {
            $url = $site->url;
            $page = 1;
        } else {
            $page_rule = $site->page_rule;
            $reg = '/\[PAGE_NUM]/is';
            preg_match($reg, $page_rule, $matches);
            if (!$matches || !$matches[0]) {
                $this->_stopError('页码规则无法解析');
            }
            $page = '0' . intval($site->cur_page + 1);
            $url = preg_replace($reg, $page, $page_rule);
        }
        try {
            $html = file_get_html($url);
        } catch (Exception $e) {
            $this->_stopError('站点地址有误！无法采集数据！'.$e->getMessage());
        }
        if(!$html) {
            $this->_stopError('站点地址有误！无法采集数据！');            
        }
        $lists = $html->find($site->item_rule_li);
        if(!$lists) {
            $this->_stopError('列表项Li标签选择器规则有误！匹配不到列表数据！');
        }        
        foreach ($lists as $item) {
            $postListModel = new SpiderPostList();
            $postContentModel = new SpiderPostContent();        
            ob_flush();
            flush();
            //匹配标题
            $a = $item->find($site->item_rule_a, 0);
            if(!$a) {
                $this->_stopError('列表项A标签选择器规则有误！匹配不到列表项数据！');
            }
            $exist = $postListModel->find('url = "' .$a->href. '"');
            if ($exist) {                
                continue;
            }
            $postListModel->attributes = array(
                'site_id' => $site->id,
                'url' => $a->href,
                'title' => $site->list_charset != 'UTF-8' ? mb_convert_encoding($a->innertext, 'UTF-8', $site->list_charset) : $a->innertext,
                'status'=> SpiderPostList::STATUS_NONE_C
            );
            if(!$postListModel->save()) {
                $this->_stopError('数据保存失败:'.var_export($postListModel->getErrors(),true));
            }
            $list_id = $postListModel->id;
            //匹配内容
            $html = file_get_html($a->href);
            if(!$html) {
                continue;
            }
            $getContent = $html->find($site->content_rule, 0);
            if(!$getContent) {
                $this->_stopError('详情页标签选择器规则有误！匹配不到内容数据！');
            }
            $content = addslashes($getContent->innertext);
            $cdata = array(
                'list_id'   => $list_id,
                'content'   => $site->content_charset != 'UTF-8' ? mb_convert_encoding($content, 'UTF-8', $site->content_charset) : $content
            );            
            $exist_c = $postContentModel->find('list_id='.$list_id);
            if($exist_c) {
                $exist_c->content = $cdata['content'];
                $save_content = $exist_c->save();                
            } else {
                $postContentModel->attributes = $cdata;
                $save_content = $postContentModel->save();
            }
            
            if(!$save_content) {
                $this->_stopError('数据保存失败:'.var_export($postContentModel->getErrors(),true));
            }  
            $postListModel->status = SpiderPostList::STATUS_C;
            if(!$postListModel->save()) {
                $this->_stopError('数据保存失败:'.var_export($postListModel->getErrors(),true));
            }            
            exit("<br/>采集 <span style='color:grey'>\"{$postListModel->title}\"</span> 完成.");
        }
        //更新页数
        $old_cur_page = $site->cur_page;
        $site->cur_page = $page;
        $site->save();
        echo "<br/>--------采集第{$old_cur_page}页完成[end]--------<br/>";
        if($site->cur_page < $site->total_page) {
            $this->_startSpider($data);
        } else {
            echo "<br/>--------<span style='color:green'>全部采集完成</span>--------<br/>";        
        }
    }
    
    /**
     * 中断提示
     * 
     * @param string $error
     */
    private function _stopError($error = '')
    {
        echo "<br/><span style='color:red'>[Error]</span>{$error}";
        echo "<br/>--------部分采集完成--------";
        exit;
    }
}