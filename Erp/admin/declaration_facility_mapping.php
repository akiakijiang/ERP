<?php
/*
 * Created on 2016-6-14
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */

define ( 'IN_ECS', true );
require_once ('includes/init.php');
require_once ('function.php');
require_once(ROOT_PATH . 'includes/cls_json.php');

$act = $_REQUEST['act'] ? $_REQUEST['act'] : '';
//var_dump($_REQUEST);

switch($act) {
	case 'facility_select':
	$json = new JSON;
    $limit = (isset($_POST['limit']) && is_numeric($_POST['limit'])) ? $_POST['limit'] : 40 ;
    print $json->encode(get_facility_list($_POST['q'], $limit)); 
	exit;
	break;
	case 'insert_facility_mapping':
	$erp_facility_id = $_REQUEST['erp_facility_id'];
	$declaration_facility = $_REQUEST['declaration_facility'];
	$json = new JSON;
	$sql_insert = "insert into ecshop.declaration_facility_mapping (erp_facility_id,declaration_facility,created_stamp,last_updated_stamp) 
					value ('{$erp_facility_id}','{$declaration_facility}',now(),now())";
	if($db->query($sql_insert)) {
		$result['msg'] = 'SUCCESS';
	} else {
		$result['msg'] = 'FAIL';
	}
	print $json->encode($result);
	exit;
	break;
	case 'delete_facility_mapping':
	$mapping_id = $_REQUEST['mapping_id'];
	$json = new JSON;
	$sql_delete = "delete from ecshop.declaration_facility_mapping where mapping_id = '{$mapping_id}'";
	if($db->query($sql_delete)) {
		$result['msg'] = 'SUCCESS';
	} else {
		$result['msg'] = 'FAIL';
	}
	print $json->encode($result);
	exit;
	break;
	case 'search':
	$erp_facility_name = $_REQUEST['erp_facility_name'];
	$declaration_facility = $_REQUEST['declaration_facility'];
	$cond = " and f.facility_name like '%{$erp_facility_name}%' and dfm.declaration_facility like '%{$declaration_facility}%'";
	$result = search_facility_mapping($_REQUEST,$cond);
	$facility_mapping = $result['faclity_list'];
	$pager = $result['Pager'];
	break;
	default:
	global $db;	
	$result = search_facility_mapping($_REQUEST,'');
	$facility_mapping = $result['faclity_list'];
	$pager = $result['Pager'];
}


//$available_facility = get_available_facility();
$smarty->assign('declaration_facility_list',$_CFG['adminvars']['declaration_facility']);
//$smarty->assign('available_facility',$available_facility);
$smarty->assign('Pager',$pager);
$smarty->assign('facility_mapping',$facility_mapping);
$smarty->display('declaration_facility_mapping.html');


function search_facility_mapping($args,$cond) {
	global $db;
	$page = intval($args['page']);
	$page = max(1, $page);
	$limit = 30;
	$offset = $limit * ($page-1);
	
	$sqlc = "select count(*) 
					from ecshop.declaration_facility_mapping dfm
					inner join romeo.facility f on f.FACILITY_ID = dfm.erp_facility_id
					where 1 " . $cond;	
	$total = $db ->getOne($sqlc);
		
	$sql = "select dfm.*,f.facility_name 
					from ecshop.declaration_facility_mapping dfm
					inner join romeo.facility f on f.FACILITY_ID = dfm.erp_facility_id
					where 1 " . $cond . "limit {$limit} offset {$offset}";
					
	$simple_facility_list = $db->getAll($sql);
	$args['Pager'] = Pager($total,$limit,$page);
	$args['faclity_list'] = $simple_facility_list;
	return $args;	
}

function get_facility_list($keyword = '', $limit = 100)
{
	global $db;
    $conditions = '';
    if (trim($keyword)) {
        $keyword = mysql_like_quote($keyword);
        $conditions .= " AND f.FACILITY_NAME LIKE '%{$keyword}%'"; 
    }                   
    $sql = "
        SELECT 
            f.facility_id,f.facility_name
        FROM 
            romeo.facility f 
        WHERE 
            f.IS_CLOSED = 'N' " ." {$conditions}
        LIMIT {$limit}
    ";
    return $db->getAll($sql);
}
?>
