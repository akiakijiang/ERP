<?php 

/**
 * COD订单导入功能
 * 
 * @author hli@oukoo.com
 * @copyright 2013 ouku.com 
 */
 
define('IN_ECS', true);
require_once('includes/init.php');
require_once('distribution.inc.php');
require_once('function.php');
require_once(ROOT_PATH.  'includes/lib_order.php');
require_once(ROOT_PATH . 'includes/cls_page.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'includes/helper/uploader.php');
require_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');
include_once(ROOT_PATH . 'includes/cls_json.php');
require_once (ROOT_PATH . 'includes/debug/lib_log.php');
require_once(ROOT_PATH . 'admin/distribution_order_address_analyze.php');

if ($_SESSION['party_id'] != 65569 && $_SESSION['party_id'] != 65581){
	sys_msg('请选择安满或者安怡组织');
}
// 请求
 $request = isset($_REQUEST['request']) && 
    in_array($_REQUEST['request'], array('ajax')) 
    ? $_REQUEST['request'] 
    : null;
$act =  // 动作
    isset($_REQUEST['act']) && 
    in_array($_REQUEST['act'], array('upload', 'search_distributor')) 
    ? $_REQUEST['act'] 
    : null ;
$info =  // 返回的信息
    isset($_REQUEST['info']) && trim($_REQUEST['info']) 
    ? urldecode($_REQUEST['info']) 
    : false ; 

// 信息
if ($info) {
    $smarty->assign('message', $info);
}
 if ($request == 'ajax'){
    $json = new JSON;
 	
    switch($act){ 
        //分销商搜索
        case 'search_distributor':
        	$limit = (isset($_POST['limit']) && is_numeric($_POST['limit'])) ? $_POST['limit'] : 30 ;
        	print $json->encode(get_distributor($_POST['q'], $limit));
        break;
 	}
 	
 	exit;
 	
 } 

// 当前时间 
$now = date('Y-m-d H:i:s');

 $shipping_list = array(
//        '圆通快递' => 85,
//        '韵达快递' => 100,
//        'EMS快递' => 47,
        '汇通快递' => 99,
        'E邮宝快递' => 107,
    	'中通快递' => 115,
        '宅急送快递_货到付款' => 11,
 );
$carrier_list = array(
//    '圆通快递' => 3,
//    'EMS快递' => 9,
//    '韵达快递' => 29,
    '汇通快递' => 28,
    'E邮宝快递' => 36,
    '中通快递' => 41,
    '宅急送快递_货到付款' => 15,
);
 
 
// Excel读取设置
$config = array(
    '商品信息' => array(
        // 订单头信息
	    'taobao_order_sn' => '淘宝订单号',
	    'distribution_purchase_order_sn' => '采购单编号',
        'tmp_sn' => '临时订单编号',
        // 订单的商品
        'goods_code' => '商家编码',
        'goods_name' => '产品名称',
        'goods_number' => '商品数量',
        'goods_price' => '商品单价',
        
    ),
    '订单信息' => array(
        'taobao_order_sn' => '淘宝订单号',
        'distribution_purchase_order_sn' => '采购单编号',
        'tmp_sn' => '临时订单编号',
        'consignee' => '收货人',
        'region' => '省市',
        'address' => '收货地址',
        'zipcode' => '邮编',
        'mobile' => '联系人手机',
        'shipping_name' => '送货方式',
        'order_amount' => '订单金额总计',
        'shipping_name' => '快递名称',
        'postscript' => '订单备注',
        'taobao_user_id' => '旺旺id',
        'shipping_fee' => '应付邮费',
        'is_cod' => '货到付款(Y/N)',
    ),
);

/*
 * 处理post请求
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $act) {

    switch ($act) {
        /**
         * 上传文件， 检查上传的excel格式，并读取数据提取插入到临时表 
         */
        case 'upload' :
            if (!lock_acquire('distribution_order_import-upload')) {
                $smarty->assign('message', '导入操作正在被执行，请稍后执行'); 
                break;
            }

            @set_time_limit(300);
            $uploader = new Helper_Uploader();
            $max_size = $uploader->allowedUploadSize();  // 允许上传的最大值

            if (!$uploader->existsFile('excel')) {
                $smarty->assign('message', '没有选择上传文件，或者文件上传失败');
                break;
            }

            // 取得要上传的文件句柄
            $file = $uploader->file('excel');

            // 检查上传文件
            if (!$file->isValid('xls, xlsx', $max_size)) {
                $smarty->assign('message', '非法的文件! 请检查文件类型类型(xls, xlsx), 并且系统限制的上传大小为'. $max_size/1024/1024 .'MB');
                break;
            }

            // 读取数据
            $data = excel_read($file->filepath(), $config, $file->extname(), $failed);
            

            if (!empty($failed)) {
                $smarty->assign('message', reset($failed));
                break;
            }
            
            // 订单数据读取失败
            if (empty($data['商品信息'])) {
                $smarty->assign('message', '不能读取到文件中的[采购单信息]的数据， 请检测文件格式');
                break;
            }
            else if (empty($data['订单信息'])) {
                $smarty->assign('message', '不能读取到文件中的[物流信息]的数据， 请检测文件格式');
                break;            	
            }

            $distributor_id = $_REQUEST['hid_distributor_id'];
            
            $facility_id = $_REQUEST['facility_id'];
            $user_id = get_user_id();
            if(!$user_id){
            	Sys_msg("搜索user_id没有成功。");
            }
            
            $pay_id = $_REQUEST['pay_id'];
            $payment = get_payment($pay_id);
            if(!$payment){
            	Sys_msg("搜索支付方式没有成功。");
            }
            
            // 分析数据取得订单头和商品的数据
            $orders = $goods = array();
            $errGoodsCode = array(); 
            $no_taobao_sn = false;
            $sign = array();
            foreach ($data['订单信息'] as $row1) {
            	$tmp_order_goods = array();
            	
            	if(empty($row1['tmp_sn'])){
	            	if (empty($row1['taobao_order_sn'])){
	            		$smarty->assign('message', '[订单信息]表中存在空的淘宝订单号或者没有临时订单编号');
	            		break 2; 
	            	}
	            	$no_taobao_sn = false;
            	}else{
            		$no_taobao_sn = true;
            	}
            	
            	//记录文件中淘宝订单号与临时订单号的情况
            	if(!empty($row1['tmp_sn'])){
            		$sign['tmp_sn'] = 1;
            	}
            	if(!empty($row1['taobao_order_sn'])){
            		$sign['taobao_order_sn'] = 1;
            	}
            	
            	if(trim($row1['is_cod']) == 'Y' && !$payment['is_cod']){
            		$smarty->assign('message', '[订单信息]表中存在支付方式为COD，而选择的支付方式为非COD的情况');
	                break 2; 
            	}
            	
            	if(trim($row1['is_cod']) != 'Y' && $payment['is_cod']){
            		$smarty->assign('message', '[订单信息]表中存在支付方式为非COD，而选择的支付方式为COD的情况');
	                break 2;
            	}
            	
            	
            	//标志一个订单是否有一个或以上商品对应
            	$flag = false;
            	if($no_taobao_sn){
            		
            		//如果没有找到 就将商品信息加入
            		foreach ($data['商品信息'] as $key2 => $row2){
            		    if ($row1['tmp_sn'] == $row2['tmp_sn']) {
            		    	array_push($tmp_order_goods,$row2);
			                // 检查套餐金额是否与ERP一致
			                if (strpos($row2['goods_code'], 'TC-') !== false) {
			                    $result_flag = distribution_price_check($row2['goods_code'], $row2['goods_price']);
			                    if(!$result_flag){
			                    	$errGoodsCode[] = $row2['tmp_sn'];
			                    }
			                }
            			    $flag = true;
                    	    unset($data['商品信息'][$key2]);  // 可减少下次循环次数	
                    	}
            		}
            		if(!$flag){
            			
            			$smarty->assign('message', "该临时订单编号{$row1['tmp_sn']}订单没有商品，请检查excel的文件格式是否已更改");
                        break 2;
            		}
            	}else{
            	    //如果没有找到 就将订单信息加入
            		foreach ($data['商品信息'] as $key2 => $row2){
            		    if ($row1['taobao_order_sn'] == $row2['taobao_order_sn']) {
            		    	array_push($tmp_order_goods,$row2);
            		    	// 检查套餐金额是否与ERP一致
			                if (strpos($row2['goods_code'], 'TC-') !== false) {
			                    $result_flag = distribution_price_check($row2['goods_code'], $row2['goods_price']);
			                    if(!$result_flag){
			                    	$errGoodsCode[] = $row2['taobao_order_sn'];
			                    }
			                }
			                $flag = true;
                    	    unset($data['商品信息'][$key2]);  // 可减少下次循环次数	
                    	}
            		}
            	   if(!$flag){
       
            			$smarty->assign('message', "该淘宝编号{$row1['taobao_order_sn']}订单没有商品，请检查excel的文件格式是否已更改");
                        break 2;
            		}
            	}
            	
                $row1['goods'] = $tmp_order_goods;
                $orders[] = $row1;
                    
            }
            
            if($sign['taobao_order_sn'] == 1 && $sign['tmp_sn'] == 1){
                  $smarty->assign('message', '[订单信息]表中既有临时订单号又有淘宝订单号');
                  break ;
            }

            // 检查套餐金额是否与ERP一致
            if(!empty($errGoodsCode)){
            	$smarty->assign('message', '导入的订单：'.implode(', ', $errGoodsCode).' 中套餐金额与ERP系统中维护的金额不一致， 请重新检查。。');
                break;
            }
            // 取得淘宝订单号或临时订单号
            if(!$no_taobao_sn){
            	$osn = Helper_Array::getCols($orders, 'taobao_order_sn');
            }else{
            	$osn = Helper_Array::getCols($orders, 'tmp_sn');
            }
            
            // 检查订单数据中是否有重复的淘宝单号
            if (count($osn) > count(array_unique($osn))) {
                $smarty->assign('message', '[订单信息]表中存在重复的淘宝订单号或者临时订单编号');
                break;                
            }
      
            // 检查淘宝订单号是否导入过了 （没有删除的）
            $exists = array();
            if(!$no_taobao_sn){
            	$sql = "
            	    SELECT taobao_order_sn FROM ecshop.ecs_order_info 
            	    WHERE taobao_order_sn ". db_create_in($osn);
                $exists = $db->getCol($sql);
                
	            if ($exists) {
	                $smarty->assign('message', '部分淘宝订单号已经存在系统中了不能生成订单：'. implode(',', $exists));
	                break;
	            }
            }

            $error_orders = array();
            $_goods_party_ids = array();
            $imported_orders = array() ;
            $error_msg = array();
            foreach ($orders as $order){
            	if(empty($order['shipping_fee'])){
            		$order['shipping_fee'] = 0;
            	}
            	
            	//处理商品
            	$order_goods = array();
            	foreach($order['goods'] as $goods){
            	    // 套餐
                    if (strpos($goods['goods_code'], 'TC-') !== false) {
                        $group = distribution_get_group_goods(null, $goods['goods_code']);
                        if ($group && !empty($group['item_list'])) {
                            foreach ($group['item_list'] as $g) {
                                $item['goods_id']     = $g['goods_id'];
                                $item['style_id']     = $g['style_id'];
                                $item['price']        = $g['price'];  // 商品金额采用我们系统维护的金额
                                $item['goods_number'] = $g['goods_number'] * $goods['goods_number'];  // 订单商品数量
                                
                                $_goods_party_ids[] = $g['goods_party_id'];  // 用于判断商品的party_id是否一致
                                $order_goods[] = $item;                                        
                            }
                        } else {
                            if($no_taobao_sn){
                        		array_push($error_orders,$order['tmp_sn']);
                        	}else{
                        		array_push($error_orders,$order['taobao_order_sn']);
                        	}
                        	// 订单中有商品不正确，就过滤掉这个订单
                        	continue 2;
                        }
                    }
                    // 商品
                    else if ($goods['goods_code']) {
                    	//如果是数字，就默认为无样式
                    	if(is_numeric($goods['goods_code'])){
                    		$goods_id = $goods['goods_code'];
                    		$style_id = 0;
                    	}else{
                    		$item = explode("_",$goods['goods_code']);
                    		$goods_id = $item[0];
                    		$style_id = $item[1];
                    	}
                        $g = distribution_get_goods($goods_id,$style_id);
                        if ($g) {
                            $item['goods_id']     = $g['goods_id'];
                            $item['style_id']     = $g['style_id'];
                            $item['price']        = $goods['goods_price'];   // 商品价格，采用导入时的金额
                            $item['goods_number'] = $goods['goods_number'];  // 订单商品数量
                            
                            $_goods_party_ids[] = $g['goods_party_id']; 
                            $order_goods[] = $item;
                        } else {
                            if($no_taobao_sn){
                        		array_push($error_orders,$order['tmp_sn']);
                        	}else{
                        		array_push($error_orders,$order['taobao_order_sn']);
                        	}
                        	// 订单中有商品不正确，就过滤掉这个订单
                        	continue 2;
                        }
                    }
                    // 导入的商品数据商品编码有误
                    else {
                        if($no_taobao_sn){
                        	array_push($error_orders,$order['tmp_sn']);
                        }else{
                        	array_push($error_orders,$order['taobao_order_sn']);
                        }
                        continue 2;      
                    }
            	}
            	
            	// 商品信息的party_id不一致 (订单的商品应该是同一个party_id)
                $_goods_party_ids = array_unique($_goods_party_ids);
                if (count($_goods_party_ids) != 1) {
                    continue;  // 跳出处理下一个订单
                }
                // 检测商品party_id是否与当前party_id一致
                if (!in_array($_SESSION['party_id'],$_goods_party_ids)){
                	continue;
                }
                
                // 通过商品的party_id来决定订单的party_id
                $party_id = reset($_goods_party_ids);
            	if($payment['is_cod']){
            		$order['pay_status'] = 0;
            		$order_type = 'cod_import';
            	}else{
            		//如果是安满和安怡的，状态初始为未付款
            		if($party_id == '65569' || $party_id == '65581'){
            			$order['pay_status'] = 0;
            		}else{
            			$order['pay_status'] = 2;
            		}
            		$order_type = 'order_import';
            	}
            	$order['shipping_name'] = trim($order['shipping_name']);
            	$order_info = array(
            	    'taobao_order_sn' => $order['taobao_order_sn'],
                    'distribution_purchase_order_sn' => $order['distribution_purchase_order_sn'],
                    'consignee' => $order['consignee'],
                    'mobile' => $order['mobile'],
                    'order_amount' => $order['order_amount'],
                    'distributor_id' => $distributor_id,  
                    'party_id' => $party_id,  
                    'zipcode' => $order['zipcode'],
                    'order_time' => $now,
                    'order_status' => 0, 
                    'shipping_status' => 0, 
                    'pay_status' => $order['pay_status'], 
                    'shipping_id' => $shipping_list[$order['shipping_name']],
                    'shipping_name' => $order['shipping_name'],
                    'shipping_fee' => $order['shipping_fee'],
                    'shipping_proxy_fee' => 0,
                    'pay_id' => $payment['pay_id'] ,             
                    'pay_name' => $payment['pay_name'],
                    'goods_amount' => 0, 
                    'carrier_bill_id' => '',
                    'order_type_id' => 'SALE', 
                    'facility_id' => $facility_id,   
                    'postscript' => $order['postscript'],
                    'user_id' => $user_id,                   // 指定用户 
                    'pack_fee' => 0,          // 包装费
            	);
            	
            	// 分析订单地址, 将取得订单的 province, city, district, address
                $order_info = array_merge($order_info, (array)distribution_order_address_analyze(trim($order['region']).trim($order['address'])));
            	
                //订单导入
                $carrier_id = $carrier_list[$order['shipping_name']];
                $message = '';
                $osn = distribution_generate_sale_order($order_info, $order_goods, $carrier_id, $order_type, $message);
                if(empty($osn['error'])) {
		        	$order_sn = $osn['order_sn'];
		        } else {
		        	$order_sn = false;
		        }
                if ($order_sn !== false) {
                   $imported_orders[] = $order_sn ;
                   $order_id = $db->getRow(sprintf("select order_id from ecshop.ecs_order_info where order_sn = '%s' limit 1 ", $order_sn));
                   $db->query(sprintf("insert into ecshop.order_attribute (order_id, attr_name, attr_value) VALUES ( %d, 'OUTER_TYPE', '{$order['postscript']}')", intval($order_id['order_id'])));
                   if(!$no_taobao_sn){
                       $db->query(sprintf("insert into ecshop.order_attribute (order_id, attr_name, attr_value) VALUES ( %d, 'TAOBAO_USER_ID', '{$order['taobao_user_id']}')", intval($order_id['order_id'])));
                   }
                }
            }
            
            // 删除上传的文件
            $file->unlink();

            // 释放锁
            lock_release('distribution_order_import-upload');
            
            $tip = "";
	        if (!empty($error_orders)) {
	            $tip = "该文件中有部分订单商品错误，临时订单号或者淘宝订单号为: ". implode(',', $error_orders); 
	            $smarty->assign('tip', $tip);
	        }
	        
            if (!empty($imported_orders)) {
            	
                $smarty->assign('message', "成功生成订单数".count($imported_orders)."， 请见下表，请修改订单的配送地址和配送方式等");
                // 查询出已生成订单列表
                $sql = "
                    select o.order_id, o.order_sn, o.order_amount, o.taobao_order_sn
                      from ecshop.ecs_order_info o 
                    where o.order_sn ". db_create_in($imported_orders);
                $imported_list = $db->getAll($sql);
                $smarty->assign('imported_list', $imported_list);
            } else {
                $smarty->assign('message', '没有订单生成。可能造成的原因为所选的商品与当前组织不符。'. $message);
            }
            
        break;
        
    }
}

