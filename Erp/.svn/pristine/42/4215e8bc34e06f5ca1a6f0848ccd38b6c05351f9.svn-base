<?php
/**
 * 内部结账
 */
define('IN_ECS', true);

require_once('includes/init.php');
require_once('includes/debug/lib_log.php');
require_once('includes/cls_json.php');
admin_priv('cw_c2c_buy_sale', 'cg_c2c_buy_sale');

include_once(ROOT_PATH . "RomeoApi/lib_inventory.php");
include_once(ROOT_PATH . "RomeoApi/lib_payment.php");
include_once(ROOT_PATH . 'RomeoApi/lib_currency.php');
require_once("function.php");

$csv = $_REQUEST['csv'];
$sum_purchase_paid_amount = $_REQUEST['sum_purchase_paid_amount'];  // 付款总金额

$size = 10;
$page = $_REQUEST['page'] ? $_REQUEST['page'] : 1;
$start = ($page - 1) * $size;
$back = $_REQUEST['back'] !== null ? $_REQUEST['back'] : "/";

if ($csv == null) {
    $limit = "LIMIT $size";
    $offset = "OFFSET $start";
} else {
    admin_priv('4cw_c2c_buy_sale_csv');
}

$is_purchase_not_paid = false;
$smarty->assign('is_purchase_not_paid', $is_purchase_not_paid);
if ($_REQUEST['act'] != 'search') {
    // 如果没有搜索条件，那么只显示模板
    $smarty->display('oukooext/c2c_buy_sale.htm');
    exit();
}

$order_ids = _getOrderIds();
$order_goods_ids = _getOrderGoodsIds();
if(empty($order_ids) && empty($order_goods_ids)){
	$message = '请输入订单号或者订单商品号';
	$smarty->assign('message',$message);
    $smarty->display('oukooext/c2c_buy_sale.htm');
    exit();
}
$provider_id = $_REQUEST['provider_id'];
if(empty($provider_id)){
	$message = '请选择供应商';
	$smarty->assign('message',$message);
    $smarty->display('oukooext/c2c_buy_sale.htm');
    exit();
}

