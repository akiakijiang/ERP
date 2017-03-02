<?php

/*
*
*	理赔订单管理
*
*	@author meichao 2025-7-13
*	@copyright
*
*/

define('IN_ECS',true);
require_once('includes/init.php');
require_once (ROOT_PATH . 'includes/helper/uploader.php');
require_once (ROOT_PATH . 'admin/function.php');
require_once (ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'includes/cls_page.php');
require_once(ROOT_PATH . 'includes/cls_json.php');
require_once (ROOT_PATH . 'admin/function.php');
require_once(ROOT_PATH . 'admin/includes/cls_pagination.php');

admin_priv('claims_settlement');
if( check_admin_priv ( 'claims_settlement_modify' )) {
	$can_view_action = true;
}

$is_claim_list = array(
	'1' => '是',
	'0' => '否',
);

$compensation_type_list = array(
	'SHIP_ONLY' => '补寄',
	'REFUND' => '退款',
);

$after_sale_type = array(
	'WZPS' => '无责破损 (外包装完好/本人签收的内物破损)',
	'WZLF' => '无责漏发 (外包装完好/本人签收仓库核实无果的漏发)',
	'WZCF' => '无责错发 (外包装完好/本人签收仓库核实无果的错发)',
	'ZCTK' => '正常退款 (未发货退款  正常的退货退款等)',
	'EYSH' => '恶意售后 (顾客恶意申请退款、恶意威胁)',
	'TCJ'  => '退差价 (活动差价或优惠券差价、好评返现、半价活动、免单)',
	'SPWT' => '商品问题 (顾客认为是质量问题/描述不符，品牌商不予承担)',
	'ZLWT' => '质量问题 (顾客对商品质量提出质疑或明显的质量问题，核实过后定为品牌商承担)',
	'YDTH' => '原单退回 (原单退回破损，但快递和仓库不承认)',
	'GKTH' => '顾客退货 (顾客退货和仓库收到实物不符，必须以顾客的为准)',
	'YTSP' => '液体商品 (液体商品破损快递不赔/液体破损直接弃件)',
	'JSTK' => '急速退款 (急速退款，顾客填写订单号无效，时间将至，联系无果)',
	'TSJB' => '投诉举报 (工商投诉赔款、举报处理)',
	'TSYW' => '特殊业务 (品牌商故意或者失误导致的售后)',
	'QTPT' => '其他平台 (由于平台/仓库产生的售后)',
	'ZRMQ' => '责任明确 (已经明确责任人，以定责的选项为准)',
);

$message = "";
$time = time()-60*60*24*30;
$date = date("Y-m-d",$time);       //得到当前日期的前三十天
$party_id = $_SESSION['party_id'];
global $db;
$extra_params = array();
$req = isset($_REQUEST['request']) && 
    in_array($_REQUEST['request'], array('ajax')) 
    ? $_REQUEST['request'] 
    : null;
$act = $_REQUEST ['act'];
if ($req == 'ajax')
{
    $json = new JSON;
    switch ($act) 
    { 
        case 'get_select_shop':
            $nick = $_REQUEST['q'];
            $sql = "
			select nick,party_id,application_key from ecshop.taobao_shop_conf
			WHERE
			nick like '%{$nick}%' and party_id=".$party_id." limit 20
			";
			$result=$GLOBALS['db']->getAll($sql);	
            if ($result)
                print $json->encode($result);
            else{
            	$sql = "select name as nick,party_id,distributor_id as application_key from ecshop.distributor where name like '%{$nick}%' and party_id=".$party_id." limit 20";
            	$result=$GLOBALS['db']->getAll($sql);	
            	if ($result)
                	print $json->encode($result);
                else
            		print $json->encode(array('error' => '店铺不存在'));
            }
            break;
    }
    exit;
}   

