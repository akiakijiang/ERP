<?php
define('IN_ECS', true);
define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'admin/includes/init.php';
include_once ROOT_PATH . 'admin/function.php';
require_once ROOT_PATH . 'includes/helper/mail.php';
require_once (ROOT_PATH . 'includes/helper/array.php');
// include_once (ROOT_PATH . 'admin/includes/lib_order_mixed_status.php');//By Sinri
Yii :: import('application.commands.LockedCommand', true);

function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

/*
苏州乐贝特殊处理
康贝的distributor_id=1201,main_d_id=1099
金佰利的346-270;1177-1253
在系统里登记着的是分销，但其实应该在售后里视作直销
*/
function is_nise_bunke_distributor_id($did){
    return in_array($did,array(1201,346,1177));
}
function is_nise_bunke_main_distributor_id($mdid){
    return in_array($mdid,array(1099,270,1253));
}

/*
把service_type转换成中文
*/
function get_service_item_type_name($v){
    global $service_type_mapping;
    return $service_type_mapping[$v];
}

/*
把back_shipping_status转换成中文
*/
function get_back_shipping_status_name($v){
    global $back_shipping_status_mapping;
    return $back_shipping_status_mapping[$v];
}

/*
把outer_check_status转换成中文
*/
function get_outer_check_status_name($v){
    global $outer_check_status_mapping;
    return $outer_check_status_mapping[$v];
}

/*
把inner_check_status转换成中文
*/
function get_inner_check_status_name($v){
    global $inner_check_status_mapping;
    return $inner_check_status_mapping[$v];
}

/*
把change_shipping_status转换成中文
*/
function get_change_shipping_status_name($v){
    global $change_shipping_status_mapping;
    return $change_shipping_status_mapping[$v];
}

/*
把service_status转换成中文
*/
function get_service_status_name($v){
    global $service_status_mapping;
    return $service_status_mapping[$v];
}

/*
把service_pay_status转换成中文
*/
function get_service_pay_status_name($v){
    global $service_pay_status_mapping;
    return $service_pay_status_mapping[$v];
}

/*
把service_call_status转换成中文
*/
function get_service_call_status_name($v){
    global $service_call_status_mapping;
    return $service_call_status_mapping[$v];
}

/*
把service_return_key转换成中文
v1: bank_info | carrier_info
*/
function get_service_return_key_name($v1,$v2){
    global $service_return_key_mapping;
    return $service_return_key_mapping[$v1][$v2];
}

class PostsaleCacheCommand extends CConsoleCommand {

	private $master; // Master数据库  

	private $updated_ids;  

	/**
	 * 当不指定ActionName时的默认调用
	 */
	public function actionIndex() {
		$this->run(array ('CachePostsaleDuties'));
	}

	private function stat_getParties($party_ids=null){
		$pids="";
		if($party_ids && is_array($party_ids)){
			$pids=implode(',', $party_ids);
			$pids=" and party_id in (".$pids.") ";
		}
		$sql="SELECT party_id from romeo.party WHERE `STATUS`='ok' and IS_LEAF='Y' and SYSTEM_MODE=2 ".$pids;
		// echo $sql;
		$parties=$this->queryMaster($sql);
		return $parties;
	}

	public function actionCachePostsaleDuties(){
		$party_ids=$this->stat_getParties();
		$parties=array();
		foreach ($party_ids as $temp_index => $partyline) {
			$party_id=$partyline['party_id'];
			if($party_id && $party_id!=''){
				$parties[]=$party_id;
				// $this->executeCachePostsaleDuties($party_id);
			}
		}
		$parties=implode(',', $parties);
		$this->executeCachePostsaleDuties($parties);
	}	

