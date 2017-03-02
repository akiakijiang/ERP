<?php

/**
 * ECSHOP 加密解密类
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
 * $Date: 2007-04-12 20:41:30 +0800 (星期四, 12 四月 2007) $
 * $Id: lib_code.php 8134 2007-04-12 12:41:30Z weberliu $
 */

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

/**
 * 定义当前使用的加密串
 */
define('AUTH_KEY', 'this is a key');

/**
 * 定义更改之前使用的加密串，如果没有必要，请保持为空值
 */
define('OLD_AUTH_KEY', '');

/**
 * 加密函数
 * @param   string  $str    加密前的字符串
 * @param   string  $key    密钥
 * @return  string  加密后的字符串
 */
function encrypt($str, $key = AUTH_KEY)
{
    $coded = '';
    $keylength = strlen($key);

    for ($i = 0, $count = strlen($str); $i < $count; $i += $keylength)
    {
        $coded .= substr($str, $i, $keylength) ^ $key;
    }

    return str_replace('=', '', base64_encode($coded));
}

/**
 * 解密函数
 * @param   string  $str    加密后的字符串
 * @param   string  $key    密钥
 * @return  string  加密前的字符串
 */
function decrypt($str, $key = AUTH_KEY)
{
    $coded = '';
    $keylength = strlen($key);
    $str = base64_decode($str);

    for ($i = 0, $count = strlen($str); $i < $count; $i += $keylength)
    {
        $coded .= substr($str, $i, $keylength) ^ $key;
    }

    return $coded;
}

?>