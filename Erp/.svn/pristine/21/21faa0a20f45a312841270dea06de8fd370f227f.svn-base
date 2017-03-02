<?php

/**
 * @filename /admin/includes/lib_order.php
 */

/**
 * 取得婚期最小天数
 * @param array $goods_list
 */
function getOrderMindays($goods_list)
{
	global $db;
	$days = 0;
	
	// 陈翼邮件说就7天
	return 7 + 5;
	
	// {{{ 制作周期跟网站保持一致
	foreach ($goods_list as $k => $goods) {
		$goods_id = (int) $goods['goods_id'];
		$sql = "SELECT g.cat_id, uniq_sku, c.cat_id, c.config
				FROM ecshop.ecs_goods g
					left join ecshop.category c on g.uniq_sku like concat('%c', c.cat_id, 'g%')
				WHERE g.goods_id = $goods_id 
				LIMIT 1";
		$row = $db->getRow($sql);
		$config = json_decode($row['config'], true);
		$days = max($days, $config['min']);
	}
	return $days + 5;
	// }}}
	
	foreach ($goods_list as $k => $goods) {
		$goods_id = (int) $goods['goods_id'];
		$sql = "SELECT cat_id FROM ecshop.ecs_goods WHERE goods_id = $goods_id LIMIT 1";
		$goods_cat_id = $db->getOne($sql);
		if (in_array($goods_cat_id, array(2335, 2380, 2417, 2445, 2460))) {
			// 婚纱
			$days = max($days, 19);
		} elseif (in_array($goods_cat_id, array(2336, 2378, 2418, 2446, 2461))) {
			// 礼服
			$days = max($days, 14);
		} elseif (in_array($goods_cat_id, array(2337, 2379, 2419, 2444, 2462))) {
			// 配件
			$days = max($days, 9);
		}
	}
	return $days;
}

/**
 * 自动匹配品名（婚纱礼服配件类；水龙头类的直接使用 Faucets）
 * @author Zandy 2011.12
 * @param array $goods_list
 */
function auto_match_description($goods_list = array(), $country_id = 0)
{
    //$r_default = array('hscode' => '', 'description' => '');
    $r_default = 0;
    foreach ($goods_list as $k => $v) {
        if (isset($rec_id)) {
            if ($v['rec_id'] == $rec_id) {
                // 这个从soap取过来的会按商品数量拆分多分，有重复，要过滤掉重复的
                unset($goods_list[$k]);
                unset($rec_id);
            }
        } else {
            $rec_id = $v['rec_id'];
        }
        $v['order_goods_attribute'] = get_order_goods_attribute($v['rec_id']);
        $m['test'] = '';
        if (!isset($v['order_goods_attribute']['cat_id']) || !$v['order_goods_attribute']['cat_id']) {
            preg_match('/c(?P<test>\d+)g/is', $v['order_goods_attribute']['sku'], $m);
        }
        $v['order_goods_attribute']['cat_id'] = isset($v['order_goods_attribute']['cat_id']) ? $v['order_goods_attribute']['cat_id'] : $m['test'];
        if ($v['order_goods_attribute']['cat_id'] == 34) {
            // service 的过滤掉
            unset($goods_list[$k]);
            continue;
        }
        $goods_list[$k] = $v;
    }
    if (empty($goods_list)) {
        return $r_default;
    }
    global $db;
    $is_dress_cat = true;
    if (sizeof($goods_list) == 1) {
        $cat_id = $goods_list[0]['order_goods_attribute']['cat_id'];
        // 配件
        if (in_array($goods_list[0]['cat_id'], array(2337, 2379, 2419, 2444, 2462))) {
            $is_dress_cat = false;
        }
    } else {
        $all_is_dresses = true;
        $all_is_accessories = true;
        $all_is_accessories_same_cat = true;
        $all_is_accessories_same_cat_id = 0;
        $hightly_dress = array('shop_price_usd_original' => 0);
        $cheaply_accessory = array('shop_price_usd_original' => 0);
        foreach ($goods_list as $k => $v) {
            if (in_array($v['cat_id'], array(2337, 2379, 2419, 2444, 2462))) {
                // 配件，说明不全是衣服
                $all_is_dresses = false;
                if ($all_is_accessories_same_cat_id == 0) {
                    $all_is_accessories_same_cat_id = $v['order_goods_attribute']['cat_id'];
                } elseif ($all_is_accessories_same_cat_id != $v['order_goods_attribute']['cat_id']) {
                    // 说明不全是同一种的配件
                    $all_is_accessories_same_cat = false;
                }
                if (!$cheaply_accessory['shop_price_usd_original'] || $v['shop_price_usd_original'] <= $cheaply_accessory['shop_price_usd_original']) {
                    $cheaply_accessory = $v;
                }
            } elseif (in_array($v['cat_id'], array(2335, 2336, 2380, 2378, 2417, 2418, 2445, 2446, 2460, 2461))) {
                // 婚纱、礼服，说明不全是配件
                $all_is_accessories = false;
                if ($v['shop_price_usd_original'] >= $hightly_dress['shop_price_usd_original']) {
                    $hightly_dress = $v;
                }
            }
        }
        switch (true) {
            case $all_is_dresses && $all_is_accessories:
                //echo 'aaa';
                // 信息有误，不可能既全部是衣服又全部是配件
                return $r_default;
                break;
            case !$all_is_dresses && $all_is_accessories:
                //echo 'bbb';
                // 说明全是配件
                if ($all_is_accessories_same_cat) {
                    $cat_id = $cheaply_accessory['order_goods_attribute']['cat_id'];
                } else {
                    $cat_id = $cheaply_accessory['order_goods_attribute']['cat_id'];
                }
                $is_dress_cat = false;
                break;
            case !$all_is_dresses && !$all_is_accessories:
                //echo 'ccc';
                // 说明又有衣服又有配件
                $cat_id = $hightly_dress['order_goods_attribute']['cat_id'];
                break;
            case $all_is_dresses && !$all_is_accessories:
                //echo 'ddd';
                // 说明都是衣服
                switch ($country_id) {
                    case 3859:
                    case 3844:
                    case 3835:
                        //$sql = "SELECT hscode, description FROM echop.hscode WHERE hscode_id = 1 ";
                        //$r = $db->getRow($sql);
                        $r = 1;
                        break;
                        
                    default:
                        //$sql = "SELECT hscode, description FROM echop.hscode WHERE hscode_id = 2 ";
                        //$r = $db->getRow($sql);
                        $r = 2;
                }
                break;
        }
    }
    if (isset($r) && $r) {
        return $r;
    } elseif ($cat_id) {
        if ($is_dress_cat) {
            // 说明是衣服分类
            switch ($country_id) {
                case 3859: // 美国
                case 3844: // 加拿大
                case 3835: // 澳大利亚
                    //$sql = "SELECT hscode, description FROM ecshop.hscode WHERE hscode_id = 1 ";
                    //$r = $db->getRow($sql);
                    $r = 1;
                    break;
                    
                default:
                    //$sql = "SELECT hscode, description FROM ecshop.hscode WHERE hscode_id = 2 ";
                    //$r = $db->getRow($sql);
                    $r = 2;
            }
        } else {
            // 说明是配件分类
            $cat_id = (int) $cat_id;
            $sql = "SELECT hs.hscode_id, hscode, description 
                    FROM ecshop.category c 
                        LEFT JOIN ecshop.hscode hs ON c.hscode_id = hs.hscode_id
                    WHERE c.cat_id = $cat_id 
                    LIMIT 1";
            $r = $db->getRow($sql);
            $r = $r['hscode_id'];
        }
        return $r;
    } else {
        return $r_default;
    }
}

