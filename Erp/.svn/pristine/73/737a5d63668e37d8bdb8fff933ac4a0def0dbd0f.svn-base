<?php
/*
* 可优化：
* 1. 缓存
*/
require_once ('cls_sales_order.php');

class ClsSalesOrderPayInfo extends ClsSalesOrderForModify implements ISalesOrderForUpdate{
	static protected function GetAllowedActionList(){
		return array('query', 'update');
	}
	function __construct($data_map ){
    	parent::__construct($data_map);
		$this->order_id_ = $data_map['order_id'] ? $data_map['order_id'] : 0;
	}

	function QueryData(){
		require_once('cls_sales_order_tools.php');
		global $db;
		$order_result = array();
		$sql = "SELECT order_status,shipping_status,currency, bonus_id, currency,
					pack_fee, shipping_fee, 
					bonus, misc_fee, additional_amount,
					order_amount,goods_amount,
					order_type_id,sum(goods_number * goods_price) as goods_total_price,
					IFNULL((SELECT eoa.attr_value FROM ecshop.order_attribute eoa 
					WHERE eoa.order_id = eoi.order_id AND eoa.attr_name = 'DISCOUNT_FEE' ),0) as order_discount
				FROM ecshop.ecs_order_info eoi
				LEFT JOIN ecshop.ecs_order_goods eog on eoi.order_id = eog.order_id
				WHERE eoi.order_id = {$this->order_id_}
				GROUP BY eog.order_id";
		$order_result = $db->getRow($sql);
		$this->currency_ = ClsSalesOrderTools::GetSymbolForCurrency($order_result['currency']);//'￥';
		$this->bonus_id_ = $order_result['bonus_id'];
		$this->goods_total_price_ = $order_result['goods_total_price'];
		$this->currency_ = "币种：".$order_result['currency'];
		$this->fee_list_ = null;
		$this->fee_list_[] = new ClsFee('amount', '订单商品金额', $order_result['goods_amount']);
		$this->fee_list_[] = new ClsFee('pack', '包装费用', $order_result['pack_fee']);
		$this->fee_list_[] = new ClsFee('shipping', '配送费用', $order_result['shipping_fee']);
		$this->fee_list_[] = new ClsFee('bonus', '抵用券抵用', $order_result['bonus']);
		$this->fee_list_[] = new ClsFee('order_discount','订单优惠券', $order_result['order_discount']);
		$order_type = $order_result['order_type_id'];
		if($order_type == 'RMA_EXCHANGE'){
			//换货订单
			$this->fee_list_[] = new ClsFee('misc','杂项费用', $order_result['misc_fee']);	
			if($order_result['additional_amount'] > 0){
				$this->fee_list_[] = new ClsFee('additional_amount', '换货订单用户还需支付金额', $order_result['additional_amount']);
			}else{
				$this->fee_list_[] = new ClsFee('additional_amount', '换货订单用户还需退给用户', $order_result['additional_amount']);
			}
		}else if($order_type == 'SHIP_ONLY'){
			//补寄订单
			$this->fee_list_[] = new ClsFee('info', '补寄订单无需支付');
		}else{
			$this->fee_list_[] = new ClsFee('order_amount', '应付金额', $order_result['order_amount']);
		}

		
		$this->DecideIfCanEdit($order_result['order_status'], $order_result['shipping_status']);
		
	}

	protected function PrepareForModify(){
		if($this->UpdatePayInfo()) {
			return true;
		}
        
        return false;
	}
	
