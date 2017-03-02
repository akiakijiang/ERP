<?php
/**
ALL HAIL SINRI EDOGAWA!
われらに罪を犯すものをわれらが許すごとく、われらの罪をも許したまへ。
**/

define('IN_ECS', true);
require_once('../includes/init.php');
require_once(ROOT_PATH . 'includes/lib_order.php');
require_once(ROOT_PATH . 'includes/cls_json.php');
include_once(ROOT_PATH . 'admin/function.php'); 
// include_once(ROOT_PATH . 'admin/includes/lib_order_mixed_status.php');//By Sinri
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once('postsale_function.php');

global $db;

$act=empty($_REQUEST['act'])?'neet':trim($_REQUEST['act']);
$search_type=empty($_REQUEST['search_type'])?'0':trim($_REQUEST['search_type']);
$search_text=empty($_REQUEST['search_text'])?'':trim($_REQUEST['search_text']);

if($act=='search'){
	//echo "act=$act search_type=$search_type search_text=$search_text<br>";
	/**
	Let us try a formatted way to find the postsale data
	first, try to find the taobao tid and order
	**/
	$sql_0="SELECT str.* FROM ecshop.sync_taobao_refund str WHERE ";
	$sql_1="SELECT o.order_id FROM ecshop.ecs_order_info o WHERE ";
	switch ($search_type) {
		case 2:
			$sql_0_con_type=" str.tid='$search_text' ";
			break;
		case 3:
			$sql_0_con_type=" str.refund_id='$search_text' ";
			break;
		case 8:
			$sql_0_con_type=" str.sid='$search_text' ";
			break;
		case 4:
			$sql_0_con_type=" str.buyer_nick='$search_text' ";
			break;
		case 1:
			$sql_1_con_type=" o.order_id=$search_text ";
			break;
		case 0:
			$sql_1_con_type=" o.order_sn='$search_text' ";
			break;
		case 5:
			$sql_1_con_type=" o.consignee='$search_text' ";
			break;
		case 6:
			$sql_1_con_type=" (o.tel='$search_text' OR o.mobile='$search_text') ";
			break;
		case 7:
			$sql_2="SELECT ros.ORDER_ID FROM romeo.shipment rs
					LEFT JOIN romeo.order_shipment ros ON rs.SHIPMENT_ID=ros.SHIPMENT_ID
					WHERE rs.TRACKING_NUMBER='$search_text';";
			break;
		default:
			$sql_0_con_type=0;
			break;
	}
	$the_mixed_postsale=array();
	if(!empty($sql_0_con_type)){
		//echo "SQL:<br>".$sql_0.$sql_0_con_type."<br>";
		$result_taobao_order_sn=$db->getAll($sql_0.$sql_0_con_type);
	}else if(!empty($sql_1_con_type)){
		//echo "SQL:<br>".$sql_1.$sql_1_con_type."<br>";
		$result_order_id=$db->getCol($sql_1.$sql_1_con_type);
	}else{
		//echo "SQL:<br>".$sql_2."<br>";
		$result_order_id=$db->getAll($sql_2);
	}
	if(!empty($result_taobao_order_sn)){
		foreach ($result_taobao_order_sn as $no => $tid_line) {
			$the_mixed_postsale[$tid_line['tid']]['taobao_refunds'][$tid_line['refund_id']]=$tid_line;
			$r=get_orders_by_taobao_order_sn($tid_line['tid']);
			if(!empty($r)){
				foreach ($r as $key => $line) {
					$the_mixed_postsale[$tid_line['tid']]['orders'][$line['order_id']]['order_info']=$line;
				}
			}
		}
	}
	if(!empty($result_order_id)){
		$array_tbosn=array();
		foreach ($result_order_id as $no => $oid) {
			$r=get_order_by_order_id($oid);
			$tbosn=get_pure_taobao_order_sn($r['taobao_order_sn']);
			if(!empty($tbosn)){
				$array_tbosn[]=$tbosn;
			}
		}
		foreach ($result_order_id as $no => $oid) {
			$r=get_order_by_order_id($oid);
			if(empty($r))continue;
			$tbosn=get_pure_taobao_order_sn($r['taobao_order_sn']);
			if(empty($tbosn)){
				$order_relation_rec=get_order_relation_path($r['order_id']);
				if(empty($order_relation_rec)){
					$tbosn='I'.$r['order_id'];
				}else{
					$tbosn='I'.$order_relation_rec['root_order_id'];
				}
			}
			$the_mixed_postsale[$tbosn]['orders'][$r['order_id']]['order_info']=$r;
		}
		if(!empty($array_tbosn)){
			foreach ($array_tbosn as $no => $tbosn) {
				$r=$db->getAll($sql_0." str.tid='$tbosn';");
				if(!empty($r)){
					foreach ($r as $key => $line) {
						$the_mixed_postsale[$tbosn]['taobao_refunds'][$line['refund_id']]=$line;
					}
				}
			}
		}
	}
	//now we get $the_mixed_postsale
	//print_r($the_mixed_postsale);
	//get_services_of_one_order
	foreach ($the_mixed_postsale as $root => $group1) {
		foreach ($group1['orders'] as $order_id => $order) {
			$r=get_services_of_one_order($order_id);
			$the_mixed_postsale[$root]['orders'][$order_id]['services']=$r;
			$r=get_refunds_of_one_order_deep($order_id);
			$the_mixed_postsale[$root]['orders'][$order_id]['refunds']=$r;
			$r=get_sale_support_messages_by_order_id($order_id);
			$the_mixed_postsale[$root]['orders'][$order_id]['messages']=$r;
		}
	}

}

