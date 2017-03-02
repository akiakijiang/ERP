<?php 
define('IN_ECS', true);
require_once('includes/init.php');
require("function.php");
require_once('distribution.inc.php');
require_once("includes/lib_common.php");
include_once(ROOT_PATH . "RomeoApi/lib_inventory.php");
require_once(ROOT_PATH. 'RomeoApi/lib_payment.php');
admin_priv('withhold');

$act = trim($_REQUEST['act']);
//搜索订单
if($act == 'search'){
    $order_sn = trim($_REQUEST['order_sn']);
    $party_id = $_SESSION['party_id'];
    if($order_sn == ''){
        print ("请输入订单号<a href=\"javascript:history.go(-1)\">返回</a>");
        exit();
    }
    if($party_id != '65609'){           //目前先针对享氏业务组开放权限
        print ("请选择正确的业务组织，目前先针对享氏业务组开放权限 <a href=\"javascript:history.go(-1)\">返回</a>");
        exit();
    }else{
        $sql = "select order_sn,order_id,order_time,confirm_time,consignee,tel,pay_name,goods_amount,order_status,pay_status,shipping_status,party_id
            from ecshop.ecs_order_info
            where order_sn = '{$order_sn}'
            ";
        $order_list = $GLOBALS['db']->getAll($sql);
        $order_list = $order_list[0];
        
        $order_sn = $_REQUEST['order_sn'];
        $main_distributor = getMainDistributor($order_sn);
        /*var_dump($main_distributor); */ 
        $distributor = $main_distributor['name'];
        $smarty->assign('distributor',$distributor);
        $smarty->assign('order_list',$order_list);
        $smarty->assign('adminvars', $_CFG['adminvars']);
    }
    if($order_list['party_id'] != '65609'){
        print ("请选择正确的业务组织，目前先针对享氏业务组开放权限 <a href=\"javascript:history.go(-1)\">返回</a>");
        exit();
    }
}
//抵扣预存款
if($act == "hold"){
    $order_sn = $_REQUEST['order_sn'];
    $main_distributor = getMainDistributor($order_sn);
    /* var_dump($main_distributor); */
    if($main_distributor['type'] != 'fenxiao'){
        print ("该订单不是分销订单，不能抵扣预存款！<a href=\"javascript:history.go(-1)\">返回</a>");
        exit();
    }else if($main_distributor['is_prepayment'] == 'N'){
    	print ("该分销商不用抵扣预存款！<a href=\"javascript:history.go(-1)\">返回</a>");
        exit();
    }
    $sql_c = "select *
        from ecshop.distribution_order_adjustment_log
        where taobao_order_sn = '{$order_sn}'
        ";
    $statu = $GLOBALS['db']->getAll($sql_c);
    if($statu){
        $message = "该订单号（".$order_sn."）已抵扣预付款！";
    }else{
        $result = distribution_edu_order_adjustment($order, $main_distributor['main_distributor_id']);
        if($result == null){
            $message = "抵扣预存款成功！";
        }else{
            $message = $result;
        }
    }
}
//通过order_sn获取主分销商ID、Name和Type
function getMainDistributor($order_sn){
    $order_sn = $_REQUEST['order_sn'];
    $sql ="select oi.order_id, oi.order_sn, oi.order_time, oi.order_status, oi.shipping_status, oi.pay_status, oi.order_type_id,
    oi.goods_amount, oi.bonus, oi.distributor_id, oi.party_id, oi.province, oi.shipping_id, oi.taobao_order_sn
    from romeo.order_shipment os
    inner join ecshop.ecs_order_info oi on cast(os.order_id as unsigned) = oi.order_id
    where oi.order_sn = '$order_sn'
    ";
    $result = $GLOBALS['db']->getAll($sql);
    $order = $result[0];
    $sql_main = "select d.is_prepayment, md.main_distributor_id, md.name, md.type from ecshop.distributor d
    left join ecshop.main_distributor md on d.main_distributor_id = md.main_distributor_id
    where d.distributor_id = '{$order['distributor_id']}' limit 1 ;" ;
    $res = $GLOBALS['db']->getRow($sql_main);
    return $res;
}
$smarty->assign('message',$message);
$smarty->display('oukooext/prepayment_withhold.htm');

?>