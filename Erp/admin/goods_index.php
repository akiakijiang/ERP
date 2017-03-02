<?php
define ( 'IN_ECS', true );
require_once ('includes/init.php');
require_once ('distribution.inc.php');

require_once ('includes/lib_category.php');
require_once ('includes/lib_goods.php');
include_once ('includes/fckeditor/fckeditor.php');
require_once (ROOT_PATH . 'includes/helper/array.php');
require_once (ROOT_PATH . 'includes/lib_order.php');
require_once (ROOT_PATH . 'includes/helper/uploader.php');
require_once ('function.php');

// 通用商品组织权限特殊判断 ljzhou 2013.07.03

// excel读取设置
$tpl = 
array ('箱规导入' => 
         array ('goods_id' => 'ERP商品编码',  
                'spec' => '箱规' ) );
$tpl_on_sale = 
array ('批量上下架' => 
         array ('goods_id' => 'ERP商品编码',  
                'is_on_sale' => '是否上架' ) );

//为中粮增加一个物料编码字段,该字段存在于brand_zhongliang_product表
$show_item_number_party = array(65625);
$show_item_number = in_array($_SESSION['party_id'],$show_item_number_party);

if ($_REQUEST ['act'] == 'index') {
	$smarty->display ( 'goods_index.htm' );
} else if ($_REQUEST ['act'] == 'start') {
	$smarty->display ( 'goods_start.htm' );
} else if ($_REQUEST ['act'] == 'list') {
	$party_id = $_SESSION ['party_id'];
	$cat_id = $_REQUEST ['cat_id'];

	$party = getOnePartyName ( $party_id );
	$cat_name = mysql_fetch_row ( getOneCatName ( $cat_id ) );
	$goodsList = goods_list ( $cat_id );
	$delete_info = trim($_REQUEST['delete_info']);
	$message = trim($_REQUEST['message']);
	if ($goodsList == - 1) {
		$cat_info = getPartyNameCat($cat_id);
		
		echo "您现在选中是 <font color='red'> ".$cat_info."</font> 组织下的类,</br>请选当前组织  <font color='red'>".$party."</font> 下的类的商品进行编辑!!!";
		return FALSE;
	}
	
	$smarty->assign('message',$message);
	$smarty->assign('delete_info', $delete_info);
	$smarty->assign ( 'goodsList', $goodsList );
	$smarty->assign ( 'cat_name', $cat_name [0] );
	$smarty->assign ( 'party', $party );
	$smarty->assign ( 'party_id', $party_id );
	$smarty->assign ( 'show_item_number' , $show_item_number);
	$smarty->assign ( 'cat_id', $cat_id );
	
	$smarty->display ( 'goods_list.htm' );
}elseif($_REQUEST['act'] == 'is_group_goods'){
	$goods_id = $_REQUEST['goods_id'];
	$sql = " SELECT ggi.*,s.color, gg.valid_from , gg.name as group_name  
				FROM ecshop.distribution_group_goods_item ggi 
				INNER JOIN ecshop.distribution_group_goods gg ON ggi.group_id = gg.group_id 
				LEFT JOIN ecshop.ecs_style s ON ggi.style_id = s.style_id 
				WHERE ggi.goods_id = {$goods_id} AND gg.status = 'OK'   ";
	$r = $db->getAll($sql);
	$result = array("hehe"=>"hehe","data"=>$r); 
	header('Content-Type: application/json');
	echo json_encode($result); 
}elseif  ($_REQUEST['act'] == 'before_del_info') {   // 删除商品前检查 信息 

	$goods_id = $_REQUEST['goods_id'];
	$style_id = 0; 
	if(isset($_REQUEST['style_id']) && is_numeric($_REQUEST['style_id']) ){
		$style_id = intval($_REQUEST['style_id']) ; 
	}

	$result = array(); 
	// 库存 
	$sql = "SELECT IFNULL(sum(ii.quantity_on_hand_total),0) as storage_amount
		from romeo.product_mapping pm 
		INNER JOIN  romeo.inventory_item ii ON pm.product_id = ii.product_id 
		WHERE pm.ecs_goods_id = '{$goods_id}' and pm.ecs_style_id = '{$style_id}'"; 
    $storage =  $db->getRow($sql);
    $result['storage'] = $storage;
    // 出入库记录 
    $three_month_ago = date("Y-m-d 00:00:00", strtotime("-3 month"));  
    $sql = "SELECT iid.* 
			from romeo.product_mapping pm 
			LEFT JOIN romeo.inventory_item ii ON pm.product_id = ii.product_id 
			LEFT JOIN romeo.inventory_item_detail iid on ii.inventory_item_id = iid.inventory_item_id 
			WHERE pm.ecs_goods_id = '{$goods_id}' and pm.ecs_style_id = '{$style_id}'   
			AND iid.created_stamp > '{$three_month_ago}' order by iid.created_stamp desc  LIMIT 5 ";

	$storage_detail =  $db->getAll($sql);
    $result['storage_detail'] = $storage_detail;
    // 该商品参加的套餐 
    $sql = " SELECT ggi.*,s.color, gg.valid_from , gg.name as group_name  
				FROM ecshop.distribution_group_goods_item ggi 
				INNER JOIN ecshop.distribution_group_goods gg ON ggi.group_id = gg.group_id 
				LEFT JOIN ecshop.ecs_style s ON ggi.style_id = s.style_id 
				WHERE ggi.goods_id = {$goods_id} {$cond} AND gg.status = 'OK'   ";
	$r = $db->getAll($sql);
	$count = count($r); 
	$result['group_count'] = $count; 
	$result['group_data'] = $r;
	header('Content-Type: application/json');
	echo json_encode($result);
}