if(!empty($order_goods_ids)){
	//order_goods_id in -c
	$sql = "select distinct og.rec_id 
			from ecshop.ecs_order_info o 
				inner join ecshop.ecs_order_goods og on o.order_id = og.order_id 
				inner join romeo.inventory_item_detail iid on convert(og.rec_id using utf8) = iid.order_goods_id 
			where ".db_create_in($order_goods_ids, "og.rec_id") ."
			 AND iid.QUANTITY_ON_HAND_DIFF > 0 
			 AND order_type_id IN ('PURCHASE')
			";
	//Qlog::log($sql);
	$order_goods_ids_c = $db->getCol($sql);	
		
	//order_goods_id out -gt 
	$sql = "select distinct og.rec_id  
			FROM ecshop.ecs_order_info o
		 	 	inner join ecshop.ecs_order_goods og on og.order_id = o.order_id
		 	 	inner join romeo.inventory_item_detail iid on iid.order_goods_id = convert(og.rec_id using utf8)
		 	where ".db_create_in($order_goods_ids, "og.rec_id")."
				AND iid.QUANTITY_ON_HAND_DIFF < 0
				AND order_type_id IN ('SUPPLIER_RETURN', 'SUPPLIER_SALE')
			";
	//Qlog::log($sql);
	$order_goods_ids_gt = $db->getCol($sql);
	
	if(count($order_goods_ids) != count($order_goods_ids_c) + count($order_goods_ids_gt)){
	    $message = '请检查订单号是否出库';
		$smarty->assign('message',$message);
	    $smarty->display('oukooext/c2c_buy_sale.htm');
	    exit();
	}
	
	//检查供应商
	if(!empty($order_goods_ids_gt)){
		$sql = "select distinct iid.order_goods_id from romeo.inventory_item_detail iid 
				inner join romeo.inventory_item ii on iid.inventory_item_id = ii.inventory_item_id 
				 where ii.provider_id = '{$provider_id}' and "
				.db_create_in($order_goods_ids_gt, "iid.order_goods_id");
		//Qlog::log($sql);
		$order_goods_ids_gt_provider = $db->getCol($sql);
		if(count($order_goods_ids_gt_provider) != count($order_goods_ids_gt)){
			$diff_ids_gt = array_diff($order_goods_ids_gt, $order_goods_ids_gt_provider);
			$message = '-gt订单供应商不一致,不一致的订单商品号:'.implode(',', $diff_ids_gt);
			$smarty->assign('message',$message);
		    $smarty->display('oukooext/c2c_buy_sale.htm');
		    exit();
		}
	}
	if(!empty($order_goods_ids_c)){
		$sql = "select distinct iid.order_goods_id from romeo.inventory_item_detail iid 
				    inner join romeo.inventory_item ii on iid.inventory_item_id = ii.inventory_item_id 
					where ii.provider_id = '{$provider_id}' and "
					.db_create_in($order_goods_ids_c, "iid.order_goods_id");
		//Qlog::log($sql);
		$order_goods_ids_c_provider = $db->getCol($sql);
		if(count($order_goods_ids_c_provider) != count($order_goods_ids_c)){
			$diff_ids_c = array_diff($order_goods_ids_c, $order_goods_ids_c_provider);
			$message = '-c订单供应商不一致,不一致的订单商品号:'.implode(',', $diff_ids_c);
			$smarty->assign('message',$message);
		    $smarty->display('oukooext/c2c_buy_sale.htm');
		    exit();
		}
	}
	
	_setPurchaseGoodsNotPaidStatus($order_goods_ids);
}else {
	//in   -c
	$sql = " select distinct o.order_id  
				FROM ecshop.ecs_order_info o
			 	 	inner join ecshop.ecs_order_goods og 
			 	 			on og.order_id = o.order_id
			 	 	inner join romeo.inventory_item_detail iid 
			 	 			on iid.order_goods_id = convert(og.rec_id using utf8)
			 	where ".db_create_in($order_ids, "o.order_id")."
				AND iid.QUANTITY_ON_HAND_DIFF > 0 
				AND order_type_id IN ('PURCHASE')
			";
	$order_ids_c = $db->getCol($sql);
	//out  -gt
	$sql = "select distinct o.order_id  
				FROM ecshop.ecs_order_info o
			 	 	inner join ecshop.ecs_order_goods og 
			 	 			on og.order_id = o.order_id
			 	 	inner join romeo.inventory_item_detail iid 
			 	 			on iid.order_goods_id = convert(og.rec_id using utf8)
			 	where ".db_create_in($order_ids, "o.order_id")."
					AND iid.QUANTITY_ON_HAND_DIFF < 0
					AND order_type_id IN ('SUPPLIER_RETURN', 'SUPPLIER_SALE')
	";
	$order_ids_gt = $db->getCol($sql);
	if(count($order_ids) != count($order_ids_c) + count($order_ids_gt)){
	    $message = '请检查订单号是否出库';
		$smarty->assign('message',$message);
	    $smarty->display('oukooext/c2c_buy_sale.htm');
	    exit();
	}
	
	//检查供应商
	if(!empty($order_ids_gt)){
		$sql = "select distinct iid.order_id from romeo.inventory_item_detail iid 
				inner join romeo.inventory_item ii on iid.inventory_item_id = ii.inventory_item_id 
				 where ii.provider_id = '{$provider_id}' and "
				.db_create_in($order_ids_gt, "iid.order_id");
		$order_ids_gt_provider = $db->getCol($sql);
		if(count($order_ids_gt_provider) != count($order_ids_gt)){
			$message = '-gt订单供应商不一致';
			$smarty->assign('message',$message);
		    $smarty->display('oukooext/c2c_buy_sale.htm');
		    exit();
		}
	}
	if(!empty($order_ids_c)){
		$sql = "select distinct iid.order_id from romeo.inventory_item_detail iid 
				    inner join romeo.inventory_item ii on iid.inventory_item_id = ii.inventory_item_id 
					where ii.provider_id = '{$provider_id}' and "
					.db_create_in($order_ids_c, "iid.order_id");
		$order_ids_c_provider = $db->getCol($sql);
		if(count($order_ids_c_provider) != count($order_ids_c)){
			$message = '-c订单供应商不一致';
			$smarty->assign('message',$message);
		    $smarty->display('oukooext/c2c_buy_sale.htm');
		    exit();
		}
	}
	
	_setPurchaseNotPaidStatus($order_ids);
}

