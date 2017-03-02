<?php
/**
 * 分销预存款补扣
 * 
 * 参数说明
 * type: 值为auto的时候把所有的运费未扣预存款的订单进行补扣
 * order_sn: type不为auto的时候针对order_sn进行补扣
 * repair: 值为1时进行数据库操作，否则只显示金额
 *
 */
define('IN_ECS', true);
require_once('../includes/init.php');
require_once(ROOT_PATH . 'admin/distribution.inc.php');
admin_priv('distribution_delivery');
require_once(ROOT_PATH . 'admin/function.php');
require_once(ROOT_PATH . 'admin/includes/lib_order.php');
require_once(ROOT_PATH . 'admin/includes/lib_common.php');
require_once(ROOT_PATH. 'RomeoApi/lib_inventory.php');
require_once(ROOT_PATH. 'RomeoApi/lib_payment.php');
require_once(ROOT_PATH. 'includes/helper/array.php');
require_once(ROOT_PATH. 'includes/debug/lib_log.php');


/**
 * 取得电教调价金额
 */
function get_edu_adjust($order) {
    global $db;

    static $adjust_order_list = array();

    if (isset($adjust_order_list[$order['order_id']])) {
        return $adjust_order_list[$order['order_id']];
    }

    // 在此时间点之前的订单不计算调价
    if (strtotime($order['order_time']) < strtotime('2010-08-31 04:00:00')) {
        return $adjust_order_list[$order['order_id']] = 0;
    }

    // 该淘宝订单已经扣过了
    $consumed = $db->getOne("SELECT 1 FROM distribution_order_adjustment_log WHERE taobao_order_sn = '{$order['taobao_order_sn']}' LIMIT 1");
    if ($consumed) {
        return $adjust_order_list[$order['order_id']] = 0;
    }

    // 已经计算过调价金额
    $adjust = $db->getOne("SELECT SUM(amount) FROM distribution_order_adjustment WHERE order_id='{$order['order_id']}' AND status='INIT'");
    if ($adjust) {
        return $adjust_order_list[$order['order_id']] = $adjust;
    }

    // 调价金额
    $adjust = 0;
    $datetime = date('Y-m-d H:i:s');

    // 计算运费的调价金额
    // 查询原来导入订单的套餐关系
    $sql = "
        SELECT
            oi.goods_code, oi.goods_number 
        FROM
            distribution_import_order_info oh
            LEFT JOIN distribution_import_order_goods oi ON oi.taobao_order_sn = oh.taobao_order_sn AND oi.batch_no = oh.batch_no
        WHERE 
            oh.deleted = 'N' AND oh.imported ='Y' AND oh.refer_order_sn = '{$order['order_sn']}'
    ";
    $imported_order_goods = $db->getAll($sql);
    if ($imported_order_goods) {
        // 查询商品调价的SQL
        $sql1 = "
            SELECT adjust_fee 
            FROM distribution_sale_price 
            WHERE (distributor_id = 0 or distributor_id = ". $order['distributor_id'] .") AND goods_id = '%d' AND style_id = '%d' AND '{$order['order_time']}' >= valid_from
            ORDER BY distributor_id DESC, valid_from DESC    
        ";

        // 查询电教商品的SQL, style_id都为0
        $sql2 = "
            SELECT g.goods_id, g.goods_party_id, g.goods_name 
            FROM ecs_goods AS g  
            WHERE g.goods_id = %d
        ";

        foreach ($imported_order_goods as $goods) {
            $post_fee = $adjust_fee = $amount = 0;

            // 套餐
            if (strpos($goods['goods_code'], 'TC-') !== false) {
                $group = distribution_get_group_goods(NULL, $goods['goods_code'], $order['order_time']);
                if ($group) {
                    // 运费
                    $postage = distribution_get_postage($goods['goods_code'], $order['province'], $order['shipping_id']);
                    if ($postage) {
                        if ($goods['goods_number'] == 1) {
                            $post_fee = $postage['post_fee'] * $goods['goods_number'];
                        }
                        else if ($goods['goods_number'] > 1) {
                            $post_fee = $postage['post_fee'] + (($goods['goods_number'] -1) * $postage['extra_fee']);
                        }
                        $amount += $post_fee;
                        $db->query("INSERT INTO distribution_order_adjustment (order_id, group_id, group_name, num, amount, type, status, created_by_user_login, created) VALUES ('{$order['order_id']}', '{$group['group_id']}', '{$group['name']}', '{$goods['goods_number']}', '{$post_fee}', 'SHIPPING_ADJUSTMENT', 'INIT', '{$_SESSION['admin_name']}', '{$datetime}')");
                    }

                    // 调价
                    foreach ($group['item_list'] as $g) {
                        $adjust_fee = $db->getOne(sprintf($sql1, $g['goods_id'], $g['style_id']));
                        if ($adjust_fee && $adjust_fee > 0) {
                            $num = $g['goods_number'] * $goods['goods_number'];
                            $adjust_fee = $adjust_fee * $num;
                            $amount += $adjust_fee;
                            $db->query("INSERT INTO distribution_order_adjustment (order_id, goods_id, style_id, goods_name, group_id, group_name, num, amount, type, status, created_by_user_login, created) VALUES ('{$order['order_id']}', '{$g['goods_id']}', '{$g['style_id']}', '{$g['goods_name']}', '{$group['group_id']}', '{$group['name']}', '{$num}', '{$adjust_fee}', 'GOODS_ADJUSTMENT', 'INIT', '{$_SESSION['admin_name']}', '{$datetime}')");
                        }
                    }
                }
            }
            // 独立的商品
            else if (is_numeric($goods['goods_code'])) {
                $g = $db->getRow(sprintf($sql2,$goods['goods_code']), true);
                if ($g) {
                    $g['style_id']=0;
                    // 运费
                    $postage = distribution_get_postage($goods['goods_code'], $order['province'], $order['shipping_id']);
                    if ($postage) {
                        if ($goods['goods_number'] == 1) {
                            $post_fee = $postage['post_fee'] * $goods['goods_number'];
                        }
                        else if ($goods['goods_number'] > 1) {
                            $post_fee = $postage['post_fee'] + (($goods['goods_number'] -1) * $postage['extra_fee']);
                        }
                        $amount += $post_fee;
                        $db->query("INSERT INTO distribution_order_adjustment (order_id, goods_id, style_id, goods_name, num, amount, type, status, created_by_user_login, created) VALUES ('{$order['order_id']}', '{$g['goods_id']}', '{$g['style_id']}', '{$g['goods_name']}', '{$goods['goods_number']}', '{$post_fee}', 'SHIPPING_ADJUSTMENT', 'INIT', '{$_SESSION['admin_name']}', '{$datetime}')");
                    }

                    // 调价
                    $adjust_fee = $db->getOne(sprintf($sql1, $g['goods_id'], $g['style_id']));
                    if ($adjust_fee && $adjust_fee > 0) {
                        $adjust_fee = $adjust_fee * $goods['goods_number'];
                        $amount += $adjust_fee;
                        $db->query("INSERT INTO distribution_order_adjustment (order_id, goods_id, style_id, goods_name, num, amount, type, status, created_by_user_login, created) VALUES ('{$order['order_id']}', '{$g['goods_id']}', '{$g['style_id']}', '{$g['goods_name']}', '{$goods['goods_number']}', '{$adjust_fee}', 'GOODS_ADJUSTMENT', 'INIT', '{$_SESSION['admin_name']}', '{$datetime}')");
                    }
                }
            }

            $adjust += $amount;
        }
    }

    return $adjust_order_list[$order['order_id']] = $adjust;
}

