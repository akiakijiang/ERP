<?php

/**
 * ECSHOP 贝宝插件
 * ============================================================================
 * 版权所有 (C) 2005-2007 北京亿商互动科技发展有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com
 * ----------------------------------------------------------------------------
 * 这是一个免费开源的软件；这意味着您可以在不用于商业目的的前提下对程序代码
 * 进行修改、使用和再发布。
 * ============================================================================
 * @author:     scott ye <scott.yell@gmail.com>
 * @version:    v2.1
 * ---------------------------------------------
 * $Author: paulgao $
 * $Date: 2007-05-28 18:05:45 +0800 (星期一, 28 五月 2007) $
 * $Id: paypal.php 8880 2007-05-28 10:05:45Z paulgao $
 */

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

$payment_lang = ROOT_PATH . 'languages/' .$GLOBALS['_CFG']['lang']. '/payment/paypal.php';

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
//    $modules[$i]['desc']    = 'paypal_desc';
//
//    /* 是否支持货到付款 */
//    $modules[$i]['is_cod']  = '0';
//
//    /* 作者 */
//    $modules[$i]['author']  = 'ECSHOP TEAM';
//
//    /* 网址 */
//    $modules[$i]['website'] = 'http://www.paypal.com';
//
//    /* 版本号 */
//    $modules[$i]['version'] = '1.0.0';
//
//    /* 配置信息 */
//    $modules[$i]['config'] = array(
//    array('name' => 'paypal_account', 'type' => 'text', 'value' => ''),
//    array('name' => 'paypal_currency', 'type' => 'select', 'value' => 'USD')
//    );

    return;
}

/**
 * 类
 */
class paypal
{
    /**
     * 构造函数
     *
     * @access  public
     * @param
     *
     * @return void
     */
    // function paypal()
    // {
    // }

    function __construct()
    {
        $this->paypal();
    }

    /**
     * 生成支付代码
     * @param   array   $order  订单信息
     * @param   array   $payment    支付方式信息
     */
    function get_code($order, $payment)
    {
        $data_order_id      = $order['log_id'];
        $data_amount        = $order['total_hk_amount'];
        $data_return_url    = RETURNURL.'?code=paypal';//$GLOBALS['ecs']->url();
        $data_pay_account   = $payment['paypal_account'];
        $hosted_button_id   = $payment['hosted_button_id']; //9425294
        $currency_code      = $payment['paypal_currency'];//HKD 港币
//        $data_notify_url    = return_url(basename(__FILE__, '.php'));
        $data_notify_url    = RESPONDURL.'?code=paypal';

        $def_url  = '<form style="text-align:center;" action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">' .   // 不能省略
//        $def_url  = '<br /><form style="text-align:center;" action="https://www.sandbox.paypal.com/cgi-bin/webscr" method="post">' .   // 不能省略
        "<input type='hidden' name='cmd' value='_xclick'>" .                             // 不能省略
        "<input type='hidden' name='business' value='{$data_pay_account}'>" .                 // 贝宝帐号
        "<input type='hidden' name='return' value='{$data_return_url}'>" .                    // 付款后页面
        "<input type='hidden' name='amount' value='{$data_amount}'>" .                        // 订单金额
        "<input type='hidden' name='invoice' value='{$data_order_id}'>" .                      // 订单号
        "<input type='hidden' name='charset' value='utf-8'>" .                              // 字符集
        "<input type='hidden' name='no_shipping' value='1'>" .                              // 不要求客户提供收货地址
        "<input type='hidden' name='no_note' value='1'>" .                                  // 付款说明
        "<input type='hidden' name='currency_code' value='{$currency_code}'>" .            // 货币
        "<input type='hidden' name='notify_url' value='{$data_notify_url}'>" .
        "<input type='hidden' name='item_name' value='{$order[order_sn]}'>" .                 // payment for
        "<input type='submit' name='submit' class='paypalButton' value='立即使用PayPal支付'>" .                      // 按钮
        "</form>";
        return $def_url;
    }

