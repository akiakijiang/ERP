<?php
/**
 * 取得淘宝上出售的电教产品，并更新到distribution_product_mapping表，
 * 然后才好维护产品的运费模板
 */

define('IN_ECS', true);
require_once('../includes/init.php');
require_once('../distribution.inc.php');
require_once(ROOT_PATH. 'admin/includes/lib_taobao.php');
require_once(ROOT_PATH. 'RomeoApi/lib_soap.php');
$order_sn = $_REQUEST['order_sn'];
if ($order_sn != NULL){
	$sql_id = "SELECT order_id FROM ecs_order_info WHERE order_sn = '$order_sn'";
	$order_id = $db->getOne($sql_id);
	if ($order_id != null){
	/*ecs_order_info的内容*/
	$sql = "SELECT * FROM ecs_order_info where order_sn = '$order_sn'";
	$order_info = $db->getAll($sql);
	foreach ($order_info as  $order_info_list){
		foreach ($order_info_list as $key => $order_info_lists){
				$order_info_cname .= $key.","; 
				$order_info_content .= "'".$order_info_list[$key]."',";			
		}
	}
	$order_info_cname = substr($order_info_cname,0,strlen($order_info_cname)-1);
	$order_info_content = substr($order_info_content, 0,strlen($order_info_content)-1);
	$sql_replace = "REPLACE INTO ecshop.ecs_order_info($order_info_cname) VALUES($order_info_content);";
	$all = $sql_replace;
	//echo "<br>".$sql_replace;
	/*ecs_order_goods*/
	$sql_g = "SELECT * FROM ecs_order_goods where order_id = $order_id";
	$order_goods = $db->getAll($sql_g);
	foreach ($order_goods as $key1 => $order_goods_list){
		foreach ($order_goods_list as $key => $order_goods_lists){
				$order_goods_cname .= $key.","; 
				$order_goods_content .= "'".$order_goods_list[$key]."',";		
		}
		$order_goods_cname = substr($order_goods_cname,0,strlen($order_goods_cname)-1);
		$order_goods_content = substr($order_goods_content, 0,strlen($order_goods_content)-1);	
		$sql_goods_replace = "REPLACE INTO ecshop.ecs_order_goods($order_goods_cname) VALUES($order_goods_content);";
		//echo "<br>".$sql_goods_replace;
		$all .= $sql_goods_replace;
		$order_goods_cname = "";
		$order_goods_content = "";	
		/*ecs_goods表*/
		$goods_id = $order_goods[$key1]['goods_id'];
		$sql_goods = "SELECT * FROM ecs_goods where goods_id = $goods_id";
		$ecs_goods = $db->getAll($sql_goods);
		foreach ($ecs_goods as $ecs_goods_list){
			foreach ($ecs_goods_list as $key => $ecs_goods_lists){
					$ecs_goods_cname .= $key.","; 
					$ecs_goods_content .= "'".$ecs_goods_list[$key]."',";
			}
		$ecs_goods_cname = substr($ecs_goods_cname, 0, strlen($ecs_goods_cname)-1);
		$ecs_goods_content = substr($ecs_goods_content, 0, strlen($ecs_goods_content)-1);
		$sql_g_replace = "REPLACE INTO ecshop.ecs_goods($ecs_goods_cname) VALUES($ecs_goods_content);";
		$all .= $sql_g_replace;
		$ecs_goods_cname = "";
		$ecs_goods_content = "";
		}
		/*ecs_style表的内容*/
		$style_id = $order_goods[$key1]['style_id'];
		$sql_style = "SELECT * FROM ecs_style WHERE style_id = $style_id";
		$ecs_style = $db->getAll($sql_style);
		foreach ($ecs_style as $ecs_style_list) {
		   	foreach ($ecs_style_list as $key => $ecs_style_lists){
		   			$ecs_style_cname .= $key.",";
		   			$ecs_style_content .= "'".$ecs_style_list[$key]."',";
		   	}
		$ecs_style_cname = substr($ecs_style_cname, 0, strlen($ecs_style_cname)-1);
	 	$ecs_style_content = substr($ecs_style_content, 0, strlen($ecs_style_content)-1);
	 	$sql_style_replace = "REPLACE INTO ecshop.ecs_style($ecs_style_cname) VALUES($ecs_style_content);";
		//echo "<br>".$sql_style_replace;
		$all .= $sql_style_replace;
		$ecs_style_cname = "";
		$ecs_style_content = "";		   	
		}
		 	
		/*romeo.pruduct表的内容*/
		$sql_product_mapping = "SELECT * FROM romeo.product_mapping WHERE ecs_goods_id = $goods_id AND ecs_style_id = $style_id";
		$product_mapping = $db->getAll($sql_product_mapping);
		foreach ($product_mapping as $key1 => $product_mapping_list){
			foreach ($product_mapping_list as $key => $product_mapping_lists){
					$product_mapping_cname .= $key.",";
					$product_mapping_content .= "'".$product_mapping_list[$key]."',";
			}
			$product_mapping_cname = substr($product_mapping_cname, 0, strlen($product_mapping_cname)-1);
	 		$product_mapping_content = substr($product_mapping_content, 0, strlen($product_mapping_content)-1);
	 		$sql_product_mapping_replace = "REPLACE INTO romeo.product_mapping($product_mapping_cname) VALUES($product_mapping_content);";
	 		//echo  "<br>".$sql_product_mapping_replace;
	 		$all .= $sql_product_mapping_replace;
	 		$product_mapping_cname = "";
	 		$product_mapping_content = "";
	 		$product_id = $product_mapping[$key1]['PRODUCT_ID'];
	 		$sql_product = "SELECT * FROM romeo.product WHERE product_id = $product_id";
	 		$product = $db->getAll($sql_product);
	 		foreach ($product as $product_list){
	 			foreach ($product_list as $key => $product_lists){
	 				if (!in_array($key, array('PRIMARY_PRODUCT_CATEGORY_ID','QUANTITY_UOM_ID','DEPTH_UOM_ID','WIDTH_UOM_ID','AMOUNT_UOM_TYPE_ID','HEIGHT_UOM_ID','WEIGHT_UOM_ID'))){
	 					$product_cname .= $key.",";
	 					$product_content .= "'".$product_list[$key]."',";
	 				}
	 			}
	 			$product_cname = substr($product_cname, 0, strlen($product_cname)-1);
	 			$product_content = substr($product_content, 0, strlen($product_content)-1);
	 			$sql_product_replace = "REPLACE INTO romeo.product($product_cname) VALUES($product_content);";
	 			//echo "<br>".$sql_product_replace;
	 			$all .= $sql_product_replace;	
	 			$product_cname = "";
	 			$product_content = "";
	 		}
		}
	}
	/*order_attibute*/
	$sql_attribute = "SELECT * FROM order_attribute WHERE order_id = $order_id";
	$order_attribute = $db->getAll($sql_attribute);
	foreach ($order_attribute as $order_attribute_list){
		foreach ($order_attribute_list as $key => $order_attribute_lists){
				$order_attribute_cname .= $key.",";
				$order_attribute_content .= "'".$order_attribute_list[$key]."',";
		}
		$order_attribute_cname = substr($order_attribute_cname, 0, strlen($order_attribute_cname)-1);
		$order_attribute_content = substr($order_attribute_content, 0, strlen($order_attribute_content)-1);
		$sql_attribute_replace = "REPLACE INTO ecshop.order_attribute($order_attribute_cname) VALUES($order_attribute_content);";
		$all .=$sql_attribute_replace;
		$order_attribute_cname = "";
		$order_attribute_content = "";
	}
	//foreach ($order_attribute as $order_attribute_list)
	/*order_action表内容*/
	$sql_action = "SELECT * FROM ecs_order_action WHERE order_id = $order_id";
	$ecs_order_action = $db->getAll($sql_action);
	foreach ($ecs_order_action as $ecs_order_action_list){
		foreach ($ecs_order_action_list as $key => $ecs_order_action_lists){
				$ecs_order_action_cname .= $key.",";
				$ecs_order_action_content .= "'".$ecs_order_action_list[$key]."',";
		}
		$ecs_order_action_cname = substr($ecs_order_action_cname, 0, strlen($ecs_order_action_cname)-1);
		$ecs_order_action_content = substr($ecs_order_action_content, 0, strlen($ecs_order_action_content)-1);
		$sql_action_replace = "REPLACE INTO ecshop.ecs_order_action($ecs_order_action_cname) VALUES($ecs_order_action_content);";
		//echo "<br>".$sql_action_replace;
		$all .= $sql_action_replace;
		$ecs_order_action_cname = "";
		$ecs_order_action_content = "";
	}
		/*shipment表内容*/
	$sql_s = "SELECT shipment_id FROM romeo.order_shipment WHERE order_id = $order_id";
	$shipment_iid = $db->getOne($sql_s);
	if($shipment_iid != NULL){
		$sql_shipment = "SELECT * FROM romeo.shipment WHERE shipment_id = $shipment_iid";
		$shipment = $db->getAll($sql_shipment);
		foreach ($shipment as $shipment_list){
			foreach ($shipment_list as $key => $shipment_lists){
				if ($shipment_list[$key]['PICKLIST_LOCATION_SEQUENCE_ID'] != NULL){
					$shipment_cname .= $key.",";
					$shipment_content .= "'".$shipment_list[$key]."',";
				}
			}
			$shipment_cname = substr($shipment_cname, 0, strlen($shipment_cname)-1);
			$shipment_content = substr($shipment_content, 0, strlen($shipment_content)-1);
			$sql_shipment_replace = "REPLACE INTO romeo.shipment($shipment_cname) VALUES($shipment_content);";	
			$all1 .=  "\n\n".$sql_shipment_replace;
		}
		//echo "<br>".$sql_shipment_replace;
		//echo "<br>".$sql_order_shipment_replace;
	/*romeo.order_shipment 表内容*/
		$sql_order_shipment = "SELECT * FROM romeo.order_shipment WHERE order_id = $order_id";
		$order_shipment = $db->getAll($sql_order_shipment);
		foreach ($order_shipment as $key1 => $order_shipment_list){
			foreach ($order_shipment_list as $key => $order_shipment_lists){
					$order_shipment_cname .= $key.",";
					$order_shipment_content .= "'".$order_shipment_list[$key]."',"; 
			}
			$order_shipment_cname = substr($order_shipment_cname, 0, strlen($order_shipment_cname)-1);
			$order_shipment_content = substr($order_shipment_content, 0, strlen($order_shipment_content)-1);
			$sql_order_shipment_replace = "REPLACE INTO romeo.order_shipment($order_shipment_cname) VALUES($order_shipment_content);";
			$all1 .= $sql_order_shipment_replace;
			$order_shipment_cname = "";
			$order_shipment_content = "";
		}
	}
}
	
else {
	 echo  "<script language=\"JavaScript\">\r\n";   echo " alert(\"请输入一个订单号\");\r\n";   echo " history.back();\r\n";   echo "</script>";   exit;   		 
}
	
}
?>

<!-- <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"> -->
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>订单导入</title>
</head>
<body>
<form method="get">
<span style="display:block;margin-top:40px;">  
订单号：<input type="" name="order_sn" value = "<?php echo $order_sn;?>"><input type="submit" value="搜索" /><br></form>
<textarea rows="40" cols="150" onpropertychange="if(this.scrollHeight>80) this.style.posHeight=this.scrollHeight+5" ><?php echo $all.$all1?></textarea>
</span>
</body>
</html>