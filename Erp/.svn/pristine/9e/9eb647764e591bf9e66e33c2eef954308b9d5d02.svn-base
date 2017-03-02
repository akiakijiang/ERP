<?php

define('IN_ECS', true);
require_once('../includes/init.php');
require_once('../function.php');
require_once (ROOT_PATH . 'includes/lib_service.php');

require_once('./postsale_function.php');

//TEST
//require_once (ROOT_PATH . 'protected/commands/PostsaleCacheCommand.php');

function Index_Librorum_Prohibitorum($the_plus_condition,$OFFSET=0,$use_role,&$countArray,&$db_time){
	
	global $db;
    global $user_priv_list;
    global $sale_support_type_map;
    global $get_sync_taobao_refund_state_map;
    global $get_sync_tmall_refund_state_map;
    global $get_sync_taobao_fenxiao_refund_state_map;
    global $refund_status_name;

    $db_time=array();

	$cg_ex_mode_array="21,13,12,2,3,1,4,19,10,11,9,14,5,8,17,18,15,6,22,7,20,23,24";
	$wl_ex_mode_array="21,13,12,2,3,1,4,19,10,11,9,14,5,8,17,18,16,23";
	$cw_ex_mode_array="21,13,12,2,3,1,4,19,10,11,9,15,6,22,7,20,16,23,24";
	$kf_ex_mode_array="21,13,14,5,8,17,18,15,6,22,7,20,16,24";
	$dz_ex_mode_array="21,12,2,3,1,4,19,10,11,9,14,5,8,17,18,15,6,22,7,20,16,23,24";

    $party_ids=get_party_with_all_children($_SESSION['party_id']);
	$party_ids=implode(',', $party_ids);

    $str_user_facilities=$_SESSION['facility_id'];
    if($use_role=='logistics')$str_user_facilities=preg_replace('/76161272/', '-76161272', $str_user_facilities);
    $user_facilities=explode(',', $str_user_facilities);

    $page_size=50;
    $page_offset=0;
    if($the_plus_condition['OFFSET']>0)$page_offset=$the_plus_condition['OFFSET'];
    if($the_plus_condition['LIMIT']>0)$page_size=$the_plus_condition['LIMIT'];
    $sql_paging=" LIMIT ".$page_size." OFFSET ".($page_size*$page_offset);


    //TAOBAO
    $conditions_tr=" AND str.party_id in ({$party_ids}) ";
    if($the_plus_condition['mode']!=0){
        $conditions_tr.=get_sync_taobao_refund_mode_condition($the_plus_condition['mode']);
    }
    if($use_role!='viewer' && $use_role!='postsale' && $use_role!='finance'){
        $conditions_tr.=" AND 0 ";
    }
    foreach ($the_plus_condition as $key => $value) {
        switch ($key) {
            case 'order_sn':
                //do filter later
                break;
            case 'taobao_order_sn':
                $conditions_tr.=" AND str.tid like '{$value}%' ";
                break;
            case 'taobao_refund_id':
                $conditions_tr.=" AND str.refund_id = '$value' ";
                break;
            case 'taobao_buyer_nick':
                $conditions_tr.=" AND str.buyer_nick = '$value' ";
                break;
            case 'buyer_name':
                //do filter later
                break;
            case 'mobile':
                //do filter later
                break;
            case 'track_number':
                //do filter later
                break;
            case 'return_track_number':
                //$conditions_tr.=" AND str.sid = '$value' ";
                break;
            case 'date_start':
                $conditions_tr.=" AND str.created>='$value' ";
                break;
            case 'date_end':
                $conditions_tr.=" AND str.created<='$value' ";
                break;
        }
    }

    $ts=microtime(true);

    $sync_taobao_refunds=get_sync_taobao_refund_waiting_lines(
        $party_ids,//$the_plus_condition['party_id'],
        $the_plus_condition['OFFSET'],
        $the_plus_condition['dist'],
        
        $the_plus_condition['distributor'],
        $the_plus_condition['refund_type'],
        $conditions_tr
    );


    $te=microtime(true);
    $db_time['sync_taobao_refunds']=$te-$ts;//(number_format(($debug_time_list[1]-$debug_time_list[0]), 4, '.', '')).'s '

    //TMALL
    $ts=microtime(true);
    $sync_tmall_refunds=get_sync_tmall_refund_waiting_lines(
        $party_ids,//$the_plus_condition['party_id'],
        $the_plus_condition['OFFSET'],
        $the_plus_condition['dist'],
        $the_plus_condition['distributor'],
        $conditions_tr
    );
    $te=microtime(true);
    $db_time['sync_tmall_refunds']=$te-$ts;


	foreach ($sync_tmall_refunds as $key => $value) {
        $sync_tmall_refunds[$key]['TAOBAO_TASK_TYPE']='tmall';
    }
    foreach ($sync_taobao_refunds['zhixiao'] as $key => $value) {
        $sync_taobao_refunds['zhixiao'][$key]['TAOBAO_TASK_TYPE']='taobao';
    }
    foreach ($sync_taobao_refunds['fenxiao'] as $key => $value) {
        $sync_taobao_refunds['fenxiao'][$key]['TAOBAO_TASK_TYPE']='taobao_fenxiao';
    }
    

    $taobao_tasks=array_merge($sync_taobao_refunds['zhixiao'],$sync_taobao_refunds['fenxiao'], $sync_tmall_refunds);


    //SERVICE
    $conditions_tr=" AND s.party_id in ({$party_ids}) and s.state_tag!=0 ";
    if($use_role=='logistics')$conditions_tr.=" AND (s.origin_facility_id in ($str_user_facilities) OR s.handle_facility_id in ($str_user_facilities) ) ";
    if($the_plus_condition['mode']!=0){
        //$conditions_tr.=get_conditions_for_mode($the_plus_condition['mode']);
        $conditions_tr.=" AND s.state_tag=".$the_plus_condition['mode']." ";
    }
    foreach ($the_plus_condition as $key => $value) {
        switch ($key) {
            case 'taobao_order_sn':
                $conditions_tr.=" AND POSITION('$value' IN s.taobao_order_sn) ";
                break;
            case 'order_sn':
                $conditions_tr.=" AND s.order_sn = '$value' ";
                break;
            case 'order_id':
                $conditions_tr.=" AND s.order_id = '$value' ";
                break;
            case 'buyer_name':
                $conditions_tr.=" AND s.consignee = '$value' ";
                break;
            case 'mobile':
                $conditions_tr.=" AND ( s.mobile = '$value' OR s.tel = '$value' ) ";
                break;
            case 'track_number':
            	$conditions_tr.=" AND 0 ";
                //$conditions_tr.=" AND ( cb.bill_no = '$value' ) ";
                break;
            case 'date_start':
                $conditions_tr.=" AND s.begin_time>='$value' ";
                break;
            case 'date_end':
                $conditions_tr.=" AND s.begin_time<='$value' ";
                break;
            case 'return_track_number':
                $conditions_tr.=" AND  0 ";
                break;
            case 'dist':
            	if($value=='zhixiao' || $value=='fenxiao'){
            		$conditions_tr.=" AND s.dist = '".$value."' ";
            	}
            	break;
            case 'distributor':
	            if($the_plus_condition['distributor'] != '-1') {
			    	$conditions_tr.=" AND s.distributor_id ='".$the_plus_condition['distributor']."' ";
			    }
			    break;
            case 'is_soon_or_pending':
            	if($use_role=='logistics'){
            		if($value=='is_pending'){
            			$conditions_tr.=" AND s.is_waiting_back=1 ";
            		}else{
            			$conditions_tr.=" AND s.is_waiting_back=0 ";
            		}
            	}
            	break;
        	case 'refund_type':
	        	if($the_plus_condition['refund_type']=='mobile' || $the_plus_condition['refund_type']=='notMoble'){
            		$conditions_tr.=" AND s.is_mobile_refund  = '".$the_plus_condition['refund_type']."' ";
            	}
	            break;
        }
    }

    if($use_role == 'logistics'){
    	$conditions_tr.= " AND s.state_tag not in ({$wl_ex_mode_array}) ";
    }elseif ($use_role == 'postsale') {
    	$conditions_tr.=" AND s.state_tag not in ({$kf_ex_mode_array}) ";
    }elseif ($use_role == 'finance') {
    	$conditions_tr.=" AND s.state_tag not in ({$cw_ex_mode_array}) ";
    }elseif ($use_role == 'shop') {
    	$conditions_tr.=" AND s.state_tag not in ({$dz_ex_mode_array}) ";
    }elseif ($use_role == 'cg') {
    	$conditions_tr.=" AND s.state_tag not in ({$cg_ex_mode_array}) ";
    }

    //$conditions_tr.=' AND date_sub(curdate(), INTERVAL 30 MINUTE) <= date(s.cache_time) ';

    $ts=microtime(true);

    $sql="SELECT
        p. NAME,
        s.*
    FROM
        ecshop.cache_postsale_service s
    LEFT JOIN romeo.party p ON CONVERT(s.party_id USING utf8)= p.party_id
    where 1 {$conditions_tr} {$sql_paging}
    ";//蓝屏大神的SQL太可怕默默改掉
     //echo "services SQL:".$sql;
    $services=$db->getAll($sql);

    $count_sql="SELECT count(1) from ecshop.cache_postsale_service s where 1 {$conditions_tr}";
    $countArray['services']=$db->getOne($count_sql);

    $te=microtime(true);
    $db_time['cache_postsale_service']=$te-$ts;

    //REFUND
    $conditions_tr=" AND r.party_id in ({$party_ids}) and r.state_tag!=0  ";
    //if($use_role=='logistics')$conditions_tr.=" AND (o.facility_id in ($str_user_facilities)) ";
    if($the_plus_condition['mode']!=0){
        //$conditions_tr.=get_uncompleted_refunds_mode_condition($the_plus_condition['mode']);
        $conditions_tr.=" and r.state_tag=".$the_plus_condition['mode']." ";
    }
    foreach ($the_plus_condition as $key => $value) {
        switch ($key) {
            case 'taobao_order_sn':
                $conditions_tr.=" AND POSITION('$value' IN r.taobao_order_sn) ";
                break;
            case 'order_sn':
                $conditions_tr.=" AND r.order_sn = '$value' ";
                break;
            case 'order_id':
                $conditions_tr.=" AND r.order_id = '$value' ";
                break;
            case 'buyer_name':
                $conditions_tr.=" AND r.consignee = '$value' ";
                break;
            case 'mobile':
            	$conditions_tr.=" AND 0 ";
                //$conditions_tr.=" AND ( o.mobile = '$value' OR o.tel = '$value' ) ";
                break;
            case 'date_start':
                $conditions_tr.=" AND r.begin_time>='$value' ";
                break;
            case 'date_end':
                $conditions_tr.=" AND r.begin_time<='$value' ";
                break;
            case 'track_number':
            case 'return_track_number':
                $conditions_tr.=" AND  0 ";
                break;
            case 'dist':
            	if($value=='zhixiao' || $value=='fenxiao'){
            		$conditions_tr.=" AND r.dist = '".$value."' ";
            	}
            	break;
           case 'distributor':
	            if($the_plus_condition['distributor'] != '-1') {
			    	$conditions_tr.=" AND r.distributor_id ='".$the_plus_condition['distributor']."' ";
			    }
			    break;      
        	case 'refund_type':
	        	if($the_plus_condition['refund_type']=='mobile' || $the_plus_condition['refund_type']=='notMoble'){
            		$conditions_tr.=" AND r.is_mobile_refund  = '".$the_plus_condition['refund_type']."' ";
            	}
	            break;
        }
    }
    
    if($use_role == 'logistics'){
    	$conditions_tr.= " AND r.state_tag not in ({$wl_ex_mode_array}) ";
    }elseif ($use_role == 'postsale') {
    	$conditions_tr.=" AND r.state_tag not in ({$kf_ex_mode_array}) ";
    }elseif ($use_role == 'finance') {
    	$conditions_tr.=" AND r.state_tag not in ({$cw_ex_mode_array}) ";
    }elseif ($use_role == 'shop') {
    	$conditions_tr.=" AND r.state_tag not in ({$dz_ex_mode_array}) ";
    }elseif ($use_role == 'cg') {
    	$conditions_tr.=" AND r.state_tag not in ({$cg_ex_mode_array}) ";
    }

    //$conditions_tr.=' AND date_sub(curdate(), INTERVAL 30 MINUTE) <= date(r.cache_time) ';

    $ts=microtime(true);

    $sql="SELECT * from ecshop.cache_postsale_refund r where 1 {$conditions_tr} {$sql_paging}"; 
    //echo "refunds SQL:".$sql;
    $refunds=$db->getAll($sql);

    $count_sql="SELECT count(1) from ecshop.cache_postsale_refund r where 1 {$conditions_tr}";
    $countArray['refunds']=$db->getOne($count_sql);

    $te=microtime(true);
    $db_time['cache_postsale_refund']=$te-$ts;

    //MESSAGE
    $conditions_tr=" AND m.party_id in ({$party_ids}) and m.state_tag!=0  ";
    if($the_plus_condition['mode']!=0){
        //$conditions_tr=get_conditions_message_mode($the_plus_condition['mode']);
        if($the_plus_condition['mode']==23 || $the_plus_condition['mode']==24){
        	$conditions_tr.=" and m.todo_tag = ".$the_plus_condition['mode']." and m.state_tag!=0 ";
        }else{
        	$conditions_tr.=" and m.state_tag = ".$the_plus_condition['mode']." ";
        }
    }
    foreach ($the_plus_condition as $key => $value) {
        switch ($key) {
            case 'taobao_order_sn':
                $conditions_tr.=" AND POSITION('$value' IN m.taobao_order_sn) ";
                break;
            case 'order_sn':
                $conditions_tr.=" AND m.order_sn = '$value' ";
                break;
            case 'order_id':
                $conditions_tr.=" AND m.order_id = '$value' ";
                break;
            case 'buyer_name':
                $conditions_tr.=" AND m.consignee = '$value' ";
                break;
            case 'mobile':
            	$conditions_tr.=' AND 0 ';
                //$conditions_tr.=" AND ( m.mobile = '$value' OR m.tel = '$value' ) ";
                break;
            case 'date_start':
                $conditions_tr.=" AND m.begin_time>='$value' ";
                break;
            case 'date_end':
                $conditions_tr.=" AND m.begin_time<='$value' ";
                break;
            case 'track_number':
            case 'return_track_number':
                $conditions_tr.=" AND  0 ";
                break;
            case 'dist':
            	if($value=='zhixiao' || $value=='fenxiao'){
            		$conditions_tr.=" AND m.dist = '".$value."' ";
            	}
            	break;
        	case 'distributor':
	            if($the_plus_condition['distributor'] != '-1') {
			    	$conditions_tr.=" AND m.distributor_id ='".$the_plus_condition['distributor']."' ";
			    }
			    break;
			case 'refund_type':
	        	if($the_plus_condition['refund_type']=='mobile' || $the_plus_condition['refund_type']=='notMoble'){
            		$conditions_tr.=" AND m.is_mobile_refund  = '".$the_plus_condition['refund_type']."' ";
            	}
	            break;   
        }
    }
    
    if($use_role == 'logistics'){
    	$conditions_tr.= " AND (m.state_tag not in ({$wl_ex_mode_array}) or m.todo_tag=24) ";
    }elseif ($use_role == 'postsale') {
    	$conditions_tr.=" AND (m.state_tag not in ({$kf_ex_mode_array}) or m.todo_tag=23) ";
    }elseif ($use_role == 'finance') {
    	$conditions_tr.=" AND m.state_tag not in ({$cw_ex_mode_array}) ";
    }elseif ($use_role == 'shop') {
    	$conditions_tr.=" AND m.state_tag not in ({$dz_ex_mode_array}) ";
    }elseif ($use_role == 'cg') {
    	$conditions_tr.=" AND m.state_tag not in ({$cg_ex_mode_array}) ";
    }

    //$conditions_tr.=' AND date_sub(curdate(), INTERVAL 30 MINUTE) <= date(m.cache_time) ';

    $ts=microtime(true);

    $sql="SELECT m.*,ssm.message,NAME
        from ecshop.cache_postsale_message m 
        LEFT JOIN ecshop.sale_support_message ssm on m.recent_msg_id=ssm.sale_support_message_id
        LEFT JOIN romeo.party p ON CONVERT(m.party_id USING utf8)= p.party_id
        where 1 
        {$conditions_tr} 
        {$sql_paging}
    ";
    //echo "messages SQL:".$sql;
    $duo_message_array=$db->getAll($sql);

    $count_sql="SELECT count(1) from ecshop.cache_postsale_message m where 1 {$conditions_tr}";
    $countArray['messages']=$db->getOne($count_sql);

    $te=microtime(true);
    $db_time['cache_postsale_message']=$te-$ts;
    
    //ORI

    $ori = array(
        'taobao_refunds' => $taobao_tasks,
        'msg' => $duo_message_array,
        'services' => $services,
        'refunds' => $refunds
    );
    $grouped_ilp=array();

    foreach ($ori['taobao_refunds'] as $key => $line) {
        $linked_orders=get_orders_by_taobao_order_sn($line['tid']);
        //Special Filter
        if(
            $the_plus_condition['order_sn'] ||
            $the_plus_condition['buyer_name'] ||
            $the_plus_condition['mobile'] ||
            $the_plus_condition['track_number']
        ){
            $left=false;
            foreach ($linked_orders as $no => $order) {
                if($the_plus_condition['order_sn']){
                    if($order['order_sn']==$the_plus_condition['order_sn']){
                        $left=true;
                        break;
                    }
                }
                if($the_plus_condition['buyer_name']){
                    if($order['consignee']==$the_plus_condition['buyer_name']){
                        $left=true;
                        break;
                    }
                }
                if($the_plus_condition['mobile']){
                    if($order['mobile']==$the_plus_condition['mobile'] ||
                        $order['tel']==$the_plus_condition['mobile']){
                        $left=true;
                        break;
                    }
                }
                if($the_plus_condition['track_number']){
                    $tns=getTrackingNumbersForOrder($order['order_id']);
                    if(!empty($tns) && in_array($the_plus_condition['track_number'], $tns)){
                        $left=true;
                        break;
                    }
                }

            }
            if(!$left) continue;
        }
        
        $grouped_ilp[$line['tid']]['taobao_refunds'][$line['refund_id']]=$line;
        
        //这货移到外面去
        if(true || $line['status']=='WAIT_SELLER_AGREE'){
            foreach ($linked_orders as $no => $order) {
                $grouped_ilp[$line['tid']]['orders'][$order['order_id']]['order_info']=$order;
                $grouped_ilp[$line['tid']]['orders'][$order['order_id']]['order_sn']=$order['order_sn'];
            }
        }
        
    }

	if($ori['msg'] && is_array($ori['msg']) && count($ori['msg'])>0){
		foreach ($ori['msg'] as $ori_line_index => $ori_line) {
			$tbsn=$ori_line['taobao_order_sn'];
			if(empty($tbsn)){
	            $orline=get_order_relation_path($ori_line['order_id']); 
	            if($orline){
	                $root_order_id=$orline['root_order_id'];
	            }else{
	                $root_order_id='NONE';
	            }
	            $grouped_ilp["*$root_order_id"]['orders'][$ori_line['order_id']]['msg']=$ori_line;
	            $grouped_ilp["*$root_order_id"]['orders'][$ori_line['order_id']]['order_sn']=$ori_line['order_sn'];
	        } else {
	            $grouped_ilp[$tbsn]['orders'][$ori_line['order_id']]['msg']=$ori_line;
	            $grouped_ilp[$tbsn]['orders'][$ori_line['order_id']]['order_sn']=$ori_line['order_sn'];
	        }
		}
	}
	if($ori['services'] && is_array($ori['services']) && count($ori['services'])>0){
		foreach ($ori['services'] as $ori_line_index => $ori_line) {
			$tbsn=$ori_line['taobao_order_sn'];
			if(empty($tbsn)){
	            $orline=get_order_relation_path($ori_line['order_id']);
	            if($orline){
	                $root_order_id=$orline['root_order_id'];
	            }else{
	                $root_order_id='NONE';
	            }
	            $grouped_ilp["*$root_order_id"]['orders'][$ori_line['order_id']]['services'][$ori_line['service_id']]=$ori_line;
	            $grouped_ilp["*$root_order_id"]['orders'][$ori_line['order_id']]['order_sn']=$ori_line['order_sn'];
	        } else {
	            $grouped_ilp[$tbsn]['orders'][$ori_line['order_id']]['services'][$ori_line['service_id']]=$ori_line;
	            $grouped_ilp[$tbsn]['orders'][$ori_line['order_id']]['order_sn']=$ori_line['order_sn'];
	        }
		}
	}
	if($ori['refunds'] && is_array($ori['refunds']) && count($ori['refunds'])>0){
		foreach ($ori['refunds'] as $ori_line_index => $ori_line) {
			$tbsn=$ori_line['taobao_order_sn'];
			if(empty($tbsn)){
	            $orline=get_order_relation_path($ori_line['order_id']);
	            if($orline){
	                $root_order_id=$orline['root_order_id'];
	            }else{
	                $root_order_id='NONE';
	            }
	            $grouped_ilp["*$root_order_id"]['orders'][$ori_line['order_id']]['refunds'][$ori_line['refund_id']]=$ori_line;
	            $grouped_ilp["*$root_order_id"]['orders'][$ori_line['order_id']]['order_sn']=$ori_line['order_sn'];
	        } else {
	            $grouped_ilp[$tbsn]['orders'][$ori_line['order_id']]['refunds'][$ori_line['refund_id']]=$ori_line;
	            $grouped_ilp[$tbsn]['orders'][$ori_line['order_id']]['order_sn']=$ori_line['order_sn'];
	        }
		}
    }
    
    //print_r("grouped_ilp".$grouped_ilp['625121215049475']['orders']['3803598']['order_sn']);
    //print_r("grouped_ilp".$grouped_ilp);

    $the_group=$grouped_ilp;
	$read_group=array();
	$memo="";
    $read_group=array();
    $time_now=time();
    foreach ($the_group as $taobao_order_sn => $group1) {
        $read_group[$taobao_order_sn]['taobao_refunds']=array();
        if($group1['taobao_refunds'] && is_array($group1['taobao_refunds']) && ($use_role=='viewer' || $use_role=='postsale' || $use_role=='finance')){
            foreach ($group1['taobao_refunds'] as $taobao_refund_id => $line) {
                
                $time_c=strtotime($line['created']);
                $time_dif_day=round(($time_now-$time_c)/(3600*24));
                $memo="";
                
                $memo="<!--\n";
                foreach ($line as $key => $value) {
                    $memo.="$key=$value\n";
                }
                $memo.="-->";

                $whodowhat='-';
                $return_shipping_info="";

                $type_label="未知";
                $status_label="喵";
                $return_fee_label="正体不明";
                
                if($line['TAOBAO_TASK_TYPE']=='taobao'){
                    if($line['status']=='WAIT_SELLER_AGREE'){
                        $whodowhat="待客服审查申请";
                    }else if($line['status']=='WAIT_BUYER_RETURN_GOODS'){
                        $whodowhat="物流坐等货物退回";
                    }else if($line['status']=='WAIT_SELLER_CONFIRM_GOODS'){
                        $whodowhat="待客服建立申请<br>待财务处理退款";
                        $return_shipping_info.=$line['company_name'].":".$line['sid'].($line['reason']?'<br>':'');
                    }
                    $type_label="淘宝直销";
                    $status_label=($line['status']=='WAIT_SELLER_AGREE'?"<span class='keikoku'>":'<span>').
                        $get_sync_taobao_refund_state_map[$line['status']].
                        "</span>";
                    $return_fee_label=$line['refund_fee'];
                }else if($line['TAOBAO_TASK_TYPE']=='tmall'){
                    if($line['status']=='wait_seller_agree'){
                        $whodowhat="待客服审查申请";
                    }else if($line['status']=='goods_returning'){
                        $whodowhat="物流坐等货物退回";
                    }
                    $type_label="天猫";
                    $status_label=($line['status']=='wait_seller_agree'?"<span class='keikoku'>":'<span>').
                        $get_sync_tmall_refund_state_map[$line['status']].
                        "</span>";
                    $return_fee_label=($line['refund_fee']/100.0);
                }else if ($line['TAOBAO_TASK_TYPE']=='taobao_fenxiao'){
                    if(in_array($line['status'],array(1)) ){
                        $whodowhat="待客服审查申请";
                    }else if(in_array($line['status'],array(2,3)) ){
                        $whodowhat="物流坐等货物退回";
                    }else if(in_array($line['status'],array(12)) ){
                        $whodowhat="待财务退款";
                    }else if(in_array($line['status'],array(9)) ){
                        $whodowhat="客服核实是否已申请退款";
                    }else if(in_array($line['status'],array(10)) ){
                        $whodowhat="客服处理卖家拒收善后";
                    }
                    $status_label=$get_sync_taobao_fenxiao_refund_state_map[$line['status']];
                    $type_label="淘宝分销";
                    $return_fee_label=$line['refund_fee'];
                }

                $read_group[$taobao_order_sn]['taobao_refunds'][$taobao_refund_id]=array(
                    '类型'=>$type_label,
                    '状态'=>$status_label,
                    '金额'=>$return_fee_label,
                    '顾客'=>$line['buyer_nick'],
                    '原因'=>$line['reason'],
                    '时间'=>'同步于'.$line['modified']."<br><span".
                        ($time_dif_day>5?" class='keikoku'>":">").
                        "发起于".($time_dif_day>0?$time_dif_day."天前":"今天").
                        "</span><!--".$line['created']."-->",
                    '待办'=>$whodowhat,
                    '备注'=>$return_shipping_info.$memo,

                );
            }
        } 

        $need_pending = false;
		if($the_plus_condition['is_soon_or_pending']=='is_pending'){
        	if($use_role=='logistics') {
        		$need_pending = true;
        	}
        }
        
        $read_group[$taobao_order_sn]['orders']=array();
        if($group1['orders'] && is_array($group1['orders'])){
            foreach ($group1['orders'] as $order_id => $group2) {
                //ORDER_SN
                $read_group[$taobao_order_sn]['orders'][$order_id]['order_sn']=$group2['order_sn'];
                //PENDING
                $read_group[$taobao_order_sn]['orders'][$order_id]['pending']='not_pending';
                //MSG
                $read_group[$taobao_order_sn]['orders'][$order_id]['msg']=array();
                
                if($use_role!='logistics' || ($use_role=='logistics' && !$need_pending)) {
                	if($group2['msg'] && is_array($group2['msg'])){
	                    $time_c=strtotime($group2['msg']['begin_time']);
	                    $time_dif_day=round(($time_now-$time_c)/(3600*24));
	                    $memo="";
	                    
	                    $shall_pass=false;
	                    
	                    if($use_role!='viewer'){
                            switch ($user_priv_list[$group2['msg']['next_worker']]['value']) {
                                case '客服':
                                case '客服(分销)':
                                    if($use_role!="postsale")$shall_pass=true;
                                    break;
                                case '财务':
                                    if($use_role!="finance")$shall_pass=true;
                                    break;
                                case '店长':
                                    if($use_role!="shop")$shall_pass=true;
                                    break;
                                case '快递理赔客服':
                                    if($use_role!="cg")$shall_pass=true;
                                    break;
                                case '北京物流':
                                    if($use_role!="logistics" || !check_admin_user_priv($_SESSION['admin_name'], 'bjwl_sale_support')){
                                        $shall_pass=true;
                                    }
                                    break;
                                case '上海物流':
                                    if($use_role!="logistics" || !check_admin_user_priv($_SESSION['admin_name'], 'shwl_sale_support')){
                                        $shall_pass=true;
                                    }
                                    break;
                                case '东莞物流':
                                    if($use_role!="logistics" || !check_admin_user_priv($_SESSION['admin_name'], 'dgwl_sale_support')){
                                        $shall_pass=true;
                                    }
                                    break;
                                case '外包物流':
                                    if($use_role!="logistics" || !check_admin_user_priv($_SESSION['admin_name'], 'wbwl_sale_support')){
                                        $shall_pass=true;
                                    }
                                    break;
                                case '武汉物流':
                                    if($use_role!="logistics" || !check_admin_user_priv($_SESSION['admin_name'], 'whwl_sale_support')){
                                        $shall_pass=true;
                                    }
                                    break;
                                case '成都物流':
                                    if($use_role!="logistics" || !check_admin_user_priv($_SESSION['admin_name'], 'cdwl_sale_support')){
                                        $shall_pass=true;
                                    }
                                    break;
                                case '精品物流':
                                    if($use_role!="logistics" || !check_admin_user_priv($_SESSION['admin_name'], 'jpwl_sale_support')){
                                        $shall_pass=true;
                                    }
                                    break;
                                     case '苏州物流':
                                    if($use_role!="logistics" || !check_admin_user_priv($_SESSION['admin_name'], 'szwl_sale_support')){
                                        $shall_pass=true;
                                    }
                                    break;
                                default:
                                    if(empty($group2['msg']['program']) && $use_role=='postsale'){
                                        //do not pass it, saith Sinri
                                        //but damn it, XLH argued it as original
                                        $shall_pass=true;
                                    } else $shall_pass=true;
                                    break;
                            }
	                    }

                        if(!empty($the_plus_condition['last_word_keyword'])){
                            if(strstr($group2['msg']['message'], $the_plus_condition['last_word_keyword'])===false){
                                $shall_pass=true;
                            }
                        }

	                    if(empty($group2['msg']['program']) && empty($group2['msg']['next_worker'])){
	                        $highlight=true;
	                    }
                        $zhuihui_todo="";
                        $zhuihui_is_wl='goutong';
                        if($group2['msg']['program']=='追回'){
                        	if($group2['msg']['todo_tag']==23){
								if($use_role=="postsale")$shall_pass=false;
                                $zhuihui_todo="待客服取消应追回订单";
                        	}elseif($group2['msg']['todo_tag']==24){
 									if($use_role=="logistics" && strpos($str_user_facilities, $group2['msg']['facility_id'])!==false){
                                    $shall_pass=false;
                                    $zhuihui_is_wl='zhuihui';
                                }
                                $zhuihui_todo="待物流追回订单";
                        	}
                        	/*
                            if($group2['msg']['order_status']!=2 && $group2['msg']['shipping_status']!=11){
                                if($use_role=="postsale")$shall_pass=false;
                                $zhuihui_todo="待客服取消应追回订单";
                            }else if($group2['msg']['order_status']==2 && $group2['msg']['shipping_status']!=11){
                                if($use_role=="logistics" && strpos($str_user_facilities, $group2['msg']['facility_id'])!==false){
                                    $shall_pass=false;
                                    $zhuihui_is_wl='zhuihui';
                                }
                                $zhuihui_todo="待物流追回订单";
                            }
                            */
                        }
	                    if(!$shall_pass){
                            $waiting_services_memo="";
                            if($group2['services'] && is_array($group2['services'])){
                                foreach ($group2['services'] as $service_id => $serv_line) {
                                    if($serv_line['is_waiting_back'] != 0 ) {
                                        $waiting_services_memo.=$service_id." ";
                                    }
                                }
                            }
                            if($waiting_services_memo!=""){
                                $waiting_services_memo="等待中的退货申请：".$waiting_services_memo;
                            }

	                        $read_group[$taobao_order_sn]['orders'][$order_id]['msg']=array(
	                            $group2['msg']['order_id']=>array(
	                                '类型'=>"沟通",
	                                '状态'=>($group2['msg']['program']==''?'未定案':'已定案<br>('.$group2['msg']['program'].')'),
	                                '金额'=>'-',
	                                '顾客'=>$group2['msg']['consignee'],
	                                '原因'=>$group2['msg']['message'],//$group2['msg']['recent_msg_id'],
	                                '时间'=>$group2['msg']['begin_time']."<br><span".
	                                    ($time_dif_day>5?" class='keikoku'>":">").
	                                    ($time_dif_day>0?$time_dif_day."天前":"今天").
	                                    "</span>",
	                                '待办'=>(empty($group2['msg']['next_worker'])?"未明确部门":$user_priv_list[$group2['msg']['next_worker']]['value']).
                                        ($zhuihui_todo!=''?"<br>".$zhuihui_todo:""),
	                                '备注'=>$memo,
                                    '组织'=>$group2['msg']['NAME'],
	                                'highlight'=>($highlight?'Y':'N'),
                                    'pending_service_list'=>$waiting_services_memo,
                                    'zhuihui_is_wl'=>$zhuihui_is_wl,
	                            )
	                        );
	                    }
	                }
					
					//### }
	
	                // AS MEG
	                
	                //REFUND
	                $read_group[$taobao_order_sn]['orders'][$order_id]['refunds']=array();
	                if($group2['refunds'] && is_array($group2['refunds'])){
	                    foreach ($group2['refunds'] as $refund_id => $line) {
	                        $ww=$line['next_worker'];
	                        if($use_role!='viewer'){
	                            $shall_show=false;
	                            if(strstr($ww, '客服') && $use_role=="postsale")$shall_show=$shall_show||true;
	                            if(strstr($ww, '财务') && $use_role=="finance")$shall_show=$shall_show||true;
	                            if(strstr($ww, '店长') && $use_role=="shop")$shall_show=$shall_show||true;
	                            if(strstr($ww, '物流') && $use_role=="logistics")$shall_show=$shall_show||true;
	                            if(!$shall_show) continue;
	                        }
	                        $time_c=strtotime($line['begin_time']);
	                        $time_dif_day=round(($time_now-$time_c)/(3600*24));
	                        $memo="";
	                        	                        
	                        $read_group[$taobao_order_sn]['orders'][$order_id]['refunds'][$refund_id]=array(
	                            '类型'=>'退款',
	                            '状态'=>$line['refund_status_desc'],
	                            '金额'=>$line['total_amount'],
	                            '顾客'=>$line['consignee'],
	                            '原因'=>'',
	                            '时间'=>"申请于".$line['begin_time']."<br><span".
	                                ($time_dif_day>5?" class='keikoku'>":">").
	                                ($time_dif_day>0?$time_dif_day."天前":"今天").
	                                "</span>",
	                            '待办'=>$line['next_worker'],
	                            '备注'=>$memo,
	                        );
	                    }
	                }
                }
                
                //SERVICE
                $read_group[$taobao_order_sn]['orders'][$order_id]['services']=array();
                if($group2['services'] && is_array($group2['services'])){
                    foreach ($group2['services'] as $service_id => $line) {
                    	
                        if($use_role!='viewer'){
                        	if($need_pending) {
                        		if($line['is_waiting_back'] == 0 ) {
                        			continue;
                        		}
                        	} else {
                        		if($line['is_waiting_back'] != 0 ) {
                        			continue;
                        		}
                        	}
                        }
                    	
                        $ww=$line['next_worker'];
                        if($use_role!='viewer'){
                            $shall_show=false;
                            if(strstr($ww, '客服') && $use_role=="postsale")$shall_show=$shall_show||true;
                            if(strstr($ww, '财务') && $use_role=="finance")$shall_show=$shall_show||true;
                            if(strstr($ww, '店长') && $use_role=="shop")$shall_show=$shall_show||true;
                            if((strstr($ww, '物流') || strstr($ww, '消费者')) && $use_role=="logistics")$shall_show=$shall_show||true;
                            if(!$shall_show) continue;
                        }
                        $time_c=strtotime($line['begin_time']);
                        $time_dif_day=round(($time_now-$time_c)/(3600*24));
                        $memo="";
                        
                        $service_info =$line;
                        
                        $return_info=get_service_return_deliver_info($line['service_id']);
                        //if($line['origin_facility_name'])$memo.="发出仓库：".$line['origin_facility_name']."<br>";
                        //if($line['bill_no'])$memo.="发出运单：".$line['shipping_name']." <input type='text' readonly='readonly' style='border: none;' value='".$line['bill_no']."'><br>";
                        //if($line['facility_name'])$memo.="运回仓库：".$line['facility_name']."<br>";
                        //if($return_info['deliver_company'])$memo.="运回运单：".$return_info['deliver_company'].$return_info['deliver_number']."<br>";
                        $read_group[$taobao_order_sn]['orders'][$order_id]['services'][$service_id]=array(
                            '类型'=>($line['service_type']==1?'换货':($line['service_type']==2?'退货':'售后')),
                            '状态'=>$line['service_status_desc'],
                            '金额'=>"-",
                            '顾客'=>$line['consignee'],
                            '原因'=>'',
                            '时间'=>"申请于".$line['begin_time']."<br><span".
                                ($time_dif_day>5?" class='keikoku'>":">").
                                ($time_dif_day>0?$time_dif_day."天前":"今天").
                                "</span>",
                            '待办'=>$line['next_worker'],
                            '备注'=>$memo,
                            '组织'=>$line['NAME'],
                            'service_info'=>$line,
                        );
                    }
                } 
                
                if(
                    !empty($group2['msg']) ||
                    !empty($group2['services']) ||
                    !empty($group2['refunds'])
                ){
                    //DO NOTHING MORE
                } else {
                    if(true || $use_role=='postsale'){//Let every one see

                        if($group2['order_info'] && count($group2['order_info'])){
                            $line=$group2['order_info'];

                            $time_c=strtotime($line['order_time']);
                            $time_dif_day=round(($time_now-$time_c)/(3600*24));
                            $memo="";
                            
                            $memo="<!--\n";
                            foreach ($group2['order_info'] as $k => $v) {
                                $memo.="$k=$v\n";
                            }
                            $memo.="-->";

                            $msg_line=get_sale_support_message_id($line['order_id']);
                            if($use_role=='postsale' && $msg_line && strstr($msg_line['program'], '无需处理')){
                                //hide
                            }else{

                                if($msg_line){
                                    $memo.=$msg_line['program']."<br>";
                                }else{
                                    $memo.="没有沟通结论"."<br>";
                                }

                                $status_memo="";
                                $linked_refunds=get_refunds_of_one_order($line['order_id']);
                                $linked_services=get_services_of_one_order($line['order_id']);
                                if($linked_services){
                                    foreach ($linked_services as $no => $linked_service) {
                                        $back_refunds=get_refunds_of_one_order($linked_service['back_order_id']);
                                        if($back_refunds)array_merge($linked_refunds,$back_refunds);
                                    }
                                    foreach ($linked_services as $no => $linked_service) {
                                        $status_memo.="售后【".($no)."】<br>".get_service_line_status_description($linked_service)."<br>";
                                    }
                                }
                                if($linked_refunds){
                                    foreach ($linked_refunds as $no => $linked_refund) {
                                        $status_memo.="退款【".($no)."】<br>".$refund_status_name[$linked_refund['STATUS']]."<br>";
                                    }
                                }
                                if(empty($linked_refunds) && empty($linked_services)){
                                    $has_no_applications=true;
                                }else{
                                    $has_no_applications=false;
                                }
                                $is_hide_erp_order=true;
                                if($use_role=='postsale'){
                                    if($has_no_applications){
                                        //Tell KF?
                                        $is_hide_erp_order=false;
                                    } else{
                                        //hide it
                                        $is_hide_erp_order=true;
                                    }
                                }else if($use_role=='viewer'){
                                    $is_hide_erp_order=false;
                                }
                                if($msg_line && strstr($msg_line['program'], '无需处理')){
                                    if($has_no_applications){
                                        $nottodo_word="此单无需处理";
                                    }else{
                                        $nottodo_word="此单结论为无需处理<br>但存在售后操作请核实";
                                    }
                                }else{
                                    if($has_no_applications){
                                        $nottodo_word='供客服选择发起售后';
                                    }else{
                                        $nottodo_word='供客服核查进度';
                                    }
                                }
                                if(!$is_hide_erp_order && $line['order_sn']){
                                    $the_order_type=get_order_type_name($line['order_sn']);
                                    if($the_order_type=='订单')
                                    $read_group[$taobao_order_sn]['orders'][$order_id]['order_info']=array(
                                        '类型'=>$the_order_type,
                                        '状态'=>(empty($status_memo)?'未建申请':'已有申请'.$status_memo),
                                        '金额'=>$line['order_amount'],
                                        '顾客'=>$line['consignee'],
                                        '原因'=>'',
                                        '时间'=>"建立于".$line['order_time']."<br><span".
                                            ($time_dif_day>5?" class='keikoku'>":">").
                                            ($time_dif_day>0?$time_dif_day."天前":"今天").
                                            "</span>",
                                        '待办'=>$nottodo_word,
                                        '备注'=>$memo,
                                    );
                                }

                            }
                        }
                    }
                }
            }
        }
    }
    //echo "<hr>";
    //print_r($read_group);

    //If sb wants to check the last message just hide others.
    if(!empty($the_plus_condition['last_word_keyword'])){
        foreach ($read_group as $taobao_order_sn => $group1) {
            foreach ($group1['orders'] as $order_id => $group2) {
                $read_group[$taobao_order_sn]['orders'][$order_id]['services']=array();
                $read_group[$taobao_order_sn]['orders'][$order_id]['refunds']=array();
                $read_group[$taobao_order_sn]['orders'][$order_id]['taobao_refunds']=array();
                $read_group[$taobao_order_sn]['orders'][$order_id]['order_info']=array();
            }
        }
    }
                
    //COUNT
    foreach ($read_group as $taobao_order_sn => $group1) {
        $read_group[$taobao_order_sn]['count']=count($group1['taobao_refunds'])+count($group1['tmall_refunds']);
        $read_group[$taobao_order_sn]['count2']=count($group1['taobao_refunds'])+count($group1['tmall_refunds']);
        foreach ($group1['orders'] as $order_id => $group2) {
            $plus_pure=(count($group2['msg']))+
                (count($group2['services']))+
                (count($group2['refunds']));

            if(
                $use_role!='viewer' && $plus_pure==0 
                 && get_order_type_name($group2['order_sn'])!='订单'
            ){
                unset($read_group[$taobao_order_sn]['orders'][$order_id]);
                continue;
            }else{
                $plus=$plus_pure+(count($group2['order_info'])>0?1:0);
                $read_group[$taobao_order_sn]['orders'][$order_id]['count']=$plus;
                $read_group[$taobao_order_sn]['count']+=$plus;

                if($plus>0)$plus2=$plus+1;else $plus2=0;
                $read_group[$taobao_order_sn]['orders'][$order_id]['count2']=$plus;
                $read_group[$taobao_order_sn]['count2']+=$plus2;
            }
        }
        if($read_group[$taobao_order_sn]['count']==0)unset($read_group[$taobao_order_sn]);
        if($use_role!='viewer' && $read_group[$taobao_order_sn]['count']==(count($group1['taobao_refunds'])+count($group1['tmall_refunds']))){
            unset($read_group[$taobao_order_sn]);
        }
    }

	return $read_group;
}

?>