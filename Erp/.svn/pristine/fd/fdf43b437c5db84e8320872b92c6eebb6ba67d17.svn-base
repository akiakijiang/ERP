<?php
require_once(ROOT_PATH . 'includes/debug/lib_log.php');
define ( 'IN_ECS', true );

$_REQUEST ['act'] = $_REQUEST ['act'] ? $_REQUEST ['act'] : 'list';
$csv = $_REQUEST ['csv'];

/*
 * 订单查询
 * */
 // var_dump( $_REQUEST  );
function get_searchorder() {
	
	$erp_search_text = trim ( $_REQUEST ['erp_order_sn'] );
	$taobao_search_text = trim ( $_REQUEST ['taobao_order_sn'] );
	// $other_search_type = trim ( $_REQUEST ['search_type'] );
	// $other_search_text = trim ( $_REQUEST ['search_text'] );
	$shop_type = $_REQUEST['shop_type'];

	$user_name = trim($_REQUEST['user_name']);
    $consignee = trim($_REQUEST['consignee']);

	
	$start_Calendar = sqlSafe ( $_REQUEST ['startCalendar'] );
	$end_Calendar = sqlSafe ( $_REQUEST ['endCalendar'] );
	
	$shipping_name = ( $_REQUEST['shipping_name']);
	$pay_name = ( $_REQUEST['pay_name']);
	
	$shipping_id = sqlSafe ( $_REQUEST ['shipping_id'] );
	$order_status = sqlSafe ( $_REQUEST ['order_status'] );
	$shipping_status = sqlSafe ( $_REQUEST ['shipping_status'] );
	$pay_status = sqlSafe ( $_REQUEST ['pay_status'] );
	$facility_id = sqlSafe ( $_REQUEST ['facility_id'] );
	$goods_name = sqlSafe ( $_REQUEST ['goods_name'] );
	$order_type = $_REQUEST ['order_type'];
	$pay_id = $_REQUEST ['pay_id'];
	
	$platform_select = trim($_REQUEST['platform_select']);
	$shop_name = trim($_REQUEST['shop_name']);
	$distributor_id = ($_REQUEST['distributor_id']);
	$goods_id = ($_REQUEST['goods_id']);
	$goods_number = trim($_REQUEST['goods_number']);

	//订单来源改为订单类型
	//$outer_type = trim($_REQUEST['outer_type']);
	//$distribution_type = trim($_REQUEST['distribution_type']);
	//$red_notice = intval($_REQUEST['red_notice']);
	
	if($platform_select != '') {
		$sqladd .= " AND oa.attr_name = 'OUTER_TYPE' AND oa.attr_value = '{$platform_select}' ";
	}
	
	if($shop_name != '' && $distributor_id) {
		$sqladd .= " AND  info.distributor_id = '{$distributor_id}' ";
	}
	
	if($goods_id && $goods_name != '') {
		$sqladd .= " AND  eog.goods_id = '{$goods_id}' ";
	}
	
	if($goods_number != '') {
		$sqladd .= " AND  eog.goods_number = '{$goods_number}' ";
	}

	if ($erp_search_text != '') {
		$sqladd .= " AND info.order_sn LIKE '{$erp_search_text}%' ";
	}
	
	if ($taobao_search_text != '') {
		$sqladd .= " AND info.taobao_order_sn LIKE '{$taobao_search_text}%' ";
	}

	if ($user_name != '') {
		$sqladd .= " AND info.nick_name like '{$user_name}%' ";
	}
	if($consignee  != ''){
		$consignee = str_replace ( " ", "", $consignee );
		$sqladd .= " AND info.consignee LIKE '{$consignee}%' ";
	}
	
	if (strtotime ( $start_Calendar ) > 0) {
		$start_Calendar = date ( "Y-m-d H:i:s", strtotime ( $start_Calendar ) );
		$sqladd .= " AND info.order_time >= '$start_Calendar'";
	}
	if (strtotime ( $end_Calendar ) > 0) {
		$end_Calendar = date ( "Y-m-d H:i:s", strtotime ( $end_Calendar ) );
		$sqladd .= " AND info.order_time <= '$end_Calendar'";
	}
	if ($shipping_id && $shipping_name != '') {
		$sqladd .= " AND info.shipping_id = '$shipping_id'";
	}
	if ($facility_id) {
		$sqladd .= " AND info.facility_id = '$facility_id'";
	}
	
	if ($order_status != - 1 && $order_status !== null && $order_status != 12) {
		$sqladd .= " AND info.order_status = '$order_status'";
	}
	
	if($order_status == 12) {	//未预定成功
		$sqladd .= " AND  r.status = 'N' ";
	}
	
	if ($shipping_status != - 1 && $shipping_status !== null) {
		// 已确认，待预定
		if ($shipping_status == 15) {
			$sqladd .= " AND info.order_status = 1 AND info.shipping_status = 0 
    		AND not exists (select 1 from romeo.order_inv_reserved r 
    		where r.order_id = info.order_id and r.status in('Y','F') limit 1) ";
		} else {
			$sqladd .= " AND info.shipping_status = '$shipping_status'";
		}
	}
	if ($pay_status != - 1 && $pay_status != null) {
		$sqladd .= " AND info.pay_status = '$pay_status'";
	}
	if ($pay_id != - 1 && $pay_id != null && $pay_id != '' && $pay_name != '') {
		$sqladd .= " AND info.pay_id = '$pay_id'";
	}
	
	if($shop_type == 'zhixiao'){
	    $sqladd .= " AND md.type = 'zhixiao' ";
	}elseif($shop_type == 'fenxiao'){
	    $sqladd .= " AND md.type = 'fenxiao' ";
	}
	
	if($order_type == 'yes'){
	    $sqladd .= " AND info.postscript <> '' ";
	}elseif($order_type == 'no'){
	    $sqladd .= " AND info.postscript = '' ";
	}
		return $sqladd;

}



