<?php
/**
 * 下采购订单
 */
define('IN_ECS', true);
require('includes/init.php');
require('function.php');
include_once('../RomeoApi/lib_currency.php');
require_once ('includes/cls_json.php');
require_once ('includes/debug/lib_log.php');
// 通用商品组织权限特殊判断 ljzhou 2013.07.03
if(check_goods_common_party()) {
	admin_priv('cg_generate_c_order_common');
} else {
    admin_priv('cg_generate_c_order');
}

$csv = $_REQUEST['csv'];
$export = $_REQUEST['export'];
if ($csv) {
    admin_priv('5cg_generate_c_order_csv');
}
if($_SESSION['party_id'] != 65625){
	die('此功能仅中粮业务组可用');
}

$act = isset($_REQUEST['act']) ? trim($_REQUEST['act']) : null;
if($act == 'check_gt'){
	$json = new JSON();
	$rec_id = isset($_REQUEST['rec_id'])?trim($_REQUEST['rec_id']):false;
	if($rec_id){
		$sql = "
			select eoi.order_sn
			from romeo.inventory_item_detail iid 
			inner join ecshop.ecs_order_goods eog on iid.order_id = eog.order_id
			inner join romeo.inventory_item ii on iid.INVENTORY_ITEM_ID = ii.INVENTORY_ITEM_ID
			inner join romeo.inventory_item gtii on gtii.ROOT_INVENTORY_ITEM_ID = ii.ROOT_INVENTORY_ITEM_ID
			inner join romeo.inventory_item_detail gtiid on gtiid.INVENTORY_ITEM_ID = gtii.INVENTORY_ITEM_ID
			inner join ecshop.ecs_order_info eoi on cast(eoi.order_id as char(20)) = gtiid.order_id
			where eoi.order_type_id = 'SUPPLIER_RETURN'
			and eog.rec_id = '{$rec_id}'
			and eoi.party_id = '{$_SESSION['party_id']}'
			GROUP BY eoi.order_sn
		";
		$order_sns = $db->getCol($sql);
		$result = "";
		foreach ($order_sns as $order_sn){
			$result .= $order_sn.",";
		}
	}
	print $json->encode($result);
	exit;
}else if($act == 'update_unit_price'){
	$json = new JSON();
	$rec_id = isset($_REQUEST['rec_id'])?trim($_REQUEST['rec_id']):false;
	$pre_price = isset($_REQUEST['pre_price'])?trim($_REQUEST['pre_price']):false;
	$update_price = isset($_REQUEST['update_price'])?trim($_REQUEST['update_price']):false;
	if($update_price != $pre_price){
		if($rec_id && $update_price){
			$sql = "select og.order_id,og.goods_id,og.style_id,boi.provider_id,boi.order_type
						from ecshop.ecs_order_goods og
						inner join ecshop.ecs_batch_order_mapping bom on og.order_id = bom.order_id 
						inner join ecshop.ecs_batch_order_info boi on bom.batch_order_id = boi.batch_order_id
				where og.rec_id = '{$rec_id}' ";
			$row = $db->getRow($sql);
			$goods_id = $row['goods_id'];
			$style_id = $row['style_id'];
			$orderId = $row['order_id'];
			$provider_id = $row['provider_id'];
			$acctType = $row['order_type'];
		    $userName = $_SESSION['admin_name'];
			include_once(ROOT_PATH . "RomeoApi/lib_inventory.php");
			$goods_and_style = array('goods_id'=> $goods_id, 'style_id'=>$style_id);
	        $result = updateInventoryItemValueByOrderProduct($goods_and_style, $orderId, $acctType, $update_price);
	        if($result){
                // 把供价记录到价格跟踪系统中去
//                $sql = "SELECT user_id FROM oukoo_universal.ok_user WHERE user_name = '{$_SESSION['admin_name']}'";
//                $uuid = $db->getOne($sql);
//                $sql = "INSERT INTO PRICE_TRACKER.PROVIDER_PRICE (GOODS_ID, goods_style_id, PROVIDER_ID, SUPPLY_PRICE, CREATED_BY, CREATED_DATETIME) 
//                		  VALUE ('{$goods_id}', '{$style_id}', '{$provider_id}', '{$update_price}', '$uuid', NOW())";
//                $db->query($sql);
	        	print $json->encode(array('status' => $result));
	        }
		}
	}
    exit;
}else if($act == 'update_provider'){
	$json = new JSON();
	$order_id = isset($_REQUEST['order_id'])?trim($_REQUEST['order_id']):false;
	$update_provider_id = isset($_REQUEST['update_provider_id'])?trim($_REQUEST['update_provider_id']):false;
	if(empty($update_provider_id)){
		print $json->encode(array('status' => false));
		exit;
	}
	include_once(ROOT_PATH . "RomeoApi/lib_inventory.php");
	$result = updateProviderIdByOrder($order_id, $update_provider_id);
	print $json->encode(array('status' => $result));
	exit;
}else if($act == 'update_batch_pencentage'){
	$json = new JSON();
	$batch_order_sn = isset($_REQUEST['batch_order_sn'])?trim($_REQUEST['batch_order_sn']):false;
	$pre_pencentage = isset($_REQUEST['pre_pencentage'])?trim($_REQUEST['pre_pencentage']):false;
	$update_pencentage = isset($_REQUEST['update_pencentage'])?trim($_REQUEST['update_pencentage']):false;
	if($pre_pencentage != $update_pencentage){
		if($batch_order_sn && $update_pencentage){
			$sql = "select oi.order_sn,og.order_id,og.goods_id,og.style_id,boi.provider_id,boi.order_type,ii.UNIT_COST,ivh.unit_cost as init_cost
						from ecshop.ecs_order_goods og 
						inner join ecshop.ecs_order_info oi on og.order_id = oi.order_id
						inner join ecshop.ecs_batch_order_mapping bom on og.order_id = bom.order_id 
						inner join ecshop.ecs_batch_order_info boi on bom.batch_order_id = boi.batch_order_id
						INNER JOIN romeo.inventory_item_detail iid ON iid.ORDER_ID = CONVERT(og.order_id using utf8)
						INNER JOIN romeo.inventory_item ii ON ii.inventory_item_id = iid.inventory_item_id 
						LEFT JOIN romeo.inventory_item_value_history ivh ON ivh.inventory_item_id = ii.inventory_item_id
				where boi.batch_order_sn = '{$batch_order_sn}' GROUP BY oi.order_id
				ORDER BY ivh.last_updated_stamp ";
			$batch_orders = $db->getAll($sql);
			$userName = $_SESSION['admin_name'];
//	        $sql = "SELECT user_id FROM oukoo_universal.ok_user WHERE user_name = '{$_SESSION['admin_name']}'";
//	        $uuid = $db->getOne($sql);
	        $init_UN_unit_cost = array();
			$db->start_transaction();
			foreach($batch_orders as $order){
				$update_price = sprintf("%01.6f", round($order['init_cost']*$update_pencentage, 6));
				include_once(ROOT_PATH . "RomeoApi/lib_inventory.php");
				$goods_and_style = array('goods_id'=> $order['goods_id'], 'style_id'=>$order['style_id']);
		        $result = updateInventoryItemValueByOrderProduct($goods_and_style, $order['order_id'], $order['order_type'], $update_price);
		        if($result){
//	                $sql1 = "INSERT INTO PRICE_TRACKER.PROVIDER_PRICE (GOODS_ID, goods_style_id, PROVIDER_ID, SUPPLY_PRICE, CREATED_BY, CREATED_DATETIME) 
//	                		  VALUE ('{$order['goods_id']}', '{$order['style_id']}', '{$order['provider_id']}', '{$update_price}', '$uuid', NOW())";
	                $sql2 = "UPDATE ecshop.ecs_batch_order_info set percentage = '{$update_pencentage}' where batch_order_sn = '{$batch_order_sn}'";		  
	                if(!($db->query($sql2))){
	                	$db->rollback();
	                	print $json->encode(array('status' => false));
	                	exit;
	                }
		        }
		        if(abs($order['UNIT_COST']-sprintf("%01.6f ", round($order['init_cost']*$pre_pencentage,6)))>=0.001){
		        	$init_UN_unit_cost[] = $order['order_sn'];
		        }
			}
			 QLog::log("操作人：".$userName."--批次改折扣采购价的batch_order_sn:".$batch_order_sn.", 最新折扣价：".$update_pencentage);
			$db->commit();
			if(!empty($init_UN_unit_cost)){
				print $json->encode(array('status' => true,'detail' => '订单号'.implode(",",$init_UN_unit_cost).'进行过微调，请注意核对'));
			}else{
				print $json->encode(array('status' =>true,'detail' => ''));
			}
		}
	}
	exit;
}else if($act == 'update_batch_provider'){
	$json = new JSON();
	$batch_order_sn = isset($_REQUEST['batch_order_sn'])?trim($_REQUEST['batch_order_sn']):false;
	$pre_batch_provider_id = isset($_REQUEST['pre_batch_provider_id'])?trim($_REQUEST['pre_batch_provider_id']):false;
	$update_batch_provider_id = isset($_REQUEST['update_batch_provider_id'])?trim($_REQUEST['update_batch_provider_id']):false;
	if(empty($update_batch_provider_id)){
		print $json->encode(array('status' => false));
		exit;
	}
	global $db;
    $sql = " SELECT ii.inventory_item_id from romeo.inventory_item ii
		LEFT JOIN romeo.inventory_item_detail iid on iid.INVENTORY_ITEM_ID = ii.INVENTORY_ITEM_ID
		LEFT JOIN ecshop.ecs_batch_order_mapping bom ON CONVERT(bom.order_id USING utf8) = iid.order_id
		LEFT JOIN ecshop.ecs_batch_order_info boi ON boi.batch_order_id = bom.batch_order_id
		where boi.batch_order_sn = '{$batch_order_sn}' ";
    $inventory_item_ids = $db->getCol($sql);
    if(!empty($inventory_item_ids)) {
        $sql1 = "update romeo.inventory_item set last_updated_stamp=now(),provider_id = '{$update_batch_provider_id}' where inventory_item_id in ('".implode("','",$inventory_item_ids)."') ";
    	$sql2 = "update ecshop.ecs_batch_order_info set provider_id = '{$update_batch_provider_id}' where batch_order_sn = '{$batch_order_sn}' ";
    	if(!($db->query($sql1) &&$db->query($sql2))){
           	print $json->encode(array('status' => false));
			exit;
        }
        Qlog::log("操作人：".$_SESSION['admin_name']."--批次修改供应商的batch_order_sn:".$batch_order_sn.", provider_id变化：".$pre_batch_provider_id."-->".$update_batch_provider_id);
    } else {
    	print $json->encode(array('status' => false));
		exit;
    }
	print $json->encode(array('status' => true));
	exit;
}else if($act == 'template'){
	$tpl = array('采购项目'  =>
				array('barcode'=>'商品条码',
					  'goods_number'=>'数量',
				));
	export_excel_template($tpl);
}