/**
 * get taobao register source
 * @author Zandy 2010.12
 * @return $source_id int
 */
function get_user_register_source_id($source = '淘宝')
{
    if (empty($source)) {
        return null;
    }
    $db = $GLOBALS['db'];
    $sql = "SELECT `id` FROM ecshop.ecs_user_register_sources WHERE source = '$source' LIMIT 1 ";
    $source_id = $db->getOne($sql);
    if (!$source_id) {
        $sqli = "INSERT INTO `ecshop`.`ecs_user_register_sources` 
			 	(`id`, `source`, `parent_id`, `weight`, `disabled`) 
			 VALUES (NULL, '$source', '0', '0', '0')";
        $db->query($sqli);
        $sql = "SELECT `id` FROM ecshop.ecs_user_register_sources WHERE source = '$source' LIMIT 1 ";
        $source_id = $db->getOne($sql);
        if (!$source_id) {
            echo '需要执行以下 sql，然后再访问本页 —— 请联系 erp 组。';
            p($sqli);
            die();
        }
    }
    return $source_id;
}

/**
 * 同步淘宝用户信息、地址信息到 ecs_users 和 ecs_user_address
 * @author Zandy 2010.12
 * @param array $order_info
 * @return not return
 */
function sync_taobao_user_info(array $order_info, $source_id)
{
    $db = $GLOBALS['db'];
    // {{{
    $order = array(
        'user_name' => $order_info['attr_value'], 
        'user_realname' => $order_info['consignee'], 
        'sex' => $order_info['sex'] == 'male' ? 1 : ($order_info['sex'] == 'female' ? 2 : 0), 
        'country' => $order_info['country'], 
        'province' => $order_info['province'], 
        'city' => $order_info['city'], 
        'district' => $order_info['district'], 
        //'address_id' => $order_info['address'], 
        'zipcode' => $order_info['zipcode'], 
        'user_tel' => $order_info['tel'], 
        'user_mobile' => $order_info['mobile'], 
        'email' => $order_info['email'], 
        'reg_source' => $source_id
    );
    // }}}
    $sql = "SELECT user_id FROM ecshop.ecs_users WHERE user_name = '" . $db->quote($order_info['attr_value']) . "'";
    $user_id = $db->getOne($sql);
    if ($user_id > 0) {
        $r = $db->update('ecshop.ecs_users', $order, "user_id = '$user_id'", 1);
    } else {
        $order['userId'] = 'ZYMI::replace(uuid(), "-", "")';
        $order['reg_time'] = time();
        $order['last_time'] = date("Y-m-d H:i:s", $order['reg_time']);
        $user_id = $db->insert('ecshop.ecs_users', $order);
    }
    if ($user_id) {
        $address = array(
            'consignee' => $order_info['consignee'], 
            'sex' => $order_info['sex'], 
            'email' => $order_info['email'], 
            'country' => $order_info['country'], 
            'province' => $order_info['province'], 
            'city' => $order_info['city'], 
            'district' => $order_info['district'], 
            'address' => $order_info['address'], 
            'zipcode' => $order_info['zipcode'], 
            'tel' => $order_info['tel'], 
            'mobile' => $order_info['mobile'], 
            'user_id' => $user_id
        );
        $sqlx = $db->prepareSQL($address, ' AND ');
        $sql = "SELECT address_id FROM ecshop.ecs_user_address WHERE $sqlx ";
        $address_id = $db->getOne($sql);
        if (!$address_id) {
            $address_id = $db->insert('ecshop.ecs_user_address', $sqlx);
        }
        if ($address_id) {
            $data = array(
                'address_id' => $address_id
            );
            $r = $db->update('ecshop.ecs_users', $data, "user_id = '$user_id'", 1);
        }
    }
}

/**
 * 取消订单->追回货物，生成-t订单
 * 拒收->拒收收货，生成-t订单
 *
 * @author zjli
 * 
 * @param int $order_id 原始订单id
 * @return bool
 */
