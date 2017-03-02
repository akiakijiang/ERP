<?php
/**
 * 淘宝外部订单导出
 * 
 */
define('IN_ECS', true);

require('../includes/init.php');
admin_priv('taobao_outside_shipped_order_export');

$act = $_REQUEST['act'];
$startTime = strtotime($_REQUEST['startDate']) ? $_REQUEST['startDate'] : null;
$endTime = strtotime($_REQUEST['endDate']) ? $_REQUEST['endDate'] : null;
$end_date_res = date("Y-m-d", strtotime($endTime) + 3600 * 24);

$application_list = get_taobao_shop_nicks();
$smarty->assign('application_list', $application_list);

if ($act == 'export') {
	$condition = getCondition();
	//查询订单
	$sql = "SELECT oi.tid, oi.buyer_nick, IF(og.outer_sku_id<>'',og.outer_sku_id,og.outer_iid) outer_id, og.title, 
				IF(gs.style_id IS NOT NULL,CONCAT_WS(',',g.goods_name,s.color),g.goods_name) gName, IF(gs.style_id IS NULL,g.barcode,gs.barcode) barcode, og.num, 
				oi.receiver_state, oi.receiver_city, oi.receiver_district, oi.receiver_address, oi.receiver_name, oi.receiver_mobile,oi.receiver_phone, oi.receiver_zip, oi.created, oi.pay_time
			FROM ecshop.sync_taobao_order_info oi INNER JOIN ecshop.sync_taobao_order_goods og ON oi.tid = og.tid
			LEFT JOIN ecshop.sync_taobao_items i ON og.num_iid = i.num_iid 
			LEFT JOIN ecshop.sync_taobao_items_sku tis on tis.sku_id=og.sku_id
			LEFT JOIN ecshop.ecs_goods g ON  g.goods_id = case when tis.goods_id<>'' then tis.goods_id else i.goods_id end
			LEFT JOIN ecshop.ecs_goods_style gs ON g.goods_id = gs.goods_id and gs.is_delete=0 and gs.style_id=case when tis.style_id<>'' then tis.style_id else i.style_id end
			LEFT JOIN ecshop.ecs_style s ON gs.style_id = s.style_id
			WHERE 1".$condition."
			ORDER BY oi.pay_time DESC";
	$orders = $db->getAll($sql);
    
	set_include_path(get_include_path() . PATH_SEPARATOR . '../includes/Classes/');
    require 'PHPExcel.php';
    require 'PHPExcel/IOFactory.php';
    
	$title = array(0 => array(
        '订单号', '旺旺号', '商家编码', '淘宝商品名称', 'ERP商品名称', '条码', '数量', '省', '市', '区',
        '地址', '收件人', '手机号码','座机号码', '邮编', '订单时间', '支付时间'
    ));
    $type = array(0 => 'string',);
    
    $filename = "";
    if ( $startTime != null &&  $endTime != null) {
		$filename .= "{$startTime}至{$endTime}_";
	}
    if ( !empty($_REQUEST['outer_id']) ) {
		$filename .= "商家编号(".$_REQUEST['outer_id'].")_";
	}
	$filename .= "淘宝外部订单_".date("Y-m-d").".xlsx";
    
    $excel = new PHPExcel();
    $sheet = $excel->getActiveSheet();
    $sheet->fromArray($title);
    $orders = array_map('array_values', $orders);
    if (!empty($orders)) {
        $sheet->fromArray($orders, null, 1, $type);
    }
    
    $output = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
    $output->setOffice2003Compatibility(true);
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    $output->save('php://output');
    
    exit();
	
}else {
	if($act == "search"){
		$startTime = strtotime($_REQUEST['startDate']) ? $_REQUEST['startDate'] : null;
		$endTime = strtotime($_REQUEST['endDate']) ? $_REQUEST['endDate'] : null;
		$sql_err="select stoi.tid from ecshop.sync_taobao_order_info stoi
			     inner join ecshop.sync_taobao_order_goods stog on stoi.tid=stog.tid
				 where pay_time >= '{$startTime}'
			      and pay_time < '{$end_date_res}'
			      and stoi.party_id = '{$_SESSION['party_id']}'
			       and stoi.SHIP_CODE = 'OUT_SHIP'
			      and stoi.status = 'WAIT_SELLER_SEND_GOODS'
			     group by stoi.tid having count(1)>1 ";
		$result_err = $db->getAll($sql_err);
		$smarty->assign('result_err',$result_err);	    
		
		$sql = "
			SELECT tsc.nick nick,count(stoi.tid) order_num, sum(stog.num) goods_num,(
			case 
			when stog.outer_sku_id<>'' then stog.outer_sku_id
			else stog.outer_iid
			end) outer_id,
			stog.title goods_name,'{$startTime}' startTime,'{$endTime}' endTime
			from ecshop.sync_taobao_order_info stoi
			inner join ecshop.sync_taobao_order_goods stog on stoi.tid = stog.tid
			inner join ecshop.taobao_shop_conf tsc on tsc.application_key = stoi.application_key
			where pay_time >= '{$startTime}'
			and pay_time < '{$end_date_res}'
			and stoi.party_id = '{$_SESSION['party_id']}'
			and stoi.SHIP_CODE = 'OUT_SHIP'
			and stoi.status = 'WAIT_SELLER_SEND_GOODS'
			group by stoi.application_key,outer_id
		";
		$result = $db->getAll($sql);
		$smarty->assign('result',$result);
	}
    $smarty->display('taobao/taobao_outside_shipped_order_export.htm');
}

