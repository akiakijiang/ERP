<?php

/**
 * ECSHOP 管理中心商品相关函数
 * ============================================================================
 * 版权所有 (C) 2005-2007 北京亿商互动科技发展有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com
 * ----------------------------------------------------------------------------
 * 这是一个免费开源的软件；这意味着您可以在不用于商业目的的前提下对程序代码
 * 进行修改、使用和再发布。
 * ============================================================================
 * @author:     wj <wjzhangq@126.com>
 * @version:    v2.1
 * ---------------------------------------------
 * $Author: weberliu $
 * $Date: 2007-06-19 11:13:24 +0800 (星期二, 19 六月 2007) $
 * $Id: lib_goods.php 9307 2007-06-19 03:13:24Z weberliu $
 */

if (! defined ( 'IN_ECS' )) {
	die ( 'Hacking attempt' );
}

/**
 * 取得推荐类型列表
 * @return  array   推荐类型列表
 */
function get_intro_list() {
	return array ('is_best' => $GLOBALS ['_LANG'] ['is_best'], 'is_new' => $GLOBALS ['_LANG'] ['is_new'], 'is_hot' => $GLOBALS ['_LANG'] ['is_hot'], 'is_promote' => $GLOBALS ['_LANG'] ['is_promote'] );
}

/**
 * 取得重量单位列表
 * @return  array   重量单位列表
 */
function get_unit_list() {
	return array ('1' => $GLOBALS ['_LANG'] ['unit_kg'], '0.001' => $GLOBALS ['_LANG'] ['unit_g'] );
}

/**
 * 取得会员等级列表
 * @return  array   会员等级列表
 */
function get_user_rank_list() {
	$sql = "SELECT * FROM " . $GLOBALS ['ecs']->table ( 'user_rank' ) . " ORDER BY min_points";
	
	return $GLOBALS ['db']->getAll ( $sql );
}

/**
 * 取得某商品的会员价格列表
 * @param   int     $goods_id   商品编号
 * @return  array   会员价格列表 user_rank => user_price
 */
function get_member_price_list($goods_id) {
	/* 取得会员价格 */
	$price_list = array ();
	$sql = "SELECT user_rank, user_price FROM " . $GLOBALS ['ecs']->table ( 'member_price' ) . " WHERE goods_id = '$goods_id'";
	$res = $GLOBALS ['db']->query ( $sql );
	while ( $row = $GLOBALS ['db']->fetchRow ( $res ) ) {
		$price_list [$row ['user_rank']] = $row ['user_price'];
	}
	
	return $price_list;
}

/**
 * 插入或更新商品属性
 * @param   int     $goods_id       商品编号
 * @param   array   $id_list        属性编号数组
 * @param   array   $is_spec_list   是否规格数组 'true' | 'false'
 * @param   array   $value_price_list   属性值数组
 */
function handle_goods_attr($goods_id, $id_list, $is_spec_list, $value_price_list) {
	/* 循环处理每个属性 */
	foreach ( $id_list as $key => $id ) {
		$is_spec = $is_spec_list [$key];
		if ($is_spec == 'false') {
			$value = $value_price_list [$key];
			$price = '';
		} else {
			$value_list = array ();
			$price_list = array ();
			if ($value_price_list [$key]) {
				$vp_list = explode ( chr ( 13 ), $value_price_list [$key] );
				foreach ( $vp_list as $v_p ) {
					$arr = explode ( chr ( 9 ), $v_p );
					$value_list [] = $arr [0];
					$price_list [] = $arr [1];
				}
			}
			$value = join ( chr ( 13 ), $value_list );
			$price = join ( chr ( 13 ), $price_list );
		}
		
		// 插入或更新记录
		$sql = "SELECT COUNT(*) FROM " . $GLOBALS ['ecs']->table ( 'goods_attr' ) . " WHERE goods_id = '$goods_id' AND attr_id = '$id'";
		if ($GLOBALS ['db']->getOne ( $sql ) > 0) {
			$sql = "UPDATE " . $GLOBALS ['ecs']->table ( 'goods_attr' ) . " SET " . "attr_value = '$value', " . "attr_price = '$price' " . "WHERE goods_id = '$goods_id' " . "AND attr_id = '$id' LIMIT 1";
		} else {
			$sql = "INSERT INTO " . $GLOBALS ['ecs']->table ( 'goods_attr' ) . " (goods_id, attr_id, attr_value, attr_price) " . "VALUES ('$goods_id', '$id', '$value', '$price')";
		}
		$GLOBALS ['db']->query ( $sql );
	}
}

/**
 * 保存某商品的会员价格
 * @param   int     $goods_id   商品编号
 * @param   array   $rank_list  等级列表
 * @param   array   $price_list 价格列表
 * @return  void
 */
function handle_member_price($goods_id, $rank_list, $price_list) {
	/* 循环处理每个会员等级 */
	foreach ( $rank_list as $key => $rank ) {
		/* 会员等级对应的价格 */
		$price = $price_list [$key];
		
		// 插入或更新记录
		$sql = "SELECT COUNT(*) FROM " . $GLOBALS ['ecs']->table ( 'member_price' ) . " WHERE goods_id = '$goods_id' AND user_rank = '$rank'";
		if ($GLOBALS ['db']->getOne ( $sql ) > 0) {
			$sql = "UPDATE " . $GLOBALS ['ecs']->table ( 'member_price' ) . " SET user_price = '$price' " . "WHERE goods_id = '$goods_id' " . "AND user_rank = '$rank' LIMIT 1";
		} else {
			$sql = "INSERT INTO " . $GLOBALS ['ecs']->table ( 'member_price' ) . " (goods_id, user_rank, user_price) " . "VALUES ('$goods_id', '$rank', '$price')";
		}
		$GLOBALS ['db']->query ( $sql );
	}
}

/**
 * 保存某商品的扩展分类
 * @param   int     $goods_id   商品编号
 * @param   array   $cat_list   分类编号数组
 * @return  void
 */
function handle_other_cat($goods_id, $cat_list) {
	/* 查询现有的扩展分类 */
	$sql = "SELECT cat_id FROM " . $GLOBALS ['ecs']->table ( 'goods_cat' ) . " WHERE goods_id = '$goods_id'";
	$exist_list = $GLOBALS ['db']->getCol ( $sql );
	
	/* 删除不再有的分类 */
	$delete_list = array_diff ( $exist_list, $cat_list );
	if ($delete_list) {
		$sql = "DELETE FROM " . $GLOBALS ['ecs']->table ( 'goods_cat' ) . " WHERE goods_id = '$goods_id' " . "AND cat_id " . db_create_in ( $delete_list );
		$GLOBALS ['db']->query ( $sql );
	}
	
	/* 添加新加的分类 */
	$add_list = array_diff ( $cat_list, $exist_list, array (0 ) );
	foreach ( $add_list as $cat_id ) {
		// 插入记录
		$sql = "INSERT INTO " . $GLOBALS ['ecs']->table ( 'goods_cat' ) . " (goods_id, cat_id) " . "VALUES ('$goods_id', '$cat_id')";
		$GLOBALS ['db']->query ( $sql );
	}
}

/**
 * 保存某商品的关联商品
 * @param   int     $goods_id
 * @return  void
 */
function handle_link_goods($goods_id) {
	$sql = "UPDATE " . $GLOBALS ['ecs']->table ( 'link_goods' ) . " SET " . " goods_id = '$goods_id' " . " WHERE goods_id = '0'";
	$GLOBALS ['db']->query ( $sql );
	
	$sql = "UPDATE " . $GLOBALS ['ecs']->table ( 'link_goods' ) . " SET " . " link_goods_id = '$goods_id' " . " WHERE link_goods_id = '0'";
	$GLOBALS ['db']->query ( $sql );
}

/**
 * 保存某商品的配件
 * @param   int     $goods_id
 * @return  void
 */
function handle_group_goods($goods_id) {
	$sql = "UPDATE " . $GLOBALS ['ecs']->table ( 'group_goods' ) . " SET " . " parent_id = '$goods_id' " . " WHERE parent_id = '0'";
	$GLOBALS ['db']->query ( $sql );
}

/**
 * 保存某商品的关联文章
 * @param   int     $goods_id
 * @return  void
 */
function handle_goods_article($goods_id) {
	$sql = "UPDATE " . $GLOBALS ['ecs']->table ( 'goods_article' ) . " SET " . " goods_id = '$goods_id' " . " WHERE goods_id = '0'";
	$GLOBALS ['db']->query ( $sql );
}

