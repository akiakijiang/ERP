<?php

/**
 * ECSHOP 用户相关函数库
 * ============================================================================
 * 版权所有 (C) 2005-2007 北京亿商互动科技发展有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com
 * ----------------------------------------------------------------------------
 * 这是一个免费开源的软件；这意味着您可以在不用于商业目的的前提下对程序代码
 * 进行修改、使用和再发布。
 * ============================================================================
 * @author:     Weber Liu <weberliu@hotmail.com>
 * @version:    v2.1
 * ---------------------------------------------
 * $Author: wj $
 * $Date: 2007-01-19 15:31:01 +0800 (五, 19  1月 2007) $
 * $Id: lib_profile.php 4369 2007-01-19 07:31:01Z wj $
 */
if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

/**
 *  获取指定用户的收藏商品列表
 *
 * @access  public
 * @param   int     $user_id        用户ID
 * @param   int     $num            列表最大数量
 * @param   int     $start          列表其实位置
 *
 * @return  array   $arr
 */
function get_collection_goods($user_id, $num = 10, $start = 0)
{
    $sql = ' select * from ((SELECT g.goods_id, g.goods_name, g.market_price, g.shop_price AS org_price, g.goods_number ,'.
                "g.shop_price, g.integral, ".
                "c.rec_id, 0 as bj_store_goods_id, g.is_on_sale, '' as name, 0 as store_id, '' as status, 0 as price, '' as subtitle " .
            ' FROM ' . $GLOBALS['ecs']->table('collect_goods') . ' AS c, ' .
            $GLOBALS['ecs']->table('goods') . " AS g ".
            " WHERE c.user_id = '$user_id' and g.goods_id = c.goods_id  and c.bj_store_goods_id = 0) UNION ALL ".
            '  (SELECT g.goods_id, g.goods_name, g.market_price, g.shop_price AS org_price, g.goods_number ,'.
                "g.shop_price, g.integral, ".
                "c.rec_id, sg.store_goods_id as bj_store_goods_id, g.is_on_sale, s.name, s.store_id, sg.status, sg.price, sg.subtitle " .
            ' FROM ' . $GLOBALS['ecs']->table('collect_goods') . ' AS c, ' .
            $GLOBALS['ecs']->table('goods') . " AS g , bj_store_goods as sg, bj_store as s ".
            " WHERE c.user_id = '$user_id' and g.goods_id = sg.goods_id  and c.bj_store_goods_id = sg.store_goods_id and sg.store_id =s.store_id)) as X order by X.rec_id DESC ";


//			echo($sql);
    $res = $GLOBALS['db'] -> selectLimit($sql, $num, $start);
    $goods_list = array();
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $goods_list[$row['rec_id']]['rec_id']      		 = $row['rec_id'];
        $goods_list[$row['rec_id']]['bj_store_goods_id'] = $row['bj_store_goods_id'];
        $goods_list[$row['rec_id']]['bj_store_name'] = $row['name'];
        $goods_list[$row['rec_id']]['bj_store_goods_price'] = price_format($row['price']);
        $goods_list[$row['rec_id']]['bj_store_id'] = $row['store_id'];
        $goods_list[$row['rec_id']]['subtitle'] = $row['subtitle'];
		$goods_list[$row['rec_id']]['goods_number']      = $row['goods_number'];
        $goods_list[$row['rec_id']]['goods_id']      = $row['goods_id'];
        $goods_list[$row['rec_id']]['goods_name']    = $row['goods_name'];
        $goods_list[$row['rec_id']]['market_price']  = price_format($row['market_price']);
        $goods_list[$row['rec_id']]['shop_price']    = price_format($row['shop_price']);
        if ($row["bj_store_goods_id"] == 0) {
        	$goods_list[$row['rec_id']]['return_point']  = getProductReturnPoint($row['integral'], $_SESSION['rank_id']);
      		$goods_list[$row['rec_id']]['is_on_sale']        = $row['is_on_sale'];
        } else {
        	$goods_list[$row['rec_id']]['return_point']  = 0;
        	$goods_list[$row['rec_id']]['is_on_sale']     = $row['status'] == "ON_SALE" ? 1 : 0;
        }
	}
//	pp($goods_list);
    return $goods_list;
}

