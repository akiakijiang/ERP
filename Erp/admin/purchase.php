<?php
/**
 * 采购订单
 */
define('IN_ECS', true);
require('includes/init.php');
admin_priv('purchase_order','ck_search_storage');
require_once(ROOT_PATH. "RomeoApi/lib_inventory.php");
require("function.php");
require_once('includes/lib_order.php');

$csv = $_REQUEST['csv'];
$act = $_REQUEST['act'];

$search_type = $_REQUEST['search_type'];
$search_types = array(
'order_sn'          => '订单号',
'goods_name'        => '商品名',
'consignee'         => '收货人',
'bill_no'           => '运单号',
'serial_number'      => '商品串号'
);

$size = 10;
$page = $_REQUEST['page'] ? $_REQUEST['page'] : 1;
$start = ($page - 1) * $size;

if ($csv === null) {
    $limit = "LIMIT $size";
    $offset = "OFFSET $start";
} else {
    admin_priv("admin_other_csv");
}

if ($act == 'search') {
    $condition = getCondition();
    $sqlc = "
		SELECT COUNT(*) 
		FROM {$ecs->table('order_info')} AS info 
		WHERE biaoju_store_id in (0, 7) {$condition}
	";
    $count = $db->getOne($sqlc);
    $pager = Pager($count, $size, $page);
    if ($count !== 0) {
        $sql_order = "
    		SELECT info.order_time, info.consignee, info.order_sn, info.order_id, info.party_id, 
				info.order_status, info.shipping_status, info.pay_status, info.order_amount, 
				info.goods_amount, info.shipping_fee, info.integral_money, info.bonus,poi.is_purchase_paid,
				poi.purchase_paid_type,poi.purchase_paid_amount,
				poi.purchase_paid_time,poi.purchaser,
				poi.purchase_invoice,poi.is_finance_paid,poi.cheque
    		FROM ecshop.ecs_order_info AS info 
    			left join romeo.purchase_order_info poi on info.order_id = poi.order_id
    		WHERE info.biaoju_store_id in (0, 7)
    			{$condition} 
    		ORDER BY info.order_time DESC $limit $offset";

        $sql_order_goods = "
        	select og.goods_name,og.goods_number,p.provider_name, GROUP_CONCAT(DISTINCT (ii.serial_number) SEPARATOR ',')
				from ecshop.ecs_order_goods og
				inner join romeo.inventory_item_detail iid on convert(og.rec_id using utf8) = iid.ORDER_GOODS_ID
				inner join romeo.inventory_item ii on iid.INVENTORY_ITEM_ID = ii.INVENTORY_ITEM_ID
				inner join ecshop.ecs_provider p on p.provider_id = ii.provider_id
				WHERE %s
			group by og.rec_id
		";

        $orders = get_order_details_list_by_sql($sql_order, $sql_order_goods, array());
    }
}else if($act == 'exportSerialNumber'){
	global $db;
	$order_id = $_REQUEST['order_id'];
	$order_sn = $_REQUEST['order_sn'];
	$title = array(0 =>array("商品名","商品串号"));
	$file_name = "订单{$order_sn}的商品串号";
	$sheetname = "商品串号";
	$sql = "SELECT DISTINCT goods_name, ii.serial_number from ecs_order_info info 
			LEFT JOIN ecs_order_goods eog on info.order_id = eog.order_id 
			LEFT JOIN romeo.inventory_item_detail iid on  iid.ORDER_GOODS_ID = convert(eog.rec_id USING utf8)
			LEFT JOIN romeo.inventory_item ii on ii.INVENTORY_ITEM_ID = iid.INVENTORY_ITEM_ID 
			WHERE info.order_id = {$order_id}";
	$data = $db ->getAll($sql);
	$smarty ->assign("data",$data);
	header("Content-type:application/vnd.ms-excel");
    header("Content-Disposition:filename=" . iconv("UTF-8","GB18030",$file_name) . ".csv");
    $out = $smarty->fetch('oukooext/serialNumber_xlsx.htm');
    echo iconv("UTF-8","GB18030", $out);
    exit();
}

