<?php
class FreightSheet extends AbstractSheet {
    //    protected function check_format() {
    //
    //    }
    /**
     * 计算快递费
     * @param $order
     */
    protected function calculate_fee($order) {

    }
    /**
     * 查找订单信息
     */
    protected function get_order_by_tracking_number($tracking_number) {
        $order = parent::get_order_by_tracking_number($tracking_number);
        if ($order != false ) {
            $order = $this->get_invoice($order);
            //订单的保价金额
            $order = $this->get_insurance_charge($order);
            $order = $this->get_freight($order);
            
            //顺丰陆运与顺丰空运价格一致的地区,是顺丰陆运中打折部分,订单所在行标记蓝色 ljzhou 2013.05.23
            $order = $this->get_discount_shunfeng($order);
        }
        return $order;
    }
    //保价费用的计算
    /**
     * 计算订单的保价金额
     * @param array $order
     */
    protected function get_insurance_charge($order) {
        //怀轩   收取保价费用是重量为0 时类型为报价
        $insurance = 0;
        global $db;
        $sql = "
        	select group_concat(oi.order_id) as order_id , group_concat(oi.order_sn) as order_sn, 
        	group_concat( if(oi.order_type_id in('SHIP_ONLY','RMA_EXCHANGE'),
        	    ifnull((select oi2.taobao_order_sn
				from ecshop.order_relation ol 
				left join ecshop.ecs_order_info oi2 ON ol.root_order_id = oi2.order_id
				where ol.order_id = oi.order_id limit 1),''), oi.taobao_order_sn)) as taobao_order_sn
        	from romeo.order_shipment os
        	left join ecshop.ecs_order_info oi on oi.order_id = cast(os.order_id as unsigned)
        	where os.shipment_id = '{$order['shipment_id']}'
        	group by os.shipment_id
        ";
        $res_order = $db->getRow($sql);
        
        $order['order_id'] = $res_order['order_id'];
        $order['order_sn'] = $res_order['order_sn'];
        $order['taobao_order_sn'] = $res_order['taobao_order_sn'];
        if ( isset($this->content[$order['tracking_number']]['excel_insurance']) ) {
        	if($order['party_id'] == 65562){
				//ECCO：EMS保价费为每双鞋10元,顺风每双鞋子，每只包都是10元,EMS保价费率为0.5%
        		/* $sql = "
						SELECT 		sum(og.goods_number) 
						FROM 		ecshop.ecs_order_goods og
        				left join 	ecshop.ecs_goods g on g.goods_id = og.goods_id
        				where 		og.order_id = {$order['order_id']} 
						and 		g.cat_id = 2404 
						group by 	og.order_id
				"; */
        		
        		$sql = "
        					select  sum(og.goods_number)
        					from romeo.order_shipment os
        					left join ecshop.ecs_order_goods og on og.order_id = os.order_id
        					left join ecshop.ecs_goods g on g.goods_id = og.goods_id
        					where os.shipment_id = '{$order['shipment_id']}'
        					and( g.cat_id = 2404
        					or g.goods_id in (82861, 82862, 82352))
        					group by os.shipment_id 
        		";
        		$goods_number = $db->getOne($sql);
        		
        		if ($order['shipment_type_id'] == '44'||$order['shipment_type_id'] == '117') {
        			$insurance = $goods_number * 5;
        		} elseif ($order['shipment_type_id'] == '36' || $order['shipment_type_id'] == '47') {
        			$insurance = $goods_number * 5;
        		}
        	}
        	elseif( $order['party_id'] == 128 ){
        		//顺丰的保价费是按照订单的金额，取千整数然后乘以千分之五
        		//EMS的是百分之一也是取千整数，然后是百分之一,（怀轩-顺丰，怀轩-EMS）
        		$sql = "
        					select sum(oi.order_amount) 
        					from romeo.order_shipment os
        					left join ecshop.ecs_order_info oi on oi.order_id = cast(os.order_id as unsigned)
        					where os.shipment_id = '{$order['shipment_id']}'
        					group by os.shipment_id
        		";
        		$order['order_amount'] = $db->getOne($sql);
        		$order_insurance = floor($order['order_amount'] / 1000) * 1000;
        		if ($order['shipment_type_id'] == '44'||$order['shipment_type_id'] == '117') {
        			$insurance = 0.005 * $order_insurance;
        		} elseif ($order['shipment_type_id'] == '36' || $order['shipment_type_id'] == '47') {
        			$insurance = 0.005 * $order_insurance;
        		}
        	} 
        	elseif($order['party_id'] ==65551 ){//保乐力加
        		$sql = "
        		select  g.goods_name, og.goods_number
        					from romeo.order_shipment os
        					left join ecshop.ecs_order_goods og on og.order_id = os.order_id
        					left join ecshop.ecs_goods g on g.goods_id = og.goods_id
        					where os.shipment_id = '{$order['shipment_id']}'
        		";
        		$result = $db->getAll($sql);
        		$goods_name = '';
        		$goods_number = 0;
        		$flag = false;
        		foreach ($result as $key=> $r)
        		{
        			$goods_number+=$r['goods_number'];
        			$goods_name = $r['goods_name'];
        			if(is_int(strpos($goods_name,'皇家礼炮')) || $goods_number>=10){
        				$flag = true;
        				break;
        			}
        		}
               //查询订单的金额、支付时间
        	   $sql = "
				select sum(oi.order_amount) as order_amount,oi.pay_time 
				from romeo.order_shipment os
				left join ecshop.ecs_order_info oi on oi.order_id = cast(os.order_id as unsigned)
				where os.shipment_id = '{$order['shipment_id']}'
				and FROM_UNIXTIME(oi.pay_time) >= '2013-12-01'
				group by os.shipment_id
        		";
        	   //Qlog::log('order_amount='.$sql);
        	   $order_am_ti = $db->getRow($sql);
        	   $order['order_amount'] = $order_am_ti['order_amount'];
        		//1.  顺丰陆运和宅急送，只保皇家礼炮以及10瓶以上的订单，按照1000的价格来保，其他订单取消保价 
                //2.  中通、EMS（不含经济型），皇家礼炮以及10瓶以上的订单，按照1000的价格来保，其他订单按照200元的价格来保
                //3.  保价费率：顺风陆运0.5%, 宅急送 0.5% ,EMS 0.5%,中通保价费率为1%
        		if($flag){//参保金额为1000
        			if($order['shipment_type_id'] == '117'){//顺丰陆运0.5%
        				$insurance =5;
        			}
        		    elseif($order['shipment_type_id'] == '12'){//宅急送 0.5%
        				$insurance =5;
        			}
        			elseif($order['shipment_type_id'] == '47'){//EMS 0.5%
        				$insurance =5;
        			}
        			elseif($order['shipment_type_id'] == '115'){//中通 1%
        				$insurance =10;
        			}
        			elseif($order['shipment_type_id'] == '99'){//汇通1%
        		    	if(!empty($order['order_amount'])&&$order['order_amount'] >= 500){//订单金额大于等于500，参保金额为订单销售额
        		    	  $insurance = $order['order_amount'] * 0.01;
        		    	}
        		    }
        		}
        		else{//参保金额为200
        			if($order['shipment_type_id'] == '47'){//EMS 0.5%
        				$insurance =1;
        			}
        			elseif($order['shipment_type_id'] == '115'){//中通 1%
        				$insurance =2;
        			}
        		    elseif($order['shipment_type_id'] == '99'){//汇通1%
        		    	if(!empty($order['order_amount'])&&$order['order_amount'] >= 500){//订单金额大于等于500，参保金额为订单销售额
        		    	  $insurance = $order['order_amount'] * 0.01;
        		    	}
        		    }
        		}
        	}
        }
        Qlog::log('clas_freight_sheet tracking_number:'.$order['tracking_number'].' insurance:'.$insurance);
        $order['insurance'] = $insurance;
        return $order;
    }
    /**
     * 检查保价费是否正确
     * @param array $order
     */
    protected function check_insurance() {
        foreach ($this->order_list as $order) {
            //怀轩   收取保价费用是重量为0
            if (isset($order['excel_row']['excel_insurance']) && ($order['party_id'] == 128 || $order['party_id'] == 65562 || $order['party_id'] == 65551 )) {
                $insurance_diff = $order['excel_row']['excel_insurance'] - $order['insurance'];
                if ($insurance_diff > 0) {
                    $order['insurance_diff'] = $insurance_diff;
                    $order['insurance_note'] = '保价费多收';
                    $this->order_list[$order['tracking_number']]['insurance_diff'] = $order['insurance_diff'];
                    $this->order_list[$order['tracking_number']]['insurance_note'] = $order['insurance_note'];
                    $this->error_insurance_list[$order['tracking_number']] = $order;
                }
            } 
        }
    }

