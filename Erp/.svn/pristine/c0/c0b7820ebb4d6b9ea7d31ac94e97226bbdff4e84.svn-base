<?php
/**
 * 获得指定分类下的子分类的数组
 *
 * @access  public
 * @param   int     $cat_id     分类的ID
 * @param   int     $selected   当前选中分类的ID
 * @param   boolean $re_type    返回的类型: 值为真时返回下拉列表,否则返回数组
 * @param   int     $level      限定返回的级数。为0时返回所有级数
 * @param   int     $is_show_all 如果为true显示所有分类，如果为false隐藏不可见分类。
 * @return  mix
 */

function get_category_list_by_party_id($party, $parent_id) {
	/*$p_id = "SELECT DISTINCT (ec.cat_id) ".
 	' FROM ' . $GLOBALS ['ecs']->table ( 'category' ) . " AS ec " . 
	" JOIN " . $GLOBALS ['ecs']->table ( 'category' ) . " AS es ON es.parent_id = ec.cat_id " . 
	"where ec.party_id = " . "'" . $party . "'";
	
	$cat_id = "SELECT tg.cat_id ".
	' FROM ' . $GLOBALS ['ecs']->table ( 'category' ) . " AS tg " . 
	" JOIN " . $GLOBALS ['ecs']->table ( 'category' ) . " AS ty ON ty.parent_id = tg.cat_id " . 
	"where tg.party_id = " . "'" . $party . "' and tg.parent_id not in(" .$p_id. ")" ;
	
	$sql = "SELECT c.cat_id, c.cat_name, c.measure_unit, c.parent_id, c.show_in_nav,  c.sort_order,  c.cat_id AS has_children " . 
	'FROM ' . $GLOBALS ['ecs']->table ( 'category' ) . " AS c " . 
	"where c.party_id=" . "'" . $party . "' and c.cat_id in (" .$cat_id.")".
	"GROUP BY c.cat_id " . 
	'ORDER BY c.parent_id, c.sort_order ASC';*/
	$sql = "";
	if($parent_id == - 1){
	$exclude = array(1 ,119 ,166 ,179 ,260, 336, 341, 613, 616, 615, 414, 597, 1071, 825, 837, 979, 1073, 1158, 1159, 1498, 1515, 1516, 2329);
	$sql = "SELECT c.cat_id, c.cat_name, c.measure_unit, c.parent_id, c.show_in_nav,  c.sort_order, COUNT(s.cat_id) AS has_children " . 
	'FROM ' . $GLOBALS ['ecs']->table ( 'category' ) . " AS c " . 
	"LEFT JOIN " . $GLOBALS ['ecs']->table ( 'category' ) . " AS s ON s.parent_id=c.cat_id " . 
	//"where c.party_id=" . "'" . $party . "' and c.parent_id = 0 ".
	"where  c.parent_id = 0 AND c.cat_id not in (".join($exclude,",").")".
	" GROUP BY c.cat_id " . 
	'ORDER BY c.parent_id, c.sort_order ASC';
	}else {
		 $sql = "SELECT c.cat_id, c.cat_name, c.measure_unit, c.parent_id, c.show_in_nav,  c.sort_order, COUNT(s.cat_id) AS has_children " .
		 'FROM ' . $GLOBALS ['ecs']->table ( 'category' ) . " AS c " . 
		 "LEFT JOIN " . $GLOBALS ['ecs']->table ( 'category' ) . " AS s ON s.parent_id=c.cat_id " . 
		// "where c.party_id=" . "'" . $party . "'" . "and c.parent_id=" . "'" . $parent_id . "'" . 
		"where c.parent_id=". "'" . $parent_id . "' and c.is_delete = 0 " . 
		" GROUP BY c.cat_id " . 'ORDER BY c.parent_id, c.sort_order ASC';
	}
	$res = $GLOBALS ['db']->getAll($sql);
	return $res;
}

