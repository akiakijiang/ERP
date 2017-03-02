<?php


define('IN_ECS', true);

require('includes/init.php');
require_once(ROOT_PATH . 'includes/lib_order.php');
/*------------------------------------------------------ */
//-- 订单查询
/*------------------------------------------------------ */

if ($_REQUEST['act'] == 'order_query')
{
    /* 检查权限 */
    ###admin_priv('order_view');

    admin_priv('csmo_add_order'); // add by Zandy

    /* 载入配送方式 */
    $smarty->assign('shipping_list', shipping_list());

    /* 载入支付方式 */
    $smarty->assign('pay_list', payment_list());

    /* 载入国家 */
    $smarty->assign('country_list', get_regions());

    /* 载入订单状态、付款状态、发货状态 */
    $smarty->assign('os_list', get_status_list('order'));
    $smarty->assign('ps_list', get_status_list('payment'));
    $smarty->assign('ss_list', get_status_list('shipping'));

    /* 模板赋值 */
    $smarty->assign('ur_here', $_LANG['03_order_query']);
    $smarty->assign('action_link', array('href' => 'order.php?act=list', 'text' => $_LANG['02_order_list']));

    /* 显示模板 */
    assign_query_info();
    $smarty->display('order_query.htm');
}

/*------------------------------------------------------ */
//-- 获取XML里面的BARCODE，根据BARCODE取出采购订单相应数据
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'generate_c'){
	include_once(ROOT_PATH . 'includes/cls_json.php');
	require_once('function.php');
	require_once(ROOT_PATH.'includes/debug/lib_log.php');
	
	$json = new JSON();	
	
	$result = array('error'=>"",  'content'=>array() , 'warehouse'=>"" , 'vouchID'=>"");

	$filename = $_REQUEST['filename'];
	
	$filename = ROOT_PATH."../gymboree/".$filename.".xml";
	
	$xml = simplexml_load_file($filename);
	
	if(!$xml){
		$result['error'] = "文件加载不成功";
	}
	
	if(!$xml->xpath("InOutVouch")){
		$result['error'] = "采购订单文件不合法，未找到InOutVouch";
	}
	
	
	if(!$xml->xpath("InOutVouchDetail")){
		$result['error'] = "采购订单文件不合法，未找到InOutVouchDetail";
	}
	
	$vouchID = "".$xml->InOutVouch->Row->fchrInOutVouchID;
	$result['vouchID'] = $vouchID;
	$sql = "
		select count(*) from ecshop.brand_gymboree_inoutvouch where fchrInOutVouchID = '{$vouchID}' 
	";
	if($db->getOne($sql) != '0'){
		$result['error'] = "该入库通知单已经下了采购单，请核对";
	}
	
	if ($result['error'] == ""){
		$result['warehouse'] = "".$xml->InOutVouch->Row->fchrWarehouseID;
		$result['vouchID'] = "".$xml->InOutVouch->Row->fchrInOutVouchID;
		foreach ($xml->InOutVouchDetail->Row as $InOutVouchDetail){
		
			$number = $InOutVouchDetail->flotQuantity;
			$itemID = $InOutVouchDetail->fchrItemID;
			$fchrFree2 = $InOutVouchDetail->fchrFree2;
			$vouchDetailID = "".$InOutVouchDetail->fchrInOutVouchDetailID;
	
			$barcode_sql = "select fchrBarCodeNO 
			from ecshop.brand_gymboree_product 
			where fchrItemID = '{$itemID}' 
			and fchrFree2 = '{$fchrFree2}'" ;
			
			$barcode = $db->getOne($barcode_sql);
			
			if(!empty($barcode)){
				$sql = "select g.goods_name,s.color,g.goods_id,s.style_id
					from ecshop.ecs_goods_style gs 
					inner join ecshop.ecs_goods g on g.goods_id = gs.goods_id
					inner join ecshop.ecs_style s on s.style_id = gs.style_id
					where gs.barcode = '{$barcode}' and gs.is_delete=0
					and g.goods_party_id = '65574'";
				
				$goods_attr = $db->getAll($sql);
				
				if(array_key_exists(1, $goods_attr)){
					$result['error'] = "根据SKU条码:{$barcode}找到了多条商品信息";
					break;
				}
				
				if(!empty($goods_attr)){
					$added_good = array();
					$added_good[0] = $goods_attr[0]['goods_name'];
					$added_good[1] = $goods_attr[0]['goods_id'];
					$added_good[2] = $goods_attr[0]['color'];
					$added_good[3] = $goods_attr[0]['style_id'];
					$added_good[4] = floatval($number);
					$added_good[5] = 0;
					$added_good[6] = 'false'; 
					$added_good[7] = '无';
					$added_good[8] = 0;
					$added_good[9] = 0;
					$added_good[10] = 1.17;
					$added_good[11] = '';
					$added_good[12] = $vouchDetailID;
					
					array_push($result['content'],$added_good);
				}else{
					$result['error'] = "根据barcode：".$barcode."找不到商品";
					break;
				}
			}else{
				$result['error'] = "根据ItemId：{$itemID}和尺码：{$fchrFree2}没能找到商品barcode";
				break;
			}
		} 	
	}
	
	echo $json->encode($result);
}

/*------------------------------------------------------ */
//-- 根据运营人员的操作，将金宝贝方面的采购数据返回给品牌商
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'back_c'){
	include_once(ROOT_PATH . 'includes/cls_json.php');
	require_once('function.php');
	require_once(ROOT_PATH.'includes/debug/lib_log.php');
	
	$json = new JSON();	
	
	$gymboree_inoutvouchID = $_REQUEST['gymboree_inoutvouchID'];
	
	$sql = "update ecshop.brand_gymboree_inoutvouch set is_send = 'true',upload_timeStamp = NOW() where fchrInOutVouchID = '{$gymboree_inoutvouchID}'";
	
	$db->query($sql);
	
	echo $json->encode("");
}

/*------------------------------------------------------ */
//-- 订单列表
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'list')
{
    /* 检查权限 */
    ###admin_priv('order_view');

    admin_priv('csmo_add_order'); // add by Zandy

    /* 取得过滤条件 */
    $filter = array();

    /* 模板赋值 */
    $smarty->assign('ur_here', $_LANG['02_order_list']);
    $smarty->assign('action_link', array('href' => 'order.php?act=order_query', 'text' => $_LANG['03_order_query']));

    $smarty->assign('filter', $filter); // 过滤条件
    $smarty->assign('status_list', $_LANG['cs']);   // 订单状态

    $smarty->assign('os_unconfirmed',   OS_UNCONFIRMED);
    $smarty->assign('cs_await_pay',     CS_AWAIT_PAY);
    $smarty->assign('cs_await_ship',    CS_AWAIT_SHIP);
    $smarty->assign('full_page',        1);

    $order_list = order_list();

    // 添加判断管理员权限，只有管理员才能移除订单
    if ($_SESSION['action_list'] == 'all') {
        $smarty->assign('is_super',   true);
    }
    $smarty->assign('order_list',   $order_list['orders']);
    $smarty->assign('filter',       $order_list['filter']);
    $smarty->assign('record_count', $order_list['record_count']);
    $smarty->assign('page_count',   $order_list['page_count']);
    $smarty->assign('sort_order_time', '<img src="images/sort_desc.gif">');

    /* 显示模板 */
    assign_query_info();
    $smarty->display('order_list.htm');
}

/*------------------------------------------------------ */
//-- 排序、分页、查询
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    $order_list = order_list();

    $smarty->assign('order_list',   $order_list['orders']);
    $smarty->assign('filter',       $order_list['filter']);
    $smarty->assign('record_count', $order_list['record_count']);
    $smarty->assign('page_count',   $order_list['page_count']);
    $sort_flag  = sort_flag($order_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('order_list.htm'), '', array('filter' => $order_list['filter'], 'page_count' => $order_list['page_count']));
}

/*------------------------------------------------------ */
//-- 订单详情页面
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'info')
{
    /* 根据订单id或订单号查询订单信息 */
    if (isset($_REQUEST['order_id']))
    {
        $order_id = intval($_REQUEST['order_id']);
        $order = order_info($order_id);
    }
    elseif (isset($_REQUEST['order_sn']))
    {
        $order_sn = trim($_REQUEST['order_sn']);
        $order = order_info(0, $order_sn);
    }
    else
    {
        /* 如果参数不存在，退出 */
        die('invalid parameter');
    }

    /* 如果订单不存在，退出 */
    if (empty($order))
    {
        die('order does not exist');
    }

    /* 根据订单是否完成检查权限
    if (order_finished($order))
    {
    admin_priv('order_view_finished');
    }
    else
    {
    admin_priv('order_view');
    }*/

    /* 取得用户名 */
    if ($order['user_id'] > 0)
    {
        $user = user_info($order['user_id']);
        if (!empty($user))
        {
            $order['user_name'] = $user['user_name'];
        }
    }

    /* 取得区域名 */
    $sql = "SELECT concat(IFNULL(c.region_name, ''), '  ', IFNULL(p.region_name, ''), " .
    "'  ', IFNULL(t.region_name, ''), '  ', IFNULL(d.region_name, '')) AS region " .
    "FROM " . $ecs->table('order_info') . " AS o " .
    "LEFT JOIN " . $ecs->table('region') . " AS c ON o.country = c.region_id " .
    "LEFT JOIN " . $ecs->table('region') . " AS p ON o.province = p.region_id " .
    "LEFT JOIN " . $ecs->table('region') . " AS t ON o.city = t.region_id " .
    "LEFT JOIN " . $ecs->table('region') . " AS d ON o.district = d.region_id " .
    "WHERE o.order_id = '$order[order_id]'";
    $order['region'] = $db->getOne($sql);

    /* 格式化金额 */
    if ($order['order_amount'] < 0)
    {
        $order['money_refund']          = abs($order['order_amount']);
        $order['formated_money_refund'] = price_format(abs($order['order_amount']));
    }

    /* 其他处理 */
    $order['pay_time']      = $order['pay_time'] > 0 ? date('Y-m-d H:i:s', $order['pay_time']) : $_LANG['ps'][PS_UNPAYED];
    $order['shipping_time'] = $order['shipping_time'] > 0 ? date('Y-m-d H:i:s', $order['shipping_time']) : $_LANG['ss'][SS_UNSHIPPED];
    $order['status']        = $_LANG['os'][$order['order_status']] . ',' . $_LANG['ps'][$order['pay_status']] . ',' . $_LANG['ss'][$order['shipping_status']];
    $order['invoice_no']    = $order['shipping_status'] == SS_UNSHIPPED ? $_LANG['ss'][SS_UNSHIPPED] : $order['invoice_no'];

    /* 取得订单的来源 */
    if ($order['from_ad'] == 0)
    {
        $order['referer'] = empty($order['referer']) ? $_LANG['from_self_site'] : $order['referer'];
    }
    elseif ($order['from_ad'] == -1)
    {
        $order['referer'] = $_LANG['from_goods_js'] . ' ('.$_LANG['from'] . $order['referer'].')';
    }
    else
    {
        /* 查询广告的名称 */
        $ad_name = $db->getOne("SELECT ad_name FROM " .$ecs->table('ad'). " WHERE ad_id='$order[from_ad]'");
        $order['referer'] = $_LANG['from_ad_js'] . $ad_name . ' ('.$_LANG['from'] . $order['referer'].')';
    }

    /* 此订单的发货备注(此订单的最后一条操作记录) */
    $sql = "SELECT action_note FROM " . $ecs->table('order_action').
    " WHERE order_id = '$order[order_id]' AND shipping_status = 1 ORDER BY action_time DESC";
    $order['invoice_note'] = $db->getOne($sql);

    /* 取得订单商品总重量 */
    $weight_price = order_weight_price($order['order_id']);
    $order['total_weight'] = $weight_price['formated_weight'];

    /* 参数赋值：订单 */
    $smarty->assign('order', $order);

    /* 取得用户信息 */
    if ($order['user_id'] > 0)
    {
        /* 用户等级 */
        if ($user['user_rank'] > 0)
        {
            $where = " WHERE rank_id = '$user[user_rank]' ";
        }
        else
        {
            $where = " WHERE min_points <= " . intval($user['rank_points']) . " ORDER BY min_points DESC ";
        }
        $sql = "SELECT rank_name FROM " . $ecs->table('user_rank') . $where;
        $user['rank_name'] = $db->getOne($sql);

        // 用户红包数量
        $today = date('Y-m-d');
        $sql = "SELECT COUNT(*) " .
        "FROM " . $ecs->table('bonus_type') . " AS bt, " . $ecs->table('user_bonus') . " AS ub " .
        "WHERE bt.type_id = ub.bonus_type_id " .
        "AND ub.user_id = '$order[user_id]' " .
        "AND ub.order_id = 0 " .
        "AND bt.use_startdate <= '$today' " .
        "AND bt.use_enddate >= '$today'";
        $user['bonus_count'] = $db->getOne($sql);
        $smarty->assign('user', $user);

        // 地址信息
        $sql = "SELECT * FROM " . $ecs->table('user_address') . " WHERE user_id = '$order[user_id]'";
        $smarty->assign('address_list', $db->getAll($sql));
    }

    /* 取得订单商品 */
    $goods_list = array();
    $goods_attr = array();
    $sql = "SELECT o.*, g.goods_number AS storage, o.goods_attr, IFNULL(b.brand_name, '') AS brand_name " .
    "FROM " . $ecs->table('order_goods') . " AS o ".
    "LEFT JOIN " . $ecs->table('goods') . " AS g ON o.goods_id = g.goods_id " .
    "LEFT JOIN " . $ecs->table('brand') . " AS b ON g.brand_id = b.brand_id " .
    "WHERE o.order_id = '$order[order_id]' ";
    $res = $db->query($sql);
    while ($row = $db->fetchRow($res))
    {
        /* 虚拟商品支持 */
        if ($row['is_real'] == 0)
        {
            /* 取得语言项 */
            $filename = ROOT_PATH . 'plugins/' . $row['extension_code'] . '/languages/common_' . $_CFG['lang'] . '.php';
            if (file_exists($filename))
            {
                include_once($filename);
                if (!empty($_LANG[$row['extension_code'].'_link']))
                {
                    $row['goods_name'] = $row['goods_name'] . sprintf($_LANG[$row['extension_code'].'_link'], $row['goods_id'], $order['order_sn']);
                }
            }
        }

        $row['formated_subtotal']       = price_format($row['goods_price'] * $row['goods_number']);
        $row['formated_goods_price']    = price_format($row['goods_price']);

        $goods_attr[] = explode(' ', trim($row['goods_attr'])); //将商品属性拆分为一个数组
        $goods_list[] = $row;
    }

    $attr = array();
    $arr  = array();
    foreach ($goods_attr AS $index => $array_val)
    {
        foreach ($array_val AS $value)
        {
            $arr = explode(':', $value);//以 : 号将属性拆开
            $attr[$index][] =  @array('name' => $arr[0], 'value' => $arr[1]);
        }
    }

    $smarty->assign('goods_attr', $attr);
    $smarty->assign('goods_list', $goods_list);

    /* 取得能执行的操作列表 */
    $operable_list = operable_list($order);
    $smarty->assign('operable_list', $operable_list);

    /* 取得订单操作记录 */
    $act_list = array();
    $action_user = '';
    $sql = "SELECT * FROM " . $ecs->table('order_action') . " WHERE order_id = '$order[order_id]' ORDER BY action_time DESC";
    $res = $db->query($sql);
    while ($row = $db->fetchRow($res))
    {
        $row['order_status']    = $_LANG['os'][$row['order_status']];
        $row['pay_status']      = $_LANG['ps'][$row['pay_status']];
        $row['shipping_status'] = $_LANG['ss'][$row['shipping_status']];
        $act_list[] = $row;

        $action_user = $row['action_user'];
    }

    $smarty->assign('action_list', $act_list);
    $smarty->assign('action_user', $action_user);

    /* 取得上一个、下一个订单号 */
    $smarty->assign('prev_id', $db->getOne("SELECT MAX(order_id) FROM " . $ecs->table('order_info') . " WHERE order_id < '$order[order_id]'"));
    $smarty->assign('next_id', $db->getOne("SELECT MIN(order_id) FROM " . $ecs->table('order_info') . " WHERE order_id > '$order[order_id]'"));

    /* 取得是否存在实体商品 */
    $smarty->assign('exist_real_goods', exist_real_goods($order['order_id']));

    /* 是否打印订单，分别赋值 */
    if (isset($_GET['print']))
    {
        $smarty->assign('shop_name',    $_CFG['shop_name']);
        $smarty->assign('shop_url',     $ecs->url());
        $smarty->assign('shop_address', $_CFG['shop_address']);
        $smarty->assign('service_phone',$_CFG['service_phone']);
        $smarty->assign('print_time',   date($_CFG['time_format'], time()));

        $smarty->template_dir = '../data';
        $smarty->display('order_print.html');
    }
    else
    {
        /* 模板赋值 */
        $smarty->assign('ur_here', $_LANG['order_info']);
        $smarty->assign('action_link', array('href' => 'order.php?act=list', 'text' => $_LANG['02_order_list']));

        /* 显示模板 */
        assign_query_info();
        $smarty->display('order_info.htm');
    }
}

