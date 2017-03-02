<?php

/**
 * PARTY相关服务
 *
 * @author yxiang@oukoo.com
 * @copyright 2010 ouku.com
 */

include_once('cls_HashMap.php');
include_once('cls_GenericValue.php');
include_once('lib_soap.php');
include_once('lib_cache.php');


// 定义常用的PARTY
defined('PARTY_ALL') or define('PARTY_ALL', 65535);


/**
 * 检查访问请求对象是否能访问控制对象
 * 
 * 比如用户拥有的权限为PARTY_OUKU, 而访问所需的权限为PARTY_ALL, 那么是不能访问的：
 * <code>
 *   party_check(PARTY_OUKU, PARTY_ALL) // 返回FALSE
 *   party_check(PARTY_OUKU, PARTY_OUKU_MOBILE) // 返回TRUE
 * </code>
 * 
 * @param int $aro_party_id  访问请求对象所属的party
 * @param int $aco_party_id  访问控制对象所属的party
 *  
 * @return boolean
 */
function party_check($aro_party_id, $aco_party_id) {
	if ($aro_party_id == $aco_party_id) { return true; }
    $party_list = party_children_list($aro_party_id);
    if (is_array($party_list) && $aco_party_id != null ){
    	 return array_key_exists($aco_party_id, $party_list);
    }
    else {
    	 return false;
    }
}

/**
 * 检查访问请求对象是否有明确的访问请求
 * 比如：如果访问请求对象即属于欧酷又属于乐其，那视为该访问请求对象的访问请求是不明确的
 *
 * @param int $party_id 访问请求对象所属的party
 *  
 * @return boolean
 */
function party_explicit($party_id) {
    return is_scalar($party_id) && ($party_list = party_get_all_list()) && isset($party_list[$party_id]) && (strcasecmp($party_list[$party_id]->isLeaf, 'Y')==0); 
}

/**
 * 构造Party的可选下拉列表
 *
 * @param array $party_id
 */
function party_options_list($party_id = NULL,$forceUpdate=false) {
    $party_list = party_get_all_list($forceUpdate);
    $list = array();
    if ($party_list) {
    	// 不指定party_id则返回所有的
    	if (is_null($party_id)) {
	        foreach ($party_list as $obj) {
	            $list[$obj->partyId] = str_repeat('--',$obj->depth).' '.$obj->name;
	        }
    	}
    	// 指定了party_id
    	else {
            if (!is_array($party_id)) {
            	$party_id = array_filter(array_map('trim', explode(',', $party_id)), 'strlen');
            }
            foreach ($party_list as $obj) {
            	if (in_array($obj->partyId, $party_id)) {
                    $list[$obj->partyId] = str_repeat('--',$obj->depth).' '.$obj->name;            		
            	}
            }
    	}
    }
    return $list;
}

/**
 * 返回指定party下级的party列表
 *
 * @param int $party_id  如果为空，返回整个party树
 * @return array
 */
function party_children_list($party_id = NULL, $recursive = true) {
    static $tree, $refs = array();

    if (!isset($tree)) {
        $party_list = party_get_all_list();
        foreach ($party_list as $offset => $obj) {
            $party_list[$offset]->childrens = array();
            $refs[$obj->partyId] =& $party_list[$offset];
        }

        $tree = array();
        foreach ($party_list as $offset => $obj) {
            $parent_id = $obj->parentPartyId;
            if ($parent_id && isset($refs[$parent_id])) {
                $parent =& $refs[$parent_id];
                $parent->childrens[] =& $party_list[$offset];               
            } else {
                $tree[] =& $party_list[$offset];
            }
        }
    }

    if (!is_null($party_id) && isset($refs[$party_id])) {
    	$list = array();
    	if ($recursive) {
	        foreach (_party_tree_to_array($refs[$party_id]) as $obj) {
	            $list[$obj->partyId] = $obj->name;
	        }
    	} else {
    	    if (isset($refs[$party_id]->childrens) && is_array($refs[$party_id]->childrens)){
		        foreach ($refs[$party_id]->childrens as $obj) {
                    $list[$obj->partyId] = $obj->name;
		        }
            }
    	}
        return $list;
    }
    
    return $tree;
}

/**
 * 返回指定party下级的party列表（新）
 * @author zjli
 *
 * @param int $party_id  如果为空，返回整个party树
 * @return array
 */
