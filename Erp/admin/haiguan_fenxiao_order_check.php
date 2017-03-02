<?php
define('IN_ECS', true);
require_once ('includes/init.php');
require_once(ROOT_PATH . 'includes/cls_json.php');

//同步状态
global $db;
$sql = "SELECT transfer_status, count(*) as count from ecshop.haiguan_order_info GROUP BY transfer_status";
$sync_status_list = $db->getAll($sql);
$sync_status_mapping = array('NORMAL' => '订单转换正常', 'ERROR'=>'订单转换失败');
foreach ($sync_status_list as $key => $value) {
	$sync_status_list[$key]['name'] = isset($sync_status_mapping[$value['transfer_status']]) ? $sync_status_mapping[$value['transfer_status']] : '其他['.$value['transfer_status'].']';
}
array_unshift($sync_status_list, array('sync_status' =>'ALL', 'name' => '不限'));
$smarty->assign('sync_status_list', $sync_status_list);

$json = new JSON;  
if($_REQUEST['act'] == 'ajax') {
	switch($_REQUEST['action']) {
		case 'GetData':
		$tid = $_REQUEST['tid'];
		$sql_order = "select * from ecshop.haiguan_order_info 
				where tid = '{$tid}'";
		$order_info = $db -> getRow($sql_order);
		$sql_order_goods = "select * from ecshop.haiguan_order_goods where haiguan_order_id = '{$order_info['haiguan_order_id']}'";
		$order_goods = $db -> getAll($sql_order_goods);
		$order_info['goods'] = $order_goods;
		$order_info['is_prepay'] = check_order($tid);
		print $json->encode($order_info);
		exit;
		break;
		case 'EditOrder':
		$return['error'] = 0;
		$return['message'] = '修改成功！';
		$tid = trim($_REQUEST['edit_order_data']['tid']);
		$amount = trim($_REQUEST['edit_order_data']['amount']);
		$post_fee = trim($_REQUEST['edit_order_data']['post_fee']);
		$goods_amount = trim($_REQUEST['edit_order_data']['goods_amount']);
		$payment = trim($_REQUEST['edit_order_data']['payment']);
		$payment_code = trim($_REQUEST['edit_order_data']['payment_code']);
		$trade_trans_no = trim($_REQUEST['edit_order_data']['trade_trans_no']);
		$haiguan_order_id = trim($_REQUEST['edit_order_data']['haiguan_order_id']);
		$account = trim($_REQUEST['edit_order_data']['account']);
		$goods = $_REQUEST['edit_order_data']['goods'];
		if(check_data($_REQUEST)) {
			$db -> start_transaction();
			$sql_order_update = "update ecshop.haiguan_order_info set amount = '{$amount}',post_fee = '{$post_fee}',goods_amount='{$goods_amount}',
					            payment='{$payment}',payment_code='{$payment_code}',trade_trans_no='{$trade_trans_no}', tid = '{$tid}',account = '{$account}', last_updated_stamp = NOW() 
					            where haiguan_order_id = '{$haiguan_order_id}'";
			if(!$db -> query($sql_order_update)) {
				$db -> rollback();
				$return['error'] = 1;
				$return['message'] = '修改出错，请重试；重试之后还不行，请联系产品技术部！';
			} else {
				foreach($goods as $key=>$good) {
					$sql_order_goods_update ="update ecshop.haiguan_order_goods set product_id = '{$good['product_id']}', outer_id = '{$good['outer_id']}',
							                goods_name = '{$good['goods_name']}', quantity = '{$good['quantity']}', amount = '{$good['amount']}',
											added_value_tax_amount = '{$good['added_value_tax_amount']}', consumption_duty_amount = '{$good['consumption_duty_amount']}', last_updateds_stamp = NOW() 
											where order_goods_id = '{$good['order_goods_id']}'";
					if(!$db -> query($sql_order_goods_update)) {
						$db -> rollback();
						$return['error'] = 1;
						$return['message'] = '修改出错，请重试；重试之后还不行，请联系产品技术部！';
						break;
					}	
				}
			}			
		}else {
			$return['error'] = 1;
			$return['message'] = '【金额有误】或者【ERP_Goods_Style_ID有误】，请检查后重试！';
		}
	
		if($return['error'] == 0) {
			$db->commit();
		}
		print $json->encode($return);
		exit();	
		break;
		case 'InitTransferStatus':
		$tid = $_REQUEST['tid'];
		$sql_update = "update ecshop.haiguan_order_info set transfer_status = 'NORMAL',transfer_note = '' where tid = '{$tid}'";
		if($db -> query($sql_update)) {
			$return['error'] = 0;
			$return['message'] = '初始化成功';
			print $json->encode($return);		
		}
		exit;
		break;
		
	}
}else {
	$sql = get_sql();
	$order_list = $db -> getAll($sql);
	$smarty->assign('data_list', $order_list);
	$smarty->display('haiguan_fenxiao_order_check.html');
	die();
	
	
	
}


