<?php
/**
 * 呼叫中心统计
 */
define('IN_ECS', true);
require('../includes/init.php');
require_once('../function.php');
party_priv(PARTY_OUKU);
admin_priv('call_center/analyze_phone');

// 查询限制时间，默认为今天
$start = isset($_REQUEST['start']) && strtotime($_REQUEST['start']) ? $_REQUEST['start'] : date("Y-m-d");
$end   = isset($_REQUEST['end']) && strtotime($_REQUEST['end']) ? $_REQUEST['end'] : date('Y-m-d', strtotime('+1 day'));

// 时间戳
$start_time = strtotime($start);
$end_time   = strtotime($end);
// 天数 （用于计算平均数）
$days = round(($end_time - $start_time)/3600/24);

// 工作时间和非工作时间
$worktime_array = array('09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00', '21:00');
$outworktime_array = array('22:00', '23:00', '00:00', '01:00', '02:00', '03:00', '04:00', '05:00', '06:00', '07:00', '08:00');


$condition = "(`CallInTime` BETWEEN '{$start_time}' AND '{$end_time}') AND `CallerID` != '96081081'
    AND ( WorkerID <> '' AND AgentGroupID <> 0 ) ";


/**
 * 座席利用率 = （呼入呼出通话时间 + 电话等待时间）/（11小时*3600*工作天数）；
 */
$worker_usage_percents = array();
$sql = "
	SELECT `WorkerID`, SUM(iRecTimeLong) AS `count` FROM `call_log` 
	WHERE {$condition} AND (`CallEndTime` <> '' AND `CallInTime` <> '') GROUP BY `WorkerID`
";
$result = $slave_db->query($sql);
if ($result !== false)
{
	while ($row = $slave_db->fetchRow($result))
	{
		$worker_usage_percents[$row['WorkerID']] = sprintf('%0.2f', $temp['count'] / ($days * 11 * 3600)).'%';		
	}
	$slave_db->free_result($result);
}
$smarty->assign('workers', array_keys($worker_usage_percents));
$smarty->assign('worker_usage_percents', $worker_usage_percents);


/**
 * 每天处理电话数（呼入、呼出）
 */
$worker_call_in_counts = array();   // 按工号分组的呼入数
$worker_call_out_counts = array();  // 按工号分组的呼出数
$worker_call_in_counts_total = 0;   // 总呼入数
$worker_call_out_counts_total = 0;  // 总呼出数
$result = $slave_db->query("SELECT WorkerID, iINorOUT, COUNT(*) AS count FROM call_log WHERE {$condition} GROUP BY WorkerID, iINorOUT");
if ($result !== false)
{
	while ($row = $slave_db->fetchRow($result))
	{
		if ($row['iINorOUT'] == 0)      // 呼入
		{
			$worker_call_in_counts[$row['WorkerID']] = $row['count'];
			$worker_call_in_counts_total += $row['count'];	
		}
		elseif ($row['iINorOUT'] == 1)  // 呼出
		{
			$worker_call_out_counts[$row['WorkerID']] = $row['count'];
			$worker_call_out_counts_total += $row['count'];			
		}	
	}
	$slave_db->free_result($result);
}
$smarty->assign('worker_call_in_counts',  $worker_call_in_counts);   // 按工号分组的呼入数
$smarty->assign('worker_call_out_counts', $worker_call_out_counts);  // 按工号分组的呼出数

$smarty->assign('worker_call_in_counts_total',  $worker_call_in_counts_total);   // 总呼入数
$smarty->assign('worker_call_out_counts_total', $worker_call_out_counts_total);  // 总呼出数


/**
 * 按时间段,售后类型 来统计
 */
$result = $slave_db->query("SELECT WorkerID, iINorOUT, CallInTime, AgentGroupID FROM call_log WHERE {$condition}");

$call_out = array();
$call_out_total = array();

$call_in = array();
$call_in_total  = array();

