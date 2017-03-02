<?php

/**
 * ECSHOP 管理中心赠品（类型）管理语言文件
 * ============================================================================
 * 版权所有 (C) 2005-2006 北京亿商互动科技发展有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com
 * ----------------------------------------------------------------------------
 * 这是一个免费开源的软件；这意味着您可以在不用于商业目的的前提下对程序代码
 * 进行修改、使用和再发布。
 * ============================================================================
 * @author:     scott ye <scott.yell@hotmail.com>
 * @version:    v2.0
 * ---------------------------------------------
 * $Author: paulgao $
 * $Date: 2007-04-19 18:11:35 +0800 (星期四, 19 四月 2007) $
 * $Id: gift.php 8272 2007-04-19 10:11:35Z paulgao $
*/

$_LANG['gift_type_list'] = '送赠品活动列表';
$_LANG['add_gift_type'] = '添加送赠品活动';
$_LANG['edit_gift_type'] = '编辑送赠品活动';
$_LANG['continue_add_gift_type'] = '继续添加送赠品活动';
$_LANG['back_gift_type_list'] = '返回送赠品活动列表';
$_LANG['add_gift_type_ok'] = '添加送赠品活动成功。';
$_LANG['edit_gift_type_ok'] = '编辑送赠品活动成功。';

/*------------------------------------------------------ */
//-- 送赠品活动列表
/*------------------------------------------------------ */
$_LANG['gift_type_name'] = '活动名称';
$_LANG['amount_range'] = '订单金额范围';
$_LANG['upward_of'] = '%0.2f 以上';

$_LANG['batch_drop_ok'] = '批量删除成功。';
$_LANG['drop_gift_type_confirm'] = '您确实要删除该送赠品活动吗？';
$_LANG['batch_drop_confirm'] = '您确实要删除选中的送赠品活动吗？';

$_LANG['back_type_list'] = '返回赠品活动列表';

/*------------------------------------------------------ */
//-- 添加/编辑送赠品活动信息
/*------------------------------------------------------ */
$_LANG['lab_gift_type_name'] = '送赠品活动名称';
$_LANG['lab_min_amount'] = '订单金额下限';
$_LANG['lab_max_amount'] = '订单金额上限';
$_LANG['lab_max_count'] = '可选赠品数量';
$_LANG['lab_start_date'] = '活动开始日期';
$_LANG['lab_end_date'] = '活动结束日期';

$_LANG['js_languages']['gift_type_name_null'] = '送赠品活动名称不能为空。';
$_LANG['js_languages']['min_amount_not_number'] = '订单金额下限不是数值。';
$_LANG['js_languages']['max_amount_not_number'] = '订单金额上限不是数值。';
$_LANG['js_languages']['max_count_not_number'] = '赠品可选数量不是数值。';

/*------------------------------------------------------ */
//-- 赠品列表
/*------------------------------------------------------ */
$_LANG['gift_list'] = '赠品列表';
$_LANG['type_gift_list'] = '属于类型【%s】的赠品列表';

$_LANG['goods_cat'] = '商品分类';
$_LANG['goods_brand'] = '商品品牌';
$_LANG['keyword'] = '关键字';

$_LANG['all_goods'] = '可选商品';
$_LANG['price'] = '价格';
$_LANG['gift_and_price'] = '赠品及价格';

// ajax
$_LANG['no_select_gifttype'] = '您没有选择任何赠品活动';

$_LANG['gift_type_name_null'] = '您没有输入送赠品活动名称';
$_LANG['gift_already_in_type'] = '您选择的商品已经有送赠品活动了。';
$_LANG['name_exist'] = '已经有相同的活动名称存在了!';
$_LANG['max_count_zero'] = '可选赠品数量必须大于0';

?>