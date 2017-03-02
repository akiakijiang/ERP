<?php
  
  require_once (ROOT_PATH . 'includes/debug/lib_log.php');
  if (!function_exists('db_create_in')){
     	require_once(ROOT_PATH."includes/lib_common.php");	
  }
	
  /**
   *   获取  -gt 申请量  
   */
function getRequest_amount($product_ids,$barcodes,$facility_id,$batch_sn,$is_new = null){
    global $db;
  	$cond = '';
  	if(empty($facility_id)) return null;
    $cond .= "AND oi.facility_id = '{$facility_id}'";
    $one_month_ago = date('Y-m-d 00:00:00',strtotime('-1 month')); 
    $cond .= " AND oi.order_time > '{$one_month_ago}' and rr.created_stamp>'{$one_month_ago}' "; 

  	if( empty($product_ids) && empty($barcodes) ){
  		return null;
  	}
  	if( !empty($product_ids) && !empty($barcodes)){
  		$cond .= " AND ( pm.product_id ".db_create_in($product_ids).' OR IFNULL(gs.barcode,g.barcode)'.db_create_in($barcodes).')';
  	}else{
  		if(!empty($product_ids)){
  			$cond .= "AND  pm.product_id ".db_create_in($product_ids);
  		}else if(!empty($barcodes)){
  			$cond .= " AND  IFNULL(gs.barcode,g.barcode) ".db_create_in($barcodes);
  		}
  	}
    if(!empty($batch_sn)){
      $cond .=" AND rr.batch_sn = '".$batch_sn."'";
    }
    if(isset($is_new)){
      $cond .=" AND og.status_id ='{$is_new}' "; 
    }
		 
	$sql = " SELECT 
              oi.facility_id , oi.order_sn,oi.order_id,oi.order_type_id ,
              pm.product_id ,og.status_id as is_new , og.rec_id as order_goods_id , 
              og.goods_price,  og.goods_number, rr.check_status,
              btm.batch_gt_sn , rr.batch_sn , 
              IFNULL(rr.ORIGINAL_SUPPLIER_ID,'432') as provider_id, 
              IFNULL(gs.barcode,g.barcode) as barcode, 
              rr.purchase_unit_price as purchase_paid_amount, 
              rr.supplier_return_id , 
              CONCAT(IFNULL(gs.barcode,g.barcode),'-',IF(og.status_id='INV_STTS_AVAILABLE','良品','不良品')) as goods_status,
              og.goods_number+IFNULL(SUM(iid.QUANTITY_ON_HAND_DIFF),0) as storage_amount 
		from romeo.supplier_return_request rr  force index(CREATED_STAMP)
		LEFT JOIN romeo.supplier_return_request_gt gt ON gt.supplier_return_id = rr.supplier_return_id 
		LEFT JOIN ecshop.ecs_order_info oi ON oi.order_sn = gt.supplier_return_gt_sn 
		left join  ecshop.ecs_order_goods og on og.order_id = oi.order_id
        LEFT JOIN ecshop.ecs_batch_gt_mapping btm on oi.order_id = btm.order_id
        left join romeo.product_mapping pm ON og.goods_id = pm.ECS_GOODS_ID and ifnull(og.style_id,0)= pm.ECS_STYLE_ID 
        LEFT JOIN ecshop.ecs_goods_style gs on og.goods_id = gs.goods_id  and og.style_id = gs.style_id and gs.is_delete=0
        LEFT JOIN ecshop.ecs_goods g on og.goods_id = g.goods_id 
        LEFT JOIN romeo.inventory_item_detail iid ON  CONVERT (og.rec_id  USING utf8) = iid.order_goods_id AND iid.cancellation_flag = 'N' 
        WHERE  rr.status  not in ('CANCELLATION', 'COMPLETION')  AND rr.CHECK_STATUS = 'PASS' 
        {$cond} GROUP BY og.rec_id ";
     
     
     /**  原sql
         $sql = " SELECT
         oi.facility_id , oi.order_sn,oi.order_id,oi.order_type_id ,
         pm.product_id ,og.status_id as is_new , og.rec_id as order_goods_id ,
         og.goods_price,  og.goods_number, rr.check_status,
         btm.batch_gt_sn , rr.batch_sn ,
         IFNULL(rr.ORIGINAL_SUPPLIER_ID,'432') as provider_id,
         IFNULL(gs.barcode,g.barcode) as barcode,
         rr.purchase_unit_price as purchase_paid_amount,
         rr.supplier_return_id ,
         CONCAT(IFNULL(gs.barcode,g.barcode),'-',IF(og.status_id='INV_STTS_AVAILABLE','良品','不良品')) as goods_status,
         og.goods_number+IFNULL(SUM(iid.QUANTITY_ON_HAND_DIFF),0) as storage_amount
         from  ecshop.ecs_order_goods og
         LEFT JOIN ecshop.ecs_order_info oi ON og.order_id = oi.order_id
         LEFT JOIN romeo.supplier_return_request_gt gt on oi.order_sn = gt.supplier_return_gt_sn
         LEFT JOIN ecshop.ecs_batch_gt_mapping btm on oi.order_id = btm.order_id
         LEFT JOIN  romeo.supplier_return_request rr ON gt.supplier_return_id = rr.supplier_return_id
         left join romeo.product_mapping pm ON og.goods_id = pm.ECS_GOODS_ID and ifnull(og.style_id,0)= pm.ECS_STYLE_ID
         LEFT JOIN ecshop.ecs_goods_style gs on og.goods_id = gs.goods_id  and og.style_id = gs.style_id
         LEFT JOIN ecshop.ecs_goods g on og.goods_id = g.goods_id
         LEFT JOIN romeo.inventory_item_detail iid ON  CONVERT (og.rec_id  USING utf8) = iid.order_goods_id AND iid.cancellation_flag = 'N'
         WHERE  rr.status  not in ('CANCELLATION', 'COMPLETION')  AND rr.CHECK_STATUS = 'PASS'
         {$cond} GROUP BY og.rec_id ";
      **/
        
			
    $start = microtime(true); 
    $ret = $db -> getAll($sql);
    $end = microtime(true);
    $time = $end-$start;
    QLog::log(' -gt get supplier return request  sql time : '.($time));
    //测试输出
//    QLog::log(' -gt get supplier return request  sql : '.($sql));
    if($time > 2){
      QLog::log(' -gt get supplier return request  sql : '.($sql));
    }
	  return $ret;
  }
  
   /**
   *   获取  -gt 申请量  
   */
