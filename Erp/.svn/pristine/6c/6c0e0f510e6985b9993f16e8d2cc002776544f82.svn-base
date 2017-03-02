<?php
define('IN_ECS', true);
include_once('../config.vars.php');
require_once('../includes/init.php');
header("content-type:text/html;charset=utf-8");

$message = "";

if(isset($_POST['act'])){
	if($_POST['act'] == 'order_batch_pick_checker'){
		if(isset($_POST['ordersn'])){
			if(empty($_POST['ordersn'])){
				$message = "<span>输入为空</span>";
			}
			else{
				//查询是否存在输入的order_sn
				$order_exist = $db -> getRow("SELECT 1 FROM ecshop.ecs_order_info oi WHERE oi.order_sn = trim('{$_POST['ordersn']}')");
				if (!$order_exist) {
					$message = '<span>不存在此订单号！</span>';
				}else{
					//查询是否已经批拣过如有，则列出批拣信息
					$if_exists =$db -> getRow("SELECT bm.*,
									-- om.pick_list_status,
									oi.order_time
								FROM ecshop.ecs_order_info oi
								INNER JOIN romeo.order_shipment m ON m.ORDER_ID = convert(oi.order_id using utf8)
								INNER JOIN romeo.shipment s ON s.SHIPMENT_ID = m.shipment_id
								INNER JOIN  romeo.batch_pick_mapping bm ON bm.shipment_id = s.SHIPMENT_ID
								-- INNER JOIN ecshop.order_mixed_status_history om ON om.order_id = oi.order_id
								WHERE oi.order_sn = trim('{$_POST['ordersn']}') 
								-- AND IF(om.pick_list_status is not null,om.is_current = 'Y',TRUE) 
								LIMIT 1"
								);	

					if($if_exists){
						$message .= 
								 "<span>已经存在此批拣单！</span>".
								 "<br>".
								 "此批拣单内容如下：".
								 "<br>".
								 "\t\tbatch_pick_mapping_id:".$if_exists['batch_pick_mapping_id'].
								 "<br>".
								 "\t\tbatch_pick_sn:".$if_exists['batch_pick_sn'].
								 "<br>".
								 "\t\tshipment_id:".$if_exists['shipment_id'].
								 "<br>".
								 "\t\t批拣车格子号:".$if_exists['grid_id'].
								 "<br>";
								 ($if_exists['is_pick'] == 'Y')?$message .="\t\tis_pick:已经被批拣":$message .="is_pick不为Y";
								 $message .=
								 "<br>";
						// if(!empty($if_exists['pick_list_status'])){
						// 	if($if_exists['pick_list_status'] == 'printed'){
						// 		$message .= 
						// 			 "打印状态：".
						// 			 "<span style = 'color:blue'>".
						// 			 "该批拣单已经打印！".
						// 			 "</span>".
						// 			 "<br>";
						// 	}else if($if_exists['pick_list_status'] == 'not-printed'){
						// 		$message .= 
						// 			 "<span style = 'color:blue'>".
						// 			 "打印状态：". 
						// 			 "该批拣单还未被打印！".
						// 			 "</span>".
						// 			 "<br>";
						// 	}
						// }else{
						// 	$message .= 
						// 		 "打印状态：".
						// 		 "<span style = 'color:blue'>".
						// 		 "打印状态为空！".
						// 		 "</span>".
						// 		 "<br>";
						// }

						$message .= 
								 "\t\t创建时间：".$if_exists['created_stamp'].
								 "<br>".
								 "\t\t最后更新时间：".$if_exists['last_updated_stamp'].
								 "<br>";
						if(!empty($if_exists['order_time'])){
								$day = (strtotime(date('Y-m-d',time()))-strtotime(date('Y-m-d',strtotime($if_exists['order_time']))))/86400;
								if($day >= 15){
									$message .=
									"<span style = 'color:blue'>".
									"订单确认时间距今已经超过15天咯~具体时间为：".$day."天！".
									"<br>(需要到页面重新选择订单确认时间哦~)<br>".
									"</span>".
									"<br>";
								}
							}else{
								 $message .= 
								 "<span style = 'color:blue'>".
								 "该订单确认时间取不到了！".
								 "</span>".
								 "<br>";
							}
							$message .= "</div>";
					}else{
						//如果没有批拣过，则查找出所有批拣要求的条件并进行核对，返回是否能批拣
						$sql = "SELECT 
									r.STATUS as 'rstatue',
									s.shipment_id,
									s.STATUS as 'sstatue',
									s.shipping_category,
									oi.shipping_status,
									oi.order_status,
									oi.FACILITY_ID,
									oi.order_time,
									oi.handle_time,
									f.FACILITY_NAME
									-- ,om.pick_list_status
								FROM
								ecshop.ecs_order_info oi use index(order_info_multi_index,order_sn) 
								LEFT JOIN romeo.facility f on oi.facility_id=f.FACILITY_ID
								LEFT JOIN romeo.order_shipment m ON m.ORDER_ID = CONVERT( oi.ORDER_ID USING UTF8)
								LEFT JOIN romeo.shipment s ON s.SHIPMENT_ID = m.SHIPMENT_ID
								LEFT JOIN ecshop.order_mixed_status_history om ON om.order_id = oi.order_id
								LEFT JOIN romeo.order_inv_reserved r ON r.order_id = oi.order_id AND oi.facility_id = r.facility_id
								WHERE oi.order_sn = trim('{$_POST['ordersn']}') 
								-- AND IF(om.pick_list_status is not null,om.is_current = 'Y',TRUE) 
								LIMIT 1";

							$result = $db -> getRow($sql);
							 // var_dump($result);
							$if_success = 1;

							$message = 
							"<span class='error' style='margin-left:20%;font-size:9px'>*</span><span style='color:black;font-size:9px'>为不满足批拣条件</span><br>";
							
							if($result['rstatue'] == 'Y'){
								$message .= "订单已预定<br>";
							}else{
								$message .= 
								 "<span class='error'>*".
								 "订单未预定！".
								 "<br>".
								 "</span>";
								$if_success = 0;
							}

							if(!empty($result['shipment_id'])){
								$message .= 
								 "发货单号为：".$result['shipment_id'].
								 "<br>";
								if($result['sstatue'] != 'SHIPMENT_INPUT'){
									 $message .= 
									 "<span class='error'>*".
									 "非SHIPMENT_INPUT状态！发货单为：".$result['sstatue']."状态".
									 "<br>".
									 "</span>";
									$if_success = 0;
								}else{
									 $message .= 
									 "发货单为：".$result['sstatue']."状态".
									 "<br>";
								}
								if($result['shipping_category'] !='SHIPPING_SEND'){
									$message .= 
									 "<span class='error'>*".
									 "非SHIPPING_SEND类别！发货单为：".$result['shipping_category']."类别".
									 "<br>".
									 "</span>";
									$if_success = 0;
								}else{
									$message .= 
									 "发货单为：".$result['shipping_category']."类别".
									 "<br>";
								}
							}else{
								$message .= 
								 "<span class='error'>*".
								 "还未创建发货单！".
								 "<br>".
								 "</span>";
								 $if_success = 0;
							}

							if($result['shipping_status'] != '0'){
								 $message .= 
								 "<span class='error'>*".
								 "非待配货状态！此订单为：".$_CFG['adminvars']['shipping_status'][$result['shipping_status']]."状态".
								 "<br>".
								 "</span>";
								$if_success = 0;
							}else{
								$message .= 
								 "此订单为：".$_CFG['adminvars']['shipping_status'][$result['shipping_status']]."状态".
								 "<br>";
							}

							if($result['order_status'] != '1'){
								 $message .= 
								 "<span class='error'>*".
								 "非已确认状态！此订单为：".$_CFG['adminvars']['order_status'][$result['order_status']]."状态".
								 "<br>".
								 "</span>";
								$if_success = 0;
							}else{
								$message .= 
								 "此订单为：".$_CFG['adminvars']['order_status'][$result['order_status']]."状态".
								 "<br>";
							}

							if($result['FACILITY_ID'] =='119603091'|| $result['FACILITY_ID'] =='119603092'
								|| $result['FACILITY_ID'] =='119603093'|| $result['FACILITY_ID'] =='92718101'|| $result['FACILITY_ID'] == null){
								 $message .= 
								 "<span class='error'>*";
								 !empty($result['FACILITY_ID'])?$message .="批拣仓库错误！此订单仓库为：".$result['FACILITY_NAME']:$message .="还未设置仓库！".
								 "<br>".
								 "</span>";
								$if_success = 0;
							}else{
								$message .= 
								 "此订单仓库为：".$result['FACILITY_NAME'].
								 "<br>";
							}

							if($result['handle_time'] != 0){
								if(date('Y-m-d H:i:s',$result['handle_time']) < date('Y-m-d H:i:s',time())){
									$message .= 
									 "<span class='error'>*".
									 "该订单已经被处理，处理时间为：".date('Y-m-d H:i:s',$result['handle_time']).
									 "<br>".
									 "</span>";
									$if_success = 0;
								}else{
									$message .= 
									 "此订单处理时间为：".date('Y-m-d H:i:s',$result['handle_time']).
									 "<br>";
								}
							}else{
								$message .= 
									 "此订单还未设置处理时间".
									 "<br>";
							}

							$message .= "<br>";
							// if(!empty($result['pick_list_status'])){
							// 	if($result['pick_list_status'] == 'printed'){
							// 		 $message .= 
							// 		 "<span style = 'color:blue'>【提醒（以下不为条件）】<br>".
							// 		 "打印状态：".
							// 		 "该批拣单已经打印！".
							// 		 "</span>".
							// 		 "<br>";
							// 	}else if($result['pick_list_status'] == 'not-printed'){
							// 		 $message .= 
							// 		 "<span style = 'color:blue'>【提醒（以下不为条件）】<br>".
							// 		 "打印状态：".
							// 		 "该批拣单还未被打印！".
							// 		 "</span>".
							// 		 "<br>";
							// 	}
							// }else{
							// 		 $message .= 
							// 		 "<span style = 'color:blue'>【提醒（以下不为条件）】<br>".
							// 		 "打印状态：".
							// 		 "打印状态为空！".
							// 		 "</span>".
							// 		 "<br>";
							// }
							
							if(!empty($result['order_time'])){
								$day = (strtotime(date('Y-m-d',time()))-strtotime(date('Y-m-d',strtotime($result['order_time']))))/86400;
								if($day >= 15){
									$message .=
									"<span style = 'color:blue'>".
									"订单确认时间距今已经超过15天咯~具体时间为：".$day."天！".
									"<br>(需要到页面重新选择订单确认时间哦~)<br>".
									"</span>".
									"<br>";
								}
							}else{
								 $message .= 
								 "<span style = 'color:blue'>".
								 "该订单确认时间取不到了！".
								 "</span>".
								 "<br>";
							}

							$message .= 
							 "<div>".
							 "<span>";
							if($if_success == 1){
								$message .= 
								 "该订单可被批拣！".
								 "<br>~\(≧▽≦)/~";
							}else{
								$message .=
								 "该订单不可被批拣！".
								 "<br>o(╯□╰)o";
							}
							$message .=
							 "</span>".
							 "</div>";
					}
	
				}	
			}	
		}
	}	
}	
?>			

