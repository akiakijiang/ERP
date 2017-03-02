<?php
define ( 'IN_ECS', true );
require_once ('includes/init.php');
require_once ('function.php');
admin_priv('haiguan_goods_info');
require_once(ROOT_PATH . 'includes/lib_order.php');
require_once(ROOT_PATH . 'includes/cls_json.php');
include_once(ROOT_PATH . 'admin/function.php'); 
require_once (ROOT_PATH . 'includes/debug/lib_log.php');
 
 $act = $_REQUEST ['act'];
 if ($req == 'ajax')
{
    $json = new JSON;
    switch ($act) 
    { 
        case 'get_select_goods':
            $goods_name = $_GET['pro_name'];
            $limit = (isset($_POST['limit']) && is_numeric($_POST['limit'])) ? $_POST['limit'] : 40 ;
            print $json->encode(distribution_get_goods_list(NULL, NULL, $_POST['q'], $limit));  
            break;
    }
    exit;
}

	$product_id = trim ( $_REQUEST ['product_id'] );
 	$goods_name = trim ( $_REQUEST ['goods_name'] );
	$product_id = $_REQUEST ['product_id'];
	$outer_id = $_REQUEST ['outer_id'];
	$item_code = $_REQUEST ['item_code'];
	$kao_code = $_REQUEST ['kao_code'];
	
	if($act == "modify"){
    	$sql = "
		update  ecshop.kuajing_bird_product set item_code=$item_code,kao_code=$kao_code where  product_id = $product_id ; 
		";
		$result=$GLOBALS['db']->query($sql);
	}
	
	
	
	$smarty->assign('product_id',$product_id);
	$smarty->assign('goods_name',$goods_name);
	$smarty->assign('product_id',$product_id);
	$smarty->assign('outer_id',$outer_id);
	$smarty->assign('item_code',$item_code);
	$smarty->assign('kao_code',$kao_code);
	$smarty->display ( 'kuajing_items_modify.html' );
?>
