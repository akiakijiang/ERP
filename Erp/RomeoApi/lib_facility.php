<?php 

/**
 * 设施相关服务
 */

include_once('lib_soap.php');
include_once('lib_cache.php');
include_once('lib_inventory.php');

// TODO, 设施相关的代码有部分在lib_inventory.php中，需要转移过来

/**
 * 通过主键取得设备信息
 *
 * @param string $facility_id
 */
function facility_get_by_pk($facility_id) {
    try {
        $handle = soap_get_client('FacilityService', 'ROMEO');
        $result = $handle->findFacilityByPrimaryKey(array("facilityId"=>$facility_id));
        if ($result && is_object($result->return)) {
            $facility = $result->return;
        }
    } catch (SoapFault $e) {
        $facility = null;
    }
    return $facility;
}


/**
 * 创建一个库位
 *
 * @param stdClass $obj
 * @param array $failed 错误信息
 */
function facility_location_save($obj, & $failed = array()) {
	if (!is_array($failed)) $failed = array();
	
	if (!isset($obj->facilityId) || !$obj->facilityId) {
		array_push($failed, "没有指定仓库");
	}
	else if (!isset($obj->locationSeqId) || !$obj->locationSeqId) {
		array_push($failed, "没有指定库位标识符");
	}
	else {
		try {
	        $handle = soap_get_client('FacilityService', 'ROMEO');
	        $handle->createFacilityLocation(array('facilityLocation' => $obj));
	        return true;
		} catch (SoapFault $e) {
			array_push($failed, $e->getMessage());
		}
	}
	return false;
}

function remove_facility_location($facility_id, $location_seq_id) {
    try {
        $handle = soap_get_client('FacilityService', 'ROMEO');
        $obj = new stdClass();
        $obj->facilityId = $facility_id;
        $obj->locationSeqId = $location_seq_id;
        $handle->removeFacilityLocation(array('facilityLocation' => $obj));
        return $handle->response;
    } catch (SoapFault $e) {
        return false;
    }
}

/**
 * 创建产品和库位的关系
 *
 * @param unknown_type $obj
 * @param unknown_type $failed
 */
function product_facility_location_save($obj, &$failed = array()) {
    if (!is_array($failed)) $failed = array();
	
    if (!$obj->productId) {
        if (!isset($obj->goodsId)) {
            array_push($failed, "没有指定产品或商品");
        }
        else {
            if (!$obj->styleId) { $obj->styleId = 0; }
            $obj->productId = getProductId($obj->goodsId, $obj->styleId);
            if (!$obj->productId) {
                array_push($failed, "根据商品找不到产品");
            }
        }
    }
	
    if (!$obj->facilityId) {
        array_push($failed, "没有指定仓库");
    }
    else if (!$obj->locationSeqId) {
        array_push($failed, "没有指定仓库库位");
    }
    else if (!$obj->productId) {
    	array_push($failed, "没有指定产品");
    } else {
    	try {
            $handle = soap_get_client('FacilityService', 'ROMEO');
            $handle->createProductFacilityLocation(array('productFacilityLocation' => $obj));
            return true;
    	} catch (SoapFault $e) {
            array_push($failed, $e->getMessage());
    	}
    }
    
    return false;
}

/**
 * 取得某个仓库设置下的所有库位
 * 
 * @param int facility_id 设施ID
 * @return array 
 */
function facility_location_list_by_facility($facility_id) {
	$facility_location_list=array();
	try {
		$handle = soap_get_client('FacilityService', 'ROMEO');
		$result = $handle->findAllLocationByFacilityId(array("facilityId"=> $facility_id));
		if ($result && isset($result->return->FacilityLocation)) {
			if (!is_array($result->return->FacilityLocation)) {
				$facility_location_list[] = $result->return->FacilityLocation; 
			} else {
				$facility_location_list = $result->return->FacilityLocation;
			}
		}
	} catch (SoapFault $e) {
		
	}
	return $facility_location_list;
}

/**
 * 取得某个产品的库位
 *
 * @param int $product_id
 * @return array
 */
function facility_location_list_by_product($product_id,$facility_id=null) {
    $handle=soap_get_client('FacilityService', 'ROMEO');
    $result=$handle->findAllFacilityLocationByProductId(array('productId'=>$product_id,'facilityId'=>$facility_id));
    if(isset($result) && isset($result->return) && isset($result->return->FacilityLocation)) {
        if(!is_array($result->return->FacilityLocation)) {
            $facility_location_list=array($result->return->FacilityLocation);
        } else {
            $facility_location_list=$result->return->FacilityLocation;
        }
    } else {
    	$facility_location_list=array();
    }
    return $facility_location_list;
}

/**
 * 通过主键查找
 */
function facility_location_get_by_pk($facility_id, $location_seq_id) {
    try {
        $handle = soap_get_client('FacilityService', 'ROMEO');
        $result = $handle->findLocationByPrimaryKey(array("facilityId"=> $facility_id,"locationSeqId"=>$location_seq_id));
        if ($result && is_object($result->return)) {
            $productLocation = $result->return;
        }
    } catch (SoapFault $e) {
        $productLocation = null;
    }
    return $productLocation;
}

/**
 * 根据仓库名查找仓库信息
 */
function get_facility($facility_name){
	global $db;
	$facility_name = trim($facility_name);
	$sql = "
	    select facility_id,facility_name 
		from romeo.facility
		where facility_name = '{$facility_name}'
	";
	$result = $db -> getRow($sql);
	return $result;
}

