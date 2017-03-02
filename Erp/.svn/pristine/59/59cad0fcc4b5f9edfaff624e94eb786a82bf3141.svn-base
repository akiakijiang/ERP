<?php
include_once('cls_HashMap.php');
include_once('cls_GenericValue.php');
include_once('lib_soap.php');

global $soapclient, $SupplierRebateSoapclient;

$romeo_http_auth_array['trace'] = true;
if(defined('ROMEO_HTTP_USER') && ROMEO_HTTP_USER) $romeo_http_auth_array['login'] = ROMEO_HTTP_USER;
if (defined('ROMEO_HTTP_PASS') && ROMEO_HTTP_PASS) $romeo_http_auth_array['password'] = ROMEO_HTTP_PASS;

// 订单 order_id 大于该值使用新的出库逻辑
define('ORDER_ID_NEW_DELIVER', 487100);

//$soapclient = new SoapClient(ROMEO_WEBSERVICE_URL, $romeo_http_auth_array);
$soapclient = new SoapClient(ROMEO_WEBSERVICE_URL."InventoryService?wsdl", $romeo_http_auth_array);
$SupplierRebateSoapclient = new SoapClient(ROMEO_WEBSERVICE_URL."SplRebService?wsdl", $romeo_http_auth_array);

/**
 * 返利公共调用函数
 * @param string  方法名
 * @param mixed method所需的参数
 * ...
 * @return mixed 返回的结果
 */
function __SupplierRebateCall() {
    global $SupplierRebateSoapclient;
    $args = func_get_args();
    $method = $args[0];
    unset($args[0]);
    $method_args = array();
    $i = 0;
    foreach ($args as $arg) {
        $method_args['arg'.$i] = $arg;
        $i++;
    }
    return $SupplierRebateSoapclient->$method($method_args)->return;
}

/**
 * 格式化一下返利数据
 *
 */
function formatSupplierRebate($SupplierRebate) {
    if(!is_object($SupplierRebate)) return false;

    $supplierRebateTypeId_enum = array('SERIAL_NUMBER' => '单个机器返利', 'ORDER' => '针对单个订单', 'PRODUCT' => '针对某个供应商的某种商品', 'SUPPLIER' => '针对供应商');
    $supplierRebateStatusId_enum = array('UNPAID' => '返利未确认', 'CONFIRMED' => '返利已经确认', 'DISCARD'=> '采购废弃', 'CANCEL' => '供应商取消');
    $supplierRebateModeId_enum = array('INVOICEPAID' => '返现金不扣发票', 'DEDUCTED' => '抵扣货款', 'GIFTED' => '返实物');

    $SupplierRebate->supplierRebateTypeId_description = $supplierRebateTypeId_enum[$SupplierRebate->supplierRebateTypeId];
    $SupplierRebate->supplierRebateStatusId_description = $supplierRebateStatusId_enum[$SupplierRebate->supplierRebateStatusId];
    $SupplierRebate->supplierRebateModeId_description = $supplierRebateModeId_enum[$SupplierRebate->supplierRebateModeId];
    $SupplierRebate->createdStamp_description = date('Y-m-d H:i:s', strtotime($SupplierRebate->createdStamp));
    $SupplierRebate->description_summary = cutstr($SupplierRebate->description, 20);
    $SupplierRebate->expectedGiftAmount = intval($SupplierRebate->expectedGiftAmount);
    $SupplierRebate->confirmedGiftAmount = intval($SupplierRebate->confirmedGiftAmount);
    $SupplierRebate->receivedGiftAmount = intval($SupplierRebate->receivedGiftAmount);
    $SupplierRebate->expectedAmount = number_format($SupplierRebate->expectedAmount, 2, '.', '');
    $SupplierRebate->confirmedAmount = number_format($SupplierRebate->confirmedAmount, 2, '.', '');
    $SupplierRebate->receivedAmount = number_format($SupplierRebate->receivedAmount, 2, '.', '');

    return $SupplierRebate;

}


/**
 * 更新供价
 *
 * @param arrary $goods_and_style 商品的goods_id , style_id
 * @param string $orderId 订单id
 * @param string $acctType 财务科目 
 * @param string $unitCost 商品的供价
 */
function updateInventoryItemValueByOrderProductNew($orderGoodsId, $unitCost) {
    global $soapclient, $db;
    $sql = "select og.order_id,pm.product_id from ecshop.ecs_order_goods og
				inner join romeo.product_mapping pm 
					on og.goods_id = pm.ecs_goods_id and og.style_id = pm.ecs_style_id
				where og.rec_id = '{$orderGoodsId}' ";
	$row = $db->getRow($sql);
	$productId = $row['product_id'];
	$orderId = $row['order_id'];
    $userName = $_SESSION['admin_name'];
    $keys = array('productId'=>'StringValue', 'unitCost'=>'StringValue', 'orderId'=>'StringValue', 'userName'=>'StringValue');
    $param = new HashMap();
    foreach ($keys as $key => $type) {
        if(${$key} == null) { continue; }
        $gv = new GenericValue();
        $method = 'set'.$type;
        $gv->$method(${$key});
        $param->put($key, $gv->getObject());
    }
    $message = "\n_____________\n input : \n " . print_r(func_get_args(), true)
             . "\n_____________\n param :\n " . print_r($param->hasharray_mapping,true);
    $return_result = false;
    try {
        $result = $soapclient->updateInventoryItemValueByOrderProduct(array('arg0'=>$param->getObject()));
        $return_hashmap = new HashMap();
        $return_hashmap->setObject($result->return);
        $status = $return_hashmap->get("status")->stringValue;
        $script = $db->escape_string($_SERVER['PHP_SELF']);
        $message = $db->escape_string(" result : \n " . print_r($result, true) . $message );
        if($status == 'FAIL') {
        } elseif($status == 'OK') {
        	$return_result = true;
        }

    } catch (Exception $e) {
        $script = $db->escape_string($_SERVER['PHP_SELF']);
        $message = $db->escape_string(" Exception :\n " .$e->getMessage(). $message );
    }
    return $return_result;
}

/**
 * 更新供价
 *
 * @param arrary $goods_and_style 商品的goods_id , style_id
 * @param string $orderId 订单id
 * @param string $acctType 财务科目 
 * @param string $unitCost 商品的供价
 */
function cancelOrderInventoryReservation($order_id) {
    global $soapclient;
    try {
        $result = $soapclient->cancelOrderInventoryReservation(array('orderId'=>$order_id)); 
    } catch (Exception $e) {
        
    }
    return $result;
}

function updateInventoryItemValueByOrderProduct($goods_and_style, $orderId, $acctType, $unitCost) {
    global $soapclient, $db;
    $productId = getProductId($goods_and_style['goods_id'], $goods_and_style['style_id'], $goods_and_style['extra1']);
    $userName = $_SESSION['admin_name'];
    $keys = array('productId'=>'StringValue', 'unitCost'=>'StringValue', 'orderId'=>'StringValue', 'acctType'=>'StringValue', 'userName'=>'StringValue');
    $param = new HashMap();
    foreach ($keys as $key => $type) {
        if(${$key} == null) { continue; }
        $gv = new GenericValue();
        $method = 'set'.$type;
        $gv->$method(${$key});
        $param->put($key, $gv->getObject());
    }

    $message = "\n_____________\n input : \n " . print_r(func_get_args(), true)
             . "\n_____________\n param :\n " . print_r($param->hasharray_mapping,true);
    $return_result = false;
    try {
        $result = $soapclient->updateInventoryItemValueByOrderProduct(array('arg0'=>$param->getObject()));
        $return_hashmap = new HashMap();
        $return_hashmap->setObject($result->return);
        $status = $return_hashmap->get("status")->stringValue;
        $script = $db->escape_string($_SERVER['PHP_SELF']);
        $message = $db->escape_string(" result : \n " . print_r($result, true) . $message );
        if($status == 'FAIL') {
        } elseif($status == 'OK') {
        	$return_result = true;
        }

    } catch (Exception $e) {
        $script = $db->escape_string($_SERVER['PHP_SELF']);
        $message = $db->escape_string(" Exception :\n " .$e->getMessage(). $message );
    }
    return $return_result;
}
/**
 * 更新供应商
 *
 * @param string $orderId 订单id
 * @param string $update_provider_id 供应商id
 */
function updateProviderIdByOrder($orderId, $update_provider_id) {
    global $soapclient;
    try {
        $result = $soapclient->updateProviderIdByOrder(array('orderId'=>$orderId,'providerId'=>$update_provider_id));
        $return_hashmap = new HashMap();
	    $return_hashmap->setObject($result->return);
	    $status = $return_hashmap->get("status")->stringValue;
	    return $status==='OK';
    } catch (Exception $e) {
        return false;
    }
}

/**
 * 创建盘点记录
 *
 * @param string $productId
 * @param string $inventoryItemAcctTypeName
 * @param unknown_type $inventoryItemTypeName
 * @param unknown_type $statusId
 * @param unknown_type $serialNumber
 * @param int $quantityOnHandVar
 * @param int $availableToPromiseVar
 * @return unknown
 */
function createInventoryItemVarianceByProductId($productId, $inventoryItemAcctTypeName, 
    $inventoryItemTypeName, $statusId, $serialNumber, $quantityOnHandVar, 
    $availableToPromiseVar, $physicalInventoryId, $unitCost, $facilityId,$comments,$orderId,$orderGoodsId) {
    
    global $soapclient;
    $actionUser = (!$_SESSION['admin_name']) ? 'system' : $_SESSION['admin_name'];
    $containerId = facility_get_default_container_id($facilityId);
    $providerId = get_self_provider_id();
    $keys = array('productId'					=>'StringValue', 
                  'inventoryItemAcctTypeName'	=>'StringValue', 
                  'inventoryItemTypeName' 		=>'StringValue', 
                  'statusId'					=>'StringValue', 
                  'serialNumber'				=>'StringValue', 
                  'quantityOnHandVar'			=>'NumberValue', 
                  'availableToPromiseVar'		=>'NumberValue', 
                  'unitCost'					=>'NumberValue', 
                  'facilityId'					=>'StringValue', 
                  'containerId'					=>'StringValue', 
                  'actionUser'					=>'StringValue', 
                  'physicalInventoryId' 		=>'StringValue',
    			  'providerId' 					=>'StringValue',
    			  'comments'					=>'StringValue',
    			  'orderId' 					=>'StringValue',
    			  'orderGoodsId' 				=>'StringValue');
    $param = new HashMap();
    foreach ($keys as $key => $type) {
        if(${$key} == null) { continue; }
        $gv = new GenericValue();
        $method = 'set'.$type;
        $gv->$method(${$key});
        $param->put($key, $gv->getObject());
    }
    $result = $soapclient->createInventoryItemVarianceByProductId(array('arg0'=>$param->getObject()));
    $return_hashmap = new HashMap();
    $return_hashmap->setObject($result->return);
    return $return_hashmap;
}

 // 相比上面的函数 需要带有batch_sn的信息
