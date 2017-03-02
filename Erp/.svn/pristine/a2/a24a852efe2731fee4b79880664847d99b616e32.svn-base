<?php

define('IN_ECS', true);
require_once('../includes/init.php');
include_once(ROOT_PATH . 'includes/cls_json.php');
require_once (ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH. "/RomeoApi/lib_inventory.php");
require(ROOT_PATH . "/includes/lib_order.php");
require_once (ROOT_PATH . 'admin/function.php');
require_once (ROOT_PATH . 'includes/lib_common.php');
require_once(ROOT_PATH . "/includes/debug/lib_log.php");

define('VARIANCE_ADD', VARIANCE_ADD);//'-v 盘盈'
define('VARIANCE_MINUS', VARIANCE_MINUS);//'-v 盘亏'

//if (!in_array($_SESSION['admin_name'], array('lchen', 'ychen', 'xlhong', 'hbai','qdi'))) {
//    exit('access denied');
//}

if (!party_explicit($_SESSION['party_id'])) {
    exit('请选择分公司的party_id，再进行操作');
}
admin_priv('physicalInventoryApply');

$type =trim($_REQUEST['act']); 
if($type == 'batchSn'){
    $json = new JSON();
    $goods_id = trim($_REQUEST['goods_id']);
    $style_id = trim($_REQUEST['style_id']);
    $facility_id = trim($_REQUEST['facility_id']);
    $sql = "SELECT DISTINCT(ii.batch_sn) 
        FROM romeo.inventory_item ii 
        INNER JOIN romeo.product_mapping pm ON ii.product_id = pm.product_id
        WHERE  pm.ecs_goods_id = '{$goods_id}' AND  pm.ecs_style_id = '{$style_id}' 
        AND ii.facility_id ='{$facility_id}' AND ii.batch_sn is not null " ;
    $data = array();
    $data['error'] = 0; 
    $result  =  $db->getAll($sql);
    foreach ($result as $key => $value) {
      $data['list'][] = $value['batch_sn']; 
    }
    echo $json->encode($data);
    exit;
}else if ($type == 'del') {
	$orderGoodsId = trim($_REQUEST['orderGoodsId']);
	$json = new JSON();
    if(empty($orderGoodsId)){
    	die('没有要调整的商品');
    }
    $sqlo = " select order_id from ecshop.ecs_order_goods
    		  where rec_id = '{$orderGoodsId}'
    		";
    $order_id = $db->getOne($sqlo);
    $result = cancelOrderInventoryReservation($order_id);
    
    $sql3 ="select oir.status as reserve_status 
    		from ecshop.ecs_order_goods og
    		inner join ecshop.ecs_order_info oi on oi.order_id = og.order_id   		 
			left join romeo.order_inv_reserved oir on oir.order_id = oi.order_id
			where rec_id = '{$orderGoodsId}'";
	$reserve_status = $db -> getOne($sql3);
    $message="";
    $sql = "  delete from ecshop.ecs_order_goods
    		  where rec_id = '{$orderGoodsId}'
    		";
    if(empty($reserve_status)){
    	 $message = $db->query($sql);
    }    
	echo $json->encode(array('message'=>$message));
	exit;
	
} else if ($type == '导出已申请调整商品'){
        // 生成Excel文档
      	set_include_path(get_include_path() . PATH_SEPARATOR . '.././includes/Classes/');
        require_once ('PHPExcel.php');
        require_once ('PHPExcel/IOFactory.php');
        $filename = "已申请调整商品清单";
        $excel = new PHPExcel();
        $excel->getProperties()->setTitle($filename);        
        $sheet = $excel->getActiveSheet();
        $sheet->setCellValue('A1', "商品名");
        $sheet->setCellValue('B1', "商品条码");
        $sheet->setCellValue('C1', "库存状态");
        $sheet->setCellValue('D1', "仓库");
        $sheet->setCellValue('E1', "申请调整数量");
        $sheet->setCellValue('F1', "商品单价");
        $sheet->setCellValue('G1', "申请时间");
        $sheet->setCellValue('H1', "订单号");
        $sheet->setCellValue('I1', "类型");
        $sheet->setCellValue('J1', "出库数量");
        $sheet->setCellValue('K1', "出库时间");
        $sheet->setCellValue('L1', "批次号");
        $i=2;
        $already_apply_goods = get_virance_order_info();
        foreach ($already_apply_goods as $item) {   
            $sheet->setCellValue("A{$i}", $item['goods_name']);
            $sheet->setCellValue("B{$i}", "'".$item['barcode']);

            if($item['status_id'] == 'INV_STTS_USED'){
              $sheet->setCellValue("C{$i}", '二手库');
            }else{
              $sheet->setCellValue("C{$i}", '正式库');
            }
            
            $sheet->setCellValue("D{$i}", $item['facility_name']);
            $sheet->setCellValue("F{$i}", $item['unit_cost']);
            $sheet->setCellValue("G{$i}", $item['order_time']);
            $sheet->setCellValue("H{$i}", $item['order_sn']);
            $sheet->setCellValue("L{$i}", $item['batch_sn']);

            if($item['order_type_id'] == 'VARIANCE_ADD'){
            	$sheet->setCellValue("E{$i}", $item['goods_number']);
            	$sheet->setCellValue("I{$i}", '盘盈');
            }else {
            	$sheet->setCellValue("E{$i}", -$item['goods_number']);
            	$sheet->setCellValue("I{$i}", '盘亏');
            }
            $sheet->setCellValue("J{$i}", $item['out_num']);
            $sheet->setCellValue("K{$i}", $item['out_time']);
            $i++;
        }
        if (!headers_sent()) {
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="'.$filename.'.xlsx"');
            header('Cache-Control: max-age=0');
            $output = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
            $output->save('php://output');
            exit;
        }
}elseif ($type == '批量导入模板下载'){
		set_include_path(get_include_path() . PATH_SEPARATOR . '.././includes/Classes/');
        require_once ('PHPExcel.php');
        require_once ('PHPExcel/IOFactory.php');
        $filename = "批量导入模板";
        $excel = new PHPExcel();
        $excel->getProperties()->setTitle($filename);
        $sheet = $excel->getActiveSheet();
        $sheet->setCellValue('A1', "商品条码");
        $sheet->setCellValue('B1', "数量");
        $sheet->setCellValue('C1', "库存状态");
        $sheet->setCellValue('D1', "批次号");
        $sheet->setCellValue('E1', "仓库");
        $sheet->setCellValue('F1', "备注");
       if (!headers_sent()) {
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="'.$filename.'.xlsx"');
            header('Cache-Control: max-age=0');
            $output = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
            $output->save('php://output');
            exit;
        }
}else if ($type == 'search'){  //获得商品条码
	$goods_id = $_REQUEST['goods_id']; 
	$style_id = $_REQUEST['style_id']; 
	$json = new JSON();
	$sql = "
             SELECT IFNULL(gs.barcode,g.barcode) as barcode
             FROM {$ecs->table('goods')} AS g
             LEFT JOIN {$ecs->table('goods_style')} AS gs ON gs.goods_id = g.goods_id 
             WHERE g.goods_party_id = '{$_SESSION['party_id']}'
             AND   g.goods_id = '{$goods_id}'
             and  IFNULL(gs.style_id ,0) = '{$style_id}'
           ";
	echo $json->encode(array('content'=>$db->getOne($sql)));
	exit;
	
} else if($type == 'create') {
    // 数据的整理工作
    
//    $comment = trim($_REQUEST['comment']);
//    if(!$comment) die('请输入调整单的原因');
//    
//    $facility_id = trim($_REQUEST['facility_id']);
//    if (!$facility_id) die('请选择仓库');

    $goods_add=array();
    $goods_minus=array();
    foreach ($_POST['goods_id'] as $key => $goods_id) {
        $goodsId  = intval($_POST['goods_id'][$key]);
        if(!$goodsId) {
            die('商品id不正确');
            break;
        }
        $styleId = intval($_POST['style_id'][$key]);

        $statusId = trim($_POST['status_id'][$key]);
        $quantityOnHandVar = intval($_POST['goods_number'][$key]);
        $purchase_paid_amount = trim($_POST['purchase_paid_amount'][$key]);
        $batchSn = trim($_POST['batchsn'][$key]);
        $commentOne = trim($_POST['comments'][$key]); 
        $facilityIdOne = trim($_POST['facility_ids'][$key]); 

        
		//盘盈价格，最低价
		//盘亏价格，随机出
//		$sql = "select 1
//					from romeo.inventory_item ii
//					 inner join romeo.product_mapping pm on ii.product_id = pm.product_id
//				where pm.ecs_style_id = '{$styleId}' and pm.ecs_goods_id = '{$goodsId}' and ii.facility_id = '{$facility_id}' 
//					limit 1 ";
//		$is_exit = $db->getOne($sql);
//		if(!$is_exit){
//			die('该商品未成入过库');
//        	break;
//		}
        $batch_sql ="";
        if(isset($batchSn) && $batchSn !="-1" && trim($batchSn) !=""){
            $batch_sql = " and ii.batch_sn = '{$batchSn}' "; 
        }else{
            $batchSn = "";
        }
		
//    	$sql = "select ii.unit_cost
//					from romeo.inventory_item ii
//					 inner join romeo.product_mapping pm on ii.product_id = pm.product_id
//				where pm.ecs_style_id = '{$styleId}' and pm.ecs_goods_id = '{$goodsId}' and ii.facility_id = '{$facility_id}' 
//					 and ii.status_id = '{$statusId}' ".$batch_sql." order by ii.unit_cost asc";
//		$purchase_paid_amount = $db->getOne($sql);
//		if(empty($purchase_paid_amount)){
//			$sql = "select ii.unit_cost
//					from romeo.inventory_item ii
//					 inner join romeo.product_mapping pm on ii.product_id = pm.product_id
//				where pm.ecs_style_id = '{$styleId}' and pm.ecs_goods_id = '{$goodsId}' and ii.facility_id = '{$facility_id}'".
//                $batch_sql."
//					  order by ii.unit_cost asc";
//			$purchase_paid_amount = $db->getOne($sql);
//		}
		if($quantityOnHandVar > 0){
	    	$sql = "select ii.unit_cost
						from romeo.inventory_item ii
						 inner join romeo.product_mapping pm on ii.product_id = pm.product_id
					where pm.ecs_style_id = '{$styleId}' and pm.ecs_goods_id = '{$goodsId}' and ii.facility_id = '{$facilityIdOne}' 
						 and ii.status_id = '{$statusId}' ".$batch_sql." order by ii.created_stamp desc";
			//Qlog::log($sql);
			$purchase_paid_amount = $db->getOne($sql);
        }else{
        	$sql = "select ii.unit_cost
						from romeo.inventory_item ii
						 inner join romeo.product_mapping pm on ii.product_id = pm.product_id
					where pm.ecs_style_id = '{$styleId}' and pm.ecs_goods_id = '{$goodsId}' and ii.facility_id = '{$facilityIdOne}' 
						 and ii.status_id = '{$statusId}' ".$batch_sql." and ii.quantity_on_hand_total > 0
					order by ii.created_stamp asc";
			//Qlog::log($sql);
			$purchase_paid_amount = $db->getOne($sql);
        }

		if(empty($purchase_paid_amount)){
			$purchase_paid_amount = 0;
		}

        $row = array(
        	'goodsId' 				=> $goodsId, 
        	'styleId' 				=> $styleId,
        	'statusId' 				=> $statusId, 
        	'quantityOnHandVar' 	=> $quantityOnHandVar,
            'batch_sn'              => $batchSn, 
        	'purchase_paid_amount' 	=> $purchase_paid_amount,
        	'facilityIdOne' 	    => $facilityIdOne,
        	'commentOne' 	        => $commentOne);
        if($quantityOnHandVar > 0){
        	$goods_add[]  = $row;
        }else{
        	$goods_minus[]  = $row;
        }
    }

    if(empty($goods_add) && empty($goods_minus)) {
        die('没有要调整的商品');
    }
	if(!empty($goods_add)){
    	create_inventory_virance_order_new($goods_add,VARIANCE_ADD);
    }
    if(!empty($goods_minus)){
    	create_inventory_virance_order_new($goods_minus,VARIANCE_MINUS);
    }
//    $smarty->assign('comment',$comment);
//    $smarty->assign('already_apply_goods', get_virance_order_info());
sys_msg('似乎大事已成，准备跳转！',0,array(array('href'=>'./physical_inventory_apply_order.php?act='.urlencode('搜索'))),true);

}elseif ($type == '搜索'){
	
	$smarty->assign('already_apply_goods', get_virance_order_info());
	
}

