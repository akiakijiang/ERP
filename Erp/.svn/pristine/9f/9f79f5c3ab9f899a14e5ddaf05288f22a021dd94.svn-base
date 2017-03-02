<?php
/*
 * Created on 2013-4-24
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
define('IN_ECS', true);
require('includes/init.php');
admin_priv('fenxiao_goods_list');
$act = isset($_REQUEST['act']) ? trim($_REQUEST['act']) : '';
$application_nicks = array(
	'f2c6d0dacf32102aa822001d0907b75a' => '乐其数码专营店',
	'f1cfc3f7859f47fa8e7c150c2be35bfc' => '金佰利官方旗舰店',
	'85b1cf4b507b497e844c639733788480' => '安满官方旗舰店',
	'ee6a834daa61d3a7d8c7011e482d3de5' => '金奇仕旗舰店',
	'dc7e418627d249ecb5295ee471a2152a' => 'nb品牌站',
	'159f1daf405445eca885a4f7811a56b8' => '乐贝网络供应商',
);
$status_list = array(
	'up'    => '淘宝在售',
    'down'  => '淘宝下架',
);
$erp_status_list = array(
	'OK' => '同步',
	'STOP' => '停止同步',
);
global $db;
$condition = "";
if ($act == "update") {
	$application_key = trim($_REQUEST['application_key']);
	$reserve_quantity = trim($_REQUEST['reserve_quantity']);
	$erp_status = trim($_REQUEST['erp_status']);
	$product_id = trim($_REQUEST['product_id']);
	$sku_id = trim($_REQUEST['sku_id']);
	$sql = "update ecshop.distribution_product_mapping
			set erp_status = '{$erp_status}', reserve_quantity = '{$reserve_quantity}'
			where application_key = '{$application_key}' and product_id = '{$product_id}' and sku_id = '{$sku_id}'
			";
	if($db->query($sql)){
		$message = "商家编码： {$_REQUEST['outer_id']} 修改成功";
	} else {
		$message = "商家编码： {$_REQUEST['outer_id']} 修改失败";
	}
}


if ($act != "") {
	$goods_name = trim($_REQUEST['goods_name']);
	if (!empty($goods_name)) {
		$condition .= " and p.name like '%". mysql_escape_string($goods_name) ."%' ";
	}
	if (!empty($_REQUEST['status']) && trim($_REQUEST['status']) != 'ALL') {
		$condition .= " and p.status = '". trim($_REQUEST['status']) ."' ";
	}
	if (!empty($_REQUEST['application_key']) && trim($_REQUEST['application_key']) != 'ALL') {
		$condition .= " and p.application_key = '". trim($_REQUEST['application_key']) ."' ";
	}
	$sql = "
		select p.product_id, p.sku_id, p.name, p.outer_id, p.status, p.goods_id, p.style_id, p.group_id, p.properties,
			p.reserve_quantity, p.application_key, t.nick, p.erp_status,
			CONCAT_WS(',', g.goods_name, 
		                IF(gs.goods_color = '', s.color, gs.goods_color)) AS goods_name
		from ecshop.distribution_product_mapping p
		left join ecshop.taobao_shop_conf t on p.application_key = t.application_key
		left join ecs_goods g ON p.goods_id = g.goods_id
		left join ecs_goods_style gs ON g.goods_id = gs.goods_id AND gs.style_id = p.style_id
		left join ecs_style s ON gs.style_id = s.style_id
		where 1 {$condition}
	";
	$taobao_goods_list = $db->getAll($sql);
	$smarty->assign('taobao_goods_list', $taobao_goods_list);
} 
$smarty->assign('message', $message);
$smarty->assign('erp_status_list', $erp_status_list);
$smarty->assign('status_list', $status_list);
$smarty->assign('application_nicks', $application_nicks);
$smarty->display('oukooext/fenxiao_erp_goods_manager.htm');
?>
