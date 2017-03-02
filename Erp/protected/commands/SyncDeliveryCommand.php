<?php
define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'includes/helper/mail.php';
require_once(ROOT_PATH . 'includes/helper/array.php');
Yii::import('application.commands.LockedCommand', true);

/*
 * Catholic Synchronizing Delivery Command
 * =======================================
 * All Hail Sinri Edogawa, Giant Salamander the Devil!
 * How vainity the life of human being is.
 */
class SyncDeliveryCommand extends CConsoleCommand{

	private $slave;
	private $shipping_time_limit_days=2;
	private $shipping_time_limit_days_offset=0;	
	private $party_limit=0;
	private $party_limit_inv=0;
	private $appkey_limit=0;
	private $shipping_pause_sync_time_table=null;

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
		$lockName="commands.".$this->getName().".".$action.$params[0].$params[1].$params[2];
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
		$lockName="commands.".$this->getName().".".$action.$params[0].$params[1].$params[2];
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

	private function sinri_log($str){
    	echo("[".date('c')."] ".$str.PHP_EOL);
    }

    private function microtime_float()
	{
	    list($usec, $sec) = explode(" ", microtime());
	    return ((float)$usec + (float)$sec);
	}

	private function formalizePartyLimit($party){
		$this->party_limit=0;
		$this->party_limit_inv=0;

		if($party=='A'){
			$this->party_limit='65558';//金佰利
		}
		elseif($party=='B'){
			$this->party_limit='65553';//雀巢
		}
		elseif($party=='C'){
			// $this->party_limit='65617,65586,65609,65569,65574';//惠氏 康贝 亨氏 安满 金宝贝
            $this->party_limit = "65586,65625,65608,65632";//康贝、中粮、百事、桂格
		}
		elseif($party=='D'){
			// $this->party_limit_inv='65558,65553,65617,65586,65609,65569,65574';//Not 金佰利 雀巢 惠氏 康贝 亨氏 安满 金宝贝
			$this->party_limit_inv='65558,65553,65586,65625,65608,65632,65638';//除了金佰利 雀巢  康贝  中粮  百事  桂格 乐其跨境的其它组织
		}
		elseif($party=='E'){
			$this->party_limit='65625';//中粮
		}
		elseif($party=='F'){
			$this->party_limit='65638';//乐其跨境
		}
		else{
			$this->party_limit=$party;//Might be '0','1,2',...
		}
	}

	public function actionTestTaobaoSplitChecker($tid=0,$order_id=0,$p=0){
		//Check if sub order (not pull goods to send)
        $sql="SELECT
				oid
			FROM
				ecshop.sync_taobao_order_goods
			WHERE
				tid = :tid;
		";
		$stog_oids=Yii::app()->getDb()->createCommand($sql)->bindValue(':tid',$tid,PDO::PARAM_STR)->queryColumn();
		
		echo "stog_oids:".PHP_EOL;
		print_r($stog_oids);
		echo PHP_EOL;

		$sql="SELECT
				out_order_goods_id
			FROM
				ecshop.ecs_order_goods
			WHERE
				order_id = :order_id;
		";
		$eog_oids=Yii::app()->getDb()->createCommand($sql)->bindValue(':order_id',$order_id,PDO::PARAM_STR)->queryColumn();
		
		echo "eog_oids:".PHP_EOL;
		print_r($eog_oids);
		echo PHP_EOL;

		$diff=array_diff($stog_oids, $eog_oids);
		$oids_in_order=array();
		if(!empty($diff)){
			foreach ($diff as $oid) {
				if(!empty($oid)){
					$oids_in_order[$oid]=$oid;
				}
			}
		}
		if(empty($diff)){
			//not split
			$is_split=0;
			$sub_tid='';
		}else{
			$is_split=1;
			$sub_tid=implode(',', $oids_in_order);
		}
		echo "is_split=".$is_split.PHP_EOL;
		echo "sub_tid=".$sub_tid.PHP_EOL;
	}

	 /**
	 * 同步发货,访问Jushita中的同步接口，对淘宝的发货信息进行同步
	 * --route=pure; // or sub
	 * --appkey=null; // or ...
	 * --group=0; // or ...
	 * --days=1; // or ...
	 */
    public function actionSyncDeliverySendNew($route='catholic',$party=0,$appkey=0,$days=2,$days_offset=0,$target_order_ids='')
    {       
        // 远程服务
        ini_set('default_socket_timeout', 600);

        $actionSyncDeliverySendNew_time_start=$this->microtime_float();

        // 此处应有各种常量配置，比如物流代码什么的

        $db=Yii::app()->getDb();
        $db->setActive(true);

        $this->shipping_time_limit_days=$days;
        $this->shipping_time_limit_days_offset=$days_offset;

        // if(!empty($party)){
        // 	$this->party_limit=$party;
        // }else{
        // 	$this->party_limit=0;
        // }

        $this->formalizePartyLimit($party);

        if(!empty($appkey)){
        	$this->appkey_limit=$appkey;
        }else{
        	$this->appkey_limit=0;
        }

        $records=array();
        if($route=='catholic'){
        	//catholic
        	$records=$this->findCatholicRecords($target_order_ids);
        }elseif($route=='merge'){
        	//merge
        	$records=$this->findMergeRecords();
        }elseif($route=='bwshop'){
        	$records=$this->findBwshopRecords();
        }

        $issues=array();

        foreach ($records as $record) {
        	if(!$this->is_shop_delivery_now($record)){
        		continue;
        	}

        	$done_tns=explode(',', $record['done_tns']);
        	$undelivery_tns=array_diff($record['TNS'],$done_tns);
        	$record['TNS']=$undelivery_tns;

        	$html='';

        	$time_start=$this->microtime_float();
        	$done=$this->catholicSyncDelivery($record,$html);
        	$time_end=$this->microtime_float();

        	$rec_info='mp_taobao_order_sn='.$record['mp_taobao_order_sn'].' platform='.$record['platform'].' tracking_number='.implode(',', $record['TNS']).' CatholicSyncDeliveryTime='.($time_end-$time_start)."s";
        	if($done===null){
        		//not supported platform
        		$this->sinri_log('[PASSOVER] '.$rec_info.' response: '.$html);
        	}elseif($done==false){
        		//failed
        		if(!empty($html)){
        			$issues[]=$html;
        		}
        		$this->sinri_log('[FAILED] '.$rec_info.' response: '.$html);
        	}else{
        		//ok
				$this->sinri_log('[DONE] '.$rec_info.' response: '.$html);
        	}
        }

        $this->send_alert_mail($issues);

        $actionSyncDeliverySendNew_time_end=$this->microtime_float();

        $this->sinri_log('ONE_COMMAND_OVER ['.$route.'] records='.count($records)." issues=".count($issues)." time=".($actionSyncDeliverySendNew_time_end-$actionSyncDeliverySendNew_time_start)."s");

    }

    ///////////////////////