// 查询该供应商的可用预付款金额
$max_prepayment_amount = prepay_get_available_amount($provider_id, $_SESSION['party_id'], 'SUPPLIER');
$currency = prepay_get_currency($provider_id, $_SESSION['party_id'], 'SUPPLIER');
$currency_list = get_currencys();
$smarty->assign('max_prepayment_amount', $max_prepayment_amount);
$smarty->assign('currency', $currency['currency']);
$smarty->assign('currency_list', $currency_list);

if(!empty($order_goods_ids)){
	$add_ids_c = db_create_in($order_goods_ids_c, "og.rec_id");
	$add_ids_gt = db_create_in($order_goods_ids_gt, "og.rec_id");
	
	$on_c = " inner join romeo.purchase_order_info poi on poi.order_goods_id = og.rec_id 
			   left join ecshop.order_bill_mapping obm on obm.order_goods_id = og.rec_id";
	$on_gt = " inner join  ecshop.supplier_return_order_info sroi on sroi.order_goods_id = og.rec_id
			  left join ecshop.order_bill_mapping obm on obm.order_goods_id = og.rec_id ";
}else {
	$add_ids_c = db_create_in($order_ids_c, "o.order_id");
	$add_ids_gt = db_create_in($order_ids_gt, "o.order_id");
	
	$on_c = " inner join romeo.purchase_order_info poi on poi.order_id = o.order_id 
			   left join ecshop.order_bill_mapping obm on obm.order_id = o.order_id";
	$on_gt = " inner join  ecshop.supplier_return_order_info sroi on sroi.order_id = o.order_id
			  left join ecshop.order_bill_mapping obm on obm.order_id = o.order_id ";
}

$sumSQL = "
	SELECT sum(a.purchase_paid_amount) AS purchase_paid_amount, sum(a.goods_price) AS goods_price FROM (
		SELECT SUM(t.unit_cost ) purchase_paid_amount, SUM(t.goods_price) goods_price
		from(
			select ii.unit_cost * iid.quantity_on_hand_diff as unit_cost,og.goods_price * iid.quantity_on_hand_diff as goods_price
		 	 FROM ecshop.ecs_order_info o
		 	 	inner join ecshop.ecs_order_goods og 
		 	 			on og.order_id = o.order_id
		 	 	inner join romeo.inventory_item_detail iid 
		 	 			on iid.order_goods_id = convert(og.rec_id using utf8)
		 	 	inner join romeo.inventory_item ii
		 	 			on iid.inventory_item_id = ii.inventory_item_id
			WHERE 
			".$add_ids_c."
			group by iid.inventory_item_detail_id 
		)t
	UNION ALL
		select sum(return_amount),sum(t.goods_price)
		from(
			SELECT ii.unit_cost * iid.quantity_on_hand_diff as return_amount ,og.goods_price  * iid.quantity_on_hand_diff as goods_price
			FROM ecshop.ecs_order_info o
		 	 	inner join ecshop.ecs_order_goods og 
		 	 			on og.order_id = o.order_id
		 	 	inner join romeo.inventory_item_detail iid 
		 	 			on iid.order_goods_id = convert(og.rec_id using utf8)
		 	 	inner join romeo.inventory_item ii
		 	 			on iid.inventory_item_id = ii.inventory_item_id
				WHERE ".$add_ids_gt."
				group by iid.inventory_item_detail_id
		)t
	) a 