function getHistoryorderCondition($args) {
	
	$erp_search_text = trim ( $_REQUEST ['erp_order_sn'] );
	$taobao_search_text = trim ( $_REQUEST ['taobao_order_sn'] );
	// $other_search_text = trim ( $args ['search_text'] );
	// $other_search_type = trim ( $args ['search_type'] );

	$user_name = trim($_REQUEST['user_name']);
    $consignee = trim($_REQUEST['consignee']);

	
	$startCalendar = sqlSafe ( $args ['startCalendar'] );
	$endCalendar = sqlSafe ( $args ['endCalendar'] );
	
	$shipping_name = ( $_REQUEST['shipping_name']);
	$pay_name = ( $_REQUEST['pay_name']);
	$shipping_id = sqlSafe ( $args ['shipping_id'] );
	$facility_id = sqlSafe ( $args ['facility_id'] );
	$order_status = sqlSafe ( $args ['order_status'] ); //订单状态
	$pay_status = sqlSafe ( $args ['pay_status'] ); //收款状态
	$shipping_status = sqlSafe ( $args ['shipping_status'] ); //发货状态
	$pay_id = sqlSafe ( $args ['pay_id'] ); //收款方式
	
	if ($erp_search_text != '') {
		$hisOrdcondition .= " AND info.order_sn LIKE '{$erp_search_text}%' ";
	}
	
	if ($taobao_search_text != '') {
		$hisOrdcondition .= "AND info.taobao_order_sn LIKE '{$taobao_search_text}%'";
	}
	

	if($user_name  != ''){
		$hisOrdcondition .= " AND info.nick_name LIKE '{$user_name}%'";
	}
	if($consignee  != ''){
		$hisOrdcondition .= " AND info.consignee LIKE '{$consignee}%' ";
	}


	if (strtotime ( $startCalendar ) > 0) {
		$startCalendar = date ( "Y-m-d H:i:s", strtotime ( $startCalendar ) );
		$hisOrdcondition .= " AND info.order_time >= '$startCalendar'";
	}
	if (strtotime ( $endCalendar ) > 0) {
		$endCalendar = strftime ( '%Y-%m-%d', strtotime ( "+1 day", strtotime ( $endCalendar ) ) );
		$hisOrdcondition .= " AND info.order_time <= '$endCalendar'";
	}
	if ($shipping_id && $shipping_name != '') {
		$hisOrdcondition .= " AND info.shipping_id = '$shipping_id'";
	}
	if ($facility_id) {
		$hisOrdcondition .= " AND info.facility_id = '$facility_id'";
	}
	
	if ($order_status != - 1 && $order_status !== null) {
		$hisOrdcondition .= " AND info.order_status = '$order_status'";
	}
	
	if ($shipping_status != - 1 && $shipping_status !== null) {
		$hisOrdcondition .= $shipping_status == 15 ? " AND info.order_status = 1 AND info.shipping_status = 0 " . " AND EXISTS (SELECT 1 FROM romeo.order_inv_reserved r where r.order_id = info.order_id AND r.status = 'N')" : " AND info.shipping_status = '{$shipping_status}'";
	}
	if ($pay_status != - 1 && $pay_status !== null) {
		$hisOrdcondition .= " AND info.pay_status = '$pay_status'";
	}
	if ($pay_id != - 1 && $pay_id !== null && $pay_id != '' && $pay_name != '') {
		$hisOrdcondition .= " AND info.pay_id = '$pay_id'";
	}
	return $hisOrdcondition;
}

