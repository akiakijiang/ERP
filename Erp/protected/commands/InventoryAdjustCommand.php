<?php
define ( 'IN_ECS', true );
define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'admin/includes/init.php';
Yii::import('application.commands.LockedCommand', true);
/*
 * -V自动调度说明：
 * （1）获得所有未进行过出库操作的订单，ecshop.ecs_vorder_request_info表里面有字段inventory_adjust，0表示没有进行过出库操作，n>=1及以上表示进行过n次出库操作
 * （2）订单每次出库操作，都是扫描订单下的所有商品，进行没有出库商品的出库操作
 * 
 */

class InventoryAdjustCommand extends LockedCommand {

	private $db; // db数据库
	private $inventory_adjust_index = 0;//对出$inventory_adjust_inde以下数次库的订单进行出库操作
	protected function getDB() {
		if (! $this->db) {
			$this->db = Yii::app ()->getDb ();
			$this->db->setActive ( true );
		}
		return $this->db;
	}
	public function actionIndex(){
		$times = 1;
		$this->beforeAction("work",$times);
		$this->actionWork($times);
		$this->afterAction("work",$times);
	}
	
	private function actionWork($times) {
		sleep(20);
		$this->inventory_adjust_index = $times;
		$party_list = $this->get_party_list();
		if (empty($party_list)) {
			echo "can not find party_list";
			return;
		}
		foreach ($party_list as $party_id){
			$vorder_array = $this->get_not_out_order ($party_id);  //扫描所有未出库的订单(分party扫描)
			foreach ( $vorder_array as $key => $vorder ) {
				$lock = $this->lock_order($vorder['vorder_id']);
				if ($lock) {
					try {
						$this->out_order ( $vorder['vorder_id'] ); //对订单进行操作,参数是一个数据，包括vorder_id
						$this->unlock_order($vorder['vorder_id']);
					} catch (Exception $e) {
						$this->unlock_order($vorder['vorder_id']);
					}
					
				}
			}
		}
	}
	
	private function get_party_list(){
		$sql = "select party_id, parent_party_id, name from romeo.party where parent_party_id <> 0 and parent_party_id <> 65535";
		$result = $this->getDB()->createCommand($sql)->queryAll();
		if (empty($result)) {
			return;
		}
		$party_list = array();
		foreach ($result as $key => $value){
			$party_list[] = $value['party_id'];
		}
		return $party_list;
	}
	
	private function lock_order($vorder_id){
		$sql = "select preprocess from ecshop.ecs_vorder_request_info where vorder_request_id = {$vorder_id}";
		$result = $this->getDB()->createCommand($sql)->queryAll();
		if (empty($result)) {
			echo "未找到映射vorder_id {$vorder_id} 所对应的预处理位";
			return false;
		}
		$preprocess = $result[0]['preprocess'];
		if ($preprocess == 1) {
			echo "vorder_id {$vorder_id} 所对应的订单正在执行中，跳过执行";
			return false;
		}else{
			$sql = "update ecshop.ecs_vorder_request_info set preprocess = 1 where vorder_request_id = {$vorder_id}";
			$this->getDB()->createCommand($sql)->execute();
			return true;
		}
	}
	private function unlock_order($vorder_id){
		$sql = "select preprocess from ecshop.ecs_vorder_request_info where vorder_request_id = {$vorder_id}";
		$result = $this->getDB()->createCommand($sql)->queryAll();
		if (empty($result)) {
			echo "未找到映射vorder_id {$vorder_id} 所对应的预处理位";
			return;
		}
		$sql = "update ecshop.ecs_vorder_request_info set preprocess = 0 where vorder_request_id = {$vorder_id}";
		$this->getDB()->createCommand($sql)->execute();
		return;
	}