";
//Qlog::log($sumSQL);
$sum = $db->getRow($sumSQL);

if ($_POST['submit'] == '采购批量已付款') {
	//Qlog::log("采购批量已付款");
    admin_priv('cg_c2c_buy_sale'); //  只有有采购权限的人
    
    $purchase_paid_time = isset($_REQUEST['purchase_paid_time']) && strtotime($_REQUEST['purchase_paid_time']) > 0
        ? $_REQUEST['purchase_paid_time']
        : date('Y-m-d H:i:s') ;  // 付款时间
    $action_time = date("Y-m-d H:i:s");  // 操作时间
    
    $order_sn = isset($_REQUEST['batch_order_sn'])?trim($_REQUEST['batch_order_sn']):false;
    $order_goods_id = isset($_REQUEST['batch_order_goods_id'])?($_REQUEST['batch_order_goods_id']):false;
    
    // 判断搜索出来的供价之和是不是等于现在想要去做update的时候的供价之和，如果不是，中间肯定是有人修改了
    if ($sum['purchase_paid_amount'] != $sum_purchase_paid_amount && (!in_array($order_sn,array('0595029503-gt','2178383743-gt','6869745019-gt','4053761507-gt','0631021051-gt','5565283765-gt')) || !in_array($order_goods_id, array('1982278','2852375','1982279','1982285','4326886','1982282')))) {
        echo ("<script type='text/javascript'>alert('已经有人修改过供价了，请重新刷新页面.{$sum['purchase_paid_amount']},{$sum_purchase_paid_amount}')</script>");
        die();
    }

    
    // 处理使用预付款部分
    $prepayment_amount = is_numeric($_REQUEST['prepayment_amount']) ? floatval($_REQUEST['prepayment_amount']) : 0;
    // 检查供应商账户是否有足够的预付款
    if ($prepayment_amount > 0) {
        if (!party_explicit($_SESSION['party_id'])) {
           sys_msg('要使用预付款操作人员的业务形态必须确定', 1); 
        }
        
        $amount = prepay_get_available_amount($_REQUEST['provider_id'], $_SESSION['party_id'], 'SUPPLIER');
        if ($amount === false) {
            sys_msg('该供应商不存在预付款帐户', 1);
        }
        
        if ($amount < $prepayment_amount) {
            sys_msg('供应商预付款帐户余额不足', 1);
        }
        
        $prepayment_flag = true;
    }
    
    // 使用总金额 > 要支付的总金额
    if ($sum['purchase_paid_amount'] > 0) {
        if ($prepayment_amount > $sum['purchase_paid_amount']) {
            sys_msg('使用金额大于要支付金额了', 1);
        }   
    } else {
        if ($prepayment_amount > 0) {
            sys_msg('不能使用预付款', 1);
        }
    }
    
    if(!empty($order_goods_ids)){
		$bill_ids_c = db_create_in($order_goods_ids_c, "order_goods_id");
		$bill_ids_gt = db_create_in($order_goods_ids_gt, "order_goods_id");
	}else {
		$bill_ids_c = db_create_in($order_ids_c, "order_id");
		$bill_ids_gt = db_create_in($order_ids_gt, "order_id");
	}
    
    $sql = "select 1 from ecshop.order_bill_mapping 
    			WHERE ".$bill_ids_gt;
	$is_already_buy = $db->getOne($sql);	
	if($is_already_buy){
		sys_msg('已经内部结账，注意不要重复', 1);
	}
	$sql = "select 1 from  ecshop.order_bill_mapping 
    			WHERE ".$bill_ids_c;
	$is_already_buy = $db->getOne($sql);	
	if($is_already_buy){
		sys_msg('已经内部结账，注意不要重复', 1);
	}
    
    //插入一条记录到 ecs_purchase_bill
    $order_type = $_REQUEST['order_type'];
    $total_amount = $sum['purchase_paid_amount'] - $prepayment_amount;
    $return_amount = 0.0;
    if ($order_type == 'dx') {
        $return_amount = $sum['goods_price'] - $sum['purchase_paid_amount'];
    }
    $sql = "
		INSERT INTO {$ecs->table('purchase_bill')} 
		(date, amount, return_amount, prepayment_amount, user, type, status, paid_type,party_id,currency) VALUES 
		(NOW(), '{$total_amount}', '{$return_amount}', '{$prepayment_amount}', '{$_SESSION['admin_name']}', '{$_REQUEST['order_type']}', 'P_PAID', '{$_REQUEST['paid_type']}','{$_SESSION['party_id']}','{$_REQUEST['prepayment_currency']}')
	";
    $db->query($sql);
    $purchase_bill_id = $db->insert_id();
    $sqls[] = $sql;
    
	if(!empty($order_goods_ids)){
		$order_goods = array();
	    $order_goods_bills = array();
		foreach ( $order_goods_ids_c as $order_goods_id ) {
       		$order_goods[] = $order_goods_id;
       		$order_id = $db->getOne("select order_id from ecshop.ecs_order_goods where rec_id = ".$order_goods_id." LIMIT 1");
			$order_goods_bills[] = "({$purchase_bill_id}, {$order_id}, {$order_goods_id})";
		}	
		
		$tmp = join(",", $order_goods);	
		if($tmp != ''){
			$sql = "UPDATE romeo.purchase_order_info
				SET is_purchase_paid = 'YES',
					purchase_paid_time = '$purchase_paid_time',
					purchase_paid_type = '$paid_type'
				WHERE 
					order_goods_id in ({$tmp}) 
			";
	        $db->query($sql);
	        $sqls[] = $sql;
		}
		
		$order_goods = array();
		foreach ( $order_goods_ids_gt as $order_goods_id ) {
       		$order_goods[] = $order_goods_id;
       		$order_id = $db->getOne("select order_id from ecshop.ecs_order_goods where rec_id = ".$order_goods_id." LIMIT 1");
			$order_goods_bills[] = "({$purchase_bill_id}, {$order_id}, {$order_goods_id})";
		}
		
		$tmp = join(",", $order_goods);
		if($tmp != ''){
			$sql = "UPDATE ecshop.supplier_return_order_info
				SET is_purchase_paid = 'YES',
					purchase_paid_time = '$purchase_paid_time',
					purchase_paid_type = '$paid_type'
				WHERE 
					order_goods_id in ({$tmp}) 
			";
	        $db->query($sql);
	        $sqls[] = $sql;
		}
		
		$bill_tmp = join(", ", $order_goods_bills);
	    if ($bill_tmp != '') {
	        $sql = "INSERT INTO ecshop.order_bill_mapping (purchase_bill_id, order_id, order_goods_id) values $bill_tmp";
	        $db->query($sql);
	        $sqls[] = $sql;
	    }
		
	}else {
		$orders = array();
	    $order_bills = array();
	    foreach ($order_ids_c as $order_id) {
	        $orders[] = $order_id;
	        $rec_ids = $db->getCol("select rec_id from ecshop.ecs_order_goods where order_id = ".$order_id);
	        foreach ( $rec_ids as $rec_id ) {
       			$order_bills[] = "({$purchase_bill_id},{$order_id},{$rec_id})";
			}
	    }
	    $tmp = join(",", $orders);
	
	    if ($tmp != '') {
	        $sql = "UPDATE romeo.purchase_order_info
				SET is_purchase_paid = 'YES',
					purchase_paid_time = '$purchase_paid_time',
					purchase_paid_type = '$paid_type'
				WHERE 
					order_id in ({$tmp}) 
			";
	        $db->query($sql);
	        $sqls[] = $sql;
	    }
	    
	    $orders = array();
	    foreach ($order_ids_gt as $order_id) {
	        $orders[] = $order_id;
	        $rec_ids = $db->getCol("select rec_id from ecshop.ecs_order_goods where order_id = ".$order_id);
	        foreach ( $rec_ids as $rec_id ) {
       			$order_bills[] = "({$purchase_bill_id},{$order_id},{$rec_id})";
			}
	    }
	    $tmp = join(", ", $orders);
	    if ($tmp != '') {
	        $sql = "UPDATE ecshop.supplier_return_order_info
				SET is_purchase_paid = 'YES',
					purchase_paid_time = '$purchase_paid_time',
					purchase_paid_type = '$paid_type'
				WHERE 
					order_id in ({$tmp}) 
			";
	        $db->query($sql);
	        $sqls[] = $sql;
	    }
	    
	    $bill_tmp = join(", ", $order_bills);
	    if ($bill_tmp != '') {
	        $sql = "INSERT INTO ecshop.order_bill_mapping (purchase_bill_id, order_id, order_goods_id) values $bill_tmp";
	        $db->query($sql);
	        $sqls[] = $sql;
	    }
	}
    
    
    // 使用预付款
    if ($prepayment_flag) {
        $code = prepay_consume(
            $_REQUEST['provider_id'], // 供应商
            $_SESSION['party_id'],    // 组织ID
            'SUPPLIER',
            $prepayment_amount,       // 使用金额 
            $purchase_bill_id,        // 付款批次号
            $_SESSION['admin_name'],  // 操作人 
			"内部结账采购批量付款",    // 备注 
			'',
			0,
			$_REQUEST['prepayment_currency'] //币种
        );
        
        switch ($code) {
            case -1 :
                sys_msg('该供应商没有预付款账号，不能使用预付款', 1);
                break;
            case 0 :
                sys_msg('使用预付款失败', 1);
                break;
        }
    }
    
    //Qlog::log("采购批量已付款成功");
}

