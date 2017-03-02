<?php 
/**
 * 文件锁
 * 
 * 名称规则：url_name.lock_name
 * 1.以所在url的主文件名为域名，系统将自动截取获得
 * 2.锁名自定义，尽量取有含义的词
 * 例：在admin/analyze_user.php下搜索，取名为analyze_user.search，当可以允许用户独立搜索时，可以取名为analyze_user.search.username
 * 调用方法：
	$file_name = 'search';
 	if (is_file_lock($file_name)) {
 		//todo
		echo '统计正在执行，30秒后自动刷新，请不要高频率刷新本页面';
		flush();
		sleep(30);
		print "<script>location.href='{$_SERVER['SCRIPT_URI']}?{$_SERVER['QUERY_STRING']}'</script>";
		exit;
	}
	create_file_lock($file_name);
	//do sth;
	release_file_lock($file_name);
	
	或者
	echo '统计正在执行，30秒后自动刷新，请不要高频率刷新本页面';
	$file_name = 'search';
	if (!wait_file_lock($file_name, 30)) {
	   die('操作超时，请重试');
	}
	create_file_lock($file_name);
	// do sth;
	release_file_lock($file_name);
 * 
 * ============================================================================
 * @author:		ncchen
 * @version:    v1.0
 * ---------------------------------------------
 * $Author:ncchen$
 * $Date: 2009-05-05$
 * $Id: lib_filelock.php$
 */

/**
 * 获得文件锁路径
 *
 * @param string $file_name
 * @return string
 */
function get_file_lock_path($file_name = '', $namespace = null) {
	if (!defined('ROOT_PATH')) {
		define('ROOT_PATH', preg_replace('/admin(.*)/', '', str_replace('\\', '/', __FILE__)));
	}
    if ($namespace == null) {
    	preg_match('/\/([^\/]*)\.php/', $_SERVER['SCRIPT_URL'], $matches);
        $namespace = $matches[1];
    }
	return ROOT_PATH. "admin/filelock/{$namespace}.{$file_name}";
}

/**
 * 获得是否有文件锁
 *
 * @param string $file_name
 * @return bool
 */
function is_file_lock($file_name) {
	$file_path = get_file_lock_path($file_name);
	return @file_exists($file_path);
}

/**
 * 生成文件锁
 *
 * @param string $file_name
 */
function create_file_lock($file_name) {
	$file_path = get_file_lock_path($file_name);
	@file_put_contents($file_path, "admin_id:{$_SESSION['admin_id']}\r\nadmin_name:{$_SESSION['admin_name']}");
}
/**
 * 删除文件锁
 *
 * @param string $file_name
 */
function release_file_lock($file_name, $flag = false) {
	if ($flag === false) {
		$path = get_file_lock_path($file_name);
	} else {
		$file_path = get_file_lock_path();
		$file_path = substr($file_path, 0, strrpos($file_path, '/')+1);
		$path = $file_path. $file_name;
	}
	@unlink($path);
}

/**
 * 等待文件锁
 * 
 * @param string $file_name
 * @param int $time_out
 */
function wait_file_lock($file_name, $time_out) {
	$time_out = intval($time_out);
	for ($i=0; $i<$time_out; $i++) { 
		if (!is_file_lock($file_name)) {
			return true;
		}
		sleep(1);
	}
	return false;
}

/**
 * 获得所有文件锁
 *
 * @return array $file_lock_list
 */
function list_file_lock() {
	$path = get_file_lock_path();
	$path = substr($path, 0, strrpos($path, '/')+1);
	$handler = opendir($path);
	$file_lock_list = array();
	while ( ($filename = readdir($handler)) !== false ) {
		if($filename != '.' && $filename != '..') {
			$file_lock_list[$filename] = file_get_contents($path.$filename);
		}
	}
	return $file_lock_list;
}
