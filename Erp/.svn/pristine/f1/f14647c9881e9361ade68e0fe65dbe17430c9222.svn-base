<?php 

/**
 * 导入支付宝红包
 * 
 * @author ncchen@oukoo.com
 * @copyright 2010 ouku.com 
 */
 
define('IN_ECS', true);
require_once('../includes/init.php');
admin_priv('import_alipay_bonus');
require_once(ROOT_PATH . 'admin/function.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'includes/helper/uploader.php');
require_once(ROOT_PATH . 'includes/debug/lib_log.php');
require_once(ROOT_PATH . 'admin/includes/lib_filelock.php');


$act =  // 动作
    isset($_REQUEST['act']) && 
    in_array($_REQUEST['act'], array('upload', 'export', 'save')) 
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

$config = array(
    'Sheet1' => array(
        'bill_no'               => '序号',
        'taobao_id'             => '淘宝ID',
        'buyer_alipay_no'       => '支付宝账号',
        'alipay_bonus'          => '红包金额',
    ),
);

$status_mapping = array(
    'OK'        =>  '正常',
    'USED'      =>  '已使用',
    'OVERDUE'   =>  '过期',  
);
$smarty->assign("status_mapping", $status_mapping);
/*
 * 处理post请求
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $act) {
    switch ($act) {
        /**
         * 上传文件， 检查上传的excel格式，并读取数据提取并添加收款 
         */
        case 'upload' :
QLog::log('快递账单导入：');
            // 添加文件锁
            $file_name = 'upload';
            if (is_file_lock($file_name)) {
                 //todo
                echo '统计正在执行，30秒后自动刷新，请不要高频率刷新本页面';
                flush();
                sleep(30);
                print "<script>location.href='{$_SERVER['SCRIPT_URI']}?{$_SERVER['QUERY_STRING']}'</script>";
                exit;
            }
            create_file_lock($file_name);
            /* 文件上传并读取 */
            @set_time_limit(300);
            $uploader = new Helper_Uploader();
            $max_size = $uploader->allowedUploadSize();  // 允许上传的最大值

            if (!$uploader->existsFile('excel')) {
                $message = '没有选择上传文件，或者文件上传失败';
                break;
            }

            // 取得要上传的文件句柄
            $file = $uploader->file('excel');
           
            // 检查上传文件
            if (!$file->isValid('xls, xlsx', $max_size)) {
                $message = '非法的文件! 请检查文件类型类型(xls, xlsx), 并且系统限制的上传大小为'. $max_size/1024/1024 .'MB';
                break;
            }
            
            // 读取excel
            $rowset = excel_read($file->filepath(), $config, $file->extname(), $failed);
            if (!empty($failed)) {
                $message = reset($failed);
                break;
            }

            /* 检查数据  */
            
            // 数据读取失败
            if (empty($rowset)) {
                $message = 'excel文件中没有数据,请检查文件';
                break;
            }
            
            // 快递账单
            if (empty($rowset['Sheet1'])) {
                $message = 'excel文件中没有订单信息';
                break;                
            }
            
            $bonus_list = $rowset['Sheet1'];  // 红包列表
            // 添加红包
            add_taobao_alipay_bonus($bonus_list);
            
            // 删除上传的文件
            $file->unlink();
            if (empty($errors)) {
                $message = "导入完毕，查看导入报告";
            } else {
                $message = "导入失败";
            }
            break;
        case "export":
            $alipay_bonus_list = get_taobao_alipay_bonus_list();
            $smarty->assign("alipay_bonus_list", $alipay_bonus_list);
            $csv = true;
            break;
        case "save":
            extract($_POST);
            $bonus['taobao_alipay_bonus_id'] = $taobao_alipay_bonus_id;
            $bonus['status'] = $status;
            save_taobao_alipay_bonus($bonus);
    }
    if (!$csv) {
        QLog::log("导入：结束。");
        if ($act == 'upload') {
            release_file_lock($file_name);
        }
        header("Location: import_alipay_bonus.php?message=". $message);
        exit;
    }
}

