<?php
define('IN_ECS', true);
define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'admin/includes/init.php';
require_once ROOT_PATH . 'data/master_config.php';
require_once ROOT_PATH . 'includes/helper/mail.php';
require_once(ROOT_PATH.'RomeoApi/lib_soap.php');
include_once ROOT_PATH . 'admin/function.php';
Yii::import('application.commands.LockedCommand', true);

class EccoCommand extends LockedCommand {
	
    private $master; // Master数据库    
    
    /**
     * 当不指定ActionName时的默认调用
     */
    public function actionIndex() {
    	//初始化数据
        $this->run(array('InitOrder'));
        $this->run(array('SyncStoreInfo'));
    }
    
     /**
     * 初始化数据
     */
    public function actionInitOrder() {
    	$this->log("begin InitOrder");
    	$start = microtime(true);
        $sql = "
			SELECT oi.order_id,oi.order_type_id
			FROM ecshop.ecs_order_info oi
			LEFT JOIN ecshop.brand_ecco_order beo on oi.order_id = beo.order_id
			where oi.party_id = '65562' and oi.distributor_id in ('318', '1517') and ((oi.order_type_id ='SALE' AND oi.pay_status = 2) 
			OR (oi.order_type_id='RMA_EXCHANGE')) and oi.order_status = 1 and oi.shipping_status = 0 
			and (select if(count(os.shipment_id) = 1, 1, null) from romeo.order_shipment os where convert(oi.order_id using utf8) = os.order_id) is not null 
    		and beo.ecco_order_id is null limit 100 
         	union all
			SELECT oi.order_id,oi.order_type_id
			FROM ecshop.ecs_order_info oi
			INNER JOIN ecshop.service s on s.back_order_id = oi.order_id
			LEFT JOIN ecshop.brand_ecco_order beo on oi.order_id = beo.order_id
			INNER JOIN ecshop.brand_ecco_order beo2 on beo2.order_id = s.order_id 
			where oi.party_id = '65562'  and oi.order_type_id = 'RMA_RETURN'
			and s.service_type IN ('1', '2')  AND s.service_status = '1' AND s.back_shipping_status IN(0,5)
			and beo.ecco_order_id is null limit 100 
        ";
        $orders = $this->getMaster()->createCommand ($sql)->queryAll();
        $i = 0;
        if (! $orders || empty($orders)) {
        	$this->log("order empty");
        } else {
	        foreach ($orders as $order) {
	        	$transaction = $this->getMaster()->beginTransaction();
	        	$sql = "
	        		insert into ecshop.brand_ecco_order 
						(order_id, created_stamp,order_type)
					values 
						({$order['order_id']}, now(),'{$order['order_type_id']}')
				";
				$sql_action = "
					insert into ecshop.brand_ecco_order_action 
						(order_id,action_type,action_time,action_note,action_user)
					values
						({$order['order_id']},'INIT',now(),'订单生成','系统')";
				try{
					$this->getMaster()->createCommand ($sql)->execute();
					$this->getMaster()->createCommand ($sql_action)->execute();
					$transaction->commit();
				}catch(Exception $e){
					$this->log("there is some error when insert order into brand_ecco_order(_action)");
					$transaction->rollback();
				}
				$this->log("order_id {$order['order_id']} init success");
				$i++;
	        }
        }
        
        $this->log("total time :".(microtime(true)-$start) . " count {$i}");
        $this->log("end InitOrder");
    }
    
