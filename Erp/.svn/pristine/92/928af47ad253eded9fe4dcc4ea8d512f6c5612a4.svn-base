<?php
define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'includes/helper/mail.php';
require_once(ROOT_PATH . 'includes/helper/array.php');
Yii::import('application.commands.LockedCommand', true);
/*
 * Created on 2015-08-18
 *
 * All Hail Giant Salamander the Evil
 * =====================================
 * 发货同步实验室
 */
class ErpSyncTaobaoDeliveryCommand extends CConsoleCommand{

	private $slave;
	private $shipping_time_limit_days=2;
	private $shipping_time_limit_days_offset=0;
	private $shipping_pause_sync_time_table=null;

	protected function getPauseSyncTimeForShipping($shipping_id){
		if(empty($this->shipping_pause_sync_time_table)){
			$sql="SELECT shipping_id,hour,minute FROM ecshop.ecs_shipping_pause_sync_time";
			$list=$this->getSlave()->createCommand($sql)->queryAll();
			$this->shipping_pause_sync_time_table=array();
			if(!empty($list)){
				foreach ($list as $line) {
					$this->shipping_pause_sync_time_table[$line['shipping_id']]=array(
						'hour'=>$line['hour'],
						'minute'=>$line['minute'],
					);
				}
			}
		}
		if(isset($this->shipping_pause_sync_time_table[$shipping_id])){
			return $this->shipping_pause_sync_time_table[$shipping_id];
		}else{
			return null;
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
	 * 取得启用的店铺
	 * 
	 * @return array
	 */
    protected function getTaobaoShopList($group = 0)
    {
        static $list;
        if(!isset($list))
        {
            $sql="select * from taobao_shop_conf where status='OK' and shop_type = 'taobao'";
            
            if($group == 0 ){   	// 全部业务组  不做处理
            }
            elseif ($group == 1) {      //金佰利
                //echo " 金佰利\n";
            	$sql .= " and party_id in (65558) ";
            }
            elseif ($group == 2) {     //雀巢  
                //echo " 雀巢   \n";
            	$sql .= " and party_id in (65553) ";
            }
            elseif ($group == 3) {   	//惠氏 康贝 亨氏 安满 金宝贝
                //echo " 惠氏 康贝 亨氏 安满 金宝贝 \n";
            	$sql .= " and party_id in (65617,65586,65609,65569,65574) ";
            }
            elseif ($group == 4) {      //除了惠氏 康贝 亨氏 安满 金宝贝的其它组织
               //echo "除了金佰利 雀巢  惠氏 康贝 亨氏 安满 金宝贝 的其它组织\n";
            	$sql .= " and party_id not in (65558,65553,65617,65586,65609,65569,65574) ";
            }
            elseif ($group == 5) {
            	//echo "中粮奇葩的合并订单向";
            	$sql .= " and party_id in (65625) ";
            }
            else {            	// 非法参数
            	//echo 'invad $group='.$group."\n";
            	$sql .= " and  1=0 ";
            }
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
	 * 每个命令执行前执行
	 *
	 * @param string $action
	 * @param array $params
	 * @return boolean
	 */
	protected function beforeAction($action, $params, $exitCode = 0)
	{
		// echo "A".PHP_EOL;
		// print_r($action);
		// echo "B".PHP_EOL;
		// print_r($params);
		// echo "C".PHP_EOL;
		// print_r($exitCode);
		// echo "D".PHP_EOL;

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
	///          同步发货实验室          ///
	////////////////////////////////////////

	private function findPureRecords($appkey){
		$db=Yii::app()->getDb();
        $db->setActive(true);

		$sql="SELECT
			mp.mapping_id,
			mp.taobao_order_sn mp_taobao_order_sn,
			oi.order_id,
			oi.shipping_name,
			oi.shipping_time,
			oi.shipping_id,
			oi.facility_id,
			mp.type,
			s.tracking_number,
			s.carrier_id
		FROM
			ecshop.ecs_taobao_order_mapping mp
		INNER JOIN ecshop.ecs_order_info oi ON mp.order_id=oi.order_id -- mp.taobao_order_sn = oi.taobao_order_sn
		INNER JOIN romeo.order_shipment os on os.order_id=convert(oi.order_id using utf8)
		INNER JOIN romeo.shipment s on s.shipment_id=os.shipment_id
		WHERE
			mp.application_key = :key
		AND oi.order_status = 1
		AND oi.pay_status = 2
		AND oi.shipping_status = 1
		AND oi.shipping_time >(UNIX_TIMESTAMP() - 3600 * 24 * {$this->shipping_time_limit_days})
		AND oi.shipping_time <=(UNIX_TIMESTAMP() - 3600 * 24 * {$this->shipping_time_limit_days_offset})
		AND mp.shipping_status = ''
		AND s.tracking_number is not null AND s.tracking_number != ''
		limit 1000
		-- SQL From ErpSyncTaobaoDeliveryCommand::findPureRecords
		";
		
		$this->sinri_log('Find Pure Records Start');
        $order_list=Yii::app()->getDb()->createCommand($sql)->bindValue(':key',$appkey,PDO::PARAM_STR)->queryAll();
        $this->sinri_log('Find Pure Records End, totally '.count($order_list));
        /*
        if(!empty($order_list)){
        	foreach ($order_list as $key => $value) {
        		$sql="SELECT
					s.tracking_number,
					s.carrier_id
				FROM
					romeo.order_shipment os
				INNER JOIN romeo.shipment s ON os.shipment_id = s.shipment_id
				WHERE
					os.order_id = :order_id
				LIMIT 1
				-- SQL From ErpSyncTaobaoDeliveryCommand::findPureRecords_getTN
				";
				$tn=Yii::app()->getDb()->createCommand($sql)->bindValue(':order_id',$value['order_id'],PDO::PARAM_STR)->queryAll();
				if(!empty($tn)){
					$order_list[$key]['tracking_number']=$tn[0]['tracking_number'];
				}
        	}
        }
        */
        $this->sinri_log('Found Pure Records Processed');

        return $order_list;
	}

	private function findSubRecords($appkey,$distributor_id){
		$db=Yii::app()->getDb();
        $db->setActive(true);

        $order_list=array();

        $sql_1="SELECT
			oi.order_id,
			oi.taobao_order_sn,
			oi.distribution_purchase_order_sn,
			oi.shipping_name,
			oi.shipping_id,
			oi.shipping_time,
			oi.facility_id,
			s.tracking_number,
			s.carrier_id
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
		-- SQL From ErpSyncTaobaoDeliveryCommand::findSubRecords
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
        			mp.mapping_id,
					mp.taobao_order_sn mp_taobao_order_sn,
					mp.type
				FROM
					ecshop.ecs_order_info oi
				INNER JOIN ecshop.ecs_taobao_order_mapping mp ON
				IF(
					(
						oi.distribution_purchase_order_sn IS NULL
						OR oi.distribution_purchase_order_sn = ''
					),
					oi.taobao_order_sn,
					oi.distribution_purchase_order_sn
				)= mp.taobao_order_sn
				WHERE
					oi.taobao_order_sn = :pure_taobao_order_sn
				AND mp.shipping_status = ''
				-- SQL From ErpSyncTaobaoDeliveryCommand::findSubRecords_checkMP
				";

        		$list2=Yii::app()->getDb()->createCommand($sql_4)->bindValue(':pure_taobao_order_sn',$pure_taobao_order_sn,PDO::PARAM_STR)->queryAll();

        		if(!empty($list2)){
        			foreach ($list2 as $k2 => $v2) {
        				$order_list[]=array(
        					'mapping_id'=>$v2['mapping_id'],
        					'mp_taobao_order_sn'=>$v2['mp_taobao_order_sn'],
							'order_id'=>$v1['order_id'],
							'shipping_name'=>$v1['shipping_name'],
							'shipping_time'=>$v1['shipping_time'],
							'shipping_id'=>$v1['shipping_id'],
							'facility_id'=>$v1['facility_id'],
							'type'=>$v2['type'],
							'tracking_number'=>$v1['tracking_number'],
							'carrier_id'=>$v1['carrier_id'],
        				);
        			}
        		}
        	}
        }

        $this->sinri_log('Found Sub Records Mapping Done');
        /*
        if(!empty($order_list)){
        	foreach ($order_list as $key => $value) {
        		$sql="SELECT
					s.tracking_number,
					s.carrier_id
				FROM
					romeo.order_shipment os
				INNER JOIN romeo.shipment s ON os.shipment_id = s.shipment_id
				WHERE
					os.order_id = :order_id
				LIMIT 1
				-- SQL From ErpSyncTaobaoDeliveryCommand::findSubRecords_getTN
				";
				$tn=Yii::app()->getDb()->createCommand($sql)->bindValue(':order_id',$value['order_id'],PDO::PARAM_STR)->queryAll();
				if(!empty($tn)){
					$order_list[$key]['tracking_number']=$tn[0]['tracking_number'];
					$order_list[$key]['carrier_id']=$tn[0]['carrier_id'];
				}
        	}
        }
		*/
        $this->sinri_log('Found Sub Records Search tracking_number Done');

        return $order_list;
	}

	private function findMergeRecords($appkey){
		$db=Yii::app()->getDb();
        $db->setActive(true);
		
		$sql = "SELECT 
				mp.mapping_id,
				mp.taobao_order_sn mp_taobao_order_sn,
				mp.type,
				eoi1.facility_id,
				ees.shipping_name,
				-- mp.application_key,
				s.tracking_number,
				eoi1.shipping_id,
				eoi1.shipping_time,
				s.carrier_id,
				eoi1.order_id
			FROM ecshop.ecs_order_info eoi1 
			INNER JOIN ecshop.order_relation eor on eor.order_id = eoi1.order_id
			INNER JOIN ecshop.ecs_order_info eoi2 on eoi2.order_id = eor.root_order_id and eoi2.order_status = 2 and eoi2.shipping_status in (0,9,11,13) AND eoi1.party_id = eoi2.party_id
			INNER JOIN ecshop.ecs_taobao_order_mapping mp on ifnull(eoi2.distribution_purchase_order_sn,substring_index(eoi2.taobao_order_sn,'-',1)) = mp.taobao_order_sn
			INNER JOIN romeo.order_shipment os on os.order_id = convert(eoi1.order_id using utf8)
			INNER JOIN romeo.shipment s on s.shipment_id = os.shipment_id
			inner join ecshop.ecs_shipping ees on ees.shipping_id = s.shipment_type_id
			where eoi1.shipping_status = 1 and eoi1.order_status =1 AND eoi1.pay_status = 2  
				and eoi1.shipping_time >(UNIX_TIMESTAMP() - 3600 * 24 * {$this->shipping_time_limit_days})
				and eoi1.shipping_time <=(UNIX_TIMESTAMP() - 3600 * 24 * {$this->shipping_time_limit_days_offset})
				and eor.parent_order_sn = 'merge' and mp.shipping_status=''
				and (eoi1.taobao_order_sn is NULL OR eoi1.taobao_order_sn='') and s.tracking_number is not null 
				and mp.application_key=:key
			limit 500
			-- SQL From ErpSyncTaobaoDeliveryCommand::findMergeRecords
		";
        $order_list=array();
        $this->sinri_log('Find Merge Records Start');
        $order_list=Yii::app()->getDb()->createCommand($sql)->bindValue(':key',$appkey,PDO::PARAM_STR)->queryAll();
        $this->sinri_log('Find Merge Records End, totally '.count($order_list));
        $this->sinri_log('Found Merge Records Processed');

        return $order_list;
	}
	
	 /**
	 * 同步发货
	 * --route=pure; // or sub
	 * --appkey=null; // or ...
	 * --group=0; // or ...
	 * --days=1; // or ...
	 */
    public function actionSyncDeliverySendNew($route='pure',$appkey=null,$group=0,$days=2,$days_offset=0)
    {       
        // 远程服务
        ini_set('default_socket_timeout', 600);

        // 此处应有各种常量配置，比如物流代码什么的

        $db=Yii::app()->getDb();
        $db->setActive(true);

        $this->shipping_time_limit_days=$days;
        $this->shipping_time_limit_days_offset=$days_offset;

        $sought_tid_set=array();

		foreach($this->getTaobaoShopList($group) as $taobaoShop)
        {	        	
        	// Syukka Handan
            if($appkey!==null && $appkey!=$taobaoShop['application_key']){
                continue;
            }                
			if($appkey == "62f6bb9e07d14157b8fa75824400981f")
			{
//			   echo("[".date('c')."] ".$taobaoShop['nick']. " stop sync! \n");	
			   continue;	
			}  
			if(!$this->is_taobao_shop_delivery_now($taobaoShop)){
        		continue;
        	}              

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
	        if($route=='merge'){
	        	$this->sinri_log($taobaoShop['nick']." BEGIN MERGE ORDER SEARCH...");
	            $order_list_merges=$this->findMergeRecords($taobaoShop['application_key']);
	            $this->sinri_log($taobaoShop['nick']." MERGE ORDERS COUNTS ".count($order_list_merges));
	        }else{
	        	$order_list_merges=array();
	        }
            $order_list_all=array_merge($order_list_subs,$order_list_common,$order_list_merges);

            $issues=array();

            foreach ($order_list_all as $order_item) {
            	$item_info="MP_TBSN=".$order_item['mp_taobao_order_sn'].
            		" OID=".$order_item['order_id'].
            		" type=".$order_item['type'].
            		" SHIP=".$order_item['shipping_name'].
            		" TN=".$order_item['tracking_number'];
            	if(empty($order_item['mp_taobao_order_sn']) || empty($order_item['tracking_number'])){
            		$item_info.=" EMPTY SO CONTINUE";
            	}else{
            		$item_info.=" GO";
            	}
            	$this->sinri_log($item_info);

            	if(!$this->is_express_delivery_now($taobaoShop,$order_item)){
            		continue;
            	}

            	// Taobao API Delivery
            	// $this->sinri_log('~让我们伸出触手~ Begin taobao delivery');

            	$html="";
            	$done=$this->taobao_delivery($taobaoShop,$order_item,$html);
            	
            	// $this->sinri_log('~让我们收回触手~ End taobao delivery as ['.($done?'DONE':'FAIL').'] : '.(empty($html)?'Nothing Serious':$html));

            	if($done){
            		$this->sinri_log("TAOBAO_SHOP_DELIVERY_DONE! 淘宝店铺 ". $taobaoShop['nick'] ." 发货同步成功:".$html." | ".$item_info);
            	}else{
            		$this->sinri_log("TAOBAO_SHOP_DELIVERY_FAILED! 淘宝店铺 ". $taobaoShop['nick'] ." 发货同步失败:".$html." | ".$item_info);
            		$issues[]=$html;
            	}
            }

            $this->send_alert_mail($taobaoShop,$issues);

            $runtime = number_format(($this->microtime_float()-$start_time), 4).'s';

            $average_time=count($order_list_all)>0?($runtime/count($order_list_all)):'N/A';
            $success_rate=count($order_list_all)>0?((1-1.0*count($issues)/count($order_list_all))*100).'%':'N/A';

            $this->sinri_log('ONE_SHOP_END ['.$route.'] time='.$runtime.' order_count='.count($order_list_all).' average_time='.$average_time.' success_rate='.$success_rate);
        }

    }

    private function send_alert_mail($taobaoShop,$issues){
    	if(empty($issues)){
    		return;
    	}
    	try {
    		$html='<p>'.PHP_EOL.(implode(PHP_EOL.'</p>'.PHP_EOL.'<p>'.PHP_EOL, $issues)).PHP_EOL.'</p>';

            $mail=Yii::app()->getComponent('mail');
            $mail->Subject="淘宝店铺 ". $taobaoShop['nick'] ." 发货同步失败";
          	
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

    private function is_taobao_shop_delivery_now($taobaoShop){
    	// 晚上不需要同步发货
        $h=date('H');
        //2015.4.23 要求晚上8点到早上9点之间所有仓库、所有快递都不同步发货
        if(($h<9 || $h>20) 
        	&& !in_array(
        		$taobaoShop['party_id'], 
        		array( //24小时都需要同步发货的业务组
        			'65608', 
        			'65632', 
        			'65625',
        			'65638',
        		)
       		)
       	) { 
        	return false; 
        }

        return true;
    }

    private function is_express_delivery_now($taobaoShop,$order_item){
    	static $pauseFacility = array(
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


    	if($taobaoShop['application_key'] != "f2c6d0dacf32102aa822001d0907b75a") {
			$shipping_name = $order_item['shipping_name'];
            $shipping_time = $order_item['shipping_time'];
            $current_time = date("Y-m-d H:m:s",time());
            //$tid = $order['type']=='fenxiao'?$order['distribution_purchase_order_sn']:$order['mp_taobao_order_sn'];
            $shipping_pause_time=$this->getPauseSyncTimeForShipping($order_item['shipping_id']);
            if(!empty($shipping_pause_time)){
            	$hour = $shipping_pause_time['hour'];
            	$minute = $shipping_pause_time['minute'];
        	}else{
        		$hour=null;
        		$minute=null;
        	}
            $facility_id = $order_item['facility_id'];
            
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
					// print_r("订单{$tid}发货时间{$shipping_time}超过当日截止时间{$pause_time}将于{$reopen_time}恢复同步物流信息");
					return false;
				}
			}

    	}

    	return true;
    }

    private function taobao_delivery($taobaoShop,$order_item,&$html){
	    $is_ok=false;

    	static $codeMap=array
        (
        'E邮宝快递' => 'EMS',
        'EMS快递' => 'EMS',
        '顺丰快递' => 'SF',
        '顺丰（陆运）'=>'SF',
		'EMS经济快递'=>'EYB',
        '万象物流' => 'DISTRIBUTOR_665651',
		'万象物流1072430' => 'DISTRIBUTOR_1072430',
		'万象物流1072663' => 'DISTRIBUTOR_1072663',
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
        '金佰利顺丰' => 'SF',
        ); 

        $facility_id = $this->facility_convert($order_item['facility_id']);

		$request = array(
            // 分销订单, 用分销采购订单号发货
            'applicationKey' =>$taobaoShop['application_key'],
            'tid'=>$order_item['mp_taobao_order_sn'],
            'sub_tid'=>'',
            'is_split'=>0,
            'company_code'=>isset($codeMap[$order_item['shipping_name']])?$codeMap[$order_item['shipping_name']]:'OTHER',
            'out_sid'=>$order_item['tracking_number'],
            'username'=>JSTUsername,'password'=>md5(JSTPassword),
        );

		// 更新发货状态
        $sql2="UPDATE ecs_taobao_order_mapping set shipping_status='WAIT_BUYER_CONFIRM_GOODS' where mapping_id = :id";
        // 淘宝上找不到的订单
        $sql3="UPDATE ecs_taobao_order_mapping set shipping_status='TRADE_FINISHED' where mapping_id = :id";
        // 淘宝端已发货
        $sql4="UPDATE ecs_taobao_order_mapping set shipping_status='TAOBAO_DELIVERED' where mapping_id = :id";

        // All Hail Sinri Edogawa! New generation is coming! Using ecshop.ecs_order_mapping.
        // For the beginning, we have to search out the EOM record
        $sql_eom="SELECT mapping_id 
        	FROM ecs_order_mapping 
        	WHERE outer_order_sn='{$order_item['mp_taobao_order_sn']}' 
        	AND platform = '{$order_item['type']}'
        	LIMIT 1
        ";
        $eom_mapping_id=Yii::app()->getDb()->createCommand($sql_eom)->queryAll();
        if(!empty($eom_mapping_id) && isset($eom_mapping_id[0]['mapping_id'])){
        	$eom_mapping_id=$eom_mapping_id[0]['mapping_id'];
        }else{
        	$eom_mapping_id=-1;
        }

        // 更新发货状态
        $sql2_new="UPDATE ecs_order_mapping set shipping_status='WAIT_BUYER_CONFIRM_GOODS' where mapping_id = :id";
        // 淘宝上找不到的订单
        $sql3_new="UPDATE ecs_order_mapping set shipping_status='TRADE_FINISHED' where mapping_id = :id";
        // 淘宝端已发货
        $sql4_new="UPDATE ecs_order_mapping set shipping_status='TAOBAO_DELIVERED' where mapping_id = :id";

        // 请求淘宝发货
        try
        {
        	// 远程服务
        	ini_set('default_socket_timeout', 600);
	    	$client=Yii::app()->getComponent('syncJushita')->SyncTaobaoService;

            $response = null ;

            $html=" 淘宝订单号： " . $order_item['mp_taobao_order_sn'] . " 发货仓库： ".$order_item['facility_id']
                    	. " 快递方式： " . $order_item['shipping_name'] . " 快递单号： " . $order_item['tracking_number'] ;
            
            $response = $client->SyncTaobaoOrderDeliverySend($request)->return;
            if(isset($response->shipping) && isset($response->shipping->isSuccess) && $response->shipping->isSuccess)
            {
                $update=Yii::app()->getDb()->createCommand($sql2)->bindValue(':id',$order_item['mapping_id'])->execute();
                if(!empty($update)){
                	$is_ok=true;
                	$html.="[OK]Commonly Success.";
                }else{
                	$html.="[KO]Commonly Success but db update failed.";
                }
                $update_new=Yii::app()->getDb()->createCommand($sql2_new)->bindValue(':id',$eom_mapping_id)->execute();
				if(!empty($update_new)){
                	// $is_ok=true;
                	$html.="[NEW_OK]Commonly Success.";
                }else{
                	$html.="[NEW_KO]Commonly Success but db update failed.";
                }
            }
            // 已经手动发货了
            else if(isset($response->subCode) && $response->subCode=='isv.logistics-offline-service-error:B04')
            {
                $update=Yii::app()->getDb()->createCommand($sql2)->bindValue(':id',$order_item['mapping_id'])->execute();
                if(!empty($update)){
                	$is_ok=true;
                	$html.="[OK]B04, has been deliveried manually.";
                }else{
                	$html.="[KO]B04, has been deliveried manually but db update failed.";
                }
                $update_new=Yii::app()->getDb()->createCommand($sql2_new)->bindValue(':id',$eom_mapping_id)->execute();
                if(!empty($update_new)){
                	// $is_ok=true;
                	$html.="[NEW_OK]B04, has been deliveried manually.";
                }else{
                	$html.="[NEW_KO]B04, has been deliveried manually but db update failed.";
                }
            }
            // 淘宝上面已经不存在的订单
            else if(isset($response->subCode) && $response->subCode=='isv.logistics-offline-service-error:B01')
            {
            	$update=Yii::app()->getDb()->createCommand($sql3)->bindValue(':id',$order_item['mapping_id'])->execute();
            	if(!empty($update)){
                	$is_ok=true;
                	$html.="[OK]B01, order not existed on Taobao.";
                }else{
					$html.="[KO]B01, order not existed on Taobao but db update failed.";
                }
                $update_new=Yii::app()->getDb()->createCommand($sql3_new)->bindValue(':id',$eom_mapping_id)->execute();
                if(!empty($update_new)){
                	// $is_ok=true;
                	$html.="[NEW_OK]B01, order not existed on Taobao.";
                }else{
					$html.="[NEW_KO]B01, order not existed on Taobao but db update failed.";
                }
            }
            //运单号不符合规则或已经被使用
            else if (isset($response->subCode) && $response->subCode == 'isv.logistics-offline-service-error:B60') {
        		require_once(ROOT_PATH . 'admin/ajax.php');
        		$result = ajax_check_tracking_number(array("carrier_id"=>$order_item['carrier_id'],"tracking_number"=>$order_item['tracking_number']));
        		if($result){
        			//符合规则说明运营同学已经维护过，无需再同步
        			$update=Yii::app()->getDb()->createCommand($sql3)->bindValue(':id',$order_item['mapping_id'])->execute();
        			if(!empty($update)){
                		$is_ok=true;
                		$html.="[OK]B60 but has maintained manually.";
                	}else{
                		$html.="[KO]B60 but has maintained manually but db update failed.";
                	}
                	$update_new=Yii::app()->getDb()->createCommand($sql3_new)->bindValue(':id',$eom_mapping_id)->execute();
                	if(!empty($update_new)){
                		// $is_ok=true;
                		$html.="[NEW_OK]B60 but has maintained manually.";
                	}else{
                		$html.="[NEW_KO]B60 but has maintained manually but db update failed.";
                	}
        		}else{
        		 	$sql = "select count(1) from ecshop.thermal_express_mailnos where tracking_number='{$order['tracking_number']}' and status != 'N' and shipping_id = '{$order_item['shipping_id']}'";
            		$is_thermal = Yii::app()->getDb()->createCommand($sql)->queryScalar();
            		if($is_thermal == 0){
            			$html .= "[FAIL]运单号不符合规则,同步发货失败\n";
            		}else{
            			//热敏单号淘宝部分不支持，或已被维护，无需再同步
            			$update=Yii::app()->getDb()->createCommand($sql3)->bindValue(':id',$order_item['mapping_id'])->execute();
            			if(!empty($update)){
                			$is_ok=true;
                			$html.="[OK]B60 Some Thermal Express Mailno not supported by Taobao, Daizyoubu";
                		}else{
                			$html.="[KO]B60 Some Thermal Express Mailno not supported by Taobao, Daizyoubu but db update failed";
                		}
                		$update_new=Yii::app()->getDb()->createCommand($sql3_new)->bindValue(':id',$eom_mapping_id)->execute();
                		if(!empty($update_new)){
                			// $is_ok=true;
                			$html.="[NEW_OK]B60 Some Thermal Express Mailno not supported by Taobao, Daizyoubu";
                		}else{
                			$html.="[NEW_KO]B60 Some Thermal Express Mailno not supported by Taobao, Daizyoubu but db update failed";
                		}
            		}
        		 }
            }
            else if(isset($response->subCode) && in_array($response->subCode,array(
            	'isv.logistics-offline-service-error:ORDER_NOT_FOUND_ERROR',
				'isp.top-remote-connection-timeout',
				'isv.logistics-offline-service-error:B150',
				'isp.top-remote-service-unavailable'
			))){
            	//不报错
            	//isv.logistics-offline-service-error:ORDER_NOT_FOUND_ERROR -->淘宝订单号是人为错误录单，淘宝端不存在
            	//isp.top-remote-connection-timeout  -->连接超时
            	//isv.logistics-offline-service-error:B150  -->发货异常，请稍等后重试
            	//isp.top-remote-service-unavailable  -->调用后端服务***抛异常，服务不可用
            	//echo("|  - has error: ".$response->subCode.", ".$response->msg.", 订单号：".$request['tid'] .", 面单号:".$request['out_sid']."\n");
            	$is_ok=true;
            	$html.="[OK]Code as ".$response->subCode;
			}
			else if(isset($response->subCode) && in_array($response->subCode,array(
            	'isv.logistics-offline-service-error:AT0011',
            	'isv.logistics-offline-service-error:AT0112',
            	'CD07',
            	'CD06',
            ))){	
            	//isv.logistics-offline-service-error:AT0011	物流订单状态不为新建状态,无需发货处理
				//isv.logistics-offline-service-error:AT0112	物流的订单状态为关闭状态,无需发货
				// "sub_code":"CD06","sub_msg":"推荐物流的订单状态为关闭状态,无需发货处理"
				// "sub_code":"CD07","sub_msg":"物流订单状态不为新建状态,无需发货处理"
            	$update=Yii::app()->getDb()->createCommand($sql4)->bindValue(':id',$order_item['mapping_id'])->execute();
            	if(!empty($update)){
                	$is_ok=true;
                	$html.="[OK]AT(Order Express Status Not Prepared) code as ".$response->subCode;
                }else{
                	$html.="[KO]AT(Order Express Status Not Prepared) code as ".$response->subCode." but db update failed";
                }
                $update_new=Yii::app()->getDb()->createCommand($sql4_new)->bindValue(':id',$eom_mapping_id)->execute();
                if(!empty($update_new)){
                	// $is_ok=true;
                	$html.="[NEW_OK]AT(Order Express Status Not Prepared) code as ".$response->subCode;
                }else{
                	$html.="[NEW_KO]AT(Order Express Status Not Prepared) code as ".$response->subCode." but db update failed";
                }
			}
			//检查用户是否登录，或session过期
			else if(isset($response->subCode) && $response->subCode=='isv.logistics-offline-service-error:P03'){
				//移至订单同步逻辑中
				$html.="[FAIL]PO3 Session Error";
			}
            // 其他错误
            else {
            	echo "[FAIL]";
            	if(isset($response->subCode)){
            		$html.="code as ".$response->subCode."; ";
            	}
            	if(isset($response->msg)){
            		$html.="msg as ".$response->msg."; ";
            	}
            	if(isset($response->subMsg)){
            		$html.="sub_msg as ".$response->subMsg."; ";
            	}
            	echo PHP_EOL;
			}


    //         else if (isset($response->subCode) && isset($response->msg))
    //         {  
				// //echo("|  - has error: ".$response->subCode.", ".$response->msg.", 订单号：".$request['tid'] .", 子订单号列表：".$request['sub_tid'].", 面单号:".$request['out_sid']."\n");
				// $html.="[FAIL]code as ".$response->subCode."; msg as ".$response->msg;
    //         } else {
				// // echo("|  - has error: \n");
    //         	$html.="[FAIL]Unknown Error";
    //         }
        }
        catch (Exception $e)
        {
//	                    echo("|  - has exception: ". $e->getMessage() . "\n");
        	$html.="[EXCEPTION]".$e->getMessage();
        }

        usleep(100000);
	            
	    return $is_ok;
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
