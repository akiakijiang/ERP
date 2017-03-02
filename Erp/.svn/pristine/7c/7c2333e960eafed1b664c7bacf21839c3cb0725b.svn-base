<?php
define('IN_ECS', true);
require_once('includes/init.php');
require_once ('function.php');
require_once (ROOT_PATH . 'includes/debug/lib_log.php');
require_once(ROOT_PATH.'/includes/helper/array.php');
require_once(ROOT_PATH.'/includes/lib_order.php');
require_once(ROOT_PATH . 'includes/cls_json.php');

global $db;
$sql_party_ids = "select party_id from romeo.party where PARENT_PARTY_ID = '65658'";
$fields_value = array();
$ref = array();
$party_ids = $db -> getAllRefby($sql_party_ids,array('party_id'), $fields_value, $ref);

if(!in_array($_SESSION['party_id'], $fields_value['party_id'])){
	//die('没有权限');
}


$act = isset($_REQUEST['act'])?trim($_REQUEST['act']):false;
$order_status_list= array(
	'' => '全部',
	'order_fail' => '上传失败',
	'order_success' => '上传成功',
	'order_retro' => '待重新上传',
);

$json = new JSON;    
switch ($act) 
{ 
	case 'retro_order':
		$order_sn = trim ( $_REQUEST ['order_sn'] );
		$taobao_order_sn = trim ( $_REQUEST ['taobao_order_sn'] );
		$sql = "update ecshop.sync_gz_haiguan_order_info set status='RETRO' where order_id = '{$taobao_order_sn}'";
		$result=$GLOBALS['db']->query($sql);
		$return['flag'] = 'SUCCESS';
		$return['message'] = '取消成功';
		print $json->encode($return);
		exit;
		break;
}

$result = call_user_func('search_haiguan_order',$_GET);
$smarty->assign('result',$result);
$smarty->assign('order_status_list',$order_status_list);
$smarty->assign('Pager',$result[-1]['pager']);
$smarty->assign('order_status',trim ( $_REQUEST ['order_status'] ));
$smarty->display ( 'gz_haiguan_order.htm' );
	
function getCondition() {
	global $db;
	$taobao_order_sn = trim ( $_REQUEST ['order_sn'] );
	$start = trim ( $_REQUEST ['start'] );
	
	$ended = trim ( $_REQUEST ['ended'] );
	$order_status = trim ( $_REQUEST ['order_status'] );
	$err_message = trim ( $_REQUEST ['err_message'] );
	
	$condition='';
	if ($taobao_order_sn != '') {
		$condition .= " and gzoi.order_id = '{$taobao_order_sn}' ";
	}

	if ($start != '') {
		$condition .= " and gzoi.created_stamp > '{$start}' ";
	}
	
	if ($ended != '') {
		$condition .= " and gzoi.created_stamp < '{$ended}' ";
	}
	if($err_message != ''){
		$condition .= " and gzoi.upload_note like '%{$err_message}%' ";
	}

	if($order_status != ''){
		$indicate_status="";
		switch ($order_status){
			case 'order_normal':
				$status = "NORMAL";
				break;
			case 'order_success':
				$status = "SUCCESS";
				break;
			case 'order_fail':
				$status = "ERROR";
				break;
			case 'order_retro':
				$status = "RETRO";
				break;
		}
		$condition .= " and gzoi.status = '{$status}' ";
	}else{
		//$condition .= " and skoi.status = 'ERROR' ";
	}
	
	return $condition;
}

function search_haiguan_order($args){
	global $db;
	$cond = getCondition();
	$index = 0;
	$page = intval($args['page']);
	$page = max(1, $page);
	$limit = 10;
	$offset = $limit * ($page-1);
	$session_party_id = $_SESSION['party_id'];
	$sqlc = "select count(1) from ecshop.sync_gz_haiguan_order_info gzoi where 1  {$cond} and gzoi.party_id={$session_party_id}";	
	$total = $db ->getOne($sqlc);
	$sql = "select gzoi.application_key,gzoi.order_id as taobao_order_sn,eoi.order_id,eoi.order_sn,eoi.taobao_order_sn,eoi.order_time,
			tsc.nick,gzoi.status,gzoi.created_stamp,gzoi.file_name,gzoi.upload_note as err_message
			from ecshop.sync_gz_haiguan_order_info gzoi
			inner join ecshop.ecs_order_info eoi on gzoi.order_id = eoi.taobao_order_sn
			inner join ecshop.taobao_shop_conf tsc on tsc.application_key = gzoi.application_key
		    where gzoi.party_id='{$session_party_id}' {$cond} order by gzoi.created_stamp desc LIMIT {$limit} OFFSET {$offset}";
	$order_error_list = $db->getAll($sql);
	if(!empty($order_error_list)){
		foreach($order_error_list as $order){
			$result[$index]['order_id']=$order['order_id'];
			$result[$index]['application_key']=$order['application_key'];
			$result[$index]['order_sn']=$order['order_sn'];
			$result[$index]['taobao_order_sn']=$order['taobao_order_sn'];
			$result[$index]['order_time']=$order['order_time'];
			$result[$index]['nick']=$order['nick'];
			$result[$index]['status']=$order['status'];
			$result[$index]['file_name']=$order['file_name'];
			$result[$index]['created_stamp']=$order['created_stamp'];
			$result[$index]['err_message']=$order['err_message'];
			$index++;
		}
	}
	$result[-1]['pager'] = pager($total,$limit,$page);
	return $result;
}
?>