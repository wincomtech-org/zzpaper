<?php

/**
 * ECSHOP 用户评论管理程序
 * ============================================================================
 * * 版权所有 2005-2016 上海商创网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecmoban.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: comment_manage.php 17217 2011-01-19 06:29:08Z liubo $
*/

define('IN_ECS', true);

require(dirname(__FILE__) . '/includes/init.php');

/* act操作项的初始化 */
if (empty($_REQUEST['act']))
{
    $_REQUEST['act'] = 'list';
}
else
{
    $_REQUEST['act'] = trim($_REQUEST['act']);
}

//ecmoban模板堂 --zhuo start
$adminru = get_admin_ru_id();
if($adminru['ru_id'] == 0){
    $smarty->assign('priv_ru',   1);
}else{
    $smarty->assign('priv_ru',   0);
} 	
//ecmoban模板堂 --zhuo end

/*------------------------------------------------------ */
//-- 获取没有回复的评论列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    /* 检查权限 */
    admin_priv('comment_priv');
    
    //商家单个权限 ecmoban模板堂 start
    $comment_edit_delete = get_merchants_permissions($_SESSION['admin_id'], 'comment_edit_delete');
    $smarty->assign('comment_edit_delete', $comment_edit_delete); //退换货权限
    //商家单个权限 ecmoban模板堂 end  
    
    $smarty->assign('ur_here',      $_LANG['05_comment_manage']);
    $smarty->assign('full_page',    1);

    $list = get_comment_list($adminru['ru_id']); 

    $smarty->assign('comment_list', $list['item']);
    $smarty->assign('filter',       $list['filter']);
    $smarty->assign('record_count', $list['record_count']);
    $smarty->assign('page_count',   $list['page_count']);

    $sort_flag  = sort_flag($list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    assign_query_info();
    $smarty->display('comment_list.htm');
}

//@author guan start
/*------------------------------------------------------ */
//-- 用户晒单列表
/*------------------------------------------------------ */

if($_REQUEST['act'] == 'single_list')
{
    /* 检查权限 */
    admin_priv('single_manage');
    
    require_once(ROOT_PATH . 'includes/lib_order.php');

    $smarty->assign('ur_here',      $_LANG['single_manage']);
    $smarty->assign('full_page',    1);

    //商家单个权限 ecmoban模板堂 start
    $single_edit_delete = get_merchants_permissions($_SESSION['admin_id'], 'single_edit_delete');
    $smarty->assign('single_edit_delete', $single_edit_delete); //退换货权限
    //商家单个权限 ecmoban模板堂 end  

    $list = get_single_list($adminru['ru_id']);

    $smarty->assign('single_list', $list['item']);
    $smarty->assign('filter',       $list['filter']);
    $smarty->assign('record_count', $list['record_count']);
    $smarty->assign('page_count',   $list['page_count']);

    $sort_flag  = sort_flag($list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    assign_query_info();
    $smarty->display('single_list.htm');
}
//@author guan end

/*------------------------------------------------------ */
//-- 翻页、搜索、排序
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'query')
{
    /* 检查权限 */
    admin_priv('comment_priv');
    
    $list = get_comment_list($adminru['ru_id']);

    $smarty->assign('comment_list', $list['item']);
    $smarty->assign('filter',       $list['filter']);
    $smarty->assign('record_count', $list['record_count']);
    $smarty->assign('page_count',   $list['page_count']);

    $sort_flag  = sort_flag($list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);
    
    //商家单个权限 ecmoban模板堂 start
    $comment_edit_delete = get_merchants_permissions($_SESSION['admin_id'], 'comment_edit_delete');
    $smarty->assign('comment_edit_delete', $comment_edit_delete); //退换货权限
    //商家单个权限 ecmoban模板堂 end  

    make_json_result($smarty->fetch('comment_list.htm'), '',
        array('filter' => $list['filter'], 'page_count' => $list['page_count']));
}

//@author guan start ajax请求晒单 start

