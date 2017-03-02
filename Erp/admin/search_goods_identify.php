<?php

/**
 * 查询商品标识
 * 
 */

define('IN_ECS', true);
require_once('includes/init.php');
include_once 'function.php';
require_once (ROOT_PATH . 'includes/debug/lib_log.php');

$barcode = trim($_REQUEST['barcode']);
$number = trim($_REQUEST['number']);

$identify_lists = get_identify($barcode,$number);
//pp($identify_lists);die;

$smarty->assign('identify_lists', $identify_lists);//商品标识
$smarty->display('oukooext/search_goods_identify.htm');

function get_identify($barcode,$number){
	global $db;
	$identify_lists = array();
	//默认打印1张
	$number=abs(intval($number));
	if($number==0)$number=1;

	if($number>10){
		$warning_for_ecs_print_goods_identify_history = ('Thy input for number, '.$number.', is too large: '." '$barcode','{$_SESSION['admin_name']}'");
		Qlog::log('[ecs_print_goods_identify_history warning] '.$warning_for_ecs_print_goods_identify_history);
		die($warning_for_ecs_print_goods_identify_history);
	}

// 	$sql = "SELECT * from ecshop.ecs_goods_identify  WHERE goods_barcode = '{$barcode}' limit 1 ";
	$sql = "SELECT g.goods_style_id,g.style_price,g.barcode,ifnull(g.goods_code,'') as goods_code, s.color as size, g.goods_id,eg.barcode as barcode2, eg.goods_name, gi.*
			FROM ecs_goods_identify as gi
			left join ecs_goods_style AS g  on gi.goods_barcode = g.barcode
			LEFT JOIN ecs_style AS s ON g.style_id = s.style_id
			left join ecs_goods as eg on eg.goods_id = g.goods_id 
			where gi.goods_barcode='{$barcode}' limit 1";
	// Qlog::log('sql='.$sql);
	$identifys = $db->getRow($sql);
	if(empty($identifys)){
		die('---此商品条码:'.$barcode.' 还未维护,暂时不能打印---');
	}
	
// var_dump($identifys);	
	//相同的商品标识重复打印
	for($i=0;$i<$number;$i++){
	    $identify_lists[$i] = $identifys;
	    $sql = "INSERT INTO  `ecshop`.`ecs_print_goods_identify_history` (goods_barcode,action_time,action_user) VALUES('{$identifys['goods_barcode']}',now(),'{$_SESSION['admin_name']}');";
	    // Qlog::log('insert-sql-'.$sql);
	    $db->query ( $sql );
	    // echo "<p>[$i] of [$number]</p>".PHP_EOL;
	}
	// die();
  return $identify_lists;
}
?>