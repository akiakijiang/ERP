<?php

/**
 * ECSHOP 购物流程相关语言
 * ============================================================================
 * 版权所有 (C) 2005-2007 北京亿商互动科技发展有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com
 * ----------------------------------------------------------------------------
 * 这是一个免费开源的软件；这意味着您可以在不用于商业目的的前提下对程序代码
 * 进行修改、使用和再发布。
 * ============================================================================
 * @author:     Weber Liu <weberliu@hotmail.com>
 * @version:    v2.x
 * ---------------------------------------------
 * $Author: paulgao $
 * $Date: 2007-04-19 18:11:35 +0800 (星期四, 19 四月 2007) $
 * $Id: shopping_flow.php 8272 2007-04-19 10:11:35Z paulgao $
*/

$_LANG['flow_login_register']['username_not_null'] = '请您输入用户名。';
$_LANG['flow_login_register']['username_invalid'] = '您输入了一个无效的用户名。';
$_LANG['flow_login_register']['password_not_null'] = '请您输入密码。';
$_LANG['flow_login_register']['email_not_null'] = '请您输入电子邮件。';
$_LANG['flow_login_register']['email_invalid'] = '您输入的电子邮件不正确。';
$_LANG['flow_login_register']['password_not_same'] = '您输入的密码和确认密码不一致。';

$_LANG['regist_success'] = "恭喜您，%s 账号注册成功!";
$_LANG['login_success'] = '恭喜！您已经成功登陆本站！';

/* 收藏物品 */
$_LANG['update_Favorites_ok']	=	'更新收藏纪录成功';

/* 购物车 */
$_LANG['update_cart'] = '更新购物车';
$_LANG['back_to_cart'] = '返回购物车';
$_LANG['update_cart_notice'] = '购物车更新成功。';
$_LANG['direct_shopping'] = '不打算登录，直接购买';
$_LANG['goods_not_exists'] = '对不起，指定的商品不存在';
$_LANG['drop_goods_confirm'] = '您确实要把该商品移出购物车吗？';
$_LANG['goods_number_not_int'] = '请您输入正确的商品数量。';
$_LANG['stock_insufficiency'] = '非常抱歉，您选择的商品 %s 的库存数量不足.';
$_LANG['shopping_flow'] = '购物流程';
$_LANG['no_gift_selected'] = '您没有选择赠品。';
$_LANG['gift_not_exist'] = '您选择的赠品不存在。';
$_LANG['gifttype_not_enjoyable'] = '您不能享受该赠品活动。';
$_LANG['gifttype_not_exist'] = '您选择的赠品活动不存在。';
$_LANG['order_amount_not_enough'] = '您的订单金额不足，不享有该优惠。';
$_LANG['not_enjoyable'] = "您不享受该优惠：%s。";
$_LANG['selected_count_full'] = '您选择的赠品数量已达到最大值。';
$_LANG['gift_already_in_cart'] = '您选择的赠品已经在购物车中了。';
$_LANG['username_exists'] = '您输入的用户名已存在，请换一个试试。';
$_LANG['email_exists'] = '您输入的电子邮件已存在，请换一个试试。';
$_LANG['surplus_not_enough'] = '您使用的余额不能超过您现有的余额。';
$_LANG['integral_not_enough'] = '您使用的积分不能超过您现有的积分。';
$_LANG['integral_too_much'] = "您使用的积分不能超过%d";
$_LANG['invalid_bonus'] = "您选择的红包并不存在。";
$_LANG['no_goods_in_cart'] = '您的购物车中没有商品！';
$_LANG['cart_is_dirty'] = '您的购物车中的商品已经发生变化，请重新检查！';
$_LANG['incorrect_address_info'] = '请填写完整的收货地址信息！';
$_LANG['checkout_failed'] = "支付失败";
$_LANG['incorrect_invoice_info']="请输入发票信息";
$_LANG['incorrect_payment_info']="请选择支付方式";
$_LANG['incorrect_shipping_info']="请选择送货方式";
$_LANG['shipping_unavailable_at_this_area']="您选择的送货地址不能使用这个送货方式";
$_LANG['not_enough_points'] = "您选择的欧币超出您拥有欧币的数目";
$_LANG['exceed_limit_integral'] = "您选择的欧币超出您的订单所能使用欧币的数目";
$_LANG['cod_unavailable']='您选择了货到付款，但是您选择的送货方式不支持货到付款';
$_LANG['not_submit_order'] = '您参与本次团购商品的订单已提交，请勿重复操作！';
$_LANG['pay_success'] = '本次支付已经成功，我们将尽快为您发货。';
$_LANG['pay_fail'] = '本次支付失败，请及时和我们取得联系。';
$_LANG['pay_disabled'] = '您选用的支付方式已经被停用。';
$_LANG['pay_invalid'] = '您选用了一个无效的支付方式。该支付方式不存在或者已经被停用。请您立即和我们取得联系。';
$_LANG['flow_no_shipping'] = '您必须选定一个配送方式。';
$_LANG['flow_no_payment'] = '您必须选定一个支付方式。';
$_LANG['pay_not_exist'] = '选用的支付方式不存在。';
$_LANG['storage_short'] = '库存不足';
$_LANG['subtotal'] = '小计';
$_LANG['accessories'] = '配件';
$_LANG['largess'] = '赠品';
$_LANG['shopping_money'] = '购物金额小计 %s';
$_LANG['than_market_price'] = '比市场价 %s 节省了 %s (%s)';
$_LANG['cart_gift_money'] = '您的购物车中参加送赠品活动的商品的总金额为 %s';
$_LANG['activity_name'] = '活动名称: ';
$_LANG['may_gift_select'] = '您可以从下面的赠品中任选 %d 个，目前您已选择了 %d 个。';
$_LANG['cart_money_amongst'] = '如果您的购物车中参加送赠品活动的商品的总金额介于 %.2f 和 %.2f 之间，您就可以从下面的赠品中任选 %d 个。';
$_LANG['cart_money_exceed'] = '如果您的购物车中参加送赠品活动的商品的总金额超过 %.2f，您就可以从下面的赠品中任选%d 个。';
$_LANG['if_purchase'] = '如果您购买了';
$_LANG['may_select_amount'] = '您可以从下面的赠品中任选 %d 个';
$_LANG['no'] = '无';
$_LANG['not_support_virtual_goods'] = '购物车中存在非实体商品,不支持匿名购买,请登陆后在购买';
$_LANG['not_support_insure'] = '不支持保价';

