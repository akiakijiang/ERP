<?php
define('IN_ECS', true);
define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'includes/helper/mail.php';
require_once (ROOT_PATH . 'admin/includes/lib_express_arata.php');


/*
 * Created on 2014-11-06
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 * 
 * C:\wamp\www\erp\protected\commands>php ../yiic carrierBillArataBinding test
 */
class CarrierBillArataBindingCommand extends CConsoleCommand {

	private $master; // Master数据库

	/**
	 * 当不指定ActionName时的默认调用
	 */
	public function actionIndex() {
		//生成出库指示数据
		$this->run(array (
			'test'
		));
	}

	public function actionTest() {
		$this->log('All Green!');
	}

	public function actionBindAll($shipping_id=100){
		$this->log('begin');
		
		$lock_file_name = $this->get_file_lock_path('bindtrackingnumber'.$shipping_id, 'pick');
		$lock_file_point = fopen($lock_file_name, "w+");
	    $would_block = false;
		if(flock($lock_file_point, LOCK_EX|LOCK_NB, $would_block)){
			try{
				//usleep(10000000);
				$this->run(array('BindTrackingAfterBPSN','--shipping_id='.$shipping_id)); 
				//$this->run(array('BindTrackingBeforeBPSN'));
				flock($lock_file_point, LOCK_UN);
	    		fclose($lock_file_point);
			}catch(Exception $e){
				echo("[". date('c'). "] 调度任务失败"."\n"); 
			}
		}else{
	    	fclose($lock_file_point);
	    	echo("[". date('c'). "] 调度冲突，请稍后"."\n"); 
	    }
		$this->log('end');
	}