function generate_return_all_back_order($order_id, $action, $goodsType = null) {
    global $db, $ecs;
    
    // 查询原始订单信息
    $sql = "SELECT * FROM ecshop.ecs_order_info WHERE order_id = '{$order_id}'  ";
    $original_order = $db->getRow($sql);
    $sqls[] = $sql;
    if (!$original_order) {
        return false;
    }

    // 记录原始的订单号和订单id
    $original_order_id = $original_order['order_id'];
    $original_order_sn = $original_order['order_sn'];
    $facility_id = $original_order['facility_id'];  // 取消的订单回到原仓库，故facility_id来自原订单
    unset($original_order['order_id']);
    unset($original_order['order_sn']);
    
    // 复制订单
    $order = $original_order;
    
    // 初始化即将生成的-t订单数据
    $order['order_status'] = 1;
    $order['shipping_status'] = 0;
    $order['order_amount'] = -1 * $original_order['order_amount'];
    $order['order_time'] = date("Y-m-d H:i:s");
    $order['order_type_id'] = 'RMA_RETURN';
    $order['address'] = str_replace('\'', '\\\'', $order['address']);    // 将订单中收件人地址中单引号转义处理
    $order['consignee'] = mysql_escape_string($order['consignee']);
    $order['inv_payee'] = mysql_escape_string($order['inv_payee']);
    $order['mobile'] = mysql_escape_string($order['mobile']);
    $order['postscript'] = str_replace('\'', '\\\'', $order['postscript']);
    
    // 获取原始订单的商品信息
    $sql = "SELECT og.* FROM {$ecs->table('order_goods')} og WHERE order_id = '{$original_order_id}' ";
    $parent_order_goods = $db->getAll($sql);
    $sqls[] = $sql;
    
    $sql = "SELECT distinct og.rec_id FROM ecshop.order_relation eor
    	 left join ecshop.ecs_order_goods og on og.order_id = eor.order_id 
    	 where eor.parent_order_id = '{$original_order_id}' ";
    $rec_ids = $db->getCol($sql);	 
    $original_order_goods = array();
    if(!empty($rec_ids)){
    	$order_goods_ids = implode("','",$rec_ids);
    	$sql = "SELECT rec_id,goods_id,style_id,sum(goods_number) order_goods_number from  ecshop.ecs_order_goods og " .
	    	" inner join romeo.inventory_item_detail iid on iid.order_goods_id = og.rec_id " .
	    	" INNER JOIN romeo.inventory_item ii ON ii.inventory_item_id = iid.inventory_item_id " .
	    	" where og.rec_id in ('{$order_goods_ids}') and iid.order_goods_id in ('{$order_goods_ids}') " .
	    	" group by og.rec_id ";
	    $child_order_goods = $db->getAll($sql);
	    $parent_key_array = array();
	    $child_key_array = array();
	    if(!empty($child_order_goods)){
	    	foreach($parent_order_goods as $key=>$parent_good){
	    		foreach($child_order_goods as $key2=>$child_good){
	    			if(in_array($key2,$child_key_array)){
	    				continue;
	    			}
	    			if($parent_good['goods_id'] == $child_good['goods_id'] && 
	    			$parent_good['style_id'] == $child_good['style_id'] && 
	    			$parent_good['goods_number'] == $child_good['order_goods_number']){
	    				array_push($parent_key_array,$key);
	    				array_push($child_key_array,$key2);
	    				break;
	    			}
	    		}
	    	}
	    } 
	    if(!empty($parent_key_array)){
	    	foreach($parent_key_array as $key)
	    	unset($parent_order_goods[$key]);
	    }
    }
    
    $original_order_goods = $parent_order_goods;
	if(empty($original_order_goods)){
		return true;
	}
	$order['order_sn'] = $original_order_sn;
    // 插入订单数据
    do {
    	$order['order_sn'] = $order['order_sn']."-t"; // 获取新订单号:在原订单号后面加"-t"
        $db->autoExecute($ecs->table('order_info'), $order, 'INSERT', '', 'SILENT');
        $error_no = $db->errno();
        if ($error_no > 0 && $error_no != 1062) {
        	return false;
        }
    } while ($error_no == 1062); //如果是订单号重复则重新提交数据
    
    $order_id = $db->insert_id();  // 新生成的-t订单的order_id
    if(empty($order_id) || $order_id <= 0){
    	return false;
    }
    
    //增加记录订单关系 added by zwsun 2009年7月23日15:37:43
    if(!add_order_relation($order_id, $original_order_id, '', $order['order_sn'], $original_order_sn)){
    	return false;
    }

    include_once(ROOT_PATH . "RomeoApi/lib_inventory.php");
    // 循环插入-t订单 order_goods
    foreach ($original_order_goods as $goods) {
    	$goods['goods_name'] = addslashes($goods['goods_name']);
        $order_goods = array (
          'order_id' => $order_id,
          'goods_id' => $goods['goods_id'],
          'style_id' => $goods['style_id'],
          'goods_name' => $goods['goods_name'],
          'goods_number' => $goods['goods_number'],
          'market_price' => $goods['market_price'],
          'goods_price' => $goods['goods_price'],
        );
        $db->autoExecute($ecs->table('order_goods'), $order_goods);
        $order_goods_id = $db->insert_id();
        if(empty($order_goods_id) || $order_goods_id <= 0){
        	return false;
        }
        
        if ($action == 'cancel') {  // 取消订单——入库
	        // 获取出库时的相关库存信息
	        $sql = "SELECT iid.QUANTITY_ON_HAND_DIFF, ii.SERIAL_NUMBER, ii.INVENTORY_ITEM_ACCT_TYPE_ID,
	        		ii.STATUS_ID, ii.UNIT_COST, ii.provider_id
	        		FROM romeo.inventory_item_detail iid
	        		INNER JOIN romeo.inventory_item ii ON ii.inventory_item_id = iid.inventory_item_id
	        		WHERE iid.order_goods_id = '{$goods['rec_id']}'";
	        $out_info = $db->getAll($sql);
			$sqls[] = $sql;
			
			// 针对每一条出库记录，重新入库
			foreach($out_info as $out_info_item){
				$status = createAcceptInventoryTransactionNew('ITT_SO_CANCEL',                                                       // $inventoryTransactionTypeId
	                                             array('goods_id'=>$goods['goods_id'], 'style_id'=>$goods['style_id']), // $goods_and_style
	                                             -$out_info_item['QUANTITY_ON_HAND_DIFF'],                              // $amount
	                                             $out_info_item['SERIAL_NUMBER'],                                       // $serialNo
	                                             $out_info_item['INVENTORY_ITEM_ACCT_TYPE_ID'],                         // $acctType
	                                             $order_id,                                                             // $orderId (-t订单)
												 '',                                                                    // $fromStatusId
											     $out_info_item['STATUS_ID'],                                           // $toStatusId
	                                             $out_info_item['UNIT_COST'],                                           // $unitCost 采购单价
	                                             $order_goods_id,                                                       // $orderGoodsId (-t订单)
	                                             $facility_id,                                                          // $facility_id
	                                             $out_info_item['provider_id']);                                        // $provider_id
	            if($status != 'OK'){
	            	return false;
	            }
			}
        }elseif($action == 'reject'){   // 拒收货物——入库
	        $is_serial = getInventoryItemType($goods['goods_id']);  // 判断该商品是否串号控制
	        if($is_serial == 'SERIALIZED'){  // 该商品是串号控制的
	        	// 获取出库时的相关库存信息
		        $sql = "SELECT iid.QUANTITY_ON_HAND_DIFF, ii.SERIAL_NUMBER, ii.INVENTORY_ITEM_ACCT_TYPE_ID,
		        		ii.STATUS_ID, ii.UNIT_COST, ii.provider_id
		        		FROM romeo.inventory_item_detail iid
		        		INNER JOIN romeo.inventory_item ii ON ii.inventory_item_id = iid.inventory_item_id
		        		WHERE iid.order_goods_id = '{$goods['rec_id']}'";
		        $out_info = $db->getAll($sql);
				$sqls[] = $sql;
				
				// 针对每一条出库记录，重新入库
				foreach($out_info as $out_info_item){
					$toStatusId = $goodsType[$out_info_item['SERIAL_NUMBER']][0] == 'new' ? 'INV_STTS_AVAILABLE' : 'INV_STTS_USED';
					$status = createAcceptInventoryTransactionNew('ITT_SO_REJECT',                                                       // $inventoryTransactionTypeId
		                                             array('goods_id'=>$goods['goods_id'], 'style_id'=>$goods['style_id']), // $goods_and_style
		                                             1,                              										// $amount
		                                             $out_info_item['SERIAL_NUMBER'],                                       // $serialNo
		                                             $out_info_item['INVENTORY_ITEM_ACCT_TYPE_ID'],                         // $acctType
		                                             $order_id,                                                             // $orderId (-t订单)
													 '',                                                                 	// $fromStatusId
												     $toStatusId,                                           				// $toStatusId
		                                             $out_info_item['UNIT_COST'],                                           // $unitCost 采购单价
		                                             $order_goods_id,                                                       // $orderGoodsId (-t订单)
		                                             $facility_id,                                                          // $facility_id
		                                             $out_info_item['provider_id']);                                        // $provider_id
		            if($status != 'OK'){
		            	return false;
		            }
				}
	        	
	        }else{  // 该商品是非串号控制的
	        	// 获取该商品的商品条码
	        	$sql = "SELECT IFNULL(gs.barcode, g.barcode) as barcode FROM ecshop.ecs_order_goods og
	          			LEFT JOIN ecshop.ecs_goods_style gs ON og.goods_id = gs.goods_id AND og.style_id = gs.style_id and gs.is_delete=0
	          			LEFT JOIN ecshop.ecs_goods g ON og.goods_id = g.goods_id
	          			WHERE og.rec_id  = '{$order_goods_id}' ";
	          	$barcode = $db->getOne($sql);
	          	if(!$barcode){
	          		return false;
	          	}
	          	
	        	// 统计退回货物的新旧情况
	        	$numOfNew = 0;  // 新的数量
	        	$numOfOld = 0;  // 二手数量
	        	if(!empty($goodsType[$barcode])){
	        		foreach($goodsType[$barcode] as $key=>$goodType){
		        		if($goodType == 'new'){
		        			$numOfNew++;
		        		}elseif($goodType == 'old'){
		        			$numOfOld++;
		        		}
		        		$goodsType[$barcode][$key] = '';
		        		if(($numOfNew + $numOfOld) == $goods['goods_number']){
		        			break;
		        		}
		        	}
	        	}
	        	
	        	// 查询商品销售出库时的unit_cost(采购单价), provider_id(供应商)以及inventory_item_acct_type_id(B2C C2C DX etc)信息 .
	        	// 此sql可能会存在问题，因为这里只取了unit_cost(采购单价)的一个值，该字段可能有多个值
	        	$sql = "SELECT ii.inventory_item_acct_type_id, ii.unit_cost, ii.provider_id
	                	FROM romeo.inventory_item ii
	                	INNER JOIN romeo.inventory_item_detail iid ON iid.INVENTORY_ITEM_ID = ii.INVENTORY_ITEM_ID
						WHERE iid.ORDER_GOODS_ID = '{$goods['rec_id']}' AND ii.STATUS_ID = 'INV_STTS_AVAILABLE' LIMIT 1";
				$out_info = $db->getRow($sql);
				if(!$out_info){
					return false;
				}
				
				// 全新商品入库(INV_STTS_AVAILABLE)
				if($numOfNew > 0){
					$status = createAcceptInventoryTransactionNew('ITT_SO_REJECT',														// $inventoryTransactionTypeId
	                                             	 array('goods_id'=>$goods['goods_id'], 'style_id'=>$goods['style_id']), // $goods_and_style
	                                             	 $numOfNew,																// $amount
	                                             	 '', 																	// $serialNo
	                                             	 $out_info['inventory_item_acct_type_id'], 								// $acctType
	                                             	 $order_id,																// $orderId (-t订单)
	                                             	 '', 																	// $fromStatusId
													 'INV_STTS_AVAILABLE',													// $toStatusId
	                                             	 $out_info['unit_cost'], 												// $unitCost 采购单价
	                                             	 $order_goods_id,														// $orderGoodsId (-t订单)
	                                             	 $facility_id, 															// $facility_id
	                                             	 $out_info['provider_id']);												// $provider_id
	                if($status != 'OK'){
	                	return false;
	                }
				}
	        	// 二手商品入库(INV_STTS_USED)
	        	if($numOfOld > 0){
	        		$status = createAcceptInventoryTransactionNew('ITT_SO_REJECT',														// $inventoryTransactionTypeId
	                                             	 array('goods_id'=>$goods['goods_id'], 'style_id'=>$goods['style_id']), // $goods_and_style
	                                             	 $numOfOld, 															// $amount
													 '', 																	// $serialNo
													 $out_info['inventory_item_acct_type_id'], 								// $acctType
													 $order_id,																// $orderId (-t订单)
	                                             	 '', 																	// $fromStatusId
													 'INV_STTS_USED',														// $toStatusId
	                                             	 $out_info['unit_cost'], 												// $unitCost 采购单价
	                                             	 $order_goods_id,														// $orderGoodsId (-t订单)
	                                             	 $facility_id, 															// $facility_id
	                                             	 $out_info['provider_id']);												// $provider_id
	                if($status != 'OK'){
	                	return false;
	                }
	        	}
	        }
        }
    }

    //update order mixed status 退货或者拒收的都是 warehouse_status = returned
    // include_once('lib_order_mixed_status.php');
    // $order_mixed_status_history_id = update_order_mixed_status($original_order_id, array('warehouse_status' => 'returned'), 'worker');
    // if(!$order_mixed_status_history_id || $order_mixed_status_history_id <= 0){
    // 	return false;
    // }
    // Sinri killed this, and if needed, please add order action instead
    
    // update shipping_time for the -t order
    $sql = "UPDATE ecshop.ecs_order_info SET shipping_time = UNIX_TIMESTAMP() WHERE order_id = {$order_id} LIMIT 1 ";
	if(!$db->query($sql)){
		return false;
	}
    return $order_id;
}

