<?php
/**
 * 开票清单详细新页面，可以编辑开票清单，搜索入库商品进行开票清单明细添加，以及删除商品
 *与老页面不同的是：搜索可添加商品只有两种情况-c订单和-gt订单，添加商品是一个订单一条记录，把老逻辑-c和-gt在同一段时间这个情况给去除了
 * 页面参数：
 */
define('IN_ECS', true);
require('../includes/init.php');
require(ROOT_PATH . 'admin/function.php');
require('purchase_invoice_request.php');
require_once(ROOT_PATH . "RomeoApi/lib_inventory.php");
admin_priv("cw_review_purchase_invoice", "cw_review_purchase_invoice_plus", "cg_edit_purchase_invoice");
require_once(ROOT_PATH . 'includes/debug/lib_log.php');
    
$purchase_invoice_request_id = $_REQUEST["purchase_invoice_request_id"];
$act = $_REQUEST['act'];
$search = $_REQUEST['search'];
$start_date = $_REQUEST['start_date'];
$end_date = $_REQUEST['end_date'];
if(empty($end_date)){
    $end_date = date("Y-m-d",time());
}
if(empty($start_date)){
    $start_date = date ("Y-m-d", strtotime('-1 Months',time()));
}

//编辑开票清单
if($act == "edit_purchase_invoice_request"){
    $type_id = $_REQUEST['type_id'];
    $provider_id = $_REQUEST['provider_id'];
    $note = $_REQUEST['note'];
    $status = $_REQUEST['status'];
    $sql_add = '';
    if(!empty($status)){
        $sql_add .=",status='{$status}'";
    }
    $sql_update = "update romeo.purchase_invoice_request set type_id='{$type_id}',supplier_id='{$provider_id}',note='{$note}'".$sql_add.
    "where purchase_invoice_request_id = '{$purchase_invoice_request_id}'
    ";
    $result = $db->query($sql_update);
    if ($result) {
        $info = "操作成功";
    } else {
        $info = "操作失败，请联系ERP！";
    }
    $back = $_POST['back'] ? $_POST['back'] : "{$WEB_ROOT}admin/purchase_invoice/purchase_invoice_request_detail_new.php?purchase_invoice_request_id={$purchase_invoice_request_id}";
    alert_back($info, $back);
    die();
}

$sql="select pir.*,p.provider_name
    from romeo.purchase_invoice_request pir
    left join ecshop.ecs_provider p on p.provider_id = pir.supplier_id
    where purchase_invoice_request_id ='{$purchase_invoice_request_id}'";
$res = $db->getAll($sql);
$purchase_invoice_request = $res[0];
$purchase_invoice_request['match_cost'] = get_match_cost($purchase_invoice_request_id);

//修改开票清单状态
if($act == "init_request"){
    $request_id = $_REQUEST['request_id'];
    $sql_update_status = "
    update romeo.purchase_invoice_request set status = 'INIT' where purchase_invoice_request_id = '{$request_id}'
    ";
    $check_result = check_has_invoice($request_id);
    $result = array();
    if(!$check_result['success']){
        die(json_encode($check_result));

    }else{
        $item = $db -> query($sql_update_status);
        $result = array();
        if($item){
		  $result['success'] = 1;
		  $result['message'] = "开票清单初始化成功";
	   }else{
		  $result['success'] = 0;
		  $result['message'] = "开票清单初始化失败";
		  }
	   die(json_encode($result));
    }
}



