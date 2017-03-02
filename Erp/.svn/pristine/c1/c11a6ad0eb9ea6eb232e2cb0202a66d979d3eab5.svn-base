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
	die('没有权限');
}


$act = isset($_REQUEST['act'])?trim($_REQUEST['act']):false;
$order_status_list= array(
	'' => '全部',
	'order_fail' => '推送失败',
	'order_success' => '推送成功',
	'order_retro' => '待重推',
	'order_close' => '关闭订单'
);
$csv = $_REQUEST['csv'];

$json = new JSON;    
switch ($act) 
{ 
	case 'retro_order':
		$order_sn = trim ( $_REQUEST ['order_sn'] );
		$kjg_order_id = trim ( $_REQUEST ['kjg_order_id'] );
		$sql = "update ecshop.sync_kjg_order_info set status='RETRO',err_message='推送失败手动回退' where kjg_order_id = '{$kjg_order_id}'";
		$result=$GLOBALS['db']->query($sql);
		if($result) {
			$return['flag'] = 'SUCCESS';
			$return['message'] = '回退成功';
			//记录操作
	        $record_sql = "INSERT INTO risky_actions (admin_user, type, timestamp, php_location, form_name, `sql`, comment) " .
	            		"VALUES ('{$_SESSION['admin_name']}', 'RETRO', NOW(), 'haiguan_order.php', 'retro_order', '".mysql_real_escape_string($sql)."', '申报取消')";
	        $GLOBALS['db']->query($record_sql);
		} else {
			$return['flag'] = 'FAIL';
			$return['message'] = '回退失败，请重试';
		}	
		print $json->encode($return);
		exit;
		break;
	case 'close_order':
		$order_sn = trim ( $_REQUEST ['order_sn'] );
		$kjg_order_id = trim ( $_REQUEST ['kjg_order_id'] );
		$mft_no= trim ( $_REQUEST ['mft_no'] );
		$applicationKey = trim ( $_REQUEST ['application_key'] );
		try{						  
			$client = new SoapClient($erpsync_webservice_url.'SyncKuajinggouService?wsdl');
			$request=array("hours"=>24,"applicationKey"=>$applicationKey,"mft_no"=>$mft_no);
			$response=$client->CloseKuajinggouOrder($request);
//			var_dump($response->return);
			if($response->return == 'SUCCESS' || $response->return == '该申报单不存在或者已关闭。') {
				$sql = "update ecshop.sync_kjg_order_info set status='CLOSE',err_message='关闭订单' where kjg_order_id = '{$kjg_order_id}'";
				$result=$GLOBALS['db']->query($sql);
				//记录操作
		        $record_sql = "INSERT INTO risky_actions (admin_user, type, timestamp, php_location, form_name, `sql`, comment) " .
		            		"VALUES ('{$_SESSION['admin_name']}', 'CLOSE', NOW(), 'haiguan_order.php', 'close_order', '".mysql_real_escape_string($sql)."', '申报关闭')";
		        $GLOBALS['db']->query($record_sql);	
		        $return['flag'] = 'SUCCESS';
				$return['message'] = '关闭成功';
			} else {
				$return['flag'] = 'FAIL';
				$return['message'] = $response->return;
				print $json->encode($return);
				exit;
				break;	
			}			
		}catch(Exception $e){
			$message = ''.$e->getMessage();
			$return['flag'] = 'FAIL';
			$return['message'] = 'exception!';
		}		
		print $json->encode($return);
		exit;
		break;	
	case 'close_retra':
		$order_sn = trim ( $_REQUEST ['order_sn'] );
		$kjg_order_id = trim ( $_REQUEST ['kjg_order_id'] );
		$mft_no= trim ( $_REQUEST ['mft_no'] );
		$applicationKey = trim ( $_REQUEST ['application_key'] );
		try{						  
			$client = new SoapClient($erpsync_webservice_url.'SyncKuajinggouService?wsdl');
			$request=array("hours"=>24,"applicationKey"=>$applicationKey,"mft_no"=>$mft_no);
			$response=$client->CloseKuajinggouOrder($request);
			if($response->return == 'SUCCESS' || $response->return == '该申报单不存在或者已关闭。') {
				$sql = "update ecshop.sync_kjg_order_info set status='RETRO',err_message='推送失败手动回退' where kjg_order_id = '{$kjg_order_id}'";
				$result=$GLOBALS['db']->query($sql);
				//记录操作
		        $record_sql = "INSERT INTO risky_actions (admin_user, type, timestamp, php_location, form_name, `sql`, comment) " .
		            		"VALUES ('{$_SESSION['admin_name']}', 'RETRO', NOW(), 'haiguan_order.php', 'close_retra', '".mysql_real_escape_string($sql)."', '申报关闭')";
		        $GLOBALS['db']->query($record_sql);
		        
			} else {
				$return['flag'] = 'FAIL';
				$return['message'] = $response->return;
				print $json->encode($return);
				exit;
				break;	
			}		
		}catch(Exception $e){
			$message = ''.$e->getMessage();
		}
		$return['flag'] = 'SUCCESS';
		$return['message'] = '关闭并回退成功';	
		print $json->encode($return);
		exit;
		break;		
	case 'close_only':
		$order_sn = trim ( $_REQUEST ['order_sn'] );
		$kjg_order_id = trim ( $_REQUEST ['kjg_order_id'] );
		$mft_no= trim ( $_REQUEST ['mft_no'] );
		$applicationKey = trim ( $_REQUEST ['application_key'] );

		$sql = "update ecshop.sync_kjg_order_info set status='CLOSE' where kjg_order_id = '{$kjg_order_id}'";
		$result=$GLOBALS['db']->query($sql);
		if($result) {
			$return['flag'] = 'SUCCESS';
			$return['message'] = '关闭成功';
		} else {
			$return['flag'] = 'FAIL';
			$return['message'] = '关闭失败';
		}
		$record_sql = "INSERT INTO risky_actions (admin_user, type, timestamp, php_location, form_name, `sql`, comment) " .
            		"VALUES ('{$_SESSION['admin_name']}', 'CLOSE', NOW(), 'haiguan_order.php', 'close_only', '".mysql_real_escape_string($sql)."', '申报关闭')";
        $GLOBALS['db']->query($record_sql);
		print $json->encode($return);
		exit;
		break;
	case 'resend_order':
		$order_sn = trim ( $_REQUEST ['order_sn'] );
		$kjg_order_id = trim ( $_REQUEST ['kjg_order_id'] );
		$mft_no= trim ( $_REQUEST ['mft_no'] );
		$applicationKey = trim ( $_REQUEST ['application_key'] );
		try{						  
			$sql = "update ecshop.sync_kjg_order_info set status='RETRO',err_message='关闭订单手动回退' where kjg_order_id = '{$kjg_order_id}'";
			$result=$GLOBALS['db']->query($sql);
			if($result) {
				$record_sql = "INSERT INTO risky_actions (admin_user, type, timestamp, php_location, form_name, `sql`, comment) " .
            		"VALUES ('{$_SESSION['admin_name']}', 'RETRO', NOW(), 'haiguan_order.php', 'resend_order', '".mysql_real_escape_string($sql)."', '申报关闭')";
        		$GLOBALS['db']->query($record_sql);
			}else{
				$return['flag'] = 'FAIL';
				$return['message'] = '回退失败';
				print $json->encode($return);
				exit;
				break;
			}		
		}catch(Exception $e){
			$message = ''.$e->getMessage();
		}
		$return['flag'] = 'SUCCESS';
		$return['message'] = '回退成功';
		print $json->encode($return);
		exit;
		break;
	case 'batch_retro_order':
		$orders_list= $_POST['checked'];
		$sql = "update ecshop.sync_kjg_order_info set status='RETRO',err_message='推送失败手动回退' where kjg_order_id".db_create_in($orders_list);
		$result=$GLOBALS['db']->query($sql);
		if($result) {
			$return['flag'] = 'SUCCESS';
			$return['message'] = '批量回退成功';
		} else {
			$return['flag'] = 'FAIL';
			$return['message'] = '批量回退失败，请重试';
		}		
		$record_sql = "INSERT INTO risky_actions (admin_user, type, timestamp, php_location, form_name, `sql`, comment) " .
            		"VALUES ('{$_SESSION['admin_name']}', 'RETRO', NOW(), 'haiguan_order.php', 'batch_retro_order', '".mysql_real_escape_string($sql)."', '申报批量回退')";
        $GLOBALS['db']->query($record_sql);	
		$smarty->assign('message',$return['message']);
//		print $json->encode($return);
//		exit;
		break;
}

