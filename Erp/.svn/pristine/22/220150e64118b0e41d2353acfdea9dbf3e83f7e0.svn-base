<?php
define('IN_ECS', true);
require('../includes/init.php');
require_once(ROOT_PATH . "admin/function.php");
require_once(ROOT_PATH . "RomeoApi/lib_inventory.php");
require_once(ROOT_PATH . "includes/helper/array.php");

$taobao_fenxiao_shop_conf_id = isset($_REQUEST['taobao_shop_conf_id'])?trim($_REQUEST['taobao_shop_conf_id']):null;
$application_key = isset($_REQUEST['application_key'])?trim($_REQUEST['application_key']):null;
$nick = isset($_REQUEST['nick'])?trim($_REQUEST['nick']):null;
$type = isset($_REQUEST['type'])?trim($_REQUEST['type']):null;

Qlog::log('application_key='.$application_key.',nick='.$nick.',type='.$type);

$taobao_fenxiao_shop_list = null;
$taobao_fenxiao_shop_list = get_taobao_fenxiao_shop($application_key);

Qlog::log('pay_id='.$taobao_fenxiao_shop_list['pay_id'].',facility_id='.$taobao_fenxiao_shop_list['facility_id'].',shipping_id='.$taobao_fenxiao_shop_list['shipping_id']);

// 配送方式列表
$shipping_list=(array)$slave_db->getAll("select shipping_id, shipping_name from ecs_shipping where support_cod = 0");
$shipping_list = Helper_Array::toHashmap($shipping_list,'shipping_id','shipping_name');

// 支付方式列表
$payments = getPayments();
$payment_list = Helper_Array::toHashmap($payments, 'pay_id', 'pay_name');


$party_list = party_list();
$facility_list = facility_list();

$act = isset($_REQUEST['act']) ? trim($_REQUEST['act']) : false;
if( $act ){
	$pay_id = isset($_REQUEST['pay_id']) ? trim($_REQUEST['pay_id']) : null;
	$party_id = isset($_REQUEST['party_id']) ? trim($_REQUEST['party_id']) : null;
	$facility_id = isset($_REQUEST['facility_id']) ? trim($_REQUEST['facility_id']) : null;
	$shipping_id = isset($_REQUEST['shipping_id']) ? trim($_REQUEST['shipping_id']) : null;
	$status = isset($_REQUEST['status']) ? trim($_REQUEST['status']) : null;
	$is_stock_update = isset($_REQUEST['is_stock_update']) ? trim($_REQUEST['is_stock_update']) : null;
	$pay_name = $payment_list[$pay_id];
	$now = date('Y-m-d h:i:s', time());
	$admin = $_SESSION['admin_name'];
	if( $type == 'add' ){
		$sql = "
				INSERT INTO ecshop.taobao_fenxiao_shop_conf (taobao_fenxiao_shop_conf_id, party_id, pay_id, pay_name, application_key, status, 
					facility_id, shipping_id, is_stock_update, create_time, create_user, update_time, update_user)
				VALUES ('{$taobao_fenxiao_shop_conf_id}', '{$party_id}', '{$pay_id}', '{$pay_name}', '{$application_key}', '{$status}', 
					'{$facility_id}', '{$shipping_id}', '{$is_stock_update}', '{$now}', '{$admin}', '{$now}', '{$admin}')
				";
	} else if( $type == 'edit' ){
		$sql = "
				UPDATE ecshop.taobao_fenxiao_shop_conf SET party_id = '{$party_id}', pay_id = '{$pay_id}', pay_name = '{$pay_name}', status = '{$status}', 
					facility_id = '{$facility_id}',shipping_id = '{$shipping_id}', is_stock_update = '{$is_stock_update}', update_time = '{$now}', update_user = '{$admin}'
				WHERE application_key = '{$application_key}'
				";
	}
	//var_dump($sql);
	if( $GLOBALS['db']->query($sql) ){
		if( $type == 'add' ){
			$message = "分销店添加成功";
		} else if( $type == 'edit' ){
			$message = "分销店修改成功";
		}
		$taobao_fenxiao_shop_list = get_taobao_fenxiao_shop($application_key);
	} else{
		if( $type == 'add' ){
			$message = "分销店添加失败";
		} else if( $type == 'edit' ){
			$message = "分销店修改失败";
		}
	}
}

$smarty->assign('taobao_fenxiao_shop_list', $taobao_fenxiao_shop_list);
$smarty->assign('nick', $nick);
$smarty->assign('application_key', $application_key);
$smarty->assign('is_stock_update_list', array('OK' => '是', 'DELETE' => '否'));
$smarty->assign('status_list', array('OK' => '启用', 'DELETE' => '停用'));
$smarty->assign('party_id_list', party_list());
$smarty->assign('facility_id_list', facility_list());
$smarty->assign('shipping_list', $shipping_list);
$smarty->assign('payment_list', $payment_list);
$smarty->assign('message', $message);
$smarty->display('taobao/taobao_fenxiao_shop_conf.htm');

function get_taobao_fenxiao_shop($application_key){
	if( $application_key != null ){
		$sql = "select * from ecshop.taobao_fenxiao_shop_conf where application_key = '{$application_key}'";
		$taobao_fenxiao_shop_list = $GLOBALS['db']->getRow($sql);
		return $taobao_fenxiao_shop_list;
	}
	return null;
}
?>