function getRequest_amountNew(){
    global $db;
		 
	$sql = " SELECT 
              oi.facility_id , oi.order_sn,oi.order_id,oi.order_type_id ,
              pm.product_id ,og.status_id as is_new , og.rec_id as order_goods_id , 
              og.goods_price,  og.goods_number, rr.check_status,
              btm.batch_gt_sn , rr.batch_sn , 
              IFNULL(rr.ORIGINAL_SUPPLIER_ID,'432') as provider_id, 
              IFNULL(gs.barcode,g.barcode) as barcode, 
              rr.purchase_unit_price as purchase_paid_amount, 
              rr.supplier_return_id , 
              CONCAT(IFNULL(gs.barcode,g.barcode),'-',IF(og.status_id='INV_STTS_AVAILABLE','良品','不良品')) as goods_status,
              og.goods_number+IFNULL(SUM(iid.QUANTITY_ON_HAND_DIFF),0) as storage_amount 
		from romeo.supplier_return_request rr  force index(CREATED_STAMP)
		LEFT JOIN romeo.supplier_return_request_gt gt ON gt.supplier_return_id = rr.supplier_return_id 
		LEFT JOIN ecshop.ecs_order_info oi ON oi.order_sn = gt.supplier_return_gt_sn 
		left join  ecshop.ecs_order_goods og on og.order_id = oi.order_id
        LEFT JOIN ecshop.ecs_batch_gt_mapping btm on oi.order_id = btm.order_id
        left join romeo.product_mapping pm ON og.goods_id = pm.ECS_GOODS_ID and ifnull(og.style_id,0)= pm.ECS_STYLE_ID 
        LEFT JOIN ecshop.ecs_goods_style gs on og.goods_id = gs.goods_id  and og.style_id = gs.style_id and gs.is_delete=0
        LEFT JOIN ecshop.ecs_goods g on og.goods_id = g.goods_id 
        LEFT JOIN romeo.inventory_item_detail iid ON  CONVERT (og.rec_id  USING utf8) = iid.order_goods_id AND iid.cancellation_flag = 'N' 
        WHERE  rr.status  not in ('CANCELLATION', 'COMPLETION')  AND rr.CHECK_STATUS != 'DENY' and rr.party_id= '{$_SESSION['party_id']}' 
        GROUP BY og.rec_id ";
			
    $start = microtime(true); 
    $ret = $db -> getAll($sql);
    $end = microtime(true);
    $time = $end-$start;
    QLog::log(' -gt get supplier return request  sql time : '.($time));
    //测试输出
//    QLog::log(' -gt get supplier return request  sql : '.($sql));
    if($time > 2){
      QLog::log(' -gt get supplier return request  sql : '.($sql));
    }
	  return $ret;
  }
   
   /**  得到商品的库存量  
    *   参数的值 可为单值 也可以是数组 
    *  $product_ids  两项至少有一项不为空 
    *  $barcodes 
    *  $facility_id  不能为空 
    *  $party_id     可为空 
    *  $provider_ids  可为空 
    *  $unit_cost ii.unit_cost 可为空 
    *  $batch_sn ii.batch_sn 可为空 生产批次号 
    */
  function getInventoryBy($product_ids,$barcodes,$facility_ids,$party_ids,$provider_ids,$unit_cost,$batch_sn,$is_new = null){
  	if(empty($facility_ids)) return null;
  	global $db;
  	$cond = '';
  	$cond .=" AND ii.facility_id ".db_create_in($facility_ids);;
  	if(!empty($party_ids)){
  		$cond .="AND cg.party_id ".db_create_in($party_ids);
  	}
    if(!empty($provider_ids)){
    	$cond .="AND pr.provider_id ".db_create_in($provider_ids);
    }
    
    if(isset($is_new)){
      $cond .= " AND ii.status_id = '{$is_new}'"; 
    }

    if(!empty($unit_cost)){
    	$cond .= ' and ii.unit_cost = ' . $unit_cost." ";
    }
    if(!empty($batch_sn)){
    	$cond .= ' and ii.batch_sn = ' ."'".$batch_sn."'";
    }
  	if(!empty($product_ids) && !empty($barcodes) ){
  		$cond .="AND (ii.product_id ".db_create_in($product_ids).' OR IFNULL(gs.barcode,g.barcode)'.db_create_in($barcodes).')';
  	}else{
  		if(!empty($product_ids)){
  			$cond.= "AND ii.product_id ".db_create_in($product_ids);
  		}else if(!empty($barcodes)){
  			$cond.= "AND IFNULL(gs.barcode,g.barcode) ".db_create_in($barcodes);
  		}
  		
  	}
  	
  	$sql = "SELECT   
				 p.product_id , cg.cat_name,  
         p.product_name as goods_name , 
         p.product_name as order_goods_name,  
         ifnull(ii.inventory_item_type_id,'NON-SERIALIZED') as goods_item_type,
         pm.ecs_goods_id as  goods_id,pm.ecs_style_id as  style_id,  	 	 
         ii.status_id as is_new, ii.batch_sn,
         ii.inventory_item_acct_type_id as order_type,	'1.17' as goods_rate, 	
         ii.inventory_item_type_id as is_serial, 
         ii.UNIT_COST as purchase_paid_amount ,ii.unit_cost as goods_price, ii.unit_cost as ret_amount , 
         ifnull((select o.currency from romeo.inventory_item ii1
         left join romeo.inventory_item_detail iid1 on iid1.inventory_item_id = ii1.inventory_item_id 
         left join ecshop.ecs_order_info o on o.order_id = iid1.order_id
         where ii1.inventory_item_id = ii.inventory_item_id and o.order_type_id = 'PURCHASE' limit 1),'RMB') as currency,  
          ifnull(pr.provider_name,'自己库存') as  provider_name , 		
         IFNULL(gs.barcode,g.barcode) as barcode , 
          g.barcode as goods_code, gs.barcode as style_code, 
          CONCAT(IFNULL(gs.barcode,g.barcode),'-',IF(ii.status_id='INV_STTS_AVAILABLE','良品','不良品')) as goods_status,		
          f.facility_id ,  f.facility_name,
         ifnull(pr.provider_id ,'432') as provider_id , 
         cg.party_id , 
         IFNULL(sum(ii.quantity_on_hand_total),0) as storage_amount
		from  romeo.product p 
		INNER JOIN romeo.product_mapping pm on p.PRODUCT_ID = pm.product_id  	
		INNER JOIN  romeo.inventory_item ii on p.PRODUCT_ID = ii.PRODUCT_ID  	
		INNER JOIN  romeo.facility f  on ii.FACILITY_ID = f.FACILITY_ID  
		LEFT JOIN ecshop.ecs_provider pr ON  ii.provider_id = pr.provider_id 
		LEFT JOIN ecshop.ecs_goods g ON pm.ECS_GOODS_ID = g.goods_id 
		LEFT JOIN ecshop.ecs_goods_style gs ON pm.ECS_GOODS_ID = gs.goods_id AND pm.ECS_STYLE_ID = gs.style_id and gs.is_delete=0
		LEFT JOIN ecshop.ecs_category cg ON g.cat_id = cg.cat_id 
		WHERE ii.status_id in('INV_STTS_AVAILABLE','INV_STTS_USED','INV_STTS_DEFECTIVE') 
		AND  ii.quantity_on_hand_total > 0
		{$cond}
		group by ii.product_id,ii.status_id,ii.inventory_item_acct_type_id,ii.unit_cost,ii.batch_sn,ii.provider_id"; 
 
    $start = microtime(true); 
    $result = $db -> getAll($sql);; 
    $end = microtime(true);
    $time = $end-$start; 

    QLog::log(' -gt get inventory sql time : '.($time));
    if($time > 2){
      QLog::log(' -gt get inventory sql : '.($sql));
    }
		return   $result; 
  }
  function getInventoryByNew(){
  
  	global $db;
  	
  	$sql = "SELECT   
				 p.product_id , cg.cat_name,  
         p.product_name as goods_name , 
         p.product_name as order_goods_name,  
         ifnull(ii.inventory_item_type_id,'NON-SERIALIZED') as goods_item_type,
         pm.ecs_goods_id as  goods_id,pm.ecs_style_id as  style_id,  	 	 
         ii.status_id as is_new, ii.batch_sn,ii.facility_id,
         ii.inventory_item_acct_type_id as order_type,	'1.17' as goods_rate, 	
         ii.inventory_item_type_id as is_serial, 
         ii.UNIT_COST as purchase_paid_amount ,ii.unit_cost as goods_price, ii.unit_cost as ret_amount , 
         ifnull((select o.currency from romeo.inventory_item ii1
         left join romeo.inventory_item_detail iid1 on iid1.inventory_item_id = ii1.inventory_item_id 
         left join ecshop.ecs_order_info o on o.order_id = iid1.order_id
         where ii1.inventory_item_id = ii.inventory_item_id and o.order_type_id = 'PURCHASE' limit 1),'RMB') as currency,  
          ifnull(pr.provider_name,'自己库存') as  provider_name , 		
         IFNULL(gs.barcode,g.barcode) as barcode , 
          g.barcode as goods_code, gs.barcode as style_code, 
          CONCAT(IFNULL(gs.barcode,g.barcode),'-',IF(ii.status_id='INV_STTS_AVAILABLE','良品','不良品')) as goods_status,		
          f.facility_id ,  f.facility_name,
         ifnull(pr.provider_id ,'432') as provider_id , 
         cg.party_id , 
         IFNULL(sum(ii.quantity_on_hand_total),0) as storage_amount
     FROM ecshop.ecs_goods AS g
			LEFT JOIN ecshop.ecs_goods_style AS gs ON g.goods_id = gs.goods_id and gs.is_delete=0
			LEFT JOIN ecshop.ecs_style AS s on s.style_id = gs.style_id 
			left join ecshop.ecs_category cg on g.cat_id = cg.cat_id 
			LEFT JOIN romeo.product_mapping AS pm ON g.goods_id = pm.ecs_goods_id and ifnull(gs.style_id,0) = pm.ecs_style_id
      LEFT JOIN romeo.product AS p ON p.product_id = pm.PRODUCT_ID
			LEFT JOIN romeo.inventory_item AS ii ON ii.product_id = pm.product_id
      LEFT JOIN ecshop.ecs_provider pr ON  ii.provider_id = pr.provider_id 
			LEFT JOIN romeo.facility f ON ii.facility_id = f.facility_id 
		WHERE ii.status_id in('INV_STTS_AVAILABLE','INV_STTS_USED','INV_STTS_DEFECTIVE') 
		AND  ii.quantity_on_hand_total > 0
		and g.goods_party_id = '{$_SESSION['party_id']}'
		group by ii.facility_id, ii.product_id,ii.status_id,ii.inventory_item_acct_type_id,ii.unit_cost,ii.batch_sn,ii.provider_id
"; 
 
    $start = microtime(true); 
    $result = $db -> getAll($sql);; 
    $end = microtime(true);
    $time = $end-$start; 

    QLog::log(' -gt get inventory sql time : '.($time));
    //测试输出
//    QLog::log(' -gt get inventory sql : '.($sql));
    if($time > 2){
      QLog::log(' -gt get inventory sql : '.($sql));
    }
		return   $result; 
  }
  
   /**
    *   串号商品 从库存中查询出串号 去掉 已经申请 出库的串号 
    *   ALTER table romeo.supplier_return_request_item ADD INDEX  `rri_serial_number`(serial_number)
    */
   function get_serialized_goods_drop_used($facility_id, $original_provider_id, $status_id , $purchase_paid_amount, $product_id){
 	global $db;
 	//2015.3.24之前供应商退货申请，审核否决后，重新申请退货时，不能获取商品串号
// 	$sql = "SELECT DISTINCT ii.serial_number as erp_goods_sn
//		from romeo.inventory_item ii 
//		LEFT JOIN romeo.supplier_return_request_item rri on ii.SERIAL_NUMBER = rri.SERIAL_NUMBER 
//		LEFT JOIN romeo.supplier_return_request rr on rri.SUPPLIER_RETURN_ID = rr.SUPPLIER_RETURN_ID
//		   AND IFNULL(rr.check_status,'Hello') != 'DENY'
//		WHERE 
//		 ii.QUANTITY_ON_HAND_TOTAL > 0  
//		AND ii.FACILITY_ID = '%s' 
//		AND ii.provider_id =  %d   
//		AND ii.STATUS_ID = '%s' 
//		and ii.inventory_item_type_id = 'SERIALIZED' and ii.unit_cost = '%s'
//		AND ii.PRODUCT_ID = '%s' 
//		AND rri.serial_number is NULL 
//		ORDER BY ii.CREATED_STAMP ";

	//2015.3.24  供应商退货申请，审核否决后，可以重新申请退货时，重新申请退货时能获取商品串号
 	$sql = "SELECT DISTINCT ii.serial_number as erp_goods_sn
		from romeo.inventory_item ii 	   
		WHERE 
		 ii.QUANTITY_ON_HAND_TOTAL > 0  
		AND ii.FACILITY_ID = '%s' 
		AND ii.provider_id =  %d   
		AND ii.STATUS_ID = '%s' 
		and ii.inventory_item_type_id = 'SERIALIZED' and ii.unit_cost = '%s'
		AND ii.PRODUCT_ID = '%s' 
		ORDER BY ii.CREATED_STAMP ";
     $serial_goods = $db->getAll(sprintf($sql,$facility_id,
     							intval($original_provider_id), $status_id , 
     							$purchase_paid_amount, $product_id));
     return $serial_goods;
 }
 
  /**
   *    销售订单 
   */
 function  getOrderSaling($product_ids,$barcodes,$facility_id,$is_new = null){
 	$cond = '';
  	if(empty($facility_id)) return null;
  	
  	$cond .= "AND oi.facility_id = '{$facility_id}'";
    $three_day_ago = date('Y-m-d 00:00:00',strtotime('-7 day')); 
    $cond .= " AND oi.order_time > '{$three_day_ago}' "; 

  	if( !empty($product_ids) && !empty($barcodes)){
  		$cond .= "AND ( pm.product_id ".db_create_in($product_ids).' OR IFNULL(gs.barcode,g.barcode)'.db_create_in($barcodes).')';
  	}else{
  		if(!empty($product_ids)){
  			$cond .= " AND  pm.product_id ".db_create_in($product_ids)." ";
  		}else if(!empty($barcodes)){
  			$cond .= " AND IFNULL(gs.barcode,g.barcode) ".db_create_in($barcodes)." ";
  		}
  	}
    if(isset($is_new)){
      $cond .= " AND og.status_id = '{$is_new}' "; 
    }
 	
 	global $db;
 	$sql = "SELECT pm.product_id ,og.order_id ,og.rec_id ,og.status_id as is_new ,  
 			 IFNULL(gs.barcode,g.barcode) as barcode,
		     oi.facility_id , oi.order_sn, og.goods_price, og.goods_number, 
		     if(og.provider_id !=0,og.provider_id,'432') as provider_id ,   
		     og.goods_number+IFNULL(SUM(iid.QUANTITY_ON_HAND_DIFF),0) as storage_amount		 
		  FROM ecshop.ecs_order_goods og 
		  INNER JOIN ecshop.ecs_order_info oi use index(order_info_multi_index) ON og.order_id = oi.order_id  
		  INNER JOIN romeo.product_mapping pm ON convert(og.goods_id using utf8) = pm.ECS_GOODS_ID and convert(ifnull(og.style_id,0) using utf8) =  pm.ECS_STYLE_ID 
	      INNER JOIN ecshop.ecs_goods g on og.goods_id = g.goods_id	
	      LEFT JOIN ecshop.ecs_goods_style gs on og.goods_id = gs.goods_id  and og.style_id = gs.style_id and gs.is_delete=0
		  LEFT JOIN romeo.inventory_item_detail iid ON  CONVERT (og.rec_id  USING utf8) = iid.order_goods_id AND iid.cancellation_flag = 'N'  
		  WHERE oi.order_status in (0,1) AND oi.shipping_status in (0,13) AND oi.order_type_id IN('SALE','RMA_EXCHANGE') AND oi.party_id = {$_SESSION['party_id']}
		  {$cond}  GROUP BY og.rec_id";
    $start = microtime(true);
    $ret_items =  $db -> getAll($sql) ;
    $end = microtime(true);
    $time = $end-$start; 
    QLog::log(' -gt get order saling sql time : '.($time));
    //测试输出sql
    QLog::log(' -gt get order saling sql : '.($sql)); 
    if($time > 2){
        QLog::log(' -gt get order saling sql : '.($sql)); 
    }
	  return   $ret_items;
 }
 
 function  getOrderSalingNew(){
 		
 	global $db;
 	$sql = "SELECT pm.product_id ,og.order_id ,og.rec_id ,og.status_id as is_new ,  
 			 IFNULL(gs.barcode,g.barcode) as barcode,
		     oi.facility_id , oi.order_sn, og.goods_price, og.goods_number, 
		     if(og.provider_id !=0,og.provider_id,'432') as provider_id ,   
		     og.goods_number+IFNULL(SUM(iid.QUANTITY_ON_HAND_DIFF),0) as storage_amount		 
		  FROM ecshop.ecs_order_goods og 
		  INNER JOIN ecshop.ecs_order_info oi use index(order_info_multi_index) ON og.order_id = oi.order_id  
		  INNER JOIN romeo.product_mapping pm ON convert(og.goods_id using utf8) = pm.ECS_GOODS_ID and convert(ifnull(og.style_id,0) using utf8) =  pm.ECS_STYLE_ID 
	      INNER JOIN ecshop.ecs_goods g on og.goods_id = g.goods_id	
	      LEFT JOIN ecshop.ecs_goods_style gs on og.goods_id = gs.goods_id  and og.style_id = gs.style_id and gs.is_delete=0
		  LEFT JOIN romeo.inventory_item_detail iid ON  CONVERT (og.rec_id  USING utf8) = iid.order_goods_id AND iid.cancellation_flag = 'N'  
		  WHERE oi.order_status in (0,1) AND oi.shipping_status in (0,13) AND oi.order_type_id IN('SALE','RMA_EXCHANGE') AND oi.party_id = {$_SESSION['party_id']}
		  AND oi.order_time > '".date('Y-m-d 00:00:00',strtotime('-7 day'))."' GROUP BY og.rec_id";
    $start = microtime(true);
    $ret_items =  $db -> getAll($sql) ;
    $end = microtime(true);
    $time = $end-$start; 
    QLog::log(' -gt get order saling sql time : '.($time));
    //测试输出sql
//    QLog::log(' -gt get order saling sql : '.($sql)); 
    if($time > 2){
        QLog::log(' -gt get order saling sql : '.($sql)); 
    }
	  return   $ret_items;
 }
 
 
   /**   仓库预留量 
    *
    */
  function getInventoryReserve($product_ids,$barcodes,$facility_ids,$party_ids){
    if(empty($facility_ids)) return null;
  	global $db;
  	$cond = '';
  	$cond .=" AND gir.facility_id ".db_create_in($facility_ids);;
  	if(!empty($party_ids)){
  		$cond .="AND gir.party_id ".db_create_in($party_ids);
  	}
    
     
  	if(!empty($product_ids) && !empty($barcodes) ){
  		$cond .="AND (pm.product_id ".db_create_in($product_ids).' OR IFNULL(gs.barcode,g.barcode)'.db_create_in($barcodes).')';
  	}else{
  		if(!empty($product_ids)){
  			$cond.= "AND pm.product_id ".db_create_in($product_ids);
  		}else if(!empty($barcodes)){
  			$cond.= "AND IFNULL(gs.barcode,g.barcode) ".db_create_in($barcodes);
  		}
  		
  	}
 	$sql = "SELECT pm.product_id, pm.ecs_goods_id ,pm.ecs_style_id,
			   IFNULL(gs.barcode,g.barcode) as barcode ,  
			   'INV_STTS_AVAILABLE' as is_new , 
			   IFNULL(sum(gir.reserve_number),0) as storage_amount 
			from ecshop.ecs_goods_inventory_reserved gir
			LEFT JOIN romeo.product_mapping pm on gir.goods_id = pm.ecs_goods_id 
			                                   and gir.style_id = pm.ecs_style_id 
			LEFT JOIN ecshop.ecs_goods g ON  pm.ecs_goods_id = g.goods_id 
			LEFT JOIN ecshop.ecs_goods_style gs ON pm.ecs_goods_id = gs.goods_id AND pm.ecs_style_id = gs.style_id and gs.is_delete=0
			WHERE    gir.status = 'OK' {$cond} 
			GROUP BY gir.goods_id , gir.style_id ";
    $start = microtime(true); 
    $ret  =  $db -> getAll($sql) ;
    $end = microtime(true);
    $time = $end-$start; 
    QLog::log(' -gt get inventory reserve sql time : '.($time));
    //测试输出
    QLog::log(' -gt get inventory reserve sql: '.($sql)); 
    if($time > 2){
       QLog::log(' -gt get inventory reserve sql: '.($sql)); 
    }
	  return  $ret;
 }
 
  /**   仓库预留量  整个业务组
    *
    */
  function getInventoryReserveNew(){
  
  	global $db;
  
 	$sql = "SELECT pm.product_id, pm.ecs_goods_id ,pm.ecs_style_id,gir.facility_id,
			   IFNULL(gs.barcode,g.barcode) as barcode ,  
			   'INV_STTS_AVAILABLE' as is_new , 
			   IFNULL(sum(gir.reserve_number),0) as storage_amount 
			from ecshop.ecs_goods_inventory_reserved gir
			LEFT JOIN romeo.product_mapping pm on gir.goods_id = pm.ecs_goods_id 
			                                   and gir.style_id = pm.ecs_style_id 
			LEFT JOIN ecshop.ecs_goods g ON  pm.ecs_goods_id = g.goods_id 
			LEFT JOIN ecshop.ecs_goods_style gs ON pm.ecs_goods_id = gs.goods_id AND pm.ecs_style_id = gs.style_id and gs.is_delete=0
			WHERE    gir.status = 'OK' and  gir.party_id = {$_SESSION['party_id']}
			GROUP BY gir.goods_id , gir.style_id ,gir.facility_id";
    $start = microtime(true); 
    $ret  =  $db -> getAll($sql) ;
    $end = microtime(true);
    $time = $end-$start; 
    QLog::log(' -gt get inventory reserve sql time : '.($time));
    //测试输出
//    QLog::log(' -gt get inventory reserve sql: '.($sql)); 
    if($time > 2){
       QLog::log(' -gt get inventory reserve sql: '.($sql)); 
    }
	  return  $ret;
 }
 
   /**   -v申请量
    *
    */
  function getInventoryVarianceMinusNew(){
  
  	global $db;
  
 	$sql = "SELECT pm.product_id ,og.order_id ,og.rec_id ,og.status_id as is_new ,  
			IFNULL(gs.barcode,g.barcode) as barcode,
			o.facility_id , o.order_sn, og.goods_price, og.goods_number,og.goods_number as  storage_amount,
			if(og.provider_id !=0,og.provider_id,'432') as provider_id	
			FROM ecshop.ecs_order_info o force index (order_info_multi_index)
			inner join ecshop.ecs_order_goods og on og.order_id = o.order_id
			INNER JOIN romeo.product_mapping pm ON convert(og.goods_id using utf8) = pm.ECS_GOODS_ID and convert(ifnull(og.style_id,0) using utf8) =  pm.ECS_STYLE_ID 
			INNER JOIN ecshop.ecs_goods g on og.goods_id = g.goods_id	
			LEFT JOIN ecshop.ecs_goods_style gs on og.goods_id = gs.goods_id  and og.style_id = gs.style_id and gs.is_delete=0
			left JOIN romeo.order_inv_reserved r ON r.order_id = o.order_id
			left JOIN romeo.inventory_item_detail iid on  convert(o.order_id using utf8) = iid.order_id
			WHERE iid.order_id is null and order_status = 2 AND shipping_status = 0
			AND (o.facility_id is not null) AND (o.facility_id != '')
			AND o.order_type_id = 'VARIANCE_MINUS'                    
			and o.party_id =  '{$_SESSION['party_id']}'  and o.order_time>=date_add(now(),interval - 30 day) group by rec_id";
    $start = microtime(true); 
    $ret  =  $db -> getAll($sql) ;
    $end = microtime(true);
    $time = $end-$start; 
    QLog::log(' -gt get inventory VARIANCE_MINUS sql time : '.($time));
    //测试输出
//    QLog::log(' -gt get inventory VARIANCE_MINUS sql: '.($sql)); 
    if($time > 2){
       QLog::log(' -gt get inventory VARIANCE_MINUS sql: '.($sql)); 
    }
	  return  $ret;
 }
 
 
  /**
   *   组织 -gt 申请的数据  product_id is_new provider_id purchase_paid_amount 
   */
 function formatInventory($request){
 	if(empty($request)) return null;
 	$format = array();
 	foreach($request as $one){
 		$key_full = $one['product_id'].'-'.$one['is_new'].'-'.$one['provider_id'].'-'.$one['purchase_paid_amount'].'-'.$one['batch_sn'];
 		$one['can_request'] = $one['storage_amount'];
 		$one['detail'] = '';
 		if(!empty($format[$key_full])) {
 			$one['storage_amount'] += $format[$key_full]['storage_amount'];
 			$one['can_request'] += $format[$key_full]['can_request'];
 		}
 	   $format[$key_full] = $one;
 	}
 	return $format;
 }
 
  function formatInventoryNew($request){
 	if(empty($request)) return null;
 	$format = array();
 	foreach($request as $one){
 		$key_full = $one['facility_id'].'-'.$one['product_id'].'-'.$one['is_new'].'-'.$one['provider_id'].'-'.$one['purchase_paid_amount'].'-'.$one['batch_sn'];
 		$one['can_request'] = $one['storage_amount'];
 		$one['detail'] = '';
 		if(!empty($format[$key_full])) {
 			$one['storage_amount'] += $format[$key_full]['storage_amount'];
 			$one['can_request'] += $format[$key_full]['can_request'];
 		}
 	   $format[$key_full] = $one;
 	}
 	return $format;
 }
 
 function formatRequest($request){
 	if(empty($request)) return null;
 	$format = array();
 	foreach($request as $one){
 		if(empty($one['facility_id'])){
 			 $one['facility_id'] = $one['ret_facility_id'];
 		}
 	   if(empty($one['facility_id'])){
 			 $one['provider_id'] = $one['ret_original_id'];
 	   }
 	   $key_full = $one['product_id'].'-'.$one['is_new'].'-'.$one['provider_id'].'-'.$one['purchase_paid_amount'].'-'.$one['batch_sn'];
 	   $one['can_request'] = $one['storage_amount'];
 	   $one['detail'] = '';
 	   $format[$key_full][] = $one;
 	}
 	return $format;
 }
 
 function formatRequestNew($request){
 	if(empty($request)) return null;
 	$format = array();
 	foreach($request as $one){
 		if(empty($one['facility_id'])){
 			 $one['facility_id'] = $one['ret_facility_id'];
 		}
 	   if(empty($one['facility_id'])){
 			 $one['provider_id'] = $one['ret_original_id'];
 	   }
 	   $key_full = $one['facility_id'].'-'.$one['product_id'].'-'.$one['is_new'].'-'.$one['provider_id'].'-'.$one['purchase_paid_amount'].'-'.$one['batch_sn'];
 	   $one['can_request'] = $one['storage_amount'];
 	   $one['detail'] = '';
 	   $format[$key_full][] = $one;
 	}
 	return $format;
 }
 
 /**
  *   组织 销售订单 和 仓库预留  product_id is_new 
  *   $type B2B  或者销售 
  */