// -c待付款订单和-gt待退款订单合并在一起
//-c的所有inventory_item中provider_id 一致
//-gt的所有inventory_item中provider_id 可能不一致，使用supplier_return_order_info中的provider_id
$sql = "
	select * from 
		((SELECT ii.provider_id AS provider_id, 
			   p.provider_name AS provider_name,
			   iid.CREATED_STAMP AS in_time,
			   poi.purchase_paid_time AS purchase_paid_time,
			   og.rec_id, 
			   og.goods_name AS goods_name,
			   o.order_id AS order_id,
			   o.order_sn AS order_sn,
			   o.currency as currency,
			   sum(ii.unit_cost*iid.quantity_on_hand_diff) AS purchase_paid_amount,
			   sum(og.goods_price*iid.quantity_on_hand_diff) AS goods_price,
			   ii.INVENTORY_ITEM_ACCT_TYPE_ID  AS order_type,
			   poi.is_finance_paid AS is_finance_paid,
			   poi.is_purchase_paid AS is_purchase_paid,
			   poi.purchaser AS purchaser,
			   IF(b.bill_id = 0, NULL, b.date) AS finance_paid_time,
			   IF(b.bill_id = 0, NULL, b.bill_id) AS finance_bill_id,
			   o.order_time, func_get_goods_category_name(g.top_cat_id, g.cat_id) as category,
			   o.order_type_id  as order_type_id
		  FROM ecshop.ecs_order_info o
			   inner join ecshop.ecs_order_goods og 
					on og.order_id = o.order_id
			   inner join ecshop.ecs_goods g
					on g.goods_id = og.goods_id
			   inner join romeo.inventory_item_detail iid
					on cast(og.rec_id as char(20)) = iid.order_goods_id
			   inner join romeo.inventory_item ii
					on iid.inventory_item_id = ii.inventory_item_id
			   inner join ecshop.ecs_provider p 
					on ii.provider_id = p.provider_id
			   ".$on_c." 
		 	   left join ecshop.ecs_oukoo_inside_c2c_bill b 
		 	    		on obm.oukoo_inside_c2c_bill_id = b.bill_id 

		  WHERE 
			".$add_ids_c."
			group by og.rec_id
			order by o.order_time DESC $limit)
		UNION ALL
		 (SELECT p.provider_id AS provider_id, 
			   p.provider_name AS provider_name,
			   iid.CREATED_STAMP AS in_time,
			   sroi.purchase_paid_time AS purchase_paid_time,
			   og.rec_id, 
			   og.goods_name AS goods_name,
			   o.order_id AS order_id,
			   o.order_sn AS order_sn,
			   o.currency as currency,
			   sum(ii.unit_cost*iid.quantity_on_hand_diff) AS purchase_paid_amount,
			   sum(og.goods_price*iid.quantity_on_hand_diff) AS goods_price,
			   ii.INVENTORY_ITEM_ACCT_TYPE_ID AS order_type,
			   sroi.is_finance_paid AS is_finance_paid,
			   sroi.is_purchase_paid AS is_purchase_paid,
			   sroi.purchaser AS purchaser,
			   IF(b.bill_id = 0, NULL, b.date) AS finance_paid_time,
			   IF(b.bill_id = 0, NULL, b.bill_id) AS finance_bill_id,
			   o.order_time, func_get_goods_category_name(g.top_cat_id, g.cat_id) as category,
			   o.order_type_id as order_type_id
		 FROM 
			  ecshop.ecs_order_info o
			  inner join ecshop.ecs_order_goods og
					on og.order_id = o.order_id
			  inner join romeo.inventory_item_detail iid
					on cast(og.rec_id as char(20)) = iid.order_goods_id
			  inner join romeo.inventory_item ii
					on iid.inventory_item_id = ii.INVENTORY_ITEM_ID 
			  inner join ecshop.ecs_provider p
					on ii.provider_id = p.provider_id
			  inner join ecshop.ecs_goods g
					on g.goods_id = og.goods_id
			  ".$on_gt."
		 	  left join ecshop.ecs_oukoo_inside_c2c_bill b 
		 	    		on obm.oukoo_inside_c2c_bill_id = b.bill_id

		  WHERE 
			".$add_ids_gt."
			group by og.rec_id
			order by o.order_time DESC $limit)
		) a
	ORDER BY a.order_time DESC
	$limit $offset";

