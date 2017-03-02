<?php
/*
* 可优化：
* 1. 缓存
*/
require_once ('cls_sales_order.php');
require_once('../../includes/helper/array.php');
require_once('cls_sales_order_tools.php');

class ClsOrderGoodsList extends ClsSalesOrderBasic{
	static protected function GetAllowedActionList(){
		return array('query');
	}

	function __construct($data_map){
    	parent::__construct($data_map);
		$this->order_id_ = $data_map['order_id'] ? $data_map['order_id'] : 0;
	}

	function QueryData(){
		global $db;
		$sql = "select facility_id, party_id, order_type_id, pay_id,distributor_id,
					shipping_status, order_status
				from ecshop.ecs_order_info where order_id = {$this->order_id_}";
		$this->SetClsDataFromOrderInfo($db->getRow($sql));
	}
	function SetClsDataFromOrderInfo($order_result){
		$this->DecideIfCanEdit($order_result['order_status'], $order_result['shipping_status']);
		$this->party_id_ = $order_result['party_id'];
		$this->order_type_id_ = $order_result['order_type_id'];
		$this->pay_id_ = $order_result['pay_id'];
		$this->facility_id_ = $order_result['facility_id'];
		$this->fenxiao_type_ = "hehe"; 
		global $db;
		
		$fenxiao_type_sql = "SELECT attr_value FROM ecshop.order_attribute WHERE order_id = {$this->order_id_} AND attr_name = 'FENXIAO_TYPE'  limit 1 "; 
	    $fenxiao_type = $db->getRow($fenxiao_type_sql);
		if(!empty($fenxiao_type)){
		  	$this->fenxiao_type_ = $fenxiao_type['attr_value']; 
		}

		// 赠品活动提醒
		require_once(ROOT_PATH.'admin/function.php');
		$this->gift_reminds_ = get_order_gift_reminds($order_result['party_id'],$order_result['distributor_id']);
		// 商品清单
		$sql = "SELECT eog.rec_id, eog.goods_id, eog.style_id, goods_name, es.color, goods_price, eog.goods_number, eog.goods_number * goods_price AS total_price, 
				oird.STATUS as reserve_status, 
				IFNULL((SELECT oga.value from ecshop.order_goods_attribute oga 
				WHERE eog.rec_id = oga.order_goods_id AND oga.name='DISCOUNT_FEE' limit 1),0)  as goods_discount,
				s.available_to_reserved as AVAILABLE_TO_PROMISE,
				ifnull(sum(ii.QUANTITY_ON_HAND_TOTAL),0) as QUANTITY_ON_HAND,egir.reserve_number,
			    ifnull((select group_concat(distinct ii2.serial_number) from romeo.inventory_item ii2
				inner join romeo.inventory_item_detail iid2 ON ii2.inventory_item_id = iid2.inventory_item_id
				where iid2.order_goods_id = convert(eog.rec_id using utf8) group by iid2.order_goods_id),'') as serial_numbers
				from ecshop.ecs_order_goods eog
				LEFT JOIN ecshop.ecs_style es on eog.style_id = es.style_id
				LEFT JOIN romeo.order_inv_reserved_detail oird on convert(eog.rec_id using utf8) = oird.order_item_id
				LEFT JOIN romeo.product_mapping pm on eog.goods_id = pm.ECS_GOODS_ID and eog.style_id = pm.ECS_STYLE_ID
				LEFT JOIN romeo.inventory_item ii on pm.product_id = ii.product_id and ii.facility_id = '{$order_result['facility_id']}'
				and ii.STATUS_ID = 'INV_STTS_AVAILABLE'
				LEFT JOIN romeo.inventory_summary s ON ii.product_id = s.product_id and ii.facility_id = s.facility_id
				and ii.status_id = s.status_id 
				LEFT JOIN ecshop.ecs_goods_inventory_reserved egir ON egir.goods_id = eog.goods_id AND egir.style_id = IFNULL(eog.style_id,0) 
				AND egir.facility_id = '{$order_result['facility_id']}'  AND  egir.status = 'OK' AND ii.STATUS_ID = 'INV_STTS_AVAILABLE'
				where eog.order_id = {$this->order_id_} 
				group by eog.rec_id";
		$ref_list = $data1_list=array();
		$db->getAllRefBy($sql, array('rec_id'), $ref_list, $data1_list);
		$this->total_price_ = 0;
		if(!empty($ref_list)){
			$order_goods_ids = implode(',', $ref_list['rec_id']);
			foreach ($data1_list['rec_id'] as $key => $data) {
				# code...
				$data_map = array('order_id'=>$this->order_id_, 'order_goods_id'=>$key);
				$new_order_goods = new ClsOrderGoodsInfo($data_map);
				$new_order_goods->SetClsDataFromOrderGoodsInfo($order_result, $data[0]);
				$this->goods_list_[$key] = $new_order_goods;
				$this->total_price_ += $new_order_goods->total_price_;
			}
		}

	}

