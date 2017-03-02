<?php
/**
 * 直销待开票清单
 */
define('IN_ECS', true);
require('includes/init.php');
require('function.php');
admin_priv('cw_invoice_main', 'cg_no_shipping_invoice', 'direct_pending_invoice_list'); // 权限名为 发票相关 

$csv = $_REQUEST['csv'];                           
if ($csv) {
	admin_priv('4cw_invoice_main_csv');
}

// 取得直销商
$distributor_list = (array)$slave_db->getCol("
    SELECT d.distributor_id FROM ecshop.distributor d LEFT JOIN main_distributor m ON m.main_distributor_id = d.main_distributor_id WHERE m.type = 'zhixiao' 
    and m.party_id = {$_SESSION ['party_id']}
    ");
array_push($distributor_list, 0);

$condition = get_condition();
$sql = "
    select o.order_id, o.order_sn, o.order_time, o.consignee, o.inv_payee, o.shipping_time,
	    o.order_amount, o.shipping_fee, o.pack_fee, o.bonus, o.postscript,
	    o.shipping_name, o.pay_name, o.distributor_id, o.province,
	    o.zipcode, o.tel, o.mobile, o.address, o.taobao_order_sn, o.handle_time,osi.shipping_invoice
	from ecshop.ecs_order_info o
	left join romeo.order_shipping_invoice osi on o.order_id = osi.order_id
    WHERE 
        o.order_type_id = 'SALE' AND
        ". db_create_in($distributor_list, 'o.distributor_id') ." AND -- 直销的订单
        ". party_sql('o.party_id') ."
        {$condition}
    ORDER BY o.order_id ASC 
";
$orders = $slave_db->getAllRefby($sql, array('order_id'), $order_ids, $_orders);

require_once("distribution.inc.php");

// 获得淘宝积分
$sql = "select order_id, attr_value from order_attribute 
        where attr_name = 'TAOBAO_POINT_FEE' and ".db_create_in($order_ids['order_id'], 'order_id');
$_order_attribures = $slave_db->getAll($sql);
$taobao_point_fee_array = array();
if (!empty($_order_attribures)) {
    foreach ($_order_attribures as $attribute) {
        $taobao_point_fee_array[$attribute['order_id']] = 
            -1 * abs(floatval($attribute['attr_value'] / 100));
    }
}

// 获得分销商
$_distributors = distribution_get_distributor_list();
$distributors = array();
foreach ($_distributors as $distributor) {
    $distributors[$distributor['distributor_id']] = $distributor;
}

// 获得省份
$sql = "select region_id, region_name from ecs_region where region_type = 1 and parent_id = 1 ";
$_provinces = $slave_db->getAll($sql);
$provinces = array();
foreach ($_provinces as $province) {
    $provinces[$province['region_id']] = $province;
}



// 获得淘宝积分
$sql = "select og.order_id,og.goods_id, og.goods_name, og.goods_price, 
    		og.goods_number,og.goods_price*og.goods_number as goods_total_amount, g.added_fee
		from ecshop.ecs_order_goods og inner join ecshop.ecs_goods g on og.goods_id = g.goods_id
        where ".db_create_in($order_ids['order_id'], 'order_id');
$temp = $slave_db->getAllRefby($sql, array('order_id'), $temp_order_ids, $_order_goods);

foreach($orders as $key => $order){
	$orders[$key]['distributor_name'] = $distributors[$order['distributor_id']]['name'];
    $orders[$key]['province_name'] = $provinces[$order['province']]['region_name'];
    $orders[$key]['taobao_point_fee'] = $taobao_point_fee_array[$order['order_id']]
            ? $taobao_point_fee_array[$order['order_id']]
            : 0;
    $orders[$key]['invoice_amount'] = $order['order_amount'] + $orders[$key]['taobao_point_fee'];
    
    empty($order['inv_payee']) && $orders[$key]['inv_payee'] = $order['consignee'];
    
    $order_goods = $_order_goods['order_id'][$order['order_id']];
    $orders[$key]['order_goods'] = $order_goods;
    $orders[$key]['order_goods_num'] = count($order_goods);
    
    
    $orders[$key]['now_date'] = date('Ymd-', time()) .'-' .($key+1);
}

//优惠券分摊到各个商品中，修改金额
foreach($orders as $key => $order){
    $goods_total = $order['order_amount'] - $order['shipping_fee'] - $order['pack_fee']; //含优惠券总价
    $goods_totals = $order['order_amount'] - $order['shipping_fee'] - $order['pack_fee'] + abs($order['bonus']); //去优惠券总价
    if($goods_totals == 0){
        $ratio = 0;
    }else{
        $ratio = $goods_total / $goods_totals;
    }
    $orders[$key]['ratio'] = $ratio;
    $order_goods_num = $orders[$key]['order_goods_num'];
    for($i=0; $i<$order_goods_num; $i++){
        $orders[$key]['order_goods'][$i]['goods_total_amount'] = $orders[$key]['order_goods'][$i]['goods_total_amount']*$ratio;
    }    
}

//获得系统可以使用的出库单号
$sql = "select value from ecs_shop_config where code = 'no_shipping_invoice_begin_no'";
$begin_no = $slave_db->getOne($sql,true);
if (!$begin_no) { $begin_no =1; }


// 取得每个订单的发出库打印时间, 默认是订单的发货时间
if ($orders) {
	$sql = "SELECT * FROM order_attribute WHERE attr_name = 'SHIPPING_INVOICE_PRINT_TIME' AND order_id " . db_create_in($order_ids['order_id']);
	$resource = $slave_db->query($sql);
	if ($resource) {
		$attrs = array();
		while ($row = $slave_db->fetchRow($resource)) {
			$attrs[$row['order_id']] = $row['attr_value'];
		}
	}
	
	foreach ($orders as $key => $order) {
		$orders[$key]['shipping_time'] = 
            isset($attrs[$order['order_id']])
            ? $attrs[$order['order_id']]
            : ($orders[$key]['shipping_time'] > 0 ? date('Y-m-d', $orders[$key]['shipping_time']) : NULL );
//        pp($orders[$key]['shipping_time']);
	}
	
	//导出新表时，对 （金额） 进行比例加成 ，使（金额总和） = （新增应收金额）
	//order_goods_num 每订单商品数；  goods_total_amount 每商品价格； invoice_amount 新增应收金额
	if($csv == '2') {
		foreach ($orders as $key => $order) {
			$order_goods_num = $orders[$key]['order_goods_num'];
			$invoice_amount = $orders[$key]['invoice_amount'];
			$total_price = 0;   //商品总价
			$price_notnull_num = 0;  //商品单价不为0；
			$diff_price = $invoice_amount;	
			
			for($i=0; $i<$order_goods_num; $i++) {
				$goods_total_amount = $orders[$key]['order_goods'][$i]['goods_total_amount'];	
				$total_price += $goods_total_amount;				
				if($goods_total_amount != 0) {
					$price_notnull_num ++;
				}							
			}
		
			for($i=0; $i<$order_goods_num; $i++) {						
				$goods_total_amount = $orders[$key]['order_goods'][$i]['goods_total_amount'];				
				if(($price_notnull_num>1) && ($goods_total_amount>0)) {
					$price_notnull_num --;
					$goods_total_amount = round($goods_total_amount / $total_price * $invoice_amount, 2);
					$diff_price  = $diff_price - $goods_total_amount;
					$orders[$key]['order_goods'][$i]['goods_total_amount'] = $goods_total_amount;
					 continue;
				} 	
				if(($price_notnull_num == 1) && ($goods_total_amount>0)) {
					$goods_total_amount = $diff_price;
					$orders[$key]['order_goods'][$i]['goods_total_amount'] = $goods_total_amount;	
					$price_notnull_num --;
					continue;
				}			
			}
			
			//所有商品总价=0，但开票金额！=0 
			if($total_price == 0) {
				for($i=0; $i<$order_goods_num; $i++) {
					$orders[$key]['order_goods'][$i]['goods_total_amount'] = $invoice_amount;
				}							
			}
		}
		$orders2 = $orders;
		$smarty->assign('orders2', $orders2);		
	}
}

$smarty->assign('orders', $orders);
$smarty->assign('print_begin_no', $begin_no);
$smarty->assign('total', count($orders));
$smarty->assign('carriers', getCarriers());
$smarty->assign('back', $_SERVER['REQUEST_URI']);
$invoiced_array = array('no' => '未开票', 'yes' => '已开票', 'ALL' => '全部');
$smarty->assign('invoiced_array', $invoiced_array);

$shipping_status_list = array('-1' => '发货前状态') + $_CFG['adminvars']['shipping_status'];
unset($shipping_status_list[0],$shipping_status_list[4],$shipping_status_list[5],$shipping_status_list[6],$shipping_status_list[7]);
$smarty->assign('all_shipping_status', $shipping_status_list);
$smarty->assign('order_status_list', $_CFG['adminvars']['order_status']);

if ($csv == '1') {
	header("Content-type:application/vnd.ms-excel");
	header("Content-Disposition:filename=" . iconv("UTF-8","GB18030","B2C(2月1日后)欠缺销售发票列表") . ".csv");	
	$out = $smarty->fetch('oukooext/no_shipping_invoice_csv.htm');
	echo iconv("UTF-8","GB18030", $out);
	exit();		
} else if($csv == '2') {
	header("Content-type:application/vnd.ms-excel");
	header("Content-Disposition:filename=" . iconv("UTF-8","GB18030","B2C(2月1日后)欠缺销售发票列表(新)") . ".csv");	
	$out = $smarty->fetch('oukooext/no_shipping_invoice_csv2.htm');
	echo iconv("UTF-8","GB18030", $out);
	exit();		
}else {
	$smarty->assign('type', 'B2C(2月1日后)');
	$smarty->display('oukooext/no_shipping_invoice.htm');
}

function get_condition() {
	$condition = "";
	
	$act = $_REQUEST['act'];
	$start_time = $_REQUEST['start_time'];
	$end_time = $_REQUEST['end_time'];
	$keyword = trim($_REQUEST['keyword']);
	$shipping_status = ($_REQUEST['shipping_status'] != NULL) ? intval($_REQUEST['shipping_status']) : -1;
	$order_status = ($_REQUEST['order_status'] != NULL) ? intval($_REQUEST['order_status']) : -1; 
	$invoiced = $_REQUEST['invoiced'];
	$invoice_nos = $_REQUEST['invoice_nos'];

	// 开始时间默认为当天
	if (strtotime($start_time) == 0) {
	    $start_time = date('Y-m-d');
	    $_REQUEST['start_time'] = $start_time;
	}
	if (strtotime($start_time) > 0) {
	    $condition .= " AND o.order_time >= '{$start_time}' ";
	}

	// 结束时间默认为当天
	if (strtotime($end_time) == 0) {
	    $end_time = date('Y-m-d');
	    $_REQUEST['end_time'] = $end_time;
	}
	if (strtotime($end_time) > 0) {
	    $end_time = strftime('%Y-%m-%d', strtotime("+1 day", strtotime($end_time)));
	    $condition .= " AND o.order_time <= '{$end_time}' ";
	}
	
	if(is_null($_REQUEST['shipping_status']) || $shipping_status == -2) {
	} else if (($shipping_status != -1)) {
	    $condition .= " AND o.shipping_status = {$shipping_status} ";
	} else {
	    $condition .= " AND o.shipping_status in (0, 8, 9, 10) ";
	} 
	
	if ($order_status != '-1') {
		$condition .= " AND o.order_status = '{$order_status}' ";
	}

	if (!empty($keyword)) {
		$condition .= " AND ".db_create_in($keyword, "o.order_sn");
	}
	
	if ($invoiced == 'yes') {
	    $condition .= " AND osi.shipping_invoice != '' ";
	}else if($invoiced == 'no'){
		$condition .= " AND (osi.shipping_invoice is null or osi.shipping_invoice = '') ";
	}

	if (trim($invoice_nos)) {
	    $invoices = explode("\r\n", $invoice_nos);
	    $sql_invoices = db_create_in($invoices, "osi.shipping_invoice");
	    $condition .= " AND {$sql_invoices} ";
	}
	return $condition;
}
