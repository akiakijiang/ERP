<?php

/**
 * ECSHOP 常量
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
 * $Id: inc_constant.php 8651 2007-05-17 03:44:15Z paulgao $
*/

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

/* 图片处理相关常数 */
define('ERR_INVALID_IMAGE',         1);
define('ERR_NO_GD',                 2);
define('ERR_IMAGE_NOT_EXISTS',      3);
define('ERR_DIRECTORY_READONLY',    4);
define('ERR_UPLOAD_FAILURE',        5);
define('ERR_INVALID_PARAM',         6);
define('ERR_INVALID_IMAGE_TYPE',    7);

/* 插件相关常数 */
define('ERR_COPYFILE_FAILED',       1);
define('ERR_CREATETABLE_FAILED',    2);
define('ERR_DELETEFILE_FAILED',     3);

/* 商品属性类型常熟 */
define('ATTR_TEXT',                 0);
define('ATTR_OPTIONAL',             1);
define('ATTR_TEXTAREA',             2);

/* 会员整合相关常数 */
define('ERR_USERNAME_EXISTS',       1); // 用户名已经存在
define('ERR_EMAIL_EXISTS',          2); // Email已经存在
define('ERR_INVALID_USERID',        3); // 无效的user_id
define('ERR_INVALID_USERNAME',      4); // 无效的用户名
define('ERR_INVALID_PASSWORD',      5); // 密码错误
define('ERR_INVALID_EMAIL',         6); // email错误

/* 加入购物车失败的错误代码 */
define('ERR_NOT_EXISTS',            1); // 商品不存在
define('ERR_OUT_OF_STOCK',          2); // 商品缺货
define('ERR_NOT_ON_SALE',           3); // 商品已下架
define('ERR_CANNT_ALONE_SALE',      4); // 商品不能单独销售

/* 购物车商品类型 */
define('CART_GENERAL_GOODS',        0); // 普通商品
define('CART_GROUP_BUY_GOODS',      1); // 团购商品

/* 订单状态 */
define('OS_UNCONFIRMED',            0); // 未确认
define('OS_CONFIRMED',              1); // 已确认
define('OS_CANCELED',               2); // 已取消
define('OS_INVALID',                3); // 无效
define('OS_RETURNED',               4); // 拒收

/* 支付类型 */
define('PAY_ORDER',                 0); // 订单支付
define('PAY_SURPLUS',               1); // 会员预付款

/* 配送状态 */
define('SS_UNSHIPPED',              0); // 未发货
define('SS_SHIPPED',                1); // 已发货*
define('SS_RECEIVED',               2); // 收货确认
define('SS_JUSHOU_RECEIVED',        3); // 拒收退回*
define('SS_ZITI_QUEREN',            4); // 发往自提点
define('SS_ZITI_DENGDAI',           5); // 等待用户自提
define('SS_ZITI_WANCHENG',          6); // 已自提*
define('SS_ZITI_QUXIAO',            7); // 自提取消
define('SS_DEDUCTED',               8); // 已出库，待发货
define('SS_TO_DEDUCTED',            9); // 已配货，待出库
define('SS_MODIFY',                 10); // 已配货，但商品改变
//define('SS_PEIHUO',               11); // 正在配货

/* 支付状态 */
define('PS_UNPAYED',                0); // 未付款
define('PS_PAYING',                 1); // 付款中
define('PS_PAYED',                  2); // 已付款

/* 综合状态 */
define('CS_AWAIT_PAY',              100); // 待付款：货到付款且已发货且未付款，非货到付款且未付款
define('CS_AWAIT_SHIP',             101); // 待发货：货到付款且未发货，非货到付款且已付款且未发货
define('CS_FINISHED',               102); // 已完成：已确认、已付款、已发货

/* 缺货处理 */
define('OOS_WAIT',                  0); // 等待货物备齐后再发
define('OOS_CANCEL',                1); // 取消订单
define('OOS_CONSULT',               2); // 与店主协商

/* 帐户明细类型 */
define('SURPLUS_SAVE',              0); // 为帐户冲值
define('SURPLUS_RETURN',            1); // 从帐户提款
define('SURPLUS_PURCHASE',          2); // 购买商品
define('SURPLUS_CANCELORDER',       3); // 取消订单

/* 评论状态 */
define('COMMENT_UNCHECKED',         0); // 未审核
define('COMMENT_CHECKED',           1); // 已审核或已回复(允许显示)
define('COMMENT_REPLYED',           2); // 该评论的内容属于回复

/* 红包发放的方式 */
define('SEND_BY_USER',              0); // 按用户发放
define('SEND_BY_GOODS',             1); // 按商品发放
define('SEND_BY_ORDER',             2); // 按订单发放
define('SEND_BY_PRINT',             3); // 线下发放

/* 广告的类型 */
define('IMG_AD',                    0); // 图片广告
define('FALSH_AD',                  1); // flash广告
define('CODE_AD',                   2); // 代码广告
define('TEXT_AD',                   3); // 文字广告

/* 赠品发放方式 */
define('GIFT_BY_ORDER',             0); // 按订单
define('GIFT_BY_GOODS',             1); // 按商品

/* 属性值的录入方式 */
define('ATTR_INPUT',                0); // 录入
define('ATTR_SELECT',               1); // 选择

/* 是否需要用户选择属性 */
define('ATTR_NOT_NEED_SELECT',      0); // 不需要选择
define('ATTR_NEED_SELECT',          1); // 需要选择

/* 用户中心留言类型 */
define('M_MESSAGE',                 0); // 留言
define('M_COMPLAINT',               1); // 投诉
define('M_ENQUIRY',                 2); // 询问
define('M_CUSTOME',                 3); // 售后
define('M_BUY',                     4); // 求购

/* 团购活动状态 */
define('GBS_PRE_START',             0); // 未开始
define('GBS_UNDER_WAY',             1); // 进行中
define('GBS_FINISHED',              2); // 已结束
define('GBS_SUCCEED',               3); // 团购成功（可以发货了）
define('GBS_FAIL',                  4); // 团购失败

/* 红包是否发送邮件 */
define('BONUS_NOT_MAIL',            0);
define('BONUS_MAIL_SUCCEED',        1);
define('BONUS_MAIL_FAIL',           2);

/* 订单缺货状态 */
define('SHORTAGE_NORMAL',			0); // 正常
define('SHORTAGE_OUT_OF_STOCK',		1); // 缺货
define('SHORTAGE_WAIT',				2); // 等待
define('SHORTAGE_ON_SALE',			3); // 到货
define('SHORTAGE_CANCELED',			4); // 取消
define('SHORTAGE_CONFIRMED',		5); // 配货

/* 订单商品缺货状态 */
define('STORAGE_OUT_OF_STOCK',		21); // 无货
define('STORAGE_WAIT',				22); // 等待
define('STORAGE_ON_SALE',			23); // 到货
define('STORAGE_RUN_OUT',			24); // 断货
?>
