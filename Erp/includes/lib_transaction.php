<?php


if (!defined('IN_ECS'))
{
	die('Hacking attempt');
}

/**
 * 修改个人资料（Email, 性别，生日)
 *
 * @access  public
 * @param   array       $profile       array_keys(user_id int, email string, sex int, birthday string);
 *
 * @return  boolen      $bool
 * @TODO 需要调用 passport 的修改 email
 */
function edit_profile($profile)
{
	if (empty($profile['user_id']))
	{
		$GLOBALS['err']->add($GLOBALS['_LANG']['not_login']);

		return false;
	}

	if (!empty($profile['email']))
	{
		if (is_email($profile['email']))
		{
			if (!$GLOBALS['user']->edit_user($profile['user_id'], '', $profile['email'], $profile['birthday'], $profile['sex']))
			{
				if ($GLOBALS['user']->error == ERR_EMAIL_EXISTS)
				{
					$GLOBALS['err']->add(sprintf($GLOBALS['_LANG']['email_exist'], $profile['email']));
				}
				else
				{
					$GLOBALS['err']->add('DB ERROR!');
				}

				return false;
			}
			else
			{
				// {{{ 更新 passport 上 email
				$arContext['UserName'] = "";
				#$arError = $GLOBALS['SSO']->changeUserInfo($arContext);
				// }}}
			}
		}
		else
		{
			$GLOBALS['err']->add(sprintf($GLOBALS['_LANG']['email_invalid'], $profile['email']));

			return false;
		}
	}

	if ($profile['sex'] != 0 && $profile['sex'] != 1 && $profile['sex'] != 2)
	{
		$GLOBALS['err'] = 2;
	}

	/* 更新users表 */
	$sql = "UPDATE " .$GLOBALS['ecs']->table('users') . " SET " .
	"sex = '$profile[sex]', " .
	/*"email = '$profile[email]', " .*/
	"birthday = '$profile[birthday]', " .

	// {{{ membership add by Zandy at 2007-12-20
	"user_realname = '$profile[user_realname]', " .
	"user_mobile = '$profile[user_mobile]', " .
	"user_address = '$profile[user_address]', " .
	"user_profile = '$profile[user_profile]', " .
	"user_fav_cate = '$profile[user_fav_cate]', " .
	"user_products = '$profile[user_products]', " .
	// }}}
    //{{{ detail address (taofei 2008 4 9)
    "country = '$profile[country]'," .
    "province = '$profile[province]'," .
    "city = '$profile[city]'," .
    "district = '$profile[district]'," .
    "zipcode = '" . $GLOBALS['db']->escape_string($profile['zipcode']) . "' " .
    //}}}
	"WHERE user_id = '$profile[user_id]'";

	$GLOBALS['db']->query($sql);

	return true;
}

/**
 * 获取用户的地址信息。 （非送货地址, 2008 4 9 新加)
 * @param   int  $user_id  用户user_id
 */
function get_user_profile_address($user_id)
{
	$sql = "SELECT country, province,city,district,zipcode, ".
    " (select region_name from " . $GLOBALS['ecs']->table('region') . " WHERE region_id = a.country) as country_name,".
    " (select region_name from " . $GLOBALS['ecs']->table('region') . " WHERE region_id = a.province) as province_name,".
    " (select region_name from " . $GLOBALS['ecs']->table('region') . " WHERE region_id = a.city) as city_name,".
    " (select region_name from " . $GLOBALS['ecs']->table('region') . " WHERE region_id = a.district) as district_name".
    " FROM " . $GLOBALS['ecs']->table('users') . " AS a" .
    " WHERE user_id='" . $user_id ."'";
    return $GLOBALS['db']->getRow($sql);
}

/**
 * 取得收货人地址列表
 * @param   int     $user_id    用户编号
 * @return  array
 */
function get_consignee_list($user_id)
{
	//{{{ 增加省市等的名称
	$sql = "SELECT *, ".
	" (select region_name from " . $GLOBALS['ecs']->table('region') . " WHERE region_id = a.country) as country_name,".
	" (select region_name from " . $GLOBALS['ecs']->table('region') . " WHERE region_id = a.province) as province_name,".
	" (select region_name from " . $GLOBALS['ecs']->table('region') . " WHERE region_id = a.city) as city_name,".
	" (select region_name from " . $GLOBALS['ecs']->table('region') . " WHERE region_id = a.district) as district_name".
	//}}}
	" FROM " . $GLOBALS['ecs']->table('user_address') .
	" as a WHERE user_id = '$user_id' order by address_id desc LIMIT 5";
	return $GLOBALS['db']->getAll($sql);
}

/**
 * 取得最后一次订单
 * @param int $user_id 用户编号
 */
function get_last_order($user_id) {
	$sql = "SELECT *, ".
	" (select region_name from " . $GLOBALS['ecs']->table('region') . " WHERE region_id = a.country) as country_name,".
	" (select region_name from " . $GLOBALS['ecs']->table('region') . " WHERE region_id = a.province) as province_name,".
	" (select region_name from " . $GLOBALS['ecs']->table('region') . " WHERE region_id = a.city) as city_name,".
	" (select region_name from " . $GLOBALS['ecs']->table('region') . " WHERE region_id = a.district) as district_name".
	" FROM " . $GLOBALS['ecs']->table('order_info') . " as a WHERE user_id ='$user_id' ORDER BY `order_id` DESC LIMIT 1 ";
	return $GLOBALS['db']->getRow($sql);
}

/**
 * 取得收货人地址列表
 * @param   int     $user_id    用户编号
 * @return  array
 */
function get_consignee_info($address_id){
	$sql = "SELECT * FROM " . $GLOBALS['ecs']->table('user_address') .
	" WHERE address_id = '$address_id' ";
	$addressInfo	=	$GLOBALS['db']->getRow($sql);
	$sqls	=	'select region_name from '.$GLOBALS['ecs']->table('region').' where region_id in ('.$addressInfo['country'].' , '.$addressInfo['province'].' , '.$addressInfo['city'].' , '.$addressInfo['district'].' )';
	$addressInfoName	=	$GLOBALS['db']->getAll($sqls);
	$addressInfo['country_name']	=	$addressInfoName[0]['region_name'];
	$addressInfo['province_name']	=	$addressInfoName[1]['region_name'];
	$addressInfo['city_name']		=	$addressInfoName[2]['region_name'];
	$addressInfo['district_name']	=	$addressInfoName[3]['region_name'];
	return $addressInfo;
}
/**
 * 取得收货人符合的列表fo
 * @param   int     $user_id    用户编号
 * @return  array
 */
function get_User_consignee($user_id,$sWhere){
	$sql = 'SELECT address_id FROM ' . $GLOBALS['ecs']->table('user_address') .
	" WHERE user_id = '$user_id' and $sWhere order by address_id desc LIMIT 1 ";
	$arresult	=$GLOBALS['db']->getRow($sql);
	if($arresult){
		return $arresult['address_id'];
	}else{
		return false;
	};
}

/**
 * 取得收货人最新地址列表
 * @param   int     $user_id    用户编号
 * @return  array
 */
function get_default_consignee($user_id){
	$sql = "SELECT * FROM " . $GLOBALS['ecs']->table('user_address') .
	" WHERE user_id = '$user_id' order by address_id desc LIMIT 1";
	return $GLOBALS['db']->getRow($sql);
}

/**
 *  给指定用户添加一个指定红包
 *
 * @access  public
 * @param   int         $user_id        用户ID
 * @param   string      $bouns_sn       红包序列号
 *
 * @return  boolen      $result
 * 
 * TODO 废弃的函数
 */
function add_bonus($user_id, $bouns_sn)
{
	if (empty($user_id))
	{
		$GLOBALS['err']->add($GLOBALS['_LANG']['not_login']);

		return false;
	}

	/* 查询红包序列号是否已经存在 */
	$sql = "SELECT bonus_id, bonus_sn, user_id FROM " .$GLOBALS['ecs']->table('user_bonus') .
	" WHERE bonus_sn = '$bouns_sn'";
	$row = $GLOBALS['db']->getRow($sql);
	if ($row)
	{
		if ($row['user_id'] == 0)
		{
			//红包没有被使用
			$sql = "UPDATE " .$GLOBALS['ecs']->table('user_bonus') . " SET user_id = '$user_id' ".
			"WHERE bonus_id = '$row[bonus_id]'";
			$result = $GLOBALS['db'] ->query($sql);
			if ($result)
			{
				return true;
			}
			else
			{
				return $GLOBALS['db']->errorMsg();
			}
		}
		else
		{
			if ($row['user_id']== $user_id)
			{
				//红包已经添加过了。
				$GLOBALS['err']->add($GLOBALS['_LANG']['bonus_is_used']);
			}
			else
			{
				//红包被其他人使用过了。
				$GLOBALS['err']->add($GLOBALS['_LANG']['bonus_is_used_by_other']);
			}

			return false;
		}
	}
	else
	{
		//红包不存在
		$GLOBALS['err']->add($GLOBALS['_LANG']['bonus_not_exist']);
		return false;
	}

}

