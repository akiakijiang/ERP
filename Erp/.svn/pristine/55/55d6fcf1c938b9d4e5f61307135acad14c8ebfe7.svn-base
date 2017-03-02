<?php
 
/**
 * 调整预付款,每月都要手动调整,太麻烦了。。
 * 
 * @author 
 */
define('IN_ECS', true);
require_once('includes/init.php');
admin_priv('cw_prepayment_ajustment');
require_once('function.php');
require_once(ROOT_PATH . 'includes/debug/lib_log.php');
require_once(ROOT_PATH . 'includes/helper/uploader.php');

$act = $_REQUEST['act'];
$ajax = $_REQUEST['ajax'];
$party_id = $_SESSION['party_id'];
$order_sn = trim($_REQUEST['order_sn']);
$taobao_order_sn = trim($_REQUEST['taobao_order_sn']);
$goods_id = trim($_REQUEST['goods_id']);
//var_dump($party_id);

if($act == 'search'){
    if(empty($goods_id)){
        $smarty->assign('message', '请选择商品！');
        $smarty->display('oukooext/prepayment_ajustment.htm');
        exit();
    }
     $re = search_order($party_id,$order_sn,$taobao_order_sn,$goods_id);
     $order_sn = $re[0]['order_sn'];
     $taobao_order_sn = $re[0]['taobao_order_sn'];
     $goods_name = $re[0]['goods_name'];
     $goods_price = $re[0]['adjust_fee'];
     $goods_number = $re[0]['goods_number'];
     //var_dump($taobao_order_sn);
     $smarty->assign('order_sn',$order_sn);
     $smarty->assign('taobao_order_sn',$taobao_order_sn);
     $smarty->assign('goods_name',$goods_name);
     $smarty->assign('goods_price',$goods_price);
     $smarty->assign('goods_number',$goods_number);
}elseif($act == 'upload_search'){
    $tpl = array('预存款调整'  =>
        array('order_sn'=>'订单号',
            'taobao_order_sn'=>'淘宝订单号',
            'goods_name'=>'商品名称',
            'type'=>'类型',
            'change_amount'=>'单价'
        ));
    
    /* 文件上传并读取 */
    @set_time_limit(300);
    $uploader = new Helper_Uploader();
    $max_size = $uploader->allowedUploadSize();  // 允许上传的最大值
    
    if (!$uploader->existsFile('prepayment_file')) {
        $smarty->assign('message', '没有选择上传文件，或者文件上传失败');
        $smarty->display('oukooext/prepayment_ajustment.htm');
        exit();
    }
    
    // 取得要上传的文件句柄
    $file = $uploader->file('prepayment_file');
     
    // 检查上传文件
    if (!$file->isValid('xls, xlsx', $max_size)) {
        $smarty->assign('message', '非法的文件! 请检查文件类型类型(xls, xlsx), 并且系统限制的上传大小为'. $max_size/1024/1024 .'MB');
        $smarty->display('oukooext/prepayment_ajustment.htm');
        exit();
    }
    
    // 读取excel
    $result = excel_read($file->filepath(), $tpl, $file->extname(), $failed);
    if (!empty($failed)) {
        $smarty->assign('message', reset($failed));
        $smarty->display('oukooext/prepayment_ajustment.htm');
        exit();
    }
    
    /* 检查数据  */
    $rowset = $result ['预存款调整'];
    
    // 订单数据读取失败
    if (empty($rowset)) {
        $smarty->assign('message', 'excel文件中没有数据,请检查文件');
        $smarty->display('oukooext/prepayment_ajustment.htm');
        exit();
    }

    foreach ($rowset as $key=>$value){
        $pre_goods_res = get_goods_id_by_name($value['goods_name']);
        if(empty($pre_goods_res)){
            $smarty->assign('message', $value['goods_name'].'名称有误！');
            $smarty->display('oukooext/prepayment_ajustment.htm');
            exit();
        }
        if($value['type'] == 'error'){
            $res = pre_error($pre_goods_res[0]['goods_id'],$value['order_sn'],$value['taobao_order_sn'],$value['change_amount']);
        }elseif($value['type'] == 'none'){
            $res = pre_none($pre_goods_res[0]['goods_id'],$value['order_sn'],$value['taobao_order_sn'],$value['change_amount']);
        }elseif($value['type'] == 'delete'){
            $res = pre_delete($pre_goods_res[0]['goods_id'],$value['order_sn']);
        }elseif($value['type'] == 'return'){
            $res = return_delete($pre_goods_res[0]['goods_id'],$value['order_sn']);
        }
        if($res){
            $smarty->assign('message', $value['order_sn'].'调整失败！');
            $smarty->display('oukooext/prepayment_ajustment.htm');
            exit();
        }
    }
    echo "<script>alert('调整成功！');</script>";
    $smarty->display('oukooext/prepayment_ajustment.htm');
    exit();
}
if($ajax == 'pre_error'){
    $goods_id = $_POST['goods_id'];
    $order_sn = $_POST['order_sn'];
    $taobao_order_sn = $_POST['taobao_order_sn'];
    $goods_price = $_POST['goods_price'];
    $res = pre_error($goods_id,$order_sn,$taobao_order_sn,$goods_price);
    die(json_encode($res));
}
if($ajax == 'pre_none'){
    $goods_id = $_POST['goods_id'];
    $order_sn = $_POST['order_sn'];
    $taobao_order_sn = $_POST['taobao_order_sn'];
    $goods_price = $_POST['goods_price'];
    $res = pre_none($goods_id,$order_sn,$taobao_order_sn,$goods_price);
    die(json_encode($res));
}

