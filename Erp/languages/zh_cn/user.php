<?php

/**
 * ECSHOP 用户中心语言项
 * ============================================================================
 * 版权所有 (C) 2005-2007 北京亿商互动科技发展有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com
 * ----------------------------------------------------------------------------
 * 这是一个免费开源的软件；这意味着您可以在不用于商业目的的前提下对程序代码
 * 进行修改、使用和再发布。
 * ============================================================================
 * @author:     wj                 <wjzhangq@126.com>
 * @version:    v2.x
 * ---------------------------------------------
 * $Author: paulgao $
 * $Date: 2007-04-19 18:11:35 +0800 (星期四, 19 四月 2007) $
 * $Id: user.php 8272 2007-04-19 10:11:35Z paulgao $
*/

$_LANG['require_login'] = '非法入口。<br />必须登录才能完成操作。';

/* 用户菜单 */
$_LANG['label_welcome'] = '欢迎页';
$_LANG['label_profile'] = '用户信息';
$_LANG['label_order'] = '我的订单';
$_LANG['label_address'] = '收货地址';
$_LANG['label_message'] = '我的留言';
$_LANG['label_tag'] = '我的标签';
$_LANG['label_collection'] = '我的收藏';
$_LANG['label_bonus'] = '我的红包';
$_LANG['label_group_buy'] = '我的团购';
$_LANG['label_booking'] = '缺货登记';
$_LANG['label_user_surplus'] = '账目明细';
$_LANG['label_logout'] = '退出';

/* 会员余额(预付款) */
$_LANG['add_surplus_log'] = '账目明细';
$_LANG['surplus_pro_type'] = '金额类型';
$_LANG['repay_money'] = '退款金额';
$_LANG['money'] = '数量';
$_LANG['surplus_type_0'] = '预付款';
$_LANG['surplus_type_1'] = '退款申请';
$_LANG['surplus_type_2'] = '购买商品';
$_LANG['surplus_type_3'] = '订单退款';
$_LANG['deposit_money'] = '付款金额';
$_LANG['process_notic'] = '会员备注';
$_LANG['admin_notic'] = '管理员备注';
$_LANG['submit_request'] = '提交申请';
$_LANG['process_time'] = '操作时间';
$_LANG['use_time'] = '使用时间';
$_LANG['is_paid'] = '状态';
$_LANG['is_confirm'] = '已完成';
$_LANG['un_confirm'] = '未确认';
$_LANG['pay'] = '付款';
$_LANG['is_cancel'] = '取消';
$_LANG['surplus_amount'] = '您的预付款数量为：';
$_LANG['payment_name'] = '您选择的支付方式为：';
$_LANG['payment_fee'] = '支付手续费用为：';
$_LANG['payment_desc'] = '支付方式描述：';
$_LANG['current_surplus'] = '您当前剩余余额为：';
$_LANG['nuit_yuan'] = '元';
$_LANG['surplus_amount_error'] = '您要申请退款的金额超过了您现有的余额，此操作将不可进行！';
$_LANG['surplus_appl_submit'] = '您的退款申请已成功提交，请等待管理员的审核！';
$_LANG['process_false'] = '此次操作失败，请返回重试！';
$_LANG['confirm_remove_account'] = '您确定要删除此条记录吗？';
$_LANG['back_page_up'] = '返回上一页';
$_LANG['back_account_log'] = '返回账目明细列表';

//JS语言项
$_LANG['account_js']['surplus_amount_empty'] = '请输入您要操作的金额数量！';
$_LANG['account_js']['surplus_amount_error'] = '您输入的金额数量格式不正确！';
$_LANG['account_js']['process_desc'] = '请输入您此次操作的备注信息！';
$_LANG['account_js']['payment_empty'] = '请选择支付方式！';