	//
	var $order_id_;
	var $goods_list_ = array();
	var $total_price_ = 0;
}

class ClsOrderGoodsInfo extends ClsSalesOrderForModify implements ISalesOrderForDelete, ISalesOrderForInsert{
	static protected function GetAllowedActionList(){
		return array('update', 'insert', 'delete');
	}
	function __construct($data_map){
    	parent::__construct($data_map);
		$this->order_id_ = $data_map['order_id'] ? $data_map['order_id'] : 0;
		$this->rec_id_ = $data_map['order_goods_id'] ? $data_map['order_goods_id'] : 0;
		$this->action_type_ = $data_map['action_type'] ? $data_map['action_type'] : 'query';
	}
	function QueryData(){
		global $db;
        $order_sql = "select party_id, facility_id, order_sn, order_type_id, order_status, pay_status, shipping_status, invoice_status, pay_id,
        			goods_amount, order_amount, additional_amount,sum(goods_price * goods_number) as goods_price_total,
        			shipping_fee, bonus
	        		from ecshop.ecs_order_info oi
	        		left join ecshop.ecs_order_goods og ON oi.order_id = og.order_id
	        		where oi.order_id = {$this->order_id_} group by oi.order_id";
	    $order_info = $db->getRow($order_sql);
	    if(in_array($this->action_type_, array('query', 'update', 'delete'))){
		    $goods_sql = "select eog.goods_id, eog.style_id, goods_name, goods_price, eog.goods_number, goods_price * eog.goods_number as total_price, es.color,
					oird.STATUS as reserve_status, 
					IFNULL((SELECT oga.value FROM order_goods_attribute oga 
					WHERE eog.rec_id = oga.order_goods_id AND oga.name = 'DISCOUNT_FEE' ),0) as goods_discount,
					ifnull(s.available_to_reserved,0) as AVAILABLE_TO_PROMISE,
					ifnull(sum(ii.QUANTITY_ON_HAND_TOTAL),0) as QUANTITY_ON_HAND,egir.reserve_number
					from ecshop.ecs_order_goods eog
					
					left join ecshop.ecs_style es on eog.style_id = es.style_id
					left join romeo.order_inv_reserved_detail oird on oird.order_item_id = convert(eog.rec_id using utf8)
					left join romeo.product_mapping pm on pm.ecs_goods_id = eog.goods_id and pm.ecs_style_id = eog.style_id
					left join romeo.inventory_item ii on ii.PRODUCT_ID = pm.product_id and ii.STATUS_ID = 'INV_STTS_AVAILABLE'
					and ii.facility_id = '{$order_info['facility_id']}' and ii.QUANTITY_ON_HAND_TOTAL > 0
					LEFT JOIN romeo.inventory_summary s ON ii.product_id = s.product_id and ii.facility_id = s.facility_id and ii.status_id = s.status_id 
					LEFT JOIN ecshop.ecs_goods_inventory_reserved egir ON egir.goods_id = eog.goods_id AND egir.style_id = IFNULL(eog.style_id,0) 
					AND egir.facility_id = {$order_info['facility_id']} 
					AND  egir.status = 'OK' AND ii.STATUS_ID = 'INV_STTS_AVAILABLE'
					where eog.order_id = {$this->order_id_} and eog.rec_id = '{$this->rec_id_}'
					group by ii.product_id";
			$order_goods = $db->getRow($goods_sql);
		    if(!($order_goods)){
	        	$this->error_info_['err_no'] = 2;
	        	$this->error_info_['message'] .= '订单商品['.$this->rec_id_.']不在订单['.$this->order_id_.']内!';
				return false;
		    }

		    $goods_sql_one = "SELECT goods_id ,goods_volume,goods_weight,spec from ecshop.ecs_goods WHERE goods_id = {$order_goods['goods_id']}";
		    $goods_sql_result = $db->getRow($goods_sql_one);
		    $this->goods_weight = $goods_sql_result['goods_weight'];
		    $this->goods_volume = $goods_sql_result['goods_volume'];
		    $this->spec = $goods_sql_result['spec']; 

	    }
		$this->SetClsDataFromOrderGoodsInfo($order_info, $order_goods);
		
		// 
		return true;
	}
	
