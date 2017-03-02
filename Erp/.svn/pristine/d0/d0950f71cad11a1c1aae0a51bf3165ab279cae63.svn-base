<?php
/**
 * 财务付款记录
 */

define('IN_ECS', true);

require('includes/init.php');

admin_priv('cw_c2c_buy_sale', 'cg_c2c_buy_sale');
require("function.php");
$act = $_REQUEST['act'];
$bill_id = $_REQUEST['bill_id'];

if ($act == 'has_invoice') {
    $sql = "UPDATE {$ecs->table('oukoo_inside_c2c_bill')} SET has_invoice = 'YES' WHERE bill_id = '$bill_id'";
    $db->query($sql);
}

$csv = $_REQUEST['csv'];
$size = 10;
$page = $_REQUEST['page'] ? $_REQUEST['page'] : 1;
$start = ($page - 1) * $size;

if ($csv == null) {
    $limit = "LIMIT $size";
    $offset = "OFFSET $start";
} else {
	admin_priv("admin_other_csv");
}

$order_sn = $_REQUEST['order_sn'];
if (empty($order_sn)) {
    $condition = getCondition();
    $sql_with_provider = "SELECT ii.provider_id, b.*, (b.amount+b.rebate_amount+b.prepayment_amount) AS total_amount
        FROM ecshop.ecs_oukoo_inside_c2c_bill b
        LEFT JOIN ecshop.order_bill_mapping obm ON obm.oukoo_inside_c2c_bill_id = b.bill_id
        LEFT JOIN ecshop.ecs_order_info oi ON oi.order_id = obm.order_id
        LEFT JOIN romeo.purchase_order_info poi ON poi.order_goods_id = obm.order_goods_id
        LEFT JOIN ecshop.ecs_order_goods og ON og.rec_id = obm.order_goods_id
        LEFT JOIN romeo.inventory_item_detail iid ON iid.ORDER_GOODS_ID = convert(og.rec_id using utf8)
        LEFT JOIN romeo.inventory_item ii ON iid.INVENTORY_ITEM_ID = ii.INVENTORY_ITEM_ID

        WHERE 1 $condition
        group by b.bill_id
        ORDER BY b.date DESC 
        $limit $offset
        ";
    $sqlc = "SELECT COUNT(DISTINCT(b.bill_id)), ii.provider_id, b.*, (b.amount+b.rebate_amount+b.prepayment_amount) AS total_amount
        FROM ecshop.ecs_oukoo_inside_c2c_bill b
        LEFT JOIN ecshop.order_bill_mapping obm ON obm.oukoo_inside_c2c_bill_id = b.bill_id
        LEFT JOIN ecshop.ecs_order_info oi ON oi.order_id = obm.order_id
        LEFT JOIN romeo.purchase_order_info poi ON poi.order_goods_id = obm.order_goods_id
        LEFT JOIN ecshop.ecs_order_goods og ON og.rec_id = obm.order_goods_id
        LEFT JOIN romeo.inventory_item_detail iid ON iid.ORDER_GOODS_ID = convert(og.rec_id using utf8)
        LEFT JOIN romeo.inventory_item ii ON iid.INVENTORY_ITEM_ID = ii.INVENTORY_ITEM_ID

        WHERE 1 $condition
        group by b.bill_id
    ";
} else {
    $sql = "
        SELECT *, icb.purchaser as purchaser
        FROM
        {$ecs->table('oukoo_inside_c2c_bill')} icb
        inner join ecshop.order_bill_mapping obm on obm.oukoo_inside_c2c_bill_id = icb.bill_id
        inner join {$ecs->table('order_info')} o on o.order_id = obm.order_id
        WHERE
        o.order_sn like '$order_sn'
        $limit $offset
    ";
    $sqlc = "
        SELECT count(*)
        FROM
        {$ecs->table('oukoo_inside_c2c_bill')} icb
        inner join ecshop.order_bill_mapping obm on obm.oukoo_inside_c2c_bill_id = icb.bill_id
        inner join {$ecs->table('order_info')} o on o.order_id = obm.order_id
        WHERE
        o.order_sn like '$order_sn'
    ";
}
$bills = $db->getAll($sql_with_provider);
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

$smarty->assign('admin_name', $_SESSION['admin_name']);
$smarty->assign('back', $_SERVER['REQUEST_URI']);
$smarty->assign('bills', $bills);
$smarty->assign('pager', $pager);
$smarty->assign('sum', $sum);
$smarty->assign('next_year', ( (int)date('Y', time()) + 1));

if ($csv) {
    header("Content-type:application/vnd.ms-excel");
    header("Content-Disposition:filename=" . iconv("UTF-8","GB18030","C2C内部对帐表") . ".csv");    
    $out = $smarty->fetch('oukooext/c2c_buy_sale_bill_csv.htm');
    echo iconv("UTF-8","GB18030", $out);
    exit();
} else {
    $smarty->display('oukooext/c2c_buy_sale_bill.htm');
}
?>

<?php
function getCondition() {
	global $db;
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
    $sql = "select distinct icb.bill_id  
		from ecshop.ecs_oukoo_inside_c2c_bill icb
		inner join ecshop.order_bill_mapping obm on obm.oukoo_inside_c2c_bill_id = icb.bill_id
		inner join ecshop.ecs_order_info o on o.order_id = obm.order_id
		where  ". party_sql('o.party_id');
	$bill_ids = $db->getCol($sql);
	$bill_ids_str = join(", ", $bill_ids);
	$condition .= " and b.bill_id in ({$bill_ids_str})";
    return $condition;
}
?>
