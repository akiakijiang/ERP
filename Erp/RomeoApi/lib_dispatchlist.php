<?php

/**
 * 工单soup调用相关库
 *
 * @author zwsun@leqee.com
 * @copyright 2011-3-30 12:56:34 jjshouse.com
 */

include_once('lib_soap.php');
include_once('cls_HashMap.php');

/**
 * 获得可以生成工单的候选
 * @param object $criteria 查询条件
 * @return array 含有可以生成工单的候选
 */
function searchDispatchCandidates($criteria) {
	try {
    $handle = soap_get_client("DispatchListService");
    $response = $handle->searchDispatchCandidates(array('criteria' => $criteria));
	} catch(Exception $e) {
		p("如果你看到此段文字请将下面这些信息复制给 erp@i9i8.com 谢谢！\n操作菜单：ERP->工单管理->未制作的工单\n异常信息：" . print_r($e, true));
	}
    return wrap_object_to_array($response->return->DispatchCandidate);
}

/**
 * 根据条件查询工单
 * @return array $criteria 含有可以生成工单的候选
 */
function searchDispatchLists($criteria) {
    $handle = soap_get_client("DispatchListService");
    $response = $handle->searchDispatchLists(array('criteria' => $criteria));
    return wrap_object_to_array($response->return->DispatchList);
}

/**
 * 取消工单
 * @param string $dispatchListId
 */
function cancelDispatchList ($dispatchListId) {
    $dispatchList = new stdClass();
    $dispatchList->dispatchListId = $dispatchListId;

    $response = soap_get_client("DispatchListService")->cancelDispatchList(
        array('dispatchList' => $dispatchList, 'userId' => $_SESSION['admin_name'])
        );
    
    updateSupplierDispatchList($dispatchListId, 'CANCELED');
}

/**
 * 创建工单
 * @param DispatchList $dispatchList
 * @return string 返回的工单id
 */
function createDispatchList ($dispatchList, $attributes) {
    $_attributes = new HashMap();
    foreach ($attributes as $key => $value) {
        $_attributes->put($key, $value);
    }
    $response = soap_get_client("DispatchListService")->createDispatchList(
        array('dispatchList' => $dispatchList, 'attributes' => $_attributes->getObject())
    );
    
    return $response->return;
}

/**
 * 创建工单属性
 */
function createDispatchListAttribute ($dispatchListId, $attributes){
	$_attributes = new HashMap();
	foreach ($attributes as $key => $value) {
        $_attributes->put($key, $value);
    }
    $response = soap_get_client("DispatchListService")->createDispatchListAttribute(
        array('dispatchListId' => $dispatchListId, 'attributes' => $_attributes->getObject())
    );
    
    return $response->return;
}
/**
 * 更新工单
 * @param object $dispatchList 工单实体
 * @param array $attributes 工单属性
 */
function updateDispatchList($dispatchList, $attributes) {
    $_attributes = new HashMap();
    foreach ($attributes as $key => $value) {
        $_attributes->put($key, $value);
    }
    soap_get_client("DispatchListService")->updateDispatchList(
        array('dispatchList' => $dispatchList, 'attributes' => $_attributes->getObject())
    );
}

/**
 * 确认dispatchList，工单完成，工单状态变成finished
 * @param object $dispatchList
 */
function confirmDispatchList ($dispatchList) {
    soap_get_client("DispatchListService")->confirmDispatchList(
        array('dispatchList' => $dispatchList, 'userId' => $_SESSION['admin_name'])
    );
    
    updateSupplierDispatchList($dispatchList->dispatchListId, 'FINISHED');
}

/**
 * 将工单状态转化为”重新审核“
 * @param object $dispatchList
 */
function reviseDispatchList ($dispatchList) {
    soap_get_client("DispatchListService")->reviseDispatchList(
        array('dispatchList' => $dispatchList, 
		'userId' => $_SESSION['admin_name'])
    );
}

/**
 * 将工单状态转化为”重新审核“
 * @param object $dispatchList
 */
function prepareDispatchList ($dispatchList) {
    soap_get_client("DispatchListService")->prepareDispatchList(
        array('dispatchList' => $dispatchList, 
		'userId' => $_SESSION['admin_name'])
    );
}

/**
 * 提交工单，先create，然后submit
 * @param object $dispatchList
 */