/*------------------------------------------------------ */
//-- 修改订单（处理提交）
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'step_post')
{
    /* 检查权限 */
    ###admin_priv('order_edit');

    admin_priv('csmo_add_order'); // add by Zandy

    /* 取得参数 step */
    $step_list = array('user', 'edit_goods', 'add_goods', 'goods', 'consignee', 'shipping', 'payment', 'other', 'money', 'invoice');
    $step = isset($_REQUEST['step']) && in_array($_REQUEST['step'], $step_list) ? $_REQUEST['step'] : 'user';

    /* 取得参数 order_id */
    $order_id = isset($_REQUEST['order_id']) ? intval($_REQUEST['order_id']) : 0;
    if ($order_id > 0)
    {
        $old_order = order_info($order_id);
    }

    /* 取得参数 step_act 添加还是编辑 */
    $step_act = isset($_REQUEST['step_act']) ? $_REQUEST['step_act'] : 'add';

    /* 插入订单信息 */
    if ('user' == $step)
    {
        /* 取得参数：user_id */
        $user_id = ($_POST['anonymous'] == 1) ? 0 : intval($_POST['user']);

        /* 插入新订单，状态为无效 */
        $order = array(
        'user_id'           => $user_id,
        'order_time'        => date('Y-m-d H:i:s'),
        'order_status'      => OS_UNCONFIRMED,
        'shipping_status'   => SS_UNSHIPPED,
        'pay_status'        => PS_UNPAYED,
        'from_ad'           => 0,
        'referer'           => $_LANG['admin'],
        'order_type_id'     => 'SALE',
        );

        do
        {
            $order['order_sn'] = get_order_sn();
            if ($db->autoExecute($ecs->table('order_info'), $order, 'INSERT', '', 'SILENT'))
            {
                break;
            }
            else
            {
                if ($db->errno() != 1062)
                {
                    die($db->error());
                }
            }
        }
        while (true); // 防止订单号重复

        $order_id = $db->insert_id();

        /* todo 记录日志 */
        admin_log($order['order_sn'], 'add', 'order');

        /* 插入 pay_log */
        $sql = 'INSERT INTO ' . $ecs->table('pay_log') . " (order_id, order_amount, order_type, is_paid)" .
        " VALUES ('$order_id', 0, '" . PAY_ORDER . "', 0)";
        $db->query($sql);

        update_order($order_id, array('order_status' => OS_UNCONFIRMED)); // Zandy

        /* 下一步 */
        header("Location: order.php?act=" . $step_act . "&order_id=" . $order_id . "&step=goods\n");
        exit;
    }
    /* 编辑商品信息 */
    elseif ('edit_goods' == $step)
    {
        if (isset($_POST['rec_id']))
        {
            foreach ($_POST['rec_id'] AS $key => $rec_id)
            {
                /* 取得参数 */
                $goods_price = floatval($_POST['goods_price'][$key]);
                $goods_number = intval($_POST['goods_number'][$key]);
                $goods_attr = $_POST['goods_attr'][$key];

                /* 修改 */
                $sql = "UPDATE " . $ecs->table('order_goods') .
                " SET goods_price = '$goods_price', " .
                "goods_number = '$goods_number', " .
                "goods_attr = '$goods_attr' " .
                "WHERE rec_id = '$rec_id' LIMIT 1";
                $db->query($sql);
            }

            /* 更新商品总金额和订单总金额 */
            $goods_amount = order_amount($order_id);
            update_order($order_id, array('goods_amount' => $goods_amount));
            //update_order_amount($order_id);

            /* 更新 pay_log */
            update_pay_log($order_id);

            /* todo 记录日志 */
            $sn = $old_order['order_sn'];
            $new_order = order_info($order_id);
            if ($old_order['total_fee'] != $new_order['total_fee'])
            {
                $sn .= ',' . sprintf($_LANG['order_amount_change'], $old_order['total_fee'], $new_order['total_fee']);
            }
            admin_log($sn, 'edit', 'order');
        }

        update_order($order_id, array('order_status' => OS_UNCONFIRMED)); // Zandy

        /* 跳回订单商品 */
        header("Location: order.php?act=" . $step_act . "&order_id=" . $order_id . "&step=goods\n");
        exit;
    }
    /* 添加商品 */
    elseif ('add_goods' == $step)
    {
        /* 取得参数 */
        $goods_id = intval($_POST['goodslist']);
        $goods_price = $_POST['add_price'] != 'user_input' ? floatval($_POST['add_price']) : floatval($_POST['input_price']);
        $goods_attr = '0';
        for ($i = 0; $i < $_POST['spec_count']; $i++)
        {
            $goods_attr .= ',' . $_POST['spec_' . $i];
        }
        $goods_number = $_POST['add_number'];

        /* 取得属性 */
        $attr_list = array();
        if ($goods_attr != '')
        {
            $sql = "SELECT a.attr_name, g.attr_value, g.attr_price " .
            "FROM " . $ecs->table('goods_attr') . " AS g, " .
            $ecs->table('attribute') . " AS a " .
            "WHERE g.attr_id = a.attr_id " .
            "AND g.goods_attr_id " . db_create_in($goods_attr);
            $res = $db->query($sql);
            while ($row = $db->fetchRow($res))
            {
                $attr = $row['attr_name'] . ': ' . $row['attr_value'];
                $attr_price = floatval($row['attr_price']);
                if ($attr_price > 0)
                {
                    $attr .= ' [+' . $attr_price . ']';
                }
                elseif ($attr_price < 0)
                {
                    $attr .= ' [-' . abs($attr_price) . ']';
                }
                $attr_list[] = $attr;

                /* 更新价格 */
                $goods_price += $attr_price;
            }
        }
        $attr_list = addslashes_deep($attr_list);

        /* 插入订单商品 */
        $sql = "INSERT INTO " . $ecs->table('order_goods') .
        " (order_id, goods_id, goods_name, goods_sn, " .
        "goods_number, market_price, goods_price, goods_attr, " .
        "is_real, extension_code, parent_id, is_gift, provider_id)" .
        "SELECT '$order_id', goods_id, goods_name, goods_sn, " .
        "'$goods_number', market_price, '$goods_price', '" . join("\r\n", $attr_list) . "', " .
        "is_real, extension_code, 0, 0, provider_id " .
        "FROM " . $ecs->table('goods') .
        " WHERE goods_id = '$goods_id' LIMIT 1";
        $db->query($sql);

        /* 更新商品总金额和订单总金额 */
        update_order($order_id, array('goods_amount' => order_amount($order_id)));
        //update_order_amount($order_id);

        /* 更新 pay_log */
        update_pay_log($order_id);

        /* todo 记录日志 */
        $sn = $old_order['order_sn'];
        $new_order = order_info($order_id);
        if ($old_order['total_fee'] != $new_order['total_fee'])
        {
            $sn .= ',' . sprintf($_LANG['order_amount_change'], $old_order['total_fee'], $new_order['total_fee']);
        }
        admin_log($sn, 'edit', 'order');

        update_order($order_id, array('order_status' => OS_UNCONFIRMED)); // Zandy

        /* 跳回订单商品 */
        header("Location: order.php?act=" . $step_act . "&order_id=" . $order_id . "&step=goods\n");
        exit;
    }
    /* 商品 */
    elseif ('goods' == $step)
    {

        update_order($order_id, array('order_status' => OS_UNCONFIRMED)); // Zandy
        /* 下一步 */
        if (isset($_POST['next']))
        {
            header("Location: order.php?act=" . $step_act . "&order_id=" . $order_id . "&step=consignee\n");
            exit;
        }
        /* 完成 */
        elseif (isset($_POST['finish']))
        {
            /* 初始化提示信息和链接 */
            $msgs   = array();
            $links  = array();

            /* 如果已付款，检查金额是否变动，并执行相应操作 */
            $order = order_info($order_id);
            handle_order_money_change($order, $msgs, $links);

            /* 显示提示信息 */
            if (!empty($msgs))
            {
                sys_msg(join(chr(13), $msgs), 0, $links);
            }
            else
            {
                /* 跳转到订单详情 */
                header("Location: order.php?act=info&order_id=" . $order_id . "\n");
                exit;
            }
        }
    }
    /* 保存收货人信息 */
    elseif ('consignee' == $step)
    {
        /* 保存订单 */
        $order = $_POST;
        update_order($order_id, $order);

        /* todo 记录日志 */
        $sn = $old_order['order_sn'];
        admin_log($sn, 'edit', 'order');

        if (isset($_POST['next']))
        {

            update_order($order_id, array('order_status' => OS_UNCONFIRMED)); // Zandy
            /* 下一步 */
            if (exist_real_goods($order_id))
            {
                /* 存在实体商品，去配送方式 */
                header("Location: order.php?act=" . $step_act . "&order_id=" . $order_id . "&step=shipping\n");
                exit;
            }
            else
            {
                /* 不存在实体商品，去支付方式 */
                header("Location: order.php?act=" . $step_act . "&order_id=" . $order_id . "&step=payment\n");
                exit;
            }
        }
        elseif (isset($_POST['finish']))
        {
            /* 如果是编辑且存在实体商品，检查收货人地区的改变是否影响原来选的配送 */
            if ('edit' == $step_act && exist_real_goods($order_id))
            {
                $order = order_info($order_id);

                /* 取得可用配送方式 */
                $region_id_list = array(
                $order['country'], $order['province'], $order['city'], $order['district']
                );
                $shipping_list = available_shipping_list($region_id_list);

                /* 判断订单的配送是否在可用配送之内 */
                $exist = false;
                foreach ($shipping_list AS $shipping)
                {
                    if ($shipping['shipping_id'] == $order['shipping_id'])
                    {
                        $exist = true;
                        break;
                    }
                }

                /* 如果不在可用配送之内，提示用户去修改配送 */
                if (!$exist)
                {
                    // 修改配送为空，配送费和保价费为0
                    update_order($order_id, array('shipping_id' => 0, 'shipping_name' => ''));
                    $links[] = array('text' => $_LANG['step']['shipping'], 'href' => 'order.php?act=edit&order_id=' . $order_id . '&step=shipping');
                    sys_msg($_LANG['continue_shipping'], 1, $links);
                }
            }

            update_order($order_id, array('order_status' => OS_UNCONFIRMED)); // Zandy

            /* 完成 */
            header("Location: order.php?act=info&order_id=" . $order_id . "\n");
            exit;
        }
    }
    /* 保存配送信息 */
    elseif ('shipping' == $step)
    {
        /* 如果不存在实体商品，退出 */
        if (!exist_real_goods($order_id))
        {
            die ('Hacking Attemp');
        }

        /* 取得订单信息 */
        $order_info = order_info($order_id);
        $region_id_list = array($order_info['country'], $order_info['province'], $order_info['city'], $order_info['district']);

        /* 保存订单 */
        $shipping_id = $_POST['shipping'];
        $shipping = shipping_area_info($shipping_id, $region_id_list);
        $weight_amount = order_weight_price($order_id);
        $shipping_fee = shipping_fee($shipping['shipping_code'], $shipping['configure'], $weight_amount['weight'], $weight_amount['amount']);
        $order = array(
        'shipping_id' => $shipping_id,
        'shipping_name' => addslashes($shipping['shipping_name']),
        'shipping_fee' => $shipping_fee
        );


        // 设置上门取货的shipping_status
        if ($shipping_id == 19 || $shipping_id == 10) {
            $order['shipping_status'] = 4;
        }

        if (isset($_POST['insure']))
        {
            /* 计算保价费 */
            $order['insure_fee'] = shipping_insure_fee($shipping['shipping_code'], order_amount($order_id), $shipping['insure']);
        }
        else
        {
            $order['insure_fee'] = 0;
        }
        update_order($order_id, $order);
        //update_order_amount($order_id);

        /* 更新 pay_log */
        update_pay_log($order_id);

        /* 清除首页缓存：发货单查询 */
        clear_cache_files('index.dwt');

        /* todo 记录日志 */
        $sn = $old_order['order_sn'];
        $new_order = order_info($order_id);
        if ($old_order['total_fee'] != $new_order['total_fee'])
        {
            $sn .= ',' . sprintf($_LANG['order_amount_change'], $old_order['total_fee'], $new_order['total_fee']);
        }
        admin_log($sn, 'edit', 'order');

        if (isset($_POST['next']))
        {
            /* 下一步 */
            header("Location: order.php?act=" . $step_act . "&order_id=" . $order_id . "&step=payment\n");
            exit;
        }
        elseif (isset($_POST['finish']))
        {
            /* 初始化提示信息和链接 */
            $msgs   = array();
            $links  = array();

            /* 如果已付款，检查金额是否变动，并执行相应操作 */
            $order = order_info($order_id);
            handle_order_money_change($order, $msgs, $links);

            /* 如果是编辑且配送不支持货到付款且原支付方式是货到付款 */
            if ('edit' == $step_act && $shipping['support_cod'] == 0)
            {
                $payment = payment_info($order['pay_id']);
                if ($payment['is_cod'] == 1)
                {
                    /* 修改支付为空 */
                    update_order($order_id, array('pay_id' => 0, 'pay_name' => ''));
                    $msgs[]     = $_LANG['continue_payment'];
                    $links[]    = array('text' => $_LANG['step']['payment'], 'href' => 'order.php?act=' . $step_act . '&order_id=' . $order_id . '&step=payment');
                }
            }

            /* 显示提示信息 */
            if (!empty($msgs))
            {
                sys_msg(join(chr(13), $msgs), 0, $links);
            }
            else
            {

                update_order($order_id, array('order_status' => OS_UNCONFIRMED)); // Zandy
                /* 完成 */
                header("Location: order.php?act=info&order_id=" . $order_id . "\n");
                exit;
            }
        }

        update_order($order_id, array('order_status' => OS_UNCONFIRMED)); // Zandy
    }
    /* 保存支付信息 */
    elseif ('payment' == $step)
    {
        /* 取得支付信息 */
        $pay_id = $_POST['payment'];
        $payment = payment_info($pay_id);

        /* 计算支付费用 */
        $order_amount = order_amount($order_id);
        if ($payment['is_cod'] == 1)
        {
            $order = order_info($order_id);
            $region_id_list = array(
            $order['country'], $order['province'], $order['city'], $order['district']
            );
            $shipping = shipping_area_info($order['shipping_id'], $region_id_list);
            $pay_fee = pay_fee($pay_id, $order_amount, $shipping['pay_fee']);
        }
        else
        {
            $pay_fee = pay_fee($pay_id, $order_amount);
        }

        /* 保存订单 */
        $order = array(
        'pay_id' => $pay_id,
        'pay_name' => addslashes($payment['pay_name']),
        'pay_fee' => $pay_fee
        );
        update_order($order_id, $order);
        //update_order_amount($order_id);

        /* 更新 pay_log */
        update_pay_log($order_id);

        /* todo 记录日志 */
        $sn = $old_order['order_sn'];
        $new_order = order_info($order_id);
        if ($old_order['total_fee'] != $new_order['total_fee'])
        {
            $sn .= ',' . sprintf($_LANG['order_amount_change'], $old_order['total_fee'], $new_order['total_fee']);
        }
        admin_log($sn, 'edit', 'order');

        if (isset($_POST['next']))
        {
            /* 下一步 */
            header("Location: order.php?act=" . $step_act . "&order_id=" . $order_id . "&step=other\n");
            exit;
        }
        elseif (isset($_POST['finish']))
        {
            /* 初始化提示信息和链接 */
            $msgs   = array();
            $links  = array();

            /* 如果已付款，检查金额是否变动，并执行相应操作 */
            $order = order_info($order_id);
            handle_order_money_change($order, $msgs, $links);

            /* 显示提示信息 */
            if (!empty($msgs))
            {
                sys_msg(join(chr(13), $msgs), 0, $links);
            }
            else
            {
                update_order($order_id, array('order_status' => OS_UNCONFIRMED)); // Zandy
                /* 完成 */
                header("Location: order.php?act=info&order_id=" . $order_id . "\n");
                exit;
            }
        }

        update_order($order_id, array('order_status' => OS_UNCONFIRMED)); // Zandy
    }
    elseif ('other' == $step)
    {
        /* 保存订单 */
        $order = array();
        if (isset($_POST['pack']) && $_POST['pack'] > 0)
        {
            $pack               = pack_info($_POST['pack']);
            $order['pack_id']   = $pack['pack_id'];
            $order['pack_name'] = addslashes($pack['pack_name']);
            $order['pack_fee']  = $pack['pack_fee'];
        }
        else
        {
            $order['pack_id']   = 0;
            $order['pack_name'] = '';
            $order['pack_fee']  = 0;
        }
        if (isset($_POST['card']) && $_POST['card'] > 0)
        {
            $card               = card_info($_POST['card']);
            $order['card_id']   = $card['card_id'];
            $order['card_name'] = addslashes($card['card_name']);
            $order['card_fee']  = $card['card_fee'];
        }
        else
        {
            $order['card_id']   = 0;
            $order['card_name'] = '';
            $order['card_fee']  = 0;
        }
        if (isset($_POST['inv_payee']))
        {
            $order['inv_payee']     = $_POST['inv_payee'];
            $order['inv_content']   = $_POST['inv_content'];
        }
        else
        {
            $order['inv_payee']     = '';
            $order['inv_content']   = '';
        }
        $order['how_oos']       = $_POST['how_oos'];
        $order['postscript']    = $_POST['postscript'];
        $order['to_buyer']      = $_POST['to_buyer'];
        update_order($order_id, $order);
        //update_order_amount($order_id);

        /* 更新 pay_log */
        update_pay_log($order_id);

        /* todo 记录日志 */
        $sn = $old_order['order_sn'];
        admin_log($sn, 'edit', 'order');

        if (isset($_POST['next']))
        {
            /* 下一步 */
            header("Location: order.php?act=" . $step_act . "&order_id=" . $order_id . "&step=money\n");
            exit;
        }
        elseif (isset($_POST['finish']))
        {

            update_order($order_id, array('order_status' => OS_UNCONFIRMED)); // Zandy
            /* 完成 */
            header("Location: order.php?act=info&order_id=" . $order_id . "\n");
            exit;
        }

        update_order($order_id, array('order_status' => OS_UNCONFIRMED)); // Zandy
    }
    elseif ('money' == $step)
    {
        /* 取得订单信息 */
        $old_order = order_info($order_id);
        if ($old_order['user_id'] > 0)
        {
            /* 取得用户信息 */
            $user = user_info($old_order['user_id']);
        }

        /* 保存信息 */
        $order['goods_amount']  = $old_order['goods_amount'];
        $order['shipping_fee']  = isset($_POST['shipping_fee']) && floatval($_POST['shipping_fee']) >= 0 ? round(floatval($_POST['shipping_fee']), 2) : 0;
        $order['insure_fee']    = isset($_POST['insure_fee']) && floatval($_POST['insure_fee']) >= 0 ? round(floatval($_POST['insure_fee']), 2) : 0;
        $order['pay_fee']       = floatval($_POST['pay_fee']) >= 0 ? round(floatval($_POST['pay_fee']), 2) : 0;
        $order['pack_fee']      = isset($_POST['pack_fee']) && floatval($_POST['pack_fee']) >= 0 ? round(floatval($_POST['pack_fee']), 2) : 0;
        $order['card_fee']      = isset($_POST['card_fee']) && floatval($_POST['card_fee']) >= 0 ? round(floatval($_POST['card_fee']), 2) : 0;

        $order['money_paid']    = $old_order['money_paid'];
        $order['surplus']       = 0;
        $order['integral']      = 0;
        $order['integral_money']= 0;
        $order['bonus_id']      = 0;
        $order['bonus']         = 0;

        /* 计算待付款金额 */
        $order['order_amount']  = $order['goods_amount']
        + $order['shipping_fee']
        + $order['insure_fee']
        + $order['pay_fee']
        + $order['pack_fee']
        + $order['card_fee']
        - $order['money_paid'];
        if ($order['order_amount'] > 0)
        {
            if ($old_order['user_id'] > 0)
            {
                /* 如果选择了红包，先使用红包支付 */
                if ($_POST['bonus_id'] > 0)
                {
                    /* todo 检查红包是否可用 */
                    $order['bonus_id']      = $_POST['bonus_id'];
                    $bonus                  = bonus_info($_POST['bonus_id']);
                    $order['bonus']         = $bonus['type_money'];

                    $order['order_amount']  -= $order['bonus'];
                }

                /* 使用红包之后待付款金额仍大于0 */
                if ($order['order_amount'] > 0)
                {
                    /* 如果设置了积分，再使用积分支付 */
                    if (isset($_POST['integral']) && intval($_POST['integral']) > 0)
                    {
                        /* 检查积分是否足够 */
                        $order['integral']          = intval($_POST['integral']);
                        $order['integral_money']    = value_of_integral(intval($_POST['integral']));
                        if ($old_order['integral'] + $user['pay_points'] < $order['integral'])
                        {
                            sys_msg($_LANG['pay_points_not_enough']);
                        }

                        $order['order_amount'] -= $order['integral_money'];
                    }

                    if ($order['order_amount'] > 0)
                    {
                        /* 如果设置了余额，再使用余额支付 */
                        if (isset($_POST['surplus']) && floatval($_POST['surplus']) >= 0)
                        {
                            /* 检查余额是否足够 */
                            $order['surplus'] = round(floatval($_POST['surplus']), 2);
                            if ($old_order['surplus'] + $user['user_money'] < $order['surplus'])
                            {
                                sys_msg($_LANG['user_money_not_enough']);
                            }

                            /* 如果红包和积分和余额足以支付，把待付款金额改为0，退回部分积分余额 */
                            $order['order_amount'] -= $order['surplus'];
                            if ($order['order_amount'] < 0)
                            {
                                $order['surplus']       += $order['order_amount'];
                                $order['order_amount']  = 0;
                            }
                        }
                    }
                    else
                    {
                        /* 如果红包和积分足以支付，把待付款金额改为0，退回部分积分 */
                        $order['integral_money']    += $order['order_amount'];
                        $order['integral']          = integral_of_value($order['integral_money']);
                        $order['order_amount']      = 0;
                    }
                }
                else
                {
                    /* 如果红包足以支付，把待付款金额设为0 */
                    $order['order_amount'] = 0;
                }
            }
        }

        $order['order_status']      = OS_UNCONFIRMED; // Zandy

        update_order($order_id, $order);

        /* 更新 pay_log */
        update_pay_log($order_id);

        /* todo 记录日志 */
        $sn = $old_order['order_sn'];
        $new_order = order_info($order_id);
        if ($old_order['total_fee'] != $new_order['total_fee'])
        {
            $sn .= ',' . sprintf($_LANG['order_amount_change'], $old_order['total_fee'], $new_order['total_fee']);
        }
        admin_log($sn, 'edit', 'order');

        /* 如果余额、积分、红包有变化，做相应更新 */
        if ($old_order['user_id'] > 0)
        {
            $new_user = array('user_id' => $user['user_id']);
            $new_user['user_money'] = $user['user_money'] + $old_order['surplus'] - $order['surplus'];
            $new_user['pay_points'] = $user['pay_points'] + $old_order['integral'] - $order['integral'];
            $db->autoExecute($ecs->table('users'), $new_user, 'UPDATE', "user_id = '$old_order[user_id]'");

            if ($old_order['bonus_id'] != $order['bonus_id'])
            {
                if ($old_order['bonus_id'] > 0)
                {
                    $sql = "UPDATE " . $ecs->table('user_bonus') .
                    " SET used_time = 0, order_id = 0 " .
                    "WHERE bonus_id = '$old_order[bonus_id]' LIMIT 1";
                    $db->query($sql);
                }

                if ($order['bonus_id'] > 0)
                {
                    $sql = "UPDATE " . $ecs->table('user_bonus') .
                    " SET used_time = '" . time() . "', order_id = '$order_id' " .
                    "WHERE bonus_id = '$order[bonus_id]' LIMIT 1";
                    $db->query($sql);
                }
            }
        }

        if (isset($_POST['finish']))
        {
            /* 完成 */
            if ($step_act == 'add')
            {
                /* 订单改为已确认，（已付款） */
                /////$arr['order_status'] = OS_CONFIRMED;
                $arr['order_status'] = 1; // 2007-10-23
                $arr['confirm_time'] = time();
                if ($order['order_amount'] <= 0)
                {
                    $arr['pay_status']  = PS_PAYED;
                    $arr['pay_time']    = time();
                }
                update_order($order_id, $arr);
            }

            /* 初始化提示信息和链接 */
            $msgs   = array();
            $links  = array();

            /* 如果已付款，检查金额是否变动，并执行相应操作 */
            $order = order_info($order_id);
            handle_order_money_change($order, $msgs, $links);

            /* 显示提示信息 */
            if (!empty($msgs))
            {
                sys_msg(join(chr(13), $msgs), 0, $links);
            }
            else
            {

                update_order($order_id, array('order_status' => OS_UNCONFIRMED)); // Zandy
                header("Location: order.php?act=info&order_id=" . $order_id . "\n");
                exit;
            }
        }
    }
    /* 保存发货后的配送方式和发货单号 */
    elseif ('invoice' == $step)
    {
        /* 如果不存在实体商品，退出 */
        if (!exist_real_goods($order_id))
        {
            die ('Hacking Attemp');
        }

        /* 保存订单 */
        $shipping_id    = $_POST['shipping'];
        $shipping       = shipping_info($shipping_id);
        $invoice_no     = $_POST['invoice_no'];
        $order = array(
        'shipping_id'   => $shipping_id,
        'shipping_name' => addslashes($shipping['shipping_name']),
        'invoice_no'    => $invoice_no
        );
        update_order($order_id, $order);

        /* todo 记录日志 */
        $sn = $old_order['order_sn'];
        admin_log($sn, 'edit', 'order');

        if (isset($_POST['finish']))
        {

            update_order($order_id, array('order_status' => OS_UNCONFIRMED)); // Zandy
            header("Location: order.php?act=info&order_id=" . $order_id . "\n");
            exit;
        }
    }
}

