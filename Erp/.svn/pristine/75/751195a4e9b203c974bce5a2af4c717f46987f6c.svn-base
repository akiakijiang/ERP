<?php
define('IN_ECS', true);
define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'admin/includes/init.php';
require_once ROOT_PATH . 'admin/includes/lib_crsms.php';
require_once ROOT_PATH . 'includes/lib_common.php';

class SendMessageCommand extends CConsoleCommand{
    public function actionIndex(){
        $this->run(array('RemindCustomeExpressage'));
        
        $this->run(array('EccoAfterSynNoonSendMsg'));
    }
    
    public function actionRemindCustomeExpressage(){
       $this->log("begin RemindCustomeExpressage");
       $start = time()-38*3600;
       $end = time()-14*3600;
       $sql = "SELECT 
                o.order_id,o.shipping_id, o.country, o.province, o.city, o.district, o.taobao_order_sn, o.order_sn, o.shipping_name,
                o.party_id, o.distributor_id, o.mobile, o.shipping_id, c.phone_no, c.name, o.address, 
                -- cb.bill_no, 
                s.tracking_number bill_no,
                p.is_cod, date_format(from_unixtime(o.shipping_time), '%d') as shipping_time
            FROM 
                ecshop.ecs_order_info o 
            -- LEFT JOIN 
            --     ecshop.ecs_carrier_bill cb ON o.carrier_bill_id = cb.bill_id
            LEFT JOIN
                romeo.order_shipment os ON os.order_id=convert(o.order_id using utf8)
            LEFT JOIN
                romeo.shipment s ON os.shipment_id=s.shipment_id
            LEFT JOIN 
                ecshop.ecs_carrier c ON s.carrier_id=c.carrier_id -- cb.carrier_id = c.carrier_id  
            LEFT JOIN 
                ecshop.ecs_payment p ON p.pay_id = o.pay_id 
            WHERE 
                o.party_id IN (65540,65547,65562,65558,65568,65569,65571,128,65553) AND
                   o.order_type_id= 'SALE' AND o.pay_id <> 1 AND 
                   o.order_status = 1 AND
                   o.shipping_time >= '{$start}' AND
                o.shipping_time < '{$end}'
                and s.status!='SHIPMENT_CANCELLED'                      
            GROUP BY
                   o.order_id
       ";
        $db = Yii::app()->getComponent('slave');
        $orders = $db->createCommand($sql)->queryAll();
        //短信内电话号码轮换
        $tel = array ('0571-28181308','0571-28290705','0571-28280632','0571-28280639');
        $tel_size = count($tel);
        $cod_order_index = 0;
        foreach ($orders as $order){
            $this->log("begin foreach order");
            if($order['party_id'] == '65547'){
                $tpl_name = "order_deliver";
                $msg_vars = array();
                
            } elseif ($order['party_id'] == '65540') {
                            $sql_delivery = "
                SELECT sa.configure FROM ecshop.ecs_shipping_area sa
                LEFT JOIN  ecshop.ecs_area_region ar ON sa.shipping_area_id = ar.shipping_area_id
                WHERE 
                    sa.shipping_id = '{$order['shipping_id']}' AND 
                    ar.region_id IN ('{$order['country']}', '{$order['province']}', '{$order['city']}', '{$order['district']}')
                ORDER BY
                    ar.region_id DESC
                ";
                $configures = $db->createCommand($sql_delivery)->queryScalar(); //获得寄送到该地区所需要的时间
                if ($configures != null) {             
                    $configures = unserialize($configures);
                    foreach ($configures as $key=>$configure) {
                        if ($configure['name'] == 'delivery_time') {
                            $delivery_time = $configure['value'];
                            break;
                        }
                        $delivery_time = '';
                    }
                } else {
                    $delivery_time = '';
                }
                $delivery_time = $delivery_time != '' ? "，预计{$delivery_time}天内送达":'';    
                if ($order['is_cod'] == '1'){
                    $cod_order_index++;
                    $tpl_name = "cod_auto_order_deliver";
                } else {
                    $tpl_name = "order_deliver";
                }
                
                // FIXME: 短信字数限制 更改短信中快递的名字为简写
                if ($order['shipping_id'] == '11') {
                    $order['shipping_name'] = '宅急送';
                }
                if ($order['shipping_id'] == '36') {
                    $order['shipping_name'] = 'EMS';
                }
                $msg_vars = array(
                    'msg_taobao_order_sn' => $order['taobao_order_sn'],
                    'msg_order_sn'        => $order['order_sn'], 
                    'msg_delivery_time'   => $delivery_time, 
                    'msg_order_name'      => $order['name'],
                    'msg_bill_no'         => $order['bill_no'],
                    'msg_phone_no'        => $tel[$cod_order_index % $tel_size],
                    'msg_shipping_name'   => $order['shipping_name'],
                );
            
            } elseif ($order['party_id'] == 65562){
                $tpl_name = "ecco_order_deliver2";
                $msg_vars = array(
                    'msg_taobao_order_sn' => $order['taobao_order_sn'],
                    'msg_shipping_name'   => $order['shipping_name'],
                    'msg_bill_no'         => $order['bill_no'],
                );
            }elseif ($order['party_id'] == 65558 && $order['distributor_id'] == 317){
                $tpl_name = "haoqi_order_deliver";
                $msg_vars = array (
                     'msg_shipping_name'   => $order['shipping_name'],
                     'msg_bill_no'         => $order['bill_no'],
                );
            }elseif ($order['party_id'] == 65558 && $order['distributor_id'] == 313){
                $tpl_name = "jinbaili_order_deliver";
                $msg_vars = array (
                     'msg_shipping_name'   => $order['shipping_name'],
                     'msg_bill_no'         => $order['bill_no'],
                );
            }elseif ($order['party_id'] == 65558 && $order['distributor_id'] == 1161){
                $tpl_name = "jinbailiBusiness_order_deliver";
                $msg_vars = array (
                     'msg_shipping_name'   => $order['shipping_name'],
                     'msg_bill_no'         => $order['bill_no'],
                );
            }elseif ($order['party_id'] == 65558){
                continue;
            }elseif ($order['party_id'] == 65553){
                continue;
            }elseif ($order['party_id'] == 65568) {
                $tpl_name = "order_deliver";
                $msg_vars = array (
                     'msg_shipping_name'   => $order['shipping_name'],
                     'msg_bill_no'         => $order['bill_no'],
                );
            }elseif ($order['party_id'] == 65569) {
                $tpl_name = "order_deliver";
                $msg_vars = array (
                     'msg_shipping_time'   => $order['shipping_time'],
                );
                continue;
            }elseif ($order['party_id'] == 65571) {
                $tpl_name = "order_deliver";
                $msg_vars = array (
                     'msg_shipping_name'   => $order['shipping_name'],
                     'msg_bill_no'         => $order['bill_no'],
                );
            }elseif ($order['party_id'] == 128) {
                $tpl_name = "order_deliver";
                $msg_vars = array (
                     'msg_shipping_name'   => $order['shipping_name'],
                     'msg_bill_no'         => $order['bill_no'],
                );
            }
            $this->log("begin erp_send_message");
            try {
                $result = erp_send_message($tpl_name, $msg_vars, $order['party_id'], $order['distributor_id'], $order['mobile']);
                if ($result !== 0) {
                    $this->log("订单号：".$order['order_sn']."短信发送失败".$result);
                } else {
                    $this->log("订单号：".$order['order_sn']."短信发送成功".$result);
                }
            } catch (Exception $e) {
                $partyname = "";
                if($order['party_id'] == 65540){
                    $partyname = "多美滋";
                }elseif ($order['party_id'] == 65547){
                    $partyname = "金奇仕";
                }elseif ($order['party_id'] == 65562){
                    $partyname = "ecco";
                }elseif ($order['party_id'] == 65558){
                    $partyname = "好奇";
                }elseif ($order['party_id'] == 65568){
                    $partyname = "欧世蒙牛";
                }elseif ($order['party_id'] == 65569){
                    $partyname = "安满";
                }elseif ($order['party_id'] == 65571){
                    $partyname = "blackmores";
                }elseif ($order['party_id'] == 128){
                    $partyname = "怀轩";
                }elseif ($order['party_id'] == 65553){
                    $partyname = "雀巢";
                }
                $this->log($partyname."短信发送出现异常！");
            }
            
        }
        
    }
    