/**
 * 根据参数获取不同的树形
 * 
 * */
function get_category_my_tree($cat_list, $html){
	$content='';
	foreach($cat_list as $cate){
		if($cate['has_children'] > 0){
			$content .= vsprintf($html['root'],array($cate['cat_id'], $cate['cat_name']));
			$content .= get_category_my_tree($cate['children'], $html);
			$content .= '</ul></li>';
		}
		else{
			$content .= vsprintf($html['leaf'],array($cate['cat_id'],$cate['cat_name']));
		}
	}
	return $content;
}

/**
 * 在获取树形时做点处理
 * 
 * @param $party 组织id
 * @param $parent_id 获取哪个组织下的商品，默认-1为全部，
 * @param $html 最后组织的标签，拥有
 * $html['root'],必须以&lt;li&gt;开头，&lt;ul&gt;结尾，且前面必须有%s填充cat_id和%s填充cat_name
 * $html['leaf']，必须以&lt;li&gt;开头,&lt;li&gt;结尾，且前面必须有%s填充cat_id和%s填充cat_name
 * 
 * @code
 * $html['root'] = "&lt;li>&lt;span class='folder'id='%s'>%s&lt;/span>&lt;ul>";
 * $html['leaf'] = "&lt;li>&lt;a href='goods_index.php?act=list&cat_id=%s' target='content'>&lt;span class='file'>%s&lt;/span>&lt;/a>&lt;/li>";
 * 
 * @author fklin
 * */
function category_proxy($party, $parent_id, $html){
	/*
	*modified by wliu 2016.1.14 去除商品添加界面不属于业务组下的商品目录
	*/
	global $db;
	$exclude = array(1 ,119 ,166 ,179 ,260, 336, 341, 613, 616, 615, 414, 597, 1071, 825, 837, 979, 1073, 1158, 1159, 1498, 1515, 1516, 2329);
	// $sql_cat_ids = "select cat_id from ecshop.ecs_category  where parent_id = '0' and is_delete = '0'";
	// $cat_ids = $db->getCol($sql_cat_ids);//作用取出父类，对应sql-->or c.cat_id ".db_create_in($cat_ids)
	require_once ('../RomeoApi/lib_party.php');
	$party_tree = party_children_list_new($party);
	$party_ids = array_keys($party_tree);
	$sql = "SELECT c.cat_id, c.cat_name, c.parent_id,  COUNT(s.cat_id) AS has_children " .
		 'FROM ' . $GLOBALS ['ecs']->table ( 'category' ) . " AS c " . 
		 "LEFT JOIN " . $GLOBALS ['ecs']->table ( 'category' ) . " AS s ON s.parent_id=c.cat_id " . 
		 "LEFT JOIN romeo.party p on p.party_id = c.party_id " .
		"where c.is_delete = 0 " . 
		"and (c.party_id".db_create_in($party_ids ).")" . 
		" GROUP BY c.cat_id " . 'ORDER BY c.parent_id,c.sort_order ASC ';	
	$cat_list = $db->getAll($sql);
	require_once("../includes/helper/array.php");
	$ref = array();
	$tree = Helper_Array::toTree($cat_list,'cat_id','parent_id','children',$ref);
	 foreach($tree as $key=>$item){
		// if(in_array($item['cat_id'],$exclude) || $item['parent_id'] != 0) {
		// 	unset($tree[$key]);			
		// }
		if(in_array($item['cat_id'],$exclude)){
			// echo "KILLED as ".$item['cat_id']."in ex...";
			// var_export($tree[$key]);
			// echo PHP_EOL;
			unset($tree[$key]);	
		}elseif($item['parent_id'] != 0){
			// echo "killed as ".$item['parent_id']."<> 0 ...";
			// var_export($tree[$key]);
			// echo PHP_EOL;
			//unset($tree[$key]);	
		}
	}
	$tree = $parent_id == -1 ? $tree : $ref[$parent_id]['children'];
	$content = get_category_my_tree($tree, $html);
	return $content;
}
/**
 * 获取查看商品时的树状
 * */
