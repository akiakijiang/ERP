<?php
define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'includes/helper/mail.php';
//require_once(ROOT_PATH . 'includes/helper/array.php');
//Yii::import('application.commands.LockedCommand', true);
/*
 * Created on 2013-9-23
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
class OneKeyInventoryCommand extends CConsoleCommand
{
	private $db;  // db数据库
	private $soapclient;
	public $turnNum = 20;  // 完结批捡个数
	public $party_mode = 2;

	public $actionUser = 'cronjob';
	
    public function actionIndex()
    {
        $currentTime=microtime(true);
        // 商品同步
        $this->run(array('BatchPick'));
    }

    public function actionBatchPick()
    {
    	$sql = "
			select bp.batch_pick_sn
			from  romeo.batch_pick bp
			inner join romeo.inventory_location_reserve ilr 
						on bp.batch_pick_sn = ilr.batch_pick_sn
			inner join romeo.shipment os 
					on os.shipment_id = ilr.shipment_id
			inner join romeo.party p
					on p.party_id = os.party_id 
			where bp.is_pick = 'N' and p.system_mode = {$this->party_mode}
			group by bp.batch_pick_sn limit  {$this->turnNum}"; 
	 	$bpsn_list = $this->getDB()->createCommand($sql)->queryAll();
		foreach($bpsn_list as $bpsn){
			$lock_name = $this->getBpsnPartyId($bpsn['batch_pick_sn']);
			$lock_file_name = $this->get_file_lock_path($lock_name, 'pick');
		    $lock_file_point = fopen($lock_file_name, "w+");
		    $would_block = false;
			if(flock($lock_file_point, LOCK_EX|LOCK_NB, $would_block)){
				$this->run(array('OneKeyBatch','--bpsn='.$bpsn['batch_pick_sn']));
				flock($lock_file_point, LOCK_UN);
        		fclose($lock_file_point);
			}else{
		    	fclose($lock_file_point);
		    	echo("[". date('c'). "] 同业务组有人正在完结，请稍后"."\n"); 
		    }
			
	 		if(count($bpsn_list) > 1){
	 			usleep(500000);
	 		}
	 	}
    }
	public function actionTest($shipmentId){
		echo($this->deliverShipmentInventory($shipmentId));
	}
	public function getBpsnPartyId($bpsn){
		$sql = "
			select  oi.party_id
			from  romeo.batch_pick bp
			inner join romeo.batch_pick_mapping bpm 
						on bp.batch_pick_sn = bpm.batch_pick_sn
			inner join romeo.order_shipment os 
						on os.shipment_id = bpm.shipment_id
			inner join ecshop.ecs_order_info oi 
						on oi.order_id =  CAST( os.order_id AS UNSIGNED )
			where bp.batch_pick_sn = '{$bpsn}'
			limit 1 ";
		return $this->getOneBySql($sql,'party_id');
	}
    /*
     * 正常流程完结 批拣单
     * */
    public function actionOneKeyProblemBatch(){
    	$sql = "
			select bp.batch_pick_sn
			from  romeo.batch_pick bp
			inner join romeo.inventory_location_reserve ilr 
						on bp.batch_pick_sn = ilr.batch_pick_sn
			where bp.is_pick = 'S' 
			group by bp.batch_pick_sn limit  {$this->turnNum}"; 
	 	$bpsn_list = $this->getDB()->createCommand($sql)->queryAll();
		foreach($bpsn_list as $bpsn){
			$lock_name = $this->getBpsnPartyId($bpsn['batch_pick_sn']);
			$lock_file_name = $this->get_file_lock_path($lock_name, 'pick');
		    $lock_file_point = fopen($lock_file_name, "w+");
		    $would_block = false;
			if(flock($lock_file_point, LOCK_EX|LOCK_NB, $would_block)){
				$this->run(array('OneKeyBatch','--bpsn='.$bpsn['batch_pick_sn']));
				flock($lock_file_point, LOCK_UN);
        		fclose($lock_file_point);
			}else{
		    	fclose($lock_file_point);
		    	echo("[". date('c'). "] 同业务组有人正在完结，请稍后"."\n");
		    }
	 	}
    }
	public function actionOneKeyBatch($bpsn){
		ini_set('max_execution_time', '0');
		$start = microtime(true); //微秒数
		$status = true;
	 	try{
			$sql = "
				select shipment_id from romeo.batch_pick_mapping where batch_pick_sn = '{$bpsn}' and is_pick = 'N' group by shipment_id";
			$shipmentIds = $this->getDB()->createCommand($sql)->queryAll();
			foreach($shipmentIds as $shipmentId){
				if(!$this->deliverShipmentInventory($shipmentId['shipment_id'])){
					$status = false;
				}
			}
			if($status){
				$this->getSoapClient()->terminalBatchPickSimple(array('batchPickSn'=>$bpsn,'actionUser'=>$this->actionUser));
			}else{
				$sql = "
					UPDATE  romeo.batch_pick bp
					set is_pick = 'S'
					where batch_pick_sn = '{$bpsn}'";
				$this->getDB()->createCommand($sql)->execute();
			}
		
	        $end1 = microtime(true)-$start;
			echo("[". date('c'). "]{$bpsn} del-total time:{$end1}\n");
	    }catch (Exception $e){
	 		echo("[". date('c'). "]{$bpsn}:".$e->getMessage()."\n");	
	    }
	}

	/*
     * 正常流程完结 shipmentId
     * */
	protected function deliverShipmentInventory($shipmentId){
		try{
			$sql = "select oi.order_id,oi.facility_id,oi.order_status
					from romeo.order_shipment os 
					inner join ecshop.ecs_order_info oi
						on oi.order_id =  CAST( os.order_id AS UNSIGNED )
					where os.shipment_id = '{$shipmentId}'";
			$orderIds = $this->getDB()->createCommand($sql)->queryAll();
			foreach($orderIds as $orderId){
				if($orderId['order_status'] == 2) {
					echo("[". date('c'). "]shipmentId:{$shipmentId} order_id:{$orderId['order_id']} is cancel"."\n");
					return false;
				}
				if(!$this->deliverOrderInventory($orderId['order_id'],$orderId['facility_id'])){
					return false;
				}
			}
			$this->getSoapClient()->updateBatchShipmentStatus(array('shipmentId'=>$shipmentId,'actionUser'=>$this->actionUser));
			return true;
		}catch(Exception $e){
			echo("[". date('c'). "]shipmentId:{$shipmentId} error".$e->getMessage()."\n");
			return false;
		}
	}
	protected function deliverOrderInventory($orderId,$facilityId){
		if($this->isOrderInventoryEnough($orderId,$facilityId)){
	    	return $this->delNewInv($orderId);
		}else{
			return false;
		}
	}
	
	protected function isOrderInventoryEnough($orderId,$facilityId){
		$sql = "select og.rec_id,pm.product_id,pm.ecs_goods_id,pm.ecs_style_id
	 				from ecshop.ecs_order_goods og
					inner join romeo.product_mapping pm 
						on og.goods_id = pm.ecs_goods_id and og.style_id = pm.ecs_style_id
					where order_id = '{$orderId}'";
		$order_goods_list = $this->getDB()->createCommand($sql)->queryAll();
    
		foreach($order_goods_list as $order_goods){
			if(!$this->newIsEnough($order_goods['rec_id'],$order_goods['product_id'],$facilityId)){
				return false;
			}
		}
		return true;
	}
	public function newIsEnough($orderGoodsId,$productId,$facilityId){
		$sql = "select eog.goods_number + ifnull(sum(iid.quantity_on_hand_diff),0) as num
				from  ecshop.ecs_order_goods eog  
    			left join romeo.inventory_item_detail iid 
    				on iid.order_goods_id = convert(eog.rec_id using utf8)
				where eog.rec_id = '{$orderGoodsId}'";
		$out_num = $this->getOneBySql($sql,'num');
		if($out_num == 0){
			return true;
		}
		//检测新库存是否有足够的数量
		$sql = "
		    select sum(ii.quantity_on_hand_total) as num
				from romeo.inventory_item ii 
			where ii.quantity_on_hand_total > 0 
			      and ii.facility_id = '{$facilityId}' and ii.product_id = '{$productId}'
			      and ii.status_id = 'INV_STTS_AVAILABLE'
		 ";
	    $sum_inventory_num = $this->getOneBySql($sql,'num');
	    if($sum_inventory_num < $out_num){
	    	return false;
	    }else{
	    	return true;
	    }
	}

	protected function delNewInv($orderId)
    {
	  $request = array('orderId' => $orderId,
	  					'actionUser' => $this->actionUser);
	  try{ 
		  $response = $this->getSoapClient()->oneKeyOrderPick($request);
	  	  return $response->return;
	  }catch (Exception $e) {
	      echo("[". date('c'). "]oneKeyOrderPick soap call exception:".$e->getMessage());
	      return false;
	  }
	}
 
     /**
	 * 取得master数据库连接
	 * 
	 * @return CDbConnection
	 */ 
    protected function getDB()
    {
        if(!$this->db)
        {
            $this->db=Yii::app()->getDb();
            $this->db->setActive(true);
        }
        return $this->db;
    }
    public function getOneBySql($sql,$columnName){
		$results = $this->getDB()->createCommand($sql)->queryAll();
		if(empty($results)){
			return null;
		}else{
			return $results[0][$columnName];
		}
	}
  	protected function getSoapClient()
	{
		if(!$this->soapclient)
		{
			$this->soapclient = Yii::app()->getComponent('romeo')->InventoryService;
		}
		return $this->soapclient;
	}
 	/*
 	 * get to 中需要的order_id
 	 * */
 	protected function getOrderIds($bpsn,$goods_id,$style_id)
 	{
	 	$sql = "
	 		select  
	 			oi.order_id
			from romeo.batch_pick bp  
			inner join romeo.batch_pick_mapping bpm  
					on bp.batch_pick_sn = bpm.batch_pick_sn  
			inner join romeo.order_shipment os  
				on bpm.shipment_id = os.shipment_id  
			inner join ecshop.ecs_order_info oi  
				on oi.order_id = CAST( os.order_id AS UNSIGNED )  
			inner join ecshop.ecs_order_goods og  
				on oi.order_id=og.order_id  
			where bp.batch_pick_sn = '{$bpsn}' AND og.goods_id='{$goods_id}' and og.style_id='{$style_id}' 
			group by oi.order_id
	 	";
	 	$ids = $this->getDB()->createCommand($sql)->queryAll();
	 	$result = array();
	 	foreach($ids as $id){
	 		$result[] = $id['order_id'];
	 	}
	 	return $result;
 	}
 	/**
	 * 获得文件锁路径
	 *
	 * @param string $file_name
	 * @return string
	 */
	protected function get_file_lock_path($file_name = '', $namespace = null) {
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
?>