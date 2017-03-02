<?php 

/**
 * 淘宝销售人员在线记录导入功能
 * 
 * @author yxiang@oukoo.com
 * @copyright 2010 ouku.com 
 */
 
define('IN_ECS', true);
require_once('../includes/init.php');
require_once(ROOT_PATH . 'admin/function.php');
require_once(ROOT_PATH . 'includes/lib_order.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'includes/helper/uploader.php');
require_once(ROOT_PATH . 'includes/debug/lib_log.php');


$act =  // 动作
    isset($_REQUEST['act']) && 
    in_array($_REQUEST['act'], array('upload', 'delete', 'import')) 
    ? $_REQUEST['act'] 
    : null ;
$info =  // 返回的信息
    isset($_REQUEST['info']) && trim($_REQUEST['info']) 
    ? urldecode($_REQUEST['info']) 
    : false ;

// 信息
if ($info) {
    $smarty->assign('message', $info);
}

// 当前时间 
$now = date('Y-m-d H:i:s');

/*
 * 处理post请求
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $act) {

    switch ($act) {
        /**
         * 上传文件
         */
        case 'upload' :

            /* 文件上传并读取 */
            @set_time_limit(300);
            $uploader = new Helper_Uploader();
            $max_size = $uploader->allowedUploadSize();  // 允许上传的最大值

            if (!$uploader->existsFile('file')) {
                $smarty->assign('message', '没有选择上传文件，或者文件上传失败');
                break;
            }

            // 取得要上传的文件句柄
            $file = $uploader->file('file');

            // 检查上传文件
            if (!$file->isValid('txt', $max_size)) {
                $smarty->assign('message', '非法的文件! 请检查文件类型类型(txt), 并且系统限制的上传大小为'. $max_size/1024/1024 .'MB');
                break;
            }

            // 分析文件内容
            $fp = @fopen($file->filepath(), 'r');
            if (!$fp) {
                $smarty->assign('message', '文件打不开');
                break;
            } else {
            	
            	/**
            	 * 1. 分析文件， 取得记录集
            	 */
            	
                // 取得淘宝销售人员列表
                $taobao_sales_list = $db->getAll("SELECT taobao_sales_id, taobao_shop_id, nickname FROM taobao_sales WHERE enabled = 'Y'");
                $taobao_sales_list = Helper_Array::toHashmap($taobao_sales_list, 'nickname');
                
                // 取得数据
                $data = $sortby = array(); 
                $offset = 0;
                $regex = '/^([0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}) (.+)\([0-9]{2}:[0-9]{2}:[0-9]{2}\): (.*)$/';
                while (!feof($fp)) {
                    $line = iconv('GB18030', 'UTF-8', fgets($fp, 4096));
                    if (preg_match($regex, $line, $matches)) {
                        $nickname = $matches[2];
                        $time = $matches[1];
                        $_t = explode(' ', $time);
                        $day = $_t[0]; 
                        $timestamp = strtotime($time);
                        
                        // 匹配上线（下线）字样
                        $p1 = mb_strpos($matches[3], '上', 0, 'UTF-8');
                        $p2 = mb_strpos($matches[3], '下', 0, 'UTF-8');
                        
                        // 表示是上线
                        if ($p1 !== FALSE) {
                        	$owner = trim(mb_substr($matches[3], 0, $p1, 'UTF-8'));
                        	$data[$offset] = array(
                        	   'taobao_shop_id' => $taobao_sales_list[$nickname]['taobao_shop_id'],
                        	   'taobao_sales_id' => $taobao_sales_list[$nickname]['taobao_sales_id'],
                        	   'nickname' => $nickname,
                        	   'owner' => $owner,
                        	   'time' => $time,
                        	   'timestamp' => $timestamp,
                        	   'day' => $day,
                        	   'type' => 'up',
                        	);
                        	$sortby[$offset] = $timestamp;  // 排序用
                        	$offset++;
                        	continue;
                        }

                        // 表示是下线
                        if ($p2 !== FALSE) {
                        	$owner = trim(mb_substr($matches[3], 0, $p2, 'UTF-8'));
                            $data[$offset] = array(
                                'taobao_shop_id' => $taobao_sales_list[$nickname]['taobao_shop_id'],
                                'taobao_sales_id' => $taobao_sales_list[$nickname]['taobao_sales_id'],
                                'nickname' => $nickname,
                                'owner' => $owner,
                                'time' => $time,
                                'timestamp' => $timestamp,
                                'day' => $day,
                                'type' => 'down',
                            );
                            $sortby[$offset] = $timestamp;
                        	$offset++;
                        	continue;
                        }
                    }
                }
                fclose($fp);
                
                /**
                 * 2. 分析记录集，将下班记录与上班记录匹配
                 */
                $rowset = array();
                if (!empty($data)) {
                	// 需要将导入的数据按时间排序
                	array_multisort($sortby, SORT_ASC, $data); 
                	
                	// 将下班记录与上班记录匹配
                	$i = 0;
	                foreach ($data as $offset => $item) {
	                	if ($item['type'] == 'up') {
                            foreach ($rowset as $key => $row) {
                                if ($item['nickname'] == $row['nickname'] && $item['owner'] == $row['owner'] && $item['day'] == $row['day']) {
                                	// 同一天有多条上线记录的话，以第一条为准
                                    continue;
                                }
                            }
                            
                            $rowset[$i] = $item;
                            $rowset[$i]['up_timestamp'] = $item['time'];
                            $rowset[$i]['closed'] = false;  // 是否闭合
                            $i++;
	                	}
	                	else if ($item['type'] == 'down') {
                            foreach ($rowset as $key => $row) {
                                if ($row['closed'] == false && $item['nickname'] == $row['nickname'] && $item['owner'] == $row['owner'] && $item['day'] == $row['day']) {
                                    //  匹配上线记录
                                    $rowset[$key]['down_timestamp'] = $item['time'];
                                    $rowset[$key]['onlie_time'] = $item['timestamp'] - $row['timestamp'];
                                    $rowset[$key]['closed'] = true;
                                }
                            }
	                	}
	                	
	                }
                }

                /**
                 * 3. 将数据保存到数据库
                 */
                if (!empty($rowset)) {
                	$sql = "INSERT INTO taobao_sales_online_log (`taobao_sales_id`, `taobao_shop_id`, `nickname`, `owner`, `up_timestamp`, `down_timestamp`, `online_time`, `imported_timestamp`, `imported_by_user_login`) VALUES ('%d', '%d', '%s', '%s', '%s', '%s', '%d', '". date('Y-m-d H:i:s') ."', '{$_SESSION['admin_name']}')";
                	$num = 0;
                	foreach ($rowset as $row) {
                        $db->query(sprintf($sql, $row['taobao_sales_id'], $row['taobao_shop_id'], $row['nickname'], $row['owner'], $row['up_timestamp'], $row['down_timestamp'], $row['online_time']), 'SILENT');
                        $num++;                		
                	}
                }
            }

            $smarty->assign('message', "导入完毕，共导入数据{$num}"); 
        break;
    }
}



