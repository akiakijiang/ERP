<?php

define('IN_ECS', true);

require('includes/init.php');
require("function.php");

$condition = getCondition();
$sql = "SELECT CONCAT_WS(' ', g.goods_name, IF(gs.goods_color = '', s.color, gs.goods_color)) AS goods_name, IFNULL(gs.sale_status_detail, g.sale_status_detail) AS sale_status_detail FROM {$ecs->table('goods_change')} gc
        INNER JOIN {$ecs->table('goods')} g ON gc.goods_id = g.goods_id 
        LEFT JOIN {$ecs->table('goods_style')} gs ON gc.goods_id = gs.goods_id AND gc.goods_style_id = gs.goods_style_id 
        LEFT JOIN {$ecs->table('style')} s ON gs.style_id = s.style_id 
        WHERE 1 $condition ";

$goods_list = $db->getAll($sql);
$smarty->assign('goods_list', $goods_list);
$smarty->display('oukooext/goods_status_change.dwt');

function getCondition() {
  if ($_REQUEST['date']) {
    $start_date = $_REQUEST['date'];
    $end_date = $_REQUEST['date']." 24:00:00";
  } else {
    $start_date = date('Y-m-d');
    $end_date = date('Y-m-d')." 24:00:00";
  }
  
  if ($_REQUEST['status'] == 'shortage') {
    $condition = " AND gc.old_value = 'normal' AND gc.new_value = 'shortage' AND gc.edit_datetime >= '$start_date' AND gc.edit_datetime < '$end_date' ";
  } else {
    $condition = " AND gc.old_value = 'shortage' AND gc.new_value = 'normal' AND gc.edit_datetime >= '$start_date' AND gc.edit_datetime < '$end_date' ";
  }
  
  if ($_REQUEST['brand_id']) {
  	$condition .= " AND g.brand_id = 1";
  }
  
  switch ($_REQUEST['goods_cagetory']) {
    case 1:	// 手机
    $condition .= " AND g.top_cat_id = 1";
    break;
    case 2:	// 配件
    $condition .= " AND g.top_cat_id = 597";
    break;
    case 3:	// 小家电
    $condition .= " AND g.top_cat_id NOT IN (1, 597, 1109) AND g.cat_id != 1157 ";
    break;
    // 1157是OPPO DVD, 1109是特殊商品
    case 4:	// DVD
    $condition .= " AND g.cat_id = 1157 ";
    break;
  }
  
  return $condition;
}