function formatSaleOrReserve($sale,$type){
	if(empty($sale)) return null;
	$format = array();
   	foreach($sale as $one){
		$key_small = $one['product_id'].'-'.$one['is_new'];
		 
 		if(!empty($one['order_sn'])){
 			$one['type']  = $type.'【'.$one['order_sn'].'】';
 		}else{
 			$one['type']  = $type;
 		}
 		$format[] = $one;
	}
   return $format;
}

function formatSaleOrReserveNew($sale,$type){
	if(empty($sale)) return null;
	$format = array();
   	foreach($sale as $one){
		$key_small = $one['facility_id'].'-'.$one['product_id'].'-'.$one['is_new'];
		 
 		if(!empty($one['order_sn'])){
 			$one['type']  = $type.'【'.$one['order_sn'].'】';
 		}else{
 			$one['type']  = $type;
 		}
 		$format[] = $one;
	}
   return $format;
}
/**
 *  $product_ids 
 *  $barcodes
 *  $facility_id 
 *  $party_ids
 *  $provider_ids
 *  $unit_cost 
 *  $batch_sn 
 */
function getInventoryBy_can_request($product_ids,$barcodes,$facility_id,$party_ids,$provider_ids,$unit_cost,$batch_sn,$is_new = null){
	  $inventory  = getInventoryBy($product_ids,$barcodes,$facility_id,$party_ids,$provider_ids,$unit_cost,$batch_sn,$is_new); // 库存  
	  $ret = getCanRequest_ByInventory($inventory,$product_ids,$barcodes,$facility_id,$party_ids,$provider_ids,$unit_cost,$batch_sn,$is_new);
	  if($ret == null ){
	  	$ret = array();
	  }
	  return $ret;
}
//获取当前业务组下所有的库存清单(用于可申请出库)
function getInventoryBy_can_request_new(){
	  $inventory  = getInventoryByNew(); // 库存  
	  $ret = getCanRequest_ByInventoryNew($inventory);
	  if($ret == null ){
	  	$ret = array();
	  }
	  return $ret;
}

