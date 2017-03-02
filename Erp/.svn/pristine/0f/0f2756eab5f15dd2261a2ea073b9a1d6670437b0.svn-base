<?php
define('IN_ECS', true);

require_once('includes/init.php');
require_once(ROOT_PATH . 'admin/function.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
admin_priv('distribution_warehouse_bill');
extract($_REQUEST);
if ($act == 'update_print_date') {
    $warehouse_bill_ids = explode(",", $update_bill_ids);
    $sql_ids = db_create_in($warehouse_bill_ids, "warehouse_bill_id");
    $sql = "UPDATE warehouse_bill SET print_date = '{$print_date}' 
        WHERE $sql_ids ";
    $db->query($sql);
} elseif ($act == 'update_print_batch_id') {
    $sql = "UPDATE warehouse_bill SET print_batch_id = '{$print_batch_id}'
        WHERE warehouse_bill_id = '{$update_bill_id}' ";
    $db->query($sql);
}
$condition = get_condition();

global $db;	
$partyId=$_SESSION['party_id'];

// 获得仓库记录单据
$warehouse_bills = get_warehouse_bills($condition);
$order_type_mapping = array('ALL' => '全部', 'SALE' => '销售', 'PURCHASE' => '采购');
$partner_name_mapping = array('ALL' => '全部', 'SALE' => '用户', 'PURCHASE' => '供应商');
$title_name_mapping = array(
    'SALE' => array('in' => '销售退货', 'out' => '销售出库'),
    'PURCHASE' => array('in' => '入库', 'out' => '退货')
    );
$type_mapping = array('ALL' => '全部', 'out' => '出库', 'in' => '入库');
if (!empty($warehouse_bills)) {
    // 分页每页数据量
    $size = 10;
    // 获得单据明细
    foreach ($warehouse_bills as $key => $warehouse_bill) {
    	$warehouse_bills[$key]['party']="NORMAL";
    	if($partyId=='65536' || $partyId=='65566' || is_jjshouse($partyId)){
    		$sql="select order_sn
    	          from ecshop.ecs_order_info
    	          where order_id='{$warehouse_bill['bill_batch_id']}'";
    	    $warehouse_bill['bill_batch_id']=$db->getOne($sql);
    	    $warehouse_bills[$key]['party']="SPECIAL";
    	}
        $warehouse_bills[$key]['bill_amount'] = sprintf("%.2f",
            $warehouse_bill['bill_amount']);
        $warehouse_bill_details = get_warehouse_bill_details_by_bill_id(
            $warehouse_bill['warehouse_bill_id']);
        $warehouse_bills[$key]['details'] = $warehouse_bill_details;
        // 分页
        $warehouse_bills[$key]['warehouse_bill_details_count'] =
            ceil(count($warehouse_bill_details) / $size);
        $warehouse_bills[$key]['size'] = $size;
        // 编号，如果有自填编号，则打印自填编号
        if($partyId=='65536' || $partyId=='65566' || is_jjshouse($partyId)){
        	$warehouse_bills[$key]['bill_batch_id'] = sprintf("%012s", 
            empty($warehouse_bill['print_batch_id']) ? 
                $warehouse_bill['bill_batch_id'] : $warehouse_bill['print_batch_id']);
        }else{
        	$warehouse_bills[$key]['bill_batch_id'] = sprintf("%010d", 
        	empty($warehouse_bill['print_batch_id']) ? 
                $warehouse_bill['bill_batch_id'] : $warehouse_bill['print_batch_id']);
        }          
        // 设置订单销售采购类型
        $warehouse_bills[$key]['order_type_name'] =
            $order_type_mapping[$warehouse_bill['order_type_id']];
        // 设置出入库类型
        $warehouse_bills[$key]['type_name'] =
            $type_mapping[$warehouse_bill['type']];
        // 设置合作用户（供应商、用户）类型
        $warehouse_bills[$key]['partner_type'] =
            $partner_name_mapping[$warehouse_bill['order_type_id']];
        $warehouse_bills[$key]['title'] = 
            $title_name_mapping[$warehouse_bill['order_type_id']][$warehouse_bill['type']];
        
        // 打印时间
        if (empty($warehouse_bill['print_date'])) {
            $warehouse_bills[$key]['print_date'] = $warehouse_bill['bill_date'];
        }
        if ($act == 'print') {
            if ($warehouse_bill['order_type_id'] == 'SALE' && $warehouse_bill_details) {
                $warehouse_bills[$key]['consignee'] = $db->getOne("
                    SELECT consignee FROM ecs_order_info WHERE order_id = '{$warehouse_bill_details[0]['order_id']}'
                ");
            }
        }
        // 修改采购出库为负值
        if (($warehouse_bill['order_type_id'] == 'PURCHASE' && $warehouse_bill['type'] == 'out')
            || ($warehouse_bill['order_type_id'] == 'SALE' && $warehouse_bill['type'] == 'in')) {
            $warehouse_bills[$key]['bill_amount'] = sprintf("%.2f", -$warehouse_bills[$key]['bill_amount']);
            foreach ($warehouse_bills[$key]['details'] as $detail_key => $warehouse_bill_detail) {
                $warehouse_bills[$key]['details'][$detail_key]['goods_amount'] = 
                    sprintf("%.2f", -$warehouse_bills[$key]['details'][$detail_key]['goods_amount']);
                $warehouse_bills[$key]['details'][$detail_key]['goods_number'] =
                    -$warehouse_bills[$key]['details'][$detail_key]['goods_number'];
            }
        }
        if ($warehouse_bill['order_type_id'] == 'SALE' && $warehouse_bill['type'] == 'in') {
            $warehouse_bills[$key]['relation'] = get_warehouse_relation($warehouse_bills[$key]['details']);
        }
    }
}

$smarty->assign('warehouse_bills', $warehouse_bills);
$providers = getProviders();
foreach ($providers as $key => $provider) {
    $providers[$key] = $provider['provider_name'];
}
$smarty->assign('providers', $providers );

$distributors = Helper_Array::toHashmap((array)$db->getAll("SELECT distributor_id, name FROM distributor WHERE distributor_id IN (20, 28, 31, 55)"), 'distributor_id', 'name');
$smarty->assign('distributors', $distributors);

if ($act == 'print') {
    if (empty($warehouse_bills)) {
//        print "<script>alert('打印批次里面存在不同的分销商的订单')</script>";
        exit();
    }
    $smarty->display('oukooext/print_warehouse_bill.htm');
} else {
    $smarty->assign('party_mapping', party_mapping());
    $smarty->assign('order_type_mapping', $order_type_mapping);
    $smarty->assign('type_mapping', $type_mapping);
    $smarty->display('oukooext/warehouse_bill.htm');
}

/**
 * 获得仓库记录
 *
 * @param unknown_type $condition
 * @return unknown
 */
function get_warehouse_bills($condition) {
    global $db;
    $sql = "SELECT * 
        FROM warehouse_bill
        WHERE 1 {$condition} ";
    return $db->getAll($sql);
}

/**
 * 获得仓库记录明细
 *
 * @param unknown_type $warehouse_bill_id
 * @return unknown
 */
function get_warehouse_bill_details_by_bill_id($warehouse_bill_id) {
    global $db;
    $sql = "SELECT bd.*, 
            case
        	   when (g.cat_id IN (1512, 1508) OR g.top_cat_id IN (1)) then '台'
        	   when (g.cat_id IN (1509, 1862) OR g.top_cat_id IN (597)) then '个'
        	   when (g.cat_id IN (1517)) then '本'
        	   else '个'
        	end as uom 
        FROM warehouse_bill_detail bd
             LEFT JOIN ecs_goods g ON bd.goods_id = g.goods_id
        WHERE warehouse_bill_id = '{$warehouse_bill_id}' 
        ORDER BY bd.goods_name";
    $warehouse_bill_details = $db->getAll($sql);
    foreach ($warehouse_bill_details as $key => $warehouse_bill_detail) {
    	$warehouse_bill_details[$key]['goods_price'] = sprintf("%.2f",
    	    $warehouse_bill_detail['goods_price']);
    	$warehouse_bill_details[$key]['goods_amount'] = sprintf("%.2f",
    	    $warehouse_bill_detail['goods_amount']);
    }
    return $warehouse_bill_details;
}

function get_condition() {
    global $db;
    extract($_REQUEST);
    if ($act != 'print') {
        check_dates($start, $end, "3 MONTH");
        if ($start != null) {
            $condition .= " AND bill_date >= '{$start}' ";
        }
        if ($end != null) {
            $condition .= " AND bill_date < date_add('{$end}', INTERVAL 1 DAY) ";
        }

        if ($order_type_id == 'PURCHASE') {
			$condition .= " AND order_type_id = 'PURCHASE' ";
			if ($provider_id != null) {
				$condition .= " AND partner_id = '{$provider_id}' ";
			}
        } else if ($order_type_id == 'SALE') {
			$condition .= " AND order_type_id = 'SALE' ";
			if ($distributor_id != null) {
				$condition .= " AND partner_id = '{$distributor_id}' "; 
			}
        }

        if (!is_null($type) && $type != 'ALL') {
            $condition .= " AND type = '{$type}' ";
        }

        if (!is_null($search_text) && trim($search_text)) {
            $keyword = trim($search_text);
            // 搜索订单号或者发票号，搜索发票号没有索引，速度很慢
        }
        $_REQUEST['start'] = $start;
        $_REQUEST['end'] = $end;
    } else {
        if ($warehouse_bill_str) {
            $warehouse_bill_id_list = explode(',', $warehouse_bill_str);
            //过滤掉重复的warehouse_bill_id
            $warehouse_bill_id_list = array_unique($warehouse_bill_id_list);
            $warehouse_bill_ids = db_create_in($warehouse_bill_id_list,
                "warehouse_bill_id");
            $condition .= " AND {$warehouse_bill_ids} ";
        }
    }
    return $condition;
}

function get_warehouse_relation($details) {
    $order_ids = array();
    foreach ($details as $detail) {
    	$order_ids[] = $detail['order_id'];
    }
    $sql_order_id = db_create_in($order_ids, 'o.order_id');
    global $db;
    $sql = "SELECT distinct o.order_sn, o.root_order_sn, osi.shipping_invoice
         FROM order_relation o 
            LEFT JOIN romeo.order_shipping_invoice osi ON o.order_id = osi.order_id
         WHERE {$sql_order_id} ";
    $relations = $db->getAll($sql);
    $res = "";
    foreach ($relations as $relation) {
    	$res .= "订单号:{$relation['order_sn']}, 原始订单:{$relation['root_order_sn']}, 发票号:{$relation['shipping_invoice']}<br />";
    }
    return $res;
}
?>