if($ajax == 'pre_delete'){
    $goods_id = $_POST['goods_id'];
    $order_sn = $_POST['order_sn'];
    $res = pre_delete($goods_id,$order_sn);
    die(json_encode($res));
}

if($ajax == 'return_delete'){
    $goods_id = $_POST['goods_id'];
    $order_sn = $_POST['order_sn'];
    $res = return_delete($goods_id,$order_sn);
    die(json_encode($res));
}

function search_order($party_id,$order_sn,$taobao_order_sn,$goods_id){
    $condition = 'oi.party_id = '.$party_id.' and ';
    if(!empty($order_sn)){
        $condition .= " oi.order_sn like '{$order_sn}' and";
    }
    if(!empty($taobao_order_sn)){
        $condition .=" oi.taobao_order_sn like '{$taobao_order_sn}' and";
    }
    $condition .=" g.goods_id = {$goods_id}";
    $sql="select oi.order_sn,oi.taobao_order_sn,g.goods_name,dsp.adjust_fee,eog.goods_number
    FROM ecshop.ecs_order_info oi
    inner join ecshop.ecs_order_goods eog on oi.order_id=eog.order_id
    inner join ecshop.distributor d on oi.distributor_id = d.distributor_id
    inner join ecshop.main_distributor md on d.main_distributor_id = md.main_distributor_id
    left join romeo.prepayment_account pa on d.main_distributor_id = pa.supplier_id and prepayment_account_type_id='DISTRIBUTOR'
    left join ecshop.distribution_group_goods dgg on convert(eog.goods_id using utf8) = dgg.code
    left join ecshop.distribution_group_goods_item gg on gg.group_id = dgg.group_id
    LEFT JOIN ecshop.ecs_goods g ON g.goods_id = eog.goods_id
    left join ecshop.distribution_sale_price dsp on (dsp.distributor_id = 0 or dsp.distributor_id = oi.distributor_id)
    and (dsp.goods_id = gg.goods_id or dsp.goods_id = eog.goods_id)
    where {$condition}
    order by dsp.valid_from desc limit 1
    ";
    //Qlog::log("主查询：".$sql);
    $re = $GLOBALS['db']->getAll($sql);
    return $re;
}

function pre_error($goods_id,$order_sn,$taobao_order_sn,$goods_price){
    $sql = "
    select o.order_id,doa.num,pt.prepayment_transaction_id,pt.prepayment_account_id,doa.amount
    from ecshop.ecs_order_info o
    left join ecshop.distribution_order_adjustment doa on o.order_id = doa.order_id
    left join romeo.prepayment_transaction pt on doa.prepayment_transaction_id = pt.prepayment_transaction_id
    where o.order_sn = '{$order_sn}' and doa.goods_id = ".$goods_id."
    limit 1";
    //Qlog::log("扣错查询：".$sql);
    $result = $GLOBALS['db']->getAll($sql);
    $prepayment_transaction_id = $result[0]['prepayment_transaction_id'];
    $prepayment_account_id = $result[0]['prepayment_account_id'];
    $goods_number = $result[0]['num'];
    $order_id = $result[0]['order_id'];
        $amount = $goods_price * $goods_number;
        $doa_amount = $result[0]['amount'];
        $sub_amount = $amount - $doa_amount;
        $res = 0;
            //更新ecshop.distribution_order_adjustment
            $sql_update1 = "
            update ecshop.distribution_order_adjustment set amount = ".$amount."
            where order_id = ".$order_id." and num = ".$goods_number." and goods_id = ".$goods_id."
        ";
        //Qlog::log("更新ecshop.distribution_order_adjustment".$sql_update1);
            $GLOBALS['db']->query($sql_update1);
            //更新romeo.prepayment_transaction
            $sql_update2 = "
            update romeo.prepayment_transaction set amount = amount - ".$sub_amount."
            where prepayment_transaction_id = '{$prepayment_transaction_id}'
                ";
                //Qlog::log("更新romeo.prepayment_transaction".$sql_update2);
    $GLOBALS['db']->query($sql_update2);
    //更新romeo.prepayment_account
    $sql_update3 = "
    update romeo.prepayment_account set amount = amount - ".$sub_amount."
    where prepayment_account_id = '{$prepayment_account_id}'
        ";
    //Qlog::log("更新romeo.prepayment_account".$sql_update3);
    $GLOBALS['db']->query($sql_update3);
    return $res;
}