    /**
     * 顺丰陆运与顺丰空运价格一致的地区,是顺丰陆运中打折部分,订单所在行标记蓝色
     * @param array $order
     */
    protected function get_discount_shunfeng($order) {
        // 顺丰（陆运），顺丰陆运—淘宝COD
        $shunfeng_shipping = array('117','122');

        if (in_array($order['shipment_type_id'],$shunfeng_shipping)) {
        	$error_sf = check_is_discount_shunfeng($order);
        	if($error_sf) {
        		$order['is_discount_shunfeng'] = true;
        	}
        } 
        return $order;
    }
    
    /**
     * 检查运单是否为补寄发票
     * 补寄运单中根据组织不同，发货仓库不同
     * @param array $order 订单信息
     */
    protected function get_invoice($order) {
        //先判断是否为补寄发票
        if($order['shipping_category'] == 'SHIPPING_INVOICE'){
        	
			$order['shipping_time'] = $order['invoice_shipping_time'];
            global $db;
            $sql = "
                SELECT  ia.district, ia.city, ia.province, r1.region_name as district_name, r2.region_name as city_name, 
                    r3.region_name as province_name, ia.consignee
                FROM ecshop.ecs_invoice_addr ia
                LEFT JOIN ecshop.ecs_region as r1 ON r1.region_id = ia.district
                LEFT JOIN ecshop.ecs_region as r2 ON r2.region_id = ia.city
                LEFT JOIN ecshop.ecs_region as r3 ON r3.region_id = ia.province 
                WHERE ia.order_id = {$order['order_id']} ";
            $info = $db->getRow($sql);
            $this->content[$order['tracking_number']]['invoice'] = $info;
            //根据组织，判断补寄发票的快递单的寄出地
            //方广，雀巢补寄发票寄出地为杭州，默认为电商服务杭州仓，其他组织为东莞，默认为电商服务东莞仓
            $hangzhou = array('65550', '65553');
            if (in_array($order['party_id'], $hangzhou)) {
                $facility_id = '22143847';
                $order['facility_name'] = "电商服务杭州仓（补寄发票）";
                $order['fee_note'] = '杭州补寄发票为人工核算';
            } else {
                $facility_id = '19568548';
                $order['facility_name'] = "电商服务东莞仓（补寄发票）";
            }
            $order['addr'] = $info;
            $order['addr']['facility_id'] = $facility_id;
        } else {
            $order['addr']['district'] = $order['district'];
            $order['addr']['city'] = $order['city'];
            $order['addr']['province'] = $order['province'];
            $order['addr']['district_name'] = $order['district_name'];
            $order['addr']['city_name'] = $order['city_name'];
            $order['addr']['province_name'] = $order['province_name'];
            $order['addr']['consignee'] = $order['consignee'];
            $order['addr']['facility_id'] = $order['facility_id'];
        }
        return $order;
    }
    /**
     * 查询运费
     * @param array $order 订单信息
     * @return array 包含运费的订单信息
     */
    protected function get_freight($order) {
        global $db;
        //内部员工自提  首重快递费和续重快递费为0
        if ($order['shipment_type_id'] == 86) {
            $order['first_weight'] = 0;
            $order['first_fee'] = 0;
            $order['continued_fee'] = 0;
        } else {
        	
            if ($order['addr']['district']) {
                $region_id = $order['addr']['district'];
            } elseif ($order['addr']['city']) {
                $region_id = $order['addr']['city'];
            } elseif ($order['addr']['province']) {
                $region_id = $order['addr']['province'];
            }
            $facility_id = facility_convert($order['addr']['facility_id']);
            $sql = "
                SELECT first_weight, first_fee, continued_fee
                FROM ecshop.ecs_carriage 
                WHERE facility_id = '{$facility_id}' AND carrier_id = '{$order['shipment_type_id']}' 
                    AND region_id = '{$region_id}'
            ";
            $region_list = $db->getRow($sql);
            if (!empty($region_list)) {
                $order['first_weight'] = $region_list['first_weight'];
                $order['first_fee'] = $region_list['first_fee'];
                $order['continued_fee'] = $region_list['continued_fee'];
            } else {
                $order['first_weight'] = -1;
                $order['first_fee'] = -1;
                $order['continued_fee'] = -1;
            }
        }
        return $order;
    }

