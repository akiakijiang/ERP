<?php

/**
 * 库存服务
 */
class FacilityServices
{
	private static $_containerList;     // has cache
	private static $_facilityTypeList;  // has cache
	private static $_facilityList;      // has cache

    /**
     * 清空缓存
     */
    public static function clearCache()
    {
        if(($cache=Yii::app()->getCache())!==null)
        {
        	$cache->delete('FacilityServices::getFacilityList');
        	$cache->delete('FacilityServices::getContainerListByFacilityId');
        	$cache->delete('FacilityServices::getFacilityTypeList');
        }
    }
    
	/**
	 * 取得某个Party下的可用设施列表
	 * 
	 * @param mixed $partyId
	 * @return array
	 */
	public static function getFacilityByPartyId($partyId)
	{
		if(!is_array($partyId))
			$partyId=preg_split('/\s*,\s*/',trim($partyId),-1,PREG_SPLIT_NO_EMPTY);
    		
		$facilities=self::getFacilityList();

		$result=array();
		foreach($partyId as $pId)
		{
	    	foreach($facilities as $facility)
	    	{
		        if (PartyServices::check($pId,PartyServices::PARTY_ALL) || PartyServices::check($facility->ownerPartyId,$pId))
	                $result[$facility->facilityId]=$facility;
		    }
	    }
	    return $result;	
    }
    
    /**
     * 取得所有设施列表
     *
     * @return array
     */
    public static function getFacilityList()
    {
    	if(self::$_facilityList===null)
    	{
    		if(($cache=Yii::app()->getCache())===null || (self::$_facilityList=$cache->get(__METHOD__))===false)
    		{
    			$response=Yii::app()->getComponent('romeo')->InventoryService->getFacilityList();
    			if(isset($response->return->Facility))
    			{
    				if(is_object($response->return->Facility))
    					self::$_facilityList=array($response->return->Facility);
    				else if(is_array($response->return->Facility))
    					self::$_facilityList=$response->return->Facility;
    				unset($response);
    			}
    			else
    				self::$_facilityList=array();
    				
    			if($cache!==null)
    				$cache->set(__METHOD__,self::$_facilityList,43200);
    		}
    	}

    	return self::$_facilityList;
    }
    
    /**
     * 取得某个仓库设施的默认容器
     *
     * @param string $facilityId
     */
    public static function getDefaultContainerByFacilityId($facilityId)
    {
        $containerList=self::getContainerListByFacilityId($facilityId);
        if(count($containerList)>0)
            return reset($containerList);
        else
            return null;
    }
    
    /**
     * 根据仓库设施ID取得容器列表
     *
     * @param string $facilityId
     * @return array
     */
    public static function getContainerListByFacilityId($facilityId=null)
    {
        if(self::$_containerList===null)
        {
            if(($cache=Yii::app()->getCache())===null || (self::$_containerList=$cache->get(__METHOD__))===false)
            {
                $response=Yii::app()->getComponent('romeo')->InventoryService->getContainerList();
                if(isset($response->return->Container))
                {
                    if(is_object($response->return->Container))
                        self::$_containerList[]=$response->return->Container;  // have one, is a object
                    else if(is_array($response->return->Container))
                        self::$_containerList=$response->return->Container;    // have many, is a array
                    unset($response);
                }
                else
                    self::$_containerList=array();
                if($cache!==null)
                    $cache->set(__METHOD__,self::$_containerList,43200);
            }
        }
        
        if($facilityId===null)
            return self::$_containerList;
        else
        {
            $return=array();
            foreach(self::$_containerList as $container)
            {
                if($container->facilityId==$facilityId)
                    $result[]=$container;
            }
            return $result;
        }  
    }
    
    /**
     * 取得设施类型列表
     *
     * @return array
     */
    public static function getFacilityTypeList()
    {
        if(self::$_facilityTypeList===null)
        {
            if(($cache=Yii::app()->getCache())===null || (self::$_facilityTypeList=$cache->get(__METHOD__)===false))
            {
                $response=Yii::app()->getComponent('romeo')->InventoryService->getFacilityTypeList();
                if(isset($response->return->FacilityType))
                {
                    if(is_object($response->return->FacilityType))
                        self::$_facilityTypeList[]=$response->return->FacilityType;
                    elseif(is_array($response->return->FacilityType))
                        self::$_facilityTypeList=$response->return->FacilityType;
                    else
                        self::$_facilityTypeList=array();
                    unset($response);
                }
                else
                    self::$_facilityTypeList=array();
                if($cache!==null)
                    $cache->set(__METHOD__,self::$_facilityTypeList,43200);
            }
        }
        return self::$_facilityTypeList;
    }
}