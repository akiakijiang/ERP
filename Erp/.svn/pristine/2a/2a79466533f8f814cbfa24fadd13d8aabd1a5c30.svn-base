<?php

define('IN_ECS', true);
require_once('../includes/init.php');
include_once(ROOT_PATH . 'includes/cls_json.php');
require_once (ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH. "/RomeoApi/lib_inventory.php");
require(ROOT_PATH . "/includes/lib_order.php");
require_once (ROOT_PATH . 'admin/function.php');
require_once (ROOT_PATH . 'includes/lib_common.php');

if (!party_explicit($_SESSION['party_id'])) {
    exit('请选择分公司的party_id，再进行操作');
}
admin_priv('physicalInventoryApply');
$act =trim($_REQUEST['act']); 

if ($act == '搜索'){
	
   $smarty->assign('already_apply_goods', get_virance_order_info());	
}
if ($act == '导出已申请调整商品' ){
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
        $sheet->setCellValue('F1', "申请时间");
        $sheet->setCellValue('G1', "订单号");
        $sheet->setCellValue('H1', "类型");
        $sheet->setCellValue('I1', "出库数量");
        $sheet->setCellValue('J1', "出库时间");
        $i=2;
        $already_apply_goods = get_virance_order_info();
        foreach ($already_apply_goods as $item) {   
            $sheet->setCellValue("A{$i}", $item['goods_name']);
            $sheet->setCellValue("B{$i}", $item['barcode']);

            if($item['status_id'] == 'INV_STTS_USED'){
              $sheet->setCellValue("C{$i}", '二手库');
            }else{
              $sheet->setCellValue("C{$i}", '正式库');
            }
            
            $sheet->setCellValue("D{$i}", $item['facility_name']);
            $sheet->setCellValue("E{$i}", $item['goods_number']);
            $sheet->setCellValue("F{$i}", $item['order_time']);
            $sheet->setCellValue("G{$i}", $item['order_sn']);

            if($item['order_type_id'] == 'VARIANCE_ADD'){
              $sheet->setCellValue("H{$i}", '盘盈');
            }else {
              $sheet->setCellValue("H{$i}", '盘亏');
            }
            $sheet->setCellValue("I{$i}", $item['out_num']);
            $sheet->setCellValue("J{$i}", $item['out_time']);
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
}

$smarty->assign('available_facility', get_available_facility());
$smarty->display('virance_inventory/physical_inventory_apply_order_detail.htm');

function get_virance_order_info(){
	global $db;
	$condition="";
	$facility_id =trim($_REQUEST['facility_id']);
	if($facility_id != -1 && $facility_id != ''){
		$condition .= " AND oi.facility_id = '{$facility_id}' ";
	}
	$sql = 
	   "
	   select oi.facility_id,og.rec_id,og.goods_name,og.goods_number, goods_price, status_id,
			  oi.order_sn,oi.order_type_id,
              IFNULL(gs.barcode,g.barcode) as barcode,
			  ifnull(sum(-iid.quantity_on_hand_diff),0) as out_num,
			  f.facility_name,oi.order_time,iid.created_stamp as out_time
			from ecshop.ecs_order_info oi
			inner join ecshop.ecs_order_goods og on oi.order_id = og.order_id
			inner join romeo.facility f on oi.facility_id = f.FACILITY_ID
            left join ecshop.ecs_goods_style gs on gs.goods_id = og.goods_id  and gs.style_id = og.style_id and gs.is_delete=0
            left join ecshop.ecs_goods g on g.goods_id = og.goods_id
			left join romeo.inventory_item_detail iid on iid.order_goods_id = convert(og.rec_id using utf8)
			where order_type_id in ('VARIANCE_ADD','VARIANCE_MINUS') and oi.party_id = '{$_SESSION['party_id']}'
			 {$condition}
			group by og.rec_id 
			order by oi.order_time desc
	      ";
	return  $db->getAll($sql);
}