/**
 *  查看此商品是否已进行过缺货登记
 *
 * @access  public
 * @param   int     $user_id        用户ID
 * @param   int     $goods_id       商品ID
 *
 * @return  int
 */
function get_booking_rec($user_id, $goods_id)
{
    $sql = 'SELECT COUNT(*) '.
           'FROM ' .$GLOBALS['ecs']->table('booking_goods').
           "WHERE user_id = '$user_id' AND goods_id = '$goods_id' AND is_dispose = 0";

    return $GLOBALS['db']->getOne($sql);
}

/**
 *  获取指定用户的留言
 *
 * @access  public
 * @param   int     $user_id        用户ID
 * @param   int     $user_name      用户名
 * @param   int     $num            列表最大数量
 * @param   int     $start          列表其实位置
 * @return  array   $msg            留言及回复列表
 */
function get_message_list($user_id, $user_name, $num, $start)
{
    /* 获取留言数据 */
    $msg = array();
    $sql = "SELECT * FROM " .$GLOBALS['ecs']->table('feedback').
           " WHERE parend_id = 0 AND user_id = '$user_id' AND user_name = '$user_name' ORDER BY msg_time DESC";
    $res = $GLOBALS['db']->SelectLimit($sql, $num, $start);
    while ($rows = $GLOBALS['db']->fetchRow($res))
    {
        /* 取得留言的回复 */
        $reply = array();
        $sql   = "SELECT user_name, user_email, msg_time, msg_content".
                 " FROM " .$GLOBALS['ecs']->table('feedback') .
                 " WHERE parend_id = '" . $rows['msg_id'] . "'";
        $reply = $GLOBALS['db']->getRow($sql);

        if ($reply)
        {
            $msg[$rows['msg_id']]['re_user_name']   = $reply['user_name'];
            $msg[$rows['msg_id']]['re_user_email']  = $reply['user_email'];
            $msg[$rows['msg_id']]['re_msg_time']    = date($GLOBALS['_CFG']['time_format'], $reply['msg_time']);
            $msg[$rows['msg_id']]['re_msg_content'] = nl2br(htmlspecialchars($reply['msg_content']));
        }

        $msg[$rows['msg_id']]['msg_content'] = nl2br(htmlspecialchars($rows['msg_content']));
        $msg[$rows['msg_id']]['msg_time']    = date($GLOBALS['_CFG']['time_format'], $rows['msg_time']);
        $msg[$rows['msg_id']]['msg_type']    = $GLOBALS['_LANG']['type'][$rows['msg_type']];
        $msg[$rows['msg_id']]['msg_title']   = nl2br(htmlspecialchars($rows['msg_title']));
        $msg[$rows['msg_id']]['message_img'] = $rows['message_img'];
    }

    return $msg;
}

/**
 *  添加留言函数
 *
 * @access  public
 * @param   array       $message
 *
 * @return  boolen      $bool
 */
/* 屏蔽文件上传操作 by ychen 2009/07/31
function add_message($message)
{
    if ($message['upload'])
    {
        $img_name = upload_file($_FILES['message_img'], 'feedbackimg');

        if ($img_name === false)
        {
            return false;
        }
    }
    else
    {
        $img_name = '';
    }

    if (empty($message['msg_title']))
    {
        $GLOBALS['err']->add($GLOBALS['_LANG']['msg_title_empty']);

        return false;
    }

    $sql = "INSERT INTO " . $GLOBALS['ecs']->table('feedback') ." VALUES ('', 0, '$message[user_id]', '$message[user_name]', '$message[user_email]', '$message[msg_title]', '$message[msg_type]', '$message[msg_content]', '".time()."', '$img_name', 0)";
    $GLOBALS['db']->query($sql);

    return true;
}
*/
/**
 *  获取用户的tags
 *
 * @access  public
 * @param   int         $user_id        用户ID
 *
 * @return array        $arr            tags列表
 * 
 * TODO 废弃的函数
 */
function get_user_tags($user_id = 0)
{
    if (empty($user_id))
    {
        $GLOBALS['error_no'] = 1;

        return false;
    }

    $tags = get_tags(0, $user_id);

    if (!empty($tags))
    {
        color_tag($tags);
    }

    return $tags;
}