/*------------------------------------------------------ */
//-- 修改订单（载入页面）
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'add' || $_REQUEST['act'] == 'edit')
{
    /* 检查权限 */
    ###admin_priv('order_edit');

    admin_priv('csmo_add_order'); // add by Zandy

    /* 取得参数 order_id */
    $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
    $smarty->assign('order_id', $order_id);

    /* 取得参数 step */
    $step_list = array('user', 'goods', 'consignee', 'shipping', 'payment', 'other', 'money');
    $step = isset($_GET['step']) && in_array($_GET['step'], $step_list) ? $_GET['step'] : 'user';
    $smarty->assign('step', $step);

    /* 取得参数 act */
    $act = $_GET['act'];
    $smarty->assign('ur_here', (($act == 'add') ?
    $_LANG['add_order'] : $_LANG['edit_order']) .' - '. $_LANG['step'][$step]);
    $smarty->assign('step_act', $act);

    /* 取得订单信息 */
    if ($order_id > 0)
    {
        $order = order_info($order_id);

        /* 如果已发货，就不能修改订单了（配送方式和发货单号除外） */
        // 若不处于“未发货”、“等待自提”、“已自提”状态、则不能修改订单 by ychen at 2008/1/23
        if ($order['shipping_status'] != SS_UNSHIPPED && $order['shipping_status'] != SS_ZITI_QUEREN && $order['shipping_status'] != SS_ZITI_DENGDAI)
        {
            if ($step != 'shipping')
            {
                sys_msg($_LANG['cannot_edit_order_shipped']);
            }
            else
            {
                $step = 'invoice';
                $smarty->assign('step', $step);
            }
        }

        $smarty->assign('order', $order);
    }
    else
    {
        if ($act != 'add' || $step != 'user')
        {
            die('invalid params');
        }
    }

    /* 选择会员 */
    if ('user' == $step)
    {
        // 无操作
    }

    /* 增删改商品 */
    elseif ('goods' == $step)
    {
        /* 取得订单商品 */
        $goods_list = order_goods($order_id);
        foreach ($goods_list AS $key => $goods)
        {
            /* 计算属性数 */
            $attr = $goods['goods_attr'];
            if ($attr == '')
            {
                $goods_list[$key]['rows'] = 1;
            }
            else
            {
                $goods_list[$key]['rows'] = count(explode(chr(13), $attr));
            }
        }
        $smarty->assign('goods_list', $goods_list);

        /* 取得商品总金额 */
        $smarty->assign('goods_amount', order_amount($order_id));
    }

    // 设置收货人
    elseif ('consignee' == $step)
    {
        /* 查询是否存在实体商品 */
        $exist_real_goods = exist_real_goods($order_id);
        $smarty->assign('exist_real_goods', $exist_real_goods);

        /* 取得收货地址列表 */
        if ($order['user_id'] > 0)
        {
            $smarty->assign('address_list', address_list($order['user_id']));

            $address_id = isset($_REQUEST['address_id']) ? intval($_REQUEST['address_id']) : 0;
            if ($address_id > 0)
            {
                $address = address_info($address_id);
                if ($address)
                {
                    $order['consignee']     = $address['consignee'];
                    $order['country']       = $address['country'];
                    $order['province']      = $address['province'];
                    $order['city']          = $address['city'];
                    $order['district']      = $address['district'];
                    $order['email']         = $address['email'];
                    $order['address']       = $address['address'];
                    $order['zipcode']       = $address['zipcode'];
                    $order['tel']           = $address['tel'];
                    $order['mobile']        = $address['mobile'];
                    $order['sign_building'] = $address['sign_building'];
                    $order['best_time']     = $address['best_time'];
                    $smarty->assign('order', $order);
                }
            }
        }

        if ($exist_real_goods)
        {
            /* 取得国家 */
            $smarty->assign('country_list', get_regions());
            if ($order['country'] > 0)
            {
                /* 取得省份 */
                $smarty->assign('province_list', get_regions(1, $order['country']));
                if ($order['province'] > 0)
                {
                    /* 取得城市 */
                    $smarty->assign('city_list', get_regions(2, $order['province']));
                    if ($order['city'] > 0)
                    {
                        /* 取得区域 */
                        $smarty->assign('district_list', get_regions(3, $order['city']));
                    }
                }
            }
        }
    }

    // 选择配送方式
    elseif ('shipping' == $step)
    {
        /* 如果不存在实体商品 */
        if (!exist_real_goods($order_id))
        {
            die ('Hacking Attemp');
        }
        /* 取得可用的配送方式列表 */
        $region_id_list = array(
        $order['country'], $order['province'], $order['city'], $order['district']
        );
        $shipping_list = available_shipping_list($region_id_list);

        /* 取得配送费用 */
        $total = order_weight_price($order_id);
        foreach ($shipping_list AS $key => $shipping)
        {
            $shipping_fee = shipping_fee($shipping['shipping_code'],
            unserialize($shipping['configure']), $total['weight'], $total['amount']);
            $shipping_list[$key]['shipping_fee'] = $shipping_fee;
            $shipping_list[$key]['format_shipping_fee'] = price_format($shipping_fee);
            $shipping_list[$key]['free_money'] = price_format($shipping['configure']['free_money']);
        }
        $smarty->assign('shipping_list', $shipping_list);
    }

    // 选择支付方式
    elseif ('payment' == $step)
    {
        /* 取得可用的支付方式列表 */
        if (exist_real_goods($order_id))
        {
            /* 存在实体商品 */
            $region_id_list = array(
            $order['country'], $order['province'], $order['city'], $order['district']
            );
            $shipping_area = shipping_area_info($order['shipping_id'], $region_id_list);
            $pay_fee = ($shipping_area['support_cod'] == 1) ? $shipping_area['pay_fee'] : 0;

            $smarty->assign('payment_list', available_payment_list($shipping_area['support_cod'], $pay_fee));
        }
        else
        {
            /* 不存在实体商品 */
            $smarty->assign('payment_list', available_payment_list(false));
        }
    }

    // 选择包装、贺卡
    elseif ('other' == $step)
    {
        /* 查询是否存在实体商品 */
        $exist_real_goods = exist_real_goods($order_id);
        $smarty->assign('exist_real_goods', $exist_real_goods);

        if ($exist_real_goods)
        {
            /* 取得包装列表 */
            $smarty->assign('pack_list', pack_list());

            /* 取得贺卡列表 */
            $smarty->assign('card_list', card_list());
        }
    }

    // 费用
    elseif ('money' == $step)
    {
        /* 查询是否存在实体商品 */
        $exist_real_goods = exist_real_goods($order_id);
        $smarty->assign('exist_real_goods', $exist_real_goods);

        /* 取得用户信息 */
        if ($order['user_id'] > 0)
        {
            $user = user_info($order['user_id']);

            /* 计算可用余额 */
            $smarty->assign('available_user_money', $order['surplus'] + $user['user_money']);

            /* 计算可用积分 */
            $smarty->assign('available_pay_points', $order['integral'] + $user['pay_points']);

            /* 取得用户可用红包 */
            $user_bonus = user_bonus($order['user_id']);
            if ($order['bonus_id'] > 0)
            {
                $bonus = bonus_info($order['bonus_id']);
                $user_bonus[] = $bonus;
            }
            $smarty->assign('available_bonus', $user_bonus);
        }
    }

    // 发货后修改配送方式和发货单号
    elseif ('invoice' == $step)
    {
        /* 如果不存在实体商品 */
        if (!exist_real_goods($order_id))
        {
            die ('Hacking Attemp');
        }

        /* 取得可用的配送方式列表 */
        $region_id_list = array(
        $order['country'], $order['province'], $order['city'], $order['district']
        );
        $shipping_list = available_shipping_list($region_id_list);

        //        /* 取得配送费用 */
        //        $total = order_weight_price($order_id);
        //        foreach ($shipping_list AS $key => $shipping)
        //        {
        //            $shipping_fee = shipping_fee($shipping['shipping_code'],
        //                unserialize($shipping['configure']), $total['weight'], $total['amount']);
        //            $shipping_list[$key]['shipping_fee'] = $shipping_fee;
        //            $shipping_list[$key]['format_shipping_fee'] = price_format($shipping_fee);
        //            $shipping_list[$key]['free_money'] = price_format($shipping['configure']['free_money']);
        //        }
        $smarty->assign('shipping_list', $shipping_list);
    }

    /* 显示模板 */
    assign_query_info();
    $smarty->display('order_step.htm');
}