//搜索可添加商品（分两类商品：-gt和-c订单的商品）
if($search == 'purchase_invoice_request_item_search'){
    $provider_id = $_REQUEST['provider_id'];
    $party_id = $_SESSION['party_id'];
    $purchase_sn = $_REQUEST['purchase_sn'];
    $provider_purchase_sn = $_REQUEST['provider_purchase_sn'];
    $batch_order_sn = $_REQUEST['batch_order_sn'];
    $goods_id = $_REQUEST['goods_id'];
    $goods_price = $_REQUEST['goods_price'];
    $facility_id = $_REQUEST['facility_id'];
    
    $sql_add = "";
    if(!empty($purchase_sn)){
        $sql_add .= " and o.order_sn = '{$purchase_sn}' ";
    }
    if(!empty($provider_purchase_sn)){
        $sql_add .= " and boi.provider_order_sn = '{$provider_purchase_sn}' ";
    }
    if(!empty($batch_order_sn)){
        $sql_add .= " and boi.batch_order_sn = '{$batch_order_sn}' ";
    }
    if(!empty($goods_id)){
        $sql_add .= " and og.goods_id = '{$goods_id}' ";
    }
    if(!empty($goods_price)){
        $sql_add .= " and ii.unit_cost = '{$goods_price}' ";
    }
    if($facility_id != '-1'){
        $sql_add .= " and ii.facility_id = '{$facility_id}' ";
    }
    $sql_gt = "select gto.order_sn, gtit.inventory_transaction_id, gtii.serial_number, gtii.product_id, ifnull(ii.unit_cost,0) as goods_price, 
            gto.order_type_id, gto.order_time, f.facility_name,gtiid.created_stamp,abs(gtiid.quantity_on_hand_diff) as quantity_on_hand_diff, 
            gteg.goods_name,gteg.goods_id,gtog.style_id,boi.provider_order_sn,gtit.inventory_transaction_id,gtiid.inventory_item_detail_id 
            from ecshop.ecs_order_info o 
            inner join romeo.purchase_return_map m  on m.purchase_order_id = o.order_id
            left join ecshop.ecs_batch_order_mapping bom on o.order_id = bom.order_id 
            left join ecshop.ecs_batch_order_info boi on boi.batch_order_id = bom.batch_order_id 
            left join ecshop.ecs_order_goods og on o.order_id = og.order_id and convert(m.purchase_order_goods_id using utf8) = og.rec_id
            inner join ecshop.ecs_order_info gto on convert(m.return_order_id using utf8) = gto.order_id
            left join ecshop.ecs_order_goods gtog on gtog.order_id = gto.order_id and cast(m.return_order_goods_id as UNSIGNED) = gtog.rec_id
            left join ecshop.ecs_goods gteg on gteg.goods_id = gtog.goods_id 
            left join romeo.inventory_item_detail iid on iid.order_goods_id = og.rec_id
            left join romeo.inventory_transaction it on iid.inventory_transaction_id = it.inventory_transaction_id and m.purchase_inv_transaction_id=it.inventory_transaction_id
            left join romeo.inventory_item ii on iid.inventory_item_id = ii.inventory_item_id and  it.to_inventory_item_id = ii.inventory_item_id 
            left join romeo.inventory_item_detail gtiid on gtiid.order_goods_id = gtog.rec_id
            left join romeo.inventory_transaction gtit on gtiid.inventory_transaction_id = gtit.inventory_transaction_id and m.RETURN_INV_TRANSACTION_ID = gtit.inventory_transaction_id
            left join romeo.inventory_item gtii on gtiid.inventory_item_id = gtii.inventory_item_id
            left join romeo.facility f on f.facility_id = ii.facility_id
            where o.party_id = '{$party_id}' and o.order_type_id = 'PURCHASE' and ii.status_id = 'INV_STTS_AVAILABLE' 
            and not exists( select 1 from romeo.purchase_invoice_request_item as piri where piri.RETURN_INVENTORY_TRANSACTION_ID = gtit.inventory_transaction_id)         
            and gto.order_sn like '%-gt'  and  iid.QUANTITY_ON_HAND_DIFF>0  and gtiid.QUANTITY_ON_HAND_DIFF<0 and gtii.status_id in ('INV_STTS_AVAILABLE','INV_STTS_DEFECTIVE') 
            and gtit.created_stamp >= '{$start_date}'  and gtit.created_stamp <= date_add('{$end_date}',interval 1 day)  
            and ii.provider_id = '{$provider_id}'  and f.facility_id = ii.facility_id {$sql_add}
        ";
    $sql_purchase = "select o.order_sn, it.inventory_transaction_id, ii.serial_number, ii.product_id, ifnull(ii.unit_cost,0) as goods_price, o.order_type_id, o.order_time, f.facility_name
            , iid.created_stamp, iid.quantity_on_hand_diff, eg.goods_name, eg.goods_id, og.style_id, boi.provider_order_sn, it.inventory_transaction_id,iid.inventory_item_detail_id  
            from ecshop.ecs_order_info o 
            left join ecshop.ecs_batch_order_mapping bom on o.order_id = bom.order_id 
            left join ecshop.ecs_batch_order_info boi on boi.batch_order_id = bom.batch_order_id , ecshop.ecs_order_goods og,ecshop.ecs_goods eg, romeo.inventory_item_detail iid, romeo.inventory_item ii, romeo.facility as f,romeo.inventory_transaction it 
            where o.order_type_id = 'PURCHASE' and ii.status_id = 'INV_STTS_AVAILABLE'
            and not exists( select 1 from romeo.purchase_invoice_request_item as piri where piri.INVENTORY_TRANSACTION_ID = it.inventory_transaction_id)
            and og.order_id = o.order_id and og.goods_id = eg.goods_id and iid.order_goods_id = cast(og.rec_id as char(20)) and iid.inventory_transaction_id = it.inventory_transaction_id and iid.inventory_item_id = ii.inventory_item_id and ii.inventory_item_id = it.to_inventory_item_id 
            and o.party_id = '{$party_id}'  
            and iid.QUANTITY_ON_HAND_DIFF>0  
            and it.created_stamp >= '{$start_date}'  and it.created_stamp <= date_add('{$end_date}',interval 1 day)   
            and ii.provider_id = '{$provider_id}'  and f.facility_id = ii.facility_id  {$sql_add}
        ";
    $sql_all = $sql_gt." union all 
        ".$sql_purchase." order by created_stamp ";
    Qlog::log($sql_all);
    $search_purchase_invoice_request_item = $db->getAll($sql_all);
    $search_purchase_invoice_request_item = add_iteration($search_purchase_invoice_request_item);
    $smarty->assign("purchase_list",$search_purchase_invoice_request_item);
    
}

