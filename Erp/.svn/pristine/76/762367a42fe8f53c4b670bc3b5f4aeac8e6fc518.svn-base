<?php
/**
 * 显示采购订单 -- 仓库查询使用(某天/某批次采购批次查询--当前及子业务组下，业务组拥有仓库及用户拥有仓库权限)
 */

define('IN_ECS', true);
require('includes/init.php');
require("function.php");
require_once(ROOT_PATH. 'includes/debug/lib_log.php');
// 通用商品组织权限特殊判断
if(check_goods_common_party()) {
	admin_priv('ck_in_storage_common');
} else {
	admin_priv('ck_in_storage', 'wl_in_storage');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['act']=='search'){
	//查询时，如果输入查询条件，按照条件查询；否则日期为当天
    $request_batch_order_time_start = $_REQUEST['batch_order_time_start'];  //采购日期
    $request_batch_order_time_end = $_REQUEST['batch_order_time_end'];  //采购日期   
	$request_batch_order_sn = trim($_REQUEST['batch_order_sn']);   //采购批次
    $facility_id = trim($_REQUEST['facility_id']);//仓库
    $purchase_order_status = trim($_REQUEST['purchase_order_status']);//批次状态
    $arrive_time = $_REQUEST['arrive_time'];//预计到货日期
	$flag= false;
	if($request_batch_order_time_start && strtotime($request_batch_order_time_start) !== false){
		$_REQUEST['batch_in_storage']['request_batch_order_time_start'] = $request_batch_order_time_start;
		$flag = true;
	}
    if($request_batch_order_time_end && strtotime($request_batch_order_time_end) !== false){
        $_REQUEST['batch_in_storage']['request_batch_order_time_end'] = $request_batch_order_time_end;
        $flag = true;
    }
	if($request_batch_order_sn != ''){
		$_REQUEST['batch_in_storage']['batch_order_sn'] = $request_batch_order_sn;
		$flag = true;
	}
    if($facility_id != ''){
        $_REQUEST['batch_in_storage']['facility_id'] = $facility_id;
        $flag = true;
    }
    if($purchase_order_status != ''){
        $_REQUEST['batch_in_storage']['purchase_order_status'] = $purchase_order_status;
        $flag = true;
    }
    if($arrive_time != ''){
        $_REQUEST['batch_in_storage']['arrive_time'] = $arrive_time;
        $flag = true;
    }
	if(!$flag){
		$_REQUEST['batch_in_storage']['batch_order_time'] = date('Y-m-d');
	}
}else{
	//默认进入页面时查询本周采购批次
    $_REQUEST['batch_in_storage']['request_batch_order_time'] = date('Y-m-d');
    $request_batch_order_time_end = date('Y-m-d');
    $request_batch_order_time_start = date('Y-m-d', strtotime("-7 day"));
    
}

//拼接搜索条件：限制业务组与仓库权限
$condition = get_batch_condition();
$sql = "
	SELECT o.is_serial,o.batch_order_id,o.batch_order_sn,o.is_cancelled,o.order_time,o.remark, 
	o.is_over_c,o.is_in_storage,o.party_id,o.facility_id,o.arrive_time,p.IN_STORAGE_MODE FROM  {$ecs->table('batch_order_info')} o
    LEFT JOIN romeo.party p on o.party_id=p.party_id 
	WHERE true {$condition}
    ORDER BY o.order_time DESC, o.batch_order_id
";
$refs_value_order = $refs_order = array();
$search_orders = $db->getAllRefBy($sql, array('batch_order_id'), $refs_value_order, $refs_order, false);
	
