<?php
$all_data = $slave_db->getAll($sql);
$count = $slave_db->getOne($sqlc);
$pager = Pager($count, $size, $page);
$all_data = data_handle($all_data);
$smarty->assign('columns', $columns);
$smarty->assign('style', $style);
$smarty->assign('all_data', $all_data);
$smarty->assign('pager', $pager);
$smarty->assign('title', $title);
if ($caption == "") {
	$smarty->assign('caption', $title);	
}
$smarty->display('oukooext/table_template.htm');
?>