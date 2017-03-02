<?php
/**
 * 采购订单查询(方式一：request:batch_order_sn ; 方式二：search; csv导出) (--当前及子业务组下，业务组拥有仓库及用户拥有仓库权限)
 * 
 * 时间段限制：7天。
 * if($start && $end) start<order_time<end
 * if($start && !$end) start<order_time<start+7
 * if(!$start && $end) end-7<order_time<end
 * if(!$start && !$end) today-7<order_time<today
 * 
 * by ytchen 2015.11.24
 */
define ( 'IN_ECS', true );
require ('includes/init.php');
require_once(ROOT_PATH . 'admin/function.php');
admin_priv ( 'ck_in_storage', 'wl_in_storage' );
require_once ('includes/lib_goods.php');
require_once ('includes/lib_product_code.php');
require_once(ROOT_PATH. 'includes/debug/lib_log.php');
// 导出csv的权限
$csv = $_POST['csv'];
if ($csv) { admin_priv("admin_other_csv"); }

//是否入库
$is_in_storage_list= array(
	'all' => '全部',
	'in' => '已入库',
	'not_in' => '未入库'
);
// 默认
$is_in_storage =
	isset($_REQUEST['is_in_storage']) && trim($_REQUEST['is_in_storage'])
    ? $_REQUEST['is_in_storage']
    : (isset($_POST['is_in_storage_hidden_form']) && trim($_POST['is_in_storage_hidden_form']) ? $_POST['is_in_storage_hidden_form'] :'all');    
    
//是否废除
$is_cancelled_list= array(
	'all' => '全部',
	'cancelled' => '已废除',
	'not_cancelled' => '未废除'
);
// 默认
$is_cancelled =
	isset($_REQUEST['is_cancelled']) && trim($_REQUEST['is_cancelled'])
    ? $_REQUEST['is_cancelled']
    : (isset($_POST['is_cancelled_hidden_form']) && trim($_POST['is_cancelled_hidden_form']) ? $_POST['is_cancelled_hidden_form'] :'all'); 

//是否完结
$is_over_c_list= array(
	'all' => '全部',
	'over_c' => '已完结',
	'not_over_c' => '未完结'
);
// 默认
$is_over_c =
	isset($_REQUEST['is_over_c']) && trim($_REQUEST['is_over_c'])
    ? $_REQUEST['is_over_c']
    :(isset($_POST['is_over_c_hidden_form']) && trim($_POST['is_over_c_hidden_form']) ? $_POST['is_over_c_hidden_form'] :'all');    

    