elseif ($_REQUEST['act'] == 'is_group_goods_style') {
	$goods_id = $_REQUEST['goods_id'];
	$cond = "";
	if(isset($_REQUEST['style_id']) && is_numeric($_REQUEST['style_id']) ){
		$style_id =  $_REQUEST['style_id']; 
		$cond = " AND ggi.style_id = '{$style_id}' "; 
	}
	$sql = " SELECT ggi.*,s.color, gg.valid_from , gg.name as group_name  
				FROM ecshop.distribution_group_goods_item ggi 
				INNER JOIN ecshop.distribution_group_goods gg ON ggi.group_id = gg.group_id 
				LEFT JOIN ecshop.ecs_style s ON ggi.style_id = s.style_id 
				WHERE ggi.goods_id = {$goods_id} {$cond} AND gg.status = 'OK'   ";
	$r = $db->getAll($sql);
	$count = count($r); 
	$result = array("count"=>$count,"data"=>$r); 
	header('Content-Type: application/json');
	echo json_encode($result);
}
elseif($_REQUEST['act'] == 'show_download') {
	$party_id = $_SESSION ['party_id'];
	$cat_id = $_REQUEST['cat_id'];
	
	$party = getOnePartyName ( $party_id );
	$cat_name = mysql_fetch_row ( getOneCatName ( $cat_id ) );
	$datasList = data_list ( $cat_id );
	$delete_info = trim($_REQUEST['delete_info']);
	if ($goodsList == - 1) {
		$cat_info = getPartyNameCat($cat_id);
	
		echo "您现在选中是 <font color='red'> ".$cat_info."</font> 组织下的类,</br>请选当前组织  <font color='red'>".$party."</font> 下的类的下载进行编辑!!!";
		return FALSE;
	}
	$smarty->assign('delete_info', $delete_info);
	$smarty->assign ( 'dataList', $datasList );
	$smarty->assign ( 'cat_name', $cat_name [0] );
	$smarty->assign ( 'party', $party );
	$smarty->assign ( 'party_id', $party_id );
	$smarty->assign ( 'cat_id', $cat_id );
	
	$smarty->display ( 'data_list.htm' );
}
else if ($_REQUEST ['act'] == 'search') {
	
	$party_id = $_SESSION ['party_id'];
	$goods_search_value = trim($_POST ['search']);
	$cat_id = trim($_POST ['cat_id']);
	$goods_search_condition = trim($_POST['goods_search_condition']);
	if ($goods_search_value == '') {
		if ($goods_search_condition == 'no_barcode') {
			# code...
			$condition = " AND ((isnull(es.barcode) or es.barcode='') and (isnull(g.barcode) or g.barcode='')) ";
		}
	}else
	{
		if ($goods_search_condition == 'goods_name') {
			$condition = " AND g.goods_name like '%$goods_search_value%'";
		} else if ($goods_search_condition == 'barcode') {
			$condition = " AND (g.barcode like '%$goods_search_value%' or es.barcode like '%$goods_search_value%') ";
		} else if ($goods_search_condition == 'outer_id') {
			if (strpos($goods_search_value, '_') === false) {
				$condition = " AND g.goods_id = '{$goods_search_value}' ";
			} else {
				list($goods_id, $style_id) = explode("_", $goods_search_value);
				$condition = " AND es.goods_id = '{$goods_id}' AND es.style_id = '{$style_id}' ";
			}
		} else if ($goods_search_condition == 'item_number') {
			$condition = " AND bzp.item_number like '%$goods_search_value%'";
		}
	}

	$goodsList = goods_list ( $cat_id, $condition );
	
	$cat_name = mysql_fetch_row ( getOneCatName ( $cat_id ) );
	$party = getOnePartyName ( $party_id );
	
	$smarty->assign ( 'goods_search_condition', $goods_search_condition);
	$smarty->assign ( 'goods_search_value', $goods_search_value );
	$smarty->assign ( 'goodsList', $goodsList );
	$smarty->assign ( 'cat_name', $cat_name [0] );
	$smarty->assign ( 'party', $party );
	$smarty->assign ( 'party_id', $party_id );
	$smarty->assign ( 'cat_id', $cat_id );
	$smarty->assign ( 'show_item_number', $show_item_number);
	
	$smarty->display ( 'goods_list.htm' );
} 

