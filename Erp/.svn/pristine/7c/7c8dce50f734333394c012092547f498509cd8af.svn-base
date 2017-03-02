<?php

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

/**
 * 处理序列化的支付、配送的配置参数
 * 返回一个以name为索引的数组
 *
 * @access  public
 * @param   string       $cfg
 * @return  void
 */
function unserialize_config($cfg)
{
    if (is_string($cfg) && ($arr = unserialize($cfg)) !== false)
    {
        $config = array();

        foreach ($arr AS $key => $val)
        {
            $config[$val['name']] = $val['value'];
        }

        return $config;
    }
    else
    {
        return false;
    }
}
/**
 * 取得已安装的配送方式
 * @return  array   已安装的配送方式
 */
function shipping_list()
{
    $sql = 'SELECT shipping_id, shipping_name ' .
            'FROM ' . $GLOBALS['ecs']->table('shipping') .
            ' WHERE enabled = 1';

    return $GLOBALS['db']->getAll($sql);
}

/**
 * 取得配送方式信息
 * @param   int     $shipping_id    配送方式id
 * @return  array   配送方式信息
 */
function shipping_info($shipping_id)
{
    $sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('shipping') .
            " WHERE shipping_id = '$shipping_id' " .
            'AND enabled = 1';

    return $GLOBALS['db']->getRow($sql);
}

/**
 * 取得可用的配送方式列表
 * @param   array   $region_id_list     收货人地区id数组（包括国家、省、市、区）
 * @return  array   配送方式数组
 */
function available_shipping_list($region_id_list)
{
    $sql = 'SELECT s.shipping_id, s.shipping_code, s.shipping_name, ' .
                's.shipping_desc, s.insure, s.shipping_disabled_desc,s.self_work_time, s.support_cod, s.support_no_cod,s.support_biaoju, a.enabled, a.configure ' .
            'FROM ' . $GLOBALS['ecs']->table('shipping') . ' AS s, ' .
                $GLOBALS['ecs']->table('shipping_area') . ' AS a, ' .
                $GLOBALS['ecs']->table('area_region') . ' AS r ' .
            'WHERE r.region_id ' . db_create_in($region_id_list) .
            ' AND r.shipping_area_id = a.shipping_area_id AND a.shipping_id = s.shipping_id AND s.enabled = 1 '.
            " AND ".party_sql("s.party_id").
            ' ORDER BY shipping_order ';

    return $GLOBALS['db']->getAll($sql);
}

/**
 * 取得可用的配送方式列表 (模糊)
 * @param   array   $region_id_list     收货人地区id数组（包括国家、省、市、区）
 * @return  array   配送方式数组
 */
function available_shipping_list2($region_id_list)
{
    //如果有一个0值(比如缺少区)，我们尝试获得一个填入数据
    $i = 0;
    while(true)
    {
        while($i < count($region_id_list) &&  $region_id_list[$i]) $i++;
        if($i > 0 && $i < count($region_id_list))
        {
            $sql_0 = sprintf("SELECT r.region_id FROM %s AS r WHERE r.parent_id = %d", $GLOBALS['ecs']->table('region'), $region_id_list[$i-1]);
            $pid = $GLOBALS['db']->getOne($sql_0);
            if($pid)
            {
                $region_id_list[$i] = $pid;
            }
            else
            {
                break;
            }
        }
        else
        {
            break;
        }
    }
    $sql = 'SELECT s.shipping_id, s.shipping_code, s.shipping_name, ' .
                's.shipping_desc, s.insure, shipping_disabled_desc, s.support_cod, s.support_no_cod,s.support_biaoju, a.enabled, a.configure ' .
            'FROM ' . $GLOBALS['ecs']->table('shipping') . ' AS s, ' .
                $GLOBALS['ecs']->table('shipping_area') . ' AS a, ' .
                $GLOBALS['ecs']->table('area_region') . ' AS r ' .
            'WHERE r.region_id ' . db_create_in($region_id_list) .
            " AND ".party_sql("s.party_id").
            ' AND r.shipping_area_id = a.shipping_area_id AND a.shipping_id = s.shipping_id AND s.enabled = 1 AND a.enabled = 1 ORDER BY shipping_order ';
    return $GLOBALS['db']->getAll($sql);
}

/**
 * 取得某配送方式对应于某收货地址的区域信息
 * @param   int     $shipping_id        配送方式id
 * @param   array   $region_id_list     收货人地区id数组
 * @return  array   配送区域信息（config 对应着反序列化的 configure）
 */
function shipping_area_info($shipping_id, $region_id_list)
{
    $sql = "
        SELECT 
            s.shipping_id, s.shipping_code, s.shipping_name, s.shipping_desc, s.insure, 
            s.support_cod, s.support_no_cod, s.default_carrier_id, a.configure
        FROM {$GLOBALS['ecs']->table('shipping')}      AS s,
             {$GLOBALS['ecs']->table('shipping_area')} AS a,
             {$GLOBALS['ecs']->table('area_region')}   AS r 
        WHERE s.shipping_id = '{$shipping_id}'
            AND r.region_id " . db_create_in($region_id_list) ."
            AND r.shipping_area_id = a.shipping_area_id AND a.shipping_id = s.shipping_id AND s.enabled = 1
    ";
    $row = $GLOBALS['db']->getRow($sql);

    if (!empty($row))
    {
        $shipping_config = unserialize_config($row['configure']);
        if (isset($shipping_config['pay_fee']))
        {
            if (strpos($shipping_config['pay_fee'], '%') !== false)
            {
                $row['pay_fee'] = floatval($shipping_config['pay_fee']) . '%';
            }
            else
            {
                 $row['pay_fee'] = floatval($shipping_config['pay_fee']);
            }
        }
        else
        {
            $row['pay_fee'] = 0.00;
        }
    }

    return $row;
}

/**
 * 计算运费
 * @param   string  $shipping_code      配送方式代码
 * @param   mix     $shipping_config    配送方式配置信息
 * @param   float   $goods_weight       商品重量
 * @param   float   $goods_amount       商品金额
 * bonus@return  float   运费
 */
function shipping_fee($shipping_code, $shipping_config, $goods_weight, $goods_amount)
{

    if (!is_array($shipping_config))
    {
        $shipping_config = unserialize($shipping_config);
    }

    $filename = ROOT_PATH . 'includes/modules/shipping/' . $shipping_code . '.php';
    if (file_exists($filename))
    {
        include_once($filename);

        $obj = new $shipping_code($shipping_config);

        return $obj->calculate($goods_weight, $goods_amount);
    }
    else
    {
        return 0;
    }
}

/**
 * 计算手续费
 * @param   string  $shipping_code      配送方式代码
 * @param   mix     $shipping_config    配送方式配置信息
 * @param   float   $goods_weight       商品重量
 * @param   float   $goods_amount       商品金额
 * @return  float   运费
 */
function shipping_proxy_fee($shipping_code, $shipping_config, $goods_weight, $goods_amount)
{

    if (!is_array($shipping_config))
    {
        $shipping_config = unserialize($shipping_config);
    }

    $filename = ROOT_PATH . 'includes/modules/shipping/' . $shipping_code . '.php';
    if (file_exists($filename))
    {
        include_once($filename);

        $obj = new $shipping_code($shipping_config);
        if (method_exists($obj, 'calc_proxy_fee'))
        {
            return $obj->calc_proxy_fee($goods_amount);
        }
        else
        {
            return 0;
        }
    }
    else
    {
        return 0;
    }
}


/**
 * 获取指定配送的保价费用
 *
 * @access  public
 * @param   string      $shipping_code  配送方式的code
 * @param   float       $goods_amount   保价金额
 * @param   mix         $insure         保价比例
 * @return  float
 */
function shipping_insure_fee($shipping_code, $goods_amount, $insure)
{
    if (strpos($insure, '%') === false)
    {
        /* 如果保价费用不是百分比则直接返回该数值 */
        return floatval($insure);
    }
    else
    {
        $path = ROOT_PATH . 'includes/modules/shipping/' . $shipping_code . '.php';

        if (file_exists($path))
        {
            include_once($path);

            $shipping = new $shipping_code;
            $insure   = floatval($insure) / 100;

            if (method_exists($shipping, 'calculate_insure'))
            {
                return $shipping->calculate_insure($goods_amount, $insure);
            }
            else
            {
                return ceil($goods_amount * $insure);
            }
        }
        else
        {
            return false;
        }
    }
}

/**
 * 取得已安装的支付方式列表
 * @return  array   已安装的配送方式列表
 */
function payment_list()
{
    $sql = 'SELECT pay_id, pay_name ' .
            'FROM ' . $GLOBALS['ecs']->table('payment') .
            ' WHERE enabled = 1';

    return $GLOBALS['db']->getAll($sql);
}

/**
 * 取得支付方式信息
 * @param   int     $pay_id     支付方式id
 * @return  array   支付方式信息
 */
function payment_info($pay_id)
{
    $sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('payment') .
            " WHERE pay_id = '$pay_id' AND enabled = 1";

    return $GLOBALS['db']->getRow($sql);
}

/**
 * 获得订单需要支付的支付费用
 *
 * @access  public
 * @param   integer $payment_id
 * @param   float   $order_amount
 * @param   mix     $cod_fee
 * @return  float
 */
function pay_fee($payment_id, $order_amount, $cod_fee=null)
{
    $pay_fee = 0;
    $payment = payment_info($payment_id);
    $rate    = ($payment['is_cod'] && !is_null($cod_fee)) ? $cod_fee : $payment['pay_fee'];

    if (strpos($rate, '%') !== false)
    {
        /* 支付费用是一个比例 */
        $val     = floatval($rate) / 100;
        $pay_fee = $val > 0 ? $order_amount * $val /(1- $val) : 0;
    }
    else
    {
        $pay_fee = floatval($rate);
    }

    return round($pay_fee, 2);
}

/**
 * 取得可用的支付方式列表
 * @param   bool    $support_cod        配送方式是否支持货到付款
 * @param   int     $cod_fee            货到付款手续费（当配送方式支持货到付款时才传此参数）
 * @return  array   配送方式数组
 */
function available_payment_list($support_cod, $cod_fee = 0)
{
    $sql = 'SELECT pay_id, pay_code, pay_name, pay_fee, pay_desc, pay_config, is_cod, pay_order' .
            ' FROM ' . $GLOBALS['ecs']->table('payment') .
            ' WHERE enabled = 1 ';
    if (!$support_cod)
    {
        $sql .= 'AND is_cod = 0 '; // 如果不支持货到付款
    }
    $sql .= 'ORDER BY pay_order'; // 排序
    $res = $GLOBALS['db']->query($sql);

    $list = array();
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        if ($row['is_cod'] == '1')
        {
            $row['pay_fee'] = $cod_fee;
        }

        $row['format_pay_fee'] = strpos($row['pay_fee'], '%') !== false ? $row['pay_fee'] :
        price_format($row['pay_fee']);
        $list[] = $row;
    }

    return $list;
}

/**
 * 取得包装列表
 * @return  array   包装列表
 */
function pack_list()
{
    $sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('pack');
    $res = $GLOBALS['db']->query($sql);

    $list = array();
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $row['format_pack_fee'] = price_format($row['pack_fee']);
        $row['format_free_money'] = price_format($row['free_money']);
        $list[] = $row;
    }

    return $list;
}

/**
 * 取得包装信息
 * @param   int     $pack_id    包装id
 * @return  array   包装信息
 */
function pack_info($pack_id)
{
    $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('pack') .
            " WHERE pack_id = '$pack_id'";

    return $GLOBALS['db']->getRow($sql);
}

/**
 * 根据订单中的商品总额来获得包装的费用
 *
 * @access  public
 * @param   integer $pack_id
 * @param   float   $goods_amount
 * @return  float
 */
function pack_fee($pack_id, $goods_amount)
{
    $pack = pack_info($pack_id);

    $val = (floatval($pack['free_money']) <= $goods_amount && $pack['free_money'] > 0) ? 0 : floatval($pack['pack_fee']);

    return $val;
}

/**
 * 取得贺卡列表
 * @return  array   贺卡列表
 */
function card_list()
{
    $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('card');
    $res = $GLOBALS['db']->query($sql);

    $list = array();
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $row['format_card_fee'] = price_format($row['card_fee']);
        $row['format_free_money'] = price_format($row['free_money']);
        $list[] = $row;
    }

    return $list;
}

/**
 * 取得贺卡信息
 * @param   int     $card_id    贺卡id
 * @return  array   贺卡信息
 */
function card_info($card_id)
{
    $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('card') .
            " WHERE card_id = '$card_id'";

    return $GLOBALS['db']->getRow($sql);
}

/**
 * 根据订单中商品总额获得需要支付的贺卡费用
 *
 * @access  public
 * @param   integer $card_id
 * @param   float   $goods_amount
 * @return  float
 */
function card_fee($card_id, $goods_amount)
{
    $card = card_info($card_id);

    return ($card['free_money'] <= $goods_amount && $card['free_money'] > 0) ? 0 : $card['card_fee'];
}

/**
 * 取得订单信息
 * @param   int     $order_id   订单id（如果order_id > 0 就按id查，否则按sn查）
 * @param   string  $order_sn   订单号
 * @return  array   订单信息（金额都有相应格式化的字段，前缀是formated_）
 */
