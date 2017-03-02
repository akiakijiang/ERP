<?php
/**
 * 查看添加订单备注
 */
define('IN_ECS', true);
require_once('includes/init.php');
admin_priv('distribution_order_manage');
include_once('function.php');
// include_once('includes/lib_order_mixed_status.php');

$order_id = isset($_REQUEST['order_id']) ? (int)$_REQUEST['order_id'] : false ;
if ($order_id &&
		$order = $db->getOne("SELECT * FROM {$ecs->table('order_info')} WHERE order_id = '{$order_id}' LIMIT 1")) 
{
		// 添加备注
		if (isset($_POST) && !empty($_POST['note'])) {
	        $order = $shopapi_client->getOrderById($order_id);
	        $order->actionUser = $_SESSION['admin_name'];
	        $order->actionNote = $_POST['note'];
	        $shopapi_client->updateOrder($order);
	        // update_order_mixed_status_note($order_id, 'worker', $order->actionNote);	
		}
	
		// 查询出备注列表
		$sql = "SELECT * FROM {$ecs->table('order_action')} WHERE order_id = '{$order_id}'";
		$result = $db->query($sql);
		if ($result) {
			$order_actions = array();
			while ($row = $db->fetchRow($result)) {
				// 得到混合状态
				$row['mix_status'] = get_order_status($row['order_status']) . '，' . 
					get_pay_status($row['pay_status']) . '，' . get_shipping_status($row['shipping_status']) ;
				$order_actions[] = $row;
			}	
		}
		
		//查询order_id对应的退换货订单的备注列表
		$sql = "SELECT eoa.* FROM {$ecs->table('order_action')} eoa 
				inner join ecshop.order_relation eor on eoa.order_id = eor.order_id
				where eor.root_order_id  = '{$order_id}'
		";		
		
		$result = $db->query($sql);
		if ($result) {
			while ($row = $db->fetchRow($result)) {
				$row['mix_status'] = get_order_status($row['order_status']) . '，' . 
					get_pay_status($row['pay_status']) . '，' . get_shipping_status($row['shipping_status']) ;
				$order_actions[] = $row;
			}	
		}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>添加订单备注</title>
  <link href="styles/default.css" rel="stylesheet" type="text/css">
  <style type="text/css">
		*.margin: 0;
	</style>
</head>
<body>

<div style="width:800px; margin:0 auto;">
<table class="bWindow">
	<tr align="center">
  	<th width="180">订单状态</th>
    <th width="70">操作人</th>
    <th width="120">操作时间</th>
    <th>备注</th>
	</tr>
  
  <?php if (!empty($order_actions) && is_array($order_actions)) : foreach ($order_actions as $item) :?>
	<tr>
  	<td align="center"><?php print $item['mix_status']; ?></td>
    <td align="center"><?php print $item['action_user']; ?></td>
    <td align="center"><?php print $item['action_time']; ?></td>
    <td>&nbsp;<?php print $item['action_note']; ?></td>
	</tr>
  <?php endforeach; endif; ?>
<table>

<br />

<form method="post" id="123" style="text-align:center; margin:0px;">
<table>
	<tr>
  	<td><textarea name="note" style="height:40px;" cols="60"></textarea></td>
    <td><input type="submit" value="提交" style="height:40px; width:40px;" /></td>
	</tr>
<table>
	<input type="hidden" name="order_id" value="<?php print $order_id; ?>" />
</form>
</div>


</body>
</html>

<?php 
} else {
?>
错误的订单号
<?php } ?>