	/**
	 * 更新支付金额等信息
	 */
	private function UpdatePayInfo() {
		global $db,$ecs,$_CFG;
		// 编辑相关费用，主要是红包和原有积分
		$order_id = $this->order_id_;
		$sql = "select pay_status,order_sn,order_type_id,misc_fee,shipping_fee,pack_fee,bonus,integral_money,goods_amount ,
				IFNULL((SELECT oa.attr_value FROM ecshop.order_attribute oa 
				WHERE oa.order_id = eoi.order_id AND oa.attr_name= 'DISCOUNT_FEE'),0) as order_discount
				from ecshop.ecs_order_info eoi
			    LEFT JOIN ecshop.order_attribute oa on oa.order_id = eoi.order_id
			    where eoi.order_id = ".$order_id." limit 1";
		$result = $db->getRow($sql);
		$origin_bonus = $result['bonus'];
		$bonus = abs(floatval($this->data_map_['bonus']));
		$origin_order_discount = $result['order_discount'];
		$order_discount =  abs(floatval($this->data_map_['order_discount']));
		$goods_amount = $result['goods_amount'];
		$integral_money = $result['integral_money'];
		$order_type_id = $result['order_type_id'];
		$misc_fee = $result['misc_fee'];
		$shipping_fee = $result['shipping_fee'];
		$pack_fee = $result['pack_fee'];
		$misc_fee = $result['misc_fee'];
		$pay_status = $result['pay_status'];
		//判断折扣是否改变，若改变则修改相应表并且改变bonus值
		if($origin_order_discount != $order_discount){
			$sql = "SELECT 1 FROM ecshop.order_attribute oa WHERE order_id = $order_id and oa.attr_name = 'DISCOUNT_FEE'";
			if($db->getOne($sql)){
				$sql = "update ecshop.order_attribute set attr_value = $order_discount where order_id = $order_id and attr_name = 'DISCOUNT_FEE' ";
			}else{
				$sql = "insert ecshop.order_attribute (order_id, attr_name, attr_value) values ($order_id,'DISCOUNT_FEE',$order_discount)";
			}
			
			$this->sql_result_[] = $sql;
			$sql = "SELECT attr_value from ecshop.order_attribute where order_id = $order_id and attr_name = 'DISCOUNT_FEE'";
			//修改订单总金额
			$bonus = $this->updateBonus($order_discount, 0);
		}
		$this->data_map_for_update_order_info_['bonus'] = -1 * $bonus;
        if ($order_type_id == 'RMA_EXCHANGE') {
            //-h 订单修改杂项费用
            $order_amount =  $shipping_fee + max($goods_amount + $pack_fee - $bonus - $integral_money, 0);
            $sql = "SELECT o.order_amount, o.misc_fee FROM {$ecs->table('order_info')} o
             INNER JOIN service s ON o.order_id = s.back_order_id WHERE s.change_order_id = '{$order_id}' LIMIT 1 ";
            $back_order = $db->getRow($sql);
            $additional_amount = $order_amount + $misc_fee + $back_order['order_amount'] - $back_order['misc_fee'];
            //更新misc_fee 和additional_fee，order_amount 
            $this->data_map_for_update_order_info_['misc_fee'] = $misc_fee;
            $this->data_map_for_update_order_info_['additional_amount'] = $additional_amount;
        } else {
        	$order_amount =  $shipping_fee + max($goods_amount + $pack_fee - $bonus - $integral_money, 0);
        }
        if ($order_amount < 0)
        $order_amount = 0;
        
        $this->data_map_for_update_order_info_['order_amount'] = $order_amount;
        $this->data_map_for_update_order_info_['pay_status'] = 0;
        require_once("../config.vars.php");

        // 付款状态
        if ($pay_status != '0') {
            $action_note = "修改红包费用，".abs($origin_bonus)."修改为".$bonus.",付款状态由". $_CFG['adminvars']['pay_status'][$pay_status] ."改为未付款" ;     
        } else {
            $action_note = "修改红包费用，".abs($origin_bonus)."修改为".$bonus;
        }
        $this->data_map_for_insert_order_action_['action_note'][] = $action_note;

        $this->GeneratePayLog();
        return true;
	}
	