	// 考虑到套餐的添加情况，所以展示的逻辑就整个商品表单全部刷新好了
	function QueryDataForDisplay(){
		$this->goods_list_ = new ClsOrderGoodsList(array('order_id'=>$this->order_id_, 'action_type'=>'query'));
		$this->goods_list_->QueryData();

		return true;
	}
	
	function SetClsDataFromOrderGoodsInfo($order_info, $order_goods_result){
		global $db;
		//order_info
		$this->order_info_ = $order_info;
		//order goods info & goods info
		$this->goods_id_ = $order_goods_result['goods_id'];
		// 如果是套餐，则加上套餐前缀
		$rec_id = $order_goods_result['rec_id'];
		$sql = "select gp.name,gp.code from ecshop.order_goods_attribute ga
		       inner join ecshop.distribution_group_goods gp ON gp.code = ga.value 
		       where ga.order_goods_id = '{$rec_id}' and ga.name = 'OUTER_IID' limit 1";
		$tc_info = $db->getRow($sql);
		$tc_before = '';
		if(!empty($tc_info)) {
			$tc_before = '['.$tc_info['name'].']['.$tc_info['code'].']';
		}
		$this->goods_name_ = $tc_before.$order_goods_result['goods_name'];
		$this->style_id_ = $order_goods_result['style_id'];
		$this->style_name_ = $order_goods_result['color'];
		if(!empty($order_goods_result['serial_numbers'])) {
			$this->serial_numbers_ = explode(',',$order_goods_result['serial_numbers']);
		}
		
		$sql = "select shipping_invoice 
				from romeo.order_shipping_invoice 
				where order_id = {$this->order_id_} and shipping_invoice <> ''";
		$this->shipping_invoices_ = $db->getCol($sql);
		$this->goods_discount_ = $order_goods_result['goods_discount'];
		$this->p_goods_price_ = $this->goods_price_ = $order_goods_result['goods_price'];
		$this->p_goods_number_ = $this->goods_number_ = $order_goods_result['goods_number'];
		$this->total_price_ = $this->goods_price_ * $this->goods_number_;
		//预定状态
		switch ($order_goods_result['reserve_status']) {
			case null:
				$this->reserve_status_ = '未预定';
				break;
			case 'N':
				$this->reserve_status_ = '预定失败';
				break;
			case 'Y':
				$this->reserve_status_ = '预定成功';
				break;
			case 'F':
				$this->reserve_status_ = '预定成功(已发货)';
				break;
			default:
				$this->reserve_status_ = '预定异常('.$order_goods_result['reserve_status'].')';
				break;
		}
		$this->atp_in_facility_ = $order_goods_result['AVAILABLE_TO_PROMISE'] ? ($order_goods_result['AVAILABLE_TO_PROMISE']-$order_goods_result['reserve_number']) : 0;
		$this->qoh_in_facility_ = $order_goods_result['QUANTITY_ON_HAND'] ? ($order_goods_result['QUANTITY_ON_HAND']-$order_goods_result['reserve_number']) : 0;
	}
	//for modify
	function insert(){
		if($this->QueryData()){
			$this->SetData();
			if(!$this->error_info_['err_no']){
				global $db;
				$sql = "select max(rec_id) from ecshop.ecs_order_goods where order_id = {$this->order_id_}";
				$this->rec_id_ = $db->getOne($sql);
				$this->action_type_ = 'query';
				$this->QueryDataForDisplay();
			}
		}
	}
	function delete(){
		if($this->QueryData()){
			$this->SetData();
			if(!$this->error_info_['err_no']){
				$this->ResetData();
			}
		}
	}
	static protected function GetIDCheckList($action_type){
		switch ($action_type) {
    		case 'query':
    		case 'insert':
				return array('order_id');
    			break;
    		case 'update':
    		case 'delete':
				return array('order_id', 'order_goods_id');
    			break;
    		default:
    			break;
    	}
	}
	protected function PrepareForModify(){
        // order goods
        $func_name = ClsOrderGoodsInfo::$s_func_map_for_generate_sqls_for_order_goods[$this->action_type_];
        if(!$this->$func_name())
        	return false;

		//order
	    global $db;
	    $sql = "select goods_price * goods_number
	    		from ecshop.ecs_order_goods
	    		where rec_id = {$this->rec_id_}";
	    $old_this_goods_total_price = $db->getOne($sql);
	    		//先将旧的折扣赋给成员变量以便后面比较
		$sql = "SELECT value from ecshop.order_goods_attribute where order_goods_id = {$this->rec_id_} and name = 'DISCOUNT_FEE'";
	    $old_this_goods_discount = $db->getOne($sql);
	    $old_this_goods_total_price = $old_this_goods_total_price ? $old_this_goods_total_price : 0;
	    $new_this_goods_total_price = $this->total_price_;
        $goods_price_diff = $new_this_goods_total_price - $old_this_goods_total_price ;
        $goods_discont_diff = $this->goods_discount_ - $old_this_goods_discount;
        // 因为本质是求得差价，所以如果是套餐的话，直接赋值为新加的套餐价格总和
        if(isset($this->group_total_price_)) {
        	$goods_price_diff = $this->group_total_price_;
        }
        
        if (abs($goods_price_diff) >= 0.000001 || $goods_discont_diff !=0 ) {
	        // 如果订单金额发生了变化且已付款待配货
	        // 修改：
	        // 1. 订单的goods_amount、order_amount、additional_amount
    	    // 2. 付款状态
           	$this->data_map_for_update_order_info_['pay_status'] = 0;
           	// 用实时算出的价格goods_price_total，而不用goods_amount字段，以免goods_amount本身就是错的
           	$this->data_map_for_update_order_info_['goods_amount'] = max(0,$this->order_info_['goods_price_total'] + $goods_price_diff);
            $this->data_map_for_update_order_info_['order_amount'] = $this->order_info_['shipping_fee'] + 
                    max(0, $this->data_map_for_update_order_info_['goods_amount'] + $this->order_info_['bonus'] + $this->order_info_['pack_fee']);  // 订单总金额
           	$this->data_map_for_update_order_info_['additional_amount'] = max(0,$this->order_info_['additional_amount'] + $goods_price_diff);
	        $sql = "select pay_code from ecshop.ecs_payment where pay_id = {$this->order_info_['pay_id']}";
            if ($db->getOne($sql) != 'cod') {
                $mail_content = "订单{$this->order_info_['order_sn']}商品被修改，订单总金额发生改变，请重新确认并操作付款状态";
                send_mail("caiwu", "caiwu@leqee.com", "订单{$this->order_info_['order_sn']}金额发生改变", $mail_content);
            }
            global $_CFG;
			$this->data_map_for_insert_order_action_['action_note'][] = mysql_real_escape_string ( "订单付款状态由" . $_CFG ['adminvars'] ['pay_status'] [$this->order_info_ ['pay_status']] . "改为未付款" );
        }else{
			$this->data_map_for_insert_order_action_['action_note'][] = "订单金额未变，不需要修改付款状态(old-".$old_this_goods_total_price." new-".$new_this_goods_total_price.")";
        }
		return true;
	}

