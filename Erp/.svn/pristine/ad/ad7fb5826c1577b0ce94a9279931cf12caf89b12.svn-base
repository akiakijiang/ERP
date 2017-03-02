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

admin_priv('kf_abnormal_orders');
/*验证是否已经登陆*/
$offset = 10;
$page = intval($_GET['page']);
$page = max(1, $page);
$from = ($page-1)*$offset;

$order_list = array();
$limit = " LIMIT $offset OFFSET $from ";

$smarty->assign('info',    $userInfo);
$is_deal = $_REQUEST['is_deal'];

$condition = "";
$act = $_REQUEST['act'];
if ($act == "edit") {
	$consult_ids = $_POST['consult_id'];
	$action_time = date("Y-m-d H:i:s");
	if (is_array($consult_ids)) {
		foreach ($consult_ids as $consult_id) {
			$attributes = array();
			$attributes['date'] = $_POST["creatation_time-$consult_id"] !== null ? trim($_POST["creatation_time-$consult_id"]) : null;
			$attributes['order_sn'] = $_POST["abnormal_order-$consult_id"] !== null ? trim($_POST["abnormal_order-$consult_id"]) : null;
			$attributes['reason_id'] = $_POST["reason-$consult_id"] !== null ? trim($_POST["reason-$consult_id"]) : null;
			$attributes['content'] = $_POST["content-$consult_id"] !== null ? trim($_POST["content-$consult_id"]) : null;
			$attributes['admin_id'] = $_POST["action_user-$consult_id"] !== null ? trim($_POST["action_user-$consult_id"]) : null;
			
			
//			$histories = array();
			if ($consult_id) {
				$attributes['is_deal'] = $_POST["is_deal-$consult_id"] !== null ? trim($_POST["is_deal-$consult_id"]) : null;
				$sql = "SELECT * FROM {$ecs->table('abnormal_orders')} WHERE id = '$consult_id'";
				$consult = $db->getRow($sql);
				$attributes['note'] = $consult['note'];
				if ($attributes['is_deal'] == '1' && $consult['is_deal'] == '0') {
					$attributes['deal_date'] = date("Y-m-d H:i:s");
					$attributes['note'] .= "已处理 ". $attributes['deal_date']. " {$_SESSION['admin_name']}<br/>";
				}
				if ($attributes['reason_id'] != $consult['reason_id']) {
					$attributes['note'] .= "修改原因". get_abnormal_reasons($consult['reason_id'])."为". get_abnormal_reasons($attributes['reason_id']). " {$_SESSION['admin_name']}<br/>";
				}
				if ($attributes['content'] != $consult['content']) {
					$attributes['note'] .= "修改具体为：". $attributes['content']. " ；{$_SESSION['admin_name']}<br/>";
				}
				$pair = array();
				foreach ($attributes AS $key=>$value) {
					if ($value !== null) {
						$pair[] = "$key='$value'";
//						if ($value != $consult[$key]) {
//							$histories[] = array('table_name'=>"{$ecs->table('oukoo_consult')}", 'field_name'=>$key, 'origin_value'=>$consult[$key], 'set_value'=>$value, 'execute_sql'=>'', 'execute_type'=>'update', 'action_user'=>$_SESSION['admin_name'], 'action_time'=>$action_time);
//						}					
					}
				}
				$sql = "UPDATE {$ecs->table('abnormal_orders')} SET " . join(', ', $pair) . " WHERE id = '$consult_id'";
				$db->query($sql);
			} else {
				$keys = $values = array();
				$sql = "SELECT order_sn FROM {$ecs->table('order_info')} WHERE order_sn = '{$attributes['order_sn']}' LIMIT 1";
				$order = $db->getOne($sql);
				if ($order) {
					$sql = "SELECT order_sn FROM {$ecs->table('abnormal_orders')} WHERE order_sn = '{$attributes['order_sn']}' LIMIT 1";
					$abnormal_order = $db->getOne($sql);
					if ($abnormal_order) {
						$info = "info=无法添加，该订单已添加过";
					} else {
						$keys[] = "note";
						$values[] = "'新建异常订单 ". date("Y-m-d H:i:s"). " {$_SESSION['admin_name']}<br/>'";
						foreach ($attributes AS $key=>$value) {
							if ($value !== null) {
								$keys[] = $key;
								$values[] = "'$value'";
								
		//						$histories[] = array('table_name'=>"{$ecs->table('oukoo_consult')}", 'field_name'=>$key, 'origin_value'=>'', 'set_value'=>$value, 'execute_sql'=>'', 'execute_type'=>'insert', 'action_user'=>$_SESSION['admin_name'], 'action_time'=>$action_time);
							}
						}
						$sql = "INSERT INTO {$ecs->table('abnormal_orders')} (" . join(', ', $keys) . ") VALUES (" . join(', ', $values) . ")";
						$db->query($sql);
					}
				} else {
					$info = "info=订单不存在";
				}
//				$consult_id = $db->insert_id();
			}
			
	//		foreach ($histories as $key => $history) {
	//			$histories[$key]['execute_sql'] = mysql_escape_string($sql);
	//			$histories[$key]['reference_key'] = $consult_id;
	//		}
	//		
	//		add_history($histories, $ecs->table('oukoo_consult_action'));
		}
	}
	
	$back = $_REQUEST['back'] !== null ? $_REQUEST['back'] : "/";
	Header("Location: $back&$info");
}
if ($act == "reasons") {
	$reason = $_REQUEST['reason'] !== null ? trim($_REQUEST['reason']) : null;
	if ($reason) {
		$sql = "SELECT id FROM {$ecs->table('abnormal_orders_reason')} WHERE name = '$reason' ";
		$is_exists = $db->getOne($sql);
		if (!$is_exists) {
			$sql = "INSERT INTO {$ecs->table('abnormal_orders_reason')} (name) VALUES ('$reason')";
			$db->query($sql);
		}
	}
}

