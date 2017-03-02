<?php
define('IN_ECS', true);

require('includes/init.php');

admin_priv('customer_service_manage_order');
require("function.php");

$consult_ids = $_POST['consult_id'];
$action_time = date("Y-m-d H:i:s");
if (is_array($consult_ids)) {
	foreach ($consult_ids as $consult_id) {
		$attributes['consult_time'] = $_POST["consult_time-$consult_id"] !== null ? trim($_POST["consult_time-$consult_id"]) : null;
		$attributes['customer_name'] = $_POST["customer_name-$consult_id"] !== null ? trim($_POST["customer_name-$consult_id"]) : null;
		$attributes['consult_area'] = $_POST["consult_area-$consult_id"] !== null ? trim($_POST["consult_area-$consult_id"]) : null;
		$attributes['content'] = $_POST["content-$consult_id"] !== null ? trim($_POST["content-$consult_id"]) : null;
		$attributes['source'] = $_POST["source-$consult_id"] !== null ? trim($_POST["source-$consult_id"]) : null;
		$attributes['method'] = $_POST["method-$consult_id"] !== null ? trim($_POST["method-$consult_id"]) : null;
		$attributes['result'] = $_POST["result-$consult_id"] !== null ? trim($_POST["result-$consult_id"]) : null;
		$attributes['note'] = $_POST["note-$consult_id"] !== null ? trim($_POST["note-$consult_id"]) : null;
		$attributes['action_user'] = $_POST["action_user-$consult_id"] !== null ? trim($_POST["action_user-$consult_id"]) : null;
		
		$histories = array();
		if ($consult_id) {
			$sql = "SELECT * FROM {$ecs->table('oukoo_consult')} WHERE consult_id = '$consult_id'";
			$consult = $db->getRow($sql);
						
			$pair = array();
			foreach ($attributes AS $key=>$value) {
				if ($value !== null) {
					$pair[] = "$key='$value'";
					
					if ($value != $consult[$key]) {
						$histories[] = array('table_name'=>"{$ecs->table('oukoo_consult')}", 'field_name'=>$key, 'origin_value'=>$consult[$key], 'set_value'=>$value, 'execute_sql'=>'', 'execute_type'=>'update', 'action_user'=>$_SESSION['admin_name'], 'action_time'=>$action_time);
					}					
				}
			}
			$sql = "UPDATE {$ecs->table('oukoo_consult')} AS s SET " . join(', ', $pair) . " WHERE consult_id = '$consult_id'";
			$db->query($sql);
		} else {
			$keys = $values = array();
			foreach ($attributes AS $key=>$value) {
				if ($value !== null) {
					$keys[] = $key;
					$values[] = "'$value'";
					
					$histories[] = array('table_name'=>"{$ecs->table('oukoo_consult')}", 'field_name'=>$key, 'origin_value'=>'', 'set_value'=>$value, 'execute_sql'=>'', 'execute_type'=>'insert', 'action_user'=>$_SESSION['admin_name'], 'action_time'=>$action_time);
				}
			}
			$sql = "INSERT INTO {$ecs->table('oukoo_consult')} (" . join(', ', $keys) . ") VALUES (" . join(', ', $values) . ")";			
			$db->query($sql);
			$consult_id = $db->insert_id();
		}
		
		foreach ($histories as $key => $history) {
			$histories[$key]['execute_sql'] = mysql_escape_string($sql);
			$histories[$key]['reference_key'] = $consult_id;
		}
		
		add_history($histories, $ecs->table('oukoo_consult_action'));
	}
}

$back = $_REQUEST['back'] !== null ? $_REQUEST['back'] : "/";
Header("Location: $back");
?>