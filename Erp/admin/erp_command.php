<?php
define ( 'IN_ECS', true );
require_once ('includes/init.php');
require_once ('function.php');
require_once(ROOT_PATH . 'includes/cls_json.php');
include_once(ROOT_PATH . 'admin/function.php'); 

if(!in_array($_SESSION['admin_name'],array(
	'mjzhou','zjli','ljni','hzhang1','qyyao','xjye','wlchen','yjchen','stsun'
))){
	die('没有权限');
}

//HISTORY
$date=date('ymd');
$file=__DIR__.'/filelock/devel-'.$date.'.log';

$command="grep ".escapeshellarg('ERP_COMMAND')." ".escapeshellarg($file);
$records=null;
$lastline=exec ( $command , $records );
 
//MAIN
$comd = trim($_REQUEST ['comd']);
$func = trim($_REQUEST ['func']);
$appkey = trim($_REQUEST ['appkey']);
$days = trim($_REQUEST ['days']);
$hours = trim($_REQUEST ['hours']); 
$group = trim($_REQUEST ['group']); 
$endDate = trim($_REQUEST ['start']); 
$others = trim($_REQUEST ['others']); 

if($comd){
	$command = "php ".__DIR__."/../protected/yiic ".$comd."  ".$func ;
	
	if($appkey != ""){
		$command .= " --appkey=".$appkey;
	}
	if($days != ""){
		$command .= " --days=".$days;
	}
	if($hours != ""){
		$command .= " --hours=".$hours;
	}
	if($group != ""){
		$command .= " --group=".$group;
	}
	if($endDate != ""){
		$command .= " --endDate=".$endDate;
	}
	if($others != ""){
		$command .= " ".$others;
	}
	QLog::log("[ERP_COMMAND] ".$_SESSION['admin_name']." # ".$command);
	$result=exec($command,$out); //<h3>'.$result.'</h3>
	$smarty->assign('msg','<h3>'.$command.'</h3>'.implode('<br>',$out));
}

$smarty->assign('history',$records);

$smarty->assign('comd',$comd);
$smarty->assign('func',$func);
$smarty->assign('appkey',$appkey);
$smarty->assign('days',$days);
$smarty->assign('hours',$hours);
$smarty->assign('start',$endDate);
$smarty->assign('others',$others);
$smarty->display ( 'erp_command.html' );
?>
