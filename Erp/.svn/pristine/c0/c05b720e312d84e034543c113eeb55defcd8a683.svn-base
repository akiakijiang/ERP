<?php 

/**
 * 咨询导入功能
 * 
 * @author yxiang@oukoo.com
 * @copyright 2009 ouku.com 
 */
define('IN_ECS', true);
require_once('../includes/init.php');
require_once(ROOT_PATH . 'admin/function.php');
require_once(ROOT_PATH . 'includes/lib_order.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'includes/helper/uploader.php');
require_once(ROOT_PATH . 'includes/debug/lib_log.php');
admin_priv('taobao_consult');

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

            @set_time_limit(300);
            $uploader = new Helper_Uploader();

            if (!$uploader->filesCount()) {
                $smarty->assign('message', '没有选择上传文件，或者文件上传失败');
                break;
            }
            
            $files = array();
            foreach ($uploader->allFiles() as $file) {
                if (!$file->isValid('txt')) {
                    $smarty->assign('message', '文件'. $file->filename() .'不是txt格式的, 操作取消');
                    continue;
                }
                
                // 取得咨询内容的拥有人
                $matches = NULL;
                if (preg_match('/^.*\[(.+)\].*/', $file->filename(), $matches)) {
                    $files[] = array('owner' => $matches[1], 'file' => $file->filepath(), 'name' => $file->filename());
                } 
                else {
                    $smarty->assign('message', '文件'. $file->filename() .'的文件名格式有误，没有咨询内容拥有人，操作取消');
                    break 2;
                }
            }
            
            $info = "";
            foreach ($files as $file) {
                $parse_result = consult_import_file_parse($file['file'], $file['owner']);
                if ($parse_result->result == "OK") {
                    $info .= $file['name'] . "导入成功, 导入会话" . $parse_result->msg . "个<br>";
                    QLog::log($_SESSION['admin_name'] . " " . $file['name'] . "导入成功, 导入会话" . $parse_result->msg . "个<br>");
                } else {
                    $info .= $file['name'] . "导入失败, 可能是" . $parse_result->msg . " 请重新尝试或者联系erp组<br>";
                    QLog::log($_SESSION['admin_name'] . " " . $file['name'] . "导入失败, " . $parse_result->msg);
                }
            }

            $smarty->assign('message', $info); 
        break;
    }
}

/**
 * 显示
 */
$smarty->assign('tpls_list', $tpls_list);
$smarty->display('taobao/taobao_consult_import.htm');


/**
 * 解析淘宝咨询文件
 *
 * @param string $file  咨询文件
 * @param string $owner 咨询文件拥有人
 * 
 * @return返回导入的会话数
 */