if ($_REQUEST['act'] == 'single_query')
{
    /* 检查权限 */
    admin_priv('single_manage');
    
    $list = get_single_list($adminru['ru_id']);

    $smarty->assign('single_list', $list['item']);
    $smarty->assign('filter',       $list['filter']);
    $smarty->assign('record_count', $list['record_count']);
    $smarty->assign('page_count',   $list['page_count']);

    $sort_flag  = sort_flag($list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);
    
    //商家单个权限 ecmoban模板堂 start
    $single_edit_delete = get_merchants_permissions($_SESSION['admin_id'], 'single_edit_delete');
    $smarty->assign('single_edit_delete', $single_edit_delete); //退换货权限
    //商家单个权限 ecmoban模板堂 end  

    make_json_result($smarty->fetch('single_list.htm'), '',
    array('filter' => $list['filter'], 'page_count' => $list['page_count']));
}



/*------------------------------------------------------ */
//-- 回复用户晒单(同时查看晒单详情)
/*------------------------------------------------------ */
if ($_REQUEST['act']=='single_reply')
{
    /* 检查权限 */
    admin_priv('single_manage');

    $single_info = array();
    $reply_info   = array();
    $id_value     = array();

    /* 获取评论详细信息并进行字符处理 */
    $sql = $sql = "SELECT * FROM " .$ecs->table('single'). " WHERE single_id = '$_REQUEST[id]'";
    $single_info = $db->getRow($sql);
    $single_info['addtime'] = local_date($_CFG['time_format'], $single_info['addtime']);


    /* 获得图片 */
    $sql = $sql = "SELECT id, img_file, cont_desc FROM " .$ecs->table('single_sun_images'). " WHERE single_id = '$_REQUEST[id]' order by id DESC";
    $single_img = $db->getAll($sql);
    $img_list = array();
    foreach ($single_img as $key => $gallery_img)
    {
            $img_list[$key]['id'] = $gallery_img['id'];
            $img_list[$key]['img_file'] = $gallery_img['img_file'];
            $img_list[$key]['cont_desc'] = $gallery_img['cont_desc'];
    }
    /* 获取管理员的用户名和Email地址 */
    $sql = "SELECT user_name, email FROM ". $ecs->table('admin_user').
    " WHERE user_id = '$_SESSION[admin_id]'";
    $admin_info = $db->getRow($sql);

    /* 模板赋值 */
    $smarty->assign('msg',          $single_info); //评论信息
    $smarty->assign('single_img',          $img_list); //评论信息
    $smarty->assign('admin_info',   $admin_info);   //管理员信息

    $smarty->assign('send_fail',   !empty($_REQUEST['send_ok']));

    $smarty->assign('ur_here',      $_LANG['single_info']);
    $smarty->assign('action_link',  array('text' => $_LANG['single_manage'],
                    'href' => 'comment_manage.php?act=single_list'));
    
    //商家单个权限 ecmoban模板堂 start
    $single_edit_delete = get_merchants_permissions($_SESSION['admin_id'], 'single_edit_delete');
    $smarty->assign('single_edit_delete', $single_edit_delete); //退换货权限
    //商家单个权限 ecmoban模板堂 end  

    /* 页面显示 */
    assign_query_info();
    $smarty->display('single_info.htm');
}

/*------------------------------------------------------ */
//-- 删除图片 by guan
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'drop_single_image')
{
    check_authz_json('single_manage');

    $img_id = empty($_REQUEST['img_id']) ? 0 : intval($_REQUEST['img_id']);

    /* 删除图片文件 */
    $sql = "SELECT img_file  " . " FROM " . $GLOBALS['ecs']->table('single_sun_images') .
            " WHERE id = '$img_id'";
    $row = $GLOBALS['db']->getRow($sql);

    if ($row['img_file'] != '' && is_file('../' . $row['img_file']))
    {
        @unlink('../' . $row['img_file']);
    }

    /* 删除数据 */
    $sql = "DELETE FROM " . $GLOBALS['ecs']->table('single_sun_images') . " WHERE id = '$img_id'";
    $GLOBALS['db']->query($sql);

    clear_cache_files();
    make_json_result($img_id);
}


