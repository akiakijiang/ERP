<?php

/**
 * 组织操作
 */
class PartyServices
{
	const PARTY_ALL='65535';
	
    private static $_partyList;  // has cache
    private static $_partyTree;  // has cache
    private static $_partyTreeRef;
    
	/**
	 * 检查访问请求对象是否能访问控制对象
	 * 
	 * 比如用户拥有的权限为PARTY_OUKU, 而访问所需的权限为PARTY_ALL, 那么是不能访问的：
	 * <code>
	 *   PartyServices::check(PARTY_OUKU, PARTY_ALL) // 返回FALSE
	 *   PartyServices::check(PARTY_OUKU, PARTY_OUKU_MOBILE) // 返回TRUE
	 * </code>
	 * 
	 * @param int $aroPartyId  访问请求对象所属的party
	 * @param int $acoPartyId  访问控制对象所属的party
	 *  
	 * @return boolean
	 */
	function check($aroPartyId, $acoPartyId)
	{
        if($aroPartyId==$acoPartyId)
            return true;
        $children=self::getChildren($aroPartyId);
        return isset($children[$acoPartyId]) || array_key_exists($acoPartyId,$children);
	}

    /**
     * 判断给定的组织是否明确
     *
     * @return boolean
     */
    public static function isExplicit($partyId)
    {
        return is_scalar($partyId) && ($partyList=self::getList()) && isset($partyList[$partyId]) && (strcasecmp($partyList[$partyId]->isLeaf, 'Y')==0);
    }
    
    /**
     * 
     */
    public static function getByPartyId($partyId)
    {
        $partyList=self::getList();
        return $partyList[$partyId];
    }
    
    /**
     * 清空缓存
     */
    public static function clearCache()
    {
        if(($cache=Yii::app()->getCache())!==null)
        {
        	$cache->delete('PartyServices::getList');
        	$cache->delete('PartyServices::getTree');
        }
    }
    
    /**
     * 返回所有组织
     * 
     * @return array
     */
    public static function getList($force=false)
    {
        if($force || self::$_partyList===null) 
        {
            if(($cache=Yii::app()->getCache())!==null && (self::$_partyList=$cache->get(__METHOD__))!==false) 
            {
                return self::$_partyList;
            }
            else
            {
                self::$_partyList=array();
                $response=Yii::app()->getComponent('romeo')->UserService->getPartyList();
                if($response && isset($response->return->Party))
                {
                    if(is_object($response->return->Party))
                        self::$_partyList[$response->return->Party->partyId]=$response->return->Party;
                    else
                    {
                        foreach($response->return->Party as $party)
                            self::$_partyList[$party->partyId]=$party;
                    }
                    unset($response);
                }
                if($cache!=null)
                    $cache->set(__METHOD__, self::$_partyList, 43200);
            }
        }
        return self::$_partyList;
    }
    
    /**
     * 返回树状的组织
     *
     * @param boolean $force
     * @return array
     */
    public static function getTree($force=false)
    {
        if(!$force && self::$_partyTree!==null)
            return self::$_partyTree;
        else 
        {
            if(($cache=Yii::app()->getCache())!==null && ($result=$cache->get(__METHOD__))!==false) 
            {
                // cache hit
            }
            else
            {
                $response=Yii::app()->getComponent('romeo')->UserService->getPartyTree();
                if($response && isset($response->return))
                   $result=$response->return;
                
                if (($cache=Yii::app()->getCache())!==null)
                    $cache->set(__METHOD__, $result, 43200);
            }
            return self::$_partyTree=self::parseTree($result);
        }
    }
    
    /**
     * 返回指定party下级的party列表
     *
     * @param int $partyId  如果为空，返回整个party树
     * @return array
     */
    function getChildren($partyId=null,$recursive=true) 
    {
        $partyTree=self::getTree();

        if($partyId!==null && isset(self::$_partyTreeRef[$partyId]))
            $partyTree=self::$_partyTreeRef[$partyId];
        elseif($partyId!==null)
            throw new CException('the partyId not applicable!');
    
        $result=array();
        if($recursive) 
        {
            foreach(self::treeToArray($partyTree) as $party)
                $result[$party->partyId]=$party->name;
        }
        else
        {
            if(isset($partyTree->children) && is_array($partyTree->children))
            {
                foreach($partyTree->children as $party)
                    $result[$party->partyId]=$party->name;
            }
        }
        return $result;
    }
    
    /**
     * @return object
     */
    protected static function parseTree($root,$depth=0)
    {
        self::$_partyTreeRef[$root->partyId]=$root;
        if(isset($root->partyList->Party))  // has children 
        {
            if(is_object($root->partyList->Party))
                $children=array($root->partyList->Party);
            else
                $children=$root->partyList->Party;
            unset($root->partyList);
            $root->depth=$depth++;
            foreach($children as $node)
                $root->children[] = self::parseTree($node, $depth);
        }
        else 
        {
            unset($root->partyList);
            $root->depth=$depth;
        }
        return $root;
    }
    
    /**
     * 将树格式化为数组
     *
     * @param object $tree
     * @return array
     */
    protected static function treeToArray($tree) 
    {
        $result=array();
        if(!empty($tree->children) && is_array($tree->children))
        {
            $children=$tree->children;
            $result[]=$tree;
            foreach($children as $node)
                $result=array_merge($result,self::treeToArray($node));
        }
        else 
            $result[]=$tree;
        return $result;
    }
}