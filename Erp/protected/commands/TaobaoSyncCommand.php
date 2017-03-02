<?php
ob_start();
define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'includes/helper/mail.php';
require_once(ROOT_PATH . 'includes/helper/array.php');
Yii::import('application.commands.LockedCommand', true);
/**
 * @author yxiang@leqee.com
 * @copyright Copyright &copy; 2010 leqee.com
 */

/**
 * 淘宝同步
 * 
 * @author yxiang@leqee.com
 * @version $Id$
 * @package application.commands
 */
class TaobaoSyncCommand extends LockedCommand
{
    private $slave;  // Slave数据库

    public $betweenKeepSessionAlive=3600;   // 为保持Session不过期，每隔多少秒发送的请求
    public $betweenSyncDeliverySend=800;    // 同步发货的时间，默认15分钟一次
    public $betweenSyncItem=1800;           // 执行商品同步的时间
    public $betweenSyncItemStock=5400;      // 执行时间
    public $betweenSyncOrder = 300;         // 订单同步 

    /**
	 * 当不指定ActionName时的默认调用
	 */
    public function actionIndex()
    {
        $currentTime=microtime(true);

        // 持久SESSION
        if($currentTime-$this->getLastExecuteTime('KeepSessionActive')>=$this->betweenKeepSessionAlive) {
        $this->run(array('KeepSessionActive'));
        }

        // 发货同步
        if($currentTime-$this->getLastExecuteTime('SyncDeliverySend')>=$this->betweenSyncDeliverySend) {
        $this->run(array('SyncDeliverySend'));
        }

        // 库存同步
        if($currentTime-$this->getLastExecuteTime('SyncItemStock')>=$this->betweenSyncItemStock)
        {
            // 同步库存前先更新商品
            $this->run(array('SyncItem','--seconds='.$this->betweenSyncItem));
            $this->run(array('SyncItemStock','--seconds='.$this->betweenSyncItemStock));
        }

        // 商品同步
        if($currentTime-$this->getLastExecuteTime('SyncItem')>=$this->betweenSyncItem) {
            $this->run(array('SyncItem','--seconds='.$this->betweenSyncItem));    
        }
        // 同步订单
        if($currentTime-$this->getLastExecuteTime('SyncOrder')>=$this->betweenSyncOrder) {
        	$this->run(array('SyncOrder'));
        }
    }

    /**
	 * 保持Session不过期
	 */
    public function actionKeepSessionActive($appkey=null)
    {
        foreach($this->getTaobaoShopList() as $taobaoShop)
        {
            if($appkey!==null && $appkey!=$taobaoShop['application_key'])
            continue;

            $response=$this->getTaobaoClient($taobaoShop)->execute('taobao.kfc.keyword.search',array('content'=>'keep in touch!'));
            if($response->isSuccess())
            echo("[". date('c') ."] ".$taobaoShop['nick']." keep session active request send success \n");
            else
            echo("[". date('c') ."] ".$response->getMsg().": ". $response->getSubMsg()." \n");
        }
    }

    /**
     * 检测发货后是否有快递方式发生改变，有则同步到淘宝
     * @param unknown_type $appkey
     */
	public  function actionSyncChangeDeliverySend($appkey = null){
		static $codeMap=array
		(
				'E邮宝快递' => 'EMS',
				'EMS快递' => 'EMS',
				'顺丰快递' => 'SF',
				'万象物流' => 'OTHER',
				'龙邦快递' => 'LBEX',
				'圆通快递' => 'YTO',
				'申通快递' => 'STO',
				'汇通快递' => 'HTKY',
				'中通快递' => 'ZTO',
				'宅急送快递' => 'ZJS',
				'韵达快递' => 'YUNDA',
				'顺丰（陆运）'=>'SF',
				'EMS经济快递'=>'EYB',
				'邮政国内小包' => 'POSTB',
				'天天快递' =>'TTKDEX',
				'全一快递' =>'UAPEX',
				'宅急便' => 'YCT',
		);
		static $companyMap=array
		(
				 'EMS'=>'EMS',
				 'SF'=> '顺丰速运',
				 'LBEX'=>'龙邦',
				 'YTO'=>'圆通速递',
				 'STO'=>'申通E物流',
				 'HTKY'=>'汇通快运',
				 'ZTO'=>'中通速递',
				 'ZJS'=>'宅急送',
				 'YUNDA'=>'韵达快运',
				 'POSTB'=>'邮政国内小包',
				 'TTKDEX' =>'天天快递',
				 'UAPEX' =>'全一快递',
				 'YCT' =>'宅急便',
		);
		// 仓库邮件
		$facilitly_email = array(
				//怀轩上海仓
				'12768420' => array(
						array('name' => '施旭舟', 'email' => 'xzshi@i9i8.com'),
						array('name' => '谭磊', 'email' => 'ltan@i9i8.com'),
				),
				//东莞仓
				'19568548' => array(
						array('name' => '东莞物流', 'email' => 'dgwl@i9i8.com'),
				),
				//上海仓
				'19568549' => array(
						array('name' => '上海物流', 'email' => 'shwl@i9i8.com'),
				),
				//北京仓
				'42741887' => array(
						array('name' => '王宝兵', 'email' => 'bbwang@i9i8.com'),
				),
				//宁波仓
				'43981157' => array(
						array('name' => '鞠倩', 'email' => 'qju@i9i8.com'),
						array('name' => '洪国建', 'email' => 'gjhong@i9i8.com'),
				),
		);
		$start = date("Y-m-d H:i:s",mktime($currenthour-24));
		// 数据库连接
		$db=Yii::app()->getDb();
		$db->setActive(true);
		//查询发货后快递方式被修改的订单
		//shipping_status='WAIT_BUYER_CONFIRM_GOODS'所代表的都是已发货且物流信息已同步的订单
		$sql1="
		select
		mp.mapping_id, mp.taobao_order_sn as mp_taobao_order_sn, mp.shipping_status as mp_shipping_status, mp.type,
		o.shipping_name, o.order_sn, o.distribution_purchase_order_sn, o.taobao_order_sn, c.bill_no, o.facility_id, o.party_id, 
		ae.action_note, ae.action_time, o.order_id,
		f.facility_name
		from
		ecs_taobao_order_mapping as mp
		inner join ecs_order_info as o ON o.order_id = mp.order_id AND o.order_status = 1 AND o.pay_status = 2 AND o.shipping_status = 1
		inner join ecs_carrier_bill as c ON c.bill_id = o.carrier_bill_id
		inner join romeo.facility f on f.facility_id = o.facility_id
		inner join ecs_order_action ae ON ae.order_id = o.order_id 
		where
		ae.action_note like '物流修改快递 :%'
		and 
		ae.action_time > '{$start}'
		and 
		mp.shipping_status='WAIT_BUYER_CONFIRM_GOODS'  and mp.application_key=:key
		";
		// 淘宝上找不到的订单
		$sql3="update ecs_taobao_order_mapping set shipping_status='TRADE_FINISHED' where mapping_id = :id";
		$facility_id = '';
		foreach($this->getTaobaoShopList() as $taobaoShop)
		{
			if($appkey!==null && $appkey!=$taobaoShop['application_key'])
				continue;
	
			echo("[".date('c')."] ".$taobaoShop['nick']. " changed_delivery send sync start! \n");
			$order_list=$db->createCommand($sql1)->bindParam(':key',$taobaoShop['application_key'])->queryAll();
			$order_list1 = $order_list ; 
			$order_list2 = $order_list ;
			foreach ($order_list1 as $key1 => $order1){//按照备注的时间来删减有相同order_id的备注记录,保留时间上最近的那条备注
				foreach ($order_list2 as $key2 =>$order2){
					if($order1['order_id'] == $order2['order_id']){
						if($order1['action_time']<$order2['action_time']){
							unset($order_list[$key1]);
							unset($order_list1[$key1]);
							unset($order_list2[$key1]);
						}
						else if($order1['action_time']>$order2['action_time']){
							unset($order_list[$key2]);
							unset($order_list1[$key2]);
							unset($order_list2[$key2]);
						}
					}
				}
			}
			echo("| total of order number is ". count($order_list) ." \n");
			$html = array();
			foreach($order_list as $order)
			{
				$fields = 'tid,company_name,out_sid';
				$request1 = array(
						'fields' =>$fields,
						'tid' =>$order['type']=='fenxiao'?$order['distribution_purchase_order_sn']:$order['taobao_order_sn'], 		
				);
				try
				{
					$response = null ;
					// 取得该订单快递方式
					$response=$this->getTaobaoClient($taobaoShop)->execute('taobao.logistics.orders.get',$request1);
					if($response->isSuccess())
					{
						foreach ($response->shippings->shipping as $key => $r){
							$company_name = $r->company_name;
							$out_sid = $r ->out_sid;
						}
						if($company_name != $companyMap[$codeMap[$order['shipping_name']]]||$out_sid != $order['bill_no']){//如果与taobao后台快递方式，面单号一致，则略过
							$facility_id = $this->facility_convert($order['facility_id']);
							if (!array_key_exists ($facility_id, $html)) {
								$html[$facility_id] = "";
							}
							echo("| (taobao: ". $order['taobao_order_sn'] .") delivery send \n");
							// 多个快递面单
							$bill_no = $order['bill_no'];
							if(strpos($bill_no, ',') !== false){
								$bill_no = current(explode(',', $bill_no));
							}
						
							$request = array(
									// 分销订单, 用分销采购订单号发货
									'tid'=>$order['type']=='fenxiao'?$order['distribution_purchase_order_sn']:$order['taobao_order_sn'],
									'company_code'=>isset($codeMap[$order['shipping_name']])?$codeMap[$order['shipping_name']]:'OTHER',
									'out_sid'=>$bill_no,
							);
						
						
							// 请求淘宝发货
							try
							{
								$response = null ;
								// 取得卖家地址库ID
						
								$response=$this->getTaobaoClient($taobaoShop)->execute('taobao.logistics.consign.resend',$request);
						
								// var_dump($response);
								if($response->isSuccess())
								{
									echo("|  - the order changed_delivery send success!  订单号：".$request['tid']."快递公司：".$request['company_code']."快递面单号：".$request['out_sid']. '' . "\n");
								}
								// 已经手动发货了
								else if($response->getSubCode()=='isv.logistics-offline-service-error:B04')
								{
									echo("|  - the order has been send!". '' ."\n");
								}
								// 淘宝上面已经不存在的订单
								else if($response->sub_code=='isv.logistics-offline-service-error:B01')
								{
									$update=$db->createCommand($sql3)->bindValue(':id',$order['mapping_id'])->execute();
									echo("|  - the order has been send! update TRADE_FINISHED result: ". $update ."\n");
								}
								//淘宝快递方式与快递单号类型不匹配  运单号不符合规则 //发送相关邮件
								else if ($response->sub_code == 'isv.logistics-offline-service-error:B60') {
									$html[$facility_id] = $html[$facility_id] . " 淘宝订单号： " . $order['taobao_order_sn'] . " 发货仓库： ".$order['facility_name']
									. " 快递方式： " . $order['shipping_name'] . " 快递单号： " . $order['bill_no'] . "\n";
								}
								// 其他错误
								else
								{
									echo("|  - has error: ".$response->sub_code.", ".$response->sub_msg." \n");
								}
							}
							catch (Exception $e)
							{
								echo("|  - has exception: ". $e->getMessage() . "\n");
							}
						}
						
					}
					else if($response->getSubCode()=='isv.invalid-parameter:trade_id:P07')
					{
						echo("|  参数：trade_id:P07无效，格式不对、非法值、越界等\n");
					}
					// 其他错误
					else
					{
						echo("|  - has error: ".$response->sub_code.", ".$response->sub_msg." \n");
					}
				}
				catch (Exception $e)
				{
					echo("|  - has exception: ". $e->getMessage() . "\n");
				}
				
		
				usleep(500000);
			}
		
			if($order_list!==array())
				echo("[".date('c')."] ".$taobaoShop['nick']. " changed_delivery send sync done! \n\n");
		
			// session过期邮件通知
			if(isset($response) && $response->getCode()=='27')
			{
				try
				{
					$mail=Yii::app()->getComponent('mail');
					$mail->Subject="淘宝店铺 ". $taobaoShop['nick'] ." Session过期";
					$mail->ClearAddresses();
					$mail->Body="
					该淘宝店铺的session过期了，不能进行同步动作，请按下面的链接取得session后更新到erp中。
					http://auth.open.taobao.com/?appkey=".$taobaoShop['params']['app_key']. " \n
					http://container.open.taobao.com/container?appkey=".$taobaoShop['params']['app_key'];
					$mail->AddAddress('hbai@leqee.com', '柏壑');
					$mail->AddAddress('mjzhou@leqee.com', '周明杰');
					$mail->AddAddress('qxu@leqee.com', '许强');
					$mail->AddAddress('yfhu@leqee.com', '胡一帆');				
					$mail->send();
				}
				catch (Exception $e){
				}
			}
			//发送预警邮件
			if (!empty($html)) {
				try {
					$mail=Yii::app()->getComponent('mail');
					$mail->Subject="淘宝店铺 ". $taobaoShop['nick'] ." 快递单号与发货类型不匹配，请仓库人员检查及修改";
					foreach ($html as $key => $value) {
						if (!empty($value)) {
							$mail->ClearAddresses();
							$mail->Body = "";
					        $mail->AddAddress('hbai@leqee.com', '柏壑');
					      //  $mail->AddAddress('mjzhou@leqee.com', '周明杰');
					        $mail->AddAddress('qxu@leqee.com', '许强');
					        $mail->AddAddress('yfhu@leqee.com', '胡一帆');	
							$mail->Body = $value;
							foreach ($facilitly_email[$key] as $item){
								$mail->AddAddress($item['email'], $item['name']);
							}
							$mail->send();
						}
					}
				} catch (Exception $e){
				}
			}
		}
	}
    /**
	 * 同步发货
	 */
    public function actionSyncDeliverySend($appkey=null)
    {
        // 晚上不需要同步发货
        $h=date('H');
        if($h<9 || $h>22) { return; }

        static $codeMap=array
        (
        'E邮宝快递' => 'EMS',
        'EMS快递' => 'EMS',
        '顺丰快递' => 'SF',
        '顺丰（陆运）'=>'SF',
		'EMS经济快递'=>'EYB',
        '万象物流' => 'OTHER',
        '龙邦快递' => 'LBEX',
        '圆通快递' => 'YTO',
        '申通快递' => 'STO',
        '汇通快递' => 'HTKY',
        '中通快递' => 'ZTO',
        '宅急送快递' => 'ZJS',
        '韵达快递' => 'YUNDA',
        '邮政国内小包' => 'POSTB',
        '天天快递' => 'TTKDEX',
        '全一快递' => 'UAPEX',
        '宅急便' => 'YCT',
        );
        
        static $pauseMap=array
        (
        '汇通快递' => '18:30:00',
        '圆通快递' => '18:30:00',
        '顺丰快递' => '18:30:00',
        '顺丰（陆运）'=>'18:30:00',
        '中通快递' => '19:00:00',
        '申通快递' => '18:30:00',
        );
        
       	static $pauseFacility = array
       	(
	       	"电商服务上海仓" => '19568549',
	       	"电商服务上海仓_2（原电商服务杭州仓" => '22143847',
	       	"乐其上海仓_2（原乐其杭州仓)" => '22143846',
	       	"康贝分销上海仓" => '81569822',
	       	"通用商品上海仓" => '83077348',
	       	"贝亲青浦仓" => '24196974',
	       	"乐其北京仓" => '42741887',
	       	"电商服务北京仓" => '79256821',
	       	"通用商品北京仓" => '83077350',
	       	"电商服务东莞仓" => '19568548',
	       	"乐其东莞仓" => '3580047',
	       	"东莞乐贝仓" => '49858449',
	       	"电商服务东莞仓2" => '76065524',
	       	"通用商品东莞仓" => '83077349'
       	);
        
        // 仓库邮件
        $facilitly_email = array(
            //怀轩上海仓
            '12768420' => array(
                array('name' => '施旭舟', 'email' => 'xzshi@i9i8.com'),
                array('name' => '谭磊', 'email' => 'ltan@i9i8.com'),
            ),
            //东莞仓
            '19568548' => array(
                array('name' => '东莞物流', 'email' => 'dgwl@i9i8.com'),
            ),
            //上海仓
            '19568549' => array(
                array('name' => '上海物流', 'email' => 'shwl@i9i8.com'),
            ),
            //北京仓
            '42741887' => array(
                array('name' => '王宝兵', 'email' => 'bbwang@i9i8.com'),
            ),
             //宁波仓
            '43981157' => array(
                array('name' => '鞠倩', 'email' => 'qju@i9i8.com'),
                array('name' => '洪国建', 'email' => 'gjhong@i9i8.com'),
            ),
        );
        // 数据库连接
        $db=Yii::app()->getDb();
        $db->setActive(true);

        // 查询已发货未同步订单
        $sql1="
			select
				mp.mapping_id, mp.taobao_order_sn as mp_taobao_order_sn, mp.shipping_status as mp_shipping_status, mp.type,
				o.shipping_name, p.hour, p.minute, o.order_sn, o.distribution_purchase_order_sn, o.taobao_order_sn, c.bill_no, o.facility_id, o.party_id,
                f.facility_name, FROM_UNIXTIME(o.shipping_time) as shipping_time
			from
				ecs_taobao_order_mapping as mp use index(application_key)
				inner join ecs_order_info as o ON o.order_id = mp.order_id AND o.order_status = 1 AND o.pay_status = 2 AND o.shipping_status = 1
				inner join ecs_carrier_bill as c ON c.bill_id = o.carrier_bill_id
                inner join romeo.facility f on f.facility_id = o.facility_id
        		left join ecs_shipping_pause_sync_time p on o.shipping_id=p.shipping_id
			where
				mp.shipping_status=''  and mp.application_key=:key and o.order_time>=DATE_ADD(now(),INTERVAL-2 month)
		";

        // 更新发货状态
        $sql2="update ecs_taobao_order_mapping set shipping_status='WAIT_BUYER_CONFIRM_GOODS' where mapping_id = :id";
        // 淘宝上找不到的订单
        $sql3="update ecs_taobao_order_mapping set shipping_status='TRADE_FINISHED' where mapping_id = :id";
        $facility_id = '';
        foreach($this->getTaobaoShopList() as $taobaoShop)
        {
            if($appkey!==null && $appkey!=$taobaoShop['application_key'])
                continue;
                
			if($appkey == "62f6bb9e07d14157b8fa75824400981f")
			{
			   echo("[".date('c')."] ".$taobaoShop['nick']. " stop sync! \n");	
			   continue;	
			}                

            echo("[".date('c')."] ".$taobaoShop['nick']. " delivery send sync start! \n");
            $order_list=$db->createCommand($sql1)->bindParam(':key',$taobaoShop['application_key'])->queryAll();
            echo("| total of order number is ". count($order_list) ." \n");
            $html = array();
            
            //汇通、圆通、顺丰暂停同步时间
            foreach($order_list as $order)
            {
             
            	if($appkey != "f2c6d0dacf32102aa822001d0907b75a") {
            		print_r("启用延时同步发货");
					$shipping_name = $order['shipping_name'];
	                $shipping_time = $order['shipping_time'];
	                $current_time = date("Y-m-d H:m:s",time());
	                $tid = $order['type']=='fenxiao'?$order['distribution_purchase_order_sn']:$order['taobao_order_sn'];
	                $hour = $order['hour'];
	                $minute = $order['minute'];
	                $facility_id = $order['facility_id'];
	                
	                if($hour!=null && $minute!=null && in_array($facility_id,$pauseFacility)) {
	                	$hour =sprintf("%2d",$hour);
	                	$minute = sprintf("%2d",$minute);
	                	$pause_time = "{$hour}:{$minute}";
	                } else {
	                	$pause_time = null;
	                }
	    
					$reopen_time = substr(date("Y-m-d H:m:s",strtotime($current_time) + 24*60*60),0,11) . '09:00:00';
					
					if(isset($pause_time)) {
						$pause_time = substr($current_time,0,11) . $pause_time;
						if($shipping_time>$pause_time && $current_time<$reopen_time) {
							print_r("订单{$tid}发货时间{$shipping_time}超过当日截止时间{$pause_time}将于{$reopen_time}恢复同步物流信息");
							continue;
						}
					}

            	}

				            	
            	$facility_id = $this->facility_convert($order['facility_id']);
                if (!array_key_exists ($facility_id, $html)) {
                    $html[$facility_id] = "";
                }
                echo("| (taobao: ". $order['taobao_order_sn'] .") delivery send \n");
                
                $tid = $order['type']=='fenxiao'?$order['distribution_purchase_order_sn']:$order['taobao_order_sn'];
                
                // 多个快递面单
                $bill_nos = $order['bill_no'];
                $is_split = 0;// 是否拆分
                $oids = array();// 子订单
                if(strpos($bill_nos, ',') !== false){
                	echo("| (taobao: ". $order['taobao_order_sn'] .") multy_shipment \n");
                	
                	$bill_nos = explode(',', $bill_nos);
                	$is_split = 1;
                	
                	if($order['type']=='fenxiao') {
                		echo("| (taobao: ". $order['taobao_order_sn'] .") fenxiao_shipment \n");
                		
                		$sql = "select fenxiao_sub_id from ecshop.sync_taobao_fenxiao_order_goods where fenxiao_parent_id='{$order['distribution_purchase_order_sn']}' ";
                	} else {
                		echo("| (taobao: ". $order['taobao_order_sn'] .") zhixiao_shipment \n");
                		
                		$sql = "select oid from ecshop.sync_taobao_order_goods where tid='{$tid}' ";
                	}
            		$oids = $db->createCommand($sql)->queryColumn();
            		
                } else {
                	echo("| (taobao: ". $order['taobao_order_sn'] .") simple_shipment \n");
                	$bill_nos = array($bill_nos);
                }
                
				var_dump('$oids');var_dump($oids);
				var_dump('$bill_nos');var_dump($bill_nos);
            	$to_deal_bills = array();
            	foreach($bill_nos as $key=>$bill_no) {
            		$to_deal_bills[$key]['bill_no'] = $bill_no;
            		// 赋值子订单
            		if($is_split) {
            			// 如果最后一条，则把剩下的子订单拼装成字符串
            			if(($key == count($bill_nos)-1) && (count($bill_nos) < count($oids))) {
            				 $to_deal_bills[$key]['oid'] = '';
            				 for($i=$key;$i<count($oids);$i++) {
            				 	$to_deal_bills[$key]['oid'] .= $oids[$i].',';
            				 }
            				 var_dump($to_deal_bills[$key]['oid']);
            				 $to_deal_bills[$key]['oid'] = substr($to_deal_bills[$key]['oid'],0,strlen($to_deal_bills[$key]['oid'])-1);
            			} else {
            				$default_oid = isset($oids[0])?$oids[0]:'';
            				$to_deal_bills[$key]['oid'] = isset($oids[$key])?$oids[$key]:$default_oid;
            			}
            		} else {
            			$to_deal_bills[$key]['oid'] = '';
            		}
            	}
            	
            	var_dump('$to_deal_bills');var_dump($to_deal_bills);
                foreach($to_deal_bills as $key=>$to_deal_bill) {
            		$request = array(
	                    // 分销订单, 用分销采购订单号发货
	                    'applicationKey' =>$taobaoShop['application_key'],
	                    'tid'=>$tid,
	                    'sub_tid'=>'',//$to_deal_bill['oid'],
	                    'is_split'=>0,//$is_split,
	                    'company_code'=>isset($codeMap[$order['shipping_name']])?$codeMap[$order['shipping_name']]:'OTHER',
	                    'out_sid'=>$to_deal_bill['bill_no'],
	                    'username'=>JSTUsername,'password'=>md5(JSTPassword),
                    );

	                var_dump($request);//continue;
	
	                // 请求淘宝发货
	                try
	                {
	                    $response = null ;
	                    // 取得卖家地址库ID
	
	                    // 远程服务
	                    $client=Yii::app()->getComponent('syncJushita')->SyncTaobaoService;
	                    $response = $client->SyncTaobaoOrderDeliverySend($request)->return;
	                    if(isset($response->shipping) && isset($response->shipping->isSuccess) && $response->shipping->isSuccess)
	                    {
	                        $update=$db->createCommand($sql2)->bindValue(':id',$order['mapping_id'])->execute();
	                        echo("|  - the order delivery send success! update WAIT_BUYER_CONFIRM_GOODS result: ". $update . "订单号：".$request['tid']."快递公司：".$request['company_code']."快递面单号：".$request['out_sid']."\n");
	                    }
	                    // 已经手动发货了
	                    else if(isset($response->subCode) && $response->subCode=='isv.logistics-offline-service-error:B04')
	                    {
	                        $update=$db->createCommand($sql2)->bindValue(':id',$order['mapping_id'])->execute();
	                        echo("|  - the order has been send! update WAIT_BUYER_CONFIRM_GOODS result: ". $update ."\n");
	                    }
	                    // 淘宝上面已经不存在的订单
	                    else if(isset($response->subCode) && $response->subCode=='isv.logistics-offline-service-error:B01')
	                    {
	                    	$update=$db->createCommand($sql3)->bindValue(':id',$order['mapping_id'])->execute();
	                        echo("|  - the order has been send! update TRADE_FINISHED result: ". $update ."\n");
	                    }
	                    //淘宝快递方式与快递单号类型不匹配  运单号不符合规则 //发送相关邮件
	                    else if (isset($response->subCode) && $response->subCode == 'isv.logistics-offline-service-error:B60') {
	                        $html[$facility_id] = $html[$facility_id] . " 淘宝订单号： " . $order['taobao_order_sn'] . " 发货仓库： ".$order['facility_name']
	                            . " 快递方式： " . $order['shipping_name'] . " 快递单号： " . $order['bill_no'] . "同步发货失败\n";
	                    }
	                    // 其他错误
	                    else if (isset($response->subCode) && isset($response->msg))
	                    {  
	                        echo("|  - has error: ".$response->subCode.", ".$response->msg." \n");
	                    } else {
	                    	echo("|  - has error: \n");
	                    	var_dump($response);
	                    }
	                }
	                catch (Exception $e)
	                {
	                    echo("|  - has exception: ". $e->getMessage() . "\n");
	                }
	
	                usleep(500000);
	                // 先不用拆分
	                break;
	            }
            }

            if($order_list!==array())
            echo("[".date('c')."] ".$taobaoShop['nick']. " delivery send sync done! \n\n");

          // session过期邮件通知
            if(isset($response) && isset($response->code) && $response->code=='27')
            {
                try
                {
                    $mail=Yii::app()->getComponent('mail');
                    $mail->Subject="淘宝店铺 ". $taobaoShop['nick'] ." Session过期";
                    $mail->Body="
						该淘宝店铺的session过期了，不能进行同步动作，请按下面的链接取得session后更新到erp中。
						http://auth.open.taobao.com/?appkey=".$taobaoShop['params']['app_key']. " \n
						http://container.open.taobao.com/container?appkey=".$taobaoShop['params']['app_key'];
					$mail->AddAddress('hbai@leqee.com', '柏壑');
					$mail->AddAddress('mjzhou@leqee.com', '周明杰');
					$mail->AddAddress('qxu@leqee.com', '许强');
					$mail->AddAddress('yfhu@leqee.com', '胡一帆');	
					$mail->AddAddress('ljzhou@leqee.com', '周灵杰');
					$mail->AddAddress('wjzhu@leqee.com', '朱伟晋');	
                    $mail->send();
                }
                catch (Exception $e){}
            }
            //发送预警邮件
            if (!empty($html)) {
                try {
                    $mail=Yii::app()->getComponent('mail');
                    $mail->Subject="淘宝店铺 ". $taobaoShop['nick'] ." 快递单号与发货类型不匹配，请仓库人员检查及修改";
                    foreach ($html as $key => $value) {
                        if (!empty($value)) {
                            $mail->ClearAddresses();
                            $mail->Body = "";
					        $mail->AddAddress('hbai@leqee.com', '柏壑');
					        $mail->AddAddress('mjzhou@leqee.com', '周明杰');
					        $mail->AddAddress('qxu@leqee.com', '许强');
					        $mail->AddAddress('yfhu@leqee.com', '胡一帆');	
					        $mail->AddAddress('ljzhou@leqee.com', '周灵杰');
				        	$mail->AddAddress('wjzhu@leqee.com', '朱伟晋');	
							$mail->AddAddress('ytchen@leqee.com', '陈艳婷');
                            $mail->Body = $value;
                            if(isset($facilitly_email[$key])){
	                            foreach ($facilitly_email[$key] as $item){
	                                $mail->AddAddress($item['email'], $item['name']);
	                            }
                            }
                            $mail->send();
                        }
                    }
                } catch (Exception $e){}
            }
        }
    }
    