else if ($_REQUEST ['act'] == 'detail') {
	$goods_id = trim($_REQUEST ['goods_id']);
	$style = getGoodsStyles ( $goods_id );
	$goods = getOneGoods ( $goods_id );
	$goods[0]['goods_weight'] = number_format($goods[0]['goods_weight']);
	$goods[0]['goods_volume'] = number_format($goods[0]['goods_volume']);
	$goods[0]['currency'] = $db->getOne("SELECT description FROM romeo.currency where currency_code = '{$goods[0]['currency']}'");
	$goods[0]['purchase_info'] =  getPurchaseInfo($goods[0]['barcode'],$goods[0]['goods_id']); 
	$consumables = getOneConsumableInfo ($goods_id);
	
	$consumable_str = '';
	$length = count($consumables);

	if($length) {
		for($i=0; $i<$length; $i++) {
			if($consumables[$i]['consumable_name'] !=''){
				$consumable_str .= $consumables[$i]['consumable_name'] .',' .$consumables[$i]['consumable_count'] .';' ;
			}			
		}
	} 

	$style_purchase_info = getStylePurchaseInfo($style,$goods[0]['goods_id']);
	for($i=0;$i < count($style);$i++){
		$barcode = $style[$i]['barcode']; 
		if(isset($style_purchase_info[$barcode])){
			$style[$i]['purchase_info'] = $style_purchase_info[$barcode]; 
		}
	}
   
	
	$smarty->assign ( 'consumable_str', $consumable_str );	
	$smarty->assign ( 'style', $style );
	$smarty->assign ( 'goods', $goods [0] );
	$smarty->assign ( 'show_item_number' , $show_item_number);
	$smarty->display ( 'goods_detail.htm' );
}else if($_REQUEST ['act'] == 'editStyle')
{
	$gStyle_id = trim($_REQUEST ['style_id']);
	$goods_id = trim($_REQUEST ['goods_id']); 
	$oneStyle  = getGoodsStyle($gStyle_id);
	$error = array('error'=>'1');
	if(empty($oneStyle)){
		print_r ( json_encode ( $error ) );
	}else{
		$oneStyle['purchase_info'] = getPurchaseInfo($oneStyle['barcode'],$goods_id);
	}
	print_r ( urldecode(json_encode ( $oneStyle )) );
}else if($_REQUEST['act'] == 'editGoodsStyle')
{
	$style_price      = trim($_POST ['style_price']);
	$barcode    	  = trim($_POST ['barcode']);
	$update_style_id  = trim($_POST ['update_style_id']);
	$goods_style_id   = trim($_POST ['goods_style_id']);
	$goods_code       = trim($_POST ['goods_code']);
	$purchase_info = $_POST ['purchase_info'];
	$goods_id =trim($_POST ['goods_id']);
	$info['style_price']	= $style_price;
	$info['barcode']	    = $barcode;
	$info['update_style_id']= $update_style_id;
	$info['goods_style_id']	= $goods_style_id;
	$info['goods_code'] = $goods_code; 
	
	$res = editGoodsStyle($info);
	if($res){
		addPurchaseInfo($info['barcode'],$purchase_info,$goods_id,$_SESSION['party_id']); 
	}
	print_r ( urldecode(json_encode ( $res )) );
}
else if($_REQUEST ['act'] == 'download_edit'){
	require_once (ROOT_PATH."includes/lib_goods.php");
	
	$data_id = trim($_REQUEST ['data_id']);
	$sql = "select * from ecshop.ecs_download_data where data_id = '{$data_id}'";
	$data = $db->getRow($sql);
	$cat_name = get_cate_name(trim($_REQUEST['cat_id']));
	$smarty->assign('cat_name', $cat_name['cat_name']);
	$smarty->assign ( 'data', $data );
	$smarty->display ( 'data_info.htm' );
}
else if ($_REQUEST ['act'] == 'edit') {
	if(check_goods_common_party()) {
		admin_priv('goods_edit_common');
	} else {
	    admin_priv ( 'goods_edit' );
	}
	
	require_once (ROOT_PATH."includes/lib_goods.php");
	$goods_id = trim($_REQUEST ['goods_id']);
	$style = getGoodsStyles ( $goods_id );
	$goods = getOneGoods ( $goods_id );
	$party_id= $_SESSION['party_id'];
	$stylePhonesList = getStylesList ( 1 );
	$styleSportsList = getStylesList ( 2 );
	$styleClothingList = getStylesList ( 3 );
	$styleYearList = getStylesList ( 5 );
	$goods[0]['goods_weight'] = number_format($goods[0]['goods_weight']);
	$goods[0]['goods_volume'] = number_format($goods[0]['goods_volume']);
	$goods[0]['purchase_info'] = getPurchaseInfo($goods[0]['barcode'],$goods_id);

	$cat_name = get_cate_name(trim($_REQUEST['cat_id']));
	
	$consumables = getOneConsumableInfo ($goods_id);
	
	$consumable_str = '';
	$length = count($consumables);
	if($length) {
		for($i=0; $i<$length; $i++) {
			if($consumables[$i]['consumable_name'] != '') {
				$consumable_str .= $consumables[$i]['consumable_name'] .',' .$consumables[$i]['consumable_count'] .';' ;
			}		
		}
	}
	//查询所有的标签
	$sql = "select tag_id,tag_name,party_id,creator,create_time,updater,update_time from tags where party_id='".$party_id."'";
	$tag_list = $db->getAll($sql);
	$sqlmaper ="SELECT tag_id FROM tag_mapping where goods_id =".$goods_id;

	$tagmap_list = $db->getCol($sqlmaper);

	$providers = getProviders();
    $smarty->assign('providers', $providers);

	// 订单录入币种选择
	$currencies = array('HKD' => '港币', 'USD' => '美元', 'RMB' => '人民币');
	$smarty->assign('currency', $currencies);
	$smarty->assign('currencys', get_currency_style()); //币种数组		
   	$product_importance_list = array('A' => 'A类', 'B' => 'B类', 'C' => 'C类');
   	$smarty->assign('product_importance_list', $product_importance_list);
   	
	$consumable_list = getConsumableInfo();
	$smarty->assign('tagmap_list', $tagmap_list);
	$smarty->assign('consumable_list', $consumable_list);		
	$smarty->assign ( 'consumable_str', $consumable_str );
	$smarty->assign('cat_name', $cat_name['cat_name']);
	$smarty->assign ( 'stylePhonesList', $stylePhonesList );
	$smarty->assign ( 'styleSportsList', $styleSportsList );
	$smarty->assign ( 'styleClothingList', $styleClothingList );
	$smarty->assign ( 'styleYearList', $styleYearList );
	$style_purchase_info = getStylePurchaseInfo($style,$goods[0]['goods_id']);
	 
	for($i=0;$i < count($style);$i++){
		$barcode = $style[$i]['barcode']; 
		if(isset($style_purchase_info[$barcode])){
			$style[$i]['purchase_info'] = $style_purchase_info[$barcode]; 
		}
	}
	$smarty->assign ( 'style', $style );
	$smarty->assign ( 'goods', $goods [0] );
	$smarty->assign ( 'show_item_number' , $show_item_number);
	$smarty->assign('tag_list', $tag_list);
	$smarty->display ( 'goods_info.htm' );
	
} else if($_REQUEST ['act'] == 'edit_data'){
	$info['data_id'] = trim($_REQUEST ['data_id']);
	$info['data_name'] = trim($_REQUEST ['data_name']);
	$info['data_size'] = trim($_REQUEST ['data_size']);
	$sql = "select cat_id  from ecshop.ecs_download_data where data_id = '{$info['data_id']}'";
	$cat_id = $db->getOne($sql);
	$res = update_data_info ( $info );
	if($res>0){
		print "<script>window.location.href='goods_index.php?act=show_download&&cat_id=".$cat_id."';</script>";
	}
}
else if ($_REQUEST ['act'] == 'editGoods') {
	$goods_id = trim($_REQUEST ['goods_id']);
	$goods_name = trim($_POST ['goods_name']);
	$goods_sku = trim($_POST ['sku']);
	$barcode = trim($_POST ['barcode']);
	$onSale = trim($_POST ['onSale']);
	$isDisplay = trim($_POST ['isDisplay']);
	$warn_number = trim($_POST ['warn_number']);
	$maintainWeight = trim($_POST ['maintainWeight']);
	$goods_weight = trim($_POST ['goods_weight']);
	$maintainWarranty = trim($_POST ['maintainWarranty']);
	$maintainBatchSn = trim($_POST ['maintainBatchSn']);
	$goods_warranty = trim($_POST['goods_warranty']);
	$contraband = trim($_POST ['contraband']);
	$goods_volume = trim($_POST['goods_volume']);
	$goods_price = trim($_POST['goods_price']);
    $action_user = $_SESSION['admin_name'];
	$consumables = getOneConsumableInfo ($goods_id);
	$tags = $_POST['tags'];
	$consumable_str = '';
	$length = count($consumables);
	$product_importance = $_POST['product_importance'];
    
    // 采购信息 
	$purchase_unit_price = $_POST['purchase_unit_price']; 
	$purchase_provider_id  =  $_POST['purchase_provider_id']; 

   
    $sql ="delete FROM tag_mapping where goods_id =".$goods_id;
    $db->query( $sql);
    	
    if(isset($tags)) {
    	foreach ($tags as $lang){ 
           $mysql = "insert into tag_mapping(tag_id,goods_id,creator,create_time,updater,update_time) values('$lang','{$_REQUEST ['goods_id']}','{$action_user}',NOW(),'{$action_user}',NOW())";
           $db->query( $mysql);
        } 	
    }
	
	if($length) {
		for($i=0; $i<$length; $i++) {
			if($consumables[$i]['consumable_name'] !=''){
				$consumable_str .= $consumables[$i]['consumable_name'] .',' .$consumables[$i]['consumable_count'] .';' ;
			}			
		}
	} 	
	
	$info ['goods_id'] = $goods_id;
	$info ['goods_name'] = $goods_name;
	$info ['goods_sku'] = $goods_sku;
	$info ['barcode'] = $barcode;
	$info ['onSale'] = $onSale;
	$info ['isDisplay'] = $isDisplay;
	$info ['warn_number'] = $warn_number;
	$info ['maintainWeight'] = $maintainWeight;
	$info ['goods_weight'] = $goods_weight;
	$info ['maintainWarranty'] = $maintainWarranty;
	$info ['maintainBatchSn'] = $maintainBatchSn;
	$info ['goods_warranty'] = $goods_warranty;
	$info ['contraband'] = $contraband;
	$info ['last_update'] = time ();
	$info ['goods_volume'] = $goods_volume;
	$info ['goods_price'] = $goods_price;
	$info['goods_length'] = trim($_POST['goods_length']);
	$info['goods_width'] = trim($_POST['goods_width']);
	$info['goods_height'] = trim($_POST['goods_height']);
	$info['box_length'] = trim($_POST['box_length']);
	$info['box_width'] = trim($_POST['box_width']);
	$info['box_height'] = trim($_POST['box_height']);

	$info['goods_code'] = trim($_POST['goods_code']);
	$info['unit_name'] = trim($_POST['unit_name']);

	$info['is_bubble_bag'] = trim($_POST['is_bubble_bag']);
	$info['is_bubble_box'] = trim($_POST['is_bubble_box']);
	$info['bubble_bag_number'] = isset($_POST['bubble_bag_number'])?trim($_POST['bubble_bag_number']):0;
	$info['bubble_box_number'] = isset($_POST['bubble_box_number'])?trim($_POST['bubble_box_number']):0;
	$info['added_fee'] = isset($_POST['added_fee'])?trim($_POST['added_fee']):1.17;
	$info['item_number'] = trim($_POST['item_number']);
	$info['is_fragile'] = $_POST['is_fragile'];
	$info['spec'] = $_POST['spec'];
	$info['currency'] = $_POST['currency'];
	$info['product_importance'] = ($_POST['product_importance'] == "") ? "C" : $_POST['product_importance'];
	$res = update_goods_info ( $info );

	// 添加 采购信息 
	if($res > 0 ){
		  $sql ="delete FROM ecshop.ecs_purchase_goods_price_provider where  goods_id ='{$info['goods_id']}' AND  barcode ='".$info ['barcode']."'";
          $db->query( $sql);
          if(!empty($purchase_unit_price)){
          	foreach ($purchase_unit_price as $key => $unit_price) {
           	    $mysql = "insert into ecshop.ecs_purchase_goods_price_provider(barcode,unit_price,provider_id,created_time,updated_time,goods_id,party_id) 
           	    	    values('{$info['barcode']}','{$unit_price}','{$purchase_provider_id[$key]}',NOW(),NOW(),'{$info['goods_id']}','{$_SESSION['party_id']}')";
                $db->query( $mysql);
          	}
          }
	}
	
	if ($res > 0) {
	    $style = getGoodsStyles ( $goods_id );
	    $goods = getOneGoods ( $goods_id );
	    $goods[0]['goods_weight'] = number_format($goods[0]['goods_weight']);
	    $goods[0]['goods_volume'] = number_format($goods[0]['goods_volume']);
	    $goods[0]['currency'] = $db->getOne("SELECT description FROM romeo.currency where currency_code = '{$goods[0]['currency']}'");
	    $goods[0]['purchase_info'] = getPurchaseInfo($info['barcode'],$info['goods_id']); 
	    $smarty->assign ( 'consumable_str', $consumable_str );	
		$smarty->assign( 'show_item_number' , $show_item_number);
	    $smarty->assign ( 'style', $style );
	    $smarty->assign ( 'goods', $goods [0] );
		$smarty->display ( 'goods_detail.htm' );
	}
} else if ($_REQUEST ['act'] == 'add') {
	$cat_id = $_REQUEST ['cat_id'];
	$party_id=$_SESSION['party_id'];
	$cat_name = mysql_fetch_row ( getOneCatName ( $cat_id ) );	
	$consumable_list = getConsumableInfo();
	$sql = "select tag_id,tag_name,party_id,creator,create_time,updater,update_time from tags where party_id='".$party_id."'";
	$tag_list = $db->getAll($sql);
	
	// 订单录入币种选择
	$currencies = array('HKD' => '港币', 'USD' => '美元', 'RMB' => '人民币');
	$product_importance_list = array('A' => 'A类', 'B' => 'B类', 'C' => 'C类');
	$smarty->assign('currency', $currencies);
	$smarty->assign('currencys', get_currency_style()); //币种数组		
	$smarty->assign('tag_list', $tag_list);
	$smarty->assign('consumable_list', $consumable_list);	
	$smarty->assign ( 'party_id', $party_id);
	$smarty->assign ( 'cat_id', $cat_id );
	$smarty->assign ( 'cat_name', $cat_name [0] );
	$smarty->assign ( 'show_item_number' , $show_item_number);
	$providers = getProviders();
    $smarty->assign('providers', $providers);
    $smarty->assign('product_importance_list', $product_importance_list);
	$smarty->display ( 'goods_add.htm' );
}else if ($_REQUEST ['act'] == 'add_download') {
	$cat_id = $_REQUEST ['cat_id'];
	$cat_name = mysql_fetch_row ( getOneCatName ( $cat_id ) );

	$smarty->assign ( 'cat_id', $cat_id );
	$smarty->assign ( 'cat_name', $cat_name [0] );
	
	$smarty->display ( 'goods_add_download.htm' );
} else if ($_REQUEST ['act'] == 'addGoods') {

	$party_id = $_SESSION ['party_id'];
	$action_user = $_SESSION['admin_name'];
	$goods_name = trim($_POST ['goods_name']);
	$cat_id = $_POST ['cat_id'];

	$top_cat_id = get_top_cat_id ( $cat_id );
	$barcode = trim($_POST ['barcode']);
	$onSale = $_POST ['onSale'];
	$warn_number = trim($_POST ['warn_number']);
	$maintainWeight = trim($_POST ['maintainWeight']);
	$goods_weight = trim($_POST['goods_weight']);
	$maintainWarranty = trim($_POST ['maintainWarranty']);
	$maintainBatchSn = trim($_POST ['maintainBatchSn']);
	$goods_warranty = trim($_POST['goods_warranty']);
	$contraband = trim($_POST ['contraband']);
	$goods_volume = trim($_POST['goods_volume']);
	$goods_price = trim($_POST['goods_price']);	
	$consumable_id = trim($_POST['consumable_id']);
	$consumable_count = trim($_POST['consumable_count']);
	$product_importance = trim($_POST['product_importance']);
   
     $tags = $_POST['tags'];

     
	$info ['party_id'] = $party_id;
	$info ['top_cat_id'] = $top_cat_id;
	$info ['goods_name'] = $goods_name;
	$info ['cat_id'] = $cat_id;
	$info ['barcode'] = $barcode;
	$info ['onSale'] = $onSale;
	$info ['warn_number'] = $warn_number;
	$info ['maintainWeight'] = $maintainWeight;
	$info ['goods_weight'] = $goods_weight;
	$info ['maintainWarranty'] = $maintainWarranty;
	$info ['maintainBatchSn'] = $maintainBatchSn;
	$info ['goods_warranty'] = $goods_warranty;
	$info ['contraband'] = $contraband;
	$info ['add_time'] = time ();
	$info ['goods_volume'] = $goods_volume;
	$info ['brand_id'] = '0';	
	$info ['goods_price'] = $goods_price;
	$info['goods_length'] = trim($_POST['goods_length']);
	$info['goods_width'] = trim($_POST['goods_width']);
	$info['goods_height'] = trim($_POST['goods_height']);

    $info['box_length'] = trim($_POST['box_length']);
	$info['box_width'] = trim($_POST['box_width']);
	$info['box_height'] = trim($_POST['box_height']);
	$info['goods_code'] = trim($_POST['goods_code']);
	$info['unit_name'] = trim($_POST['unit_name']);

	$info['is_bubble_bag'] = trim($_POST['is_bubble_bag']);
	$info['is_bubble_box'] = trim($_POST['is_bubble_box']);
	$info['added_fee'] = trim($_POST['added_fee']);
	$info['item_number'] = trim($_POST['item_number']);
	$info['is_fragile'] = $_POST['is_fragile'];
	$info['spec'] = $_POST['spec'];
	$info['currency'] = $_POST['currency'];
	$info['product_importance'] = ($_POST['product_importance']=="") ? "C" : $_POST['product_importance'];
	$purchase_unit_price = $_POST['purchase_unit_price']; 
    $purchase_provider_id = $_POST['purchase_provider_id']; 

	if($party_id == 16) {					//电教
		if($cat_id == '1862') {	
			$info ['brand_id'] = '581';		//配件
		} elseif ($cat_id == '1517') {	
			$info ['brand_id'] = '567';		//教材
		} elseif ($cat_id == '1512') {	
			$info ['brand_id'] = '51';		//步步高
		} 
	}
	$res = add_goods_info ( $info );

	if ($res > 0) {
		if(!empty($purchase_unit_price)){
	        foreach ($purchase_unit_price as $key => $unit_price) {
	        	if( is_numeric($unit_price)  && is_numeric( $purchase_provider_id[$key] ) ){
	        		$mysql = "insert into ecshop.ecs_purchase_goods_price_provider(barcode,unit_price,provider_id,created_time,updated_time,goods_id,party_id) 
	           	    	    values('{$info['barcode']}','{$unit_price}','{$purchase_provider_id[$key]}',NOW(),NOW(),'{$res}','{$_SESSION['party_id']}')";
	            	$db->query( $mysql);
	        	}
	        }
		}
		// 增加product_mapping映射
		require_once ROOT_PATH . 'RomeoApi/lib_inventory.php';
		$sql = "select goods_id from ecshop.ecs_goods where goods_party_id = '{$info ['party_id']}' and barcode = '{$info ['barcode']}' and goods_name = '{$info ['goods_name']}' limit 1";
		$good_id = $db->getOne($sql);
		if(!empty($good_id)) {
		   getProductId($good_id,0);
		}

		//更新ecs_goods_consumable表  created_stamp = now()
		if($consumable_id != '0') {
			$sql = "insert into " .$GLOBALS ['ecs']->table ( 'goods_consumable' ) . " 
				(goods_id, consumable_id, consumable_count, action_user,created_stamp,last_update_stamp) 
				values('{$good_id}', '{$consumable_id}', '{$consumable_count}', '{$action_user}', now(),now())";
			$result = $db->query ( $sql );
		
		}
		if(isset($tags)){
			foreach ($tags as $lang){ 
	           $mysql = "insert into tag_mapping(tag_id,goods_id,creator,create_time,updater,update_time) values('$lang','{$good_id}','{$action_user}',NOW(),'{$action_user}',NOW())";
	           $db->query( $mysql);
       		} 
		}		
		print "<script>window.location.href='goods_index.php?act=list&&cat_id=".$cat_id."';</script>";
	}
}
elseif($_REQUEST ['act'] == 'batch_spec_add') {
	$cat_id = trim($_POST ['cat_id']);	
	$party_id = $_SESSION ['party_id'];
	$execl_info = readExcel($tpl);
	if(empty($execl_info['result'])) {
		$message = $execl_info['error_info'];
	} else {		
		$result = $execl_info['result'];
		/* 检查数据  */
		$rowset = $result ['箱规导入'];		
		// 订单数据读取失败
		if (empty ( $rowset )) {
			$message = 'excel文件中没有数据,请检查文件';
		} else {
			$goods_id = Helper_Array::getCols ( $rowset, 'goods_id' );
			$spec = Helper_Array::getCols ( $rowset, 'spec' );			
			// 检查商品数据中是否有空内容
			$is_empty = false;
			foreach ( array_keys ( $tpl ['箱规导入'] ) as $val ) {
				$in_val = Helper_Array::getCols ( $rowset, $val );
				$in_len = count ( $in_val );
				Helper_Array::removeEmpty ( $in_val );
				if (empty ( $in_val ) || $in_len > count ( $in_val )) {
					$message = "文件中存在空的{$tpl['箱规导入'][$val]}，请确保有数据的行都是完整的";
					$is_empty = true;
				}
			}		
		  	if(!$is_empty) {
		  		if (count ( $goods_id ) > count ( array_unique ( $goods_id ) )) {
					$message = '文件中存在重复的ERP商品编码';
				} else {
					$goods_spec_import_fail = array ();
					$goods_spec_import_success = array();
					foreach ( $rowset as $key => $row ) {				
						$sql_goods_id = "select count(*) from ecshop.ecs_goods  
								where goods_id = '{$row['goods_id']}'  and goods_party_id = '{$party_id}' and is_delete = 0";
						$goods_id_count = $db -> getOne($sql_goods_id);
						if($goods_id_count == 1) {
							$sql = "update ecshop.ecs_goods 
								set spec = '{$row['spec']}' 
								where goods_id = '{$row['goods_id']}' and goods_party_id = '{$party_id}' and is_delete = 0";			
							$result = $GLOBALS ['db']->query ( $sql );
							if($result) {
								$goods_spec_import_success[] = $row['goods_id'];
							} else {
								$goods_spec_import_fail[] = $row['goods_id'];
							}
							$riskysql = "INSERT INTO risky_actions (admin_user, type, timestamp, php_location, form_name, `sql`, comment) " .
            							"VALUES ('{$_SESSION['admin_name']}', 'update', NOW(), 'goods_index.php', 'form', '".mysql_real_escape_string($sql)."', '商品导入箱规')";
							
							$db->query($riskysql);								
						} else {
							$goods_spec_import_fail[] = $row['goods_id'];
						}					
					}		
					if(!empty($goods_spec_import_success)) {
						$message .= implode(',', $goods_spec_import_success).'添加箱规成功！<br/>';
					}
					if(!empty($goods_spec_import_fail)) {
						$message .= implode(',', $goods_spec_import_fail).'添加箱规失败，请检查数据后再导入！';
					}			
				}			
      		}			
		}		
	}	
	$url = "goods_index.php?act=list&cat_id=". $cat_id . "&message=".$message;
	header("Location: {$url}"); 	
}elseif ( $_REQUEST ['act'] == 'down_sale') {
	// 下架商品 goods 级别 
	$goods_ids = trim($_REQUEST['goods_ids']);
	$goods_ids = explode(",",$goods_ids);
	$goods_modify_success = array();
	$goods_modify_fail = array( ); 
	$party_id = $_SESSION ['party_id']; 
	foreach ($goods_ids as $key => $goods_id) {
		$sql_goods_id = "select count(*) from ecshop.ecs_goods  
								where goods_id = '{$goods_id}'  and goods_party_id = '{$party_id}' and is_delete = 0";
		$goods_id_count = $db -> getOne($sql_goods_id);
		if(empty($goods_id_count)){
			$goods_modify_fail[] = $goods_id; 
		}else{
			$sql = "update ecshop.ecs_goods 
				    set is_on_sale = '0' 
					where goods_id = '{$goods_id}' and goods_party_id = '{$party_id}' and is_delete = 0";			
			$result = $GLOBALS ['db']->query ( $sql );
			if($result) {
				$riskysql = "INSERT INTO risky_actions (admin_user, type, timestamp, php_location, form_name, `sql`, comment) " .
            							"VALUES ('{$_SESSION['admin_name']}', 'update', NOW(), 'goods_index.php', 'form', '".mysql_real_escape_string($sql)."', '商品下架')";
				$db->query($riskysql);	
				$goods_modify_success[] = $goods_id;
			} else {
				$goods_modify_fail[] = $goods_id;
			}		
		}
	}	
	$has_fail = 0; 
	$message = ""; 
	if( count($goods_modify_success) > 0 ){
		$message .= implode(",",$goods_modify_success)." 商品下架成功";
	}
	if( count($goods_modify_fail) > 0 ){
		$message = implode(",",$goods_modify_fail)." 下架失败，可能是因为该商品已经删除"; 
		$has_fail = 1; 
	}  
	$result = array(
		"message"=>$message ,
		"has_fail"=>$has_fail
		); 
	echo json_encode($result); 
}
elseif($_REQUEST ['act'] == 'batch_on_sale') {
	$cat_id = trim($_REQUEST ['cat_id']);	
	$party_id = $_SESSION ['party_id'];
	$execl_info = readExcel($tpl_on_sale);
	if(empty($execl_info['result'])) {
		$message = $execl_info['error_info'];
	} else {		
		$result = $execl_info['result'];
		/* 检查数据  */
		$rowset = $result ['批量上下架'];		
		// 订单数据读取失败
		if (empty ( $rowset )) {
			$message = 'excel文件中没有数据,请检查文件';
		} else {
			$goods_id = Helper_Array::getCols ( $rowset, 'goods_id' );
			$is_on_sale = Helper_Array::getCols ( $rowset, 'is_on_sale' );	

			// 检查商品数据中是否有空内容
			$is_empty = false;
			foreach ( array_keys ( $tpl_on_sale ['批量上下架'] ) as $val ) {
				$in_val = Helper_Array::getCols ( $rowset, $val );
				$in_len = count ( $in_val );
				Helper_Array::removeEmpty ( $in_val );
				if (empty ( $in_val ) || $in_len > count ( $in_val )) {
					$message = "文件中存在空的{$tpl_on_sale['批量上下架'][$val]}，请确保有数据的行都是完整的";
					$is_empty = true;
				}
			}		
		  	if(!$is_empty) {
		  		if (count ( $goods_id ) > count ( array_unique ( $goods_id ) )) {
					$message = '文件中存在重复的ERP商品编码';
				} else {
					$goods_modify_fail = array ();
					$goods_modify_success = array();
					$goods_has_group = array( ); 
					foreach ( $rowset as $key => $row ) {				
						$sql_goods_id = "select count(*) from ecshop.ecs_goods  
								where goods_id = '{$row['goods_id']}'  and goods_party_id = '{$party_id}' and is_delete = 0";
						$goods_id_count = $db -> getOne($sql_goods_id);

						if($goods_id_count == 1 && in_array($row['is_on_sale'],array('1','0'))) {
							$noGroup = array( );
							$noGroup['no'] = true;  
							if( intval($row['is_on_sale']) == 0 ){
								$noGroup = isHasGroup($row['goods_id']);
							}
							if($noGroup['no'] == true){
								$sql = "update ecshop.ecs_goods 
									set is_on_sale = '{$row['is_on_sale']}' 
									where goods_id = '{$row['goods_id']}' and goods_party_id = '{$party_id}' and is_delete = 0";			
								$result = $GLOBALS ['db']->query ( $sql );
								if($result) {
									$riskysql = "INSERT INTO risky_actions (admin_user, type, timestamp, php_location, form_name, `sql`, comment) " .
            							"VALUES ('{$_SESSION['admin_name']}', 'update', NOW(), 'goods_index.php', 'form', '".mysql_real_escape_string($sql)."', '商品批量上下架')";
									
									$db->query($riskysql);	
									
									$goods_modify_success[] = $row['goods_id'];
								} else {
									$goods_modify_fail[] = $row['goods_id'];
								}	
							}else{
								$goods_has_group[] = array("goods_id"=>$row['goods_id'],"group"=>$noGroup['data'],"is_on_sale"=>$row['is_on_sale']); 
							}
						} else {
							$goods_modify_fail[] = $row['goods_id'];
						}					
					}		

					if(!empty($goods_modify_success)) {
						$message .= implode(',', $goods_modify_success).'批量上下架成功！';
					}
					if(!empty($goods_modify_fail)) {
						$message .= implode(',', $goods_modify_fail).'批量上下架失败，请检查数据后再导入！';
					}			
				}			
      		}			
		}		
	}	
	$result = array( );
	$result['message'] = $message;
	$result['goods_has_group'] = $goods_has_group; 
	// header('Content-Type: application/json'); 
	echo json_encode($result);
}
elseif($_REQUEST ['act'] == 'add_download_data'){
	$cat_id = $_REQUEST['cat_id'];
	
	$info ['party_id'] =$_SESSION ['party_id'];
	$info ['top_cat_id'] = get_top_cat_id ( $cat_id );
	$info ['data_name'] = trim($_POST ['data_name']);
	$info ['cat_id'] = $_POST ['cat_id'];
	$info ['goods_weight'] = trim($_POST['goods_weight']);
	$info ['add_time'] = time ();
	$res = add_download_info ( $info );

	if ($res > 0) {
		print "<script>window.location.href='goods_index.php?act=show_download&&cat_id=".$cat_id."';</script>";
	}
}
else if ($_REQUEST ['act'] == 'ajax') {
	$goods_id = trim($_POST ['goods_id']);
	$style_id = trim($_POST ['style_id']);
	$goods_price = trim($_POST ['goods_price']);
	$barcode = trim($_POST ['barcode']);
	$goods_code = trim($_POST['goods_code']); 
	$purchase_info = $_POST['purchase_info']; 
	
	$info ['goods_id'] = $goods_id;
	$info ['style_id'] = $style_id;
	$info ['goods_price'] = $goods_price;
	$info ['barcode'] = $barcode;
	$info['goods_code'] = $goods_code; 
	// 检测原始goods_id的商品是否入过库了 ljzhou 2013.02.22
	$sql = "select 1 
			from ecshop.ecs_order_info o 
			inner join ecshop.ecs_order_goods og on o.order_id = og.order_id 
			where o.order_type_id = 'PURCHASE' and og.goods_id = {$goods_id} and og.style_id = 0
			limit 1 ";
	
	if ($db->getOne($sql)) {
		$res = -4;
	} else {
		$res = add_goods_style ( $info );
		// 增加product_mapping映射
		if($res > 0) {
			require_once ROOT_PATH . 'RomeoApi/lib_inventory.php';
		    getProductId($info['goods_id'],$info['style_id']);
		    addPurchaseInfo($barcode,$purchase_info,$goods_id,$_SESSION['party_id']); 
		}
	}
	
	print_r ( urldecode(json_encode ( $res )) );

} else if ($_REQUEST ['act'] == 'checkBarCode') {
	$barcode = trim($_REQUEST ['goodsBar']);
	$goods_id = trim($_REQUEST ['goods_id']);
	
	$res_state = checkBarCode ( $barcode, $goods_id );
	
	if ($res_state == - 2) {
		die ( 'ok' );
	} else if ($res_state == - 1) {
		die ( '-1' );
	} else {
		die ( 'error' );
	}
} 
 else if ($_REQUEST ['act'] == 'getNewBarCode') {
	$goods_id = trim($_REQUEST ['goods_id']);
	$temp=get_order_sn();
	$barcode="444".substr($temp,0,strlen($temp)-4);
	//die($barcode);
	$res_state = checkBarCode ( $barcode, $goods_id );
	//pp($barcode);
	//die();
	if ($res_state == - 2) {
		die ( $barcode );
	} else  {
		die ( 'failed' );
	}
} 

