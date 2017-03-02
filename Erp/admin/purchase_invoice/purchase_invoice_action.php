<?php
/**
 * 处理逻辑的页面
 *
 */
define('IN_ECS', true);
require('../includes/init.php');
admin_priv("cw_review_purchase_invoice", "cw_review_purchase_invoice_plus", "cg_edit_purchase_invoice");
require(ROOT_PATH . 'admin/function.php');
require_once(ROOT_PATH . 'RomeoApi/lib_soap.php');
require_once(ROOT_PATH . "RomeoApi/lib_inventory.php");

$action = $_POST['action'];

$purchase_invoice_soapclient = soap_get_client('PurchaseInvoiceService');

switch ($action) {
	case 'purchase_invoice_request_add':	// 添加开票清单
		$provider_id = intval($_POST['provider_id']);
		$note = trim($_POST['note']);
		if ($provider_id <= 0) {
			die('供应商非法');
		}
		
		try {
			$result = $purchase_invoice_soapclient->createPurchaseInvoiceRequest(array("arg0"=>"{$_SESSION['admin_name']}", "arg1"=>"$provider_id", "arg2"=>"$note"));
			
			if ($result->return->status == "OK") {
				$info = "操作成功";
			} else {
				$info = $result->return->info;
			}
			$purchase_invoice_request_id = $result->return->result->anyType;			
			
			$back = "{$WEB_ROOT}admin/purchase_invoice/purchase_invoice_request_detail.php?purchase_invoice_request_id={$purchase_invoice_request_id}";
			
		} catch (Exception $ex) {
			$info = $ex->faultstring;
		}
		alert_back($info, $back);
		die();
		break;
		
	case 'purchase_invcoie_request_edit':			// 编辑开票清单
		$purchase_invoice_request_id = trim($_POST['purchase_invoice_request_id']);
		$provider_id = intval($_POST['provider_id']);
		$type_id = trim($_POST['type_id']);
		$note = trim($_POST['note']);
		if ($provider_id <= 0) {
			die('供应商非法');
		}
		try {
			$result = $purchase_invoice_soapclient->editPurchaseInvoiceRequest(array("arg0"=>"$purchase_invoice_request_id", "arg1"=>"$provider_id", "arg2"=>"$note", "arg3"=>"$type_id"));
			
			if ($result->return->status == "OK") {
				$info = "操作成功";
			} else {
				$info = $result->return->info;
			}	
		} catch (Exception $ex) {
			$info = $ex->faultstring;
		}
		
		$back = $_POST['back'] ? $_POST['back'] : "{$WEB_ROOT}admin/purchase_invoice/purchase_invoice_request_detail.php?purchase_invoice_request_id={$purchase_invoice_request_id}";
		alert_back($info, $back);
		
		die();
		break;
		
	case 'purchase_invoice_request_item_add':		// 添加开票清单明细	
		$purchase_invoice_request_id = trim($_POST['purchase_invoice_request_id']);
		$transaction_ids = $_POST['transaction_id'];
		$index_list = $_POST['index_add'];
		if (is_array($index_list)) {
			foreach ($index_list as $index) {
				$product_id = $_POST["product_id_{$index}"];
				$serial_number = $_POST["serial_number_{$index}"];
				$fixed_cost = $_POST["fixed_cost_{$index}"];
				$amount = $_POST["amount_{$index}"];
				$transaction_id = $_POST["transaction_id_{$index}"];
				$order_sn = $_POST["order_sn_{$index}"];
				$sql = "select og.added_fee from 
						romeo.inventory_transaction it
						inner join romeo.inventory_item_detail iid on iid.inventory_transaction_id = it.inventory_transaction_id
						inner join ecshop.ecs_order_goods og on og.rec_id = cast(iid.order_goods_id as unsigned) 
						where it.inventory_transaction_id = '{$transaction_id}'";
				$added_fee = $db->getOne($sql);
				
				$params[] = array("arg0"=>"$purchase_invoice_request_id", "arg1"=>"$product_id", "arg2"=>"$transaction_id", "arg3"=>"$serial_number", 
								"arg4"=>"$fixed_cost", "arg5"=>"$amount", "arg6"=>"$order_sn","arg7" => "$added_fee");
			}
			try {
				$result = $purchase_invoice_soapclient->addPurchaseInvoiceRequestItem(array("arg0"=>json_encode($params)));
				if ($result->return->status == "OK") {
					$info = "操作成功";
				} else {
					$info = $result->return->info;
				}
			} catch (Exception $ex) {
				$info = $ex->faultstring;
			}
		}

		$back = $_POST['back'] ? $_POST['back'] : "{$WEB_ROOT}admin/purchase_invoice/purchase_invoice_request_detail.php?purchase_invoice_request_id={$purchase_invoice_request_id}";
		alert_back($info, $back);
		die();
		break;
	
	case 'purchase_invoice_add':			// 添加发票
		$invoice_no = trim($_POST['invoice_no']);
		$provider_id = intval($_POST['provider_id']);
		$invoice_date = trim($_POST['invoice_date']);
		$note = trim($_POST['note']);
		
		if ($provider_id <= 0) {
			die('供应商非法');
		}
		if(strlen($invoice_no) > 120){
				die('发票号长度不能大于120');
		}
		try {
			$result = $purchase_invoice_soapclient->createPurchaseInvoice(array("arg0"=>"{$_SESSION['admin_name']}", "arg1"=>"$invoice_no", "arg2"=>"$provider_id", "arg3"=>"$note", "arg4"=>"NORMAL", "arg5"=>"$invoice_date"));
			if ($result->return->status == "OK") {
				$info = "操作成功";
			} else {
				$info = $result->return->info;
			}
		} catch (Exception $ex) {
			$info = $ex->faultstring;
		}
		
		$back = "{$WEB_ROOT}admin/purchase_invoice/purchase_invoice_detail.php?invoice_no=$invoice_no";
		alert_back($info, $back);
		die();
		break;
		
	case 'purchase_invoice_edit':			// 编辑发票
		$invoice_no = trim($_POST['invoice_no']);
		$provider_id = intval($_POST['provider_id']);
		$invoice_date = trim($_POST['invoice_date']);
		$note = trim($_POST['note']);

		if ($provider_id <= 0) {
			die('供应商非法');
		}
		try {
			$result = $purchase_invoice_soapclient->editPurchaseInvoice(array("arg0"=>"$invoice_no", "arg1"=>"$provider_id", "arg2"=>"$note", "arg3"=>"NORMAL", "arg4"=>"$invoice_date"));
			if ($result->return->status == "OK") {
				$info = "操作成功";
			} else {
				$info = $result->return->info;
			}			
		} catch (Exception $ex) {
			$info = $ex->faultstring;
		}
		
		$back = "{$WEB_ROOT}admin/purchase_invoice/purchase_invoice_detail.php?invoice_no=$invoice_no";
		alert_back($info, $back);
		die();
		break;
		
	case 'purchase_invoice_item_add':		// 添加发票明细
		$goods_id = intval($_REQUEST['search_goods_id']);
		$style_id = intval($_REQUEST['search_goods_style_id']);
		$price = floatval($_REQUEST['price']);
		$amount = intval($_REQUEST['amount']);
		$invoice_no = trim($_REQUEST['invoice_no']);
		$product_id = getProductId($goods_id, $style_id);
		if ($product_id < 0) {
			die('商品id非法');
		}
		try {
			$result = $purchase_invoice_soapclient->addPurchaseInvoiceItem(array("arg0"=>"$invoice_no", "arg1"=>"$product_id", "arg2"=>"$amount", "arg3"=>"$price"));
			if ($result->return->status == "OK") {
				$info = "操作成功";
			} else {
				$info = $result->return->info;
			}			
		} catch (Exception $ex) {
			$info = $ex->faultstring;
		}
		
		
		$back = "{$WEB_ROOT}admin/purchase_invoice/purchase_invoice_detail.php?invoice_no=$invoice_no";
		alert_back($info, $back);
		die();
		break;
		
	case 'purchase_invoice_item_add_by_request':	// 根据开票清单添加商品
		$purchase_invoice_request_id = trim($_REQUEST['purchase_invoice_request_id']);
		$invoice_no = trim($_REQUEST['invoice_no']);
		
		try {
			$result = $purchase_invoice_soapclient->addPurchaseInvoiceItemByRequest(array("arg0"=>"$invoice_no", "arg1"=>"$purchase_invoice_request_id"));
			if ($result->return->status == "OK") {
				$info = "操作成功";
			} else {
				$info = $result->return->info;
			}			
		} catch (Exception $ex) {
			$info = $ex->faultstring;
		}			
		
		$back = "{$WEB_ROOT}admin/purchase_invoice/purchase_invoice_detail.php?invoice_no=$invoice_no";
		alert_back($info, $back);
		die();
		break;
		
	case 'purchase_invoice_status_change': 			// 发票状态变化
		$status = trim($_POST['status']);
		$invoice_no = trim($_POST['invoice_no']);
		try {
			switch ($status) {
				case 'CANCEL':
					// TODO
					break;
				case 'CONFIRM':
					$result = $purchase_invoice_soapclient->confirmPurhcaseInvoice(array("arg0"=>"$invoice_no", "arg1"=>"{$_SESSION['admin_name']}"));
					break;
				case 'CLOSE':
					$result = $purchase_invoice_soapclient->closePurchaseInvoice(array("arg0"=>"$invoice_no", "arg1"=>"{$_SESSION['admin_name']}"));
					break;
				default:
					break;
			}
			if ($result->return->status == "OK") {
				$info = "操作成功";
			} else {
				$info = $result->return->info;
			}			
		} catch (Exception $ex) {
			$info = $ex->faultstring;
		}
		
		
		$back = "{$WEB_ROOT}admin/purchase_invoice/purchase_invoice_detail.php?invoice_no=$invoice_no";
		
		alert_back($info, $back);
		break;
		
	case 'purchase_invoice_request_status_change':
		$status = trim($_POST['status']);
		$purchase_invoice_request_id = trim($_POST['purchase_invoice_request_id']);
		try {
			switch ($status) {
				case 'CANCEL':
					$result = $purchase_invoice_soapclient->cancelPurchaseInvoiceRequest(array("arg0"=>"$purchase_invoice_request_id", "arg1"=>"{$_SESSION['admin_name']}"));
					break;
				case 'CONFIRM':
					$result = $purchase_invoice_soapclient->confirmPurchaseInvoiceRequest(array("arg0"=>"$purchase_invoice_request_id", "arg1"=>"{$_SESSION['admin_name']}"));
					break;
				case 'CLOSE':
					$result = $purchase_invoice_soapclient->closePurchaseInvoiceRequest(array("arg0"=>"$purchase_invoice_request_id", "arg1"=>"{$_SESSION['admin_name']}"));
					break;
				default:
					break;			
			}
			if ($result->return->status == "OK") {
				$info = "操作成功";
			} else {
				$info = $result->return->info;
			}
		} catch (Exception $ex) {
			$info = $ex->faultstring;
		}
		
		
		$back = "{$WEB_ROOT}admin/purchase_invoice/purchase_invoice_request_detail.php?purchase_invoice_request_id={$purchase_invoice_request_id}";
		alert_back($info, $back);
		break;
		
	case 'purchase_invoice_item_delete':			// 删除采购发票明细
		$invoice_no = trim($_POST['invoice_no']);
		$purchase_invoice_item_id_list = $_POST['purchase_invoice_item_id'];
		if (is_array($purchase_invoice_item_id_list)) {
			foreach ($purchase_invoice_item_id_list as $purchase_invoice_item_id) {
				$params[] = array("arg0"=>"$purchase_invoice_item_id");
			}
			try {
				$result = $purchase_invoice_soapclient->deletePurchaseInvoiceItem(array("arg0"=>json_encode($params)));
				if ($result->return->status == "OK") {
					$info = "操作成功";
				} else {
					$info = $result->return->info;
				}
			} catch (Exception $ex) {
				$info = $ex->faultstring;
			}
		}
		$back = "{$WEB_ROOT}admin/purchase_invoice/purchase_invoice_detail.php?invoice_no=$invoice_no";
		alert_back($info, $back);
		break;
		
	case 'purchase_invoice_request_item_delete':		// 删除开票清单明细
		$purchase_invoice_request_id = trim($_POST['purchase_invoice_request_id']);
		$purchase_invoice_request_type_id = trim($_POST['purchase_invoice_request_type_id']);
		
		$index_list = $_POST['index_delete'];
		
		if (is_array($index_list)) {
			foreach ($index_list as $index) {
				if ($purchase_invoice_request_type_id == "AVERAGE") {
				    $purchase_invoice_request_item_id = intval($_POST["purchase_invoice_request_item_id_{$index}"]);
				    $product_id = intval($_POST["product_id_{$index}"]);
					$params[] = array("arg0"=>"$purchase_invoice_request_id", "arg1"=>"$product_id", "arg2"=>"$purchase_invoice_request_item_id");
				} elseif ($purchase_invoice_request_type_id == "ORIGINAL") {
				    $purchase_invoice_request_item_id = intval($_POST["purchase_invoice_request_item_id_{$index}"]);
					$params[] = array("arg0"=>"$purchase_invoice_request_item_id");
				}
			}
			try {
				if ($purchase_invoice_request_type_id == "AVERAGE") {
					$result = $purchase_invoice_soapclient->deletePurchaseInvoiceRequestItemByGroup(array("arg0"=>json_encode($params)));
				} elseif ($purchase_invoice_request_type_id == "ORIGINAL") {
					$result = $purchase_invoice_soapclient->deletePurchaseInvoiceRequestItem(array("arg0"=>json_encode($params)));
				}
				if ($result->return->status == "OK") {
					$info = "操作成功";
				} else {
					$info = $result->return->info;
				}				
			}catch (Exception $ex) {
				$info = $ex->faultstring;
			}
		}
		$back = $_POST['back'] ? $_POST['back'] : "{$WEB_ROOT}admin/purchase_invoice/purchase_invoice_request_detail.php?purchase_invoice_request_id={$purchase_invoice_request_id}";
		alert_back($info, $back);
		break;
		
	case 'purchase_invoice_request_item_matched_delete':			// 删除匹配信息
		$purchase_invoice_request_id = trim($_POST['purchase_invoice_request_id']);
		$purchase_invoice_request_type_id = trim($_POST['purchase_invoice_request_type_id']);
		$index_list = $_POST['index'];
		if (is_array($index_list)) {
			foreach ($index_list as $index) {
				if ($purchase_invoice_request_type_id == "AVERAGE") {
				    $product_id = intval($_POST["product_id_{$index}"]);
				    $purchase_invoice_request_item_id = intval($_POST["purchase_invoice_request_item_id_{$index}"]);
					$params[] = array("arg0"=>"$purchase_invoice_request_id", "arg1"=>"$product_id", "arg2"=>"$purchase_invoice_request_item_id");
				} elseif ($purchase_invoice_request_type_id == "ORIGINAL") {
                    $purchase_invoice_request_item_id = intval($_POST["purchase_invoice_request_item_id_{$index}"]);
					$params[] = array("arg0"=>"", "arg1"=>"$purchase_invoice_request_item_id");
				}
			}
			try {
				if ($purchase_invoice_request_type_id == "AVERAGE") {
					$result = $purchase_invoice_soapclient->deletePurchaseInvoiceItemMatchByProduct(array("arg0"=>json_encode($params)));
				} elseif ($purchase_invoice_request_type_id == "ORIGINAL") {
					$result = $purchase_invoice_soapclient->deletePurchaseInvoiceItemMatch(array("arg0"=>json_encode($params)));
				}
				if ($result->return->status == "OK") {
					$info = "操作成功";
				} else {
					$info = $result->return->info;
				}
			}catch (Exception $ex) {
				$info = $ex->faultstring;
			}
		}

		$back = $_POST['back'];
		alert_back($info, $back);	
		die();
		break;
		
	case 'purchase_invoice_item_match_add':							// 添加发票明细
		$purchase_invoice_request_item_match_add_id = trim($_POST['purchase_invoice_request_item_match_add_id']);
		$product_id = trim($_POST['product_id']);
		$amount = intval($_POST['amount']);
		$purchase_invoice_request_type_id = trim($_POST['purchase_invoice_request_type_id']);
		$purchase_invoice_request_id = trim($_POST['purchase_invoice_request_id']);
		$invoice_no = trim($_POST['search_invoice_no']);
		
		try {
			if ($purchase_invoice_request_type_id == "AVERAGE") {
				$result = $purchase_invoice_soapclient->createPurchaseInvoiceItemMatchByProduct(array("arg0"=>"$invoice_no", "arg1"=>"$product_id", "arg2"=>"$purchase_invoice_request_item_match_add_id", "arg3"=>"$amount", "arg4"=>"$purchase_invoice_request_id"));
			} elseif ($purchase_invoice_request_type_id == "ORIGINAL") {
				$result = $purchase_invoice_soapclient->createPurchaseInvoiceItemMatch(array("arg0"=>"$invoice_no", "arg1"=>"$product_id", "arg2"=>"$amount", "arg3"=>"$purchase_invoice_request_item_match_add_id"));
			}
			if ($result->return->status == "OK") {
				$info = "操作成功";
			} else {
				$info = $result->return->info;
			}			
		} catch (Exception $ex) {
			$info = $ex->faultstring;
		}		

		$back = "{$WEB_ROOT}admin/purchase_invoice/match.php?purchase_invoice_request_id={$purchase_invoice_request_id}";
		alert_back($info, $back);
		break;
		
	case 'purchase_invoice_request_input':			// 批量导入开票单
		/**
		 * 文件抬头
		 * PROVIDER_ID	INVENTORY_TRANSACTION_ID	PRODUCT_ID	SERIAL_NUMBER	QUANTITY	UNIT_COST	UNIT_NET_COST	UNIT_TAX	INVOICE_NO
		 */
		$info = "导入成功";
		$back = "{$WEB_ROOT}admin/purchase_invoice/purchase_invoice_request_input.php";
		
		if (is_uploaded_file($_FILES['input_file']['tmp_name'])) {
			$file = fopen($_FILES['input_file']['tmp_name'], 'r');
			$head = fgetcsv_reg($file);		// 过滤头部数据
			
			// 获取数据
//			$purchase_invoice_soapclient->createPurchaseInvoiceRequest(array("arg0"=>"{$_SESSION['admin_name']}", "arg1"=>"$provider_id", "arg2"=>"$note"));
			$purchase_invoice_request_list = array();
			$purchase_invoice_request = null;
			$last_row = "";
			while ($row = fgetcsv_reg($file)) {
				$row[0] = iconv("gbk", "utf-8", $row[0]);
				
				// 新建发票抬头
				if ($last_row[8] != $row[8]) {
					if ($purchase_invoice_request) {
						$purchase_invoice_request_list[] = $purchase_invoice_request;
					}
					$purchase_invoice_request = array();
					$purchase_invoice_request['provider_name'] = $row[0];
					$purchase_invoice_request['provider_id'] = get_provider_id_by_name($row[0]);
					$purchase_invoice_request['note'] = "批量导入，发票号:$row[8]";
					$purchase_invoice_request['item_list'] = array();
				} 
				
				$purchase_invoice_request_item = array();
				$purchase_invoice_request_item['transaction_id'] = $row[1];
				$purchase_invoice_request_item['product_id'] = $row[2];
				$purchase_invoice_request_item['serial_number'] = $row[3];
				$purchase_invoice_request_item['quantity'] = $row[4];
				$purchase_invoice_request_item['unit_cost'] = $row[5];
				$purchase_invoice_request['item_list'][] = $purchase_invoice_request_item;
				
				$last_row = $row;
			}
			if ($purchase_invoice_request) {
				$purchase_invoice_request_list[] = $purchase_invoice_request;
			}
			
			// 开始存储数据
			try {
				foreach ($purchase_invoice_request_list as $purchase_invoice_request) {
					// 添加开票单
					$result = $purchase_invoice_soapclient->createPurchaseInvoiceRequest(array("arg0"=>"{$_SESSION['admin_name']}", "arg1"=>"{$purchase_invoice_request['provider_id']}", "arg2"=>"{$purchase_invoice_request['note']}"));

					if ($result->return->status != "OK") {
						$info = $result->return->info;
						$ex = new Exception();
						$ex->faultstring = "创建索票单失败({$result->return->info})";
						throw $ex;
					}
					
					$purchase_invoice_request_id = $result->return->result->anyType;
					// 添加开票单明细
					$params = array();
					foreach ($purchase_invoice_request['item_list'] as $purchase_invoice_request_item) {
						$params[] = array("arg0"=>"$purchase_invoice_request_id", "arg1"=>"{$purchase_invoice_request_item['product_id']}", "arg2"=>"{$purchase_invoice_request_item['transaction_id']}", "arg3"=>"{$purchase_invoice_request_item['serial_number']}", "arg4"=>"{$purchase_invoice_request_item['unit_cost']}", "arg5"=>"{$purchase_invoice_request_item['quantity']}");
					}
					
					$result = $purchase_invoice_soapclient->addPurchaseInvoiceRequestItem(array("arg0"=>json_encode($params)));
					
					if ($result->return->status == "OK") {
						$info = "操作成功";
					} else {
						$info = $result->return->info;
					}
				}
			} catch (Exception $ex) {
				$info = $ex->faultstring;
			}
			
		}
		
		alert_back($info, $back);
		die();
		break;
				
	default:
		die();
		break;
}

?>