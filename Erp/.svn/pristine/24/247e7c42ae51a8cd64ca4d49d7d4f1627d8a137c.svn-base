<?
/**
 * 客服 未发货跟踪
 * 
 * @author ncchen
 * 
 */

define('IN_ECS', true);
require('includes/init.php');

admin_priv('manus_send_message');
require_once("function.php");
require_once(ROOT_PATH. "RpcApi/message/MessageQueue.php");
require_once(ROOT_PATH. "RpcApi/message/MessageSearchResult.php");

$act = $_POST['action'];
if ($act == 'set_config') {
	$config = $_POST['config'];
	$result = $message_client->setMessageConfig($application_key[1], "message", $config);
	if ($result == 1) {
		print "<script>alert('设置成功');</script>";
	} else {
		print "<script>alert('设置失败');</script>";
	}
	print "<script>location.href='{$_SERVER['SCRIPT_URI']}?{$_SERVER['QUERY_STRING']}'</script>";
} else if ($act == 'set_message') {
	$result = $message_client->updateMessageQueue($application_key[1], $_POST['message_id'], $_POST['message_status']);
	if ($result == -1) {
		print "<script>alert('设置失败');</script>";
	}
	print "<script>location.href='{$_SERVER['SCRIPT_URI']}?{$_SERVER['QUERY_STRING']}'</script>";
}
$size = 20;
$page = $_REQUEST['page'] ? $_REQUEST['page'] : 1;
$start = ($page - 1) * $size;

$status = $_REQUEST['status'] ? $_REQUEST['status'] : null;

$messageSearchResult = $message_client->listMessageQueue($application_key[1], $status, $start, $size);

$message_count = 0;
$message_list = array();
if ($messageSearchResult != null) {
	$message_count = $messageSearchResult->getTotalCount();
	$message_list = $messageSearchResult->getResult();
}
$pager = Pager($message_count, $size, $page);
$smarty->assign('pager', $pager);


$smarty->assign('message_list', $message_list);

$config = $message_client->getMessageConfig($application_key[1], "message");
$smarty->assign('config', $config);

$smarty->display('oukooext/message_queue.htm');