    /**
	 * 同步发货
	 */
    public function actionSyncDeliverySendNew($appkey=null)
    {
        // 晚上不需要同步发货
        $h=date('H');
        if($h<9 || $h>22) { return; }

        static $codeMap=array
        (
        'E邮宝快递' => 'EMS',
        'EMS快递' => 'EMS',
        '顺丰快递' => 'SF',
        '顺丰（陆运）'=>'SF',
		'EMS经济快递'=>'EYB',
        '万象物流' => 'OTHER',
        '龙邦快递' => 'LBEX',
        '圆通快递' => 'YTO',
        '申通快递' => 'STO',
        '汇通快递' => 'HTKY',
        '中通快递' => 'ZTO',
        '宅急送快递' => 'ZJS',
        '韵达快递' => 'YUNDA',
        '邮政国内小包' => 'POSTB',
        '天天快递' => 'TTKDEX',
        '全一快递' => 'UAPEX',
        '宅急便' => 'YCT',
        );
        
        static $pauseMap=array
        (
        '汇通快递' => '18:30:00',
        '圆通快递' => '18:30:00',
        '顺丰快递' => '18:30:00',
        '顺丰（陆运）'=>'18:30:00',
        '中通快递' => '19:00:00',
        '申通快递' => '18:30:00',
        );
        
        static $pauseFacility = array
       	(
	       	"电商服务上海仓" => '19568549',
	       	"电商服务上海仓_2（原电商服务杭州仓" => '22143847',
	       	"乐其上海仓_2（原乐其杭州仓)" => '22143846',
	       	"康贝分销上海仓" => '81569822',
	       	"通用商品上海仓" => '83077348',
	       	"贝亲青浦仓" => '24196974',
	       	"乐其北京仓" => '42741887',
	       	"电商服务北京仓" => '79256821',
	       	"通用商品北京仓" => '83077350',
	       	"电商服务东莞仓" => '19568548',
	       	"乐其东莞仓" => '3580047',
	       	"东莞乐贝仓" => '49858449',
	       	"电商服务东莞仓2" => '76065524',
	       	"通用商品东莞仓" => '83077349'
       	);
        
        // 仓库邮件
        $facilitly_email = array(
            //怀轩上海仓
            '12768420' => array(
                array('name' => '施旭舟', 'email' => 'xzshi@i9i8.com'),
                array('name' => '谭磊', 'email' => 'ltan@i9i8.com'),
            ),
            //东莞仓
            '19568548' => array(
                array('name' => '东莞物流', 'email' => 'dgwl@i9i8.com'),
            ),
            //上海仓
            '19568549' => array(
                array('name' => '上海物流', 'email' => 'shwl@i9i8.com'),
            ),
            //北京仓
            '42741887' => array(
                array('name' => '王宝兵', 'email' => 'bbwang@i9i8.com'),
            ),
             //宁波仓
            '43981157' => array(
                array('name' => '鞠倩', 'email' => 'qju@i9i8.com'),
                array('name' => '洪国建', 'email' => 'gjhong@i9i8.com'),
            ),
        );
        // 数据库连接
        $db=Yii::app()->getDb();
        $db->setActive(true);

        // 查询已发货未同步订单
        $sql1="
			select
				mp.mapping_id, mp.taobao_order_sn as mp_taobao_order_sn, mp.shipping_status as mp_shipping_status, mp.type,o.shipping_id,cb.carrier_id,o.order_status,
				o.shipping_name, p.hour, p.minute, o.order_sn, o.distribution_purchase_order_sn, o.taobao_order_sn, s.tracking_number, o.facility_id, o.party_id,
                f.facility_name, FROM_UNIXTIME(o.shipping_time) as shipping_time
			from
				ecshop.ecs_taobao_order_mapping as mp use index(application_key)
				inner join ecshop.ecs_order_info as o ON o.order_id = mp.order_id AND o.order_status = 1  AND o.pay_status = 2 AND o.shipping_status = 1
				inner join romeo.order_shipment as os ON os.ORDER_ID = convert(o.order_id using utf8)
				inner join romeo.shipment as s ON s.shipment_id = os.shipment_id
                inner join romeo.facility f on f.facility_id = o.facility_id
                inner join ecshop.ecs_carrier_bill cb on o.carrier_bill_id = cb.bill_id
        		left join ecshop.ecs_shipping_pause_sync_time p on o.shipping_id=p.shipping_id
			where
				mp.shipping_status=''  and mp.application_key=:key and o.order_time>=DATE_ADD(now(),INTERVAL-2 month) and s.tracking_number is not null 
		";

        // 更新发货状态
        $sql2="update ecs_taobao_order_mapping set shipping_status='WAIT_BUYER_CONFIRM_GOODS' where mapping_id = :id";
        // 淘宝上找不到的订单
        $sql3="update ecs_taobao_order_mapping set shipping_status='TRADE_FINISHED' where mapping_id = :id";
        $facility_id = '';
        foreach($this->getTaobaoShopList() as $taobaoShop)
        {
            if($appkey!==null && $appkey!=$taobaoShop['application_key'])
                continue;
                
			if($appkey == "62f6bb9e07d14157b8fa75824400981f")
			{
			   echo("[".date('c')."] ".$taobaoShop['nick']. " stop sync! \n");	
			   continue;	
			}                

            echo("[".date('c')."] ".$taobaoShop['nick']. " delivery send sync start! \n");
            $order_list=Yii::app()->getDb()->createCommand($sql1)->bindParam(':key',$taobaoShop['application_key'])->queryAll();
            echo("| total of order number is ". count($order_list) ." \n");
            $html = array();
            
            //汇通、圆通、顺丰暂停同步时间
            foreach($order_list as $key=>$order)
            {
             	
            	if($appkey != "f2c6d0dacf32102aa822001d0907b75a") {
					$shipping_name = $order['shipping_name'];
	                $shipping_time = $order['shipping_time'];
	                $current_time = date("Y-m-d H:m:s",time());
	                $tid = $order['type']=='fenxiao'?$order['distribution_purchase_order_sn']:$order['mp_taobao_order_sn'];
	                $hour = $order['hour'];
	                $minute = $order['minute'];
	                $facility_id = $order['facility_id'];
	                
	                if($hour!=null && $minute!=null && in_array($facility_id,$pauseFacility)) {
	                	$hour =sprintf("%2d",$hour);
	                	$minute = sprintf("%2d",$minute);
	                	$pause_time = "{$hour}:{$minute}";
	                } else {
	                	$pause_time = null;
	                }
	    
					$reopen_time = substr(date("Y-m-d H:m:s",strtotime($current_time) + 24*60*60),0,11) . '09:00:00';
					
					if(isset($pause_time)) {
						$pause_time = substr($current_time,0,11) . $pause_time;
						if($shipping_time>$pause_time && $current_time<$reopen_time) {
							print_r("订单{$tid}发货时间{$shipping_time}超过当日截止时间{$pause_time}将于{$reopen_time}恢复同步物流信息");
							continue;
						}
					}

            	}

				            	
            	$facility_id = $this->facility_convert($order['facility_id']);
                if (!array_key_exists ($facility_id, $html)) {
                    $html[$facility_id] = "";
                }
                echo("| (taobao: ". $order['mp_taobao_order_sn'] .") delivery send \n");
                
                $tid = $order['type']=='fenxiao'?$order['distribution_purchase_order_sn']:$order['mp_taobao_order_sn'];
                
                // 多个快递面单
                $tracking_numbers = $order['tracking_number'];
                $is_split = 0;// 是否拆分
                $oids = array();// 子订单
                if(strpos($tracking_numbers, ',') !== false){
                	echo("| (taobao: ". $order['mp_taobao_order_sn'] .") multy_shipment \n");
                	
                	$tracking_numbers = explode(',', $tracking_numbers);
                	$is_split = 1;
                	
                	if($order['type']=='fenxiao') {
                		echo("| (taobao: ". $order['mp_taobao_order_sn'] .") fenxiao_shipment \n");
                		
                		$sql = "select fenxiao_sub_id from ecshop.sync_taobao_fenxiao_order_goods where fenxiao_parent_id=:tid ";
                	} else {
                		echo("| (taobao: ". $order['mp_taobao_order_sn'] .") zhixiao_shipment \n");
                		
                		$sql = "select oid from ecshop.sync_taobao_order_goods where tid=:tid ";
                	}
            		$oids = $db->createCommand($sql)->bindParam(':tid',$tid)->queryColumn();
            		
                } else {
                	echo("| (taobao: ". $order['mp_taobao_order_sn'] .") simple_shipment \n");
                	$tracking_numbers = array($tracking_numbers);
                }
                
            	$to_deal_bills = array();
            	foreach($tracking_numbers as $key=>$tracking_number) {
            		$to_deal_bills[$key]['tid'] = $tid;
            		$to_deal_bills[$key]['tracking_number'] = $tracking_number;
            		$to_deal_bills[$key]['shipping_name'] = $order['shipping_name'];
            		// 赋值子订单
            		if($is_split) {
            			$to_deal_bills[$key]['is_split'] = 1;
            			// 如果最后一条，则把剩下的子订单拼装成字符串
            			if(($key == count($tracking_numbers)-1) && (count($tracking_numbers) < count($oids))) {
            				 $to_deal_bills[$key]['oid'] = '';
            				 for($i=$key;$i<count($oids);$i++) {
            				 	$to_deal_bills[$key]['oid'] .= $oids[$i].',';
            				 }
            				 $to_deal_bills[$key]['oid'] = substr($to_deal_bills[$key]['oid'],0,strlen($to_deal_bills[$key]['oid'])-1);
            			} else {
            				$default_oid = isset($oids[0])?$oids[0]:'';
            				$to_deal_bills[$key]['oid'] = isset($oids[$key])?$oids[$key]:$default_oid;
            			}
            		} else {
            			$to_deal_bills[$key]['oid'] = '';
            			$to_deal_bills[$key]['is_split'] = 0;
            		}
            	}
            	
                foreach($to_deal_bills as $key=>$to_deal_bill) {
            		$request = array(
	                    // 分销订单, 用分销采购订单号发货
	                    'applicationKey' =>$taobaoShop['application_key'],
	                    'tid'=>$to_deal_bill['tid'],
	                    'sub_tid'=>$to_deal_bill['oid'],
	                    'is_split'=>$to_deal_bill['is_split'],
	                    'company_code'=>isset($codeMap[$to_deal_bill['shipping_name']])?$codeMap[$to_deal_bill['shipping_name']]:'OTHER',
	                    'out_sid'=>$to_deal_bill['tracking_number'],
	                    'username'=>JSTUsername,'password'=>md5(JSTPassword),
                    );
					var_dump('$to_deal_bill:');var_dump($to_deal_bill);
	                var_dump('$request:');var_dump($request);//continue;
	
	                // 请求淘宝发货
	                try
	                {
	                    $response = null ;
	                    // 取得卖家地址库ID
	
	                    // 远程服务
	                    $client=Yii::app()->getComponent('syncJushita')->SyncTaobaoService;
	                    $response = $client->SyncTaobaoOrderDeliverySend($request)->return;
	                    if(isset($response->shipping) && isset($response->shipping->isSuccess) && $response->shipping->isSuccess)
	                    {
	                        $update=Yii::app()->getDb()->createCommand($sql2)->bindValue(':id',$order['mapping_id'])->execute();
	                        echo("|  - the order delivery send success! update WAIT_BUYER_CONFIRM_GOODS result: ". $update . "订单号：".$request['tid']."快递公司：".$request['company_code']."快递面单号：".$request['out_sid']."\n");
	                    }
	                    // 已经手动发货了
	                    else if(isset($response->subCode) && $response->subCode=='isv.logistics-offline-service-error:B04')
	                    {
	                        $update=Yii::app()->getDb()->createCommand($sql2)->bindValue(':id',$order['mapping_id'])->execute();
	                        echo("|  - the order has been send! update WAIT_BUYER_CONFIRM_GOODS result: ". $update ."订单号：".$request['tid'] ."\n");
	                    }
	                    // 淘宝上面已经不存在的订单
	                    else if(isset($response->subCode) && $response->subCode=='isv.logistics-offline-service-error:B01')
	                    {
	                    	$update=Yii::app()->getDb()->createCommand($sql3)->bindValue(':id',$order['mapping_id'])->execute();
	                        echo("|  - the order has been send! update TRADE_FINISHED result: ". $update ."订单号：".$request['tid'] ."\n");
	                    }
	                    //淘宝快递方式与快递单号类型不匹配  运单号不符合规则 //发送相关邮件
	                    else if (isset($response->subCode) && $response->subCode == 'isv.logistics-offline-service-error:B60') {
	                    	$sql = "select count(1) from ecshop.thermal_express_mailnos where tracking_number='{$order['tracking_number']}' and status != 'N' and shipping_id = '{$order['shipping_id']}'";
	                    	$is_thermal = Yii::app()->getDb()->createCommand($sql)->queryScalar();
	                    	if($order['shipping_id']=="115"&&substr($order['tracking_number'],0,3)=="689"){
	                    		//中通快递在淘宝端没有689开头的规则，但据仓库反映收到一批689开头的中通面单，ERP系统中认同，这部分订单不能实现同步！！！
	                    		echo("|  - the order 中通快递  面单号以689开头! 订单号：".$request['tid'] ."\n");
	                    	}else if($is_thermal!=0){
	                    		echo("|  - the order 快递：".$order['shipping_name'].", 面单号:".$order['tracking_number']." ，热敏!taobao不识别。。 订单号：".$request['tid'] ."\n");
	                    	}else{
	                    		require_once(ROOT_PATH . 'admin/ajax.php');
	                    		$result = ajax_check_tracking_number(array("carrier_id"=>$order['carrier_id'],"tracking_number"=>$order['tracking_number']));
	                    		 if($result){
	                    		 	echo("| - the order 快递方式与快递单号不冲突，但是淘宝不通过！！订单号：".$request['tid'] ."\n");
	                    		 }else{
		                    		$html[$facility_id] = $html[$facility_id] . " 淘宝订单号： " . $order['mp_taobao_order_sn'] . " 发货仓库： ".$order['facility_name']
		                            	. " 快递方式： " . $order['shipping_name'] . " 快递单号： " . $order['tracking_number'] . "同步发货失败\n";
	                    		 }
	                    	}
	                    }
	                    //拆单校验未通过，放在后面重新跑起来
//	                    else if(isset($response->subCode) && $response->subCode == 'isv.logistics-offline-service-error:P38'){
//	                    	echo("| - the order has failed to split check !\n");
//	                    	echo("|  - the order has failed to split check ! check the next...\n");
//	                    	//根据 taobao_order_sn run select ecs_order_info表
//	                    	$sql ="select oi.order_id,oi.order_sn,oi.shipping_name,FROM_UNIXTIME(oi.shipping_time) as shipping_time," .
//	                    			" oi.taobao_order_sn as taobao_order_sn,oi.distribution_purchase_order_sn,s.tracking_number " .
//	                    			" from ecshop.ecs_order_info oi" .
//	                    			" left join romeo.order_shipment os on os.order_id = convert(oi.order_id using utf8)" .
//	                    			" left join romeo.shipment s on s.shipment_id = os.shipment_id " .
//	                    			" where oi.taobao_order_sn like '{$tid}%' and oi.order_sn != '{$order['order_sn']}' " .
//	                    			" AND oi.order_status = 1 AND oi.pay_status = 2 AND oi.shipping_status = 1";
//	                    	echo("|  - isv.logistics-offline-service-error:P38===sql : ".$sql."\n");
//	                    	$anss = $db->createCommand($sql)->queryAll();
//	                    	if(!empty($anss)){
//	                    		$sub_tid = "";
//	                    		foreach($anss as $ans){
//	                    			$sub_tid .= $ans['taobao_order_sn'].",";
//	                    		}
//	                    		$sub_tid = substr($sub_tid,0,-1);
//	                    		echo("|  - isv.logistics-offline-service-error:P38 -- sub_tid:".$sub_tid."\n");
//	                    		$to_deal_bills[]=array("tid"=>$to_deal_bill['tid'],"oid"=>$sub_tid,"is_split"=>1,"shipping_name"=>$to_deal_bill['shipping_name'],"tracking_number"=>$to_deal_bill['tracking_number']);
//	                    		foreach($anss as $ans){
//	                    			$to_deal_bills[] = array("tid"=>$ans['taobao_order_sn'],"oid"=>"","is_split"=>0,"shipping_name"=>$ans['shipping_name'],"tracking_number"=>$ans['tracking_number']);
//	                    		}
//	                    	}else{
//	                    		echo("|  - the order can't find sub_tid : ".$response->subCode.", ".$response->msg." \n");
//	                    	}
//	                    }
	                    else if(isset($response->subCode) && 
	                    in_array($response->subCode,array('isv.logistics-offline-service-error:ORDER_NOT_FOUND_ERROR',
							'isp.top-remote-connection-timeout','isp.top-remote-service-unavailable'))){
	                    	//不报错
	                    	//isv.logistics-offline-service-error:ORDER_NOT_FOUND_ERROR -->淘宝订单号是人为错误录单，淘宝端不存在
	                    	//isp.top-remote-connection-timeout  -->连接超时
	                    	//isv.logistics-offline-service-error:B150  -->发货异常，请稍等后重试
	                    	//isp.top-remote-service-unavailable  -->调用后端服务***抛异常，服务不可用
	                    }
	                    // 其他错误
	                    else if (isset($response->subCode) && isset($response->msg))
	                    {  
	                        echo("|  - has error: ".$response->subCode.", ".$response->msg." \n");
	                    } else {
	                    	echo("|  - has error: \n");
	                    }
	                }
	                catch (Exception $e)
	                {
	                    echo("|  - has exception: ". $e->getMessage() . "\n");
	                }
	
	                usleep(500000);
	            }
            }

            if($order_list!==array())
            echo("[".date('c')."] ".$taobaoShop['nick']. " delivery send sync done! \n\n");

          // session过期邮件通知
            if(isset($response) && isset($response->code) && $response->code=='27')
            {
                try
                {
                    $mail=Yii::app()->getComponent('mail');
                    $mail->Subject="淘宝店铺 ". $taobaoShop['nick'] ." Session过期";
                    $mail->Body="
						该淘宝店铺的session过期了，不能进行同步动作，请按下面的链接取得session后更新到erp中。
						http://auth.open.taobao.com/?appkey=".$taobaoShop['params']['app_key']. " \n
						http://container.open.taobao.com/container?appkey=".$taobaoShop['params']['app_key'];
					$mail->AddAddress('hbai@leqee.com', '柏壑');
					$mail->AddAddress('mjzhou@leqee.com', '周明杰');
					$mail->AddAddress('qxu@leqee.com', '许强');
					$mail->AddAddress('yfhu@leqee.com', '胡一帆');	
					$mail->AddAddress('ljzhou@leqee.com', '周灵杰');
					$mail->AddAddress('wjzhu@leqee.com', '朱伟晋');	
					$mail->AddAddress('ytchen@leqee.com', '陈艳婷');
                    $mail->send();
                }
                catch (Exception $e){}
            }
            //发送预警邮件
            if (!empty($html)) {
                try {
                    $mail=Yii::app()->getComponent('mail');
                    $mail->Subject="淘宝店铺 ". $taobaoShop['nick'] ." 快递单号与发货类型不匹配，请仓库人员检查及修改";
                    foreach ($html as $key => $value) {
                        if (!empty($value)) {
                            $mail->ClearAddresses();
                            $mail->Body = "";
					        $mail->AddAddress('hbai@leqee.com', '柏壑');
					        $mail->AddAddress('mjzhou@leqee.com', '周明杰');
					        $mail->AddAddress('qxu@leqee.com', '许强');
					        $mail->AddAddress('yfhu@leqee.com', '胡一帆');	
					        $mail->AddAddress('ljzhou@leqee.com', '周灵杰');
				        	$mail->AddAddress('wjzhu@leqee.com', '朱伟晋');	
				        	$mail->AddAddress('ytchen@leqee.com', '陈艳婷');
							$mail->AddAddress('dgwl@leqee.com', '东莞物流');
							$mail->AddAddress('shwl@leqee.com', '上海物流');	
                            $mail->Body = $value;
                            if(isset($facilitly_email[$key])){
	                            foreach ($facilitly_email[$key] as $item){
	                                $mail->AddAddress($item['email'], $item['name']);
	                            }
                            }
                            $mail->send();
                        }
                    }
                } catch (Exception $e){}
            }
        }
    }
    
    
/**
	 * 同步淘宝上的商品
	 *
	 * @param string  $appkey
	 * @param integer $seconds
	 */
    public function actionSyncItemStatus($appkey=null,$seconds=null)
    {
        // 不启用商品同步的列表
        $exclude_list=array
        (
         // 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
         // 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
        );

        $db=Yii::app()->getDb();
        $start=microtime(true);
        $sync_count = 0;
        // 循环每个店铺
        foreach($this->getTaobaoShopList() as $taobaoShop)
        {
            if(in_array($taobaoShop['application_key'], $exclude_list))
            continue;

            // 指定店铺
            if(isset($appkey)&&$appkey!=$taobaoShop['application_key'])
            continue;

            echo("[".date('c')."] ".$taobaoShop['nick']. " item status sync start: \n");
            $client=$this->getTaobaoClient($taobaoShop);
        	$request=array(
		        'fields'=>'num_iid',
        		'banner'=>'off_shelf,sold_out',	//off_shelf(我下架的) 
		        'page_no'=>1,
		        'page_size'=>40,
        	);
        	if ($seconds !== null) {
        		$request['start_modified'] = date("Y-m-d H:i:s", time()-60*60);
        		$request['end_modified'] = date("Y-m-d H:i:s", time());
        	}
        	$num_iids = $this->getTaobaoItemsByStatus($client, 'instock', $request, 'num_iid');
        	$item_list = array();
        	foreach(array_chunk($num_iids,20) as $item_chunk){
	            try{
	                // 这个接口一次只能查20个商品，但能取得详细信息
	                $request=array(
		                'fields'=>'iid,num_iid,outer_id,modified,approve_status,detail_url,num,title,price,sku,list_time,delist_time',
		                'num_iids'=>implode(',',$item_chunk),
	                );
	                $response=$client->execute('taobao.items.list.get',$request);
	                if($response->isSuccess()){
	                	$item_list=array_merge($item_list,$response->items->item);
	                } else {
	                	echo($response->getMsg().": ".$response->getSubMsg()."\n");
	                }
	            } catch(Exception $e) {
	                echo($e->getMessage()."\n");
	                continue;
	            }
	            usleep(500000);
        	}
        	$i = 0;
        	
			foreach($item_list as $item) {
				$i++;
				if(isset($item->skus)) {
	                foreach($item->skus->sku as $sku) {
	                    if(isset($sku->outer_id) && $this->checkOuterId($taobaoShop,$sku->outer_id,true)) {
	                    	$sql = "UPDATE ecshop.ecs_taobao_goods SET approve_status = 'instock', quantity	= '{$sku->quantity}', last_modify = '".date("Y-m-d H:i:s" , time())."' WHERE outer_id = '{$sku->outer_id}' ";
							if($db->createCommand($sql)->execute()) {
								$sync_count++;
							}
	                    }
	                }
            	} else {
            		if(isset($item->outer_id)&&$this->checkOuterId($taobaoShop,$item->outer_id,false)) {
            			$sql = "UPDATE ecshop.ecs_taobao_goods SET approve_status = 'instock', quantity	= '{$item->num}', last_modify = '".date("Y-m-d H:i:s" , time())."' WHERE outer_id = '{$item->outer_id}' ";
							if($db->createCommand($sql)->execute()) {
								$sync_count++;
							}
            		}
            	}
			}				
        }
        echo "[". date('c'). "]商品状态同步数：".$sync_count." 耗时：".(microtime(true)-$start)."\n";
    }

