<?php
define('IN_ECS',true);
define("ROOT_PATH", dirname(__FILE__) . '/../../');
Yii::import('application.commands.LockedCommand', true);

/**
 * 库存预订
 * 
 * @author yxiang
 *
 */
class ReserveOrderInventoryCommand extends CConsoleCommand
{
	public $betweenReserveOrderInventory=720;  // 库存预定的间隔时间，默认12分钟一次
	public $betweenCheckReserveOrderInventorye=900;  // 检查库存预定的间隔时间
	private $soapclient;

	/**
	 * 当不指定ActionName时的默认调用
	 */
	public function actionIndex()
	{
		$currentTime=microtime(true);
		$h=date('H');
		
		// 库存预定
		if(($currentTime-$this->getLastExecuteTime('Reserve'))>=$this->betweenReserveOrderInventory) {
		    $this->run(array('Reserve'));
        }
	}
	

	
	/**
	 * 执行批量库存预定
	 */
	public function actionReserve()
	{
		$start=microtime(true);
		try {
			$response=Yii::app()->getComponent('romeo')->InventoryService->reserveOrderInventory();
			echo "[". date('c'). "]预订订单数：".$response->return." 耗时：".(microtime(true)-$start)."\n";
		} catch (Exception $e) {
			echo "[". date('c') ."]订单库存预订异常: ".$e->getMessage()."\n";
		}
		
	}
	public function actionReserveByPartyId($party_id = '')
	{
		ini_set('default_socket_timeout', 2400);
		
		// 加锁
		$lock_name = $party_id;
		if(empty($lock_name)) {
			$lock_name = 'all_party';
		}
	    $lock_file_name = $this->get_file_lock_path($lock_name, 'ReserveByPartyId');
	    $lock_file_point = fopen($lock_file_name, "w+");
	    $would_block = false;
	    if(!flock($lock_file_point, LOCK_EX|LOCK_NB, $would_block)){
	    	fclose($lock_file_point);
	    	echo("上次预定还在进行，请稍后 party_id:".$lock_name);
	    	return;
	    }
	    
	    try {
	    	//外贸组织除外
		    $sql = "SELECT party_id,name 
		    	from romeo.party 
		    	where is_leaf='Y' and  status = 'ok' and parent_party_id != '65542'
			".' -- ROI '.__LINE__.PHP_EOL;
			$partyList = Yii::app()->getDb()->createCommand($sql)->queryAll();
			
			$not_reserved_partyList = array(
				"65559", 	//每伴旗舰店
				"65588", 	//本草婴缘旗舰店
				//"65585", 	//nb品牌站
				//"65587", 	//kabrita旗舰店
				//"65596", 	//imperialxo林贝儿旗舰店
				//"65597", 	//三雄极光官方旗舰店
				//"128",	//怀轩名品专营店
				//"65557", 	//yukiwenzi
			);
			foreach ($partyList as $value) {
			    if (in_array($value['party_id'], $not_reserved_partyList)) {
			        continue;
			    }
			    
	            // 如果有指定组织
			    if($party_id && ($value['party_id'] != $party_id) ) {
			    	continue;
			    }
			    
			    $start=microtime(true);
			    try {
			    	$response=Yii::app()->getComponent('romeo')->InventoryService->reserveOrderInventoryByPartyId(array("partyId" => $value['party_id']));
			    	echo "partyId: ".$value['party_id'] . " [". date('c'). "]预订订单数：".$response->return." 耗时：".(microtime(true)-$start)."\n";
			    } catch (Exception $e) {
			    	echo  "partyId: ".$value['party_id'] . " [". date('c') ."]订单库存预订异常: ".$e->getMessage()."\n";
			    }
			    
			    // 有指定的组织只跑一次
			    if($party_id) {   
			    	break;
			    }
			}
	    } 
	    catch (Exception $e2) {
		    echo  "partyId: ".$value['party_id'] . " [". date('c') ."]订单库存预订异常2: ".$e2->getMessage()."\n";
		}
		
		flock($lock_file_point, LOCK_UN);
	    fclose($lock_file_point);
	    echo("本次预定结束 party_id:".$lock_name);
		
	}
	public function actionReserveNew($group=0,$party_id=-1,$appkey='')
	{
		ini_set('default_socket_timeout', 2400);
		
		$stime=microtime(true);
		$partyList=array();
		$lock_name = '业务组'.$party_id;
		// 加锁
		if($party_id==-1 && $appkey== ''){
			if($group==0){
			    $lock_name = 'all_party';
			}elseif ( $group==1 ) {
		        $lock_name = 'party_group_one';
	        }elseif ( $group==2 ) {
		        $lock_name = 'party_group_two';
	        }elseif ( $group==3 ) {
		        $lock_name = 'party_group_three';
	        }elseif ( $group==4 ) {
		        $lock_name = 'party_group_four';				
	        }elseif ( $group==5 ) {
		        $lock_name = 'party_group_five';				
	        }
	        $partyList = $this->getTaobaoShopList($group);
		}else if($appkey== '' && $party_id != -1){
			 $sql = "SELECT party_id,name from romeo.party
				where is_leaf='Y' 
				and status = 'ok' 
				and parent_party_id != '65542' 
				and need_to_reserve = 1 
				and party_id=".$party_id." ".' -- ROI '.__LINE__.PHP_EOL;
			 $partyList = Yii::app()->getDb()->createCommand($sql)->queryAll();
		}else if($appkey!= ''){
			 $lock_name = $appkey;
			 $sql = "SELECT party_id,distributor_id 
			 from ecshop.taobao_shop_conf 
			 where application_key='".$appkey."' ".' -- ROI '.__LINE__.PHP_EOL;
			 $partyList = Yii::app()->getDb()->createCommand($sql)->queryAll();
			}  	       
        echo  date("Y-m-d H:i:s")." ".$lock_name." 预定开始内存：".memory_get_usage()."\n"; 		
//	    $lock_file_name = $this->get_file_lock_path($lock_name, 'ReserveNew');
//	    $lock_file_point = fopen($lock_file_name, "w+");
//	    $would_block = false;
//	    if(!flock($lock_file_point, LOCK_EX|LOCK_NB, $would_block)){
//	    	fclose($lock_file_point);
//	    	echo("上次预定还在进行，请稍后 party_id:".$lock_name);
//	    	return;
//	    }	    
	    try {
	    	//外贸组织除外
//		    $sql = "
//				select party_id,name from romeo.party where is_leaf='Y' and  status = 'ok' and parent_party_id != '65542' and need_to_reserve = 1
//			";
//			$partyList = Yii::app()->getDb()->createCommand($sql)->queryAll();
			
			foreach ($partyList as $value) {
			    $start=microtime(true);
			    try {
			    	if($appkey == ''){
			    	     $this->run(array('ReserveByPartyIdNew','--party_id='.$value['party_id']));
			    	}
			    	else{
			    		$this->run(array('ReserveByPartyIdNew','--party_id='.$value['party_id'],'--distributor_id='.$value['distributor_id']));
			    	}
//			    	echo "partyId: ".$value['party_id'] . " [". date('c'). "]订单库存预订：耗时：".(microtime(true)-$start)."\n";
			    } catch (Exception $e) {
			    	echo  date("Y-m-d H:i:s")." partyId: ".$value['party_id'] . " [". date('c') ."]订单库存预订异常: ".$e->getMessage()."\n";
			    }
			}
	    } 
	    catch (Exception $e2) {
		    echo   date("Y-m-d H:i:s")." partyId: ".$value['party_id'] . " [". date('c') ."]订单库存预订异常2: ".$e2->getMessage()."\n";
		}
		
//		flock($lock_file_point, LOCK_UN);
//	    fclose($lock_file_point);
//	    echo("本次预定结束 :".$lock_name);
	    $etime=microtime(true);
	    $total=$etime-$stime;
//	    echo  date("Y-m-d H:i:s")." ".$lock_name." 执行时间: ". $total."\n";
		echo  date("Y-m-d H:i:s")." ".$lock_name." 执行时间: ".$total." 预定结束内存：".memory_get_usage()."\n"; 
	}
	public function actionReserveByPartyIdNew($party_id,$distributor_id='',$days=20){
		$reserve_order_number = 2500;
	    $stime=microtime(true);
		echo date("Y-m-d H:i:s")." partyId:".$party_id." 预定开始内存：".memory_get_usage()."\n"; 
		$distributor_param='';
		if($distributor_id!=''){
		   $distributor_param = " AND o.distributor_id = {$distributor_id} "; 
//		   echo $distributor_param."\n";
		}
		
				
		//删除修改仓库后预定表没有修改的  预定信息
		$sql = "
			SELECT o.order_id
                 FROM ecshop.ecs_order_info o use index(order_time,order_info_multi_index)
                   inner JOIN romeo.order_inv_reserved r ON r.order_id = o.order_id
                   inner JOIN ecshop.ecs_payment p ON o.pay_id = p.pay_id
                 WHERE order_status in(1,11) AND shipping_status=0
				   AND (o.facility_id is not null) AND (o.facility_id != '')
                   AND( (o.order_type_id = 'SALE' AND (  o.pay_status = 2 or(p.pay_code = 'cod' and p.is_cod = 1) ) )
                     OR o.order_type_id IN ('RMA_EXCHANGE', 'SHIP_ONLY'))
                 and r.status = 'Y' and o.FACILITY_ID<>r.facility_id and o.order_time>=date_add(now(),interval - 10 day) 
                and o.party_id =  '{$party_id}' {$distributor_param} limit $reserve_order_number
			";
	    echo $sql."\n";	    
		$order_ids=Yii::app()->getDb()->createCommand($sql)->queryColumn();
		echo "查询仓库不一致订单花费的时间".(microtime(true)-$stime)."\n";
		echo "查询r.status = Y 状态但是订单与预定仓库不一致订单内存：".memory_get_usage()."\n";
		echo "查询r.status = Y 状态但是订单与预定仓库不一致订单数量：".count($order_ids)."\n";
		if(!empty($order_ids)) {
			$stimenot=microtime(true);
			foreach ( $order_ids as $order_id ){
				$this->getSoapClient()->cancelOrderInventoryReservation(array('orderId'=>$order_id));
			}
			echo "处理仓库不一致订单花费的时间".(microtime(true)-$stimenot)."\n";
		}
		
//	  以上为处理仓库不同的情况 --------------------------------------------------------------------------------

//以下处理-gt 与  -v 的预定  1查询得到需要预订的order_id
       
       $gtime=microtime(true);
       $sql = "SELECT o.order_id
                  FROM ecshop.ecs_order_info o force index (order_info_multi_index)
                  inner JOIN romeo.supplier_return_request_gt gt on  gt.supplier_return_gt_sn = o.order_sn 
                  INNER JOIN romeo.supplier_return_request rt ON   rt.supplier_return_id = gt.supplier_return_id
                   left JOIN romeo.order_inv_reserved r ON r.order_id = o.order_id
                 WHERE order_status =1 AND shipping_status =6
				          AND (o.facility_id is not null) AND (o.facility_id != '')
                   AND o.order_type_id in ('SUPPLIER_RETURN','SUPPLIER_SALE','SUPPLIER_TRANSFER')                    
                   and (r.status = 'N' or r.status is null ) and rt.status = 'CREATED' and rt.check_status != 'DENY'
                   and o.party_id =  '{$party_id}' {$distributor_param} and o.order_time>=date_add(now(),interval - {$days} day) order by o.order_id            
			".' -- ROI '.__LINE__.PHP_EOL;
		
		$order_ids=Yii::app()->getDb()->createCommand($sql)->queryColumn();
		echo '-gt reserved sql: '.$sql.PHP_EOL;			
		$gvtime2=microtime(true);
		echo date("Y-m-d H:i:s")." partyId:".$party_id." 查询 -gt需要预订订单大小:".count($order_ids)."--SQL执行时间：".($gvtime2-$gtime)."\n";
		$order_ids = $this->get_enough_reserved_order_ids($order_ids,$reserve_order_number);		
		if(!empty($order_ids)) {
			$stimee=microtime(true);
			foreach ( $order_ids as $order_id ){
				$this->run(array('InitAndReserveByOrderId','--order_id='.$order_id['order_id']));
			}
			$gvtime3=microtime(true);
	        echo date("Y-m-d H:i:s")." partyId:".$party_id." ReserveByOrderId执行时间:". ($gvtime3-$gvtime2)."\n";
		}
        echo date("Y-m-d H:i:s")." partyId:".$party_id." 预定-gt 符合条件订单后内存：".memory_get_usage()."\n"; 
        
        
       $vtime=microtime(true);
       $sql = "SELECT o.order_id
                 FROM ecshop.ecs_order_info o force index (order_info_multi_index)
                   left JOIN romeo.order_inv_reserved r ON r.order_id = o.order_id
                   left JOIN romeo.inventory_item_detail iid on  convert(o.order_id using utf8) = iid.order_id
                 WHERE iid.order_id is null and order_status = 2 AND shipping_status = 0
				   AND (o.facility_id is not null) AND (o.facility_id != '')
                   AND o.order_type_id = 'VARIANCE_MINUS'                    
                   and (r.status = 'N' or r.status is null )
                   and o.party_id =  '{$party_id}' {$distributor_param} and o.order_time>=date_add(now(),interval - {$days} day) order by o.order_id            
			".' -- ROI '.__LINE__.PHP_EOL;
		
		$order_ids=Yii::app()->getDb()->createCommand($sql)->queryColumn();
		echo '-v reserved sql: '.$sql.PHP_EOL;			
		$vtime2=microtime(true);
		echo date("Y-m-d H:i:s")." partyId:".$party_id." 查询 -gt -v 需要预订订单大小:".count($order_ids)."--SQL执行时间：".($vtime2-$vtime)."\n";
		$order_ids = $this->get_enough_reserved_order_ids($order_ids,$reserve_order_number);		
		if(!empty($order_ids)) {
			$gvtime2=microtime(true);
			foreach ( $order_ids as $order_id ){
				$this->run(array('InitAndReserveByOrderId','--order_id='.$order_id['order_id']));
			}
			$gvtime3=microtime(true);
	        echo date("Y-m-d H:i:s")." partyId:".$party_id." ReserveByOrderId执行时间:". ($gvtime3-$gvtime2)."\n";
		}
        echo date("Y-m-d H:i:s")." partyId:".$party_id." 预定 -v符合条件订单后内存：".memory_get_usage()."\n"; 

//-----------------------------------------------------------------------------------------------------------------------

		$stimek=microtime(true);
				
		$sql = "SELECT o.order_id
                 FROM ecshop.ecs_order_info o force index (order_info_multi_index)
                   inner JOIN romeo.order_inv_reserved r ON r.order_id = o.order_id
                   inner JOIN ecshop.ecs_payment p ON o.pay_id = p.pay_id
                 WHERE order_status in(1,11) AND shipping_status=0
				   AND (o.facility_id is not null) AND (o.facility_id != '')
                   AND( (o.order_type_id = 'SALE' AND (  o.pay_status = 2 or(p.pay_code = 'cod' and p.is_cod = 1) ) )
                     OR o.order_type_id IN ('RMA_EXCHANGE', 'SHIP_ONLY'))
                 and r.status = 'N'
                and o.party_id =  '{$party_id}' {$distributor_param} and o.order_time>=date_add(now(),interval - {$days} day) order by o.order_id            
			".' -- ROI '.__LINE__.PHP_EOL;
		$order_ids=Yii::app()->getDb()->createCommand($sql)->queryColumn();
		echo 'First sql: '.$sql.PHP_EOL;	
//		echo $party_id."查询r.status = N 状态需要预定订单后内存：".memory_get_usage()."\n"; 
		$mtime=microtime(true);
		echo date("Y-m-d H:i:s")." partyId:".$party_id." 查询r.status = N 状态需要预订订单大小:".count($order_ids)."--SQL执行时间：".($mtime-$stimek)."\n";
		// 得到可预订量足够的订单，并且限制数量
		
		
		$order_ids = $this->get_enough_reserved_order_ids($order_ids,$reserve_order_number);		
		$order_num_n = count($order_ids);
		echo date("Y-m-d H:i:s")." partyId:".$party_id." 过滤得到r.status = N 库存满足的预定订单大小:".count($order_ids)."\n";
//		echo $party_id."过滤r.status = N 状态的订单后内存：".memory_get_usage()."\n"; 
		if(!empty($order_ids)) {
			$stimee=microtime(true);
			foreach ( $order_ids as $order_id ){
				$this->run(array('ReserveByOrderId','--order_id='.$order_id['order_id']));
			}
			$etimee=microtime(true);
	        $ttotall=$etimee-$stimee;
	        echo date("Y-m-d H:i:s")." partyId:".$party_id." ReserveByOrderId执行时间:". $ttotall."\n";
		}
        echo date("Y-m-d H:i:s")." partyId:".$party_id." 预定r.status = N 符合条件订单后内存：".memory_get_usage()."\n"; 
        
        $stimeeer=microtime(true);
		$sql = "SELECT o.order_id
                 FROM ecshop.ecs_order_info o force index(order_info_multi_index)
                   LEFT JOIN romeo.order_inv_reserved r ON r.order_id = o.order_id
                   LEFT JOIN ecshop.ecs_payment p ON o.pay_id = p.pay_id
                 WHERE order_status in (1,11) AND shipping_status=0
				   AND (o.facility_id is not null) AND (o.facility_id != '')
                   AND( (o.order_type_id = 'SALE' AND (  o.pay_status = 2 or(p.pay_code = 'cod' and p.is_cod = 1) ) )
                     OR o.order_type_id IN ('RMA_EXCHANGE', 'SHIP_ONLY'))
                 and r.status is null
                and o.party_id =  '{$party_id}' {$distributor_param} and o.order_time>=date_add(now(),interval - {$days} day) order by o.order_id 
		".' -- ROI '.__LINE__.PHP_EOL;
		$order_ids=Yii::app()->getDb()->createCommand($sql)->queryColumn();
		echo 'Second sql: '.$sql.PHP_EOL;
//		echo $party_id."查询r.status is null 状态需要预定订单后内存：".memory_get_usage()."\n";
		echo date("Y-m-d H:i:s")." partyId:".$party_id." 查询 r.status is null需要预定订单大小：".count($order_ids)."--SQL执行时间：".(microtime(true)-$stimeeer)."\n";
		// 得到可预订量足够的订单，并且限制数量
		$order_ids = $this->get_enough_reserved_order_ids($order_ids,$reserve_order_number);
		echo date("Y-m-d H:i:s")." partyId:".$party_id." 过滤r.status is null状态库存满足要求的订单大小".count($order_ids)."\n";
//		echo $party_id."过滤r.status is null 状态订单后内存使用：".memory_get_usage()."\n";
		if(!empty($order_ids)) {
			$sttime=microtime(true);
			foreach ( $order_ids as $order_id ){
				$this->run(array('InitAndReserveByOrderId','--order_id='.$order_id['order_id']));
			}
			$eetime=microtime(true);
	        $totall=$eetime-$sttime;
	        echo date("Y-m-d H:i:s")." partyId:".$party_id." InitAndReserveByOrderId执行时间:". $totall."\n";
		}
//		unset($order_ids);
		$etime=microtime(true);
	    $total=$etime-$stime;
	    echo date("Y-m-d H:i:s")." partyId:".$party_id." 订单预定执行时间: ". $total." 订单预定总数:".($order_num_n+count($order_ids))." 预定结束内存：".memory_get_usage()."\n";	  
		
	}
	public function actionInitAndReserveByOrderId($order_id){
		
		$order_test = $this->is_enough_for_reserved($order_id);
		
		if(empty($order_test)){
			echo 'order_id:'.$order_id.' not enough goods for reserve';
			return null;
		}
		
		echo 'order_id:'.$order_id.' createOrderReserveByOrderId and ReserveByOrderId';
		
		$this->getSoapClient()->createOrderReserveByOrderId(array('orderId'=>$order_id));
		$this->run(array('ReserveByOrderId','--order_id='.$order_id));			  
	}
	
