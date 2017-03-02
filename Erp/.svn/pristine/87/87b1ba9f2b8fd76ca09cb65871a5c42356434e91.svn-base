<?php 
require_once ('includes/debug/lib_log.php');

/**
 * 取得分销商品供价
 *
 * @param int $goods_id 
 * @param int $style_id
 * 
 * @return float
 */
function distribution_get_provide_price($goods_id, $style_id = 0)
{
    $sql = "SELECT price FROM provide_price WHERE goods_id = ". intval($goods_id) ." AND style_id = ". intval($style_id);
    $result = $GLOBALS['db']->getOne($sql);
    return $result ? $result : 0 ;
}

/*
 * 取得分销商品的供应商
 * 
 * @param int $goods_id
 * @param int $style_id
 * 
 * return array
 */
function distribution_get_provider($goods_id, $style_id = 0)
{
    global $db, $ecs;
    
    $sql = "SELECT provider_id FROM provide_price WHERE goods_id = ". intval($goods_id) ." AND style_id = ". intval($style_id);
    $provider_id = $db->getOne($sql);
    if ($provider_id) {
	    $sql = "SELECT provider_id, provider_name, provider_code FROM {$ecs->table('provider')} WHERE provider_status = 1 AND provider_id = {$provider_id}";
	    return $db->getRow($sql);
    }
    return array();
}

/**
 * 取得分销商品售价
 *
 * @param int $goods_id 
 * @param int $style_id
 * @param datetime $time 启用时间
 * 
 * @return float
 */
function distribution_get_sale_price($distributor_id, $goods_id, $style_id = 0, $time = NULL)
{
    if (is_null($time)) { $time = date('Y-m-d H:i:s'); }
    $sql = "
        SELECT price 
        FROM distribution_sale_price 
        WHERE distributor_id = ". intval($distributor_id) ." AND goods_id = ". intval($goods_id) ." AND style_id = ". intval($style_id) ." AND '{$time}' >= valid_from
        ORDER BY valid_from DESC    
    ";
    $result = $GLOBALS['db']->getOne($sql, true);
    return $result ? $result : 0 ;
}

/**
 * 取得分销商品的销售折扣价
 *
 * @param int $distribution_id
 * @param int $goods_id
 * @param int $style_id
 * @param datetime $time  启用时间
 *
 * @return float
 */
function distribution_get_sale_rebate($distributor_id, $goods_id, $style_id = 0, $time = NULL)
{
    // 如果没有指定启用时间，则以最新的启用了的价格为准
    if (is_null($time)) { $time = date('Y-m-d H:i:s'); }
    $sql = "
        SELECT rebate_amount
        FROM distribution_sale_price
        WHERE distributor_id = ". intval($distributor_id) ." AND goods_id = ". intval($goods_id) ." AND style_id = ". intval($style_id) ." AND '{$time}' >= valid_from
        ORDER BY valid_from DESC
    ";
    $result = $GLOBALS['db']->getOne($sql, true);
    return $result ? $result : 0 ;
}

/**
 * 取得套餐及其明细
 * 
 * @param int $pkv 套餐记录的主键
 * @param string $code 套餐编码
 * @param datetime $time 启用时间
 */
