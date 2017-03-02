<?php
/**
ALL HAIL SINRI EDOGAWA!
我らをこころみにあわせず、悪より救いいだしたまえ。
**/

//【售后处理总表】、【店长】、【客服】、【物流】、【财务】界面，设置对应的查看权限

define('IN_ECS', true);
require_once('../includes/init.php');
//admin_priv('kf_order_entry');//admin_priv('xxx','', false) 
require_once(ROOT_PATH . 'includes/lib_order.php');
require_once(ROOT_PATH . 'includes/cls_json.php');
include_once(ROOT_PATH . 'admin/function.php'); 
// include_once(ROOT_PATH . 'admin/includes/lib_order_mixed_status.php');//By Sinri
require_once(ROOT_PATH . 'includes/helper/array.php');

require_once('postsale_function.php');

if(!isDevPrivUser($_SESSION['admin_name'])){
	admin_priv('postsale_statistics');
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>售后统计中心</title>
		<script type="text/javascript" src="../js/style/zapatec/utils/zapatec.js"></script>
		<script type="text/javascript" src="../js/style/zapatec/zpcal/src/calendar.js"></script>
		<script type="text/javascript" src="../js/style/zapatec/zpcal/lang/calendar-en.js"></script>
		<script type="text/javascript" src="../misc/jquery.js"></script>
		<script type="text/javascript" src="../misc/jquery.ajaxQueue.js"></script>
		<link rel="stylesheet" href="../js/style/zapatec/zpcal/themes/winter.css" />
		<style type="text/css">
		.div-dialog-mask{
		    background: #B6FFB5;
		   	border-style: inset;
		    z-index:1987; 
		    position: fixed; /*虽然IE6不支持fixed，这里依然可以兼容ie6*/
		    left: 10%; 
		    top: 15%; 
		    width: 80%; 
		    height: 70%; 
		    overflow: hidden;
		}
		/*ie6 遮罩select*/
		.div-dialog-mask iframe{
		    width:98%;
		    height:90%;
		    position:absolute;
		    top:5%;
		    left:1%;
		    z-index:-1;
		    border: none;
		}
		</style>
  		<style type="text/css">
  			p {
  				padding: 0px;
  				margin: 2px;
  			}
	  		table, td {
				border: 1px solid gray;
				border-collapse:collapse;
				font-size: 13px;
				text-align: center;
				/* padding: 5px; */
			}
			th {
				background-color: #2899D6;/* #6CB8FF; */
				color: #EEEEEE;
				border: 1px solid gray;
				border-collapse:collapse;
				padding: 5px;
			}

			.count_table table, td{
				border: 1px solid #EEEEEE;
				border-collapse:collapse;
				font-size: 13px;
				text-align: left;
			}

			span.keikoku{
				color: red;
			}


  			div.waku{
  				/* padding: 5px;*/
  				margin-bottom: 5px;
  			}
			table.detail_table {
				border: 1px solid gray;
				border-collapse:collapse;
			}
			table.detail_table td {
				border: 1px solid gray;
				border-collapse:collapse;
				padding: 4px;
				text-align: center;
			}
			table.detail_table th {
				border: 1px solid gray;
				border-collapse:collapse;
				padding: 4px;
				font-size: 15px;
				text-align: center;
			}

			div.tab_board {
				background-color: #EEEEEE;
				padding-top: 12px;
				padding-bottom: 8px;
				margin: 0px;
			}
			span.tab_on {
				background-color: #78E7FF;
				padding: 9px;
				/* border: 1px solid gray; */
				font-weight: bold;
			}
			span.tab_off {
				background-color: #6CB8FF;
				padding: 9px;
				/* border: 1px solid gray; */
				font-weight: bold;
			}
			a.tab:link {color:#FFFFFF;text-decoration: none;}		/* 未被访问的链接 */
			a.tab:visited {color:#FFFFFF;text-decoration: none;}	/* 已被访问的链接 */
			a.tab:hover {color:#770000;text-decoration: none;}	/* 鼠标指针移动到链接上 */
			a.tab:active {color:#FF0000;text-decoration: none;}	/* 正在被点击的链接 */

			a.type_a:link {color:#000000;text-decoration: none;}		/* 未被访问的链接 */
			a.type_a:visited {color:#000000;text-decoration: none;}	/* 已被访问的链接 */
			a.type_a:hover {color:#000000;text-decoration: none;}	/* 鼠标指针移动到链接上 */
			a.type_a:active {color:#000000;text-decoration: none;}	/* 正在被点击的链接 */

			p.captain {
				/* color: #5555EE; */
				font-size: 16px;
				padding: 0px;
				margin: 5px;
			}

			ul.tabnav li{
				float:left;
				display:inline;
				margin-left:2px;
			}
			.tabnav li a{
				background-color:#2899D6;
				border:2px solid #2899D6;
				color:#EEEEEE;
				display:block;
				padding:5px 10px 5px 10px;/* top right bottom left */
				line-height:20px;
				float:left;
				font-weight:bold;
				text-decoration: none;
			}

			.tabnav li a.active,
			.tabnav li a:hover{
				color: #2899D6;
				background-color:#fff;
				border-bottom:2px solid #fff;
				_position:relative;
				text-decoration: none;
			}
			#tab_roles .tabnav{
				border-bottom:2px solid #2899D6;
				height:32px;
				_overflow:hidden;
			}
			div.mynewtabs {
				margin-bottom: 0px;
			}

			#hide_pre_div {
				display: none;
			}
  		</style>
  	</head>
  	<body>
  	<div id='hide_pre_div'>
  	<pre>
  		<?php 
  		//print_r($_SESSION);
  		if($_REQUEST['act']=='query'){
	  		$parties=get_party_with_all_children($_SESSION['party_id']);
	  		print_r($parties);
	  		$time_start = microtime_float();
	  		$stat=stat_all($parties);
	  		$time_end = microtime_float();
			$time = $time_end - $time_start;
			$timescale= "Up to ". date("Y-m-d H:i:s")." cost time for query database is ".$time;
	  		print_r($stat); 
  		}
  		?>
  	</pre>
  	</div>
  	<div class="waku">
  		<h1>售后统计</h1>
  		<p>
  			<a href="sale_support_status.php?act=query">开始统计</a>
  			<?php if($timescale) echo $timescale; ?>
  		</p>
  		<p>
  			查询的是线上实时售后数据。请不要频繁执行统计！
  		</p>
  		<table class="detail_table">
  			<tr>
  				<th rowspan="2">业务组</th>
  				<th colspan="9">沟通</th>
  				<th rowspan="2">追回</th>
  				<th colspan="9">退换货</th>
  				<th colspan="4">退款</th>
  			</tr>
  			<tr>
  				<th>统计</th>
  				<th>客服</th>
  				<th>上海</th>
  				<th>东莞</th>
  				<th>财务</th>
  				<th>外包</th>
  				<th>北京</th>
  				<th>采购</th>
  				<th>店长</th>

 				<th>统计</th>
 				<th>新换货</th>
 				<th>新退货</th>
 				<th>换货已入库</th>
 				<th>退货已入库</th>
 				<th>等待寄回</th>
 				<th>等待入库</th>
 				<th>收货待验</th>
 				<th>拒绝待回访</th>
 				<!--
 				<th>已经退款-废弃</th>
 				<th>待退款-废弃</th>
				-->

 				<th>退款统计</th>
 				<th>待客服审核</th>
 				<th>待物流审核</th>
 				<th>待财务审核</th>
 				<!--
 				<th>待财务付款-废弃</th>
 				-->
  			</tr>
  			<?php

  			if($stat && count($stat)>0){
  				foreach ($stat as $party_id => $stat_line) {
  					echo "<tr>";
  					echo "<td>".$stat_line['party_name']."</td>";

					echo "<td>".$stat_line['message_30']."</td>";
  					echo "<td>".$stat_line['message_31']."</td>";
  					echo "<td>".$stat_line['message_32']."</td>";
  					echo "<td>".$stat_line['message_33']."</td>";
  					echo "<td>".$stat_line['message_34']."</td>";
  					echo "<td>".$stat_line['message_35']."</td>";
  					echo "<td>".$stat_line['message_36']."</td>";
  					echo "<td>".$stat_line['message_37']."</td>";
  					echo "<td>".$stat_line['message_38']."</td>";

  					echo "<td>".$stat_line['returncancel']."</td>";

  					echo "<td>".$stat_line['service_0']."</td>";
  					echo "<td>".$stat_line['service_1']."</td>";
  					echo "<td>".$stat_line['service_2']."</td>";
  					echo "<td>".$stat_line['service_3']."</td>";
  					echo "<td>".$stat_line['service_4']."</td>";
  					echo "<td>".$stat_line['service_5']."</td>";
  					echo "<td>".$stat_line['service_6']."</td>";
  					echo "<td>".$stat_line['service_7']."</td>";
  					echo "<td>".$stat_line['service_8']."</td>";
  					// echo "<td>".$stat_line['service_9']."</td>";
  					// echo "<td>".$stat_line['service_10']."</td>";

  					echo "<td>".$stat_line['refund_20']."</td>";
  					echo "<td>".$stat_line['refund_21']."</td>";
  					echo "<td>".$stat_line['refund_22']."</td>";
  					echo "<td>".$stat_line['refund_23']."</td>";
  					// echo "<td>".$stat_line['refund_24']."</td>";
  					
  					echo "</tr>";
  				}
  			}else{
  			?>
  			<tr>
  				<td colspan="24">Nothing to display (╯‵□′)╯︵┻━┻</td>
  			</tr>
  			<?php
  			}
  			?>
  		</table>
  	</div>
  		
  	</body>
</html>

<?php

function stat_getParties($party_ids=null){
	global $db;
	$pids="";
	if($party_ids && is_array($party_ids)){
		$pids=implode(',', $party_ids);
		$pids=" and party_id in (".$pids.") ";
	}
	$sql="SELECT party_id,`name` party_name from romeo.party WHERE `STATUS`='ok' and IS_LEAF='Y' and SYSTEM_MODE=2 ".$pids;
	// echo $sql;
	$parties=$db->getAll($sql);
	return $parties;
}

function stat_service_base($party_id,$stat_mode){
	global $db;
	$sql="SELECT
		-- distinct s.service_id 
		count(DISTINCT s.service_id)
	FROM
		ecshop.service s
	INNER JOIN ecshop.ecs_order_info o ON s.order_id = o.order_id
	LEFT JOIN romeo.refund r ON r.ORDER_ID = CONVERT(s.back_order_id USING utf8)
	WHERE
		(
			/* If it has come to refund, not a postsale service to do */
			o.pay_status != '4'
			AND r.REFUND_ID IS NULL
		)
	AND s.is_complete = '0'
	AND(
		/* not checked. and not called for some special (BAKA) party */
		(
			s.inner_check_status = 0
			AND s.outer_check_status = 0
		)
		OR(
			s.party_id IN(65585)
			AND s.service_call_status != 2
		)
	)/* base filter */
	AND s.party_id = {$party_id}
	".it_is_the_glory_of_God_to_conceal_a_thing()."
	-- AND s.apply_datetime >= '2014-04-01'
	/* all the filters */
	";
	$conditions="";
	switch ($stat_mode) {
		case 0:
			//all
			$conditions.="";
			break;
        case 1:
        //unconfirmed service - change
            $conditions.=" AND (s.service_status=0) AND (s.service_type=1) ";
            break;
        case 2:
        //unconfirmed service - return
            $conditions.=" AND (s.service_status=0) AND (s.service_type=2) ";
            break;
        case 3:
        //checked service wait to call - change
            // $conditions.=" AND ((s.outer_check_status=23 OR s.inner_check_status=32) AND s.service_call_status=1 AND (s.service_type=1)) ";
        //Change-returned no change order
            $conditions.=" AND ((s.outer_check_status=23 OR s.inner_check_status=32) AND (s.change_order_id is not NULL and s.change_order_id!=0) AND (s.service_type=1)) ";
            break;
        case 4:
        //checked service wait to call - return
            // $conditions.=" AND ((s.outer_check_status=23 OR s.inner_check_status=32) AND s.service_call_status=1 AND (s.service_type=2)) ";
        //Return-returned no refund order
            $conditions.=" AND ((s.outer_check_status=23 OR s.inner_check_status=32) AND (s.service_type=2)) ";
            break;
        case 5:
        // 物流：等待消费者寄回货物
            $conditions.=" AND (s.back_shipping_status=5) ";
            break;
        case 6:
        //unreturned service
            $conditions.=" AND (s.service_status=1 AND s.back_shipping_status=0) ";
            break;
        case 7:
        //unchecked returned service
            $conditions.=" AND (s.service_status=1 AND s.back_shipping_status=12 AND (s.outer_check_status=0 and s.inner_check_status=0)) ";
            break;
        case 8:
        //service denied wait to call
            $conditions.=" AND (s.service_status=3 AND s.service_call_status!=2) ";
            break;
        case 9:
        //return service refunded -> X
            $conditions.=" AND (s.service_pay_status=2 AND s.service_type=2) ";
            break;
        case 10:
        //checked but not paid - return NOT SO CORRECT -> X
            $conditions.=" AND ((s.outer_check_status=23 OR s.inner_check_status=32) AND s.service_call_status=2 AND s.service_pay_status=0 AND s.service_type=2) ";
            break;
        default:
            $conditions.=" AND 0 ";
            //die("虽然不知道你是何方妖孽但是你看起来很厉害的样子道高一尺魔高一丈冤冤相报何时了于是就死在这里吧呵呵呵呵 —— —— 邪恶的大鲵");
            break;
	}
	return $db->getOne($sql.$conditions);
	// return $db->getCol($sql.$conditions);
}

function stat_refund_base($party_id,$stat_mode){
	global $db;
	$sql="SELECT
		count(DISTINCT r.refund_id)
	FROM
		romeo.refund r
	LEFT JOIN ecshop.ecs_order_info o ON r.order_id = o.order_id
	WHERE
		1 ".but_the_honour_of_kings_is_to_search_out_a_matter()."
	AND o.party_id = '{$party_id}'
	AND
	o.pay_status != '4'
	AND r. STATUS != 'RFND_STTS_EXECUTED'
	AND r. STATUS != 'RFND_STTS_CANCELED'
	AND r. STATUS != 'RFND_STTS_CHECK_OK' ";
	$conditions="";
    switch ($stat_mode) {
    	case 20:
    		break;
        case 23:
            //CW 财务审核
            $conditions.=" AND (r.STATUS='RFND_STTS_INIT' OR r.STATUS='RFND_STTS_IN_CHECK') 
                            AND r.CHECK_DATE_2 is not null AND r.CHECK_DATE_3 is null ";
            break;
        case 24:
            //CW 财务付款 废弃
            $conditions.=" AND (r.STATUS='RFND_STTS_CHECK_OK') AND (r.EXECUTE_DATE is null) ";
            break;
        case 22:
            //WL 物流审核
            $conditions.=" AND (r.STATUS='RFND_STTS_INIT' OR r.STATUS='RFND_STTS_IN_CHECK') 
                            AND r.CHECK_DATE_1 is not null AND r.CHECK_DATE_2 is null ";
            break;
        case 21:
            //KF 客服审核
            $conditions.=" AND (r.STATUS='RFND_STTS_INIT' OR r.STATUS='RFND_STTS_IN_CHECK') 
                            AND r.CHECK_DATE_1 is null ";
            break;
        default:
            $conditions.=" AND 0 ";
            break;
    }
    return $db->getOne($sql.$conditions);
}

function stat_message_base($party_id,$stat_mode){
	global $db;
	$sql="SELECT
			count(DISTINCT ssm.order_id)
		FROM
			ecshop.sale_support_message ssm 
		LEFT JOIN ecshop.ecs_order_info o ON ssm.order_id = o.order_id
		WHERE
			o.party_id = {$party_id} and
			ssm.sale_support_message_id IN(
			SELECT
				MAX(
					issm.sale_support_message_id
				)
			FROM
				ecshop.sale_support_message issm
			WHERE
				issm.order_id = ssm.order_id
		) ".hide_message_long_age();
	$conditions="";
	switch ($stat_line) {
		case 40:
			$conditions.=" and (
				(
					ssm.program IS NULL
					OR ssm.program = ''
					OR(
						ssm.program = '追回'
						AND o.shipping_status != 11
					)
				)
			)";
		case 30:
			$conditions.=" and (
				(
					ssm.next_process_group IS NOT NULL
					AND ssm.next_process_group != ''
				)
			)";
			break;
		case 31:
		//KF
			$conditions.=" ssm.next_process_group = 'KF' or ssm.next_process_group = 'FXKF' ";
			break;
		case 32:
		//SHWL	
			$conditions.=" ssm.next_process_group = 'SHWL' ";
			break;
		case 33:
		//DGWL	
			$conditions.=" ssm.next_process_group = 'DGWL' ";
			break;
		case 34:
		//CW	
			$conditions.=" ssm.next_process_group = 'CW' ";
			break;
		case 35:
		//WBWL	
			$conditions.=" ssm.next_process_group = 'WBWL' ";
			break;
		case 36:
		//BJWL	
			$conditions.=" ssm.next_process_group = 'BJWL' ";
			break;
		case 37:
		//CG	
			$conditions.=" ssm.next_process_group = 'CG' ";
			break;
		case 38:
		//DZ
			$conditions.=" ssm.next_process_group = 'DZ' ";
			break;
		
		default:
			# code...
			break;
	}
}

function stat_all($parties=null){
	if($parties==null || !is_array($parties)){
		$parties=stat_getParties();
	}else{
		$parties=stat_getParties($parties);
	}
	$result=array();
	foreach ($parties as $pindex => $pline) {
		if($pline['party_name']!=''){
			$result[$pline['party_id']]=array(
				'party_id'=>$pline['party_id'],
				'party_name'=>$pline['party_name']
			);
			for ($i=0; $i <11 ; $i++) { 
				$c=stat_service_base($pline['party_id'],$i);
				$result[$pline['party_id']]['service_'.$i]=$c;
			}
			for ($i=20; $i <25 ; $i++) { 
				$c=stat_refund_base($pline['party_id'],$i);
				$result[$pline['party_id']]['refund_'.$i]=$c;
			}
			for ($i=30; $i <39 ; $i++) { 
				$c=stat_refund_base($pline['party_id'],$i);
				$result[$pline['party_id']]['message_'.$i]=$c;
			}

			$c=stat_refund_base($pline['party_id'],40);
			$result[$pline['party_id']]['returncancel']=$c;
		}
	}
	return $result;
}

?>