<?php
define('IN_ECS', true);
require_once(ROOT_PATH . "admin/includes/init.php");

class MonitorHeader
{
	public $title_ = '页面标题';
	public $search_condition_list_ = array('检索参数');
	public $back_url_ = '表单提交链接';

	function __construct($title, $search_condition_list)
	{
		$this->title_ = $title;
		$this->search_condition_list_ = $search_condition_list;
		$v=explode('/',$_SERVER['PHP_SELF']);
		$this->back_url_ = end($v);
	}
}

function GetTableMonitorInfoAndAdditionalQueryInfoFromSQL($table_name, $sql, $primary_key_name, $ref_name_array=array()){
	global $db;
	$extend_ref_name_array = array();
	if(!in_array($primary_key_name, $ref_name_array)){
		$extend_ref_name_array = array_merge(array($primary_key_name), $ref_name_array);
	}else{
		$extend_ref_name_array = $ref_name_array;
	}

	$ref_list = $data_list=array();
	$result1 = $db->getAllRefBy($sql, $extend_ref_name_array, $ref_list, $data_list);
	// pp("*********result**********");
	// pp($result1);
	// pp("*********extend_ref_name_array**********");
	// pp($extend_ref_name_array);
	// pp("********ref_list***********");
	// pp($ref_list);
	// pp("**********data_list*********");
	// pp($data_list);
	// pp("*******************");

	$table_info = array('table_name'=>$table_name, 'attr_list' =>array(), 'item_list' => array());
	$ref_str_list = array();
	if(empty($data_list)){
		$table_info['attr_list'][] = '无记录';
	}else{
		foreach ($ref_name_array as $ref_name) {
			# code...
			$ref_data_list = $ref_list[$ref_name];
			$ref_str_list[$ref_name] = "'". implode($ref_data_list, "','") . "'";
		}

		$data_list = $data_list[$primary_key_name];
		$sample = reset($data_list);
		$attr_list = array();
		foreach ($sample[0] as $key => $value) {
			$attr_list[] = $key;
		}
		foreach ($data_list as $key => &$value) {
			$value = $value[0];
		}
		$table_info['attr_list'] = $attr_list;
		$table_info['item_list'] = $data_list;
	}

	$result = array('monitor_info' => $table_info, 'query_info' => $ref_str_list);
	return $result;
}

function convert_str_to_sql($strs) {
	if(empty($strs)) return '';
	
	$nos = explode(',',$strs);
	$result = "'";
	foreach($nos as $no) {
		$result .= $no."','";
	}
	$result = substr($result,0,strlen($result)-2);
	
	return $result;
}

?>