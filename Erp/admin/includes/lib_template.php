<?php

/**
 * ECSHOP 管理中心模版相关公用函数库
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
 * $Date: 2007-04-19 18:11:35 +0800 (星期四, 19 四月 2007) $
 * $Id: lib_template.php 8272 2007-04-19 10:11:35Z paulgao $
*/

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

$template_files = array('index.dwt',
                        'article.dwt',
                        'article_cat.dwt',
                        'brand.dwt',
                        'catalog.dwt',
                        'category.dwt',
                        'user_clips.dwt',
                        'compare.dwt',
                        'gallery.dwt',
                        'goods.dwt',
                        'group_buy_goods.dwt',
                        'group_buy_flow.dwt',
                        'group_buy_list.dwt',
                        'user_passport.dwt',
                        'pick_out.dwt',
                        'receive.dwt',
                        'respond.dwt',
                        'search.dwt',
                        'flow.dwt',
                        'snatch.dwt',
                        'user.dwt',
                        'tag_cloud.dwt',
                        'user_transaction.dwt',
                        'style.css'
                        );

/* 每个页面允许包含的库项目 */
$page_libs      = array('article'       => array('/library/ur_here.lbi',
                                                '/library/search_form.lbi',
                                                '/library/member.lbi',
                                                '/library/recommend_best.lbi',
                                                '/library/recommend_hot.lbi',
                                                '/library/comments.lbi',
                                                '/library/goods_related.lbi',
                                                '/library/recommend_promotion.lbi',
                                                '/library/history.lbi',
                                                ),
                        'article_cat'   => array('/library/ur_here.lbi',
                                                '/library/search_form.lbi',
                                                '/library/member.lbi',
                                                '/library/category_tree.lbi',
                                                '/library/top10.lbi',
                                                '/library/history.lbi',
                                                '/library/recommend_best.lbi',
                                                '/library/recommend_hot.lbi',
                                                '/library/recommend_promotion.lbi',
                                                '/library/cart.lbi',
                                                '/library/vote.lbi'),
                         'brand'        => array(
                                                '/library/ur_here.lbi',
                                                '/library/search_form.lbi',
                                                '/library/member.lbi',
                                                '/library/category_tree.lbi',
                                                '/library/top10.lbi',
                                                '/library/history.lbi',
                                                '/library/recommend_best.lbi',
                                                '/library/goods_list.lbi',
                                                '/library/pages.lbi',
                                                '/library/recommend_promotion.lbi',
                                                '/library/cart.lbi',
                                                '/library/vote.lbi'
                                            ),
                        'catalog'         => array('/library/ur_here.lbi',
                                                '/library/search_form.lbi',
                                            ),
                        'category'      => array('/library/ur_here.lbi',
                                                '/library/search_form.lbi',
                                                '/library/member.lbi',
                                                '/library/category_tree.lbi',
                                                '/library/top10.lbi',
                                                '/library/history.lbi',
                                                '/library/recommend_best.lbi',
                                                '/library/recommend_hot.lbi',
                                                '/library/goods_list.lbi',
                                                '/library/pages.lbi',
                                                '/library/recommend_promotion.lbi',
                                                '/library/brands.lbi',
                                                '/library/cart.lbi',
                                                '/library/vote.lbi'
                                                ),
                         'compare'         => array('/library/ur_here.lbi',
                                                '/library/search_form.lbi',
                                            ),
                         'flow'             => array('/library/ur_here.lbi',
                                                '/library/search_form.lbi',
                                            ),
                         'index'         => array('/library/ur_here.lbi',
                                                '/library/search_form.lbi',
                                                '/library/member.lbi',
                                                '/library/new_articles.lbi',
                                                '/library/category_tree.lbi',
                                                '/library/top10.lbi',
                                                '/library/invoice_query.lbi',
                                                '/library/recommend_best.lbi',
                                                '/library/recommend_new.lbi',
                                                '/library/recommend_hot.lbi',
                                                '/library/group_buy.lbi',
                                                '/library/recommend_promotion.lbi',
                                                '/library/brands.lbi',
                                                '/library/cart.lbi',
                                                '/library/vote.lbi'
                                                ),

                        'goods'         => array('/library/ur_here.lbi',
                                                '/library/search_form.lbi',
                                                '/library/cart.lbi',
                                                '/library/member.lbi',
                                                '/library/category_tree.lbi',
                                                '/library/goods_attrlinked.lbi',
                                                '/library/history.lbi',

                                                '/library/goods_fittings.lbi',
                                                '/library/goods_gallery.lbi',
                                                '/library/goods_tags.lbi',
                                                '/library/comments.lbi',
                                                '/library/bought_goods.lbi',

                                                '/library/goods_related.lbi',
                                                '/library/goods_article.lbi',
                                            ),
                        'search_result' => array('/library/ur_here.lbi',
                                                '/library/search_form.lbi',
                                                '/library/member.lbi',
                                                '/library/category_tree.lbi',
                                                '/library/cart.lbi',
                                                '/library/search_result.lbi',
                                                '/library/top10.lbi',
                                                '/library/search_advanced.lbi',
                                                '/library/history.lbi',
                                                '/library/pages.lbi'
                                            ),
                        'tag_cloud'     => array('/library/ur_here.lbi',
                                                '/library/search_form.lbi',
                                                '/library/cart.lbi',
                                                '/library/member.lbi',
                                                '/library/category_tree.lbi',
                                                '/library/history.lbi',
                                                '/library/top10.lbi',
                                                '/library/recommend_best.lbi',
                                                '/library/recommend_new.lbi',
                                                '/library/recommend_hot.lbi',
                                                '/library/recommend_promotion.lbi'
                                            ),
                         'group_buy_goods' => array('/library/ur_here.lbi',
                                                '/library/search_form.lbi',
                                                '/library/member.lbi',
                                                '/library/category_tree.lbi',
                                                '/library/cart.lbi',
                                                '/library/history.lbi',
                                            ),
                         'group_buy_list'  => array('/library/ur_here.lbi',
                                                '/library/search_form.lbi',
                                                '/library/member.lbi',
                                                '/library/category_tree.lbi',
                                                '/library/cart.lbi',
                                                '/library/top10.lbi',
                                                '/library/history.lbi',
                                            ),
                         'search'          => array('/library/ur_here.lbi',
                                                '/library/search_form.lbi',
                                                '/library/member.lbi',
                                                '/library/category_tree.lbi',
                                                '/library/cart.lbi',
                                                '/library/top10.lbi',
                                                '/library/history.lbi',
                                            ),
                         'snatch'           => array('/library/ur_here.lbi',
                                                '/library/search_form.lbi',
                                                '/library/member.lbi',
                                                '/library/category_tree.lbi',
                                                '/library/cart.lbi',
                                            ),
                        );