/**
 * 保存某商品的相册图片
 * @param   int     $goods_id
 * @param   array   $image_files
 * @param   array   $image_descs
 * @return  void
 */
function handle_gallery_image($goods_id, $image_files, $image_descs) {
	foreach ( $image_descs as $key => $img_desc ) {
		/* 是否成功上传 */
		$flag = false;
		if (isset ( $image_files ['error'] )) {
			if ($image_files ['error'] [$key] == 0) {
				$flag = true;
			}
		} else {
			if ($image_files ['tmp_name'] [$key] != 'none') {
				$flag = true;
			}
		}
		
		if ($flag) {
			// 生成缩略图
			$thumb_url = $GLOBALS ['image']->make_thumb ( $image_files ['tmp_name'] [$key], $GLOBALS ['_CFG'] ['thumb_width'], $GLOBALS ['_CFG'] ['thumb_height'] );
			$thumb_url = is_string ( $thumb_url ) ? $thumb_url : '';
			
			$upload = array ('name' => $image_files ['name'] [$key], 'type' => $image_files ['type'] [$key], 'tmp_name' => $image_files ['tmp_name'] [$key], 'size' => $image_files ['size'] [$key] );
			if (isset ( $image_files ['error'] )) {
				$upload ['error'] = $image_files ['error'] [$key];
			}
			$img_original = $GLOBALS ['image']->upload_image ( $upload );
			$img_url = $img_original;
			
			// 如果服务器支持GD 则添加水印
			if (gd_version () > 0) {
				$pos = strpos ( basename ( $img_original ), '.' );
				$newname = dirname ( $img_original ) . '/' . $GLOBALS ['image']->random_filename () . substr ( basename ( $img_original ), $pos );
				copy ( '../' . $img_original, '../' . $newname );
				$img_url = $newname;
				
				$GLOBALS ['image']->add_watermark ( '../' . $img_url, '', $GLOBALS ['_CFG'] ['watermark'], $GLOBALS ['_CFG'] ['watermark_place'], $GLOBALS ['_CFG'] ['watermark_alpha'] );
			}
			
			$sql = "INSERT INTO " . $GLOBALS ['ecs']->table ( 'goods_gallery' ) . " (goods_id, img_url, img_desc, thumb_url, img_original) " . "VALUES ('$goods_id', '$img_url', '$img_desc', '$thumb_url', '$img_original')";
			$GLOBALS ['db']->query ( $sql );
		}
	}
}

/**
 * 修改商品某字段值
 * @param   string  $goods_id   商品编号，可以为多个，用 ',' 隔开
 * @param   string  $field      字段名
 * @param   string  $value      字段值
 * @return  bool
 */
function update_goods($goods_id, $field, $value) {
	if ($goods_id) {
		/* 清除缓存 */
		clear_cache_files ();
		
		$sql = "UPDATE " . $GLOBALS ['ecs']->table ( 'goods' ) . " SET $field = '$value' , last_update = '" . time () . "' " . "WHERE goods_id " . db_create_in ( $goods_id );
		
		return $GLOBALS ['db']->query ( $sql );
	} else {
		return false;
	}
}

/**
 * 从回收站删除多个商品
 * @param   mix     $goods_id   商品id列表：可以逗号格开，也可以是数组
 * @return  void
 */
function delete_goods($goods_id) {
	if (empty ( $goods_id )) {
		return;
	}
	
	/* 取得有效商品id */
	$sql = "SELECT DISTINCT goods_id FROM " . $GLOBALS ['ecs']->table ( 'goods' ) . " WHERE goods_id " . db_create_in ( $goods_id ) . " AND is_delete = 1";
	$goods_id = $GLOBALS ['db']->getCol ( $sql );
	if (empty ( $goods_id )) {
		return;
	}
	
	/* 删除商品图片和轮播图片文件 */
	$sql = "SELECT goods_thumb, goods_img, original_img, cycle_img " . "FROM " . $GLOBALS ['ecs']->table ( 'goods' ) . " WHERE goods_id " . db_create_in ( $goods_id );
	$res = $GLOBALS ['db']->query ( $sql );
	while ( $goods = $GLOBALS ['db']->fetchRow ( $res ) ) {
		if (! empty ( $goods ['goods_thumb'] )) {
			@unlink ( '../' . $goods ['goods_thumb'] );
		}
		if (! empty ( $goods ['goods_img'] )) {
			@unlink ( '../' . $goods ['goods_img'] );
		}
		if (! empty ( $goods ['original_img'] )) {
			@unlink ( '../' . $goods ['original_img'] );
		}
		if (! empty ( $goods ['cycle_img'] )) {
			@unlink ( '../' . $goods ['cycle_img'] );
		}
	}
	
	/* 删除商品 */
	$sql = "DELETE FROM " . $GLOBALS ['ecs']->table ( 'goods' ) . " WHERE goods_id " . db_create_in ( $goods_id );
	$GLOBALS ['db']->query ( $sql );
	
	/* 删除商品相册的图片文件 */
	$sql = "SELECT img_url, thumb_url, img_original " . "FROM " . $GLOBALS ['ecs']->table ( 'goods_gallery' ) . " WHERE goods_id " . db_create_in ( $goods_id );
	$res = $GLOBALS ['db']->query ( $sql );
	while ( $row = $GLOBALS ['db']->fetchRow ( $res ) ) {
		if (! empty ( $row ['img_url'] )) {
			@unlink ( '../' . $row ['img_url'] );
		}
		if (! empty ( $row ['thumb_url'] )) {
			@unlink ( '../' . $row ['thumb_url'] );
		}
		if (! empty ( $row ['img_original'] )) {
			@unlink ( '../' . $row ['img_original'] );
		}
	}
	
	/* 删除商品相册 */
	$sql = "DELETE FROM " . $GLOBALS ['ecs']->table ( 'goods_gallery' ) . " WHERE goods_id " . db_create_in ( $goods_id );
	$GLOBALS ['db']->query ( $sql );
	
	/* 删除相关表记录 */
	$sql = "DELETE FROM " . $GLOBALS ['ecs']->table ( 'collect_goods' ) . " WHERE goods_id " . db_create_in ( $goods_id );
	$GLOBALS ['db']->query ( $sql );
	$sql = "DELETE FROM " . $GLOBALS ['ecs']->table ( 'gift' ) . " WHERE goods_id " . db_create_in ( $goods_id );
	$GLOBALS ['db']->query ( $sql );
	$sql = "DELETE FROM " . $GLOBALS ['ecs']->table ( 'goods_article' ) . " WHERE goods_id " . db_create_in ( $goods_id );
	$GLOBALS ['db']->query ( $sql );
	$sql = "DELETE FROM " . $GLOBALS ['ecs']->table ( 'goods_attr' ) . " WHERE goods_id " . db_create_in ( $goods_id );
	$GLOBALS ['db']->query ( $sql );
	$sql = "DELETE FROM " . $GLOBALS ['ecs']->table ( 'goods_cat' ) . " WHERE goods_id " . db_create_in ( $goods_id );
	$GLOBALS ['db']->query ( $sql );
	$sql = "DELETE FROM " . $GLOBALS ['ecs']->table ( 'member_price' ) . " WHERE goods_id " . db_create_in ( $goods_id );
	$GLOBALS ['db']->query ( $sql );
	$sql = "DELETE FROM " . $GLOBALS ['ecs']->table ( 'group_goods' ) . " WHERE parent_id " . db_create_in ( $goods_id );
	$GLOBALS ['db']->query ( $sql );
	$sql = "DELETE FROM " . $GLOBALS ['ecs']->table ( 'group_goods' ) . " WHERE goods_id " . db_create_in ( $goods_id );
	$GLOBALS ['db']->query ( $sql );
	$sql = "DELETE FROM " . $GLOBALS ['ecs']->table ( 'link_goods' ) . " WHERE goods_id " . db_create_in ( $goods_id );
	$GLOBALS ['db']->query ( $sql );
	$sql = "DELETE FROM " . $GLOBALS ['ecs']->table ( 'link_goods' ) . " WHERE link_goods_id " . db_create_in ( $goods_id );
	$GLOBALS ['db']->query ( $sql );
	$sql = "DELETE FROM " . $GLOBALS ['ecs']->table ( 'tag' ) . " WHERE goods_id " . db_create_in ( $goods_id );
	$GLOBALS ['db']->query ( $sql );
	$sql = "DELETE FROM " . $GLOBALS ['ecs']->table ( 'comment' ) . " WHERE comment_type = 0 AND id_value " . db_create_in ( $goods_id );
	$GLOBALS ['db']->query ( $sql );
	
	/* 删除相应虚拟商品记录 */
	$sql = "DELETE FROM " . $GLOBALS ['ecs']->table ( 'virtual_card' ) . " WHERE goods_id " . db_create_in ( $goods_id );
	if (! $GLOBALS ['db']->query ( $sql, 'SILENT' ) && $GLOBALS ['db']->errno () != 1146) {
		die ( $GLOBALS ['db']->error () );
	}
	
	/* 清除缓存 */
	clear_cache_files ();
}