	public function executeCachePostsaleDuties($parties){

		$time_0 = microtime_float();

		//FIRST - FIND OUT ALL PARTIES TO CREATE RANGE STRING
		
		// $party_ids=$this->stat_getParties();
		// $parties=array();
		// foreach ($party_ids as $temp_index => $partyline) {
		// 	$party_id=$partyline['party_id'];
		// 	if($party_id && $party_id!=''){
		// 		$parties[]=$party_id;
		// 	}
		// }
		// $parties=implode(',', $parties);

		$this->log($parties);

		$this->updated_ids=array();

		$recent_days=120;

		$time_1 = microtime_float();

		//SECOND - SERVICE

		$services=$this->getServices($parties,$recent_days);

		// $this->log($services);

		foreach ($services as $tempindex => $line) {
			$service_id=$line['service_id'];
			$party_id=$line['party_id'];
			$order_id=$line['order_id'];
			$order_sn=$line['order_sn'];
			$taobao_order_sn=$line['taobao_order_sn'];
			$is_mobile_refund=$line['is_mobile_refund'];
			$goods_on_back_way=($line['back_shipping_status'] == 5)?1:0;
			$next_worker=$this->get_service_next_responsor($line);
			$time_created_duty=$line['apply_datetime'];
			$handle_facility_id=$line['facility_id'];
			$origin_facility_id=$line['origin_facility_id'];
			$service_type=$line['service_type'];
			$service_status_desc=$this->get_service_line_status_description($line);
			$consignee=$line['consignee'];
			$consignee = $this->my_mysql_escape_string($consignee);
			$tel=$line['tel'];
			$tel = $this->my_mysql_escape_string($tel);
			$mobile=$line['mobile'];
			$mobile = $this->my_mysql_escape_string($mobile);
			$dist=$line['type'];
			$distributor_id=$line['distributor_id'];

			if($dist=='fenxiao' && is_nise_bunke_distributor_id($line['distributor_id'])){
				$dist='zhixiao';
			}

			$state_tag=0;
			$todo_tag=0;

			if($line['service_status']==0 && $line['service_type']==1){
				$state_tag=1;
			}elseif ($line['service_status']==0 && $line['service_type']==2) {
				$state_tag=2;
			}elseif (($line['outer_check_status']==23 || $line['inner_check_status']==32) && $line['service_call_status']==1 && $line['service_type']==2) {
				$state_tag=3;
			}elseif (($line['outer_check_status']==23 || $line['inner_check_status']==32) && $line['service_call_status']==1 && $line['service_type']==1) {
				$state_tag=4;
			}elseif (($line['outer_check_status']==23 || $line['inner_check_status']==32) && $line['service_call_status']==2 && $line['service_pay_status']==0 && $line['service_type']==2) {
				$state_tag=5;
			}elseif ($line['service_status']==1 && $line['back_shipping_status']==0) {
				$state_tag=6;
			}elseif ($line['service_status']==1 && $line['back_shipping_status']==12 && $line['outer_check_status']==0 && $line['inner_check_status']==0) {
				$state_tag=7;
			}elseif ($line['service_pay_status']==2 && $line['service_type']==2) {
				$state_tag=8;
			}elseif ($line['service_status']==3 && $line['service_call_status']!=2) {
				$state_tag=9;
			}elseif ($line['back_shipping_status']==5 && $line['service_call_status']==0 && $line['outer_check_status']==0 && $line['inner_check_status']==0) {
				$state_tag=22;
			}

			if($state_tag!=0){
				$this->updated_ids['services'][]=$service_id;
			}

			if($taobao_order_sn == null || $taobao_order_sn==''){
				// $this->log('NULL TBSN is ['.$taobao_order_sn.']');
				$taobao_order_sn='*'.$order_id;
				// $this->log('NULL TBSN -> '.$taobao_order_sn);
			}
			$consignee=$this->my_mysql_escape_string($consignee);
			$sql_insert="INSERT INTO ecshop.cache_postsale_service (
				service_id,
				party_id,
				order_id,
				order_sn,
				taobao_order_sn,
				is_mobile_refund,
				consignee,
				tel,
				mobile,
				dist,
				begin_time,
				is_waiting_back,
				next_worker,
				handle_facility_id,
				origin_facility_id,
				service_type,
				service_status_desc,
				state_tag,
				todo_tag,
				distributor_id,
				cache_time
			) 
			VALUES(
				{$service_id},
				{$party_id},
				{$order_id},
				'{$order_sn}',
				'{$taobao_order_sn}',
				'{$is_mobile_refund}',
				'{$consignee}',
				'{$tel}',
				'{$mobile}',
				'{$dist}',
				'{$time_created_duty}',
				{$goods_on_back_way},
				'{$next_worker}',
				'{$handle_facility_id}',
				'{$origin_facility_id}',
				{$service_type},
				'{$service_status_desc}',
				{$state_tag},
				{$todo_tag},
				'{$distributor_id}',
				NOW()
			) 
			ON DUPLICATE KEY UPDATE 
				is_waiting_back=VALUES(is_waiting_back),
				next_worker=VALUES(next_worker),
				handle_facility_id=VALUES(handle_facility_id),
				origin_facility_id=VALUES(origin_facility_id),
				service_type=VALUES(service_type),
				service_status_desc=VALUES(service_status_desc),
				state_tag=VALUES(state_tag),
				todo_tag=VALUES(todo_tag),
				cache_time=VALUES(cache_time)
			";

			// $this->log($sql_insert);
			$services_done=$this->executeMaster($sql_insert);
			// $this->log("SERVICE ".$service_id." executed ".$services_done);
		}


		$time_2 = microtime_float();

		//THIRD - REFUND

		$refunds=$this->getRefunds($parties,$recent_days);

		// $this->log($refunds);

		// 退款状态
		$refund_status_name=array(
		    'RFND_STTS_INIT'=>"已生成",
		    'RFND_STTS_IN_CHECK'=>"处理中",
		    'RFND_STTS_CHECK_OK'=>"已审毕",
		    'RFND_STTS_EXECUTED'=>"已完成",
		    'RFND_STTS_CANCELED'=>"已取消"
		);

