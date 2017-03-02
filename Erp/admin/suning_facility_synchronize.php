<?php 

/**
 * 分仓库同步
 * Created on 2015-9-1
 * 
 */
 
define('IN_ECS', true);
require_once('includes/init.php');
require_once('distribution.inc.php');
admin_priv('distribution_group_goods');
require_once(ROOT_PATH . 'includes/lib_order.php');
require_once(ROOT_PATH . 'includes/cls_json.php');
require_once(ROOT_PATH . 'includes/cls_page.php');
require_once(ROOT_PATH . 'includes/helper/array.php');  


$act =     // 动作
    isset($_REQUEST['act']) && 
    in_array($_REQUEST['act'], array('add_goods', 'verify', 'add', 'update', 'search','filter')) 
    ? $_REQUEST['act'] 
    : null ;
$info =    // 返回的信息
    isset($_REQUEST['info']) && trim($_REQUEST['info']) 
    ? urldecode($_REQUEST['info']) 
    : false ;
$page =    // 分页
    is_numeric($_REQUEST['page']) && ($_REQUEST['page'] > 0) 
    ? $_REQUEST['page'] 
    : 1 ;


/*
 * 处理post请求
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST) && $act) {

    switch ($act) {        
        /* 添加仓库 */
        case 'add' :
            $data=$_POST['sendData'];
            $slash=stripslashes($data);// 这里是用来处理前台传的json数组中的"被转化成\&quot;的问题，如果没有，后台无法通过下标获取值   
            $data2 = json_decode($slash, TRUE);//通过上一步的处理，这里用json_decode将json数组转化成关联数组
            
            // 创建条件
            $conditions = "";
            $len= count($data2);
            foreach ($data2 as $key => $item) {
              if(($key+1) == $len){
                $conditions.= "('".$item['facility_id']."','".$item['facility_code']."','".$item['is_sync']."','".$item['application_key']."','".$item['is_split_facility']."','OK')";
              }else{
                $conditions.= "('".$item['facility_id']."','".$item['facility_code']."','".$item['is_sync']."','".$item['application_key']."','".$item['is_split_facility']."','OK'),";
              }   
            }
            $sql = "INSERT INTO ecshop.sync_inventory_facility_mapping 
            (facility_id,facility_code,is_sync,application_key,is_split_facility,status) VALUES ".$conditions;
            $res = $db->query($sql);
            if($res){
              echo json_encode($res);
              $smarty->assign('message', '添加成功');
            }
        
          header("suning_facility_synchronize.php"); exit;
         
            
        break;
        
        
        /* 编辑仓库 */
        case 'update' :
            $data=$_POST['sendData'];
            $slash=stripslashes($data);// 这里是用来处理前台传的json数组中的"被转化成\&quot;的问题，如果没有，后台无法通过下标获取值   
            $data2 = json_decode($slash, TRUE);//通过上一步的处理，这里用json_decode将json数组转化成关联数组
            //var_dump($data2);
            // 保存仓库相关信息 
            foreach ($data2 as $key => $item) {
              $conditions = "";
              if(empty($item['mapping_id'])){ //编辑时新加入了仓库
                $conditions.= "('".$item['facility_id']."','".$item['facility_code']."','".$item['is_sync']."','".$item['application_key']."','".$item['is_split_facility']."','OK')";
                $sql_1 = "INSERT INTO ecshop.sync_inventory_facility_mapping (facility_id,facility_code,is_sync,application_key,is_split_facility,status) VALUES ".$conditions;
                $res = $db->exec($sql_1);
               
              }
              else{//更新原有的数据
                $conditions.="SET facility_id='".$item['facility_id']."',facility_code='".$item['facility_code']."',is_sync='".$item['is_sync']."',application_key='".$item['application_key']."',is_split_facility='".$item['is_split_facility']."' WHERE mapping_id=".$item['mapping_id'];
                $sql_2 = "UPDATE ecshop.sync_inventory_facility_mapping ".$conditions;
                $res = $db->exec($sql_2);
              }
            }
            echo json_encode($res);
            $smarty->assign('message', '更新成功');

          header("suning_facility_synchronize.php"); exit;
      break;
      /*验证新加仓库是否选择分仓与之前选择一致*/ 
      case 'verify':
            $application_key=$_POST['application_key'];
            $sql_3 = "SELECT DISTINCT is_split_facility from ecshop.sync_inventory_facility_mapping
                      WHERE application_key='{$application_key}' AND status='OK'";
            $res = $db->getRow($sql_3);
            if($res == false){
              $res['is_split_facility']="false";
              echo json_encode($res);exit;
            }else{
              echo json_encode($res);exit;
            }

      break; 
    }
}
//加载店铺列表
$party_id=$_SESSION['party_id'];
if($party_id ==120 || $party_id == 65535 || $party_id == 32640){
    $smarty->assign('message', '没有店铺信息,可能是没有选择组织');
}
$sql_1 = "SELECT DISTINCT taobao_shop_conf_id,application_key,distributor_id as taobao_shop_id,nick as taobao_shop_name
          from  ecshop.taobao_shop_conf  
          where party_id = '{$party_id}' and status = 'OK'";
    $taobao_shop_list = $db->getAll($sql_1);


    $smarty->assign('available_facility', get_available_facility());
 if ($taobao_shop_list) {
    $smarty->assign('taobao_shop_list', $taobao_shop_list);
}


