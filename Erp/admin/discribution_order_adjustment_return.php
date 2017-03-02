<?php
/**
 * 返还预付款
 */
define('IN_ECS', true);
require_once('includes/init.php');
if (!check_admin_priv('distribution_order_adjustment_manage')) {
    die('缺少[订单调价管理]权限');
}
require_once('function.php');
require_once('distribution.inc.php');
require_once(ROOT_PATH . 'RomeoApi/lib_payment.php');

$order_id = isset($_REQUEST['order_id']) ? (int)$_REQUEST['order_id'] : false ;
$order_sql="SELECT order_id,party_id,order_sn,distributor_id,taobao_order_sn,distribution_purchase_order_sn FROM {$ecs->table('order_info')} WHERE order_id = '{$order_id}' LIMIT 1";
if ($order_id && $order = $db->getRow($order_sql)) 
{
    /**
     * 提交处理
     */
    if($_POST && !empty($_POST['return'])){
        // 要返还的金额
        $return_amount=0;
        // 备注
        $note=$_POST['note'];
        // 交易时间
        $transaction_date=$_POST['transaction_date'];

        $sql="SELECT * FROM distribution_order_adjustment WHERE order_id=%d AND goods_id=%d AND style_id=%d AND group_id=%d AND type='%s' AND status='CONSUMED' LIMIT 1";
        $inserts=array();
        foreach($_POST['return'] as $return){
            if(!isset($return['checked'])||$return['checked']!=1||!is_numeric($return['num'])||$return['num']<1){
                continue;
            }
        	
            // 查询原来的记录
            $row=$db->getRow(sprintf($sql,$return['order_id'],$return['goods_id'],$return['style_id'],$return['group_id'],$return['type']));
            if($row){
            	if ($return['num']>$row['num']) {
                    continue;
            	}
            	
            	$amount=round($return['unit_price']*$return['num'], 6);
                $return_amount+=$amount;
                $row['amount']=-$amount;
                $row['status']='RETURNED';
                $row['num']=$return['num'];
                $inserts[]=$row;
            }
        }


        // 返还预付款
        if($return_amount>0){
            // 取得订单的分销商
            $sql2="SELECT main_distributor_id FROM distributor WHERE distributor_id=%d LIMIT 1";
            $partner_id=$db->getOne(sprintf($sql2,$order['distributor_id']));
            $party_id=$order['party_id'];
        	
            // 预付款预付方式（银行）
            $prepay_payment_type_list=prepay_payment_type_list();
            $prepay_payment_type=isset($prepay_payment_type_list['3139385'])?$prepay_payment_type_list['3139385']:reset($prepay_payment_type_list);
            $payment_type_id=$prepay_payment_type->prepaymentPaymentTypeId;

            $prepay_add_result=prepay_add(
                $partner_id, 
                $party_id, 
                $payment_type_id,   // 付款方式，现金
                0,                  // 最小金额，这个只在创建交易时有用
                $return_amount,     // 付款金额 
                $transaction_date,  // 交易时间 
                $_SESSION['admin_name'],
                $note,
                'DISTRIBUTOR'
            );
            switch($prepay_add_result){
                case -1:
                    $message="账户不存在，并尝试添加该预付款账户失败";
                    break;
                case 0:
                    $message="添加预付款失败";
                    break;
                // 添加预付款成功
                default:
                    $insert_sql="INSERT INTO distribution_order_adjustment (order_id,goods_id,style_id,goods_name,group_id,group_name,num,amount,type,status,prepayment_transaction_id,created_by_user_login,created) VALUES ";
                    foreach($inserts as $row)
                        $insert_sql_rows[] = "('{$row['order_id']}','{$row['goods_id']}','{$row['style_id']}','{$row['goods_name']}','{$row['group_id']}','{$row['group_name']}','{$row['num']}','{$row['amount']}','{$row['type']}','{$row['status']}','{$prepay_add_result}','{$_SESSION['admin_name']}','".date('Y-m-d H:i:s')."')";	
                    $insert_sql.=implode(',',$insert_sql_rows);
                    $db->query($insert_sql);
                    $message="操作成功";
            }
        }
        else {
            $message="返回金额为0，请选择返还项";
        }
    }
	
	
    // 查询预付款
    $sql="
        SELECT *, SUM(amount) as total_amount, SUM(IF(status='RETURNED',-num,num)) as total_num
        FROM distribution_order_adjustment 
        WHERE order_id = '{$order['order_id']}' AND status IN ('CONSUMED','RETURNED')  
        GROUP BY goods_id, style_id, group_id, type
    ";
    $list=$db->getAll($sql);
    if($list){
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>返还预付款</title>
  <link href="styles/default.css" rel="stylesheet" type="text/css">
  <style type="text/css">
		*.margin: 0;
	</style>
</head>
<body>

<?php if($message):?>
<div style="text-align:center;color:red;"><strong><?php echo $message;?></strong></div>
<?php endif;?>


<form method="post" id="123" style="text-align:center; margin:0px;">

<div style="width:800px; margin:0 auto;">
<h4 style="text-align:left;">可返还项：</h4>
<table class="bWindow">
	<tr align="center">
  	<th width="30%">商品名/套餐名</th>
    <th width="20%">调价类型</th>
    <th width="12%">可返还金额</th>
    <th width="12%">可返还数量</th>
    <th width="26%">返还数</th>
	</tr>
  
  <?php if (!empty($list) && is_array($list)) : foreach ($list as $key=>$item) :?>
    <?php
        if ($item['total_num']>0) {
            $item['unit_price']=round($item['total_amount']/$item['total_num'], 6);	
        }
    ?>
	<tr>
  	<td align="center"><?php if($item['group_id']): ?><?php print $item['goods_name'] ?><?php ;else:?><?php print $item['goods_name']; ?><?php endif;?></td>
    <td align="center"><?php if($item['type']=='SHIPPING_ADJUSTMENT'):?>运费调整<?php ;else:?>商品金额调整<?php endif;?></td>
    <td align="center"><?php printf('%01.6f', $item['total_amount']); ?></td>
    <td align="center"><?php echo $item['total_num'];?></td>
    <td align="center">
        <?php if($item['total_amount']>0&&$item['total_num']>0): ?>
        <input type="checkbox" name="return[<?php echo $key;?>][checked]" value="1" />
        &nbsp;&nbsp;
        <input type="text" name="return[<?php echo $key;?>][num]" value="<?php echo $item['total_num']?>" size="4" maxlength="4" />
        
        <input type="hidden" name="return[<?php echo $key;?>][order_id]" value="<?php echo $item['order_id']?>" />
        <input type="hidden" name="return[<?php echo $key;?>][goods_id]" value="<?php echo $item['goods_id']?>" />
        <input type="hidden" name="return[<?php echo $key;?>][style_id]" value="<?php echo $item['style_id']?>" />
        <input type="hidden" name="return[<?php echo $key;?>][group_id]" value="<?php echo $item['group_id']?>" />
        <input type="hidden" name="return[<?php echo $key;?>][type]" value="<?php echo $item['type']?>" />
        <input type="hidden" name="return[<?php echo $key;?>][unit_price]" value="<?php echo $item['unit_price']?>" />
        <?php endif;?>
    </td>
	</tr>
  <?php endforeach; endif; ?>
<table>

<br />

<table>
    <tr>
        <td colspan="2" align="left"><input type="text" name="transaction_date" size="20" value="<?php echo date('Y-m-d H:i:s'); ?>" /> 交易时间</td>
    </tr>
    <tr><td colspan="2">&nbsp;</td></tr>
	<tr>
  	    <td><textarea name="note" style="height:40px;" cols="60">返还订单调价预付款，订单号<?php echo $order['order_sn'];?></textarea></td>
        <td><input type="submit" value="提交" style="height:40px; width:40px;" /></td>
	</tr>
<table>
	
<input type="hidden" name="order_id" value="<?php print $order_id; ?>" />

</div>
</form>


</body>
</html>
<?php 
    }
    else {
        print "<br /><div style=\"text-align:center;\"><h2>没有订单调整项</h2></div>";   
    }
?>

<?php } else {?>

错误的订单号

<?php } ?>