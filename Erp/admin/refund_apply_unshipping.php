<?php

/**
 * 退款申请
 * 
 * @author yxiang@oukoo.com
 * @copyright ouku.com
 */

define('IN_ECS', true);
require_once('includes/init.php');
// require_once('includes/lib_bonus.php');
require_once('includes/lib_main.php');
require_once(ROOT_PATH . 'RomeoApi/lib_refund.php');
require_once(ROOT_PATH . 'includes/lib_order.php');
require_once('includes/lib_service.php');

require_once('includes/lib_postsale_cache.php');

set_error_handler('refund_error_handler');
admin_priv('refund_apply'); // 退款申请权限

$order_id = isset($_REQUEST['order_id']) && is_numeric($_REQUEST['order_id']) ? $_REQUEST['order_id'] : false ;  // 要处理的订单

// 查询出订单数据
if ($order_id && $order = order_info($order_id))
{
    if (!refund_order_enabled($order))
    {
        die('该订单不能做退款申请');
    }
}
else
{
    die ('查询不到该订单，请确认在该订单对应的组织下操作');
}

//查询该订单是否是被拆分的
$sql = "
	select eor.order_sn,eor.order_id
	from ecshop.order_relation eor
	inner join ecshop.ecs_order_info eoi on eoi.order_id = eor.parent_order_id
	inner join ecshop.ecs_order_info eoi2 on eoi2.order_id = eor.order_id
	where eoi.order_type_id = 'SALE' and eoi2.order_type_id = 'SALE'
	and eoi.order_id = '{$order_id}'
";
$child_orders = $db->getAll($sql);
if (!empty($child_orders)) {
	$smarty->assign('alert', '该订单已被拆分，点确定关闭该窗口，点取消继续申请');
}


$handle = refund_get_soap_client(); // service服务句柄

/*
* 新建退款申请
*/
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST))
{   
    $after_sales_type = $_REQUEST['after_sales_type'];
    $note = $_CFG['adminvars']['after_sales_type_list'][$after_sales_type];//售后类型详细
    $refund_no = refund_save($_POST,$note); // 取得退款单号
    if ($refund_no){
    	$sql_service = "select service_id,service_type,service_status,service_call_status from ecshop.service where back_order_id = '{$order_id}'";
    	$service_info = $db -> getRow($sql_service);
    	
    	$service = array (
	        'service_id' => $service_info['service_id'],
	        'service_status' => $service_info['service_status'],
	        'service_type' => $service_info['service_type'],
	        'log_note' =>  "客服 {$_SESSION['admin_name']} 申请退款",
	        'log_type' => 'CUSTOMER_SERVICE',
	        'is_remark' => 0,
	        'service_call_status' =>$service_info['service_call_status']
	    );	        
	    $result = service_log($service);

        //新需求，一旦退款申请生成，客服不需要客服审核，直接到物流审核
        $input = array();
        $input['refund_id'] = $refund_no;
        $input['user'] = "system";
        $input['level'] = 1;
        $input['note'] = "system_note";
        $input['order_id']  = $order_id;
        refund_check($input);
	    
        $smarty->assign('alert', "退款申请成功了, 申请单号为{$refund_no}, 点击确定关闭该页面");
        //SINRI POSTSALE CACHE UPDATE
        POSTSALE_CACHE_updateRefunds(null,180,$refund_no);
    }
    else
        $smarty->assign('alert', '退款申请失败，点确定关闭该窗口，点取消继续申请');
}
else
{
    // 取得该订单未执行的退款单
    $alert = "";
    try
    {
        if ($order['is_finance_clear'] == 1) {
            $alert .= "请注意，该订单已经清算，不能申请退款！";
        }

        $order_unexecuted_refund = $handle->getUnexecutedRefundInfoByOrderId(array('arg0' => $order['order_id']))->return->UnexecutedRefundInfo;
        if (!empty($order_unexecuted_refund))
        {
            // 取得退款单号
            if (is_object($order_unexecuted_refund))
                $reund_id_array[] = $order_unexecuted_refund->refundId;
            elseif (is_array($order_unexecuted_refund))
            {
                foreach ($order_unexecuted_refund as $item)
                $reund_id_array[] = $item->refundId;
            }

            $alert .= ("该订单已经存在未处理的退款申请了，您要关闭该页面吗？已存在退款单号:"
            .implode(', ', $reund_id_array));
        }
    }
    catch (SoapFault $e)
    {
        trigger_error("SOAP(错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})", E_USER_WARNING);
    }

    if ($alert != "") {
        $smarty->assign('alert', $alert);
    }
}


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

