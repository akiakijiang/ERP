<?php
/*
 * OUKU 镖局库
*/
function get_biaoju_rpc_context() {
  global $biaoju_rpc_host, $biaoju_rpc_path, $biaoju_rpc_port;
  return new RpcContext($biaoju_rpc_host, $biaoju_rpc_path, $biaoju_rpc_port);
}


function cart_biaoju_stores($type = CART_GENERAL_GOODS) {
    $sql = "(SELECT 0 as biaoju_store_id" .
            " FROM " . $GLOBALS['ecs']->table('cart') ." as c ".
            " WHERE c.session_id = '" . SESS_ID . "' " .
            " AND c.rec_type = '$type' ".
            " AND c.biaoju_store_goods_id = 0 limit 1) UNION ALL (".
    		" SELECT distinct b.store_id as biaoju_store_id " .
            " FROM " . $GLOBALS['ecs']->table('cart') ." as c, bj_store_goods as b ".
            " WHERE c.session_id = '" . SESS_ID . "' " .
            " AND c.rec_type = '$type' ".
            " AND c.biaoju_store_goods_id = b.store_goods_id".
    		" order by b.store_id)";
   return $GLOBALS['db']->getAll($sql);
}

function clear_biaoju_cart($type = CART_GENERAL_GOODS, $store_id) {
    $sql = "select c2.rec_id from " . $GLOBALS['ecs']->table('cart') .
            " as c2, bj_store_goods g where c2.biaoju_store_goods_id = g.store_goods_id ".
    		" and c2.session_id = '" . SESS_ID . "' AND c2.rec_type = '$type'".
    		" and g.store_id=".$store_id;
    $arr = $GLOBALS['db']->getAll($sql);
    
    $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') ." WHERE rec_id in (";
    $empty = true;
    foreach ($arr as $key => $value) {
    	if ($empty) $empty = false;
    	else $sql = $sql.",";
        $sql = $sql.$value["rec_id"];
    }
    $sql = $sql.")";
    if (!$empty) $GLOBALS['db']->query($sql);
}


function cart_biaoju_goods($type = CART_GENERAL_GOODS, $bj_store_id=-1) {
    $sql = "SELECT b.store_goods_id biaoju_store_goods_id, b.store_id biaoju_store_id, s.name biaoju_store_name, ".
    		"c.rec_id, c.user_id, c.goods_id, c.goods_name, c.goods_sn, c.goods_number, " .
            "c.market_price, b.price shop_price, g.market_price, c.goods_attr, c.is_real, c.parent_id, c.is_gift, b.status bj_status " .
            "c.can_handsel, b.price * c.goods_number as subtotal " .
            "FROM " . $GLOBALS['ecs']->table('cart') ." as c ,  ". $GLOBALS['ecs']->table('goods').
			" as g , bj_store_goods as b, bj_store as s ".
            " WHERE g.goods_id = c.goods_id and c.session_id = '" . SESS_ID . "' " .
            " AND c.rec_type = '$type'".
            " AND b.store_id = s.store_id ".
            " AND c.biaoju_store_goods_id = b.store_goods_id ".
    		($bj_store_id == -1 ? "" : (" AND b.store_id='".$bj_store_id."' ")).
    		" order by s.store_id, c.goods_name";

    $arr = $GLOBALS['db']->getAll($sql);
    foreach ($arr as $key => $value) {
        $arr[$key]['formated_market_price'] = price_format($value['market_price']);
       	$arr[$key]['formated_goods_price']  = price_format($value['shop_price']);
   		$arr[$key]['formated_subtotal']     = price_format($value['subtotal']);
   		$arr[$key]['is_on_sale']     = $value['bj_status'] == "ON_SALE"? 1 : 0;
    }
    return $arr;
}

function check_cart_biaoju_goods($bj_store_id) {
    $sql = "SELECT 1 " .
            "FROM " . $GLOBALS['ecs']->table('cart') ." as c ,  ". $GLOBALS['ecs']->table('goods').
			" as g , bj_store_goods as b ".
            " WHERE g.goods_id = c.goods_id and c.session_id = '" . SESS_ID . "' " .
            " AND c.biaoju_store_goods_id = b.store_goods_id";
    if ($bj_store_id > 0) $sql = $sql." AND b.store_id=".$bj_store_id;
    		
    return $GLOBALS['db']->getOne($sql) != 0;
}


