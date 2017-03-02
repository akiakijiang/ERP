<?php

/**
 * ECSHOP 商品相关函数库
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
 * $Author: scottye $
 * $Date: 2007-04-23 15:08:51 +0800 (星期一, 23 四月 2007) $
 * $Id: lib_goods.php 8299 2007-04-23 07:08:51Z scottye $
*/

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}



/**
 * 指定分类下的 物品 
 * oukoo_get_goods
 * @param int/string $good_id
 * @param int $limit
 * @return Array
 */
function oukoo_get_goods($good_id,$limit=0){
    $sLimit	=	'';
    if(gettype($good_id)=='integer'){
        $sWhere	=	' Where goods_type = '.$good_id;
    }else {
        $sWhere	=	' Where goods_type in ('.$good_id.') ';
    }

    if($limit	>	0){
        $sLimit	=	' limit '.$limit;
    }

    $Sql	=	' select goods_name , goods_id from ' . $GLOBALS['ecs']->table('goods') .$sWhere.$sLimit;
    return $GLOBALS['db']->getAll($Sql);
}
/**
 * 指定 分类下的 2及分类 
 * oukoo_get_goods
 * @param int/String $Cat_id
 * @param int $limit
 * @return Array
 */
function oukoo_get_category($Cat_id,$limit=0){

    $sLimit	=	'';
    if(gettype($Cat_id)=='integer'){
        $sWhere	=	' Where parent_id = '.$Cat_id;
    }else {
        $sWhere	=	' Where parent_id in ('.$Cat_id.') ';
    }

    if($limit	>	0){
        $sLimit	=	' limit '.$limit;
    }

    $Sql	=	' select cat_name , cat_id from ' . $GLOBALS['ecs']->table('category') .$sWhere.' order by sort_order desc '.$sLimit;
    return $GLOBALS['db']->getAll($Sql);
}
/**
 * 获得指定分类同级的所有分类以及该分类下的子分类
 *
 * @access  public
 * @param   integer     $cat_id     分类编号
 * @return  array
 */
function get_categories_tree($cat_id = 0)
{
    if ($cat_id > 0)
    {
        $sql = 'SELECT parent_id FROM ' . $GLOBALS['ecs']->table('category') . " WHERE cat_id = '$cat_id'";
        $parent_id = $GLOBALS['db']->getOne($sql);
    }
    else
    {
        $parent_id = 0;
    }

    /*
    判断当前分类中全是是否是底级分类，
    如果是取出底级分类上级分类，
    如果不是取当前分类及其下的子分类
    */
    $sql = 'SELECT count(*) FROM ' . $GLOBALS['ecs']->table('category') . " WHERE parent_id = '$parent_id'";
    if ($GLOBALS['db']->getOne($sql))
    {
        /* 获取当前分类及其子分类 */
        $sql = 'SELECT a.cat_id, a.cat_name, a.sort_order AS parent_order, a.cat_id, ' .
        'b.cat_id AS child_id, b.cat_name AS child_name, b.sort_order AS child_order ' .
        'FROM ' . $GLOBALS['ecs']->table('category') . ' AS a ' .
        'LEFT JOIN ' . $GLOBALS['ecs']->table('category') . ' AS b ON b.parent_id = a.cat_id ' .
        "WHERE a.parent_id = '$parent_id' ORDER BY parent_order ASC, a.cat_id ASC, child_order ASC";
    }
    else
    {
        /* 获取当前分类及其父分类 */
        $sql = 'SELECT a.cat_id, a.cat_name, b.cat_id AS child_id, b.cat_name AS child_name, b.sort_order ' .
        'FROM ' . $GLOBALS['ecs']->table('category') . ' AS a ' .
        'LEFT JOIN ' . $GLOBALS['ecs']->table('category') . ' AS b ON b.parent_id = a.cat_id ' .
        "WHERE b.parent_id = '$parent_id' ORDER BY sort_order ASC";
    }
    $res = $GLOBALS['db']->getAll($sql);

    $cat_arr = array();
    foreach ($res AS $row)
    {
        $cat_arr[$row['cat_id']]['id']   = $row['cat_id'];
        $cat_arr[$row['cat_id']]['name'] = $row['cat_name'];
        $cat_arr[$row['cat_id']]['url']  = build_uri('category', array('cid' => $row['cat_id']), $row['cat_name']);

        if ($row['child_id'] != NULL)
        {
            $cat_arr[$row['cat_id']]['children'][$row['child_id']]['id']   = $row['child_id'];
            $cat_arr[$row['cat_id']]['children'][$row['child_id']]['name'] = $row['child_name'];
            $cat_arr[$row['cat_id']]['children'][$row['child_id']]['url']  = build_uri('category', array('cid' => $row['child_id']), $row['child_name']);
        }
    }

    return $cat_arr;
}

/**
 * 调用当前分类的销售排行榜
 *
 * @access  public
 * @param   string  $cats   查询的分类
 * @return  array
 */
