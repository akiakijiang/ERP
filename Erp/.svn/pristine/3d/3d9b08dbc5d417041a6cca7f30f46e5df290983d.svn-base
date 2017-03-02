<?php

/**
 * 红包相关函数库 
 * ========================
 * 本函数库已被邪恶的大鲵废除 All Hail Sinri Edogawa! 
 * “一代过去，一代又来，地却永远长存。” ———— 传道书
 */

/**
 * 发送保价红包
 *
 * @param string $user_name 发给的用户名
 * @param int $bouns_amount 红包金额
 * @param int $order_id 订单id
 * @param string $comment 红包生成原因
 * @param string $give_comment 发送红包的原因
 * @param int $party_id 组织
 * 
 * @return void
 */
function send_bonus($type = 'PRICE_PROTECTED', $user_name, $bouns_amount, $order_id, $comment, $give_comment, $party_id = PARTY_OUKU_MOBILE)
{    
    // 红包配置
    $gtc = array
    (
        'party_id'    => $party_id,                 // 启用组织
        'gtc_value'   => $bouns_amount,
        'gtc_state'   => 2,                         // 有时间限制的红包
        'gtc_stime'   => time(),                    // 一年的限制
        'gtc_etime'   => time() + 365 * 24 * 3600,
        'gtc_comment' => $comment,
        'gtc_type_id' => $type                      // 红包类型
    );
    // 首先生成红包配置
    $config = bonus_create_config($gtc);

    if ($config)
    {
        // 生成一个红包
        bonus_create($config, $order_id, $comment);

        // 发送一个红包
        $gt_codes = get_gt_code($config['gtc_id'], 1, $give_comment);

        // 将该红包分配给用户
        $gt_code = reset($gt_codes);
        give_gt_to_user($gt_code, $user_name);
        
        return $gt_code;
    }
}

/**
 * 生成红包
 *
 * @param array $bonus 红包配置
 * @param int $order_id 关联的订单
 * @param string $comment 红包生成的原因
 * @param string $give_comment 发送红包的原因
 * 
 * @return int|false 成功返回红包的id，失败返回false
 */
function bonus_create($config, $order_id = null, $comment = '', $give_comment = '')
{
    global $db, $ecs;
    
    static $admin_user_id;

    // 静态化当前用户的id, 防止每次调用都查询
    if (!isset($admin_user_id))
    {
        $sql = "SELECT userId FROM {$ecs->table('users')} WHERE user_name = '{$_SESSION['admin_name']}'";
        $admin_user_id = $db->getOne($sql, true);
        if (!$admin_user_id) { $admin_user_id = $_SESSION['admin_name']; }
    }

    // 红包从红包配置继承过来的属性：
    $gt = array
    (
        'gtc_id'      => $config['gtc_id'],
        'gtc_value'   => $config['gtc_value'],
        'gtc_stime'   => $config['gtc_stime'],
        'gtc_etime'   => $config['gtc_etime'],
        'gt_state'    => $config['gtc_state'],      // 红包状态
        'gtc_type_id' => $config['gtc_type_id'],    // 红包的类型
        'party_id'    => $config['party_id'],       // 红包的组织ID
    );

    // 红包自己的属性
    $gt['gt_code']       = bonus_genetate_code(1); // 红包code
    $gt['gt_ctime']      = time();                 // 红包生成时间
    $gt['gt_cip']        = ip2long(getRealIp());   // 红包生成ip
    $gt['gt_comment']    = empty($comment) ? $config['gtc_comment'] : $comment ;  // 红包的备注
    $gt['gt_creator']    = $admin_user_id;         // 管理员用户名或用户32位id
    $gt['give_comment']  = $give_comment;          // 获取这个红包的备注
    $gt['refer_id']      = $order_id;              // 关联的订单id

    // 创建红包
    $gt = array_map(array(&$db, 'escape_string'), $gt);
    $result = $db->autoExecute('`membership`.`ok_gift_ticket`', $gt, 'INSERT');
    if ($result)
    {
        return $db->insert_id();
    }
    else
    {
        return 0;
    }
}