    /**
	 * 同步淘宝上的商品
	 *
	 * @param string  $appkey
	 * @param integer $seconds
	 */
    public function actionSyncItem($appkey=null,$seconds=1800)
    {
        // 不启用商品同步的列表
        $exclude_list=array
        (
         // 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
         // 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
        );

        // 循环每个店铺
        foreach($this->getTaobaoShopList() as $taobaoShop)
        {
            // 排除店铺
            if(in_array($taobaoShop['application_key'], $exclude_list))
            continue;

            // 指定店铺
            if(isset($appkey)&&$appkey!=$taobaoShop['application_key'])
            continue;

            echo("[".date('c')."] ".$taobaoShop['nick']. " item sync start: \n");

            if($seconds===null)
            $start_modified=$end_modified=null;
            else
            {
                $start_modified=date('Y-m-d H:i:s',time()-$seconds);
                $end_modified=date('Y-m-d H:i:s');
            }
            // 取得所有商品
            $item_list=$this->getTaobaoItems($taobaoShop,null,$start_modified,$end_modified);

            echo("| total of items number is ". count($item_list) ." \n");
            if($item_list!==array())
            {   $html = "";
                foreach($item_list as $item) {
                    $html .= $this->updateItem($taobaoShop,$item);
                }
                if ($html) {
                    //发送预警邮件给店长
                    $this->SyncItemOuterIdsSendMail($html, $taobaoShop);
                }
                echo("[".date('c')."] ". $taobaoShop['nick']. " item sync down! \n\n");
                sleep(1);
            }
        }
        usleep(500000);
        //同步一下下架商品状态
        $this->run(array('SyncItemStatus'));
    }
    /**
     * @param string $html
     * @param array $taobaoShop
     * 在售商品中无商家编码，发送预警邮件给店长
     */
    function SyncItemOuterIdsSendMail ($html, $taobaoShop) {
        $html = '
            <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
            <html xmlns="http://www.w3.org/1999/xhtml">
            <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
                <style type="text/css">
                    table, table tr, table th, table td{
                        border:1px solid #000;
                        border-collapse: collapse;
                    }
                </style>
                </head>
                <body style="font-size:12px;color:#000;">
                <div style="margin:auto;width:800px;">
                <h3>请店长在淘宝上补充商家编码</h3>
                <table width="100%">
                    <tr><th>店铺名称</th><th>淘宝商品名称</th><th>淘宝商品链接</th></tr>' . $html . 
                "</table></div></body></html>";
        $from = array(
            'email' => 'erp@leqee.com', 'name' => '请店长在淘宝上补充商家编码',
        );
        $sql = "
            select user_name as name, user_email as email from ecshop.ecs_party_assign_email 
            where party_id = '{$taobaoShop['party_id']}' and warning_id = ''
        ";
        $to_email = $this->getSlave()->createCommand($sql)->queryAll();
        $to = array_merge($to_email,array(
            array('name' => 'ERP', 'email' => 'erp@i9i8.com'),
            array('name' => '流程组', 'email' => 'liucheng@i9i8.com'),
        ));
        $subject = "店铺名称：".$taobaoShop['nick'] ."，请店长在淘宝上补充商家编码";
        $this->sendMail($subject, $from, $to, $html, $taobaoShop['nick']);
    }

    /**
	 * 更新一个商品
	 *
	 * @param array $taobaoShop 淘宝店铺
	 * @param object $item 商品
	 */
    private function updateItem($taobaoShop,$item)
    {
        // 更新或添加的数据
        $data=array
        (
        'iid'=>isset($item->iid)? $item->iid : $item->num_iid,
        'num_iid'=>$item->num_iid,
        'sku_id'=>'',
        'outer_id'=>isset($item->outer_id)?$item->outer_id:'',
        'properties'=>isset($itme->properties)?$item->properties:'',
        'goods_id'=>isset($item->outer_id)?$item->outer_id:0,
        'style_id'=>0,
        'last_modify'=>$item->modified,
        'url'=>isset($item->detail_url)?$item->detail_url:'',
        'price'=>isset($item->price)?$item->price:'',
        'quantity'=>isset($item->num)?$item->num:0,
        'title'=>isset($item->title)?$item->title:'',
        'approve_status'=>$item->approve_status,
        'application_key'=>$taobaoShop['application_key'],
        'party_id'=>$taobaoShop['party_id']
        );

        $db=Yii::app()->getDb();
        $table=$db->getSchema()->getTable('ecs_taobao_goods');
        $builder=$db->getCommandBuilder();
        $sql="delete from ecs_taobao_goods where taobao_goods_id=:id";  // 删除sql

        // 通过num_iid查找
        $command=$builder->createSqlCommand("select taobao_goods_id,num_iid,sku_id,last_modify,status from ecs_taobao_goods where application_key=? and num_iid=?", array($taobaoShop['application_key'],$item->num_iid));
        $list=$command->queryAll();
        $item_log = "| item: (num_iid: ".$item->num_iid.") ". $item->title ." \n";
        $html = "";
        // 不存在该商品记录则创建, outer_id为空的记录不创建
        if($list===array())
        {
            $data['status']='OK';  // 刚创建时状态OK
            if(isset($item->skus))
            {
                foreach($item->skus->sku as $sku)
                {
                    if(isset($sku->outer_id)&&$this->checkOuterId($taobaoShop,$sku->outer_id,true))
                    {
                        $data['sku_id']=$sku->sku_id;
                        $data['price']=$sku->price;
                        $data['last_modify']=$sku->modified;
                        $data['outer_id']=$sku->outer_id;
                        $data['quantity']=$sku->quantity;
                        $data['properties']=$sku->properties;
                        if(strpos($sku->outer_id, 'TC-') !== false){
                        	list($data['goods_id'],$data['style_id'])=array(0, 0);
                        }else{
                        	list($data['goods_id'],$data['style_id'])=explode('_',$sku->outer_id);
                        	if ($taobaoShop['party_id'] == '65574') {
                        		//金宝贝
                        		if (!empty($item->outer_id) && ($data['goods_id'] != $item->outer_id)) {
                        			echo "gymboree outer_id != outer_sku_id  detail_url: ".$item->detail_url . "\n";
                        		}
                        	}
                        }
                        try{
                        	$success=(boolean)$builder->createInsertCommand($table,$data)->execute();
                        	echo($item_log."|  - sku: (sku_id: ".$sku->sku_id.", outer_id: ".$sku->outer_id.") created".($success?' success':' failed')."! \n");
                        } catch (Exception $e ){
                        	var_dump($item);
                        	var_dump($e->errorInfo);
                        }
                    }
                    else{
                        echo($item_log."|  - sku: (sku_id: ".$sku->sku_id.", outer_id: ".(isset($sku->outer_id)?print_r($sku->outer_id):'NULL').") outer_id is empty or not valid! can not create! \n");	
                        if ($item->approve_status == "onsale") {
                            $html = '<tr><td>'.$taobaoShop['nick']."</td><td>{$item->title}</td><td>"."<a href=\"{$item->detail_url}\">".$item->detail_url."</a></td></tr>";
                        }
                    }
                    
                }
            }
            else
            {
                if(isset($item->outer_id)&&$this->checkOuterId($taobaoShop,$item->outer_id))
                {
                	try {
                		$success=(boolean)$builder->createInsertCommand($table,$data)->execute();
                		echo($item_log."|  - item: (num_iid: ".$item->num_iid.", outer_id: ".$item->outer_id.") created".($success?' success':' failed')."! \n");
                	} catch (Exception $e ){
                		var_dump($item);
                		var_dump($e->errorInfo);
                	}
                }
                else{
                    echo($item_log."|  - item: (num_iid: ".$item->num_iid.", outer_id: ".(isset($item->outer_id)?print_r($item->outer_id):'NULL').") outer_id is empty or not valid! can not create! \n");
                    if ($item->approve_status == "onsale") {
                        $html = '<tr><td>'.$taobaoShop['nick']."</td><td>{$item->title}</td><td>"."<a href=\"{$item->detail_url}\">".$item->detail_url."</a></td></tr>";
                    }
                }
                
            }
        }
        // 存在该商品记录则对比做删除或更新
        else
        {
            if(isset($item->skus))
            {
                $updated_list=array();
                foreach($item->skus->sku as $sku)
                {
                    if(!isset($sku->outer_id)||!$this->checkOuterId($taobaoShop,$sku->outer_id, true))
                    continue;

                    $data['sku_id']=$sku->sku_id;
                    $data['price']=$sku->price;
                    $data['last_modify']=$sku->modified;
                    $data['outer_id']=isset($sku->outer_id)?$sku->outer_id:'';
                    $data['quantity']=$sku->quantity;
                    $data['properties']=$sku->properties;
                    if(strpos($sku->outer_id, 'TC-') !== false){
                    	list($data['goods_id'],$data['style_id'])=array(0, 0);
                    }else{
                    	list($data['goods_id'],$data['style_id'])=explode('_',$data['outer_id']);
                    }
                    foreach($list as $row)
                    {
                        // 系统中有这条SKU记录，如果outer_id不为空就更新，否则删除
                        if($sku->sku_id==$row['sku_id'])
                        {
                            $updated_list[]=$row['taobao_goods_id'];
                            if(strtotime($sku->modified)>strtotime($row['last_modify']) || strtotime($item->modified)>strtotime($row['last_modify']))
                            {
                                $criteria=$builder->createColumnCriteria($table,array('taobao_goods_id'=>$row['taobao_goods_id']));
                                $command=$builder->createUpdateCommand($table,$data,$criteria);
                                $success=(boolean)$command->execute();
                                echo($item_log."|  - sku: (sku_id: ".$sku->sku_id.", outer_id: ".$sku->outer_id.") update".($success?' success':' failed')."! \n");
                            }
//                            else
//                            echo("|  - sku: (sku_id: ".$sku->sku_id.", outer_id: ".$sku->outer_id.") not need update \n");

                            continue 2;
                        }
                    }
                    // 系统中没有这条SKU，尝试创建
                    $data['status']='OK';
                    try {
                    	$success=(boolean)$builder->createInsertCommand($table,$data)->execute();
                    	echo($item_log."|  - sku: (sku_id: ".$sku->sku_id.", outer_id: ".$sku->outer_id.") created".($success?' success':' failed')."! \n");
                    } catch (Exception $e) {
                    	var_dump($item);
                    	var_dump($e->errorInfo);
                    }
                }

                // delete
                foreach($list as $row)
                {
                    if(!in_array($row['taobao_goods_id'],$updated_list))
                    {
                        $db->createCommand($sql)->bindValue(":id",$row['taobao_goods_id'])->execute();
                        echo("|  - sku: (row_id: ".$row['taobao_goods_id'].", sku_id: ".$row['sku_id'].", outer_id: " . (isset($row['outer_id'])?print_r($row['outer_id']) : 'NULL') .") have been delete \n");
                    }
                }
            }
            else
            {
                $updated_list=array();
                if(isset($item->outer_id)&&$this->checkOuterId($taobaoShop,$item->outer_id))
                {
                    $row=$db->createCommand("select * from ecs_taobao_goods where num_iid=:num_iid and sku_id='' and application_key=:appkey")
                    ->bindValue(':num_iid',$item->num_iid)
                    ->bindValue(':appkey',$taobaoShop['application_key'])
                    ->queryRow();
                    if($row===false)  // created
                    {
                        $data['status']='OK';
                        try {
                        	$success=(boolean)$builder->createInsertCommand($table,$data)->execute();
                        	echo($item_log."|  - item: (outer_id: ".$item->outer_id.") created".($success?' success':' failed')."! \n");
                        } catch (Exception $e ){
                        	var_dump($item);
                        	var_dump($e->errorInfo);
                        }
                    }
                    else  // update
                    {
                        $updated_list[]=$row['taobao_goods_id'];
                        if(strtotime($item->modified)>strtotime($row['last_modify']))
                        {
                            $criteria=$builder->createColumnCriteria($table,array('taobao_goods_id'=>$row['taobao_goods_id']));
                            $success=(boolean)$command=$builder->createUpdateCommand($table,$data,$criteria)->execute();
                            echo("|  - item: (outer_id: ".$item->outer_id.") update".($success?' success':' failed')."! \n");
                        }
//                        else
//                        echo("|  - item: (outer_id: ".$item->outer_id.") not need update \n");
                    }
                }
                else {
                    echo("|  - item: (outer_id: ".(isset($item->outer_id)?print_r($item->outer_id):'NULL').") outer_id is empty or not valid! \n");
                    if ($item->approve_status == "onsale") {
                       $html = '<tr><td>'.$taobaoShop['nick']."</td><td>{$item->title}</td><td>"."<a href=\"{$item->detail_url}\">".$item->detail_url."</a></td></tr>";
                    }
                }
                 // 删除已经保存的sku
                foreach($list as $row)
                {
                    if(!in_array($row['taobao_goods_id'],$updated_list))
                    {
                        $db->createCommand($sql)->bindValue(':id',$row['taobao_goods_id'])->execute();
                        echo("|  - sku:  (row_id: ".$row['taobao_goods_id'].", sku_id: ".$row['sku_id'].", outer_id: ".$row['outer_id'].") have been delete \n");
                    }
                }
            }
        }
        return $html;
    }