function order_info($order_id, $order_sn = '')
{
	global $_CFG;
	
    /* 计算订单各种费用之和的语句 */
    $total_fee = " (goods_amount + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee) AS total_fee ";
    $order_id = intval($order_id);
    if ($order_id > 0)
    {
        $sql = "SELECT *, " . $total_fee . " FROM " . $GLOBALS['ecs']->table('order_info') .
                " WHERE order_id = '$order_id' AND " . party_sql('party_id');
    }
    else
    {
        $sql = "SELECT *, " . $total_fee . "  FROM " . $GLOBALS['ecs']->table('order_info') .
                " WHERE order_sn = '$order_sn' AND " . party_sql('party_id');
    }
    $order = $GLOBALS['db']->getRow($sql);

    /* 格式化金额字段 */
    if ($order)
    {
        $order['formated_goods_amount']   = price_format($order['goods_amount']);
        $order['formated_shipping_fee']   = price_format($order['shipping_fee']);
        $order['formated_insure_fee']     = price_format($order['insure_fee']);
        $order['formated_pay_fee']        = price_format($order['pay_fee']);
        $order['formated_pack_fee']       = price_format($order['pack_fee']);
        $order['formated_card_fee']       = price_format($order['card_fee']);
        $order['formated_total_fee']      = price_format($order['total_fee']);
        $order['formated_money_paid']     = price_format($order['money_paid']);
        $order['formated_bonus']          = price_format($order['bonus']);
        $order['formated_integral_money'] = price_format($order['integral_money']);
        $order['formated_surplus']        = price_format($order['surplus']);
        $order['formated_real_paid']      = price_format($order['real_paid']);
    	$order['postscript ']        	  = $order['postscript'];
        $order['formated_order_amount']   = price_format(abs($order['order_amount']));
        $shipping_info = shipping_info($order['shipping_id']);
        if (!empty($shipping_info)) {
        	$order['shipping_name'] = $shipping_info['shipping_name'];
        }
        $payment_info = payment_info($order['pay_id']);
        if (!empty($payment_info)) {
        	$order['pay_name'] = $payment_info['pay_name'];
        }
        
        // 格式化状态
        $order['formated_order_status']    = $_CFG['adminvars']['order_status'][$order['order_status']];
        $order['formated_pay_status']      = $_CFG['adminvars']['pay_status'][$order['pay_status']];
        $order['formated_shipping_status'] = $_CFG['adminvars']['shipping_status'][$order['shipping_status']];
        
        // 将 order_type_id设置上
        if ( $order['order_type_id'] == '' && str_len($order['order_sn'])) {
            $order['order_type_id'] = 'SALE';
        }
    }

    return $order;
}

/**
 * 判断订单是否已完成
 * @param   array   $order  订单信息
 * @return  bool
 */
function order_finished($order)
{
    return $order['order_status']  == OS_CONFIRMED &&
        ($order['shipping_status'] == SS_SHIPPED || $order['shipping_status'] == SS_RECEIVED) &&
        ($order['pay_status']      == PS_PAYED   || $order['pay_status'] == PS_PAYING);
}

/**
 * 取得订单商品
 * @param   int     $order_id   订单id
 * @return  array   订单商品数组
 */
function order_goods($order_id)
{
    $sql = "SELECT rec_id, goods_id, style_id, goods_name, goods_sn, market_price, goods_number, " .
            "goods_price, goods_attr, is_real, parent_id, is_gift, return_points, " .
            "goods_price * goods_number AS subtotal " .
            "FROM " . $GLOBALS['ecs']->table('order_goods') .
            " WHERE order_id = '$order_id'";

    return $GLOBALS['db']->getAll($sql);
}

/**
 * 记录订单操作记录
 *
 * @access  public
 * @param   string  $order_sn           订单编号
 * @param   integer $order_status       订单状态
 * @param   integer $shipping_status    配送状态
 * @param   integer $pay_status         付款状态
 * @param   string  $note               备注
 * @param   string  $username           用户名，用户自己的操作则为 buyer
 * @return  void
 */
function order_action($order_sn, $order_status, $shipping_status, $pay_status, $note = '', $username = null)
{
    if (is_null($username))
    {
        $username = $_SESSION['admin_name'];
    }

    $sql = 'INSERT INTO ' . $GLOBALS['ecs']->table('order_action') .
                ' (order_id, action_user, order_status, shipping_status, pay_status, action_note, action_time) ' .
            'SELECT ' .
                "order_id, '$username', '$order_status', '$shipping_status', '$pay_status', '$note', '" . date('Y-m-d H:i:s') . "' " .
            'FROM ' . $GLOBALS['ecs']->table('order_info') . " WHERE order_sn = '$order_sn'";
    $GLOBALS['db']->query($sql);
}

/**
 * 取得订单总金额
 * @param   int     $order_id   订单id
 * @param   bool    $include_gift   是否包括赠品
 * @return  float   订单总金额
 */
function order_amount($order_id, $include_gift = true)
{
    $sql = "SELECT SUM(goods_price * goods_number) " .
            "FROM " . $GLOBALS['ecs']->table('order_goods') .
            " WHERE order_id = '$order_id'";
    if (!$include_gift)
    {
        $sql .= " AND is_gift = 0";
    }

    return floatval($GLOBALS['db']->getOne($sql));
}

/**
 * 取得某订单商品总重量和总金额（对应 cart_weight_price）
 * @param   int     $order_id   订单id
 * @return  array   ('weight' => **, 'amount' => **, 'formated_weight' => **)
 */
function order_weight_price($order_id)
{
    $sql = "SELECT SUM(g.goods_weight * o.goods_number) AS weight, " .
                "SUM(o.goods_price * o.goods_number) AS amount " .
            "FROM " . $GLOBALS['ecs']->table('order_goods') . " AS o, " .
                $GLOBALS['ecs']->table('goods') . " AS g " .
            "WHERE o.order_id = '$order_id' " .
            "AND o.goods_id = g.goods_id";
    $row = $GLOBALS['db']->getRow($sql);
    $row['weight'] = floatval($row['weight']);
    $row['amount'] = floatval($row['amount']);

    /* 格式化重量 */
    $row['formated_weight'] = formated_weight($row['weight']);

    return $row;
}

/**
 * 获得订单中的费用信息
 *
 * @access  public
 * @param   array   $order
 * @param   array   $goods
 * @param   array   $consignee
 * @param   bool    $is_gb_deposit  是否团购保证金（如果是，应付款金额只计算商品总额和支付费用，可以获得的积分取 $gift_integral）
 * @return  array
 */
function order_fee($order, $goods, $consignee)
{
    /* 初始化订单的扩展code */
    if (!isset($order['extension_code']))
    {
        $order['extension_code'] = '';
    }

/*
    if ($order['extension_code'] == 'group_buy')
    {
        $group_buy = group_buy_info($order['extension_id']);
    }
*/
    $total  = array('real_goods_count' => 0,
                    'gift_amount'      => 0,
                    'goods_price'      => 0,
                    'market_price'     => 0,
                    'pack_fee'         => 0,
                    'card_fee'         => 0,
                    'shipping_fee'     => 0,
                    'shipping_insure'  => 0,
                    'integral_money'   => 0,
                    'bonus'            => 0,
                    'surplus'          => 0,
                    'cod_fee'          => 0,
					'limit_integral'   => 0,//每个物品限制使用的欧币数
                    'pay_fee'          => 0);
    $weight = 0;


    /* 商品总价 */
    foreach ($goods AS $key => $val)
    {
        /* 统计实体商品的个数 */
        if ($val['is_real'])
        {
            $total['real_goods_count']++;
        }
		//echo($val['goods_price'].'<br>');
		//修改了这里把 goods_price 该成 shop_price
        $total['goods_price']  += $val['shop_price'] * $val['goods_number'];
        $total['market_price'] += $val['market_price'] * $val['goods_number'];
		$total['limit_integral'] += $val['limit_integral'] * $val['goods_number'];
    }
	//echo($total['goods_price']);
    $total['saving']    = $total['market_price'] - $total['goods_price'];
    $total['save_rate'] = $total['market_price'] ? round($total['saving'] * 100 / $total['market_price']) . '%' : 0;

    $total['goods_price_formated']  = price_format($total['goods_price']);
    $total['market_price_fomrated'] = price_format($total['market_price']);
    $total['saving_fomrated']       = price_format($total['saving']);

/*
    // 包装费用
    if (!empty($order['pack_id']))
    {
        $total['pack_fee']      = pack_fee($order['pack_id'], $total['goods_price']);
    }
    $total['pack_fee_formated'] = price_format($total['pack_fee']);

    // 贺卡费用
    if (!empty($order['card_id']))
    {
        $total['card_fee']      = card_fee($order['card_id'], $total['goods_price']);
    }
    $total['card_fee_formated'] = price_format($total['card_fee']);
*/
   /* // 红包
    if (!empty($order['bonus_id']))
    {
        $bonus          = bonus_info($order['bonus_id']);
        $total['bonus'] = $bonus['type_money'];
    }*/

    $total['bonus_formated'] = '';

    /* 配送费用 */
    $shipping_cod_fee = NULL;

    if ($order['shipping_id'] > 0 && $total['real_goods_count'] > 0)
    {
        $region['country']  = $consignee['country'];
        $region['province'] = $consignee['province'];
        $region['city']     = $consignee['city'];
        $region['district'] = $consignee['district'];
        $shipping_info = shipping_area_info($order['shipping_id'], $region);

        if (!empty($shipping_info))
        {
            $weight_price = cart_weight_price();

            $total['shipping_fee'] = shipping_fee($shipping_info['shipping_code'],
                $shipping_info['configure'], $weight_price['weight'], $total['goods_price']);

            if (!empty($order['need_insure']) && $shipping_info['insure'] > 0)
            {
                $total['shipping_insure'] = shipping_insure_fee($shipping_info['shipping_code'],
                    $total['goods_price'], $shipping_info['insure']);
            }
            else
            {
                $total['shipping_insure'] = 0;
            }

            if ($shipping_info['support_cod'])
            {
                $shipping_cod_fee = $shipping_info['pay_fee'];
            }
        }
    }

    $total['shipping_fee_formated']    = price_format($total['shipping_fee']);
    $total['shipping_insure_formated'] = price_format($total['shipping_insure']);

    // 红包和积分最多能支付的金额为商品总额
    $max_amount = $total['goods_price'];

    /* 计算订单总额 */
    if ($order['extension_code'] == 'group_buy' && $group_buy['deposit'] > 0)
    {
        $total['amount'] = $total['goods_price'];
    }
    else
    {
       /* $total['amount'] = $total['goods_price'] + $total['pack_fee']包装 + $total['card_fee']贺卡 +
            $total['shipping_fee'] + $total['shipping_insure'] + $total['cod_fee'] 货到付款手续费;*/

		 $total['amount'] = $total['goods_price'] + $total['shipping_fee'] + $total['shipping_insure'] + $total['cod_fee'];

        // 减去红包金额
      //  $use_bonus        = min($total['bonus'], $max_amount); // 实际减去的红包金额
      //  $total['bonus']   = $use_bonus;
      //  $total['bonus_formated'] = price_format($total['bonus']);
     //   $total['amount'] -= $use_bonus; // 还需要支付的订单金额
     //   $max_amount      -= $use_bonus; // 积分最多还能支付的金额
    }

  /*  // 余额
    $order['surplus'] = $order['surplus'] > 0 ? $order['surplus'] : 0;
    if ($total['amount'] > 0)
    {
        if (isset($order['surplus']) && $order['surplus'] > $total['amount'])
        {
            $order['surplus'] = $total['amount'];
            $total['amount']  = 0;
        }
        else
        {
            $total['amount'] -= floatval($order['surplus']);
        }
    }
    else
    {
        $order['surplus'] = 0;
        $total['amount']  = 0;
    }

    $total['surplus'] = $order['surplus'];
    $total['surplus_formated'] = price_format($order['surplus']);
	*/
   /* //
    $order['integral'] = $order['integral'] > 0 ? $order['integral'] : 0;
    if ($total['amount'] > 0 && $max_amount > 0 && $order['integral'] > 0)
    {
        $integral_money = value_of_integral($order['integral']);

        // 使用积分支付
        $use_integral            = min($total['amount'], $max_amount, $integral_money); // 实际使用积分支付的金额
        $total['amount']        -= $use_integral;
        $total['integral_money'] = $use_integral;
        $order['integral']       = integral_of_value($use_integral);
    }
    else
    {
        $total['integral_money'] = 0;
        $order['integral']       = 0;
    }*/
    $total['integral'] = $order['integral'];
    $total['integral_formated'] = price_format($total['integral_money']);

    /* 保存订单信息 */
    $_SESSION['flow_order'] = $order;

    /* 支付费用 */
    if (!empty($order['pay_id']))
    {
        $total['pay_fee']      = pay_fee($order['pay_id'], $total['amount'], $shipping_cod_fee);
    }

    $total['pay_fee_formated'] = price_format($total['pay_fee']);

    $total['amount']           += $total['pay_fee']; // 订单总额累加上支付费用
    $total['amount_formated']  = price_format($total['amount']);

    /*//取得可以得到的积分和红包
    if ($order['extension_code'] == 'group_buy')
    {
        $total['will_get_integral'] = $group_buy['gift_integral'];
    }
    else
    {
        $points = round(cart_amount() - $total['bonus'] - $total['integral_money']);
        $total['will_get_integral'] = $points <= 0 ? 0 : $points;
    }*/

	$total['will_get_integral'] 	= '';
    //$total['will_get_bonus']        = price_format(get_total_bonus());
	$total['will_get_bonus']        = '';
    $total['formated_goods_price']  = price_format($total['goods_price']);
    $total['formated_market_price'] = price_format($total['market_price']);
    $total['formated_saving']       = price_format($total['saving']);

    return $total;
}

/**
 * 修改订单
 * @param   int     $order_id   订单id
 * @param   array   $order      key => value
 * @return  bool
 */
function update_order($order_id, $order)
{
    return $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('order_info'),
        $order, 'UPDATE', "order_id = '$order_id'");
}

/**
 * 得到新订单号
 * @return  string
 */
function get_order_sn()
{
  $a = mt_rand();
  $b = $a << 2;
  $b = $b | 05646543;
  return substr(date('Ym',time()),-4).substr('000000000'.abs($b), -9);
}

/**
 * 得到批次新订单号 ljzhou 2012.10.18
 * @return  string
 */
function get_batch_order_sn()
{
  global $db;
  $day = date('Y-m-d',time()); 
  $batch_order_sn = ''; 
  $batch_order_sn = date('Ymd',time()); 
  $a = mt_rand();
  $b = substr('00'.abs($a), -2);
  $batch_order_sn.=$b;

  $sql = "select count(*) from ecshop.ecs_batch_order_info oi where oi.order_time >= curdate() limit 1";
  $num = $db->getOne($sql);
  $batch_order_sn = $batch_order_sn.'-'.($num+1);
  return $batch_order_sn;
}

/**
 * 通过某一个订单号重新找回它的原始订单
 * 
 * @param string $order_sn 订单号
 * 
 * @return array
 */