	private function GenerateSqlsForUpdateOrderGoods(){

        if(!isset($this->data_map_['goods_price'])){
        	$this->data_map_['goods_price'] = $this->goods_price_;
        }
        if(!isset($this->data_map_['goods_number'])){
        	$this->data_map_['goods_number'] = $this->goods_number_;
        }
        if(!isset($this->data_map_['style_id'])){
        	$this->data_map_['style_id'] = $this->style_id_;
        }
		if(!isset($this->data_map_['goods_discount'])){
        	$this->data_map_['goods_discount'] = $this->goods_discount_;
        }
        
        global $db;

        $order_goods_sql_values = array();
		//商品价格有修改，检查是否允许修改
        if($this->goods_price_ != $this->data_map_['goods_price']){
            if (($_SESSION['party_id'] == '65574' || $this->order_info_['party_id'] == '65574') && ! check_admin_priv('gymboree_order_goods_price_update')) {
	        	$this->error_info_['err_no'] = 1;
	        	$this->error_info_['message'] .= '抱歉，金宝贝不允许修改商品价格，如果情况特殊，请申请权限【金宝贝商品价格修改】';
                return false;
            }
            if($this->order_info_['order_type_id'] == 'RMA_EXCHANGE' || strpos($this->order_info_['order_sn'],'-h') != false){
            	//换货订单无法修改商品价格
	        	$this->error_info_['err_no'] = 1;
	        	$this->error_info_['message'] .= '抱歉，换货订单不允许修改商品价格';
                return false;
            }
            //
            $order_goods_sql_values[] = "goods_price = '".$this->data_map_['goods_price']."' ";
        	$this->data_map_for_insert_order_action_['action_note'][] = mysql_real_escape_string("修改商品[{$this->goods_name_}]的价格从{$this->goods_price_}到"
            	.$this->data_map_['goods_price']);
            $this->SetGoodsPrice($this->data_map_['goods_price']);
        }

        //商品数量或style有修改，检查库存
        if($this->goods_number_ != $this->data_map_['goods_number'] || $this->style_id_ != $this->data_map_['style_id']){
        	$start = microtime();
        	$sql_for_goods = "select p.product_name, sum(s.AVAILABLE_TO_RESERVED) as goods_stock 
        						from romeo.product_mapping pm
        						left join romeo.product p on pm.product_id = p.product_id
        						left join romeo.inventory_summary s on 
        								pm.product_id = s.product_id and s.AVAILABLE_TO_RESERVED > 0 and s.facility_id = {$this->order_info_['facility_id']}
        						where pm.ECS_GOODS_ID = {$this->goods_id_} and pm.ECS_STYLE_ID = {$this->data_map_['style_id']}
        						group by pm.product_id";
        	
	        $new_goods_info = $db->getRow($sql_for_goods);
    	    $new_goods_name = mysql_real_escape_string($new_goods_info['product_name']);
    	    $new_goods_stock = $new_goods_info['goods_stock'] ? $new_goods_info['goods_stock'] : 0;
	        // 先不需要判断库存信息
//	        if($new_goods_stock < $this->data_map_['goods_number']){
//	        	//库存不足
//        		$this->error_info_['err_no'] = 1;
//        		$this->error_info_['message'] .= "{$new_goods_name} 当前库存为".$new_goods_stock."，请修改数量";
//            	return false;
//	        }
            //
            if($this->goods_number_ != $this->data_map_['goods_number']){
	            $this->data_map_for_insert_order_action_['action_note'][] = mysql_real_escape_string("修改商品{$new_goods_name}的数量为"
	            	.$this->data_map_['goods_number']);
	            $order_goods_sql_values[] = "goods_number = '".$this->data_map_['goods_number']."' ";
	            $this->SetGoodsNumber($this->data_map_['goods_number']);
            }
            if($this->style_id_ != $this->data_map_['style_id']){
				
		    	$sql = "select product_name from romeo.product p 
		    			left join romeo.product_mapping pm on p.product_id = pm.product_id
		    			where pm.ECS_GOODS_ID = {$this->goods_id_} and pm.ECS_STYLE_ID = {$this->data_map_["style_id"]} limit 1";
		    	$goods_name = $db->getOne($sql);
		    	$goods_name = mysql_real_escape_string($goods_name);
		    	if(empty($goods_name)){
	        		$this->error_info_['err_no'] = 1;
	        		$this->error_info_['message'] .= "商品名不存在，请检查类型数据";
	            	return false;
		    	}
		    	
		    	
				$order_goods_sql_values[] = " goods_name = '".$goods_name."' ";
    	        $order_goods_sql_values[] = "style_id = '".$this->data_map_['style_id']."' ";

	            $sql = "select concat(color, '_', value) from ecshop.ecs_style where style_id = '{$this->data_map_['style_id']}'";
	            $style_name = $db->getOne($sql);
	            $this->data_map_for_insert_order_action_['action_note'][] = mysql_real_escape_string("修改商品{$new_goods_name}的类型为{$style_name}");
            }
        }

		//检查优惠券是否有改动，判断是否大于商品总价格
		if($this->goods_discount_ != $this->data_map_['goods_discount']){
			if($this->data_map_['goods_discount'] > $this->total_price_ ){
				$this->error_info_['err_no'] = 1;
	        	$this->error_info_['message'] .= "商品优惠价格不能大于该商品总金额";
				return false;
			}
			$condition = " NAME = 'DISCOUNT_FEE' AND order_goods_id = ".$this->rec_id_;
			$sql = "select 1 from ecshop.order_goods_attribute WHERE $condition" ;
			if($db->getOne($sql)){
				$sql = "update ecshop.order_goods_attribute 
						SET value = ".$this->data_map_['goods_discount']." 
						WHERE $condition";
			}
			else{
				$sql = "INSERT INTO ecshop.order_goods_attribute (order_goods_id, name, value) 
						VALUES (".$this->rec_id_.",'DISCOUNT_FEE',".$this->data_map_['goods_discount'].")";
			}
			$this->sql_result_[] = $sql;
			$this->data_map_for_insert_order_action_['action_note'][] = mysql_real_escape_string("修改商品[{$this->goods_name_}]的优惠券从{$this->goods_discount_}到"
            	.$this->data_map_['goods_discount']);
            $drift = $this->data_map_['goods_discount'] - $this->goods_discount_;
            $this->goods_discount_ = $this->data_map_['goods_discount'];
            //修改订单优惠总金额
            $sql = "select attr_value from order_attribute where order_id = {$this->order_id_} AND attr_name = 'DISCOUNT_FEE'";
            $order_discount = $db->getOne($sql);
            $this->order_info_['bonus'] = -1*$this->updateBonus($order_discount,$drift);
		}
        //
        
		if(!empty($order_goods_sql_values)){
			$this->sql_result_[] = "update ecshop.ecs_order_goods set ".implode(',', $order_goods_sql_values) . " where rec_id = {$this->rec_id_}";
		}
		return true;
	}
	private function GenerateSqlsForInsertOrderGoods(){
    	global $db;
    	//check
    	//检查数据合理性
    	if($this->data_map_['goods_number'] <= 0){
    		$this->error_info_['err_no'] = 1;
    		$this->error_info_['message'] .= "商品数量不可小于1，请修改商品数量";
        	return false;
    	}
    	
    	// 添加套餐
    	if(!empty($this->data_map_['group_goods_id']) and $this->data_map_['group_goods_id'] != -1) {
    		$tc_code = mysql_real_escape_string($this->data_map_['group_goods_id']);
    		require_once('../function.php');
    		$group_order_goods = get_group_order_goods($tc_code,$this->order_info_['party_id']);
		    $group_order_goods = $group_order_goods['group_order_goods']['code'][$tc_code];
		    
			if(empty($group_order_goods)) {
				$this->error_info_['err_no'] = 1;
	    		$this->error_info_['message'] .= "根据套餐编码找不到对应的商品列表：".$tc_code;
	        	return false;
			}
			
			$this->group_total_price_ = 0;
			foreach($group_order_goods as $group_order_good) {
				$this->group_total_price_  += $group_order_good['goods_number'] * $group_order_good['goods_price'];
				$goods_name = mysql_real_escape_string($group_order_good['goods_name']);
				$this->sql_insert_with_attr[] = 
					array(
						'insert_sql' => "insert into ecshop.ecs_order_goods 
			        		(order_id, goods_id, style_id, goods_name, goods_number, goods_price,status_id,added_fee) 
			        		values ({$this->order_id_}, {$group_order_good['goods_id']}, {$group_order_good['style_id']}, '{$goods_name}', {$group_order_good['goods_number']},
			        		 {$group_order_good['goods_price']},'INV_STTS_AVAILABLE',{$group_order_good['added_fee']})",
						'attr_table_name' => 'ecshop.order_goods_attribute',
						'attr_table_source_id_name' => 'order_goods_id',
						'attrs' => array(
							array('name' => 'OUTER_IID','value' => $tc_code)							
							)
						);
				/*$this->sql_result_[] = 
		        	"insert into ecshop.ecs_order_goods 
		        		(order_id, goods_id, style_id, goods_name, goods_number, goods_price,status_id,added_fee) 
		        		values ({$this->order_id_}, {$group_order_good['goods_id']}, {$group_order_good['style_id']}, '{$goods_name}', {$group_order_good['goods_number']},
		        		 {$group_order_good['goods_price']},'INV_STTS_AVAILABLE',{$group_order_good['added_fee']})";*/
		        $this->data_map_for_insert_order_action_['action_note'][] = mysql_real_escape_string("添加套餐【{$tc_code}】【{$group_order_good['group_name']}】包含商品{$goods_name}：单价{$group_order_good['goods_price']}, 数量{$group_order_good['goods_number']}");
			}

    		return true;
    	} 
    	// 添加普通商品
    	else 
    	{

			$sql_for_goods = "select goods_party_id, p.product_name, sum(s.AVAILABLE_TO_RESERVED) as goods_stock 
						from romeo.product_mapping pm
						left join ecshop.ecs_goods eg on pm.ECS_GOODS_ID = eg.goods_id
						left join romeo.product p on pm.product_id = p.product_id
						left join romeo.inventory_summary s on 
								pm.product_id = s.product_id and s.AVAILABLE_TO_RESERVED > 0 and s.facility_id = {$this->order_info_['facility_id']}
						where pm.ECS_GOODS_ID = {$this->data_map_['goods_id']} and pm.ECS_STYLE_ID = {$this->data_map_['style_id']}
						group by pm.product_id";
	        $goods_info = $db->getRow($sql_for_goods);
	        if(!$goods_info){
	    		$this->error_info_['err_no'] = 1;
	    		$this->error_info_['message'] .= "商品不存在，请重新选择";
	        	return false;
			}
		    $goods_name = mysql_real_escape_string($goods_info['product_name']);
		    $goods_stock = $goods_info['goods_stock'] ? $goods_info['goods_stock'] : 0;
	        if($goods_info['goods_party_id'] != $this->order_info_['party_id']){
	    		$this->error_info_['err_no'] = 1;
	    		$this->error_info_['message'] .= "商品{$goods_name} 组织错误，请重新选择商品";
	        	return false;
	        }
	        // 先不考虑库存问题
	//        if($goods_stock < $this->data_map_['goods_number']){
	//        	//库存不足
	//    		$this->error_info_['err_no'] = 1;
	//    		$this->error_info_['message'] .= "{$goods_name} 当前库存为".$goods_stock."，请修改数量";
	//        	return false;
	//        }
	        //
	        $this->sql_result_[] = 
	        	"insert into ecshop.ecs_order_goods 
	        		(order_id, goods_id, style_id, goods_name, goods_number, goods_price) 
	        		values ({$this->order_id_}, {$this->data_map_['goods_id']}, {$this->data_map_['style_id']}, '{$goods_name}', {$this->data_map_['goods_number']}, {$this->data_map_['goods_price']})";
	        $this-> data_map_['goods_name'] = $goods_name;
	        //
	        $this->SetGoodsNumberAndPrice($this->data_map_['goods_price'], $this->data_map_['goods_number']);
	        // 
	        $this->data_map_for_insert_order_action_['action_note'][] = mysql_real_escape_string("添加商品{$goods_name}：单价{$this->data_map_['goods_price']}, 数量{$this->data_map_['goods_number']}");
	    	
	    	return true;	
    	}

	}
	private function GenerateSqlsForDeleteOrderGoods(){
		global $db;

		//check
		$sql = "select count(*) from ecshop.ecs_order_goods where order_id = {$this->order_id_}";
		if($db->getOne($sql) <= 1){
    		$this->error_info_['err_no'] = 1;
    		$this->error_info_['message'] .= "当前商品为该订单最后一个商品，无法删除；请先添加其他商品或直接取消订单";
        	return false;
		}

		$sql = "SELECT eog_child.rec_id
				from ecshop.ecs_order_goods eog_child
				LEFT JOIN ecshop.ecs_order_goods eog_parent on eog_child.order_id = eog_parent.order_id and eog_child.parent_id = eog_parent.rec_id
				where eog_parent.rec_id = {$this->rec_id_}";
		$rec_ids = $db->getCol($sql);
		$rec_ids[] = $this->rec_id_;
		$rec_ids = implode(',', $rec_ids);
		$this->sql_result_[] = 
        	"delete from ecshop.ecs_order_goods where rec_id in ($rec_ids)";
		//由于要先删除所有订单商品关联记录后才能重新对订单红包进行求和，因此先在这里进行删除
        $this->sql_result_[] = "delete from ecshop.order_goods_attribute where order_goods_id in ($rec_ids)";
		//判断是否需要更新红包，目前仅OR才会传这个参数
        if($this->data_map_['changeBonus']){
			$sql = " SELECT IFNULL(sum(value),0) as goods_discount
            		FROM ecshop.order_goods_attribute 
     				WHERE  name= 'DISCOUNT_FEE' AND order_goods_id in ($rec_ids)";
     		$drift = $db->getOne($sql);
     		$sql = " SELECT attr_value from order_attribute where order_id = {$this->order_id_} and attr_name = 'DISCOUNT_FEE' ";
        	$order_discount = $db->getOne($sql);
        	$this->order_info_['bonus'] = -1*$this->updateBonus($order_discount, -1*$drift);
        }
        $sql = "select goods_name,goods_number,goods_price from ecshop.ecs_order_goods where rec_id = {$this->rec_id_} limit 1";
        $rec_info = $db->getRow($sql);
        
        $this->SetGoodsNumberAndPrice(0,0);
        $this->data_map_for_insert_order_action_['action_note'][] = mysql_real_escape_string("删除商品{$rec_info['goods_name']}：单价{$rec_info['goods_price']}, 数量{$rec_info['goods_number']}");
        return true;
	}
	protected function ModifyViaRomeo(){
		try{
			$handle=soap_get_client("InventoryService");
			$handle->cancelOrderInventoryReservation(array('orderId'=>$this->order_id_));
		}
		catch(Exception $e) 
		{
            $this->error_info_ = array('err_no' => 3, 'message' => "romeo接口调用失败".$e->getMessage());
            return false;
		}
		return true;
	}

