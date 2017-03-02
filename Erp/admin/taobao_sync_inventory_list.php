<?php
/**
 * 查看直销商品库存
 */
define('IN_ECS', true);
require_once('includes/init.php');
require_once ('includes/debug/lib_log.php');
require_once(ROOT_PATH . 'RomeoApi/lib_payment.php');
require_once(ROOT_PATH . 'includes/cls_page.php');

/*
 * 获取库存信息
 * 
 */
$fromSql = "LEFT JOIN romeo.product_mapping pm ON g.goods_id = pm.ECS_GOODS_ID AND IF(gs.style_id IS NULL,0,gs.style_id) = pm.ECS_STYLE_ID
			LEFT JOIN romeo.inventory_item ii ON ii.PRODUCT_ID = pm.PRODUCT_ID AND ii.status_id = 'INV_STTS_AVAILABLE' AND ii.quantity_on_hand_total > 0 ";
			 
$endSql = " AND eoi.shipping_status IN (0,10) AND eoi.order_status IN (0,1) 
		   AND eoi.order_type_id IN ('SALE','RMA_EXCHANGE','SHIP_ONLY') AND eoi.pay_status = 2 AND iid.order_id IS NULL
		   GROUP BY eoi.facility_id, eog.goods_id, eog.style_id) AS inv
		LEFT JOIN romeo.facility f ON f.facility_id = inv.facility_id
		LEFT JOIN ecshop.ecs_goods g ON g.goods_id = inv.goods_id
		LEFT JOIN ecshop.ecs_goods_style gs ON gs.goods_id = g.goods_id AND gs.style_id = inv.style_id and gs.is_delete=0
		LEFT JOIN ecshop.ecs_style s ON s.style_id = gs.style_id
		LEFT JOIN ecshop.ecs_category c ON c.cat_id = g.cat_id
		GROUP BY inv.facility_id, inv.goods_id, inv.style_id";