function	selectGoodsName($goodsName,$userId){
	$sql	=	'select order_id  from '.$GLOBALS['ecs']->table('order_goods') .' where
	goods_name likes %'.$goodsName.'%';
	$rows	=	$GLOBALS['db']->getAll($sql);
}


/**
 * 取得订单数
 *
 * @param int $user_id shop数据库中的自增型user id
 * @param string $condition where子句中的条件以 AND 开头，例如$condition = " AND order_status = 1"
 * @return unknown
 */
function get_user_order_count($user_id, $condition = '')
{
	$sql = "SELECT COUNT(*) FROM {$GLOBALS['ecs']->table('order_info')} WHERE user_id = '$user_id' AND parent_order_id = 0 {$condition}";
	return  $GLOBALS['db']->getOne($sql);
}

/**
 *  获取用户指定范围的订单列表
 *
 * @access  public
 * @param   int         $user_id        用户ID号
 * @param   string      $selectInfo     商品名 (只获取含有这个商品的订单)
 * @param   int         $num            列表最大数量
 * @param   int         $start          列表起始位置
 * @param   int         $sql_condition  sql搜索条件，必须以AND开头，例如$sql_condition = " AND order_sn = '1122334455'"
 * @return  array       $order_list     订单列表
 */
function get_user_orders($user_id, $pay_state = true, $selectInfo = '', $num = 10, $start = 0, $parent_order_id=0, $allow_status = null, $sql_condition = '')
{
	/* 取得订单列表 */
	$arr    = array();
	$sql = "SELECT *, " .
		"(goods_amount + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee) AS total_fee, ".
	    " (select name from bj_store where bj_store.store_id=biaoju_store_id) as biaoju_store_name, ".
	    " (select count(1) from " .$GLOBALS['ecs']->table('order_info') ." as oi where oi.parent_order_id=order_info.order_id) as sub_order_count ".
		" FROM " .$GLOBALS['ecs']->table('order_info') .
		" as  order_info WHERE parent_order_id='$parent_order_id' ".
	    " and user_id = '$user_id' AND LENGTH(order_sn) = 10 AND INSTR(order_sn,'-') = 0 {$sql_condition} ORDER BY order_time DESC";
	if ($num > 0) {
		$res = $GLOBALS['db']->SelectLimit($sql, $num, $start);
		$orders = array();
		while ($order =  $GLOBALS['db']->fetchRow($res)) {
		  $orders[] = $order;
		}
	} else {
		$orders = $GLOBALS['db']->getAll($sql);
	}
	foreach($orders as $key => $row) {
		/* 如果不是父订单 */
		if ($row["sub_order_count"] == 0) {
			$sql	=	"select * from ".$GLOBALS['ecs']->table('order_goods') ." where order_id = ".$row['order_id'];
			$goods	=	$GLOBALS['db']->getAll($sql);
			$bad = true;
			foreach ($goods as $k => $v) {
				if($selectInfo && strrpos($v['goods_name'], $selectInfo)!==false){

					$bad = false;
				}
				if ($v["biaoju_store_goods_id"] > 0) {
					$sql	=	"select * from bj_store_goods where store_goods_id = ".$v['biaoju_store_goods_id'];
					$goods[$k]["biaoju_goods"] = $GLOBALS['db']->getRow($sql);
				}
			}
            #提供了$selectInfo 但是 没有一个商品名符合
			if ($selectInfo && $bad) continue;

			$row["goods"] = sort_array_tree($goods, "rec_id", "parent_id");
			$row["goods_count"] = count($goods);
		} else {
			/* 如果是父订单, 获取子订单 */
			$row["order_list"] = get_user_orders($user_id, $pay_state,$selectInfo, 0, 0, $row["order_id"], $allow_status);
			$row["order_count"] = count($row["order_list"]);
		}
		
		$row['order_status_desc'] = orderStatus($row['order_status'], $row['pay_status'], $row['shipping_status'], $row['invoice_status'], $row['pay_id']);
		#$row['order_status_desc_simple'] = simple_order_status_desc($row['order_status']);
		    //获取需要支付的log_id
		    $row['log_id']  = get_paylog_id($row['order_id'], $pay_type = PAY_ORDER);
        $order_status_desc = orderStatusDetail($row);
        $row['order_status_simple'] = $order_status_desc[0]; 
        $row['order_status_detail'] = $order_status_desc[1]; 
        $row['pay_online'] = $order_status_desc['pay_online']; 
        $row['shipping_status_desc'] = shippingStatus($row['shipping_status'], $row['order_status']);
        $row['payment_info'] = payment_info($row['pay_id']);
        add_carrier_bill(&$row);
        //订单状态过滤
        if(is_array($allow_status) && !in_array($row['order_status_desc'], $allow_status))
        {
            continue;
        }
        //是父订单，但是所有子订单都被过滤了
        if($row["sub_order_count"] > 0 && $row["order_count"] == 0)
        {
        	continue;
        }
        $arr[] = $row;
	}
	return $arr;
}

/**
 * 取消一个用户订单 (包括子订单)
 * @access  public
 * @param   int         $order_sn       订单SN
 * @param   int         $user_id        用户ID
 * @param   string      $note           取消订单的说明
 * @return void
 */
function cancel_order_by_sn($order_sn, $user_id = 0, $note = '',  $cancel_by = 'buyer') {
	$sql = "SELECT order_id FROM " .$GLOBALS['ecs']->table('order_info') . " WHERE order_sn = '$order_sn'";
	$order_id = $GLOBALS['db']->getOne($sql);
	return cancel_order($order_id, $user_id, $note, $cancel_by);
}
/**
 * 取消一个用户订单 (包括子订单)
 * @access  public
 * @param   int         $order_id       订单SN
 * @param   int         $user_id        用户ID
 * @param   string      $note           取消订单的说明
 * @param   cancel_by   $cancel_by      被谁取消
 * @return void
 */
function cancel_order($order_id, $user_id = 0, $note = '', $cancel_by = 'buyer') {
  global $ecs, $db;
	/* 查询订单信息 */
	$sql = "SELECT user_id, order_sn , order_id, surplus , integral , bonus, bonus_id, order_status, shipping_status, pay_status, user_id, parent_order_id FROM " .$GLOBALS['ecs']->table('order_info') ." WHERE order_id = '$order_id' or parent_order_id= '$order_id'";
	$orders = $GLOBALS['db']->getAll($sql);
	/* 检查状态 */
	if (empty($orders))	{
		$GLOBALS['err']->add($GLOBALS['_LANG']['order_exist']);
		return false;
	}
	foreach ($orders as $key => $order) {
		// 如果用户ID大于0，检查订单是否属于该用户
		if ($user_id > 0 && $order['user_id'] != $user_id)
		{
			$GLOBALS['err'] ->add($GLOBALS['_LANG']['no_priv']);
			return false;
		}
		###已收货，拒收退回，已自提 这几个状态不能取消
        if (in_array($order['shipping_status'], array(SS_RECEIVED, SS_JUSHOU_RECEIVED, SS_ZITI_WANCHENG)))
        {
			$GLOBALS['err']->add($GLOBALS['_LANG']['current_ss_not_cancel']);
			return false;
		}
		
        /*
		if (in_array($order['shipping_status'], array(SS_SHIPPED, SS_RECEIVED, SS_JUSHOU_RECEIVED, SS_ZITI_WANCHENG))
			|| in_array($order['pay_status'], array(PS_PAYED))) {
			$GLOBALS['err']->add($GLOBALS['_LANG']['current_ss_not_cancel']);
			return false;
		}
        */
	}
	foreach ($orders as $key => $order) {
		//将用户订单设置为取消
		//设置订单缺货状态为0 ncchen
		$order_id = $order['order_id'];
			
		$sql = "UPDATE ".$GLOBALS['ecs']->table('order_info') ." SET order_status = '".OS_CANCELED."' , shortage_status = '".SHORTAGE_NORMAL."' WHERE order_id = '$order_id'";
		if ($GLOBALS['db']->query($sql)) {
			
			/* 记录log */
			order_action($order['order_sn'], OS_CANCELED, $order['shipping_status'], $order['pay_status'],"{$GLOBALS['_LANG']['buyer_cancel']}:{$note}", $cancel_by);
			
			// 修改订单商品状态 ncchen
			$sql = "SELECT rec_id FROM {$ecs->table('order_goods')} WHERE order_id = {$order_id} AND goods_status != 0";
			if ($order_goods = $db->getCol($sql)) {
				foreach ($order_goods as $order_good) {
					$order_goods_action_list[] = sprintf("({$order_good['rec_id']}, %d, '用户取消，修改缺货状态为正常。{$_REQUEST['actionNote']}', NOW(), '$cancel_by')", STORAGE_OUT_OF_STOCK);
				}
				$order_goods_action = join(",", $order_goods_action_list);
				$sql = sprintf("INSERT INTO {$ecs->table('order_goods_action')} (order_goods_id, goods_status, action_note, action_time, action_user) VALUES %s", $order_goods_action);
				$db->query($sql);
				$sql = "UPDATE {$ecs->table('order_goods')} SET goods_status = 0 WHERE order_id = {$order_id} AND goods_status != 0";
				$db->query($sql);
			}
			if ($order['integral'] < 0) {
				change_user_integral($order['user_id'], $order['integral']);
			}
			// {{{ 1 退还欧币，如果有 add by Zandy 2007-12-26
			$db = $GLOBALS['db'];
			$ecs = $GLOBALS['ecs'];
			if ($order["parent_order_id"] == 0 && abs($order['integral']) > 0) {
				require_once('currency/currency.method.php');
				// {{{ 1.1 检查是否有该用户，没有则插入
				$sql0 = "select * from ".$ecs->table('users')." where user_id = '{$order['user_id']}' limit 1 ";
				$ft0 = $db->getRow($sql0);
				if (!$ft0['userId']){die('error return points');}
				$sql = "select * from membership.ok_user where user_id = '{$ft0['userId']}' limit 1 ";
				$ft1 = $db->getRow($sql);

				if (!$ft1['user_id'])
				{
					$sql = "INSERT INTO membership.ok_user (`rank_points`, `pay_points`, `rank_price`, `user_id`) VALUES ('', '', '', '{$ft0['userId']}');";
					$ttt1 = $db->query($sql);
				}
				// }}}
				$nowtime = time();
				// {{{ 1.2 退还加欧币
				$sqlu = "update membership.ok_user
					set pay_points = pay_points-".abs($order['integral'])."
					where user_id = '{$ft0['userId']}' limit 1";
				$ttta = $db->query($sqlu);
				// }}}
				// {{{ 1.3
				$sql_i = "INSERT INTO membership.ok_point_log
					(`pl_id`, `user_id`, `site_id`, `pl_utime`, `pl_uip`, `pl_ponits`, `use_mark`, `use_type`, `pl_comment`)
					VALUES
					('', '{$ft0['userId']}', 1, $nowtime, '".ip2long($_SERVER['REMOTE_ADDR'])."', ".abs($order['integral']).", '{$order['order_sn']}', 6, '订单取消退还');";
				$tttb = $db->query($sql_i);
				// }}}
			}
			//退回红包
			if(abs($order['bonus']) > 0 && strlen($order['bonus_id']) == 16) {
			  $sql = "UPDATE membership.ok_gift_ticket SET gt_state = 2, used_timestamp = 0, used_order_id = 0, used_user_id = '' WHERE `gt_code` = '{$order['bonus_id']}' AND `gt_state` = 4 LIMIT 1";
			  $db->query($sql);
			  //TODO 要记录下来这种变化
			  order_action($order['order_sn'], OS_CANCELED, $order['shipping_status'], $order['pay_status'], "红包已退回", $cancel_by);
	//  		  $sql = "insert into ";
			}
			// }}}
		}
		// }}}
	}
	return true;
}