$form_post = false; 
$request_order_time = $_REQUEST['order_time'];
$request_order_time_end = $_REQUEST['order_time_end'];
if (isset($request_order_time) && strtotime($request_order_time) > 0){
    if(!(isset($request_order_time_end) && strtotime($request_order_time_end) > 0)){
    	$request_order_time_end = date('Y-m-d', strtotime("+7 days", strtotime($request_order_time)));
    }
}else{
	if(isset($request_order_time_end) && strtotime($request_order_time_end) > 0){
		$request_order_time = date('Y-m-d', strtotime("-7 days", strtotime($request_order_time_end)));
    }else{
    	$request_order_time = date('Y-m-d', strtotime("-7 days"));
    	$request_order_time_end = date('Y-m-d');
    }  
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['act']=='search') {
	$form_post = true; 
	$_REQUEST['statistics'] = array (
       'order_sn' => $_REQUEST ['order_sn'], // 采购单号
       'batch_order_id' => $_REQUEST ['batch_order_id'], // 批次采购单号
	   'batch_order_sn' => $_REQUEST ['batch_order_sn'], // 批次采购单号
       'goods_name' => mysql_real_escape_string(trim($_REQUEST ['goods_name'])), // 商品名
       'goods_barcode' => mysql_real_escape_string ( trim ( $_REQUEST ['goods_barcode'] ) ), // 商品条码
	   'is_in_storage' => isset($_REQUEST ['is_in_storage'])? $_REQUEST ['is_in_storage']:'all',
	   'is_cancelled' => isset($_REQUEST ['is_cancelled'])? $_REQUEST ['is_cancelled']:'all',
	   'is_over_c' => isset($_REQUEST ['is_over_c'])? $_REQUEST ['is_over_c']:'all',
	   'facility_id' => (isset($_REQUEST ['facility_id']) && $_REQUEST ['facility_id'] != '0')? $_REQUEST ['facility_id']:'', 
	   'order_time'        => $request_order_time, 
	   'order_time_end'        => $request_order_time_end                        // 订购单时间
	);
} else if(isset($_POST['pager_hidden_form']) || $csv){
	$_REQUEST['statistics'] = array (
       'order_sn' => $_POST ['order_sn_hidden_form'], // 采购单号
       'batch_order_id' => $_POST ['batch_order_id_hidden_form'], // 批次采购单号
	   'batch_order_sn' => $_POST ['batch_order_sn_hidden_form'], // 批次采购单号
       'goods_name' => mysql_real_escape_string(trim($_POST ['goods_name_hidden_form'])), // 商品名
       'goods_barcode' => mysql_real_escape_string ( trim ( $_POST ['goods_barcode_hidden_form'] ) ), // 商品条码
	   'is_in_storage' => $_POST['is_in_storage_hidden_form'],
	   'is_cancelled' => $_POST ['is_cancelled_hidden_form'],
	   'is_over_c' => $_POST ['is_over_c_hidden_form'],
	   'facility_id' => (isset($_POST ['facility_id_hidden_form']) && $_POST ['facility_id_hidden_form'] != '0')? $_POST ['facility_id_hidden_form']:'', 
	   'order_time'   =>isset($_POST ['order_time_hidden_form'])?$_POST ['order_time_hidden_form']:$request_order_time, 
	   'order_time_end'  => isset($_POST ['order_time_end_hidden_form'])?$_POST ['order_time_end_hidden_form']:$request_order_time_end                        // 订购单时间
	);
}else{
	if(isset($_REQUEST ['batch_order_sn'])){
		$_REQUEST ['statistics'] = array (
	   		'batch_order_sn' => $_REQUEST ['batch_order_sn'], // 批次采购单号
	   		'order_time'        => $request_order_time, 
	   		'order_time_end'        => $request_order_time_end
		);
	}else if(!isset($_REQUEST ['page'])){
		$_REQUEST ['statistics'] = array (
			'order_time'        => $request_order_time_end,   //默认进入页面只显示今日采购批次
	   		'order_time_end'        => $request_order_time_end
		); 
	}
}

$batch_order_sn = trim($_REQUEST ['batch_order_sn']);
$facility_user_list = array_intersect_assoc(get_available_facility(),get_user_facility());
$statistics = '';
if ($batch_order_sn) {
	$cond = "";
	if(empty($_REQUEST['statistics']['facility_id'])){
		$facility_user_str = implode("','",array_keys($facility_user_list));
    	$cond .= " AND facility_id in ('{$facility_user_str}') ";
    }else{
    	$cond .= " AND  facility_id = '".$_REQUEST['statistics']['facility_id']."' ";
    }
    if(strtotime($_REQUEST['statistics']['order_time']) > 0){
    	$cond .= " AND order_time > '".$_REQUEST['statistics']['order_time']."'";
    }
    if(strtotime($_REQUEST['statistics']['order_time_end']) > 0){
    	$order_time_end2 = date('Y-m-d', strtotime("+1 days",strtotime($_REQUEST['statistics']['order_time_end'])));
    	$cond .= " AND order_time < '{$order_time_end2}'";
    }
    
	$sql = "SELECT * FROM {$ecs->table('batch_order_info')} WHERE batch_order_sn = '{$batch_order_sn}'  AND " . party_sql('party_id').$cond;
	$order = $db->getRow ( $sql, true );
	if (empty($order)) {
		$info = "采购批次搜索失败，请检查该批次是否在【所选仓库，默认查询时间段，当前业务组】";
	}
	$sql = "select
        sum(if(om.is_in_storage = 'Y',1,0)) as in_storage_num,sum(if(om.is_in_storage = 'N',1,0)) as not_in_storage_num,
        sum(if(om.is_cancelled = 'Y',1,0)) as cancelled_num,sum(if(om.is_over_c = 'Y',1,0)) as over_c_num,
        sum(if(om.order_id is not null,1,0)) as total_num
        from {$ecs->table('batch_order_info')} boi
        left join {$ecs->table('batch_order_mapping')} om ON om.batch_order_id = boi.batch_order_id
        where boi.batch_order_sn = '{$batch_order_sn}'
      ";
	$statistics = $db->getRow ( $sql );
}

$condition = get_condition();

//中粮加物料编码
$cofco = array();
if($_SESSION['party_id'] == 65625){
	$cofco['table'] = 'LEFT JOIN ecshop.brand_zhongliang_product bzp ON bzp.barcode = g.barcode ';
	$cofco['field'] = 'bzp.spec, ';
}

