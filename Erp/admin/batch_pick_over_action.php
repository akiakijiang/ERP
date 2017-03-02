<?php 
/**
 * 批量完结批捡单
 * ljzhou 2013-10-15
 */
define('IN_ECS', true);

require_once('includes/init.php');
require_once('function.php');
require_once('includes/lib_sinri_DealPrint.php');

$batch_pick_sn=$_REQUEST['batch_pick_sn'];
$from_php = array_key_exists('from_php', $_REQUEST) ? $_REQUEST['from_php'] : 'search_batch_pick.php';
if(empty($batch_pick_sn)) {
	die('批捡单号为空');
}

$result  = EndBatchPickBill($batch_pick_sn);
//pp('EndBatchPickBill:');pp($result);

if($result['success']) {
	$message  = '批捡单号：'.$batch_pick_sn.' 完结成功！';
} else {
	$message  = '批捡单号：'.$batch_pick_sn.' 完结失败，错误：'.$result['error'];
}

header("Location: ".$from_php."?message=".urldecode($message)); 
exit;


function EndBatchPickBill($BPSN)
{
	QLog::log("EndBatchPickBill({$BPSN})");
	//检测批单对应的面单是否都已经打印
	$result = check_batch_pick_barcode($BPSN);
	if(!array_key_exists('success', $result))
	{
		die('EndBatchPickBill: check BPSN error'.$result['error']);
	}
	else
	{
		if($result['success'])
		{
			$result = one_key_batch_pick($BPSN);
		}
		else{
			$result['success'] = false;
			$result['error'] = '批拣单状态有误：'.$result['error'];
		}
	}
	return $result;
}


?>
