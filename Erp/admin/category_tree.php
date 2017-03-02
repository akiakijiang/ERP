<?php
define ( 'IN_ECS', true );
require_once ('includes/init.php');
require_once ('distribution.inc.php');

require_once ('includes/lib_category.php');

/**
 * 大鲵曰：这是原先的代码,准备扔掉
 */

if ($_REQUEST ['act'] == 'list') {
	$party_id = $_SESSION ['party_id'];
	
	if($party_id == 65625 && check_admin_priv('zhongliang_ERP_system')){
		$content = category_tree_list ( $party_id, 9718);
	}else{
		$content.= category_tree_list ( $party_id, - 1 );
	}
	$smarty->assign('is_edit','0');
	$smarty->assign ( 'categoryTree', $content );
	$smarty->display ( 'category_tree.htm' );
}elseif($_REQUEST ['act'] == 'edit')
{
	$party_id = $_SESSION ['party_id'];
	  $content.= category_tree_edit ( $party_id, - 1 );
	
	$smarty->assign('is_edit','1');
	$smarty->assign ( 'categoryTree', $content);
	$smarty->display ( 'category_tree.htm' );
}

/**
 * 大鲵曰：这是大鲵伸出触手写的代码,准备换上去
 */

elseif($_REQUEST['act']=='list_by_sinri'){//For Readonly List of SKU Editing
	require_once ('includes/lib_sinri_category.php');

	$root_cat_id_list=array();

	if($_SESSION['party_id'] == 65625 && check_admin_priv('zhongliang_ERP_system')){
		$root_cat_id_list=array(9718);
	}

	$tree=CategoryAgency::getCategoryTreeWithPartyId($_SESSION['party_id'],$root_cat_id_list);
	$content="";
	foreach ($tree as $index=>/*$eda) { foreach($eda as*/ $node){
			$content.=CategoryAgency::parseSinriCategoryTreeNodeToHTML($node,'list');
		}
	// }
	
	$smarty->assign('is_edit','0');
	$smarty->assign ( 'categoryTree', $content );
	$smarty->display ( 'category_tree.htm' );
}elseif($_REQUEST['act']=='edit_list_by_sinri'){//For Readonly List of Category Editing
	require_once ('includes/lib_sinri_category.php');
	$tree=CategoryAgency::getCategoryTreeWithPartyId($_SESSION['party_id']);
	$content="";
	foreach ($tree as $index=>$eda) {
		foreach($eda as $node){
			$content.=CategoryAgency::parseSinriCategoryTreeNodeToHTML($node,'edit');
		}
	}
	
	$smarty->assign('is_edit','1');
	$smarty->assign ( 'categoryTree', $content );
	$smarty->display ( 'category_tree.htm' );
}

/**
 * 大鲵曰：这是大鲵伸出触手写的实验代码,无视就好了
 */

elseif($_REQUEST['act']=='tree_by_sinri'){ // FOR EXPERIMENT NO ACTUAL USE
	require_once ('includes/lib_sinri_category.php');
	$treer=new SinriCategoryTree();
	$treer->testTree();
	var_export($treer->trees);
}


?>