<?php
/**
 * 注册来源统计  按时间 | 按来源 | 按来源地
 * 
 * @author yxiang@oukoo.com 3/30/2007
 */
define('IN_ECS', true);

require_once('includes/init.php');
require_once("function.php");
admin_priv('analyze_register_source');

/**
 * 初始化变量
 */
// url参数
$url_params = array();

// 格式化时间
$start = $_REQUEST['start'];
$end = $_REQUEST['end']; 
if (!strtotime($start)) 
{
	$start = date('Y-m-d');
}
if (!strtotime($end)) 
{
	$end = date('Y-m-d');
}

// 查询条件
if (isset($_POST['source']) )
{
	$source = intval($_POST['source']); 
}
else if (isset($_GET['param']))
{
	$source = intval($_GET['param']);
}
else
{
	$source = '';	
}

$url_params['start']  = $start;
$url_params['end']    = $end;
$url_params['param']  = $source;

/**
 * 查询组合数据
 */	
// 注册来源数据
$register_sources = $slave_db->getAllCached('SELECT * FROM '.$ecs->table('user_register_sources'));
// 注册来源地数据
$register_provinces = $slave_db->getAllCached('SELECT `region_id`,`region_name` FROM '.$ecs->table('region') ." WHERE parent_id = 1"); // 只限于中国的省份
// 用户查询时间区间条件
$sql_conditions = "u.reg_time > UNIX_TIMESTAMP('{$start}') AND u.reg_time <= UNIX_TIMESTAMP(DATE_ADD('{$end}',INTERVAL 1 DAY))";
if (!empty($source))
{
	$sql_conditions .= " AND u.reg_source = '{$source}'";
}

