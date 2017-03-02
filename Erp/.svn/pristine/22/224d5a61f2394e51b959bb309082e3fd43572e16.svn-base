<?php

abstract class ClsSalesOrderStatusAbstract{
	function __construct(){
		$this->status_name_ = '状态名';
    	$this->allowed_action_list_[] = 'add_note';
    }
    abstract function GetAllowedEditActionList();
    var $status_name_ = '状态名';
	var $allowed_action_list_ = array();
}

?>