    /*
    public function actionDumexSendMsg(){
       require_once(ROOT_PATH.'admin/includes/cls_message.php');
       $message_url = trim(MESSAGE_URL);
       $message_serialnumber = trim(MESSAGE_SERIALNUMBER);
       $message_password = trim(MESSAGE_PASSWORD);
       $message_sessionkey = trim(MESSAGE_SESSIONKEY);
       $MessageReplyClient = new Client($message_url,$message_serialnumber,$message_password,$message_sessionkey);
       $MessageReplyClient->setOutgoingEncoding("UTF-8");  
       $start = time()-38*3600;
       $end = time()-14*3600;
       $sql = "
                SELECT 
                    o.shipping_id, o.country, o.province, o.city, o.district, o.taobao_order_sn, o.order_sn, o.shipping_name,
                    o.party_id, o.distributor_id, o.mobile, o.shipping_id, c.phone_no, c.name, o.address, cb.bill_no, p.is_cod
                FROM 
                    ecshop.ecs_order_info o 
                LEFT JOIN 
                    ecshop.ecs_carrier_bill cb ON o.carrier_bill_id = cb.bill_id
                LEFT JOIN 
                    ecshop.ecs_carrier c ON cb.carrier_id = c.carrier_id  
                LEFT JOIN 
                    ecshop.ecs_payment p ON p.pay_id = o.pay_id 
                WHERE 
                    o.party_id = 65540 AND
                       o.order_type_id= 'SALE' AND o.pay_id <> 1 AND
                       o.order_status = 1 AND
                       o.shipping_time >= '{$start}' AND
                    o.shipping_time < '{$end}' 
                  
                   GROUP BY
                       o.order_id
       ";

        $db = Yii::app()->getComponent('slave');
        $orders = $db->createCommand($sql)->queryAll();
        //短信内电话号码轮换
        $tel = array ('0571-28281303','0571-28280632','0571-28181301','0571-28280631','0571-28181303');
        $tel_size = count($tel);
        $cod_order_index = 0;
        $builder = $db->getCommandBuilder ();
        foreach ($orders as $order){
        	try {
	            $cod_order_index++;
	            // 获取短信模板
	            $content = sprintf("亲爱的客户，您订购的多美滋奶粉已经寄出，快递单号为 '%s'， 有问题请电联'%s'【多美滋官方旗舰店】", $order['bill_no'], $tel[$cod_order_index % $tel_size]) ;
	            $send_result = $MessageReplyClient->sendSMS(array($order['mobile']), $content);
	            
	            $msg_data = array();
                $msg_data['history_id'] = null;
                $msg_data['result'] = $send_result;
                $msg_data['type'] = 'SINGLE';
                $msg_data['send_time'] = date("Y-m-d H:i:s");
                $msg_data['dest_mobile'] = $order['mobile'];
                $msg_data['content'] = $content;
                $msg_data['user_id'] = '1';
                $msg_data['server_name'] = 'emay';
                $table = $db->getSchema()->getTable('message.message_history');
                $builder->createInsertCommand($table, $msg_data)->execute();
                
	            if ($send_result == 0) {
	                $this->log("订单号：".$order['order_sn']." 短信发送失败 返回结果：".$send_result);
	            } else {
	                $this->log("订单号：".$order['order_sn']." 短信发送成功 返回结果：".$send_result);
	            }
        	} catch(Exception $e) {
        	   $this->log("订单号：".$order['order_sn']." 短信发送异常 异常信息: " . $e->getMessage());
        	}
        }
        
    }
        
    public function actionDumexCodCreatedMsg() {
        require_once(ROOT_PATH.'admin/includes/cls_message.php');
        $message_url = trim(MESSAGE_URL);
        $message_serialnumber = trim(MESSAGE_SERIALNUMBER);
        $message_password = trim(MESSAGE_PASSWORD);
        $message_sessionkey = trim(MESSAGE_SESSIONKEY);
        $MessageReplyClient = new Client($message_url,$message_serialnumber,$message_password,$message_sessionkey);
        $MessageReplyClient->setOutgoingEncoding("UTF-8");  
         
        $db = Yii::app ()->getDb ();
        
        //短息模板
        $sql = "select template_content from ecshop.ecs_msg_templates where template_code = 'dumex_cod_order_import' limit 1";
        $content = $db->createCommand ($sql)->queryScalar();
        
        $start = date("Y-m-d H:i:s", time()- 3600 * 24 * 2);
        $end = date("Y-m-d H:i:s", time());
        $sql = "select 
                    oi.mobile,
                    oi.order_id,
                    oi.order_status, 
                    oi.pay_status, 
                    oi.shipping_status,
                    oi.taobao_order_sn,
                    group_concat(oa.action_note) as action_note
                from 
                    ecshop.ecs_order_info oi 
                    inner join ecshop.ecs_order_action oa on oi.order_id = oa.order_id
                where
                    oi.party_id = 65540 and 
                    oi.pay_id = 1 and 
                    oi.order_time >= '$start' and 
                    oi.order_time < '$end' and 
                    oi.order_type_id = 'SALE' and 
                    oi.taobao_order_sn is not null and 
                    oi.taobao_order_sn <> '' and 
                    oi.taobao_order_sn not like '%-F' and 
                    oi.order_status <> 2 
                group 
                    by oi.order_id 
                having 
                    action_note not like '%订单短信已发送%' and 
                    action_note not like '%顾客回复短信%'     
        ";
        
       $orders = $db->createCommand ($sql)->queryAll();
       $builder = $db->getCommandBuilder ();
       try {
            if (!empty($orders)) {
               foreach ($orders as $order) {
                   
                    $send_result = $MessageReplyClient->sendSMS(array($order['mobile']), $content);
                       
                    $msg_data = array();
                    $msg_data['history_id'] = null;
                    $msg_data['result'] = $send_result;
                    $msg_data['type'] = 'SINGLE';
                    $msg_data['send_time'] = date("Y-m-d H:i:s");
                    $msg_data['dest_mobile'] = $order['mobile'];
                    $msg_data['content'] = $content;
                    $msg_data['user_id'] = '1';
                    $msg_data['server_name'] = 'emay';
                    $table = $db->getSchema()->getTable('message.message_history');
                    $builder->createInsertCommand($table, $msg_data)->execute();
                    
                    $oa_data = array();
                    $oa_data['order_id'] = $order['order_id'];
                    $oa_data['action_user'] = 'system';
                    $oa_data['shipping_status'] = $order[shipping_status];
                    $oa_data['order_status'] = $order['order_status'];
                    $oa_data['pay_status'] = $order['pay_status'];
                    $oa_data['action_time'] = date("Y-m-d H:i:s");
                    $oa_data['invoice_status'] = 0;
                    $oa_data['action_note'] = "订单短信已发送";
                    $oa_data['note_type'] = "message";
                    $table = $db->getSchema()->getTable('ecs_order_action');
                    $builder->createInsertCommand($table, $oa_data)->execute();
                    
                    $this->log("淘宝订单号: " . $order['taobao_order_sn'] . " 短信发送结果: " . $send_result . " " .$order['mobile']);
               }
            }
       } catch (Exception $e) {
               $errMsg = $e->getMessage();
               $this->log($errMsg);
       }
         
        
        
    }
    */
    public function actionEccoAfterSynSendMsg(){
    	$start = date('Y-m-d H:i:s',time()-5*60);
        $end = date('Y-m-d H:i:s',time());
        $sql = "
                SELECT 
                    party_id,distributor_id,order_id,mobile,order_sn
                FROM 
                    ecshop.ecs_order_info 
                WHERE 
                    order_type_id= 'SALE' AND
                    party_id = 65562 AND
                    order_time >= '{$start}' AND
                    order_time < '{$end}'                  
                GROUP BY
                    order_id
       ";
        
           $db = Yii::app()->getComponent('slave');
        $orders = $db->createCommand($sql)->queryAll();
        foreach ($orders as $order){
            $tpl_name = "ecco_afterSyn_deliver";
            $msg_vars = array();
       	    try {
                $result = erp_send_message($tpl_name, $msg_vars, $order['party_id'], $order['distributor_id'], $order['mobile']);
                if ($result !== 0) {
                    $this->log("订单号：".$order['order_sn']."短信发送失败".$result);
                } else {
                    $this->log("订单号：".$order['order_sn']."短信发送成功".$result);
                }
            } catch (Exception $e) {
                $this->log("ECCO短信发送出现异常！");
            }
        }
    }    
    
