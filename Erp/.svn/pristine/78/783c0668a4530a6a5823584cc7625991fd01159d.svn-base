<?php
/**
 * 增量更新淘宝库存
 */
define('IN_ECS', true);
require_once('../includes/init.php');
admin_priv('taobao_goods_stock_update');
require_once(ROOT_PATH . 'admin/function.php');
require_once 'admin/includes/taobaosdk/lotusphp_runtime/Logger/Logger.php';
require_once(ROOT_PATH . 'admin/includes/taobaosdk/top/TopClient.php');
require_once(ROOT_PATH . 'admin/includes/taobaosdk/top/request/ItemQuantityUpdateRequest.php');
require_once (ROOT_PATH . 'includes/helper/uploader.php');
require_once (ROOT_PATH . 'includes/helper/array.php');
$act = isset($_POST['act']) ? trim($_POST['act']) : false;
global $db;

//判断组织是否为具体的业务单位
if(!party_explicit($_SESSION['party_id'])) {
    sys_msg("请选择具体的组织后再来录入订单");
}

$sql = "select taobao_shop_conf_id, nick
    from taobao_shop_conf where party_id = '{$_SESSION['party_id']}' and status = 'OK' 
";


$taobao_shop_list = $db->getAll($sql);
$smarty->assign("taobao_shop_list", $taobao_shop_list);
if ($_SERVER ['REQUEST_METHOD'] == 'POST' && $act) {
	switch ($act) {
		case 'upload':
	    $taobao_shop_id = trim($_POST['taobao_shop']);
	    // excel读取设置
		$tpl = array('批量加库存'  =>
			array('outer_id'=>'商家编码',
				  'quantity'=>'数量',
			));
		
		QLog::log ( '外包发货：' );
		/* 文件上传并读取 */
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
		$rowset = $result ['批量加库存'];
		
		// 订单数据读取失败
		if (empty ( $rowset )) {
			$smarty->assign ( 'message', 'excel文件中没有数据,请检查文件' );
			break;
		}
		
		$order_check_array = array('outer_id','quantity');
		foreach ($order_check_array as $check_column) {
	        $region_array = Helper_Array::getCols($rowset, $check_column);
			$region_size = count($region_array);
			Helper_Array::removeEmpty ($region_array);
			if($region_size > count($region_array)){
				$smarty->assign('message','excel里面存在空的'.$tpl['批量加库存'][$check_column]);
				break 2;
			}
		}
		
		foreach (Helper_Array::getCols($rowset, 'quantity') as $item_value) {
			if(!(preg_match ('/^[1-9]([0-9]+)?$/', $item_value) && $item_value > 0)){
				$smarty->assign('message',"添加数量必须为正整数");
				break 2;
			}
		}
		
		$error_outer_ids = "";
		foreach (Helper_Array::getCols($rowset, 'outer_id') as $item_value) {
			$sql_outer = "select * from ecshop.sync_taobao_items_sku stis" .
				" inner join ecshop.sync_taobao_items sti on sti.num_iid = stis.num_iid" .
				" where stis.outer_id = '".$item_value."' and sti.approve_status = 'onsale'";
			$item = $db->getRow($sql_outer);
			if(empty($item)){
				$sql_outer = "select * from ecshop.sync_taobao_items where outer_id = '".$item_value."' " .
						"and approve_status = 'onsale'";
				$item = $db->getRow($sql_outer);
			}
			if (empty($item)){
				$error_outer_ids = $item_value + ",";
			}			
		}
		
		if($error_outer_ids != ""){
			$smarty->assign('message',"以下outer_id有错误。系统上找不到");
			break;
		}
		
		$out_str = "";
		foreach ($rowset as $row) {
			$result = goods_stock_update($taobao_shop_id,$row['outer_id'],$row['quantity']);
			$out_str = $out_str.$row['outer_id'].",".$row['quantity'].",".$result."\n";
		}
		var_dump($out_str);
		break;
	}
}

function goods_stock_update($taobao_shop_id,$outer_id,$quantity){
	global $db;
    if (!empty($taobao_shop_id) && !empty($outer_id) & !empty($quantity)) {
		$sql_api = "select * from ecshop.taobao_api_params where taobao_api_params_id = ".$taobao_shop_id;
		$taobao_api = $db->getRow($sql_api);
		
		$sql_outer = "select * from ecshop.sync_taobao_items_sku stis" .
				" inner join ecshop.sync_taobao_items sti on sti.num_iid = stis.num_iid" .
				" where stis.outer_id = '".$outer_id."' and sti.approve_status = 'onsale'";
		$item = $db->getRow($sql_outer);
		if(empty($item)){
			$sql_outer = "select * from ecshop.sync_taobao_items where outer_id = '".$outer_id."' " .
					"and approve_status = 'onsale'";
			$item = $db->getRow($sql_outer);
		}

        if (!empty($taobao_api)) {
            $c = new TopClient();
            $c->appkey = $taobao_api['app_key'];
            $c->secretKey = $taobao_api['app_secret'];
            $request = new ItemQuantityUpdateRequest();
            $request->setNumIid($item['num_iid']);
            if (!empty($info['sku_id'])) {
                $request->setSkuId($item['sku_id']);
                $request->setOuterId($item['outer_id']);
            }
            $request->setQuantity($quantity);
            $request->setType(2);
            try {
                $response = $c->execute($request, $taobao_api['session_id']);
                if (!empty($response->item)) {
                    if (!empty($item['sku_id'])) {
                        foreach ($response->item->skus->sku as $sku) {
                            if ($sku->sku_id == $item['sku_id']) {
//                                $message = "商家编码：" . $item['outer_id'] ." 库存已经修改为：". $sku->quantity ."修改时间：". $sku->modified;
                            }
                        }
                    } else {
//                        $message = "商家编码：". $item['outer_id']." 库存已经修改为： " . $response->item->num ." 修改时间：". $response->item->modified;
                    }
                } else {
                    $message = "商家编码：".$item['outer_id'] . " 库存同步错误结果：".$response->sub_msg ."错误代码：".$response->sub_code;
                }
            } catch (Exception $e) {
                $message = "商家编码：". $item['outer_id'] ." 库存修改商品异常";
            }
        } else {
            $message = "该商家编码暂未同步到ERP系统，如有问题，请联系ERP组。";
        }
    } else {
        $message="淘宝店铺、商品商家编码、增量库存值与不能为空，请检查您的输入值";
    }
    return $message;
}


$smarty->display("taobao/taobao_goods_stock_update.htm");
