<?
/**
 * 客服 未发货跟踪
 * 
 * @author ncchen
 * 
 */

define('IN_ECS', true);
require('includes/init.php');

admin_priv('manus_send_mail');
require_once("function.php");

$act = $_POST['action'];
if ($act == 'set_mail') {
	$result = update_mail_queue($_POST['mail_id'], $_POST['mail_status']);
	if ($result == -1) {
		print "<script>alert('设置失败');</script>";
	}
	print "<script>location.href='{$_SERVER['SCRIPT_URI']}?{$_SERVER['QUERY_STRING']}'</script>";
}
$size = 20;
$page = $_REQUEST['page'] ? $_REQUEST['page'] : 1;
$start = ($page - 1) * $size;

$status = $_REQUEST['status'] ? $_REQUEST['status'] : null;

$mail_queue = list_mail_queue($status, $start, $size);

$pager = Pager($mail_queue['mail_count'], $size, $page);
$smarty->assign('pager', $pager);

$smarty->assign('mail_list', $mail_queue['mail_list']);

$smarty->display('oukooext/mail_queue.htm');