function retrieve_original_order($order_sn)
{
    global $db; 
    static $original_order_sn_stack = array();
    
    if (!isset($original_order_sn_stack[$order_sn])) {
        $original_order_sn = $db->getOne("SELECT root_order_sn FROM order_relation WHERE order_sn = '". $db->escape_string($order_sn) ."'", true);
        if ($original_order_sn) {
            $original_order_sn_stack[$order_sn] = $original_order_sn;    
        } else {
            $original_order_sn_stack[$order_sn] = $order_sn;    
        }
    }
    
    return order_info(0, $original_order_sn_stack[$order_sn]);
}

/* updated by Pengcheng 2007-12-18, add biaoju store info*/
/* $bj_store_id=-1 means all */
function cart_goods($type = CART_GENERAL_GOODS, $bj_store_id=-1)
{
    if ($bj_store_id > 0) return cart_biaoju_goods($type, $bj_store_id);
    $sql = "SELECT c.rec_id, c.user_id, c.goods_id, c.goods_name, c.goods_sn, c.goods_number, c.parent_id, " .
            "c.market_price, g.shop_price,g.market_price, c.goods_attr, c.is_real, c.parent_id, c.is_gift, " .
            "c.can_handsel, g.shop_price * c.goods_number as subtotal, g.integral , g.limit_integral " .
            "FROM " . $GLOBALS['ecs']->table('cart') ." as c ,  ". $GLOBALS['ecs']->table('goods').
			" as g  ".
            " WHERE g.goods_id = c.goods_id and c.session_id = '" . SESS_ID . "' " .
            " AND c.biaoju_store_goods_id=0 " .
            "AND c.rec_type = '$type' order by rec_id desc";

    $arr = $GLOBALS['db']->getAll($sql);

    $parents = array(); // 保存所有基本件
    foreach ($arr as $key => $value)
    {
    	if ($value['parent_id'] == 0) { //基本件
	        $arr[$key]['formated_goods_price']  = price_format($value['shop_price']);
   	    	$arr[$key]['formated_subtotal']     = price_format($value['subtotal']);
    	} else {
			$sql = 'SELECT goods_price FROM ' . $GLOBALS['ecs']->table('group_goods') . " WHERE goods_id = {$value['goods_id']} AND parent_id = {$value['parent_id']}";
			$price = $GLOBALS['db']->getRow($sql);
			if ($price) {
				/* 配件存在，按照套餐价格显示 */
		        $arr[$key]['shop_price']  = $price["goods_price"];
   		    	$arr[$key]['subtotal']     = $price["goods_price"] * $value["goods_number"];
		        $arr[$key]['formated_goods_price']  = price_format($arr[$key]['shop_price']);
   		    	$arr[$key]['formated_subtotal']     = price_format($arr[$key]['subtotal']);
   		    	if ($value["limit_integral"] >  $price["goods_price"]) $arr[$key]["limit_integral"] = intval($price["goods_price"]);
			} else {
				/* 配件不存在，按照普通显示 */
			    $arr[$key]['formated_goods_price']  = price_format($value['shop_price']);
   	    		$arr[$key]['formated_subtotal']     = price_format($value['subtotal']);
			}
    	}

    	$arr[$key]['formated_market_price'] = price_format($value['market_price']);
        $arr[$key]['biaoju_store_goods_id']     = 0;
        $arr[$key]['biaoju_store_id']     = 0;
        $arr[$key]['biaoju_store_name']     = "";
        $arr[$key]['biaoju_store_goods_id']     	= 0;


        if ($arr[$key]['integral'])
        {
        	$arr[$key]['return_point']		= getProductReturnPoint($arr[$key]['integral'], $_SESSION['rank_id']) * $value['goods_number'];
        }
        else
        {
        	$arr[$key]['return_point']		= 0;
        }
    	if ($value['parent_id'] == 0) $parents[] = $arr[$key];
    }

    /* 如果有配件，重新排序 */
    if (count($parents) != count($arr)) {
    	$result = array();
    	foreach ($parents as $key=>$value) {
    		$result[] = $value;
    		foreach ($arr as $k => $v) {
    			if ($v["parent_id"] == $value["rec_id"])  $result[] = $v;
    		}
    	}
    	$arr = $result;
    }

    if ($bj_store_id==-1)
    {
    	$arr = $arr + cart_biaoju_goods($type, $bj_store_id);
   	}
    return $arr;
}

/**
 * 取得购物车总金额
 * @params  boolean $include_gift
 * @param   int     $type   类型：默认普通商品
 * @return  float   购物车总金额
 */
function cart_amount($include_gift = true, $type = CART_GENERAL_GOODS)
{
    $sql = "SELECT SUM(goods_price * goods_number) " .
            " FROM " . $GLOBALS['ecs']->table('cart') .
            " WHERE session_id = '" . SESS_ID . "' " .
            "AND rec_type = '$type' ";

    if (!$include_gift)
    {
        $sql .= ' AND is_gift = 0';
    }

    return floatval($GLOBALS['db']->getOne($sql));
}

/**
 * 检查某商品是否已经存在于购物车
 *
 * @access  public
 * @param   integer     $id
 * @param   array       $spec
 * @param   int         $type   类型：默认普通商品
 * @return  boolean
 */
/* updated by Pengcheng 2007-12-17, add BiaoJu Store Id */
function cart_goods_exists($id, $spec, $type = CART_GENERAL_GOODS, $bjStoreGoodsId=0)
{
    /* 检查该商品是否已经存在在购物车中 */
    $sql = "SELECT COUNT(*) FROM " .$GLOBALS['ecs']->table('cart').
            "WHERE session_id = '" .SESS_ID. "' AND goods_id = '$id' ".
            "AND parent_id = 0 AND goods_attr = '" .get_goods_attr_info($spec). "' " .
            "AND rec_type = '$type'".
     		" AND biaoju_store_goods_id=".$bjStoreGoodsId;

    return ($GLOBALS['db']->getOne($sql) > 0);
}

/**
 * 获得购物车中商品的总重量和总价格
 *
 * @access  public
 * @param   int     $type   类型：默认普通商品
 * @return  array
 */
function cart_weight_price($type = CART_GENERAL_GOODS)
{
    /* 获得购物车中商品的总重量 */
    $sql    = 'SELECT SUM(g.goods_weight * c.goods_number) AS weight, ' .
                    'SUM(c.goods_price * c.goods_number) AS amount ' .
                'FROM ' . $GLOBALS['ecs']->table('cart') . ' AS c '.
                'LEFT JOIN ' . $GLOBALS['ecs']->table('goods') . ' AS g ON g.goods_id = c.goods_id '.
                "WHERE c.session_id = '" . SESS_ID . "' " .
                "AND rec_type = '$type'";
    $row = $GLOBALS['db']->getRow($sql);
    $row['weight'] = floatval($row['weight']);
    $row['amount'] = floatval($row['amount']);

    /* 格式化重量 */
    $row['formated_weight'] = formated_weight($row['weight']);

    return $row;
}

/**
 * 添加商品到购物车
 *
 * @access  public
 * @param   integer $goods_id   商品编号
 * @param   integer $num        商品数量
 * @param   array   $spec       规格
 * @param   array   $fittings   配件
 * @param   integer $style_id   样式
 * @return  boolean
 */
function addto_cart($goods_id, $num = 1, $spec = array(), $fittings = array(), $style_id = 0)
{
	global $spath;
    $GLOBALS['err']->clean();

    /* 取得商品信息 */
    $sql = "SELECT g.goods_name, g.goods_sn, g.is_on_sale, IFNULL(gs.sale_status, g.sale_status) AS sale_status, g.is_real, ".
                "g.market_price, g.shop_price AS org_price, g.promote_price, g.promote_start, ".
                "g.promote_end, g.goods_weight, g.can_handsel, g.integral, g.extension_code, ".
                "g.goods_number, g.is_alone_sale, g.is_gift, ".
                "g.shop_price ".
            " FROM " .$GLOBALS['ecs']->table('goods'). " AS g LEFT JOIN {$GLOBALS['ecs']->table('goods_style')} AS gs ON gs.style_id = '$style_id' AND g.goods_id = gs.goods_id ".
            " WHERE g.goods_id = '$goods_id'" .
            " AND g.is_delete = 0";
    $goods = $GLOBALS['db']->GetRow($sql);
    if (empty($goods))
    {
        $GLOBALS['err']->add($GLOBALS['_LANG']['goods_not_exists'], ERR_NOT_EXISTS);
        return false;
    }

    /* 是否正在销售 */
    if ($goods['is_on_sale'] == 0 || $goods['sale_status'] != 'normal')
    {
        $GLOBALS['err']->add($GLOBALS['_LANG']['not_on_sale'], ERR_NOT_ON_SALE);
        return false;
    }

    /* 检查是否允许单独销售 */
    if ($goods['is_alone_sale'] == 0)
    {
        $GLOBALS['err']->add($GLOBALS['_LANG']['cannt_alone_sale'], ERR_CANNT_ALONE_SALE);
        return false;
    }

    /* 检查库存 */
    if (($GLOBALS['_CFG']['use_storage'] == 1 && $num > $goods['goods_number']) || $goods['sale_status'] != 'normal')
    {
        $num = $goods['goods_number'];
        $GLOBALS['err']->add(sprintf($GLOBALS['_LANG']['shortage'], $num), ERR_OUT_OF_STOCK);
        return false;
    }
    
    /* 检查样式 */
    $sql = "SELECT s.style_id  FROM ".$GLOBALS['ecs']->table('goods_style')." as gs "
          ." INNER JOIN ".$GLOBALS['ecs']->table('style')." as s ON gs.style_id = s.style_id"
          ." WHERE gs.goods_id = '$goods_id' AND gs.sale_status = 'normal' ";
    if ($style_ids = $GLOBALS['db']->getCol($sql)) {
    	
      if (!in_array($style_id, $style_ids)) { //如果有样式的必须选择样式
    		return false;
    	}
    	
    	$sql = "SELECT gs.style_price, IF(gs.goods_color = '', s.color, gs.goods_color) AS color, s.value FROM ".$GLOBALS['ecs']->table('goods_style')." as gs "
            ." INNER JOIN ".$GLOBALS['ecs']->table('style')." as s ON gs.style_id = s.style_id"
            ." WHERE gs.goods_id = '$goods_id' AND s.style_id = '$style_id'";
      if($style_prices = $GLOBALS['db']->getRow($sql)) {
        if ($style_prices['style_price'] > 0) {
        	$goods['shop_price'] = $style_prices['style_price']; //修正商品价格
        }
        $goods['goods_name'] = $goods['goods_name'].' '.$style_prices['color'];
//        $goods['color'] = $style_prices['color'];
//        $goods['color_value'] = $style_prices['value'];
      } else {
      	return false;
      }
      
    }


    /* 检查配件 */
    $fittings = is_array($fittings) ? $fittings : array();
    /* 读取0元配件 */
    $free_fittings = get_goods_fittings($goods_id,true);
    if(!empty($free_fittings)){
      foreach ($free_fittings as $key => $freeFitting){
      	$is_exist = false;
      	foreach ($fittings as $key_f=> $fitting){
      		if($freeFitting['group_goods_id'] == $fitting[0]){
      			$is_exist = true;
      			break;
      		}
      	}
      	if(!$is_exist){
            $fittings[] = array(0 => $freeFitting['group_goods_id'], 1=>1);
      	}
      }
    }
    $fittingGoods = array();

    foreach ($fittings as $key => $fitting) {
	  $sql = "SELECT * FROM ".$GLOBALS['ecs']->table('group_goods') . " WHERE group_goods_id= " .intval($fitting[0]).
			" and parent_store_id=0 and parent_id=".$goods_id;
	  $row = $GLOBALS['db']->getRow($sql);
	  if (!$row) {
          $GLOBALS['err']->add($GLOBALS['_LANG']['goods_not_exists'], ERR_NOT_EXISTS);
          return false;
	  }
	  if ($row["child_store_id"] == 0) {
        $sql = "SELECT g.goods_id, g.goods_sn, g.goods_name, g.market_price," .
                    " g.is_real, g.extension_code, g.can_handsel, g.is_on_sale" .
                " FROM " . $GLOBALS['ecs']->table('goods') . " AS g" .
                " WHERE ".
                " g.goods_id=".$row["goods_id"] .
                " AND g.is_delete = 0";

        $subgoods = $GLOBALS['db']->getRow($sql);
        if (empty($subgoods)) {
            $GLOBALS['err']->add($GLOBALS['_LANG']['goods_not_exists'], ERR_NOT_EXISTS);
            return false;
        }
        if ($subgoods['is_on_sale'] == 0) {
          $GLOBALS['err']->add($GLOBALS['_LANG']['not_on_sale'], ERR_NOT_ON_SALE);
          return false;
        }
		$subgoods["goods_price"] = $row["goods_price"];
        $subgoods["goods_number"] = intval($fitting[1]);
		$subgoods["store_goods_id"] = 0;
        $fittingGoods[] = $subgoods;
	  } else {
        $sql = "SELECT g.goods_id, g.goods_sn, g.goods_name, g.market_price, " .
                    " g.is_real, g.extension_code, g.can_handsel, g.is_on_sale, bg.store_goods_id, bg.status " .
                " FROM bj_store_goods AS bg, " . $GLOBALS['ecs']->table('goods') . " AS g" .
                " WHERE bg.store_goods_id = " . $row["goods_id"] .
                " AND bg.goods_id = g.goods_id" .
                " AND g.is_delete = 0";

        $subgoods = $GLOBALS['db']->getRow($sql);
        if (empty($subgoods)) {
            $GLOBALS['err']->add($GLOBALS['_LANG']['goods_not_exists'], ERR_NOT_EXISTS);
            return false;
        }
        if ($subgoods['status'] != 'ON_SALE') {
          $GLOBALS['err']->add($GLOBALS['_LANG']['not_on_sale'], ERR_NOT_ON_SALE);
          return false;
        }
		$subgoods["goods_price"] = $row["goods_price"];
        $subgoods["goods_number"] = intval($fitting[1]);
        $fittingGoods[] = $subgoods;
	  }
    }

    $parent_id = 0;
    /* 初始化要插入购物车的基本件数据 */
    $parent = array(
        'user_id'       => $_SESSION['user_id'],
        'session_id'    => SESS_ID,
        'goods_id'      => $goods_id,
        'goods_sn'      => addslashes($goods['goods_sn']),
        'goods_name'    => addslashes($goods['goods_name']),
        'market_price'  => $goods['market_price'],
        'goods_attr'    => '',
        'is_real'       => $goods['is_real'],
        'extension_code'=> $goods['extension_code'],
        'is_gift'       => 0,
        'goods_price'   => $goods['shop_price'],
        'goods_number'   => $num,
        'parent_id'   => 0,
        'can_handsel'   => $goods['can_handsel'],
        'rec_type'      => CART_GENERAL_GOODS,
        'biaoju_store_goods_id' => 0,
        'style_id' => $style_id
    );

	/* 如果已经存在，那么更新数量 */
    $sql = "SELECT rec_id FROM " . $GLOBALS['ecs']->table('cart') .
                " WHERE goods_id = " . intval($parent["goods_id"]) .
    			" AND session_id = '".SESS_ID."'".
                " AND biaoju_store_goods_id = 0".
                " AND style_id = '". intval($parent['style_id']) . "'".
                " AND parent_id = 0";

    $cartgoods = $GLOBALS['db']->getRow($sql);
    if (empty($cartgoods)) {
   		/* 插入基本件 */
    	$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart'), $parent, 'INSERT');
    	$parent_id = $GLOBALS['db']->insert_id();
    } else {
    	$parent_id = $cartgoods["rec_id"];
	    /* 更新基本件 */
	    // 如果有套餐的情况下基本件数字规定为1 by ychen 2008/06/04
	    if (count($fittingGoods) == 0) {
	    	$sql = "
	    		UPDATE {$GLOBALS['ecs']->table('cart')}
	    		SET  goods_number = goods_number + {$parent['goods_number']}
	            WHERE rec_id = {$cartgoods['rec_id']}
	        ";
	    } else {
	    	$sql = "
	    		UPDATE {$GLOBALS['ecs']->table('cart')}
	    		SET  goods_number = {$parent['goods_number']}
	            WHERE rec_id = {$cartgoods['rec_id']}
	        ";	    	
	    }
	    $GLOBALS['db']->query($sql);
    }

    /* 初始化要插入购物车的基本件数据 */
    $cart = array(
        'user_id'       => $_SESSION['user_id'],
        'session_id'    => SESS_ID,
        'goods_attr'    => '',
        'is_gift'       => 0,
        'rec_type'      => CART_GENERAL_GOODS,
        'parent_id'		=> $parent_id
    );

    /* 插入配件 */
    foreach ($fittingGoods as $key => $fitting) {
        $cart['goods_number'] = $fitting["goods_number"];
        $cart['goods_id'] = $fitting["goods_id"];
        $cart['goods_sn'] = $fitting["goods_sn"];
        $cart['goods_name'] = $fitting["goods_name"];
        $cart['market_price'] = $fitting["market_price"];
        $cart['goods_price'] = $fitting["goods_price"];
        $cart['is_real'] = $fitting["is_real"];
        $cart['extension_code'] = $fitting["extension_code"];
        $cart['can_handsel'] = $fitting["can_handsel"];
		$cart['biaoju_store_goods_id'] = $fitting["store_goods_id"];

        /* 如果已经存在，那么更新数量 */
	    $sql = "SELECT rec_id FROM " . $GLOBALS['ecs']->table('cart') .
	                " WHERE goods_id = " . intval($fitting["goods_id"]) .
    				" AND session_id = '".SESS_ID."'".
	                " AND biaoju_store_goods_id = " . $cart['biaoju_store_goods_id'].
	                " AND parent_id = " . $cart['parent_id'];

	    $cartgoods = $GLOBALS['db']->getRow($sql);
	    if (empty($cartgoods)) {
        	$GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('cart'), $cart, 'INSERT');
	    } else {
	      if ($fitting["goods_price"] != 0) { //价格为0的套餐不更新 by zwsun
	      	$sql = "UPDATE " . $GLOBALS['ecs']->table('cart') .
	                " SET  goods_number = goods_number + ". $cart['goods_number'] .
	                " WHERE rec_id = " . $cartgoods['rec_id'];
		      $GLOBALS['db']->query($sql);
	      }
	    }
    }

    // {{{ 本来返回true，现在改为返回$goods_name，为了得到添加的商品信息 by ychen 08/02/15
	return $goods['goods_name'];
    // }}}
}

