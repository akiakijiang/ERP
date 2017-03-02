<?php
function GenerateAllowedActionsForHeader($order_type, $header_status, $sync_status, &$action_list, &$result = null){
	$priv_rst = check_admin_priv_with_feedback('or_po_action');
	$action_list = array();
	if($priv_rst['is_allow']){
		//废除PO/恢复PO
		if($header_status =='OK')
			$action_list[] = array('name'=>'废除PO', 'action'=>'ClosePO');
		else if($header_status =='CLOSED')
			$action_list[] = array('name'=>'恢复PO', 'action'=>'ReopenPO');
		//重建PO
		if(in_array($sync_status, array('INIT','WrongData'))){
			$action_list[] = array('name'=>'编辑PO', 'action'=>'EditPO');
		}else{
			$action_list[] = array('name'=>'重建PO', 'action'=>'AddPOFromOld');
		}
		//补建退单
		//if($order_type == 'Z3OS' && $sync_status == 'Finish' && $header_status =='OK')
			$action_list[] = array('name'=>'补建退单', 'action'=>'GenerateZRTO');
		//补建退运费
		if($order_type == 'Z3OS')
			$action_list[] = array('name'=>'补建退运费', 'action'=>'GenerateZRTOSR');
		//初始化同步
		if(in_array($sync_status, array('WrongData','ConnectionErr','Pending','Doing','ProxyException')) && $header_status =='OK')
			$action_list[] = array('name'=>'初始化同步', 'action'=>'InitSyncStatus');
		//完结同步	
		if(in_array($sync_status, array('Timeout','Doing','ProxyException')) && $header_status =='OK'){
			$action_list[] = array('name'=>'完结同步', 'action'=>'FinishSyncStatus');
		}
	}else{
		$result['err_no'] = 1;
		$result['msg'] = $priv_rst['err_msg'];
	}
}
?>