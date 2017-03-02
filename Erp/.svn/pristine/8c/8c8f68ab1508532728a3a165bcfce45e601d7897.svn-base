<?php
define('IN_ECS', true);

require('../includes/init.php');
require_once(ROOT_PATH . 'includes/cls_json.php');
require_once(ROOT_PATH . 'includes/debug/lib_log.php');
require_once('../function.php');

admin_priv('goods_reserve');
$act = $_REQUEST['act'];
$method = $_REQUEST['method'] ? $_REQUEST['method'] : 'get';
global $db;
if($method == 'get'){
	$limit = 50;
	$page = $_REQUEST['page'] ? $_REQUEST['page'] : 1;
	$offset = ($page-1) * $limit;
	
	$condition = getCondition($_REQUEST);
	$sql = "SELECT egir.*,p.product_name, f.facility_name ,IFNULL(gs.barcode,g.barcode) as barcode,freeze_reason
			FROM ecshop.ecs_goods_inventory_reserved egir
			LEFT JOIN romeo.product_mapping pm ON pm.ecs_goods_id = egir.goods_id AND pm.ecs_style_id = egir.style_id
			LEFT JOIN ecshop.ecs_goods g ON g.goods_id = egir.goods_id AND g.goods_party_id = egir.party_id
			LEFT JOIN ecshop.ecs_goods_style gs ON gs.goods_id = egir.goods_id AND gs.style_id = egir.style_id and gs.is_delete=0
			LEFT JOIN romeo.facility f ON f.facility_id = egir.facility_id 
			LEFT JOIN romeo.product p ON p.product_id = pm.product_id
			WHERE egir.status = 'OK' AND egir.party_id = {$_SESSION['party_id']}  $condition limit $limit offset $offset";
	$goods_reserveds = $db->getAll($sql);
	
	$sql = "SELECT COUNT(*) FROM ecshop.ecs_goods_inventory_reserved egir 
			LEFT JOIN romeo.product_mapping pm ON pm.ecs_goods_id = egir.goods_id AND pm.ecs_style_id = egir.style_id
			LEFT JOIN ecshop.ecs_goods g ON g.goods_id = egir.goods_id AND g.goods_party_id = egir.party_id
			LEFT JOIN ecshop.ecs_goods_style gs ON gs.goods_id = egir.goods_id AND gs.style_id = egir.style_id and gs.is_delete=0
			LEFT JOIN romeo.facility f ON f.facility_id = egir.facility_id
			LEFT JOIN romeo.product p ON p.product_id = pm.product_id
			WHERE egir.status = 'OK' AND egir.party_id = {$_SESSION['party_id']}  $condition ";
	$count = $db->getOne($sql);
	
	$smarty->assign('facility', array_intersect_assoc(get_available_facility(),get_user_facility()));
	$pager = Pager($count, $limit, $page);
	$smarty->assign('goods_reserveds',$goods_reserveds);
	$smarty->assign('pager',$pager);
	$smarty->display('goods_facility_reserved.htm');
}