/**
PHP OVER
**/
?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>售后处理中心档案馆</title>
		<script type="text/javascript" src="../js/style/zapatec/utils/zapatec.js"></script>
		<script type="text/javascript" src="../js/style/zapatec/zpcal/src/calendar.js"></script>
		<script type="text/javascript" src="../js/style/zapatec/zpcal/lang/calendar-en.js"></script>
		<style type="text/css">
		fieldset {
			font-size: 15px;
			margin-top: 5px;
			margin-bottom: 5px;
			/*background-color: white;*/
			border: gray 1px solid #DFEFFB;
		}
		div.group1 {
			border: gray 1px solid #DFEFFB;
			margin: 10px;
		}
		div.group2 {
			margin: 10px;
		}
		div.group3 {
			margin: 10px;
		}
		table, td {
			border: 1px solid gray;
			border-collapse:collapse;
			font-size: 15px;
			padding: 5px;
			border: gray 1px solid;
			border-collapse:collapse;
			text-align: center;
		}
		th {
			background-color: #2899D6;/* #6CB8FF; */
			color: #EEEEEE;
			border: 1px solid gray;
			border-collapse:collapse;
			font-size: 15px;
			padding-left: 5px;
			padding-right: 5px;
			border: gray 1px solid;
			border-collapse:collapse;
		}
		</style>
	</head>
	<body>
		<div>
			<form>
				Search postsale history with this type,
				<select name="search_type" id="search_type">
					<option id="st0" value='0' <?php if ($search_type=='0') { echo "selected='selected' "; } ?> >ERP订单号</option>
					<option id="st1" value='1' <?php if ($search_type=='1') { echo "selected='selected' "; } ?> >ERP订单ID</option>
					<option id="st2" value='2' <?php if ($search_type=='2') { echo "selected='selected' "; } ?> >淘宝订单号</option>
					<option id="st3" value='3' <?php if ($search_type=='3') { echo "selected='selected' "; } ?> >淘宝退款编号</option>
					<option id="st4" value='4' <?php if ($search_type=='4') { echo "selected='selected' "; } ?> >顾客ID(旺旺号)</option>
					<option id="st5" value='5' <?php if ($search_type=='5') { echo "selected='selected' "; } ?> >顾客名称</option>
					<option id="st6" value='6' <?php if ($search_type=='6') { echo "selected='selected' "; } ?> >顾客手机号</option>
					<option id="st7" value='7' <?php if ($search_type=='7') { echo "selected='selected' "; } ?> >发货运单号</option>
					<option id="st8" value='8' <?php if ($search_type=='8') { echo "selected='selected' "; } ?> >退回运单号</option>
				</select>
				as
				<input type='text' name='search_text' value="<?php echo $search_text; ?>">
				, I 
				<input type='submit' name='submit' value='confirm'>
				it now.
				<input type='hidden' name='act' value='search'>
			</form>
		</div>
		<div>
			<?php
			if($the_mixed_postsale){
				echo "<textarea>";
				print_r($the_mixed_postsale);
				echo "</textarea>";
			}
			?>
		</div>
		<div>
			<?php
			if($the_mixed_postsale){
				echo "<div class='group1' id='group1_count'>";
				echo "<fieldset><legend>组合统计</legend>共计找到了".count($the_mixed_postsale)."组销售订单。</fieldset>";
				echo "</div>";
				foreach ($the_mixed_postsale as $tbosn => $group1) {
					echo "<div class='group3' id='group1_".$tbosn."'>";
					echo "<fieldset>";
					if(substr($tbosn, 0,1)=='I'){
						echo "<legend>内部根订单ID为".substr($tbosn, 1)."</legend>";
					}else{
						echo "<legend>外部订单号为$tbosn</legend>";
					}
					echo "<div class='group3' id='group1_".$tbosn."_count'>";
					echo "<fieldset>";
					echo "<legend>统计</legend>";
					echo "共包含了".count($group1['taobao_refunds'])."项淘宝退款申请。共包含了".count($group1['orders'])."个ERP订单。";
					echo "</fieldset>";
					echo "</div>";
					if($group1['taobao_refunds'] && count($group1['taobao_refunds'])){
						echo "<div class='group3' id='group1_".$tbosn."_taobao_refunds'>";
						echo "<fieldset>";
						echo "<legend>淘宝退款</legend>";
						foreach ($group1['taobao_refunds'] as $taobao_refund_id => $taobao_refund_line) {
							echo "<div class='group3' id='group1_".$tbosn."_taobao_refunds_".$taobao_refund_id."'>";
							//print_r($taobao_refund_line);
							$title_tr="";
							$content_tr="";
							foreach ($taobao_refund_line as $key => $value) {
								$title_tr.="<th>".$key."</th>";
								$content_tr.="<td>".$value."</td>";
							}
							echo "<table>";
							echo "<tr>".$title_tr."</tr>";
							echo "<tr>".$content_tr."</tr>";
							echo "</table>";
							echo "</div>";
						}
						echo "</fieldset>";
						echo "</div>";
					}
					if($group1['orders'] && count($group1['orders'])){
						echo "<div id='group1_".$tbosn."_orders'>";
						foreach ($group1['orders'] as $order_id => $group2) {
							echo "<div class='group2' id='group1_".$tbosn."_orders_".$order_id."'>";
							//print_r($group2);
							echo "<fieldset>";
							echo "<legend>订单{$order_id}</legend>";
							echo "<div class='group3' id='group1_".$tbosn."_orders_".$order_id."_count'>";
							echo "<fieldset>";
							echo "<legend>订单信息</legend>";
							$title_tr="";
							$content_tr="";
							foreach ($group2['order_info'] as $key => $value) {
								$title_tr.="<th>".$key."</th>";
								$content_tr.="<td>".$value."</td>";
							}
							echo "<table>";
							echo "<tr>".$title_tr."</tr>";
							echo "<tr>".$content_tr."</tr>";
							echo "</table>";
							echo "</fieldset>";
							//echo "<div class='group3' id='group1_".$tbosn."_orders_".$order_id."_count'>";
							echo "<fieldset>";
							echo "<legend>订单售后操作统计</legend>";
							echo "包括沟通".count($group2['messages'])."条。退换".count($group2['services'])."条。退款".count($group2['refunds'])."条。";
							echo "</fieldset>";
							//echo "</div>";
							echo "<fieldset>";
							echo "<legend>订单售后沟通记录</legend>";
							echo "<table>";
							echo "<tr>
									<th style='width: 20%;'>时间</th>
									<th style='width: 10%;'>操作人</th>
									<th style='width: 20%;'>责任归属</th>
									<th style='width: 50%;'>内容</th>
								</tr>";
							foreach ($group2['messages'] as $no => $line) {
								echo "<tr>";
								echo "<td>".$line['created_stamp']."</td>";		
								echo "<td>".$line['send_by']."</td>";
								echo "<td>".(empty($line['next_process_group'])?"添加备忘":"要求".$line['next_process_group']."处理")."</td>";
								echo "<td>".$line['message']."</td>";
								echo "</tr>";		
							}
							echo "</table>";
							echo "</fieldset>";
							echo "<fieldset>";
							echo "<legend>订单退换货申请记录</legend>";
							//echo "<p>"."<a href=\"../sale_serviceV3.php?act=search&search_text=".$order_key_info['order_sn']."\" target='blank'>[已经建立的退换货申请]</a>"."</p>";
							echo "<table>";
							echo "<tr>
									<th>退换申请编号</th>
									<!--
									<th>申请人</th>
									<th>申请时间</th>
									-->
									<th>描述</th>
									<th>记录</th>
								</tr>";
							foreach ($group2['services'] as $sno => $service) {
								echo "<tr>";
								echo "<td>".$service['service_id']."</td>";
								echo "<td>
										用户 ".$service['user_name']."<!--[".$service['user_id']."]-->
										<br>
										于".$service['apply_datetime']."申请
										<br>
										理由：".$service['apply_reason']."
										<br>
										".get_service_line_status_description($service)."
									</td>";
								echo "<td>";
								$logs_byKF=get_service_log_for_service_id($service['service_id'],1);
								$logs_byXT=get_service_log_for_service_id($service['service_id'],0);
								if($logs_byKF){
									//echo "<p class='captain'>售后备注(客服记录的事情)</p>";
									echo "<table>";
									echo "<tr>
											<th>状态</th>
											<th>用户</th>
											<th>时间</th>
											<th>售后备注(客服记录的事情)</th>
										</tr>";
									foreach ($logs_byKF as $lid => $log) {
										if(!$log['is_remark'])continue;
										echo "<tr>";
										echo "<td>".$log['status_name']."</td>";
										echo "<td>".$log['log_username']."</td>";
										echo "<td>".$log['log_datetime']."</td>";
										echo "<td>".$log['log_note']."</td>";
										echo "</tr>";
									}
									echo "</table>";
								}else{
									echo "无售后备注(客服记录的事情)";
								}
								echo "<hr>";
								if($logs_byXT){
									//echo "<p class='captain'>操作记录(系统自动添加的记录)</p>";
									echo "<table>";
									echo "<tr>
											<th>状态</th>
											<th>用户</th>
											<th>时间</th>
											<th>操作记录(系统自动添加的记录)</th>
										</tr>";
									foreach ($logs_byXT as $lid => $log) {
										if($log['is_remark'])continue;
										echo "<tr>";
										echo "<td>".$log['status_name']."</td>";
										echo "<td>".$log['log_username']."</td>";
										echo "<td>".$log['log_datetime']."</td>";
										echo "<td>".$log['log_note']."</td>";
										echo "</tr>";
									}
									echo "</table>";
								}else{
									echo "无操作记录(系统自动添加的记录)";
								}
								echo "</td>";
								echo "</tr>";
							}
							echo "</table>";
							echo "</fieldset>";
							echo "<fieldset>";
							echo "<legend>订单退款申请记录</legend>";
							echo "<table>";
							echo "<tr>
									<th>退款申请编号</th>
									<th>申请人</th>
									<th>发起日</th>
									<th>金额</th>
									<th>状态</th>
								</tr>";
							foreach ($group2['refunds'] as $key => $refund) {
								echo "<tr>";
								echo "<td>"."<a href=\"../refund_view.php?refund_id=".$refund['REFUND_ID']."\" target='blank'>".$refund['REFUND_ID']."</a>"."</td>";
								echo "<td>".$refund['CREATED_BY_USER_LOGIN']."</td>";
								echo "<td>".$refund['CREATED_STAMP']."</td>";
								echo "<td>".sprintf("%01.2f",$refund['TOTAL_AMOUNT'])."</td>";
								echo "<td>".get_refund_next_responsor($refund)."</td>";
								echo "</tr>";
							}
							echo "</table>";
							echo "</fieldset>";
							echo "</fieldset>";
							echo "</div>";
						}
						echo "</div>";
					}
					echo "</fieldset>";
					echo "</div>";
				}
			}
			?>
		</div>


		<script type="text/javascript">//<![CDATA[
		/*
	      Zapatec.Calendar.setup({
	        weekNumbers       : false,
	        electric          : false,
	        inputField        : "start",
	        ifFormat          : "%Y-%m-%d",
	        daFormat          : "%Y-%m-%d"
	      });
	      Zapatec.Calendar.setup({
	        weekNumbers       : false,
	        electric          : false,
	        inputField        : "end",
	        ifFormat          : "%Y-%m-%d",
	        daFormat          : "%Y-%m-%d"
	      });
		*/
	    //]]>
		</script>
	</body>
</html>