else if ($_REQUEST ['act'] == 'AddConsumable') {
	$action_user = $_SESSION['admin_name'];
	$goods_id = trim($_REQUEST ['goods_id']);
	$consumable_id = trim($_REQUEST ['consumable_id']);
	$consumable_count = trim($_REQUEST ['consumable_count']);

	//更新ecs_goods_consumable表  判断数据库中是否存在，存在更新数据，不存在插入数据
 	$sql = "SELECT * FROM " . $GLOBALS ['ecs']->table ( 'goods_consumable' ) . " 
			where goods_id = " .$goods_id ." AND consumable_id = " .$consumable_id;
	$res = $GLOBALS ['db']->getAll ( $sql );
	if($res) {
		$sql = "update " .$GLOBALS ['ecs']->table ( 'goods_consumable' ) . " set 
				consumable_count =  '$consumable_count', 
				action_user = '$action_user',
				last_update_stamp = now() 
				where goods_id = '$goods_id' AND consumable_id = '$consumable_id' ";
		$result = $GLOBALS ['db']->query ( $sql );
	} else {
		$sql = "insert into " .$GLOBALS ['ecs']->table ( 'goods_consumable' ) . " 
				(goods_id, consumable_id, consumable_count, action_user,created_stamp,last_update_stamp) 
				values('{$goods_id}', '{$consumable_id}', '{$consumable_count}', '{$action_user}', now(), now())";
		$result = $db->query ( $sql );
	}	
	if (!$result) {
		die ( 'failed' );
	}
} 