/* 缺货登记 */
$_LANG['oos_booking'] = '缺货登记';
$_LANG['booking_goods_name'] = '订购商品名';
$_LANG['booking_amount'] = '订购数量';
$_LANG['booking_time'] = '登记时间';
$_LANG['process_desc'] = '处理备注';
$_LANG['describe'] = '订购描述';
$_LANG['contact_username'] = '联系人';
$_LANG['contact_phone'] = '联系电话';
$_LANG['submit_booking_goods'] = '提交缺货登记';
$_LANG['booking_success'] = '您的商品订购已经成功提交！';
$_LANG['booking_rec_exist'] = '此商品您已经进行过缺货登记了！';
$_LANG['back_booking_list'] = '返回缺货登记列表';
$_LANG['not_dispose'] = '未处理';
$_LANG['no_goods_id'] = '请指定商品ID';

//JS语言项
$_LANG['booking_js']['booking_amount_empty'] = '请输入您要订购的商品数量！';
$_LANG['booking_js']['booking_amount_error'] = '您输入的订购数量格式不正确！';
$_LANG['booking_js']['describe_empty'] = '请输入您的订购描述信息！';
$_LANG['booking_js']['contact_username_empty'] = '请输入联系人姓名！';
$_LANG['booking_js']['email_empty'] = '请输入联系人的电子邮件地址！';
$_LANG['booking_js']['email_error'] = '您输入的电子邮件地址格式不正确！';
$_LANG['booking_js']['contact_phone_empty'] = '请输入联系人的电话！';

/* 个人资料 */
$_LANG['confirm_submit'] = '　确 定　';
$_LANG['member_rank'] = '会员等级';
$_LANG['member_discount'] = '会员折扣';
$_LANG['rank_integral'] = '等级积分';
$_LANG['consume_integral'] = '消费积分';
$_LANG['account_balance'] = '账户余额';
$_LANG['user_bonus'] = '用户红包';
$_LANG['user_bonus_info'] = '共计 %d 个,价值 %s';
$_LANG['not_bonus'] = '没有红包';
$_LANG['add_user_bonus'] = '添加一个红包';
$_LANG['bonus_number'] = '红包序列号';
$_LANG['old_password'] = '原密码';
$_LANG['new_password'] = '新密码';
$_LANG['confirm_password'] = '确认密码';

$_LANG['bonus_sn_exist'] = '此红包号码已经被占用了！';
$_LANG['bonus_sn_not_exist'] = '此红包号码不存在！';
$_LANG['add_bonus_sucess'] = '添加新的红包操作成功！';
$_LANG['add_bonus_false'] = '添加新的红包操作失败！';

$_LANG['not_login'] = '用户未登录。无法完成操作';
$_LANG['profile_lnk'] = '查看我的个人资料';
$_LANG['edit_email_failed'] = '编辑电子邮件地址失败！';
$_LANG['edit_profile_success'] = '您的个人资料已经成功修改！';
$_LANG['edit_profile_failed'] = '修改个人资料操作失败！';
$_LANG['oldpassword_error'] = '您输入的旧密码有误!请确认再后输入！';

//JS语言项
$_LANG['profile_js']['bonus_sn_empty'] = '请输入您要添加的红包号码！';
$_LANG['profile_js']['bonus_sn_error'] = '您输入的红包号码格式不正确！';

$_LANG['profile_js']['email_empty'] = '请输入您的电子邮件地址！';
$_LANG['profile_js']['email_error'] = '您输入的电子邮件地址格式不正确！';
$_LANG['profile_js']['old_password_empty'] = '请输入您的原密码！';
$_LANG['profile_js']['new_password_empty'] = '请输入您的新密码！';
$_LANG['profile_js']['confirm_password_empty'] = '请输入您的确认密码！';
$_LANG['profile_js']['both_password_error'] = '您现两次输入的密码不一致！';

/* 支付方式 */
$_LANG['pay_name'] = '名称';
$_LANG['pay_desc'] = '描述';
$_LANG['pay_fee'] = '手续费';