/**
 * 获取是否订单商品定制机
 *
 * @param string $type 类型
 * @param bool $goods_name 是否是商品名
 * @return unknown
 */
function get_customize_type($type = null, $goods_name = false) {
    $prestr = $goods_name ? " (" : "";
    $poststr = $goods_name ? ")" : "";
    switch ($type) {
        case 'mobile':
            return $prestr.'移动'.$poststr;
            break;
        case 'non-mobile':
            return $prestr.'非移动'.$poststr;
            break;
        case 'all':
            if ($goods_name == false) {
                return '都可以';
            } else {
                return '';
            }
            break;
        case 'not-applicable':
            if ($goods_name == false) {
                return '未指定';
            } else {
                return '';
            }
            break;
        default:
            return array(
            'not-applicable'     =>         '未指定',
            'mobile'                     =>         '移动',
            'non-mobile'             =>         '非移动',
            'all'                         =>         '都可以',
            );
    }
}
/**
 * 获取订单所有信息md5，用于判断订单是否修改
 *
 * @author ncchen
 * @param int $order_id
 * @return string $res md5返回值
 */
function get_order_info_md5($order_id) {
    if (!function_exists('getOrderInfo')) {
        require_once(ROOT_PATH . 'admin/function.php');
    }
    $order_info = getOrderInfo($order_id);
    $serial_info = @serialize($order_info);
    $res = md5($serial_info);
    return $res;
}

