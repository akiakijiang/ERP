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

$order_sn = isset($_REQUEST['order_sn']) ? $_REQUEST['order_sn'] : false ;
if ($order_sn && $order = order_info(NULL, $order_sn))  {
    // 根据淘宝订单的支付宝账号查分销商
    if ($order['distributor_id'] > 0) {
        $sql = "SELECT * FROM distributor WHERE distributor_id = '{$order['distributor_id']}' AND status = 'NORMAL' LIMIT 1";
        $distributor = $db->getRow($sql);
        if (!$distributor) {
           $errmsg[] = "系统中不存在该订单的分销商: #{$order['distributor_id']}"; 
        }
    }
    
    // 订单的商品列表
    $goods_list = order_goods($order['order_id']);
    
    // 订单操作信息
    $sql = "
        SELECT * FROM {$ecs->table('order_action')} WHERE order_id = '{$order['order_id']}' ORDER BY action_time ASC
    ";
    $action_list = $db->getAll($sql);

    // 订单商品的预定状态
    $order_goods_inventory = get_order_goods_inventory($order['order_id'], $order['party_id']);
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

<h3>订单信息</h3> 
<table class="bWindow">
	<tr>
  		<td align="center" width="20%">订单状态</td>
    	<td>&nbsp;&nbsp;
    	   <strong><?php print $order['formated_order_status']; ?></strong>，
    	   <strong><?php print $order['formated_pay_status']; ?></strong>（<?php print $order['pay_name']; ?>），
    	   <strong><?php print $order['formated_shipping_status']; ?></strong>（<?php print $order['shipping_name']; ?>）
    	</td>
	</tr>
    
    <tr>
        <td align="center">订单金额</td>
        <td>&nbsp;&nbsp;
            <strong><?php print $order['order_amount']; ?></strong> =
            <strong><?php print $order['goods_amount']; ?></strong>（商品总金额）+
            <strong><?php print $order['shipping_fee']; ?></strong>（配送费用）+
            <strong><?php print $order['pack_fee']; ?></strong>（包装费用）
            <strong><?php print $order['bonus']; ?></strong>（抵用券抵用）
        </td>
    </tr>
	
	<tr>
  		<td align="center">收货人</td>
    	<td>&nbsp;&nbsp;<?php print $order['consignee']; ?></td>
	</tr>
	
    <tr>
        <td align="center">订单备注</td>
        <td style="color:red;">&nbsp;&nbsp;<?php print $order['postscript']; ?></td>
    </tr>
    
    <tr>
        <td align="center">预订时间</td>
        <td style="color:red;">&nbsp;&nbsp;
            <?php if ($order_goods_inventory) : ?>
            <?php print date('Y-m-d H:i:s', strtotime($order_goods_inventory['order']['reservedTime'])); ?>
            <?php ;else: ?>
                                未预订
            <?php endif; ?>
        </td>
    </tr>
	
<table>


<?php if (!empty($goods_list) && is_array($goods_list)): ?>
<h3>商品信息</h3> 
<table class="bWindow">
	<tr>
    <th width="40%">商品名称</th>
    <th width="15%">现有库存</th>
    <th width="10%">可预订量</th>
    <th width="10%">已预订量</th>
    <th width="10%">商品状态</th>
    <th width="10%">单价</th>
    <th width="5%">数量</th>
	</tr>
  
  <?php foreach ($goods_list as $item) : ?>
	<tr>
  	<td align="center">&nbsp;&nbsp;<?php print $item['goods_name']; ?></td>
  	<td style="color:red;">
        <?php if ( $order_goods_inventory 
                   && isset($order_goods_inventory[$item['goods_id'].'_'.$item['style_id']])
                   && $order_goods_inventory[$item['goods_id'].'_'.$item['style_id']]['stockQuantity'] > 0 ) : 
        ?>
        <?php foreach ($order_goods_inventory[$item['goods_id'].'_'.$item['style_id']]['facilityQuantity'] as $facility_id => $facility_stock): ?>
        <?php print '&nbsp;&nbsp;&nbsp;'.facility_mapping($facility_id); ?> : <?php print $facility_stock['stockQuantity']; ?> <br />
        <?php endforeach; ?>
        <?php ;else: ?>
                     无
        <?php endif; ?>
  	</td>
  	<td align="center">
        <?php if ($order_goods_inventory && isset($order_goods_inventory[$item['goods_id'].'_'.$item['style_id']]['facilityQuantity'][$order_goods_inventory['order']['facilityId']]['availableToReserved'])) : ?>
        <?php print $order_goods_inventory[$item['goods_id'].'_'.$item['style_id']]['facilityQuantity'][$order_goods_inventory['order']['facilityId']]['availableToReserved']; ?>
        <?php ;else: ?>
                     无
        <?php endif; ?>
    </td>
    <td align="center">
        <?php if ($order_goods_inventory && isset($order_goods_inventory[$item['goods_id'].'_'.$item['style_id']]['reservedQuantity'])): ?>
        <?php print $order_goods_inventory[$item['goods_id'].'_'.$item['style_id']]['reservedQuantity']; ?>
        <?php ;else: ?>
                     无
        <?php endif; ?>
    </td>
    <td align="center">
        <?php if ($order_goods_inventory && isset($order_goods_inventory[$item['goods_id'].'_'.$item['style_id']]['statusId'])): ?>
        <?php print $status_id_list[$order_goods_inventory[$item['goods_id'].'_'.$item['style_id']]['statusId']]; ?>
        <?php ;else: ?>
                     无
        <?php endif; ?>
    </td>
    <td align="center"><?php print $item['goods_price']; ?></td>
    <td align="center"><?php print $item['goods_number']; ?></td>
	</tr>
  <?php endforeach; ?>
<table>
<?php endif; ?>



<?php if (!empty($action_list) && is_array($action_list)) : ?>
<h3>订单操作信息</h3>
<table class="bWindow">
    <tr align="center">
        <th width="20%">订单状态</th>
        <th width="12%">操作人</th>
        <th width="18%">操作时间</th>
        <th width="50%">备注</th>
    </tr>
    
    <?php foreach ($action_list as $action) : ?>
	<tr align="center">
        <td>
            <?php print get_order_status($action['order_status']); ?>，
            <?php print get_pay_status($action['pay_status']); ?>，
            <?php print get_shipping_status($action['shipping_status']); ?>
        </td>
        <td><?php print $action['action_user']; ?></td>
        <td><?php print $action['action_time']; ?></td>
        <td style="text-align:left; padding-left:5px;"><?php print $action['action_note']; ?></td>
	</tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>



</div>
<br />
</body>
</html>

<?php 
} else {
?>
错误的订单号
<?php } ?>