/**
 * 更新 is_basic
 * @param   int     $goods_id   商品编号
 * @return  bool
 */
function change_is_basic($goods_id) {
	$sql = "SELECT COUNT(*) FROM " . $GLOBALS ['ecs']->table ( 'group_goods' ) . " WHERE parent_id = '$goods_id'";
	$goods ['is_basic'] = $GLOBALS ['db']->getOne ( $sql ) > 0 ? '1' : '0';
	
	return $GLOBALS ['db']->autoExecute ( $GLOBALS ['ecs']->table ( 'goods' ), $goods, 'UPDATE', "goods_id = '$goods_id'" );
}

/**
 * 更新 is_linked 暂时没有用
 * @param   int     $goods_id   商品编号
 * @return  bool
 */
function change_is_linked($goods_id) {
	$sql = "SELECT COUNT(*) FROM " . $GLOBALS ['ecs']->table ( 'link_goods' ) . " WHERE goods_id = '$goods_id'";
	$goods ['is_linked'] = $GLOBALS ['db']->getOne ( $sql ) > 0 ? '1' : '0';
	
	return $GLOBALS ['db']->autoExecute ( $GLOBALS ['ecs']->table ( 'goods' ), $goods, 'UPDATE', "goods_id = '$goods_id'" );
}

/**
 * 为某商品生成唯一的货号
 * @param   int     $goods_id   商品编号
 * @return  string  唯一的货号
 */
function generate_goods_sn($goods_id) {
	$goods_sn = $GLOBALS ['_CFG'] ['sn_prefix'] . str_repeat ( '0', 6 - strlen ( $goods_id ) ) . $goods_id;
	
	$sql = "SELECT goods_sn FROM " . $GLOBALS ['ecs']->table ( 'goods' ) . " WHERE goods_sn LIKE '" . mysql_like_quote ( $goods_sn ) . "%' AND goods_id <> '$goods_id' " . " ORDER BY LENGTH(goods_sn) DESC";
	$sn_list = $GLOBALS ['db']->getCol ( $sql );
	if (in_array ( $goods_sn, $sn_list )) {
		$max = pow ( 10, strlen ( $sn_list [0] ) - strlen ( $goods_sn ) + 1 ) - 1;
		$new_sn = $goods_sn . rand ( 0, $max );
		while ( in_array ( $new_sn, $sn_list ) ) {
			$new_sn = $goods_sn . rand ( 0, $max );
		}
		$goods_sn = $new_sn;
	}
	
	return $goods_sn;
}

/**
 * 取得通用属性和某分类的属性，以及某商品的属性值
 * @param   int     $cat_id     分类编号
 * @param   int     $goods_id   商品编号
 * @return  array   规格与属性列表
 */
function get_attr_list($cat_id, $goods_id = 0) {
	if (empty ( $cat_id )) {
		$sql = "SELECT cat_id FROM " . $GLOBALS ['ecs']->table ( 'goods_type' ) . " LIMIT 1";
		$cat_id = $GLOBALS ['db']->getOne ( $sql );
	}
	
	// 查询属性值及商品的属性值
	$sql = "SELECT a.attr_id, a.attr_name, a.attr_select, a.attr_type, a.attr_values, v.attr_value, v.attr_price " . "FROM " . $GLOBALS ['ecs']->table ( 'attribute' ) . " AS a " . "LEFT JOIN " . $GLOBALS ['ecs']->table ( 'goods_attr' ) . " AS v " . "ON v.attr_id = a.attr_id AND v.goods_id = '$goods_id' " . "WHERE a.cat_id = " . intval ( $cat_id ) . " OR a.cat_id = 0 " . "ORDER BY a.attr_type, a.attr_id, a.sort_order, v.attr_price, v.goods_attr_id";
	
	$row = $GLOBALS ['db']->GetAll ( $sql );
	
	return $row;
}

/**
 * 根据属性数组创建属性的表单
 *
 * @access  public
 * @param   int     $cat_id     分类编号
 * @param   int     $goods_id   商品编号
 * @return  string
 */
function build_attr_html($cat_id, $goods_id = 0) {
	$attr = get_attr_list ( $cat_id, $goods_id );
	$html = '<table width="100%" id="attrTable">';
	$spec = 0;
	
	foreach ( $attr as $key => $val ) {
		$html .= "<tr><td class='label'>";
		if ($val ['attr_type'] == 1) {
			$html .= ($spec != $val ['attr_id']) ? "<a href='javascript:;' onclick='addSpec(this)'>[+]</a>" : "<a href='javascript:;' onclick='removeSpec(this)'>[-]</a>";
			$spec = $val ['attr_id'];
		}
		;
		
		$html .= "$val[attr_name]</td><td><input type='hidden' name='attr_id_list[]' value='$val[attr_id]' />";
		
		if ($val ['attr_select'] == 0) {
			$html .= '<input name="attr_value_list[]" type="text" value="' . htmlspecialchars ( $val ['attr_value'] ) . '" size="40" /> ';
		} elseif ($val ['attr_select'] == 2) {
			$html .= '<textarea name="attr_value_list[]" rows="3" cols="40">' . htmlspecialchars ( $val ['attr_value'] ) . '</textarea>';
		} else {
			$html .= '<select name="attr_value_list[]">';
			$html .= '<option value="">' . $GLOBALS ['_LANG'] ['select_please'] . '</option>';
			
			$attr_values = explode ( "\n", $val ['attr_values'] );
			
			foreach ( $attr_values as $opt ) {
				$opt = trim ( htmlspecialchars ( $opt ) );
				
				$html .= ($val ['attr_value'] != $opt) ? '<option value="' . $opt . '">' . $opt . '</option>' : '<option value="' . $opt . '" selected="selected">' . $opt . '</option>';
			}
			$html .= '</select> ';
		}
		
		$html .= ($val ['attr_type'] == 1) ? $GLOBALS ['_LANG'] ['spec_price'] . ' <input type="text" name="attr_price_list[]" value="' . $val ['attr_price'] . '" size="5" maxlength="10" />' : ' <input type="hidden" name="attr_price_list[]" value="0" />';
		
		$html .= '</td></tr>';
	}
	
	$html .= '</table>';
	
	return $html;
}

/**
 * 获得指定商品相关的商品
 *
 * @access  public
 * @param   integer $goods_id
 * @return  array
 */
function get_linked_goods($goods_id) {
	$sql = "SELECT lg.link_goods_id AS goods_id, g.goods_name, lg.is_double " . "FROM " . $GLOBALS ['ecs']->table ( 'link_goods' ) . " AS lg, " . $GLOBALS ['ecs']->table ( 'goods' ) . " AS g " . "WHERE lg.goods_id = '$goods_id' " . "AND lg.link_goods_id = g.goods_id ";
	$row = $GLOBALS ['db']->getAll ( $sql );
	
	foreach ( $row as $key => $val ) {
		$linked_type = $val ['is_double'] == 0 ? $GLOBALS ['_LANG'] ['single'] : $GLOBALS ['_LANG'] ['double'];
		
		$row [$key] ['goods_name'] = $val ['goods_name'] . " -- [$linked_type]";
		
		unset ( $row [$key] ['is_double'] );
	}
	
	return $row;
}

/**
 * 获得指定商品的配件
 *
 * @access  public
 * @param   integer $goods_id
 * @return  array
 */
function get_group_goods($goods_id) {
	$sql = "SELECT gg.goods_id, CONCAT(g.goods_name, ' -- [', gg.goods_price, ']') AS goods_name " . "FROM " . $GLOBALS ['ecs']->table ( 'group_goods' ) . " AS gg, " . $GLOBALS ['ecs']->table ( 'goods' ) . " AS g " . "WHERE gg.parent_id = '$goods_id' " . "AND gg.goods_id = g.goods_id ";
	$row = $GLOBALS ['db']->getAll ( $sql );
	
	return $row;
}

/**
 * 获得商品的关联文章
 *
 * @access  public
 * @param   integer $goods_id
 * @return  array
 */