//添加开票清单明细
if($act == 'purchase_invoice_request_item_add'){
    $index_list = $_POST['index_add'];
    if (is_array($index_list)) {
        foreach ($index_list as $index) {
            $product_id = $_POST["product_id_{$index}"];
            $serial_number = $_POST["serial_number_{$index}"];
            $fixed_cost = $_POST["fixed_cost_{$index}"];
            $amount = $_POST["amount_{$index}"];
            $transaction_id = $_POST["transaction_id_{$index}"];
            $inventory_item_detail_id = $_POST["inventory_item_detail_id_{$index}"];
            $order_sn = $_POST["order_sn_{$index}"];
            $order_type = $_POST["type_{$index}"];
            $sql = "select og.added_fee from
                romeo.inventory_transaction it
                inner join romeo.inventory_item_detail iid on iid.inventory_transaction_id = it.inventory_transaction_id
                inner join ecshop.ecs_order_goods og on og.rec_id = cast(iid.order_goods_id as unsigned)
                where it.inventory_transaction_id = '{$transaction_id}'";
            $added_fee = $db->getOne($sql);
            $unit_net_cost = $fixed_cost / $added_fee;
            $unit_tax = $fixed_cost - $unit_net_cost;
            $sql_item_id = "select purchase_invoice_request_item_id from romeo.purchase_invoice_request_item order by purchase_invoice_request_item_id desc limit 1
            ";
            $res = $db->getAll($sql_item_id);
            $purchase_invoice_request_item_id = $res[0][purchase_invoice_request_item_id];
            $purchase_invoice_request_item_id += 1;
            if($order_type == 'SUPPLIER_RETURN'){
                $total_cost = $fixed_cost * $amount;
                $unit_total_cost = $unit_net_cost * $amount;
                $total_tax = $total_cost - $unit_total_cost;
                $sql_insert = "insert into romeo.purchase_invoice_request_item(purchase_invoice_request_item_id,inventory_transaction_id,created_stamp,last_update_stamp,last_update_tx_stamp,product_id,purchase_invoice_request_id,
                    quantity,serial_number,unit_cost,unit_net_cost,unit_tax,return_inventory_transaction_id,return_quantity,return_amount,inventory_item_detail_id,return_inventory_item_detail_id)
                    values('{$purchase_invoice_request_item_id}',null,now(),now(),now(),'{$product_id}','{$purchase_invoice_request_id}',{$amount},'{$serial_number}',0,0,
                    0,'{$transaction_id}',{$amount},{$total_cost},null,'{$inventory_item_detail_id}')";

                $sql_update ="update romeo.purchase_invoice_request set last_update_stamp = now(),last_update_tx_stamp = now(),total_cost = total_cost - {$total_cost},total_net_cost = total_net_cost - {$unit_total_cost},
                    total_tax = total_tax - {$total_tax}
                    where purchase_invoice_request_id = '{$purchase_invoice_request_id}'
                    ";
            }elseif($order_type == 'SUPPLIER_SALE'){
                $amount = 0;
                $total_cost = $fixed_cost * $amount;
                $unit_total_cost = $unit_net_cost * $amount;
                $total_tax = $total_cost - $unit_total_cost;
                $sql_insert = "insert into romeo.purchase_invoice_request_item(purchase_invoice_request_item_id,inventory_transaction_id,created_stamp,last_update_stamp,last_update_tx_stamp,product_id,purchase_invoice_request_id,
                quantity,serial_number,unit_cost,unit_net_cost,unit_tax,return_inventory_transaction_id,return_quantity,return_amount,inventory_item_detail_id,return_inventory_item_detail_id)
                values('{$purchase_invoice_request_item_id}',null,now(),now(),now(),'{$product_id}','{$purchase_invoice_request_id}',{$amount},'{$serial_number}',0,0,
                0,'{$transaction_id}',{$amount},{$total_cost},null,'{$inventory_item_detail_id}')";
                
                    $sql_update ="update romeo.purchase_invoice_request set last_update_stamp = now(),last_update_tx_stamp = now(),total_cost = total_cost - {$total_cost},total_net_cost = total_net_cost - {$unit_total_cost},
                    total_tax = total_tax - {$total_tax}
                    where purchase_invoice_request_id = '{$purchase_invoice_request_id}'
                    ";
            }else{
                $sql_insert = "insert into romeo.purchase_invoice_request_item(purchase_invoice_request_item_id,inventory_transaction_id,created_stamp,last_update_stamp,last_update_tx_stamp,product_id,purchase_invoice_request_id,
                    quantity,serial_number,unit_cost,unit_net_cost,unit_tax,return_inventory_transaction_id,return_quantity,return_amount,inventory_item_detail_id,return_inventory_item_detail_id) 
                    values('{$purchase_invoice_request_item_id}','{$transaction_id}',now(),now(),now(),'{$product_id}','{$purchase_invoice_request_id}',{$amount},'{$serial_number}',{$fixed_cost},{$unit_net_cost},
                    {$unit_tax},null,null,null,'{$inventory_item_detail_id}',null)";
                $total_cost = $fixed_cost * $amount;
                $unit_total_cost = $unit_net_cost * $amount;
                $total_tax = $total_cost - $unit_total_cost;
                $sql_update ="update romeo.purchase_invoice_request set last_update_stamp = now(),last_update_tx_stamp = now(),total_cost = total_cost + {$total_cost},total_net_cost = total_net_cost + {$unit_total_cost},
                    total_tax = total_tax + {$total_tax}
                    where purchase_invoice_request_id = '{$purchase_invoice_request_id}'
                    ";
            }
            Qlog::log("更新语句：".$sql_update);
            Qlog::log("插入语句：".$sql_insert);
            $result1 = $db->query($sql_insert);
            $result2 = $db->query($sql_update);
            if($result1 && $result2){
                $info = "操作成功！";
            }else{
                $result = "操作失败！";
                break;
            }
        }   
    }else{
       die("无商品选中！"); 
    }
    $back = $_POST['back'] ? $_POST['back'] : "{$WEB_ROOT}admin/purchase_invoice/purchase_invoice_request_detail_new.php?purchase_invoice_request_id={$purchase_invoice_request_id}";
    alert_back($info, $back);
}