function category_tree_list($party_id, $parent_id) {
	$html = array();
	$html['root'] = "<li><span class='folder'id='%s'>%s</span><ul>";
	$html['leaf'] = "<li><a href='goods_index.php?act=list&cat_id=%s' target='content'><span class='file'>%s</span></a></li>";
	return category_proxy($party_id, $parent_id, $html);
}
/**
 *获取编辑类别时的树状
 * */
function category_tree_edit($party_id, $parent_id) {
		$html = array();
		$html['root'] = "<li><span class='folder'><a href='category_index.php?act=edit&cat_id=%s' target='edit_content'>%s</a></span><ul>";
		$html['leaf'] = "<li><a href='category_index.php?act=edit&cat_id=%s' target='edit_content'><span class='file'>%s</span></a></li>";
	return category_proxy($party_id, $parent_id, $html);
}


/**
 * 添加类别
 * @cat_type  为0表示添加顶级类,为1表示添加子类
 * */
function add_cat($cat_id,$category_name,$cat_type,$party_id){
	if($cat_type==0)
	{
		$sql = "insert into". $GLOBALS['ecs']->table('category').
		  "(cat_name,parent_id,party_id)".
		  " values('$category_name','0','$party_id')";
	}
	else if($cat_type==1)
	{
		$sql_goods_num = "SELECT count(*) as num ".
					' FROM ' . $GLOBALS ['ecs']->table ( 'goods' ). " AS c " . 
					"where c.cat_id=" . "'" . $cat_id."'";
	 	$check  = mysql_fetch_array($GLOBALS['db']->query($sql_goods_num));
    	if($check['num']>0)
    	{
    		return -100;
    	}
    	$sql_datas_num = "SELECT count(*) as num ".
    			' FROM ' . $GLOBALS ['ecs']->table ( 'download_data' ). " AS c " .
    			"where c.cat_id=" . "'" . $cat_id."'";
    	$check_data  = mysql_fetch_array($GLOBALS['db']->query($sql_datas_num));
    	if($check_data['num']>0)
    	{
    		return -100;
    	}
		$sql = "insert into". $GLOBALS['ecs']->table('category').
		  	   "(cat_name,parent_id,party_id)".
		  	   " values('$category_name','$cat_id','$party_id')";
	}
	$result = $GLOBALS['db']->query($sql);
	return $result;
}

function delete_cat($cat_id) {
	if ($cat_id) {
		$sql = " update " . $GLOBALS['ecs']->table('category') . " set is_delete = 1, last_update_stamp = now() where cat_id = {$cat_id} limit 1 ";
		$result = $GLOBALS['db']->query($sql);
		return $result;
	}
	return false;
}
/**
 * 检查当时选中类是否所选组织下的类别
 * */
function checkCatParty($cat_id)
{
    global $db;
    $sql = "select party_id from ecshop.ecs_category where cat_id = '{$cat_id}'";
    $party_id = $db->getOne($sql);
    if ($party_id == $_SESSION['party_id']) {
        return 0;
    } else {
        $parent_party_list = array('32640', '65542', '120'); //电商服务  海外业务 乐其
        if (in_array($party_id, $parent_party_list)) {
            $sql = "select parent_party_id from romeo.party where party_id = '{$_SESSION['party_id']}'";
            $parent_party_id = $db->getOne($sql);
            if ($party_id == $parent_party_id) {
                return 0;
            } else {
                return -1;
            }
        } else {
            return -1;
        }
        
    } 
}

/**
 * 获取类别名称
 * */
function getOneCatName($cat_id) {
	$sql = "SELECT c.cat_name ".
	' FROM ' . $GLOBALS ['ecs']->table ( 'category' ). " AS c " . 
	"where c.cat_id=" . "'" . $cat_id."'";
	$res = $GLOBALS ['db']->query($sql);
	return $res;
}
/**
 * 获取耗材信息
 */