$type = trim($_REQUEST['type']);
$repair = trim($_REQUEST['repair']);

if ($type == 'auto') {
    $sql = "
select distinct o.order_sn, o.order_id, o.order_time
from romeo.inventory_item i
inner join romeo.inventory_item_detail id on i.inventory_item_id = id.inventory_item_id
inner join ecshop.ecs_order_info o on CONVERT(o.order_id using utf8) = id.order_id
left join ecshop.distribution_order_adjustment a on o.order_id = a.order_id and a.type = 'SHIPPING_ADJUSTMENT'
left join ecshop.distributor d on o.distributor_id = d.distributor_id
left join ecshop.main_distributor md on d.main_distributor_id = md.main_distributor_id
where
i.status_id = 'INV_STTS_AVAILABLE'
and id.QUANTITY_ON_HAND_DIFF < 0
and o.order_type_id = 'SALE'
and o. shipping_status in (1,2)
and o.party_id in (16,65548)
and md.type = 'fenxiao'
and md.main_distributor_id != 25
and o.order_time > '2011-07-01'
and (a.order_id is null or a. status = 'INIT')
order by o.order_time
    ";
    $order_sn_list = $db->getCol($sql);
    pp($order_sn_list);
} else {
    $order_sn_list = array($_REQUEST['order_sn']);
}