function get_goods_articles($goods_id) {
	$sql = "SELECT g.article_id, a.title " . "FROM " . $GLOBALS ['ecs']->table ( 'goods_article' ) . " AS g, " . $GLOBALS ['ecs']->table ( 'article' ) . " AS a " . "WHERE g.goods_id = '$goods_id' " . "AND g.article_id = a.article_id ";
	$row = $GLOBALS ['db']->getAll ( $sql );
	
	return $row;
}
/**获取单个商品信息
 * 
 */
function getOneGoods($goodsId) {
	if($_SESSION['party_id'] == 65625) { 
		$sql = "SELECT g.* ,c.cat_name, IFNULL(bzp.item_number,'') as item_number, bzp.is_fragile, bzp.spec" . " FROM " . $GLOBALS ['ecs']->table ( 'goods' ) . " AS g 
				LEFT JOIN " . $GLOBALS ['ecs']->table ( 'category' ) . " AS c ON g.cat_id = c.cat_id
				LEFT JOIN ecshop.brand_zhongliang_product bzp on bzp.barcode = g.barcode  
				where g.goods_id=" . $goodsId;
	} else {
		$sql = "SELECT g.* ,c.cat_name" . " FROM " . $GLOBALS ['ecs']->table ( 'goods' ) . " AS g 
				LEFT JOIN " . $GLOBALS ['ecs']->table ( 'category' ) . " AS c ON g.cat_id = c.cat_id
				where g.goods_id=" . $goodsId;
	}
	$res = $GLOBALS ['db']->getAll ( $sql );
	return $res;
}
/**
 * 获取某goods_id的耗材信息
 */
function getOneConsumableInfo($goods_id) {
	$sql = "SELECT g.goods_id, g.consumable_id, c.consumable_name, g.consumable_count FROM " . $GLOBALS ['ecs']->table ( 'goods_consumable' ) . " AS g 
			LEFT JOIN  " .$GLOBALS ['ecs']->table ( 'consumable' ) . " AS c ON g.consumable_id = c.consumable_id 
			where g.goods_id = " .$goods_id;
	$res = $GLOBALS ['db']->getAll ( $sql );
	return $res;
}

/**
 * 获取特定商品的样式(颜色/尺寸)
 * */
function getGoodsStyles($goodsId) {
	$sql = "SELECT g.goods_style_id,g.style_price,g.barcode,ifnull(g.goods_code,'') as goods_code, s.color" . 
			" FROM " . $GLOBALS ['ecs']->table ( 'goods_style' ) . " AS g " . 
			" LEFT JOIN " . $GLOBALS ['ecs']->table ( 'style' ) . " AS s ON g.style_id = s.style_id" .
			" where g.goods_id='$goodsId' and g.is_delete = 0 ";
	$res = $GLOBALS ['db']->getAll ( $sql );
	return $res;
}
/**
 * 根据goods_style_id 获取详细信息
 * */
function getGoodsStyle($goods_style_id) {
	$sql = "SELECT g.goods_style_id,g.style_id,g.style_price,g.barcode,ifnull(g.goods_code,'') as goods_code ,s.color,s.value,s.type" . 
		    " FROM " . $GLOBALS ['ecs']->table ( 'goods_style' ) . " AS g " .
		    " LEFT JOIN " . $GLOBALS ['ecs']->table ( 'style' ) . " AS s ON g.style_id = s.style_id" . 
            " where g.goods_style_id='$goods_style_id'";
	$result = mysql_fetch_array ( $GLOBALS ['db']->query ( $sql ) );
	return $result;
}
/**
 * 更新样式的style_id,barcode等信息
 * 
 * */
function editGoodsStyle($info) {
	$style_price = $info ['style_price'];
	$barcode = $info ['barcode'];
	$update_style_id = $info ['update_style_id'];
	$goods_style_id = $info ['goods_style_id'];
	$goods_code = mysql_escape_string($info['goods_code']);
	
	if($barcode!='null')
	{
		$style_barCode_sql = "select count(*) as barcode_num from " . $GLOBALS ['ecs']->table ( 'goods_style' ) . " where  barcode = '$barcode'  and goods_style_id != '$goods_style_id'";
		$goods_barCode_sql = "select count(*) as code_num from " . $GLOBALS ['ecs']->table ( 'goods' ) . " where  barcode  = '$barcode'";
		
		$res_num = mysql_fetch_array ( $GLOBALS ['db']->query ( $style_barCode_sql ) );
		$res_num2 = mysql_fetch_array ( $GLOBALS ['db']->query ( $goods_barCode_sql ) );
		
		if ($res_num ['barcode_num'] > 0 || $res_num2 ['code_num'] > 0) {
			return - 1;
		}
	}
	
	$sql = "update" . $GLOBALS ['ecs']->table ( 'goods_style' ) . "set 
		  	  style_price ='$style_price',
		  	   	  barcode ='$barcode',
		  	     style_id ='$update_style_id',
		  	     goods_code='$goods_code',
		  	      last_update_stamp = now() " . "where goods_style_id ='$goods_style_id'";
	
	$res = $GLOBALS ['db']->query ( $sql );
	
	//SINRI change as well in romeo.inventory_location
	$sql_pid="SELECT
					p.PRODUCT_ID
				FROM
					(
						SELECT
							egs.goods_id,
							egs.style_id
						FROM
							ecshop.ecs_goods_style egs
						WHERE
							egs.goods_style_id = '$goods_style_id' and egs.is_delete=0
					) AS t1
				LEFT JOIN romeo.product_mapping p ON p.ECS_GOODS_ID = t1.goods_id
				AND p.ECS_STYLE_ID = t1.style_id;";
	$product_id=$GLOBALS ['db']->getOne($sql_pid);
	$sql_location_change="UPDATE romeo.inventory_location il
							SET il.goods_barcode = '$barcode'
							WHERE
								il.product_id = '$product_id';";
	$GLOBALS ['db']->query ( $sql_location_change );

	return $res;
}

/**
 * 根据类型(1表示手机，2运动鞋，3配件装备)获取样式列表信息
 * 
 * */
function getStylesList($type) {
	$sql = "SELECT *" . " FROM " . $GLOBALS ['ecs']->table ( 'style' ) . " AS g " . " where g.type=" . $type . " order by style_id desc";
	$res = $GLOBALS ['db']->getAll ( $sql );
	return $res;
}

/**
 * 获取商品列表信息
 * 
 * */
function goods_list($cat_id, $conditions = NULL) {
	
	$checkCate = "SELECT COUNT(*) as num" . " FROM " . $GLOBALS ['ecs']->table ( 'category' ) . " AS c " . " where c.cat_id = $cat_id and " . party_sql ( 'c.PARTY_ID' );
	
	$res = mysql_fetch_array ( $GLOBALS ['db']->query ( $checkCate ) );
	
	if ($res ['num'] == 0) {
		return - 1;
	}
	
	$today = date ( 'Y-m-d' );
	
	/* 记录总数 */
	$sql = "SELECT COUNT(*) FROM " . $GLOBALS ['ecs']->table ( 'goods' ) . " AS g WHERE g.cat_id=$cat_id";
	$filter ['record_count'] = $GLOBALS ['db']->getOne ( $sql, l );
	
	/* 分页大小 */
	$filter = page_and_size ( $filter );
	
	$sql = "SELECT g.goods_id,g.goods_name,es.style_id,g.goods_sn, g.shop_price,s.color,g.is_on_sale, g.goods_number, 
			    if(isnull(es.barcode) or es.barcode ='',g.barcode,es.barcode) as barcode, IFNULL(bzp.item_number,'') as item_number  
		    FROM " . $GLOBALS ['ecs']->table ( 'goods' ) . " AS g 
		    LEFT JOIN " . $GLOBALS ['ecs']->table ( 'goods_style' ) . " AS es ON es.goods_id = g.goods_id 
		    LEFT JOIN " . $GLOBALS ['ecs']->table ( 'style' ) . " AS s ON s.style_id = es.style_id 
		    LEFT JOIN ecshop.brand_zhongliang_product bzp ON g.barcode = bzp.barcode
		    where g.cat_id=$cat_id  and ". party_sql('g.goods_party_id') . $conditions . 
		    " and g.is_delete = 0 and if(es.goods_id is null, es.is_delete is null , es.is_delete = 0) 
		    order by g.goods_id desc"
    ;
	//    " LIMIT " . $filter['start'] . ",$filter[page_size]";
	$row = $GLOBALS ['db']->getAll ( $sql );
	
	$filter ['keyword'] = stripslashes ( $filter ['keyword'] );
	$arr = array ('goods' => $row, 'filter' => $filter, 'page_count' => $filter ['page_count'], 'record_count' => $filter ['record_count'] );
	
	return $row;
}
/**
 * 获取下载资料列表信息
 *
 * */
