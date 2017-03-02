<?php

// classes
abstract class ClsSalesOrderBasic{
	/**
	 *  @param $data_map 为传入的url参数
	 * */
	function __construct($data_map){
		$this->action_type_ = $data_map['action_type'] ? $data_map['action_type'] : 'query';
		$this->content_type_ = $data_map['content_type'];
		//判断传入参数中的动作类型和内容类型，如果动作类型不在子类的动作范围内则报错误返回
		if(!in_array($this->action_type_, $this->GetAllowedActionList())){
			$this->error_info_['err_no'] = 1;
			$this->error_info_['message'] = '当前内容不可执行该请求'.$this->action_type_;
			return;
		}
		//check prim
		$check_result = check_admin_priv_with_feedback('customer_service_manage_order', 'order_view');
		if(!$check_result['is_allow']){
			$this->error_info_['err_no'] = 1;
			$this->error_info_['message'] = $check_result['err_msg'];
			return;
		}

		//此句为将需要传入的各种id比如order_goods_id和order_id共同拼成一个数组$id_check_list
		//getIdCheckList为子类实现的方法返回需要传入的id的一个数组，在之后进行对其循环判断，并将id值赋到id_map_中
		//id_map_由于是成员变量，他可以被任何子类读取，即在子类getIdCheckList返回的数组中的id名若url中存有则可以通过id_map_访问
		$id_check_list = array_merge(array('order_id'), $this->GetIDCheckList($this->action_type_));
		foreach ($id_check_list as $id_name) {
			if(!array_key_exists($id_name, $data_map)){
				$this->error_info_['err_no'] = 1;
				$this->error_info_['message'] = '请传入'.$id_name;
				return;
			}else{
				$this->id_map_[$id_name] = $data_map[$id_name];
			}
		}

		//check data_map
		switch ($this->action_type_) {
			case 'query':
			break;
			case 'delete':
				$this->data_map_['changeBonus'] = $data_map['changeBonus'];
				break;
			case 'update':
				//update需GetAllAttrListForUpdate()中的一个或一个以上data
				$attr_list = $this->GetAllAttrListForUpdate();
			
				if(!empty($attr_list)){
					foreach ($attr_list as $attr_name) {
						if(isset($data_map[$attr_name])){
							$this->data_map_[$attr_name] = $data_map[$attr_name];
						}
					}
					if(empty($this->data_map_)){
						$this->error_info_ = array('err_no'=>1, 'message'=>"没有需要修改的属性!请至少修改".implode(',', $attr_list). "中的一项");
						return;
					}
				}
				
				break;
			case 'insert':
				//insert需要GetAllAttrListForInsert()中的所有data
				$attr_list = $this->GetAllAttrListForInsert();
				foreach ($attr_list as $attr_name) {
					# code...
					if(!isset($data_map[$attr_name])){
						$this->error_info_ = array('err_no'=>1, 'message'=> $attr_name."未赋值");
						return;
					}else{
						$this->data_map_[$attr_name] = $data_map[$attr_name];			
					}
				}
				break;
			default:
				$this->error_info_['err_no'] = 11111;
				$this->error_info_['message'] = '不可能进到这个分支';
				break;
		}
	}