if ( strstr($_REQUEST['outer_id'],'TC-') == null ) {
	$type = "uni"; //独立商品
	$sql = "SELECT f.facility_name, c.cat_name, IF(gs.style_id IS NULL,g.goods_name,CONCAT_WS(',',g.goods_name,s.color)) gName, SUM(inv.inv_number) inv_number, 
				SUM(inv.confirmed_number) confirmed_number, SUM(inv.unconfirmed_number) unconfirmed_number
			 FROM (
			 	SELECT ii.facility_id, g.goods_id, pm.ecs_style_id style_id, SUM(IFNULL(ii.quantity_on_hand_total,0)) inv_number, 0 as confirmed_number, 0 as unconfirmed_number
			 	FROM ecshop.ecs_goods g LEFT JOIN ecshop.ecs_goods_style gs ON g.goods_id = gs.goods_id and gs.is_delete=0 ".$fromSql.
			   "WHERE g.goods_id = ".$_REQUEST['goods_id']." AND pm.ecs_style_id = ".$_REQUEST['style_id']." AND IF(gs.style_id IS NULL,g.is_delete,gs.is_delete) = 0 
				GROUP BY ii.facility_id, g.goods_id, pm.ecs_style_id
				UNION
				SELECT eoi.facility_id, eog.goods_id, eog.style_id, 0 AS inv_number, SUM(CASE WHEN eoi.order_status = 1 THEN eog.goods_number ELSE 0 END) confirmed_number,
					SUM(CASE WHEN eoi.order_status = 0 THEN eog.goods_number ELSE 0 END) unconfirmed_number
				FROM ecshop.ecs_order_info eoi INNER JOIN ecshop.ecs_order_goods eog ON eoi.order_id = eog.order_id
					LEFT JOIN romeo.inventory_item_detail iid ON convert(eoi.order_id using utf8) = iid.order_id
				WHERE eog.goods_id = ".$_REQUEST['goods_id']." AND eog.style_id = ".$_REQUEST['style_id'].
			$endSql;
}
else {
	$type = "dg"; //套餐
	$sql = "SELECT f.facility_name, c.cat_name, IF(gs.style_id IS NULL,g.goods_name,CONCAT_WS(',',g.goods_name,s.color)) gName, inv.goods_number, SUM(inv.inv_number) inv_number, 
				SUM(inv.confirmed_number) confirmed_number, SUM(inv.unconfirmed_number) unconfirmed_number
			 FROM (
			 	SELECT ii.facility_id, g.goods_id, pm.ecs_style_id style_id, dgi.goods_number, SUM(IFNULL(ii.quantity_on_hand_total,0)) inv_number, 0 as confirmed_number, 0 as unconfirmed_number
			 	FROM ecshop.distribution_group_goods dg LEFT JOIN ecshop.distribution_group_goods_item dgi ON dg.group_id = dgi.group_id
				LEFT JOIN ecshop.ecs_goods g ON dgi.goods_id = g.goods_id
				LEFT JOIN ecshop.ecs_goods_style gs ON g.goods_id = gs.goods_id AND dgi.style_id = gs.style_id and gs.is_delete=0 ".$fromSql.
				"WHERE dg.`code` = '".mysql_escape_string($_REQUEST['outer_id'])."'  AND IF(gs.style_id IS NULL,g.is_delete,gs.is_delete) = 0 
				GROUP BY ii.facility_id, g.goods_id, pm.ecs_style_id
				UNION
				SELECT eoi.facility_id, eog.goods_id, eog.style_id, dgi.goods_number, 0 AS inv_number, SUM(CASE WHEN eoi.order_status = 1 THEN eog.goods_number ELSE 0 END) confirmed_number,
					SUM(CASE WHEN eoi.order_status = 0 THEN eog.goods_number ELSE 0 END) unconfirmed_number
				FROM ecshop.distribution_group_goods dg LEFT JOIN ecshop.distribution_group_goods_item dgi ON dg.group_id = dgi.group_id
					LEFT JOIN ecshop.ecs_order_goods eog ON dgi.goods_id = eog.goods_id AND dgi.style_id = eog.style_id
					INNER JOIN ecshop.ecs_order_info eoi ON eoi.order_id = eog.order_id
					LEFT JOIN romeo.inventory_item_detail iid ON convert(eoi.order_id using utf8)= iid.ORDER_ID
				WHERE dg.`code` = '".mysql_escape_string($_REQUEST['outer_id'])."' ".
			$endSql;
}
//var_dump($sql);
$item_inventory_list = $slave_db->getAll($sql);
$smarty->assign('item_inventory_list',$item_inventory_list);
$smarty->assign('goods_name',trim($_REQUEST['goods_name']));
$smarty->assign('outer_id',$_REQUEST['outer_id']);
$smarty->assign('goods_id',$_REQUEST['goods_id']);
$smarty->assign('style_id',$_REQUEST['style_id']);
$smarty->assign('type',$type);

// 处理请求
$startTime = isset($_REQUEST['start']) && strtotime($_REQUEST['start']) 
    ? $_REQUEST['start'] 
    : null;
$endTime = isset($_REQUEST['end']) && strtotime($_REQUEST['end']) 
    ? $_REQUEST['end'] 
    : null;
   
// 构造分页
$size = 20;
$page = $_REQUEST['page'] ? $_REQUEST['page'] : 1;
$start = ($page - 1) * $size;
if ($csv == null) {
    $limit = " LIMIT $size";
    $offset = " OFFSET $start";
}

// 取得库存同步情况列表
$inventory_info = get_inventory_info($_REQUEST['inv_type'],$_REQUEST['outer_id'],$startTime,$endTime,$offset,$limit);
$count = get_inventory_count($_REQUEST['inv_type'],$_REQUEST['outer_id'],$startTime,$endTime);
//echo "count=".$count;
$pager = setPager($count, $size, $page,"taobao_sync_inventory_list.php?inv_type=".$_REQUEST['inv_type']."&outer_id=".$_REQUEST['outer_id']."&start=".$_REQUEST['start'].
		"&end=".$_REQUEST['end']."&goods_id=".$_REQUEST['goods_id']."&style_id=".$_REQUEST['style_id']."&goods_name=".$_REQUEST['goods_name']);

$smarty->assign('startTime',$startTime);
$smarty->assign('endTime',$endTime);
$smarty->assign('inv_type',$_REQUEST['inv_type']);
$smarty->assign('inventory_info',$inventory_info);        
$smarty->assign('pager',$pager);

$smarty->display('oukooext/taobao_sync_inventory_list.htm');

/*
 * 获取库存同步信息
 */
function get_inventory_info($inv_type,$outer_id,$begin_time,$end_time,$offset,$limit) {
	global $db;
	$order_sql = " order by created_time desc ";
	$condition = " where outer_id='{$outer_id}' ";
	if ($begin_time) {
		$condition .= " and created_time>='{$begin_time}'";
	}
	if ($end_time) {
		$condition .= " and created_time<='{$end_time}'";
	}
	$sql = "";
	if ( $inv_type == "zhixiao" ) {
		$sql = "select num_iid,outer_id,taobao_quantity,created_time from ecshop.ecs_taobao_inventory" 
	       .$condition.$order_sql.$limit.$offset;
	}else if ( $inv_type == "fenxiao" ){
		$sql = "SELECT product_id as num_iid, outer_id, taobao_quantity, created_time FROM ecshop.ecs_taobao_fenxiao_inventory" 
	       .$condition.$order_sql.$limit.$offset;
	}
	//var_dump($sql);
	return $db->getAll($sql);
}

function get_inventory_count($inv_type,$outer_id,$begin_time,$end_time) {
	global $db;
	$condition = " where outer_id='{$outer_id}' ";
	if ($begin_time) {
		$condition .= " and created_time>='{$begin_time}'";
	}
	if ($end_time) {
		$condition .= " and created_time<='{$end_time}'";
	}
	$sql = "";
	if ( $inv_type == "zhixiao" ) {
		$sql = "select count(1) from ecshop.ecs_taobao_inventory" . $condition;
	}
	else if ( $inv_type == "fenxiao" ){
		$sql = "select count(1) from ecshop.ecs_taobao_fenxiao_inventory" . $condition;
	}
	//var_dump($sql);
	return intval($db->getOne($sql));
}

/*
 * 分页
 */
function setPager($total, $offset = 9, $page = null, $url = null, $back = 3, $label = 'page'){
    $page = null == $page ? 1 : $page;
    $page = $page < 1 ? 1 : $page;

    $pages = ceil($total/$offset);
    $pages = $pages > 0 ? $pages : 1;
    $page = $page > $pages ? $pages : $page;
    
    $url = null == $url ? $_SERVER['REQUEST_URI'] : $url;
    $url = preg_replace("/([?|&])$label\=[0-9]*/", "\\1", $url);
    $url = str_replace(array("&&", "?&"), array('&', '?'), $url);

    $url .= strstr($url, '?')
    ? (substr($url, -1) == '?' ? '' : (substr($url, -1) == '&' ? '' : '&'))
    : '?';

    $ppp = '';
    #$ppp .= '<a href="'.$url.$label.'=1" target="" title="First Page 1" class="Pager">&#171</a> ';
    $ppp .= '<a href="'.$url.$label.'=1" target="" title="First Page 1" class="Pager">[首页]</a> ';
    if ($pages <= ($back*2 + 1))
    {
        for ($i=1; $i<=$pages; $i++)
        {
            if ($page == $i) {
                $ppp .= $i;
            } else {
                $ppp .= '<a href="'.$url.$label.'='.$i.'" target="" title="Page '.$i.'" class="Pager">['.$i.']</a>';
            }
        }
    }else{
        $b = $back + 2;
        if ($page <= $b)
        {
            $fromfrom = 1;
            $toto = $back * 2 + 1;
        }elseif ($page > $pages - $b){
            $c = $back*2;
            $fromfrom = $pages - $c;
            $toto = $pages;
        }else{
            $fromfrom = $page - $back;
            $toto = $page + $back;
        }
        for ($i=$fromfrom; $i<=$toto; $i++)
        {
            if ($page == $i) {
                $ppp .= $i;
            } else {
                $ppp .= ' <a href="'.$url.$label.'='.$i.'" target="" title="Page '.$i.'" class="Pager">['.$i.']</a> ';
            }
        }
    }
    #$ppp .= ' <a href="'.$url.$label.'='.$pages.'" target="" title="Last Page '.$pages.'" class="Pager">&#187</a>';
    $ppp .= ' <a href="'.$url.$label.'='.$pages.'" target="" title="Last Page '.$pages.'" class="Pager">[尾页]</a>';
    $ppp .= ' <input type="text" class="pagerInput" name="page" value="'.$page.'" size="5" onFocus="this.select();" onBlur="if(this.value != '.$page.' && this.value >= 1 && this.value <= '.$pages.'){location.href=\''.$url.$label.'=\' + this.value;}else{this.value = '.$page.';}" title=" 跳转 ">';
    $ppp .= ' ( 页数/记录数 :  '.$pages.'/'.$total.')';
    return $ppp;
}
?>
