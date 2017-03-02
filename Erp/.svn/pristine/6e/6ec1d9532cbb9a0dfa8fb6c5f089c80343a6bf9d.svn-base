<?php
define('IN_ECS', true);
require_once('../includes/init.php');
require_once ('../../includes/cls_json.php');
require_once ('../../includes/debug/lib_log.php');

$content_list_mapping = array(
	//订单头数据
	'order_header' => array('cls_file_name' => 'cls_sales_order_header.php', 'cls_name' => 'SalesOrderHeader'),
	//平台数据
	'platform_info' => array('cls_file_name' => 'cls_sales_order_platform_info.php', 'cls_name' => 'ClsPlatformInfo'),
	//合并订单数据
	'merge_info' => array('cls_file_name' => 'cls_sales_order_merge_info.php', 'cls_name' => 'ClsSalesOrderMergeInfo'),
	//订单具体信息
	'source_order_info' => array('cls_file_name' => 'cls_sales_order_detail.php', 'cls_name' => 'ClsSalesOrderDetail'),
	//收货人信息
	'consignee' => array('cls_file_name' => 'cls_sales_order_consignee.php', 'cls_name' => 'ClsSalesOrderConsignee'),
	//支付方式
	'payment' => array('cls_file_name' => 'cls_sales_order_payment.php', 'cls_name' => 'ClsSalesOrderPayment'),
	//配送方式
	'express' => array('cls_file_name' => 'cls_sales_order_express.php', 'cls_name' => 'ClsSalesOrderExpress'),
	//仓库
	'facility' => array('cls_file_name' => 'cls_sales_order_facility.php', 'cls_name' => 'ClsSalesOrderFacility'),
	//商品信息
	'goods_list' => array('cls_file_name' => 'cls_sales_order_goods_list.php', 'cls_name' => 'ClsOrderGoodsInfo'),
	//付款信息
	'pay_info' => array('cls_file_name' => 'cls_sales_order_pay_info.php', 'cls_name' => 'ClsSalesOrderPayInfo'),
	//物流信息
	'logistic_info' => array('cls_file_name' => 'cls_sales_order_logistic.php', 'cls_name' => 'ClsSalesOrderLogisticInfo'),
	//售后记录
	'service_records' => array('cls_file_name' => 'cls_sales_order_service_records.php', 'cls_name' => 'ClsSalesOrderServiceRecords'),
	//售后消息
	'service_record_messages' => array('cls_file_name' => 'cls_sales_order_service_records.php', 'cls_name' => 'ClsSalesOrderServiceMessages'),
	//售后评论及回复
	'service_record_comments' => array('cls_file_name' => 'cls_sales_order_service_records.php', 'cls_name' => 'ClsSalesOrderServiceComments'),
	//售后操作日志
	'service_record_logs' => array('cls_file_name' => 'cls_sales_order_service_records.php', 'cls_name' => 'ClsSalesOrderServiceLogs'),
	//订单操作记录
	'action_records' => array('cls_file_name' => 'cls_sales_order_action_records.php', 'cls_name' => 'ClsSalesOrderActionRecords'),
	//订单状态历史记录
	'status_history_records' => array('cls_file_name' => 'cls_sales_order_status_history_records.php', 'cls_name' => 'ClsSalesOrderStatusHistoryRecords'),
	);

//pp($_REQUEST);
//$_REQUEST['content_type'] = 'facility';
//$_REQUEST['action_type'] = 'query';
if(!array_key_exists($_REQUEST['content_type'], $content_list_mapping)){
	// 售后沟通特殊处理
	$result=(object)array();
	if($_REQUEST['content_type'] == 'sale_support_message') {
		$result->error_info_ = array('err_no'=>0, 'message'=>"sale_support_message");
	} 
	else 
	{
	   //获取内容不在对应操作动作可提供范围内
	   $result->error_info_ = array('err_no'=>1, 'message'=>"invalid content_type[".$_REQUEST['content_type']."]!");
	}

}else{
	$content_data = $content_list_mapping[$_REQUEST['content_type']];
	require_once($content_data['cls_file_name']);
	$result = new $content_data['cls_name']($_REQUEST);
	$result->DoAction();
}
//pp('//////////////////////////');
//pp($result->error_info_);
//pp($result->order_info_);

die (json_encode($result));


?>