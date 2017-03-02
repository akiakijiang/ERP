<?php
/*
* 可优化：
* 1. 缓存
*/
require_once ('cls_sales_order.php');

class ClsSalesOrderServiceRecords extends ClsSalesOrderBasic{
	static protected function GetAllowedActionList(){
		return array('query');
	}
	function __construct($data_map){
    	parent::__construct($data_map);
		$this->order_id_ = $data_map['order_id'] ? $data_map['order_id'] : 0;
	}

	function QueryData(){
		//
		global $db;
		$sql = "SELECT service_id, service_type, service_status, 
						apply_username, apply_reason, apply_datetime, 
						review_username, review_remark, review_datetime,
						check_result
				FROM ecshop.service
				WHERE order_id =  '{$this->order_id_}' ";
		$services = $db->getAll($sql);
      	foreach ($services as $service) {
      		# code...
			$this->record_list_[] = new ClsSalesOrderServiceRecord($service);
		}
		//售后沟通记录
		$sql = "SELECT count(*) 
				FROM ecshop.sale_support_message
				WHERE order_id =  '{$this->order_id_}' ";
		$this->service_message_count_ = $db->getOne($sql);
	}

	var $record_list_ = array();
    //售后沟通记录
    var $service_message_count_ = 0;

}

class ClsSalesOrderServiceRecord{
	function __construct($service){
		require_once('../config.vars.php');
		global $_CFG;
      	//
		$this->service_id_ = $service['service_id'];
		//
		$this->type_name_ = 
			$_CFG['adminvars']['service_type_mapping'][$service['service_type']] ? $_CFG['adminvars']['service_type_mapping'][$service['service_type']] : $service['service_type'];
		$this->status_name_ = 
			$_CFG['adminvars']['status_mapping'][$service['service_status']] ? $_CFG['adminvars']['status_mapping'][$service['service_status']] : $service['service_status'];
		//
		$this->apply_username_ = $service['apply_username'] ? $service['apply_username'] : '无';
		$this->apply_reason_ = $service['apply_reason'] ? $service['apply_reason'] : '无';
		$this->apply_datetime_ = $service['apply_datetime'] ? $service['apply_datetime'] : '无';
		//
		$this->review_username_ = $service['review_username'] ? $service['review_username'] : '无';
		$this->review_remark_ = $service['review_remark'] ? $service['review_remark'] : '无';
		$this->review_datetime_ = $service['review_datetime'] ? $service['review_datetime'] : '无';
		//
		$this->check_result_ = $service['check_result'] ? $service['check_result'] :'无';

		//用户反馈信息
		$feed_back = ClsSalesOrderServiceRecord::GetServiceFeedback($this->service_id_);
		//退款帐号信息
		if($feed_back['bank_info']){
			foreach ($feed_back['bank_info'] as $key => $value) {
				# code..
				$this->return_account_info_list_[$key] = $value;
			}
		}
		//退回快递信息
		if($feed_back['carrier_info']){
			foreach ($feed_back['carrier_info'] as $key => $value) {
				# code..
				$this->return_carrier_info_list_[$key] = $value;
			}
		}

		global $db;
		//comments
		$sql = "SELECT count(*) FROM service_comment WHERE service_id = '{$this->service_id_}' ";
		$this->service_comment_count_ = $db->getOne($sql);
		//售后操作记录
		$sql = "SELECT count(*) FROM service_log WHERE service_id = '{$this->service_id_}' ";
		$this->service_log_count_ = $db->getOne($sql);
	}
	/**
	 * 根据service_id返回用户返回的信息
	 *
	 * @param int service 售后服务
	 * @return void
	 */
	static function GetServiceFeedback($service_id) {
	    global $db, $ecs, $service_return_key_mapping;
	    $sql = "SELECT * FROM ecshop.service_return WHERE service_id = '{$service_id}' ORDER BY service_return_id ASC ";

	    $temps = $db->getAll($sql);
	    $return_infos = array ();
	    foreach ($temps as $return_info) {
	        $return_name = $service_return_key_mapping[$return_info['return_name']];
	        $return_infos[$return_info['return_type']][$return_name] = $return_info['return_value'];
	    }

	    return $return_infos;
	}

	var $service_id_;
	//售后类型
	var $type_name_;
	//售后状态
	var $status_name_;
	//退款帐号信息
	var $return_account_info_list_ = array();
	//退回快递信息
	var $return_carrier_info_list_ = array();

	//申请人信息
    var $apply_username_;
    var $apply_reason_;
    var $apply_datetime_;
    //审核人信息
    var $review_username_;
    var $review_remark_;
    var $review_datetime_;
    //检测结果
    var $check_result_;
    //售后评论
    var $service_comment_count_ = 0;
    //售后操作记录
    var $service_log_count_ = 0;
}

