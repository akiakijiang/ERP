<?php
define('IN_ECS', true);
require('includes/init.php');
require_once('function.php');
require_once('includes/lib_product_code.php');

$now = date("H:i:s", time());
if (!((strtotime($now) >= strtotime("8:00:00") && strtotime($now) <= strtotime("12:00:00")) || (strtotime($now) >= strtotime("19:00:00") && strtotime($now) <= strtotime("22:00:00")))) {
    //sys_msg('批量借机操作，目前只可以在早上8点到12点或者晚上7点到10点之间进行操作，谢谢配合。');
}
if ($_SESSION['party_id'] == 16 || is_jjshouse($_SESSION['party_id'])) {
    sys_msg('电教业务和海外业务，请勿在此页面操作。');
}
admin_priv('patch_order_goods');
$conditon = '';
$act = trim($_REQUEST['act']);
global $db;
if ( $act == 'search') {
    $goods_id = trim($_REQUEST['order_goods_id']);
    $style_id = trim($_REQUEST['order_style_id']) == "" ? 0 :trim($_REQUEST['order_style_id']);
    $facility_id = trim($_REQUEST['facility']);
    $start_time = trim($_REQUEST['start']);
    $end_time = trim($_REQUEST['ended']);
    $provider_id = trim($_REQUEST['original_provider_id']);
    
    $conditon .= " and og.goods_id = '{$goods_id}' and og.style_id = '{$style_id}' 
        and o.facility_id = '{$facility_id}' ";
    if ($start_time != '') {
        $conditon .= " and o.order_time >= '{$start_time}' "; 
    }
    if ($end_time != '') {
        $conditon .= " and o.order_time <= '{$end_time}' ";
    }
    if ($provider_id != '') {
        $conditon .= " and e.provider_id = '{$provider_id}' ";
    }
    $sql = "
    select o.order_id, o.order_sn, o.facility_id, og.goods_id, og.style_id, og.goods_number, e.erp_goods_sn,
        e.out_sn,e.in_sn, e.order_type, e.purchase_paid_amount, e.action_user, e.is_new,
        e.order_goods_id, e.provider_id, p.provider_name, 
        CONCAT(g.goods_name,' ',IF (gs.goods_color = '' or gs.goods_color is null , ifnull(s.color, ''), ifnull(gs.goods_color, ''))) as product_name,
        f.facility_name, g.goods_name, o.consignee, oa.attr_value as predict_return_time
    from ecshop.ecs_order_info o
    left join ecshop.ecs_order_goods og on o.order_id = og.order_id
    left join ecshop.ecs_oukoo_erp e on e.order_goods_id = og.rec_id
    left join ecshop.ecs_provider p on p.provider_id = e.provider_id
    left join ecshop.ecs_goods g on g.goods_id = og.goods_id
    left join ecshop.ecs_goods_style gs on gs.goods_id = og.goods_id and gs.style_id = og.style_id and gs.is_delete=0
    left join ecshop.ecs_style s on s.style_id = og.style_id
    left join romeo.facility f on f.facility_id = o.facility_id
    left join ecshop.order_attribute oa on oa.order_id = o.order_id and oa.attr_name = 'PREDICT_RETURN_TIME'
    where o.party_id = '{$_SESSION['party_id']}' and o.order_type_id = 'BORROW'
        " .$conditon. "
    group by og.rec_id
    having COUNT(*) = og.goods_number
    order by o.order_id asc
    ";
    $parameter = array('facility_id', 'goods_id', 'style_id', 'order_type', 'purchase_paid_amount', 'is_new', 'provider_id', 'action_user', 'consignee');

    $eg_list = $lists = array();
    $borrow_goods_list = $db->getAll($sql);
        foreach ($borrow_goods_list as $key => $item) {
            $goods['facility_id'] = $item['facility_id'];
            $goods['goods_id'] =  $item['goods_id'];
            $goods['style_id'] =  $item['style_id'];
            $goods['order_type'] = $item['order_type']; 
            $goods['purchase_paid_amount'] =  $item['purchase_paid_amount'];
            $goods['is_new'] =  $item['is_new'];
            $goods['provider_id'] =  $item['provider_id'];
            $goods['action_user'] = $item['action_user'];
            $goods['product_name'] = $item['product_name'];
            $goods['facility_name'] = $item['facility_name'];
            $goods['provider_name'] = $item['provider_name'];
            $goods['goods_name'] = $item['goods_name'];
            $goods['consignee'] = $item['consignee'];
            $goods['predict_return_time'] = $item['predict_return_time'];
            
            $result[] = $goods;
            if ($key == 0 ) {
                $lists[0]['goods_list'] = $goods;
                $lists[0]['order_list'][] = $item;
                $lists[0]['total_number'] += $item['goods_number'];
                continue;
            }
            $res = array_search($goods, $result);
            if ($res === false) {
                $size = count($lists);
                $lists[$size]['goods_list'] = $goods;
                $lists[$size]['order_list'][] = $item;
                $lists[$size]['total_number'] += $item['goods_number'];
            } else {
                $lists[$res]['goods_list'] = $goods;
                $lists[$res]['total_number'] += $item['goods_number'];
                $lists[$res]['order_list'][] = $item;
            }
        }
//        pp($lists);
        $smarty->assign('lists', $lists);
} elseif ($act == "return") {
    include_once(ROOT_PATH . "RomeoApi/lib_inventory.php");
    $num = 0;
    $order_id_list = $_REQUEST['order_id'];
    $order_sn_list = $_REQUEST['order_sn'];
    $goods_number_list = $_REQUEST['goods_number'];
    $erp_goods_sn_list = $_REQUEST['erp_goods_sn'];
    $out_sn_list = $_REQUEST['out_sn'];
    $order_goods_id_list = $_REQUEST['order_goods_id_list'];
 
    
    $goods_name = trim($_REQUEST['goods_name']);
    $order_type = $_REQUEST['order_type'];
    $purchase_paid_amount = $_REQUEST['purchase_paid_amount'];
    $is_new = $_REQUEST['is_new'];
    $provider_id = $_REQUEST['provider_id'];
    $facility_id = $_REQUEST['facility_id'];
    $goods_id = $_REQUEST['goods_id'];
    $style_id = $_REQUEST['style_id'];
    
    $return_total_number = (int)$_REQUEST['return_total_number'];
    $i = 0;
    foreach ($order_goods_id_list as $key=>$order_goods_id){
        if ($i >= $return_total_number) {
            break;
        }
        $sql = "SELECT COUNT(*)
            FROM {$ecs->table('oukoo_erp')}
            WHERE order_goods_id = '{$order_goods_id}'";
        $count = $db->getOne($sql);
        
        if ($count == 1) { // 如果为1说明没有入库，为2说明已经入库
            $erp_goods_sn = $erp_goods_sn_list[$key];
            $is_new_db = 'NONE';
            if ($is_new == 'NEW') {
                $is_new_db = 'NEW';
                $toStatusId = "INV_STTS_AVAILABLE";
            } else {
                $sql = "SELECT is_new FROM {$ecs->table('oukoo_erp')} WHERE order_goods_id = '{$order_goods_id}' AND out_sn != '' ORDER BY erp_id ";
                $origin_is_new = $db->getOne($sql);
                if($origin_is_new == 'NEW') {
                    $message .= "<br/>订单号：" .$order_sn_list[$key] ." 串号： " .$erp_goods_sn ." 换货出去的机器是全新的，换货入库的也必须是新的。";
                    continue;
                }
                $is_new_db = 'SECOND_HAND';
                $toStatusId = "INV_STTS_USED";
            }
            $storage_list = getStorageGoods(" AND erp_goods_sn = '{$erp_goods_sn}'");
            $temp_erp = $storage_list[0];
            if ($temp_erp == null) {
                //入库
                $sql = "INSERT INTO {$ecs->table('oukoo_erp')}
                    (order_goods_id, erp_goods_sn, in_sn, action_user, order_type, purchase_paid_amount, provider_id, is_new, in_time, facility_id) 
                    VALUES ('{$order_goods_id}', '{$erp_goods_sn}', '{$order_sn_list[$key]}-1', '{$_SESSION['admin_name']}', 
                     '{$order_type}', '{$purchase_paid_amount}', '{$provider_id}', '{$is_new_db}', NOW(), '{$facility_id}')";
                $db->query($sql); 
                add_erp_log("{$order_sn_list[$key]}-1"); 
                //            romeo code:
                //            入库（供应商到正式库）
                $info = '归还机器入库成功';
                $invTranType = "ITT_BORROW_RET";
                //此处order_id为-gt订单的order_id
     
                createAcceptInventoryTransaction("ITT_BORROW_RET", array('goods_id'=>$goods_id, 'style_id'=>$style_id), 
                    1, $erp_goods_sn, $order_type, $order_id_list[$key], '', $toStatusId, $purchase_paid_amount,  $order_goods_id, $facility_id, $provider_id);

                if (isset($_REQUEST['is_print_barcode']) && $_REQUEST['is_print_barcode'] == 1) {
                    $sql = "SELECT goods_party_id FROM ecs_goods WHERE goods_id = '{$goods_id}' ";
                    $goods_party_id = $db->getOne($sql);

                    // 如果是鞋子的，那么就去读取internal_sku
                    if ($goods_party_id == PARTY_OUKU_SHOES) {
                        $sql = "SELECT internal_sku FROM ecs_goods_style
                            WHERE goods_id = '{$goods_id}' AND style_id = '{$style_id}' ";
                        $insku = $db->getOne($sql);
                        // 为方便查看，需要将中间加上 -
                        $goods_name = substr($insku, 0, 6).'-'.substr($insku, 6, 6).'-'.substr($insku, 12);
                    }

                    $code = encode_goods_id($goods_id, $style_id);
                    $printer_id = $_REQUEST['printer_id'];
                    print_product_code($order_id_list[$key], $code, 1, $goods_id, $printer_id, $goods_name);
                }
                $i += $goods_number_list[$key];
                $return .= "<br/>订单号：" .$order_sn_list[$key]. " 串号： " . $out_sn_list[$key] . "入库成功。";
            } else {
                continue;
            }
        } else {
            $message_error .= "<br/>订单号：" .$order_sn_list[$key] ." 串号： " .$erp_goods_sn ." 已经入库，不可以重复操作。";
        }
    }
    
    $smarty->assign('message', "此次还机数量：" . $i . $return . $message .
        "<p style=\"color:red;\">" . $message_error . "</p>");
}

$smarty->assign('facilitys', get_available_facility()); 
$smarty->display("oukooext/patch_borrow_goods_select.htm");