/* 登录注册 */
$_LANG['forthwith_login'] = '登录';
$_LANG['forthwith_register'] = '注册新用户';
$_LANG['signin_failed'] = '对不起，登录失败，请检查您的用户名和密码是否正确';

/* 收货人信息 */
$_LANG['flow_js']['consignee_not_null'] = '收货人姓名不能为空！';
$_LANG['flow_js']['country_not_null'] = '请您选择收货人所在国家！';
$_LANG['flow_js']['province_not_null'] = '请您选择收货人所在省份！';
$_LANG['flow_js']['city_not_null'] = '请您选择收货人所在城市！';
$_LANG['flow_js']['district_not_null'] = '请您选择收货人所在区域！';
$_LANG['flow_js']['invalid_email'] = '您输入的邮件地址不是一个合法的邮件地址。';
$_LANG['flow_js']['address_not_null'] = '收货人的详细地址不能为空！';
$_LANG['flow_js']['tele_not_null'] = '电话不能为空！';
$_LANG['flow_js']['shipping_not_null'] = '请您选择配送方式！';
$_LANG['flow_js']['payment_not_null'] = '请您选择支付方式！';
$_LANG['flow_js']['goodsattr_style'] = 1;

$_LANG['new_consignee_address'] = '新收货地址';
$_LANG['consignee_address'] = '收货地址';
$_LANG['consignee_name'] = '收货人姓名';
$_LANG['country_province'] = '国家/省份';
$_LANG['please_select'] = '请选择...';
$_LANG['city_district'] = '城市/地区';
$_LANG['email_address'] = '电子邮件地址';
$_LANG['detailed_address'] = '详细地址';
$_LANG['postalcode'] = '邮政编码';
$_LANG['phone'] = '电话';
$_LANG['mobile'] = '手机';
$_LANG['backup_phone'] = '手机';
$_LANG['sign_building'] = '标志建筑';
$_LANG['deliver_goods_time'] = '最佳送货时间';
$_LANG['default'] = '默认';
$_LANG['default_address'] = '默认地址';
$_LANG['confirm_submit'] = '确认提交';
$_LANG['confirm_edit'] = '确认修改';
$_LANG['country'] = '国家';
$_LANG['province'] = '省份';
$_LANG['city'] = '城市';
$_LANG['area'] = '所在区域';
$_LANG['consignee_add'] = '添加新收货地址';
$_LANG['shipping_address'] = '配送至这个地址';
$_LANG['address_amount'] = '您的收货地址最多只能是三个';
$_LANG['not_fount_consignee'] = '对不起，您选定的收货地址不存在。';

/*------------------------------------------------------ */
//-- 订单提交
/*------------------------------------------------------ */

$_LANG['select_shipping'] = '您选定的配送方式为';
$_LANG['select_payment'] = '您选定的支付方式为';
$_LANG['order_amount'] = '您的应付款金额为';
$_LANG['remember_order_number'] = '感谢您在本店购物！您的订单已提交成功，请记住您的订单号';
$_LANG['back_home'] = '<a href="index.php">返回首页</a>';
$_LANG['goto_user_center'] = '<a href="user.php">用户中心</a>';
$_LANG['order_submit_back'] = '您可以 %s 或去 %s';

$_LANG['order_placed_sms'] = "您有新订单.收货人:%s 电话:%s";
$_LANG['sms_paid'] = '已付款';

$_LANG['notice_gb_order_amount'] = '（备注：团购如果有保证金，第一次只需支付保证金和相应的支付费用）';

?>