// 信息
if ($info) {
    $smarty->assign('message', $info);
}


// 编辑或者删除模式
if (isset($_GET['mapping_id']) && is_numeric($_GET['mapping_id'])) {
  if($_GET['work'] == 'update' && isset($_GET['application_key'])){
      $group = taobao_shop_get_group_ref($_GET['application_key']);
        if ($group) {
            $smarty->assign('update', $group);  
        } else {
            $smarty->assign('message', '选择的仓库不存在');
        }
  }else if ($_GET['work']  == 'delete'){
    $sql = "UPDATE ecshop.sync_inventory_facility_mapping SET status='DELETE' WHERE mapping_id = ".$_GET['mapping_id'];
    $db->exec($sql);            
    header("Location: suning_facility_synchronize.php?info=". urlencode('删除成功')); exit;
  }  
}


// 查询条件
$conditions = NULL ;
if($_SERVER['REQUEST_METHOD'] == 'GET' && !empty($_GET) && $act == 'filter') {
  //传回前台显示
  $application_key = $_GET['taobao_shop_id'];
  $facility_id = $_GET['facility_id'];
  $smarty->assign('taobao_shop_id_selected', $application_key); 
  $smarty->assign('facility_id_selected', $facility_id); 

  if($application_key=='-1' && $facility_id !='-1'){
    $conditions = "AND sm.facility_id='{$facility_id}'";
  }
  elseif ($application_key !='-1' && $facility_id =='-1') {
    $conditions = "AND sm.application_key='{$application_key}'";
  }
  elseif ($application_key !='-1' && $facility_id !='-1') {
    $conditions = "AND sm.application_key='{$application_key}' AND sm.facility_id='{$facility_id}'";
  }
}
//var_dump($conditions);

  $total = $db->getOne("SELECT COUNT(*) FROM ecshop.sync_inventory_facility_mapping sm
LEFT JOIN ecshop.taobao_shop_conf tsc on sm.application_key=tsc.application_key
LEFT JOIN romeo.facility rf on sm.facility_id=rf.facility_id WHERE  tsc.party_id='{$party_id}' AND sm.status='OK'".$conditions);
 

// 分页 
$page_size = 15;  // 每页数量
$total_page = ceil($total/$page_size);  // 总页数
if ($page > $total_page) $page = $total_page;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $page_size;
$limit = $page_size;

// 展示表格
  $facility_list = $db->getAll("SELECT DISTINCT sm.*,nick as distributor_name,rf.facility_name 
    from ecshop.sync_inventory_facility_mapping sm
LEFT JOIN ecshop.taobao_shop_conf tsc on sm.application_key=tsc.application_key
LEFT JOIN romeo.facility rf on sm.facility_id=rf.facility_id WHERE  tsc.party_id='{$party_id}'
" .$conditions."AND sm.status='OK' ORDER BY application_key,facility_id LIMIT {$offset}, {$limit}");


$pagination = new Pagination($total, $page_size, $page, 'page', $url = 'suning_facility_synchronize.php', null, $extra_params);
$smarty->assign('total', $total);  // 总数
$smarty->assign('facility_list', $facility_list);//业务组仓库展示
$smarty->assign('pagination', $pagination->get_simple_output());  // 分页
$smarty->assign('filter', $filter);  // 查询条件
$smarty->display('suning_facility_synchronize.htm');

 
/**
  * 编辑时获得店铺列表
  */
 function taobao_shop_get_group_ref($application_key) {
    $sql = "SELECT sm.*,nick as distributor_name,rf.facility_name,tsc.party_id 
    from ecshop.sync_inventory_facility_mapping sm 
LEFT JOIN ecshop.taobao_shop_conf tsc on sm.application_key=tsc.application_key
LEFT JOIN romeo.facility rf on sm.facility_id=rf.facility_id
WHERE sm.application_key='{$application_key}' AND sm.status='OK' ORDER BY application_key,facility_id";
    return $GLOBALS['db']->getAll($sql);
  }
