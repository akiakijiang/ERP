<?php
/**
 * 采购付款记录
 */
define('IN_ECS', true);

require('includes/init.php');
admin_priv('cw_c2c_buy_sale', 'cg_c2c_buy_sale');
require("function.php");

$act = $_REQUEST['act'];
$purchase_bill_id = $_REQUEST['p_bill_id'];

$csv = $_REQUEST['csv'];
$size = 10;
$page = $_REQUEST['page'] ? $_REQUEST['page'] : 1;
$start = ($page - 1) * $size;
$partyid=$_SESSION['party_id'];

if ($csv == null) {
    $limit = "LIMIT $size";
    $offset = "OFFSET $start";
} else {
	admin_priv("admin_other_csv");
}

$order_sn = $_REQUEST['order_sn'];
$provider = $_REQUEST['provider'];  
// var_dump($_REQUEST);
if (empty($order_sn)) {
    $condition = getCondition();
    $sql_provider = "SELECT ii.provider_id as real_provider_id,b.*,(b.amount+b.rebate_amount+b.prepayment_amount) AS purchase_amount
                    FROM ecshop.ecs_purchase_bill b
                    LEFT JOIN ecshop.order_bill_mapping obm ON obm.purchase_bill_id = b.purchase_bill_id
                    LEFT JOIN ecshop.ecs_order_info oi ON oi.order_id = obm.order_id 
                    LEFT JOIN romeo.purchase_order_info poi ON obm.order_goods_id = poi.order_goods_id 
                    LEFT JOIN ecshop.ecs_order_goods og on obm.order_goods_id = og.rec_id 
                    LEFT JOIN romeo.inventory_item_detail iid ON iid.ORDER_GOODS_ID = convert(og.rec_id using utf8) 
                    LEFT JOIN romeo.inventory_item ii ON iid.INVENTORY_ITEM_ID = ii.INVENTORY_ITEM_ID
                    WHERE b.`status`!='NEW' AND b.party_id = '{$partyid}' $condition
                    GROUP BY b.purchase_bill_id
                    ORDER BY b.date DESC
                    $limit $offset
                    ;";
    $sqlc = "
        SELECT COUNT(DISTINCT(b.purchase_bill_id))
        FROM ecshop.ecs_purchase_bill b
        LEFT JOIN ecshop.order_bill_mapping obm ON obm.purchase_bill_id = b.purchase_bill_id
        LEFT JOIN ecshop.ecs_order_info oi ON oi.order_id = obm.order_id 
        LEFT JOIN romeo.purchase_order_info poi ON obm.order_goods_id = poi.order_goods_id 
        LEFT JOIN ecshop.ecs_order_goods og on obm.order_goods_id = og.rec_id
        LEFT JOIN romeo.inventory_item_detail iid ON iid.ORDER_GOODS_ID = convert(og.rec_id using utf8) 
        LEFT JOIN romeo.inventory_item ii ON iid.INVENTORY_ITEM_ID = ii.INVENTORY_ITEM_ID
        WHERE b.`status`!='NEW' AND b.party_id = '65553' $condition
    ";
} else {
    $sql = "
        SELECT pb.*, (pb.amount+pb.rebate_amount+pb.prepayment_amount) AS purchase_amount 
        FROM
        ecshop.ecs_purchase_bill pb 
        left join ecshop.order_bill_mapping obm 
		 	    		on pb.purchase_bill_id = obm.purchase_bill_id
        left join ecshop.ecs_order_info o on o.order_id = obm.order_id
        WHERE
        o.order_sn like '$order_sn'
        $limit $offset
    ";
    $sqlc = "
        SELECT count(*)
        FROM
        ecshop.ecs_purchase_bill pb 
        left join ecshop.order_bill_mapping obm 
		 	    		on pb.purchase_bill_id = obm.purchase_bill_id
        left join ecshop.ecs_order_info o on o.order_id = obm.order_id
        WHERE
        o.order_sn like '$order_sn'
    ";
}
// print($sql_provider);
//$bills_with_provider = $db->getAll($sql_provider);
// var_dump($bills_with_provider[0]);
$bills = $db->getAll($sql_provider);
$count = $db->getOne($sqlc);
$pager = Pager($count, $size, $page);

//zlh
foreach ($bills as $key => $entry) {
    if ($entry['voucher_date']!='0000-00-00 00:00:00'){
        $bills[$key]['voucher_year'] = date('Y', strtotime($entry['voucher_date']));
        $bills[$key]['voucher_month'] = date('m', strtotime($entry['voucher_date']));
    } else {
        $bills[$key]['voucher_year'] = date('Y', strtotime($entry['date']));
        $bills[$key]['voucher_month'] = date('m', strtotime($entry['date']));
    }
}
// var_dump($bills[0]);
$smarty->assign('back', $_SERVER['REQUEST_URI']);
$smarty->assign('bills', $bills);
$smarty->assign('pager', $pager);
$smarty->assign('sum', $sum);
$smarty->assign('next_year', ( (int)date('Y', time()) + 1));

if ($csv) {
    header("Content-type:application/vnd.ms-excel");
    header("Content-Disposition:filename=" . iconv("UTF-8","GB18030","C2C内部对帐表") . ".csv");    
    $out = $smarty->fetch('oukooext/purchase_bill_csv.htm');
    echo iconv("UTF-8","GB18030", $out);
    exit();
} else {
    $smarty->display('oukooext/purchase_bill.htm');
}
?>

<?php
function getCondition() {
    $order_type = $_REQUEST['order_type'];
    $start = $_REQUEST['start'];
    $end = $_REQUEST['end'];
    $amount = $_REQUEST['amount'];
    $voucher_no = $_REQUEST['voucher_no_serach'];
    $voucher_year = $_REQUEST['voucher_year_search'];
    $voucher_month = $_REQUEST['voucher_month_search'];

    $provider_id = $_REQUEST['provider_id'];

    $condition = "";
        //return $condition;
    if ($provider_id != '') {
        $condition .= " AND ii.provider_id = '$provider_id'";
    }

    if ($order_type == 'b2c' || $order_type == 'c2c' || $order_type == 'dx') {
        $condition .= " AND b.type = '$order_type'";
    }
    if (strtotime($start) > 0) {
        $condition .= " AND b.date >= '$start'";
    }
    if (strtotime($end) > 0) {
        $end = strftime('%Y-%m-%d', strtotime("+1 day", strtotime($end)));
        $condition .= " AND b.date < '$end'";
    }
    if ($amount != '') {
        $amount = floatval($amount);
        $condition .= " AND b.amount = $amount";
    }
    
    if ($voucher_no != '') {
        $condition .= " AND b.voucher_no = '$voucher_no'";
    }
    if (!empty($voucher_month) && !empty($voucher_year)) {
        if (strlen($voucher_month)==1) {
            $voucher_month = '0'.$voucher_month;
        }
        $v_start = $voucher_year.'-'.$voucher_month.'-01 00:00:00';
        $condition .= " AND b.voucher_date >= '$v_start'";
        $v_end = strftime('%Y-%m-%d', strtotime("+1 month", strtotime($v_start)));
        $condition .= " AND b.voucher_date < '$v_end'";
    } else if (!empty($voucher_year) && empty($voucher_month)) {
        $v_start = $voucher_year.'-01-01 00:00:00';
        $condition .= " AND b.voucher_date >= '$v_start'";
        $v_end = strftime('%Y-%m-%d', strtotime("+1 year", strtotime($v_start)));
        $condition .= " AND b.voucher_date < '$v_end'";
    }
    return $condition;
}
?>