/*------------------------------------------------------ */
//-- 处理
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'process')
{
    /* 取得参数 func */
    $func = isset($_GET['func']) ? $_GET['func'] : '';

    /* 删除订单商品 */
    if ('drop_order_goods' == $func)
    {
        /* 检查权限 */
        ###admin_priv('order_edit');

        admin_priv('csmo_add_order'); // add by Zandy

        /* 取得参数 */
        $rec_id = intval($_GET['rec_id']);
        $step_act = $_GET['step_act'];
        $order_id = intval($_GET['order_id']);

        /* 删除 */
        $sql = "DELETE FROM " . $ecs->table('order_goods') .
        " WHERE rec_id = '$rec_id' LIMIT 1";
        $db->query($sql);

        /* 更新商品总金额和订单总金额 */
        update_order($order_id, array('goods_amount' => order_amount($order_id)));
        //update_order_amount($order_id);

        /* 跳回订单商品 */
        header("Location: order.php?act=" . $step_act . "&order_id=" . $order_id . "&step=goods\n");
        exit;
    }

    /* 取消刚添加或编辑的订单 */
    elseif ('cancel_order' == $func)
    {
        $step_act = $_GET['step_act'];
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        if ($step_act == 'add')
        {
            /* 如果是添加，删除订单，返回订单列表 */
            if ($order_id > 0)
            {
                $sql = "DELETE FROM " . $ecs->table('order_info') .
                " WHERE order_id = '$order_id' LIMIT 1";
                $db->query($sql);
            }
            header("Location: order.php?act=list\n");
            exit;
        }
        else
        {
            /* 如果是编辑，返回订单信息 */
            header("Location: order.php?act=info&order_id=" . $order_id . "\n");
            exit;
        }
    }

    /* 编辑订单时由于订单已付款且金额减少而退款 */
    elseif ('refund' == $func)
    {
        /* 处理退款 */
        $order_id       = $_REQUEST['order_id'];
        $refund_type    = $_REQUEST['refund'];
        $refund_note    = $_REQUEST['refund_note'];
        $refund_amount  = $_REQUEST['refund_amount'];
        $order          = order_info($order_id);
        order_refund($order, $refund_type, $refund_note, $refund_amount);

        /* 修改应付款金额为0，已付款金额减少 $refund_amount */
        update_order($order_id, array('order_amount' => 0, 'money_paid' => $order['money_paid'] - $refund_amount));

        /* 返回订单详情 */
        header("Location: order.php?act=info&order_id=" . $order_id . "\n");
        exit;
    }

    /* 载入退款页面 */
    elseif ('load_refund' == $func)
    {
        $refund_amount = floatval($_REQUEST['refund_amount']);
        $smarty->assign('refund_amount', $refund_amount);
        $smarty->assign('formated_refund_amount', price_format($refund_amount));

        $anonymous = $_REQUEST['anonymous'];
        $smarty->assign('anonymous', $anonymous); // 是否匿名

        $order_id = intval($_REQUEST['order_id']);
        $smarty->assign('order_id', $order_id); // 订单id

        /* 显示模板 */
        $smarty->assign('ur_here', $_LANG['refund']);
        assign_query_info();
        $smarty->display('order_refund.htm');
    }

    else
    {
        die('invalid params');
    }
}

/*------------------------------------------------------ */
//-- 合并订单
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'merge')
{
    /* 检查权限 */
    admin_priv('order_os_edit');

    /* 取得满足条件的订单 */
    $sql = "SELECT o.order_sn, u.user_name " .
    "FROM " . $ecs->table('order_info') . " AS o " .
    "LEFT JOIN " . $ecs->table('users') . " AS u ON o.user_id = u.user_id " .
    " WHERE (o.order_status = '" . OS_UNCONFIRMED . "' OR o.order_status = '" . OS_CONFIRMED . "') " .
    "AND o.shipping_status = '" . SS_UNSHIPPED . "' " .
    "AND o.pay_status = '" . PS_UNPAYED . "' " .
    "AND o.user_id > 0 ".
    "AND o.extension_id > 0 ";
    $smarty->assign('order_list', $db->getAll($sql));

    /* 模板赋值 */
    $smarty->assign('ur_here', $_LANG['04_merge_order']);
    $smarty->assign('action_link', array('href' => 'order.php?act=list', 'text' => $_LANG['02_order_list']));

    /* 显示模板 */
    assign_query_info();
    $smarty->display('merge_order.htm');
}

/*------------------------------------------------------ */
//-- 订单打印模板（载入页面）
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'templates')
{
    /* 检查权限 */
    admin_priv('order_os_edit');

    /* 读入订单打印模板文件 */
    $file_path    = ROOT_PATH.'data/order_print.html';
    $file_content = file_get_contents($file_path);
    @fclose($file_content);

    include_once(ROOT_PATH."includes/fckeditor/fckeditor.php");

    /* 编辑器 */
    $editor = new FCKeditor('FCKeditor1');
    $editor->BasePath   = "../includes/fckeditor/";
    $editor->ToolbarSet = "Normal";
    $editor->Width      = "95%";
    $editor->Height     = "500";
    $editor->Value      = $file_content;

    $fckeditor = $editor->CreateHtml();
    $smarty->assign('fckeditor', $fckeditor);

    /* 模板赋值 */
    $smarty->assign('ur_here',      $_LANG['edit_order_templates']);
    $smarty->assign('action_link',  array('href' => 'order.php?act=list', 'text' => $_LANG['02_order_list']));
    $smarty->assign('act', 'edit_templates');

    /* 显示模板 */
    assign_query_info();
    $smarty->display('order_templates.htm');
}
/*------------------------------------------------------ */
//-- 订单打印模板（提交修改）
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'edit_templates')
{
    /* 更新模板文件的内容 */
    $file_name = @fopen("../data/order_print.html", 'w+');
    @fwrite($file_name, stripslashes($_POST['FCKeditor1']));
    @fclose($file_name);

    /* 提示信息 */
    $link[] = array('text' => $_LANG['back_list'], 'href'=>'order.php?act=list');
    sys_msg($_LANG['edit_template_success'], 0, $link);
}

/*------------------------------------------------------ */
//-- 操作订单状态（载入页面）
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'operate')
{
    /* 取得订单id（可能是多个，多个sn）和操作备注（可能没有） */
    $order_id       = $_REQUEST['order_id'];
    $batch          = isset($_REQUEST['batch']); // 是否批处理
    $action_note    = isset($_REQUEST['action_note']) ? trim($_REQUEST['action_note']) : '';

    /* 确认 */
    if (isset($_POST['confirm']))
    {
        $require_note   = false;
        $action         = $_LANG['op_confirm'];
        $operation      = 'confirm';
    }
    /* 付款 */
    elseif (isset($_POST['pay']))
    {
        $require_note   = $_CFG['order_pay_note'] == 1;
        $action         = $_LANG['op_pay'];
        $operation      = 'pay';
    }
    /* 未付款 */
    elseif (isset($_POST['unpay']))
    {
        $require_note   = $_CFG['order_unpay_note'] == 1;
        $order          = order_info($order_id);
        if ($order['money_paid'] > 0)
        {
            $show_refund = true;
        }
        $anonymous      = $order['user_id'] == 0;
        $action         = $_LANG['op_unpay'];
        $operation      = 'unpay';
    }
    /* 发货 */
    elseif (isset($_POST['ship']))
    {
        $require_note   = $_CFG['order_ship_note'] == 1;
        $show_invoice_no= true;
        $action         = $_LANG['op_ship'];
        $operation      = 'ship';
    }
    /* 未发货 */
    elseif (isset($_POST['unship']))
    {
        $require_note   = $_CFG['order_unship_note'] == 1;
        $action         = $_LANG['op_unship'];
        $operation      = 'unship';
    }
    /* 收货确认 */
    elseif (isset($_POST['receive']))
    {
        $require_note   = $_CFG['order_receive_note'] == 1;
        $action         = $_LANG['op_receive'];
        $operation      = 'receive';
    }
    /* 取消 */
    elseif (isset($_POST['cancel']))
    {
        $require_note   = $_CFG['order_cancel_note'] == 1;
        $action         = $_LANG['op_cancel'];
        $operation      = 'cancel';
        $show_cancel_note   = true;
        $order          = order_info($order_id);
        if ($order['money_paid'] > 0)
        {
            $show_refund = true;
        }
        $anonymous      = $order['user_id'] == 0;
    }
    /* 无效 */
    elseif (isset($_POST['invalid']))
    {
        $require_note   = $_CFG['order_invalid_note'] == 1;
        $action         = $_LANG['op_invalid'];
        $operation      = 'invalid';
    }
    /* 退货 */
    elseif (isset($_POST['return']))
    {
        $require_note   = $_CFG['order_return_note'] == 1;
        $order          = order_info($order_id);
        if ($order['money_paid'] > 0)
        {
            $show_refund = true;
        }
        $anonymous      = $order['user_id'] == 0;
        $action         = $_LANG['op_return'];
        $operation      = 'return';
    }
    /* 删除 */
    elseif (isset($_POST['remove']))
    {
        $require_note = false;
        $operation = 'remove';
        if (!$batch)
        {
            /* 检查能否操作 */
            /*
            $order = order_info($order_id);
            $operable_list = operable_list($order);
            if (!isset($operable_list['remove']))
            {
            die('Hacking attempt');
            }
            */
            /* 删除订单 */
            $db->query("DELETE FROM ".$ecs->table('order_info'). " WHERE order_id = '$order_id'");
            $db->query("DELETE FROM ".$ecs->table('order_goods'). " WHERE order_id = '$order_id'");
            $db->query("DELETE FROM ".$ecs->table('order_action'). " WHERE order_id = '$order_id'");

            /* todo 记录日志 */
            admin_log($order['order_sn'], 'remove', 'order');

            /* 返回 */
            sys_msg($_LANG['order_removed'], 0, array(array('href'=>'order.php?act=list', 'text' => $_LANG['return_list'])));
        }
    }

    /* 直接处理还是跳到详细页面 */
    if (($require_note && $action_note == '') || isset($show_invoice_no) || isset($show_refund))
    {
        /* 模板赋值 */
        $smarty->assign('require_note', $require_note); // 是否要求填写备注
        $smarty->assign('action_note', $action_note);   // 备注
        $smarty->assign('show_cancel_note', isset($show_cancel_note)); // 是否显示取消原因
        $smarty->assign('show_invoice_no', isset($show_invoice_no)); // 是否显示发货单号
        $smarty->assign('show_refund', isset($show_refund)); // 是否显示退款
        $smarty->assign('anonymous', isset($anonymous) ? $anonymous : true); // 是否匿名
        $smarty->assign('order_id', $order_id); // 订单id
        $smarty->assign('batch', $batch);   // 是否批处理
        $smarty->assign('operation', $operation); // 操作

        /* 显示模板 */
        $smarty->assign('ur_here', $_LANG['order_operate'] . $action);
        assign_query_info();
        $smarty->display('order_operate.htm');
    }
    else
    {
        /* 直接处理 */
        if (!$batch)
        {
            /* 一个订单 */
            header("Location: order.php?act=operate_post&order_id=" . $order_id .
            "&operation=" . $operation . "&action_note=" . urlencode($action_note) . "\n");
            exit;
        }
        else
        {
            /* 多个订单 */
            header("Location: order.php?act=batch_operate_post&order_id=" . $order_id .
            "&operation=" . $operation . "&action_note=" . urlencode($action_note) . "\n");
            exit;
        }
    }
}

/*------------------------------------------------------ */
//-- 操作订单状态（处理批量提交）
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'batch_operate_post')
{
    /* 取得参数 */
    $order_id   = $_REQUEST['order_id'];        // 订单id（逗号格开的多个订单id）
    $operation  = $_REQUEST['operation'];       // 订单操作
    $action_note= $_REQUEST['action_note'];     // 操作备注

    /* 初始化处理的订单sn */
    $sn_list = array();

    /* 确认 */
    if ('confirm' == $operation)
    {
        /* 取得未确认的订单 */
        $sql = "SELECT * FROM " . $ecs->table('order_info') .
        " WHERE order_sn " . db_create_in($order_id) .
        " AND order_status = '" . OS_UNCONFIRMED . "'";
        $res = $db->query($sql);
        while ($order = $db->fetchRow($res))
        {
            /* 检查能否操作 */
            $operable_list = operable_list($order);
            if (!isset($operable_list[$operation]))
            {
                continue;
            }

            $order_id = $order['order_id'];

            /* 标记订单为已确认 */
            update_order($order_id, array('order_status' => OS_CONFIRMED, 'confirm_time' => time()));
            //update_order_amount($order_id);

            /* 记录log */
            order_action($order['order_sn'], OS_CONFIRMED, SS_UNSHIPPED, PS_UNPAYED, $action_note);

            /* 发送邮件 */
            /*
            if ($_CFG['send_confirm_email'] == '1')
            {
            $tpl = get_mail_template('order_confirm');
            $smarty->assign('order', $order);
            $smarty->assign('shop_name', $_CFG['shop_name']);
            $smarty->assign('send_date', date('Y-m-d'));
            $smarty->assign('sent_date', date('Y-m-d'));
            $content = $smarty->fetch('db:order_confirm');
            send_mail($order['consignee'], $order['email'], $tpl['template_subject'], $content, $tpl['is_html']);
            }*/

            $sn_list[] = $order['order_sn'];
        }
    }

    /* 无效 */
    elseif ('invalid' == $operation)
    {
        /* 取得未付款、未发货的订单 */
        $sql = "SELECT * FROM " . $ecs->table('order_info') .
        " WHERE order_sn " . db_create_in($order_id) .
        " AND pay_status = '" . PS_UNPAYED . "' AND shipping_status = '" . SS_UNSHIPPED . "'";
        $res = $db->query($sql);
        while ($order = $db->fetchRow($res))
        {
            /* 检查能否操作 */
            $operable_list = operable_list($order);
            if (!isset($operable_list[$operation]))
            {
                continue;
            }

            $order_id = $order['order_id'];

            /* 标记订单为“无效” */
            update_order($order_id, array('order_status' => OS_INVALID));

            /* 记录log */
            order_action($order['order_sn'], OS_INVALID, SS_UNSHIPPED, PS_UNPAYED, $action_note);

            /* 发送邮件 */
            if ($_CFG['send_invalid_email'] == '1')
            {
                $tpl = get_mail_template('order_invalid');
                $smarty->assign('order', $order);
                $smarty->assign('shop_name', $_CFG['shop_name']);
                $smarty->assign('send_date', date('Y-m-d'));
                $smarty->assign('sent_date', date('Y-m-d'));
                $content = $smarty->fetch('db:order_invalid');
                send_mail($order['consignee'], $order['email'], $tpl['template_subject'], $content, $tpl['is_html']);
            }

            /* 退还用户余额、积分、红包 */
            return_user_surplus_integral_bonus($order);

            $sn_list[] = $order['order_sn'];
        }
    }
    elseif ('cancel' == $operation)
    {
        /* 取得未付款、未发货的订单 */
        $sql = "SELECT * FROM " . $ecs->table('order_info') .
        " WHERE order_sn " . db_create_in($order_id);
        $res = $db->query($sql);
        while ($order = $db->fetchRow($res))
        {
            /* 检查能否操作 */
            //            $operable_list = operable_list($order);
            //            if (!isset($operable_list[$operation]))
            //            {
            //                continue;
            //            }
            $order_id = $order['order_id'];

            /* 标记订单为“取消”，记录取消原因 */
            $cancel_note = trim($_REQUEST['cancel_note']);
            update_order($order_id, array('order_status' => OS_CANCELED, 'to_buyer' => $cancel_note));

            /* 记录log */
            order_action($order['order_sn'], OS_CANCELED, $order['shipping_status'], PS_UNPAYED, $action_note);

            /* 发送邮件 */
            /*if ($_CFG['send_cancel_email'] == '1')
            {
            $tpl = get_mail_template('order_cancel');
            $smarty->assign('order', $order);
            $smarty->assign('shop_name', $_CFG['shop_name']);
            $smarty->assign('send_date', date('Y-m-d'));
            $smarty->assign('sent_date', date('Y-m-d'));
            $content = $smarty->fetch('db:order_cancel');
            send_mail($order['consignee'], $order['email'], $tpl['template_subject'], $content, $tpl['is_html']);
            }*/

            /* 退还用户余额、积分、红包 */
            return_user_surplus_integral_bonus($order);

            $sn_list[] = $order['order_sn'];
        }
    }
    elseif ('remove' == $operation)
    {
        $order_id_list = explode(',', $order_id);
        foreach ($order_id_list as $id)
        {
            /* 检查能否操作 */
            //            $order = order_info('', $id);
            //            $operable_list = operable_list($order);
            //            if (!isset($operable_list['remove']))
            //            {
            //                continue;
            //            }
            $id = $db->getOne("SELECT order_id FROM {$ecs->table('order_info')} WHERE order_sn = '$id'");
            /* 删除订单 */
            $db->query("DELETE FROM ".$ecs->table('order_info'). " WHERE order_id = '$id'");
            $db->query("DELETE FROM ".$ecs->table('order_goods'). " WHERE order_id = '$id'");
            $db->query("DELETE FROM ".$ecs->table('order_action'). " WHERE order_id = '$id'");

            /* todo 记录日志 */
            admin_log($order['order_sn'], 'remove', 'order');

            $sn_list[] = $order['order_sn'];
        }
    }
    else
    {
        die('invalid params');
    }

    /* 取得备注信息 */
    $action_note = $_REQUEST['action_note'];

    /* 返回信息 */
    $msg = count($sn_list) == 0 ? $_LANG['no_fulfilled_order'] : $_LANG['updated_order'] . join($sn_list, ',');
    $links[] = array('text' => $_LANG['return_list'], 'href' => 'order.php?act=list');
    sys_msg($msg, 0, $links);
}