//已添加商品显示(只有采购订单类型)
$sql_added_item_purchase = "select eg.goods_id,p.ecs_style_id,eg.goods_name,sum(piri.quantity) as quantity,piri.unit_cost,piri.unit_net_cost,p.product_id      
        from romeo.purchase_invoice_request_item piri
        left join romeo.product_mapping p on p.product_id = piri.product_id 
        left join ecshop.ecs_goods eg on eg.goods_id = p.ecs_goods_id
        left join romeo.inventory_transaction it on it.inventory_transaction_id = piri.return_inventory_transaction_id 
        left join romeo.inventory_item_detail iid on iid.inventory_transaction_id = it.inventory_transaction_id
        left join ecshop.ecs_order_goods og on og.rec_id = cast(iid.order_goods_id as unsigned) 
        where piri.purchase_invoice_request_id = '{$purchase_invoice_request_id}' and piri.inventory_transaction_id <> '' 
        group by p.product_id,piri.unit_cost  
        ";
$added_item_list_purchase = $db->getAll($sql_added_item_purchase);

//已添加商品显示(只有-gt订单类型)
$sql_added_item_gt = "select eg.goods_id,p.ecs_style_id,eg.goods_name,piri.purchase_invoice_request_item_id,piri.return_inventory_transaction_id,piri.inventory_transaction_id,
        piri.return_amount,piri.return_quantity,og.added_fee,p.product_id 
        from romeo.purchase_invoice_request_item piri
        left join romeo.product_mapping p on p.product_id = piri.product_id
        left join ecshop.ecs_goods eg on eg.goods_id = p.ecs_goods_id
        left join romeo.inventory_transaction it on it.inventory_transaction_id = piri.return_inventory_transaction_id
        left join romeo.inventory_item_detail iid on iid.inventory_transaction_id = it.inventory_transaction_id
        left join ecshop.ecs_order_goods og on og.rec_id = cast(iid.order_goods_id as unsigned)
        where piri.purchase_invoice_request_id = '{$purchase_invoice_request_id}' and piri.return_inventory_transaction_id <> '' 
        ";