function getCanRequest_ByInventory($inventory,$product_ids,$barcodes,$facility_id,$party_ids,$provider_ids,$unit_cost,$batch_sn,$is_new){
	 if(empty($inventory)) return null;
	 $request = getRequest_amount($product_ids,$barcodes,$facility_id,$batch_sn,$is_new);     // -gt 申请 
	 $sale = getOrderSaling($product_ids,$barcodes,$facility_id,$is_new);        // 销售订单  
	 $reserve  = getInventoryReserve($product_ids,$barcodes,$facility_id,$party_ids);    // 仓库预留 
   $start = microtime(true);
   $result = getCanRequest_step($inventory,$request,$sale,$reserve);
   $end = microtime(true);
   QLog::log(' -gt  inventory - request -saling - reserve time  : '.($end-$start));
	 return $result; 
}

function getCanRequest_ByInventoryNew($inventory){
	 if(empty($inventory)) return null;
	 $request = getRequest_amountNew();     // -gt 申请 
	 $sale = getOrderSalingNew();        // 销售订单  
	 $reserve  = getInventoryReserveNew();  //冻结
	 $varianceMinus = getInventoryVarianceMinusNew();    // -v库存 
   $start = microtime(true);
   $result = getCanRequest_step_new($inventory,$request,$sale,$reserve,$varianceMinus);
   $end = microtime(true);
   QLog::log(' -gt  inventory - request -saling - reserve time  : '.($end-$start));
	 return $result; 
}