function submitDispatchList ($dispatchList) {
    soap_get_client("DispatchListService")->submitDispatchList(
        array('dispatchList' => $dispatchList, 
	          'userId' => $_SESSION['admin_name'])
    );
    
    // 工厂生产单
    createSupplierDispatchList($dispatchList->dispatchListId);
}

/**
 * 根据dispatchListId查询工单
 * @param string $dispathcListId
 * @return object $dispatchList
 */
function getDispatchList($dispatchListId) {
    $response = soap_get_client("DispatchListService")->getDispatchList(
        array('dispatchListId' => $dispatchListId)
    );
    
    return $response->return;
}

/**
 * 获得工单的属性 
 * @param string $dispatchListId 工单id
 * @return object $attributeMap 工单属性
 */
function getDispatchListAttributes ($dispatchListId) {
    $response = soap_get_client("DispatchListService")->getDispatchListAttributes(
        array('dispatchListId' => $dispatchListId)
    );
    $hashMap = new HashMap();
    $hashMap->setObject($response->return);
    return $hashMap->hasharray_mapping;
}

/**
 * 查询催货单
 * @param int $providerId 供应商id
 * @return array $dispatchLists 催货单列表
 */
function getPickupList ($partyId, $providerId = 0) {
    $providerId = intval($providerId);
    if ($providerId) {
        $response = soap_get_client("DispatchListService")->getPickupList(
            array('providerId' => $providerId, 'partyId' => $partyId)
        );
        
        $dispatchLists = wrap_object_to_array($response->return->DispatchList);
    } else {
        $response = soap_get_client("DispatchListService")->getAllPickupList(array('partyId' => $partyId));
        $dispatchLists = wrap_object_to_array($response->return->DispatchList);
    }
    
    return $dispatchLists;
}

function getNoticeList ($partyId, $providerId = 0) {
    $providerId = intval($providerId);
    if ($providerId) {
        $response = soap_get_client("DispatchListService")->getNoticeList(
            array('providerId' => $providerId, 'partyId' => $partyId)
        );
        
        $dispatchLists = wrap_object_to_array($response->return->DispatchList);
    } else {
        $response = soap_get_client("DispatchListService")->getAllNoticeList(array('partyId' => $partyId));
        $dispatchLists = wrap_object_to_array($response->return->DispatchList);
    }
    
    return $dispatchLists;
}

/**
 * attribute的key加上描述文字
 * 
 */
function jjshouseGoodsAttributeName() {
    static $map = array(
        '尺寸' => 'size',
        '胸围' => 'bust',
        '腰围' => 'waist',
        '臀围' => 'hips',
        '肩到胸' => '',
        '肩到腰' => '',
        '喉咙到下摆' => 'hollow_to_hem',
        '喉咙到地' => 'hollow_to_floor',
        '身高' => 'height',
        '颜色' => 'color',
        '腰带颜色' => 'sash_color',
        '腰带长' => 'sash_size',
        '材质' => 'textures',
        '罩杯' => 'cup_size',
        '上衣颜色' => 'bodice_color',
        '裙子颜色' => 'skirt_color',
        '刺绣颜色' => 'embroidery_color',
        '带披肩' => 'wrap',
    );
    
    return $map;
}

/**
 * 创建supplier dispatch list
 * @param string $dispatchListId
 * @return 是否更新成功
 */
function createSupplierDispatchList($dispatchListId) {
    global $db;
    
    $dispatchListId = intval($dispatchListId);
    $sql = "select dispatch_sn, provider_id
            from romeo.dispatch_list where dispatch_list_id = {$dispatchListId}";
    $dispatchList = $db->getRow($sql);
    
    if (!$dispatchList) {
        return false;
    }
    
    $sql = "insert into mps.supplier_dispatch_list ".
           " (`dispatch_sn`, `supplier_id`, `status`, `created_by_user_login`, `created_stamp`, `last_update_by_user_login`, `last_update_stamp`) ".
           " values ".
           " ( '{$dispatchList['dispatch_sn']}', '{$dispatchList['provider_id']}', 'CREATED', '{$_SESSION['admin_name']}', now(), '{$_SESSION['admin_name']}', now() )";
    return $db->query($sql);
}

/**
 * 更新 supplier dispatch list 状态
 * @param string $dispatchListId
 * @return 是否更新成功
 */