	function DoAction(){
		$action_type = $this->action_type_;
		if($this->error_info_['err_no'])
			return;
		else{
			return $this->$action_type();
		}
	}
	//
	private function query(){
		//admin_priv('view_new_order_edit_page');

		//检查当前订单状态是否允许编辑相应订单属性
		global $db;
		$sql = "select order_status, shipping_status from ecshop.ecs_order_info where order_id = {$this->id_map_['order_id']}";
		$status_value = $db->getRow($sql);
		$this->DecideIfCanEdit($status_value['order_status'], $status_value['shipping_status']);
		$this->QueryData();
	}
	function DecideIfCanEdit($order_status, $shipping_status){
		global $db;
		$sql = "select order_id from ecshop.ecs_out_ship_order where order_id = '{$this->order_id_}' limit 1 ";
		$outShipOrder = $db->getOne($sql);
		//检查当前订单状态是否允许编辑相应订单属性
		require_once ('cls_sales_order_basic_status.php');
		require_once ('cls_sales_order_shipping_status.php');
		$basic_status_ins = ClsSalesOrderBasicStatus::GetStatusInstance($order_status, $outShipOrder);
		$shipping_status_ins = ClsSalesOrderShippingStatus::GetStatusInstance($shipping_status);
		$allowed_edit_actions = array_intersect($basic_status_ins->GetAllowedEditActionList(), $shipping_status_ins->GetAllowedEditActionList());
		$this->can_edit_ = in_array($this->content_type_, $allowed_edit_actions);
	}
	// static abstract protected function GetAllowedActionList();//OLD
	static protected function GetAllowedActionList(){return array();}//changed by Sinri for PHP warning: Strict Standards: Static function should not be abstract
	static protected function GetIDCheckList($action_type){
		return array('order_id');
	}
	protected abstract function QueryData();

	var $id_map_;
	var $data_map_;
	var $error_info_ = array('err_no'=>0, 'message'=>'');
	var $can_edit_;
}
abstract class ClsSalesOrderForModify extends ClsSalesOrderBasic{
	protected function SetData(){
		//check prim
		$check_result = check_admin_priv_with_feedback('customer_service_manage_order', 'order_view');//modify_new_order_edit_page
		if(!$check_result['is_allow']){
			$this->error_info_['err_no'] = 1;
			$this->error_info_['message'] = $check_result['err_msg'];
			return;
		}

		
		global $db;
		//对于修改taobao_order_sn 需要在系统中不存在	
		if($this->data_map_['content_type'] == 'platform_info'){			
			$sql_check = "select order_id from ecshop.ecs_order_info where  taobao_order_sn = '{$this->data_map_['taobao_order_sn']} ' AND order_id <> '{$this->data_map_['order_id']} ';";
			$check_result = $db->getCol($sql_check);
			if(count($check_result)!=0){
					$this->error_info_['err_no'] = 1;
					$this->error_info_['message'] = '该淘宝订单号已经存在了，ERP ORDER_ID:'.$check_result['order_id'];
					return ;
			}
		}
		
		//check状态
		//检查当前订单状态是否允许编辑相应订单属性
		$sql = "select order_status, shipping_status, pay_status ,p.is_cod
		from ecshop.ecs_order_info oi
		left join ecshop.ecs_payment p ON oi.pay_id = p.pay_id
		where order_id = {$this->id_map_['order_id']}";
		$status_value = $db->getRow($sql);
		
		$sql = "select order_id from ecshop.ecs_out_ship_order where order_id = {$this->id_map_['order_id']} limit 1 ";
		$outShipOrder = $db->getOne($sql);
		require_once ('cls_sales_order_basic_status.php');
		require_once ('cls_sales_order_shipping_status.php');
		require_once ('cls_sales_order_pay_status.php');
		
		$basic_status_ins = ClsSalesOrderBasicStatus::GetStatusInstance($status_value['order_status'], $outShipOrder);
		$shipping_status_ins = ClsSalesOrderShippingStatus::GetStatusInstance($status_value['shipping_status']);
		$pay_status_ins = ClsSalesOrderPayStatus::GetStatusInstance($status_value['pay_status']);
		$allowed_edit_actions = array_intersect($basic_status_ins->GetAllowedEditActionList(), $shipping_status_ins->GetAllowedEditActionList());
		// TODO 目前付款状态特殊处理
		if(!in_array($this->content_type_, $allowed_edit_actions) && !in_array($this->content_type_, $pay_status_ins->GetAllowedEditActionList()) && !$status_value['is_cod'] ){
			$this->error_info_['err_no'] = 1;
			$this->error_info_['message'] = '当前订单状态不允许进行该操作';
			return;
		}
		//
		if($this->PrepareForModify()){
			$this->GenerateModifySqlsForOrderInfo();
			$this->GenerateSqlsForOrderAction();
			if(isset($this->data_map_['add_time']) && $this->data_map_['add_time'] != ''){
				$this->data_map_for_insert_order_action_['action_note'] = array("【添加备注】,仓库可操作时间：".$this->data_map_['add_time']);
				$this->GenerateSqlsForOrderAction();
			}
//			pp($this->sql_result_);//return true;
			global $db;
			$db->start_transaction();        //开始事务
			if(!empty($this->sql_result_)){
				foreach ($this->sql_result_ as $sql) {
					# code...
					if(false == $db->query($sql)){
			            $db->rollback();
			            $this->error_info_ = array('err_no' => 2, 'message' => "数据修改失败，请检查数据".$sql);
			            return;
			        }
				}
			}
			if(!empty($this->sql_insert_with_attr)){
				foreach ($this->sql_insert_with_attr as $item) {
					# code...
					if(false == $db->query($item['insert_sql'])){
			            $db->rollback();
			            $this->error_info_ = array('err_no' => 2, 'message' => "数据修改失败，请检查数据".$item['insert_sql']);
			            return;
			        }
			        $insert_id = $db->insert_id();
			        
			        foreach ($item['attrs'] as $attr) {
			        	# code...
			        	$name = mysql_real_escape_string($attr['name']);
			        	$value = mysql_real_escape_string($attr['value']);
			        	$sql = "insert into {$item['attr_table_name']} ({$item['attr_table_source_id_name']}, name, value)VALUES ({$insert_id}, '{$name}', '{$value}');";
						if(false == $db->query($sql)){
				            $db->rollback();
				            $this->error_info_ = array('err_no' => 2, 'message' => "数据修改失败，请检查数据".$sql);
				            return;
				        }
			        }
				}
			}


			//注释掉该段代码，为废除做准备 modify by qyyao 2015-12-18
//		    require_once('../includes/lib_order_mixed_status.php');
//		    if(!empty($this->data_map_for_insert_order_mixed_status_)){
//		    	$success = update_order_mixed_status($this->order_id_, $this->data_map_for_insert_order_mixed_status_, 'worker', $this->data_map_for_insert_order_action_['action_note']);
//		    	if($success === false){
//			    	$db->rollback();
//				    $this->error_info_ = array('err_no' => 2, 'message' => "找不到订单初始化的历史记录，请联系ERP");
//			    	return;
//		    	}
//		    }else{
//		    	$success = update_order_mixed_status_note($this->order_id_, 'worker', $this->data_map_for_insert_order_action_['action_note']);
//		    }
//		    if($success <= 0){
//		    	$db->rollback();
//			    $this->error_info_ = array('err_no' => 2, 'message' => "历史记录添加失败，请联系ERP");
//		    	return;
//		    }
			$db->commit();					//	提交事务
			
			if($this->ModifyViaRomeo()){
				// 不在这里commit是因为2个事务，romeo那边会死锁

			}else{
	            
			}
		}
	}