    /**
     * 同步店仓数据
     * */
	public function actionSyncStoreInfo(){
		$this->log("Ecco SyncStoreInfo start");
		$start = microtime(true);
		$count = 0;
		$success_count = 0;
		
		try {
			$file_contents = $this->get_getData(ECCOMC_WEBSERVICE_URL.'getStores');
		} catch (Exception $e) {
			$this->log("Call ".ECCOMC_WEBSERVICE_URL."getStores Exception: " . $e->getMessage());
			$this->sendMail("[ERP]SyncStoreInfo Command Error", "Call ".ECCOMC_WEBSERVICE_URL."getStores Exception: " . $e->getMessage());
		}
		$this->log("Call ".ECCOMC_WEBSERVICE_URL."getStores Result: " . $file_contents);
		
		if (!empty($file_contents) && $file_contents != 'No items') {
			$stores = json_decode($file_contents, true);
			
			if ($stores && is_array($stores)) {
				// 插入商铺记录SQL准备
				$items = array ();
				foreach ($stores as $store) {
					$items[] = array(
							'midware_store_id'=>$store['store_id'],
							'portal_store_id'=>$store['portal_store_id'],
							'store_name'=>$store['store_name'],
							'manager'=>$store['manager'],
							'mobile'=>$store['mobile'],
							'telephone'=>$store['telephone'],
							'province'=>$store['province'],
							'city'=>$store['city'],
							'district'=>$store['district'],
							'address'=>$store['address'],
							'zipcode'=>$store['zipcode'],
							'isactive'=>$store['isactive'],
					);
					$count++;
				}
				
				foreach ($items as $key=>$item){
					$mark = false;
					if(!$this->isExists('ecshop.brand_ecco_store','portal_store_id',$item['portal_store_id'])) {
						$this->log("Insert ecshop.brand_ecco_store. portal_store_id: {$item['portal_store_id']}");
						$mark = $this->DataInsertionWithTimestamp('ecshop.brand_ecco_store', $item, "", $items[$key]['ecco_store_id']);
					}else{
						$this->log("Update ecshop.brand_ecco_store. portal_store_id: {$item['portal_store_id']}");
						$mark = $this->DataUpdateWithTimestamp('ecshop.brand_ecco_store', $item, 'portal_store_id', $item['portal_store_id']);
					}
					
					if($mark) {
						$success_count++;
					}
				}
				
				$this->log("Ecco SyncStoreInfo Success");
			}
			
		}
		
		$this->log("Total time :".(microtime(true)-$start) . " count: {$count}, success_count: {$success_count}");
        $this->log("Ecco SyncStoreInfo end");
	}
	
	/**
     * Ecco采购入库出库
     */
    public function actionShipOrder() {
    	//筛选ecshop.brand_ecco_order表中承诺并已发货的订单order_id（ecs_order_info表中也同步已发货但没有出库记录的）
    	$sql = "select oi.order_id,oi.facility_id,oi.party_id  
    			 from ecshop.ecs_order_info oi  
    			 inner join ecshop.brand_ecco_order beo on oi.order_id = beo.order_id  
    			 inner join ecshop.ecs_order_goods og on og.order_id = oi.order_id  
    			 left join romeo.inventory_item_detail iid on iid.order_goods_id = convert(og.rec_id using utf8)
    			 where oi.party_id='65562' and beo.order_type in('SALE','RMA_EXCHANGE') and oi.order_status = 1  
    			 and  oi.shipping_status = 1 and iid.inventory_item_detail_id is null 
    			 GROUP BY oi.order_id ";
    	$order_ids = $this->getMaster()->createCommand ($sql)->queryAll();
    	//采购：根据要发货的订单商品生成采购批次，包含多个采购订单
    	//入库：根据采购批次，多个采购订单分别入库
    	//出库：根据要发货订单获取所有商品信息，逐一出库
    	foreach($order_ids as $order){
    		global $facility_id;
			$facility_id = $order['facility_id'];
	    	$party_id = $order['party_id'];
	    	$batch_info = array("party_id"=>$party_id, "facility_id"=>$facility_id);
    		$goods_sql = "select order_id,goods_id,goods_number,style_id from ecshop.ecs_order_goods where order_id = '{$order['order_id']}'";
    		$goods = $this->getMaster()->createCommand ($goods_sql)->queryAll();
    		$goods_list = array();
    		foreach($goods as $good){
    			array_push($goods_list,array("goods_id"=>$good['goods_id'], "style_id"=>$good['style_id'], "goods_number"=>$good['goods_number']));
    		}
			//下采购订单，成功则返回采购批次
    		$result = generate_order($batch_info, $goods_list);
	    	if($result['err_no']==0){
	    		$batch_order_id = $result['message'];
	    		$this->log("Ecco线下发货订单id=".$order['order_id']."生成采购单 ，批次batch_order_id = ".$batch_order_id."\n");
	    		$c_order_ids = "select og.goods_number,og.order_id,oi.facility_id " .
	    				" from ecshop.ecs_batch_order_mapping m " .
	    				" LEFT JOIN ecshop.ecs_order_info oi on oi.order_id = m.order_id" .
	    				" LEFT JOIN ecshop.ecs_order_goods og on og.order_id = m.order_id" .
	    			" where m.batch_order_id = '{$batch_order_id}' ";
	    		$order_ins = $this->getMaster()->createCommand ($c_order_ids)->queryAll();
	    		$result1='';
	    		foreach($order_ins as $order_in){
	    			//入库
	    			global $is_command;
	    			$is_command = true;
	    			$result2 = actual_inventory_in($order_in['order_id'], $order_in['goods_number'], true, 'INV_STTS_AVAILABLE', $order_in['facility_id'], 'EccoCommand');
	    			if($result2['res'] == 'fail'){
	    				$result1 = 'fail';
	    				break;
	    			}
	    		}
	    		if($result1=='fail'){
	    			$this->log("order_id {$order['order_id']} inventory in fail");
	    			$this->sendMail("shipOrder actual_inventory_in fail ".$order['order_id'] , " -- order_id:".$order['order_id']." ;batch_order_id:".$batch_order_id);
	    		}else{
	    			$this->log("Ecco线下发货订单id=".$order['order_id']."对应采购订单已成功入库\n");
	    			$order_list_sql = "select og.rec_id as order_goods_id,og.goods_id,og.style_id,og.goods_number,og.order_id,oi.facility_id " .
	    					"  from ecshop.ecs_order_info oi  " .
	    					" LEFT JOIN ecshop.ecs_order_goods og on og.order_id = oi.order_id " .
	    					" where oi.order_id = '{$order['order_id']}'";
	    			$order_list = $this->getMaster()->createCommand ($order_list_sql)->queryAll();		
	    			
	    			foreach ($order_list as $item ) {
	    				$msg = null;
	    				$info = check_out_goods($item);
			 			if ($info['msg'] != 'success') {
				 			$msg = "check_out_goods: {$info['back']}";
				 			$this->sendMail("shipOrder check_out_goods fail ".$item['order_id'] , " -- order_goods_id:".$item['order_goods_id']." ;error: ".$msg);
			 			}
	    			}
	    			if (check_batch_out_storage_status($order['order_id'])) {
				 		$this->log("Ecco线下发货订单id=".$order['order_id']."虚拟出库成功\n");
				 	}else{
				 		$this->sendMail("shipOrder actual_inventory_out fail ".$order['order_id'] , " -- order_id:".$order['order_id']);
				 	}
	    		}
    		}else{
    			$mes = $result['message'];
    			$this->sendMail("shipOrder generate_order fail ".$order['order_id'] , " -- order_id:".$order['order_id']."message :".$mes);
    		}
    	}
    	
    }

