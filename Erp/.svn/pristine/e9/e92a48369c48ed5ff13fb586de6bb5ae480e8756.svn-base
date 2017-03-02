<?php
define('IN_ECS', true);

require('includes/init.php');
require_once(ROOT_PATH . 'includes/cls_json.php');

admin_priv('cofco_monitor');
$act = $_REQUEST['act'];
$method = $_REQUEST['method'];
$database = $_REQUEST['database'];
//默认查询中粮商品信息
$database = empty($database) ? 'zlshop' : $database; 
//创建对应的类
$sync_data = new $database();
$_REQUEST['database'] = $database;
if($act == 'search'){
	//执行sql获取结果集
	$sync_orders = $sync_data->get_orders($_REQUEST);	
	$smarty->assign('sync_orders', $sync_orders);
}
if($method == 'ajax'){
	$sync_order_goods = $sync_data->$act($_REQUEST);
	$json = new JSON();
	print $json->encode($sync_order_goods);
	die();
}
$smarty->assign('database', $database);
$smarty->display('brand_integration_monitor_65625.htm');

/**
 * 基类，通过传入的参数来确定sql执行的条件
 * */
abstract class Database{
	
	protected $condition = '';
	/**
	 * 获取订单详细信息，字段必须为order_sn,create_time,sync_time,sync_status,sync_note,feedback_status,feedback_note
	 * */
	public function get_orders($args){
		$condition = $this->get_common_condition($args);
		$condition .= $this->get_diff_condition($args);
		global $db;
		$sql = $this->get_order_sql($condition);
		return $db->getAll($sql);
	}
	
	/**
	 * 获取相同部分的条件
	 * */
	function get_common_condition($args){
		$condition = '';
		$order_sn = trim($args['order_sn']);
		if($order_sn){
			//如果查询order_sn则查询全部
			$condition .= " AND order_sn like '{$order_sn}%'";
			return $condition;
		}
		if($args['sync_status'] && $args['sync_status'] != 'ALL'){
			$condition .= " AND sync_status = '{$args['sync_status']}'";
		}
		if($args['feedback_status'] && $args['feedback_status'] != 'ALL'){
			$condition .= " AND feedback_status = '{$args['feedback_status']}'";
		}
		if($args['start_time']){
			$condition .= " AND sync_time > '{$args['start_time']}'";
		}
		if($args['end_time']){
			$end_time = date('Y-m-d',strtotime($args['end_time']) + 24*3600);
			$condition .= " AND sync_time < '{$end_time}'";
		}
		if(empty($args['start_time']) && empty($args['end_time']) && $_SERVER['REQUEST_METHOD'] != 'POST'){
			$condition .= " AND sync_time > '".date('Y-m-d', strtotime('-1 day'))."'";
		}
		return $condition;
	}
	
	function reset_status($args){
		global $db;
		if(!check_admin_priv('cofco_data_modify')){
			$result['message'] = '没有权限--中粮数据修改权限';
			$result['status'] = 'FAILURE';
			return $result;
		};
		$order_id = $args['order_id'];
		$motion = $args['motion'];
		$result = array();
		$sql = "SELECT order_sn, sync_status, feedback_status FROM ".$this->get_info_table()." WHERE order_id = $order_id";
		$r = $db->getRow($sql);
		if(empty($r)){
			$result['message'] = '找不到对应的中间表记录';
			$result['status'] = 'FAILURE';
			return $result;
		}
		try{
			$sql = "UPDATE ".$this->get_info_table() ." SET $motion = 'NORMAL' WHERE order_id = $order_id ";
			$db->query($sql);	
			$this->create_action($args['database'], $r);
			$result['message'] = '更新成功';
			$result['status'] = 'SUCCESS';
			$result['feedback'] = 'NORMAL';
			return $result;
		}catch(Exception $e){
			$result['message'] = '数据库更新失败';
			$result['status'] = 'FAILURE';
			return $result;
		}
		
	}
	
	private function create_action($database, $args){
		global $db;
		$sql = "INSERT INTO brand_cofco_monitor_action VALUES
				(NULL, '{$args['order_sn']}', '$database', '{$args['sync_status']}','{$args['feedback_status']}',
				'{$_SESSION['admin_name']}',NOW())";
		$db->query($sql);
	}
	//获取sql
	protected abstract function get_order_sql($condition);
	
	protected abstract function get_time_item();
	
	/**
	 * 获取不同部分的条件
	 * */
	protected abstract function get_diff_condition($args);
	
	public abstract function find_goods($args);
	
	protected abstract function get_info_table();
} 

/**
 * 中粮商品类
 * */
class zlshop extends Database{
	protected function get_order_sql($condition){
		$sql = " SELECT i.order_id, i.order_sn, i.create_time, i.sync_time, 
				 i.sync_status, i.sync_note, i.feedback_status, i.feedback_note, 
				 count(*) as goods_num
				 FROM ecshop.brand_cofco_order_info i
				 LEFT JOIN ecshop.brand_cofco_order_goods g ON i.order_id = g.order_id 
				 WHERE 1 $condition group by i.order_id order by i.sync_time desc";
		return $sql;
	}
	protected function get_time_item(){
		return 'create_time';
	}
	protected function get_diff_condition($args){
	}
	
	public function find_goods($args){
		$order_id = $args['order_id'];
		global $db;
		$sql = " SELECT rec_id as order_goods_id, goods_name, goods_sn,goods_number, goods_price 
				FROM ecshop.brand_cofco_order_goods WHERE order_id = $order_id";
		return $db->getAll($sql);
	}
	protected function get_info_table(){
		return 'ecshop.brand_cofco_order_info';
	} 
}

/**
 * 喜宴卡类
 * */
class cofco_dealer extends Database{
	protected function get_order_sql($condition){
		$sql = " SELECT i.order_id, i.order_sn, i.created_on as create_time, 
				 i.sync_time, i.sync_status, i.sync_note, i.feedback_status, i.feedback_note, 
				 count(*) as goods_num
				 FROM ecshop.brand_cofco_dealer_order_info i
				 LEFT JOIN ecshop.brand_cofco_dealer_order_goods g ON g.order_id = i.order_id
				 WHERE 1 $condition group by i.order_id order by i.sync_time desc";
		return $sql;
	}
	protected function get_time_item(){
		return 'created_on';
	}
	protected function get_diff_condition($args){
	}
	
	public function find_goods($args){
		$order_id = $args['order_id'];
		global $db;
		$sql = " SELECT auto_id as order_goods_id, IFNULL(g.goods_name,'ERP未维护此商品') as goods_name, bcdog.plan_sn as goods_sn, 
				 bcdog.quantity as goods_number,
				 bcdog.part_amount/bcdog.quantity/100 as goods_price
				 FROM ecshop.brand_cofco_dealer_order_goods bcdog 
				 LEFT JOIN ecshop.ecs_goods g ON IF(bcdog.plan_sn = '9996','ZXLOGOTZ',IF(bcdog.plan_sn = '9997', 'zxjfdh', bcdog.plan_sn))  = g.barcode AND g.goods_party_id = 65625
				 WHERE bcdog.order_id = $order_id";
		return $db->getAll($sql);
	}
	
	protected function get_info_table(){
		return 'ecshop.brand_cofco_dealer_order_info';
	}
}
?>