function data_list($cat_id, $conditions = NULL) {

	$checkCate = "SELECT COUNT(*) as num" . " FROM " . $GLOBALS ['ecs']->table ( 'category' ) . " AS c " . " where c.cat_id = $cat_id and " . party_sql ( 'c.PARTY_ID' );

	$res = mysql_fetch_array ( $GLOBALS ['db']->query ( $checkCate ) );

	if ($res ['num'] == 0) {
		return - 1;
	}

	$sql = "SELECT dd.data_id,dd.data_name,dd.data_size
	FROM " . $GLOBALS ['ecs']->table ( 'download_data' ) . " AS dd
	where dd.cat_id=$cat_id  and ". party_sql('dd.data_party_id') . $conditions .
			" 
			order by dd.data_id desc"
			;
	$row = $GLOBALS ['db']->getAll ( $sql );

	return $row;
}
/**
 *添加商品信息
 * */
function add_goods_info($info) {
	global $db;
	$top_cat_id = $info ['top_cat_id'];
	$party_id = $info ['party_id'];
	$goods_name = $info ['goods_name'];
	$cat_id = $info ['cat_id'];
	$barcode = $info ['barcode'];
	$onSale = $info ['onSale'];
	$warn_number = $info ['warn_number'];
	$maintainWeight = $info ['maintainWeight'];
	$goods_weight = $info ['goods_weight'];
	$maintainWarranty = $info ['maintainWarranty'];
	$maintainBatchSn = $info ['maintainBatchSn'];
	$goods_warranty = $info ['goods_warranty'];
	$contraband = $info ['contraband'];
	$add_time = $info ['add_time'];
	$goods_volume = $info ['goods_volume'];
	$brand_id = $info ['brand_id'];
	$goods_price = $info ['goods_price'];

	$goods_length = $info['goods_length'];
	$goods_width = $info['goods_width'];
	$goods_height = $info['goods_height'];

	$box_length = $info['box_length'];
	$box_width = $info['box_width'];
	$box_height = $info['box_height'];
    $goods_code = $info['goods_code'];
    $goods_code = mysql_escape_string($goods_code); 
    $unit_name = $info['unit_name'];

	$is_bubble_bag = $info['is_bubble_bag'];
	$is_bubble_box = $info['is_bubble_box'];
	$added_fee = $info['added_fee'];
	$item_number = $info['item_number'];
	$is_fragile = $info['is_fragile'];
	$spec = $info['spec'];
	if(!empty($info['currency'])) {
		$currency = $info['currency'];
	} else {
		$currency = 'RMB';
	}
	$product_importance = $info['product_importance'];
	
	$db->start_transaction();
	try{
		if($item_number){
			$sql = "insert into ecshop.brand_zhongliang_product (goods_name, barcode, item_number, is_fragile, spec) VALUES ('$goods_name','$barcode','$item_number',$is_fragile, $spec)";	
			$db->query($sql);
			}
		
		$sql = "insert into " . $GLOBALS ['ecs']->table ( 'goods' ) . " 
		    (goods_party_id , goods_name , cat_id , brand_id, top_cat_id , barcode ,
		    add_time , is_on_sale , warn_number , is_maintain_weight, goods_weight, goods_volume, shop_price, is_maintain_warranty, is_maintain_batch_sn, goods_warranty,goods_height, 
		    goods_length, goods_width, is_bubble_bag, is_bubble_box, last_update_stamp, is_contraband,added_fee,currency,spec, 
		    box_height,box_length,box_width,goods_code,unit_name,product_importance) 
		    values('$party_id' , '$goods_name' , '$cat_id', '$brand_id', '$top_cat_id','$barcode' , 
		    '$add_time' , '$onSale' , '$warn_number' , '$maintainWeight', '$goods_weight', '{$goods_volume}','{$goods_price}','{$maintainWarranty}','{$maintainBatchSn}','{$goods_warranty}',
		    '{$goods_height}', '{$goods_length}', '{$goods_width}', 
		    '{$is_bubble_bag}', '{$is_bubble_box}', now(), '{$contraband}','{$added_fee}','{$currency}','{$spec}', 
		    '{$box_height}','{$box_length}','{$box_width}','{$goods_code}','{$unit_name}', '{$product_importance}')";
		$result = $db->query ( $sql );
		$result = $db->insert_id(); 
		//人头马 微信仓类目
		if($cat_id == '9678') {
			$sql_goods_id = "select goods_id from ecshop.ecs_goods where barcode = '{$barcode}' and cat_id = '{$cat_id}'";
			$goods_id = $db -> getOne($sql_goods_id);
			$sql_weixin_rtm_product = "insert ecshop.weixin_rtm_product (goods_name,barcode,party_id,outer_id,is_sync,created_stamp,last_updated_stamp) 
										values('{$goods_name}','{$barcode}','{$party_id}','{$goods_id}','1',now(),now())";
			$db -> query($sql_weixin_rtm_product);			
		}
		$riskysql = "INSERT INTO risky_actions (admin_user, type, timestamp, php_location, form_name, `sql`, comment) " .
            			"VALUES ('{$_SESSION['admin_name']}', 'add', NOW(), 'lib_goods.php', 'form', '".mysql_real_escape_string($sql)."', '添加商品：{$goods_name}')";
		$db->query($riskysql);
		
		$db->commit();
		
	}catch(Exception $e){
		$db->rollBack();
	}
	
	return $result;
}
/**
 *添加商品信息
 * */
function add_download_info($info) {

	$top_cat_id = $info ['top_cat_id'];
	$party_id = $info ['party_id'];
	$data_name = $info ['data_name'];
	$cat_id = $info ['cat_id'];
	$data_size = $info ['goods_weight'];
	$add_time = $info ['add_time'];

	$sql = "
	insert into " . $GLOBALS ['ecs']->table ( 'download_data' ) . "(data_party_id, cat_id, data_name, data_size, add_time, top_cat_id)
	values('$party_id','$cat_id','$data_name','$data_size','$add_time','$top_cat_id')
	";

	$result = $GLOBALS ['db']->query ( $sql );
	return $result;
}
/**
 * 添加商品样式
 * 
 * */
function add_goods_style($info) {
	
	$goods_id = $info ['goods_id'];
	$style_id = $info ['style_id'];
	$barcode = $info ['barcode'];
	$goods_code = $info['goods_code']; 
	$goods_code = mysql_escape_string($goods_code); 
	$style_price = floatval ( $info ['goods_price'] );
	
	if($barcode!='null')
	{
		$style_check_barCode_sql = "select count(*) as barcode_num from" . $GLOBALS ['ecs']->table ( 'goods_style' ) . " as gs left join " . $GLOBALS ['ecs']->table ( 'goods' ) . 
		                           " as g on gs.goods_id = g.goods_id where  gs.barcode = '$barcode' and g.goods_party_id = {$_SESSION['party_id']}";
		
		$goods_check_barCode_sql = "select count(*) as code_num from" . $GLOBALS ['ecs']->table ( 'goods' ) . "where  barcode  = '$barcode' and ecs_goods.goods_party_id = {$_SESSION['party_id']}";
		
		$res_code_num = mysql_fetch_array ( $GLOBALS ['db']->query ( $style_check_barCode_sql ) );
		$res_code_num2 = mysql_fetch_array ( $GLOBALS ['db']->query ( $goods_check_barCode_sql ) );
		
		if ($res_code_num ['barcode_num'] > 0 || $res_code_num2 ['code_num'] > 0) {
			return - 1;
		}
	}
	
	$check_sql = "select count(*) as style_num from" . $GLOBALS ['ecs']->table ( 'goods_style' ) . "where  goods_id='$goods_id' and style_id='$style_id'";
	$check = mysql_fetch_array ( $GLOBALS ['db']->query ( $check_sql ) );
	if ($check ['style_num'] > 0) {
		return - 2;
	}

	$sql = "insert into" . $GLOBALS ['ecs']->table ( 'goods_style' ) . "(goods_id,style_id,style_price,barcode,goods_code,last_update_stamp)" . " values('$goods_id','$style_id','$style_price','$barcode','$goods_code', now())";
	$result = $GLOBALS ['db']->query ( $sql );
		
	
	if($result>0)
	{
		$riskysql = "INSERT INTO risky_actions (admin_user, type, timestamp, php_location, form_name, `sql`, comment) " .
            			"VALUES ('{$_SESSION['admin_name']}', 'add', NOW(), 'lib_goods.php', 'form', '".mysql_real_escape_string($sql)."', '添加商品样式')";
		
		$db->query($riskysql);
		$goods_style_id = "select goods_style_id  from " . $GLOBALS ['ecs']->table ( 'goods_style' ) . " where goods_id = '$goods_id' and style_id = '$style_id' ";
		$style = mysql_fetch_array ( $GLOBALS ['db']->query ( $goods_style_id ) );
		return $style ['goods_style_id'];
	}else
	{
		return -3;
	}
}
/**
 * 检查barcode的唯一性
 * */
