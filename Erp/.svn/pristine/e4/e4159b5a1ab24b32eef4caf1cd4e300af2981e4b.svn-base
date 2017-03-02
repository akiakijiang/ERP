<?php
define('IN_ECS', true);

require('includes/init.php');
require_once('function.php');
require_once(ROOT_PATH . 'includes/cls_json.php');
admin_priv('taobao_goods_list');

$request = trim($_REQUEST['request']);
$act = trim($_REQUEST['act']);
$pageNum = array(
        '50' => '50',
        '100' => '100',
        '300' => '300',
    );
$pagesize = isset($_REQUEST['pagesize']) ? trim($_REQUEST['pagesize']) : 50;
if (!empty($request) && $request == 'ajax' && $act == 'search_goods_category'){
	$json = new JSON;
	$act = $_REQUEST['act'];
	$cat_name = trim($_POST['q']);
	print $json->encode(get_goods_category($_SESSION['party_id'], $cat_name));
	exit();
 } elseif (!empty($act) && ($act == 'search' || $act == 'page_search')) {

 	$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : 1 ;
 	if ($act == 'search') {
 		$cat_id = trim($_REQUEST['goods_cat_id']);
 		$cat_name = trim($_REQUEST['goods_category_select']);
 		$goods_status = trim($_REQUEST['goods_status_selected']);
 		$is_auto_reserve = trim($_REQUEST['is_auto_reserve']);
 		$goods_name = trim($_REQUEST['goods_name']);
 		//计算符合条件的总记录数
 		if ($cat_id == 'taocan') {
 			$sql = "
 				select count(tg.taobao_goods_id)
 				from ecshop.ecs_taobao_goods tg
 				left join ecshop.distribution_group_goods g on tg.outer_id = g.code
 				where g.status = 'OK' and tg.party_id = '{$_SESSION['party_id']}'
 			";
 		} else {
	 		$sql = "
	            select count(tg.taobao_goods_id)
	            from ecshop.ecs_taobao_goods tg
	            left join ecshop.ecs_goods g on tg.goods_id = g.goods_id
	            left join ecshop.ecs_style s on tg.style_id = s.style_id
	            where g.goods_party_id = '{$_SESSION['party_id']}' and tg.party_id = '{$_SESSION['party_id']}' 
	        ";
	 		if ($cat_id != '') {
	 			$sql .= " and g.cat_id = '{$cat_id}' ";
	 		}
 		}
 	} elseif ($act == 'page_search') {
 		if (isset($_REQUEST['cat_id']) && !empty($_REQUEST['cat_id'])) {
 			$cat_id = trim($_REQUEST['cat_id']);
 		}
 		if (isset($_REQUEST['status']) && !empty($_REQUEST['status'])) {
 			$goods_status = trim($_REQUEST['status']);
 		}
 		if (isset($_REQUEST['total']) && !empty($_REQUEST['total'])) {
   	        $total = trim($_REQUEST['total']);
 		}
 	    if (isset($_REQUEST['is_auto_reserve']) && !empty($_REQUEST['is_auto_reserve'])) {
   	        $is_auto_reserve = trim($_REQUEST['is_auto_reserve']);
 		}
 	}
 	//查询符合条件第一页数据条件
 	$condition = "";
 	if ($goods_status != 'ALL') {
 		$condition .= " and tg.status = '{$goods_status}' ";
 	}
 	if ($is_auto_reserve != 'ALL') {
 		$condition .= " and tg.is_auto_reserve = '{$is_auto_reserve}' ";
 	}
 	if (!empty($goods_name)) {
 		$condition .= " and g.goods_name like '%".$goods_name."%' ";
 	}
 	if ($act == 'search' && !empty($sql)) {
 		//查询总记录数
 		global $db;
 		$sql .= $condition . " group by tg.party_id limit 1 ";
 		$total = $db->getOne($sql);
 	}
 	//查询当前页面
 	$taobao_goods_list = get_goods_by_cat_id ($cat_id, $condition, $pagesize, ($page-1)*$pagesize);

 	//构造分页
 	$url = $_SERVER['PHP_SELF'] . "?act=page_search&page=" . $page . "&cat_id=" .$cat_id. "&status=" . $goods_status .
        "&total=" . $total . "&is_auto_reserve=" . $is_auto_reserve."&pagesize=".$pagesize;
 	
 	$Pager = Pager($total, $pagesize, $page, $url);

 	//参数
 	$smarty->assign("cat_id", $cat_id);
 	$smarty->assign("cat_name", $cat_name);
 	$smarty->assign("goods_status", $goods_status);
 	$smarty->assign("is_auto_reserve", $is_auto_reserve);
 	$smarty->assign('Pager', $Pager);
 	$smarty->assign("taobao_goods_list", $taobao_goods_list);
 } elseif ($act == 'update_taobao_goods' && !empty($act)) {
 	$json = new JSON;
 	global $db;
 	$update_sql = "";
 	$taobao_goods_list = explode("_", trim($_REQUEST['taobao_goods_list']));
 	$reserve_quantity = trim($_REQUEST['quantity']);
 	$goods_status = trim($_REQUEST['status']); //淘宝商品状态
 	if ($goods_status != 'not_update' && !empty($goods_status)) {
 		$update_sql .= " status = '{$goods_status}' ";
 	}

 	$reserve = trim($_REQUEST['reserve']);//是否启用系统计算
 	if ($reserve != 'ALL') {
 		if (!empty($update_sql)) {
 			$update_sql .= ",";
 		}
 		$update_sql .= " is_auto_reserve = '{$reserve}' ";
 		if ($reserve != 1 && $reserve_quantity != 'not_update') {
 			$update_sql .= ", reserve_quantity = '{$reserve_quantity}' ";
 		}
 	}
 	$sql = " update ecshop.ecs_taobao_goods set " . $update_sql . 
 	    " where " .db_create_in($taobao_goods_list, 'taobao_goods_id');

 	$res = $db->query($sql);
 	print $json->encode($res);
 	exit();
 } 
 /**
  * 获取指定商品类型的商品列表
  */
 function get_goods_by_cat_id ($cat_id=null, $condition, $pagesize, $offset) {
 	global $db;
 	if ($cat_id == "taocan") {
 		$sql = "
 	    SELECT tg.*, g.name as goods_name, 1 as is_on_sale
 	    FROM ecs_taobao_goods tg 
 	    LEFT JOIN ecshop.distribution_group_goods g on g.code = tg.outer_id
 	    where g.status = 'OK' " . $condition ." and tg.party_id = '{$_SESSION['party_id']}'
 	    order by g.group_id limit {$pagesize} offset {$offset}
 	    ";
 	} else {
 		$c = "";
 	 	if ($cat_id != null) {
 			$c = " and g.cat_id = '{$cat_id}' " ;
 		}
 		$sql = "
 	    SELECT tg.*, CONCAT_WS(',', g.goods_name, 
               IF(gs.goods_color = '', s.color, gs.goods_color)) AS goods_name,
               g.is_on_sale
 	    FROM ecs_taobao_goods tg 
 	    LEFT JOIN ecs_goods g ON tg.goods_id = g.goods_id
 	    LEFT JOIN ecs_goods_style gs ON g.goods_id = gs.goods_id AND gs.style_id = tg.style_id
 	    LEFT JOIN ecs_style s ON gs.style_id = s.style_id 
 	    where tg.party_id = '{$_SESSION['party_id']}' " . $condition ." and tg.goods_id <> 0 {$c}
 	    order by g.goods_id, gs.style_id limit {$pagesize} offset {$offset}
 	    ";
 	}
 	return $db->getAll($sql);
 }
 /**
  * 获取当前业务组织下的商品类型
  */
 function get_goods_category ($party_id, $cat_name) {
 	global $db;
 	$c = "";
 	if (!empty($cat_name)) {
 		$c = " and c.cat_name like '%{$cat_name}%' ";
 	}
 	$sql = "
 		SELECT c.cat_id, c.party_id, c.parent_id, c.cat_name 
	 	FROM ecshop.ecs_category c
	 	LEFT JOIN romeo.party p on p.party_id = convert(c.party_id using utf8)
	 	WHERE c.party_id not in (1, 4) and c.sort_order < 50 and c.party_id = '{$party_id}' and c.cat_name != p.name ".$c
	;
 	$result = $db->getAll($sql);
 	$sql_taocan = "
 		select 1 from 
 		ecshop.ecs_taobao_goods tg
 		left join ecshop.distribution_group_goods g on tg.outer_id = g.code and tg.party_id = g.party_id
 		where g.status = 'OK' AND g.party_id = '{$party_id}' limit 1";
 	if($db->getOne($sql_taocan)){
 		$taocan = array("cat_id" => "taocan", "party_id" => "$party_id", "parent_id" => "", "cat_name" => "店铺套餐");
 		if (!empty($result)) {
 			$result[] = $taocan;
 		} else {
 			$result = $taocan;
 		}
 	}
// 	pp($result);
 	return $result;
 }
 $smarty->assign('reserve_list',$reserve_list = array("否", ' 是 '));
 $smarty->assign('pagesize', $pagesize);
 $smarty->assign('pageNum', $pageNum);
 $smarty->assign('taobao_goods_status', $_CFG['adminvars']['taobao_goods_status']);
 $smarty->display('oukooext/taobao_erp_goods_manager.htm');


?>