	public function actionCreateReserveInfoByOrderIdToItem($order_id){
		
		$order_test = $this->is_enough_for_reserved($order_id);
		
		if(empty($order_test)){
			echo 'order_id:'.$order_id.' not enough goods for reserve';
			return null;
		}
		
		echo 'order_id:'.$order_id.' createOrderReserveByOrderId and ReserveByOrderId';
		
		$this->getSoapClient()->createOrderReserveByOrderId(array('orderId'=>$order_id));
		$this->run(array('ReserveOrderByOrderIdToItem','--order_id='.$order_id));			  
	}
	
	public function actionReserveByOrderId($order_id){
				
		$sql = "SELECT 		rd.order_item_id,oi.party_id,p.IN_STORAGE_MODE,oi.order_type_id
			FROM 		romeo.order_inv_reserved_detail rd
			LEFT JOIN   ecshop.ecs_order_info oi ON rd.order_id = oi.order_id
			LEFT JOIN  romeo.party p on p.party_id = oi.party_id		
			WHERE 		rd.order_id = '{$order_id}' and rd.status = 'N'
            GROUP BY    rd.order_item_id ".' -- ROI '.__LINE__.PHP_EOL;
		$order_goods_ids=Yii::app()->getDb()->createCommand($sql)->queryAll();
		echo(date('Y-m-d H:i:s').' order_id '.$order_id.' Begin to YUDING...'.PHP_EOL);
//		echo "\n";
		foreach ( $order_goods_ids as $order_goods_id ){
			if($order_goods_id['IN_STORAGE_MODE']==3 && ($order_goods_id['order_type_id']=='RMA_EXCHANGE' ||$order_goods_id['order_type_id']=='SHIP_ONLY' || $order_goods_id['order_type_id']=='SALE' )){
				Yii::app()->getComponent('romeo')->InventoryService->reserveOrderInventoryWithInventoryItemByOrderGoodsId(array('orderGoodsId'=>$order_goods_id['order_item_id']));
			} else {
				Yii::app()->getComponent('romeo')->InventoryService->reserveOrderInventoryByOrderGoodsId(array('orderGoodsId'=>$order_goods_id['order_item_id']));
			}
		}
		$this->getSoapClient()->updateOrderReserveStatusByOrderId(array('orderId'=>$order_id));
		
	}
	