function get_shipping_types($conditon = "",$limit = 100) {
    global $db, $ecs;	
	if(trim ( $conditon )){
	 	$conditon = mysql_like_quote ( $conditon );
	 	$condition .= " AND s.shipping_name LIKE '%{$conditon}%' ";
	 }
    $sql = "SELECT * FROM {$ecs->table('shipping')} as s  WHERE enabled = 1 $condition ORDER BY shipping_code, support_cod limit {$limit}";
    $rs = $db->query($sql);
    $shipping_types = Array();
    while ($row = $db->fetchRow($rs)) {
        if (!party_check($row['party_id'], $_SESSION['party_id'])) {
            continue;
        }
        $shipping_types[] = $row;
    }
    return $shipping_types;
}

function get_pay_type($conditon = "",$limit = 100) {
	global $db, $ecs;	
	if(trim ( $conditon )){
	 	$conditon = mysql_like_quote ( $conditon );
	 	$condition .= " AND p.pay_name LIKE '%{$conditon}%' ";
	 }
    $sql = "SELECT *, IF(enabled=1, pay_name, CONCAT(pay_name, ' (已挂起)')) AS pay_name FROM {$ecs->table('payment')} as p  WHERE (enabled = 1 OR enabled_backend = 'Y') $condition ORDER BY pay_order limit {$limit}";
    $rs = $db->query($sql);
    $payments = Array();
    while ($row = $db->fetchRow($rs))
    {
        $payments[] = $row;
    }
    return $payments;
}

//模糊搜索店铺名称
function get_shop_name($conditon = "",$limit = 100) {
	global $db;
	if(trim ( $conditon )){
		$conditon = mysql_like_quote ( $conditon );
		$condition .= " AND name LIKE '%{$conditon}%' ";
	}
	$sql = "SELECT * FROM ecshop.distributor  WHERE party_id = {$_SESSION['party_id']} and status='NORMAL'  $condition limit {$limit}";
	$rs = $db->query($sql);
	$shop_names = Array();
	while ($row = $db->fetchRow($rs))
	{
		$shop_names[] = $row;
	}
	return $shop_names;
}

//模糊搜索商品名称
function get_goods_name($conditon = "",$limit = 100) {
	global $db, $ecs;
	if(trim ( $conditon )){
		$conditon = mysql_like_quote ( $conditon );
		$condition .= " AND goods_name LIKE '%{$conditon}%' ";
	}
	$sql = "SELECT goods_name,goods_id FROM {$ecs->table('goods')}  WHERE goods_party_id = {$_SESSION['party_id']} $condition limit {$limit}";
	Qlog::log($sql);
	$rs = $db->query($sql);
	$goods_names = Array();
	while ($row = $db->fetchRow($rs))
	{
		$goods_names[] = $row;
	}
	return $goods_names;
}

?>