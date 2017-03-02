<?php 

/**
 * 创建拣选任务
 * 
 * @author yxiang@oukoo.com
 * @copyright 2010 ouku.com
 */

define('IN_ECS', true);
require_once('includes/init.php');
require_once('function.php');
 
if(!in_array($_SESSION['party_id'], array('16','65606','64','65565','65543','65566','65563'))){
    if(!in_array($_SESSION['admin_name'], array('cywang', 'jxiong','xlhong','hbai')))
        die("亲！请进入新仓系统使用！！！！！！！！！");
 }

admin_priv('inventory_picklist_new');
require_once(ROOT_PATH . 'includes/cls_json.php');
require_once(ROOT_PATH . 'RomeoApi/lib_soap.php');
require_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'includes/cls_page.php');
// 请求
$act = 
    isset($_REQUEST['act']) && in_array(trim($_REQUEST['act']), array('search','picklist','picking_list', 'dispatch_list', 'cancelle_list')) 
    ? $_REQUEST['act'] 
    : 'search' ;

// 当前页码
$page = 
    is_numeric($_REQUEST['page']) && $_REQUEST['page'] > 0
    ? $_REQUEST['page'] 
    : 1 ;
// 每页多少记录数
$page_size =
    is_numeric($_REQUEST['size']) && $_REQUEST['size'] > 0
    ? $_REQUEST['size']
    : 20 ;

// 消息
$message =
    isset($_REQUEST['message']) && trim($_REQUEST['message'])
    ? $_REQUEST['message']
    : false;

//仓库
$facility_list = array_intersect(get_user_facility(), get_available_facility());
// 所处仓库
if (empty($facility_list)) {
    die('没有仓库权限'); 
}
//分页
$page_size_list = array('20' => '20', '50' => '50', '100' => '100');
// 配送方式
$shipping_type_list = Helper_Array::toHashmap(getShippingTypes(), 'shipping_id', 'shipping_name');

// 配送方式
$status_list = array(
    'PICKLIST_INPUT'=>'未打印', 
    'PICKLIST_PRINTED'=>'已打印',
    'PICKLIST_ASSIGNED'=>'已经分配面单',
    'PICKLIST_PICKED'=>'已完成拣选', 
    'PICKLIST_CANCELLED'=>'已取消'
);

// 过滤条件
$filter = array(
    // 每页多少条记录
    'size' => 
        $page_size,
    // 仓库
    'facility_id' => 
        isset($_REQUEST['facility_id']) && isset($facility_list[$_REQUEST['facility_id']]) 
        ? $_REQUEST['facility_id'] 
        : NULL ,
    // 配送方式
    'shipping_id' =>
        isset($_REQUEST['shipping_id']) && isset($shipping_type_list[$_REQUEST['shipping_id']]) 
        ? $_REQUEST['shipping_id'] 
        : NULL ,
    'status' =>
        isset($_REQUEST['status']) && isset($status_list[$_REQUEST['status']]) 
        ? $_REQUEST['status'] 
        : 'PICKLIST_INPUT',
);

// 链接
$url = 'picklist_shipment.php';
foreach ($filter as $k => $v) {
    if ($v) {
        $url = add_param_in_url($url, $k, $v);
    }
}