/* 收货地址 */
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
$_LANG['yes'] = '是';
$_LANG['no'] = '否';
$_LANG['country'] = '国家';
$_LANG['province'] = '省份';
$_LANG['city'] = '城市';
$_LANG['area'] = '所在区域';

$_LANG['del_address_false'] = '删除收货地址失败！';
$_LANG['add_address_success'] = '添加新地址成功！';
$_LANG['edit_address_success'] = '您的收货地址信息已成功更新！';
$_LANG['address_list_lnk'] = '返回地址列表';
$_LANG['please_select'] = '请选择...';
$_LANG['add_address'] = '新增收货地址';
$_LANG['confirm_edit'] = '确认修改';

$_LANG['confirm_drop_address'] = '你确认要删除该收货地址吗？';

/* 会员密码找回 */
$_LANG['username_and_email'] = '请输入您注册的用户名和注册时填写的电子邮件地址。';
$_LANG['enter_new_password'] = '请输入您的新密码';
$_LANG['username_no_email'] = '您填写的用户名与电子邮件地址不匹配，请重新输入！';
$_LANG['fail_send_password'] = '发送邮件出错，请与管理员联系！';
$_LANG['send_success'] = '重置密码的邮件已经发到您的邮箱：';
$_LANG['parm_error'] = '参数错误，请返回！';
$_LANG['edit_password_failure'] = '您输入的原密码不正确！';
$_LANG['edit_password_success'] = '您的新密码已设置成功！';
$_LANG['username_not_match_email'] = '用户名与电子邮件地址不匹配，请重新输入！';
//JS语言项
$_LANG['password_js']['user_name_empty'] = '请输入您的用户名！';
$_LANG['password_js']['email_address_empty'] = '请输入您的电子邮件地址！';
$_LANG['password_js']['email_address_error'] = '您输入的电子邮件地址格式不正确！';
$_LANG['password_js']['new_password_empty'] = '请输入您的新密码！';
$_LANG['password_js']['confirm_password_empty'] = '请输入您的确认密码！';
$_LANG['password_js']['both_password_error'] = '您两次输入的密码不一致！';

/* 会员留言 */
$_LANG['message_title'] = '主题';
$_LANG['message_time'] = '留言时间';
$_LANG['reply_time'] = '回复时间';
$_LANG['shopman_reply'] = '店主回复';
$_LANG['send_message'] = '发表留言';
$_LANG['message_type'] = '留言类型';
$_LANG['message_content'] = '留言内容';
$_LANG['submit_message'] = '提交留言';
$_LANG['upload_img'] = '上传文件';
$_LANG['img_name'] = '图片名称';

/* 留言类型 */
$_LANG['type'][M_MESSAGE] = '留言';
$_LANG['type'][M_COMPLAINT] = '投诉';
$_LANG['type'][M_ENQUIRY] = '询问';
$_LANG['type'][M_CUSTOME] = '售后';
$_LANG['type'][M_BUY] = '求购';

$_LANG['add_message_success'] = '发表留言成功';
$_LANG['message_list_lnk'] = '返回留言列表';
$_LANG['msg_title_empty'] = '留言标题为空';

$_LANG['img_type_tips'] = '<font color="red">小提示：</font>';
$_LANG['img_type_list'] = '您可以上传以下格式的文件：<br />gif、jpg、png、word、excel、txt、zip、ppt、pdf';
$_LANG['view_upload_file'] = '查看上传的文件';
$_LANG['upload_file_type'] = '您上傳的文件类型不正确,请重新上传！';
$_LANG['upload_file_error'] = '文件上传出现错误,请重新上传！';
$_LANG['message_empty'] = '您现在还没有留言！';
$_LANG['msg_success'] = '您的留言已成功提交！';
$_LANG['confirm_remove_msg'] = '你确实要彻底删除这条留言吗？';