function consult_import_file_parse($file, $owner) {
    global $db;
   
    $parse_result = new stdClass();
    
    if ($fp = @fopen($file, 'r')) {
        $sql1 = "INSERT INTO ecshop.taobao_consulting_section (`taobao_shop_id`, `referee`, `replier`, `owner`, `start`, `end`, `avg_respond_time`, `refer_length`, `reply_length`, `imported_timestamp`, `imported_by_user_login`) VALUES ('%d', '%s', '%s', '{$owner}', '%s', '%s', '%d', '%d', '%d', '". date('Y-m-d H:i:s') ."', '{$_SESSION['admin_name']}')";
        $sql2 = "INSERT INTO ecshop.taobao_consulting_content (`section_id`, `referee`, `replier`, `time`, `interval`, `type`, `content`, `length`) VALUES %s";
        $regex = '/^([0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}) (.+)\([0-9]{2}:[0-9]{2}:[0-9]{2}\): (.*)$/';
        $regex1 = '/^([0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}) (.+): (.*)$/';
        $_status = array(
            'in_section' => false,       // 标识一个咨询会话是否开始了
            'sequence'  => 0,            // 咨询会话序列号
            'referee' => NULL,           // 当前会话中的咨询人
            'replier' => NULL,           // 当前会话中的咨询回复人
            'start'   => NULL,           // 会话开始时间
            'end'     => NULL,           // 会话结束时间
            'consulting_start' => NULL,  // 一条咨询开始时的时间
            'reply_number' => 0,         // 这个会话中共回复了多少条咨询
            'reply_interval' => 0,       // 这个会话中回复花费的总间隔时间
            'reply_length' => 0,         // 这个会话中回复字数
            'refer_length' => 0,         // 这个会话中提问字数
        );
    
        while (!feof($fp)) {
            $time = $person = $content = '';
            $line = iconv('GB18030', 'UTF-8', fgets($fp, 4096));
            if (preg_match(trim($regex), $line, $matches) || preg_match(trim($regex1), $line, $matches)) {
//            	Qlog::log($line.'匹配成功');
                $time    = $matches[1];
                $person  = $matches[2];
                $content = trim($matches[3]);
                $length  = mb_strlen($content, "UTF-8");
    
                if($content != ''){
//                	Qlog::log($content.'--导入');
                	// 如果没有开始会话，则会话开始
	                if (!$_status['in_section']) {
	                    $_status['in_section'] = true; 
	                    $_status['sequence']++;
	                    $_status['start'] = $_status['end'] = $time;
	                } else {
	                    $_status['end'] = $time;
	                }
	    
	                // 回复
	                if (consult_import_check_replier($person) !== false) {
	                    $_status['replier'] = $person;
	                    if (!is_null($_status['consulting_start'])) {
	                        // 取得从一条咨询开始到回复间隔了多少时间
	                        $interval = consult_import_get_reply_spent_time($_status['consulting_start'], $time);
	                        // 距离咨询开始已经过了120分钟
	                        if ($interval > 7200) {
	                            $interval = 0;
	                        }
	                        $_status['consulting_start'] = NULL;
	                        $_status['reply_number']++;
	                        $_status['reply_interval'] += $interval;
	                        $_status['reply_length'] += $length;
	                        $rowset[] = "('#sid{$_status['sequence']}#', NULL, ". $db->qstr($person). ", '{$time}', '{$interval}', 'REPLY', ". $db->qstr($content) .", '{$length}')";
	                    } else {
	                        $_status['refer_length'] += $length;
	                        $rowset[] = "('#sid{$_status['sequence']}#', NULL, ". $db->qstr($person). ", '{$time}', NULL, 'REPLY', ". $db->qstr($content) .", '{$length}')";
	                    }
	                }
	                // 客户咨询
	                else {
	                    $_status['referee'] = $person;
	                    // 询问开始时间
	                    if (is_null($_status['consulting_start'])) {
	                        $_status['consulting_start'] = $time;
	                    } 
	                    // 间隔7200秒后继续询问也算询问开始时间
	                    else if (strtotime($time) - strtotime($_status['consulting_start']) > 7200) {
	                        $_status['consulting_start'] = $time;
	                    }
	                    $rowset[] = "('#sid{$_status['sequence']}#', ". $db->qstr($person) .", NULL,  '{$time}', NULL, 'CONSULT', ". $db->qstr($content) .", '{$length}')";
	                }
                }
            }
            // 换行， 标示一个咨询段落结束
            elseif (!trim($line) && (preg_match('/^\r\n$/', $line) || feof($fp))) {
                if ($_status['in_section']) {
                    // 插入记录
                    $avg = $_status['reply_interval'] == 0 ? 0 : round($_status['reply_interval']/$_status['reply_number']) ; 
                    $shop = consult_import_check_replier($_status['replier']);
                    
                    //判断taobao_shop_id、replier、referee不为空
                    if (empty($shop['taobao_shop_id']) || empty($_status['replier']) || empty($_status['referee'])) {
//                        $parse_result->result = "ERROR";
//                        $parse_result->msg = "会话中回复人为空";
//                        return $parse_result;
                          continue;
                    }
                    
                    //判断是否重复导入
                    $is_exists = is_exists_consult($shop['taobao_shop_id'], $_status['referee'], $_status['replier'], $_status['start'], $_status['end']);
                    if (!$is_exists && $db->query(sprintf($sql1, $shop['taobao_shop_id'], $_status['referee'], $_status['replier'], $_status['start'], $_status['end'], $avg, $_status['refer_length'], $_status['reply_length']))) {
                        $sid = $db->insert_id();
                        foreach ($rowset as $key => $row) {
                            $rowset[$key] = str_replace("#sid{$_status['sequence']}#", $sid, $row);
                        }
//                        Qlog::log(sprintf($sql2, implode(', ', $rowset)));
                        $db->query(sprintf($sql2, implode(', ', $rowset)));
                    }
    
                    $rowset = array();
                    $_status['in_section'] = false;
                    $_status['consulting_start'] = NULL;
                    $_status['reply_number'] = 0;
                    $_status['reply_interval'] = 0;
                    $_status['reply_length'] = 0;
                    $_status['refer_length'] = 0;
                    $_status['replier'] = NULL;
                    $_status['referee'] = NULL;
                }
            }
        }
    
        fclose($fp);
        $parse_result->result = 'OK';
        $parse_result->msg = $_status['sequence'];
        return $parse_result;
    }
    else {
        $parse_result->result = 'ERROR';
        $parse_result->msg = "无法打开文件";
        return $parse_result;
    }
}

/**
 * 检测是否是我们的人员
 *
 * @return string|false
 */
function consult_import_check_replier($replier) {
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
function consult_import_get_reply_spent_time($post_point, $reply_point)
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

/**
 * 判断是否已经存在，防止重复导入
 * 无效记录允许重复
 * 判断的维度为: taobao_shop_id, referee，replier, start end
 */
 
 function is_exists_consult($taobao_shop_id, $referee, $replier, $start, $end){
     global $db;
     if ($taobao_shop_id && $referee && $replier && $start && $end) {
         $sql = "select 1 from ecshop.taobao_consulting_section 
                 where taobao_shop_id = {$taobao_shop_id} and referee = '{$referee}' and replier = '{$replier}' 
                 and start = '{$start}' and end = '{$end}'
                 limit 1 
                 ";
         if ($db->getOne($sql)) {
             return true;
         }
         return false;
         
     }
     return true;
 }