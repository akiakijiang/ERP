<?php
define('IN_ECS', true);

require('includes/init.php');
require("function.php");

$size = $_REQUEST['size'] ? $_REQUEST['size'] : 20;
$page = $_REQUEST['page'] ? $_REQUEST['page'] : 1;
$start = ($page - 1) * $size;

$limit = "LIMIT $size";
$offset = "OFFSET $start";

$act = $_REQUEST['act'];

if ($act == 'edit') {
	$brand_id = $_REQUEST['brand_id'];
	$brand_code = $_REQUEST['brand_code'];
	$sql = "UPDATE {$ecs->table('brand')} SET brand_code = '{$brand_code}' WHERE brand_id = '{$brand_id}'";
	$db->query($sql);
}


$condition = get_condition();
$sql = "
	SELECT * FROM {$ecs->table('brand')}
	WHERE
		brand_name REGEXP '^[a-zA-z0-9].*'
		{$condition}
	ORDER BY brand_name
	$limit $offset
";

$sqlc = "
	SELECT COUNT(*) FROM {$ecs->table('brand')}
	WHERE 
		brand_name REGEXP '^[a-zA-z0-9].*'
		{$condition}
";
//pp($sql);
$brands = $db->getAll($sql);
$count = $db->getOne($sqlc);
$pager = Pager($count, $size, $page);

$smarty->assign('brands', $brands);
$smarty->assign('back', $_SERVER['REQUEST_URI']);
$smarty->assign('pager', $pager);
$smarty->display('oukooext/brand_code_edit.htm');

function get_condition() {
	$condition = "";
	$act = $_REQUEST['act'];
	
	$brand_name = trim($_REQUEST['brand_name']);
	
	if ($brand_name != '') {
		$condition .= " AND brand_name LIKE '%{$brand_name}%'";
	}
	
	if ($act != 'search') {
		$condition .= " AND IFNULL(brand_code, '') = ''";
	}
	
	return $condition;
}
?>