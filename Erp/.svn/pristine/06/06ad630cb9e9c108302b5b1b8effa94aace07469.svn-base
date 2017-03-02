<?php

/**
 * OUKU 支付宝插件
 */

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

$payment_lang = ROOT_PATH . 'languages/' .$GLOBALS['_CFG']['lang']. '/payment/alipay.php';

if (file_exists($payment_lang))
{
    global $_LANG;

    include_once($payment_lang);
}

/* 模块的基本信息 */
if (isset($set_modules) && $set_modules == TRUE)
{
    $i = isset($modules) ? count($modules) : 0;

    $code = basename(__FILE__, '.php');
    $sql = "SELECT * FROM {$ecs->table('payment')} WHERE pay_code = '{$code}'";
    $payments = $db->getAll($sql);

    foreach ($payments as $key => $payment) {
        $modules[$payment['pay_id']] = $payment;
        $modules[$payment['pay_id']]['author'] = 'oukoo';
    }

    //    /* 代码 */
    //    $modules[$i]['code']    = basename(__FILE__, '.php');
    //
    //    /* 描述对应的语言项 */
    //    $modules[$i]['desc']    = 'alipay_desc';
    //
    //    /* 是否支持货到付款 */
    //    $modules[$i]['is_cod']  = '0';
    //
    //    /* 作者 */
    //    $modules[$i]['author']  = 'oukoo';
    //
    //    /* 网址 */
    //    $modules[$i]['website'] = 'http://www.alipay.com';
    //
    //    /* 版本号 */
    //    $modules[$i]['version'] = '1.0.1';
    //
    //    /* 配置信息 */
    //    $modules[$i]['config']  = array(
    //        array('name' => 'alipay_account',           'type' => 'text',   'value' => ''),
    //        array('name' => 'alipay_key',               'type' => 'text',   'value' => ''),
    //        array('name' => 'alipay_partner',           'type' => 'text',   'value' => ''),
    //        array('name' => 'alipay_real_method',       'type' => 'select', 'value' => '0'),
    //        array('name' => 'alipay_virtual_method',    'type' => 'select', 'value' => '0')
    //    );


    return;
}

/**
 * 类
 */
class alipay_direct
{

    /**
     * 构造函数
     *
     * @access  public
     * @param
     *
     * @return void
     */
    // function alipay_direct()
    // {
    // }

    function __construct()
    {
        $this->alipay_direct();
    }

    /**
     * 生成支付代码
     * @param   array   $order      订单信息
     * @param   array   $payment    支付方式信息
     */
    function get_code($order, $payment)
    {
        if (!empty($order['order_id']))
        {
            /* 欧酷网目前全部为实体商品 */
            $service = (!empty($payment['alipay_real_method']) 
                && $payment['alipay_real_method'] == 1) ?
                'create_direct_pay_by_user' : 'trade_create_by_buyer';
        }
        else
        {
            /* 非订单方式，按照虚拟商品处理 */
            $service = (!empty($payment['alipay_virtual_method']) 
                && $payment['alipay_virtual_method'] == 1) ?
                'create_direct_pay_by_user' : 'create_digital_goods_trade_p';
        }

        $parameter = array(
        'service'           => $service,
        'partner'           => $payment['alipay_partner'],
        '_input_charset'    => 'utf-8',
        //'return_url'        => return_url(basename(__FILE__, '.php')),
        'return_url'        => RETURNURL.'?code=alipay_direct',
        'notify_url'        => RESPONDURL.'?code=alipay_direct',
        /* 业务参数 */
        'subject'           => $order['order_sn'],
        'out_trade_no'      => $order['log_id'],
        'price'             => number_format($order['order_amount'], 2, ".", ""),
        'quantity'          => 1,
        'payment_type'      => 1,
        /* 物流参数 */
        'logistics_type'    => 'EXPRESS',
        'logistics_fee'     => 0,
        'logistics_payment' => 'BUYER_PAY_AFTER_RECEIVE',
        /* 买卖双方信息 */
        'seller_email'      => $payment['alipay_account'],
        /*银行直连*/
        'paymethod'         => 'bankPay',
        'defaultbank'       => $payment['alipay_bankId']
        );

        ksort($parameter);
        reset($parameter);

        $param = '';
        $sign  = '';

        foreach ($parameter AS $key => $val)
        {
            $param .= "$key=" .urlencode($val). "&";
            $sign  .= "$key=$val&";
        }

        $param = substr($param, 0, -1);
        $sign  = substr($sign, 0, -1). $payment['alipay_key'];

        $button = '<span class="alipayDiv"><input type="button" class="alipayButton" '. 
            'onclick="window.open(\'https://www.alipay.com/cooperate/gateway.do?'.$param. 
            '&sign='.md5($sign).'&sign_type=MD5\');" value="[使用支付宝支付]" />'. 
            '<span id="alipayHelp"><a href="http://help.alipay.com/support/3716-4100/0-4100.htm" '. 
            'target="_blank" style="font-size:12px;">[支付宝帮助]</a></span>
        </span>
';

        global $db, $ecs;
        $ouku_request = "https://www.alipay.com/cooperate/gateway.do?" .$param. "&sign=".
            md5($sign)."&sign_type=MD5";
        $sql = "UPDATE {$ecs->table('pay_log')} SET ouku_request = '{$ouku_request}' 
            WHERE log_id = '{$order['log_id']}'";
        $db->query($sql);

        return $button;
    }

