<?php 
/**
 * 仓库库位管理
 */
define('IN_ECS', true);

require_once('includes/init.php');
admin_priv('facility_location_manage');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');
require_once(ROOT_PATH . 'RomeoApi/lib_facility.php');

// 期初时间
$act = 
    !empty($_REQUEST['act']) && in_array($_REQUEST['act'], array('update'))
    ? $_REQUEST['act'] 
    : null ;

$facility_list = facility_list();

// 当前仓库ID
$facility_id = 
    !empty($_REQUEST['facility_id']) && array_key_exists($_REQUEST['facility_id'], $facility_list)
    ? $_REQUEST['facility_id']
    : null ;

// 库位ID
$location_seq_id = 
    !empty($_REQUEST['location_seq_id']) 
    ? $_REQUEST['location_seq_id'] 
    : null ;
    
// 消息
$info =
    !empty($_REQUEST['info'])
    ? $_REQUEST['info']
    : false;

if ($info) {
	$smarty->assign('message', $info);
}

$filter = array('facility_id'=>$facility_id, 'location_seq_id'=>$location_seq_id);

// 更新
if (!is_null($act)) {
	if (isset($_POST['product_facility_location'])) {
		$productFacilityLocation = (object) $_POST['product_facility_location'];
		$failed = array();
		$result = product_facility_location_save($productFacilityLocation, $failed);
		if ($result) {
			header("Location: product_facility_location_update.php?facility_id={$productFacilityLocation->facilityId}&location_seq_id={$productFacilityLocation->locationSeqId}&info=". urlencode("保存成功"));
            exit;
		} else {
			$smarty->assign('message', reset($failed));
		}
	}
}

$smarty->assign('filter', $filter);
$smarty->assign('facility_list', $facility_list);
$smarty->display('oukooext/product_facility_location_update.htm');
