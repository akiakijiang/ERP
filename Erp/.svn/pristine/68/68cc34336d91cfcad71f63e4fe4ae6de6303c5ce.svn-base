<?php 

/**
 * 分销订单导入功能
 * 
 * @author yxiang@oukoo.com
 * @copyright 2009 ouku.com 
 */
 
define('IN_ECS', true);
require_once('includes/init.php');
require_once('distribution.inc.php');
// 权限
if(!in_array($_SESSION['admin_name'],array('jjhe','ychen','shyuan','zwsun','lchen', 'xtlai')))
{
	die('没有权限');
}

require_once('function.php');
require_once(ROOT_PATH.  'includes/lib_order.php');
require_once(ROOT_PATH . 'includes/cls_page.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'includes/helper/uploader.php');
require_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');


$act =  // 动作
    isset($_REQUEST['act']) && 
    in_array($_REQUEST['act'], array('upload', 'delete', 'import')) 
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


// 当前时间 
$now = date('Y-m-d H:i:s');

// Excel读取设置
$config = array(
    '采购单信息' => array(
        // 订单头信息
	    'taobao_order_sn' => '订单编号',
//	    'distribution_purchase_order_sn' => '采购单编号', 
	    'order_amount' => '订单总金额',
	    'shipping_fee' => '应付邮费',
	    'order_time' => '下订单时间',
	    'distributor_name' => '店铺名',
        'postscript' => '顾客留言',
        
        // 订单的商品
        'goods_code' => '商家编码',
        'goods_name' => '产品名称',
        'goods_number' => '商品数量',
        'goods_price' => '商品单价',
        'group_name' => '商品属性',
        
    ),
    '物流信息' => array(
        'taobao_order_sn' => '订单编号',
        'consignee' => '收货人',
        'address' => '收货地址',
        'shipping_name' => '送货方式',
        'tel' => '联系电话',
        'mobile' => '联系手机',
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
            // 订单导入的仓库
            $facility_id = intval($_POST['facility_id']);
            // 订单支付方式
            $pay_id = intval($_POST['pay_id']);
            if(empty($facility_id)){
            	$smarty->assign('message', '要先选择订单导入的仓库 后面才能继续，，'); 
                break;
            }
            if(empty($pay_id)){
                $smarty->assign('message', '要先填写导入订单支付方式 后面才能继续，，'); 
                break;
            }
            
            
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
            if (empty($data['采购单信息'])) {
                $smarty->assign('message', '不能读取到文件中的[采购单信息]的数据， 请检测文件格式');
                break;
            }
            else if (empty($data['物流信息'])) {
                $smarty->assign('message', '不能读取到文件中的[物流信息]的数据， 请检测文件格式');
                break;            	
            }

            // 分析数据取得订单头和商品的数据
            $orders = $goods = array();
            foreach ($data['采购单信息'] as $row1) { 
            	// if (empty($row1['taobao_order_sn']) || empty($row1['distribution_purchase_order_sn'])) {
            	if (empty($row1['taobao_order_sn']) ) {
            		$smarty->assign('message', '[采购单信息]表中存在空的淘宝订单号');
            		break 2;
            	}
      
            	// 单头
            	if ($row1['goods_code']=='-' && $row1['goods_name']=='-' && $row1['distributor_name']!='-' && $row1['order_time']!='-') {
            		// 匹配物流信息
                    foreach ($data['物流信息'] as $key2 => $row2) {
                    	if ($row1['taobao_order_sn'] == $row2['taobao_order_sn']) {
                    	   $orders[] = array_merge($row1,$row2);
                    	   unset($data['物流信息'][$key2]);  // 可减少下次循环次数	
                    	}
                    }
            	}
            	// 明细
            	else if ($row1['goods_name']!='-' && $row1['goods_code']!='-' && $row1['distributor_name']=='-' && $row1['order_time']=='-') {
                    $goods[] = $row1;
            	}
            	// 无法确定
            	else {
            		var_dump($row1);  die();
            		$smarty->assign('message', '格式错误，不知道该行是订单信息还是商品');
            		break 2;
            	}
            }

            // 取得淘宝订单号
            $osn = Helper_Array::getCols($orders, 'taobao_order_sn');
            // 检查订单数据中是否有重复的淘宝单号
            if (count($osn) > count(array_unique($osn))) {
                $smarty->assign('message', '[采购单信息]表中存在重复的淘宝订单号');
                break;                
            }
            
            // 商品数据读取失败
            if (count($goods) < count($orders)) {
                $smarty->assign('message', '数据解析失败，商品数少于订单数，请检查excel的文件格式是否已更改');
                break;
            }
      
            // 检查淘宝订单号是否导入过了 （没有删除的）
            $sql = "
            	SELECT taobao_order_sn FROM distribution_import_order_info 
            	WHERE deleted = 'N' AND taobao_order_sn ". db_create_in($osn);
            $exists = $db->getCol($sql);
            
            $batch_no = strtoupper($_SESSION['admin_name']) . '-'. time(); // 批次号
                    
            // 将订单数据保存到数据库中
            $segment = array();  // sql片段
            $count = 0;  // 导入淘宝订单数
            foreach ($orders as $order) {
                // 如果淘宝订单号已经存在了则不导入
                if (in_array($order['taobao_order_sn'], $exists)) {
                    continue;
                }
                $item = array();
                $item['taobao_order_sn'] = $order['taobao_order_sn'];
//                $item['distribution_purchase_order_sn'] = $order['distribution_purchase_order_sn'];
                $item['order_amount'] = $order['order_amount'];
                $item['shipping_fee'] = $order['shipping_fee'];
                $item['distributor_name'] = $order['distributor_name'];
                $item['postscript'] = $order['postscript'];
                $item['order_time'] = $order['order_time'];
                $item['consignee'] = $order['consignee'];
                $item['address'] = $order['address'];
                $item['shipping_name'] = $order['shipping_name'];
                $item['tel'] = $order['tel'];
                $item['mobile'] = $order['mobile'];
                $item['batch_no'] = $batch_no;
                $item['created_by_user_login'] = $_SESSION['admin_name'];
                $item['created'] = $now;
                $item['updated'] = $now;
                $item['facility_id'] = strval($facility_id);
                $item['pay_id'] = $pay_id;
                $segment[] = '('. implode(',', array_map(array(& $db, 'qstr'), $item)) .')';
                $count++;
            }
            if (empty($segment)) {
                $smarty->assign('message', '该文件没有订单需要导入，所有的订单号都已经存在了');
                break; 
            }
//            $sql = sprintf("
//            	INSERT INTO distribution_import_order_info (taobao_order_sn,distribution_purchase_order_sn,order_amount,shipping_fee,distributor_name,postscript,order_time,consignee,address,shipping_name,tel,mobile, batch_no,create_by_user_login,created,updated) VALUES %s", 
//                implode(', ', $segment)
//            );
            $sql = sprintf("
            	INSERT INTO distribution_import_order_info (taobao_order_sn,order_amount,shipping_fee,distributor_name,postscript,order_time,consignee,address,shipping_name,tel,mobile, batch_no,create_by_user_login,created,updated,facility_id,pay_id) VALUES %s", 
                implode(', ', $segment)
            );
            $result = $db->query($sql, 'SILENT');
            if (!$result) {
                $smarty->assign('message', '订单信息导入错误，错误信息：'. $db->errorMsg());
                break;
            }
            
            // 将商品数据保存到数据库中
            $segment = array();
            foreach ($goods as $g) {
                // 如果淘宝订单号已经存在了则不导入
                if (in_array($g['taobao_order_sn'], $exists)) {
                    continue;
                }
                $item = array();
                $item['taobao_order_sn'] = $g['taobao_order_sn'];
                $item['goods_code'] = $g['goods_code'];
                $item['goods_name'] = $g['goods_name'];
                $item['goods_number'] = $g['goods_number'];
                $item['goods_price'] = $g['goods_price'];
                $item['group_name'] = $g['group_name'];
                $item['batch_no'] = $batch_no;
                $item['created']  = $now;
                $segment[] = '('. implode(',', array_map(array(& $db, 'qstr'), $item)) .')';
            }
            $sql = sprintf(
                "INSERT INTO distribution_import_order_goods (taobao_order_sn,goods_code,goods_name,goods_number,goods_price,group_name, batch_no, created) VALUES %s", 
                implode(', ', $segment)
            );
            $result = $db->query($sql, 'SILENT');
            if (!$result) {
                $db->query("DELETE FROM distribution_import_order_info WHERE batch_no = '{$batch_no}'");  // 删除该批次号的订单信息
                $smarty->assign('message', '商品信息导入错误，错误信息 ：'. $db->errorMsg());
                break;
            }
            
            // 删除上传的文件
            $file->unlink();
            if ($exists) {
                $smarty->assign('message', "成功导入订单数：{$count}。该文件中有部分订单已经导入过了, 重复的淘宝订单号: ". implode(' ， ', $exists)); 
            } else {
                $smarty->assign('message', "数据导入成功。本次导入淘宝订单数：{$count}, 请仔细核对结果: ");
            }

            // 释放锁
            lock_release('distribution_order_import-upload');
        break;
        
        
        
        /**
         * 导入订单到正式数据表  
         */
        case 'import' :
            if (!lock_acquire('distribution_order_import-import')) {
                $smarty->assign('message', '导入操作正在被执行，请稍后再试!');
                break;
            }

            @set_time_limit(300);
            if (!is_array($_POST['checked']) || empty($_POST['checked'])) {
                $smarty->assign('message', '请选择要导入的淘宝订单');
                break;
            }
            
            // 检查传入的淘宝订单号是否存在订单中了
            $sql = "SELECT taobao_order_sn FROM {$ecs->table('order_info')} WHERE taobao_order_sn ". db_create_in($_POST['checked']);
            $exists = $db->getCol($sql);
            if ($exists) {
                $smarty->assign('message', '部分淘宝订单号已经存在系统中了不能生成订单：'. implode(' ， ', $exists));
                break;
            }
       
            // 按分销商名和PARTY_ID分组取得所有分销商
            $sql = "SELECT CONCAT_WS('_', party_id, name) AS flag, distributor_id, party_id, name FROM distributor WHERE status = 'NORMAL'";
            $distributor_list = Helper_Array::toHashmap((array)$db->getAll($sql), 'flag');

            $success = array();  // 成功生成的订单
            require_once('distribution_order_address_analyze.php');
            
            // 循环生成订单
            foreach ($_POST['checked'] as $taobao_order_sn) {
                // 为每个订单初始化数据
                $order = $order_goods = array();
                $party_id = $_SESSION['party_id'];
                
                // 要导入的订单 (未导入未删除过的)
                $import_order = $db->getRow("
                    SELECT * FROM distribution_import_order_info 
                    WHERE taobao_order_sn = '{$taobao_order_sn}' AND imported = 'N' AND deleted = 'N' LIMIT 1
                ");

                // 通过淘宝订单号没有查询到该订单
                if (!$import_order || empty($import_order['distributor_name'])) {
                    continue;
                }

                // 导入的订单商品 (必须和订单同一批次)
                $import_order_goods = $db->getAll("
                    SELECT * FROM distribution_import_order_goods 
                    WHERE taobao_order_sn = '{$import_order['taobao_order_sn']}' AND batch_no = '{$import_order['batch_no']}'
                ");
      
                // 订单的运费
                $total_shipping_fee = $import_order['shipping_fee'];
                    
                // 取得生成订单的商品
                if ($import_order_goods) {
                    $_goods_party_ids = array();
                    
                    foreach ($import_order_goods as $goods) {
                    	$item = array();

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
                            }
                        }
                        // 商品
                        else if (is_numeric($goods['goods_code'])) {
                            $g = distribution_get_goods($goods['goods_code']);
                            if ($g) {
                                $item['goods_id']     = $g['goods_id'];
                                $item['style_id']     = $g['style_id'];
                                $item['price']        = $goods['goods_price'];   // 商品价格，采用导入时的金额
                                $item['goods_number'] = $goods['goods_number'];  // 订单商品数量
                                
                                $_goods_party_ids[] = $g['goods_party_id']; 
                                $order_goods[] = $item;
                            }
                        }
                        // 导入的商品数据商品编码有误
                        else {
                            continue 2;      
                        }
                    }

                    
                    // 商品信息的party_id不一致 (订单的商品应该是同一个party_id)
                    $_goods_party_ids = array_unique($_goods_party_ids);
                    if (count($_goods_party_ids) != 1) {
                        continue;  // 跳出处理下一个订单
                    }
                    
                    // 通过商品的party_id来决定订单的party_id
                    $party_id = reset($_goods_party_ids);
                }
              
                // 订单信息
                $order = array(
                    'taobao_order_sn' => $import_order['taobao_order_sn'],
//                    'distribution_purchase_order_sn' => $import_order['distribution_purchase_order_sn'],
                    'consignee' => $import_order['consignee'],
                    'tel' => $import_order['tel'],
                    'mobile' => $import_order['mobile'],
                    'postscript' => $import_order['postscript'],   
                    'facility_id' => $import_order['facility_id'],
                    'pay_id' => $import_order['pay_id'],
                    'pack_fee' => 0, // 包装费为0
                    'shipping_proxy_fee' => 0,  // 手续费都为0
                    'shipping_fee' => $total_shipping_fee,  // 配送费用
                    'shipping_name' => $import_order['shipping_name'],  // 快递
                    'pay_status' => 2,  // 已付款
                    'user_id' => 1,  // 指定用户 
                    'party_id' => $party_id,  // 订单类型 （根据商品的party_id来决定）
                );

                // 分析订单地址, 将取得订单的 province, city, district, address
                $order = array_merge($order, (array)distribution_order_address_analyze($import_order['address']));
                
                // 取得订单对应的分销商
                $distributor = isset($distributor_list[$party_id . '_' .$import_order['distributor_name']])
                    ? $distributor_list[$party_id . '_' . $import_order['distributor_name']]
                    : false ;

                // 待导订单的分销商和我们系统中的分销商不匹配
                if ($distributor === false || $distributor['party_id'] != $party_id) {
                	continue; 
                }
                
                // 根据分销商来决定支付方式
                if ($distributor['distributor_id'] == 28) {  // OPPO淘宝商城
                    $order['pay_id'] = 68;  // 支付宝-手机商城 
                }
                else if ($distributor['distributor_id'] == 31) {  // 乐其数码专营店
                    $order['pay_id'] = 67;  // 支付宝-电教商城
                }
                
                // 订单信息 
                $order['distributor_id'] = $distributor['distributor_id'];  // 分销商ID
                $order['shipping_id'] = 85;                                 // 默认圆通
                $carrier_id = 3;                                            // 默认圆通
                $order_type = 'NON-COD';                                    // 先款后货
                
                // 分析导入订单的配送方式，信息维护在订单备注里，如果匹配不到则为顺风
                $_shipping_list = array (
                    'EMS' => array('shipping_id' => 47, 'carrier_id' => 9 ),
                    '顺丰' => array('shipping_id' => 44, 'carrier_id' => 10 ),
                    '顺风' => array('shipping_id' => 44, 'carrier_id' => 10 ),
                    '龙邦' => array('shipping_id' => 87, 'carrier_id' => 18 ), 
                    '韵达' => array('shipping_id' => 100, 'carrier_id' => 29 ), 
                    '汇通' => array('shipping_id' => 99, 'carrier_id' => 28 ), 
		            '圆通' => array('shipping_id' => 85, 'carrier_id' => 3 ), 
                );
                $shipping_find = array();
                foreach ($_shipping_list as $shipping_key => $shipping_value) {
                    if (strpos($order['shipping_name'], $shipping_key) !== false) {
                        $shipping_find[] = $shipping_value;
                    }
                }
                
                if (count($shipping_find) == 1) {
                    $shipping_result = $shipping_find[0];
                } elseif (count($shipping_find) > 1) {  // 出现两个快递方式标记为自提
                    $shipping_result = array('shipping_id' => 86, 'carrier_id' => 15);
                } else {    // 默认EMS
                    $shipping_result = array('shipping_id' => 47, 'carrier_id' => 9);
                }
                
                $order['shipping_id'] = $shipping_result['shipping_id'];
                $carrier_id = $shipping_result['carrier_id'];
                
//                // 如果杭州仓有货就转到杭州仓
//                if ($order_goods) {
//                   // 取得需要查询库存的列表
//                    foreach ($order_goods as $og) {
//                        $_goods_style_list[]=array('goods_id'=>$og['goods_id'],'style_id'=>$og['style_id']);
//                    }
//                    
//                    // 查询对应的仓库是否有库存
//                    $storage_list = getStorage('INV_STTS_AVAILABLE', $facility_id, $_goods_style_list);
//                    $facility_available = true;
//                    foreach ($order_goods as $og) {
//                        if (!isset($storage_list[$og['goods_id'].'_'.$og['style_id']]['qohTotal']) || 
//                            $storage_list[$og['goods_id'].'_'.$og['style_id']]['qohTotal'] < $og['goods_number']) {
//                            $facility_available=false;
//                            break;
//                        }
//                    }
//                    
//                }

                $message = '';
                $osn = distribution_generate_sale_order($order, $order_goods, $carrier_id, $order_type, $message);
                if(empty($osn['error'])) {
		        	$order_sn = $osn['order_sn'];
		        } else {
		        	$order_sn = false;
		        }
                if ($order_sn !== false) {
                    // 更新该淘宝订单标示为已导入
                    $db->query("
                        UPDATE distribution_import_order_info 
                        SET imported = 'Y', imported_by_user_login = '{$_SESSION['admin_name']}', refer_order_sn = '{$order_sn}', updated = NOW() 
                        WHERE order_id = '{$import_order['order_id']}' LIMIT 1
                    ");
                    $success[] = $order_sn;
                }
            }
            
            /**
             * 成功生成订单了，列出订单信息
             */
            if (!empty($success)) {
                $smarty->assign('message', "成功生成订单, 请见下表，请修改订单的配送地址和配送方式等");
                // 查询出已生成订单列表
//                $sql = "
//                    SELECT o.order_id, o.order_sn, o.order_amount, o.taobao_order_sn, o.distribution_purchase_order_sn,
//                        d.name AS distributor_name
//                    FROM {$ecs->table('order_info')} AS o 
//                        LEFT JOIN distributor AS d ON d.distributor_id = o.distributor_id
//                    WHERE o.distributor_id > 0 AND o.order_sn ". db_create_in($success);
                $sql = "
                    SELECT o.order_id, o.order_sn, o.order_amount, o.taobao_order_sn, o.shipping_name, o.pay_name,
                        d.name AS distributor_name
                    FROM {$ecs->table('order_info')} AS o 
                        LEFT JOIN distributor AS d ON d.distributor_id = o.distributor_id
                    WHERE o.distributor_id > 0 AND o.order_sn ". db_create_in($success);
                $imported_list = $db->getAll($sql);
                $smarty->assign('imported_list', $imported_list);
            } else {
                $smarty->assign('message', '没有订单生成。'. $message);
            }

            // 释放锁
            lock_release('distribution_order_import-import');
        break;
    }
}

// 删除动作
else if ($act && $act == 'delete') {
    $id = isset($_REQUEST['order_id']) && is_numeric($_REQUEST['order_id']) ? $_REQUEST['order_id'] : false ;
    if ($id) {
        $review = $db->getRow("SELECT * FROM distribution_import_order_info WHERE order_id = {$id} LIMIT 1");
        if ($review && $review['imported'] != 'Y') {
            // 删除订单
            $db->query("UPDATE distribution_import_order_info SET deleted = 'Y', updated='{$now}' WHERE order_id = '{$id}'");
            header("Location: temp_order_import.php?info=". urlencode('删除成功')); 
            exit;   
        } else {
            // 订单不存在或者该订单已经导入
            header("Location: temp_order_import.php?info=". urlencode('该订单不存在或者已导入不能删除')); 
            exit;               
        }
    }
}


/**
 * 显示
 */

// 查询每页显示数列表
$page_size_list = array('20' => '20', '50' => '50', '100' => '100', '200' => '200');
// 查询显示类型
$view_type_list = array('UNIMPORTED' => '未生成订单的', 'IMPORTED' => '已生成订单的', 'DELETED' => '已删除的');

$page =  // 当前页码
    is_numeric($_REQUEST['page']) && $_REQUEST['page'] > 0
    ? $_REQUEST['page'] 
    : 1 ;
$size =  // 每页多少记录数
    is_numeric($_REQUEST['size']) && $_REQUEST['size'] > 0
    ? $_REQUEST['size']
    : reset(array_keys($page_size_list)) ; 
$view =  // 显示模式
    isset($_REQUEST['view']) && trim($_REQUEST['view'])
    ? $_REQUEST['view']
    : reset(array_keys($view_type_list));
$filter = array(
    'size' => $size, 'view' => $view
);  
  
switch ($view) {
    case 'UNIMPORTED' :
        $conditions  = "deleted = 'N' AND imported = 'N'";
        break ;
    case 'IMPORTED' :
        $conditions  = "deleted = 'N' AND imported = 'Y'";
        break ;
    case 'DELETED' :
        $conditions  = "deleted = 'Y'";
        break ;    
}

// 订单总数 
$total = $db->getOne("SELECT COUNT(order_id) FROM distribution_import_order_info WHERE {$conditions}");

// 构造分页
$total_page = ceil($total/$size);  // 总页数
if ($page > $total_page) $page = $total_page;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $size;
$limit = $size; 

// 查询订单
$sql = "
    SELECT * 
    FROM distribution_import_order_info
    WHERE {$conditions} 
    ORDER BY created DESC, distributor_name, taobao_order_sn LIMIT {$offset}, {$limit}
";
$ref_fields = $ref_orders = array();
$order_list = $db->getAllRefby($sql, array('taobao_order_sn', 'batch_no', 'refer_order_sn'), $ref_fields, $ref_orders, false);

// 查询所有分销商, 用于检查导入的订单信息中 分销商名和支付宝账号是否存在并匹配
$distributor_name_list = (array)$db->getCol("SELECT name FROM distributor WHERE status = 'NORMAL'");

// 检查导入的订单数据是否正确
if ($order_list) {
    // 限定导入商品的批次号和淘宝订单号去匹配商品
    $sql = "
    	SELECT * 
    	FROM distribution_import_order_goods 
    	WHERE taobao_order_sn ". db_create_in($ref_fields['taobao_order_sn']) ." AND
    		batch_no ". db_create_in($ref_fields['batch_no']);
    $tmp = $ref_goods = array();
    $result = $db->getAllRefby($sql, array('taobao_order_sn'), $tmp, $ref_goods, false);

    // 查出订单ID
    $rons = $ref_fields['refer_order_sn']; Helper_Array::removeEmpty($rons);
    if (!empty($rons)) {
        $sql = "SELECT order_id, order_sn FROM {$ecs->table('order_info')} WHERE order_sn ". db_create_in($rons);
        $tmp2 = $ref_oid = array();
        $result = $db->getAllRefby($sql, array('order_sn'), $tmp2, $ref_oid, false);
    }
    
    foreach ($ref_orders['taobao_order_sn'] as $order_sn => $item) {
        $ref =& $ref_orders['taobao_order_sn'][$order_sn][0];  // 对订单记录的引用
        $ref['errno'] = 0;  // 错误代码

        // 取得已生成订单的ID
        $ref['refer_order_id'] = isset($ref_oid['order_sn'][$ref['refer_order_sn']])
            ? $ref_oid['order_sn'][$ref['refer_order_sn']][0]['order_id']
            : '' ;
        
        // 检查分销商是否匹配
        if (!$ref['distributor_name'] ||  // 没有分销商帐号
            !in_array($ref['distributor_name'], $distributor_name_list) ) {  // 或者系统不存在该分销商帐号
            $ref['errno']  = 1;
            $ref['errmsg'] = '没有分销商账号或该分销商不存在我们系统中';
        }
        
        // 检查是否有商品
        $matched = false;  // 是否匹配到商品
        $amount  = $ref['shipping_fee'];  // 商品的总金额
        if (isset($ref_goods['taobao_order_sn'][$order_sn]) 
            && !empty($ref_goods['taobao_order_sn'][$order_sn])) {
            foreach ($ref_goods['taobao_order_sn'][$order_sn] as $key => $g) {
                if ($g['batch_no'] == $ref['batch_no']) {  // 保证批次号匹配
                    $matched = true;
                    $amount += $g['goods_price'] * $g['goods_number'] ;
                    $ref['goods_list'][] = $ref_goods['taobao_order_sn'][$order_sn][$key];
                }
            }
        }
        if (!$matched) {
            $ref['errno']  = 2;
            $ref['errmsg'] = '该订单没有导入对应的商品信息';
        }

//        // 检查商品金额和订单金额是否匹配
//        $amount=round($amount,2);
//        if ($amount == 0 || $amount != $ref['order_amount']) {
//            $ref['errno']  = 3;
//            $ref['errmsg'] = '导入信息中商品金额和订单总金额不匹配';            
//        }
    }
}


// 构造分页
$pagination = new Pagination(
    $total, $size, $page, 'page', $url = 'temp_order_import.php', null, $filter
);


$pay_list = payment_list();
$$available_pay = array();
foreach($pay_list as $pay_item){
	$available_pay[$pay_item['pay_id']] = $pay_item['pay_name'];
}

$smarty->assign('filter', $filter);  // 过滤条件
$smarty->assign('page_size_list', $page_size_list);  // 每页显示数列表
$smarty->assign('view_type_list', $view_type_list);  // 显示类型
$smarty->assign('order_list', $order_list);
$smarty->assign('pagination', $pagination->get_simple_output());  // 分页

$smarty->assign('available_facility', get_available_facility());  // 发货仓选择
$smarty->assign('pay_list', $available_pay);

$smarty->display('distributor/temp_order_import.htm');

