<?php
/**
 * 待发货
 */
define('IN_ECS', true);
require('includes/init.php');
require('includes/lib_service.php');

admin_priv('wl_dcV2');
require_once("function.php");
require_once("includes/lib_common.php");
require_once("includes/lib_order.php");
require_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
set_time_limit(300);

$back = remove_param_in_url($_SERVER['REQUEST_URI'], "info");
$csv = $_REQUEST['csv'];

$size = 10;
$page = $_REQUEST['page'] ? $_REQUEST['page'] : 1;
$start = ($page - 1) * $size;

if ($csv == null) {
    $limit = "LIMIT $size";
    $offset = "OFFSET $start";
} else {
    admin_priv("3wl_dcV2_csv");
}

$condition = _get_condition();


//判断使用索引
$use_index = "";
if(empty($_REQUEST['order_sn']) && empty($_REQUEST['taobao_order_sn']) && !(isset($_REQUEST['search_type']) && in_array($_REQUEST['search_type'],array('user_name','tracking_number')) && !empty($_REQUEST['search_text']))){
	$use_index = " force index (order_info_multi_index) ";
}

if(isset($_REQUEST['search_type']) && $_REQUEST['search_type']=='tracking_number' && trim($_REQUEST['search_text'])!=''){
	$cond = " INNER JOIN romeo.order_shipment os ON o.order_id = cast(os.order_id AS UNSIGNED) ";
}else{
	$cond = " INNER JOIN romeo.order_shipment os ON convert(o.order_id using utf8) = os.order_id ";
}
$sql = "SELECT 
        DISTINCT o.order_id
    FROM 
        ecshop.ecs_order_info AS o {$use_index}
        INNER JOIN ecshop.ecs_order_goods og ON og.order_id = o.order_id
		{$cond}
        INNER JOIN romeo.order_shipment oss ON os.order_id = oss.order_id
        INNER JOIN romeo.shipment s ON oss.shipment_id = s.shipment_id and s.shipping_category='SHIPPING_SEND'  
    WHERE 
        o.order_status = 1                                           
        AND o.order_type_id IN ('SALE','RMA_EXCHANGE', 'SHIP_ONLY')  {$condition} 
    ORDER BY o.order_time $limit $offset
".' -- dcV2 '.__LINE__.PHP_EOL;
//QLOG::LOG("dcV2_sql:".$sql);
$db->getAllRefby($sql, array('order_id'), $order_ids, $ref2, true);
$orders = _get_order_info_list($order_ids['order_id']);
$sqlc = "
    SELECT 
        count(DISTINCT o.order_id)
    FROM 
        ecshop.ecs_order_info AS o {$use_index}
        INNER JOIN ecshop.ecs_order_goods og ON og.order_id = o.order_id
		{$cond}
        INNER JOIN romeo.order_shipment oss ON os.order_id = oss.order_id
        INNER JOIN romeo.shipment s ON oss.shipment_id = s.shipment_id  and s.shipping_category='SHIPPING_SEND'  
    WHERE 
        o.order_status = 1                                           
        AND o.order_type_id IN ('SALE','RMA_EXCHANGE', 'SHIP_ONLY')  {$condition}  
";
$count = $db->getOne($sqlc);
$pager = Pager($count, $size, $page);
$payments = getPayments();
$shippingTypes = getShippingTypes();

