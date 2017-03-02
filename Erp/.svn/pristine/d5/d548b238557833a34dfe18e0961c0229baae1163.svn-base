<?php

/**
 * 乐其商品 批量导入功能
 * 
 * @author mjzhou@oukoo.com
 * @copyright 2012 ouku.com 
 */

define ( 'IN_ECS', true );
require_once ('includes/init.php');
require_once ('function.php');
require_once (ROOT_PATH . 'RomeoApi/lib_payment.php');
require_once (ROOT_PATH . 'includes/lib_order.php');
require_once (ROOT_PATH . 'includes/helper/array.php');
require_once (ROOT_PATH . 'includes/helper/uploader.php');
require_once (ROOT_PATH . 'includes/debug/lib_log.php');
// 通用商品组织权限特殊判断 ljzhou 2013.07.03
if(check_goods_common_party()) {
	admin_priv('goods_edit_common');
} else {
    admin_priv ( 'goods_edit' );
}

$act = // 动作
isset ( $_REQUEST ['act'] ) && in_array ( $_REQUEST ['act'], array ('list_excel', 'upload', 'download_leqee', 'download_bymboree', 'download_style','download_model_product' ) ) ? $_REQUEST ['act'] : null;
$info = // 返回的信息
isset ( $_REQUEST ['info'] ) && trim ( $_REQUEST ['info'] ) ? urldecode ( $_REQUEST ['info'] ) : false;

$item_select =  // 调用模板
isset($_REQUEST['item_select'])  ? $_REQUEST['item_select'] : false ;

// 信息
if ($info) {
	$smarty->assign ( 'message', $info );
}

// 当前时间 
$now = date ( 'Y-m-d H:i:s' );

// excel读取设置
$tpl = 
array ('乐其商品导入' => 
         array ('goods_name' => '商品名称', 
                'goods_barcode' => '商品条码', 
                'cat_name' => '商品分类', 
                'goods_weight' => '商品重量(g)', 
                'goods_volume' => '商品体积(cm^3)', 
                'is_on_sale' => '是否上架', 
                'warn_number' => '警告库存', 
                'style_price' => '商品价格(元)', 
                'sku_barcode' => 'SKU条码', 
                'style_id' => '样式ID',
                'is_maintain_warranty' => '是否维护保质期',
                'goods_warranty' => '保质期(月)',
                'box_length'=>'箱子长度(cm)',
                'box_width'=>'箱子宽度(cm)',
                'box_height'=>'箱子高度(cm)',
                'spec'=>'箱规',
                'added_fee'=>'商品税率(默认1.17)',
                'product_importance'=>'产品重要性(A、B、C)'
                 ) );
$can_empty_cols = array("sku_barcode","style_id","added_fee","box_length","box_width","box_height"); 

$is_on_sale = array ('是' => TRUE, '否' => FALSE );
$is_maintain_warranty = array ('是' => TRUE, '否' => FALSE );

// 由用户的party_id和权限来决定可以选择业务
$item_list = array ();

$sql = "select last_update_stamp from ecshop.brand_gymboree_product";
$item_list = Helper_Array::toHashmap ( ( array ) $db->getAll ( $sql ), 'last_update_stamp','last_update_stamp' );

/*
 * 处理post请求
 */