function party_children_list_new($party_id = NULL, $recursive = true) {
    static $tree, $refs = array();

    if (!isset($tree)) {
        $party_list = party_get_all_list();
        foreach ($party_list as $offset => $obj) {
            $party_list[$offset]->childrens = array();
            $refs[$obj->partyId] =& $party_list[$offset];
        }

        $tree = array();
        foreach ($party_list as $offset => $obj) {
            $parent_id = $obj->parentPartyId;
            if ($parent_id && isset($refs[$parent_id])) {
                $parent =& $refs[$parent_id];
                $parent->childrens[] =& $party_list[$offset];               
            } else {
                $tree[] =& $party_list[$offset];
            }
        }
    }

    if (!is_null($party_id) && isset($refs[$party_id])) {
    	$list = array();
    	if ($recursive) {
	        foreach (_party_tree_to_array($refs[$party_id]) as $obj) {
            	if($obj->systemMode == 2){  // 只获取属于新系统的组织
            		$list[$obj->partyId] = $obj->name;
            	}
	        }
    	} else {
    	    if (isset($refs[$party_id]->childrens) && is_array($refs[$party_id]->childrens)){
		        foreach ($refs[$party_id]->childrens as $obj) {
		        	if($obj->systemMode == 2){  // 只获取属于新系统的组织
		        		$list[$obj->partyId] = $obj->name;
		        	}
		        }
            }
    	}
        return $list;
    }
    
    return $tree;
}

/**
 * 将party树展开成list
 *
 * @param object $tree
 * @return array
 */
function _party_tree_to_array($tree) {
    $ret = array();
    if (!empty($tree->childrens) && is_array($tree->childrens)){
        $childrens = $tree->childrens;
        $ret[] = $tree;
        foreach ($childrens as $node) {
            $ret = array_merge($ret, _party_tree_to_array($node));
        }
    }
    else {
        $ret[] = $tree;
    }
    return $ret;
}

/**
 * 返回party列表
 *
 * @param boolean $tree
 *   是否做为树形返回
 */
function party_get_all_list($forceUpdate=false) {
    static $party_list;

    if ($forceUpdate || !isset($party_list)) {
        $cache_id = 'romeoApi:party:partyList';
        $cache = RomeoApi_Cache::instance();
        if ($forceUpdate || ($party_list = $cache->get($cache_id)) === false) {
            $party_list = array();
            try {
                $handle = soap_get_client('UserService');
                $response = $handle->getPartyTree();
                if ($response && $response->return) {
                    $party_list = _party_parse_tree($response->return);
                }
                $cache->set($cache_id, $party_list);
            } catch (SoapFault $e) {
                trigger_error("SOAP调用失败: (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})", E_USER_WARNING);
            }
        }
    }
    
    return $party_list;
}

/**
 * 解析party树
 *
 * @param object $tree
 * @return array
 */
function _party_parse_tree($tree, $depth = 0) {
	$ret = array();
    if (isset($tree->partyList->Party)) {
        $childrens = wrap_object_to_array($tree->partyList->Party);
        unset($tree->partyList);
        $tree->depth = $depth++;
        $ret[$tree->partyId] = $tree;
        foreach ($childrens as $node) {
            $ret += _party_parse_tree($node, $depth);
        }
    }
    else if (is_object($tree)) {
        unset($tree->partyList);
        $tree->depth = $depth;
        $ret[$tree->partyId] = $tree;
    }
    return $ret;
}

/**
 * 创建party
 * 
 * @param array $row
 *   - parent_party_id
 *   - name
 *   - description
 * 
 * @return boolean
 */
function party_insert($row, & $failed = array()) {
    if (empty($row['parent_party_id'])) {
        $failed[] = '没有指定父级Party';
        return false;  
    }
    
    if (empty($row['name'])) {
        $failed[] = '没有Party名';
        return false;  
    }
     
    try {
        $party = new stdClass();
        $party->parentPartyId = $row['parent_party_id'];
        $party->name = $row['name'];
        $party->description = $row['description'];
        $handle = soap_get_client('UserService');
        $handle->createParty(array('party'=>$party));
        RomeoApi_Cache::instance()->delete('romeoApi:party:partyList');
        return true;
    }
    catch (SoapFault $e) {
        $failed[] = "SOAP调用失败: (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})";
    }

    return false;
}

/**
 * 用户party相关
 * @+{
 */

/**
 * 更新用户的party_id
 *
 * @param int $user_id 用户的id
 * @param array $party_list
 * 
 * @return boolean
 */
function party_save_user_party($user_id, $party_list) {
    try {
        foreach ($party_list as $party_id) {
            $param[] = array('userId'=>$user_id, 'partyId'=>$party_id);
        }
        $handle = soap_get_client('UserService');
        $handle->createUserParty(array('userPartyList' => $param));
        RomeoApi_Cache::instance()->delete('party_get_user_party_new-'.$user_id);
        return true;
    } catch (SoapFault $e) {}
	
    return false;
}