    private function is_shop_delivery_now($record){
    	// 晚上不需要同步发货
        $h=date('H');
        //2015.4.23 要求晚上8点到早上9点之间所有仓库、所有快递都不同步发货
        if(($h<9 || $h>20) 
        	&& !in_array(
        		$record['party_id'], 
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

    private function is_express_delivery_now($record){
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


    	if($record['application_key'] != "f2c6d0dacf32102aa822001d0907b75a") {
			$shipping_name = $record['shipping_name'];
            $shipping_time = $record['shipping_time'];
            $current_time = date("Y-m-d H:m:s",time());
            //$tid = $order['type']=='fenxiao'?$order['distribution_purchase_order_sn']:$order['mp_taobao_order_sn'];
            $shipping_pause_time=$this->getPauseSyncTimeForShipping($record['shipping_id']);
            if(!empty($shipping_pause_time)){
            	$hour = $shipping_pause_time['hour'];
            	$minute = $shipping_pause_time['minute'];
        	}else{
        		$hour=null;
        		$minute=null;
        	}
            $facility_id = $record['facility_id'];
            
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

    ///////////////////////

    /*
    Record Fields:
	    oi.order_id,
		oi.shipping_name,
		oi.shipping_time,
		oi.shipping_id,
		oi.facility_id,
		oi.party_id,
		oi.taobao_order_sn erp_outer_sn,
		s.tracking_number,
		mp.platform,
		mp.mapping_id,
		mp.outer_order_sn mp_taobao_order_sn,
		mp.application_key
    */

	/*
	このカトリックデリバリーシステムは、以下の条件を満たした注文を探し出して、実行対象とする。
	イ、通常の出荷状態になること。
	ロ、注文時間は2015-09-15 00:00:00以後であること。前日からマッピング開始したゆえ。
	ハ、注文のsource_typeは、'taobao'と'360buy'の間に値をとること。
	*/

    private function findCatholicRecords($target_order_ids=''){
    	$time_1=$this->microtime_float();

    	$db=Yii::app()->getDb();
        $db->setActive(true);

        $sql_party_limit="";
        if(!empty($this->party_limit)){
        	$sql_party_limit.=" AND oi.party_id in ( ".$this->party_limit." ) ";
        }
        if(!empty($this->party_limit_inv)){
        	$sql_party_limit.=" AND oi.party_id not in ( ".$this->party_limit_inv." ) ";
        }
        if(!empty($this->appkey_limit)){
        	$sql_appkey_limit="AND (mp.application_key='".$this->appkey_limit."' OR mp.application_key is NULL)";
        }else{
        	$sql_appkey_limit="";
        }

        if(!empty($target_order_ids)){
        	$target_order_ids_sql=" and oi.order_id in ({$target_order_ids}) ";
        }else{
        	$target_order_ids_sql="";
        }

        $sql_1="SELECT
			oi.order_id,
			oi.shipping_name,
			oi.shipping_time,
			oi.shipping_id,
			oi.facility_id,
			oi.party_id,
			oi.taobao_order_sn erp_outer_sn,
			oi.distribution_purchase_order_sn,
			s.tracking_number,
			s.carrier_id,
			mp.platform,
			mp.mapping_id,
			mp.outer_order_sn mp_taobao_order_sn,
			mp.application_key,
			mp.shipping_status,
			mp.tracking_numbers,
            mp.update_time
		FROM
			ecshop.ecs_order_info oi USE INDEX (PRIMARY,shipping_time)
		INNER JOIN romeo.order_shipment os on os.order_id=convert(oi.order_id using utf8)
		INNER JOIN romeo.shipment s on s.shipment_id=os.shipment_id
		LEFT JOIN ecshop.ecs_order_mapping mp ON oi.taobao_order_sn = mp.outer_order_sn
		-- 20151014 At Changsha
		-- if(locate(oi.taobao_order_sn,'-'),  substr(oi.taobao_order_sn,0,locate(oi.taobao_order_sn,'-')),oi.taobao_order_sn)= mp.outer_order_sn
		WHERE
			oi.order_status = 1
		AND oi.pay_status = 2
		AND oi.shipping_status = 1
		AND oi.order_time>='2015-09-15 00:00:00' -- For New MP went alive on 20150910 afternoon
		AND oi.shipping_time >(UNIX_TIMESTAMP() - 3600 * 24 * {$this->shipping_time_limit_days})
		AND oi.shipping_time <=(UNIX_TIMESTAMP() - 3600 * 24 * {$this->shipping_time_limit_days_offset})
		AND oi.source_type in ('taobao','360buy','360buy_overseas')
		{$sql_party_limit}
		AND (mp.shipping_status = '' or mp.shipping_status is null)
		{$sql_appkey_limit}
		AND s.tracking_number is not null AND s.tracking_number != ''
		AND s.status != 'SHIPMENT_CANCELLED'
		{$target_order_ids_sql}
		LIMIT 2000
		-- SyncDeliveryCommand->findCatholicRecords 1
		";
		$sql_2="SELECT
			oi.order_id,
			oi.shipping_name,
			oi.shipping_time,
			oi.shipping_id,
			oi.facility_id,
			oi.party_id,
			oi.taobao_order_sn erp_outer_sn,
			oi.distribution_purchase_order_sn,
			s.tracking_number,
			s.carrier_id,
			mp.platform,
			mp.mapping_id,
			mp.outer_order_sn mp_taobao_order_sn,
			mp.application_key,
			mp.shipping_status,
			mp.tracking_numbers,
            mp.update_time
		FROM
			ecshop.ecs_order_info oi USE INDEX (PRIMARY,shipping_time)
		INNER JOIN romeo.order_shipment os on os.order_id=convert(oi.order_id using utf8)
		INNER JOIN romeo.shipment s on s.shipment_id=os.shipment_id
		LEFT JOIN ecshop.ecs_order_mapping mp ON oi.taobao_order_sn = mp.outer_order_sn
		-- 20151014 At Changsha
		-- if(locate(oi.taobao_order_sn,'-'),  substr(oi.taobao_order_sn,0,locate(oi.taobao_order_sn,'-')),oi.taobao_order_sn)= mp.outer_order_sn
		WHERE
			oi.order_status = 1
		AND oi.pay_status = 2
		AND oi.shipping_status = 1
		AND oi.order_time>='2015-09-15 00:00:00' -- For New MP went alive on 20150910 afternoon
		AND oi.shipping_time >(UNIX_TIMESTAMP() - 3600 * 24 * {$this->shipping_time_limit_days})
		AND oi.shipping_time <=(UNIX_TIMESTAMP() - 3600 * 24 * {$this->shipping_time_limit_days_offset})
		AND oi.source_type in ('taobao','360buy','360buy_overseas')
		{$sql_party_limit}
		AND mp.shipping_status='SELLER_CONSIGNED_PART'
		{$sql_appkey_limit}
		AND s.tracking_number is not null AND s.tracking_number != ''
		AND s.status != 'SHIPMENT_CANCELLED'
		{$target_order_ids_sql}
		LIMIT 1000
		-- SyncDeliveryCommand->findCatholicRecords 2
		";
		$sql="(
			{$sql_1}
		)union(
			{$sql_2}
		)";

        $order_list=Yii::app()->getDb()->createCommand($sql)->queryAll();

        $time_2=$this->microtime_float();
        $this->sinri_log('Find_Catholic_Records_Pure totally: '.count($order_list)." time: ".($time_2-$time_1));

        $done_list=array();

        foreach($order_list as $rec){
        	if(empty($rec['mapping_id'])){
        		//IF erp_outer_sn is X-N, process X;ELSE throw away.
    			$subtagindex=strpos($rec['erp_outer_sn'],'-');
        		$pure_taobao_order_sn='';
        		if($subtagindex!==false){
        			$pure_taobao_order_sn=substr($rec['erp_outer_sn'], 0,$subtagindex);
        		}
        		if(!empty($rec['distribution_purchase_order_sn'])){
        			$pure_taobao_order_sn=$rec['distribution_purchase_order_sn'];
        		}
        		if(!empty($pure_taobao_order_sn)){
        			if(!empty($this->appkey_limit)){
			        	$sql_appkey_limit="AND mp.application_key='".$this->appkey_limit."'";
			        }else{
			        	$sql_appkey_limit="";
			        }
        			$sql="SELECT
							mp.platform,
							mp.mapping_id,
							mp.outer_order_sn mp_taobao_order_sn,
							mp.application_key,
							mp.shipping_status,
							mp.tracking_numbers,
            				mp.update_time
						FROM
							ecshop.ecs_order_mapping mp
						WHERE
							(mp.shipping_status = '' or mp.shipping_status='SELLER_CONSIGNED_PART')
						AND mp.outer_order_sn = :pure_taobao_order_sn
						{$sql_appkey_limit}
					";

					$time_origin_1=$this->microtime_float();
					
					$mp_list=Yii::app()->getDb()->createCommand($sql)->bindValue(':pure_taobao_order_sn',$pure_taobao_order_sn,PDO::PARAM_STR)->queryAll();
					
					$time_origin_2=$this->microtime_float();
					$this->sinri_log('Find_Original_Records For '.json_encode($rec).' totally '.count($mp_list)." time: ".($time_origin_2-$time_origin_1));
					
					if(count($mp_list)>0){
						foreach ($mp_list as $mp) {
							$key=$mp['platform'].'#'.$mp['mp_taobao_order_sn'].'#'.$rec['order_id'];
							if(isset($done_list[$key])){
								$done_list[$key]['TNS'][$rec['tracking_number']]=$rec['tracking_number'];
							}else{
								$done_list[$key]=array(
									'order_id'=>$rec['order_id'],
									'shipping_name'=>$rec['shipping_name'],
									'shipping_time'=>$rec['shipping_time'],
									'shipping_id'=>$rec['shipping_id'],
									'facility_id'=>$rec['facility_id'],
									'party_id'=>$rec['party_id'],
									'erp_outer_sn'=>$rec['erp_outer_sn'],
									'carrier_id'=>$rec['carrier_id'],
									'platform'=>$mp['platform'],
									'mapping_id'=>$mp['mapping_id'],
									'mp_taobao_order_sn'=>$mp['mp_taobao_order_sn'],
									'application_key'=>$mp['application_key'],
									'shipping_status'=>$mp['shipping_status'],
									'TNS'=>array($rec['tracking_number']=>$rec['tracking_number']),
									'done_tns'=>$mp['tracking_numbers'],
									'update_time'=>$mp['update_time'],
								);
							}
						}
					}
        		}
        	}else{
        		//PROCESS, GOOD.
        		$key=$rec['platform'].'#'.$rec['mp_taobao_order_sn'].'#'.$rec['order_id'];
				if(isset($done_list[$key])){
					$done_list[$key]['TNS'][$rec['tracking_number']]=$rec['tracking_number'];
				}else{
					$done_list[$key]=array(
						'order_id'=>$rec['order_id'],
						'shipping_name'=>$rec['shipping_name'],
						'shipping_time'=>$rec['shipping_time'],
						'shipping_id'=>$rec['shipping_id'],
						'facility_id'=>$rec['facility_id'],
						'party_id'=>$rec['party_id'],
						'erp_outer_sn'=>$rec['erp_outer_sn'],
						'carrier_id'=>$rec['carrier_id'],
						'platform'=>$rec['platform'],
						'mapping_id'=>$rec['mapping_id'],
						'mp_taobao_order_sn'=>$rec['mp_taobao_order_sn'],
						'application_key'=>$rec['application_key'],
						'shipping_status'=>$rec['shipping_status'],
						'TNS'=>array($rec['tracking_number']=>$rec['tracking_number']),
						'done_tns'=>$rec['tracking_numbers'],
						'update_time'=>$rec['update_time'],
					);
				}
        	}
        }

        $time_3=$this->microtime_float();
        if(count($order_list)>0){
        	$ave=number_format(($time_3-$time_2)/count($order_list), 2, '.', '');
        }else{
        	$ave='NA';
        }
        $stat=array(
        	'order_list_count'=>count($order_list),
        	'done_list_count'=>count($done_list),
        	'full_time'=>($time_3-$time_1),
        	'pure_time'=>($time_2-$time_1),
        	'process_one_ave'=>$ave,
        	);
        $this->sinri_log('Find_Catholic_Records stat: '.json_encode($stat));

        return $done_list;
    }

    private function findMergeRecords(){
    	//然而并没有什么中粮的合并订单的事情。
    	$time_1=$this->microtime_float();

    	$db=Yii::app()->getDb();
        $db->setActive(true);

        $sql_party_limit="";
        if(!empty($this->party_limit)){
        	$sql_party_limit.=" AND eoi1.party_id in ( ".$this->party_limit." ) ";
        }
        if(!empty($this->party_limit_inv)){
        	$sql_party_limit.=" AND eoi1.party_id not in ( ".$this->party_limit_inv." ) ";
        }

        if(!empty($this->appkey_limit)){
        	$sql_appkey_limit="AND (mp.application_key='".$this->appkey_limit."' OR mp.application_key is NULL)";
        }else{
        	$sql_appkey_limit="";
        }
    	$sql="SELECT
				mp.mapping_id,
				mp.outer_order_sn mp_taobao_order_sn,
				mp.platform,
				mp.application_key,
				mp.tracking_numbers,
            	mp.update_time,
				s.tracking_number,
				s.carrier_id,
				eoi1.facility_id,
				ees.shipping_name,
				eoi1.shipping_id,
				eoi1.shipping_time,
				eoi1.order_id,
				eoi1.party_id,
				eoi1.taobao_order_sn erp_outer_sn
			FROM
				ecshop.ecs_order_info eoi1 use index (shipping_time)
			INNER JOIN ecshop.order_relation eor ON eor.order_id = eoi1.order_id
			INNER JOIN ecshop.ecs_order_info eoi2 ON eoi2.order_id = eor.root_order_id
			AND eoi2.order_status = 2
			AND eoi2.shipping_status IN (0, 9, 11, 13)
			AND eoi1.party_id = eoi2.party_id
			INNER JOIN ecshop.ecs_order_mapping mp ON ifnull(
				eoi2.distribution_purchase_order_sn,
				substring_index(eoi2.taobao_order_sn, '-', 1)
			) = mp.outer_order_sn
			INNER JOIN romeo.order_shipment os ON os.order_id = CONVERT (eoi1.order_id USING utf8)
			INNER JOIN romeo.shipment s ON s.shipment_id = os.shipment_id
			INNER JOIN ecshop.ecs_shipping ees ON ees.shipping_id = s.shipment_type_id
			WHERE
				eoi1.shipping_status = 1
			AND eoi1.order_status = 1
			AND eoi1.pay_status = 2
			{$sql_party_limit}
			AND eoi1.shipping_time > (UNIX_TIMESTAMP() - 3600 * 24 * {$this->shipping_time_limit_days})
			AND eoi1.shipping_time <= (UNIX_TIMESTAMP() - 3600 * 24 * {$this->shipping_time_limit_days_offset})
			AND eor.parent_order_sn = 'merge'
			AND (mp.shipping_status = '' or mp.shipping_status='SELLER_CONSIGNED_PART')
			{$sql_appkey_limit}
			AND (
				eoi1.taobao_order_sn IS NULL
				OR eoi1.taobao_order_sn = ''
			)
			AND s.tracking_number IS NOT NULL
			LIMIT 500 
			-- SQL From ErpSyncTaobaoDeliveryCommand::findMergeRecords
		";
		$order_list=Yii::app()->getDb()->createCommand($sql)->queryAll();

		$time_2=$this->microtime_float();
        $this->sinri_log('Find_Meger_Records_Pure totally: '.count($order_list)." time: ".($time_2-$time_1));

		$done_list=array();

		foreach ($order_list as $rec) {
			if(empty($rec['mapping_id'])){
				continue;
			}
			$key=$rec['platform'].'#'.$rec['mp_taobao_order_sn'].'#'.$rec['order_id'];
			if(isset($done_list[$key])){
				$done_list[$key]['TNS'][$rec['tracking_number']]=$rec['tracking_number'];
			}else{
				$done_list[$key]=array(
					'order_id'=>$rec['order_id'],
					'shipping_name'=>$rec['shipping_name'],
					'shipping_time'=>$rec['shipping_time'],
					'shipping_id'=>$rec['shipping_id'],
					'facility_id'=>$rec['facility_id'],
					'party_id'=>$rec['party_id'],
					'erp_outer_sn'=>$rec['erp_outer_sn'],
					'carrier_id'=>$rec['carrier_id'],
					'platform'=>$rec['platform'],
					'mapping_id'=>$rec['mapping_id'],
					'mp_taobao_order_sn'=>$rec['mp_taobao_order_sn'],
					'application_key'=>$rec['application_key'],
					'shipping_status'=>$rec['shipping_status'],
					'TNS'=>array($rec['tracking_number']=>$rec['tracking_number']),
					'done_tns'=>$rec['tracking_numbers'],
					'update_time'=>$rec['update_time'],
				);
			}
		}

		$time_3=$this->microtime_float();
        if(count($order_list)>0){
        	$ave=number_format(($time_3-$time_2)/count($order_list), 2, '.', '');
        }else{
        	$ave='NA';
        }
        $stat=array(
        	'order_list_count'=>count($order_list),
        	'done_list_count'=>count($done_list),
        	'full_time'=>($time_3-$time_1),
        	'pure_time'=>($time_2-$time_1),
        	'process_one_ave'=>$ave,
        	);
        $this->sinri_log('Find_Merge_Records stat: '.json_encode($stat));

		return $done_list;
    }

    private function findBwshopRecords(){
    	$time_1=$this->microtime_float();

    	$db=Yii::app()->getDb();
        $db->setActive(true);

    	$sql="SELECT 
				oi.order_id,
				oi.shipping_name,
				oi.shipping_time,
				oi.shipping_id,
				oi.facility_id,
				oi.party_id,
				oi.taobao_order_sn erp_outer_sn,
				boi.tracking_number,
				s.carrier_id,
				mp.platform,
				mp.mapping_id,
				mp.outer_order_sn mp_taobao_order_sn,
				mp.application_key,
				mp.shipping_status,
				mp.tracking_numbers,
            	mp.update_time
			FROM
				ecshop.bw_order_info boi 
			STRAIGHT_JOIN ecshop.ecs_order_info oi -- USE INDEX (order_info_multi_index) 
			ON oi.taobao_order_sn =  CAST(boi.outer_order_sn as char(255))
			INNER JOIN ecshop.bw_shop bs ON bs.shop_id = boi.shop_id
			INNER JOIN romeo.order_shipment os ON os.order_id = CONVERT (oi.order_id USING utf8)
			INNER JOIN romeo.shipment s ON s.shipment_id = os.shipment_id
			LEFT JOIN ecshop.ecs_order_mapping mp ON oi.taobao_order_sn = mp.outer_order_sn
			WHERE
				oi.order_status = 1
			AND oi.shipping_status in (0,1)
			AND oi.pay_status = 2
			AND oi.order_time >= '2015-09-15 00:00:00' -- For New MP went alive on 20150910 afternoon
			AND (
				oi.shipping_time=0 or (
					oi.shipping_time > (UNIX_TIMESTAMP() - 3600 * 24 * {$this->shipping_time_limit_days})
					AND oi.shipping_time <= (UNIX_TIMESTAMP() - 3600 * 24 * {$this->shipping_time_limit_days_offset})
				)
			)
			AND oi.order_type_id='SALE'
			AND oi.source_type IN ('taobao', '360buy','360buy_overseas')
			AND oi.party_id = 65638
			AND bs.ecs_distributor_id = oi.distributor_id
			AND (
				mp.shipping_status = ''
				OR mp.shipping_status = 'SELLER_CONSIGNED_PART'
				OR mp.shipping_status IS NULL
			)
			AND boi.tracking_number IS NOT NULL
			AND boi.tracking_number != ''
			AND (
				boi.shipping_status = '24'
				or 
				(bs.credit_shipping ='Y' and boi.shipping_status = '22') 
			)
			AND s. STATUS != 'SHIPMENT_CANCELLED'
			LIMIT 2000 -- SyncDeliveryCommand->findCatholicRecords
		";
		$order_list=Yii::app()->getDb()->createCommand($sql)->queryAll();

		$time_2=$this->microtime_float();

		$done_list=array();

		foreach ($order_list as $rec) {
			if(empty($rec['mapping_id'])){
				continue;
			}
			$key=$rec['platform'].'#'.$rec['mp_taobao_order_sn'].'#'.$rec['order_id'];
			if(isset($done_list[$key])){
				$done_list[$key]['TNS'][$rec['tracking_number']]=$rec['tracking_number'];
			}else{
				$done_list[$key]=array(
					'order_id'=>$rec['order_id'],
					'shipping_name'=>$rec['shipping_name'],
					'shipping_time'=>$rec['shipping_time'],
					'shipping_id'=>$rec['shipping_id'],
					'facility_id'=>$rec['facility_id'],
					'party_id'=>$rec['party_id'],
					'erp_outer_sn'=>$rec['erp_outer_sn'],
					'carrier_id'=>$rec['carrier_id'],
					'platform'=>$rec['platform'],
					'mapping_id'=>$rec['mapping_id'],
					'mp_taobao_order_sn'=>$rec['mp_taobao_order_sn'],
					'application_key'=>$rec['application_key'],
					'shipping_status'=>$rec['shipping_status'],
					'TNS'=>array($rec['tracking_number']=>$rec['tracking_number']),
					'done_tns'=>$rec['tracking_numbers'],
					'update_time'=>$rec['update_time'],
				);
			}
		}

		$time_3=$this->microtime_float();

		if(count($order_list)>0){
        	$ave=number_format(($time_3-$time_2)/count($order_list), 2, '.', '');
        }else{
        	$ave='NA';
        }
        $stat=array(
        	'order_list_count'=>count($order_list),
        	'done_list_count'=>count($done_list),
        	'full_time'=>($time_3-$time_1),
        	'pure_time'=>($time_2-$time_1),
        	'process_one_ave'=>$ave,
        	);
        $this->sinri_log('Find_Bwshop_Records stat: '.json_encode($stat));

		return $done_list;
    }

    /////////////////////////////

    private function catholicSyncDelivery($record,&$html){
    	$platform=$record['platform'];
    	/*
    	京东 360buy, 360buy_overseas
		淘猫 fixed,auction,guarantee_trade,auto_delivery,independent_simple_trade,independent_shop_trade,ec,cod,fenxiao,game_equipment,shopex_trade,netcn_trade,external_trade,o2o_offlinetrade,step,nopaid,pre_auth_type
		淘宝录单 taobao
		蜜芽 miya
		顺快 sfhk
		苏宁 suning
		天国 tmall_i18n
		一号 yhd
    	*/
    	if(in_array($platform, array('taobao','fixed','auction','guarantee_trade','auto_delivery','independent_simple_trade','independent_shop_trade','ec','cod','fenxiao','game_equipment','shopex_trade','netcn_trade','external_trade','o2o_offlinetrade','step','nopaid','pre_auth_type','tmall_i18n'))){
    		//Alas, the Heavenly Cat, thy treasures are sought and token by others
    		$ok=false;
    		$html="";
    		if(!empty($record['TNS'])){
    			$ok=true;

    			$stog_oids=$this->getTaobaoOids($record['mp_taobao_order_sn']);
    			$eog_oids=$this->getOrderOids($record['order_id']);
    			$diff=array_diff($stog_oids, $eog_oids);
    			if(empty($diff) && count($record['TNS'])==1){
					$record['is_split']=0;
					$record['sub_tid']='';
					foreach ($record['TNS'] as $key => $value) {
	    				$record['tracking_number']=$value;
	    				$ok_item=$this->taobaoSyncDelivery($record,$html_item);
	    				$ok=$ok && $ok_item;
	    				$html.=$html_item;
	    			}
				}else{
					$record['is_split']=1;
					$tns=array_values($record['TNS']);
					for ($tn_i=0; $tn_i < count($tns); $tn_i++) { 
						$record['tracking_number']=$tns[$tn_i];
						if($tn_i<count($eog_oids)){
							if($tn_i<count($tns)-1){
								$record['sub_tid']=$eog_oids[$tn_i];
							}else{
								$ttt=array();
								for ($eog_i=$tn_i; $eog_i < count($eog_oids); $eog_i++) { 
									if(!empty($eog_oids[$eog_i])){
										$ttt[]=$eog_oids[$eog_i];
									}
								}
								if(!empty($ttt)){
									$record['sub_tid']=implode(',', $ttt);
									$record['is_split']=1;
								}else{
									$record['is_split']=0;
									$record['sub_tid']='';
								}
							}
						}else{
							break;
						}
						$ok_item=$this->taobaoSyncDelivery($record,$html_item);
	    				$ok=$ok && $ok_item;
	    				$html.=$html_item;
					}
				}    			
    		}

    		//here use erp_outer_sn, if it is -X, insert one into mp
    		//IF erp_outer_sn is X-N, process X;ELSE throw away.
    		$status=($ok?'TB_OK':'TB_EX');
			$subtagindex=strpos($record['erp_outer_sn'],'-');
    		if($subtagindex!==false){
    			//insert into mp
    			$sql="INSERT INTO `ecshop`.`ecs_order_mapping`
				(`mapping_id`,
				`erp_order_id`,
				`outer_order_sn`,
				`platform`,
				`status`,
				`shipping_status`,
				`application_key`,
				`is_noted`,
				`note_time`,
				`tracking_numbers`,
				`created_time`,
				`update_time`)
				VALUES
				(
					NULL,
					'{$record['order_id']}',
					'{$record['erp_outer_sn']}',
					'{$record['platform']}',
					'OK',
					'{$status}',
					'{$record['application_key']}',
					'N',
					NULL,
					'{$record['tracking_numbers']}',
					NOW(),
					NOW()
				)
				";
				$new_rec_id=Yii::app()->getDb()->createCommand($sql)->execute();
				$this->sinri_log("[insert mp-x] for [{$record['order_id']}]({$record['erp_outer_sn']})->{$$new_rec_id}");
    		}

    		return $ok;
    		// return $this->taobaoSyncDelivery($record,$html);
    	}elseif(in_array($platform, array('360buy', '360buy_overseas'))){
    		//Oh, the Milktea was drunk up, and only scraps left
    		return $this->jdSyncDelivery($record,$html);
    	}else{
    		//Guhehe, nanda korya
    		$this->sinri_log('[platform_error] Unable to process the order from this platform: '.$platform);
    		return null;
    	}
    }

    private function getTaobaoOids($tid){
    	$sql="SELECT
				oid
			FROM
				ecshop.sync_taobao_order_goods
			WHERE
				tid = :tid
		";
		$stog_oids=Yii::app()->getDb()->createCommand($sql)->bindValue(':tid',$tid,PDO::PARAM_STR)->queryColumn();
		return $stog_oids;
    }

    private function getOrderOids($order_id){
    	$sql="SELECT DISTINCT
			    og.out_order_goods_id
			FROM
			    ecshop.ecs_order_goods og
			WHERE
			    og.order_id = :order_id 
		";
		$eog_oids=Yii::app()->getDb()->createCommand($sql)->bindValue(':order_id',$order_id,PDO::PARAM_STR)->queryColumn();
		return $eog_oids;
    }

    private function taobaoSyncDelivery($record,&$html){
    	$is_ok=false;

    	static $codeMap=array(
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
			'菜鸟-快消-圆通'  => 'DISTRIBUTOR_526033' ,
			'菜鸟-快消-中通'  => 'DISTRIBUTOR_526075' ,
			'菜鸟-晟邦-快消'  => 'DISTRIBUTOR_1863862' ,
			'菜鸟-万象-重货分仓'=>   'DISTRIBUTOR_665651' ,
			'菜鸟-重货-辽宁黄马甲' =>   'DISTRIBUTOR_1732074',
			'菜鸟-重货-陕西黄马甲' =>   'DISTRIBUTOR_1732073' ,
			'菜鸟-重货-吉林黄马甲' =>   'DISTRIBUTOR_1732122' ,
			'菜鸟-重货-新疆黄马甲' =>   'DISTRIBUTOR_1732123' ,
			'菜鸟-如风达（重货）' =>   'DISTRIBUTOR_1848248',
			'菜鸟-重货-中通'  =>     'DISTRIBUTOR_526075' ,
			'菜鸟-晟邦-农业重货' =>   'DISTRIBUTOR_1863851' ,
			'菜鸟-重货-重庆黄马甲' =>  'DISTRIBUTOR_1731796' ,
			'菜鸟-韵达快运' => 'YUNDA-001',
        ); 

        $facility_id = $this->facility_convert($record['facility_id']);

        $tracking_number=$record['tracking_number'];
        if(empty($tracking_number)){
        	return false;
        }

        $is_split=empty($record['is_split'])?0:1;
		$sub_tid=empty($record['sub_tid'])?'':$record['sub_tid'];

		$request = array(
            // 分销订单, 用分销采购订单号发货
            'applicationKey' =>$record['application_key'],
            'tid'=>$record['mp_taobao_order_sn'],
            'sub_tid'=>$sub_tid,
            'is_split'=>$is_split,
            'company_code'=>isset($codeMap[$record['shipping_name']])?$codeMap[$record['shipping_name']]:'OTHER',
            'out_sid'=>$tracking_number,
            'username'=>JSTUsername,'password'=>md5(JSTPassword),
        );

		// 更新发货状态
        // All Hail Sinri Edogawa! New generation is coming! Using ecshop.ecs_order_mapping.
        // For the beginning, we have to search out the EOM record
        $eom_mapping_id=$record['mapping_id'];

        // 更新发货状态
        if($is_split==0){
        	$sql2_new="UPDATE ecshop.ecs_order_mapping 
        		set shipping_status='WAIT_BUYER_CONFIRM_GOODS' ,
        			tracking_numbers = IF(tracking_numbers = '', '{$tracking_number}', CONCAT(tracking_numbers, ',', '{$tracking_number}')),
				    update_time = NOW()
        		where mapping_id = :id";
    	}else{
        	// 部分发货
        	$sql2_new="UPDATE ecshop.ecs_order_mapping 
        		set shipping_status='SELLER_CONSIGNED_PART' ,
        			tracking_numbers = IF(tracking_numbers = '', '{$tracking_number}', CONCAT(tracking_numbers, ',', '{$tracking_number}')),
				    update_time = NOW()
        		where mapping_id = :id";
    	}
        // 淘宝上找不到的订单
        $sql3_new="UPDATE ecshop.ecs_order_mapping 
        	set shipping_status='TRADE_FINISHED' ,
        		tracking_numbers = IF(tracking_numbers = '', '{$tracking_number}', CONCAT(tracking_numbers, ',', '{$tracking_number}')),
				update_time = NOW()
        	where mapping_id = :id";
        // 淘宝端已发货
        $sql4_new="UPDATE ecshop.ecs_order_mapping 
        	set shipping_status='TAOBAO_DELIVERED' ,
        		tracking_numbers = IF(tracking_numbers = '', '{$tracking_number}', CONCAT(tracking_numbers, ',', '{$tracking_number}')),
				update_time = NOW()
        	where mapping_id = :id";
        
        //无论是什么异常，都先更改状态
		$sql5_new="UPDATE ecshop.ecs_order_mapping 
        	set shipping_status='DELIVERY_FAIL' ,
        		memo=IF(memo is null,:memo1,concat(memo,';', :memo2)),
        		update_time = NOW()
        	where mapping_id = :id";

        $html="<p>SD_RECORD: ".json_encode($record)."</p>";
        $html.="<p>JUSHITA_REQUEST: ".json_encode($request)."</p>" ;
        $html.="<p><a target='_blank' href='https://ecadmin.leqee.com/admin/SinriTest/taobao_split_oid_monitor.php?act=for_tb&tbsn={$record['mp_taobao_order_sn']}'>查看多订单多面单详情</a>"."</p>";
        $html.="<p>";

        // 请求淘宝发货
        try
        {
        	// 远程服务
        	ini_set('default_socket_timeout', 600);
	    	$client=Yii::app()->getComponent('syncJushita')->SyncTaobaoService;

            $response = null ;
            
            $response = $client->SyncTaobaoOrderDeliverySend($request)->return;
            if(isset($response->shipping) && isset($response->shipping->isSuccess) && $response->shipping->isSuccess)
            {
//            	$update_old=Yii::app()->getDb()->createCommand($sql2)->bindValue(':id',$old_mapping_id)->execute();
                $update_new=Yii::app()->getDb()->createCommand($sql2_new)->bindValue(':id',$eom_mapping_id)->execute();
				if(!empty($update_new)){
                	$is_ok=true;
                	$html.="[NEW_OK]Commonly Success.";
                }else{
                	$html.="[NEW_KO]Commonly Success but db update failed.";
                }
            }
            // 已经手动发货了
            else if(isset($response->subCode) && $response->subCode=='isv.logistics-offline-service-error:B04')
            {
//            	$update_old=Yii::app()->getDb()->createCommand($sql2)->bindValue(':id',$old_mapping_id)->execute();
                $update_new=Yii::app()->getDb()->createCommand($sql2_new)->bindValue(':id',$eom_mapping_id)->execute();
                if(!empty($update_new)){
                	$is_ok=true;
                	$html.="[NEW_OK]B04, has been deliveried manually.";
                }else{
                	$html.="[NEW_KO]B04, has been deliveried manually but db update failed.";
                }
            }
            // 淘宝上面已经不存在的订单
            else if(isset($response->subCode) && $response->subCode=='isv.logistics-offline-service-error:B01')
            {
//            	$update_old=Yii::app()->getDb()->createCommand($sql3)->bindValue(':id',$old_mapping_id)->execute();
                $update_new=Yii::app()->getDb()->createCommand($sql3_new)->bindValue(':id',$eom_mapping_id)->execute();
                if(!empty($update_new)){
                	$is_ok=true;
                	$html.="[NEW_OK]B01, order not existed on Taobao.";
                }else{
					$html.="[NEW_KO]B01, order not existed on Taobao but db update failed.";
                }
            }
            //运单号不符合规则或已经被使用
            else if (isset($response->subCode) && $response->subCode == 'isv.logistics-offline-service-error:B60') {
        		require_once(ROOT_PATH . 'admin/ajax.php');
        		$result = ajax_check_tracking_number(array("carrier_id"=>$record['carrier_id'],"tracking_number"=>$record['tracking_number']));
        		if($result){
        			//符合规则说明运营同学已经维护过，无需再同步
//        			$update_old=Yii::app()->getDb()->createCommand($sql3)->bindValue(':id',$old_mapping_id)->execute();
        			$update_new=Yii::app()->getDb()->createCommand($sql3_new)->bindValue(':id',$eom_mapping_id)->execute();
                	if(!empty($update_new)){
                		$is_ok=true;
                		$html.="[NEW_OK]B60 but has maintained manually.";
                	}else{
                		$html.="[NEW_KO]B60 but has maintained manually but db update failed.";
                	}
        		}else{
        		 	$sql = "select count(1) from ecshop.thermal_express_mailnos where tracking_number='{$record['tracking_number']}' and status != 'N' and shipping_id = '{$record['shipping_id']}'";
            		$is_thermal = Yii::app()->getDb()->createCommand($sql)->queryScalar();
            		if($is_thermal == 0){
            		
            			$memo="[FAIL]运单号不符合规则,同步发货失败";

	            		//异常订单，暂时均处理为DELIVERY_FAIL
//	            		$update_old=Yii::app()->getDb()->createCommand($sql5)->bindValue(':id',$old_mapping_id)->execute();
	                	$update_new=Yii::app()->getDb()->createCommand($sql5_new)->bindValue(':id',$eom_mapping_id)->bindValue(':memo1',$memo)->bindValue(':memo2',$memo)->execute();
            	
            			$html .= $memo."\n";
            		}else{
            			//热敏单号淘宝部分不支持，或已被维护，无需再同步
//            			$update_old=Yii::app()->getDb()->createCommand($sql3)->bindValue(':id',$old_mapping_id)->execute();
                		$update_new=Yii::app()->getDb()->createCommand($sql3_new)->bindValue(':id',$eom_mapping_id)->execute();
                		if(!empty($update_new)){
                			$is_ok=true;
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
            	
            	//异常订单，暂时均处理为DELIVERY_FAIL
            	$memo="Code: ".$response->subCode." Msg: ".$response->msg;
//            	$update_old=Yii::app()->getDb()->createCommand($sql5)->bindValue(':id',$old_mapping_id)->execute();
                $update_new=Yii::app()->getDb()->createCommand($sql5_new)->bindValue(':id',$eom_mapping_id)->bindValue(':memo1',$memo)->bindValue(':memo2',$memo)->execute();
            	
            	$is_ok=true;
            	$html.="[OK]Code as ".$memo;
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
//				$update_old=Yii::app()->getDb()->createCommand($sql4)->bindValue(':id',$old_mapping_id)->execute();
                $update_new=Yii::app()->getDb()->createCommand($sql4_new)->bindValue(':id',$eom_mapping_id)->execute();
                if(!empty($update_new)){
                	$is_ok=true;
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
			//CD25
			else if(isset($response->subCode) && in_array($response->subCode,array('CD25'))){
				
				//异常订单，暂时均处理为DELIVERY_FAIL
				$memo="[FAIL]code as ".$response->subCode." msg as ".$response->msg." sub_msg as ".$response->subMsg;
//            	$update_old=Yii::app()->getDb()->createCommand($sql5)->bindValue(':id',$old_mapping_id)->execute();
                $update_new=Yii::app()->getDb()->createCommand($sql5_new)->bindValue(':id',$eom_mapping_id)->bindValue(':memo1',$memo)->bindValue(':memo2',$memo)->execute();
            	
            	
				$html.=$memo;

				echo "SYNC_DELIVERY_TAOBAO_CD25: ".$record['mp_taobao_order_sn'].' | '.$record['order_id'].PHP_EOL;
            }
			else if(isset($response->subCode) && in_array($response->subCode,array('CD01'))){
				//当subCode为'CD01'，不处理为DELIVERY_FAIL
				$memo="Exception, ";
            	if(isset($response->subCode)){
            		$memo.="code as ".$response->subCode.", ";
            	}
            	if(isset($response->msg)){
            		$memo.="msg as ".$response->msg.", ";
            	}
            	if(isset($response->subMsg)){
            		$memo.="sub_msg as ".$response->subMsg.", ";
            	}
            	$html.= $memo;
            }
            
            //当状态为B105/S01时，不处理为DELIVERY_FAIL
            //author：xhchen
            else if(isset($response->subCode) && (in_array($response->subCode,array('B150'))||in_array($response->subCode,array('S01')))){
				$memo="Exception, ";
            	if(isset($response->subCode)){
            		$memo.="code as ".$response->subCode.", ";
            	}
            	if(isset($response->msg)){
            		$memo.="msg as ".$response->msg.", ";
            	}
            	if(isset($response->subMsg)){
            		$memo.="sub_msg as ".$response->subMsg.", ";
            	}
            	$html.= $memo;
            }
            //END
            
            
            else if(isset($response->msg) && $response->msg=='Platform System error'){
            	//当异常信息为Platform System error，不处理为DELIVERY_FAIL
            	$html.="[FAIL] msg as ".$response->msg;
            }
            // 其他错误
            else {
            	//异常订单，暂时均处理为DELIVERY_FAIL
            	$memo="Exception, ";
            	if(isset($response->subCode)){
            		$memo.="code as ".$response->subCode.", ";
            	}
            	if(isset($response->msg)){
            		$memo.="msg as ".$response->msg.", ";
            	}
            	if(isset($response->subMsg)){
            		$memo.="sub_msg as ".$response->subMsg.", ";
            	}
//            	$update_old=Yii::app()->getDb()->createCommand($sql5)->bindValue(':id',$old_mapping_id)->execute();
                $update_new=Yii::app()->getDb()->createCommand($sql5_new)->bindValue(':id',$eom_mapping_id)->bindValue(':memo1',$memo)->bindValue(':memo2',$memo)->execute();
            	
            	$html.=$memo;
            	// echo "[FAIL]";
            	
            	echo PHP_EOL;
			}
			
			
			$pub_msg = 'taobao_order_sn=' .$record['mp_taobao_order_sn'] .' sub_tid='.$sub_tid.' is_split='.$is_split.' company_code='.isset($codeMap[$record['shipping_name']])?$codeMap[$record['shipping_name']]:'OTHER'.'out_sid='.$tracking_number;
			//打印Response信息 
			if(isset($response->shipping) && isset($response->shipping->isSuccess) && $response->shipping->isSuccess)
            {//发货成功
            	echo "\nTaobaoDeliverSuccess:pub_msg--".$pub_msg."\n" ;
            }else{
            	$err_msg = ' ';
            	if(isset($response->subCode)){
            		$err_msg.="code=".$response->subCode."; ";
            	}
            	if(isset($response->msg)){
            		$err_msg.=" msg=".$response->msg."; ";
            	}
            	if(isset($response->subMsg)){
            		$err_msg.=" sub_msg=".$response->subMsg."; ";
            	};
            	echo "TaobaoDeliverFail:pub_msg--".$pub_msg." err_msg--".$err_msg;
            }
			


    //         else if (isset($response->subCode) && isset($response->msg))
    //         {  
				// //echo("|  - has error: ".$response->subCode.", ".$response->msg.", 订单号：".$request['tid'] .", 子订单号列表：".$request['sub_tid'].", 面单号:".$request['out_sid']."\n");
				// $html.="[FAIL]code as ".$response->subCode."; msg as ".$response->msg;
    //         } else {
				// // echo("|  - has error: \n");
    //         	$html.="[FAIL]Unknown Error";
    //         }

			$html.=" update_new=".$update_new.PHP_EOL;
        }
        catch (Exception $e)
        {
//	                    echo("|  - has exception: ". $e->getMessage() . "\n");
        	$html.="[EXCEPTION]".$e->getMessage();
        }

        $html.="</p>";
        usleep(100000);
	            
	    return $is_ok;
    }

    private function jdSyncDelivery($record,&$html){
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
	        '京东COD' => '2087',
	        '京东配送' => '2087'
        );
    	
    	try {
    		// 远程服务
    		$client=Yii::app()->getComponent('erpsync')->SyncJdService;
    		
    		$shipping_status = 'WAIT_GOODS_RECEIVE_CONFIRM';
            $order_id =  $record['order_id'];
            $taobao_order_sn = $record['mp_taobao_order_sn'];
            $order_status = 'WAIT_GOODS_RECEIVE_CONFIRM';
            $order_state_remark = '等待收货确认';

            $tracking_numbers=implode(",",$record['TNS']);
    		
    		$request = array(
	                    // 分销订单, 用分销采购订单号发货
	                    'applicationKey' =>$record['application_key'],
	                    'order_id'=>$record['order_id'],
	                    'jd_order_id'=>$record['mp_taobao_order_sn'],
	                    'shipping_name'=>$record['shipping_name'] ,
	                    'logistics_id'=>isset($codeMap[$record['shipping_name']])?$codeMap[$record['shipping_name']]:'OTHER',
	                    'way_bill'=>$tracking_numbers,
                    );
	
	        // 请求京东发货
            $response = null ;

            $html.='JD_REQUEST: '.json_encode($request).PHP_EOL;
            
            $response = $client->SyncJdOrderSendDeliveryNew($request)->return;
            if(isset($response) && isset($response->code) && ($response->code=='0' || $response->code=='10400001' ||  $response->code=='10400010'    ) ){ //10400001表示已经手动发货（京东后台已经出库）   			
    			// jdOrderId,"WAIT_GOODS_RECEIVE_CONFIRM","等待收货确认"
    			
    			$update_jd_order_info = "UPDATE ecshop.sync_jd_order_info set order_state='{$order_status}', order_state_remark='{$order_state_remark}', modified=now() where order_id= {$taobao_order_sn}	";
    			// $update_jd_order_info_result = $db->query($update_jd_order_info);
    			$update_jd_order_info_result=Yii::app()->getDb()->createCommand($update_jd_order_info)->execute();
    			
    			$update_jd_order_mapping = "UPDATE ecshop.ecs_jd_order_mapping set shipping_status = '{$shipping_status}'   where taobao_order_sn = '{$taobao_order_sn}'" ;
    			// $update_jd_order_mapping_result = $db->query($update_jd_order_mapping);
    			$update_jd_order_mapping_result=Yii::app()->getDb()->createCommand($update_jd_order_mapping)->execute();

    			$update_jd_order_mapping_new = "UPDATE ecshop.ecs_order_mapping 
    				set shipping_status = '{$shipping_status}',
    					tracking_numbers = IF(tracking_numbers = '', '{$tracking_numbers}', CONCAT(tracking_numbers, ',', '{$tracking_numbers}')),
						update_time = NOW()
    				where outer_order_sn = '{$taobao_order_sn}' and platform in ('360buy','360buy_overseas')" ;
    			// $update_jd_order_mapping_result = $db->query($update_jd_order_mapping);
    			$update_jd_order_mapping_result_new=Yii::app()->getDb()->createCommand($update_jd_order_mapping_new)->execute();

    			$html.="update_jd_order_info_result=".$update_jd_order_info_result." update_jd_order_mapping_result=".$update_jd_order_mapping_result." update_jd_order_mapping_result_new=".$update_jd_order_mapping_result_new.PHP_EOL;

    			if( $update_jd_order_info_result && $update_jd_order_mapping_result_new ){
    				$html.=("js_delivery success! order_id: ".$order_id ." code: ".$response->code ." msg: ". $response->msg .' ');
	    			$html.=PHP_EOL;
	    			return true;
    			}else{
    				$html.=("js_delivery success but db failed! order_id: ".$order_id ." code: ".$response->code ." msg: ". $response->msg .' ');
	    			$html.=PHP_EOL;
    			}
            }
            
            elseif( isset($response) && isset($response->code) && ($response->code=='10300009' )){  //运单没有在青龙系统生成，一般是生成京东运单号，京东青龙系统延迟导致的，下次还需要继续进行发货同步
             	$html.=("js_delivery fail! order_id: ".$order_id ." code: ".$response->code ." msg: ". $response->msg .' ');
            }
            else {
            	//异常的订单都先处理为DELIVERY_FAIL
            	$memo="js_delivery fail! order_id: ".$order_id ." code: ".$response->code ." msg: ". $response->msg;
            	$update_jd_order_mapping = "UPDATE ecshop.ecs_jd_order_mapping 
            		set 
            			shipping_status = 'DELIVERY_FAIL'
            		where 
            			taobao_order_sn = '{$taobao_order_sn}'
            	" ;
    			// $update_jd_order_mapping_result = $db->query($update_jd_order_mapping);
    			$update_jd_order_mapping_result=Yii::app()->getDb()->createCommand($update_jd_order_mapping)->execute();

    			$update_jd_order_mapping_new = "UPDATE ecshop.ecs_order_mapping 
    				set shipping_status = 'DELIVERY_FAIL',
            			memo=IF(memo is null,:memo1,concat(memo,';', :memo2)),
            			update_time = NOW()
    				where outer_order_sn = '{$taobao_order_sn}' and platform in ('360buy','360buy_overseas')" ;
    			// $update_jd_order_mapping_result = $db->query($update_jd_order_mapping);
    			$update_jd_order_mapping_result_new=Yii::app()->getDb()->createCommand($update_jd_order_mapping_new)->bindValue(':memo1',$memo)->bindValue(':memo2',$memo)->execute();
    			
	            $html.=("js_delivery fail! order_id: ".$order_id ." code: ".$response->code ." msg: ". $response->msg .PHP_EOL);
            }
    		
    	} catch (Exception $e) {
    		$html .= "Exception: ".$e->getMessage().PHP_EOL;
    	}
    	return false;
    }

    ////////

    private function send_alert_mail($issues){
    	if(empty($issues)){
    		return;
    	}
    	try {
    		$html='<p>'.PHP_EOL.(implode(PHP_EOL.'</p>'.PHP_EOL.'<p>'.PHP_EOL, $issues)).PHP_EOL.'</p>';

            $mail=Yii::app()->getComponent('mail');
            $mail->Subject="发货同步失败";
          	
            $mail->ClearAddresses();
	        $mail->AddAddress('qyyao@leqee.com', 'qyyao');
	        $mail->AddAddress('hyzhou1@leqee.com', 'hyzhou1');
	        $mail->AddAddress('stsun@leqee.com', 'stsun');
	        $mail->AddAddress('yxie@leqee.com', 'yxie');
	        $mail->AddAddress('mjzhou@leqee.com', 'mjzhou');
        	$mail->AddAddress('ljni@leqee.com', '邪恶的大鲵');
            $mail->Body = "<pre>".$html."</pre>";
            $mail->IsHtml();  // 如果邮件是html格式的话
            $mail->send();
        } catch (Exception $e){

        }
    }
}

/*
按照20150916在报表数据库测试的结果，同一个taobao的oid的商品们拆到了不同的order_id下之后造成分单发货同步被坑的情况。呜呼。因为单子不多，不正常的就呵呵了，得手工。

select count(order_id) from ecshop.ecs_order_info oi
where oi.order_status=1
and oi.order_time>'2015-09-15 00:00:00'
and EXISTS (select 1 from ecshop.ecs_order_goods og where og.order_id=oi.order_id and og.out_order_goods_id limit 1)
-- all order is 8172
;

select count(distinct eoi.order_id) from 
(
select og.out_order_goods_id from ecshop.ecs_order_info oi
inner join ecshop.ecs_order_goods og on oi.order_id=og.order_id
where oi.order_status=1
and oi.order_time>'2015-09-15 00:00:00'
and og.out_order_goods_id is not null and og.out_order_goods_id!=''
group by og.out_order_goods_id
having count(distinct og.order_id)>1
) m
inner join ecshop.ecs_order_goods eog on eog.out_order_goods_id=m.out_order_goods_id
inner join ecshop.ecs_order_info eoi on eog.order_id=eoi.order_id
where 
eoi.order_status=1
and eoi.order_time>'2015-09-15 00:00:00'
-- this is 42
;
*/