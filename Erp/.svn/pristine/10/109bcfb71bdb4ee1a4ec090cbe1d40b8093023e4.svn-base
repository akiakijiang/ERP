<?php 

/**
 * PARTY管理
 */
define('IN_ECS', true);

require_once('includes/init.php');
admin_priv('party_manage');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'RomeoApi/lib_party.php');

global $db;

// 动作
$act = 
    !empty($_REQUEST['act']) //&& in_array($_REQUEST['act'], array('insert','update_cluster'))
    ? $_REQUEST['act'] 
    : null ;


switch ($act) {
	/**
	 * 添加
	 */
	case 'insert' :
        $result = party_insert($_POST['party'], $failed);
        if ($result) {
            $smarty->assign('message', '操作成功！');
        }
        else {
        	$smarty->assign('message', reset($failed));
        }
        break;
    case 'update_cluster':
        $change_party_id=$_POST['party_id'];
        $change_party_group=$_POST['party_group'];
        changePartyCluster($change_party_id,$change_party_group,$message);
        $smarty->assign('message', $message);
        break;
    case 'update_relation':
        $change_party_id=$_POST['party_id'];
        $change__parent_party_id=$_POST['parent_party_id'];
        changePartyRelation($change_party_id,$change__parent_party_id,$message);
        $smarty->assign('message', $message);
        break;
}
$forceUpdatePartyCache=true;

$party_get_all_list=party_get_all_list($forceUpdatePartyCache);

// 为了魔改ERP INDEX
$sql="SELECT PARTY_ID,party_group FROM romeo.party where PARTY_ID in ('".implode("','", array_keys($party_get_all_list))."')";
$party_group_list=$db->getAll($sql);
// $party_group_mapping=array();
foreach ($party_group_list as $line) {
    if($line['party_group']===null){
        $party_group_display="[不显示在分簇列表中]";
    }elseif($line['party_group']===''){
        $party_group_display="[分簇显示空白]";
    }else{
        $party_group_display=$line['party_group'];
    }
    $party_get_all_list[$line['PARTY_ID']]->party_group=$party_group_display;
}


$party_options_list=party_options_list(NULL,$forceUpdatePartyCache);
// var_dump($party_get_all_list);
// var_dump($party_options_list);
// die();
$smarty->assign('party_list', $party_get_all_list);
$smarty->assign('party_options_list', $party_options_list);
$smarty->display('oukooext/party_manage.htm');

function changePartyCluster($party_id,$party_group,&$message){
    global $db;

    if(empty($party_id)){
        $message='出错！party_id is empty';
        return false;
    }
    if($party_group=='!'){
        $party_group=' NULL ';
    }else{
        $party_group=$db->quote($party_group);
    }

    $sql="UPDATE romeo.party SET party_group = '{$party_group}' WHERE party_id='".$db->quote($party_id)."'";
    $afx=$db->exec($sql);
    if($afx){
        $message="操作成功！UPDATED ".$afx." ROW";
        return true;
    }else{
        $message="大势已去！ DB return ".json_encode($afx);
        return false;
    }
}

function changePartyRelation($party_id,$parent_party_id,&$message=''){
    global $db;

    if(empty($party_id) || empty($parent_party_id)){
        $message="业务组不要不选啊:{$party_id} of {$parent_party_id}";
        return false;
    }
    $sql="SELECT IS_LEAF FROM romeo.party WHERE PARTY_ID='{$parent_party_id}'";
    $is_leaf=$db->getOne($sql);
    if($is_leaf!='N'){
        $message='所选的业务组并不是非端末业务组';
        return false;
    }

    $db->start_transaction();
    try {
        $sql="UPDATE romeo.party SET PARENT_PARTY_ID='{$parent_party_id}' WHERE PARTY_ID='{$party_id}'";
        $afx=$db->exec($sql);
        if($afx!=1)throw new Exception("Error Update PARTY", 1);
        
        $sql="UPDATE romeo.party_relation SET PARENT_PARTY_ID='{$parent_party_id}' WHERE PARTY_ID='{$party_id}'";
        $afx=$db->exec($sql);
        if($afx!=1)throw new Exception("Error Update PARTY_RELATION", 1);

        $db->commit();
        $message='大事已成';
        return true;
    } catch (Exception $e) {
        $message=$e->getMessage();
        $db->rollback();
        return false;
    }
}