function pre_none($goods_id,$order_sn,$taobao_order_sn,$goods_price){
    $sql_pre = "select prepayment_transaction_id from ecshop.distribution_order_adjustment_log where taobao_order_sn = '{$taobao_order_sn}'";
    $result_pre = $GLOBALS['db']->getAll($sql_pre);
    $res = 0;
    if(empty($result_pre)){
        $sql="select prepayment_transaction_id from ecshop.distribution_order_adjustment_log order by prepayment_transaction_id DESC limit 1";
        $result = $GLOBALS['db']->getAll($sql);
        $prepayment_transaction_id = $result[0]['prepayment_transaction_id'] + 1;
        $sql_log = "
        INSERT INTO ecshop.distribution_order_adjustment_log (taobao_order_sn,prepayment_transaction_id,status) values ('{$taobao_order_sn}',"
        .$prepayment_transaction_id.",'CONSUMED')
        ";
        //Qlog::log("sql_log:".$sql_log);
        $GLOBALS['db']->query($sql_log);
    }else{
        $prepayment_transaction_id = $result_pre[0]['prepayment_transaction_id'];
    }
        $sql_info = "
            select eoi.order_id,from_unixtime(eoi.shipping_time) as shipping_time,pa.PREPAYMENT_ACCOUNT_ID,eog.goods_number,ifnull(gg.style_id,eog.style_id) as style_id,
                    eg.goods_name,ifnull(dgg.group_id,0) as group_id,ifnull(dgg.name,'') as group_name
                    from ecshop.ecs_order_info eoi
                    inner join ecshop.distributor d on eoi.distributor_id = d.distributor_id
                    inner join ecshop.main_distributor md on d.main_distributor_id = md.main_distributor_id
                    inner join ecshop.ecs_order_goods eog on eoi.order_id=eog.order_id
                    left join ecshop.distribution_group_goods dgg on eog.goods_id = dgg.code
                    left join ecshop.distribution_group_goods_item AS gg on gg.group_id = dgg.group_id
                    inner join ecshop.ecs_goods eg on eg.goods_id = eog.goods_id
                    left join romeo.prepayment_account pa on d.main_distributor_id = pa.supplier_id and prepayment_account_type_id='DISTRIBUTOR'
                    where eoi.order_sn = '{$order_sn}' and eog.goods_id = ".$goods_id."
                    ";
         //QLog::log("pre_none:".$sql_info);
        $result_info = $GLOBALS['db']->getAll($sql_info);
        $order_id = $result_info[0]['order_id'];
        $shipping_time = $result_info[0]['shipping_time'];
        $prepayment_account_id = $result_info[0]['PREPAYMENT_ACCOUNT_ID'];
        $goods_number = $result_info[0]['goods_number'];
        $amount = $goods_number * $goods_price;
        $style_id = $result_info[0]['style_id'];
        $goods_name = $result_info[0]['goods_name'];
        $group_id = $result_info[0]['group_id'];
        $group_name = $result_info[0]['group_name'];
        //插入romeo.prepayment_transaction
        if(empty($result_pre)){
        $sql_pt = "
            insert into romeo.prepayment_transaction
            (prepayment_transaction_id,created_stamp,last_update_stamp,last_update_tx_stamp,created_tx_stamp,
            current_status_sequence_no,note,prepayment_account_id,amount,created_by_user_login,prepayment_transaction_type_id,
            transaction_timestamp) values (".$prepayment_transaction_id.",'{$shipping_time}','{$shipping_time}','{$shipping_time}','{$shipping_time}',
            0,'订单{$order_sn},淘宝订单号{$taobao_order_sn}','{$prepayment_account_id}',-".$amount.",'hyzhou1','3139283','{$shipping_time}')
        ";
        //QLog::log("插入romeo.prepayment_transaction:".$sql_pt);
        $GLOBALS['db']->query($sql_pt);
   }else{
        $sql_update = "
        update romeo.prepayment_transaction set amount = amount - ".$amount."
        where prepayment_transaction_id = '{$prepayment_transaction_id}'
        ";
        //Qlog::log("更新romeo.prepayment_transaction".$sql_update);
        $GLOBALS['db']->query($sql_update);
    }
    //插入ecshop.distribution_order_adjustment
    $sql_doa = "
             INSERT INTO ecshop.distribution_order_adjustment
        (order_id, goods_id, style_id, goods_name, group_id,group_name,num, amount, type, status, prepayment_transaction_id,created_by_user_login, created)
            values(".$order_id.",".$goods_id.",".$style_id.",'{$goods_name}',".$group_id.",'{$group_name}',
            ".$goods_number.",".$amount.",'GOODS_ADJUSTMENT','CONSUMED',".$prepayment_transaction_id.",'hyzhou1','{$shipping_time}')
            ";
            //QLog::log("插入ecshop.distribution_order_adjustment:".$sql_doa);
    $GLOBALS['db']->query($sql_doa);
            //更新romeo.prepayment_account，供应商预存款
            $sql_pa = "
                update romeo.prepayment_account set AMOUNT = AMOUNT - ".$amount." where prepayment_account_id = '{$prepayment_account_id}'
            ";
    //QLog::log("更新romeo.prepayment_account".$sql_pa);
    $GLOBALS['db']->query($sql_pa);
    return $res;
}

function pre_delete($goods_id,$order_sn){
    $sql = "select o.order_id,pt.prepayment_transaction_id,pt.prepayment_account_id,doa.amount
    from ecshop.ecs_order_info o
    inner join ecshop.distribution_order_adjustment doa on o.order_id = doa.order_id
    inner join romeo.prepayment_transaction pt on doa.prepayment_transaction_id = pt.prepayment_transaction_id
    where o.order_sn = '{$order_sn}' and doa.goods_id = ".$goods_id."
    limit 1";
    //Qlog::log($sql);
    $result_de = $GLOBALS['db']->getAll($sql);
    $prepayment_account_id = $result_de[0]['prepayment_account_id'];
    $prepayment_transaction_id = $result_de[0]['prepayment_transaction_id'];
    if(empty($result_de)){
        $res = 1;
    }else{
        $res = 0;
        $order_id = $result_de[0]['order_id'];
        $sql_delete = "delete from ecshop.distribution_order_adjustment where order_id = '{$order_id}' and goods_id = '{$goods_id}' limit 1 ";
        //Qlog::log($sql_delete);
        $GLOBALS['db']->query($sql_delete);
        $sql_update1 = "
        update romeo.prepayment_transaction set amount = amount + ".$result_de[0]['amount']."
        where prepayment_transaction_id = '{$prepayment_transaction_id}'
                ";
        //Qlog::log($sql_update1);
        $GLOBALS['db']->query($sql_update1);
        $sql_update2 = "
        update romeo.prepayment_account set amount = amount + ".$result_de[0]['amount']."
        where prepayment_account_id = '{$prepayment_account_id}'
                ";
        //Qlog::log($sql_update2);
        $GLOBALS['db']->query($sql_update2);
    }
   return $res;    
}

function return_delete($goods_id,$order_sn){
    $sql = "select o.order_id,pt.prepayment_transaction_id,pt.prepayment_account_id,doa.amount
    from ecshop.ecs_order_info o
    left join ecshop.distribution_order_adjustment doa on o.order_id = doa.order_id
    left join romeo.prepayment_transaction pt on doa.prepayment_transaction_id = pt.prepayment_transaction_id
    where o.order_sn = '{$order_sn}' and doa.goods_id = ".$goods_id." and doa.status = 'RETURNED'
    limit 1";
    //Qlog::log($sql);
    $result_de = $GLOBALS['db']->getAll($sql);
    $prepayment_account_id = $result_de[0]['prepayment_account_id'];
    $prepayment_transaction_id = $result_de[0]['prepayment_transaction_id'];
    if(empty($result_de)){
        $res = 1;
    }else{
        $res = 0;
        $order_id = $result_de[0]['order_id'];
        $sql_delete = "delete from ecshop.distribution_order_adjustment where order_id = '{$order_id}' and goods_id = '{$goods_id}' and status = 'RETURNED' limit 1 ";
        //Qlog::log($sql_delete);
        $GLOBALS['db']->query($sql_delete);
    }
    $sql_update1 = "
    update romeo.prepayment_transaction set amount = amount + ".$result_de[0]['amount']."
    where prepayment_transaction_id = '{$prepayment_transaction_id}'
       ";
    //Qlog::log($sql_update1);
    $GLOBALS['db']->query($sql_update1);
    $sql_update2 = "
       update romeo.prepayment_account set amount = amount + ".$result_de[0]['amount']."
       where prepayment_account_id = '{$prepayment_account_id}'
       ";
    //Qlog::log($sql_update2);
    $GLOBALS['db']->query($sql_update2);
    return $res;
}

function get_goods_id_by_name($goods_name){
    $sql = "select goods_id from ecshop.ecs_goods where goods_name = '{$goods_name}'";
    $res = $GLOBALS['db']->getAll($sql);
    return $res;
}
$smarty->display('oukooext/prepayment_ajustment.htm');
?>