// 总数
$total = $slave_db->getOne(" SELECT COUNT(*) FROM  {$ecs->table('users')} u WHERE {$sql_conditions} "); 
// 每页多少条记录
$page_size = 15;
// 当前页码
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? $_GET['page'] : 1 ;
if ($page > ceil($total/$page_size))
{
	$page = 1;
	$_REQUEST['page'] = $page;
}
// 数据库查询偏移量
$setoff = ($page-1) * $page_size; 
// 列表数据
$list = $slave_db->getAll(" SELECT u.user_id, u.userId, u.user_name, u.reg_time, s.source AS reg_source, p.region_name AS reg_province 
                      FROM {$ecs->table('users')} u 
                      LEFT JOIN {$ecs->table('user_register_sources')} s ON u.reg_source = s.id
                      LEFT JOIN {$ecs->table('region')} p ON u.reg_province = p.region_id
                      WHERE {$sql_conditions}
                      ORDER BY u.user_id DESC
                      LIMIT {$setoff}, {$page_size}  
                    ");
if ($total > 0)
{
	/*
	 * 按注册来源统计,
	 */
 	if (empty($source))  // 当选择了注册来源，是不需要统计该项的
 	{
		$result = $slave_db->query(" SELECT COUNT(u.user_id) AS num, u.reg_source AS source_id  
	                      	   FROM {$ecs->table('users')} u 
	                           WHERE {$sql_conditions} 
							   GROUP BY u.reg_source
	                         ");
	
	    if ($result !== false)
	    {
	        $rows = array();
	        while ($row = $slave_db->fetchRow($result))
	        {
	            $rows[$row['source_id']] = $row['num'];
	        }
	    } 
	 	if (isset($rows) && count($rows) > 0)
	 	{	
	 		$i = 0;
			$register_source_stat = array();  // 统计结果的数组
			foreach ($register_sources as $item)
			{
				$num = isset($rows[$item['id']]) ? $rows[$item['id']] : 0 ; 
				$register_source_stat[$i]['source']  = $item['source'];
				$register_source_stat[$i]['total']   = $num;
				$register_source_stat[$i]['percent'] = round($num/$total, 3) * 100;   // 百分比
				$i++; 
			}	
	 	}
	 	unset($result, $rows); 		
 	}

	/*
	 * 按注册来源地来统计
	 */
	$result = $slave_db->query(" SELECT COUNT(u.user_id) AS num, u.reg_province AS province_id  
                      	   FROM {$ecs->table('users')} u 
                           WHERE {$sql_conditions} 
						   GROUP BY u.reg_province
                         ");
    if ($result !== false)
    {
        $rows = array();
        while ($row = $slave_db->fetchRow($result))
        {
            $rows[$row['province_id']] = $row['num'];
        }
    }
 	if ($rows)
 	{	
 		$i = 0;
		$register_province_stat = array();  // 统计结果的数组
		foreach ($register_provinces as $item)
		{
			$num = isset($rows[$item['region_id']]) ? $rows[$item['region_id']] : 0 ; 
			$register_province_stat[$i]['province'] = $item['region_name'];
			$register_province_stat[$i]['total']    = $num;
			$register_province_stat[$i]['percent']  = round($num/$total, 3) * 100;   // 百分比 
			$i++;
		}	
 	}
 	unset($result, $rows);

	/*
	 * 按注册时间段来统计 
	 */
	$result = $slave_db->query(" SELECT COUNT(u.user_id) AS num, FROM_UNIXTIME(u.reg_time, '%H') as time  
                      	   FROM {$ecs->table('users')} u 
                           WHERE {$sql_conditions} 
						   GROUP BY FROM_UNIXTIME(u.reg_time, '%H')
                         ");
    if ($result !== false)
    {
        $rows = array();
        while ($row = $slave_db->fetchRow($result))
        {
            $rows[$row['time']] = $row['num'];
        }
    }
 	if ($rows)
 	{	
 		// 时间段的对应mapping
 		$times_mapping = array
	    (
			'00' => '00:00 - 01:00',
			'01' => '01:00 - 02:00',
			'02' => '02:00 - 03:00',
			'03' => '03:00 - 04:00',
			'04' => '04:00 - 05:00',
			'05' => '05:00 - 06:00',
			'06' => '06:00 - 07:00',
			'07' => '07:00 - 08:00',
			'08' => '08:00 - 09:00',
			'09' => '09:00 - 10:00',
			'10' => '10:00 - 11:00',
			'11' => '11:00 - 12:00',
			'12' => '12:00 - 13:00',
			'13' => '13:00 - 14:00',
			'14' => '14:00 - 15:00',
			'15' => '15:00 - 16:00',
			'16' => '16:00 - 17:00',
			'17' => '17:00 - 18:00',
			'18' => '18:00 - 19:00',
			'19' => '19:00 - 20:00',
			'20' => '20:00 - 21:00',
			'21' => '21:00 - 22:00',
			'22' => '22:00 - 23:00',
			'23' => '23:00 - 24:00',
		);
		
		$i = 0;
		$register_time_stat = array();  // 统计结果的数组
		ksort($rows);
		foreach ($rows as $time => $num) // 00 - 23
		{
			$register_time_stat[$i]['time']    = $times_mapping[$time];
			$register_time_stat[$i]['total']   = $num;
			$register_time_stat[$i]['percent'] = round($num/$total, 3) * 100;   // 百分比 
			$i++;
		}	
 	}
 	unset($result, $rows);
}

/**
 * 构造分页
 */
require('../includes/cls_page.php');
$pagination = new Pagination($total, $page_size, -1, 'page', 'analyze_register_source.php', null, $url_params);
$paginater = $pagination->get_simple_output();

/**
 * 模板变量
 */
/* 下拉表单 */
$smarty->assign('sources', $register_sources);        // 注册来源列表
$smarty->assign('provinces', $register_provinces);    // 注册来源地列表

/* 状态变量 */
$smarty->assign('source', $source);
$smarty->assign('start', $start);                   // 开始时间
$smarty->assign('end', $end);                       // 结束时间

/* 统计列表数据 */
if ($total > 0)
{
	$smarty->assign('register_source_stat', $register_source_stat);      // 按来源
	$smarty->assign('register_province_stat', $register_province_stat);  // 按来源地
	$smarty->assign('register_time_stat', $register_time_stat);          // 按注册时间
}
 
 /* 分页列表数据 */
$smarty->assign('list', $list);                   // 分页列表
$smarty->assign('total', $total);                   // 记录总数
$smarty->assign('paginater', $paginater);           // 分页

$smarty->display('oukooext/analyze_register_source.htm');

?>