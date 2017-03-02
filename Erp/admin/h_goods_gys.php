<?php
/**
 * 借机展示页面逻辑
 */
define('IN_ECS', true);

require('includes/init.php');
require('includes/lib_order.php');

admin_priv('purchase_order');
require_once("function.php");
require_once(ROOT_PATH.'includes/lib_order.php');

$back = $_REQUEST['back'];
$goods_id = trim($_REQUEST['goods_id']);
$style_id = trim($_REQUEST['style_id']);
$serial_number   = $_REQUEST['serial_number'];
$type     = $_REQUEST['type'];
$party_id = trim($_REQUEST['party_id']);
$facility_id = trim($_REQUEST['facility_id']);
$currency = trim($_REQUEST['currency']);
$order_type = trim($_REQUEST['order_type']);
$status_id   = trim($_REQUEST['status_id']);
$barcode = trim($_REQUEST['barcode']);
$order_id = $_REQUEST['order_id'];
$act = $_REQUEST['act'];

if (!$goods_id || !$goods = $db->getRow("SELECT `goods_id`, `goods_name` FROM {$ecs->table('goods')} WHERE `goods_id` = '{$goods_id}'"))
{
    sys_msg('没有此类商品！');
}

// 借机
if($act == "borrow") {
	if (!empty($serial_number) && (!checkHasSerialNumber($serial_number)))
    {
        sys_msg('该串号已经不在库存里！');
    }
}

// 续借
if($act == "edit_h"){
	$sql = "select oi.consignee,oi.order_id,og.goods_number,oi.postscript,
	        (select date(max(bh.predict_return_time)) from ecshop.ecs_borrow_history bh where oi.order_id = bh.order_id limit 1) as predict_return_time
	        from ecshop.ecs_order_info oi
	        inner join ecshop.ecs_order_goods og ON oi.order_id = og.order_id
	        where oi.order_id = '{$order_id}' limit 1";
	$edit_goods = $db->getRow($sql);
	$smarty->assign('edit_goods', $edit_goods);
	$smarty->assign('renew', true);
}

$smarty->assign('party_id', $party_id);
$smarty->assign('facility_id', $facility_id);
$smarty->assign('order_type', $order_type);
$smarty->assign('status_id', $status_id);
$smarty->assign('barcode', $barcode);
$smarty->assign('order_id', $order_id);
$smarty->assign('back', $back);
$smarty->assign('type', $type);
$smarty->assign('result', $result);
$smarty->assign('goods_number', $goods_number);
$smarty->assign('serial_number', $serial_number);
$smarty->assign('goods', $goods);
$smarty->assign('goods_id', $goods_id);
$smarty->assign('style_id', $style_id);
$smarty->display('oukooext/h_goods_gys.htm');

?>