if(empty($info)){
	$page_limit = ''; 
	// 如果不是 csv 需要分页 
	if(empty($csv)){
		if ( $form_post ){
			$page = 1; 
			$_REQUEST['page'] = 1; 
		}else{
			$page = isset($_POST['pager_hidden_form'])?$_POST['pager_hidden_form']:1; 	 
		}
	
		$sql = "
		    SELECT
		        count(*)
			FROM 
			     {$ecs->table('batch_order_info')} AS boi force index(order_time)
			    INNER JOIN {$ecs->table('batch_order_mapping')} AS om ON om.batch_order_id = boi.batch_order_id
		        INNER JOIN {$ecs->table('order_info')} AS o ON o.order_id = om.order_id
		        INNER JOIN {$ecs->table('order_goods')} AS og ON og.order_id = o.order_id 
		        LEFT JOIN {$ecs->table('goods')} AS g ON g.goods_id = og.goods_id 
		        {$cofco['table']}
		        LEFT JOIN {$ecs->table('goods_style')} AS gs ON gs.goods_id = og.goods_id AND gs.style_id = og.style_id
			WHERE
		        o.order_type_id = 'PURCHASE' {$condition}
			";
		//总记录数
		QLog::log("查询符合条件的采购订单数sql1 : ".$sql); 
		$total = $db->getOne($sql);
		// 分页 
		$page_size = 30;  // 每页数量
		$total_page = ceil($total/$page_size);  // 总页数
		if ($page > $total_page) $page = $total_page;
		if ($page < 1) $page = 1;
		$offset = ($page - 1) * $page_size;
		$limit = $page_size;
		$page_limit .= " limit {$offset},{$page_size} "; 

		$Pager = pager_post_parameter($total, $page_size, $page); 
	}else{
		$page_limit .= " limit 0,900 ";  
	}
	
	$sql = "
			SELECT
		        o.order_sn, o.order_id, o.order_time,o.order_status,{$cofco['field']}
		        og.goods_name, og.goods_number, og.customized,og.rec_id, og.goods_id, og.style_id, gs.internal_sku, if(gs.barcode is NULL or gs.barcode = '',
		        g.barcode,gs.barcode) as barcode,
		        boi.order_type, boi.provider_id, p.provider_name, boi.purchaser, 
		        o.facility_id, f.facility_name,boi.batch_order_sn,om.is_in_storage,om.is_cancelled,om.is_over_c,
				boi.provider_order_sn, boi.provider_out_order_sn,boi.batch_order_id,
				boi.remark, 
				IF(om.is_over_c = 'Y' || om.is_cancelled = 'Y' || ebi.indicate_status = 'FINISHED' || ebi.indicate_status = 'FINISH', 1, 0) AS is_done
			FROM 
			    {$ecs->table('batch_order_info')} AS boi force index(order_time)
			    INNER JOIN {$ecs->table('batch_order_mapping')} AS om ON om.batch_order_id = boi.batch_order_id
		        INNER JOIN {$ecs->table('order_info')} AS o ON o.order_id = om.order_id
		        INNER JOIN {$ecs->table('order_goods')} AS og ON og.order_id = o.order_id 
		        LEFT JOIN {$ecs->table('provider')} p on p.provider_id = boi.provider_id
		        LEFT JOIN {$ecs->table('goods')} AS g ON g.goods_id = og.goods_id 
		        LEFT JOIN ecshop.express_best_indicate ebi ON ebi.order_id = boi.batch_order_id
		        {$cofco['table']}
		        LEFT JOIN {$ecs->table('goods_style')} AS gs ON gs.goods_id = og.goods_id AND gs.style_id = og.style_id
		        LEFT JOIN  romeo.facility f on f.facility_id = o.facility_id
			WHERE
		        o.order_type_id = 'PURCHASE' {$condition} 
		    GROUP BY og.rec_id
		    ORDER BY boi.batch_order_id, o.order_id {$page_limit}
			";
    QLog::log("查询符合条件的采购订单sql2 : ".$sql);
	$refs_value_order = $refs_order = array ();
	$search_orders = $db->getAllRefBy ( $sql, array ('rec_id' ), $refs_value_order, $refs_order, false );

	if (!empty( $search_orders )) {
		$in_order_goods_id_str = implode(",",$refs_value_order ['rec_id']);
		//新库存：查询每个订单的已入库数
		$sql = "SELECT SUM(IFNULL(iid.QUANTITY_ON_HAND_DIFF,0)) AS new_in_number, og.goods_number AS goods_number,og.order_id,
					  group_concat(iid.created_stamp SEPARATOR  ' ') as iid_created_stamp ,og.rec_id
			          FROM ecshop.ecs_order_goods og
					  LEFT JOIN romeo.inventory_item_detail iid ON iid.order_goods_id = convert(og.rec_id using utf8)
					  LEFT JOIN romeo.inventory_item ii on iid.INVENTORY_ITEM_ID = ii.INVENTORY_ITEM_ID and ii.STATUS_ID = 'INV_STTS_AVAILABLE'
					WHERE og.rec_id in ({$in_order_goods_id_str})
					group by iid.order_goods_id,og.rec_id ";
	    $receive_infos = $db->getAll($sql);
	
	    if(empty($csv)){
	    	// 查询每个订单的收货容器(即上架车)情况
		 	$sql = "
		        SELECT
		            ild.order_id, if(il.inventory_location_id, location_barcode,'已上架') as location_barcode, sum(goods_number_diff) as rec_num,og.rec_id
		        FROM
		            romeo.inventory_location_detail ild
		            LEFT JOIN romeo.inventory_location il on ild.inventory_location_id = il.inventory_location_id
                    inner join romeo.product_mapping pm on ild.product_id = pm.PRODUCT_ID
		            inner JOIN ecshop.ecs_order_goods og on pm.ecs_goods_id = og.goods_id and pm.ecs_style_id = og.style_id and og.order_id = ild.order_id
		        WHERE
		            og.rec_id in ({$in_order_goods_id_str})  
		        GROUP BY og.rec_id, location_barcode
			";
			$refs_value_count_4 = $refs_count_4 = array ();
			$db->getAllRefBy ( $sql, array ('rec_id'), $refs_value_count_4, $refs_count_4 );  
	    }
	   
		require_once ('includes/lib_goods.php');
		if(empty($csv)){
			$row_span = array();
		}
	
		foreach ( $search_orders as $key => $order ) {
			$search_orders [$key] ['facility_name'] = facility_mapping ( $order ['facility_id'] );
			$search_orders [$key] ['goods_item_type'] = get_goods_item_type ( $order ['goods_id'] );
	
			if(empty($csv)){
				$batch_order_id = $search_orders[$key]['batch_order_id'];
		    	if(isset($row_span[$batch_order_id])){
		    		$row_span[$batch_order_id] = $row_span[$batch_order_id] + 1; 
		    	}else{
		    		$row_span[$batch_order_id] =  1;  
		    	}
		    	$search_orders [$key] ['receive_location_info'] = $refs_count_4 ['rec_id'] [$order ['rec_id']];
			}
	
			foreach ($receive_infos as $receive_info_key=>$receive_info) {
				if($receive_info['rec_id'] == $order['rec_id']) {
					$new_in_number = $receive_info['new_in_number'];
		            $new_not_in_number = $receive_info['goods_number'] - $receive_info['new_in_number'];
		            $new_needed_number = $search_orders[$key]['is_done'] ? 0 : $new_not_in_number;
			    	$search_orders[$key]['new_in_number'] = $new_in_number;
			    	$search_orders[$key]['new_not_in_number'] =$new_not_in_number;
			    	$search_orders[$key]['new_needed_number'] = $new_needed_number;
			    	$search_orders[$key]['iid_created_stamp'] = $receive_info['iid_created_stamp']; 
			    	continue;
				}
	    	}
		}
		$smarty->assign ( 'search_orders', $search_orders );
	}
}