/**
 * 清空购物车
 * @param   int     $type   类型：默认普通商品
 */
function clear_cart($type = CART_GENERAL_GOODS, $store_id=-1)
{
	if ($store_id > 0) {
		clear_biaoju_cart($type, $store_id);
		return;
	}
    $sql = "DELETE FROM " . $GLOBALS['ecs']->table('cart') .
            " WHERE session_id = '" . SESS_ID . "' AND biaoju_store_goods_id=0 AND rec_type = '$type'";
    $GLOBALS['db']->query($sql);
}

/**
 * 获得指定的商品属性
 *
 * @access  public
 * @param   array   $arr
 * @return  string
 */
function get_goods_attr_info($arr)
{
    $attr   = '';

    if (!empty($arr))
    {
        $fmt = "%s:%s[%s] \n";

        $sql = "SELECT a.attr_name, ga.attr_value, ga.attr_price ".
                "FROM ".$GLOBALS['ecs']->table('goods_attr')." AS ga, ".
                    $GLOBALS['ecs']->table('attribute')." AS a ".
                "WHERE " .db_create_in($arr, 'ga.goods_attr_id')." AND a.attr_id = ga.attr_id";
        $res = $GLOBALS['db']->query($sql);

        while ($row = $GLOBALS['db']->fetchRow($res))
        {
            $attr_price = round(floatval($row['attr_price']), 2);
            $attr .= sprintf($fmt, $row['attr_name'], $row['attr_value'], $attr_price);
        }

        $attr = str_replace('[0]', '', $attr);
    }

    return $attr;
}

/**
 * 取得用户信息
 * @param   int     $user_id    用户id
 * @return  array   用户信息
 */
function user_info($user_id)
{
    $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('users') .
            " WHERE user_id = '$user_id'";
    $user = $GLOBALS['db']->getRow($sql);

    unset($user['question']);
    unset($user['answer']);

    /* 格式化帐户余额 */
    if ($user)
    {
//        if ($user['user_money'] < 0)
//        {
//            $user['user_money'] = 0;
//        }
        $user['formated_user_money'] = price_format($user['user_money']);
    }

    return $user;
}

/**
 * 修改用户
 * @param   int     $user_id   订单id
 * @param   array   $user      key => value
 * @return  bool
 */
function update_user($user_id, $user)
{
    return $GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('users'),
        $user, 'UPDATE', "user_id = '$user_id'");
}

/**
 * 取得用户地址列表
 * @param   int     $user_id    用户id
 * @return  array
 */
function address_list($user_id)
{
    $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('user_address') .
            " WHERE user_id = '$user_id'";

    return $GLOBALS['db']->getAll($sql);
}

/**
 * 取得用户地址信息
 * @param   int     $address_id     地址id
 * @return  array
 */
function address_info($address_id)
{
    $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('user_address') .
            " WHERE address_id = '$address_id'";

    return $GLOBALS['db']->getRow($sql);
}

/**
 * 取得用户当前可用红包
 * @param   int     $user_id    用户id
 * @return  array   红包数组
 * 
 * TODO 废弃的函数
 */
function user_bonus($user_id)
{
    $today = date('Y-m-d');
    $sql = "SELECT t.type_id, t.type_name, t.type_money, b.bonus_id " .
            "FROM " . $GLOBALS['ecs']->table('bonus_type') . " AS t," .
                $GLOBALS['ecs']->table('user_bonus') . " AS b " .
            "WHERE t.type_id = b.bonus_type_id " .
            "AND t.use_startdate <= '$today' " .
            "AND t.use_enddate >= '$today' " .
            "AND b.user_id = '$user_id' " .
            "AND b.order_id = 0";

    return $GLOBALS['db']->getAll($sql);
}

/**
 * 取得红包信息
 * @param   int     $bonus_id   红包id
 * @param   array   红包信息
 * 
 * TODO 废弃的函数
 */
function bonus_info($bonus_id)
{
    $sql = "SELECT t.*, b.* " .
            "FROM " . $GLOBALS['ecs']->table('bonus_type') . " AS t," .
                $GLOBALS['ecs']->table('user_bonus') . " AS b " .
            "WHERE t.type_id = b.bonus_type_id " .
            "AND b.bonus_id = '$bonus_id'";

    return $GLOBALS['db']->getRow($sql);
}

/**
 * 检查红包是否已使用
 * @param   int $bonus_id   红包id
 * @return  bool
 * 
 * TODO 废弃的函数
 */
function bonus_used($bonus_id)
{
    $sql = "SELECT order_id FROM " . $GLOBALS['ecs']->table('user_bonus') .
            " WHERE bonus_id = '$bonus_id'";

    return  $GLOBALS['db']->getOne($sql) > 0;
}

/**
 * 设置红包为已使用
 * @param   int     $bonus_id   红包id
 * @param   int     $order_id   订单id
 * @return  bool
 * 
 * TODO 废弃的函数
 */
function use_bonus($bonus_id, $order_id)
{
    $sql = "UPDATE " . $GLOBALS['ecs']->table('user_bonus') .
            " SET order_id = '$order_id', used_time = '" . time() . "' " .
            "WHERE bonus_id = '$bonus_id' LIMIT 1";

    return  $GLOBALS['db']->query($sql);
}

/**
 * 设置红包为未使用
 * @param   int     $bonus_id   红包id
 * @param   int     $order_id   订单id
 * @return  bool
 * 
 * TODO 废弃的函数
 */
function unuse_bonus($bonus_id)
{
    $sql = "UPDATE " . $GLOBALS['ecs']->table('user_bonus') .
            " SET order_id = 0, used_time = 0 " .
            "WHERE bonus_id = '$bonus_id' LIMIT 1";

    return  $GLOBALS['db']->query($sql);
}

/**
 * 计算欧币的价值（能抵多少钱）
 * @param   int     $integral   积分
 * @return  float   欧币价值
 */
function value_of_integral($integral)
{
    $scale = floatval($GLOBALS['_CFG']['integral_scale']);

    return $scale > 0 ? round(($integral / 100) * $scale, 2) : 0;
}

/**
 * 计算指定的金额需要多少积分
 *
 * @access  public
 * @param   integer $value  金额
 * @return  void
 */
function integral_of_value($value)
{
    $scale = floatval($GLOBALS['_CFG']['integral_scale']);

    return $scale > 0 ? round($value / $scale * 100) : 0;
}

/**
 * 获得购物车中商品数目
 * @return int
 */