/*------------------------------------------------------ */
//-- 晒单状态为显示或者禁止
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'single_check')
{
    /* 检查权限 */
    admin_priv('single_manage');

    if ($_REQUEST['check'] == 'allow')
    {
            $sql = "UPDATE " .$ecs->table('order_goods'). " SET is_single = 2 WHERE order_id = '$_REQUEST[id]' AND goods_id=$_REQUEST[goods_id]";
            $db->query($sql);
            $sql = "UPDATE " .$ecs->table('single'). " SET is_audit = 1, integ='$_REQUEST[integ]' WHERE order_id = '$_REQUEST[id]'";
            $db->query($sql);


            if(!empty($_REQUEST['integ']))
            {
                    log_account_change($_REQUEST[user_id], $user_money = 0, $frozen_money = 0, $rank_points = 0, $pay_points = floatval($_REQUEST['integ']), $change_desc = '晒单奖励');
            }

            //add_feed($_REQUEST['id'], COMMENT_GOODS);

            /* 清除缓存 */
            clear_cache_files();

            ecs_header("Location: comment_manage.php?act=single_list\n");
            exit;
    }
    else
    {
            $sql = "UPDATE " .$ecs->table('order_goods'). " SET is_single = 3 WHERE order_id = '$_REQUEST[id]' AND goods_id=$_REQUEST[goods_id]";
            $db->query($sql);
            $sql = "UPDATE " .$ecs->table('single'). " SET is_audit = 0, integ='-$_REQUEST[integ]' WHERE order_id = '$_REQUEST[id]'";
            $db->query($sql);

            if(!empty($_REQUEST['integ']))
            {
                    log_account_change($_REQUEST[user_id], $user_money = 0, $frozen_money = 0, $rank_points = 0, $pay_points = floatval(-$_REQUEST['integ']), $change_desc = '晒单禁止扣除积分');
            }

            /* 清除缓存 */
            clear_cache_files();

            ecs_header("Location: comment_manage.php?act=single_list\n");
            exit;
    }
}
//@author guan end