if (!empty($search_orders)) {
    $in_order_ids = db_create_in($refs_value_order['batch_order_id'], 'batch_order_id');
    // 查询订单是否已被取消
    $sql = "
	SELECT
        o.batch_order_id, count(o.batch_order_id) AS count
	FROM   
        {$ecs->table('batch_order_info')} AS o
	WHERE
        $in_order_ids and o.is_cancelled = 'Y'
    GROUP BY o.batch_order_id
    ";
    $refs_value_count = $refs_count = array();
    $db->getAllRefBy($sql, array('batch_order_id'), $refs_value_count, $refs_count);
    
    foreach ($search_orders as $key => $order) {
        $search_orders[$key]['canceled'] = $refs_count['batch_order_id'][$order['batch_order_id']][0]['count'];
        $search_orders[$key]['party_name'] = party_mapping($order['party_id']);
        $search_orders[$key]['facility_name'] = facility_mapping($order['facility_id']);
    }
}
$smarty->assign('search_orders', $search_orders);
$smarty->assign('facility_lists',array_intersect_assoc(get_available_facility(),get_user_facility(),get_un_out_facility()));
$smarty->assign('party_name',party_mapping($_SESSION['party_id']));
$smarty->assign('back', $_SERVER['REQUEST_URI']);
$smarty->assign('batch_order_time_start', $request_batch_order_time_start);
$smarty->assign('batch_order_time_end', $request_batch_order_time_end);
$smarty->assign('purchase_order_status', $purchase_order_status);
$smarty->assign('arrive_time', $arrive_time);
$smarty->display('oukooext/purchase_order_list_displayV6.htm');


/**
 * 根据request中的信息构造查询条件
 */
function get_batch_condition()
{
    $condition = "";

    // 从session 中获取检索条件
    $batch_order_sn = trim($_REQUEST['batch_in_storage']['batch_order_sn']);
    $batch_order_time  = $_REQUEST['batch_in_storage']['request_batch_order_time'];
    $batch_order_time_start  = $_REQUEST['batch_in_storage']['request_batch_order_time_start'];
    $batch_order_time_end  = $_REQUEST['batch_in_storage']['request_batch_order_time_end'];
    $facility_id  = $_REQUEST['batch_in_storage']['facility_id'];
    $purchase_order_status  = $_REQUEST['batch_in_storage']['purchase_order_status'];
    $arrive_time  = $_REQUEST['batch_in_storage']['arrive_time'];

    if ($batch_order_sn != '')
    {
        $condition .= " AND batch_order_sn = '{$batch_order_sn}' ";
    }

    // 指定哪一天的
    if ($batch_order_time && strtotime($batch_order_time) !== false)
    {
        $end = date('Y-m-d', strtotime("+1 day", strtotime($batch_order_time))) ;
        $start = date('Y-m-d', strtotime("-8 day", strtotime($end)));        
        $condition .= " AND (order_time > '{$start}' AND order_time < '{$end}') ";
    }elseif($batch_order_sn==''){
        $start = $batch_order_time_start;
        $end = date('Y-m-d', strtotime("+1 day", strtotime($batch_order_time_end)));
        $condition .= " AND (order_time > '{$start}' AND order_time < '{$end}') ";
    }
    if($facility_id != '' && $facility_id != 0){
        $condition .= " AND facility_id= '{$facility_id}' ";
    }
    if($purchase_order_status != ''){
        if($purchase_order_status==0){
            $condition .= " AND is_in_storage= 'N' AND is_cancelled='N' AND is_over_c='N'";
        }else if($purchase_order_status==1){
            $condition .= " AND is_in_storage= 'Y'";
        }else if($purchase_order_status==2){
            $condition .= " AND is_over_c='Y'";
        }else if($purchase_order_status==3){
            $condition .= " AND is_cancelled='Y'";
        }       
    }else{
        $condition .= " AND is_in_storage= 'N' AND is_cancelled='N' AND is_over_c='N'";
    }
    if( $arrive_time !=''){
        $arrive_time_temp = date('Y-m-d', strtotime("+1 day", strtotime($arrive_time)));
        $condition .= " AND arrive_time >= '{$arrive_time}' AND arrive_time < '{$arrive_time_temp}'";
    }

	$facility_user_list = array_intersect_assoc(get_user_facility(),get_un_out_facility());
	$facility_user_str = implode("','",array_keys($facility_user_list));
    $condition .= ' AND '. party_sql('o.party_id')." AND o.facility_id in ('{$facility_user_str}')";
    return $condition;
    
}

?>