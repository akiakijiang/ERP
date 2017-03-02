<?php
define('IN_ECS', true);
require_once('../includes/init.php');
require_once ('../function.php');
require_once (ROOT_PATH . 'includes/debug/lib_log.php');
require_once(ROOT_PATH.'/includes/helper/array.php');
require_once(ROOT_PATH.'/includes/lib_order.php');
require_once(ROOT_PATH . 'includes/cls_json.php');

// admin_priv('bird_retro');
// $bird_authority="";
// if(!in_array($_SESSION['admin_name'],array(
// 	'mjzhou','zjli','hlong','hzhang1','qyyao'
// ))){
// 	$bird_authority = "no_authority";
// }else{
// 	$bird_authority = "have_authority";
// }
global $db;
$act = isset($_REQUEST['act'])?trim($_REQUEST['act']):false;

$json = new JSON;    
switch ($act) 
{ 
	case 'retro_order':
		$order_id = trim ( $_REQUEST ['order_id'] );
		$sql = "update ecshop.brand_combin_action set sync_status='INIT' where order_id = '{$order_id}'";
		$result=$GLOBALS['db']->query($sql);
		$return['flag'] = 'SUCCESS';
		$return['message'] = '取消成功';
		print $json->encode($return);
		exit;
		break;

	case 'batch_retro_order':
		print_r('expression');
		$orders_list= $_REQUEST['checked'];
		$order_count = count($orders_list);
		print_r($order_count);
		for($i=0;$i<$order_count;$i++){
			$sql = "update ecshop.brand_combin_action set sync_status='INIT' where order_id = '{$orders_list[$i]}'";
			print_r($sql);
			$result=$GLOBALS['db']->query($sql);
		}
		// // $return['flag'] = 'SUCCESS';
		// // $return['message'] = '取消成功';
		// // print $json->encode($return);
		// exit;
		// break;

	}


$order_type_list= array(
	''=>'不限',
	'SALE' => '销售(SALE)',
	'RMA_RETURN' => '退货(RMA_RETURN)',
	'RMA_EXCHANGE' => '换货(RMA_EXCHANGE)'
);
$smarty->assign('order_type_list', $order_type_list);



$sql = "SELECT sync_status, count(*) as count from ecshop.brand_combin_action where 1=1 GROUP BY sync_status";
$send_status_list = $db->getAll($sql);
$sync_status_mapping = array(
	''=>'不限',
	'INIT' => '未推送(INIT)',
	'DOING' => '处理中(DOING)',
	'FIN' => '完成(FIN)',
	'ERR' => '错误(ERR)',
	'INN_ERR' => '内部错误(INN_ERR)'
	);
foreach ($send_status_list as $key => &$value) {
	$value['name'] = isset($sync_status_mapping[$value['sync_status']]) ? $sync_status_mapping[$value['sync_status']] : '其他['.$value['sync_status'].']';
}
array_unshift($send_status_list, array('sync_status' =>'', 'name' => '不限'));
$smarty->assign('send_status_list', $send_status_list);

// $send_status_list= array(
// 	''=>'不限',
// 	'INIT' => '未推送(INIT)',
// 	'DOING' => '处理中(DOING)',
// 	'FIN' => '完成(FIN)',
// 	'ERR' => '错误(ERR)',
// 	'INN_ERR' => '内部错误(INN_ERR)'
// );
// $smarty->assign('send_status_list', $send_status_list);

$result = call_user_func('search_combi_order',$_GET);
$smarty->assign('result',$result);
$smarty->assign('Pager',$result[-1]['pager']);
$smarty->display ( 'combi_crm_monitor.htm' );
	
