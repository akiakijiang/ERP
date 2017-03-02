<?php
die('非法进入');
/**
 * 最优快递转化订单测试
 */
define ( 'IN_ECS', true );
require ('includes/init.php');
require_once ('includes/lib_best_shipping.php');

TaobaoOrderTransfer();
die();

?>