	/*
	 * 获得所有的-v订单和相对应的order_info中的order_id
	 */
	private function get_not_out_order($party_id) {
		$sql = "select vorder_request_id as vorder_id  
				from ecshop.ecs_vorder_request_info evri 
				where " .
						//"-- evri.inventory_adjust <= {$this->inventory_adjust_index} and " .
						"vorder_status = 'COMPLETE' and evri.party_id = '{$party_id}'";
		$vorder_array = $this->getDB ()->createCommand ( $sql )->queryAll ();
		return $vorder_array;
	}
	//
	/*
	 * 对订单进行出库操作，会针对每个未出库商品进行出库操作
	 */
	private function out_order($vorder_id) {
		//做映射
		$result = $this->insert_order_info($vorder_id);
		if ($result == -1) {
			echo "vorder {$vorder_id} 映射失败";
			return;
		}
		
		$sql = "select order_id from ecshop.ecs_vorder_request_mapping where vorder_request_id = {$vorder_id}";
		$result = $this->getDB()->createCommand($sql)->queryAll();
		if (empty($result)) {
			echo "未找到映射order_id";
			return;
		}
		$order_id = $result[0]['order_id'];
		echo "out_order : ORDER ID ".$order_id." \r\n ";
		$order_goods_array = $this->get_order_goods ( $order_id, $vorder_id );
		if (empty($order_goods_array)) {
			$this->vorder_flag_adjust($vorder_id);
			return;
		}
		foreach ( $order_goods_array as $order_goods ) {
			$this->out_order_goods ($order_goods);//针对每一个商品进行出库操作
		}
		$this->vorder_flag_adjust($vorder_id);
	}
	//
	private function out_order_goods($order_goods) {
		$order_goods_id = $order_goods ["order_goods_id"];
		$serial_number = $order_goods ["serial_number"];
		$sql = "select order_goods_id from romeo.inventory_item_detail where order_goods_id = '{$order_goods_id}'";
		$result = $this->getDB()->createCommand($sql)->queryAll();
		if (!empty($result)) {
			echo "{$order_goods_id}已经出过库，不可以重复出库，自动修改vorder_request_item表出库标志位";
			$this->update_item_inventory($order_goods_id);
			return;
		}
		
		if (! empty ( $serial_number )) {
			$sql = "select order_type_id from ecshop.ecs_order_info oi
			inner join ecshop.ecs_order_goods og on oi.order_id = og.order_id
			where og.rec_id = '{$order_goods_id}'";
			$tmp2 = $this->getDB ()->createCommand ( $sql )->queryAll ();
			$order_type_id = $tmp2 [0] ['order_type_id'];
			$sql = " select 1 from romeo.inventory_item
					where serial_number = '{$serial_number}'
					 ";
			$exit_serial_number = $this->getDB ()->createCommand ( $sql )->queryAll ();
			if ($order_type_id == 'VARIANCE_MINUS' || $order_type_id == 'VARIANCE_ADD') {
				if (empty ( $exit_serial_number )) {
					echo ($serial_number . '错误：串号有误');
					return;
				}
			}else {
				echo ($order_goods_id . '订单商品号有误');
				return;
			}
		}
		$result = $this->deliver_inventory_virance_order_inventory ( $order_goods_id, $serial_number );
		/*
		 * $result是hashmap,判断是否调用romeo成功看返回状态
		 */
		if (empty($result)){
			echo "out_order_goods this->deliver_inventory_virance_order_inventory result empty ";
			return;
		}
		$exestatus = $result->get('status');
		if ($exestatus->stringValue == 'OK') {
			/*
			 * 调用成功，修改ecs_vorder_request_item表里面的adjustment位置
			 */
			$this->update_item_inventory($order_goods_id);
		}else{
			echo "order_goods_wrong $order_goods_id";
			echo "  ".$exestatus->stringValue."  ";
		}
	}
	private function update_item_inventory($order_goods_id){
		$round = 3;
		do {
			$sql = "update ecshop.ecs_vorder_request_item set adjustment = '1' where order_goods_id = '{$order_goods_id}' ";
			$this->getDB ()->createCommand ( $sql )->execute ();
			$sql = "select adjustment from ecshop.ecs_vorder_request_item where order_goods_id = '{$order_goods_id}' ";
			$result = $this->getDB ()->createCommand ( $sql )->queryAll ();
			if ($result[0]['adjustment'] == 1) {
				echo "update_item_inventory done";
				break;
			}
			$round = $round - 1;
		} while ( $round > 0 );
		if ($round <= 0) {
			echo "{$order_goods_id}已经出库，但是修改ecshop.ecs_vorder_request_item中adjustment错误";
		}
	}
	//
	private function get_order_goods($order_id, $vorder_id) {
		$sql = "select og.rec_id as order_goods_id,og.goods_number,oi.order_type_id,og.goods_id,og.style_id,evri.serial_number
				from ecshop.ecs_order_goods og
				inner join ecshop.ecs_order_info oi on oi.order_id = og.order_id
				inner join ecshop.ecs_vorder_request_item evri on evri.order_goods_id = og.rec_id
				where evri.vorder_request_id = '{$vorder_id}' and oi.order_id = '{$order_id}' and evri.adjustment = '0' and evri.is_delete = '0' 
				and evri.order_goods_id <> '0' ";
		$order_goods_id_array = $this->getDB ()->createCommand ( $sql )->queryAll ();
		echo "get_order_goods {$order_id}, {$vorder_id} order_goods_id_array=";
		print_r($order_goods_id_array);
		return $order_goods_id_array;
	}

	
	private function deliver_inventory_virance_order_inventory($orderGoodsId, $serialNumber) {
		require_once (ROOT_PATH . "admin/includes/lib_function_inventory_command.php");
		require_once (ROOT_PATH . "RomeoApi/lib_inventory.php");
		$sql = "select oi.order_id,oi.facility_id,og.status_id,pm.product_id,og.goods_number,og.goods_price,oi.postscript,oi.order_type_id
				from ecshop.ecs_order_goods og
				inner join ecshop.ecs_order_info oi on og.order_id = oi.order_id
				inner join romeo.product_mapping pm on og.goods_id = pm.ecs_goods_id and og.style_id = pm.ecs_style_id
				where og.rec_id = '{$orderGoodsId}'";
		$row2 = $this->getDB ()->createCommand ( $sql )->queryAll ();
		$row = $row2 [0];
		$productId = $row ['product_id'];
		$facilityId = $row ['facility_id'];
		$statusId = $row ['status_id'];
		$unitCost = $row ['goods_price'];
		$comment = $row ['postscript'];
		$order_type_id = $row ['order_type_id'];
		$orderId = $row ['order_id'];
		$quantityOnHandVar = $row ['goods_number'];
		
		$sql = " select physical_inventory_id
				from romeo.inventory_item_detail where order_goods_id = '{$orderGoodsId}'";
		$result0 = $this->getDB ()->createCommand ( $sql )->queryAll ();
		if (empty ( $result0 )) {
			$physicalInventoryId = createPhysicalInventory_lcji ( $comment );
		} else {
			$physicalInventoryId = $result0 [0] ['physical_inventory_id'];
		}
		if (empty ( $physicalInventoryId )) {
			echo ("创建physicalInventoryId不成功 ");
			return;
		}
		
		$sql = "select INVENTORY_ITEM_ACCT_TYPE_ID,INVENTORY_ITEM_TYPE_ID
				from romeo.inventory_item
				where product_id = '{$productId}' and facility_id = '{$facilityId}'
				";
		$row2 = $this->getDB ()->createCommand ( $sql )->queryAll ();
		if (empty ( $row2 )) {
			echo ("该商品不存在过, " . $sql);
			return;
		}
		$row = $row2 [0];
		$inventoryItemTypeName = $row ['INVENTORY_ITEM_TYPE_ID']; // SERIALIZED, NON-SERIALIZED
		$inventoryItemAcctTypeName = $row ['INVENTORY_ITEM_ACCT_TYPE_ID']; // B2C, C2C
		if ($inventoryItemTypeName == 'SERIALIZED') {
			$quantityOnHandVar = 1;
		}
		if ($order_type_id == 'VARIANCE_MINUS') {
			$quantityOnHandVar = - $quantityOnHandVar;
		}
		$availableToPromiseVar = $quantityOnHandVar;
		$result = createInventoryItemVarianceByProductId_lcji ( $productId, $inventoryItemAcctTypeName, $inventoryItemTypeName, $statusId, $serialNumber, $quantityOnHandVar, $availableToPromiseVar, $physicalInventoryId, $unitCost, $facilityId, $comment, $orderId, $orderGoodsId, 'cronjob' );
	
		return $result;
	}