/* 会员红包 */
$_LANG['bonus_is_used'] = '你输入的红包你已经领取过了！';
$_LANG['bonus_is_used_by_other'] = '你输入的红包已经被其他人领取！';
$_LANG['bonus_add_success'] = '您已经成功的添加了一个新的红包！';
$_LANG['bonus_not_exist'] = '你输入的红包不存在';
$_LANG['user_bonus_empty'] = '您现在还没有红包';
$_LANG['add_bonus_sucess'] = '添加新的红包操作成功！';
$_LANG['add_bonus_false'] = '添加新的红包操作失败！';

/* 会员订单 */
$_LANG['order_list_lnk'] = '我的订单列表';
$_LANG['order_number'] = '订单编号';
$_LANG['order_addtime'] = '下单时间';
$_LANG['order_money'] = '订单总金额';
$_LANG['order_status'] = '订单状态';
$_LANG['first_order'] = '主订单';
$_LANG['second_order'] = '从订单';
$_LANG['merge_order'] = '合并订单';
$_LANG['no_priv'] = '你没有权限操作他人订单';
$_LANG['buyer_cancel'] = '用户取消';
$_LANG['cancel'] = '取消订单';
$_LANG['pay_money'] = '付款';
$_LANG['view_order'] = '等待Ouku确认';
$_LANG['received'] = '等待收货';
$_LANG['ss_received'] = '已完成';
$_LANG['confirm_cancel'] = '您确认要取消该订单吗？取消后此订单将视为无效订单';
$_LANG['merge_ok'] = '订单合并成功！';
$_LANG['merge_invalid_order'] = '对不起，您选择合并的订单不允许进行合并的操作。';
$_LANG['select'] = '请选择...';
$_LANG['order_not_pay'] = "你的订单状态为 %s ,不需要付款";
$_LANG['order_sn_empty'] = '合并主订单号不能为空';
$_LANG['merge_order_notice'] = '订单合并是在发货前将相同状态的订单合并成一新的订单。<br />收货地址，送货方式等以主定单为准。';
$_LANG['order_exist'] = '该订单不存在！';
$_LANG['order_is_group_buy'] = '[团购]';
$_LANG['gb_deposit'] = '（保证金）';
$_LANG['notice_gb_order_amount'] = '（备注：团购如果有保证金，第一次只需支付保证金和相应的支付费用）';

/* 订单状态 */
$_LANG['os'][OS_UNCONFIRMED] = '未确认';
$_LANG['os'][OS_CONFIRMED] = '已确认';
$_LANG['os'][OS_CANCELED] = '已取消';
$_LANG['os'][OS_INVALID] = '无效';
$_LANG['os'][OS_RETURNED] = '退货';

$_LANG['ss'][SS_UNSHIPPED] = '未发货';
$_LANG['ss'][SS_SHIPPED] = '已发货';
$_LANG['ss'][SS_RECEIVED] = '收货确认';

$_LANG['ss'][SS_JUSHOU_RECEIVED] = '拒收退回';
$_LANG['ss'][SS_ZITI_QUEREN] = '自提确认中';
$_LANG['ss'][SS_ZITI_DENGDAI] = '等待自提';
$_LANG['ss'][SS_ZITI_WANCHENG] = '已自提';
$_LANG['ss'][SS_ZITI_QUXIAO] = '自提取消';

//等待自提

$_LANG['ps'][PS_UNPAYED] = '未付款';
$_LANG['ps'][PS_PAYING] = '付款中';
$_LANG['ps'][PS_PAYED] = '已付款';

$_LANG['shipping_not_need'] = '无需使用配送方式';
$_LANG['current_os_not_unconfirmed'] = '当前订单状态不是“未确认”。';
$_LANG['current_os_already_confirmed'] = '当前订单已经被确认，无法取消，请与店主联系。';
$_LANG['current_ss_not_cancel'] = '只有在未发货状态下才能取消，你可以与店主联系。';
$_LANG['current_ps_not_cancel'] = '只有未付款状态才能取消，要取消请联系店主。';
$_LANG['confirm_received'] = '你确认已经收到货物了吗？';

