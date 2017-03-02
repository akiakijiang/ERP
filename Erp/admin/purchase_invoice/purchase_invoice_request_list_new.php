<?php
/**
 * 开票清单列表新页面，可以进行搜索
 */
define('IN_ECS', true);
require('../includes/init.php');
require('purchase_invoice_request.php');
admin_priv("cw_review_purchase_invoice", "cw_review_purchase_invoice_plus", "cg_edit_purchase_invoice", "cg_purchase_list");
require(ROOT_PATH . 'admin/function.php');
require_once(ROOT_PATH . "RomeoApi/lib_inventory.php");
require_once(ROOT_PATH . 'includes/cls_page.php');
$act = $_REQUEST['act'];
//查询
if($act == 'search'){
    $invoice_request_id = $_REQUEST['invoice_request_id'];
    $provider_name = $_REQUEST['provider_name'];
    $provider_id = $_REQUEST['provider_id'];
    $start_time = $_REQUEST['start_date'];
    $end_time = $_REQUEST['end_date'];
    $status = $_REQUEST['status'];
    $sql_add = ' where';
    if(empty($start_time)){
        $start_time = date ( "Y-m-d", strtotime('-3 Months',time()));
        $sql_add .= " pir.created_stamp >= '{$start_time}' ";
    }else{
        $sql_add .= " pir.created_stamp >= '{$start_time}' ";
    }
    if(!empty($end_time)){
        $sql_add .= " and pir.created_stamp <= '{$end_time}' ";
    }
    if(!empty($invoice_request_id)){
        $sql_add .= " and pir.purchase_invoice_request_id = '{$invoice_request_id}' ";
    }
    if(!empty($provider_id)){
        $sql_add .= " and pir.supplier_id = '{$provider_id}' ";
    }
    if(!empty($status)){
        $sql_add .=" and pir.status = '{$status}' ";
    }
    
    //查找条数
    $sql_c = "select count(1)
        from romeo.purchase_invoice_request pir
        left join ecshop.ecs_provider p on p.provider_id = pir.supplier_id".$sql_add.
        "order by pir.created_stamp desc
        ";
    $res = $db->getAll($sql_c);
    $total = $res[0]['count(1)'];
    $page_size = 20;  //每页显示条数
    $total_page = ceil($total/$page_size);  // 总页数
    $page =  // 当前页码
    is_numeric($_REQUEST['page']) && ($_REQUEST['page'] > 0)
    ? $_REQUEST['page']
    : 1 ;
    if ($page > $total_page) $page = $total_page;
    if ($page < 1) $page = 1;
    $offset = ($page - 1) * $page_size;
    $limit = $page_size;
    $extra_params['provider_id']=$_REQUEST['provider_id'];
    //查找开票清单明细
    $sql = "select pir.purchase_invoice_request_id,p.provider_name,pir.created_stamp,pir.created_user,pir.status,pir.total_net_cost,total_cost
        from romeo.purchase_invoice_request pir
        left join ecshop.ecs_provider p on p.provider_id = pir.supplier_id".$sql_add.
        "order by pir.created_stamp desc 
        limit $limit offset $offset 
        ";
    $purchase_invoice_request_list = $db->getAll($sql);
    $smarty->assign('purchase_invoice_request_list', $purchase_invoice_request_list);
    $pagination = new Pagination(
        $total, $page_size, $page, 'page', $url = 'purchase_invoice_request_list_new.php?act=search&start_date='.$start_time.'&end_date='.$end_time, null, $extra_params
    );
    $smarty->assign('pagination', $pagination->get_simple_output());  // 分页   
    $smarty->assign('start_time',$start_time); //开始时间
    $smarty->assign('end_time',$end_time);//结束时间
    $smarty->assign('provider_name',$provider_name);
}
//新建开票清单
if($act == 'purchase_invoice_request_add'){
    $user_name = $_SESSION['admin_name'];
    $provider_id = $_REQUEST['provider_id'];
    $provider_code = get_provider_code($provider_id);
    $note = $_REQUEST['note'];
    $date= date('Ymd',time());
    $number = get_sequence_number($date);
    $purchase_invoice_request_id = $provider_code . $date . $number;
    $sql_request_add = "insert into romeo.purchase_invoice_request(purchase_invoice_request_id,created_stamp,last_update_stamp,
    last_update_tx_stamp,created_tx_stamp,sequence_number,note,status,start_time,supplier_id,total_cost,total_net_cost,total_tax,
    type_id,created_user,end_time)
    values('{$purchase_invoice_request_id}',now(),now(),now(),null,'{$number}','{$note}','INIT',null,'{$provider_id}',0,0,0,'AVERAGE',
    '{$user_name}',null)
        ";
    $db->query($sql_request_add);
    $back = "{$WEB_ROOT}admin/purchase_invoice/purchase_invoice_request_detail_new.php?purchase_invoice_request_id={$purchase_invoice_request_id}";
    print("<script type='text/javascript'>alert('操作成功！');var tempwindow=window.open('_blank');tempwindow.location='{$back}';</script>");
}
//开票清单明细导出CSV
$purchase_invoice_request_id_excel = trim($_POST['purchase_invoice_request_id_excel']);
$provider_name = trim($_POST['provider_name']);
if ($purchase_invoice_request_id_excel) {
     
    $sql="
    select
    oi.order_sn,go.goods_sn,go.barcode,og.goods_name,re.quantity,re.unit_cost,re.unit_net_cost
    from romeo.purchase_invoice_request_item as re
    left join romeo.inventory_item_detail as de on de.inventory_transaction_id = re.inventory_transaction_id
    left join ecshop.ecs_order_goods as og on og.order_id = de.order_id 
    left join ecshop.ecs_provider as pr on og.provider_id = pr.provider_id
    left join ecshop.ecs_order_info as oi on de.order_id = oi.order_id
    left join ecshop.ecs_goods as go on go.goods_id = og.goods_id
    where re.purchase_invoice_request_id = '$purchase_invoice_request_id_excel'
    order by og.goods_name ";

    $purchase_result = $db->getAll($sql);
    $purchase_invoice_details = wrap_object_to_array($purchase_result);

    $smarty->assign('purchase_invoice_request_id_excel',$purchase_invoice_request_id_excel);
    $smarty->assign('provider_name',$provider_name);
    $smarty->assign('purchase_invoice_details',$purchase_invoice_details);
    header ( "Content-type:application/vnd.ms-excel" );
    header ( "Content-Disposition:filename=" . iconv ( "UTF-8", "GB18030", $provider_name . "开票清单明细" ) . ".csv" );
    $out = $smarty->fetch ( 'oukooext/purchase_invoice/purchase_invoice_request_list_csv.htm' );
    echo iconv ( "UTF-8", "GB18030", $out );
    exit ();
}

$smarty->display('oukooext/purchase_invoice/purchase_invoice_request_list_new.htm');

?>