// 导出Xml
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST) && $_POST['act'] == 'xml')
{
    if (!empty($_POST['orders']) && is_array($_POST['orders'])) 
    {
        // 生成XML文档
        $doc = new DOMDocument("1.0", "UTF-8");
        $doc->formatOutput = true;
        
        // 根节点
        $root = $doc->appendChild(new DOMElement("ns:OUKU_ASN", NULL, "http://www.ouku.com"));
        $asn = $root->appendChild(new DOMElement("Asn"));
        
        $header = $asn->appendChild(new DOMElement("AsnHeader"));
        $header_1 = $header->appendChild(new DOMElement("code"));
        $header_1->appendChild(new DOMText(microtime(true)));
        $header_2 = $header->appendChild(new DOMElement("warehouseCode"));
        $header_2->appendChild(new DOMText(21));
        
        
        foreach ($_POST['orders'] as $order) {
            if (!$order['selected']) {
                continue;
            }
            
            $detail = $asn->appendChild(new DOMElement("AsnDetail"));
            
            $detail_1 = $detail->appendChild(new DOMElement("skuCode"));
            $detail_1->appendChild(new DOMText($order['product_code']));
            
            $detail_2 = $detail->appendChild(new DOMElement("expectedQty"));
            $detail_2->appendChild(new DOMText($order['goods_number']));
        }

        if (!headers_sent()) {
            header('Content-Type: text/xml');
            header("Content-Disposition: attachment; filename=". date('Y-m-d') .".xml; charset=UTF-8");
            header('Cache-Control: public, must-revalidate, max-age=0');
            $doc->save("php://output");
            exit;
        }
    }
}


