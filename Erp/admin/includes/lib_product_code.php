<?php

/**
 * 打印产品的编码
 *
 * @param int $order_id
 * @param string $code
 * @param int $amount
 * @return boolean
 */
function print_product_code($order_id, $code, $amount, $goods_id, $printer_id = '', $label = '', $party_id = 0) {
    global $db;
    if ($amount < 0) return false;
    
    if (!$party_id || $goods_id) {
        $sql = "SELECT goods_party_id FROM ecs_goods WHERE goods_id = '{$goods_id}' ";
        $party_id = $db->getOne($sql);
    }

    $label = mb_substr($label, 0, 30, "utf-8");    
    $sql_insert = array();
    while ($amount > 0) {
        $temp = $amount;
        if ($amount > 100) {
            $temp = 100;
        }
        
        
        $amount -= $temp;        
        $sql_insert[] = "('{$order_id}', '{$code}', '{$temp}', '{$label}', '{$party_id}', '{$printer_id}')";
    }
    
    $_SESSION['latest_printer_id'] = $printer_id;
    
    $sql = "INSERT INTO print_serial_number (order_id, code, amount, label, party_id, printer_id)
            VALUES ". implode(',', $sql_insert);
    $db->query($sql);
    return true;
}


/**
 * 根据组织返回打印机列表
 *
 * @param int $party_id
 * @return array
 */
function get_serial_printers($party_id = null) {
    global $_CFG;
    
    if ($party_id === null) {
        $party_id = $_SESSION['party_id'];
    }
    $all_printers = @unserialize($_CFG['printer_config']);
    
    if ($all_printers[$party_id]) {
        return $all_printers[$party_id];
    } else {
        return array( '' => '默认打印机');
    }
}