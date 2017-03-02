<?php

/**
 * 库存Services
 * @author yxiang@leqee.com
 */
class InventoryServices
{  
    /**
     * 取得指定产品的某个库存状态下的可用库存
     * 
     * @param string $statusId
     *   库存状态,可用的库存状态有: 
     *   - INV_STTS_AVAILABLE  可用库
     *   - INV_STTS_DEFECTIVE  次品库
     *   - INV_STTS_DELIVER    发货库
     *   - INV_STTS_INSPECT    检测库
     *   - INV_STTS_USED       二手库
     * @param array $facilityId
     *   仓库
     * @param array $productIdList
     *   产品
     * @return array  返回的结果并不会按产品合并，不同的串号会有一条记录
     */
    public static function getInventoryAvailable($statusId,$facilities=null,$products=null)
    {
    	if($facilities!==null && is_string($facilities))
            $facilities=preg_split('/\s*,\s*/',$facilities,-1,PREG_SPLIT_NO_EMPTY);
        if($products!==null && is_string($products))
            $products=preg_split('/\s*,\s*/',$products,-1,PREG_SPLIT_NO_EMPTY);
        $request=array(
            'statusId'=>$statusId,
            'facilityIdList'=>$facilities,
            'productIdList'=>$products,
        );
        $response=Yii::app()->getComponent('romeo')->InventoryService->getInventoryAvailableByStatus($request);
        $result=new InventoryServicesContext($response->return);
        if($result->get('status')!=='OK')
            throw new Exception('查询库存异常，请检查参数');

        $inventoryItemList=array();
        $arrayList=$result->get('itemList','array');
        if(isset($arrayList->anyType))  // 否则就是没有数据
        {
            if(is_object($arrayList->anyType))  // 如果只有一条数据，需要包装
                $arrayList->anyType=array($arrayList->anyType);
            foreach($arrayList->anyType as $context) 
            {
                $item=new InventoryServicesContext($context);
                $inventoryItemList[]=array(
                    'goodsId'=>$item->get('goodsId'),
                    'styleId'=>$item->get('styleId'),
                    'qohTotal'=>$item->get('qohTotal'),
                    'unitCost'=>$item->get('unitCost'),
                    'acctType'=>$item->get('acctType'),
                    'productName'=>$item->get('productName'),
                    'productId'=>$item->get('productId'),
                    'serialNumber'=>$item->get('serialNumber'),
                );
                unset($item);
            }
        }
        
        return $inventoryItemList;
    }
    
    /**
     * 取得库存总表记录，并按产品分组
     *
     * @param string $statusId
     * @param array $facilities
     * 
     * @return array
     */
	public static function getInventorySummaryAssocByProduct($statusId,$facilityId=null,$productId=null,$containerId=null)
	{
		$assoc=array();
    	
		if($statusId!==null && is_string($statusId))
			$statusId=preg_split('/\s*,\s*/',$statusId,-1,PREG_SPLIT_NO_EMPTY);
		if($productId!==null && is_string($productId))
			$productId=preg_split('/\s*,\s*/',$productId,-1,PREG_SPLIT_NO_EMPTY);
		if($facilityId!==null && is_string($facilityId))
			$facilityId=preg_split('/\s*,\s*/',$facilityId,-1,PREG_SPLIT_NO_EMPTY);
		if($containerId!==null && is_string($containerId))
			$containerId=preg_split('/\s*,\s*/',$containerId,-1,PREG_SPLIT_NO_EMPTY);
		$request=array(
			'statusId'=>$statusId,
			'productId'=>$productId,
			'facilityId'=>$facilityId,
			'containerId'=>$containerId,
		);
        // 内存不够，需要调大
        ini_set('memory_limit', '64M');
		$response=Yii::app()->getComponent('romeo')->InventoryService->getInventorySummaryByCondition($request);
		if($response && isset($response->return) && isset($response->return->InventorySummary))
		{
			if(is_object($response->return->InventorySummary))
				$assoc[$response->return->InventorySummary->productId]=array($response->return->InventorySummary);
			else 
			{
				foreach($response->return->InventorySummary as $inventorySummary)
					$assoc[$inventorySummary->productId][]=$inventorySummary;
			}
		}

		return $assoc;
	}

