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
    !empty($_REQUEST['act']) && in_array($_REQUEST['act'], array('update', 'remove'))
    ? $_REQUEST['act'] 
    : null ;

$facility_list = array_intersect_assoc ( facility_list (), get_user_facility () );

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
    
// 信息
$info = 
    !empty($_REQUEST['info'])
    ? $_REQUEST['info']
    : null ;

$filter = array('facility_id'=>$facility_id, 'location_seq_id'=>$location_seq_id);

if ($info) {
	$smarty->assign('message', $info);
}

// 更新
if (!is_null($act)) {
    if ($act == "remove") {
        remove_facility_location($facility_id, $location_seq_id);
        header("Location: facility_location_manage.php?facility_id={$facility_id}&message=".urlencode("库位 {$location_seq_id} 删除成功"));
        exit();
    }
    
    if (isset($_POST['facility_location'])) {
        $facilityLocation = (object) $_POST['facility_location'];
        $failed = array();
        $result = facility_location_save($facilityLocation, $failed);
        if ($result) {
            header("Location: facility_location_update.php?facility_id={$facilityLocation->facilityId}&location_seq_id={$facilityLocation->locationSeqId}&info=". urlencode("保存成功"));
            exit;
        } else {
            $smarty->assign('message',"该库存数据已存在或系统异常，请核实后再试");
        }
    }
}

// 编辑
if (isset($facility_id) && isset($location_seq_id)) {
	try {
        $handle = soap_get_client('FacilityService', 'ROMEO');
        $result = $handle->findLocationByPrimaryKey(array("facilityId"=> $facility_id,"locationSeqId"=>$location_seq_id));
        if ($result && is_object($result->return)) {
        	$facility_location = $result->return;
        	$smarty->assign("facility_location", $facility_location);
        }
	} catch (SoapFault $e) {
        $smarty->assign("message", $e->getMessage());
	}
}

$smarty->assign('filter', $filter);
$smarty->assign('facility_list', $facility_list);
$smarty->display('oukooext/facility_location_update.htm');