	static function GetOrderGoodsAttrListForUpdate(){
		return array('style_id', 'goods_price', 'goods_number','goods_discount');
	}
	static function GetAllAttrListForUpdate(){
		return array_merge(ClsOrderGoodsInfo::GetOrderGoodsAttrListForUpdate());
	}
	static function GetAllAttrListForInsert(){
		return array('group_goods_id','goods_id', 'style_id', 'goods_price', 'goods_number');
	}

	//end for modify
	function SetGoodsNumberAndPrice($goods_price, $goods_number){
		$this->p_goods_price_ = $this->goods_price_ = $goods_price;
		$this->p_goods_number_ = $this->goods_number_ = $goods_number;
		$this->total_price_ = $this->goods_number_ * $this->goods_price_;
	}
	function SetGoodsNumber($goods_number){
		$this->p_goods_number_ = $this->goods_number_ = $goods_number;
		$this->total_price_ = $this->goods_number_ * $this->goods_price_;
	}
	function GetGoodsNumber(){
		return $this->goods_number_;
	}
	function SetGoodsPrice($goods_price){
		$this->p_goods_price_ = $this->goods_price_ = $goods_price;
		$this->total_price_ = $this->goods_number_ * $this->goods_price_;
	}
	function GetGoodsPrice(){
		return $this->goods_price_;
	}

