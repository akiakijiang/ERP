<?php
define('IN_ECS', true);
require('includes/init.php');

$goods_id = isset($_REQUEST['goods_id']) ? intval($_REQUEST['goods_id']) : 0;
$type = isset($_GET['type']) ? strval($_GET['type']) : 'b';

// 店铺数组
$shop_detail = array(
    'c' => array(
        'intro' => '<img src="http://img1.oukoo.com/upimg/ouku/Image/taobao/ouku-ds-banner.jpg" width="740" alt="欧酷数码专营店"/><br/><br/><img src="http://img1.oukoo.com/upimg/ouku/Image/taobao/ouku-ds-bar.jpg" width="740" alt="商品介绍"/>',
        'bp'    => '<p><img src="http://img1.oukoo.com/upimg/ouku/Image/taobao/ouku-dsbp-bar.jpg" width="740" alt="标配"/></p>',
        'cs'    => '<p align="center"><img src="http://img1.oukoo.com/upimg/ouku/Image/taobao/ouku-dscs-bar.jpg" width="740" alt="商品参数"/></p>'
    ), //淘宝商城欧酷数码专营店
    'hp' => array(
        'intro' => '<img src="http://img1.oukoo.com/upimg/ouku/Image/taobao/hp-ds-banner.jpg" width="740" alt="惠普笔记本官方授权店" /><br/><br/><img src="http://img1.oukoo.com/upimg/ouku/Image/taobao/hp-ds-bar.jpg" width="740" alt="商品介绍"/>',
        'bp'    => '<p><img src="http://img1.oukoo.com/upimg/ouku/Image/taobao/hp-dsbp-bar.jpg" width="740" alt="标配"/></p>',
        'cs'    => '<p align="center"><img src="http://img1.oukoo.com/upimg/ouku/Image/taobao/hp-dscs-bar.jpg" width="740" alt="商品参数"/></p>'
    ),
    'b' => array(
        'intro' => '<IMG height=228 src="http://img1.oukoo.com/upimg/ouku/Image/taobao/p-top.png" width=740 useMap=#Map border=0></P> <MAP id=Map name=Map><AREA shape=RECT coords=533,131,625,167 href="http://favorite.taobao.com/popup/add_collection.htm?itemid=58969643&amp;itemtype=0&amp;ownerid=a0dacfec60eef92fbedac5e02a825357"><AREA shape=RECT coords=628,131,733,168 href="http://oksm.mall.taobao.com/shop/xshop/wui_page-20182073.htm"><AREA shape=RECT coords=528,170,625,204 href="http://rate.taobao.com/user-rate-a0dacfec60eef92fbedac5e02a825357.htm">
<AREA shape=RECT coords=635,171,731,205 href="http://item.taobao.com/auction/item_detail-2-50fae73ca89d7f6f9dd67700998f21b6.htm"></MAP>
',
    ),
);

$intro = $shop_detail[$type]['intro'];
$bp = $shop_detail[$type]['bp'];
$cs = $shop_detail[$type]['cs'];
$smarty->assign('intro',$intro);
$smarty->assign('bp', $bp);
$smarty->assign('cs', $cs);
$row = $db->getRow("SELECT seller_note,top_cat_id FROM ecs_goods WHERE goods_id='{$goods_id}'");
$smarty->assign('seller_note', $row['seller_note']);
$smarty->assign('top_cat_id', $row['top_cat_id']);
if ($row['top_cat_id'] == 1) {
    $properties['pro'] = get_goods_new_properties($goods_id);
}else{
    $properties = get_goods_properties($goods_id);
}
$goods_gallery = $db->getAll("SELECT img_url FROM ecs_goods_gallery WHERE goods_id='{$goods_id}' AND is_display = 'Y' ORDER BY sequence"); 
$smarty->assign('gallery', $goods_gallery);
$pro = $properties['pro']; //商品的属性

$smarty->assign('properties', $pro);

$smarty->display('oukooext/goods_properties.dwt');

