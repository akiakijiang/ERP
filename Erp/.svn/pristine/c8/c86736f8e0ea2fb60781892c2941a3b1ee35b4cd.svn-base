<?php	
	define('IN_ECS', true);
	require_once('../includes/init.php');
	// header("content-type:text/html;charset=utf-8");

	if(!empty($_REQUEST['tracking_number'])){
		$sql_order = "SELECT obp.* from romeo.shipment s 
		inner join romeo.out_batch_pick_mapping obpm on s.shipment_id=obpm.shipment_id 
		inner join romeo.out_batch_pick obp on obpm.batch_pick_id = obp.batch_pick_id 
		where s.tracking_number ='{$_REQUEST['tracking_number']}'";
		$sql_result = $db->getAll($sql_order);
	}
	else{
		$sql_result = array();
	}
?>
<html>
<head>
	<meta http-equiv="content-type" content="text/html;charset=utf-8">
	<title>根据快递单号查外包批次</title>
</head>
<body>
<h1>根据快递单号查外包批次：</h1>
<form>
    快递单号：<input type = "text" name = "tracking_number">
    <input type = "submit" name = "submit" value = "search">
</form>
<hr>
<?php
	if(!empty($sql_result)){
		echo "<table border = '1'><tr>";
		foreach($sql_result as $value){
			foreach($value as $key => $val){
				echo "<th>$key</th>";
			}
			break;
		}
		echo "</tr>";
		foreach($sql_result as $_value){
			echo "<tr>";
			foreach($_value as $_val){
				echo "<td>$_val</td>";
			}
		echo "</tr>";
		}	
		echo "</table>";
	}else{
		echo "什么都没有";
	}
?>
<hr>
<div> All Hail Sinri Edogawa! </div>
</body>
</html>