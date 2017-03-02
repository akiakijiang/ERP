<?php

/**
 * OUKU 银行汇款（转帐）插件
 */

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

$payment_lang = ROOT_PATH . 'languages/' .$GLOBALS['_CFG']['lang']. '/payment/bank.php';

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
//    $modules[$i]['desc']    = 'bank_desc';
//
//    /* 是否支持货到付款 */
//    $modules[$i]['is_cod']  = '0';
//
//    /* 作者 */
//    $modules[$i]['author']  = '';
//
//    /* 网址 */
//    $modules[$i]['website'] = 'http://www.ouku.com';
//
//    /* 版本号 */
//    $modules[$i]['version'] = '1.0.0';
//
//    /* 配置信息 */
//    $modules[$i]['config']  = array();

    return;
}

/**
 * 类
 */
class bank
{
    /**
     * 构造函数
     *
     * @access  public
     * @param
     *
     * @return void
     */
    // function bank()
    // {
    // }

    function __construct()
    {
        $this->bank();
    }

    /**
     * 提交函数
     */
    function get_code($order)
    {
    	global $db, $ecs;
    	$sql = "SELECT * FROM {$ecs->table('payment')} WHERE pay_id = '{$order['pay_id']}'";
    	$payment = $db->getRow($sql);
    	return "
    	<p class='pay_desc'>".$payment['pay_desc']."</p>
    	<div class='bankTip'>为了及时准确的确认您的汇款,欧酷已随机减去部分支付金额，请您汇款时务必按照最终“<span style='color:red;'><strong>支付的费用</strong></span>”来支付。(每天的确认收款时间为09：30、12：00、15：30)</div>
    	"
    	/*
        return "请通过以下银行转帐，户名：刘琼<br><hr><table border=0 width=100% >".
                 "<tr><td style='width:50%'><strong>中国农业银行：</strong>中国农业银行上海市分行</td><td><strong>帐号：</strong>6228480030508170419</td></tr>".
                 "<tr><td style='width:50%'><strong>交通银行：</strong>交通银行上海分行</td><td><strong>帐号：</strong>6222600110011978322</td></tr>".
                 "<tr><td style='width:50%'><strong>浦发银行：</strong>上海浦东发展银行</td><td><strong>帐号：</strong>6225210106693535</td></tr>".
				 "<tr><td style='width:50%'><strong>招商银行：</strong>招商银行上海东方支行</td><td><strong>帐号：</strong>6225881210637103</td></tr>".
				 "<tr><td style='width:50%'><strong>中国银行：</strong>中国银行上海市支行</td><td><strong>帐号：</strong>4563510800029111753</td></tr>".
				 "<tr><td style='width:50%'><strong>中国工商银行：</strong>中国工商银行上海市张江支行</td><td><strong>帐号：</strong>6222021001013355471</td></tr>".
				 "<tr><td style='width:50%'><strong>中国建设银行：</strong>中国建设银行上海市分行</td><td><strong>帐号：</strong>6227001215110041882</td></tr>".				 
        	     "</table><br>        */
;

    }

    /**
     * 处理函数
     */
    function response()
    {
        return;
    }
}

?>