$smarty->assign('user_current_party_name', party_mapping($_SESSION['party_id']));
$smarty->assign('available_facility', get_available_facility());
$smarty->display('virance_inventory/physical_inventory_apply_order_batchsn.htm');

function get_virance_order_info(){
	global $db;
	$condition="";
    $facility_id =$_REQUEST['facility_id'];
	if($facility_id != -1 && $facility_id != ''){
		if(is_array($facility_id)){
		   $condition .= " AND oi.facility_id ".db_create_in ($facility_id);
		}else{
			$condition .= " AND oi.facility_id ='{$facility_id}'";
		}		
	}
	$sql = 
	   "
	   select oi.facility_id,og.rec_id,og.goods_name,og.goods_number, og.goods_attr,ifnull(ii.unit_cost, og.goods_price) unit_cost, og.status_id,
			  oi.order_sn,oi.order_type_id,og.rec_id as order_goods_id,
              IFNULL(gs.barcode,g.barcode) as barcode,
			  ifnull(sum(-iid.quantity_on_hand_diff),0) as out_num,
			  f.facility_name,oi.order_time,iid.created_stamp as out_time, oird.status as reserve_status
			from ecshop.ecs_order_info oi
			inner join ecshop.ecs_order_goods og on oi.order_id = og.order_id
			left join romeo.order_inv_reserved_detail oird on oird.order_id = oi.order_id
			inner join romeo.facility f on oi.facility_id = f.FACILITY_ID
            left join ecshop.ecs_goods_style gs on gs.goods_id = og.goods_id and gs.style_id = og.style_id and gs.is_delete=0
            left join ecshop.ecs_goods g on g.goods_id = og.goods_id
			left join romeo.inventory_item_detail iid on iid.order_goods_id = convert(og.rec_id using utf8)
			left join romeo.inventory_item ii on iid.inventory_item_id = ii.inventory_item_id
			where order_type_id in ('VARIANCE_ADD','VARIANCE_MINUS') and oi.party_id = '{$_SESSION['party_id']}'
			  {$condition}
			group by og.rec_id 
			order by oi.order_time desc , oi.order_sn desc
	      ";

	$result =  $db->getAll($sql);
    foreach ($result as $key => &$good) {
        $batch_sn = "";
        if( strpos($good['goods_attr'],"batch_sn") > 0 ){
            $batch_sn = json_decode($good['goods_attr'],true); 
            $batch_sn = $batch_sn['batch_sn'];
        }
        $good['batch_sn'] = $batch_sn;
    }
    return $result;
}