/**
 * 生成指定数量的抵用券并同时发送给用户
 * 
 * @param array $gtc
 * @param array $gt, 需要的键 user_id , [refer_id], [give_comment], [gt_comment]
 * @param int $count
 */
function bonus_create_and_give_to_user_by_config($gtc, $gt, $count = 1)
{
    global $db, $ecs;
	 
    static $admin_user_id;
    // 静态化当前用户的id, 防止每次调用都查询
    if (!isset($admin_user_id)) {
        $sql = "SELECT userId FROM {$ecs->table('users')} WHERE user_name = '{$_SESSION['admin_name']}'";
        $admin_user_id = $db->getOne($sql, true);
        if (!$admin_user_id) { $admin_user_id = $_SESSION['admin_name']; }
    }
    
    $count = intval($count);
    if (!is_array($gt)) $gt = (array)$gt;
    if (strlen($gt['user_id']) != 32) {
    	return false;
    }
    
    // 红包私有属性，不需要外部提供的
    $timestamp = time();
    $_gt_private_props = array(
        // 从红包配置继承过来的属性：
        'party_id'    => $gtc['party_id'],       // 红包的组织ID
        'gtc_id'      => $gtc['gtc_id'],
        'gtc_value'   => $gtc['gtc_value'],
        'gtc_stime'   => $gtc['gtc_stime'],
        'gtc_etime'   => $gtc['gtc_etime'],
        'gt_state'    => $gtc['gtc_state'],      // 红包状态
        'gtc_type_id' => $gtc['gtc_type_id'],    // 红包的类型
        
        // 自己生成需要的属性
        'gt_ctime'       => $timestamp,             // 红包生成时间
        'gt_cip'         => ip2long(getRealIp()),   // 红包生成ip
        'gt_creator'     => $admin_user_id,         // 管理员用户名或用户32位id
        'give_time'      => $timestamp,
        'give_user'      => $admin_user_id,
        'draw_timestamp' => $timestamp,
    );
    
    $gt = array_merge($gt, $_gt_private_props);
    $gt = array_map(array(&$db, 'escape_string'), $gt);

    // 创建指定数量的抵用券
    for ($i = 0; $i < $count; $i++) {
    	$gt['gt_code'] = bonus_genetate_code(1); // 红包code
	    $result = $db->autoExecute('`membership`.`ok_gift_ticket`', $gt, 'INSERT', 'SILENT');
	    if ($result) { $ids[] = $db->insert_id(); }
    }
    return $ids;
}

/**
 * 创建红包配置
 *
 * @param array $gtc 红包配置
 * 
 * @return array|false 成功返回红包配置，失败返回false
 */
function bonus_create_config($gtc)
{
    global $db;
    
    $_default_gtc['site_id']  = 1;                  // 启用端编号 ，1为ouku
    $_default_gtc['party_id'] = PARTY_OUKU_MOBILE;  // 默认启用组织为欧酷手机组
    
    $table = '`membership`.`ok_gift_ticket_config`';
    $gtc = array_merge($_default_gtc, array_map(array(&$db, 'escape_string'), $gtc));  // 安全过滤
    $result = $db->autoExecute($table, $gtc, 'INSERT');
    if ($result)
    {
        $gtc_id = $db->insert_id();
        return $db->getRow("SELECT * FROM {$table} WHERE gtc_id = '{$gtc_id}'", true);
    }
    return false;
}

/**
 * 获得红包的配置
 *
 * @param string $bonus_id
 * @return array
 */
function get_bonus_config($bonus_id) {
    global $db;
    $sql = "SELECT c.*, t.* FROM `membership`.`ok_gift_ticket` gift
            INNER JOIN `membership`.`ok_gift_ticket_config` c ON gift.gtc_id = c.gtc_id
            INNER JOIN `membership`.`ok_gift_ticket_config_type` t ON gift.gtc_type_id = t.gtc_type_id 
            WHERE gift.gt_code = '{$bonus_id}'
            ";
    return $db->getRow($sql);
}