/**
 *  验证性的删除某个tag
 *
 * @access  public
 * @param   int         $tag_words      tag的ID
 * @param   int         $user_id        用户的ID
 *
 * @return  boolen      bool
 * 
 * TODO 废弃的函数
 */
function delete_tag($tag_words, $user_id)
{
     $sql = "DELETE FROM ".$GLOBALS['ecs']->table('tag').
            " WHERE tag_words = '$tag_words' AND user_id = '$user_id'";

     return $GLOBALS['db']->query($sql);
}

/**
 *  获取某用户的缺货登记列表
 *
 * @access  public
 * @param   int     $user_id        用户ID
 * @param   int     $num            列表最大数量
 * @param   int     $start          列表其实位置
 *
 * @return  array   $booking
 */
function get_booking_list($user_id, $num, $start)
{
    $booking = array();
    $sql = "SELECT bg.rec_id, bg.goods_id, bg.goods_number, bg.booking_time, bg.dispose_note, g.goods_name ".
           "FROM " .$GLOBALS['ecs']->table('booking_goods')." AS bg , " .$GLOBALS['ecs']->table('goods')." AS g". " WHERE bg.goods_id = g.goods_id AND bg.user_id = '$user_id' ORDER BY bg.booking_time DESC";
    $res = $GLOBALS['db']->SelectLimit($sql, $num, $start);

    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        if (empty($row['dispose_note']))
        {
            $row['dispose_note'] = 'N/A';
        }
        $booking[] = array('rec_id'       => $row['rec_id'],
                           'goods_name'   => $row['goods_name'],
                           'goods_number' => $row['goods_number'],
                           'booking_time' => date($GLOBALS['_CFG']['date_format'], $row['booking_time']),
                           'dispose_note' => $row['dispose_note'],
                           'url'          => build_uri('goods', array('gid'=>$row['goods_id']), $row['goods_name']));
    }

    return $booking;
}

/**
 *  获取某用户的缺货登记列表
 *
 * @access  public
 * @param   int     $goods_id    商品ID
 *
 * @return  array   $info
 */
function get_goodsinfo($goods_id)
{
    $info = array();
    $sql  = "SELECT goods_name FROM " .$GLOBALS['ecs']->table('goods'). " WHERE goods_id = '$goods_id'";

    $info['goods_name']   = $GLOBALS['db']->getOne($sql);
    $info['goods_number'] = 1;
    $info['id']           = $goods_id;

    if (!empty($_SESSION['user_id']))
    {
        $row = array();
        $sql = "SELECT ua.consignee, ua.email, ua.tel, ua.mobile ".
               "FROM ".$GLOBALS['ecs']->table('user_address')." AS ua, ".$GLOBALS['ecs']->table('users')." AS u".
               " WHERE u.address_id = ua.address_id AND u.user_id = '$_SESSION[user_id]'";
        $row = $GLOBALS['db']->getRow($sql) ;
        $info['consignee'] = empty($row['consignee']) ? '' : $row['consignee'];
        $info['email']     = empty($row['email'])     ? '' : $row['email'];
        $info['tel']       = empty($row['mobile'])    ? (empty($row['tel']) ? '' : $row['tel']) : $row['mobile'];
    }

    return $info;
}

/**
 *  验证删除某个收藏商品
 *
 * @access  public
 * @param   int         $booking_id     缺货登记的ID
 * @param   int         $user_id        会员的ID
 * @return  boolen      $bool
 */
function delete_booking($booking_id, $user_id)
{
    $sql = 'DELETE FROM ' .$GLOBALS['ecs']->table('booking_goods').
           " WHERE rec_id ='$booking_id' AND user_id = '$user_id'";
    return $GLOBALS['db']->query($sql);
}

/**
 * 添加缺货登记记录到数据表
 * @access  public
 * @param   array $booking
 *
 * @return void
 */
function add_booking($booking)
{
    $sql = "INSERT INTO " .$GLOBALS['ecs']->table('booking_goods'). " VALUES ('', '$_SESSION[user_id]', '$booking[email]', '$booking[linkman]', '$booking[tel]', '$booking[goods_id]', '$booking[desc]', '$booking[goods_amount]', '".time()."', 0, '', 0, '')";
    $GLOBALS['db']->query($sql) or die ($GLOBALS['db']->errorMsg());

    return $GLOBALS['db']->insert_id();
}

