<?php
/**
 * 1. 修改屏蔽号码状态
 * 2. 导出屏蔽号码数据到callcenter中
 *
 * @author ncchen
 */
define('IN_ECS', true);

require('../../includes/master_init.php');
$p = $_REQUEST['p'];

if ($p != 'OUKOO_IMPORTCALLCENTER_PASS') {
  header("Status: 404 Not Found",false,404);
	die();
}
if ($_REQUEST['act'] == 'edit_status') {
    //修改屏蔽号码状态
    $no_status = $_REQUEST['no_status'];
    if ($no_status != 'A' && $no_status != 'S') {
        die("error status");
    }
    //组织数据
    $data = $_REQUEST['data'];
    $mask_phones = explode("\n",$data);
    if (count($mask_phones) > 0) {
        $sql_mask_phone = "('". join("','", $mask_phones) ."')";
        // 修改
        $sql_time = "";
        if ($no_status == 'A') {
            $sql_time = ", actived_time = NOW()";
        }
        if ($no_status == 'S') {
            $sql_time = ", stopped_time = NOW()";
        }
        $sql = "UPDATE callcenter_mask_phone SET no_status = '{$no_status}' {$sql_time}
            WHERE mask_phone_no IN {$sql_mask_phone} ";
        if (!$db->query($sql)) {
            die("error update");
        }
    }
    // 输出成功
    die("done");
} else if ($_REQUEST['act'] == 'export') {
    echo "done\n";
    // 导出屏蔽号码数据到callcenter中
    $sql = "SELECT cus_phone_no, mask_phone_no, cus_phone_no_all, order_sn, 'erp'
             FROM callcenter_mask_phone WHERE no_status = 'P' ";
    $mask_phones = $db->getAll($sql);
    $mask_phone_list = array();
    foreach ($mask_phones as $key => $mask_phone) {
        $mask_phone_list[] = join("\t", $mask_phone);
    }
    //输出屏蔽号码
    $mask_phone_str = join("\n", $mask_phone_list);
    echo $mask_phone_str;
} else if ($_REQUEST['act'] == 'active_time') {
    echo "done\n";
    $sql = "SELECT DATE_ADD(NOW(), INTERVAL -value DAY) AS active_time
         FROM ecs_shop_config WHERE code = 'callcenter_active_time' LIMIT 1 ";
    echo $db->getOne($sql);
}