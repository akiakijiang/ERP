<?php die("是时候封印这群妖怪了。铲平忌术部宣。(涉及到古老的欧币系统，已被停用。)");
 
/**
 * 发送抵用券，一步式生成和发送抵用券
 * 
 * @copyright 2009 ouku.com
 */

define('IN_ECS', true);
require_once('includes/init.php');
require_once('includes/lib_common.php');
require_once('includes/lib_bonus.php');   // 红包库
admin_priv('bonus_manage');

require_once(ROOT_PATH . 'includes/helper/array.php');   // 数组助手
require_once(ROOT_PATH . 'includes/helper/validator.php');  // 验证助手


if (isset($_REQUEST['message'])) {
    $smarty->assign('message', $_REQUEST['message']);
}


// 基础数据
$party_id_list = array(PARTY_OUKU_MOBILE => '手机网', PARTY_OUKU_SHOES => '鞋子网');
$gtc_type_list = bonus_type_list();
$gtc_state_list = $GLOBALS['_CFG']['ms']['gtc_state']; unset($gtc_state_list[1]);

 
// 处理ajax请求
if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    $act = isset($_REQUEST['act']) ? $_REQUEST['act'] : NULL ;
    switch ($act) {
        // 更改party后更改限制选项
    	case 'change_party' :
            $party_id = isset($_REQUEST['party_id']) ? $_REQUEST['party_id'] : PARTY_OUKU_MOBILE ;
            $gtc_data_limit = bonus_use_category_limit();
            $response = isset($gtc_data_limit[$party_id]) ? $gtc_data_limit[$party_id] : array();
            echo json_encode($response);
            break;
        
        // 更改抵用券类型后更改
        case 'change_type' :
            $type_id = isset($_REQUEST['type_id']) ? $_REQUEST['type_id'] : false ;
            if ($type_id) {
                $sql = "SELECT refer_id_required FROM `membership`.`ok_gift_ticket_config_type` WHERE gtc_type_id = '{$type_id}'";
                $result = $db->getOne($sql);
                echo $result;
            }
            break;
    		
        // 检查用户是否存在
    	case 'check_user' :
            $result = gt_user_check($_REQUEST['users']);
            echo json_encode($result);
            break;
    		
        // 检查订单
    	case 'check_order' :
            $result = gt_order_check($_REQUEST['orders']);
            echo json_encode($result);
            break;
    }

    exit;
}


