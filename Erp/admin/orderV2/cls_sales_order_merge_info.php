<?php
require_once ('cls_sales_order.php');
require_once(ROOT_PATH . 'admin/includes/lib_filelock.php');
class ClsSalesOrderMergeInfo extends ClsSalesOrderForModify implements ISalesOrderForUpdate{

  //sinri 
  public $canForceDivorce;

	static protected function GetAllowedActionList(){
		return array('query', 'update');
	}
	function __construct($data_map){
    	parent::__construct($data_map);
		$this->order_id_ = $data_map['order_id'] ? $data_map['order_id'] : 0;
		$this->merge_type_ = $data_map['merge_type'];
    //sinri
    $this->canForceDivorce = false;
	}
	function QueryData(){
		global $db;
		//合并订单
		$sql = "SELECT eoi.order_id, eoi.order_sn
				from romeo.order_shipment os2
				left join romeo.order_shipment os1 on os1.shipment_id = os2.shipment_id
				left join ecshop.ecs_order_info eoi on  eoi.order_id = cast(os2.order_id as unsigned)
				where os1.order_id = '{$this->order_id_}' and os2.order_id <> '{$this->order_id_}'";
		$this->merged_order_ids_ = $db->getAll($sql);

		$sql = "SELECT shipping_status,order_status from ecshop.ecs_order_info where order_id = {$this->order_id_}";
		$order_info = $db->getRow($sql);
		$this->SetClsData($order_info);
	}
	function SetClsData($order_info){
    $this->DecideIfCanEdit($order_info['order_status'], $order_info['shipping_status']);
	}

