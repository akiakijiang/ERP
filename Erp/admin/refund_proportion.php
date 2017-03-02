<?php
define ( 'IN_ECS', true );
require_once ('includes/init.php');

admin_priv ( 'refund_proportion' );

$act = $_REQUEST['act'] != '' ? $_REQUEST['act'] : 'list';

if($act == 'list'){
    
    $start_time = strtotime(trim($_POST['startCalendar']));
    $end_time = strtotime(trim($_POST['endCalendar']));
    $days = ($end_time - $start_time)/(3600*24);
    
    if($start_time == '' || $end_time == ''){
        $res = get_data();
    }
    else {
        $res = get_data(trim($_POST['startCalendar']),$days);
    }

    $smarty->assign('res',$res);
    $smarty->display('oukooext/refund_proportion.htm');
}

/**
 * 获取一段时间内每天的退款订单量，退款订单金额的值，以及比例
 * 
 * @param date $time 开始时间，默认为当天的前7天的那一天
 * @param　int $days 统计天数
 * 
 * */

function get_data($time = 'null' , $days = 7){
    
    if($time == 'null'){
        $start_time = date("Y-m-d",time() - 3600 * 24 * $days);
    }
    else {
        $start_time = $time;
    }
    
    $res = array();
    
    for ($i = 0 ; $i < $days; $i++){
        
        $start_time_delivery = strtotime($start_time) + 3600 * 24 * $i;
        $end_time_delivery = $start_time_delivery + 3600 * 24;
        
        $start_time_refund = date("Y-m-d H:i:s",$start_time_delivery);
        $end_time_refund = date("Y-m-d H:i:s",$end_time_delivery);
        
        //execute_date表示退款成功的时间
        $refund_sql = "
            select total_amount,order_id,currency
            from `romeo`.`refund` 
            where EXECUTE_DATE > '$start_time_refund' and EXECUTE_DATE < '$end_time_refund'
            and status = 'RFND_STTS_EXECUTED' and ". party_sql ('PARTY_ID');
        
        //shipping_time表示发货时间
        $delivery_sql = "
            SELECT o.order_id, o.order_amount ,o.currency
            FROM `ecshop`.`ecs_order_info` AS o
            WHERE o.shipping_status = 1 and ". party_sql ('o.PARTY_ID')." and 
            o.order_type_id = 'SALE' and
            o.shipping_time > '$start_time_delivery' and o.shipping_time < '$end_time_delivery' ";

        //截止当前操作退款的订单
        $delivery_refund_sql = "
            SELECT r.total_amount, r.order_id, r.currency
            FROM romeo.refund r 
            LEFT JOIN ecshop.order_relation or1 ON or1.order_id = r.order_id 
            LEFT JOIN ecshop.ecs_order_info o ON o.order_id = or1.root_order_id
            WHERE  o.shipping_status = 1 and ". party_sql ('o.PARTY_ID')." and o.order_type_id = 'SALE' and r.status = 'RFND_STTS_EXECUTED' 
                and o.shipping_time > '$start_time_delivery' and o.shipping_time < '$end_time_delivery' 
                and r.EXECUTE_DATE < '" . date("Y-m-d H:i:s", time()) . "'";
        //截止当前退款订单中退款原因为JJs供应商质量问题退款的订单
        $quality_problem_sql = "
            SELECT r.total_amount, r.order_id, r.currency FROM romeo.refund r
            LEFT JOIN ecshop.order_relation or1 ON or1.order_id = r.order_id
            LEFT JOIN ecshop.ecs_order_info o ON o.order_id = or1.root_order_id
            LEFT JOIN romeo.refund_detail rd ON r.REFUND_ID = rd.REFUND_ID
            LEFT JOIN romeo.refund_detail_reason rdr ON rd.REFUND_DETAIL_REASON_ID = rdr.REFUND_DETAIL_REASON_ID
            WHERE  o.shipping_status = 1 and o.order_type_id = 'SALE' and ". party_sql ('o.PARTY_ID')."  
                and r.status = 'RFND_STTS_EXECUTED' and rdr.REASON = 'JJs供应商质量问题退款'
                and o.shipping_time > '$start_time_delivery' and o.shipping_time < '$end_time_delivery' 
                and r.EXECUTE_DATE < '" . date("Y-m-d H:i:s", time()) . "'";

        $refund_list = $GLOBALS ['db']->getAll($refund_sql);
        $delivery_list = $GLOBALS ['db']->getAll($delivery_sql);
        $delivery_refund_list = $GLOBALS['db']->getAll($delivery_refund_sql);
        $quality_problem_list = $GLOBALS['db']->getAll($quality_problem_sql);
        
        $delivery_price_amount = 0;
        $refund_price_amount = 0;
        $res[$i]['delivery_refund_price_amount'] = $res[$i]['quality_problem_price_amount'] = 0;
        foreach ($delivery_list as $item){
            $delivery_price_amount += get_currency($item['order_amount'],$item['currency']);
        }
        foreach ($refund_list as $refund){
            $refund_price_amount += get_currency($refund['total_amount'],$refund['currency']);
        }
        //当日操作发货订单中的退款订单总金额
        foreach ($delivery_refund_list as $item) {
            $res[$i]['delivery_refund_price_amount'] += get_currency($item['total_amount'], $item['currency']);
        }
        //当日操作发货订单中的退款订单原因为JJs供应商质量问题退款的订单总金额
        foreach ($quality_problem_list as $item) {
            $res[$i]['quality_problem_price_amount'] += get_currency($item['total_amount'], $item['currency']);
        }

        //数据组装
        $res[$i]['date'] = date("Y-m-d",$start_time_delivery);
        $res[$i]['delivery_amount'] = count($delivery_list);
        $res[$i]['refund_amount'] = count($refund_list);
        $res[$i]['delivery_refund_amount'] = count($delivery_refund_list);
        $res[$i]['quality_problem_amount'] = count($quality_problem_list);
        $res[$i]['delivery_price_amount'] = $delivery_price_amount;
        $res[$i]['refund_price_amount'] = $refund_price_amount;
        
        if($res[$i]['delivery_amount'] == 0){
            $res[$i]['refund_proportion'] = '发货订单数为0';
            $res[$i]['delivery_refund_proportion'] = $res[$i]['quality_problem_proportion'] = 0;
        }
        else{
            $num = number_format($res[$i]['refund_amount']/$res[$i]['delivery_amount'] * 100,3);
            $res[$i]['refund_proportion'] = $num."%";
            //订单比率
            $res[$i]['delivery_refund_proportion'] =
                number_format($res[$i]['delivery_refund_amount'] / $res[$i]['delivery_amount'] * 100, 3) . "%";
            $res[$i]['quality_problem_proportion'] =
                number_format($res[$i]['quality_problem_amount'] / $res[$i]['delivery_amount'] * 100, 3) . "%";
        }
        if($delivery_price_amount == 0){
            $res[$i]['refund_price_proportion'] = '发货金额为0';
            $res[$i]['delivery_refund_price_proportion'] = $res[$i]['quality_problem_price_proportion'] = 0;
        }
        else{
            $n = number_format($refund_price_amount / $delivery_price_amount * 100 , 3);
            $res[$i]['refund_price_proportion'] = $n."%";
            //金额比率
            $res[$i]['delivery_refund_price_proportion'] =
                number_format($res[$i]['delivery_refund_price_amount'] / $delivery_price_amount * 100 , 3) . "%";
            $res[$i]['quality_problem_price_proportion'] =
                number_format($res[$i]['quality_problem_price_amount'] / $delivery_price_amount * 100 , 3) . "%";
        }
    }
    return $res;
}
/**
 * 根据录入的金额，原始币种，转换的币种，返回转换过的金额(如果转换率查不到，将返回录入的金额)
 * 
 * @param int $amount 录入金额
 * @param　strint $from_currency　原始币种
 * @param　strint $to_currency　要转换为的币种
 * 
 * */