    /**
     * 取得按产品分组的库存
     *
     */
    public static function getInventoryAvailableAssocByProduct($statusId,$facilityId=null,$products=null)
    {
    	
    }

	/**
	 * 库存接收
	 *
	 * @return boolean
	 */
	public static function createInventoryAccept($inventoryTransactionTypeId,$productId,$amount,$serialNo,$acctType,$orderId,$fromStatusId,$toStatusId,$unitCost=null,$orderGoodsId,$toFacilityId)
	{
        $inventoryItemType=self::getInventoryItemType($productId);
        $userName=Yii::app()->getUser()->getName();
        $toContainer=FacilityServices::getDefaultContainerByFacilityId($toFacilityId);
        if($toContainer===null)
        {
        	Yii::log("入库失败，找不到接受仓库的容器, facilityId:$toFacilityId",CLogger::LEVEL_ERROR,'application.product.inventory');
        	return false;
        }

        try 
        {
	        $context=new InventoryServicesContext;
	        $context->put('inventoryTransactionTypeId',$inventoryTransactionTypeId);
	        $context->put('productId',$productId);
	        $context->put('amount',$amount,'number');
	        $context->put('inventoryItemType',$inventoryItemType);
	        $context->put('serialNo',$serialNo);
	        $context->put('orderId',$orderId);
	        $context->put('fromStatusId',$fromStatusId);
	        $context->put('toStatusId',$toStatusId);
	        $context->put('userName',$userName);
	        $context->put('acctType',$acctType);
	        $context->put('unitCost',$unitCost);
	        $context->put('orderGoodsId',$orderGoodsId);
	        $context->put('toFacilityId',$toFacilityId);
	        $context->put('toContainerId',$toContainer->containerId);
	        $param=$context->getRequestParam();
            $response=Yii::app()->getComponent('romeo')->InventoryService->createAcceptInventoryTransaction(array('arg0'=>$param));
            $result=new InventoryServicesContext($response->return);
            $status=$result->get('status');
        }
        catch (Exception $e)
        {
        	Yii::log("入库失败, ".$e->getMessage(),CLogger::LEVEL_ERROR,'application.product.inventory');
        	$status='Exception';
        }
        
        $args=func_get_args();
        return $status==='OK';
	}
	
    /**
     * 库存移动
     * 
     * @return boolean
     */
    public static function createInventoryTransfer($inventoryTransactionTypeId, $productId, $amount, $serialNo, $acctType, $fromOrderId, $toOrderId, $fromStatusId, $toStatusId, $orderGoodsId, $fromFacilityId, $toFacilityId)
    {
    	$userName=Yii::app()->getUser()->getName();
        $inventoryItemType=self::getInventoryItemType($productId);
        $fromContainer=FacilityServices::getDefaultContainerByFacilityId($fromFacilityId);
        if($fromContainer===null)
        {
            Yii::log("移库失败，找不到发货仓库的容器, facilityId:$fromFacilityId",CLogger::LEVEL_ERROR,'application.product.inventory');
            return false;
        }
        $toContainer=FacilityServices::getDefaultContainerByFacilityId($toFacilityId);
        if($toContainer===null)
        {
            Yii::log("移库失败，找不到接受仓库的容器, facilityId:$toFacilityId",CLogger::LEVEL_ERROR,'application.product.inventory');
            return false;
        }
        
        try
        {
	        $context=new InventoryServicesContext;
	        $context->put('inventoryTransactionTypeId',$inventoryTransactionTypeId);
	        $context->put('productId',$productId);
	        $context->put('amount',$amount,'number');
	        $context->put('inventoryItemType',$inventoryItemType);
	        $context->put('serialNo',$serialNo);
	        $context->put('fromOrderId',$fromOrderId);
	        $context->put('toOrderId',$toOrderId);
	        $context->put('fromStatusId',$fromStatusId);
	        $context->put('toStatusId',$toStatusId);
	        $context->put('userName',$userName);
	        $context->put('acctType',$acctType);
	        $context->put('orderGoodsId',$orderGoodsId);
	        $context->put('fromFacilityId',$fromFacilityId);
	        $context->put('fromContainerId',$fromContainer->containerId);
	        $context->put('toFacilityId',$toFacilityId);
	        $context->put('toContainerId',$toContainer->containerId);
	        $param=$context->getRequestParam();
	        $response=Yii::app()->getComponent('romeo')->InventoryService->createTransferInventoryTransaction(array('arg0'=>$param));
	        $result=new InventoryServicesContext($response->return);
	        $status=$result->get('status');
        }
        catch (Exception $e)
        {
        	$status='Exception';
        	Yii::log("移库失败, ".$e->getMessage(),CLogger::LEVEL_ERROR,'application.product.inventory');
        }
        
        $args=func_get_args();
        return $status==='OK';
    }
    