function getCondition() {
	global $db;
	$order_sn = trim ( $_REQUEST ['order_sn'] );//ERP订单号
	$taobao_order_no = trim ( $_REQUEST ['taobao_order_no'] );//淘宝订单号
	$order_type = trim ( $_REQUEST ['order_type'] );//订单类型
	$order_start_time = trim ( $_REQUEST ['order_start_time'] );//订单开始时间
	$order_end_time = trim ( $_REQUEST ['order_end_time'] );//订单结束时间
	$delivery_start_time = trim ( $_REQUEST ['delivery_start_time'] );//发货开始时间
	$delivery_end_time = trim ( $_REQUEST ['delivery_end_time'] );//发货结束时间
	$send_status = trim ( $_REQUEST ['send_status'] );//推送状态
	$last_start_time = trim ( $_REQUEST ['last_start_time'] );//推送开始时间
	$last_end_time = trim ( $_REQUEST ['last_end_time'] );//推送结束时间


	$condition='';

	// ERP订单号
	if($order_sn) {
		$condition .= " and bco.order_sn like '%{$order_sn}%' ";
	}
	// 淘宝订单号
	if($taobao_order_no) {
		$condition .= " and bco.orderno like '%{$taobao_order_no}%' ";
	}
	// 订单时间
	if ($order_start_time != '') {
		$condition .= " and order_date > '{$order_start_time}' ";
	}
	if ($order_end_time != '') {
		$condition .= " and order_date < '{$order_end_time}' ";
	}
	// 发货时间
	if ($delivery_start_time != '') {
		$condition .= " and shipdate > '{$delivery_start_time}' ";
	}	
	if ($delivery_end_time != '') {
		$condition .= " and shipdate < '{$delivery_end_time}' ";
	}
	// 推送时间
	if ($last_start_time != '') {
		$condition .= " and last_updated_stamp > '{$last_start_time}' ";
	}	
	if ($last_end_time != '') {
		$condition .= " and last_updated_stamp < '{$last_end_time}' ";
	}
	// 订单类型
	if($order_type != ''){
		$condition .= " and order_type = '{$order_type}' ";
	}else{
		$condition .= " ";
	}
	// 推送状态
	if($send_status != ''){
		$condition .= " and sync_status = '{$send_status}' ";
	}else{
		$condition .= " ";
	}

	return $condition;
}
	
function search_combi_order($args){
	global $db;
	$cond = getCondition();
	$index = 0;
	$page = intval($args['page']);
	$page = max(1, $page);
	$limit = 10;
	$offset = $limit * ($page-1);

	$sqlc = "select count(1) from ecshop.brand_combin_action bca
			INNER JOIN ecshop.brand_combin_order bco ON bco.order_id=bca.order_id 
			where 1  {$cond}";	
	$total = $db ->getOne($sqlc);//符合条件的数据总条数
	$sql = "select bca.order_id,bco.shipdate,bca.sync_note,bca.sync_status,bca.last_updated_stamp,
			bca.order_date,bca.order_type,bco.orderno,bco.order_sn from ecshop.brand_combin_action bca 
			INNER JOIN ecshop.brand_combin_order bco ON bco.order_id=bca.order_id 
		    where 1 {$cond} LIMIT {$limit} OFFSET {$offset}";
	// echo $sql.'sql';
	$combi_list = $db->getAll($sql);
	// print_r($combi_list);
	if(!empty($combi_list)){
		foreach($combi_list as $order){
			$result[$index]['order_sn']=$order['order_sn'];
			$result[$index]['order_id']=$order['order_id'];
			$result[$index]['orderno']=$order['orderno'];
			$result[$index]['order_date']=$order['order_date'];
			$result[$index]['shipdate']=$order['shipdate'];
			$result[$index]['sync_note']=$order['sync_note'];
			$result[$index]['last_updated_stamp']=$order['last_updated_stamp'];
			switch($order[order_type]){
				case 'SALE':
					$order_type = '销售 (sale)';
					break;
				case 'RMA_EXCHANGE':
					$order_type = '换货 (rma_exchange)';
					break;
				case 'RMA_RETURN':
					$order_type = '退货(rma_return) ';
					break;
			}

			switch($order[sync_status]){
				case 'INIT':
					$sync_status = '未推送(init) ';
					break;
				case 'Doing':
					$sync_status = '处理中(doing) ';
					break;
				case 'FIN':
					$sync_status = '完成(fin)';
					break;
				case 'ERR':
					$sync_status = '错误(err)';
					break;
				case 'INN_ERR':
					$sync_status = '内部错误(inn_err)';
					break;
			}
			$result[$index]['order_type']=$order_type;
			$result[$index]['sync_status']=$sync_status;
			$index++;
		}
	}
	$result[-1]['pager'] = pager($total,$limit,$page);
	return $result;
}
?>