$providers = getProviders();
$smarty->assign('back', $_SERVER['REQUEST_URI']);
$smarty->assign('admin_name', $_SESSION['admin_name']);
$smarty->assign('providers', $providers);
$smarty->assign('purchasePaidTypes',  $_CFG['adminvars']['purchase_paid_type']);
$smarty->assign('all_order_status', $_CFG['adminvars']['order_status']);
$smarty->assign('orders', $orders);
$smarty->assign('pager', $pager);
$smarty->assign('search_types', $search_types);
$smarty->assign('search_type', $search_type);
$smarty->assign('facilitys', get_available_facility());

if ($csv) {
    header("Content-type:application/vnd.ms-excel");
    header("Content-Disposition:filename=" . iconv("UTF-8","GB18030","采购报表") . ".csv");
    $out = $smarty->fetch('oukooext/purchase_csv.htm');
    echo iconv("UTF-8","GB18030", $out);
    exit();
} else {
    $smarty->display('oukooext/purchase.htm');
}
?>

<?php

function getCondition() {
    global $ecs, $db;

    $start = $_REQUEST['start'];
    $end = $_REQUEST['end'];
    
    $search_text = trim($_REQUEST['search_text']);
    $search_type = $_REQUEST['search_type'];
    $provider_id = $_REQUEST['provider_id'];
    $facility_id = $_REQUEST['facility_id'];

    $condition = ' AND '.party_sql('oi.party_id');

    if (strtotime($start) > 0) {
        $condition .= " AND order_time >= '$start'";
    }else{
    	$start = ''; 
    }

    if (strtotime($end) > 0) {
    	$end_time = strtotime($end); 
        $end = date('Y-m-d',strtotime('+1 day',$end_time)).""; 
        $condition .= " AND order_time <= '$end'";

        if($start ==''){
        	$start =  date('Y-m-d',strtotime('-7 day',$end_time)).""; 
    		$_REQUEST['start'] = $start; 
    		$condition .= " AND order_time >= '$start'"; 
        }
    }else{
    	if($start == ''){
    		$start =  date('Y-m-d',strtotime('-7 day')).""; 
    		$_REQUEST['start'] = $start; 
    		$condition .= " AND order_time >= '$start'"; 
    	}
    }

    if ($provider_id != null && $provider_id != -1) {
        $condition .= " AND ii.provider_id = '$provider_id'";
    }

    if ($facility_id != null && $facility_id != -1) {
    	$condition .= " AND oi.facility_id = '$facility_id'";
    }
	$sql0 = "SELECT DISTINCT oi.order_id 
			from ecshop.ecs_order_info oi force index (PRIMARY,order_sn,order_info_multi_index)
			left join romeo.purchase_order_info poi on oi.order_id = poi.order_id
			-- left join ecshop.ecs_carrier_bill cb on oi.carrier_bill_id = cb.bill_id
            LEFT JOIN romeo.order_shipment os ON os.order_id = CONVERT (oi.order_id USING utf8)
            LEFT JOIN romeo.shipment s ON os.shipment_id = s.shipment_id
			left join ecshop.ecs_order_goods og on oi.order_id = og.order_id ";
    $sql1_ii = " left join romeo.inventory_item_detail iid on CAST( iid.order_goods_id AS UNSIGNED ) = og.rec_id ";
    $sql1 = " left join romeo.inventory_item_detail iid on iid.order_goods_id = convert(og.rec_id USING UTF8) ";
	$sql2 = " left join romeo.inventory_item ii on iid.inventory_item_id = ii.inventory_item_id
			where 1 ".$condition;

    if ($search_text != '') {
    	$search_condition = '';
        if (in_array($search_type, array('order_sn','consignee'))) {
            $search_condition .= " AND oi.{$search_type} = '{$search_text}' ";
        } else if ($search_type == 'bill_no') {
            // $search_condition = " AND cb.{$search_type} = '{$search_text}' ";
            $search_condition = " AND s.tracking_number = '{$search_text}' ";
        } else if ($search_type == 'serial_number'){
        	$search_condition .= " AND ii.{$search_type} = '{$search_text}' ";
            $sql1=$sql1_ii;
        } else if ($search_type == 'goods_name'){
            $search_condition .= " AND og.{$search_type} = '{$search_text}' ";
        }
        $sql=$sql0.$sql1.$sql2.$search_condition;
    }else{
        $sql=$sql0.$sql1.$sql2;
    }
    $sql.=" AND (s.STATUS is null or s. STATUS != 'SHIPMENT_CANCELLED')
    ";
    $order_ids = $db->getCol($sql);
    
	return " AND ". db_create_in($order_ids, "info.order_id");
}


?>
