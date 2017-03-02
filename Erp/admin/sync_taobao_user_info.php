<?php
/**
 * 从order_info同步淘宝用户信息到users
 * $Id: sync_taobao_user_info.php 25448 2010-12-22 08:22:22Z yzhang $
 * @author yzhang@leqee.com
 * @copyright 2010.12 leqee.com
 */

define('IN_ECS', true);
require ('includes/init.php');
//admin_priv('sync_taobao_user_info');
require ("function.php");
include_once ('includes/lib_order.php');

set_time_limit(0);

// get register source
$source_id = get_user_register_source_id('淘宝');

// get order total
//$sql = "SELECT COUNT(*) AS cc FROM ecshop.order_attribute a 
//			LEFT JOIN ecshop.ecs_order_info b ON a.order_id = b.order_id
//		WHERE a.attr_name = 'TAOBAO_USER_ID' ";
$sql = "SELECT COUNT(*) AS cc FROM ecshop.ecs_order_info a 
			LEFT JOIN ecshop.order_attribute b ON a.order_id = b.order_id
		WHERE b.attr_name = 'TAOBAO_USER_ID' ";
$total = $db->getOne($sql);

$offset = 6;
$page = isset($_GET['page']) ? intval($_GET['page']) : 0;
$page = $page > 0 ? $page : 1;
$from = ($page - 1) * $offset;

$pages = ceil($total / $offset);
if ($page > 1 && $page > $pages) {
    die('end sync');
} else {
    echo "from $from";
}

$limit = " LIMIT $offset OFFSET $from ";
// get order list
//$sql = "SELECT * FROM ecshop.order_attribute a 
//			LEFT JOIN ecshop.ecs_order_info b ON a.order_id = b.order_id
//		WHERE a.attr_name = 'TAOBAO_USER_ID' AND b.order_id IS NOT NULL
//		$limit ";
$sql = "SELECT * FROM ecshop.ecs_order_info a 
			LEFT JOIN ecshop.order_attribute b ON a.order_id = b.order_id 
		WHERE b.attr_name = 'TAOBAO_USER_ID' 
		$limit ";

$getAll = $db->getAll($sql);

//ecs_order_info
//TAOBAO_USER_ID	consignee	sex	country	province	city	district	address	zipcode	tel	mobile	email	fromsite
//ecs_users
//userId	user_name	sex	country	province	city	district	address_id	zipcode	user_tel	user_mobile	email	xxx
//user_address
//consignee    sex    email    country    province    city    district    address    zipcode    tel    mobile
$r = array();
foreach ($getAll as $order_info) {
    sync_taobao_user_info($order_info, $source_id);
}

header("location: ?page=" . ($page + 1));