    /**
	 * 检查商品的商家编码, 如果返回false说明这个外部编码不符合要求的, 下列情况下会返回false
	 * 1. SKU的外部编码格式不是 “32640_12” 这样的格式
	 * 2. 商品的party_id和店铺的party_id不一致
	 * 3. ERP商品有sku而淘宝商品没有sku
	 * 4. ERP商品没有sku而淘宝商品是sku
	 * 5. 商品或sku不存在
	 *
	 * @param array $taobaoShop
	 * @param string $outerId
	 * @param boolean $isSku
	 * @return boolean
	 */
    private function checkOuterId($taobaoShop, $outerId, $isSku=false)
    {
        if(is_object($outerId)){
            return false;
        }
        if(empty($outerId)||$outerId===''||trim($outerId)===''){
        	return false;
        }
        

        // 同步乐其电教商品（套餐）  还有可能其他业务套餐
        if(strpos($outerId, 'TC-') !== false){
        	 $command=Yii::app()->getDb()->createCommand("select 1 from ecshop.distribution_group_goods where code = :code limit 1");
        	 if($command->bindValue(':code', $outerId)->queryScalar() === false){
        	 	return false ;
        	 }else{
        	 	return true;
        	 }
        }
        
        if($isSku)
        {
            if(strpos($outerId,'_')===false)
            return false;
            else
            {
                list($goods_id,$style_id)=explode('_',$outerId);
                if(empty($goods_id)||is_null($style_id))  // style_id有可能为0
                return false;
            }
        }
        else
        {
            $goods_id=$outerId;
            $style_id=0;
        }

        // 查询商品是否存在
        $command=Yii::app()->getDb()->createCommand("select goods_party_id from ecs_goods where goods_id=:goods_id limit 1");
        $party_id=$command->bindValue(':goods_id',$goods_id,PDO::PARAM_INT)->queryScalar();
        if($party_id===false||($taobaoShop['party_id']!=$party_id))
        return false;
        if($style_id>0)
        {
            // 查询SKU是否存在
            $command=Yii::app()->getDb()->createCommand("select 1 from ecs_goods_style where goods_id=:goods_id and style_id=:style_id limit 1");
            if($command->bindValue(':goods_id',$goods_id,PDO::PARAM_INT)->bindValue(':style_id',$style_id,PDO::PARAM_INT)->queryScalar()===false)
            return false;
        }
        else
        {
            // 如果该商品有SKU
            $command=Yii::app()->getDb()->createCommand("select 1 from ecs_goods_style where goods_id=:goods_id limit 1");
            if($command->bindValue(':goods_id',$outerId,PDO::PARAM_INT)->queryScalar()!==false)
            return false;
        }

        return true;
    }

    /**
	 * 同步订单
	 */
    public function actionSyncOrder($appkey=null,$hours=3)
    {
        // 不启用订单同步的列表
        $exclude_list=array
        (
//           'e27b2f032f43484e85678eae66840c1e', //康贝店铺
           '995e1a1b43eb4e2ba151f22ea45314ff', //康漫           
        // 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
        // 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
        );
        
        // 转到新同步的店
        $erpsync_list=array
        (
        '0893053569514b048e8ebafc04555ff0',  //本草婴缘
        '923ec15fa8b34e4a8f30e5dd8230cdef',  //安怡
        '573d454e82ff408297d56fbe1145cfb9',  //金宝贝
        'e27b2f032f43484e85678eae66840c1e',  //康贝
        );

        // 远程服务
        $client=Yii::app()->getComponent('romeo')->TaobaoOrderService;
        foreach($this->getTaobaoShopList() as $taobaoShop)
        {
            if(in_array($taobaoShop['application_key'],$exclude_list))
            continue;

            if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
            continue;

            echo("[".date('c')."] ".$taobaoShop['nick']." sync order start \n");

            // 同步生成订单
            try
            {
               if(in_array($taobaoShop['application_key'],$erpsync_list)) {
                	echo("[".date('c')."] ".$taobaoShop['nick']." sync order 已转新同步 \n");
                }
               else {
                   $request=array("hours"=>$hours,"applicationKey"=>$taobaoShop['application_key']);
                   echo("[".date('c')."] ".$taobaoShop['nick']." sync order 全部已转新同步 \n");
                  // $response=$client->synchronizeOrder($request);
                   print_r($response);            	
               }
            }
            catch(Exception $e)
            {
                echo("|  Exception: ".$e->getMessage()."\n");
            }

            usleep(500000);

            
            // 同步完成订单
            try
            {
                $request=array("hours"=>$hours,"applicationKey"=>$taobaoShop['application_key']);
                $response=$client->synchronizeOrderFinished($request);
                print_r($response);
            }
            catch(Exception $e)
            {
                echo("|  Exception: ".$e->getMessage()."\n");
            }
            
            usleep(500000);

            // 同步交易关闭订单
            try
            {
                $request=array("hours"=>$hours,"applicationKey"=>$taobaoShop['application_key']);
                echo("[".date('c')."] ".$taobaoShop['nick']." sync order 关闭订单全部已转新同步 \n");
              //  $response=$client->synchronizeOrderClosed($request);
                print_r($response);
            }
            catch(Exception $e)
            {
                echo("|  Exception: ".$e->getMessage()."\n");
            }
            
            usleep(500000);
        }
    }
    
    /**
     * 同步淘宝分销订单
     * 
     */
    public function actionSyncFenxiaoOrder($appkey=null,$hours=3)
    {
        // 不启用订单同步的列表
        $exclude_list = array
        (
            'f2c6d0dacf32102aa822001d0907b75a' ,         // 乐其数码专营店
            // 'd1ac25f28f324361a9a1ea634d52dfc0' ,         // 怀轩名品专营店
            // 'fd42e8aeb24b4b9295b32055391e9dd2' ,         // oppo乐其专卖店
            // '239133b81b0b4f0ca086fba086fec6d5' ,         // 贝亲官方旗舰店
            // '11b038f042054e27bbb427dfce973307' ,         // 多美滋官方旗舰店
            // 'ee0daa3431074905faf68cddf9869895' ,         // accessorize旗舰店
            // 'ee6a834daa61d3a7d8c7011e482d3de5' ,         // 金奇仕官方旗舰店
            // 'fba27c5113229aa0062b826c998796c6' ,         // 方广官方旗舰店
            // 'f38958a9b99df8f806646dc393fdaff4' ,         // 阳光豆坊旗舰店
            // '7f83e72fde61caba008bad0d21234104' ,         // nutricia官方旗舰店
            // '62f6bb9e07d14157b8fa75824400981f',          // 雀巢官方旗舰店
            'f1cfc3f7859f47fa8e7c150c2be35bfc',          // 金佰利官方旗舰店
            '753980cc6efb478f8ee22a0ff1113538',          //gallo官方旗舰店
            '85b1cf4b507b497e844c639733788480',          //安满官方旗舰店
            '573d454e82ff408297d56fbe1145cfb9',          //金宝贝
            'ee6a834daa61d3a7d8c7011e482d3de5',          //金奇仕
            'dc7e418627d249ecb5295ee471a2152a',          //新百伦
            '7626299ed42c46b0b2ef44a68083d49a',          //blackmores
            '159f1daf405445eca885a4f7811a56b8',          //康贝
            '923ec15fa8b34e4a8f30e5dd8230cdef',          //安怡
        );
        
                // 转到新同步的店
        $erpsync_list=array
        (
        '85b1cf4b507b497e844c639733788480',          //安满官方旗舰店
        '159f1daf405445eca885a4f7811a56b8',          //康贝
        );

        // 远程服务
        $client=Yii::app()->getComponent('romeo')->TaobaoOrderService;
        foreach($this->getTaobaoShopList() as $taobaoShop)
        {
            if(!in_array($taobaoShop['application_key'], $exclude_list))
            continue;

            if($appkey!==null && $appkey!=$taobaoShop['application_key'])
            continue;

            echo("[".date('c')."] ".$taobaoShop['nick']." sync fenxiaoOrder start \n");

            // 同步生成订单
            try
            {
               if(in_array($taobaoShop['application_key'],$erpsync_list)) {
                	echo("[".date('c')."] ".$taobaoShop['nick']." sync order 已转新同步 \n");
                }
               else {               	            	
                $request=array("hours"=>$hours,"applicationKey"=>$taobaoShop['application_key']);
                echo("[".date('c')."] ".$taobaoShop['nick']." sync order 全部已转新同步 \n");
                // $response=$client->synchronizeFenxiaoOrder($request);
                print_r($response);
               }
            }
            catch(Exception $e)
            {
                echo("|  Exception: ".$e->getMessage()."\n");
            }

            usleep(500000);

            
            // 同步完成订单
            try
            {
                $request=array("hours"=>$hours,"applicationKey"=>$taobaoShop['application_key']);
                $response=$client->synchronizeFenxiaoOrderFinished($request);
                print_r($response);
            }
            catch(Exception $e)
            {
                echo("|  Exception: ".$e->getMessage()."\n");
            }
            
            usleep(500000);
        }
    }
    