/*------------------------------------------------------ */
//-- 回复用户评论(同时查看评论详情)
/*------------------------------------------------------ */
if ($_REQUEST['act']=='reply')
{
    /* 检查权限 */
    admin_priv('comment_priv');
    
    $comment_info = array();
    $reply_info   = array();
    $id_value     = array();

    /* 获取评论详细信息并进行字符处理 */
    $sql = "SELECT * FROM " .$ecs->table('comment'). " WHERE comment_id = '$_REQUEST[id]'";
    $comment_info = $db->getRow($sql);
    $comment_info['content']  = str_replace('\r\n', '<br />', htmlspecialchars($comment_info['content']));
    $comment_info['content']  = nl2br(str_replace('\n', '<br />', $comment_info['content']));
    $comment_info['add_time'] = local_date($_CFG['time_format'], $comment_info['add_time']);
    //晒单图片
    $sql = "SELECT img_thumb FROM ". $ecs->table('comment_img') ." WHERE comment_id = '$_REQUEST[id]'";
    $img_list = $db->getAll($sql);
    $comment_info['img_list'] = $img_list;
    /* 获得评论回复内容 */
    $sql = "SELECT * FROM ".$ecs->table('comment'). " WHERE parent_id = '$_REQUEST[id]' AND single_id = 0 AND dis_id = 0 and user_id=0";
    $reply_info = $db->getRow($sql);

    if (empty($reply_info))
    {
        $reply_info['content']  = '';
        $reply_info['add_time'] = '';
    }
    else
    {
        $reply_info['content']  = nl2br(htmlspecialchars($reply_info['content']));
        $reply_info['add_time'] = local_date($_CFG['time_format'], $reply_info['add_time']);
    }
    /* 获取管理员的用户名和Email地址 */
    $sql = "SELECT user_name, email FROM ". $ecs->table('admin_user').
           " WHERE user_id = '$_SESSION[admin_id]'";
    $admin_info = $db->getRow($sql);

    /* 取得评论的对象(文章或者商品) */
    if ($comment_info['comment_type'] == 0)
    {
        $sql = "SELECT goods_name FROM ".$ecs->table('goods').
               " WHERE goods_id = '$comment_info[id_value]'";
        $id_value = $db->getOne($sql);
    }
    else
    {
        $sql = "SELECT title FROM ".$ecs->table('article').
               " WHERE article_id='$comment_info[id_value]'";
        $id_value = $db->getOne($sql);
    }

    /* 模板赋值 */
    $smarty->assign('msg',          $comment_info); //评论信息
    $smarty->assign('admin_info',   $admin_info);   //管理员信息
    $smarty->assign('reply_info',   $reply_info);   //回复的内容
    $smarty->assign('id_value',     $id_value);  //评论的对象
    $smarty->assign('send_fail',   !empty($_REQUEST['send_ok']));

    $smarty->assign('ur_here',      $_LANG['comment_info']);
    $smarty->assign('action_link',  array('text' => $_LANG['05_comment_manage'],
    'href' => 'comment_manage.php?act=list'));

    /* 页面显示 */
    assign_query_info();
    $smarty->display('comment_info.htm');
}
/*------------------------------------------------------ */
//-- 处理 回复用户评论
/*------------------------------------------------------ */
if ($_REQUEST['act']=='action')
{
    /* 检查权限 */
    admin_priv('comment_priv');
    
    /* 获取IP地址 */
    $ip     = real_ip();
	
	$comment_info=$db->getRow("SELECT comment_id,ru_id FROM ".$ecs->table('comment')." WHERE comment_id = '$_REQUEST[comment_id]' and ru_id='".$adminru['ru_id']."'");

    /* 获得评论是否有回复 */
    $sql = "SELECT comment_id,content,parent_id,ru_id FROM ".$ecs->table('comment').
           " WHERE parent_id = '$comment_info[comment_id]' AND single_id = 0 AND dis_id = 0 and ru_id='".$comment_info['ru_id']."'";
    $reply_info = $db->getRow($sql);

    if (!empty($reply_info['content'])&&$adminru['ru_id']==$comment_info['ru_id'])
    {
        /* 更新回复的内容 */
        $sql = "UPDATE ".$ecs->table('comment')." SET ".
               "email     = '$_POST[email]', ".
               "user_name = '$_POST[user_name]', ".
               "content   = '$_POST[content]', ".
               "add_time  =  '" . gmtime() . "', ".
               "ip_address= '$ip', ".
               "status    = 0".
               " WHERE comment_id = '".$reply_info['comment_id']."'";
    }
    elseif($adminru['ru_id']==$comment_info['ru_id'])
    {
        /* 插入回复的评论内容 */
        $sql = "INSERT INTO ".$ecs->table('comment')." (comment_type, id_value, email, user_name , ".
                    "content, add_time, ip_address, status, parent_id,ru_id) ".
               "VALUES('$_POST[comment_type]', '$_POST[id_value]','$_POST[email]', " .
                    "'$_SESSION[admin_name]','$_POST[content]','" . gmtime() . "', '$ip', '0', '$_POST[comment_id]','$adminru[ru_id]')";
    }
	else
	{
		sys_msg($_LANG['priv_error']);	
	}
    $db->query($sql);

    /* 更新当前的评论状态为已回复并且可以显示此条评论 */
    $sql = "UPDATE " .$ecs->table('comment'). " SET status = 1 WHERE comment_id = '$_POST[comment_id]'";
    $db->query($sql);

    /* 邮件通知处理流程 */
    if (!empty($_POST['send_email_notice']) or isset($_POST['remail']))
    {
        //获取邮件中的必要内容
        $sql = 'SELECT user_name, email, content ' .
               'FROM ' .$ecs->table('comment') .
               " WHERE comment_id ='$_REQUEST[comment_id]'";
        $comment_info = $db->getRow($sql);

        /* 设置留言回复模板所需要的内容信息 */
        $template    = get_mail_template('recomment');

        $smarty->assign('user_name',   $comment_info['user_name']);
        $smarty->assign('recomment', $_POST['content']);
        $smarty->assign('comment', $comment_info['content']);
        $smarty->assign('shop_name',   "<a href='".$ecs->url()."'>" . $_CFG['shop_name'] . '</a>');
        $smarty->assign('send_date',   date('Y-m-d'));

        $content = $smarty->fetch('str:' . $template['template_content']);

        /* 发送邮件 */
        if (send_mail($comment_info['user_name'], $comment_info['email'], $template['template_subject'], $content, $template['is_html']))
        {
            $send_ok = 0;
        }
        else
        {
            $send_ok = 1;
        }
    }

    /* 清除缓存 */
    clear_cache_files();

    /* 记录管理员操作 */
    admin_log(addslashes($_LANG['reply']), 'edit', 'users_comment');

    ecs_header("Location: comment_manage.php?act=reply&id=$_REQUEST[comment_id]&send_ok=$send_ok\n");
    exit;
}
/*------------------------------------------------------ */
//-- 更新评论的状态为显示或者禁止
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'check')
{
    /* 检查权限 */
    admin_priv('comment_priv');
    
    if ($_REQUEST['check'] == 'allow')
    {
        /* 允许评论显示 */
        $sql = "UPDATE " .$ecs->table('comment'). " SET status = 1 WHERE comment_id = '$_REQUEST[id]'";
        $db->query($sql);
		
		$sql = 'SELECT id_value FROM '.$ecs->table('comment')." WHERE comment_id = '$_REQUEST[id]'";
		$goods_id = $db->getOne($sql);

		$sql = "SELECT COUNT(*) FROM ".$ecs->table('comment')." WHERE id_value = '$goods_id' AND comment_type = 0 AND status = 1 AND parent_id = 0 ";	
		$count = $db->getOne($sql);
	
	
		$sql = "UPDATE ".$ecs->table('goods'). " SET comments_number = '$count' WHERE goods_id = '$goods_id'";

		$db->query($sql);

        //add_feed($_REQUEST['id'], COMMENT_GOODS);

        /* 清除缓存 */
        clear_cache_files();

        ecs_header("Location: comment_manage.php?act=reply&id=$_REQUEST[id]\n");
        exit;
    }
    else
    {
        /* 禁止评论显示 */
        $sql = "UPDATE " .$ecs->table('comment'). " SET status = 0 WHERE comment_id = '$_REQUEST[id]'";
        $db->query($sql);
		
		$sql = 'SELECT id_value FROM '.$ecs->table('comment')." WHERE comment_id = '$_REQUEST[id]'";
		$goods_id = $db->getOne($sql);

		$sql = "SELECT COUNT(*) FROM ".$ecs->table('comment')." WHERE id_value = '$goods_id' AND comment_type = 0 AND status = 1 AND parent_id = 0 ";	
		$count = $db->getOne($sql);
	
	
		$sql = "UPDATE ".$ecs->table('goods'). " SET comments_number = '$count' WHERE goods_id = '$goods_id'";

		$db->query($sql);

        /* 清除缓存 */
        clear_cache_files();

        ecs_header("Location: comment_manage.php?act=reply&id=$_REQUEST[id]\n");
        exit;
    }
}