/**
 * 插入会员账目明细
 *
 * @access  public
 * @param   array     $surplus  会员余额信息
 * @param   string    $amount   余额
 *
 * @return  int
 * 
 * TODO 废弃的函数
 */
function insert_user_account($surplus, $amount)
{
    $sql = 'INSERT INTO ' .$GLOBALS['ecs']->table('user_account').
           ' (user_id, admin_user, amount, add_time, paid_time, admin_note, user_note, process_type, payment, is_paid)'.
            " VALUES ('$surplus[user_id]', '', '$amount', '".time()."', 0, '', '$surplus[user_note]', '$surplus[process_type]', '$surplus[payment]', 0)";
    $GLOBALS['db']->query($sql);

    return $GLOBALS['db']->insert_id();
}

/**
 * 更新会员账目明细
 *
 * @access  public
 * @param   array     $surplus  会员余额信息
 *
 * @return  int
 * 
 * TODO 废弃的函数
 */
function update_user_account($surplus)
{
    $sql = 'UPDATE ' .$GLOBALS['ecs']->table('user_account'). ' SET '.
           "amount     = '$surplus[amount]', ".
           "user_note  = '$surplus[user_note]', ".
           "payment    = '$surplus[payment]' ".
           "WHERE id   = '$surplus[rec_id]'";
    $GLOBALS['db']->query($sql);

    return $surplus['rec_id'];
}

/**
 * 将支付LOG插入数据表
 *
 * @access  public
 * @param   integer     $id         订单编号
 * @param   float       $amount     订单金额
 * @param   integer     $type       支付类型
 * @param   integer     $is_paid    是否已支付
 *
 * @return  int
 */
function insert_pay_log($id, $amount, $type = PAY_SURPLUS, $is_paid = 0)
{
    $sql = 'INSERT INTO ' .$GLOBALS['ecs']->table('pay_log')." (order_id, order_amount, order_type, is_paid)".
            " VALUES  ('$id', '$amount', '$type', '$is_paid')";
    $GLOBALS['db']->query($sql);

     return $GLOBALS['db']->insert_id();
}

/**
 * 取得上次未支付的pay_lig_id
 *
 * @access  public
 * @param   array     $surplus_id  余额记录的ID
 * @param   array     $pay_type    支付的类型：预付款/订单支付
 *
 * @return  int
 */
function get_paylog_id($surplus_id, $pay_type = PAY_SURPLUS)
{
    $sql = 'SELECT log_id FROM' .$GLOBALS['ecs']->table('pay_log').
           " WHERE order_id = '$surplus_id' AND order_type = '$pay_type' AND is_paid = 0";

    return $GLOBALS['db']->getOne($sql);
}

/**
 * 根据ID获取当前余额操作信息
 *
 * @access  public
 * @param   int     $surplus_id  会员余额的ID
 *
 * @return  int
 * 
 * TODO 废弃的函数
 */
function get_surplus_info($surplus_id)
{
    $sql = 'SELECT * FROM ' .$GLOBALS['ecs']->table('user_account').
           " WHERE id = '$surplus_id'";

    return $GLOBALS['db']->getRow($sql);
}

/**
 * 取得已安装的支付方式(其中不包括线下支付的)
 * @return  array   已安装的配送方式列表
 * 
 * TODO 废弃的函数
 */
function get_online_payment_list()
{
    $sql = 'SELECT pay_id, pay_name, pay_fee, pay_desc ' .
            'FROM ' . $GLOBALS['ecs']->table('payment') .
            " WHERE enabled = 1 AND is_cod <> 1";

    return $GLOBALS['db']->getAll($sql);
}

/**
 * 查询会员余额的操作记录
 *
 * @access  public
 * @param   int     $user_id    会员ID
 * @param   int     $num        每页显示数量
 * @param   int     $start      开始显示的条数
 * @return  array
 * 
 * TODO 废弃的函数
 */
