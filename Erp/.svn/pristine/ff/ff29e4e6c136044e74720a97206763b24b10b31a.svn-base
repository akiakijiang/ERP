<?php
require_once('init.php');
// require_once (ROOT_PATH . 'includes/debug/lib_log.php');

/**
* 售后缓存更新用的各种东西。SINRI EDOGAWA，20141016
*/

function POSTSALE_CACHE_updateService($parties=null,$recent_days=120,$service_id=null){
	$services=POSTSALE_CACHE_getServices($parties,$recent_days,$service_id);

	$target_ended=$service_id;

	foreach ($services as $tempindex => $line) {
			$service_id=$line['service_id'];
			$party_id=$line['party_id'];
			$order_id=$line['order_id'];
			$order_sn=$line['order_sn'];
			$taobao_order_sn=$line['taobao_order_sn'];
			$goods_on_back_way=($line['back_shipping_status'] == 5)?1:0;
			$next_worker=get_service_next_responsor($line);
			$time_created_duty=$line['apply_datetime'];
			$handle_facility_id=$line['facility_id'];
			$origin_facility_id=$line['origin_facility_id'];
			$service_type=$line['service_type'];
			$service_status_desc=POSTSALE_CACHE_get_service_line_status_description($line);
			$consignee=$line['consignee'];
			$tel=mysql_escape_string($line['tel']);
			$mobile=mysql_escape_string($line['mobile']);
			$dist=$line['type'];
			$distributor_id=$line['distributor_id'];
			$is_mobile_refund=$line['is_mobile_refund'];

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

			if($taobao_order_sn == null || $taobao_order_sn==''){
				// $this->log('NULL TBSN is ['.$taobao_order_sn.']');
				$taobao_order_sn='*'.$order_id;
				// $this->log('NULL TBSN -> '.$taobao_order_sn);
			}
			$consignee=mysql_escape_string($consignee);
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
			//$services_done=$this->executeMaster($sql_insert);
			// $this->log("SERVICE ".$service_id." executed ".$services_done);

		global $db;
		$db->exec($sql_insert);
		
		if($service_id==$target_ended){
			$target_ended=null;
		}
	}
	if($target_ended){
		$end_sql="update ecshop.cache_postsale_service SET state_tag=0, cache_time=NOW() where service_id={$target_ended}";
		global $db;
		$db->exec($end_sql);
	}
}


function POSTSALE_CACHE_getServices($parties=null,$recent_days=120,$service_id=null){
	if(!empty($parties))$parties_condition=" AND s.party_id IN ({$parties}) ";
	if(!empty($service_id))$only_one_condition=" AND s.service_id={$service_id} ";
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
			o.distributor_id,
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
			{$only_one_condition}
			{$parties_condition}
            AND date_sub(NOW(), INTERVAL {$recent_days} DAY) <= s.apply_datetime -- date(s.apply_datetime)
        ORDER BY s.apply_datetime desc;";

    // $this->log($services_sql);

    //$services=$this->queryMaster($services_sql);
    global $db;
    $services=$db->getAll($services_sql);


    return $services;
}