		foreach ($refunds as $tempindex => $line) {
			$refund_id=$line['REFUND_ID'];
			$party_id=$line['PARTY_ID'];
			$order_id=$line['order_id'];
			$order_sn=$line['order_sn'];
			$taobao_order_sn=$line['taobao_order_sn'];
			$is_mobile_refund=$line['is_mobile_refund'];
			$time_created_duty=$line['CREATED_STAMP'];
			$next_worker=$this->get_refund_next_responsor($line);
			$refund_status_desc =$refund_status_name[$line['STATUS']];
			$total_amount=$line['TOTAL_AMOUNT'];
			$consignee=$line['consignee'];
			$consignee = $this->my_mysql_escape_string($consignee);
			$dist=$line['type'];
			$distributor_id=$line['distributor_id'];

			if($dist=='fenxiao' && is_nise_bunke_distributor_id($line['distributor_id'])){
				$dist='zhixiao';
			}

			$state_tag=0;
			$todo_tag=0;

			if(($line['STATUS']=='RFND_STTS_INIT' || $line['STATUS']=='RFND_STTS_IN_CHECK') && !empty($line['CHECK_DATE_2']) && empty($line['CHECK_DATE_3']) ){
				$state_tag=5;
			}elseif ($line['STATUS']=='RFND_STTS_CHECK_OK') {
				$state_tag=0;//8;
			}elseif(($line['STATUS']=='RFND_STTS_INIT' || $line['STATUS']=='RFND_STTS_IN_CHECK') && !empty($line['CHECK_DATE_1']) && empty($line['CHECK_DATE_2']) ){
				$state_tag=20;
			}elseif(($line['STATUS']=='RFND_STTS_INIT' || $line['STATUS']=='RFND_STTS_IN_CHECK') && empty($line['CHECK_DATE_1']) ){
				$state_tag=19;
			}

			if($state_tag!=0){
				$this->updated_ids['refunds'][]=$refund_id;
			}

			if($taobao_order_sn == null || $taobao_order_sn==''){
				// $this->log('NULL TBSN is ['.$taobao_order_sn.']');
				$taobao_order_sn='*'.$order_id;
				// $this->log('NULL TBSN -> '.$taobao_order_sn);
			}

			$refund_insert_sql="INSERT INTO ecshop.cache_postsale_refund (
				refund_id,
				party_id,
				order_id,
				order_sn,
				taobao_order_sn,
				is_mobile_refund,
				consignee,
				dist,
				begin_time,
				next_worker,
				refund_status_desc,
				total_amount,
				state_tag,
				todo_tag,
				distributor_id,
				cache_time
			) VALUES (
				'{$refund_id}',
				{$party_id},
				{$order_id},
				'{$order_sn}',
				'{$taobao_order_sn}',
				'{$is_mobile_refund}',
				'{$consignee}',
				'{$dist}',
				'{$time_created_duty}',
				'{$next_worker}',
				'{$refund_status_desc}',
				{$total_amount},
				{$state_tag},
				{$todo_tag},
				'{$distributor_id}',
				NOW()
			) ON DUPLICATE KEY UPDATE 
				next_worker=VALUES(next_worker),
				refund_status_desc=VALUES(refund_status_desc),
				total_amount=VALUES(total_amount),
				state_tag=VALUES(state_tag),
				todo_tag=VALUES(todo_tag),
				cache_time=VALUES(cache_time)
			";
			// $this->log($refund_insert_sql);
			$refunds_done=$this->executeMaster($refund_insert_sql);
			// $this->log("REFUND ".$refund_id." executed ".$refunds_done);
		}

		$time_3 = microtime_float();

		//FOURTH - MESSAGES

		$plan_list = array(
		    //'th' => '退货不退款',
		    'tk' => '仅退款',
		    'thtk' => '退货退款',
		    'hh' => '换货',
		    'zh' => '追回',
		    'bj' => '录单补寄',
		    'ms' => '无需处理'
		);

		// $messages=$this->getMessages($parties,$recent_days);
		// $messages_new=$this->getMessages_new($parties,$recent_days);
		// $this->log('old_message: '.json_encode($messages)." new_message: ".json_encode($messages_new)." equal=".(json_encode($messages)==json_encode($messages_new)?'Y':'N'));

		$messages=$this->getMessages_new($parties,$recent_days);

		// $this->log($messages);

		foreach ($messages as $tempindex => $line) {			
			$order_id=$line['order_id'];
			$order_sn=$line['order_sn'];
			$taobao_order_sn=$line['taobao_order_sn'];
			$is_mobile_refund=$line['is_mobile_refund'];
			$party_id=$line['party_id'];
			$consignee=$line['consignee'];
			$consignee = $this->my_mysql_escape_string($consignee);
			$time_created_duty=$line['created_stamp'];
			$program=$line['program'];
			$next_worker=$line['next_process_group'];
			$dist=$line['type'];
			$distributor_id=$line['distributor_id'];

			if($dist=='fenxiao' && is_nise_bunke_distributor_id($line['distributor_id'])){
				$dist='zhixiao';
			}

			$order_status=$line['order_status'];
			$shipping_status=$line['shipping_status'];
			$facility_id=$line['facility_id'];

			$recent_msg_id=$line['sale_support_message_id'];

			$state_tag=0;
			$todo_tag=0;

			if($program == '追回'){
				if($line['order_status']!=2){
					$state_tag=23;
					$todo_tag=23;
				}elseif($line['shipping_status'] != 11){
					$state_tag=24;
					$todo_tag=24;
				}
			}

			if(stristr($next_worker,'KF')){
				$state_tag=12;
			}elseif($next_worker=='DZ'){
				$state_tag=13;
			}elseif($next_worker=='CW'){
				$state_tag=14;
			}elseif(stristr($next_worker, 'WL')){
				$state_tag=15;
			}elseif($next_worker=='CG'){
				$state_tag=16;
			}elseif (empty($next_worker) && empty($program)) {
				$state_tag=21;
			}

			if($state_tag!=0){
				$this->updated_ids['messages'][]=$order_id;
			}

			if($taobao_order_sn == null || $taobao_order_sn==''){
				// $this->log('NULL TBSN is ['.$taobao_order_sn.']');
				$taobao_order_sn='*'.$order_id;
				// $this->log('NULL TBSN -> '.$taobao_order_sn);
			}

			$message_insert_sql="INSERT INTO ecshop.cache_postsale_message (
				order_id,
				order_sn,
				taobao_order_sn,
				is_mobile_refund,
				party_id,
				consignee,
				facility_id,
				dist,
				begin_time,
				program,
				next_worker,
				state_tag,
				todo_tag,
				order_status,
				shipping_status,
				recent_msg_id,
				distributor_id,
				cache_time
			) VALUES (
				{$order_id},
				'{$order_sn}',
				'{$taobao_order_sn}',
				'{$is_mobile_refund}',
				{$party_id},
				'{$consignee}',
				'{$facility_id}',
				'{$dist}',
				'{$time_created_duty}',
				'{$program}',
				'{$next_worker}',
				{$state_tag},
				{$todo_tag},
				{$order_status},
				{$shipping_status},
				{$recent_msg_id},
				'{$distributor_id}',
				NOW()
			) ON DUPLICATE KEY UPDATE
				begin_time=VALUES(begin_time),
				program=VALUES(program),
				next_worker=VALUES(next_worker),
				state_tag=VALUES(state_tag),
				todo_tag=VALUES(todo_tag),
				order_status=VALUES(order_status),
				shipping_status=VALUES(shipping_status),
				recent_msg_id=VALUES(recent_msg_id),
				cache_time=VALUES(cache_time) 
			";
			// $this->log($message_insert_sql);
			$messages_done=$this->executeMaster($message_insert_sql);
			// $this->log("MESSAGE OF ".$order_id." executed ".$messages_done);
		}

		$time_4 = microtime_float();

		//FIFTH REMOVE OLD

		$this->removeOldCache();// I think it is the problem

		$time_5 = microtime_float();

		$this->log("time for GET PARTIES: ".($time_1-$time_0));
		$this->log("time for GET AND UPDATE SERVICES: ".($time_2-$time_1));
		$this->log("time for GET AND UPDATE REFUNDS: ".($time_3-$time_2));
		$this->log("time for GET AND UPDATE MESSAGES: ".($time_4-$time_3));
		$this->log("time for CLEAN OLD CACHE: ".($time_5-$time_4));
		$this->log("time for ALL: ".($time_5-$time_0));
	}

	public function getServices($parties,$recent_days=120){
		$services_sql="SELECT
				o.taobao_order_sn,
				str.is_mobile_refund,
				o.order_id,o.order_sn,
				s.service_id,
				s.service_type,
				s.service_call_status,
				s.service_status,
				s.party_id,
				s.is_complete,
				s.inner_check_status,
				s.outer_check_status,
				s.apply_datetime,
				s.service_pay_status,
				s.back_shipping_status,
				o.consignee,
				o.order_status,
				o.shipping_status,
				o.pay_status,
				o.shipping_id,
				o.shipping_name,
				s.facility_id,
				o.facility_id AS origin_facility_id,
				o.mobile,
				o.tel,
				d.distributor_id,
				md.type
			FROM
				ecshop.service s
			INNER JOIN ecshop.ecs_order_info o ON s.order_id = o.order_id
			LEFT JOIN ecshop.sync_taobao_refund str ON o.taobao_order_sn = str.tid					
			LEFT JOIN ecshop.distributor d ON o.distributor_id = d.distributor_id
			LEFT JOIN ecshop.main_distributor md ON md.main_distributor_id = d.main_distributor_id
			LEFT JOIN romeo.refund r ON r.ORDER_ID = CONVERT(s.back_order_id USING utf8)
            WHERE
                (
                    o.pay_status != '4' 
                    AND r.REFUND_ID is NULL
                ) AND
                s.is_complete = '0' 
                AND (
                    s.inner_check_status=0 OR s.service_call_status!=2
                )
				AND s.party_id in ({$parties})
                AND date_sub(NOW(), INTERVAL {$recent_days} DAY) <= s.apply_datetime -- date(s.apply_datetime)
            ORDER BY s.apply_datetime desc;";

        // $this->log($services_sql);

        $services=$this->queryMaster($services_sql);

        return $services;
	}

	public function getRefunds($parties,$recent_days=120){
		$refund_sql="SELECT
			o.taobao_order_sn,
			str.is_mobile_refund,
			o.order_id,
			o.order_sn,
			r.CUSTOMER_USER_NAME,
			r.`STATUS`,
			r.CREATED_STAMP,
			r.REFUND_ID,
			r.PARTY_ID,
			r.TOTAL_AMOUNT,
			r.CHECK_DATE_1,
			r.CHECK_DATE_2,
			r.CHECK_DATE_3,
			o.pay_status,
			d.distributor_id,
			md.type
		FROM
			romeo.refund r
		LEFT JOIN ecshop.ecs_order_info o ON r.order_id = o.order_id
		LEFT JOIN ecshop.sync_taobao_refund str ON o.taobao_order_sn = str.tid
		LEFT JOIN ecshop.distributor d ON o.distributor_id = d.distributor_id
		LEFT JOIN ecshop.main_distributor md ON md.main_distributor_id = d.main_distributor_id
		WHERE
			o.pay_status != '4'
		AND r.STATUS != 'RFND_STTS_EXECUTED'
		AND r.STATUS != 'RFND_STTS_CANCELED'
		AND r.STATUS != 'RFND_STTS_CHECK_OK'
		AND r.PARTY_ID in ({$parties})
		AND date_sub(NOW(), INTERVAL {$recent_days} DAY)<= r.CREATED_STAMP -- date(r.CREATED_STAMP)
		ORDER BY
			r.CREATED_STAMP DESC";

		// $this->log($refund_sql);

        $refunds=$this->queryMaster($refund_sql);

        return $refunds;
	}

	public function getMessages($parties,$recent_days=120){
		$messages_sql="SELECT
			o.taobao_order_sn,
			str.is_mobile_refund,
			ssm.order_id,
			o.order_sn,
			o.party_id,
			ssm.sale_support_message_id,
			ssm.created_stamp,
			ssm.send_by,
			o.consignee,
			ssm.support_type,
			ssm.program,
			ssm.`STATUS` STATUS,
			ssm.message,
			ssm.next_process_group,
			d.distributor_id,
			md.type,
			o.order_status,
			o.shipping_status,
			o.facility_id
		FROM
			ecshop.sale_support_message ssm
		LEFT JOIN ecshop.ecs_order_info o ON ssm.order_id = o.order_id
		LEFT JOIN ecshop.sync_taobao_refund str ON o.taobao_order_sn = str.tid
		LEFT JOIN ecshop.distributor d ON o.distributor_id = d.distributor_id
		LEFT JOIN ecshop.main_distributor md ON md.main_distributor_id = d.main_distributor_id
		WHERE
			o.party_id IN({$parties})
		AND(
			(
				ssm.next_process_group IS NOT NULL
				AND ssm.next_process_group != ''
			)
			OR(
				ssm.program IS NULL
				OR ssm.program = ''
				OR(
					ssm.program = '追回'
					AND o.shipping_status != 11
				)
			)
		)
		AND ssm.sale_support_message_id IN(
			SELECT
				MAX(
					issm.sale_support_message_id
				)
			FROM
				ecshop.sale_support_message issm
			WHERE
				issm.order_id = ssm.order_id
		)
		AND date_sub(NOW(), INTERVAL {$recent_days} DAY)<= ssm.created_stamp
		-- ORDER BY ssm.sale_support_message_id
		";

		// $this->log($messages_sql);

        $messages=$this->queryMaster($messages_sql);

        return $messages;
	}

	public function getMessages_new($parties,$recent_days=120){
		$messages_sql="SELECT
			o.taobao_order_sn,
			str.is_mobile_refund,
			ssm.order_id,
			o.order_sn,
			o.party_id,
			ssm.sale_support_message_id,
			ssm.created_stamp,
			ssm.send_by,
			o.consignee,
			ssm.support_type,
			ssm.program,
			ssm.`STATUS` STATUS,
			ssm.message,
			ssm.next_process_group,
			d.distributor_id,
			md.type,
			o.order_status,
			o.shipping_status,
			o.facility_id
		FROM
			ecshop.sale_support_message ssm
		INNER JOIN (
			SELECT 
		        order_id, MAX(sale_support_message_id) ssm_id
		    FROM
		        ecshop.sale_support_message
		    WHERE
		        DATE_SUB(NOW(), INTERVAL {$recent_days} DAY) <= created_stamp
		    GROUP BY order_id
		) AS ossm ON ssm.sale_support_message_id = ossm.ssm_id
		LEFT JOIN ecshop.ecs_order_info o ON ssm.order_id = o.order_id
		LEFT JOIN ecshop.sync_taobao_refund str ON o.taobao_order_sn = str.tid
		LEFT JOIN ecshop.distributor d ON o.distributor_id = d.distributor_id
		LEFT JOIN ecshop.main_distributor md ON md.main_distributor_id = d.main_distributor_id
		WHERE
			o.party_id IN({$parties})
		AND(
			(
				ssm.next_process_group IS NOT NULL
				AND ssm.next_process_group != ''
			)
			OR(
				ssm.program IS NULL
				OR ssm.program = ''
				OR(
					ssm.program = '追回'
					AND o.shipping_status != 11
				)
			)
		)
		AND date_sub(NOW(), INTERVAL {$recent_days} DAY)<= ssm.created_stamp
		ORDER BY ssm.sale_support_message_id
		";	
		// $this->log($messages_sql);

    	$messages=$this->queryMaster($messages_sql);
        
		return $messages;
	}



	/*
	新百伦 外包 回访不做就退款
	*/
	private function is_require_service_call_party($party_id){
	    $list = array(
	        '新百伦' => '65585', 
	    );
	    if(in_array($party_id, $list)){
	        return '0';
	    } else{
	        return '1';
	    }
	}

	/*
	售后服务的状态说明文字
	*/
	private function get_service_line_status_description($line){
	    $v=array(
	        "service_type"=>get_service_item_type_name($line['service_type']),
	        "service_status"=>get_service_status_name($line['service_status']),
	        "back_shipping_status"=>get_back_shipping_status_name($line['back_shipping_status']),
	        "outer_check_status"=>get_outer_check_status_name($line['outer_check_status']),
	        "inner_check_status"=>get_inner_check_status_name($line['inner_check_status']),
	        "change_shipping_status"=>get_change_shipping_status_name($line['change_shipping_status']),
	        "service_pay_status"=>get_service_pay_status_name($line['service_pay_status']),
	        "service_call_status"=>get_service_call_status_name($line['service_call_status'])
	    );
	    extract($line);
	    $r="不明";
	    if($service_type==1){
	        //Change
	        if($service_status==0) $r= "换货申请，待审核";
	        if($service_status==1 && $back_shipping_status==0) $r= "已审核，待退货";
	        if($service_status==1 && $back_shipping_status==5) $r= "等待消费者寄回货物 ";
	        if($service_status==1 && $back_shipping_status==12 && $outer_check_status==0 && $inner_check_status==0) $r= "货已收到，待验货";
	        if($outer_check_status==23 || $inner_check_status==32
	            && $service_call_status==1) $r= "验货通过，已入库，待确认换货信息";
	        /*
	        if($outer_check_status==23 || $inner_check_status==32
	            && $service_call_status!=1) return "待物流确认<!--物流：请准备换货发货-->";
	        if($change_shipping_status==52) return "待客服审核<!--客服：退货被原样退回-->";
	        if($change_shipping_status==53) return "待客服审核<!--客服：原样退回件已被顾客查收-->";
	        if($change_shipping_status==42) return "待物流确认<!--物流：准备出库-->";
	        if($change_shipping_status==43) return "待物流确认<!--物流：准备发货-->";
	        if($change_shipping_status==44) return "待客服审核<!--客服：换货已经发货-->";
	        if($change_shipping_status==45) return "待客服审核<!--客服：换货已经签收-->";
	        */
	        if($service_status==2){
	            if (empty($change_order_id)) $r= "待确认换货订单";
	            else $r="换货订单已确认";
	        }
	        if($service_status==3 && $service_call_status!=2) $r= "换货申请被拒待客服回访";
	    } else if ($service_type==2){
	        //Return
	        if($service_status==0) return "退货申请，待审核";
	        if($service_status==1 && $back_shipping_status==0) $r= "已审核，待退货";
	        if($service_status==1 && $back_shipping_status==5) $r= "等待消费者寄回货物 ";
	        if($service_status==1 && $back_shipping_status==12 && $outer_check_status==0 && $inner_check_status==0) $r= "货已收到，待验货";
	        /*
	        if($change_shipping_status==52) return "待客服审核<!--客服：退货被原样退回-->";
	        if($change_shipping_status==53) return "待客服审核<!--客服：原样退回件已被顾客查收-->";
	        */
	        if($outer_check_status==23 || $inner_check_status==32
	            && $service_call_status==1 
	            ) $r= "验货通过，已入库，待确认退款";
	        if($outer_check_status==23 || $inner_check_status==32
	            && $service_call_status==2 && $service_pay_status==0) $r= "退款申请待审核";
	        if($service_pay_status==2) $r= "退款信息已确认，待退款";
	        /*
	        if($service_pay_status==4) return "待客服审核<!--客服：退款确认完成-->";
	        */
	        if($service_status==2) $r= "退货完毕待确认退款申请";
	        if($service_status==3 && $service_call_status!=2) $r= "退货申请被拒待客服回访";
	        
	    }
	    return $r;
	}

	/*
	待客服审核
	待物流确认
	待运营确认
	待客服建立退款申请
	待客服建立退货申请
	待客服建立换货申请
	待录单/补寄
	*/

	/*
	为售后服务寻找责任方
	*/
	private function get_service_next_responsor($line){
	    extract($line);
	    $r='';
	    if($service_type==1){
	        //Change
	        if($service_status==0) return "待客服和店长审核";//<!--客服和店长：确认是否同意换货要求-->
	        if($service_status==1 && $back_shipping_status==0) return "待物流确认";//<!--物流：准备接收退回的货物-->
	        if($service_status==1 && $back_shipping_status==5) return "等待消费者寄回货物";//<!--物流：准备接收退回的货物-->
	        if($service_status==1 && $back_shipping_status==12 && $outer_check_status==0 && $inner_check_status==0) return "待物流确认";//<!--物流：准备验货-->
	        if($outer_check_status==23 || $inner_check_status==32
	            && ('1'!=$this->is_require_service_call_party($line['party_id']) || $service_call_status==1)
	            && empty($change_order_id)) return "待客服确认换货订单";//<!--客服：完成退回，待确认意向，可建立换货申请-->
	        if($outer_check_status==23 || $inner_check_status==32
	            && ('1'!==$this->is_require_service_call_party($line['party_id']) || $service_call_status!=1)) return "待物流确认";//<!--物流：请准备换货发货-->
	        if($change_shipping_status==52) return "待客服审核";//<!--客服：退货被原样退回-->
	        if($change_shipping_status==53) return "待客服审核";//<!--客服：原样退回件已被顾客查收-->
	        if($change_shipping_status==42) return "待物流确认";//<!--物流：准备出库-->
	        if($change_shipping_status==43) return "待物流确认";//<!--物流：准备发货-->
	        if($change_shipping_status==44) return "待客服审核";//<!--客服：换货已经发货-->
	        if($change_shipping_status==45) return "待客服审核";//<!--客服：换货已经签收-->
	        if($service_status==3 && ('1'!==$this->is_require_service_call_party($line['party_id']) || $service_call_status!=2)) return "待客服审核";//<!--客服：换货审核未通过，需要回访-->
	    } else if ($service_type==2){
	        //Return
	        if($service_status==0) return "待客服和店长审核";//<!--客服和店长：确认是否同意退货要求-->
	        if($service_status==1 && $back_shipping_status==0) return "待物流确认";//<!--物流：准备接收退回的货物-->
	        if($service_status==1 && $back_shipping_status==5) return "等待消费者寄回货物";//<!--物流：准备接收退回的货物-->
	        if($service_status==1 && $back_shipping_status==12 && $outer_check_status==0 && $inner_check_status==0) return "待物流确认";//<!--物流：准备验货-->
	        if($change_shipping_status==52) return "待客服审核";//<!--客服：退货被原样退回-->
	        if($change_shipping_status==53) return "待客服审核";//<!--客服：原样退回件已被顾客查收-->
	        if($outer_check_status==23 || $inner_check_status==32
	            && ('1'!==$this->is_require_service_call_party($line['party_id']) || $service_call_status==1) 
	            ) return "待客服建立退款申请";//<!--客服：完成退回，待确认意向，可建立退货申请-->
	        if($outer_check_status==23 || $inner_check_status==32
	            && ('1'!==$this->is_require_service_call_party($line['party_id']) || $service_call_status==2) && $service_pay_status==0) return "待物流和财务确认";//<!--物流和财务：请确认退货退款申请-->
	        if($service_pay_status==2) return "待客服审核";//<!--客服：已退款请确认-->
	        if($service_pay_status==4) return "待客服审核";//<!--客服：退款确认完成-->
	        if($service_status==3 && ('1'!==$this->is_require_service_call_party($line['party_id']) || $service_call_status!=2)) return "待客服审核";//<!--客服：退货审核未通过，需要回访-->
	    }
	    return "客服核查流程异常";

	}

	/*
	为未完结退款申请查找责任方
	*/
	private function get_refund_next_responsor($line){
	    extract($line);
	    switch($STATUS){
	        case 'RFND_STTS_INIT':
	        case 'RFND_STTS_IN_CHECK':
	            if(empty($CHECK_DATE_1)) return "客服：审核退款申请";
	            else if (empty($CHECK_DATE_2)) return "物流：审核退款申请";
	            else if (empty($CHECK_DATE_3)) return "财务：审核退款申请";
	            break;
	        case 'RFND_STTS_CHECK_OK':
	            if(empty($EXECUTE_DATE))return "财务：执行退款申请";
	            else return "警告：未执行状态有执行时间记录";
	            break;
	        case 'RFND_STTS_EXECUTED':
	            return "此退款申请已经被执行。";
	            break;
	        case 'RFND_STTS_CANCELED':
	            return "此退款申请已经被取消。";
	            break;
	    }
	    return "不知道该找谁干活T_T";
	}

	private function removeOldCache(){

		if($this->updated_ids){
			if($this->updated_ids['services'] && count($this->updated_ids['services'])){
				$sids=implode(',', $this->updated_ids['services']);
				$end_sql="update ecshop.cache_postsale_service SET state_tag=0 where service_id not in ({$sids}) and state_tag!=0 and date_sub(NOW(), INTERVAL 5 MINUTE) >= date(cache_time)";
				$services_done=$this->executeMaster($end_sql);	
				$this->log("Services updated ".count($this->updated_ids['services']));			
			}
			if($this->updated_ids['refunds'] && count($this->updated_ids['refunds'])){
				$rids=implode(',', $this->updated_ids['refunds']);
				$end_sql="update ecshop.cache_postsale_refund SET state_tag=0 where refund_id not in ({$rids}) and state_tag!=0 and date_sub(NOW(), INTERVAL 5 MINUTE) >= date(cache_time)";
				$refunds_done=$this->executeMaster($end_sql);	
				$this->log("Refunds updated ".count($this->updated_ids['refunds']));
			}
			if($this->updated_ids['messages'] && count($this->updated_ids['messages'])){
				$oids=implode(',', $this->updated_ids['messages']);
				$end_sql="update ecshop.cache_postsale_message SET state_tag=0 where order_id not in ({$oids}) and state_tag!=0 and date_sub(NOW(), INTERVAL 5 MINUTE) >= date(cache_time)";
				$messages_done=$this->executeMaster($end_sql);	
				$this->log("Messages updated ".count($this->updated_ids['messages']));
			}
			$this->log("OLD SERVICE REMOVE executed ".$services_done);
			$this->log("OLD REFUND REMOVE executed ".$refunds_done);
			$this->log("OLD MESSAGE REMOVE executed ".$messages_done);
		}

		
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
			// foreach ($m as $key => $value) {
			// 	print "Array[".$key."]=".$value." \r\n";
			// }
			print "Json of array is " . json_encode($m) . " \r\n";
		}else{
			print date("Y-m-d H:i:s") . " " . $m . " \r\n";
		}
	}