	/**
	 * ECCO退货线下店铺入库，ERP系统操作虚拟入库出库(ECCO无串号商品)
	 */	
	public function actionBackOrderInventory(){
		global $db;
		$sql = "SELECT s.service_id,beo.order_id,
				beo.return_action_status check_status,beo.return_check_result check_result
				from ecshop.brand_ecco_order beo 
				INNER JOIN  ecshop.service s on beo.order_id = s.back_order_id
				where  s.service_type IN ('1', '2')  AND s.party_id = '65562'
				AND s.service_status = '1' AND s.back_shipping_status IN(0,5) and beo.order_type='RMA_RETURN' AND beo.return_action_status != 'INIT' ";
		$back_orders = $db->getAll($sql);
		include_once ROOT_PATH . 'admin/includes/lib_service.php';
		require_once(ROOT_PATH."RomeoApi/lib_RMATrack.php");
		foreach($back_orders as $back_order){
			$service_id = $back_order['service_id'];
			$check_status = $back_order['check_status'];
			$check_result = $back_order['check_result'];
			if($check_status == 'PASS'){
				$this->log("Ecco线下退换货订单order_id = ".$back_order['order_id']."即将开始ERP虚拟入库------\n");
				$result= back_goods_add_inventory($service_id);
				if($result){
					$this->log("Ecco线下退换货订单order_id = ".$back_order['order_id']."成功ERP虚拟入库\n");
					$sql0 = "UPDATE ecshop.service SET back_shipping_status = 12,outer_check_status=23,
	            		    inner_check_status = 32,
			                service_status = '2', 
			                service_call_status = '1',
			                check_result = '{$check_result}'
			                WHERE service_id = '{$service_id}' ";
					$sql1 = "UPDATE ecshop.ecs_order_info SET shipping_time = UNIX_TIMESTAMP() WHERE order_id = {$back_order['order_id']} LIMIT 1 ";
					if($db->query($sql0) && $db->query($sql1)){
						$sql = "select * from ecshop.service where service_id = '{$service_id}' ";
						$service = $db->getRow($sql);
						$service['log_note'] = 'O2O门店操作入库';
						$service['log_type'] = 'LOGISTIC';
						if(!service_log($service)){
					    	$this->log("Ecco线下退换货订单order_id = ".$back_order['order_id']."更新售后日志失败\n");
						}
					}
					if(isRMATrackNeeded()){
						try{
							$result = getTrackByServiceId($service_id);
					        if ($result->total > 0) {
					            $tracks = wrap_object_to_array($result->resultList->Track);
					            foreach ($tracks as $track) {
					                $track->receivedDate = date("Y-m-d H:i:s");
					                $track->receivedUser = $_SESSION['admin_name'];
					                updateTrack($track);
					            }
					        }    
						}catch(Exception $e){
							sys_msg("对不起，更新受理时间失败！请联系ERP组", 1);
						}
					}
					$order_list_sql = "select og.rec_id as order_goods_id,og.goods_id,og.style_id,og.goods_number,og.order_id,oi.facility_id " .
		    					"  from ecshop.ecs_order_info oi  " .
		    					" LEFT JOIN ecshop.ecs_order_goods og on og.order_id = oi.order_id " .
		    					" where oi.order_id = {$back_order['order_id']}";
		    		$order_list =$db->getAll($order_list_sql);	
		    		//退货订单正常流程只有入库不出库，所以inventoty_item_detail表中根据order_id只会有入库数据。
		    		//但现在要针对ecco店仓订单出库，由于入库成功出库也可以成功。但	check_batch_out_storage_status检查时goods_number+inventory_item_item.quantity_on_hand_diff=no_out_number == goods_number
		    		//actual_inventory_out($order_list); 
		    		//虚拟出库不改订单状态
		    		$out_check_flag = true;
		    		foreach($order_list as $item){
		    			$info = check_out_goods($item);
				 		if ($info['msg'] != 'success') {
							$msg = "check_out_goods: {$info['back']}";
							$this->log("[ERROR]Ecco线下虚拟出库订单order_id = {$item['order_id']},order_goods_id = {$item['order_goods_id']}  error: {$msg}\n");
							$out_check_flag = false;
							break;
				 		}
		    		}
				 	if($out_check_flag){
			 			//根据order_id对应romeo.inventory_item_detail表中sum(iid.quantity_on_hand_diff)==0来判断出库是否成功
			 			$sql = "select ifnull(sum(quantity_on_hand_diff),0) as no_out_number
						 	from romeo.inventory_item_detail
						 	where ORDER_ID = '{$back_order['order_id']}' ";
						$no_out_number = $db->getOne($sql);
						if($no_out_number == 0) {
						 	$this->log("Ecco线下退货订单order_id = ".$back_order['order_id']."在ERP系统中已虚拟出库\n");
						}else{
							$this->log("[ERROR]Ecco线下退货订单order_id = ".$back_order['order_id']."出库异常，请联系ERP\n");
						}
			 		}else{
			 			$this->log("[ERROR]Ecco线下退货订单order_id = ".$back_order['order_id']."在ERP系统中虚拟出库失败！\n");
			 			$this->sendMail("returnOrder back_goods_minus_inventory fail ".$back_order['order_id'] , " -- order_id:".$back_order['order_id']);
			 		}
		    		
		    		POSTSALE_CACHE_updateService(null,180,$service_id);
				}else{
					$this->log("Ecco线下退换货订单order_id = ".$back_order['order_id']."在ERP虚拟入库 fail!!\n");
					$this->sendMail("returnOrder back_goods_add_inventory fail ".$back_order['order_id'] , " -- order_id:".$back_order['order_id']);
				}
			}else if($check_status == 'BACK'){
				$transaction = $this->getMaster()->beginTransaction();
				$sql1 = "UPDATE ecshop.service SET 
	            		    inner_check_status = 33,
			                service_status = '3', 
			                service_call_status = '1',
			                check_result = '{$check_result}'
			                WHERE service_id = '{$service_id}' ";
			    $sql2 = "UPDATE ecshop.service_order_goods SET is_approved = 0, amount = 0 WHERE service_id = '{$service_id}'";
		       	$sql3 = "UPDATE ecshop.order_relation SET parent_order_id = 0, root_order_id = 0, parent_order_sn = '', root_order_sn = '' WHERE order_id = '{$service['back_order_id']}'";
		       	
		        if(!($db->query($sql1) && $db->query($sql2) && $db->query($sql3))){
					$transaction->rollback();
					$this->log("Ecco线下入库订单order_id = ".$back_order['order_id']."拒绝入库状态更新失败\n");
				}else{
					$sql = "select * from ecshop.service where service_id = '{$service_id}' ";
					$service = $db->getRow($sql);
					$service['log_note'] = 'O2O门店拒绝入库';
					$service['log_type'] = 'LOGISTIC';
					service_log($service);
					$transaction->commit();
				}
				POSTSALE_CACHE_updateService(null,180,$service_id);
			}
			 
		}
	} 
	/**
	 * 同步员工数据
	 * */
	public function actionSyncPromoterInfo(){
		$this->log("Ecco SyncPromoterInfo start");
		$start = microtime(true);
		$count = 0;
		$success_count = 0;
		
		try {
            $file_contents = $this->get_getData(ECCOMC_WEBSERVICE_URL.'getPromoters');
		} catch (Exception $e) {
			$this->log("Call ".ECCOMC_WEBSERVICE_URL."getPromoters Exception: " . $e->getMessage());
			$this->sendMail("[ERP]SyncPromoterInfo Command Error", "Call ".ECCOMC_WEBSERVICE_URL."getPromoters Exception: " . $e->getMessage());
		}
		$this->log("Call ".ECCOMC_WEBSERVICE_URL."getPromoters Result: " . $file_contents);
	
		if (!empty($file_contents) && $file_contents != 'No items') {
			$promoters = json_decode($file_contents, true);
				
			if ($promoters && is_array($promoters)) {
				// 插入员工记录SQL准备
				$items = array ();
				foreach ($promoters as $promoter) {
					$items[] = array(
							'midware_promoter_id'=>$promoter['promoter_id'],
							'portal_promoter_id'=>$promoter['portal_promoter_id'],
							'portal_promoter_no'=>$promoter['portal_promoter_no'],
							'portal_store_id'=>$promoter['portal_store_id'],
							'promoter_name'=>$promoter['promoter_name'],
							'mobile'=>$promoter['mobile'],
							'telephone'=>$promoter['telephone'],
							'address'=>$promoter['address'],
							'birthdate'=>$promoter['birthdate'],
							'isactive'=>$promoter['isactive'],
					);
					$count++;
				}
	
				foreach ($items as $key=>$item){
					$mark = false;
					if(!$this->isExists('ecshop.brand_ecco_promoter','portal_promoter_id',$item['portal_promoter_id'])) {
						$this->log("Insert ecshop.brand_ecco_promoter. portal_promoter_id: {$item['portal_promoter_id']}");
						// 用户名为员工编号，密码默认为00000000
						$item['user_name'] = $item['portal_promoter_no'];
						$item['password'] = md5('00000000');
						$mark = $this->DataInsertionWithTimestamp('ecshop.brand_ecco_promoter', $item, "", $items[$key]['ecco_promoter_id']);
					}else{
						$this->log("Update ecshop.brand_ecco_promoter. portal_promoter_id: {$item['portal_promoter_id']}");
						$mark = $this->DataUpdateWithTimestamp('ecshop.brand_ecco_promoter', $item, 'portal_promoter_id', $item['portal_promoter_id']);
					}
					
					if($mark) {
						$success_count++;
					}
				}
	
				$this->log("Ecco SyncPromoterInfo Success");
			}
				
		}
	
		$this->log("Total time :".(microtime(true)-$start) . " count: {$count}, success_count: {$success_count}");
		$this->log("Ecco SyncPromoterInfo end");
	}
	
