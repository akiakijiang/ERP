<?php
define('IN_ECS', true);
require('../includes/init.php');
require_once(ROOT_PATH . 'includes/lib_order.php');

/*
 * 查询goods_id style_id对应的商品，所有的价格列表
 * 
 */



if ($_GET["act"] == "search_price") {
	include_once(ROOT_PATH . 'includes/cls_json.php');
	require_once(ROOT_PATH."RomeoApi/lib_inventory.php");
	$json = new JSON();
	$result = array('error' => 0, 'message'=> '', 'content' => '');
	global $db;

	$goods_id = $_GET["goods_id"];
	$style_id = $_GET["style_id"];
	$party_id = $_SESSION["party_id"];
	if (isset($goods_id) && isset($style_id)) {
		$sql = "select unit_cost from romeo.inventory_item ii
				inner join romeo.product_mapping pm on pm.product_id = ii.product_id
				inner join ecshop.ecs_goods eg on eg.goods_id = pm.ecs_goods_id
				where pm.ecs_goods_id = '{$goods_id}' and pm.ecs_style_id = '{$style_id}' 
				and eg.goods_party_id ='{$party_id}'";
		$price_list = $db->getAll($sql);
		if (!isset($price_list)) {
			$result["message"] = "根据goods_id和style_id找不到价格，请手工填写价格";
			echo $json->encode($result);
			exit();
		}

		$data = array();
		foreach ($price_list as $key => $item){
			$data[] = $item["unit_cost"];
		}
		$data = array_unique($data);
		$data_return = array();
		foreach ($data as $key => $item){
			$data_return[] = $item;
		}
		$result["goods_price_list"] = $data_return;
		echo $json->encode($result);
	};
}