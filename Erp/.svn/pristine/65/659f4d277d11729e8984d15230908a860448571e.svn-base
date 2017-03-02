<?php

/**
 * $Id$
 * 
 * 添加修改供应商资料
 *
 */

define('IN_ECS', true);
require('includes/init.php');
require_once('function.php');

admin_priv('purchase_add');
$edit_currency = false;
if ($_REQUEST['provider_id']) {
	// 修改
	$provider_id = $_REQUEST['provider_id'];

	$sql = "SELECT * FROM ".$ecs->table('provider')." WHERE provider_id = '$provider_id' ";
	$getRow = $db->getRow($sql);

	$sql = "SELECT * FROM " . $ecs->table('provider_address') . " WHERE provider_id = '$provider_id'";
	$getRow['provider_address'] = $db->getAll($sql);

	$sql = "SELECT * FROM " . $ecs->table('provider_contact_person') . " WHERE provider_id = '$provider_id'";
	$getRow['contact_people'] = $db->getAll($sql);
	$getRow['contact_people_length'] = count($getRow['contact_people']);
	
	 
	$getRow['provider_type'] = provider_type_mod($getRow['provider_type']);

	$smarty->assign('provider_id', $provider_id);
	if ($_REQUEST['action'] == 'view') {
		$smarty->assign('action', 'view');
	}
	$edit_currency = true;
}else{
	$getRow = array();
	# @see function.php
	$getRow['provider_type'] = provider_type_add();
}

#p($getRow);
// 订单录入币种选择
//$currencies = array('HKD' => '港币', 'USD' => '美元', 'RMB' => '人民币', 'TWD' => '台币');
$smarty->assign('edit_currency', $edit_currency);
//$smarty->assign('currency', $currencies);
$smarty->assign('currencys', get_currency_style()); //币种数组
$smarty->assign('provider', $getRow);

$smarty->display('oukooext/supplier-info.htm');

?>