if ($act == "search") {
	$reason_id = $_REQUEST['reason_id'];
	$is_deal = $_REQUEST['is_deal'];
	$start = $_REQUEST['start'];
	$end = $_REQUEST['end'];
	$searchtext = $_REQUEST['search_text'];
	if ($reason_id != -1) {
		$condition .= " AND ao.reason_id = $reason_id ";
	}
	if ($is_deal != -1) {
		$condition .= " AND ao.is_deal = $is_deal ";
	}
	if ($start) {
		$condition .= " AND ao.date > '$start' ";
	}
	if ($end) {
		$condition .= " AND ao.date < '$end' ";
	}
	if ($searchtext) {
		$condition .= " AND (ao.order_sn = '$searchtext' OR ao.note LIKE '%$searchtext%' OR ao.content LIKE '%$searchtext%' 
										OR oi.consignee = '$searchtext' OR oi.tel = '$searchtext' OR oi.mobile = '$searchtext' OR u.user_name = '$searchtext')";
	}
}
if ($act == "edit_reason") {
	$reason_id = $_POST['reason_id'];
	$reason_name = $_POST['reason_name'];
	$sql = "UPDATE {$ecs->table('abnormal_orders_reason')} SET name = '$reason_name' WHERE id = $reason_id ";
	$db->query($sql);
}
if ($_GET['reason_id'] && $_GET['reason_id'] != -1) {
	$condition .= " AND ao.reason_id = {$_GET['reason_id']} ";
}

# 添加party条件判断 2009/08/06 yxiang
$sql = " 
	SELECT ao.*, oi.consignee AS customer_name, oi.tel, oi.mobile, u.user_name, aor.name AS reason_name, IF( ao.is_deal = 1, '是', '否') AS deal_status
	FROM {$GLOBALS['ecs']->table('order_info')} AS oi, {$GLOBALS['ecs']->table('abnormal_orders')} AS ao, {$ecs->table('admin_user')} u, {$ecs->table('abnormal_orders_reason')} aor
	WHERE oi.order_sn = ao.order_sn AND u.user_id = ao.admin_id AND ao.reason_id = aor.id AND ". party_sql('oi.party_id') ." {$condition} 
	ORDER BY ao.date DESC, ao.id DESC 
	$limit
";

$sqlc = "
	SELECT COUNT(*) 
	FROM {$ecs->table('order_info')} oi, {$GLOBALS['ecs']->table('abnormal_orders')} AS ao, {$ecs->table('admin_user')} u
	WHERE oi.order_sn = ao.order_sn AND u.user_id = ao.admin_id AND ". party_sql('oi.party_id') ." {$condition} 
";
$abnormal_orders = $db->getAll($sql);
$total = $db->getOne($sqlc);
$Pager = Pager($total, $offset, $page);

$smarty->assign('pager', $Pager);
$smarty->assign('back', "abnormal_orders.php?");
$smarty->assign('abnormal_orders', $abnormal_orders);
$smarty->assign('abnormal_reasons', get_abnormal_reasons());
$csv = $_REQUEST['csv'];
if ($csv) {
	admin_priv("admin_other_csv");
	header("Content-type:application/vnd.ms-excel");
	header("Content-Disposition:filename=" . iconv("UTF-8","GB18030","异常订单表") . ".csv");	
	$out = $smarty->fetch('oukooext/abnormal_orders_csv.htm');
	echo iconv("UTF-8","GB18030", $out);
	exit();
//$smarty->display('oukooext/abnormal_orders_csv.htm');
} else {
	$smarty->display('oukooext/abnormal_orders.htm');
}

function get_abnormal_reasons ($reason_id = null) {
	global $db, $ecs;
	if ($reason_id) {
		return $db->getOne("SELECT name FROM {$ecs->table('abnormal_orders_reason')} WHERE id = '$reason_id' LIMIT 1");
	} else {
		return $db->getAll("SELECT * FROM {$ecs->table('abnormal_orders_reason')} ");
	}
}
?>