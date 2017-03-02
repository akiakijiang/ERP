<?php

define('IN_ECS', true);
require_once('includes/init.php');
require_once('includes/lib_ups.php');

$order_id = $_REQUEST['order_id'];
$pieces = intval($_REQUEST['pieces']);

$pieces = $pieces > 0 ? $pieces : 1;

$pinming = $_REQUEST['pinming'];
$param = array(
	'pinming' => $pinming,
);
$result = ups_ship_request($order_id, $pieces, $param);

if ($result['code'] > 0) {
    print "该订单无法自动提交到ups，请使用ups软件打印（{$result['msg']}）";
    exit();
}

print $result['msg'];