if($party_id == '65535') {
	$sql_basic = "SELECT cs.*,eoi.order_sn,GROUP_CONCAT(s.tracking_number) as tracking_number,p.NAME as party_name,d.name as distributor_name,concat('\'',eoi.taobao_order_sn) as taobao_order_sn,eoi.consignee,rd.NOTE
			FROM ecshop.claims_settlement cs 
			INNER JOIN ecshop.ecs_order_info eoi use index(PRIMARY) ON eoi.order_id = cs.order_id 
			INNER JOIN romeo.party p ON convert(eoi.party_id using utf8) = p.party_id 
			INNER JOIN ecshop.distributor d ON eoi.distributor_id = d.distributor_id
      		INNER JOIN ecshop.main_distributor md ON d.main_distributor_id = md.main_distributor_id
			LEFT JOIN romeo.order_shipment os ON os.ORDER_ID = CONVERT(eoi.order_id using utf8) AND cs.compensation_type='SHIP_ONLY'
			LEFT JOIN romeo.shipment s ON os.shipment_id = s.shipment_id 
			LEFT JOIN romeo.refund r ON r.refund_id = cs.refund_id
			LEFT JOIN romeo.refund_detail rd ON rd.refund_id = cs.refund_id
			WHERE cs.is_delete = '0'  AND ((cs.compensation_type='REFUND' AND CHECK_NOTE_3 is not null) OR (cs.compensation_type='SHIP_ONLY')) 
			";
} else {
	$sql_basic = "SELECT cs.*,eoi.order_sn,GROUP_CONCAT(s.tracking_number) as tracking_number,p.NAME as party_name,d.name as distributor_name,concat('\'',eoi.taobao_order_sn) as taobao_order_sn,eoi.consignee,rd.NOTE
			FROM ecshop.claims_settlement cs 
			INNER JOIN ecshop.ecs_order_info eoi use index(PRIMARY) ON eoi.order_id = cs.order_id AND eoi.party_id = '{$party_id}' 
			INNER JOIN romeo.party p ON convert(eoi.party_id using utf8) = p.party_id 
			INNER JOIN ecshop.distributor d ON eoi.distributor_id = d.distributor_id
      		INNER JOIN ecshop.main_distributor md ON d.main_distributor_id = md.main_distributor_id
			LEFT JOIN romeo.order_shipment os ON os.ORDER_ID = CONVERT(eoi.order_id using utf8) AND cs.compensation_type='SHIP_ONLY'
			LEFT JOIN romeo.shipment s ON os.shipment_id = s.shipment_id 
			LEFT JOIN romeo.refund r ON r.refund_id = cs.refund_id
			LEFT JOIN romeo.refund_detail rd ON rd.refund_id = cs.refund_id
			WHERE cs.is_delete = '0'  AND ((cs.compensation_type='REFUND' AND CHECK_NOTE_3 is not null) OR (cs.compensation_type='SHIP_ONLY')) 
			";
}


