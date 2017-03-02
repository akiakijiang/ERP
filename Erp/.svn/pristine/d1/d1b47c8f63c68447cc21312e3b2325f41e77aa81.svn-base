<?php
/**
 * 仓库设置管理
 */
define('IN_ECS', true);

require_once ('includes/init.php');
admin_priv('facility_manage');
require_once (ROOT_PATH . 'includes/helper/array.php');
require_once (ROOT_PATH . 'RomeoApi/lib_inventory.php');
require_once (ROOT_PATH . 'includes/cls_json.php');

$request = // 请求 
	isset ($_REQUEST['request']) ? trim($_REQUEST['request']) : null;
// 期初时间
$act = !empty ($_REQUEST['act']) && in_array($_REQUEST['act'], array ('add','remove_facility')) ? $_REQUEST['act'] : null;

/*
 * 处理ajax请求
 */
if ($request == 'ajax') {

	$json = new JSON;
	switch ($act) {
		case 'remove_facility' :
			if (empty ($_POST['facility_id'])) {
				$smarty->assign('message', '没有成功获取到仓库ID，请重试');
				break;
			}
			$facilityId = $_POST['facility_id'];
			global $db;
			$sql = "update romeo.facility set is_closed = 'Y' where facility_id = '{$facilityId}' ";

			if (!$db->query($sql)) {
				$result = false;
			} else {
				$result = true;
			}
			break;
				
	}
	print $json->encode($result);
	exit;
}
// 提交操作
if (!is_null($act)) {
	switch ($act) {
		// 添加操作
		case 'add' :
			if (empty ($_POST['facility'])) {
				$smarty->assign('message', '提交数据有误');
				break;
			}
			$facility_name = trim($_POST['facility']['facility_name']);
			$sql = "select count(*) from romeo.facility where facility_name = '{$facility_name}' and IS_CLOSED='N'";
			$count = $db->getOne($sql);
			if($count>0){
				$smarty->assign('message', '需要维护仓库名已经存在，请先CTRL+F查询');
				break;
			}

			Helper_Array :: removeEmpty($_POST['facility']);

			// 创建仓库
			$facility_id = facility_insert($_POST['facility'], $failed);
			if ($facility_id === false) {
				$smarty->assign('message', reset($failed));
				break;
			}

			// 创建|更新容器
			$row['facility_id'] = $facility_id;
			$row['description'] = $_POST['facility']['facility_name'];
			if (is_numeric($_POST['facility']['container_id']) && $_POST['facility']['container_id'] > 0) {
				$row['container_id'] = $_POST['facility']['container_id'];
			}
			$result = facility_container_save($row, $failed = array ());
			if ($result) {
				$smarty->assign('message', '创建成功');
			} else {
				$smarty->assign('message', '设施对应的容器添加失败');
			}

			break;
	}
}

$sql = "SELECT f.facility_id,f.facility_name,f.is_out_ship,f.CREATED_STAMP,f.physical_facility,p.name as ownerPartyName " .
	" FROM romeo.facility f" .
	" inner join romeo.party p on p.party_id = f.OWNER_PARTY_ID" .
	" where f.IS_CLOSED='N' ";
$facility_list = $db->getAll($sql);
$physical_facility_list_sql = "select distinct physical_facility from romeo.facility where is_closed='N' ";
$physical_facility_list = $db->getCol($physical_facility_list_sql);
$smarty->assign ('physical_facility_list', $physical_facility_list);
$smarty->assign('facility_list', $facility_list);
$smarty->assign('party_options_list', party_options_list());
$smarty->display('oukooext/facility_manage.htm');