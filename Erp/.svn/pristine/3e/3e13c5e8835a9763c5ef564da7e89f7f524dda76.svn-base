<?php

/**
 * OUKU 网银在线插件
 */

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}
$payment_lang = ROOT_PATH . 'languages/' .$GLOBALS['_CFG']['lang']. '/payment/chinabank.php';

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
//    $modules[$i]['desc']    = 'chinabank_desc';
//
//    /* 是否支持货到付款 */
//    $modules[$i]['is_cod']  = '0';
//
//    /* 支付费用 */
//    $modules[$i]['pay_fee'] = '2.5%';
//
//    /* 作者 */
//    $modules[$i]['author']  = 'Oukoo';
//
//    /* 网址 */
//    $modules[$i]['website'] = 'http://www.chinabank.com.cn';
//
//    /* 版本号 */
//    $modules[$i]['version'] = '1.0.1';
//
//    /* 配置信息 */
//    $modules[$i]['config'] = array(
//        array('name' => 'chinabank_account', 'type' => 'text', 'value' => ''),
//        array('name' => 'chinabank_key',     'type' => 'text', 'value' => ''),
//    );

    return;
}

/**
 * 类
 */
class chinabank
{
    /**
     * 构造函数
     *
     * @access  public
     * @param
     *
     * @return void
     */
    // function chinabank()
    // {
    // }

    function __construct()
    {
        $this->chinabank();
    }

    /**
     * 生成支付代码
     * @param   array   $order      订单信息
     * @param   array   $payment    支付方式信息
     */
    function get_code($order, $payment)
    {
        $data_vid           = trim($payment['chinabank_account']);
        $data_orderid       = $order['order_sn'];
        $data_vamount       = $order['order_amount'];
        $data_vmoneytype    = 'CNY';
        $data_vpaykey       = trim($payment['chinabank_key']);
        #$data_vreturnurl    = return_url(basename(__FILE__, '.php'));
        $data_vreturnurl    = RETURNURL.'?code=chinabank';		
        
        	
        $MD5KEY =$data_vamount.$data_vmoneytype.$data_orderid.$data_vid.$data_vreturnurl.$data_vpaykey;
        $MD5KEY = strtoupper(md5($MD5KEY));

        $def_url  = '<form method=post action="https://pay3.chinabank.com.cn/PayGate" target="_blank">';
        $def_url .= "<input type=HIDDEN name='v_mid' value='".$data_vid."'>";
        $def_url .= "<input type=HIDDEN name='v_oid' value='".$data_orderid."'>";
        $def_url .= "<input type=HIDDEN name='v_amount' value='".$data_vamount."'>";
        $def_url .= "<input type=HIDDEN name='v_moneytype'  value='".$data_vmoneytype."'>";
        $def_url .= "<input type=HIDDEN name='v_url'  value='".$data_vreturnurl."'>";
        $def_url .= "<input type=HIDDEN name='v_md5info' value='".$MD5KEY."'>";
        $def_url .= "<input type=submit class='bankButton'  value='[使用网银在线支付]'>";
        $def_url .= "</form>
";
        
        global $db, $ecs;
        $ouku_request = $def_url;
        $sql = "UPDATE {$ecs->table('pay_log')} SET ouku_request = '" . mysql_escape_string($ouku_request) ."' WHERE log_id = '{$order['log_id']}'";
        $db->query($sql);
        return $def_url;
    }

    /**
     * 响应操作
     */
    function respond()
    {
        $payment        = get_payment(basename(__FILE__, '.php'));

        $v_oid          = trim($_POST['v_oid']);
        $v_pmode        = trim($_POST['v_pmode']);
        $v_pstatus      = trim($_POST['v_pstatus']);
        $v_pstring      = trim($_POST['v_pstring']);
        $v_amount       = trim($_POST['v_amount']);
        $v_moneytype    = trim($_POST['v_moneytype']);
        $remark1        = trim($_POST['remark1' ]);
        $remark2        = trim($_POST['remark2' ]);
        $v_md5str       = trim($_POST['v_md5str' ]);

        /**
         * 重新计算md5的值
         */
        $key            = $payment['chinabank_key'];

        $md5string=strtoupper(md5($v_oid.$v_pstatus.$v_amount.$v_moneytype.$key));

        /* 检查秘钥是否正确 */
        if ($v_md5str==$md5string)
        {
            if ($v_pstatus == '20')
            {
                /* 改变订单状态 */
                global $db, $ecs; //根据订单sn获得log_id
                $sql = "SELECT p.log_id FROM {$GLOBALS['ecs']->table('pay_log')} p 
                        INNER JOIN {$GLOBALS['ecs']->table('order_info')} o ON p.order_id = o.order_id
                         WHERE o.order_sn = '{$v_oid}' ORDER BY log_id DESC LIMIT 1 ";
                $log_id = $db->getOne($sql);
                order_paid($log_id);
                return true;
            }
        }
        else
        {
            return false;
        }
    }
}

?>