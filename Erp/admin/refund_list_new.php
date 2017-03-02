<?php

/**
 * 退款申请列表
 * 
 * @author yxiang@oukoo.com
 * @copyright ouku.com
 */

define('IN_ECS', true);
require_once ('includes/init.php');
require_once (ROOT_PATH . 'RomeoApi/lib_refund_new.php');
require_once (ROOT_PATH . 'includes/helper/array.php');
require_once (ROOT_PATH . 'includes/debug/lib_log.php');

$startTime = microtime(true);

set_error_handler('refund_error_handler');
admin_priv('refund_list'); // 退款申请列表查看权限

$act     = isset($_REQUEST['act']) && in_array($_REQUEST['act'], array('list', 'search', 'today')) ? $_REQUEST['act'] : 'list';
$request = isset($_REQUEST['request']) && in_array($_REQUEST['request'], array('ajax', 'export', 'import')) ? $_REQUEST['request'] : null;
$view    = isset($_REQUEST['view']) && in_array($_REQUEST['view'], array('1', '2', '3')) ? $_REQUEST['view'] : 1; // 申请列表面对的对象, 面对不同的人有不同的显示


$refund_status_list = refund_status_list(); // 状态列表
$refund_type_list = refund_type_list();  //退款类型列表

/* 通过$act来构造查询条件 */
switch ($act)
{
    case 'search':
        // 搜索
        $conditions = $search = $_POST['search'];
        $status_orig = isset($search['status']) && array_key_exists($search['status'], $refund_status_list) ? $search['status'] : null;
        $status = isset($search['status']) && array_key_exists($search['status'], $refund_status_list) ? $search['status'] : null;
        if($status=='RFND_STTS_INIT2'){
            $status="RFND_STTS_INIT";
        }
        $conditions['status'] = $search['status'] = $status;
        $conditions['status_orig'] = $search['status_orig']=$status_orig;
        $search['act'] = $act;
        $conditions['facility_id']=$_SESSION['facility_id'];
        unset($conditions['view']); // 搜索时不限制部门
        break;

    case 'list':
        // 待审核列表
        $conditions['view'] = $search['view'] = $view;
        $search['act'] = $act;
        $conditions['facility_id']=$_SESSION['facility_id'];
        break;

    case 'today':
        // 今日的退款申请
        $conditions['end'] = $conditions['start'] = date('Y-m-d');
        $search = $conditions;
        $search['act'] = $act;
        $conditions['facility_id']=$_SESSION['facility_id'];
        $search['view'] = $view;
        break;
}

_refund_list_conditions($conditions);

/* 查询 */
switch ($request)
{
    case 'ajax':
        // ajax请求, 查询是否有更新
        $result = refund_get_all_by_conditions_new($conditions, true);
        print json_encode(array('timestamp' => strtotime($result)));
        exit;
        break;

    case 'export':
        // 导出的数据查询工作 写在 _refund_list_export 函数中
        break;

    case 'import':
        // 导入财务退款记账时间
        _refund_list_import('file');
		
    default:
        // 列表数据
        $list = refund_get_all_by_conditions_new($conditions);
}

//增加时间限制
$end = date("Y-m-d");
$start = date("Y-m-d", strtotime('-3 month'));
$smarty->assign('end', $end);
$smarty->assign('start', $start);
$smarty->assign('view', $view);

$endTime = microtime(true);
$timeOut = $endTime - $startTime;

// 导出 list
if ($request == 'export')
{

    _refund_list_export($conditions);
    exit;
}
// 显示 list
else
{   
    $smarty->assign('view', $view); 
	$smarty->assign('timeOut', $timeOut); // 载入时间
    $smarty->assign('list', $list); // 查询结果列表
    $smarty->assign('search', $search); // 持有查询条件
    $smarty->assign('refund_status_list', $refund_status_list); // 退款状态列表
//    $smarty->assign('refund_status_link', $refund_status_link); // 退款状态列表
    $smarty->assign('refund_type_list', $refund_type_list); //退款类型列表
    $smarty->assign('refund_payment_type_list', refund_payment_type_list_new()); // 退款方式列表
    $smarty->display('oukooext/refund_list_new.htm');
}


/**
 * 构造查询条件
 * 
 * @param array $conditions
 */