function createInventoryItemVarianceByProductIdBatchSn($productId, $inventoryItemAcctTypeName, 
    $inventoryItemTypeName, $statusId, $serialNumber, $quantityOnHandVar, 
    $availableToPromiseVar, $physicalInventoryId, $unitCost, $facilityId,$comments,$orderId,$orderGoodsId,$batch_sn) {
    
    if(!isset($batch_sn) || strlen($batch_sn) < 1 ){
        $batch_sn = "";
    }
    global $soapclient;
    $actionUser = (!$_SESSION['admin_name']) ? 'system' : $_SESSION['admin_name'];
    $containerId = facility_get_default_container_id($facilityId);
    $providerId = get_self_provider_id();
    $keys = array('productId'                   =>'StringValue', 
                  'inventoryItemAcctTypeName'   =>'StringValue', 
                  'inventoryItemTypeName'       =>'StringValue', 
                  'statusId'                    =>'StringValue', 
                  'serialNumber'                =>'StringValue', 
                  'quantityOnHandVar'           =>'NumberValue', 
                  'availableToPromiseVar'       =>'NumberValue', 
                  'unitCost'                    =>'NumberValue', 
                  'facilityId'                  =>'StringValue', 
                  'containerId'                 =>'StringValue', 
                  'actionUser'                  =>'StringValue', 
                  'physicalInventoryId'         =>'StringValue',
                  'providerId'                  =>'StringValue',
                  'comments'                    =>'StringValue',
                  'orderId'                     =>'StringValue',
                  'orderGoodsId'                =>'StringValue',
                  'batch_sn'                    =>'StringValue');
    $param = new HashMap();
    foreach ($keys as $key => $type) {
        if(${$key} == null) { continue; }
        $gv = new GenericValue();
        $method = 'set'.$type;
        $gv->$method(${$key});
        $param->put($key, $gv->getObject());
    }
    $result = $soapclient->createInventoryItemVarianceByProductIdHasBatchSn(array('arg0'=>$param->getObject()));
    $return_hashmap = new HashMap();
    $return_hashmap->setObject($result->return);
    return $return_hashmap;
}

/*
 * 修改发货单状态
 * */
function updateBatchShipmentStatus($shipmentId){
	global $soapclient;
	try{
		$actionUser = $_SESSION['admin_name'];
		$response = $soapclient->updateBatchShipmentStatus(array('shipmentId'=>$shipmentId,'actionUser'=>$actionUser));
	}catch (Exception $e) {
	  echo("{$method} soap call exception:".$e->getMessage());
	}
}

/**
 * 创建物理盘点记录
 * @param string $generalComments
 * @return string $physicalInventoryId
 */