function get_cart_goods_num() {
  include_once('lib_biaoju.php');  
  $cart_goods = show_cart_goods();
  $bj_goods = show_biaoju_cart_goods();
  $goods_list = $cart_goods['goods_list'];
  $total = 0;
  foreach ($goods_list as $goods) {
    $total += $goods['goods_number'];
  }
  foreach($bj_goods['store_list'] as $store_goods) {
  	$goods_list = $store_goods['goodsList'];
	  foreach ($goods_list as $goods) {
	    $total += $goods['goods_number'];
	  }
  }
  return $total;
//    $sql = "SELECT SUM(c.goods_number)" .
//          "from  ". $GLOBALS['ecs']->table('cart') ." as c WHERE session_id = '" . SESS_ID . "'" ;
//    return $GLOBALS['db']->getOne($sql);

}
//FIXME: remove 欧币 related code
function show_cart_goods(){
	 /* 初始化 */
    $totalPrice = 0;
    $limitIntegral = 0;
    $saveFittingPrice = 0;

     // 循环、统计
    $sql = "SELECT c.goods_id ,c.rec_id, c.goods_number ,g.shop_price, c.parent_id, c.style_id, g.goods_name, g.goods_thumb, g.top_cat_id, g.cat_id, g.shop_price * c.goods_number as subtotal, g.integral, g.limit_integral, g.sale_status" .
          ", g.addtional_shipping_fee, g.is_vip, g.vip_price, IFNULL(gs.goods_number, g.goods_number) AS remain_goods_number, IFNULL(gs.is_remains, g.is_remains) AS is_remains FROM  ". $GLOBALS['ecs']->table('cart') ." AS c INNER JOIN ". $GLOBALS['ecs']->table('goods').
			" AS g ON g.goods_id = c.goods_id LEFT JOIN ".$GLOBALS['ecs']->table('goods_style')." gs ON c.goods_id = gs.goods_id AND c.style_id  = gs.style_id WHERE c.session_id = '" . SESS_ID . "' AND c.biaoju_store_goods_id = 0 ORDER BY c.rec_id DESC" ;
    $arr = $GLOBALS['db']->getAll($sql);

    $limit_bonus = 0;
    $totalAddtionalFee = 0;
	$hasFitting = False;

    foreach ($arr as $key => $value)
    {
       	$totalAddtionalFee = $totalAddtionalFee + $value['addtional_shipping_fee'] * $value['goods_number'];
       	// add different prices by zwsun 2008/6/12
       	if($value['style_id'] > 0) {     	  
       	  $sql = "SELECT gs.style_price, IF(gs.goods_color = '', s.color, gs.goods_color) AS color, s.value, gs.sale_status FROM ".$GLOBALS['ecs']->table('goods_style')." as gs "
                ." INNER JOIN ".$GLOBALS['ecs']->table('style')." as s ON gs.style_id = s.style_id"
                ." WHERE gs.goods_id = '{$value['goods_id']}' AND s.style_id = '{$value['style_id']}'";
          if($style_prices = $GLOBALS['db']->getRow($sql)) {
            if ($style_prices['style_price'] > 0) {
            	$arr[$key]['shop_price'] = $style_prices['style_price']; //修正商品价格
            	$value['shop_price'] = $style_prices['style_price']; //修正商品价格
            	$arr[$key]['subtotal'] = $value['shop_price'] * $value['goods_number']; //修正小计
            	$value['subtotal'] = $value['shop_price'] * $value['goods_number']; //修正小计
            }
            $arr[$key]['goods_name'] = $value['goods_name'].' '.$style_prices['color'];
            $arr[$key]['sale_status'] = $style_prices['sale_status'];
            $value['goods_name'] = $value['goods_name'].' '.$style_prices['color'];            
          } else { //如果该颜色下架了，那么去掉
          	unset($arr[$key]);
          	continue;
          }
       	}

		$arr[$key]['shop_price_formatted']  = price_format($value['shop_price']);
   	    $arr[$key]['subtotal_formatted']     = price_format($value['subtotal']);
    	if ($value['parent_id'] > 0) { //配件
	    	$parent =  $GLOBALS['db']->getRow("select * from ". $GLOBALS['ecs']->table('cart') ." where rec_id=".$value['parent_id'] );
	    	if ($parent) {
		    	if ($parent["biaoju_store_goods_id"] == 0) {
		    		$sql = 'SELECT goods_price FROM ' . $GLOBALS['ecs']->table('group_goods') . " WHERE goods_id = {$value["goods_id"]} and child_store_id=0 AND parent_id = {$parent["goods_id"]} AND parent_store_id=0";
		    	} else {
		    		$sql = 'SELECT goods_price FROM ' . $GLOBALS['ecs']->table('group_goods') . " WHERE goods_id = {$value["goods_id"]} and child_store_id=0 AND parent_id = {$parent["biaoju_store_goods_id"]} AND parent_store_id > 0";
		    	}

				$price = $GLOBALS['db']->getRow($sql);

				if ($price) {
					/* 配件存在，按照套餐价格显示 */
			        $arr[$key]['shop_price']  = $price["goods_price"];
			        $temp_subtotal = $arr[$key]['subtotal'];
	   		    	$arr[$key]['subtotal']     = $price["goods_price"] * $value["goods_number"];
	   		    	$saveFittingPrice += ($temp_subtotal - $arr[$key]['subtotal']);
			        $arr[$key]['shop_price_formatted']  = price_format($arr[$key]['shop_price']);
	   		    	$arr[$key]['subtotal_formatted']     = price_format($arr[$key]['subtotal']);
	   		    	if ($value["limit_integral"] >  $price["goods_price"]) $arr[$key]["limit_integral"] = intval($price["goods_price"]);
				}
	    	}
	    	$hasFitting = True;
    	} else if ($value["is_vip"] > 0 && $value["vip_price"] > 0 && $value["vip_price"] <  $value["shop_price"] && isset($_SESSION["rank_id"])){
    		$rank_id = intval($_SESSION["rank_id"]);
    		if ($rank_id >= $value["is_vip"]) {
		        $arr[$key]['shop_price']  = $value["vip_price"];
   		    	$arr[$key]['subtotal']     = $value["vip_price"] * $value["goods_number"];
		        $arr[$key]['shop_price_formatted']  = price_format($arr[$key]['shop_price']);
   		    	$arr[$key]['subtotal_formatted']     = price_format($arr[$key]['subtotal']);
    		}
    	}
        if ($arr[$key]['integral'])
        {
        	$arr[$key]['all_return_point']	= getProductReturnNameAndPoint($arr[$key]['integral'], $arr[$key]['goods_number']);
        	if ($_SESSION['rank_id'] !== null)
        	{
        		$arr[$key]['return_point']	= getProductReturnPoint($arr[$key]['integral'], $_SESSION['rank_id']) * $arr[$key]['goods_number'];
        	}
        }
        else
        {
        	$arr[$key]['return_point']	= 0;
        }


        /* 如果可以用欧币，那么可以用红包*/
        if ($value["limit_integral"] > 0) {
        	$limit_bonus += $arr[$key]["subtotal"];
        }

        $totalPrice	+=	$arr[$key]['subtotal'];
        $limitIntegral += $arr[$key]['limit_integral'] * $arr[$key]['goods_number'];


    	if ($value['parent_id'] == 0) $parents[] = $arr[$key];
    }

    /* 如果有配件，重新排序 */
    if ($hasFitting) {
    	$arr =  sort_array_tree($arr, "rec_id", "parent_id");
    }
  $saveFittingPrice = ($saveFittingPrice>0) ? $saveFittingPrice : 0;
	$arCartList['goods_list']	=	$arr;
	$arCartList['total']	=	$totalPrice;
	$arCartList['total_formatted']	=	price_format($totalPrice);
	$arCartList['save_fitting_price']	=	$saveFittingPrice;
	$arCartList['save_fitting_price_formatted']	=	price_format($saveFittingPrice);
	$arCartList['limit_integral']	=	$limitIntegral;
	$arCartList['limit_bonus']	=	$limit_bonus;
	$arCartList['total_addtional_shipping_fee']	=	$totalAddtionalFee;
    return $arCartList;

}

/**
 * 获得购物车中的商品
 *
 * @access  public
 * @return  array
 */
/*
function get_cart_goods()
{

    $goods_list = array();
    $total = array(
        'goods_price'  => 0, // 本店售价合计（有格式）
        'market_price' => 0, // 市场售价合计（有格式）
        'gift_amount'  => 0, // 参与送赠品的商品售价合计（无格式）
        'saving'       => 0, // 节省金额（有格式）
        'save_rate'    => 0, // 节省百分比
        'goods_amount' => 0, // 本店售价合计（无格式）
    );


    $sql = "SELECT *, IF(parent_id, parent_id, goods_id) AS pid " .
            " FROM " . $GLOBALS['ecs']->table('cart') . " " .
            " WHERE session_id = '" . SESS_ID . "' AND rec_type = '" . CART_GENERAL_GOODS . "'" .
            " ORDER BY pid, parent_id";
    $res = $GLOBALS['db']->query($sql);


    $virtual_goods_count = 0;
    $real_goods_count    = 0;

    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $total['goods_price']  += $row['goods_price'] * $row['goods_number'];
        $total['market_price'] += $row['market_price'] * $row['goods_number'];
        if ($row['is_gift'] == '0' && $row['can_handsel'] == '1')
        {
            $total['gift_amount'] += $row['goods_price'] * $row['goods_number'];
        }

        $row['subtotal']     = price_format($row['goods_price'] * $row['goods_number']);
        $row['goods_price']  = price_format($row['goods_price']);
        $row['market_price'] = price_format($row['market_price']);


        if ($row['is_real'])
        {
            $real_goods_count++;
        }
        else
        {
            $virtual_goods_count++;
        }


        if (trim($row['goods_attr']) != '')
        {
            $sql = "SELECT attr_value FROM " . $GLOBALS['ecs']->table('goods_attr') . " WHERE goods_attr_id " .
            db_create_in($row['goods_attr']);
            $attr_list = $GLOBALS['db']->getCol($sql);
            foreach ($attr_list AS $attr)
            {
                $row['goods_name'] .= ' [' . $attr . '] ';
            }
        }

        $goods_list[] = $row;
    }
    $total['goods_amount'] = $total['goods_price'];
    $total['saving']       = price_format($total['market_price'] - $total['goods_price']);
    if ($total['market_price'] > 0)
    {
        $total['save_rate'] = $total['market_price'] ? round(($total['market_price'] - $total['goods_price']) *
        100 / $total['market_price']).'%' : 0;
    }
    $total['goods_price']  = price_format($total['goods_price']);
    $total['market_price'] = price_format($total['market_price']);
    $total['real_goods_count']    = $real_goods_count;
    $total['virtual_goods_count'] = $virtual_goods_count;

    return array('goods_list' => $goods_list, 'total' => $total);
}
*/
/**
 * 取得购物车的可选赠品
 *
 * @access  public
 * @param   float   $gift_amount    // 参与送赠品的商品总金额
 * @return  array
 */
function get_cart_gift($gift_amount)
{
    /* 取得购物车中已有的赠品id */
    $sql = "SELECT is_gift, goods_id " .
            "FROM " . $GLOBALS['ecs']->table('cart') .
            " WHERE session_id = '" . SESS_ID . "' AND is_gift <> 0 " .
            "AND rec_type = '" . CART_GENERAL_GOODS . "'";
    $res = $GLOBALS['db']->query($sql);
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $selected_id_list[$row['is_gift']][] = $row['goods_id'];
    }

    /* 取得所有有效的赠品类型及购物车中该类型赠品的数量 */
    $today = date('Y-m-d');
    $gift_type_list = array();
    $sql = "SELECT t.*, COUNT(g.goods_id) AS gift_count " .
            "FROM " . $GLOBALS['ecs']->table('gift_type') . " AS t, " .
                $GLOBALS['ecs']->table('gift') . " AS g " .
            " WHERE t.gift_type_id = g.gift_type_id " .
            " AND t.start_date <= '$today' AND t.end_date >= '$today' " .
            " GROUP BY t.gift_type_id ";
    $res = $GLOBALS['db']->query($sql);
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        /* 暂时不考虑按商品发放 */
        if ($row['send_type'] == GIFT_BY_GOODS)
        {
            continue;
        }
        $arr = unserialize($row['param']);
        $row = array_merge($row, $arr);
        $row['enjoyable'] = $gift_amount >= $row['min_amount'] && ($gift_amount <= $row['max_amount'] || $row['max_amount'] == 0);

        /* 语言描述信息 */
        $row['selected_count'] = isset($selected_id_list[$row['gift_type_id']]) ? count($selected_id_list[$row['gift_type_id']]) : 0;
        //您可以从下面的赠品中任选 max_count 个，目前您已选择了 selected_count 个
        $row['may_select_desc'] = sprintf($GLOBALS['_LANG']['may_gift_select'], $row['max_count'], $row['selected_count']);

        //当购物车中参加送赠品活动的商品的总金额介于 min_amount 和 max_amount 之间，可选多少max_count个
        $row['cart_money_amongst'] = sprintf($GLOBALS['_LANG']['cart_money_amongst'], $row['min_amount'], $row['max_amount'], $row['max_count']);

        //当购物车中参加送赠品活动的商品的总金额超过min_amount，可以从赠品中任选max_count个。
        $row['cart_money_exceed'] = sprintf($GLOBALS['_LANG']['cart_money_exceed'], $row['min_amount'], $row['max_count']);

        //您可以从下面的赠品中任选 max_count 个
        $row['may_select_amount'] = sprintf($GLOBALS['_LANG']['may_select_amount'], $row['max_count']);

        $gift_type_list[] = $row;
    }

    /* 取得所有赠品，按赠品类型分组，标出是否已选 */
    $gift_list = array();
    $sql = "SELECT gi.*, go.goods_name " .
            "FROM " . $GLOBALS['ecs']->table('gift') . " AS gi, " . $GLOBALS['ecs']->table('goods') . " AS go " .
            "WHERE gi.goods_id = go.goods_id " .
            "AND go.is_on_sale = 1 " .
            "AND go.is_delete = 0 " .
            "ORDER BY gi.gift_type_id";
    $res = $GLOBALS['db']->query($sql);
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $id = $row['gift_type_id'];
        $row['goods_price'] = price_format($row['goods_price']);
        $row['selected'] = isset($selected_id_list[$row['gift_type_id']]) ? in_array($row['goods_id'], $selected_id_list[$row['gift_type_id']]) : false;
        $gift_list[$id][] = $row;
    }

    return array(
        'gift_type_list' => $gift_type_list,
        'gift_list'      => $gift_list,
    );
}


/**
 * 取得收货人信息
 * @param   int     $user_id    用户编号
 * @return  array
 */
function get_consignee($user_id)
{
    if (isset($_SESSION['flow_consignee']))
    {
        /* 如果存在session，则直接返回session中的收货人信息 */

        return $_SESSION['flow_consignee'];
    }
    else
    {
        /* 如果不存在，则取得用户的默认收货人信息 */
        $arr = array();

        if ($user_id > 0)
        {
            /* 取默认地址 */
            $sql = "SELECT ua.*".
                    " FROM " . $GLOBALS['ecs']->table('user_address') . "AS ua, ".$GLOBALS['ecs']->table('users').' AS u '.
                    " WHERE u.user_id='$user_id' AND ua.address_id = u.address_id";
            $arr = $GLOBALS['db']->getRow($sql);
        }

        return $arr;
    }
}

/**
 * 查询购物车（订单id为0）或订单中是否有实体商品
 * @param   int     $order_id   订单id
 * @param   int     $flow_type  购物流程类型
 * @return  bool
 */
function exist_real_goods($order_id = 0, $flow_type = CART_GENERAL_GOODS)
{
    if ($order_id <= 0)
    {
        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('cart') .
                " WHERE session_id = '" . SESS_ID . "' AND is_real = 1 " .
                "AND rec_type = '$flow_type'";
    }
    else
    {
        $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('order_goods') .
                " WHERE order_id = '$order_id' AND is_real = 1";
    }

    return $GLOBALS['db']->getOne($sql) > 0;
}

/**
 * 检查收货人信息是否完整
 * @param   array   $consignee  收货人信息
 * @param   int     $flow_type  购物流程类型
 * @return  bool    true 完整 false 不完整
 */
function check_consignee_info($consignee, $flow_type)
{
    if (exist_real_goods(0, $flow_type))
    {
        /* 如果存在实体商品 */
        $res = !empty($consignee['consignee']) &&
            !empty($consignee['country']) &&
            !empty($consignee['email']) &&
            (!empty($consignee['tel']) || !empty($consignee['mobile']));

        if ($res)
        {
            if (empty($consignee['province']))
            {
                /* 没有设置省份，检查当前国家下面有没有设置省份 */
                $pro = get_regions(1, $consignee['country']);
                $res = empty($pro);
            }
            elseif (empty($consignee['city']))
            {
                /* 没有设置城市，检查当前省下面有没有城市 */
                $city = get_regions(2, $consignee['province']);
                $res = empty($city);
            }
            elseif (empty($consignee['district']))
            {
                $dist = get_regions(3, $consignee['city']);
                $res = empty($dist);
            }
        }

        return $res;
    }
    else
    {
        /* 如果不存在实体商品 */
        return !empty($consignee['consignee']) &&
            !empty($consignee['email']) &&
            (!empty($consignee['tel']) || !empty($consignee['mobile']));
    }
}

/**
 * 获得上一次用户采用的支付和配送方式
 *
 * @access  public
 * @return  void
 */
function last_shipping_and_payment()
{
    $sql = "SELECT shipping_id, pay_id " .
            " FROM " . $GLOBALS['ecs']->table('order_info') .
            " WHERE user_id = '$_SESSION[user_id]' " .
            " ORDER BY order_id DESC LIMIT 1";
    $row = $GLOBALS['db']->getRow($sql);

    if (empty($row))
    {
        /* 如果获得是一个空数组，则返回默认值 */
        $row = array('shipping_id' => 0, 'pay_id' => 0);
    }

    return $row;
}

/**
 * 取得当前用户应该得到的红包总额
 */