    /**
     * 响应操作
     */
    function respond()
    {
        $payment  = get_payment($_GET['code']);
        $pay_log_id = trim($_GET['out_trade_no']);
        $discount = doubleval(trim($_GET['discount']));
        $total_fee = doubleval(trim($_GET['total_fee']));
        /* 检查支付的金额是否相符 */
        if (!check_money($pay_log_id, $total_fee-$discount))
        {
            require_once(ROOT_PATH. "includes/lib_common.php");
            send_mail("caiwu@ouku.com", "caiwu@ouku.com", "支付宝支付金额不符", 
                "支付宝支付金额不符 订单号{$_GET['subject']} 付款金额{$total_fee}");
            return false;
        }

        $notify_query_url = "http://notify.alipay.com/trade/notify_query.do?". 
            "partner={$payment['alipay_partner']}&notify_id={$_GET['notify_id']}";

        $result = (string) file_get_contents($notify_query_url, false);
        $sql = 'insert into ' . $GLOBALS['ecs']->table('pay_log_action') .
        " SET log_id = '$pay_log_id', response_data='$notify_query_url\n$result' ";
        $GLOBALS['db']->query($sql);
        if ($result != "true") {
            return false;
        }
        /* 检查数字签名是否正确 */
        ksort($_GET);
        reset($_GET);

        $sign = '';
        foreach ($_GET AS $key=>$val)
        {
            if ($key != 'sign' && $key != 'sign_type' && $key != 'code')
            {
                $sign .= "$key=$val&";
            }
        }

        $sign = substr($sign, 0, -1) . $payment['alipay_key'];
        if (md5($sign) != $_GET['sign'])
        {
            return false;
        }

        if ($_GET['trade_status'] == 'TRADE_FINISHED' || $_GET['trade_status'] == 'TRADE_SUCCESS')
        {
            /* 改变订单状态 */
            if (abs($discount) > 0) {
                order_paid($pay_log_id, 2, "用户支付，实付{$total_fee}元，活动抵用{$discount}元。".
                    "支付宝积分换ouku红包活动，自动产生{$discount}元红包。", $total_fee);
            } else {
                order_paid($pay_log_id);
            }
            return true;
        }
        else
        {
            return false;
        }
    }
    /**
     * 异步
     *
     * @return unknown
     */
    function respond_asyn()
    {
        $payment  = get_payment($_POST['code']);
        $pay_log_id = trim($_POST['out_trade_no']);
        $discount = doubleval(trim($_POST['discount']));
        $total_fee = doubleval(trim($_POST['total_fee']));
        /* 检查支付的金额是否相符 */
        if (!check_money($pay_log_id, $total_fee-$discount))
        {
            require_once(ROOT_PATH. "includes/lib_common.php");
            send_mail("caiwu@ouku.com", "caiwu@ouku.com", "支付宝支付金额不符", 
                "支付宝支付金额不符 订单号{$_POST['subject']} 付款金额{$total_fee}");
            return false;
        }
        $notify_query_url = "http://notify.alipay.com/trade/notify_query.do?partner=". 
            "{$payment['alipay_partner']}&notify_id={$_POST['notify_id']}";

        $result = (string) file_get_contents($notify_query_url, false);
        $sql = 'insert into ' . $GLOBALS['ecs']->table('pay_log_action') .
        " SET log_id = '$pay_log_id', response_data='$notify_query_url\n$result' ";
        $GLOBALS['db']->query($sql);
        if ($result != "true") {
            return false;
        }
        /* 检查数字签名是否正确 */
        ksort($_POST);
        reset($_POST);

        $sign = '';
        foreach ($_POST AS $key=>$val)
        {
            if ($key != 'sign' && $key != 'sign_type' && $key != 'code')
            {
                $sign .= "$key=$val&";
            }
        }

        $sign = substr($sign, 0, -1) . $payment['alipay_key'];
        if (md5($sign) != $_POST['sign'])
        {
            return false;
        }

        if ($_POST['trade_status'] == 'TRADE_FINISHED' || $_POST['trade_status'] == 'TRADE_SUCCESS')
        {
            /* 改变订单状态 */
            if (abs($discount) > 0) {
                order_paid($pay_log_id, 2, "用户支付，实付{$total_fee}元，活动抵用{$discount}元。". 
                    "支付宝积分换ouku红包活动，自动产生{$discount}元红包。", $total_fee);
            } else {
                order_paid($pay_log_id);
            }
            return true;
        }
        else
        {
            return false;
        }
    }
}

?>