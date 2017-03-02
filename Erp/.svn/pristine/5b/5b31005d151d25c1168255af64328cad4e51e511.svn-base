<?php
/**
 * 供价管理
 */
define('IN_ECS', true);
require_once ('../includes/init.php');
require_once ('../../RomeoApi/lib_dispatchlist.php');

admin_priv('dispatch_list_customer_service');
party_priv('65542');

$criteria = new stdClass();
$criteria->offset = 0;
$criteria->count = 2000;
$criteria->partyId = $_SESSION['party_id'];

$act = $_GET['act'];
if ($act == 'search')
{
	$row = $_REQUEST['row'];
	foreach ($row as $key => $value)
	{
		if ($value)
		{
			$criteria->$key = $value;
		}
	}
}

//var_dump($criteria);
$candidates = searchDispatchCandidates($criteria);
$candidates = addFinishedCancelledCount($candidates);
if (!empty($candidates)) {
    foreach ($candidates as $v)
    {
	    $orderId = (int) $v->orderId;
	    $sql = "SELECT action_time FROM `ecs_order_action` where order_id = $orderId and action_note like '订单确认，%' ";
	    $action_time = $GLOBALS['db']->getOne($sql);
	    $v->action_time = $action_time;
    }
}
unset($v);
$smarty->assign('candidates', $candidates);
$smarty->assign('candidates_count', sizeof($candidates));
$smarty->display('dispatchlist/candidate.htm');