/**
 * 显示
 */
if ($csv) {
	header("Content-type:application/vnd.ms-excel");
	header("Content-Disposition:filename=" . iconv("UTF-8","GB18030","淘宝商城支付宝红包列表") . ".csv");	
	$out = $smarty->fetch('taobao/import_alipay_bonus_csv.htm');
	echo iconv("UTF-8","GB18030", $out);
	exit();	
} else {
    $size = 20;
    $page = $_REQUEST['page'] ? $_REQUEST['page'] : 1;
    $start = ($page - 1) * $size;
    
    $limit = "LIMIT $size";
    $offset = "OFFSET $start";
    
    $condition = get_condition();
    $count = get_taobao_alipay_bonus_count($condition);
    $alipay_bonus_list = get_taobao_alipay_bonus_list(" $condition $limit $offset");
    $pager = Pager($count, $size, $page, remove_param_in_url($_SERVER['REQUEST_URI'], 'info'));
    $smarty->assign("pager", $pager);
    $smarty->assign("alipay_bonus_list", $alipay_bonus_list);
	$smarty->display('taobao/import_alipay_bonus.htm');
}


/**
 * 添加红包信息
 *
 * @param array $bonus_list
 */
function add_taobao_alipay_bonus($bonus_list) {
    if (count($bonus_list) == 0) return;
    global $db;
    $b = array();
    foreach ($bonus_list as $bonus) {
        $buyer_alipay_no = mysql_escape_string($bonus['buyer_alipay_no']);
        $alipay_bonus = mysql_escape_string($bonus['alipay_bonus']);
    	$b[] = "'{$buyer_alipay_no}', '{$alipay_bonus}', 'OK', NOW() ";
    }
    $sql_bill = "(". join("),(", $b). ")";
    $sql = "INSERT INTO taobao_alipay_bonus 
        (buyer_alipay_no, alipay_bonus, status, created_time) 
        VALUES {$sql_bill} ";
    $db->query($sql);
}

/**
 * 修改红包信息
 *
 * @param array $bonus
 */
function save_taobao_alipay_bonus($bonus) {
    if (empty($bonus)) return;
    global $db;
    $buyer_alipay_no = mysql_escape_string($bonus['buyer_alipay_no']);
    $alipay_bonus = mysql_escape_string($bonus['alipay_bonus']);
    $sql = "UPDATE taobao_alipay_bonus 
        SET status = '{$bonus['status']}', update_time = NOW() 
        WHERE taobao_alipay_bonus_id = '{$bonus['taobao_alipay_bonus_id']}'";
    $db->query($sql);
}

/**
 * 获得支付宝红包列表
 *
 */
function get_taobao_alipay_bonus_list($condition=null) {
    global $db;
    $sql = "SELECT * FROM taobao_alipay_bonus WHERE 1 {$condition} ";
    $alipay_bonus_list = $db->getAll($sql);
    return $alipay_bonus_list;
}

/**
 * 获得支付宝红包数量
 *
 */
function get_taobao_alipay_bonus_count($condition) {
    global $db;
    $sql = "SELECT count(*) FROM taobao_alipay_bonus WHERE 1 {$condition} ";
    $count = $db->getOne($sql);
    return $count;
}

/**
 * 获得搜索条件
 *
 * @return unknown
 */
function get_condition() {
    extract($_REQUEST);
    $sql_condition = "";
    if ($act != 'search') {
        return $sql_condition;
    }
    if ($start) {
        $sql_condition .= " AND created_time >= '{$start}' ";
    }
    if ($end) {
        $sql_condition .= " AND created_time < '{$end} 23:59:59' ";
    }
    if (trim($search_text) != '') {
        $sql_condition .= " AND buyer_alipay_no LIKE '{$search_text}' ";
    }
    if ($status != 'ALL' && trim($status) != '') {
        $sql_condition .= " AND status = '{$status}' ";
    }
    return $sql_condition;
}