	public function actionReserveOrderByOrderIdToItem($order_id){
				
		$sql = "SELECT 		rd.order_item_id,oi.party_id,p.IN_STORAGE_MODE,oi.order_type_id
			FROM 		romeo.order_inv_reserved_detail rd
			LEFT JOIN   ecshop.ecs_order_info oi ON rd.order_id = oi.order_id
			LEFT JOIN  romeo.party p on p.party_id = oi.party_id		
			WHERE 		rd.order_id = '{$order_id}' and rd.status = 'N'
            GROUP BY    rd.order_item_id ".' -- ROI '.__LINE__.PHP_EOL;
		$order_goods_ids=Yii::app()->getDb()->createCommand($sql)->queryAll();
		echo(date('Y-m-d H:i:s').' order_id '.$order_id.' Begin to YUDING...'.PHP_EOL);
		foreach ( $order_goods_ids as $order_goods_id ){
			Yii::app()->getComponent('romeo')->InventoryService->reserveOrderInventoryWithInventoryItemByOrderGoodsId(array('orderGoodsId'=>$order_goods_id['order_item_id']));
		}
		$this->getSoapClient()->updateOrderReserveStatusByOrderId(array('orderId'=>$order_id));
		
	}
	
	// 得到可预订量足够的订单，并且限制数量
	public function get_enough_reserved_order_ids($order_ids,$problem_order_number) {
		if(empty($order_ids)) return null;
		require_once(ROOT_PATH.'includes/lib_common.php');
			
		$arrayList = array_chunk($order_ids, 1000);
		$num = count($arrayList);
		$order_list=array();
		for ($index = 0; $index < $num; $index++) {	
//			echo "get_enough_reserved_order_ids循环第: ". ($index+1)."次\n";
			$stime=microtime(true);
			$sql =  "SELECT oi.order_id
			         from ecshop.ecs_order_info oi
			         left join romeo.order_inv_reserved re ON oi.order_id = re.order_id
			         where oi.order_id ".db_create_in($arrayList[$index])."
			         and (re.status ='N' OR re.status is null)
			         -- 排除没有product_id的
			         and not exists (
			             select 1 from
			             ecshop.ecs_order_goods og
			             left join romeo.product_mapping pm ON og.goods_id = pm.ecs_goods_id and og.style_id = pm.ecs_style_id
			             where og.order_id = oi.order_id and pm.product_id is null
			             limit 1
			         )
			         -- 排除可预订量不够的
					 and not exists (
						 select 1 from
						 ecshop.ecs_order_info o
						 inner join ecshop.ecs_order_goods og ON o.order_id = og.order_id
						 inner join romeo.product_mapping pm ON og.goods_id = pm.ecs_goods_id and og.style_id = pm.ecs_style_id
						 inner join romeo.inventory_summary im ON pm.product_id = im.product_id and o.facility_id = im.facility_id
												and im.status_id = og.status_id
						 left join romeo.order_inv_reserved_detail ird ON convert(og.rec_id using utf8) = ird.order_item_id
						 left join ecshop.ecs_goods_inventory_reserved r ON pm.ecs_goods_id = r.goods_id and pm.ecs_style_id = r.style_id
												and r.facility_id = o.facility_id and r.status = 'OK'
		
						 where o.order_id = oi.order_id
						 -- 只针对没预定成功的
						 and (ird.status ='N' or ird.status is null)
		                 -- 商品数>可用库存数
						 and og.goods_number > im.available_to_reserved - if(og.status_id='INV_STTS_AVAILABL',ifnull(r.reserve_number,0),0)		
						 limit 1
					 ) order by oi.order_id
			       ".' -- ROI '.__LINE__.PHP_EOL;
//		    echo $sql."\n";
			$orders=Yii::app()->getDb()->createCommand($sql)->queryAll();	
//			echo "一次循环符合要求的订单数量".count($orders)."\n";	
		    $order_list =array_merge($order_list,$orders);
//		    echo "合并后的订单数量".count($order_list)."\n";		    
		    if(count($order_list) >$problem_order_number){
		    	return $order_list;
		    }			
		}
		$etime=microtime(true);
		$total=$etime-$stime;
		echo date("Y-m-d H:i:s")." get_enough_reserved_order_ids执行时间: ". $total."\n";
		return $order_list;
		
	}
	