function check_order($tid) {
	global $db;
	$sql = "select count(1) from ecshop.haiguan_order_info hoi
			left join ecshop.ecs_order_info eoi on eoi.taobao_order_sn = hoi.tid
			left join ecshop.distribution_order_adjustment doa on doa.order_id = eoi.order_id
			where doa.order_id is not null and hoi.tid = '{$tid}'";
//	var_dump($sql);
	$count = $db -> getOne($sql);
	if($count > 0) {
		return false;
	}else {
		return true;
	}
}

function check_data($args) {
	$tid = trim($args['edit_order_data']['tid']);
	$amount = trim($args['edit_order_data']['amount']);
	$post_fee = trim($args['edit_order_data']['post_fee']);
	$goods_amount = trim($args['edit_order_data']['goods_amount']);
	$payment = trim($args['edit_order_data']['payment']);
	return check_header($args)&&check_items($args);
}

function check_header($args){
	$amount = trim($args['edit_order_data']['amount']);
	$post_fee = trim($args['edit_order_data']['post_fee']);
	$goods_amount = trim($args['edit_order_data']['goods_amount']);
	$payment = trim($args['edit_order_data']['payment']);
	return ($payment == $goods_amount) && ($amount + $post_fee == $goods_amount);
}

function check_items($args) {
	global $db;
	$goods = $args['edit_order_data']['goods'];
	$goods_amount = 0;
	$amount = trim($args['edit_order_data']['amount']);
	foreach($goods as $key=>$good) {
		$goods_amount = $goods_amount + $good['amount'];
		$sql = "select count(*) from ecshop.haiguan_goods where outer_id = '{$good['outer_id']}' and party_id = '{$_SESSION['party_id']}'";
		$count = $db -> getOne($sql);
		if($count < 1) {
			return false;
		}
	}
	return $goods_amount == $amount;
}

function get_sql(){
	if(!isset($_REQUEST['search_type']) || $_REQUEST['search_type'] == 'NEED_SYNC_BUT_NOT'){
		$sql = "select hoi.*,eoi.order_id,eoi.order_sn from ecshop.haiguan_order_info hoi
				left join ecshop.ecs_order_info eoi on hoi.tid = eoi.taobao_order_sn
				where eoi.order_id is null and hoi.transfer_status = 'NORMAL' and hoi.order_time > DATE_SUB(CURDATE(),INTERVAL 100 day)";

	}else{
		$sql = "select hoi.*,eoi.order_id,eoi.order_sn from ecshop.haiguan_order_info hoi
				left join ecshop.ecs_order_info eoi on hoi.tid = eoi.taobao_order_sn
				where 1 " . get_condition() . " order by hoi.order_time limit 100";
	}
	return $sql;
} 

function get_condition() {
	$cond = '';
	$order_sn = trim($_REQUEST["search_order_sn"]);
	$taobao_order_sn = trim($_REQUEST["search_taobao_order_sn"]);
	$order_time_start = trim($_REQUEST["search_order_time_start"]);
	$order_time_end = trim($_REQUEST["search_order_time_end"]);
	$sync_status = trim($_REQUEST["search_transfer_status"]);
	if($order_sn) {
		$cond .= " and eoi.order_sn like '{$order_sn}%' ";
	}
	if($taobao_order_sn) {
		$cond .= " and hoi.tid like '{$taobao_order_sn}%' ";
	}
	if($order_time_start) {
		$cond .= " and hoi.order_time >= '{$order_time_start}' ";
	}
	if($order_time_end) {
		$cond .= " and hoi.order_time < DATE_ADD('{$order_time_end}', INTERVAL 1 day) ";
	}
	if($sync_status && $sync_status!='ALL') {
		$cond .= " and hoi.transfer_status = '{$sync_status}' ";
	}

	return $cond;
	
}

?>