$sqlc = "
	SELECT COUNT(*) FROM 
		(SELECT distinct og.rec_id FROM 
			ecshop.ecs_order_info o inner join ecshop.ecs_order_goods og on o.order_id = og.order_id 
			WHERE 
				".$add_ids_c."
		UNION ALL
		SELECT distinct og.rec_id  FROM 
				ecshop.ecs_order_info o inner join ecshop.ecs_order_goods og on o.order_id = og.order_id 
			 WHERE 
				".$add_ids_gt."
	    ) a
";
//Qlog::log($sql);
$goods_list = $db->getAll($sql);
$count = $db->getOne($sqlc);
$pager = Pager($count, $size, $page);

//*****  应财务要求，对某些订单采取特殊处理
$batch_order_sn = trim($_REQUEST['batch_order_sn']);
$batch_order_goods_id = trim($_REQUEST['batch_order_goods_id']);
if(in_array($batch_order_sn,array('0595029503-gt','2178383743-gt','6869745019-gt','4053761507-gt')) || in_array($batch_order_goods_id, array('1982278','1982279','1982285','1982282'))){
	$sum['purchase_paid_amount'] = -190.00;
}

$smarty->assign('provider_select', trim($_REQUEST['provider_select']));
$smarty->assign('provider_id', trim($_REQUEST['provider_id']));
$smarty->assign('order_type', trim($_REQUEST['order_type']));
$smarty->assign('back', remove_param_in_url($_SERVER['REQUEST_URI'], 'info'));
$smarty->assign('goods_list', $goods_list);
$smarty->assign('pager', $pager);
$smarty->assign('sum', $sum);