else if($method == 'ajax'){
	$sql = '';
	$data = '';
	$goods_id = $_REQUEST['goods_id'];
	$style_id = $_REQUEST['style_id'];
	$facility_id = $_REQUEST['facility_id'];
	
	$now_number = 0;
	if($act == 'update' || $act == 'insert'){
		$sql = "SELECT SUM(ii.quantity_on_hand_total) as now_number 
				FROM romeo.product_mapping pm 
				LEFT JOIN romeo.inventory_item ii ON pm.product_id = ii.product_id AND ii.status_id = 'INV_STTS_AVAILABLE' 
				WHERE pm.ecs_goods_id = $goods_id AND pm.ecs_style_id = $style_id AND ii.facility_id = '$facility_id'";
		$now_number = $db->getOne($sql);
		$now_number = empty($now_number) ? 0 : $now_number; 
	}
	if($act=='delete'){
		$sql = "UPDATE ecshop.ecs_goods_inventory_reserved SET status = 'DELETED' WHERE goods_id = $goods_id AND style_id = $style_id AND facility_id = $facility_id";
	}
	else if($act=='update'){
		$ori_facility_id = $_REQUEST['ori_facility_id'];
		$reserve_number = $_REQUEST['reserve_number'];
		$freeze_reason = mysql_real_escape_string($_REQUEST['freeze_reason']);
		if($now_number < $reserve_number){
			$data['message'] = "仓库剩余数量为$now_number,小于该预留库存";
		}
		$sql = "UPDATE ecshop.ecs_goods_inventory_reserved SET facility_id = $facility_id, reserve_number=$reserve_number,freeze_reason='{$freeze_reason}',last_updated_by = '{$_SESSION['admin_name']}',last_updated_stamp = now()
				WHERE goods_id = $goods_id AND style_id = $style_id AND facility_id = $ori_facility_id";
	}else if($act== 'insert'){
		$reserve_number = $_REQUEST['reserve_number'];
		$freeze_reason = mysql_real_escape_string($_REQUEST['freeze_reason']);
		if($now_number < $reserve_number){
			$data['message'] = "仓库剩余数量为$now_number,小于该预留库存";
		}
		$sql = "INSERT INTO ecshop.ecs_goods_inventory_reserved (party_id, goods_id, style_id, facility_id, reserve_number, created_by,freeze_reason, created_stamp, last_updated_by, last_updated_stamp,status)
				VALUES ( {$_SESSION['party_id']}, $goods_id,$style_id,$facility_id, $reserve_number, '{$_SESSION['admin_name']}','{$freeze_reason}', now(), '{$_SESSION['admin_name']}', now(), 'OK') 
				ON DUPLICATE KEY UPDATE
				last_updated_by = '{$_SESSION['admin_name']}',last_updated_stamp = now(),reserve_number = $reserve_number,status = 'OK',freeze_reason='{$freeze_reason}' ";
	}else if($act=='search_goods'){
		$keyword = $_REQUEST['q'];
		$sql = "SELECT g.goods_id, IFNULL(gs.style_id,0) as style_id, g.goods_name 
				FROM ecshop.ecs_goods g 
				LEFT JOIN ecshop.ecs_goods_style gs ON gs.goods_id = g.goods_id and gs.is_delete=0
				LEFT JOIN romeo.product_mapping pm ON pm.ecs_goods_id = g.goods_id AND pm.ecs_style_id = IFNULL(gs.style_id, 0)
				LEFT JOIN romeo.product p ON p.product_id = pm.product_id 
				WHERE g.goods_name like '%$keyword%' AND g.goods_party_id = {$_SESSION['party_id']}";
		$data = $db->getAll($sql);
	}
	
	if(!empty($sql)&& empty($data['message'])){
		$db->query($sql);
	}
	$json = new JSON();
	print $json->encode($data);
	die();
}




function getCondition($args){
	$condition = '';
	if(!empty($args['outer_id'])){
		$outer_id = trim($args['outer_id']);
		$gs_id = explode('_',$outer_id);
		$goods_id = $gs_id[0];
		$style_id = empty($gs_id[1]) ? 0 : $gs_id[1];
		$condition .= " AND egir.goods_id = $goods_id AND egir.style_id = $style_id";
	}
	
	if(!empty($args['goods_name'])){
		$goods_name = trim($args['goods_name']);
		$condition .= " AND p.product_name like '%$goods_name%'";
	}
	
	if(!empty($args['barcode'])){
		$barcode = trim($args['barcode']);
		$condition .= " AND IFNULL(gs.barcode,g.barcode) like '$barcode%'";
	}
	
	if($args['facility_id']!='-1' && !empty($args['facility_id'])){
		$facility_id = $args['facility_id'];
		$condition .= " AND f.facility_id = '$facility_id' ";
	}
	
	return $condition;
}



?>