	//函数职责：
	// 1. 生成order_info表需要修改的属性-值表，即$data_map_for_update_order_info_
	// 2. 生成order_action表需要添加的属性-值表$data_map_for_insert_order_action_
	// 3. 生成其余需执行的sql
	protected function PrepareForModify(){
		$order_info_attr_list = $this->GetOrderInfoAttrListForUpdate();
		foreach ($this->data_map_ as $attr_name => $attr_value) {
			# code...
			if(in_array($attr_name, $order_info_attr_list)){
				$this->data_map_for_update_order_info_[$attr_name] = $attr_value;
			}
		}
		return true;
	}

	//函数职责：
	// 1. 根据order_info表需要修改的属性-值表($data_map_for_update_order_info_)生成具体sql
	// 2. 将SQL添入$sql_result_
	private function GenerateModifySqlsForOrderInfo(){
		if(empty($this->data_map_for_update_order_info_))
			return;
		//
		$order_values = array();
		$attr_list = array();
		foreach ($this->data_map_for_update_order_info_ as $attr_name => $attr_value) {
			$attr_value = mysql_real_escape_string($attr_value);
			# code...
				$order_values[] = $attr_name . " = '".$attr_value."' ";
				$attr_list[] = $attr_name;
		}
		if(!empty($order_values)){
			$this->sql_result_[] = "update ecshop.ecs_order_info set ".implode(',', $order_values) . " where order_id = {$this->order_id_}";
		}
	}