function createPhysicalInventory($generalComments = '') {
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

/**
 * 创建物理snapshot
 *
 * @return string $status
 */
function createInventoryItemSnapshotBatch() {
    global $soapclient;
    $param = new HashMap();
    $result = $soapclient->createInventoryItemSnapshotBatch(array('arg0'=>$param->getObject()));
    $return_hashmap = new HashMap();
    $return_hashmap->setObject($result->return);
    $physicalInventoryId = $return_hashmap->get("status")->stringValue;
    return $physicalInventoryId;
}
/**
 * 获取自己仓库的provider_id
 *
 * @return 自己仓库的provider_id
 */
function get_self_provider_id()
{
	global $db;
    $provider_id = '432';
    $sql_s = "select provider_status from ecs_provider where provider_id = '432' limit 1";
    if ($db->getOne($sql_s) != 1) {
    	$sql_s = "select provider_id from ecs_provider where provider_status = 1 and provider_name = '自己库存' limit 1";
        $provider_id = $db->getOne($sql_s);
    }
    return $provider_id;
}
/**
 * 获得新库存系统的productid
 *
 * @param int $goods_id
 * @param int $style_id
 * @param string $extra1
 * @return string
 */
function getProductId($goodsId, $styleId){
    $goodsId = intval($goodsId);
    $styleId = intval($styleId);
    $productIds = getProductIds(array(array('goods_id' => $goodsId, 'style_id' => $styleId)));
    return ( isset($productIds[$goodsId.'_'.$styleId]) ? $productIds[$goodsId.'_'.$styleId] : null ) ;
}

/**
 * 获得新库存系统的productIdList
 *
 * @param int $goods_id
 * @return string
 */
function getProductIdListByGoodsId($goodsId){
    global $soapclient;
    $goodsId = intval($goodsId);
    $productIdList = $soapclient->getProductIdListByGoodsId(
        array('goodsId' => $goodsId))->return;
    return $productIdList;
}

/**
 * 通过goodsId和styleId获得productId，
 * 该函数使用了缓存以保证最快的执行速度, 如果查询的商品不在缓存中，则通过service查询，并更新缓存
 * 
 * @param array $goodsIdStyleIdList 
 *   二维数组，每一行包括商品的goods_id和style_id: array('goods_id' => {goodsId}, 'style_id' => {styleId})
 * @param boolean 
 *   是否刷新缓存, 不是特殊情况请不要设置为true
 *
 * @return array  
 *   返回结果为商品的goodsId加styleId与productId的对应关系,
 *   二维数组，每一行为这样的格式  array('{goodsId}_{styleId}' => productID),
 */
function getProductIds($goodsIdStyleIdList, $refresh = false) {
    global $soapclient, $db, $ecs;
    static $productMapping;

    if (empty($goodsIdStyleIdList) || !is_array($goodsIdStyleIdList)) {
        return false;
    }

    // 缓存策略：不过期缓存
    $cache_policy = array('life_time' => null);
    $cache_id     = "inventory:productMapping";  
    include_once('lib_cache.php');
    $cache = RomeoApi_Cache::instance();
    if ($refresh === true) { 
    	$cache->delete($cache_id);
    	$productMapping = array(); 
    } else {
	    // 尝试从文件缓存取得对应关系表，并保存到内存
        if (!isset($productMapping)) {
            $productMapping = $cache->get($cache_id, $cache_policy);
        }
    }
     
    $result = array();   // 返回结果
    $chache_hit = true;  // 缓存命中
    foreach ($goodsIdStyleIdList as $key => $item) {
    	// 格式化为int型，如将null转为0
    	$goodsIdStyleIdList[$key]['goods_id'] = intval($item['goods_id']);
    	$goodsIdStyleIdList[$key]['style_id'] = intval($item['style_id']);

        // 判断在缓存中是否有
        $gsId = $goodsIdStyleIdList[$key]['goods_id'] .'_'. $goodsIdStyleIdList[$key]['style_id'];
        if (isset($productMapping[$gsId])) {
            $result[$gsId] = $productMapping[$gsId]; 
        } else {
            $chache_hit = false;
            $gsIdList[] = $gsId;
            $goodsIds[] = $goodsIdStyleIdList[$key]['goods_id'];  // 用于查询商品        	
        }
    }
    
    // 缓存中没有命中
    if (!$chache_hit) {
	    // 要确认ERP系统中存在该商品, 因为ROMEO的getProductIdByGoodsIdStyleId方法会自动建立Mapping
//	    $sql = "
//	        SELECT 
//	            IF(s.color, CONCAT_WS(' ', g.goods_name, IF(gs.goods_color = '', s.color, gs.goods_color)), g.goods_name) AS goods_name, 
//                g.goods_id, IF(s.style_id, s.style_id, 0) AS style_id
//	        FROM 
//	            {$ecs->table('goods')} AS g
//	            LEFT JOIN {$ecs->table('goods_style')} gs ON gs.goods_id = g.goods_id
//	            LEFT JOIN {$ecs->table('style')} s ON s.style_id = gs.style_id
//	        WHERE g.goods_id " . db_create_in($goodsIds);
	    $sql = "
	        SELECT 
	             g.goods_id, g.goods_name, ifnull(gs.goods_color, '') as goods_color, ifnull(s.color, '') as color, ifnull(s.style_id, '0')  as style_id
	        FROM 
	            {$ecs->table('goods')} AS g
	            LEFT JOIN {$ecs->table('goods_style')} gs ON gs.goods_id = g.goods_id
	            LEFT JOIN {$ecs->table('style')} s ON s.style_id = gs.style_id
	        WHERE g.goods_id " . db_create_in($goodsIds);
	    $goodsList = $db->getAll($sql);
	    if (!$goodsList) { return array(); }
	    // 取得ROMEO中的产品对应关系
	    foreach ($goodsList as $goods) {
            if ( isset($productMapping["{$goods['goods_id']}_{$goods['style_id']}"]) ||
                 !in_array($goods['goods_id'] .'_'. $goods['style_id'], $gsIdList) ) {

                continue;
            }

            // 取得productId
	        $param = new HashMap();
	        $v[0] = new GenericValue();
	        $v[1] = new GenericValue();
	        $v[2] = new GenericValue();
	        
	        // 转化成prodcut_name
            $goods_name =  $goods['goods_name'];
            
            if(!empty($goods['style_id'])){
                if(!empty($goods['goods_color'])){
                	$goods_name = $goods_name . ' ' . $goods['goods_color'];
                }else if(!empty($goods['color'])){
                	$goods_name = $goods_name . ' ' . $goods['color'];
                }
            }
	        
	        $param->put("goodsId", $v[0]->setStringValue($goods['goods_id'])->getObject());
	        $param->put("styleId", $v[1]->setStringValue($goods['style_id'])->getObject());
	        $param->put("productName", $v[2]->setStringValue($goods_name)->getObject());
	        
	        $response = $soapclient->getProductIdByGoodsIdStyleId(array('arg0'=>$param->getObject()));
	        $return = new HashMap();
	        $return->setObject($response->return);
	        $productId = $return->get("productId")->stringValue;
	        $productMapping["{$goods['goods_id']}_{$goods['style_id']}"] = $productId;
	        
	    }
	    
	    // 写入缓存
	    $cache->set($cache_id, $productMapping, $cache_policy);

        foreach ($gsIdList as $gsId) {  
            if (isset($productMapping[$gsId])) {
                $result[$gsId] = $productMapping[$gsId];
            }
        }
    }
    
    return $result;
}


/**
 * 根据新库存系统的productid获得goods_id style_id
 *
 * @param string $productId
 * @return array($goods_id, $style_id)
 */
function getGoodsIdStyleIdByProductId($productId) {
    global $soapclient, $db, $ecs;
    static $productMapping = array();
    $productId = (string) ($productId);
    if(is_array($productMapping[$productId])) return $productMapping[$productId];

    $keys = array('productId'=>'StringValue');
    $param = new HashMap();
    foreach ($keys as $key => $type) {
        if(${$key} == null) { continue; }
        $gv = new GenericValue();
        $method = 'set'.$type;
        $gv->$method(${$key});
        $param->put($key, $gv->getObject());
    }

    $result = $soapclient->getGoodsIdStyleIdByProductId(array('arg0'=>$param->getObject()));

    $return_hashmap = new HashMap();
    $return_hashmap->setObject($result->return);

    if($return_hashmap->get("status")->stringValue == 'OK') {
        $goodsId = $return_hashmap->get("goodsId")->stringValue;
        $styleId = $return_hashmap->get("styleId")->stringValue;
        $sql = "SELECT goods_name FROM {$ecs->table('goods')} WHERE goods_id = '$goodsId' ";
        $goods_name = $db->getOne($sql);

        if($styleId) {
            $sql = "SELECT color FROM {$ecs->table('style')} WHERE style_id = '$styleId' ";
            $goods_name .= (" ".$db->getOne($sql));
        }
        $productMapping[$productId] = array('goods_id' =>$goodsId, 'style_id' => $styleId, 'goods_name' => $goods_name);
        return $productMapping[$productId];
    }
    // 添加错误日志
    require_once(ROOT_PATH.'includes/debug/lib_log.php');
    QLog::log(print_r(debug_backtrace(), true));
    return false;
}

/**
 * 获得新库存系统的库存
 *
 * @param string $statusId
 * @param string $facilityId 
 * @param string $goodsIdStyleIdList
 * @return array
 */
function getInventoryAvailableByStatus($statusId, $facilityId = null, $goodsIdStyleIdList = null) {
    global $soapclient;
    
    $facilityIdList = null;
    $productIdList = null;
    
    $facilityId = trim($facilityId);
    if ($facilityId) {
        $facilityIdList = explode(',', $facilityId);
    }

    //如果提供了goodsId StyleId
    if ($goodsIdStyleIdList != null) {
    	$productIdList = getProductIds($goodsIdStyleIdList);
    }
    
    // 要传递的参数
    $webparam = array(
        'statusId' => $statusId,
        'facilityIdList' => $facilityIdList,
        'productIdList' => $productIdList,
    );
    
    $result = $soapclient->getInventoryAvailableByStatus($webparam);
    $return_hashmap = new HashMap();
    $return_hashmap->setObject($result->return);
    $itemList = $return_hashmap->get("itemList");

    return $itemList;
}

/**
 * 返回新系统的库存
 * $goodsIdStyleIdList结构如下：
 * array(array('goods_id'=>$goods_id, 'style_id'=>$style_id) ... )
 *
 * @param string $stts              要查询库的名字 默认为正式库
 * @param string $facilityId        要限制的仓库名字，默认不限制仓库
 * @param array $goodsIdStyleIdList 要限制的产品 goods_id style_id 的数组
 * @return array                    
 * )
 */
function getStorage($stts = 'INV_STTS_AVAILABLE',  $facilityId = null, $goodsIdStyleIdList = null) {
    $result = array();
    $inventory = getInventoryAvailableByStatus($stts, $facilityId, $goodsIdStyleIdList);
    
    if (!isset($inventory->arrayList->anyType)) {
        return null;
    }

    $return_hashmap = new HashMap();
    // 要赋值的变量名
    $vars = array(
        'unitCost', 'productId', 'productName', 
        'serialNumber', 'qohTotal', 'styleId', 'goodsId'
    );    
    // 包装返回数据
    if (is_object($inventory->arrayList->anyType)) {
        $inventory->arrayList->anyType = array($inventory->arrayList->anyType);
    }
    foreach ($inventory->arrayList->anyType as $item) {            
        $return_hashmap->setObject($item);
        foreach ($vars as $var) {
            ${$var} = $return_hashmap->get($var);
        }
        
        $result["{$goodsId}_{$styleId}"]["qohTotal"] += $qohTotal;
    }
    
    return $result;
}

/**
 * 取得库存总表， 可以通过配置来设置库存总表的策略。
 * 
 * @param string @statusId
 * 
 * @return array  返回按仓库分组的产品库存
 */
function getInventorySummaryList($statusId = 'INV_STTS_AVAILABLE'){
	global $soapclient, $_CFG;
	
	// 使用库存总表
	$inventory_summary_list = array();
	if ($_CFG['inventory_summary_list_enabled']) {
	    $webparam = array(
	        'productId' => NULL,
	        'statusId' => $statusId,
	        'facilityId' => NULL,
	        'containerId' => NULL,
	        'goodsPartyId' => NULL,
	    );
	    $response = $soapclient->getInventorySummaryList($webparam);
	    if ($response && isset($response->return->InventorySummary)) {
	    	// 包装返回数据
	        if (is_object($response->return->InventorySummary)) {
                $response->return->InventorySummary = array($response->return->InventorySummary);
	        }
	        foreach ($response->return->InventorySummary as $item) {
                $inventory_summary_list[$item->facilityId][$item->goodsId .'_'. $item->styleId]['stockQuantity'] = $item->stockQuantity;    
	        }
	    }
	} else {
		$hashmap = new HashMap();
		foreach (array_keys(facility_list()) as $facilityId) {
		    $inventory = getInventoryAvailableByStatus($statusId, $facilityId);
            if (isset($inventory->arrayList->anyType)) {
                // 包装返回数据
                if (is_object($inventory->arrayList->anyType)) {
                    $inventory->arrayList->anyType = array($inventory->arrayList->anyType);
                }
                foreach ($inventory->arrayList->anyType as $item) {            
                    $hashmap->setObject($item);
                    $goodsId  = $hashmap->get('goodsId');
                    $styleId  = $hashmap->get('styleId');
                    $qohTotal = $hashmap->get('qohTotal');
                    if ($qohTotal > 0) {
                        $inventory_summary_list[$facilityId][$goodsId .'_'. $styleId]['stockQuantity'] += $qohTotal;
                    }
                }
            }
		}
	}

	return $inventory_summary_list;
}

/**
 * 获得新库存系统的历史库存
 *
 * @param int $goods_id
 * @param int $style_id
 * @param string $extra1
 * @return string
 */
function restoreHistoricalInventory($datetime){
    global $soapclient;
    $keys = array('datetime'=>'StringValue');
    $param = new HashMap();
    foreach ($keys as $key => $type) {
        if(${$key} == null) { continue; }
        $gv = new GenericValue();
        $method = 'set'.$type;
        $gv->$method(${$key});
        $param->put($key, $gv->getObject());
    }
    $result = $soapclient->restoreHistoricalInventory(array('arg0'=>$param->getObject()));
    //  pp($result);
    $return_hashmap = new HashMap();
    $return_hashmap->setObject($result->return);
    $itemList = $return_hashmap->get("itemList");
    $storage = array();
    $vars = array('unitCost', 'productId', 'acctTypeId', 'productName', 'serialNumber', 'qohTotal', 'statusId', 'invItemTypeId', 'styleId', 'goodsId');
    foreach ( $itemList->arrayList->anyType as $item) {

        $return_hashmap->setObject($item);
        foreach ($vars as $var) {
            ${$var} = $return_hashmap->get($var);
        }
        $storage[$productId]['qohTotal'] += $qohTotal;
        $total += $qohTotal;
        $storage[$productId]['productName'] = $productName;
        $storage[$productId]['serialNumber'][] = $serialNumber;

        //     die();
    }
    print $total."<br>";
    foreach ($storage as $productId => $storage_item) {
        print $productId . " ////// ".$storage_item['productName'] . " ". $storage_item['qohTotal'] ."<br />";
        $serialNumber = $storage_item['serialNumber'];
        foreach ($serialNumber as $sn) {
            print "sn: $sn ";
        }
        print "<br/>";
    }
    return $itemList;
}



/**
 * 获得产品是否需要串号
 *
 * @param int $goods_id
 * @return string
 */
function getInventoryItemType($goods_id) {
    global $db;
    static $_inventory_item_types = array();
                
    // 需要串号控制的商品
    $serialized_goods_config = array(
        28586, 28587, 31166, 33518, 33955,        // dvd，dvd使用串号控制
        35918,                                    // 奥普-风机
    );
    
    // 需要串号控制的商品分类
    $serialized_cat_config = array(
        DIS_MOB_CAT_ID,                           // 乐其电教分类
        DIS_EDU_CAT_ID,                           // 乐其手机分类
        1502,2256,2262,                           // 蓝光DVD， OPPO
        2437,					                  // 乐贝蓝光	
        2438,					                  // 香港平世	
        2247,2248,2275,2258,2274,2283,            // 怀轩
        // 2285,                                     // 多美滋
        2287,                                     // 奥普
        2306,2307,2305,2304,2311,2312,2313,2310,2308,2309,2319,2320,2317,2316,2315,2314,2300,2302,2303,2301,2332,      // 孕之彩
        9321    //测试组织的串号控制商品
    );
    
    // 需要串号控制的顶级分类
    $serialized_topcat_config = array(
        1, 1458, 414,                             // 手机 电子教育 笔记本 
        //2291,                                     // Dragonfly
    );

    $goods_id = intval($goods_id);
    if (!(isset($_inventory_item_types[$goods_id]) && $_inventory_item_types[$goods_id])) {
        if (in_array($goods_id, $serialized_goods_config)) {
            $type = 'SERIALIZED';
        } else {
            $sql = "SELECT top_cat_id, cat_id,goods_party_id FROM ecs_goods WHERE goods_id = '$goods_id' ";
            $cat = $db->getRow($sql);
            $top_cat_id = $cat['top_cat_id']; //对于商品而言，top_cat_id均为0.无效列
            $cat_id = $cat['cat_id'];
            $goods_party_id = $cat['goods_party_id'];
            if (in_array($top_cat_id, $serialized_topcat_config) || in_array($cat_id, $serialized_cat_config)) {
                $type = 'SERIALIZED';
//            } else if($goods_party_id=='65668' && !in_array($cat_id,array(10835,10837,10834))){ //ASC业务 耗材与赠品之外的其他商品均为串号(!!!!耗材多一个分类要改一次代码)
//            	$type = 'SERIALIZED';
            }else{
                $type = 'NON-SERIALIZED';
            }
        }
        $_inventory_item_types[$goods_id] = $type;
    }
    
    return $_inventory_item_types[$goods_id];
    
}

/**
 * 移库
 *
 * @param string $inventoryTransactionTypeId
 * @param array $goods_and_style
 * @param int $amount
 * @param string $serialNo
 * @param string $acctType
 * @param string $fromOrderId
 * @param string $toOrderId
 * @param string $fromStatusId
 * @param string $toStatusId
 * @param string $userName
 * @return object
 */
function createTransferInventoryTransaction($inventoryTransactionTypeId, $goods_and_style, 
                                            $amount, $serialNo, $acctType, $fromOrderId, $toOrderId, 
                                            $fromStatusId, $toStatusId, $orderGoodsId,
                                            $fromFacilityId = null, $toFacilityId = null ) {
    global $soapclient, $db;
    require_once(ROOT_PATH.'includes/debug/lib_log.php');
    QLog::log("createTransferInventoryTransaction getProductId begin");
    $productId = getProductId($goods_and_style['goods_id'], $goods_and_style['style_id'], $goods_and_style['extra1']);
    QLog::log("createTransferInventoryTransaction getProductId end");
    $inventoryItemType = getInventoryItemType($goods_and_style['goods_id']);
    QLog::log("createTransferInventoryTransaction getInventoryItemType end");
    $userName = $_SESSION['admin_name'];
    if (empty($userName)) {
    	$userName = 'system';
    }
    $serialNo = trim($serialNo);
    if (!$orderGoodsId) {$orderGoodsId = '-1'; } //如果orderGoodsId为空，要写上，防止出入库失败
    
    // 如果是移向 INV_STTS_DELIVER 直接改成出库
    if ($toStatusId == 'INV_STTS_DELIVER') {
   	    $result = createDeliverInventoryTransaction($inventoryTransactionTypeId, $goods_and_style, 
                                       $amount, $serialNo, $acctType, $fromOrderId, $toOrderId, 
                                       $fromStatusId, '', $orderGoodsId,
                                       $fromFacilityId);
        if ($result != true){
        	QLog::log("createTransferInventoryTransaction failed.");  
        	QLog::log("inventoryTransactionTypeId: " . $inventoryTransactionTypeId); 
        	QLog::log("goods_id: " . $goods_and_style['goods_id']); 
        	QLog::log("style_id: " . $goods_and_style['style_id']);
        	QLog::log("amount: " . $amount);  
        	QLog::log("serialNo: " . $serialNo);  
        	QLog::log("acctType: " . $acctType); 
        	QLog::log("fromOrderId: " . $fromOrderId);
        	QLog::log("toOrderId: " . $toOrderId);
        	QLog::log("fromStatusId: " . $fromStatusId);
        	QLog::log("toStatusId: " . $toStatusId);
        	QLog::log("orderGoodsId: " . $orderGoodsId);
        	QLog::log("fromFacilityId: " . $fromFacilityId);
        	QLog::log("toFacilityId: " . $toFacilityId);
        }
        return $result;
    }
    
    // 如果是从 INV_STTS_DELIVER 移向正式库的，改成先到INV_STTS_RETURNED，然后到正式库
    // 如果直接入正式库，是没有办法找到原始的记录，除非修改romeo 端的逻辑
    // 由于这部分的数据相对较少(千分之一)，暂时维持两步的逻辑了
    if ($fromStatusId == 'INV_STTS_DELIVER') {
        //FIXME: 在此以前的订单，已经有部分商品在deliver里面了，不能使用新的逻辑
        if (intval($fromOrderId) > intval(ORDER_ID_NEW_DELIVER)) {
         $sql = "select unit_cost,provider_id 
         from romeo.inventory_item ii inner join romeo.inventory_item_detail iid ON iid.inventory_item_id = ii.inventory_item_id 
         where order_goods_id = '{$orderGoodsId}' and serial_number = '{$serialNo}' limit 1 ";
         QLog::log("price_provider: " . $sql);
         $price_provider = $db->getRow($sql);
         $unitPrice = $price_provider['unit_price'];
         $providerId = $price_provider['provider_id'];
         QLog::log("unitPrice: " . $unitPrice." providerId:".$providerId);
         $result = 
            createAcceptInventoryTransaction($inventoryTransactionTypeId, $goods_and_style, 
                                          $amount, $serialNo, $acctType, 
                                          $fromOrderId, 
                                          '', 'INV_STTS_RETURNED', 
                                          $unitPrice, $orderGoodsId, 
                                          $fromFacilityId, $providerId)
         && createTransferInventoryTransaction($inventoryTransactionTypeId, $goods_and_style, 
                                          $amount, $serialNo, $acctType, 
                                          $fromOrderId, $toOrderId, 
                                          'INV_STTS_RETURNED', $toStatusId, 
                                          $orderGoodsId,
                                          $fromFacilityId, $toFacilityId);
        return $result;
        }
    }
    
    // 目前facility和container一一对应，故可以直接使用mapping
    $fromContainerId = facility_get_default_container_id($fromFacilityId); 
    $toContainerId = facility_get_default_container_id($toFacilityId); 

    $keys = array('inventoryTransactionTypeId'=>'StringValue', 
                  'productId'=>'StringValue', 
                  'amount'=>'NumberValue', 
                  'inventoryItemType' => 'StringValue', 
                  'serialNo'=>'StringValue', 
                  'fromOrderId'=>'StringValue', 
                  'toOrderId' => 'StringValue', 
                  'fromStatusId'=>'StringValue', 
                  'toStatusId'=>'StringValue', 
                  'userName'=>'StringValue', 
                  'acctType'=>'StringValue', 
                  'orderGoodsId'=>'StringValue',
                  'fromFacilityId' => 'StringValue',                  
                  'fromContainerId' => 'StringValue',
                  'toFacilityId' => 'StringValue',
                  'toContainerId' => 'StringValue',
                  );
    $param = new HashMap();
    foreach ($keys as $key => $type) {
        if(${$key} == null) { continue; }
        $gv = new GenericValue();
        $method = 'set'.$type;
        $gv->$method(${$key});
        $param->put($key, $gv->getObject());
    }

    $input = func_get_args();
    return romeo_execute("createTransferInventoryTransaction", $input, $param);
}

/**
 * 入库
 *
 * @param unknown_type $inventoryTransactionTypeId
 * @param unknown_type $goods_and_style
 * @param unknown_type $amount
 * @param unknown_type $serialNo
 * @param unknown_type $acctType
 * @param unknown_type $orderId
 * @param unknown_type $fromStatusId
 * @param unknown_type $toStatusId
 * @param unknown_type $unitCost
 * @param unknown_type $orderGoodsId
 */
function createAcceptInventoryTransaction($inventoryTransactionTypeId, $goods_and_style, 
                                          $amount, $serialNo, $acctType, $orderId, $fromStatusId, 
                                          $toStatusId, $unitCost = null, $orderGoodsId = 0, 
                                          $toFacilityId = null,$providerId) {
    global $soapclient, $db;
    require_once(ROOT_PATH.'includes/debug/lib_log.php');
    QLog::log("createAcceptInventoryTransaction getProductId begin");
    $productId = getProductId($goods_and_style['goods_id'], 
                              $goods_and_style['style_id'], 
                              $goods_and_style['extra1']);
                              
    $serialNo = trim($serialNo);
    QLog::log("createAcceptInventoryTransaction getProductId end");
    //  if(!$productId) print $goods_and_style['goods_id']." ". $goods_and_style['style_id']. " no productId";
    $inventoryItemType = getInventoryItemType($goods_and_style['goods_id']);
    QLog::log("createAcceptInventoryTransaction getInventoryItemType end");
    $userName = (!$_SESSION['admin_name']) ? 'system' : $_SESSION['admin_name'];
    if (!$orderGoodsId) {$orderGoodsId = '-1'; } //如果orderGoodsId为空，要写上，防止出入库失败
    
    // 目前facility和container一一对应，故可以直接使用mapping
    $toContainerId = facility_get_default_container_id($toFacilityId); 

    $keys = array('inventoryTransactionTypeId'=>'StringValue', 
                  'productId'=>'StringValue', 
                  'amount'=>'NumberValue', 
                  'inventoryItemType' => 'StringValue', 
                  'serialNo'=>'StringValue', 
                  'orderId'=>'StringValue', 
                  'fromStatusId'=>'StringValue', 
                  'toStatusId'=>'StringValue', 
                  'userName'=>'StringValue', 
                  'acctType'=>'StringValue', 
                  'unitCost'=>'StringValue',
                  'orderGoodsId' => 'StringValue',
                  'toFacilityId' => 'StringValue',
                  'toContainerId' => 'StringValue',
    			  'providerId' => 'StringValue',
                  );
    $param = new HashMap();
    foreach ($keys as $key => $type) {
        if(${$key} == null) { continue; }
        $gv = new GenericValue();
        $method = 'set'.$type;
        $gv->$method(${$key});
        $param->put($key, $gv->getObject());
    }
    $input = func_get_args();
    return romeo_execute("createAcceptInventoryTransaction", $input, $param);
}

/**
 * 入库(新)
 * 调用新的Romeo入库接口，能够自动获取到可用的provider_id及unit_cost
 *
 * @param unknown_type $inventoryTransactionTypeId
 * @param unknown_type $goods_and_style
 * @param unknown_type $amount
 * @param unknown_type $serialNo
 * @param unknown_type $acctType
 * @param unknown_type $orderId
 * @param unknown_type $fromStatusId
 * @param unknown_type $toStatusId
 * @param unknown_type $unitCost
 * @param unknown_type $orderGoodsId
 */
function createAcceptInventoryTransactionNew($inventoryTransactionTypeId, $goods_and_style, 
                                          $amount, $serialNo, $acctType, $orderId, $fromStatusId, 
                                          $toStatusId, $unitCost = null, $orderGoodsId = 0, 
                                          $toFacilityId = null,$providerId) {
    global $soapclient, $db;
    require_once(ROOT_PATH.'includes/debug/lib_log.php');
    QLog::log("createAcceptInventoryTransactionNew getProductId begin");
    $productId = getProductId($goods_and_style['goods_id'], 
                              $goods_and_style['style_id'], 
                              $goods_and_style['extra1']);
                              
    $serialNo = trim($serialNo);
    QLog::log("createAcceptInventoryTransactionNew getProductId end");
    $inventoryItemType = getInventoryItemType($goods_and_style['goods_id']);
    QLog::log("createAcceptInventoryTransactionNew getInventoryItemType end");
    $userName = (!$_SESSION['admin_name']) ? 'system' : $_SESSION['admin_name'];
    if (!$orderGoodsId) {$orderGoodsId = '-1'; } //如果orderGoodsId为空，要写上，防止出入库失败
    
    // 目前facility和container一一对应，故可以直接使用mapping
    $toContainerId = facility_get_default_container_id($toFacilityId); 

    $keys = array('inventoryTransactionTypeId'=>'StringValue', 
                  'productId'=>'StringValue', 
                  'amount'=>'NumberValue', 
                  'inventoryItemType' => 'StringValue', 
                  'serialNo'=>'StringValue', 
                  'orderId'=>'StringValue', 
                  'fromStatusId'=>'StringValue', 
                  'toStatusId'=>'StringValue', 
                  'userName'=>'StringValue', 
                  'acctType'=>'StringValue', 
                  'unitCost'=>'StringValue',
                  'orderGoodsId' => 'StringValue',
                  'toFacilityId' => 'StringValue',
                  'toContainerId' => 'StringValue',
    			  'providerId' => 'StringValue',
                  );
    $param = new HashMap();
    foreach ($keys as $key => $type) {
        if(${$key} == null) { continue; }
        $gv = new GenericValue();
        $method = 'set'.$type;
        $gv->$method(${$key});
        $param->put($key, $gv->getObject());
    }
    $input = func_get_args();
    return romeo_execute("createAcceptInventoryTransactionNew", $input, $param);
}

/**
 * 出库
 *
 * @param string $inventoryTransactionTypeId
 * @param array $goods_and_style
 * @param int $amount
 * @param string $serialNo
 * @param string $acctType
 * @param string $fromOrderId
 * @param string $toOrderId
 * @param string $fromStatusId
 * @param string $toStatusId
 * @return object
 */
function createDeliverInventoryTransaction($inventoryTransactionTypeId, $goods_and_style, 
                                           $amount, $serialNo, $acctType, $fromOrderId, $toOrderId, 
                                           $fromStatusId, $toStatusId, $orderGoodsId,
                                           $fromFacilityId = null) {
    // 没有在 INV_STTS_DELIVER 库里面的 inventory_item了
    if ($fromStatusId == 'INV_STTS_DELIVER') {
        //FIXME: 在此以前的订单，已经有部分商品在deliver里面了，不能使用新的逻辑
        if (intval($fromOrderId) > intval(ORDER_ID_NEW_DELIVER)) {
        return true;
        }
    }
    
    global $soapclient, $db;
    require_once(ROOT_PATH.'includes/debug/lib_log.php');
    QLog::log("createDeliverInventoryTransaction getProductId begin");
    $productId = getProductId($goods_and_style['goods_id'], 
                              $goods_and_style['style_id'], 
                              $goods_and_style['extra1']);
    QLog::log("createDeliverInventoryTransaction getProductId end");
    $inventoryItemType = getInventoryItemType($goods_and_style['goods_id']);
    QLog::log("createDeliverInventoryTransaction getInventoryItemType end");
    $userName = $_SESSION['admin_name'];
    if (empty($userName)) {
    	$userName = "system";
    }
    $serialNo = trim($serialNo);
    if (!$orderGoodsId) {$orderGoodsId = '-1'; } //如果orderGoodsId为空，要写上，防止出入库失败

    // 目前facility和container一一对应，故可以直接使用mapping
    $fromContainerId = facility_get_default_container_id($fromFacilityId); 
    $partyId=$db->getOne("select goods_party_id from ecshop.ecs_goods where goods_id={$goods_and_style['goods_id']} limit 1");
    
    $keys = array('inventoryTransactionTypeId'=>'StringValue', 
                  'productId'=>'StringValue', 
                  'amount'=>'NumberValue', 
                  'inventoryItemType' => 'StringValue', 
                  'serialNo'=>'StringValue', 
                  'fromOrderId'=>'StringValue', 
                  'toOrderId' => 'StringValue', 
                  'fromStatusId'=>'StringValue', 
                  'toStatusId'=>'StringValue', 
                  'userName'=>'StringValue', 
                  'acctType'=>'StringValue', 
                  'orderGoodsId'=>'StringValue',
                  'fromFacilityId' => 'StringValue',
                  'fromContainerId' => 'StringValue',
                  );
    $param = new HashMap();
    foreach ($keys as $key => $type) {
        if(${$key} == null) { continue; }
        $gv = new GenericValue();
        $method = 'set'.$type;
        $gv->$method(${$key});
        $param->put($key, $gv->getObject());
    }

    $input = func_get_args();
    return romeo_execute("createDeliverInventoryTransaction", $input, $param);
}
/**
 * 出库 并增加库位信息
 */
function createDeliverInventoryAndLocationTransaction($inventoryTransactionTypeId, $goods_and_style, 
                                           $amount, $serialNo, $fromOrderId, $toOrderId, 
                                           $fromStatusId, $toStatusId, $orderGoodsId,$fromFacilityId,
                                           $statusId,$productId,//location
                                           $batchPickSn,$fromLocationBarcode,
                                           $validity,$actionType,$shipmentId) {
    global $soapclient, $db;
    require_once(ROOT_PATH.'includes/debug/lib_log.php');
    QLog::log("createDeliverInventoryTransaction getInventoryItemType begin");
    $inventoryItemType = getInventoryItemType($goods_and_style['goods_id']);
    QLog::log("createDeliverInventoryTransaction getInventoryItemType end");
    $userName = $_SESSION['admin_name'];
    if (empty($userName)) {
    	$userName = "system";
    }
    $serialNo = trim($serialNo);
    if (!$orderGoodsId) {$orderGoodsId = '-1'; } //如果orderGoodsId为空，要写上，防止出入库失败

    // 目前facility和container一一对应，故可以直接使用mapping
    $fromContainerId = facility_get_default_container_id($fromFacilityId); 
    $partyId=$db->getOne("select goods_party_id from ecshop.ecs_goods where goods_id={$goods_and_style['goods_id']} limit 1");
    //location中的orderid用batchpicksn
//    $orderId = $batchPickSn;
    $orderId = $toOrderId;
    $facilityId = $fromFacilityId;
    $keys = array('inventoryTransactionTypeId'=>'StringValue',
                  'productId'=>'StringValue', 
                  'amount'=>'NumberValue', 
                  'inventoryItemType' => 'StringValue', 
                  'serialNo'=>'StringValue', 
                  'fromOrderId'=>'StringValue', 
                  'toOrderId' => 'StringValue', 
                  'fromStatusId'=>'StringValue', 
                  'toStatusId'=>'StringValue', 
                  'userName'=>'StringValue',
                  'orderGoodsId'=>'StringValue',
                  'fromFacilityId' => 'StringValue',
                  'fromContainerId' => 'StringValue',//location
                  'fromLocationBarcode'=>'StringValue',
                  'validity' => 'StringValue',
                  'facilityId'=>'StringValue',
                  'statusId' => 'StringValue',
                  'actionType' => 'StringValue',
                  'orderId'=>'StringValue',
                  'partyId'=>'StringValue',
    			  'shipmentId'=>'StringValue',
    			  'batchPickSn'=>'StringValue',
                  );
    $param = new HashMap();
    foreach ($keys as $key => $type) {
        if(${$key} == null) { continue; }
		QLog::log($key.':'.${$key});
        $gv = new GenericValue();
        $method = 'set'.$type;
        $gv->$method(${$key});
        $param->put($key, $gv->getObject());
    }
    $input = func_get_args();
    if(empty($batchPickSn)){
    	//病单拣货出库
    	return romeo_execute("createDeliverInventoryAndLocationSickShipment", $input, $param);
    }else{
    	//正常批拣单出库
    	return romeo_execute("createDeliverInventoryAndLocationTransaction", $input, $param);
    }
}
function createDeliverLocationTransaction($productId,
							    $fromLocationBarcode,
		 						$amount,
		 						$validity,
		 						$facilityId,
		 						$statusId,
		 						$actionType,
		 						$orderId,
		 						$serialNo,
		 						$partyId){
	global $soapclient, $db;
    require_once(ROOT_PATH.'includes/debug/lib_log.php');
    QLog::log("createDeliverLocation  begin");
	$userName = $_SESSION['admin_name'];
    if (empty($userName)) {
    	$userName = "system";
    }
    $keys = array('productId'=>'StringValue',
                  'fromLocationBarcode'=>'StringValue', 
                  'amount'=>'NumberValue', 
                  'validity' => 'StringValue',
                  'facilityId'=>'StringValue',
                  'statusId' => 'StringValue',
                  'userName' => 'StringValue',
                  'actionType' => 'StringValue',
                  'orderId'=>'StringValue',
                  'partyId'=>'StringValue',
                  );
    $param = new HashMap();
    foreach ($keys as $key => $type) {
        if(${$key} == null) { continue; }
		QLog::log($key.':'.${$key});
        $gv = new GenericValue();
        $method = 'set'.$type;
        $gv->$method(${$key});
        $param->put($key, $gv->getObject());
    }
    $input = func_get_args();
    return romeo_execute("createDeliverLocationTransaction", $input, $param, $serialNo);
}

function terminalShipmentPick($shipmentId){
	global $soapclient;
    try{
    	$userName = $_SESSION['admin_name'];
    	$response = $soapclient->terminalShipmentPick(array('shipmentId'=>$shipmentId,'actionUser'=>$userName));
		return $response->return;
    }catch (SoapFault $e) {
        echo("SOAP调用失败: (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})");
        return false;
	}
}

function terminalShipmentRecheck($shipmentId){
	global $soapclient;
	$request=array();
	$request['shipmentId'] = $shipmentId;
	$request['actionUser'] = $_SESSION['admin_name'];
    try{
    	$response = $soapclient->terminalShipmentRecheck($request);    	
    	$result = $response->return;
		return $result;
    }catch (SoapFault $e) {
        echo("SOAP调用失败: (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})");
        return false;
	}
}

/**
 * 出库 并增加库位信息
 */
function createLocation($partyId, $locationBarcode,$locationType) {
    global $soapclient, $db;
    require_once(ROOT_PATH.'includes/debug/lib_log.php');
    QLog::log("createDeliverInventoryTransaction getInventoryItemType begin");
    $userName = $_SESSION['admin_name'];
    if (empty($userName)) {
    	$userName = "system";
    }
    $keys = array('locationBarcode'=>'StringValue',
                  'locationType'=>'StringValue', 
                  'partyId'=>'StringValue', 
                  'userName'=>'StringValue', 
                  );
    $param = new HashMap();
    foreach ($keys as $key => $type) {
        if(${$key} == null) { continue; }
        $gv = new GenericValue();
        $method = 'set'.$type;
        $gv->$method(${$key});
        $param->put($key, $gv->getObject());
    }
    $input = func_get_args();
    return romeo_execute("createLocation", $input, $param);
}

function deleteLocation($locationBarcode) {
    global $soapclient, $db;
    require_once(ROOT_PATH.'includes/debug/lib_log.php');
    QLog::log("createDeliverInventoryTransaction getInventoryItemType begin");
    $userName = $_SESSION['admin_name'];
    if (empty($userName)) {
    	$userName = "system";
    }
    $keys = array('locationBarcode'=>'StringValue',
                  'userName'=>'StringValue', 
                  );
    $param = new HashMap();
    foreach ($keys as $key => $type) {
        if(${$key} == null) { continue; }
        $gv = new GenericValue();
        $method = 'set'.$type;
        $gv->$method(${$key});
        $param->put($key, $gv->getObject());
    }
    $input = func_get_args();
    return romeo_execute("deleteLocation", $input, $param);
}

/**
 * 采购订单入库一步接口调用
 */
function createPurchaseAcceptAndTransfer($goods_and_style, 
                                          $amount, $serialNos, $acctType, $orderId, $fromStatusId, 
                                          $toStatusId, $unitCost, $orderGoodsId, 
                                          $toFacilityId,$providerId) {

    global $soapclient, $db;
    require_once(ROOT_PATH.'includes/debug/lib_log.php');
    QLog::log("createPurchaseAcceptAndTransfer getProductId begin");
    QLog::log("providerId:".$providerId);
    $productId = getProductId($goods_and_style['goods_id'], 
                              $goods_and_style['style_id'], 
                              $goods_and_style['extra1']);
    QLog::log("createPurchaseAcceptAndTransfer getProductId end");
    $inventoryItemType = getInventoryItemType($goods_and_style['goods_id']);

    QLog::log("createPurchaseAcceptAndTransfer getInventoryItemType end");
    $userName = (!$_SESSION['admin_name']) ? 'system' : $_SESSION['admin_name'];
    
    //$serialNos = array_map('trim', $serialNos);
    if (!$orderGoodsId) {$orderGoodsId = '-1'; } //如果orderGoodsId为空，要写上，防止出入库失败

    // 目前facility和container一一对应，故可以直接使用mapping
    $toContainerId = facility_get_default_container_id($toFacilityId); 
    
    $keys = array(
                  'productId'=>'StringValue', 
                  'amount'=>'NumberValue', 
                  'inventoryItemType' => 'StringValue', 
                  'serialNos'=>'ArrayList',  // 通过arraylist传过去，java端取不到，所以只有在后面再加一个参数了
                  'orderId'=>'StringValue', 
                  'fromStatusId'=>'StringValue', 
                  'toStatusId'=>'StringValue', 
                  'userName'=>'StringValue', 
                  'acctType'=>'StringValue', 
                  'unitCost'=>'StringValue',
                  'orderGoodsId'=>'StringValue',
                  'toFacilityId' => 'StringValue',
                  'toContainerId' => 'StringValue',
    			  'providerId' => 'StringValue',
                  );
    $param = new HashMap();
    foreach ($keys as $key => $type) {
        if(${$key} == null) { continue; }
        $gv = new GenericValue();
        $method = 'set'.$type;
        $gv->$method(${$key});
        $param->put($key, $gv->getObject());
    }
//pp($param);exit();
    $input = func_get_args();
    return romeo_execute("createPurchaseAcceptAndTransfer", $input, $param, $serialNos);
}

/**
 * 采购订单入库一步接口调用 并增加库位信息 
 */
function createPurchaseAcceptAndLocationTransaction($goods_and_style, 
                                          $amount, $serialNos, $acctType, $orderId, $fromStatusId, 
                                          $toStatusId, $unitCost, $orderGoodsId, 
                                          $toFacilityId,$locationBarcode,$goodsBarcode,$validity,$batchSn,
                                          $locationStatusId,$actionType,$providerId) {

    global $soapclient, $db;

    require_once(ROOT_PATH.'includes/debug/lib_log.php');
    QLog::log("createPurchaseAcceptAndTransfer getProductId begin");
    $productId = getProductId($goods_and_style['goods_id'], 
                              $goods_and_style['style_id'], 
                              $goods_and_style['extra1']);
    QLog::log("createPurchaseAcceptAndTransfer getProductId end");
    $inventoryItemType = getInventoryItemType($goods_and_style['goods_id']);
    QLog::log("createPurchaseAcceptAndTransfer getInventoryItemType end");
    $userName = (!$_SESSION['admin_name']) ? 'system' : $_SESSION['admin_name'];
    
    $partyId=$db->getOne("select goods_party_id from ecshop.ecs_goods where goods_id={$goods_and_style['goods_id']} limit 1");
    //$serialNos = array_map('trim', $serialNos);
    if (!$orderGoodsId) {$orderGoodsId = '-1'; } //如果orderGoodsId为空，要写上，防止出入库失败
    // 目前facility和container一一对应，故可以直接使用mapping
    $toContainerId = facility_get_default_container_id($toFacilityId); 
    
    $keys = array(
                  'productId'=>'StringValue', 
                  'amount'=>'NumberValue', 
                  'inventoryItemType' => 'StringValue', 
                  'serialNos'=>'ArrayList',  // 通过arraylist传过去，java端取不到，所以只有在后面再加一个参数了
                  'orderId'=>'StringValue', 
                  'fromStatusId'=>'StringValue', 
                  'toStatusId'=>'StringValue', 
                  'userName'=>'StringValue', 
                  'acctType'=>'StringValue', 
                  'unitCost'=>'StringValue',
                  'orderGoodsId'=>'StringValue',
                  'toFacilityId' => 'StringValue',
                  'toContainerId' => 'StringValue',
                  'locationBarcode' => 'StringValue',
                  'goodsBarcode' => 'StringValue',
                  'validity' => 'StringValue',
                  'batchSn' => 'StringValue',
                  'partyId' =>'NumberValue',
                  'locationStatusId' => 'StringValue',
                  'locationType' => 'StringValue',
                  'actionType' =>'StringValue',
                  'providerId' => 'StringValue',
                  );
    $param = new HashMap();
    Qlog::log('createPurchaseAcceptAndLocationTransaction test unit_cost '.$unitCost);
    foreach ($keys as $key => $type) {
        if(!isset(${$key})) { continue;}
        $gv = new GenericValue();
        $method = 'set'.$type;
        $gv->$method(${$key});
        Qlog::log('createPurchaseAcceptAndLocationTransaction foreach '.$key.' val:'.${$key});
        $param->put($key, $gv->getObject());
    }
    
    $input = func_get_args();
    return romeo_execute_message("createPurchaseAcceptAndLocationTransaction", $input, $param, $serialNos);
}
function createAcceptLocation($productId,
							    $locationBarcode,
							    $goodsBarcode,
		 						$amount,
		 						$validity,
		 						$toFacilityId,
		 						$locationStatusId,
		 						$actionType,
		 						$orderId,
		 						$serialNos,
		 						$partyId,
		 						$goods_id){
	global $soapclient, $db;
    require_once(ROOT_PATH.'includes/debug/lib_log.php');
    QLog::log("createPurchaseAcceptAndTransfer getProductId begin");

    $inventoryItemType = getInventoryItemType($goods_id);
    QLog::log("createPurchaseAcceptAndTransfer getInventoryItemType end");
    $userName = (!$_SESSION['admin_name']) ? 'system' : $_SESSION['admin_name'];

    $keys = array(
                  'productId'=>'StringValue', 
                  'locationBarcode' => 'StringValue',
                  'goodsBarcode' => 'StringValue',
                  'amount'=>'NumberValue', 
                  'validity' => 'StringValue',
    			  'toFacilityId' => 'StringValue',
    			  'locationStatusId' => 'StringValue',
    			  'actionType' =>'StringValue',
                  'orderId'=>'StringValue', 
                  'partyId' =>'NumberValue',    
   				  'inventoryItemType' => 'StringValue',
    			  'userName'=>'StringValue',
                  );
    $param = new HashMap();
    foreach ($keys as $key => $type) {
        if(!isset(${$key})) { continue;}
        $gv = new GenericValue();
        $method = 'set'.$type;
        $gv->$method(${$key});
        $param->put($key, $gv->getObject());
    }
    
    $input = func_get_args();
    //pp($param);
    return romeo_execute_message("createAcceptLocation", $input, $param, $serialNos);
}
/**
 * 采购订单入库一步接口调用，由Command调用，区别在于 不用QLOG，
 */
function createPurchaseAcceptAndTransferCommand($goods_and_style, 
                                          $amount, $serialNos, $acctType, $orderId, $fromStatusId, 
                                          $toStatusId, $unitCost, $orderGoodsId, 
                                          $toFacilityId,$providerId) {

    global $soapclient, $db;
    require_once(ROOT_PATH.'includes/debug/lib_log.php');
    echo("createPurchaseAcceptAndTransfer getProductId begin");
    $productId = getProductId($goods_and_style['goods_id'], 
                              $goods_and_style['style_id'], 
                              $goods_and_style['extra1']);
    echo("createPurchaseAcceptAndTransfer getProductId end");
    $inventoryItemType = getInventoryItemType($goods_and_style['goods_id']);
    echo("createPurchaseAcceptAndTransfer getInventoryItemType end");
    $userName = (!$_SESSION['admin_name']) ? 'system' : $_SESSION['admin_name'];
    //$serialNos = array_map('trim', $serialNos);
    if (!$orderGoodsId) {$orderGoodsId = '-1'; } //如果orderGoodsId为空，要写上，防止出入库失败
    $partyId=$db->getOne("select goods_party_id from ecshop.ecs_goods where goods_id={$goods_and_style['goods_id']} limit 1");
    // 目前facility和container一一对应，故可以直接使用mapping
    $toContainerId = facility_get_default_container_id($toFacilityId); 
    $keys = array(
                  'productId'=>'StringValue', 
                  'amount'=>'NumberValue', 
                  'inventoryItemType' => 'StringValue', 
                  'serialNos'=>'ArrayList',  // 通过arraylist传过去，java端取不到，所以只有在后面再加一个参数了
                  'orderId'=>'StringValue', 
                  'fromStatusId'=>'StringValue', 
                  'toStatusId'=>'StringValue', 
                  'userName'=>'StringValue', 
                  'acctType'=>'StringValue', 
                  'unitCost'=>'StringValue',
                  'orderGoodsId'=>'StringValue',
                  'toFacilityId' => 'StringValue',
                  'toContainerId' => 'StringValue',
                  'providerId' => 'StringValue',
                  'partyId' => 'StringValue',
                  );
    $param = new HashMap();
    foreach ($keys as $key => $type) {
        if(${$key} == null) { continue; }
        $gv = new GenericValue();
        $method = 'set'.$type;
        $gv->$method(${$key});
        $param->put($key, $gv->getObject());
    }

    $input = func_get_args();
    return romeo_execute_command("createPurchaseAcceptAndTransfer", $input, $param, $serialNos);
}

/**
 * 库位转移
 */
function createTransferLocationTransaction($goods_and_style,$fromLocationBarcode, $toLocationBarcode, $serialNos,
                                           $goodsBarcode, $amount, $validity, $batch_sn, $facilityId, 
                                           $statusId, $actionType,$orderId){

    global $soapclient, $db;
    require_once(ROOT_PATH.'includes/debug/lib_log.php');
    QLog::log("createTransferLocationTransaction getProductId begin");
    $productId = getProductId($goods_and_style['goods_id'], 
                              $goods_and_style['style_id'], 
                              $goods_and_style['extra1']);
    QLog::log("productid:".$productId);
    $userName = (!$_SESSION['admin_name']) ? 'system' : $_SESSION['admin_name'];
    
    $partyId=$db->getOne("select goods_party_id from ecshop.ecs_goods where goods_id={$goods_and_style['goods_id']} limit 1");
    //$serialNos = array_map('trim', $serialNos);
    $keys = array(
                  'productId'=>'StringValue', 
                  'fromLocationBarcode'=>'StringValue',
                  'toLocationBarcode'=>'StringValue',                 
                  'serialNos'=>'ArrayList',  // 通过arraylist传过去，java端取不到，所以只有在后面再加一个参数了
                  'goodsBarcode'=>'StringValue',
                  'amount'=>'NumberValue', 
                  'validity' => 'StringValue',
                  'facilityId'=>'StringValue',
                  'statusId' => 'StringValue',
                  'userName' => 'StringValue',
                  'actionType' => 'StringValue',
                  'orderId'=>'StringValue',
                  'partyId'=>'StringValue'
                  );
    $param = new HashMap();
    foreach ($keys as $key => $type) {
        if(${$key} == null) { continue; }
        $gv = new GenericValue();
        $method = 'set'.$type;
        $gv->$method(${$key});
        $param->put($key, $gv->getObject());
    }
     
    $input = func_get_args();
    return romeo_execute_message("createTransferLocationTransaction", $input, $param, $serialNos);
}

/**
 * 执行romeo操作，带错误信息的数组 
 * ljzhou 2013.08.19
 * @param str $method
 * @param array $input
 * @param Object $param
 * @param Object $param1
 * 
 * @return 带错误信息的数组
 */
function romeo_execute_message($method, $input, $param, $param1 = null) {
    global $soapclient, $db;
    $message = array(
        // 传入参数
        'input'=>$input,  
        // 组织后参数
        'param'=>$param->hasharray_mapping,
        // 调用方法
        'method'=>$method,
        // 调用结果
        'status'=>'OTHER',
    );
    try {
        if ($method == 'createPurchaseAcceptAndTransfer') {
            $result = $soapclient->$method(array('serialNumbers'=>$param1, 'context'=>$param->getObject()));
        } else if($method == 'createPurchaseAcceptAndLocationTransaction') {
        	$result = $soapclient->$method(array('serialNumbers'=>$param1, 'context'=>$param->getObject()));
        }
         else if($method == 'createTransferLocationTransaction') {
        	$result = $soapclient->$method(array('serialNumbers'=>$param1, 'context'=>$param->getObject()));
        }
    	 else if($method == 'createAcceptLocation') {
        	$result = $soapclient->$method(array('serialNumbers'=>$param1, 'context'=>$param->getObject()));
        }
        else{
            $result = $soapclient->$method(array('arg0'=>$param->getObject()));
        }
        QLog::log("{$method} soap call end");
        $return_hashmap = new HashMap();
        $return_hashmap->setObject($result->return);
        $status = $return_hashmap->get("status")->stringValue;
        $error = $return_hashmap->get("error")->stringValue;
        Qlog::log('mee='.$error);
        
        $message['result'] = $result;
        $message['status'] = $status;
        $message['error'] = $error;
    } catch (Exception $e) {
        $message['error'] = $e->getMessage();
        $status = 'Exception';
        QLog::log("{$method} soap call exception");
    }
    $message['status'] = $status;
    $script = $db->escape_string($_SERVER['PHP_SELF']);
    QLog::log("{$method} query end");
    return $message;
}

/**
 * 执行romeo操作，记录日志
 *
 * @param str $method
 * @param array $input
 * @param Object $param
 * @param Object $param1
 * 
 * @return boolean
 */
function romeo_execute($method, $input, $param, $param1 = null) {
    global $soapclient, $db;
    $message = array(
        // 传入参数
        'input'=>$input,  
        // 组织后参数
        'param'=>$param->hasharray_mapping,
        // 调用方法
        'method'=>$method,
        // 调用结果
        'status'=>'OTHER',
    );
    try {
		$data_with_serialNumber_methods = array(
			'createPurchaseAcceptAndTransfer',
			'createDeliverLocationTransaction',
			'createSupplierReturnOrderNewGT'
		);
        if (in_array($method,$data_with_serialNumber_methods)) {
            $result = $soapclient->$method(array('serialNumbers'=>$param1, 'context'=>$param->getObject()));
        }
    	else if(empty($param1)){
			$result = $soapclient->$method(array('arg0'=>$param->getObject()));
    	}else{
			$result = $soapclient->$method(array('serialNumbers'=>$param1, 'context'=>$param->getObject()));
    	}

        
        QLog::log("{$method} soap call end");
        $return_hashmap = new HashMap();
        $return_hashmap->setObject($result->return);
        $status = $return_hashmap->get("status")->stringValue;
        $message['result'] = $result;
    } catch (Exception $e) {
        $message['exception'] = $e->getMessage();
        $status = 'Exception';
        QLog::log("{$method} soap call exception：".$e);
    }
    $message['status'] = $status;
    $script = $db->escape_string($_SERVER['PHP_SELF']);
    QLog::log("{$method} query end");
    return $status==='OK';
}

/**
 * 执行romeo操作，记录日志，由Command调用，区别在于不用QLOG，
 *
 * @param str $method
 * @param array $input
 * @param Object $param
 * @param Object $param1
 * 
 * @return boolean
 */
function romeo_execute_command($method, $input, $param, $param1 = null) {
    global $soapclient, $db;
    $message = array(
        // 传入参数
        'input'=>$input,  
        // 组织后参数
        'param'=>$param->hasharray_mapping,
        // 调用方法
        'method'=>$method,
        // 调用结果
        'status'=>'OTHER',
    );

    try {
        if ($method == 'createPurchaseAcceptAndTransfer') {
            $result = $soapclient->$method(array('serialNumbers'=>$param1, 'context'=>$param->getObject()));
        } else {
            $result = $soapclient->$method(array('arg0'=>$param->getObject()));
        }
        echo("{$method} soap call end");
        $return_hashmap = new HashMap();
        $return_hashmap->setObject($result->return);
        $status = $return_hashmap->get("status")->stringValue;
        $message['result'] = $result;
    } catch (Exception $e) {
        $message['exception'] = $e->getMessage();
        $status = 'Exception';
        echo("{$method} soap call exception");
    }
    $message['status'] = $status;
    $script = $db->escape_string($_SERVER['PHP_SELF']);
    echo("{$method} query end");
    return $status==='OK';
}


/**
 * @+{ 
 * 仓库设备相关
 */

/* 
 * 取得状态正常的仓库设施列表
 * 
 * @return array
 */
function facility_get_all_list() {
    static $facility_list;

    if (!isset($facility_list)) {
        $cache_id = "inventory:facilityList";  
        include_once('lib_cache.php');
        $cache = RomeoApi_Cache::instance();
        $facility_list = $cache->get($cache_id);
        
        if ($facility_list === false) {
	    	$facility_list = array();
	        try {
	            $handle = soap_get_client('InventoryService', 'ROMEO', 'Soap_Client');
	            $response = $handle->getFacilityList();
	            if (isset($response) && isset($response->Facility)) {
	                $response->Facility = wrap_object_to_array($response->Facility);
	                foreach ($response->Facility as $facility) {
	                    if ($facility->isClosed != 'Y') {
	                        $facility_list[$facility->facilityId] = $facility;
	                    }
	                }
	            }
	            $cache->set($cache_id, $facility_list);
	        } catch (SoapFault $e) {
	            trigger_error("SOAP调用失败: (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})", E_USER_WARNING);
	        }
        }
    }
    
    return $facility_list;
}

/**
 * 返回仓库设施的mapping表
 *
 * @return array
 */
function facility_list() {
	static $facility_mapp;
	
	if (!isset($facility_mapp)) {
		$facility_mapp = array();
	    $facility_list = facility_get_all_list();
	    if ($facility_list) {
	    	foreach ($facility_list as $facility) {
	    		$facility_mapp[$facility->facilityId] = $facility->facilityName; 
	    	}
	    }
	}
	
    return $facility_mapp;
}

/**
 * 取得仓库设施类型的mapping表
 * 
 * @return array
 */
function facility_type_list() {
    static $facility_type_mapp;
    
    if (!isset($facility_type_mapp)) {
    	$facility_type_mapp = array();
	    try {
	        $handle = soap_get_client('InventoryService', 'ROMEO', 'Soap_Client');
	        $response = $handle->getFacilityTypeList();
	        if (isset($response) && isset($response->FacilityType)) {
	            $response->FacilityType = wrap_object_to_array($response->FacilityType);
	            foreach ($response->FacilityType as $item) {
	            	$facility_type_mapp[$item->facilityTypeId] = $item->description;
	            }
	        }
	    } catch (SoapFault $e) {
            trigger_error("SOAP调用失败: (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})", E_USER_WARNING);
	    }
    }
    
    return $facility_type_mapp;
}

/**
 * 取得指定仓库的默认容器
 * 
 * @return string  返回容器的ID
 */
function facility_get_default_container_id($facility_id) {
    if ($facility_id) {
        $facility_container_list = facility_get_container_list($facility_id);
        if ($facility_container_list) {
            $container = reset($facility_container_list);
            return $container->containerId;
        }
    }
    return NULL;
}

/**
 * 取得指定仓库下的所有容器, 如果不指定仓库id，则返回所有的容器
 * 
 * @param int $facility_id  
 * 
 * @return array
 */
function facility_get_container_list($facility_id = null) {
	// 所有仓库容器列表
    static $container_list;
    
    if (!isset($container_list)) {
        $cache_id = "inventory:facilityContainerList";  
        include_once('lib_cache.php');
        $cache = RomeoApi_Cache::instance();
        $container_list = $cache->get($cache_id);
        if ($container_list === false) {
        	$container_list = array();
		    try {
		        $handle = soap_get_client('InventoryService', 'ROMEO', 'Soap_Client');
		        $response = $handle->getContainerList();
		        if (isset($response) && isset($response->Container)) {
		            $response->Container = wrap_object_to_array($response->Container);
		            foreach ($response->Container as $container) {
		            	if ($container->isClosed != 'Y') {  // 未关闭的
		                    $container_list[] = $container;
		            	}
		            }
		        }
			    $cache->set($cache_id, $container_list);
		    } catch (SoapFault $e) {
		        trigger_error("SOAP调用失败: (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})", E_USER_WARNING);
		    }
        }
    }
    
    // 如果指定了仓库设施ID,则返回属于该仓库设施的容器
    if (!is_null($facility_id)) {
    	$facility_container_list = array();
    	foreach ($container_list as $container) {
    		if ($container->facilityId == $facility_id) {
                $facility_container_list[] = $container; 	
    		}
    	}
    	return $facility_container_list;
    }
    
    return $container_list;
}

/**
 * 添加仓库
 * 
 * @param array $row 
 *   - facility_name 设施名
 *   - facility_type_id 类型
 *   - owner_party_id 所属组织。比如 如果一个仓库属于欧酷，那欧酷下的组织都能用
 *   - weight
 * @return string|false 成功返回新仓库设施的ID, 失败返回FALSE
 */
function facility_insert($row, & $failed = array()) {
	if (empty($row['facility_name'])) {
	    $failed[] = '仓库设施名不能为空';
	    return false;
	}
	
	if (empty($row['facility_type_id'])) {
        $failed[] = '请指定仓库类型';
        return false;
	}
	if (!array_key_exists($row['facility_type_id'], facility_type_list())) {
        $failed[] = '该库存类型不存在';
        return false;
	}
	
	if (empty($row['owner_party_id'])) {
        $failed[] = '请指定该仓库设施所属的组织';
        return false;
	}
	if (empty($row['physical_facility'])) {
	    $failed[] = '物理仓不能为空';
	    return false;
	}
	if (empty($row['is_out_ship'])){
		$row['is_out_ship']='N';
	}
	
	// 添加仓库实施
	$param = new HashMap();
	$p[0] = new GenericValue();
	$p[1] = new GenericValue();
	$p[2] = new GenericValue();
	$p[3] = new GenericValue();
	$p[4] = new GenericValue();
	$param->put('facilityName',   $p[0]->setStringValue($row['facility_name'])->getObject());
	$param->put('facilityTypeId', $p[1]->setStringValue($row['facility_type_id'])->getObject());
	$param->put('ownerPartyId',   $p[2]->setStringValue($row['owner_party_id'])->getObject());
	$param->put('physicalFacility',   $p[3]->setStringValue($row['physical_facility'])->getObject());
	$param->put('isOutShip',   $p[4]->setStringValue($row['is_out_ship'])->getObject());
	try {
		$handle = soap_get_client('InventoryService', 'ROMEO', 'Soap_Client');
		$response = $handle->createFacility($param->getObject());
		if (isset($response)) {
		    $result = new HashMap();
		    $result->setObject($response);
		    if ($result->get("status")->stringValue == 'OK') {
		        include_once('lib_cache.php');
		        RomeoApi_Cache::instance()->delete("inventory:facilityList");
		        return $result->get("facilityId")->stringValue;
	        }
		}
	}
	catch (SoapFault $e) {
        $failed[] = "SOAP调用失败: (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})";
	}
	
	return false;
}

/**
 * 创建一个仓库容器
 *
 * @param array $row
 *   - facility_id 所属仓库设施的ID
 *   - description 描述
 *   - container_id 如果指定了该ID则尝试更新
 * @return boolean
 */
function facility_container_save($row, & $failed = array()) {
    if (empty($row['facility_id'])) {
        $failed[] = "没有指定仓库";
        return false;
    }
    
    try {
	    $handle = soap_get_client('InventoryService', 'ROMEO', 'Soap_Client');
        $param = new HashMap();
        $p[0] = new GenericValue();
        $param->put('facilityId',  $p[0]->setStringValue($row['facility_id'])->getObject());
	    
        // 更新 
        if (isset($row['container_id'])) {
            if (isset($row['description'])) {
                $p[1] = new GenericValue();
                $param->put('description', $p[1]->setStringValue($row['description'])->getObject());
            }
            $p[2] = new GenericValue();
            $param->put('containerId', $p[2]->setStringValue($row['container_id'])->getObject());
            $response = $handle->updateContainer($param->getObject());
        }
	    // 创建
        else {
        	if (empty($row['description'])) {
                $failed[] = "没有指定仓库的描述信息";
                return false;
        	}
            $p[1] = new GenericValue();
            $param->put('description', $p[1]->setStringValue($row['description'])->getObject());
            $response = $handle->createContainer($param->getObject());
        }
	    
        if (isset($response)) {
	        $result = new HashMap();
	        $result->setObject($response);
	        if ($result->get("status")->stringValue == 'OK') {
	            include_once('lib_cache.php');
	            RomeoApi_Cache::instance()->delete("inventory:facilityContainerList");  // 清除容器缓存
	            return true;
	        }
	    }
    }
    catch (Exception $e) {
    	$failed[] = "创建容器时发生异常，信息：". $e->getMessage();
    }
    
    return false;
}

/**
  * ecshop.ecs_order_info.shipping_status =  9 => '已配货，待出库'
  * status:Exception,OK,
  * Failed,shipment拣货没有成功
  * qdi 2013.08.21
*/

function terminal_batch_pick($bpsn){
    global $soapclient;
    $result = array();
    try{
    	$userName = $_SESSION['admin_name'];
    	$response = $soapclient->terminalBatchPick(array('batchPickSn'=>$bpsn,'actionUser'=>$userName));
//    	print_r($result);
    	$return_hashmap = new HashMap();
        $return_hashmap->setObject($response->return);
        $status = $return_hashmap->get("status")->stringValue;
        if($status == 'Failed'){
        	$status = false;
        	$shipmentIds = $return_hashmap->get("failedShipmentIds")->arrayList->anyType;
			if(is_array($shipmentIds)){
				$result['shipmentIds'] = $shipmentIds;
			}else{
				$result['shipmentIds'] = array();
				$result['shipmentIds'][] = $shipmentIds;
			}
        }else{
        	$status = true;
        }
    }catch (SoapFault $e) {
//    	$result['exception'] = $e->getMessage();
		$result['shipmentIds'] = array();	
        $status = false;
        echo("SOAP调用失败: (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})");
	}
	$result['success'] = $status;
    return $result;
}
/*
 * 一键完结新库存出库
 * */
function oneKeyBatchPick($bpsn){
    global $soapclient;
    $result = array();
    $result['success'] = true;
    try{
    	$userName = $_SESSION['admin_name'];
    	QLog::log("oneKeyBatchPick({$bpsn},{$userName})");
    	$result['success'] = $soapclient->oneKeyBatchPick(array('batchPickSn'=>$bpsn,'actionUser'=>$userName));
    	if(!$result['success'])
    		$result['error'] = "webservice oneKeyBatchPick 调用失败";
    }catch (SoapFault $e) {
    	$result['success'] = false;
    	$result['error'] = "SOAP调用失败: (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})";
	}
    return $result;
}
/*
 * 一键完结新库存出库
 * 一个订单完结不成功，不影响下一个订单出库
 * */
function oneKeyBatchPickNew($bpsn){
	$status = true;
	global $soapclient,$db;
	$sql = "
		select shipment_id from romeo.batch_pick_mapping where batch_pick_sn = '{$bpsn}' and is_pick = 'N'";
	$shipmentIds = $db->getAll($sql);
	$userName = $_SESSION['admin_name'];
	foreach($shipmentIds as $shipmentId){
		try{
			$soapclient->oneKeyShipmentPick(array('shipmentId'=>$shipmentId['shipment_id'],'actionUser'=>$userName));
		}catch(Exception $e){
			$status = false;
		}
	}
	if($status){
		terminalBatchPickSimple($bpsn);
	}else{
		$sql = "
			UPDATE  romeo.batch_pick bp
			set is_pick = 'S'
			where batch_pick_sn = '{$bpsn}'";
		$db->query($sql);
	}
	return $status;
}
function terminalBatchPickSimple($bpsn){
	global $soapclient;
	$userName = $_SESSION['admin_name'];
	$soapclient->terminalBatchPickSimple(array('batchPickSn'=>$bpsn,'actionUser'=>$userName));
}

function deliverLocationProduct($bpsn,$location_barcode,$product_id){
	global $soapclient;
    $result = array();
    $result['success'] = true;
    try{
    	$userName = $_SESSION['admin_name'];
    	QLog::log("deliverLocationProduct({$bpsn},{$userName},{$location_barcode},{$product_id})");
    	$result['success'] = $soapclient->deliverLocationProduct(
    			array('batchPickSn'=>$bpsn,
    				'actionUser'=>$userName,
    				'locationBarcode'=>$location_barcode,
    			    'productId'=>$product_id));
    	if(!$result['success'])
    		$result['error'] = "webservice deliverLocationProduct 调用失败";
    }catch (SoapFault $e) {
    	$result['success'] = false;
    	$result['error'] = "SOAP调用失败: (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})";
	}
    return $result;
}

/**
 * 仓库设备相关
 * 
 * }-@ 
 */

/**
 * 获取库存列表
 * 
 */
function _get_inventory_summary_list($product_id, $status_id, $facility_id, $container_id) {
    global $soapclient;
    $inventorySummaryList = $soapclient->getInventorySummaryList($product_id, $status_id, 
        $facility_id, $container_id);
    return $inventorySummaryList->return->InventorySummary;
}

function get_inventory_summary_list($product_id, $status_id, $facility_id, $container_id) {
    $inventorySummaryList = _get_inventory_summary_list($product_id, $status_id, $facility_id, 
        $container_id);
    $inventory_summary_list = array();
    foreach ($inventorySummaryList as $inventorySummary) {
        if ($status_id != null && $inventorySummary->statusId !== $status_id) {
            continue;
        }
        $inventory_summary_list["{$inventorySummary->goodsId}_{$inventorySummary->styleId}"] += 
            $inventorySummary->availableToReserved;
    }
    return $inventory_summary_list;
}

 /**
给一批Shipment，检查其是否库存充足。
CHECKED ON 20130914
USED IN
Deal_Batch_Pick.php
**/
function is_shipments_have_enough_inventory($shipment_id_array){
  global $soapclient;
//  QLog::log("is_shipments_have_enough_inventory($shipment_id_array) called 1");
  $request=array();
  $request['shipmentIds']=array();
  foreach ($shipment_id_array as $key => $value) {
//  	  QLog::log("is_shipments_have_enough_inventory shipment_id:".$value);
      $request['shipmentIds'][]=$value;
  }
  try{ 
	  $result = $soapclient->shipmentsInventoryEnough($request);
	  $is_enough = $result->return;
//	  QLog::log("record_shipments_to_batch_pick($shipment_id_array) result $is_enough");
  	  return $is_enough;
  }catch (Exception $e) {
      echo("shipmentsInventoryEnough soap call exception:".$e->getMessage());
  }
}

function inventoryStockCount($location_barcode,$goods_barcode,$serial_number,$goods_number){
  global $soapclient;
  $result = array();
  $result['success'] = true;
  $userName = $_SESSION['admin_name'];
  if (empty($userName)) {
  	$userName = "system";
  }
  QLog::log("inventoryStockCount($location_barcode,$goods_barcode,$serial_number,$goods_number) called");
  $request = array('locationBarcode' => $location_barcode,
  					'goodsBarcode' => $goods_barcode,
					'serialNumber' => $serial_number,
					'goodsNumber' => $goods_number,
  					'actionUser' => $userName);

  try{ 
	  $response = $soapclient->inventoryStockCount($request);
	  $result['success'] = true;
  	  return $result;
  }catch (Exception $e) {
      echo("shipmentsInventoryEnough soap call exception:".$e->getMessage());
      $result['success'] = false;
      $result['error'] = "shipmentsInventoryEnough soap call exception:".$e->getMessage();
  }
}

function oneKeyOrderPick($orderId){
  global $soapclient;
  $result = array();
  $result['success'] = true;
  $userName = $_SESSION['admin_name'];
  if(empty($userName)){
  	$userName = 'system';
  }
  $request = array('orderId' => $orderId,
  					'actionUser' => $userName);
  try{ 
	  $response = $soapclient->oneKeyOrderPick($request);
	  $result['success'] = true;
  	  return $result;
  }catch (Exception $e) {
      echo("oneKeyOrderPick soap call exception:".$e->getMessage());
      $result['success'] = false;
      $result['error'] = "shipmentsInventoryEnough soap call exception:".$e->getMessage();
  }
}


function delInvItemDetail($inventoryItemDetailId){
  global $soapclient;
  $result = array();
  $result['success'] = true;
  $userName = $_SESSION['admin_name'];
  $request = array('inventoryItemDetailId' => $inventoryItemDetailId);
  try{ 
	  $response = $soapclient->delInvItemDetail($request);
	  $result['success'] = true;
  	  return $result;
  }catch (Exception $e) {
      echo("delInvItemDetail soap call exception:".$e->getMessage());
      $result['success'] = false;
      $result['error'] = "delInvItemDetail soap call exception:".$e->getMessage();
  }
}	


/**
 * 老流程配货出库
 * $order_list 订单列表
 * $real_out_goods_numbers  product_id对应的出库数 的数组
 * $serial_numbers 要出库的串号
 */
 
function stock_delivery($order_list,$real_out_goods_numbers,$serial_numbers) {
	global $db;
	$result = array();
	if(empty($order_list)) {
		$result['error'] = 'stock_delivery order_list为空';
		return $result;
	}
	if(empty($real_out_goods_numbers)) {
		$result['error'] = 'stock_delivery real_out_goods_numbers为空';
		return $result;
	}
	// 循环订单的商品，检查输入的产品串号
    foreach($order_list as $order_item){
	    foreach($order_item['order_goods'] as $order_goods_item){
	      $product_id = getProductId($order_goods_item['goods_id'], $order_goods_item['style_id']);
	      $inventoryItemType = getInventoryItemType($order_goods_item['goods_id']);
	      if($inventoryItemType == 'SERIALIZED') {
	     	 $loop_number = $order_goods_item['goods_number']; 
	     	 $loop_sub_number = 1; 
	      } 
	      else 
	      {
	      	 // 循环次数
	     	 $loop_number = 1;
	     	 // 本次循环需出库数
	     	 if($order_goods_item['goods_number'] >  $real_out_goods_numbers[$product_id]) {
	     	    $loop_sub_number = $real_out_goods_numbers[$product_id];
	     	 } else {
	     	    $loop_sub_number = $order_goods_item['goods_number'];
	     	 }
	      }
     
	      for($key_num=0;$key_num<$loop_number;$key_num++) {
	    		
	    	Qlog::log('count pick num product_id:'.$product_id.' $loop_sub_number:'.$loop_sub_number.' real_out_number:'.$real_out_goods_numbers[$product_id]);
	    	
	    	// 如果数量出够了，则返回
	        if($real_out_goods_numbers[$product_id] <= 0) {
	        	break;
	        }
	      	try {
	                            
	            // 如果输入的是商品编码, 则分析商品编码，并从库存中为其随机挑一个未出库的商品串号
	            if($inventoryItemType == 'SERIALIZED') {
	            	if(!empty($serial_numbers[$product_id])) {
	            		$serial_number_str = implode(',',$serial_numbers[$product_id]);
	            		Qlog::log('out_pick:serial_number is not null product_id:'.$product_id.' serial_number_str:'.$serial_number_str);
	            		$cond = " AND ii.serial_number ".db_create_in($serial_number_str);
	            	} else {
	            		Qlog::log('out_pick:serial_number is null product_id:'.$product_id);
	            		continue;
	            	}
	            } 
	            else 
	            {
	            	$cond = " AND pm.ecs_goods_id = '{$order_goods_item['goods_id']}' AND pm.ecs_style_id = '{$order_goods_item['style_id']}' ";
	            }
	 
	            $cond .= $order_goods_item['status_id']=='INV_STTS_AVAILABLE' ? " AND ii.status_id = 'INV_STTS_AVAILABLE'" : " AND ii.status_id = 'INV_STTS_USED'";
	
	            // 查询该串号的入库记录（入库但还没有出库的） 	
	            $sql = "
	            	SELECT 
	                        pm.ecs_goods_id as goods_id, pm.ecs_style_id as style_id, ii.serial_number,  
	                        ii.inventory_item_acct_type_id as order_type, ii.provider_id, ii.status_id,ii.facility_id
	            	FROM romeo.inventory_item ii 
	                LEFT JOIN romeo.product_mapping pm ON ii.product_id = pm.product_id
	            	WHERE ii.facility_id = '{$order_item['facility_id']}' {$cond} and ii.quantity_on_hand_total > 0
	        	    LIMIT 1
	            ";
	            Qlog::log('out_pick: search_stock sql:'.$sql);
	            $barcode = encode_goods_id($order_goods_item['goods_id'], $order_goods_item['style_id']);
	            
	            // 获取出库单号时候避免取重加锁
	            $lock_name = "out_sn_{$barcode}";
	            $lock_file_name = get_file_lock_path($lock_name, 'delivery');
	            $lock_file_point = fopen($lock_file_name, "w+");
	            $max_sleep = 5;
	            $has_outsn_lock = false;
	            $wouldblock = false;
	            while ($max_sleep > 0) {
	                if (flock($lock_file_point, LOCK_EX|LOCK_NB, $wouldblock)) {
	                    touch($lock_file_name);
	                    $has_outsn_lock = true;
	                    $ine = $db->getRow($sql);
	                    break;
	                }
	                sleep(1);
	                $max_sleep--;
	            }
	            if (!$has_outsn_lock) {
	                $result['outsn_lock_exception'][] = $barcode;
	            }
	            
	            // 找不到可以用于该商品的串号
	            if (!$ine) {
	                $result['unmatched_notfound'][] = $barcode;
	                // 释放锁，并关闭锁文件
	                flock($lock_file_point, LOCK_UN);
	                fclose($lock_file_point);
	                continue;
	            }
	            
	            
	            // 出库
	            $formStatusId = $ine['status_id'];
	                
	            Qlog::log('out_pick:createTransferInventoryTransaction:start');
	
	            // 新库存出库
	            $inventoryTransactionResult = createTransferInventoryTransaction(
	                'ITT_SALE', array('goods_id'=>$ine['goods_id'], 'style_id'=>$ine['style_id']), $loop_sub_number,
	                $ine['serial_number'], $ine['order_type'], null, $order_item['order_id'],
	                $formStatusId, 'INV_STTS_DELIVER', $order_goods_item['rec_id'],
	                $order_item['facility_id'], $order_item['facility_id']
	            );
	            if (!$inventoryTransactionResult) {
	            	$result['transfer_exception'][] = $barcode;
	            }
	      	} catch (Exception $e) {
	      		QLog::log('out_pick exception:'.$e);
	      		$result['error'] = 'createTransferInventoryTransaction exception:'.$e->getMessage();
	      	}
	      	
	      	$real_out_goods_numbers[$product_id] = (int)$real_out_goods_numbers[$product_id] - $loop_sub_number;
	      	Qlog::log('count pick num2 product_id:'.$product_id.' real_out_number:'.$real_out_goods_numbers[$product_id]);
	      	
	        // 释放锁，并关闭锁文件
	        flock($lock_file_point, LOCK_UN);
	        fclose($lock_file_point);
	      }
       }
    }
    
    return $result;
}	