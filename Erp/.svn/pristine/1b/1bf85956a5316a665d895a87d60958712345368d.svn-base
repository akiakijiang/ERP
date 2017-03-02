<?php

/**
 * ECSHOP Google sitemap 类
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
 * $Id: cls_google_sitemap.php 8651 2007-05-17 03:44:15Z paulgao $
*/

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

class google_sitemap
{
    var $header = "<\x3Fxml version=\"1.0\" encoding=\"UTF-8\"\x3F>\n\t<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">";
    var $charset = "UTF-8";
    var $footer = "\t</urlset>\n";
    var $items = array();

    /**
     * 增加一个新的子项
     *@access   public
     *@param    google_sitemap  item    $new_item
     */
    function add_item($new_item)
    {
        $this->items[] = $new_item;
    }

    /**
     * 生成XML文档
     *@access    public
     *@param     string  $file_name  如果提供了文件名则生成文件，否则返回字符串.
     *@return [void|string]
     */
    function build( $file_name = null )
    {
        $map = $this->header . "\n";

        foreach ($this->items AS $item)
        {
            $item->loc = htmlentities($item->loc, ENT_QUOTES);
            $map .= "\t\t<url>\n\t\t\t<loc>$item->loc</loc>\n";

            // lastmod
            if ( !empty( $item->lastmod ) )
                $map .= "\t\t\t<lastmod>$item->lastmod</lastmod>\n";

            // changefreq
            if ( !empty( $item->changefreq ) )
                $map .= "\t\t\t<changefreq>$item->changefreq</changefreq>\n";

            // priority
            if ( !empty( $item->priority ) )
                $map .= "\t\t\t<priority>$item->priority</priority>\n";

            $map .= "\t\t</url>\n\n";
        }

        $map .= $this->footer . "\n";

        if (!is_null($file_name))
        {
            return file_put_contents($file_name, $map);
        }
        else
        {
            return $map;
        }
    }

}

class google_sitemap_item
{
    /**
     *@access   public
     *@param    string  $loc        位置
     *@param    string  $lastmod    日期格式 YYYY-MM-DD
     *@param    string  $changefreq 更新频率的单位 (always, hourly, daily, weekly, monthly, yearly, never)
     *@param    string  $priority   更新频率 0-1
     */
    function google_sitemap_item($loc, $lastmod = '', $changefreq = '', $priority = '')
    {
        $this->loc = $loc;
        $this->lastmod = $lastmod;
        $this->changefreq = $changefreq;
        $this->priority = $priority;
    }
}

?>