<?php
define('IN_ECS',true);
define("ROOT_PATH", dirname(__FILE__) . '/../../');
//ini_set('memory_limit', '1024M');
Yii::import('application.commands.LockedCommand', true);

/**
 * 自动修复qoh（item总数和summary表的quantity一致），atp（可预订库存）
 *
 * @author ljzhou 2014-12-11
 *
 */
class AutoModifyInventoryReserveSummaryCommand extends CConsoleCommand
{
	
	 public function actionModifyAtpAndQohNew()
	 {
		$start_time = microtime(true);
		// 加锁
		$lock_name = 'ModifyAtpAndQohNew';
	    $lock_file_name = $this->get_file_lock_path($lock_name, 'ModifyAtpAndQohNew');
	    $lock_file_point = fopen($lock_file_name, "w+");
	    $would_block = false;
	    if(!flock($lock_file_point, LOCK_EX|LOCK_NB, $would_block)){
	    	fclose($lock_file_point);
	    	echo("上次修改还在进行，请稍后 :".$lock_name);
	    	return;
	    }
	    
	    try {
	    	
//	    	$sql = "select count(1) as num from romeo.inventory_reserve_summary"; 
//	    	$lists = Yii::app ()->getDb ()->createCommand ($sql)->queryAll();
//	    	if(empty($lists[0]["num"])){
//	    		echo("start init inventory_reserve_summary"."\n");
//	    		$sql_copy = "insert into romeo.inventory_reserve_summary(status_id,facility_id,product_id,stock_quantity,available_to_reserved,party_id,created_stamp,last_updated_stamp)
//							SELECT status_id,facility_id,product_id,sum(stock_quantity),sum(available_to_reserved), goods_party_id,CREATED_STAMP,LAST_UPDATED_STAMP from (
//							select im.status_id,im.facility_id,im.product_id,im.stock_quantity,im.available_to_reserved, 
//							eg.goods_party_id,im.CREATED_STAMP,im.LAST_UPDATED_STAMP 
//							from romeo.inventory_summary im 
//							left JOIN romeo.product_mapping pm on pm.product_id= im.product_id
//							left JOIN ecshop.ecs_goods eg on eg.goods_id = pm.ecs_goods_id
//							group by im.product_id,im.facility_id,im.status_id,im.CONTAINER_ID
//							) as temp
//							group by product_id,facility_id,status_id";
//			    echo("如果 inventory_reserve_summary中没有 数据 那么初始化 从inventory_reserve_summary表中取");
//				Yii::app ()->getDb ()->createCommand ($sql_copy)->execute();
//	    	}
	    	//修复inventory_reserve_summary中实际库存 前提是 是 inventory_reserve_summary有这条记录
			$qoh_list = $this->get_qoh_error_products_new();
			
			if(!empty($qoh_list)) {
				var_dump('qoh_list:');var_dump(count($qoh_list));var_dump($qoh_list);
				foreach ($qoh_list as $qoh) {
				    $result = $this->create_modify_qoh_inventory_new($qoh['product_id'],$qoh['facility_id'],$qoh['status_id']);
				    print_r($result['message']);
				}
			}

// 查报表数据 暂无发现这种情况 在item中有新商品入库 而在inventory_reserve_summary中没有记录
			$qoh_list2 = $this->get_qoh_no_products_new();
			
			if(!empty($qoh_list2)) {
				var_dump('qoh_list:');var_dump(count($qoh_list2));var_dump($qoh_list2);
				foreach ($qoh_list2 as $qoh) {
				    $result = $this->create_insert_qoh_inventory_new($qoh['product_id'],$qoh['facility_id'],$qoh['status_id']);
				    print_r($result['message']);
				}
			}
			
			//修复可预订库存  计算订单中有预定记录为 'Y'的修复
			$atp_list = $this->get_atp_error_products_new();
			if(!empty($atp_list)) {
				var_dump('atp_list:');var_dump(count($atp_list));var_dump($atp_list);
				foreach ($atp_list as $atp) {
				    $result = $this->create_modify_atp_inventory_new($atp['product_id'],$atp['facility_id'],$atp['status_id']);
				    print_r($result['message']);
				}
			}
			
			//修复可预订库存 在erp订单中已经没有预定状态 为'Y'的订单 分开是因为简化逻辑 还有就是 sql慢
			$atp_list2 = $this->get_atp_error_products_not_reserved_new();
			if(!empty($atp_list2)) {
				var_dump('atp_list:');var_dump(count($atp_list2));var_dump($atp_list2);
				foreach ($atp_list2 as $atp) {
				    $result = $this->create_modify_atp_inventory_new($atp['product_id'],$atp['facility_id'],$atp['status_id']);
				    print_r($result['message']);
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
	
	function get_qoh_error_products_new() {
		$sql = "select im.product_id,im.facility_id,  im.reserve_summary_id, im.status_id, 
		 im.stock_quantity,ifnull(sum(ii.quantity_on_hand_total),0) as item_sum
	     from romeo.inventory_reserve_summary im
	     left join romeo.inventory_item ii ON im.product_id = ii.product_id and im.facility_id = ii.facility_id
	     and im.status_id = ii.status_id
	     where im.status_id in('INV_STTS_AVAILABLE','INV_STTS_USED')
	     and im.product_id is not null and im.facility_id is not null
	     group by im.product_id, im.facility_id,im.status_id 
       having stock_quantity <>item_sum ";
       $lists = Yii::app ()->getDb ()->createCommand ($sql)->queryAll();
       
       return $lists;
	}

     //处理没有将入库记录插入inventory_reserve_summary的情况
	function get_qoh_no_products_new() {
		$sql = "select ii.product_id,ii.facility_id,ii.status_id,
				ifnull(sum(ii.quantity_on_hand_total),0) as item_sum
				from romeo.inventory_item ii
				left join romeo.inventory_reserve_summary im  ON im.product_id = ii.product_id and im.facility_id = ii.facility_id
				and im.status_id = ii.status_id
				where ii.status_id in('INV_STTS_AVAILABLE','INV_STTS_USED')
				and ii.product_id is not null and ii.facility_id is not null and im.reserve_summary_id is null
				group by ii.product_id, ii.facility_id,ii.status_id
				having item_sum > 0 ";
       $lists = Yii::app ()->getDb ()->createCommand ($sql)->queryAll();
       
       return $lists;
	}
	
	 function create_modify_qoh_inventory_new($product_id,$facility_id,$status_id='INV_STTS_AVAILABLE'){
	 	$result = array();
	 	$result['success'] = false;
	 	$result['message'] = " 修改qoh失败facility_id:".$facility_id." product_id:".$product_id." \n";
	 	$sql_qoh = "select im.product_id,im.facility_id,im.available_to_reserved,im.reserve_summary_id,im.stock_quantity as num_summary,ifnull(sum(ii.quantity_on_hand_total),0) as num_item
			from romeo.inventory_reserve_summary im
			left join romeo.inventory_item ii ON im.product_id = ii.product_id
			AND ii.facility_id = im.facility_id AND ii.status_id = im.status_id
			WHERE  im.facility_id = '{$facility_id}' and im.product_id = '{$product_id}'
			AND im.status_id = '{$status_id}'
			group by im.product_id limit 1
	 	";
		$qoh= Yii::app ()->getDb ()->createCommand ($sql_qoh)->queryRow();
	
	 	if(!empty($qoh)){
	 	  $reserve_summary_id = $qoh['reserve_summary_id'];
	 	  $qoh_diff = $qoh['num_summary'] - $qoh['num_item'];
	 	  if($qoh_diff != 0){
		 	  $sql_update_qoh = "update romeo.inventory_reserve_summary set stock_quantity=stock_quantity-{$qoh_diff} where reserve_summary_id='{$reserve_summary_id}' limit 1 ";
		 	  Yii::app ()->getDb ()->createCommand ($sql_update_qoh)->execute();
	 	      $result['success'] = true;
	 		  $result['message'] = " 修改qoh成功facility_id:".$facility_id." product_id:".$product_id." before: ". $qoh['num_summary']." after:". ($qoh['num_summary']-$qoh_diff)."\n";
	 	    }
	 	}
	 	return $result;
	 }
	 
	 
	  function create_insert_qoh_inventory_new($product_id,$facility_id,$status_id='INV_STTS_AVAILABLE'){
	 	$result = array();
	 	$result['success'] = false;
	 	$result['message'] = " 修改qoh失败facility_id:".$facility_id." product_id:".$product_id." \n";
	 	$sql_qoh = "select ii.product_id,ii.facility_id,ifnull(sum(ii.quantity_on_hand_total),0) as num_item,ii.party_id,ii.status_id
			from romeo.inventory_item ii
			WHERE  ii.facility_id = '{$facility_id}' and ii.product_id = '{$product_id}'
			AND ii.status_id = '{$status_id}'
			group by ii.product_id,ii.facility_id,ii.status_id
			limit 1
	 	";
		$qoh= Yii::app ()->getDb ()->createCommand ($sql_qoh)->queryRow();
	
	 	if(!empty($qoh)){
	 		try{
		 	  $sql_insert_qoh = " insert into romeo.inventory_reserve_summary (status_id, 
		      facility_id, product_id, stock_quantity, available_to_reserved, comments, party_id,  created_stamp, last_updated_stamp)
	          values('{$status_id}','{$facility_id}','{$product_id}','{$qoh['num_item']}','{$qoh['num_item']}','','{$qoh['party_id']}',now(),now() )";
		 	  Yii::app ()->getDb ()->createCommand ($sql_insert_qoh)->execute();
	 	      $result['success'] = true;
	 		  $result['message'] = " 修改qoh成功facility_id:".$facility_id." product_id:".$product_id." before: 0"." after:".$qoh['num_item']."\n";
	 		}
	 		catch(Exception $e){
	 			echo "create_insert_qoh_inventory_new fail: ".$e->getMessages();
	 		}
	 	}
	 	return $result;
	 }
	 
	 function get_atp_error_products_new() {
		$sql = "SELECT (im.available_to_reserved +sum(ifnull(ird.reserved_quantity,0)) -  im.stock_quantity) as diff,im.product_id,im.facility_id,im.status_id,
                im.reserve_summary_id, im.stock_quantity,im.available_to_reserved, ifnull(sum(if(ird.status = 'Y',ird.reserved_quantity,0)),0) as  reserved
                from romeo.inventory_reserve_summary im
                left JOIN romeo.order_inv_reserved_detail ird   on im.product_id = ird.product_id and im.facility_id = ird.facility_id and  im.status_id = ird.status_id
                left JOIN  ecshop.ecs_order_info oi on oi.order_id = ird.ORDER_ID
                where ((oi.shipping_status  in(0,13,16) and oi.order_type_id ='SALE') 
                        or (oi.shipping_status = 6 and oi.order_type_id in ('SUPPLIER_RETURN','SUPPLIER_SALE','SUPPLIER_TRANSFER')) 
                        or (oi.shipping_status = 0 and oi.order_type_id = 'VARIANCE_MINUS'))
                and im.status_id in ('INV_STTS_AVAILABLE','INV_STTS_USED') and ird.status = 'Y'
                and im.product_id is not null and im.facility_id is not null
                group by im.product_id,im.facility_id,im.status_id 
                having diff <>0 
                ";
       $lists = Yii::app ()->getDb ()->createCommand ($sql)->queryAll();
       
       return $lists;
	}
	
	function get_atp_error_products_not_reserved_new() {
		$sql = "SELECT (im.available_to_reserved -  im.stock_quantity) as diff,im.product_id,im.facility_id,
				im.reserve_summary_id, im.stock_quantity,im.available_to_reserved,im.status_id
				from romeo.inventory_reserve_summary im
				where                         
				im.status_id in('INV_STTS_AVAILABLE','INV_STTS_USED')
				and not EXISTS (
				   select 1 from romeo.order_inv_reserved_detail ird  where im.product_id = ird.product_id and im.facility_id = ird.facility_id 
				   and  im.status_id = ird.status_id  and ird.status = 'Y'
				)
				and im.product_id is not null and im.facility_id is not null
				group by im.product_id,im.facility_id,im.status_id
				having diff <>0 
                ";
       $lists = Yii::app ()->getDb ()->createCommand ($sql)->queryAll();
       
       return $lists;
	}
	
	 function create_modify_atp_inventory_new($product_id,$facility_id,$status_id = 'INV_STTS_AVAILABLE'){
	 	$result = array();
	 	$result['success'] = false;
	 	$result['message'] = " 修改atp失败facility_id:".$facility_id." product_id:".$product_id." \n";
	 	$sql = "select im.product_id,im.facility_id,  im.reserve_summary_id,  im.stock_quantity,im.available_to_reserved
			from romeo.inventory_reserve_summary im
			where im.facility_id = '{$facility_id}' and im.status_id = '{$status_id}' and im.product_id = '{$product_id}'
	 	";
	
		$atp= Yii::app ()->getDb ()->createCommand ($sql)->queryRow();
	    //销售订单预定量
	 	$sql = "select sum(ifnull(ird.reserved_quantity,0)) as reserved_amount
				from romeo.order_inv_reserved_detail ird
				INNER JOIN ecshop.ecs_order_info oi on oi.order_id = ird.order_id 
                where oi.shipping_status  in(0,13,16) and ird.reserved_quantity <> 0 
				and ird.status = 'Y' and ird.facility_id = '{$facility_id}'  and ird.product_id = '{$product_id}'
				and ird.status_id = '{$status_id}' and oi.order_type_id = 'SALE'
				group by ird.product_id,ird.facility_id limit 1
	 	";
	 	
	 	$reserved= Yii::app ()->getDb ()->createCommand ($sql)->queryRow();
	 	if(empty($reserved)){
	 		$reserved['reserved_amount'] = 0;
	 	}
	 	//供应商退货订单预定量
	 	$sql2 = "select ifnull(sum(oir.reserved_quantity),0) as reserved_amount
		         from romeo.order_inv_reserved_detail oir
                 inner JOIN ecshop.ecs_order_info oi on oir.order_id = oi.order_id
                 inner JOIN romeo.supplier_return_request_gt gt on  gt.supplier_return_gt_sn = oi.order_sn 
                 INNER JOIN romeo.supplier_return_request rt ON   rt.supplier_return_id = gt.supplier_return_id
		         where  oi.order_type_id in ('SUPPLIER_RETURN','SUPPLIER_SALE','SUPPLIER_TRANSFER') 
                 and  rt.status = 'CREATED' and oir.reserved_quantity <> 0 
                 and oir.status_id = '{$status_id}'
		         and oir.status = 'Y' and oir.facility_id = '{$facility_id}'  and oir.product_id = '{$product_id}'
		         group by oir.product_id,oir.facility_id
	 	";
	 	
	 	$reserved_gt= Yii::app ()->getDb ()->createCommand ($sql2)->queryRow();
	 	if(empty($reserved_gt)){
	 		$reserved_gt['reserved_amount'] = 0;
	 	}
	    //-v minus订单预定量
	 	$sql3 = " select ifnull(sum(oir.reserved_quantity),0) as reserved_amount 
			    from romeo.order_inv_reserved_detail oir
			    inner JOIN ecshop.ecs_order_info oi on oir.order_id = oi.order_id
			   	left JOIN romeo.inventory_item_detail iid on  convert(oi.order_id using utf8) = iid.order_id
			    where iid.order_id is null 
			    and oir.status ='Y' and oi.order_type_id = 'VARIANCE_MINUS'
			    and oir.status_id = '{$status_id}' 
			    and oir.facility_id = '{$facility_id}'  and oir.product_id = '{$product_id}'
			    group by oir.product_id,oir.facility_id
	 	";
	 	$reserved_v= Yii::app ()->getDb ()->createCommand ($sql3)->queryRow();
	 	if(empty($reserved_v)){
	 		$reserved_v['reserved_amount'] = 0;
	 	}

	 	$diff = $atp['available_to_reserved'] + $reserved['reserved_amount']+$reserved_gt['reserved_amount']+$reserved_v['reserved_amount'] - $atp['stock_quantity'];
	 	
	 	if($diff != 0){
	 	  $reserve_summary_id = $atp['reserve_summary_id'];
	      $sql_update_atp = "update romeo.inventory_reserve_summary set available_to_reserved=available_to_reserved-{$diff},last_updated_stamp = NOW() where reserve_summary_id='{$reserve_summary_id}' limit 1 ";
	   	  Yii::app ()->getDb ()->createCommand ($sql_update_atp)->execute();
	   	  $result['success'] = true;
		  $result['message'] = " 修改atp成功:facility_id:".$facility_id." product_id:".$product_id." before: ". $atp['available_to_reserved']." after:". ($atp['available_to_reserved']-$diff)." \n";
	 	}
	 	return $result;
	 }

	/**
	 * 获得文件锁路径
	 *
	 * @param string $file_name
	 * @return string
	 */
	function get_file_lock_path($file_name = '', $namespace = null) {
		if (!defined('ROOT_PATH')) {
			define('ROOT_PATH', preg_replace('/admin(.*)/', '', str_replace('\\', '/', __FILE__)));
		}
	    if ($namespace == null) {
	    	preg_match('/\/([^\/]*)\.php/', $_SERVER['SCRIPT_URL'], $matches);
	        $namespace = $matches[1];
	    }
		return ROOT_PATH. "admin/filelock/{$namespace}.{$file_name}";
	}

}