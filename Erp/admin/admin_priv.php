<?php
define('IN_ECS', true);

require('includes/init.php');

if (!in_array($_SESSION['admin_name'], array('pjun', 'ychen', 'jjhe','lwang')) ) {
    die('no privilege');
}
if ( in_array($_REQUEST['add_type'], array('comment', 'ads',  'price_tracker') ) ) {

    $user_name = $_REQUEST['user_name'];
    $sql = "select userId from {$ecs->table('users')} where user_name  = '{$user_name}' ";
    $userId = $db->getOne($sql);

    if (!$userId) {
        die('no user!');
    }

    if ($_REQUEST['add_type'] == 'comment') {
        $real_name = $_REQUEST['real_name'];
        $nick = $_REQUEST['nick'];
        if ($_REQUEST['nick']) {
            $sql = "insert into reply_nick(user_id, name) values('{$userId}', '{$nick}')  ";
            $db->query($sql);
        }

        // 查询erp帐号是否存在
        $sql = "select user_id from ecshop.ecs_admin_user where user_name = '{$_REQUEST['erp_user_name']}' limit 1";
        $erp_user_id = $db->getOne($sql);
        
        $sql = "insert into CRAWLER.ADMINUSERS(USER_NAME, UNI_USER_ID, IS_ADSTRACKER, PARTY_ID, ERP_USER_ID) 
            values('{$real_name}', '{$userId}', '{$_REQUEST['ads']}', '{$_REQUEST['party_id']}', '{$erp_user_id}') ";
        $db2 = mysql_connect($t188_db_host, $t188_db_user, $t188_db_pass);
        mysql_query('set names utf8', $db2);
        mysql_query($sql, $db2);
        mysql_close($db2);
    }

    if ($_REQUEST['add_type'] == 'price_tracker') {
        $sql = "insert into PRICE_TRACKER.USER(USER_ID, TYPE) values('{$userId}', '{$_REQUEST['user_type']}') ";
        $db->query($sql);
    }

    $back = $_REQUEST['back'];
    header("location:".($back ? $back : $_SERVER['HTTP_REFERER']));

}
if ( in_array($_REQUEST['del_type'], array('comment', 'ads',  'price_tracker') ) ) {

    $uni_user_id = $_REQUEST['uni_user_id'];
    if ($_REQUEST['del_type'] == 'ads' || $_REQUEST['del_type'] == 'comment') {
        if ($_REQUEST['del_type'] == 'ads' ) {
            $sql = "update CRAWLER.ADMINUSERS set IS_ADSTRACKER = 1 - IS_ADSTRACKER where UNI_USER_ID = '{$uni_user_id}' limit 1 ";
        } else {
            $db->query("DELETE FROM reply_nick WHERE user_id = '{$uni_user_id}' LIMIT 1 ");
            $sql = "delete from CRAWLER.ADMINUSERS where UNI_USER_ID = '{$uni_user_id}' LIMIT 1 ";
        }

        $db2 = mysql_connect($t188_db_host, $t188_db_user, $t188_db_pass);
        mysql_query('set names utf8', $db2);
        mysql_query($sql, $db2);
        mysql_close($db2);
    }

    if ($_REQUEST['del_type'] == 'price_tracker') {
        $sql = "delete from PRICE_TRACKER.USER where USER_ID = '{$uni_user_id}' limit 1 ";
        $db->query($sql);
    }
    $back = $_REQUEST['back'];
    header("location:".($back ? $back : $_SERVER['HTTP_REFERER']));

}

if ( in_array($_REQUEST['update_type'], array('comment', 'price_tracker') ) ) {

    $uni_user_id = $_REQUEST['uni_user_id'];
    if ($_REQUEST['update_type'] == 'comment') {
        $sql = "update CRAWLER.ADMINUSERS set PARTY_ID = '{$_REQUEST['party_id']}' 
            where UNI_USER_ID = '{$uni_user_id}' limit 1 ";
        $db2 = mysql_connect($t188_db_host, $t188_db_user, $t188_db_pass);
        mysql_query('set names utf8', $db2);
        mysql_query($sql, $db2);
        
        // 查询erp帐号是否存在
        $sql = "select user_id from ecshop.ecs_admin_user where user_name = '{$_REQUEST['erp_user_name']}' limit 1";
        $erp_user_id = $db->getOne($sql);
        if (!empty($erp_user_id)) {
            $sql = "update CRAWLER.ADMINUSERS set ERP_USER_ID = '{$erp_user_id}' 
                where UNI_USER_ID = '{$uni_user_id}' limit 1";
            mysql_query($sql, $db2);
        }
        mysql_close($db2);
    }

    $back = $_REQUEST['back'];
    header("location:".($back ? $back : $_SERVER['HTTP_REFERER']));
}

$sql = "SELECT pu.TYPE, pu.USER_ID FROM PRICE_TRACKER.USER pu";
$price_trakers = $db->getAll($sql);
foreach ($price_trakers as $k=>$price_traker) {
    $sql = "SELECT u.user_name, r.name AS nick FROM {$ecs->table('users')} u
          LEFT JOIN reply_nick r ON u.userId = r.user_id WHERE u.userId = '{$price_traker['USER_ID']}' ";
    $user = $db->getRow($sql);
    $price_trakers[$k]['u_name'] = $user['user_name'];
    $price_trakers[$k]['nick'] = $user['nick'];
}

$sql = "SELECT * FROM CRAWLER.ADMINUSERS";
$causers = array();
$db2 = mysql_connect($t188_db_host, $t188_db_user, $t188_db_pass);
mysql_query('set names utf8', $db2);
$r = mysql_query($sql, $db2);
while ($causer = mysql_fetch_array($r)) {
    $sql = "SELECT u.user_name, r.name AS nick FROM {$ecs->table('users')} u
          LEFT JOIN reply_nick r ON u.userId = r.user_id WHERE u.userId = '{$causer['UNI_USER_ID']}' ";
    $user = $db->getRow($sql);
    $causer['u_name'] = $user['user_name'];
    $causer['nick'] = $user['nick'];
    $causer['party_name'] = party_mapping($causer['PARTY_ID']);
    // 获得erp帐号
    $sql = "select user_name from ecs_admin_user where user_id = '{$causer['ERP_USER_ID']}' ";
    $causer['erp_user_name'] = $db->getOne($sql);
    $causers[] = $causer;
}
mysql_close($db2);


$smarty->assign('causers', $causers);
$smarty->assign('price_trakers', $price_trakers);
$smarty->assign('party_names', party_mapping());

$smarty->assign('back', $_SERVER['REQUEST_URI']);
$smarty->display('oukooext/admin_priv.dwt');


