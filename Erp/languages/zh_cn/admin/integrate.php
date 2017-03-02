<?php

/**
 * ECSHOP 管理中心会员数据整合插件管理程序语言文件
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
 * $Id: integrate.php 8272 2007-04-19 10:11:35Z paulgao $
*/

$_LANG['integrate_name'] = '名称';
$_LANG['integrate_version'] = '版本';
$_LANG['integrate_author'] = '作者';

$_LANG['integrate_setup'] = '设置会员数据整合插件';
$_LANG['continue_sync'] = '继续同步会员数据';
$_LANG['go_userslist'] = '返回会员帐号列表';

/* 表单相关语言项 */
$_LANG['lable_db_host'] = '数据库服务器主机名：';
$_LANG['lable_db_name'] = '数据库名：';
$_LANG['lable_db_chartset'] = '数据库字符集：';
$_LANG['lable_db_user'] = '数据库帐号：';
$_LANG['lable_db_pass'] = '数据库密码：';
$_LANG['lable_prefix'] = '数据表前缀：';
$_LANG['lable_url'] = '被整合系统的完整 URL：';
/* 表单相关语言项(discus5x) */
$_LANG['cookie_prefix']          = 'COOKIE前缀：';

$_LANG['sync_start'] = '同步起始位置：';
$_LANG['sync_number'] = '同步记录数量：';
$_LANG['sync_target'] = '同步目标：';
$_LANG['sync_target_sys'][0] = 'ECSHOP';
$_LANG['sync_target_sys'][1] = '目前整合的第三方系统';
$_LANG['btn_sync'] = '开始同步会员数据';

/* 提示信息 */
$_LANG['update_success'] = '设置会员数据整合插件已经成功。';
$_LANG['install_confirm'] = '请勿在运行中随意切换整合的插件。\r\n切换整合的插件将会清空商城的会员相关数据，其中包括：\n会员信息，会员账目明细，会员收货地址，会员红包，订单信息，购物车\r\n您确定要安装该会员数据整合插件吗？';
$_LANG['neednot_sync'] = '当前使用的是ECSHOP自身的会员系统，不需要进行同步操作。';
$_LANG['sync_success'] = '现有会员总数 %d 个记录。<br/>本次同步起始位置为 %d，同步数量为 %d，成功同步 %d 个记录。';
$_LANG['sync_notics'] = '<strong>请谨慎使用该功能</strong><div style="color:red">在正常的使用中您并不需要同步数据</div><div>如果当您使用了一段时间后想整合其他系统的会员数据或者换回 ECSHOP 会员系统时。您可以使用该功能将现有的会员数据同步到第三方系统，或者将第三方系统的会员数据同步到 ECSHOP。</div';
$_LANG['need_not_setup'] = '当您采用ECSHOP会员系统时，无须进行设置。';
$_LANG['different_domain'] = '您设置的整合对象和 ECSHOP 不在同一域下。<br />您将只能共享该系统的会员数据，但无法实现同时登录。';

/* JS语言项 */
$_LANG['js_languages']['no_host'] = '数据库服务器主机名不能为空。';
$_LANG['js_languages']['no_user'] = '数据库帐号不能为空。';
$_LANG['js_languages']['no_name'] = '数据库名不能为空。';
$_LANG['js_languages']['no_integrate_url'] = '请输入整合对象的完整 URL';
$_LANG['js_languages']['install_confirm'] = '请不要在系统运行中随意的更换整合对象。\r\n您确定要安装该会员数据整合插件吗？';
$_LANG['js_languages']['num_invalid'] = '同步数据的记录数不是一个整数';
$_LANG['js_languages']['start_invalid'] = '同步数据的起始位置不是一个整数';
$_LANG['js_languages']['sync_confirm'] = '同步会员数据会将目标数据表重建。请在执行同步之前备份好您的数据。\r\n您确定要开始同步会员数据吗？';

$_LANG['cookie_prefix_notic'] = 'UTF8版本的cookie前缀默认为xnW_，GB2312/GBK版本的cookie前缀默认为KD9_。';

?>