/**
 * 生成抵用券code，并保证唯一性
 * 
 * @param int 需要生成的code数
 * 
 * @return string|array  当需要生成的code数大于1时，返回数组
 */
function bonus_genetate_code($num = 1)
{
    while (1)
    {
        $seed  = mt_rand().'.oukoo';
        $md5   = md5($seed);
        $code  = substr($md5, 8, 16);
        $sql   = "SELECT COUNT(*) FROM `membership`.`ok_gift_ticket` WHERE gt_code = '{$code}'";
        $count = $GLOBALS['db']->getOne($sql, true);
        if ($count == 0) break;
    }

    return $code;
}

/**
 * 根据抵用券配置发送抵用券, 同时更新抵用券的发送人、发送原因、和发送时间
 * 返回指定数量的抵用券的编码
 *
 * @param int $gtc_id 抵用券配置ID
 * @param int $count 抵用券的数目
 * @param string $give_comment 发送原因
 * 
 * @return array 返回抵用券编码列表，非法则返回null
 */
function get_gt_code($gtc_id, $count, $give_comment)
{
    static $admin_user_id;

    // 静态化当前用户的id, 防止每次调用都查询
    if (!isset($admin_user_id))
    {
        $admin_user_id = $GLOBALS['db']->getOne("SELECT userId FROM {$GLOBALS['ecs']->table('users')} WHERE user_name = '{$_SESSION['admin_name']}'", true);
        if (!$admin_user_id) { $admin_user_id = $_SESSION['admin_name']; }
    }

    // 取得指定数量未使用的抵用卷
    $sql = "SELECT * FROM membership.ok_gift_ticket WHERE gtc_id = '{$gtc_id}' AND gt_state != 1 AND gt_state != 4 AND user_id = '' AND give_user = '' LIMIT {$count}";
    $gts = $GLOBALS['db']->getAllRefby($sql, array('gt_code'), $fields_value, $ref);
    if (count($gts) < $count) { return null; }  // 需要的数量与实际取得的数量不相等

    // 取得抵用券code
    $gt_codes = $fields_value['gt_code'];

    // 更新抵用券的发送人、发送原因和发送时间
    $sql = "UPDATE `membership`.`ok_gift_ticket` SET give_user = '{$admin_user_id}', give_comment = '{$give_comment}', give_time = '".time()."' WHERE %s";
    foreach (array_chunk($gt_codes, 25) as $group)
    {
        // 如果抵用券过多的话，防止SQL语句的in部分过长，需要分批更新
        $in = db_create_in($group, 'gt_code');
        if (!empty($in))
        {
            $GLOBALS['db']->query(sprintf($sql, $in));
        }
    }

    return $gt_codes;
}

/**
 * 将抵用券发送给用户，shop中红包列表
 *
 * @param string $gt_code 抵用券编码
 * @param string $user_name 用户名
 * 
 * @return boolean
 */
function give_gt_to_user($gt_code, $user_name)
{
    static $uids = array();

    // 取得红包
    $gt = $GLOBALS['db']->getRow("SELECT * FROM membership.ok_gift_ticket WHERE gt_code = '{$gt_code}'");

    // 取得用户id
    if (!isset($uids[$user_name]))
    {
        $uids[$user_name] = $GLOBALS['db']->getOne("SELECT userId FROM {$GLOBALS['ecs']->table('users')} WHERE user_name = '{$user_name}'");
    }

    if ($gt && $uids[$user_name])
    {
        $sql = "UPDATE membership.ok_gift_ticket SET user_id = '{$uids[$user_name]}', draw_timestamp = UNIX_TIMESTAMP() WHERE gt_code = '{$gt['gt_code']}'";
        return $GLOBALS['db']->query($sql);
    }
	
	return false;
}

/**
 * 根据抵用券配置返回抵用券数量
 *
 * @param unknown_type $gtc_id
 */
