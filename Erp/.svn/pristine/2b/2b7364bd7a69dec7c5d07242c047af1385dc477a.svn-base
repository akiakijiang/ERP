<?php

/**
 * 销售订单录入
 * 
 * @author yxiang@oukoo.com
 * @copyright 2009 ouku.com
 */

define('IN_ECS', true);

require_once(dirname(__FILE__) . '/init.php');
require_once(ROOT_PATH . 'includes/lib_order.php');
require_once(ROOT_PATH . 'includes/cls_json.php');
include_once(ROOT_PATH . 'admin/function.php'); 
// include_once(ROOT_PATH . 'admin/includes/lib_order_mixed_status.php');
require_once(ROOT_PATH . 'includes/helper/array.php');


// 请求
$request = isset($_REQUEST['request']) && 
    in_array($_REQUEST['request'], array('ajax')) 
    ? $_REQUEST['request'] 
    : null;
    
$act = isset($_REQUEST['act']) && 
    in_array($_REQUEST['act'], array('add_goods', 'search_goods', 'search_goods_styles', 'remove_goods', 'get_regions', 'done', 'message')) 
    ? $_REQUEST['act'] 
    : null;

// 购物车的session键
$cart_session_id = '_sales_order_entry_cart_' . $_SESSION['distributor_id'];


/*
 * 处理ajax请求
 */