	private function is_serial_number_type($goods_id, $style_id) {
		$sql = "select INVENTORY_ITEM_TYPE_ID from romeo.inventory_item ii
				inner join romeo.product_mapping pm on pm.product_id = ii.product_id
				where pm.ecs_goods_id = '{$goods_id}' and pm.ecs_style_id = '{$style_id}'";
		$result = $this->getDB ()->createCommand ( $sql )->queryAll ();
		if (empty ( $result )) {
			echo "根据goods，style" . $goods_id . "  " . $style_id . "检索不到序列号类型";
			return false;
		}
		$item_type_id = $result [0] ['INVENTORY_ITEM_TYPE_ID'];
		if ($item_type_id == 'SERIALIZED') {
			return true;
		} else {
			return false;
		}
	}
	
	private function vorder_flag_adjust($vorder_id){
		echo "vorder_flag_adjust VORDER ID ".$vorder_id." \r\n ";
		$sql = "select inventory_adjust from ecshop.ecs_vorder_request_info where vorder_request_id = '{$vorder_id}'";
		$result = $this->getDB()->createCommand($sql)->execute();
		$count = intval($result[0]['inventory_adjust']);
		$count++;
		if($count >= $this->inventory_adjust_index){
			$sql_out = "update ecshop.ecs_vorder_request_info set inventory_adjust = '{$count}',vorder_status = 'OVER' where vorder_request_id = '{$vorder_id}'";
		}else{
			$sql_out = "update ecshop.ecs_vorder_request_info set inventory_adjust = '{$count}' where vorder_request_id = '{$vorder_id}'";
		}
		echo "vorder_flag_adjust VORDER ID ".$vorder_id." sql_out: ".$sql_out;
		$round = 5;
		do {
			$line = $this->getDB()->createCommand($sql_out)->execute();
			if ($line == 1) {
				echo "vorder_flag_adjust VORDER ID ".$vorder_id." done;";
				break;
			}
			$round --;
		}while ($round > 0);
		if ($round <= 0){
			echo "{$vorder_id}已经进行了出库操作（并不保证所有商品均出库成功），但是修改ecshop.ecs_vorder_request_info中inventory_adjust错误";
		}
	}
	
	
	
