<?php 
/**
 * 产品-仓库库位管理
 */
define('IN_ECS', true);

require_once('includes/init.php');
admin_priv('facility_location_manage');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');
require_once(ROOT_PATH . 'RomeoApi/lib_facility.php');

// 仓库列表
$facility_list = facility_list();

// 操作
$act = 
    ! empty($_REQUEST['act']) && in_array($_REQUEST['act'], array('delete', 'ajaxquery'))
    ? $_REQUEST['act'] 
    : null ;

// 当前仓库
$facility_id = 
    ! empty($_REQUEST['facility_id']) && array_key_exists($_REQUEST['facility_id'], $facility_list)
    ? $_REQUEST['facility_id']
    : key($facility_list) ;

// 库位
$location_seq_id = 
    ! empty($_REQUEST['location_seq_id'])
    ? $_REQUEST['location_seq_id']
    : null;

// 产品
$product_id = 
    ! empty($_REQUEST['product_id'])
    ? $_REQUEST['product_id']
    : null;    

// 消息
$info = 
    ! empty($_REQUEST['info'])
    ? $_REQUEST['info']
    : null;
    
$filter = array('facility_id'=>$facility_id,'location_seq_id'=>$location_seq_id);

if ($info) {
	$smarty->assign("message", $info);
}

if (!is_null($act)) {
    switch ($act) {
        // 移除产品
        case 'delete' :
        	if (isset($facility_id) && isset($location_seq_id) && isset($product_id)) {
        		try {
                    $handle = soap_get_client('FacilityService', 'ROMEO');
                    $param=array("facilityId"=>$facility_id,"locationSeqId"=>$location_seq_id,"productId"=>$product_id);
                    $handle->removeProductFacilityLocation($param);
                    header("Location: product_facility_location_manage.php?faciliy_id={$facility_id}&location_seq_id={$location_seq_id}&info=". urlencode("已移除"));
                    exit;
        		} catch (SoapFault $e) {
        			$smarty->assign("message", $e->getMessage());
        		}
        	}
        break;
    }
}

// 取得库位信息
if ((isset($facility_id) && isset($location_seq_id)) && $facility_location = facility_location_get_by_pk($facility_id, $location_seq_id)) {
    $handle = soap_get_client('FacilityService', 'ROMEO');
    $result = $handle->findAllProductFacilityLocation(array("facilityId"=>$facility_id,"locationSeqId"=>$location_seq_id));
    if (isset($result) && isset($result->return) && isset($result->return->ProductFacilityLocation)) {
        $product_faclity_location_list = is_array($result->return->ProductFacilityLocation) ? 
            $result->return->ProductFacilityLocation : array($result->return->ProductFacilityLocation);
    } 
    
    // 取得产品名
    if ($product_faclity_location_list) {
    	$productIds=array();
        foreach ($product_faclity_location_list as $product) {
        	array_push($productIds, $product->productId);
        }
        
        $sql="SELECT PRODUCT_ID,PRODUCT_NAME FROM romeo.product WHERE PRODUCT_ID ". db_create_in($productIds);
        $result=$slave_db->getAllRefby($sql, array('PRODUCT_ID'), $ref_fields, $ref_rowset);
        foreach ($product_faclity_location_list as $key=>$product) {
            $product_faclity_location_list[$key]->productName = $ref_rowset['PRODUCT_ID'][$product->productId][0]['PRODUCT_NAME'];
        }
    }
    
    $smarty->assign("facility_location", $facility_location);
    $smarty->assign("product_faclity_location_list", $product_faclity_location_list);

}
else {
    $smarty->assign("message", "找不到对应的库位");
}

if ($act == 'ajaxquery') {
    $json_array = array(
    	'count' => count($product_faclity_location_list),
        'facility_id' => $facility_id,
        'location_seq_id' => $location_seq_id
    );
    print json_encode($json_array);
    exit();
}

$smarty->assign("facility_list", facility_list());
$smarty->assign('filter', $filter);
$smarty->display('oukooext/product_facility_location_manage.htm');
