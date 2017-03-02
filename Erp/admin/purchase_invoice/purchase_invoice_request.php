<?php
/**
 * 开票清单列表新页面函数库
 */

/**
 *获取开票清单ID最后面的三位序号
 */
function get_sequence_number($date){
    global $db;
    $sql = "select last_update_stamp from romeo.sn_sequence";
    $res = $db->getAll($sql);
    $time = date('Ymd',strtotime($res[0]['last_update_stamp']));
    $sql_sn="select sequence_number from romeo.sn_sequence";
    $res_sn = $db->getAll($sql_sn);
    $number = $res_sn[0]['sequence_number'];
    if(strlen($number) == 1){
        $number = '00'.$number;
    }elseif (strlen($number) == 2 ){
        $number = '0'.$number;
    }
    $first_number = '001';
    if($date == $time){
        $sql_update = "update romeo.sn_sequence set last_update_stamp = now(),last_update_tx_stamp = now(),sequence_number = sequence_number +1";
        $db->query($sql_update);
        return $number;
    }else{
        $sql_update = "update romeo.sn_sequence set last_update_stamp = now(),last_update_tx_stamp = now(),sequence_number = '002'";
        $db->query($sql_update);
        return $first_number;    
    }
}

/**
 *获取已开票金额
 */
function get_match_cost($request_id){
    global $db;
    $sql="select piri.quantity,piri.unit_cost,piri.return_inventory_transaction_id,piri.return_amount 
        from romeo.purchase_invoice_item_match piim
        inner join romeo.purchase_invoice_item pii on pii.PURCHASE_INVOICE_ITEM_ID = piim.PURCHASE_INVOICE_ITEM_ID
        inner join romeo.purchase_invoice_request_item piri on piri.PURCHASE_INVOICE_REQUEST_ITEM_ID = piim.PURCHASE_INVOICE_REQUEST_ITEM_ID
        inner join romeo.purchase_invoice pi on pi.PURCHASE_INVOICE_ID = pii.PURCHASE_INVOICE_ID
        inner join romeo.purchase_invoice_request pir on pir.PURCHASE_INVOICE_REQUEST_ID = piri.PURCHASE_INVOICE_REQUEST_ID 
        where pir.purchase_invoice_request_id = '{$request_id}'
        ";
    $match_list = $db->getAll($sql);
    $match_cost = 0;
    foreach ($match_list as $match) {
        if ($match['return_inventory_transaction_id']) {
            $match_cost -= $match['return_amount'];
        }
        $match_cost += $match['unit_cost'] * $match['quantity'];
    }
    return $match_cost;
}

/**
 *对查出结果增加序号
 */
function add_iteration($list){
    for($i = 1;$i <= count($list);$i++ ){
        $list[$i-1]['iteration'] = $i;
    }
    return $list;
}
?>