	//判断单个订单库存是否充足
	public function is_enough_for_reserved($order_id) {
		$stime=microtime(true);
		if(empty($order_id)) return null;
		require_once(ROOT_PATH.'includes/lib_common.php');
			$sql =  "SELECT oi.order_id
			         from ecshop.ecs_order_info oi
			         left join romeo.order_inv_reserved re ON oi.order_id = re.order_id
			         where oi.order_id = {$order_id}
			         and (re.status ='N' OR re.status is null)
			         and not exists (
			             select 1 from
			             ecshop.ecs_order_goods og
			             left join romeo.product_mapping pm ON og.goods_id = pm.ecs_goods_id and og.style_id = pm.ecs_style_id
			             where og.order_id = oi.order_id and pm.product_id is null
			             limit 1
			         )
					 and not exists (
						select 
						o.order_id ,sum(og.goods_number) as goodnum ,og.goods_id,og.style_id,ifnull(im.available_to_reserved,0) as available,
						ifnull(r.reserve_number,0) as reserve,og.status_id
						from
						ecshop.ecs_order_info o
						inner join ecshop.ecs_order_goods og ON o.order_id = og.order_id
						inner join romeo.product_mapping pm ON og.goods_id = pm.ecs_goods_id and og.style_id = pm.ecs_style_id
						left join romeo.inventory_summary im ON pm.product_id = im.product_id and o.facility_id = im.facility_id
											and im.status_id = og.status_id
						left join romeo.order_inv_reserved_detail ird ON convert(og.rec_id using utf8) = ird.order_item_id
						left join ecshop.ecs_goods_inventory_reserved r ON pm.ecs_goods_id = r.goods_id and pm.ecs_style_id = r.style_id
											and r.facility_id = o.facility_id and r.status = 'OK'
						where o.order_id = oi.order_id
						and (ird.status ='N' or ird.status is null)
						group by goods_id,style_id
						having goodnum > available - if(og.status_id ='INV_STTS_AVAILABLE', reserve	,0)		
						limit 1
					 ) 
			       ".' -- ROI '.__LINE__.PHP_EOL;
//			echo 'is_enough_for_reserved sql: '.$sql.PHP_EOL;      
			$order_id=Yii::app()->getDb()->createCommand($sql)->queryAll();	
			
			$etime=microtime(true);
		    $total=$etime-$stime;
		    
		    echo date("Y-m-d H:i:s")." get_enough_reserved_order_ids 执行时间: ". $total."\n";
		    			
		return $order_id;
		
	}
	