	/**
	 * 同步POS用户数据
	 * */
	public function actionSyncUserInfo(){
		$this->log("Ecco SyncUserInfo start");
		$start = microtime(true);
		$count = 0;
		$success_count = 0;
	
		try {
			$file_contents = $this->get_getData(ECCOMC_WEBSERVICE_URL.'getUsers');
		} catch (Exception $e) {
			$this->log("Call ".ECCOMC_WEBSERVICE_URL."getUsers Exception: " . $e->getMessage());
			$this->sendMail("[ERP]SyncUserInfo Command Error", "Call ".ECCOMC_WEBSERVICE_URL."getUsers Exception: " . $e->getMessage());
		}
		$this->log("Call ".ECCOMC_WEBSERVICE_URL."getUsers Result: " . $file_contents);
	
		if (!empty($file_contents) && $file_contents != 'No items') {
			$users = json_decode($file_contents, true);
	
			if ($users && is_array($users)) {
				// 插入员工记录SQL准备
				$items = array ();
				foreach ($users as $user) {
					$items[] = array(
							'midware_user_id'=>$user['user_id'],
							'portal_user_id'=>$user['portal_user_id'],
							'portal_store_id'=>$user['portal_store_id'],
							'portal_distributor_id'=>$user['portal_distributor_id'],
							'email'=>$user['email'],
							'name'=>$user['name'],
							'truename'=>$user['truename'],
							'isactive'=>$user['isactive'],
					);
					$count++;
				}
	
				foreach ($items as $key=>$item){
					$mark = false;
					if(!$this->isExists('ecshop.brand_ecco_user','portal_user_id',$item['portal_user_id'])) {
						$this->log("Insert ecshop.brand_ecco_user. portal_user_id: {$item['portal_user_id']}");
						// 密码默认为00000000
						$item['password'] = md5('00000000');
						$mark = $this->DataInsertionWithTimestamp('ecshop.brand_ecco_user', $item, "", $items[$key]['ecco_user_id']);
					}else{
						$this->log("Update ecshop.brand_ecco_user. portal_user_id: {$item['portal_user_id']}");
						$mark = $this->DataUpdateWithTimestamp('ecshop.brand_ecco_user', $item, 'portal_user_id', $item['portal_user_id']);
					}
						
					if($mark) {
						$success_count++;
					}
				}
	
				$this->log("Ecco SyncUserInfo Success");
			}
	
		}
	
		$this->log("Total time :".(microtime(true)-$start) . " count: {$count}, success_count: {$success_count}");
		$this->log("Ecco SyncUserInfo end");
	}
    