/* 合并订单及订单详情 */
$_LANG['merge_order_success'] = '合并的订单的操作已成功！';
$_LANG['merge_order_failed']  = '合并的订单的操作失败！请返回重试！';
$_LANG['order_sn_not_null'] = '请填写要合并的订单号';
$_LANG['two_order_sn_same'] = '要合并的两个订单号不能相同';
$_LANG['order_not_exist'] = "订单 %s 不存在";
$_LANG['os_not_unconfirmed_or_confirmed'] = " %s 的订单状态不是“未确认”或“已确认”";
$_LANG['ps_not_unpayed'] = "订单 %s 的付款状态不是“未付款”";
$_LANG['ss_not_unshipped'] = "订单 %s 的发货状态不是“未发货”";
$_LANG['order_user_not_same'] = '要合并的两个订单不是同一个用户下的';
$_LANG['from_order_sn'] = '第一个订单号：';
$_LANG['to_order_sn'] = '第二个订单号：';
$_LANG['merge'] = '合并';
$_LANG['notice_order_sn'] = '当两个订单不一致时，合并后的订单信息（如：支付方式、配送方式、包装、贺卡、红包等）以第二个为准。';
$_LANG['subtotal'] = '小计';
$_LANG['goods_price'] = '商品价格';
$_LANG['goods_attr'] = '属性';
$_LANG['use_balance'] = '使用余额';
$_LANG['order_postscript'] = '订单附言';
$_LANG['order_number'] = '订单号';
$_LANG['consignment'] = '发货单';
$_LANG['shopping_money'] = '商品总价';
$_LANG['invalid_order_id'] = '订单号错误';
$_LANG['shipping'] = '配送方式';
$_LANG['payment'] = '支付方式';
$_LANG['use_pack'] = '使用包装';
$_LANG['use_card'] = '使用贺卡';
$_LANG['order_total_fee'] = '订单总金额';
$_LANG['order_money_paid'] = '已付款金额';
$_LANG['order_amount'] = '应付款金额';
$_LANG['accessories'] = '配件';
$_LANG['largess'] = '赠品';
$_LANG['use_more_surplus'] = '追加使用余额';
$_LANG['max_surplus'] = '（您的帐户余额：%s）';
$_LANG['button_submit'] = '确定';
$_LANG['order_detail'] = '订单详情';
$_LANG['error_surplus_invalid'] = '您输入的数字不正确！';
$_LANG['error_order_is_paid'] = '该订单不需要付款！';
$_LANG['error_surplus_not_enough'] = '您的帐户余额不足！';

/* 订单状态 */
$_LANG['detail_order_sn'] = '订单号';
$_LANG['detail_order_status'] = '订单状态';
$_LANG['detail_pay_status'] = '付款状态';
$_LANG['detail_shipping_status'] = '配送状态';
$_LANG['detail_order_sn'] = '订单号';
$_LANG['detail_to_buyer'] = '卖家留言';

$_LANG['confirm_time'] = '确认于 %s';
$_LANG['pay_time'] = '付款于 %s';
$_LANG['shipping_time'] = '发货于 %s';

$_LANG['select_payment'] = '所选支付方式';
$_LANG['order_amount'] = '应付款金额';
$_LANG['update_address'] = '更新收货地址';

/* 取回密码 */
$_LANG['back_home_lnk'] = '返回首页';
$_LANG['get_password_lnk'] = '返回获取密码页面';

/* 登录 注册 */
$_LANG['label_username'] = '用户名';
$_LANG['label_email'] = 'email';
$_LANG['label_password'] = '密码';
$_LANG['label_confirm_password'] = '确认密码';
$_LANG['want_login'] = '我已有账号，我要登录';

$_LANG['login_success'] = '登录成功';
$_LANG['confirm_login'] = '确认登录';
$_LANG['profile_lnk'] = '查看我的个人信息';
$_LANG['login_failure'] = '用户名或密码错误';
$_LANG['relogin_lnk'] = '重新登录';