function get_total_bonus()
{
    $today = date('Y-m-d');

    /* 按商品发的红包 */
    $sql = "SELECT SUM(c.goods_number * t.type_money)" .
            "FROM " . $GLOBALS['ecs']->table('cart') . " AS c, "
                    . $GLOBALS['ecs']->table('bonus_type') . " AS t, "
                    . $GLOBALS['ecs']->table('goods') . " AS g " .
            "WHERE c.session_id = '" . SESS_ID . "' " .
            "AND c.is_gift = 0 " .
            "AND c.goods_id = g.goods_id " .
            "AND g.bonus_type_id = t.type_id " .
            "AND t.send_type = '" . SEND_BY_GOODS . "' " .
            "AND t.send_startdate <= '$today' " .
            "AND t.send_enddate >= '$today' " .
            "AND c.rec_type = '" . CART_GENERAL_GOODS . "'";
    $goods_total = floatval($GLOBALS['db']->getOne($sql));

    /* 取得购物车中非赠品总金额 */
    $sql = "SELECT SUM(goods_price * goods_number) " .
            "FROM " . $GLOBALS['ecs']->table('cart') .
            " WHERE session_id = '" . SESS_ID . "' " .
            " AND is_gift = 0 " .
            " AND rec_type = '" . CART_GENERAL_GOODS . "'";
    $amount = floatval($GLOBALS['db']->getOne($sql));

    /* 按订单发的红包 */
    $sql = "SELECT ROUND('$amount' / min_amount) * type_money " .
            "FROM " . $GLOBALS['ecs']->table('bonus_type') .
            " WHERE send_type = '" . SEND_BY_ORDER . "' " .
            " AND send_startdate <= '$today' " .
            "AND send_enddate >= '$today' " .
            "AND min_amount > 0 ";
    $order_total = floatval($GLOBALS['db']->getOne($sql));

    return $goods_total + $order_total;
}
/**
 * 处理余额（下订单时减少，取消（无效，退货）订单时增加）
 * @param   int     $user_id    用户编号
 * @param   float   $surplus    增减的余额：正数为增，负数为减
 * 
 * TODO 废弃的函数
 */
function change_user_surplus($user_id, $surplus, $order_sn='')
{
    $admin_user = isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : '';

    $type = $surplus < 0 ? SURPLUS_PURCHASE : SURPLUS_CANCELORDER;
    $sql = "INSERT INTO " . $GLOBALS['ecs']->table('user_account') . " ( ".
            "user_id, admin_user, amount, add_time, paid_time, admin_note, process_type, is_paid".
            ") VALUES (".
            "'$user_id', '$admin_user', '$surplus', ". time() .", ". time() .", '" .$order_sn. "', '$type', 1)";
    $GLOBALS['db']->query($sql);

    $sql = 'UPDATE ' . $GLOBALS['ecs']->table('users') . ' SET ' .
            "user_money = user_money + ('$surplus') " .
            "WHERE user_id = '$user_id'";
    $GLOBALS['db']->query($sql);
}

/**
 * 处理消费积分（下订单时减少，取消（无效，退货）订单时增加）
 * @param   int     $user_id    用户编号
 * @param   int     $integral   增减的积分
 */
function change_user_integral($user_id, $integral)
{
    $sql = 'UPDATE ' . $GLOBALS['ecs']->table('users') . ' SET ' .
            "pay_points = pay_points + ('$integral') " .
            "WHERE user_id = '$user_id'";
    $GLOBALS['db']->query($sql);
}

/**
 * 处理红包（下订单时设为使用，取消（无效，退货）订单时设为未使用
 * @param   int     $bonus_id   红包编号
 * @param   int     $order_id   订单号
 * @param   int     $is_used    是否使用了
 * 
 * TODO 废弃的函数
 */
function change_user_bonus($bonus_id, $order_id, $is_used = true)
{
    if ($is_used)
    {
        $sql = 'UPDATE ' . $GLOBALS['ecs']->table('user_bonus') . ' SET ' .
                "used_time = ' . time() . ', " .
                "order_id = '$order_id' " .
                "WHERE bonus_id = '$bonus_id'";
    }
    else
    {
        $sql = 'UPDATE ' . $GLOBALS['ecs']->table('user_bonus') . ' SET ' .
                'used_time = 0, ' .
                'order_id = 0 ' .
                "WHERE bonus_id = '$bonus_id'";
    }
    $GLOBALS['db']->query($sql);
}

/**
 * 获得订单信息
 *
 * @access  private
 * @return  array
 * 
 * TODO 废弃的函数
 */
function flow_order_info()
{
    $order = isset($_SESSION['flow_order']) ? $_SESSION['flow_order'] : array();

    /* 初始化配送和支付方式 */
    if (!isset($order['shipping_id']) || !isset($order['pay_id']))
    {
        /* 如果还没有设置配送和支付 */
        if ($_SESSION['user_id'] > 0)
        {
            /* 用户已经登录了，则获得上次使用的配送和支付 */
            $arr = last_shipping_and_payment();

            if (!isset($order['shipping_id']))
            {
                $order['shipping_id'] = $arr['shipping_id'];
            }
            if (!isset($order['pay_id']))
            {
                $order['pay_id'] = $arr['pay_id'];
            }
        }
        else
        {
            if (!isset($order['shipping_id']))
            {
                $order['shipping_id'] = 0;
            }
            if (!isset($order['pay_id']))
            {
                $order['pay_id'] = 0;
            }
        }
    }

    if (!isset($order['pack_id']))
    {
        $order['pack_id'] = 0;  // 初始化包装
    }
    if (!isset($order['card_id']))
    {
        $order['card_id'] = 0;  // 初始化贺卡
    }
    if (!isset($order['bonus']))
    {
        $order['bonus'] = 0;    // 初始化红包
    }
    if (!isset($order['integral']))
    {
        $order['integral'] = 0; // 初始化积分
    }
    if (!isset($order['surplus']))
    {
        $order['surplus'] = 0;  // 初始化余额
    }

    /* 扩展信息 */
    if (isset($_SESSION['flow_type']) && intval($_SESSION['flow_type']) == CART_GROUP_BUY_GOODS)
    {
        $order['extension_code'] = 'group_buy';
        $order['extension_id'] = intval($_SESSION['group_buy_id']);
    }

    return $order;
}

/**
 * 合并订单
 * @param   string  $from_order_sn  从订单号
 * @param   string  $to_order_sn    主订单号
 * @return  成功返回true，失败返回错误信息
 */
function merge_order($from_order_sn, $to_order_sn)
{
    /* 订单号不能为空 */
    if (trim($from_order_sn) == '' || trim($to_order_sn) == '')
    {
        return $GLOBALS['_LANG']['order_sn_not_null'];
    }

    /* 订单号不能相同 */
    if ($from_order_sn == $to_order_sn)
    {
        return $GLOBALS['_LANG']['two_order_sn_same'];
    }

    /* 取得订单信息 */
    $from_order = order_info(0, $from_order_sn);
    $to_order   = order_info(0, $to_order_sn);

    /* 检查订单是否存在 */
    if (!$from_order)
    {
        return sprintf($GLOBALS['_LANG']['order_not_exist'], $from_order_sn);
    }
    elseif (!$to_order)
    {
        return sprintf($GLOBALS['_LANG']['order_not_exist'], $to_order_sn);
    }

    /* 检查合并的订单是否为普通订单，非普通订单不允许合并 */
    if ($from_order['extension_code'] != '' || $to_order['extension_code'] != 0)
    {
        return $GLOBALS['_LANG']['merge_invalid_order'];
    }

    /* 检查订单状态是否是已确认或未确认、未付款、未发货 */
    if ($from_order['order_status'] != OS_UNCONFIRMED && $from_order['order_status'] != OS_CONFIRMED)
    {
        return sprintf($GLOBALS['_LANG']['os_not_unconfirmed_or_confirmed'], $from_order_sn);
    }
    elseif ($from_order['pay_status'] != PS_UNPAYED)
    {
        return sprintf($GLOBALS['_LANG']['ps_not_unpayed'], $from_order_sn);
    }
    elseif ($from_order['shipping_status'] != SS_UNSHIPPED)
    {
        return sprintf($GLOBALS['_LANG']['ss_not_unshipped'], $from_order_sn);
    }

    if ($to_order['order_status'] != OS_UNCONFIRMED && $to_order['order_status'] != OS_CONFIRMED)
    {
        return sprintf($GLOBALS['_LANG']['os_not_unconfirmed_or_confirmed'], $to_order_sn);
    }
    elseif ($to_order['pay_status'] != PS_UNPAYED)
    {
        return sprintf($GLOBALS['_LANG']['ps_not_unpayed'], $to_order_sn);
    }
    elseif ($to_order['shipping_status'] != SS_UNSHIPPED)
    {
        return sprintf($GLOBALS['_LANG']['ss_not_unshipped'], $to_order_sn);
    }

    /* 检查订单用户是否相同 */
    if ($from_order['user_id'] != $to_order['user_id'])
    {
        return $GLOBALS['_LANG']['order_user_not_same'];
    }

    /* 合并订单 */
    $order = $to_order;
    $order['order_id']   = '';
    $order['order_time'] = date('Y-m-d H:i:s');

    // 合并商品总额
    $order['goods_amount'] += $from_order['goods_amount'];

    if ($order['shipping_id'] > 0)
    {
        // 重新计算配送费用
        $weight_price       = order_weight_price($to_order['order_id']);
        $from_weight_price  = order_weight_price($from_order['order_id']);
        $weight_price['weight'] += $from_weight_price['weight'];
        $weight_price['amount'] += $from_weight_price['amount'];

        $region_id_list = array($order['country'], $order['province'], $order['city'], $order['district']);
        $shipping_area = shipping_area_info($order['shipping_id'], $region_id_list);

        $order['shipping_fee'] = shipping_fee($shipping_area['shipping_code'],
            unserialize($shipping_area['configure']), $weight_price['weight'], $weight_price['amount']);

        // 如果保价了，重新计算保价费
        if ($order['insure_fee'] > 0)
        {
            $order['insure_fee'] = shipping_insure_fee($shipping_area['shipping_code'], $order['goods_amount'], $shipping_area['insure']);
        }
    }

    // 重新计算包装费、贺卡费
    if ($order['pack_id'] > 0)
    {
        $pack = pack_info($order['pack_id']);
        $order['pack_fee'] = $pack['free_money'] > $order['goods_amount'] ? $pack['pack_fee'] : 0;
    }
    if ($order['card_id'] > 0)
    {
        $card = card_info($order['card_id']);
        $order['card_fee'] = $card['free_money'] > $order['goods_amount'] ? $card['card_fee'] : 0;
    }

    // 红包不变，合并积分、余额、已付款金额
    $order['integral']      += $from_order['integral'];
    $order['integral_money'] = value_of_integral($order['integral']);
    $order['surplus']       += $from_order['surplus'];
    $order['money_paid']    += $from_order['money_paid'];

    // 计算应付款金额（不包括支付费用）
    $order['order_amount'] = $order['goods_amount']
                           + $order['shipping_fee']
                           + $order['insure_fee']
                           + $order['pack_fee']
                           + $order['card_fee']
                           + $order['bonus']
                           + $order['integral_money'];

    // 重新计算支付费
    if ($order['pay_id'] > 0)
    {
        // 货到付款手续费
        $cod_fee          = $shipping_area ? $shipping_area['pay_fee'] : 0;
        $order['pay_fee'] = pay_fee($order['pay_id'], $order['order_amount'], $cod_fee);

        // 应付款金额加上支付费
        $order['order_amount'] += $order['pay_fee'];
    }

    /* 插入订单表
    do
    {
        $order['order_sn'] = get_order_sn();
        if ($GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('order_info'), addslashes_deep($order), 'INSERT'))
        {
            break;
        }
        else
        {
            if ($GLOBALS['db']->errno() != 1062)
            {
                die($GLOBALS['db']->errorMsg());
            }
        }
    }
    while (true); // 防止订单号重复
    */
    if ($GLOBALS['db']->autoExecute($GLOBALS['ecs']->table('order_info'), addslashes_deep($order), 'INSERT'))
    {
		$order_id = $GLOBALS['db']->insert_id();
		$sql = "UPDATE " . $GLOBALS['ecs']->table('order_info') . " SET order_sn = '' WHERE $order_id = '$order_id' ";
		$GLOBALS['db']->query($sql);
    }

    /* 订单号 */
    $order_id = $GLOBALS['db']->insert_id();

    /* 更新订单商品 */
    $sql = 'UPDATE ' . $GLOBALS['ecs']->table('order_goods') .
            " SET order_id = '$order_id' " .
            "WHERE order_id " . db_create_in(array($from_order['order_id'], $to_order['order_id']));
    $GLOBALS['db']->query($sql);

    /* 删除原订单 */
    $sql = 'DELETE FROM ' . $GLOBALS['ecs']->table('order_info') .
            "WHERE order_id " . db_create_in(array($from_order['order_id'], $to_order['order_id']));
    $GLOBALS['db']->query($sql);

    /* 返还 from_order 的红包，因为只使用 to_order 的红包 */
    if ($from_order['bonus_id'] > 0)
    {
        unuse_bonus($from_order['bonus_id']);
    }

    /* 返回成功 */
    return true;
}




/**
 * 获取订单留言
 * @param   string  $order_id  从订单ID
 * @return  返回list
 */
function get_order_comments($order_id) {
    $sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('order_comment') . ' WHERE order_id='.$order_id." and status='OK' order by post_datetime desc";
    $comments = $GLOBALS['db']->getAll($sql);

    foreach ($comments as $key => $value) {
        if ($value['comment_cat'] == 1) $comments[$key]['comment_cat_desc'] = "订单支付";
        elseif ($value['comment_cat'] == 5) $comments[$key]['comment_cat_desc'] = "订单修改"; // modified zwsun 2008-6-27
        elseif ($value['comment_cat'] == 2) $comments[$key]['comment_cat_desc'] = "物流配送";
//        elseif ($value['comment_cat'] == 3) $comments[$key]['comment_cat_desc'] = "售后服务";
        elseif ($value['comment_cat'] == 4) $comments[$key]['comment_cat_desc'] = "订单确认";
        elseif ($value['comment_cat'] == 6) $comments[$key]['comment_cat_desc'] = "拒收申请"; // ncchen on 20081216

//        else $comments[$key]['comment_cat_desc'] = "其他问题";
    }
    return $comments;
}

/**
 * 根据订单order_amount 和 支付方式(shipping_id, pay_id) 计算默认手续费
 * @param   array $order
 * @return  int
 */
