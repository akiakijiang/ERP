<?php
/**
 * oukoo[欧酷网]
 * 到货通知后台
 * @author :ncchen<ncchen@oukoo.com>
 * @copyright oukoo<0.5>
*/

define('IN_ECS', true);
require('includes/init.php');
require("function.php");
admin_priv('kf_pricecut_inform');

$back = $_SERVER['REQUEST_URI'];
$act = $_REQUEST['act'];
if ($act == "edit") {
	$inform_id = $_REQUEST['inform_id'];
	$op_note = $_REQUEST['op_note'];
	$op_date = $_REQUEST['handle_time'];
	$edit_status = $_REQUEST['edit_status'];
	$sql = " SELECT IFNULL(style_price, shop_price) AS price, pi.price_inform
						FROM {$ecs->table('pricecut_inform')} pi
						INNER JOIN {$ecs->table('goods')} g ON pi.goods_id = g.goods_id
						LEFT JOIN {$ecs->table('goods_style')} gs ON gs.style_id = pi.style_id AND gs.goods_id = g.goods_id 
						WHERE pi.id = '$inform_id' ";
	$pricecut_inform = $db->getRow($sql);
	$sql = " INSERT INTO {$ecs->table('pricecut_inform_action')} (inform_id, action_user, op_status, action_time, action_note, price_inform, price) VALUES ('$inform_id', '{$_SESSION['admin_name']}', '$edit_status', '$op_date', '$op_note', '{$pricecut_inform['price_inform']}', '{$pricecut_inform['price']}' ) ";
	$db->query($sql);
	$sql = " UPDATE {$ecs->table('pricecut_inform')} SET op_status = '$edit_status' WHERE id = '$inform_id' ";
	$db->query($sql);
	$back = remove_param_in_url($_SERVER['REQUEST_URI'], "act");
}

$offset = 10;
$page = intval($_GET['page']);
$page = max(1, $page);
$from = ($page-1)*$offset;

$order_list = array();
$limit = " LIMIT $offset OFFSET $from ";

$smarty->assign('info',    $userInfo);
$smarty->assign('back',    $back);
$is_deal = $_REQUEST['is_deal'];
$op_status = $_REQUEST['op_status'];

$condition = "";

if ($act == "search") {
	$start = $_REQUEST['start'];
	$end = $_REQUEST['end'];
	$searchtext = $_REQUEST['searchtext'];
	if ($start) {
		$condition .= " AND pi.date > '$start' ";
	}
	if ($end) {
		$condition .= " AND pi.date < '$end' ";
	}
	if ($searchtext) {
		$condition .= " AND (pi.user_name = '$searchtext' OR pi.email = '$searchtext' OR pi.user_mobile = '$searchtext' OR g.goods_name LIKE '%$searchtext%')";
	}
}
if ($is_deal != '' && $is_deal != -1) {
	$condition .= " AND pi.is_deal = '$is_deal' ";
}
if ($op_status != '' && $op_status != -1) {
	$condition .= " AND pi.op_status = '$op_status' ";
}

$sql = " SELECT pi.id, g.goods_id, CONCAT_WS(' ', g.goods_name, IF(gs.goods_color = '', s.color, gs.goods_color)) AS goods_name, g.is_on_sale, IFNULL(gs.sale_status, g.sale_status) AS status, pi.op_status, pi.shop_price,
					IFNULL(gs.style_price , g.shop_price) AS price, pi.price_inform, gs.img_url, gs.goods_color, pi.date, pi.email, pi.user_mobile, pi.is_deal, pi.style_id, IF(pi.is_deal = 1, pi.action_time, null) AS action_time,
					u.user_realname, u.user_name, GROUP_CONCAT(CONCAT_WS(' ', pia.action_note, pia.action_time, pia.action_user) ORDER BY pia.action_id SEPARATOR '<br/>') AS op_note,
					IF(IF(gs.price_range = 0 OR gs.price_range is null, g.price_range, gs.price_range) = 0, 10, IF(gs.price_range = 0 OR gs.price_range is null, g.price_range, gs.price_range)) AS price_range
            FROM {$GLOBALS['ecs']->table('goods')} AS g, {$GLOBALS['ecs']->table('pricecut_inform')} AS pi
            	LEFT JOIN {$ecs->table('users')} AS u ON u.user_id = pi.uid
            	LEFT JOIN {$GLOBALS['ecs']->table('goods_style')} AS gs ON pi.style_id = gs.style_id AND pi.goods_id = gs.goods_id
            	LEFT JOIN {$ecs->table('style')} AS s ON s.style_id = gs.style_id
            	LEFT JOIN {$ecs->table('pricecut_inform_action')} AS pia ON pia.inform_id = pi.id
            WHERE g.goods_id = pi.goods_id {$condition}
            GROUP BY pi.id
            ORDER BY pi.date DESC, pi.id DESC 
            $limit";

$sqlc = "SELECT COUNT(*) FROM {$ecs->table('pricecut_inform')} pi, {$GLOBALS['ecs']->table('goods')} AS g WHERE g.goods_id = pi.goods_id {$condition}	";
$res = $db->getAll($sql);
$goods_list = array();
$map_goods_status = array(
		'tosale' 			=>		'即将上市',
		'presale' 			=>		'预订',
		'shortage' 			=>		'缺货', 
		'normal' 			=>		'在售',
		'withdrawn' 		=>		'撤回',
		'booking' 			=>		'预订',
	);
$map_deal_status = array(
		'0'		=>		'未提醒',
		'1'		=>		'已提醒',
		'2'		=>		'已取消',
		'3'		=>		'已删除',
);
$map_op_status = array(
		'none'		=>		'未处理',
		'ever'		=>		'待跟踪',
		'deal'		=>		'已处理',
);

foreach ($res as $key => $row)
{
	$goods = array();
	$goods['goods_id'] = $row['goods_id'];
	$goods['style_id'] = $row['style_id'];
	$goods['goods_name'] = $row['goods_name'];
	$goods['status'] = $row['status'];
	$goods['is_on_sale'] = $row['is_on_sale'];
	$goods['status_name'] = $map_goods_status[$row['status']];
	$goods['price'] = $row['price'];
	$goods['price_inform'] = $row['price_inform'];
	$goods['price_range'] = $row['price_range'];
	$goods['shop_price'] = $row['shop_price'];
	$goods['img_url'] = $row['img_url'];
	$goods['goods_color'] = $row['goods_color'];
	$goods['date'] = $row['date'];
	$goods['email'] = $row['email'];
	$goods['user_mobile'] = $row['user_mobile'];
	$goods['is_deal'] = $map_deal_status[$row['is_deal']];
	$goods['op_status'] = $map_op_status[$row['op_status']];
	$goods['op_note'] = $row['op_note'];
	$goods['id'] = $row['id'];
	$goods['user_realname'] = $row['user_realname'];
	$goods['user_name'] = $row['user_name'];
	$goods['action_time'] = $row['action_time'];
	$goods_list[] = $goods;
}

$total = $db->getOne($sqlc);
$Pager = Pager($total, $offset, $page);

$smarty->assign('Pager', $Pager);
$smarty->assign('goods_list', $goods_list);
$smarty->display('oukooext/pricecut_inform.htm');
?>