	private function insert_order_info($vorder_request_id){

		$sql = "select 1 from ecshop.ecs_vorder_request_mapping where vorder_request_id = '{$vorder_request_id}'";
		$flag = $this->getDB()->createCommand($sql)->queryAll();
		if (!empty($flag)) {
			return 1;//表示已经有了映射，不用重新映射
		}
		
		$sql = "select step0_user_id,vorder_request_id,facility_id,party_id,comments,v_category from ecshop.ecs_vorder_request_info
			where vorder_request_id = '{$vorder_request_id}'";
		$info = $this->getDB()->createCommand ( $sql )->queryAll();
		
		$sql = "select item.rec_id,item.product_id,item.v_category,item.goods_status,item.goods_name,item.goods_type_id,
			item.goods_number,item.goods_price,item.goods_amount,item.reason,pm.ecs_goods_id,pm.ecs_style_id,item.serial_number
			from ecshop.ecs_vorder_request_item item
			left join romeo.product_mapping pm on pm.product_id = item.product_id
			where  item.vorder_request_id = '{$vorder_request_id}' and is_delete = '0'
		";
		$item = $this->getDB()->createCommand($sql)->queryAll();
		
		
		$flag = $this->order_success($vorder_request_id, $info[0], $item);
		if ($flag) {
			return 0;//表示映射成功
		}else{
			return -1;//表示映射失败
		}
	}
	
	
	private function order_success($vorder_request_id,$order_info,$item_info) {
		/* 
		 * 创建ecs_order_info条目，创建ecs_vorder_request_mapping条目。执行ecs_order_info里面新创建的条目
		 * */
		require_once (ROOT_PATH . "includes/lib_order.php");
		
		$admin_id = $order_info ["step0_user_id"];
		$facility_id = $order_info ["facility_id"];
		$party_id = $order_info ["party_id"];
		$comments = $order_info ["comments"];
		$cat = $order_info ['v_category'];
		
		
		$transaction=$this->getDB()->beginTransaction();
		try{
			
			$order_sn = get_order_sn () . "-v";
			$sql = "INSERT INTO ecshop.ecs_order_info
	                (order_sn, order_time, order_status, shipping_status , pay_status, user_id, postscript, 
	                order_type_id, party_id, facility_id)
	                VALUES('{$order_sn}', NOW(), 2, 0, 0, '{$admin_id}',
	                         '库存调整订单  {$comments}', 'VARIANCE_{$cat}', '{$party_id}', '{$facility_id}')";
			$line = $this->getDB()->createCommand ( $sql )->execute();
			if ($line != 1) {
				$transaction->rollBack();
				return false;
			}
			$order_id = $this->getDB()->getLastInsertID();
			
			$sql = "insert into ecshop.ecs_vorder_request_mapping (vorder_request_id,order_id) values('{$vorder_request_id}','{$order_id}')";
			$line = $this->getDB()->createCommand ( $sql )->execute();
			if ($line != 1) {
				$transaction->rollBack();
				return false;
			}
			
			$vorder_request_items = $item_info;
			foreach ( $vorder_request_items as $good ) {
				$rec_id = $good['rec_id'];
				$goods_id = trim ( $good ["ecs_goods_id"] );
				$style_id = trim ( $good ["ecs_style_id"] );
				$goods_name = $good ["goods_name"];
				$goods_count =intval($good ["goods_number"]);
				$goods_price = $good ["goods_price"];
				$goods_status = trim ( $good ["goods_status"] );
				$goods_reason = $good ["reason"];
					
				// 插入对应的记录到order_goods表
					
				$sql = "INSERT INTO ecshop.ecs_order_goods
						(order_id, goods_id, style_id, goods_name, goods_number, goods_price, status_id,action_note)
						VALUES('{$order_id}', '{$goods_id}', '{$style_id}', '{$goods_name}',
						'{$goods_count}', '{$goods_price}','{$goods_status}','{$goods_reason}')";
				
				$line = $this->getDB()->createCommand ( $sql )->execute();
				if ($line != 1) {
					$transaction->rollBack();
					return false;
				}
				$order_goods_id = $this->getDB()->getLastInsertID();
				//将该item记录对应的order_goods_id记录下来
				$sql = "update ecshop.ecs_vorder_request_item set order_goods_id = '{$order_goods_id}' where rec_id = '{$rec_id}'";
				$line = $this->getDB()->createCommand ( $sql )->execute();
				if ($line != 1) {
					$transaction->rollBack();
					return false;
				}
		
			}
			$transaction->commit();
			return true;
		}catch(Exception $e){
			$transaction->rollBack();
			return false;
		}
	}
}