    /**
     * 响应操作
     */
    function respond()
    {
        $payment        = get_payment('paypal');
        $merchant_id    = $payment['paypal_account'];               ///获取商户编号
        $currency_code      = $payment['paypal_currency'];//HKD 港币

        // read the post from PayPal system and add 'cmd'
        $req = 'cmd=_notify-validate';
        foreach ($_POST as $key => $value)
        {
            $value = urlencode(stripslashes($value));
            $req .= "&$key=$value";
        }

        // post back to PayPal system to validate
        $header  = "POST /cgi-bin/webscr HTTP/1.0\r\n";
        $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $header .= "Content-Length: " . strlen($req) . "\r\n\r\n";

        //If testing on Sandbox use:
//        $fp = fsockopen ('ssl://www.sandbox.paypal.com', 443, $errno, $errstr, 30);
        $fp = fsockopen ('ssl://www.paypal.com', 443, $errno, $errstr, 30);

        // assign posted variables to local variables
        $payment_status = $_POST['payment_status'];
        $payment_amount = $_POST['mc_gross'];
        $payment_currency = $_POST['mc_currency'];
        $txn_id = $_POST['txn_id'];
        $receiver_email = $_POST['receiver_email'];
        $payer_email = $_POST['payer_email'];
        $order_sn = $_POST['item_name'];
        $log_id = $_POST['invoice'];
        $action_note = $txn_id . '（' . $GLOBALS['_LANG']['paypal_txn_id'] . '）' . $_POST['memo'];

        if (!$fp)
        {
            fclose($fp);

            return false;
        }
        else
        {
            fputs($fp, $header . $req);
            while (!feof($fp))
            {
                $res = fgets($fp, 1024);
                if (strcmp($res, 'VERIFIED') == 0)
                {
                    $ret = true;
                    // check the payment_status is Completed
                    if ($payment_status != 'Completed')
                    {
                        $ret = false;
                    }

                    // check that txn_id has not been previously processed
                    $sql = "SELECT COUNT(*) FROM " . $GLOBALS['ecs']->table('order_action') . " WHERE action_note LIKE '" . mysql_like_quote($txn_id) . "%'";
                    if ($ret && $GLOBALS['db']->getOne($sql) > 0)
                    {
                        $ret = false;
                    }

                    // check that receiver_email is your Primary PayPal email
                    if ($ret && $receiver_email != $merchant_id)
                    {
                        $ret = false;
                    }

                    // check that payment_amount/payment_currency are correct
                    // 目前港币保存在order_goods_attribute里，统计这里的商品金额
                    $sql = "SELECT SUM(oga.value) FROM ecs_order_info oi
                             LEFT JOIN ecs_order_goods og ON oi.order_id = og.order_id
                             LEFT JOIN order_goods_attribute oga ON oga.order_goods_id = og.rec_id
                            WHERE order_sn = '$order_sn' ";
                    // 浮点数计算，可能需要改精度:abs($GLOBALS['db']->getOne($sql) - $payment_amount) < 0.01
                    if ($ret && $GLOBALS['db']->getOne($sql) != $payment_amount)
                    {
                        $ret = false;
                    }
                    if ($ret && $payment_currency != $currency_code)
                    {
                        $ret = false;
                    }

                    // process payment
                    if ($ret) {
                        // 修改支付状态为已确认
                        order_paid($log_id, 2, $action_note);
                    } else {
                        order_paid($log_id, 1, $action_note);
                    }
                    fclose($fp);

                    return $ret;
                }
                elseif (strcmp($res, 'INVALID') == 0)
                {
                    // log for manual investigation
                    fclose($fp);

                    return false;
                }
            }
        }
    }
    
    /**
     * 异步确认
     *
     * @return unknown
     */
    function respond_asyn() {
        return $this->respond();
    }
}

?>