function get_account_log($user_id, $num, $start)
{
    $account_log = array();
    $sql = 'SELECT * FROM ' .$GLOBALS['ecs']->table('user_account').
           " WHERE user_id = '$user_id' ORDER BY add_time DESC";
    $res = $GLOBALS['db']->selectLimit($sql, $num, $start);

    if ($res)
    {
        while ($rows = $GLOBALS['db']->fetchRow($res))
        {
            $rows['add_time']         = date($GLOBALS['_CFG']['date_format'], $rows['add_time']);
            $rows['admin_note']       = nl2br(htmlspecialchars($rows['admin_note']));
            $rows['short_admin_note'] = ($rows['admin_note'] > '') ? substr($rows['admin_note'], 0, 15).'...' : 'N/A';
            $rows['user_note']        = nl2br(htmlspecialchars($rows['user_note']));
            $rows['short_user_note']  = ($rows['user_note'] > '') ? substr($rows['user_note'], 0, 15).'...' : 'N/A';
            $rows['pay_status']       = ($rows['is_paid'] == 0) ? $GLOBALS['_LANG']['un_confirm'] : $GLOBALS['_LANG']['is_confirm'];

            /* 会员的操作类型： 预付款，退款申请，购买商品，取消订单*/
            if ($rows['process_type'] == 0)
            {
                $rows['type'] = $GLOBALS['_LANG']['surplus_type_0'];
            }
            elseif ($rows['process_type'] == 1)
            {
                $rows['type'] = $GLOBALS['_LANG']['surplus_type_1'];
            }
            elseif ($rows['process_type'] == 2)
            {
                $rows['type'] = $GLOBALS['_LANG']['surplus_type_2'];
            }
            else
            {
                $rows['type'] = $GLOBALS['_LANG']['surplus_type_3'];
            }

            /* 支付方式的ID */
            $sql = 'SELECT pay_id FROM ' .$GLOBALS['ecs']->table('payment').
                   " WHERE pay_name = '$rows[payment]' AND enabled = 1";
            $pid = $GLOBALS['db']->getOne($sql);

            /* 如果是预付款而且还没有付款, 允许付款 */
            if (($rows['is_paid'] == 0) && ($rows['process_type'] == 0))
            {
                $rows['handle'] = '<a href="user.php?act=pay&id='.$rows['id'].'&pid='.$pid.'">'.$GLOBALS['_LANG']['pay'].'</a>';
            }

            $account_log[] = $rows;
        }

        return $account_log;
    }
    else
    {
         return false;
    }
}

/**
 *  删除未确认的会员帐目信息
 *
 * @access  public
 * @param   int         $rec_id     会员余额记录的ID
 * @param   int         $user_id    会员的ID
 * @return  boolen
 * 
 * TODO 废弃的函数
 */
function del_user_account($rec_id, $user_id)
{
    $sql = 'DELETE FROM ' .$GLOBALS['ecs']->table('user_account').
           " WHERE is_paid = 0 AND id = '$rec_id' AND user_id = '$user_id'";

    return $GLOBALS['db']->query($sql);
}

/**
 * 查询会员余额的数量
 * @access  public
 * @param   int     $user_id        会员ID
 * @return  int
 * 
 * TODO 废弃的函数
 */
function get_user_surplus($user_id)
{
    $sql = "SELECT SUM(amount) FROM " .$GLOBALS['ecs']->table('user_account').
           " WHERE is_paid = 1 AND user_id = '$user_id'";

    return $GLOBALS['db']->getOne($sql);
}

function get_userSessionkey_info($user_session_key){
	$sql =	"select * from ".$GLOBALS['ecs']->table('users')." where userId = '$user_session_key'";
	 return $GLOBALS['db']->getOne($sql);
}
/**
 * 获取用户中心默认页面所需的数据
 *
 * @access  public
 * @param   int         $user_id            用户ID
 *
 * @return  array       $info               默认页面所需资料数组
 */
