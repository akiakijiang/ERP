<?php 
define('IN_ECS', true);
require_once('includes/init.php');
require_once ('function.php');
require_once (ROOT_PATH . 'includes/debug/lib_log.php');
require_once(ROOT_PATH.'/includes/helper/array.php');
require_once(ROOT_PATH.'/includes/lib_order.php');
require_once(ROOT_PATH . 'includes/cls_json.php');
require_once (ROOT_PATH . 'includes/lib_common.php');


global $db;
$sql_party_ids = "select party_id from romeo.party where PARENT_PARTY_ID = '65658'";
$party_ids = $db -> getCol($sql_party_ids);

if(!in_array($_SESSION['party_id'], $party_ids)){
	die('没有权限');
}


$distribution_list_sql = "select * from ecshop.distributor where status = 'NORMAL' and party_id = '{$_SESSION['party_id']}'";
$distribution_list = $db -> getAll($distribution_list_sql);
$smarty -> assign('distribution_list',$distribution_list);
$act = $_REQUEST['act'];
//var_dump($act);
$json = new JSON;
switch($act) {
	case 'search':
	$distributor_list = $_POST['distributor'];
	$order_total_count_sql = "select distributor_id,count(1) as total_count from ecshop.ecs_order_info where order_status = '1' and pay_status = '2' and facility_id = '222187982' and order_time > DATE(now()) and party_id = '{$_SESSION['party_id']}' and distributor_id".db_create_in($distributor_list)."group by distributor_id";
//	$order_total_count_sql = "select distributor_id,count(1) as total_count from ecshop.ecs_order_info where order_status = '1' and pay_status = '2' and facility_id = '222187982' and order_time > '2015-06-01' and party_id = '{$_SESSION['party_id']}' and distributor_id".db_create_in($distributor_list)."group by distributor_id";
	$fields_value = array();
	$ref = array();
	$order_total_count = $db -> getAllRefby($order_total_count_sql,array('distributor_id'),$fields_value,$ref);
	$order_ratio_sql = "select  d.name,er1.region_name as province,er2.region_name as city,er3.region_name as district,count(eoi.order_id) as count,
					eoi.distributor_id,eoi.city as city_id,eoi.district as district_id
					from ecshop.ecs_order_info  eoi
					inner join ecshop.distributor d on d.distributor_id = eoi.distributor_id
					left join ecshop.ecs_region er1 on er1.region_type = 1 and er1.region_id = eoi.province
					left join ecshop.ecs_region er2 on er2.region_type = 2 and er2.region_id = eoi.city
					left join ecshop.ecs_region er3 on er3.region_type = 3 and er3.region_id = eoi.district
					where eoi.order_status = '1' and eoi.pay_status = '2' and eoi.order_time > DATE(now()) and eoi.party_id = '{$_SESSION['party_id']}' 
					and eoi.facility_id = '222187982' and eoi.distributor_id".db_create_in($distributor_list).
					"group by eoi.city,eoi.distributor_id,eoi.district
					order by eoi.distributor_id,count desc ";
	$order_ratio = $db -> getAll($order_ratio_sql);
	
	foreach($order_ratio as $key => $order) {
		$ratio = number_format($order['count']/$ref['distributor_id'][$order['distributor_id']][0]['total_count'],2) * 100 ;
		$order_ratio[$key]['ratio'] = $ratio. '%';
		if($ratio < 10) {
			unset($order_ratio[$key]);
		}
	}
	$smarty -> assign('order_ratio',$order_ratio);
	break;
	case 'get_appoint_order':
	$appoint_order_sql = "select * from ecshop.ecs_order_info where order_status = '1' and pay_status = '2' and party_id = '{$_SESSION['party_id']}' and facility_id = '222187982' and order_time > DATE(now()) and distributor_id = '{$_REQUEST['distributor_id']}' and city = '{$_REQUEST['city_id']}' and district = '{$_REQUEST['district_id']}'";
//	$appoint_order_sql = "select * from ecshop.ecs_order_info where order_status = '1' and pay_status = '2' and party_id = '{$_SESSION['party_id']}' and facility_id = '222187982' and order_time > '2015-07-01' and distributor_id = '{$_REQUEST['distributor_id']}' and city = '{$_REQUEST['city_id']}' and district = '{$_REQUEST['district_id']}'";
//	var_dump($appoint_order_sql);
	$appoint_order_list = $db -> getAll($appoint_order_sql);
	if(empty($appoint_order_list)) {
		$result['is'] = 0;  
	} else {
		$result['is'] = 1;
    	$result['content'] = $appoint_order_list;
	}
	$json = new JSON;
	print $json->encode($result);
	exit;
	break;	
	default:
	$order_total_count_sql = "select distributor_id,count(1) as total_count from ecshop.ecs_order_info where order_status = '1' and pay_status = '2'  and facility_id = '222187982' and order_time > DATE(now()) and party_id = '{$_SESSION['party_id']}' group by distributor_id";
//	$order_total_count_sql = "select distributor_id,count(1) as total_count from ecshop.ecs_order_info where order_status = '1' and pay_status = '2'  and facility_id = '222187982' and order_time > '2015-06-01' and party_id = '{$_SESSION['party_id']}' group by distributor_id";
	$fields_value = array();
	$ref = array();
	$order_total_count = $db -> getAllRefby($order_total_count_sql,array('distributor_id'),$fields_value,$ref);
	$order_ratio_sql = "select  d.name,er1.region_name as province,er2.region_name as city,er3.region_name as district,count(eoi.order_id) as count,
					eoi.distributor_id,eoi.city as city_id,eoi.district as district_id
					from ecshop.ecs_order_info  eoi
					inner join ecshop.distributor d on d.distributor_id = eoi.distributor_id
					left join ecshop.ecs_region er1 on er1.region_type = 1 and er1.region_id = eoi.province
					left join ecshop.ecs_region er2 on er2.region_type = 2 and er2.region_id = eoi.city
					left join ecshop.ecs_region er3 on er3.region_type = 3 and er3.region_id = eoi.district
					where eoi.order_status = '1' and eoi.pay_status = '2' and eoi.facility_id = '222187982' and eoi.order_time > DATE(now()) and eoi.party_id = '{$_SESSION['party_id']}' 
					group by eoi.city, eoi.distributor_id,eoi.district
					order by eoi.distributor_id,count desc ";
	$order_ratio = $db -> getAll($order_ratio_sql);
	
	foreach($order_ratio as $key => $order) {
		$ratio = number_format($order['count']/$ref['distributor_id'][$order['distributor_id']][0]['total_count'],2) * 100 ;
		$order_ratio[$key]['ratio'] = $ratio. '%';
		if($ratio < 20) {
			unset($order_ratio[$key]);
		}
	}
	$smarty -> assign('order_ratio',$order_ratio);
	
}
$smarty -> display('declaration_order_check.html');

?>