if ($request == 'ajax' && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
{
    $json = new JSON;
   
    switch ($act) {
        // 搜索地区
        case 'get_regions':
            $result = sales_order_entry_get_regions($_POST['type'], $_POST['parent']);
            if (isset($_POST['target'])) {
                $result['target'] = $_POST['target'];
            }
            print $json->encode($result);
            break;
    
        // 搜索商品
        case 'search_goods':
            $q        = isset($_POST['q']) ? trim($_POST['q']) : false ;
            $field    = isset($_POST['field']) ? $_POST['field'] : null ;
            $limit    = isset($_POST['limit']) ? $_POST['limit'] : null ;
            $category = isset($_POST['category']) ? $_POST['category'] : null ;
            if ($q) {
                $result = sales_order_entry_search_goods($_SESSION['distributor_id'], $q, $field, $limit, $category);
            }
            if ($result) {
                print $json->encode($result);
            }
            break;
            
        // 搜索商品颜色
        case 'search_goods_styles':
            $goods_id = isset($_POST['goods_id']) ? $_POST['goods_id'] : null ;
            $result = sales_order_entry_search_goods_styles($goods_id);
            print $json->encode($result);
            break;
            
        // 添加商品
        case 'add_goods':
            $goods = sales_order_entry_get_goods($_SESSION['distributor_id'], $_POST['goods_id'], $_POST['style_id']);
            
            if ($goods) {
                // 添加到购物车
                $goods_style_id = $goods['goods_id'].'_'.$goods['style_id'];
                if (isset($_SESSION[$cart_session_id]) &&
                    array_key_exists($goods_style_id, $_SESSION[$cart_session_id])) {
                    $_SESSION[$cart_session_id][$goods_style_id]['quantity'] += $_POST['goods_number'];
                } else {
                    $_SESSION[$cart_session_id][$goods_style_id] = array(
                        'goods_id' => $goods['goods_id'],
                        'style_id' => $goods['style_id'],
                        'quantity' => $_POST['goods_number'],
                    );    
                }
                print $json->encode($goods);
            } else {
                print $json->encode(array('error' => '商品不存在,或该颜色已经下架'));
            }
            break;
            
        // 删除商品
        case 'remove_goods':
            // 从购物车中删除
            $goods_style_id = $_POST['goods_style_id'];
            if (isset($_SESSION[$cart_session_id]) && 
                array_key_exists($goods_style_id, $_SESSION[$cart_session_id])) {
                unset($_SESSION[$cart_session_id][$goods_style_id]);
            }
        break;
    }

    exit;
}




/*
 * 生成销售订单
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST) && $act == 'done') {
    $order = $_POST['order'];
    $order_goods = $_POST['order_goods'];  // 订单商品
    $tel = $_POST['tel'];
    
    do {
        if (empty($order_goods)) {
            $message = '没有添加订单商品';
            break;    
        }
        
        if (empty($order)) {
            $message = '没有订单数据';
            break;
        }
                
        Helper_Array::removeEmpty($order);           // 删除空白的订单属性

        if (isset($order['need_invoice'])) {         // 需要发票
            $order['need_invoice'] = 'Y';    
        } else {
            $order['need_invoice'] = 'N';
        }
        // 设置电话号码
        if (!empty($tel)) {
            $order['tel'] = trim($tel[0])."-".trim($tel[1]). (trim($tel[2])?"-".trim($tel[2]):"");
        }
        
        $order['party_id'] = $_SESSION['party_id'];              // 
        $order['shipping_proxy_fee'] = 0;                        // 手续费都为0 （如果不指定则会计算）
        $order['shipping_fee'] = 0;                              // 运费为0
        $order['party_id'] = $_SESSION['party_id'];              // 订单类型 
        $order['pay_id'] = 58;                                   // 中国银行基本户
        $order['pack_fee'] = 0;                                  // 包装费为0
        $order['user_id'] = 1; 
        $order['outer_type'] = $_SESSION['distributor_name'];    // 订单外部类型
        $order['distributor_id'] = $_SESSION['distributor_id'];  // 分销商ID
        $order_sn = sales_order_entry($order, $order_goods, $message);
        if ($order_sn !== false) {
            unset($_SESSION[$cart_session_id]);  // 清空购物车
            header("Location: salesOrderEntry.php?act=message&message=" . urlencode("订单成功生成，订单号为：{$order_sn}"));
            exit;
        }
    }
    while (false);
    
    $smarty->assign('message', $message);  // 错误消息
    $smarty->assign('order', $order);      // 失败要持有订单数据
    $smarty->assign('tel', $tel);          // 失败要持有订单数据
}
else if ($act == 'message' ) {
    $message = trim($_REQUEST['message']);
    $smarty->assign('message', $message);  // 信息
}

$province_list = Helper_Array::toHashmap((array)get_regions(1, $GLOBALS['_CFG']['shop_country']), 'region_id', 'region_name');

// 如果选择了订单省份，则持有城市数据
if ($order['province'] > 0) {
    $city_list = Helper_Array::toHashmap((array)get_regions(2, $order['province']), 'region_id', 'region_name');
    $smarty->assign('city_list', $city_list);    
}
if ($order['city'] > 0) {
    $district_list = Helper_Array::toHashmap((array)get_regions(3, $order['city']), 'region_id', 'region_name');
    $smarty->assign('district_list', $district_list);
}

// 持有的商品数据
if (!empty($_SESSION[$cart_session_id])) {
    foreach ($_SESSION[$cart_session_id] as $item) {
        $g = sales_order_entry_get_goods($_SESSION['distributor_id'], $item['goods_id'], $item['style_id']);
        if ($g) {
            $g['quantity'] = $item['quantity'];
            $cart_goods_list[] = $g;
        }
    }
}

$smarty->assign('cart_goods_list', $cart_goods_list);
$smarty->assign('province_list', $province_list);  // 省份列表

$smarty->display('api/sales_order_entry.htm');



/**
 * 生成分销销售订单
 * 
 * @param array 订单
 * @param array 订单商品，需要的键为： goods_id， style_id, goods_number, price
 * 
 * @return string 订单sn
 */
function sales_order_entry($order, $order_goods, & $msg)
{  
    global $db, $ecs;
    // 默认属性
    $_order_default = array
    (
        'party_id'        => PARTY_OUKU_MOBILE,
        'pay_id'          => 0,   // 支付方式
        'pack_fee'        => 2,   // 包装费
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
        'need_invoice'    => 'N',       // 默认不打印发票
        'user_id'         => 0,  
    );
    $order = array_merge($_order_default, (array)$order);
    
    //如果没有facilityId，给他分配
    if (!$order['facility_id']) {
        $order['facility_id'] = assign_order_facility($order);
    }
    
    do {
        // 验证
        if (!is_numeric($order['user_id']) || $order['user_id'] < 0) {
            $msg = '没有指定订单的用户';
            break;
        }
        
        if (!empty($order['email']) && !preg_match('/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i', $order['email'])) {
            $msg = '请填写正确的邮箱地址';
            break;            
        }
        
        /*
        if (!is_numeric($order['shipping_id']) || $order['shipping_id'] < 0) {
            $msg = '没有选择配送方式';
            break;   
        }
        */
        
        if (!is_numeric($order['party_id']) || $order['party_id'] < 0) {
            $msg = '没有选择业务类型';
            break;
        }
        
        if (!is_numeric($order['pay_id']) || $order['pay_id'] < 0) {
            $msg = '没有选择支付方式';
            break;   
        }
        
        if (!is_numeric($order['province']) || !is_numeric($order['city']) ) {
            $msg = '没有选择收货地址';
            break;
        }
        
        if (empty($order_goods) || count($order_goods) < 1) {
            $msg = '没有添加商品不能生成订单';
            break;
        }
       
        // 检查外部订单号是否存在了
        if (!empty($order['taobao_order_sn'])) {
            if ($order['outer_type'] == '-1') {
                $msg = "请指定订单的外部类型";
                break;
            } else {
                $exists = $db->getOne("
                    SELECT 1 FROM {$ecs->table('order_info')} as o INNER JOIN order_attribute as a ON a.order_id = o.order_id 
                    WHERE a.attr_name = 'OUTER_TYPE' AND a.attr_value = '{$order['outer_type']}' AND o.taobao_order_sn = '{$order['taobao_order_sn']}'
                ");
                if ($exists) {
                    $msg = '该外部订单号已经存在了，请检查是否重复录单了';
                    break;
                }
            }
        }
        
        // 检查用户是否存在
        $user = user_info($order['user_id']);
        if (!$user) {
            $msg = "通过#{$order['user_id']}取不到对应的用户";
            break;  
        }
        
        // 取得支付方式名
        $payment = payment_info($order['pay_id']);
        if (!$payment) {
            $msg = "通过#{$order['pay_id']}取不到对应的支付方式, 请检查该支付方式是否启用了";
            break; 
        }
        $order['pay_name'] = $payment['pay_name'];
        
        // 取得配送信息和承运信息
        // 如果指定了订单的配送方式，则检查
        $_region_id_list = array($order['country'], $order['province'], $order['city'], $order['district']);
        if (isset($order['shipping_id'])) {
            $shipping = shipping_area_info($order['shipping_id'], $_region_id_list); 
        }
        // 如果没有指定订单的配送方式，则为自动指定一个，如有没有找到合适的，就用EMS的先款后货
        // 需要注意的是，指定的配送方式应该与支付的cod(是否货到付款)一致
        else {
            $_available_shipping_list = available_shipping_list($_region_id_list);
            if ($_available_shipping_list) {
                $_field = $payment['is_cod'] ? 'support_cod' : 'support_no_cod' ;
                foreach ($_available_shipping_list as $_shipping) {
                    if ($_shipping[$_field]) {
                        $shipping = $_shipping;
                        break;
                    }
                }
            }

            if (!$_available_shipping_list || !$shipping) {
                $shipping = shipping_info(47);  // EMS先款后货
            }
        }
        if (!$shipping) {
            $msg = "通过#{$order['shipping_id']}取不到对应的配送方式，请确认该地点快递可达，如果指定了配送方式，请确认该配送方式已启用";
            break;
        }    
        $order['shipping_id'] = $shipping['shipping_id'];      // 订单的配送方式
        $order['shipping_name'] = $shipping['shipping_name'];  // 订单的配送名称
        $carrier_id = $shipping['default_carrier_id'];         // 承运商ID, 用户生成订单的免单记录
        
        // 选择货到付款的支付方式 必须有 货到付款的快递方式
        if ($payment['is_cod'] && (!$shipping['support_cod'])) { 
            $msg = "所选支付方式为货到付款，但配送方式不支持货到付款";
            break;
        }
        
        // 选择先款后货的支付方式，必须有先款后货的快递方式
        if ((!$payment['is_cod']) && (!$shipping['support_no_cod'])) {
            $msg = "所选支付方式为先款后货，但配送方式不是先款后货";
            break;
        }
        
        $goods_amount = 0;               // 商品总金额
        $goods_list = array();           // 商品列表
        $goods_party_id_list = array();  // 商品的party_id
        foreach ($order_goods as $item) {
            $g = sales_order_entry_get_goods($_SESSION['distributor_id'], intval($item['goods_id']), intval($item['style_id']));  // 取得商品售价
            if ($g) {
                $g['goods_number'] = intval($item['goods_number']);  // 商品数量
                if (isset($item['price']) && floatval($item['price']) >= 0) {  // 如果修改了价格的话
                    $g['shop_price'] = round((float)$item['price'], 2);
                }
                $subtotal = $g['shop_price'] * $g['goods_number'];
                $goods_amount += $subtotal;
                $goods_list[] = $g; 
                $goods_party_id_list[] = $g['goods_party_id'];  
            } else {
                $msg = "通过商品编号{$item['goods_id']}#{$item['style_id']}找不到对应的商品";
                return false;
            }
        }
        
        $goods_party_id_list = array_unique($goods_party_id_list);
        if (count($goods_party_id_list) > 1) {
            $msg = "所添加的商品的party_id不一致";
            break;
        }
        
        if (reset($goods_party_id_list) != $order['party_id']) {
            $msg = "商品的party_id和订单的party_id不一致";
            break;
        }
        
        // 如果没有指定订单配送费用，则计算配送运费
        if (!isset($order['shipping_fee'])) {
            $total_shipping_fee = shipping_fee($shipping['shipping_code'], $shipping['configure'], 0, $goods_amount);
            $order['shipping_fee'] = $total_shipping_fee;  // 配送总费用    
        } else {
            $order['shipping_fee'] = floatval($order['shipping_fee']);               
        }
        
        // 如果没有指定订单配送手续费，计算配送手续费
        if (!isset($order['shipping_proxy_fee'])) {
            $shipping_proxy_fee = shipping_proxy_fee($shipping['shipping_code'], $shipping['configure'], 0, $goods_amount);
            $order['shipping_proxy_fee'] = $shipping_proxy_fee;  // 配送手续费    
        }
        
        $order['order_time']   = date("Y-m-d H:i:s");  // 下单时间
        $order['goods_amount'] = $goods_amount;  // 商品总金额
        
        // 如果没有指定订单总金额，则计算订单总金额
        if (!isset($order['order_amount'])) {
            $order['order_amount'] = $order['shipping_fee'] + 
                max(0, $goods_amount - $order['bonus_value'] - $order['integral_money'] + $order['pack_fee']);  // 订单总金额   
        }

        // 插入配送面单记录 killed by Sinri 20160105
        // $db->query("INSERT INTO {$ecs->table('carrier_bill')} (carrier_id, weight, send_address, receiver, phone_no) VALUES ('{$carrier_id}', 0, '', '', '')");
        // $order['carrier_bill_id'] = $db->insert_id();
        $order['carrier_bill_id'] = 0;
        
        // 生成订单
        $error_no = 0;
        $order = array_map(array(& $db, 'escape_string'), $order);   // 订单头信息
        do {
            $order['order_sn'] = get_order_sn();  // 生成订单sn
            $db->autoExecute($ecs->table('order_info'), $order, 'INSERT', '', 'SILENT');
            $error_no = $GLOBALS['db']->errno();
            if ($error_no > 0 && $error_no != 1062) { 
                $db->query("DELETE FROM {$ecs->table('carrier_bill')} WHERE bill_id = '{$order['carrier_bill_id']}' LIMIT 1", 'SILENT');
                $msg = '生成订单失败，错误代码：{$error_no}, 错误消息：'. $db->error();
                return false;
            }
        } while ($error_no == 1062);
        $order_id = $db->insert_id();

        // 添加order_goods记录
        foreach ($goods_list as $item) {
        	$item['goods_name'] = addslashes($item['goods_name']);
            $sql = "
                INSERT INTO {$ecs->table('order_goods')} ( 
                  order_id, goods_id, goods_name, goods_sn, goods_number, 
                  market_price, goods_price, goods_attr, is_real, extension_code, 
                  parent_id, is_gift, provider_id, biaoju_store_goods_id, 
                  return_points, subtitle, addtional_shipping_fee, style_id
                ) VALUES (
                  '{$order_id}','{$item['goods_id']}','{$item['goods_name']}','{$item['goods_sn']}','{$item['goods_number']}',
                  '{$item['market_price']}','{$item['shop_price']}','','{$item['is_real']}','{$item['extension_code']}',
                  '0', '0', '{$item['provider_id']}', 0, 
                  '0',  '', '0', '{$item['style_id']}'
                )
            ";
            $db->query($sql);
        }
        
        // 添加外部订单类型
        if ($order['outer_type'] != "-1") {
            require_once(ROOT_PATH . 'admin/includes/lib_order.php');
            add_order_attribute($order_id, 'OUTER_TYPE', $order['outer_type']);
        }
        
        // 添加外部积分折扣
        /*
        $outer_point_fee = trim($order['taobao_point_fee']);
        if (!empty($outer_point_fee)) {
            add_order_attribute($order_id, 'TAOBAO_POINT_FEE', $outer_point_fee);
        }
        */

        // 添加erp记录
        require_once(ROOT_PATH . 'admin/function.php');
        
        // 初始化并添加订单history
        // 订单类型 ， 为货到付款或非货到付款  COD | NON-COD
        $order_type = $payment['pay_code'] == 'cod' ? 'COD' : 'NON-COD';
        // require_once(ROOT_PATH . 'admin/includes/lib_order_mixed_status.php'); 
        // insert_order_mixed_status($order_id, $order_type, 'worker');  // 记录订单状态
        // if ($order['order_status'] == 1) {
        //     update_order_mixed_status($order_id, array('order_status' => 'confirmed'), 'system');
        // }
                
        // 添加订单Action
        $sql = "
            INSERT INTO {$ecs->table('order_action')} (
                order_id, order_status, pay_status, shipping_status, action_time, action_note, action_user
            ) VALUES (
                '{$order_id}', '{$order['order_status']}', '{$order['pay_status']}', '{$order['shipping_status']}',
                NOW(), '录入订单', '{$_SESSION['admin_name']}'
            )
        ";
        $db->query($sql, 'SILENT');
        
        return $order['order_sn'];
    }
    while (false);

    return false;
}

/**
 * 取得在售商品, 返回一条分销商品记录, ‘shop_price’为售价
 * 商品的价格由distribution_sale_price表中维护的为准
 * 
 * @return array
 */
function sales_order_entry_get_goods($distributor_id, $goods_id, $style_id = '')
{ 
    // 取得商品信息
    $sql = "
        SELECT 
            g.goods_id, g.goods_party_id, g.goods_name, g.goods_sn, g.market_price, g.shop_price, 
            g.is_real, g.extension_code, g.provider_id, p.price as shop_price
        FROM 
            {$GLOBALS['ecs']->table('goods')} AS g  
            INNER JOIN distribution_sale_price as p ON p.goods_id = g.goods_id AND p.style_id = '{$style_id}'
        WHERE 
            g.is_delete = 0 AND g.goods_id = '{$goods_id}' AND p.distributor_id = '{$distributor_id}'
    ";        
    $goods = $GLOBALS['db']->getRow($sql, true); 
    
    if ($goods) {
        if ($style_id > 0) {
            $sql = "
                SELECT 
                    IF(gs.goods_color = '', s.color, gs.goods_color) AS color, s.style_id
                FROM {$GLOBALS['ecs']->table('goods_style')} AS gs 
                    INNER JOIN {$GLOBALS['ecs']->table('style')} AS s ON gs.style_id = s.style_id
                WHERE gs.goods_id = '{$goods_id}' AND gs.sale_status = 'normal' AND s.style_id = '{$style_id}'
            ";
            $style = $GLOBALS['db']->getRow($sql);
           
            if ($style) { 
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
 * 取得地区列表
 * 
 * @return array
 */
function sales_order_entry_get_regions($type, $parent = 1)
{
    $result = array();
    $result['regions'] = get_regions($type, $parent);
    return $result;
}

/**
 * 按关键字搜索商品
 * 
 * 仅仅从distribution_sale_price中寻找与该分销商匹配的商品
 */
function sales_order_entry_search_goods($distributor_id, $q, $field = null, $limit = null, $category = null)
{
    // 按哪个字段来搜索
    $fields = array('goods_name');
    $field = ($field && in_array($field, $fields)) ? $field : reset($fields) ;
    // 关键词
    $keyword = mysql_like_quote($q);
    // limit
    $limit = ($limit && is_numeric($limit)) ? $limit : 30 ;
    
    // 如果限定了商品类别
    if (!empty($category)) {
        switch ($category) {
            case 'mobile' : // 手机
            $conditions = " AND g.`top_cat_id` = '1'";
            break;
            case 'fittings' : // 配件
            $conditions = " AND g.`top_cat_id` = '597'";
            break;
            case 'dvd' : // dvd
            $conditions = " AND g.`cat_id` = '1157'";
            break;
            case 'education' : // 电教产品
            $conditions = " AND g.`top_cat_id` = '1458'";
            break;
            case 'shoes' : // 鞋品
            $conditions = " AND g.`goods_party_id` = " . PARTY_OUKU_SHOES;
            break;
            case 'notebook' : // 笔记本
            $conditions = " AND g.`top_cat_id` = '414' ";
            break;
            case 'other' : // 其他
            $conditions = " AND (g.`top_cat_id` NOT IN (1, 597, 1109, 1458) AND g.`cat_id` != '1157' )";
            break;
        }
    }
    
    $sql = "
        SELECT
            concat(g.`goods_name`, IF(g.`is_on_sale` = 0, '(已下架)', '')) as goods_name,
            g.`goods_id`, g.`goods_party_id`, g.`cat_id`, g.`top_cat_id`, g.`is_on_sale`
        FROM
            distribution_sale_price AS p
            INNER JOIN {$GLOBALS['ecs']->table('goods')} AS g ON g.goods_id = p.goods_id 
        WHERE
            (g.`is_delete` = 0) {$conditions} AND (g.`{$field}` LIKE '%{$keyword}%') AND p.distributor_id = '{$distributor_id}'
        LIMIT {$limit}
    ";
    return $GLOBALS['db']->getAll($sql);
}

/**
 * 搜索商品颜色
 * 
 * @param int $goods_id  // 商品id
 * 
 * @return array
 */
function sales_order_entry_search_goods_styles($goods_id)
{
    if ($goods_id)
    {
        $sql = "
            SELECT 
                gs.internal_sku, s.style_id, s.color, s.value 
            FROM 
                {$GLOBALS['ecs']->table('goods_style')} gs
                INNER JOIN {$GLOBALS['ecs']->table('style')} s ON s.style_id = gs.style_id
            WHERE gs.style_id = s.style_id AND gs.goods_id = '{$goods_id}'
        ";
        $styles = $GLOBALS['db']->getAll($sql);
    }
    return $styles;
}