function show_biaoju_cart_goods() {
     // 循环、统计 
    $sql = "SELECT c.rec_id, b.store_id as biaoju_store_id, s.name biaoju_store_name, b.store_goods_id biaoju_store_goods_id, b.subtitle biaoju_subtitle, c.parent_id, ".
    			" c.goods_price, c.goods_id ,c.rec_id, c.goods_number , b.price shop_price ,g.goods_name,c.goods_sn, g.goods_thumb, b.price * c.goods_number as subtotal, g.addtional_shipping_fee " .
				" from  ". $GLOBALS['ecs']->table('cart') ." as c, ". $GLOBALS['ecs']->table('goods')." as g, bj_store_goods as b, bj_store as s ".
				" WHERE g.goods_id = c.goods_id ".
				" and c.biaoju_store_goods_id = b.store_goods_id ".
				" and b.store_id = s.store_id ".
    			" and session_id = '" . SESS_ID . "' order by b.store_id, g.goods_name" ;
    $arr = $GLOBALS['db']->getAll($sql);
    
	$bjStores = array();
	$storeId = 0;
	$storeGoods = null;
    $totalPrice = 0;
    $totalAddtionalFee = 0;
    
    $hasFitting = False;
    
    foreach ($arr as $key => $value) {
    	if ($storeId != $value["biaoju_store_id"]) {
    		if ($storeGoods != null) $bjStores[] = $storeGoods;
    		$storeId = $value["biaoju_store_id"];
    		$storeGoods = array("storeId"=>$storeId,"storeName"=>$value["biaoju_store_name"], "total"=>0, "total_addtional_shipping_fee"=>0, "goodsList"=>array());
    	}
    
    	if ($value['parent_id'] > 0) { //配件
    		$parent =  $GLOBALS['db']->getRow("select * from ". $GLOBALS['ecs']->table('cart') ." where rec_id=".$value['parent_id'] );
    		if ($parent) {
		    	if ($parent["biaoju_store_goods_id"] == 0) {
		    		$sql = 'SELECT goods_price FROM ' . $GLOBALS['ecs']->table('group_goods') . " WHERE goods_id = {$value["biaoju_store_goods_id"]} and child_store_id={$value["biaoju_store_id"]} AND parent_id = {$parent["goods_id"]} AND parent_store_id=0";
		    	} else {
		    		$sql = 'SELECT goods_price FROM ' . $GLOBALS['ecs']->table('group_goods') . " WHERE goods_id = {$value["biaoju_store_goods_id"]} and child_store_id={$value["biaoju_store_id"]} AND parent_id = {$parent["biaoju_store_goods_id"]} AND parent_store_id > 0";
		    	}
			  $price = $GLOBALS['db']->getRow($sql);
			  if ($price) {
		        /* 配件存在，按照套餐价格显示 */
			    $value['shop_price']  = $price["goods_price"];
	   		    $value['subtotal']     = $price["goods_price"] * $value["goods_number"];
			  }
			  $hasFitting = True;
    		}
    	}
    	
       	$totalPrice	=	$totalPrice + $value['subtotal'];
       	$totalAddtionalFee = $totalAddtionalFee + $value['addtional_shipping_fee'] * $value['goods_number'];
       	$storeGoods["total"] = $storeGoods["total"] + $value['subtotal'];
       	$storeGoods["total_addtional_shipping_fee"] = $storeGoods["total_addtional_shipping_fee"] + $value['addtional_shipping_fee'];
       	
   		$value['subtotal_formatted'] 			= price_format($value['subtotal']);
   		$value['shop_price_formatted']  		= price_format($value['shop_price']);
   		$storeGoods["goodsList"][] = $value;
    }
	if ($storeGoods != null) $bjStores[] = $storeGoods;
    
    foreach ($bjStores as $key => $value) {
        $bjStores[$key]["total_formatted"] = price_format($value["total"]);
    }
    

    /* 如果有配件，重新排序 */
    if ($hasFitting) {
      foreach ($bjStores as $i => $store) {
        $bjStores[$i]["goodsList"] = sort_array_tree($bjStores[$i]["goodsList"] , "rec_id", "parent_id") ;
      }
    }
    
    $arCartList = array();
	$arCartList['store_list']	=	$bjStores;
	$arCartList['total_formatted']	=	price_format($totalPrice);
	$arCartList['total']	=	$totalPrice;
	$arCartList['limit_bonus']	=	0; /* 镖局商家不能使用红包 */
	$arCartList['total_addtional_shipping_fee']	=	$totalAddtionalFee;
    return $arCartList;
}


/*
 * 加入镖局的商品
 * 目前镖局只支持添加自己的配件
 */