	function ResetData(){
		$this->rec_id_ = 0;
		$this->goods_id_ = 0;
		$this->goods_name_ = 0;
		$this->style_id_ = 0;
		$this->style_name_ = 0;
		$this->goods_discount_ = 0;
		$this->goods_price_ = 0;
		$this->p_goods_price_ = 0;
		$this->goods_number_ = 0;
		$this->p_goods_number_ = 0;
		$this->total_price_ = 0;
		$this->reserve_status_ = 0;
		$this->inv_count_in_facility_ = 0;
		$this->shipping_invoices_ = array();
		$this->serial_numbers_ = array();
	}

	var $order_info_ = array();
	//ecs_order_goods
	var $rec_id_ = 0;
	//商品名称 goods_name
	var $goods_id_ = 0;
	var $goods_name_ = '商品名';
	//样式名 style_name
	var $style_id_ = 0;
	var $style_name_ = '样式名';
	//单价 goods_price
	private $goods_price_ = 0.00;
	var $p_goods_price_ = 0.00;
	//数量 goods_number
	private $goods_number_ = 0;
	var $p_goods_number_ = 0;
	//商品优惠券
	var $goods_discount_ = 0;
	private $old_goods_discount = 0;

	var $goods_weight = 0;
	var  $goods_volume = 0;
	var $spec = 0; 

	//小计 goods_price * goods_number
	var $total_price_ = 0.00;

	//romeo.order_inv_reserved
	//预定状态
	var $reserve_status_ = 0;

	//配送仓库存 romeo.inventory_summary
	var $inv_count_in_facility_ = 0;

	//发票号
	// romeo.order__shipping_invoice.shipping_invoice
	var $shipping_invoices_ = array();
	
	// 出库串号
	var $serial_numbers_ = array();
    var $goods_list_ = array();
	static $s_func_map_for_generate_sqls_for_order_goods = array(
		'update' => "GenerateSqlsForUpdateOrderGoods",
		'insert' => "GenerateSqlsForInsertOrderGoods",
		'delete' => "GenerateSqlsForDeleteOrderGoods");
}

?>