    /**
     * 库存出库
     * 
     * @return boolean
     */
    public static function createInventoryDeliver($inventoryTransactionTypeId,$productId,$amount,$serialNo,$acctType,$fromOrderId,$toOrderId,$fromStatusId, $toStatusId,$orderGoodsId,$fromFacilityId)
    {
    	$userName=Yii::app()->getUser()->getName();
    	$inventoryItemType=self::getInventoryItemType($productId);
    	$fromContainer=FacilityServices::getDefaultContainerByFacilityId($fromFacilityId);
        if($fromContainer===null)
        {
            Yii::log("出库失败，找不到发货仓库的容器, facilityId:$fromFacilityId",CLogger::LEVEL_ERROR,'application.product.inventory');
            return false;
        }
        
    	try 
    	{
	    	$context=new InventoryServicesContext;
	    	$context->put('inventoryTransactionTypeId',$inventoryTransactionTypeId);
	    	$context->put('productId',$productId);
	    	$context->put('amount',$amount,'number');
	    	$context->put('inventoryItemType',$inventoryItemType);
	    	$context->put('serialNo',$serialNo);
	    	$context->put('fromOrderId',$fromOrderId);
	    	$context->put('toOrderId',$toOrderId);
	    	$context->put('fromStatusId',$fromStatusId);
	    	$context->put('toStatusId',$toStatusId);
	    	$context->put('userName',$userName);
	    	$context->put('acctType',$acctType);
	    	$context->put('orderGoodsId',$orderGoodsId);
	    	$context->put('fromFacilityId',$fromFacilityId);
	    	$context->put('fromContainerId',$fromContainer->containerId);
	    	$param=$context->getRequestParam();
            $response=Yii::app()->getComponent('romeo')->InventoryService->createDeliverInventoryTransaction(array('arg0'=>$param));
            $result=new InventoryServicesContext($response->return);
            $status=$result->get('status');
    	}
    	catch (Exception $e) 
    	{
    		$status='Exception';
    		Yii::log('发货失败，'.$e->getMessage(),CLogger::LEVEL_ERROR,'application.product.inventory');
    	}
    	
    	$args=func_get_args();
        return $status==='OK';
    }
    
    /**
     * 创建库存差异
     */
    public static function createInventoryItemVariance()
    {
    	
    }
    
    public static function createPhysicalInventory()
    {
    	
    }
    
    /**
     * Enter description here...
     *
     * @param unknown_type $goodsId
     * 
     * TODO
     */
    public static function getInventoryItemType($productId)
    {
        return 'SERIALIZED';  
    }
    