/* 动态库项目 */
$dyna_libs      = array('cat_goods',
                        'brand_goods',
                        'cat_articles',
                        'ad_position'
                  );

/* 插件的 library */
$sql = 'SELECT code, library FROM ' . $ecs->table('plugins') . " WHERE assign = 1 AND library > ''";
$res = $db->query($sql);

while ($row = $db->fetchRow($res))
{
    include_once('../plugins/' . $row['code'] . '/languages/common_' . $_CFG['lang'] . '.php');

    $page_libs['index'][] = $row['library'];
}

/**
 * 获得模版的信息
 *
 * @access  private
 * @param   string      $template_name      模版名
 * @return  array
 */
function get_template_info($template_name)
{
    $info = array();

    $info['code']       = $template_name;
    $info['screenshot'] = (file_exists('../themes/' . $template_name . '/screenshot.png')) ?
                            '../themes/' . $template_name . '/screenshot.png' : '';

    if (file_exists('../themes/' . $template_name . '/style.css') && !empty($template_name))
    {
        $arr = array_slice(file('../themes/'. $template_name. '/style.css'), 0, 8);
        $template_name      = explode(': ', $arr[1]);
        $template_uri       = explode(': ', $arr[2]);
        $template_desc      = explode(': ', $arr[3]);
        $template_version   = explode(': ', $arr[4]);
        $template_author    = explode(': ', $arr[5]);
        $author_uri         = explode(': ', $arr[6]);

        $info['name']       = trim($template_name[1]);
        $info['uri']        = trim($template_uri[1]);
        $info['desc']       = trim($template_desc[1]);
        $info['version']    = trim($template_version[1]);
        $info['author']     = trim($template_author[1]);
        $info['author_uri'] = trim($author_uri[1]);
    }
    else
    {
        $info['name']       = '';
        $info['uri']        = '';
        $info['desc']       = '';
        $info['version']    = '';
        $info['author']     = '';
        $info['author_uri'] = '';
    }

    return $info;
}

