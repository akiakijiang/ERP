<?php

/**
 * 运费计算
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
//include_once(ROOT_PATH . 'admin/includes/lib_order_mixed_status.php');//By Sinri
require_once(ROOT_PATH . 'includes/helper/array.php');


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
    in_array($_REQUEST['act'], array('add_goods', 'remove_goods', 'done', 'message','compute','reflesh')) 
    ? $_REQUEST['act'] 
    : null;
    


// 购物车的session键
$cart_session_id = '_compute_ship_sales_cart_';

if($act=='reflesh') {
	if(isset($_SESSION[$cart_session_id]))
		unset($_SESSION[$cart_session_id]);
}

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
                        'goods_id'     => $goods['goods_id'],
                        'style_id'     => $goods['style_id'],
                        'quantity'     => $_POST['goods_number'],
                        'goods_weight' => $goods['goods_weight'],
                        'parent_id'    => $goods['parent_id'],        // 属于哪个商品的搭配 (套餐功能)
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
 * 计算运费
 */
if ($_SERVER['REQUEST_METHOD']=='POST' && $act=='compute' && !empty($_POST)){
	$order = $_POST['order'];
	$province = $order['province'];
	$shipping_id = $order['shipping_id'];
    
    $order_goods = $_POST['order_goods'];  // 订单商品

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
       
        $order['party_id'] = $_SESSION['party_id'];  // 订单类型 
        $weight = 0.0;
        foreach ($order_goods as $value){
        	$weight+= $value['goods_weight']*$value['goods_number'];
        }
        
        $goods_fee = compute_sales($shipping_id,$weight,$province,$order['facility_id']);
        if ($goods_fee!== false) {
        	$order['shipping_fee'] = $goods_fee;
        	$order['shipping_weight'] = $weight;
        	$smarty->assign('order', $order);
        }
        else{
        	 $message = "计算错误";
			 $smarty->assign('message', $message);  // 错误消息
			 $smarty->assign('order', $order);      // 失败要持有订单数据
        }
    }
    while (false);
   

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




//$pay_list = Helper_Array::toHashmap((array)getPayments(), 'pay_id', 'pay_name');
//$shipping_list = Helper_Array::toHashmap((array)getShippingTypes(), 'shipping_id', 'shipping_name');
$shipping_list = array("1"=>"邮政","2"=>"圆通");
//var_dump($shipping_list);
$province_list = Helper_Array::toHashmap((array)get_regions(1, $GLOBALS['_CFG']['shop_country']), 'region_id', 'region_name');
//var_dump($province_list);
/*如果选择了订单省份，则持有城市数据
if ($order['province'] > 0) {
    $city_list = Helper_Array::toHashmap((array)get_regions(2, $order['province']), 'region_id', 'region_name');
    $smarty->assign('city_list', $city_list);    
}
if ($order['city'] > 0) {
    $district_list = Helper_Array::toHashmap((array)get_regions(3, $order['city']), 'region_id', 'region_name');
    $smarty->assign('district_list', $district_list);
}*/

// 持有的商品数据
if (!empty($_SESSION[$cart_session_id])) {
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
}

// 可选仓库
// $facility_list=get_available_facility();
if ($_SESSION['party_id']==65539)
	$facility_list=array('24196974'=>'贝亲青浦仓');
else
	$facility_list=array('22143847'=>'电商服务杭州仓');

$smarty->assign('cart_goods_list', $cart_goods_list);  // 购物车中商品
$smarty->assign('cart_goods_tree', $cart_goods_tree);  // 格式化后商品列表，将子商品放在父商品的children里面
$smarty->assign('province_list', $province_list);  // 省份列表
$smarty->assign('facility_list', $facility_list);  // 仓库
$smarty->assign('pay_list', $pay_list);  // 支付方式
$smarty->assign('shipping_list', $shipping_list);  // 配送方式
//外部订单类型
$smarty->assign('outer_type_options', $_CFG['adminvars']['outer_type']);
$smarty->assign('sub_outer_type_options', $_CFG['adminvars']['sub_outer_type']);

$smarty->display('oukooext/compute_ship_sales.htm');