function getCanRequest_step($inventory,$request,$sale,$reserve){
	  $inventory = formatInventory($inventory);
	  if(empty($inventory)) return null;
	  $request = formatRequest($request);
	  $sale = formatSaleOrReserve($sale,'销售');
	  $reserve = formatSaleOrReserve($reserve,'仓库预留');
	  if(!empty($request)){
	  	$inventory = inventory_minus_request($inventory,$request);   // 减去 -gt 申请  
	  }
	  if(!empty($sale)){
	  	$inventory = inventory_minus_saleOrReserve_new($inventory,$sale);   // 减去  销售订单 
	  }
	  if(!empty($reserve)){
	  	$inventory = inventory_minus_saleOrReserve_new($inventory,$reserve);  // 减去  仓库预留 
	  }
      $array_ret_item = array();
      foreach($inventory as $item){
      	$array_ret_item[] = $item;
      }
	return   $array_ret_item;   
}

function getCanRequest_step_new($inventory,$request,$sale,$reserve,$varianceMinus){
	  $inventory = formatInventoryNew($inventory);
	  if(empty($inventory)) return null;
	  $request = formatRequestNew($request); 
	  $sale = formatSaleOrReserveNew($sale,'销售');
	  $reserve = formatSaleOrReserveNew($reserve,'仓库预留');
	  $reserve = formatSaleOrReserveNew($varianceMinus,'盘亏调整');
	  if(!empty($request)){
	  	$inventory = inventory_minus_request($inventory,$request);   // 减去 -gt 申请  
	  }
	  if(!empty($sale)){
	  	$inventory = inventory_minus_saleOrReserve_new($inventory,$sale);   // 减去  销售订单 
	  }
	  if(!empty($reserve)){
	  	$inventory = inventory_minus_saleOrReserve_new($inventory,$reserve);  // 减去  仓库预留 
	  }
	   if(!empty($varianceMinus)){
	  	$inventory = inventory_minus_saleOrReserve_new($inventory,$varianceMinus);  // 减去  -v盘亏 
	  }
      $array_ret_item = array();
      foreach($inventory as $item){
      	$array_ret_item[] = $item;
      }
	return   $array_ret_item;   
}
 
 
 /**
  *   库存 减去 -gt 申请 
  */
 function inventory_minus_request($inventory,$request){
 	if(empty($request)) return $inventory;
 	$ret = array();
 	foreach($inventory as  $key => $item){
 		$request_items = $request[$key];
 		if(count($request_items) > 0 ){
 			foreach($request_items as $r){
 				$item['can_request'] -= $r['storage_amount'];
 				$sn_or_request = '';
 				if(!empty($r['batch_gt_sn'])){
 					$sn_or_request = $r['batch_gt_sn'];
 				}else{
 					$sn_or_request = $r['supplier_return_id'];
 				}
 			    $item['detail_detail'][]= array("sn"=>$sn_or_request,"amount"=>$r['storage_amount']);
 			}
 		} 
 		$ret[] = $item;
 	}
 	foreach($ret as &$value){
 	    $details = $value['detail_detail'];
 	    if(count($details) > 0 ){
 	    	$tmp_array = array();
 	    	foreach($details as $detail){
 	    	    $k = $detail["sn"];
 	    	    $storage_amount = $detail["amount"];
 	    	    $tmp_array[$k]+= $storage_amount;
 	    	}
 	    	foreach($tmp_array as $s_k => $show){
 	    		$value['detail'].='B2B '.'【'.$s_k."】".$show."个 <br/>";
 	    	}
 	    	
 	    }
 	    $value['detail_detail'] = null;
   }
 	return $ret;
}
 
 /**
  *   库存 减去 销售 或者 仓库预留 
  */