/*------------------------------------------------------ */
//-- 删除某一条晒单 @author guan
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'single_remove')
{

    check_authz_json('single_manage');

    $id = intval($_GET['id']);
    $sql = "SELECT order_id FROM " . $ecs->table('single') . " WHERE single_id = '$id'";
    $res = $db->getRow($sql);
    $order_id = $res['order_id'];
    $db->query("UPDATE " . $ecs->table('order_info') . " SET is_single='4'" . " WHERE order_id = '$order_id'");
    $sql = "DELETE FROM " .$ecs->table('single'). " WHERE single_id = '$id'";
    $res = $db->query($sql);
    if ($res)
    {
            $db->query("DELETE FROM " .$ecs->table('goods_gallery'). " WHERE single_id = '$id'");
    }

    admin_log('', 'single_remove', 'ads');

    $url = 'comment_manage.php?act=single_query&' . str_replace('act=single_remove', '', $_SERVER['QUERY_STRING']);

    ecs_header("Location: $url\n");
    exit;
}

/*------------------------------------------------------ */
//-- 删除某一条评论
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove')
{
    check_authz_json('comment_priv');

    $id = intval($_GET['id']);
    
    /* 删除该品牌的图标 */
    $sql = "SELECT comment_img, img_thumb FROM " .$ecs->table('comment_img'). " WHERE comment_id = '$id'";
    $img = $db->getAll($sql);
    
    if($img){
        for($i=0; $i<count($img); $i++){
            @unlink(ROOT_PATH .$img[$i]['comment_img']);
            @unlink(ROOT_PATH .$img[$i]['img_thumb']);
            get_oss_del_file(array($img[$i]['comment_img'], $img[$i]['img_thumb']));
        }
    }
    
    $sql = "DELETE FROM " .$ecs->table('comment_img'). " WHERE comment_id = '$id'";
    $res = $db->query($sql);
    
    $sql = "DELETE FROM " .$ecs->table('comment'). " WHERE comment_id = '$id'";
    $res = $db->query($sql);
    if ($res)
    {
        $db->query("DELETE FROM " .$ecs->table('comment'). " WHERE parent_id = '$id'");
    }

    admin_log('', 'remove', 'ads');

    $url = 'comment_manage.php?act=query&' . str_replace('act=remove', '', $_SERVER['QUERY_STRING']);

    ecs_header("Location: $url\n");
    exit;
}