function checkBarCode($barcode, $goods_id = NULL) {

	$goods_check_barCode_sql = "select count(*) as code_num  " .
			"from " . $GLOBALS ['ecs']->table ( 'goods' ) . 
			" where  barcode  = '$barcode' " . " and goods_id != $goods_id " . 
			" and goods_party_id = {$_SESSION['party_id']} and is_delete = 0 ";

	if ($goods_id == '') {
		$goods_check_barCode_sql = "select count(*) as code_num from " .
		 $GLOBALS ['ecs']->table ( 'goods' ) . " where  barcode  = '$barcode' " .
		  " and goods_party_id = {$_SESSION['party_id']} and is_delete = 0 ";
	}
	
	$style_check_barCode_sql = "select count(*) as barcode_num from " . 
			$GLOBALS ['ecs']->table ( 'goods_style' ) .  " as gs  left join " . 
			$GLOBALS ['ecs']->table ( 'goods' ) . " as g on gs.goods_id = g.goods_id " .
			" where gs.barcode = '$barcode' and g.goods_party_id = {$_SESSION['party_id']} " .
			" and g.is_delete = 0 ";
	$res_code_num = mysql_fetch_array ( $GLOBALS ['db']->query ( $style_check_barCode_sql ) );
	$res_code_num2 = mysql_fetch_array ( $GLOBALS ['db']->query ( $goods_check_barCode_sql ) );

	if ($res_code_num ['barcode_num'] > 0 || $res_code_num2 ['code_num'] > 0) {
		return - 1;
	}

	return - 2;
}
/**
 * 更新商品基本信息
 * */
function update_goods_info($info) {
	
	$goods_id = $info ['goods_id'];
	$goods_name = $info ['goods_name'];
	$goods_sku = $info ['goods_sku'];
	$barcode = $info ['barcode'];
	$onSale = $info ['onSale'];
	$warn_number = $info ['warn_number'];
	$last_update = $info ['last_update'];
	$maintainWeight = $info ['maintainWeight'];
	$goods_weight = $info ['goods_weight'];
	$maintainWarranty = $info ['maintainWarranty'];
	$maintainBatchSn = $info ['maintainBatchSn'];
	$goods_warranty = $info ['goods_warranty'];
	$contraband = $info ['contraband'];
	$goods_volume = $info ['goods_volume'];
	$goods_price = $info ['goods_price'];
	$item_number = $info['item_number'];
	$is_fragile = $info['is_fragile'];
	$spec = $info['spec'];
	$product_importance = $info['product_importance'];
	$info['goods_code'] = mysql_escape_string($info['goods_code']);
	if(!empty($info['currency'])) {
		$currency = $info['currency'];
	} else {
		$currency = 'RMB';
	}
	

	if($item_number){
		$sql = "select 1 from ecshop.brand_zhongliang_product where barcode = '$barcode'";
		if($GLOBALS ['db']->getOne($sql)){
			$sql = " update ecshop.brand_zhongliang_product set item_number = '$item_number', is_fragile = $is_fragile, spec = $spec where barcode = '$barcode'";		
		}else{
			$sql = "insert into ecshop.brand_zhongliang_product (goods_name, barcode, item_number, is_fragile, spec) values ('$goods_name', '$barcode', '$item_number', $is_fragile, $spec)";
		}
		$GLOBALS ['db']->query ( $sql );
	}	
	
	$sql = "update ".$GLOBALS ['ecs']->table ( 'goods' ) . " set 
	    goods_name = '$goods_name',
	    sku = '$goods_sku',
	    barcode = '$barcode',
	    last_update = '$last_update',
	    is_on_sale = '$onSale',
	    is_maintain_weight = '$maintainWeight',
	    goods_weight = '$goods_weight',
	    is_maintain_warranty = '$maintainWarranty',
	    is_maintain_batch_sn = '$maintainBatchSn',
	    goods_warranty = '$goods_warranty',
	    warn_number = '$warn_number',
	    goods_volume = '{$goods_volume}',
	    shop_price = '{$goods_price}',
	    goods_length = '{$info['goods_length']}', goods_width = '{$info['goods_width']}', goods_height = '{$info['goods_height']}',
	    box_length = '{$info['box_length']}', box_width = '{$info['box_width']}', box_height = '{$info['box_height']}', 
	    goods_code = '{$info['goods_code']}',unit_name = '{$info['unit_name']}',
	    is_bubble_bag = '{$info['is_bubble_bag']}', is_bubble_box = '{$info['is_bubble_box']}',
	    bubble_bag_number = '{$info['bubble_bag_number']}', bubble_box_number = '{$info['bubble_box_number']}',
	    added_fee = '{$info['added_fee']}',
	    last_update_stamp = now(),
	    is_contraband = '$contraband',
	    currency = '$currency',
	    spec = '{$spec}',
	    product_importance = '{$product_importance}' 
	    where goods_id = '$goods_id'";
	    
	
	$result = $GLOBALS ['db']->query ( $sql );
	if($result){
		$riskysql = "INSERT INTO risky_actions (admin_user, type, timestamp, php_location, form_name, `sql`, comment) " .
            			"VALUES ('{$_SESSION['admin_name']}', 'update', NOW(), 'lib_goods.php', 'form', '".mysql_real_escape_string($sql)."', '修改商品')";
		$GLOBALS ['db']->query($riskysql);
		
		//SINRI update inventory_location
		$styles=getGoodsStyles($goods_id);
		if($styles && is_array($styles) && count($styles)>0){
			//Go thy style!
		}else{
			$sql_pid="SELECT
							p.PRODUCT_ID
						FROM
							romeo.product_mapping p 
						WHERE p.ECS_GOODS_ID = '$goods_id'
						AND p.ECS_STYLE_ID = '0';";
			$product_id=$GLOBALS ['db']->getOne($sql_pid);
			$sql_update_goods_barcode="UPDATE romeo.inventory_location il
										SET il.goods_barcode = '$barcode'
										WHERE
											il.product_id = '$product_id';";
			$GLOBALS['db']->query($sql_update_goods_barcode);
			$sql_product_name = "select PRODUCT_NAME from romeo.product where PRODUCT_ID = '$product_id' ";
			$product_name = $GLOBALS['db']->getOne($sql_product_name);
			if($product_name!= $goods_name){
				$sql_update_name = "update romeo.product set PRODUCT_NAME = '$goods_name',LAST_UPDATED_STAMP=now() where PRODUCT_ID = '$product_id'";
				$GLOBALS['db']->query($sql_update_name);
			}
		}
		//更新套餐中的商品信息
		$sql_group_good_name = "select goods_name from ecshop.distribution_group_goods_item where goods_id = '$goods_id' limit 1";
		$group_goods_name = $GLOBALS['db']->getOne($sql_group_good_name);
		if($group_goods_name != $goods_name) {
			$sql_update_group_goods_item = "update ecshop.distribution_group_goods_item set goods_name = '$goods_name' where goods_id = '$goods_id'";
			$GLOBALS['db']->query($sql_update_group_goods_item);
		}		
	}	

	return $result;
}
/**
 * 更新商品基本信息
 * */
function update_data_info($info) {

	$data_id = $info ['data_id'];
	$data_name = $info ['data_name'];
	$data_size = $info ['data_size'];

	$sql = "
	update ".$GLOBALS ['ecs']->table ( 'download_data' ) . " set
	data_name = '$data_name',
	data_size = '$data_size'
	where data_id = '$data_id'";

	$result = $GLOBALS ['db']->query ( $sql );
	return $result;
}
/**
 * 检查sku的唯一性
 * */
function checkGoodsSku($sku) {
	$sql = "select count(*) AS sku_num from " . $GLOBALS ['ecs']->table ( 'goods' ) . " where sku='$sku'";
	$result = $GLOBALS ['db']->getAll ( $sql );
	return $result;
}
/**
 * 获取商品的顶级分类
 * */