if ($filter['facility_id']) {
    $condition1 .= " AND o.FACILITY_ID = '{$filter['facility_id']}'";
} else {
    $condition1 .= " AND ". facility_sql('o.FACILITY_ID', array_keys($facility_list));
}
if ($filter['shipping_id']) {
    $condition2 .= " AND s.SHIPMENT_TYPE_ID = '{$filter['shipping_id']}'";
}
if ($act == 'search') {
    if ($filter['status'] == 'PICKLIST_INPUT') {
        $sql_repeat_order = "
            SELECT T.seller, T.facility_name, Cast( group_concat( T.order_sn SEPARATOR '\n' ) AS char( 1000 ) ) AS order_sn_list, 
                 Cast( group_concat( T.order_id SEPARATOR '\n' ) AS char( 1000 ) ) AS order_id_list, 
                T.goods_name_list, T.goods_number_list, count(*) AS repeat_order_count, T.facility_id,
                T.shipping_id, T.shipping_name, T.goods_id_list, T.style_id_list,
                Cast( group_concat( T.shipment_id SEPARATOR '\n' ) AS char( 1000 ) ) AS shipment_id_list,
                count(distinct T.shipment_id) as shipment_num, count(distinct T.order_id) as order_num
            FROM ( 
                SELECT o.order_sn, o.order_id, GROUP_CONCAT( og.goods_name order by og.goods_id SEPARATOR '\n' ) AS goods_name_list, 
                    Cast( group_concat( og.goods_id order by og.goods_id SEPARATOR '\n' ) AS char( 1000 ) ) AS goods_id_list,
                    Cast( group_concat( og.style_id order by og.goods_id SEPARATOR '\n' ) AS char( 1000 ) ) AS style_id_list,
                    Cast( group_concat( og.goods_number  order by og.goods_id SEPARATOR '\n' ) AS char( 1000 ) ) AS goods_number_list, 
                    p.name AS seller, f.facility_name, f.facility_id, o.shipping_id, o.shipping_name, s.shipment_id
                FROM ecshop.ecs_order_goods og 
                LEFT JOIN ecshop.ecs_order_info o ON og.order_id = o.order_id 
                LEFT JOIN romeo.order_inv_reserved r ON o.order_id = r.order_id
                LEFT JOIN romeo.order_shipment os ON os.order_id = CONVERT( o.order_id USING utf8 )
                LEFT JOIN romeo.shipment s on os.shipment_id = s.shipment_id
                LEFT JOIN romeo.party p ON p.party_id = o.party_id 
                LEFT JOIN romeo.facility f ON f.facility_id = o.facility_id
                LEFT JOIN ecshop.distributor d ON d.distributor_id = o.distributor_id 
                LEFT JOIN ecshop.main_distributor m ON m.main_distributor_id = d.main_distributor_id
                LEFT JOIN romeo.picklist_item pi on pi.order_id = og.order_id
                WHERE r.STATUS = 'Y' AND s.STATUS = 'SHIPMENT_INPUT' AND s.shipping_category = 'SHIPPING_SEND' 
                 AND pi.picklist_item_id is null
                 AND ". party_sql('o.PARTY_ID') . $condition1 . $condition2 . " 
                 AND (m.type is null or m.type = 'zhixiao') 
                GROUP BY o.order_id
                ORDER BY o.reserved_time,og.goods_id, og.goods_number
                )T 
            GROUP BY T.GOODS_ID_LIST, T.goods_number_list , T.facility_id, T.shipping_id
            having shipment_num = order_num
            ORDER BY count(*) DESC " ;
    } elseif ($filter['status'] == 'PICKLIST_PICKED') {
        $sql_repeat_order = "
            select p.picklist_id, GROUP_CONCAT( DISTINCT og.goods_name SEPARATOR '\n' ) as goods_name_list,
                cast(group_concat(DISTINCT og.goods_number SEPARATOR '\n') as char(1000)) as goods_number_list,
                cast(group_concat(DISTINCT og.goods_id SEPARATOR '\n') as char(1000)) as goods_id_list,
                cast( group_concat( og.style_id SEPARATOR '\n' ) as char( 1000 ) ) as style_id_list,
                cast(group_concat(DISTINCT o.order_sn SEPARATOR '\n') as char(1000)) as order_sn_list,
                cast(group_concat(DISTINCT o.order_id SEPARATOR '\n') as char(1000)) as order_id_list,
                f.facility_name, o.shipping_name, count(DISTINCT o.order_id) as repeat_order_count, o.shipping_id,
                Cast( group_concat(DISTINCT s.shipment_id SEPARATOR '\n' ) AS char( 1000 ) ) AS shipment_id_list,
                rp.name as seller, count(distinct pi.order_id) as item_order_num, 
                count(distinct (select order_id from ecshop.ecs_order_info oi 
                     where oi.order_id = pi.order_id and oi.shipping_status in (1, 2, 3, 6, 11) )
                ) as order_num
            from romeo.picklist p
            left join romeo.picklist_item pi on p.picklist_id = pi.picklist_id
            left join romeo.order_inv_reserved oir on oir.order_id = pi.order_id 
            left join ecshop.ecs_order_info o on pi.order_id = o.order_id 
            left join ecshop.ecs_order_goods og on o.order_id = og.order_id 
            left join romeo.order_shipment os on o.order_id = os.order_id
            left join romeo.shipment s on os.shipment_id = s.shipment_id
            left join romeo.facility f on f.facility_id = o.facility_id
            left join romeo.party rp on o.party_id = rp.party_id
            where --  oir.status = 'Y' and
             p.status = 'PICKLIST_ASSIGNED' and
             ". party_sql('o.PARTY_ID'). $condition1 . $condition2. "
            group by p.picklist_id, f.facility_id, o.shipping_id
            having item_order_num = order_num
            order by p.picklist_id 
        ";
    } else {
        $sql_repeat_order = "
            select p.picklist_id, GROUP_CONCAT( DISTINCT og.goods_name SEPARATOR '\n' ) as goods_name_list,
                cast(group_concat(DISTINCT og.goods_number SEPARATOR '\n') as char(1000)) as goods_number_list,
                cast(group_concat(DISTINCT og.goods_id SEPARATOR '\n') as char(1000)) as goods_id_list,
                cast( group_concat( og.style_id SEPARATOR '\n' ) as char( 1000 ) ) as style_id_list,
                cast(group_concat(DISTINCT o.order_sn SEPARATOR '\n') as char(1000)) as order_sn_list,
                cast(group_concat(DISTINCT o.order_id SEPARATOR '\n') as char(1000)) as order_id_list,
                f.facility_name, o.shipping_name, count(DISTINCT o.order_id) as repeat_order_count, o.shipping_id,
                Cast( group_concat(DISTINCT s.shipment_id SEPARATOR '\n' ) AS char( 1000 ) ) AS shipment_id_list,
                rp.name as seller
            from romeo.picklist p
            left join romeo.picklist_item pi on p.picklist_id = pi.picklist_id
            left join romeo.order_inv_reserved oir on oir.order_id = pi.order_id 
             join ecshop.ecs_order_info o on pi.order_id = o.order_id 
            left join ecshop.ecs_order_goods og on o.order_id = og.order_id 
            left join romeo.order_shipment os on o.order_id = os.order_id
            left join romeo.shipment s on os.shipment_id = s.shipment_id
            left join romeo.facility f on f.facility_id = o.facility_id
            left join romeo.party rp on o.party_id = rp.party_id
            where oir.status = 'Y'  and p.status = '{$filter['status']}'
            and ". party_sql('o.PARTY_ID'). $condition1 . $condition2. "
            group by p.picklist_id, f.facility_id, o.shipping_id
            order by p.picklist_id 
        ";
    } 
    $candidate = $db->getAll($sql_repeat_order);
    foreach ($candidate as $key => $item) {
        $goods_size = 0;
        $goods_name_list = $goods_number_list = $goods_id_list = array();
        $candidate[$key]['order_sn_list'] = array_combine(explode("\n", $item['order_id_list']), explode("\n", $item['order_sn_list']));
        $goods_name_list = explode("\n", $item['goods_name_list']);
        $goods_size = count($goods_name_list);
        $goods_number_list = explode("\n", $item['goods_number_list']);
        $goods_id_list = explode("\n", $item['goods_id_list']);
        $style_id_list = explode("\n", $item['style_id_list']);
        unset($candidate[$key]['goods_name_list']);
        for ($i = 0; $i < $goods_size; $i++) {
            $candidate[$key]['goods_name_list'][$i]['goods_name'] = $goods_name_list[$i];
            $candidate[$key]['goods_name_list'][$i]['goods_number'] = $goods_number_list[$i];
            $candidate[$key]['goods_name_list'][$i]['goods_id'] = $goods_id_list[$i];
            $candidate[$key]['goods_name_list'][$i]['style_id'] = $style_id_list[$i];
        }
        $candidate[$key]['shipment_id_list'] = explode("\n", $item['shipment_id_list']);
        unset($candidate[$key]['goods_id_list']);
        unset($candidate[$key]['style_id_list']);
        unset($candidate[$key]['goods_number_list']);
        unset($candidate[$key]['order_id_list']);
    }
} elseif ($act == "picking_list" || $act == "dispatch_list" ) {
    $condition = "";
    $facility_id = trim($_REQUEST['facility_id']);
    $shipping_id = trim($_REQUEST['shipping_id']);
    $order_list = explode(" ", $_REQUEST['order_list']);
    Helper_Array::removeEmpty($order_list);
    $order_list = implode(",", $order_list);
    $goods_list = explode(" ", $_REQUEST['goods_list']);
    Helper_Array::removeEmpty($goods_list);
    $shipment_list =  explode(" ", $_REQUEST['shipment_list']);
    Helper_Array::removeEmpty($shipment_list);
    foreach ($goods_list as $item) {
        list($goods_id, $style_id, $goods_number) = explode("_", $item);
        if (empty($condition)) {
            $condition .= " AND ((og.goods_id = '{$goods_id}' and og.style_id = '{$style_id}' and og.goods_number = '{$goods_number}')";
        } else {
            $condition .= " or (og.goods_id = '{$goods_id}' and og.style_id = '{$style_id}' and og.goods_number = '{$goods_number}') ";
        }
    }
    if (!empty($condition)) {
        $condition .= ")";
    }
    
    //检查发货单是否生成批拣任务、是否打印
    $sql = "
        select p.picklist_id
        from ecshop.ecs_order_goods og 
        left join romeo.picklist_item pi on pi.order_item_id = og.rec_id
        left join romeo.picklist p on pi.picklist_id =  p.picklist_id
        where og.order_id in (".$order_list.") ".$condition
    ;
    $check_result = $db->getOne($sql);
    if (empty($check_result)) {
        try {
            $handle=soap_get_client('PicklistService');
            $response=$handle->createPicklistFromShipments(array(
            'shipmentIds'=>$shipment_list,
            'createdByUserLogin'=>$_SESSION['admin_name'],
            ));
            $message = "操作成功，已生成批拣任务，批拣单号: ".$response->return;
            print json_encode (array("act" => $act, "picklist_id" => $response->return, "shipment_list" => $shipment_list));
            exit();
        }
        catch (Exception $e) {
            //iframe.src = picklist_print.php?act=picking_list&PICKLIST_ID={$response->return}&print=1
            $message = '操作失败，详细原因：'.$e->getMessage();
        }
    } else {
        print json_encode(array("act" => $act, "picklist_id" => $check_result, "shipment_list" => $shipment_list));
        exit();
    }
} elseif ($act == 'cancelle_list') {
    // 取消拣货单
    $picklist_id = trim($_REQUEST['picklist_id']);
    if ($picklist_id) {
        try {
            $handle=soap_get_client('PicklistService');
            $response=$handle->cancelPicklist(array(
                'picklistId' => $picklist_id,
                'lastModifiedByUserLogin' => $_SESSION['admin_name'],
            ));
            $message = "批拣号 ".$picklist_id . "已经取消";
            $url = "picklist_shipment.php?status={$filter['status']}&facility_id={$filter['facility_id']}&shipping_id={$filter['shipping_id']}&size={$filter['size']}";
            alert_back($message, $url);
        }
        catch (Exception $e) {
            $message = '操作失败，详细原因：'.$e->getMessage();
        }
    } else {
        $message = "批拣号为空";
    }
}

if ($candidate) {
    // 构造分页
    $total = count($candidate); // 总记录数
    $total_page = ceil($total/$page_size);  // 总页数
    $page = max(1, min($page, $total_page));
    $offset = ($page-1)*$page_size;
    $limit = $page_size;
    // 分页
    $candidate = array_splice($candidate, $offset, $limit);
    $pagination = new Pagination(
        $total, $page_size, $page, 'page', $url, null, $filter
    );

    $smarty->assign('total', $total);  // 总数
    $smarty->assign('candidate', $candidate);    // 当前页列表
    $smarty->assign('pagination', $pagination->get_simple_output());  // 分页    
}

if ($message) {
    $smarty->assign('message',$message);    
}
$smarty->assign('url', $url);
$smarty->assign('filter', $filter);                          // 筛选条件
$smarty->assign('page_size_list', $page_size_list);          // 每页显示数列表
$smarty->assign('facility_list', $facility_list);            // 用户所处仓库列表
$smarty->assign('shipping_type_list', $shipping_type_list);  // 配送方式列表 
$smarty->assign('status_list', $status_list);                // 各种状态列表 
$smarty->display('shipment/picklist_shipment.htm');