<!DOCTYPE html>
<html>
<head>
	<title>
		根据订单号查询批量拣货单信息及失败原因
	</title>
	<style type="text/css">
	div {
		margin: 15px;
	}
	span {
			color: #FF0000;
	}
	.error {
		color:#FF00FF  ;
	}
	.input {
		height: 25px;
		line-height: 23px;
		width: 200px;
		padding-right: 16px;
		border:solid 1px #DCDCDC;
	}
	.button {
		display:inline-block;
		border: none;
	    outline: none;
	    background: #88bc40;
	    background-image: -moz-linear-gradient(top, #a2ca51, #88bc40);
	    background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0,#a2ca51), color-stop(1, #88bc40));
	    border-bottom: 1px solid #729f36;
	    color: #fff;
	    width: 45px;
	    height: 30px;
	    line-height: 25px;
	    cursor: pointer;
	    border-radius: 4px;
	    text-align: center;
	}
	</style>
</head>
<body style='font-family: 微软雅黑;'>
<center>
<p style="font-family:幼圆;font-size:28px;margin-top:80px;font-weight:bold;">根据订单号查询批量拣货单信息及失败原因</p>
<div>
<form method = "post">
	<input type = "hidden" name = "act" value = "order_batch_pick_checker"> 
    订单号：<input class="input" id = "osn" type = "text"  name = "ordersn" value = "<?php echo trim($_POST['ordersn'])?>">
    <input type = "submit" class = "button" name = "submit" value = "查询">
    <input type = "button" class = "button" name = "reset" value = "清空" onclick="document.getElementById('osn').value = '';"><br>
</form>
</div>

<div>
	<?php echo $message;?>
</div>
</center>

</body>
</html>
