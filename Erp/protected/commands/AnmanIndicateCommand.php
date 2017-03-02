<?php

define('IN_ECS', true);
define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'admin/includes/init.php';
require_once ROOT_PATH . 'data/master_config.php';
require_once ROOT_PATH . 'includes/helper/mail.php';
require_once(ROOT_PATH.'RomeoApi/lib_soap.php');
require_once(ROOT_PATH.'RomeoApi/lib_RMATrack.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once ROOT_PATH . 'admin/includes/lib_service.php';
include_once ROOT_PATH . 'admin/function.php';
 

class AnmanIndicateCommand extends CConsoleCommand {
	
    private $master; // Master数据库    
    
    /**
     * 当不指定ActionName时的默认调用
     */
    public function actionIndex() {
    	
		//生成收货确认数据
		$this->run(array('IndicateInventoryOutCreate'));
		
		//推送已经生成好的数据
		$this->run(array('ReturnOrders'));
    }
    
     /**
     * 生成收货确认数据
     */
    public function actionIndicateInventoryOutCreate($start_date = null, $end_date = null) {
    	$this->log("begin IndicateInventoryOutCreate");
    	$start = microtime(true);
    	$start_date = $start_date==null ? date('Y-m-d 00:00:00', strtotime("-1 day")) : $start_date;
    	$end_date = $end_date==null ? date("Y-m-d 00:00:00", time()) : $end_date;
        $sql = "
			select maxRecId.rightTime,eoi.order_id, if(oa.attr_value is not null, oa.attr_value, '') taobao_user_id, eoi.goods_amount + eoi.bonus goods_amount, eoi.consignee, eoi.mobile, eoi.email, 
					eoi.province, eoi.city, eoi.district, eoi.address, eoi.zipcode,
					eg.barcode, eg.goods_name, 
				case when t.a=1 then 1 when t.a=2 then eog.goods_number-1 else eog.goods_number end quantity,
				case when t.a=1 then eoi.goods_amount + eoi.bonus 
				        - ifnull((select sum(cast(eog3.goods_price*if(eoi.goods_amount=0,0,(eoi.goods_amount + eoi.bonus)/maxRecId.order_goods_amount) as decimal(10,2))
													*if(eog3.rec_id=maxRecId.max_rec_id, eog3.goods_number - 1,eog3.goods_number)) 
						            from ecshop.ecs_order_goods eog3 
									inner join ecshop.ecs_goods eg3 on eg3.goods_id = eog3.goods_id
									where eog3.order_id=eoi.order_id),
							     0)
				     else cast(eog.goods_price*if(eoi.goods_amount=0,0,(eoi.goods_amount + eoi.bonus)/maxRecId.order_goods_amount) as decimal(10,2))
				end price, eog.rec_id, eog.goods_id
			from ecshop.ecs_order_info eoi
			 inner join ecshop.ecs_order_goods eog on eoi.order_id = eog.order_id
			 inner join ( select tall.order_id,max(eog21.rec_id) max_rec_id,tall.rightTime,
			                     sum(eog21.goods_price*eog21.goods_number) as order_goods_amount
			                from
			                  (select t1.order_id,t1.action_time as rightTime 
			                     from ( select eoi.order_id,TIMESTAMP(min(eoa.action_time)) action_time
			                              from ecshop.ecs_order_info eoi 
					                     inner join ecshop.ecs_order_action eoa on eoa.order_id = eoi.order_id 
					                     left join ecshop.ecs_indicate ei on eoi.order_id = ei.order_id and ei.indicate_type = 'INVENTORY_OUT'
					                     where eoi.order_type_id = 'SALE' and eoi.party_id = '65569' and eoi.distributor_id = '448' 
					                        and ((eoi.order_status = '1' and eoi.shipping_status = '2' ) or eoi.order_status in ('2','4'))
					                        and ei.indicate_id is null group by eoi.order_id  ) t1                
			                   where t1.action_time >= '{$start_date}' and t1.action_time < '{$end_date}'                    
			                  ) tall
						    inner join ecshop.ecs_order_goods eog21 on tall.order_id=eog21.order_id
							inner join ecshop.ecs_goods eg22 on eog21.goods_id = eg22.goods_id
						    group by tall.order_id
			            ) maxRecId on eog.order_id = maxRecId.order_id
			inner join ( select 0 as a union all select 1 as a union all select 2 as a ) t 
			   on (t.a=0 and eog.rec_id <> maxRecId.max_rec_id 
			       or t.a=1 and eog.rec_id = maxRecId.max_rec_id 
			       or t.a=2 and eog.rec_id = maxRecId.max_rec_id and eog.goods_number > 1 )
			    inner join ecshop.ecs_goods eg on eg.goods_id = eog.goods_id
            left join ecshop.order_attribute oa on eoi.order_id = oa.order_id and oa.attr_name = 'TAOBAO_USER_ID'
		where eg.goods_id in (130900, 130901, 184873, 184895)
		";
		var_dump($sql);
		$orders = $this->getMaster()->createCommand ($sql)->queryAll();
		if (! $orders) {
			$this->log("没有订单数据!");
			return ;
		}
		$orders = Helper_Array::groupBy($orders, 'order_id');
		$countAll = count($orders);
		$success_count = 0;
		foreach ($orders as $order) {
			$sql = "insert into ecshop.ecs_indicate 
			            (party_id, order_id, order_time, taobao_user_id,  goods_amount, consignee, 
			             mobile, email, province, city, district, address, 
			             indicate_type, indicate_media, indicate_status, created_stamp, last_update_stamp)
			        values 
			           (65569, {$order[0]['order_id']}, '{$order[0]['rightTime']}', '{$order[0]['taobao_user_id']}', {$order[0]['goods_amount']}, '{$order[0]['consignee']}', 
			            '{$order[0]['mobile']}', '{$order[0]['email']}', {$order[0]['province']}, {$order[0]['city']}, {$order[0]['district']}, '{$order[0]['address']}', 
			            'INVENTORY_OUT', 'TXT', 'INIT', now(), now())
			";
			var_dump($sql);
			$this->getMaster()->createCommand ($sql)->execute();
			$sql = "select indicate_id from ecshop.ecs_indicate where order_id = {$order[0]['order_id']} limit 1 ";
			$result = $this->getMaster()->createCommand ($sql)->queryAll();
			$indicate_id = $result[0]['indicate_id'];
			foreach ($order as $order_item) {
				$sql = "insert into ecshop.ecs_indicate_detail 
				         (indicate_id, order_goods_id, goods_id, barcode, goods_name, goods_number, goods_price)
				        values 
				          ({$indicate_id}, {$order_item['rec_id']}, {$order_item['goods_id']}, '{$order_item['barcode']}', '{$order_item['goods_name']}', {$order_item['quantity']}, {$order_item['price']})
				";
				$this->getMaster()->createCommand($sql)->execute();
			}
			$success_count ++;
		}
		
		$this->log("共: {$countAll}, 成功: {$success_count}, " . "耗时: " . (microtime(true)-$start));
		$this->log("end IndicateInventoryOutCreate");
    }
    
    public function actionReturnOrders( $start_date=null, $end_date=null){
    	$this->log("begin ReturnOrders");
    	$start = microtime(true);
    	$start_date = $start_date==null ? date('Y-m-d 00:00:00', strtotime("-1 day")) : $start_date;
    	$end_date = $end_date==null ? date("Y-m-d 00:00:00", time()) : $end_date;
//        $sql = "SELECT ei1.indicate_id, ei1.taobao_user_id, ei1.order_time, ei1.order_id, NULL AS eddBd, ei1.goods_amount, ei1.consignee, ei1.mobile, ei1.email, 
//				      ei1.province,
//				      IF(ei1.city IS NOT NULL, ei1.city, rr.brand_anmum_region_id ) AS city,
//				      IF(ei1.district IS NULL AND ei1.anmum_region_type != 2, rr1.brand_anmum_region_id, ei1.district) AS district,
//				      ei1.address, ei1.zipcode, ei1.transactionStatus
//				FROM (
//					SELECT ei.indicate_id, ei.taobao_user_id, ei.order_time, ei.order_id, NULL AS eddBd, ei.goods_amount, ei.consignee, ei.mobile, ei.email, 
//				      r.brand_anmum_region_id AS province, 
//				      IF(ei.province IN (2,3,10,23),r3.brand_anmum_region_id,IF(r2.anmum_region_type = 2,r2.brand_anmum_region_id,r1.brand_anmum_region_id)) AS city,
//				      IF(ei.province IN (2,3,10,23),r1.brand_anmum_region_id,IF(r2.anmum_region_type = 2,'',r2.brand_anmum_region_id)) AS district, r2.anmum_region_type, 
//				      ei.address, ei.zipcode,
//				      IF(eoi.order_status IN ('2','4'),'02',IF(eoi.order_status='1' AND eoi.shipping_status='2','01','')) AS transactionStatus
//					FROM ecshop.ecs_indicate ei 
//				  LEFT JOIN ecshop.ecs_order_info eoi ON ei.order_id = eoi.order_id
//					LEFT JOIN ecshop.brand_anmum_region r ON ei.province = r.erp_region_id AND r.anmum_region_type = 1 
//				  LEFT JOIN ecshop.brand_anmum_region r3 ON ei.province = r3.erp_region_id AND r3.anmum_region_type = 2
//					LEFT JOIN ecshop.brand_anmum_region r1 ON ei.city = r1.erp_region_id
//					LEFT JOIN ecshop.brand_anmum_region r2 ON ei.district = r2.erp_region_id 
//					WHERE ei.party_id = 65569 and ei.indicate_status = 'INIT' and ei.order_time >= '$start_date' and ei.order_time < '$end_date') as ei1
//				LEFT JOIN ecshop.brand_anmum_region rr ON ei1.province = rr.anmum_parent_id AND rr.erp_region_id > 10000
//				LEFT JOIN ecshop.brand_anmum_region rr1 ON ei1.city = rr1.anmum_parent_id AND rr1.erp_region_id > 10000
//				GROUP BY ei1.order_id";
        $sql = "SELECT ei.indicate_id, IF(ei.taobao_user_id IS NOT NULL,ei.taobao_user_id,'') AS taobao_user_id, ei.order_time, ei.order_id, '' AS eddBd, ei.goods_amount, ei.consignee, 
				     ei.mobile, IF(ei.email IS NOT NULL ,ei.email,'') AS email, 
						(SELECT region_name FROM ecshop.ecs_region WHERE region_id = ei.province) AS province, 
						(SELECT region_name FROM ecshop.ecs_region WHERE region_id = ei.city) AS city,
						IF(r.region_name IS NOT NULL,r.region_name,'') AS district,
						ei.address, IF(ei.zipcode IS NOT NULL,ei.zipcode,'') AS zipcode,
						IF(eoi.order_status IN ('2','4'),'02',IF(eoi.order_status='1' AND eoi.shipping_status='2','01','')) AS transactionStatus
				FROM ecshop.ecs_indicate ei 
				LEFT JOIN ecshop.ecs_order_info eoi ON ei.order_id = eoi.order_id
				LEFT JOIN ecshop.ecs_region r ON ei.district = r.region_id
				WHERE ei.party_id = 65569 and ei.indicate_status = 'INIT' AND ei.mobile IS NOT NULL AND ei.mobile != '' and ei.order_time >= '$start_date' and ei.order_time < '$end_date'";
    	var_dump($sql);
    	$orders = $this->getMaster()->createCommand($sql)->queryAll();
    	$orders = Helper_Array::groupBy($orders, 'order_id');
		$countAll = count($orders);
		$success_count = 0;
		$fail_order_ids = "";
		if(!$orders){
			$this->log("今天没有订单推送");
			return;
		}
		foreach ($orders as $order) {
			$send_result=$this->sendOrder($order);
			$status = "";
			if($send_result>0){
				$success_count++;
				$status = true;
				
			}else if($send_result == -1){
				$status = false;
				$this->log("订单号：".$order[0]['order_id']."权限验证失败");
				$fail_order_ids.=$order[0]['order_id'].",";
			}else if($send_result == -2){
				$status = false;
				$this->log("订单号：".$order[0]['order_id']."推送发生异常");
				$fail_order_ids.=$order[0]['order_id'].",";
			}else if($send_result == -3){
				$status = false;
				$this->log("订单号：".$order[0]['order_id']."订单号重复，已经推送过了");
				$fail_order_ids.=$order[0]['order_id'].",";
			}else{
				$this->log("订单号：".$order[0]['order_id']."异常，返回结果为$send_result");
			}
			
			//接口调用成功，需要更新状态为FINISH，将finished_stamp，last_update_stamp更新，
			//接口调用失败，需要更新状态为FAILURE，将last_update_stamp更新，
			//return_result 保存返回的结果
			if($status){
				$sql = "update ecshop.ecs_indicate set indicate_status = 'FINISH',finished_stamp=now(),last_update_stamp = now(),return_result='{$send_result}' where order_id  = '{$order[0]['order_id']}'"; 
				$this->getMaster()->createCommand($sql)->execute();
			}else{
				$sql = "update ecshop.ecs_indicate set last_update_stamp = now(),return_result='{$send_result}' where order_id  = '{$order[0]['order_id']}'"; 
				$this->getMaster()->createCommand($sql)->execute();
			}
		}
		//当有失败的订单时，将失败的订单，通过邮件的方式通知到以下相关人员
		if($fail_order_ids){
			 try {
                 $mail = Yii::app ()->getComponent ('mail');
                    
                 $mail->Subject = "【ANMUM对接】订单推送到ANMUM组织出错报告".date('Y-m-d H:i');
                    
                 $mail->Body="以下订单推送发生错误，请查核：$fail_order_ids";
                    
                 $mail->ClearAddresses ();
	             $mail->AddAddress("mjzhou@i9i8.com","周明杰");
	             $mail->AddAddress("qxu@leqee.com","许强");
	             $mail->AddAddress("jwang@i9i8.com","王健");
                 $mail->AddAddress("hyzhou1@leqee.com","周涵英");
	                
                 $mail->send ();
             } catch ( Exception $e ) {
                 var_dump ( "发送邮件异常" );
             }
		}
		$this->log("共: {$countAll}, 成功: {$success_count}, " . "耗时: " . (microtime(true)-$start));
		$this->log("end ReturnOrders");
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
    
    private function log ($m) {
		print date("Y-m-d H:i:s") . " " . $m . "\r\n";
    }
    
    //调用WEBSERVICE发送数据的方法，返回发送的结果，
    // 结果为 -1，-2，-3或者ANMUM的订单号。
    // -1表示为：权限不正确
    // -2表示为：调用接口ANMUM发生异常
    // -3表示为：改订单已经推送过
    //  如果是一个正整数，则表示推送成功，返回了ANMUM的订单号
    private function sendOrder($order){
    	global $ANMANURL;
    	global $ANMANLOCATION;
    	global $ANMAMUSERNAME;
    	global $ANMUMPASSWORD;
//    	$url ="http://58.246.200.82:8081/WebPage/Interface/TMall.asmx?WSDL";
//    	$url = "http://180.166.150.26:8081/tmallinterface/interface/tmall.asmx?WSDL";
    	$client = new SoapClient($ANMANURL, array('soap_version' => SOAP_1_1,'trace' =>true));
		$h = new SoapHeader('http://tempuri.org/','SoapHeaderValidate', array('UserName'=>$ANMAMUSERNAME,'Password'=>$ANMUMPASSWORD),false);
		$client->__setSoapHeaders(array($h));
		// 这个地方是限定端口，不加这句话，会导致WEBSERVICE接口报HTTP NOT FOUND的错误，因为找不到端口号，数据无法传输
//		$client->__setLocation('http://58.246.200.82:8081/WebPage/Interface/TMall.asmx');
		$client->__setLocation($ANMANLOCATION);
		$client->soap_defencoding='utf-8';
		$client->decode_utf8=false;
		$client->xml_encoding='utf-8';
		//var_dump($order);
    	try {
    		$sql = "select * from ecshop.ecs_indicate_detail where indicate_id = '{$order[0]['indicate_id']}'";
    		var_dump($sql);
    		$order_goods = $this->getMaster()->createCommand($sql)->queryALL();
    		//查询出所有商品，将商品一个一个的添加到LIST上
    		if(!$order_goods){
    			$this->log("订单号：".$order['order_id']."订单商品为空");
    			return false;
    		}
    		$order_goods_list = array();
    		foreach ($order_goods as $order_good) {
    			$order_good_item = array("Quantity"=> $order_good['goods_number'],"ProductPrice"=>$order_good['goods_price'],'ProductCode'=>$order_good['barcode']);
    			if(in_array($order_good['goods_id'], array(130900, 184873))){
    				$order_good_item['ProductCode'] = 'S0 tin';
    			}else if(in_array($order_good['goods_id'], array(130901, 184895))){
    				$order_good_item['ProductCode'] = 'S0 bag';
    			}
    			$order_goods_list[] = $order_good_item;
    		}
    		$return_order = array();
    		$return_order['taobaoId'] = $order[0]['taobao_user_id'];
    		$return_order['purchaseDate'] = date ('c' ,$this->str2time($order[0]['order_time']));
    		$return_order['orderNo'] = $order[0]['order_id'];
    		$return_order['eddBd'] = $order[0]['eddBd'];
    		$return_order['orderAmount'] = $order[0]['goods_amount'];
    		$return_order['consumerName'] = $order[0]['consignee'];
    		$return_order['mobile'] = $order[0]['mobile'];
    		$return_order['email'] = $order[0]['email'];
    		$return_order['province'] = $order[0]['province'];
    		$return_order['city'] = $order[0]['city'];
    		$return_order['district'] = $order[0]['district'];
    		$return_order['address'] = $order[0]['address'];
    		$return_order['postalCode'] = $order[0]['zipcode'];
    		$return_order['transactionStatus'] = $order[0]['transactionStatus'];
    		$return_order['products'] = $order_goods_list;
    		
    		
    		// 发送数据时，先更新数据的sended_stamp,表示数据什么时候发送。
    		$sql = "update ecshop.ecs_indicate set indicate_status='SENDED',sended_stamp=now(),last_update_stamp = now() where order_id  = '{$order[0]['order_id']}' ";
			$this->getMaster()->createCommand($sql)->execute();
			
			var_dump($return_order);
			//发送数据，返回结果
    		$result = $client->ReciveTranscatoinFromTMALL($return_order);
			return $result->ReciveTranscatoinFromTMALLResult;
		}
		catch (SOAPFault $e) {
			$this->log("订单号：".$order['order_id']."发送失败");
			print $e;
		}
    }
    
    //将我们数据库查出来的时间字符串eg"2013-07-24" 转化为时间戳，方便在后面的操作过程中，转化为ANMUM接口方需要的时间参数
    private function str2time($str){
		$array = explode("-",$str);
		$year = $array[0];
		$month = $array[1];
		$array = explode(":",$array[2]);
		$minute = $array[1];
		$second = $array[2];
		$array = explode(" ",$array[0]);
		$day = $array[0];
		$hour = $array[1];
		$timestamp = mktime($hour,$minute,$second,$month,$day,$year);
		return $timestamp;
    }
}	
	

