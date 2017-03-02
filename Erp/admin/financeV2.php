<?php

/**
 * 财务 - 订单管理
 */
define('IN_ECS', true);
require_once('includes/init.php');
admin_priv('finance_order');
require_once("function.php");
require_once(ROOT_PATH. 'RomeoApi/lib_payment.php');
require_once(ROOT_PATH. 'includes/lib_order.php');
include_once(ROOT_PATH. 'RomeoApi/lib_currency.php');
// require_once(ROOT_PATH . 'admin/includes/lib_order_mixed_status.php');
require_once(ROOT_PATH. 'includes/debug/lib_log.php');
$csv = $_REQUEST['csv'];
if ($csv) {
    admin_priv('4cw_finance_manage_order_csv');
}

$sql="SELECT
        IS_LEAF
    FROM
        romeo.party p
    WHERE
        p.PARTY_ID = '{$_SESSION['party_id']}'
    LIMIT 1
";
$r=$db->getOne($sql);
if($r=='N'){
   die('<h1>请选择具体的业务组织。╮(╯_╰)╭</h1>');
}

$search_types = array(
    'order_amount'     => '订单金额',
    'customer'         => '客户名',
    'erp_goods_sn'     => '商品串号',
    'shipping_invoice' => '销售发票号',
    'in_sn'            => '入库单号',
    'out_sn'           => '出库单号',
);

$all_pay_status = array(
    0 => '未付款',
//	1 => '付款中',
	2 => '已付款',
//	3 => '待退款',
	4 => '已退款',
);

$smarty->assign('search_types', $search_types);

$submit = $_REQUEST['submit'];

$size = $_REQUEST['size'] ? $_REQUEST['size'] : 10;
$page = $_REQUEST['page'] ? $_REQUEST['page'] : 1;
$start = ($page - 1) * $size;

if ($csv == null) {
    $limit = "LIMIT $size";
    $offset = "OFFSET $start";
}