    /**
     * 取得master数据库连接
     * 
     * @return CDbConnection
     */
    protected function getMaster() {
        if (! $this->master) {
            $this->master = Yii::app()->getDb();
            $this->master->setActive(true);
        }
        return $this->master;
    }
    
    private function log ($m) {
		print date("Y-m-d H:i:s") . " " . $m . "\r\n";
    }
    
    // data insertion with timestamp
    private function DataInsertionWithTimestamp($table_name, $data_map, $record_rec, &$insert_id){
    	$data_map['created_stamp'] = $data_map['last_update_stamp'] = date("Y-m-d H:i:s", time());
    	return $this->DataInsertion($table_name, $data_map, $record_rec, $insert_id);
    }
    
    // data insertion
    private function DataInsertion($table_name, $data_map, $record_rec, &$insert_id){
    	$attr_names = $attr_values = array();
    	foreach ($data_map as $key => $val) {
    		if(!is_array($val) && !($val ==='')){
    			$attr_names[] = $key;
    			$attr_values[] = $val;
    		}
    	}
    	$sql = "insert into " . $table_name . " (". implode(",", $attr_names) .") values ('". implode("','", $attr_values) ."')";
    	$this->log("DataInsertion_sql = ".$sql);
    	try{
    		if(!$this->getMaster()->createCommand($sql)->execute()){
    			$this->log($record_rec." failed to record in ".$table_name);
    			$this->log('DataInsertion SQL: '.$sql);
    			return false;
    		}else{
    			$insert_id = $this->getMaster()->getLastInsertID();
    			return true;
    		}
    	}
    	catch(Exception $e){
    		$this->log('DataInsertion SQL: '.$sql);
    		$this->log('DataInsertion Exception: '.$e->getMessage());
    		return false;
    	}
    
    }
    