function calculte_proxy_amount(&$order)
{
	switch($order['pay_id'])
    {
        case 1 :#货到付款
            if ($order['shipping_id']== 11)
            {
            	return max(3.0,  0.01 * $order['order_amount']);
            }
            else
            {
            	return 0;
            }
            break;
        case 9 :
            return 0.01 * $order['order_amount'];
        case 2 :#邮局汇款
            return 0;
        case 3 :#银行汇款/转帐
            return 0;
        case 4 :#网银在线网上支付
            return 0.01 * $order['order_amount'];
        case 8 :#招商银行网上银行
            return 0.01 * $order['order_amount'];
        default:
            return 0;
    }
}




/**
 * 所有回复$reply_to的订单评论
 */
function get_after_order_comment_reply($reply_to)
{
    $sql = "SELECT * FROM `ecs_after_order_comment` WHERE `reply_to` = $reply_to "	;
    return $GLOBALS['db']->getAll($sql);

}

/**
 * 根据ecs_after_order_comment 表的一行获得所有相关信息
 * @return   array('main' => xx, 'replys' => xx, 'order_detail' => xx )
 */
function build_after_order_comment_item($row)
{
    	$user_id = get_userSessionkey_info($row['user_id']);
        $info =  get_user_default($user_id);
        $row['info'] = $info;
        $sql = "SELECT `bj_store`.`name`,`bj_store`.`store_id` FROM `bj_store` LEFT JOIN `ecs_order_info` AS oi ON `oi`.`biaoju_store_id` = `bj_store`.`store_id` 
            WHERE `oi`.`order_id` = $row[order_id] "; 
        $store_info = $GLOBALS['db']->getRow($sql);
        $sql = "select rn.name from reply_nick rn, ecs_after_order_comment eaoc where eaoc.reply_to = {$row['comment_id']} and eaoc.user_id = rn.user_id";
        $nick_name = $GLOBALS['db']->getRow($sql);
        //print $nick_name["name"];
        if(!$store_info)
        {
            $row['store_name'] = "欧酷正品行货商城";
        }
        else
        {
            $row['store_name'] = $store_info['name'];
        }
        
        // 添加购买次数
        $sql = "SELECT COUNT(*) FROM {$GLOBALS['ecs']->table('order_info')} WHERE order_status = 1 AND (shipping_status = 2 OR shipping_status = 6) AND user_id = '{$info['user_id']}'";
        $confirm_order_count = $GLOBALS['db']->getOne($sql);
        $row['info']['confirm_order_count'] = $confirm_order_count;
       
        return array(
             'main' => $row,
             'replys' => get_after_order_comment_reply($row['comment_id']),
             'order_detail' => get_order_detail($row['order_id'], $info['user_id']),
             'nick_name' => $nick_name["name"]
         );
}

/**
 * 获得这个用户的评论（交易完成后的评论)
 * @param $userId 32位的字符串
 * @param array order_comments
 */
function get_after_order_comment($userId = "", $limit = "")
{
    if($userId != "")
    {
        $sql = "SELECT * FROM `ecs_after_order_comment` WHERE `user_id` = '$userId' AND `user_type` = 1 ORDER BY `post_time` DESC LIMIT $limit";
    }
    else
    {
        $sql = "SELECT * FROM `ecs_after_order_comment` WHERE `user_type` = 1 ORDER BY `post_time` DESC LIMIT $limit";
    }
    $rows = $GLOBALS['db']->getAll($sql);
    $comments = array();
    foreach($rows as $row)
    {
        $comments[$row['comment_id']] = build_after_order_comment_item($row);
    }
    return $comments;
}

/**
 * 获得评论（交易完成后的评论)的数目
 * @param string $userId $userId (char32)
 */
function get_after_order_comment_count($userId = "")
{
    if($userId != "")
    {
        $sql = "SELECT count(*) FROM `ecs_after_order_comment` WHERE `user_id` = '$userId' AND `user_type` = 1 ";
    }
    else
    {
        $sql = "SELECT count(*) FROM `ecs_after_order_comment` WHERE `user_type` = 1";
    }
    return $GLOBALS['db']->getOne($sql);
}


/**
 * 根据goods_id biaoju_store_goods_id 获得订单评论
 * @author Tao Fei (ftao@oukoo.com)
 * @param int $goods_id goods_id
 * @param int $biaoju_store_goods_id biaoju_store_goods_id
 * @return array 该商品相关的订单评论
 */
function get_after_order_comment_by_goods($goods_id = 0, $biaoju_store_goods_id = 0, $limit = "")
{
    if($goods_id <= 0 && $biaoju_store_goods_id <=0)
    {
        return array();
    }

    if($biaoju_store_goods_id  > 0)
    {
        $sql = "SELECT * FROM `ecs_after_order_comment` WHERE  `user_type`=1 AND `order_id` in  " .
            " (SELECT `order_id` FROM `ecs_order_goods` WHERE biaoju_store_goods_id =$biaoju_store_goods_id) AND exists(select 1 from ecs_after_order_comment r where r.reply_to=ecs_after_order_comment.comment_id) AND `show_tag`=1 ORDER BY `post_time` DESC LIMIT $limit";
    }
    else
    {
        $sql = "SELECT * FROM `ecs_after_order_comment` WHERE  `user_type`=1 AND `order_id` in  " .
            " (SELECT `order_id` FROM `ecs_order_goods` WHERE goods_id =$goods_id AND biaoju_store_goods_id = 0) AND exists(select 1 from ecs_after_order_comment r where r.reply_to=ecs_after_order_comment.comment_id) AND `show_tag`=1 ORDER BY `post_time` DESC LIMIT $limit";  
    }
    $rows = $GLOBALS['db']->getAll($sql);
    $comments = array();
    foreach($rows as $row)
    {
        $comments[$row['comment_id']] = build_after_order_comment_item($row);
    }
    return $comments;
}

/**
 * 根据goods_id biaoju_store_goods_id 获得订单评论数目
 * @param int $goods_id goods_id
 * @param int $biaoju_store_goods_id biaoju_store_goods_id
 */
function get_after_order_comment_count_by_goods($goods_id = 0, $biaoju_store_goods_id = 0)
{
    if($goods_id <= 0 && $biaoju_store_goods_id <=0)
    {
        return 0;
    }

    if($biaoju_store_goods_id  > 0)
    {
      $sql = "SELECT COUNT(*) FROM `ecs_after_order_comment` a
              INNER JOIN ecs_order_goods og on a.order_id = og.order_id
              WHERE  a.`user_type`=1 AND a.`show_tag`=1 AND og.biaoju_store_goods_id = '{$biaoju_store_goods_id}' AND og.biaoju_store_goods_id = 0;";
//         $sql = "SELECT COUNT(*) FROM `ecs_after_order_comment` WHERE  `user_type`=1 AND `order_id` in  " .
//            " (SELECT `order_id` FROM `ecs_order_goods` WHERE biaoju_store_goods_id =$biaoju_store_goods_id) AND `show_tag`=1";
    }
    else
    {
      $sql = "SELECT COUNT(*) FROM `ecs_after_order_comment` a
              INNER JOIN ecs_order_goods og on a.order_id = og.order_id
              WHERE  a.`user_type`=1 AND a.`show_tag`=1 AND og.goods_id = '{$goods_id}' AND og.biaoju_store_goods_id = 0;";
//        $sql = "SELECT COUNT(*) FROM `ecs_after_order_comment` WHERE  `user_type`=1 AND `order_id` in  " .
//            " (SELECT `order_id` FROM `ecs_order_goods` WHERE goods_id =$goods_id AND biaoju_store_goods_id = 0) AND `show_tag`=1";
    }
    return $GLOBALS['db']->getOne($sql);
}


/**
 * 根据store_id 获得订单评论
 * @author Tao Fei (ftao@oukoo.com)
 * @param int $store_id  0 表示 ouku
 * @return array 该商品相关的订单评论
 */
function get_after_order_comment_by_store_id($store_id, $limit)
{
    $sql = "SELECT * FROM `ecs_after_order_comment` WHERE  `user_type`=1 AND `order_id` in  " .
            " (SELECT `order_id` FROM `ecs_order_info` WHERE biaoju_store_id = $store_id) AND exists(select 1 from ecs_after_order_comment r where r.reply_to=ecs_after_order_comment.comment_id) AND `show_tag` = 1 ORDER BY `post_time` DESC LIMIT $limit";
    $rows = $GLOBALS['db']->getAll($sql);
    $comments = array();
    foreach($rows as $row)
    {
        $comments[$row['comment_id']] = build_after_order_comment_item($row);
    }
   
    return $comments;
}

/**
 * 根据goods_id store_id 获得订单评论数目
 * @author Tao Fei (ftao@oukoo.com)
 * @param int $store_id store_id  0 表示 ouku 
 * @return int
 */
function get_after_order_comment_count_by_store_id($store_id)
{
    $sql = "SELECT COUNT(*) FROM `ecs_after_order_comment` WHERE  `user_type`=1 AND `order_id` in  " .
            " (SELECT `order_id` FROM `ecs_order_info` WHERE biaoju_store_id =$store_id) AND exists(select 1 from ecs_after_order_comment r where r.reply_to=ecs_after_order_comment.comment_id)  AND `show_tag`=1";
    return $GLOBALS['db']->getOne($sql);
}


function get_all_shipping_area()
{
    $provinces = get_regions(1, $GLOBALS['_CFG']['shop_country']);
    //$provinces[] = array('region_id' => 233, 'region_name' => "广州");
    //$provinces[] = array('region_id' => 234, 'region_name' => "深圳");
    return $provinces;
}


function get_area_shipping_data_by_name($region_name,$goodsId,$styleId,$type)
{
    $area = get_region_by_name($region_name);
    if(!$area)
    {
        return array();
    }
    else
    {
        return get_area_shipping_data($area['region_id'],$goodsId,$styleId,$type);
    }
}

function get_area_shipping_data($region_id,$goodsId,$styleId,$type=0)
{
    global $_CFG;
    $filename = ROOT_PATH . 'includes/modules/shipping/common.php';
    if (file_exists($filename)){
      include_once($filename);
    }     
    //pp($goodsId);
    $region = array($_CFG['shop_country']);
//    if($region_id == 233 || $region_id == 234)
//    {
//        $region[] = 20;
//        $region[] = $region_id;
//        $region[] = 0;
//    }
//    else
//    {
    if($type == 1){
        $region[] = $region_id;
        $region[] = 0;
        $region[] = 0;            	
    }else if($type == 2){
        $sql = "SELECT parent_id FROM {$GLOBALS['ecs']->table('region')} WHERE region_id={$region_id}";
        $region[] = $GLOBALS['db']->getOne($sql);
        $region[] = $region_id;
        $region[] = 0;                	
    }else if($type == 3){
    	  $sql = "SELECT parent_id FROM {$GLOBALS['ecs']->table('region')} WHERE region_id={$region_id}";
    	  $parent_id = $GLOBALS['db']->getOne($sql);
    	  $sql = "SELECT parent_id FROM {$GLOBALS['ecs']->table('region')} WHERE region_id={$parent_id}";
        $region[] = $GLOBALS['db']->getOne($sql);
        $region[] = $parent_id;
        $region[] = $region_id;                  
    }
//    }
    $asl = available_shipping_list2($region);
    if($goodsId != 0){
      $sql = "SELECT goods_weight , shop_price, sale_status FROM {$GLOBALS['ecs']->table('goods')} WHERE goods_id = $goodsId";
      $sql_style = "SELECT style_id,style_price FROM {$GLOBALS['ecs']->table('goods_style')} WHERE goods_id = $goodsId";
      $goods = $GLOBALS['db']->getRow($sql);
      $goods_style = $GLOBALS['db']->getAll($sql_style);
      #pp($goods_style);
      $goods_weight = $goods['goods_weight'];
      $goods_price = intval($goods['shop_price']);
      $to_sale = $goods['sale_status'];
      //pp($styleId);
        $price_max = 0;
        $price_min = 0;
      if($goods_style){
        $price_min = 9999999;
        foreach ($goods_style as $style){
            if($style['style_price'] > $price_max) {
              $price_max = $style['style_price'];
            }
            if($style['style_price'] < $price_min) {
              $price_min = $style['style_price'];
            }
            if($style['style_id'] == $styleId){
               $goods_price = intval($style['style_price']);
            }
         }
       }
       if ( $styleId == 0 && $price_min ) {
         $goods_price = $price_min;
       }
    }
    
        
    //推荐快递：在有顺丰的情况下后面加推荐字样，没有顺丰只有EMS的情况下在EMS后面加推荐
    $cod_recommend= null;
    $no_cod_recommend= null;
    
//    pp($asl);
    $package_fee = 2; 
    foreach($asl as $key => $shipping)
    {
        $t = $shipping;
        $config = unserialize_config($t['configure']);
        $shipping_config = unserialize($t['configure']);
//        pp($config);
        #pp($shipping_config);
        if($goodsId != 0){
           $obj = new common($shipping_config);
           $total_fee = $obj->calculate($goods_weight, $goods_price);
           $goods_amount = $goods_price + $total_fee + $package_fee;
           if($config['percent']){
             $goods_amount += floor($goods_price * $config['percent']/100);
//             pp($config['percent']);
           }
        }
        $total_fee = $goodsId == 0 ? $config['basic_fee'] : $total_fee;
        
        if(strpos($t['shipping_name'], "同城") !== false)
        {
            if ($t['support_no_cod'] == 1 && $t['support_cod'] == 0)
            {
               if($no_cod_recommend === null)
               {
                   $no_cod_recommend = $key;
               }
               $cod = 0;
            }
            elseif ($t['support_no_cod'] == 0 && $t['support_cod'] == 1)
            {
               if($cod_recommend === null)
               {
                   $cod_recommend = $key;
               }
               $cod = 1;
            }
        }        
        if(strpos($t['shipping_name'], "顺丰") !== false)
        {
            if ($t['support_no_cod'] == 1 && $t['support_cod'] == 0)
            {
               if($no_cod_recommend === null)
               {
                   $no_cod_recommend = $key;
               }
               $cod = 0;
            }
            elseif ($t['support_no_cod'] == 0 && $t['support_cod'] == 1)
            {
               if($cod_recommend === null)
               {
                   $cod_recommend = $key;
               }
               $cod = 1;
            }
        }
        if(strpos($t['shipping_name'], "EMS") !== false)
        {
            if ($t['support_no_cod'] == 1 && $t['support_cod'] == 0)
            {
               if($no_cod_recommend === null)
               {
                   $no_cod_recommend = $key;
               }
               $cod = 0;
            }
            elseif ($t['support_no_cod'] == 0 && $t['support_cod'] == 1)
            {
               if($cod_recommend === null)
               {
                   $cod_recommend = $key;
               }
               $cod = 1;
            }
        }
        $shipping_name = strtok($t['shipping_name'],'(');
        
        $desc = sprintf("%s需%s天 运费%s元 包装费%s元", $shipping_name, $config['delivery_time'], $total_fee, $package_fee);
        
        if($config['percent'])
        {
            $desc .= sprintf("+%s%%总额", $config['percent']);
        }
        if($to_sale == 'tosale'){
          $desc .= sprintf(" <span id='goods_amount_%s'>待定</span>",$cod);
        }else{        
	        if($goodsId != 0){
	          if($price_min != $price_max && $styleId == 0){
	            $desc .= sprintf(" <span id='goods_amount_%s'>(先选颜色)</span>",$cod);
	          }else{
	            $desc .= sprintf(" <span id='goods_amount_%s'>%s元</span>",$cod,$goods_amount);
	          }
	        }
        }  

        $t['desc_to_show'] = $desc;
        $asl[$key] = $t;
        $asl[$key]['total_fee'] = $total_fee;
        $asl[$key]['percent'] = $config['percent'];
    }
    if($cod_recommend !== null)
    {
        //$asl[$cod_recommend]['desc_to_show'] .= "<sup style='color:red'>推荐</sup>";
        $asl[$cod_recommend]['cod_commend'] = $asl[$cod_recommend]['desc_to_show'];
    }
    if($no_cod_recommend !== null)
    {
        //$asl[$no_cod_recommend]['desc_to_show'] .= "<sup style='color:red'>推荐</sup>";
        $asl[$no_cod_recommend]['no_cod_commend'] = $asl[$no_cod_recommend]['desc_to_show'];
    }
    return $asl;
}