if ($csv) {
    admin_priv('4cw_c2c_buy_sale_csv');
    header("Content-type:application/vnd.ms-excel");
    header("Content-Disposition:filename=" . iconv("UTF-8", "GB18030", "{$order_type}内部对帐表") . ".csv");
    $out = $smarty->fetch('oukooext/c2c_buy_sale_csv.htm');
    echo iconv("UTF-8", "GB18030", $out);
    exit();
} else {
    $smarty->display('oukooext/c2c_buy_sale.htm');
}
function _getOrderIds(){
	global $db;
	$batch_order_sn = trim($_REQUEST['batch_order_sn']);
	$order_ids = null;
    if ($batch_order_sn != '') {
		$order_sns = preg_split('/[\s]+/', $batch_order_sn);
	
	    $sql = "select order_id from ecshop.ecs_order_info  where "
	    			.db_create_in($order_sns, "order_sn")
	    				.' AND ' . party_sql('party_id');
	    $order_ids = $db->getCol($sql);
		if(count($order_sns) != count($order_ids)){
			die("订单号有误".$batch_order_sn);
		}
    }
    return $order_ids;
}
function _getOrderGoodsIds(){
	global $db;
	$batch_order_goods_id = trim($_REQUEST['batch_order_goods_id']);
	$order_goods_ids = null;
	if($batch_order_goods_id != ''){
		$$batch_order_goods_ids = preg_split('/[\s]+/', $batch_order_goods_id);
		$sql = "select og.rec_id from ecshop.ecs_order_info o inner join ecshop.ecs_order_goods og on o.order_id = og.order_id where "
				.db_create_in($$batch_order_goods_ids, "og.rec_id") . ' AND ' . party_sql('o.party_id');
		$order_goods_ids = $db->getCol($sql);
		if(count($$batch_order_goods_ids) != count($order_goods_ids)){
			die("订单商品号有误".$batch_order_goods_id);
		}
	}
	return $order_goods_ids;
}
function _setPurchaseNotPaidStatus($order_ids) {
    global $smarty;
    global $db;
	$is_purchase_not_paid = true;
	$sql = "select * from  romeo.purchase_order_info where 
				is_purchase_paid = 'YES' and "
				.db_create_in($order_ids, "order_id");
	$is_purchase_paid_order = $db->getOne($sql);

	if(!empty($is_purchase_paid_order)){
		$is_purchase_not_paid = false;
	}
	$sql = "select * from  ecshop.supplier_return_order_info where 
				is_purchase_paid = 'YES' and "
				.db_create_in($order_ids, "order_id");
	$is_purchase_paid_order = $db->getOne($sql);
	if(!empty($is_purchase_paid_order)){
		$is_purchase_not_paid = false;
	}
    $smarty->assign('is_purchase_not_paid', $is_purchase_not_paid);
}

function _setPurchaseGoodsNotPaidStatus($order_goods_ids) {
    global $smarty;
    global $db;
	$is_purchase_not_paid = true;
	$sql = "select * from  romeo.purchase_order_info where 
				is_purchase_paid = 'YES' and "
				.db_create_in($order_goods_ids, "order_goods_id");
	$is_purchase_paid_order_goods = $db->getOne($sql);

	if(!empty($is_purchase_paid_order_goods)){
		$is_purchase_not_paid = false;
	}
	$sql = "select * from  ecshop.supplier_return_order_info where 
				is_purchase_paid = 'YES' and "
				.db_create_in($order_goods_ids, "order_goods_id");
	$is_purchase_paid_order_goods = $db->getOne($sql);
	if(!empty($is_purchase_paid_order_goods)){
		$is_purchase_not_paid = false;
	}
    $smarty->assign('is_purchase_not_paid', $is_purchase_not_paid);
}

?>