function addto_cart_biaoju($goods_id, $store_goods_id, $num = 1, $fittings = array()) {
    $GLOBALS['err']->clean();

    /* 取得商品信息 */
      $sql = "SELECT g.goods_id, g.goods_sn, g.goods_name, g.market_price, bg.price goods_price," .
                    " g.is_real, g.extension_code, g.can_handsel, g.is_on_sale, bg.store_goods_id" .
                " FROM  bj_store_goods AS bg, " .
      			$GLOBALS['ecs']->table('goods'). " as g ".
                " WHERE bg.store_goods_id = $store_goods_id ".
                " AND bg.goods_id = g.goods_id" .
                " AND g.is_delete = 0" .
                " AND bg.status = 'ON_SALE' " .
                " AND g.goods_id =$goods_id ";
      			
    $goods = $GLOBALS['db']->GetRow($sql);
    
    if (empty($goods)) {
        $GLOBALS['err']->add($GLOBALS['_LANG']['goods_not_exists'], ERR_NOT_EXISTS);
        return false;
    }
    
    /* 检查配件 */
    $fittings = is_array($fittings) ? $fittings : array();
    $fittingGoods = array();
    foreach ($fittings as $key => $fitting) {
      $sql = "SELECT g.goods_id, g.goods_sn, g.goods_name, g.market_price, gg.goods_price goods_price," .
                    " g.is_real, g.extension_code, g.can_handsel, g.is_on_sale, bg.store_goods_id" .
                " FROM  ".$GLOBALS['ecs']->table('group_goods')."  AS gg,  bj_store_goods AS bg, " .
      			$GLOBALS['ecs']->table('goods'). " as g ".
                " WHERE ".
                " gg.goods_id = bg.store_goods_id" .
                " AND gg.parent_id = '$store_goods_id'".
                " AND gg.group_goods_id = ". intval($fitting[0]) .
                " AND bg.goods_id = g.goods_id" .
                " AND g.is_delete = 0" .
                " AND bg.status = 'ON_SALE' ";
                           
      $subgoods = $GLOBALS['db']->getRow($sql);
      if (empty($subgoods)) {
          $GLOBALS['err']->add($GLOBALS['_LANG']['goods_not_exists'], ERR_NOT_EXISTS);
          return false;
      }
      $subgoods["goods_number"] = intval($fitting[1]);
      $fittingGoods[] = $subgoods;
    }
    
    /* 初始化要插入购物车的基本件数据 */
    $parent = array(
        'user_id'       => $_SESSION['user_id'],
        'session_id'    => SESS_ID,
        'goods_id'      => $goods_id,
        'goods_sn'      => addslashes($goods['goods_sn']),
        'goods_name'    => addslashes($goods['goods_name']),
        'market_price'  => $goods['market_price'],
        'goods_attr'    => '',
        'is_real'       => $goods['is_real'],
        'extension_code'=> $goods['extension_code'],
        'is_gift'       => 0,
        'goods_price'   => $goods['goods_price'],
        'goods_number'   => $num,
        'parent_id'   => 0,
        'can_handsel'   => $goods['can_handsel'],
        'rec_type'      => CART_GENERAL_GOODS,
        'biaoju_store_goods_id' => $store_goods_id
    );
    
    $parent_id = 0;

	/* 如果已经存在，那么更新数量 */
    $sql = "SELECT rec_id FROM " . $GLOBALS['ecs']->table('cart') .
                " WHERE goods_id = " . intval($parent["goods_id"]) .
    			" AND session_id = '".SESS_ID."'".
	            " AND biaoju_store_goods_id = " . $parent['biaoju_store_goods_id'].
                " AND parent_id = 0";
	    
    $cartgoods = $GLOBALS['db']->getRow($sql);
    if (empty($cartgoods)) {
   		/* 插入基本件 */
    	$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart'), $parent, 'INSERT');
    	$parent_id = $GLOBALS['db']->insert_id();
    } else {
	    /* 更新基本件 */
	    // 如果有套餐的情况下基本件数字规定为1 by ychen 2008/06/04
    	$parent_id = $cartgoods["rec_id"];
    	if (count($fittingGoods) == 0) {
	    	$sql = "
	    		UPDATE {$GLOBALS['ecs']->table('cart')}
	    		SET  goods_number = goods_number + {$parent['goods_number']}
	    		WHERE rec_id = '{$cartgoods['rec_id']}'
	    	";
    	} else {
 	    	$sql = "
	    		UPDATE {$GLOBALS['ecs']->table('cart')}
	    		SET  goods_number = '{$parent['goods_number']}'
	    		WHERE rec_id = '{$cartgoods['rec_id']}'
	    	";   		
    	}
	    $GLOBALS['db']->query($sql);
    }
    
    /* 初始化要插入购物车的基本件数据 */
    $cart = array(
        'user_id'       => $_SESSION['user_id'],
        'session_id'    => SESS_ID,
        'goods_attr'    => '',
        'is_gift'       => 0,
        'rec_type'      => CART_GENERAL_GOODS,
        'parent_id'  => $parent_id
    );
    
    /* 插入配件 */
    foreach ($fittingGoods as $key => $fitting) {
        $cart['goods_number'] = $fitting["goods_number"];
        $cart['goods_id'] = $fitting["goods_id"];
        $cart['goods_sn'] = $fitting["goods_sn"];
        $cart['goods_name'] = $fitting["goods_name"];
        $cart['market_price'] = $fitting["market_price"];
        $cart['goods_price'] = $fitting["goods_price"];
        $cart['is_real'] = $fitting["is_real"];
        $cart['extension_code'] = $fitting["extension_code"];
        $cart['can_handsel'] = $fitting["can_handsel"];
        $cart['biaoju_store_goods_id'] = $fitting["store_goods_id"];
        
        /* 如果已经存在，那么更新数量 */
	    $sql = "SELECT rec_id FROM " . $GLOBALS['ecs']->table('cart') .
	                " WHERE goods_id = " . intval($fitting["goods_id"]) .
    				" AND session_id = '".SESS_ID."'".
	                " AND biaoju_store_goods_id = " . $cart['biaoju_store_goods_id'].
	                " AND parent_id = " . $cart['parent_id'];
	    $cartgoods = $GLOBALS['db']->getRow($sql);
	    if (empty($cartgoods)) {
        	$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart'), $cart, 'INSERT');
	    } else {
	    	$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') .
	                " SET  goods_number = goods_number + ". $cart['goods_number'] .
	                " WHERE rec_id = " . $cartgoods['rec_id'];
		    $GLOBALS['db']->query($sql);
	    }
    }
    
    // {{{ 本来返回true，现在改为返回$goods_name，为了得到添加的商品信息 by ychen 08/02/15
	return $goods['goods_name'];
    // }}}
}

