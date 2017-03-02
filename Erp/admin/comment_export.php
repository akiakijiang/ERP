<?php
/**
 * 留言的导出
 * 
 * @author  yxiang@oukoo.com  3/31/2009
 */

define('IN_ECS', true);

require('includes/init.php');
admin_priv('comment_csv');

/* act操作项的初始化 */
if (empty($_REQUEST['act']))
{
    $_REQUEST['act'] = 'page'; 
}
else
{
    $_REQUEST['act'] = trim($_REQUEST['act']);
}

// 分类
$types = array
(
	'goods'     => '商品咨询',
	'shipping'  => '物流配送',
	'payment'   => '支付问题',
	'postsale'  => '保修及发票',
	'complaint' => '投诉建议',
);

/*
 * 页面
 */
if ($_REQUEST['act']  == 'page')
{
	$smarty->assign('types', $types);
	$smarty->display('oukooext/comment_export.htm');	
}

/*
 * 导出
 */
if ($_REQUEST['act'] == 'export')
{
	// 检查权限
    admin_priv('comment_export_csv');
    
    // 条件
    $type  = empty($_REQUEST['type']) ? NULL : trim($_REQUEST['type']);
    $start = empty($_REQUEST['start_date']) ? date('Y-m-d') : $_REQUEST['start_date'];
	$end   = empty($_REQUEST['end_date']) ? date('Y-m-d') : $_REQUEST['end_date'];
	
	// 限制字段
	$field = '`nick`,`comment`,`post_datetime`,`reply`,`replied_nick`,`replied_datetime`,`status`';
	
	// 查询	
	$sql = " SELECT {$field} FROM `bj_comment` WHERE parentid = 0 AND (`replied_datetime` BETWEEN '{$start}' AND '{$end}') ";
 	if (!empty($type)) { $sql .= " AND `type` = '{$type}'"; }
	$result = $db->query($sql);
	
	ob_start();
	print csv_iconv('"留言者","留言内容","留言时间","回复内容","回复人","回复时间","状态"'). "\r\n";
	if ($result !== false)
	{
		while ($row = $db->fetchRow($result))
		{
			/*
			$row['nick']         = csv_escape($row['nick']);
			$row['comment']      = csv_escape($row['comment']);
			$row['reply']        = csv_escape($row['reply']);
			$row['replied_nick'] = csv_escape($row['replied_nick']);
			*/
			array_walk($row, 'csv_escape');
			print implode(',', $row) ."\r\n";
			//print "{$nick},{$comment},{$row['post_datetime']},{$reply},{$replied_nick},{$row['replied_datetime']},{$row['status']}\r\n";
		}
	}
	$output = ob_get_clean();
	
	// 导出成csv
	$title = "{$start}到{$end}的留言数据";
	if (!empty($type) && isset($types[$type])) { $title .= "({$types[$type]})"; }
	$title = csv_iconv($title) . ".csv";
	
	// 输出
	header("Content-type:application/vnd.ms-excel");
	//header("Content-type:text/csv");
	header("Content-Disposition:attachment; filename={$title}");
	header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
	header('Expires:0');
	header('Pragma:public');
	echo $output;
	exit();	
}

/**
 * 过滤
 */
function csv_escape(& $str)
{
    $str = str_replace(array(',', '"', "\n\r"), array('，', '“', ''), $str);
    if ($str == "")
	{
		$str = '""';	
	}
	else
	{
		$str = '"'.csv_iconv($str).'"';
	}
}

/**
 * 转码
 */
function csv_iconv($str)
{
    return iconv("UTF-8", "GB18030", $str);
}

?>