$status_ids = array('INV_STTS_AVAILABLE'=>'良品（全新）','INV_STTS_USED'=>'不良品（二手）');
$smarty->assign('status_ids', $status_ids);

$providers =  get_zhongliang_provider();
$smarty->assign('providers', $providers);

// 能入二手库的组织
$can_in_used = in_array($_SESSION['party_id'],get_in_used_partys());
$smarty->assign('can_in_used', $can_in_used);

$purchasers = array('xfeng', 'wwang', 'lunux', 'qlliu', 'yy', 'others');
$smarty->assign('purchasers', $purchasers);

//  目前只有香港平世的才会是非人民币的币种
$currencies = ('65536' == $_SESSION['party_id']) ? array('HKD' => '港币', 'RMB' => '人民币') : array('RMB' => '人民币');
$smarty->assign('currencies', $currencies);

$condition = get_condition();

//搜索采购订单
$sql = "
    SELECT 
        o.*, og.*, ifnull(ii.provider_id,boi.provider_id) as provider_id, p.applied_rebate, o.facility_id,boi.is_over_c,poi.is_purchase_paid,boi.percentage,
        func_get_goods_category_name(g.top_cat_id, g.cat_id) as category,ifnull(boi.batch_order_sn,'') as batch_order_sn, ifnull(boi.batch_order_id,'') as batch_order_id, ifnull(boi.provider_id,ii.provider_id) as batch_provider_id,
        ifnull((select count(*) from {$ecs->table('batch_order_mapping')} om1 where om1.batch_order_id = om.batch_order_id limit 1),0) as count_order,
    	boi.provider_order_sn, boi.provider_out_order_sn, boi.remark,boi.check_status
    FROM 
        {$ecs->table('order_info')}  o 
        left join romeo.purchase_order_info poi on o.order_id = poi.order_id
        LEFT JOIN {$ecs->table('batch_order_mapping')} om ON o.order_id = om.order_id
        LEFT JOIN {$ecs->table('batch_order_info')} boi ON boi.batch_order_id = om.batch_order_id
        LEFT JOIN {$ecs->table('order_goods')} og ON o.order_id = og.order_id
        LEFT JOIN {$ecs->table('goods')} AS g ON g.goods_id = og.goods_id 
        LEFT JOIN `purchase_order_applied_rebate` AS p ON p.order_id = og.order_id
        LEFT JOIN ecshop.order_attribute oa ON o.order_id = oa.order_id and oa.attr_name = 'gymboree_vouchID'
        LEFT JOIN romeo.inventory_item_detail iid on iid.order_id = convert(o.order_id using utf8)
        LEFT JOIN romeo.inventory_item ii on iid.inventory_item_id = ii.inventory_item_id 
	WHERE
        o.order_type_id = 'PURCHASE' {$condition['order_condition']}
        {$condition['condition']}
    GROUP BY order_sn
    ORDER BY order_time DESC";