/**
 * 取得用户所有可用的PARTY列表， 比如用户有OUKU的权限，也会返回OUKU下面的
 *
 * @param int $user_id 用户的ID
 * @return array 失败返回false
 */
function party_get_user_party($user_id) {
    static $user_party_list;
	
    if(!isset($user_party_list[$user_id])){
        $cache=RomeoApi_Cache::instance();
        $cid=__FUNCTION__.'-'.$user_id;
        if(($user_party_list[$user_id]=$cache->get($cid))===false){
            $user_party_list[$user_id]=array();
            try {
                $handle = soap_get_client('UserService');
                $response = $handle->getUserParty(array('userId' => $user_id));
                if ($response && $response->return->UserParty) {
                    $list = wrap_object_to_array($response->return->UserParty);
                    foreach ($list as $obj) {
                        array_push($user_party_list[$user_id], $obj->partyId);
                    }
                }
                $cache->set($cid,$user_party_list[$user_id]);
            } catch (SoapFault $e) {
                trigger_error("取得用户的Party权限错误！SOAP调用失败: (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})", E_USER_ERROR);
            }
        }
    }
	
    return $user_party_list[$user_id];
}

/**
 * 取得用户所有可用的PARTY列表(适用于废完老库存后的新系统，用于限制组织数)
 *
 * @param int $user_id 用户的ID
 * @return array 失败返回false
 */
function party_get_user_party_new($user_id) {
    static $user_party_list;
    global $db;
	
    if(!isset($user_party_list[$user_id])){
        $cache=RomeoApi_Cache::instance();
        $cid=__FUNCTION__.'-'.$user_id;
        if(($user_party_list[$user_id]=$cache->get($cid))===false){
            $user_party_list[$user_id]=array();
            $sql = "SELECT a.party_id from (SELECT distinct(p.party_id) FROM ecshop.ecs_admin_user eau
            		INNER JOIN romeo.user_party up ON up.user_id = eau.user_id AND up.status = 'ok'
            		LEFT JOIN romeo.party p ON (p.party_id = up.party_id OR p.parent_party_id = up.party_id) AND (p.system_mode = '2' OR p.system_mode = '3')
            		WHERE eau.user_id = '{$user_id}') as a where a.party_id is not null
                    order by party_id desc";
            $user_party_list[$user_id] = $db->getCol($sql);
            $cache->set($cid,$user_party_list[$user_id]);
        }
    }
	
    return $user_party_list[$user_id];
}

/**
 * 楼上的party_get_user_party_new只有一层向下兼容，让我们伸出触手
 * @param int $user_id 用户的ID
 * @return array 失败返回false
 */
function party_get_user_party_by_sinri($user_id) {
    static $user_party_list;
    global $db;

    $user_id=intval($user_id);
    
    if(!isset($user_party_list[$user_id])){
        $cache=RomeoApi_Cache::instance();
        $cid=__FUNCTION__.'-'.$user_id;
        if(($user_party_list[$user_id]=$cache->get($cid))===false){
            $user_party_list[$user_id]=array();

            $list=array();
            
            $sql = "SELECT
                up.party_id
            FROM
                romeo.user_party up
            LEFT JOIN romeo.party p ON (p.party_id = up.party_id)
            AND (
                p.system_mode = '2'
                OR p.system_mode = '3'
            )
            WHERE
                up. STATUS = 'ok'
            AND up.USER_ID = {$user_id}
            AND p.`STATUS` = 'ok'
            ";
            $list_sub = $db->getCol($sql);

            foreach ($list_sub as $item) {
                $list[$item]=$item;
            }

            $i=0;
            while($i<count($list)){
                $tlist=array_values($list);
                $sql="SELECT
                    p.PARTY_ID
                FROM
                    romeo.party p
                WHERE
                    p.PARENT_PARTY_ID = '{$tlist[$i]}'
                AND (
                    p.system_mode = '2'
                    OR p.system_mode = '3'
                )
                AND p.`STATUS` = 'ok'
                ";
                $list_sub=$db->getCol($sql);

                foreach ($list_sub as $item) {
                    $list[$item]=$item;
                }

                $i++;
            }
            
            $user_party_list[$user_id]=array_values($list);
            $cache->set($cid,$user_party_list[$user_id]);
        }
    }
    
    return $user_party_list[$user_id];
}

/**
 * 取得用户默认的PARTY
 *
 * @param int $user_id 用户ID
 * @return itn
 */
function party_get_user_default_party($user_id) {
    $user_party_list = party_get_user_party_new($user_id);
    if (is_array($user_party_list)) {
        return reset($user_party_list);
    }
}
