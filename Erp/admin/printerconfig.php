<?php

/**
 *  条码打印机配置
 */
define('IN_ECS', true);
require_once('includes/init.php');
if(!in_array($_SESSION['admin_name'],array('yxiang','hqqi','jjhe','ychen','shyuan','zwsun','lchen')))
{
	die('没有权限');
}

$printer_configs = @unserialize($_CFG['printer_config']);
if (!$printer_configs) $printer_configs = array();

//var_dump(unserialize($_CFG['printer_config']));

$action = $_REQUEST['act'];
if ($action == 'add') {
    $party_id = $_REQUEST['party_id'];
    $printer_id = str_replace(array('\\"', "\\'"), array('',''), $_REQUEST['printer_id']);
    $printer_name = str_replace(array('\\"', "\\'"), array('',''), $_REQUEST['printer_name']);
    
    $printer_configs[$party_id][$printer_id] = $printer_name;
    
    $str = serialize($printer_configs);
    
    $sql = "update ecs_shop_config set value = '{$str}' where code = 'printer_config' limit 1  ";
    $db->query($sql);
    /* 清除缓存 */
    clear_all_files();
} else if ($action == 'delete') {
    $party_id = $_REQUEST['party_id'];
    $printer_id = str_replace(array('\\"', "\\'"), array('',''), $_REQUEST['printer_id']);
    
    unset($printer_configs[$party_id][$printer_id]);
    
    $str = serialize($printer_configs);
    
    $sql = "update ecs_shop_config set value = '{$str}' where code = 'printer_config' limit 1  ";
    $db->query($sql);
    /* 清除缓存 */
    clear_all_files();
} 


$party_options_list = party_options_list($user_party_list);


// var_dump($printer_configs);
//var_dump($_CFG['printer_config']);
$smarty->assign('printer_configs', $printer_configs);
$smarty->assign('party_options_list', $party_options_list);
$smarty->display('printerconfig.htm');