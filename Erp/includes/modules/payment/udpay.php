<?php

/**
 * OUKU 网银在线插件
 */

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}
$payment_lang = ROOT_PATH . 'languages/' .$GLOBALS['_CFG']['lang']. '/payment/udpay.php';

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

    return;
}

/**
 * 生成签名
 *
 * @param unknown_type $message
 * @param unknown_type $exponent
 * @param unknown_type $modulus
 * @return unknown
 */
function generate_sigature($message, $exponent, $modulus) {
    $md5Message = md5($message);
    $fillStr = "01fffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff".
        "ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff". 
        "fffffffffffffffffffffffffff003020300c06082a864886f70d020505000410";
    $md5Message = $fillStr.$md5Message;
    $intMessage = bin2int(hex2bin($md5Message));
    $intE = bin2int(hex2bin($exponent));
    $intM = bin2int(hex2bin($modulus));
    $intResult = powmod($intMessage, $intE, $intM);
    $hexResult = bin2hex(int2bin($intResult));
    return $hexResult;
}

/**
 * 验证签名
 *
 * @param unknown_type $message
 * @param unknown_type $sign
 * @param unknown_type $exponent
 * @param unknown_type $modulus
 * @return unknown
 */
function verify_sigature($message, $sign, $exponent, $modulus) {
    $intSign = bin2int(hex2bin($sign));
    $intExponent = bin2int(hex2bin($exponent));
    $intModulus = bin2int(hex2bin($modulus));
    $intResult = powmod($intSign, $intExponent, $intModulus);
    $hexResult = bin2hex(int2bin($intResult));
    $md5Message = md5($message);
    if ($md5Message == substr($hexResult, -32)) {
        return "1";
    } else return "0";
}
if (version_compare(PHP_VERSION, '5.4.0', '<')) {
function hex2bin($hexdata) {
    for ($i=0;$i<strlen($hexdata);$i+=2) {
        $bindata=chr(hexdec(substr($hexdata,$i,2))).$bindata;
    }
    return $bindata;
}
}

function bin2int($str)
{
    $result = '0';
    $n = strlen($str);
    do {
        $result = bcadd(bcmul($result, '256'), ord($str{--$n}));
    } while ($n > 0);
    return $result;
}

function int2bin($num)
{
    $result = '';
    do {
        $result= chr(bcmod($num, '256')).$result;
        $num = bcdiv($num, '256');
    } while (bccomp($num, '0'));
    return $result;
}

function powmod($num, $pow, $mod){
    $result = '1';
    do {
        if (!bccomp(bcmod($pow, '2'), '1')) {
            $result = bcmod(bcmul($result, $num), $mod);
        }
        $num = bcmod(bcpow($num, '2'), $mod);
        $pow = bcdiv($pow, '2');
    } while (bccomp($pow, '0'));
    return $result;
}

/**
 * 网汇通类
 */
class udpay
{
    /**
     * 构造函数
     *
     * @access  public
     * @param
     *
     * @return void
     */
    // function udpay()
    // {
    // }

    function __construct()
    {
        $this->udpay();
    }

    /**
     * 生成支付代码
     * @param   array   $order      订单信息
     * @param   array   $payment    支付方式信息
     */
    function get_code($order, $payment)
    {
        //以下顺序固定，不能调换，原始串顺序为：
        //交易代码+商户代码+交易日期+交易流水号+订单号+支付币别+支付金额+订单信息+附加信息+
        //返回URL+接口模式
        $parameter = array(

        //交易代码，固定值：TP001
        'txCode' => "TP001",

        //商户代码
        //网汇通注册商户号；测试用：10075；“*”表示长度可变，最大为20位
        'merchantId' => $payment['account'],

        //商户系统的交易日期，格式：20090824
        'transDate' => date('Ymd'),

        //商户系统的交易流水号，对账用，非必填。
        'transFlow' => $order['order_sn'],

        //商户系统的订单号
        'orderId' => $order['order_sn'],

        //支付币别，固定值：156（人民币）
        'curCode' => "156",

        //金额以分单位，100代表1元
        'amount' => $order['order_amount'] * 100,

        //关于订单的说明，非必填
        'orderInfo' => 'ouku',

        //订单附加信息，非必填
        'comment' => 'ouku',

        //接受支付结果的页面地址。必须是绝对地址。
        'merURL' => RETURNURL.'?code=udpay',
        //程序返回结果为为事先与对方定义
        //'bgUrl'  => RESPONDURL.'?code=udpay',

        //接口类型 ：5 表示 页面返回+程序返回
        'interfaceType' => 5,

        );

        $msg_list = array();
        foreach ($parameter AS $key => $val)
        {
            if ($val != '') {
                $param .= "<input type=\"hidden\" name=\"{$key}\" value=\"" .urlencode($val). "\" />";
                $msg_list[] = "{$key}={$val}";
            }
        }
        $msg = join('&', $msg_list);
        $sign = generate_sigature($msg, $payment['private_exponent'], 
            $payment['private_modulus']);
        $class = 'udpay';
        $def_url = "<form name=\"udpay\" method=\"post\" action=\"{$payment['pay_url']}\">
            $param
            <input type=\"hidden\" name=\"sign\" value=\"" .urlencode($sign). "\" />
            <input type=\"submit\" name=\"submit\" value=\"立即使用网汇通支付\" 
                class='{$class}'>
            </form>";
        global $db, $ecs;
        $ouku_request = $def_url;
        $sql = "UPDATE {$ecs->table('pay_log')} SET ouku_request = '" . 
            mysql_escape_string($ouku_request) ."' WHERE log_id = '{$order['log_id']}'";
        $db->query($sql);
        return $def_url;
    }

