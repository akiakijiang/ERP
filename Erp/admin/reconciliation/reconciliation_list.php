<?php
/**
 * 对账单列表
 */
define('IN_ECS', true);
require_once('../includes/init.php');
admin_priv('cg_reconciliation_list');
require_once(ROOT_PATH . "admin/function.php");

$sql = "
    select * from ecs_purchase_bill pb, ecs_provider p
    where pb.provider_id = p.provider_id 
    order by pb.date desc
";
$purchase_bill_list = $db->getAll($sql);

$smarty->assign('purchase_bill_list', $purchase_bill_list);
$smarty->display("reconciliation/reconciliation_list.htm");