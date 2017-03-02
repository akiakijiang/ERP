<?php
define ( "IN_ECS", true );
require_once('../includes/init.php');
require (ROOT_PATH .'admin/function.php');
require_once (ROOT_PATH . "/RomeoApi/lib_inventory.php");
include_once (ROOT_PATH . 'RomeoApi/lib_currency.php');
include_once (ROOT_PATH . 'includes/cls_json.php');
require_once (ROOT_PATH . 'RomeoApi/lib_payment.php');
require_once (ROOT_PATH . 'includes/lib_order.php');
require_once (ROOT_PATH . 'includes/helper/array.php');
require_once (ROOT_PATH . 'includes/helper/uploader.php');
require_once (ROOT_PATH . 'includes/debug/lib_log.php');

if ($_REQUEST ["act"] == "") {
	$smarty->display ( "newfile2.html" );
	exit ();
} // 针对一般性访问

if ($_REQUEST ["act"] == "batchupload") {
	admin_priv("physicalInventoryApply");
	//需要验证权限，代码开发完毕要补充上main_priv("");
	batchupload ();
}

if ($_POST["act"] == "batchcheck") {
	batchcheck();
}

function batchcheck(){
	global $smarty,$db;
	
	header("Content-type:application/vnd.ms-excel");
	header("Content-Disposition:filename=" .  "库存调整单复核审查.csv");
	
	$out = $smarty->fetch('brooktest/finance_csv.htm');
	echo $out;
}