$smarty->assign('party_id',$_SESSION['party_id']);
$smarty->assign('info',$info);
if ($csv == "批次采购订单统计信息csv" ) {
	header ( "Content-type:application/vnd.ms-excel" );
	header ( "Content-Disposition:filename=" . iconv ( "UTF-8", "GB18030", "批次采购订单统计信息" ) . ".csv" );
	$out = $smarty->fetch ( 'oukooext/batch_order_statistics_csv.htm' );
	echo iconv ( "UTF-8", "GB18030", $out );
	exit ();
}

$smarty->assign('available_facility',$facility_user_list); 
$smarty->assign('facility_names',implode(",",$facility_user_list));
$smarty->assign('party_name',party_mapping($_SESSION['party_id']));
$smarty->assign('statistics', $statistics);
$smarty->assign('is_in_storage', $is_in_storage);      			
$smarty->assign('is_in_storage_list', $is_in_storage_list);
$smarty->assign('is_cancelled', $is_cancelled);      			
$smarty->assign('is_cancelled_list', $is_cancelled_list);
$smarty->assign('is_over_c', $is_over_c);      			
$smarty->assign('is_over_c_list', $is_over_c_list);
if(isset($Pager)){
	$smarty->assign('Pager', $Pager); 
}
if(isset($row_span)){
	$smarty->assign('row_span', $row_span);
}
$smarty->assign('now_date',date('Y-m-d'));
$smarty->display ( 'oukooext/batch_order_statisticsV2.htm' );