function create_inventory_virance_order($comment,$facility_id,$goods,$order_type_id){
	global $db,$ecs;
	$error_no = 0;
    
    foreach($goods as $good){
    	do {
	        $order_sn = get_order_sn()."-v";
	        $sql = "INSERT INTO ecshop.ecs_order_info
	                (order_sn, order_time, order_status, shipping_status , pay_status, user_id, postscript, 
	                order_type_id, party_id, facility_id)
	                VALUES('{$order_sn}', NOW(), 2, 0, 0, {$_SESSION['admin_id']},
	                         '库存调整订单 {$comment}', '{$order_type_id}', '{$_SESSION['party_id']}', '{$facility_id}')";
	        $db->query($sql, 'SILENT');
	        $error_no = $db->errno();
	        if ($error_no > 0 && $error_no != 1062) {
	            die($db->errorMsg());
	        }
	    } while ($error_no == 1062); // 如果是订单号重复则重新提交数据
	    $sqls[] = $sql;
	    $order_id = $db->insert_id();
    	
    	$goodsId = trim($good['goodsId']);
        $styleId = intval($good['styleId']);
        $statusId = trim($good['statusId']);
        $batch_sn = $good['batch_sn'];
        $quantityOnHandVar = intval($good['quantityOnHandVar']);
        $goods_count = abs($quantityOnHandVar);
        
         // 把 batch_sn 存入 order_goods 表中的 goods_attr 中 
        if( $batch_sn !=""){
            $batch = array("batch_sn"=>$batch_sn);
            $batch = json_encode($batch);
        }else{
            $batch = ""; 
        }
        $sql = "select p.product_name from romeo.product_mapping pm 
        			inner join romeo.product p on pm.product_id = p.product_id
        			where pm.ecs_goods_id = '{$goodsId}' and pm.ecs_style_id = '{$styleId}'";
        			
        $goods_name = $db->getOne($sql);
    	$goods_name = mysql_real_escape_string($goods_name); 
        $purchase_paid_amount = trim($good['purchase_paid_amount']);
        $sql ="";
        // 插入对应的记录到order_goods表
        if($batch !="" && strlen($batch) > 1 ){
            $sql = "INSERT INTO {$ecs->table('order_goods')}
                            (order_id, goods_id, style_id, goods_name, goods_number, goods_price, status_id,goods_attr) 
                      VALUES('{$order_id}', '{$goodsId}', '{$styleId}', '{$goods_name}', 
                               '{$goods_count}', '{$purchase_paid_amount}','{$statusId}','{$batch}')";
        } else {
            $sql = "INSERT INTO {$ecs->table('order_goods')}
                            (order_id, goods_id, style_id, goods_name, goods_number, goods_price, status_id) 
                      VALUES('{$order_id}', '{$goodsId}', '{$styleId}', '{$goods_name}', 
                               '{$goods_count}', '{$purchase_paid_amount}','{$statusId}')";
        }
        $db->query($sql);
    }
}

