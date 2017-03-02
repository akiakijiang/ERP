<?php

/**
 * $Id$
 * 
 * 添加修改供应商品牌资料
 *
 */

define('IN_ECS', true);
require('includes/init.php');
require_once('function.php');

//admin_priv('purchase_add');

if ($_REQUEST['provider_brand_id']) {
	// 修改
	$provider_brand_id = $_REQUEST['provider_brand_id'];

	$sql = "SELECT * FROM ".$ecs->table('provider_brand_manage')." WHERE provider_brand_id = '$provider_brand_id' ";
	$getRow = $db->getRow($sql);

	$sql = "SELECT * FROM " . $ecs->table('provider_brand_person') . " WHERE provider_brand_id = '$provider_brand_id'";
	$getRow['contact_people'] = $db->getAll($sql);

	# @see function.php
	$getRow['provider_type'] = provider_type_mod($getRow['provider_type']);

	$smarty->assign('provider_brand_id', $provider_brand_id);
	if ($_REQUEST['action'] == 'view') {
		$smarty->assign('action', 'view');
	}
}else{
	$getRow = array();

	# @see function.php
	$getRow['provider_type'] = provider_type_add();
}

#p($getRow);

$smarty->assign('brand', $getRow);

$smarty->display('oukooext/brand-info.htm');

?>