	/**
	 * 发货后还原库存预订(内贸)
	 */
	public function actionFinishedOrderInventoryReservation($party_id='all') {
		$start = microtime(true);
		
		$order_list_new = array ();
		
		//将-gt的库存还原预定
		$sql2 = "select distinct oi.order_id 
		    from romeo.order_inv_reserved oir
		    inner JOIN ecshop.ecs_order_info oi on oir.order_id = oi.order_id
		   	inner JOIN romeo.supplier_return_request_gt gt on  gt.supplier_return_gt_sn = oi.order_sn 
		    INNER JOIN romeo.supplier_return_request rt ON   rt.supplier_return_id = gt.supplier_return_id
		    where oir.status ='Y' and oi.order_type_id in ('SUPPLIER_RETURN','SUPPLIER_SALE','SUPPLIER_TRANSFER') 
		    and  rt.status = 'COMPLETION'";
		$order_gtList=Yii::app()->getDb()->createCommand($sql2)->queryAll();
		foreach ( $order_gtList as $order ) {
			array_push ( $order_list_new, $order ['order_id'] );
		}
		
		//将已经出库的VARIANCE_MINUS还原预定
		$sql3 ="select distinct oi.order_id 
		    from romeo.order_inv_reserved oir
		    inner JOIN ecshop.ecs_order_info oi on oir.order_id = oi.order_id
		   	inner JOIN romeo.inventory_item_detail iid on  convert(oi.order_id using utf8) = iid.order_id
		    where oir.status ='Y' and oi.order_type_id = 'VARIANCE_MINUS'"; 
		    
		$order_vList=Yii::app()->getDb()->createCommand($sql3)->queryAll();
		foreach ($order_vList as $order ) {
			array_push ( $order_list_new, $order ['order_id'] );
		}
		
		// 查看romeo.order_inv_reserved
		$sql1 = "SELECT 		distinct oi.order_id 
			FROM 		ecshop.ecs_order_info oi
			LEFT JOIN	romeo.order_inv_reserved r on oi.order_id = r.order_id
			LEFT JOIN 	romeo.party pt on convert(oi.party_id using utf8) = pt.party_id
			WHERE 		oi.order_status = 1
			AND 		oi.shipping_status in(1,2,3,8,9,11,12)
			AND 		pt.parent_party_id != '65542'
			AND 		r.status = 'Y'
		".' -- ROI '.__LINE__.PHP_EOL;
		$order_list=Yii::app()->getDb()->createCommand($sql1)->queryAll();
		
		foreach ( $order_list as $order ) {
			array_push ( $order_list_new, $order ['order_id'] );
		}
		
		$order_list_new = array_unique($order_list_new);
		foreach($order_list_new as $order)
		{	
			try {
	    		$response=Yii::app()->getComponent('romeo')->InventoryService->finishedOrderInventoryReservation(array('orderId'=>$order));
			} catch (Exception $e) {
				echo "[" . date('c'). "] ". $order['order_id']. "还原库存预订异常:" . $e->getMessage()."\n";
			}		            
		}
		echo "[". date('c'). "]发货后还原库存预订：" . count($order_list_new)." 耗时：".(microtime(true)-$start)."\n";				
		
	}
	
