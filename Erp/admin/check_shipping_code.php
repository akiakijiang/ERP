<?php
define('IN_ECS',true );
require_once('includes/init.php');
require_once('function.php');
require_once(ROOT_PATH . 'includes/cls_json.php');


global $db;
$shipment_id = $_REQUEST['shipment_id'];
if(empty($shipment_id)){
		Header("Location: shipment_recheck.php");
	}
$act = $_POST['act'];
if($act == 'ajax'){
	$shipping_code = $_POST['shipping_code'];

	$sql = "SELECT 1 FROM ecshop.brand_heinz_order_shipping_code WHERE shipping_code = '{$shipping_code}' ";
	$shipping = $db->getOne($sql);
	
	$result['success'] = empty($shipping);
	$result['message'] = $result['success'] ? "成功":"{$shipping_code} 已经存在";
	

	header('Content-type: text/html; charset=utf-8');
	$json = new JSON;
    print $json->encode($result);
	die();
}
else if($act == 'submit'){
	$message = '';
	$shipping_code_length_unused = false;
	do{
		$shipping_codes = $_POST['shipping_code'];

		if(empty($shipping_codes)){
			$message = '未提交数据';
			break;
		}
		foreach ($shipping_codes as $key=>$shipping_code) {
			if(strlen(trim($shipping_code)) != 12) {
				$shipping_code_length_unused = true;				
			}
		}
		if($shipping_code_length_unused) {
			$message = "亨氏商品物流码为12位，请检查输入！";
			break;
		}
		foreach($shipping_codes as $key=>$shipping_code){
			$shipping_codes[$key] = trim($shipping_code);
		}
		if(count($shipping_codes) != count(array_unique($shipping_codes))){
			$message = "拥有重复数据";
			break;
		}
		$sql = "SELECT shipping_code FROM ecshop.brand_heinz_order_shipping_code bhc
				LEFT JOIN ecshop.ecs_order_info o ON o.order_id = bhc.order_id
				WHERE o.order_status = 1 AND shipping_code ".db_create_in($shipping_codes);
		$result = $db->getCol($sql);
		if(!empty($result)){
			$message = "如下物流编码已存在".JOIN(',',$result);
			break;
		}
		require_once('../RomeoApi/lib_inventory.php');
		$db->start_transaction();
		for($i = 0; $i<count($shipping_codes); $i++){
			$sql = "INSERT INTO ecshop.brand_heinz_order_shipping_code 
					(order_id,product_id,shipping_code,action_user,created_stamp,last_updated_stamp)
				VALUES 
				({$_POST['order_id'][$i]},'{$_POST['product_id'][$i]}','{$shipping_codes[$i]}','{$_SESSION['admin_name']}',now(),now())";
			$db->query($sql);
		}
		
		$result = terminalShipmentRecheck($shipment_id);
		if(!$result){
			$message = '更新已复核状态失败';
			break;
		}
		$db->commit();
		Header("Location: shipment_recheck.php");
	}while(false);
	$smarty->assign('message',$message);
	
}
$sql = "SELECT og.goods_name, pm.product_id,o.order_id,og.goods_number,bh.heinz_goods_sn
	FROM romeo.order_shipment os
	INNER JOIN ecshop.ecs_order_info o ON o.order_id = CAST(os.order_id as unsigned) 
	INNER JOIN ecshop.ecs_order_goods og ON og.order_id = o.order_id
	INNER JOIN romeo.product_mapping pm ON pm.ecs_goods_id = og.goods_id AND pm.ecs_style_id = og.style_id
	INNER JOIN ecshop.brand_heinz_goods bh ON bh.goods_outer_id = og.goods_id
	WHERE bh.is_activity = 1 AND os.shipment_id = '$shipment_id' and bh.heinz_goods_sn like 'H%'";
$result = $db->getAll($sql);
if(empty($result)){
	$message = '商品或套餐信息维护有误，请误发货，并联系运营！';
	$smarty->assign('message',$message);
	Header("Location: shipment_recheck.php");
}
$order_goods = array();
foreach($result as &$order_good){
	for($i = 0; $i<$order_good['goods_number']; $i++){
		$order_goods[] = $order_good;
	}
}
$smarty->assign("shipment_id",$shipment_id);
$smarty->assign("order_goods",$order_goods);
$smarty->display("shipment/check_shipping_code.htm");




?>
