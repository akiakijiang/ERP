<?php

/**
 * 财务对账
 */
 
define('IN_ECS', true);
require('includes/init.php');
admin_priv('finance_order');
require_once('function.php');
require_once(ROOT_PATH . 'RomeoApi/lib_payment.php');

$submit = $_REQUEST['submit'];

/* 取得快递公司结算的数据库 */
$finance_sf_db = new cls_mysql($finance_sf_db_host, 
							   $finance_sf_db_user, 
							   $finance_sf_db_pass, 
							   $finance_sf_db_name);
/* C2CPlus数据库 */
/*$finance_c2cplus_db = new cls_mysql($finance_c2cplus_db_host, 
									$finance_c2cplus_db_user, 
									$finance_c2cplus_db_pass, 
									$finance_c2cplus_db_name);
*/
$no_order_bill_no_list = array();

if ($submit == '开始对账') {$seq = $_GET["seq"];
	$sql = "select shipping_id, type, bill_time, times from job_schedule where seq = '{$seq}'";
	$job = $finance_sf_db->getRow($sql);
	if ($job !== null) {
		
		if ($job["type"] == 1) {//邮费、代收货款合在一起
		  $sql = "SELECT bill_no, proxy_amount, order_amount, shipping_fee 
		  		  FROM ouku_order_amount 
		  		  WHERE bill_time = '{$job["bill_time"]}' 
		  		  	AND times = '{$job["times"]}'
		  		  	AND shipping_id = '{$job["shipping_id"]}'";
		  $recs = $finance_sf_db->getAll($sql);

		  foreach($recs AS $key=>$rec) {
		  	
		  	// 更新ouku
		  	
		  	// 代收金额为0，如果我们系统的物流状态不是拒收退回，列出来
		  	if ($rec["order_amount"] <= 0) {
		  		$sql = "select count(*) from ecs_order_info a, ecs_carrier_bill b
		  			where b.bill_no = '{$rec["bill_no"]}'
		  			 and b.bill_id  = a.carrier_bill_id and shipping_status = 3";
		  		$count = $db->getAll($sql);
		  		if ($count == 0) {
		  			$order_amount_zero[] = $rec["bill_no"];
		  		}
		  	}
		  	
		  	// 无代收货款的，不应该有手续费
		  	if ($rec["order_amount"] <= 0 && $rec["proxy_amount"] > 0){
		  		$no_order_has_proxy[] = $rec["bill_no"];
		  		continue;
		  	}
		  	
		  	// 一个快递单号出现在多个订单里
		  	$sql = "select count(*) from ecs_order_info a, ecs_carrier_bill b
		  			where b.bill_no = '{$rec["bill_no"]}'
		  			 and b.bill_id  = a.carrier_bill_id";
		  	$count = $db->getOne($sql);
		  	if ($count > 1) {
		  		$bill_no_more[] = $rec["bill_no"];
		  		continue;
		  	} 
		  	
		  	// 先找该快递是欧酷发？
		  	$sql = "select a.order_id, a.order_status, a.invoice_status, a.shipping_status, a.pay_status, a.pay_id, a.user_id, a.real_paid, c.name as carrier_name
		  			from ecs_order_info a, ecs_carrier_bill b, ecs_carrier c
		  			where a.carrier_bill_id = b.bill_id and c.carrier_id = b.carrier_id and b.bill_no = '{$rec["bill_no"]}'";
		  	// $db->query($sql);
		  	$ouku_rec = $db->getRow($sql);
		  	if ($ouku_rec && $ouku_rec["order_id"] != null) {
		  		if ($rec["order_amount"] > 0) { 
		  			$received_amount = (float)$rec['order_amount'] - (float)$ouku_rec['real_paid'];
	  				if (_paytrans_create_and_use($ouku_rec, $received_amount, "{$ouku_rec['carrier_name']}账单导入"))
	  				{
					  	$sql = "update ecs_order_info a, ecs_carrier_bill b
					  			set a.real_shipping_fee  = '{$rec['shipping_fee']}',
					  				a.real_paid = '{$rec['order_amount']}', 
					  				a.proxy_amount = '{$rec['proxy_amount']}',
					  				a.pay_status = 2,
					  				a.pay_time = UNIX_TIMESTAMP(NOW())
					  			where b.bill_no = '{$rec['bill_no']}'
					  			  and b.bill_id  = a.carrier_bill_id
					  			  and a.real_paid = 0 and a.is_finance_clear != 1";
					  	$db->query($sql);
					  	if ($db->affected_rows() == 1) {
						  	$sql = "insert into ecs_order_action(order_id, action_user, order_status, shipping_status, 
						  			pay_status, action_time, action_note, invoice_status) 
						  			values({$ouku_rec["order_id"]}, '{$_SESSION['admin_name']}', {$ouku_rec["order_status"]}, {$ouku_rec["shipping_status"]}, 
						  					2, NOW(), '{$job["bill_time"]} Bill', {$ouku_rec["invoice_status"]})";
						  	$db->query($sql);
						  	// update order mixed status 
	                        // include_once('includes/lib_order_mixed_status.php');
	                        // update_order_mixed_status($ouku_rec['order_id'], array('pay_status' => 'paid'), 'worker');
					  	}
	  				}
			  	} else { // 代收货款金额为0，不需要修改订单的状态
			  		if ($rec['shipping_fee'] != 0) {
				  		$sql = "update ecs_order_info a, ecs_carrier_bill b
					  			set a.real_shipping_fee  = '{$rec['shipping_fee']}'
					  			where b.bill_no = '{$rec['bill_no']}'
					  			  and b.bill_id  = a.carrier_bill_id
					  			  AND a.real_shipping_fee = 0";
					  	$db->query($sql);
				  	}
				  	if ($rec['real_paid'] != 0) {
				  		$received_amount = (float)$rec['real_paid'] - (float)$ouku_rec['real_paid'];  // 收到的款项
				  		if (_paytrans_create_and_use($ouku_rec, $received_amount, "{$ouku_rec['carrier_name']}账单导入"))
				  		{
						  	$sql = "update ecs_order_info a, ecs_carrier_bill b
						  			set a.real_paid = '{$rec['real_paid']}'
						  			where b.bill_no = '{$rec['bill_no']}'
						  			  and b.bill_id  = a.carrier_bill_id
						  			  AND a.real_paid = 0 and a.is_finance_clear != 1";
						  	$db->query($sql);	
				  		}
				  	}
			  	}
			  	
		  	}
		  	else{ 
		  	    $no_order_bill_no_list[] = $rec["bill_no"];
		  	    //查找C2CPlus是否有数据？
			  	/*$sql = "update order_info 
			  			set real_shipping_fee = '{$rec["shipping_fee"]}',
			  				real_order_amount = '{$rec["order_amount"]}',
			  				proxy_amount 	  = '{$rec["proxy_amount"]}',
			  				is_ouku_in		  = 'YES' 
			  		 	where bill_no	=	'{$rec["bill_no"]}' and is_ouku_in!='YES'";
			  	$finance_c2cplus_db->query($sql);
			  	$count = $finance_c2cplus_db->affected_rows();
			  	if ($count == 0) {
			  		$combined_not_found_list[] = $rec["bill_no"];
			  	}*/
		  	}
		  }
		} else if ($job["type"] == 0) { //邮费、代收货款分开
			// 处理代收货款费用
			$sql = "select bill_no, proxy_amount, order_amount 
					from order_amount 
		  		    where bill_time = '{$job["bill_time"]}' 
		  		      AND times = '{$job["times"]}'
		  		      AND shipping_id = '{$job["shipping_id"]}'";
		  	$recs = $finance_sf_db->getAll($sql);
		  	foreach($recs AS $key=>$rec) {
			  	// 更新ouku
			  	// 代收金额为0，如果我们的系统物流状态不是拒收退回，列出来
			  	if ($rec["order_amount"] <= 0) {
			  		$sql = "select count(*) from ecs_order_info a, ecs_carrier_bill b
			  			where b.bill_no = '{$rec["bill_no"]}'
			  			 and b.bill_id  = a.carrier_bill_id and shipping_status = 3";
			  		$count = $db->getAll($sql);
			  		if ($count == 0) {
			  			$order_amount_zero[] = $rec["bill_no"];
			  		}
			  	}
			  	
			  	// 无代收货款的，不应该有手续费
			  	if ($rec["order_amount"] <= 0 && $rec["proxy_amount"] > 0){
			  		$no_order_has_proxy[] = $rec["bill_no"];
			  		continue;
			  	}
			  	
			  	// 判断一个快递单号出现在多个订单里，如果是就不更新，需要手工做
			  	$sql = "select count(*) from ecs_order_info a, ecs_carrier_bill b
			  			where b.bill_no = '{$rec["bill_no"]}'
			  			 and b.bill_id  = a.carrier_bill_id";
			  	$count = $db->getOne($sql);
			  	if ($count > 1) { 
			  		$bill_no_more[] = $rec["bill_no"];
			  		continue;
			  	} 
			  	
			  	// 先找该快递是欧酷发？
			  	$sql = "select a.order_id, a.order_status, a.invoice_status, a.shipping_status, a.pay_status, a.pay_id, a.user_id, a.real_paid, c.name as carrier_name
			  			from ecs_order_info a, ecs_carrier_bill b, ecs_carrier c
			  			where a.carrier_bill_id = b.bill_id and c.carrier_id = b.carrier_id and b.bill_no = '{$rec["bill_no"]}'";
			  	// $db->query($sql);
			  	$ouku_rec = $db->getRow($sql);
			  	if ($ouku_rec && $ouku_rec["order_id"] != null) {
			  		if ($rec["order_amount"] > 0) { //代收货款金额<0，不更新订单
			  			$received_amount = (float)$rec['order_amount'] - (float)$ouku_rec['real_paid'];  // 收到的款项
			  			if (_paytrans_create_and_use($ouku_rec, $received_amount, "{$ouku_rec['carrier_name']}账单导入"))
	  					{
					  		$sql = "update ecs_order_info a, ecs_carrier_bill b
						  			set a.real_paid = '{$rec["order_amount"]}', 
						  				proxy_amount = '{$rec["proxy_amount"]}',
						  				a.pay_status = 2,
						  				a.pay_time = UNIX_TIMESTAMP(NOW()) 
						  			where b.bill_no = '{$rec["bill_no"]}'
						  			 and b.bill_id  = a.carrier_bill_id
						  			 and a.real_paid = 0 and a.is_finance_clear != 1";
						  	$db->query($sql);
						  	if ($db->affected_rows() == 1) {
							  	$sql = "insert into ecs_order_action(order_id, action_user, order_status, shipping_status, 
							  			pay_status, action_time, action_note, invoice_status) 
							  			values({$ouku_rec["order_id"]}, '{$_SESSION['admin_name']}', {$ouku_rec["order_status"]}, {$ouku_rec["shipping_status"]}, 
							  					2, NOW(), '{$job["bill_time"]} Bill', {$ouku_rec["invoice_status"]})";
							  	$db->query($sql);
						  	}	
  						}
				  	}
				  	
			  	}
			  	else{ 
			  	    $no_order_bill_no_list[] = $rec["bill_no"];
			  	    //查找C2CPlus是否有数据？
			  		/*$sql = "select * from order_info 
				  		 	where bill_no	=	'{$rec["bill_no"]}' and is_ouku_in!='YES'";
				  	$tmp = $finance_c2cplus_db->getRow($sql);
				  	if ($tmp != null) {
					  	$sql = "update order_info 
					  			set real_order_amount = '{$rec["order_amount"]}',
					  				proxy_amount 	  = '{$rec["proxy_amount"]}',
					  				is_ouku_in		  = 'YES' 
					  		 	where bill_no	=	'{$rec["bill_no"]}' and is_ouku_in!='YES'";
					  	$finance_c2cplus_db->query($sql);
				  	} else {
				  		$combined_not_found_list[] = $rec["bill_no"];
				  	}*/
			  	}
		  	}
		  	// 处理邮费
		  	$sp_not_found_fee_list_sum = 0.0;
			$sql = "SELECT bill_no, fee 
					FROM real_shipping_fee 
		  		    WHERE bill_time = '{$job["bill_time"]}' 
		  		      AND times = '{$job["times"]}'
		  		      AND shipping_id = '{$job["shipping_id"]}'";
		  	$recs = $finance_sf_db->getAll($sql);
		  	foreach($recs AS $key=>$rec) {
		  		// 判断一个快递单号出现在多个订单里，如果是就不更新，需要手工做
			  	$sql = "select count(*) from ecs_order_info a, ecs_carrier_bill b
			  			where b.bill_no = '{$rec["bill_no"]}'
			  			 and b.bill_id  = a.carrier_bill_id";
			  	$count = $db->getOne($sql);
			  	if ($count > 1) { 
			  		$bill_no_more[] = $rec["bill_no"];
			  		continue;
			  	} 
			  	
			  	// 更新ouku
			  	$sql = "select a.* from ecs_order_info a, ecs_carrier_bill b
			  			where b.bill_no = '{$rec["bill_no"]}'
			  			 and b.bill_id  = a.carrier_bill_id";
			  	$tmp = $db->getRow($sql);
			  	if ($tmp != null) {
				  	$sql = "update ecs_order_info a, ecs_carrier_bill b
				  			set a.real_shipping_fee = '{$rec["fee"]}'
				  			where b.bill_no = '{$rec["bill_no"]}'
				  			 and b.bill_id  = a.carrier_bill_id";
				  	$db->query($sql);
			  	} else {
			  	    $no_order_bill_no_list[] = $rec["bill_no"];
			  	    // 不去c2cplus中查找了
			  	    $sp_not_found_fee_list[] = $rec;
				  	$sp_not_found_fee_list_sum += $rec["fee"];
			  		/*$sql = "select * from order_info 
				  		 	where bill_no	=	'{$rec["bill_no"]}'";
				  	$tmp = $finance_c2cplus_db->getRow($sql);
				  	if ($tmp != null) {
					  	$sql = "update order_info 
					  			set real_shipping_fee = '{$rec["fee"]}'
					  		 	where bill_no	=	'{$rec["bill_no"]}'";
					  	$finance_c2cplus_db->query($sql);
				  	} else {
				  		$sp_not_found_fee_list[] = $rec;
					  	$sp_not_found_fee_list_sum += $rec["fee"];
				  	}*/
			  	}
		  	}
		}
	}
	
	Header("Location: $back"); 
}


