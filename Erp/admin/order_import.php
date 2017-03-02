<?php 

/**
 * 特殊订单导入
 */
 
define('IN_ECS', true);
require_once('includes/init.php');
require_once('distribution.inc.php');

//admin_priv('dumex_order_import');
require_once('function.php');
require_once(ROOT_PATH.  'includes/lib_order.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'includes/helper/uploader.php');
require_once (ROOT_PATH . 'includes/debug/lib_log.php');
include_once(ROOT_PATH . 'includes/cls_json.php');

require_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');
require_once(ROOT_PATH . 'admin/distribution_order_address_analyze.php');

if(!in_array($_SESSION['admin_name'],array('mszeng','hli','mjzhou')))
{
	die('没有权限');
}

// 请求
 $request = isset($_REQUEST['request']) && 
    in_array($_REQUEST['request'], array('ajax')) 
    ? $_REQUEST['request'] 
    : null;
 $act = isset($_REQUEST['act']) && 
    in_array($_REQUEST['act'], array('search_goods','search_distributor','upload')) 
    ? $_REQUEST['act'] 
    : null;


 if ($request == 'ajax'){
    $json = new JSON;
 	
    switch($act){
 		// 搜索商品
        case 'search_goods':
        	
            $limit = (isset($_POST['limit']) && is_numeric($_POST['limit'])) ? $_POST['limit'] : 30 ;
            print $json->encode(distribution_get_goods_list(null, null, $_POST['q'], $limit));  
        break;
        
        //分销商搜索
        case 'search_distributor':
        	
        	$limit = (isset($_POST['limit']) && is_numeric($_POST['limit'])) ? $_POST['limit'] : 30 ;
        	print $json->encode(get_distributor($_POST['q'], $limit));
        break;
 		
 	}
 	
 	exit;
 	
 } 

// 信息
if ($info) {
    $smarty->assign('message', $info);
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
 );
$carrier_list = array(
//    '圆通快递' => 3,
//    'EMS快递' => 9,
//    '韵达快递' => 29,
    '汇通快递' => 28,
    'E邮宝快递' => 36,
    '中通快递' => 41,
);

 /*
  * 处理post请求
  */
 if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_REQUEST['act'] == 'upload') {
 	
 	
 	        $order_goods = array();
 	        $goods_amount = 0;
            
 			$distributor_id = $_REQUEST['hid_distributor_id'];
            $pay_id = $_REQUEST['pay_id'];
            $payment = get_payment($pay_id);
            if(!$payment){
            	Sys_msg("搜索支付方式没有成功。");
            }
            $facility_id = $_REQUEST['facility_id'];
            $user_id = get_user_id();
            if(!$user_id){
            	Sys_msg("搜索user_id没有成功。");
            }
            
 	        $main_goods_id = $_REQUEST['hid_main_goods_id'];
            $main_style_id = $_REQUEST['hid_main_style_id'];
            $main_goods = distribution_get_goods($main_goods_id, $main_style_id);
            if(!$main_goods){
            	Sys_msg("搜索商品1没有成功。");
            }
 
            $main_goods_num = $_REQUEST['main_goods_amount'];
            if(empty($main_goods_num)){
            	Sys_msg("商品1数量为空，或者未赋值");
            }
            //添加商品1
            $goods_item = array();
            $goods_item['goods_id'] = $main_goods['goods_id'];
            $goods_item['style_id'] = $main_goods['style_id'];
            $goods_item['price'] = $main_goods['shop_price'];
            $goods_item['goods_number'] = $main_goods_num;
            $order_goods[] = $goods_item;
            $goods_amount = $goods_item['price'] * $goods_item['goods_number'];
            unset($goods_item);
            
            //检测是否有第二个商品
	        if($_REQUEST['in_use'] == 'able'){
	            $child_goods_id = $_REQUEST['hid_child_goods_id'];
	            $child_style_id = $_REQUEST['hid_child_style_id'];
	            $child_goods = distribution_get_goods($child_goods_id, $child_style_id);
	            if(!$child_goods){
	            	Sys_msg("搜索商品2没有成功。");
	            }
	            $child_goods_num = $_REQUEST['child_goods_amount'];
	            if(empty($main_goods_num)){
	            	Sys_msg("商品2数量为空，或者未赋值");
	            }
	            //添加商品2
	            $goods_item = array();
            	$goods_item['goods_id'] = $child_goods['goods_id'];
            	$goods_item['style_id'] = $child_goods['style_id'];
            	$goods_item['price'] = $child_goods['shop_price'];
            	$goods_item['goods_number'] = $child_goods_num;
            	$order_goods[] = $goods_item;
            	$goods_amount += $goods_item['price'] * $goods_item['goods_number'];
            }
 	
           /**
            * 上传文件， 检查上传的excel格式，并读取数据提取插入到临时表 
            */
            if (!lock_acquire('distribution_order_import-upload')) {
                $smarty->assign('message', '导入操作正在被执行，请稍后执行'); 
                $smarty->display('distributor/dumex_taocan_order_import.htm');
                exit();
            }

            @set_time_limit(300);
            $uploader = new Helper_Uploader();
            $max_size = $uploader->allowedUploadSize();  // 允许上传的最大值

            if (!$uploader->existsFile('excel')) {
                $smarty->assign('message', '没有选择上传文件，或者文件上传失败');
                $smarty->display('distributor/dumex_taocan_order_import.htm');
                exit();
            }

            // 取得要上传的文件句柄
            $file = $uploader->file('excel');

            // 检查上传文件
            if (!$file->isValid('csv', $max_size)) {
                $smarty->assign('message', '非法的文件! 请检查文件类型类型(csv), 并且系统限制的上传大小为'. $max_size/1024/1024 .'MB');
                $smarty->display('distributor/dumex_taocan_order_import.htm');
                exit();
            }
            
            
            
            // 读取数据
            $imported_orders = array() ;
            $exists_taobao_order_sn = array() ;
            $error_msg = array();
            $batch_no = strtoupper($_SESSION['admin_name']) . '-'. time(); // 批次号
            $goodscode = null;
            $order_info = array(); 
            $goods_info = array();   
            $line_number = 0;
            if (($handle = fopen($file->filepath(), "r")) !== FALSE) {
               	 while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                      if(0 == $line_number){
                          $line_number ++ ;  
                          continue;
                      }
                      $taobao_order_sn = $data[0];
                      $distribution_purchase_order_sn = $data[0];
                      $consignee = $data[1];
                      $mobile = $data[2];
                      $shipping_name = $data[3];
                      $province = $data[4];
                      $city = $data[5];
                      $district = $data[6];
                      $address = $data[7];
                      $zipcode = $data[8];
                      $order_amount = $data[9];
                      $goods = $data[11];
                      $postscript = $data[14].' '.$data[15];
                      $taobao_user_id = $data[16];
                      // 填充数据信息
                      $order_info = array(
                              'taobao_order_sn' => $taobao_order_sn,
                              'distribution_purchase_order_sn' => $distribution_purchase_order_sn,
                              'consignee' => $consignee,
                              'mobile' => $mobile,
                              'order_amount' => $order_amount,
                              'distributor_id' => $distributor_id, 
                              'party_id' => $_SESSION['party_id'], 
                              'zipcode' => $zipcode,
                              'order_time' => $now,
                              'order_status' => 0,
                              'shipping_status' => 0,
                              'pay_status' => 2,
                              'shipping_id' => $shipping_list["$shipping_name"],
                              'shipping_name' => $shipping_name,
                              'shipping_fee' => 0,
                              'shipping_proxy_fee' => 0,
                              'pay_id' => $payment['pay_id'] ,             
                              'pay_name' => $payment['pay_name'],
                              'goods_amount' => $goods_amount, 
                              'carrier_bill_id' => '',
                              'order_type_id' => 'SALE', 
                              'facility_id' => $facility_id,   
                              'postscript' => $postscript,
                              'user_id' => $user_id,                   // 指定用户 
                              'pack_fee' => 0,          // 包装费
                            );
                    
                      // 分析订单地址, 将取得订单的 province, city, district, address
                      $order_info = array_merge($order_info, (array)distribution_order_address_analyze($province . $city . $district . $address));
                     
                      // 订单导入
                      $carrier_id = $carrier_list["$shipping_name" ];
                      $order_type = 'order_import';             // 多美滋货到付款
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
                          $db->query(sprintf("insert into ecshop.order_attribute (order_id, attr_name, attr_value) VALUES ( %d, 'OUTER_TYPE', '{$postscript}')", intval($order_id['order_id'])));
                          $db->query(sprintf("insert into ecshop.order_attribute (order_id, attr_name, attr_value) VALUES ( %d, 'TAOBAO_USER_ID', '{$taobao_user_id}')", intval($order_id['order_id'])));
                      }
               	 }
            }
            // 关掉文件
            fclose($handle);
            
            
            // 删除上传的文件
            $file->unlink();
            if ($exists_taobao_order_sn) {
                $smarty->assign('message', "成功导入订单数：". count($imported_orders) ."。该文件中有部分订单已经导入过了, 重复的订单编号: ". implode('， ', $exists_taobao_order_sn)); 
            } else {
                $smarty->assign('message', "数据导入成功。本次导入订单数：". count($imported_orders) .", 请仔细核对结果: ");
            }

            // 释放锁
            lock_release('distribution_order_import-upload');
            
            
            if (!empty($imported_orders)) {
                $smarty->assign('message', "成功生成订单, 请见下表，请修改订单的配送地址和配送方式等");
                // 查询出已生成订单列表
                $sql = "
                    select o.order_id, o.order_sn, o.order_amount, o.taobao_order_sn
                      from ecshop.ecs_order_info o 
                    where o.order_sn ". db_create_in($imported_orders);
                $imported_list = $db->getAll($sql);
                $smarty->assign('imported_list', $imported_list);
            } else {
                $smarty->assign('message', '没有订单生成。'. $message);
            }
            
            if(!empty($error_msg)){
            	$smarty->assign('message', implode('<br/>', $error_msg));
            }
 }
        

$pay_list = Helper_Array::toHashmap((array)getPayments(), 'pay_id', 'pay_name');
$smarty->assign('available_facility', get_available_facility($_SESSION['party_id']));
$smarty->assign('pay_list', $pay_list);
$smarty->display('distributor/batch_order_import.htm');

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
	    SELECT pay_id, IF(enabled=1, pay_name, CONCAT(pay_name, ' (已挂起)')) AS pay_name 
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