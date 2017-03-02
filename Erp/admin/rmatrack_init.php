<?php
/**
 * 该程序为初始化RMA track的类型之用
 *
 */

define('IN_ECS', true);

set_time_limit(1000);
require('includes/init.php');
include_once(ROOT_PATH . "RomeoApi/lib_RMATrack.php");
include_once("config.vars.php");

if ($_GET['v'] == 'init_by_zwsun') {
	die();
	foreach ($rma_track_attribute_type as $type) {
	    $trackAttributeType = new stdClass();
	    $trackAttributeType->trackAttributeTypeId = $type[0];
	    $trackAttributeType->name = $type[1];
	    $trackAttributeType->attributeType = $type[2];
	    $trackAttributeType->attributeValues = $type[3];
	//    var_dump($trackAttributeType);
	    print createTrackAttributeType($trackAttributeType)."<br />"; 
	}
	
	$trackType = new  stdClass();
	$trackType->trackTypeId = 'V1';
	$trackType->name = 'V1';
	print createTrackType($trackType)."<br />"; 
} else if($_GET['v'] == 'init_by_ncchen_20091013') {
	// 添加“返修”初始化信息
	$trackType = new  stdClass();
	$trackType->trackTypeId = 'WARRANTY';
	$trackType->name = '返修';
	print createTrackType($trackType)."<br />"; 
}