$ref_fields = $ref_orders = array();
$search_orders = $db->getAllRefby($sql, array('order_id'), $ref_fields, $ref_orders, false);
if ($search_orders) {
    $in_order_ids = db_create_in($ref_fields['order_id']);
    
    //统计新库存入库数量
    $sql = " select oi.order_id,ii.unit_cost as purchase_paid_amount,
	  sum(if(iid.quantity_on_hand_diff is not null and iid.quantity_on_hand_diff > 0,iid.quantity_on_hand_diff,0)) as new_in_num
	    from ecshop.ecs_order_info oi
		left join romeo.inventory_item_detail iid ON convert(oi.order_id using utf8) = iid.order_id
		left join romeo.inventory_item ii ON iid.inventory_item_id = ii.inventory_item_id
		where 
		  oi.order_id {$in_order_ids} and ii.status_id = 'INV_STTS_AVAILABLE'
		group by oi.order_id ";
     $refs_temp_new = $refs_count_new = array();
     $db->getAllRefby($sql, array('order_id'), $refs_temp_new, $refs_count_new, true);
    // 判断废除
	$sql = "select
                order_id, 1 as canceled
        from {$ecs->table('batch_order_mapping')}
            where is_cancelled = 'Y' and order_id {$in_order_ids} ";
    $refs_temp2 = $refs_canceled = array();
    $db->getAllRefby($sql, array('order_id'), $refs_temp2, $refs_canceled, false);
    // 组装订单数据
    foreach($ref_orders['order_id'] as $order_id => $group) {
        $order = & $ref_orders['order_id'][$order_id][0];
        $order['new_in_count'] = $refs_count_new['order_id'][$order_id][0]['new_in_num'] ? $refs_count_new['order_id'][$order_id][0]['new_in_num'] : 0;
        $order['purchase_paid_amount'] = $refs_count_new['order_id'][$order_id][0]['purchase_paid_amount'] ? $refs_count_new['order_id'][$order_id][0]['purchase_paid_amount'] : 0;
        $order['canceled'] = $refs_canceled['order_id'][$order_id][0]['canceled'] == 1;
        $order['facility_name'] = facility_mapping($order['facility_id']);
        $order['product_code'] = encode_goods_id($order['goods_id'], $order['style_id']);
    }
}

