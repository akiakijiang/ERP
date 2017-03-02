<?php
define('IN_ECS', true);
require('includes/init.php');
require('function.php');
admin_priv('cw_finance_storage_main', 'ck_t_in_info');

$csv = $_REQUEST['csv'];

$size = 15;
$page = $_POST['pager_hidden_form'] ? $_POST['pager_hidden_form'] : 1;
$start = ($page - 1) * $size;

if ($csv == null) {
	$limit = "LIMIT $size";
	$offset = "OFFSET $start";
} else {
	admin_priv('2ck_t_in_info_csv', '4cw_finance_storage_main_csv');
}

if ($_REQUEST['act']=='search' || $csv) {
	$_REQUEST['t_infos'] = array (
       'order_type' => $_REQUEST ['order_type'], 
       'is_new' 	=> $_REQUEST ['is_new'], 
	   'facility_id' => $_REQUEST ['facility_id'], 
	   'start'      => $_REQUEST['start'], 
	   'end'        => $_REQUEST['end']         
	);
} else if(isset($_POST['pager_hidden_form']) ){
	$_REQUEST['t_infos'] = array (
       'order_type' => $_POST ['order_type_hidden_form'], 
       'is_new' 	=> $_POST ['is_new_hidden_form'], 
	   'facility_id' => $_POST ['facility_id_hidden_form'], 
	   'start'      => $_POST['start_hidden_form'], 
	   'end'        => $_POST['end_hidden_form']  
	);
}else{
	$smarty->assign ( 'available_facility', array ('999999' => '未指定仓库' ) + array_intersect_assoc(get_available_facility(),get_user_facility()) );
	$smarty->display ( 'oukooext/t_in_info.htm' );
	exit ();
}

$condition = getCondition();

$sql = "SELECT ii.created_stamp AS in_time,ii.inventory_item_acct_type_id as order_type,ii.provider_id, 
		 ii.unit_cost AS purchase_paid_amount,iid.quantity_on_hand_diff AS goods_number,og.goods_name,og.goods_price, 
		 IF(ii.status_id = 'INV_STTS_AVAILABLE', '全新', '二手') AS is_new,c.cat_name, 
		 IF(gs.barcode IS NULL, g.barcode,gs.barcode) AS barcode, f.facility_name,oi.order_sn,oi.order_id,ori.order_id AS ori_order_id, 
		 ori.order_status AS ori_order_status,ori.shipping_status AS ori_shipping_status, ogsi.shipping_invoice,  p.name,it.inventory_transaction_type_id
		FROM romeo.inventory_item ii
		INNER JOIN romeo.inventory_item_detail iid on ii.INVENTORY_ITEM_ID = iid.INVENTORY_ITEM_ID
		INNER JOIN romeo.inventory_transaction it on it.INVENTORY_TRANSACTION_ID = iid.INVENTORY_TRANSACTION_ID
		INNER JOIN romeo.facility f ON f.facility_id = ii.facility_id
		INNER JOIN ecshop.ecs_order_info oi on oi.order_id = iid.order_id and ii.facility_id = oi.facility_id
		INNER JOIN ecshop.ecs_order_goods og on oi.order_id = og.order_id and og.rec_id = iid.order_goods_id 
		LEFT JOIN ecshop.ecs_goods_style gs ON og.goods_id = gs.goods_id AND og.style_id = gs.style_id and gs.is_delete=0
		LEFT JOIN ecshop.ecs_goods g ON g.goods_id = og.goods_id
		LEFT JOIN ecshop.ecs_category c on g.top_cat_id = c.cat_id
		LEFT JOIN ecshop.order_relation ore ON ore.order_id = oi.order_id
		LEFT JOIN ecshop.ecs_order_info ori ON ori.order_id = ore.parent_order_id
		LEFT JOIN romeo.order_shipping_invoice ogsi ON ogsi.order_id = ori.order_id
		LEFT JOIN romeo.party p ON p.party_id = convert(oi.party_id USING utf8)
		WHERE 
			oi.order_type_id = 'RMA_RETURN' {$condition}
		ORDER BY ii.created_stamp DESC $limit $offset";
$goods_list = $db->getAll($sql);

