<?php
define('IN_ECS', true);
require('includes/init.php');
admin_priv("tj_analyze_tit");
$condition = get_condition();

// 确认订单放最上面，接下来是取消的、未确认的，没有订单的聊天记录放最后
$sql = "
    SELECT o.order_id, o.order_sn, o.order_time, o.order_status, o.shipping_status, o.pay_status, c.client_id, c.session_id, c.start_time/1000 as start_time
    , IF(o.order_status = 1, UNIX_TIMESTAMP(o.order_time), order_status) sort
    FROM talk_in_time.client c LEFT JOIN ecshop.ecs_order_info o ON c.tracker_id = o.track_id AND UNIX_TIMESTAMP(o.order_time) > c.start_time/1000
    WHERE
        1
        $condition
    GROUP BY c.session_id
    ORDER BY sort DESC, start_time DESC
";
$clients = $db->getAll($sql);

$session_id = trim($_REQUEST['session_id']);
$sql = "
    SELECT * 
    FROM talk_in_time.message m INNER JOIN talk_in_time.client c ON m.client_id = c.client_id
    WHERE
        c.session_id = '{$session_id}'
    ORDER BY time
";
$messages = $db->getAll($sql);

$smarty->assign('clients', $clients);
$smarty->assign('messages', $messages);
$smarty->display('oukooext/analyze_tit.htm');

function get_condition() {
    $condition = "";
    $start = trim($_REQUEST['start']);
    $end = trim($_REQUEST['end']);
    
    if (strtotime($start) <= 0) {
        $start = date("Y-m-d");
    }
    $start = strtotime($start);
    $condition .= " AND start_time >= $start * 1000";
    
    if (strtotime($end) <= 0) {
        $end = date("Y-m-d");    
    }
    $end = strftime('%Y-%m-%d', strtotime("+1 day", strtotime($end)));
    $end = strtotime($end);
    $condition .= " AND start_time <= $end * 1000"; 
    return $condition;
}
?>