     public function actionEccoAfterSynNoonSendMsg(){
        $start = date('Y-m-d H:i:s',time()-14*3600);
        $end = date('Y-m-d H:i:s',time());
        $sql = "
                SELECT 
                    party_id,distributor_id,order_id,mobile,order_sn
                FROM 
                    ecshop.ecs_order_info 
                WHERE 
                    order_type_id= 'SALE' AND
                    party_id = 65562 AND
                    order_time >= '{$start}' AND
                    order_time < '{$end}'                  
                GROUP BY
                    order_id
       ";
        
        
        $db = Yii::app()->getComponent('slave');
        $orders = $db->createCommand($sql)->queryAll();
        foreach ($orders as $order){
            $tpl_name = "ecco_afterSyn_deliver";
            $msg_vars = array();
       	    try {
                $result = erp_send_message($tpl_name, $msg_vars, $order['party_id'], $order['distributor_id'], $order['mobile']);
                if ($result !== 0) {
                    $this->log("订单号：".$order['order_sn']."短信发送失败".$result);
                } else {
                    $this->log("订单号：".$order['order_sn']."短信发送成功".$result);
                }
            } catch (Exception $e) {
                $this->log("ECCO短信发送出现异常！");
            }
        }
    }      
    
    
    //取得状态报告
    public function actionGetMessageReport(){
        require_once(ROOT_PATH.'admin/includes/cls_message2.php');
        $message_url = trim(MESSAGE_URL);
        $message_serialnumber = trim(MESSAGE_SERIALNUMBER);
        $message_password = trim(MESSAGE_PASSWORD);
        $message_sessionkey = trim(MESSAGE_SESSIONKEY);
        $MessageClient = new Client($message_url,$message_serialnumber,$message_password,$message_sessionkey);
        try {
            $reportAry = $MessageClient->getReport();
        } catch (Exception $e) {
            $this->log("emay service getReport error !");
            return ;
        } 
        var_dump($reportAry);
        do {
            if ($reportAry) {
                $reportAry = is_array($reportAry[0]) ? $reportAry : array($reportAry);
                $db = Yii::app()->getDb();
                $builder = $db->getCommandBuilder ();
                foreach ($reportAry as $report) {
                    $status_code = $report['errorCode'];
                    $note = $report['memo'];
                    $dest_mobile = $report['mobile'];
                    if (substr($dest_mobile, 0, 2) == "86") {
                       $dest_mobile = substr($dest_mobile, 2);
                    }
                    $receive_time = date("Y-m-d H:i:s", strtotime($report['receiveDate']));
                    $report_status = $report['reportStatus'];
                    $seq_id = $report['seqID'];
                    $service_code = $report['serviceCodeAdd'];
                    $submit_date = date("Y-m-d H:i:s", strtotime($report['submitDate']));
                    
                    $msg_data = array();
                    $msg_data['report_id'] = null;
                    $msg_data['server_name'] = 'emay';
                    $msg_data['serial_number'] = $message_serialnumber;
                    $msg_data['dest_mobile'] = $dest_mobile;
                    $msg_data['status_code'] = $status_code;
                    $msg_data['note'] = $note;
                    $msg_data['receive_time'] = $receive_time;
                    $msg_data['report_status'] = $report_status;
                    $msg_data['seq_id'] = $seq_id;
                    $msg_data['service_code'] = $service_code;
                    $msg_data['submit_date'] = $submit_date;
                    $msg_data['created_stamp'] = date("Y-m-d H:i:s");
                    $msg_data['last_update_stamp'] = date("Y-m-d H:i:s");
                    $table = $db->getSchema()->getTable('message.message_report');
                    $builder->createInsertCommand($table, $msg_data)->execute();
                }
            }
         $reportAry = $MessageClient->getReport();
         var_dump($reportAry);
        } while ($reportAry);
        $this->log("");
    }
    
