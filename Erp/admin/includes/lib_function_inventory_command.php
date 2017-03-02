<?php

function createInventoryItemVarianceByProductId_lcji($productId, $inventoryItemAcctTypeName, $inventoryItemTypeName, $statusId, $serialNumber, $quantityOnHandVar, $availableToPromiseVar, $physicalInventoryId, $unitCost, $facilityId, $comments, $orderId, $orderGoodsId, $actionUser) {
	require_once ROOT_PATH . 'RomeoApi/lib_inventory.php';
	global $soapclient;
	$actionUser = 'cronjob';
	$containerId = facility_get_default_container_id ( $facilityId );
	$providerId = get_self_provider_id ();
	$keys = array (
			'productId' => 'StringValue',
			'inventoryItemAcctTypeName' => 'StringValue',
			'inventoryItemTypeName' => 'StringValue',
			'statusId' => 'StringValue',
			'serialNumber' => 'StringValue',
			'quantityOnHandVar' => 'NumberValue',
			'availableToPromiseVar' => 'NumberValue',
			'unitCost' => 'NumberValue',
			'facilityId' => 'StringValue',
			'containerId' => 'StringValue',
			'actionUser' => 'StringValue',
			'physicalInventoryId' => 'StringValue',
			'providerId' => 'StringValue',
			'comments' => 'StringValue',
			'orderId' => 'StringValue',
			'orderGoodsId' => 'StringValue' 
	);
	$param = new HashMap ();
	foreach ( $keys as $key => $type ) {
		if (${$key} == null) {
			continue;
		}
		$gv = new GenericValue ();
		$method = 'set' . $type;
		$gv->$method ( ${$key} );
		$param->put ( $key, $gv->getObject () );
	}
	$result = $soapclient->createInventoryItemVarianceByProductId ( array (
			'arg0' => $param->getObject () 
	) );
	$return_hashmap = new HashMap ();
	$return_hashmap->setObject ( $result->return );
	return $return_hashmap;
}

function createPhysicalInventory_lcji($generalComments = '') {
	require_once ROOT_PATH . 'RomeoApi/lib_inventory.php';
	global $soapclient;
	$keys = array('generalComments'=>'StringValue');
	$param = new HashMap();
	foreach ($keys as $key => $type) {
		if(${$key} == null) { continue; }
		$gv = new GenericValue();
		$method = 'set'.$type;
		$gv->$method(${$key});
		$param->put($key, $gv->getObject());
	}
	$result = $soapclient->createPhysicalInventory(array('arg0'=>$param->getObject()));
	$return_hashmap = new HashMap();
	$return_hashmap->setObject($result->return);
	$physicalInventoryId = $return_hashmap->get("physicalInventoryId")->stringValue;
	return $physicalInventoryId;
}

