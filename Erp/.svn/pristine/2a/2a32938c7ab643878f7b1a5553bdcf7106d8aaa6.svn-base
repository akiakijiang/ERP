<?php
/**
ALL HAIL SINRI EDOGAWA!
我らをこころみにあわせず、悪より救いいだしたまえ。
**/

//【售后处理总表】、【店长】、【客服】、【物流】、【财务】界面，设置对应的查看权限

$debug_time_list=array();
$debug_time_list[0]=microtime(true);

$db_time=array();

define('IN_ECS', true);
require_once('../includes/init.php');
//admin_priv('kf_order_entry');//admin_priv('xxx','', false) 
require_once(ROOT_PATH . 'includes/lib_order.php');
require_once(ROOT_PATH . 'includes/cls_json.php');
include_once(ROOT_PATH . 'admin/function.php'); 
// include_once(ROOT_PATH . 'admin/includes/lib_order_mixed_status.php');//By Sinri
require_once(ROOT_PATH . 'includes/helper/array.php');

require_once('postsale_function_cached.php');

if(!isDevPrivUser($_SESSION['admin_name'])){
	admin_priv(
		'lcz_sale_support',
		'kf_postsale_support',
		'bjwl_sale_support',
		'shwl_sale_support',
		'dgwl_sale_support',
		'wbwl_sale_support',
		'whwl_sale_support',
		'cdwl_sale_support',
		'jpwl_sale_support',
		'szwl_sale_support',
		'cw_sale_support',
		'dz_sale_support',
		'cg_sale_support',
		'kf_postsale_support_fenxiao'
	);
}

global $user_priv_list;

// 退款状态
$refund_status_name=array(
	'RFND_STTS_INIT'=>"已生成",
	'RFND_STTS_IN_CHECK'=>"处理中",
	'RFND_STTS_CHECK_OK'=>"已审毕",
	'RFND_STTS_EXECUTED'=>"已完成",
	'RFND_STTS_CANCELED'=>"已取消"
);

$message_to_see = array(
	'viewer' => array("KF","DGWL","SHWL","WHWL","CDWL","JPWL","SZWL","CW","DZ"), 
	'shop' => array("DZ"),
	'postsale' => array("KF"),
	'logistics' => array("DGWL","SHWL","WBWL","BJWL","WHWL","CDWL","JPWL","SZWL"),
	'finance' => array("CW"),
	'cg' => array("CG")
);

$get_sync_taobao_refund_state_map = array(
	'SELLER_REFUSE_BUYER' => '已拒绝',
	'WAIT_SELLER_CONFIRM_GOODS' => '等待验货',
	'CLOSED' => '已关闭',
	'SUCCESS' => '已成功',
	'WAIT_SELLER_AGREE' => '等待审核',
	'WAIT_BUYER_RETURN_GOODS' => '等待退货'
);

$debug_time_list[1]=microtime(true);

//print_r($_SESSION);
//print_r($_REQUEST);

$as_role=isset($_REQUEST['as_role'])?$_REQUEST['as_role']:"bypriv";//viewer//shop//postsale//logistics//finance//cg
$use_role=isset($_REQUEST['use_role'])?$_REQUEST['use_role']:$as_role;//shop//postsale//logistics//finance//cg

$act=(isset($_REQUEST['act']))?trim($_REQUEST['act']):"neet";//search//neet

$tasks=(isset($_REQUEST['tasks']))?$_REQUEST['tasks']:array('grouped');
if(!is_array($tasks))$tasks=array($tasks);
//pp($tasks);
if(!isDevPrivUser($_SESSION['admin_name'])) {//开发权限
	switch ($use_role) {
		case 'bypriv':
			if(check_admin_priv($user_priv_list['LCZ']['priv'])){
				$as_role="viewer";
				$use_role=$as_role;
				break;
			}
			if(check_admin_priv($user_priv_list['KF']['priv'],$user_priv_list['FXKF']['priv'])){
				$as_role="postsale";
				$use_role=$as_role;
			}
			if(check_admin_priv($user_priv_list['BJWL']['priv'])){
				$as_role="logistics";
				$use_role=$as_role;
			}
			if(check_admin_priv($user_priv_list['SHWL']['priv'])){
				$as_role="logistics";
				$use_role=$as_role;
			}
			if(check_admin_priv($user_priv_list['DGWL']['priv'])){
				$as_role="logistics";
				$use_role=$as_role;
			}
			if(check_admin_priv($user_priv_list['WBWL']['priv'])){
				$as_role="logistics";
				$use_role=$as_role;
			}
			if(check_admin_priv($user_priv_list['WHWL']['priv'])){
				$as_role="logistics";
				$use_role=$as_role;
			}
			if(check_admin_priv($user_priv_list['CDWL']['priv'])){
				$as_role="logistics";
				$use_role=$as_role;
			}
			if(check_admin_priv($user_priv_list['JPWL']['priv'])){
				$as_role="logistics";
				$use_role=$as_role;
			}
			if(check_admin_priv($user_priv_list['SZWL']['priv'])){
				$as_role="logistics";
				$use_role=$as_role;
			}
			if(check_admin_priv($user_priv_list['CW']['priv'])){
				$as_role="finance";
				$use_role=$as_role;
			}
			if(check_admin_priv($user_priv_list['DZ']['priv'])){
				$as_role="shop";
				$use_role=$as_role;
			}
			if(check_admin_priv($user_priv_list['CG']['priv'])){
				$as_role="cg";
				$use_role=$as_role;
			}
			if($use_role=='bypriv'){//这种情况不可能- -
				die("獅子の巫女たる高神の剣巫が願い奉る 破魔の曙光 雪霞の神狼 鋼の神威をもちて 我に悪神百鬼討たせ給え！");
			}
			break;
		case 'viewer':
			//admin_priv($user_priv_list['LCZ']['priv']);//秀丽要求开放总表给所有角色
			break;
		case 'shop':
			admin_priv($user_priv_list['DZ']['priv']);
			break;
		case 'postsale':
			admin_priv($user_priv_list['KF']['priv'],$user_priv_list['FXKF']['priv']);
			break;
		case 'logistics':
			admin_priv($user_priv_list['BJWL']['priv'],$user_priv_list['SHWL']['priv'],$user_priv_list['DGWL']['priv'],$user_priv_list['WBWL']['priv'],
						$user_priv_list['WHWL']['priv'],$user_priv_list['CDWL']['priv'],$user_priv_list['JPWL']['priv'],$user_priv_list['SZWL']['priv']);
			break;
		case 'finance':
			admin_priv($user_priv_list['CW']['priv']);
			break;
		case 'cg':
			admin_priv($user_priv_list['CG']['priv']);
			break;
		default:
			die("獅子の巫女たる高神の剣巫が願い奉る 破魔の曙光 雪霞の神狼 鋼の神威をもちて 我に悪神百鬼討たせ給え！");
			break;
	}
} else {
	if($use_role=='bypriv')$as_role=$use_role="viewer";
}

$is_soon_or_pending=(isset($_REQUEST['is_soon_or_pending']) && trim($_REQUEST['is_soon_or_pending'])=='is_pending')?"is_pending":"is_soon";

$search_text=isset($_REQUEST['search_text'])?trim($_REQUEST['search_text']):"";
$search_type=isset($_REQUEST['search_type'])?trim($_REQUEST['search_type']):2;
$search_date_start=isset($_REQUEST['search_date_start'])?trim($_REQUEST['search_date_start']):"";
$search_date_end=isset($_REQUEST['search_date_end'])?trim($_REQUEST['search_date_end']):"";
$search_page=isset($_REQUEST['page'])?trim($_REQUEST['page']):"1";

$last_word_keyword=isset($_REQUEST['last_word_keyword'])?trim($_REQUEST['last_word_keyword']):'';

$search_mode=isset($_REQUEST['search_mode'])?trim($_REQUEST['search_mode']):0;
$search_distributor=isset($_REQUEST['distributor'])?trim($_REQUEST['distributor']):'-1';
$search_dist=isset($_REQUEST['dist_type'])?trim($_REQUEST['dist_type']):'zhixiao';
if($use_role=='logistics' || $use_role=='cg'){
	$search_dist='all';
}

$search_refund_type=isset($_REQUEST['refund_type'])?trim($_REQUEST['refund_type']):'all';

$search_in_party=isset($_REQUEST['search_in_party'])?$_REQUEST['search_in_party']:1;
$search_party=$search_in_party?$_SESSION['party_id']:0;

//$counts=get_count_of_duties_for_each_roles($search_party,$search_dist);
//$counts_refund=get_count_of_refunding_for_each_roles($search_party,$search_dist);

