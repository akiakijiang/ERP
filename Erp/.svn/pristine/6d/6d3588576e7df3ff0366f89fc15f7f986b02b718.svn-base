<?php

/**
 * ECSHOP 申通快递 配送方式插件
 * ============================================================================
 * 版权所有 (C) 2005-2007 北京亿商互动科技发展有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com
 * ----------------------------------------------------------------------------
 * 这是一个免费开源的软件；这意味着您可以在不用于商业目的的前提下对程序代码
 * 进行修改、使用和再发布。
 * ============================================================================
 * @author:     liry <liry123@hotmail.com>
 * @version:    v2.1
 * ---------------------------------------------
 * $Author: paulgao $
 * $Date: 2007-05-17 13:33:17 +0800 (星期四, 17 五月 2007) $
 * $Id: sto_express.php 8653 2007-05-17 05:33:17Z paulgao $
 */

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

$shipping_lang = ROOT_PATH.'languages/' .$GLOBALS['_CFG']['lang']. '/shipping/sto_express.php';
if (file_exists($shipping_lang))
{
    global $_LANG;
    include_once($shipping_lang);
}

/* 模块的基本信息 */
if (isset($set_modules) && $set_modules == TRUE)
{
    $i = (isset($modules)) ? count($modules) : 0;

    /* 配送方式插件的代码必须和文件名保持一致 */
    $modules[$i]['code']    = basename(__FILE__, '.php');

    $modules[$i]['version'] = '1.0.0';

    /* 配送方式的描述 */
    $modules[$i]['desc']    = 'sto_express_desc';

    /* 配送方式是否支持货到付款 */
    $modules[$i]['cod']     = false;

    /* 插件的作者 */
    $modules[$i]['author']  = 'ECSHOP TEAM';

    /* 插件作者的官方网站 */
    $modules[$i]['website'] = 'http://www.ecshop.com';

    /* 配送接口需要的参数 */
    $modules[$i]['configure'] = array(
                                    array('name' => 'basic_fee',    'value'=>15), /* 1000克以内的价格           */
                                    array('name' => 'step_fee',     'value'=>5),  /* 续重每1000克增加的价格 */
                                );

    return;
}

/**
 * 申通快递费用计算方式:
 * ====================================================================================
 * - 江浙沪地区统一资费： 1公斤以内15元， 每增加1公斤加5-6元, 云南为8元
 * - 其他地区统一资费:    1公斤以内18元， 每增加1公斤加5-6元, 云南为8元
 * - 对于体大质轻的包裹，我们将按照航空运输协会的规定，根据体积和实际重量中较重的一种收费，需将包的长、宽、高、相乘，再除以6000
 * - (具体资费请上此网站查询:http://www.car365.cn/fee.asp 客服电话:021-52238886)
 * -------------------------------------------------------------------------------------
 *
 */
class sto_express
{
    /*------------------------------------------------------ */
    //-- PUBLIC ATTRIBUTEs
    /*------------------------------------------------------ */

    /**
     * 配置信息参数
     */
    var $configure;

    /*------------------------------------------------------ */
    //-- PUBLIC METHODs
    /*------------------------------------------------------ */

    /**
     * 构造函数
     *
     * @param: $configure[array]    配送方式的参数的数组
     *
     * @return null
     */
    function sto_express($cfg=array())
    {
        foreach ($cfg AS $key=>$val)
        {
            $this->configure[$val['name']] = $val['value'];
        }
    }

    /**
     * 计算订单的配送费用的函数
     *
     * @param   float   $goods_weight   商品重量
     * @param   float   $goods_amount   商品金额
     * @return  decimal
     */
    function calculate($goods_weight, $goods_amount)
    {
        if ($this->configure['free_money'] > 0 && $goods_amount >= $this->configure['free_money'])
        {
            return 0;
        }
        else
        {
            @$fee = $this->configure['basic_fee'];

            if ($goods_weight > 1)
            {
                $fee += (ceil(($goods_weight - 1))) * $this->configure['step_fee'];
            }
            return $fee;
        }
    }

    /**
     * 查询快递状态
     *
     * @access  public
     * @param   string  $invoice_sn     发货单号
     * @return  string  查询窗口的链接地址
     */
    function query($invoice_sn)
    {
        $form_str = '<a href="http://www.sto.cn/querybill/webform1.aspx?wen=' .$invoice_sn. '" target="_blank">' .$invoice_sn. '</a>';
        return $form_str;
    }
}

?>