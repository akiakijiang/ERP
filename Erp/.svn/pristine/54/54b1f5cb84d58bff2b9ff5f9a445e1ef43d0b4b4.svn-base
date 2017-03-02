<?php

/**
 * 返回当前激活的所有支付方式
 * pay_id 主键
 * pay_name 系统内的支付方式名词
 * acct_name 财务认可的支付方式名词
 */


function get_enabled_payment_methods() {
    global $db, $ecs;
    
    $sql = "SELECT pay_id, pay_name, IF(enabled=1, pay_name, CONCAT(acct_name, ' (已挂起)')) AS acct_name FROM {$ecs->table('payment')} WHERE enabled = 1 OR enabled_backend = 'Y' order by pay_id";
    $payments = $db->getAll($sql);
    return $payments;
}

?>