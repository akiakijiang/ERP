<?php
require_once(ROOT_PATH. 'admin/includes/lib_filelock.php');

/**
 * 修改数据函数库
 */
function autoModifyAtpAndQoh()
{
	$start_time = microtime(true);
	// 加锁
	$lock_name = 'ModifyAtpAndQoh';
    $lock_file_name = get_file_lock_path($lock_name, 'ModifyAtpAndQoh');
    $lock_file_point = fopen($lock_file_name, "w+");
    $would_block = false;
    if(!flock($lock_file_point, LOCK_EX|LOCK_NB, $would_block)){
    	fclose($lock_file_point);
    	echo("上次修改还在进行，请稍后 :".$lock_name);
    	return;
    }
    
    try {
    	
		$qoh_list = get_qoh_error_products();
		
		if(!empty($qoh_list)) {
			var_dump('qoh_list:');var_dump(count($qoh_list));var_dump($qoh_list);
			foreach ($qoh_list as $qoh) {
			    $result = create_modify_qoh_inventory($qoh['product_id'],$qoh['facility_id']);
			    print_r($result['message']);
			    Qlog::log($result['message']);
			}
		}
		
		$atp_list = get_atp_error_products();
		if(!empty($atp_list)) {
			var_dump('atp_list:');var_dump(count($atp_list));var_dump($atp_list);
			foreach ($atp_list as $atp) {
			    $result = create_modify_atp_inventory($atp['product_id'],$atp['facility_id']);
			    print_r($result['message']);
			    Qlog::log($result['message']);
			}
		}

    } 
    catch (Exception $e) {
	    echo  "ModifyAtpAndQoh: ". " [". date('c') ."]异常: ".$e->getMessage()."\n";
	}
	
	flock($lock_file_point, LOCK_UN);
    fclose($lock_file_point);
    
    $end_time = microtime(true);
    echo("本次批次修改qoh，atp结束，耗时:".($end_time-$start_time)."\n");
	
}

function get_qoh_error_products() {
	global $db;
	$sql = "select im.product_id,im.facility_id,  im.inventory_summary_id,  
	 im.stock_quantity,ifnull(sum(ii.quantity_on_hand_total),0) as item_sum
     from romeo.inventory_summary im
     left join romeo.inventory_item ii ON im.product_id = ii.product_id and im.facility_id = ii.facility_id
     and im.status_id = ii.status_id
     where im.status_id = 'INV_STTS_AVAILABLE'
     and im.product_id is not null and im.facility_id is not null
     group by im.product_id, im.facility_id 
   having stock_quantity <>item_sum ";
   $lists = $db->getAll($sql);
   
   return $lists;
}


 /**
  *  修改qoh
  */
 function create_modify_qoh_inventory($product_id,$facility_id,$status_id='INV_STTS_AVAILABLE'){
 	global $db;
 	$result = array();
 	$result['success'] = false;
 	$result['message'] = " 修改qoh失败facility_id:".$facility_id." product_id:".$product_id." \n";
 	$sql_qoh = "select im.product_id,im.facility_id,im.available_to_reserved,im.inventory_summary_id,im.stock_quantity as num_summary,ifnull(sum(ii.quantity_on_hand_total),0) as num_item
		from romeo.inventory_summary im
		left join romeo.inventory_item ii ON im.product_id = ii.product_id
		AND ii.facility_id = im.facility_id AND ii.status_id = im.status_id
		WHERE  im.facility_id = '{$facility_id}' and im.product_id = '{$product_id}'
		AND im.status_id = '{$status_id}'
		group by im.product_id limit 1
 	";
	$qoh= $db->getRow($sql_qoh);

 	if(!empty($qoh)){
 	  $inventory_summary_id = $qoh['inventory_summary_id'];
 	  $qoh_diff = $qoh['num_summary'] - $qoh['num_item'];
 	  if($qoh_diff != 0){
	 	  $sql_update_qoh = "update romeo.inventory_summary set STOCK_QUANTITY=STOCK_QUANTITY-{$qoh_diff} where inventory_summary_id='{$inventory_summary_id}' limit 1 ";
	 	  $db->query($sql_update_qoh);
 	      $result['success'] = true;
 		  $result['message'] = " 修改qoh成功facility_id:".$facility_id." product_id:".$product_id." before: ". $qoh['num_summary']." after:". ($qoh['num_summary']-$qoh_diff)."\n";
 	    }
 	}
 	return $result;
 }
 
 	