function getCondition(){
	$startTime = strtotime($_REQUEST['startDate']) ? $_REQUEST['startDate'] : null;
	$endTime = strtotime($_REQUEST['endDate']) ? $_REQUEST['endDate'] : null;
	$end_date_res = date("Y-m-d", strtotime($endTime) + 3600 * 24);
	
	//取得条件
	$condition = " AND oi.status = 'WAIT_SELLER_SEND_GOODS' AND oi.ship_code = 'OUT_SHIP' AND oi.party_id = ".$_SESSION['party_id'];
//	if( !empty($_REQUEST['outer_id']) ){
	//分割outer_id，取得goods_id和style_id
	$goods_style_id = strtok($_REQUEST['outer_id'],'_'); 
	$goods_id = $goods_style_id;
	$goods_style_id = strtok(" ");
	$style_id = $goods_style_id;
	//echo "goods_id=".$goods_id."--style_id=".$style_id;
	/*
	if( strstr($goods_id,'TC-') == null ){
		$condition .= " AND g.goods_id = ".$goods_id;
		if( !empty($style_id) && $style_id != 0 ){
			$condition .= " AND gs.style_id = ".$style_id;
			$condition .= " AND og.outer_sku_id = '".$_REQUEST['outer_id']."'";
		}else{ //有样式的商品，如果没有输入样式则查不出结果
			$condition .= " AND gs.style_id is null";
			$condition .= " AND og.outer_iid = '".$_REQUEST['outer_id']."'";
		}
	}else{
		$condition .= " AND og.outer_iid = '".$_REQUEST['outer_id']."'";
	} */
	
	$condition .= "  AND (case when og.outer_sku_id<>'' then og.outer_sku_id else og.outer_iid end) = '".$_REQUEST['outer_id']."'"; 
//	}
	if ( $startTime ) {
		$condition .= " AND oi.pay_time >= '{$startTime}'";
	}
	if ( $endTime ) {
		$condition .= " AND oi.pay_time < '{$end_date_res}'";
	}
	
	return $condition;
}

/**
 * 取得淘宝店铺信息
 * 
 */
function get_taobao_shop_nicks() {
    $application_list = get_taobao_shop_list(); //取得taobao店铺信息
    $application_nicks = array();
    foreach ($application_list as $application) {
        $application_nicks[$application['application_key']] = $application['nick'];
    }
    return $application_nicks;
}

function get_taobao_shop_list() {
    global $db;
    $sql = "SELECT nick,application_key FROM ecshop.taobao_shop_conf WHERE shop_type = 'taobao' and party_id = '".$_SESSION['party_id']."'";
    $application_list = $db->getAll($sql);
    return $application_list;
}
?>