$page_item_limit=isset($_REQUEST['page_item_limit'])?intval($_REQUEST['page_item_limit']):50;
//die("- -".$page_item_limit);
if($page_item_limit<1 || $page_item_limit>500) $page_item_limit=50;

//$count_sync_refund=count_sync_taobao_refund_waiting_lines($search_party,$search_dist);

//Here ture kills NEET
if(true || $act=="search"){
	//grouped
	if(in_array('grouped', $tasks)){
		$misaka_mikoto=array();
		//$party_ids=get_party_with_all_children($search_party);
		//print_r($party_ids);
		//foreach ($party_ids as $no => $pid) {
			//CONDITION
			$the_plus_condition=array();
			//$the_plus_condition['party_id']=$pid;//$search_party;
			$the_plus_condition['dist']=$search_dist;
			$the_plus_condition['distributor']=$search_distributor;
			$the_plus_condition['refund_type']=$search_refund_type;
			$the_plus_condition['OFFSET']=$search_page-1;
			$the_plus_condition['LIMIT']=$page_item_limit;
			$the_plus_condition['is_soon_or_pending']=$is_soon_or_pending;
			if($search_text=="" && $search_date_start=="" && $search_date_end==""){
				//NO OTHER CONDITIONS
			} else{
				if($search_text!=""){
					switch ($search_type) {
						case '0':
							$the_plus_condition['order_sn']=$search_text; //$conditions_message.=" AND o.order_sn='$search_text' ";
							break;
						case '1':
							$the_plus_condition['order_id']=$search_text; //$conditions_message.=" AND o.order_id='$search_text' ";
							break;
						case '2':
							$the_plus_condition['taobao_order_sn']=$search_text; //$conditions_tr.=" AND str.tid like '$search_text%' ";
							break;
						case '3':
							$the_plus_condition['taobao_refund_id']=$search_text; //$conditions_tr.=" AND str.refund_id = '$search_text' ";
							break;
						case '4':
							$the_plus_condition['taobao_buyer_nick']=$search_text; //$conditions_tr.=" AND str.buyer_nick like '%$search_text%' ";
							break;
						case '5':
							$the_plus_condition['buyer_name']=$search_text; //$conditions.=" AND (s.apply_username='$search_text' OR o.consignee='$search_text') ";
							break;
						case '6':
							$the_plus_condition['mobile']=$search_text; //$conditions.=" AND o.mobile='$search_text' ";
							break;
						case '7':
							$the_plus_condition['track_number']=$search_text; //$conditions.=" AND cb.bill_no='$search_text' ";
							break;
						case '8':
							$the_plus_condition['return_track_number']=$search_text; //$conditions_tr.=" AND str.sid = '$search_text' ";
							break;
					}
				}
				if($search_date_start!=""){
					$the_plus_condition['date_start']=$search_date_start; //$conditions_tr.=" AND str.created>='$search_date_start' ";
				}
				if($search_date_end!=""){
					$the_plus_condition['date_end']=$search_date_end; //$conditions_tr.=" AND str.created<='$search_date_end' ";
				}
			}
			$the_plus_condition['mode']=$search_mode;

			if(!empty($last_word_keyword)){
				$the_plus_condition['last_word_keyword']=$last_word_keyword;
			}

			$debug_time_list[2]=microtime(true);

			//$misaka_mikoto_t=only_my_railgun($the_plus_condition,$search_page-1,$use_role);
			$misaka_mikoto=Index_Librorum_Prohibitorum($the_plus_condition,$search_page-1,$use_role,$countArray,$db_time);
			//print_r($misaka_mikoto_t);
			//$misaka_mikoto=array_merge($misaka_mikoto,$misaka_mikoto_t);
			//foreach ($misaka_mikoto_t as $tbsn => $line) {
				//$misaka_mikoto[$tbsn]=$line;
			//}
		//}
		//print_r($misaka_mikoto);
		if($misaka_mikoto){
			$available_pages_1=floor((($countArray['services'])/$page_item_limit)+1);
			$available_pages_2=floor((($countArray['refunds'])/$page_item_limit)+1);
			$available_pages_3=floor((($countArray['messages'])/$page_item_limit)+1);
			$available_pages=max($available_pages_1,$available_pages_2,$available_pages_3);
		}else {
			$available_pages=0;
		}
		//$misaka_mikoto = level_5_judgement_light($misaka_mikoto,$search_page-1,$page_item_limit);
	}
}

//导出EXCEL
set_include_path(get_include_path() . PATH_SEPARATOR . '../includes/Classes/');
require 'PHPExcel.php';
require 'PHPExcel/IOFactory.php';
$i = 0;
foreach ($misaka_mikoto as $key => $value) {
    $medium_arr = $misaka_mikoto[$key]['orders'];
    foreach($medium_arr as $key1 => $value1){
	   $medium_arr1 = $medium_arr[$key1];
	   foreach($medium_arr1 as $key2 => $value2){
	       if(!empty($medium_arr1['refunds'])){
	           $export_content[$i]['taobao_order_sn'] = $key;
	           $export_content[$i]['order_sn'] = $medium_arr[$key1]['order_sn'];
	           $medium_arr2 = current($medium_arr1['refunds']);
	           $export_content[$i]['类型'] = $medium_arr2['类型'];
	           $export_content[$i]['状态'] = $medium_arr2['状态'];
	           $export_content[$i]['金额'] = $medium_arr2['金额'];
	           $export_content[$i]['顾客'] = $medium_arr2['顾客'];
	           $export_content[$i]['时间'] = $medium_arr2['时间'];
	           $export_content[$i]['待办'] = $medium_arr2['待办'];
	           $export_content[$i]['备注'] = $medium_arr2['备注'];
	           $i ++;
	       }
	       if(!empty($medium_arr1['services'])){
	           $export_content[$i]['taobao_order_sn'] = $key;
	           $export_content[$i]['order_sn'] = $medium_arr[$key1]['order_sn'];
	           $medium_arr3 = current($medium_arr1['services']);
	           $export_content[$i]['类型'] = $medium_arr3['类型'];
	           $export_content[$i]['状态'] = $medium_arr3['状态'];
	           $export_content[$i]['金额'] = $medium_arr3['金额'];
	           $export_content[$i]['顾客'] = $medium_arr3['顾客'];
	           $export_content[$i]['时间'] = $medium_arr3['时间'];
	           $export_content[$i]['待办'] = $medium_arr3['待办'];
	           $export_content[$i]['备注'] = $medium_arr3['备注'];
	           $i ++;
	       }
	   }
    }
}

$export = $_REQUEST['export'];
if($export == '1'){

	$title = array(0 => array(
        '外部订单号', 'ERP订单', '类型', '状态', '金额',
        '顾客', '时间', '待办', '备注'
    ));
    $type = array(0 => 'string',);
    $filename .= "售后处理信息".".xlsx";
    $excel = new PHPExcel();
    $sheet = $excel->getActiveSheet();
    $sheet->fromArray($title);
    $export_content = array_map('array_values', $export_content);
    if (!empty($export_content)) {
        $sheet->fromArray($export_content, null, 1, $type);
    }
    $output = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
    $output->setOffice2003Compatibility(true);
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    $output->save('php://output');

    exit();
}

$s_type=$search_type;
$s_page=$search_page;
$s_ds=$search_date_start;
$s_de=$search_date_end;
$s_text=$search_text;
$s_dist=$search_dist;
$s_distributor=$search_distributor;
$s_refund_type=$search_refund_type;
$s_in_party=$search_in_party;
$s_mode=$search_mode;
$debug_time_list[3]=microtime(true);