/**
 * 更新订单对应的 pay_log
 * 如果未支付，修改支付金额；否则，生成新的支付log
 * @param   int     $order_id   订单id
 */
function update_pay_log($order_id)
{
    $order_id = intval($order_id);
    if ($order_id > 0)
    {
        $sql = "SELECT order_amount FROM " . $GLOBALS['ecs']->table('order_info') .
               " WHERE order_id = '$order_id'";
        $order_amount = $GLOBALS['db']->getOne($sql);
        
        if (!is_null($order_amount))
        {
            $sql = "SELECT log_id FROM " . $GLOBALS['ecs']->table('pay_log') .
            " WHERE order_id = '$order_id'" .
            " AND order_type = '" . PAY_ORDER . "'" .
            " AND is_paid = 0";
            $log_id = intval($GLOBALS['db']->getOne($sql));
            if ($log_id > 0)
            {
                /* 未付款，更新支付金额 */
                $sql = "UPDATE " . $GLOBALS['ecs']->table('pay_log') .
                " SET order_amount = '$order_amount' " .
                "WHERE log_id = '$log_id' LIMIT 1";
            }
            else
            {
                /* 已付款，生成新的pay_log */
                $sql = "INSERT INTO " . $GLOBALS['ecs']->table('pay_log') .
                " (order_id, order_amount, order_type, is_paid)" .
                "VALUES('$order_id', '$order_amount', '" . PAY_ORDER . "', 0)";
            }
            $GLOBALS['db']->query($sql);
        }
    }
}

/**
 * 根据order的信息合法的得到该order的shippinga_area_id
 * @author Wang Yuan
 * @param   array $order   订单关联数组，至少包含'province' 和 'shipping_id'两个键
 */
function get_shipping_area_id_by_order($order) {
    $province_id = $order['province'];
    $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('area_region') . " WHERE region_id = {$province_id};";
    $shipping_area_list = $GLOBALS['db']->getAll($sql);
    $shipping_area_id_list = array();
    foreach ($shipping_area_list as $k => $v) {
        $shipping_area_id_list[] = $v['shipping_area_id'];
    }
    //pp($shipping_area_id_list);
    $str = empty($shipping_area_id_list) ? '(null)' : '('.implode(',', $shipping_area_id_list).')';

    $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('shipping_area') . " WHERE shipping_area_id in {$str};";
    $list = $GLOBALS['db']->getAll($sql);
    foreach ($list as $key => $val) {
        if ($val['shipping_id'] == $order['shipping_id']) {
            $shipping_area_id = $val['shipping_area_id'];
        }
    }
    return $shipping_area_id;
}

/**
 * 获得订单（列表）某状态信息
 * @author ncchen
 * 
 * @param string $order_id
 * @param string $order_status
 * @return mix
 */
function get_order_actions_by_order_id($order_id, $order_status) {
    global $ecs, $db;
    $in_order_ids = db_create_in($order_id, 'order_id');
    $sql = "SELECT action_user, action_time, order_id
            FROM {$ecs->table('order_action')} 
            WHERE order_status = '{$order_status}'
             AND $in_order_ids
            GROUP BY order_id
            ";
    if (is_array($order_id)) {
        $refs_value = array();
        $refs = array();
        $db->getAllRefBy($sql, array('order_id'), $refs_value, $refs);
        return $refs;
    }
    if (is_string($order_id)) {
        return $db->getRow($sql);
    }
}

