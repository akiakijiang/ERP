<?php

/**
 * OUKU 便利通插件
 * www.chblt.com
 */

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

$payment_lang = ROOT_PATH . 'languages/' .$GLOBALS['_CFG']['lang']. '/payment/chblt.php';

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
class chblt
{

    /**
     * 构造函数
     *
     * @access  public
     * @param
     *
     * @return void
     */
    // function chblt()
    // {
    // }

    function __construct()
    {
        $this->chblt();
    }

    /**
     * 生成支付代码
     * @param   array   $order      订单信息
     * @param   array   $payment    支付方式信息
     */
    function get_code($order, $payment)
    {
        switch(PARTY_ID){
            case 1:
                $title = '欧酷商城订单';
                break;
            case 4:
                $title = '欧酷派商城订单';
                break;
            default:
                $title = '欧酷商城订单';
            break; 
        }

        $parameter = array(
            'ServiceType'   => '1',  // 1 直接扣款 0 担保交易
            'MerchantId'    => $payment['chblt_MerchantId'], // 商户号
            'OrderId'       => $order['order_sn'], //订单号
            'Title'         => $title, // 订单标题 
            'Description'   => $title, // 订单描述信息
            'Amount'        => $order['order_amount'], //订单金额
            'CallbackUrl'   => RESPONDURL.'?code=chblt',
            'ReturnUrl'     => RETURNURL.'?code=chblt',
            'TerminalId'    => $payment['chblt_TerminalId'],
            'Version'       => '1.0'
        );
        $SignatureKey  = $payment['chblt_SignatureKey'];
        $SignatureType = "0"; // 签名的类型 0 MD5,1 RSA, 2 DSA
        $query = '';
        foreach ($parameter AS $k => $v) {
            $query .= '&'.$k.'='.$this->upperurlencode($v);
        }
        $query = substr($query, 1);
        $sign = strtoupper(md5($query.$SignatureKey));
        
        $getway = 'https://pay.chblt.com/payment/web.server/bltpayment.aspx';
        //$getway = 'http://pay.chblt.com/test/payment/web.server/bltpayment.aspx'; //测试地址

        $html_input = '';

        foreach ($parameter AS $k => $v) {
            $html_input .= "<input type='hidden' name='{$k}' value='{$v}' />";    
        }

        $html = "<form method='get' action='{$getway}' target='_blank'>".
            $html_input.
            "<input type='hidden' name='Memo' value='' />". //Memo 订单备注
            "<input type='hidden' name='SignatureType' value='{$SignatureType}' />".
            "<input type='hidden' name='Signature' value='{$sign}' />".
            "<input type='submit' value='立即使用便利通支付' class='chblt'/>".
            "</form>"; 

        global $db, $ecs;

        $ouku_request = $html;
        $sql = "UPDATE {$ecs->table('pay_log')} SET ouku_request = '" . mysql_escape_string($ouku_request) .
                "' WHERE log_id = '{$order['log_id']}'";

        $db->query($sql);

        return $html;
    }

