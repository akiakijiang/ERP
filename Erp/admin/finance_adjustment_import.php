<?php 
/**
 * 财务调账
 * 
 */
define('IN_ECS', true);
require_once('includes/init.php');
admin_priv('finance_adjustment');
require_once('function.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'includes/helper/uploader.php');
require_once(ROOT_PATH . 'includes/debug/lib_log.php');

$act =  // 动作
    isset($_REQUEST['act']) && 
    in_array($_REQUEST['act'], array('upload', 'insert')) 
    ? $_REQUEST['act'] 
    : null ;
/*
 * 处理post请求
 */
if ($act) {

    switch ($act) {
        /**
         * 上传文件， 检查上传的excel格式，并读取数据提取并添加收款 
         */
        case 'upload' :
        	// excel读取设置
			$tpl = array('调账申请'  =>
						array('order_sn'=>'订单号',
							  'adjust_amount'=>'期末余额',
							  'note'=>'备注',
						));

            /* 文件上传并读取 */
            @set_time_limit(300);
            $uploader = new Helper_Uploader();
            $max_size = $uploader->allowedUploadSize();  // 允许上传的最大值

            if (!$uploader->existsFile('excel')) {
                $smarty->assign('message', '没有选择上传文件，或者文件上传失败');
                break;
            }

            // 取得要上传的文件句柄
            $file = $uploader->file('excel');
           
            // 检查上传文件
            if (!$file->isValid('xls, xlsx', $max_size)) {
                $smarty->assign('message', '非法的文件! 请检查文件类型类型(xls, xlsx), 并且系统限制的上传大小为'. $max_size/1024/1024 .'MB');
                break;
            }
            
            // 读取excel
            $result = excel_read($file->filepath(), $tpl, $file->extname(), $failed);
            if (!empty($failed)) {
                $smarty->assign('message', reset($failed));
                break;
            }
            
            /* 检查数据  */
			$rowset = $result ['调账申请'];
            
            // 订单数据读取失败
            if (empty($rowset)) {
                $smarty->assign('message', 'excel文件中没有数据,请检查文件');
                break;
            }
			
            /* 添加收款  */
			$sql = "select order_id from ecshop.ecs_order_info where order_sn = '%d' LIMIT 1";
            $amount = 0;
            $null_order  = ""; //找不到order_id的order_sn
            $adjust_string = "";
            foreach ($rowset as $key => $row) {
            	if( empty($row['order_sn']) || empty($row['adjust_amount']) || $row['order_sn'] = '' || $row['adjust_amount'] = '' || $row['adjust_amount'] = 0){
            		unset($rowset[$key]);
            		continue;
            	}
				
				//ERP订单号不足10位的，前面补零
				while(strlen($rowset[$key]['order_sn']) < 10){
					$rowset[$key]['order_sn'] = '0'.$rowset[$key]['order_sn'];
				}
				
				$order_id = $db->getOne("select order_id from ecshop.ecs_order_info where order_sn = '".$rowset[$key]['order_sn']."' and order_type_id in ('SALE', 'SUPPLIER_SALE') LIMIT 1");
            	$rowset[$key]['adjust_amount'] = number_format(str_replace(',', '', $rowset[$key]['adjust_amount']), 4, ".", "");
            	if(!empty($order_id)){
            		$rowset[$key]['order_id'] = $order_id;
            		$adjust_string .= $order_id.','.$rowset[$key]['order_sn'].','.$rowset[$key]['adjust_amount'].','.$row['note'].';';
            	}else{
            		$null_order .= $rowset[$key]['order_sn'].",";
            		unset($rowset[$key]);
            	}
            	
            	$amount += $rowset[$key]['adjust_amount'];
            }
            
            if($null_order != ''){
            	$smarty->assign('message', '订单'.$null_order.'找不到order_id');
            }
            
            // 删除上传的文件
            $file->unlink();
       		$smarty->assign('len', count($rowset));
            $smarty->assign('amount', number_format($amount, 4));
            $smarty->assign('null_order', $null_order);
        	$smarty->assign('adjust_list', $rowset);
        	$smarty->assign('adjust_string',$adjust_string);
        break;
	/**
	 * 调账
	 *
	 **/
		 case 'insert' :
        	/*分割调整数据*/
        	$adjust_string = $_REQUEST['adjust_string']?$_REQUEST['adjust_string']:null;
        	$adjust_item = explode(';', substr($adjust_string, 0, strlen($adjust_string)-1));
        	$adjust_list = array();
        	$i = 0;
        	foreach ( $adjust_item as $key => $adjust ) {
       			$item = explode(',', $adjust);
       			$adjust_list[$i]['order_id'] = $item[0];
       			$adjust_list[$i]['order_sn'] = $item[1];
       			$adjust_list[$i]['adjust_amount'] = $item[2];
       			$adjust_list[$i]['note'] = $item[3];
       			$i += 1;
			}
			
			$adjust_time = $_REQUEST['adjust_time'] && $_REQUEST['adjust_time'] != '' ? $_REQUEST['adjust_time'] : date('Y-m-d',time());
        	$seccess = 0;
        	$adjust_amount = 0;
        	$exist_order = '';
        	$exist_amount = 0;
        	$seccess_amount = 0;
			/*将数据插入数据库*/
        	foreach ( $adjust_list as $key => $item ) {
        		if(empty($item['order_id'])){
        			break;
        		}
        		$select_sql = "select 1 from romeo.finance_variance_amount where order_id = {$item['order_id']} and note = '{$item['note']}' and amount = {$item['adjust_amount']}";
   				$adjust_amount += $item['adjust_amount'];
   				if($db->getAll($select_sql)){
       				$exist_order .= $item['order_sn'].',';
       				$exist_amount += $item['adjust_amount'];
       				continue;
   				}
       			$insert_sql="INSERT INTO romeo.finance_variance_amount
							(variance_id, order_id, amount, note, status, varianced_stamp, created_by_user_login,created_tx_stamp, created_stamp, last_updated_tx_stamp, last_updated_stamp)
						values
							(null,{$item['order_id']},{$item['adjust_amount']},'{$item['note']}','OK','{$adjust_time}','{$_SESSION['admin_name']}', now(), now(), now(), now())
						";
       			if($db->query($insert_sql)){
       				$seccess += 1;
       				$seccess_amount += $item['adjust_amount'];
       			}
			}
			if($exist_order != ''){
				$smarty->assign('message', "订单".$exist_order."已调整过金额, 金额为".$exist_amount); 
			}else{
				$smarty->assign('message', "导入完毕，成功调整{$seccess}个订单，调整金额为".$seccess_amount); 
			}
			
			$smarty->assign('len', count($adjust_list));
            $smarty->assign('amount', $adjust_amount);
            $smarty->assign('seccess_amount', $seccess_amount);
        	$smarty->assign('adjust_list', $adjust_list);
        break;
    }
}

/**
 * 显示
 */
$smarty->display('finance/finance_adjustment_import.htm');

