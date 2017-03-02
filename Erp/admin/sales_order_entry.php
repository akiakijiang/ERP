<?php

/**
 * 销售订单录入
 * 
 * @author yxiang@oukoo.com
 * @copyright 2009 ouku.com
 */

define('IN_ECS', true);
require_once('includes/init.php');
admin_priv('kf_order_entry');
require_once(ROOT_PATH . 'includes/lib_order.php');
require_once(ROOT_PATH . 'includes/cls_json.php');
include_once(ROOT_PATH . 'admin/function.php'); 
// include_once(ROOT_PATH . 'admin/includes/lib_order_mixed_status.php');
require_once(ROOT_PATH . 'includes/helper/array.php');

$kf_batch_order_entry = false;
if( check_admin_user_priv($_SESSION['admin_name'], 'kf_batch_order_entry')){
	$kf_batch_order_entry = true;
	$smarty->assign('kf_batch_order_entry', $kf_batch_order_entry);
}

//判断组织是否为具体的业务单位
if(!party_explicit($_SESSION['party_id'])) {
    sys_msg("请选择具体的组织后再来录入订单");
}

// 请求
$request = isset($_REQUEST['request']) && 
    in_array($_REQUEST['request'], array('ajax')) 
    ? $_REQUEST['request'] 
    : null;
    
$act = isset($_REQUEST['act']) && 
    in_array($_REQUEST['act'], array('add_goods', 'remove_goods', 'done', 'message')) 
    ? $_REQUEST['act'] 
    : null;

// 购物车的session键
$cart_session_id = '_sales_order_entry_cart_';


/*
 * 处理ajax请求
 */
