<?php

/**
 * 订单明细
 * 
 * @author yxiang@oukoo.com
 * @copyright 2010 ouku.com 
 */

define('IN_ECS', true);
require_once('includes/init.php');
require_once('function.php');
require_once(ROOT_PATH .'includes/lib_order.php');
require_once(ROOT_PATH .'includes/helper/array.php');
require_once(ROOT_PATH .'RomeoApi/lib_inventory.php');


// 错误信息
$errmsg = array();

$status_id_list = array(
    'INV_STTS_AVAILABLE'=>'新品',
    'INV_STTS_USED'     =>'二手'
);

$product_id = isset($_REQUEST['product_id']) ? $_REQUEST['product_id'] : false ;
$status_id = isset($_REQUEST['status_id']) ? $_REQUEST['status_id'] : false;
if($product_id && $status_id){
    $sql="
        select
            o.order_id,o.order_status,o.pay_status,o.shipping_status,o.order_sn, o.order_time,
            o.taobao_order_sn,
            if (o.order_type_id='SALE', (select action_time from ecshop.ecs_order_action where order_id=o.order_id and order_status=1 order by action_time ASC limit 1), o.order_time) as confirm_time
        from romeo.order_inv_reserved r
            inner join romeo.order_inv_reserved_detail d on d.ORDER_INV_RESERVED_ID=r.ORDER_INV_RESERVED_ID
            inner join ecshop.ecs_order_info as o on o.order_id=r.ORDER_ID
        where
            d.PRODUCT_ID='{$product_id}' and d.STATUS_ID='{$status_id}' and
            r.STATUS='N'
        GROUP BY r.ORDER_ID    
    ";
    $order_list=$db->getAll($sql);
}

if ($order_list)  {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>订单详细</title>
  <link href="styles/default.css" rel="stylesheet" type="text/css">
  <style type="text/css">
		*.margin: 0;
  </style>
</head>
<body>


<?php if (!empty($errmsg)): ?>
<div style="width:800px; margin:0 auto; color:red; border:#000 1px solid;">
<ul>
<?php foreach ($errmsg as $msg): ?>
<li><?php print $msg; ?></li>
<?php endforeach; ?>
</ul>
</div>
<?php endif; ?>


<div style="width:800px; margin:0 auto;">

<h3>订单列表</h3> 
<table class="bWindow">
	<tr>
  		<th width="5%">No.</th>
  		<th width="15%">订单号</th>
        <th width="15%">淘宝订单号</th>
        <th width="20%">下单时间</th>
        <th width="20%">确认时间</th>
        <th width="25%">订单状态</th> 
	</tr>
    
    <?php if(!empty($order_list) && is_array($order_list)): ?>
    <?php $i=1; ?>
    <?php foreach($order_list as $order) : ?>	
    <tr>
        <td align="center"><?php echo $i++; ?></td>
        <td align="center"><?php echo $order['order_sn']; ?></td>
        <td align="center"><?php echo $order['taobao_order_sn']; ?></td>
        <td align="center"><?php echo $order['order_time']; ?></td>
        <td align="center"><?php echo $order['confirm_time']; ?></td>
        <td align="center"><?php echo get_order_status($order['order_status']); ?>，<?php echo get_pay_status($order['pay_status']); ?>，<?php echo get_shipping_status($order['shipping_status']); ?></td>
    </tr>
    <?php endforeach; ?>
    <?php endif; ?>
<table>

</div>
<br />
</body>
</html>

<?php 
} else {
?>
错误的参数
<?php } ?>
