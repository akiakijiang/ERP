<?php

/**
 * ECSHOP 站点地图生成程序语言文件
 * ============================================================================
 * 版权所有 (C) 2005-2006 北京亿商互动科技发展有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com
 * ----------------------------------------------------------------------------
 * 这是一个免费开源的软件；这意味着您可以在不用于商业目的的前提下对程序代码
 * 进行修改、使用和再发布。
 * ============================================================================
 * @author:     Weber Liu <weberliu@hotmail.com>
 * @version:    v2.0
 * ---------------------------------------------
 * $Author: paulgao $
 * $Date: 2007-04-19 18:11:35 +0800 (星期四, 19 四月 2007) $
 * $Id: sitemap.php 8272 2007-04-19 10:11:35Z paulgao $
*/

$_LANG['homepage_changefreq'] = '首页更新频率';
$_LANG['category_changefreq'] = '分类页更新频率';
$_LANG['content_changefreq'] = '内容页更新频率';

$_LANG['priority']['always'] = '一直更新';
$_LANG['priority']['hourly'] = '小时';
$_LANG['priority']['daily'] = '天';
$_LANG['priority']['weekly'] = '周';
$_LANG['priority']['monthly'] = '月';
$_LANG['priority']['yearly'] = '年';
$_LANG['priority']['never'] = '从不更新';

$_LANG['generate_success'] = '站点地图已经生成到data目录下。<br />地址为：%s';
$_LANG['generate_failed'] = '生成站点地图失败，请检查 /data/ 目录是都可以写入.';
$_LANG['sitemaps_note'] = 'Sitemaps 服务旨在使用 Feed 文件 sitemap.xml 通知 Google、Yahoo! 以及 Microsoft 等 Crawler(爬虫)网站上哪些文件需要索引、这些文件的最后修订时间、更改频度、文件位置、相对优先索引权，这些信息将帮助他们建立索引范围和索引的行为习惯。详细信息请查看 <a href="http://www.sitemaps.org/" target="_blank">sitemaps.org</a> 网站上的说明。';
?>