if($_REQUEST['act'] == '筛选'){	

	$extra_params = array(
		'order_sn'           => isset($_REQUEST['order_sn'])?$_REQUEST['order_sn']:null,
		'taobao_order_sn'    => isset($_REQUEST['taobao_order_sn'])?$_REQUEST['taobao_order_sn']:null,
		'start_stamp'  	     => isset($_REQUEST['start_stamp'])?$_REQUEST['start_stamp']:null,
		'end_stamp'          => isset($_REQUEST['end_stamp'])?$_REQUEST['end_stamp']:null,
		'responsible_party'  => isset($_REQUEST['responsible_party'])?$_REQUEST['responsible_party']:null,
		'compensation_type'  => isset($_REQUEST['compensation_type'])?$_REQUEST['compensation_type']:null,
		'is_claim'           => isset($_REQUEST['is_claim'])?$_REQUEST['is_claim']:null,
		'start_stamp'        => isset($_REQUEST['start_stamp'])?$_REQUEST['start_stamp']:null,
		'shopnick' 			 => isset($_REQUEST['shopnick'])?$_REQUEST['shopnick']:null,
		'type'				 => isset($_REQUEST['type'])?$_REQUEST['type']:null,
		'act'                => '筛选',
		);

	$condition = get_condition();         //前台传回来的数据进行条件的追加
	$sql = $sql_basic . $condition."
			GROUP BY cs.compensation_id
			order by created_stamp";
	$claims_settlements = $db->getAll($sql);
	
	//总页数
	$total = sizeof($claims_settlements);
	//页码
	$page = 
    is_numeric($_REQUEST['page']) && ($_REQUEST['page'] > 0) 
    ? $_REQUEST['page'] 
    : 1 ;
    //每页数据量
	$limit = 20;
	$total_page = ceil($total/$limit);
	$page = max(1,min($page,$total_page));
	$offset = $limit * ($page-1);	
	
	$sql_offset = $sql . " LIMIT {$limit} OFFSET {$offset} ";
	$claims_settlements = $db->getAll($sql_offset);
		
	foreach($claims_settlements as $key=>$item) {
		$compensation_type_key = $item['compensation_type'];
		$claims_settlements[$key]['compensation_type'] = $compensation_type_list[$compensation_type_key];
		$claims_settlements[$key]['can_modify'] = true;
		if($item['created_stamp'] < $date) {
			$claims_settlements[$key]['can_modify'] = false;
		}
		$order_sn_temp = $claims_settlements[$key]['order_sn'];
		$created_stamp_temp = $claims_settlements[$key]['created_stamp'];
		$order_stamp = $order_sn_temp."_".$created_stamp_temp;//构建一个以order_id和created_stamp为下边的数组$row_span，用来存储相同数据有几条
		$claims_settlements[$key]['order_stamp']= $order_stamp;
    	if(isset($row_span[$order_stamp]) && ($row_span['order_sn'] == $order_sn_temp) && ($row_span['created_stamp'] == $created_stamp_temp)){
    		$row_span[$order_stamp] = $row_span[$order_stamp] + 1; 
		}else{
    		$row_span[$order_stamp] =  1;
    		$row_span['order_sn'] =  $claims_settlements[$key]['order_sn'];
    		$row_span['created_stamp'] =  $claims_settlements[$key]['created_stamp'];
    	}
	}
	
	$pagination = new Pagination($total, $limit, $page, 'page', $url = 'claims_settlement.php', null, $extra_params);		
	$smarty->assign('claims_settlements',$claims_settlements);
	$smarty->assign('pagination', $pagination->get_simple_output());  // 分页
	$smarty->assign('row_span',$row_span);
	
} else if($_REQUEST['act'] == '导出') {
	global $db;
	$condition = get_condition();         //前台传回来的数据进行条件的追加
	$sql = $sql_basic . $condition . "
			GROUP BY cs.compensation_id
			order by created_stamp";
			//Qlog::log($sql);
	$claims_settlements = $db->getAll($sql);

	foreach($claims_settlements as $key=>$item) {
		$compensation_type_key = $item['compensation_type'];
		$claims_settlements[$key]['compensation_type'] = $compensation_type_list[$compensation_type_key];
		$compensation_party_key = $item['responsible_party'];
		if($compensation_party_key == "CK"){
		$order_id = $item['order_id'];
		$sql = "SELECT eoi.facility_id,f.facility_name FROM ecshop.ecs_order_info eoi
LEFT JOIN romeo.facility f ON eoi.facility_id=f.facility_id
WHERE order_id= {$order_id}";
		$facility_info = $db->getRow($sql);
		$claims_settlements[$key]['facility_name'] = $facility_info['facility_name'];
		}
		$claims_settlements[$key]['responsible_party'] = $_CFG['adminvars']['responsible_party'][$compensation_party_key];
		$is_claim_key = $item['is_claim'];
		$claims_settlements[$key]['is_claim'] = $is_claim_list[$is_claim_key];
	}
	$smarty->assign('claims_settlements', $claims_settlements);
	header("Content-type:application/vnd.ms-excel");
    header("Content-Disposition:filename=" . iconv("UTF-8","GB18030","责任理赔表") . ".csv");
    $out = $smarty->fetch('oukooext/claims_settlement_csv.htm');
    echo iconv("UTF-8","GB18030", $out);
    exit();
} else if($_REQUEST['act'] == 'modify'){
	$compensation_id = $_REQUEST['compensation_id'] ? trim($_REQUEST['compensation_id']):'';
	$sql_select = "select * from  ecshop.claims_settlement where compensation_id = {$compensation_id}";
	$claims_info = $db->getRow($sql_select);
	if(!empty($claims_info))  {
		$compensation_amount = $_REQUEST['compensation_amount'] != null? trim($_REQUEST['compensation_amount']):$claims_info['compensation_amount'];
		$freight = $_REQUEST['freight'] ? trim($_REQUEST['freight']):$claims_info['freight'];
		$note = $_REQUEST['note'] ? trim($_REQUEST['note']):$claims_info['note'];
		$responsible_party = $_REQUEST['responsible_party'] ? trim($_REQUEST['responsible_party']):$claims_info['responsible_party'];
		$is_claim = isset($_REQUEST['is_claim']) ? trim($_REQUEST['is_claim']):$claims_info['is_claim'];
		$sql_update = "update ecshop.claims_settlement set compensation_amount = '{$compensation_amount}', freight='{$freight}', note='{$note}', responsible_party='{$responsible_party}', is_claim={$is_claim},last_updated_stamp = now() where compensation_id = {$compensation_id}";
		$db->query($sql_update);
	}	
	exit();	
} else if($_REQUEST['act'] == 'delete') {
	$compensation_id = $_REQUEST['compensation_id'] ? trim($_REQUEST['compensation_id']):'';
	$sql = "update ecshop.claims_settlement set is_delete = '1',last_updated_stamp = now() where compensation_id = {$compensation_id}";
	$db->query($sql);		
	exit();
	
} else if($_REQUEST['act'] == 'import_freight') {
	$tpl = array( '运费批量导入' => array(
			'compensation_id' => '责任理赔编码',
			'freight' => '运费'
		)
	);
	$execl_info = readExcel($tpl);
	if(empty($execl_info['result'])) {
		$message = $execl_info['error_info'];
	} else {		
		$result = $execl_info['result'];
		/* 检查数据  */
		$rowset = $result ['运费批量导入'];		
		// 订单数据读取失败
		if (empty ( $rowset )) {
			$message = 'excel文件中没有数据,请检查文件';
		} else {
			$goods_id = Helper_Array::getCols ( $rowset, 'compensation_id' );
			$spec = Helper_Array::getCols ( $rowset, 'freight' );			
			// 检查商品数据中是否有空内容
			$is_empty = false;
			foreach ( array_keys ( $tpl ['运费批量导入'] ) as $val ) {
				$in_val = Helper_Array::getCols ( $rowset, $val );
				$in_len = count ( $in_val );
				Helper_Array::removeEmpty ( $in_val );
				if (empty ( $in_val ) || $in_len > count ( $in_val )) {
					$message = "文件中存在空的{$tpl['运费批量导入'][$val]}，请确保有数据的行都是完整的";
					$is_empty = true;
				}
			}		
		  	if(!$is_empty) {
		  		if (count ( $goods_id ) > count ( array_unique ( $goods_id ) )) {
					$message = '文件中存在重复的责任理赔编码';
				} else {
					$import_fail = array ();
					$import_success = array();
					foreach ( $rowset as $key => $row ) {				
						$sql_compensation_id = "select count(*) from ecshop.claims_settlement   
								where compensation_id = '{$row['compensation_id']}'  and is_delete = 0";
						$compensation_id_count = $db -> getOne($sql_compensation_id);
						if($compensation_id_count == 1) {
							$sql = "update ecshop.claims_settlement 
								set freight = '{$row['freight']}',last_updated_stamp = now() 
								where compensation_id = '{$row['compensation_id']}' and is_delete = 0";			
							$result = $GLOBALS ['db']->query ( $sql );
							if($result) {
								$import_success[] = $row['compensation_id'];
							} else {
								$import_fail[] = $row['compensation_id'];
							}								
						} else {
							$import_fail[] = $row['compensation_id'];
						}					
					}		
					if(!empty($import_success)) {
						$message .= implode(',', $import_success).'修改运费成功！';
					}
					if(!empty($import_fail)) {
						$message .= implode(',', $import_fail).'修改运费失败，请检查数据后再导入！';
					}			
				}			
      		}			
		}		
	}	
	$smarty->assign('message',$message);
}else if($_REQUEST['act'] == 'change_claim') {
	$claim = $_POST['claim'];
	$compensation_id_arr = explode('_', $claim);
	$sql = "update ecshop.claims_settlement set is_claim = 1 
			where compensation_id ". db_create_in($compensation_id_arr);
	$result = $db -> query($sql);
	if($result == true) {
		$response['result'] = 1;
	}
	echo json_encode($response);
	exit();

}else {
	$sql = $sql_basic . " AND responsible_party != 'WZTK'  AND cs.created_stamp >= date_sub(curdate(),interval 30 day)
			GROUP BY cs.compensation_id
			order by created_stamp";
	$claims_settlements = $db->getAll($sql);
	
	$total = sizeof($claims_settlements);
	$page = intval($_GET['page']);
    $page = max(1, $page);
	$limit = 20;
	$offset = $limit * ($page-1);		
	$sql_offset = $sql . " LIMIT {$limit} OFFSET {$offset} ";
	$claims_settlements = $db->getAll($sql_offset);
		
	foreach($claims_settlements as $key=>$item) {
		$compensation_type_key = $item['compensation_type'];
		$claims_settlements[$key]['compensation_type'] = $compensation_type_list[$compensation_type_key];
		$claims_settlements[$key]['can_modify'] = true;
		if($item['created_stamp'] < $date) {
			$claims_settlements[$key]['can_modify'] = false;
		}
		$order_sn_temp = $claims_settlements[$key]['order_sn'];
		$created_stamp_temp = $claims_settlements[$key]['created_stamp'];
		$order_stamp=$order_sn_temp."_".$created_stamp_temp;
		$claims_settlements[$key]['order_stamp']= $order_stamp;
    	if(isset($row_span[$order_stamp]) && ($row_span['order_sn'] == $order_sn_temp) && ($row_span['created_stamp'] == $created_stamp_temp)){
    		$row_span[$order_stamp] = $row_span[$order_stamp] + 1; 
		}else{
    		$row_span[$order_stamp] =  1;
    		$row_span['order_sn'] =  $claims_settlements[$key]['order_sn'];
    		$row_span['created_stamp'] =  $claims_settlements[$key]['created_stamp'];
    	}		    	
	}
	$smarty->assign('row_span',$row_span);	
	$pagination = new Pagination($total, $limit, $page, 'page', $url = 'claims_settlement.php', null, $extra_params);		
	$smarty->assign('claims_settlements',$claims_settlements);
	$smarty->assign('pagination', $pagination->get_simple_output());  // 分页
}