else if ($_REQUEST ['act'] == 'DelConsumable') {
	$goods_id = trim($_REQUEST ['goods_id']);
	$consumable_id = trim($_REQUEST ['consumable_id']);
	
	//判断是否存在，存在删除数据，不存在返回failed
	$sql = "SELECT * FROM " . $GLOBALS ['ecs']->table ( 'goods_consumable' ) . " 
			where goods_id = " .$goods_id ." AND consumable_id = " .$consumable_id;
	$res = $GLOBALS ['db']->getAll ( $sql );	
	if($res) {
		$sql = "DELETE FROM " . $GLOBALS ['ecs']->table ( 'goods_consumable' ) . "
				where goods_id = " .$goods_id ." AND consumable_id = " .$consumable_id;
		$res = $GLOBALS ['db']->getAll ( $sql );
	} else {
		die('failed');
	}
}



else if ($_REQUEST ['act'] == 'addStyle') {
	$type = trim($_POST ['type']);
	$color = trim($_POST ['color']);
	$value = trim($_POST ['value']);
	
	$info ['color'] = $color;
	$info ['type'] = $type;
	$info ['value'] = $value;
	
	$res = add_ecs_style ( $info );
	$return = array ('type' => $type, 'style_id' => $res );
	
	print_r ( urldecode(json_encode ( $return )) );
} 
else if ($_REQUEST ['act'] == 'delete') {
	$goods_id = trim($_REQUEST['goods_id']);
	$style_id = trim($_REQUEST['style_id']);
	$cat_id = trim($_REQUEST['cat_id']);

	if ($style_id) {
		$outer_id = $goods_id."_".$style_id;
	} else {
		$outer_id = $goods_id;
	}

	if ($style_id) {
		$sql = " update ecshop.ecs_goods_style set is_delete = 1, barcode = null, last_update_stamp = now() where goods_id = '{$goods_id}' and style_id = '{$style_id}' limit 1 ";
	} else {
		$sql = "update ecshop.ecs_goods set is_delete = 1, is_on_sale = 0, barcode = null, last_update_stamp = now() where goods_id = '{$goods_id}' and ". party_sql('goods_party_id')." limit 1 ";
	}
	if ($db->query($sql)) {
		$riskysql = "INSERT INTO risky_actions (admin_user, type, timestamp, php_location, form_name, `sql`, comment) " .
            							"VALUES ('{$_SESSION['admin_name']}', 'delete', NOW(), 'goods_index.php', 'form', '".mysql_real_escape_string($sql)."', '删除商品：{$outer_id}')";
		$db->query($riskysql);
		
		$delete_info = " 商家编码 ".$outer_id. "已经删除成功";
	} else {
		$delete_info = " 商家编码 ".$outer_id. "删除失败，请重新操作";
	}

	$url = "goods_index.php?act=list&cat_id=". $cat_id . "&delete_info=".$delete_info;
	header("Location: {$url}"); 
} else if ($_REQUEST ['act'] == 'delete_data') {
	$data_id = trim($_REQUEST['data_id']);
	$cat_id = trim($_REQUEST['cat_id']);
	$sql = " delete from  ecshop.ecs_download_data where data_id = '{$data_id}' and ". party_sql('data_party_id')." limit 1 ";
	if ($db->query($sql)) {
		$delete_info = "已经删除成功";
	} else {
		$delete_info = "删除失败，请重新操作";
	}
	$url = "goods_index.php?act=show_download&cat_id=". $cat_id . "&delete_info=".$delete_info;
	header("Location: {$url}");
}
else if ($_REQUEST ['act'] == 'checkColorName') {
	$color = trim($_REQUEST['color']);
	if (empty($color)) {
		echo json_encode ("error");
		exit();
	}
	$sql = "select 1 from ecshop.ecs_style where type = 1 and color = '{$color}' limit 1;";
	if ($db->getOne($sql)){
		echo json_encode ("success");
	} else {
		echo json_encode ("error");
	}
	exit();
}
else if ($_REQUEST['act'] == "export") {
	$sql = "select p.name, c.cat_name, g.goods_name, if(gs.style_id is null, g.goods_id, concat(g.goods_id, '_', gs.style_id)) outer_id, 
			g.barcode goods_barcode, gs.barcode sku_barcode,if(gs.style_id is null,ifnull(g.goods_code,''),ifnull(gs.goods_code,'')) as goods_code, 
			s.color, g.goods_warranty, if(gs.style_id is null, g.shop_price, gs.style_price) price, g.goods_weight, g.spec,  
			g.unit_name, g.goods_length, g.goods_width, g.goods_height, g.goods_volume, 
			cast(GROUP_CONCAT(CONCAT_WS(' ',ec.consumable_name,egc.consumable_count)) as char(100)) AS goods_consumable, 
			g.warn_number, g.added_fee, g.currency, g.product_importance, if(g.is_maintain_batch_sn = 0, '否', '是') AS is_maintain_batch_sn
			from ecshop.ecs_goods g 
				left join ecshop.ecs_goods_style gs on g.goods_id = gs.goods_id and gs.is_delete=0
				left join ecshop.ecs_category c on g.cat_id = c.cat_id
				left join romeo.party p on g.goods_party_id = p.party_id
				left join ecshop.ecs_style s on gs.style_id = s.style_id  
				LEFT JOIN ecshop.ecs_goods_consumable egc ON g.goods_id = egc.goods_id
				LEFT JOIN ecshop.ecs_consumable ec on egc.consumable_id = ec.consumable_id  
			where g.goods_party_id = {$_SESSION ['party_id']} and if(gs.style_id is null, g.is_delete, gs.is_delete) = 0 
			GROUP BY g.goods_id, gs.style_id";
	$list = $db->getAll($sql);
	$smarty->assign('list', $list);
	header("Content-type:application/vnd.ms-excel");
    header("Content-Disposition:filename=" . iconv("UTF-8","GB18030","商品信息表") . ".csv");
    $out = $smarty->fetch('oukooext/goods_list_csv.htm');
    echo iconv("UTF-8","GB18030", $out);
    exit();
}