    /**
     * 响应操作
     */
    function respond()
    {
        $payment        = get_payment(basename(__FILE__, '.php'));

        $parameter = array(
            'CallbackId'    => trim($_REQUEST["CallbackId"]),
            'MerchantId'    => trim($_REQUEST["MerchantId"]),
            'SequenceId'    => trim($_REQUEST["SequenceId"]),  
            'OrderId'       => trim($_REQUEST["OrderId"]),
            'Amount'        => trim($_REQUEST["Amount"]), 
            'Status'        => trim($_REQUEST["Status"]), // 00 支付成功; 其他返回码: 支付失败   
            'Description'   => trim($_REQUEST["Description"]),
            'Version'       => trim($_REQUEST["Version"]),
        );

        $SignatureType = trim($_REQUEST["SignatureType"]);
        $Signature = trim($_REQUEST["Signature"]);

        $query = '';
        foreach ($parameter AS $k => $v) {
            $query .= '&'.$k.'='.$this->upperurlencode($v);
        }
        $query = substr($query, 1);
        $query .= $payment['chblt_SignatureKey'];

        $Signature2 = strtoupper(md5($query));
        
        /* 检查秘钥是否正确 */
        if ($Signature == $Signature2)
        {
            global $db, $ecs; //根据订单sn获得log_id

            $order_sn = $parameter['OrderId'];

            $sql = "SELECT p.log_id, o.pay_status FROM {$GLOBALS['ecs']->table('pay_log')} p ". 
                   " INNER JOIN {$GLOBALS['ecs']->table('order_info')} o ON p.order_id = o.order_id ".
                   " WHERE o.order_sn = '{$order_sn}' ORDER BY log_id DESC LIMIT 1 ";

            $log = $db->getRow($sql);
            
            /* 检查支付的金额是否相符 */
            if (!check_money($log['log_id'], $parameter['Amount']))
            {
                return false;
            }
            /* 改变订单状态 */
            if ($parameter['Status'] == '00') {
                if ($log['pay_status'] != 2) {
                    order_paid($log['log_id']);
                }
                return true;
            }
        }
        return false;
    }

    /**
     * ncchen 090323
     * 异步响应操作
     */
    function respond_asyn()
    {
        $payment        = get_payment(basename(__FILE__, '.php'));
        
        $parameter = array(
            'CallbackId'    => trim($_REQUEST["CallbackId"]),
            'MerchantId'    => trim($_REQUEST["MerchantId"]),
            'SequenceId'    => trim($_REQUEST["SequenceId"]),  
            'OrderId'       => trim($_REQUEST["OrderId"]),
            'Amount'        => trim($_REQUEST["Amount"]), 
            'Status'        => trim($_REQUEST["Status"]), // 00 支付成功; 其他返回码: 支付失败   
            'Description'   => trim($_REQUEST["Description"]),
            'Version'       => trim($_REQUEST["Version"]),
        );

        $SignatureType = trim($_REQUEST["SignatureType"]);
        $Signature = trim($_REQUEST["Signature"]);

        $query = '';
        foreach ($parameter AS $k => $v) {
            $query .= '&'.$k.'='.$this->upperurlencode($v);
        }
        $query = substr($query, 1);
        $query .= $payment['chblt_SignatureKey'];

        $Signature2 = strtoupper(md5($query));
        
        /* 检查秘钥是否正确 */
        if ($Signature == $Signature2)
        {
            global $db, $ecs; //根据订单sn获得log_id

            $order_sn = $parameter['OrderId'];
            $sql = "SELECT p.log_id, o.pay_status FROM {$GLOBALS['ecs']->table('pay_log')} p ". 
                " INNER JOIN {$GLOBALS['ecs']->table('order_info')} o ON p.order_id = o.order_id ".
                " WHERE o.order_sn = '{$order_sn}' ORDER BY log_id DESC LIMIT 1 ";
            $log = $db->getRow($sql);

            /* 检查支付的金额是否相符 */
            if (!check_money($log['log_id'], $parameter['Amount']))
            {
                return false;
            }
            /* 改变订单状态 */
            if ($parameter['Status'] == '00') {
                if ($log['pay_status'] != 2) {
                    order_paid($log['log_id']);
                }
                return true;
            }
        }
        return false;
    }

    function upperurlencode($str)
    {
        $str = urlencode($str);
        $str = str_replace("'", "%27", $str);
        $str = str_replace("(", "%28", $str);
        $str = str_replace(")", "%29", $str);
        $str = str_replace("*", "%2A", $str);
        $str = str_replace("-", "%2D", $str);
        $str = str_replace(".", "%2E", $str);
        $str = str_replace("_", "%5F", $str);
        $str = str_replace("!", "%21", $str);
        return strtoupper($str);
    }

}

?>