// 取得订单的商品
_fetch_order_goods($orders);
$province_list = get_regions(1, $GLOBALS['_CFG']['shop_country']);
$province_hashamp = Helper_Array::toHashmap($province_list, 'region_id', 'region_name');
foreach ($orders as $key => $order) {   
    // 取得订单的送货省份
    $orders[$key]['province_name'] = $province_hashamp[$order['province']];
    
    //重新获得支付方式和货运方式
    $orders[$key]['shipping_name'] = $shippingTypes[$order['shipping_id']] ? 
        $shippingTypes[$order['shipping_id']]['shipping_name'] : 
        $order['shipping_name'];
        
    $orders[$key]['pay_name'] = $payments[$order['pay_id']] ? 
        $payments[$order['pay_id']]['pay_name'] : 
        $order['pay_name'];
    
    // 发货状态名
    $orders[$key]['shipping_status_name'] = $_CFG['adminvars']['shipping_status'][$order['shipping_status']];
    
    // 订单状态名
    $orders[$key]['order_status_name'] = $_CFG['adminvars']['order_status'][$order['order_status']];
    
    // 如果是自提的，给出默认的快递公司
    $order_shipping = $shippingTypes[$order['shipping_id']];
    if ($order['carrier_id'] == 0 && $order_shipping['support_no_cod'] == 1 && $order_shipping['support_cod'] == 1) { 
        if($orders[$key]['province'] == 10) {  //本地自提
            $orders[$key]['carrier_id'] = 13;
        } else { //外地自提
            $orders[$key]['carrier_id'] = 10;
        }
    }
    
    // 取得备注 和 订单确认时间
    $sql = "SELECT * FROM {$ecs->table('order_action')} 
        WHERE order_id = {$order['order_id']} ORDER BY action_time ASC
    ".' -- dcV2 '.__LINE__.PHP_EOL;
    $action_notes = $db->getAll($sql);
    $orders[$key]['confirm_time'] = 0;
    foreach ($action_notes as $note) {
        if ($note['action_note'] != '') {
            $orders[$key]['action_notes'][] = $note;
        }
        if ($note['order_status'] == 1 && !$orders[$key]['confirm_time']) {
            $orders[$key]['confirm_time'] = $note['action_time'];
            $orders[$key]['confirm_user'] = $note['action_user'];
        }
    }
    
    //延迟发货相关信息
    if($orders[$key]['best_time']) {
        if (strtotime($orders[$key]['best_time']) <= time()) {
            $orders[$key]['can_be_issued'] = true;
        } else {
            $orders[$key]['can_be_issued'] = false;
        }
    } else {
        $orders[$key]['can_be_issued'] = true;
    }
}


$shipping_name_mapping = array(
    "上" => "S", "山" => "S", "深" => "S", "四" => "S", "沈" => "S", "陕" => "S", "石" => "S",
    "北" => "B", "东" => "D", "辽" => "L", "浙" => "Z", "杭" => "H", "黑" => "H", "河" => "H", 
    "海" => "H", "湖" => "H", "广" => "G", "贵" => "G", "成" => "C", "重" => "C", "长" => "C",
    "江" => "J", "吉" => "J", "青" => "Q", "南" => "N", "宁" => "N", "武" => "W", "温" => "W",
    "天" => "T", "太" => "T", "福" => "F", "厦" => "X", "云" => "Y",
);
foreach ($shippingTypes as $key => $shippingType) {
    $shippingTypes[$key]['shipping_name'] = 
        $shipping_name_mapping[sub_str($shippingType['shipping_name'], 0, 1, false)] . 
        $shippingType['shipping_name'] ;
}


$carriers = getCarriers();
$smarty->assign('carriers', $carriers);
$smarty->assign('shippingTypes', $shippingTypes);
$smarty->assign('orders', $orders);
$smarty->assign('pager', $pager);
$smarty->assign('back', $back);
$smarty->assign('all_shipping_status', $_CFG['adminvars']['shipping_status']);
$smarty->assign ('available_facility',  array ('999999' => '未指定仓库' ) + array_intersect_assoc(get_available_facility(),get_user_facility()));
if ($csv){
	if(isset($_REQUEST['search_type']) && $_REQUEST['search_type']=='tracking_number' && trim($_REQUEST['search_text'])!=''){
		$cond = " INNER JOIN romeo.order_shipment os ON o.order_id = cast(os.order_id AS UNSIGNED) ";
	}else{
		$cond = " INNER JOIN romeo.order_shipment os ON convert(o.order_id using utf8) = os.order_id ";
	}
    $sql = "SELECT 
                 o.order_sn,o.order_time,p.name,f.facility_name,es.shipping_name,s.tracking_number,s.shipping_leqee_weight
           FROM 
                 {$ecs->table('order_info')} AS o
                 INNER JOIN {$ecs->table('shipping')} AS es ON o.shipping_id = es.shipping_id
                 INNER JOIN romeo.party p ON convert(o.party_id using utf8) = p.party_id
                 INNER JOIN romeo.facility f ON o.facility_id = f.facility_id
                 {$cond}
                 INNER JOIN romeo.shipment s ON os.shipment_id = s.shipment_id
           WHERE 
                 o.shipping_status = 8 and o.order_status = 1 {$condition}  AND s.shipping_leqee_weight is not null
           order by s.tracking_number
          ".' -- dcV2 '.__LINE__.PHP_EOL;
    $orders = $db->getAll($sql);
    $smarty->assign('orders', $orders);
    header("Content-type:application/vnd.ms-excel");
    header("Content-Disposition:filename=" . iconv("UTF-8","GB18030","已称重未发货运单报表") . ".csv");
    $out = $smarty->fetch('oukooext/dc_weight_csv.htm');
    echo iconv("UTF-8","GB18030", $out);
    exit();
} else {
    $smarty->assign('search_types', $search_types);
    $smarty->assign('facility_name', facility_mapping($_SESSION['facility_id']));
    $smarty->assign('modify_send_order_shipment', check_admin_user_priv($_SESSION['admin_name'], 'modify_send_order_shipment'));
    $smarty->display('oukooext/dcV2.htm');
}