function updateSupplierDispatchList($dispatchListId, $targetStatus) {
    global $db;
    
    $sql = "select sd.* 
            from mps.supplier_dispatch_list sd
            inner join romeo.dispatch_list d on sd.dispatch_sn = d.dispatch_sn
            where d.dispatch_list_id = '{$dispatchListId}' ";
    $supplierDispatchList = $db->getRow($sql);
    
    if (!$supplierDispatchList) {
        return false;
    }
    
    // 根据erp这边的动作决定mps那边的动作
    if ($targetStatus == 'CANCELED') {
        if ($supplierDispatchList['status'] == 'CREATED') {
            $status = "CANCELED-CONFIRMED";
        } else if ($supplierDispatchList['status'] == 'CONFIRMED') {
            $status = "CANCELED";
        } else {
            $status = "DISCARDED";
        }
    } else if ($targetStatus == 'FINISHED') {
        $status = "FINISHED";
    }
    
    
    $sql = "update mps.supplier_dispatch_list ".
           " set status = '{$status}', last_update_by_user_login = '{$_SESSION['admin_name']}', last_update_stamp = now() ".
           " where supplier_dispatch_list_id = '{$supplierDispatchList['supplier_dispatch_list_id']}' limit 1 ";
    $db->query($sql);
    
    $sql = "INSERT INTO `mps`.`supplier_dispatch_list_status_history` " .
           " (`supplier_dispatch_list_id`, `status`, `created_stamp`, `created_by_user_login`) " .
           " VALUES ".
           " ('{$supplierDispatchList['supplier_dispatch_list_id']}', 'CANCELED', now(), '{$_SESSION['admin_name']}')";
    return $db->query($sql);
}

/**
 * 分析商品的材质
 * @param String $goodsName 商品名
 * @return String 提取的商品材质属性
 */
function parsejjshouseGoodsTexture($goodsName) {
    /*
    $attrs = array(
        'Chiffon' => '雪纺',
        'Taffeta' => '塔夫绸', 
        'Organza' => '欧根纱',
        'Satin' => '缎布',
        'Tulle' => '网',
        'Charmeuse' => '弹力缎',
        'Lace' => '蕾丝',
    );
    */
    $attrs = array(
        'Silk(\s*)Like(\s*)Satin' =>  '仿真丝',
        'Elastic(\s*)Woven(\s*)Satin' =>  '弹力缎',
        'Elastic(\s*)Satin' =>  '弹力缎',
        'Chiffon' => '雪纺',
        'Taffeta' => '塔夫绸', 
        'Organza' => '欧根纱',
        'Satin' => '缎布',
        
        'Tulle' => '网',
        'Charmeuse' => '弹力缎',
        'Lace' => '蕾丝',
    );
    
    // $textures = array();
    foreach ($attrs as $attrEn => $attrCn) {
        if (preg_match("/\b{$attrEn}\b/i", $goodsName, $matches)) {
            return $attrCn;
        }
    }
    
    
    return null;
    
}

/**
 * 更换aws的图片url为国内的url
 * @param string $imgSrc
 * @return string 国内的url
 */
function localjjshouseImageUrl($imgSrc) {
    return str_replace('img.jjshouse.com', 'imgerp.leqee.com', $imgSrc);
}

/**
 * 
 * 给工单列表增加相同订单号取消和完成的其他工单数量
 * @param array $dispatch_list
 */
function addFinishedCancelledCount(&$dispatch_list) {
	if (is_array($dispatch_list)) {
		foreach ($dispatch_list as $key => $order) {
			$orderSn = $dispatch_list[$key]->orderSn;
			$orders[$orderSn] = null;
		}
	}
	if (is_array($orders)) {
		foreach ($orders as $orderSn => $order) {
			$criteria = new stdClass();
			$criteria->offset = 0;
			$criteria->count = 500;
			$criteria->orderSn = $orderSn;
			$criteria->dispatchStatusId = 'CANCELLED';
			$list = searchDispatchLists($criteria);
			if (!empty($list)) {
				$orders[$orderSn]->cancelledCount = count($list);
			}
			else {
				$orders[$orderSn]->cancelledCount = 0;
			}
			$criteria->dispatchStatusId = 'FINISHED';
			$list = searchDispatchLists($criteria);
			if (!empty($list)) {
				$orders[$orderSn]->finishedCount = count($list);
			}
			else {
				$orders[$orderSn]->finishedCount = 0;
			}				
		}
	}
	if (is_array($dispatch_list)) {
    	foreach ($dispatch_list as $key => $dispatch) {
    		$orderSn = $dispatch->orderSn;
    		$dispatch_list[$key]->finishedCount = $orders[$orderSn]->finishedCount;
    		$dispatch_list[$key]->cancelledCount = $orders[$orderSn]->cancelledCount;
    	}
	}
	return $dispatch_list;
}