/*------------------------------------------------------ */
//-- 批量删除用户评论
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'batch')
{
    /* 检查权限 */
    admin_priv('comment_priv');
    
    $action = isset($_POST['sel_action']) ? trim($_POST['sel_action']) : 'deny';

    if (isset($_POST['checkboxes']))
    {
        switch ($action)
        {
            case 'remove':
                
                $sql = "SELECT comment_img, img_thumb FROM " .$ecs->table('comment_img') ." WHERE ". db_create_in($_POST['checkboxes'], 'comment_id');
                $img = $db->getAll($sql);

                if($img){
                    for($i=0; $i<count($img); $i++){
                        @unlink(ROOT_PATH .$img[$i]['comment_img']);
                        @unlink(ROOT_PATH .$img[$i]['img_thumb']);
                        get_oss_del_file(array($img[$i]['comment_img'], $img[$i]['img_thumb']));
                    }
                }

                $db->query("DELETE FROM " .$ecs->table('comment_img'). " WHERE " . db_create_in($_POST['checkboxes'], 'comment_id'));
                $db->query("DELETE FROM " . $ecs->table('comment') . " WHERE " . db_create_in($_POST['checkboxes'], 'comment_id'));
                $db->query("DELETE FROM " . $ecs->table('comment') . " WHERE " . db_create_in($_POST['checkboxes'], 'parent_id'));
                break;

           case 'allow' :
               $db->query("UPDATE " . $ecs->table('comment') . " SET status = 1  WHERE " . db_create_in($_POST['checkboxes'], 'comment_id'));
               break;

           case 'deny' :
               $db->query("UPDATE " . $ecs->table('comment') . " SET status = 0  WHERE " . db_create_in($_POST['checkboxes'], 'comment_id'));
               break;

           default :
               break;
        }

        clear_cache_files();
        $action = ($action == 'remove') ? 'remove' : 'edit';
        admin_log('', $action, 'adminlog');

        $link[] = array('text' => $_LANG['back_list'], 'href' => 'comment_manage.php?act=list');
        sys_msg(sprintf($_LANG['batch_drop_success'], count($_POST['checkboxes'])), 0, $link);
    }
    else
    {
        /* 提示信息 */
        $link[] = array('text' => $_LANG['back_list'], 'href' => 'comment_manage.php?act=list');
        sys_msg($_LANG['no_select_comment'], 0, $link);
    }
}

/**
 * 获取评论列表
 * @access  public
 * @return  array
 */