function _get_order_info_list($order_ids){
	global $db;
    // 灭ECB 20151202 邪恶的大鲵
	$sql = "SELECT
        o.order_id,
        o.order_sn,
        o.consignee,
        o.shipping_id,
        o.shipping_name,
        o.shipping_status,
        o.party_id,
        o.postscript,
        o.province,
        o.pay_id,
        o.pay_name,
        o.order_status,
        o.order_time,
        o.best_time,
        o.carrier_bill_id bill_id,
        s.tracking_number bill_no,
        s.carrier_id
    FROM
        ecshop.ecs_order_info o 
    LEFT JOIN romeo.order_shipment os ON os.order_id = CONVERT (o.order_id USING utf8)
    LEFT JOIN romeo.shipment s ON os.shipment_id = s.shipment_id
    WHERE
        o.order_id  " . db_create_in($order_ids) . "
        AND s. STATUS != 'SHIPMENT_CANCELLED'
    GROUP BY order_id
	".' -- dcV2 '.__LINE__.PHP_EOL;
	return $db->getAll($sql);
}
/**
 * 取得配送方式列表, 并包含每个配送方式的已配货待出库订单数
 * 
 * @return array
 */
function _get_shipping_list() 
{
    global $db, $ecs;
    $result = getShippingTypes();
    
    // 查询出对应的已出库待发货订单数量
    # 添加party条件判断 2009/08/06 yxiang
    if ($result) {
        $sql = "SELECT
                COUNT(DISTINCT order_sn) as count, shipping_id
            FROM 
                {$ecs->table('order_info')} AS o force index (order_info_multi_index)
                INNER JOIN {$ecs->table('payment')} AS p ON p.pay_id = o.pay_id
            WHERE 
                order_status = 1 AND shipping_status = 9 
                AND( (o.order_type_id = 'SALE' AND (p.pay_code = 'cod' OR o.pay_status = 2) ) 
                     OR o.order_type_id IN ('RMA_EXCHANGE', 'SHIP_ONLY')
                   )
                AND biaoju_store_id IN (0, 7) 
                AND ". party_sql('o.party_id') ." 
                AND ". facility_sql('o.facility_id') ."
                AND (best_time IS NULL OR best_time <= NOW()) AND
                shipping_id ". db_create_in(array_keys($result)) ."
            GROUP BY shipping_id
        ".' -- dcV2 '.__LINE__.PHP_EOL;
        $db->getAllRefby($sql, array('shipping_id'), $ref_field2, $ref2, true);
        
        foreach ($result as $key => $row) {
            if (isset($ref2['shipping_id'][$row['shipping_id']])) {
                $result[$key]['order_count'] = $ref2['shipping_id'][$row['shipping_id']][0]['count'];    
            } else {
                $result[$key]['order_count'] = 0;
            }
        }
    }
    
    return $result;
}

/**
 * 通过传入的订单得到订单的商品详细信息
 * 是按订单循环查询的替代方案
 * 
 * @param array $orders 对订单数组的引用
 */
