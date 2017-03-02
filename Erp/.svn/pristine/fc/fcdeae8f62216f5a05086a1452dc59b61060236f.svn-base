<?php
    define('IN_ECS', true);
	require('includes/init.php');
	require('function.php');
	include_once('../RomeoApi/lib_currency.php');
	include_once(ROOT_PATH . 'includes/cls_json.php');
	require_once (ROOT_PATH . 'RomeoApi/lib_payment.php');
	require_once (ROOT_PATH . 'includes/lib_order.php');
	require_once (ROOT_PATH . 'includes/helper/array.php');
	require_once (ROOT_PATH . 'includes/helper/uploader.php');
	require_once (ROOT_PATH . 'includes/debug/lib_log.php');
	
	$json = new JSON();
	$msg = "";
	$fileElementName = 'fileToUpload';
	
	$party_id = $_REQUEST['party_id'];
		
	$uploader = new Helper_Uploader();
	
	$max_size = $uploader -> allowedUploadSize(); // 允许上传的最大值
	
	$final =array(message =>"", content =>array());
	
	$config = array('不计入满赠的商品' =>
	            array('shop_code'=>'商家编码'
	          ));
	          
	if(!$uploader->existsFile ('fileToUpload')){
		$final['message'] = '没有选择上传文件，或者文件上传失败';
	}
	
	//取得要上传的文件句柄
	if($final['message'] == ""){
		$file = $uploader->file ('fileToUpload');
	}
	
	//检查上传文件
	if($final['message'] == "" && !$file->isValid ('xls,xlsx', $max_size)){
		$final['message'] = "非法文件！请检查文件类型(xls,xlsx),并且系统限制的上传大小为'. $max_size /1024/1024 . 'MB'";
	}
	
	//读取excel
	if($final['message'] == ""){
	    $result = excel_read ( $file->filepath (), $config, $file->extname (), $failed );
		if (! empty ( $failed )) {
			$final['message'] = reset ( $failed );
		}
	}
	
	if($final['message'] == ""){
		$rowset = $result ['不计入满赠的商品'];
		if(empty($rowset)){
			$final['message'] = "excel文件中没有数据，请检查文件";
		}
	}
	
	
	if($final['message'] == ""){
		foreach ($rowset as $row){
			if($row['shop_code'] != ''){
				// 普通商品
				if(preg_match('/^[0-9]+_[0-9]+$/',$row['shop_code']) || preg_match('/^[0-9]+$/',$row['shop_code'])) {
					$exclude_goods_item = array();
					if(preg_match('/^[0-9]+_[0-9]+$/',$row['shop_code']) && !preg_match('/^[0-9]+_0$/',$row['shop_code'])){
						$goods_style_id = explode("_",$row['shop_code']);
						$exclude_goods_item['goods_id'] = $goods_style_id[0];
						$exclude_goods_item['style_id'] = $goods_style_id[1];
						//$sql = "select count(*) from ecshop.ecs_goods_style where goods_id = '{$exclude_goods_item['goods_id']}' and style_id = '{$exclude_goods_item['style_id']}'";
						$sql = "select g.goods_id goods_id, gs.style_id style_id, g.cat_id cat_id, concat_ws('',g.goods_id,'_',gs.style_id) as outer_id, " .
								"concat_ws(' ', g.goods_name, if(gs.goods_color = '', s.color, gs.goods_color), " .
								"'(', g.goods_id,'_',gs.style_id,')') as goods_name, 'item' as type " .
								"from ecshop.ecs_goods as g " .
								"left join ecshop.ecs_goods_style as gs on gs.goods_id = g.goods_id and gs.is_delete=0 and gs.is_delete=0 " .
								"left join ecshop.ecs_style as s on gs.style_id = s.style_id " .
								"where (g.is_on_sale = 1 and g.is_delete = 0) and g.goods_id = '{$exclude_goods_item['goods_id']}' " .
								"and gs.style_id = '{$exclude_goods_item['style_id']}' and g.goods_party_id = '{$_SESSION['party_id']}'";
					}else if(preg_match('/^[0-9]+$/',$row['shop_code']) || preg_match('/^[0-9]+_0$/',$row['shop_code'])){
						$exclude_goods_item['goods_id'] = str_replace('_0','',$row['shop_code']);
						$exclude_goods_item['style_id'] = 0;
						//$sql = "select count(*) from ecshop.ecs_goods where goods_id = '{$exclude_goods_item['goods_id']}'";
						$sql = "select g.goods_id good_id, 0 as style_id, g.cat_id cat_id, concat_ws('',g.goods_id,'_0') as outer_id," .
								"concat_ws(' ',g.goods_name,'(',g.goods_id,'_ 0',')') as goods_name, 'item' as type " .
								"from ecshop.ecs_goods as g " .
								"where (g.is_on_sale = 1 and g.is_delete = 0) and g.goods_id = '{$exclude_goods_item['goods_id']}' " .
								"and g.goods_party_id = '{$_SESSION['party_id']}'";
					}
					$result = $GLOBALS['db']->getAll($sql);
					if(!$result) {
						$final['message'] = "系统异常，下面商家编码找不到对应商品，请检查后重新导入：".$row['shop_code'];
					} else {
						array_push($final['content'],$result);
						//QLog::log($result);
					}
				} 
				// 套餐
				else if(preg_match('/^TC-[0-9]+$/',$row['shop_code'])) {
					$sql = "select
								0 as goods_id,
								0 as style_id,
								0 as cat_id,
								dg.code as outer_id,
								concat_ws(' ',dg.name,'(',dg.code,')') as goods_name,
								'taocan' as type
							from
								ecshop.distribution_group_goods dg
							where 1  AND dg.code = '{$row['shop_code']}' AND dg.party_id='{$_SESSION['party_id']}' limit 100";
					$result = $GLOBALS['db']->getAll($sql);
					if(!$result) {
						$final['message'] = "系统异常，下面商家编码找不到对应商品，请检查后重新导入：".$row['shop_code'];	
					} 
					else 
					{
						array_push($final['content'],(array)$result);
					}
				}
			}
		}
	 
	}
	
	//QLog::log($final['message']);
	//QLog::log($final['content']);
	print 	$json->encode($final);

?>