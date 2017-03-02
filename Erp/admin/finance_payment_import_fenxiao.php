<?php 

/**
 * 财务分销收款导入功能 copy from finance_payment_import.php
 * 
 * @author jwang@i9i8.com
 * @copyright 2013 leqee.com 
 */
 
define('IN_ECS', true);
require_once('includes/init.php');
admin_priv('finance_order','cw_batch_payment_fenxiao');
require_once('function.php');
require_once(ROOT_PATH . 'RomeoApi/lib_payment.php');
require_once(ROOT_PATH . 'includes/lib_order.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'includes/helper/uploader.php');
require_once(ROOT_PATH . 'includes/debug/lib_log.php');
// require_once(ROOT_PATH . 'admin/includes/lib_order_mixed_status.php');

$start = isset($_REQUEST['start']) ? $_REQUEST['start'] : date('Y-m-d', strtotime("-3 months"));
$end = isset($_REQUEST['end']) ? $_REQUEST['end'] : date('Y-m-d');

$act =  // 动作
    isset($_REQUEST['act']) && 
    in_array($_REQUEST['act'], array('upload', 'delete', 'import')) 
    ? $_REQUEST['act'] 
    : null ;
$info =  // 返回的信息
    isset($_REQUEST['info']) && trim($_REQUEST['info']) 
    ? urldecode($_REQUEST['info']) 
    : false ; 
$tpl =  // 调用模板
    isset($_REQUEST['tpl']) && in_array($_REQUEST['tpl'], 
        array('leqee_distribution', 'leqee_distribution_nutricia', 
              'leqee_distribution_kimberlyClark', 'leqee_distribution_gallo',
              'leqee_distribution_anmum','leqee_distribution_mars','leqee_distribution_blackmores',
              'leqee_distribution_anyi','leqee_distribution_combi','leqee_distribution_hengshi',
              'leqee_distribution_huishi','leqee_distribution_dragonfly','leqee_distribution_pingshi',
			  'leqee_distribution_duola'))
    ? $_REQUEST['tpl']
    : false ;

// 信息
if ($info) {
    $smarty->assign('message', $info);
}

// 当前时间 
$now = date('Y-m-d H:i:s');

// excel读取设置
$tpls['leqee_distribution'] = array(
    '乐其分销收款' => array(
        'alipay_no' => '支付宝交易号',
        'pay_cost' => '实收金额',
        'pay_time' => '收款时间',
        'pay_note' => '收款备注信息',
    ),
);

$tpls['leqee_distribution_nutricia'] = array(
    'nutricia分销收款' => array(
        'alipay_no' => '支付宝交易号',
        'pay_cost' => '实收金额',
        'pay_time' => '收款时间',
        'pay_note' => '收款备注信息',
    ),
);

$tpls['leqee_distribution_kimberlyClark'] = array(
    '金佰利分销收款' => array(
        'alipay_no' => '支付宝交易号',
        'pay_cost' => '实收金额',
        'pay_time' => '收款时间',
        'pay_note' => '收款备注信息',
    ),
);


$tpls['leqee_distribution_gallo'] = array(
    'gallo分销收款' => array(
        'alipay_no' => '支付宝交易号',
        'pay_cost' => '实收金额',
        'pay_time' => '收款时间',
        'pay_note' => '收款备注信息',
    ),
);

$tpls['leqee_distribution_anmum'] = array(
    '安满分销收款' => array(
        'alipay_no' => '支付宝交易号',
        'pay_cost' => '实收金额',
        'pay_time' => '收款时间',
        'pay_note' => '收款备注信息',
    ),
);


$tpls['leqee_distribution_mars'] = array(
    '玛氏分销收款' => array(
        'alipay_no' => '支付宝交易号',
        'pay_cost' => '实收金额',
        'pay_time' => '收款时间',
        'pay_note' => '收款备注信息',
    ),
);


$tpls['leqee_distribution_blackmores'] = array(
    'blackmores分销收款' => array(
        'alipay_no' => '支付宝交易号',
        'pay_cost' => '实收金额',
        'pay_time' => '收款时间',
        'pay_note' => '收款备注信息',
    ),
);



$tpls['leqee_distribution_anyi'] = array(
	'安怡分销收款' => array(
		'alipay_no' => '支付宝交易号',
		'pay_cost' => '实收金额',
		'pay_time' => '收款时间',
		'pay_note' => '收款备注信息',
	),
);
$tpls['leqee_distribution_combi'] = array(
	'康贝分销收款' => array(
		'alipay_no' => '支付宝交易号',
		'pay_cost' => '实收金额',
		'pay_time' => '收款时间',
		'pay_note' => '收款备注信息',
	),
);

$tpls['leqee_distribution_hengshi'] = array(
    '亨氏分销收款' => array(
        'alipay_no' => '支付宝交易号',
        'pay_cost' => '实收金额',
        'pay_time' => '收款时间',
        'pay_note' => '收款备注信息',
    ),
);

$tpls['leqee_distribution_huishi'] = array(
    '惠氏分销收款' => array(
        'alipay_no' => '支付宝交易号',
        'pay_cost' => '实收金额',
        'pay_time' => '收款时间',
        'pay_note' => '收款备注信息',
    ),
);

$tpls['leqee_distribution_dragonfly'] = array(
    'Dragonfly分销收款' => array(
        'alipay_no' => '支付宝交易号',
        'pay_cost' => '实收金额',
        'pay_time' => '收款时间',
        'pay_note' => '收款备注信息',
    ),
);

$tpls['leqee_distribution_pingshi'] = array(
    '香港平世分销收款' => array(
        'alipay_no' => '支付宝交易号',
        'pay_cost' => '实收金额',
        'pay_time' => '收款时间',
        'pay_note' => '收款备注信息',
    ),
);

$tpls['leqee_distribution_duola'] = array(
    '台湾哆啦分销收款' => array(
        'alipay_no' => '支付宝交易号',
        'pay_cost' => '实收金额',
        'pay_time' => '收款时间',
        'pay_note' => '收款备注信息',
    ),
);

// 由用户的party_id和权限来决定可以选择业务
$tpls_list = array();
if (party_check(PARTY_LEQEE, $_SESSION['party_id'])) {
    $tpls_list += array('leqee_distribution' => '乐其分销收款');
} elseif (party_check(65548, $_SESSION['party_id'])) {
    $tpls_list += array('leqee_distribution_nutricia' => 'nutricia分销收款');
} elseif (party_check(65558, $_SESSION['party_id'])) {
    $tpls_list += array('leqee_distribution_kimberlyClark' => '金佰利分销收款');
} elseif (party_check(65555, $_SESSION['party_id'])) {
    $tpls_list += array('leqee_distribution_gallo' => 'gallo分销收款'); 
} elseif (party_check(65569, $_SESSION['party_id'])) {
    $tpls_list += array('leqee_distribution_anmum' => '安满分销收款');       
} elseif (party_check(65572, $_SESSION['party_id'])) { 
	$tpls_list += array('leqee_distribution_mars' => '玛氏分销收款');
} elseif (party_check(65571, $_SESSION['party_id'])) { 
	$tpls_list += array('leqee_distribution_blackmores' => 'blackmores分销收款');
}elseif (party_check(65581, $_SESSION['party_id'])) {
	$tpls_list += array('leqee_distribution_anyi' => '安怡分销收款');
}elseif (party_check(65586, $_SESSION['party_id'])) {
	$tpls_list += array('leqee_distribution_combi' => '康贝分销收款');
}elseif (party_check(65609, $_SESSION['party_id'])) {
	$tpls_list += array('leqee_distribution_hengshi' => '亨氏分销收款');
}elseif (party_check(65617, $_SESSION['party_id'])) {
	$tpls_list += array('leqee_distribution_huishi' => '惠氏分销收款');
}elseif(party_check(65543, $_SESSION['party_id']))  {
	$tpls_list += array('leqee_distribution_dragonfly' => 'Dragonfly分销收款');
}elseif(party_check(65566, $_SESSION['party_id']))  {
	$tpls_list += array('leqee_distribution_pingshi' => '香港平世分销收款');
}elseif(party_check(65630, $_SESSION['party_id']))  {
	$tpls_list += array('leqee_distribution_duola' => '台湾哆啦分销收款');
}

/*
 * 处理post请求
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $act) {

    switch ($act) {
        /**
         * 上传文件， 检查上传的excel格式，并读取数据提取并添加收款 
         */
        case 'upload' :
            if (!$tpl) {
                $smarty->assign('message', '没有选择导入业务');
                break;
            }
QLog::log('收款导入：');
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
            $result = excel_read($file->filepath(), $tpls[$tpl], $file->extname(), $failed);
            if (!empty($failed)) {
                $smarty->assign('message', reset($failed));
                break;
            }
         
            /* 检查数据  */
            if ($tpl == 'leqee_distribution'){
                $rowset = $result['乐其分销收款'];
            }
            else if ($tpl == 'leqee_distribution_nutricia') {
                $rowset = $result['nutricia分销收款'];
            }
            else if ($tpl == 'leqee_distribution_kimberlyClark') {
                $rowset = $result['金佰利分销收款'];
            }
            else if ($tpl == 'leqee_distribution_gallo') {
                $rowset = $result['gallo分销收款'];
            }
            else if ($tpl == 'leqee_distribution_anmum') {
                $rowset = $result['安满分销收款'];
            }
            else if ($tpl == 'leqee_distribution_mars') {                       
            	$rowset = $result ['玛氏分销收款'];
            }
            else if ($tpl == 'leqee_distribution_blackmores') {                       
            	$rowset = $result ['blackmores分销收款'];
            }
            else if ($tpl == 'leqee_distribution_anyi') {
                $rowset = $result['安怡分销收款'];
            }
            else if ($tpl == 'leqee_distribution_combi') {
                $rowset = $result['康贝分销收款'];
            }
            else if ($tpl == 'leqee_distribution_hengshi') {
                $rowset = $result['亨氏分销收款'];
            }
            else if ($tpl == 'leqee_distribution_huishi') {
                $rowset = $result['惠氏分销收款'];
            }
            else if ($tpl == 'leqee_distribution_dragonfly'){
            	$rowset = $result['Dragonfly分销收款'];
			}
			else if ($tpl == 'leqee_distribution_duola'){
            	$rowset = $result['台湾哆啦分销收款'];
			}
			else if ($tpl == 'leqee_distribution_pingshi'){
            	$rowset = $result['香港平世分销收款'];
			}
            
            $ref_field = 'alipay_no';
            
            // 订单数据读取失败
            if (empty($rowset)) {
                $smarty->assign('message', 'excel文件中没有数据,请检查文件');
                break;
            }

            $in = Helper_Array::getCols($rowset, $ref_field);  // 构造IN查询
            
            // 检查订单数据中是否有空白的淘宝单号
            $len = count($in);
            Helper_Array::removeEmpty($in);
            if (empty($in) || $len > count($in)) {
                $smarty->assign('message', '文件中存在空的订单号，请确保有数据的行都是完整的');
                break;
            }
            
            // 检查订单数据中是否有重复的淘宝单号
            if ($len > count(array_unique($in))) {
                $smarty->assign('message', '文件中存在重复的订单号');
                break;
            }

QLog::log("收款导入：EXCEL文件分析通过，采用的模板信息为：". print_r($tpls[$tpl], true) ."，开始检查订单是否存在");
            // 查询出对应的订单
            $sql = "
                SELECT
                    o.order_id, o.order_sn, o.pay_id, o.order_amount, o.real_paid, o.taobao_order_sn, o.pay_time, 
                    o.pay_number as alipay_no, u.userId, count(o.order_id) AS num
                FROM
                    {$ecs->table('order_info')} o force index(order_info_multi_index)
                    LEFT JOIN {$ecs->table('users')} u ON u.user_id = o.user_id 
                WHERE
                   	o.order_time >= '{$start}' and o.order_time < date_add('{$end}', INTERVAL 1 day)
                    and o.party_id = {$_SESSION['party_id']}
                    and o.order_type_id = 'SALE' 
                    and o.pay_number ". db_create_in($in) ."
                GROUP BY o.pay_number
            ";
// 			QLog::log($sql);
            $orders = Helper_Array::toHashmap((array)$db->getAll($sql), $ref_field);
            if (empty($orders) || (count(array_keys($orders)) != count($in)) ) {
                $smarty->assign('message', '根据这些单号查询不到的订单：'. implode('， ', array_diff($in, array_keys($orders))));
                break;
            }

            /* 添加收款  */
QLog::log("收款导入：数据检查通过，开始添加收款");
            $payment_import_report = array();
            $i = 0;
            foreach ($rowset as $key => $row) {

                if (!array_key_exists($row[$ref_field], $orders)) {                    
                    $payment_import_report['failed'][$i] = $row;
                    $payment_import_report['failed'][$i]['errmsg'] = '系统中找不到该订单';
QLog::log("收款导入：根据单号{$row[$ref_field]} ({$ref_field}) 找不到对应的订单 ");    
                }
                else if (!is_numeric($row['pay_cost']) || $row['pay_cost'] <= 0 ) { 
                    $payment_import_report['failed'][$i] = $row;
                    $payment_import_report['failed'][$i]['errmsg'] = '收款金额错误错误';
QLog::log("收款导入：订单{$row[$ref_field]} ({$ref_field}) 的收款金额错误 ");    
                }
                else {
                    $order = $orders[$row[$ref_field]];  // 订单
                    //考虑到乐其OPPO支付宝收款没有淘宝订单号的情况，直接默认跳过
                    $duplicate = 1;
                    $taobao_order_sn = substr($order['taobao_order_sn'],0,15);
                    //由于淘宝订单号重复，客服会添加'_%'，于是判断淘宝订单号在系统中重复有新的方法。
                    $sql = "
                       SELECT count(taobao_order_sn)
                       FROM ecshop.ecs_order_info 
                       WHERE party_id = {$_SESSION['party_id']}  and order_type_id = 'SALE' and taobao_order_sn like '{$taobao_order_sn}%' and order_status != '2'
	                       ";
	                    $duplicate = $slave_db -> getOne($sql);
                    if ($order['num'] > 1 || $duplicate != 1) {  // 淘宝订单号对应的订单有多个
                        $payment_import_report['failed'][$i] = $order;
                        $payment_import_report['failed'][$i]['errmsg'] = "该淘宝订单号{$taobao_order_sn}对应的有多个订单";
QLog::log("收款导入[异常]：淘宝订单号{$taobao_order_sn} 对应多个订单");
                    }
                    else if ($order['real_paid'] + $row['pay_cost'] > $order['order_amount']) {  // 实收将要大于应收了
                        $payment_import_report['failed'][$i] = $order;
                        $payment_import_report['failed'][$i]['errmsg'] = '实收大于应收';
QLog::log("收款导入[异常]：订单{$order['order_sn']} 实收将要大于应收，不能添加收款");
                    }
                    else {
                        $ret = paytrans_receive($order, $row['pay_cost'], $row['pay_note'], $order['userId'], $failed);
                        if ($ret === false) {
                            $payment_import_report['failed'][$i] = $order;
                            $payment_import_report['failed'][$i]['errmsg'] = reset($failed);                        
                        } else {
                            $order['real_paid'] += $row['pay_cost']; 
                            $payment_import_report['successed'][$i] = $order;
                            $payment_import_report['successed'][$i]['pay_cost'] = $row['pay_cost'];      
                            $payment_import_report['successed'][$i]['pay_note'] = $row['pay_note'];
                            $payment_import_report['successed'][$i]['diff'] = $order['order_amount'] - $order['real_paid'];
                            
                            // 更新订单的收款时间
                            //收款后更改订单的收款状态
                            $sql = "select order_id, order_status, pay_status, shipping_status, order_amount, pay_id from {$ecs->table('order_info')} where order_id = '{$order['order_id']}'";
                            $data_order_action = $db->getRow($sql);                         
                            if (!$order['pay_time']) {
                                $pay_time = $row['pay_time'] && strtotime($row['pay_time']) ? strtotime($row['pay_time']) : time() ;
                                $payment_import_report['successed'][$i]['pay_time'] = $pay_time;
                                //添加支付状态的修改2011-8-18
                                $db->query("UPDATE {$ecs->table('order_info')} SET pay_time = '{$pay_time}'  WHERE order_id = '{$order['order_id']}' LIMIT 1");

                                //判断实收订单金额大于等于订单金额时更改订单状态
                                if($row['pay_cost'] >= $data_order_action['order_amount']){
                                	$condition = '' ;
                                	if ($data_order_action['pay_id'] == '1' && $data_order_action['shipping_status'] == '1') {
                                		// COD订单收款后， 直接更新收货确认（根据运单号更新状态漏掉的）
                                		$condition = ', shipping_status = 2 ';
                                		$data_order_action['shipping_status'] = 2;
                                	}
                                    $db->query("UPDATE {$ecs->table('order_info')} SET pay_status = 2 ". $condition ." WHERE order_id = '{$order['order_id']}' LIMIT 1");
                                    $data_order_action['pay_status'] = 2;
                                }
                            }
                            $sql = "insert into {$ecs->table('order_action')} (order_id, action_user, shipping_status, order_status, pay_status, action_time, action_note)
                                    values ('{$order['order_id']}', '{$_SESSION['admin_name']}', '{$data_order_action['shipping_status']}', '{$data_order_action['order_status']}', '{$data_order_action['pay_status']}', now(),'批量收款,金额  {$row['pay_cost']}') ";
                            $db->query($sql);
                            // update_order_mixed_status($order['order_id'], array('pay_stauts' => 'paid'), 'worker','批量收款');
                        }
QLog::log("收款导入：订单{$order['order_sn']} 收款结果：交易金额  {$row['pay_cost']}, 支付交易号为 " . var_export($ret, true));
                    }
                }
                
                $i++;
            }
            
            // 删除上传的文件
            $file->unlink();
            $smarty->assign('payment_import_report', $payment_import_report);
            $smarty->assign('message', "导入完毕，查看导入报告"); 
        break;
    }
QLog::log("收款导入：结束。查看 错误信息：". $smarty->get_template_vars('message'));
}



/**
 * 显示
 */
$smarty->assign('tpls_list', $tpls_list);
$smarty->assign('start', $start);
$smarty->assign('end', $end);
$smarty->display('finance/finance_payment_import_fenxiao.htm');