//
class ClsSalesOrderServiceMessages extends ClsSalesOrderBasic{
	static protected function GetAllowedActionList(){
		return array('query');
	}
	function __construct($data_map){
    	parent::__construct($data_map);
		$this->order_id_ = $data_map['order_id'] ? $data_map['order_id'] : 0;
	}
	function QueryData(){
		global $db;
		//messages
		$sql = "SELECT created_stamp, support_type, message, send_by, status
				FROM ecshop.sale_support_message
				WHERE order_id =  '{$this->order_id_}' ";
		$messages = $db->getAll($sql);

		$this->service_message_list_ = array();
		foreach ($messages as $message) {
			# code...
			$this->service_message_list_[] = new ClsServiceMessageInfo($message);
		}
	}
	var $order_id_;
	var $service_message_list_;
}
class ClsServiceMessageInfo{
	function __construct($message){
		require_once('../config.vars.php');
		global $_CFG;
		$this->created_stamp_ = $message['created_stamp'];
		$this->support_type_ = $_CFG['adminvars']['support_type'][$message['support_type']];
		$this->message_ = $message['message'];
		$this->send_by_ = $message['send_by'];
		$this->status_ = $message['status'];
	}
	var $created_stamp_;  //发送时间
	var $support_type_;  //咨询类型
	var $message_;  //咨询详情
	var $send_by_;  //发送人
	var $status_;	//操作状态：进行中、完结
}

//售后评论
class ClsSalesOrderServiceComments extends ClsSalesOrderBasic{
	static protected function GetAllowedActionList(){
		return array('query');
	}
	function __construct($data_map){
    	parent::__construct($data_map);
		$this->service_id_ = $data_map['service_id'] ? $data_map['service_id'] : 0;
		$this->check_list_ = array('service_id');
	}
	function QueryData(){
		global $db;
		//comments
		$sql = "SELECT post_username, post_comment, post_datetime,
					replied_username, reply, replied_datetime
				FROM service_comment 
				WHERE service_id = '{$this->service_id_}' ";
		$comments = $db->getAll($sql);

		$this->service_comments_ = array();
		foreach ($comments as $comment) {
			# code...
			$this->service_comments_[] = new ClsServiceCommentInfo($comment);
		}
	}
	static protected function GetIDCheckList($action_type){
		return array('service_id');
	}
	var $service_id_;
	var $service_comments_ = array();
}
class ClsServiceCommentInfo{
	function __construct($comment){
		$this->post_username_ = $comment['post_username'];
		$this->post_comment_ = $comment['post_comment'];
		$this->post_datetime_ = $comment['post_datetime'];
		$this->replied_username_ = $comment['replied_username'];
		$this->reply_ = $comment['reply'];
		$this->replied_datetime_ = $comment['replied_datetime'];
	}

	//评论信息
	var $post_username_;
	var $post_comment_;
	var $post_datetime_;
	//回复信息
	var $replied_username_;
	var $reply_;
	var $replied_datetime_;
}

//售后记录
class ClsSalesOrderServiceLogs extends ClsSalesOrderBasic{
	static protected function GetAllowedActionList(){
		return array('query');
	}
	function __construct($data_map){
    	parent::__construct($data_map);
		$this->service_id_ = $data_map['service_id'] ? $data_map['service_id'] : 0;
	}
	function QueryData(){
		global $db;
		//logs
		$sql = "SELECT is_remark, status_name, log_username, log_datetime, log_note
				FROM service_log 
				WHERE service_id = '{$this->service_id_}' ";
		$logs = $db->getAll($sql);

		$this->service_logs_ = array();
		foreach ($logs as $log) {
			# code...
			$this->service_logs_[] = new ClsServiceLogInfo($log);
		}
	}
	static protected function GetIDCheckList($action_type){
		return array('service_id');
	}

	var $service_id_;
	var $service_logs_ = array();

}
class ClsServiceLogInfo{
	function __construct($log){
		$this->logger_type_ = $log['is_remark'] == '0' ? '系统自动添加' : '客服记录';
		$this->status_name_ = $log['status_name'];
		$this->logger_ = $log['log_username'];
		$this->datetime_ = $log['log_datetime'];
		$this->note_ = $log['log_note'];
	}
	var $logger_type_;  //客服记录 or 系统自动添加
    var $status_name_;      //售后状态
    var $logger_;    //操作人
    var $datetime_;    //操作时间
    var $note_;         //备注
}

?>