function get_atp_error_products() {
	global $db;
	$sql = "select im.product_id,im.facility_id, im.inventory_summary_id, im.stock_quantity,im.available_to_reserved,
	       ifnull(sum(if(ird.status = 'Y',ird.reserved_quantity,0)),0) as reserved,
	       ( im.available_to_reserved + sum(if(ird.status = 'Y',ird.reserved_quantity,0)) -  im.stock_quantity) as diff
		   from romeo.inventory_summary im
		   left join romeo.order_inv_reserved_detail ird  ON im.product_id = ird.product_id 
	                 and im.facility_id = ird.facility_id and  im.status_id = ird.status_id
		   where im.status_id = 'INV_STTS_AVAILABLE' 
	       -- 排除发货完但是还没还原预定的订单
		   and not exists (select 1 from ecshop.ecs_order_info oi where oi.order_id = ird.order_id 
	       and oi.shipping_status in(1,2,3,8,9,11,12)
	       ) 
	       and im.product_id is not null and im.facility_id is not null
		   group by im.product_id, im.facility_id 
	       having diff <>0";
   $lists = $db->getAll($sql);
   
   return $lists;
}


 /**
  * 单个修改atp
  */
 function create_modify_atp_inventory($product_id,$facility_id){
 	global $db;
 	$result = array();
 	$result['success'] = false;
 	$result['message'] = " 修改atp失败facility_id:".$facility_id." product_id:".$product_id." \n";
 	$sql = "select im.product_id,im.facility_id,  im.inventory_summary_id,  im.stock_quantity,im.available_to_reserved
		from romeo.inventory_summary im
		where im.facility_id = '{$facility_id}' and im.status_id = 'INV_STTS_AVAILABLE' and im.product_id = '{$product_id}'
 	";

	$atp= $db->getRow($sql);
 	$sql = "select ifnull(sum(ird.reserved_quantity),0) as reserved_amount
			from romeo.order_inv_reserved_detail ird
			where not exists (select 1 from ecshop.ecs_order_info oi where oi.order_id = ird.order_id and oi.shipping_status in(1,2,3,8,9,11,12)) and ird.reserved_quantity <> 0 
					and ird.status = 'Y' and ird.facility_id = '{$facility_id}'  and ird.product_id = '{$product_id}'
			group by ird.product_id,ird.facility_id
 	";
 	
 	$reserved= $db->getRow($sql);
 	if(empty($reserved)){
 		$reserved['reserved_amount'] = 0;
 	}

 	$diff = $atp['available_to_reserved'] + $reserved['reserved_amount'] - $atp['stock_quantity'];
 	
 	if($diff != 0){
 	  $INVENTORY_SUMMARY_ID = $atp['inventory_summary_id'];
      $sql_update_atp = "update romeo.inventory_summary set AVAILABLE_TO_RESERVED=AVAILABLE_TO_RESERVED-{$diff} where INVENTORY_SUMMARY_ID='{$INVENTORY_SUMMARY_ID}' limit 1 ";
   	  $db->query($sql_update_atp);
   	  $result['success'] = true;
	  $result['message'] = " 修改atp成功:facility_id:".$facility_id." product_id:".$product_id." before: ". $atp['available_to_reserved']." after:". ($atp['available_to_reserved']-$diff)." \n";
 	}
 	return $result;
 }
	 
?>