if ($result !== false)
{
	while ($row = $slave_db->fetchRow($result))
	{
		$hour = date("H", $row['CallInTime']);
		$AgentGroupID = $row['AgentGroupID'];
		
		if ($row['iINorOUT'] == 1)  // 呼出
		{
			$call_out[$hour][$AgentGroupID]++;
			$call_out_total[$AgentGroupID]++;
		}
		elseif ($riw['iINorOUT'] == 0) // 呼出
		{
			$call_in[$hour][$AgentGroupID]++;
			$call_in_total[$AgentGroupID]++;
		}		
	}
}
$smarty->assign('call_in', $call_in);       // 按时间段和状态（售中，售后，售前）分组的呼入数
$smarty->assign('call_out', $call_out);     // 按时间段和状态（售中，售后，售前）分组的呼出数

$smarty->assign('call_in_total', $call_in_total);     // 按（售中，售后，售前）分组的呼出总数
$smarty->assign('call_out_total', $call_out_total);   // 按（售中，售后，售前）分组的呼出总数




//--------总体数据统计
//2. 平均放弃等待时间 = 放弃呼叫前在线等待时间/放弃电话数量
$sql = "SELECT AVG(IF(CallAgentTime>0, CallAgentTime, StartACDTime)  - CallInTime) FROM call_log WHERE {$condition} AND (StartACDTime <> '0') AND (AgentRcvTime = '0') AND (iINorOUT = 0)";
$giveup_avgtime = $slave_db->getOne($sql);
$smarty->assign('giveup_avgtime', intval($giveup_avgtime));


//3. 平均应答时间 = 被接通用户在线等待时间/接起电话总数
$sql = "SELECT AVG( AgentRcvTime - CallInTime  ) FROM call_log WHERE {$condition} AND (AgentRcvTime <> '0') AND (iINorOUT = 0)";
$answer_wait_avgtime = $slave_db->getOne($sql);
$smarty->assign('answer_wait_avgtime', intval($answer_wait_avgtime));


//4. 平均延迟时间 = 平均用户在线等待时间
$sql = "SELECT AVG(IF(CallAgentTime > 0, CallAgentTime, IF(StartACDTime > 0 ,StartACDTime, CallInTime)) - CallInTime) FROM call_log WHERE {$condition} AND (iINorOUT = 0)";
$delay_avgtime = $slave_db->getOne($sql);
$smarty->assign('delay_avgtime', intval($delay_avgtime));


//呼入转化率
//统计用固定电话呼入的
//$sql = "  ";
//$buy_after_callin_tel = $slave_db->getCol($sql);
//统计用手机呼入的
// ncchen 090316 以上两个合在一起查询
$sql = " 
    SELECT DISTINCT info.order_id, info.order_sn, info.consignee, info.tel, info.mobile 
    FROM {$ecs->table('order_info')} info 
    INNER JOIN call_log cl ON  replace(info.tel,'-','') = cl.CallerID 
    WHERE 
        info.order_status != 2 AND info.tel != '' AND info.order_type_id = 'SALE'
        AND info.special_type_id <> 'PRESELL' AND info.party_id = ". PARTY_OUKU_MOBILE ." 
        AND info.order_time >= '{$start}' AND  info.order_time < '{$end}' 
        AND cl.CallInTime >= '{$start_time}' AND cl.CallInTime < '{$end_time}'
        AND info.order_time > FROM_UNIXTIME(cl.CallInTime)
UNION
    SELECT DISTINCT info.order_id, info.order_sn, info.consignee, info.tel, info.mobile 
    FROM {$ecs->table('order_info')} info 
    INNER JOIN call_log cl ON  SUBSTRING(info.tel, 1, 3) = '021' 
        AND SUBSTRING(info.tel, 5) = cl.CallerID
    WHERE 
        info.order_status != 2 AND info.tel != '' AND info.order_type_id = 'SALE'
        AND info.special_type_id <> 'PRESELL' AND info.party_id = ". PARTY_OUKU_MOBILE ." 
        AND info.order_time >= '{$start}' AND  info.order_time < '{$end}' 
        AND cl.CallInTime >= '{$start_time}' AND cl.CallInTime < '{$end_time}'  
		AND info.order_time > FROM_UNIXTIME(cl.CallInTime)