function inventory_minus_saleOrReserve($inventory,$sale){
	if(empty($sale)) return $inventory;
	if(!is_array($sale)) $sale[] = $sale;
	 
	foreach($sale as $s_item){
		$sale_should = $s_item['storage_amount'];   
		$s_key_small = $s_item['product_id'].'-'.$s_item['is_new'];
		 
		// 在库存中预留 
		foreach($inventory as  &$i_item){
			$i_key_small = $i_item['product_id'].'-'.$i_item['is_new'];
			if($sale_should <= 0 ) break;
			if($s_key_small == $i_key_small ){
				$i_can_request = $i_item['can_request'];
				if($i_can_request > 0 ){
					if($i_can_request >= $sale_should){
						$i_can_request -= $sale_should;
						$i_item['can_request'] = $i_can_request;
						$i_item['detail_detail'][] = array("sn"=>$s_item['type'],"amount"=>$sale_should);
						$sale_should = 0;
					}else{
						$sale_should -= $i_can_request;
						$i_item['can_request'] = 0;
						$i_item['detail_detail'][] = array("sn"=>$s_item['type'],"amount"=>$i_can_request);
					}
				} 
			}
		} // $inventory
	} // end $sale 
	
	// 整理相同订单的   显示信息 整理到一起
	foreach($inventory as &$value){
 	    $details = $value['detail_detail'];
 	    if( is_array($details) ){
 	    	if(count($details) > 0 ){
 	    		$tmp_array = array();
 	    		foreach($details as $detail){
 	    	    $k = $detail["sn"];
 	    	    $storage_amount = $detail["amount"];
 	    	    $tmp_array[$k]+= $storage_amount;
	 	    	}
	 	    	foreach($tmp_array as $s_k => $show){
	 	    		$value['detail'].= $s_k.$show." 个 <br/>";
	 	    	}
 	    	}
 	    }
 	    $value['detail_detail'] = '';
   }
	
	return $inventory;
}

 /**
  *   库存 减去 销售 或者 仓库预留 
  */