/**
 * 获得模版文件中的编辑区域及其内容
 *
 * @access  public
 * @param   string  $tmp_name   模版名称
 * @param   string  $tmp_file   模版文件名称
 * @return  array
 */
function get_template_region($tmp_name, $tmp_file, $lib=true)
{
    global $dyna_libs;

    $file = '../themes/' . $tmp_name . '/' . $tmp_file;

    /* 将模版文件的内容读入内存 */
    $content = file_get_contents($file);

    /* 获得所有编辑区域 */
    static $regions = array();

    if (empty($regions))
    {
        $matches = array();
        $result  = preg_match_all('/(<!--\\s*TemplateBeginEditable\\sname=")([^"]+)("\\s*-->)/', $content, $matches, PREG_SET_ORDER);

        if ($result && $result > 0)
        {
            foreach ($matches AS $key => $val)
            {
                if ($val[2] != 'doctitle' && $val[2] != 'head')
                {
                    $regions[] = $val[2];
                }
            }
        }

    }

    if (!$lib)
    {
        return $regions;
    }

    $libs = array();
    /* 遍历所有编辑区 */
    foreach ($regions AS $key => $val)
    {
        $matches = array();
        $pattern = '/(<!--\\s*TemplateBeginEditable\\sname="%s"\\s*-->)(.*?)(<!--\\s*TemplateEndEditable\\s*-->)/s';

        if (preg_match(sprintf($pattern, $val), $content, $matches))
        {
            /* 找出该编辑区域内所有库项目 */
            $lib_matches = array();

            $result      = preg_match_all('/([\s|\S]{0,20})(<!--\\s#BeginLibraryItem\\s")([^"]+)("\\s-->)/',
                                          $matches[2], $lib_matches, PREG_SET_ORDER);
            $i = 0;
            if ($result && $result > 0)
            {
                foreach ($lib_matches AS $k => $v)
                {
                    $v[3]   = strtolower($v[3]);
                    $libs[] = array('library' => $v[3], 'region' => $val, 'lib'=>basename(substr($v[3], 0, strpos($v[3], '.'))), 'sort_order' => $i);
                    $i++;
                }

            }
        }
    }

    return $libs;
}

/**
 * 将插件library从默认模板中移动到指定模板中
 *
 * @access  public
 * @param   string  $tmp_name   模版名称
 * @param   string  $msg        如果出错，保存错误信息，否则为空
 * @return  Boolen
 */
function move_plugin_library($tmp_name, &$msg)
{
    $sql = 'SELECT code, library FROM ' . $GLOBALS['ecs']->table('plugins') . " WHERE library > ''";
    $rec = $GLOBALS['db']->query($sql);
    $return_value = true;
    $target_dir = ROOT_PATH . 'themes/' . $tmp_name;
    $source_dir = ROOT_PATH . 'themes/' . $GLOBALS['_CFG']['template'];
    while ($row = $GLOBALS['db']->fetchRow($rec))
    {
        //先移动，移动失败试则拷贝
        if (!@rename($source_dir . $row['library'], $target_dir . $row['library']))
        {
            if (!@copy(ROOT_PATH . 'plugins/' . $row['code'] . '/templates' . $row['library'], $target_dir . $row['library']))
            {
                $return_value = false;
                $msg .= "\n moving " . $row['library'] . ' failed';
            }
        }
    }
}

/**
 * 获得指定库项目在模板中的设置内容
 *
 * @access  public
 * @param   string  $lib    库项目
 * @param   array   $libs    包含设定内容的数组
 * @return  void
 */
function get_setted($lib, &$arr)
{
    $options = array('region' => '', 'sort_order' => 0, 'display' => 0);

    foreach ($arr AS $key => $val)
    {
        if ($lib == $val['library'])
        {
            $options['region']     = $val['region'];
            $options['sort_order'] = $val['sort_order'];
            $options['display']    = 1;

            break;
        }
    }

    return $options;
}

?>