UNION 
    SELECT DISTINCT info.order_id, info.order_sn, info.consignee, info.tel, info.mobile 
    FROM {$ecs->table('order_info')} info 
    INNER JOIN call_log cl ON  info.mobile = cl.CallerID 
    WHERE 
        info.order_status != 2 AND info.mobile != '' AND info.order_type_id = 'SALE'
        AND info.special_type_id <> 'PRESELL' AND info.party_id = ". PARTY_OUKU_MOBILE ." 
        AND info.order_time >= '{$start}' AND  info.order_time < '{$end}'
        AND cl.CallInTime >= '{$start_time}' AND cl.CallInTime < '{$end_time}'
		AND info.order_time > FROM_UNIXTIME(cl.CallInTime) 
";

$buy_after_callin_list = $slave_db->getAll($sql);
$buy_after_callin_count = count($buy_after_callin_list);
$smarty->assign('buy_after_callin_list', $buy_after_callin_list);
$smarty->assign('buy_after_callin_count', $buy_after_callin_count);
//统计在后面
//$buy_after_callin_percent = $worker_call_in_counts_total > 0 ?sprintf("%.2f", $buy_after_callin_count / $worker_call_in_counts_total * 100)." %" : '无呼入记录';
//$smarty->assign('buy_after_callin_percent', $buy_after_callin_percent);

//呼出转化率
$sql = "
	SELECT COUNT(DISTINCT info.order_id) 
	FROM {$ecs->table('order_info')} info 
	INNER JOIN call_log cl ON  info.tel = cl.PhoneNO 
	WHERE 
		info.order_time >= '{$start}' AND  info.order_time < '{$end}' 
		AND info.special_type_id <> 'PRESELL' AND info.party_id = ". PARTY_OUKU_MOBILE ." 
		AND cl.CallInTime >= '{$start_time}' AND cl.CallInTime < '{$end_time}' 
		AND info.tel != '' 
		AND info.order_type_id = 'SALE'
		AND info.order_time > FROM_UNIXTIME(cl.CallInTime)
";
$buy_after_callout_count = $slave_db->getOne($sql);

$sql = "
	SELECT COUNT(DISTINCT info.order_id) 
	FROM {$ecs->table('order_info')} info 
	INNER JOIN call_log cl ON  info.mobile = cl.PhoneNO 
	WHERE 
		info.order_time >= '{$start}' AND  info.order_time < '{$end}' 
		AND info.special_type_id <> 'PRESELL' AND info.party_id = ". PARTY_OUKU_MOBILE ."
		AND cl.CallInTime >= '{$start_time}' AND cl.CallInTime < '{$end_time}'
		AND info.mobile != '' 
		AND info.order_type_id = 'SALE' 
		AND info.order_time > FROM_UNIXTIME(cl.CallInTime)
";
$buy_after_callout_count += $slave_db->getOne($sql);
$buy_after_callout_percent = $worker_call_out_counts_total > 0 ? sprintf("%.2f", $buy_after_callout_count / $worker_call_out_counts_total * 100)." %" : '无呼出记录';
$smarty->assign('buy_after_callout_count', $buy_after_callout_count);
$smarty->assign('buy_after_callout_percent', $buy_after_callout_percent);

//每天售前、售中、售后电话量
$sql = "SELECT COUNT(*)  FROM call_log WHERE {$condition} AND (iINorOUT = 0) AND AgentGroupID = '80194091'";
$before_sale_count = $slave_db->getOne($sql);
//呼入转换率只统计售前呼入的
$buy_after_callin_percent = $before_sale_count > 0 ?sprintf("%.2f", $buy_after_callin_count / $before_sale_count * 100)." %" : '无售前呼入记录';
$smarty->assign('buy_after_callin_percent', $buy_after_callin_percent);