/**
HTML IS BENEATHE
**/
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>售后处理中心</title>
		<script type="text/javascript" src="../js/style/zapatec/utils/zapatec.js"></script>
		<script type="text/javascript" src="../js/style/zapatec/zpcal/src/calendar.js"></script>
		<script type="text/javascript" src="../js/style/zapatec/zpcal/lang/calendar-en.js"></script>
		<script type="text/javascript" src="../misc/jquery.js"></script>
		<script type="text/javascript" src="../misc/jquery.ajaxQueue.js"></script>
		<script type="text/javascript">
		function doAJAX(method,url,isAsync){
			var xmlhttp;
			if (url.length==0){
				document.getElementById("order_info_box").innerHTML="";
				return;
			}
			if (window.XMLHttpRequest){// code for IE7+, Firefox, Chrome, Opera, Safari
			  	xmlhttp=new XMLHttpRequest();
			} else {// code for IE6, IE5
			  	xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
			}
			xmlhttp.onreadystatechange=function(){
			  	if (xmlhttp.readyState==4 && xmlhttp.status==200){
			    	document.getElementById("order_info_box").innerHTML=xmlhttp.responseText;
			    }else{
			    	document.getElementById("order_info_box").innerHTML="ERROR rS="+xmlhttp.readyState+" hS="+xmlhttp.status;
			    }
			}
			//document.getElementById("p_progress").innerHTML="Go "+method+" For "+url+" is isAsync="+(isAsync?"YES":"NO")+"<br>";
			//xmlhttp.addEventListener("progress", updateProgress, false);
			//xmlhttp.addEventListener("load", transferComplete, false);
			//xmlhttp.addEventListener("error", transferFailed, false);
			//xmlhttp.addEventListener("abort", transferCanceled, false);
			xmlhttp.open(method,url,isAsync);
			xmlhttp.send();
		}

		// progress on transfers from the server to the client (downloads)
		function updateProgress (oEvent) {
		  if (oEvent.lengthComputable) {
		    var percentComplete = oEvent.loaded / oEvent.total;
		    document.getElementById("p_progress").innerHTML+="PCT:"+percentComplete+"=" + oEvent.loaded +"/" +oEvent.total+"<br>";
		  } else {
		    // Unable to compute progress information since the total size is unknown
		  }
		}

		function transferComplete(evt) {
		  document.getElementById("p_progress").innerHTML+=("The transfer is complete.<br>");
		}

		function transferFailed(evt) {
		  document.getElementById("p_progress").innerHTML+=("An error occurred while transferring the file.<br>");
		}

		function transferCanceled(evt) {
		  document.getElementById("p_progress").innerHTML+=("The transfer has been canceled by the user.<br>");
		}

		</script>

		<script type="text/javascript">
		
		function con_type_check(){
			var ist=0;
			if(document.getElementById('taskradio1').checked)ist=1;
			if(document.getElementById('taskradio2').checked)ist=2;
			if(document.getElementById('taskradio3').checked)ist=3;
			if(document.getElementById('taskradio4').checked)ist=4;
			var ntype=document.getElementById('search_type').value;
			switch (ist){
				case 1:
				document.getElementById('st0').disabled=true;
				document.getElementById('st2').disabled=false;
				document.getElementById('st5').disabled=true;
				document.getElementById('st7').disabled=true;
				document.getElementById('st8').disabled=false;
				document.getElementById('st3').disabled=false;
				document.getElementById('st4').disabled=false;
				if(ntype==0 || ntype==7) document.getElementById('search_type').value=2;
				break;
				case 4:
				document.getElementById('st0').disabled=false;
				document.getElementById('st2').disabled=false;
				document.getElementById('st5').disabled=false;
				document.getElementById('st7').disabled=false;
				document.getElementById('st8').disabled=false;
				document.getElementById('st3').disabled=false;
				document.getElementById('st4').disabled=false;
				break;
				case 2:
				document.getElementById('st0').disabled=false;
				document.getElementById('st2').disabled=false;
				document.getElementById('st5').disabled=true;
				document.getElementById('st7').disabled=true;
				document.getElementById('st8').disabled=true;
				document.getElementById('st3').disabled=true;
				document.getElementById('st4').disabled=true;
				if(ntype==5 || ntype==7 || ntype==8 || ntype==3 || ntype==4) document.getElementById('search_type').value=2;
				break;
				case 3:
				document.getElementById('st0').disabled=false;
				document.getElementById('st2').disabled=false;
				document.getElementById('st5').disabled=false;
				document.getElementById('st7').disabled=false;
				document.getElementById('st8').disabled=true;
				document.getElementById('st3').disabled=true;
				document.getElementById('st4').disabled=false;
				if(ntype==8 || ntype==3) document.getElementById('search_type').value=2;
				break;
			}
		}
		function show_key_order_info_box(order_id){
			//showModelessDialog('sinri_sale_support_test.php?order_id='+order_id,'example04','dialogWidth:400px;dialogHeight:300px;dialogLeft:200px;dialogTop:150px;center:yes;help:yes;resizable:yes;status:yes'); 
			document.getElementById('order_iframe').src="about:blank";
			document.getElementById('divMask').style.display='block';
			document.getElementById('order_iframe').src='postsale_order_info_box.php?order_id='+order_id;
		}

		var single_info_cell_setting=true;
		var opening_order_id=0;

		function switch_key_order_info_cell_box(order_id){
			var box_id='box_'+order_id;
			var box_iframe_id='order_'+order_id+'_iframe';
			var box_btn_id='box_btn_'+order_id;

			//alert('switch_key_order_info_cell_box'+order_id);
			
			if(document.getElementById(box_id).style.display=='none'){
				document.getElementById(box_iframe_id).src='postsale_order_info_box.php?order_id='+order_id;
				document.getElementById(box_id).style.display='table-cell';
				document.getElementById(box_btn_id).value='收起详细信息';
				if(single_info_cell_setting){
					if(opening_order_id!=0){
						switch_key_order_info_cell_box(opening_order_id);
					}
					opening_order_id=order_id;
				}
			}else{
				document.getElementById(box_id).style.display='none';
				document.getElementById(box_btn_id).value='展开详细信息';
				if(single_info_cell_setting){
					opening_order_id=0;
				}
			}
		}
		
		// 等待消费者寄回货物
		function update_logistic_service_note(service_id) {
		    if(!confirm('确认等待消费者寄回货物吗？')) {
		    	return false;
		    }
		    $('#cancel_wait').attr('disabled',true);
			var result = "";
	    	$.ajax({
	   		    async:false,
	            type: 'POST',
	            url: '../ajax.php?act=update_logistic_service_note',
	            data: 'action_type=5&service_id='+service_id,
	            dataType: 'json',
	            error: function() {	
	            	alert('ajax请求错误,update_logistic_service_note service_id:' + service_id); 
	            },
	            success: function(data) {
	               result = true;
	           	}
	        }); 
	        
	        if(result) {
		        alert('等待消费者寄回货物成功');
		        window.location.href="sale_support_center_cached.php?as_role=logistics&use_role=logistics";
	        }

		}
		//导出excel
		function export_to_excel(){
			alert("导出");
			document.excel_form.submit();
		}

		var msgIdArray=new Array();
		
		</script>
		<link rel="stylesheet" href="../js/style/zapatec/zpcal/themes/winter.css" />
		<style type="text/css">
		.div-dialog-mask{
		    background: #B6FFB5;
		   	border-style: inset;
		    z-index:1987; 
		    position: fixed; /*虽然IE6不支持fixed，这里依然可以兼容ie6*/
		    left: 10%; 
		    top: 15%; 
		    width: 80%; 
		    height: 70%; 
		    overflow: hidden;
		}
		/*ie6 遮罩select*/
		.div-dialog-mask iframe{
		    width:98%;
		    height:90%;
		    position:absolute;
		    top:5%;
		    left:1%;
		    z-index:-1;
		    border: none;
		}
		</style>
  		<style type="text/css">
  			p {
  				padding: 0px;
  				margin: 2px;
  			}
	  		table, td {
				border: 1px solid gray;
				border-collapse:collapse;
				font-size: 13px;
				text-align: center;
				/* padding: 5px; */
			}
			th {
				background-color: #2899D6;/* #6CB8FF; */
				color: #EEEEEE;
				border: 1px solid gray;
				border-collapse:collapse;
				padding: 5px;
			}

			.count_table table, td{
				border: 1px solid #EEEEEE;
				border-collapse:collapse;
				font-size: 13px;
				text-align: left;
			}

			span.keikoku{
				color: red;
			}


  			div.waku{
  				/* padding: 5px;*/
  				margin-bottom: 5px;
  			}
			table.detail_table {
				border: 1px solid gray;
				border-collapse:collapse;
			}
			table.detail_table td {
				border: 1px solid rgba(220,220,220,1);
				border-collapse:collapse;
				padding: 4px;
				text-align: center;
			}
			table.detail_table th {
				border: 1px solid rgba(220,220,220,1);
				border-collapse:collapse;
				padding: 4px;
				font-size: 15px;
				text-align: center;
			}

			div.tab_board {
				background-color: #EEEEEE;
				padding-top: 12px;
				padding-bottom: 8px;
				margin: 0px;
			}
			span.tab_on {
				background-color: #78E7FF;
				padding: 9px;
				/* border: 1px solid gray; */
				font-weight: bold;
			}
			span.tab_off {
				background-color: #6CB8FF;
				padding: 9px;
				/* border: 1px solid gray; */
				font-weight: bold;
			}
			a.tab:link {color:#FFFFFF;text-decoration: none;}		/* 未被访问的链接 */
			a.tab:visited {color:#FFFFFF;text-decoration: none;}	/* 已被访问的链接 */
			a.tab:hover {color:#770000;text-decoration: none;}	/* 鼠标指针移动到链接上 */
			a.tab:active {color:#FF0000;text-decoration: none;}	/* 正在被点击的链接 */

			a.type_a:link {color:#000000;text-decoration: none;}		/* 未被访问的链接 */
			a.type_a:visited {color:#000000;text-decoration: none;}	/* 已被访问的链接 */
			a.type_a:hover {color:#000000;text-decoration: none;}	/* 鼠标指针移动到链接上 */
			a.type_a:active {color:#000000;text-decoration: none;}	/* 正在被点击的链接 */

			p.captain {
				/* color: #5555EE; */
				font-size: 16px;
				padding: 0px;
				margin: 5px;
			}

			ul.tabnav li{
				float:left;
				display:inline;
				margin-left:2px;
			}
			.tabnav li a{
				background-color:#2899D6;
				border:2px solid #2899D6;
				color:#EEEEEE;
				display:block;
				padding:5px 10px 5px 10px;/* top right bottom left */
				line-height:20px;
				float:left;
				font-weight:bold;
				text-decoration: none;
			}

			.tabnav li a.active,
			.tabnav li a:hover{
				color: #2899D6;
				background-color:#fff;
				border-bottom:2px solid #fff;
				_position:relative;
				text-decoration: none;
			}
			#tab_roles .tabnav{
				border-bottom:2px solid #2899D6;
				height:32px;
				_overflow:hidden;
			}
			div.mynewtabs {
				margin-bottom: 0px;
			}
  		</style>
  	</head>
  	<body onload="con_type_check();">
  		<div id="tab_roles" class="mynewtabs">
			<ul class="tabnav nostyle">
				<li><a href="sale_support_center_cached.php?as_role=viewer&use_role=viewer" <?php if ($as_role=='viewer') { echo "class='active'";} ?>>售后处理总表</a></li>
				<li><a href="sale_support_center_cached.php?as_role=shop&use_role=shop" <?php if ($as_role=='shop') { echo "class='active'";} ?>>店长</a></li>
				<li><a href="sale_support_center_cached.php?as_role=postsale&use_role=postsale" <?php if ($as_role=='postsale') { echo "class='active'";} ?>>客服</a></li>
				<li><a href="sale_support_center_cached.php?as_role=logistics&use_role=logistics" <?php if ($as_role=='logistics') { echo "class='active'";} ?>>物流</a></li>
				<li><a href="sale_support_center_cached.php?as_role=finance&use_role=finance" <?php if ($as_role=='finance') { echo "class='active'";} ?>>财务</a></li>
				<li><a href="sale_support_center_cached.php?as_role=cg&use_role=cg" <?php if ($as_role=='cg') { echo "class='active'";} ?>>快递理赔客服</a></li>
			</ul>
		</div>
		<form  method="POST" id="sform"> <!-- action="sale_support_center_cached.php" -->
		<div class="waku" style="
				text-align: left; 
				margin-bottom: 10px; 
				margin-top: -10px;
				/*
				border: 1px solid gray;
				border-collapse:collapse;
				*/
				padding:5px;
				background-color: #EEEEEE;
				
			">
			<?php 
				echo "搜索到待处理任务 ".($countArray['services']+$countArray['refunds']+$countArray['messages'])." 件，包括退换货申请 ".
				$countArray['services']. " 件，退款申请 ".
				$countArray['refunds']." 件，售后沟通 ". 
				$countArray['messages']." 件";
			?>
		</div>
		<?php if(false && $as_role=="viewer") { ?>
			<div  class="waku" style="
				text-align: left; 
				margin-bottom: 10px; 
				margin-top: -10px;
				/*
				border: 1px solid gray;
				border-collapse:collapse;
				*/
				padding:5px;
				background-color: #EEEEEE;
				
			">
				<!--<p style="margin: 0px;font-size: 10px;">-->
				<table class="count_table">
					<tr>
						<td>
				<?php
				//if(in_array('taobao_refund', $tasks)){
					if($count_sync_refund){
						echo "淘宝退款统计 $count_sync_refund";
					}else{
						echo "淘宝退款统计 无";
					}
					echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
				//} 
				?>
						</td>
						<td>
				<?php
				//if(in_array('messages', $tasks)){
					if ($messages_count && is_array($messages_count)){
						echo "售后沟通统计 ".($messages_count['售后沟通统计'])." (";
						foreach ($messages_count as $key => $value) {
							if($key=='售后沟通统计')continue;
							echo "&nbsp;".$key." ".$value."&nbsp;";
						}
						echo ")";
					}else{
						echo "售后沟通统计 无";
					}
					echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
				//}
				?>
						</td>
					</tr>
					<tr>
						<td>
				<?php
				//if(in_array('service_and_refund', $tasks)){
					if($counts_refund){
						$is_first=true;
						foreach ($counts_refund as $n1 => $v1) {
							if($v1['name']=='客服'){
								echo "(";
									$is_first=false;
							}
				  			echo$v1['name']." ".$v1['value']."&nbsp;";
						}
						if($counts_refund)echo ")";
					}else{
						echo "退款申请统计 无";
					}
					
					echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
				//}
				?>
						</td>
						<td>
				<?php
				//if(in_array('service_and_refund', $tasks)){
					if($counts){
						$is_first=true;
						foreach ($counts as $n1 => $v1) {
							if($v1['name']=='客服'){
								echo "(";
									$is_first=false;
							}
							if(!$is_first)echo "&nbsp;";
					  		echo $v1['name']." ".$v1['value']."&nbsp;";
						}
						if($counts)echo ")";
					}else{
						echo "售后操作统计 无";
					}
				//}
				?>
						</td>
					</tr>	
					<tr>
						<td><?php echo "更新于".date("Y-m-d H:i",time());?></td>
						<td>当前组织为<?php echo get_party_name_by_id($_SESSION['party_id']); ?>，第<?php echo get_party_online_level(); ?>批上线。共<?php echo $available_pages;?>页
							<!--
							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
							//ERP调试信息：用户身份：<?php echo $use_role; ?>。
							-->
						</td>
					</tr>			
				</table>
			</div>
	  	<?php } ?>
  		<div> <!-- style="background-color: #DAFFDA;" -->
  			<div class="waku" style="text-align: left;<?php if(!isDevPrivUser($_SESSION['admin_name'])) echo "display: none;"; ?>">
				售后处理类别
				<input type='radio' name='tasks' value='grouped' id="taskradio4" onchange="con_type_check();"
					<?php if(in_array('grouped', $tasks)) echo " checked='checked' "; ?>
				><a href="#" class="type_a" onclick="document.getElementById('taskradio4').checked=true;con_type_check();">待处理售后</a>
				
  			</div>
	  		<div  class="waku" style="text-align: left;">
				<div id="non_mode_div" <?php if(false && $s_mode!=0) echo "style=\"display: none;\"" ?>>
					发起时间范围
					<input id="start" name="search_date_start" style="width:100px" value="<?php echo $s_ds; ?>" /> 
					到 <input id="end" name="search_date_end" style="width:100px" value="<?php echo $s_de; ?>" />
					&nbsp;&nbsp;
					<select name="search_type" id="search_type" style="width:102px"> 
						<option id="st0" value='0' <?php if ($s_type=='0') { echo "selected='selected' "; } ?> >ERP订单号</option>
						<!--
						<option id="st1" value='1' <?php if ($s_type=='1') { echo "selected='selected' "; } ?> >ERP订单ID</option>
						-->

						<option id="st2" value='2' <?php if ($s_type=='2') { echo "selected='selected' "; } ?> >淘宝订单号</option>

						<option id="st3" value='3' <?php if ($s_type=='3') { echo "selected='selected' "; } ?> >淘宝退款编号</option>
						
						
						<option id="st4" value='4' <?php if ($s_type=='4') { echo "selected='selected' "; } ?> >顾客ID(旺旺号)</option>
						
						<option id="st5" value='5' <?php if ($s_type=='5') { echo "selected='selected' "; } ?> >顾客名称</option>
						<!--
						<option id="st6" value='6' <?php if ($s_type=='6') { echo "selected='selected' "; } ?> >顾客手机号</option>
						-->
						<option id="st7" value='7' <?php if ($s_type=='7') { echo "selected='selected' "; } ?> >发货运单号</option>
						
						<option id="st8" value='8' <?php if ($s_type=='8') { echo "selected='selected' "; } ?> >退回运单号</option>
						
					</select>
					<input type="text" style="width:160px" name="search_text" value="<?php echo $s_text; ?>">
					<!-- 这下面的只有Chrome下面有效，壮哉我大CHROMIC HTML5
					起止时间
					<input type="date" name="search_date_start" value="<?php echo $s_ds; ?>">
					~
					<input type="date" name="search_date_end" value="<?php echo $s_de; ?>">
					-->
					&nbsp;&nbsp;
					<select name="dist_type" style="width:92px">
						<option value="all" 
						<?php if($use_role=='postsale' && !(check_admin_priv('kf_postsale_support') && check_admin_priv('kf_postsale_support_fenxiao'))) { ?>
						disabled='disabled'
						<?php } ?>
						 <?php if ($s_dist=='all') { echo "selected='selected' "; } ?> >分销和直销</option>

						<option value="fenxiao" 
						<?php if($use_role=='postsale' && !check_admin_priv('kf_postsale_support_fenxiao')) { ?>
						disabled='disabled'
						<?php } ?>
						 <?php if ($s_dist=='fenxiao') { echo "selected='selected' "; } ?> >分销</option>

						<option value="zhixiao"
						<?php if($use_role=='postsale' && !check_admin_priv('kf_postsale_support')) { ?>
						disabled='disabled'
						<?php } ?>
						 <?php if ($s_dist=='zhixiao') { echo "selected='selected' "; } ?> >直销</option>
					</select>
					<select name="distributor" style="width:182px">
						<option value="-1">所有店铺</option>
						<?php foreach (get_distributorlist($_SESSION['party_id']) as $item) { ?>					
						<option value="<?php echo $item['distributor_id']?>"
						<?php if($s_distributor == $item['distributor_id']) {echo "selected='selected'";} ?>
						>
						<?php echo $item['name'];}?></option>
					</select>
				</div>
				<div style="margin-top:6px;margin-left:30px;">
					关于手机天猫
					<select name="refund_type" style="width:92px">
						<option value="all" 
						 <?php if ($s_refund_type=='all') { echo "selected='selected' "; } ?> >所有</option>
						<option value="mobile" 
						 <?php if ($s_refund_type=='mobile') { echo "selected='selected' "; } ?> >手机天猫</option>
						<option value="notMoble"
						 <?php if ($s_refund_type=='notMoble') { echo "selected='selected' "; } ?> >非手机天猫</option>
					</select>
					
					<select name="search_mode" id="search_mode_select" style="width:182px">
						<option <?php if ($search_mode=='0') { echo "selected='selected' "; } ?> value="0">全部状态</option>
						<?php if($as_role=='viewer'){ ?>
						<optgroup label="巡查">
						<option <?php if ($search_mode=='21') { echo "selected='selected' "; } ?> value="21">无人负责的售后沟通</option>
						</optgroup>
						<?php }
						if($as_role=='viewer' || $as_role=='shop'){ ?>
						<optgroup label="店长">
						<option <?php if ($search_mode=='13') { echo "selected='selected' "; } ?> value="13">店长：处理售后沟通</option>
						</optgroup>
						<?php } 
						if($as_role=='viewer' || $as_role=='postsale') { ?>
						<optgroup label="客服">
						<option <?php if ($search_mode=='12') { echo "selected='selected' "; } ?> value="12">客服：处理售后沟通</option>
						<option <?php if ($search_mode=='2') { echo "selected='selected' "; } ?> value="2">客服：待审核的退货申请</option>
						<option <?php if ($search_mode=='3') { echo "selected='selected' "; } ?> value="3">客服：验货入库待确认退款</option>

						<option <?php if ($search_mode=='1') { echo "selected='selected' "; } ?> value="1">客服：待审核的换货申请</option>
						<option <?php if ($search_mode=='4') { echo "selected='selected' "; } ?> value="4">客服：验货入库待确认换货</option>

						<option <?php if ($search_mode=='19') { echo "selected='selected' "; } ?> value="19">客服：待审核的退款申请</option>

						<option <?php if ($search_mode=='10') { echo "selected='selected' "; } ?> value="10">客服：淘宝未审核退款申请</option>
						<option <?php if ($search_mode=='11') { echo "selected='selected' "; } ?> value="11">客服：淘宝待执行退款申请</option>

						<option <?php if ($search_mode=='9') { echo "selected='selected' "; } ?> value="9">客服：申请被拒待回访</option>

						<option <?php if ($search_mode=='23') { echo "selected='selected' "; } ?> value="23">客服：追回单尚未取消</option>
						</optgroup>
						<?php } 
						if($as_role=='viewer' || $as_role=='finance') { ?>
						<optgroup label="财务">
						<option <?php if ($search_mode=='14') { echo "selected='selected' "; } ?> value="14">财务：处理售后沟通</option>

						<option <?php if ($search_mode=='5') { echo "selected='selected' "; } ?> value="5">财务：退款申请待审核</option>
						<option <?php if ($search_mode=='8') { echo "selected='selected' "; } ?> value="8">财务：退款信息已确认待退款</option>

						<option <?php if ($search_mode=='17') { echo "selected='selected' "; } ?> value="17">财务：淘宝未审核退款申请</option>
						<option <?php if ($search_mode=='18') { echo "selected='selected' "; } ?> value="18">财务：淘宝待执行退款申请</option>
						</optgroup>
						<?php } 
						if($as_role=='viewer' || $as_role=='logistics') { ?>
						<optgroup label="物流">
						<option <?php if ($search_mode=='15') { echo "selected='selected' "; } ?> value="15">物流：处理售后沟通</option>
						
						<option <?php if ($search_mode=='6') { echo "selected='selected' "; } ?> value="6">物流：已审核待退货</option>
						<option <?php if ($search_mode=='22') { echo "selected='selected' "; } ?> value="22">物流：等待消费者寄回货物</option>
						<option <?php if ($search_mode=='7') { echo "selected='selected' "; } ?> value="7">物流：货已收到待验货</option>

						<option <?php if ($search_mode=='20') { echo "selected='selected' "; } ?> value="20">物流：待审核的退款申请</option>

						<option <?php if ($search_mode=='24') { echo "selected='selected' "; } ?> value="24">物流：需要追回</option>
						</optgroup>
						<?php } 
						if($as_role=='viewer' || $as_role=='cg') { ?>
						<optgroup label="快递理赔客服">
						<option <?php if ($search_mode=='16') { echo "selected='selected' "; } ?> value="16">采购：处理售后沟通</option>
						</optgroup>
						<?php } ?>
					</select>

					售后当前留言关键字(只过滤不影响统计结果)
					<input type="text" name="last_word_keyword" value="<?php echo $last_word_keyword; ?>" >
				</div>
				<div style="margin-top:6px;margin-left:30px;">
					<!--<input type="submit" id="submit_common" value="查询">-->
					<a href="#" class="tab" onclick="document.getElementById('sform').submit();">
						<span style="
							text-align:center;width:50px;display:inline;
							padding: 5px;
							background-color: #2899D6;
						">搜 索
						</span>
					</a>
					<a href="#" style="margin-left:16px;" class="tab" onclick="export_to_excel();">
						<span style="
							text-align:center;width:50px;display:inline;
							padding: 5px;
							background-color: #2899D6;
						">导 出
						</span>
					</a>
					</div>
					<div id="non_mode_div_place" 
						<?php if(true || $s_mode==0) echo "style=\"display: none;\"" ?>
					>
						已经设定任务
					</div>
					<input type="hidden" name="as_role" id="as_role" value="<?php echo $as_role; ?>">
					<input type="hidden" name="use_role" id="use_role" value="<?php echo $use_role; ?>">
					<input type="hidden" name="act" value="search">
					

				<!--
				<input type="submit" value="查询">
				&nbsp;-->
				
	  		</div>
			<div>
			<?php 
			if(isDevPrivUser($_SESSION['admin_name'])) {
			?>
					<!--【ERP内部测试用】当前组织为<?php echo get_party_name_by_id($_SESSION['party_id']); ?>。-->
					<input type="checkbox" name="search_in_party" value="0" <?php if(!$s_in_party) echo "checked=\"checked\""; ?>>
					搜索所有业务组织（内部测试用）
					<input type='button' id='single_info_cell_setting_button' value='关闭单个摘要窗口模式' onclick="
						single_info_cell_setting=!single_info_cell_setting;
						document.getElementById('single_info_cell_setting_button').value=(single_info_cell_setting?'关闭':'打开')+'单个摘要窗口模式';
					">
			<?php 
			} 
			if($use_role=='logistics' // || $use_role=='viewer'
				){
			?>
				<input type='radio' name='is_soon_or_pending' <?php if($is_soon_or_pending=='is_soon') echo "checked='checked'"; ?> value='is_soon'><?php echo ($use_role=='viewer'?"全部任务":"当前可以处理的任务"); ?>
				<input type='radio' name='is_soon_or_pending' <?php if($is_soon_or_pending=='is_pending') echo "checked='checked'"; ?> value='is_pending'>待消费者退回货物的任务
			<?php
			}
			?>
			</div>

			<?php 
	  			if(/*($use_role=='viewer' || $use_role=='postsale') AND */ in_array('grouped', $tasks)){
	  		?>
	  		<div style="display: none;">
	  			<textarea><?php print_r($misaka_mikoto); ?></textarea>
	  		</div>
	  		<div>
	  			<table class="detail_table" style="width: 100%">
	  				<tr>
	  					<th>外部订单号</th>
	  					<th colspan='2'>ERP订单与其售后</th>
	  					<th>类型</th>
	  					<th>状态</th>
	  					<th>金额</th>
	  					<th>顾客</th>	  					
	  					<!--<th>原因</th>-->
	  					<th>时间</th>
	  					<th>待办</th>
	  					<th>备注</th>
	  					<?php 
							if($as_role == 'logistics' && $use_role == 'logistics'){
								
								echo "<th>组织</th>";
							}
				  		?>
	  				</tr>
					<?php
					$group_no=-1;
//					pp('$misaka_mikoto');pp($misaka_mikoto);pp('end $misaka_mikoto');
					//var_dump($misaka_mikoto['100500034003722102']['orders']['7194419 ']['services']);
					if($misaka_mikoto && count($misaka_mikoto)>0){
						foreach ($misaka_mikoto as $taobao_order_sn => $group1) {
							$group_no++;
							$this_group_start_no=$group_no;
							if($group1['taobao_refunds']){
								foreach ($group1['taobao_refunds'] as $taobao_refund_id => $line) {
									if($group_no % 2) echo "<tr style=\"background-color: #EEEEEE;\">\n";
						  			else echo "<tr style=\"background-color: #FFFFFF;\">\n";
									if($group_no==$this_group_start_no){
		  								echo "<td rowspan='".$group1['count2']."'>".$taobao_order_sn."</td>";
		  							}
		  							$group_no+=2;
		  							echo "<td colspan='2'>"."<a href='taobao_refund_message.php?taobao_refund_id={$taobao_refund_id}' target='new_blank'>".$taobao_refund_id."</a></td>";
		  							echo "<td>".$line['类型']."</td>";
		  							echo "<td>".$line['状态']."</td>";
		  							echo "<td>".$line['金额']."</td>";
		  							echo "<td>".$line['顾客']."</td>";
		  							//echo "<td>".$line['原因']."</td>";
		  							echo "<td>".$line['时间']."</td>";
		  							echo "<td>".$line['待办']."</td>";
		  							echo "<td>".$line['备注'].(empty($line['原因'])?'':$line['原因']);
		  							foreach ($group1['orders'] as $order_id => $group2){
		  								if($group2['count']==0){
		  									echo "<!--
		  										<br><a href=\"../order_edit.php?order_id=".$order_id."\" target='blank'>".$group2['order_sn']."</a> 等待他方处理
		  										-->";
		  								}
		  							}
		  							echo "</td>";
		  							echo "</tr>";
								}
							}

							if(false && $group1['tmall_refunds']){
								foreach ($group1['tmall_refunds'] as $taobao_refund_id => $line) {
									if($group_no % 2) echo "<tr style=\"background-color: #EEEEEE;\">\n";
						  			else echo "<tr style=\"background-color: #FFFFFF;\">\n";
									if($group_no==$this_group_start_no){
		  								echo "<td rowspan='".$group1['count2']."'>".$taobao_order_sn."</td>";
		  							}
		  							$group_no+=2;
		  							echo "<td colspan='2'>".$taobao_refund_id."</td>";
		  							echo "<td>".$line['类型']."</td>";
		  							echo "<td>".$line['状态']."</td>";
		  							echo "<td>".$line['金额']."</td>";
		  							echo "<td>".$line['顾客']."</td>";
		  							//echo "<td>".$line['原因']."</td>";
		  							echo "<td>".$line['时间']."</td>";
		  							echo "<td>".$line['待办']."</td>";
		  							echo "<td>".$line['备注'].(empty($line['原因'])?'':$line['原因']);
		  							foreach ($group1['orders'] as $order_id => $group2){
		  								if($group2['count']==0){
		  									echo "<!--
		  										<br><a href=\"../order_edit.php?order_id=".$order_id."\" target='blank'>".$group2['order_sn']."</a> 等待他方处理
		  										-->";
		  								}
		  							}
		  							echo "</td>";
		  							echo "</tr>";
								}
							}

							if($group1['orders']){
								foreach ($group1['orders'] as $order_id => $group2) {
									$is_first_in_order=true;
		  							if($group2['msg'] && count($group2['msg'])){
		  								foreach ($group2['msg'] as $id => $line) {
		  									if($group_no % 2) echo "<tr style=\"background-color: #EEEEEE;\">\n";
								  			else echo "<tr style=\"background-color: #FFFFFF;\">\n";
											if($group_no==$this_group_start_no){
				  								echo "<td rowspan='".$group1['count2']."'>".$taobao_order_sn."</td>";
				  							}
				  							$group_no+=2;
				  							if($is_first_in_order){
				  								echo "<td rowspan='".$group2['count2']."'>".
				  									"<a href=\"../order_edit.php?order_id=".$order_id."\" target='blank'>".$group2['order_sn']."</a>".
				  									//"&nbsp;<a href='#' onclick='show_key_order_info_box(".$order_id.");'>[摘要]</a>".
				  									"<br><input type='button' id='box_btn_".$order_id."' value='展开详细信息' onclick='switch_key_order_info_cell_box(".$order_id.");'>".
				  									"</td>";
				  								$is_first_in_order=false;
				  							}
				  							echo "<td><a href=\"../sale_support/sale_support.php?order_id=".$order_id."\" target='blank'><input type='button' value='查看沟通'><!--$id--></a></td>";
		  									echo "<td>".$line['类型']."</td>";
				  							echo "<td".($line['highlight']=='Y'?" style='background-color: yellow;'":"").">".$line['状态']."</td>";
				  							echo "<td>".$line['金额']."</td>";
				  							echo "<td>".$line['顾客']."</td>";	
				  							//echo "<td>".$line['原因']."</td>";
				  							echo "<td>".$line['时间']."</td>";
				  							echo "<td".($line['highlight']=='Y'?" style='background-color: yellow;'":"").">".$line['待办'];
				  								//"<br><a href='#' onclick='show_key_order_info_box(".$order_id.");'><input type='button' value='快速处理'></a>".
				  							if($use_role=='logistics'){
					  							if($line['zhuihui_is_wl']=='zhuihui'){
					  								echo "<br><a href='#' onClick=\"window.open('../shipped_cancel.php?act=search&order_id=".$order_id."','','height=500,width=611,scrollbars=yes,status=yes')\"
							><input type='button' value='物流操作追回'></a>";
					  							}
					  						}else{
					  							if($line['zhuihui_is_wl']=='zhuihui'){
					  								echo "<br>物流操作追回";
					  							}
					  						}
				  							echo "</td>";
				  							echo "<td>".$line['备注'].(empty($line['原因'])?'':
				  								$line['原因']
				  								//"<input type='text' value='「".(mb_strlen($line['原因'],'UTF-8')>8?mb_substr($line['原因'], 0,5,'UTF-8')."...":$line['原因'])."」'>"
				  								//"<input type='text' readonly='readonly' id='MSG_PREVIEW_".$line['原因']."' style='width: 95%;background-color: #FFEAC1;' value='加载中'>"
				  								);
				  							if($line['pending_service_list']!=''){
				  								echo (empty($line['原因'])?"":"<br>")."<span style='background-color:yellow;'>".$line['pending_service_list']."</span>";
				  							}
				  							echo "</td>";
				  							if($as_role == 'logistics' && $use_role == 'logistics'){
				  								echo "<td>".$line['组织']."</td>";
				  							}
				  							
				  							echo "</tr>";

				  							?>
				  							<script type="text/javascript">
				  							<?php 
				  								if(!empty($line['原因'])){
				  									//fruits[fruits.length] = "Lemon";
				  									echo " msgIdArray[msgIdArray.length]='".$line['原因']."'; ";
				  								}
				  							?>
				  							</script>
				  							<?php
		  								}
		  							}
		  							
		  							if($group2['services'] && count($group2['services'])){
		  								foreach ($group2['services'] as $id => $line) {
		  									if($group_no % 2) echo "<tr style=\"background-color: #EEEEEE;\">\n";
								  			else echo "<tr style=\"background-color: #FFFFFF;\">\n";
											if($group_no==$this_group_start_no){
				  								echo "<td rowspan='".$group1['count2']."'>".$taobao_order_sn."</td>";
				  							}
				  							$group_no+=2;
				  							if($is_first_in_order){
				  								echo "<td rowspan='".$group2['count2']."'>".
				  									"<a href=\"../order_edit.php?order_id=".$order_id."\" target='blank'>".$group2['order_sn']."</a>".
				  									//"&nbsp;<a href='#' onclick='show_key_order_info_box(".$order_id.");'>[摘要]</a>".
				  									"<br><input type='button' id='box_btn_".$order_id."' value='展开详细信息' onclick='switch_key_order_info_cell_box(".$order_id.");'>".
				  									"</td>";
				  								$is_first_in_order=false;
				  							}
		  									
		  									if($line['类型']=='换货') $service_type_parameter=1;
				  							else if($line['类型']=='退货') $service_type_parameter=2;
		  									echo "<td>";
		  									if(true || $use_role!='logistics'){
		  										echo "<a href=\"../sale_serviceV3.php?service_type=".($service_type_parameter)."&act=search&search_text=".$group2['order_sn']."\" target='blank'><input type='button' value='查看申请'><!--[".$id."]--></a>";
		  									}else{
		  										echo "<a href=\"../back_goodsV3.php?act=search&search_type=order_sn&search_text=".$group2['order_sn']."\" target='blank'><input type='button' value='退换入库'><!--[".$id."]--></a>";
		  									}
		  									echo "</td>";
		  									echo "<td>".$line['类型']."</td>";
				  							echo "<td>".$line['状态']."</td>";
				  							echo "<td>".$line['金额']."</td>";
				  							echo "<td>".$line['顾客']."</td>";
				  							
				  							//echo "<td>".$line['原因']."</td>";
				  							echo "<td>".$line['时间']."</td>";
				  							echo "<td>";
				  							if($use_role=='logistics'){
				  								echo "<a href=\"../back_goodsV3.php?act=search&search_type=order_sn&search_text=".$group2['order_sn']."\" target='blank'><input type='button' value='".$line['待办']."'><!--[".$id."]--></a>";
				  								// 如果当前是 "已审核，待退货" 状态
				  								if(
				  									$line['service_info']['state_tag']==6
				  									//$line['service_info']['back_shipping_status']==0 && $line['service_info']['service_status']==1
				  								) {
				  									echo "<br><input type='button' id='cancel_wait' value='等待消费者寄回货物' onclick='update_logistic_service_note(".$line['service_info']['service_id'].")'>";
				  								}
				  							}else{
				  								echo $line['待办'];
				  							}
		  									echo "</td>";
				  							echo "<td>".$line['备注'].(empty($line['原因'])?'':"<br>".$line['原因'])."</td>";
				  							if($as_role == 'logistics' && $use_role == 'logistics'){
				  								echo "<td>".$line['组织']."</td>";
				  							}
				  							
				  							echo "</tr>";
		  								}
		  							}
		  							if($group2['refunds'] && count($group2['refunds'])){
		  								foreach ($group2['refunds'] as $id => $line) {
		  									if($group_no % 2) echo "<tr style=\"background-color: #EEEEEE;\">\n";
								  			else echo "<tr style=\"background-color: #FFFFFF;\">\n";
											if($group_no==$this_group_start_no){
				  								echo "<td rowspan='".$group1['count2']."'>".$taobao_order_sn."</td>";
				  							}
				  							$group_no+=2;
				  							if($is_first_in_order){
				  								echo "<td rowspan='".$group2['count2']."'>".
				  									"<a href=\"../order_edit.php?order_id=".$order_id."\" target='blank'>".$group2['order_sn']."</a>".
				  									//"&nbsp;<a href='#' onclick='show_key_order_info_box(".$order_id.");'>[摘要]</a>".
				  									"<br><input type='button' id='box_btn_".$order_id."' value='展开详细信息' onclick='switch_key_order_info_cell_box(".$order_id.");'>".
				  									"</td>";
				  								$is_first_in_order=false;
				  							}
		  									echo "<td>".
		  										"<a href=\"../refund_view.php?refund_id=".$id."\" target='blank'><input type='button' value='查看申请'><!--[".$id."]--></a>".
		  										"</td>";
		  									echo "<td>".$line['类型']."</td>";
				  							echo "<td>".$line['状态']."</td>";
				  							echo "<td>".$line['金额']."</td>";
				  							echo "<td>".$line['顾客']."</td>";
				  							//echo "<td>".$line['原因']."</td>";
				  							echo "<td>".$line['时间']."</td>";
				  							if($use_role!='viewer'){
					  							echo "<td>"."<a href=\"../refund_check.php?refund_id=".$id."\" target='blank'>".
					  								"<input type='button' value='".$line['待办']."'>"."</a>".
					  								//"<br><a href='#' onclick='show_key_order_info_box(".$order_id.");'><input type='button' value='查看摘要'></a>".
					  								"</td>";
				  							}else{
				  								echo "<td>".$line['待办']."</td>";
				  							}
				  							echo "<td>".$line['备注'].(empty($line['原因'])?'':$line['原因'])."</td>";
				  							echo "</tr>";
		  								}
		  							}
		  							if($group2['order_info'] && count($group2['order_info'])){
		  								if($group_no % 2) echo "<tr style=\"background-color: #EEEEEE;\">\n";
							  			else echo "<tr style=\"background-color: #FFFFFF;\">\n";
										if($group_no==$this_group_start_no){
			  								echo "<td rowspan='".$group1['count2']."'>".$taobao_order_sn."</td>";
			  							}
			  							$group_no+=2;
		  								//foreach ($group2['order_info'] as $id => $line) {
		  								$line=$group2['order_info'];
			  							if($is_first_in_order){
			  								echo "<td colspan='2' rowspan='".$group2['count2']."'>".
			  									"<a href=\"../order_edit.php?order_id=".$order_id."\" target='blank'>".$group2['order_sn']."</a>".
			  									//"&nbsp;<a href='#' onclick='show_key_order_info_box(".$order_id.");'>[摘要]</a>".
			  									"<br><input type='button' id='box_btn_".$order_id."' value='展开详细信息' onclick='switch_key_order_info_cell_box(".$order_id.");'>".
			  									"</td>";
			  								$is_first_in_order=false;
			  							}
	  									//echo "<td>".$order_id."</td>";
	  									echo "<td>".$line['类型']."</td>";
			  							echo "<td>".$line['状态']."</td>";
			  							echo "<td>".$line['金额']."</td>";
			  							echo "<td>".$line['顾客']."</td>";
			  							//echo "<td>".$line['原因']."</td>";
			  							echo "<td>".$line['时间']."</td>";
			  							echo "<td>".$line['待办'].
			  								//"<br><a href='#' onclick='show_key_order_info_box(".$order_id.");'><input type='button' value='快速处理'></a>".
				  							"</td>";
			  							echo "<td>".$line['备注'].(empty($line['原因'])?'':"<br>".$line['原因'])."</td>";
		  								//}
		  								echo "</tr>";
		  							}
		  							if(!$is_first_in_order){
			  							echo "
			  								<tr>
			  									<td colspan='9' style='display: none; height: 500px;background-color: #B4EEB4;' id='box_".$order_id."'>
			  										<iframe id='order_".$order_id."_iframe' style='top:5px;width:99%;height: 99%;' ></iframe>
			  									</td>
			  								</tr>
			  							";
			  						}
								}
							}
						}
					} else 
					{
						if($colspan==0 && $as_role == 'logistics' && $use_role == 'logistics'){
							echo "<tr><td style='text-align: center;height: 300px;font-size:20px;' colspan='11'>无搜索结果<!--";
						}
						else{
							echo "<tr><td style='text-align: center;height: 300px;font-size:20px;' colspan='10'>无搜索结果<!--";
						}
						
						echo "count=".count($misaka_mikoto)."<br>";
						print_r($misaka_mikoto);
						echo "--></td></tr>";
					}
					?>
	  			</table>

	  		</div>
	  		<?php 
	  			}
	  		?>
			<div  class="waku" style="text-align: center;margin-top:10px;">
				每页同一类售后包含<input type='text' name='page_item_limit' style="width: 25px;" value='<?php echo $page_item_limit; ?>'>组
				&nbsp;&nbsp;&nbsp;
				<input type="button" value="<" onclick="
						var p=document.getElementById('page');
						if(<?php echo $s_page; ?>>1)p.value=<?php echo $s_page; ?>-1;
						if(<?php echo $s_page; ?>>1)document.getElementById('sform').submit();
					"
					<?php if ($s_page <= 1) echo "disabled='disabled' "; ?>
					>
					第<input type="text" name="page" id="page" style="width: 25px;" value="<?php echo $s_page; ?>">页
					<input type="button" value=">" onclick="
						var p=document.getElementById('page');
						p.value=<?php echo $s_page; ?>+1;
						document.getElementById('sform').submit();
					"
					<?php if ($s_page >= $available_pages) echo " disabled='disabled' "; ?>
					>
					共<?php echo $available_pages; ?>页
					&nbsp;&nbsp;&nbsp;
					<!--<input type="submit" value="跳转">-->
					<a href="#" class="tab" onclick="document.getElementById('sform').submit();">
						<span style="
							padding: 5px;
							background-color: #2899D6;
						">
							&nbsp;跳 转&nbsp;
						</span>
					</a>
			</div>
	  	</div>
  		</form>
  		<form method="POST" name="excel_form">
  			<input type="hidden" name="export" value="1" />
  		</form>
  		<script type="text/javascript">
  		/*
  		$(document).ready(
  			function(){
  				$(document).keypress(function(e){   
				    if(e.keyCode==27){
				    	//if(document.getElementById('divMask').style.display!='none')
				    	document.getElementById('divMask').style.display='none';
				    }
  			}
  		);
		*/

		for (var i = 0; i < msgIdArray.length; i++) {
			var msgId=msgIdArray[i];
			// 不再使用ajax加载message
			// ajaxLoadMsg(msgId);
		};

		function ajaxLoadMsg(msgId){
			var xmlhttp;
			var url='postsale_function.php?act=ajax&call=get_msg_by_id&msg_id='+msgId;
			if (window.XMLHttpRequest){
			  	xmlhttp=new XMLHttpRequest();
			} else {
			  	xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
			}
			var info_span=('MSG_PREVIEW_'+msgId);
			
			xmlhttp.onreadystatechange=function(){
			  	if (xmlhttp.readyState==4){
			  		if(xmlhttp.status==200){
				    	document.getElementById(info_span).value=xmlhttp.responseText;
					}else{
				    	document.getElementById(info_span).value="加载失败";
				    }
				}
			}
			
			xmlhttp.open('POST',url,true);
			xmlhttp.send();
		}

  		function keyUp(e) {
            var currKey = 0, e = e || event;
            currKey = e.keyCode || e.which || e.charCode;
            var keyName = String.fromCharCode(currKey);
            //alert("按键码: " + currKey + " 字符: " + keyName);
            if(currKey==27){
            	close_show_keyinfo();
            } else if(currKey==116){
            	reload_keyinfo();
            }
        }
        document.onkeyup= keyUp;

        function reload_keyinfo(){
        	var f = document.getElementById('order_iframe');
			f.src = f.src;
        }

        function close_show_keyinfo(){
        	document.getElementById('divMask').style.display='none';
        }
  		</script>
  		<div class="div-dialog-mask" id="divMask" style="display: none;">
			<div style="position:absolute;font-size:13pt;top:13px;left:86%;">
				<a href="#" onclick="reload_keyinfo();">刷新</a>
				&nbsp; &nbsp;
				<a href="#" onclick="close_show_keyinfo();">关闭</a>
			</div>
		    <iframe id="order_iframe" style="position:absolute;top:35px;"></iframe>
		</div>
		<!--
		<a href="#" onclick="show_key_order_info_box('123');">Go123</a>
		--
		<a href="#" onclick="show_key_order_info_box('1235956');">TEST WINDOW</a>
		-- -->
  		<script type="text/javascript">//<![CDATA[
	      Zapatec.Calendar.setup({
	        weekNumbers       : false,
	        electric          : false,
	        inputField        : "start",
	        ifFormat          : "%Y-%m-%d",
	        daFormat          : "%Y-%m-%d"
	      });
	      Zapatec.Calendar.setup({
	        weekNumbers       : false,
	        electric          : false,
	        inputField        : "end",
	        ifFormat          : "%Y-%m-%d",
	        daFormat          : "%Y-%m-%d"
	      });
	    //]]>
		</script>
		<hr>
		<p style="font-size:5px;color:gray;">
		分段耗时统计：
		<?php 
		$debug_time_list[4]=microtime(true);
		// print_r($debug_time_list);
		// $prev_time=0;
		// $prev_name='';
		// foreach($debug_time_list as $name => $time){
		// 	if($prev_time!=0){
		// 		echo $prev_name." = ".(number_format(($time-$prev_time), 4, '.', '')).'s '.PHP_EOL;
		// 	}
		// 	$prev_time=$time;
		// 	$prev_name=$name;
		// }

		// print_r($db_time);

		$db_time['sum']=$db_time['sync_taobao_refunds']+$db_time['sync_tmall_refunds']+$db_time['cache_postsale_service']+$db_time['cache_postsale_refund']+$db_time['cache_postsale_message'];

		echo "初始化 ".(number_format(($debug_time_list[1]-$debug_time_list[0]), 4, '.', '')).'秒 '.PHP_EOL;
		echo "读取请求 ".(number_format(($debug_time_list[2]-$debug_time_list[1]), 4, '.', '')).'秒 '.PHP_EOL;
		echo "加载数据 ".(number_format(($debug_time_list[3]-$debug_time_list[2]), 4, '.', '')).'秒（'.
			'淘宝退款查询'.(number_format($db_time['sync_taobao_refunds'], 4, '.', '')).'秒，'.
			'天猫退款查询'.(number_format($db_time['sync_tmall_refunds'], 4, '.', '')).'秒，'.
			'退换货查询'.(number_format($db_time['cache_postsale_service'], 4, '.', '')).'秒，'.
			'退款查询'.(number_format($db_time['cache_postsale_refund'], 4, '.', '')).'秒，'.
			'沟通查询'.(number_format($db_time['cache_postsale_message'], 4, '.', '')).'秒，'.
			'大SQL合计'.(number_format($db_time['sum'], 4, '.', '')).'秒，'.
			'过滤组装数据合计'.(number_format(($debug_time_list[3]-$debug_time_list[2])-$db_time['sum'], 4, '.', '')).'秒'.
			'）'.PHP_EOL;
		echo "页面展示 ".(number_format(($debug_time_list[4]-$debug_time_list[3]), 4, '.', '')).'秒 '.PHP_EOL;
		?>
		</p>
  	</body>
</html>

<?php

function order_process_into_html($order_id){
	global $db;
	$sql_s="SELECT * 
	FROM ecshop.service s 
	WHERE s.order_id='$order_id' 
	AND ( 
		(
	        s.service_status=0
	        OR ((s.outer_check_status=23 OR s.inner_check_status=32) AND (".is_require_service_call_party($search_party)." AND s.service_call_status=1))
	        OR (s.service_status=3 AND ( ".is_require_service_call_party($search_party)." AND s.service_call_status!=2))        
	    ) OR
		(
        	s.service_status=1 AND s.back_shipping_status=0
            OR (s.service_status=1 AND s.back_shipping_status=12 AND s.outer_check_status=0)
        )
	) AND s.service_type=1;
	";
	$all_services_h=$db->getAll($sql_s);
	$sql_s="SELECT * 
	FROM ecshop.service s 
	WHERE s.order_id='$order_id' 
	AND ( 
		(
	        s.service_status=0
	        OR ((s.outer_check_status=23 OR s.inner_check_status=32) AND (".is_require_service_call_party($search_party)." AND s.service_call_status=1))
	        OR (s.service_status=3 AND ( ".is_require_service_call_party($search_party)." AND s.service_call_status!=2))        
	    ) OR
		(
        	s.service_status=1 AND s.back_shipping_status=0
            OR (s.service_status=1 AND s.back_shipping_status=12 AND s.outer_check_status=0)
        )
	) AND s.service_type=2;
	";
	$all_services_t=$db->getAll($sql_s);
	$sql_r="SELECT
        r.*
    FROM
        romeo.refund r
    WHERE
    	r.ORDER_ID='$order_id'
        AND r.STATUS != 'RFND_STTS_EXECUTED'
        AND r.STATUS != 'RFND_STTS_CANCELED'
        AND r.STATUS != 'RFND_STTS_CHECK_OK'
	";
	$all_refunds=$db->getAll($sql_r);
	$html="";
	if($all_services_h && is_array($all_services_h) && count($all_services_h)>0){
		$html.="换货";
	}
	if($all_services_t && is_array($all_services_t) && count($all_services_t)>0){
		$html.="退货";
	}
	if($all_refunds && is_array($all_refunds) && count($all_refunds)>0){
		$html.="退款";
	}
	if($html!="")	$html.="中"; //else $html="-_-";
	return "<p>$html</p>";
}

function get_distributorlist($party_id) {
	global $db;
	$sql = "select distinct distributor_id, name from ecshop.distributor where party_id={$party_id} and status = 'NORMAL'";
	$distributor_list = $db -> getAll($sql);
	//var_dump($distributor_list);
	return $distributor_list;
	
}

?>