$_LANG['sex'] = '性　别';
$_LANG['male'] = '男';
$_LANG['female'] = '女';
$_LANG['secrecy'] = '保密';
$_LANG['birthday'] = '出生日期';

$_LANG['logout'] = '您已经成功的退出了。';
$_LANG['username_empty'] = '用户名为空';
$_LANG['username_invalid'] = '用户名 %s 含有敏感字符';
$_LANG['username_exist'] = '用户名 %s 已经存在';
$_LANG['confirm_register'] = '确认注册';

$_LANG['email_empty'] = 'email为空';
$_LANG['email_invalid'] = '%s 不是合法的email地址';
$_LANG['email_exist'] = '%s 已经存在';
$_LANG['register'] = '注册新用户名';
$_LANG['register_success'] = '用户名 %s 注册成功';

/* 用户中心默认页面 */
$_LANG['welcome_to'] = '欢迎您回到';
$_LANG['last_time'] = '您的上一次登录时间';
$_LANG['your_account'] = '您的账户';
$_LANG['your_notice'] = '用户提醒';
$_LANG['your_surplus'] = '余额';
$_LANG['your_bonus'] = '红包';
$_LANG['your_message'] = '留言';
$_LANG['your_order'] = '订单';
$_LANG['your_integral'] = '积分';
$_LANG['last_month_order'] = '您最近30天内提交了';
$_LANG['order_unit'] = '个订单';
$_LANG['please_received'] = '以下订单已发货，请注意查收';

/* 我的标签 */
$_LANG['no_tag'] = '暂时没有标签';
$_LANG['confirm_drop_tag'] = '您确认要删除此标签吗？';

/* user_passport.dwt js语言文件 */
$_LANG['passport_js']['username_empty'] = '- 用户名不能为空。';
$_LANG['passport_js']['username_shorter'] = '- 用户名长度不能少于 5 个字符。';
$_LANG['passport_js']['username_invalid'] = '- 用户名只能是由字母数字以及下划线组成。';
$_LANG['passport_js']['password_empty'] = '- 登录密码不能为空。';
$_LANG['passport_js']['password_shorter'] = '- 登录密码不能少于 6 个字符。';
$_LANG['passport_js']['password_longer'] = '- 登录密码不能大于18个字符。';
$_LANG['passport_js']['confirm_password_invalid'] = '- 两次输入密码不一致';
$_LANG['passport_js']['email_empty'] = '- Email 为空';
$_LANG['passport_js']['email_invalid'] = '- Email 不是合法的地址';

/* user_clips.dwt js 语言文件 */
$_LANG['clips_js']['msg_title_empty'] = '留言标题为空';
$_LANG['clips_js']['msg_content_empty'] = '留言内容为空';

/* 合并订单js */
$_LANG['merge_order_js']['from_order_empty'] = '请选择要合并的从订单';
$_LANG['merge_order_js']['to_order_empty'] = '请选择要合并的主订单';
$_LANG['merge_order_js']['order_same'] = '主订单和从订单相同，请重新选择';

/* 将用户订单中商品加入购物车 */
$_LANG['order_id_empty'] = '未指定订单号';
$_LANG['return_to_cart_success'] = '订单中商品已经成功加入购物车中';

/* 保存用户订单收货地址 */
$_LANG['consigness_empty'] = '收货人姓名为空';
$_LANG['address_empty'] = '收货地址详情为空';
$_LANG['require_unconfirmed'] = '该订单状态下不能再修改地址';

/* 红包详情 */
$_LANG['bonus_sn'] = '红包序号';
$_LANG['bonus_name'] = '红包名称';
$_LANG['bonus_amount'] = '红包金额';
$_LANG['bonus_end_date'] = '截至使用日期';
$_LANG['bonus_status'] = '红包状态';

$_LANG['not_start'] = '未开始';
$_LANG['overdue'] = '已过期';
$_LANG['not_use'] = '未使用';
$_LANG['had_use'] = '已使用';

?>