    /**
     * 批量增量更新淘宝库存
     * @param string $appkey
     * @param int $seconds
     */
    public function actionPatchUpdateItemStock($appkey=null,$seconds=null){
         // 启用库存同步的店铺列表
        $include_list = array(
//            'cccc89fddefb426fac12af2084fb9656',          // 欧酷数码专营店
//            'cb6d61c0c6f14dc0a298c9d7cb9d4495',          // 惠普笔记本专供店
//            '0780b32fa52c43b1809e39d4ca3e02af',          // lchen1979
//            'f2c6d0dacf32102aa822001d0907b75a',          // 乐其数码专营店
//            'd1ac25f28f324361a9a1ea634d52dfc0',          // 怀轩名品专营店
//            '566ad34e11a143ef8cb77737762e7d54',          // 森马官方旗舰店
//            '56d31a317f3148fab96c10d4762e1efe',          // 夏娃之秀官方旗舰店
//            'fd42e8aeb24b4b9295b32055391e9dd2',          // oppo乐其专卖店
              '239133b81b0b4f0ca086fba086fec6d5',          // 贝亲官方旗舰店
              '11b038f042054e27bbb427dfce973307',          // 多美滋官方旗舰店
//            'f2c6e386cf32102aa822001d0907b75a',          // 奥普电器旗舰店
//            'a54c58229b474d2694a5ba3d304c71ec',          // 孕之彩官方旗舰店
              'ee0daa3431074905faf68cddf9869895',          // accessorize旗舰店
//            'ee6a834daa61d3a7d8c7011e482d3de5',          // 金奇仕官方旗舰店
//            'fba27c5113229aa0062b826c998796c6',          // 方广官方旗舰店
//            'f38958a9b99df8f806646dc393fdaff4',          // 阳光豆坊旗舰店
//            '7f83e72fde61caba008bad0d21234104',          // nutricia官方旗舰店
              '62f6bb9e07d14157b8fa75824400981f',          // 雀巢官方旗舰店
              '753980cc6efb478f8ee22a0ff1113538',          // gallo官方旗舰店
//            '589e7a67c0f94fb686a9287aaa9107db',          // yukiwenzi-分销
              'fe1441b38d4742008bd9929291927e9e',          // 好奇官方旗舰店
              'f1cfc3f7859f47fa8e7c150c2be35bfc',          // 金佰利官方旗舰店
              'dccd25640ed712229d50e48f2170f7fd',          // ecco爱步官方旗舰店
              '9f6ca417106894739e99ebcbf511e82f',          // 每伴旗舰店
//              'd2c716db4c9444ebad50aa63d9ac342e',          // 皇冠巧克力
			  '6ecd27fb75354272ba07f08a2507fa40',          // 蒙牛母婴旗舰店
			  '85b1cf4b507b497e844c639733788480',          // 安满官方旗舰店
			  '7626299ed42c46b0b2ef44a68083d49a',          // blackmores官方旗舰店
              '87b6a6a6ced1499c90073197670b54ce',          //玛氏宠物旗舰店
              '573d454e82ff408297d56fbe1145cfb9',          //金宝贝
              '9781a6fe164a4193acf195d68c10ddfc',          //保乐力加
              'ea66b158e2fb102ab1b4001d0907b75a',          //小树苗
              '4fe1115f63f14f7fb6edaafe519f63ce',          //中美史克
              'f2c6efe8cf32102aa822001d0907b75a',          //黄色小鸭
        );
        $start = microtime(true);
        //获取七天内系统中未预定成功订单中指定商品的使用库存
        $start_time = date("Y-m-d H:i:s", time()-3600*24*7);
        $end_time = date("Y-m-d H:i:s", time());
        $need_to_reserved_list = $this->getPorductNeedToReservedList($start_time, $end_time);
        $need_to_reserved_hashamp = Helper_Array::toHashmap((array)$need_to_reserved_list, 'product_id', 'pending_count');
     
        //默认6小时前预订过的商品信息
        $reserved_sql = '';
        if ($seconds !== null) {
            $order_time = " o.order_time >= '". date("Y-m-d H:i:s", time() - $seconds) ."' ";
            $order_time1 = " o1.order_time >= '". date("Y-m-d H:i:s", time() - $seconds) ."' ";
        } else {
            $order_time = " o.order_time >= '". date("Y-m-d H:i:s", time() - 60*60*6) ."' ";
            $order_time1 = " o1.order_time >= '". date("Y-m-d H:i:s", time() - 60*60*6) ."' ";
        }
        //获取库存同步时间差内库存发生改变的商品列表
        $sql="
            select  tg.*
            from ecshop.ecs_order_info o
            left join ecshop.ecs_order_goods og on o.order_id = og.order_id
            left join ecshop.ecs_taobao_goods tg on og.goods_id = tg.goods_id and og.style_id = tg.style_id 
            where {$order_time} and  tg.application_key=:appkey 
                and tg.status = 'OK' and tg.approve_status = 'onsale' 
            group by og.goods_id, og.style_id
            union all
            select tg1.*
            from ecshop.ecs_order_info o1
            left join ecshop.ecs_order_goods og1 on o1.order_id = og1.order_id
            left join ecshop.distribution_group_goods_item gi on gi.goods_id = og1.goods_id and gi.style_id = og1.style_id
            left join ecshop.distribution_group_goods gg on gi.group_id = gg.group_id
            left join ecshop.ecs_taobao_goods tg1 on tg1.outer_id = gg.code
            where {$order_time1} and  tg1.application_key=:appkey 
                and tg1.status = 'OK' and tg1.approve_status = 'onsale' 
            group by gg.code
        ";
        $select=Yii::app()->getDb()->createCommand($sql); 
        foreach($this->getTaobaoShopList() as $taobaoShop) {
            if(!in_array($taobaoShop['application_key'], $include_list)){
                continue;
            }
            if($appkey!==null&&$appkey!=$taobaoShop['application_key']){
                continue;
            }
            // 取得要同步库存的商品和SKU
            $items=$select->bindValue(':appkey', $taobaoShop['application_key'])->queryAll();
            $calc_start = microtime(true);
            $html = '';  //异常商品邮件发送内容
            //同步库存前检查预警库存数量，发送邮件
            foreach($items as $idx => $item) {
                // 先定义预添加的数组属性
                $items[$idx]['available_to_reserved'] = 0;
                $items[$idx]['stock_quantity'] = 0;
                $need_to_reserved = 0;  //默认系统中待预定商品使用库存为0
                if (strpos($item['outer_id'], 'TC-') !== false) {
                    // 套餐商品同步
                    $taocanStock = $this->getTCStock($taobaoShop['party_id'], $item['outer_id'],$need_to_reserved_hashamp, $taobaoShop['taobao_shop_conf_id']) ;
                    if (!empty($taocanStock)) {
                        $items[$idx]['available_to_reserved'] += $taocanStock['availableToReserved'] ;
                        $items[$idx]['stock_quantity'] += $taocanStock['quantitys'] ;
                        if ($items[$idx]['available_to_reserved'] > $items[$idx]['stock_quantity']) {
                            echo 'taobaoShop '. $taobaoShop['nick'].' outerId '.$item['outer_id'].
                                ' availableToReserved '.$taocanStock['availableToReserved'] . ' stockQuantity ' . $taocanStock['stockQuantity'];
                            //如果可预定库存大于仓库实际库存，则可预定量使用实际库存
                            $items[$idx]['available_to_reserved'] = $items[$idx]['stock_quantity'];
                        } elseif ($item['reserve_quantity'] > $items[$idx]['available_to_reserved']) {
                            $html .= 
                                "<tr>
                                    <td>". $item['title'] ."</td>
                                    <td>". $item['outer_id'] ."</td>
                                    <td>". $item['quantity'] ."</td>
                                    <td>". $items[$idx]['available_to_reserved'] ."</td>
                                    <td>". $item['reserve_quantity']  ."</td>
                                 </tr>";
                            unset($items[$idx]);
                        }
                    }
                } else {
                    $productId = ProductServices::getProductId($item['goods_id'],$item['style_id']);
                    if (empty($productId)) {
                        echo("[" .date('c') ."] " . "row (goods_id: ".$item['goods_id'].", style_id: ".$item['style_id'].") productId not exists ! \n");    
                    } else {
                        // 取得库存
                        $facilities = FacilityServices::getFacilityByPartyId($taobaoShop['party_id']);  // 可用仓库
                        $facilities = $this->getTaobaoShopFacility ($facilities, $taobaoShop['taobao_shop_conf_id']) ;
                        $inventorySummaryAssoc = InventoryServices::getInventorySummaryAssocByProduct('INV_STTS_AVAILABLE', array_keys($facilities), $productId, null);
                        if (!empty($inventorySummaryAssoc)) {
                            foreach ($inventorySummaryAssoc as $assoc) {
                                foreach ($assoc as $productStock) {
                                    $items[$idx]['available_to_reserved'] += $productStock->availableToReserved;
                                    $items[$idx]['stock_quantity'] += $productStock->stockQuantity;    
                                    //检查每个仓中商品库存数量是否异常
                                    if ($productStock->availableToReserved > $productStock->stockQuantity) {
                                        echo 'taobaoshop ' .$taobaoShop['nick'].' outer_id '. $item['outer_id'] .' facilityId ' . $productStock->facilityId
                                            .' availableToReserved ' . $productStock->availableToReserved . ' stockQuantity ' . $productStock->stockQuantity;
                                        $items[$idx]['available_to_reserved'] = $items[$idx]['stock_quantity'];
                                    }
                                }
                            }
                            //实际可预定商品库存 - ERP里已有订单且未预定成功的预存
                            $need_to_reserved = isset($need_to_reserved_hashamp[$productId])?(($need_to_reserved_hashamp[$productId] != NULL) ? (int)$need_to_reserved_hashamp[$productId] : 0) : 0; 
                            $items[$idx]['available_to_reserved'] = max(0, $items[$idx]['available_to_reserved'] - $need_to_reserved);
                            //检查是否出现库存异常
                            if ($item['reserve_quantity'] > $items[$idx]['available_to_reserved']) {
                                //检查商品同步库存小于预警库存时邮件预警 发送给店长
                                $html .= "
                                    <tr>
                                        <td>". $item['title'] ."</td>
                                        <td>". $item['outer_id'] ."</td>
                                        <td>". $item['quantity'] ."</td>
                                        <td>". $items[$idx]['available_to_reserved'] ."</td>
                                        <td>".  $item['reserve_quantity'] ."</td>
                                    </tr>
                                ";
                                unset($items[$idx]);
                                continue;
                            }
                        }
                    }
                }
            }
            // 库存预警计算所需时间
            echo "[".date('c')."] " . $taobaoShop['nick'] . " 库存预警计算库存项数：" . count($items) ." 耗时：".(microtime(true)-$calc_start)."\n";
           
            //检查是否需要发送邮件
            if ($html) {
                $html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
                    <html xmlns="http://www.w3.org/1999/xhtml">
                    <head>
                    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
                    <style type="text/css">
                        table, table tr, table th, table td{
                            border:1px solid #000;
                            border-collapse: collapse;
                        }
                    </style>
                    </head>
                    <body style="font-size:12px;color:#000;">
                    <div style="margin:auto;width:800px;">
                    <h3>店铺名称：'.$taobaoShop['nick'] .' 请店长注意该商品在淘宝上库存。</h3>
                    <table width="100%">
                        <tr>
                            <th>商品名称</th>
                            <th>商家编码</th>
                            <th>淘宝库存（仅作参考）</th>
                            <th>可预订库存</th>  
                            <th>预警库存</th>
                        </tr>' . $html . "</table></div></body></html>";
                $from = array(
                	'email' => 'erp@leqee.com', 'name' => '商品增量库存同步预警列表',
                );
                $sql = "
                	select user_name as name, user_email as email from ecshop.ecs_party_assign_email 
                	where party_id = '{$taobaoShop['party_id']}' and warning_id = ''
            	";
                $to_email = $this->getSlave()->createCommand($sql)->queryAll();
                $to = array_merge($to_email,array(
                    array('name' => 'ERP', 'email' => 'erp@i9i8.com'),
                    array('name' => '流程组', 'email' => 'liucheng@i9i8.com'),
                    ));
                $subject = "店铺名称：".$taobaoShop['nick'] ."淘宝库存同步商品预警列表";
                $this->sendMail($subject, $from, $to, $html, $taobaoShop['nick']);
            }
            //增量同步淘宝库存
            $this->updateIncrementStock($items, $taobaoShop);
        }
         echo "[".date('c')."] " . "库存同步耗时：".(microtime(true)-$start)."\n";
    }
    /**
     * 增量更新淘宝库存
     * @param array items 需要同步库存的商品列表
     * @param array taobaoShop 店铺参数
     * todo 双11期间只允许增加库存
     */
    function updateIncrementStock($items, $taobaoShop){
        $normalIncrementAppKeyList = array(
        );
        //库存同步起始时间
        $start = microtime(true);
        $num_iid_list = $item_list = $item_quantity_list = array();
        $num_iid_list = array_unique(Helper_Array::getCols($items, 'num_iid'));
        $client = $this->getTaobaoClient($taobaoShop);
        //获取淘宝库存
        $items = Helper_Array::groupBy($items, 'num_iid');
        //获取待同步商品的淘宝库存 、计算商品待同步的增量库存
        foreach(array_chunk($num_iid_list,20) as $item_chunk){
            try {
                $request = array(
                    'fields'=>'iid,num_iid,outer_id,modified,approve_status,detail_url,num,title,price,sku,list_time,delist_time',
                    'num_iids'=>implode(',',$item_chunk),
                );
                // 这个接口一次只能查20个商品，但能取得详细信息
                $response=$client->execute('taobao.items.list.get',$request);
                if($response->isSuccess()){
                    foreach ($response->items->item as $item_key => $item_value) {
                        $num_quantity = array();
                        $num_quantity['skuid_quantities'] = $num_quantity['outerid_quantities'] = "";
                        if (!empty($item_value->skus)) {
                            $size = count($item_value->skus->sku);
                            $k = 0;
                            foreach ($item_value->skus->sku as $sku_key => $sku_value) {
                                foreach ($items[$item_value->num_iid] as $value) {
                                    if (!empty($sku_value) && $sku_value->sku_id == $value['sku_id'] && $sku_value->outer_id == $value['outer_id']) {
                                        $num = $value['available_to_reserved'] - $sku_value->quantity;
                                        if (in_array($taobaoShop['application_key'], $normalIncrementAppKeyList)) {
                                            if ($num > 0) {
                                                $k++;
                                                //taobao.skus.quantity.update 最多支持20个sku修改库存
                                                if (empty($num_quantity['num_iid'])) {
                                                    $num_quantity['num_iid'] = $value['num_iid'];
                                                }
                                                $num_quantity['skuid_quantities'] .= $sku_value->sku_id.":".$num.";";
                                                $num_quantity['outerid_quantities'] .= $sku_value->outer_id.":".$num.";";
                                                if ($k % 20 == 0) {
                                                    $item_quantity_list[] = $num_quantity;
                                                    $num_quantity = array();
                                                    $num_quantity['skuid_quantities'] = $num_quantity['outerid_quantities'] = $num_quantity['num_iid'] = "";
                                                }
                                            }
                                        } else {
                                            $k++;
                                            //taobao.skus.quantity.update 最多支持20个sku修改库存
                                            if (empty($num_quantity['num_iid'])) {
                                                $num_quantity['num_iid'] = $value['num_iid'];
                                            }
                                            $num_quantity['skuid_quantities'] .= $sku_value->sku_id.":".$num.";";
                                            $num_quantity['outerid_quantities'] .= $sku_value->outer_id.":".$num.";";
                                            if ($k % 20 == 0) {
                                                $item_quantity_list[] = $num_quantity;
                                                $num_quantity = array();
                                                $num_quantity['skuid_quantities'] = $num_quantity['outerid_quantities'] = $num_quantity['num_iid'] = "";
                                            }
                                        }
                                    }
                                }
                            }
                            if ($k % 20 != 0) {
                                $item_quantity_list[] = $num_quantity;
                            }
                        } else {
                            //无sku
                            foreach ($items[$item_value->num_iid] as $v) {
                                if (!empty($v['outer_id']) && $v['outer_id'] == $item_value->outer_id) {
                                    $num_quantity['num_iid'] = $v['num_iid'];
                                    $num_quantity['skuid_quantities'] = "";
                                    $num_quantity['quantity'] = $v['available_to_reserved'] - $item_value->num;
                                    if (in_array($taobaoShop['application_key'], $normalIncrementAppKeyList)) {
                                        if ($num_quantity['quantity'] > 0) {
                                            $item_quantity_list[] = $num_quantity;
                                        }
                                    } else {
                                        $item_quantity_list[] = $num_quantity;
                                    }
                                    $num_quantity = array();
                                    $num_quantity['num_iid'] = $num_quantity['quantity'] = "";
                                }
                            }
                        }
                    }
                } else {
                    echo($response->getMsg().": ".$response->getSubMsg()."\n");
                }
            } catch (Exception $e) {
                echo($e->getMessage()."\n");
                continue;
            }
            usleep(500000);
        }
        //需要更新淘宝库存的
        foreach ($item_quantity_list as $item) {
            if (empty($item['skuid_quantities'])) {
                $request = array(
                    'num_iid' => $item['num_iid'],
                    'quantity' => $item['quantity'],
                    'type' => 2, //增量更新
                );
                try {
                $response=$this->getTaobaoClient($taobaoShop)->execute('taobao.item.quantity.update', $request);
                if ($response->isSuccess()) {
                    echo " num_iid update stock seccess num_iid: " . $item['num_iid']." quantity: " . $item['quantity']
                    ." now quantity ". $response->item->num . "\n";
                } else {
                    echo " num_iid update stock failed num_iid:  " . $item['num_iid']." quantity: " . $item['quantity']
                    ." now quantity ". $response->item->num . "\n";
                }
                } catch (Exception $e) {
                    echo $e->getMessage() . "\n";
                }
            } else {
                $request = array(
                    'num_iid' => $item['num_iid'],
                    'type' => 2, //增量更新
                    'skuid_quantities' => $item['skuid_quantities'],
                    'outerid_quantities' => $item['outerid_quantities'],
                );
                try {
                    $response=$this->getTaobaoClient($taobaoShop)->execute('taobao.skus.quantity.update', $request);
                    if ($response->isSuccess()) {
                        echo " num_iid update stock seccess num_iid: " . $item['num_iid']." skuid_quantities: " . $item['skuid_quantities']
                        ." outerid_quantities ". $item['outerid_quantities'] . "\n";
                    } else {
                        echo " num_iid update stock failed num_iid: " . $item['num_iid']." skuid_quantities: " . $item['skuid_quantities']
                        ." outerid_quantities ". $item['outerid_quantities'] . "\n";
                    }
                }catch (Exception $e) {
                    echo $e->getMessage() . "\n";
                }
            }
        }
        $end = microtime(true);
        echo "[".date('c')."] " ."店铺名称：" . $taobaoShop['nick'] ." 增量库存同步时间：".($end-$start) ."\n";
    }
	protected function beforeAction($action, $params) {
		$lockName="commands.".$this->getName().".".$action;
		$lock=Yii::app()->getComponent('lock');
		if ($this->getName() == "taobaosync" && $action == "syncItemStock") {
			if(($lock=Yii::app()->getComponent('lock'))!==null && !$lock->acquire('commands.taobaosync.syncOrder',300)) {
				 echo "[".date('c')."] " ." 订单正在同步，暂不启用库存同步\n";
				return false;
			}
		}
		return parent::beforeAction($action, $params);
	}
    /**
     * 同步淘宝库存
     * 
     * @param string $appkey 执行店铺的应用编号
     * @param integer $seconds 修改时间
     */
    public function actionSyncItemStock($appkey=null,$seconds=null)
    {
        // 启用库存同步的店铺列表
        $include_list = array
        (
//            'cccc89fddefb426fac12af2084fb9656',          // 欧酷数码专营店
//            'cb6d61c0c6f14dc0a298c9d7cb9d4495',          // 惠普笔记本专供店
//            '0780b32fa52c43b1809e39d4ca3e02af',          // lchen1979
//            'f2c6d0dacf32102aa822001d0907b75a',          // 乐其数码专营店
//            'd1ac25f28f324361a9a1ea634d52dfc0',          // 怀轩名品专营店
//            '566ad34e11a143ef8cb77737762e7d54',          // 森马官方旗舰店
//            '56d31a317f3148fab96c10d4762e1efe',          // 夏娃之秀官方旗舰店
//            'fd42e8aeb24b4b9295b32055391e9dd2',          // oppo乐其专卖店
              '239133b81b0b4f0ca086fba086fec6d5',          // 贝亲官方旗舰店
              '11b038f042054e27bbb427dfce973307',          // 多美滋官方旗舰店
//            'f2c6e386cf32102aa822001d0907b75a',          // 奥普电器旗舰店
//            'a54c58229b474d2694a5ba3d304c71ec',          // 孕之彩官方旗舰店
              'ee0daa3431074905faf68cddf9869895',          // accessorize旗舰店
//            'ee6a834daa61d3a7d8c7011e482d3de5',          // 金奇仕官方旗舰店
//            'fba27c5113229aa0062b826c998796c6',          // 方广官方旗舰店
//            'f38958a9b99df8f806646dc393fdaff4',          // 阳光豆坊旗舰店
//            '7f83e72fde61caba008bad0d21234104',          // nutricia官方旗舰店
              '62f6bb9e07d14157b8fa75824400981f',          // 雀巢官方旗舰店
              '753980cc6efb478f8ee22a0ff1113538',          // gallo官方旗舰店
//            '589e7a67c0f94fb686a9287aaa9107db',          // yukiwenzi-分销
              'fe1441b38d4742008bd9929291927e9e',          // 好奇官方旗舰店
              'f1cfc3f7859f47fa8e7c150c2be35bfc',          // 金佰利官方旗舰店
              'dccd25640ed712229d50e48f2170f7fd',          // ecco爱步官方旗舰店
              '9f6ca417106894739e99ebcbf511e82f',          // 每伴旗舰店
//              'd2c716db4c9444ebad50aa63d9ac342e',          // 皇冠巧克力
			  '6ecd27fb75354272ba07f08a2507fa40',          // 蒙牛母婴旗舰店
			  '85b1cf4b507b497e844c639733788480',          // 安满官方旗舰店
			  '7626299ed42c46b0b2ef44a68083d49a',          // blackmores官方旗舰店
              '87b6a6a6ced1499c90073197670b54ce',          //玛氏宠物旗舰店
              '573d454e82ff408297d56fbe1145cfb9',          //金宝贝
              '9781a6fe164a4193acf195d68c10ddfc',          //保乐力加
              'ea66b158e2fb102ab1b4001d0907b75a',          //小树苗
              '4fe1115f63f14f7fb6edaafe519f63ce',          //中美史克
              'f2c6efe8cf32102aa822001d0907b75a',          //黄色小鸭
              '7c6a0d1d9c4d4b9c9dc163121b318832',          //雀巢母婴官方旗舰店
              '60e1863c867610309a21003048df78e2',          //金佰利商用官方旗舰店
              '15301d9800d5102fa5bd003048df78e2',         //皇上皇旗舰店
              '9d70da12970b10309a21003048df78e2',         //三雄极光官方旗舰店
              '15a7a931bfe4481cbacf2d304b45ea30',         //ecco女鞋旗舰店
              '8499f592bc77102fa5bd003048df78e2',         //苏州乐贝母婴专营
              '4e4f2770d690409a96883fe2df14954c',         //人头马
              'ebcff996c16210309a21003048df78e2',         //依云
              'fff088943c02103185cd003048df78e2',         //凯伦赫容 
              '91a6650ad49b102ea5bd003048df78e2',         //百事
              'afdef8b4902f1031a439003048df78e2',         //百威英博官方旗舰店
              'c8ad8a440d5b102fa5bd003048df78e2',         //亨氏
        );
        
        //获取七天内系统中未预定成功订单中指定商品的使用库存
        $start_time = date("Y-m-d H:i:s", time()-3600*24*7);
        $end_time = date("Y-m-d H:i:s", time());
        $need_to_reserved_list = $this->getPorductNeedToReservedList($start_time, $end_time);
        $need_to_reserved_hashamp = Helper_Array::toHashmap((array)$need_to_reserved_list, 'product_id', 'pending_count');

        $start = microtime(true);
        $sql="
			SELECT goods_id,style_id,num_iid,sku_id,outer_id,url,quantity,approve_status,title,url,
					is_auto_reserve,reserve_quantity,is_use_reserve
			FROM ecs_taobao_goods
			WHERE status = 'OK' AND approve_status = 'onsale' AND application_key=:appkey 
		";
		
        if($seconds!==null)  // 只同步淘宝上近期有修改的商品（比如卖出的商品）
        {
            $start_time=date('Y-m-d H:i:s',time()-$seconds);
            $ended_time=date('Y-m-d H:i:s');
            $sql.=" and last_modify between '$start_time' and '$ended_time'";
        }
        
        $select=Yii::app()->getDb()->createCommand($sql); 
        foreach($this->getTaobaoShopList() as $taobaoShop)
        {
            if(!in_array($taobaoShop['application_key'], $include_list))
            continue;

            if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
            continue;

            // 取得要同步库存的商品和SKU
            $items=$select->bindValue(':appkey', $taobaoShop['application_key'])->queryAll();
            $calc_count = count($items);
            $calc_start = microtime(true);
            
            //同步库存时检查预警库存数量，发送邮件
            $from = array(
                'email' => 'erp@leqee.com', 'name' => '商品同步库存预警列表',
            );
            $sql = "
                select user_name as name, user_email as email from ecshop.ecs_party_assign_email 
                where party_id = '{$taobaoShop['party_id']}' and warning_id = ''
            ";
            $to_email = $this->getSlave()->createCommand($sql)->queryAll();
            $html = '';  //异常商品邮件发送内容
            $html_a = array();
            foreach($items as $idx => $item)
            {
                // 先定义预添加的数组属性
                $items[$idx]['available_to_reserved'] = 0;
                $items[$idx]['stock_quantity'] = 0;
                //仓库库存是有有异常
                $need_to_reserved = 0;  //默认系统中待预定商品使用库存为0
                
                if (strpos($item['outer_id'], 'TC-') !== false) {
                	// 套餐商品同步
                	$taocanStock = $this->getTCStock($taobaoShop['party_id'], $item['outer_id'],$need_to_reserved_hashamp, $taobaoShop['taobao_shop_conf_id']) ;
    	
                    if (!empty($taocanStock)) {
                        $items[$idx]['available_to_reserved'] += $taocanStock['availableToReserved'] ;
                        $items[$idx]['stock_quantity'] += $taocanStock['quantitys'] ;
                        if ($items[$idx]['available_to_reserved'] > $items[$idx]['stock_quantity']) {
                            echo 'taobaoShop '. $taobaoShop['nick'].' outerId '.$item['outer_id'].
                                ' availableToReserved '.$taocanStock['availableToReserved'] . ' stockQuantity ' . $taocanStock['stockQuantity'];
                            //如果可预定库存大于仓库实际库存，则可预定量使用实际库存
                            $items[$idx]['available_to_reserved'] = $items[$idx]['stock_quantity'];
                        } elseif ($item['reserve_quantity'] > $items[$idx]['available_to_reserved']) {
                        	$html_a[$item['num_iid']][] = array('title' => $item['title'], 'outer_id' =>  $item['outer_id'], 'quantity' =>  $item['quantity'],
                        		'available_to_reserved' => $items[$idx]['available_to_reserved'], 'reserve_quantity' => $item['reserve_quantity']);
                            unset($items[$idx]);
                            continue;
                        }
                        if($item['is_use_reserve'] == 1 ) {
                            $items[$idx]['available_to_reserved'] = max(0, $items[$idx]['available_to_reserved'] - $item['reserve_quantity']);
                            echo 'taobaoshop ' .$taobaoShop['nick'].' outer_id '. $item['outer_id'].' num_iid '. $item['num_iid']." subtraction quantity:".$item['reserve_quantity']."\n";
                        }                         
                    }
                    
                } else {
                	$productId = ProductServices::getProductId($item['goods_id'],$item['style_id']);
                	if (empty($productId)) {
                	    echo("[" .date('c') ."] " . "row (goods_id: ".$item['goods_id'].", style_id: ".$item['style_id'].") productId not exists ! \n");	
                	} else {
                		// 取得库存
                		$facilities = FacilityServices::getFacilityByPartyId($taobaoShop['party_id']);  // 可用仓库
            			$facilities = $this->getTaobaoShopFacility ($facilities, $taobaoShop['taobao_shop_conf_id']) ;
                		
                		$inventorySummaryAssoc = InventoryServices::getInventorySummaryAssocByProduct('INV_STTS_AVAILABLE', array_keys($facilities), $productId, null);
                		if (!empty($inventorySummaryAssoc)) {
                			foreach ($inventorySummaryAssoc as $assoc) {
                				foreach ($assoc as $productStock) {
                				    $items[$idx]['available_to_reserved'] += $productStock->availableToReserved;
                                    $items[$idx]['stock_quantity'] += $productStock->stockQuantity;	
                                    //检查每个仓中商品库存数量是否异常
                                    if ($productStock->availableToReserved > $productStock->stockQuantity) {
                                        echo 'taobaoshop ' .$taobaoShop['nick'].' outer_id '. $item['outer_id'] .' facilityId ' . $productStock->facilityId
                                            .' availableToReserved ' . $productStock->availableToReserved . ' stockQuantity ' . $productStock->stockQuantity;
                                        $items[$idx]['available_to_reserved'] = $items[$idx]['stock_quantity'];
                                    }
                                }
                            }
                            //实际可预定商品库存 - ERP里已有订单且未预定成功的预存
                            $need_to_reserved = isset($need_to_reserved_hashamp[$productId])?(($need_to_reserved_hashamp[$productId] != NULL) ? (int)$need_to_reserved_hashamp[$productId] : 0) : 0; 
                            $items[$idx]['available_to_reserved'] = max(0, $items[$idx]['available_to_reserved'] - $need_to_reserved);
                            //检查是否出现库存异常                            
                            if ($item['reserve_quantity'] > $items[$idx]['available_to_reserved']) {
                                //检查商品同步库存小于预警库存时邮件预警 发送给店长
                                $html_a[$item['num_iid']][] = array('title' => $item['title'], 'outer_id' =>  $item['outer_id'], 'quantity' =>  $item['quantity'],
                        		'available_to_reserved' => $items[$idx]['available_to_reserved'], 'reserve_quantity' => $item['reserve_quantity']);                              
                                unset($items[$idx]);
                                continue;
                            }
                            if($item['is_use_reserve'] == 1 ) {
                            	$items[$idx]['available_to_reserved'] = max(0, $items[$idx]['available_to_reserved'] - $item['reserve_quantity']);
                            	echo 'taobaoshop ' .$taobaoShop['nick'].' outer_id '. $item['outer_id'].' num_iid '. $item['num_iid']." subtraction quantity:".$item['reserve_quantity']."\n";
                            } 
                		}
                		
                	}
                }
            }
            $num_keys = $num_quantity_list = array();
            $num_keys = array_keys($html_a);
            $client=$this->getTaobaoClient($taobaoShop);
            //获取可预定量小于预警库存的商品在淘宝上的库存数量
            foreach (array_chunk($num_keys,20) as $item_chunk) {
	            try {
	                // 这个接口一次只能查20个商品，但能取得详细信息
	                $request = array(
		                'fields'=>'iid,num_iid,outer_id,modified,approve_status,num,title,price,sku',
		                'num_iids'=>implode(',',$item_chunk),
	                );
	                $response=$client->execute('taobao.items.list.get',$request);
	                if ($response->isSuccess()) {
	                	 $num_quantity_list = array_merge($num_quantity_list, $response->items->item);
	                } else {
	                	echo($response->getMsg().": ".$response->getSubMsg()."\n");
	                }
	            } catch(Exception $e) {
	                echo($e->getMessage()."\n");
	                continue;
	            }
	            usleep(500000);
	        }

	        foreach ($num_quantity_list as $item) {
	        	if (isset($item->skus)) {
                	foreach($item->skus->sku as $sku) {
                		if (isset($html_a[$item->num_iid])) {
                			foreach ($html_a[$item->num_iid] as $key=>$item_h) {
                				if ($item_h['outer_id'] == $sku->outer_id) {
                					$html_a[$item->num_iid][$key]['quantity'] = isset($sku->quantity)?$sku->quantity:0;
                				}
                			}
                		}
               		 }
            	} else {
            		if (isset($html_a[$item->num_iid])){
            			if ($html_a[$item->num_iid][0]['outer_id'] == $item->outer_id) {
                			$html_a[$item->num_iid][0]['quantity'] = isset($item->num)?$item->num:0;
                		}
            		}
                }
            }
            foreach ($html_a as $num_item) {
            	foreach($num_item as $item){
            		 $html .= "
	                    <tr>
	                        <td>". $item['title'] ."</td>
	                        <td>". $item['outer_id'] ."</td>
	                        <td>". $item['quantity'] ."</td>
	                        <td>". $item['available_to_reserved'] ."</td>
	                        <td>". $item['reserve_quantity'] ."</td>
	                    </tr>
	                ";
            	}
            }
            // 库存预警计算所需时间
            echo "[".date('c')."] " . $taobaoShop['nick'] . " 库存预警计算库存项数：" . $calc_count ." 耗时：".(microtime(true)-$calc_start)."\n";
            
            
            //检查是否需要发送邮件
            if ($html) { 
            	$html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
                    <html xmlns="http://www.w3.org/1999/xhtml">
                    <head>
                    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
                    <style type="text/css">
                        table, table tr, table th, table td{
                            border:1px solid #000;
                            border-collapse: collapse;
                        }
                    </style>
                    </head>
                    <body style="font-size:12px;color:#000;">
                    <div style="margin:auto;width:800px;">
                    <h3>店铺名称：'.$taobaoShop['nick'] .' 请店长注意该商品在淘宝上库存。</h3>
                    <table width="100%">
                        <tr>
                            <th>商品名称</th>
                            <th>商家编码</th>
                            <th>淘宝库存（仅作参考）</th>
                            <th>可预订库存</th>  
                            <th>预警库存</th>
                        </tr>' . $html . "</table></div></body></html>";
            //    $to_email = array_merge($to_email,array(
            //        array('name' => 'ERP', 'email' => 'mjzhou@leqee.com'),
           //         ));
                $subject = "店铺名称：".$taobaoShop['nick'] ."淘宝库存同步商品预警列表";
                $this->sendMail($subject, $from, $to_email, $html, $taobaoShop['nick']);
            }

            // 已上架但库存不足
            // 已下架但有库存
            $sync_start = microtime(true);
            $sync_count = 0;
            foreach($items as $item)
            {
                // 淘宝在售,新库存与淘宝上面不一致的商品
                if( $item['approve_status']=='onsale' 
                	&& $item['available_to_reserved'] >= 0
                	&& $item['available_to_reserved'] != $item['quantity']
                	)
                {
                	$sync_count++;
                	//echo $item['outer_id'].":".$item['available_to_reserved']."\n";
                    $this->updateItemStock($taobaoShop, $item);
                }
            }     
            echo "[".date('c')."] " . $taobaoShop['nick'] . " 库存同步库存项数：" . $sync_count ." 耗时：".(microtime(true)-$sync_start)."\n";
            sleep(1);
        }        
         echo "[".date('c')."] " . "库存同步耗时：".(microtime(true)-$start)."\n";
    }
    /**
     * 
     * 获取店铺同步库存的仓库列表
     */
    function getTaobaoShopFacility ($facilities, $taobao_shop_conf_id) {
    	if (isset($facilities['69897656'])) {
    		unset($facilities['69897656']);//天猫超市(上海)
    	}
    	if (isset($facilities['77451244'])) {
    		unset($facilities['77451244']);//天猫超市(东莞)
    	}
    	if ($taobao_shop_conf_id == 14) {
    		//电商服务商2
    		if (isset($facilities['76065524'])) {
    			unset($facilities['76065524']);
    		}
    	}
        if ($taobao_shop_conf_id == 22) {
        	//金佰利旗舰店
        	if (isset($facilities['19568548'])) {
        		unset($facilities['19568548']);//电商服务东莞仓
        	}
        }
        return $facilities;
    }