function getConsumableInfo() {
	 $sql = "SELECT cm.consumable_id, cm.consumable_name ".
	 	' FROM ' .$GLOBALS ['ecs'] ->table( 'consumable' ) . " AS  cm " ;
 	return $GLOBALS['db']->getAll($sql);    
}

/**
 *根据商品ID获取它所在的组织名称
 * 
 * */
function getPartyNameCat($cat_id){
	$party_id = "SELECT c.party_id ".
	' FROM ' . $GLOBALS ['ecs']->table ( 'category' ). " AS c " . 
	"where c.cat_id=" . "'" . $cat_id."'";
	$sql = "SELECT p.NAME ".
	' FROM ' ."`romeo`.`party` ". " AS p " . 
	"where p.PARTY_ID=" . "(" . $party_id.")";
	$res = mysql_fetch_array($GLOBALS ['db']->query($sql));
	return $res['NAME'];
}


/**
 * 获取组织信息
 * */
function getOnePartyName($party_id) {
	$sql = "SELECT p.NAME ".
	' FROM ' ."`romeo`.`party` ". " AS p " . 
	"where p.PARTY_ID=" . "'" . $party_id."'";
	$res = mysql_fetch_array($GLOBALS ['db']->query($sql));
	return $res['NAME'];
}


function getParentCatName($cat_id) {
	$p_id = "select parent_id,cat_name from" . $GLOBALS ['ecs']->table ( 'category' ) . "where cat_id='$cat_id'";
	$r_id = mysql_fetch_array ( $GLOBALS ['db']->query ( $p_id ) );
	$num=0;
	if($r_id['parent_id']==0)
	{
		$res[0]['parent_name']=$r_id['cat_name'];
		return $res;
	}else 
	{
		while ( TRUE ) {
		$num++;
		$sql = "select cat_id,parent_id,cat_name from " . $GLOBALS ['ecs']->table ( 'category' ) . " where cat_id= " . $r_id ['parent_id'];
		$r_id = mysql_fetch_array ( $GLOBALS ['db']->query ( $sql ) );
		$arr[$num]['parent_name']=$r_id['cat_name'];
		if ($r_id ['parent_id'] == 0) {
			return $arr;
		}
	 }
	}
	
}

function isCanDelete($cat_id) {
	if ($cat_id) {
		$sql = "select 1 
				FROM " . $GLOBALS ['ecs']->table ( 'goods' ) . " AS g 
				LEFT JOIN " . $GLOBALS ['ecs']->table ( 'goods_style' ) . " AS es ON es.goods_id = g.goods_id 
				where g.cat_id=$cat_id
				and g.is_delete = 0 and if(es.goods_id is null, es.is_delete is null , es.is_delete = 0) 
				limit 1";
		if (mysql_fetch_array( $GLOBALS ['db']->query ( $sql ))) {
			return false;
		}
		
		$sql = "select 1 from ecshop.ecs_category where parent_id = {$cat_id} and is_delete = 0 limit  1";
		if (mysql_fetch_array( $GLOBALS ['db']->query ( $sql ))) {
			return false;
		}
		return true;
	}
	return false;
} 