function distribution_get_group_goods($pkv = NULL, $code = NULL, $time = NULL)
{
    global $db, $ecs;
    
    if (!is_null($pkv)) {
        $group = $db->getRow("SELECT * FROM distribution_group_goods WHERE group_id = ". intval($pkv), true);
    } else {
        if (is_null($time)) { $time = date('Y-m-d H:i:s'); }
        $group = $db->getRow("
            SELECT * FROM distribution_group_goods
            WHERE code = '". $db->escape_string($code) ."' AND '{$time}' >= valid_from
            ORDER BY valid_from DESC LIMIT 1
        ");
    }
    
    if ($group) {
        // 套餐商品类表
        $item_list = $db->getAll("
            SELECT gg.*, g.goods_party_id, g.is_on_sale FROM distribution_group_goods_item AS gg
            LEFT JOIN {$ecs->table('goods')} AS g ON g.goods_id = gg.goods_id 
            WHERE group_id = '{$group['group_id']}'
        ");
        // 如果有商品下架了， 订单就不能导入
        $is_on_sale = true ;
        foreach ($item_list as $goods_item) {
        	// 判断商品下架
        	if ($goods_item['is_on_sale'] == '0') {
        		$is_on_sale = false ;
        	}
        }
        
        if ($is_on_sale) {
            $group['item_list'] = $item_list;	
        } else {
        	$group['item_list'] = null ;
        }
        
    }
    
    return $group;
}

function distribution_price_check($goods_code, $total_amount = 0){
	global $db ;
	
	$group_goods = $db->getRow(sprintf("select code, amount from ecshop.distribution_group_goods where code = '%s' limit 1  ;", $goods_code));
	if(empty($group_goods)){
		return false ;
	}else{
		if(floatval($total_amount) !== floatval($group_goods['amount'])){
			return false;
		}else{
			return true;
		}
	}
	
}

/**
 * 取得在售商品, 返回一条分销商品记录, ‘shop_price’为售价
 * 
 * @return array
 */
function distribution_get_goods($goods_id, $style_id = '')
{ 
    // 取得商品信息
    $sql = "
        SELECT 
            g.goods_id, g.goods_party_id, g.goods_name, g.goods_sn, g.market_price, g.shop_price, 
            g.is_real, g.extension_code, g.provider_id 
        FROM 
            {$GLOBALS['ecs']->table('goods')} AS g  
        WHERE 
            g.is_on_sale = 1 AND g.is_delete = 0 AND g.goods_id = '{$goods_id}'
    ";        
    $goods = $GLOBALS['db']->getRow($sql, true); 
    
    if ($goods) {
       	if ($style_id > 0) {
            $sql = "
                SELECT 
                    IF(gs.goods_color = '', s.color, gs.goods_color) AS color, 
                    gs.style_price, gs.sale_status, s.style_id, s.value
                FROM {$GLOBALS['ecs']->table('goods_style')} AS gs 
                    INNER JOIN {$GLOBALS['ecs']->table('style')} AS s ON gs.style_id = s.style_id
                WHERE gs.goods_id = '{$goods_id}' AND s.style_id = '{$style_id}'
            ";
            $style = $GLOBALS['db']->getRow($sql);
	       
            if ($style) { 
                if ($style['style_price'] > 0) { 
                $goods['shop_price'] = $style['style_price']; }  // 修正商品价格
                $goods['goods_name']  = $goods['goods_name'].' '.$style['color'];  // 商品名
                $goods['style_id'] = $style['style_id'];
            } else {
                return false;  // 如果该颜色下架了
            }
        } else {
            $goods['style_id'] = 0;
        }
    }
    
    return $goods;
}

/**
 * 取得分销订单的商品
 * 
 * @param int $order_id 订单号
 * 
 * @return array
 */
function distribution_get_order_goods($order_id)
{
    global $db, $ecs;
    
    // 取得订单的商品
    $sql = "
        SELECT 
            og.rec_id, og.goods_id, og.style_id, og.goods_name, og.goods_number,ii.serial_number,
            og.customized, og.goods_price * og.goods_number as goods_amount, g.top_cat_id, g.cat_id
        FROM
            {$ecs->table('order_goods')} AS og 
            LEFT JOIN romeo.inventory_item_detail iid ON iid.order_goods_id = convert(og.rec_id using utf8)
            LEFT JOIN romeo.inventory_item ii ON iid.inventory_item_id = ii.inventory_item_id
            LEFT JOIN {$ecs->table('goods')} g on og.goods_id = g.goods_id 
        WHERE
            og.order_id = '{$order_id}'
	";
    $ref_field = $ref = array();
    $db->getAllRefby($sql, array('rec_id'), $ref_field, $ref, false);

    $goods_list = array();
    foreach ($ref['rec_id'] as $group) {
        $g = reset($group);
        $k = $g['rec_id'];
        $goods_list[$k] = $g;
		
        // 有串号控制的商品
        if (getInventoryItemType($g['goods_id']) == 'SERIALIZED') {
            // 取得商品串号
            foreach ($group as $item) {
			    if (!empty($item['serial_number'])) {
                    $_serial_number[] = '('.$item['serial_number'].')';   
			    }
            }
            if (!empty($_serial_number)) {
                $goods_list[$k]['serialNumber'] = implode('<br />', $_serial_number);			    
            }
        }
        // 无串号控制商品
        else {
            // 取得配件编号
            $goods_list[$k]['productCode'] = encode_goods_id($g['goods_id'], $g['style_id']);
			
            // 定制图案
            $attr = get_order_goods_attribute($g['rec_id']);
            if ($attr) {
                $goods_list[$k]['customize'] = $attr;	
            }
        }
		
        // 是否有定制信息
        $goods_list[$k]['goods_name'] .= get_customize_type($g['customized'], true);
    }
	
    return $goods_list;
}

/**
 * 取得指定分类的在售商品列表
 * 
 * @param int $top_cat_id 分类id
 * @param int $cat_id 分类id
 * @param string $keyword 关键字
 * @param int $limit 限定记录数
 * 
 * @return array
 */
function distribution_get_goods_list($top_cat_id = 0, $cat_id = 0, $keyword = '', $limit = 100)
{
    $conditions = '';
    if ($top_cat_id > 0) {
       $conditions = " AND g.top_cat_id = '{$top_cat_id}'";
    }
    if ($cat_id > 0) {
       $conditions .= " AND g.cat_id = '{$cat_id}'";
    }
    if (trim($keyword)) {
        $keyword = mysql_like_quote($keyword);
        $conditions .= " AND g.goods_name LIKE '%{$keyword}%'"; 
    }                
    
    $sql = "
        SELECT 
            g.goods_id, g.cat_id, gs.style_id, 
            CONCAT_WS(' ', g.goods_name, IF( gs.goods_color = '', s.color, gs.goods_color) ) as goods_name
        FROM 
            {$GLOBALS['ecs']->table('goods')} AS g 
            LEFT JOIN {$GLOBALS['ecs']->table('goods_style')} AS gs ON gs.goods_id = g.goods_id
            left join {$GLOBALS['ecs']->table('style')} as s on gs.style_id = s.style_id
        WHERE 
            ( g.is_on_sale = 1 AND g.is_delete = 0 ) AND ". party_sql('g.goods_party_id') ." {$conditions}
        LIMIT {$limit}
    ";
    return $GLOBALS['db']->getAll($sql);
}

/**
 * cyj
 * 取得支付方式
 */
function distribution_get_payments_list($keyword = '', $limit = 100)
{
    $conditions = '';
 
    if (trim($keyword)) {
        $keyword = mysql_like_quote($keyword);
        $conditions .= " AND pay_name LIKE '%{$keyword}%'"; 
    }                
    
    $sql = "
        SELECT 
            pay_id,pay_name
        FROM 
            {$GLOBALS['ecs']->table('payment')} 
        WHERE 
            (enabled = 1 OR enabled_backend = 'Y') {$conditions} ORDER BY pay_order 
        LIMIT {$limit}
    ";
    return $GLOBALS['db']->getAll($sql);
}

/**
 * 通过关键字
 * 取得分销商
 */
function distribution_get_select_distributor_list($keyword = '',$limit = 40)
{
	$conditions = '';
	if (trim($keyword)) {
		$keyword = mysql_like_quote($keyword);
		$conditions .= " AND name LIKE '%{$keyword}%' ";
	}
	$sql = "
	SELECT
	distributor_id,name,party_id
	FROM distributor
	WHERE
	status = 'NORMAL' {$conditions} AND ". party_sql('party_id') ."
	LIMIT {$limit}
	";
	return $GLOBALS['db']->getAll($sql);
}

/**
 * 通过关键字
 * 取得配送方式
 */
function distribution_get_select_shipping_list($keyword = '',$limit = 40)
{
	$conditions = '';
	if (trim($keyword)) {
		$keyword = mysql_like_quote($keyword);
		$conditions .= " AND shipping_name LIKE '%{$keyword}%' ";
	}
	$sql = "
	SELECT
	shipping_id,shipping_name
	FROM {$GLOBALS['ecs']->table('shipping')} 
	WHERE enabled = 1  {$conditions} ORDER BY shipping_code, support_cod
	LIMIT {$limit}
	";
	
	return $GLOBALS['db']->getAll($sql);
}

/**
 * 取得分销店铺，可以指定分销商店铺名，或者指定分销商
 * 
 * @param int 分销商id，不指定则按用户所属组织返回所有的列表 
 * @param int 分销商
 */
function distribution_get_distributor_list($id = null, $main_distributor_id = null)
{
    $fields = 'distributor_id, party_id, name, tel, contact, address, alipay_account, is_taxpayer, abt_print_invoice, abt_logo_style, abt_change_price';
    
    if (is_numeric($id)) {
        $sql = "SELECT {$fields} FROM distributor WHERE status = 'NORMAL' AND distributor_id = '{$id}' AND ". party_sql('party_id');
        return $GLOBALS['db']->getRow($sql, true);
    } 
    elseif (is_numeric($main_distributor_id)) {
        $sql = "SELECT {$fields} FROM distributor WHERE status = 'NORMAL' AND main_distributor_id = '{$main_distributor_id}' AND ". party_sql('party_id');
        return $GLOBALS['db']->getAll($sql);    
    }
    else {
        $sql = "SELECT {$fields} FROM distributor WHERE status = 'NORMAL' AND ". party_sql('party_id');
        return $GLOBALS['db']->getAll($sql);   
    }
}
/**
 * 取得直销列表
 */
function distribution_get_zhi_distributor_list()
{
	
   $sql = "SELECT * FROM `distributor` d
			LEFT JOIN main_distributor m ON d.main_distributor_id = m.main_distributor_id WHERE type = 'zhixiao' AND ".party_sql('d.party_id');
   return $GLOBALS['db']->getAll($sql);
}
/**
 * 取得分销商列表
 * 
 * @return array
 */
function distribution_get_main_distributor_list()
{
   $sql = "SELECT * FROM `distributor` d
			LEFT JOIN main_distributor m ON d.main_distributor_id = m.main_distributor_id WHERE type = 'fenxiao' AND ".party_sql('d.party_id');
    return $GLOBALS['db']->getAll($sql);
}

/**
 * 生成分销销售订单
 * 
 * @param array 订单
 * @param array 订单商品，需要的键为： goods_id， style_id, goods_number, price
 * @param int 配送方式
 * @param string 订单类型 ， 为货到付款或非货到付款  COD | NON-COD
 * 
 * @return string 订单sn
 */
function distribution_generate_sale_order($order, $order_goods, $carrier_id = 0, $order_type = '', & $msg)
{  
    global $db, $ecs;
    $result = array();
    // 添加事务控制
    $db->start_transaction();
    try {
	    // 默认属性
	    $_order_default = array
	    (
	    	'party_id'        => PARTY_LEQEE_MOBILE,
	    	'pay_id'          => 0,   // 支付方式
	        'pack_fee'        => 0,   // 包装费
	        'insure_fee'      => 0,   // 保障费
	        'inv_payee'       => '',  // 发票抬头        
	        'consignee'       => '',  // 收货人
	        'country'         => 1,   // 中国   
	        'order_status'    => 0,   // 订单状态
	        'shipping_status' => 0,   // 配送状态
	        'pay_status'      => 0,   // 支付状态
	        'bonus_value'     => 0,   // 红包使用
	        'integral_money'  => 0,   // 欧币
	        'zipcode'         => '',  // 邮编
	        'special_type_id' => 'NORMAL',  // 是否为特殊订单
	        'order_type_id'   => 'SALE',    // 订单类型，默认为销售订单
	        'is_display'      => 'Y',       // 是否显示给客服
	        'need_invoice'    => 'Y',       // 默认打印发票
	    	'user_id'         => 1,  
	    	'nick_name'       => '', //taobao_urer_name
	    	'bonus'           => 0,
	    	'source_type'     => ''
	    );
	    $order = array_merge($_order_default, (array)$order);
	    
	    if($order['taobao_user_id']) {
	    	$order['nick_name'] = $order['taobao_user_id'];
	    }
	    
	    //如果没有facilityId，给他分配
	    if (!$order['facility_id']) {
	        $order['facility_id'] = assign_order_facility($order, $order_goods);
	    }
	    
	    do
	    {
	        // 验证
	        if (!is_numeric($order['distributor_id'])) {
	            $result['error'] = '没有选择分销商';
	            break;   
	        }
	    	
	        if (!is_numeric($order['shipping_id'])) {
	            $result['error'] = '没有选择配送方式';
	            break;   
	        }
	        
	        if (!is_numeric($order['province']) || !is_numeric($order['city']) ) {
	            $result['error'] = '没有选择收货地址';
	            break;
	        }
	        
	    	// 检查外部订单号
	        if (!empty($order['taobao_order_sn'])) {
	            if ($order['outer_type'] == '-1') {
	                $result['error'] = "请先选择外部订单类型才能录入外部订单号";
	                break;
	            } else {
	                $exists = $db->getOne("
	                    SELECT 1 FROM {$ecs->table('order_info')} AS o INNER JOIN order_attribute AS a ON a.order_id = o.order_id
	                    WHERE a.attr_name = 'OUTER_TYPE' AND a.attr_value = '{$order['outer_type']}' AND o.taobao_order_sn = '{$order['taobao_order_sn']}'
	                ");
	                if ($exists) {
	                    $result['error'] = '该外部订单号已经存在了';
	                    break;                
	                }
                    //source_type, Sinri Edogawa, 20150921
                    $order['source_type']=$order['outer_type'];
	            }
	        }
	        
	        // 取得配送信息和承运信息
	        if (!$carrier_id) {
	            $region_id_list = array($order['country'], $order['province'], $order['city'], $order['district']);
	            $shipping = shipping_area_info($order['shipping_id'], $region_id_list);  
	            $carrier_id = $shipping['default_carrier_id'];          
	        } else {
	            $shipping = shipping_info($order['shipping_id']);
	        }
			
	        // 配送名称
	        $order['shipping_name'] = addslashes($shipping['shipping_name']);
	
	        // 取得支付方式名
	        if ($order['pay_id'] > 0) {
	            $payment = payment_info($order['pay_id']);
	            $order['pay_name'] = addslashes($payment['pay_name']);   
	        }
	        
	        // 运费
	        if (isset($order['shipping_fee'])) {
	            $order['shipping_fee'] = floatval($order['shipping_fee']);   
	        }
	       
	        if (count($order_goods) > 0)
	        {
	            $goods_amount = 0;        // 商品总金额
	            $goods_list   = array();  // 商品列表
	            foreach ($order_goods as $item) {
	                $g = distribution_get_goods(intval($item['goods_id']), intval($item['style_id']));  // 取得商品售价
	                if ($g) {
	                    $g['goods_number'] = intval($item['goods_number']);  // 商品数量
	                    if (isset($item['price']) && floatval($item['price']) >= 0) {  // 如果修改了价格的话
	                    	$g['shop_price'] = round((float)$item['price'], 6);
	                    }
	                    $subtotal = $g['shop_price'] * $g['goods_number'];
	                    $goods_amount += $subtotal;
	                    
	                    // 套餐
	                    $g['tc_code'] = isset($item['tc_code'])?$item['tc_code']:'';
	                    $g['group_code'] = isset($item['group_code'])?$item['group_code']:'';
	                    $g['group_name'] = isset($item['group_name'])?$item['group_name']:'';
	                    $g['group_number'] = isset($item['group_number'])?$item['group_number']:'0';
	                    $g['pay_amount'] = isset($item['pay_amount'])?$item['pay_amount']:0;
	                    $g['out_order_goods_id'] = isset($item['out_order_goods_id'])?$item['out_order_goods_id']:'';
	                    
	                    //商品级别红包
	                    $g['discount_fee'] = isset($item['discount_fee'])?$item['discount_fee']:0;
//	                    $order['bonus'] = $order['bonus'] - $g['discount_fee'];
	                    $goods_list[] = $g;	                    
	                } else {
	                	$result['error'] = "通过该编号{$item['goods_id']}#{$item['style_id']}找不到对应的商品";
	                    break 2 ;
	                }
	            }
	            
	            // 如果没有指定订单配送手续费，计算配送手续费
	            if (!isset($order['shipping_proxy_fee'])) {
	                $shipping_proxy_fee = 
	                    shipping_proxy_fee($shipping['shipping_code'], $shipping['configure'], 0, $goods_amount);
	                $order['shipping_proxy_fee'] = $shipping_proxy_fee;  // 配送手续费    
	            }
	            
	            // 如果没有指定订单配送费用，则计算配送运费
	            if (!isset($order['shipping_fee'])) {
	                $total_shipping_fee = 
	                    shipping_fee($shipping['shipping_code'], $shipping['configure'], 0, $goods_amount);
	                $order['shipping_fee'] = $total_shipping_fee;  // 配送总费用    
	            }
	            
	            $order['confirm_time']   = strtotime(date("Y-m-d H:i:s"));  // 确认订单时间
	            $order['order_time']   = date("Y-m-d H:i:s");  // 下单时间
	            $order['goods_amount'] = $goods_amount;  // 商品总金额
	            // 如果没有指定订单总金额，则计算订单总金额
	            if (!isset($order['order_amount'])) {
	                $order['order_amount'] = $order['shipping_fee'] + 
	                    max(0, $goods_amount - $order['bonus_value'] - $order['integral_money'] + $order['pack_fee'] + $order['bonus']);  // 订单总金额   
	            }
	  
	            // 插入配送面单记录
	            // $db->query("INSERT INTO {$ecs->table('carrier_bill')} (carrier_id, weight, send_address, receiver, phone_no) VALUES ('{$carrier_id}', 0, '', '', '')");
	            // $order['carrier_bill_id'] = $db->insert_id();
                $order['carrier_bill_id'] = '0'; 
	            
	            // 生成订单
	            $error_no = 0;
	            $order = array_map(array(& $db, 'escape_string'), $order);   // 订单头信息
	           
	            do {
	                // 如果是香港平世组织，则修改为其录入的币种
	                if ('65566' == $_SESSION['party_id']) {
	                   $order['currency'] = $_POST['currency']; // 货币
	                }
	                
	                //补寄订单order_sn=原订单order_sn+'-b'
	                if($order['type'] == 'SHIP_ONLY'){
	                	$b_order_sn =  $order['root_order_sn'].'-b'; 
                        $b_sql = "select count(1) from ecshop.ecs_order_info where order_sn like '{$b_order_sn}%'";
                        $b_num = $GLOBALS['db']->getOne($b_sql); 
                        if(empty($b_num)){
                        	$b_num = ""; 
                        }else{
                        	$b_num = intval($b_num); 
                        	$b_num = $b_num+1; 
                        }

	                	$order['order_sn'] = $order['root_order_sn'].'-b'.$b_num;
	                }else{
	                	$order['order_sn'] = get_order_sn();  // 生成订单sn
	                }
	                $db->autoExecute($ecs->table('order_info'), $order, 'INSERT', '', 'SILENT');
	                $error_no = $GLOBALS['db']->errno();
	                if ($error_no > 0 && $error_no != 1062) { 
	                    $result['error'] = '生成订单失败，错误代码：{$error_no}, 错误消息：'. $db->ErrorMsg();
	                    break 2;
	                }
	            } while ($error_no == 1062);
	            $order_id = $db->insert_id();
	            // 添加order_goods记录
	            foreach ($goods_list as $item)
	            {
	                // $is_gift, $addtional_shipping_fee
	                $added_fee = $db->getOne("select added_fee from ecshop.ecs_goods where goods_id = '".$item['goods_id']."'");
	                $item['goods_name'] = addslashes($item['goods_name']);
	                $sql = "
	                    INSERT INTO {$ecs->table('order_goods')} ( 
	                      order_id, goods_id, goods_name, goods_sn, goods_number, 
	                      market_price, goods_price, goods_attr, is_real, extension_code, 
	                      parent_id, is_gift, provider_id, biaoju_store_goods_id, 
	                      return_points, subtitle, addtional_shipping_fee, style_id,added_fee,group_code,group_name,group_number,discount_fee,
	                      pay_amount,out_order_goods_id,create_time
	                    ) VALUES ( 
	                      '{$order_id}','{$item['goods_id']}','{$item['goods_name']}','{$item['goods_sn']}','{$item['goods_number']}',
	                      '{$item['market_price']}','{$item['shop_price']}','','{$item['is_real']}','{$item['extension_code']}',
	                      '0', '0', '{$item['provider_id']}', 0, 
	                      '0',  '', '0', '{$item['style_id']}','{$added_fee}','{$item['group_code']}','{$item['group_name']}',
	                      '{$item['group_number']}','{$item['discount_fee']}','{$item['pay_amount']}','{$item['out_order_goods_id']}',now()
	                    )
	                ";
	                $db->query($sql);
	                $order_goods_id = $db->insert_id();
	                // 如果有套餐
	                if(!empty($item['tc_code'])) {
	                	$tc_code = $item['tc_code'];
	                	$sql = "insert into ecshop.order_goods_attribute (order_goods_id,name,value) values('{$order_goods_id}','OUTER_IID','{$tc_code}')";
	                    $db->query($sql);
	                }
	                if(!empty($item['discount_fee']) && $item['discount_fee'] != '0') {
	                	$sql = "insert into ecshop.order_goods_attribute (order_goods_id,name,value) values('{$order_goods_id}','DISCOUNT_FEE','{$item['discount_fee']}')";
	                    $db->query($sql);
	                }
	            }
	        } else {
	            $result['error'] = '没有添加商品不能生成订单';
	            break;
	        }
	        
	//        // 添加配送 20101217 yxiang
//	        if (!function_exists('soap_get_client')) {
//	        	require_once(ROOT_PATH . 'RomeoApi/lib_soap.php');
//	        }
	//        try {
	//            $handle=soap_get_client('ShipmentService');
	//            $handle->createShipmentForOrder(array(
	//                'orderId'=>$order_id,
	//                'carrierId'=>$carrier_id,
	//                'shipmentTypeId'=>$order['shipping_id'],
	//                'partyId'=>$order['party_id'],
	//                'createdByUserLogin'=>$_SESSION['admin_name'],
	//            ));
	//        } catch (Exception $e) {
	//        	QLog::log("分销下采购订单生成shipment失败\n orderId=" . $order_id . " carrierId=" . $carrier_id 
	//        		. " shipmentTypeId=" . $order['shipping_id'] . " partyId=" . $order['party_id'] . " createdByUserLogin=" . $_SESSION['admin_name']);
	//			QLog::log($e);
	//        }
	        
            /*
	        if (!function_exists('insert_order_mixed_status')) { 
	            require_once(ROOT_PATH . 'admin/includes/lib_order_mixed_status.php'); 
	        }
	         // 订单类型 ， 为货到付款或非货到付款  COD | NON-COD
	        insert_order_mixed_status($order_id, $order_type, 'worker');  // 记录订单状态
	        
	        // 如果订单生成并确认，添加action
	        if ($order['order_status'] == OS_CONFIRMED) 
	        {
	            $action['order_id']        = $order_id;
	            $action['order_status']    = $order['order_status'];
	            $action['pay_status']      = $order['pay_status'];
	            $action['shipping_status'] = $order['shipping_status'];
	            $action['action_time']     = date("Y-m-d H:i:s");
	            $action['action_note']     = '生成订单,自动确认';
	            $action['action_user']     = $_SESSION['admin_name'];
	            $db->autoExecute($ecs->table('order_action'), $action);
	            
	            update_order_mixed_status($order_id, array('order_status' => 'confirmed'), 'system');
	        }
            */
           
	        // 添加外部积分折扣
	        $outer_point_fee = trim($order['taobao_point_fee']);
	        if (!empty($outer_point_fee)) {
	        	if (!function_exists('add_order_attribute')) {
	        	    include_once("admin/includes/lib_order.php");	
	        	}
	            add_order_attribute($order_id, 'TAOBAO_POINT_FEE', $outer_point_fee);
	        }
	        
	        if(!empty($order['discount_fee']) && $order['discount_fee'] != '0') {
	        	if (!function_exists('add_order_attribute')) {
	        	    include_once("admin/includes/lib_order.php");	
	        	}
	            add_order_attribute($order_id, 'DISCOUNT_FEE', $order['discount_fee']);
	        }
	        
	        // 蓝光OPPO业务港币结算，添加货币种类
	        if ('65536' == $_SESSION['party_id']) {
	        	if (!function_exists('add_order_attribute')) {
	        	    include_once("admin/includes/lib_order.php");	
	        	}
	        	if ('HKD' == $order['currency']) {
	        	    add_order_attribute($order_id, 'order_currency_symbol', 'HK$');	
	        	} elseif ('USD' == $order['currency']) {
	        		add_order_attribute($order_id, 'order_currency_symbol', 'US$');	
	        	}
	        	
	        }
	        
	        // 如果当前组织是香港平世，添加货币种类
	        if ('65566' == $_SESSION['party_id']) {
	            if (!function_exists('add_order_attribute')) {
	                include_once("admin/includes/lib_order.php");    
	            }
	            
	            if ('HKD' == $order['currency']) {
	                add_order_attribute($order_id, 'order_currency_symbol', 'HK$');    
	            } elseif ('USD' == $order['currency']) {
	                add_order_attribute($order_id, 'order_currency_symbol', 'US$');    
	            } elseif ('AUD' == $order['currency']) {
	                add_order_attribute($order_id, 'order_currency_symbol', 'AU$');    
	            } elseif ('NZD' == $order['currency']) {
	                add_order_attribute($order_id, 'order_currency_symbol', 'NZ$');    
	            } elseif ('CAD' == $order['currency']) {
	                add_order_attribute($order_id, 'order_currency_symbol', 'CA$');    
	            } elseif ('EUR' == $order['currency']) {
	                add_order_attribute($order_id, 'order_currency_symbol', '€');    
	            } elseif ('GBP' == $order['currency']) {
	                add_order_attribute($order_id, 'order_currency_symbol', '£');    
	            } elseif ('CHF' == $order['currency']) {
	                add_order_attribute($order_id, 'order_currency_symbol', 'CHF');    
	            } elseif ('DKK' == $order['currency']) {
	                add_order_attribute($order_id, 'order_currency_symbol', 'DKK');    
	            } elseif ('NOK' == $order['currency']) {
	                add_order_attribute($order_id, 'order_currency_symbol', 'NOK');    
	            } elseif ('SEK' == $order['currency']) {
	                add_order_attribute($order_id, 'order_currency_symbol', 'SEK');
	            }    
	        }
	        
	        
	        // 添加订单映射关系 ecs_order_mapping
	        if (!empty($order['distribution_purchase_order_sn'])) {
	            include_once("admin/includes/lib_taobao.php");
	            if ($order['party_id'] == 16) {
	                add_ecs_order_mapping($order_id, $order['distribution_purchase_order_sn'], '乐其数码专营店');   //添加ecs_order_mapping中间表
	            } else if ($order['party_id'] == 65548) {  //nutricia官方旗舰店这个店铺已经停用
	                add_ecs_order_mapping($order_id, $order['distribution_purchase_order_sn'], 'nutricia官方旗舰店');  //添加ecs_order_mapping中间表
	            }
	        }

            //By Sinri Edogawa 20151030 找不到ecs_order_mapping的记录就插入一条,淘宝和京东
            if(in_array($order['source_type'],array('taobao','360buy_overseas','360buy'))){
                $subtagindex=strpos($order['taobao_order_sn'],'-');
                $pure_taobao_order_sn=$order['taobao_order_sn'];
                if($subtagindex!==false){
                    $pure_taobao_order_sn=substr($order['taobao_order_sn'], 0,$subtagindex);
                }
                $eom_check_sql="SELECT 
                        mapping_id
                    FROM
                        ecshop.ecs_order_mapping
                    WHERE
                        outer_order_sn = '{$pure_taobao_order_sn}'
                            AND platform IN ('360buy' , '360buy_overseas',
                            'fenxiao',  'fixed', 'step','tmall_i18n','taobao') ";
                $eom_id=$db->getOne($eom_check_sql);
                if(empty($eom_id)){
                    //insert
                    include_once("admin/includes/lib_taobao.php");
                    add_ecs_order_mapping($order_id, $pure_taobao_order_sn, $order['distributor_name'],$order['source_type'],'手动录单');
                }
            }

	        $db->commit();
	        $result['order_sn'] = $order['order_sn'];
            $result['order_id'] = $order_id; 
//	        return $order['order_sn'];
	    }
	    while (false);
    } catch(Exception $e) {
    	 $result['error'] = '系统执行异常：'.$e->getMessage();
    }
    if(!empty($result['error'])) {   	
    	$db->rollBack();
    }
    return $result;
}

/**
 * 通过商家编码取得运费模板
 *
 * @param 商家编码  $outer_id
 * @param 配送地区  $region_id
 * @param 配送方式  $shipping_id
 */
function distribution_get_postage($outer_id, $region_id, $shipping_id) {
	global $db;
	// 从该产品所对应的运费模板中找出地区和配送方式匹配的
	$sql = "
        SELECT p.postage_id, p.post_fee, p.extra_fee, p.region_id, p.shipping_id
        FROM distribution_product_mapping as pm
            LEFT JOIN distribution_product_postage AS pp ON pp.product_id = pm.product_id and pp.sku_id = pm.sku_id
            LEFT JOIN distribution_postage AS p ON p.postage_id = pp.postage_id
        WHERE pm.outer_id = ". $db->qstr($outer_id);
	$postage_list = $db->getAll($sql);
	if ($postage_list) {
		foreach ($postage_list as $postage) {
	        $regions = explode(',', $postage['region_id']);
	        $shippings = explode(',', $postage['shipping_id']);
	        if (in_array($region_id, $regions) && in_array($shipping_id, $shippings)) {
                return $postage;
	        }
		}
	}
	return false;
}


/**
 * 取得电教调价金额,专为确认订单做预警用 ljzhou 2013.03.04
 * 
 */
function distribution_get_edu_adjust_confirm_order($order) {
	global $db;
    static $adjust_order_list = array();

    if (isset($adjust_order_list[$order['order_id']])) {
        return $adjust_order_list[$order['order_id']];
    }

    // 在此时间点之前的订单不计算调价
    if (strtotime($order['order_time']) < strtotime('2010-08-31 04:00:00')) {
        return $adjust_order_list[$order['order_id']] = 0;
    }
    
    // 该淘宝订单已经扣过了
    $consumed = $db->getOne("SELECT 1 FROM distribution_order_adjustment_log WHERE taobao_order_sn = '{$order['order_sn']}' LIMIT 1");
    if ($consumed) {
    	return $adjust_order_list[$order['order_id']] = 0;
    }
    
    // 已经计算过调价金额
    $adjust = $db->getOne("SELECT SUM(amount) FROM distribution_order_adjustment WHERE order_id='{$order['order_id']}' AND status='INIT'");
    if ($adjust) {
        return $adjust_order_list[$order['order_id']] = $adjust;
    }
	
    // 调价金额
    $adjust = 0;
    $datetime = date('Y-m-d H:i:s');

    // 计算运费的调价金额
    // 查询原来导入订单的套餐关系
    $sql = "
        SELECT
            oi.goods_code, oi.goods_number 
        FROM
            distribution_import_order_info oh
            LEFT JOIN distribution_import_order_goods oi ON oi.taobao_order_sn = oh.taobao_order_sn AND oi.batch_no = oh.batch_no
        WHERE 
            oh.deleted = 'N' AND oh.imported ='Y' AND oh.refer_order_sn = '{$order['order_sn']}'
    ";
    $imported_order_goods = $db->getAll($sql);
    if ($imported_order_goods) {
        // 查询商品调价的SQL
        $sql1 = "
            SELECT adjust_fee 
            FROM distribution_sale_price 
            WHERE (distributor_id = 0 or distributor_id = ". $order['distributor_id'] .") AND goods_id = '%d' AND style_id = '%d' AND '{$order['order_time']}' >= valid_from
            ORDER BY distributor_id DESC, valid_from DESC    
        ";
        
        // 查询电教商品的SQL, style_id都为0
        $sql2 = "
            SELECT g.goods_id, g.goods_party_id, g.goods_name 
            FROM ecs_goods AS g  
            WHERE g.goods_id = %d
        ";
 
        foreach ($imported_order_goods as $goods) {
            $post_fee = $adjust_fee = $amount = 0;
	    	
            // 套餐
            if (strpos($goods['goods_code'], 'TC-') !== false) {
            	$group = distribution_get_group_goods(NULL, $goods['goods_code'], $order['order_time']);
            	if ($group) {
                    // 运费
	                $postage = distribution_get_postage($goods['goods_code'], $order['province'], $order['shipping_id']);
	                if ($postage) {
	                	if ($goods['goods_number'] == 1) {	
	                		$post_fee = $postage['post_fee'] * $goods['goods_number'];
	                	}
	                	else if ($goods['goods_number'] > 1) {
	                		$post_fee = $postage['post_fee'] + (($goods['goods_number'] -1) * $postage['extra_fee']);
	                	}
                        $amount += $post_fee;
	                    //$db->query("INSERT INTO distribution_order_adjustment (order_id, group_id, group_name, num, amount, type, status, created_by_user_login, created) VALUES ('{$order['order_id']}', '{$group['group_id']}', '{$group['name']}', '{$goods['goods_number']}', '{$post_fee}', 'SHIPPING_ADJUSTMENT', 'INIT', '{$_SESSION['admin_name']}', '{$datetime}')");
	                }
                    
                    // 调价
                    foreach ($group['item_list'] as $g) {
                        $adjust_fee = $db->getOne(sprintf($sql1, $g['goods_id'], $g['style_id']));
                        if ($adjust_fee && $adjust_fee > 0) {
                            $num = $g['goods_number'] * $goods['goods_number'];
                            $adjust_fee = $adjust_fee * $num;
                            $amount += $adjust_fee;
                            //$db->query("INSERT INTO distribution_order_adjustment (order_id, goods_id, style_id, goods_name, group_id, group_name, num, amount, type, status, created_by_user_login, created) VALUES ('{$order['order_id']}', '{$g['goods_id']}', '{$g['style_id']}', '{$g['goods_name']}', '{$group['group_id']}', '{$group['name']}', '{$num}', '{$adjust_fee}', 'GOODS_ADJUSTMENT', 'INIT', '{$_SESSION['admin_name']}', '{$datetime}')");     
                        }
                    }
            	}
            }
            // 独立的商品 
            else if (is_numeric($goods['goods_code'])) {
            	$g = $db->getRow(sprintf($sql2,$goods['goods_code']), true);
                if ($g) {
                    $g['style_id']=0;
                    // 运费
                    $postage = distribution_get_postage($goods['goods_code'], $order['province'], $order['shipping_id']);
                    if ($postage) {
                        if ($goods['goods_number'] == 1) {
                            $post_fee = $postage['post_fee'] * $goods['goods_number'];	
                        }
                        else if ($goods['goods_number'] > 1) {
                            $post_fee = $postage['post_fee'] + (($goods['goods_number'] -1) * $postage['extra_fee']);
                        } 
                        $amount += $post_fee;
                        //$db->query("INSERT INTO distribution_order_adjustment (order_id, goods_id, style_id, goods_name, num, amount, type, status, created_by_user_login, created) VALUES ('{$order['order_id']}', '{$g['goods_id']}', '{$g['style_id']}', '{$g['goods_name']}', '{$goods['goods_number']}', '{$post_fee}', 'SHIPPING_ADJUSTMENT', 'INIT', '{$_SESSION['admin_name']}', '{$datetime}')");
                    }
                    
                    // 调价
                    $adjust_fee = $db->getOne(sprintf($sql1, $g['goods_id'], $g['style_id']));
                    if ($adjust_fee && $adjust_fee > 0) {
                        $adjust_fee = $adjust_fee * $goods['goods_number'];
                        $amount += $adjust_fee;
                        //$db->query("INSERT INTO distribution_order_adjustment (order_id, goods_id, style_id, goods_name, num, amount, type, status, created_by_user_login, created) VALUES ('{$order['order_id']}', '{$g['goods_id']}', '{$g['style_id']}', '{$g['goods_name']}', '{$goods['goods_number']}', '{$adjust_fee}', 'GOODS_ADJUSTMENT', 'INIT', '{$_SESSION['admin_name']}', '{$datetime}')");
                    }
                }
            }

            $adjust += $amount;
        }
    }
    
    return $adjust_order_list[$order['order_id']] = $adjust;
}


/**
 * 取得电教调价金额
 */
function distribution_get_edu_adjust($order) {
	global $db;
	
    static $adjust_order_list = array();

    if (isset($adjust_order_list[$order['order_id']])) {
        return $adjust_order_list[$order['order_id']];
    }

    // 在此时间点之前的订单不计算调价
    if (strtotime($order['order_time']) < strtotime('2010-08-31 04:00:00')) {
        return $adjust_order_list[$order['order_id']] = 0;
    }
    
    // 该淘宝订单已经扣过了 
    $consumed = $db->getOne("SELECT 1 FROM distribution_order_adjustment_log WHERE taobao_order_sn = '{$order['order_sn']}' LIMIT 1");
    if ($consumed) {
    	return $adjust_order_list[$order['order_id']] = 0;
    }
    
    // 已经计算过调价金额
    $adjust = $db->getOne("SELECT SUM(amount) FROM distribution_order_adjustment WHERE order_id='{$order['order_id']}' AND status='INIT'");
    if ($adjust) {
        return $adjust_order_list[$order['order_id']] = $adjust;
    }
	
    // 调价金额
    $adjust = 0;
    $datetime = date('Y-m-d H:i:s');

    // 计算运费的调价金额
    $sql = "SELECT goods_id, style_id, goods_number FROM ecshop.ecs_order_goods WHERE order_id='{$order['order_id']}' ";
    $imported_order_goods = $db->getAll($sql);
    if($imported_order_goods){
    	// 查询商品调价的SQL
        $sql1 = "
            SELECT adjust_fee 
            FROM distribution_sale_price 
            WHERE (distributor_id = 0 or distributor_id = ". $order['distributor_id'] .") AND goods_id = '%d' AND '{$order['order_time']}' >= valid_from
            ORDER BY distributor_id DESC, valid_from DESC    
        ";
        
        // 查询电教商品的SQL, style_id都为0
        $sql2 = "
            SELECT g.goods_id, g.goods_party_id, g.goods_name 
            FROM ecs_goods AS g  
            WHERE g.goods_id = %d
        ";
        foreach ( $imported_order_goods as $goods ) {
       		$adjust_fee = 0;
       		$g = $db->getRow(sprintf($sql2,$goods['goods_id']), true);
            if ($g) {
                // 调价
                $adjust_fee = $db->getOne(sprintf($sql1, $g['goods_id']));
                if ($adjust_fee && $adjust_fee > 0) {
                    $adjust_fee = $adjust_fee * $goods['goods_number'];
                    $db->query("INSERT INTO distribution_order_adjustment (order_id, goods_id, style_id, goods_name, num, amount, type, status, created_by_user_login, created) VALUES ('{$order['order_id']}', '{$g['goods_id']}', '{$goods['style_id']}', '{$g['goods_name']}', '{$goods['goods_number']}', '{$adjust_fee}', 'GOODS_ADJUSTMENT', 'INIT', '{$_SESSION['admin_name']}', '{$datetime}')");
                }
            }
            $adjust += $adjust_fee;
		}
    }
    
    return $adjust_order_list[$order['order_id']] = $adjust;
}


/**
 * 电教分销订单抵扣预存款函数
 */
function distribution_edu_order_adjustment($order, $main_distributor_id){
	require_once(ROOT_PATH. 'includes/debug/lib_log.php');
	
	$result = null;
	$adjust = distribution_get_edu_adjust($order);
	if ($adjust > 0) {
		$available = prepay_get_available_amount($main_distributor_id, $order['party_id'], 'DISTRIBUTOR');
		if ($available===false) {
			$result = '不存在该分销商的预付款账户，请通知财务解决' ;
			return $result;
		}
		
		// 判断余额
		if ($available < $adjust) {
		    $result = '分销商的预付款账户余额不足，请通知财务' ;
		    return $result;	
		}
		
		$note = "订单调价,ERP订单号 {$order['order_sn']},淘宝订单号{$order['taobao_order_sn']}";
		$prepay_consume_result = prepay_consume(
                        $main_distributor_id,                         // 合作伙伴ID 
                        $order['party_id'],                            // 组织
                        'DISTRIBUTOR',                                 // 账户类型
                        $adjust,                                        // 使用金额
                        NULL,                                           // 账单
                        $_SESSION['admin_name'],
                        $note,                                          // 备注
                        NULL                                            // 支票号
                    );
                    
		if ($prepay_consume_result == 0) {
            QLog::log("使用预付款失败了，CODE: ". $prepay_consume_result, QLog::ERR);
            $result = '使用预付款失败';
        }
        else if ($prepay_consume_result == -1) {
            QLog::log("预付款账户不存在呢，CODE: ". $prepay_consume_result, QLog::ERR);
            $result = '预付款账户不存在';
        }
        else {
             // 标识该淘宝订单已经扣过预付款
             global $db;
             $res=$db->query("INSERT INTO distribution_order_adjustment_log (taobao_order_sn,prepayment_transaction_id,status) VALUES ('{$order['order_sn']}','{$prepay_consume_result}','CONSUMED')", 'SILENT');
             $db->query("UPDATE distribution_order_adjustment SET status = 'CONSUMED', prepayment_transaction_id = '{$prepay_consume_result}' WHERE order_id='{$order['order_id']}' AND status='INIT'");
             if(!$res){ 
             	QLog::log("该淘宝订单号（".$order['taobao_order_sn']."）重复抵扣预付款", QLog::ERR); 
             }
        }
	}
	
	return $result ;
	
}