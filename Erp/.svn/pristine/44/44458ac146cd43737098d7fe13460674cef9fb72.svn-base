<?php
define('IN_ECS', true);
require('../includes/init.php');
require(ROOT_PATH . 'admin/function.php');
require_once(ROOT_PATH . 'RomeoApi/lib_soap.php');
admin_priv('taobao_session');

$taobao_soapclient = soap_get_client('TaobaoSessionService');
$action = $_POST['action'];
if ($action == 'update') {
    $taobao_session_id = trim($_POST['taobao_session_id']);
    if ($taobao_session_id != '') {
        $taobao_session = array();
        $taobao_session['appkey'] = '12012553';
        $taobao_session['session_id'] = $taobao_session_id;
        $taobao_session_object = convert_taobao_session_object($taobao_session);
        $taobao_soapclient->setTaobaoSession(array('taobaoSession' => $taobao_session_object));
    }
}
$taobao_session = $taobao_soapclient->getNowTaobaoSession()->return;
$taobao_session_id = $taobao_session->topSession;
$smarty->assign('taobao_session_id', $taobao_session_id);
$smarty->display('taobao/taobao_session.htm');

function convert_taobao_session_object($taobao_session) {
    $taobao_session_object = new stdClass();
    $taobao_session_object->topAppkey = $taobao_session['appkey'];
    $taobao_session_object->topSession = $taobao_session['session_id'];
    $taobao_session_object->created = date('Y-m-d H:i:s');
    $taobao_session_object->user = 'erp';
    return $taobao_session_object;
}
?>