/* ajax请求，创建支付交易 */
if (isset($_REQUEST['request']) && $_REQUEST['request']=='ajax')
{
    // 返回的结果
    $result  = false;
    $message = '';

    do
    {
        if (!$order = order_info((int)$_POST['order_id']))
        {
            $message = '订单不存在';
            break;
        }

        // 取得用户信息
        $user = user_info($order['user_id']);

        // 查询是否有已存在的支付交易
        try
        {
            $handle = paytrans_get_soap_client();
            $response = $handle->getPaymentTransactionByOrderId($order['order_id']);
        }
        catch (SoapFault $e)
        {
            $message = "SOAP查询该订单的支付交易失败: (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})";
            break;
        }

        // 已存在支付交易，则尝试匹配
        if ($response->total > 0)
        {
            $used    = true;   // 已存在的支付交易是否已全部使用
            $matched = false;  // 未使用的支付交易是否有匹配的

            $list = array();
            if (is_object($response->resultList->PaymentTransaction))
                $list[0] = $response->resultList->PaymentTransaction;
            else if (is_array($response->resultList->PaymentTransaction))
                $list = $response->resultList->PaymentTransaction;

            // 未使用的支付金额
            $pmt_trans_received = array();
            foreach ($list as $item)
            {
                if ($item->status == PMT_TRANS_STTS_RECEIVED) // 如果有支付交易未使用
                {
                    $used = false;
                    $pmt_trans_received[] = sprintf('%01.2f', $item->receivedAmount);

                    if ((float)$item->receivedAmount === (float)$_POST['received_amount'])
                    {
                        if (!$matched)  // 有多比交易匹配时，只匹配一次
                        {
                            $matched = true;
                            $transId = $item->paymentTransactionId;
                        }
                    }
                }
            }

            // 已存在没使用的支付交易但没有匹配金额的，则跳出， 并提示未使用的支付交易金额
            if (!$used && !$matched)
            {
                $message = '没有一笔未使用的支付交易与输入的金额相匹配，已存在未使用的支付金额分别为:'. implode(',', $pmt_trans_received);
                break;
            }
        }

        // 不存在支付交易, 或者存在支付交易但已全部使用，则创建一条支付交易
        if ($response->total == 0 || $used === true)
        {
            try
            {
                // 构造交易对象
                $trans = new stdClass();
                $trans->orderId            = $order['order_id'];
                $trans->payId              = $order['pay_id'];
                $trans->status             = PMT_TRANS_STTS_RECEIVED;
                $trans->note               = $_POST['note'];
                $trans->receivedAmount     = $_POST['received_amount'];  // 为收到的金额
                $trans->accountFrom        = $user['userId'];  // 为付款的来源（如果是用户，为用户的user_name）
                $trans->accountTo          = 'OUKU';
                $trans->createdByUserLogin = $_SESSION['admin_name'];

                $transId = $handle->createPaymentTransaction($trans);
            }
            catch (SoapFault $e)
            {
                $message = "SOAP创建支付交易失败: (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})";
                break;
            }
        }

        // 使用一笔支付交易
        if ($transId)
        {
            try
            {
                $amount = $handle->usePaymentTransaction($transId, $_POST['note']);
                // 更新订单 real_paid
                $real_paid = (float)$order['real_paid'] + (float)$amount;
                update_order($order['order_id'], array('real_paid' => $real_paid));
            }
            catch (SoapFault $e)
            {
                $message = "SOAP使用交易支付失败: (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})";
                break;
            }
        }

        $message = "操作成功, 交易金额:{$amount}，现在订单({$order['order_sn']})的实付金额为:{$real_paid}, 请等待页面刷新";
        $result  = true;

    } while (false);
    
    // 更新订单的收款时间
    //收款后更改订单的收款状态
    $sql = "select order_id, order_status, pay_status, shipping_status, order_amount, pay_id from {$ecs->table('order_info')} where order_id = '{$order['order_id']}'";
    $data_order_action = $db->getRow($sql);                            
    if (!$order['pay_time']) {
    	$received_time = $_POST['received_time'];
        $pay_time = $received_time && strtotime($received_time) ? strtotime($received_time) : time() ;  
        //添加支付状态的修改2011-8-18
        $db->query("UPDATE {$ecs->table('order_info')} SET pay_time = '{$pay_time}'  WHERE order_id = '{$order['order_id']}' LIMIT 1");

        //判断实收订单金额大于等于订单金额时更改订单状态
        if($real_paid >= $data_order_action['order_amount']){
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
            values ('{$order['order_id']}', '{$_SESSION['admin_name']}', '{$data_order_action['shipping_status']}', '{$data_order_action['order_status']}', '{$data_order_action['pay_status']}', now(),'财务收款，金额{$amount}') ";
    $db->query($sql);
    // update_order_mixed_status($order['order_id'], array('pay_stauts' => 'paid'), 'worker','财务收款');     

    print json_encode(compact('result', 'message'));
    exit;
}


$act_voucher = $_REQUEST['act_voucher'];
if ($act_voucher=='search') {
    $voucher_no = $_REQUEST['voucher_no'];
    if ($voucher_no=='') {
        $condition = 'AND false';
    } else {
        $batch_order_sn_real_paid = array();
        $batch_order_sn_real_paid_list = array();
        $batch_order_sn_real_paid_str = preg_split('/[\s]+/',trim($_REQUEST['batch_order_sn_real_paid']));
        $order_sn_voucher = '';
        $order_sns_voucher = array();

        foreach ($batch_order_sn_real_paid_str as $entry) {
            if ($order_sn_voucher=='') {
                $order_sn_voucher = $entry;
                $order_sns_voucher[] = $entry;
            } else {
                if (empty($batch_order_sn_real_paid[$order_sn_voucher])) {
                    $batch_order_sn_real_paid[$order_sn_voucher] = 0;
                }
                $entry = floatval($entry);
                $batch_order_sn_real_paid[$order_sn_voucher] += $entry;
                $batch_order_sn_real_paid_list[] = array('sn' =>$order_sn_voucher, 'paid' =>$entry);
                $order_sn_voucher = '';
            }
        }
        if (!empty($order_sns_voucher)) {
            $condition = " AND " . db_create_in($order_sns_voucher, "order_sn");
            //过滤非法输入
            $sql = "select order_sn, order_id from {$ecs->table('order_info')} where 1 {$condition}";
            $order_id_list = $db->getAll($sql);
            $order_ids = array();
            foreach ($order_id_list as $entry) {
                $order_ids[$entry['order_sn']] = $entry['order_id'];
            }
            foreach ($batch_order_sn_real_paid as $order_sn_tmp => $paid) {
                if (empty($order_ids[$order_sn_tmp])) {
                    unset($batch_order_sn_real_paid[$order_sn_tmp]);
                }
            }
            foreach ($batch_order_sn_real_paid_list as $key => $entry) {
                if (empty($order_ids[$entry['sn']])) {
                    unset($batch_order_sn_real_paid_list[$key]);
                }
            }
            $smarty->assign('voucher_amount_total', array_sum($batch_order_sn_real_paid));
            $smarty->assign('voucher_rec_total', count($batch_order_sn_real_paid_list));
        } else {
            $condition = 'AND false';
        }
    }
} else {
    $condition = getCondition();
}

$currencies = array(' ' => '不限', 'RMB' => '人民币');

$smarty->assign('currencies', $currencies);

if ($_REQUEST['act'] != 'search') {
    // 如果没有搜索条件，那么只显示模板
    $smarty->display('oukooext/financeV2.htm');
    exit();
}

// 查询出符合条件的订单列表和订单数
// $sql = "SELECT info.order_id, order_sn, cb.bill_no, order_time, order_status, order_amount, 
// 		   goods_amount, shipping_fee, shipping_status, shipping_name, 
// 		   integral_money, bonus, consignee, pay_id, pay_status, real_paid, 
// 		   real_shipping_fee, proxy_amount, pay_method, is_finance_clear, 
// 		   pack_fee, order_type_id, additional_amount, info.currency, taobao_order_sn,poi.is_finance_paid,poi.purchaser,osi.shipping_invoice
// 	FROM {$ecs->table('order_info')} AS info use index(order_info_multi_index,order_sn,taobao_order_sn)
// 	LEFT JOIN {$ecs->table('carrier_bill')} cb ON cb.bill_id = carrier_bill_id 
// 	left join romeo.purchase_order_info as poi on poi.order_id = info.order_id
// 	left join romeo.order_shipping_invoice osi on osi.order_id = info.order_id
// 	WHERE 1 
// 		{$condition} 
// 	ORDER BY info.order_time DESC $limit $offset";
// $sqlc = "
// 	SELECT COUNT(*) 
// 	FROM {$ecs->table('order_info')} AS info use index(order_info_multi_index,order_sn,taobao_order_sn)
// 	LEFT JOIN {$ecs->table('carrier_bill')} cb ON cb.bill_id = carrier_bill_id 
// 	WHERE 1 
// 		{$condition}";
// 以上SQL由于ECB团灭而被改改改
$sql = "SELECT info.order_id, order_sn, 
        (select s.tracking_number
        from romeo.order_shipment os 
        inner join romeo.shipment s ON os.shipment_id = s.shipment_id
        where
        os.order_id = CONVERT (info.order_id USING utf8)
        AND (s. STATUS is null or s. STATUS != 'SHIPMENT_CANCELLED')
        limit 1
        ) as bill_no,
        order_time, order_status, order_amount, 
        goods_amount, shipping_fee, shipping_status, shipping_name, 
        integral_money, bonus, consignee, pay_id, pay_status, real_paid, 
        real_shipping_fee, proxy_amount, pay_method, is_finance_clear, 
        pack_fee, order_type_id, additional_amount, info.currency, taobao_order_sn,poi.is_finance_paid,poi.purchaser,osi.shipping_invoice
    FROM {$ecs->table('order_info')} AS info use index(order_info_multi_index,order_sn,taobao_order_sn)
    left join romeo.purchase_order_info as poi on poi.order_id = info.order_id
    left join romeo.order_shipping_invoice osi on osi.order_id = info.order_id
    WHERE 1 
        {$condition} 
    ORDER BY info.order_time DESC $limit $offset";
$sqlc = "SELECT COUNT(*) 
    FROM {$ecs->table('order_info')} AS info force index(order_info_multi_index,order_sn,taobao_order_sn)
    -- LEFT JOIN romeo.order_shipment os ON os.order_id = CONVERT (info.order_id USING utf8)
    -- LEFT JOIN romeo.shipment s ON os.shipment_id = s.shipment_id
    WHERE 1 
    -- AND (s. STATUS is null or s. STATUS != 'SHIPMENT_CANCELLED')
        {$condition}
    -- GROUP BY info.order_id
    ";

//Qlog::log($sql);
//Qlog::log($sqlc);
$orders = $db->getAll($sql);
$count = $db->getOne($sqlc);

if(trim($_REQUEST['batch_taobao_order_sn']) != ''){
    $order_h = batch_taobao_h();
    if(!empty($order_h)){
	   $orders = array_merge($orders,$order_h);
	   $count = $count + $flag;
    }
}
$pager = Pager($count, $size, $page);
foreach ($orders as $key => $order) {
    // 查询出订单商品
    $sql = "
		SELECT g.rec_id,g.goods_number,g.goods_name,g.goods_price,g.parent_id
		 FROM ecshop.ecs_order_goods AS g 
				
		WHERE g.order_id = '{$order['order_id']}'
	";
    $goods_list = $db->getAll($sql);

    if (substr(strtolower($order['order_sn']),-3) == '-gh' ) {
        $goods_list[0]['goods_number'] = count($goods_list);
    }
    $orders[$key]['goods_list'] = $goods_list;
    
    //售后的订单增加淘宝订单号
    $order_type = array("RMA_RETURN","RMA_EXCHANGE","SHIP_ONLY");
    if(in_array($orders[$key]['order_type_id'], $order_type)){
    	$sqlt = "select oi.taobao_order_sn  
                   from ecshop.order_relation r 
                   inner join ecshop.ecs_order_info oi 
                   		on r.root_order_id = oi.order_id 
                  where r.order_id = %d ";
    	$order_taobao_sn = $db->getOne(sprintf($sqlt, intval($orders[$key]['order_id'])));
    	$orders[$key]['taobao_order_sn'] = $order_taobao_sn;
    }
    // 订单状态
    $orders[$key]['order_status_name'] = get_order_status($order['order_status']);
    $orders[$key]['shipping_status_name'] = get_shipping_status($order['shipping_status']);
    $orders[$key]['pay_status_name'] = get_pay_status($order['pay_status']);

    // 标示订单的实收是否等于应收
    if ($orders[$key]['order_amount'] != $orders[$key]['real_paid'] && $order['order_status'] == 1) {
        $orders[$key]['red_notice'] = 1;
    } else {
        $orders[$key]['red_notice'] = 0;
    }

    // 取得物品总数
    $sql = "SELECT count(*) AS total_goods_count FROM {$ecs->table('order_goods')} g WHERE g.order_id = '{$order['order_id']}'";
    $total_goods_count = $db->getOne($sql);
    $orders[$key]['total_goods_count'] = substr(strtolower($order['order_sn']),-3) == '-gh' ? count($goods_list) :  $total_goods_count;

    //zlh
    if (!empty($order_sns_voucher)) {
        $orders[$key]['accumulated_paid'] = $accumulated_paids[$orders[$key]['order_sn']];
        $orders[$key]['voucher_paid'] = $batch_order_sn_real_paid[$orders[$key]['order_sn']];
    }
    
    // 从支付交易中取得订单的收款时间 
    try {
        $handle = paytrans_get_soap_client();
        $response = $handle->getPaymentTransactionByOrderId($order['order_id']);
        $times = array();
        if ($response->total > 0) {
            $paytrans = wrap_object_to_array($response->resultList->PaymentTransaction);
            foreach ($paytrans as $trans) {
                if ($trans->status == 'USED') {
                    $times[] = (float)strtotime($trans->usedStamp);
                }
            }
            $pay_time = !empty($times) ? max($times) : 0 ;
            $orders[$key]['pay_time'] = $pay_time > 0 ? $pay_time : $order['pay_time'] ;
        }
    } catch (SoapFault $e) {
    }
}

$smarty->assign('back', $_SERVER['REQUEST_URI']);
$smarty->assign('orders', $orders);
$smarty->assign('pager', $pager);
$smarty->assign('payments', getPayments());
$smarty->assign('shippingTypes', getShippingTypes());
$smarty->assign('all_pay_status', $all_pay_status);
$smarty->assign('all_order_status', $_CFG['adminvars']['order_status']);
if ($csv) {
    admin_priv('4cw_finance_manage_order_csv');
    header("Content-type:application/vnd.ms-excel");
    header("Content-Disposition:filename=" . iconv("UTF-8","GB18030","财务报表") . ".csv");
    $out = $smarty->fetch('oukooext/finance_csv.htm');
    echo iconv("UTF-8","GB18030", $out);
    exit();
} else {
    $smarty->display('oukooext/financeV2.htm');
}


//输入淘宝订单号判断是否有换货单
function batch_taobao_h() {
	global $ecs;
	global $db;
	global $flag;
	$flag = 0;
	
	$batch_taobao_order_sn = trim($_REQUEST['batch_taobao_order_sn']);
	if ($batch_taobao_order_sn != '') {
        $taobao_order_sns = preg_split('/[\s]+/', $batch_taobao_order_sn);
        foreach ($taobao_order_sns as $key => $taobao_order_sn) {
            if (trim($taobao_order_sn) == '') {
                unset($taobao_order_sns[$key]);
            }
        }
        $condition = db_create_in($taobao_order_sns, "taobao_order_sn");
    }
	
	
	$sql = "SELECT r.order_id FROM ecs_order_info o 
		LEFT JOIN order_relation r ON o.order_id = r.root_order_id
		WHERE ".$condition." AND ".party_sql('o.party_id');
	$order_id = $db->getAll($sql);
	foreach ($order_id as $orders_id){
		$p_id .= $orders_id['order_id']; 
	}
	$order_h =$order_h1= array();
	if ( $p_id != ''){
		foreach ($order_id as $k => $order_ids){
			$id = $order_ids['order_id'];
			if($id != ''){
                // ECB 团灭 20151203 邪恶的大鲵
				// $sqlt = "SELECT order_id, order_sn, bill_no, order_time, order_status, order_amount, 
				//     goods_amount, shipping_fee, shipping_status, shipping_name, 
				//     integral_money, bonus, consignee, pay_id, pay_status, real_paid, 
			 // 	    real_shipping_fee, proxy_amount, pay_method, is_finance_clear, 
			 //  	    pack_fee, order_type_id, additional_amount, info.currency, taobao_order_sn 
			 //  	    FROM {$ecs->table('order_info')} AS info 
	   //   	 	    LEFT JOIN {$ecs->table('carrier_bill')} cb ON cb.bill_id = carrier_bill_id 
	   //    	        WHERE info.order_id = $id";
                $sqlt="SELECT
                        info.order_id,
                        info.order_sn,
                        s.tracking_number bill_no,
                        info.order_time,
                        info.order_status,
                        info.order_amount,
                        info.goods_amount,
                        info.shipping_fee,
                        info.shipping_status,
                        info.shipping_name,
                        info.integral_money,
                        info.bonus,
                        info.consignee,
                        info.pay_id,
                        info.pay_status,
                        info.real_paid,
                        info.real_shipping_fee,
                        info.proxy_amount,
                        info.pay_method,
                        info.is_finance_clear,
                        info.pack_fee,
                        info.order_type_id,
                        info.additional_amount,
                        info.currency,
                        info.taobao_order_sn
                    FROM
                        ecshop.ecs_order_info AS info
                    LEFT JOIN romeo.order_shipment os ON os.order_id = CONVERT (info.order_id USING utf8)
                    LEFT JOIN romeo.shipment s ON os.shipment_id = s.shipment_id
                    WHERE
                        info.order_id = $id
                    AND (s. STATUS is null or s. STATUS != 'SHIPMENT_CANCELLED')
                    GROUP BY
                        info.order_id
                ";
			    $order = $db->getAll($sqlt);
				foreach ($order as $order_list){
					if($order_list['order_type_id'] == 'RMA_EXCHANGE'){
						$order_h1[] = array_merge($order_h,$order_list);
						$flag = $flag+1;
					}
				}
			}
		}									
	}	
	return $order_h1;
}

//淘宝订单号的模糊搜索功能
function db_create_tb_in($item,$field_name='')
{
	global $ecs;
	global $db;
	$item=trim($item);
	$item_list_tb="'$item'";
    $sql="select taobao_order_sn from {$ecs->table('order_info')} where taobao_order_sn LIKE '{$item}%'";
	$item_list=$db->getAll($sql);
	if(!empty($item_list))
	{
		foreach($item_list as $item1)
		{
			
			if($item1!='')
			{
				$item2=$item1['taobao_order_sn'];
				$item_list_tb.=$item_list_tb?",'$item2'":"'$item2'";
			}
		}
	}
	return $field_name.' IN('.$item_list_tb.')';
}


function getCondition() {
    global $ecs;

    $order_status = $_REQUEST['order_status'];
    $shipping_id = $_REQUEST['shipping_id'];
    $shipping_status = $_REQUEST['shipping_status'];
    $order_type = $_REQUEST['order_type'];
    $pay_id = $_REQUEST['pay_id'];
    $pay_status = $_REQUEST['pay_status'];
    $is_finance_paid = $_REQUEST['is_finance_paid'];
    $red_notice = $_REQUEST['red_notice'];
    $start = $_REQUEST['start'];
    $end = $_REQUEST['end'];
    $search_text = trim($_REQUEST['search_text']);
    $batch_order_sn = trim($_REQUEST['batch_order_sn']);
    $batch_bill_no = trim($_REQUEST['batch_bill_no']);
    $batch_taobao_order_sn = trim($_REQUEST['batch_taobao_order_sn']);
    $currency = trim($_REQUEST['currency']);

    $act = $_REQUEST['act'];

    $condition = "";
    $order_condition = "";

    if ($order_status != null && $order_status != -1) {
        $order_condition .= " AND order_status = '$order_status'";
    }

    if ($pay_id != null && $pay_id != -1) {
        $order_condition .= " AND pay_id = '$pay_id'";
    }
    if ($pay_status != null && $pay_status != -1) {
        $order_condition .= " AND pay_status = '$pay_status'";
    }
    if ($is_finance_paid == 'YES') {
        $condition .= " AND is_finance_paid = '$is_finance_paid'";
    }
    if ($shipping_id != null && $shipping_id != -1) {
        $order_condition .= " AND shipping_id = '$shipping_id'";
    }
    if ($shipping_status != null && $shipping_status != -1) {
        $order_condition .= " AND shipping_status = '$shipping_status'";
    }
    if ($currency) {
        $order_condition .= " AND currency = '{$currency}' ";
    }
    
    if ($red_notice != null && $red_notice != -1) {
        switch ($red_notice) {
            case 1:
                $order_condition .= " AND order_amount != real_paid AND order_status = 1";
                break;
            case 2:
                $order_condition .= " AND order_amount != real_paid 
                                      AND order_type_id = 'RMA_RETURN' ";
                break;
            case 3:
                $order_condition .= " AND pay_id = 1 AND carrier_id = 5 AND order_time < DATE_ADD(now(), INTERVAL -35 DAY) AND pay_status = 0";
                break;
            case 4:
                $order_condition .= " AND pay_id = 1 AND shipping_id in (19, 23, 24) AND order_time < DATE_ADD(now(), INTERVAL -7 DAY) AND pay_status = 0";
                break;
            case 5:
                $order_condition .= " AND pay_id = 5 AND order_time < DATE_ADD(now(), INTERVAL -10 DAY) AND pay_status = 0";
                break;
            case 6:
                $order_condition .= " AND pay_id in (4, 8, 9) AND order_time < DATE_ADD(now(), INTERVAL -3 DAY) AND pay_status = 0";
                break;
            case 7:
                $order_condition .= " AND pay_id in (2, 3) AND order_time < DATE_ADD(now(), INTERVAL -3 DAY) AND pay_status = 0";
                break;
            case 8:
                $condition .= " AND is_finance_paid = 'YES' AND purchase_invoice = ''";
                break;
            case 9:
                $order_condition .= "  AND shipping_status in (1,2,6) ";
                $condition .= " AND (shipping_invoice = '' OR shipping_invoice IS NULL) ";
                break;
            case 10:
                $order_condition .= " AND pay_status = '2' AND (proxy_amount = 0 OR proxy_amount IS NULL)";
                break;
            case 11:
                $order_condition .= " AND order_status = 1
				                      AND shipping_status in (1, 2, 6) 
				                      AND pay_status != 2 
				                      AND order_type_id = 'SALE' ";
                break;
        }
    }

    if (strtotime($start) > 0) {
        $order_condition .= " AND order_time >= '$start'";
    }else{
    	$default_time = date('Y-m-d', strtotime("-3 month", time()));
    	$order_condition .= " AND order_time >= '{$default_time}'";
    }
    if (strtotime($end) > 0) {
        $end = strftime('%Y-%m-%d', strtotime("+1 day", strtotime($end)));
        $order_condition .= " AND order_time <= '$end'";
    }
    if ($search_text != '') {
        $search_type = trim($_REQUEST['search_type']);
        switch ($search_type) {    
            case "order_amount":
                $order_condition .= " AND info.order_amount = '{$search_text}' ";
                break;
            case "customer":
                $order_condition .= " AND (info.consignee LIKE '{$search_text}%' 
                                           OR info.inv_payee LIKE '{$search_text}%') ";
                break;
            case "shipping_invoice":
                $condition .= " AND e.shipping_invoice LIKE '{$search_text}%' ";
                break;
            case "goods_name":
                $condition .= " AND g.goods_name LIKE '%{$search_text}%' ";
                break;
        }
        //$condition .= parseSearchText($search_text);
    }

    if ($condition == '' && $act != 'search') {
        $order_condition = " AND order_status = 1";
    }

    if ($batch_order_sn != '') {
        $order_sns = preg_split('/[\s]+/', $batch_order_sn);
        foreach ($order_sns as $key => $order_sn) {
            if (trim($order_sn) == '') {
                unset($order_sns[$key]);
            }
        }
        $order_condition .= " AND " . db_create_in($order_sns, "order_sn");
    }
    if ($batch_bill_no != '') {
        $bill_nos = preg_split('/[\s]+/', $batch_bill_no);
        foreach ($bill_nos as $key => $bill_no) {
            if (trim($bill_no) == '') {
                unset($bill_no[$key]);
            }
        }
//        $order_condition .= " AND " . db_create_in($bill_nos, "bill_no");
        
        global $db;
        // ECB团灭 20151203 邪恶的大鲵
    //     $bill_sql="select order_sn
				// FROM `ecshop`.`ecs_carrier_bill` cb
				// INNER JOIN `ecshop`.`ecs_order_info` AS o ON cb.bill_id = o.carrier_bill_id 
				// WHERE " . db_create_in($bill_nos, "bill_no");
        $bill_sql="SELECT
                info.order_sn
            FROM
                romeo.shipment s
            INNER JOIN romeo.order_shipment os ON s.SHIPMENT_ID = os.SHIPMENT_ID
            INNER JOIN ecshop.ecs_order_info info ON cast(os.order_id AS UNSIGNED) = info.order_id
            WHERE " . db_create_in($bill_nos, "s.TRACKING_NUMBER");
		$order_sns=$db->getCol($bill_sql);
//		$orderSns = implode(", ", $order_sns);
		$order_condition .=" AND " . db_create_in($order_sns, "order_sn");
    }
    if ($batch_taobao_order_sn != '') {
    	$count=0;
        $taobao_order_sns = preg_split('/[\s]+/', $batch_taobao_order_sn);
        foreach ($taobao_order_sns as $key => $taobao_order_sn) {
            if (trim($taobao_order_sn) == '') {
                unset($taobao_order_sns[$key]);
            }
            $count++;
        }
        if($count>1){//如果批量输入订单号，则执行精确搜索
        	$order_condition .= " AND " . db_create_in($taobao_order_sns, "taobao_order_sn");
        }else{//否则经行模糊搜索
        	$taobao_order_sn=$taobao_order_sns[0];
            $order_condition .= " AND " . db_create_tb_in($taobao_order_sn, "taobao_order_sn");
        }
    }
    
    # 添加party条件判断 2009/08/06 yxiang
    $order_condition .= ' AND '.party_sql('info.party_id');
    
    # 添加订单类型的判断 hyzhou1
    $order_condition .= " AND info.order_type_id in ('SALE', 'RMA_RETURN', 'RMA_EXCHANGE')";

    $final_condition = 	$order_condition ;

    if (!empty($condition)) { // 如果非空的话，再去erp表去连接查询
        $final_condition .= "
	    AND EXISTS (
			SELECT 1 FROM {$ecs->table('order_goods')} AS g 
			LEFT JOIN `romeo`.`purchase_order_info` AS poi 
					ON g.order_id = poi.order_id 
			left join romeo.order_shipping_invoice osi
					ON g.order_id = osi.order_id 
			WHERE g.order_id = info.order_id {$condition}
		)
	    ";
    }
    return $final_condition;
}

?>