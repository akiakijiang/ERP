<?php

/**
 * 获得cps返现规则
 *
 * @param unknown_type $party_id
 * @return unknown
 */
function get_cps_rebate_rules($party_id, $is_delete) {
    global $slave_db;
    $sql_condition = "";
    if ($party_id) {
        $sql_condition .= " AND cr.party_id = '{$party_id}' ";
    }
    if ($is_delete) {
        $sql_condition .= " AND cr.is_delete = '{$is_delete}' ";
    }
    $sql = "SELECT cr.cps_name, cr.startdate, cr.enddate, crd.rebate_type, crd.rebate_detail,
                cr.cps_rebate_rule_id, crd.cps_rebate_rule_detail_id, cr.is_delete, cr.party_id, crd.calc_type
            FROM cps_rebate_rule cr
             LEFT JOIN cps_rebate_rule_detail crd ON cr.cps_rebate_rule_id = crd.cps_rebate_rule_id
            WHERE 1 $sql_condition 
            ORDER BY cr.party_id, cr.cps_name, cr.is_delete, cr.startdate ";
    $fields_value = array();
    $ref = array();
    $slave_db->getAllRefby($sql, array('cps_name', 'cps_rebate_rule_id'), $fields_value, $ref);
    return $ref;
}

/**
 * 获得返现总额
 * cps_rebate_by_amount: 总价返现
 * cps_rebate_by_goods: 商品分类返现
 *
 * @param unknown_type $order
 * @return unknown
 */
function get_cps_rebate($order) {
    static $cps_rebate_rules;
    if (is_null($cps_rebate_rules) || 
        (is_array($cps_rebate_rules) && !key_exists($order['party_id'], $cps_rebate_rules))) {
        $is_delete = 'N';
        $tmp = get_cps_rebate_rules($order['party_id'], $is_delete);
        $cps_rebate_rules[$order['party_id']] = $tmp['cps_name'];
    }
    $cps_rebate_rule = get_cps_rebate_rule($order, $cps_rebate_rules[$order['party_id']]);
    if ($cps_rebate_rule == null || empty($cps_rebate_rule)) {
        return $order;
    }
    // 订单返现基数：商品总额 - 红包 （$bonus为负值）
    $order_amount = $order['goods_amount'] + $order['bonus'];
    if ($order_amount < 0) {
        $order_amount = 0;
    }
    // 通过返现基数获得rebate
    $order['cps_rebate_by_amount'] = get_cps_rebate_by_amount($order_amount, $cps_rebate_rule);
    $order['cps_rebate_by_goods'] = 0;
    // 额外规则
    $order['extra'] = array();
    // 通过订单中商品获得rebate
    foreach ($order['order_goods'] as $key => $order_goods) {
        // 取得商品价格
        $order['order_goods'][$key]['goods_amount'] =
            $order_goods['goods_price'] * $order_goods['goods_number'];
        // 平均扣除红包
        $order['order_goods'][$key]['cps_goods_amount'] = $order['order_goods'][$key]['goods_amount']
            + $order['order_goods'][$key]['goods_amount'] * $order['bonus'] / $order['goods_amount'];
        // 根据分类获得rebate
        $order['order_goods'][$key]['cps_rebate_by_goods'] =
            get_cps_rebate_by_order_goods($order['order_goods'][$key], $cps_rebate_rule);
        // rebate总计
        $order['cps_rebate_by_goods'] += $order['order_goods'][$key]['cps_rebate_by_goods'];
    }
    // 额外规则
    $order['extra'] = get_extra_cps_rebate($order);
    $order['cps_amount'] = $order['cps_rebate_by_goods'] + $order['cps_rebate_by_amount'] +
        $order['extra']['cps_rebate_by_extra'] ;
    return $order;
}
/**
 * 获得订单可以使用的返现规则
 *
 * @param unknown_type $order
 * @param unknown_type $cps_rebate_rules
 * @return unknown
 */
function get_cps_rebate_rule($order, $cps_rebate_rules) {
    if ($cps_rebate_rules[$order['cps_code']]) {
        $cps_rebate_rule = array();
        foreach ($cps_rebate_rules[$order['cps_code']] as $key => $rebate_rule) {
            if ($rebate_rule['startdate'] <= $order['order_time']
                && $rebate_rule['enddate'] >= $order['order_time']) {
                $cps_rebate_rule[] = $rebate_rule;
            }
        }
        return $cps_rebate_rule;
    } else {
        return null;
    }
}

/**
 * 根据返现基数金额取得返现额
 *
 * @param unknown_type $order_amount
 * @param unknown_type $cps_rebate_rule
 * @return unknown_type $rebate
 */
function get_cps_rebate_by_amount($order_amount, $cps_rebate_rule) {
    if ($order_amount <= 0) {
        return 0;
    }
    foreach ($cps_rebate_rule as $rule) {
        if ($rule['rebate_type'] == 'by_amount') {
            $rebate_bounds = unserialize($rule['rebate_detail']);
            $default = 0;
            foreach ($rebate_bounds as $key => $rebate_bound) {
                if ($rebate_bound[0] <= $order_amount && $order_amount < $rebate_bounds[$key+1][0]) {
                    if ($rule['calc_type'] == 'add') {
                        return $rebate_bound[1];
                    } else if ($rule['calc_type'] == 'multiply') {
                        return $rebate_bound[1] * $order_amount;
                    }
                }
                $default = $rebate_bound[1];
            }
            if ($rule['calc_type'] == 'add') {
    	        return $default;
    	    } else if ($rule['calc_type'] == 'multiply') {
    	        return $default * $order_amount;
    	    }
        }
    }
    return 0;
}

/**
 * 根据商品分类获得返现
 *
 * @param unknown_type $order_goods
 * @param unknown_type $cps_rebate_rule
 * @return unknown
 */
function get_cps_rebate_by_order_goods($order_goods, $cps_rebate_rule) {
    foreach ($cps_rebate_rule as $rule) {
        if ($rule['rebate_type'] == 'by_order_goods') {
            $rebate_bounds = unserialize($rule['rebate_detail']);
            $default = 0;
            foreach ($rebate_bounds as $rebate_bound) {
            	if ($rebate_bound[0] == $order_goods['cat_name']) {
            	    if ($rule['calc_type'] == 'add') {
            	        return $rebate_bound[1];
            	    } else if ($rule['calc_type'] == 'multiply') {
            	        return $rebate_bound[1] * $order_goods['cps_goods_amount'];
            	    }
            	}
            	if ($rebate_bound[0] == 'default') {
            	    $default = $rebate_bound[1];
            	}
            }
            if ($rule['calc_type'] == 'add') {
    	        return $default;
    	    } else if ($rule['calc_type'] == 'multiply') {
    	        return $default * $order_goods['cps_goods_amount'];
    	    }
        }
    }
    return 0;
}

function get_extra_cps_rebate($order) {
    $extra = array();
    if ($order['cps_code'] == 'netease') {
        $extra['bluray_number'] = 0;
        foreach ($order['order_goods'] as $key => $order_goods) {
            // 蓝光
            if ($order_goods['cat_id'] == 1502) {
                $extra['bluray_number'] += $order_goods['goods_number'];
            }
        }
        $extra['cps_rebate_by_extra'] = $extra['bluray_number'] * 20;
    }
    return $extra;
}