function _refund_list_conditions(&$conditions)
{
    global $db, $ecs;

    if (!isset($conditions['start']) || strtotime($conditions['start']) === false)
    {
        $conditions['start'] = null;
    }

    if (isset($conditions['end']) && strtotime($conditions['end']) !== false)
    {
        $conditions['end'] = date('Y-m-d', strtotime('+1 day', strtotime($conditions['end'])));
    }
    else
    {
        $conditions['end'] = null;
    }
	$oids = array();
    // 按订单号查询
    if (isset($conditions['order_id']) && !empty($conditions['order_id']))
    {
        $order_ids = $db->getCol("SELECT order_id FROM {$ecs->table('order_info')} WHERE order_sn like '{$conditions['order_id']}%'", true);

        if ($order_ids)
        {
            $oids = $order_ids;
            foreach ($order_ids as $order_id) {
            	// 如果有原始订单，先从原始订单中取
            	require_once ROOT_PATH .'admin/includes/lib_order_relation.php';
            	$orders = get_order_related_orders($order_id);
            	if ($orders)
            	{
            		$oids = array_merge($oids, Helper_Array::getCols($orders, 'order_id'));
            	}
            } 
        }
    }

    $taobao_order_sn = trim($conditions['taobao_order_sn']);
    if($taobao_order_sn){
    	$order_ids = $db->getCol("SELECT order_id FROM {$ecs->table('order_info')} WHERE taobao_order_sn like '{$taobao_order_sn}%' and party_id = {$_SESSION['party_id']}");
    	$oids = array_merge($order_ids,$oids);
    }
    if ($oids)
    {
        $conditions['order_id'] = $oids;
    }

    // 按订单客户查询
    if (isset($conditions['user_id']) && !empty($conditions['user_id']))
    {
        $user_id = $db->getOne("SELECT user_id FROM {$ecs->table('users')} WHERE user_name = '{$conditions['user_id']}' OR user_realname = '{$conditions['user_id']}'", true);
        if ($user_id)
            $conditions['user_id'] = $user_id;
        else
            $conditions['user_id'] = -1;
    }
    else
    {
        $conditions['user_id'] = null;
    }

    // 每个部门进去默认就展示他们现在需要审核的
    if (isset($conditions['view']) && is_numeric($conditions['view']))
    {
        $conditions['current_checker'] = $conditions['view'];
        unset($conditions['view']);
    }
}

/**
 * 导出
 * 
 * @param array $conditions 查询条件
 */