    /**
     * 检查所有的运费设置是否有异常，或者地区是否有异常，将这些异常存入$this->missing_carriage_list中
     * 从cls_abstract_sheet.php中剪切出来
     */
    protected function check_freight_set() {
        foreach ($this->order_list as $key=>$order) {
            if ($order['first_fee'] == -1 || $order['continued_fee'] == -1) {
                $this->missing_carriage_list[$order['tracking_number']] = $order;
                unset($this->order_list[$key]);
            }
        }
    }

    /**
     * 费用检查
     */
    protected function check_fee() {
        foreach ($this->order_list as $key=>$order) {
            $order = $this->calculate_fee($order);
            $this->order_list[$order['tracking_number']]['calculated_fee'] = $order['calculated_fee'];
            $facility = facility_convert($order['facility_id']);
            //电商服务北京仓导出对账单的时候使用预估重量
            if($order['facility_id'] == '79256821'){
            	$this->order_list[$order['tracking_number']]['shipping_leqee_weight'] = $order['shipping_leqee_weight'];
            }
            //如果按快递公司重量算的运费大于按仓库重量算的运费算是异常重
            //不直接通过重量的比较是因为重量有一个取舍的过程，最后运费可能是一致的。
           if(round($order['calculated_fee'],2) > round($order['shipping_fee'],2)&&$facility!='12768420' ){
          	//在重量异常的基础上再比较快递公司实际收取的费用和按照系统重量计算的运费，如果快递公司实际收取的费用反而要小，则这条记录放入费用正常订单列表
                if(($order['excel_row']['final_fee'] - $order['shipping_fee'])<=0){
                	continue;
                }
                else{
                	$this->error_weight[$order['tracking_number']] = $order;
                	unset($this->order_list[$key]);
                }	
            }else{
            	if ($order['excel_row_note'] != "办公件") {
            		$order['fee_diff'] = $order['excel_row']['final_fee'] - $order['calculated_fee'];
            		$order['fee_diff'] = trim($this->get_real_value($order['fee_diff']));
            		//判断运费已经设置 且 费用多收
            		if ($order['fee_diff'] > 0 && $order['calculated_fee'] != false) {
            			$order['fee_note'] = '多收';
//             		现在保价费检查结果和运费检查结果分开了
            			$this->error_fee_list[$order['tracking_number']] = $order;
            			unset($this->order_list[$key]);
            		}
            		else{
            			$this->order_list[$key] = $order;
            		}
                } 
            }
        }
    }
    /**
     * 检查数据
     * 检查是否存在没有设置运费的地区,如果是手续费不需要检查
     * @return true false
     */
    public function check_data() {
        $this->check_freight_set();
        //先检查保价费后检查快递费
        if('export' != $_REQUEST['act']){
        	$this->check_insurance();
        }     
        $result = parent::check_data();
        if (empty($this->missing_carriage_list) && $result && empty($this->error_match_list)) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * 在未找到快递单号的运单里面查找办公件
     * @param array $missing_order_list
     */
    public function check_office_shipment($shipping_id){
    	global $db;
    	if (!empty($this->missing_order_list)) {
    		foreach ($this->missing_order_list as $key => $item) {
    			//检查是否为办公件
    			$sql = "
		    		select os.shipment_id, os.party_id, os.shipping_date, os.start_province, os.start_city, os.start_district,
						os.end_province, os.end_city, os.end_district, os.tracking_number, os.package_type, os.shipping_id,
						os.weight, os.action_user, os.status, os.note, r1.region_name as start_province_name, 
						r2.region_name as start_city_name,r3.region_name as start_district_name,
						r4.region_name as end_province_name, r5.region_name as end_city_name, 
						r6.region_name as end_district_name, p.name, os.action_time, s.shipping_name
					from romeo.office_shipment os
					left join romeo.party p on os.party_id = p.party_id
					left join ecshop.ecs_shipping s on s.shipping_id = os.shipping_id
					left join ecshop.ecs_region r1 on r1.region_id = os.start_province
					left join ecshop.ecs_region r2 on r2.region_id = os.start_city
					left join ecshop.ecs_region r3 on r3.region_id = os.start_district
					left join ecshop.ecs_region r4 on r4.region_id = os.end_province
					left join ecshop.ecs_region r5 on r5.region_id = os.end_city
					left join ecshop.ecs_region r6 on r6.region_id = os.end_district
					where os.status = 'OK' and tracking_number = '{$item['tracking_number']}'
    			";
    			$row = $db->getRow($sql);
    			if (!empty($row)) {
    				$row['excel_row'] = $item;
    				$row['excel_row_note'] = '办公件';
    				if ($shipping_id != $row['shipping_id']) {
    					$this->error_match_list[$row['tracking_number']] = $row;
    					unset($this->missing_order_list[$key]);
    					continue;
    				}
    				//计算快递费
    				$this->check_office_fee($row);
    				unset($this->missing_order_list[$key]);
    			}
    		}
    	}
    	//检查数据返回true 或false
    
    }
    /**
     * 获取办公件的快递费
     * @param array $row
     */
    protected function check_office_fee ($row) {
    	global $db;
    	if (!empty($row['start_district'])) {
    		$from_region_id = $row['start_district'];
    	} elseif (!empty($row['start_city'])) {
    		$from_region_id = $row['start_city'];
    	} elseif (!empty($row['start_province'])) {
    		$from_region_id = $row['start_province'];
    	}
    	//转换为仓库 todo
    	$region_facility_list = array(
    		'462' => '19568549', // 上海市青浦区崧煌路810号3号楼2楼 
    		'451' => '12768420', //上海市长宁区愚园路1258号1005室
    		'490' => '42741887', //北京海淀区羊坊店路18号光耀东方S座847室
    		'2771'=> '19568548', //广东省  东莞市  长安镇  乌沙步步高大道126号
    	);
		$str = "";
        if (array_key_exists($from_region_id, $region_facility_list)) {
    		$facility_id = $region_facility_list[$from_region_id] ;
    		$str = " and facility_id = '{$facility_id}' ";
    		$row['facility_id'] = $facility_id;
    	} else {
    		$str = " and from_region_id = {$from_region_id} ";
    	}
    	if (!empty($row['end_district'])) {
    		$region_id = $row['end_district'];
    	} elseif (!empty($row['end_city'])) {
    		$region_id = $row['end_city'];
    	} elseif (!empty($row['end_province'])) {
    		$region_id = $row['end_province'];
    	}
    	$sql = "
    		select first_weight, first_fee, continued_fee
    		from ecshop.ecs_carriage 
    		where region_id = {$region_id}  and carrier_id = {$row['shipping_id']} ". $str ."
    		limit 1
    	";
    	$fee_item = $db->getRow($sql);
    	if (!empty($fee_item)) {
    		$row['first_weight'] =  $fee_item['first_weight'];
    		if ($row['package_type'] == 1) {
    			$row['office_shipping_fee'] = $fee_item['first_fee'];
    			$row['weight'] = $fee_item['first_weight'];
    		} elseif ($row['package_type'] == 2) {
    			$weight = $this->get_weight($row['weight'], $row);
    			$row['office_shipping_fee'] = $fee_item['first_fee'] +  $weight * $fee_item['continued_fee'];
    		}
    		$row['office_shipping_fee'] = $this->get_real_value($row['office_shipping_fee']);
    		$out_weight = $this->get_weight($row['excel_row']['weight'], $row);
    		$row['office_calculated_fee'] = $fee_item['first_fee'] +  $out_weight * $fee_item['continued_fee'];
    		$row['office_calculated_fee'] = $this->get_real_value($row['office_calculated_fee']);
    		$row['fee_diff'] = $row['excel_row']['final_fee'] - $row['office_shipping_fee'];
    		if ( round($row['excel_row']['final_fee'],2) > round($row['office_shipping_fee'],2) && ($row['excel_row']['weight'] > $row['weight'])) {
    			$this->error_weight[$row['tracking_number']] = $row;
    		} elseif ($row['fee_diff'] > 0) {
    			$row['fee_note'] = '多收（办公件）'.$row['note'];
    			$this->error_fee_list[$row['tracking_number']] = $row;
    		} else {
    			$this->order_list[$row['tracking_number']] = $row;
    		}
    	} else {
    		$this->missing_carriage_list[$row['tracking_number']] = $row;
    	}
    	
    }
    /**
     * 计算续重
     * @param $weight       excel账单中的重量
     * @param $first_weight 系统中记录的首重
     * @return 快递单的续重
     */
    protected function get_weight($weight, $first_weight) {
    	if(isset($first_weight['first_weight'])){
    		$first_weight = $first_weight['first_weight'];
    	}
        $weight = max(0, ceil(($weight - $first_weight)/$first_weight));//首重为0.5和1的情况下有差别
        return $weight;
    }
     /**
     * 更新快递费
     */
    public function update_fee() {
        if ($this->check_data()) {
            foreach ($this->order_list as $order) {
                if (!empty($order['shipment_id'])) {
                	if ($order['excel_row_note'] == '办公件') {
                		//$office_shipment_list
                		$office_shipment = new stdClass();
                		$office_shipment->shipmentId = $order['shipment_id']; 
                		$office_shipment->lastActionUser = $_SESSION['admin_name']; 
                		$office_shipment->outWeight = $order['excel_row']['weight']; 
                		$office_shipment->shippingCost = $order['excel_row']['final_fee']; 
                		$this->office_shipment_list[] = $office_shipment;
                	} else {
                    $shipment = new stdClass();
                    $shipment->shipmentId = $order['shipment_id']; 
                    $shipment->shippingLeqeeWeight = -1;
                    $shipment->shippingServiceFee = -1;
                    if(!empty($order['excel_row']['weight'])){
                    	 $shipment->shippingOutWeight = $order['excel_row']['weight'] * 1000;
                    }
                    else{
                    	 $shipment->shippingOutWeight = -1;
                    }
                    if(!empty($order['excel_row']['final_fee'])){
                    	$shipment->shippingCost = $order['excel_row']['final_fee'];
                    }
                    else{
                    	$shipment->shippingCost = -1;
                    }
                    //是否更新保价费
                    if (isset($order['excel_row']['excel_insurance'])&&!empty($order['excel_row']['excel_insurance'])) {
                        $shipment->shippingInsuredCost = $order['excel_row']['excel_insurance'];
                    } else {
                        $shipment->shippingInsuredCost = -1;
                    }
                    if(isset($order['excel_row']['remark'])&&!empty($order['excel_row']['remark'])){
                    	$shipment->billNote = $order['excel_row']['remark'];
                    	$shipment->shippingNote = $order['excel_row']['remark'];
                    }
                    else{
                    	$shipment->billNote = '无';
                    	$shipment->shippingNote = '无';
                    	
                    }
                    $this->shipment_list[] = $shipment ;
                	}
                } else {
                    continue;
                }
            }
        	if (!empty($this->office_shipment_list)) {
            	$soap_client_office = soap_get_client('OfficeShipmentService');
            	$result = $soap_client_office->updateOfficeShipmentByImport(array('officeShipmentList' => $this->office_shipment_list));
            }
            if (!empty($this->shipment_list)) {
                parent::update_fee();
            }
        }
    }
    /**
     * 将异常数据输出
     * @param string $file_name 导出excel文件名
     */
    public function export_error_excel($file_name) {
    	$cell_nos = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
    	
        $excel = new PHPExcel();
        $excel->getProperties()->setTitle($file_name);
        $sheet_no = 1;
        if (!empty($this->error_fee_list)) {
            //var_dump($this->error_fee_list);die();
            $sheet_no ++;
            $sheet = $excel->getActiveSheet();
            $sheet->setTitle('快递费多收');
            $sheet->setCellValue('A1', "日期");
			$sheet->setCellValue('B1', "运单号码");
			$sheet->setCellValue('C1', "订单号");
			$sheet->setCellValue('D1', "淘宝订单号");
			$sheet->setCellValue('E1', "收款方式");
			$sheet->setCellValue('F1', "组织");
			$sheet->setCellValue('G1', "仓库");
			$sheet->setCellValue('H1', "姓名");
			$sheet->setCellValue('I1', "省");
			$sheet->setCellValue('J1', "市及地区");
			$sheet->setCellValue('K1', "重量");
			$sheet->setCellValue('L1', "仓库称重重量(kg)");
			$sheet->setCellValue('M1', "快递公司快递费");
			$sheet->setCellValue('N1', "按快递公司重量算的快递费");
			$sheet->setCellValue('O1', "按系统重量算的快递费");
			$sheet->setCellValue('P1', "快递费差值");
			$sheet->setCellValue('Q1', "快递费备注");
            //var_dump($this->error_fee_list);die();
            $i = 2;
            foreach ($this->error_fee_list as $order) {

                $sheet->setCellValueExplicit("B{$i}", $order['tracking_number'], PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("C{$i}", $order['order_sn'], PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("D{$i}", $order['taobao_order_sn'], PHPExcel_Cell_DataType::TYPE_STRING);
                $sheet->setCellValue("E{$i}", $order['pay_name']);
                $sheet->setCellValue("F{$i}", $order['name']);
                if ($order['excel_row_note'] == '办公件') {
                	$sheet->setCellValue("A{$i}", $order['shipping_date']);
                	$sheet->setCellValue("G{$i}", $order['start_province_name'].$order['start_city_name'].$order['start_district_name']);
                	$sheet->setCellValue("H{$i}", '');
                	$sheet->setCellValue("I{$i}", $order['end_province_name']);
                	$sheet->setCellValue("J{$i}", $order['end_city_name'].$order['end_district_name']);
                	$sheet->setCellValue("N{$i}", $order['office_shipping_fee']);
                } else {
                	$sheet->setCellValue("A{$i}", $order['shipping_time']);
                	$sheet->setCellValue("G{$i}", $order['facility_name']);
                	$sheet->setCellValue("H{$i}", $order['addr']['consignee']);
                	$sheet->setCellValue("I{$i}", $order['addr']['province_name']);
                	$sheet->setCellValue("J{$i}", $order['addr']['city_name'].$order['addr']['district_name']);
                	$sheet->setCellValue("N{$i}", $order['calculated_fee']);
                	$sheet->setCellValue("O{$i}", $order['shipping_fee']);
                }
                $sheet->setCellValue("L{$i}", $order['shipping_leqee_weight']/1000);
                $sheet->setCellValue("K{$i}", $order['excel_row']['weight']);
                $sheet->setCellValue("M{$i}", $order['excel_row']['final_fee']);
                $sheet->setCellValue("P{$i}", $order['fee_diff']);
                $sheet->setCellValue("Q{$i}", $order['fee_note']);
                // 目前还没找到设置整行颜色的方法，先这样循环设置
            	if($order['is_discount_shunfeng']) {
            		$cell_numbers = array_slice($cell_nos,0,17);
            		foreach($cell_numbers as $cell_number) {
            			 $sheet->getStyle($cell_number.$i)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
            		     $sheet->getStyle($cell_number.$i)->getFill()->getStartColor()->setARGB('FF87CEFA');
            		}
            		$sheet->setCellValue("R{$i}", "陆运价格与空运价格一致");
            	}
            	
                $i++;
            }
        }
        if (!empty($this->error_insurance_list)) {
        	//var_dump($this->error_insurance_list);
        	if ($sheet_no == 1) {
        		$name = '$sheet';
        		$name = $excel->getActiveSheet();
        	} else {
        		$name = '$sheet'.$sheet_no;
        		$name = $excel->createSheet();
        		 
        	}
        	$sheet_no++;
        	$name->setTitle('保价费多收');
        	$name->setCellValue('A1', "日期");
			$name->setCellValue('B1', "运单号码");
			$name->setCellValue('C1', "订单号");
			$name->setCellValue('D1', "淘宝订单号");
			$name->setCellValue('E1', "收款方式");
			$name->setCellValue('F1', "组织");
			$name->setCellValue('G1', "仓库");
			$name->setCellValue('H1', "姓名");
			$name->setCellValue('I1', "省");
			$name->setCellValue('J1', "市及地区");
			$name->setCellValue('K1', "仓库称重重量(kg)");
			$name->setCellValue('L1', "快递公司保价费");
			$name->setCellValue('M1', "ERP计算保价费");
			$name->setCellValue('N1', "保价费差值");
			$name->setCellValue('O1', "保价费备注");
        	$i = 2;
        	foreach ($this->error_insurance_list as $order) {

    			$name->setCellValue("A{$i}", $order['shipping_time']);
    			$name->setCellValueExplicit("B{$i}", $order['tracking_number'], PHPExcel_Cell_DataType::TYPE_STRING);
    			$name->setCellValueExplicit("C{$i}", $order['order_sn'], PHPExcel_Cell_DataType::TYPE_STRING);
    			$name->setCellValueExplicit("D{$i}", $order['taobao_order_sn'], PHPExcel_Cell_DataType::TYPE_STRING);
    			$name->setCellValue("E{$i}", $order['pay_name']);
    			$name->setCellValue("F{$i}", $order['name']);
    			$name->setCellValue("G{$i}", $order['facility_name']);
    			$name->setCellValue("H{$i}", $order['addr']['consignee']);
    			$name->setCellValue("I{$i}", $order['addr']['province_name']);
    			$name->setCellValue("J{$i}", $order['addr']['city_name'].$order['addr']['district_name']);
    			$name->setCellValue("K{$i}", $order['shipping_leqee_weight']/1000);
    			$name->setCellValue("L{$i}", $order['excel_row']['excel_insurance']);
    			$name->setCellValue("M{$i}", $order['insurance']);
    			$name->setCellValue("N{$i}", $order['insurance_diff']);
    			$name->setCellValue("O{$i}", $order['insurance_note']);
        			
        		// 目前还没找到设置整行颜色的方法，先这样循环设置
            	if($order['is_discount_shunfeng']) {
            		$cell_numbers = array_slice($cell_nos,0,15);
            		foreach($cell_numbers as $cell_number) {
            			 $name->getStyle($cell_number.$i)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
            		     $name->getStyle($cell_number.$i)->getFill()->getStartColor()->setARGB('FF87CEFA');
            		}
            		$name->setCellValue("P{$i}", "陆运价格与空运价格一致");
            	}
        		$i++;
        	}
        
        }
        if (!empty($this->missing_carriage_list)) {
            //var_dump($this->missing_carriage_list);
            if ($sheet_no == 1) {
                $name = '$sheet';
                $name = $excel->getActiveSheet();
            } else {
                $name = '$sheet'.$sheet_no;
                $name = $excel->createSheet();
                 
            }
            $sheet_no++;
            $name->setTitle('订单地区未设置运费');
            $name->setCellValue('A1', "日期");
			$name->setCellValue('B1', "运单号码");
			$name->setCellValue('C1', "订单号");
			$name->setCellValue('D1', "淘宝订单号");
			$name->setCellValue('E1', "收款方式");
			$name->setCellValue('F1', "组织");
			$name->setCellValue('G1', "仓库/办公件起始地址");
			$name->setCellValue('H1', "姓名");
			$name->setCellValue('I1', "省");
			$name->setCellValue('J1', "市及地区");
			$name->setCellValue('K1', "重量");
			$name->setCellValue('L1', "仓库称重重量(kg)");
			$name->setCellValue('M1', "快递公司快递费");
			$name->setCellValue('N1', "备注");
            $i = 2;
            foreach ($this->missing_carriage_list as $order) {
            	
            	if ($order['excel_row_note'] == '办公件') {
            		$name->setCellValue("A{$i}", $order['shipping_date']);
            		$name->setCellValueExplicit("B{$i}", $order['tracking_number'], PHPExcel_Cell_DataType::TYPE_STRING);
            		$name->setCellValue("C{$i}", '');
            		$name->setCellValue("D{$i}", '');
            		$name->setCellValue("E{$i}", '');
            		$name->setCellValue("F{$i}", $order['name']);
            		$name->setCellValue("G{$i}", $order['start_province_name'].$order['start_city_name'].$order['start_district_name']);
            		$name->setCellValue("H{$i}", '');
            		$name->setCellValue("I{$i}", $order['end_province_name']);
            		$name->setCellValue("J{$i}", $order['end_city_name'].$order['end_district_name']);
            		$name->setCellValue("K{$i}", $order['weight']);
            		$name->setCellValue("L{$i}", $order['shipping_leqee_weight']/1000);
            		$name->setCellValue("M{$i}", $order['excel_row']['final_fee']);
            		$name->setCellValue("N{$i}", $order['note']."（办公件 ）");
            	} else {
            		$name->setCellValue("A{$i}", $order['shipping_time']);
            		$name->setCellValueExplicit("B{$i}", $order['tracking_number'], PHPExcel_Cell_DataType::TYPE_STRING);
            		$name->setCellValueExplicit("C{$i}", $order['order_sn'], PHPExcel_Cell_DataType::TYPE_STRING);
            		$name->setCellValueExplicit("D{$i}", $order['taobao_order_sn'], PHPExcel_Cell_DataType::TYPE_STRING);
            		$name->setCellValue("E{$i}", $order['pay_name']);
            		$name->setCellValue("F{$i}", $order['name']);
            		$name->setCellValue("G{$i}", $order['facility_name']);
            		$name->setCellValue("H{$i}", $order['addr']['consignee']);
            		$name->setCellValue("I{$i}", $order['addr']['province_name']);
            		$name->setCellValue("J{$i}", $order['addr']['city_name'].$order['addr']['district_name']);
            		$name->setCellValue("K{$i}", $order['excel_row']['weight']);
            		$name->setCellValue("L{$i}", $order['shipping_leqee_weight']/1000);
            		$name->setCellValue("M{$i}", $order['excel_row']['final_fee']);
            	}
            	
            	// 目前还没找到设置整行颜色的方法，先这样循环设置
            	if($order['is_discount_shunfeng']) {
            		$cell_numbers = array_slice($cell_nos,0,14);
            		foreach($cell_numbers as $cell_number) {
            			 $name->getStyle($cell_number.$i)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
            		     $name->getStyle($cell_number.$i)->getFill()->getStartColor()->setARGB('FF87CEFA');
            		}
            		$name->setCellValue("O{$i}", "陆运价格与空运价格一致");
            	}
                $i++;
            }

        }
        if (!empty($this->error_match_list)) {
            //var_dump($this->missing_carriage_list);
            if ($sheet_no == 1) {
                $name = '$sheet';
                $name = $excel->getActiveSheet();
            } else {
                $name = '$sheet'.$sheet_no;
                $name = $excel->createSheet();
                 
            }
            $sheet_no++;
            $name->setTitle('订单快递与所选择快递不一致');
            $name->setCellValue('A1', "运单快递方式");
			$name->setCellValue('B1', "日期");
			$name->setCellValue('C1', "运单号码");
			$name->setCellValue('D1', "订单号");
			$name->setCellValue('E1', "淘宝订单号");
			$name->setCellValue('F1', "收款方式");
			$name->setCellValue('G1', "组织");
			$name->setCellValue('H1', "仓库");
			$name->setCellValue('I1', "姓名");
			$name->setCellValue('J1', "省");
			$name->setCellValue('K1', "市及地区");
			$name->setCellValue('L1', "重量");
			$name->setCellValue('M1', "仓库称重重量(kg)");
			$name->setCellValue('N1', "快递公司快递费");
			$name->setCellValue('O1', "备注");
            $i = 2;
            foreach ($this->error_match_list as $order) {
            	
                $name->setCellValue("A{$i}", $order['shipping_name']);
                
                $name->setCellValueExplicit("C{$i}", $order['tracking_number'], PHPExcel_Cell_DataType::TYPE_STRING);
                $name->setCellValueExplicit("D{$i}", $order['order_sn'], PHPExcel_Cell_DataType::TYPE_STRING);
                $name->setCellValueExplicit("E{$i}", $order['taobao_order_sn'], PHPExcel_Cell_DataType::TYPE_STRING);
                $name->setCellValue("F{$i}", $order['pay_name']);
                $name->setCellValue("G{$i}", $order['name']);
                if ($order['excel_row_note'] == '办公件') {
                	$name->setCellValue("B{$i}", $order['shipping_date']);
                	$name->setCellValue("H{$i}", $order['start_province_name'].$order['start_city_name'].$order['start_district_name']);
	                $name->setCellValue("I{$i}", '');
	                $name->setCellValue("J{$i}", $order['end_province_name']);
	                $name->setCellValue("K{$i}", $order['end_city_name'].$order['end_district_name']);
	                $name->setCellValue("O{$i}", $order['note']);
                } else {
                	$name->setCellValue("B{$i}", $order['shipping_time']);
	                $name->setCellValue("H{$i}", $order['facility_name']);
	                $name->setCellValue("I{$i}", $order['addr']['consignee']);
	                $name->setCellValue("J{$i}", $order['addr']['province_name']);
	                $name->setCellValue("K{$i}", $order['addr']['city_name'].$order['addr']['district_name']);
                }
                $name->setCellValue("L{$i}", $order['excel_row']['weight']);
                $name->setCellValue("M{$i}", $order['shipping_leqee_weight']/1000);
                $name->setCellValue("N{$i}", $order['excel_row']['final_fee']);
                
                // 目前还没找到设置整行颜色的方法，先这样循环设置
            	if($order['is_discount_shunfeng']) {
            		$cell_numbers = array_slice($cell_nos,0,15);
            		foreach($cell_numbers as $cell_number) {
            			 $name->getStyle($cell_number.$i)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
            		     $name->getStyle($cell_number.$i)->getFill()->getStartColor()->setARGB('FF87CEFA');
            		}
            		$name->setCellValue("P{$i}", "陆运价格与空运价格一致");
            	}
                $i++;
            }
        
        }
            if (!empty($this->error_weight)) {
            //var_dump($this->error_weight);die();
            if ($sheet_no == 1) {
                $name = '$sheet';
                $name = $excel->getActiveSheet();
            } else {
                $name = '$sheet'.$sheet_no;
                $name = $excel->createSheet();
                 
            }
            $sheet_no++;
            $name->setTitle('快递公司重量异常');
            $name->setCellValue('A1', "日期");
            $name->setCellValue('B1', "运单号码");
            $name->setCellValue('C1', "订单号");
            $name->setCellValue('D1', "淘宝订单号");
            $name->setCellValue('E1', "收款方式");
            $name->setCellValue('F1', "组织");
            $name->setCellValue('G1', "仓库");
            $name->setCellValue('H1', "运单快递方式");
            $name->setCellValue('I1', "省");
            $name->setCellValue('J1', "市/区");
            $name->setCellValue('K1', "保价费");
            $name->setCellValue('L1', "快递公司重量(kg)");
            $name->setCellValue('M1', "仓库称重重量(kg)");
            $name->setCellValue('N1', "快递公司重量运费");
            $name->setCellValue('O1', "快递公司实收快递费");
            $name->setCellValue('P1', "仓库称重运费");
            $name->setCellValue('Q1', "快递费差值");
            $name->setCellValue('R1', "办公件发出地");
            $name->setCellValue('S1', "办公件目的省份");
            $name->setCellValue('T1', "办公件目的城市");
            $name->setCellValue('U1', "注释");
            $i = 2;
            foreach ($this->error_weight as $order) {

            	$name->setCellValueExplicit("B{$i}", $order['tracking_number'], PHPExcel_Cell_DataType::TYPE_STRING);
            	$name->setCellValueExplicit("C{$i}", $order['order_sn'], PHPExcel_Cell_DataType::TYPE_STRING);
            	$name->setCellValueExplicit("D{$i}", $order['taobao_order_sn'], PHPExcel_Cell_DataType::TYPE_STRING);
            	$name->setCellValue("E{$i}", $order['pay_name']);
            	$name->setCellValue("F{$i}", $order['name']);
            	$name->setCellValue("H{$i}", $order['shipping_name']);
            	if ($order['excel_row_note'] == '办公件') {
            		$name->setCellValue("A{$i}", $order['shipping_date']);
            		$name->setCellValue("L{$i}", $order['excel_row']['weight']);
            		$name->setCellValue("M{$i}", $order['weight']);
            		$name->setCellValue("N{$i}", round((double)$order['office_calculated_fee'],2));
            		$name->setCellValue("O{$i}", $order['excel_row']['final_fee']);
            		$name->setCellValue("P{$i}", round((double)$order['office_shipping_fee'],2));
            		$name->setCellValue("R{$i}", $order['start_province_name'].$order['start_city_name'].$order['start_district_name']);
            		$name->setCellValue("S{$i}", $order['end_province_name']);
            		$name->setCellValue("T{$i}", $order['end_city_name'].$order['end_district_name']);
            		$name->setCellValue("U{$i}", $order['note']."（办公件）");
            	} else {
            		$name->setCellValue("A{$i}", $order['shipping_time']);
            		$name->setCellValue("G{$i}", $order['facility_name']);
            		$name->setCellValue("I{$i}", $order['addr']['province_name']);
            		$name->setCellValue("J{$i}", $order['addr']['city_name'].$order['addr']['district_name']);
            		$name->setCellValue("K{$i}",  $order['excel_row']['excel_insurance']);
            		$name->setCellValue("L{$i}", $order['excel_row']['weight']);
            		if ( $order['shipping_leqee_weight'] != null && $order['shipping_leqee_weight'] != 0) {
            			$name->setCellValue("M{$i}", $order['shipping_leqee_weight']/1000);
            		}else {
            			$name->getStyle("M{$i}")->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
            			$name->getStyle("M{$i}")->getFill()->getStartColor()->setARGB('FFFF0000');
            			$name->setCellValue("M{$i}", $order['estimate_weight']/1000);
            		}
            		$name->setCellValue("N{$i}", round((double)$order['calculated_fee'],2));
            		$name->setCellValue("O{$i}", $order['excel_row']['final_fee']);
            		$name->setCellValue("P{$i}", round((double)$order['shipping_fee'],2));
            		$name->setCellValue("Q{$i}", ($order['excel_row']['final_fee']-round((double)$order['shipping_fee'],2)));
            	}
            	// 目前还没找到设置整行颜色的方法，先这样循环设置
            	if($order['is_discount_shunfeng']) {
            		$cell_numbers = array_slice($cell_nos,0,22);
            		foreach($cell_numbers as $cell_number) {
            			 $name->getStyle($cell_number.$i)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
            		     $name->getStyle($cell_number.$i)->getFill()->getStartColor()->setARGB('FF87CEFA');
            		}
            		$name->setCellValue("V{$i}", "陆运价格与空运价格一致");
            	}
                $i++;
            }
        }
        if (!empty($this->missing_order_list)) {
            //var_dump($this->missing_order_list);die();
            $no = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
            if ($sheet_no == 1 ) {
                $name = '$sheet';
                $name = $excel->getActiveSheet();
            } else {
                $name = '$sheet'.$sheet_no;
                $name = $excel->createSheet();
            }
            $sheet_no++;
            $name->setTitle('未找到对应订单');
            $i = 1;
            $j = 0;
            foreach ($this->missing_order_list as $order) {
                foreach ($order as $key => $col) {
                    foreach ($this->tpls as $k => $var_name) {
                        if ($k == $key) {
                            $name->setCellValue("{$no[$j]}{$i}", $var_name);
                            $j++;
                        }
                    }
                }
                break;
            }
            $j = 0;
            foreach ($this->missing_order_list as $order) {
                $i++;
                $j = 0;
                foreach ($order as $k => $col){
                    if  ($k == 'tracking_number' ) {
                        $name->setCellValueExplicit("{$no[$j]}{$i}", $col, PHPExcel_Cell_DataType::TYPE_STRING);
                    } else {
                        $name->setCellValue("{$no[$j]}{$i}", $col);
                    }
                    $j++;
                }
            }
        }
        if (!empty($this->order_list)) {
            if ($sheet_no == 1 ) {
                $name = '$sheet';
                $name = $excel->getActiveSheet();
            } else {
                $name = '$sheet'.$sheet_no;
                $name = $excel->createSheet();
            }
            $sheet_no++;
            $name->setTitle('运单-订单列表');
			$name->setCellValue('A1', "日期");
			$name->setCellValue('B1', "运单号码");
			$name->setCellValue('C1', "订单号");
			$name->setCellValue('D1', "淘宝订单号");
			$name->setCellValue('E1', "收款方式");
			$name->setCellValue('F1', "组织");
			$name->setCellValue('G1', "仓库");
			$name->setCellValue('H1', "快递方式");
			$name->setCellValue('I1', "重量");
			$name->setCellValue('J1', "仓库称重重量(kg)");
			$name->setCellValue('K1', "金额");
			$name->setCellValue('L1', "省");
			$name->setCellValue('M1', "市/区");
			$name->setCellValue('N1', "保价费");
            $i = 2;
            foreach ($this->order_list as $order) {
                $name->setCellValueExplicit("B{$i}", $order['tracking_number'], PHPExcel_Cell_DataType::TYPE_STRING);
                $name->setCellValueExplicit("C{$i}", $order['order_sn'], PHPExcel_Cell_DataType::TYPE_STRING);
                $name->setCellValueExplicit("D{$i}", $order['taobao_order_sn'], PHPExcel_Cell_DataType::TYPE_STRING);
                $name->setCellValue("E{$i}", $order['pay_name']);
                $name->setCellValue("F{$i}", $order['name']);
                $name->setCellValue("H{$i}", $order['shipping_name']);
                if ($order['excel_row_note'] == '办公件') {
					$name->setCellValue("A{$i}", $order['shipping_date']);
					$name->setCellValue("G{$i}", $order['start_province_name'] . $order['start_city_name'] . $order['start_district_name']);
					$name->setCellValue("I{$i}", $order['excel_row']['weight']);
					$name->setCellValue("J{$i}", $order['shipping_leqee_weight']/1000);
					$name->setCellValue("K{$i}", $order['excel_row']['final_fee']);
					$name->setCellValue("L{$i}", $order['end_province_name']);
					$name->setCellValue("M{$i}", $order['end_city_name'] . $order['end_district_name']);
				} else {
					$name->setCellValue("A{$i}", $order['shipping_time']);
					$name->setCellValue("G{$i}", $order['facility_name']);
					$name->setCellValue("I{$i}", $order['excel_row']['weight']);
					$name->setCellValue("J{$i}", $order['shipping_leqee_weight']/1000);
					$name->setCellValue("K{$i}", $order['excel_row']['final_fee']);
					$name->setCellValue("L{$i}", $order['addr']['province_name']);
					$name->setCellValue("M{$i}", $order['addr']['city_name'] . $order['addr']['district_name']);
					$name->setCellValue("N{$i}", $order['excel_row']['excel_insurance']);
				}
                // 目前还没找到设置整行颜色的方法，先这样循环设置
            	if($order['is_discount_shunfeng']) {
            		$cell_numbers = array_slice($cell_nos,0,14);
            		foreach($cell_numbers as $cell_number) {
            			 $name->getStyle($cell_number.$i)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
            		     $name->getStyle($cell_number.$i)->getFill()->getStartColor()->setARGB('FF87CEFA');
            		}
            		$name->setCellValue("O{$i}", "陆运价格与空运价格一致");
            	}
                $i++;
            }
        }
        if (!headers_sent()) {
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="'.$file_name.'.xls"');
            header('Cache-Control: max-age=0');
            $output = PHPExcel_IOFactory::createWriter($excel, 'Excel5');
            $output->save('php://output');
            exit;
        }
    }


}