/**
 * 获得订单留言
 * @author ncchen
 *
 * @param string $order_id
 * @param boolean $postscript
 * @return array
 */
function get_order_actions_list_by_order_id($order_id, $postscript = false) {
    global $ecs, $db;
    $in_order_ids = db_create_in($order_id, 'order_id');
    $sql = ($postscript?"
            (SELECT CONCAT('用户留言：', postscript) AS action_note, order_time AS action_time, order_id, 'user' AS action_user
            FROM {$ecs->table('order_info')} 
            WHERE $in_order_ids AND postscript != '')
            UNION":"").
            "(SELECT action_note, action_time, order_id, action_user
            FROM {$ecs->table('order_action')} a
            WHERE $in_order_ids
                AND action_note != ''
            ORDER BY action_time DESC)
        ";
            if (is_array($order_id)) {
                $refs_value = array();
                $refs = array();
                $db->getAllRefBy($sql, array('order_id'), $refs_value, $refs);
                return $refs;
            }
            if (is_string($order_id)) {
                return $db->getAll($sql);
            }
}

/**
 * 获得订单信息列表
 * @author ncchen
 *
 * @param string $sql_order
 * @param string $sql_order_goods
 * @param array $detail_list 附加明细，如订单操作
 * @return array $orders
 */
function get_order_details_list_by_sql($sql_order, $sql_order_goods, $detail_list = array('order_action')) {
    global $db, $ecs;
    
    // 订单列表
    $refs_value_order = $refs_order = array();
    $orders = $db->getAllRefby($sql_order, array('order_id'), $refs_value_order, $refs_order, false);
    if (empty($orders)) return $orders;
    
    // 商品列表
    $refs_value_goods = $refs_goods = array();
    $sql_order_goods = sprintf($sql_order_goods, db_create_in($refs_value_order['order_id'], 'og.order_id'));
    $goods_list = $db->getAllRefby($sql_order_goods, array('order_id'), $refs_value_goods, $refs_goods, false);
    //detail_list 附加明细
    $order_actions = array();
    if (in_array('order_action', $detail_list)) {
        $order_actions = get_order_actions_list_by_order_id($refs_value_order['order_id']);
    }
    
    // 取得库存总表
    // 性能问题先注释wjzhu@2012-07-16
    // $inventory_summary_list = getInventorySummaryList('INV_STTS_AVAILABLE');
    
    foreach ($orders as $key => $order) {
        $total_goods_count = 0;
        if (!empty($refs_goods['order_id'][$order['order_id']])) {
            foreach ($refs_goods['order_id'][$order['order_id']] as $goods_key => $goods) {
	            if (strpos($order['order_sn'],'-gt')) {
	                $refs_goods['order_id'][$order['order_id']][$goods_key]['cheque'] = $goods['cheque_gt'];
	                $refs_goods['order_id'][$order['order_id']][$goods_key]['is_finance_paid'] = $goods['is_finance_paid_gt'];
	                $refs_goods['order_id'][$order['order_id']][$goods_key]['is_purchase_paid'] = $goods['is_purchase_paid_gt'];
	                $refs_goods['order_id'][$order['order_id']][$goods_key]['purchase_paid_time'] = $goods['purchase_paid_time_gt'];
	            }
	            $total_goods_count += $goods['goods_number'];
	            $refs_goods['order_id'][$order['order_id']][$goods_key]['productcode'] = encode_goods_id($goods['goods_id'], $goods['style_id']);
	            
            }
        }

        $orders[$key]['goods_list'] = $refs_goods['order_id'][$order['order_id']];
        $orders[$key]['order_status_name'] = get_order_status($order['order_status']);
        $orders[$key]['shipping_status_name'] = get_shipping_status($order['shipping_status']);
        $orders[$key]['pay_status_name'] = get_pay_status($order['pay_status']);
        $orders[$key]['order_info_md5'] = get_order_info_md5($order['order_id']);

        // 取得物品总数
        $orders[$key]['total_goods_count'] = $total_goods_count;

        $orders[$key]['action_notes'] = $order_actions['order_id'][$order['order_id']];
    }
    
    return $orders;
}


/**
 * 记录订单之间的关系
 *
 * @param int $order_id
 * @param int $parent_order_id
 * @param int $root_order_id
 * @param string $order_sn
 * @param string $parent_order_sn
 * @param string $root_order_sn
 */
function add_order_relation($order_id, $parent_order_id, $root_order_id = '', $order_sn = '', $parent_order_sn = '', $root_order_sn = '') {
    global $db, $ecs;

    // 如果没有root_order_id ，找parent的root_order_id
    if (!$root_order_id) {
        $sql = "SELECT root_order_id FROM order_relation WHERE order_id = '{$parent_order_id}' ";
        $root_order_id = $db->getOne($sql);
    }

    // 如果没有root_order_id，root_order_id就为parent_order_id
    if (!$root_order_id) {
        $root_order_id = $parent_order_id;
    }

    // require_once("function.php");
    require_once(ROOT_PATH . 'admin/function.php');
    //获得order_sn
    if (!$order_sn) {
        $order_sn = getOrderSnByOrderId($order_id);
    }

    //获得parent_order_sn
    if (!$parent_order_sn) {
        $parent_order_sn = getOrderSnByOrderId($parent_order_id);
    }

    //获得root_order_sn
    if (!$root_order_sn) {
        $root_order_sn = getOrderSnByOrderId($root_order_id);
    }

    //执行插入关系
    $sql = "INSERT INTO order_relation (order_id, order_sn, parent_order_id, parent_order_sn, root_order_id, root_order_sn)
            VALUES('{$order_id}', '{$order_sn}', '{$parent_order_id}', '{$parent_order_sn}', '{$root_order_id}', '$root_order_sn' ) ";
    if(!$db->query($sql)){
    	return false;
    }else{
    	return true;
    }
}

/**
 * 增加订单属性
 *
 * @param int $order_id
 * @param string $attr_name
 * @param string $attr_value
 * @return int $attribute_id
 */