function get_top_cat_id($cat_id) {
	$parent_id = $cat_id;
	while ( TRUE ) {
		$sql = "select cat_id,parent_id,cat_name from " . $GLOBALS ['ecs']->table ( 'category' ) . " where cat_id= '{$parent_id}' ";
		$r_id = mysql_fetch_array ( $GLOBALS ['db']->query ( $sql ) );
		$parent_id = $r_id ['parent_id'];
		if ($parent_id == 0) {
			return $r_id['cat_id'];
		}
	}
}

/**
 * 增加ecs_style值
 */
function add_ecs_style($info) {
	
	$type = $info ['type'];
	$value = $info ['value'];
	$color = $info ['color'];
	
	$checkValue = "select count(*) AS num from " . $GLOBALS ['ecs']->table ( 'style' ) . " where value='$value' and type = '$type'";
	$res = mysql_fetch_array ( $GLOBALS ['db']->query ( $checkValue ) );
	if ($res ['num'] > 0) {
		return - 1;
	}
	
	$sql = "insert into" . $GLOBALS ['ecs']->table ( 'style' ) . "(color,value,type)" . " values('$color','$value','$type')";
	$res_insert = $GLOBALS ['db']->query ( $sql );
	
	if ($res_insert > 0) {
		$style_id = "select style_id  from " . $GLOBALS ['ecs']->table ( 'style' ) . " where value='$value'";
		$style = mysql_fetch_array ( $GLOBALS ['db']->query ( $style_id ) );
		return $style ['style_id'];
	} else {
		return - 2;
	}

}

/**
 * 获得商品style属性
 *
 * @author ncchen 090115
 * @param int $goods_id
 * @param int $style_id
 * @return array
 */
function get_goods_style_info($goods_id, $style_id = 0) {
	global $ecs, $db;
	$sql = "SELECT g.*,gs.*,pm.product_id, IFNULL(gs.goods_number, g.goods_number) AS goods_number, IFNULL(gs.sale_status, g.sale_status) AS sale_status, IFNULL(gs.is_remains, g.is_remains) AS is_remains
					 FROM {$ecs->table('goods')} g
					 LEFT JOIN {$ecs->table('goods_style')} gs ON g.goods_id = gs.goods_id AND gs.style_id = '$style_id' 
					 LEFT JOIN romeo.product_mapping pm on pm.ECS_GOODS_ID = g.goods_id AND pm.ECS_STYLE_ID = '$style_id'
					WHERE g.goods_id = '$goods_id' 
				";
	$goods_info = $db->getRow ( $sql );
	return $goods_info;
}

/**
 * 获得商品的串号类型
 *
 * @param int $goods_id
 * @param int $style_id
 * @return string
 */
function get_goods_item_type($goods_id, $style_id = 0) {
	if (! function_exists ( 'getInventoryItemType' )) {
		require_once (ROOT_PATH . 'RomeoApi/lib_inventory.php');
	}
	if (in_array ( $goods_id, array ('28586', '28587', '31166', '33518' ) )) { // 判断dvd，启用串号
		$goods_item_type = 'SERIALIZED';
	} else {
		$goods_item_type = getInventoryItemType ( $goods_id );
	}
	return $goods_item_type;
}

/**
 * 通过选定项获得分类
 *
 * @param unknown_type $cat_id
 * @return unknown
 */
function get_goods_cate_by_request($cat_id) {
	$cate = array ();
	for($i = 2; $i >= 0; -- $i) {
		if ($cat_id ["cat_id_{$i}"] != 'all') {
			$cate ['class'] = "cat_id_{$i}";
			$cate ['cat_id'] = $cat_id ["cat_id_{$i}"];
			break;
		}
	}
	return $cate;
}

/**
 * 获得父分类
 *
 * @param mix $cat_id
 * @return mix
 */
function get_parent_cate_id($cat_id) {
	global $db, $ecs;
	$in_cat_ids = db_create_in ( $cat_id, 'parent_id' );
	$sql = "SELECT cat_id FROM ecs_category WHERE {$in_cat_ids} ";
	$parent_cat_id = $db->getCol ( $sql );
	$parent_cat_id = db_create_in ( $parent_cat_id, $cat_id, 'cat_id' );
	return $parent_cat_id;
}
/**
 * 通过分类id得到商品查询语句
 *
 * @param string $class 分类级别
 * @param string $cat_id
 * @return string
 */
function get_goods_condition_by_cate($class, $cat_id) {
	$condition = ' AND ';
	
	switch ($class) {
		case 'cat_id_0' :
			switch ($cat_id) {
				case 'mobile' : //手机组
					$condition .= ' (g.top_cat_id = 1 OR g.top_cat_id = 597) ';
					break;
				case 'dvd' : //DVD组
					$condition .= ' g.cat_id = 1157 ';
					break;
				case 'education' : //电教组
					$condition .= ' g.top_cat_id = 1458 ';
					break;
				case 'shoe' : //鞋子组
					$condition .= ' g.cat_id = 0 ';
					break;
				case 'customize_phone' : //定制手机组
					$condition .= ' g.cat_id = 0 ';
					break;
				case 'gift' : // 礼品
					$condition .= ' g.top_cat_id = 1367 ';
					break;
				case 'ec' : // 电子商务
					$condition .= ' g.top_cat_id = 2245';
					break;
				default :
					$condition .= ' g.cat_id = 0 ';
					break;
			}
			break;
		case 'cat_id_1' :
			switch ($cat_id) {
				case 'digit_fitting' : //数码配件
					$in_cat_ids = get_parent_cate_id ( array (1086, 1151 ) );
					$condition .= " {$in_cat_ids} ";
					break;
				case 'earphone' : //耳机/耳麦
					$in_cat_ids = get_parent_cate_id ( array (601 ) );
					$condition .= " {$in_cat_ids} ";
					break;
				case '2246' :
				case '2264' :
				case '2265' :
				case '2276' :
				case '2284' :
				case '2286' :
					$in_cat_ids = get_parent_cate_id ( array ($cat_id ) );
					$condition .= " {$in_cat_ids} ";
					break;
				default :
					$condition .= " g.top_cat_id = '{$cat_id}' ";
					break;
			}
			break;
		case 'cat_id_2' :
			switch ($cat_id) {
				case 'fitting_a' : //保护套
					$in_cat_ids = get_parent_cate_id ( array (1283, 1386, 1387, 1359 ) );
					$condition .= " {$in_cat_ids} ";
					break;
				case 'fitting_other' : //其他
					$in_cat_ids = get_parent_cate_id ( array (608, 1144, 1283, 1386, 1387, 1359, 599, 603, 1122, 609, 1086, 1151, 601 ) );
					$condition .= " g.top_cat_id = 597 AND NOT {$in_cat_ids} ";
					break;
				default :
					$in_cat_ids = get_parent_cate_id ( array ($cat_id ) );
					$condition .= " {$in_cat_ids} ";
					break;
			}
			break;
		default :
			$condition .= ' g.cat_id = 0 ';
			break;
	}
	return $condition;
}

/**
 * 获得商品信息列表
 * @author ncchen
 *
 * @param string $sql_goods
 * @param boolean $is_exist 是否存在库存（null：不判断；true：存在库存；false：不存在库存）
 * @param array $detail_list 附加明细，如订单操作
 * @return array $goods_list
 */