// 表单提交处理
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $gtc = isset($_POST['gtc']) ? $_POST['gtc'] : array();  // 抵用券配置数据
    $gt = isset($_POST['gt']) ? $_POST['gt'] : array();     // 抵用券数据
	
    do {
        // 验证数据
        if (empty($gtc) || empty($gt)) {
            $smarty->assign('message', '没有提交数据');
            break;
        }
		
        // 过滤空属性
        Helper_Array::removeEmpty($gtc);
        Helper_Array::removeEmpty($gt);
        
        if (empty($gtc['party_id'])) {
            $smarty->assign('message', '请选择红包启用的网站');
            break;
        }
        
        if (empty($gtc['gtc_type_id'])) {
            $smarty->assign('message', '请选择红包类型');
            break;
        }
        
        if (empty($gtc['gtc_state'])) {
            $smarty->assign('message', '请选择红包状态');
            break;
        }
        
        if ($gtc['gtc_state'] == 2) {
            if (! Helper_Validator::validate($gtc['gtc_daw_stime'], 'is_datetime')) {
                $smarty->assign('message', '请填写正确的限制使用时间');
                break;
            }
	        
            if (! Helper_Validator::validate($gtc['gtc_daw_etime'], 'is_date')) {
                $smarty->assign('message', '请填写正确的限制使用时间');
                break;
            }
	        
            $gtc['gtc_stime'] = strtotime($gtc['gtc_daw_stime']);
            $gtc['gtc_etime'] = strtotime($gtc['gtc_daw_etime']);
            if ($gtc['gtc_stime'] >= $gtc['gtc_etime']) {
                $smarty->assign('message', '限制的期末时间不能大于期初时间');
                break;
            }
        } else {
            $gtc['gtc_stime'] = 0;
            $gtc['gtc_etime'] = 0;
        }
  
        if (! Helper_Validator::validate($gtc['gtc_value'], 'is_float')) {
            $smarty->assign('message', '请填写正确的抵用券金额'); 
            break;
        }
        
        if (! Helper_Validator::validate($gt['number'], 'is_int')) {
            $smarty->assign('message', '请填写正确的抵用券数量'); 
            break;
        }
        
        // 检查抵用券配置
        $type = $db->getRow("SELECT * FROM membership.ok_gift_ticket_config_type WHERE gtc_type_id = '{$gtc['gtc_type_id']}'");
        if (!$type) {
            $smarty->assign('message', '非法的抵用券类型'); 
            break;
        }
        
        $check_type = isset($_REQUEST['check_type']) ? $_REQUEST['check_type'] : 'user';
		
        // 需填写关联订单号
        if ($check_type == 'order') {
            if (empty($gt['order_sn'])) {
                $smarty->assign('message', '请填写订单号'); 
                break;
            }
            // 检查用户是否存在
            $users = gt_order_check($gt['order_sn']);
            if (!empty($users['inexistent'])) {
                $smarty->assign('message', '订单('. implode('，', $users['inexistent']) . ')不存在，不能发送');
                break;
            }
            if (empty($users['exists'])) {
                $smarty->assign('message', '没有要发送给的订单');
                break;
            }
        }
        // 需填写用户
        else {
            if (empty($gt['user_name'])) {
                $smarty->assign('message', '请填写要发送给的用户名');
                break;
            }
            // 检查用户是否存在
            $users = gt_user_check($gt['user_name']);
            if (!empty($users['inexistent'])) {
                $smarty->assign('message', '用户('. implode('，', $users['inexistent']) . ')不存在，不能发送');
                break;
            }
            if (empty($users['exists'])) {
                $smarty->assign('message', '没有要发送给的用户');
                break;
            }
        }
        
        // 生成抵用券配置
        if (isset($gtc['gtc_date_for_top_cat_id']) || isset($gtc['gtc_data_goods_amount_limit'])) {
            $gtc['gtc_data'] = serialize(array(
                'for_top_cat_id' => $gtc['gtc_data_for_top_cat_id'], 
                'goods_amount_limit'=> $gtc['gtc_data_goods_amount_limit']
            ));
        }
        $gtc['gtc_comment'] = $gt['gt_comment'];
        $config = bonus_create_config($gtc);
            
        // 生成并发送抵用券
        if ($config) {
            $count = $gt['number']; 
        	
            foreach ($users['exists'] as $user) {
                $gt['user_id'] = $user['userId'];
                if ($type['refer_id_required'] == 'Y') {
                    $gt['refer_id'] = $user['order_id'];
                }
                $gt_ids = bonus_create_and_give_to_user_by_config($config, $gt, $count);
                
                // 发送短信给用户
                $sql = "
                    SELECT o.mobile 
                    FROM {$ecs->table('order_info')} o 
                        INNER JOIN {$ecs->table('users')} u ON o.user_id = u.user_id 
                    WHERE u.userId = '{$user['userId']}' AND o.mobile != ''
                    ORDER BY o.order_id DESC LIMIT 1
                ";
                $mobile = $db->getOne($sql);
                if (!$mobile) {
                    $mobile = $db->getOne("SELECT user_mobile FROM {$ecs->table('users')} WHERE userId = '{$user['userId']}'");
                }
                if ($mobile) {
                    $msg_vars = array('msg_gtc_value' => $config['gtc_value']);
                    erp_send_message('gt_send', $msg_vars, $gtc['party_id'], NULL, $mobile);
                }
            }
        }
        // 生成抵用券配置失败了哦
        else {
            $smarty->assign('message', '生成抵用券配置失败咯');
            break;
        }
        
        header("Location: gt_give_direct.php?message=". urlencode('抵用券发放成功'));
        exit;
        
    } while (false);
}
// 表单初始数据
else {
    $gtc = array(
        'gtc_daw_stime' => date('Y-m-d H:i:s'),
        'gtc_daw_etime' => date('Y-m-d H:i:s', strtotime('next year')),
    );
    $gt = array(
        'number' => 1,
    );
}