function test() {
	$json = new JSON (); // 返回json数据
	$fileElementName = 'fileToUpload'; // _FILE中的文件索引
	$uploader = new Helper_Uploader ();
	$final = array (
			message => 0,
			content => array (),
			error_item => "" 
	); // 要传回的原始数据，最后要对final进行json化
	if (! $uploader->existsFile ( $fileElementName )) {
		$final ['message'] = '没有选择上传文件，或者文件上传失败';
	} else {
		$final ['message'] = 'youzhegewenjian';
	}
	$item_value = "19092.333";
	if (preg_match ( '/^[1-9]([0-9]*\.*[0-9]+)?$/', $item_value )) {
		$final ['message'] = - 1;
	} else {
		$final ['message'] = 1;
	}
	echo $json->encode ( $final );
}
function batchupload() {
	global $db;
	$json = new JSON (); // 返回json数据
	$error = ""; // 错误信息
	$msg = "";
	$fileElementName = 'fileToUpload'; // _FILE中的文件索引
	
	$add_goods = array (); // 我最终要展示给用户的商品
	
	$party_id = $_SESSION ['party_id'];
	if (!isset($party_id)){
		die("请先选择分公司");
	}
	$uploader = new Helper_Uploader ();
	$max_size = $uploader->allowedUploadSize (); // 允许上传的最大值
	$final = array (
			message => "",
			content => array (),
			error_item => "" 
	); // 要传回的原始数据，最后要对final进行json化
	$config = array (
			'-v订单' => array (
					'goods_name' => '商品名称(goods_name)',
					'barcode' => '商品条码(barcode)',
					'count' => '数量(count)',
					'price' => '单价(price)',
					'amount' => '总金额',
					'status' => '库存状态(正式库、二手库)',
					'reason' => '原因',
					'party_name' => '业务组织'
			) 
	);
	
	if (! $uploader->existsFile ( $fileElementName )) {
		$final ['message'] = '没有选择上传文件，或者文件上传失败';
	}
	
	// 取得要上传的文件句柄
	if ($final ['message'] == "") {
		$file = $uploader->file ( $fileElementName );
	}
	
	// 检查上传文件
	if ($final ['message'] == "" && ! $file->isValid ( 'xls, xlsx', $max_size )) {
		$final ['message'] = "非法的文件! 请检查文件类型(xls, xlsx), 并且系统限制的上传大小为" . $max_size / 1024 / 1024 . "MB";
	}
	
	// 读取excel
	if ($final ['message'] == "") {
		$result = excel_read ( $file->filepath (), $config, $file->extname (), $failed );
		if (! empty ( $failed )) {
			$final ['message'] = reset ( $failed );
		}
	}
	
	if ($final ['message'] == "") {
		$rowset = $result ['-v订单'];
		if (empty ( $rowset )) {
			$final ['message'] = "excel文件中没有数据,请检查文件";
		}
	}
	
	if ($final ['message'] == "") {
		$in_goods_name = Helper_Array::getCols ( $rowset, 'goods_name' );
		$in_barcode = Helper_Array::getCols ( $rowset, 'barcode' );
		$in_count = Helper_Array::getCols ( $rowset, 'count' );
		$in_price = Helper_Array::getCols ( $rowset, 'price' );
		$in_amount = Helper_Array::getCols ( $rowset, 'amount' );
		$in_status = Helper_Array::getCols ( $rowset, 'status' );
		$in_reason = Helper_Array::getCols ( $rowset, 'reason' );
		$in_party_name = Helper_Array::getCols ( $rowset, 'party_name' );
		
		$check_value_arr = array (
				'barcode' => '商品条码(barcode)',
				'count' => '数量(count)',
				'price' => '单价(price)',
				'amount' => '总金额',
				'status' => '库存状态(正式库、二手库)',
				'reason' => '原因',
				'party_name' => '业务组织',
		);
		
		foreach ( array_keys ( $check_value_arr ) as $val ) {
			$in_val = Helper_Array::getCols ( $rowset, $val );
			$in_len = count ( $in_val );
			Helper_Array::removeEmpty ( $in_val );
			if (empty ( $in_val ) || $in_len > count ( $in_val )) {
				$empty_col = true;
				$final ['message'] = "文件中存在空的{$check_value_arr[$val]}，请确保后7列每一行都有数据";
			}
		}
	}
	
	$in_party_name = array_unique($in_party_name);
	if (count($in_party_name) != 1) {
		$final ["message"] = $final ["message"] . ",业务组织均需一致";;
	}
	
	if ($final ['message'] == "") {
		foreach ( $in_count as $val_count ) {
			if (! preg_match ( '/^[1-9\-\+]([0-9]+)?$/', $val_count )) {
				$final ["message"] = "商品数量必须为整数";
				break;
			}
		}
		foreach ( $in_price as $val_price ) {
			if (! preg_match ( '/^[0-9]([0-9]*\.*[0-9]+)?$/', $val_price )) {
				$final ["message"] = $final ["message"] . ",商品价格必须是正浮点数";
				break;
			}
		}
		foreach ( $in_status as $val_status ) {
			if (strcmp ( $val_status, "正式库" ) && strcmp ( $val_status, "二手库" )) {
				$final ["message"] = $final ["message"] . ",库存状态必须是正式库或者二手库";
				break;
			}
		}
		foreach ( $in_amount as $key => $val_amount ) {
			if ( floatval(trim($in_amount[$key])) != floatval(trim($in_price[$key]))*(intval(trim($in_count[$key]))) ) {
				$final ["message"] = $final ["message"] . ",数量*单价！=总金额";
				break;
			}
		}

	}

	if ($final["message"] == ""){
		$sql = "select party_id from romeo.party where name = '{$in_party_name[0]}'";

		$file_party_id = $db->getOne($sql);
		
		if (!isset($file_party_id)) {
			$final["message"] = "文件中业务组织数据库中无记录".$in_parity_name[0];
		}else{
			if ($file_party_id != $_SESSION["party_id"]){
				$final["message"] = "文件中业务组织不正确";;
			}
		}
	}
	
	
	
	$error_barcodes = array (); // 找不到商品的barcode
	if ($final ['message'] == "") {
		foreach ( $rowset as $row ) {
			if ($row ['barcode'] == "" || $row['status'] == "") {
				$final ['message'] = "缺少商品barcode或者库存状态";
				break;
			} else {
				$status_id = "";
				if ( strcmp("正式库", $row['status']) == 0 || strcmp("INV_STTS_AVAILABLE", $row['status']) == 0){
					$status_id = "INV_STTS_AVAILABLE";
				}
				elseif (strcmp("二手库", $row['status']) == 0 || strcmp("INV_STTS_USED", $row['status']) == 0) {
					$status_id = "INV_STTS_USED";
				}
				/*
				 * 注意  ecs_goods表和ecs_goods_style表中，都有barcode，两个barcode可能不一致，以egs中为准，如果egs中没有，那么就用eg中的
				 */
				$sql = "select barcode from ecshop.ecs_goods_style where barcode = '{$row ['barcode']}' and is_delete=0";
				$barcodecheck = $db->getOne($sql);
				if (!isset($barcodecheck)){
					$sql = "
					select eg.goods_id, eg.goods_name, es.style_id, es.color, ii.quantity_on_hand_total, ii.quantity_on_hand,
					ii.unit_cost, ii.status_id, eg.barcode 
					from romeo.inventory_item ii
					inner join romeo.product_mapping pm on pm.product_id = ii.product_id
					inner join ecshop.ecs_goods eg on eg.goods_id = pm.ecs_goods_id
					left join ecshop.ecs_style es on es.style_id = pm.ecs_style_id
					left join ecshop.ecs_goods_style egs on egs.goods_id = eg.goods_id and egs.is_delete=0
					where eg.barcode = '{$row ['barcode']}' and ii.STATUS_ID = '{$status_id}'
					and eg.goods_party_id = '{$party_id}'";
				}else{
					$sql = "
					select eg.goods_id, eg.goods_name, es.style_id, es.color, ii.quantity_on_hand_total, ii.quantity_on_hand,
					ii.unit_cost, ii.status_id, egs.barcode 
					from romeo.inventory_item ii
					inner join romeo.product_mapping pm on pm.product_id = ii.product_id
					inner join ecshop.ecs_goods eg on eg.goods_id = pm.ecs_goods_id
					left join ecshop.ecs_style es on es.style_id = pm.ecs_style_id
					left join ecshop.ecs_goods_style egs on egs.goods_id = eg.goods_id and egs.is_delete=0
					where egs.barcode = '{$row ['barcode']}' and ii.STATUS_ID = '{$status_id}'
					and eg.goods_party_id = '{$party_id}'";
				}
				$result_all = $db->getAll ( $sql );

				if (! isset($result_all) ) {
					$error_barcodes [] = "barcode为" . $row ['barcode'] . "的商品: 找不到记录,也许您选择的分公司不对<br>";
				}
				$countok = 0; // 满足订单价格的库存总数，因为同一个product，同一个价格，会有很多库存item
				foreach ( $result_all as $val ) {
					if (floatval ( $val ['unit_cost'] ) == floatval ( $row ['price'] )) {
						$countok = $countok + intval( $val ['quantity_on_hand_total']);
					}
				}
				if (!isset($result_all [0] ["style_id"])){
					$result_all [0] ["style_id"] = 0;
					$result_all [0] ["color"] = "无颜色";
				}
				
				$returnarr = array (
						"goods_id" => $result_all [0] ["goods_id"],
						"style_id" => $result_all [0] ["style_id"],
						"goods_name" => $result_all [0] ["goods_name"],
						"style_name" => $result_all [0] ["color"],
						"barcode_name" => $result_all [0] ["barcode"],
						"status_id" => $result_all [0] ["status_id"],
						"count" => $row ["count"],
						"price" => $row ["price"],
						"amount" => $row["amount"],
						"reason" => $row["reason"] 
				);
				if ($countok > 0)
					if (intval ( $row ['count'] ) < 0) {
						if ($countok < abs ( $row ['count'] )) {
							$error_barcodes [] = "商品 " . $returnarr['goods_name'] . ": 您选定的价格" . $row ['price'] . "库存数量".$countok."不能满足".$row['count']."<br>";
						} else {
							array_push ( $add_goods, $returnarr );
						}
					} else {
						array_push ( $add_goods, $returnarr );
					}
				else {
					$error_barcodes [] = "商品 " . $row ['barcode'] . ": 您选定的价格" . $row ['price'] . "在您选定的".$returnarr['status_id']."数据库中没有满足的<br>";
				}
			}
		}
	}
	
	foreach ( $error_barcodes as $val ) {
		$final ["error_item"] = $final ["error_item"] . $val;
	}
	
	foreach ( $add_goods as $add_good ) {
		array_push ( $final ['content'], $add_good );
	}
	
	QLog::log ( $final ['message'] );
	
	/* var_dump($final); */
	echo $json->encode($final);
}
?>