	protected function PrepareForModify(){
		$func = 'PrepareForModify_'.$this->merge_type_;
		return $this->$func();
	}
	protected function PrepareForModify_merge(){
		global $db;
		//
    	if (empty($this->data_map_['merge_shipment_order_sn'])) {
            $this->error_info_ = array('err_no' => 3, 'message' => '请输入订单号');
            return false;
    	}else{
    		$to_merge_order_sn = $this->data_map_['merge_shipment_order_sn'];
    	}

		//被合并订单
        if ($this->data_map_['merge_shipment_external_type'] == 'taobao') {
        	$merge_sql = "
              select oi.order_id, oi.order_sn, oi.shipping_status, oi.order_status, oi.facility_id, oi.shipping_id 
                   , oi.consignee, oi.mobile, oi.tel, oi.province, oi.city, oi.district, oi.address, ifnull(a.attr_value, '') as attr_value, md.type
               from ecshop.ecs_order_info oi 
                    left join ecshop.order_attribute a on oi.order_id = a.order_id and a.attr_name = 'TAOBAO_USER_ID'
                    left join ecshop.distributor d ON oi.distributor_id = d.distributor_id
                    left join ecshop.main_distributor md ON d.main_distributor_id = md.main_distributor_id
              where oi.taobao_order_sn = '%s' and ".party_sql('oi.party_id');
            $merge_shipment_order=$db->getRow(sprintf($merge_sql, trim($to_merge_order_sn)));   
            if(empty($merge_shipment_order)){
	            $this->error_info_ = array('err_no' => 3, 'message' => "合并的淘宝订单号：".trim($to_merge_order_sn)."不存在，或不是同一个业务订单。");
    	        return false;
            }
        } else {
        	$merge_sql = "
              select oi.order_id, oi.order_sn, oi.shipping_status, oi.order_status, oi.facility_id, oi.shipping_id 
                   , oi.consignee, oi.mobile, oi.tel, oi.province, oi.city, oi.district,oi.address, ifnull(a.attr_value, '') as attr_value, md.type
               from ecshop.ecs_order_info oi 
                    left join ecshop.order_attribute a on oi.order_id = a.order_id and a.attr_name = 'TAOBAO_USER_ID'
                    left join ecshop.distributor d ON oi.distributor_id = d.distributor_id
                    left join ecshop.main_distributor md ON d.main_distributor_id = md.main_distributor_id
              where oi.order_sn = '%s' and ".party_sql('oi.party_id');
            $merge_shipment_order=$db->getRow(sprintf($merge_sql, trim($to_merge_order_sn)));    
            
            if(empty($merge_shipment_order)){
	            $this->error_info_ = array('err_no' => 3, 'message' => "合并的ERP订单号：".trim($to_merge_order_sn)."不存在，或不是同一个业务订单。");
    	        return false;
            }
        }
        $this->to_merge_order_id_ = $merge_shipment_order['order_id'];


        //本订单
		$original_sql = "
          select oi.order_id, oi.order_sn, oi.shipping_status, oi.order_status, oi.facility_id, oi.shipping_id 
               , oi.consignee, oi.mobile, oi.tel, oi.province, oi.city, oi.district, oi.address, ifnull(a.attr_value, '') as attr_value, md.type
           from ecshop.ecs_order_info oi 
                left join ecshop.order_attribute a on oi.order_id = a.order_id and a.attr_name = 'TAOBAO_USER_ID'
                left join ecshop.distributor d ON oi.distributor_id = d.distributor_id
                left join ecshop.main_distributor md ON d.main_distributor_id = md.main_distributor_id
          where oi.order_id = %d and ".party_sql('oi.party_id'); 
        $original_shipment_order = $db->getRow(sprintf($original_sql, intval($this->order_id_)));
        //

        if ($original_shipment_order['order_status'] != $merge_shipment_order['order_status']) {
            $this->error_info_ = array('err_no' => 3, 'message' => "订单{$merge_shipment_order['order_sn']}与当前订单状态不一致不可合并");
            return false;
        }
        if ($original_shipment_order['shipping_status'] != $merge_shipment_order['shipping_status']) {
            $this->error_info_ = array('err_no' => 3, 'message' => "订单{$merge_shipment_order['order_sn']}与当前订单发货状态不一致不可合并");
            return false;
        }

        // 直分销订单不能合并
        if($merge_shipment_order['type'] != $original_shipment_order['type']){
            $this->error_info_ = array('err_no' => 3, 'message' => "要合并发货的订单直分销类型不一致");
   	        return false;
        }
        // 检查合并发货的订单是否在一个仓库
        if ($merge_shipment_order['facility_id']=='' || $merge_shipment_order['facility_id']!=$original_shipment_order['facility_id']) {
            $this->error_info_ = array('err_no' => 3, 'message' => "要合并发货的订单不在一个仓库");
   	        return false;
        }
        // 检查合并发货的订单是否是同一个快递方式
        if ($merge_shipment_order['shipping_id'] != $original_shipment_order['shipping_id']) {
            $this->error_info_ = array('err_no' => 3, 'message' => "要合并发货的订单配送方式不一致");
   	        return false;
        }
        if($merge_shipment_order['consignee'] != $original_shipment_order['consignee']){
            $this->error_info_ = array('err_no' => 3, 'message' => "要合并发货的订单收件人不一致");
   	        return false;
        }
        if($merge_shipment_order['mobile'] != $original_shipment_order['mobile']){
            $this->error_info_ = array('err_no' => 3, 'message' => "要合并发货的订单收件人手机号不一致");
   	        return false;
        }
        if($merge_shipment_order['tel'] != $original_shipment_order['tel']){
            $this->error_info_ = array('err_no' => 3, 'message' => "要合并发货的订单收件人电话号不一致");
   	        return false;
        }
        if($merge_shipment_order['province'] != $original_shipment_order['province'] || $merge_shipment_order['city'] != $original_shipment_order['city'] 
        	|| $merge_shipment_order['district'] != $original_shipment_order['district'] || $merge_shipment_order['address'] != $original_shipment_order['address']){
            $this->error_info_ = array('err_no' => 3, 'message' => "要合并发货的订单详细地址不一致");
   	        return false;
        }

        //order_action
        global $db, $ecs;
        $sql = "SELECT order_id, order_status, pay_status, shipping_status, invoice_status "
              ." FROM {$ecs->table('order_info')} WHERE order_id in ({$this->to_merge_order_id_}, {$this->order_id_})";
        $action_data_list = $db->getAll($sql);
        foreach ($action_data_list as &$value) {
        	# code...
        	$value['action_user'] = $_SESSION['admin_name'];
        	$value['action_time'] = date("Y-m-d H:i:s");
        	$value['action_note'] = "详情订单页合并订单："."{$merge_shipment_order['order_sn']}".","."{$original_shipment_order['order_sn']}";
  		    $set = array();
  		    foreach ($value as $k => $v) {
  		        $set[] = "$k = '$v'";
  		    }
  		    $set = join(", ", $set);
  		    $this->sql_result_[] = "INSERT INTO ecshop.ecs_order_action SET $set ";
        }
        return true;
	}
	protected function PrepareForModify_split(){
	    // 拆分订单增加操作日志 ljzhou 2012.12.19
	    require_once ('../function.php');
		$orderIds = get_merge_order_ids($this->order_id_);
		$order_ids = implode(',',$orderIds);
		$orderSns = get_order_sns($order_ids);
		$order_sns = implode(',',$orderSns);
		global $db, $ecs;
        $sql = "SELECT order_id, order_status, pay_status, shipping_status, invoice_status "
              ." FROM {$ecs->table('order_info')} WHERE order_id in ({$order_ids})";
        $action_data_list = $db->getAll($sql);
        foreach ($action_data_list as &$value) {
        	# code...
        	$value['action_user'] = $_SESSION['admin_name'];
        	$value['action_time'] = date("Y-m-d H:i:s");
        	$value['action_note'] = "详情订单页拆分订单：" . "{$order_sns}";
		      $set = array();
  		    foreach ($value as $k => $v) {
  		        $set[] = "$k = '$v'";
  		    }
  		    $set = join(", ", $set);
  		    $this->sql_result_[] = "INSERT INTO ecshop.ecs_order_action SET $set ";
        }
		return true;
	}
	
