<?php
define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'includes/helper/mail.php';
require_once(ROOT_PATH . 'includes/helper/array.php');
Yii::import('application.commands.LockedCommand', true);

//global $db;
/*
 * Created on 2015-08-18
 *
 * All Hail Giant Salamander the Evil
 * =====================================
 * 发货同步实验室
 */
class ErpSyncJdDeliveryCommand extends CConsoleCommand{

	private $slave;
	private $shipping_time_limit_days=2;
	private $shipping_time_limit_days_offset=0;
	private $shipping_pause_sync_time_table=null;

	/**
     * 取得启用的京东店铺
     *
     * @return array
     */
    protected function getTaobaoShopList()
    {
    	static $list;
    	if(!isset($list))
    	{
    		//360buy_overseas 表示京东境外店
    		$sql="SELECT * FROM taobao_shop_conf WHERE status='OK' and shop_type in ('360buy', '360buy_overseas')";
    		$list=Yii::app()->getDb()->createCommand($sql)->queryAll();
    		$command=Yii::app()->getDb()->createCommand("SELECT * from taobao_api_params where taobao_api_params_id=:id");
    		foreach($list as $key=>$item){
    			$list[$key]['params']=$command->bindValue(':id',$item['taobao_api_params_id'])->queryRow();
    		}
    	}
    	return $list;
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
	 * 每个命令执行前执行
	 *
	 * @param string $action
	 * @param array $params
	 * @return boolean
	 */
	protected function beforeAction($action, $params, $exitCode = 0)
	{

		if(strnatcasecmp($action,'index')==0)
			return true;
		
		// 加锁
		$lockName="commands.".$this->getName().".".$action.$params[0].$params[2];
		if(($lock=Yii::app()->getComponent('lock'))!==null && $lock->acquire($lockName,60*10))
		{
			// 记录命令的最后一次执行的开始时间
			$key='commands.'.$this->getName().'.'.strtolower($action).':start';
			Yii::app()->setGlobalState($key,microtime(true));
			return true;	
		}
		else
		{
			echo "[".date('Y-m-d H:i:s')."] 命令{$action}正在被执行，或上次执行异常导致独占锁没有被释放，请稍候再试。\n";
			return false;
		}
	}
	
	/**
	 * 执行完毕后执行
	 * 
	 * @param string $action the action name
	 * @param array $params the parameters to be passed to the action method.
	 */
	protected function afterAction($action,$params, $exitCode = 0)
	{	
		if(strnatcasecmp($action,'index')==0)
			return;

		// 记录命令的最后一次执行的完毕时间
		$key='commands.'.$this->getName().'.'.strtolower($action).':done';
		Yii::app()->setGlobalState($key,microtime(true));
		
		// 释放锁
		$lockName="commands.".$this->getName().".".$action.$params[0].$params[2];
		$lock=Yii::app()->getComponent('lock');
		$lock->release($lockName);
	}
	
	/**
	 * 取得最后一次执行完毕的时间
	 *
	 * @param string $action
	 */
	protected function getLastExecuteTime($action)
	{
		$key='commands.'.$this->getName().'.'.strtolower($action).':done';
		return Yii::app()->getGlobalState($key,0);
	}

	////////////////////////////////////////
	///           同步发货殖民地           ///
	////////////////////////////////////////


    private function findPureRecords($appkey){
		$db=Yii::app()->getDb();
        $db->setActive(true);

        $sql="SELECT
			oi.order_id,
			oi.taobao_order_sn,
			mp.taobao_order_sn mp_taobao_order_sn,
			oi.shipping_id,
			oi.shipping_name,
			s.tracking_number
		FROM
			ecshop.ecs_jd_order_mapping mp
		INNER JOIN ecshop.ecs_order_info oi ON mp.taobao_order_sn = oi.taobao_order_sn
		INNER JOIN romeo.order_shipment os ON os.order_id = CONVERT (oi.order_id USING utf8)
		INNER JOIN romeo.shipment s ON s.shipment_id = os.shipment_id
		WHERE
			mp.application_key = :key
		AND oi.order_status = 1 
		-- AND oi.pay_status = 2
		AND oi.shipping_status = 1
		AND oi.shipping_time > (UNIX_TIMESTAMP() - 3600 * 24 * {$this->shipping_time_limit_days})
		AND oi.shipping_time <= (UNIX_TIMESTAMP() - 3600 * 24 * {$this->shipping_time_limit_days_offset})
		AND mp.shipping_status = ''
		AND s.tracking_number IS NOT NULL
		AND s.tracking_number != ''
		LIMIT 1000
		-- SQL From ErpSyncJdDeliveryCommand::findPureRecords
		";

		$this->sinri_log('Find Pure Records Start');
        $order_list=Yii::app()->getDb()->createCommand($sql)->bindValue(':key',$appkey,PDO::PARAM_STR)->queryAll();
        $this->sinri_log('Find Pure Records End, totally '.count($order_list));

        return $order_list;
	}

	private function findSubRecords($appkey,$distributor_id){
		$db=Yii::app()->getDb();
        $db->setActive(true);

        $order_list=array();

        $sql_1="SELECT
			oi.order_id,
			oi.taobao_order_sn,
			-- oi.distribution_purchase_order_sn,
			oi.shipping_name,
			oi.shipping_id,
			-- oi.shipping_time,
			-- oi.facility_id,
			-- s.carrier_id,
			s.tracking_number			
		FROM
			ecshop.ecs_order_info oi use index (shipping_time)
		INNER JOIN romeo.order_shipment os on os.order_id=convert(oi.order_id using utf8)
		INNER JOIN romeo.shipment s on s.shipment_id=os.shipment_id
		WHERE
			oi.distributor_id = :distributor_id
		AND oi.order_status = 1
		AND oi.pay_status = 2
		AND oi.shipping_status = 1
		AND oi.shipping_time >(UNIX_TIMESTAMP() - 3600 * 24 * {$this->shipping_time_limit_days})
		AND oi.shipping_time <=(UNIX_TIMESTAMP() - 3600 * 24 * {$this->shipping_time_limit_days_offset})
		AND s.tracking_number is not null AND s.tracking_number != ''
		AND oi.taobao_order_sn LIKE '%-%'
		limit 1000
		-- SQL From ErpSyncJdDeliveryCommand::findSubRecords
		";

		$this->sinri_log('Find Sub Records Start');
		$list1=Yii::app()->getDb()->createCommand($sql_1)->bindValue(':distributor_id',$distributor_id,PDO::PARAM_STR)->queryAll();
        $this->sinri_log('Find Sub Records End, totally '.count($list1));

        if(!empty($list1)){
        	foreach ($list1 as $k1 => $v1) {
        		$subtagindex=strpos($v1['taobao_order_sn'],'-');
        		$pure_taobao_order_sn='';
        		if($subtagindex!==false){
        			$pure_taobao_order_sn=substr($v1['taobao_order_sn'], 0,$subtagindex);
        		}

        		if(empty($pure_taobao_order_sn)){
        			continue;
        		}

        		$sql_4="SELECT
						-- mp.mapping_id,
						mp.taobao_order_sn mp_taobao_order_sn
					FROM
						ecshop.ecs_order_info oi
					INNER JOIN ecshop.ecs_jd_order_mapping mp ON oi.taobao_order_sn = mp.taobao_order_sn
					WHERE
						oi.taobao_order_sn = :pure_taobao_order_sn
					AND mp.shipping_status = '' 
					-- SQL From ErpSyncTaobaoDeliveryCommand::findSubRecords_checkMP
				";

        		$list2=Yii::app()->getDb()->createCommand($sql_4)->bindValue(':pure_taobao_order_sn',$pure_taobao_order_sn,PDO::PARAM_STR)->queryAll();

        		if(!empty($list2)){
        			foreach ($list2 as $k2 => $v2) {
        				$order_list[]=array(
        					// 'mapping_id'=>$v2['mapping_id'],
        					'mp_taobao_order_sn'=>$v2['mp_taobao_order_sn'],
							'order_id'=>$v1['order_id'],
							'shipping_name'=>$v1['shipping_name'],
							// 'shipping_time'=>$v1['shipping_time'],
							'shipping_id'=>$v1['shipping_id'],
							// 'facility_id'=>$v1['facility_id'],
							// 'type'=>$v2['type'],
							'tracking_number'=>$v1['tracking_number'],
							// 'carrier_id'=>$v1['carrier_id'],
        				);
        			}
        		}
        	}
        }

        $this->sinri_log('Found Sub Records Mapping Done');

        return $order_list;
    }

	/**
     * 同步发货
     * @param string $appkey 执行店铺的应用编号
     * @param int months 订单确认时间距离当前时间几个months内
     */
    public function actionSyncJdOrderSendDelivery($route='pure',$appkey=null,$group=0,$days=2,$days_offset=0)
    {
    	// 不启用商品同步的店铺列表
		$exclude_list=array
		(
				// 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
				// 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
		);
		ini_set('default_socket_timeout', 1200);
		
		
		
		


		$this->shipping_time_limit_days=$days;
        $this->shipping_time_limit_days_offset=$days_offset;
    
    	
    	foreach($this->getTaobaoShopList() as $taobaoShop)
    	{
			if(in_array($taobaoShop['application_key'],$exclude_list)){
				continue;
			}
	
			if($appkey!==null&&$appkey!=$taobaoShop['application_key']){
				continue;
			}
    
    		echo("[".date('c')."] ".$taobaoShop['nick']." SyncJdOrderSendDelivery start \n");
			// $start = microtime(true);

			// Sagyou Hajime

        	$start_time = $this->microtime_float();

			$this->sinri_log('===');
			$this->sinri_log($taobaoShop['nick']." route=".$route." distributor_id=".$taobaoShop['distributor_id'].' days='.$this->shipping_time_limit_days);
         	$this->sinri_log($taobaoShop['nick']. " delivery send sync start!");
         	if($route=='pure'){
         		$this->sinri_log($taobaoShop['nick']." BEGIN COMMON ORDER SEARCH...");
            	$order_list_common=$this->findPureRecords($taobaoShop['application_key']);
            	$this->sinri_log($taobaoShop['nick']." COMMON ORDERS COUNTS ".count($order_list_common));
            }else{
				$order_list_common=array();
            }
            if($route=='sub'){
	            $this->sinri_log($taobaoShop['nick']." BEGIN SUB ORDER SEARCH...");
	            $order_list_subs=$this->findSubRecords($taobaoShop['application_key'],$taobaoShop['distributor_id']);
	            $this->sinri_log($taobaoShop['nick']." SUB ORDERS COUNTS ".count($order_list_subs));
	        }else{
	        	$order_list_subs=array();
	        }
	        
            $order_list_all_tmp=array_merge($order_list_subs,$order_list_common);

			$order_list_all=array();
			foreach($order_list_all_tmp as $order_tmp){
				$order_list_all[$order_tmp['order_id']]['mp_taobao_order_sn']=$order_tmp['mp_taobao_order_sn'];
				$order_list_all[$order_tmp['order_id']]['order_id']=$order_tmp['order_id'];
				$order_list_all[$order_tmp['order_id']]['shipping_name']=$order_tmp['shipping_name'];
				$order_list_all[$order_tmp['order_id']]['TNS'][$order_tmp['tracking_number']]=$order_tmp['tracking_number'];
			}
			
            $issues=array();

            foreach ($order_list_all as $order_item) {
            	$item_info="MP_TBSN=".$order_item['mp_taobao_order_sn'].
            		" OID=".$order_item['order_id'].
            		" SHIP=".$order_item['shipping_name'].
            		" TN=".implode(",",$order_item['TNS']);

            	if(empty($order_item['mp_taobao_order_sn']) || empty($order_item['TNS'])){
            		$item_info.=" EMPTY SO CONTINUE";
            		$this->sinri_log($item_info);
            		continue;
            	}else{
            		$item_info.=" GO";
            	}

            	$html='';
            	$done=$this->js_delivery($taobaoShop,$order_item,$html);

            	if($done){
            		$this->sinri_log("JD_SHOP_DELIVERY_DONE! 京东店铺 ". $taobaoShop['nick'] ." 发货同步成功:".$html." | ".$item_info);
            	}else{
            		$this->sinri_log("JD_SHOP_DELIVERY_FAILED! 京东店铺 ". $taobaoShop['nick'] ." 发货同步失败:".$html." | ".$item_info);
            		$issues[]=$html;
            	}
            }

            $this->send_alert_mail($taobaoShop,$issues);

            $runtime = number_format(($this->microtime_float()-$start_time), 4).'s';

            $average_time=count($order_list_all)>0?($runtime/count($order_list_all)):'N/A';

            $this->sinri_log('ONE_SHOP_END ['.$route.'] time='.$runtime.' order_count='.count($order_list_all).' average_time='.$average_time);
    
    		// echo "[". date('c'). "]  耗时：".(microtime(true)-$start)."\n";
    		usleep(500000);
    	}
    }

    private function js_delivery($taobaoShop,$order_item,&$html){
    	$db=Yii::app()->getDb();
        $db->setActive(true);
    	
    	static $codeMap=array(
	        '中通快递' => '1499',
	        '顺丰快递' => '467',
	        '申通快递' => '470',
	        'EMS快递' => '465',
	        'EMS经济快递' => '465',
	        '邮政国内小包' => '2170',
	        '汇通快递' => '1748',
	        '顺丰（陆运）' => '467',
	        '圆通快递' => '463',
	        '韵达快递' => '1327',
	        '京东COD' => '2087' ,
	        '京东配送' => '2087'
        );
    	
    	
    	
    	try {
    		// 远程服务
    		$client=Yii::app()->getComponent('erpsync')->SyncJdService;
    		
    		$shipping_status = 'WAIT_GOODS_RECEIVE_CONFIRM';
            $order_id =  $order_item['order_id'];
            $taobao_order_sn = $order_item['mp_taobao_order_sn'];
            $order_status = 'WAIT_GOODS_RECEIVE_CONFIRM';
            $order_state_remark = '等待收货确认';
    		
    		$request = array(
	                    // 分销订单, 用分销采购订单号发货
	                    'applicationKey' =>$taobaoShop['application_key'],
	                    'order_id'=>$order_item['order_id'],
	                    'jd_order_id'=>$order_item['mp_taobao_order_sn'],
	                    'shipping_name'=>$order_item['shipping_name'] ,
	                    'logistics_id'=>isset($codeMap[$order_item['shipping_name']])?$codeMap[$order_item['shipping_name']]:'OTHER',
	                    'way_bill'=>implode(",",$order_item['TNS']),
                    );
	
	        // 请求京东发货
            $response = null ;
            
            $response = $client->SyncJdOrderSendDeliveryNew($request)->return;
            if(isset($response) && isset($response->code) && ($response->code=='0' || $response->code=='10400001' ||  $response->code=='10400010'    ) ){ //10400001表示已经手动发货（京东后台已经出库）   			
    			// jdOrderId,"WAIT_GOODS_RECEIVE_CONFIRM","等待收货确认"
    			
    			$update_jd_order_info = "UPDATE ecshop.sync_jd_order_info set order_state='{$order_status}', order_state_remark='{$order_state_remark}', modified=now() where order_id= {$order_id}	";
    			// $update_jd_order_info_result = $db->query($update_jd_order_info);
    			$update_jd_order_info_result=Yii::app()->getDb()->createCommand($update_jd_order_info)->execute();
    			
    			$update_jd_order_mapping = "UPDATE ecshop.ecs_jd_order_mapping set shipping_status = '{$shipping_status}'   where taobao_order_sn = '{$taobao_order_sn}'" ;
    			// $update_jd_order_mapping_result = $db->query($update_jd_order_mapping);
    			$update_jd_order_mapping_result=Yii::app()->getDb()->createCommand($update_jd_order_mapping)->execute();
    			
    			$update_jd_order_mapping_new = "UPDATE ecshop.ecs_order_mapping set shipping_status = '{$shipping_status}'   where outer_order_sn = '{$taobao_order_sn}' and platform in ('360buy','360buy_overseas')" ;
    			// $update_jd_order_mapping_result = $db->query($update_jd_order_mapping);
    			$update_jd_order_mapping_result_new=Yii::app()->getDb()->createCommand($update_jd_order_mapping_new)->execute();

    			if( $update_jd_order_info && $update_jd_order_mapping ){
    				$html.=("js_delivery success! order_id: ".$order_id ." code: ".$response->code ." msg: ". $response->msg .' ');
	    			if($update_jd_order_mapping_result_new){
	    				$html.=(' [NEW_OK]');
	    			}else{
	    				$html.=(' [NEW_KO]');
	    			}
	    			$html.=PHP_EOL;
	    			return true;
    			}else{
    				$html.=("js_delivery success but db failed! order_id: ".$order_id ." code: ".$response->code ." msg: ". $response->msg .' ');
    				if($update_jd_order_mapping_result_new){
	    				$html.=(' [NEW_OK]');
	    			}else{
	    				$html.=(' [NEW_KO]');
	    			}
	    			$html.=PHP_EOL;
    			}
            }
            
            else {
	              $html.=("js_delivery fail! order_id: ".$order_id ." code: ".$response->code ." msg: ". $response->msg .PHP_EOL);
            }
    		
    	} catch (Exception $e) {
    		$html .= "Exception: ".$e->getMessage().PHP_EOL;
    	}
    	return false;
    }

    private function send_alert_mail($taobaoShop,$issues){
    	if(empty($issues)){
    		return;
    	}
    	try {
    		$html='<p>'.PHP_EOL.(implode(PHP_EOL.'</p>'.PHP_EOL.'<p>'.PHP_EOL, $issues)).PHP_EOL.'</p>';

            $mail=Yii::app()->getComponent('mail');
            $mail->Subject="京东店铺 ". $taobaoShop['nick'] ." 发货同步失败";
          	
            $mail->ClearAddresses();
	        $mail->AddAddress('mjzhou@leqee.com', '周明杰');
	        $mail->AddAddress('zjli@leqee.com', '李志杰');	
	        $mail->AddAddress('hyzhou1@leqee.com', '周涵英');
        	$mail->AddAddress('wjzhu@leqee.com', '朱伟晋');	
        	$mail->AddAddress('ytchen@leqee.com', '陈艳婷');
        	$mail->AddAddress('qyyao@leqee.com', '姚启亚');
        	$mail->AddAddress('ljni@leqee.com', '邪恶的大鲵');
            $mail->Body = $html;
            $mail->IsHtml();  // 如果邮件是html格式的话
            $mail->send();
        } catch (Exception $e){

        }
    }

    private function sinri_log($str){
    	echo("[".date('c')."] ".$str.PHP_EOL);
    }

    private function microtime_float()
	{
	    list($usec, $sec) = explode(" ", microtime());
	    return ((float)$usec + (float)$sec);
	}
}

?>