function get_user_default($user_id)
{
    $sql = "SELECT pay_points, user_id, user_mobile, user_name,userId,email,sex, user_money,address_id,reg_time, last_time, rank_points, user_rank, user_profile FROM " .$GLOBALS['ecs']->table('users'). " WHERE user_id = '$user_id'";
    $row = $GLOBALS['db']->getRow($sql);
    $info = array();
    $info['userId']  = $row['userId'];
	$info['email']  = $row['email'];
	$info['sex']  = $row['sex'];
	$info['username']  = $row['user_name'];
	$info['user_mobile']  = $row['user_mobile'];
    $info['shop_name'] = $GLOBALS['_CFG']['shop_name'];
    $info['address_id']=	$row['address_id'];
    $info['user_id']=	$row['user_id'];
	$info['rank_points']=	$row['rank_points'];
    $info['pay_points']=	$row['pay_points'];
    $info['integral']  = $row['pay_points'] . $GLOBALS['_CFG']['integral_name'];
    $info['user_profile']  = $row['user_profile'];
    $info['reg_time'] = $row['reg_time'];
    include_once(ROOT_PATH . 'includes/lib_oukoo.php');
    //user_rank总是动态计算  Tao Fei 2008 5 6
    $info['rank_name'] = getRankNameByPoints($user_id);
    $info['user_rank'] = getRankIdByPoints($user_id); 
    $info['rank_id'] = $info['user_rank']; 



    //如果$_SESSION中时间无效说明用户是第一次登录。取当前登录时间。
    $last_time = !isset($_SESSION['last_time']) ? $row['last_time'] : $_SESSION['last_time'];

    if (substr($last_time, 0, 4) == '0000')
    {
        $last_time = date('Y-m-d H:i:s');
    }

    $info['last_time'] = date($GLOBALS['_CFG']['time_format'], strtotime($last_time));
    $info['surplus']   = price_format($row['user_money']);
    $info['bonus']     = sprintf($GLOBALS['_LANG']['user_bonus_info'], 0, price_format(0));

    $pre_month = date('Ymd', strtotime('-30 day'));
    $info['order_count'] = $GLOBALS['db']->getOne("SELECT COUNT(*) FROM " .$GLOBALS['ecs']->table('order_info'). " WHERE user_id = '" .$user_id. "' AND order_sn > '" .$pre_month. "'");

    $last_time = substr($last_time, 0);
    $last_time = str_replace('-','',$last_time);
    $info['shipped_order'] = $GLOBALS['db']->getAll("SELECT order_id, order_sn FROM " .$GLOBALS['ecs']->table('order_info'). " WHERE user_id = '" .$user_id. "' AND order_sn > '" .$last_time. "' AND shipping_status > '" .SS_UNSHIPPED. "'");

    return $info;
}

/**
 * 添加商品标签
 *
 * @access  public
 * @param   integer     $id
 * @param   string      $tag
 * @return  void
 * 
 * TODO 废弃的函数
 */
function add_tag($id, $tag)
{
    if (empty($tag))
    {
        return;
    }

    $arr = explode(' ', $tag);

    foreach ($arr AS $val)
    {
        /* 检查是否重复 */
        $sql = "SELECT COUNT(*) FROM ". $GLOBALS['ecs']->table("tag").
                " WHERE user_id = '".$_SESSION['user_id']."' AND goods_id = '$id' AND tag_words = '$val'";

        if ($GLOBALS['db']->getOne($sql) == 0)
        {
            $sql = "INSERT INTO ".$GLOBALS['ecs']->table("tag")." (user_id, goods_id, tag_words) ".
                    "VALUES ('".$_SESSION['user_id']."', '$id', '$val')";
            $GLOBALS['db']->query($sql);
        }
    }
}

/**
 * 标签着色
 *
 * @access   public
 * @param    array
 * @author   Xuan Yan
 *
 * @return   none
 * 
 * TODO 废弃的函数
 */