function format_shipping_data($data)
{    	
    $m=0;
    $n=0;
    $cod_html = "";
    $nocod_html = "";
    $total_fee = 0;
    foreach($data as $key=>$item)
    {
        if ($item['support_no_cod'] == 1 && $item['support_cod'] == 0)
        {	
            $m += 1;
            if($m==2){
                $nocod_html .= $item['desc_to_show'] . '&nbsp;&nbsp;&nbsp;&nbsp;';
            }else{
                $nocod_html .= $item['desc_to_show'] . '<br/>';
            }
			     if($item['no_cod_commend']){
			       $commend_nocod_html = $item['no_cod_commend'];
			     	 $total_fee = $item['total_fee'];     
			     }       
        }
        if ($item['support_no_cod']== 0 && $item['support_cod'] == 1)
        {
            $n += 1;
            if($n==1){
                $cod_html .= $item['desc_to_show'] . '<br/>';
            }else{
                $cod_html .= $item['desc_to_show'] . '&nbsp;&nbsp;&nbsp;&nbsp;';
            }
            if($item['cod_commend']) {
              $commend_cod_html = $item['cod_commend'];
              $total_fee_cod = $item['total_fee'];
              $cod_percent = $item['percent'];               	
            }
        }
    }
    return array("cod_html" => $cod_html, "nocod_html" => $nocod_html,"commend_nocod_html"=>$commend_nocod_html,"commend_cod_html"=>$commend_cod_html,"total_fee"=>$total_fee,"total_fee_cod" => $total_fee_cod ,"cod_percent" => $cod_percent);
}

function filter_by_biaoju_support($item)
{
    return $item['support_biaoju'] == 1;
}

/**
 * 检查限购 add by zwsun 2008-8-23
 * check_buy_restrict 
 * @param int $good_id 商品
 * @param int $style_id 颜色
 * @param int $num 新购买的数量，修改订单时调用此函数时要注意传过来的是差值。
 * @param int $is_check_cart 是否检查购物车
 * @param array $check_array 检查历史订单的限制条件
 * @return string $message
 */

function check_buy_restrict($goods_id, $style_id, $num, $is_check_cart = true, $check_array = null) {
  global $db, $ecs, $userInfo;
  //检查限购 
  $message = '';
  $now = date("Y-m-d H:i:s");
	$sql = "SELECT restrict_count, restrict_begin, restrict_end FROM {$ecs->table('buy_restrict')} WHERE goods_id = '{$goods_id}' AND restrict_begin <= '{$now}' AND '{$now}' < restrict_end ";
	if ($buy_restrict = $db->getRow($sql)) {
	  $restrict_count = $buy_restrict['restrict_count'];
	  $restrict_begin = $buy_restrict['restrict_begin'];
	  $restrict_end = $buy_restrict['restrict_end'];
	  
	  if (intval($num) > $restrict_count) {
	  	$message = "很抱歉，该产品只能限购 $restrict_count 件。";
	  }
		
	  //检查购物车里面是否已经买过了
	  $sql = "SELECT SUM(c.goods_number) FROM {$ecs->table('cart')} AS c 
		        INNER JOIN  {$ecs->table('goods')} AS g ON g.goods_id = c.goods_id
		        WHERE c.session_id = '" . SESS_ID . "' AND c.biaoju_store_goods_id = 0 AND c.goods_id = '{$goods_id}' " ;
		if ($is_check_cart && $exists_count = $db->getOne($sql)) {
    	if ( ($exists_count + intval($num)) > $restrict_count ) {
      	$left_count = $restrict_count - $exists_count;
		  	$message = "很抱歉，该产品只能限购 $restrict_count 件，您已经购买了 $exists_count 件，". ( $left_count > 0 ? "最多还能购买 $left_count 件。" : "无法购买更多。" );
      }
    }
    
    //检查用户以前的购买记录以免投机取巧
    $condition_array = array();
    $condition = '';

    if ($check_array['tel']) {
    	$condition_array[] = " info.tel = '{$check_array['tel']}' ";
    }
    if ($check_array['mobile']) {
    	$condition_array[] = " info.mobile = '{$check_array['mobile']}' ";
    }
    if ($userInfo) {
    	$condition_array[] = " info.user_id = '{$userInfo['user_id']}' ";
    }
    if (!empty($condition_array)) {
    	$condition = ' AND (' . join(' OR ', $condition_array) .' )';
    }
    if($condition) {
		  $sql = "SELECT SUM(og.goods_number) FROM {$ecs->table('order_info')} info INNER JOIN {$ecs->table('order_goods')} og ON info.order_id = og.order_id WHERE info.order_status != 2 AND og.goods_id = '{$goods_id}'  AND info.order_time >= '{$restrict_begin}' AND info.order_time < '{$restrict_end}' {$condition} ";
		  if ($buied_count = $db->getOne($sql)) {
		  	if ( ($buied_count + intval($num)) > $restrict_count ) {
        	$left_count = $restrict_count - $buied_count;
  		  	$message = "很抱歉，该产品只能限购 $restrict_count 件，您以前已经购买了 $buied_count 件，". ( $left_count > 0 ? "最多还能购买 $left_count 件。" : "无法购买更多。" );
        }
		  }
    }
	}
	
	return $message;
}
/**
 * 取得缺货信息
 * @param   int     $order_id     订单id
 * @return  int   缺货信息
 */
function shortage_info($order_id)
{
    $sql = "SELECT shortage_status FROM {$GLOBALS['ecs']->table('order_info')} WHERE order_id = '$order_id'";

    return $GLOBALS['db']->getOne($sql);
}

/**
 * 取得订单商品的属性 
 *
 * @param int $order_goods_id
 * 
 * @return array|false
 */
function get_order_goods_attribute($order_goods_id)
{
	global $db;
	
	$sql = "
		SELECT name, value FROM order_goods_attribute WHERE order_goods_id = {$order_goods_id}
	";
	$result = $db->query($sql);
	
	if ($result) {
		$attribute = array();
		while ($row = $db->fetchRow($result)) {
			$attribute[$row['name']] = $row['value'];
		}
		return $attribute;
	}
	
	return $result;
}

/**
 * 取得订单商品的已预定库存状态, 如果该订单没有预定库存，将返回FALSE
 * 
 * 如果订单已预定，将返回订单商品的已预定量、可预定量、总库存，已经在可用仓库的可预定量和库存
 * 
 * @param int $order_id
 * @param int $order_party_id
 * 
 * @return array
 */
function get_order_goods_inventory($order_id, $order_party_id) {
	global $db;
    $order_goods_inventory = array();
    
    $sql="select * from romeo.order_inv_reserved where ORDER_ID = '{$order_id}'";
    $order_inv_reserved=$db->getRow($sql);
    if ($order_inv_reserved) {
		$order_goods_inventory['order']['status'] = $order_inv_reserved['STATUS'];  // 订单的预定状态
		$order_goods_inventory['order']['reservedTime'] = $order_inv_reserved['RESERVED_TIME'];  // 订单的预定时间
		$order_goods_inventory['order']['partyId'] = $order_inv_reserved;
		$order_goods_inventory['order']['facilityId'] = $order_inv_reserved['FACILITY_ID'];
		
		$sql="select * from romeo.order_inv_reserved_detail where ORDER_INV_RESERVED_ID = '{$order_inv_reserved['ORDER_INV_RESERVED_ID']}'";
		$order_inv_reserved_detail=$db->getAll($sql);
		if ($order_inv_reserved_detail) {
			foreach ($order_inv_reserved_detail as $item) {
            	$goodsIdStyleId = getGoodsIdStyleIdByProductId($item['PRODUCT_ID']);
            	$goodsStyleId = $goodsIdStyleId['goods_id'] .'_'. $goodsIdStyleId['style_id'];
             
            	// 已预定量
            	$order_goods_inventory[$goodsStyleId]['reservedQuantity'] += $item['RESERVED_QUANTITY'];
           	 	// 设置商品状态
            	$order_goods_inventory[$goodsStyleId]['statusId'] = $item['STATUS_ID'];
            	// 商品预订状态
            	$order_goods_inventory[$goodsStyleId]['status'] = $item['STATUS'];
            	
            	// 取得库存总表
                $inventory_summary_sql="select * from romeo.inventory_summary where PRODUCT_ID = '{$item['PRODUCT_ID']}' AND STATUS_ID = '{$item['STATUS_ID']}'";
                $inventory_summary_list=$db->getAll($inventory_summary_sql);
                if($inventory_summary_list) {
                    foreach($inventory_summary_list as $inventory_summary){
                    	$facilityId=$inventory_summary['FACILITY_ID'];
				        $order_goods_inventory[$goodsStyleId]['availableToReserved'] += $inventory_summary['AVAILABLE_TO_RESERVED'];
				        // 总库存
				        $order_goods_inventory[$goodsStyleId]['stockQuantity'] += $inventory_summary['STOCK_QUANTITY'];
				        // 不同仓库下的可预定量和库存
				        $order_goods_inventory[$goodsStyleId]['facilityQuantity'][$facilityId]['availableToReserved'] += $inventory_summary['AVAILABLE_TO_RESERVED'];
				        $order_goods_inventory[$goodsStyleId]['facilityQuantity'][$facilityId]['stockQuantity'] += $inventory_summary['STOCK_QUANTITY'];
                    }
                }
			}
		}
		
		return $order_goods_inventory;
    }
    
    return false;
    
    /*
    try {
        include_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');
        $handle = soap_get_client('InventoryService');
        $orderIdList = array($order_id);
        $facilityIdList = array_keys(get_available_facility($order_party_id));
        $param = array('orderIdList' => $orderIdList, 'facilityIdList' => $facilityIdList);
        $response = $handle->getOrderInvReservedListByOrderIds($param);
        if (isset($response->return->OrderInvReserved)) {
            $order = $response->return->OrderInvReserved;
            $order_goods_inventory['order']['status'] = $order->status;  // 订单的预定状态
            $order_goods_inventory['order']['reservedTime'] = $order->reservedTime;  // 订单的预定时间
            $order_goods_inventory['order']['partyId'] = $order->partyId;
            // 仓库id
            $order_goods_inventory['order']['facilityId'] = $order->facilityId;
        }
    } 
    catch (SoapFault $e) {
        trigger_error("取得库存预定信息错误，错误信息： " . $e->faultstring, E_USER_WARNING);
    }
    
    if ($order && isset($order->orderInvReservedDetailList->OrderInvReservedDetail)) {
        $goods_list = wrap_object_to_array($order->orderInvReservedDetailList->OrderInvReservedDetail);
        foreach ($goods_list as $goods) {
        	// 只计算新的
            $goodsIdStyleId = getGoodsIdStyleIdByProductId($goods->productId);
            $goodsStyleId = $goodsIdStyleId['goods_id'] .'_'. $goodsIdStyleId['style_id'];
             
            // 已预定量
            $order_goods_inventory[$goodsStyleId]['reservedQuantity'] += $goods->reservedQuantity;
            // 设置商品状态
            $order_goods_inventory[$goodsStyleId]['statusId'] = $goods->statusId;
            // 商品预订状态
            $order_goods_inventory[$goodsStyleId]['status'] = $goods->status;
             
            if (isset($goods->inventorySummaryList->InventorySummary)) {
                $inventorySummary = wrap_object_to_array($goods->inventorySummaryList->InventorySummary);
	            foreach ($inventorySummary as $inventory) {
	                // 判断库存的新旧状态和订单中商品的新旧是否一致，不一致跳过
                    if ($inventory->statusId != $goods->statusId){
	            		continue;
                    }
                    $facilityId = $inventory->facilityId;
		            // 可预定
		            $order_goods_inventory[$goodsStyleId]['availableToReserved'] += $inventory->availableToReserved;
		            // 总库存
		            $order_goods_inventory[$goodsStyleId]['stockQuantity'] += $inventory->stockQuantity;
		            // 不同仓库下的可预定量和库存
		            $order_goods_inventory[$goodsStyleId]['facilityQuantity'][$facilityId]['availableToReserved'] += $inventory->availableToReserved;
		            $order_goods_inventory[$goodsStyleId]['facilityQuantity'][$facilityId]['stockQuantity'] += $inventory->stockQuantity;
                }
            }
        }
        return $order_goods_inventory;
    }
    
    return FALSE;
    */
}