function get_condition(){
	extract($_REQUEST);
	$condition = "";
	if(isset($_REQUEST['order_sn']) && trim($_REQUEST['order_sn']) != ''){
		$condition .= "  AND eoi.order_sn = '".mysql_escape_string(trim($_REQUEST['order_sn']))."'";
	}
	if(isset($_REQUEST['taobao_order_sn']) && trim($_REQUEST['taobao_order_sn']) != ''){
		$condition .= "  AND eoi.taobao_order_sn = '".mysql_escape_string(trim($_REQUEST['taobao_order_sn']))."'";
	}
	if(isset($_REQUEST['start_stamp']) && trim($_REQUEST['start_stamp']) != ''){
		$start_time = $_REQUEST['start_stamp']." 00:00:00";
		$condition .= " AND cs.created_stamp >= '".mysql_escape_string(trim($start_time))."'";
	}
	if(isset($_REQUEST['end_stamp']) && trim($_REQUEST['end_stamp']) != ''){
		$end_time = $_REQUEST['end_stamp']." 23:59:59";
		$condition .= " AND cs.created_stamp <= '".mysql_escape_string(trim($end_time))."'";
	}
	if(isset($_REQUEST['responsible_party']) && trim($_REQUEST['responsible_party']) != 'ALL') {
		$condition .= " AND cs.responsible_party = '".mysql_escape_string(trim($_REQUEST['responsible_party']))."'";		
	} else {
		$condition .= " AND responsible_party != 'WZTK' ";
	}
	if(isset($_REQUEST['compensation_type']) && trim($_REQUEST['compensation_type']) != 'ALL') {
		$condition .= " AND cs.compensation_type = '".mysql_escape_string(trim($_REQUEST['compensation_type']))."'";		
	}	
	if(isset($_REQUEST['is_claim']) && trim($_REQUEST['is_claim']) != 'ALL') {
		$condition .= " AND cs.is_claim = '".mysql_escape_string(trim($_REQUEST['is_claim']))."'";		
	}
	if(isset($_REQUEST['shopnick']) && trim($_REQUEST['shopnick']) != '') {
		$condition .= " AND d.name = '".mysql_escape_string(trim($_REQUEST['shopnick']))."'";		
	}
	if(empty($_REQUEST['start_stamp']) && empty($_REQUEST['end_stamp'])) {
		$condition .= " AND cs.created_stamp >= date_sub(curdate(),interval 30 day) ";
	}
	if(isset($_REQUEST['type']) && trim($_REQUEST['type']) != 'ALL'){
		$condition .= " AND md.type = '".mysql_escape_string(trim($_REQUEST['type']))."'";
	}
	return $condition;
	
}