	/**
	 * 取消订单的预定，还原库存
	 */
	public function actionCancelOrderInventoryReservation($order_id) {
		$start = microtime(true);
		try {
    		$response=Yii::app()->getComponent('romeo')->InventoryService->cancelOrderInventoryReservation(array('orderId'=>$order_id));
		} catch (Exception $e) {
			echo "[" . date('c'). "] ". $order['order_id']. "取消预订还原库存异常:" . $e->getMessage()."\n";
		}		            

		echo "[". date('c'). "]取消预订还原库存： 耗时：".(microtime(true)-$start)."\n";
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

	protected function getSoapClient()
	{
		if(!$this->soapclient)
		{
			$this->soapclient = Yii::app()->getComponent('romeo')->InventoryService;
		}
		return $this->soapclient;
	}
	
	protected function getTaobaoShopList($group)
    {
        static $partyList;
        if(!isset($partyList))
        {
            $sql = "SELECT party_id,name from romeo.party where is_leaf='Y' and  status = 'ok' and parent_party_id != '65542' and need_to_reserve = 1
			".' -- ROI '.__LINE__.PHP_EOL;
            
            if($group == 0 ){   	// 全部业务组  不做处理
            }
            elseif ($group == 1) {      //金佰利
                echo " 金佰利\n";
            	$sql .= " and party_id in ('65558') ";
            }
            elseif ($group == 2) {     //雀巢  
                echo " 雀巢 \n";
            	$sql .= " and party_id in ('65553') ";
            }
            elseif ($group == 3) {   	//惠氏 康贝 亨氏 安满 金宝贝
                echo " 康贝、中粮、百事、桂格 \n";
            	$sql .= " and party_id in ('65586','65625','65608','65632') ";
            }
            elseif ($group == 4) {      //除了康贝,雀巢,保乐力加,金佰利,金宝贝的其它组织      加入 妮维雅 65646,优色林 65645用于新预定测试
               echo "除了金佰利 雀巢  康贝  中粮  百事  桂格 乐其跨境的其它组织\n";
            	$sql .= "  and party_id not in ('65558','65553','65586','65625','65608','65632','65638','65646','65645') ";
            }
            elseif ($group == 5) {      //乐其跨境
               echo "乐其跨境\n";
               $sql .= " and party_id in ('65638') ";
            }
            else {            	// 非法参数
            	echo 'invad $group='.$group."\n";
            	$sql .= " and  1=0 ";
            }
            $partyList = Yii::app()->getDb()->createCommand($sql)->queryAll();
        }
        return $partyList;
    }
    
    /**
	 * 每个命令执行前执行
	 *
	 * @param string $action
	 * @param array $params
	 * @return boolean
	 */
	protected function beforeAction($action, $params)
	{
		if(strnatcasecmp($action,'index')==0)
			return true;
		
		// 加锁
		$lockName="commands.".$this->getName().".".$action.$params[0];
		if(($lock=Yii::app()->getComponent('lock'))!==null && $lock->acquire($lockName,60*60))
		{
			// 记录命令的最后一次执行的开始时间
			$key='commands.'.$this->getName().'.'.strtolower($action).':start';
			Yii::app()->setGlobalState($key,microtime(true));
			return true;	
		}
		else
		{
			echo "[".date('Y-m-d H:i:s')."] 命令{$action}正在被执行，或上次执行异常导致独占锁没有被释放，请稍候再试。\n";
			return false;
		}
	}
	
	/**
	 * 执行完毕后执行
	 * 
	 * @param string $action the action name
	 * @param array $params the parameters to be passed to the action method.
	 */
	protected function afterAction($action,$params)
	{	
		if(strnatcasecmp($action,'index')==0)
			return;

		// 记录命令的最后一次执行的完毕时间
		$key='commands.'.$this->getName().'.'.strtolower($action).':done';
		Yii::app()->setGlobalState($key,microtime(true));
		
		// 释放锁
		$lockName="commands.".$this->getName().".".$action.$params[0];
		$lock=Yii::app()->getComponent('lock');
		$lock->release($lockName);
	}


}