    /**
	 * 更新淘宝商品库存
	 * 
	 * @param array $taobaoShop 淘宝店铺 
	 * @param array $item       商品
	 * 
	 * @return boolean
	 */
    protected function updateItemStock($taobaoShop, $item)
    {
        if ($item['approve_status']=='' || !in_array($item['approve_status'], array('onsale','instock')))
        {
            echo("|  item: (num_iid: ".$item['num_iid'].") ".$item['title']." approve_status is not valid! \n");
            return false;
        }

        $request = array(
                     'num_iid' => $item['num_iid'],
                     'type' => 1, 
                  ) ;
        if (!empty($item['sku_id'])) {
        	$request['sku_id'] = $item['sku_id'] ;
        }
        
        $request['quantity'] = $item['available_to_reserved'];
		// 库存同步更新           
    	try {
       		$response=$this->getTaobaoClient($taobaoShop)->execute('taobao.item.quantity.update', $request);     
         	if ($response->isSuccess()) {
         		echo("[" . date('c') . "] ". "succeed: (num_iid = {$item['num_iid']} outer_id = {$item['outer_id']}" . ") " . $item['title'] . " {$item['quantity']} -> {$request['quantity']} " . " \n");	
           		//同步成功记录同步历史
				$c = array("skuId"=>$item['sku_id'], "numIid" =>$item['num_iid'], "outerId"=> $item['outer_id'],
				   "partyId"=> (int)$taobaoShop['party_id'], "taobaoQuantity" => (int)$item['available_to_reserved']);
				$client = Yii::app()->getComponent('romeo')->TaobaoOrderService;
				$client->createTaobaoInventory($c);
           	}else{
           		echo("[" . date('c') . "] " . "failed: (num_iid = {$item['num_iid']} outer_id = {$item['outer_id']}" . ") " . $item['title'] . $response->getMsg() . ", " . $response->getSubMsg() . "\n");	
           	}   
      	} catch(Exception $e) {
      			echo("[" . date('c') . "] " . "failed: (num_iid = {$item['num_iid']} outer_id = {$item['outer_id']}" . ") " . $item['title'] . $e->getMessage(). " \n");
      	}
    }
    
    
    /**
     * 根据传入的ERP套餐，查找出套餐内各商品库存 —> 套餐库存 以最少的商品为准
     * 
     */
    protected function getTCStock($partyId, $taocanCode,$need_to_reserved_hashamp, $taobao_shop_conf_id) {
    	// 查找套餐内商品
    	$sql = "select gi.goods_id, gi.style_id, sum(gi.goods_number) as goods_number
                  from ecshop.distribution_group_goods g 
                     inner join ecshop.distribution_group_goods_item gi on g.group_id = gi.group_id
                 where g.code = '%s' and g.party_id = '%d' and g.status = 'OK' 
                 group by gi.goods_id, gi.style_id" ;
                 
        $goodsItemList = $this->getSlave()->createCommand(sprintf($sql, $taocanCode, $partyId))->queryAll();
        if (empty($goodsItemList)) {
        	return null ;
        }

        // 转化成 product 
        $products = array();
        foreach ($goodsItemList as $goodsItem) {
        	$productId = ProductServices::getProductId($goodsItem['goods_id'], $goodsItem['style_id']);
        	if($productId === null) {
        		echo("|  row (" . $taocanCode . " goods_id: ".$goodsItem['goods_id'].", style_id: ".$goodsItem['style_id'].") productId not exists ! \n");
        	} else {
        		$products[$productId] = intval($goodsItem['goods_number']);
        	}
                            	
        }
    	// 可用仓库
        $facilities=FacilityServices::getFacilityByPartyId($partyId);
    	$facilities = $this->getTaobaoShopFacility ($facilities, $taobao_shop_conf_id) ;
    	if ($facilities!==array()) {
                $inventorySummaryAssoc=InventoryServices::getInventorySummaryAssocByProduct('INV_STTS_AVAILABLE',array_keys($facilities),count($products)<100?array_keys($products):null,null);
        }  
   
        // 确认套餐的库存量
        if (!empty($inventorySummaryAssoc)) {
        	$quantitys = 100000 ;
        	$availableToReserved = 0 ;
        	$result = array();
            $need_to_reserved = 0; //七天未预定订单商品数量。
        	foreach ($inventorySummaryAssoc as $key => $productInvs) {
        		$productQuantity = 0 ;
        		$reserveQuantity = 0 ;
        		foreach ($productInvs as $productInv) {
        			$productQuantity += $productInv->stockQuantity ;
        			$reserveQuantity += $productInv->availableToReserved;
        		    if ($reserveQuantity > $productQuantity) {
                        echo 'outerId '.$taocanCode .' product_id ' . $productInv->productId . ' facilityId ' . $productInv->facilityId 
                        . ' availableToReserved ' . $productInv->availableToReserved . ' stockQuantity ' .$productInv->stockQuantity." \n";
                        $reserveQuantity = $productQuantity;
        		    }
        		}
                //检查七天内未预定商品数量  先减去单品的数量再计算套餐的数量
                $need_to_reserved =isset($need_to_reserved_hashamp[$key]) ? (($need_to_reserved_hashamp[$key] != NULL) ? (int)$need_to_reserved_hashamp[$key] : 0) : 0;
                $reserveQuantity = max(($reserveQuantity - $need_to_reserved), 0);

                if (floor($productQuantity / $products[$key]) < $quantitys) {
        			$quantitys = floor($productQuantity / $products[$key]) ;
        			$availableToReserved = floor($reserveQuantity / $products[$key]) ;
        	    }
        			
        	}
            $product_size = $product_stock_size = 0;
            $product_size = count($products);
            $product_stock_size = count($inventorySummaryAssoc);
            // 当summuary中无记录时  商品未进行收货入库
            if ($product_size > $product_stock_size) {
                $quantitys = 0;
                $availableToReserved = 0;
            }
        	$result['quantitys'] = $quantitys ;
        	$result['availableToReserved'] = $availableToReserved ;
        	
        	return $result ;
        	
        } else {
        	return null ;
        }
	
    }
    /**
     * 获取所有店铺七天内未预订单成功订单所使用商品库存(排除天猫仓的订单)
     */
 	protected function getPorductNeedToReservedList($startTime, $endTime){
    	$sql = "
    	    SELECT 		p.product_id, sum(IF (r.goods_number is null, og.goods_number, (r.goods_number - r.reserved_quantity))) as pending_count
    		FROM		ecshop.ecs_order_info o 
            LEFT JOIN   ecshop.ecs_order_goods og on o.order_id = og.order_id
            LEFT JOIN   romeo.product_mapping p on og.goods_id = p.ecs_goods_id and og.style_id = p.ecs_style_id
    		LEFT JOIN	romeo.order_inv_reserved_detail r on convert(og.rec_id using utf8) = r.order_item_id
    		WHERE 	 	o.order_status in(0, 1) 
    		AND 		o.shipping_status IN (0, 10)
    		AND 		o.facility_id is not null
    		AND 		o.facility_id != ''  
    		AND			( (o.order_type_id = 'SALE' AND (o.pay_id = 1 OR o.pay_status = 2) ) 
						OR o.order_type_id IN ('RMA_EXCHANGE', 'SHIP_ONLY'))
		    AND 		o.order_time <= '{$endTime}' 
    		AND 		o.order_time > '{$startTime}'
    		AND 		p.product_id IS NOT NULL and o.facility_id not in ('77451244', '69897656')
    		GROUP BY 	p.product_id
    		HAVING		pending_count > 0
    	";
        $need_to_reserved_list = $this->getSlave()->createCommand($sql)->queryAll();
	    return $need_to_reserved_list;
    }
    /**
	 * 取得某个淘宝店铺的在售的（或者库存中）的商品
	 * 
	 * @param array   淘宝店铺
	 * @param string  商品上传后的状态。onsale出售中，instock库中，不选表示所有
	 * @param 
	 */
    protected function getTaobaoItems($taobaoShop, $approve_status=null, $start_modified=null, $end_modified=null)
    {
        $item_list=$num_iids=array();
        $client=$this->getTaobaoClient($taobaoShop);
        $request=array(
	        'fields'=>'num_iid',
	        'page_no'=>1,
	        'page_size'=>40,
        );
        
        if($start_modified!==null) {
        	$request['start_modified']=$start_modified;
        }
        
        if($end_modified!==null) {
        	$request['end_modified']=$end_modified;
        }

        // 取得在售商品
        $onsale_num_iids = array();
        if($approve_status==null || $approve_status=='onsale')
        {
        	$onsale_num_iids = $this->getTaobaoItemsByStatus($client, 'onsale', $request, 'num_iid');
        }

        // 取得库存中的商品列表
        $instock_num_iids = array();
        if($approve_status==null || $approve_status=='instock')
        {
        	$request['banner']= 'never_on_shelf';
        	$instock_num_iids = $this->getTaobaoItemsByStatus($client, 'instock', $request, 'num_iid');
        }

        // 循环商品并取得详细信息
        $num_iids = array_merge($onsale_num_iids, $instock_num_iids);
        foreach(array_chunk($num_iids,20) as $item_chunk)
        {
            try
            {
                // 这个接口一次只能查20个商品，但能取得详细信息
                $request=array(
	                'fields'=>'iid,num_iid,outer_id,modified,approve_status,detail_url,num,title,price,sku,list_time,delist_time',
	                'num_iids'=>implode(',',$item_chunk),
                );
                $response=$client->execute('taobao.items.list.get',$request);
                if($response->isSuccess())
                	$item_list=array_merge($item_list,$response->items->item);
                else
                	echo($response->getMsg().": ".$response->getSubMsg()."\n");
            }
            catch(Exception $e)
            {
                echo($e->getMessage()."\n");
                continue;
            }
            usleep(500000);
        }

        return $item_list;
    }
    
    public function actionSyncCStoreTaobaoBuyerNick() {
    	// 目前只有nutricia业务下有C店
    	$taobaoAppMapping = array(
    	         // yukiwenzi
    	         176 => array(
                           'appkey' => '12400903',
                           'secret' => '276bb418d97afcfb02b73ae863395cf6', 
                           'sessionKey' => '61005136ce29e80b037b01fe52fed798990eeb451f023b135958658', 
                           'version' => '2.0',  
    	              ),
    	         // lchen1979
    	         178 => array(
                           'appkey' => '12401370',
                           'secret' => 'f168ad8846a06a52bf2bf8fc3deb87bf', 
                           'sessionKey' => '6100d05240b261860e857718ea1624e34698afb49956ff3359780010', 
                           'version' => '2.0',  
    	              ),
    	     );
                   
        // 远程服务
        $client = Yii::app()->getComponent('romeo')->TaobaoOrderService;
        $request = array();
        
        foreach ($taobaoAppMapping as $key => $taobaoApp) {
        	// 传入参数
        	$request['distributorId'] = $key ;
        	$request['appkey'] = $taobaoApp['appkey'];
        	$request['secret'] = $taobaoApp['secret'];
        	$request['sessionKey'] = $taobaoApp['sessionKey'];
        	$request['version'] = $taobaoApp['version'];
        	
        	// 调用ROMEO接口
        	$response = $client->syncC2CStoreTaobaoBuyerNick($request);
        	
        	print_r($response);
        }
        
        
    	
    }

    /**
	 * 返回请求对象
	 *
	 * @param array $taobaoShop
	 * @return TaobaoClient
	 */
    protected function getTaobaoClient($taobaoShop)
    {
        static $clients=array();
        $key=$taobaoShop['taobao_shop_conf_id'];
        if(!isset($clients[$key]))
        $clients[$key]=new TaobaoClient($taobaoShop['params']['app_key'],$taobaoShop['params']['app_secret'],$taobaoShop['params']['session_id'],($taobaoShop['params']['is_sandbox']=='Y'?true:false));
        return $clients[$key];
    }

