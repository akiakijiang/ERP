<?php

/**
 * ECSHOP 货到付款插件
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
 * $Date: 2007-05-17 13:33:17 +0800 (星期四, 17 五月 2007) $
 * $Id: cod.php 8653 2007-05-17 05:33:17Z paulgao $
 */

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

$payment_lang = ROOT_PATH . 'languages/' .$GLOBALS['_CFG']['lang']. '/payment/cod.php';

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
//    $modules[$i]['desc']    = 'cod_desc';
//
//    /* 是否支持货到付款 */
//    $modules[$i]['is_cod']  = '1';
//
//    /* 支付费用，由配送决定 */
//    $modules[$i]['pay_fee'] = '0';
//
//    /* 作者 */
//    $modules[$i]['author']  = 'ECSHOP TEAM';
//
//    /* 网址 */
//    $modules[$i]['website'] = 'http://www.ecshop.com';
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
class cod
{
    /**
     * 构造函数
     *
     * @access  public
     * @param
     *
     * @return void
     */
    // function cod()
    // {
    // }

    function __construct()
    {
        $this->cod();
    }

    /**
     * 提交函数
     */
    function get_code()
    {
        return '
        <p class="codTip">请在收货时向送货员支付您的订单款项，祝您购物愉快！</p>

';
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