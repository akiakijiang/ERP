<?php 
	define('IN_ECS', true);
	require_once('../includes/init.php');

	$type_array = array(
		"batch_pick"=>"批捡",
		"shipment"=>"发货",
		"track"=>"面单",
		"invoice"=>"发票"
	);

	$order_type_array = array(
		"batch_pick_sn"=>"批捡单号",
		"order_sn"=>"ERP订单号",
		"shipment_sn"=>"发货单号",
		"track_sn"=>"面单号",
		"invoice_sn"=>"发票"
	);

	$record_type_array = array(
		"BATCH_PICK"=>"打印批捡单",
		"BATCH_SHIPMENT"=>"打印一个批次发货单",
		"SHIPMENT"=>"打印单独发货单",
		"BATCH_THERMAL"=>"打印一个批次的热敏面单",
		"THERMAL"=>"打印单独热敏面单",
		"ADD_BATCH_THERMAL"=>"打印批量追加的热敏面单",
		"ADD_THERMAL"=>"打印单个追加的热敏面单",
		"BILL"=>"打印同一批次的普通面单"
	);
	
	$type_status = "batch_pick";
	$order_type_status = "batch_pick_sn";
	

	
	if(isset($_POST['find_info'])){

		$find_order_record = "SELECT epa.print_type, epa.print_item, epa.order_sn, epa.create_user, epa.create_time 
                         	FROM ecshop.ecs_print_action epa ";

		$condition = "";
		$sql = "";

		$number = trim($_POST['number']);
		$type = $_POST['type'];               //第一个select
		$order_type = $_POST['order_type'];   //第二个select
		$type_status = $type;
		$order_type_status = $order_type;
		//echo $number;
		//echo $type;
		//echo $order_type;

		if($type == "batch_pick"){
			$sql = " LEFT JOIN romeo.batch_pick_mapping bpm ON bpm.batch_pick_sn = epa.print_item 
					 LEFT JOIN romeo.order_shipment os1 ON os1.SHIPMENT_ID = bpm.shipment_id  
					 LEFT JOIN romeo.order_shipment os2 ON os2.SHIPMENT_ID = os1.SHIPMENT_ID 	
					 LEFT JOIN ecshop.ecs_order_info oi ON oi.order_id = os2.ORDER_ID 	 
			";
			if($order_type == "batch_pick_sn"){
				$condition = "WHERE epa.print_item = '".$number."' AND epa.print_type = 'BATCH_PICK' GROUP BY create_time ORDER BY create_time DESC";
			}else if($order_type == "order_sn") {
				$condition = "WHERE oi.order_sn = '".$number."' AND epa.print_type = 'BATCH_PICK' GROUP BY create_time ORDER BY create_time DESC";
			}else if($order_type == "shipment_sn"){
				$condition = "WHERE os1.SHIPMENT_ID = '".$number."' AND epa.print_type = 'BATCH_PICK' GROUP BY create_time ORDER BY create_time DESC";
			}else{
				echo "<script>alert('选择错误！'); history.back(-1);</script>";
			}
			$find_order_record = $find_order_record.$sql.$condition;

		}

		if($type == "shipment"){
			$sql = " LEFT JOIN romeo.batch_pick_mapping bpm ON bpm.batch_pick_sn = epa.print_item 
	 				 LEFT JOIN romeo.order_shipment os ON os.SHIPMENT_ID = bpm.shipment_id OR os.SHIPMENT_ID = epa.print_item 
					 LEFT JOIN ecshop.ecs_order_info oi ON oi.order_id = os.ORDER_ID 
			";
			if($order_type == 'order_sn'){
				$condition = "WHERE oi.order_sn = '".$number."' AND epa.print_type IN ('SHIPMENT','BATCH_SHIPMENT') ORDER BY create_time DESC";
			}else if($order_type == 'batch_pick_sn'){
				$condition = "WHERE bpm.batch_pick_sn = '".$number."' AND epa.print_type = 'BATCH_SHIPMENT' ORDER BY create_time DESC";
			}else if ($order_type == 'shipment_sn'){
				$condition = "WHERE os.shipment_id = '".$number."' AND epa.print_type IN ('SHIPMENT','BATCH_SHIPMENT') ORDER BY create_time DESC";
			}else{
				echo "<script>alert('选择错误！'); history.back(-1);</script>";
			}
			$find_order_record = $find_order_record.$sql.$condition;
		}

		if($type == "track"){
			$sql = " LEFT JOIN romeo.shipment s ON s.TRACKING_NUMBER = epa.print_item 
					 LEFT JOIN romeo.batch_pick_mapping bpm ON bpm.shipment_id = s.SHIPMENT_ID 
					 LEFT JOIN romeo.order_shipment os ON os.SHIPMENT_ID = s.SHIPMENT_ID 
					 LEFT JOIN ecshop.ecs_order_info oi ON oi.order_id = os.ORDER_ID   
			";

			$sql_1 = " LEFT JOIN romeo.batch_pick_mapping bpm ON bpm.batch_pick_sn = epa.print_item 
					   LEFT JOIN romeo.shipment s ON s.SHIPMENT_ID = bpm.shipment_id 
					   LEFT JOIN romeo.order_shipment os ON os.SHIPMENT_ID = bpm.shipment_id 
					   LEFT JOIN ecshop.ecs_order_info oi ON oi.order_id = os.ORDER_ID 
			";

			if($order_type == 'order_sn'){
				$condition = " WHERE oi.order_sn = '".$number."'";
			}else if ($order_type == 'shipment_sn'){
				$condition = " WHERE s.shipment_id = '".$number."'";
			}else if($order_type == 'batch_pick_sn'){
				$condition = " WHERE bpm.batch_pick_sn = '".$number."'";
			}else if($order_type == 'track_sn'){
				$condition = " WHERE s.TRACKING_NUMBER = '".$number."'";
			}else{
				echo "<script>alert('选择错误！'); history.back(-1);</script>";
			}
			$find_order_record = "(".$find_order_record.$sql.$condition.") UNION (".$find_order_record.$sql_1.$condition." AND 
				 epa.print_type IN ('BATCH_THERMAL','ADD_BATCH_THERMAL','BATCH_BILL','THERMAL','ADD_THERMAL','BILL','ADD_BILL')) ORDER BY create_time DESC";
		}

		if($type == "invoice"){
			$find_order_record = "SELECT epa.print_type, epa.print_item, epa.order_sn, epa.create_user, epa.create_time 
									FROM ecshop.ecs_print_action epa 
								  WHERE epa.print_item = '".$number."'

			";
		}
		//echo "<br/>";
		//echo $find_order_record;
		$result_list = $db->getAll($find_order_record);

	}
