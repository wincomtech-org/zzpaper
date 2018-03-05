<?php

/**
 * ECSHOP 动态内容函数库
 * ============================================================================
 * * 版权所有 2005-2016 上海商创网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.ecmoban.com；
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和
 * 使用；不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: liubo $
 * $Id: lib_insert.php 17217 2011-01-19 06:29:08Z liubo $
*/

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

/**
 * 获得查询次数以及查询时间
 *
 * @access  public
 * @return  string
 */
function insert_query_info()
{
    if ($GLOBALS['db']->queryTime == '')
    {
        $query_time = 0;
    }
    else
    {
        if (PHP_VERSION >= '5.0.0')
        {
            $query_time = number_format(microtime(true) - $GLOBALS['db']->queryTime, 6);
        }
        else
        {
            list($now_usec, $now_sec)     = explode(' ', microtime());
            list($start_usec, $start_sec) = explode(' ', $GLOBALS['db']->queryTime);
            $query_time = number_format(($now_sec - $start_sec) + ($now_usec - $start_usec), 6);
        }
    }

    /* 内存占用情况 */
    if ($GLOBALS['_LANG']['memory_info'] && function_exists('memory_get_usage'))
    {
        $memory_usage = sprintf($GLOBALS['_LANG']['memory_info'], memory_get_usage() / 1048576);
    }
    else
    {
        $memory_usage = '';
    }

    /* 是否启用了 gzip */
    $gzip_enabled = gzip_enabled() ? $GLOBALS['_LANG']['gzip_enabled'] : $GLOBALS['_LANG']['gzip_disabled'];

    $online_count = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('sessions'));

    /* 加入触发cron代码 */
    $cron_method = empty($GLOBALS['_CFG']['cron_method']) ? '<img src="api/cron.php?t=' . gmtime() . '" alt="" style="width:0px;height:0px;" />' : '';

    return sprintf($GLOBALS['_LANG']['query_info'], $GLOBALS['db']->queryCount, $query_time, $online_count) . $gzip_enabled . $memory_usage . $cron_method;
}

/**
 * 调用浏览历史by wang修改
 *
 * @access  public
 * @return  string
 */
