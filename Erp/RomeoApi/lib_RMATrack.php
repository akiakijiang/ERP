<?php

/**
 * 支付相关服务函数库
 * 
 * @author zwsun@oukoo.com
 * @copyright ouku.com
 */
 
require_once('lib_soap.php');



function RMATrack_get_soap_client()
{
    return soap_get_client('RMATrackService', 'ROMEO', 'Soap_Client');
}

function   createTrack($track) {
    $client = RMATrack_get_soap_client();
    return $client->createTrack($track);
}

/* 更新售后档案 */
function updateTrack($track) {
    $client = RMATrack_get_soap_client();
    return $client->updateTrack($track);
}


 /* 根据Id 查询售后档案 */
function getTrackByTrackId($trackId) {
    $client = RMATrack_get_soap_client();
    return $client->getTrackByTrackId($trackId);
}


 /* 根据售后服务Id 查询售后档案 */
function getTrackByServiceId($serviceId) {
    $client = RMATrack_get_soap_client();
    return $client->getTrackByServiceId($serviceId);
}


 /* 根据orderId 查询 售后档案 */
function   getTrackByOrderId($orderId) {
    $client = RMATrack_get_soap_client();
    return $client->getTrackByOrderId($orderId);
}


 /* 根据各个参数查询售后档案 */
function getTrack($queryParam) {
    $client = RMATrack_get_soap_client();
    return $client->getTrack($queryParam);
}


 /* 查询售后档案的历史记录 */ 
function getTrackAction($queryParamTrackAction) {
    $client = RMATrack_get_soap_client();
    return $client->getTrackAction($queryParamTrackAction);
}

 
 /* 创建售后档案的属性 */
function createTrackAttribute($trackAttribute) {
    $client = RMATrack_get_soap_client();
    return $client->createTrackAttribute($trackAttribute);
}


 /* 更新售后档案的属性 */
function updateTrackAttribute($trackAttribute) {
    $client = RMATrack_get_soap_client();
    return $client->updateTrackAttribute($trackAttribute);
}


 /* 删除售后档案属性 */
function deleteTrackAttribute($trackAttributeId) {
    
}


 /* 查询售后档案的属性 */
function getTrackAttributeByTrackId($trackId) {
    $client = RMATrack_get_soap_client();
    return $client->getTrackAttributeByTrackId($trackId);
}



/* 获得属性的类型 */
function getTrackAttributeType() {
    $client = RMATrack_get_soap_client();
    return $client->getTrackAttributeType();
}


 /* 创建属性的类型，用于配置系统 */
function createTrackAttributeType($trackAttributeType) {
    $client = RMATrack_get_soap_client();
    return $client->createTrackAttributeType($trackAttributeType);
}


 /* 删除属性的类型，用于配置系统 */
function deleteTrackAttributeType($trackAttributeTypeId) {
    
}

 /* 创建售后档案的类型，用于配置系统 */
function createTrackType($trackType) {
    $client = RMATrack_get_soap_client();
    return $client->createTrackType($trackType);
}

/* 删除售后档案的类型，用于配置系统 */
function   deleteTrackType($trackTypeId) {
    
}

/**
 * 获得attributetype的各个选项的值，方便smarty调用
 *
 * @return array
 */
function getTrackAttributeTypeOptions () { 
    $types = wrap_object_to_array(getTrackAttributeType()->resultList->TrackAttributeType);

    $types_array = array();
    if(is_array($types)&&!empty($types)){
	    foreach ($types as $type) {
	        if ($type->attributeType == 'CHECK' || $type->attributeType == 'CHOICE' ) {
	            $tmp_array = explode(',', $type->attributeValues);
	            $tmp_array = array_combine($tmp_array, $tmp_array);
	            if ($type->trackAttributeTypeId == 'OUTER_PACK' || $type->trackAttributeTypeId == 'INNER_PACK') {
	                $types_array[$type->trackAttributeTypeId] = array('OK' => ' 完好', 'DAMAGED'=>' 破损');
	            } else {
	                $types_array[$type->trackAttributeTypeId] = $tmp_array;
	            }
	        }
	    }
    }
    return $types_array;
}

/**
 * 根据TrackId获得相关属性的数组，此方法按数组组织，方便使用
 *
 * @param string $trackId
 * @return array
 */
function getTrackAttributeArrayByTrackId($trackId) {
    $result = getTrackAttributeByTrackId($trackId);
    $trackAttribute = $result->resultList->TrackAttribute;
    
    ///将数据重组一下，不然无法使用
    if ($result->total > 0 ) {
        $trackAttributeArray = array();
        foreach ($trackAttribute as $attribute) {
            // 两个属性的值要特殊处理成数组，方便smarty使用
            if (in_array($attribute->trackAttributeTypeId, 
                    array('MATERIALS_RECEIVED', 'WARRANTY_TAB') ) ) {
                $attribute->value = explode(',', $attribute->value);
            }
            $trackAttributeArray[$attribute->trackAttributeTypeId] = $attribute;
        }
        return  $trackAttributeArray;
    }
    
    return null;
}

/**
 * 根据售后Id的数组获得相关Track
 *
 * @param array $serviceIds
 * @return array
 */
function getTrackByServiceIdArray($serviceIds) {
    $qp = new stdClass();
    $qp->serviceIds = $serviceIds;
    $qp->offset = 0;
    $qp->limit = 0;
    $rma_track_result = getTrack($qp);
    $rma_tracks = wrap_object_to_array($rma_track_result->resultList->Track);
    return $rma_tracks;
    
}

function isRMATrackNeeded(){
    // return true;
    return false;
}