$sql = "SELECT COUNT(*)  FROM call_log WHERE {$condition} AND (iINorOUT = 0) AND AgentGroupID = '22097779'";
$sale_count = $slave_db->getOne($sql);
$sql = "SELECT COUNT(*)  FROM call_log WHERE {$condition} AND (iINorOUT = 0) AND AgentGroupID = '94769287'";
$saleservice_count = $slave_db->getOne($sql);
$sql = "SELECT COUNT(*)  FROM call_log WHERE {$condition} AND (iINorOUT = 0) AND AgentGroupID = '80078125'";
$admin_count = $slave_db->getOne($sql);

//呼叫放弃率 = 放弃电话数/接入电话数*100%
$sql = "SELECT COUNT(*) AS type_count, AgentGroupID FROM call_log WHERE {$condition} AND (StartACDTime <> '0') AND (AgentRcvTime = '0') AND (iINorOUT = 0) GROUP BY AgentGroupID ";
$temps = $slave_db->getAll($sql);
foreach ($temps as $temp) 
{
	if ($temp['AgentGroupID'] == '80194091') {
		$before_sale_giveup_percent = $before_sale_count > 0 ? sprintf('%0.2f', ($temp['type_count'] / $before_sale_count) * 100).'%' : '无呼入';
	} elseif ($temp['AgentGroupID'] == '22097779') {
		$sale_giveup_percent = $sale_count > 0 ? sprintf('%0.2f', ($temp['type_count'] / $sale_count) * 100).'%' : '无呼入';
	} elseif ($temp['AgentGroupID'] == '94769287') {
		$saleservice_giveup_percent = $saleservice_count > 0 ? sprintf('%0.2f', ($temp['type_count'] / $saleservice_count) * 100).'%' : '无呼入';
	} elseif ($temp['AgentGroupID'] == '80078125') {
		$admin_giveup_percent = $admin_count > 0 ? sprintf('%0.2f', ($temp['type_count'] / $admin_count) * 100).'%' : '无呼入';
	}
}

$smarty->assign('start', $start);
$smarty->assign('end', $end);
$smarty->assign('before_sale_count', $before_sale_count);
$smarty->assign('sale_count', $sale_count);
$smarty->assign('saleservice_count', $saleservice_count);
$smarty->assign('admin_count', $admin_count);
$smarty->assign('before_sale_giveup_percent', $before_sale_giveup_percent);
$smarty->assign('sale_giveup_percent', $sale_giveup_percent);
$smarty->assign('saleservice_giveup_percent', $saleservice_giveup_percent);
$smarty->assign('admin_giveup_percent', $admin_giveup_percent);


