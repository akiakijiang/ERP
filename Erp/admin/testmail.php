<?
/**
 * 测试邮件列表
 * 
 * @author ncchen 090506
 */
define('IN_ECS', true);

require('includes/init.php');

$smtp_path = 'testmail/';
$testmail_list = list_testmail($smtp_path);
$smarty->assign('testmail_list', $testmail_list);
$smarty->display('oukooext/testmail.htm');

function timecmp( $row1,$row2 )
{
   return strcmp($row2['filemtime'], $row1['filemtime']) ;
}

function list_testmail($path) {
	$handler = opendir(ROOT_PATH.'admin/'. $path);
	$testmail_list = array();
	while ( ($filename = readdir($handler)) !== false ) {
		if($filename != '.' && $filename != '..') {
			$testmail_list[$filename] = array('filemtime' => date('Y-m-d H:i:s', filemtime(ROOT_PATH. 'admin/'.$path.$filename)), 'filepath' => $path.$filename);
		}
	}
	uasort($testmail_list, 'timecmp');
	return $testmail_list;
}