/**
 * 确认一个用户订单
 *
 * @access  public
 * @param   int         $order_id       订单ID
 * @param   int         $user_id        用户ID
 *
 * @return  bool        $bool
 */
function affirm_received($order_id, $user_id = 0)
{
	/* 查询订单信息，检查状态 */
	$sql = "SELECT user_id, order_sn , order_status, shipping_status, pay_status FROM ".$GLOBALS['ecs']->table('order_info') ." WHERE order_id = '$order_id'";

	$order = $GLOBALS['db']->GetRow($sql);

	// 如果用户ID大于 0 。检查订单是否属于该用户
	if ($user_id > 0 && $order['user_id'] != $user_id)
	{
		$GLOBALS['err'] -> add($GLOBALS['_LANG']['no_priv']);

		return false;
	}
	/* 检查订单 */
	elseif ($order['shipping_status'] == SS_RECEIVED)
	{
		$GLOBALS['err'] ->add($GLOBALS['_LANG']['order_already_received']);

		return false;
	}
	elseif ($order['shipping_status'] != SS_SHIPPED)
	{
		$GLOBALS['err']->add($GLOBALS['_LANG']['order_invalid']);

		return false;
	}
	/* 修改订单发货状态为“确认收货” */
	else
	{
		$sql = "UPDATE " . $GLOBALS['ecs']->table('order_info') . " SET shipping_status = '" . SS_RECEIVED . "' WHERE order_id = '$order_id'";
		if ($GLOBALS['db']->query($sql))
		{
			/* 记录日志 */
			order_action($order['order_sn'], $order['order_status'], SS_RECEIVED, $order['pay_status'], '', $GLOBALS['_LANG']['buyer']);

			return true;
		}
		else
		{
			die($GLOBALS['db']->errorMsg());
		}
	}

}

/**
 * 保存用户的收货人信息
 * 如果收货人信息中的 id 为 0 则新增一个收货人信息
 *
 * @access  public
 * @param   array   $consignee
 * @param   boolean $default        是否将该收货人信息设置为默认收货人信息
 * @return  boolean
 */
function save_consignee($consignee, $default=false)
{
	if ($consignee['address_id'] > 0)
	{
		/* 修改地址 */
		$res = $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('user_address'), $consignee, 'UPDATE', 'address_id = ' . $consignee['address_id']);
	}
	else
	{
		/* 添加地址 */
		$res = $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('user_address'), $consignee, 'INSERT');
		$consignee['address_id'] = $GLOBALS['db']->insert_id();
	}

	if ($default)
	{
		/* 保存为用户的默认收货地址 */
		$sql = "UPDATE " . $GLOBALS['ecs']->table('users') .
		" SET address_id = '$consignee[address_id]' WHERE user_id = '$_SESSION[user_id]'";

		$res = $GLOBALS['db']->query($sql);
	}

	return $res !== false;
}

/**
 * 删除一个收货地址
 *
 * @access  public
 * @param   integer $id
 * @return  boolean
 */
function drop_consignee($id)
{
	$sql = "SELECT user_id FROM " .$GLOBALS['ecs']->table('user_address') . " WHERE address_id = '$id'";
	$uid = $GLOBALS['db']->getOne($sql);

	if ($uid != $_SESSION['user_id'])
	{
		return false;
	}
	else
	{
		$sql = "DELETE FROM " .$GLOBALS['ecs']->table('user_address') . " WHERE address_id = '$id'";
		$res = $GLOBALS['db']->query($sql);

		return $res;
	}
}

/**
 *  添加或更新指定用户收货地址
 *
 * @access  public
 * @param   array       $address
 * @return  bool
 */
function update_address($address)
{
	$address_id = intval($address['address_id']);
	unset($address['address_id']);

	if ($address_id > 0)
	{
		/* 更新指定记录 */
		$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('user_address'), $address, 'UPDATE', 'address_id = ' .$address_id);
	}
	else
	{
		/* 插入一条新记录 */
		$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('user_address'), $address, 'INSERT');
		$address_id = $GLOBALS['db']->insert_id();
	}

	if (isset($address['defalut']) && $address['default'] > 0 && isset($address['user_id']))
	{
		$sql = "UPDATE ".$GLOBALS['ecs']->table('users') .
		" SET address_id = '".$address_id."' ".
		" WHERE user_id = '" .$address['user_id']. "'";
		$GLOBALS['db'] ->query($sql);
	}

	return $address_id;
}

/**
 *  获取指订单的详情
 *
 * @access  public
 * @param   int         $order_id       订单ID
 * @param   int         $user_id        用户ID
 *
 * @return   arr        $order          订单所有信息的数组
 */
function get_order_detail($order_id, $user_id = 0) {
	$order_sn = $GLOBALS['db']->getOne("SELECT order_sn FROM ".$GLOBALS['ecs']->table('order_info') ." WHERE order_id = '$order_id'");
	return get_order_detail_by_sn($order_sn, $user_id);
}




/**
 *  根据Order_sn获取指订单的详情, 包括子订单信息
 *
 * @access  public
 * @param   string         $order_sn       订单SN
 * @param   int         $user_id        用户ID
 * @return   arr        $order          订单所有信息的数组
 */