function get_currency($amount = 0 ,$from_currency = 'USD', $to_currency = 'RMB'){
    
    $exchange_from_currency_sql = "
        select CURRENCY_CONVERSION_RATE
        from `romeo`.`currency_conversion` 
        where CANCELLATION_FLAG = 'N' and TO_CURRENCY_CODE = '$from_currency' 
        and FROM_CURRENCY_CODE = 'USD' 
        order by LAST_UPDATE_STAMP desc ";
    
    $exchange_to_currency_sql = "
        select CURRENCY_CONVERSION_RATE
        from `romeo`.`currency_conversion` 
        where CANCELLATION_FLAG = 'N' and TO_CURRENCY_CODE = '$to_currency' 
        and FROM_CURRENCY_CODE = 'USD' 
        order by LAST_UPDATE_STAMP desc ";
    
    if($from_currency == ''){
        return $amount;
    }
    
    if($from_currency == 'USD'){
        $exchange_to_currency = $GLOBALS ['db']->getOne($exchange_to_currency_sql); 
        if(isset($exchange_to_currency) && $exchange_to_currency != ''){
            $res = $amount * $exchange_to_currency;
            return $res ;
        }
        else{
            return $amount;
        }
    }
    else {
        $exchange_from_currency = $GLOBALS ['db']->getOne($exchange_from_currency_sql); 
        $exchange_to_currency = $GLOBALS ['db']->getOne($exchange_to_currency_sql); 
        
        if(isset($exchange_from_currency) && isset($exchange_to_currency)){
            $amount_usd = $amount / $exchange_from_currency;
            $res = number_format($amount_usd * $exchange_to_currency,2);
            return $res;
        }
        else {
            
            return $amount;
        }
    }
}
?>
