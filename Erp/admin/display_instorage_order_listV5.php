<?php
/**
 * 入库订单列表
 * 
 * @author  cywang 20130801
 */

define('IN_ECS', true);
require('includes/init.php');
require("function.php");


// 导出csv的权限
$csv = $_REQUEST['csv'];
if ($csv) { admin_priv("admin_other_csv"); }
//search option
class SearchOption
{
	//tab id：0=按订单号搜索；1=按条件搜索
	public $search_tab_id_;
	//按订单号搜索
	public $order_sn_;
	//按条件搜索
	public $storage_status_;	//入库状态：todo=未入库 done=已入库 all=所有订单
	public $order_type_info_;	//订单类型
	public $start_time_;		//起始时间
	public $end_time_;			//结束时间
	public $cagetory_name_;		//商品类别名
	public $goods_cagetory_;		//商品类别
	public $goods_name_;		//商品名
	public $goods_barcode_;		//商品条码
	public $provider_;			//供应商
	public $facility_id_;		//仓库
}
//order type info
class OrderTypeInfo
{
	public $type_name_;
	public $admin_priv_;
	public $choosen_;
	public function __construct($type_name, $admin_priv, $choosen)
	{
		$this->type_name_ = $type_name;
		$this->admin_priv_ = $admin_priv;
		$this->choosen_ = $choosen;
	}
}
//init SearchOption
$search_option = new SearchOption;
$search_option->search_tab_id_= (array_key_exists('search_tab_id', $_REQUEST) && $_REQUEST['search_tab_id']!='')?  $_REQUEST['search_tab_id'] :'0';
$search_option->order_sn_= array_key_exists('order_sn', $_REQUEST) ? $_REQUEST['order_sn'] : '';
$search_option->storage_status_= array_key_exists('storage_status', $_REQUEST) ? $_REQUEST['storage_status'] : 'todo';
$search_option->start_time_= array_key_exists('start_time', $_REQUEST) ? $_REQUEST['start_time'] : date("Y-m-d",time());
$search_option->end_time_= array_key_exists('end_time', $_REQUEST) ? $_REQUEST['end_time'] : date("Y-m-d",strtotime('+1 day'));
$search_option->cagetory_name_= array_key_exists('cagetory_name', $_REQUEST) ? $_REQUEST['cagetory_name'] : "";
$search_option->goods_cagetory_= array_key_exists('goods_cagetory', $_REQUEST) ? $_REQUEST['goods_cagetory'] : "";
$search_option->goods_name_= array_key_exists('goods_name', $_REQUEST) ? $_REQUEST['goods_name'] : "";
$search_option->goods_barcode_= array_key_exists('goods_barcode', $_REQUEST) ? $_REQUEST['goods_barcode'] : "";
$search_option->provider_= array_key_exists('provider', $_REQUEST) ? $_REQUEST['provider'] : "";
$search_option->facility_id_= array_key_exists('facility_id', $_REQUEST) ? $_REQUEST['facility_id'] : "";

$search_option->order_type_info_ = array(
		"purchase" => new OrderTypeInfo(
				"purchase", 
				check_goods_common_party()*check_admin_priv('ck_in_storage_common')
					+(!check_goods_common_party())*check_admin_priv('ck_in_storage', 'wl_in_storage'),
				$_REQUEST['order_type_purchase']),
		'returned_exchange' => new OrderTypeInfo(
				"returned_exchange", 
				check_admin_priv('cg_back_goods_check'),
				$_REQUEST['order_type_returned_exchange']),
		'gh' => new OrderTypeInfo(
				"gh", 
				true,
				$_REQUEST['order_type_gh']),
		'borrow' => new OrderTypeInfo(
				"borrow", 
				true,
				$_REQUEST['order_type_borrow']),
		'callback' => new OrderTypeInfo(
				"callback",
				true,
				$_REQUEST['order_type_callback'])
		);

// 消息
$info = $_REQUEST['info'];

if ($csv == "csv导出") {
	header ( "Content-type:application/vnd.ms-excel" );
	header ( "Content-Disposition:filename=" . iconv ( "UTF-8", "GB18030", "采购订单详情" ) . ".csv" );
	$out = $smarty->fetch ( 'oukooext/in_storage_csv_V5.htm' );
	echo iconv ( "UTF-8", "GB18030", $out );
	exit ();
}

//smarty
$search_orders = GetSearchOrders($order_type_info);
$smarty->assign('search_orders', $search_orders);
$smarty->assign('facility_id_list', array ('0' => '未指定仓库' ) + array_intersect_assoc(get_available_facility(),get_user_facility()) );
$smarty->assign('back', $_SERVER['REQUEST_URI']);
$smarty->assign('facility_name', facility_mapping($_SESSION['facility_id']));
$smarty->assign('info', $info);
$smarty->assign('search_option', $search_option);
$smarty->display('oukooext/display_instorage_order_listV5.htm');


/*
 * 根据条件查询入库订单
 * */
function GetSearchOrders($search_option)
{
	return $search_orders;
}
?>