function get_order_detail_by_sn($order_sn, $user_id) {
	include_once(ROOT_PATH . 'includes/lib_order.php');
	$order = order_info(0, $order_sn);
	if (!$order) {
		$GLOBALS['err']->add($GLOBALS['_LANG']['invalid_order_id']);
		return false;
	}
	//检查订单是否属于该用户
	if ($user_id > 0 && $user_id != $order['user_id']) {
		$GLOBALS['err']->add($GLOBALS['_LANG']['no_priv']);
		return false;
	}
	$order['order_status_desc'] = orderStatus($order['order_status'], $order['pay_status'], $order['shipping_status'], $order['invoice_status'], $order['pay_id']);
	$order['shipping_status_desc'] = shippingStatus($order['shipping_status']);
	/* 对发货号处理 */
	if (!empty($order['carrier_bill_id'])) {
		// {{{ 货运单信息
		$order['carrier_bill'] = $GLOBALS['db']->getRow("SELECT * FROM ".$GLOBALS['ecs']->table('carrier_bill') ."
				WHERE bill_id = '{$order['carrier_bill_id']}'");
		if ($order['carrier_bill'] && isset($order['carrier_bill']["carrier_id"]))
		$order['carrier_info'] = $GLOBALS['db']->getRow("SELECT * FROM ".$GLOBALS['ecs']->table('carrier') ."
				WHERE carrier_id = '{$order['carrier_bill']["carrier_id"]}'");
		// }}}
	}

	/* 只有未确认才允许用户修改订单地址 */
	$order['allow_update_address'] = $order['order_status'] == OS_UNCONFIRMED ? 1 : 0;

	/* 无配送时的处理 */
	$order['shipping_id'] == -1 and $order['shipping_name'] = $GLOBALS['_LANG']['shipping_not_need'];

	/* 其他信息初始化 */
	$order['how_oos_name']     = $order['how_oos'];
	$order['how_surplus_name'] = $order['how_surplus'];

	/* 虚拟商品付款后处理 */
	if ($order['pay_status'] != PS_UNPAYED)
	{
		/* 取得已发货的虚拟商品信息 */
		$virtual_goods = get_virtual_goods($order_id, true);

		if ($virtual_goods)
		{
			/* 将语言项注册为全局 */
			global $_LANG;
			$order['extension_html'] = '';

			/* 取得虚拟商品所需要插件支持 */
			$extension_code = array_unique(array_keys($virtual_goods));
			foreach ($extension_code AS $code)
			{
				if (file_exists('./plugins/'.$code.'/'.$code.'_inc.php'))
				{
					include_once('./plugins/'.$code.'/'.$code.'_inc.php');
					/* 存在语言项包含语言项 */
					if (file_exists('./plugins/'.$code.'/languages/common_'.$GLOBALS['_CFG']['lang'].'.php'))
					{
						include_once('./plugins/'.$code.'/languages/common_'.$GLOBALS['_CFG']['lang'].'.php');
					}
					$$code = new $code();
				}
				else
				{
					$order['extension_html'] .= '<tr><td colspan="4">'.sprintf($GLOBALS['_LANG']['plugins_not_found'],$code) . '</td></tr>';
				}
			}

			foreach ($virtual_goods AS $code => $goods_list)
			{
				foreach ($goods_list as $goods)
				{
					$order['extension_html'] .= $$code->result($order['order_sn'], $goods);
				}
			}

		}

	}

	/* 确认时间 支付时间 发货时间 */
	if ($order['confirm_time'] > 0 && $order['order_status'] == OS_CONFIRMED)
	{
		$order['confirm_time'] = sprintf(/*$GLOBALS['_LANG']['confirm_time'],*/date($GLOBALS['_CFG']['time_format'], $order['confirm_time']));
	}
	else
	{
		$order['confirm_time'] = '';
	}
	if ($order['pay_time'] > 0 && $order['pay_status'] != PS_UNPAYED)
	{
		$order['pay_time'] = sprintf(/*$GLOBALS['_LANG']['pay_time'],*/date($GLOBALS['_CFG']['time_format'], $order['pay_time']));
	}
	else
	{
		$order['pay_time'] = '';
	}
	if ($order['shipping_time'] > 0 && $order['shipping_status'] != SS_UNSHIPPED)
	{
		$order['shipping_time'] = sprintf(/*$GLOBALS['_LANG']['shipping_time'],*/date($GLOBALS['_CFG']['time_format'], $order['shipping_time']));
	}
	else
	{
		$order['shipping_time'] = '';
	}

	/* 订单 支付 配送 状态语言项 */
	#$order['order_status'] = $GLOBALS['_LANG']['os'][$order['order_status']];
	###$order['pay_status'] = $GLOBALS['_LANG']['ps'][$order['pay_status']]; // Zandy 2007-11-14
	#$order['pay_status'] = '';
	#$order['shipping_status_desc'] = $GLOBALS['_LANG']['ss'][$order['shipping_status']];
	// {{{ region
	$where_in = "{$order['province']}, {$order['city']}, {$order['district']}";
	$sql = "select * from ".$GLOBALS['ecs']->table('region')." where region_id in ($where_in)";
	$getAll = $GLOBALS['db']->getAll($sql);

	$last = null;
	$region = "";
	foreach ($getAll as $k => $v) {
		if ($last == $v['region_name']) continue;
		if ($last != null) $region = $region." ";
		$region = $region.$v['region_name'];
		$last = $v['region_name'];
	}
    $order['province_name'] = $getAll[0]['region_name'];
	$order['region'] = $region;
	// }}}

	// 获取商品信息或者子订单
	$sql	=	"select count(1) as sub_order_count from ".$GLOBALS['ecs']->table('order_info') ." where parent_order_id = ".$order['order_id'];
	$row	=	$GLOBALS['db']->getRow($sql);
	/* 如果不是父订单 */
	if ($row["sub_order_count"] == 0) {
		$sql	=	"select *, goods_number * goods_price AS total_price from ".$GLOBALS['ecs']->table('order_goods') ." where order_id = ".$order['order_id'];
		$goods	=	$GLOBALS['db']->getAll($sql);
		foreach ($goods as $k => $v) {
			if ($v["biaoju_store_goods_id"] > 0) {
				$sql	=	"select * from bj_store_goods where store_goods_id = ".$v['biaoju_store_goods_id'];
				$goods[$k]["biaoju_goods"] = $GLOBALS['db']->getRow($sql);
			}
			$sql	=	"select * from ".$GLOBALS['ecs']->table('goods') ." where goods_id = ".$v['goods_id'];
			$goods[$k]["shop_goods"] = $GLOBALS['db']->getRow($sql);
		}
		$order["goods"] = sort_array_tree($goods, "rec_id", "parent_id");
		$order["goods_count"] = count($goods);
	} else {
		/* 如果是父订单, 获取子订单 */
		$order["order_list"] = get_user_orders($user_id, 0, '', 0, 0, $order["order_id"] );
		/* 重新计算价格 */
		$order_amount = 0;
		foreach ($order["order_list"] as $key => $suborder) {
			if ($suborder['order_status']  !=  OS_CANCELED)
				$order_amount += $suborder["order_amount"];
		}
		$order["order_amount"] = $order_amount;
	}

	if ($order["biaoju_store_id"] > 0) {
		$sql	=	"select name from bj_store where store_id = ".$order['biaoju_store_id'];
		$row = $GLOBALS['db']->getRow($sql);
		$order["biaoju_store_name"] = $row["name"];
	}

	/* 如果是未付款状态，生成支付按钮 */
	if ($order["order_amount"] > 0 && $order['pay_status'] == PS_UNPAYED &&
	($order['order_status'] == OS_UNCONFIRMED ||
	$order['order_status'] == OS_CONFIRMED))
	{
		/* 在线支付按钮 */
		//支付方式信息
		$payment_info = array();
		$payment_info = payment_info($order['pay_id']);

		//无效支付方式
		if ($payment_info === false)
		{
			$order['pay_online'] = '';
		}
		else
		{
			//取得支付信息，生成支付代码
			$payment = unserialize_config($payment_info['pay_config']);
			//获取需要支付的log_id
			$order['log_id']    = get_paylog_id($order['order_id'], $pay_type = PAY_ORDER);
			$order['user_name'] = $_SESSION['user_name'];
			$order['pay_desc']  = $payment_info['pay_desc'];

			/* 调用相应的支付方式文件 */
			include_once(ROOT_PATH . 'includes/modules/payment/' . $payment_info['pay_code'] . '.php');

			/* 取得在线支付方式的支付按钮 */
			$pay_obj    = new $payment_info['pay_code'];
			$order['pay_online'] = $pay_obj->get_code($order, $payment);
		}
	}
	else
	{
		$order['pay_online'] = '';
	}
    $order['payment_info'] = payment_info($order['pay_id']) ;

	$order['biaoju_store_link'] = toBiaojuStorePath($order["biaoju_store_id"]);
	
	// 根据配送方式和发货地址取得送货天数
	$order['delivery_time'] = get_delivery_time($order['shipping_id'], $order['province'], $order['city'], $order['district'], $order['country']);
	return $order;
}

/**
 * 根据配送方式和发货地址取得送货天数
 *
 * @param int $shipping_id 运送id
 * @param int $province 省
 * @param int $city 城市
 * @param int $district 地区
 * @param int $country 国家
 * @return int 发货天数
 */
function get_delivery_time($shipping_id, $province = 0, $city = 0, $district = 0, $country = 1) {
	global $db, $ecs;
    $sql = "
    	SELECT sa.configure FROM {$ecs->table('shipping_area')} sa, {$ecs->table('area_region')} ar 
    	WHERE 
    		sa.shipping_area_id = ar.shipping_area_id 
    		AND sa.shipping_id = '{$shipping_id}'
    		AND ar.region_id IN ('{$country}', '{$province}', '{$city}', '{$district}')
    ";	
    $configures = $db->getOne($sql);
    if ($configures != null) {
    	$configures = unserialize($configures);
    	foreach ($configures as $key=>$configure) {
    		if ($configure['name'] == 'delivery_time') {
    			return $configure['value'];
    		}
    	}
    }
    return -1;    
}

/**
 *  获取用户可以和并的订单数组
 *
 * @access  public
 * @param   int         $user_id        用户ID
 *
 * @return  array       $merge          可合并订单数组
 */
function get_user_merge($user_id)
{
	$sql  = "SELECT order_sn FROM ".$GLOBALS['ecs']->table('order_info') .
	" WHERE user_id  = '$user_id' " .
	"AND (order_status = ".OS_UNCONFIRMED." OR order_status = ".OS_CONFIRMED." ) ".
	"AND shipping_status = ".SS_UNSHIPPED." AND pay_status = ".PS_UNPAYED. " ".
	"AND extension_code = '' ".
	" ORDER BY order_time DESC";
	$list = $GLOBALS['db']->GetCol($sql);

	$merge = array();
	foreach ($list as $val)
	{
		$merge[$val] = $val;
	}

	return $merge;
}

/**
 *  合并指定用户订单
 *
 * @access  public
 * @param   string      $from_order         合并的从订单号
 * @param   string      $to_order           合并的主订单号
 *
 * @return  boolen      $bool
 */
function merge_user_order($from_order, $to_order, $user_id = 0)
{
	if ($user_id > 0)
	{
		/* 检查订单是否属于指定用户 */
		if (strlen($to_order) > 0)
		{
			$sql = "SELECT user_id FROM " .$GLOBALS['ecs']->table('order_info').
			" WHERE order_sn = '$to_order'";
			$order_user = $GLOBALS['db']->getOne($sql);
			if ($order_user != $user_id)
			{
				$GLOBALS['err']->add($GLOBALS['_LANG']['no_priv']);
			}
		}
		else
		{
			$GLOBALS['err']->add($GLOBALS['_LANG']['order_sn_empty']);
			return false;
		}
	}

	$result = merge_order($from_order, $to_order);
	if ($result === true)
	{
		return true;
	}
	else
	{
		$GLOBALS['err']->add($result);
		return false;
	}
}

/**
 *  将指定订单中的商品添加到购物车
 *
 * @access  public
 * @param   int         $order_id
 *
 * @return  mix         $message        成功返回true, 错误返回出错信息
 */
function return_to_cart($order_id)
{
	/* 初始化基本件数量 goods_id => goods_number */
	$basic_number = array();

	/* 查订单商品：不考虑赠品 */
	$sql = "SELECT goods_id, goods_number, goods_attr, parent_id" .
	" FROM " . $GLOBALS['ecs']->table('order_goods') .
	" WHERE order_id = '$order_id' AND is_gift = 0" .
	" ORDER BY parent_id ASC";
	$res = $GLOBALS['db']->query($sql);

	$time=date('Y-m-d');
	while ($row = $GLOBALS['db']->fetchRow($res))
	{
		// 查该商品信息：是否删除、是否上架

		$sql = "SELECT goods_sn, goods_name, goods_number, market_price, " .
		"IF(is_promote = 1 AND '$time' BETWEEN promote_start AND promote_end, promote_price, shop_price) AS goods_price," .
		"is_real, extension_code, is_alone_sale, can_handsel, goods_type " .
		"FROM " . $GLOBALS['ecs']->table('goods') .
		" WHERE goods_id = '$row[goods_id]' " .
		" AND is_delete = 0 LIMIT 1";
		$goods = $GLOBALS['db']->getRow($sql);

		// 如果该商品不存在，处理下一个商品
		if (empty($goods))
		{
			continue;
		}

		// 如果使用库存，且库存不足，修改数量
		if ($GLOBALS['_CFG']['use_storage'] == 1 && $goods['goods_number'] < $row['goods_number'])
		{
			if ($goods['goods_number'] == 0)
			{
				// 如果库存为0，处理下一个商品
				continue;
			}
			else
			{
				// 库存不为0，修改数量
				$row['goods_number'] = $goods['goods_number'];
			}
		}

		// 如果有属性值，查询有效的属性值
		if ($row['goods_attr'] != '' && $goods['goods_type'] > 0)
		{
			$sql = "SELECT goods_attr_id " .
			"FROM " . $GLOBALS['ecs']->table('attribute') . " AS a, " . $GLOBALS['ecs']->table('goods_attr') . " AS ga " .
			"WHERE a.cat_id = '$goods[goods_type]' " .
			"AND a.attr_id = ga.attr_id " .
			"AND ga.goods_id = '$row[goods_id]' " .
			"AND ga.goods_attr_id " . db_create_in($row['goods_attr']);
			$attr_id = $GLOBALS['db']->getCol($sql);
			$row['goods_attr'] = join(',', $attr_id);
		}

		// 要返回购物车的商品
		$return_goods = array(
		'goods_id'      => $row['goods_id'],
		'goods_sn'      => addslashes($goods['goods_sn']),
		'goods_name'    => addslashes($goods['goods_name']),
		'market_price'  => $goods['market_price'],
		'goods_price'   => $goods['goods_price'],
		'goods_number'  => $row['goods_number'],
		'goods_attr'    => addslashes($row['goods_attr']),
		'is_real'       => $goods['is_real'],
		'extension_code'=> addslashes($goods['extension_code']),
		'parent_id'     => '0',
		'is_gift'       => '0',
		'can_handsel'   => $goods['can_handsel'],
		'rec_type'      => CART_GENERAL_GOODS
		);

		// 如果是配件
		if ($row['parent_id'] > 0)
		{
			// 查询基本件信息：是否删除、是否上架、能否作为普通商品销售
			$sql = "SELECT goods_id " .
			"FROM " . $GLOBALS['ecs']->table('goods') .
			" WHERE goods_id = '$row[parent_id]' " .
			" AND is_delete = 0 AND is_on_sale = 1 AND is_alone_sale = 1 LIMIT 1";
			$parent = $GLOBALS['db']->getRow($sql);
			if ($parent)
			{
				// 如果基本件存在，查询组合关系是否存在
				$sql = "SELECT goods_price " .
				"FROM " . $GLOBALS['ecs']->table('group_goods') .
				" WHERE parent_id = '$row[parent_id]' " .
				" AND goods_id = '$row[goods_id]' LIMIT 1";
				$fitting_price = $GLOBALS['db']->getOne($sql);
				if ($fitting_price)
				{
					// 如果组合关系存在，取配件价格，取基本件数量，改parent_id
					$return_goods['parent_id']      = $row['parent_id'];
					$return_goods['goods_price']    = $fitting_price;
					$return_goods['goods_number']   = $basic_number[$row['parent_id']];
				}
			}
		}
		else
		{
			// 保存基本件数量
			$basic_number[$row['goods_id']] = $row['goods_number'];
		}

		// 返回购物车：看有没有相同商品
		$sql = "SELECT goods_id " .
		"FROM " . $GLOBALS['ecs']->table('cart') .
		" WHERE session_id = '" . SESS_ID . "' " .
		" AND goods_id = '$return_goods[goods_id]' " .
		" AND goods_attr = '$return_goods[goods_attr]' " .
		" AND parent_id = '$return_goods[parent_id]' " .
		" AND is_gift = 0 " .
		" AND rec_type = '" . CART_GENERAL_GOODS . "'";
		$cart_goods = $GLOBALS['db']->getOne($sql);
		if (empty($cart_goods))
		{
			// 没有相同商品，插入
			$return_goods['session_id'] = SESS_ID;
			$return_goods['user_id']    = $_SESSION['user_id'];
			$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart'), $return_goods, 'INSERT');
		}
		else
		{
			// 有相同商品，修改数量
			$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') . " SET " .
			"goods_number = goods_number + '$return_goods[goods_number]' " .
			"WHERE session_id = '" . SESS_ID . "' " .
			"AND goods_id = '$return_goods[goods_id]' " .
			"AND rec_type = '" . CART_GENERAL_GOODS . "' LIMIT 1";
			$GLOBALS['db']->query($sql);
		}

		// 清空购物车的赠品
		$sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') .
		" WHERE session_id = '" . SESS_ID . "' AND is_gift = 1";
		$GLOBALS['db']->query($sql);

		return true;
	}
}

/**
 *  保存用户收货地址
 *
 * @access  public
 * @param   array   $address        array_keys(consignee string, email string, address string, zipcode string, tel string, mobile stirng, sign_building string, best_time string, order_id int)
 * @param   int     $user_id        用户ID
 *
 * @return  boolen  $bool
 */
function save_order_address($address, $user_id)
{
	$GLOBALS['err']->clean();
	/* 数据验证 */
	empty($address['consignee']) and $GLOBALS['err']->add($GLOBALS['_LANG']['consigness_empty']);
	empty($address['address']) and $GLOBALS['err']->add($GLOBALS['_LANG']['address_empty']);
	$address['order_id'] == 0 and $GLOBALS['err']->add($GLOBALS['_LANG']['order_id_empty']);
	if (empty($address['email']))
	{
		$GLOBALS['err']->add($GLOBALS['email_empty']);
	}
	else
	{
		if (!is_email($address['email']))
		{
			$GLOBALS['err']->add(sprintf($GLOBALS['_LANG']['email_invalid'], $address['email']));
		}
	}
	if ($GLOBALS['err']->error_no > 0)
	{
		return false;
	}

	/* 检查订单状态 */
	$sql = "SELECT user_id, order_status FROM " .$GLOBALS['ecs']->table('order_info'). " WHERE order_id = '" .$address['order_id']. "'";
	$row = $GLOBALS['db']->getRow($sql);
	if ($row)
	{
		if ($user_id > 0 && $user_id != $row['user_id'])
		{
			$GLOBALS['err']->add($GLOBALS['_LANG']['no_priv']);
			return false;
		}
		if ($row['order_status'] != OS_UNCONFIRMED)
		{
			$GLOBALS['err']->add($GLOBALS['_LANG']['require_unconfirmed']);
			return false;
		}
		$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('order_info'), $address, 'UPDATE', "order_id = '$address[order_id]'");
		return true;
	}
	else
	{
		/* 订单不存在 */
		$GLOBALS['err']->add($GLOBALS['_LANG']['order_exist']);
		return false;
	}
}

/**
 *
 * @access  public
 * @param   int         $user_id         用户ID
 * @param   int         $num             列表显示条数
 * @param   int         $start           显示起始位置
 *
 * @return  array       $arr             红保列表
 * 
 * TODO 废弃的函数
 */
function get_user_bouns_list($user_id, $num = 10, $start = 0)
{
	$sql = "SELECT u.bonus_sn, u.order_id, b.type_name, b.type_money, b.use_startdate, b.use_enddate ".
	" FROM " .$GLOBALS['ecs']->table('user_bonus'). " AS u ,".
	$GLOBALS['ecs']->table('bonus_type'). " AS b".
	" WHERE u.bonus_type_id = b.type_id AND u.user_id = '" .$user_id. "'";
	$res = $GLOBALS['db']->selectLimit($sql, $num, $start);
	$arr = array();
	$cur_date = date("Y-m-d");
	while ($row = $GLOBALS['db']->fetchRow($res))
	{
		/* 先判断是否被使用，然后判断是否开始或过期 */
		if (empty($row['order_id']))
		{
			/* 没有被使用 */
			if ($row['use_startdate'] > $cur_date)
			{
				$row['status'] = $GLOBALS['_LANG']['not_start'];
			}
			else if ($row['use_enddate'] < $cur_date)
			{
				$row['status'] = $GLOBALS['_LANG']['overdue'];
			}
			else
			{
				$row['status'] = $GLOBALS['_LANG']['not_use'];
			}
		}
		else
		{
			$row['status'] = '<a href="user.php?act=order_detail&order_id=' .$row['order_id']. '" >' .$GLOBALS['_LANG']['had_use']. '</a>';
		}

		$row['use_startdate'] = date($GLOBALS['_CFG']['date_format'], strtotime($row['use_startdate']));
		$row['use_enddate'] = date($GLOBALS['_CFG']['date_format'], strtotime($row['use_enddate']));

		$arr[] = $row;
	}
	return $arr;

}

/**
 * 获得会员的团购活动列表
 *
 * @access  public
 * @param   int         $user_id         用户ID
 * @param   int         $num             列表显示条数
 * @param   int         $start           显示起始位置
 *
 * @return  array       $arr             团购活动列表
 */
function get_user_group_buy($user_id, $num = 10, $start = 0)
{
	return true;
}

/**
  * 获得团购详细信息(团购订单信息)
  *
  *
  */
function get_group_buy_detail($user_id, $group_buy_id)
{
	return true;
}

// {{{

/**
 *
 * @see line 408
 * @author       Dummy | Zandy
 * update by Tao Fei (ftao@oukoo.com) 新的订单状态
 * @modifiedby   $LastChangedBy:  $
 * @param
 * @return
 * @throws       none
*/
function orderStatus($order_status, $pay_status, $shipping_status, $invoice_status, $pay_id)
{
	$s = '';
	#vv($order_status, $pay_status, $shipping_status, $invoice_status);
	if ($order_status == 2)
	{
		#$s = '订单取消';
		$s = '交易失败';
	}
	#elseif ($order_status == 1  && $pay_status == 2 && ($shipping_status == 0 || $shipping_status == 4))
	#{
    #		$s = '已付款';
	#}
	elseif (in_array($shipping_status, array(0, 4)) && $order_status == 0 && ($pay_status == 0 || $pay_status == 1)) 
	{
		#vv($shipping_status,$order_status,$pay_status);
		#$s = '等待确认';
		$s = '正在交易';
//	} elseif ($order_status == 0 || $shipping_status == 4 || ($order_status == 1 && in_array($pay_status, array(0, 1, 2)) && in_array($invoice_status, array(0, 1, 2)) )
//	     || ($order_status == 1 && $pay_status == 0 && $pay_id != 1 && $shipping_status == 0 && $invoice_status == 3))
//	{
//		$s = '等待确认';
	}
	elseif ($order_status == 1 && in_array($shipping_status, array(0, 4)))
	{
		$s = '正在交易';
	}
	elseif ($shipping_status == 1)
	{
		$s = '正在交易';
	}
	elseif ($shipping_status == 2)
	{
		$s = '交易成功';
	}
	elseif ($shipping_status == 3)
	{
		$s = '交易失败';
	}
	elseif ($order_status == 4)
	{
		$s = '交易失败';
	}
	elseif ($shipping_status == 5)
	{
		$s = '正在交易';
	}
	elseif ($shipping_status == 6)
	{
		$s = '交易成功';
	}
	elseif ($shipping_status == 7)
	{
		$s = '交易失败';
	}
	elseif ($shipping_status == 8)
	{
		$s = '正在交易';
	}
	elseif ($shipping_status == 9)
	{
		$s = '正在交易';
	}
	return $s;
}

// }}}

/**
 * 直接同数据库字段对应的物流状态
 */
function shippingStatus($shipping_status,$order_status=1)
{
    if ($order_status == 0 || $order_status == 2) {
  	  return '';
    }
    
    $map = array(
        0 => '待配货',
        1 => '已发货',
        2 => '收货确认',
        3 => '拒收退回',
        4 => '等待用户自提',
        5 => '已发往自提点',
        6 => '已自提',
        7 => '自提取消',
        8 => '已出库，待发货',
        9 => '已配货，待出库',
        10 => '已配货，但商品改变'
    );
    return $map[$shipping_status];
}

/**
 * 根据订单 获得订单可能的正常流程下的状态列表
 *
 */
function possible_order_status(&$order)
{
    $order_status = $order['order_status'];
    $shipping_status = $order['shipping_status'];
    $shipping = shipping_info($order['shipping_id']);
    $payment = payment_info($order['pay_id']);
    $shortage_status = $order['shortage_status'];
    if($payment['is_cod'] == 0)
    {
        $ret = array(
            0 => "订单生成，待付款",
            "已付款，待配货",
        );
    }
    else
    {
        $ret = array(
            0 => "订单生成，待确认",
            "订单已确认，待配货",
        );
    }
    if ($shortage_status == SHORTAGE_WAIT || $shortage_status == SHORTAGE_CONFIRMED) {
    	$ret[] = "暂缺货，请等待";
    	$ret[] = "已到货，待配货";
    }
/*    $ret[] = "正在配货";*/
    $ret[] = "配货完成，待出库";
    $ret[] = "已出库，待发货";
    if ($shipping['support_cod'] && $shipping['support_no_cod'])
    {
        $ret[] = "已发往自提点";
        $ret[] = "已到货,等待用户自提";
        $ret[] = "交易成功";
    }
    else
    {
        $ret[] = "已发货，待签收";
        $ret[] = "交易成功";
    }
    if($shipping_status == SS_JUSHOU_RECEIVED)
    {
        $ret[count($ret)-1] = "用户拒收";
    }
    elseif($shipping_status == SS_ZITI_QUXIAO)
    {
        $ret[count($ret)-1] = "自提已取消";
    }

    $order['possible_order_status'] = $ret; 
    return $order;
}

/**
 * 为每一个订单添加相关的评论信息
 */
function add_after_order_comment_info($item)
{
    //$order_status 3, $pay_status 2 $shipping_status 6 可设置这个值测试交易成功的订单。
    global $order_status_details_mapping;

    if(!$item["sub_order_count"] || $item["sub_order_count"] ==0 )
    {
    	//检测是否是OUKU
        //可以评论的条件是 已经发货/自提/交易完成
        //update by Tao Fei (2008-05-17) 允许评论认证商家的订单
        //if($item['biaoju_store_id'] > 0 || !in_array($item['shipping_status_desc'], array("已发货", "待自提", "已收货", "已自提")))
        if(!in_array($item['shipping_status'], array(SS_SHIPPED, SS_ZITI_DENGDAI, SS_RECEIVED, SS_ZITI_WANCHENG)))
        {
            $item['can_comment'] = 0;
        }
        else
        {
            $item['can_comment'] = 1;
            $sql = sprintf("SELECT COUNT(*) FROM `ecs_after_order_comment` WHERE `order_id` = '%s' AND `user_id` = '%s'", $item['order_id'], $GLOBALS['userInfo']['userId']);
            $item['has_comment'] = $GLOBALS['db']->getOne($sql) > 0;
            if ($item['has_comment']) //如果用户已发表，不能再评价订单了。
            {
            	$item['can_comment'] = 0;
            }
        }
    }
    else
    {
      if (is_array($item['order_list'])) {
      	$item['order_list'] = array_map("add_after_order_comment_info", $item['order_list']);
      }        
    }
    return $item;
}

function orderStatusDetail(&$order)
{
    $payment = payment_info($order['pay_id']);
    /* 取得支付信息，生成支付代码 */
    if ($payment['pay_code']) {
    	@include_once(ROOT_PATH . 'includes/modules/payment/' . $payment['pay_code'] . '.php');
    	$pay_obj    = new $payment['pay_code'];
      $pay_online = $pay_obj->get_code($order, unserialize_config($payment['pay_config']));
    } else {
      $pay_online = "";
    }
    $pay_code = $payment['pay_code'];
    include_once(ROOT_PATH . 'conf/ouku.config.php');
    
    $pay_status = $order['pay_status'];
    $order_status = $order['order_status'];
    $shipping_status = $order['shipping_status'];
    $shipping = shipping_info($order['shipping_id']); 
    $shortage_status = $order['shortage_status'];
    $sql_mid = "SELECT midway_address FROM {$GLOBALS['ecs']->table('shipping')} WHERE shipping_id = {$order['shipping_id']}";
	  $mid_way = $GLOBALS['db']->getOne($sql_mid); 
    //首先处理订单完成状态
    if($order_status == OS_CONFIRMED && ($shipping_status == SS_RECEIVED || $shipping_status == SS_ZITI_WANCHENG))
    {
        $ret = array("交易成功", 
            sprintf(
                "<div style='clear:both'>此订单已经交易完毕，感谢您对欧酷的支持和信任，".($order['can_comment'] ? "如您对欧酷有任何建议或者意见，<a href='%s'>[进入订单评价]</a>为此次交易点评，我们将悉心参考，" : '')."期待您的再次光顾。
				<p style='margin-top:5px;'>如果您对此次所购买的商品有何售后问题，可点击此处进入<a href='#shouhou'>[售后服务申请]</a>，我们的售后人员会在您提交申请后尽快给予核准并处理。</p>
				<p style='margin-top:5px;'>如果您在签收商品前发现该商品发生了降价调整，可以点击此处提交<a href='#shouhou' onclick=\"document.getElementById('cat5').checked = 'checked'\">[价格保护申请]</a>，我们会将差价以现金抵用券的形式返还至您的欧酷账户内。</p>
				</div>",
                WEB_ROOT . "member/my_comment.php?order_sn=" . $order['order_sn']
                
            )
        );
    }
    //取消
    elseif($order_status == OS_CANCELED)
    {
        $ret = array("订单已取消", "<div style='clear:both'>您的订单已经被取消，该订单将只存在于订单列表内，并且该订单不会做任何操作，若您想购买其他商品，可以再次选择。</div>");
    }
    //拒收
    elseif($shipping_status == SS_JUSHOU_RECEIVED)
    {
        $ret = array("用户拒收", "<div style='clear:both'>您已经提交了订单拒收申请，我们会通知快递公司将包裹做退回处理。如您有何其他需求，或者希望更改订单，可以直接致电我们的客服4008-206-206咨询。</div>");
    }
    elseif($shipping_status == SS_ZITI_QUXIAO)
    {
        $ret = array("自提已取消", "<div style='clear:both'>您已经提交了订单拒收申请，我们会通知快递公司将包裹做退回处理。如您有何其他需求，或者希望更改订单，可以直接致电我们的客服4008-206-206咨询。</div>");
    }    
    //款到发货
    elseif($shipping_status == SS_UNSHIPPED)
    {
    	if ($shortage_status == SHORTAGE_WAIT) {
    		$ret = array("暂缺货，请等待", "<div style='clear:both'>您所订购的商品暂时缺货，欧酷采购GG会尽快采购到货，请您耐心等待。</div>");
    	} elseif ($shortage_status == SHORTAGE_CONFIRMED) {
    		$ret = array("已到货，待配货", "<div style='clear:both'>您所订购的商品已经到货，欧酷网会在48小时之内完成配货和发往自提点，发票会放在信封中随商品一起寄出。请您耐心等待。</div>");
    	}
        elseif($payment['is_cod'] == 0)
        {
            //如果有父订单 则在线支付是不能根据子订单的
            if($order['parent_order_id'] > 0 && in_array($payment['pay_code'], array('alipay', 'chinabank', 'cmbchina', 'icbc')))
            {
                $p_order = get_order_detail($order['parent_order_id']);
                $pay_online = sprintf(
                    "这个订单是<a href='%smember/sOrdersInfo.php?order_sn=%s'>订单%s</a>的子订单.目前系统不支持子订单独支付，请在父订单页面进行支付。", 
                    WEB_ROOT,
                    $p_order['order_sn'],
                    $p_order['order_sn']
                );
            }
            if($pay_status != 2)
            {
              $unpaied_tips = '';
              if ($pay_code == 'alipay') {
              	$unpaied_tips = sprintf("<div style='clear:both'>您选择的商品已经记录在订单中，在您付款后，欧酷网将会按订单为您配货。若在付款中发生了错误，请您确认您的支付宝账户余额是否足够，若余额不足请先为支付宝充值，可点击<a href=\"http://www.alipay.com/\" target=\"_blank\">此处</a>登录支付宝查询。%s</div>", $pay_online);
              } elseif ($pay_code == 'cmbchina') {
                $unpaied_tips = sprintf("<div style='clear:both'>您选择的商品已经记录在订单中，在您付款后，欧酷网将会按订单为您配货。请在付款前确认您的帐户余额，并且设置相应的支付限额，招商银行支付限额可点此<a href=\"".WEB_ROOT."help/index.php?id=14#xiane\" target=\"_blank\">查看</a>。如果您已经付款，我们会在确认收款后为您备货。%s</div>", $pay_online);
              } elseif ($pay_code == 'icbc') {
                $unpaied_tips = sprintf("<div style='clear:both'>您选择的商品已经记录在订单中，在您付款后，欧酷网将会按订单为您配货。请在付款前确认您的帐户余额，并且设置相应的支付限额，工商银行支付限额可点此<a href=\"".WEB_ROOT."help/index.php?id=14#xiane\" target=\"_blank\">查看</a>。如果您已经付款，我们会在确认收款后为您备货。%s</div>", $pay_online);
              } elseif ($pay_code == 'chinabank') {
                $unpaied_tips = sprintf("<div style='clear:both'>您选择的商品已经记录在订单中，在您付款后，欧酷网将会按订单为您配货。如果您在网上开通了网银在线支付功能，在一段时期内，您只能进行小额的网上支付。这段时期就是\"保护期\"。 保护期的作用，是保护您的资金安全，防止或减少您因为不慎泄漏了卡号和密码，而被他人盗开网上支付功能给您造成的损失，点击<a href=\"http://www.ouku.com/help/index.php?id=14\" target=\"_blank\">此处查看</a>详细网银在线内容。如果您已经付款，我们会在确认收款后为您备货。%s</div>", $pay_online);
              } elseif ($pay_code == 'post') {
                $unpaied_tips = ("<div style='clear:both'>您选择的商品已经记录在订单中，在您汇款后，欧酷网将会按订单为您配货。请在汇款单的附言处注明订单号（非常重要！） 欧酷网目前仅受理\"中国邮政汇款单\"普通汇款业务（电子汇款或加急汇款是无法处理的），若您已经汇款，请点击此处提交汇款信息，以便我们最快为您确认，邮局汇款到帐时间一般在3～5个工作日内，详细汇款说明请<a href=\"http://www.ouku.com/help/index.php?id=16\" target=\"_blank\">点击此处</a></div>");
              } elseif ($pay_code == 'bank') {
                $unpaied_tips = ("<div style='clear:both'>您选择的商品已经记录在订单中，在您付款后，欧酷网将会按订单为您配货。在办理银行汇款时，为了方便您的汇款可以及时查收，欧酷网系统为您随机减去小数金额，请您在汇款时务必按照最终详细金额来支付，银行会按照您填写的支付金额来找零，不会有任何不便。异地转账在1～2个工作日内即可确认，周末将顺延至周一，详细汇款说明请<a href=\"http://www.ouku.com/help/index.php?id=15\" target=\"_blank\">点击此处</a></div>");
              } elseif ($pay_code == 'tenpay') {
                $unpaied_tips = sprintf("<div style='clear:both'>您选择的商品已经记录在订单中，在您付款后，欧酷网将会按订单为您配货。若在付款中发生了错误，请您确认您的财付通账户余额是否足够，若余额不足请先为财付通充值，可点击<a href=\"http://www.tenpay.com/\" target=\"_blank\">此处</a>登录财付通查询。%s</div>", $pay_online);
              } else {
                $unpaied_tips = sprintf("<div style='clear:both'>您选择的商品已经记录在订单中，在您付款后，欧酷网将会按订单为您配货。若在付款中发生了错误，请您确认您的账户余额是否足够。%s</div>", $pay_online);
              }
                $ret = array("订单生成，待付款", $unpaied_tips);
            }
            else
            {
                $ret = array("已付款，待配货", 
                    sprintf("<div style='clear:both'>
您的付款已确认，正常情况下，欧酷网会在48小时之内完成配货和发货，发票会放在信封中随商品一起寄出。有时可能订购的商品会出现暂时缺货,造成发货延迟，请您耐心的等待。货到后我们会立即给您发货！</div>", $pay_online));
            }
        }
        else //货到付款
        {
            if($order_status == OS_UNCONFIRMED)
            {
                //自提
                if($shipping['support_cod'] && $shipping['support_no_cod'])
                {
                   $ret = array("订单生成，待确认",
                        "<div style='clear:both'>您所选择的商品已经记录在订单详细中，我们的客服人员会尽快与您确认订单，确认后即可为您配货。请务必在与我们客服确认商品是否到达自提点后，再前去领取。自提点联系电话只用于查询如何到达自提点和自提手续，如果您有其他方面的疑问，如产品咨询，请拨打我们的客服电话，详细信息可点击此处<a href='".WEB_ROOT."about/contact.php' target='_blank'>查看</a>。</div>");
                }
                else
                {
                    $ret = array("订单生成，待确认",
                        "<div style='clear:both'>您所选择的商品已经记录在订单详细中，我们的客服人员会尽快与您确认订单，确认后即可为您配货。您所选择的快递公司会将商品直接送货上门，欧酷商城通过第三方快递公司来进行配送，需要先付款给快递员，然后当其面开箱验货，如当场发现包裹有坏损或配件缺失，请立刻致电欧酷网客服4008-206-206投诉，欧酷客服会立即给予处理答复。</div>");
                }
            }
            else
            {
                //自提
                if($shipping['support_cod'] && $shipping['support_no_cod'])
                {  
                    $ret = array("订单已确认，待配货", "<div style='clear:both;'>您的订单已经被确认，正常情况下，欧酷网会在48小时之内完成配货和发往自提点，发票会放在信封中随商品一起寄出。有时可能订购的商品会出现暂时缺货,会造成发货的延迟，请您耐心的等待。</div>");
                }
                else
                {
                    $ret = array("订单已确认，待配货","<div style='clear:both;'>您的订单已经被确认，正常情况下，欧酷网会在48小时之内完成配货和发货，发票会放在信封中随商品一起寄出。有时可能订购的商品会出现暂时缺货,会造成发货的延迟，请您耐心的等待。到货后我们会立即给你发货。</div>");
                }
            }
        }
    }
    //配货中 ncchen 081211
    /*elseif ($shipping_status == SS_PEIHUO) {
    	$ret = array("正在配货", "<div style='clear:both;'>正在为您的订单配货。</div>");
    }*/
    elseif($shipping_status == SS_TO_DEDUCTED)
    {
        $ret = array("配货完成，待出库","<div style='clear:both;'>您所订购的商品已经全部配齐，准备出库并发货。由于快递公司取件时间在每天18点，从确认到配货完成需要时间，若您的订单在当日16点后确认，将需要延迟一天发货。</div>");
    }
    elseif($shipping_status == SS_DEDUCTED)
    {
        $ret = array("已出库，待发货", "<div style='clear:both'>您所订购的商品已经记录并出库，即将等候快递公司前来收件，欧酷商城会在每天下午18～19点统一发货。偶然出现临时缺货情况，以及因邮政EMS周末不收件的情况，发货时间会有相应的延迟，请您耐心等待。</div>");
    }
    elseif($shipping_status == SS_SHIPPED)
    {
        $sql_delivery = "SELECT sa.configure FROM {$GLOBALS['ecs']->table('shipping_area')} sa, {$GLOBALS['ecs']->table('area_region')} ar 
	        	WHERE sa.shipping_area_id = ar.shipping_area_id 
	        		AND sa.shipping_id = '{$order['shipping_id']}'
	        		AND ar.region_id IN ('{$order['country']}', '{$order['province']}', '{$order['city']}', '{$order['district']}')
	      ";
				$configures = $GLOBALS['db']->getOne($sql_delivery); //获得寄送到该地区所需要的时间 
        if ($configures != null) {
        	$configures = unserialize($configures);
        	foreach ($configures as $key=>$configure) {
        		if ($configure['name'] == 'delivery_time') {
        			$delivery_time = $configure['value'];
        			break;
        		}
        		$delivery_time = '';
        	}
        } else {
        	$delivery_time = '';
        }
        //自提
        if($shipping['support_cod'] && $shipping['support_no_cod'])
        {
            $ret = array("已发往自提点", "<div style='clear:both'>快递公司已经收件，上海自提点将在第二天送达，其他地区自提点将在2～3天内送达，请您耐心等待我们自提点的到货通知，再前去领取即可。<br>自提地点：{$mid_way}</div>");
        }
        //货到付款
        elseif($payment['is_cod'] == 1)
        {
            $ret = array(
                "已发货，待签收", 
                sprintf("<div style='clear:both'>已发货，正常情况下货物预计会在{$delivery_time}天左右送达，届时请确保您的电话的畅通，以便欧酷客服或快递公司与您联系。快递员从收件到送至快递派发中心记录会稍有延迟，大约2小时后您就可以通过快递单号查询您的快递状态。快递公司：<a href=\"%s\" target=\"_blank\">%s</a> 快递单号：%s 快递公司查询电话：%s。</div>",
                    $order['carrier_info']['web_site'], $order['carrier_info']['name'], $order['carrier_bill']['bill_no'], $order['carrier_info']['phone_no'])
            );
        }
        else
        {
            $ret = array(
                "已发货，待签收", 
                sprintf("<div style='clear:both'>已发货，正常情况下货物预计会在{$delivery_time}天左右送达，届时请确保您的电话的畅通，以便欧酷客服或快递公司与您联系。快递员从收件到送至快递派发中心记录会稍有延迟，大约2小时后您就可以通过快递单号查询您的快递状态。快递公司：<a href=\"%s\" target=\"_blank\">%s</a> 快递单号：%s 快递公司查询电话：%s。</div>",
                    $order['carrier_info']['web_site'], $order['carrier_info']['name'], $order['carrier_bill']['bill_no'], $order['carrier_info']['phone_no'])
            );
        }
    }
    elseif ($shipping_status == SS_ZITI_QUEREN)
    {
        $ret = array(
                "已发往自提点", 
                "<div style='clear:both'>快递公司已经收件，上海自提点将在第二天送达，其他地区自提点将在2～3天内送达，请您耐心等待我们自提点的到货通知，再前去领取即可。</div>");
    }
    elseif($shipping_status == SS_TO_DEDUCTED)
    {
        $ret = array("配货完成，待出库", "<div style='clear:both'>您所订购的商品已经全部配齐，准备出库并发货。由于快递公司取件时间在每天18点，从确认到配货完成需要时间，若您的订单在当日16点后确认，将需要延迟一天发货。</div>");
    }
    elseif($shipping_status == SS_ZITI_DENGDAI) //自提点已到货,等待用户自提
    {
        $ret = array("已到货,等待用户自提", "<div style='clear:both'>自提点已收到货物，请您耐心等待，务必在等到我们自提点给您去取货的通知后，带好你的身份证件前去取货。如果你是请人代取，请代取的人协带身份证件和您的身份证件，再前去取货。<br>自提地点：{$mid_way}</div>");
    }
    $ret['pay_online'] = $pay_online;

    return $ret;
}


function add_carrier_bill(&$order)
{
	/* 对发货号处理 */
	if (!empty($order['carrier_bill_id'])) {
		// {{{ 货运单信息
		$order['carrier_bill'] = $GLOBALS['db']->getRow("SELECT * FROM ".$GLOBALS['ecs']->table('carrier_bill') ."
				WHERE bill_id = '{$order['carrier_bill_id']}'");
		if ($order['carrier_bill'] && isset($order['carrier_bill']["carrier_id"]))
		$order['carrier_info'] = $GLOBALS['db']->getRow("SELECT * FROM ".$GLOBALS['ecs']->table('carrier') ."
				WHERE carrier_id = '{$order['carrier_bill']["carrier_id"]}'");
		// }}}
	}
    return $order;
}

?>