// 计算建议退款金额
$diff = (float)$order['real_paid'] - (float)$order['order_amount'];
$refund_total_amount = $diff > 0 ? $diff : (float)$order['real_paid'];

// 原销售订单信息
$original_order = retrieve_original_order($order['order_sn']);

//订单平台
$shop_type = $db->getOne("select shop_type from ecshop.taobao_shop_conf where party_id = ".$order['party_id']." AND distributor_id = ".$order['distributor_id']." LIMIT 1");

// 退款支付类型, 以原订单的支付方式为准
$order_payment_type = refund_order_payment_type($original_order['order_id']);

// 订单相关的红包
$order_refer_bonus = array(); //get_orders_refer_bonus($original_order['order_id']);

// 判断该订单的商品有没有不在库存中的，并给出提示
if ($goodslist = refund_order_disabled_goods_list($order['order_id'], $order['order_type_id'])) {
    if ($order['order_type_id'] == 'SALE' || $order['order_type_id'] == 'RMA_EXCHANGE') {
        $message = "该订单有部分商品已经出库，只能做部分退款，已经出库的商品(不能做退款的商品)有：<br />";
    } elseif ($order['order_type_id'] == 'RMA_RETURN') {
        $message = "该订单有部分商品没有入库，请谨慎操作，未入库的商品(不能做退款的商品)有: <br />";
    }
    foreach ($goodslist as $item) {
        $message .= "{$item['goods_name']} ({$item['goods_number']}个)，";
    }
    $smarty->assign('message', $message);
}

// 显示
$smarty->assign('order',                     $order);  // 订单信息
$smarty->assign('original_order',            $original_order);  // 原销售订单信息
$smarty->assign('order_refer_bonus',         $order_refer_bonus);  // 订单相关的红包
$smarty->assign('order_total_refund_money',  $order_total_refund_money);  // 订单累计退款金
$smarty->assign('refund_type_id',            refund_order_type($order));  // 退款类型
$smarty->assign('refund_total_amount',       $refund_total_amount); // 建议退款金额
$smarty->assign('after_sales_type_list', $_CFG['adminvars']['after_sales_type_list']);  //售后类型
$smarty->assign('shop_type', $shop_type != null ? $shop_type : '');
$smarty->assign('order_goods_list',          refund_order_goods_list($order['order_id'], $order['order_type_id']));  // 订单的商品明细
$smarty->assign('order_payment_type',        $order_payment_type);  // 订单的退款方式
$smarty->assign('refund_type_list',          refund_type_list());  // 退款类型列表
$smarty->assign('refund_payment_type_list',  refund_payment_type_list());  // 退款方式列表
$smarty->assign('refund_detail_type_list',   refund_detail_type_list());  // 退款明细类型列表
$smarty->assign('refund_detail_goods_reason_list',  refund_detail_reason_list('GOODS'));  // 退款明细原因列表
$smarty->assign('refund_detail_others_reason_list', refund_detail_reason_list('OTHERS'));  // 退款明细原因列表
$smarty->assign('refund_detail_commodity_reason_list', refund_detail_reason_list('COMMODITY')); //退款明细原因列表
$refund_detail_others_reason_list = refund_detail_reason_list('OTHERS');
$refund_detail_other_reason_list = array ( 
		1174647 => $refund_detail_others_reason_list[1174645],
		1174703 => $refund_detail_others_reason_list[1174647],
		1174649 => $refund_detail_others_reason_list[1174706],
		1174706 => $refund_detail_others_reason_list[1174649]
);
$refund_detail_money_reason_list = array (
		1174646 => $refund_detail_others_reason_list[1174646],
		1174648 => $refund_detail_others_reason_list[1174648],
		1174705 => $refund_detail_others_reason_list[1174705]
);
$smarty->assign('refund_detail_other_reason_list', $refund_detail_other_reason_list); 
$smarty->assign('refund_detail_money_reason_list', $refund_detail_money_reason_list); 

$smarty->assign('responsible_party_list', $_CFG['adminvars']['responsible_party']); //责任人
$smarty->display('oukooext/refund_apply_unshipping.htm');