/* 导出 */
if (isset($_REQUEST['act']) && trim($_REQUEST['act']) == 'export')
{
	$filename = "从{$start}到{$end}的呼叫中心统计数据.xlsx";
	
	set_include_path(get_include_path() . PATH_SEPARATOR . realpath(dirname(__FILE__).'/../includes/Classes/'));
	require 'PHPExcel.php';
	require 'PHPExcel/IOFactory.php';
	
	$excel = new PHPExcel();
	
	// 设置属性
	$excel->getProperties()->setTitle($title);
	$excel->getProperties()->setSubject($title);
	
	$sheet = $excel->getActiveSheet();
	$sheet->setCellValue('A1', "呼叫中心统计[{$start}到{$end}]");
	$sheet->mergeCells('A1:D1');
	$sheet->getStyle("A1")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$sheet->getStyle("A1")->getFont()->setBold(true);
	$sheet->getColumnDimension('B')->setWidth(50); 
	
	$sheet->setCellValue("C2", '总数');
	$sheet->setCellValue("D2", '平均');
	
	// 数据
	// 售前
	$i = 3;
	$sheet->setCellValue("A{$i}", '售前');
	$sheet->getStyle("A{$i}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);  

	$sheet->setCellValue("B{$i}", '售前呼入数量');
	$sheet->setCellValue("C{$i}", $call_in_total['80194091']);
	$sheet->setCellValue("D{$i}", round($call_in_total['80194091']/$days, 1));
	$i++;
	
	$sheet->setCellValue("B{$i}", '售前呼出数量');
	$sheet->setCellValue("C{$i}", $call_out_total['80194091']);
	$sheet->setCellValue("D{$i}", round($call_out_total['80194091']/$days, 1));
	
	$sheet->mergeCells("A3:A{$i}");
	$i++;
	
	// 售中
	$flag = $i;
	$sheet->setCellValue("A{$i}", '售中');
	$sheet->getStyle("A{$i}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER); 

	$sheet->setCellValue("B{$i}", '售中呼入数量');
	$sheet->setCellValue("C{$i}", $call_in_total['22097779']);
	$sheet->setCellValue("D{$i}", round($call_in_total['22097779']/$days, 1));
	$i++;

	$sheet->setCellValue("B{$i}", '售中呼出数量');
	$sheet->setCellValue("C{$i}", $call_out_total['22097779']);
	$sheet->setCellValue("D{$i}", round($call_out_total['22097779']/$days, 1));
	
	$sheet->mergeCells("A{$flag}:A{$i}");
	$i++;
	
	// 售后
	$flag = $i;
	$sheet->setCellValue("A{$i}", '售后');
	$sheet->getStyle("A{$i}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

	$sheet->setCellValue("B{$i}", '售后呼入数量');
	$sheet->setCellValue("C{$i}", $call_in_total['94769287']);
	$sheet->setCellValue("D{$i}", round($call_in_total['94769287']/$days, 1));
	$i++;
	
	$sheet->setCellValue("B{$i}", '售后呼出数量');
	$sheet->setCellValue("C{$i}", $call_out_total['94769287']);
	$sheet->setCellValue("D{$i}", round($call_out_total['94769287']/$days, 1));
	
	$sheet->mergeCells("A{$flag}:A{$i}");
	$i++;
    
    // 售后
	$flag = $i;
	$sheet->setCellValue("A{$i}", '管理组');
	$sheet->getStyle("A{$i}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);

	$sheet->setCellValue("B{$i}", '管理组呼入数量');
	$sheet->setCellValue("C{$i}", $call_in_total['80078125']);
	$sheet->setCellValue("D{$i}", round($call_in_total['80078125']/$days, 1));
	$i++;
	
	$sheet->setCellValue("B{$i}", '管理组呼出数量');
	$sheet->setCellValue("C{$i}", $call_out_total['80078125']);
	$sheet->setCellValue("D{$i}", round($call_out_total['80078125']/$days, 1));
	
	$sheet->mergeCells("A{$flag}:A{$i}");
	$i++;

	// 订单转化率
	$flag = $i;	
	$sheet->setCellValue("A{$i}", '订单转化率');
	$sheet->getStyle("A{$i}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
	
	$sheet->setCellValue("B{$i}", '呼入后用户订单转换率');
	$sheet->setCellValue("C{$i}", $buy_after_callin_percent);
	$sheet->mergeCells("C{$i}:D{$i}");
	$i++;

	$sheet->setCellValue("B{$i}", '呼出后用户订单转换率');
	$sheet->setCellValue("C{$i}", $buy_after_callout_percent);
	$sheet->mergeCells("C{$i}:D{$i}");	
	$sheet->mergeCells("A{$flag}:A{$i}");
	
	// 输出
	if (!headers_sent())
	{
		$output = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
		$output->setOffice2003Compatibility(true);
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment; filename="'.$filename.'"');
		header('Cache-Control: max-age=0');
		$output->save('php://output');
	}
}
/* 显示 */
else
{
	$smarty->display('call_center/analyze_phone.htm');	
}