if($csv){
	$haiguan_orders = search_haiguan_order($_GET);
//	var_dump($haiguan_orders);
	$smarty->assign('haiguan_orders', $haiguan_orders);
	header("Content-type:application/vnd.ms-excel");
    header("Content-Disposition:filename=" .iconv("UTF-8", "GB18030", "申报订单报表") . ".csv");
    $out = $smarty->fetch('oukooext/haiguan_order_csv.htm');
    echo iconv("UTF-8", "GB18030", $out);
    exit();	
}

$result = call_user_func('search_haiguan_order',$_GET);
$smarty->assign('type',$act);
$smarty->assign('result',$result);
$smarty->assign('order_status_list',$order_status_list);
$smarty->assign('Pager',$result[-1]['pager']);
$smarty->assign('order_status',trim ( $_REQUEST ['order_status'] ));
$smarty->display ( 'haiguan_order.htm' );
	
function getCondition() {
	global $db;
	$taobao_order_sn = trim ( $_REQUEST ['order_sn'] );
	$start = trim ( $_REQUEST ['start'] );
	
	$ended = trim ( $_REQUEST ['ended'] );
	$order_status = trim ( $_REQUEST ['order_status'] );
	$err_message = trim ( $_REQUEST ['err_message'] );
	
	$condition='';
	if ($taobao_order_sn != '') {
		$condition .= " and skoi.taobao_order_sn = '{$taobao_order_sn}' ";
	}

	if ($start != '') {
		$condition .= " and skoi.created_stamp > '{$start}' ";
	}
	
	if ($ended != '') {
		$condition .= " and skoi.created_stamp < '{$ended}' ";
	}
	if($err_message != ''){
		$condition .= " and skoi.err_message like '%{$err_message}%' ";
	}

	if($order_status != ''){
		$indicate_status="";
		switch ($order_status){
			case 'order_success':
				$status = "SUCCESS";
				break;
			case 'order_fail':
				$status = "ERROR";
				break;
			case 'order_retro':
				$status = "RETRO";
				break;
			case 'order_close':
				$status = "CLOSE";
				break;
		}
		$condition .= " and skoi.status = '{$status}' ";
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
	$sqlc = "select count(1) from ecshop.sync_kjg_order_info skoi 
			inner join ecshop.ecs_order_info eoi on skoi.taobao_order_sn = eoi.taobao_order_sn
			where eoi.order_status = '1'  {$cond} and skoi.party_id={$session_party_id}";	
	$total = $db ->getOne($sqlc);
	$sql = "select skoi.application_key,skoi.kjg_order_id,eoi.order_id,eoi.order_sn,eoi.taobao_order_sn,eoi.order_time,
			kap.nick,skoi.status,skoi.mft_no,skoi.payment_no,skoi.created_stamp, skoi.err_message
			from ecshop.sync_kjg_order_info skoi
			inner join ecshop.ecs_order_info eoi on skoi.taobao_order_sn = eoi.taobao_order_sn
			inner join ecshop.haiguan_api_params kap on skoi.application_key = kap.application_key
		    where eoi.order_status = '1' and skoi.party_id='{$session_party_id}' {$cond} order by skoi.created_stamp desc ";
	if($args['csv'] == null) {
		$sql = $sql . "LIMIT {$limit} OFFSET {$offset}";
	}
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
			$result[$index]['mft_no']=$order['mft_no'];
			$result[$index]['payment_no']=$order['payment_no'];
			$result[$index]['created_stamp']=$order['created_stamp'];
			$result[$index]['err_message']=$order['err_message'];
			$result[$index]['kjg_order_id']=$order['kjg_order_id'];
			$index++;
		}
	}
	$result[-1]['pager'] = pager($total,$limit,$page);
	return $result;
}
?>