    /**
	 * 取得启用的淘宝店铺的列表
	 * 
	 * @return array
	 */
    protected function getTaobaoShopList()
    {
        static $list;
        if(!isset($list))
        {
            $sql="select * from taobao_shop_conf where status='OK' and shop_type = 'taobao' ";
            $list=$this->getSlave()->createCommand($sql)->queryAll();
            $command=$this->getSlave()->createCommand("select * from taobao_api_params where taobao_api_params_id=:id");
            foreach($list as $key=>$item)
            $list[$key]['params']=$command->bindValue(':id',$item['taobao_api_params_id'])->queryRow();
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
     * 取得淘宝上待上架且ERP里面有库存的商品，发送邮件与相关店长
     * 话说这个command也就这个有点用了
     */
    public function actionWarnInventoryShelvedGoods () {
          // 不启用商品同步的列表
        $exclude_list=array
        (
         // 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
         // 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
        );
        $subject = "淘宝待上架且ERP有库存商品预警";
        $from = array(
            'email' => 'erp@leqee.com', 'name' => 'ERP定时库存警报任务'
            );
        //目前暂停店铺组织列表列表
        $Shop = array('1', '65537', '65538', '64', '65541', '65544', '65548', '65550');
        // 循环每个店铺
        foreach($this->getTaobaoShopList() as $taobaoShop)
        {
            // 排除店铺
            if(in_array($taobaoShop['application_key'], $exclude_list))
            continue;

            // 指定店铺
            if(isset($appkey)&&$appkey!=$taobaoShop['application_key'])
            continue;
            
            $list = array();
            //获取一个店铺中待上架的商品列表
            $list = $this->GetTaobaoShelvedGoodsList($taobaoShop);
            //检查是否存在需要警报的商品信息，如果不存在则不发送邮件
            if (empty($list)) {
                echo $taobaoShop['nick']."no need send email \n";
                continue;
            } else {
                $html = $this->getEmailHtml($list, $taobaoShop['nick']);
                $sql = "
                    select user_name as name, user_email as email from ecshop.ecs_party_assign_email 
                    where party_id = '{$taobaoShop['party_id']}' and warning_id = ''
                ";
                $email = $this->getSlave()->createCommand($sql)->queryAll();
                $to = array(
                    array('name' => 'zgliu@leqee.com', 'email' => '刘志刚'),
                    array('name' => 'lwlei@leqee.com', 'email' => '雷林伟'),
                    array('name' => 'bfan@leqee.com', 'email' => '樊斌'),
                );
                $email = array_merge($email, $to);
                //发送邮件方法
                if (in_array($taobaoShop['party_id'], $Shop) || empty($email)) {
                    continue;
                } else {
                    $this->sendMail($subject, $from, $email, $html, $taobaoShop['nick']);
                }
            }
        }
    }
    
    protected function getTaobaoItemsByStatus($client, $approve_status, $request, $field) {
    	$method = '';
    	$list= array();
    	if ($approve_status == 'onsale') {
    		$method= 'taobao.items.onsale.get';
    	} elseif ($approve_status=='instock') {
    		$method= 'taobao.items.inventory.get';
    	} else {
    		return $list;
    	}
    	
    	$repeat=1;
        do
        {
       		try
			{
            	$request['page_no']=$repeat++;
             	$response=$client->execute($method,$request);
                if($response->isSuccess())
                {
                	if(isset($response->total_results) && $response->total_results>0)
                   	{
                    	foreach($response->items->item as $item) {
                    		if($field == 'num_iid') {
                      			$list[]=$item->num_iid;
                    		} elseif ($field == 'outer_id' && !empty($item->outer_id) && !is_object($item->outer_id)) {
                    			$list[]=$item->outer_id;
                    		}
                    	}

                     	if($request['page_no']*$request['page_size']>=$response->total_results)
                      		break;
                   	}
                   	else
                        break;
				}
            	else
       			{
                	echo $method . ", " . $response->getMsg(). ": " . $response->getSubMsg() . "\n";
                  	break;
               	}
         	}
           	catch(Exception $e)
           	{
            	echo($e->getMessage()."\n");
            	break;
           	}
          	usleep(500000);
    	}while($repeat>0 && $repeat<30);
    	
    	return $list;
    }
    /**
     * 发送邮件
     * @param string $subject
     * @param $array $from  $from = array ('email' => 'erp@leqee.com', 'name' => 'ERP定时库存警报任务');
     * @param $array $to $to = array (array('email' => 'bbgfx@i9i8.com', 'name' => '步步高电教分销组'));
     */
    private function sendMail($subject, $from, $to, $html, $shop_name) {
        if (empty($subject)) {
            echo "{$shop_name}邮件主题为空。\n";
            return ;
        }
        if (empty($from)) {
            echo "{$shop_name}邮件发送者信息为空。\n";
            return ;
        }
        if (empty($to)) {
            echo "{$shop_name}邮件接收者信息为空。\n";
            return ;
        }
        $mail = Helper_Mail::smtp();
        $mail->IsSMTP();                 // 启用SMTP
        $mail->Host="smtp.exmail.qq.com";      //smtp服务器 以126邮箱为例子
        $mail->SMTPAuth = true;         //启用smtp认证
        $mail->Username = Yii::app()->params['emailUsername'];   // 你的邮箱地址
        $mail->Password = Yii::app()->params['emailPassword'];      //你的邮箱密码  */

        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->SetFrom(Yii::app()->params['emailUsername'], "erp-report");
        $mail->IsHTML(true);
        $mail->ClearAddresses();
//        $mail->AddAddress("zgliu@leqee.com", "刘志刚");
//        $mail->AddAddress("lwlei@leqee.com", "雷林伟");
//        $mail->AddAddress("bfan@leqee.com", "樊斌");
        foreach ($to as $item) {
            $item['email'] = trim($item['email']);
            $mail->AddAddress("{$item['email']}", "{$item['name']}");
        }
        
        $mail->Body = $html;
        if ($mail->send()){
            echo "send email to {$shop_name} successful \n";
        } else {
            echo "send email to {$shop_name} failed \n";
        }
    }
    /**
     * 
     * 返回发送邮件的html代码
     * @param array $list
     * @param string $shop_name
     * @return string
     */
    private function getEmailHtml($list, $shop_name) {
         $html = '
            <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
            <html xmlns="http://www.w3.org/1999/xhtml">
            <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
            </head>
            <body style="font-size:12px;color:#000;">
            <div style="margin:auto;width:800px;">
            <h3>淘宝上待上架商品且ERP有库存的商品警报列表（'. date('Y-m-d H:i') .'）</h3>
            <table width="100%" border="1px;">
                    <tr>
                        <th>No.</th>
                        <th>所属业务</th>
                        <th>淘宝上商品名称</th>  
                        <th>淘宝上显示库存</th>
                        <th>ERP商品名称</th>
                        <th>ERP库存</th>
                    </tr>   
            ';
         $i = 1;
         foreach ($list as $key => $item) {
             $html .= "
                 <tr>
                     <td>{$i}</td>
                     <td>{$shop_name}</td>
                     <td>{$item['taobao_goods_name']}</td>
                      <td>{$item['taobao_goods_num']}</td>
                     <td>{$item['product_name']}</td>
                     <td>{$item['erp_goods_num']}</td>
                 </tr>
             ";
             $i ++;
         }
         $html .= "</table></div></body></html>";
         return $html;
    }
    /**
     * 返回该店铺淘宝待上架且ERP系统中有可预订库存的商品列表
     * @param array $taobaoShop
     * @return array $list 
     */
    private function GetTaobaoShelvedGoodsList ($taobaoShop) {
        $list = null;
        $client = $this->getTaobaoClient($taobaoShop);
        $request = array(
            'fields' => 'approve_status,num_iid,title,nick,type,cid,pic_url,num,props,valid_thru,list_time,price,has_discount,has_invoice,has_warranty,has_showcase,modified,delist_time,postage_id,seller_cids,outer_id',
            'page_no' => 1,
            'page_size' => 40,
            'banner' => 'off_shelf',
        );
        $response = $client->execute('taobao.items.inventory.get', $request);
        if ($response->isSuccess()) {
            if (isset($response->total_results) && $response->total_results>0) {
                foreach ($response->items->item as $item) {
                    if (isset($item->outer_id) && $this->checkOuterId($taobaoShop, $item->outer_id)) {
                        if ( strpos($item->outer_id, 'TC-') !== false) {
                            $group_goods_sql = "
                                select g.name, gi.goods_id, gi.style_id, gi.goods_name
                                from ecshop.distribution_group_goods g 
                                left join ecshop.distribution_group_goods_item gi on g.group_id = gi.group_id
                                where g.code =:code and g.status = 'OK' ";
                            $group_goods = $this->getSlave()->createCommand($group_goods_sql)
                                ->bindValue(':code', $item->outer_id)->queryAll();
                                if (!empty($group_goods)) {
                                    foreach ($group_goods as $goods_item) {
                                        $sql = "
                                            select s.available_to_reserved, p.product_name
                                            from romeo.product_mapping pm
                                            left join romeo.inventory_summary s on pm.product_id = s.product_id
                                            left join romeo.product p on p.product_id = pm.product_id
                                            where pm.ecs_goods_id =:goods_id and pm.ecs_style_id =:style_id 
                                                and s.status_id = 'INV_STTS_AVAILABLE'";
                                        $good_info = $this->getSlave()->createCommand($sql)
                                        ->bindValue(':goods_id', $goods_item['goods_id'])
                                        ->bindValue(':style_id', $goods_item['style_id'])->queryRow();
                                        if (!empty($good_info) && $good_info['available_to_reserved'] > 0) {
                                            $v = null;
                                            $v['taobao_goods_name'] = $item->title;
                                            $v['taobao_goods_num'] = $item->num;
                                            $v['erp_goods_num'] = $good_info['available_to_reserved'];
                                            $v['product_name'] = $good_info['product_name']. "(ERP套餐名称:" .$goods_item['name'].")";
                                            $list[] = $v;
                                        } else {
                                           continue;
                                        }
                                    }
                                } else {
                                   continue;
                                }
                        } elseif (!empty($item->outer_id)) {
                            if (strpos($item->outer_id, '_') !== false) {
                                $goods = explode('_', $item->outer_id);
                                $goods_id = $goods[0];
                                $style_id = isset($goods[1]) ? $goods[1] : 0;
                            } else {
                                $goods_id = $item->outer_id;
                                $style_id = 0;
                            }
                            $sql = "
                                select s.available_to_reserved, p.product_name
                                from romeo.product_mapping pm
                                left join romeo.inventory_summary s on pm.product_id = s.product_id
                                left join romeo.product p on p.product_id = pm.product_id
                                where pm.ecs_goods_id =:goods_id and pm.ecs_style_id =:style_id 
                                    and s.status_id = 'INV_STTS_AVAILABLE'";
                            $good_info = $this->getSlave()->createCommand($sql)
                                         ->bindValue(':goods_id', $goods_id)
                                         ->bindValue(':style_id', $style_id)->queryRow();
                            if (!empty($good_info) && $good_info['available_to_reserved'] > 0) {
                                $v = null;
                                $v['taobao_goods_name'] = $item->title;
                                $v['taobao_goods_num'] = $item->num;
                                $v['erp_goods_num'] = $good_info['available_to_reserved'];
                                $v['product_name'] = $good_info['product_name'];
                                $list[] = $v;
                            } else {
                                continue;
                            }
                        } else {
                            continue;
                        }
                    } else {
                        continue;
                    }
                }
            }
            else
            break;
        }else{
            echo( "淘宝店铺： " . $taobaoShop['nick'] . "获取待上架商品失败，" .$response->getMsg().", ".$response->getSubMsg()."\n");
        }
        return $list;
    }
    /**
     * 
     * 同步库存之前检查商品库存是否小于等于预留库存数量
     * @param array $item
     */
    private function check_goods_number ($item) {
        $sql = "
            select sum(s.stock_quantity) as stock_num, sum(s.available_to_reserved) as available_num, p.product_name
            from romeo.product_mapping pm
            left join romeo.product p on p.product_id = pm.product_id
            left join romeo.inventory_summary s on pm.product_id = s.product_id 
            where pm.ecs_goods_id = :goods_id and pm.ecs_style_id =:style_id and s.status_id = 'INV_STTS_AVAILABLE'
            group by p.product_id
        ";
        $goods_stock = $this->getSlave()->createCommand($sql)
            ->bindValue(':goods_id', $item['goods_id'])
            ->bindValue(':style_id', $item['style_id'])->queryRow();
        if ($goods_stock['stock_num'] < $goods_stock['available_num']) {
            //库存异常报警给ERP
            $goods = "商品名：".$item['product_name']."goods_id:".$item['goods_id']."style_id:".$item['style_id'].
                "实际库存量：".$goods_stock['stock_num']."可预定量：". $goods_stock['available_num']."<\n>";
            return $goods;
        } elseif ($goods_stock['available_num'] <= $item['reserve_quantity']) {
            return false;
        } else {
            return true;
        }
        
    }
    
    /**
     * 同步交易关闭的订单,更新系统订单状态
     */
    
    public function actionSyncCloseOrderStatus() {
        //指定的店铺
        $exclude_list=array
        (
            '573d454e82ff408297d56fbe1145cfb9',  // gymboree官方旗舰店
        );
        //指定的状态
        $status_list = array 
        (
            'TRADE_CLOSED', //付款以后用户退款成功，交易自动关闭
        );
        $db=Yii::app()->getDb();
        $builder = $db->getCommandBuilder();
        foreach($this->getTaobaoShopList() as $taobaoShop) {
            if(!in_array($taobaoShop['application_key'], $exclude_list)) {
                continue;
            }
            $client = $this->getTaobaoClient($taobaoShop);
            $sql = "select distinct o2.taobao_order_sn, o2.order_id
                from ecshop.ecs_order_info o 
                inner join order_relation r on o.order_id = r.order_id 
                inner join ecshop.ecs_order_info o2 on r.root_order_id = o2.order_id 
                where o.order_type_id = 'RMA_RETURN' 
                and o2.order_type_id = 'SALE' 
                and o2.taobao_order_sn is not null 
                and o2.taobao_order_sn <> ''
                and o2.shipping_status not in (2, 3) 
                and o2.order_status not in (0, 2)
                and o.party_id = {$taobaoShop['party_id']}
                and o2.distributor_id = {$taobaoShop['distributor_id']}";
            $list = $db->createCommand($sql)->queryAll();
            foreach($list as $order) {
                if (! is_numeric($order['taobao_order_sn'])) {
                    continue;
                }
//                $request = array(
//                    'fields' => 'tid,status',
//                    'tid' => $order['taobao_order_sn']
//                );
//                $response = $client->execute('taobao.trade.fullinfo.get', $request);
				$sql = "select status from ecshop.sync_taobao_order_info where tid = '{$order['taobao_order_sn']}' limit 1";
				$status = $db->createCommand($sql)->queryScalar();
                if(!empty($status)) {
//                    $status = $response->trade->status;
                    if(in_array($status, $status_list)) {
                        //修改订单状态,不修改付款状态
                        $sql = "update ecshop.ecs_order_info set order_status = 4, shipping_status = 3 where order_id = {$order['order_id']} limit 1";
                        $db->createCommand($sql)->execute();
                        
                        //获得上一个订单变更历史的其他数据
                        $sql = "select pay_status, invoice_status, shortage_status from ecshop.ecs_order_action where order_id = {$order['order_id']} order by action_id desc limit 1";
                        $order_action = $db->createCommand($sql)->queryRow();
                        //记录订单变更历史
                        $data = array(
                            'order_id' => $order['order_id'],
                            'action_user' => 'webService',
                            'shipping_status' => 3,
                            'order_status' => 4,
                            'pay_status' => $order_action['pay_status'],
                            'action_time' => date("Y-m-d H:i:s"),
                            'action_note' => "系统同步淘宝状态(交易关闭)",
                            'invoice_status' => $order_action['invoice_status'],
                            'shortage_status' => $order_action['shortage_status'],
                        );
                        $table = $db->getSchema()->getTable('ecs_order_action');
                        $builder->createInsertCommand($table, $data)->execute();
                        echo("[". date('c') ."][TaobaoSyncCommand][SyncCloseOrderStatus]{$taobaoShop['nick']}:{$order['taobao_order_sn']} status: {$status} SyncCloseOrderStatus ok\n");
                    } else {
                        echo("[". date('c') ."][TaobaoSyncCommand][SyncCloseOrderStatus]{$taobaoShop['nick']}:{$order['taobao_order_sn']} status: {$status}\n");
                    }
                } else {
                    echo("[". date('c') ."][TaobaoSyncCommand][SyncCloseOrderStatus]{$taobaoShop['nick']}:{$order['taobao_order_sn']} ".$response->getMsg().": ". $response->getSubMsg()." \n");
                }
            }
        }

    }
    /**
     * 逻辑仓库转换为实际仓库
     * @param string $facilityId
     */
    private function facility_convert($facilityId) {
         $facility_mapping = array (
             '12768420' =>  '12768420',    //  怀轩上海仓
             '19568548' =>  '19568548',    //  电商服务东莞仓
             '3580047'  =>  '19568548',    //  乐其东莞仓
             '19568549' =>  '19568549',    //  电商服务上海仓
             '3633071'  =>  '19568549',    //  乐其上海仓
             '22143846' =>  '19568549',    //  乐其杭州仓
             '22143847' =>  '19568549',    //  电商服务杭州仓
             '24196974' =>  '19568549',    //  贝亲青浦仓
             '42741887' =>  '42741887',    //  乐其北京仓
         );
         if (array_key_exists($facilityId, $facility_mapping)) {
             return $facility_mapping[$facilityId] ;
         } else {
             return $facilityId ;
         }
    }
        /**
     * NewBalance 库存同步
     */
     public function actionSyncNewBalanceItemStock($appkey = null) {
     	$start = time();
     	  // 启用库存同步的店铺列表
        $include_list = array(
//              '85b1cf4b507b497e844c639733788480',          // 安满
//              '573d454e82ff408297d56fbe1145cfb9',          //金宝贝
        );
        foreach($this->getTaobaoShopList() as $taobaoShop){
            if(!in_array($taobaoShop['application_key'], $include_list)) {
	            continue;
            }

            if($appkey!==null&&$appkey!=$taobaoShop['application_key']) {
	            continue;
            }
            
            $db=Yii::app()->getDb();
			$db->setActive(true);
	     	$sql = "select * from ecshop.ecs_taobao_goods where party_id = '{$taobaoShop['party_id']}' and status = 'OK' and approve_status = 'onsale'";
	     	$goods_list = $db->createCommand($sql)->queryAll();
	     	$goods_time = time();
	     	echo date('c')." ecs_taobao_goods total_time:" . ($goods_time-$start) . " total_num:".count($goods_list)."\n";
	     	if (!empty($goods_list)) {
	     		$actual_inventory_list = array();
	     		//查询最后一次库存实绩
	     		$time = $db->createCommand("select created_stamp from ecshop.ecs_actual_inventory order by id desc limit 1")->queryScalar();
	     		if ($time) {
	     			$sql = "
		     			select ai.barcode, ai.stock_quantity as number, if(g.goods_id is null, gs.barcode , g.goods_id) as goods_id, 
						    if (g.goods_id is null, gs.style_id, 0) as style_id, 
						    concat( if(g.goods_id is null, gs.barcode , g.goods_id) , '#', if (g.goods_id is null, gs.style_id, 0)) as id
						from ecshop.ecs_actual_inventory ai
						left join ecshop.ecs_goods g on g.barcode = ai.barcode
						left join ecshop.ecs_goods_style gs on CONVERT(ai.barcode USING utf8) = gs.barcode and gs.is_delete=0
						where ai.status = 'INV_STTS_AVAILABLE' and created_stamp >= '".date('Y-m-d', $time)."'
		     		";
		     		$actual_inventory_list = Helper_Array::toHashmap((array)$db->createCommand($sql)->queryAll(), 'id');
	     		} else {
	     			$time = strtotime("-2 day");
	     		}
	     		$actual_time = time();
	     		echo date('c')." ecs_actual_inventory total_time:".($actual_time-$goods_time). " total_num:".count($actual_inventory_list) ."\n";
	     		//计算当天 -gt 减 -c 增 -sale 减 -t 不变 库存数量
	     		$sql = "
		     		select sum(case i.indicate_type when 'INVENTORY_IN' then  ad.goods_number
		     			when 'INVENTORY_RETURN' then 0
						else -ad.goods_number end) as number, id.goods_id, 
						id.style_id, CONCAt( id.goods_id, '#', id.style_id) as id
					from ecshop.ecs_actual a
					left join ecshop.ecs_indicate i on a.indicate_id = i.indicate_id
					left join ecshop.ecs_indicate_detail id on id.indicate_id = i.indicate_id
					left join ecshop.ecs_actual_detail ad on ad.actual_id = a.actual_id and ad.indicate_id = i.indicate_id and ad.indicate_detail_id = id.indicate_detail_id
					where i.party_id = '{$taobaoShop['party_id']}' and a.last_update_stamp > '".date('Y-m-d', $time)."'
						and ad.goods_type = '良品'
					group by id.goods_id, id.style_id
				";
				$product_list1 = $db->createCommand($sql)->queryAll();
				$product_list = Helper_Array::toHashmap((array)$product_list1, 'id');
				$product_time = time();
				echo date('c') . " actual total_time:".($product_time-$actual_time) . " total_num:".count($product_list)."\n";
				$sql_s = "
					select og.goods_id, og.style_id, sum(og.goods_number) as number, concat(og.goods_id, '#', og.style_id) as id
					from ecshop.ecs_order_info o
					left join ecshop.ecs_order_goods og on o.order_id = og.order_id
					where o.party_id = '{$taobaoShop['party_id']}' and o.order_type_id = 'SALE'  and o.shipping_status = '0'
					    and not exists (select 1 from ecshop.ecs_actual a 
						 		left join ecshop.ecs_indicate i on a.indicate_id = i.indicate_id 
						 		where i.order_id = o.order_id limit 1)
						and o.order_time > '".date('Y-m-d', $time)."'
					 group by og.goods_id, og.style_id
				";
				$product_list_sale1 = $db->createCommand($sql_s)->queryAll();
				$product_list_sale = Helper_Array::toHashmap((array)$product_list_sale1, 'id');
				$sale_time = time();
				echo date('c') . " order_goods total_time:".($sale_time-$product_time). " total_num:".count($product_list_sale) ." \n";
	     		// 计算库存 分套餐 与商品不同计算

	     		foreach ($goods_list as $key=>$item) {
	     			$product_id = $item['goods_id']."#".$item['style_id'];
	     			if (strpos($item['outer_id'], 'TC-') !== false) {
	     				//套餐
	     				$TC_stock = $this->getGroupGoodsStock($item['outer_id'], 
	     					$actual_inventory_list, $product_list, $product_list_sale);
	     				$goods_list[$key] = array_merge($goods_list[$key], array("available_to_reserved" => $TC_stock)); 
	     			} else {
	     				$actual_num = isset($actual_inventory_list[$product_id]["number"]) ? $actual_inventory_list[$product_id]["number"] : 0;
	     				$product_num = isset($product_list[$product_id]["number"]) ? $product_list[$product_id]["number"] : 0;
	     				$sale_num = isset($product_list_sale[$product_id]["number"]) ? $product_list_sale[$product_id]["number"] : 0;
	     				$goods_list[$key] = array_merge($item, array("available_to_reserved"=>($actual_num + $product_num - $sale_num)));
	     			}
	     		}
	     		$html = "";
	     		foreach ($goods_list as $item) {
	     			if ($item['available_to_reserved'] < $item['reserve_quantity']) {
	     				$html .= "<tr>
                                    <td>". $item['title'] ."</td>
                                    <td>". $item['outer_id'] ."</td>
                                    <td>". $item['quantity'] ."</td>
                                    <td>". $item['available_to_reserved'] ."</td>
                                    <td>". $item['reserve_quantity']  ."</td>
                                 </tr>";
	     			}
	     			if ($item['available_to_reserved'] != $item['quantity']) {
//	     				$this->updateItemStock($taobaoShop, $item);
	     			}
	     		}
	     		//检查是否需要发送邮件
	            if ($html) {
	                $html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	                    <html xmlns="http://www.w3.org/1999/xhtml">
	                    <head>
	                    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	                    <style type="text/css">
	                        table, table tr, table th, table td{
	                            border:1px solid #000;
	                            border-collapse: collapse;
	                        }
	                    </style>
	                    </head>
	                    <body style="font-size:12px;color:#000;">
	                    <div style="margin:auto;width:800px;">
	                    <h3>店铺名称：'.$taobaoShop['nick'] .' 请店长注意该商品在淘宝上库存。</h3>
	                    <table width="100%">
	                        <tr>
	                            <th>商品名称</th>
	                            <th>商家编码</th>
	                            <th>淘宝库存（仅作参考）</th>
	                            <th>可预订库存</th>  
	                            <th>预警库存</th>
	                        </tr>' . $html . "</table></div></body></html>";
	                $from = array(
	                	'email' => 'erp@leqee.com', 'name' => '商品库存同步预警列表',
	                );
	                $sql = "
	                	select user_name as name, user_email as email from ecshop.ecs_party_assign_email 
	                	where party_id = '{$taobaoShop['party_id']}' and warning_id = ''
	            	";
	                $to_email = $this->getSlave()->createCommand($sql)->queryAll();
	                $to = array_merge($to_email,array(
	                    array('name' => 'ERP', 'email' => 'erp@i9i8.com'),
	                    ));
	                $subject = "店铺名称：".$taobaoShop['nick'] ."淘宝库存同步商品预警列表";
	                $this->sendMail($subject, $from, $to, $html, $taobaoShop['nick']);
	            }
	     	}
            
        }
     }
     /**
      * 获取套餐的库存
      */
     protected function getGroupGoodsStock ($outer_id, $actual_inventory_list, $product_list, $product_list_sale) {
     	$db=Yii::app()->getDb();
		$db->setActive(true);
     	$sql = "
     		select gi.goods_id, gi.style_id, gi.goods_number
			from ecshop.distribution_group_goods g
			left join ecshop.distribution_group_goods_item gi on g.group_id = gi.group_id
			where g.status = 'OK' and g.code ='{$outer_id}'
     	";
     	$group_goods_list = $db->createCommand($sql)->queryAll();
     	$stock_quantity = 0;
     	foreach ($group_goods_list as $key=>$goods_item) {
     		$product = $goods_item['goods_id']."#".$goods_item['style_id'];
     		$actual_num = isset($actual_inventory_list[$product]["number"]) ?$actual_inventory_list[$product]["number"] : 0;
			$product_num = isset($product_list[$product]["number"]) ? $product_list[$product]["number"] : 0;
			$sale_num = isset($product_list_sale[$product]["number"]) ? $product_list_sale[$product]["number"] : 0;
			$goods_num = $actual_num + $product_num - $sale_num; 
     		if ($goods_num > 0) {
     			$item_number = floor($goods_num/$goods_item['goods_number']);
     			if (($item_number > 0 && $stock_quantity > $item_number) || $key == 0) {
     				$stock_quantity = $item_number;
     			} 
     		} else {
     			//如果存在商品没有库存，则套餐库存为0
     			$stock_quantity = 0;
     		}
     	}
     	return $stock_quantity;
     }
     	/**
	 *  同步分销店铺商品信息
	 */
	public function actionSyncFenxiaoItem ($appkey=null,$seconds=1800) {
		$start = time();
		echo "[". date('c') ."]" . " SyncFenxiaoItem start \n";
		//启用分销商品同步店铺列表
		$include_list = array(
			'f2c6d0dacf32102aa822001d0907b75a', //乐其数码专营店
			'f1cfc3f7859f47fa8e7c150c2be35bfc', //金佰利
			'dc7e418627d249ecb5295ee471a2152a', //nb品牌站
			'ee6a834daa61d3a7d8c7011e482d3de5', //金奇仕
			'85b1cf4b507b497e844c639733788480', //安满
			'7626299ed42c46b0b2ef44a68083d49a', //blackmores
			'159f1daf405445eca885a4f7811a56b8', //康贝
			'923ec15fa8b34e4a8f30e5dd8230cdef', //安怡
		);
		foreach ($this->getTaobaoShopList() as $taobaoShop) {
			if (!in_array($taobaoShop['application_key'], $include_list)) {
				continue;
			}
			// 指定店铺
			if (isset($appkey)&&$appkey!=$taobaoShop['application_key']) {
				continue;
			}
			if (empty($seconds)) {
				$start_modified = $end_modified = null;
			} else {
				$start_modified=date('Y-m-d H:i:s',time()-$seconds);
				$end_modified=date('Y-m-d H:i:s');
			}
			// 取得所有商品
			$this->getTaobaoFenxiaoItems($taobaoShop,null,$start_modified,$end_modified);
		}
		echo "[". date('c') ."]" . " SyncFenxiaoItem end total_time:".(time()-$start)."\n";
	}
	
	/**
	 * 根据店铺获取分销店铺商品信息
	 */
	protected function getTaobaoFenxiaoItems ($taobaoShop, $status = null,$start_modified = null, $end_modified = null){
		$start = time();
		echo ("[". date('c') ."]" ." name:" . $taobaoShop['nick'] ." getTaobaoPorducts start \n");
		$client = $this->getTaobaoClient($taobaoShop);
//		if ($status === null) {
//			$status = 'up'; //上架商品
//		}
		$request = array(
			'status' => $status, 
			'fields' => 'properties,pid,name,productcat_id,outer_id,status,created,modified,skus',
			'page_no' => 1,
			'page_size' => 50,
		);
		if ($start_modified != null) {
			$request['start_modified'] = $start_modified;
		}
		if ($end_modified != null) {
			$request['end_modified'] = $end_modified;
		}
		$repeat = 1;
		$count = 0;
		do {
			try {
				$request['page_no'] = $repeat++;
				$response = $client->execute('taobao.fenxiao.products.get',$request); 
				if ($response->isSuccess()) {
					if ($response->total_results > 0) {
						$count += count($response->products->fenxiao_product);
						foreach ($response->products->fenxiao_product as $item) {
							$taobao_product = array(
								'product_id'     => $item->pid,
								'name'           => $item->name,
								'outer_id'       => is_string($item->outer_id) ? $item->outer_id : '',
								'productcat_id'  => $item->productcat_id,
								'status'         => $item->status,
								'created'        => $item->created,
								'modified'       => $item->modified,
								'application_key'=> $taobaoShop['application_key'],
								'properties'     => is_string($item->properties) ? $item->properties : '', 
								'erp_status'     => 'OK',
							);
							//检查是否存在样式
							if (isset($item->skus->fenxiao_sku)) {
								$product_name = $taobao_product['name'];
								foreach ($item->skus->fenxiao_sku as $sku) {
									$taobao_product['name'] = $product_name. ' ' . $sku->name;
									$taobao_product['sku_id'] = $sku->id;
									$taobao_product['outer_id'] = is_string($sku->outer_id) ? $sku->outer_id : '';
									$taobao_product['properties'] = is_string($sku->properties) ? $sku->properties : '';
									if(isset($sku->outer_id)&&$this->checkOuterId($taobaoShop, $sku->outer_id, true)){
										$this->updateOrInsertfenxiaoItem($taobao_product, $taobaoShop);
									} else {
										echo("[". date('c') ."]  sku outer_id empty or error. product_id ". $taobao_product['product_id']. " \n");
									}
								}
							} else {
								$taobao_product['sku_id'] = '';
								if(isset($item->outer_id)&&$this->checkOuterId($taobaoShop,$item->outer_id)) {
									$this->updateOrInsertfenxiaoItem($taobao_product, $taobaoShop);
								} else {
									echo("[". date('c') ."]  outer_id empty or error. product_id ".$taobao_product['product_id']. " product_name: ".$taobao_product['name']." \n");
								}
							}
						}
					}
					if ($response->total_results <= $request['page_no']*50) {
						break;
					}
				} else {
					echo("[". date('c') ."]  message ". $response->getMsg().": ". $response->getSubMsg()." \n");
				}
				
			} catch(Exception $e) {
				echo($e->getMessage()."\n");
				break;
			}
			usleep(500000);
		}while($repeat>0 && $repeat<30);
		
		echo ("[". date('c') ."]" ." name:" . $taobaoShop['nick'] ." getTaobaoPorducts end total_time: ".(time()-$start)." total_num: ".$count."\n");
	}		
	/**
	 * 分销商品数据存储
	 */ 
	protected function updateOrInsertfenxiaoItem($taobao_product, $taobaoShop) {
		$db = Yii::app()->getDb();
		do {
			unset($taobao_product['goods_id'], $taobao_product['style_id'], $taobao_product['group_id']);
			array_map('trim', $taobao_product);
			if (is_null($taobao_product['sku_id']) || $taobao_product['sku_id']===null) {
				$taobao_product['sku_id'] = '';
			}
			// 空的产品ID
			if (empty($taobao_product['product_id'])) {
				break;  
			}
			
			// 没有商品名
			if (empty($taobao_product['name'])) {
				echo "[". date('c') ."] product_id:" . $taobao_product['product_id'] . " empty name \n";
				break;  
			}
			
			// 没有组织
			if (empty($taobao_product['application_key'])) {
				echo "[". date('c') ."] product_id:" . $taobao_product['product_id'] . " empty application_key \n";
				break;
			}
			
			// 没有商家编码
			if (empty($taobao_product['outer_id'])) {
				echo "[". date('c') ."] product_id:" . $taobao_product['product_id'] . " empty outer_id \n";
				break;
			}
			
			// 商家编码
			$outer_id = $taobao_product['outer_id'];
			if (!empty($outer_id) && strpos($outer_id, 'TC-') !== false) {
				$taobao_product['goods_id'] = $taobao_product['style_id']  = null;
				// 套餐  group_code = outer_id
				$sql = "select group_id from ecshop.distribution_group_goods 
					WHERE code = '{$outer_id}' order by valid_from desc limit 1";
				$group_id = $db->createCommand($sql)->queryScalar();
				if ($group_id) {
					$taobao_product['group_id'] = $group_id;
				} else {
					echo "[". date('c') ."] can not found group_goods by outer_id:" . $outer_id . "\n";
					break;
				}
			} else if (!empty($outer_id) && strpos($outer_id, '_') !== false) {
				$goods_info = explode("_", $outer_id);
				$taobao_product['goods_id'] = $goods_info[0];
				$taobao_product['style_id'] = $goods_info[1];
				$taobao_product['group_id'] = null;
			} else if (!empty($outer_id) && is_numeric($outer_id)) {
				// 商品  goods_id = outer_id
				$taobao_product['goods_id'] = trim($outer_id);
				$taobao_product['style_id'] = 0;
				$taobao_product['group_id'] = null;
			} else {
				echo "[". date('c') ."] empty or error outer_id: " . $outer_id . "\n";
				break;
			}
			
			// 查询是否存在
			$_exists_sql = "SELECT product_id, sku_id, modified FROM distribution_product_mapping WHERE product_id = '%s' AND sku_id= '%s' limit 1";
			$exists = $db->createCommand(sprintf($_exists_sql, $taobao_product['product_id'], $taobao_product['sku_id']))->queryRow();
			$table = $db->getSchema()->getTable('distribution_product_mapping');
			$builder = $db->getCommandBuilder();
			$act = $result = '';
			if ($exists) {
				if (strtotime($taobao_product['modified']) > strtotime($exists['modified'])) {
					$act = "update";
					// 存在则更新
					$sql = "update ecshop.distribution_product_mapping set
						name = '{$taobao_product['name']}', productcat_id = '{$taobao_product['productcat_id']}',
						outer_id = '{$taobao_product['outer_id']}', status = '{$taobao_product['status']}', 
						created = '{$taobao_product['created']}', modified = '{$taobao_product['modified']}',
						goods_id = '{$taobao_product['goods_id']}', style_id = '{$taobao_product['style_id']}',
						group_id = '{$taobao_product['group_id']}', application_key = '{$taobao_product['application_key']}',
						properties = '{$taobao_product['properties']}'
						where  product_id = '{$exists['product_id']}' AND sku_id= '{$exists['sku_id']}' limit 1;";
					$result = (boolean)$db->createCommand($sql)->execute();
				}
			} else {
				$act = "insert";
				$result = (boolean)$builder->createInsertCommand($table,$taobao_product)->execute();
			}
			
			if ($result && !empty($act)) {
				echo "[". date('c') ."] outer_id:" . $outer_id ." " .$act. " success \n";
			} elseif (!$result && !empty($act)) {
				echo "[". date('c') ."] outer_id:" . $outer_id ." " .$act. " 淘宝上有不同的商品使用可相同的外部编码\n";
			}
			
		} while (false);
	}
	/**
	 * 分销业务 经销商商品监控
	
	public function actionSyncFenxiaoTrademonitorOrder($appkey=null,$hours=3){
		$start_time = time();
		echo("[".date('c')."] SyncFenxiaoTrademonitorOrder start \n");
		$include_list = array(
//        	'f2c6d0dacf32102aa822001d0907b75a', //乐其数码专营店
			'f1cfc3f7859f47fa8e7c150c2be35bfc', //金佰利
//			'bd497325e76542d0b5b88a4e8ddacc9f', //nb品牌站
        );

        // 远程服务
		$client=Yii::app()->getComponent('romeo')->TaobaoOrderService;
        foreach ($this->getTaobaoShopList() as $taobaoShop) {
            if(!in_array($taobaoShop['application_key'],$include_list))
            continue;

            if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
            continue;

            echo("[".date('c')."] ".$taobaoShop['nick']." SyncFenxiaoTrademonitorOrder start \n");
            // 同步生成订单
            try {
                $request = array("hours" => $hours,"applicationKey" => $taobaoShop['application_key']);
                $response= $client->synchronizeFenxiaoTrademonitorOrder($request);
                print_r($response);
            } catch(Exception $e) {
                echo("|  Exception: ".$e->getMessage()."\n");
            }

            usleep(500000);
        }
		echo("[".date('c')."] SyncFenxiaoTrademonitorOrder end. total_time: " .(time()-$start_time) . " \n");
	}
	 */
	/**
	 * 分销商品库存同步
	 */
	public function actionSyncFenxiaoItemStock($appkey=null) {
		$start = time();
		echo "[". date('c') ."]" . " SyncFenxiaoItemStock start \n";
		$sql="
			select product_id, sku_id, name, outer_id, status, goods_id, style_id, group_id, properties, reserve_quantity, name
			from ecshop.distribution_product_mapping
			WHERE status = 'up' AND application_key=:application_key
			and erp_status = 'OK'
		";
		//启用分销库存同步店铺列表
		$include_list = array(
			'f2c6d0dacf32102aa822001d0907b75a', //乐其数码专营店
			'f1cfc3f7859f47fa8e7c150c2be35bfc', //金佰利
			'dc7e418627d249ecb5295ee471a2152a', //nb品牌站
//			'ee6a834daa61d3a7d8c7011e482d3de5', //金奇仕
//			'85b1cf4b507b497e844c639733788480', //安满
			'7626299ed42c46b0b2ef44a68083d49a', //blackmores
	//		'159f1daf405445eca885a4f7811a56b8', //康贝
			'923ec15fa8b34e4a8f30e5dd8230cdef', //安怡
		);
		$select=Yii::app()->getDb()->createCommand($sql); 
		foreach ($this->getTaobaoShopList() as $taobaoShop) {
			if (!in_array($taobaoShop['application_key'], $include_list)) {
				continue;
			}
			// 指定店铺
			if (isset($appkey)&&$appkey!=$taobaoShop['application_key']) {
				continue;
			}
			$sql_stock = "select is_stock_update from ecshop.taobao_fenxiao_shop_conf where taobao_fenxiao_shop_conf_id = '". $taobaoShop['taobao_shop_conf_id']."'";
			$is_stock_update = Yii::app()->getDb()->createCommand($sql_stock)->queryScalar();
			if($is_stock_update != 'OK'){
				echo "[". date('c') ."]" . " SyncFenxiaoItem 店铺:".$taobaoShop['nick']."取消同步\n";
				continue;
			}
			echo "[". date('c') ."]" . " SyncFenxiaoItem 店铺:".$taobaoShop['nick']."开始同步\n";
			
			$items=$select->bindValue(':application_key', $taobaoShop['application_key'])->queryAll();
			//获取分销同步商品中未预订单订单使用的库存数量, 默认计算7天内未预订单订单
			$startTime = date("Y-m-d H:i:s", time() - 3600 * 24 * 7);
			$endTime = date("Y-m-d H:i:s", time());
			$need_to_reserved_list = $this-> getPorductNeedToReservedList($startTime, $endTime);
			$need_to_reserved_hashamp = Helper_Array::toHashmap((array)$need_to_reserved_list, 'product_id', 'pending_count');
			//预警邮件内容
			$html_a = array();
			foreach($items as $idx => $item){
                // 先定义预添加的数组属性
                $items[$idx]['available_to_reserved'] = 0;
                $items[$idx]['stock_quantity'] = 0;
                //仓库库存是有有异常
                $need_to_reserved = 0;  //默认系统中待预定商品使用库存为0
                
                if (strpos($item['outer_id'], 'TC-') !== false) {
                	// 套餐商品同步
                	$taocanStock = $this->getTCStock($taobaoShop['party_id'], $item['outer_id'],$need_to_reserved_hashamp, $taobaoShop['taobao_shop_conf_id']) ;
    	
                    if (!empty($taocanStock)) {
                        $items[$idx]['available_to_reserved'] += max(0, ($taocanStock['availableToReserved'] - $item['reserve_quantity']));
                        $items[$idx]['stock_quantity'] += max(0, $taocanStock['quantitys']);
                        if ($items[$idx]['available_to_reserved'] > $items[$idx]['stock_quantity']) {
                            echo 'taobaoShop fenxiao '. $taobaoShop['nick'].' outerId '.$item['outer_id'].
                                ' availableToReserved '.$taocanStock['availableToReserved'] . ' stockQuantity ' . $taocanStock['stockQuantity'];
                            //如果可预定库存大于仓库实际库存，则可预定量使用实际库存
                            $items[$idx]['available_to_reserved'] = $items[$idx]['stock_quantity'];
                        } 
                        if ($items[$idx]['available_to_reserved'] <= 0) {
                        	$items[$idx]['available_to_reserved'] += max(0, $taocanStock['availableToReserved']);
                        	$html_a[$item['outer_id']] = array('name' => $item['name'], 'outer_id' =>  $item['outer_id'], 
                        		'available_to_reserved' => $taocanStock['availableToReserved'], 'reserve_quantity' => $item['reserve_quantity']);
                        }
                    }
                    
                } else {
                	$productId = ProductServices::getProductId($item['goods_id'],$item['style_id']);
                	if (empty($productId)) {
                	    echo("[" .date('c') ."] " . "row (goods_id: ".$item['goods_id'].", style_id: ".$item['style_id'].") productId not exists ! \n");	
                	} else {
                		// 取得库存
                		$facilities = FacilityServices::getFacilityByPartyId($taobaoShop['party_id']);  // 可用仓库
            			$facilities = $this->getTaobaoShopFacility ($facilities, $taobaoShop['taobao_shop_conf_id']) ;
                		
                		$inventorySummaryAssoc = InventoryServices::getInventorySummaryAssocByProduct('INV_STTS_AVAILABLE', array_keys($facilities), $productId, null);
                		if (!empty($inventorySummaryAssoc)) {
                			foreach ($inventorySummaryAssoc as $assoc) {
                				foreach ($assoc as $productStock) {
                				    $items[$idx]['available_to_reserved'] += $productStock->availableToReserved;
                                    $items[$idx]['stock_quantity'] += $productStock->stockQuantity;	
                                    //检查每个仓中商品库存数量是否异常
                                    if ($productStock->availableToReserved > $productStock->stockQuantity) {
                                        echo 'taobaoshop ' .$taobaoShop['nick'].' outer_id '. $item['outer_id'] .' facilityId ' . $productStock->facilityId
                                            .' availableToReserved ' . $productStock->availableToReserved . ' stockQuantity ' . $productStock->stockQuantity;
                                        $items[$idx]['available_to_reserved'] = $items[$idx]['stock_quantity'];
                                    }
                                }
                            }
                            //实际可预定商品库存 - ERP里已有订单且未预定成功的预存
                            $need_to_reserved = isset($need_to_reserved_hashamp[$productId])?(($need_to_reserved_hashamp[$productId] != NULL) ? (int)$need_to_reserved_hashamp[$productId] : 0) : 0; 
                            $available_to_reserved = max(0, $items[$idx]['available_to_reserved'] - $need_to_reserved - $item['reserve_quantity']);
                            //检查是否出现库存异常
                            if ($available_to_reserved <= 0) {
                            	$items[$idx]['available_to_reserved'] = max(0, $items[$idx]['available_to_reserved'] - $need_to_reserved);
                                //检查商品同步库存小于预警库存时邮件预警 发送给店长
                                $html_a[$item['outer_id']] = array('name' => $item['name'], 'outer_id' =>  $item['outer_id'], 
                        		'available_to_reserved' => $items[$idx]['available_to_reserved'], 'reserve_quantity' => $item['reserve_quantity']);
                                continue;
                            } else {
                            	 $items[$idx]['available_to_reserved'] = $available_to_reserved;
                            }
                		}
                		
                	}
                }
            }
            
            foreach ($items as $item) {
            	if (!empty($item['product_id']) && !empty($item['outer_id'])) {
	            	$this->updateFenxiaoItemStock($item, $taobaoShop);
            	}
            }
            if (!empty($html_a)) {
            	$html = "";
            	foreach ($html_a as $item) {
            		$html .= "<tr>
		                        <th>{$item['name']}</th>
		                        <th>{$item['outer_id']}</th>
		                        <th>{$item['available_to_reserved']}</th>  
		                        <th>{$item['reserve_quantity']}</th>
		                    </tr>";
            	}
	            $this->fenxiaoSendEmail($taobaoShop, $html);
            }
            
		}
		echo "[". date('c') ."]" . " SyncFenxiaoItem end total_time:".(time()-$start)."\n";
	}
	/**
	 * 同步分销库存
	 */
	public function updateFenxiaoItemStock($item, $taobaoShop) {
		$request = array(
			'quantity' => $item['available_to_reserved'],
		);
		if (empty($item['sku_id'])) {
			$request['pid'] = $item['product_id'];
			$request['outer_id'] = $item['outer_id'];
			$method = "taobao.fenxiao.product.update";
		} else {
			$request['product_id'] = $item['product_id'];
			$request['properties'] = $item['properties'];
			$request['sku_number'] = $item['outer_id'];
			$method = "taobao.fenxiao.product.sku.update";
		}
		try {
			$response=$this->getTaobaoClient($taobaoShop)->execute($method, $request); 
			if ($response->isSuccess()) {
         		echo("[" . date('c') . "] ". "succeed: (produt_id = {$item['product_id']} outer_id = {$item['outer_id']}" . ") " . $item['available_to_reserved'] . " \n");	
           		//同步成功记录同步历史
           		$db=Yii::app()->getDb();
        		$table=$db->getSchema()->getTable('ecs_taobao_fenxiao_inventory');
        		$data = array(
        			'product_id' => $item['product_id'],
        			'sku_id' => $item['sku_id'],
        			'outer_id' => $item['outer_id'],
        			'properties' => $item['properties'],
        			'party_id' => $taobaoShop['party_id'],
        			'taobao_quantity' => $item['available_to_reserved'],
        			'reserve_quantity' => $item['reserve_quantity'],
        			'created_time' => date("Y-m-d H:i:s", time()),
        		);
				$db->getCommandBuilder()->createInsertCommand($table,$data)->execute();
           	}else{
           		echo("[" . date('c') . "] " . "failed: (produt_id = {$item['product_id']} outer_id = {$item['outer_id']}" . ")  \n");
           	}  
		} catch(Exception $e) {
      			echo("[" . date('c') . "] " . "failed: (produt_id = {$item['product_id']} outer_id = {$item['outer_id']}" . ") " . $item['outer_id'] . $e->getMessage(). " \n");
      	}
	}
	public function fenxiaoSendEmail($taobaoShop, $html) {
		$html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
                <html xmlns="http://www.w3.org/1999/xhtml">
                <head>
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
                <style type="text/css">
                    table, table tr, table th, table td{
                        border:1px solid #000;
                        border-collapse: collapse;
                    }
                </style>
                </head>
                <body style="font-size:12px;color:#000;">
                <div style="margin:auto;width:800px;">
                <h3>店铺名称：'.$taobaoShop['nick'] .' 请店长注意该商品在淘宝分销店铺的库存。</h3>
                <table width="100%">
                    <tr>
                        <th>商品名称</th>
                        <th>商家编码</th>
                        <th>可预订库存</th>  
                        <th>预警库存</th>
                    </tr>' . $html . "</table></div></body></html>";
            $from = array(
            	'email' => 'erp@leqee.com', 'name' => '分销商品库存同步预警列表',
            );
            $sql = "
            	select user_name as name, user_email as email from ecshop.ecs_party_assign_email 
            	where party_id = '{$taobaoShop['party_id']}' and warning_id = '1'
        	";
            $to_email = $this->getSlave()->createCommand($sql)->queryAll();
            $to = array_merge($to_email,array(
                array('name' => 'ERP', 'email' => 'erp@leqee.com'),
                ));
            $subject = "店铺名称：".$taobaoShop['nick'] ."淘宝库存同步分销商品预警列表";
            $this->sendMail($subject, $from, $to, $html, $taobaoShop['nick']);
	}	
}