?>
<html>
	<head>
		<meta http-equiv="content-type" content="text/html;charset=utf-8">
		<link rel="stylesheet" type="text/css" href="../bwshop/bootstrap-combined.min.css">
		<script src="../bwshop/jquery.min.js"></script>
		<script src="../bwshop/bootstrap.min.js"></script>
		<title>批捡记录查询</title>
	</head>

	<body>
		<div class = "col-xs-8"><h1>订单批捡单发货单面单操作记录查询</h1></div>
		<div class = "col-xs-12" style="margin-top:30px;">
			<form action="" method="post">
				<label style="display:inline-block;vertical-align:middle">查询类型</label>
				<select name="type" style="vertical-align:middle">
				<?php 
					foreach ($type_array as $key => $value) {
						echo "<option value='".$key."'".($key==$type_status?" selected = 'selected'" : "").">".$value."</option>";
					}
				?>
				</select>

				<select name="order_type">
				<?php 
					foreach ($order_type_array as $key => $value) {
						echo "<option value='".$key."'".($key==$order_type_status?" selected = 'selected'" : "").">".$value."</option>";
					}
				?>
				</select>

				<input type="text" name="number" style="height:30px;" value="<?php echo $number;?>" placeholder="请输入查询号码">
				<button type="submit" name="find_info" class="btn" style="margin-bottom:10px;">查询</button>
			</form>
		</div>
		
		<br/>
		<br/>
		<div class = "col-xs-10">
			<table  class="table table-bordered table-hover table-condensed">
			<?php 
				if($result_list){
			?>	
			<tr align="center">
				<td>类型:<?php echo $type_array[$type];?></td>
				<td>批件号/发货号/面单号</td>
				<td>订单号</td>
				<td>操作人</td>
				<td>操作时间</td>
			</tr>
			<?php foreach ($result_list as $result) {
			?>
			<tr>
				<td><?php echo $result['print_type'];?></td>
				<td><?php echo $result['print_item'];?></td>
				<td><?php echo $result['order_sn'];?></td>
				<td><?php echo $result['create_user'];?></td>
				<td><?php echo $result['create_time'];?></td>
			</tr>
			<?php
				}	
			}else if($number!=''&&!$result_list){
				echo "<tr><td><h2><b>未搜索到相关信息~</b></h2></td></tr>";
			}
			?>
			</table>
		</div>
		
	</body>
</html>