/**
 * 根据request中的信息构造查询条件
 */
function get_condition()
{
    $condition = "";

    // 从session 中获取检索条件
    $batch_order_id = trim($_REQUEST['statistics']['batch_order_id']);
    $batch_order_sn = trim($_REQUEST['statistics']['batch_order_sn']);
    $order_sn       = trim($_REQUEST['statistics']['order_sn']);
    $goods_name     = trim($_REQUEST['statistics']['goods_name']);
    $goods_barcode  = trim($_REQUEST['statistics']['goods_barcode']);
    $is_in_storage  = trim($_REQUEST['statistics']['is_in_storage']);
    $is_cancelled   = trim($_REQUEST['statistics']['is_cancelled']);
    $is_over_c      = trim($_REQUEST['statistics']['is_over_c']);
    $order_time        = $_REQUEST['statistics']['order_time'];
    $order_time_end       = $_REQUEST['statistics']['order_time_end'];
    $facility_id = $_REQUEST['statistics']['facility_id']; 
	
    if ($batch_order_id != '')
    {
        $condition .= " AND boi.batch_order_id = '{$batch_order_id}'";
    }
    
    if ($batch_order_sn != '')
    {
        $condition .= " AND boi.batch_order_sn = '{$batch_order_sn}'";
    }
    if ($order_sn != '')
    {
        $condition .= " AND o.order_sn = '{$order_sn}'";
    }
    if ($goods_name != '')
    {
        $condition .= " AND og.goods_name like '%{$goods_name}%'";
    }
    if ($goods_barcode != '')
    {
        $condition .= " AND if(gs.barcode is NULL or gs.barcode = '',g.barcode,gs.barcode) = '{$goods_barcode}'";
    }
    if ($is_in_storage != '')
    {
    	if($is_in_storage == 'in') {
    		$condition .= " AND om.is_in_storage = 'Y'";
    	} elseif ($is_in_storage == 'not_in') {
    		$condition .= " AND om.is_in_storage = 'N'";
    	}
    }
    if ($is_cancelled != '')
    {
    	if($is_cancelled == 'cancelled') {
    		$condition .= " AND om.is_cancelled = 'Y'";
    	} elseif ($is_cancelled == 'not_cancelled') {
    		$condition .= " AND om.is_cancelled = 'N'";
    	}
    }
    if ($is_over_c != '')
    {
    	if($is_over_c == 'over_c') {
    		$condition .= " AND om.is_over_c = 'Y'";
    	} elseif ($is_over_c == 'not_over_c') {
    		$condition .= " AND om.is_over_c = 'N'";
    	}
    }
	
    // 指定哪一天的
    if ( strtotime($order_time) > 0){
        $start = $order_time;
        if(strtotime($order_time_end) > 0){
        	$end = date('Y-m-d', strtotime("+1 day", strtotime($order_time_end)));
        }else{
        	$end = date('Y-m-d', strtotime("+8 day", strtotime($order_time)));
        }
    }else{
    	if(strtotime($order_time_end) > 0){
    		$start = date('Y-m-d', strtotime("-7 day", strtotime($order_time)));
        	$end = date('Y-m-d', strtotime("+1 day", strtotime($order_time_end)));
        }else{
        	$start = date('Y-m-d', strtotime("-7 day"));
        	$end = date('Y-m-d');
        }  
    }
    $condition .= " AND o.order_time > '{$start}' AND o.order_time < '{$end}' AND boi.order_time > '{$start}' AND boi.order_time < '{$end} '";

    if(empty($facility_id)){
    	$facility_user_list = array_intersect_assoc(get_available_facility(),get_user_facility());
		$facility_user_str = implode("','",array_keys($facility_user_list));
    	$condition .= ' AND '. party_sql('boi.party_id')." AND boi.facility_id in ('{$facility_user_str}') ";
    }else{
    	$condition .= ' AND '. party_sql('boi.party_id')." AND  boi.facility_id = '{$facility_id}' ";
    }
    return $condition;
}

?>