for($i=0;$i<count($search_orders); ){
	$order_0 = $search_orders[$i];
	$count_order = $order['count_order'];
	$order_0['is_purchase_paid_total'] = 'N';
	for($j=0;$j<$count_order;$j++){
		$order_1 = $search_orders[$i+$j];
		if(!($order_1['is_purchase_paid'] != 'YES' && $order_1['new_in_count']>0)){
			$order_0['is_purchase_paid_total'] = 'YES';
			break;
		}
	}
	for($j=0;$j<$count_order;$j++){
		$search_orders[$i+$j]['is_purchase_paid_total'] = $order_0['is_purchase_paid_total'];
	}
	$i = $i+$count_order;
}

$smarty->assign('search_orders', $search_orders);

if($export == "1"){
    header("Content-type:application/vnd.ms-excel;charset=utf-8");
    header("Content-Disposition:filename=" . iconv("UTF-8","GB18030","金宝贝采购订单项") . ".csv");
	$filename = $_REQUEST['filename'];
	$filename = ROOT_PATH."../gymboree/".$filename.".xml";
	ob_start();
	$header_str =  iconv("UTF-8",'GB18030',"商品ID,尺码,数量,条码,顺序号,零售价,零售金额,是否完成,累计入库数量 ,商品编码 , 样式条码\n");
	$file_str="";

    $smarty->assign('available_facility', get_available_facility());
	$xml = simplexml_load_file($filename);
	foreach ( $xml->InOutVouchDetail->Row as $InOutVouchDetail){		
		$file_str.= $InOutVouchDetail->fchrItemID.",";
		$file_str.= $InOutVouchDetail->fchrFree2.",";
		$file_str.= $InOutVouchDetail->flotQuantity.",";
		$file_str.= $InOutVouchDetail->fchrBarCode.",";
		$file_str.= $InOutVouchDetail->ftinOrder.",";
		$file_str.= $InOutVouchDetail->flotQuotePrice.",";
		$file_str.= $InOutVouchDetail->flotMoney.",";
		$file_str.= $InOutVouchDetail->fbitFinish.",";
		$file_str.= $InOutVouchDetail->flotInQuantity.",";
		$file_str.= $InOutVouchDetail->fchrItemCode.",";
		$sql = "
			select fchrBarCodeNO from ecshop.brand_gymboree_product 
			where fchrItemID = '{$InOutVouchDetail->fchrItemID}' 
			and fchrFree2 = '{$InOutVouchDetail->fchrFree2}';
		";
		$barcode = $db->getOne($sql);
		$file_str.= $barcode."\n";
	}	
	$file_str=  iconv("utf-8",'gbk',$file_str);
	ob_end_clean();
	echo $header_str;
	echo $file_str;
    exit();
}