function get_top10($cats = '')
{
    $where = !empty($cats) ? "AND ($cats OR " . get_extension_goods($cats) . ") " : '';

    /* 排行统计的时间 */
    switch ($GLOBALS['_CFG']['top10_time'])
    {
        case 1: // 一年
        $top10_time = "AND o.order_sn >= '" . date('Ymd', time() - 365 * 86400) . "'";
        break;
        case 2: // 半年
        $top10_time = "AND o.order_sn >= '" . date('Ymd', time() - 180 * 86400) . "'";
        break;
        case 3: // 三个月
        $top10_time = "AND o.order_sn >= '" . date('Ymd', time() - 90 * 86400) . "'";
        break;
        case 4: // 一个月
        $top10_time = "AND o.order_sn >= '" . date('Ymd', time() - 30 * 86400) . "'";
        break;
        default:
            $top10_time = '';
    }

    $sql = 'SELECT g.goods_id, g.goods_name, g.goods_img, SUM(og.goods_number) as goods_number ' .
    'FROM ' . $GLOBALS['ecs']->table('goods') . ' AS g, ' .
    $GLOBALS['ecs']->table('order_info') . ' AS o, ' .
    $GLOBALS['ecs']->table('order_goods') . ' AS og ' .
    "WHERE g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 $where $top10_time " .
    'AND og.order_id = o.order_id AND og.goods_id = g.goods_id ' .
    "AND o.order_status = '" . OS_CONFIRMED . "' " .
    "AND (o.pay_status = '" . PS_PAYED . "' OR o.pay_status = '" . PS_PAYING . "') " .
    "AND (o.shipping_status = '" . SS_SHIPPED . "' OR o.shipping_status = '" . SS_RECEIVED . "') ".
    'GROUP BY g.goods_id ORDER BY goods_number DESC, g.goods_id DESC LIMIT ' . $GLOBALS['_CFG']['top_number'];
    $arr = $GLOBALS['db']->getAll($sql);

    for ($i = 0, $count = count($arr); $i < $count; $i++)
    {
        $arr[$i]['short_name'] = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
        sub_str($arr[$i]['goods_name'], 0, $GLOBALS['_CFG']['goods_name_length']) : $arr[$i]['goods_name'];
        $arr[$i]['url']        = build_uri('goods', array('gid' => $arr[$i]['goods_id']), $arr[$i]['goods_name']);
    }

    return $arr;
}

/**
 * 获得推荐商品
 *
 * @access  public
 * @param   string      $type       推荐类型，可以是 best, new, hot, promote
 * @return  array
 */