function add_order_attribute($order_id, $attr_name, $attr_value) {
    global $db;
    $order_id = intval($order_id);
    $sql = "insert into order_attribute (attr_name, attr_value, order_id) 
            values('$attr_name', '$attr_value', $order_id)";
    $db->query($sql);
    return $db->insert_id();
}

/**
 * 修改订单属性
 *
 * @param int $order_id
 * @param string $attr_name
 * @param string $attr_value
 * @return int $attribute_id
 */
function update_order_attribute($order_id, $attr_name, $attr_value) {
    global $db;
    $order_id = intval($order_id);

    $order_attr = get_order_attribute($order_id, $attr_name);
    
    if (empty($order_attr['attribute_id'])) {
        return add_order_attribute($order_id, $attr_name, $attr_value);
    } else {
        if ($order_attr['attr_value'] != $attr_value) {
            $sql = "update order_attribute set attr_value = '{$attr_value}' 
                    where attribute_id =  {$order_attr['attribute_id']} ";
            $db->query($sql);
        }
        return $order_attr['attribute_id'];
    }
}

/**
 * 获得订单的属性
 *
 * @param int $order_id
 * @param string $attr_name
 */
function get_order_attribute($order_id, $attr_name) {
    global $db;
    
    $order_id = intval($order_id);
    $sql = "select attribute_id, attr_value from order_attribute 
            where order_id = '{$order_id}' and attr_name = '{$attr_name}'
            limit 1";
    $order_attr = $db->getRow($sql);
    return $order_attr;
}

/**
 * 获得订单的属性列表
 *
 * @param int $order_id
 * @param array $attr_names
 */
function get_order_attribute_list($order_id, $attr_names) {
    global $db;
    
    $order_id = intval($order_id);
    $sql_attr_name = "";
    if (!is_null($attr_names)) {
        $sql_attr_name = " and " .db_create_in($attr_names, 'attr_name');
    }
    $sql = "select attribute_id, attr_name, attr_value from order_attribute 
            where order_id = '{$order_id}' {$sql_attr_name}";
    $fields_value = array();
    $ref = array();
    $order_attrs = $db->getAllRefby($sql, array('attr_name'), $fields_value, $ref);
    return $ref['attr_name'];
}

/**
 * 修改屏蔽号码
 *
 * @param unknown_type $order
 * @param unknown_type $action
 */
function convert_mask_phone(&$order, $action = null) {
	return;
    // 获得屏蔽配置
    static $mask_phone_config;
    if (!$mask_phone_config) {
        $mask_phone_config = get_mask_phone_config();
    }
    // 获得订单渠道
    $order_attribute = get_order_attribute($order['order_id'], 'SUB_OUTER_TYPE');
    if (empty($order_attribute)) {
        $order_attribute['attr_value'] = 'ouku';
    }
    // 0、参与屏蔽的ouku渠道
    // 1、参与屏蔽的快递方式
    // 2、参与屏蔽的party id
    // 3、参与屏蔽的仓库id
    // 4、参与屏蔽的province(region_id)
    // 5、总开关
    if ($mask_phone_config['sub_outer_type'][$order_attribute['attr_value']]
         || $mask_phone_config['shipping'][$order['carrier_id']]
         || $mask_phone_config['party'][$order['party_id']]
         || $mask_phone_config['facility'][$order['facility_id']]
         || $mask_phone_config['province'][$order['province']]
         || $mask_phone_config['switch'] != 'on') {
        return;
    }
    if (!(count($mask_phone_config['user']) == 0 ||
         $mask_phone_config['user'][$order['user_id']])) {
        return;
    }
    $tel = $order['tel'];
    $mobile = $order['mobile'];
    // 获取屏蔽电话，修改打印的订单信息
    if (!$order['tel']) {
        $order['mask_tel'] = set_mask_phone($mobile, $order['order_sn'], 'tel', $action);
    } else {
        $order['mask_tel'] = set_mask_phone($tel, $order['order_sn'], 'tel', $action);
    }
    if (!$order['mobile']) {
        $order['mask_mobile'] = set_mask_phone($tel, $order['order_sn'], 'mobile', $action);
    } else {
        $order['mask_mobile'] = set_mask_phone($mobile, $order['order_sn'], 'mobile', $action);
    }
    // 转化电话号码
    get_mask_phone($order);
}

/**
 * 设置屏蔽电话号码，修改或者添加
 *
 * @param unknown_type $phone
 * @param unknown_type $order_sn
 * @param unknown_type $type
 * @param unknown_type $action
 */
function set_mask_phone($phone, $order_sn, $type, $action = null) {
    global $db;
    //$phones = split("-", $phone);
    $phones = explode("-", $phone);
    if (count($phones) == 1) {
        $cus_phone_no = $phone;
    } else {
        $cus_phone_no = $phones[0]. $phones[1];
    }
//    $cus_phone_no_all = str_replace($phone, "-", "");
    $cus_phone_no_all = $phone;
    $sql = "SELECT mask_phone_no FROM callcenter_mask_phone 
            WHERE order_sn = '{$order_sn}' AND type = '{$type}' LIMIT 1 ";
    $mask_phone_no = $db->getOne($sql);
    if ($action == 'get') {
        return $mask_phone_no;
    }
    if ($mask_phone_no) {
        $action = 'edit';
    } else {
        $action = 'add';
    }
    if ($action == 'add') {
        do {
            $mask_phone_no = get_rand_no($cus_phone_no);
            $sql = "INSERT INTO callcenter_mask_phone 
                (mask_phone_no, cus_phone_no, cus_phone_no_all, order_sn, no_status, created_time,
                    type)
                VALUES ('{$mask_phone_no}', '{$cus_phone_no}', '{$cus_phone_no_all}', '{$order_sn}', 
                    'P', NOW(), '{$type}') ";
            $db->query($sql, 'SILENT');
            $error_no = $db->errno();
        } while ($error_no == 1062); //如果是mask_phone_no重复则重新提交数据
        return $mask_phone_no;
    } else if ($action == 'edit') {
        $sql = "UPDATE callcenter_mask_phone
             SET cus_phone_no = '{$cus_phone_no}', cus_phone_no_all = '{$cus_phone_no_all}',
              no_status = 'P'
             WHERE order_sn = '{$order_sn}' AND type = '{$type}'";
        $db->query($sql);
        return $mask_phone_no;
    }
}