	/*
	 * 批拣前
	 * */
	public function actionBindTrackingBeforeBPSN(){
		$this->log("[BindTrackingBeforeBPSN][begin]");
		$limit = 1000;
		$sql = "SELECT
				shipping_id,facility_id
			FROM
				romeo.facility_shipping
			where is_delete = 0
		";
		$shiping_facility_list = $this->getMaster()->createCommand($sql)->queryAll();
		foreach($shiping_facility_list as $shiping_facility){
			$facility_id = $shiping_facility['facility_id'];
			$shipping_id = $shiping_facility['shipping_id'];
			$this->log("[BindTrackingBeforeBPSN][ing] '{$facility_id}','{$shipping_id}'");
			$sql = "SELECT
						os.shipment_id,os.order_id,o.carrier_bill_id
					FROM
						ecshop.ecs_order_info o
					inner join romeo.order_shipment os on convert(o.order_id using utf8) = os.order_id
					inner join romeo.shipment s on os.shipment_id = s.shipment_id 
					WHERE
						o.shipping_status = 0 
					AND o.facility_id = '{$facility_id}' 
					AND  o.shipping_id = '{$shipping_id}'
					AND(
						s.TRACKING_NUMBER = ''
						OR s.TRACKING_NUMBER IS NULL
					)
					AND s.SHIPPING_CATEGORY = 'SHIPPING_SEND'
					limit {$limit}";
			$shipment_total_list = $this->getMaster()->createCommand($sql)->queryAll();
			$number = count($shipment_total_list);
			$this->log("[BindTrackingBeforeBPSN] count:'{$number}' ");
			if(isset($shipment_total_list) && count($shipment_total_list) > 0){
				$length = 100;
				for($i=0; $i < count($shipment_total_list); $i += $length){
					$shipment_list = array_slice($shipment_total_list, $i, $length);
					$this->add_thermal_tracking_number($shipment_list,$shipping_id,$facility_id);
				}
			}
		}
		$this->log("[BindTrackingBeforeBPSN][end]");
	}
	/*
	 * 批拣后
	 * */
	public function actionBindTrackingAfterBPSN($shipping_id=100,$facility_id=0){
		$this->log('ABOVE INNER BENEATH OUTER');
		$bindcont = "";
		if($facility_id!=0){
			$bindcont = " and fs.facility_id = '".$facility_id."' ";
		}
		$sql = "SELECT bpm.batch_pick_id
			from romeo.facility_shipping fs 
			inner join ecshop.ecs_order_info oi 
				ON fs.facility_id = oi.facility_id AND fs.shipping_id = oi.shipping_id
			inner join romeo.order_shipment os on oi.order_id=cast(os.order_id as UNSIGNED)
			inner join romeo.shipment s on os.shipment_id = s.shipment_id
			inner join romeo.out_batch_pick_mapping bpm on s.shipment_id = bpm.shipment_id
			where fs.is_delete = 0  and bpm.created_stamp > date_sub(NOW(),interval 10 day)
			and (s.TRACKING_NUMBER = '' OR s.TRACKING_NUMBER IS NULL)  and fs.shipping_id = ".$shipping_id." {$bindcont}
			group by bpm.batch_pick_id 
			limit 20 ";
		$bpsn_list = $this->getMaster()->createCommand($sql)->queryAll();
		foreach($bpsn_list as $bpsn){
			$this->add_thermal_out_bpsn($bpsn['batch_pick_id']);
		}
		$this->log("[actionBindTrackingAfterBPSN] end");
	}
	private function add_thermal_bpsn($bpsn,$shipping_id,$facility_id){
		$this->log("[add_thermal_bpsn] begin:'{$bpsn}' ");
		$branch=getLocalBranchWithFacilityId($facility_id);
		$sql = "SELECT os.shipment_id,os.order_id,oi.carrier_bill_id,oi.shipping_id,oi.facility_id
				from romeo.batch_pick bp
				inner join romeo.batch_pick_mapping bpm on bp.batch_pick_sn = bpm.batch_pick_sn
				inner join romeo.shipment s on bpm.shipment_id = s.shipment_id
				inner join romeo.order_shipment os on s.shipment_id = os.shipment_id 
				inner join ecshop.ecs_order_info oi on os.order_id = oi.order_id 
				where bp.batch_pick_sn = '{$bpsn}' and (s.TRACKING_NUMBER = '' OR s.TRACKING_NUMBER IS NULL)";
		//sinri:这个SQL下考虑合并订单情况
		$shipment_list = $this->getMaster()->createCommand($sql)->queryAll();
		$this->add_thermal_tracking_number($shipment_list,$shipping_id,$branch);
		$this->log("[add_thermal_bpsn] end:'{$bpsn}' ");
	}
	private function add_thermal_out_bpsn($bpsn_id){
		$this->log("[add_thermal_out_bpsn] begin:'{$bpsn_id}' ");
		$sql = "SELECT bpm.shipment_id,s.SHIPMENT_TYPE_ID
				from romeo.out_batch_pick bp
				inner join romeo.out_batch_pick_mapping bpm on bp.batch_pick_id = bpm.batch_pick_id
				inner join romeo.shipment s on bpm.shipment_id = s.shipment_id
				inner join romeo.order_shipment os on os.order_id = s.primary_order_id and s.shipment_id = os.shipment_id
				where bp.batch_pick_id = '{$bpsn_id}' 
						and (s.TRACKING_NUMBER = '' OR s.TRACKING_NUMBER IS NULL) ";
		$shipments = $this->getMaster()->createCommand($sql)->queryAll();
	    foreach($shipments as $shipment){
	    	if(!$this->add_thermal_out_tracking_number($shipment['shipment_id'])){
	    		return false;
	    	}
	    }
	    
	    if($shipments[0]['SHIPMENT_TYPE_ID']=='115'){
			$sql = "select oi.order_id,oi.facility_id,r1.region_name as province_name,r2.region_name as city_name,r3.region_name as district_name,oi.address
				from romeo.out_batch_pick_mapping bpm
				inner join romeo.order_shipment os on bpm.shipment_id = os.shipment_id 
				inner join ecshop.ecs_order_info oi on cast(os.order_id as unsigned) = oi.order_id 
				left join ecshop.order_attribute oa on oa.order_id = oi.order_id and oa.attr_name='ztoBigPen' 
				left join ecshop.ecs_region r1 on r1.region_id = oi.province 
				left join ecshop.ecs_region r2 on r2.region_id = oi.city
				left join ecshop.ecs_region r3 on r3.region_id = oi.district 
				where bpm.batch_pick_sn = '{$bpsn}' and oa.attribute_id is null AND oi.shipping_id = 115 ";
			$applyZtoMarkOrders = $this->getMaster()->createCommand($sql)->queryAll();
			if(count($applyZtoMarkOrders)>0){
				foreach($applyZtoMarkOrders as $applyZtoMarkOrder){
					get_zto_mark_single_order($applyZtoMarkOrder);
				}
			}
	    }
    
	    $this->log("[add_thermal_out_bpsn] end:'{$bpsn_id}' ");
	    return true;
	}
	private function add_thermal_tracking_number($order_shipment_ids,$shipping_id,$branch){
		$this->log("add_thermal_tracking_number($order_shipment_ids,$shipping_id,$branch) GO");
		$shipments=array();
		foreach ($order_shipment_ids as $line) {
			$shipments[$line['shipment_id']]=$line;
		}
		$shipment_id_array = array_keys($shipments);
		$number = count($shipments);

		if($number<=0) {
			$this->log('add_thermal_tracking_number count 0 error, return false');
			return false;
		}

		$this->log("[add_thermal_tracking_number] shipment count:{$number} ");

		$tracking_numbers = get_thermal_mailnos($shipping_id,$branch,$number);
		//print_r($tracking_numbers);
		if(!isset($tracking_numbers) || count($tracking_numbers) != count($shipments))
		{
			$this->log('try to get thermal mailnos but failed to reserve all');
			return false;
		}
		$sql_shipment_array = array();
		$sql_carrier_array = array();
		$shipment_ids = array();
		$tn_to_bind=array();

		$i=0;
		foreach ($shipments as $shipment_id => $line) {
			$tracking_number = $tracking_numbers[$i]['tracking_number'];
			$bill_id = $line['carrier_bill_id'];

			$this->log("Preparing: shipment=".$shipment_id." tracking_number=".$tracking_number." facility_id=".$line['facility_id']." shipping_id=".$line['shipping_id']);

			$sql_shipment_array[] = "UPDATE romeo.shipment
				SET tracking_number = '{$tracking_number}',
				 LAST_MODIFIED_BY_USER_LOGIN = 'cronjob',
				 LAST_UPDATE_STAMP = NOW(),
				 LAST_UPDATE_TX_STAMP = NOW()
				WHERE
					shipment_id = '{$shipment_id}' ";
			$shipment_ids[] = $shipment_id;
			$sql_carrier_array[] = "UPDATE ecshop.ecs_carrier_bill SET bill_no = '{$tracking_number}' WHERE bill_id = '{$bill_id}' ";
			$tn_to_bind[]=$tracking_number;

			$i++;
		}


		$sql_check="select count(1) as counting from romeo.shipment where tracking_number in ('" . implode("','", $tn_to_bind) . "') and shipment_type_id={$shipping_id} ";
		$check_as_all=$this->queryMaster($sql_check);
		if($check_as_all[0]['counting']>0){
			$this->log("Unique Shipment Tracking Number Checking Failed [{$check_as_all[0]['counting']}] !");
			return false;
		}

		$sql = implode(";", $sql_shipment_array);
		$this->executeMaster($sql);
		bind_arata_shipment_mailnos($shipping_id,$tn_to_bind);//from lib_express_arata.php
	  	$sql = implode(";", $sql_carrier_array);
		$this->executeMaster($sql);
	  	return true;
	}
	