/*------------------------------------------------------ */
//-- 操作订单状态（处理提交）
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'operate_post')
{
    // 增加权限判断，客服可操作 by ychen 2008/05/23
    admin_priv('customer_service_manage_order');

    /* 取得参数 */
    $order_id   = $_REQUEST['order_id'];        // 订单id
    $operation  = $_REQUEST['operation'];       // 订单操作

    /* 查询订单信息 */
    $order = order_info($order_id);

    /* 检查能否操作 */
    $operable_list = operable_list($order);
    if (!isset($operable_list[$operation]))
    {
        // 去除这个判断 by ychen 2008/05/23
        //die('Hacking attempt');
    }

    /* 取得备注信息 */
    $action_note = $_REQUEST['action_note'];

    /* 初始化提示信息 */
    $msg = '';

    /* 确认 */
    if ('confirm' == $operation)
    {
        /* 标记订单为已确认 */
        update_order($order_id, array('order_status' => OS_CONFIRMED, 'confirm_time' => time()));
        //update_order_amount($order_id);

        /* 记录log */
        order_action($order['order_sn'], OS_CONFIRMED, SS_UNSHIPPED, PS_UNPAYED, $action_note);

        /* 发送邮件 */
        /*$cfg = $_CFG['send_confirm_email'];
        if ($cfg == '1')
        {
        $tpl = get_mail_template('order_confirm');
        $smarty->assign('order', $order);
        $smarty->assign('shop_name', $_CFG['shop_name']);
        $smarty->assign('send_date', date('Y-m-d'));
        $smarty->assign('send_date', date('Y-m-d'));
        $smarty->assign('sent_date', date('Y-m-d'));
        $content = $smarty->fetch('db:order_confirm');
        if (!send_mail($order['consignee'], $order['email'], $tpl['template_subject'], $content, $tpl['is_html']))
        {
        $msg = $_LANG['send_mail_fail'];
        }
        }*/
    }
    /* 付款 */
    elseif ('pay' == $operation)
    {
        /* 标记订单为已确认、已付款，更新付款时间和已支付金额 */
        if ($order['order_status'] != OS_CONFIRMED)
        {
            die('订单未确认'); // Zandy
            $arr['order_status']    = OS_CONFIRMED;
            $arr['confirm_time']    = time();
        }
        $arr['pay_status']  = PS_PAYED;
        $arr['pay_time']    = time();
        $arr['money_paid']  = $order['money_paid'] + $order['order_amount'];
        $arr['order_amount']= 0;
        update_order($order_id, $arr);

        /* 记录log */
        order_action($order['order_sn'], OS_CONFIRMED, $order['shipping_status'], PS_PAYED, $action_note);
    }
    /* 设为未付款 */
    elseif ('unpay' == $operation)
    {
        /* 标记订单为未付款，更新付款时间和已付款金额 */
        $arr = array(
        'pay_status'    => PS_UNPAYED,
        'pay_time'      => 0,
        'money_paid'    => 0,
        'order_amount'  => $order['money_paid']
        );
        update_order($order_id, $arr);

        /* todo 处理退款 */
        $refund_type = @$_REQUEST['refund'];
        $refund_note = @$_REQUEST['refund_note'];
        order_refund($order, $refund_type, $refund_note);

        /* 记录log */
        order_action($order['order_sn'], OS_CONFIRMED, SS_UNSHIPPED, PS_UNPAYED, $action_note);
    }
    /* 发货 */
    elseif ('ship' == $operation)
    {
        /* 取得发货单号 */
        $invoice_no = $_REQUEST['invoice_no'];

        /* 对虚拟商品的支持 */
        $virtual_goods = get_virtual_goods($order_id);
        if (!empty($virtual_goods))
        {
            if (!virtual_goods_ship($virtual_goods, $msg, $order['order_sn']))
            {
                sys_msg($msg);
            }
        }

        /* 标记订单为已确认。如果是货到付款，标记订单为“确认收货”，否则，标记订单为“已发货” */
        /* 更新发货时间和发货单号 */
        $payment = payment_info($order['pay_id']);
        $shipping_status = $payment['is_cod'] == 1 ? SS_RECEIVED : SS_SHIPPED;
        if ($order['order_status'] != OS_CONFIRMED)
        {
            $arr['order_status']    = OS_CONFIRMED;
            $arr['confirm_time']    = time();
        }
        $arr['shipping_status']     = $shipping_status;
        $arr['shipping_time']       = time();
        $arr['invoice_no']          = $invoice_no;
        update_order($order_id, $arr);
        $order['invoice_no'] = $invoice_no;

        /* 记录log */
        order_action($order['order_sn'], OS_CONFIRMED, $shipping_status, $order['pay_status'], $action_note);

        /* 如果订单用户不为空，计算积分，并发给用户；发红包 */
        if ($order['user_id'] > 0)
        {
            /* 取得用户信息 */
            $user = user_info($order['user_id']);

            /* 计算并发放积分 */
            $integral = integral_to_give($order);
            $arr = array(
            'pay_points'  => $user['pay_points'] + $integral,
            'rank_points' => $user['rank_points'] + $integral
            );
            update_user($order['user_id'], $arr);

            /* 发放红包 */
            send_order_bonus($order_id);
        }

        /* 如果使用库存，则修改库存 */
        $cfg = $_CFG['use_storage'];
        if ($cfg == '1')
        {
            change_order_goods_storage($order['order_id']);
        }

        /* 发送邮件 */
        /*$cfg = $_CFG['send_ship_email'];
        if ($cfg == '1')
        {
        $tpl = get_mail_template('deliver_notice');
        $smarty->assign('order', $order);
        $smarty->assign('send_time', date('Y-m-d H:i:s'));
        $smarty->assign('shop_name', $_CFG['shop_name']);
        $smarty->assign('send_date', date('Y-m-d'));
        $smarty->assign('sent_date', date('Y-m-d'));
        $smarty->assign('confirm_url', $ecs->url() . 'receive.php?id=' . $order['order_id'] . '&con=' . rawurlencode($order['consignee']));
        $content = $smarty->fetch('db:deliver_notice');
        if (!send_mail($order['consignee'], $order['email'], $tpl['template_subject'], $content, $tpl['is_html']))
        {
        $msg = $_LANG['send_mail_fail'];
        }
        }*/

        /* 如果需要，发短信 */
        if ($GLOBALS['_CFG']['sms_order_shipped'] == '1' && $order['mobile'] != '')
        {
            include_once('../includes/cls_sms.php');
            $sms = new sms();
            $sms->send($order['mobile'], sprintf($GLOBALS['_LANG']['order_shipped_sms'], $order['order_sn'],
            date($GLOBALS['_LANG']['sms_time_format']), $GLOBALS['_CFG']['shop_name']), 0);
        }

        /* 清除缓存 */
        clear_cache_files();
    }
    /* 设为未发货 */
    elseif ('unship' == $operation)
    {
        /* 标记订单为“未发货”，更新发货时间 */
        update_order($order_id, array('shipping_status' => SS_UNSHIPPED, 'shipping_time' => 0));

        /* 记录log */
        order_action($order['order_sn'], $order['order_status'], SS_UNSHIPPED, $order['pay_status'], $action_note);

        /* 如果订单用户不为空，计算积分，并退回 */
        if ($order['user_id'] > 0)
        {
            /* 取得用户信息 */
            $user = user_info($order['user_id']);

            /* 计算并退回积分 */
            $integral = integral_to_give($order);
            $arr = array(
            'pay_points'  => $user['pay_points'] - $integral,
            'rank_points' => $user['rank_points'] - $integral
            );
            update_user($order['user_id'], $arr);

            /* todo 计算并退回红包 */
            return_order_bonus($order_id);
        }

        /* 如果使用库存，则增加库存 */
        $cfg = $_CFG['use_storage'];
        if ($cfg == '1')
        {
            change_order_goods_storage($order['order_id'], false);
        }

        /* 清除缓存 */
        clear_cache_files();
    }
    /* 收货确认 */
    elseif ('receive' == $operation)
    {
        /* 标记订单为“收货确认” */
        update_order($order_id, array('shipping_status' => SS_RECEIVED));

        /* 记录log */
        order_action($order['order_sn'], $order['order_status'], SS_RECEIVED, $order['pay_status'], $action_note);
    }
    /* 取消 */
    elseif ('cancel' == $operation)
    {
        /* 标记订单为“取消”，记录取消原因 */
        $cancel_note = isset($_REQUEST['cancel_note']) ? trim($_REQUEST['cancel_note']) : '';
        $arr = array(
        'order_status'  => OS_CANCELED,
        'to_buyer'      => $cancel_note /*,
        'pay_status'    => PS_UNPAYED,
        'pay_time'      => 0,
        'money_paid'    => 0,
        'order_amount'  => $order['money_paid']*/
        );
        update_order($order_id, $arr);

        /* todo 处理退款 */
        if ($order['money_paid'] > 0)
        {
            $refund_type = $_REQUEST['refund'];
            $refund_note = $_REQUEST['refund_note'];
            order_refund($order, $refund_type, $refund_note);
        }

        /* 记录log */
        order_action($order['order_sn'], OS_CANCELED, $order['shipping_status'], PS_UNPAYED, $action_note);
        order_action($order['order_sn'], OS_CANCELED, -1, -1, $action_note);

        /* 退还用户余额、积分、红包 */
        return_user_surplus_integral_bonus($order);

        /* 发送邮件 */
        /*$cfg = $_CFG['send_cancel_email'];
        if ($cfg == '1')
        {
        $tpl = get_mail_template('order_cancel');
        $smarty->assign('order', $order);
        $smarty->assign('shop_name', $_CFG['shop_name']);
        $smarty->assign('send_date', date('Y-m-d'));
        $smarty->assign('sent_date', date('Y-m-d'));
        $content = $smarty->fetch('db:order_cancel');
        if (!send_mail($order['consignee'], $order['email'], $tpl['template_subject'], $content, $tpl['is_html']))
        {
        $msg = $_LANG['send_mail_fail'];
        }
        }*/
    }
    /* 设为无效 */
    elseif ('invalid' == $operation)
    {
        /* 标记订单为“无效”、“未付款” */
        update_order($order_id, array('order_status' => OS_INVALID));

        /* 记录log */
        order_action($order['order_sn'], OS_INVALID, $order['shipping_status'], PS_UNPAYED, $action_note);

        /* 发送邮件 */
        $cfg = $_CFG['send_invalid_email'];
        if ($cfg == '1')
        {
            $tpl = get_mail_template('order_invalid');
            $smarty->assign('order', $order);
            $smarty->assign('shop_name', $_CFG['shop_name']);
            $smarty->assign('send_date', date('Y-m-d'));
            $smarty->assign('sent_date', date('Y-m-d'));
            $content = $smarty->fetch('db:order_invalid');
            if (!send_mail($order['consignee'], $order['email'], $tpl['template_subject'], $content, $tpl['is_html']))
            {
                $msg = $_LANG['send_mail_fail'];
            }
        }

        /* 退货用户余额、积分、红包 */
        return_user_surplus_integral_bonus($order);
    }
    /* 退货 */
    elseif ('return' == $operation)
    {
        /* 标记订单为“退货”、“未付款”、“未发货” */
        $arr = array('order_status'     => OS_RETURNED,
        'pay_status'       => PS_UNPAYED,
        'shipping_status'  => SS_UNSHIPPED,
        'money_paid'       => 0,
        'order_amount'     => $order['money_paid']);
        update_order($order_id, $arr);

        /* todo 处理退款 */
        $refund_type = $_REQUEST['refund'];
        $refund_note = $_REQUEST['refund_note'];
        order_refund($order, $refund_type, $refund_note);

        /* 记录log */
        order_action($order['order_sn'], OS_RETURNED, SS_UNSHIPPED, PS_UNPAYED, $action_note);

        /* 如果订单用户不为空，计算积分，并退回 */
        if ($order['user_id'] > 0)
        {
            /* 取得用户信息 */
            $user = user_info($order['user_id']);

            /* 计算并退回积分 */
            $integral = integral_to_give($order);
            $arr = array(
            'pay_points'  => $user['pay_points']  - $integral,
            'rank_points' => $user['rank_points'] - $integral
            );
            update_user($order['user_id'], $arr);

            /* todo 计算并退回红包 */
            return_order_bonus($order_id);
        }

        /* 如果使用库存，则增加库存 */
        $cfg = $_CFG['use_storage'];
        if ($cfg == '1')
        {
            change_order_goods_storage($order['order_id'], false);
        }

        /* 退货用户余额、积分、红包 */
        return_user_surplus_integral_bonus($order);

        /* 清除缓存 */
        clear_cache_files();
    }
    else
    {
        die('invalid params');
    }

    /* 操作成功 */
    $links[] = array('text' => $_LANG['order_info'], 'href' => 'order.php?act=info&order_id=' . $order_id);
    sys_msg($_LANG['act_ok'] . $msg, 0, $links);
}

elseif ($_REQUEST['act'] == 'json')
{
    include_once(ROOT_PATH . 'includes/cls_json.php');
    $json = new JSON();

    $func = $_REQUEST['func'];
    if ($func == 'get_goods_info')
    {
        /* 取得商品信息 */
        $goods_id = $_REQUEST['goods_id'];
        $sql = "SELECT goods_id, c.cat_name, goods_sn, goods_name, b.brand_name, " .
        "goods_number, market_price, shop_price, promote_price, " .
        "promote_start, promote_end, goods_brief, goods_type, is_promote " .
        "FROM " . $ecs->table('goods') . " AS g " .
        "LEFT JOIN " . $ecs->table('brand') . " AS b ON g.brand_id = b.brand_id " .
        "LEFT JOIN " . $ecs->table('category') . " AS c ON g.cat_id = c.cat_id " .
        " WHERE goods_id = '$goods_id'";
        $goods = $db->getRow($sql);
        $today = date('Y-m-d');
        $goods['goods_price'] = ($goods['is_promote'] == 1 &&
        $goods['promote_start'] <= $today && $goods['promote_end'] >= $today) ?
        $goods['promote_price'] : $goods['shop_price'];

        /* 取得会员价格 */
        $sql = "SELECT p.user_price, r.rank_name " .
        "FROM " . $ecs->table('member_price') . " AS p, " .
        $ecs->table('user_rank') . " AS r " .
        "WHERE p.user_rank = r.rank_id " .
        "AND p.goods_id = '$goods_id' ";
        $goods['user_price'] = $db->getAll($sql);

        /* 取得商品属性 */
        $sql = "SELECT a.attr_id, a.attr_name, g.goods_attr_id, g.attr_value, g.attr_price " .
        "FROM " . $ecs->table('goods_attr') . " AS g, " .
        $ecs->table('attribute') . " AS a " .
        "WHERE g.attr_id = a.attr_id " .
        "AND g.goods_id = '$goods_id' ";
        $goods['attr_list'] = array();
        $res = $db->query($sql);
        while ($row = $db->fetchRow($res))
        {
            $goods['attr_list'][$row['attr_id']][] = $row;
        }
        $goods['attr_list'] = array_values($goods['attr_list']);

        echo $json->encode($goods);
    }
}

