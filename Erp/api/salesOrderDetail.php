<?php
/**
 * 销售订单查看
 * 
 * @author yxiang@oukoo.com
 * @copyright 2009 ouku.com
 */

define('IN_ECS', true);

require_once(dirname(__FILE__) . '/init.php');
require_once(ROOT_PATH . 'includes/lib_order.php');
require_once(ROOT_PATH . 'includes/cls_json.php');
include_once(ROOT_PATH . 'admin/function.php'); 
// include_once(ROOT_PATH . 'admin/includes/lib_order_mixed_status.php');
require_once(ROOT_PATH . 'includes/helper/array.php');

$order_sn = isset($_GET['orderSn']) ? trim($_GET['orderSn']) : false ; 
if ($order_sn &&
		$order = $db->getRow("SELECT * FROM {$ecs->table('order_info')} WHERE order_sn = '{$order_sn}' LIMIT 1")) 
{
    // 订单状态
    $order['status_name'] = get_order_status($order['order_status']) . '，' . get_pay_status($order['pay_status']) . '，' . get_shipping_status($order['shipping_status']);

    // 查询出订单商品 
    $sql = "
        SELECT goods_name, goods_number, goods_price, CONCAT_WS('#', goods_id, style_id) as goods_code FROM {$ecs->table('order_goods')} 
        WHERE order_id = '{$order['order_id']}' 
    ";		    
    $order['goods_list'] = $db->getAll($sql);
        
    // 查询出订单属性
    $sql = "SELECT attr_name, attr_value FROM order_attribute WHERE order_id = '{$order['order_id']}'";
    $attrs = $db->getAll($sql);
    if ($attrs) {
        foreach ($attrs as $attr) {
            $order['attrs'][$attr['attr_name']] = $attr['attr_value'];
        }
    }

    // 订单操作信息
    $order['action'] = $db->getAll("SELECT * FROM {$ecs->table('order_action')} WHERE order_id = '{$order['order_id']}' ORDER BY action_id ASC");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>订单详细</title>
  <link href="../admin/styles/default.css" rel="stylesheet" type="text/css">
  <style type="text/css">
		*.margin: 0;
  </style>
</head>
<body>


<div style="width:800px; margin:0 auto;">

<h3>订单信息</h3> 
<table class="bWindow">
	<tr>
  		<td align="center" width="30%">外部订单号</td>
    	<td>&nbsp;&nbsp;<strong><?php print $order['taobao_order_sn']; ?></strong></td>
	</tr>
	
    <tr>
        <td align="center">订单金额</td>
        <td>&nbsp;&nbsp;<strong><?php print $order['order_amount']; ?></strong></td>
    </tr>

    <tr>
        <td align="center">订单状态</td>
        <td>&nbsp;&nbsp;<strong><?php print $order['status_name']; ?></strong></td>
    </tr>
	
	<tr>
  		<td align="center">收货人</td>
    	<td>&nbsp;&nbsp;<?php print $order['consignee']; ?></td>
	</tr>
	
	<tr>
  		<td align="center">收货地址</td>
    	<td>&nbsp;&nbsp;<?php print $order['address']; ?></td>
	</tr>
	
	<tr>
  		<td align="center">收货联系电话</td>
    	<td>&nbsp;&nbsp;<?php print $order['tel']; ?></td>
	</tr>
	
	<tr>
  		<td align="center">收货联系手机</td>
    	<td>&nbsp;&nbsp;<?php print $order['mobile']; ?></td>
	</tr>
	
	<tr>
  		<td align="center">订单备注</td>
    	<td>&nbsp;&nbsp;<?php print $order['postscript']; ?></td>
	</tr>
<table>


<h3>商品信息</h3> 
<table class="bWindow">
	<tr align="center">
    <th width="30%">商品名称</th>
    <th width="30%">商品编码</th>
    <th width="20%">数量</th>
    <th width="20%">单价</th>
	</tr>
  
  <?php if (!empty($order['goods_list']) && is_array($order['goods_list'])) : foreach ($order['goods_list'] as $item) :?>
	<tr>
  	<td align="center"><?php print $item['goods_name']; ?></td>
  	<td align="center"><?php print $item['goods_code']; ?></td>
    <td align="center"><?php print $item['goods_number']; ?></td>
    <td align="center"><?php print $item['goods_price']; ?></td>
	</tr>
  <?php endforeach; endif; ?>
<table>

<br />
<h3>订单操作信息</h3> 
<table class="bWindow">
	<tr align="center">
    <th width="30%">订单状态</th>
    <th width="30%">订单备注</th>
    <th width="20%">操作时间</th>
    <th width="20%">操作人</th>
	</tr>
  
  <?php if (!empty($order['action']) && is_array($order['action'])) : foreach ($order['action'] as $item) :?>
	<tr>
    <td align="center"><?php print get_order_status($item['order_status']) .'，'. get_shipping_status($item['shipping_status']) .'，'. get_pay_status($item['pay_status']); ?></td>
    <td align="left"><?php print $item['action_note']; ?></td>
  	<td align="center"><?php print $item['action_time']; ?></td>
  	<td align="center"><?php print $item['action_user']; ?></td>
	</tr>
  <?php endforeach; endif; ?>
<table>


</div>
<br />
</body>
</html>

<?php 
} else {
?>
错误的订单号
<?php } ?>