if ($csv == "1" ) {
    admin_priv('5cg_generate_c_order_csv');
    header("Content-type:application/vnd.ms-excel");
    header("Content-Disposition:filename=" . iconv("UTF-8","GB18030","库存订单") . ".csv");
    $out = $smarty->fetch('oukooext/generate_c_order_csv.htm');
    echo iconv("UTF-8","GB18030", $out);
    exit();
}else {

    $smarty->assign('available_facility',array_intersect_assoc(get_available_facility(),get_user_facility()));
    $smarty->assign('super_update_purchase', check_admin_priv('cg_super_update_purchase'));
    $smarty->display('oukooext/generate_cofco_c_order.htm');
}

 /**
  * 能入二手库的组织
  */
 function get_in_used_partys() {
 	$party_ids = array('65625');
 	return $party_ids;
 }
 
 /**
  * 中粮供应商
  * */
 function get_zhongliang_provider(){
 	global $db;
 	$sql = " SELECT * FROM ecshop.ecs_provider WHERE provider_name like '%中粮%'";
 	$providers = $db->getAll($sql);
 	return $providers ? $providers : array();
 }
function get_condition() {
    global $smarty;
     
    $condition = array('order_condition' => '', 'condition' => '');

    $act = $_REQUEST['act'];
    $order_sn = trim($_REQUEST['order_sn']);
    $goods_name = trim($_REQUEST['goods_name']);
    // $goods_cagetory = trim($_REQUEST['goods_cagetory']);
    $purchase_paid_amount = $_REQUEST['purchase_paid_amount'];
    $provider_id = $_REQUEST['provider_id'];
    $order_type = $_REQUEST['order_type'];
    // $is_purchase_paid = $_REQUEST['is_purchase_paid'];
    // $purchase_paid_type = $_REQUEST['purchase_paid_type'];
    $order_time = $_REQUEST['order_time'];
    $start_in_time = $_REQUEST['start_in_time'];
    $end_in_time = $_REQUEST['end_in_time'];
    // $cheque = $_REQUEST['cheque'];
    $purchase_invoice = $_REQUEST['purchase_invoice'];
    $purchaser = $_REQUEST['purchaser'];
    $facility_id = $_REQUEST['facility_id'];
    $currency = $_REQUEST['currency'];
    $inoutVouchId = trim($_REQUEST['inoutVouchIdName_select']); // 金宝贝增加入库单搜索 ljzhou 2013.04.11
	$provider_order_sn = trim($_REQUEST['provider_order_sn']);
    if ($facility_id != '' && $facility_id != -1) {
    	$condition['order_condition'] .= " AND o.facility_id = '{$facility_id}'";
    }
    if ($order_sn != '') {
        $condition['order_condition'] .= " AND order_sn LIKE '%{$order_sn}%'";
    }
    if ($goods_name != '') {
        $condition['condition'] .= " AND og.goods_name LIKE '%{$goods_name}%'";
    }
    if ($purchase_paid_amount != '') {
        $condition['condition'] .= " AND poi.purchase_paid_amount = '{$purchase_paid_amount}'";
    }
    if ($provider_id != '' && $provider_id != -1) {
        $condition['condition'] .= " AND boi.provider_id = '{$provider_id}'";
    }
    if ($order_type != '' && $order_type != -1) {
        $condition['condition'] .= " AND order_type = '{$order_type}'";
    }
    // if ($is_purchase_paid != '' && $is_purchase_paid != -1) {
    //     if ($is_purchase_paid == 'NO') {
    //         $condition['condition'] .= " AND (is_purchase_paid = '{$is_purchase_paid}' OR is_purchase_paid = 'NONE')";
    //     } else {
    //         $condition['condition'] .= " AND is_purchase_paid = '{$is_purchase_paid}'";    
    //     }
    // }
    // if ($purchase_paid_type != '' && $purchase_paid_type != -1) {
    //     $condition['condition'] .= " AND purchase_paid_type = '{$purchase_paid_type}'";
    // }
    if ($purchaser != '' && $purchaser != -1) {
        $condition['condition'] .= " AND boi.purchaser = '{$purchaser}'";
    }
    if ($currency) {
        $condition['order_condition'] .= " AND o.currency = '{$currency}' ";
    }
    if($inoutVouchId != '' && $inoutVouchId != -1) {
    	$condition['condition'] .= " AND oa.attr_value = '{$inoutVouchId}'  ";
    }
    if($provider_order_sn){
    	$condition['condition'] .=" AND boi.provider_order_sn like '{$provider_order_sn}%'";
    }
    
    // if ($goods_cagetory > 0) {
    //     if ($goods_cagetory == 1) {
    //         $condition['condition'] .= " AND g.top_cat_id IN (1)";
    //     } elseif ($goods_cagetory == 2) {
    //         $condition['condition'] .= " AND g.top_cat_id IN (597) ";
    //     } elseif($goods_cagetory == 3) {
    //         $condition['condition'] .= " AND g.top_cat_id NOT IN (1, 597, 1109) AND g.cat_id != '1157'";
    //     } elseif($goods_cagetory == 4) {
    //         $condition['condition'] .= " AND g.cat_id IN (1157) ";
    //     } elseif($goods_cagetory == 5) {
    //         $condition['condition'] .= " AND g.top_cat_id IN (1458) ";
    //     }
    // }
    if (strtotime($order_time) > 0) {
        $condition['condition'] .= " AND o.order_time >= '{$order_time}'";
        $order_time_end = strftime('%Y-%m-%d', strtotime("+1 day", strtotime($order_time)));
        $condition['condition'] .= " AND o.order_time < '{$order_time_end}'";
    }
    // if ($cheque != '') {
    //     $condition['condition'] .= " AND cheque = '{$cheque}'";
    // }
    if ($purchase_invoice != '') {
        $condition['condition'] .= " AND purchase_invoice = '{$purchase_invoice}'";
    }

    $today = date("Y-m-d");
    $today_timestamp = strtotime($today);
    
    if ($act == 'search' && $condition['condition'] == '' && $condition['order_condition'] == '') {
        $t = strtotime($end_in_time);
        if ($t === false || $t > $today_timestamp) {
            $end_in_time = $today; 
        }
        $t = strtotime($start_in_time);
        if ($t === false || $t > $today_timestamp) {
            $start_in_time = date('Y-m-d', strtotime('-2 month', strtotime($end_in_time)));    
        }
    }

    if (strtotime($start_in_time) > 0) {
        $smarty->assign('start_in_time', $start_in_time);
        $condition['condition'] .= " AND iid.created_stamp >= '{$start_in_time}'";
    }
    if (strtotime($end_in_time) > 0) {
        $smarty->assign('end_in_time', $end_in_time);
        $end_in_time = strftime('%Y-%m-%d', strtotime("+1 day", strtotime($end_in_time)));
        $condition['condition'] .= " AND iid.created_stamp < '{$end_in_time}'";
    }

    if ($act != 'search') {
        $today = date("Y-m-d");
        $tomorrow = strftime('%Y-%m-%d', strtotime("+1 day", strtotime($today)));
        $condition['order_condition'] .= " AND o.order_time > '{$today}' AND o.order_time < '{$tomorrow}'";
    }
    
	# 添加party条件判断 2009/08/07 yxiang
	$condition['order_condition'] .= ' AND ' . party_sql('o.party_id'); 
	
    return $condition;
}