function  compute_sales($shipping_id,$weight,$province, $facility_id){
	
	$procince_map = array(
		"2"=>  "北京",
		"3"=>  "天津" ,
		"4"=>  "河北" ,
		"5"=>  "山西" ,
		"6"=>  "内蒙古" ,
		"7"=>  "辽宁" ,
		"8"=>  "吉林" ,
		"9"=>  "黑龙江" ,
		"10"=>  "上海" ,
		"11"=>  "江苏" ,
		"12"=>  "浙江" ,
		"13"=>  "安徽" ,
		"14"=>  "福建" ,
		"15"=>  "江西" ,
		"16"=>  "山东" ,
		"17"=>  "河南" ,
		"18"=>  "湖北" ,
		"19"=>  "湖南" ,
		"20"=>  "广东" ,
		"21"=>  "广西" ,
		"22"=>  "海南" ,
		"23"=>  "重庆" ,
		"24"=>  "四川" ,
		"25"=>  "贵州" ,
		"26"=>  "云南" ,
		"27"=>  "西藏" ,
		"28"=>  "陕西" ,
		"29"=>  "甘肃" ,
		"30"=>  "青海" ,
		"31"=>  "宁夏" ,
		"32"=>  "新疆" ,
		"3689"=>"台湾" ,
		"3688"=>  "香港" ,
		"3784"=> "澳门" 
	);
	
	// 贝亲青浦
	if($facility_id==65539)
	{
		$shippingFee = array(
		    // EMS
		    '1' => array(
		        '上海、'=>array(
		            'first'=>10,
		            'second'=>4
		        ),
		        '江苏、浙江、安徽、'=>array(
		            'first'=>7,
		            'second'=>2
		        ),
		        '北京、天津、河北、山西、辽宁、福建、江西、山东、河南、湖北、湖南、广东、陕西、'=>array(
		            'first'=>10,
		            'second'=>3.6
		        ),
		        '内蒙古、吉林、黑龙江、四川、广西、海南、重庆、贵州、甘肃、'=>array(
		            'first'=>11,
		            'second'=>5.4
		        ),
		        '云南、青海、宁夏、'=>array(
		            'first'=>12,
		            'second'=>6
		        ),
		        '西藏、新疆、'=>array(
		            'first'=>12,
		            'second'=>10.2
		        ),
		        '其他、'=>array(
		            'first'=>20,
		            'second'=>20
		        )
		    ),
		    // 圆通
		    "2"=> array(
		       '上海、浙江、江苏、'=>array(
		            'first'=>7,
		            'second'=>2
		        ),
		        '其他、'=>array(
		            'first'=>12,
		            'second'=>7
		        )
		    )
	    );		
	}
	// 电商杭州仓
	else 
	{
		$shippingFee = array(
		    // EMS
		    '1' => array(
		        '浙江、上海、江苏、'=>array(
		            'first'=>8,
		            'second'=>2
		        ),
		        '北京、广东、福建、江西、安徽、'=>array(
		            'first'=>11,
		            'second'=>4
		        ),
		        '天津、山东、湖北、湖南、'=>array(
		            'first'=>11,
		            'second'=>6
		        ),
		        '河北、山西、河南、陕西、'=>array(
		            'first'=>13,
		            'second'=>6
		        ),
		        '四川、重庆、'=>array(
		            'first'=>15,
		            'second'=>8
		        ),
		        '辽宁、吉林、黑龙江、'=>array(
		            'first'=>15,
		            'second'=>10
		        ),
		        '广西、海南、'=>array(
		            'first'=>15,
		            'second'=>8
		        ),
		        '内蒙古、宁夏、甘肃、青海、贵州、云南、'=>array(
		            'first'=>18,
		            'second'=>10
		        ),
		        '新疆、西藏、云南、'=>array(
		            'first'=>23,
		            'second'=>15
		        ),
		        '其他、'=>array(
		            'first'=>23,
		            'second'=>20
		        )
		    ),
		    // 圆通
		    "2"=> array(
		       '上海、浙江、江苏、'=>array(
		            'first'=>8,
		            'second'=>1
		        ),
		        '其他、'=>array(
		            'first'=>12,
		            'second'=>8
		        )
		    )
	    );
	}

    $unit = array("1"=>0.5,"2"=>1);
    if(array_key_exists($province,$procince_map)) {
    	$province_name =$procince_map[$province]."、" ;
    }else{
    	$province_name ="其他、";
    }
    $first = false;
    $second = false;
    //var_dump($weight);
    if(array_key_exists($shipping_id,$shippingFee)){
    	$ship = $shippingFee[$shipping_id];
    	foreach ($ship as $key=>$value){
    		if(strpos($key,$province_name)!==false){
    			$first = $value['first'];
    			$second = $value['second'];
    			break;
    		}
    	}
    	
    	if($first===false){
    		$first = $ship['其他、']['first'];
    		$second = $ship['其他、']['second'];
    	}
    	#var_dump($first);
    	#var_dump($second);
    	$fee = 0.0;
    	$fee+=$first;
    	$weight -=$unit[$shipping_id];
    	while ($weight>0){
    		$weight -=$unit[$shipping_id];
    		$fee+=$second;
    	}
    	return  $fee;
    }
    else{
    	return false;
    }
}


/**
 * 取得在售商品, 返回一条分销商品记录, ‘shop_price’为售价
 * 
 * @return array
 */
function sales_order_entry_get_goods($goods_id, $style_id = '', $parent_id = '')
{ 
	$goods_id=intval($goods_id);
	$style_id=intval($style_id);
	
    // 取得商品信息
    $sql = "
        SELECT 
            g.goods_id, g.goods_party_id, g.goods_name, g.goods_sn, g.goods_weight, g.shop_price, 
            g.is_real, g.extension_code, g.provider_id,
            g.goods_weight
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
        if ($style_id > 0) {
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