function getLatestPurchasePrices($orderGoodsId, $dispatchSn, $limit = 3) {
    global $slave_db;

    $sql = "
    SELECT oga.value AS wrap_price, dl.external_goods_id AS goods_id
    FROM romeo.dispatch_list dl
    	left join ecshop.order_goods_attribute oga on oga.order_goods_id = dl.order_goods_id and oga.name = 'wrap_price' and oga.value > 0
    WHERE dl.order_goods_id = '{$orderGoodsId}'
	LIMIT 1
    ";
    
    $wrap_price_goods_id = $slave_db->getRow($sql);
    $wrap_price = isset($wrap_price_goods_id['wrap_price']) ? floatval($wrap_price_goods_id['wrap_price']) : 0;
    $addsql = " and (oga.value <= 0 OR oga.value is null) ";
    if ($wrap_price > 0) {
        $addsql = " and oga.value > 0 ";
    }
    
    $goods_id = isset($wrap_price_goods_id['goods_id']) ? intval($wrap_price_goods_id['goods_id']) : 0;
    $sql = "
	select p.provider_name, d.price, d.dispatch_sn, d.currency, d.provider_id
	from romeo.dispatch_list d
	    left join ecshop.order_goods_attribute oga on d.order_goods_id = oga.order_goods_id and oga.name = 'wrap_price'
	    left join ecshop.ecs_provider p on p.provider_id = d.provider_id
	where 1
	    and d.external_goods_id='{$goods_id}'
	    and d.dispatch_sn != '{$dispatchSn}'
	    $addsql
	order by 
	    d.created_stamp desc
	limit $limit
    	";
    return $slave_db->getAll($sql);
    
    /*
    // 先找到对应的jjshouse的goods_id
    $sql = "select value from order_goods_attribute where order_goods_id = '{$orderGoodsId}' and name = 'goods_id' ";
    $jjshouseGoodsId = $slave_db->getOne($sql);
    
    $sql = "
	select
	    p.provider_name, d.price, d.dispatch_sn, d.currency
	from
	    romeo.dispatch_list d
	    inner join ecshop.ecs_order_goods sog on sog.rec_id = d.order_goods_id
	    inner join ecshop.order_goods_attribute oga on sog.rec_id = oga.order_goods_id
	    left join ecshop.ecs_provider p on p.provider_id = d.provider_id
	where
	    oga.name = 'goods_id'
	    and oga.value='{$jjshouseGoodsId}'
	    and d.dispatch_sn != '{$dispatchSn}'
	order by 
	    d.created_stamp desc
	limit 3;
    ";

    return $slave_db->getAll($sql);
    */
}

/**
 * 拆分style属性和image属性
 * @param array $_attributes
 * @param boolean $ishashmap 传入的是数组还是hashmap
 * @return array style属性和image属性
 */
function splitStyleImgAttributes($_attributes, $ishashmap = false) {
    $styleAttributes = array();
    $imgAttributes = array();
    
    if ($ishashmap) {
        foreach ($_attributes as $attr_name => $attr_value) {
            if (strpos($attr_name, 'goodsStyle_') === 0) {
                $styleAttributes[substr($attr_name, 11)] = $attr_value;
            } elseif(strpos($attr_name, 'goodsImage') === 0) {
                $imgAttributes[$attr_name] = $attr_value;
            }
        }
    } else {
        foreach ($_attributes as $_attribute) {
            if (strpos($_attribute['attr_name'], 'goodsStyle') === 0) {
                $styleAttributes[substr($_attribute['attr_name'], 11)] = $_attribute['attr_value'];
            } elseif(strpos($_attribute['attr_name'], 'goodsImage') === 0) {
                $imgAttributes[$_attribute['attr_name']] = $_attribute['attr_value'];
            }
        }
    }
    
    return array($styleAttributes, $imgAttributes);
}
