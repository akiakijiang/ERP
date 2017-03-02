<?php
/*
 * Created on 2013-10-15
 * 直销运单推送到淘宝
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
define('IN_ECS', true);
require('includes/init.php');
require('function.php');
require('SyncWaybilCommand.php');
include_once('../RomeoApi/lib_currency.php');
include_once(ROOT_PATH . 'includes/cls_json.php');
require_once (ROOT_PATH . 'RomeoApi/lib_payment.php');
require_once (ROOT_PATH . 'includes/lib_order.php');
require_once (ROOT_PATH . 'includes/helper/array.php');
require_once (ROOT_PATH . 'includes/helper/uploader.php');
require_once (ROOT_PATH . 'includes/debug/lib_log.php');

admin_priv('waybill_push_to_taobao');

$act = $_REQUEST['act'];
$taobao_shop_id = $_REQUEST['taobao_shop'];
$flag = true;
$shops = get_taobao_shop();
$errors = array();
static $codeMap=array
(
	'E邮宝' => 'EMS',
	'EMS' => 'EMS',
	'顺丰' => 'SF',
	'万象' => 'OTHER',
	'龙邦' => 'LBEX',
	'圆通' => 'YTO',
	'申通' => 'STO',
	'汇通' => 'HTKY',
	'中通' => 'ZTO',
	'宅急送' => 'ZJS',
	'韵达' => 'YUNDA',
	'顺丰（陆运）'=>'SF',
	'EMS经济'=>'EYB',
	'邮政国内小包' => 'POSTB',
	'天天' =>'TTKDEX',
	'全一' =>'UAPEX',
);
if (!empty($act) && $act == 'action' && !empty($taobao_shop_id)) {
	require_once (ROOT_PATH . 'includes/helper/array.php');
	require_once (ROOT_PATH . 'includes/helper/uploader.php');
 	$final = '';
 	do{
 		/* 文件上传并读取 */
		@set_time_limit ( 300 );
		$uploader = new Helper_Uploader ();
		$max_size = $uploader->allowedUploadSize (); // 允许上传的最大值
		if (!$uploader->existsFile ( 'excel' )) {
			$final .=  '没有选择上传文件，或者文件上传失败';
			$flag = false;
			break;
		}
		// 取得要上传的文件句柄
		$file = $uploader->file ( 'excel' );
		
		// 检查上传文件
		if (! $file->isValid ( 'xls, xlsx', $max_size )) {
			$final .= "   " . '非法的文件! 请检查文件类型类型(xls, xlsx), 并且系统限制的上传大小为' . $max_size / 1024 / 1024 . 'MB';
			$flag = false;
			break;
		}
		$tpl = 
		array ('同步运单号' => 
		         array ('order_sn' => '订单编号',
		                'bill_sn' => '运单编号',
		                'shiping_name' => '快递'
		       ) );
		// 读取excel
		$record = excel_read ($file->filepath (), $tpl, $file->extname (), $failed );
		//判断是否符合条件
		//pp(sizeof($record['同步运单号']));exit();
		if (sizeof($record['同步运单号']) == 0) {
			$final .= " 导入的数据为空";
			$flag = false;
			break;
		}
		$i = 1;
		$j = 0;
		foreach ( $record['同步运单号'] as $key => $rec ) {
			$companycode = $codeMap[$rec['shiping_name']];
			if(!in_array($rec['shiping_name'],array_keys($codeMap))){
				$i++;
			    $errors[$j] .='EXCEL第'.$i.' 行'.', 订单号：'.$rec['order_sn'].'中输入的快递不符合规则！！';
			    $j++;
			}else{
				$result = SyncWaybillaction($rec,$taobao_shop_id,$companycode);
				$i++;
				if(isset($result->shipping) && isset($result->shipping->isSuccess) && $result->shipping->isSuccess){
					Qlog::log('----SyncSuccess-----'.'---order_sn='.$rec['order_sn'].'--bill_sn='.$rec['bill_sn']);
				}else{
					$msg = "";
					if (isset($result->subCode)) {
						$msg .= " ".$result->subCode;
						
						if ($result->subCode == 'isv.logistics-offline-service-error:B04') {
							$msg .= " ". "之前已经发货，不能重复发";
						} else if ($result->subCode == 'isv.logistics-offline-service-error:B01') {
							$msg .= " " . "订单不存在";
						} else if ($result->subCode == 'isv.logistics-offline-service-error:B60') {
							$msg .= " " . "运单号规则不符合";
						}
					}
					if (isset($result->msg)) {
						$msg .= " ".$result->msg;
					}
					$errors[$j] = 'EXCEL 第'.$i.' 行'.', 订单号：'.$rec['order_sn'].$msg.'。';
					Qlog::log('----SyncFail-----'.'---order_sn='.$rec['order_sn'].'--bill_sn='.$rec['bill_sn']);
					$flag = false;
					$j++;
				}
			}
        }
 	}while(false);
 	//pp($errors);exit();
}
//查找所有淘宝店铺
function get_taobao_shop(){
	global $db;
	    $sql ="SELECT taobao_shop_conf_id,nick from taobao_shop_conf"; 
    $shops =$db->getAll($sql);
    $list = array();
    foreach ($shops as $shop) {
    	$list[$shop['taobao_shop_conf_id']] = $shop['nick'];
    }
    return $list;
}

$smarty->assign('final',$final);
$smarty->assign('errors',$errors);
$smarty->assign('shops',$shops);
$smarty->display('oukooext/waybill_push_to_taobao.htm');

?>