	/**
	 * 使用红包 //其实并没有人用这个函数
	 */
	private function UseBonus() {
		throw new Exception("是时候封印这群妖怪了。铲平忌术部宣。(涉及到古老的欧币系统，已被停用。)", 1);
		/*
		$bonus_id = $this->data_map_['bonus_id'];
        require_once("../includes/lib_bonus.php");
	    $bonus_result = test_bonus_id($bonus_id, $this->order_id_);
	    if (!$bonus_result['result']) {
            $this->error_info_ = array('err_no' => 2, 'message' => $bonus_result['info']);
	        return false;
	    }

	    $giftTicket = $bonus_result['giftTicket'];
	    $userId = $bonus_result['userId'];

	    // 使用抵用券
	    $this->sql_result_[] = "UPDATE membership.ok_gift_ticket SET gt_state = 4, used_timestamp = UNIX_TIMESTAMP(), used_order_id = '{$this->order_id_}', used_user_id = '{$userId}' WHERE gt_code  = '{$bonus_id}'";

	    // 写入log
	    require_once(ROOT_PATH."includes/lib_common.php");
	    $iUseType = 4; #购买使用
	    $sUserIp  = real_ip();
	    $squoteId = $userId."|".date("Y-m-d H:i:s")."|购买使用".$bonus_id."|Ip".$sUserIp;
	    $this->sql_result_[] = "INSERT INTO membership.ok_gift_ticket_log (gtc_id, gt_id, user_id, gtl_utime, gtl_uip, gtl_type, gtl_fk_id, gtl_comment) VALUES ('{$giftTicket['gtc_id']}','{$giftTicket['gt_id']}','$userId', NOW(),'" .ip2long($sUseIp) . "','$iUseType','$squoteId','{$_SESSION['admin_name']} 在后台使用') ";

		global $db;        
        $sql = "select shipping_fee, goods_amount, pack_fee, integral_money from ecshop.ecs_order_info where order_id = {$this->order_id_}";
        $order_info = $db->getRow($sql);
        $bouns_value = abs($bonus_result['value']);
        //红包最多可以抵消的金额是商品金额和包装费
        if ($bouns_value > $order_info['goods_amount'] + $order_info['pack_fee']) {
            $bouns_value = $order_info['goods_amount'] + $order_info['pack_fee'];
        }
        $bouns_value = -1 * $bouns_value;

        //
        $this->data_map_for_update_order_info_['bonus_id'] = $bonus_id;
        $this->data_map_for_update_order_info_['bonus'] = $bouns_value;
        $this->data_map_for_update_order_info_['order_amount'] 
        	= $order_info['shipping_fee']+ max($order_info['goods_amount'] + $order_info['pack_fee'] + $bouns_value - $order_info['integral_money'], 0);

        //
        $this->data_map_for_insert_order_action_['action_note'][] = " 使用红包 $bonus_id 价值 {$bouns_value} " . $_REQUEST['bonus_id_note'];

        //
        $this->GeneratePayLog();

        return true;
        */
	}

	private function GeneratePayLog(){

		global $db;        
        $sql = "SELECT order_amount
        		FROM ecshop.ecs_pay_log
                WHERE order_id = {$this->order_id_}";
        $order_amount = $db->getOne($sql);
        
        if (!is_null($order_amount))
        {
            $sql = "SELECT log_id FROM ecshop.ecs_pay_log
            		WHERE order_id = '$order_id'
            			AND order_type = '" . PAY_ORDER . "'
            			AND is_paid = 0";
            $log_id = intval($GLOBALS['db']->getOne($sql));
            if ($log_id > 0)
            {
                /* 未付款，更新支付金额 */
                $this->sql_result_[]
                	 = "UPDATE ecshop.ecs_pay_log
                	 	SET order_amount = '$order_amount' 
                	 	WHERE log_id = '$log_id' LIMIT 1";
            }
            else
            {
                /* 已付款，生成新的pay_log */
                $this->sql_result_[]
                	 = "INSERT INTO ecshop.ecs_pay_log
                 		(order_id, order_amount, order_type, is_paid)
                		VALUES('$order_id', '$order_amount', '" . PAY_ORDER . "', 0)";
            }
        }
	}

	static function GetAllAttrListForUpdate(){
		return ClsSalesOrderPayInfo::GetOrderInfoAttrListForUpdate();
	}

	static function GetOrderInfoAttrListForUpdate(){
		return array('bonus','order_discount');
	}

	//
	var $order_id_;
	var $bonus_id_;
	var $currency_;
	var $fee_list_ = array();
}

class ClsFee{
	function __construct($id, $name, $value=0){
		$this->id_ = $id;
		$this->name_ = $name;
		$this->value_ = $value;
	}
	var $id_;
	var $name_;
	var $value_;
}
?>