// 根据barcode 添加采购信息 
function addPurchaseInfo($barcode,$purchase_info,$goods_id,$party_id){
	if(empty($barcode)) return; 
	global $db; 
    $sql ="delete FROM ecshop.ecs_purchase_goods_price_provider where goods_id ='{$goods_id}' AND  barcode ='".$barcode."'";
    $db->query( $sql);
    if(isset($purchase_info)){
    	foreach ($purchase_info as $key => $info) {
    		if( is_numeric($info['unit_price'])  && is_numeric( $info['provider_id'] ) ){
	       	    $mysql = "insert into ecshop.ecs_purchase_goods_price_provider(barcode,unit_price,provider_id,created_time,updated_time,goods_id,party_id) 
	           	    	    values('{$barcode}','{$info['unit_price']}','{$info['provider_id']}',NOW(),NOW(),'{$goods_id}','{$party_id}')";
	            $db->query( $mysql);
	        } 
    	}
    }
}

//  某个 style商品 获取编辑商品时填写的采购信息 
function getStylePurchaseInfo($styles,$goods_id){
	if( empty($styles)){
		return array(); 
	}
	$barcodes = "";
	foreach ($styles as $key => $value) {
		 $barcodes.="'{$value['barcode']}',"; 
	}
	$barcodes = "(".rtrim($barcodes,",").")";

	global $db;
	$sql = "SELECT cp.*,p.provider_name FROM ecs_purchase_goods_price_provider cp  INNER JOIN ecshop.ecs_provider p 
		on cp.provider_id = p.provider_id   WHERE cp.goods_id ='{$goods_id}' and cp.barcode  in {$barcodes} and cp.is_delete = 0 "; 
	$r = $db->getAll($sql); 
	$result = array(); 
	foreach ($r as $key => $value) {
		$result[$value['barcode']][] = $value;
	}
	return $result; 
}