function get_stores($cart_goods) {
  $total = 0;
  $last = -1;
  foreach ($cart_goods as $key => $value) {
    if ($last != $value['biaoju_store_id']) 
    	$total = $total + 1;
    $last = $value['biaoju_store_id'];
  }
  return $total;
}

function get_store_list_by_goodsId($goods_id) {
	$sql = "SELECT * FROM bj_store_goods g, bj_store s WHERE g.store_id = s.store_id AND g.status = 'ON_SALE' AND s.status = 'OK' AND g.goods_id = '$goods_id'";
	$stores = $GLOBALS['db']->getAll($sql);
	return $stores;
}

function get_biaoju_goods_by_store_goodsId($store_goods_id) {
    $sql = "SELECT * FROM bj_store_goods g WHERE g.store_goods_id = '$store_goods_id'";
    $info = $GLOBALS['db']->getRow($sql);
    return $info;
}


function get_recommend_goods_by_storeId($store_id, $num = 5) {
	$sql = "SELECT g.price, g.store_goods_id, ecsg.goods_name, ecsg.goods_thumb, g.subtitle FROM bj_recommend r, bj_store_goods g, ". $GLOBALS['ecs']->table('goods') . " ecsg WHERE r.store_goods_id = g.store_goods_id AND g.status = 'ON_SALE' AND g.goods_id = ecsg.goods_id AND  g.store_id = '$store_id' order by r.sequence limit $num";
	$goods = $GLOBALS['db']->getAll($sql);
	return $goods;
}

function clear_cart_by_ids($rec_ids) {
  if (count($rec_ids) == 0) return;
  $sql = "delete from " . $GLOBALS['ecs']->table('cart') ." WHERE rec_id in (";
  $started = false;
  foreach ($rec_ids as $rec_id) {
    if ($started) $sql = $sql.",";
    $started = true;
    $sql = $sql.$rec_id;
  }
  $sql = $sql.")";
  $GLOBALS['db']->query($sql);
}

function get_store_list() {
	$sql = "SELECT *, COUNT(g.store_goods_id) AS goods_count FROM bj_store AS s, bj_store_goods AS g WHERE s.status = 'OK' and g.status='ON_SALE' AND s.store_id = g.store_id GROUP BY s.store_id ORDER BY s.store_id";
	return $GLOBALS['db']->getAll($sql);
}

/**
 * 获取店铺的商品数目
 * TODO: 考虑商品状态, 只显示上架的 ?
 * @param $store_id 
 * @return int 该店铺的商品数目
 */
function get_store_goods_num($store_id, $status = null)
{
    if($status === null)
    {
        $sql = "SELECT COUNT(*) FROM bj_store_goods g WHERE g.store_id = '$store_id'";
    }
    else
    {
        $sql = "SELECT COUNT(*) FROM bj_store_goods g WHERE g.store_id = '$store_id' AND status = '$status'";
    }
    return $GLOBALS['db']->getOne($sql);
}
?>