function color_tag(&$tags)
{
    $tagmark = array(
        array('color'=>'#666666','size'=>'0.8em'),
        array('color'=>'#333333','size'=>'0.9em'),
        array('color'=>'#006699','size'=>'1.0em'),
        array('color'=>'#CC9900','size'=>'1.1em'),
        array('color'=>'#666633','size'=>'1.2em'),
        array('color'=>'#993300','size'=>'1.3em'),
        array('color'=>'#669933','size'=>'1.4em'),
        array('color'=>'#3366FF','size'=>'1.5em'),
        array('color'=>'#197B30','size'=>'1.6em'),
    );

    $maxlevel = count($tagmark);
    $tcount = $scount = array();

    foreach($tags AS $val)
    {
        $tcount[] = $val['tag_count']; // 获得tag个数数组
    }
    $tcount = array_unique($tcount); // 去除相同个数的tag

    sort($tcount); // 从小到大排序

    $tempcount = count($tcount); // 真正的tag级数
    $per = $maxlevel >= $tempcount ? 1 : $maxlevel / ($tempcount - 1);

    foreach ($tcount AS $key => $val)
    {
        $lvl = floor($per * $key);
        $scount[$val] = $lvl; // 计算不同个数的tag相对应的着色数组key
    }

    $rewrite = intval($GLOBALS['_CFG']['rewrite']) > 0;

    /* 遍历所有标签，根据引用次数设定字体大小 */
    foreach ($tags AS $key => $val)
    {
        $lvl = $scount[$val['tag_count']]; // 着色数组key

        $tags[$key]['color'] = $tagmark[$lvl]['color'];
        $tags[$key]['size']  = $tagmark[$lvl]['size'];
        $tags[$key]['url']   = $rewrite ? 'tag-' . urlencode($val['tag_words']) . '.html' : 'search.php?keywords=' . urlencode($val['tag_words']);
    }
    shuffle($tags);
}
/**
 *  获取指定用户的到货通知列表
 *
 * @access  public
 * @param   int     $user_id        用户ID
 * @param   int     $num            列表最大数量
 * @param   int     $start          列表起始位置
 *
 * @return  array   $arr
 * 
 * TODO 废弃的函数
 */
function get_goods_inform_list($user_id, $is_deal = 0, $num = 10, $start = 0)
{
    $sql = " SELECT gi.id, g.goods_id, g.goods_name, g.is_on_sale, IFNULL(gs.sale_status, g.sale_status) AS status, IFNULL(gs.style_price , g.shop_price) AS price, gs.img_url, IF(gs.goods_color = '', s.color, gs.goods_color) AS goods_color , gi.date, gi.email, gi.user_mobile, gi.is_deal, gi.style_id, gi.action_time
            FROM {$GLOBALS['ecs']->table('goods')} AS g, {$GLOBALS['ecs']->table('goods_inform')} AS gi
            	LEFT JOIN {$GLOBALS['ecs']->table('goods_style')} AS gs ON gi.style_id = gs.style_id AND gi.goods_id = gs.goods_id
            	LEFT JOIN {$GLOBALS['ecs']->table('style')} AS s ON s.style_id = gs.style_id
            WHERE gi.uid = '$user_id' AND g.goods_id = gi.goods_id AND gi.is_deal = '$is_deal'
            ORDER BY gi.date DESC, gi.id DESC";
    $res = $GLOBALS['db'] -> selectLimit($sql, $num, $start);
    $goods_list = array();
    $map_goods_status = array(
    		'tosale' 			=>		'即将上市',
    		'presale' 			=>		'预订',
    		'shortage' 			=>		'缺货',
    		'normal' 			=>		'在售',
    		'withdrawn' 		=>		'撤回',
    		'booking' 			=>		'预订',
    	);
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
    	$goods = array();
    	$goods['goods_id'] = $row['goods_id'];
    	$goods['style_id'] = $row['style_id'];
    	$goods['goods_name'] = $row['goods_name'];
    	$goods['status'] = $row['status'];
    	$goods['is_on_sale'] = $row['is_on_sale'];
    	$goods['status_name'] = $map_goods_status[$row['status']];
    	$goods['price'] = $row['price'];
    	$goods['img_url'] = $row['img_url'];
    	$goods['goods_color'] = $row['goods_color'];
    	$goods['date'] = $row['date'];
    	$goods['email'] = $row['email'];
    	$goods['user_mobile'] = $row['user_mobile'];
    	$goods['is_deal'] = $row['is_deal'];
    	$goods['id'] = $row['id'];
    	$goods['action_time'] = $row['action_time'];
    	$goods_list[] = $goods;
	}
    return $goods_list;
}
/**
 *  获取指定用户的降价提醒列表
 *
 * @access  public
 * @param   int     $user_id        用户ID
 * @param   int     $num            列表最大数量
 * @param   int     $start          列表起始位置
 *
 * @return  array   $arr
 */