$added_item_list_gt = $db->getAll($sql_added_item_gt);

$added_item_list = array_merge($added_item_list_purchase,$added_item_list_gt);
$added_item_list = add_iteration($added_item_list);



//删除已添加的商品明细
if($act == "purchase_invoice_request_item_delete"){
    $index_list = $_REQUEST['index_delete'];
    if(is_array($index_list)){
        foreach ($index_list as $index) {
            $product_id = $_REQUEST["product_id_{$index}"];
            $total_cost = $_REQUEST["total_cost_{$index}"];
            $total_net_cost = $_REQUEST["total_net_cost_{$index}"];
            $total_tax = $_REQUEST["total_tax_{$index}"];
            $return_inventory_transaction_id = $_REQUEST["return_inventory_transaction_id_{$index}"];
            $inventory_transaction_id = $_REQUEST["inventory_transaction_id_{$index}"];
            $purchase_invoice_request_item_id = $_REQUEST["purchase_invoice_request_item_id_{$index}"];
            $unit_cost = $_REQUEST["unit_cost_{$index}"];
            //只有-gt
            if(!empty($return_inventory_transaction_id) && empty($inventory_transaction_id)){
                $sql = "select 1 from romeo.purchase_invoice_request_item where purchase_invoice_request_item_id = '{$purchase_invoice_request_item_id}' 
                ";
                $res_confirm = $db->getAll($sql);
                $total_tax = $total_cost - $total_net_cost;
                $sql_detele = "delete from romeo.purchase_invoice_request_item 
                where purchase_invoice_request_item_id = '{$purchase_invoice_request_item_id}'  
                ";
                if(!empty($res_confirm)){
                    $sql_update = "update romeo.purchase_invoice_request set last_update_stamp = now(),last_update_tx_stamp = now(),total_cost = total_cost + {$total_cost},total_net_cost = total_net_cost + {$total_net_cost},
                        total_tax = total_tax + {$total_tax}
                        where purchase_invoice_request_id = '{$purchase_invoice_request_id}'
                        ";
                }else{
                    $sql_update = "update romeo.purchase_invoice_request set last_update_stamp = now(),last_update_tx_stamp = now(),total_cost = total_cost,total_net_cost = total_net_cost,
                        total_tax = total_tax  
                        where purchase_invoice_request_id = '{$purchase_invoice_request_id}' 
                        ";
                }
            }elseif(!empty($return_inventory_transaction_id) && !empty($inventory_transaction_id)){ //老页面留下的坑，数据库记录前部分为-c后部分为-gt
                $total_tax = $total_cost - $total_net_cost;
                $sql_detele = "update romeo.purchase_invoice_request_item set return_inventory_transaction_id = null,return_amount = null,return_quantity = null,return_inventory_item_detail_id = null 
                where purchase_invoice_request_item_id = '{$purchase_invoice_request_item_id}' 
                ";
                $sql = "select 1 from romeo.purchase_invoice_request_item where purchase_invoice_request_item_id = '{$purchase_invoice_request_item_id}'
                ";
                $res_confirm = $db->getAll($sql);
                if(!empty($res_confirm)){
                    $sql_update = "update romeo.purchase_invoice_request set last_update_stamp = now(),last_update_tx_stamp = now(),total_cost = total_cost + {$total_cost},total_net_cost = total_net_cost + {$total_net_cost},
                        total_tax = total_tax + {$total_tax}
                        where purchase_invoice_request_id = '{$purchase_invoice_request_id}'
                        ";
                }else{
                    $sql_update = "update romeo.purchase_invoice_request set last_update_stamp = now(),last_update_tx_stamp = now(),total_cost = total_cost,total_net_cost = total_net_cost,
                        total_tax = total_tax  
                        where purchase_invoice_request_id = '{$purchase_invoice_request_id}' 
                        ";
                }
            }else{//只有-c
                $sql_detele = "delete from romeo.purchase_invoice_request_item
                where purchase_invoice_request_id = '{$purchase_invoice_request_id}' and product_id = '{$product_id}' and return_inventory_item_detail_id is null and unit_cost = '{$unit_cost}' 
                ";
                $sql_update = "update romeo.purchase_invoice_request set last_update_stamp = now(),last_update_tx_stamp = now(),total_cost = total_cost - {$total_cost},total_net_cost = total_net_cost - {$total_net_cost},
                total_tax = total_tax - {$total_tax}
                where purchase_invoice_request_id = '{$purchase_invoice_request_id}'
                ";
            }
            Qlog::log("删除语句：".$sql_detele);
            Qlog::log("更新语句：".$sql_update);
            $result1 = $db->query($sql_detele);
            $result2 = $db->query($sql_update);
            if($result1 && $result2){
                $info = "操作成功！";
            }else{
                $result = "操作失败！";
                break;
            }
        }
    }else{
       die("无商品选中！"); 
    }
    $back = $_POST['back'] ? $_POST['back'] : "{$WEB_ROOT}admin/purchase_invoice/purchase_invoice_request_detail_new.php?purchase_invoice_request_id={$purchase_invoice_request_id}";
    alert_back($info, $back);
}

