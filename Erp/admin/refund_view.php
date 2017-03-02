<?php

/**
 * 退款申请审核
 * 
 * @author yxiang@oukoo.com
 * @copyright ouku.com
 */

define('IN_ECS', true);
require_once('includes/init.php');
// require_once('includes/lib_bonus.php');
require_once(ROOT_PATH . 'RomeoApi/lib_refund.php');
require_once(ROOT_PATH . 'includes/lib_order.php');
set_error_handler('refund_error_handler');
admin_priv('refund_list');  // 退款申请列表查看权限

$refund_id = isset($_REQUEST['refund_id']) && is_numeric($_REQUEST['refund_id']) ? $_REQUEST['refund_id'] : false ; // 退款单id


// 更改是否抵扣实收金额状态
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST))
{       
    // 需要财务权限
    admin_priv('cw_refund_check', 'refund_execute');
            
    $refund_detail_id = isset($_POST['refund_detail_id']) && is_array($_POST['refund_detail_id']) ? $_POST['refund_detail_id'] : array() ;
    try {
        $handle = refund_get_soap_client();
        $result = $handle->updateReceivable(array('arg0' => $_POST['refund_id'], 'arg1' => $refund_detail_id));
    }
    catch (SoapFault $e) {
        trigger_error("SOAP更新是否抵扣实收金额错误, (错误代码:{$e->faultcode}, 错误信息:{$e->faultstring}", E_USER_ERROR); 
    }
    
    if ($result) {
        $smarty->assign('message', '操作成功');      
    }
}


// 查询出退款单信息
if ($refund_id && $refund = refund_get_detail_by_pk($refund_id))
{
	// 查询出原订单信息
	if (!$order = order_info($refund->orderId))
        die ('原订单不存在');
}
else
{
    die ('退款申请记录不存在');
}

$handle = refund_get_soap_client();

// 取得累计退款金额
try
{
	$order_total_refund_money = $handle->getOrderTotalRefundMoney(array('arg0'=>$order['order_id']))->return;
}
catch (SoapFault $e)
{
	trigger_error("SOAP取得累计退款金额失败: (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})", E_USER_WARNING);
	$order_total_refund_money = 0;		
}

// 取得退款单的状态名
$status_mapping = refund_status_list();
$refund->statusName = $status_mapping[$refund->status];

// 取得历史审核信息
try
{
	$check_history = array();
	$result = $handle->getCheckedInfo(array('arg0'=>$refund->refundId));
	if (is_object($result->return->RefundCheckedInfo))
		$check_history[0] = $result->return->RefundCheckedInfo;
	else if (is_array($result->return->RefundCheckedInfo))
		$check_history = $result->return->RefundCheckedInfo;
	foreach ($check_history as $k => $item)
	{
		$check_history[$k]->formated_dep = $check_list[$item->dep];
	}
}
catch (SoapFault $e)
{
	trigger_error("SOAP取得历史审核信息出错: (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})", E_USER_ERROR);	
}

// 原销售订单信息
$original_order = retrieve_original_order($order['order_sn']);

// 原订单相关的红包
$order_refer_bonus = array(); //get_orders_refer_bonus($original_order['order_id']);


if($order['order_id']){
	global $db;
	$sql="select taobao_order_sn from ecshop.ecs_order_info where order_id=".$order['order_id'];
	$order['taobao_order_sn']=$db->getOne($sql);
}
if($original_order['order_id']){
	global $db;
	$sql="select taobao_order_sn from ecshop.ecs_order_info where order_id=".$original_order['order_id'];
	$original_order['taobao_order_sn']=$db->getOne($sql);
}

$original_order['goods_list']=getOrderOIList($original_order['order_id']);

// 显示
$smarty->assign('refund', $refund);  // 退款申请记录
$smarty->assign('order',  $order);   // 当前订单信息
$smarty->assign('original_order', $original_order);  // 原销售订单信息
$smarty->assign('order_refer_bonus', $order_refer_bonus);  // 订单相关的红包
$smarty->assign('order_total_refund_money', $order_total_refund_money);  // 订单累计退款金额
$smarty->assign('check_history', $check_history);  // 历史审核信息
$smarty->assign('responsible', get_claims_settlement($order['order_id'], $refund_id)); //理赔信息
$smarty->assign('claims_note',get_claims_note($order['order_id'], $refund_id)); //理赔信息售后类型

$smarty->display('oukooext/refund_view.htm');

function getOrderOIList($order_id){
	global $db;
	$sql="SELECT
		eog.rec_id,
		eog.goods_id,
		eog.style_id,
		eog.goods_name,
		eog.goods_number,
		eog.goods_price,
		eog.goods_number * eog.goods_price AS total_price
	FROM
		ecshop.ecs_order_goods eog
	WHERE
		eog.order_id = {$order_id}
	";
	$goodsList=$db->getAll($sql);
	foreach ($goodsList AS $goodsKey => $goods) {
		$sql = "SELECT ii.inventory_item_acct_type_id AS order_type, osi.* 
	    		FROM romeo.inventory_item_detail iid
	    		INNER JOIN romeo.inventory_item ii ON ii.inventory_item_id = iid.inventory_item_id
	    		LEFT JOIN romeo.order_shipping_invoice osi ON osi.order_id = iid.order_id
	    		WHERE iid.order_goods_id = '{$goods['rec_id']}'";
	    $erps = $db->getAll($sql);
	    $shipping_invoices = array();
	    $order_types = array();
	    
	    foreach ($erps as $erp) {
	        $shipping_invoices[] = $erp['shipping_invoice'];
	        $order_types[] = $erp['order_type'];
	    }
	    $goodsList[$goodsKey]['shipping_invoices'] = $shipping_invoices;
	    $goodsList[$goodsKey]['order_types'] = $order_types;
	    $goodsList[$goodsKey]['erp_info'] = $erps;

	    // 获取商品退换货入库的数量
	    $sql = "SELECT SUM(it.quantity_on_hand) FROM ecshop.order_relation orl
	    		INNER JOIN ecshop.ecs_order_info oi ON oi.order_id = orl.order_id AND oi.order_type_id = 'RMA_RETURN'
	    		INNER JOIN ecshop.ecs_order_goods og ON og.order_id = oi.order_id AND og.goods_id = {$goods['goods_id']} AND og.style_id = {$goods['style_id']}
	    		INNER JOIN romeo.inventory_item_detail iid ON convert(og.rec_id using utf8) = iid.order_goods_id AND iid.QUANTITY_ON_HAND_DIFF > 0
	    		INNER JOIN romeo.inventory_transaction it ON iid.inventory_transaction_id = it.inventory_transaction_id
	    			AND it.TO_STATUS_ID IN ('INV_STTS_AVAILABLE','INV_STTS_USED','INV_STTS_DEFECTIVE')
	    		WHERE orl.parent_order_id = {$order_id}";
	    $returned_number = $db->getOne($sql); 
	    if(empty($returned_number)){
	    	$returned_number = 0;
	    }
	    $goodsList[$goodsKey]['returned_number'] = $returned_number;
	}

	return $goodsList;
}