function get_recommend_goods($type = '', $cats = '')
{
    static $result = NULL;

    $time = date('Y-m-d');

    if ($result === NULL)
    {
        $sql = 'SELECT g.goods_id, g.goods_name, g.market_price, g.shop_price AS org_price, g.promote_price, ' .
        "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, ".
        'promote_start, promote_end, g.goods_brief, g.goods_thumb, goods_img, b.brand_name, g.is_best, g.is_new, g.is_hot, g.is_promote ' .
        'FROM ' . $GLOBALS['ecs']->table('goods') . ' AS g ' .
        'LEFT JOIN ' . $GLOBALS['ecs']->table('brand') . ' AS b ON b.brand_id = g.brand_id ' .
        "LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp ".
        "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' ".
        'WHERE g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 AND ' .
        "(g.is_best = 1 OR g.is_new = 1 OR g.is_hot = 1 OR (g.is_promote = 1 AND promote_start <= '$time' AND promote_end >= '$time')) " .
        'ORDER BY g.sort_order, g.last_update DESC';
        $result = $GLOBALS['db']->getAll($sql);
    }

    /* 取得每一项的数量限制 */
    $num = 0;
    switch ($type)
    {
        case 'best':
            $num = $GLOBALS['_CFG']['best_number'];
            break;
        case 'new':
            $num = $GLOBALS['_CFG']['new_number'];
            break;
        case 'hot':
            $num = $GLOBALS['_CFG']['hot_number'];
            break;
        case 'promote':
            $num = $GLOBALS['_CFG']['promote_number'];
            break;
    }

    $idx = 0;
    $goods = array();
    foreach ($result AS $row)
    {
        if ($idx >= $num)
        {
            break;
        }

        if (($type == 'best' && $row['is_best'] == 1) || ($type == 'new' && $row['is_new'] == 1) ||
        ($type == 'hot' && $row['is_hot'] == 1) || ($type == 'promote' && $row['is_promote'] == 1 && $row['promote_start'] <= $time && $row['promote_end'] >= $time))
        {
            if ($row['promote_price'] > 0)
            {
                $promote_price = bargain_price($row['promote_price'], $row['promote_start'], $row['promote_end']);
                $goods[$idx]['promote_price'] = $promote_price > 0 ? price_format($promote_price) : '';
            }
            else
            {
                $goods[$idx]['promote_price'] = '';
            }

            $goods[$idx]['id']           = $row['goods_id'];
            $goods[$idx]['name']         = $row['goods_name'];
            $goods[$idx]['brief']        = $row['goods_brief'];
            $goods[$idx]['brand_name']   = $row['brand_name'];
            $goods[$idx]['short_name']   = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
            sub_str($row['goods_name'], 0, $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
            $goods[$idx]['market_price'] = price_format($row['market_price']);
            $goods[$idx]['shop_price']   = price_format($row['shop_price']);
            $goods[$idx]['thumb']        = empty($row['goods_thumb']) ? $GLOBALS['_CFG']['no_picture'] : $row['goods_thumb'];
            $goods[$idx]['goods_img']    = empty($row['goods_img'])   ? $GLOBALS['_CFG']['no_picture'] : $row['goods_img'];
            $goods[$idx]['url']          = build_uri('goods', array('gid' => $row['goods_id']), $row['goods_name']);

            $idx++;
        }
    }

    return $goods;
}

/**
 * 获得指定分类下的推荐商品
 *
 * @access  public
 * @param   string      $type       推荐类型，可以是 best, new, hot, promote
 * @param   string      $cats       分类的ID
 * @param   integer     $brand      品牌的ID
 * @return  array
 */
function get_category_recommend_goods($type = '', $cats = '', $brand = 0)
{
    $brand_where = ($brand > 0) ? " AND g.brand_id = '$brand'" : '';

    $sql =  'SELECT g.goods_id, g.goods_name, g.market_price, g.shop_price AS org_price, g.promote_price, ' .
    "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, ".
    'promote_start, promote_end, g.goods_brief, g.goods_thumb, goods_img, b.brand_name ' .
    'FROM ' . $GLOBALS['ecs']->table('goods') . ' AS g ' .
    'LEFT JOIN ' . $GLOBALS['ecs']->table('brand') . ' AS b ON b.brand_id = g.brand_id ' .
    "LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp ".
    "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' ".
    'WHERE g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 ' . $brand_where;

    $num = 0;
    switch ($type)
    {
        case 'best':
            $num = $GLOBALS['_CFG']['best_number'];
            $sql .= ' AND is_best = 1';
            break;
        case 'new':
            $num = $GLOBALS['_CFG']['new_number'];
            $sql .= ' AND is_new = 1';
            break;
        case 'hot':
            $num = $GLOBALS['_CFG']['hot_number'];
            $sql .= ' AND is_hot = 1';
            break;
        case 'promote':
            $num = $GLOBALS['_CFG']['promote_number'];
            $time = date('Y-m-d');
            $sql .= " AND is_promote = 1 AND promote_start <= '$time' AND promote_end >= '$time'";
            break;
    }

    if (!empty($cats))
    {
        $sql .= " AND (" . $cats . " OR " . get_extension_goods($cats) .")";
    }

    $sql .= ' ORDER BY g.sort_order, g.last_update DESC';
    $res = $GLOBALS['db']->selectLimit($sql, $num);
    $idx = 0;
    $goods = array();
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        if ($row['promote_price'] > 0)
        {
            $promote_price = bargain_price($row['promote_price'], $row['promote_start'], $row['promote_end']);
            $goods[$idx]['promote_price'] = $promote_price > 0 ? price_format($promote_price) : '';
        }
        else
        {
            $goods[$idx]['promote_price'] = '';
        }

        $goods[$idx]['id']           = $row['goods_id'];
        $goods[$idx]['name']         = $row['goods_name'];
        $goods[$idx]['EnUrlName']    = urlencode($row['goods_name']);
        $goods[$idx]['brief']        = $row['goods_brief'];
        $goods[$idx]['brand_name']   = $row['brand_name'];
        $goods[$idx]['short_name']   = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
        sub_str($row['goods_name'], 0, $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
        $goods[$idx]['market_price'] = price_format($row['market_price']);
        $goods[$idx]['shop_price']   = price_format($row['shop_price']);
        $goods[$idx]['thumb']        = empty($row['goods_thumb']) ? $GLOBALS['_CFG']['no_picture'] : $row['goods_thumb'];
        $goods[$idx]['goods_img']    = empty($row['goods_img'])   ? $GLOBALS['_CFG']['no_picture'] : $row['goods_img'];
        $goods[$idx]['url']          = build_uri('goods', array('gid' => $row['goods_id']), $row['goods_name']);

        $idx++;
    }

    return $goods;
}

/**
 * 获得商品的详细信息
 *
 * @access  public
 * @param   integer     $goods_id
 * @return  void
 */
function get_goods_info($goods_id)
{
    $time = date('Y-m-d');
    $sql = "SELECT g.*, c.measure_unit,c.parent_id,c.cat_name, c.guarantee,c.buynote,c.shipping, IF(g.commonsense is not null AND g.commonsense != '', g.commonsense, c.commonsense) AS commonsense, b.brand_id, b.brand_name AS goods_brand, " .
    '(vote_score/vote_times) AS comment_rank ' .
    'FROM ' . $GLOBALS['ecs']->table('goods') . ' AS g, ' .
    $GLOBALS['ecs']->table('category') . ' AS c, ' .
    $GLOBALS['ecs']->table('brand') . ' AS b ' .
    "WHERE g.goods_id = '$goods_id' " .
    "AND g.is_delete = 0 " .
    "AND g.cat_id = c.cat_id  " .
    "AND g.brand_id = b.brand_id ";
    $row = $GLOBALS['db']->getRow($sql);

    if ($row !== false)
    {
        /*  属于那个类别 */
        $sql	=	'select cat_name, buynote, shipping, commonsense from '.$GLOBALS['ecs']->table('category').' where cat_id  ='.$row['parent_id'];
        $parent_row = $GLOBALS['db']->getRow($sql);
        if($parent_row){
            $row['parent_show']	=	$parent_row['cat_name'];
            $row['parent_buynote'] = $parent_row['buynote'];
            $row['parent_shipping'] = $parent_row['shipping'];
            $row['parent_commonsense'] = $parent_row['commonsense'];
        }
        /* 用户评论级别取整 */
        //$row['comment_rank']  	= 	ceil($row['comment_rank']) == 0 ? 5 : ceil($row['comment_rank']);
        $row['comment_rank']  	= 	(int)$row['comment_rank'];

        $row['click_pic']		=	'smile/sm'.$row['click_count'].'.gif';
        /* 获得商品的销售价格 */

        // {{{ by ychen 2008_2_1
        ###$row['market_price']        = price_format($row['market_price']);
        $row['market_price']        	= $row['market_price'];
        $row['market_price_formatted']  = price_format($row['market_price']);
        // }}}

        // add different prices by zwsun 2008/6/12
        if($style_prices =$GLOBALS['db']->getAll("SELECT gs.style_price, gs.style_id, gs.img_url, gs.sale_status, IF (gs.goods_color = '', s.color, gs.goods_color) AS color, IF(gs.style_value != '', gs.style_value , s.value) AS value FROM ".$GLOBALS['ecs']->table('goods_style')." as gs LEFT JOIN ".$GLOBALS['ecs']->table('style')." as s ON gs.style_id = s.style_id  where gs.goods_id = '$goods_id'")) {
            $prices_row = array();
            $price_max = 0;
            $price_min = 9999999;
            foreach ($style_prices as $k => $v) {
                $prices_row[$k]['style_id'] = $v['style_id'];
                $prices_row[$k]['img_url'] = $v['img_url'];
                $prices_row[$k]['color'] = $v['color'];
                $prices_row[$k]['sale_status'] = $v['sale_status'];
                $prices_row[$k]['color_value'] = $v['value'];
                $prices_row[$k]['style_price'] = $v['style_price'];
                $prices_row[$k]['style_price_formatted'] = price_format($v['style_price']);

                if($row['market_price'] > $v['style_price'] && $v['style_price'] > 0) {
                    $prices_row[$k]['save_price'] = floatval($row['market_price']) - floatval($v['style_price']);
                    $prices_row[$k]['save_price_formatted'] = price_format($prices_row[$k]['save_price']);
                }
                if($v['style_price'] > $price_max) {
                    $price_max = $v['style_price'];
                }
                if($v['style_price'] < $price_min) {
                    $price_min = $v['style_price'];
                }
            }
            $row['style_multi'] = true;
            $row['style_prices'] = $prices_row;
            $row['style_price_max'] = $price_max;
            $row['style_price_min'] = $price_min;
        } else {
            $row['style_multi'] = false;
        }

        $row['shop_price_formatted'] = price_format($row['shop_price']);

        /* 修正促销价格 */
        if ($row['promote_price'] > 0)
        {
            $promote_price = bargain_price($row['promote_price'], $row['promote_start'], $row['promote_end']);
        }
        else
        {
            $promote_price = 0;
        }
        $row['promote_price'] =  price_format($promote_price);

        /* 修正重量显示 */
        $row['goods_weight']  = (intval($row['goods_weight']) > 0) ?
        $row['goods_weight'] . $GLOBALS['_LANG']['kilogram'] :
        ($row['goods_weight'] * 1000) . $GLOBALS['_LANG']['gram'];

        /* 修正上架时间显示 */
        $row['add_time']      = date($GLOBALS['_CFG']['date_format'], $row['add_time']);

        /* 促销时间倒计时 */
        $time = date('Y-m-d');
        if ($time >= $row['promote_start'] && $time <= $row['promote_end'])
        {
            $row['gmt_end_time']  = time2gmt(strtotime($row['promote_end'] . ' 23:59:59'));
        }
        else
        {
            $row['gmt_end_time'] = 0;
        }

        /* 是否显示商品库存数量 */
        $row['goods_number']  = ($GLOBALS['_CFG']['use_storage'] == 1) ? $row['goods_number'] : '';

        /* 加密的商品名字 */
        $row['en_goods_name'] = urlencode($row['goods_name']);

        /* 修正商品图片 */
        if (empty($row['goods_img']))
        {
            $row['goods_img'] = $GLOBALS['_CFG']['no_picture'];
        }
        if (empty($row['goods_thumb']))
        {
            $row['goods_thumb'] = $GLOBALS['_CFG']['no_picture'];
        }

        #添加商品反馈积分 以 integral 字段来判断 integral 是产品返回 欧币的 基数
        if ($row['integral'])
        {
            $row['all_return_point'] = getProductReturnNameAndPoint($row['integral']);
        }
        else
        {
            $row['all_return_point'] = array();
        }
        // }}}

        return $row;
    }
    else
    {
        return false;
    }
}

function get_goods_cat_stack($cat_id) {
    $stack = array();
    $index = 0;
    while ($cat_id != 0) {
        $sql = "SELECT cat_id, parent_id, cat_name, guarantee, buynote, shipping, commonsense from " . $GLOBALS['ecs']->table('category')
        . " where cat_id = '$cat_id'";
        $row = $GLOBALS['db']->getRow($sql);
        if ($row) {
            $parent_id = $row['parent_id'];
            $stack[$index]['cat_name'] = $row['cat_name'];
            $stack[$index]['cat_id'] = $row['cat_id'];
            $stack[$index]['parent_id'] = $row['parent_id'];
            $stack[$index]['guarantee'] = $row['guarantee'];
            $stack[$index]['buynote'] = $row['buynote'];
            $stack[$index]['shipping'] = $row['shipping'];
            $stack[$index]['commonsense'] = $row['commonsense'];
            $index++;
            $cat_id = $row['parent_id'];
        } else {
            break;
        }
    }
    return array_reverse($stack);
}

/**
 * 获得商品的属性和规格
 *
 * @access  public
 * @param   integer $goods_id
 * @return  array
 */
function get_goods_properties($goods_id)
{
    /* 获得商品的规格 */
    $sql = 'SELECT a.attr_id, a.attr_name, a.is_linked, a.attr_type, g.goods_attr_id, g.attr_value, g.attr_price ' .
    'FROM ' . $GLOBALS['ecs']->table('goods_attr') . ' AS g ' .
    'LEFT JOIN ' . $GLOBALS['ecs']->table('attribute') . ' AS a ON a.attr_id = g.attr_id ' .
    "WHERE g.goods_id = '$goods_id' " .
    'ORDER BY g.goods_attr_id';
    $res = $GLOBALS['db']->getAll($sql);

    $arr['pro'] = array();     // 属性
    $arr['spe'] = array();     // 规格
    $arr['lnk'] = array();     // 关联的属性

    foreach ($res AS $row)
    {
        //		if ($row['attr_type'] == 0)
        //		{
        $arr['pro'][$row['attr_id']]['name']  = $row['attr_name'];
        $arr['pro'][$row['attr_id']]['value'] = $row['attr_value'];
        //		}
        //		else
        //		{
        //			$arr['spe'][$row['attr_id']]['name']     = $row['attr_name'];
        //			$arr['spe'][$row['attr_id']]['values'][] = array(
        //			'label'        => $row['attr_value'],
        //			'price'        => $row['attr_price'],
        //			'format_price' => price_format($row['attr_price']),
        //			'id'           => $row['goods_attr_id']);
        //		}

        if ($row['is_linked'] == 1)
        {
            /* 如果该属性需要关联，先保存下来 */
            $arr['lnk'][$row['attr_id']]['name']  = $row['attr_name'];
            $arr['lnk'][$row['attr_id']]['value'] = $row['attr_value'];
        }
    }

    return $arr;
}

/**
 * 获得属性相同的商品
 *
 * @access  public
 * @param   array   $attr   // 包含了属性名称,ID的数组
 * @return  array
 */
function get_same_attribute_goods($attr)
{
    $lnk = array();

    if (!empty($attr))
    {
        foreach ($attr['lnk'] AS $key => $val)
        {
            $lnk[$key]['title'] = sprintf($GLOBALS['_LANG']['same_attrbiute_goods'], $val['name'], $val['value']);

            /* 查找符合条件的商品 */
            $sql = 'SELECT g.goods_id, g.goods_name, g.goods_thumb, g.goods_img, g.shop_price AS org_price, ' .
            "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, ".
            'g.market_price, g.promote_price, g.promote_start, g.promote_end ' .
            'FROM ' . $GLOBALS['ecs']->table('goods') . ' AS g ' .
            'LEFT JOIN ' . $GLOBALS['ecs']->table('goods_attr') . ' as a ON g.goods_id = a.goods_id ' .
            "LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp ".
            "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' ".
            "WHERE a.attr_id = '$key' AND a.attr_value = '$val[value]' AND g.goods_id <> '$_REQUEST[id]' " .
            'LIMIT ' . $GLOBALS['_CFG']['attr_related_number'];
            $res = $GLOBALS['db']->getAll($sql);

            foreach ($res AS $row)
            {
                $lnk[$key]['goods'][$row['goods_id']]['goods_id']      = $row['goods_id'];
                $lnk[$key]['goods'][$row['goods_id']]['goods_name']    = $row['goods_name'];
                $lnk[$key]['goods'][$row['goods_id']]['short_name']    = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
                sub_str($row['goods_name'], 0, $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
                $lnk[$key]['goods'][$row['goods_id']]['goods_img']     = (empty($row['goods_img'])) ? $GLOBALS['_CFG']['no_picture'] : $row['goods_img'];
                $lnk[$key]['goods'][$row['goods_id']]['market_price']  = price_format($row['market_price']);
                $lnk[$key]['goods'][$row['goods_id']]['shop_price']    = price_format($row['shop_price']);
                $lnk[$key]['goods'][$row['goods_id']]['promote_price'] = bargain_price($row['promote_price'],
                $row['promote_start'], $row['promote_end']);
                $lnk[$key]['goods'][$row['goods_id']]['url']           = build_uri('goods', array('gid' => $row['goods_id']), $row['goods_name']);
            }
        }
    }

    return $lnk;
}

/**
 * 获得指定商品的相册
 *
 * @access  public
 * @param   integer     $goods_id
 * @return  array
 */
function get_goods_gallery($goods_id,$startSize,$iSize){
    $sql = 'SELECT img_id, img_url, thumb_url, img_desc' .
    ' FROM ' . $GLOBALS['ecs']->table('goods_gallery') .
    " WHERE goods_id = '$goods_id' LIMIT ".$startSize .','.$iSize;
    $row = $GLOBALS['db']->getAll($sql);

    return $row;
}

/**
 * 获得指定商品的相册的个数
 *
 * @access  public
 * @param   integer     $goods_id
 * @return  array
 */
function get_goods_gallery_count($goods_id){
    $sql = 'SELECT  count(*) as gallery_count' .
    ' FROM ' . $GLOBALS['ecs']->table('goods_gallery') .
    " WHERE goods_id = '$goods_id'";
    $row = $GLOBALS['db']->getOne($sql);
    return $row;
}


/**
 * 获得指定分类下的商品
 *
 * @access  public
 * @param   integer     $cat_id     分类ID
 * @param   integer     $num        数量
 * @return  array
 */
function assign_cat_goods($cat_id, $num)
{
    $children = get_children($cat_id);

    $sql = 'SELECT g.goods_id, g.goods_name, g.market_price, g.shop_price AS org_price, ' .
    "IFNULL(mp.user_price, g.shop_price * '$_SESSION[discount]') AS shop_price, ".
    'g.promote_price, promote_start, promote_end, g.goods_brief, g.goods_thumb, g.goods_img ' .
    "FROM " . $GLOBALS['ecs']->table('goods') . ' AS g '.
    "LEFT JOIN " . $GLOBALS['ecs']->table('member_price') . " AS mp ".
    "ON mp.goods_id = g.goods_id AND mp.user_rank = '$_SESSION[user_rank]' ".
    'WHERE g.is_on_sale = 1 AND g.is_alone_sale = 1 AND '.
    'g.is_delete = 0 AND (' . $children . 'OR ' . get_extension_goods($children) . ') ' .
    'ORDER BY g.sort_order, g.goods_id DESC LIMIT ' . $num;
    $res = $GLOBALS['db']->getAll($sql);

    $goods = array();
    foreach ($res AS $idx => $row)
    {
        if ($row['promote_price'] > 0)
        {
            $promote_price = bargain_price($row['promote_price'], $row['promote_start'], $row['promote_end']);
            $goods[$idx]['promote_price'] = $promote_price > 0 ? price_format($promote_price) : '';
        }
        else
        {
            $goods[$idx]['promote_price'] = '';
        }

        $goods[$idx]['id']           = $row['goods_id'];
        $goods[$idx]['name']         = $row['goods_name'];
        $goods[$idx]['brief']        = $row['goods_brief'];
        $goods[$idx]['market_price'] = $row['market_price'];
        $goods[$idx]['short_name']   = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
        sub_str($row['goods_name'], 0, $GLOBALS['_CFG']['goods_name_length']) : $row['goods_name'];
        $goods[$idx]['shop_price']   = price_format($row['shop_price']);
        $goods[$idx]['thumb']        = empty($row['goods_thumb']) ? $GLOBALS['_CFG']['no_picture'] : $row['goods_thumb'];
        $goods[$idx]['goods_img']    = empty($row['goods_img']) ? $GLOBALS['_CFG']['no_picture'] : $row['goods_img'];
        $goods[$idx]['url']          = build_uri('goods', array('gid' => $row['goods_id']), $row['goods_name']);
    }

    $GLOBALS['smarty']->assign('cat_goods_' . $cat_id, $goods);

    /* 分类信息 */
    $sql = 'SELECT cat_name FROM ' . $GLOBALS['ecs']->table('category') . " WHERE cat_id = '$cat_id'";
    $cat['name'] = $GLOBALS['db']->getOne($sql);
    $cat['url']  = build_uri('category', array('cid' => $cat_id), $cat['name']);
    $cat['id']   = $cat_id;

    return $cat;
}

/**
 * 获得指定的品牌下的商品
 *
 * @access  public
 * @param   integer     $brand_id       品牌的ID
 * @param   integer     $num            数量
 * @param   integer     $cat_id         分类编号
 * @return  void
 */
function assign_brand_goods($brand_id,$good_id, $num, $cat_id = 0)
{
    $sql = 'SELECT g.goods_id, g.goods_name, g.market_price, g.shop_price AS shop_price, ' .
    'g.promote_price, g.promote_start, g.promote_end, g.goods_brief, g.goods_thumb, g.goods_img ' .
    'FROM ' . $GLOBALS['ecs']->table('goods') . ' AS g ' .
    "WHERE g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 AND g.goods_id !='$good_id' AND g.cat_id = '$cat_id' AND g.brand_id = '$brand_id'";

    $sql2 =  'SELECT g.goods_id, g.goods_name, g.market_price, g.shop_price AS shop_price, ' .
    'g.promote_price, g.promote_start, g.promote_end, g.goods_brief, g.goods_thumb, g.goods_img ' .
    'FROM ' . $GLOBALS['ecs']->table('goods') . ' AS g ' .
    "WHERE g.is_on_sale = 1 AND g.is_alone_sale = 1 AND g.is_delete = 0 AND g.goods_id !='$good_id' AND g.cat_id != '$cat_id' AND g.brand_id = '$brand_id'";


    $sql .= ' ORDER BY g.sort_order, g.goods_id DESC';
    $sql2 .= ' ORDER BY g.sort_order, g.goods_id DESC';

    $res = $GLOBALS['db']->selectLimit($sql, $num);

    $idx = 0;
    $goods = array();
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        if ($row['promote_price'] > 0)
        {
            $promote_price = bargain_price($row['promote_price'], $row['promote_start'], $row['promote_end']);
        }
        else
        {
            $promote_price = 0;
        }

        $goods[$idx]['id']            = $row['goods_id'];
        $goods[$idx]['name']          = $row['goods_name'];
        $goods[$idx]['short_name']    = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
        sub_str($row['goods_name'], 0, $GLOBALS['_CFG']['goods_name_length']) :
        $row['goods_name'];
        $goods[$idx]['market_price']  = $row['market_price'];
        $goods[$idx]['shop_price']    = price_format($row['shop_price']);
        $goods[$idx]['promote_price'] = $promote_price > 0 ? price_format($promote_price) : '';
        $goods[$idx]['brief']         = $row['goods_brief'];
        $goods[$idx]['thumb']         = empty($row['goods_thumb']) ?
        $GLOBALS['_CFG']['no_picture'] : $row['goods_thumb'];
        $goods[$idx]['goods_img']     = empty($row['goods_img']) ?
        $GLOBALS['_CFG']['no_picture'] : $row['goods_img'];
        $goods[$idx]['url']           = build_uri('goods', array('gid' => $row['goods_id']), $row['goods_name']);

        $idx++;
    }

    if ($idx < $num) {
        $res = $GLOBALS['db']->selectLimit($sql2, $num - $idx);
        while (($row = $GLOBALS['db']->fetchRow($res)) && $idx < $num)
        {
            if ($row['promote_price'] > 0)
            {
                $promote_price = bargain_price($row['promote_price'], $row['promote_start'], $row['promote_end']);
            }
            else
            {
                $promote_price = 0;
            }

            $goods[$idx]['id']            = $row['goods_id'];
            $goods[$idx]['name']          = $row['goods_name'];
            $goods[$idx]['short_name']    = $GLOBALS['_CFG']['goods_name_length'] > 0 ?
            sub_str($row['goods_name'], 0, $GLOBALS['_CFG']['goods_name_length']) :
            $row['goods_name'];
            $goods[$idx]['market_price']  = $row['market_price'];
            $goods[$idx]['shop_price']    = price_format($row['shop_price']);
            $goods[$idx]['promote_price'] = $promote_price > 0 ? price_format($promote_price) : '';
            $goods[$idx]['brief']         = $row['goods_brief'];
            $goods[$idx]['thumb']         = empty($row['goods_thumb']) ?
            $GLOBALS['_CFG']['no_picture'] : $row['goods_thumb'];
            $goods[$idx]['goods_img']     = empty($row['goods_img']) ?
            $GLOBALS['_CFG']['no_picture'] : $row['goods_img'];
            $goods[$idx]['url']           = build_uri('goods', array('gid' => $row['goods_id']), $row['goods_name']);

            $idx++;
        }
    }

    /* 分类信息 */
    $sql = 'SELECT brand_name FROM ' . $GLOBALS['ecs']->table('brand') . " WHERE brand_id = '$brand_id'";

    $brand['id']   = $brand_id;
    $brand['name'] = $GLOBALS['db']->getOne($sql);
    $brand['url']  = build_uri('brand', array('bid' => $brand_id), $brand['name']);

    $brand_goods = array('brand' => $brand, 'goods' => $goods);

    return $brand_goods;
}

/**
 * 获得所有扩展分类属于指定分类的所有商品ID
 *
 * @access  public
 * @param   string $cat_id     分类查询字符串
 * @return  string
 */
function get_extension_goods($cats)
{
    $sql = 'SELECT goods_id FROM ' . $GLOBALS['ecs']->table('goods_cat') . " AS g WHERE $cats";
    $row = $GLOBALS['db']->getCol($sql);

    return db_create_in($row, 'g.goods_id');
}

/**
 * 判断某个商品是否正在特价促销期
 *
 * @access  public
 * @param   float   $price      促销价格
 * @param   string  $start      促销开始日期
 * @param   string  $end        促销结束日期
 * @return  float   如果还在促销期则返回促销价，否则返回0
 */
function bargain_price($price, $start, $end)
{
    if ($price == 0)
    {
        return 0;
    }
    else
    {
        $time = date('Y-m-d');
        if ($time >= $start && $time <= $end)
        {
            return $price;
        }
        else
        {
            return 0;
        }
    }
}

/**
 * 获得指定的规格的价格
 *
 * @access  public
 * @param   mix     $spec   规格ID的数组或者逗号分隔的字符串
 * @return  void
 */
function spec_price($spec)
{
    if (!empty($spec))
    {
        $where = db_create_in($spec, 'goods_attr_id');

        $sql = 'SELECT SUM(attr_price) AS attr_price FROM ' . $GLOBALS['ecs']->table('goods_attr') . " WHERE $where";
        $price = floatval($GLOBALS['db']->getOne($sql));
    }
    else
    {
        $price = 0;
    }

    return $price;
}

/**
 * 取得团购活动信息
 * @param   int     $group_buy_id   团购活动id
 * @param   int     $current_num    本次购买数量（计算当前价时要加上的数量）
 * @return  array
 *                  status          状态：
 */
function group_buy_info($group_buy_id, $current_num = 0)
{
    /* 取得团购活动信息 */
    $group_buy_id = intval($group_buy_id);
    $sql = "SELECT b.*, g.goods_name, g.is_on_sale, g.is_alone_sale " .
    "FROM " . $GLOBALS['ecs']->table('group_buy') . " AS b, " .
    $GLOBALS['ecs']->table('goods') . " AS g " .
    "WHERE b.goods_id = g.goods_id " .
    "AND b.group_buy_id = '$group_buy_id' " .
    "AND g.is_delete = 0";
    $group_buy = $GLOBALS['db']->getRow($sql);

    /* 如果为空，返回空数组 */
    if (empty($group_buy))
    {
        return array();
    }

    /* 格式化时间 */
    $group_buy['formated_start_date'] = date('Y-m-d H:i', $group_buy['start_date']);
    $group_buy['formated_end_date'] = date('Y-m-d H:i', $group_buy['end_date']);

    /* 格式化保证金 */
    $group_buy['formated_deposit'] = price_format($group_buy['deposit']);

    /* 处理价格阶梯 */
    $price_ladder = unserialize($group_buy['price_ladder']);
    if (!is_array($price_ladder) || empty($price_ladder))
    {
        $price_ladder = array(array('amount' => 0, 'price' => 0));
    }
    else
    {
        foreach ($price_ladder as $key => $amount_price)
        {
            $price_ladder[$key]['formated_price'] = price_format($amount_price['price']);
        }
    }
    $group_buy['price_ladder'] = $price_ladder;

    /* 统计信息 */
    $stat = group_buy_stat($group_buy_id, $group_buy['deposit']);
    $group_buy = array_merge($group_buy, $stat);

    /* 计算当前价 */
    $cur_price  = $price_ladder[0]['price']; // 初始化
    $cur_amount = $stat['valid_goods'] + $current_num; // 当前数量
    foreach ($price_ladder as $amount_price)
    {
        if ($cur_amount >= $amount_price['amount'])
        {
            $cur_price = $amount_price['price'];
        }
        else
        {
            break;
        }
    }
    $group_buy['cur_price'] = $cur_price;
    $group_buy['formated_cur_price'] = price_format($cur_price);

    /* 最终价 */
    $group_buy['trans_price'] = $group_buy['cur_price'];
    $group_buy['formated_trans_price'] = $group_buy['formated_cur_price'];

    /* 状态 */

    $group_buy['status'] = group_buy_status($group_buy);

    if (isset($GLOBALS['_LANG']['gbs'][$group_buy['status']]))
    {
        $group_buy['status_desc'] = $GLOBALS['_LANG']['gbs'][$group_buy['status']];
    }

    return $group_buy;
}

/*
* 取得某团购活动统计信息
* @param   int     $group_buy_id   团购活动id
* @param   float   $deposit        保证金
* @return  array   统计信息
*                  total_order     总订单数
*                  total_goods     总商品数
*                  valid_order     有效订单数
*                  valid_goods     有效商品数
*/
function group_buy_stat($group_buy_id, $deposit)
{
    /* 取得总订单数和总商品数 */
    $group_buy_id = intval($group_buy_id);
    $sql = "SELECT COUNT(*) AS total_order, SUM(g.goods_number) AS total_goods " .
    "FROM " . $GLOBALS['ecs']->table('order_info') . " AS o, " .
    $GLOBALS['ecs']->table('order_goods') . " AS g " .
    " WHERE o.order_id = g.order_id " .
    "AND o.extension_code = 'group_buy' " .
    "AND o.extension_id = '$group_buy_id' " .
    "AND (order_status = '" . OS_CONFIRMED . "' OR order_status = '" . OS_UNCONFIRMED . "')";
    $stat = $GLOBALS['db']->getRow($sql);
    if ($stat['total_order'] == 0)
    {
        $stat['total_goods'] = 0;
    }

    /* 取得有效订单数和有效商品数 */
    $deposit = floatval($deposit);
    if ($deposit > 0 && $stat['total_order'] > 0)
    {
        $sql .= " AND (o.money_paid + o.surplus) >= '$deposit'";
        $row = $GLOBALS['db']->getRow($sql);
        $stat['valid_order'] = $row['total_order'];
        if ($stat['valid_order'] == 0)
        {
            $stat['valid_goods'] = 0;
        }
        else
        {
            $stat['valid_goods'] = $row['total_goods'];
        }
    }
    else
    {
        $stat['valid_order'] = $stat['total_order'];
        $stat['valid_goods'] = $stat['total_goods'];
    }

    return $stat;
}

function	get_brand_name($ibrand){
    if($ibrand){
        $sql = 'SELECT  * from '.$GLOBALS['ecs']->table("brand").' where brand_id ='.(int)$ibrand;
        return  $GLOBALS['db']->getRow($sql);
    }
}
function get_cate_name($cat_id){
    $cat_id = (int) $cat_id;
    if($cat_id){
        $sql = 'SELECT  * from '.$GLOBALS['ecs']->table("category").' where cat_id ='.(int)$cat_id;
        return  $GLOBALS['db']->getRow($sql);
    }
}

/**
 * 获得团购的状态
 *
 * @access  public
 * @param   array
 * @return  integer
 */
function group_buy_status($group_buy)
{
    $now = time();
    if ($group_buy['is_finished'] == 0)
    {
        /* 未处理 */
        if ($now < $group_buy['start_date'])
        {
            $status = GBS_PRE_START;
        }
        elseif ($now > $group_buy['end_date'])
        {
            $status = GBS_FINISHED;
        }
        else
        {
            if ($group_buy['restrict_amount'] == 0 || $group_buy['valid_goods'] < $group_buy['restrict_amount'])
            {
                $status = GBS_UNDER_WAY;
            }
            else
            {
                $status = GBS_FINISHED;
            }
        }
    }
    elseif ($group_buy['is_finished'] == GBS_SUCCEED)
    {
        /* 已处理，团购成功 */
        $status = GBS_SUCCEED;
    }
    elseif ($group_buy['is_finished'] == GBS_FAIL)
    {
        /* 已处理，团购失败 */
        $status = GBS_FAIL;
    }

    return $status;
}


/**
 * 根据商品积分返回各类角色反馈积分
 * @param int $productIntegral
 * @return array 返回 角色名－积分 数组
 */
function getProductReturnNameAndPoint($productIntegral = 0, $number = 1) {
    $rankConfig = $GLOBALS['_CFG']['rank_config'];
    //$currency_scale = $GLOBALS['_CFG']['currency_scale'];
    $productRank = Array();
    foreach ($rankConfig as $key => $rank) {
        $productRank[$key]['sub_rank_name'] = sub_str($rank['rank_name'], 2, strlen($rank['rank_name']), false);	// zandy 2007-12-27
        $productRank[$key]['rank_name'] = $rank['rank_name'];
        $productRank[$key]['rank_id'] = $rank['rank_id'];
        $productRank[$key]['rank_points'] = $number * ceil($productIntegral * $rank['discount'] / 100);
    }
    return $productRank;
}

/**
 * 根据商品积分和用户等级返回反馈积分
 * @param int $productPrice
 * @param int $rank_id
 * @return int 返回 反馈积分 
 */
function getProductReturnPoint($productIntegral, $rank_id) {
    $rankConfig = $GLOBALS['_CFG']['rank_config'];
    if ($rank_id > 0) {
        if(is_array($rankConfig)){
            foreach ($rankConfig as $rank) {
                if ($rank['rank_id'] == $rank_id) {
                    return ceil($productIntegral * $rank['discount'] / 100);
                }
            }
        }
    } else {
        return ceil($productIntegral * $rank['discount'] / 100);
    }
    return 0;
}

function getSamePriceLevelGoods($goods_id, $top_cat_id, $price, $num = 5, $price_range = 200) {
    if ($top_cat_id <= 0 || $price <= 0) {
        return null;
    }

    $up_price = $price + $price_range;
    $low_price = $price - $price_range;

    $sql = "SELECT goods_id, goods_name, goods_thumb, shop_price, market_price FROM " . $GLOBALS['ecs']->table('goods') . " WHERE is_on_sale = 1 AND is_delete = 0 AND is_display = 1 AND top_cat_id = '$top_cat_id' AND goods_id != '$goods_id' AND shop_price > '$low_price' AND shop_price < '$up_price' limit $num";

    $goods_list = $GLOBALS['db']->getAll($sql);

    foreach ($goods_list as $key => $goods) {
        $goods_list[$key]['market_price_formatted'] = price_format($goods['market_price']);
        $goods_list[$key]['shop_price_formatted'] = price_format($goods['shop_price']);
    }

    return $goods_list;
}


/**
 * 获得指定商品的配件
 * @access  public
 * @param   integer $goods_id 主商品id $is_free 判断是否含有0元配件 
 * @return  array 
 */

function get_goods_fittings($goods_id, $is_free=false)
{
    global $ecs;
    $goods_id =intval($goods_id);

    if($is_free){
        $condition = " AND gg.goods_price=0";
    } else {
        $condition = "";
    }
    
    $sql = "SELECT cat_id FROM {$ecs->table('goods')} WHERE goods_id = '{$goods_id}' ";
    $parent_cat_id = $GLOBALS['db']->getOne($sql);
    
    $sql = "SELECT gg.group_goods_id, gg.seq AS seq, IFNULL(gg.name, g.goods_name) AS group_name, 
        g.goods_id, g.cat_id, c.parent_id AS parent_cat_id, gg.goods_price, g.goods_name, 
        g.goods_thumb, g.goods_img, g.shop_price AS org_price, 0 as store_goods_id, 0 as style_id".
    " FROM {$ecs->table('group_goods')}  AS gg, 
           {$ecs->table('goods')} AS g , 
           {$ecs->table('category')} AS c ".
    " WHERE c.cat_id = g.cat_id and g.goods_id = gg.goods_id 
	   AND gg.parent_store_id = 0 and gg.child_store_id = 0
	   $condition 
	   AND (gg.parent_id = {$goods_id} OR gg.parent_cat_id = '$parent_cat_id' )
	   AND g.is_delete = 0 AND g.is_on_sale = 1 
	   AND g.is_display = 1 AND g.sale_status = 'normal' 
	   ORDER BY seq ";

    $res = $GLOBALS['db']->getAll($sql);
    
    foreach ($res as $key => $value) {
        $res[$key]["formatted_goods_price"] = price_format($value['goods_price']);
        $res[$key]["formatted_org_price"] = price_format($value['org_price']);
    }

    return $res;
}

?>