function get_comment_list($ru_id)
{
    /* 查询条件 */
    $filter['keywords']     = empty($_REQUEST['keywords']) ? 0 : trim($_REQUEST['keywords']);
    if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1)
    {
        $filter['keywords'] = json_str_iconv($filter['keywords']);
    }

    $sort = array('comment_id','comment_rank','add_time','id_value','status');
    $filter['sort_by'] = in_array($_REQUEST['sort_by'], $sort) ? trim($_REQUEST['sort_by']) : 'add_time'; 
    $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : 'ASC';
	
    //ecmoban模板堂 --zhuo start
    $sql = "select user_id from " .$GLOBALS['ecs']->table('merchants_shop_information'). " where shoprz_brandName LIKE '%" . mysql_like_quote($filter['keywords']) . "%' OR shopNameSuffix LIKE '%" . mysql_like_quote($filter['keywords']) . "%'";
    $user_id = $GLOBALS['db']->getOne($sql);

    if(empty($user_id)){
            $user_id = 0;
    }

    $where_user = '';
    if($user_id > 0){
            $where_user = " OR ru_id in(" .$user_id. ")";
    }
    
    $where = "1";
    $where .= (!empty($filter['keywords'])) ? " AND (content LIKE '%" . mysql_like_quote($filter['keywords']) . "%' " .$where_user. ") " : '';
	//ecmoban模板堂 --zhuo end
	
	//ecmoban模板堂 --zhuo start
        if($ru_id > 0){
            $where .= " and ru_id = '$ru_id' ";
        }
        //ecmoban模板堂 --zhuo end
        
    $where .= " AND (parent_id = 0 OR (parent_id > 0 AND user_id > 0))";    

    $sql = "SELECT count(*) FROM " .$GLOBALS['ecs']->table('comment'). " WHERE $where";
    $filter['record_count'] = $GLOBALS['db']->getOne($sql);

    /* 分页大小 */
    $filter = page_and_size($filter);

    /* 获取评论数据 */
    $arr = array();
    $sql  = "SELECT * FROM " .$GLOBALS['ecs']->table('comment'). " WHERE $where " .
            " ORDER BY $filter[sort_by] $filter[sort_order] ".
            " LIMIT ". $filter['start'] .", $filter[page_size]";
    
    $res  = $GLOBALS['db']->query($sql);
    
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        if($row['comment_type'] == 2){
            $sql = "SELECT goods_name FROM " .$GLOBALS['ecs']->table('goods'). " WHERE goods_id='$row[id_value]'";
            $goods_name = $GLOBALS['db']->getOne($sql);
            
            $row['title'] = $goods_name ."<br/><font style='color:#1b9ad5;'>(". $GLOBALS['_LANG']['goods_user_reply'].")</font>";
        }else{
            $sql = ($row['comment_type'] == 0) ?
                "SELECT goods_name FROM " .$GLOBALS['ecs']->table('goods'). " WHERE goods_id='$row[id_value]'" :
                "SELECT title FROM ".$GLOBALS['ecs']->table('article'). " WHERE article_id='$row[id_value]'";
            $row['title'] = $GLOBALS['db']->getOne($sql);
        }    
       
        $row['add_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['add_time']);
        $row['ru_name'] = get_shop_name($row['ru_id'], 1); //ecmoban模板堂 --zhuo

        $arr[] = $row;
    }
    
    $filter['keywords'] = stripslashes($filter['keywords']);
    $arr = array('item' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

    return $arr;
}


/** 
 * 获取晒单列表
 * @access  public
 * @return  array
 * 
 * @author by guan 晒单评价 start
 */
function get_single_list($ru_id)
{
	/* 查询条件 */
	$filter['keywords']     = empty($_REQUEST['keywords']) ? 0 : trim($_REQUEST['keywords']);
	if (isset($_REQUEST['is_ajax']) && $_REQUEST['is_ajax'] == 1)
	{
		$filter['keywords'] = json_str_iconv($filter['keywords']);
	}
	$filter['sort_by']      = empty($_REQUEST['sort_by']) ? 's.addtime' : trim($_REQUEST['sort_by']);
	$filter['sort_order']   = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

	$where = (!empty($filter['keywords'])) ? " AND s.order_sn LIKE '%" . mysql_like_quote($filter['keywords']) . "%' " : '';
        
        if($ru_id > 0){
            $where .= " AND g.user_id = '$ru_id'";
        }
	
	$sql  = "SELECT s.* FROM " .$GLOBALS['ecs']->table('single') ." as s, " .$GLOBALS['ecs']->table('goods') ." as g ". " WHERE s.goods_id = g.goods_id AND 1=1 $where ";
	
	$filter['record_count'] = $GLOBALS['db']->getOne($sql);

	/* 分页大小 */
	$filter = page_and_size($filter);

	/* 获取晒单列表 */
	$arr = array();
	$sql  = "SELECT s.*, g.user_id as ru_id FROM " .$GLOBALS['ecs']->table('single') ." as s, " .$GLOBALS['ecs']->table('goods') ." as g ".  " WHERE s.goods_id = g.goods_id AND 1=1 $where " .
	" ORDER BY $filter[sort_by] $filter[sort_order] ".
	" LIMIT ". $filter['start'] .", $filter[page_size]";
	$res  = $GLOBALS['db']->query($sql);


	while ($row = $GLOBALS['db']->fetchRow($res))
	{
		$sql = "SELECT goods_name FROM " .$GLOBALS['ecs']->table('goods'). " WHERE goods_id='$row[goods_id]'";
		$row['goods_name'] = $GLOBALS['db']->getOne($sql);


		$row['addtime'] = local_date($GLOBALS['_CFG']['time_format'], $row['addtime']);
		$row['order_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['order_time']);
                $row['shop_name'] = get_shop_name($row['ru_id'], 1);

		$arr[] = $row;
	}
	$filter['keywords'] = stripslashes($filter['keywords']);
	$arr = array('item' => $arr, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

	return $arr;
}
?>