    private function DataUpdateWithTimestamp($table_name, $data_map, $record_rec, &$id){
    	$data_map['last_update_stamp'] = date("Y-m-d H:i:s", time());
    	return $this->DataUpdate($table_name, $data_map, $record_rec, $id);
    }
    
    // data update
    private function DataUpdate($table_name, $data_map, $primary_key_name, $id){
    	$attr_names = array();
    	foreach ($data_map as $key => $val) {
    		if(!is_array($val) && !($val ==='')){
    			$attr_names[] = ($key."='".$val."'");
    		}
    	}
    	$sql = "update " . $table_name . " set ". implode(",", $attr_names) ." where ". $primary_key_name .'='. $id;
    	try{
    		$this->getMaster()->createCommand($sql)->execute();
    		return true;
    	}
    	catch(Exception $e){
    		$this->log('DataUpdate SQL: '.$sql);
    		$this->log('DataUpdate Exception: '.$e->getMessage());
    		return false;
    	}
    
    }
    
    // 判断数据是否已经存在
    private function isExists($table_name, $primary_key_name, $id){
    	$sql = "SELECT * FROM " . $table_name . " WHERE " . $primary_key_name . " = '" . $id . "' LIMIT 1";
    	try{
    		$result = $this->getMaster()->createCommand($sql)->queryRow();
    		if($result){
    			return true;
    		}
    		return false;
    	}catch(Exception $e){
    		$this->log('isExists SQL: '.$sql);
    		$this->log('isExists Exception: '.$e->getMessage());
    		return false;
    	}
    }
    
