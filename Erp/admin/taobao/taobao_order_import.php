<?php 

/**
 * 订单导入功能
 * 
 * @author yxiang@oukoo.com
 * @copyright 2009 ouku.com 
 */
 
define('IN_ECS', true);
require_once('../includes/init.php');
admin_priv('taobao_order_import');
require_once(ROOT_PATH . 'admin/function.php');
require_once(ROOT_PATH . 'admin/distribution.inc.php');
require_once(ROOT_PATH . 'includes/lib_order.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'includes/helper/uploader.php');
require_once(ROOT_PATH . 'includes/debug/lib_log.php');


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


$config = array(
    '订单信息' => array(
        'taobao_order_sn' => '订单编号',
        'email' => '买家支付宝账号',
        'goods_amount' => '买家应付货款',
        'shipping_fee' => '买家应付邮费',
        'order_amount' => '总金额',
        #'real_paid' => '买家实际支付金额',
        'postscript' => '买家留言',
        'consignee' => '收货人姓名',
        'address' => '收货地址',
        'tel' => '联系电话',
        'mobile' => '联系手机',
        #'order_time' => '订单创建时间',
        #'pay_time' => '订单付款时间',
        #'shipping_name' => '物流公司',
        #'note' => '订单备注',
        #'consignee' => '买家会员名',
        #'bill_no' => '物流单号',
        #'shipping_name' => '运送方式',
    ),
    '宝贝' => array(
        'taobao_order_sn' => '订单编号',
        'goods_id' => '外部系统编号',
        'style_id' => '商品属性',
        'price' => '价格',
        'goods_number' => '购买数量',
        #'goods_name' => '标题',
        #'note' => '备注',
    ),
);

/*
 * 处理post请求
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $act) {

    switch ($act) {
        /**
         * 上传文件， 检查上传的excel格式，并读取数据提取并添加收款 
         */
        case 'upload' :

QLog::log('淘宝订单导入：');
            /* 文件上传并读取 */
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
            
            // 读取excel
            $rowset = excel_read($file->filepath(), $config, $file->extname(), $failed);
            if (!empty($failed)) {
                $smarty->assign('message', reset($failed));
                break;
            }

            /* 检查数据  */
            
            // 数据读取失败
            if (empty($rowset)) {
                $smarty->assign('message', 'excel文件中没有数据,请检查文件');
                break;
            }
            
            // 订单信息
            if (empty($rowset['订单信息'])) {
                $smarty->assign('message', 'excel文件中没有订单信息');
                break;                
            }
            
            // 订单信息
            if (empty($rowset['宝贝'])) {
                $smarty->assign('message', 'excel文件中没有商品数据,请检查文件');
                break;                
            }
            
            $order_list = $rowset['订单信息'];  // 订单列表
            $goods_list = Helper_Array::groupBy($rowset['宝贝'], 'taobao_order_sn');  // 取得按淘宝订单号分组的商品列表

            $in = Helper_Array::getCols($order_list, 'taobao_order_sn');  // 淘宝订单号

            // 检查订单数据中是否有空白的淘宝单号
            $len = count($in);
            Helper_Array::removeEmpty($in);
            if (empty($in) || $len > count($in)) {
                $smarty->assign('message', '【订单信息】中存在空的淘宝订单号，请确保有数据的行都是完整的');
                break;
            }
            
            // 检查订单数据中是否有重复的淘宝单号
            if ($len > count(array_unique($in))) {
                $smarty->assign('message', '【订单信息】中存在重复的淘宝订单号');
                break;
            }
      
            // 检查淘宝订单号是否存在
            $sql = "SELECT taobao_order_sn FROM {$ecs->table('order_info')} WHERE taobao_order_sn ". db_create_in($in);
            $exists = $db->getCol($sql);
            if ($exists) {
                $smarty->assign('message', "这些淘宝订单号已经存在系统中了，不能导入：" . implode(', ', $exists));
                break;
            }
            
            // 地址分析
            require_once('distribution_order_address_analyze.php');
            
            // 生成订单
            foreach ($order_list as $order) {
                $order['pack_fee'] = 0;
                $order['shipping_proxy_fee'] = 0;
                $order['distributor_id'] = 0;
                $order['party_id'] = PARTY_OUKU_MOBILE;  // 订单类型
                $order['user_id'] = '248408';            // 淘宝欧酷 
                $order['pay_id'] = 65;                   // 支付宝－淘宝商城 
                $order['shipping_id'] = 47;              // 默认EMS
                $order_type = 'NON-COD';                 // 先款后货
                $carrier_id = 9;                         // 默认EMS
                
                // 去掉淘宝导出数据中的特殊字符
                $order['mobile'] = str_replace("'", '', $order['mobile']);
                $order['tel'] = str_replace("'", '', $order['tel']);
                
                // 分析订单地址, 将取得订单的 province, city, district, address
                $order = array_merge($order, (array)distribution_order_address_analyze($order['address']));
                
                // 订单的商品
                $order_goods = $goods_list[$order['taobao_order_sn']];
                
                $message = '';
                $osn = distribution_generate_sale_order($order, $order_goods, $carrier_id, $order_type, $message);
                if(empty($osn['error'])) {
		        	$order_sn = $osn['order_sn'];
		        } else {
		        	$order_sn = false;
		        }
                if ($order_sn === false) {
                    $order['errmsg'] = $message;
                    $import_report['failed'][] = $order;
                }
                // 成功生成订单
                else {
                    $success[] = $order_sn;
                }
QLog::log("淘宝订单导入：导入订单：". var_export($order, true) .", 导入结果  : ". var_export($order_sn, true));
            }
            
            // 删除上传的文件
            $file->unlink();
            $smarty->assign('payment_import_report', $payment_import_report);
            $smarty->assign('message', "导入完毕，查看导入报告"); 
        break;
    }
QLog::log("淘宝订单导入：结束。查看 错误信息：". $smarty->get_template_vars('message'));


    if (isset($success)) {
        $sql = "
            SELECT order_id, order_sn, order_amount, real_paid, taobao_order_sn 
            FROM {$ecs->table('order_info')} WHERE order_sn ". db_create_in($success);
        $import_report['successed'] = $db->getAll($sql);
    }
    
    // 导入报告
    $smarty->assign('import_report', $import_report);
}



/**
 * 显示
 */
$smarty->assign('tpls_list', $tpls_list);
$smarty->display('taobao/taobao_order_import.htm');


