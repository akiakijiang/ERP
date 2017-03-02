<?php
/**
 * 导入屏蔽电话的通话记录
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

//获得之前同步的最大id
if ($_REQUEST['act'] == "maxid") {
    print "done\n".$db->getOne("select IFNULL(max(id), 0) from callcenter_mask_talk_history");
    die();
}
//组织数据
$data = $_REQUEST['data'];

$logs = explode("\n",$data);
$datastr = "";
$insert_array = array();
foreach ($logs as $log) {
    $temp_data = explode("\t", $log);
    if (count($temp_data) == 12) {
        $insert_array[] = "('". join("','", $temp_data) ."')";
    }
}

// 插入数据
if (count($insert_array)) {
    $datastr = join(",", $insert_array);
    $db->query("INSERT INTO callcenter_mask_talk_history values {$datastr}");
}
print "done";