    /**
     * 响应操作
     */
    function respond()
    {
        $payment = get_payment(basename(__FILE__, '.php'));

        $parameter_array = array('txCode', 'merchantId', 'transDate', 'transFlow', 'orderId',
            'curCode', 'amount', 'orderInfo', 'comment', 'whtFlow', 'success', 'errorType');
        $msg_list = array();
        foreach ($parameter_array as $par) {
            if($_GET[$par]) {
                $msg_list[] = "$par=".$_GET[$par];
            }
        }
        $msg = join('&', $msg_list);

        /* 检查秘钥是否正确 */
        $verify_sigature = verify_sigature($msg, $_GET['sign'], $payment['public_exponent'],
            $payment['public_modulus']);
        if ($_GET['success'] == 'Y' && $verify_sigature == '1') {
            global $db, $ecs; //根据订单sn获得log_id

            $order_sn = $_GET['orderId'];
            $sql = "SELECT p.log_id, o.pay_status FROM {$GLOBALS['ecs']->table('pay_log')} p ".
            " INNER JOIN {$GLOBALS['ecs']->table('order_info')} o ON p.order_id = o.order_id ".
            " WHERE o.order_sn = '{$order_sn}' ORDER BY log_id DESC LIMIT 1 ";
            $log = $db->getRow($sql);
            /* 检查支付的金额是否相符 */
            if (!check_money($log['log_id'], $_GET['amount'] / 100)) {
                return false;
            }
            /* 改变订单状态 */
            if ($log['pay_status'] != 2) {
                order_paid($log['log_id']);
            }
            return true;
        }
        return false;
    }

    /**
     * ncchen 090323
     * 异步响应操作
     */
    function respond_asyn()
    {
        $payment = get_payment(basename(__FILE__, '.php'));

        $parameter_array = array('txCode', 'merchantId', 'transDate', 'transFlow', 'orderId',
            'curCode', 'amount', 'orderInfo', 'comment', 'whtFlow', 'success', 'errorType');
        $msg_list = array();
        foreach ($parameter_array as $par) {
            if($_POST[$par]) {
                $msg_list[] = "$par=".$_POST[$par];
            }
        }
        $msg = join('&', $msg_list);

        /* 检查秘钥是否正确 */
        $verify_sigature = verify_sigature($msg, $_POST['sign'], $payment['public_exponent'],
            $payment['public_modulus']);
        if ($_POST['success'] == 'Y' && $verify_sigature == '1') {
            global $db, $ecs; //根据订单sn获得log_id

            $order_sn = $_POST['orderId'];
            $sql = "SELECT p.log_id, o.pay_status FROM {$GLOBALS['ecs']->table('pay_log')} p ".
            " INNER JOIN {$GLOBALS['ecs']->table('order_info')} o ON p.order_id = o.order_id ".
            " WHERE o.order_sn = '{$order_sn}' ORDER BY log_id DESC LIMIT 1 ";
            $log = $db->getRow($sql);
            /* 检查支付的金额是否相符 */
            if (!check_money($log['log_id'], $_POST['amount'] / 100)) {
                return false;
            }
            /* 改变订单状态 */
            if ($log['pay_status'] != 2) {
                order_paid($log['log_id']);
            }
            return true;
        }
        return false;
    }
}

?>