function get_pricecut_inform_list($user_id, $is_deal = 0, $num = 10, $start = 0)
{
    $sql = " SELECT pi.id, g.goods_id, g.goods_name, g.is_on_sale, IFNULL(gs.sale_status, g.sale_status) AS status, IFNULL(gs.style_price , g.shop_price) AS price, gs.img_url, IF(gs.goods_color = '', s.color, gs.goods_color) AS goods_color , IF(IF(gs.price_range = 0 OR gs.price_range is null, g.price_range, gs.price_range) = 0, 10, IF(gs.price_range = 0 OR gs.price_range is null, g.price_range, gs.price_range)) AS goods_price_range, pi.date, pi.email, pi.user_mobile, pi.is_deal, pi.style_id, pi.action_time, pi.price_inform, pi.price_range, pi.shop_price
            FROM {$GLOBALS['ecs']->table('goods')} AS g, {$GLOBALS['ecs']->table('pricecut_inform')} AS pi
            	LEFT JOIN {$GLOBALS['ecs']->table('goods_style')} AS gs ON pi.style_id = gs.style_id AND pi.goods_id = gs.goods_id
            	LEFT JOIN {$GLOBALS['ecs']->table('style')} AS s ON s.style_id = gs.style_id
            WHERE pi.uid = '$user_id' AND g.goods_id = pi.goods_id AND pi.is_deal = '$is_deal'
            ORDER BY pi.date DESC, pi.id DESC";

    $res = $GLOBALS['db'] -> selectLimit($sql, $num, $start);
    $goods_list = array();
    $map_goods_status = array(
    		'tosale' 			=>		'即将上市',
    		'presale' 			=>		'预订',
    		'shortage' 			=>		'缺货',
    		'normal' 			=>		'在售',
    		'withdrawn' 		=>		'下市',
    		'booking' 			=>		'预订',
    	);
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
    	$goods = array();
    	$goods['goods_id'] = $row['goods_id'];
    	$goods['style_id'] = $row['style_id'];
    	$goods['goods_name'] = $row['goods_name'];
    	$goods['status'] = $row['status'];
    	$goods['is_on_sale'] = $row['is_on_sale'];
    	$goods['status_name'] = $map_goods_status[$row['status']];
    	$goods['price'] = $row['price'];
    	$goods['img_url'] = $row['img_url'];
    	$goods['goods_color'] = $row['goods_color'];
    	$goods['date'] = $row['date'];
    	$goods['email'] = $row['email'];
    	$goods['user_mobile'] = $row['user_mobile'];
    	$goods['is_deal'] = $row['is_deal'];
    	$goods['id'] = $row['id'];
    	$goods['action_time'] = $row['action_time'];
    	$goods['price_inform'] = $row['price_inform'];
    	$goods['shop_price'] = $row['shop_price'];
    	$goods['price_range'] = $row['price_range'];
    	$goods['goods_price_range'] = $row['goods_price_range'];
    	$goods_list[] = $goods;
	}
    return $goods_list;
}
/**
 *  获取指定用户的到货通知数量
 *
 * @access  public
 * @param   int     $user_id        用户ID
 *
 * @return  array   $arr
 */
function get_goods_inform_num($user_id, $is_deal = 0)
{
    $sql = " SELECT COUNT(*)
            FROM {$GLOBALS['ecs']->table('goods')} AS g, {$GLOBALS['ecs']->table('goods_inform')} AS gi
            WHERE gi.uid = '$user_id' AND g.goods_id = gi.goods_id AND gi.is_deal = '$is_deal'
            ORDER BY gi.date DESC, gi.id DESC";

    $res = $GLOBALS['db'] -> getOne($sql);
    return $res;
}
/**
 *  获取指定用户的降价提醒数量
 *
 * @access  public
 * @param   int     $user_id        用户ID
 *
 * @return  array   $arr
 */
function get_pricecut_inform_num($user_id, $is_deal = 0)
{
    $sql = " SELECT COUNT(*)
            FROM {$GLOBALS['ecs']->table('goods')} AS g, {$GLOBALS['ecs']->table('pricecut_inform')} AS pi
            WHERE pi.uid = '$user_id' AND g.goods_id = pi.goods_id AND pi.is_deal = '$is_deal'
            ORDER BY pi.date DESC, pi.id DESC";

    $res = $GLOBALS['db'] -> getOne($sql);
    return $res;
}

?>