// 获取编辑商品时填写的采购信息 
function getPurchaseInfo($barcode,$goods_id){
	if( empty($barcode)){
		return array(); 
	}
	global $db;
	$sql = "SELECT cp.*,p.provider_name FROM ecs_purchase_goods_price_provider cp  INNER JOIN ecshop.ecs_provider p 
		on cp.provider_id = p.provider_id   WHERE  cp.goods_id = '{$goods_id}' and cp.barcode = '$barcode' and cp.is_delete = 0 "; 
	$r = $db->getAll($sql); 
	return $r; 
}

// 判断该商品是否在套餐中 
function isHasGroup($goods_id){
	global $db; 
	$sql = " SELECT ggi.*, gg.name as group_name 
		FROM ecshop.distribution_group_goods_item ggi 
		INNER JOIN ecshop.distribution_group_goods gg ON ggi.group_id = gg.group_id 
		WHERE ggi.goods_id = {$goods_id} AND gg.status = 'OK'   ";
	$r = $db->getAll($sql);
	$result = array(); 
    if(empty($r)){
    	$result['no'] = true; 
    }else{
    	$result['no'] = false; 
    }
    $result['data'] = $r; 
    return $result; 
}

function readExcel($tpl) {
	$excel_info = array();
	$uploader = new Helper_Uploader ();
	$max_size = $uploader->allowedUploadSize (); // 允许上传的最大值
	if (! $uploader->existsFile ( 'excel' )) {
		$excel_info['error_info'] = '没有选择上传文件，或者文件上传失败';
		return $excel_info;

	} 
	// 取得要上传的文件句柄
	$file = $uploader->file ( 'excel' );		
	// 检查上传文件
	if (! $file->isValid ( 'xls, xlsx', $max_size )) {
		$excel_info['error_info'] = '非法的文件! 请检查文件类型类型(xls, xlsx), 并且系统限制的上传大小为' . $max_size / 1024 / 1024 . 'MB';
		return $excel_info;
	} 		
	// 读取excel
	$result = excel_read ( $file->filepath (), $tpl, $file->extname (), $failed );
	$excel_info['result'] = $result;
	if (! empty ( $failed )) {
		$excel_info['error_info'] = reset ( $failed );
		return $excel_info;
	}	
	return $excel_info;		
}
?>