    public function actionSyncOrderStatus(){
  	    $this->log("Ecco SyncOrderStatus Begin");
   	    $start = microtime(true);
   	    
   	    $sql="SELECT beo.order_id erp_order_id, oi.order_status erp_order_status
   	    	  FROM ecshop.brand_ecco_order beo 
   	    	  INNER JOIN ecshop.ecs_order_info oi ON beo.order_id = oi.order_id 
   	    	  WHERE beo.erp_order_status <> oi.order_status AND oi.order_status IN (1,2)";
   	    
   	    $orders = $this->getMaster()->createCommand ($sql)->queryAll();
   	    $i = 0;
   	    if (!$orders) {
   	    	$this->log("Ecco SyncOrderStatus Order Empty");
   	    } else {
   	    	$restResult = $this->doRestApi("updateOrderStatus", $orders);
   	    	if ($restResult['http_code'] == 200) {
   	    		$this->log("Ecco SyncOrderStatus Rest API Success");
   	    		 
   	    		$orders = json_decode($restResult['result'], true);
   	    		if ($orders && is_array($orders)) {
   	    			foreach($orders as $order) {
   	    				$this->DataUpdate('ecshop.brand_ecco_order', $order, 'order_id', $order['order_id']);
   	    				$this->log("Ecco SyncOrderStatus Success. order_id: {$order['order_id']}, order_status -> {$order['erp_order_status']} ");
   	    				$i++;
   	    			}
   	    		} else {
   	    			$this->log("Result Data Decode Error");
   	    			$this->sendMail("SyncOrderStatus Result Data Decode Error", $restResult['result']);
   	    		}
   	    	} else {
   	    		$this->log("Ecco SyncOrderStatus Rest Error " . $restResult['http_code']);
   	    		$this->sendMail("Ecco SyncOrderStatus Rest Error " . $restResult['http_code'], $restResult['result']);
   	    	}
   	    }
   	    $this->log("Total time :".(microtime(true)-$start) . " Count : {$i}");
   	    $this->log("Ecco SyncOrderStatus End");
    }
    
