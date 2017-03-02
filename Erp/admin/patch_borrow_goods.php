<?php
/*
 * Created on 2011-8-27
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
 define('IN_ECS', true);

 require('includes/init.php');
 require_once('function.php');
 require_once(ROOT_PATH . 'includes/cls_json.php');
 require_once("RomeoApi/lib_inventory.php");
 require_once(ROOT_PATH . 'RomeoApi/lib_supplier_return.php');
 require_once (ROOT_PATH . 'includes/lib_order.php');
 require_once (ROOT_PATH . 'admin/includes/lib_order.php');
 require_once (ROOT_PATH . 'includes/helper/uploader.php');
 require_once (ROOT_PATH . 'includes/debug/lib_log.php');
 require_once (ROOT_PATH . 'includes/helper/array.php');
 require_once (ROOT_PATH . 'RomeoApi/lib_facility.php');
 require_once(ROOT_PATH . 'admin/includes/lib_filelock.php');
 
admin_priv('patch_order_goods');
$now = date("H:i:s", time());
if (!((strtotime($now) >= strtotime("8:00:00") && strtotime($now) <= strtotime("12:00:00")) || (strtotime($now) >= strtotime("19:00:00") && strtotime($now) <= strtotime("22:00:00")))) {
   // sys_msg('批量借机操作，目前只可以在早上8点到12点或者晚上7点到10点之间进行操作，谢谢配合。');
}
if ($_SESSION['party_id'] == 16 || is_jjshouse($_SESSION['party_id'])) {
	sys_msg('电教业务和海外业务，请勿在此页面操作。');
}
 
 $request = $_REQUEST['request'];
 if (!empty($request) && $request == 'ajax'){
 	$json = new JSON;
 	$act = $_REQUEST['act'];
 	switch ($act) {
 		case 'search_goods' :
  	        $limit = 40 ;   // 每次最大显示40行
 		    require_once('function.php');
            print $json->encode(get_goods_list_like($_POST['q'], $limit));
 		    break ;
 		case 'search_providers':
 		    $limit = 40 ;
 		    require_once('function.php');
            print $json->encode(get_providers_list_like($_POST['q'], $limit));
            
            break ;
        case 'search_goods_storage' :
         	// 检查当前商品库存
 		    $order_goods_id = $_REQUEST['goods_id'] ;
            $order_style_id = $_REQUEST['style_id'] ;
            $original_provider_id = $_REQUEST['original_provider_id'];
            $facility_id = $_REQUEST['facility_id'] ;
            
            $status_id = $_REQUEST['status_id'] ;
            $purchase_unit_price = $_REQUEST['purchase_paid_amount'] ;
            // 检查商品是否串号控制
            if (!function_exists('get_goods_item_type')){
     	        require_once("admin/includes/lib_goods.php");	
             }
            $goods_item_type = get_goods_item_type($order_goods_id);
            // SQL
            $cond = '';
            if (!empty($original_provider_id)) {
     	        $cond = 'and e.provider_id = ' . $original_provider_id ;
            }
            if (!empty($status_id)) {
            	$cond = $cond . ' and e.is_new = ' . $status_id ;
            }
            if (!empty($purchase_unit_price)) {
            	$cond = $cond . ' and e.purchase_paid_amount = ' . $purchase_unit_price ;
            }
            
 		    $sql = "select og.goods_name, og.goods_id, og.style_id, e.purchase_paid_amount, e.is_new, e.order_type, e.provider_id, p.provider_name, f.facility_name, count(*) as storage_amount
                   from ecshop.ecs_oukoo_erp e 
                        left join ecshop.ecs_oukoo_erp as oute on e.in_sn = oute.out_sn
                        left join ecshop.ecs_order_goods as og on e.order_goods_id = og.rec_id 
                        left join ecshop.ecs_provider as p on e.provider_id = p.provider_id
                        left join romeo.facility f on e.facility_id = f.facility_id
                   where e.in_sn != '' and e.out_sn = '' and e.facility_id = '%s' %s 
                     and og.goods_id = %d and og.style_id = %d 
                     and oute.out_sn is null 
                  group by og.goods_id, og.style_id, e.purchase_paid_amount, e.is_new, e.order_type, e.provider_id;" ;
             
            $ret_item = $db->getAll(sprintf($sql,  $facility_id, $cond, intval($order_goods_id), intval($order_style_id))) ;
            if (!empty($ret_item)) {
                $ret_item[] = $goods_item_type ;
            }
            print $json->encode($ret_item) ;
            
            break ;
       case 'upload':

       	    $import = $_REQUEST['import'];
       		$borrow_goods_detail = array();
			$fileElementName = 'fileToUpload';
			$party_id = $_REQUEST['party_id'];
			$uploader = new Helper_Uploader ();
			$final = array(message => "",content => array());
			if($import == 'Y'){
				//如果锁住的话 一个小时之内不能操作
				$file_name = 'patch_borrow_goods-import';
				$file_name .= date('Y').date('m').date('d').date('H');
				if (is_file_lock($file_name)) {
	                $final['message'] = "导入操作正在执行，请稍后执行";
	                print $json->encode($final);
					break;
	            }else{
	            	create_file_lock($file_name);
	            }
			}
			$max_size = $uploader->allowedUploadSize (); // 允许上传的最大值
		    $config = array('批量借机'  =>
					  array(
						   'barcode' => '商品条码',
						   'amount' => '数量',
						   'name' => '借机人姓名',
						   'return_time' => '还机时间',
						   'remark' => '备注',
					  	   'facility_name' => '仓库',
					  	   'status' => '库存类型'
					)
		    );
		    
 			if (!$uploader->existsFile ( 'fileToUpload' )) {
				$final['message'] =  '没有选择上传文件，或者文件上传失败';
				print $json->encode($final);
				break;
			}
			
 			//取得要上传的文件句柄
		    $file = $uploader->file ( 'fileToUpload' );
			
			// 检查上传文件
			if (!$file->isValid ( 'xls, xlsx', $max_size )) {
				$final['message'] = "非法的文件! 请检查文件类型(xls, xlsx), 并且系统限制的上传大小为". $max_size/1024/1024 . "MB";
				print $json->encode($final);
				break;
			}
			
 			// 读取excel
			$data = excel_read ( $file->filepath (), $config, $file->extname (), $failed );
			if (! empty ( $failed )) {
				$final['message'] = reset ( $failed );
				print $json->encode($final);
				break;
			}
			
			$rowset = $data['批量借机'];
			if (empty ( $rowset )) {
				$final['message'] = "excel文件中没有数据,请检查文件";
				print $json->encode($final);
				break;
			}
			
			//库存类型匹配
			$ret_stauts_map = array(
			    '全新' => 'NEW',
			    '二手' => 'SECOND_HAND',
			    '次品' => 'DISCARD'
			);
			
			if($import == 'Y'){
				// 删除上传的文件
            	$file->unlink();
			}
			
			
			//检测是否为空
			$check_value_arr = array(
				'barcode' => '商品条码',
				'amount' => '数量',
				'name' => '借机人姓名',
				'return_time' => '还机时间',
				'remark' => '备注',
				'facility_name' => '仓库',
				'status' => '库存类型'
			);
			
 			foreach ( array_keys ( $check_value_arr ) as $val ) {
				$in_val = Helper_Array::getCols ( $rowset, $val );
				$in_len = count ( $in_val );
				Helper_Array::removeEmpty ( $in_val );
				if (empty ( $in_val ) || $in_len > count ( $in_val )) {
					$empty_col = true;
					$final['message'] = "文件中存在空的{$check_value_arr[$val]}，请确保必须的列每一行都有数据";
					print $json->encode($final);
				    break 2;
				}
		    }
		    
		    $i = 1;
		    //有一个不行，就不能导入
		    $flag = true;
		    //库存总额判断
		    $add_storage = array();
		    foreach ($rowset as $key => $row){
		    	$request = array();
		    	//判定数量
		    	$i ++;
		    	$amount = $row['amount'];
		    	$amount = trim($amount);
		    	if(!is_int($amount/1) || $amount <= 0){
		    		$final['content'][$key]['success'] = 0;
		    		$final['content'][$key]['message'] = "第{$i}行数量填写失误";
		    		$flag = false;
		    		continue;
		    	}
		    	$request['amount'] = $amount;
		    	
		    	//判定时间
		    	$return_time = $row['return_time'];
		    	if(!is_date($return_time)){
		    		$final['content'][$key]['success'] = 0;
		    		$final['content'][$key]['message'] = "第{$i}行时间格式有误,格式为xxxx-xx-xx且excel中时间前面必须有个英文格式的引号";
		    		$flag = false;
		    		continue;
		    	}
		    	if(strtotime($return_time) < strtotime(date("Y-m-d"))){
		    		$final['content'][$key]['success'] = 0;
		    		$final['content'][$key]['message'] = "第{$i}行预计还机时间比当前时间要小，请检查";
		    		$flag = false;
		    		continue;
		    	}
		    	$request['return_time'] = $return_time;
		    	
		    	//判定仓库
		    	$facility = get_facility($row['facility_name']);
		    	if(!$facility){
		    		$final['content'][$key]['success'] = 0;
		    		$final['content'][$key]['message'] = "第{$i}行仓库名称填写有误，仓库名称必须与erp中仓库名称完全一致";
		    		$flag = false;
		    		continue;
		    	}
		    	$request['facility_id'] = $facility['facility_id'];
		    	$request['facility_name'] = $facility['facility_name'];
		    	
		        //判定库存类型
		    	$status = trim($row['status']);
		    	$status_id = $ret_stauts_map[$status];
		    	$status_arr = array('NEW','SECOND_HAND','DISCARD');
		    	if(!in_array($status_id,$status_arr)){
		    		$final['content'][$key]['success'] = 0;
		    		$final['content'][$key]['message'] = "第{$i}行库存类型填写有误，库存类型只有‘全新’，‘二手’，‘次品’";
		    		$flag = false;
		    		continue;
		    	}
		    	$request['status_id'] = $status_id;
		    	$request['status'] = $status;
		    	
		    	//判定商品
		    	$goods = get_goods_by_barcode ($row['barcode']);
		    	$party_id = $_SESSION['party_id'];
		    	if(!$goods){
		    		$final['content'][$key]['success'] = 0;
		    		$final['content'][$key]['message'] = "第{$i}行商品找不到，请检查商品条码是否填写有误";
		    		$flag = false;
		    		continue;
		    	}
		    	if($party_id != $goods['goods_party_id']){
		    		$final['content'][$key]['success'] = 0;
		    		$final['content'][$key]['message'] = "第{$i}行商品的组织与当前的组织不同，请查看";
		    		$flag = false;
		    		continue;
		    	}
		    	$request['goods_id'] = $goods['goods_id'];
		    	$request['style_id'] = $goods['style_id'];
		    	$request['goods_party_id'] = $goods['goods_party_id'];
		    	$request['goods_name'] = $goods['goods_name'];
		    	$storage_amount = search_goods_storage($request);
		    	$request['storage_amount'] = $storage_amount;
		    	if(!$storage_amount || $storage_amount < $request['storage_amount']){
		    		$final['content'][$key]['success'] = 0;
		    		$final['content'][$key]['message'] = "第{$i}行{$request['goods_name']}的库存不足({$request['storage_amount']})，请查看";
		    		$flag = false;
		    		continue;
		    	}
		    	//如果申请的库存大于现有库存，则报错
		    	if(empty($add_storage[$row['barcode'].$request['facility_id'].$request['status_id']])){
		    		//记录商品的库存，如果还没有相应的记录，则初始化为0
		    		$add_storage[$row['barcode'].$request['facility_id'].$request['status_id']] = 0;
		    	}
		    	$add_storage[$row['barcode'].$request['facility_id'].$request['status_id']] += $amount;
		    	if($storage_amount < $add_storage[$row['barcode'].$request['facility_id'].$request['status_id']]){
		    		$final['content'][$key]['success'] = 0;
		    		$final['content'][$key]['message'] = "前几行申请商品数量总和导致第{$i}行{$request['goods_name']}的库存不足，请查看";
		    		$flag = false;
		    		continue;
		    	}
		    	$request['barcode'] = $row['barcode'];
		    	$request['p_name'] = addslashes(trim($row['name']));
		    	$request['remark'] = "S内部人员借机 || " .addslashes(trim($row['remark']));
		    	$final['content'][$key]['success'] = 1;
		    	$final['content'][$key]['message'] = "可以导入";
		    	$final['content'][$key]['request'] = $request;
		    }
		    if($import == 'N'){
		    	print $json->encode($final);
		    }
		    else if($import == 'Y'){
		    	$import_final = array(message => "",content => array());
		    	if(!$flag){
		    		$import_final['message'] = "经检测上传文件中存在一些问题，无法导入。具体原因请点击检测";
		    		// 释放锁
            		release_file_lock($file_name);
					print $json->encode($import_final);
		    	}else{
		    		foreach ($final['content'] as $key => $req){
		    			$message = create_borrow_goods($req['request']);
		    			$import_final['content'][$key]['success'] = $message['success'];
		    			$import_final['content'][$key]['message'] = $message['message'];
		    		}
		    		// 释放锁
            		release_file_lock($file_name);
		    		print $json->encode($import_final);
		    	}
		    }
			break;
 	}
 		
 	exit ;
 }
 
 //借机
 $act = $_POST['act'];
 if ('create_item' == $act) {
 	$goods_style_id = trim($_REQUEST['goods_style_id']);
 	$goods_rate = trim($_REQUEST['goods_rate']);
 	$provider_id = trim($_REQUEST['ret_provider_id']);
 	$goods_num = (int)trim($_REQUEST['ret_amount']);
 	$goods_status_id = trim($_REQUEST['ret_status_id']);
 	$p_name = trim($_REQUEST['p_name']);
 	$remark = "S内部人员借机 || " . trim($_REQUEST['remark']);
 	$facility_id = trim($_REQUEST['facility_id']);
 	$goods_name =  trim($_REQUEST['goods_name']);
 	$facility_name = trim($_REQUEST['facility_name']);
 	$predict_return_time = trim($_REQUEST['date']);
 	list($goods_id, $style_id) = explode('_', trim($_REQUEST['goods_style_id']));

 	if(isset($goods_id) && $goods_id > 0) {
 		if (empty($style_id)) {
 			$style_id = 0;
 		}
 		//查询借机串号
 		$sql_sn = "
                select e.*, og.goods_name,o.order_id, o.order_sn, o.currency
                from ecshop.ecs_oukoo_erp e
                left join ecshop.ecs_oukoo_erp oute on e.in_sn = oute.out_sn
                left join ecshop.ecs_order_goods og on e.order_goods_id = og.rec_id
                left join ecshop.ecs_order_info o on o.order_id = og.order_id
                left join ecshop.ecs_erp_validity ev on e.erp_id = ev.erp_id
                where e.in_sn != '' and oute.out_sn is null and og.goods_id = '{$goods_id}' and og.style_id = '{$style_id}' 
	                and e.facility_id = '{$facility_id}' and e.provider_id = '{$provider_id}' and e.is_new = '{$goods_status_id}'
	                and o.party_id = '{$_SESSION['party_id']}' 
	            order by ev.validity,e.erp_id
	            limit {$goods_num}
 			";
 		$sn_list = $db->getAll($sql_sn);
 		if (empty($sn_list)) {
 			$message = $goods_name . "在". $facility_name ."仓暂无库存。";
 		} elseif (count($sn_list) == $goods_num) {
 			foreach ($sn_list as $item) {
 				//操作借机
 				$error_no = 0;
 				do {
 					$order_sn = get_order_sn() . "-gh"; //获取新订单号
 					$sql = "
			                INSERT INTO {$ecs->table('order_info')}
			                (order_sn, order_time, consignee, shipping_time, user_id, postscript, 
			                order_status, party_id, facility_id, currency, order_type_id) 
			                VALUES 
			                ('{$order_sn}', NOW(), '{$p_name}', UNIX_TIMESTAMP(NOW()), '0', 
			                 '{$remark}', 1, '{$_SESSION['party_id']}', '{$facility_id}', '{$item['currency']}', 'BORROW')
		                ";
 					$db->query($sql, 'SILENT');
 					$error_no = $db->errno();
 					if ($error_no > 0 && $error_no != 1062) {
 						die($db->errorMsg());
 					}

 				} while ($error_no == 1062); //如果是订单号重复则重新提交数据
 				$order_id = $db->insert_id();
 				
 				//向order_attribute中插入预计归还时间
 				$sql = "
 				       INSERT INTO order_attribute
 				       (order_id, attr_name, attr_value)
 				       VALUES
 				       ('{$order_id}','PREDICT_RETURN_TIME','{$predict_return_time}')";
 				$db->query($sql);

 				add_order_relation($order_id, $item['order_id'], '', $order_sn, $item['order_sn']);
 				$goods_name = addslashes($goods_name);
 				$sql_order_goods = "
		                INSERT INTO {$ecs->table('order_goods')} 
		                (order_id, goods_id, style_id, goods_name, goods_number, goods_price, added_fee) VALUES 
		                ('{$order_id}', '{$goods_id}', '{$style_id}', '{$goods_name}', 1, '{$order_amount}', '{$goods_rate}')
	                ";
 				//$order_amount todo
 				$db->query($sql_order_goods);
 				$order_goods_id = $db->insert_id();

 				$sql = "
		                 INSERT INTO {$ecs->table('oukoo_erp')}
		                 (order_goods_id, erp_goods_sn, out_sn, action_user, 
		                  order_type, purchase_paid_amount, is_new, provider_id, facility_id) 
		                 VALUES 
		                 ('{$order_goods_id}', '{$item['erp_goods_sn']}', '{$item['in_sn']}', '{$_SESSION['admin_name']}', 
		                 '{$item['order_type']}', '{$item['purchase_paid_amount']}', 
		                 '{$item['is_new']}', '{$item['provider_id']}', '{$facility_id}')
	                 ";
 				$db->query($sql);
 				add_erp_log($item['in_sn'], 'out');
 				if($item['is_new'] == 'NEW') {
 					$fromStatusId = 'INV_STTS_AVAILABLE';
 				} else if ($item['is_new'] == 'SECOND_HAND') {
 					$fromStatusId = 'INV_STTS_USED';
 				} else {
 					$fromStatusId = 'INV_STTS_DEFECTIVE';
 				}
 				$info = '该货已经借机给内部员工，等待归还的货物入仓库';
 					
 				createDeliverInventoryTransaction('ITT_BORROW',array('goods_id'=>$goods_id, 'style_id'=>$style_id),
 				1, $item['erp_goods_sn'], $item['order_type'], null, $order_id, $fromStatusId, '', $order_goods_id, $facility_id);
 			}
 			$message = "借机已经操作，请勿重复操作。";
 		} elseif (count($sn_list) < $goods_num) {
 			$message = "该仓库中库存不足，请重新操作。";
 		}
 	} else {
 		$message = '请先选择正确的商品。';
 	}
 	$smarty->assign('message', $message);
 }
 


 // 检索对应party下可用仓库
 if (!function_exists('get_available_facility')) {
     require_once("admin/includes/lib_main.php");	
 }
 
 $smarty->assign('facilitys', get_available_facility());

 // $smarty->assign('status_id', $status_id);
 $smarty->display('oukooext/patch_borrow_goods.htm');

 
 
 //判断是否为有效时间
 function is_date($time){
 	return preg_match("/^(\d{4})(-)(\d{2})(-)(\d{2})$/",$time);
 }
 
 /*
  * 根据条码得到商品
  * 先查ecs_goods_style中的若没有再查ecs_goods中的
  */
 function get_goods_by_barcode($barcode){
 	$barcode = trim($barcode);
 	if(empty($barcode)){
 		return false;
 	}
 	global $db;
 	$sql = "
 		select 
			g.goods_id, g.cat_id, gs.style_id, g.goods_party_id,
    		CONCAT_WS(' ', g.goods_name, IF( gs.goods_color = '', s.color, gs.goods_color) ) as goods_name
		from 
			ecshop.ecs_goods_style gs 
			inner join ecshop.ecs_goods g on g.goods_id = gs.goods_id
			inner join ecshop.ecs_style s on s.style_id = gs.style_id
		where 
		(g.is_on_sale = 1 and g.is_delete = 0) and gs.barcode = '{$barcode}' and gs.is_delete=0
	";
 	$goods = $db -> getRow($sql);
 	if(!$goods){
 		$sql = "
 			select 
				g.goods_id, g.cat_id, 0 style_id, g.goods_party_id, g.goods_name
			from 
				ecshop.ecs_goods g
				left join ecshop.ecs_goods_style gs on gs.goods_id = g.goods_id and gs.is_delete=0
			where 
				(g.is_on_sale = 1 and g.is_delete = 0) and g.barcode = '{$barcode}'
				and gs.goods_id is null
		";
 		$goods = $db -> getRow($sql);
 	}
 	return $goods;
 }
 
 //查询库存
 function search_goods_storage($request){
 	global $db;
 	// 检查当前商品库存
 	$order_goods_id = $request['goods_id'] ;
    $order_style_id = $request['style_id'] ;
    $facility_id = $request['facility_id'] ;
    $status_id = $request['status_id'] ;
    
 	$sql = "
 		select 
 			count(*) as storage_amount
		from 
			ecshop.ecs_oukoo_erp e 
			left join ecshop.ecs_oukoo_erp as oute on e.in_sn = oute.out_sn
			left join ecshop.ecs_order_goods as og on e.order_goods_id = og.rec_id 
			left join ecshop.ecs_provider as p on e.provider_id = p.provider_id
			left join romeo.facility f on e.facility_id = f.facility_id
		where 
			e.in_sn != '' and e.out_sn = '' and e.facility_id = '%s' and e.is_new = '%s' 
      		and og.goods_id = %d and og.style_id = %d 
      		and oute.out_sn is null 
		group by 
			og.goods_id, og.style_id, e.is_new, e.facility_id
		limit 1
	" ;
     
    $storage_amount = $db->getOne(sprintf($sql,  $facility_id, $status_id, intval($order_goods_id), intval($order_style_id))) ;
    return $storage_amount;
 }
 
 //借机存入
 function create_borrow_goods($request){
 	global $db;
 	global $ecs;
 	if(empty($request)){
 		$result['success'] = '0';
 		$result['message'] = "传入为空";
 		return $result;
 	}
 	$goods_num = $request['amount'];
 	$goods_status_id = $request['status_id'];
 	$p_name = $request['p_name'];
 	$remark = $request['remark'];
 	$facility_id = $request['facility_id'];
 	$goods_name =  $request['goods_name'];
 	$facility_name = $request['facility_name'];
 	$predict_return_time = $request['return_time'];
 	$goods_id = $request['goods_id'];
 	$style_id = $request['style_id'];

 	if(isset($goods_id) && $goods_id > 0) {
 		if (empty($style_id)) {
 			$style_id = 0;
 		}
 		//查询借机串号
 		$sql_sn = "
                select e.*, og.goods_name,o.order_id, o.order_sn, o.currency, og.added_fee
                from ecshop.ecs_oukoo_erp e
                left join ecshop.ecs_oukoo_erp oute on e.in_sn = oute.out_sn
                left join ecshop.ecs_order_goods og on e.order_goods_id = og.rec_id
                left join ecshop.ecs_order_info o on o.order_id = og.order_id
                left join ecshop.ecs_erp_validity ev on e.erp_id = ev.erp_id
                where e.in_sn != '' and oute.out_sn is null and og.goods_id = '{$goods_id}' and og.style_id = '{$style_id}' 
	                and e.facility_id = '{$facility_id}' 
	                and e.is_new = '{$goods_status_id}'
	                and o.party_id = '{$_SESSION['party_id']}' 
	            order by ev.validity,e.erp_id
	            limit {$goods_num}
 			";
 		$sn_list = $db->getAll($sql_sn);
 		if (empty($sn_list)) {
 			$message = $goods_name . "在". $facility_name ."仓暂无库存。";
 		} elseif (count($sn_list) == $goods_num) {
 			foreach ($sn_list as $item) {
 				//操作借机
 				$error_no = 0;
 				do {
 					$order_sn = get_order_sn() . "-gh"; //获取新订单号
 					$sql = "
			                INSERT INTO {$ecs->table('order_info')}
			                (order_sn, order_time, consignee, shipping_time, user_id, postscript, 
			                order_status, party_id, facility_id, currency, order_type_id) 
			                VALUES 
			                ('{$order_sn}', NOW(), '{$p_name}', UNIX_TIMESTAMP(NOW()), '0', 
			                 '{$remark}', 1, '{$_SESSION['party_id']}', '{$facility_id}', '{$item['currency']}', 'BORROW')
		                ";
 					$db->query($sql, 'SILENT');
 					$error_no = $db->errno();
 					if ($error_no > 0 && $error_no != 1062) {
 						die($db->errorMsg());
 					}

 				} while ($error_no == 1062); //如果是订单号重复则重新提交数据
 				$order_id = $db->insert_id();
 				
 				//向order_attribute中插入预计归还时间
 				$sql = "
 				       INSERT INTO order_attribute
 				       (order_id, attr_name, attr_value)
 				       VALUES
 				       ('{$order_id}','PREDICT_RETURN_TIME','{$predict_return_time}')";
 				$db->query($sql);

 				add_order_relation($order_id, $item['order_id'], '', $order_sn, $item['order_sn']);
 				$goods_name = addslashes($goods_name);
 				$sql_order_goods = "
		                INSERT INTO {$ecs->table('order_goods')} 
		                (order_id, goods_id, style_id, goods_name, goods_number, goods_price, added_fee) VALUES 
		                ('{$order_id}', '{$goods_id}', '{$style_id}', '{$goods_name}', 1, '{$order_amount}', '{$item['added_fee']}')
	                ";
 				//$order_amount todo
 				$db->query($sql_order_goods);
 				$order_goods_id = $db->insert_id();

 				$sql = "
		                 INSERT INTO {$ecs->table('oukoo_erp')}
		                 (order_goods_id, erp_goods_sn, out_sn, action_user, 
		                  order_type, purchase_paid_amount, is_new, provider_id, facility_id) 
		                 VALUES 
		                 ('{$order_goods_id}', '{$item['erp_goods_sn']}', '{$item['in_sn']}', '{$_SESSION['admin_name']}', 
		                 '{$item['order_type']}', '{$item['purchase_paid_amount']}', 
		                 '{$item['is_new']}', '{$item['provider_id']}', '{$facility_id}')
	                 ";
 				$db->query($sql);
 				add_erp_log($item['in_sn'], 'out');
 				if($item['is_new'] == 'NEW') {
 					$fromStatusId = 'INV_STTS_AVAILABLE';
 				} else if ($item['is_new'] == 'SECOND_HAND') {
 					$fromStatusId = 'INV_STTS_USED';
 				} else {
 					$fromStatusId = 'INV_STTS_DEFECTIVE';
 				}
 					
 				createDeliverInventoryTransaction('ITT_BORROW',array('goods_id'=>$goods_id, 'style_id'=>$style_id),
 				1, $item['erp_goods_sn'], $item['order_type'], null, $order_id, $fromStatusId, '', $order_goods_id, $facility_id);
 			}
 			$message = "借机已经操作，请勿重复操作。";
 			$result['success'] = '1';
 			$result['message'] = $message;
 		} elseif (count($sn_list) < $goods_num) {
 			$message = "该仓库中库存不足，请重新操作。";
 			$result['success'] = '0';
 			$result['message'] = $message;
 		}
 	} else {
 		$message = '请先选择正确的商品。';
 		$result['success'] = '0';
 		$result['message'] = $message;
 	}
 	return $result;
 }
?>
