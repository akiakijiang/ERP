<?php
/**
All Hail Sinri Edogawa !

This php and its corresponding html templates is used to give a html formated page of a barcode tip to print.

@AUTHOR ljni@i9i8.com
@UPDATED 20130810

@PARAM barcode A string of barcode
@PARAM type Just designed for type grouding || location || goods and only grouding realized.
@PARAM sugu_print Set it as 1 if you want to print only one tip, or set it 0.
**/
	define('IN_ECS', true);
	require_once('includes/init.php');
	require_once 'function.php';

	$party_id = $_SESSION['party_id'];
	$party_name = party_mapping($party_id);
	$smarty->assign("party_name", $party_name);
	
	if(isset($_REQUEST['barcode']) && is_string($_REQUEST['barcode'])){
    	$barcode = $_REQUEST['barcode'];
	}
	else {
    	die("参数错误");
	}
	if(isset($_REQUEST['type']) && is_string($_REQUEST['type'])){
    	$type = $_REQUEST['type'];
	}
	else {
    	die("参数错误");
	}
	if(isset($_REQUEST['sugu_print']) && is_string($_REQUEST['sugu_print'])){
    	$sugu_print = $_REQUEST['sugu_print'];
	}
	else {
    	$sugu_print = 0;//Sugu print suru koto dehanaku. 1 nara, sugu. 2 nara, batch.
	}

	$smarty->assign('barcode',$barcode);
	$smarty->assign('type',$type);
	$smarty->assign('sugu_print',$sugu_print);

	if($type=='grouding'){
		$smarty->display('oukooext/barcode_paper/grouding_barcode.htm');
	} else if ($type=='location') {
		$smarty->display('oukooext/barcode_paper/warehouse_location_barcode.htm');
	} else if ($type=='goods') {
		require_once('includes/lib_sinri_DealPrint.php');
		$name=getGoodsNameByBarcode($barcode);
		$smarty->assign('goods_name',$name);
		$smarty->display('oukooext/barcode_paper/goods_barcode.htm');
	} else if ($type='sn'){
		$keys = preg_split("/[\s,-]+/", $barcode);	
		require_once('includes/lib_sinri_DealPrint.php');
		if(sizeof($keys)>0)$name=getGoodsNameByBarcode($keys[0]);else $name=null;
		$smarty->assign('goods_name',$name);
		$smarty->display('oukooext/barcode_paper/sn_barcode.htm');
	} 
?>