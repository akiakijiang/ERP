<?php
define ( 'IN_ECS', true );
require_once ('includes/init.php');
require_once ('function.php');

admin_priv('brand_goods_maintain_65619');
$act = $_REQUEST ['act'];
if($act == 'save') {
	$codes = $_REQUEST['code'];
	$material_numbers = $_REQUEST['material_number'];
	$goods_types = $_REQUEST['goods_type'];
	if(empty($codes) || empty($material_numbers) || empty($goods_types)) {
		$message_error = '数据为空！';
	}
	if(count($codes) != count($material_numbers) || count($codes) != count($goods_types)) {
		$message_error = '数据长度出错！';
	}
	
	$result = batch_save_OR_products($codes,$material_numbers,$goods_types);
	if(!$result['success']) {
		$message_error= $result['error'];
	} else {
		$message_success = '更新成功!';
	}
}

$cond = getCondition();
$goods_list = search_goods('65619',$cond);


//var_dump($goods_list);
$smarty->assign('goods_list',$goods_list);
$smarty->assign('message_error',$message_error);
$smarty->assign('message_success',$message_success);

if ($_REQUEST['type'] == '商品导出CSV') {
	header ( "Content-type:application/vnd.ms-excel" );
	header ( "Content-Disposition:filename=" . iconv ( "UTF-8", "GB18030", "商品导出CSV" ) . ".csv" );
	$out = $smarty->fetch ( 'oukooext/brand_goods_65619_csv.htm' );
	echo iconv ( "UTF-8", "GB18030", $out );
	exit ();
}

$smarty->display ( 'brand_goods_maintain_65619.html' );


function getCondition() {
	global $ecs;
	$result = array();
	$condition =$tc_goods_name= $simple_goods_name=$tc_code=$simple_code="";
	$barcode = trim ( $_REQUEST ['barcode'] );
	$goods_name = trim ( $_REQUEST ['goods_name'] );
	$material_number = $_REQUEST ['material_number'];
	$goods_type = $_REQUEST ['goods_type'];

	if ($barcode != '') {
		$tc_code .= " AND gg.code LIKE '%{$barcode}%' ";
		$simple_code .= " AND g.goods_id LIKE '%{$barcode}%' ";
	}
	if ($goods_name != '') {
		$tc_goods_name = " AND gg.name LIKE '%{$goods_name}%' ";
		$simple_goods_name = " AND g.goods_name LIKE '%{$goods_name}%' ";
	}
	if ($material_number !='') {
		$condition .= " AND p.material_number LIKE '%{$material_number}%' ";
	}

	
	if ($goods_type != - 1 and $goods_type != '') {
		$condition .= " AND p.goods_type = '{$goods_type}' ";
	}
	$result['tc_cond'] = $condition.$tc_goods_name.$tc_code;
	$result['simple_cond'] = $condition.$simple_goods_name.$simple_code;
	
	return $result;
}


/**
 * 批量更新
 */
function batch_save_OR_products($codes,$material_numbers,$goods_types) {
	$result = array();
	$len = count($codes);
	// 判断哪些已经存在了
	$exist_codes = get_exist_goods($codes);
	for($i=0;$i<$len;$i++) {
		$code = $codes[$i];
		$material_number = $material_numbers[$i];
		$goods_type = $goods_types[$i];
		if(!empty($exist_codes) && in_array($code,$exist_codes)) {
			$sqls[] = "update ecshop.brand_or_product set material_number = '{$material_number}',goods_type='{$goods_type}',last_updated_stamp=now() where code='{$code}' limit 1";
		} else {			
			$sqls[] = "insert into ecshop.brand_or_product (code,material_number,goods_type,created_stamp,last_updated_stamp) values('{$code}','{$material_number}','{$goods_type}',now(),now())";
		}
	}
	$result = exec_sql_transaction($sqls);
	return $result;
}

function get_exist_goods($codes) {
	if(empty($codes)) return null;
	global $db;
	$sql = "select p.code
	        from ecshop.brand_or_product p
			where p.code ".db_create_in($codes);
	$exist_codes = $db->getCol($sql);
	return $exist_codes;
}

function search_goods($party_id,$cond) {
	global $db;
	$goods_list = array();
	// 从套餐出发
	$sql = "select gg.code,name as goods_name,ifnull(p.material_number,'') as material_number,p.goods_type,if(p.code is null,1,0) as is_product_empty
	        from ecshop.distribution_group_goods gg
			left join ecshop.brand_or_product p ON p.code = gg.code
			where 
			1 {$cond['tc_cond']}
			and gg.party_id = '{$party_id}' and gg.name like '%market%'
			group by gg.code order by is_product_empty desc";
//    var_dump('$tc_goods_list');var_dump($sql);
	$tc_goods_list = $db->getAll($sql);
	
    // 从goods出发
	$sql = "select g.goods_id as code,g.goods_name,ifnull(p.material_number,'') as material_number,p.goods_type,if(p.code is null,1,0) as is_product_empty 
	        from ecshop.ecs_goods g
	        left join ecshop.ecs_category c ON g.cat_id = c.cat_id 
			left join ecshop.brand_or_product p ON p.code = g.goods_id
			where 
			1 {$cond['simple_cond']}
			and g.goods_party_id = '{$party_id}' and c.cat_name not in('虚拟商品','耗材商品') and g.is_delete = false
			group by g.goods_id order by is_product_empty desc";
//	var_dump('$simple_goods_list');var_dump($sql);
	$simple_goods_list = $db->getAll($sql);
	
	// 合并
	if(!empty($tc_goods_list)) {
		foreach($tc_goods_list as $tc_goods) {
			$goods_list[] = $tc_goods;
		}
	}
	if(!empty($simple_goods_list)) {
		foreach($simple_goods_list as $simple_goods) {
			$goods_list[] = $simple_goods;
		}
	}
	require_once (ROOT_PATH . 'includes/helper/array.php');
	
	$goods_list = Helper_Array::sortByCol($goods_list, 'is_product_empty', SORT_DESC);
	return $goods_list;
}

?>




