function get_goods_details_list_by_sql($sql_goods, $is_exist = null, $detail_list = array()) {
	global $db, $ecs;
	$refs_value_goods = array ();
	$refs_goods = array ();
	//    $goods_list = $db->getAllRefby($sql_goods, array('gs_id'), $refs_value_goods, $refs_goods, false);
	$goods_list = $db->getAll ( $sql_goods );
	if (empty ( $goods_list ))
		return $goods_list;
	$goods_ids = array ();
	foreach ( $goods_list as $goods ) {
		$goods_ids [] = $goods ['goods_id'];
	}
	
	$new_storage_list = getStorage ();
	$old_storage_list = getStorage ( 'INV_STTS_USED' );
	//    $none_storage_list = getStorage('INV_STTS_DEFECTIVE');
	if (key_exists ( 'sale_list', $detail_list )) {
		if ($detail_list ['sale_list'] ['date'] ['start'] != '' && $detail_list ['sale_list'] ['date'] ['end'] != '') {
			//获得按天计算数据
			$goods_sale_by_date_span = get_goods_sale_by_date_span ( $detail_list ['sale_list'] ['date'] ['start'], $detail_list ['sale_list'] ['date'] ['end'], 'DATE', $goods_ids );
			if ($goods_sale_by_date_span ['gs_id']) {
				foreach ( $goods_sale_by_date_span ['gs_id'] as $key => $sale_list ) {
					foreach ( $sale_list as $sale ) {
						$goods_daily_sale_list [$key] [$sale ['sale_span']] = $sale ['diff'];
					}
					$goods_daily_sale_list [$key] ['today'] = $goods_daily_sale_list [$key] [$dates [0]];
				}
			}
		}
		
		if ($detail_list ['sale_list'] ['week'] ['start'] != '' && $detail_list ['sale_list'] ['week'] ['end'] != '') {
			//获得按周计算数据
			$goods_sale_by_date_span = get_goods_sale_by_date_span ( $detail_list ['sale_list'] ['week'] ['start'], $detail_list ['sale_list'] ['week'] ['end'], 'WEEK', $goods_ids );
			if ($goods_sale_by_date_span ['gs_id']) {
				foreach ( $goods_sale_by_date_span ['gs_id'] as $key => $sale_list ) {
					foreach ( $sale_list as $sale ) {
						$goods_daily_sale_list [$key] [$sale ['sale_span']] = $sale ['diff'];
					}
				}
			}
		}
		
		if ($detail_list ['sale_list'] ['month'] ['start'] != '' && $detail_list ['sale_list'] ['month'] ['end'] != '') {
			//获得按月计算数据
			$goods_sale_by_date_span = get_goods_sale_by_date_span ( $detail_list ['sale_list'] ['month'] ['start'], $detail_list ['sale_list'] ['month'] ['end'], 'MONTH', $goods_ids );
			if ($goods_sale_by_date_span ['gs_id']) {
				foreach ( $goods_sale_by_date_span ['gs_id'] as $key => $sale_list ) {
					foreach ( $sale_list as $sale ) {
						$goods_daily_sale_list [$key] [$sale ['sale_span']] = $sale ['diff'];
					}
				}
			}
		}
	}
	
	//detail_list 附加明细
	foreach ( $goods_list as $key => $goods ) {
		if ($goods ['goods_storage_status'] == 'NEW') {
			$goods_list [$key] ['storage_real'] = isset ( $new_storage_list ["{$goods['goods_id']}_{$goods['style_id']}"] ['qohTotal'] ) ? $new_storage_list ["{$goods['goods_id']}_{$goods['style_id']}"] ['qohTotal'] : 0;
		} else if ($goods ['goods_storage_status'] == 'SECOND_HAND') {
			$goods_list [$key] ['storage_real'] = isset ( $old_storage_list ["{$goods['goods_id']}_{$goods['style_id']}"] ['qohTotal'] ) ? $old_storage_list ["{$goods['goods_id']}_{$goods['style_id']}"] ['qohTotal'] : 0;
		} else if ($goods ['goods_storage_status'] == 'NONE') {
			$goods_list [$key] ['storage_real'] = isset ( $none_storage_list ["{$goods['goods_id']}_{$goods['style_id']}"] ['qohTotal'] ) ? $none_storage_list ["{$goods['goods_id']}_{$goods['style_id']}"] ['qohTotal'] : 0;
		}
		$goods_list [$key] ['sale_status'] = get_goods_sale_status ( $goods ['sale_status'] );
		$goods_list [$key] ['goods_storage_status'] = get_goods_storage_status ( $goods ['goods_storage_status'] );
		$goods_list [$key] ['cost'] = sprintf ( "%.2f", $goods ['cost'] );
		if ($goods ['price']) {
			$goods_list [$key] ['gross_profit_ratio'] = sprintf ( "%.2f%%", ($goods ['price'] - $goods ['cost']) / $goods ['price'] * 100 );
		} else {
			$goods_list [$key] ['gross_profit_ratio'] = 'N/A';
		}
		$goods_list [$key] ['sales'] = $goods_daily_sale_list ["{$goods['goods_id']}_{$goods['style_id']}"];
		if ($is_exist !== null) {
			if (($is_exist == true && $goods_list [$key] ['storage_real'] == 0) || ($is_exist == false && $goods_list [$key] ['storage_real'] > 0)) {
				unset ( $goods_list [$key] );
			}
		}
	}
	
	return $goods_list;
}

/**
 * 获得历史销量
 *
 * @param string $start
 * @param string $end
 * @param string $span
 * @param array $goods_ids
 * @return array
 */
function get_goods_sale_by_date_span($start, $end, $span = 'DATE', $goods_ids = null) {
	/* 初始化数据库类 */
	require_once (ROOT_PATH . 'includes/cls_mysql.php');
	global $ecs, $db;
	if ($span == 'MONTH') {
		$sql_date = "CONCAT('Y', YEAR(detail.created_stamp), DATE_FORMAT(detail.created_stamp, '%m')) ";
	} else if ($span == 'WEEK') {
		$sql_date = "CONCAT('W', YEARWEEK(detail.created_stamp, 1)) ";
	} else if ($span == 'DATE') {
		$sql_date = "DATE(detail.created_stamp) ";
	} else {
		return null;
	}
	$sql_goods_id = "";
	if (! is_null ( $goods_ids )) {
		$sql_goods_id = " AND " . db_create_in ( $goods_ids, 'pm.ecs_goods_id' );
	}
	//获得历史销量
	$sql = "SELECT ii.product_id, pm.ecs_goods_id, pm.ecs_style_id, CONCAT_WS('_', pm.ecs_goods_id, pm.ecs_style_id) as gs_id, 
                {$sql_date} AS sale_span,
                case
                    when detail.order_id is null then 'variance' -- 如果没有对应订单号，就是库存调整单
                    when order_type_id = 'PURCHASE' then 'purchase' 
                    when (order_type_id IN ('SUPPLIER_EXCHANGE', 'BORROW')) and (detail.quantity_on_hand_diff > 0) then 'ghin'
                    when (order_type_id IN ('SUPPLIER_EXCHANGE', 'BORROW')) and (detail.quantity_on_hand_diff < 0) then 'ghout'
                    when (order_type_id = 'RMA_RETURN') then 'tin'
                    when (order_type_id = 'RMA_EXCHANGE') then 'hout'
                    when (order_type_id IN ('SUPPLIER_RETURN', 'SUPPLIER_SALE')) then 'gtout'
                    else 'sale'
                end as action_type,
                SUM(- detail.quantity_on_hand_diff) as diff
            FROM romeo.inventory_item_detail detail
                LEFT JOIN ecshop.ecs_order_info o ON detail.order_id = o.order_id
                LEFT JOIN ecshop.ecs_order_goods og ON detail.order_goods_id = og.rec_id,
                romeo.inventory_item ii
                LEFT JOIN romeo.product_mapping pm on ii.product_id = pm.product_id
            WHERE
                (detail.inventory_item_id = ii.inventory_item_id) AND
                        -- 需要统计正式库、二手库、次品库内的商品
                (ii.status_id in ('INV_STTS_USED', 'INV_STTS_AVAILABLE', 'INV_STTS_DEFECTIVE')) AND
                (detail.created_stamp >= '{$start}') AND 
                (detail.created_stamp < '{$end} 24:00:00') AND
                (detail.cancellation_flag <> 'Y') AND
                ((order_sn like '%-c') OR (detail.order_id is null) OR (og.rec_id is not null) AND (og.rec_id <> ''))
                {$sql_goods_id}
            GROUP BY gs_id, sale_span, action_type
            HAVING action_type = 'sale'
        ";
	$refs_value = array ();
	static $goods_daily_sale;
	if ($goods_daily_sale [$span] == null) {
		$goods_daily_sale [$span] = array ();
		$db->getAllRefBy ( $sql, array ('gs_id' ), $refs_value, $goods_daily_sale [$span], false );
	}
	return $goods_daily_sale [$span];
}
/**
 * 获得商品信息列表合计
 *
 * @param array $goods_list
 */
function get_goods_details_in_total(&$goods_list) {
	$goods_total = array ('gs_id' => '合计' );
	foreach ( $goods_list as $goods ) {
		foreach ( $goods as $key => $item ) {
			if (is_numeric ( $item )) {
				$goods_total [$key] += $item;
			}
			if (is_array ( $item )) {
				foreach ( $item as $key1 => $value ) {
					if (is_numeric ( $value )) {
						$goods_total [$key] [$key1] += $value;
					}
				}
			
			}
		}
	}
	$goods_list ['total'] = $goods_total;
}
?>