//处理异常发票清单号
if(empty($purchase_invoice_request)){
    die("发票清单号不正确！");
}
$smarty->assign("purchase_invoice_request",$purchase_invoice_request);
$smarty->assign("added_item_list",$added_item_list);
$smarty->assign('facility_list', facility_list());
$smarty->assign("start_date",$start_date);
$smarty->assign("end_date",$end_date);
//导出csv
$csv = $_REQUEST['csv'];
if ($csv == "csv") {
    header("Content-type:application/vnd.ms-excel");
    header("Content-Disposition:filename=" . iconv("UTF-8","GB18030", get_provider_name($provider_id) . "开票清单") . ".csv");
    $out = $smarty->fetch('oukooext/purchase_invoice/purchase_invoice_request_detail_csv_new.htm');
    echo iconv("UTF-8","GB18030", $out);
    exit();
} elseif ($csv == "搜索结果导出csv") {
    header("Content-type:application/vnd.ms-excel");
    header("Content-Disposition:filename=" . iconv("UTF-8","GB18030", get_provider_name($provider_id) . "搜索结果") . ".csv");
    $out = $smarty->fetch('oukooext/purchase_invoice/purchase_invoice_request_detail_search_csv_new.htm');
    echo iconv("UTF-8","GB18030", $out);
    exit();
}else{
    $smarty->display("oukooext/purchase_invoice/purchase_invoice_request_detail_new.htm");
}

/*
 * 检测开票清单能否初始化
 */
function check_has_invoice($request_id){
    global $db;
    $sql = "
    select 1
    from romeo.purchase_invoice_request
    where purchase_invoice_request_id = '{$request_id}'
    ";
    $item = $db -> getOne($sql);
    if($item){
        $sql = "
        select ir.status as request_status,ir.purchase_invoice_request_id,i.status as invoice_status,i.invoice_no
        from romeo.purchase_invoice_request ir
        left join romeo.purchase_invoice_request_item iri on iri.purchase_invoice_request_id = ir.PURCHASE_INVOICE_REQUEST_ID
        left join romeo.purchase_invoice_item_match im on im.purchase_invoice_request_item_id = iri.PURCHASE_INVOICE_REQUEST_ITEM_ID
        left join romeo.purchase_invoice_item ii on ii.purchase_invoice_item_id = im.PURCHASE_INVOICE_ITEM_ID
        left join romeo.purchase_invoice i on i.purchase_invoice_id = ii.purchase_invoice_id
        where ir.purchase_invoice_request_id = '{$request_id}' and i.status is not null
    limit 1
        ";
		$item = $db -> getRow($sql);
		if($item){
			if($item['invoice_status'] == 'INIT'){
				$message = "该开票清单已有发票关联，请先删除关联再做操作";
			}else if($item['invoice_status'] == 'CONFIRM' || $item['invoice_status'] == 'CLOSE'){
		$message = "该开票清单已有发票关联，并且进行了已审或者已复审操作，无法再进行更改";
		}
		}
}else{
$message = "无法找该发票清单{$request_id},请联系erp";
}
if(isset($message)){
    $result['message'] = $message;
    $result['success'] = 0;
	}else{
		$result['success'] = 1;
	}
	return $result;
}

?>