function _fetch_order_goods(& $orders)
{
    if (!is_array($orders)) return;
    global $db; 
    
    // 取得订单id的数组，用于构造in查询
    $oid = array();
    foreach ($orders as $order) {
        $oid[] = $order['order_id'];
    }
    
    /* 查询order_goods记录并组装到orders */
    if (!empty($oid)) {
        // 查询出订单的商品
        $sql = "SELECT 
                og.order_id,og.rec_id,og.goods_name,og.goods_number,ifnull(sum(-iid.quantity_on_hand_diff),0) as out_number,
                 CONCAT_WS('_', og.goods_id, og.style_id) AS goods_style_id
            FROM
                ecshop.ecs_order_goods AS og 
                left join romeo.inventory_item_detail iid 
    				on iid.order_goods_id = convert(og.rec_id using utf8)
            WHERE og.order_id " . db_create_in($oid) . " 
            group by og.rec_id
        ".' -- dcV2 '.__LINE__.PHP_EOL;
        $goods_list = $db->getAllRefby($sql, array('order_id'), $tmp, $refs, false);
        // 组装到订单
        foreach ($orders as $key => $order) {
            $orders[$key]['goods_list'] = $refs['order_id'][$order['order_id']];
        }
        
        return $refs;
    }  
}

/**
 * 通过请求构造查询条件
 * 
 * @return string
 */
function _get_condition() {
    global $ecs;
	$order_sn = trim($_REQUEST['order_sn']);
	$taobao_order_sn = trim($_REQUEST['taobao_order_sn']);
    $search_type = $_REQUEST['search_type'];
    $search_text = trim($_REQUEST['search_text']);
    $start = $_REQUEST['start'];
    $end = $_REQUEST['end'];

    $act = $_REQUEST['act'];

    $order_condition = "";
	if(isset($order_sn) && $order_sn !=''){
		$order_condition .=" AND o.order_sn = '{$order_sn}' ";
	}
	if(isset($taobao_order_sn) && $taobao_order_sn !=''){
		$order_condition .=" AND o.taobao_order_sn like '{$taobao_order_sn}%' ";
	}
	
    if (strtotime($start) > 0) {
        $order_condition .= " AND order_time >= '$start'";
    }
    if (strtotime($end) > 0) {
        $end = strftime('%Y-%m-%d', strtotime("+1 day", strtotime($end)));
        $order_condition .= " AND order_time <= '$end'";
    }

    
    // 按关键字模糊搜索
    if ($search_type != '-1' && $search_text !='') {
    	$date = date('Y-m-d',strtotime('-3 Months'));    
        switch ($search_type) {
            case "user_name":
                $order_condition .= " AND o.nick_name like '{$search_text}%' AND o.order_time > '{$date}' ";
                break;
           	case "tel_mobile" :
				$order_condition .= " AND (o.tel LIKE '{$search_text}%' OR info.mobile LIKE '{$search_text}%' ) AND o.order_time > '{$date}'";
				break;
			
			case "consignee" :
				$other_search_text = str_replace ( " ", "", $search_text );
				$order_condition .= " AND o.consignee LIKE '{$search_text}%' AND o.order_time > '{$date}' ";
				break;
			
			case "tracking_number" :
				$order_condition .= " AND s.tracking_number = '{$search_text}' ";
				break;
        }
    }

    if (trim($order_condition) == '') {
    	$date = date('Y-m-d',strtotime('-7 days')); 
        $order_condition .= " AND o.order_time >= '$date' ";
        $_REQUEST['start'] = $date;
    }
	if( $act !='search'){
        $order_condition .= " AND o.shipping_status = 8 ";
    }
    $facility_id = $_REQUEST['facility_id'];
    if(isset($facility_id) && $facility_id !='999999'){
		$order_condition .= " AND o.facility_id = '{$facility_id}' ";
	}else{
		$facility_ids =implode("','",array_keys(array_intersect_assoc(get_available_facility(),get_user_facility())));
		$order_condition .= " AND o.facility_id in ('{$facility_ids}') ";
	}
	# 添加party条件判断 2009/08/06 yxiang
	$order_condition .= " AND ". party_sql('o.party_id');
    return $order_condition;
}



?>