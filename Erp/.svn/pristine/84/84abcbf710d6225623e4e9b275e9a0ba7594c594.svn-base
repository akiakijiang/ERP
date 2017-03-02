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

require_once('includes/lib_postsale_cache.php');

refund_helper_check_priv('ALL');   // 退款审核权限 kf_refund_check, wl_refund_check, cw_refund_check, refund_execute, shzg_refund_check, shzg_refund_execute

$refund_id = isset($_REQUEST['refund_id']) && is_numeric($_REQUEST['refund_id']) ? $_REQUEST['refund_id'] : false ; // 退款单id

// 查询出退款单信息
if ($refund_id && $refund = refund_get_detail_by_pk($refund_id))
{
	// 查询出原订单信息
	if (!$order = order_info($refund->orderId))
		die ('原订单不存在, 请确认您的业务形态是否吻合');
}
else
{
	die ('退款申请记录不存在');
}

// 退款申请审核动作处理
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST))
{
    switch ($_POST['act']) {
        
        // 退款审核
        case 'refund_check' :
        	$input = $_POST['approve'];
        	$input['refund_id'] = $refund->refundId;
        	$input['order_id']  = $order['order_id'];
        	if ($input['level'] == 'ok') {           // 确认退款
        		$result = refund_execute($input);
        	}
                else if ($input['level'] == 'giveup') {  // 弃审
        		$result = refund_giveup($input);
        	}
        	else if (is_numeric($input['level'])) {  // 正常审核
        		$result = refund_check($input);
        	}
        	else if ($input['level'] == 'cancel') {  // 取消
        		$result = refund_cancel($input);
        	}
        break;
        
        // 设置抵扣项
        case 'refund_update_receivable' :
            // 需要财务权限
            admin_priv('cw_refund_check', 'refund_execute'); 
            $refund_detail_id = isset($_POST['refund_detail_id']) && is_array($_POST['refund_detail_id']) ? $_POST['refund_detail_id'] : array() ;
            try {
                $handle = refund_get_soap_client();
                $result = $handle->updateReceivable(array('arg0' => $refund->refundId, 'arg1' => $refund_detail_id));
            }
            catch (SoapFault $e) {
                trigger_error("SOAP更新是否抵扣实收金额错误, (错误代码:{$e->faultcode}, 错误信息:{$e->faultstring}", E_USER_ERROR); 
            }
        break;
    }

	if ($result)  { 	
		// 更新成功后重新查询
		$refund = refund_get_detail_by_pk($refund_id);
		$smarty->assign('message', '操作成功');	

        //SINRI POSTSALE CACHE UPDATE
        POSTSALE_CACHE_updateRefunds(null,180,$refund_id);	 
	}
}


// 取得累计退款金额
$handle = refund_get_soap_client();
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

// 构造审核按钮
try
{
	$result = $handle->getRefundCheckInfo(array('arg0'=>$refund->refundId));
	$check_status = array();
	$check_seq = (array)$result->return->checkSeq->string;
	$check_actived = (array)$result->return->currentCheck->int;
	$check_list = refund_check_list();
	if (!empty($check_seq))
	{
		foreach ($check_seq as $level)
		{
			$check_status[$level]['name'] = $check_list[$level];
			$check_status[$level]['actived'] = in_array($level, $check_actived) && refund_helper_check_priv($level); // 也需要有当前审核权限审核按钮才能激活
		}
	}
	$check_ok = $result->return->checkedOk && ((refund_helper_check_priv('ok') && $refund->refundTypeId != '8') || (refund_helper_check_priv('ok1') && $refund->refundTypeId == '8') );  // 是否可以执行退款了 (有执行退款权限，并且各部门已审核通过)
	
	$check_giveup = ( ($refund->status == RFND_STTS_IN_CHECK && in_array(3, $check_actived) ) || $refund->status == RFND_STTS_CHECK_OK || $refund->status == RFND_STTS_EXECUTED ) 
					&& refund_helper_check_priv(3);  // 是否可以弃审 (有财务审核权限，并且财务可以审核了)
	
	$check_cancel = ( $refund->status == RFND_STTS_INIT || ($refund->status == RFND_STTS_IN_CHECK && !in_array(3, $check_actived) && !in_array(4, $check_actived) ) )
					&& refund_helper_check_priv(1);  // 是否可以取消 (有客服审核权限，退款单状态为创建或财务可审核之前)
}
catch (SoapFault $e)
{
	trigger_error("SOAP取得申请单审核信息出错: (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})", E_USER_ERROR);
}

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

// 订单相关的红包
$order_refer_bonus = array(); //get_orders_refer_bonus($original_order['order_id']);


// 执行退款前显示可以返还预付款
if ($check_ok) {
    // 取得可退的预付款
    $sql="
        SELECT *, SUM(amount) as sub_total_amount
        FROM distribution_order_adjustment 
        WHERE order_id = '{$original_order['order_id']}' AND status IN ('CONSUMED','RETURNED')  
        GROUP BY goods_id, style_id, group_id, type
    ";
    $order_adjustment_list=$db->getAll($sql);
    $avaiable_refund_prepayment=0;  // 可退的预付款金额
    if($order_adjustment_list){
        foreach($order_adjustment_list as $row)
            $avaiable_refund_prepayment+=$row['sub_total_amount'];
    }
    $smarty->assign('avaiable_refund_prepayment', $avaiable_refund_prepayment);  // 可退预付款
}

// 显示
$smarty->assign('refund',         $refund);  // 退款申请记录
$smarty->assign('order',          $order);   // 当前订单信息
$smarty->assign('original_order', $original_order);  // 原销售订单信息 
$smarty->assign('order_refer_bonus', $order_refer_bonus);  // 订单相关的红包
$smarty->assign('order_total_refund_money',  $order_total_refund_money);  // 订单累计退款金额

$smarty->assign('check_history', $check_history);  // 历史审核信息
$smarty->assign('check_status',  $check_status);  // 审核状态
$smarty->assign('check_ok',      $check_ok);  // 是否可以执行退款了
$smarty->assign('check_cancel',  $check_cancel);  // 是否可以取消退款
$smarty->assign('check_giveup',  $check_giveup);  // 是否可以弃审
$smarty->assign('responsible', get_claims_settlement($order['order_id'], $refund_id)); //理赔信息
$smarty->assign('claims_note',get_claims_note($order['order_id'], $refund_id)); //理赔信息售后类型

$smarty->display('oukooext/refund_check.htm');