	protected function ModifyViaRomeo(){
		try {
			$func = 'MergeOrdersViaRomeo_'.$this->merge_type_;
			return $this->$func();
		}
        catch (Exception $e) {
            $this->error_info_ = array('err_no' => 3, 'message' => "合并/拆分订单操作失败".$e->getMessage());
   	        return false;
        }
	}

	private function MergeOrdersViaRomeo_merge(){
        //判断合并的订单是否在打印批拣单
		$lock_file_from = get_file_lock_path($this->order_id_, 'pick_merge');
		$lock_file_point_from = fopen($lock_file_from, "w+");
		$would_block = true;
		if(!flock($lock_file_point_from, LOCK_EX|LOCK_NB, $would_block_ref)){
			fclose($lock_file_point_from);
			unlink($lock_file_from);
			$this->error_info_ = array('err_no' => 3, 'message' => "订单".$this->order_id_."正在参与批拣或与他单合并操作，合并失败".$e->getMessage());
   	        $would_block = false;
   	        return false;
		}
		if($would_block){
			$lock_file_to = get_file_lock_path($this->to_merge_order_id_, 'pick_merge');
			$lock_file_point_to = fopen($lock_file_to, "w+");
			if(!flock($lock_file_point_to, LOCK_EX|LOCK_NB, $would_block_ref)){
				fclose($lock_file_point_from);
				unlink($lock_file_from);
				fclose($lock_file_point_to);
				unlink($lock_file_to);
				$this->error_info_ = array('err_no' => 3, 'message' => "订单".$this->to_merge_order_id_."正在参与批拣或与他单合并操作，合并失败".$e->getMessage());
	   	        $would_block = false;
	   	        return false;
			}
		}
		if($would_block){
			//合并订单
//			sleep(3);
			try{
				$handle=soap_get_client('ShipmentService');
				$handle->ordersMergeToShipment(array('orderIdList'=>array($this->order_id_, $this->to_merge_order_id_), 'username'=>$_SESSION['admin_name']));
			}catch(Exception $e){
				$this->error_info_ = array('err_no' => 3, 'message' => "合并/拆分订单操作失败".$e->getMessage());
			}
			flock($lock_file_point_from, LOCK_UN);
		  	fclose($lock_file_point_from);
		  	unlink($lock_file_from);
		  	if(file_exists($lock_file_from)){
		  		QLog::log("merge_pick lock for order_id = ".$this->order_id_." failed to release ");
		  	}
			flock($lock_file_point_to, LOCK_UN);
		  	fclose($lock_file_point_to);
		  	unlink($lock_file_to);
		  	if(file_exists($lock_file_to)){
		  		QLog::log("merge_pick lock for order_id = ".$this->to_merge_order_id_." failed to release ");
		  	}
			return true;
		} 
	}
	private function MergeOrdersViaRomeo_split(){
		//拆分订单
		$handle=soap_get_client('ShipmentService');
		$handle->splitShipmentByOrderId(array('orderId'=>$this->order_id_));
		$error_info['message'] = "已经拆分合并发货";
		return true;
	}

	static protected function GetIDCheckList($action_type){
		switch ($action_type) {
			case 'query':
				return array('order_id');
			case 'update':
				return array('order_id', 'merge_type');
			default:
				return array();
		}
	}

	static function GetAllAttrListForUpdate(){
		return array('order_id', 'merge_shipment_order_sn', 'merge_shipment_external_type');
	}

	static function GetOrderInfoAttrListForUpdate(){
		return array();
	}
}

?>