	//函数职责：
	// 1. 根据order_action表需要添加的属性-值表($data_map_for_insert_order_action_)生成具体sql
	// 2. 将SQL添入$sql_result_
	private function GenerateSqlsForOrderAction(){
		$this->data_map_for_insert_order_action_['order_id'] = $this->order_id_;
        $this->data_map_for_insert_order_action_['action_time'] = date("Y-m-d H:i:s");

		$sql = "select order_status, shipping_status, pay_status, invoice_status, shortage_status
				from ecshop.ecs_order_info
				where order_id = {$this->order_id_}";
		global $db;
		$order_result = $db->getRow($sql);
		$status_name_list = array('order_status', 'shipping_status', 'pay_status', 'invoice_status', 'shortage_status');
		foreach ($status_name_list as $status_name) {
			$this->data_map_for_insert_order_action_[$status_name] = 
				isset($this->data_map_for_insert_order_action_[$status_name]) ? $this->data_map_for_insert_order_action_[$status_name] :
					(isset($this->data_map_for_update_order_info_[$status_name]) ? $this->data_map_for_update_order_info_[$status_name] : $order_result[$status_name]);
		}

		if(!isset($this->data_map_for_insert_order_action_['action_user']))
	        $this->data_map_for_insert_order_action_['action_user'] = $_SESSION['admin_name'];

        $this->data_map_for_insert_order_action_['action_note'] 
        	= empty($this->data_map_for_insert_order_action_['action_note']) ? '' : join(',', $this->data_map_for_insert_order_action_['action_note']);
	    foreach ($this->data_map_for_insert_order_action_ as $k => $v) {
	    	$v = mysql_real_escape_string($v);
	        $set[] = "$k = '$v'";
	    }
	    $set = join(", ", $set);
	    $this->sql_result_[] = "INSERT INTO ecshop.ecs_order_action SET $set ";
	}

	//函数职责
	// romeo
	protected function ModifyViaRomeo(){
		return true;
	}


	function update(){
//		admin_priv('modify_new_order_edit_page');
		$this->QueryDataForModify();
		if(!$this->error_info_['err_no']){
			$this->SetData();			
			if(!$this->error_info_['err_no']) 
				$this->QueryData();
		}
	}

	function QueryDataForModify(){
		return $this->QueryData();
	}
	
	/**
	 * 在修改订单的优惠券或删除商品时，订单的优惠总和bonus也要随之变化
	 * */
	function updateBonus($order_discount, $drift){
		global $db;
		$sql = " SELECT IFNULL(sum(oga.value),0) as goods_discount
            		FROM ecshop.ecs_order_goods eog 
     				LEFT JOIN ecshop.order_goods_attribute oga on eog.rec_id = oga.order_goods_id
     				WHERE  oga.name= 'DISCOUNT_FEE' AND eog.order_id = {$this->order_id_}";
           
           $goods_discount = $db->getOne($sql);
           $bonus = $order_discount + $drift + $goods_discount;
           $sql = "UPDATE ecshop.ecs_order_info set bonus = -$bonus WHERE order_id = {$this->order_id_}";
           $this->sql_result_[] = $sql;
           return $bonus;
	}

	var $sql_result_ = array();
	var $data_map_for_update_order_info_ = array();
	var $data_map_for_insert_order_action_ = array();
	var $data_map_for_insert_order_mixed_status_ = array();
}

interface ISalesOrderForDelete{
	function delete();
}
interface ISalesOrderForInsert{
	function insert();
	static function GetAllAttrListForInsert();
}
interface ISalesOrderForUpdate{
	function update();
	static function GetAllAttrListForUpdate();
	static function GetOrderInfoAttrListForUpdate();
}
?>