    public function actionSyncShippingStatus() {
    	$this->log("Ecco SyncShippingStatus Begin");
    	$start = microtime(true);
    	$sql = "
    		select beo.order_id erp_order_id ,beo.order_type,o.order_status erp_order_status, o.shipping_status, o.shipping_name shipping_method, s.tracking_number, 
    			beo.check_action_status,beo.check_action_time,beo.ship_action_status,beo.ship_action_time,beo.return_action_status,beo.return_action_time,beo.portal_promoter_id,beo.store_id
    		from ecshop.brand_ecco_order beo 
    		inner join ecshop.ecs_order_info o on beo.order_id = o.order_id
    		left join romeo.order_shipment os on convert(o.order_id using utf8) = os.order_id 
    		left join romeo.shipment s on os.shipment_id = s.shipment_id 
    		where beo.shipping_note_status = 'INIT' and beo.download_status = 'SUCCESS'
    		and ((beo.order_type in ('SALE','RMA_EXCHANGE') AND 
    				((beo.check_action_status = 'PROMISE' and beo.ship_action_status in ('DISPATCH', 'BACK')) or (beo.check_action_status = 'REFUSE')) 
    			) OR(beo.order_type = 'RMA_RETURN' AND beo.return_action_status in ('PASS', 'BACK')))
    		group by beo.order_id 
    		order by beo.order_id ";
    	$orders = $this->getMaster()->createCommand ($sql)->queryAll();
    	$i = 0;
    	if (! $orders) {
    		$this->log("Ecco SyncShippingStatus Order Empty");
    	} else {
    		
    		$restResult = $this->doRestApi("shipOrders", $orders);
    		if ($restResult['http_code'] == 200) {
    			$this->log("Ecco SyncShippingStatus Rest Success ");
    			
    			$orders = json_decode($restResult['result'], true);
    			if ($orders && is_array($orders)) {
    				foreach($orders as $order) {
    					$this->DataUpdate('ecshop.brand_ecco_order', $order, 'order_id', $order['order_id']);
    					$this->log("Order Shipping or return {$order['shipping_note_status']} {$order['order_id']}");
    					$i++;
    				}
    			} else {
    				$this->log("Result Data Error");
    				$this->sendMail("SyncShippingStatus Result Data Error", $restResult['result']);
    			}
    		} else {
    			$this->log("Ecco SyncShippingStatus Rest Error " . $restResult['http_code']);
    			$this->sendMail("Ecco SyncShippingStatus Rest Error " . $restResult['http_code'], $restResult['result']);
    		}
    	}
    	$this->log("Total time :".(microtime(true)-$start) . " Count : {$i}");
    	$this->log("Ecco SyncShippingStatus End");
    	
    }
    
    private function doRestApi($method, $request){
    	$ch = curl_init();  
        curl_setopt($ch, CURLOPT_POST, 1);  
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, ECCOMC_WEBSERVICE_URL . $method);  
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request));  
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(  
            'Content-Type: application/json; charset=utf-8',  
            'Content-Length: ' . strlen(json_encode($request)))  
        );  
        ob_start();  
        $result =  curl_exec($ch);  
        $http_code = curl_getinfo($ch,CURLINFO_HTTP_CODE);
        return array("http_code" => $http_code, "result" => $result);
    }
    
    // GET data by CURL
    private function get_getData($url)
    {
    	$ch = curl_init($url);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    	curl_setopt($ch, CURLOPT_BINARYTRANSFER, true) ;
    	$data = curl_exec($ch);
    	curl_close($ch);
    	return $data;
    }
    
    function sendMail($subject, $body = null, $path = null, $file_name = null) {
		$mail=Helper_Mail::smtp();
		$mail->IsSMTP();                 // 启用SMTP
	    $mail->Host="smtp.exmail.qq.com";      //smtp服务器 以126邮箱为例子
	    $mail->SMTPAuth = true;         //启用smtp认证
	    $mail->Username =$GLOBALS['emailUsername'];   // 你的邮箱地址
	    $mail->Password = $GLOBALS['emailPassword'];      //你的邮箱密码  */
	    $mail->CharSet='UTF-8';
		$mail->Subject="【Erp-EccoCommand】" . $subject;
		$mail->SetFrom($GLOBALS['emailUsername'], '乐其网络科技');
		
		$mail->AddAddress('zjli@leqee.com', '李志杰');
		$mail->AddAddress('ytchen@leqee.com', '陈艳婷');
		
		$mail->Body = date("Y-m-d H:i:s") . " " . $body;
		if($path != null && $file_name != null){
			$mail->AddAttachment($path, $file_name);
		}
		try {
			if ($mail->Send()) {
				$this->log('mail send success');
		    } else {
		    	$this->log('mail send fail');
		    }
		} catch(Exception $e) {
			$this->log('mail send exception ' . $e->getMessage());
			// 屏蔽PHP邮箱 版本错误
			//Deprecated: Function set_magic_quotes_runtime() is deprecated in /mnt/hgfs/www/erpbrand/includes/phpmailer/class.phpmailer.php on line 1471 Deprecated: Function set_magic_quotes_runtime() is deprecated in /mnt/hgfs/www/erpbrand/includes/phpmailer/class.phpmailer.php on line 1475  Deprecated: Function set_magic_quotes_runtime() is deprecated in /mnt/hgfs/www/erpbrand/includes/phpmailer/class.phpmailer.php on line 1471 Deprecated: Function set_magic_quotes_runtime() is deprecated in /mnt/hgfs/www/erpbrand/includes/phpmailer/class.phpmailer.php on line 1475 
		}
	}
}	
	

