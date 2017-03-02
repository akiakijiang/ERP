<?php

/**
 * 修改退款申请
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
admin_priv('refund_apply'); // 退款申请权限(可申请才能编辑)

$after_sales_type_list = array(
    '1' => '无责破损(外包装完好/本人签收的内物破损)',
    '2' => '无责漏发(外包装完好/本人签收仓库核实无果的漏发)',
    '3' => '无责错发(外包装完好/本人签收仓库核实无果的错发)',
    '4' => '正常退款(未发货退款  正常的退货退款等)',
    '5' => '恶意售后(顾客恶意申请退款、恶意威胁)',
    '6' => '退差价(活动差价或优惠券差价、好评返现、半价活动、免单)',
    '7' => '商品问题(顾客认为是质量问题/描述不符，品牌商不予承担)',
    '8' => '质量问题(顾客对商品质量提出质疑或明显的质量问题，核实过后定为品牌商承担)',
    '9' => '原单退回(原单退回破损，但快递和仓库不承认)',
    '10' => '顾客退货(顾客退货和仓库收到实物不符，必须以顾客的为准)',
    '11' => '液体商品(液体商品破损快递不赔/液体破损直接弃件)',
    '12' => '急速退款(急速退款，顾客填写订单号无效，时间将至，联系无果)',
    '13' => '投诉举报(工商投诉赔款、举报处理)',
    '14' => '特殊业务(品牌商故意或者失误导致的售后)',
    '15' => '责任明确(已经明确责任人，以定责的选项为准)',
    '16' => '其他平台(由于平台/仓库产生的售后)'    
);

$refund_id = isset($_REQUEST['refund_id']) && is_numeric($_REQUEST['refund_id']) ? $_REQUEST['refund_id'] : false ; // 退款单id

// 查询出退款单信息
if ($refund_id && $refund = refund_get_detail_by_pk($refund_id))
{
	// 查询出原订单信息
	if (!$order = order_info($refund->orderId))
		die('原订单不存在');
	
	if ($refund->status != RFND_STTS_INIT){
		if($refund->status != RFND_STTS_IN_CHECK){
			trigger_error('该退款单已在处理中，不能再编辑了', E_USER_WARNING);
		}
	}

}
else
{
	die ('退款申请记录不存在');
}

/*
 * 更新退款申请
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST))
{   
    $claim = $_REQUEST['after_sales_type'];
    $claim_note = $after_sales_type_list[$claim];
	$update = $_POST;
	$update['refund_id'] = $refund->refundId;
	$result = refund_save($update,$claim_note); // 取得退款单号
	if ($result){
		//天猫待配货整单退款 类型 系统自动审核和执行退款
		if($_POST['info']['refund_type_id'] == 8){
			$input = array();
	        $input['refund_id'] = $refund->refundId;
	        $input['user'] = "system";
	        $input['level'] = 1;
	        $input['note'] = "system_note";
	        $input['order_id']  = $refund->orderId;
	        refund_check($input);
		}
		
		$smarty->assign('alert', "更新退款申请成功");
	}else{
		$smarty->assign('alert', '更新退款申请失败，请重新尝试');
	}
	// 更新后重新查询
	unset($refund);
	$refund = refund_get_detail_by_pk($refund_id);
}

// 取得累计退款金额
try
{
	$handle = refund_get_soap_client();
	$order_total_refund_money = $handle->getOrderTotalRefundMoney(array('arg0'=>$order['order_id']))->return;
}
catch (SoapFault $e)
{
	trigger_error("SOAP取得累计退款金额失败: (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})", E_USER_WARNING);
	$order_total_refund_money = 0;		
}

// 原销售订单信息
$original_order = retrieve_original_order($order['order_sn']);

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

$note = get_claims_note($order['order_id'], $refund_id);
$after_sales_type = array_keys($after_sales_type_list,$note);
$sales_type = $after_sales_type[0];

// 显示
$smarty->assign('refund',                    $refund);  // 退款单信息
$smarty->assign('order',                     $order);  // 订单信息
$smarty->assign('original_order',            $original_order);  // 原销售订单信息  
$smarty->assign('order_refer_bonus',         $order_refer_bonus);  // 订单相关的红包
$smarty->assign('order_total_refund_money',  $order_total_refund_money);  // 订单累计退款金
$smarty->assign('order_goods_list',          refund_order_goods_list($order['order_id'], $order['order_type_id']));  // 订单的商品明细
$smarty->assign('order_payment_type',        $order_payment_type);  // 订单的退款方式

$smarty->assign('refund_type_list',          refund_type_list());  // 退款类型列表
$smarty->assign('refund_payment_type_list',  refund_payment_type_list());  // 退款方式列表 
$smarty->assign('refund_detail_type_list',   refund_detail_type_list());  // 退款明细类型列表
$refund_detail_goods_reason_list = refund_detail_reason_list('GOODS');
$smarty->assign('refund_detail_goods_reason_list', $refund_detail_goods_reason_list);  // 退款明细原因列表
$smarty->assign('refund_detail_others_reason_list', refund_detail_reason_list('OTHERS'));  // 退款明细原因列表
$refund_detail_commodity_reason_list = refund_detail_reason_list('COMMODITY');
$refund_detail_others_reason_list = refund_detail_reason_list('OTHERS');
$refund_commodity_reason = $refund->goodsDetail[0]->refundDetailReasonId;

if(array_key_exists($refund_commodity_reason, $refund_detail_goods_reason_list)){
	$refund_detail_commodity_reason_list[$refund_commodity_reason] = $refund_detail_goods_reason_list[$refund_commodity_reason];
}

$smarty->assign('refund_detail_commodity_reason_list', $refund_detail_commodity_reason_list); //退款明细原因列表
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

$othersDetail = $refund->othersDetail;

for($i=0;$i<count($othersDetail);++$i){
	$othersReasonId = $othersDetail[$i]->refundDetailReasonId;
	$othersReasonTypeId = $othersDetail[$i]->refundDetailTypeId;
	if($othersReasonTypeId==2 && !array_key_exists($othersReasonId, $refund_detail_other_reason_list)){
		$refund_detail_other_reason_list[$othersReasonId] = $refund_detail_others_reason_list[$othersReasonId];
	}
	else if(!array_key_exists($othersReasonId, $refund_detail_money_reason_list)){
		$refund_detail_money_reason_list[$othersReasonId] = $refund_detail_others_reason_list[$othersReasonId];
	}
}

$smarty->assign('refund_detail_other_reason_list', $refund_detail_other_reason_list); 
$smarty->assign('refund_detail_money_reason_list', $refund_detail_money_reason_list); 

$smarty->assign('responsible_party_list', $_CFG['adminvars']['responsible_party']);
$smarty->assign('responsible', get_claims_settlement($order['order_id'], $refund_id));
$smarty->assign('after_sales_type_list', $after_sales_type_list);  //售后类型列表
$smarty->assign('sales_type',$sales_type);//售后类型
$smarty->display('oukooext/refund_edit.htm');

