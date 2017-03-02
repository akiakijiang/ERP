<?php
define('IN_ECS', true);

require('includes/init.php');
include_once 'function.php';
admin_priv('customer_service_manage_order');
// include_once('includes/lib_order_mixed_status.php');

$act = trim(strval($_REQUEST['act']));
$inv_payee = trim($_REQUEST['inv_payee']);
$time = date("Y-m-d H:i:s");
$order_id = $_REQUEST['order_id'];

$sql = "SELECT order_id, inv_payee, order_status, shipping_status, pay_status FROM {$ecs->table('order_info')} WHERE order_id = '{$order_id}'";
$order = $db->getOne($sql);
$inv_payee_old = $order['inv_payee'];

if($act == 'change_inv')
{
	if($inv_payee != $inv_payee_old)
	{
		$sql = "UPDATE {$ecs->table('order_info')} SET inv_payee = '{$inv_payee}' WHERE order_id={$order_id}";
		$db->query($sql);   
	}
}

if ($act == 'reply')
{
	$order_comment_id = $_REQUEST['order_comment_id'];
	$reply = trim(mysql_escape_string($_REQUEST['reply']));
	$sql = "
		UPDATE {$ecs->table('order_comment')} SET 
			`reply` = '{$reply}',
			`replied_by` = '{$_SESSION['admin_name']}', 
			`reply_datetime` = '{$time}',
			`reply_point` = IF(`reply_point` = 0 OR `reply_point` IS NULL, '{$time}', `reply_point`)
		WHERE order_comment_id = '{$order_comment_id}'
		LIMIT 1
	";
	$db->query($sql);
}
elseif ($act == 'confirm_order')
{
	$order_comment_id = $_REQUEST['order_comment_id'];
	$reply = trim(mysql_escape_string($_REQUEST['reply']));
	$sql = "
		UPDATE {$ecs->table('order_comment')} SET 
			`reply` = '{$reply}',
			`replied_by` = '{$_SESSION['admin_name']}',
			`reply_datetime` = '{$time}', 
			`reply_point` = IF(`reply_point` = 0 OR `reply_point` IS NULL, '{$time}', `reply_point`)
		WHERE order_comment_id = '{$order_comment_id}'
		LIMIT 1
	";
	$db->query($sql);
	
  	$order = $db->getRow("SELECT order_id, mobile FROM {$ecs->table('order_info')} WHERE order_sn = '{$_POST['order_sn']}'");
  	orderActionLog(array('order_id'=> $order['order_id'],'order_status'=>1,'shipping_status'=>0, 'action_note'=>trim(mysql_escape_string($_REQUEST['reply']))));
  	$sql = "UPDATE {$ecs->table('order_info')} SET order_status = '1', confirm_time = '".time()."' WHERE order_sn = '{$_POST['order_sn']}' LIMIT 1";
	$db->query($sql);
	
	// update_order_mixed_status($order['order_id'], array('order_status' => 'confirmed'), 'worker', $_REQUEST['reply']);
}
elseif ($act == 'add') {
	$order_id = $_REQUEST['order_id'];
	$comment = $_REQUEST['comment'];
	$comment_cat = $_REQUEST['comment_cat'];
	$reply = $_REQUEST['reply'];
	$sql = "
	   INSERT INTO {$ecs->table('order_comment')} (order_id, comment_cat, comment, post_datetime, reply, replied_by, reply_datetime, reply_point, status) 
	   VALUES ('%d', '%d', '%s', '{$time}', '%s', '{$_SESSION['admin_name']}', '{$time}', '{$time}', 'OK')
    ";
    $result = $db->query(sprintf($sql, $order_id, $comment_cat, $comment, $reply), 'SILENT');
    if ($result) {
        orderActionLog(array('order_id'=> $order['order_id'],'order_status'=>$order['order_status'],'shipping_status'=>$order['shipping_status'], 'pay_status'=>$order['pay_status'], 'action_note'=>trim(mysql_escape_string('前台展示：' .$reply))));
    }
}


$back = $_REQUEST['back'] === null ? 'order_reply.php' : $_REQUEST['back'];
header("location: $back");

?>