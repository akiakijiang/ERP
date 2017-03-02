<?php

/*
 * ECSHOP 模版文件编译前预处理
 * ============================================================================
 * 版权所有 (C) 2005-2007 北京亿商互动科技发展有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com
 * ----------------------------------------------------------------------------
 * 这是一个免费开源的软件；这意味着您可以在不用于商业目的的前提下对程序代码
 * 进行修改、使用和再发布。
 * ============================================================================
 * @author:     wj                 <wjzhangq@126.com>
 * @version:    v2.1
 * ---------------------------------------------
 * $Author: paulgao $
 * $Date: 2007-04-19 18:11:35 +0800 (星期四, 19 四月 2007) $
 * $Id: prefilter.preCompile.php 8272 2007-04-19 10:11:35Z paulgao $
*/

function smarty_prefilter_preCompile($source, &$smarty)
{
    $file_type = strtolower(strrchr($smarty->_current_file, '.'));
    $tmp_dir   = 'themes/' . $GLOBALS['_CFG']['template'] . '/'; // 模板所在路径

    /**
     * 处理模板文件
     */
    if ($file_type == '.dwt')
    {
        /* 将模板中所有library替换为链接 */
        $pattern     = '/<!--\s#BeginLibraryItem\s\"\/(.*?)\"\s-->.*?<!--\s#EndLibraryItem\s-->/se';
        $replacement = "'{include file='.strtolower('\\1'). '}'";
        $source      = preg_replace($pattern, $replacement, $source);

        /* 检查有无动态库文件，如果有为其赋值 */
        $dyna_libs = get_dyna_libs($GLOBALS['_CFG']['template'], $smarty->_current_file);
        if ($dyna_libs)
        {
            foreach ($dyna_libs AS $region => $libs)
            {
                $pattern = '/<!--\\s*TemplateBeginEditable\\sname="'. $region .'"\\s*-->(.*?)<!--\\s*TemplateEndEditable\\s*-->/s';

                if (preg_match($pattern, $source, $reg_match))
                {
                    $reg_content = $reg_match[1];
                    /* 生成匹配字串 */
                    $keys = array_keys($libs);
                    $lib_pattern = '';
                    foreach($keys AS $lib)
                    {
                        $lib_pattern .= '|' . str_replace('/', '\/', substr($lib, 1));
                    }
                    $lib_pattern = '/{include\sfile=(' . substr($lib_pattern, 1) . ')}/';
                    /* 修改$reg_content中的内容 */
                    $GLOBALS['libs'] = $libs;
                    $reg_content = preg_replace_callback($lib_pattern, 'dyna_libs_replace', $reg_content);

                    /* 用修改过的内容替换原来当前区域中内容 */
                    $source = preg_replace($pattern, $reg_content, $source);
                }
            }
        }

        /* 在头部加入版本信息 */
        $source = preg_replace('/<head>/i', "<head>\r\n<meta name=\"Generator\" content=\"" . APPNAME .' ' . VERSION . "\" />",  $source);

        /* 修正css路径 */
        $source = preg_replace('/(<link\shref=["|\'])(?:\.\/|\.\.\/)?(css\/)?([a-z0-9A-Z_]+\.css["|\']\srel=["|\']stylesheet["|\']\stype=["|\']text\/css["|\'])/i','\1' . $tmp_dir . '\2\3', $source);

        /* 修正js目录下js的路径 */
        $source = preg_replace('/(<script\s(?:type|language)=["|\']text\/javascript["|\']\ssrc=["|\'])(?:\.\/|\.\.\/)?(js\/[a-z0-9A-Z_]+\.(?:js|vbs)["|\']><\/script>)/', '\1' . $tmp_dir . '\2', $source);

        /* 修正模板中对images目录下的链接 */
        $source = preg_replace('/((?:background|src)\s*=\s*["|\'])(?:\.\/|\.\.\/)?(images\/.*?["|\'])/is', '\1' . $tmp_dir . '\2', $source);
        $source = preg_replace('/((?:background|background-image):\s*?url\()(?:\.\/|\.\.\/)?(images\/)/is', '\1' . $tmp_dir . '\2', $source);

        /* 替换相对链接 */
        $source = preg_replace('/(href=["|\'])\.\.\/(.*?)(["|\'])/i', '\1\2\3', $source);
    }

    /**
     * 处理库文件
     */
     elseif ($file_type == '.lbi')
     {
        /* 去除meta */
        $pattern = '/<meta\shttp-equiv=["|\']Content-Type["|\']\scontent=["|\']text\/html;\scharset=(?:.*?)["|\']>\r?\n?/i';
        $source  = preg_replace($pattern, '', $source);

        /* 替换路径 */

        /* 在images前加上 $tmp_dir */
        $pattern = '/((?:background|src)\s*=\s*["|\'])(?:\.\/|\.\.\/)?(images\/.*?["|\'])/is';
        $source  = preg_replace($pattern, '\1' . $tmp_dir . '\2', $source);

        $pattern = '/((?:background|background-image):\s*?url\()(?:\.\/|\.\.\/)?(images\/)/is';
        $source  = preg_replace($pattern, '\1' . $tmp_dir . '\2', $source);

        /* 替换相对链接 */
        $pattern = '/(href=["|\'])\.\.\/(.*?)(["|\'])/i';
        $source = preg_replace($pattern, '\1\2\3', $source);
     }

    /* 替换文件编码头部 */
    if (strpos($source, "\xEF\xBB\xBF") !== FALSE)
    {
        $source = str_replace("\xEF\xBB\xBF", '', $source);
    }

    /* 替换smarty注释 */
    $pattern = '/<!--[^>|\n]*?({.+?})[^<|{|\n]*?-->/';
    $source  = preg_replace($pattern, '\1', $source);

    /* 替换不换行的html注释 */
    $pattern = '/<!--[^<|>|{|\n]*?-->/';
    $source  = preg_replace($pattern, '', $source);

    return $source;
}

?>