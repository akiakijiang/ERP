<?php
define('IN_ECS', true);

require('includes/init.php');
require('includes/lib_order.php');
require_once('includes/lib_goods.php');
require_once('includes/lib_product_code.php');
admin_priv('purchase_order');
require_once("function.php");
require_once(ROOT_PATH.'includes/lib_order.php');

$back = $_REQUEST['back'];
$order_goods_id = $_REQUEST['order_goods_id'];
$info = $_REQUEST['info'];

// 查询换机的订单数据
$sql = "
	SELECT
	ifnull(ii.unit_cost,0) as purchase_paid_amount, ii.inventory_item_acct_type_id as order_type, ii.serial_number, ii.status_id,
	og.goods_name, og.goods_id,og.style_id,ii.provider_id as org_provider_id,oi.facility_id,
	oi.order_sn, oi.order_id,oi.consignee, oi.postscript,og.goods_number,
	ifnull((select sum(quantity_on_hand_diff) from romeo.inventory_item_detail iid2 
	where iid2.quantity_on_hand_diff > 0 and iid2.order_goods_id = convert(og.rec_id using utf8) group by og.rec_id),0) as in_number,
	(og.goods_number - ifnull((select sum(quantity_on_hand_diff) from romeo.inventory_item_detail iid2 
	where iid2.quantity_on_hand_diff > 0 and iid2.order_goods_id = convert(og.rec_id using utf8) group by og.rec_id),0)) as not_in_number 
	FROM
	{$ecs->table('order_info')} oi
	LEFT JOIN {$ecs->table('order_goods')} og ON oi.order_id = og.order_id
	LEFT JOIN romeo.inventory_item_detail iid ON convert (og.rec_id using utf8) = iid.order_goods_id
	LEFT JOIN romeo.inventory_item ii ON iid.inventory_item_id = ii.inventory_item_id
    LEFT JOIN {$ecs->table('goods')} g ON g.goods_id = og.goods_id
	LEFT JOIN {$ecs->table('provider')} p ON p.provider_id = ii.provider_id
	WHERE
	oi.order_type_id = 'BORROW'
	AND og.rec_id = '{$order_goods_id}'
	GROUP by og.rec_id
";
//pp($sql);
$rec = $db->getRow($sql);
	
if ($_REQUEST['submit'] == '入库') {
    $goods_number = trim($_REQUEST['goods_number']);
    if($rec['not_in_number'] == 0) {
    	sys_msg('该商品已经全部还机，请检查！');
    }
    if($rec['not_in_number'] < $goods_number) {
    	sys_msg('本次入库：'.$goods_number.' 超过未入库数：'.$rec['not_in_number'].' 请检查！');
    }

    $serial_number = trim($_REQUEST['serial_number']);
    $toStatusId = trim($_REQUEST['status_id']);
    
	if (checkHasSerialNumber($serial_number))
    {
        sys_msg('该串号已经入库，请检查！');
    }

    $provider_id = $rec['org_provider_id'];
    
    // romeo code:
    // 入库（供应商到正式库）
    include_once(ROOT_PATH . "RomeoApi/lib_inventory.php");
    $goods_id = $rec['goods_id'];
    $style_id = $rec['style_id'];
    
    try {
        $result = createAcceptInventoryTransactionNew("ITT_BORROW_RET", 
                                         array('goods_id'=>$goods_id, 'style_id'=>$style_id), 
                                         $goods_number, $serial_number, $rec['order_type'], $rec['order_id'], 
                                         '', $toStatusId, $rec['purchase_paid_amount'], 
                                         $order_goods_id, $rec['facility_id'],$provider_id);
    } catch(Exception $e) {
    	$info = '入库异常：'.$e->getMessage();
    }
    if($result) {
    	$info = '商品入库成功';  
    	header("location:h_return_goods_gys.php?order_goods_id=".$order_goods_id."&info=".$info."&back=h_return.php");
    	      	
    } else {
    	$info .= ' 商品入库失败';
    }

    if (isset($_REQUEST['is_print_barcode']) && $_REQUEST['is_print_barcode'] == 1) {
        $label = $rec['goods_name'];
        $goods_id = $rec['goods_id'];
        $style_id = $rec['style_id'];
        $sql = "SELECT goods_party_id FROM ecs_goods WHERE goods_id = '{$goods_id}' ";
        $goods_party_id = $db->getOne($sql);
    
        $code = encode_goods_id($goods_id, $style_id);
        $printer_id = $_REQUEST['printer_id'];
        print_product_code($rec['order_id'], $code, 1, $goods_id, $printer_id, $label);
    }

}

$smarty->assign('back', $back);
$smarty->assign('info', $info);
$smarty->assign('rec', $rec);
$smarty->assign('goods_item_type', get_goods_item_type($rec['goods_id']));
$smarty->assign('printers', get_serial_printers());
$smarty->display('oukooext/h_return_goods_gys.htm');


?>