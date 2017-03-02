<?php

/**
 * ECSHOP 管理中心起始页语言文件
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
 * $Id: index.php 8272 2007-04-19 10:11:35Z paulgao $
*/

$_LANG['about'] = '关于 ECSHOP';
$_LANG['preview'] = '查看网店';
$_LANG['menu'] = '菜单';
$_LANG['help'] = '帮助';
$_LANG['signout'] = '退出';
$_LANG['profile'] = '个人设置';
$_LANG['view_message'] = '查看留言';
$_LANG['send_msg'] = '发送留言';
//$_LANG['toggle_calculator'] = '计算器';
$_LANG['expand_all'] = '展开';
$_LANG['collapse_all'] = '闭合';
$_LANG['no_help'] = '暂时还没有该部分内容';

$_LANG['js_languages']['expand_all'] = '展开';
$_LANG['js_languages']['collapse_all'] = '闭合';

/*------------------------------------------------------ */
//-- 计算器
/*------------------------------------------------------ */

//$_LANG['calculator'] = '计算器';
//$_LANG['clear_calculator'] = '清除';
//$_LANG['backspace'] = '退格';

/*------------------------------------------------------ */
//-- 起始页
/*------------------------------------------------------ */
$_LANG['pm_title'] = '留言标题';
$_LANG['pm_username'] = '留言者';
$_LANG['pm_time'] = '留言时间';

$_LANG['order_stat'] = '订单统计信息';
$_LANG['unconfirmed'] = '未确认订单：';
$_LANG['await_ship'] = '待发货订单：';
$_LANG['await_pay'] = '待支付订单：';
$_LANG['finished'] = '已成交订单数：';
$_LANG['new_booking'] = '新缺货登记：';
$_LANG['new_reimburse'] = '退款申请：';

$_LANG['goods_stat'] = '商品统计信息';
$_LANG['goods_count'] = '商品总数：';
$_LANG['sales_count'] = '促销商品数：';
$_LANG['new_goods'] = '新品推荐数：';
$_LANG['recommed_goods'] = '精品推荐数：';
$_LANG['hot_goods'] = '热销商品数：';
$_LANG['warn_goods'] = '库存警告商品数：';
$_LANG['clear_cache'] = '清除缓存';

$_LANG['acess_stat'] = '访问统计';
$_LANG['acess_today'] = '今日访问：';
$_LANG['online_users'] = '在线人数：';
$_LANG['user_count'] = '会员总数：';
$_LANG['today_register'] = '今日注册：';
$_LANG['new_feedback'] = '最新留言：';
$_LANG['new_comments'] = '最新评论：';

$_LANG['system_info'] = '系统信息';
$_LANG['os'] = '服务器操作系统：';
$_LANG['web_server'] = 'Web 服务器：';
$_LANG['php_version'] = 'PHP 版本：';
$_LANG['mysql_version'] = 'MySQL 版本：';
$_LANG['gd_version'] = 'GD 版本：';
$_LANG['zlib'] = 'Zlib 支持：';
$_LANG['ecs_version'] = 'ECShop 版本：';
$_LANG['install_date'] = '安装日期：';
$_LANG['ip_version'] = 'IP 库版本：';
$_LANG['max_filesize'] = '文件上传的最大大小：';
$_LANG['safe_mode'] = '安全模式：';
$_LANG['safe_mode_gid'] = '安全模式GID：';
$_LANG['timezone'] = '时区设置：';
$_LANG['no_timezone'] = '无需设置';
$_LANG['socket'] = 'Socket 支持：';

$_LANG['remove_install'] = '您还没有删除 install 文件夹，出于安全的考虑，我们建议您删除 install 文件夹。';
$_LANG['remove_upgrade'] = '您还没有删除 upgrade 文件夹，出于安全的考虑，我们建议您删除 upgrade 文件夹。';
$_LANG['temp_dir_cannt_read'] = '您的服务器设置了 open_base_dir 且没有包含 %s，您将无法上传文件。';
$_LANG['not_writable'] = '%s 目录不可写入，%s';
$_LANG['data_cannt_write'] = '您将无法上传包装、贺卡、品牌等等图片文件。';
$_LANG['afficheimg_cannt_write'] = '您将无法上传广告的图片文件。';
$_LANG['brandlogo_cannt_write'] = '您将无法上传品牌的图片文件。';
$_LANG['cardimg_cannt_write'] = '您将无法上传贺卡的图片文件。';
$_LANG['feedbackimg_cannt_write'] = '用户将无法通过留言上传文件。';
$_LANG['packimg_cannt_write'] = '您将无法上传包装的图片文件。';
$_LANG['cert_cannt_write'] = '您将无法上传 ICP 备案证书文件。';
$_LANG['images_cannt_write']= '您将无法上传任何商品图片。';
$_LANG['imagesupload_cannt_write']= '您将无法通过编辑器上传任何图片。';
$_LANG['tpl_cannt_write'] = '您的网站将无法浏览。';
$_LANG['tpl_backup_cannt_write'] = '您就无法备份当前的模版文件。';
$_LANG['order_print_canntwrite'] = 'data目录下的order_print.html文件属性为不可写，您将无法修改订单打印模板。';
$_LANG['shop_closed_tips'] = '您的商店已被暂时关闭。在设置好您的商店之后别忘记打开哦！';
$_LANG['empty_upload_tmp_dir'] = '当前的上传临时目录为空，您可能无法上传文件，请检查 php.ini 中的设置。';
$_LANG['caches_cleared'] = '页面缓存已经清除成功。';

/*------------------------------------------------------ */
//-- 关于我们
/*------------------------------------------------------ */
$_LANG['team_member'] = 'ECSHOP 团队成员';
$_LANG['director'] = '项目策划';
$_LANG['programmer'] = '程序开发';
$_LANG['ui_designer'] = '界面设计';
$_LANG['documentation'] = '文档整理';
$_LANG['special_thanks'] = '特别感谢';
$_LANG['official_site'] = '官方网站';
$_LANG['site_url'] = '网站地址：';
$_LANG['support_center'] = '支持中心：';
$_LANG['support_forum'] = '支持论坛：';
?>