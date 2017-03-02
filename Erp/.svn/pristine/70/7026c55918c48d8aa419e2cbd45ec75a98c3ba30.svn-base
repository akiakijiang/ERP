<?php

/**
 * ECSHOP 顺丰速运 配送方式插件
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
 * $Id: sf_express.php 8653 2007-05-17 05:33:17Z paulgao $
 */

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

$shipping_lang = ROOT_PATH.'languages/' .$GLOBALS['_CFG']['lang']. '/shipping/sf_express.php';

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
    $modules[$i]['desc']    = 'sf_express_desc';

    /* 配送方式是否支持货到付款 */
    $modules[$i]['cod']     = false;

    /* 插件的作者 */
    $modules[$i]['author']  = 'ECSHOP TEAM';

    /* 插件作者的官方网站 */
    $modules[$i]['website'] = 'http://www.ecshop.com';

    /* 配送接口需要的参数 */
    $modules[$i]['configure'] = array(
                                    array('name' => 'basic_fee',    'value'=>15), /* 1000克以内的价格           */
                                    array('name' => 'step_fee',     'value'=>2),  /* 续重每1000克增加的价格 */
                                );

    return;
}

/**
 * 顺丰速运费用计算方式: 起点到终点 * 重量(kg)
 * ====================================================================================
 * -浙江，上海，江苏地区为15元/公斤，续重(2元/公斤)
 * -续重每500克或其零数 (具体请上顺丰速运网站查询:http://www.sf-express.com/sfwebapp/price.jsp 客服电话 4008111111)
 *
 * -------------------------------------------------------------------------------------
 */

class sf_express
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
    function sf_express($cfg=array())
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
           // $_SESSION['cart_weight'] = $goods_weight;
            return $fee;
        }
    }

    /**
     * 查询快递状态
     *
     * @access  public
     * @return  string  查询窗口的链接地址
     */
    function query($invoice_sn)
    {
        $form_str = '<a href="http://www.sf-express.com/sfwebapp/track.jsp?tracklist='.$invoice_sn.'" target="_blank">' .$invoice_sn. '</a>';

        return $form_str;
    }
}

?>