<?php
define('IN_ECS', true);

require_once('includes/init.php');
require_once('includes/lib_service.php');
require_once('function.php');
require_once(ROOT_PATH."RomeoApi/lib_RMATrack.php");


$act = $_REQUEST['act'];
if ($act == 'edit_track') {
    admin_priv('customer_service_manage_order');

    $trackId = $_REQUEST['trackId'];
    $result = getTrackByTrackId($trackId);
    $track = $result->resultList->Track;
    $track->brandName = $_REQUEST['brandName'];
    $track->productName = $_REQUEST['productName'];
    $track->serialNumber = $_REQUEST['serialNumber'];
    $track->contactInfo = $_REQUEST['contactInfo'];
    $track->customerName = $_REQUEST['customerName'];
    $track->userComment  = $_REQUEST['userComment'];
    updateTrack($track);
    $back = $_POST['back'] ? $_POST['back'] : $_SERVER['HTTP_REFERER'];
    header("Location:".$back);
}

//编辑售后档案属性相关代码
elseif ($act == 'edit_track_attribute') {
    admin_priv('cg_back_goods');
    
    $trackId = $_REQUEST['trackId'];
    
    //修改供应商
    $result = getTrackByTrackId($trackId);
    $track = $result->resultList->Track;
    $track->supplierName = $_REQUEST['supplierName'];
    updateTrack($track);

    //获得售后档案相关属性
    $attributes = getTrackAttributeByTrackId($trackId);
//     pp($_POST);die();
    if ($attributes->total == 0) {
        $attribute_array = array();
        foreach ($rma_track_attribute_type as $type) {
            $ta = new stdClass();
            $ta->trackId = $trackId;
            $ta->trackAttributeTypeId = $type[0];
            $ta->name = $type[1];
            $value = $_REQUEST[$type[0]];
            if (is_array($value)) {
                $ta->value = join(',', $value);
            } else {
                $ta->value = $_REQUEST[$type[0]];
            }
            $attribute_array[] = $ta;
        }
        $attribute = new stdClass();
        $attribute->TrackAttribute = $attribute_array;
        createTrackAttribute($attribute);
    } else {
        $trackAttributes = wrap_object_to_array($attributes->resultList->TrackAttribute);
        
        foreach ($trackAttributes as $trackAttribute) {
            // 如果是售后档案属性是数组，那么就要做一次join
            if (is_array($_REQUEST[$trackAttribute->trackAttributeTypeId])) {
                $value = join(',', $_REQUEST[$trackAttribute->trackAttributeTypeId]);
            } else {
                $value = $_REQUEST[$trackAttribute->trackAttributeTypeId];
            }
            
            if ($trackAttribute->value != $value) {
                $trackAttribute->value = $value;
                updateTrackAttribute($trackAttribute);
            }
        }
    }

    $back = $_POST['back'] ? $_POST['back'] : $_SERVER['HTTP_REFERER'];
    header("Location:".$back);

}

