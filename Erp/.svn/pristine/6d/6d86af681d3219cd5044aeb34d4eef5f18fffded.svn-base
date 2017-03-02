<?php
define('IN_ECS', true);
define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'admin/includes/init.php';
require_once ROOT_PATH . 'includes/helper/mail.php';
require_once ROOT_PATH .'admin/distribution.inc.php';
require_once ROOT_PATH .'RomeoApi/lib_payment.php';
require_once ROOT_PATH .'admin/function.php';

/**
 * 外包耗材绑定 + 耗材虚拟出库（正常称重绑定与外包绑定）
 */
class AutoDeliveryConsumableCommand extends CConsoleCommand {
	private $slave; // Slave数据库
	private $master;
	
	private $soapclient;
	private $orderSoapclient;
	public $actionUser = 'cronjob';
	/**
	 * 当不指定ActionName时的默认调用
	 */
	public function actionIndex() {
		$this->run ( array ('AddTrackConsumable'));
        $this->run ( array ('AutoDeliveryConsumable' ) );
        
	}
	
	public function actionAddTrackConsumable($days=3){
		$sql = " SELECT so.order_id ,s.TRACKING_NUMBER,
			(select if(consumables is null or consumables='',null,consumables) from ecshop.ecs_out_ship_goods_configure sgc 
				where sgc.facility_id = ot.facility_id and sgc.outer_id = ot.outer_id 
				and sgc.party_id = ot.party_id and sgc.start_time<oi.order_time and sgc.end_time > oi.order_time
				limit 1) as consumables
			FROM ecshop.ecs_out_ship_order so 
			INNER JOIN ecshop.ecs_out_ship_order_task ot on ot.task_id = so.task_id  
			INNER JOIN ecshop.ecs_order_info oi on oi.order_id = so.order_id
			INNER JOIN romeo.shipment s on s.PRIMARY_ORDER_ID = convert(so.order_id using utf8) and s.tracking_number is not null and s.tracking_number !='' 
			where  so.create_time > date_sub(NOW(),interval {$days} DAY)  and oi.shipping_status in (1,2)
			and  not EXISTS ( 
					 select 1 from ecshop.ecs_barcode_tracking_mapping btm  
					 where  btm.tracking_number = s.tracking_number ) 
			having consumables is not null ";
		$orders = $this->getMaster ()->createCommand ( $sql )->queryAll ();
		if(!empty($orders)){
			foreach($orders as $order){
				if(!empty($order['TRACKING_NUMBER']) && !empty($order['consumables'])) {
					$insert_sql = "INSERT INTO ecshop.ecs_barcode_tracking_mapping(tracking_number,barcode,is_pick_up,created_stamp,last_updated_stamp) 
						VALUES ('{$order['TRACKING_NUMBER']}','{$order['consumables']}','N',NOW(),NOW())"; 
					$this->getMaster ()->createCommand ($insert_sql)->execute();
				}
			}
		}
											
	}
	