QLog::log ( "商品导入[更新]：{$act} " );
if ($_SERVER ['REQUEST_METHOD'] == 'POST' && $act) {
	
	switch ($act) {
		
		/**
		 * 读取excel内容，并在页面列出
		 */
		case 'list_excel' :
			
			QLog::log ( '商品列出：' );
			/* 文件上传并读取 */
			@set_time_limit ( 300 );
			$uploader = new Helper_Uploader ();
			$max_size = $uploader->allowedUploadSize (); // 允许上传的最大值
				
			if (! $uploader->existsFile ( 'excel' )) {
				$smarty->assign ( 'message', '没有选择上传文件，或者文件上传失败' );
				break;
			}
				
			// 取得要上传的文件句柄
			$file = $uploader->file ( 'excel' );
				
			// 检查上传文件
			if (! $file->isValid ( 'xls, xlsx', $max_size )) {
				$smarty->assign ( 'message', '非法的文件! 请检查文件类型类型(xls, xlsx), 并且系统限制的上传大小为' . $max_size / 1024 / 1024 . 'MB' );
				break;
			}
				
			// 读取excel
			$result = excel_read ( $file->filepath (), $tpl, $file->extname (), $failed );
			if (! empty ( $failed )) {
				$smarty->assign ( 'message', reset ( $failed ) );
				break;
			}
				
			/* 检查数据  */
			$rowset = $result ['乐其商品导入'];
				
			// 订单数据读取失败
			if (empty ( $rowset )) {
				$smarty->assign ( 'message', 'excel文件中没有数据,请检查文件' );
				break;
			}
				
			$i = 0;
			$goods_import_list = array();
			foreach ($rowset as $key => $row) {
				$goods_import_list['insert'][$i] = $row;
				$i++;
			}
			
			// 删除上传的文件
			$file->unlink ();
			$smarty->assign ( 'goods_import_list', $goods_import_list );
			$smarty->assign ( 'message', "读取完毕，请检查导入数据" );

			break;

		/**
		 * 上传文件， 检查上传的excel格式，并读取数据提取并添加收款 
		 */
		case 'upload' :

			QLog::log ( '商品导入：' );
			/* 文件上传并读取 */
			@set_time_limit ( 300 );
			$uploader = new Helper_Uploader ();
			$max_size = $uploader->allowedUploadSize (); // 允许上传的最大值
			

			if (! $uploader->existsFile ( 'excel' )) {
				$smarty->assign ( 'message', '没有选择上传文件，或者文件上传失败' );
				break;
			}
			
			// 取得要上传的文件句柄
			$file = $uploader->file ( 'excel' );
			
			// 检查上传文件
			if (! $file->isValid ( 'xls, xlsx', $max_size )) {
				$smarty->assign ( 'message', '非法的文件! 请检查文件类型类型(xls, xlsx), 并且系统限制的上传大小为' . $max_size / 1024 / 1024 . 'MB' );
				break;
			}
			
			// 读取excel
			$result = excel_read ( $file->filepath (), $tpl, $file->extname (), $failed );
			if (! empty ( $failed )) {
				$smarty->assign ( 'message', reset ( $failed ) );
				break;
			}
			
			/* 检查数据  */
			$rowset = $result ['乐其商品导入'];


			
			// 订单数据读取失败
			if (empty ( $rowset )) {
				$smarty->assign ( 'message', 'excel文件中没有数据,请检查文件' );
				break;
			}
			
			$in_goods_barcode = Helper_Array::getCols ( $rowset, 'goods_barcode' );
			$in_sku_barcode = Helper_Array::getCols ( $rowset, 'sku_barcode' );
			$in_is_on_sale = Helper_Array::getCols ( $rowset, 'is_on_sale' );
			$in_cat_name = Helper_Array::getCols ( $rowset, 'cat_name' );
			$in_style_id = Helper_Array::getCols ( $rowset, 'style_id' );
			$in_is_maintain_warranty = Helper_Array::getCols ( $rowset, 'is_maintain_warranty' );
 
            // 检查商品数据中是否有空内容
			$empty_col = false;
			foreach ( array_keys ( $tpl ['乐其商品导入'] ) as $val ) {

				$in_val = Helper_Array::getCols ( $rowset, $val );
				$in_len = count ( $in_val );
				Helper_Array::removeEmpty ( $in_val );
				if (empty ( $in_val ) || $in_len > count ( $in_val )) {
					if(!in_array($val,$can_empty_cols) ) {
						$empty_col = true;
						$smarty->assign ( 'message', "文件中存在空的{$tpl['乐其商品导入'][$val]}，请确保有数据的行都是完整的" );
						break;
					}
					
				}
			}

			
			if ($empty_col)
				break;

	        // 检查商品数据中是否有(非空)重复的SKU条码        
			if (count ( array_filter( $in_sku_barcode ) ) > count ( array_unique ( array_filter( $in_sku_barcode ) ) )) {
				$smarty->assign ( 'message', '文件中存在重复的SKU条码' );
				break;
			}
		    
			$error_exist = false; 
			// 检查商品数据中是否有重复的 商品条码+样式 
			$in_goods_style_id = array ();
			foreach ( $rowset as $row ) {
				if (isset ( $row ['goods_barcode'] )) {
					$in_goods_style_id [] = $row ['goods_barcode'] . '_' . $row ['style_id'];
				}
				// 如果该行存在sku则需要有style_id，如果有style_id
			    if(isset($row['sku_barcode']) &&  !empty($row['sku_barcode']) ){
			    	if(empty($row['style_id'])){
			    		$error_exist = true; 
			    	}
			    }
			    // 如果该行存在style_id则需要有sku
			    if(isset($row['style_id']) &&  !empty($row['style_id']) ){
			    	if(empty($row['sku_barcode'])){
			    		$error_exist = true; 
			    	}
			    }
			}
			if (count ( $in_goods_style_id ) > count ( array_unique ( $in_goods_style_id ) )) {
				$smarty->assign ( 'message', '文件中存在重复的商品样式' );
				break;
			}

			if($error_exist){
				$error_exist = false; 
				$smarty->assign ( 'message', 'sku条码存在时，必须指定相应的样式，或，样式存在时，sku条码必须指定' );
				break; 
			}
			
			// 检查商品数据中是否有重复的 barcode+名称+分类 
			$in_goods_barcode_cat_name = array ();
			$error_exist = false; 
			foreach ( $rowset as $row ) {
				if (isset ( $row ['goods_barcode'] )) {
					$in_goods_barcode_cat_name [] = $row ['goods_barcode'] . '_' . $row ['goods_name'] . '_' . $row ['cat_name'];
				}
				
				// 判断保质期合法
				if(!is_numeric(trim($row['goods_warranty']))) {
					$smarty->assign ( 'message', '文件中存在非数字的保质期，请完整检查' );
					$error_exist = true; 
					break;
				}

				// 判断箱规 
				if(!is_numeric(trim($row['spec']))  || intval(trim($row['spec'])) < 0 ) {
					$smarty->assign ( 'message', '箱规为必填项 且为正整数' );
					$error_exist = true; 
					break;
				}
			}
			if( $error_exist ) break; 
			
			if (count ( array_unique ( $in_goods_barcode_cat_name ) ) > count ( array_unique ( $in_goods_barcode ) )) {
				$smarty->assign ( 'message', '文件中存在相同的商品条码拥有不同的名称或分类' );
				break;
			}
			
			//是否入库判断
			if (count ( array_diff ( array_unique ( $in_is_on_sale ), array_keys ( $is_on_sale ) ) ) > 0) {
				$smarty->assign ( 'message', '是否入库只能选择是或否，请完整检查' );
				break;
			}
			
			//是否维护保质期
			if (count ( array_diff ( array_unique ( $in_is_maintain_warranty ), array_keys ( $is_maintain_warranty ) ) ) > 0) {
				$smarty->assign ( 'message', '是否维护保质期只能选择是或否，请完整检查' );
				break;
			}
			
			$sql = "
                    SELECT
                        cat_id,cat_name
                    FROM
                        {$ecs->table('category')} o                        
                    WHERE
                        cat_name " . db_create_in ( array_unique ( $in_cat_name ) ) . "
                      and party_id = " . "'" . $_SESSION ['party_id'] . "'";
			
			$cat_ids = Helper_Array::toHashmap ( ( array ) $db->getAll ( $sql ), 'cat_name' );
			if (empty ( $cat_ids ) || (count ( array_keys ( $cat_ids ) ) != count ( array_unique ( $in_cat_name ) ))) {
				$smarty->assign ( 'message', '查询不到的类别：' . implode ( '， ', array_diff ( array_unique ( $in_cat_name ), array_keys ( $cat_ids ) ) ) );
				break;
			}
			
			// 判断样式是否存在 
			Helper_Array::removeEmpty ( $in_style_id ); 
			$sql = "
                    SELECT
                        style_id,color
                    FROM
                        {$ecs->table('style')} o                        
                    WHERE
                        style_id " . db_create_in ( array_unique ( $in_style_id ) );
			
			$styles = Helper_Array::toHashmap ( ( array ) $db->getAll ( $sql ), 'style_id' );
			if (empty ( $cat_ids ) || (count ( array_keys ( $styles ) ) != count ( array_unique ( $in_style_id ) ))) {

				$smarty->assign ( 'message', '查询不到的样式：' . implode ( '， ', array_diff ( array_unique ( $in_style_id ), array_keys ( $styles ) ) ) );
				break;
			}
			$sql = "
                    SELECT
                        goods_id,barcode,'N' update_flag,count(goods_id) num
                    FROM
                        {$ecs->table('goods')} g                        
                    WHERE
                        barcode " . db_create_in ( array_unique ( $in_goods_barcode ) ) . "
                      and goods_party_id = " . "'" . $_SESSION ['party_id'] . "'" . " group by barcode";
                      //var_dump($sql);
			
			$goods = Helper_Array::toHashmap ( ( array ) $db->getAll ( $sql ), 'barcode' );
			
			$sql = "
                    SELECT
                        gs.goods_style_id,gs.goods_id,gs.style_id,gs.barcode,'N' update_flag,count(gs.goods_id) num
                    FROM
                        {$ecs->table('goods_style')} gs,
                        {$ecs->table('goods')} g                        
                    WHERE gs.goods_id = g.goods_id
                      and gs.barcode " . db_create_in ( $in_sku_barcode ) . "
                      and g.goods_party_id = " . "'" . $_SESSION ['party_id'] . "'" . " group by gs.barcode";
			
			$goods_styles = Helper_Array::toHashmap ( ( array ) $db->getAll ( $sql ), 'barcode' );
			//var_dump($goods);die();
			
			/* 商品导入  */
			QLog::log ( "商品导入：数据检查通过，开始导入" );
			$goods_import_report = array ();
			$goods_style_import_report = array ();
			$i = 0;
			foreach ( $rowset as $key => $row ) {
				$goods_update_flag = false; 
				if (array_key_exists ( $row ['goods_barcode'], $goods )) {
					if ($goods [$row ['goods_barcode']] ['num'] > 1) {
						$goods_import_report ['failed'] [$i] = $row;
						$goods_import_report ['failed'] [$i] ['errmsg'] = "该商品编码{$row['goods_barcode']}对应的有多个";
					} else {
						if ($goods [$row ['goods_barcode']] ['update_flag'] == 'N') { //商品存在即更新且第一次读取到则更新
							$goods_import_report ['update'] [$i] = $row;
							$box_sql = "";
						  
						  	if(isset($row['added_fee']) && is_numeric($row['added_fee'])){
								$box_sql .= " , added_fee = '{$row['added_fee']}' "; 
							}
							if(isset($row['box_length']) && is_numeric($row['box_length'])){
								$box_sql .= " , box_length = '{$row['box_length']}' "; 
							}
							if(isset($row['box_width']) && is_numeric($row['box_width'])){
								$box_sql .= " , box_width = '{$row['box_width']}' "; 
							}
							if(isset($row['box_height']) && is_numeric($row['box_height'])){
								$box_sql .= " , box_height = '{$row['box_height']}' "; 
							}
							$goods_update_flag = true; 
							$sql = "update " . $GLOBALS ['ecs']->table ( 'goods' ) . " set 
	                                    goods_name = '{$row['goods_name']}',
	                                    cat_id = '{$cat_ids[$row['cat_name']]['cat_id']}',	    
	                                    barcode = '{$row['goods_barcode']}',
	                                    last_update = '" . time () . "',
	                                    is_on_sale = '{$is_on_sale[$row['is_on_sale']]}',
	                                    is_maintain_warranty = '{$is_maintain_warranty[$row['is_maintain_warranty']]}',
	                                    goods_warranty = '{$row['goods_warranty']}', 
	                                    goods_weight = '{$row['goods_weight']}',
	                                    warn_number = '{$row['warn_number']}',
	                                    goods_volume = '{$row['goods_volume']}',
	                                    last_update_stamp = now(),
	                                    spec = '{$row['spec']}', product_importance = '{$row['product_importance']}'
	                                    {$box_sql}
	                                  where goods_id = '{$goods[$row['goods_barcode']]['goods_id']}'
	                                    and goods_party_id = '{$_SESSION['party_id']}' ";
							
 							QLog::log ( "商品导入[更新]：{$sql} " );
							$result = $GLOBALS ['db']->query ( $sql );
							
							$riskysql = "INSERT INTO risky_actions (admin_user, type, timestamp, php_location, form_name, `sql`, comment) " .
            							"VALUES ('{$_SESSION['admin_name']}', 'update', NOW(), 'goods_style_import.php', 'form', '".mysql_real_escape_string($sql)."', '商品导入[更新]')";
							
							$GLOBALS ['db']->query($riskysql);	
							
 							$goods [$row ['goods_barcode']] ['update_flag'] = 'Y';
						} else { //商品存在且已更新 不做处理
						}
					}
				} 

				else { //商品不存在则插入商品记录
					$goods_import_report ['insert'] [$i] = $row;
					$row['goods_name'] = mysql_escape_string($row['goods_name']);
					$box_sql_set = "";
					$box_sql_value = ""; 
					
					if(isset($row['added_fee']) && is_numeric($row['added_fee'])){
						$box_sql_set .=" , added_fee "; 
						$box_sql_value .=" , '{$row['added_fee']}' "; 
					}

					if(isset($row['box_length']) && is_numeric($row['box_length'])){
						$box_sql_set .=" , box_length "; 
						$box_sql_value .=" , '{$row['box_length']}' "; 
					}
					if(isset($row['box_width']) && is_numeric($row['box_width'])){
						$box_sql_set .=" , box_width "; 
						$box_sql_value .=" , '{$row['box_width']}' "; 		 
					}
					if(isset($row['box_height']) && is_numeric($row['box_height'])){
						$box_sql_set .=" , box_height "; 
						$box_sql_value .=" , '{$row['box_height']}' "; 
					}

					$sql = "insert into " . $GLOBALS ['ecs']->table ( 'goods' ) . " 
	                            (goods_party_id , goods_name , cat_id , top_cat_id , barcode ,
	                             add_time , is_on_sale , warn_number , goods_weight, goods_volume, last_update_stamp, is_maintain_warranty, goods_warranty
	                             ,spec {$box_sql_set}, product_importance) 
	                          values('{$_SESSION['party_id']}' , '{$row['goods_name']}' , '{$cat_ids[$row['cat_name']]['cat_id']}', 
	                                   '$top_cat_id','{$row['goods_barcode']}' , 
	                                 '" . time () . "' , '{$is_on_sale[$row['is_on_sale']]}' , '{$row['warn_number']}' , 
	                                 '{$row['goods_weight']}', '{$row['goods_volume']}', now(), 
	                                 '{$is_maintain_warranty[$row['is_maintain_warranty']]}' , '{$row['goods_warranty']}',
	                                 '{$row['spec']}' {$box_sql_value}, '{$row['product_importance']}')";
					
 					$result = $GLOBALS ['db']->query ( $sql );
 					
 					$sql_goods_id = "select goods_id from ecshop.ecs_goods where barcode = '{$row['goods_barcode']}' and cat_id = '{$cat_ids[$row['cat_name']]['cat_id']}'";
					$goods_id = $db -> getOne($sql_goods_id);
 					
					$riskysql = "INSERT INTO risky_actions (admin_user, type, timestamp, php_location, form_name, `sql`, comment) " .
            							"VALUES ('{$_SESSION['admin_name']}', 'add', NOW(), 'goods_style_import.php', 'form', '".mysql_real_escape_string($sql)."', '商品导入[新增]')";
					$GLOBALS ['db']->query($riskysql);
 					
					//人头马 微信仓类目
					if($cat_ids[$row['cat_name']]['cat_id'] == '9678') {
						$sql_weixin_rtm_product = "insert ecshop.weixin_rtm_product (goods_name,barcode,party_id,outer_id,is_sync,created_stamp,last_updated_stamp) 
													values('{$row['goods_name']}','{$row['goods_barcode']}','{$_SESSION['party_id']}','{$goods_id}','1',now(),now())";
						$db -> query($sql_weixin_rtm_product);			
					}
 					
 					
 					QLog::log ( "商品导入[新增]：{$sql} " );
					
					$sql = "
                    SELECT
                        goods_id,barcode,'Y' update_flag,count(goods_id) num
                    FROM
                        {$ecs->table('goods')} g                        
                    WHERE
                        barcode = '{$row['goods_barcode']}' 
                      and goods_party_id = " . "'" . $_SESSION ['party_id'] . "'" . " group by barcode";
 					QLog::log ( "商品导入[新增结果]：{$sql} " );
 					$goods = Helper_Array::toHashmap ( array_merge_recursive ( $goods, Helper_Array::toHashmap ( ( array ) $db->getAll ( $sql ), 'barcode' ) ), 'barcode' );
				}


				//当存在SKU时（非标品），处理SKU； 若不存在SKU（标品），ecs_goods_style中不插入数据
				if(!empty($row['sku_barcode']) && !empty($row['style_id'])){//当上传的excel文档中sku_barcode和style_id都有值时
					if (array_key_exists ( $row ['sku_barcode'], $goods_styles )) {
						if ($goods_styles [$row ['sku_barcode']] ['num'] > 1) {
							$goods_style_import_report ['failed'] [$i] = $row;
							$goods_style_import_report ['failed'] [$i] ['errmsg'] = "该SKU编码{$row['sku_barcode']}对应的有多个";
						} elseif($goods_styles [$row ['sku_barcode']] ['goods_id'] != $goods[$row['goods_barcode']]['goods_id'] ){
							// SKU条码对应的goods_id必须和系统中已存在的一致。
							$goods_style_import_report ['failed'] [$i] = $row;
							$goods_style_import_report ['failed'] [$i] ['errmsg'] = "该SKU编码{$row['sku_barcode']}对应的商品与系统中已存在的不一致";
						} else { //SKU修改
							$goods_style_import_report ['update'] [$i] = $row;
					
							$sql = "
							update
							{$ecs->table('goods_style')} gs,
							{$ecs->table('goods')} g
							set gs.style_price = '{$row['style_price']}',
							gs.goods_id = '{$goods[$row['goods_barcode']]['goods_id']}',
							gs.style_id = '{$row['style_id']}',
							gs.last_update_stamp = now()
							WHERE gs.goods_id = g.goods_id
							and gs.barcode = '{$row['sku_barcode']}'
							and g.goods_party_id = " . "'" . $_SESSION ['party_id'] . "'";
					
							$goods_style_import_report ['update'] [$i] = $row;
							$result = $GLOBALS ['db']->query ( $sql );
							QLog::log ( "SKU导入[更新]：{$sql} " );
						}
					
					} else { //sku增加
						$goods_style_import_report ['insert'] [$i] = $row;
						$sql = "
							insert into {$ecs->table('goods_style')}
							(goods_id,style_id,style_price,barcode,last_update_stamp)
							values({$goods[$row['goods_barcode']]['goods_id']},'{$row['style_id']}',
							'{$row['style_price']}','{$row['sku_barcode']}', now())";	
						QLog::log ( "SKU导入[新增]：{$sql} " );
						$result = $GLOBALS ['db']->query ( $sql );
					}
				}
				// else if((empty($row['sku_barcode']) && !empty($row['style_id']))|| (!empty($row['sku_barcode']) && empty($row['style_id'])) ){
 				//     if($goods_update_flag == false){
					// 	$goods_style_import_report	['insert'] [$i] = $row;
					// 	$sql = "
					// 	insert into {$ecs->table('goods_style')}
					// 	(goods_id,style_id,style_price,last_update_stamp)
					// 	values({$goods[$row['goods_barcode']]['goods_id']},'{$row['style_id']}',
					// 		'{$row['style_price']}', now())";
					// 	$result = $GLOBALS ['db']->query ( $sql );
					// } 
					
			    // }
			    $i ++;
			} 
			// 删除上传的文件
			$file->unlink ();
			$smarty->assign ( 'goods_import_report', $goods_import_report );
			$smarty->assign ( 'goods_style_import_report', $goods_style_import_report );
			$smarty->assign ( 'message', "导入完毕，查看导入报告" );
			break;
		case 'download_leqee': 
			{
				$sql = "
          select g.goods_name,g.barcode,c.cat_name,g.is_on_sale,g.warn_number,g.goods_id,
                 if(gs.style_id is null,g.goods_id,concat(g.goods_id,'_',gs.style_id)) as erp_code,
                 if(gs.style_id is null,ifnull(g.goods_code,''),ifnull(gs.goods_code,'')) as goods_code, 
                 s.color,gs.style_price,gs.barcode as sku_barcode, g.is_maintain_warranty, g.goods_warranty
            from ecshop.ecs_goods g 
            left join ecshop.ecs_goods_style gs on g.goods_id = gs.goods_id and gs.is_delete=0
            left join ecshop.ecs_category c on g.cat_id = c.cat_id
            left join ecshop.ecs_style s on gs.style_id = s.style_id
            where g.goods_party_id = '{$_SESSION['party_id']}'
        ";
				$goods_list = $db->getAll ( $sql );
				header ( "Content-type:application/vnd.ms-excel" );
				header ( "Content-Disposition:filename=" . iconv ( "UTF-8", "GB18030", "乐其商品" ) . ".csv" );
				//$out = $smarty->fetch('oukooext/corr_order_csv.htm');
				$out = "商品名称,商品条码,商品分类,是否上架,是否维护保质期,保质期,警告库存,ERP商品编码,ERP编码,样式,价格,SKU条码,商品货号\n";
				foreach ( $goods_list as $key => $goods ) {
					$out .= $goods ['goods_name'] . "," . $goods ['barcode'] . "," . $goods ['cat_name'] . "," . $goods ['is_on_sale'] . ",";
					$out .= $goods ['is_maintain_warranty'] . "," . $goods['goods_warranty'] . ",";
					$out .= $goods ['warn_number'] . ",".$goods['goods_id'].",";
					$out .= $goods ['erp_code'] . "," . $goods ['color'] . "," . $goods ['style_price'] . "," . $goods ['sku_barcode'].",".$goods['goods_code']. "\n";
				}
				echo iconv ( "UTF-8", "GB18030", $out );
				exit ();
			}
			
		case 'download_bymboree' :
			{
				$sql = "
                  select fchrItemName,fchrItemTypeName,fchrItemCode,flotQuotePrice,
                         fchrBarCodeNo,fchrFree2 
                  from ecshop.brand_gymboree_product
                  where last_update_stamp = '{$item_select}'
                ";
				
				$goods_list = $db->getAll ( $sql );
				header ( "Content-type:application/vnd.ms-excel" );
				header ( "Content-Disposition:filename=" . iconv ( "UTF-8", "GB18030", "金宝贝商品" ) . ".csv" );
				//$out = $smarty->fetch('oukooext/corr_order_csv.htm');
				$out = "商品名称,分类名称,款号,单价,SKU条码,尺码\n";
				foreach ( $goods_list as $key => $goods ) {
					$out .= $goods ['fchrItemName'] . "," . $goods ['fchrItemTypeName'] . "," . $goods ['fchrItemCode'] . ",";
					$out .= $goods [flotQuotePrice]. "," . $goods ['fchrBarCodeNo']. "," . $goods ['fchrFree2']."\n" ;
				}
				echo iconv ( "UTF-8", "GB18030", $out );
				exit ();
			}	
			
		case 'download_style' :
			{
//				Qlog::log('样式表下载');
				$sql = "select * from ecshop.ecs_style";
				$style_list = $db->getAll($sql);
				header ( "Content-type:application/vnd.ms-excel" );
				header ( "Content-Disposition:filename=" . iconv ( "UTF-8", "GB18030", "样式表" ) . ".csv" );
				$out = "样式ID,颜色(尺码),样式,类型\n";
				foreach ( $style_list as $key => $style ) {
					$out .= $style ['style_id'] . "," . $style ['color'] . "," . $style ['value'] . "," . $style ['type']. "\n" ;
				}
				echo iconv ( "UTF-8", "GB18030", $out );
				exit ();
			}
		case 'download_model_product' :
			{
				
				header ( "Content-type:application/vnd.ms-excel" );
				header ( "Content-Disposition:filename=" . iconv ( "UTF-8", "GB18030", "乐其商品导入" ) . ".csv" );
				$out = "商品名称,商品条码,商品分类,商品重量(g),商品体积(cm^3),是否上架,警告库存,商品价格(元),SKU条码,样式ID,是否维护保质期,保质期(月),商品税率(默认1.17),箱规,箱子长度(cm),箱子宽度(cm),箱子高度(cm),产品重要性(A、B、C)\n";
				echo iconv ( "UTF-8", "GB18030", $out );
				exit ();
			}	
	} //end of switch
	
	QLog::log ( "商品导入：结束。查看 错误信息：" . $smarty->get_template_vars ( 'message' ) );
}

/**
 * 显示
 */
$smarty->assign ( 'party_id', $_SESSION ['party_id'] );
$smarty->assign ( 'item_list', $item_list );
$smarty->display ( 'goods_style_import.htm' );