	/**
	 * 根据发货单号绑定运单号(外包使用热敏快递：中通，圆通，韵达，汇通，宅急送)
	 * 2015.10.10
	 * 1. 圆通单独通过接口拉取（同时获取大头笔信息，插入到ecshop.order_attribute中attr_name='bigPen';运单号插入到thermal_express_mailnos-'R'）
	 * 2. 韵达单独通过接口拉取（同时获取大头笔等信息，插入到ecshop.ecs_order_yunda_mailno_apply;运单号插入到thermal_express_mailnos-'R'） 
	 * *** 韵达获取方式有两种： 1. 贝亲账户只用于贝亲青浦仓使用加密的pdf_info原方式纸张打印  2. 其他账户使用非加密的pdf_info通用面单纸打印（外包仅限此方案）
	 * 3. 汇通，申通，中通，宅急送（批量拉取绑定模式，通过ecshop.thermal_express_mailnos ）
	 */
	private function add_thermal_out_tracking_number($shipmentId){
		global $db;
		$sql="SELECT
		      oi.order_id,oi.order_sn,oi.shipping_id,oi.distributor_id,
	        oi.carrier_bill_id,oi.order_status,oi.shipping_status,oi.pay_status,
	        s.tracking_number,oi.facility_id
		      FROM
		      ecshop.ecs_order_info oi
		      inner join romeo.order_shipment os ON oi.order_id = cast(os.order_id as unsigned)
		      inner join romeo.shipment s ON os.shipment_id = s.shipment_id
		      where os.shipment_id = '{$shipmentId}' and oi.order_status = 1 limit 1
		";
		$order=$db->getRow($sql);
		
		if(empty($order)){
			echo('<div style="color:red;font-weight:bolder;">发货单'.$shipmentId.'对应订单已被取消！</div>');
			return false;
		}
		// 这个IF用于解决已经有面单的单子不再添加面单，减少浪费面单
		if(!empty($order['tracking_number'])){
	    	return true;
		}
	   
	   	//85	圆通快递 ; 89	申通快递 ; 99	汇通快递 ; 100	韵达快递 ; 115	中通快递 ; 12 宅急送
		$arata_shipping_ids=array('85','89','99','100','115','12');//涉及不同仓库划分
	
		for($i=0;$i<3;$i++){
			$branch='';
			if(in_array($order['shipping_id'],$arata_shipping_ids)){ //四通一达  lib_express_arata.php
			  	$branch=getBranchWithFacilityId($order['facility_id']);  // 后期需整改
			  	if($order['facility_id']=='185963127'){$branch='BJ';} //北京水果外包流程 使用热敏账号为 电商服务北京仓 ！！！！！
			  	if($order['shipping_id'] =='85'){//圆通快递
			  		if($order['facility_id']=='185963131'){$branch='JXWBC';} //嘉兴外包仓 圆通使用“JXWBC”，汇通使用“JXSG” -- 因汇通与其他仓共用账号，而圆通不然。此时，汇通已经有数千号段待用
			  		$tracking_number = get_yto_thermal_mailno($order['order_id'],$shipmentId,$branch);  
			  	}elseif($order['shipping_id'] =='100' && $order['facility_id'] =='24196974'){//贝亲青浦仓的韵达快递
			  	$tracking_number = 0;
//			  		$tracking_number = get_yunda_thermal_mailno_by_order($order['order_id'],$shipmentId,'BQ');  
			  	}elseif($order['shipping_id'] =='100' && $order['facility_id'] !='24196974'){//其他仓的韵达快递
			  		$tracking_number = get_yunda_thermal_mailno_by_order($order['order_id'],$shipmentId,$branch);  
			  	}else{//申通，汇通，中通
			  		$tracking_number = get_thermal_mailno($order['shipping_id'],$branch); 
			  	}
			}
			if($tracking_number!=-1){
			  	break;
			}else{
				Qlog::log('SINRI_WARNING add_thermal_tracking_number for '.$shipmentId.' LOCKED by others, try again?');
				usleep(200000);//0.2 second waiting
			}
	  	}
	  	if($tracking_number=='0'){
	    	echo("订单{$order['order_id']} 热敏面单不足！");
	    	return false;
	  	}else if($tracking_number==-1){
	    	echo "订单{$order['order_id']} 尝试竞拍热敏面单号3次均告失败！请联系ERP！";
	    	return false;
	  	}
	 	$sql = "update romeo.shipment set tracking_number = '{$tracking_number}',last_modified_by_user_login='cronjob' where shipment_id = '{$shipmentId}'  ";
	  	$db->query($sql);
	  	if(in_array($order['shipping_id'],array('89','99','115','12'))){ // R->Y
	  		bind_arata_shipment_mailno($order['shipping_id'],$tracking_number); 
	  	}elseif(in_array($order['shipping_id'],array('100','85'))){// R->F
	  		finish_arata_shipment_mailno($order['shipping_id'],$tracking_number); 
	 	}
	  	$sql=sprintf("UPDATE ecshop.ecs_carrier_bill SET bill_no = '%s' WHERE bill_id = '%d' LIMIT 1",$tracking_number,$order['carrier_bill_id']);
	  	$db->query($sql);
	  	$note = "批拣扫描！热敏！快递面单, 面单号为：{$tracking_number}";
	  	$sql = " INSERT INTO ecshop.ecs_order_action 
	        (order_id, order_status, shipping_status, pay_status, action_time, action_note, action_user) VALUES 
	        ('{$order['order_id']}', '{$order['order_status']}', '{$order['shipping_status']}', '{$order['pay_status']}', NOW(), '{$note}', 'cronjob')
	  	";
	  	$db->query($sql);
	  	return true;
	}