	/**
	 * 耗材自动出库
	 * 
	 */
	public function actionAutoDeliveryConsumable() {
		//history problem:status = Y,not out
		$this->run ( array ('DeliveryProblem' ) );
		//no inventory problem:status = S,not out
		$this->run ( array ('AutoDeliveryConsumableProblem' ) );
		//normal out:status = N,not out
		$this->run ( array ('AutoDeliveryConsumableNew' ) );
	}
	public function actionAutoDeliveryConsumableNew(){
		$start_order_time = date("Y-m-d H:i:s",strtotime("-10 days",time()));
		// 未出库的条码和运单
		$sql = "select
		                    bm.barcode,bm.tracking_number, s.shipment_id, s.primary_order_id as order_id, eg.goods_id, eg.goods_name
		        from        ecshop.ecs_barcode_tracking_mapping bm
		        inner join  romeo.shipment s ON bm.tracking_number = s.tracking_number
		        inner join ecshop.ecs_order_info oi ON s.primary_order_id = oi.order_id
				inner join ecshop.ecs_goods eg on eg.barcode = bm.barcode and ((CONVERT(eg.goods_party_id USING utf8 ) = s.party_id ) OR (eg.goods_party_id = 65595))
		        where 
		                    is_pick_up = 'N' and eg.is_delete = 0 and bm.created_stamp >='{$start_order_time}'
		        -- 上海的只出2014-10-01后的订单
		        and not (oi.facility_id in('12768420','19568549','22143846','22143847','24196974',
                '3633071','69897656','76161272','81569822','81569823') and oi.order_time < '2014-10-01')
		        order by bm.created_stamp
		        ";

		$shipments = $this->getMaster ()->createCommand ( $sql )->queryAll ();
	    foreach ($shipments as $shipment){
	    	try{
				
				$this->add_consumable_order_goods($shipment['shipment_id'],$shipment['barcode'],$shipment['order_id'],$shipment['goods_id'],$shipment['goods_name']);
	    		$this->deliverShipmentConsumable($shipment['shipment_id'],$shipment['barcode'],$shipment['tracking_number']);
	    	}catch(Exception $e){
	    		$this->update_cousumable_out_status($shipment['barcode'],$shipment['tracking_number'],'S');
	    		echo("[". date('c'). "]deliverShipmentConsumable(".$shipment['shipment_id'].") soap call exception:".$e->getMessage()."\n");
	    	}
	    }
	}
	/*
	 * 正常出库错误的调度
	*/
	public function actionAutoDeliveryConsumableProblem(){
		$start_order_time = date("Y-m-d H:i:s",strtotime("-10 days",time()));
		$sql = "select
		                    bm.barcode,bm.tracking_number, s.shipment_id, s.primary_order_id as order_id, eg.goods_id, eg.goods_name
		        from        ecshop.ecs_barcode_tracking_mapping bm
		        inner join  romeo.shipment s ON bm.tracking_number = s.tracking_number
				inner join ecshop.ecs_goods eg on eg.barcode = bm.barcode and ((CONVERT(eg.goods_party_id USING utf8 ) = s.party_id ) OR (eg.goods_party_id = 65595))
		        where 
		                    is_pick_up = 'S'  and bm.created_stamp >='{$start_order_time}' and eg.is_delete = 0
		        order by bm.created_stamp
		        ";
		$shipments = $this->getMaster ()->createCommand ( $sql )->queryAll ();
	    foreach ($shipments as $shipment){
	    	try{
				
				$this->add_consumable_order_goods($shipment['shipment_id'],$shipment['barcode'],$shipment['order_id'],$shipment['goods_id'],$shipment['goods_name']);
	    		$this->deliverShipmentConsumable($shipment['shipment_id'],$shipment['barcode'],$shipment['tracking_number']);
	    	}catch(Exception $e){
	    		$this->update_cousumable_out_status($shipment['barcode'],$shipment['tracking_number'],'S');
	    		echo("[". date('c'). "]deliverShipmentConsumable(".$shipment['shipment_id'].") soap call exception:".$e->getMessage()."\n");
	    	}
	    }
	}
	/*
	 * 历史错误数据调度
	*/
	public function actionDeliveryProblem(){
		$start_order_time = date("Y-m-d H:i:s",strtotime("-10 days",time()));
		$sql = "select 
					os.shipment_id,s.tracking_number,
					g.barcode,g.goods_id,os.order_id
					from ecshop.ecs_barcode_tracking_mapping bm
					inner join romeo.shipment s on bm.tracking_number = s.tracking_number
					inner join  romeo.order_shipment os on s.shipment_id = os.shipment_id
					inner join ecshop.ecs_order_goods eg on os.order_id = eg.order_id
					inner join ecshop.ecs_goods AS g on eg.goods_id = g.goods_id and bm.barcode = g.barcode and g.is_delete = 0
					left join romeo.inventory_item_detail iid on convert(eg.rec_id using utf8)= iid.order_goods_id
					where  bm.is_pick_up = 'Y'  and bm.created_stamp >='{$start_order_time}' and iid.order_goods_id is null and s.created_stamp >= '2014-10-01'
					group by os.shipment_id,s.tracking_number,
					g.barcode 
					order by bm.created_stamp
		        ";
		
		$shipments = $this->getMaster ()->createCommand ( $sql )->queryAll ();
	    foreach ($shipments as $shipment){
	    	try{
	    		$this->deliverShipmentConsumable($shipment['shipment_id'],$shipment['barcode'],$shipment['tracking_number']);
	    		echo("[". date('c'). "]deliverShipmentConsumable(".$shipment['shipment_id'].") (".$shipment['barcode'].") (".$shipment['tracking_number'].") \n");
				
	    	}catch(Exception $e){
	    		$this->update_cousumable_out_status($shipment['barcode'],$shipment['tracking_number'],'S');
	    		echo("[". date('c'). "]deliverShipmentConsumable(".$shipment['shipment_id'].") soap call exception:".$e->getMessage()."\n");
	    	}
	    }
	}
	private function add_consumable_order_goods($shipmentId,$barcode,$orderId,$goodsId,$goodsName){
		//考虑合并订单，追加面单情况
		$sql = " select count(distinct s2.shipment_id) as num
				from romeo.shipment s1
				inner join romeo.order_shipment os on s1.primary_order_id = os.order_id
				inner join romeo.shipment s2 on s2.shipment_id = os.shipment_id
				inner join ecshop.ecs_barcode_tracking_mapping bm on bm.tracking_number = s2.tracking_number
				where s1.shipment_id = '{$shipmentId}' and bm.barcode = '{$barcode}' ";
		$need_number = $this->db_get_one($sql,'num');
		$sql = " select count(eg.goods_number) as num from 
				ecshop.ecs_order_goods eg 
				inner join ecshop.ecs_goods AS g on eg.goods_id = g.goods_id
				where eg.order_id = '{$orderId}' and g.barcode = '{$barcode}' ";
		$already_add_number = $this->db_get_one($sql,'num');
		if($already_add_number >= $need_number){
			return;
		}
		$order_goods = new stdClass();
		$order_goods->orderId = $orderId;
		$order_goods->goodsId = $goodsId;
		$order_goods->goodsPrice = '0.00';
		$order_goods->goodsNumber = '1';
		$order_goods->styleId = '0';
		$order_goods->goodsName = $goodsName;
		$this->getOrderSoapClient()->addOrderGoodsNew(array('orderItem'=>$order_goods));
	}
	private function db_get_one($sql,$columnName){
		$result = $this->getSlave ()->createCommand ( $sql )->queryAll ();
		if(empty($result)){
			return null;
		}else{
			return $result[0][$columnName];
		}
	}
	private function deliverShipmentConsumable($shipmentId,$barcode,$trackingNumber){
		// 根据条码判断是否需要转仓：通用耗材商品的条码需要转仓
		$check_tysp = check_tysp_barcode($barcode);
		// 未出库的条码和运单
		$sql = "select oi.order_id,og.goods_name,og.rec_id,oi.facility_id,oi.order_status, oi.pay_status, oi.shipping_status, oi.invoice_status,pm.product_id
				from romeo.order_shipment os
		        inner join  ecshop.ecs_order_info oi ON os.order_id = oi.order_id
		        inner join  ecshop.ecs_order_goods og ON oi.order_id = og.order_id
		        inner join  ecshop.ecs_goods g ON og.goods_id = g.goods_id
		        inner join romeo.product_mapping pm on og.goods_id = pm.ecs_goods_id and og.style_id = pm.ecs_style_id
		        where 
					os.shipment_id = '{$shipmentId}' and g.barcode = '{$barcode}' and g.is_delete = 0
		        ";		
		$orders = $this->getMaster()->createCommand( $sql )->queryAll();
		if(empty($orders)) {
			echo("[". date('c'). "]deliverShipmentConsumable(".$shipmentId.") (".$barcode.") (".$trackingNumber.") null"."\n");
			return false;
		}
	    foreach ($orders as $order){
	    	$facility_id = $order['facility_id'];
			if($check_tysp) {
				// 根据仓库区域最后都转化为 通用商品上海仓，通用商品东莞仓，通用商品北京仓
				$facility_id = convert_facility_by_region($facility_id);
			}
			if($this->newIsEnough($order['rec_id'],$order['product_id'],$facility_id)){
				$response = $this->getSoapClient()->oneKeyOrderComsumablePick(array('orderGoodsId'=>$order['rec_id'],
																'facilityId'=>$facility_id,
																'actionUser'=>$this->actionUser));
				echo("[". date('c'). "]deliverShipmentConsumable(".$shipmentId.") success "."\n");
			}else{
				echo("[". date('c'). "]deliverShipmentConsumable(".$shipmentId.") failed 库存不足 "."\n");
				$this->update_cousumable_out_status($barcode,$trackingNumber,'S');
				$sql_status = "select is_pick_up from ecshop.ecs_barcode_tracking_mapping where tracking_number = '{$trackingNumber}' and barcode = '{$barcode}'";
				$is_pick_up = $this->getMaster()->createCommand( $sql )->queryOne();
				echo("[". date('c'). "]deliverShipmentConsumable_status(".$shipmentId.")  (".$trackingNumber.") (".$is_pick_up.") \n");
				return false;
			}
	    	
			if(!$response->return){
				$this->update_cousumable_out_status($barcode,$trackingNumber,'S');
				echo("[". date('c'). "]deliverShipmentConsumable(".$shipmentId.") failed 出库失败 "."\n");
				return false;
			}
		    $this->_addOrderAction($order['order_id'],$order['goods_name'],
		    						$barcode,$order['order_status'],
		    						$order['pay_status'],$order['shipping_status'],
		    						$order['invoice_status']);
	    }
	    update_barcode_tracking_mapping($barcode,$trackingNumber);
	}
	private function newIsEnough($orderGoodsId,$productId,$facilityId){
		$sql = "select eog.goods_number + ifnull(sum(iid.quantity_on_hand_diff),0) as num
				from  ecshop.ecs_order_goods eog  
    			left join romeo.inventory_item_detail iid 
    				on iid.order_goods_id = convert(eog.rec_id using utf8)
				where eog.rec_id = '{$orderGoodsId}'";
		$out_num = $this->db_get_one($sql,'num');
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
	    $sum_inventory_num = $this->db_get_one($sql,'num');
	    
	    
	    
	    if($sum_inventory_num < $out_num){
	    	return false;
	    }else{
	    	return true;
	    }
	}
	private function _addOrderAction($orderId,$goodsName,$barcode,$orderStatus,$payStatus,$shipping_status,$invoice_status){
		$actionNote = "系统添加耗材商品：". $goodsName . " ".$barcode." 数量：1";
        $order_action_status['order_id'] = $orderId;
		$order_action_status['order_status'] = $orderStatus;
		$order_action_status['pay_status'] = $payStatus;
		$order_action_status['shipping_status'] = $shipping_status;
		$order_action_status['invoice_status'] = $invoice_status;
		$order_action_status['action_user'] = $this->actionUser;
		$order_action_status['action_time'] = date("Y-m-d H:i:s", time());
        $order_action_status['action_note'] = $actionNote;
		orderActionLog($order_action_status);
	}
	private function update_cousumable_out_status($barcode,$trackingNumber,$status){
		$sql = "update ecshop.ecs_barcode_tracking_mapping set is_pick_up = '{$status}',last_updated_stamp = now() where barcode = '{$barcode}' and tracking_number = '{$tracking_number}' limit 1";
		$this->getMaster()->createCommand($sql)->execute();
	}
	protected function getSoapClient()
	{
		if(!$this->soapclient)
		{
			$this->soapclient = Yii::app()->getComponent('romeo')->InventoryService;
		}
		return $this->soapclient;
	}
	protected function getOrderSoapClient()
	{
		if(!$this->orderSoapclient)
		{
			$this->orderSoapclient = Yii::app()->getComponent('romeo')->OrderService;
		}
		return $this->orderSoapclient;
	}
	
    /**
	 * 取得slave数据库连接
	 * 
	 * @return CDbConnection
	 */ 
    protected function getSlave()
    {
        if(!$this->slave)
        {
            if(($this->slave=Yii::app()->getComponent('slave'))===null)
            $this->slave=Yii::app()->getDb();
            $this->slave->setActive(true);
        }
        return $this->slave;
    }

    /**
     * 取得master数据库连接
     *
     * @return CDbConnection
     */
    protected function getMaster() {
        if (! $this->master) {
            $this->master = Yii::app ()->getDb ();
            $this->master->setActive ( true );
        }
        return $this->master;
    }
}