function _refund_list_export($conditions)
{
    global $slave_db;
    @set_time_limit(300);

    try
    {
        $handle = refund_get_soap_client();
        $args = _refund_helper_conditions($conditions);
        $args['orderId'] ='';
        unset($args['offset'], $args['limit']); // 去掉 offset 和 limit       
        $response = $handle->getRefundSummaryReport($args)->return->RefundSummaryReportElement;
        if (is_object($response)) 
            $list[0] = $response;
        elseif (is_array($response))
            $list = $response;
        if (empty($list)) 
        {
            trigger_error("没有记录", E_USER_WARNING);
            return;
        }
        $order_sn_group = array();
        foreach ($list as $item)
            $order_sn_group[] = $item->orderSn;
        // 查询退款单对应的原始订单号
        if (!empty($order_sn_group))
        {
            $sql = "SELECT order_sn, root_order_sn FROM order_relation WHERE order_sn " . db_create_in($order_sn_group);
            $slave_db->getAllRefby($sql, array('order_sn'), $ref_field, $orders, true);
        }
       //查询订单的顾客邮箱和淘宝订单号
        if(!empty($order_sn_group))
        {
        	foreach ($list as $key => $lists)
        	{
        		if (isset($orders['order_sn'][$lists->orderSn][0]['root_order_sn']))
        		{
        			$sql = "SELECT email, taobao_order_sn, pay_name FROM ecs_order_info where order_sn = '{$orders['order_sn'][$lists->orderSn][0]['root_order_sn']}'";
        			$row = $slave_db->getRow($sql);
        			$list[$key]->email = $row['email'];
        			$list[$key]->taobao_order_sn = $row['taobao_order_sn'];
        			$list[$key]->pay_name = $row['pay_name'];
        		}
        		else {
        			$sql = "SELECT email, taobao_order_sn, pay_name FROM ecs_order_info where order_sn = '{$list[$key]->orderSn}'";
        			$row = $slave_db->getRow($sql);
        			$list[$key]->email = $row['email'];
        			$list[$key]->taobao_order_sn = $row['taobao_order_sn'];
        			$list[$key]->pay_name = $row['pay_name'];
        		}
        		
        		//从order_attribute表中，筛选出买家的支付宝账号
        		if(isset($list[$key]->taobao_order_sn)) {
        			$sql = "select attr_value 
        					from order_attribute  oa
        					left join ecs_order_info oi on oi.order_id=oa.order_id
        					where oi.taobao_order_sn= '{$list[$key]->taobao_order_sn}' and oa.attr_name = 'BUYER_ALIPAY_NO' ";
        			$row = $slave_db->getRow($sql);
        			$list[$key]->buyer_alipay_no = $row['attr_value'];
        		}
        		
        		//从refund 表中，筛选出退款的账号、开户人
        		if(isset($list[$key]->taobao_order_sn)) {
        			$sql = "select r.ACCOUNT_USER_LOGIN, r.BANK_ACCOUNT_NO
	    			from romeo.refund r
					left join ecs_order_info o on o.order_id= r.order_id
	   				left join romeo.refund_payment_type rpt on rpt.refund_payment_type_id = r.refund_payment_type_id
        			where o.taobao_order_sn= '{$list[$key]->taobao_order_sn}' and rpt.code = 'BANK' ";
        			$row = $slave_db->getRow($sql);
        			if(!empty($row)) {
        				$list[$key]->buyer_alipay_no = $row['BANK_ACCOUNT_NO'];
        				$list[$key]->account_user = $row['ACCOUNT_USER_LOGIN'];
        			}
        			
        		}
        	}
        }
    }
    catch (SoapFault $e)
    {
        trigger_error("SOAP导出查询退款记录失败: (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})", E_USER_ERROR);
        $list = array();
    }
    // 载入excel库
    set_include_path(get_include_path() . PATH_SEPARATOR . './includes/Classes/');
    require 'PHPExcel.php';
    require 'PHPExcel/IOFactory.php';

    $filename = "退款申请.xlsx";
    $excel = new PHPExcel();

    // 设置属性
    $excel->getProperties()->setTitle($title);
    $excel->getProperties()->setSubject($title);

    $sheet = $excel->getActiveSheet();

    $r = 1;
    $sheet->setCellValue("A{$r}", '序号');
    $sheet->setCellValue("B{$r}", '订单号');
    $sheet->setCellValue("C{$r}",'原始订单号');
    $sheet->setCellValue("D{$r}", '淘宝订单号');
    $sheet->setCellValue("E{$r}", '退款方式');
    $sheet->setCellValue("F{$r}", '客服申请退款时间');
    $sheet->setCellValue("G{$r}", '仓库审核时间');
    $sheet->setCellValue("H{$r}", '财务完成退款时间');
    $sheet->setCellValue("I{$r}", '退款原因');
    $sheet->setCellValue("J{$r}", '备注');
    $sheet->setCellValue("K{$r}", '退款项目');
    $sheet->setCellValue("L{$r}", '商品名称');
    $sheet->setCellValue("M{$r}", '退款金额');
    $sheet->setCellValue("N{$r}", '退款金额其他');
    $sheet->setCellValue("O{$r}", '客户名');
    $sheet->setCellValue("P{$r}", '客户邮箱');
    $sheet->setCellValue("Q{$r}", '客服组申请人');
    $sheet->setCellValue("R{$r}", '是否入库');
    $sheet->setCellValue("S{$r}", '是否红冲');
    
    $sheet->setCellValue("T{$r}", '支付方式');
    $sheet->setCellValue("U{$r}", '退款确认人/财务审核人');
    $sheet->setCellValue("V{$r}", '退款支付宝账号');
    $sheet->setCellValue("W{$r}", '开户名');
    ;
    $r++;
    foreach ($list as $key => $item)
    {
        // 取得原始订单号
        $item->formatedOrderSn = $item->orderSn;   
        $sheet->setCellValue("A{$r}", $r - 1);
        $sheet->setCellValueExplicit("B{$r}", $item->formatedOrderSn, PHPExcel_Cell_DataType::TYPE_STRING);
        if(!empty($orders['order_sn'][$item->orderSn][0]['root_order_sn'])) {
        	$sheet->setCellValueExplicit("C{$r}", $orders['order_sn'][$item->orderSn][0]['root_order_sn'], PHPExcel_Cell_DataType::TYPE_STRING);
        }
        else {
        	$sheet->setCellValueExplicit("C{$r}", $item->formatedOrderSn, PHPExcel_Cell_DataType::TYPE_STRING);
        }
        $sheet->setCellValueExplicit("D{$r}", $item->taobao_order_sn, PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->setCellValue("E{$r}", $item->refundPaymentType);

        $sheet->setCellValue("F{$r}", !empty($item->createdTime) ? date('Y-m-d H:i:s', strtotime($item->createdTime)) : '');
        $sheet->setCellValue("G{$r}", !empty($item->storageCheckDate) ? date('Y-m-d H:i:s', strtotime($item->storageCheckDate)) : '');
        $sheet->setCellValue("H{$r}", !empty($item->financeExcuteDate) ? date('Y-m-d H:i:s', strtotime($item->financeExcuteDate)) : '');
        $sheet->setCellValue("I{$r}", $item->refundReason);
        $sheet->setCellValue("J{$r}", $item->note);
        $sheet->setCellValue("K{$r}", $item->refundDetailType);
        $sheet->setCellValue("L{$r}", $item->goodsName);
        if($item->refundDetailType != '其他费用调整')
        	$sheet->setCellValue("M{$r}", sprintf($item->refundAmount, '%01.2f'));
        else
        	$sheet->setCellValue("N{$r}", sprintf($item->refundAmount, '%01.2f'));
        $sheet->setCellValue("O{$r}", $item->customerName);
        $sheet->setCellValue("P{$r}", $item->email);        
        $sheet->setCellValue("Q{$r}", $item->createdByUserLogin);
        $sheet->setCellValue("R{$r}", '');
        $sheet->setCellValue("S{$r}", '');
        $sheet->setCellValue("T{$r}", $item->pay_name);
        if($item->refundPaymentType != '淘宝店铺')
            $sheet->setCellValue("U{$r}", $item->executeByUserLogin);
        else
            $sheet->setCellValue("U{$r}", $item->checkUserLogin3);
        
        $sheet->setCellValue("V{$r}", $item->buyer_alipay_no);
        $sheet->setCellValue("W{$r}", $item->account_user);
        
        $r++;
    }

    // 输出
    if (!headers_sent())
    {
        $output = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
        $output->setOffice2003Compatibility(true);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $output->save('php://output');
    }

    // 内存使用情况
    //echo date('H:i:s') . " Peak memory usage: " . (memory_get_peak_usage(true) / 1024 / 1024) . " MB\r\n";
}

/**
 * 导入
 * 要求导入的csv文件的格式为: '退款单号','订单sn号','退款时间'
 * 
 * @param string $field 文件上传域的控件名
 */
function _refund_list_import($field)
{
    global $smarty;
    
    // 文件上传
    if ($_FILES[$field]['error'] != UPLOAD_ERR_OK || !is_uploaded_file($_FILES[$field]['tmp_name']))
    {	
        trigger_error('文件上传失败，请确认选择了正确的上传文件', E_USER_ERROR);
        return;

    }
    // 解析csv文件
    require_once 'includes/lib_csv.php';
    $rows = csv_parse(file_get_contents($_FILES[$field]['tmp_name']));

    // 检查是否正确的解析为数组了
    if (empty($rows) || !is_array($rows))
    {
        $smarty->assign('message', '文件解析失败，或者您提交的文件内容不符合规格');
        return;
    }

    // 将order_sn转成order_id
    $oSns = array();
    foreach ($rows as $key => $row)
    {
        array_map('trim', $row);
        if (empty($row[1]))
            unset($rows[$key]);
        else
            $oSns[] = $row[1];
    }
    if (!empty($oSns))
    {
        $sql = "SELECT order_id, order_sn FROM {$GLOBALS['ecs']->table('order_info')} WHERE " . db_create_in($oSns, 'order_sn');
        $orders = $GLOBALS['db']->getAllRefby($sql, array('order_sn'), $refs_value, $refs, false);
    }
    foreach ($rows as $key => $row)
    {
        $rows[$key][1] = $refs['order_sn'][$row[1]][0]['order_id']; // 转成order_id
    }

    // 实行service
    try
    {
        $handle = refund_get_soap_client();
        $json = json_encode($rows);
        $result = $handle->updateConfirmDate(array('arg0' => $_SESSION['admin_name'], 'arg1' => $json));
    }
    catch (SoapFault $e)
    {
        trigger_error("SOAP导入财务退款记账时间失败: (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})", E_USER_ERROR);
        return;
    }

    if (empty($result->return->failedRefundId))
    {
        $smarty->assign('message', '导入成功');
    }
    else
    {
        $smarty->assign('message', "导入成功，但有部分退款单导入失败，请确认退款单号和订单号是否匹配。失败的退款单号:" . implode(',', $result->return->failedRefundId));
    }

    @unlink($_FILES[$field]['tmp_name']);
}