$smarty->assign('gtc', $gtc);
$smarty->assign('gt', $gt);

$smarty->assign('gtc_state_list', $gtc_state_list);
$smarty->assign('party_id_list', $party_id_list);
$smarty->assign('bonus_type_list', $gtc_type_list);
$smarty->display('oukooext/gt_give_direct.htm');


/**
 * 判断用户是否存在
 * 
 * @param mixed  $users 
 * 
 * @return array   返回存在的用户和不存在的用户
 */
function gt_user_check($users)
{
    global $db, $ecs;
    
    // 返回的结果，存在的用户和不存在的用户
    $exists = array();
    $inexistent = array();
    
    // 处理用户
    if (!empty($users) && !is_array($users)) {
        $result = preg_match_all("/[^,^\s]+/", $users, $matches);
        $users = ($result > 0) ? $matches[0] : array() ;
        $users = array_filter(array_map('trim', $users), 'strlen'); 
    }
    if (is_array($users) && count($users) > 0) {
        $users = array_unique($users); // 移除重复的用户名  
    } else {
    	return  compact('exists', 'inexistent');
    }

    $exclude = $users;
    $sql = "SELECT `userId`, `user_name` FROM {$ecs->table('users')} WHERE `user_name` %s";
    $result = $db->getAll(sprintf($sql, db_create_in($users)));
    if ($result) {
        // 所有用户都存在
        if (count($users) == count($result)) {
            $exists = $result;        
        }
        // 有部分用户不存在，则循环匹配
        else {
            foreach ($result as $u) {
                // 用户存在
                if (in_array($u['user_name'], $users)) {
                    $exists[] = $u;
                    unset($exclude[array_search($u['user_name'], $exclude)]);
                }
            }
            
            // 剩下的是不存在的用户
            if (count($exclude) > 0) {
            	sort($exclude);
                $inexistent = $exclude;   
            }
        }
    }
    // 所有用户都不存在
    else {
        $inexistent = $users;    
    }
    
    unset($result, $exclude);
    return compact('exists', 'inexistent'); 
}

/**
 * 检查订单及订单对应的用户是否存在，并查询出订单的用户ID
 * 
 * @return array   返回存在的用户和不存在的用户
 */
function gt_order_check($orders)
{
    global $db, $ecs;
    
    // 返回的结果，存在的用户和不存在的用户
    $exists = array();
    $inexistent = array();
    
    // 处理用户
    if (!empty($orders) && !is_array($orders)) {
        $result = preg_match_all("/[^,^\s]+/", $orders, $matches);
        $orders = ($result > 0) ? $matches[0] : array() ;
        $orders = array_filter(array_map('trim', $orders), 'strlen'); 
    }
    if (is_array($orders) && count($orders) > 0) {
        $orders = array_unique($orders); // 移除重复的订单号  
    } else {
        return compact('exists', 'inexistent');
    }
    
    $exclude = $orders;
    $sql = "
        SELECT o.order_id, o.order_sn, u.userId, u.user_name 
        FROM {$ecs->table('order_info')} AS o INNER JOIN {$ecs->table('users')} AS u ON u.user_id = o.user_id
        WHERE o.`order_sn` %s
    ";
    $result = $db->getAll(sprintf($sql, db_create_in($orders)));
    if ($result) {
        // 所有的都存在
        if (count($orders) == count($result)) {
            $exists = $result;        
        }
        // 有部分不存在，则循环匹配
        else {
            foreach ($result as $item) {
                // 用户存在
                if (in_array($item['order_sn'], $orders)) {
                    $exists[] = $item;
                    unset($exclude[array_search($item['order_sn'], $exclude)]);
                }
            }
            
            // 剩下的是不存在的
            if (count($exclude) > 0) {
                sort($exclude);
                $inexistent = $exclude;   
            }
        }
    }
    // 所有用户都不存在
    else {
        $inexistent = $orders;    
    }
    
    unset($result, $exclude);
    return compact('exists', 'inexistent'); 
}

?>