$smarty->assign('back', $_SERVER['REQUEST_URI']);
// 代收货款金额 在系统中无法找到部分 c2cplus 。 已经无用了
$smarty->assign('combined_not_found_list', $combined_not_found_list); 
// 代收货款金额:以下部分在我们系统里找不到
$smarty->assign('sp_not_found_order_amount_list', $sp_not_found_order_amount_list);
// 快递费用:以下部分在我们系统里找不到 $sp_not_found_fee_list_sum 是总计
$smarty->assign('sp_not_found_fee_list_sum', $sp_not_found_fee_list_sum);
$smarty->assign('sp_not_found_fee_list', $sp_not_found_fee_list); // 

// 以下订单在快递公司账单代收货款金额是0，但在我们系统里不是拒收订单
$smarty->assign('order_amount_zero', $order_amount_zero);

// 一个快递单号出现在几个订单里
$smarty->assign('bill_no_more', $bill_no_more); 

// 没有订单的bill_no
$smarty->assign('no_order_bill_no_list', $no_order_bill_no_list); 

// 该订单代收货款金额是0，但手续费却大于0
$smarty->assign('no_order_has_proxy', $no_order_has_proxy);


$smarty->assign('pager', $pager);
$smarty->display('oukooext/finance_dshk_update.htm');


/**
 * 通过订单号来创建并使用一条支付交易
 * 
 * @param array $order 订单信息
 * @param float $received_amount 收到的款
 * @param string $note 备注
 * @param string $from 付款来源
 * @param stromg $to 付款去向
 * 
 * @return boolean
 */
function _paytrans_create_and_use($order, $received_amount, $note)
{	
    if ((float)$received_amount <= 0) 
    {
        return false;  // 当金额<=0时，不创建支付交易 
    }

    // 创建一条支付交易
    if (!$transId = paytrans_create_by_order($order, $received_amount, $note))
    {
        return false;  // 创建失败了
    }
    
    // 使用该条支付交易   
    try
    {
        $handle = paytrans_get_soap_client();
        $amount = $handle->usePaymentTransaction($transId);
        return true;
    }
    catch (SoapFault $e)
    {
        trigger_error("SOAP使用交易支付失败: (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})", E_USER_ERROR);
        return false;	
    }
}

?>