// 处理方式
$dispose_method_list = array(
		'0'=>'未知',
		'1'=>'退货',
		'2'=>'换货',
		'3'=>'异货换货',
		'4'=>'其他',
		'5'=>'错发',
		'6'=>'漏发',
		'7'=>'虚拟入库',
		'8'=>'追回',
		'9'=>'拒收'
);
if (!empty($goods_list)) {
	foreach ($goods_list as $key=>$goods_item){
		switch ($goods_item['inventory_transaction_type_id']){
			case 'ITT_SO_RET':
				$sql = "SELECT dispose_method FROM ecshop.service WHERE back_order_id = {$goods_item['order_id']} LIMIT 1";
				$dispose_method_id = $db->getOne($sql);
				$goods_list[$key]['dispose_method'] = $dispose_method_list[$dispose_method_id];
				break;
			case 'ITT_SO_CANCEL':
				$goods_list[$key]['dispose_method'] = '追回';
				break;
			case 'ITT_SO_REJECT':
				$goods_list[$key]['dispose_method'] = '拒收';
				break;
			default:
				$goods_list[$key]['dispose_method'] = '未知';
				break;
		}
	}
}
$sqlc = "
		SELECT 1
		FROM romeo.inventory_item ii
		INNER JOIN romeo.inventory_item_detail iid on ii.INVENTORY_ITEM_ID = iid.INVENTORY_ITEM_ID
		INNER JOIN ecshop.ecs_order_info oi on oi.order_id = iid.order_id and ii.facility_id = oi.facility_id
		INNER JOIN ecshop.ecs_order_goods og on oi.order_id = og.order_id and og.rec_id = iid.order_goods_id 
		WHERE 
			oi.order_type_id = 'RMA_RETURN' 
			{$condition}";
$goods_counts= $db->getCol($sqlc);
$count = count($goods_counts);
$Pager = pager_post_parameter($count, $size, $page); 

$providers = getProviders();
$smarty->assign('providers', $providers);
$smarty->assign('goods_list', $goods_list);
$smarty->assign('pager', $Pager);
$smarty->assign('purchasePaidTypes',  $_CFG['adminvars']['purchase_paid_type']);
$smarty->assign ('available_facility',  array ('999999' => '未指定仓库' ) + array_intersect_assoc(get_available_facility(),get_user_facility()) ); 

if ($csv) {
    $party_id = $_SESSION ['party_id'];
    $invoiced_name = $invoiced_array[$invoiced];
    $sql = "select name
    from romeo.party
    where party_id = '{$party_id}'";
    $result = $GLOBALS['db']->getAll($sql);
    $party_name = $result[0]['name'];
    $smarty->assign('party_name',$party_name);
    
	admin_priv('2ck_t_in_info_csv', '4cw_finance_storage_main_csv');
	header("Content-type:application/vnd.ms-excel");
	header("Content-Disposition:filename=" . iconv("UTF-8","GB18030","销退入库详情报表") . ".csv");	
	$out = $smarty->fetch('oukooext/t_in_info_csv.htm');
	echo iconv("UTF-8","GB18030", $out);
	exit();	
} else {
	$smarty->display('oukooext/t_in_info.htm');
}


function getCondition() {
	$order_type = $_REQUEST['t_infos']['order_type'];
	$is_new = $_REQUEST['t_infos']['is_new'];
	$facility_id = $_REQUEST['t_infos']['facility_id'];
	$start = $_REQUEST['t_infos']['start'];
	$end = $_REQUEST['t_infos']['end'];
	
	$condition = '';
	if ($is_new != null && $is_new != -1) {
		$condition .= " AND ii.status_id = '{$is_new}'";		
	}	
	
	if (strtotime($start) > 0) {
		$condition .= " AND ii.created_stamp >= '$start'";
	}
	if (strtotime($end) > 0) {
		$end = strftime('%Y-%m-%d', strtotime("+1 day", strtotime($end)));
		$condition .= " AND ii.created_stamp <= '$end'";
	}
	if($condition==''){
		$date = date('Y-m-d',strtotime('-1 day')); 
        $condition .= " AND ii.created_stamp >= '$date' ";
        $_REQUEST['t_infos']['start'] = $date;
	}
	if ($order_type != null && $order_type != -1) {
		$condition .= " AND ii.inventory_item_acct_type_id = '$order_type'";
	}	
	if ($facility_id != null && $facility_id != '999999') {
		$condition .= " AND ii.facility_id = '{$facility_id}'";		
	}else{
		$facility_ids =implode("','",array_keys(array_intersect_assoc(get_available_facility(),get_user_facility())));
		$condition .= " AND ii.facility_id in ('{$facility_ids}') ";
	}	
	# 添加party条件判断 2009/08/06 yxiang
	$condition .= ' AND '. party_sql('oi.party_id');
	
	return $condition;
}
?>