function insert_history()
{
    $str = '<ul>';
    if (!empty($_COOKIE['ECS']['history']))
    {
        $where = db_create_in($_COOKIE['ECS']['history'], 'goods_id');
		//ecmoban模板堂 --zhuo start
		if($GLOBALS['_CFG']['review_goods'] == 1){
			$where .= ' AND review_status > 2 ';
		}
		//ecmoban模板堂 --zhuo end
        $sql   = 'SELECT goods_id, goods_name, goods_thumb, shop_price FROM ' . $GLOBALS['ecs']->table('goods') .
                " WHERE $where AND is_on_sale = 1 AND is_alone_sale = 1 AND is_delete = 0 order by INSTR('".$_COOKIE['ECS']['history']."',goods_id) limit 0,10";
        $query = $GLOBALS['db']->query($sql);
        $res = array();
        while ($row = $GLOBALS['db']->fetch_array($query))
        {
            $goods['goods_id'] = $row['goods_id'];
            $goods['goods_name'] = $row['goods_name'];
            $goods['short_name'] = $GLOBALS['_CFG']['goods_name_length'] > 0 ? sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
            $goods['goods_thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
            $goods['shop_price'] = price_format($row['shop_price']);
            $goods['url'] = build_uri('goods', array('gid'=>$row['goods_id']), $row['goods_name']);
			//by wang
			$str.='<li><div class="p-img"><a href="'.$goods['url'].'" target="_blank" title="'.$goods['goods_name'].'"><img src="'.$goods['goods_thumb'].'" width="178" height="178"></a></div>
                            <div class="p-name"><a href="'.$goods['url'].'" target="_blank">'.$goods['short_name'].'</a></div><div class="p-price">'.$price.'</div>
                            <a href="javascript:addToCart('.$goods['goods_id'].');" class="btn">加入购物车</a></li>';
        }
    }
	$str.="</ul>";
    return $str;
}

function insert_history_test()
{
	#需要查询的IP start
	
	if(!isset($_COOKIE['province'])){
		$area_array = get_ip_area_name();
	 
		if($area_array['county_level'] == 2){
			$date = array('region_id', 'parent_id', 'region_name');
			$where = "region_name = '" .$area_array['area_name']. "' AND region_type = 2";
			$city_info = get_table_date('region', $where, $date, 1);
			
			$date = array('region_id', 'region_name');
			$where = "region_id = '" .$city_info[0]['parent_id']. "'";
			$province_info = get_table_date('region', $where, $date);
			
			$where = "parent_id = '" .$city_info[0]['region_id']. "' order by region_id asc limit 0, 1";
			$district_info = get_table_date('region', $where, $date, 1);
			
		}elseif($area_array['county_level'] == 1){
			$area_name = $area_array['area_name'];
			
			$date = array('region_id', 'region_name');
			$where = "region_name = '$area_name'";
			$province_info = get_table_date('region', $where, $date);
			
			$where = "parent_id = '" .$province_info['region_id']. "' order by region_id asc limit 0, 1";
			$city_info = get_table_date('region', $where, $date, 1);
			
			$where = "parent_id = '" .$city_info[0]['region_id']. "' order by region_id asc limit 0, 1";
			$district_info = get_table_date('region', $where, $date, 1);
		}
	}
	
	$province_id = isset($_COOKIE['province']) ? $_COOKIE['province'] : $province_info['region_id'];
	$city_id = isset($_COOKIE['city']) ? $_COOKIE['city'] : $city_info[0]['region_id'];
	$district_id = isset($_COOKIE['district']) ? $_COOKIE['district'] : $district_info[0]['region_id'];
	
	setcookie('province', $province_id, gmtime() + 3600 * 24 * 30);
	setcookie('city', $city_id, gmtime() + 3600 * 24 * 30);
	setcookie('district', $district_id, gmtime() + 3600 * 24 * 30);
	
	$area_info = get_area_info($province_id);
	$area_id = $area_info['region_id'];
	
	$region_where = "regionId = '$province_id'";
	$date = array('parent_id');
	$region_id = get_table_date('region_warehouse', $region_where, $date, 2);
	#需要查询的IP end
	
    $str = '';
    if (!empty($_COOKIE['ECS']['history']))
    {
        $where = db_create_in($_COOKIE['ECS']['history'], 'g.goods_id');

        //ecmoban模板堂 --zhuo start
        $leftJoin = '';	

        if($GLOBALS['_CFG']['open_area_goods'] == 1){
                $leftJoin .= " left join " .$GLOBALS['ecs']->table('link_area_goods'). " as lag on g.goods_id = lag.goods_id ";
                $where .= " and lag.region_id = '$area_id' ";
        }

        $shop_price = "wg.warehouse_price, wg.warehouse_promote_price, wag.region_price, wag.region_promote_price, g.model_price, g.model_attr, ";
        $leftJoin .= " left join " .$GLOBALS['ecs']->table('warehouse_goods'). " as wg on g.goods_id = wg.goods_id and wg.region_id = '$region_id' ";
        $leftJoin .= " left join " .$GLOBALS['ecs']->table('warehouse_area_goods'). " as wag on g.goods_id = wag.goods_id and wag.region_id = '$area_id' ";
        //ecmoban模板堂 --zhuo end	
        
        $sql   = 'SELECT g.goods_id, g.goods_name, g.goods_thumb, ' .
				$shop_price .
				"IFNULL(mp.user_price, IF(g.model_price < 1, g.shop_price, IF(g.model_price < 2, wg.warehouse_price, wag.region_price)) * '$_SESSION[discount]') AS shop_price, " .
				"IF(g.model_price < 1, g.promote_price, IF(g.model_price < 2, wg.warehouse_promote_price, wag.region_promote_price)) as promote_price, " . 
				'g.is_promote, g.promote_start_date, g.promote_end_date FROM ' . $GLOBALS['ecs']->table('goods') ." as g ". 
				$leftJoin.
				"LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp ".
                    "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' ".
                " WHERE $where AND g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 limit 0,10";
	
        $query = $GLOBALS['db']->query($sql);
        $res = array();
		
        while ($row = $GLOBALS['db']->fetch_array($query))
        {
            if ($row['promote_price'] > 0)
            {
                    $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
            }
            else
            {
                    $promote_price = 0;
            }

            $goods['goods_id'] = $row['goods_id'];
            $goods['goods_name'] = $row['goods_name'];
            $goods['short_name'] = $GLOBALS['_CFG']['goods_name_length'] > 0 ? sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
            $goods['goods_thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
            $goods['shop_price'] = price_format($row['shop_price']);
            $goods['promote_price'] = ($promote_price > 0) ? price_format($promote_price) : '';
            $goods['is_promote'] = $row['is_promote'];
            $goods['url'] = build_uri('goods', array('gid'=>$row['goods_id']), $row['goods_name']);

            if($row['is_promote'] == 1){
                    $price = $goods['promote_price'];
            }else{
                    $price = $goods['shop_price'];
            }
			
            $str.='<dl class="nch-sidebar-bowers">
                    <dt class="goods-name"><a href="'.$goods['url'].'" target="_blank" title="'.$goods['goods_name'].'">'.$goods['short_name'].'</a></dt>
                    <dd class="goods-pic"><a href="'.$goods['url'].'" target="_blank"><img src="'.$goods['goods_thumb'].'" alt="'.$goods['goods_name'].'" /></a></dd>
                    <dd class="goods-price">'.$price.'</dd>
                    </dl>';
        }
        
    }
	
    return $str;
}


function insert_index_history()
{
    $str = '';
    if (!empty($_COOKIE['ECS']['history']))
    {
        $where = db_create_in($_COOKIE['ECS']['history'], 'goods_id');
        $sql   = 'SELECT goods_id, goods_name, goods_thumb, shop_price ,market_price FROM ' . $GLOBALS['ecs']->table('goods') .
                " WHERE $where AND is_on_sale = 1 AND is_alone_sale = 1 AND is_delete = 0";
        $res = $GLOBALS['db']->getAll($sql);
     	
		foreach($res as $idx => $row)
		{
			$goods[$idx]['goods_id'] = $row['goods_id'];
            $goods[$idx]['goods_name'] = $row['goods_name'];
            $goods[$idx]['short_name'] = $GLOBALS['_CFG']['goods_name_length'] > 0 ? sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
            $goods[$idx]['goods_thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
            $goods[$idx]['shop_price'] = price_format($row['shop_price']);
			$goods[$idx]['market_price'] = price_format($row['market_price']);
            $goods[$idx]['url'] = build_uri('goods', array('gid'=>$row['goods_id']), $row['goods_name']);	
		}
		
		$GLOBALS['smarty']->assign('history_goods',$goods);
	    $output = $GLOBALS['smarty']->fetch('library/history_info.lbi');
		$GLOBALS['smarty']->caching = $need_cache;
		return $output;
    }
   // return $str;
}


function insert_history_info($num='')
{

	if(!isset($_COOKIE['province'])){
		$area_array = get_ip_area_name();
	 
		if($area_array['county_level'] == 2){
			$date = array('region_id', 'parent_id', 'region_name');
			$where = "region_name = '" .$area_array['area_name']. "' AND region_type = 2";
			$city_info = get_table_date('region', $where, $date, 1);
			
			$date = array('region_id', 'region_name');
			$where = "region_id = '" .$city_info[0]['parent_id']. "'";
			$province_info = get_table_date('region', $where, $date);
			
			$where = "parent_id = '" .$city_info[0]['region_id']. "' order by region_id asc limit 0, 1";
			$district_info = get_table_date('region', $where, $date, 1);
			
		}elseif($area_array['county_level'] == 1){
			$area_name = $area_array['area_name'];
			
			$date = array('region_id', 'region_name');
			$where = "region_name = '$area_name'";
			$province_info = get_table_date('region', $where, $date);
			
			$where = "parent_id = '" .$province_info['region_id']. "' order by region_id asc limit 0, 1";
			$city_info = get_table_date('region', $where, $date, 1);
			
			$where = "parent_id = '" .$city_info[0]['region_id']. "' order by region_id asc limit 0, 1";
			$district_info = get_table_date('region', $where, $date, 1);
		}
	}
	
	$province_id = isset($_COOKIE['province']) ? $_COOKIE['province'] : $province_info['region_id'];
	$city_id = isset($_COOKIE['city']) ? $_COOKIE['city'] : $city_info[0]['region_id'];
	$district_id = isset($_COOKIE['district']) ? $_COOKIE['district'] : $district_info[0]['region_id'];
	
	setcookie('province', $province_id, gmtime() + 3600 * 24 * 30);
	setcookie('city', $city_id, gmtime() + 3600 * 24 * 30);
	setcookie('district', $district_id, gmtime() + 3600 * 24 * 30);
	
	$area_info = get_area_info($province_id);
	$area_id = $area_info['region_id'];
	
	$region_where = "regionId = '$province_id'";
	$date = array('parent_id');
	$region_id = get_table_date('region_warehouse', $region_where, $date, 2);
	#需要查询的IP end
	
    if (!empty($_COOKIE['ECS']['history']))
    {
        $where = db_create_in($_COOKIE['ECS']['history'], 'g.goods_id');

        //ecmoban模板堂 --zhuo start
        $leftJoin = '';	

        if($GLOBALS['_CFG']['open_area_goods'] == 1){
                $leftJoin .= " left join " .$GLOBALS['ecs']->table('link_area_goods'). " as lag on g.goods_id = lag.goods_id ";
                $where .= " and lag.region_id = '$area_id' ";
        }

        $shop_price = "wg.warehouse_price, wg.warehouse_promote_price, wag.region_price, wag.region_promote_price, g.model_price, g.model_attr, ";
        $leftJoin .= " left join " .$GLOBALS['ecs']->table('warehouse_goods'). " as wg on g.goods_id = wg.goods_id and wg.region_id = '$region_id' ";
        $leftJoin .= " left join " .$GLOBALS['ecs']->table('warehouse_area_goods'). " as wag on g.goods_id = wag.goods_id and wag.region_id = '$area_id' ";
        //ecmoban模板堂 --zhuo end
		$limit="";
		if(!empty($num)&&$num>0)
		{
			$limit=" limit 0,$num";	
		}
		else
		{
			$limit=" limit 0,14 ";
		}
        
        $sql   = 'SELECT g.goods_id, g.goods_name, g.goods_thumb, ' .
				$shop_price .
				"IFNULL(mp.user_price, IF(g.model_price < 1, g.shop_price, IF(g.model_price < 2, wg.warehouse_price, wag.region_price)) * '$_SESSION[discount]') AS shop_price, " .
				"IF(g.model_price < 1, g.promote_price, IF(g.model_price < 2, wg.warehouse_promote_price, wag.region_promote_price)) as promote_price, " . 
				'g.is_promote, g.promote_start_date, g.promote_end_date FROM ' . $GLOBALS['ecs']->table('goods') ." as g ". 
				$leftJoin.
				"LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp ".
                    "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' ".
                " WHERE $where AND g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 ".$limit;
	
        $query = $GLOBALS['db']->query($sql);
        $res = array();
		$k=0;
        while ($row = $GLOBALS['db']->fetch_array($query))
        {
            if ($row['promote_price'] > 0)
            {
                    $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
            }
            else
            {
                    $promote_price = 0;
            }

            $goods['goods_id'] = $row['goods_id'];
            $goods['goods_name'] = $row['goods_name'];
            $goods['short_name'] = $GLOBALS['_CFG']['goods_name_length'] > 0 ? sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
            $goods['goods_thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
            $goods['shop_price'] = price_format($row['shop_price']);
            $goods['promote_price'] = ($promote_price > 0) ? price_format($promote_price) : '';
            $goods['is_promote'] = $row['is_promote'];
            $goods['url'] = build_uri('goods', array('gid'=>$row['goods_id']), $row['goods_name']);

            $res[$key++]=$goods;
        }
        return $res;
    }
	else
	{
		return '';	
	}
}

function insert_history_category()
{
	#需要查询的IP start
	
	if(!isset($_COOKIE['province'])){
		$area_array = get_ip_area_name();
	 
		if($area_array['county_level'] == 2){
			$date = array('region_id', 'parent_id', 'region_name');
			$where = "region_name = '" .$area_array['area_name']. "' AND region_type = 2";
			$city_info = get_table_date('region', $where, $date, 1);
			
			$date = array('region_id', 'region_name');
			$where = "region_id = '" .$city_info[0]['parent_id']. "'";
			$province_info = get_table_date('region', $where, $date);
			
			$where = "parent_id = '" .$city_info[0]['region_id']. "' order by region_id asc limit 0, 1";
			$district_info = get_table_date('region', $where, $date, 1);
			
		}elseif($area_array['county_level'] == 1){
			$area_name = $area_array['area_name'];
			
			$date = array('region_id', 'region_name');
			$where = "region_name = '$area_name'";
			$province_info = get_table_date('region', $where, $date);
			
			$where = "parent_id = '" .$province_info['region_id']. "' order by region_id asc limit 0, 1";
			$city_info = get_table_date('region', $where, $date, 1);
			
			$where = "parent_id = '" .$city_info[0]['region_id']. "' order by region_id asc limit 0, 1";
			$district_info = get_table_date('region', $where, $date, 1);
		}
	}
	
	$province_id = isset($_COOKIE['province']) ? $_COOKIE['province'] : $province_info['region_id'];
	$city_id = isset($_COOKIE['city']) ? $_COOKIE['city'] : $city_info[0]['region_id'];
	$district_id = isset($_COOKIE['district']) ? $_COOKIE['district'] : $district_info[0]['region_id'];
	
	setcookie('province', $province_id, gmtime() + 3600 * 24 * 30);
	setcookie('city', $city_id, gmtime() + 3600 * 24 * 30);
	setcookie('district', $district_id, gmtime() + 3600 * 24 * 30);
	
	$area_info = get_area_info($province_id);
	$area_id = $area_info['region_id'];
	
	$region_where = "regionId = '$province_id'";
	$date = array('parent_id');
	$region_id = get_table_date('region_warehouse', $region_where, $date, 2);
	#需要查询的IP end
	
    $str = '';
    if (!empty($_COOKIE['ECS']['history']))
    {
        $where = db_create_in($_COOKIE['ECS']['history'], 'g.goods_id');

        //ecmoban模板堂 --zhuo start
        $leftJoin = '';	

        if($GLOBALS['_CFG']['open_area_goods'] == 1){
                $leftJoin .= " left join " .$GLOBALS['ecs']->table('link_area_goods'). " as lag on g.goods_id = lag.goods_id ";
                $where .= " and lag.region_id = '$area_id' ";
        }

        $shop_price = "wg.warehouse_price, wg.warehouse_promote_price, wag.region_price, wag.region_promote_price, g.model_price, g.model_attr, ";
        $leftJoin .= " left join " .$GLOBALS['ecs']->table('warehouse_goods'). " as wg on g.goods_id = wg.goods_id and wg.region_id = '$region_id' ";
        $leftJoin .= " left join " .$GLOBALS['ecs']->table('warehouse_area_goods'). " as wag on g.goods_id = wag.goods_id and wag.region_id = '$area_id' ";
        //ecmoban模板堂 --zhuo end	
        
        $sql   = 'SELECT g.goods_id, g.goods_name, g.goods_thumb, ' .
				$shop_price .
				"IFNULL(mp.user_price, IF(g.model_price < 1, g.shop_price, IF(g.model_price < 2, wg.warehouse_price, wag.region_price)) * '$_SESSION[discount]') AS shop_price, " .
				"IF(g.model_price < 1, g.promote_price, IF(g.model_price < 2, wg.warehouse_promote_price, wag.region_promote_price)) as promote_price, " . 
				'g.is_promote, g.promote_start_date, g.promote_end_date FROM ' . $GLOBALS['ecs']->table('goods') ." as g ". 
				$leftJoin.
				"LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp ".
                    "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' ".
                " WHERE $where AND g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 limit 0,14";
	
        $query = $GLOBALS['db']->query($sql);
        $res = array();
		
        while ($row = $GLOBALS['db']->fetch_array($query))
        {
            if ($row['promote_price'] > 0)
            {
                    $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
            }
            else
            {
                    $promote_price = 0;
            }

            $goods['goods_id'] = $row['goods_id'];
            $goods['goods_name'] = $row['goods_name'];
            $goods['short_name'] = $GLOBALS['_CFG']['goods_name_length'] > 0 ? sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
            $goods['goods_thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
            $goods['shop_price'] = price_format($row['shop_price']);
            $goods['promote_price'] = ($promote_price > 0) ? price_format($promote_price) : '';
            $goods['is_promote'] = $row['is_promote'];
            $goods['url'] = build_uri('goods', array('gid'=>$row['goods_id']), $row['goods_name']);

            if($row['is_promote'] == 1){
                    $price = $goods['promote_price'];
            }else{
                    $price = $goods['shop_price'];
            }
			
            $str.='<li>
                        <div class="produc-content">
                            <div class="p-img"><a href="'.$goods['url'].'" target="_blank" title="'.$goods['goods_name'].'"><img src="'.$goods['goods_thumb'].'" width="142" height="142" /></a></div>
                            <div class="p-price">'.$price.'</div>
                            <div class="btns"><a href="'.$goods['url'].'" target="_blank" class="btn-9">立即购买</a></div>
                        </div>
                    </li>';
        }
        
    }
	
    return $str;
}
		
/**
 * 调用购物车信息
 *
 * @access  public
 * @return  string
 * $num int by wang查询数据的数量
 */
function insert_cart_info($type = 0,$num=0)
{
    //ecmoban模板堂 --zhuo start
    if(!empty($_SESSION['user_id'])){
            $sess_id = " user_id = '" . $_SESSION['user_id'] . "' ";
            $c_sess = " c.user_id = '" . $_SESSION['user_id'] . "' ";
    }else{
            $sess_id = " session_id = '" . real_cart_mac_ip() . "' ";
            $c_sess = " c.session_id = '" . real_cart_mac_ip() . "' ";
    }

    $limit = '';
	
    if($type == 1){
            $limit = " LIMIT 0,4";
    }
	if(!empty($num)&&$num>0)
	{
		$limit=" LIMIT 0,$num";
	}
    //ecmoban模板堂 --zhuo end

    $sql = 'SELECT c.*,g.goods_thumb,g.goods_id,c.goods_number,c.goods_price' .
           ' FROM ' . $GLOBALS['ecs']->table('cart') ." AS c ".
            " LEFT JOIN ".$GLOBALS['ecs']->table('goods')." AS g ON g.goods_id=c.goods_id ".
           " WHERE " . $c_sess . " AND rec_type = '" . CART_GENERAL_GOODS . "'" . $limit;
    $row = $GLOBALS['db']->getAll($sql);
    
    $arr = array();
    $cart_value='';
    foreach($row AS $k=>$v)
    {
		//判断商品类型，如果是超值礼包则修改链接和缩略图 by wu start
		if($v['extension_code']=='package_buy')
		{
			$arr[$k]['url']          = 'package.php';
			$arr[$k]['goods_thumb']  = 'images/package_goods.png';
		}
	    else
		{
			$arr[$k]['url']          = build_uri('goods', array('gid' => $v['goods_id']), $v['goods_name']);
			$arr[$k]['goods_thumb']  = get_image_path($v['goods_id'], $v['goods_thumb'], true);
		}	
		//判断商品类型，如果是超值礼包则修改链接和缩略图 by wu end
        //$arr[$k]['goods_thumb']  =get_image_path($v['goods_id'], $v['goods_thumb'], true);
        $arr[$k]['short_name']   = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
                       sub_str($v['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $v['goods_name'];
        //$arr[$k]['url']          = build_uri('goods', array('gid' => $v['goods_id']), $v['goods_name']);
        $arr[$k]['goods_number'] = $v['goods_number'];
        $arr[$k]['goods_name']   = $v['goods_name'];
        $arr[$k]['goods_price']  = price_format($v['goods_price']);
        $arr[$k]['rec_id']       = $v['rec_id'];
        $arr[$k]['warehouse_id']       = $v['warehouse_id'];
        $arr[$k]['area_id']       = $v['area_id'];
        $arr[$k]['extension_code']       = $v['extension_code'];
        $arr[$k]['is_gift']       = $v['is_gift'];
        
        if ($v['extension_code'] == 'package_buy')
        {
            $arr[$k]['package_goods_list'] = get_package_goods($v['goods_id']);
        }
        
        $cart_value=!empty($cart_value) ? $cart_value . ',' . $v['rec_id'] : $v['rec_id'];

        $properties 			 = get_goods_properties($v['goods_id'], $v['warehouse_id'], $v['area_id'], $v['goods_attr_id'], 1);

        if($properties['spe']){
                $arr[$k]['spe']      = array_values($properties['spe']);
        }else{
                $arr[$k]['spe']      = array();
        }
    }
    	
    $sql = 'SELECT SUM(goods_number) AS number, SUM(goods_price * goods_number) AS amount' .
           ' FROM ' . $GLOBALS['ecs']->table('cart') .
           " WHERE " .$sess_id. " AND rec_type = '" . CART_GENERAL_GOODS . "'";
    $row = $GLOBALS['db']->getRow($sql);

    if ($row)
    {
        $number = intval($row['number']);
        $amount = floatval($row['amount']);
    }
    else
    {
        $number = 0;
        $amount = 0;
    }
	
	if($type == 1){
		
		$cart= array('goods_list' => $arr, 'number' => $number, 'amount' => price_format($amount, false),'goods_list_count'=>count($arr));
		
		return $cart;
	}elseif($type == 2){
		//by wang
		$cart= array('goods_list' => $arr, 'number' => $number, 'amount' => price_format($amount, false),'goods_list_count'=>count($arr));
		
		return $cart;
	}else{
		$GLOBALS['smarty']->assign('number',$number);
		$GLOBALS['smarty']->assign('amount',$amount);
                $GLOBALS['smarty']->assign('cart_info',$row);

		$GLOBALS['smarty']->assign('cart_value',$cart_value);//by wang
		$GLOBALS['smarty']->assign('str',sprintf($GLOBALS['_LANG']['cart_info'], $number, price_format($amount, false)));
		$GLOBALS['smarty']->assign('goods',$arr);
			
		$output = $GLOBALS['smarty']->fetch('library/cart_info.lbi');
		return $output;
	}
}

/**
 * 调用购物车加减返回信息
 *
 * @access  public
 * @return  string
 */
function insert_flow_info($goods_price,$market_price,$saving,$save_rate,$goods_amount,$real_goods_count)
{
    $GLOBALS['smarty']->assign('goods_price',$goods_price);
	$GLOBALS['smarty']->assign('market_price',$market_price);
	$GLOBALS['smarty']->assign('saving',$saving);
	$GLOBALS['smarty']->assign('save_rate',$save_rate);
	$GLOBALS['smarty']->assign('goods_amount',$goods_amount);
	$GLOBALS['smarty']->assign('real_goods_count',$real_goods_count);
		
    $output = $GLOBALS['smarty']->fetch('library/flow_info.lbi');
    return $output;
}

/**
 * 购物车弹出框返回信息
 *
 * @access  public
 * @return  string
 */
function insert_show_div_info($goods_number,$script_name,$goods_id,$goods_recommend,$goods_amount,$real_goods_count)
{
    $GLOBALS['smarty']->assign('goods_number',$goods_number);
	$GLOBALS['smarty']->assign('script_name',$script_name);
	$GLOBALS['smarty']->assign('goods_id',$goods_id);
	$GLOBALS['smarty']->assign('goods_recommend',$goods_recommend);
	$GLOBALS['smarty']->assign('goods_amount',$goods_amount);
	$GLOBALS['smarty']->assign('real_goods_count',$real_goods_count);

    $output = $GLOBALS['smarty']->fetch('library/show_div_info.lbi');
    return $output;
}


/**
 * 调用指定的广告位的广告
 *
 * @access  public
 * @param   integer $id     广告位ID
 * @param   integer $num    广告数量
 * @return  string
 */
function insert_ads($arr)
{
    static $static_res = NULL;
    $arr['id'] = intval($arr['id']);
    $arr['num'] = intval($arr['num']);

    $time = gmtime();
    if (!empty($arr['num']) && $arr['num'] != 1)
    {
        $sql  = 'SELECT a.ad_id, a.position_id, a.media_type, a.ad_link, a.ad_code, a.ad_name, p.ad_width, ' .
                    'p.ad_height, p.position_style, RAND() AS rnd ' .
                'FROM ' . $GLOBALS['ecs']->table('ad') . ' AS a '.
                'LEFT JOIN ' . $GLOBALS['ecs']->table('ad_position') . ' AS p ON a.position_id = p.position_id ' .
                "WHERE enabled = 1 AND start_time <= '" . $time . "' AND end_time >= '" . $time . "' ".
                    "AND a.position_id = '" . $arr['id'] . "' " .
                'ORDER BY rnd LIMIT ' . $arr['num'];
        $res = $GLOBALS['db']->GetAll($sql);
    }
    else
    {
        if ($static_res[$arr['id']] === NULL)
        {
            $sql  = 'SELECT a.ad_id, a.position_id, a.media_type, a.ad_link, a.ad_code, a.ad_name, p.ad_width, '.
                        'p.ad_height, p.position_style, RAND() AS rnd ' .
                    'FROM ' . $GLOBALS['ecs']->table('ad') . ' AS a '.
                    'LEFT JOIN ' . $GLOBALS['ecs']->table('ad_position') . ' AS p ON a.position_id = p.position_id ' .
                    "WHERE enabled = 1 AND a.position_id = '" . $arr['id'] .
                        "' AND start_time <= '" . $time . "' AND end_time >= '" . $time . "' " .
                    'ORDER BY rnd LIMIT 1';
            $static_res[$arr['id']] = $GLOBALS['db']->GetAll($sql);
        }
        $res = $static_res[$arr['id']];
    }
    $ads = array();
    $position_style = '';

    foreach ($res AS $row)
    {
        if ($row['position_id'] != $arr['id'])
        {
            continue;
        }
        $position_style = $row['position_style'];
        switch ($row['media_type'])
        {
            case 0: // 图片广告
                //OSS文件存储ecmoban模板堂 --zhuo start
                if((strpos($row['ad_code'], 'http://') === false && strpos($row['ad_code'], 'https://') === false)){
                    if($GLOBALS['_CFG']['open_oss'] == 1 && !empty($row['ad_code'])){
                        $bucket_info = get_bucket_info();
                        $src = $bucket_info['endpoint'] . DATA_DIR . '/afficheimg/' . $row['ad_code'];
                    }else{
                        $src = DATA_DIR . "/afficheimg/$row[ad_code]";
                    }
                }else{
                    $src = $row['ad_code'];
                }
                //OSS文件存储ecmoban模板堂 --zhuo end
         
                //$ads[] = "<a href='affiche.php?ad_id=$row[ad_id]&amp;uri=" .urlencode($row["ad_link"]). "'
                $ads[] = "<a href='" .$row["ad_link"]. "'    
                target='_blank'><img src='$src' width='" .$row['ad_width']. "' height='$row[ad_height]'
                border='0' /></a>";
                break;
            case 1: // Flash
                
                //OSS文件存储ecmoban模板堂 --zhuo start
                if((strpos($row['ad_code'], 'http://') === false && strpos($row['ad_code'], 'https://') === false)){
                    if($GLOBALS['_CFG']['open_oss'] == 1 && !empty($row['ad_code'])){
                        $bucket_info = get_bucket_info();
                        $src = $bucket_info['endpoint'] . DATA_DIR . '/afficheimg/' . $row['ad_code'];
                    }else{
                        $src = DATA_DIR . "/afficheimg/$row[ad_code]";
                    }
                }else{
                    $src = $row['ad_code'];
                }
                //OSS文件存储ecmoban模板堂 --zhuo end
                
                $ads[] = "<object classid=\"clsid:d27cdb6e-ae6d-11cf-96b8-444553540000\" " .
                         "codebase=\"http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=8,0,0,0\"  " .
                           "width='$row[ad_width]' height='$row[ad_height]'>
                           <param name='movie' value='$src'>
                           <param name='quality' value='high'>
                           <embed src='$src' quality='high'
                           pluginspage='http://www.macromedia.com/go/getflashplayer'
                           type='application/x-shockwave-flash' width='$row[ad_width]'
                           height='$row[ad_height]'></embed>
                         </object>";
                break;
            case 2: // CODE
                $ads[] = $row['ad_code'];
                break;
            case 3: // TEXT
                
                //OSS文件存储ecmoban模板堂 --zhuo start
                if($GLOBALS['_CFG']['open_oss'] == 1 && !empty($row['ad_code'])){
                    $bucket_info = get_bucket_info();
                    $row['ad_code'] = $bucket_info['endpoint'] . $row['ad_code'];
                }
                //OSS文件存储ecmoban模板堂 --zhuo end
                    
                //$ads[] = "<a href='affiche.php?ad_id=$row[ad_id]&amp;uri=" .urlencode($row["ad_link"]). "'
                $ads[] = "<a href='" .$row["ad_link"]. "' target='_blank'>" .htmlspecialchars($row['ad_code']). '</a>';
                break;
        }
    }
    $position_style = 'str:' . $position_style;

    $need_cache = $GLOBALS['smarty']->caching;
    $GLOBALS['smarty']->caching = false;

    $GLOBALS['smarty']->assign('ads', $ads);
    $val = $GLOBALS['smarty']->fetch($position_style);

    $GLOBALS['smarty']->caching = $need_cache;

    return $val;
}

/**
 * 调用会员信息
 *
 * @access  public
 * @return  string
 */
function insert_member_info()
{
    $need_cache = $GLOBALS['smarty']->caching;
    $GLOBALS['smarty']->caching = false;

    if ($_SESSION['user_id'] > 0)
    {
        $GLOBALS['smarty']->assign('user_info', get_user_info());
    }
    else
    {
        if (!empty($_COOKIE['ECS']['username']))
        {
            $GLOBALS['smarty']->assign('ecs_username', stripslashes($_COOKIE['ECS']['username']));
        }
        $captcha = intval($GLOBALS['_CFG']['captcha']);
        if (($captcha & CAPTCHA_LOGIN) && (!($captcha & CAPTCHA_LOGIN_FAIL) || (($captcha & CAPTCHA_LOGIN_FAIL) && $_SESSION['login_fail'] > 2)) && gd_version() > 0)
        {
            $GLOBALS['smarty']->assign('enabled_captcha', 1);
            $GLOBALS['smarty']->assign('rand', mt_rand());
        }
    }
	
	$GLOBALS['smarty']->assign('shop_name', $GLOBALS['_CFG']['shop_name']);
	
    $output = $GLOBALS['smarty']->fetch('library/member_info.lbi');

    $GLOBALS['smarty']->caching = $need_cache;

    return $output;
}

/**
 * 调用评论信息
 *
 * @access  public
 * @return  string
 */
function insert_comments($arr)
{
    $arr['id'] = intval($arr['id']);
    $arr['type'] = addslashes($arr['type']);
    $need_cache = $GLOBALS['smarty']->caching;
    $need_compile = $GLOBALS['smarty']->force_compile;

    $GLOBALS['smarty']->caching = false;
    $GLOBALS['smarty']->force_compile = true;

    /* 验证码相关设置 */
    if ((intval($GLOBALS['_CFG']['captcha']) & CAPTCHA_COMMENT) && gd_version() > 0)
    {
        $GLOBALS['smarty']->assign('enabled_captcha', 1);
        $GLOBALS['smarty']->assign('rand', mt_rand());
    }
    $GLOBALS['smarty']->assign('username',     stripslashes($_SESSION['user_name']));
    $GLOBALS['smarty']->assign('email',        $_SESSION['email']);
    $GLOBALS['smarty']->assign('comment_type', $arr['type']);
    $GLOBALS['smarty']->assign('id',           $arr['id']);
    $cmt = assign_comment($arr['id'],          $arr['type']);

    $GLOBALS['smarty']->assign('comments',     $cmt['comments']);
    $GLOBALS['smarty']->assign('pager',        $cmt['pager']);
    $GLOBALS['smarty']->assign('count',        $cmt['count']);
    $GLOBALS['smarty']->assign('size',        $cmt['size']);


    $val = $GLOBALS['smarty']->fetch('library/comments_list.lbi');

    $GLOBALS['smarty']->caching = $need_cache;
    $GLOBALS['smarty']->force_compile = $need_compile;

    return $val;
}


/**
 * 调用评论信息
 *
 * @access  public
 * @return  string
 */
function insert_comments_single($arr)
{
    $arr['id'] = intval($arr['id']);
    $arr['type'] = addslashes($arr['type']);
    $need_cache = $GLOBALS['smarty']->caching;
    $need_compile = $GLOBALS['smarty']->force_compile;

    $GLOBALS['smarty']->caching = false;
    $GLOBALS['smarty']->force_compile = true;

    /* 验证码相关设置 */
    if ((intval($GLOBALS['_CFG']['captcha']) & CAPTCHA_COMMENT) && gd_version() > 0)
    {
        $GLOBALS['smarty']->assign('enabled_captcha', 1);
        $GLOBALS['smarty']->assign('rand', mt_rand());
    }
    $GLOBALS['smarty']->assign('username',     stripslashes($_SESSION['user_name']));
    $GLOBALS['smarty']->assign('email',        $_SESSION['email']);
    $GLOBALS['smarty']->assign('comment_type', $arr['type']);
    $GLOBALS['smarty']->assign('id',           $arr['id']);
    $cmt = assign_comments_single($arr['id'],          $arr['type']);

    $GLOBALS['smarty']->assign('comments_single',     $cmt['comments']);
    $GLOBALS['smarty']->assign('single_pager',        $cmt['pager']);


    $val = $GLOBALS['smarty']->fetch('library/comments_single_list.lbi');

    $GLOBALS['smarty']->caching = $need_cache;
    $GLOBALS['smarty']->force_compile = $need_compile;

    return $val;
}

/**
 * 调用商品购买记录
 *
 * @access  public
 * @return  string
 */
function insert_bought_notes($arr)
{
    $arr['id'] = intval($arr['id']);
    $need_cache = $GLOBALS['smarty']->caching;
    $need_compile = $GLOBALS['smarty']->force_compile;

    $GLOBALS['smarty']->caching = false;
    $GLOBALS['smarty']->force_compile = true;

    /* 商品购买记录 */
    $sql = 'SELECT u.user_name, og.goods_number, oi.add_time, IF(oi.order_status IN (2, 3, 4), 0, 1) AS order_status ' .
           'FROM ' . $GLOBALS['ecs']->table('order_info') . ' AS oi LEFT JOIN ' . $GLOBALS['ecs']->table('users') . ' AS u ON oi.user_id = u.user_id, ' . $GLOBALS['ecs']->table('order_goods') . ' AS og ' .
           'WHERE oi.order_id = og.order_id AND ' . time() . ' - oi.add_time < 2592000 AND og.goods_id = ' . $arr['id'] . ' ORDER BY oi.add_time DESC LIMIT 5';
    $bought_notes = $GLOBALS['db']->getAll($sql);

    foreach ($bought_notes as $key => $val)
    {
        $bought_notes[$key]['add_time'] = local_date("Y-m-d G:i:s", $val['add_time']);
    }

    $sql = 'SELECT count(*) ' .
           'FROM ' . $GLOBALS['ecs']->table('order_info') . ' AS oi LEFT JOIN ' . $GLOBALS['ecs']->table('users') . ' AS u ON oi.user_id = u.user_id, ' . $GLOBALS['ecs']->table('order_goods') . ' AS og ' .
           'WHERE oi.order_id = og.order_id AND ' . time() . ' - oi.add_time < 2592000 AND og.goods_id = ' . $arr['id'];
    $count = $GLOBALS['db']->getOne($sql);


    /* 商品购买记录分页样式 */
    $pager = array();
    $pager['page']         = $page = 1;
    $pager['size']         = $size = 5;
    $pager['record_count'] = $count;
    $pager['page_count']   = $page_count = ($count > 0) ? intval(ceil($count / $size)) : 1;;
    $pager['page_first']   = "javascript:gotoBuyPage(1,$arr[id])";
    $pager['page_prev']    = $page > 1 ? "javascript:gotoBuyPage(" .($page-1). ",$arr[id])" : 'javascript:;';
    $pager['page_next']    = $page < $page_count ? 'javascript:gotoBuyPage(' .($page + 1) . ",$arr[id])" : 'javascript:;';
    $pager['page_last']    = $page < $page_count ? 'javascript:gotoBuyPage(' .$page_count. ",$arr[id])"  : 'javascript:;';

    $GLOBALS['smarty']->assign('notes', $bought_notes);
    $GLOBALS['smarty']->assign('pager', $pager);


    $val= $GLOBALS['smarty']->fetch('library/bought_notes.lbi');

    $GLOBALS['smarty']->caching = $need_cache;
    $GLOBALS['smarty']->force_compile = $need_compile;

    return $val;
}


/**
 * 调用在线调查信息
 *
 * @access  public
 * @return  string
 */
function insert_vote()
{
    $vote = get_vote();
    if (!empty($vote))
    {
        $GLOBALS['smarty']->assign('vote_id',     $vote['id']);
        $GLOBALS['smarty']->assign('vote',        $vote['content']);
    }
    $val = $GLOBALS['smarty']->fetch('library/vote.lbi');

    return $val;
}

//ecmoban模板堂 --zhuo start
/**
 * 通过类型与传入的ID获取广告内容  修改 zuo start
 *
 * @param string $type
 * @param int $id
 * @return string
 */	
 //广告位大图				
function insert_get_adv($arr)
{
	$need_cache = $GLOBALS['smarty']->caching;
    $need_compile = $GLOBALS['smarty']->force_compile;

    $GLOBALS['smarty']->caching = false;
    $GLOBALS['smarty']->force_compile = true;

    /* 验证码相关设置 */
    if ((intval($GLOBALS['_CFG']['captcha']) & CAPTCHA_COMMENT) && gd_version() > 0)
    {
        $GLOBALS['smarty']->assign('enabled_captcha', 1);
        $GLOBALS['smarty']->assign('rand', mt_rand());
    }
   
    $ad_type = substr($arr['logo_name'], 0, 12);
    $GLOBALS['smarty']->assign('ad_type', $ad_type);
    
    $name = $arr['logo_name'];
    $GLOBALS['smarty']->assign('ad_posti', get_ad_posti($name, $ad_type));

    $val = $GLOBALS['smarty']->fetch('library/position_get_adv.lbi');  

    $GLOBALS['smarty']->caching = $need_cache;
    $GLOBALS['smarty']->force_compile = $need_compile;
	
	return $val;
}

function get_ad_posti($name = '', $ad_type = ''){
	
    $name = "ad.ad_name=" ."'$name'". " and";

    $time = gmtime();
    $sql = "select ap.ad_width,ap.ad_height,ad.ad_name,ad.ad_code,ad.ad_link, ad.link_color, start_time, end_time, ad_type, goods_name from ".$GLOBALS['ecs']->table('ad_position')." as ap left join ".$GLOBALS['ecs']->table('ad')." as ad on ad.position_id = ap.position_id " . 
            " where " .$name. " ad.media_type=0 and '$time' > ad.start_time and '$time' < ad.end_time and ad.enabled=1 AND theme = '" .$GLOBALS['_CFG']['template']. "'";
    $res = $GLOBALS['db']->getAll($sql);

     foreach($res as $key=>$row){
            $arr[$key]['ad_name'] = $row['ad_name'];
            $arr[$key]['ad_code'] = DATA_DIR . '/afficheimg/' . $row['ad_code'];
            
            //OSS文件存储ecmoban模板堂 --zhuo start
            if($GLOBALS['_CFG']['open_oss'] == 1 && !empty($row['ad_code'])){
                $bucket_info = get_bucket_info();
                $arr[$key]['ad_code'] = $bucket_info['endpoint'] . DATA_DIR . '/afficheimg/' . $row['ad_code'];
            }
            //OSS文件存储ecmoban模板堂 --zhuo end
            
            $arr[$key]['ad_link'] = $row['ad_link'];
            $arr[$key]['ad_width'] = $row['ad_width'];
            $arr[$key]['ad_height'] = $row['ad_height'];
            $arr[$key]['link_color'] = $row['link_color'];
            $arr[$key]['posti_type'] = $ad_type;
            $arr[$key]['start_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['start_time']);
            $arr[$key]['end_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['end_time']);
            $arr[$key]['ad_type'] = $row['ad_type'];
            $arr[$key]['goods_name'] = $row['goods_name'];
     }

     return $arr;
}

//广告位小图
function insert_get_adv_child($arr)
{
    $arr['id'] = intval($arr['id']);
    $need_cache = $GLOBALS['smarty']->caching;
    $need_compile = $GLOBALS['smarty']->force_compile;

    $GLOBALS['smarty']->caching = false;
    $GLOBALS['smarty']->force_compile = true;
    
    $arr['warehouse_id'] = isset($arr['warehouse_id']) ? intval($arr['warehouse_id']) : 0;
    $arr['area_id'] = isset($arr['area_id']) ? intval($arr['area_id']) : 0;

    /* 验证码相关设置 */
    if ((intval($GLOBALS['_CFG']['captcha']) & CAPTCHA_COMMENT) && gd_version() > 0)
    {
        $GLOBALS['smarty']->assign('enabled_captcha', 1);
        $GLOBALS['smarty']->assign('rand', mt_rand());
    }
    
    if($arr['id'] && $arr['ad_arr'] != ''){
        $id_name = '_'.$arr['id']."',";
        $str_ad = str_replace(',',$id_name,$arr['ad_arr']);
        $in_ad_arr = substr($str_ad,0,strlen($str_ad)-1);
    }else{
        $id_name = "',";
        $str_ad = str_replace(',',$id_name,$arr['ad_arr']);
        $in_ad_arr = substr($str_ad,0,strlen($str_ad)-1);
    }

    $ad_child = get_ad_posti_child($in_ad_arr, $arr['warehouse_id'], $arr['area_id']);
    $GLOBALS['smarty']->assign('ad_child', $ad_child);

    $merch = substr(substr($arr['ad_arr'],0,6),1);
    $users = substr(substr($arr['ad_arr'],0,8),1);
    $index_ad = substr(substr($arr['ad_arr'],0,9),1);
    $cat_goods_banner = substr(substr($arr['ad_arr'],0,17),1);
    $cat_goods_hot = substr(substr($arr['ad_arr'],0,14),1);
    $index_brand = substr(substr($arr['ad_arr'],0,19),1);

    $marticle = explode(',',$GLOBALS['_CFG']['marticle']); 

    $val = $GLOBALS['smarty']->fetch('library/position_get_adv_small.lbi');

    if($arr['id'] == $marticle[0] && $merch == 'merch'){
            $val = $GLOBALS['smarty']->fetch('library/position_merchantsIn.lbi');
    }elseif($users == 'users_a'){
            $val = $GLOBALS['smarty']->fetch('library/position_merchantsIn_users.lbi');
    }elseif($users == 'users_b'){
            $val = $GLOBALS['smarty']->fetch('library/position_merchants_usersBott.lbi');
    }
    
    if($index_ad == 'index_ad'){
        $val = $GLOBALS['smarty']->fetch('library/index_ad_position.lbi');
    }elseif($cat_goods_banner == 'cat_goods_banner'){
        $val = $GLOBALS['smarty']->fetch('library/cat_goods_banner.lbi');
    }
    
    if($cat_goods_hot == 'cat_goods_hot'){
        $val = $GLOBALS['smarty']->fetch('library/cat_goods_hot.lbi');
    }
    
    if($index_brand == 'index_brand_banner'){
        $val = $GLOBALS['smarty']->fetch('library/index_brand_banner.lbi');
    }elseif($index_brand == 'index_group_banner'){
        $val = $GLOBALS['smarty']->fetch('library/index_group_banner.lbi');
    }elseif($index_brand == 'index_banner_group'){
		$prom_ad=array();
		if(!empty($ad_child)&&is_array($ad_child))
		{
			foreach($ad_child as $key=>$val)
			{
				if($val['goods_info']['promote_end_date']<gmtime())
				{
					unset($ad_child[$key]);
				}
			}
		}
		$prom_ad=$ad_child;

		$GLOBALS['smarty']->assign('prom_ad',$prom_ad);
        $val = $GLOBALS['smarty']->fetch('library/index_banner_group_list.lbi');

    }

	//登录页轮播广告 by wu
	$login_banner=substr(substr($arr['ad_arr'],0,13),1);
    if($login_banner == 'login_banner'){
        $val = $GLOBALS['smarty']->fetch('library/login_banner.lbi');
    }
	//顶级分类页（家电/食品）幻灯广告 by wu
	$top_style_cate_banner=substr(substr($arr['ad_arr'],0,22),1);
    if($top_style_cate_banner == 'top_style_elec_banner'){
        $val = $GLOBALS['smarty']->fetch('library/cat_top_ad.lbi');
    }elseif($top_style_cate_banner == 'top_style_food_banner'){
		$val = $GLOBALS['smarty']->fetch('library/cat_top_ad.lbi');
	}
	//顶级分类页（家电）底部横幅广告 by wu
	$top_style_cate_row=substr(substr($arr['ad_arr'],0,20),1);
    if($top_style_cate_row == 'top_style_elec_foot'){
        $val = $GLOBALS['smarty']->fetch('library/top_style_food.lbi');
    }
	//顶级分类页（家电/食品）楼层横幅广告 by wu
	$top_style_cate_row=substr(substr($arr['ad_arr'],0,19),1);
    if($top_style_cate_row == 'top_style_elec_row'){
        $val = $GLOBALS['smarty']->fetch('library/top_style_food.lbi');
    }elseif($top_style_cate_row == 'top_style_food_row'){
		$val = $GLOBALS['smarty']->fetch('library/top_style_food.lbi');
	}
	//顶级分类页（家电）品牌广告 by wu
	$top_style_elec_brand=substr(substr($arr['ad_arr'],0,21),1);
    if($top_style_elec_brand == 'top_style_elec_brand'){
        $val = $GLOBALS['smarty']->fetch('library/top_style_elec_brand.lbi');
    }
	//顶级分类页（家电/食品）楼层左侧广告 by wu
	$top_style_elec_left=substr(substr($arr['ad_arr'],0,20),1);
    if($top_style_elec_left == 'top_style_elec_left'){
        $val = $GLOBALS['smarty']->fetch('library/cat_top_floor_ad.lbi');
    }
	$top_style_food_left=substr(substr($arr['ad_arr'],0,20),1);
    if($top_style_food_left == 'top_style_food_left'){
        $val = $GLOBALS['smarty']->fetch('library/cat_top_floor_ad.lbi');
    }	
	//顶级分类页（食品）热门广告 by wu
	$top_style_food_hot=substr(substr($arr['ad_arr'],0,19),1);
    if($top_style_food_hot == 'top_style_food_hot'){
        $val = $GLOBALS['smarty']->fetch('library/top_style_food_hot.lbi');
    }	
	
    // 预售首页 大轮播图
    $presale_banner = substr(substr($arr['ad_arr'],0,15),1);
    if($presale_banner == 'presale_banner'){
        $val = $GLOBALS['smarty']->fetch('library/presale_banner.lbi');
    }
    
    //预售首页小轮播
    $presale_banner_small = substr(substr($arr['ad_arr'],0,21),1);
    if($presale_banner_small == 'presale_banner_small'){
        $val = $GLOBALS['smarty']->fetch('library/presale_banner_small.lbi');
    }
    //预售首页小轮播  左侧的banner
    $presale_banner_small_left = substr(substr($arr['ad_arr'],0,26),1);
    if($presale_banner_small_left == 'presale_banner_small_left')
    {
        $val = $GLOBALS['smarty']->fetch('library/presale_banner_small_left.lbi');
    }
    //预售首页小轮播  右侧的banner
    $presale_banner_small_right = substr(substr($arr['ad_arr'],0,27),1);
    if($presale_banner_small_right == 'presale_banner_small_right')
    {
        $val = $GLOBALS['smarty']->fetch('library/presale_banner_small_right.lbi');
    }
    //预售 新品页轮播图
    $presale_banner_new = substr(substr($arr['ad_arr'],0,19),1);
    if($presale_banner_new == 'presale_banner_new')
    {
        $val = $GLOBALS['smarty']->fetch('library/presale_banner_new.lbi');
    }
    //预售 抢先订页 轮播图
    $presale_banner_advance = substr(substr($arr['ad_arr'],0,23),1);
    if($presale_banner_advance == 'presale_banner_advance')
    {
        $val = $GLOBALS['smarty']->fetch('library/presale_banner_advance.lbi');
    }
    
    //预售 抢先订页 轮播图
    $presale_banner_category = substr(substr($arr['ad_arr'],0,24),1);
    if($presale_banner_category == 'presale_banner_category')
    {
        $val = $GLOBALS['smarty']->fetch('library/presale_banner_category.lbi');
    }
    
    //品牌首页分类下广告by wang
    $brand_cat_ad = substr(substr($arr['ad_arr'],0,13),1);
    if($brand_cat_ad == 'brand_cat_ad'){
        $val = $GLOBALS['smarty']->fetch('library/brand_cat_ad.lbi');
    }

    //顶级分类页首页幻灯片by wang
    $cat_top_ad = substr(substr($arr['ad_arr'],0,11),1);
    if($cat_top_ad == 'cat_top_ad'){

            $val = $GLOBALS['smarty']->fetch('library/cat_top_ad.lbi');
    }

    //顶级分类页首页新品首发左侧上广告by wang
    $cat_top_new_ad = substr(substr($arr['ad_arr'],0,15),1);

    if($cat_top_new_ad == 'cat_top_new_ad'){
            $val = $GLOBALS['smarty']->fetch('library/cat_top_new_ad.lbi');
    }

    //顶级分类页首页新品首发左侧下广告by wang
    $cat_top_newt_ad = substr(substr($arr['ad_arr'],0,16),1);

    if($cat_top_newt_ad == 'cat_top_newt_ad'){

            $val = $GLOBALS['smarty']->fetch('library/cat_top_newt_ad.lbi');
    }

    //顶级分类页首页楼层左侧广告幻灯片by wang
    $cat_top_floor_ad = substr(substr($arr['ad_arr'],0,17),1);
    if($cat_top_floor_ad == 'cat_top_floor_ad'){
            $val = $GLOBALS['smarty']->fetch('library/cat_top_floor_ad.lbi');
    }

    //首页幻灯片下优惠商品左侧广告by wang
    $cat_top_prom_ad = substr(substr($arr['ad_arr'],0,16),1);
    if($cat_top_prom_ad == 'cat_top_prom_ad'){
            $val = $GLOBALS['smarty']->fetch('library/cat_top_prom_ad.lbi');
    }

    //CMS频道页面左侧广告
    $article_channel_left_ad = substr(substr($arr['ad_arr'],0,24),1);

    if($article_channel_left_ad == 'article_channel_left_ad'){

            $val = $GLOBALS['smarty']->fetch('library/article_channel_left_ad.lbi');
    }

    //CMS频道页面商城公告下方广告
    $notic_down_ad = substr(substr($arr['ad_arr'],0,14),1);
    if($notic_down_ad == 'notic_down_ad'){
            $val = $GLOBALS['smarty']->fetch('library/notic_down_ad.lbi');
    }

    //品牌商品页面上方左侧广告
    $brand_list_left_ad = substr(substr($arr['ad_arr'],0,19),1);
    if($brand_list_left_ad == 'brand_list_left_ad'){
            $val = $GLOBALS['smarty']->fetch('library/brand_list_left_ad.lbi');
    }

    //品牌商品页面上方右侧广告
    $brand_list_right_ad = substr(substr($arr['ad_arr'],0,20),1);
    if($brand_list_right_ad == 'brand_list_right_ad'){
            $val = $GLOBALS['smarty']->fetch('library/brand_list_right_ad.lbi');
    }elseif($brand_list_right_ad == 'category_top_banner'){
        $val = $GLOBALS['smarty']->fetch('library/category_top_banner.lbi');
    }

    //搜索商品页面上方左侧广告
    $search_left_ad = substr(substr($arr['ad_arr'],0,15),1);
    if($search_left_ad == 'search_left_ad'){
            $val = $GLOBALS['smarty']->fetch('library/search_left_ad.lbi');
    }

    //搜索商品页面上方右侧广告
    $search_right_ad = substr(substr($arr['ad_arr'],0,16),1);
    if($search_right_ad == 'search_right_ad'){
            $val = $GLOBALS['smarty']->fetch('library/search_right_ad.lbi');
    }
	
    //搜索全部分类页左边广告
    $category_all_left = substr(substr($arr['ad_arr'],0,18),1);
    if($category_all_left == 'category_all_left'){
        $val = $GLOBALS['smarty']->fetch('library/category_all_left.lbi');
    }elseif($category_all_left == 'category_top_left'){
        $val = $GLOBALS['smarty']->fetch('library/category_top_left.lbi');
    }
    
    //搜索全部分类页右边广告
    $category_all_right = substr(substr($arr['ad_arr'],0,19),1);
    if($category_all_right == 'category_all_right'){
        $val = $GLOBALS['smarty']->fetch('library/category_all_right.lbi');
    }

    $GLOBALS['smarty']->caching = $need_cache;
    $GLOBALS['smarty']->force_compile = $need_compile;

    return $val;
}
function get_ad_posti_child($cat_n_child = '', $warehouse_id = 0, $area_id = 0){

    if($cat_n_child == 'sy'){
        $cat_n_child = '';
    }
    if(!empty($cat_n_child)){
       $cat_child = " ad.ad_name in($cat_n_child) and "; 
    }

    $time = gmtime();
    $sql = "select ap.ad_width,ap.ad_height,ad.ad_name,ad.ad_code,ad.ad_link, ad.link_color, start_time, end_time, ad_type, goods_name from ".$GLOBALS['ecs']->table('ad_position')." as ap " .
            " left join ".$GLOBALS['ecs']->table('ad')." as ad on ad.position_id = ap.position_id " . 
            " where " .$cat_child. " ad.media_type=0 and '$time' > ad.start_time and '$time' < ad.end_time and ad.enabled=1 AND theme = '" .$GLOBALS['_CFG']['template']. "' order by ad.ad_id asc";
    $res = $GLOBALS['db']->getAll($sql);
     $arr=array();
     foreach($res as $key=>$row){
            $key = $key + 1; 
            $arr[$key]['ad_name'] = $row['ad_name'];
            $arr[$key]['ad_code'] = DATA_DIR . '/afficheimg/' . $row['ad_code'];
            
            //OSS文件存储ecmoban模板堂 --zhuo start
            if($GLOBALS['_CFG']['open_oss'] == 1 && !empty($row['ad_code'])){
                $bucket_info = get_bucket_info();
                $arr[$key]['ad_code'] = $bucket_info['endpoint'] . DATA_DIR . '/afficheimg/' . $row['ad_code'];
            }
            //OSS文件存储ecmoban模板堂 --zhuo end
    
            
            $arr[$key]['ad_link'] = $row['ad_link'];
            $arr[$key]['ad_width'] = $row['ad_width'];
            $arr[$key]['ad_height'] = $row['ad_height'];
            $arr[$key]['link_color'] = $row['link_color'];
            $arr[$key]['start_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['start_time']);
            $arr[$key]['end_time'] = local_date($GLOBALS['_CFG']['time_format'], $row['end_time']);
            $arr[$key]['ad_type'] = $row['ad_type'];
            $arr[$key]['goods_name'] = $row['goods_name'];
            
            if($row['goods_name']){
                $arr[$key]['goods_info'] = get_goods_ad_promote($row['goods_name'], $warehouse_id, $area_id);
                
                if(empty($row['ad_link'])){
                    $arr[$key]['ad_link'] = $arr[$key]['goods_info']['url'];
                }
            }
     }

     return $arr;
}

//广告位促销商品
function get_goods_ad_promote($goods_name = '', $warehouse_id = 0, $area_id = 0){
    
    $time = gmtime();
    $leftJoin = "";
    //ecmoban模板堂 --zhuo start
    $leftJoin .= " left join " .$GLOBALS['ecs']->table('warehouse_goods'). " as wg on g.goods_id = wg.goods_id and wg.region_id = '$warehouse_id' ";
    $leftJoin .= " left join " .$GLOBALS['ecs']->table('warehouse_area_goods'). " as wag on g.goods_id = wag.goods_id and wag.region_id = '$area_id' ";

    $where = '';
    if($GLOBALS['_CFG']['open_area_goods'] == 1){
            $leftJoin .= " left join " .$GLOBALS['ecs']->table('link_area_goods'). " as lag on g.goods_id = lag.goods_id ";
            $where .= " and lag.region_id = '$area_id' ";
    }

    if($GLOBALS['_CFG']['review_goods'] == 1){
            $where .= ' AND g.review_status > 2 ';
    }
    
    $where .= " AND g.goods_name = '$goods_name' ";
    //ecmoban模板堂 --zhuo end	

    $sql = 'SELECT g.goods_id, g.goods_name, g.goods_name_style, g.comments_number, g.sales_volume,g.market_price, ' . 
			' IF(g.model_price < 1, g.shop_price, IF(g.model_price < 2, wg.warehouse_price, wag.region_price)) AS org_price, ' .
            "IFNULL(mp.user_price, IF(g.model_price < 1, g.shop_price, IF(g.model_price < 2, wg.warehouse_price, wag.region_price)) * '$_SESSION[discount]') AS shop_price, " .
			"IF(g.model_price < 1, g.promote_price, IF(g.model_price < 2, wg.warehouse_promote_price, wag.region_promote_price)) as promote_price, " .
                "promote_start_date, promote_end_date, g.goods_brief, g.goods_thumb, goods_img, b.brand_name, " .
                "g.is_best, g.is_new, g.is_hot, g.is_promote, RAND() AS rnd " .
            'FROM ' . $GLOBALS['ecs']->table('goods') . ' AS g ' .
			$leftJoin . 
            'LEFT JOIN ' . $GLOBALS['ecs']->table('brand') . ' AS b ON b.brand_id = g.brand_id ' .
            "LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp ".
                "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' ".
            'WHERE g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 ' .
            " AND g.is_promote = 1 AND promote_start_date <= '$time' AND promote_end_date >= '$time' " . $where . "ORDER BY g.sort_order, g.last_update DESC";

    $row = $GLOBALS['db']->getRow($sql);
    
    if ($row['promote_price'] > 0)
    {
        $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
        $row['promote_price'] = $promote_price > 0 ? price_format($promote_price) : '';
    }
    else
    {
        $row['promote_price'] = '';
    }
    
    $row['goods_thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
    $row['goods_img']   = get_image_path($row['goods_id'], $row['goods_img'], true);
    
    $row['market_price'] = price_format($row['market_price']);
    $row['shop_price']   = price_format($row['shop_price']);
    $row['url']          = build_uri('goods', array('gid' => $row['goods_id']), $row['goods_name']);
 
    return $row;
}
//ecmoban模板堂 --zhuo end

/*** 调用评论信息条数*/    
function insert_comments_count($arr){    
	$count=$GLOBALS['db']->getOne('SELECT COUNT(*) FROM ' .$GLOBALS['ecs']->table('comment').
			"WHERE id_value='$arr[id]'"."AND comment_type='$arr[type]' AND status = 1 AND parent_id = 0");    
	return $count;    
}


/**
 * 调用对比栏上的浏览历史
 *
 * @access  public
 * @return  string
 * @author by guan
 */
function insert_history_arr()
{
    $str = '';
    if (!empty($_COOKIE['ECS']['history']))
    {
		$goods_cookie = json_decode(str_replace('\\', '', $_COOKIE['compareItems']), true);
		$goods_ids = array();
		if(!empty($goods_cookie))
		{
			foreach($goods_cookie as $key => $val)
			{
				$goods_ids[] = $val['d'];
			}
		}

        $where = db_create_in($_COOKIE['ECS']['history'], 'goods_id');
        $sql   = 'SELECT goods_id, goods_name,goods_type, market_price, goods_thumb, shop_price FROM ' . $GLOBALS['ecs']->table('goods') .
                " WHERE $where AND is_on_sale = 1 AND is_alone_sale = 1 AND is_delete = 0";
        $query = $GLOBALS['db']->query($sql);
        $res = array();
        while ($row = $GLOBALS['db']->fetch_array($query))
        {
            $goods['goods_id'] = $row['goods_id'];
            $goods['goods_name'] = $row['goods_name'];
            $goods['goods_type'] = $row['goods_type'];
            $goods['market_price'] = price_format($row['market_price']);
            $goods['short_name'] = $GLOBALS['_CFG']['goods_name_length'] > 0 ? sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
            $goods['goods_thumb'] = get_image_path($row['goods_id'], $row['goods_thumb'], true);
            $goods['shop_price'] = price_format($row['shop_price']);
            $goods['url'] = build_uri('goods', array('gid'=>$row['goods_id']), $row['goods_name']);
			
			if(in_array($goods['goods_id'], $goods_ids))
			{
				$btn_class = "btn-compare-s_red";
				$history_select = 1;
			}
			else
			{
				$btn_class = "btn-compare-s";
				$history_select = 0;
			}
			
            $str.='<li style="width:226px;"><dl class="hasItem"><dt><a href="'.$goods['url'].'" target="_blank"><img src="'.$goods['goods_thumb'].'" alt="'.$goods['goods_name'].'" width="50" height="50" /></a></dt><dd><a class="diff-item-name" href="'.$goods['url'].'" target="_blank" title="'.$goods['goods_name'].'">'.$goods['short_name'].'</a><span class="p-price"><a id="history_btn'.$goods['goods_id'].'" class="btn-compare '.$btn_class.'" onmouseover="onchangeBtnClass(this, '.$goods['goods_id'].');" onmouseout="RemoveBtnClass(this, '.$goods['goods_id'].');" href="javascript:duibi_submit(this,'.$goods['goods_id'].');"><span>对比</span></a><strong class="J-p-1069555">' . $goods['shop_price'] . '</strong></span></dd>'.'</dl><input type="hidden" id="history_id'.$goods['goods_id'].'" value="'.$goods['goods_id'].'" /><input type="hidden" id="history_name'.$goods['goods_id'].'" value="'.$goods['goods_name'].'" /><input type="hidden" id="history_img'.$goods['goods_id'].'" value="'.$goods['goods_thumb'].'" /><input type="hidden" id="history_market'.$goods['goods_id'].'" value="'.$goods['market_price'].'" /><input type="hidden" id="history_shop'.$goods['goods_id'].'" value="'.$goods['shop_price'].'" /><input type="hidden" id="history_type'.$goods['goods_id'].'" value="'.$goods['goods_type'].'" /><input type="hidden" id="history_select'.$goods['goods_id'].'" value="'.$history_select.'" /></li>';
        }
    }
    return $str;
}

/**
 * 网站左侧浮动框内容
 */				
function insert_user_menu_position()
{
    $need_cache = $GLOBALS['smarty']->caching;
    $need_compile = $GLOBALS['smarty']->force_compile;

    $GLOBALS['smarty']->caching = false;
    $GLOBALS['smarty']->force_compile = true;

    $rank = get_rank_info();
    if ($rank)
    {
            $GLOBALS['smarty']->assign('rank_name', $rank['rank_name']);
    }

    $GLOBALS['smarty']->assign('info',        get_user_default($_SESSION['user_id']));

    $cart_info = insert_cart_info(1);
    $GLOBALS['smarty']->assign('cart_info',        $cart_info);
	
    $val = $GLOBALS['smarty']->fetch('library/user_menu_position.lbi');  

    $GLOBALS['smarty']->caching = $need_cache;
    $GLOBALS['smarty']->force_compile = $need_compile;
	
    return $val;
}

/**
 * 商品详情页讨论圈title
 */				
function insert_goods_comment_title($arr)
{
    $need_cache = $GLOBALS['smarty']->caching;
    $need_compile = $GLOBALS['smarty']->force_compile;

    $GLOBALS['smarty']->caching = false;
    $GLOBALS['smarty']->force_compile = true;
    
    $goods_id = $arr['goods_id'];
    $comment_allCount = get_goods_comment_count($goods_id);
    $comment_good = get_goods_comment_count($goods_id, 1);
    $comment_middle = get_goods_comment_count($goods_id, 2);
    $comment_short = get_goods_comment_count($goods_id, 3);

    $GLOBALS['smarty']->assign('comment_allCount',        $comment_allCount);
    $GLOBALS['smarty']->assign('comment_good',        $comment_good);
    $GLOBALS['smarty']->assign('comment_middle',        $comment_middle);
    $GLOBALS['smarty']->assign('comment_short',        $comment_short);

    $val = $GLOBALS['smarty']->fetch('library/goods_comment_title.lbi');  

    $GLOBALS['smarty']->caching = $need_cache;
    $GLOBALS['smarty']->force_compile = $need_compile;
	
    return $val;
}

/**
 * 商品详情页讨论圈title
 */				
function insert_goods_discuss_title($arr)
{
    $need_cache = $GLOBALS['smarty']->caching;
    $need_compile = $GLOBALS['smarty']->force_compile;

    $GLOBALS['smarty']->caching = false;
    $GLOBALS['smarty']->force_compile = true;
    
    $goods_id = $arr['goods_id'];
    $all_count = get_discuss_type_count($goods_id); //帖子总数
    $t_count = get_discuss_type_count($goods_id, 1); //讨论帖总数
    $w_count = get_discuss_type_count($goods_id, 2); //问答帖总数
    $q_count = get_discuss_type_count($goods_id, 3); //圈子帖总数
    $s_count = get_commentImg_count($goods_id); //晒单帖总数

    $GLOBALS['smarty']->assign('all_count',       $all_count);   
    $GLOBALS['smarty']->assign('t_count',       $t_count);    
    $GLOBALS['smarty']->assign('w_count',       $w_count);    
    $GLOBALS['smarty']->assign('q_count',       $q_count);    
    $GLOBALS['smarty']->assign('s_count',       $s_count);
        
    $val = $GLOBALS['smarty']->fetch('library/goods_discuss_title.lbi');  

    $GLOBALS['smarty']->caching = $need_cache;
    $GLOBALS['smarty']->force_compile = $need_compile;
	
    return $val;
}

//获取头部城市筛选模块
function insert_header_region($arr)
{
	$need_cache = $GLOBALS['smarty']->caching;
    $need_compile = $GLOBALS['smarty']->force_compile;

    $GLOBALS['smarty']->caching = false;
    $GLOBALS['smarty']->force_compile = true;

    $val = $GLOBALS['smarty']->fetch('library/header_region_style.lbi');  

    $GLOBALS['smarty']->caching = $need_cache;
    $GLOBALS['smarty']->force_compile = $need_compile;
	
	return $val;
}

//by wang获得推荐品牌信息
function insert_recommend_brands($arr)
{
	$where=' where be.is_recommend=1 order by b.sort_order asc ';
	if(intval($arr['num'])>0)
	{
		$where.=" limit 0,".intval($arr['num']);
	}
	$sql="select b.* from ".$GLOBALS['ecs']->table('brand')." as b left join ".$GLOBALS['ecs']->table('brand_extend')." as be on b.brand_id=be.brand_id ".$where;
	$val='';
	$recommend_brands=$GLOBALS['db']->getAll($sql);

	foreach ($recommend_brands AS $key => $val)
        {
            $recommend_brands[$key]['brand_logo'] = DATA_DIR . '/brandlogo/'.$val['brand_logo'];
            
            //OSS文件存储ecmoban模板堂 --zhuo start
            if($GLOBALS['_CFG']['open_oss'] == 1 && $val['brand_logo']){
                $bucket_info = get_bucket_info();
                $recommend_brands[$key]['brand_logo'] = $bucket_info['endpoint'] . DATA_DIR . '/brandlogo/' . $val['brand_logo'];
            }
            //OSS文件存储ecmoban模板堂 --zhuo end    
        }

	if(count($recommend_brands)>0)
	{
		$need_cache = $GLOBALS['smarty']->caching;
		$need_compile = $GLOBALS['smarty']->force_compile;
	
		$GLOBALS['smarty']->caching = false;
		$GLOBALS['smarty']->force_compile = true;
		
		$GLOBALS['smarty']->assign('recommend_brands',$recommend_brands);
		$val = $GLOBALS['smarty']->fetch('library/index_brand_street.lbi');  
	
		$GLOBALS['smarty']->caching = $need_cache;
		$GLOBALS['smarty']->force_compile = $need_compile;
		
		
	}
	return $val;
}


//by wang 随机关键字
function insert_rand_keyword()
{
	$searchkeywords = explode(',', trim($GLOBALS['_CFG']['search_keywords']));
	if(count($searchkeywords)>0)
	{
		return $searchkeywords[rand(0,count($searchkeywords)-1)];	
	}
	else
	{
		return '';	
	}
		
}

//获得楼层设置内容by wang
function insert_get_floor_content($arr)
{
	$filename=!empty($arr['filename'])?trim($arr['filename']):'0';
	$region=!empty($arr['region'])?trim($arr['region']):'0';
	$id=!empty($arr['id'])?intval($arr['id']):'0';
	$field=!empty($arr['field'])?trim($arr['field']):'brand_id';
	$theme=$GLOBALS['_CFG']['template'];

	$sql="select ".$field." from ".$GLOBALS['ecs']->table('floor_content')." where filename='$filename' and region='$region' and id='$id' and theme='$theme'";

	return $GLOBALS['db']->getCol($sql);
}

/**
 * 调用浏览历史 //ecmoban模板堂 --zhuo
 *
 * @access  public
 * @return  string
 */
function insert_history_goods($parameter)
{
	$warehouse_id=!empty($parameter['warehouse_id'])?intval($parameter['warehouse_id']):'0';
	$goods_id=!empty($parameter['goods_id'])?intval($parameter['goods_id']):'0';
	$area_id=!empty($parameter['area_id'])?intval($parameter['area_id']):'0';
    $arr = array();
    if (!empty($_COOKIE['ECS']['history'])){
        $where = db_create_in($_COOKIE['ECS']['history'], 'g.goods_id');
        if($GLOBALS['_CFG']['review_goods'] == 1){
                $where .= ' AND g.review_status > 2 ';
        }
        $leftJoin = '';	

        $shop_price = "wg.warehouse_price, wg.warehouse_promote_price, wag.region_price, wag.region_promote_price, g.model_price, g.model_attr, ";
        $leftJoin .= " left join " .$GLOBALS['ecs']->table('warehouse_goods'). " as wg on g.goods_id = wg.goods_id and wg.region_id = '$warehouse_id' ";
        $leftJoin .= " left join " .$GLOBALS['ecs']->table('warehouse_area_goods'). " as wag on g.goods_id = wag.goods_id and wag.region_id = '$area_id' ";

        if($GLOBALS['_CFG']['open_area_goods'] == 1){
            $leftJoin .= " left join " .$GLOBALS['ecs']->table('link_area_goods'). " as lag on g.goods_id = lag.goods_id ";
            $where .= " and lag.region_id = '$area_id' ";
        }
        
        if($goods_id > 0){
            $where .= " AND g.goods_id <> '$goods_id' ";
        }

        $sql = 'SELECT g.goods_id, g.user_id, g.goods_name, g.goods_thumb, g.goods_img, IF(g.model_price < 1, g.shop_price, IF(g.model_price < 2, wg.warehouse_price, wag.region_price)) AS org_price, ' .
                "IFNULL(mp.user_price, IF(g.model_price < 1, g.shop_price, IF(g.model_price < 2, wg.warehouse_price, wag.region_price)) * '$_SESSION[discount]') AS shop_price, ".
                'g.market_price, g.sales_volume, ' .
                'IF(g.model_price < 1, g.promote_price, IF(g.model_price < 2, wg.warehouse_promote_price, wag.region_promote_price)) as promote_price, ' . 
                'g.promote_start_date, g.promote_end_date' .
                ' FROM ' . $GLOBALS['ecs']->table('goods') . " as g " .
                "LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp ".
                "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' ".
                $leftJoin . 
                " WHERE $where AND g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 order by INSTR('".$_COOKIE['ECS']['history']."',g.goods_id) limit 0,10";

        $res = $GLOBALS['db']->query($sql);

        while ($row = $GLOBALS['db']->fetchRow($res))
        {
            $arr[$row['goods_id']]['goods_id']     = $row['goods_id'];
            $arr[$row['goods_id']]['goods_name']   = $row['goods_name'];
            $arr[$row['goods_id']]['short_name']   = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
                    sub_str($row['goods_name'], $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
            $arr[$row['goods_id']]['goods_thumb']  = get_image_path($row['goods_id'], $row['goods_thumb'], true);
            $arr[$row['goods_id']]['goods_img']    = get_image_path($row['goods_id'], $row['goods_img']);
            $arr[$row['goods_id']]['url']          = build_uri('goods', array('gid'=>$row['goods_id']), $row['goods_name']);
            $arr[$row['goods_id']]['sales_volume'] = $row['sales_volume'];
            $arr[$row['goods_id']]['shop_name'] = get_shop_name($row['user_id'], 1); //店铺名称
            $arr[$row['goods_id']]['shopUrl'] = build_uri('merchants_store', array('urid'=>$row['user_id']));
            
            if ($row['promote_price'] > 0)
            {
                    $promote_price = bargain_price($row['promote_price'], $row['promote_start_date'], $row['promote_end_date']);
            }
            else
            {
                    $promote_price = 0;
            }
            
            $arr[$row['goods_id']]['market_price'] = price_format($row['market_price']);   
            $arr[$row['goods_id']]['shop_price']   = price_format($row['shop_price']);
            $arr[$row['goods_id']]['promote_price']    = ($promote_price > 0) ? price_format($promote_price) : '';
        }
    }
    
    $GLOBALS['smarty']->assign('history_goods',$arr);
	$val = $GLOBALS['smarty']->fetch('library/history_goods.lbi');
	
	return $val;
}

//调用浏览记录 by wu
function insert_history_goods_pro()
{
	$history_goods = get_history_goods(0, $GLOBALS['region_id'], $GLOBALS['area_info']['region_id']);
	$history_count=array();
	if ($history_goods) {
		for ($i = 0; $i < count($history_goods) / 6; $i++) {
			//$history_count[$i]=$i; 修改浏览记录 by wu
			for ($j = 0; $j < 6; $j++) {
				if (pos($history_goods)) {
					$history_count[$i][] = pos($history_goods);
					next($history_goods);
				} else {
					break;
				}
			}
		}
	}
        
	$GLOBALS['smarty']->assign('history_count',$history_count);
	$GLOBALS['smarty']->assign('history_goods',$history_goods);	
        
	$val = $GLOBALS['smarty']->fetch('library/cate_top_history_goods.lbi');	
	return $val;	
}

?>