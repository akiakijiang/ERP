<?php

define('IN_ECS', true);
require_once('includes/init.php');
require_once('includes/lib_dhl.php');

$order_id = $_REQUEST['order_id'];
$pieces = intval($_REQUEST['pieces']);
$pieces = $pieces > 0 ? $pieces : 1;

$pinming = $_REQUEST['pinming'];
$declaredValue = $_REQUEST['declaredValue'];
$P_D = $_REQUEST['P_D']; // DHL产品（货物类型）：WPX 物品、 DOX 文件
$area = $_REQUEST['area'];
$param = array(
		'pinming' => $pinming, 
		'declaredValue' => $declaredValue, 
		'P_D' => $P_D, 
		'area' => $area
);

$result = dhl_ship_request($order_id, $pieces, $param);

if ($result === false) {
    print "该订单无法自动提交到dhl，请使用原始的dhl运单纸打印";
    exit();
}

list($awb, $html) = $result;

print $html;