    /* 安满短信提醒*/
    public function actionAnManMessageSend() {
    	/*
       
        }*/
    } 
    
     /* 安怡短信提醒*/
    public function actionAnyiMessageSend() {/*
       $start = date('Y-m-d 17:00:00', time() - 3600 * 24);
       $end = date('Y-m-d 17:00:00', time());
       $sql = "
            select oi.order_sn, oi.mobile, oi.party_id, oi.distributor_id
            from ecshop.ecs_order_info oi
            inner join ecshop.ecs_order_goods og on oi.order_id = og.order_id 
            inner join ecshop.ecs_order_action oa on oi.order_id = oa.order_id 
            where oi.party_id = 65581 and oi.order_type_id = 'SALE' and 
            oa.shipping_status = 2 and oa.action_user = 'webService' and 
            oa.action_time >= '$start' and oa.action_time < '$end'and 
            (og.goods_id = 180312 or (og.goods_id = 180311 and og.goods_number = 1)) 
            group by oi.order_id "; 
        $db = Yii::app()->getComponent('slave');
        $orders = $db->createCommand($sql)->queryAll();
        foreach ($orders as $order){
            $tpl_name = "anyi_goods_promotion";
            $msg_vars = array();
       	    try {
                $result = erp_send_message($tpl_name, $msg_vars, $order['party_id'], $order['distributor_id'], $order['mobile']);
                if ($result !== 0) {
                    $this->log("安怡订单号：".$order['order_sn']."短信发送失败".$result);
                } else {
                    $this->log("安怡订单号：".$order['order_sn']."短信发送成功".$result);
                }
            } catch (Exception $e) {
                $this->log("安怡短信发送出现异常！");
            }
        }*/
    }
    