function inventory_minus_saleOrReserve_new($inventory,$sale){
	if(empty($sale)) return $inventory;
	if(!is_array($sale)) $sale[] = $sale;
	 
	foreach($sale as $s_item){
		$sale_should = $s_item['storage_amount'];   
		$s_key_small = $s_item['facility_id'].'-'.$s_item['product_id'].'-'.$s_item['is_new'];
		 
		// 在库存中预留 
		foreach($inventory as  &$i_item){
			$i_key_small = $i_item['facility_id'].'-'.$i_item['product_id'].'-'.$i_item['is_new'];
			if($sale_should <= 0 ) break;
			if($s_key_small == $i_key_small ){
				$i_can_request = $i_item['can_request'];
				if($i_can_request > 0 ){
					if($i_can_request >= $sale_should){
						$i_can_request -= $sale_should;
						$i_item['can_request'] = $i_can_request;
						$i_item['detail_detail'][] = array("sn"=>$s_item['type'],"amount"=>$sale_should);
						$sale_should = 0;
					}else{
						$sale_should -= $i_can_request;
						$i_item['can_request'] = 0;
						$i_item['detail_detail'][] = array("sn"=>$s_item['type'],"amount"=>$i_can_request);
					}
				} 
			}
		} // $inventory
	} // end $sale 
	
	// 整理相同订单的   显示信息 整理到一起
	foreach($inventory as &$value){
 	    $details = $value['detail_detail'];
 	    if( is_array($details) ){
 	    	if(count($details) > 0 ){
 	    		$tmp_array = array();
 	    		foreach($details as $detail){
 	    	    $k = $detail["sn"];
 	    	    $storage_amount = $detail["amount"];
 	    	    $tmp_array[$k]+= $storage_amount;
	 	    	}
	 	    	foreach($tmp_array as $s_k => $show){
	 	    		$value['detail'].= $s_k.$show." 个 <br/>";
	 	    	}
 	    	}
 	    }
 	    $value['detail_detail'] = '';
   }
	
	return $inventory;
}

    /**
     *   中粮   获取申请出库 但未出库的量
     */ 
 function cofco_getReturnRequest($product_ids,$barcodes,$facility_id,$original_provider_id){
 	global $db;
 	if(empty($facility_id)) return null;
 	
 	$cond = '';
  	if(empty($facility_id)) return null;
    $cond .= "AND oi.facility_id = '{$facility_id}'";
  	 
  	if( !empty($product_ids) && !empty($barcodes)){
  		$cond .= " AND ( pm.product_id ".db_create_in($product_ids).' OR IFNULL(gs.barcode,g.barcode)'.db_create_in($barcodes).')';
  	}else{
  		if(!empty($product_ids)){
  			$cond .= "AND  pm.product_id ".db_create_in($product_ids);
  		}else if(!empty($barcodes)){
  			$cond .= " AND  IFNULL(gs.barcode,g.barcode) ".db_create_in($barcodes);
  		}
  	}
  	if(!empty($original_provider_id)){
  		$cond .=  'AND r.original_supplier_id = '. $original_provider_id ;
  	}
 
 	$sql = "  
 			select gtm.batch_gt_sn , r.supplier_return_id, 
			og.goods_name,og.goods_number,oi.facility_id,oi.order_sn,oi.order_id,oi.order_time,pm.product_id,  
			IFNULL(gs.barcode,g.barcode) as barcode, 		
			ifnull(sum(iid.quantity_on_hand_diff),0) as out_number,ifnull(ad.normal_quantity,0),ifnull(ad.defective_quantity,0),
			if(r.status_id='INV_STTS_AVAILABLE', ifnull(ad.normal_quantity,og.goods_number), ifnull(ad.defective_quantity,og.goods_number)) as storage_amount ,
			r.status_id as is_new,  ifnull(r.original_supplier_id,'432') as provider_id   , r.purchase_unit_price as purchase_paid_amount
			from 
			ecshop.ecs_order_goods og 
			left join ecshop.ecs_order_info oi ON og.order_id = oi.order_id 
			left join ecshop.ecs_batch_gt_mapping gtm on oi.order_id = 	gtm.order_id 	
			left join romeo.supplier_return_request_gt gt ON oi.order_sn = gt.SUPPLIER_RETURN_GT_SN
			left join romeo.supplier_return_request r ON gt.SUPPLIER_RETURN_ID = r.SUPPLIER_RETURN_ID 
			left join romeo.product_mapping pm ON og.goods_id = pm.ECS_GOODS_ID and og.style_id = pm.ECS_STYLE_ID 
			LEFT JOIN ecshop.ecs_goods_style gs on og.goods_id = gs.goods_id  and og.style_id = gs.style_id and gs.is_delete=0
	        LEFT JOIN ecshop.ecs_goods g on og.goods_id = g.goods_id				
			left join romeo.inventory_item_detail iid ON convert(og.rec_id using utf8) = iid.order_goods_id
			left join ecshop.express_best_actual_detail ad ON og.rec_id = ad.order_goods_id 
			where  r.status !='COMPLETION' AND r.check_status='pass' and r.return_order_amount != r.storage_amount and r.status_id in('INV_STTS_AVAILABLE','INV_STTS_USED')
		    {$cond}
			and not exists (select 1 from ecshop.express_best_indicate i where ad.order_id = i.order_id and  i.indicate_status = 'FINISH')
			group by og.rec_id
			having out_number = 0
 		   ";
 	$ret_items =  $db -> getAll($sql) ;
	return   $ret_items;
 }
 
 
 /**   中粮B2B出库 获取 库存量 和 可以 申请的量  库存 -销售 -申请 -仓库预留 
  *   $product_ids 
  *   $barcodes 
  *   $facility_id
  *   $party_ids 
  *   $original_provider_id
  */
 function cofco_getInventoryBy_can_request($product_ids,$barcodes,$facility_id,$party_ids,$original_provider_id){
	  $inventory  = getInventoryBy($product_ids,$barcodes,$facility_id,$party_ids,$original_provider_id,null,null); // 库存 
	  $request =  cofco_getReturnRequest($product_ids,$barcodes,$facility_id,$original_provider_id);
	  $sale =  getOrderSaling($product_ids,$barcodes,$facility_id);
	  $reserve = getInventoryReserve($product_ids,$barcodes,$facility_id,$party_ids);
	  return getCanRequest_step($inventory,$request,$sale,$reserve);
}
  
?>