function readExcel($tpl) {
	$excel_info = array();
	$uploader = new Helper_Uploader ();
	$max_size = $uploader->allowedUploadSize (); // 允许上传的最大值
	if (! $uploader->existsFile ( 'excel' )) {
		$excel_info['error_info'] = '没有选择上传文件，或者文件上传失败';
		return $excel_info;

	} 
	// 取得要上传的文件句柄
	$file = $uploader->file ( 'excel' );		
	// 检查上传文件
	if (! $file->isValid ( 'xls, xlsx', $max_size )) {
		$excel_info['error_info'] = '非法的文件! 请检查文件类型类型(xls, xlsx), 并且系统限制的上传大小为' . $max_size / 1024 / 1024 . 'MB';
		return $excel_info;
	} 		
	// 读取excel
	$result = excel_read ( $file->filepath (), $tpl, $file->extname (), $failed );
	$excel_info['result'] = $result;
	if (! empty ( $failed )) {
		$excel_info['error_info'] = reset ( $failed );
		return $excel_info;
	}	
	return $excel_info;
}

//理赔比例
$compensation_sql = "
	SELECT t.nick, 
		MAX(ywz_compensation_amount) as ywz_compensation_amount,
		MAX(compensation_amount) as compensation_amount,
		MAX(order_amount) AS order_amount,  
		round(MAX(ywz_compensation_amount)/MAX(order_amount)*1000, 4) AS ywz_compensation_proportion,  
		round(MAX(compensation_amount)/MAX(order_amount)*1000, 4) AS compensation_proportion
	FROM (
		SELECT c.taobao_shop_conf_id, c.nick, 0 as order_amount, ifnull(SUM(if(cs.responsible_party = 'YWZ', cs.compensation_amount, 0)), 0) ywz_compensation_amount, ifnull(SUM(cs.compensation_amount), 0) AS compensation_amount
		FROM ecshop.claims_settlement cs force index(created_stamp)
		INNER JOIN ecshop.ecs_order_info o ON cs.order_id = o.order_id
	  	INNER JOIN ecshop.taobao_shop_conf c on o.party_id = c.party_id and o.distributor_id = c.distributor_id
		WHERE cs.created_stamp >= CONCAT(DATE_FORMAT(NOW(),'%Y-%m'),'-01') AND cs.is_delete = 0 AND o.party_id = {$_SESSION['party_id']}
		GROUP BY c.taobao_shop_conf_id
		UNION ALL
		SELECT c.taobao_shop_conf_id, c.nick, IFNULL(SUM(order_amount), 0) AS order_amount, 0 as ywz_compensation_proportion, 0 AS compensation_amount
		FROM ecshop.ecs_order_info o force index(order_info_multi_index)
	  	INNER JOIN ecshop.taobao_shop_conf c on o.party_id = c.party_id and o.distributor_id = c.distributor_id
		WHERE order_time >= DATE_SUB(NOW(),INTERVAL day(last_day(now())) day) AND order_type_id = 'SALE' AND order_status != 2 AND o.party_id = {$_SESSION['party_id']}
		GROUP BY c.taobao_shop_conf_id 
	) AS t
	GROUP BY t.taobao_shop_conf_id
	HAVING compensation_amount > 0 AND order_amount > 0
	";
$compensation_proportion_list = $slave_db->getAll($compensation_sql);
$smarty->assign('compensation_proportion_list', $compensation_proportion_list);

$smarty->assign('after_sale_type',$after_sale_type);
$smarty->assign('order_sn',$_REQUEST['order_sn']);
$smarty->assign('taobao_order_sn',$_REQUEST['taobao_order_sn']);
$smarty->assign('start_stamp',$_REQUEST['start_stamp']);
$smarty->assign('end_stamp',$_REQUEST['end_stamp']);
$smarty->assign('responsible_party',$_REQUEST['responsible_party']);
$smarty->assign('compensation_type',$_REQUEST['compensation_type']);
$smarty->assign('is_claim',$_REQUEST['is_claim']);
$smarty->assign('shopnick',$_REQUEST['shopnick']);
$smarty->assign('type', $_REQUEST['type']);

$smarty->assign('can_view_action',$can_view_action);
$smarty->assign('message',$message);
$smarty->assign('responsible_party_list',$_CFG['adminvars']['responsible_party']);
$smarty->assign('is_claim_list',$is_claim_list);
$smarty->assign('compensation_type_list',$compensation_type_list);
$smarty->display('claims_settlement.html');  
?>