<?php
/**
All Hail Sinri Edogawa !

This php and its corresponding html templates is used to give a html formated page of a barcode tip to print.

@AUTHOR ljni@i9i8.com
@UPDATED 20130810

@PARAM barcodes A string of barcodes with a comma between each pair of barcode.
@PARAM type Just designed for type grouding || location || goods and only grouding realized.
@PARAM sugu_print Set it as 1 if you want to print those directly, or set it 0.
**/
	define('IN_ECS', true);
	require_once('includes/init.php');
	require_once 'function.php';
	//pp($_REQUEST);
	//die();

	if(isset($_REQUEST['type']) && is_string($_REQUEST['type'])){
    	$type = $_REQUEST['type'];
	}
	else {
    	die("参数错误");
	}
	if($type=='tray'){
		if(isset($_REQUEST['barcodes']) && is_string($_REQUEST['barcodes'])){
	    	$barcodes = preg_split('/\s*,\s*/',$_REQUEST['barcodes'],-1,PREG_SPLIT_NO_EMPTY);
		}
		else {
	    	die("参数错误");
		}
	}else if($type=='grouding'){
		if(isset($_REQUEST['barcodes']) && is_string($_REQUEST['barcodes'])){
	    	$barcodes = preg_split('/\s*,\s*/',$_REQUEST['barcodes'],-1,PREG_SPLIT_NO_EMPTY);
		}
		else {
	    	die("参数错误");
		}
	} else if($type=='goods'){
		if(isset($_REQUEST['barcode']) && is_string($_REQUEST['barcode'])){
			$barcode=$_REQUEST['barcode'];
		} else {
			die("参数错误");
		}
		if(isset($_REQUEST['number'])){
			$number=$_REQUEST['number'];
		} else {
			die("参数错误");
		}
		$barcodes=array();
		for ($i=0; $i < $number; $i++) { 
			$barcodes[]=$barcode;
		}
	} else if ($type=='sn'){
		if(isset($_REQUEST['barcode']) && is_string($_REQUEST['barcode'])){
			$barcode=$_REQUEST['barcode'];
		} else {
			die("参数错误");
		}
		if(isset($_REQUEST['number'])){
			$number=$_REQUEST['number'];
		} else {
			die("参数错误");
		}		
		if(isset($_REQUEST['start'])){
			$start=$_REQUEST['start'];
		}
		$barcodes=array();
		for ($i=$start; $i < $start+$number; $i++) { 
			$barcodes[]=$barcode.'-'.$i;
		}
	} else if ($type=="location"){
		if(isset($_REQUEST['barcode']) && is_string($_REQUEST['barcode'])){
			$barcode=$_REQUEST['barcode'];
		} else {
			die("参数错误");
		}
		if(isset($_REQUEST['number'])){
			$number=$_REQUEST['number'];
		} else {
			die("参数错误");
		}
		$barcodes=array();
		for ($i=0; $i < $number; $i++) { 
			$barcodes[]=$barcode;
		}
	} else if($type=="locations"){
		//pp($_REQUEST);
		if(isset($_REQUEST['barcodes']) && is_string($_REQUEST['barcodes'])){
	    	$barcodes = preg_split('/\s*,\s*/',$_REQUEST['barcodes'],-1,PREG_SPLIT_NO_EMPTY);
		}
		else {
	    	die("参数错误");
		}
	}
	else if($type=="locations_post"){
		if(isset($_REQUEST['barcodes']) && is_array($_REQUEST['barcodes'])){
	    	$barcodes = $_REQUEST['barcodes'];
		} else {
	    	die("参数错误");
		}
		$type="locations";
	}else if($type=='scan_barcode'){
		if(isset($_REQUEST['barcodes']) && is_string($_REQUEST['barcodes'])){
	    	$barcodes = preg_split('/\s*,\s*/',$_REQUEST['barcodes'],-1,PREG_SPLIT_NO_EMPTY);
		}
		else {
	    	die("参数错误");
		}
	}

	if(isset($_REQUEST['sugu_print']) && is_string($_REQUEST['sugu_print'])){
    	$sugu_print = $_REQUEST['sugu_print'];
	}
	else {
    	$sugu_print = 0;//Sugu print suru koto dehanaku. 1 nara, sugu. 2 nara, batch.
	}

	$smarty->assign('type',$type);
	$smarty->assign('sugu_print',$sugu_print);
	$smarty->assign('barcodes',$barcodes);
	$smarty->display('oukooext/barcode_paper/barcode_list.htm');

?>