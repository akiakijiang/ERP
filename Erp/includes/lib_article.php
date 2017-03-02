<?php

/**
 * ECSHOP 文章及文章分类相关函数库
 * ============================================================================
 * 版权所有 (C) 2005-2007 北京亿商互动科技发展有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com
 * ----------------------------------------------------------------------------
 * 这是一个免费开源的软件；这意味着您可以在不用于商业目的的前提下对程序代码
 * 进行修改、使用和再发布。
 * ============================================================================
 * @author:     Weber Liu <weberliu@hotmail.com>
 * @version:    v2.1
 * ---------------------------------------------
 * $Author: paulgao $
 * $Date: 2007-05-17 11:44:15 +0800 (星期四, 17 五月 2007) $
 * $Id: lib_article.php 8651 2007-05-17 03:44:15Z paulgao $
*/

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

/**
 * 获得文章分类下的文章列表
 *
 * @access  public
 * @param   integer     $cat_id
 * @param   integer     $page
 * @param   integer     $size
 *
 * @return  array
 */
function get_cat_articles($cat_id, $page = 1, $size = 20)
{
    $sql = 'SELECT article_id, title, author, add_time, file_url, open_type' .
           ' FROM ' .$GLOBALS['ecs']->table('article') .
           " WHERE is_open = 1 AND cat_id = '$cat_id'" .
           ' ORDER BY article_id DESC';
    $res = $GLOBALS['db']->selectLimit($sql, $size, ($page-1) * $size);

    $arr = array();
    if ($res)
    {
        while ($row = $GLOBALS['db']->fetchRow($res))
        {
            $article_id = $row['article_id'];

            $arr[$article_id]['id']          = $article_id;
            $arr[$article_id]['title']       = $row['title'];
            $arr[$article_id]['short_title'] = $GLOBALS['_CFG']['article_title_length'] > 0 ? sub_str($row['title'], 0, $GLOBALS['_CFG']['article_title_length']) : $row['title'];
            $arr[$article_id]['author']      = empty($row['author']) || $row['author'] == '_SHOPHELP' ? $GLOBALS['_CFG']['shop_name'] : $row['author'];
            $arr[$article_id]['url']         = $row['open_type'] != 1 ? build_uri('article', array('aid'=>$article_id), $row['title']) : trim($row['file_url']);
            $arr[$article_id]['add_time']    = date($GLOBALS['_CFG']['date_format'], $row['add_time']);
        }
    }

    return $arr;
}

?>