	/*
	 * 金宝贝短信批量发送
	 * */
	 public function actionGymboreeMessageSend(){
		$start = date('Y-m-d 0:00:00');
		$end = date('Y-m-d 22:00:00');
		$condition = "where party_id = 65574 AND order_time > '{$start}' AND order_time < '{$end}' AND order_type_id = 'SALE' AND pay_status = '2'";

		$sqln = "SELECT sum(eog.goods_number) 
							from ecshop.ecs_order_info eoi
							inner join ecshop.ecs_order_goods eog on eog.order_id = eoi.order_id $condition ";
		//音乐书销量计算
		$sqlm = $sqln." AND eog.goods_id='195878' ";
		$sql = "SELECT round(sum(order_amount),0)  as sale_amount,
					count(distinct nick_name) as sale_ID_amount,
					({$sqln}) as total_num, 
					({$sqlm}) as music_book_num,
					round(avg(order_amount),0) as avg_order_amount,
					round(sum(order_amount)/sum(goods_amount),2) as discount
					from ecs_order_info info $condition ";
		$db = Yii :: app()->getDb();

		$send_msges = $db->createCommand($sql)->queryAll();		
		$dest_mobile_array = array (
			'13901937484','18621560876','18616200722','15958111380','18358193072',
			'15700163225','18721280337','15267142303'
		);
		$send_msges = $send_msges[0];
		$tpl = "【金宝贝】本日截止22:00销售情况： 销售额：{$send_msges['sale_amount']}元 ，" .
		"商品数：{$send_msges['total_num']}件，" .
		"音乐书销量数：{$send_msges['music_book_num']}件，" .
		"成交用户数：{$send_msges['sale_ID_amount']}个，" .
		"客单价：{$send_msges['avg_order_amount']}元，" .
		"discount_rate：{$send_msges['discount']}";		
		foreach($dest_mobile_array as $item){
			$mobile = array($item);
			send_message($tpl, $mobile, null, "yiduSingle");
		}
	}
	