/*------------------------------------------------------ */
//-- 合并订单
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'ajax_merge_order')
{
    include_once(ROOT_PATH . 'includes/cls_json.php');
    $json = new JSON();

    $from_order_sn = empty($_POST['from_order_sn']) ? '' : substr($_POST['from_order_sn'], 1);
    $to_order_sn = empty($_POST['to_order_sn']) ? '' : substr($_POST['to_order_sn'], 1);

    $m_result = merge_order($from_order_sn, $to_order_sn);
    $result = array('error'=>0,  'content'=>'');
    if ($m_result === true)
    {
        $result['message'] = $GLOBALS['_LANG']['act_ok'];
    }
    else
    {
        $result['error'] = 1;
        $result['message'] = $m_result;
    }
    die($json->encode($result));
}

/*------------------------------------------------------ */
//-- 删除订单
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove_order')
{
    $order_id = intval($_REQUEST['id']);

    /* 检查权限 */
    check_authz_json('order_edit');

    /* 检查订单是否允许删除操作 */
    $order = order_info($order_id);
    $operable_list = operable_list($order);
    if (!isset($operable_list['remove']))
    {
        make_json_error('Hacking attempt');
        exit;
    }

    $GLOBALS['db']->query("DELETE FROM ".$GLOBALS['ecs']->table('order_info'). " WHERE order_id = '$order_id'");
    $GLOBALS['db']->query("DELETE FROM ".$GLOBALS['ecs']->table('order_goods'). " WHERE order_id = '$order_id'");
    $GLOBALS['db']->query("DELETE FROM ".$GLOBALS['ecs']->table('order_action'). " WHERE order_id = '$order_id'");

    if ($GLOBALS['db'] ->errno() == 0)
    {
        $url = 'order.php?act=query&' . str_replace('act=remove_order', '', $_SERVER['QUERY_STRING']);

        header("Location: $url\n");
        exit;
    }
    else
    {
        make_json_error($GLOBALS['db']->errorMsg());
    }
}


/*------------------------------------------------------ */
//-- 订单确认 订单取消 -- 我觉得应该已经没用了，因为RPC要被干掉
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'order_confirm' || $_REQUEST['act'] == 'order_resume' || $_REQUEST['act'] == 'order_cancel') {
    include_once(ROOT_PATH . 'includes/cls_json.php');
    $json = new JSON();
    $result = array('error'=>0, 'message'=>'', 'content'=>'');
    //  $order_sn = $_REQUEST['order_sn'];
    //  $note = '确认订单 '.$_REQUEST['note'];
    //  $db->query(" UPDATE {$ecs->table('order_info')} SET order_status = 1 WHERE order_sn = '$order_sn' LIMIT 1 ");
    //  orderActionLog($order_sn,array('order_status'=>1));
    $order_id = $_REQUEST['order_id'];
    $order = $shopapi_client->getOrderById($order_id);
    if ($_REQUEST['act'] == 'order_confirm') {
        $order->orderStatus = 1;
        $order->actionNote = '确认订单 '.$_REQUEST['note'];
    }
    if ($_REQUEST['act'] == 'order_resume') {
        $order->orderStatus = 1;
        $order->actionNote = '恢复订单 '.$_REQUEST['note'];
    }
    if ($_REQUEST['act'] == 'order_cancel') {
        $order->orderStatus = 2;
        $order->actionNote = '取消订单 '.$_REQUEST['note'];
    }
    $order->actionUser = $action_user;
    $shopapi_client->updateOrder($order);
    die($json->encode($result));
}
elseif ($_REQUEST['act'] == 'order_transfer') {
    $order = $shopapi_client->getOrderById($_REQUEST['order_id']);
    $orign_userid = $order->userId;
    $sql = "SELECT user_name FROM {$ecs->table('users')} WHERE user_id = $orign_userid";
    $orign_user_name = $db->getOne($sql);
    $orign_username = $order->userName;
    $order->userId = $_REQUEST['user_id'];
    $order->actionUser = $_SESSION['admin_name'];
    $order->actionNote = "转移订单：从用户 $orign_user_name 转移到 {$_REQUEST['user_name']}";
    $shopapi_client->updateOrder($order);

    $back = $_REQUEST['back'] ? $_REQUEST['back'] : "order_edit.php?order_id={$_REQUEST['order_id']}";
    header('Location:'.$back);
}


/*------------------------------------------------------ */
//-- 根据关键字和id搜索用户
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'search_users')
{
    include_once(ROOT_PATH . 'includes/cls_json.php');
    $json = new JSON();

    //$id_name = empty($_GET['id_name']) ? '' : trim($_GET['id_name']);
    $id_name = empty($_REQUEST['id_name']) ? '' : trim($_REQUEST['id_name']);

    $result = array('error'=>0, 'message'=>'', 'content'=>'');
    if ($id_name != '')
    {
        $sql = "SELECT user_id, user_name FROM " . $GLOBALS['ecs']->table('users') .
        " WHERE user_id LIKE '%" . mysql_like_quote($id_name) . "%'" .
        " OR user_name LIKE '%" . mysql_like_quote($id_name) . "%'" .
        " LIMIT 20";
        $res = $GLOBALS['db']->query($sql);
        $result['userlist'] = array();
        while ($row = $GLOBALS['db']->fetchRow($res))
        {
            $result['userlist'][] = array('user_id' => $row['user_id'], 'user_name' => $row['user_name']);
        }

        if(count($result['userlist']) == 0){
            $result['error'] = 2;
            $result['message'] = '无此用户，请重新搜索。';
        }
    }
    else
    {
        $result['error'] = 1;
        $result['message'] = '用户名不能未空。';
    }

    die($json->encode($result));
}

/*------------------------------------------------------ */
//-- 根据关键字搜索商品
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'search_goods')
{
    include_once(ROOT_PATH . 'includes/cls_json.php');
    $json = new JSON();

    $keyword = empty($_GET['keyword']) ? '' : trim($_GET['keyword']);

    $result = array('error'=>0, 'message'=>'', 'content'=>'');

    if ($keyword != '')
    {
        $keyword = mysql_like_quote($keyword);
        if ($_SESSION['party_id'] == PARTY_OUKU_SHOES)
        {            
            $conditions = "(g.goods_name LIKE '%$keyword%' OR g.sku LIKE '%$keyword%')"; 
        }
        else {
        	$conditions = "(g.goods_name LIKE '%$keyword%' OR g.barcode = '$keyword')";
        } 
        
        // 如果结果需要包含不下架的产品，则指定‘extend’参数
        if (isset($_GET['extend']) && $_GET['extend']=='include_not_on_sale')
        {
            $sql = "
                SELECT g.goods_id,g.spec,g.barcode, g.goods_sn,g.shop_price, g.top_cat_id, CONCAT( goods_name, IF(is_on_sale = '0', '(已下架)', '')) AS goods_name, c.cat_name 
                FROM {$GLOBALS['ecs']->table('goods')} g
                LEFT JOIN {$GLOBALS['ecs']->table('category')} c on g.cat_id = c.cat_id
                WHERE {$conditions} AND g.is_delete = 0 AND " . party_sql("g.goods_party_id");
        }
        else
        {
            $sql = "
                SELECT g.goods_id,g.spec,g.barcode,g.goods_name, g.shop_price,g.goods_sn, g.top_cat_id, c.cat_name
                FROM {$GLOBALS['ecs']->table('goods')} g
                LEFT JOIN {$GLOBALS['ecs']->table('category')} c on g.cat_id = c.cat_id
                WHERE {$conditions} AND g.is_delete = 0 AND g.is_on_sale = 1 AND " . party_sql("g.goods_party_id");
        }
        $ref_goods_fields = $ref_goods_rowset = array();
        $goods_list = $GLOBALS['db']->getAllRefby($sql, array('goods_id'), $ref_goods_fields, $ref_goods_rowset, false);

        $result['goodslist'] = array();
        if ($goods_list) 
        {
            //print_r($goods_list);die();
            $goods_ids=array();
            foreach ($goods_list as $goods_item) {
                $goods_ids[]=$goods_item['goods_id'];
            }
            $goods_ids=implode(',', $goods_ids);
            // 查询商品的样式
            $sql = "
                SELECT *, IF (gs.goods_color = '', s.color, gs.goods_color) AS color
                FROM {$ecs->table('goods_style')} AS gs
                    LEFT JOIN {$ecs->table('style')} AS s ON gs.style_id = s.style_id  
                WHERE gs.style_id >0 and  gs.goods_id in ($goods_ids)" ;
            $ref_style_fields = $ref_styles_rowset = array();
            $GLOBALS['db']->getAllRefby($sql, array('goods_id'), $ref_style_fields, $ref_styles_rowset, false);

            foreach ($goods_list as $goods) 
            {
                $result['goodslist'][] = array(
                    'goods_id'   => $goods['goods_id'], 
                    'name'       => $goods['goods_name'],
                    'spec'       => $goods['spec'], 
                    'shop_price' => $goods['shop_price'],  
                    'barcode'    => $goods['barcode'], 
                    'top_cat_id' => $goods['top_cat_id'],
                    'cat_name'   => $goods['cat_name'],
                    'style_list' => isset($ref_styles_rowset['goods_id'][$goods['goods_id']]) ? $ref_styles_rowset['goods_id'][$goods['goods_id']] : false , 
                );
            }
        }
    }
    else
    {
        $result['error'] = 1;
        $result['message'] = 'NO KEYWORDS';
    }

    die($json->encode($result));
}


/*------------------------------------------------------ */
//-- 根据关键字搜索套餐商品
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'search_group_goods')
{
	global $db;
    include_once(ROOT_PATH . 'includes/cls_json.php');
    $json = new JSON();

    $keyword = empty($_GET['keyword']) ? '' : trim($_GET['keyword']);

    $result = array('error'=>0, 'message'=>'', 'content'=>'');

    if ($keyword != '')
    {
        $keyword = mysql_like_quote($keyword);
       
        $conditions = "(g.name LIKE '%$keyword%' OR g.code LIKE '%$keyword%')"; 

        $sql = "
            SELECT g.code, g.name
            FROM ecshop.distribution_group_goods g 
            WHERE {$conditions} AND g.status = 'OK' AND " . party_sql("g.party_id");
        $group_goods = $db->getAll($sql);
        
        $result['groupGoodsList'] = array();
        if (!empty($group_goods)) 
        {
            foreach ($group_goods as $goods) 
            {
                $temp = array(
                    'group_goods_id'   => $goods['code'], 
                    'name'       => $goods['name'],
                );
                
                $result['groupGoodsList'][] = $temp;
            }
        }
    }
    else
    {
        $result['error'] = 1;
        $result['message'] = 'NO KEYWORDS';
    }

    die($json->encode($result));
}

/*------------------------------------------------------ */
//-- 根据关键字搜索套餐商品 格式不一样
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'search_group_goods_list')
{
	global $db;
    include_once(ROOT_PATH . 'includes/cls_json.php');
    $json = new JSON();

    $keyword = empty($_POST['q']) ? '' : trim($_POST['q']);

    $result = array();

    $keyword = mysql_like_quote($keyword);
    $conditions = '';
    if(!empty($keyword)) {
    	$conditions = " and (g.name LIKE '%$keyword%' OR g.code LIKE '%$keyword%') "; 
    }

    $sql = "
        SELECT g.code, g.name
        FROM ecshop.distribution_group_goods g 
        WHERE 1 {$conditions} AND g.status = 'OK' AND " . party_sql("g.party_id");
    $group_goods = $db->getAll($sql);
    if (!empty($group_goods)) 
    {
        foreach ($group_goods as $goods) 
        {
            $temp = array(
                'group_goods_id'   => $goods['code'], 
                'name'       => $goods['name'],
            );
            
            $result[] = $temp;
        }
    }

    die($json->encode($result));
}
elseif ($_REQUEST['act'] == 'search_purchase_info'){ 
    // 获取商品的采购信息 对于特定的供应商 
    $barcode =  trim( $_REQUEST['barcode'] ); 
    $provider_id =  $_REQUEST['provider_id'] ; 
    $goods_id = $_REQUEST['goods_id'] ;  
    $goods_barcode = trim( $_REQUEST['goods_barcode']  ) ;  
    $r = array(); 
    if( empty($barcode) || empty($provider_id) ){
       
    }else{
         $sql = "SELECT cp.*,p.provider_name FROM ecs_purchase_goods_price_provider cp  INNER JOIN ecshop.ecs_provider p 
                on cp.provider_id = p.provider_id   WHERE cp.barcode = '{$barcode}' and cp.goods_id='{$goods_id}' and cp.provider_id='{$provider_id}' and cp.is_delete = 0 limit 1"; 
        $r = $db->getRow($sql); 
    }
    if(empty($r) && !empty($goods_barcode) && $goods_barcode != $barcode){
         $sql = "SELECT cp.*,p.provider_name FROM ecs_purchase_goods_price_provider cp  INNER JOIN ecshop.ecs_provider p 
                on cp.provider_id = p.provider_id   WHERE cp.barcode = '{$goods_barcode}' and cp.goods_id='{$goods_id}' and cp.provider_id='{$provider_id}' and cp.is_delete = 0 limit 1"; 
        $r = $db->getRow($sql); 
    }
    die( json_encode($r));
}
/*------------------------------------------------------ */
//-- 根据商品ID搜索商品类型
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'search_goods_style')
{
    include_once(ROOT_PATH . 'includes/cls_json.php');
    require_once(ROOT_PATH."RomeoApi/lib_inventory.php");
    $json = new JSON();

    $goods_id = intval($_REQUEST['goods_id']);
    $is_rma_exchange = $_REQUEST['is_rma_exchange'];
    $pre_goods_price = $_REQUEST['pre_goods_price'];
    $result = array('error' => 0, 'message'=> '', 'content' => '');

    if ($goods_id > 0)
    {
        $sql = "SELECT added_fee,shop_price FROM {$ecs->table('goods')} where goods_id = '$goods_id' limit 1";
        $hehe = $db->getRow($sql);
        $result['price'] = $hehe['shop_price'];
        $result['added_fee'] = $hehe['added_fee']; 
        $sql = "SELECT *, CONCAT(IF (gs.goods_color = '', s.color, gs.goods_color), 
                IF(gs.sale_status != 'normal', '(非在售)', '')) AS color 
                FROM {$ecs->table('goods_style')} gs, {$ecs->table('style')} s 
                WHERE gs.style_id = s.style_id AND gs.goods_id = '$goods_id' and gs.is_delete = 0 ";
        $result['goods_style_list'] = $db->getAll($sql);
        require_once('function.php');
        
        //若是换货订单 商品单价格依照原订单商品单价
        if($is_rma_exchange){
        	$result['price'] = $pre_goods_price;
        	foreach($result['goods_style_list'] as &$item){
        		$item['style_price'] = $pre_goods_price;
        	}
        }
        
        //获得goodsId的数组，用来库存查询
        $goodsIdList = array();
        foreach ($result['goods_style_list'] as $goods_item) {
            $goodsIdList[] =array(
            'goods_id' => $goods_item['goods_id'],
            'style_id' => $goods_item['style_id'],
            );
        }
        
        // 如果不需要库存信息，就不查询了 
        if (!$_REQUEST['no_storage_info']) {
            $storage_str = '';
            $goods_storage = getStorage('INV_STTS_AVAILABLE', '', $goodsIdList);
            foreach ($result['goods_style_list'] as $goods_item) {
                $storage_count = intval($goods_storage["{$goods_item['goods_id']}_{$goods_item['style_id']}"]['qohTotal']);
                $storage_str .= $goods_item['color']." ".($storage_count ? $storage_count : 0)." ";
            }
            if (!$result['goods_style_list']) {
                $storage_count = intval($goods_storage["{$goods_id}_0"]['qohTotal']);
                $storage_str = $storage_count ? $storage_count : 0;
            }

            $result['storage'] = $storage_str;
        }
        //查询是否为赠品
        $sql = "
           SELECT 1 
           FROM ecshop.ecs_goods g
           inner join ecshop.ecs_category c on c.cat_id = g.cat_id and c.cat_name like '%赠品%'
           where g.goods_id = '{$goods_id}'
           ";
        $db->getOne($sql) ? $result['is_gift'] = true : $result['is_gift'] = false;
        $result['id'] = $_REQUEST['id'];
    }
    else
    {
        $result['error'] = 1;
        $result['message'] = 'NO GOODS ID';
    }
    die($json->encode($result));

}