/*
	protected function sendMail($subject, $body, $path=null, $file_name=null) {
        require_once(ROOT_PATH. 'includes/helper/mail.php');
        $mail=Helper_Mail::smtp();
        $mail->IsSMTP();                 // 启用SMTP
        $mail->Host="smtp.exmail.qq.com";      //smtp服务器 以126邮箱为例子
        $mail->SMTPAuth = true;         //启用smtp认证
        $mail->Username =$GLOBALS['emailUsername'];   // 你的邮箱地址
        $mail->Password = $GLOBALS['emailPassword'];      //你的邮箱密码 
        $mail->CharSet='UTF-8';
        $mail->Subject=$subject;
        $mail->SetFrom('erp@leqee.com', '乐其网络科技');
        $mail->AddAddress('ljni@leqee.com', '倪李俊');
        $mail->Body = date("Y-m-d H:i:s") . " " . $body;
        if($path != null && $file_name != null){
            $mail->AddAttachment($path, $file_name);
        }
        try {
            if ($mail->Send()) {
                LogRecord('mail send sucess ');
            } else {
                LogRecord('mail send fail ');
            }
        } catch(Exception $e) {
            // 屏蔽PHP邮箱 版本错误
            //Deprecated: Function set_magic_quotes_runtime() is deprecated in /mnt/hgfs/www/erpbrand/includes/phpmailer/class.phpmailer.php on line 1471 Deprecated: Function set_magic_quotes_runtime() is deprecated in /mnt/hgfs/www/erpbrand/includes/phpmailer/class.phpmailer.php on line 1475  Deprecated: Function set_magic_quotes_runtime() is deprecated in /mnt/hgfs/www/erpbrand/includes/phpmailer/class.phpmailer.php on line 1471 Deprecated: Function set_magic_quotes_runtime() is deprecated in /mnt/hgfs/www/erpbrand/includes/phpmailer/class.phpmailer.php on line 1475 
        }
    }
*/
    private function my_mysql_escape_string($v){
    	return mysql_escape_string($v);
    	// return urlencode($v);
    }
}


?>