	/**
	 * BabyNes官方旗舰店发货时短信推送
	 * */
	public function actionBabyNesShipmentMessageSend(){
		$start = time()-2*3600;
		$w = 65622;
		$sql = "SELECT o.order_id, s.tracking_number, s.status, o.shipping_name,o.mobile 
				FROM ecshop.ecs_order_info o
				INNER JOIN romeo.order_shipment os on os.order_id = convert(o.order_id using utf8)
				INNER JOIN romeo.shipment s on s.shipment_id = os.shipment_id
				where o.shipping_time > $start AND o.party_id = 65622 AND pay_status = 2 AND order_status = 1
				AND o.shipping_status in (1,2) AND o.taobao_order_sn like 'CN%' ";
		global $db;
		$s = $r = array();
		$db->getAllRefBy($sql,array('order_id'), $s, $r);
		$message = '';
		if(empty($r)) return;
		foreach($r['order_id'] as $order_id =>$item){
			if(count($item)>1){
				foreach($item as $v){
					if($v['status'] != 'SHIPMENT_SHIPPED' || empty($v['mobile'])) continue 2;
				}
				
				foreach($item as $key => $v){
					// $message = $key == 0 ? 
					// "【BabyNes官方旗舰店】亲的宝贝出发啦！因宝贝受到严密保护，您将收到".count($item)."个包裹，包裹1号{$v['shipping_name']}{$v['tracking_number']} 注意查收哦"
					//  : 
					// "【BabyNes官方旗舰店】宝贝继续出发！包裹".($key+1)."号{$v['shipping_name']}{$v['tracking_number']}注意查收！到货48小时内会员中心将与您联系，敬请期待";
					
					// send_message($message,$item[0]['mobile'],null,'yidu');

                    if($key==0){
                        $message="亲的宝贝出发啦！因宝贝受到严密保护，您将收到".count($item)."个包裹，包裹1号{$v['shipping_name']}{$v['tracking_number']} 注意查收哦";
                    }else{
                        $message="宝贝继续出发！包裹".($key+1)."号{$v['shipping_name']}{$v['tracking_number']}注意查收！到货48小时内会员中心将与您联系，敬请期待";
                    }
                    $done=send_message_with_crsms($message, $item[0]['mobile'], "BabyNes官方旗舰店",$response);
                    $this->log("$done=send_message_with_crsms(".$message.", ".$item[0]['mobile'].", BabyNes官方旗舰店,".$response.");");
				}
			}
			else{
				if($item[0]['status'] != 'SHIPMENT_SHIPPED' || empty($item[0]['mobile'])) continue;
				
                // $message = "【BabyNes官方旗舰店】亲的宝贝出发啦！{$item[0]['shipping_name']}{$item[0]['tracking_number']},亲注意查收哦！会员中心将在到货48小时内与您联系，敬请期待";
				// send_message($message,$item[0]['mobile'],null,'yidu');

                $message = "亲的宝贝出发啦！{$item[0]['shipping_name']}{$item[0]['tracking_number']},亲注意查收哦！会员中心将在到货48小时内与您联系，敬请期待";
                $done=send_message_with_crsms($message, $item[0]['mobile'], "BabyNes官方旗舰店",$response);
                $this->log("$done=send_message_with_crsms(".$message.", ".$item[0]['mobile'].", BabyNes官方旗舰店,".$response.");");
			}			
			
		}
		
	}
	public function actionCofcoShipmentMessageSend(){
		global $db;
		$start = time()-(2*3600 + 3*60);
		$days_ago = date("Y-m-d 00:00:00", strtotime("5 days ago")); 
		$sql = "SELECT po.order_id 
				FROM ecshop.ecs_order_info po 
				INNER JOIN ecshop.order_relation ori ON ori.parent_order_id = po.order_id 
				INNER JOIN ecshop.ecs_order_info so ON so.order_id = ori.order_id AND so.order_type_id = 'SALE'
				     AND so.shipping_time > {$start} 
				WHERE  po.order_time > '{$days_ago}' and po.order_type_id = 'SALE' 
				group by po.order_id ";
		$time1_start = microtime(true); 		 
		$parent_ids = $db->getCol($sql);
        
		$time1_end = microtime(true);
		$this->log( "time 1 :".($time1_end - $time1_start)  ) ;
		$sql_union = ""; 
		if(!empty($parent_ids)){
			$sql_union = " UNION ".
				"SELECT ori.parent_order_id as order_id ,p.name, s.tracking_number, s.status, o.shipping_name, o.mobile, o.shipping_status,o.order_status 
				FROM ecshop.order_relation ori
				INNER JOIN ecshop.ecs_order_info o ON o.order_id = ori.order_id AND o.order_type_id = 'SALE'
				LEFT JOIN romeo.order_shipment os ON os.order_id = convert(o.order_id using utf8)
				LEFT JOIN romeo.shipment s ON s.shipment_id = os.shipment_id  
				INNER JOIN romeo.party p on  p.party_id = convert(o.party_id using utf8)
				WHERE ori.parent_order_id ".db_create_in($parent_ids);
		}
		$this->log( " parent_order_ids  :".(db_create_in($parent_ids) )  ) ; 
		$sql = "SELECT o.order_id,p.name, s.tracking_number, s.status, o.shipping_name,o.mobile , o.shipping_status , o.order_status 
				FROM ecshop.ecs_order_info o
				LEFT JOIN ecshop.order_relation ori ON ori.order_id = o.order_id
				INNER JOIN romeo.order_shipment os on os.order_id = convert(o.order_id using utf8)
				INNER JOIN romeo.shipment s on s.shipment_id = os.shipment_id 
				INNER JOIN romeo.party p on  p.party_id = convert(o.party_id using utf8)
				where  o.shipping_time > $start  AND pay_status = 2 AND order_status = 1 
				AND order_time > '{$days_ago}'
				AND o.order_type_id = 'SALE' AND o.shipping_status in (1,2) AND s.shipping_category = 'SHIPPING_SEND'
				AND ori.parent_order_id is NULL   {$sql_union} ";
		$s = $r = array();
		$time2_start = microtime(true);
		$db->getAllRefBy($sql,array('order_id'), $s, $r);

		$time2_end = microtime(true);
		$this->log( "time2: ".($time2_end - $time2_start) ); 
		$message = '';
		if(empty($r)) return;
		foreach($r['order_id'] as $order_id =>$item){
			if(count($item)>1){
               
                // 9 月 9日后新加的 判断该订单是否已经发送短信 
                $sql_send = "SELECT 1 FROM ecshop.order_attribute where order_id = '{$order_id}' and attr_name = 'SPLIT_MSG_SEND' and attr_value = '0' LIMIT 1 "; 
                $is_msg_send = $db->getOne($sql_send);
                $this->log("order_id {$order_id} msg has send ".$is_msg_send); 
                if(!empty($is_msg_send) ) continue; 

				$shipment = '';
				$party_name = '';
				foreach($item as $v){
					if(empty($v['mobile']) || empty($v['tracking_number']) || $v['order_status'] == 2 || !in_array($v['shipping_status'],array(0,1,2))) continue 2;
					$shipment .= "{$v['shipping_name']}{$v['tracking_number']} ";
					$party_name = $v['name'];
				}
				$message = "温馨提示：由于您订购的商品较多，我们将分为（".count($item)."）个包裹为您发货，请亲注意签收。" .
						"快递：$shipment";
				// var_dump($message);
				// var_dump($order_id);
				// var_dump($item[0]['mobile']);
				$length = 126;
				$count = (mb_strlen($message,'utf-8'))/$length;
				$message_send_result = 1; // 表示未发送成功 
				for($i = 0; $i<$count; $i++){
					$sendmessage = trim(mb_substr($message,$i*$length,$length,'utf-8'));
					if(empty($sendmessage)){
						continue;
					}
					// $is_send_ok = send_message("【".$party_name."】".$sendmessage,$item[0]['mobile'],null,'yiduSingle');
                    $is_send_ok=send_message_with_crsms($sendmessage, $item[0]['mobile'], $party_name, $response);
                    $this->log("send_message_with_crsms({$sendmessage}, {$item[0]['mobile']}, {$party_name}, '{$response}')");
					if($is_send_ok == 1){
						$message_send_result = 0; // 表示短信发送成功 
					}
				}
				// 记录短信发送情况 
 				$sql_insert_attr = "INSERT INTO ecshop.order_attribute (order_id,attr_name,attr_value) 
 					VALUES({$order_id},'SPLIT_MSG_SEND',{$message_send_result})"; 
				$db->query($sql_insert_attr); 
				 
//				send_message($message,$item[0]['mobile'],null,'yidu');
			}
			else{
//				if($item[0]['status'] != 'SHIPMENT_SHIPPED' || empty($item[0]['mobile'])) continue;
//				$message = "亲的宝贝出发啦！{$item[0]['shipping_name']}{$item[0]['tracking_number']},亲注意查收哦！会员中心将在到货48小时内与您联系，敬请期待【BabyNes官方旗舰店】";
//				send_message($message,$item[0]['mobile'],null,'yidu');
			}			
			
		}
	}
	