/*------------------------------------------------------ */
//-- 根据商品ID和style_id查询上次入库的价格
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'search_last_price')
{
    include_once(ROOT_PATH . 'includes/cls_json.php');
    include_once('function.php');
    $json = new JSON();

    $goods_id = intval($_REQUEST['goods_id']);
    $style_id = intval($_REQUEST['style_id']);
    $result = array('error' => 0, 'message'=> '', 'content' => '');

    if ($goods_id > 0)
    {
        $condition = " pm.ecs_goods_id = '{$goods_id}'";
        if ($style_id > 0) {
            $condition .= " AND pm.ecs_style_id = '{$style_id}'";
        }
        $sql = "
            select 
            		ii.UNIT_COST AS price, func_get_goods_category_name(g.top_cat_id, g.cat_id) as category
			from romeo.product_mapping pm
				inner join romeo.inventory_item ii on pm.product_id = ii.PRODUCT_ID
				inner join ecshop.ecs_goods g ON g.goods_id = pm.ecs_goods_id
            WHERE {$condition} and ii.INVENTORY_ITEM_ID = ii.ROOT_INVENTORY_ITEM_ID
            order by ii.CREATED_STAMP DESC 
            LIMIT 1
        ";
        $goods = $db->getRow($sql);
        // 限制是否可以查看该商品的供价 
        if ($goods && view_provide_price($goods['category'])) {
            $result['price'] = $goods['price'];
        } else {
            $result['price'] = 'xxx';
        }
    }
    else
    {
        $result['error'] = 1;
        $result['message'] = 'NO GOODS ID';
    }
    die($json->encode($result));

}
/*------------------------------------------------------ */
//-- 根据商品ID搜索商品类型
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'search_goods_fitting')
{
    include_once(ROOT_PATH . 'includes/cls_json.php');
    include_once(ROOT_PATH . 'includes/lib_goods.php');
    $json = new JSON();
    $goods_id = intval($_REQUEST['goods_id']);
    $result = array('error' => 0, 'message'=> '', 'content' => '');

    if ($goods_id > 0)
    {
        $result = get_goods_fittings($goods_id);
    }
    else
    {
        $result['error'] = 1;
        $result['message'] = 'NO GOODS ID';
    }
    die($json->encode($result));

}

/*------------------------------------------------------ */
//-- 根据order_id搜索订单商品信息
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'search_order_goods')
{
    include_once(ROOT_PATH . 'includes/cls_json.php');
    $json = new JSON();

    $order_id = intval($_REQUEST['order_id']);
    $order_sn = trim($_REQUEST['order_sn']);
    $result = array('error' => 0, 'message'=> '', 'content' => '');

    if ($order_id <= 0) {
        $sql = "SELECT order_id FROM {$ecs->table('order_info')} WHERE order_sn = '{$order_sn}'";
        $order_id = $db->getOne($sql);
    }


    if ($order_id > 0)
    {
        $sql = "SELECT * FROM {$ecs->table('order_goods')} WHERE order_id = '{$order_id}'";
        $result['goods_list'] = $db->getAll($sql);
    }
    else
    {
        $result['error'] = 1;
        $result['message'] = 'NO GOODS ID';
    }
    die($json->encode($result));

}

/*------------------------------------------------------ */
//-- 根据goods_id style_id搜索商品信息
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'getGoodsStylePrice')
{
    include_once(ROOT_PATH . 'includes/cls_json.php');
    $json = new JSON();

    $goods_id = intval($_REQUEST['goods_id']);
    $style_id = intval($_REQUEST['style_id']);
    $is_rma_exchange = $_REQUEST['is_rma_exchange'];
    $pre_goods_price = $_REQUEST['pre_goods_price'];
    $result = array('error' => 0, 'message'=> '', 'content' => '');

    if ($goods_id > 0)
    {
        $sql = "SELECT gs.style_price FROM {$ecs->table('goods_style')} gs, {$ecs->table('style')} s WHERE gs.style_id = s.style_id AND gs.goods_id = '$goods_id' AND s.style_id = '$style_id' ";
        if($is_rma_exchange){
        	$result['price'] = $pre_goods_price;
        }else{
        	$result['price'] = $db->getOne($sql);
        }
    }
    else
    {
        $result['error'] = 1;
        $result['message'] = 'NO GOODS ID';
    }
    die($json->encode($result));

}

/*------------------------------------------------------ */
//-- 编辑收货单号
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_invoice_no')
{
    /* 检查权限 */
    check_authz_json('order_edit');

    $no = empty($_POST['val']) ? 'N/A' : trim($_POST['val']);
    $no = $no=='N/A' ? '' : $no;
    $order_id = empty($_POST['id']) ? 0 : intval($_POST['id']);

    if ($order_id == 0)
    {
        make_json_error('NO ORDER ID');
        exit;
    }

    $sql = 'UPDATE ' . $GLOBALS['ecs']->table('order_info') . " SET invoice_no='$no' WHERE order_id = '$order_id'";
    if ($GLOBALS['db']->query($sql))
    {
        if (empty($no))
        {
            make_json_result('N/A');
        }
        else
        {
            make_json_result(stripcslashes($no));
        }
    }
    else
    {
        make_json_error($GLOBALS['db']->errorMsg());
    }
}

/*------------------------------------------------------ */
//-- 编辑发票抬头
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_invoice_payee'){
    include_once(ROOT_PATH . 'includes/cls_json.php');
    $json = new JSON();
    $result = false;
    
    $order_id = intval($_REQUEST['order_id']);
    $inv_payee = $_REQUEST['inv_payee'];
    $order_info = $db->getRow(sprintf("select order_id, order_status, shipping_status, pay_status, inv_payee from ecs_order_info where order_id = '%d'", $order_id));
    if($order_info){
        // 更新发票抬头
        $db->query(sprintf("update ecs_order_info set need_invoice='Y', inv_payee='%s' where order_id= '%d' limit 1", $inv_payee, $order_id));
        // 添加action记录
        $sql = sprintf("insert into ecs_order_action (order_id, action_user, order_status, shipping_status,  pay_status, action_time, action_note, invoice_status, shortage_status, note_type) " .
        		"  values ('%d', '%s', '%d', '%d', '%d', now(), '%s', -1, 0, '')"
        		  , intval($order_info['order_id']), $_SESSION['admin_name'], intval($order_info['order_status']), intval($order_info['shipping_status'])
        		  , intval($order_info['pay_status']), '修改发票抬头：'.$order_info['inv_payee'].' --> '.$inv_payee
        		);
        
        $db->query($sql);
    	
    	$result = true;
    }
    
    die($json->encode($result));
	
}
/*------------------------------------------------------ */
//-- 编辑付款备注
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_pay_note')
{
    /* 检查权限 */
    check_authz_json('order_edit');

    $no = empty($_POST['val']) ? 'N/A' : trim($_POST['val']);
    $no = $no=='N/A' ? '' : $no;
    $order_id = empty($_POST['id']) ? 0 : intval($_POST['id']);

    if ($order_id == 0)
    {
        make_json_error('NO ORDER ID');
        exit;
    }

    $sql = 'UPDATE ' . $GLOBALS['ecs']->table('order_info') . " SET pay_note='$no' WHERE order_id = '$order_id'";
    if ($GLOBALS['db']->query($sql))
    {
        if (empty($no))
        {
            make_json_result('N/A');
        }
        else
        {
            make_json_result(stripcslashes($no));
        }
    }
    else
    {
        make_json_error($GLOBALS['db']->errorMsg());
    }
}

/**
 * 取得状态列表
 * @param   string  $type   类型：all | order | shipping | payment
 */
function get_status_list($type = 'all')
{
    global $_LANG;

    $list = array();

    if ($type == 'all' || $type == 'order')
    {
        $pre = $type == 'all' ? 'os_' : '';
        foreach ($_LANG['os'] AS $key => $value)
        {
            $list[$pre . $key] = $value;
        }
    }

    if ($type == 'all' || $type == 'shipping')
    {
        $pre = $type == 'all' ? 'ss_' : '';
        foreach ($_LANG['ss'] AS $key => $value)
        {
            $list[$pre . $key] = $value;
        }
    }

    if ($type == 'all' || $type == 'payment')
    {
        $pre = $type == 'all' ? 'ps_' : '';
        foreach ($_LANG['ps'] AS $key => $value)
        {
            $list[$pre . $key] = $value;
        }
    }
    return $list;
}

/**
 * 改变订单中商品库存
 * @param   int     $order_id   订单号
 * @param   bool    $is_dec     是否减少（发货时减少，退货时增加）
 */
function change_order_goods_storage($order_id, $is_dec = true)
{
    global $ecs, $db;

    /* 查询订单商品信息 */
    $sql = "SELECT goods_id, SUM(goods_number) AS num FROM " . $ecs->table('order_goods') .
    " WHERE order_id = '$order_id' AND is_real = 1 GROUP BY goods_id ";
    $res = $db->query($sql);
    if ($res)
    {
        while ($row = $db->fetchRow($res))
        {
            if ($is_dec)
            {
                $sql = "UPDATE " . $ecs->table('goods') .
                " SET goods_number = goods_number - '" . $row['num'] . "' " .
                " WHERE goods_id = '" . $row['goods_id'] . "' LIMIT 1";
            }
            else
            {
                $sql = "UPDATE " . $ecs->table('goods') .
                " SET goods_number = goods_number + '" . $row['num'] . "' " .
                " WHERE goods_id = '" . $row['goods_id'] . "' LIMIT 1";
            }
            $db->query($sql);
        }
    }
}

/**
 * 退回余额、积分、红包（取消、无效、退货时），把订单使用余额、积分、红包设为0
 * @param   array   $order  订单信息
 */
function return_user_surplus_integral_bonus($order)
{
    /* 处理余额、积分、红包 */
    if ($order['user_id'] > 0)
    {
        $user = user_info($order['user_id']);
        $arr  = array(
        'user_money' => $user['user_money'] + $order['surplus'],
        'pay_points' => $user['pay_points'] + $order['integral']
        );
        update_user($order['user_id'], $arr);

        /* 生成购物帐户明细 */
        if ($order['surplus'] > 0)
        {
            $account = array(
            'user_id'       => $order['user_id'],
            'admin_user'    => $_SESSION['admin_name'],
            'amount'        => $order['surplus'],
            'add_time'      => time(),
            'paid_time'     => time(),
            'admin_note'    => $order['order_sn'],
            'process_type'  => SURPLUS_CANCELORDER,
            'is_paid'       => 1
            );
            $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('user_account'), $account, 'INSERT');
        }

        if ($order['bonus_id'] > 0)
        {
            unuse_bonus($order['bonus_id']);
        }

        /* 修改订单 */
        $arr = array(
        'bonus_id'  => 0,
        'bonus'     => 0,
        'integral'  => 0,
        'integral_money'    => 0,
        'surplus'   => 0
        );
        update_order($order['order_id'], $arr);
    }
}

/**
 * 取得订单应该发放的红包
 * @param   int     $order_id   订单id
 * @return  array
 */
function order_bonus($order_id)
{
    /* 查询按商品发的红包 */
    $today = date('Y-m-d');
    $sql = "SELECT b.type_id, b.type_money, SUM(o.goods_number) AS number " .
    "FROM " . $GLOBALS['ecs']->table('order_goods') . " AS o, " .
    $GLOBALS['ecs']->table('goods') . " AS g, " .
    $GLOBALS['ecs']->table('bonus_type') . " AS b " .
    " WHERE o.order_id = '$order_id' " .
    " AND o.is_gift = 0 " .
    " AND o.goods_id = g.goods_id " .
    " AND g.bonus_type_id = b.type_id " .
    " AND b.send_type = '" . SEND_BY_GOODS . "' " .
    " AND b.send_startdate <= '$today' " .
    " AND b.send_enddate >= '$today' " .
    " GROUP BY b.type_id ";
    $list = $GLOBALS['db']->getAll($sql);

    /* 查询定单中非赠品总金额 */
    $amount = order_amount($order_id, false);

    /* 查询订单日期 */
    $sql = "SELECT LEFT(order_time, 10) " .
    "FROM " . $GLOBALS['ecs']->table('order_info') .
    " WHERE order_id = '$order_id' LIMIT 1";
    $order_date = $GLOBALS['db']->getOne($sql);

    /* 查询按订单发的红包 */
    $sql = "SELECT type_id, type_money, IFNULL(ROUND('$amount' / min_amount), 1) AS number " .
    "FROM " . $GLOBALS['ecs']->table('bonus_type') .
    "WHERE send_type = '" . SEND_BY_ORDER . "' " .
    "AND send_startdate <= '$order_date' " .
    "AND send_enddate >= '$order_date' ";
    $list = array_merge($list, $GLOBALS['db']->getAll($sql));

    return $list;
}

/**
 * 发红包：发货时发红包
 * @param   int     $order_id   订单号
 * @return  bool
 */
function send_order_bonus($order_id)
{
    /* 取得订单应该发放的红包 */
    $bonus_list = order_bonus($order_id);

    /* 如果有红包，统计并发送 */
    if ($bonus_list)
    {
        /* 用户信息 */
        $sql = "SELECT u.user_id, u.user_name, u.email " .
        "FROM " . $GLOBALS['ecs']->table('order_info') . " AS o, " .
        $GLOBALS['ecs']->table('users') . " AS u " .
        "WHERE o.order_id = '$order_id' " .
        "AND o.user_id = u.user_id ";
        $user = $GLOBALS['db']->getRow($sql);

        /* 统计 */
        $count = 0;
        $money = '';
        foreach ($bonus_list AS $bonus)
        {
            $count += $bonus['number'];
            $money .= price_format($bonus['type_money']) . ' [' . $bonus['number'] . '], ';

            /* 修改用户红包 */
            $sql = "INSERT INTO " . $GLOBALS['ecs']->table('user_bonus') . " (bonus_type_id, user_id) " .
            "VALUES('$bonus[type_id]', '$user[user_id]')";
            for ($i = 0; $i < $bonus['number']; $i++)
            {
                if (!$GLOBALS['db']->query($sql))
                {
                    return $GLOBALS['db']->errorMsg();
                }
            }
        }

        /* 发送邮件 */
        $tpl = get_mail_template('send_bonus');
        $GLOBALS['smarty']->assign('count', $count);
        $GLOBALS['smarty']->assign('money', $money);
        $GLOBALS['smarty']->assign('shop_name', $GLOBALS['_CFG']['shop_name']);
        $GLOBALS['smarty']->assign('send_date', date('Y-m-d'));
        $GLOBALS['smarty']->assign('sent_date', date('Y-m-d'));
        $content = $GLOBALS['smarty']->fetch('db:send_bonus');
        send_mail($user['user_name'], $user['email'], $tpl['template_subject'], $content, $tpl['is_html']);
    }

    return true;
}

/**
 * 返回订单发放的红包
 * @param   int     $order_id   订单id
 */
function return_order_bonus($order_id)
{
    /* 取得订单应该发放的红包 */
    $bonus_list = order_bonus($order_id);

    /* 删除 */
    if ($bonus_list)
    {
        /* 取得订单信息 */
        $order = order_info($order_id);
        $user_id = $order['user_id'];

        foreach ($bonus_list AS $bonus)
        {
            $sql = "DELETE FROM " . $GLOBALS['ecs']->table('user_bonus') .
            " WHERE bonus_type_id = '$bonus[type_id]' " .
            "AND user_id = '$user_id' " .
            "AND order_id = '0' LIMIT " . $bonus['number'];
            $GLOBALS['db']->query($sql);
        }
    }
}

/**
 * 更新订单总金额
 * @param   int     $order_id   订单id
 * @return  bool
 */
function update_order_amount($order_id)
{
    $sql = "UPDATE " . $GLOBALS['ecs']->table('order_info') .
    " SET order_amount = goods_amount " .
    "  + shipping_fee " .
    "  + insure_fee " .
    "  + pay_fee " .
    "  + pack_fee " .
    "  + card_fee " .
    //"  - money_paid " .
    //"  - surplus " .
    "  + integral_money " .
    "  + bonus " .
    "WHERE order_id = '$order_id' LIMIT 1";

    return $GLOBALS['db']->query($sql);
}

/**
 * 返回某个订单可执行的操作列表，包括权限判断
 * @param   array   $order      订单信息 order_status, shipping_status, pay_status
 * @param   bool    $is_cod     支付方式是否货到付款
 * @return  array   可执行的操作  confirm, pay, unpay, ship, unship, receive, cancel, invalid, return, drop
 * 格式 array('confirm' => true, 'pay' => true)
 */