$pay_list = Helper_Array::toHashmap((array)getPayments(), 'pay_id', 'pay_name');
$smarty->assign('available_facility', get_available_facility($_SESSION['party_id']));
$smarty->assign('pay_list', $pay_list);

$smarty->display('cod_order_import.htm');

function get_distributor($keyword,$limit){
	global $db;
	$sql = "
	    select d.distributor_id,d.name 
		from ecshop.distributor d
		where (d.distributor_id like '%{$keyword}%' or d.name like '%{$keyword}%' or d.contact like '%{$keyword}%') and"
		. party_sql('d.party_id')."
		limit {$limit}
    ";
	return $db->getAll($sql);
}

function get_payment($pay_id){
	global $db;
	$sql = "
	    SELECT pay_id, IF(enabled=1, pay_name, CONCAT(pay_name, ' (已挂起)')) AS pay_name, is_cod
		FROM ecshop.ecs_payment
		WHERE (enabled = 1 OR enabled_backend = 'Y') and pay_id = '{$pay_id}'
    ";
	return $db->getRow($sql);
}

function get_user_id($party_id = null){
	global $db;
	if($party_id == null){
		$party_id = $_SESSION['party_id'];
	}
	$sql = "
	   select user_id from ecshop.taobao_shop_conf where party_id = '{$party_id}' limit 1
	";
	return $db->getOne($sql);
}