function cat_lists($cat_id = 0, $selected = 0, $party = 0, $re_type = true, $level = 0, $is_show_all = true) {
	static $res = NULL;
	
	if ($res === NULL) {
		$data = FALSE;
		if ($data === false) {
			$sql = "SELECT c.cat_id, c.cat_name, c.measure_unit, c.parent_id, c.show_in_nav,  c.sort_order, COUNT(s.cat_id) AS has_children " . 'FROM ' . $GLOBALS ['ecs']->table ( 'category' ) . " AS c " . "LEFT JOIN " . $GLOBALS ['ecs']->table ( 'category' ) . " AS s ON s.parent_id=c.cat_id " . //"where c.party_id="."'".$party."'".
			"GROUP BY c.cat_id " . 'ORDER BY c.parent_id, c.sort_order ASC';
			$res = $GLOBALS ['db']->getAll ( $sql );
			
			$sql = "SELECT cat_id, COUNT(*) AS goods_num " . " FROM " . $GLOBALS ['ecs']->table ( 'goods' ) . " WHERE is_delete = 0 AND is_on_sale = 1 " . " GROUP BY cat_id";
			$res2 = $GLOBALS ['db']->getAll ( $sql );
			
			$sql = "SELECT gc.cat_id, COUNT(*) AS goods_num " . " FROM " . $GLOBALS ['ecs']->table ( 'goods_cat' ) . " AS gc , " . $GLOBALS ['ecs']->table ( 'goods' ) . " AS g " . " WHERE g.goods_id = gc.goods_id AND g.is_delete = 0 AND g.is_on_sale = 1 " . " GROUP BY gc.cat_id";
			$res3 = $GLOBALS ['db']->getAll ( $sql );
			
			$newres = array ();
			foreach ( $res2 as $k => $v ) {
				$newres [$v ['cat_id']] = $v ['goods_num'];
				foreach ( $res3 as $ks => $vs ) {
					if ($v ['cat_id'] == $vs ['cat_id']) {
						$newres [$v ['cat_id']] = $v ['goods_num'] + $vs ['goods_num'];
					}
				}
			}
			
			foreach ( $res as $k => $v ) {
				$res [$k] ['goods_num'] = ! empty ( $newres [$v ['cat_id']] ) ? $newres [$v ['cat_id']] : 0;
			}
			//如果数组过大，不采用静态缓存方式
			if (count ( $res ) <= 1000) {
				// write_static_cache('cat_pid_releate', $res);
			}
		} else {
			$res = $data;
		}
	}
	
	if (empty ( $res ) == true) {
		return $re_type ? '' : array ();
	}
	
	$options = cat_options ( $cat_id, $res ); // 获得指定分类下的子分类的数组
	

	$children_level = 99999; //大于这个分类的将被删除
	if ($is_show_all == false) {
		foreach ( $options as $key => $val ) {
			if ($val ['level'] > $children_level) {
				unset ( $options [$key] );
			} else {
				if ($val ['is_show'] == 0) {
					unset ( $options [$key] );
					if ($children_level > $val ['level']) {
						$children_level = $val ['level']; //标记一下，这样子分类也能删除
					}
				} else {
					$children_level = 99999; //恢复初始值
				}
			}
		}
	}
	
	/* 截取到指定的缩减级别 */
	if ($level > 0) {
		if ($cat_id == 0) {
			$end_level = $level;
		} else {
			$first_item = reset ( $options ); // 获取第一个元素
			$end_level = $first_item ['level'] + $level;
		}
		
		/* 保留level小于end_level的部分 */
		foreach ( $options as $key => $val ) {
			if ($val ['level'] >= $end_level) {
				unset ( $options [$key] );
			}
		}
	}
	
	if ($re_type == true) {
		$select = '';
		foreach ( $options as $var ) {
			$select .= '<option value="' . $var ['cat_id'] . '" ';
			$select .= ($selected == $var ['cat_id']) ? "selected='ture'" : '';
			$select .= '>';
			if ($var ['level'] > 0) {
				$select .= str_repeat ( '&nbsp;', $var ['level'] * 4 );
			}
			$select .= htmlspecialchars ( addslashes ( $var ['cat_name'] ), ENT_QUOTES ) . '</option>';
		}
		
		return $select;
	} else {
		foreach ( $options as $key => $value ) {
			$options [$key] ['url'] = build_uri ( 'category', array ('cid' => $value ['cat_id'] ), $value ['cat_name'] );
		}
		
		return $options;
	}
}
?>