	public function actionSendMessageForOutSplitOrders($month=1){
        global $db;

        $month=intval($month);

        $this->log('SendMessage SendMessageForOutSplitOrders begin with Month='.$month);

        //search for INIT status mapping
        $sql="SELECT 
            mapping_id, out_split_order_ids, out_sms_status
        FROM
            ecshop.ecs_order_mapping
        WHERE
            1 AND out_split_order_ids IS NOT NULL
                AND out_split_order_ids != ''
                AND out_sms_status = 'INIT'
                AND created_time > SUBDATE(NOW(), INTERVAL {$month} MONTH)
        ORDER BY mapping_id
        LIMIT 1000
        ";

        $this->log('search orders with sql: '.$sql);

        $mapping_list = $db->getAll($sql);

        $this->log('get a list of '.count($mapping_list).' orders');

        foreach ($mapping_list as $mapping) {
            $mapping_id=$mapping['mapping_id'];
            $out_split_order_ids=$mapping['out_split_order_ids'];
            $out_sms_status=$mapping['out_sms_status'];

            if(preg_match('/[^0-9,]/', $out_split_order_ids)||preg_match('/(^,)|(,$)/', $out_split_order_ids)){
                $this->log('Mapping['.$mapping_id.'] contains incorrect format field: '.$out_split_order_ids);
                continue;
            }

            $order_id_list=explode(',', $out_split_order_ids);
            if(count($order_id_list)<=1){
                $this->log('Mapping['.$mapping_id.'] contains not so many orders: '.$out_split_order_ids);
                continue;
            }

            //check is all sent
            $sql="SELECT o.mobile,o.order_id,o.shipping_status,s.shipment_id,s.tracking_number,p.name as party_name,es.shipping_name 
            FROM ecshop.ecs_order_info o
            LEFT JOIN romeo.order_shipment os ON convert(o.order_id using utf8)=os.order_id
            LEFT JOIN romeo.shipment s ON s.shipment_id=os.shipment_id 
            LEFT JOIN romeo.party p ON convert(o.party_id using utf8)=p.party_id
            LEFT JOIN ecshop.ecs_shipping es ON cast(s.shipment_type_id as unsigned) = es.shipping_id

            WHERE o.order_id in ({$out_split_order_ids})
            AND o.order_type_id = 'SALE' 
            -- AND o.shipping_status in (1,2) 
            AND s.shipping_category = 'SHIPPING_SEND'
            AND s.status!='SHIPMENT_CANCELLED'
            group by s.tracking_number
            ";
            $os_list=$db->getAll($sql);

            print_r($os_list);
            echo PHP_EOL;

            if(count($os_list)<=1){
                $this->log('ONLY ONE TRACKING NUMBER!');
                continue;
            }

            $need_not_to_send_sms=false;
            $mobile='';
            foreach ($os_list as $os_item) {
                if($os_item['shipping_status']!=1 and $os_item['shipping_status']!=2){
                    $need_not_to_send_sms=true;
                    break;
                }
                if(empty($os_item['tracking_number'])){
                    $need_not_to_send_sms=true;
                    break;
                }
                if(!empty($os_item['mobile'])){
                    $mobile=$os_item['mobile'];
                }
            }
            if($need_not_to_send_sms){
                $this->log('Mapping['.$mapping_id.'] pending to send');
                continue;
            }
            if(empty($mobile)){
                $this->log('Mapping['.$mapping_id.'] no mobile');
                continue;
            }

            //send
            $shipment = '';
            $party_name = '';
            foreach($os_list as $v){
                $shipment .= "{$v['shipping_name']}{$v['tracking_number']} ";
                $party_name = $v['party_name'];
            }
            $message = "温馨提示：由于您订购的商品较多，我们将分为（".count($os_list)."）个包裹为您发货，请亲注意签收。快递：$shipment";

            $length = 126;
            $count = (mb_strlen($message,'utf-8'))/$length;
            $message_send_result = 1; // 表示未发送成功 
            for($i = 0; $i<$count; $i++){
                $sendmessage = trim(mb_substr($message,$i*$length,$length,'utf-8'));
                if(empty($sendmessage)){
                    continue;
                }
                $is_send_ok=send_message_with_crsms($sendmessage, $mobile, $party_name, $response);
                $this->log("send_message_with_crsms({$sendmessage}, {$mobile}, {$party_name}, '{$response}')");
                if($is_send_ok == 1){
                    $message_send_result = 0; // 表示短信发送成功 
                }
            }
            // 记录短信发送情况 
            foreach ($order_id_list as $order_id) {
                $sql_insert_attr = "INSERT INTO ecshop.order_attribute (order_id,attr_name,attr_value) VALUES({$order_id},'SPLIT_MSG_SEND',{$message_send_result})"; 
                $db->query($sql_insert_attr); 
            }
            $sql_insert_to_eom="UPDATE ecshop.ecs_order_mapping SET out_sms_status='SENT' WHERE mapping_id={$mapping_id} LIMIT 1";
            $db->query($sql_insert_to_eom);
        }

        $this->log('over');

    }
	