/**
 * 显示
 */
$smarty->assign('tpls_list', $tpls_list);
$smarty->display('taobao/taobao_sales_online_log_import.htm');


/**
 * 检测是否是我们的人员
 *
 * @return string|false
 */
function check_replier($replier) {
    static $sales;

    if (!isset($sales)) {
        $resource = $GLOBALS['db']->query("SELECT `taobao_shop_id`, `nickname` FROM taobao_sales WHERE enabled = 'Y'");
        if ($resource) {
            while ($row = $GLOBALS['db']->fetchRow($resource)) {
                $sales[$row['nickname']] = $row;
            }
        }
    }

    if (isset($sales[$replier]))
        return $sales[$replier];
    else
        return false;
}

/**
 * 取得回复花费的时间
 * 
 * @param string $post_datetime  留言时间
 * @param string $reply_point    回复时间
 * 
 * @return 返回回复该留言花费的秒数，非工作时间内的不算
 */
function _get_reply_spent_time($post_point, $reply_point)
{
    // 工作时间为每天9点到24点
	$worktime_s = 9;
    $worktime_e = 24;
	
	$_timestamp = strtotime($post_point);
	if ($_timestamp === false) return 0;
	
	// 留言发布时的星期和小时 
	list($_dayofweek, $_hour, $_days) = explode('#', @date('w#G#z', $_timestamp));
			
	if ($_hour < $worktime_s)
		$_timestamp = strtotime( date("Y-m-d {$worktime_s}:00:00", $_timestamp) );
	elseif ($_hour > $worktime_e)
		$_timestamp = strtotime('+1 day', strtotime(date("Y-m-d {$worktime_s}:00:00", $_timestamp)) );		

	$_timestamp2 = strtotime($reply_point);
	$spent = $_timestamp2 - $_timestamp;
	$date_diff = date('z', $_timestamp2) - $_days;
	if ($date_diff > 1) {  // 隔天的,每天按12小时算
        $spent -= $date_diff * 43200;
	}
	return $spent > 0 ? $spent : 0 ;
}