	/**
	 * 取得master数据库连接
	 * 
	 * @return CDbConnection
	 */
	protected function getMaster() {
		if (!$this->master) {
			$this->master = Yii :: app()->getDb();
			$this->master->setActive(true);
		}
		return $this->master;
	}

	protected function queryMaster($sql){
		$list = $this->getMaster()->createCommand($sql)->queryAll();
		return $list;
	}

	protected function executeMaster($sql){
		$list = $this->getMaster()->createCommand($sql)->execute();
		return $list;
	}

	private function log($m) {
		if(is_array($m) || is_object($m)){
			print date("Y-m-d H:i:s") . " " . $m . " \r\n";
			print "Json of array is " . json_encode($m) . " \r\n";
		}else{
			print date("Y-m-d H:i:s") . " " . $m . " \r\n";
		}
	}
	protected function get_file_lock_path($file_name = '', $namespace = null) {
		if (!defined('ROOT_PATH')) {
			define('ROOT_PATH', preg_replace('/admin(.*)/', '', str_replace('\\', '/', __FILE__)));
		}
	    if ($namespace == null) {
	    	preg_match('/\/([^\/]*)\.php/', $_SERVER['SCRIPT_URL'], $matches);
	        $namespace = $matches[1];
	    }
		return ROOT_PATH. "admin/filelock/{$namespace}.{$file_name}";
	}
}


?>