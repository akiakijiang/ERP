<?php

/**
 * 分销订单导入功能 （查看导入数据的明细）
 * 
 * @author yxiang@oukoo.com
 * @copyright 2009 ouku.com 
 */

define('IN_ECS', true);
require_once('includes/init.php');
require_once('distribution.inc.php');
admin_priv('distribution_order_import');
require_once(ROOT_PATH .'includes/lib_order.php');
require_once(ROOT_PATH .'includes/helper/array.php');


// 错误信息
$errmsg = array();

$order_id = isset($_REQUEST['order_id']) ? (int)$_REQUEST['order_id'] : false ;
if ($order_id &&
		$order = $db->getRow("SELECT * FROM distribution_import_order_info WHERE order_id = '{$order_id}' LIMIT 1")) 
{
        // 根据淘宝订单的支付宝账号查分销商
        if (!empty($order['distributor_name'])) {
            $sql = "SELECT * FROM distributor WHERE name = '{$order['distributor_name']}' AND status = 'NORMAL' LIMIT 1";
            $distributor = $db->getRow($sql);
            if (!$distributor) {
               $errmsg[] = "系统中不存在该淘宝订单的分销商: {$order['distributor_name']}"; 
            }
        } else {
            $errmsg[] = "导入数据中没有分销商帐号";
        }
    
        // 查询出订单商品 (必须限制商品和订单为同一批次的，因为可能存在同样淘宝订单号的订单已经删除)
        $sql = "
            SELECT * FROM distribution_import_order_goods
            WHERE taobao_order_sn = '{$order['taobao_order_sn']}' AND batch_no = '{$order['batch_no']}'  -- 同一批次
        ";		    
        $goods_list = $db->getAll($sql);
        if (!$goods_list) {
            $errmsg[] = "导入的数据中没有相关的商品信息";
        } else {
            // 商品的party_id数组
            $_goods_party_ids = array();  
            
            foreach ($goods_list as $key => $goods) {
                // 套餐
                if (strpos($goods['goods_code'], 'TC-') !== false) {
                    $group = distribution_get_group_goods(null, $goods['goods_code']);
                    if ($group && $group['item_list']) {
                        $goods_list[$key]['goods_detail'] = $group['item_list'];
                        $_goods_party_ids = array_merge($_goods_party_ids, Helper_Array::getCols($group['item_list'], 'goods_party_id'));
                    } else {
                        $errmsg[] = "系统中不存在该套餐：‘{$goods['goods_code']}’";
                    }
                }
                // 商品
                else if (is_numeric($goods['goods_code'])) {
                    $g = distribution_get_goods($goods['goods_code']);
                    if ($g) {
                        $g['goods_number'] = $goods['goods_number']; 
                        $goods_list[$key]['goods_detail'][] = $g;
                        $_goods_party_ids[] = $g['goods_party_id'];
                    } else {
                        $errmsg[] = "系统中查询不到该商品：‘{$goods['goods_name']}’ code: ‘{$goods['goods_code']}’";
                    }
                }
                // 商品编码未维护
                else {
                    $errmsg[] = "{$goods['goods_name']}的商品编码未维护";
                }
            }
        }
		
        if (!empty($_goods_party_ids)) {
            // 商品的party_id不统一
            if (count(array_unique($_goods_party_ids)) != 1) {
                $errmsg[] = "该订单商品的party_id不统一，请检查基础数据";
            }
        	
        	// 商品和分销商的party_id不统一
        	// TODO 一个分销商可能在不同的party下有账户
//            if ($distributor && $distributor['party_id'] != reset($_goods_party_ids)) {
//                $errmsg[] = "商品的party_id和该分销商的party_id不一致，请检查基础数据";
//            }
        }
		
        // 已经生成订单了, 则显示已生成订单的信息
        if ($order['refer_order_sn']) {
            $imported_order = order_info(0, $order['refer_order_sn']);
        }
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

<h3>导入订单信息</h3> 
<table class="bWindow">
	<tr>
  		<td align="center" width="30%">淘宝订单号</td>
    	<td>&nbsp;&nbsp;<strong><?php print $order['taobao_order_sn']; ?></strong> &nbsp;&nbsp;[导入操作人：<?php print $order['create_by_user_login']; ?>]</td>
	</tr>
	
	<tr>
  		<td align="center">分销采购单号</td>
    	<td>&nbsp;&nbsp;<?php print $order['distribution_purchase_order_sn']; ?></td>
	</tr>
	
	<tr>
  		<td align="center">分销商</td>
    	<td>&nbsp;&nbsp;<?php print $order['distributor_name']; ?></td>
	</tr>
	
    <tr>
        <td align="center">支付宝账号</td>
        <td>&nbsp;&nbsp;<?php print $order['alipay_account']; ?></td>
    </tr>
    
    <tr>
        <td align="center">订单金额</td>
        <td>&nbsp;&nbsp;<strong><?php print $order['order_amount']; ?></strong></td>
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


<h3>导入商品信息</h3> 
<table class="bWindow">
	<tr align="center">
    <th width="30%">商品名称</th>
    <th width="15%">商品编码</th>
    <th width="8%">数量</th>
    <th width="8%">单价</th>
    <th><font color="red">匹配信息</font></th>
	</tr>
  
  <?php if (!empty($goods_list) && is_array($goods_list)) : foreach ($goods_list as $item) :?>
	<tr>
  	<td align="center"><?php print $item['goods_name']; ?></td>
  	<td align="center"><?php print $item['goods_code']; ?></td>
    <td align="center"><?php print $item['goods_number']; ?></td>
    <td align="center"><?php print $item['goods_price']; ?></td>
    <td align="center" style="color:red;">
        <?php if (!empty($item['goods_detail'])) : ?>
        <?php foreach ($item['goods_detail'] as $g) : ?>
        	<?php print $g['goods_name']; ?><br />
        <?php endforeach; ?>
        <?php ;else: ?>
        	Warning: 没有商品匹配 ！
        <?php endif; ?>
	</td>
	</tr>
  <?php endforeach; endif; ?>
<table>



<?php if ($order['refer_order_sn']) : ?>
<h3>生成订单信息</h3>
<table class="bWindow">
	<tr>
	<td width="30%" align="center">订单号</td>
	<td>&nbsp;&nbsp;<a href="distribution_order_edit.php?order_id=<?php print $imported_order['order_id']; ?>" target="_blank"><?php print $imported_order['order_sn']; ?></a> &nbsp;&nbsp;[订单生成操作人： <?php print $order['imported_by_user_login']; ?>]</td>
	</tr>
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