	/* 短信批量发送调度 */
	public function actionSendMessageBatchSchedule() {
		// 每次调度 发送的条数
		$SEND_SCHEDULE_SIZE = 30000;
		// 每次调用发送函数时发送的条数
		$SEND_LOOP_SIZE = 10000;
		
		$scheduletime = date ( 'Y-m-d h:i:s' );
		$this->log ( "开始调度：" . $scheduletime );
		
		$sql = "
               select msd.msg_send_id,msd.dest_mobile,mtb.template_content,msd.party_id,mtb.server_name 
                 from ecshop.ecs_msg_send_detail msd
                 inner join ecshop.ecs_msg_templates_batch mtb
                   on msd.template_id = mtb.template_id
                 where now() >= msd.start_time
                   and msd.send_result = -1
				           and msd.template_id = ( select template_id from ecshop.ecs_msg_send_detail 
				                                    where now()>=start_time 
				                                      and send_result = -1 order by create_time,start_time limit 1)
	             order by msd.create_time,msd.start_time,mtb.template_id
	             limit {$SEND_SCHEDULE_SIZE} ";
		$db = Yii::app ()->getDb ();
		$send_msges = $db->createCommand ( $sql )->queryAll ();
		
		$dest_mobile_array = array ();
		$msg_send_id_array = array ();
		$num = 1;
		foreach ( $send_msges as $send_msg ) {
			$dest_mobile_array [] = $send_msg ['dest_mobile'];
			$msg_send_id_array [] = $send_msg ['msg_send_id'];
			
			if ($num % $SEND_LOOP_SIZE == 0 || $num == count ( $send_msges )) {
				$this->log ( "发送短信" . count ( $dest_mobile_array ) . "条，包含msg_send_id:{$send_msg['msg_send_id']},短信内容：{$send_msg['template_content']},调度时间：{$scheduletime}" );
				$result = 1;
				$result = send_message ( $send_msg ['template_content'], $dest_mobile_array, $send_msg ['party_id'], $send_msg ['server_name'] );
				
				if ($result === false) {
					$this->log ( "无权限" );
					$result = 1;
				}
				$updatesql = "
       	 	              update ecshop.ecs_msg_send_detail
       	 	                set send_time = current_timestamp(),send_result={$result}
       	 	              where msg_send_id in ( " . implode ( ",", $msg_send_id_array ) . " )";
				
				$db->createCommand ( $updatesql )->execute ();
				$dest_mobile_array = array ();
				$msg_send_id_array = array ();
			}
			$num ++;
		}
	}      

    public function log($m){
        echo date("Y-m-d H:i:s") . " " . $m . PHP_EOL;
    }

    public function actionSendTest(){
        // $result = send_message ('【乐其】温馨提示，您的订单已经开始处理，大约需要3天可以到达！', array('18768113897'), 65558, 'emay' );
        // print_r($result);
        $datas=array();
        $datas[0]=array("msg"=>"尊敬的乐其金佰利分销商：目前您的预存款账户只剩@，请在24小时内及时续款，避免停发货","sign"=>"乐其");
        $datas[1]=array("msg"=>"温馨提示：由于您订购的商品较多，我们将分为（@）个包裹为您发货，请亲注意签收。快递：@ ","sign"=>"babynes");
        $datas[2]=array("msg"=>"亲的宝贝出发啦！因宝贝受到严密保护，您将收到@个包裹，包裹1号@注意查收哦","sign"=>"BabyNes官方旗舰店");
        $datas[3]=array("msg"=>"宝贝继续出发！包裹@号@注意查收！到货48小时内会员中心将与您联系，敬请期待","sign"=>"BabyNes官方旗舰店");
        $datas[4]=array("msg"=>"亲的宝贝出发啦！@,亲注意查收哦！会员中心将在到货48小时内与您联系，敬请期待","sign"=>"BabyNes官方旗舰店");
        $dest_mobile='18768113897';
        foreach ($datas as $key => $data) {
            $res=send_message_with_crsms($data['msg'], $dest_mobile, $data['sign']);
            print_r($res);
            echo PHP_EOL;
        }
    }
}
