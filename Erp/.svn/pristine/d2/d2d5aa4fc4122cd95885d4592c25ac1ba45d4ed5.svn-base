<?php
	define('IN_ECS', true);
	require_once('../includes/init.php');
	require_once(ROOT_PATH . 'includes/lib_order.php');
	require_once(ROOT_PATH . 'includes/cls_json.php');
	include_once(ROOT_PATH . 'admin/function.php'); 
	//include_once(ROOT_PATH . 'admin/includes/lib_order_mixed_status.php');//By Sinri
	require_once(ROOT_PATH . 'includes/helper/array.php');

	require_once('postsale_function.php');

	$order_base='unknown';

	$order_id=(empty($_REQUEST['order_id'])?'':trim($_REQUEST['order_id']));

	if(empty($order_id)){
		$order_sn=(empty($_REQUEST['order_sn'])?'':trim($_REQUEST['order_sn']));
		$order_key_info=get_order_key_information_by_order_sn($order_sn);
		$order_base='order_sn';
	}else{
		$order_key_info=get_order_key_information_by_order_id($order_id);
		$order_base='order_id';
	}

	$is_show_only=(empty($_REQUEST['onlyshow'])?false:true);

	global $user_priv_list;
    global $sale_support_type_map;
    global $get_sync_taobao_refund_state_map;
    global $get_sync_tmall_refund_state_map;
    global $get_sync_taobao_fenxiao_refund_state_map;
    global $refund_status_name;

	/**
	退货退款，仅退款，换货，追回，录单补寄，退货不退款
	**/
	$plan_list = array(
		//'th' => '退货不退款',
		'tk' => '仅退款',
		'thtk' => '退货退款',
		'hh' => '换货',
		'zh' => '追回',
		'bj' => '录单补寄',
		'ms' => '无需处理'
	);
	/* 权限对应 */
	/*
	$user_priv_list = array(
		'LCZ'=> array('priv' => 'lcz_sale_support','value'=>'售后巡查'),
		'KF' => array('priv' => 'kf_postsale_support', 'value' => '客服'),
		'SHWL' => array('priv' => 'shwl_sale_support', 'value' => '上海物流'),
		'DGWL' => array('priv' => 'dgwl_sale_support', 'value' => '东莞物流'),
		'CW' => array('priv' => 'cw_sale_support', 'value' => '财务'),
		'DZ' => array('priv' => 'dz_sale_support', 'value' => '店长'),
		'CG' => array('priv' => 'cg_sale_support', 'value' => '采购')
	);

	$get_sync_taobao_refund_state_map = array(
	'SELLER_REFUSE_BUYER' => '已拒绝',
	'WAIT_SELLER_CONFIRM_GOODS' => '等待验货',
	'CLOSED' => '已关闭',
	'SUCCESS' => '已成功',
	'WAIT_SELLER_AGREE' => '等待审核',
	'WAIT_BUYER_RETURN_GOODS' => '等待退货'
	);
	*/
?>