/**
 * 随机号码，共8位，不足补0
 *
 * @param unknown_type $phone
 * @return unknown
 */
function get_rand_no($phone) {
    return sprintf("%04d", rand(0, 10000)). sprintf("%04d", substr(time()+intval($phone), -4, 4));
}

/**
 * 获得屏蔽号码的配置
 *
 * @return unknown
 */
function get_mask_phone_config() {
    global $db;
    $mask_phone_config = array();
    // 渠道的配置
    $sql = "SELECT value FROM ecs_shop_config WHERE code = 'callcenter_exclude_channel' LIMIT 1 ";
    $sub_outer_type = $db->getOne($sql);
    if (trim($sub_outer_type) != '') {
        //$sub_outer_types = split(",", $sub_outer_type);
        $sub_outer_types = explode(",", $sub_outer_type);
        foreach ($sub_outer_types as $res) {
            $mask_phone_config['sub_outer_type'][trim($res)] = true;
        }
    }
    // 快递的配置
    $sql = "SELECT value FROM ecs_shop_config WHERE code = 'callcenter_exclude_shipping' LIMIT 1 ";
    $shipping = $db->getOne($sql);
    if (trim($shipping) != '') {
        //$shippings = split(",", $shipping);
        $shippings = explode(",", $shipping);
        foreach ($shippings as $res) {
            $mask_phone_config['shipping'][trim($res)] = true;
        }
    }
    // 地区的配置
    $sql = "SELECT value FROM ecs_shop_config WHERE code = 'callcenter_exclude_province' LIMIT 1 ";
    $province = $db->getOne($sql);
    if (trim($province) != '') {
        //$provinces = split(",", $province);
        $provinces = explode(",", $province);
        // 排除掉这些地区
        foreach ($provinces as $res) {
            $mask_phone_config['province'][trim($res)] = true;
        }
    }
    // 组织的配置
    $sql = "SELECT value FROM ecs_shop_config WHERE code = 'callcenter_exclude_party' LIMIT 1 ";
    $party = $db->getOne($sql);
    if (trim($party) != '') {
        //$partys = split(",", $party);
        $partys = explode(",", $party);
        foreach ($partys as $res) {
            $mask_phone_config['party'][trim($res)] = true;
        }
    }
    // 仓库的配置
    $sql = "SELECT value FROM ecs_shop_config WHERE code = 'callcenter_exclude_facility' LIMIT 1 ";
    $facility = $db->getOne($sql);
    if (trim($facility) != '') {
        //$facilitys = split(",", $facility);
        $facilitys = explode(",", $facility);
        foreach ($facilitys as $res) {
            $mask_phone_config['facility'][trim($res)] = true;
        }
    }
    // 开关配置
    $sql = "SELECT value FROM ecs_shop_config WHERE code = 'callcenter_switch' LIMIT 1 ";
    $mask_phone_config['switch'] = $db->getOne($sql);
    
    // 有效用户配置
    $sql = "SELECT value FROM ecs_shop_config WHERE code = 'callcenter_include_user' LIMIT 1 ";
    $user = $db->getOne($sql);
    if (trim($user) != '') {
        $users = explode(",", $user);
        $mask_phone_config['user'][0] = true;
        $sql = "SELECT user_id FROM ecs_users WHERE ". db_create_in($users, "user_name");
        $_users = $db->getCol($sql);
        foreach ($_users as $res) {
            $mask_phone_config['user'][trim($res)] = true;
        }
    }
    //短信是否回复
    $sql = "SELECT value FROM ecs_shop_config WHERE code = 'sms_cod_message' LIMIT 1";
    $message = $db->getOne($sql);
    if (trim($message) != '') {
        $message = explode(",", $message);
        foreach ($message as $res) {
            $mask_phone_config['sms_cod_message'][trim($res)] = true;
        }
    }
    return $mask_phone_config;
}

/**
 * 得到打印的电话号码
 *
 * @param unknown_type $order
 */
function get_mask_phone(&$order) {
    global $_CFG;
    $tel = $order['tel'];
    //$phones = split("-", $tel);
    $phones = explode("-", $tel);
    if ($order['province'] == 10) {
        $callcenter_phone = $_CFG['callcenter_phone_sh'];
    } else if ($_CFG['callcenter_phone']) {
        $callcenter_phone = $_CFG['callcenter_phone'];
    } else {
        $callcenter_phone = '4008206206';
    }
    // 修改屏蔽号码
    if (strlen($order['mask_mobile']) == 8) {
        if (trim($order['mobile']) == "" && count($phones) > 2) {
            $depart = "再转{$phones[2]}";
        }
        $order['mobile'] = "{$callcenter_phone}转分机{$order['mask_mobile']}{$depart}";
    }
    if (strlen($order['mask_tel']) == 8) {
        if (count($phones) > 2) {
            $depart = "再转{$phones[2]}";
        }
        $order['tel'] = "{$callcenter_phone}转分机{$order['mask_tel']}{$depart}";
    }
    // 重置屏蔽号码
    unset($order['mask_tel']);
    unset($order['mask_mobile']);
}

/**
 * 通过订单号搜索屏蔽号码
 *
 * @param unknown_type $no_status
 * @param array $order_sns
 * @return unknown
 */
function search_mask_phone_by_sns($no_status = 'A', array $order_sns = null) {
    global $db;
    $sql_condition = "";
    if ($no_status != null && trim($no_status) != "") {
        $sql_condition .= " AND no_status = '{$no_status}' ";
    }
    if ($order_sns != null && !empty($order_sns)) {
        $sql_condition .= " AND order_sn IN ( '". join("','", $order_sns). "' ) ";
    }
    $sql = "SELECT mask_phone_no, cus_phone_no, created_time, actived_time, no_status, order_sn
         FROM callcenter_mask_phone
         WHERE 1 {$sql_condition} ";
    $fields_value = array();
    $ref = array();
    // 获得以order_sn为键值的array
    $db->getAllRefby($sql, array('order_sn'), $fields_value, $ref);
    return $ref['order_sn'];
}
?>
