<?php
/**
@author ljni@i9i8.com
**/
define('IN_ECS', true);
require('includes/init.php');
require("function.php");
require("ajax.php");
require_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');
require_once('includes/lib_sinri_DealPrint.php');
function show_one_duty(){
	global $smarty;
	$duty=$_SESSION['duties'][$_SESSION['duty_no']];
	foreach ($duty as $key => $value) {
		if($key == 'location_barcode'){
			$shall_location_barcode = $value;
		} else if ($key == 'goods_barcode'){
			$shall_goods_barcode=$value;
		} else if ($key == 'goods_name'){
			$shall_goods_name=$value;
		} else if( $key == 'product_id'){
			$shall_product_id = $value;
		}
	}
	$smarty->assign('duty',$duty);
}
$state = 0;
if(isset($_REQUEST['state'])){
	$state = $_REQUEST['state'];
}
if($state == 0){
	if(isset($_SESSION['BPSN']))$_SESSION['BPSN'] = null;
	if(isset($_SESSION['location_barcode']))$_SESSION['location_barcode'] = null;
	if(isset($_SESSION['duties']))$_SESSION['duties'] = null;
	if(isset($_SESSION['duty_no']))$_SESSION['duty_no'] = null;
} else if($state == 1){
		$BPSN = $_REQUEST['BPSN'];
		$res = check_batch_pick_barcode($BPSN);
		if($res['success']){//BPSN检查成功
			$_SESSION['BPSN'] = $BPSN;
			$result = get_location_duty_list($BPSN);
		    if($result['success'] == false){//获取任务失败（ERROR）
				$_SESSION['error_get_duty_list'] = $result['error'];
				header("Location:batch_pick_rf_scan_n1.php?state=3");
				exit();
			} else {
				$_SESSION['duties'] = $result['duties'];
				$_SESSION['duty_no'] = -1;
				$len = sizeof($_SESSION['duties']);	
				$_SESSION['duty_length'] = $len;
				if($len > 0){
					$_SESSION['duty_no'] = ($_SESSION['duty_no'] + 1) % $len;
					$state = 201;
					show_one_duty();
				} else{
					header("Location:batch_pick_rf_scan_n1.php?state=3");
				    exit();
				}	
			}
		} else {
			$goto = 0;
		}
} else if($state == 200){
	$len = sizeof($_SESSION['duties']);	
	$_SESSION['duty_length'] = $len;
	if($len > 0){
		$_SESSION['duty_no'] = ($_SESSION['duty_no'] + 1) % $len;
		$state = 201;
		show_one_duty();
	} else {
		$BPSN = $_SESSION['BPSN'];
		header("Location:batch_pick_rf_scan_n1.php?state=1&BPSN=$BPSN");
		exit();
	}
} else if($state == 202){
	$product_id = $_REQUEST['product_id'];
	$from_location_barcode = $_REQUEST['location_barcode'];
	$res = deliver_batch_location_product($_SESSION['BPSN'],$from_location_barcode,$product_id);
	if($res['success'] == true){//出库成功，转到下一个DUTY，若没有，则转到下一库位
		unset($_SESSION['duties'][$_SESSION['duty_no']]);
		$temp_duties = array();
		$i = 0;
		foreach ($_SESSION['duties'] as $key => $value) {
			$temp_duties[$i] = $value;
			if($key > $_SESSION['duty_no']){
				$_SESSION['duty_no'] = $i;
			}
			$i = $i + 1;
		}
		$_SESSION['duties'] = $temp_duties;
		header("Location:batch_pick_rf_scan_n1.php?state=200");
		exit();
	} else {//出库失败，提示出库错误
		$goto = 0;
		$error_msg = $res['error'];	
		$smarty->assign('error_msg',$error_msg);			
	}
} else if($state == 3){
	if(isset($_SESSION['error_get_duty_list'])){//获取任务失败，提示相应错误
		$goto = 0;
		$smarty->assign('error_get_duty_list',$_SESSION['error_get_duty_list']);
	} else {
		terminal_batch_pick($_SESSION['BPSN']);
		$goto = 1;
	}
}
$smarty->assign('BPSN',$_SESSION['BPSN']); 
$smarty->assign('goto',$goto);
$smarty->assign('state',$state);
$smarty->display('oukooext/batch_pick_rf_scan.htm');
?>