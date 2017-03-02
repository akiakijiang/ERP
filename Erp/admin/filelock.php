<?php
/**
 * 文件锁管理
 *
 * @author ncchen 090505
 */
define('IN_ECS', true);

require('includes/init.php');
require('includes/lib_filelock.php');
admin_priv('manage_filelock');

$action = $_GET['action'];
if ($action == 'del') {
	$path = $_GET['path'];
	release_file_lock($path, true);
	print "<script>location.href='filelock.php'</script>";
	exit;
}
$file_lock_list = list_file_lock();

$smarty->assign('file_lock_list', $file_lock_list);
$smarty->display('oukooext/filelock.htm');