function POSTSALE_CACHE_updateRefunds($parties=null,$recent_days=120,$refund_id=null){
	$refunds=POSTSALE_CACHE_getRefunds($parties,$recent_days,$refund_id);

	// 退款状态
	$refund_status_name=array(
	    'RFND_STTS_INIT'=>"已生成",
	    'RFND_STTS_IN_CHECK'=>"处理中",
	    'RFND_STTS_CHECK_OK'=>"已审毕",
	    'RFND_STTS_EXECUTED'=>"已完成",
	    'RFND_STTS_CANCELED'=>"已取消"
	);

	$target_ended=$refund_id;

	foreach ($refunds as $tempindex => $line) {
		$refund_id=$line['REFUND_ID'];
		$party_id=$line['PARTY_ID'];
		$order_id=$line['order_id'];
		$order_sn=$line['order_sn'];
		$taobao_order_sn=$line['taobao_order_sn'];
		$is_mobile_refund=$line['is_mobile_refund'];
		$time_created_duty=$line['CREATED_STAMP'];
		$next_worker=get_refund_next_responsor($line);
		$refund_status_desc =$refund_status_name[$line['STATUS']];
		$total_amount=$line['TOTAL_AMOUNT'];
		$distributor_id=$line['distributor_id'];
		
		 $consignee=$line['consignee'];
		
		// 如果收件人包含有单引号
       $consignee=mysql_escape_string($consignee);
       
       
       
		$dist=$line['type'];

		if($dist=='fenxiao' && is_nise_bunke_distributor_id($line['distributor_id'])){
			$dist='zhixiao';
		}

		$state_tag=0;
		$todo_tag=0;


		if(($line['STATUS']=='RFND_STTS_INIT' || $line['STATUS']=='RFND_STTS_IN_CHECK') && !empty($line['CHECK_DATE_2']) && empty($line['CHECK_DATE_3']) ){
			$state_tag=5;
		}elseif ($line['STATUS']=='RFND_STTS_CHECK_OK' && $line['STATUS']=='RFND_STTS_CHECK_OK') {
			$state_tag=8;
		}elseif(($line['STATUS']=='RFND_STTS_INIT' || $line['STATUS']=='RFND_STTS_IN_CHECK') && !empty($line['CHECK_DATE_1']) && empty($line['CHECK_DATE_2']) ){
			$state_tag=20;
		}elseif(($line['STATUS']=='RFND_STTS_INIT' || $line['STATUS']=='RFND_STTS_IN_CHECK') && empty($line['CHECK_DATE_1']) ){
			$state_tag=19;
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
		//$refunds_done=$this->executeMaster($refund_insert_sql);
		// $this->log("REFUND ".$refund_id." executed ".$refunds_done);

		global $db;
		$db->exec($refund_insert_sql);
		
		if($refund_id==$target_ended){
			$target_ended=null;
		}
	}
	if($target_ended){
		$end_sql="update ecshop.cache_postsale_refund SET state_tag=0, cache_time=NOW() where refund_id='{$target_ended}'";
		global $db;
		$db->exec($end_sql);
	}
}

function POSTSALE_CACHE_getRefunds($parties=null,$recent_days=120,$refund_id=null){
	if(!empty($parties))$parties_condition=" AND r.PARTY_ID IN ({$parties}) ";
	if(!empty($refund_id))$only_one_condition=" AND r.REFUND_ID='{$refund_id}' ";
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
		o.distributor_id,
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
	{$only_one_condition}
	{$parties_condition}
	AND date_sub(NOW(), INTERVAL {$recent_days} DAY)<= r.CREATED_STAMP -- date(r.CREATED_STAMP)
	ORDER BY
		r.CREATED_STAMP DESC";

	// $this->log($refund_sql);

    //$refunds=$this->queryMaster($refund_sql);
	global $db;
	$refunds=$db->getAll($refund_sql);

    return $refunds;
}

function POSTSALE_CACHE_updateMessages($parties=null,$recent_days=120,$order_id=null){
	$messages=POSTSALE_CACHE_getMessages($parties,$recent_days,$order_id);

	$target_ended=$order_id;
	foreach ($messages as $tempindex => $line) {			
		$order_id=$line['order_id'];
		$order_sn=$line['order_sn'];
		$taobao_order_sn=$line['taobao_order_sn'];
		$party_id=$line['party_id'];
		$consignee=$line['consignee'];
		$consignee=mysql_escape_string($consignee);
		$time_created_duty=$line['created_stamp'];
		$program=$line['program'];
		$next_worker=$line['next_process_group'];
		$dist=$line['type'];
		$distributor_id=$line['distributor_id'];
		$is_mobile_refund=$line['is_mobile_refund'];

		if($dist=='fenxiao' && is_nise_bunke_distributor_id($line['distributor_id'])){
			$dist='zhixiao';
		}

		$order_status=$line['order_status'];
		$shipping_status=$line['shipping_status'];
		$facility_id=$line['facility_id'];

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

		if($taobao_order_sn == null || $taobao_order_sn==''){
			// $this->log('NULL TBSN is ['.$taobao_order_sn.']');
			$taobao_order_sn='*'.$order_id;
			// $this->log('NULL TBSN -> '.$taobao_order_sn);
		}

		$recent_msg_id=$line['sale_support_message_id'];

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
			'{$order_id}',
			'{$order_sn}',
			'{$taobao_order_sn}',
			'{$is_mobile_refund}',
			'{$party_id}',
			'{$consignee}',
			'{$facility_id}',
			'{$dist}',
			'{$time_created_duty}',
			'{$program}',
			'{$next_worker}',
			'{$state_tag}',
			'{$todo_tag}',
			'{$order_status}',
			'{$shipping_status}',
             '{$recent_msg_id}' ,
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


		global $db;
		$db->exec($message_insert_sql);
		if($order_id==$target_ended){
			$target_ended=null;
		}
	}
	if($target_ended){
		$end_sql="update ecshop.cache_postsale_message SET state_tag=0, cache_time=NOW() where order_id={$target_ended}";
		global $db;
		$db->exec($end_sql);
		// QLog::log("message finish sql is [ $end_sql ] ");
	}
}

function POSTSALE_CACHE_getMessages($parties=null,$recent_days=120,$order_id=null){
	if(!empty($parties))$parties_condition=" AND o.party_id IN ({$parties}) ";
	if(!empty($order_id))$only_one_condition=" AND ssm.order_id={$order_id} ";
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
		o.distributor_id,
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
		1
	{$parties_condition}
	{$only_one_condition}
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
	AND date_sub(NOW(), INTERVAL {$recent_days} DAY)<= ssm.created_stamp;
	";

	// $this->log($messages_sql);

    global $slave_db;
    $messages=$slave_db->getAll($messages_sql);
    // QLog::log("The message search sql is [ $messages_sql ]");
    // QLog::log("The result is ".json_encode($messages));



    return $messages;
}

//////////////////////////////////////////////////////

/*
新百伦 外包 回访不做就退款
*/
function POSTSALE_CACHE_is_require_service_call_party($party_id){
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
function POSTSALE_CACHE_get_service_line_status_description($line){
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
function get_service_next_responsor($line){
    extract($line);
    $r='';
    if($service_type==1){
        //Change
        if($service_status==0) return "待客服和店长审核";//<!--客服和店长：确认是否同意换货要求-->
        if($service_status==1 && $back_shipping_status==0) return "待物流确认";//<!--物流：准备接收退回的货物-->
        if($service_status==1 && $back_shipping_status==5) return "等待消费者寄回货物";//<!--物流：准备接收退回的货物-->
        if($service_status==1 && $back_shipping_status==12 && $outer_check_status==0 && $inner_check_status==0) return "待物流确认";//<!--物流：准备验货-->
        if($outer_check_status==23 || $inner_check_status==32
            && ('1'!=POSTSALE_CACHE_is_require_service_call_party($line['party_id']) || $service_call_status==1)
            && empty($change_order_id)) return "待客服确认换货订单";//<!--客服：完成退回，待确认意向，可建立换货申请-->
        if($outer_check_status==23 || $inner_check_status==32
            && ('1'!==POSTSALE_CACHE_is_require_service_call_party($line['party_id']) || $service_call_status!=1)) return "待物流确认";//<!--物流：请准备换货发货-->
        if($change_shipping_status==52) return "待客服审核";//<!--客服：退货被原样退回-->
        if($change_shipping_status==53) return "待客服审核";//<!--客服：原样退回件已被顾客查收-->
        if($change_shipping_status==42) return "待物流确认";//<!--物流：准备出库-->
        if($change_shipping_status==43) return "待物流确认";//<!--物流：准备发货-->
        if($change_shipping_status==44) return "待客服审核";//<!--客服：换货已经发货-->
        if($change_shipping_status==45) return "待客服审核";//<!--客服：换货已经签收-->
        if($service_status==3 && ('1'!==POSTSALE_CACHE_is_require_service_call_party($line['party_id']) || $service_call_status!=2)) return "待客服审核";//<!--客服：换货审核未通过，需要回访-->
    } else if ($service_type==2){
        //Return
        if($service_status==0) return "待客服和店长审核";//<!--客服和店长：确认是否同意退货要求-->
        if($service_status==1 && $back_shipping_status==0) return "待物流确认";//<!--物流：准备接收退回的货物-->
        if($service_status==1 && $back_shipping_status==5) return "等待消费者寄回货物";//<!--物流：准备接收退回的货物-->
        if($service_status==1 && $back_shipping_status==12 && $outer_check_status==0 && $inner_check_status==0) return "待物流确认";//<!--物流：准备验货-->
        if($change_shipping_status==52) return "待客服审核";//<!--客服：退货被原样退回-->
        if($change_shipping_status==53) return "待客服审核";//<!--客服：原样退回件已被顾客查收-->
        if($outer_check_status==23 || $inner_check_status==32
            && ('1'!==POSTSALE_CACHE_is_require_service_call_party($line['party_id']) || $service_call_status==1) 
            ) return "待客服建立退款申请";//<!--客服：完成退回，待确认意向，可建立退货申请-->
        if($outer_check_status==23 || $inner_check_status==32
            && ('1'!==POSTSALE_CACHE_is_require_service_call_party($line['party_id']) || $service_call_status==2) && $service_pay_status==0) return "待物流和财务确认";//<!--物流和财务：请确认退货退款申请-->
        if($service_pay_status==2) return "待客服审核";//<!--客服：已退款请确认-->
        if($service_pay_status==4) return "待客服审核";//<!--客服：退款确认完成-->
        if($service_status==3 && ('1'!==POSTSALE_CACHE_is_require_service_call_party($line['party_id']) || $service_call_status!=2)) return "待客服审核";//<!--客服：退货审核未通过，需要回访-->
    }
    return "客服核查流程异常";

}

/*
为未完结退款申请查找责任方
*/
function get_refund_next_responsor($line){
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

?>