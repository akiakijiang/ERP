<?php
/*
 * Created on 2011-8-27
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
 define('IN_ECS', true);

 require('../includes/init.php');
 require_once('../function.php');
 require_once(ROOT_PATH . 'includes/cls_json.php');
 require_once("RomeoApi/lib_inventory.php");
 require_once(ROOT_PATH . 'RomeoApi/lib_supplier_return.php');
 require_once (ROOT_PATH . 'includes/debug/lib_log.php');

 $act = $_POST['act'];
 
 if ('export_return_goods' == $act) {
 // 检查当前商品库存
	$order_goods_id = $_REQUEST['goods_id'] ;
    $order_style_id = $_REQUEST['style_id'] ;
	$original_provider_id = $_REQUEST['original_provider_id'];
    $facility_id = $_REQUEST['facility_id'] ;
            
    $status_id = $_REQUEST['status_id'] ;
    $purchase_unit_price = $_REQUEST['purchase_paid_amount'] ;
    // 检查商品是否串号控制
    if (!function_exists('get_goods_item_type')){
     	require_once("admin/includes/lib_goods.php");	
    }
    // SQL
    $cond = '';
    if(!empty($facility_id)){
       $cond = $cond . 'and ii.facility_id = '.$facility_id;
    }
    if(!empty($product_id)){
       $cond = $cond . 'ii.product_id = '.$product_id;
    }
    if (!empty($original_provider_id)) {
       $cond = $cond . 'and ii.provider_id = ' . $original_provider_id ;
    }
    if (!empty($purchase_unit_price)) {
       $cond = $cond . ' and ii.unit_cost = ' . $purchase_unit_price ;
    }

            //转为以新库存为标准
    $product_id = getProductId($order_goods_id,$order_style_id);
            
     $sql = "
		       select ifnull(og.goods_name,p.product_name) as goods_name, ifnull(og.goods_id,pm.ecs_goods_id) as goods_id, 
		              ifnull(og.style_id,pm.ecs_style_id) as style_id, ii.unit_cost as purchase_paid_amount, 
		              ii.status_id as is_new, ii.inventory_item_acct_type_id as order_type, ifnull(ii.provider_id,'432') as provider_id, ifnull(pr.provider_name,'自己库存') as provider_name, f.facility_name, ii.quantity_on_hand_total as storage_amount
			   from romeo.inventory_item_detail iid
			   inner join romeo.inventory_item ii on ii.inventory_item_id = iid.INVENTORY_ITEM_ID
			   left join ecshop.ecs_order_goods og on og.order_id = convert(iid.order_id,unsigned) and og.goods_id = '{$order_goods_id}' and og.style_id = '{$order_style_id}'
			   left join ecshop.ecs_provider pr on pr.provider_id = ii.provider_id 
			   inner join romeo.facility f on f.facility_id = ii.facility_id
			   left join romeo.product_mapping pm on pm.product_id = ii.product_id
			   left join romeo.product p on p.product_id = pm.product_id
			   where iid.quantity_on_hand_diff > 0 and ii.status_id in('INV_STTS_AVAILABLE','INV_STTS_USED','INV_STTS_DEFECTIVE')
			         and ii.quantity_on_hand_total > 0
			         $cond
			   group by iid.inventory_item_id, ii.product_id, ii.unit_cost, ii.status_id, ii.inventory_item_type_id
	        ";
     $ret_items = $db -> getAll($sql);
            
     $status_map = array('INV_STTS_AVAILABLE' => 'NEW', 'INV_STTS_USED' => 'SECOND_HAND', 'INV_STTS_DEFECTIVE' => 'DISCARD') ;

     $array_ret_item = array();
 	 if (!empty($ret_items)) {
         $array_ret_items = array();
         //将搜索出来的汇总下
         foreach($ret_items as $item){
            if (!array_key_exists($item['purchase_paid_amount'].$item['is_new'].$item['order_type'].$item['provider_id'],$array_ret_items)){
            	$array_ret_items[$item['purchase_paid_amount'].$item['is_new'].$item['order_type'].$item['provider_id']] = $item;
            }else{
            	$array_ret_items[$item['purchase_paid_amount'].$item['is_new'].$item['order_type'].$item['provider_id']]['storage_amount'] += $item['storage_amount'];
            }
         }
         foreach($array_ret_items as $array_item){
            $array_item['is_new'] = $status_map[$array_item['is_new']];
            $array_ret_item[] = $array_item;
    	}
    }
	header("Content-type:application/vnd.ms-excel");
	header("Content-Disposition:filename=" . iconv("UTF-8","GB18030","供应商退货申请（-gt）清单") . ".csv");
	$smarty->assign('goods_list', $array_ret_item);
	$out = $smarty->fetch('supplier_return/gt_apply_list_csv.htm');
	echo iconv("UTF-8","GB18030", $out);
 }
?>
