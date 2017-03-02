<?php
define('IN_ECS', true);

set_time_limit(1000);
require('includes/init.php');
require_once(ROOT_PATH. "RomeoApi/lib_inventory.php");


$sql = "SELECT goods_id, top_cat_id FROM {$ecs->table('goods')} ";
$goods = $db->getAll($sql);
foreach ($goods as $good) {
	$catMap[$good['goods_id']] = $good['top_cat_id'];
}

function show($itemList, $catId) {
  global $catMap;
  $storage = array();
  $return_hashmap = new HashMap();
  $vars = array('unitCost', 'productId', 'productName', 'serialNumber', 'qohTotal', 'styleId', 'goodsId');
  foreach ( $itemList->arrayList->anyType as $item) {
	$return_hashmap->setObject($item);
    foreach ($vars as $var) {
        ${$var} = $return_hashmap->get($var);
    }
	if ($catId == $catMap[$goodsId] || !isset($catId)) {
		print $serialNumber . ",".$productName . ",". $qohTotal . ",". $unitCost."<br />";
	    $total += $qohTotal;
		$storage[$productId]['qohTotal'] += $qohTotal;
	    $storage[$productId]['productName'] = $productName;
		$storage[$productId]['goodsId'] = $goodsId;
		$storage[$productId]['unitCost'] = $unitCost;
		$storage[$productId]['catId'] = $catMap[$goodsId];
	    $storage[$productId]['serialNumber'][] = $serialNumber;   
	} 
//     die();
  }
  print $total."<br>";
//  foreach ($storage as $productId => $storage_item) {
//    print $productId . ",".$storage_item['productName'] . ",". $storage_item['qohTotal'].",";
//    $serialNumber = $storage_item['serialNumber'];
//    foreach ($serialNumber as $sn) {
//      print "sn: $sn ";
//    }
    print "<br/>";
}

if($_REQUEST['status'] == '0') {
	$itemList = getInventoryAvailableByStatus('INV_STTS_AVAILABLE');
	show($itemList, $_REQUEST['cat'] );
} else if ($_REQUEST['status'] == '1') {
	$itemList = getInventoryAvailableByStatus('INV_STTS_USED');
	show($itemList, $_REQUEST['cat']);
} else if ($_REQUEST['status'] == '2') {
	$itemList = getInventoryAvailableByStatus('INV_STTS_DELIVER');
	show($itemList, $_REQUEST['cat']);
} else if ($_REQUEST['status'] == '3') {
	$itemList = getInventoryAvailableByStatus('INV_STTS_DEFECTIVE');
	show($itemList, $_REQUEST['cat']);
} else {
	$itemList = getInventoryAvailableByStatus('INV_STTS_AVAILABLE');
	show($itemList, $_REQUEST['cat']);
}