function get_gt_count($gtc_id)
{
    global $db;
    $sql = "SELECT COUNT(*) FROM membership.ok_gift_ticket WHERE gtc_id = '$gtc_id' AND gt_state != 1 AND gt_state != 4 AND user_id = '' AND give_user = ''";
    $count = $db->getOne($sql);
    return $count;
}

/**
 * 取得与某个订单关联的红包 (或者说是由某个订单而生成的红包)
 * 
 * @param int $order_id 订单id
 */
function get_orders_refer_bonus($order_id)
{
    $sql = "SELECT * FROM membership.ok_gift_ticket WHERE refer_id = '{$order_id}'";
    $list = $GLOBALS['db']->getAll($sql);

    if ($list) {
        $type_list = bonus_type_list();
        
        // 取得红包的使用情况
        foreach ($list as & $item) {
            $item['gtc_type_name'] = $type_list[$item['gtc_type_id']];
            
            // 拥有人
            if ($item['user_id']) {
                $item['user_name'] = $GLOBALS['db']->getOne("
                	SELECT user_name FROM {$GLOBALS['ecs']->table('users')} WHERE userId = '{$item['user_id']}'
                ");
            }
            
            // 被哪个订单使用
            if ($item['used_order_id']) {
                $item['used_order_sn'] = $GLOBALS['db']->getOne("
                	SELECT order_sn FROM {$GLOBALS['ecs']->table('order_info')} WHERE order_id = '{$item['used_order_id']}'
                ");
            }
        }
    }

    return $list;
}

/**
 * 红包类型列表
 * 
 * @return array
 */
function bonus_type_list()
{
    static $list;

    if (!isset($list))
    {
        $list = array();
        $sql = "SELECT gtc_type_id, type_name FROM `membership`.`ok_gift_ticket_config_type`";
        $types = $GLOBALS['db']->getAll($sql);
        foreach ($types as $item)
        {
            $list[$item['gtc_type_id']] = $item['type_name'];
        }
    }

    return $list;
}

/**
 * 返回抵用券的使用限制
 * 请保持和membership中的party model中的一致
 */
function bonus_use_category_limit() 
{
	return array(
	    PARTY_OUKU_MOBILE => array(
	        array('cate_id' => '0', 'cate_name' => '所有'),
	        array('cate_id' => '1', 'cate_name' => '手机'),
	        array('cate_id' => '2', 'cate_name' => '手机配件'),
	        array('cate_id' => '3', 'cate_name' => '小家电'),
	        array('cate_id' => '4', 'cate_name' => 'DVD'),        
	    ),
	    PARTY_OUKU_SHOES => array(
	        array('cate_id' => '0', 'cate_name' => '所有'),
	    ),
	);	
}


/**
 * 测试红包是否可以使用
 *
 * @param string $code
 * @param int $order_id
 * @return mixed 
 */
function test_bonus_id($code, $order_id) {
    global $db, $ecs;

    if (strlen($code) != 16) {
        return array('info' => '错误的红包代码', 'result' => false, 'value' => 0);
    }

    //获得 32 位的 userId
    $sql = "SELECT userId FROM {$ecs->table('users')} u INNER JOIN {$ecs->table('order_info')} o  ON u.user_id = o.user_id WHERE o.order_id = '{$order_id}' ";
    $userId = $db->getOne($sql);

    if (!$userId) {
        return array('info' => '找不到用户', 'result' => false, 'value' => 0);
    }

    //获得红包的信息
    $sql = "SELECT gt.*, gtc.gtc_data FROM membership.ok_gift_ticket gt
            INNER JOIN membership.ok_gift_ticket_config gtc ON gt.gtc_id = gtc.gtc_id
            WHERE gt.gt_code = '$code' ";
    $giftTicket = $db->getRow($sql);

    //如果红包不存在
    if (!$giftTicket) {
        return array('info' => '红包不存在', 'result' => false, 'value' => 0);
    }

    if ($giftTicket['gt_state'] == 1) {
        // 是否被禁用
        return array('info' => '红包被禁用', 'result' => false, 'value' => 0);
    }else if ($giftTicket['gt_state'] == 4) {
        // 是否已使用
        return array('info' => '红包已使用', 'result' => false, 'value' => 0);
    }else if ($giftTicket['gt_state'] == 2 && !($giftTicket['gtc_stime'] < time() && time() < $giftTicket['gtc_etime'] )) {
        // 检测有时间限制的情形
        return array('info' => "红包有时间限制，不在可以使用的时间段".date("Y-m-d H:i:s", $giftTicket['gtc_stime']) . " ".date("Y-m-d H:i:s", $giftTicket['gtc_etime']) , 'result' => false, 'value' => 0);
    }

    //查看红包是否发给某个用户
    if ($giftTicket['user_id'] != '' && $giftTicket['user_id'] != $userId) {
        return array('info' => "红包不是该用户的", 'result' => false, 'value' => 0);
    }

    //检查红包限制
    $gtc_data = @unserialize($giftTicket['gtc_data']);

    // Tudo: 不同的组织有不同的分类
    $for_top_cat_id = $gtc_data['for_top_cat_id'];
    $goods_amount_limit = $gtc_data['goods_amount_limit'];

    if ($goods_amount_limit > 0) {
        require_once(ROOT_PATH . 'admin/function.php');
        
        $goods_list = getOrderGoods($order_id);
        if ($for_top_cat_id) {
            $cat_sub_total = 0;
            foreach ($goods_list as $goods) {
                if ($for_top_cat_id == get_real_cat_id($goods['top_cat_id'], $goods['cat_id'])) $cat_sub_total += $goods['total_price'];
            }
            if ($cat_sub_total < $goods_amount_limit) {
                return array('info' => "红包在商品购买 " .get_real_cat_name($for_top_cat_id) ." 金额达到 {$goods_amount_limit} 才能使用 ", 'result' => false, 'value' => 0);
            }
        } else {
            $cat_sub_total = 0;
            foreach ($goods_list as $goods) {
                $cat_sub_total += $goods['total_price'];
            }
            if ($cat_sub_total < $goods_amount_limit) {
                return array('info' => "红包在商品购买金额达到 {$goods_amount_limit} 才能使用 ", 'result' => false, 'value' => 0);
            }
        }
    }

    return array('info' => "红包金额".$giftTicket['gtc_value'], 'result' => true, 'value' => $giftTicket['gtc_value'], 'giftTicket' => $giftTicket, 'userId' => $userId);
}

/**
 * 使用红包
 *
 * @param  string $code
 * @param int $order_id
 * @return mixed
 */
function use_bonus_id($code, $order_id) {
    global $db, $ecs;

    // 先检查一下红包是否能够用
    $test_result = test_bonus_id($code, $order_id);
    if (!$test_result['result']) {
        return $test_result;
    }

    $giftTicket = $test_result['giftTicket'];
    $userId = $test_result['userId'];

    // 使用抵用券
    $sql = "UPDATE membership.ok_gift_ticket SET gt_state = 4, used_timestamp = UNIX_TIMESTAMP(), used_order_id = '{$order_id}', used_user_id = '{$userId}' WHERE gt_code  = '{$code}'";
    $db->query($sql);

    // 写入log
    require_once(ROOT_PATH."includes/lib_common.php");
    $iUseType = 4; #购买使用
    $sUserIp  = real_ip();
    $squoteId = $userId."|".date("Y-m-d H:i:s")."|购买使用".$code."|Ip".$sUserIp;
    $sql = "INSERT INTO membership.ok_gift_ticket_log (gtc_id, gt_id, user_id, gtl_utime, gtl_uip, gtl_type, gtl_fk_id, gtl_comment) VALUES ('{$giftTicket['gtc_id']}','{$giftTicket['gt_id']}','$userId', NOW(),'" .ip2long($sUseIp) . "','$iUseType','$squoteId','{$_SESSION['admin_name']} 在后台使用') ";
    $db->query($sql);
    return $test_result;
}