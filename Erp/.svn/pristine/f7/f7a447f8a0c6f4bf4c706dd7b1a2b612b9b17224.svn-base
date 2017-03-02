<?php
//require_once(ROOT_PATH . "admin/includes/alipay/alipay_service.php");
//require_once(ROOT_PATH . "admin/includes/alipay/alipay_config.php");
//$parameter = array(
//"service" => "send_goods_confirm_by_platform", //交易类型，必填实物交易＝trade_create_by_buyer
//"partner" =>$partner,                                               //合作商户号
//"return_url" =>$return_url,  //同步返回
//"notify_url" =>$notify_url,  //异步返回
//"_input_charset" => $_input_charset,                                //字符集，默认为GBK
//"trade_no" => $_POST["trade_no"] ,                      //商品交易号，必填,每次测试都须修改
//"logistics_name"  => $_POST["logistics_name"],     //物流公司名称
//"invoice_no"   => $_POST["invoice_no"],     //物流发货单号
//"transport_type"  => $_POST["transport_type"],     //发货时的运输类型填 POST, EXPRESS, EMS
//"seller_ip" => "127.0.0.1",               //卖家邮箱，必填
//);
//$alipay = new alipay_service($parameter,$security_code,$sign_type);
//
//$link=$alipay->create_url();
//
//header("Location: $link ");


/**
 * 支付宝发货接口
 *
 * @param string $trade_no 支付宝流水号
 * @param string $carrier_name 快递公司
 * @param string $bill_no 运单号
 * @param string $transport_type 货运类型，默认为EXPRESS
 * @return string 返回请求结果
 */
function alipay_shipping($trade_no, $carrier_name, $bill_no, $transport_type = 'EXPRESS') {
	require_once(ROOT_PATH . "admin/includes/alipay/alipay_service.php");
	require(ROOT_PATH . "admin/includes/alipay/alipay_config.php");
	$parameter = array(
	"service" => "send_goods_confirm_by_platform", //交易类型，必填实物交易＝trade_create_by_buyer
	"partner" =>$partner,                                               //合作商户号
	"return_url" =>$return_url,  //同步返回
	"notify_url" =>$notify_url,  //异步返回
	"_input_charset" => $_input_charset,                                //字符集，默认为GBK
	"trade_no" => $trade_no,                      //商品交易号，必填,每次测试都须修改
	"logistics_name" => $carrier_name,     //物流公司名称
	"invoice_no" => $bill_no,     //物流发货单号
	"transport_type" => $transport_type,     //发货时的运输类型填 POST, EXPRESS, EMS
	"seller_ip" => "127.0.0.1",               //卖家邮箱，必填
	);
	$alipay = new alipay_service($parameter,$security_code,$sign_type);
	$link = $alipay->create_url();
	$content = file_get_contents ($link);
	return $content;
}

function alipay_shipping_by_order_sn($order_sn) {
	global $db, $ecs;
	$sql = "
		SELECT *, c.name carrier_name FROM {$ecs->table('order_info')} o, {$ecs->table('pay_log')} pl, {$ecs->table('carrier_bill')} cb, {$ecs->table('carrier')} c
		WHERE
			o.order_id = pl.order_id
			AND o.carrier_bill_id = cb.bill_id
			AND cb.carrier_id = c.carrier_id
			AND o.order_sn = '{$order_sn}'
			AND cb.bill_no != ''
			AND o.pay_id = 5
	";
	$order = $db->getRow($sql);
	
	if ($order != null) {
		$match = array();
		preg_match("/\ntrade_no=([0-9]+)\n/", $order['request_data'], $match);
		$order['trade_no'] = $match[1];
		if ($order['trade_no'] != '')
			alipay_shipping($order['trade_no'], $order['carrier_name'], $order['bill_no']);
	}
}
?>