function operable_list($order)
{
    /* 取得订单状态、发货状态、付款状态 */
    $os = $order['order_status'];
    $ss = $order['shipping_status'];
    $ps = $order['pay_status'];

    /* 取得订单操作权限 */
    $actions = $_SESSION['action_list'];
    if ($actions == 'all')
    {
        $priv_list  = array('os' => true, 'ss' => true, 'ps' => true, 'edit' => true);
    }
    else
    {
        $actions    = ',' . $actions . ',';
        $priv_list  = array(
        'os'    => strpos($actions, ',order_os_edit,') !== false,
        'ss'    => strpos($actions, ',order_ss_edit,') !== false,
        'ps'    => strpos($actions, ',order_ps_edit,') !== false,
        'edit'  => strpos($actions, ',order_edit,') !== false
        );
    }

    /* 取得订单支付方式是否货到付款 */
    $payment = payment_info($order['pay_id']);
    $is_cod  = $payment['is_cod'] == 1;

    /* 根据状态返回可执行操作 */
    $list = array();
    if (OS_UNCONFIRMED == $os)
    {
        /* 状态：未确认 => 未付款、未发货 */
        if ($priv_list['os'])
        {
            $list['confirm']    = true; // 确认
            $list['invalid']    = true; // 无效
            $list['cancel']     = true; // 取消
            if ($is_cod)
            {
                /* 货到付款 */
                if ($priv_list['ss'])
                {
                    $list['ship'] = true; // 发货
                }
            }
            else
            {
                /* 不是货到付款 */
                if ($priv_list['ps'])
                {
                    $list['pay'] = true;  // 付款
                }
            }
        }
    }
    elseif (OS_CONFIRMED == $os)
    {
        /* 状态：已确认 */
        if (PS_UNPAYED == $ps)
        {
            /* 状态：已确认、未付款 */
            if (SS_UNSHIPPED == $ss)
            {
                /* 状态：已确认、未付款、未发货 */
                if ($priv_list['os'])
                {
                    $list['cancel'] = true; // 取消
                    $list['invalid'] = true; // 无效
                }
                if ($is_cod)
                {
                    /* 货到付款 */
                    if ($priv_list['ss'])
                    {
                        $list['ship'] = true; // 发货
                    }
                }
                else
                {
                    /* 不是货到付款 */
                    if ($priv_list['ps'])
                    {
                        $list['pay'] = true; // 付款
                    }
                }
            }
            else
            {
                /* 状态：已确认、未付款、已发货或已收货 => 货到付款 */
                if ($priv_list['ps'])
                {
                    $list['pay'] = true; // 付款
                }
                if ($priv_list['ss'])
                {
                    if (SS_SHIPPED == $ss)
                    {
                        $list['receive'] = true; // 收货确认
                    }
                    $list['unship'] = true; // 设为未发货
                    if ($priv_list['os'])
                    {
                        $list['return'] = true; // 退货
                    }
                }
            }
        }
        else
        {
            /* 状态：已确认、已付款和付款中 */
            if (SS_UNSHIPPED == $ss)
            {
                /* 状态：已确认、已付款和付款中、未发货 => 不是货到付款 */
                if ($priv_list['ss'])
                {
                    $list['ship'] = true; // 发货
                }
                if ($priv_list['ps'])
                {
                    $list['unpay'] = true; // 设为未付款
                    if ($priv_list['os'])
                    {
                        $list['cancel'] = true; // 取消
                    }
                }
            }
            else
            {
                /* 状态：已确认、已付款和付款中、已发货或已收货 */
                if ($priv_list['ss'])
                {
                    if (SS_SHIPPED == $ss)
                    {
                        $list['receive'] = true; // 收货确认
                    }
                    if (!$is_cod)
                    {
                        $list['unship'] = true; // 设为未发货
                    }
                }
                if ($priv_list['ps'] && $is_cod)
                {
                    $list['unpay']  = true; // 设为未付款
                }
                if ($priv_list['os'] && $priv_list['ss'] && $priv_list['ps'])
                {
                    $list['return'] = true; // 退货（包括退款）
                }
            }
        }
    }
    elseif (OS_CANCELED == $os)
    {
        /* 状态：取消 */
        if ($priv_list['os'])
        {
            $list['confirm'] = true;
        }
        if ($priv_list['edit'])
        {
            $list['remove'] = true;
        }
    }
    elseif (OS_INVALID == $os)
    {
        /* 状态：无效 */
        if ($priv_list['os'])
        {
            $list['confirm'] = true;
        }
        if ($priv_list['edit'])
        {
            $list['remove'] = true;
        }
    }
    elseif (OS_RETURNED == $os)
    {
        /* 状态：退货 */
        if ($priv_list['os'])
        {
            $list['confirm'] = true;
        }
    }

    /* 修正发货操作 */
    if (!empty($list['ship']))
    {
        /* 如果是团购活动且未处理成功，不能发货 */
        if ($order['extension_code'] == 'group_buy')
        {
            include_once(ROOT_PATH . 'includes/lib_goods.php');
            $group_buy = group_buy_info(intval($order['extension_id']));
            if ($group_buy['status'] != GBS_SUCCEED)
            {
                unset($list['ship']);
            }
        }
    }

    return $list;
}

/**
 * 处理编辑订单时订单金额变动
 * @param   array   $order  订单信息
 * @param   array   $msgs   提示信息
 * @param   array   $links  链接信息
 */
function handle_order_money_change($order, &$msgs, &$links)
{
    $order_id = $order['order_id'];
    if ($order['pay_status'] == PS_PAYED || $order['pay_status'] == PS_PAYING)
    {
        /* 应付款金额 */
        $money_dues = $order['order_amount'];
        if ($money_dues > 0)
        {
            /* 修改订单为未付款 */
            update_order($order_id, array('pay_status' => PS_UNPAYED, 'pay_time' => 0));
            $msgs[]     = $GLOBALS['_LANG']['amount_increase'];
            $links[]    = array('text' => $GLOBALS['_LANG']['order_info'], 'href' => 'order.php?act=info&order_id=' . $order_id);
        }
        elseif ($money_dues < 0)
        {
            $anonymous  = $order['user_id'] > 0 ? 0 : 1;
            $msgs[]     = $GLOBALS['_LANG']['amount_decrease'];
            $links[]    = array('text' => $GLOBALS['_LANG']['refund'], 'href' => 'order.php?act=process&func=load_refund&anonymous=' .
            $anonymous . '&order_id=' . $order_id . '&refund_amount=' . abs($money_dues));
        }
    }
}

/**
 *  获取订单列表信息
 *
 * @access  public
 * @param
 *
 * @return void
 */
function order_list()
{
    /* 过滤信息 */
    $filter['order_sn'] = empty($_REQUEST['order_sn']) ? '' : trim($_REQUEST['order_sn']);
    $filter['consignee'] = empty($_REQUEST['consignee']) ? '' : trim($_REQUEST['consignee']);
    $filter['email'] = empty($_REQUEST['email']) ? '' : trim($_REQUEST['email']);
    $filter['address'] = empty($_REQUEST['address']) ? '' : trim($_REQUEST['address']);
    $filter['zipcode'] = empty($_REQUEST['zipcode']) ? '' : trim($_REQUEST['zipcode']);
    $filter['tel'] = empty($_REQUEST['tel']) ? '' : trim($_REQUEST['tel']);
    $filter['mobile'] = empty($_REQUEST['mobile']) ? 0 : intval($_REQUEST['mobile']);
    $filter['country'] = empty($_REQUEST['country']) ? 0 : intval($_REQUEST['country']);
    $filter['province'] = empty($_REQUEST['province']) ? 0 : intval($_REQUEST['province']);
    $filter['city'] = empty($_REQUEST['city']) ? 0 : intval($_REQUEST['city']);
    $filter['district'] = empty($_REQUEST['district']) ? 0 : intval($_REQUEST['district']);
    $filter['shipping_id'] = empty($_REQUEST['shipping_id']) ? 0 : intval($_REQUEST['shipping_id']);
    $filter['pay_id'] = empty($_REQUEST['pay_id']) ? 0 : intval($_REQUEST['pay_id']);
    $filter['order_status'] = isset($_REQUEST['order_status']) ? intval($_REQUEST['order_status']) : -1;
    $filter['shipping_status'] = isset($_REQUEST['shipping_status']) ? intval($_REQUEST['shipping_status']) : -1;
    $filter['pay_status'] = isset($_REQUEST['pay_status']) ? intval($_REQUEST['pay_status']) : -1;
    $filter['user_id'] = empty($_REQUEST['user_id']) ? 0 : intval($_REQUEST['user_id']);
    $filter['user_name'] = empty($_REQUEST['user_name']) ? '' : trim($_REQUEST['user_name']);
    $filter['composite_status'] = isset($_REQUEST['composite_status']) ? intval($_REQUEST['composite_status']) : -1;
    $filter['group_buy_id'] = isset($_REQUEST['group_buy_id']) ? intval($_REQUEST['group_buy_id']) : 0;

    $filter['sort_by'] = empty($_REQUEST['sort_by']) ? 'order_time' : trim($_REQUEST['sort_by']);
    $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC' : trim($_REQUEST['sort_order']);

    $where = 'WHERE 1 ';
    if ($filter['order_sn'])
    {
        $where .= " AND o.order_sn LIKE '%" . mysql_like_quote($filter['order_sn']) . "%'";
    }
    if ($filter['consignee'])
    {
        $where .= " AND o.consignee LIKE '%" . mysql_like_quote($filter['consignee']) . "%'";
    }
    if ($filter['email'])
    {
        $where .= " AND o.email LIKE '%" . mysql_like_quote($filter['email']) . "%'";
    }
    if ($filter['address'])
    {
        $where .= " AND o.address LIKE '%" . mysql_like_quote($filter['address']) . "%'";
    }
    if ($filter['zipcode'])
    {
        $where .= " AND o.zipcode LIKE '%" . mysql_like_quote($filter['zipcode']) . "%'";
    }
    if ($filter['tel'])
    {
        $where .= " AND o.tel LIKE '%" . mysql_like_quote($filter['tel']) . "%'";
    }
    if ($filter['mobile'])
    {
        $where .= " AND o.mobile LIKE '%" .mysql_like_quote($filter['mobile']) . "%'";
    }
    if ($filter['country'])
    {
        $where .= " AND o.country = '$filter[country]'";
    }
    if ($filter['province'])
    {
        $where .= " AND o.province = '$filter[province]'";
    }
    if ($filter['city'])
    {
        $where .= " AND o.city = '$filter[city]'";
    }
    if ($filter['district'])
    {
        $where .= " AND o.district = '$filter[district]'";
    }
    if ($filter['shipping_id'])
    {
        $where .= " AND o.shipping_id  = '$filter[shipping_id]'";
    }
    if ($filter['pay_id'])
    {
        $where .= " AND o.pay_id  = '$filter[pay_id]'";
    }
    if ($filter['order_status'] != -1)
    {
        $where .= " AND o.order_status  = '$filter[order_status]'";
    }
    if ($filter['shipping_status'] != -1)
    {
        $where .= " AND o.shipping_status = '$filter[shipping_status]'";
    }
    if ($filter['pay_status'] != -1)
    {
        $where .= " AND o.pay_status = '$filter[pay_status]'";
    }
    if ($filter['user_id'])
    {
        $where .= " AND o.user_id = '$filter[user_id]'";
    }
    if ($filter['user_name'])
    {
        $where .= " AND u.user_name LIKE '%" . mysql_like_quote($filter['user_name']) . "%'";
    }

    //综合状态
    switch($filter['composite_status'])
    {
        case CS_AWAIT_PAY :
            $where .= " AND o.order_status = '" . OS_CONFIRMED . "' ";
            $where .= " AND o.pay_status = '" . PS_UNPAYED . "' ";
            $ids = get_pay_ids();
            $where .= " AND ((o.pay_id " . db_create_in($ids['is_cod']) . " AND o.shipping_status <> '" . SS_UNSHIPPED . "') OR o.pay_id " . db_create_in($ids['is_not_cod']) . ") ";
            break;

        case CS_AWAIT_SHIP :
            $where .= " AND o.order_status = '" . OS_CONFIRMED . "' ";
            $where .= " AND o.shipping_status = '" . SS_UNSHIPPED . "' ";
            $ids = get_pay_ids();
            $where .= " AND ((o.pay_id " . db_create_in($ids['is_not_cod']) . " AND o.pay_status <> '" . PS_UNPAYED . "') OR o.pay_id " . db_create_in($ids['is_cod']) . ") ";
            break;

        case CS_FINISHED :
            $where .= " AND o.order_status = '" . OS_CONFIRMED . "' ";
            $where .= " AND (o.pay_status = '" . PS_PAYED . "' OR o.pay_status = '" . PS_PAYING . "') ";
            $where .= " AND (o.shipping_status = '" . SS_SHIPPED . "' OR o.shipping_status = '" . SS_RECEIVED . "') ";
            break;

        default:
            if ($filter['composite_status'] != -1)
            {
                $where .= " AND o.order_status = '$filter[composite_status]' ";
            }
    }

    /* 团购订单 */
    if ($filter['group_buy_id'])
    {
        $where .= " AND o.extension_code = 'group_buy' AND o.extension_id = '$filter[group_buy_id]' ";
    }

    /* 分页大小 */
    $filter['page'] = empty($_REQUEST['page']) || (intval($_REQUEST['page']) <= 0) ? 1 : intval($_REQUEST['page']);

    if (isset($_REQUEST['page_size']) && intval($_REQUEST['page_size']) > 0)
    {
        $filter['page_size'] = intval($_REQUEST['page_size']);
    }
    elseif (isset($_COOKIE['ECSCP']['page_size']) && intval($_COOKIE['ECSCP']['page_size']) > 0)
    {
        $filter['page_size'] = intval($_COOKIE['ECSCP']['page_size']);
    }
    else
    {
        $filter['page_size'] = 15;
    }

    /* 记录总数 */
    if ($filter['user_name'])
    {
        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('order_info') . " AS o ,".
        $GLOBALS['ecs']->table('users') . " AS u " . $where;
    }
    else
    {
        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('order_info') . " AS o ". $where;
    }

    $record_count   = $GLOBALS['db']->getOne($sql);
    $page_count     = $record_count > 0 ? ceil($record_count / $filter['page_size']) : 1;

    /* 查询 */
    $sql = "SELECT o.order_id, o.order_sn, o.order_time, o.order_status, o.shipping_status, o.order_amount, o.money_paid," .
    "o.pay_status, o.consignee, o.address, o.email, o.tel, o.extension_code, o.extension_id, " .
    "(o.goods_amount + o.shipping_fee + o.insure_fee + o.pay_fee + o.pack_fee + o.card_fee) AS total_fee, " .
    "IFNULL(u.user_name, '" .$GLOBALS['_LANG']['anonymous']. "') AS buyer ".
    " FROM " . $GLOBALS['ecs']->table('order_info') . " AS o " .
    " LEFT JOIN " .$GLOBALS['ecs']->table('users'). " AS u ON u.user_id=o.user_id ". $where .
    " ORDER BY $filter[sort_by] $filter[sort_order] ".
    " LIMIT " . ($filter['page'] - 1) * $filter['page_size'] . ",$filter[page_size]";

    $row = $GLOBALS['db']->getAll($sql);

    /* 格式话数据 */
    foreach ($row AS $key => $value)
    {
        $row[$key]['formated_order_amount'] = price_format($value['order_amount']);
        $row[$key]['formated_money_paid'] = price_format($value['money_paid']);
        $row[$key]['formated_total_fee'] = price_format($value['total_fee']);
        $row[$key]['short_order_time'] = date('m-d H:i', strtotime($value['order_time']));
        if ($value['order_status'] == OS_INVALID || $value['order_status'] == OS_CANCELED)
        {
            /* 如果该订单为无效或取消则显示删除链接 */
            $row[$key]['can_remove'] = 1;
        }
        else
        {
            $row[$key]['can_remove'] = 0;
        }
    }
    foreach (array('order_sn', 'consignee', 'email', 'address', 'zipcode', 'tel', 'user_name') AS $val)
    {
        $filter[$val] = stripslashes($filter[$val]);
    }
    $arr = array('orders' => $row, 'filter' => $filter, 'page_count' => $page_count, 'record_count' => $record_count);

    return $arr;
}

/**
 * 更新订单对应的 pay_log
 * 如果未支付，修改支付金额；否则，生成新的支付log
 * @param   int     $order_id   订单id
 */
function update_pay_log($order_id)
{
    $order_id = intval($order_id);
    if ($order_id > 0)
    {
        $sql = "SELECT order_amount FROM " . $GLOBALS['ecs']->table('order_info') .
        " WHERE order_id = '$order_id'";
        $order_amount = $GLOBALS['db']->getOne($sql);
        if (!is_null($order_amount))
        {
            $sql = "SELECT log_id FROM " . $GLOBALS['ecs']->table('pay_log') .
            " WHERE order_id = '$order_id'" .
            " AND order_type = '" . PAY_ORDER . "'" .
            " AND is_paid = 0";
            $log_id = intval($GLOBALS['db']->getOne($sql));
            if ($log_id > 0)
            {
                /* 未付款，更新支付金额 */
                $sql = "UPDATE " . $GLOBALS['ecs']->table('pay_log') .
                " SET order_amount = '$order_amount' " .
                "WHERE log_id = '$log_id' LIMIT 1";
            }
            else
            {
                /* 已付款，生成新的pay_log */
                $sql = "INSERT INTO " . $GLOBALS['ecs']->table('pay_log') .
                " (order_id, order_amount, order_type, is_paid)" .
                "VALUES('$order_id', '$order_amount', '" . PAY_ORDER . "', 0)";
            }
            $GLOBALS['db']->query($sql);
        }
    }
}

/**
 * 取得某订单应该赠送的积分数
 * @param   array   $order  订单
 * @return  int     积分数
 */
function integral_to_give($order)
{
    /* 判断是否团购 */
    if ($order['extension_code'] == 'group_buy')
    {
        include_once(ROOT_PATH . 'includes/lib_goods.php');
        $group_buy = group_buy_info(intval($order['extension_id']));

        return $group_buy['gift_integral'];
    }
    else
    {
        // 商品总金额减去红包和积分支付的部分（剩下的是用现金支付的部分）
        return max(round($order['goods_amount'] - $order['bonus'] - $order['integral_money']), 0);
    }
}

?>