function get_goods_properties($goods_id)
{
    global $db;
	/* 获得商品的规格 */
	$sql = 'SELECT a.attr_id, a.attr_name, a.is_linked, a.attr_type, g.goods_attr_id, g.attr_value, g.attr_price ' .
	'FROM ' . $GLOBALS['ecs']->table('goods_attr') . ' AS g ' .
	'LEFT JOIN ' . $GLOBALS['ecs']->table('attribute') . ' AS a ON a.attr_id = g.attr_id ' .
	"WHERE g.goods_id = '$goods_id' " .
	'AND a.is_new_version = 0 AND g.is_new_version = 0 '.
	'ORDER BY g.goods_attr_id';
    $res = $db->getAll($sql);
//    $res = $GLOBALS['db']->getAll($sql);
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

function array_del($arrayInfo)
{
    if(is_array($arrayInfo)){
        foreach ($arrayInfo as $key =>$value){
            if(strval(substr($value['name'],0,1))==".")
            {
                $arrayInfo[$key]['tag']	=	substr($value['name'],1);
                $arTag[]				=	$arrayInfo[$key];
            }
            if(substr($value['name'],0,1) == "#"){
                $arrayInfo[$key]['title']	=	substr($value['name'],1);
            }
            if(strval(substr($value['name'],0,1))=="*"){
                $arrayInfo[$key]['add']	=	substr($value['name'],1);
            }
        }
        $arrayInfo['tag'] 	=	$arTag;

        return $arrayInfo;
    }
}
function get_goods_new_properties($goods_id)
{
    global $db, $ecs;
	/* 获得商品的规格 */
    $sql = "SELECT a.attr_id, a.attr_name, a.is_linked, a.attr_type, a.attr_values,a.parent_id, g.goods_attr_id, g.attr_value, g.attr_price
            FROM {$ecs->table('goods_attr')} AS g
            LEFT JOIN {$ecs->table('attribute')} AS a ON a.attr_id = g.attr_id
	        WHERE g.goods_id = '$goods_id'
            AND a.is_new_version = 1 AND g.is_new_version = 1
            AND g.is_delete = 0
            AND g.is_show = 1 AND a.is_show = 1
            AND a.is_delete = 0
	        ORDER BY a.sort_order DESC
            ";
    $res = $db->getAll($sql);
    //pp($res);
    // attr_values 有 category, checkbox, radio, select, text
    // 从模块中取值分别是 checkbox, radio, select
    $attr_value_ids = array();
    //使用attr_id作为key
    $pro = array(); //存储模版中拥有的固定变量attr_value_id
    $arr = array();
    foreach ($res AS $k => $v) {
        if (in_array($v['attr_values'], array('checkbox', 'radio', 'select')) && $v['attr_value'] != ''){
            $attr_value_ids[] = $v['attr_value'];
        }
        
        if ($v['parent_id'] == 0) {
            $arr[] = $v;
        }else{
            $pro[$v['attr_id']] = $v;
        }
    }
    
    // 根据attr_value_id查找模版的值 
    $tmp_value = array();
    if ($attr_value_ids) {
        $attr_value_ids = implode(',', $attr_value_ids);
        $sql = "SELECT attr_value,attr_value_id, attr_id FROM ecs_attr_value WHERE is_delete = 0 
                AND attr_value_id IN ({$attr_value_ids})";
        $tmp_value = $db->getAll($sql);
    }

    // 模版的值合并到原来的数组
    foreach ($pro AS $k =>$v) {
        $attr_value_ids = explode(',', $v['attr_value']);
        $t = array();
        foreach ($tmp_value AS $kk => $vv) {
            if (in_array($vv['attr_value_id'], $attr_value_ids)) {
                $t[] = $vv['attr_value'];
                $pro[$k]['attr_value'] = implode(',', $t);
            }
        }
    }
    
    // 根据parent_id构造三级
    foreach ($arr AS $k => $v) {
        foreach ($pro AS $kk => $vv) {
            if ($vv['parent_id'] == $v['attr_id']){
                $arr[$k]['child'][$vv['attr_id']] = $vv;
            }
        }
    }

    foreach ($arr AS $k => $v) {
        if (!$v['child']) continue;
        foreach ($v['child'] AS $kk => $vv) {
            foreach($pro AS $kkk => $vvv){
                if ($vvv['parent_id'] == $vv['attr_id']){
                    $arr[$k]['child'][$vv['attr_id']]['child'][] = $vvv;
                }
            }
        }
    }
	return $arr;
}