<html>
	<head>
		<title>
			售后处理订单关键信息表
		</title>
		<style type="text/css">
		div.info {
			font-size: 15px;
			padding-top: 5px;
			padding-bottom: 5px;
		}
		fieldset.info {
			font-size: 15px;
			margin-top: 5px;
			margin-bottom: 5px;
			/*background-color: white;*/
			border: gray 1px solid;
		}
		table, td {
			border: 1px solid gray;
			border-collapse:collapse;
			font-size: 15px;
			padding: 5px;
			border: gray 1px solid;
			border-collapse:collapse;
			text-align: center;
			margin-top: 5px;
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
		p.captain {
			padding: 2px;
			font-size: 20px;
		}
		</style>
		
		<script type="text/javascript">

		function do_ajax(method,url,isAsync,info_span){
			var xmlhttp;
			if (url.length==0){
				document.getElementById(info_span).innerHTML="正在更新";
				return;
			}
			if (window.XMLHttpRequest){
			  	xmlhttp=new XMLHttpRequest();
			} else {
			  	xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
			}
			if(info_span=='order_msg_program_span'){
				xmlhttp.onreadystatechange=function(){
				  	if (xmlhttp.readyState==4){
				  		if(xmlhttp.status==200){
					    	document.getElementById(info_span).innerHTML=xmlhttp.responseText;
							alert('请及时进行相应操作！');
							location.reload(); 
						}else{
					    	document.getElementById(info_span).innerHTML="更新失败";
					    }
					}
				}
			}else{
				xmlhttp.onreadystatechange=function(){
				  	if (xmlhttp.readyState==4 && xmlhttp.status==200){
				    	document.getElementById(info_span).innerHTML=xmlhttp.responseText;
				    	location.reload();
				    }else{
				    	document.getElementById(info_span).innerHTML="更新失败";
				    }
				}
			}
			xmlhttp.open(method,url,isAsync);
			xmlhttp.send();
		}

		function insert_order_msg_program(order_id){
			var program=document.getElementById('order_msg_plan_select').value;
			if(order_id!='0' && program!='0'){
				var method='POST';
				var url='postsale_function.php?act=ajax&call=insert_order_msg_program&order_id='+order_id+'&program='+program;
				var isAsync=true;
				var info_span='order_msg_program_span';
				do_ajax(method,url,isAsync,info_span);
			} else {
				alert("请选择具体处理方案！");
			}
		}
		function fast_terminate_msg(order_id){
			if(order_id!='0'){
				var method='POST';
				var url='postsale_function.php?act=ajax&call=fast_terminate_msg&order_id='+order_id;
				var isAsync=true;
				var info_span='fast_terminate_msg_span';
				do_ajax(method,url,isAsync,info_span);
			} else {
				alert("这种错误非常神奇，理论上不可能发生，但是出现了就通知ERP修吧。");
			}
		}
		function append_memo_to_messages(order_id){
			if(order_id!=0){
				var msg=prompt("请输入备注内容。备注不会改变已经确认的方案和当前的责任部门。\n如果需要取消添加备注，请将输入框留空或点击取消。","");
				if(msg!=null && msg!=''){
					var method='POST';
					var url='postsale_function.php?act=ajax&call=append_memo_to_messages&order_id='+order_id+'&msg='+msg;
					var isAsync=true;
					var info_span='append_memo_to_messages_span';
					do_ajax(method,url,isAsync,info_span);
				}
			}
		}
		function set_order_pending(order_id){
			if(order_id!='0'){
				var method='POST';
				var url='postsale_function.php?act=ajax&call=set_order_pending&order_id='+order_id;
				var isAsync=true;
				var info_span='order_pending_info_span';
				do_ajax(method,url,isAsync,info_span);
			} else {
				alert("这种错误非常神奇，理论上不可能发生，但是出现了就通知ERP修吧。");
			}
		}
		function cancel_order_pending(order_id){
			if(order_id!='0'){
				var method='POST';
				var url='postsale_function.php?act=ajax&call=cancel_order_pending&order_id='+order_id;
				var isAsync=true;
				var info_span='order_pending_info_span';
				do_ajax(method,url,isAsync,info_span);
			} else {
				alert("这种错误非常神奇，理论上不可能发生，但是出现了就通知ERP修吧。");
			}
		}
		function over_order_pending(order_id){
			if(order_id!='0'){
				var method='POST';
				var url='postsale_function.php?act=ajax&call=over_order_pending&order_id='+order_id;
				var isAsync=true;
				var info_span='order_pending_info_span';
				do_ajax(method,url,isAsync,info_span);
			} else {
				alert("这种错误非常神奇，理论上不可能发生，但是出现了就通知ERP修吧。");
			}
		}
		</script>
	</head>
	<body>
		<div>
			<?php 
			if($order_key_info) {
				/**
				DEBUG
				**/
				//echo "<!--\n";
				//print_r($order_key_info);
				//echo "\n-->";
				echo "<fieldset class='info'>
						<legend>
						ERP订单：".$order_key_info['order_sn']."<!--".$order_key_info['order_id']."--> &nbsp;&nbsp;".
							get_order_status_name($order_key_info['order_status']).
						" ".get_shipping_status_name($order_key_info['shipping_status']).
						" ".get_pay_status_name($order_key_info['pay_status'])."
						</legend>";	
				/**	
				//general status		
				**/
				echo "<fieldset class='info'><legend>订单状态</legend>";
				echo "<div class='info'>";
				echo "组织：".$order_key_info['party_name']."<!--".$order_key_info['party_id']."--> ";
				echo "&nbsp;&nbsp;";
				echo "销售方：".$order_key_info['distributor_name'];
				echo "&nbsp;&nbsp;";
				echo "外部订单号：".$order_key_info['taobao_order_sn']." ";
				echo "&nbsp;&nbsp;";
				echo "支付方式：".$order_key_info['pay_name']."<!--".$order_key_info['pay_id']."-->"." ";
				echo "</div><div class='info'>";
				echo "下单于 ".$order_key_info['order_time']." ";
				echo "&nbsp;&nbsp;";
				echo "付款于 ".(empty($order_key_info['pay_time'])?"未来":date("Y-m-d H:i:s",$order_key_info['pay_time']))." ";	
				//echo "&nbsp;&nbsp;";//echo "</div><div class='info'>";
				//echo "预定于 ".(empty($order_key_info['reserved_time'])?"未来":date("Y-m-d H:i:s",$order_key_info['reserved_time']))." ";
				echo "</div>";

				$order_relation_link=get_order_relation_line($order_key_info['order_sn']);
				if($order_relation_link){
					echo "<div class='info'>";
					echo "订单关系：";
					foreach ($order_relation_link as $no => $osn) {
						if($no==0){
							if($osn){
								echo "已经生成的子订单有 ";
								foreach ($osn as $key => $value) {
									$oid=get_order_id_by_order_sn($value);
									echo "[<a target='blank' href='../order_edit.php?order_id=$oid'>$value</a>] ";		
								}
							}else{
								echo "无子订单 ";
							}
						} else {
							$oid=get_order_id_by_order_sn($osn);
							if($no>1)echo "<--父订单";else echo "<--本订单";
							echo "[<a target='blank' href='../order_edit.php?order_id=$oid'>$osn</a>]";
						}
					}
					echo "</div>";
				}else {
					echo "<div class='info'>
							本订单<a target='blank' href='../order_edit.php?order_id=".$order_key_info['order_id']."'>[".$order_key_info['order_sn']."]</a>没有发现关联的订单
						</div>";
				}
				echo "</fieldset>";
				/**
				Postsale Board
				**/
				echo "<fieldset class='info'><legend>售后处理</legend>";
				echo "<div class='info'>";
				echo "<p>";
				echo "<a href=\"../sale_support/sale_support.php?order_id=".$order_key_info['order_id']."\" target='blank'>前往沟通页面</a>&nbsp;";
				$last_msg=get_sale_support_message_id($order_key_info['order_id']);
				if($last_msg && !empty($user_priv_list[$last_msg['next_process_group']]['value'])){//如果有最新的沟通消息，并且责任部门非空
					echo "目前責任部門：".$user_priv_list[$last_msg['next_process_group']]['value']."&nbsp;&nbsp;";//提示责任部门
					if(isDevPrivUser($_SESSION['admin_name']) || (
							(
								(
									$last_msg['next_process_group']=='' 
									|| $last_msg['next_process_group']==null
								)
								&& check_admin_priv('kf_postsale_support','kf_postsale_support_fenxiao')
							)
							|| 
							($last_msg['next_process_group']=='KF' && check_admin_priv('kf_postsale_support'))
							|| 
							($last_msg['next_process_group']=='FXKF' && check_admin_priv('kf_postsale_support_fenxiao'))
							/* ||
							($last_msg['next_process_group']=='DZ' && check_admin_priv('dz_sale_support')) ||
							($last_msg['next_process_group']=='SHWL' && check_admin_priv('shwl_sale_support')) ||
							($last_msg['next_process_group']=='DGWL' && check_admin_priv('dgwl_sale_support')) ||
							($last_msg['next_process_group']=='CG' && check_admin_priv('cg_sale_support')) ||
							($last_msg['next_process_group']=='CW' && check_admin_priv('cw_sale_support')) 
							*/
						)
					){
						echo "<span id='fast_terminate_msg_span'><a href='#' onclick='fast_terminate_msg(\"".$order_key_info['order_id']."\");'>了解并结束沟通</a></span>&nbsp;&nbsp;";
					}
				}
				else {
					echo "无负责部门"."&nbsp;&nbsp;";
				}
				echo "售后处理方案：";
				echo "<span id='order_msg_program_span'>";
				if(empty($last_msg['program'])){
					echo "未确定。";
				} else {
					echo $last_msg['program'];
				}
				echo "</span>
					&nbsp;&nbsp;&nbsp;";
				if(isDevPrivUser($_SESSION['admin_name']) || 
					(
						(
							$last_msg['next_process_group']=='' 
							|| $last_msg['next_process_group']==null
						)
						&& check_admin_priv('kf_postsale_support','kf_postsale_support_fenxiao')
					)
					|| 
					($last_msg['next_process_group']=='KF' && check_admin_priv('kf_postsale_support'))
					 ||
					($last_msg['next_process_group']=='FXKF' && check_admin_priv('kf_postsale_support_fenxiao'))
				){
					if(empty($last_msg['program'])){
						echo "【快速确认】";
					} else {
						echo "【快速修正】";
					}
					//echo "可选方案：";
					echo "<select id='order_msg_plan_select'>";
					echo "<option value='0'>以下6种可选</option>";
					foreach ($plan_list as $pk => $plan) {
						echo "<option value='$plan'>$plan</option>";
					}
					echo "</select>";
					echo "<a href='#' onclick='insert_order_msg_program(\"".$order_key_info['order_id']."\");'
						><input type='button' value='确认更新'></a>";
				} else {
					echo "将由客服来确定方案。";
				}
				echo "</p>";
				/*
				if($last_msg && $last_msg['status']=='PENDING'){
					$can_do=false;
					if(isDevPrivUser($_SESSION['admin_name']) || check_admin_priv('shwl_sale_support') || check_admin_priv('dgwl_sale_support')){
						$can_do=true;
					} 
					echo "<p>";
					echo "正在等待消费者退货。如果长时间未收到，可以";
					if($can_do){
						echo "<input type='button' onclick='cancel_order_pending(\"".$order_key_info['order_id']."\");' value='取消等待'>";
					}else{
						echo "联系物流部门取消等待";
					}
					echo "并联系客服处理后续。如果签收了对应的货物，请";
					if($can_do){
						echo "<input type='button' onclick='over_order_pending(\"".$order_key_info['order_id']."\");' value='确认收货'>。";
					}else{
						echo "联系物流部门操作确认收货。";
					}
					echo "<span id='order_pending_info_span'></span>";
					echo "</p>";
				}else{
					$can_do=false;
					if(isDevPrivUser($_SESSION['admin_name']) || check_admin_priv('shwl_sale_support') || check_admin_priv('dgwl_sale_support')){
						$can_do=true;
					} 
					echo "<p>如果此单需要物流部门等待消费者退回，可以";
					if($can_do){
						echo "<input type='button' onclick='set_order_pending(\"".$order_key_info['order_id']."\");' value='标记为 等待消费者退货 状态'>。";
					}else{
						echo "要求物流部门准备等待接收，并尽可能提供退回单号。";
					}
					echo "<span id='order_pending_info_span'></span>";
					echo "</p>";
				}
				*/
				if(empty($last_msg['program'])){
					echo "<p>请首先进行沟通，了解情况。在售后方案确认之后，可以在这里进行快速申请操作。</p>";
				}else{
					/*
					if(strstr($last_msg['program'],'退款') && !strstr($last_msg['program'],'不退款')){//仅退款
			    		$target_url='../refund_apply.php?order_id='.$order_key_info['order_id'].'&type=OTHERS';
			    		$gostr2="<a href='#' onClick=\"window.open('".$target_url."','','height=500,width=611,scrollbars=yes,status=yes')\">建立退款申请</a>";
			    	}
			    	if(strstr($last_msg['program'],'退货')){//退货不退款,退货退款
			    		$target_url='../sale_serviceV3.php?service_type=2&fast_apply=1&order_sn='.$order_key_info['order_sn'];
			    		$gostr1="<a href='#' onClick=\"window.open('".$target_url."','','height=500,width=611,scrollbars=yes,status=yes')\">建立退货申请</a>";
			    	}
			    	if(strstr($last_msg['program'],'换货')){//换货
			    		$target_url='../sale_serviceV3.php?service_type=1&fast_apply=1&order_sn='.$order_key_info['order_sn'];
			    		$gostr1="<a href='#' onClick=\"window.open('".$target_url."','','height=500,width=611,scrollbars=yes,status=yes')\">建立换货申请</a>";
			    	}
			    	if(strstr($last_msg['program'],'追回')){//追回
			    		$target_url_sc='../shipped_cancel.php?act=search&order_id='.$order_key_info['order_id'];
			    		$gostr1="<a href='#' onClick=\"window.open('".$target_url_sc."','','height=500,width=611,scrollbars=yes,status=yes')\">操作追回</a>";
			    	}
			    	if(strstr($last_msg['program'],'录单补寄')){//录单补寄
			    		$target_url='../distribution_order.php?order_sn='.$order_key_info['order_sn'];
			    		$gostr1="<a href='#' onClick=\"window.open('".$target_url."','','height=500,width=611,scrollbars=yes,status=yes')\">录单补寄</a>";
			    	}
			    	echo "&nbsp;".$gostr1."&nbsp;".$gostr2;
			    	*/
			    	echo "<p>";
			    	if(strstr($last_msg['program'],'无需处理')){
			    		echo "该订单无需处理";
			    	}
			    	else
			    		echo "
			    		<a href='#' onClick=\"window.open('../sale_serviceV3.php?service_type=2&fast_apply=1&order_sn=".$order_key_info['order_sn']."','','height=500,width=611,scrollbars=yes,status=yes')\"
						><input type='button' value='建立退货申请'></a>
						&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
						<a href='#' onClick=\"window.open('../sale_serviceV3.php?service_type=1&fast_apply=1&order_sn=".$order_key_info['order_sn']."','','height=500,width=611,scrollbars=yes,status=yes')\"
						><input type='button' value='建立换货申请'></a>
						&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
						<!--
						<a href='#' onClick=\"window.open('../refund_apply.php?order_id=".$order_key_info['order_id']."&type=OTHERS','','height=500,width=611,scrollbars=yes,status=yes')\"
						><input type='button' value='建立杂项退款申请'></a>
						&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
						-->
						<a href='#' onClick=\"window.open('../refund_apply.php?order_id=".$order_key_info['order_id']."','','height=500,width=611,scrollbars=yes,status=yes')\"
						><input type='button' value='建立退款申请'></a>
						&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
						<a href='#' onClick=\"window.open('../shipped_cancel.php?act=search&order_id=".$order_key_info['order_id']."','','height=500,width=611,scrollbars=yes,status=yes')\"
						><input type='button' value='操作追回'></a>
						&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
						<a href='#' onClick=\"window.open('../distribution_order.php?order_sn=".$order_key_info['order_sn']."','','height=500,width=611,scrollbars=yes,status=yes')\"
						><input type='button' value='录单补寄'></a>
					";
					if(true || isDevPrivUser($_SESSION['admin_name'])){
						echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
							<input type='button' value='添加沟通备注' onclick='append_memo_to_messages(\"".$order_key_info['order_id']."\");'>
							";
					}
					echo "</p>";
				}
				echo "</div>";
				//echo "<hr>";
				echo "<div class='info'>";
				echo "<span id='append_memo_to_messages_span'></span>";
				$msgs=get_sale_support_messages_by_order_id($order_key_info['order_id']);
				if($msgs){
					echo "<table>";
					echo "<tr>
							<th style='width: 20%;'>时间</th>
							<th style='width: 10%;'>操作人</th>
							<th style='width: 20%;'>责任归属</th>
							<th style='width: 50%;'>内容</th>
						</tr>";
					foreach ($msgs as $no => $line) {
						echo "<tr>";
						/*
						echo "<p>在".$line['created_stamp'].", ".
							get_admin_user_real_name($line['send_by'])."(".$line['send_by'].")".
							(empty($line['next_process_group'])?"添加备忘":"要求".$user_priv_list[$line['next_process_group']]['value']."处理").
							"：".
							"[".$line['message']."]".
							"</p>";
						*/
						echo "<td>".$line['created_stamp']."</td>";		
						echo "<td><!--".get_admin_user_real_name($line['send_by'])."-->".$line['send_by']."</td>";
						echo "<td>".(empty($line['next_process_group'])?"添加备忘":"要求".$user_priv_list[$line['next_process_group']]['value']."处理")."</td>";
						echo "<td>".$line['message']."</td>";
						echo "</tr>";		
					}
					echo "</table>";
				}
				echo "</div>";
				
				$tb_refunds=get_sync_taobao_refund_lines_of_a_trade($order_key_info['taobao_order_sn']);
				if($tb_refunds && count($tb_refunds)>0){
					global $get_sync_taobao_refund_state_map;
					echo "<div class='info'>";
					echo "<table>";
					echo "<tr><td colspan='8'>共计发起过".count($tb_refunds)."件淘宝直销退款申请</td></tr>";
					echo "<tr>
							<th>淘宝退款编号</th>
							<th>金额</th>
							<th>目前状态</th>
							<th>退回单号</th>
							<th>发起时间</th>
							<th>最近同步时间</th>
							<th>涉及商品</th>
							<th>数量</th>
						</tr>";
					foreach ($tb_refunds as $no => $line) {
						echo "<tr>";
						echo "<td>".$line['refund_id']."</td>";
						echo "<td>".$line['refund_fee']."</td>";
						echo "<td>".$get_sync_taobao_refund_state_map[$line['status']]."</td>";
						echo "<td>".$line['company_name'].":".$line['sid']."</td>";
						echo "<td>".$line['created']."</td>";
						echo "<td>".$line['last_update_timestamp']."</td>";
						echo "<td>".$line['title']."</td>";
						echo "<td>".$line['num']."</td>";
						echo "</tr>";
					}
					echo "</table>";
					echo "</div>";
				}

				$tb_fx_refunds=get_sync_taobao_fenxiao_refund_lines_of_a_trade($order_key_info['taobao_order_sn']);
				if($tb_fx_refunds && count($tb_fx_refunds)>0){
					global $get_sync_taobao_fenxiao_refund_state_map;
					echo "<div class='info'>";
					echo "<table>";
					echo "<tr><td colspan='8'>共计发起过".count($tb_fx_refunds)."件淘宝分销退款申请</td></tr>";
					echo "<tr>
							<th>淘宝退款编号</th>
							<th>金额</th>
							<th>目前状态</th>
							<th>退回单号</th>
							<th>发起时间</th>
							<th>最近同步时间</th>
							<th>涉及商品</th>
							<th>数量</th>
						</tr>";
					foreach ($tb_fx_refunds as $no => $line) {
						echo "<tr>";
						echo "<td>".$line['refund_id']."</td>";
						echo "<td>".$line['refund_fee']."</td>";
						echo "<td>".$get_sync_taobao_fenxiao_refund_state_map[$line['status']]."</td>";
						echo "<td>暂不支持</td>";
						echo "<td>".$line['created']."</td>";
						echo "<td>".$line['buyer_modified']."</td>";
						echo "<td>暂不支持</td>";
						echo "<td>暂不支持</td>";
						echo "</tr>";
					}
					echo "</table>";
					echo "</div>";
				}

				$tmall_refunds=get_sync_tmall_refund_lines_of_a_trade($order_key_info['taobao_order_sn']);
				if($tmall_refunds && count($tmall_refunds)>0){
					global $get_sync_tmall_refund_state_map;
					echo "<div class='info'>";
					echo "<table>";
					echo "<tr><td colspan='8'>共计发起过".count($tmall_refunds)."件天猫退款申请</td></tr>";
					echo "<tr>
							<th>淘宝退款编号</th>
							<th>金额</th>
							<th>目前状态</th>
							<th>退回单号</th>
							<th>发起时间</th>
							<th>最近同步时间</th>
							<th>涉及商品</th>
							<th>数量</th>
						</tr>";
					foreach ($tmall_refunds as $no => $line) {
						echo "<tr>";
						echo "<td>".$line['refund_id']."</td>";
						echo "<td>".($line['refund_fee']/100.0)."</td>";
						echo "<td>".$get_sync_tmall_refund_state_map[$line['status']]."</td>";
						echo "<td>".$line['company_name'].":".$line['sid']."</td>";
						echo "<td>".$line['created']."</td>";
						echo "<td>".$line['modified']."</td>";
						echo "<td>".$line['title']."</td>";
						echo "<td>".$line['num']."</td>";
						echo "</tr>";
					}
					echo "</table>";
					echo "</div>";
				}

				echo "<div class='info'>";
				$services=get_services_for_order_id($order_key_info['order_id']);
				if($services){
					echo "<p>"."<a href=\"../sale_serviceV3.php?act=search&search_text=".$order_key_info['order_sn']."\" target='blank'>[已经建立的退换货申请]</a>"."</p>";
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
					foreach ($services as $sno => $service) {
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
				}

				$refunds=get_refunds_for_order_id($order_key_info['order_id']);
				if($refunds){
					echo "<table>";
					echo "<tr>
							<th>退款申请编号</th>
							<th>申请人</th>
							<th>发起日</th>
							<th>金额</th>
							<th>状态</th>
						</tr>";
					foreach ($refunds as $key => $refund) {
						echo "<tr>";
						echo "<td>"."<a href=\"../refund_view.php?refund_id=".$refund['REFUND_ID']."\" target='blank'>".$refund['REFUND_ID']."</a>"."</td>";
						echo "<td>".$refund['CREATED_BY_USER_LOGIN']."</td>";
						echo "<td>".$refund['CREATED_STAMP']."</td>";
						echo "<td>".sprintf("%01.2f",$refund['TOTAL_AMOUNT'])."</td>";
						echo "<td>".get_refund_next_responsor($refund)."</td>";
						echo "</tr>";
					}
					echo "</table>";
				}
				/*
				$tb_refunds=get_taobao_sync_refund_for_order_taobao_sn($order_key_info['taobao_order_sn']);
				if($tb_refunds){
					echo "<table>";
					echo "<tr>
							<th>淘宝退款编号</th>
							<th>淘宝店铺</th>
							<th>申请人</th>
							<th>发起日</th>
							<th>金额</th>
							<th>状态</th>
							<th>涉及商品</th>
							<th>数量</th>
						</tr>";
					$time_now=time();
					foreach ($tb_refunds as $key => $tb_refund) {
						$time_c=strtotime($tb_refund['created']);
  						$time_dif_day=round(($time_now-$time_c)/(3600*24));
						echo "<tr>";
						echo "<td>".$tb_refund['refund_id']."</td>";
						echo "<td>".$tb_refund['seller_nick']."</td>";
						echo "<td>".$tb_refund['buyer_nick']."</td>";
						echo "<td>".$tb_refund['created']."[".($time_dif_day>0?$time_dif_day."天前":"今天")."]</td>";
						echo "<td>".sprintf("%01.2f",$tb_refund['TOTAL_AMOUNT'])."</td>";
						echo "<td>".$get_sync_taobao_refund_state_map[$tb_refund['status']]."</td>";
						echo "<td>".$get_sync_taobao_refund_state_map[$tb_refund['title']]."</td>";
						echo "<td>".$get_sync_taobao_refund_state_map[$tb_refund['num']]."</td>";
						echo "</tr>";
					}
					echo "</table>";
				}
				*/
				//echo "<textarea>";print_r($tb_refunds);echo "</textarea>";

				echo "</div>";
				echo "</fieldset>";
				/**
				//user info
				**/
				echo "<fieldset class='info'><legend>物流信息</legend>";
				echo "<div class='info'>";
				echo "顾客：".$order_key_info['consignee']." ";
				echo "&nbsp;&nbsp;";
				echo "电话：".$order_key_info['tel']." ";
				echo "&nbsp;&nbsp;";
				echo "手机：".$order_key_info['mobile']." ";
				echo "&nbsp;&nbsp;";
				echo "仓库：".$order_key_info['FACILITY_NAME']."<!--".$order_key_info['facility_id']."-->";
				echo "&nbsp;&nbsp;";
				echo "送货方式：".$order_key_info['shipping_name']."<!--".$order_key_info['shipping_id']."--> ";
				//echo "缺货等待要求：".($order_key_info['is_shortage_await']=='NO'?'48小时无货取消':'48小时无货再等3天');
				echo "</div><div class='info'>";
				echo "地址：".$order_key_info['country_name']." ".
							$order_key_info['province_name']." ".
							$order_key_info['city_name']." ".
							$order_key_info['district_name']." ".
							$order_key_info['address'].
							"&nbsp;&nbsp;邮编：".$order_key_info['zipcode']." ";
				//echo "</div><div class='info'>";
				//echo "仓库：".$order_key_info['FACILITY_NAME']."<!--".$order_key_info['facility_id']."-->";
				//echo "&nbsp;&nbsp;";
				//echo "送货方式：".$order_key_info['shipping_name']."<!--".$order_key_info['shipping_id']."--> ";
				//echo "</div>";

				echo "</fieldset>";
				/**
				//goods info
				**/
				echo "<fieldset class='info'><legend>交易信息</legend>";
				echo "<div class='info'>";
				echo "商品金额：".sprintf("%01.2f",$order_key_info['goods_amount'])."&nbsp;&nbsp;";
				echo "包装费用：".sprintf("%01.2f",$order_key_info['pack_fee'])."&nbsp;&nbsp;";
				echo "配送费用：".sprintf("%01.2f",$order_key_info['shipping_fee'])."&nbsp;&nbsp;";
				echo "抵扣金额：".sprintf("%01.2f",$order_key_info['bonus'])."&nbsp;&nbsp;";
				echo "合计金额：".sprintf("%01.2f",$order_key_info['order_amount'])."&nbsp;&nbsp;";
				echo "</div>";
				echo "<div class='info'>";
				echo "发票要求：".($order_key_info['need_invoice']=="Y"?'需要':"不需要")."&nbsp;&nbsp;";
				echo "发票状态：".(get_invoice_status_name($order_key_info['invoice_status']))."&nbsp;&nbsp;";
				echo "发票记录ID：".(empty($order_key_info['invoice_no'])?"无":$order_key_info['invoice_no']);
				echo "</div>";
				echo "<div class='info'>";
				echo "<table>";
				echo "<tr><th colspan='8'>共计".$order_key_info['total_goods_number']."件商品</th></tr>";
				echo "<tr>
						<th>No</th>
						<th>商品</th>
						<th>样式</th>
						<th>数量</th>
						<th>单价</th>
						<!--<th>新旧</th>-->
						<th>库存</th>
						<th>状态</th>
					</tr>";
				foreach ($order_key_info['order_goods'] as $ogid => $order_good_array) {
					if(!empty($order_good_array['style_id'])){
						$style_group=get_style_by_style_id($order_good_array['style_id']);
						$style_word="样式ID：".$style_group['style_id']."<br>".
									"颜色：".$style_group['color']."<br>".
									"值：".$style_group['value']."<br>".
									"类型：".$style_group['type'];
					}else{
						$style_word="无样式";
					}
					echo "<tr>";
					echo "<td>".($ogid+1)."</td>
						<td>".
						$order_good_array['goods_name'].
						"</td>
						<td>".
						$style_word.//(empty($order_good_array['style_id'])?"":"样式ID为[".$order_good_array['style_id']."]").
						"</td>
						<td>".
						"计".$order_good_array['goods_number']."件
						</td>
						<td>".
						"单价每件".sprintf("%01.2f",$order_good_array['goods_price']).
						"</td>
						<!--
						<td>".
						"商品状态：".($order_good_array['is_new']=='NEW'?"全新":"二手").
						"</td>
						-->
						<td>".
						"<!--所属库存：-->".get_inventory_status_id_name($order_good_array['status_id']).
						"</td>
						<td>".
						get_good_status_name($order_good_array['goods_status']).
						"</td>";
					echo "</tr>";
				}
				echo "</table>";
				echo "</div>";
				echo "</fieldset>";
				/**
				ACTION
				**/
				echo "<fieldset class='info'><legend>操作信息</legend>";
				echo "<div class='info'>";
				echo "<table>";
				echo "<tr>
						<th>No</th>
						<th>用户</th>
						<th>时间</th>
						<th>操作记录</th>
						<th>订单</th>
						<th>配送</th>
						<th>支付</th>
						<th>发票</th>
						<th>缺足</th>
					</tr>";
				foreach ($order_key_info['order_action'] as $oaid => $oa) {
					echo "<tr>";
					echo "<td>[$oaid]</td>".
						"<td>".$oa['action_user']."</td>
						<td>".$oa['action_time']."</td>
						<td>".$oa['action_note']."</td>
						<td>".get_order_status_name($oa['order_status'])."</td>
						<td>".get_shipping_status_name($oa['shipping_status'])."</td>
						<td>".get_pay_status_name($oa['pay_status'])."</td>
						<td>".get_invoice_status_name($oa['invoice_status'])."</td>
						<td>".get_shortage_status_name($oa['shortage_status'])."</td>";
					echo "</tr>";
				}
				echo "</table>";
				echo "</div>";
				echo "</fieldset>";
				echo "</fieldset>";				
			} else {
				/**
				EXCEPTION
				**/
				echo "无法查询到该订单的信息（$order_base ";
				if($order_base=='order_sn') echo "$order_sn";
				else if($order_base=='order_id') echo "$order_id";
				else echo "EMPTY";
				echo "）";
			} ?>
		</div>
	</body>
</html>
<?php

function get_services_for_order_id($order_id){
	global $db;
	$sql="SELECT 
			s.*, 
			eau.user_name admin_user_name,
			eu.user_name user_name
		FROM ecshop.service s 
		LEFT JOIN ecshop.ecs_admin_user eau ON s.user_id=eau.user_id
		LEFT JOIN ecshop.ecs_users eu ON s.user_id=eu.user_id
		WHERE s.order_id='$order_id';";
	$result=$db->getAll($sql);
	return $result;
}

function get_service_log_for_service_id($service_id,$is_remark){
	global $db;
	$sql="SELECT
			*
		FROM
			ecshop.service_log sl
		WHERE
			sl.service_id = $service_id
			AND sl.is_remark = $is_remark
		ORDER BY
			sl.log_datetime;";
	$r=$db->getAll($sql);
	return $r;
}

function get_refunds_for_order_id($order_id){
	global $db;
	$sql="SELECT 
			r.* 
			-- eau.user_name admin_user_name,
			-- eu.user_name user_name
		FROM romeo.refund r
		-- LEFT JOIN ecshop.ecs_admin_user eau ON s.user_id=eau.user_id
		-- LEFT JOIN ecshop.ecs_users eu ON s.user_id=eu.user_id
		WHERE r.ORDER_ID='$order_id';";
	$result=$db->getAll($sql);
	return $result;
}

function get_taobao_sync_refund_for_order_taobao_sn($otsn){
	global $db;
	$sql="SELECT
			*
		FROM
			ecshop.sync_taobao_refund str
		WHERE
			str.tid = $otsn
			-- POSITION(str.tid IN '$otsn')
		;";
	$r=$db->getAll($sql);
	//pp($sql);
	//pp($r);
	return $r;
}

function get_order_relation_family($order_sn){
	global $db;
	$sql="SELECT
			r2.order_id,
			r2.order_sn,
			r2.parent_order_id,
			r2.parent_order_sn,
			r2.root_order_id,
			r2.root_order_sn
		FROM
			ecshop.order_relation r2,
			(
				SELECT
					r0.root_order_sn
				FROM
					ecshop.order_relation r0
				WHERE
					r0.order_sn = '$order_sn'
					OR r0.root_order_sn = '$order_sn'
			) AS r1
		WHERE
			r2.root_order_sn = r1.root_order_sn;";
	$r=$db->getAll($sql);
	//echo "[ $sql ]<br>";
	//print_r($r);
	return $r;
}

function get_order_children($order_family,$parent_order_sn){
	$order_children=array();
	foreach ($order_family as $key => $line) {
		if($line['parent_order_sn']==$parent_order_sn){
			$order_children[$line['order_sn']]=$line['order_sn'];
		}
	}
	return $order_children;
}

function get_order_parent($order_family,$child_order_sn){
	foreach ($order_family as $key => $line) {
		if($line['order_sn']==$child_order_sn){
			return $line['parent_order_sn'];
		}
	}
	return false;
}

function get_order_relation_line($order_sn){
	$order_family=get_order_relation_family($order_sn);
	$link=array();
	if($order_family){
		$rootosn=$order_family[0]['root_order_sn'];
		//echo "ROOT-ORDER-SN=$rootosn<br>";
		$children=get_order_children($order_family,$order_sn);
		$link[]=$children;
		$posn=$order_sn; 
		//echo "POSN=$posn<br>";
		while($posn!=$rootosn){
			$link[]=$posn;
			$posn=get_order_parent($order_family,$posn); 
			//echo "POSN=$posn<br>";
		}
		$link[]=$posn;
		return $link;
	}
	else return false;
}

function get_order_id_by_order_sn($order_sn){
	return $GLOBALS['db']->getOne("select order_id from ecshop.ecs_order_info where order_sn = '$order_sn';");
}

function get_style_by_style_id($style_id){
	global $db;
	$sql="select style_id,color,value,type from ecshop.ecs_style where style_id=$style_id;";
	$r=$db->getRow($sql);
	return $r;
}

function get_admin_user_real_name($user_name){
	global $db;
	$sql="SELECT
		real_name
		FROM
		ecshop.ecs_admin_user
		WHERE
		user_name='$user_name';
	";
	$r=$db->getOne($sql);
	return $r;
}

?>