function create_inventory_virance_order_new($goods,$order_type_id){
	global $db,$ecs;
	$error_no = 0;
   
    QLog::log("create_inventory_virance_order_new : goods size:".count($goods));
    foreach($goods as $good){
    	
    	$commentOne = trim($good['commentOne']);
        $facilityIdOne = trim($good['facilityIdOne']);
        $goodsId = trim($good['goodsId']);
        $styleId = intval($good['styleId']);
        $statusId = trim($good['statusId']);
        $batch_sn = $good['batch_sn'];
        $quantityOnHandVar = intval($good['quantityOnHandVar']);
        $goods_count = abs($quantityOnHandVar);
       
        QLog::log("create_inventory_virance_order_new : goods_id".$goodsId." styleId:".$styleId." facility_id:".$facilityIdOne);
        
        $sql="SELECT g.goods_party_id
	                FROM  ecshop.ecs_goods AS g
	                WHERE g.goods_id = '{$goodsId}'";
    	$party_id = $db->getOne($sql);
        
        $db->start_transaction(); 
        
    	do {
	        $order_sn = get_order_sn()."-v";
	        $sql = "INSERT INTO ecshop.ecs_order_info
	                (order_sn, order_time, order_status, shipping_status , pay_status, user_id, postscript, 
	                order_type_id, party_id, facility_id)
	                VALUES('{$order_sn}', NOW(), 2, 0, 0, {$_SESSION['admin_id']},
	                         '库存调整订单 {$commentOne}', '{$order_type_id}', '{$party_id}', '{$facilityIdOne}')";
	        $db->query($sql, 'SILENT');
	        QLog::log("create_inventory_virance_order_new : insert order sql".$sql);
	        $error_no = $db->errno();
	        if ($error_no > 0 && $error_no != 1062) {
	            die($db->errorMsg());
	        }
	    } while ($error_no == 1062); // 如果是订单号重复则重新提交数据
	    $sqls[] = $sql;
	    $order_id = $db->insert_id();
    	
    	
        
         // 把 batch_sn 存入 order_goods 表中的 goods_attr 中 
        if( $batch_sn !=""){
            $batch = array("batch_sn"=>$batch_sn);
            $batch = json_encode($batch);
        }else{
            $batch = ""; 
        }
        $sql = "select p.product_name from romeo.product_mapping pm 
        			inner join romeo.product p on pm.product_id = p.product_id
        			where pm.ecs_goods_id = '{$goodsId}' and pm.ecs_style_id = '{$styleId}'";
        			
        $goods_name = $db->getOne($sql);
    	$goods_name = mysql_real_escape_string($goods_name); 
        $purchase_paid_amount = trim($good['purchase_paid_amount']);
        $sql ="";
        // 插入对应的记录到order_goods表
        if($batch !="" && strlen($batch) > 1 ){
            $sql = "INSERT INTO {$ecs->table('order_goods')}
                            (order_id, goods_id, style_id, goods_name, goods_number, goods_price, status_id,goods_attr) 
                      VALUES('{$order_id}', '{$goodsId}', '{$styleId}', '{$goods_name}', 
                               '{$goods_count}', '{$purchase_paid_amount}','{$statusId}','{$batch}')";
            QLog::log("create_inventory_virance_order_new : insert goods sql".$sql);
        } else {
            $sql = "INSERT INTO {$ecs->table('order_goods')}
                            (order_id, goods_id, style_id, goods_name, goods_number, goods_price, status_id) 
                      VALUES('{$order_id}', '{$goodsId}', '{$styleId}', '{$goods_name}', 
                               '{$goods_count}', '{$purchase_paid_amount}','{$statusId}')";
            QLog::log("create_inventory_virance_order_new : insert goods sql".$sql);
        }
        $db->query($sql);
        
        $db->commit();
    }
}


