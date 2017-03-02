<?php

/**
 * ECSHOP 支付宝语言文件
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
 * $Author: weberliu $
 * $Date: 2007-05-15 18:47:51 +0800 (星期二, 15 五月 2007) $
 * $Id: alipay.php 8624 2007-05-15 10:47:51Z weberliu $
 */

define('RETURNURL','http://www.ouku.com/ouku_return.php');
define('CHINA','zh_cn');
define('ENGLISH','en_us');
define('JAPAN','zh_jp');

global $_LANG;

$_LANG['alipay'] = '支付宝';
$_LANG['alipay_desc'] = '支付宝，是支付宝公司针对网上交易而特别推出的安全付款服务，其运作的实质是' .
        '以支付宝为信用中介，在买家确认收到商品前，由支付宝替买卖双方暂时保管货款的一种增值服务。' .
        '（网址：http://www.alipay.com）';
$_LANG['alipay_account'] = '支付宝帐户';
$_LANG['alipay_key'] = '交易安全校验码';
$_LANG['alipay_partner'] = '合作者身份ID';
$_LANG['pay_button'] = '立即使用支付宝支付';
$_LANG['alipay_direct']     = '是否银行直连';
$_LANG['alipay_direct_range'][1]     = '是';
$_LANG['alipay_direct_range'][0]     = '否';
$_LANG['alipay_credit']     = '是否信用卡';
$_LANG['alipay_credit_range'][1]     = '是';
$_LANG['alipay_credit_range'][0]     = '否';
$_LANG['alipay_bankId']     = '银行代码';
$_LANG['alipay_bankname']     = '银行名称';

$_LANG['alipay_virtual_method'] = '选择虚拟商品接口';
$_LANG['alipay_virtual_method_desc'] = '您可以选择支付时采用的接口类型，不过这和支付宝的帐号类型有关，具体情况请咨询支付宝';
$_LANG['alipay_virtual_method_range'][0] = '使用普通虚拟商品交易接口';
$_LANG['alipay_virtual_method_range'][1] = '使用即时到帐交易接口';

$_LANG['alipay_real_method'] = '选择实体商品接口';
$_LANG['alipay_real_method_desc'] = '您可以选择支付时采用的接口类型，不过这和支付宝的帐号类型有关，具体情况请咨询支付宝';
$_LANG['alipay_real_method_range'][0] = '使用普通实物商品交易接口';
$_LANG['alipay_real_method_range'][1] = '使用即时到帐交易接口';

?>