foreach ($order_sn_list as $order_sn) {
    $order = get_core_order_info($order_sn);
    // 取得订单的分销商信息
    if ($order['distributor_id'] > 0) {
        $sql = "SELECT * FROM distributor WHERE distributor_id = '{$order['distributor_id']}'";
        $distributor = $db->getRow($sql, true);
    }
    // 取得主分销商信息
    if ($distributor) {
        $main_distributor = $db->getRow("SELECT * FROM main_distributor WHERE main_distributor_id = '{$distributor['main_distributor_id']}' LIMIT 1");
    }
    $adjust = get_edu_adjust($order);
    pp("订单号: {$order_sn}", "调整金额: {$adjust}", $prepay_consume_result);
    if ($adjust > 0 && $repair == 1) {
        $note = "订单调价,ERP订单号 {$order['order_sn']},淘宝订单号{$order['taobao_order_sn']}";
        $prepay_consume_result = prepay_consume(
        $main_distributor['main_distributor_id'],  // 合作伙伴ID
        $order['party_id'],                        // 组织
        'DISTRIBUTOR',                             // 账户类型
        $adjust,                                   // 使用金额
        NULL,                                      // 账单
        $_SESSION['admin_name'],
        $note,                                     // 备注
        NULL                                       // 支票号
        );
        if ($prepay_consume_result == 0) {
            pp("使用预付款失败了，CODE: ". $prepay_consume_result);
            QLog::log("使用预付款失败了，CODE: ". $prepay_consume_result, QLog::ERR);
            $smarty->assign('使用预付款失败');
        } else if ($prepay_consume_result == -1) {
            pp("预付款账户不存在呢，CODE: ". $prepay_consume_result);
            QLog::log("预付款账户不存在呢，CODE: ". $prepay_consume_result, QLog::ERR);
            $smarty->assign('预付款账户不存在');
        } else {
            // 标识该淘宝订单已经扣过预付款
            $res=$db->query("INSERT INTO distribution_order_adjustment_log (taobao_order_sn,prepayment_transaction_id,status) VALUES ('{$order['taobao_order_sn']}','{$prepay_consume_result}','CONSUMED')", 'SILENT');
            $db->query("UPDATE distribution_order_adjustment SET status = 'CONSUMED', prepayment_transaction_id = '{$prepay_consume_result}' WHERE order_id='{$order['order_id']}' AND status='INIT'");
            if(!$res){
                pp("该淘宝订单号（".$order['taobao_order_sn']."）重复抵扣预付款");
                QLog::log("该淘宝订单号（".$order['taobao_order_sn']."）重复抵扣预付款", QLog::ERR);
            }
        }
    }
}