if ($request == 'ajax')
{
    $json = new JSON;
   
    switch ($act) {
        // 添加商品
        case 'add_goods':
            $goods = sales_order_entry_get_goods($_POST['goods_id'], $_POST['style_id'], $_POST['parent_id']);
            $goods['goods_number'] = $_POST['goods_number'];
            if($_POST['price'] != 0){
            	$goods['shop_price'] = $_POST['price'];
            }
            if ($goods) {
                // 添加到购物车
                $ix = $goods['goods_id'] . '_' . $goods['style_id'] . '_' . $goods['parent_id'];
                if (isset($_SESSION[$cart_session_id]) &&
                    array_key_exists($ix, $_SESSION[$cart_session_id])) {
                    // 只是添加数量
                    $_SESSION[$cart_session_id][$ix]['quantity'] += $_POST['goods_number'];
                } else {
                    // 添加新商品到购物车
                    $_SESSION[$cart_session_id][$ix] = array(
                        'goods_id'  => $goods['goods_id'],
                        'style_id'  => $goods['style_id'],
                        'quantity'  => $_POST['goods_number'],
                        'parent_id' => $goods['parent_id'],        // 属于哪个商品的搭配 (套餐功能)
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
            $ix = $_POST['goods_style_parent_id'];
            if (isset($_SESSION[$cart_session_id]) && array_key_exists($ix, $_SESSION[$cart_session_id])) { 
                if ($_SESSION[$cart_session_id][$ix]['parent_id'] == 0) {
                    // 删除所有子商品
                    foreach ($_SESSION[$cart_session_id] as $k => $g) {
                        if ($g['parent_id'] == $_SESSION[$cart_session_id][$ix]['goods_id']) {
                            unset($_SESSION[$cart_session_id][$k]);
                        }
                    }
                }
                unset($_SESSION[$cart_session_id][$ix]);
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
        if (!empty($order['taobao_order_sn'])) {
            //检查外部订单号是否存在
            if(!preg_match("/^[0-9a-zA-Z]+[\w-]*[\x{4e00}-\x{9af5}]*$/u",$order['taobao_order_sn'])){
            	$message='外部订单号前请不要添加备注说明';
            	break;
            }
            $sql = "SELECT order_sn, order_id FROM {$ecs->table('order_info')} WHERE taobao_order_sn = '". $db->escape_string($order['taobao_order_sn']) ."'";
            $exists = $db->getRow($sql);
            if (!empty($exists['order_id'])) {
                $message = '该淘宝订单号已经存在了，ERP订单号：'."<a href=\"order_edit.php?order_id={$exists['order_id']}\" target=\"_blank\">
                    {$exists['order_sn']}</a> 如有问题，请及时联系ERP组。";
                break;
            }
        }
        
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
        $order['shipping_proxy_fee'] = 0;            // 手续费都为0 （如果不指定则会计算）
        $order['party_id'] = $_SESSION['party_id'];  // 订单类型 
        $order_sn = sales_order_entry($order, $order_goods, $message);
        if ($order_sn !== false) {
            unset($_SESSION[$cart_session_id]);
            header("Location: sales_order_entry.php?act=message&message=" . urlencode("订单成功生成，订单号为：{$order_sn}"));
            exit;
        }
    }
    while (false);
    
    $smarty->assign('message', $message);  // 错误消息
    $smarty->assign('order', $order);      // 失败要持有订单数据
    $smarty->assign('tel', $tel);      // 失败要持有订单数据
}

else if ($act == 'message' ) {
    $message = trim($_REQUEST['message']);
    // 尝试将提示信息中的订单号替换为链接
    if ($message && preg_match('/[0-9]{10}/', $message, $matches)) {
        $order_sn = $matches[0];
        $order_id = $db->getOne("SELECT order_id FROM {$ecs->table('order_info')} WHERE order_sn = '{$order_sn}'");
        if ($order_id) {
            $replacements = '<a href="order_edit.php?order_id='.$order_id.'" target="_blank">'.$order_sn.'</a>';
            $message = preg_replace('/[0-9]{10}/', $replacements, $message);
        }
    }
    $smarty->assign('message', $message);  // 信息
}




$pay_list = Helper_Array::toHashmap((array)getPayments(), 'pay_id', 'pay_name');
$shipping_list = Helper_Array::toHashmap((array)getShippingTypes(), 'shipping_id', 'shipping_name');
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
/*if (!empty($_SESSION[$cart_session_id])) {
    foreach ($_SESSION[$cart_session_id] as $item) {
        $g = sales_order_entry_get_goods($item['goods_id'], $item['style_id'], $item['parent_id']);
        if ($g) {
            $g['quantity'] = $item['quantity'];
            $cart_goods_list[] = $g;
        }

    }
    if ($cart_goods_list) {
        $cart_goods_tree = Helper_Array::toTree($cart_goods_list, 'goods_id', 'parent_id', 'children');
    }
}*/

$smarty->assign('currency', get_currency_style()); //币种数组
$smarty->assign('cart_goods_list', $cart_goods_list);  // 购物车中商品
$smarty->assign('cart_goods_tree', $cart_goods_tree);  // 格式化后商品列表，将子商品放在父商品的children里面
$smarty->assign('province_list', $province_list);  // 省份列表
$smarty->assign('available_facility', get_available_facility());  // 仓库
$smarty->assign('pay_list', $pay_list);  // 支付方式
$smarty->assign('shipping_list', $shipping_list);  // 配送方式
//外部订单类型
$smarty->assign('outer_type_options', $_CFG['adminvars']['outer_type']);
$smarty->assign('sub_outer_type_options', $_CFG['adminvars']['sub_outer_type']);

$smarty->display('oukooext/sales_order_entry.htm');




/**
 * 取得在售商品, 返回一条分销商品记录, ‘shop_price’为售价
 * 
 * @return array
 */
function sales_order_entry_get_goods($goods_id, $style_id = '', $parent_id = '')
{ 
    // 取得商品信息
    $sql = "
        SELECT 
            g.goods_id, g.goods_party_id, g.goods_name, g.goods_sn, g.market_price, g.shop_price, 
            g.is_real, g.extension_code, g.provider_id 
        FROM 
            {$GLOBALS['ecs']->table('goods')} AS g  
        WHERE 
            g.is_delete = 0 AND g.goods_id = '{$goods_id}'
    ";        
    $goods = $GLOBALS['db']->getRow($sql, true); 
    
    if ($goods) {
        $goods['parent_id'] = 0;
        if (!$style_id) { $goods['style_id'] = 0; }

        // 如果是某个商品的套餐
        if ($parent_id > 0) {
            $sql = "
                SELECT goods_price, parent_id
                FROM {$GLOBALS['ecs']->table('group_goods')}
                WHERE parent_id = '{$parent_id}' AND goods_id = '{$goods_id}'
            ";
            $belong = $GLOBALS['db']->getRow($sql);

            if ($belong) {
                $goods['shop_price'] = $belong['goods_price'];  // 使用套餐中定义的价格
                $goods['parent_id'] = $belong['parent_id'];
            } else {
                return false;
            }
        } 
        // 如果限定了颜色
        elseif ($style_id > 0) {
            $sql = "
                SELECT 
                    IF(gs.goods_color = '', s.color, gs.goods_color) AS color, 
                    gs.style_price, gs.sale_status, s.style_id, s.value
                FROM {$GLOBALS['ecs']->table('goods_style')} AS gs 
                    INNER JOIN {$GLOBALS['ecs']->table('style')} AS s ON gs.style_id = s.style_id
                WHERE gs.goods_id = '{$goods_id}' AND gs.sale_status = 'normal' AND s.style_id = '{$style_id}'
            ";
            $style = $GLOBALS['db']->getRow($sql);
           
            if ($style) { 
                $goods['shop_price'] = $style['style_price'];  // 使用该商品样式的价格
                $goods['goods_name'] = $goods['goods_name'].' '.$style['color'];  // 商品名
                $goods['style_id'] = $style['style_id'];
            } else {
                return false;  // 如果该颜色下架了
            }
        }
    }
    
    return $goods;
}


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
        'need_invoice'    => 'Y',       // 默认打印发票
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
        
        if (!is_numeric($order['shipping_id']) || $order['shipping_id'] < 0) {
            $msg = '没有选择配送方式';
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
       
        // 检查外部订单号
        if (!empty($order['taobao_order_sn'])) {
            if ($order['outer_type'] == '-1') {
                $msg = "请先选择外部订单类型才能录入外部订单号";
                break;
            } else {
                $exists = $db->getOne("
                    SELECT 1 FROM {$ecs->table('order_info')} AS o INNER JOIN order_attribute AS a ON a.order_id = o.order_id
                    WHERE a.attr_name = 'OUTER_TYPE' AND a.attr_value = '{$order['outer_type']}' AND o.taobao_order_sn = '{$order['taobao_order_sn']}'
                ");
                if ($exists) {
                    $msg = '该外部订单号已经存在了';
                    break;                
                }
            }
        }
        
        $user = user_info($order['user_id']);
        if (!$user) {
            $msg = "通过#{$order['user_id']}取不到对应的用户";
            break;  
        }
        
        // 取得配送信息和承运信息
        $_region_id_list = array($order['country'], $order['province'], $order['city'], $order['district']);
        $shipping = shipping_area_info($order['shipping_id'], $_region_id_list); 
        if (!$shipping) {
            $msg = "通过#{$order['shipping_id']}取不到对应的配送方式，请确认该地点快递可达";
            break;
        }
        $carrier_id = $shipping['default_carrier_id'];
        $order['shipping_name'] = $shipping['shipping_name'];  // 配送名称

        // 取得支付方式名
        $payment = payment_info($order['pay_id']);
        if (!$payment) {
            $msg = "通过#{$order['pay_id']}取不到对应的支付方式";
            break; 
        }
        $order['pay_name'] = $payment['pay_name'];
        
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
        
        // 运费
        if (isset($order['shipping_fee'])) {
            $order['shipping_fee'] = floatval($order['shipping_fee']);   
        }
        
        $goods_amount = 0;               // 商品总金额
        $goods_list = array();           // 商品列表
        $goods_party_id_list = array();  // 商品的party_id
        foreach ($order_goods as $item) {
            $g = sales_order_entry_get_goods(intval($item['goods_id']), intval($item['style_id']), intval($item['parent_id']));  // 取得商品售价
            if ($g) {
                $g['goods_number'] = intval($item['goods_number']);  // 商品数量
                if (isset($item['price']) && floatval($item['price']) >= 0) {  // 如果修改了价格的话
                    $g['shop_price'] = round((float)$item['price'], 6);
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
        
        // 如果没有指定订单配送手续费，计算配送手续费
        if (!isset($order['shipping_proxy_fee'])) {
            $shipping_proxy_fee = shipping_proxy_fee($shipping['shipping_code'], $shipping['configure'], 0, $goods_amount);
            $order['shipping_proxy_fee'] = $shipping_proxy_fee;  // 配送手续费    
        }
        
        // 如果没有指定订单配送费用，则计算配送运费
        if (!isset($order['shipping_fee'])) {
            $total_shipping_fee = shipping_fee($shipping['shipping_code'], $shipping['configure'], 0, $goods_amount);
            $order['shipping_fee'] = $total_shipping_fee;  // 配送总费用    
        }
        
        $order['order_time']   = date("Y-m-d H:i:s");  // 下单时间
        $order['goods_amount'] = $goods_amount;  // 商品总金额
        
        // 如果没有指定订单总金额，则计算订单总金额
        if (!isset($order['order_amount'])) {
            $order['order_amount'] = $order['shipping_fee'] + 
                max(0, $goods_amount - $order['bonus_value'] - $order['integral_money'] + $order['pack_fee']);  // 订单总金额   
        }

        // 插入配送面单记录
        $db->query("INSERT INTO {$ecs->table('carrier_bill')} (carrier_id, weight, send_address, receiver, phone_no) VALUES ('{$carrier_id}', 0, '', '', '')");
        $order['carrier_bill_id'] = $db->insert_id();
        
        // 生成订单
        $error_no = 0;
        $order = array_map(array(& $db, 'escape_string'), $order);   // 订单头信息
        do {
            // 如果是faucetland组织，则修改为其录入的币种
            if ('65560' == $_SESSION['party_id']) {
               $order['currency'] = $_POST['currency'];        // 货币
            }
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
        
        // 添加配送 20101217 yxiang
//        try {
//            $handle=soap_get_client('ShipmentService');
//            $handle->createShipmentForOrder(array(
//                'orderId'=>$order_id,
//                'carrierId'=>$carrier_id,
//                'shipmentTypeId'=>$order['shipping_id'],
//                'partyId'=>$order['party_id'],
//                'createdByUserLogin'=>$_SESSION['admin_name'],
//            ));
//        } 
//        catch (Exception $e) {
//
//        }
        
        require_once('includes/lib_order.php');
        // 增加订单的销售人员 录单的人不一定是订单的销售人员
        //add_order_attribute($order_id, 'SALESPERSON', $_SESSION['admin_name']);
        
        // 添加外部订单类型
        if ($order['outer_type'] != "-1") {
            add_order_attribute($order_id, 'OUTER_TYPE', $order['outer_type']);
        }
        if ($order['sub_outer_type'] != "-1") {
            add_order_attribute($order_id, 'SUB_OUTER_TYPE', $order['sub_outer_type']);
        }
        // 添加外部积分折扣
        $outer_point_fee = trim($order['taobao_point_fee']);
        if (!empty($outer_point_fee)) {
            add_order_attribute($order_id, 'TAOBAO_POINT_FEE', $outer_point_fee);
        }
        
        // 如果当前组织是faucetland，添加货币种类
        if ('65560' == $_SESSION['party_id']) {
            if (!function_exists('add_order_attribute')) {
                include_once("admin/includes/lib_order.php");    
            }
            
            if ('HKD' == $order['currency']) {
                add_order_attribute($order_id, 'order_currency_symbol', 'HK$');    
            } elseif ('USD' == $order['currency']) {
                add_order_attribute($order_id, 'order_currency_symbol', 'USD');    
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

        
        // if (!function_exists('insert_order_mixed_status')) { 
        //     require_once(ROOT_PATH . 'admin/includes/lib_order_mixed_status.php'); 
        // }
        // 订单类型 ， 为货到付款或非货到付款  COD | NON-COD
        $order_type = $payment['pay_code'] == 'cod' ? 'COD' : 'NON-COD';
        // insert_order_mixed_status($order_id, $order_type, 'worker');  // 记录订单状态
            
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
        
        // if ($order['order_status'] == 1) {
        //     update_order_mixed_status($order_id, array('order_status' => 'confirmed'), 'system');
        // }
        
        return $order['order_sn'];
    }
    while (false);

    return false;
}