    /**
     * 写Romeo库存执行log
     * 
     * @param $method 调用的方法
     * @param $input  传入的参数
     * @param $param  远程请求参数
     * @param $result 远程返回结果
     * @param $status 调用结果  OK|FAIL|EXCEPTION
     */
    protected static function log($method,$input,$param,$result,$status)
    {
        $message = array(
            // 调用方法
            'method'=>$method,
	        // 传入参数
	        'input'=>$input,  
	        // 远程请求参数
	        'param'=>$param,
            // 远程请求返回结果
            'result'=>$result,
	        // 调用结果
	        'status'=>$status,
	    );	    
	    $sql="INSERT INTO romeo_execute_log(script,message,message_type,datetime)VALUES(:script,:message,:status,NOW())";
	    $db=Yii::app()->getDb();
	    $db->setActive(true);
        $command=$db->createCommand($sql);
        $command->bindValue(':script',Yii::app()->getRequest()->getUrl(),PDO::PARAM_STR);
        $command->bindValue(':message',serialize($message),PDO::PARAM_STR);
        $command->bindValue(':status',$status,PDO::PARAM_STR);
	    $command->execute();
    }
}

/**
 * 包装库存服务返回结果
 */
class InventoryServicesContext
{
    private $_k=array();  // keys
    private $_o;          // object
	
    public function __construct($context=null)
    {
        if($context!==null && isset($context->entry))
            if(is_array($context->entry))
                $this->_o=$context;
            else
                $this->_o->entry=array($context->entry);
        else
            $this->_o->entry=array();

        if(!empty($this->_o->entry))
        {
            foreach($this->_o->entry as $key=>$e)
                $this->_k[$e->key]=&$this->_o->entry[$key];
        }
    }
	
    /**
     * 取得请求参数
     *
     * @return stdClass
     */
    public function getRequestParam()
    {
        return $this->_o;
    }

    /**
     * 检查是否存在对应的key
     *
     * @param string $key
     * @return mixed
     */
    public function contains($key)
    {
        return isset($this->_k[$key]);
    }
    
    /**
     * 获取一个值
     *
     * @param string $key
     * @param string $type  string|number|map|array, 设置为其他值时将判断返回一个有值的键
     * @return mixed
     */
	public function get($key,$type='null') 
	{
        if(isset($this->_k[$key]))
        {
            $e=$this->_k[$key];
            if($e->value===null||is_scalar($e->value)||$type===null)
                return $e->value;
            switch($type)
            {
                case 'string':
                    return $e->value->stringValue;
                case 'number':
                    return $e->value->numberValue;
                case 'array':
                    return $e->value->arrayList;
                case 'map':
                    return $e->value->hashmap;
                default:
                    if(isset($e->value->stringValue)&&$e->value->stringValue!==null)
                        return $e->value->stringValue;
                    else if(isset($e->value->numberValue)&&$e->value->numberValue!==null)
                        return $e->value->numberValue;
                    else if(isset($e->value->arrayList)&&$e->value->arrayList!==null)
                        return $e->value->arrayList;
                    else if(isset($e->value->hashmap)&&$e->value->hashmap!==null)
                        return $e->value->hashmap;
                    else
                        return null;
            }
        }
        return null;
	}
	
    /**
     * 添加一个值
     *
     * @param string $key
     * @param mixed  $value
     * @param string $type string|number|map|array
     */
    public function put($key,$val,$type='string')
    {
        $value=new stdClass;
        switch($type)
        {
            case 'number':
                $value->numberValue=$val;
                break;
            case 'map':
                $value->hashmap=$val;
                break;
            case 'array':
                $value->arrayList=$val;
                break;
            default:
                $value->stringValue=$val;
        }
		
        if(isset($this->_k[$key]) || array_key_exists($key,$this->_k))  // key exists
            $this->_k[$key]->value=$value;
        else
        {
            $e=new stdClass;
            $e->key=$key;
            $e->value=$value;
            
            $this->_k[$key]=$e;
            array_push($this->_o->entry,$this->_k[$key]);
        }
    }
}
