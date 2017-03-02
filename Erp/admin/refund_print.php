<?php

/**
 * 打印退款申请单
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


// 显示
$smarty->assign('refund', $refund);  // 退款申请记录
$smarty->assign('order',  $order);   // 当前订单信息
$smarty->assign('original_order', $original_order);  // 原销售订单信息
$smarty->assign('order_refer_bonus', $order_refer_bonus);  // 订单相关的红包
$smarty->assign('order_total_refund_money', $order_total_refund_money);  // 订单累计退款金额
$smarty->assign('check_history', $check_history);  // 历史审核信息

$smarty->display('oukooext/refund_print.htm');

