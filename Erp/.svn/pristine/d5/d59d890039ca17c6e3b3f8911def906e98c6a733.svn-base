<?php
/*
 * Created on 2015-1-27
 * 商品转仓规则管理
 */
define('IN_ECS', true);
require_once('../includes/init.php');

// 添加权限
admin_priv('goods_facility_mapping_manage');
 
require_once(ROOT_PATH . 'admin/distribution.inc.php');
require_once(ROOT_PATH . 'includes/cls_json.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . "admin/function.php");
require_once(ROOT_PATH . "includes/cls_mysql.php");

global $db;
 $goods_facility_mapping_page = false;
if ($_REQUEST['add_area']) {
    $goods_facility_mapping_page = true;
    
    
}

// var_dump($goods_facility_mapping_page);

$facility_list = array();
$act = $_REQUEST['act'];
$mapping_id = $_REQUEST['mapping_id']?$_REQUEST['mapping_id']:NULL;
$algorithm = $_REQUEST['algorithm'];
if($act == 'search_goods_and_taocan'){
   $limit = (isset($_POST['limit']) && is_numeric($_POST['limit'])) ? $_POST['limit'] : 30 ;
   $json = new JSON;
   print $json->encode(get_goods_and_taocan_list($_POST['q'], $limit)); 
   exit;
} else if($act == 'insert'){
      $mapping_id = $_REQUEST['hid_mapping_id'];
      $message = goods_facility_mapping_update($mapping_id  , $algorithm ); 
      $smarty->assign ( 'message', $message);
   
} else if ($act == 'delete'){
    $tip = delete_goods_facility_mapping($mapping_id);
    $smarty->assign('tip', $tip);
    if($algorithm == 'ALL_CONTAIN'){
      $goods_facility_mapping_page = true;
    }
   
} else if ($act == 'update'){
  if( $algorithm == 'ALL_CONTAIN' ){
    
      $sql = "select gfm.goods_name,gfm.start_time,gfm.end_time,gfm.facility_id,gfm.facility_name,gfm.distributor_id,gfm.shipping_id,gfm.shipping_name,
            IF(gfm.distributor_id = 0,'',tsc.nick) as distributor_name ,gfm.outer_id ,gfm.mapping_id,if(now() > gfm.start_time, 'N', 'Y') as start_can_update,if(now() > gfm.end_time, 'N', 'Y') as can_update
            from  ecshop.ecs_goods_facility_mapping  as gfm 
            left join ecshop.taobao_shop_conf as tsc on tsc.distributor_id = gfm.distributor_id AND tsc.party_id = gfm.party_id
          where gfm.mapping_id= '{$mapping_id}'";
      $goods_facility_mapping=$db->getRow($sql);
      
        $mapping_id = $goods_facility_mapping['mapping_id'];
        $goods_sql = " select  goods_name,outer_id from ecshop.ecs_goods_facility_mapping_goods_ref where  mapping_id = {$mapping_id} ; " ;
        $region_sql = " select rf.mapping_id , rf.region_id, rf.region_name, rf.region_type, r.parent_id from ecshop.ecs_goods_facility_mapping_region_ref rf
                        inner join ecshop.ecs_region r ON rf.region_id = r.region_id
                        where rf.mapping_id = {$mapping_id} ; " ;
        
        $goods_list = $db->getAll($goods_sql) ;
        $region_list = $db->getAll($region_sql) ;        
        
        //如果region为city（即region_type = 2），则需要提供parent_id
        foreach( $region_list as  &$region ){
        	if( $region['region_type'] == 2  ){
        		$sql_select_parent_id = "select parent_id from ecshop.ecs_region where region_id = {$region['region_id']} ";
        		$ecs_region = $db->getRow( $sql_select_parent_id );
        		$region['parent_id'] = $ecs_region['parent_id'];
        	}
		}
        
        $goods_facility_mapping['region_list'] = $region_list ;
        $goods_facility_mapping['goods_list'] = $goods_list ;
        
        $region_length = count($region_list);
        $goods_length =  count($goods_list) ;
        $arr_length = $region_length >=  $goods_length ? $region_length : $goods_length ;
        
        $goods_facility_mapping['arr_length'] = $arr_length ;
        
        $goods_facility_mapping_page = true;
          
  }else{
    $sql = "select gfm.goods_name,gfm.start_time,gfm.end_time,gfm.facility_id,gfm.facility_name,gfm.distributor_id,gfm.shipping_id,gfm.shipping_name,
            IF(gfm.distributor_id = 0,'',tsc.nick) as distributor_name ,gfm.outer_id ,gfm.mapping_id,if(now() > gfm.start_time, 'N', 'Y') as start_can_update,if(now() > gfm.end_time, 'N', 'Y') as can_update
            from  ecshop.ecs_goods_facility_mapping  as gfm 
            left join ecshop.taobao_shop_conf as tsc on tsc.distributor_id = gfm.distributor_id AND tsc.party_id = gfm.party_id
          where gfm.mapping_id= '{$mapping_id}'";
    $goods_facility_mapping = $db->getRow($sql);
  }
  $smarty->assign('view_only',$_REQUEST['view_only']);
  $smarty->assign('goods_facility_mapping', $goods_facility_mapping);
  
 } else if($act == 'goods_facility_search'){
        $shop_id = $_REQUEST['search_shop'];
        $facility_id = $_REQUEST['search_facility'];
        $type = $_REQUEST['search_type'];
        $outer_id = $_REQUEST['search_outer_id'];
        $tc_code = $_REQUEST['search_tc_code'];
        if($type == 'item'){
            $goods_id = $outer_id;
        }else{
            $goods_id = $tc_code;
        }
        $search_goods_facility_list = search_goods_facility_list($_SESSION['party_id'],$goods_id,$shop_id,$facility_id);  
        $smarty->assign('search_goods_facility_list', $search_goods_facility_list);
        $smarty->assign('search_facility_id',$facility_id);
        $smarty->assign('search_distributor_id',$shop_id);
  }
 
$available_shipping_list = getShippingTypes();
foreach ( $available_shipping_list as $key => $value ) {
       $available_shipping_list[$key] = $value['shipping_name'];
}
$smarty->assign( 'available_shipping',$available_shipping_list);
$smarty->assign( 'goods_facility_mapping_page',$goods_facility_mapping_page);
$smarty->assign ( 'available_distributor', get_distributor_list());
$smarty->assign ( 'available_facility', array_intersect_assoc ( get_available_facility (), get_user_facility () ) ); 
$smarty->assign('goods_facility_mapping_list',get_goods_facility_mapping_list());
$smarty->assign('goods_region_facility_mapping_list',get_goods_region_facility_mapping_list());


$province_list=$db->getAll("SELECT
  region_id,
  region_name
FROM
  ecs_region
WHERE
  region_type = 1
AND parent_id=1
");
$smarty->assign('province_list', $province_list);

$smarty->display('taobao/goods_facility_mapping.htm');


  /**
  * 判断商品转仓规则是否需要更新
  */
 function is_update($mapping_id,$start_time,$end_time, $facility_id, $shipping_id, $distributor_id){
    global $db;
        $sql =" select gfm.start_time,gfm.end_time,gfm.facility_id, gfm.shipping_id,gfm.distributor_id
              from  ecshop.ecs_goods_facility_mapping  as gfm 
            where gfm.mapping_id= '{$mapping_id}'";
    $result = $db->getRow($sql);
    if(($result['start_time'] == $start_time) && ($result['end_time'] == $end_time) && 
         ($result['facility_id'] == $facility_id) && ($result['shipping_id'] == $shipping_id) && ($result['distributor_id'] == $distributor_id)){
             return false;
        } 
        return true;
 }
 
  /**
  * 判断商品-区域转仓规则区域列表是否需要更新
  */
 function is_update_region_list($mapping_id,  $region_ids , &$region_ids_in_db  ){
    global $db;
    
    $region_sql = " select  region_id   from   ecshop.ecs_goods_facility_mapping_region_ref where  mapping_id = {$mapping_id} ; " ;
    $region_list = $db->getAll($region_sql) ;        
                
    
    foreach( $region_list as $region ){
      $region_ids_in_db[] = $region['region_id']; 
    }
    
    $need_update = ! is_array_equals( $region_ids_in_db , $region_ids );
    
    
    return $need_update ;
 }
 
  /**
  * 判断商品-区域转仓规则商品参照表是否需要更新
  */
 function is_update_goods_list($mapping_id,$outer_ids ,&$outer_ids_in_db ){
    global $db;
    
    $goods_sql = " select  outer_id   from   ecshop.ecs_goods_facility_mapping_goods_ref where  mapping_id = {$mapping_id} ; " ;
    $goods_list = $db->getAll($goods_sql) ;        
                
    
    foreach( $goods_list as $goods ){
      $outer_ids_in_db[] = $goods['outer_id']; 
    }
    
    $need_update = ! is_array_equals( $outer_ids_in_db , $outer_ids );
    
    
    return $need_update ;
 }
 
 
 /**
  * 判断两个数组的值是否完全相等
  */
  function is_array_equals($arr1 , $arr2){
    $a = array_diff($arr1,$arr2);
    $b = array_diff($arr2,$arr1);
    if(empty($a) && empty($b)){
        return true;
    }else{
        return false;
    }
    
  }
 
 
 
  /**
  * 删除商品转仓规则
  */
 function delete_goods_facility_mapping($mapping_id = null){
  $message ='';
  if(empty($mapping_id)){
    return false;
  }
  global $db;
    $delete_sql = " update ecshop.ecs_goods_facility_mapping set status='DELETED',".
                  " last_updated_by ='{$_SESSION['admin_name']}',last_updated_stamp = now()".
                  " where mapping_id = {$mapping_id} ";
    $result = $db->query($delete_sql);
    if ($result){
      record_action("delete",$delete_sql,'商品转仓规则删除');
      $message ='商品转仓规则删除【成功】';
    }else {
      $message ='商品转仓规则删除【失败】';
    }
    return $message;
 }
 
 /**
  * 同个店铺同个商品同个编码的转仓规则只能增加一个
  */
 function is_exists_goods_facility($outer_id,$distributor_id){
     $sql = "select 1 from ecshop.ecs_goods_facility_mapping 
         where outer_id = '{$outer_id}' and distributor_id = '{$distributor_id}' and status='OK' 
         ";
     $res = $GLOBALS['db']->getAll($sql);
     if(empty($res)){
         return false;
     }else{
         return true;
     }
 }
 
  /**
  * 添加或者更新商品转仓规则(适应商品-区域转仓规则)
  */
 function goods_facility_mapping_update($mapping_id = NULL , $algorithm ){
  
  $algorithm = $_REQUEST['algorithm'];
  global $db;
  $message ='';
  // 1 添加或者更新商品-区域转仓规则  
  if( $algorithm == 'ALL_CONTAIN' ){
    $outer_ids = $_REQUEST['outer_ids'];
    $goods_names = $_POST['goods_names'];
    
    $region_ids = $_REQUEST['region_ids']; 
    $region_types = $_REQUEST['region_types'];
    $region_names = $_REQUEST['region_names'];


    
    
    $start_time = $_REQUEST['start_time'];
    $end_time =   $_REQUEST['end_time'];
    $facility_id = $_REQUEST['facility_id'];
    $facility_name = $_REQUEST['facility_name'];
    $distributor_id = $_REQUEST['distributor_id'];
    $shipping_id = $_REQUEST['shipping_id'];
    $shipping_name = $_REQUEST['shipping_name'];
    
    
    if($distributor_id == '' || $distributor_id == null ){
      $distributor_id = 0;
    }
    if($shipping_id == '' || $shipping_id == null ){
      $shipping_id = 0;
    }
    
    
    
    if($mapping_id) {
       //update
       $outer_ids_in_db = array();
       $region_ids_in_db = array();
       
       
       $need_update =  is_update($mapping_id,$start_time,$end_time,$facility_id, $shipping_id, $distributor_id );  //基本信息是否需要更新
       $need_goods_update =  is_update_goods_list( $mapping_id, $outer_ids , $outer_ids_in_db );  //商品列表是否需要更新
       $need_regions_update =  is_update_region_list( $mapping_id, $region_ids, $region_ids_in_db );  //区域列表是否需要更新
       
       
       
       if( $need_update || $need_goods_update || $need_regions_update  ){
        if( $_REQUEST['can_update'] == 'Y'){
          
          $db->start_transaction();  //开启事务，保证数据一致性，出现错误就需要回滚 
          $result = 1;
//          echo "need_update" .$need_update  . "<br>";
//          echo "need_goods_update" .$need_goods_update . "<br>";
//          echo "need_regions_update" .$need_regions_update . "<br>";
         
		  // 1 更新基本信息         
          if($need_update) {
              $update_sql = " update ecshop.ecs_goods_facility_mapping set facility_name='{$facility_name}',facility_id ={$facility_id},".
                        " start_time='{$start_time}',end_time='{$end_time}',last_updated_by='{$_SESSION['admin_name']}',".
                        " last_updated_stamp=now() ,distributor_id = {$distributor_id} ,shipping_id = {$shipping_id} ,shipping_name = '{$shipping_name}'  where mapping_id= {$mapping_id}";
              $result = $db->query($update_sql);
          }
          
          // 2 更新商品列表
          if($need_goods_update && $result ){
            
            $outer_ids_to_delete = array_diff( $outer_ids_in_db , $outer_ids); //数据库列表中存在，但是更新列表中不存在的outer_id需要从数据库中删除
          	$outer_ids_to_add = array_diff($outer_ids,$outer_ids_in_db); //更新列表中存在，但是数据库列表中不存在的outer_id需要添加到数据库
            
            
          
	        foreach($outer_ids_to_delete as  $outer_id_to_delete ){ //暂时先这样写，后面换成 outer in ()的形式进行删除
	          $delete_goods_ref_sql = " delete from ecshop.ecs_goods_facility_mapping_goods_ref where mapping_id= {$mapping_id} and outer_id = '{$outer_id_to_delete}' ";
	          $result = $db->query($delete_goods_ref_sql);
	          if( ! $result  ) {
	            break;
	          }            
	          
	        }
//            echo "result outer_ids_to_delete". $result  . "<br>";
             
	         //insert商品-区域转仓规则商品映射表
	        if( $result ){
	    	  
	    	  
	          foreach( $outer_ids_to_add as $outer_id_to_add ){
	              $goods_length=count( $outer_ids );
	    		  
	    		  
		          for($i=0;$i<$goods_length;$i++) {
		          	
		          	
		            if($outer_id_to_add == $outer_ids[$i] ){
		            	
		              
		              $insert_goods_ref_sql = "insert into ecshop.ecs_goods_facility_mapping_goods_ref  
		                       (mapping_id,outer_id,goods_name) 
		                       values('{$mapping_id}','{$outer_ids[$i]}','{$goods_names[$i]}') ;";
		                       
			          
			          
		              $result =  $db->query($insert_goods_ref_sql);
		            }
		          }
		          
		          if( ! $result  ) {
		             break;
		          }    
	          }
	        }
	       
          }
          
          //3 更新区域列表
          if($need_regions_update && $result ){
            
            $region_ids_to_delete = array_diff( $region_ids_in_db , $region_ids); //数据库列表中存在，但是更新列表中不存在的region_id需要从数据库中删除
          	$region_ids_to_add = array_diff($region_ids,$region_ids_in_db); //更新列表中存在，但是数据库列表中不存在的region_id需要添加到数据库
            
            
          
	        foreach($region_ids_to_delete as  $region_id_to_delete ){ //暂时先这样写，后面换成 region in ()的形式进行删除
	          $delete_region_ref_sql = " delete from ecshop.ecs_goods_facility_mapping_region_ref where mapping_id= {$mapping_id} and region_id = '{$region_id_to_delete}' ";
	          $result = $db->query($delete_region_ref_sql);
	          if( ! $result  ) {
	            break;
	          }            
	          
	        }
             
	         //insert商品-区域转仓规则区域映射表
	        if( $result ){
	    	  
	          foreach( $region_ids_to_add as $region_id_to_add ){
	              $region_length=count( $region_ids );
	    		  
		          for($i=0;$i<$region_length;$i++) {
		          	
		          	
		            if($region_id_to_add == $region_ids[$i] ){
		              
		             $insert_region_ref_sql = " insert into ecshop.ecs_goods_facility_mapping_region_ref ". 
                  			 " (mapping_id, region_id,region_name,region_type )". 
                           " values (  '{$mapping_id}', '{$region_ids[$i]}', '{$region_names[$i]}','{$region_types[$i]}' )";
            		 $result =  $db->query($insert_region_ref_sql);
			          
		            }
		          }
		          
		          if( ! $result  ) {
		             break;
		          }    
	          }
	        }
	       
          }
          
          
          if ($result){
            $db->commit();
            record_action("update",$update_sql,'更新商品转仓规则');
            $message = "编辑商品-区域转仓规则【成功】。。。。。";
          }else{
            $db->rollback();
        	$message = "编辑商品-区域转仓规则【失败】。。。。。";
          }
        }
       }else {
          $message = "编辑商品-区域转仓规则【无效】。。。。。";
       }
    } 
    
    else {
    //insert
      
      $db->start_transaction();  //开启事务，保证数据一致性，出现错误就需要回滚 
      
      //insert商品-区域转仓规则表  
      $insert_goods_facility_sql = " insert into ecshop.ecs_goods_facility_mapping ". 
                    " (party_id,facility_id,facility_name, distributor_id, shipping_id,  shipping_name, status,start_time,".
                    " end_time,created_by,created_stamp,last_updated_by,last_updated_stamp , algorithm  )". 
                      " values ( {$_SESSION['party_id']}, {$facility_id}, '{$facility_name}',".
                      " {$distributor_id}, {$shipping_id}, '{$shipping_name}', 'OK','{$start_time}','{$end_time}','{$_SESSION['admin_name']}',now(),'{$_SESSION['admin_name']}',now() , '{$algorithm}' )";
                      
      $result = $db->query($insert_goods_facility_sql);
      $mapping_id = $db->insert_id();
      
      if($result && $mapping_id != 0 ){
        
      
        //insert商品-区域转仓规则商品映射表
        $goods_length=count( $outer_ids );

  
      for($i=0;$i<$goods_length;$i++) {
        
        $insert_goods_ref_sql = "insert into ecshop.ecs_goods_facility_mapping_goods_ref  
                 (mapping_id,outer_id,goods_name) 
                 values('{$mapping_id}','{$outer_ids[$i]}','{$goods_names[$i]}')";
          $result =  $db->query($insert_goods_ref_sql);
          
          if( ! $result  ) {
            break;
          }            
          
      }
        
        //insert商品-区域转仓规则区域映射表（当商品映射列表插入成功时，才能继续插入，否则需要回滚）
      
      if( $result ){
          $region_length=count( $region_ids );
          
        for($i=0;$i<$region_length;$i++) {
          $insert_region_ref_sql = " insert into ecshop.ecs_goods_facility_mapping_region_ref ". 
                   " (mapping_id, region_id,region_name,region_type )". 
                           " values (  '{$mapping_id}', '{$region_ids[$i]}', '{$region_names[$i]}','{$region_types[$i]}' )";
            $result =  $db->query($insert_region_ref_sql);
            
            if( ! $result  ) {
              break;
            }            
        }
      }
        
        if( $result  ) {
          $db->commit();
          record_action("add", $insert_goods_facility_sql,'添加商品-区域转仓规则');
            $message = "添加新的商品转仓-区域规则【成功】。。。。。";
       }else{
          $db->rollback();
          $message = "添加新的商品转仓-区域规则【失败】。。。。。";
       }        
          
      }else {
        $db->rollback();
        $message = "添加新的商品转仓-区域规则【失败】。。。。。";
      }
   
     
     
    }
     
  }
  // 2 添加或者更新商品转仓规则
  else{
    $outer_id ='';
      $type = $_REQUEST['hid_type'];
      if($type == 'item')
      {
          $outer_id = $_REQUEST['hid_outer_id'];
      }else if($type == 'taocan'){
        $outer_id = $_REQUEST['hid_tc_code'];
      }
      $goods_name = $_POST['goods_name'];
      $start_time = $_REQUEST['start_time'];
      // print_r($start_time.'start_time');
      // die;
      $end_time =   $_REQUEST['end_time'];

    $facility_id = $_REQUEST['facility_id'];
    $facility_name = $_REQUEST['facility_name'];
    $distributor_id = $_REQUEST['distributor_id'];
    $shipping_id = $_REQUEST['shipping_id'];
    $shipping_name = $_REQUEST['shipping_name'];
    
    $algorithm = $_REQUEST['algorithm'];
    
    if($distributor_id == '' || $distributor_id == null ){
      $distributor_id = 0;
    }
    $is_exists_goods_facility = is_exists_goods_facility($outer_id,$distributor_id);//同个店铺同个商品同个编码的转仓规则只能增加一个，检查是否重复添加
    
    if($mapping_id) {
       //update
         if(is_update($mapping_id,$start_time,$end_time,$facility_id, $shipping_id, $distributor_id)){
          if( $_REQUEST['can_update'] == 'Y'){
            $update_sql = " update ecshop.ecs_goods_facility_mapping set facility_name='{$facility_name}',facility_id ={$facility_id},".
                          " start_time='{$start_time}',end_time='{$end_time}',last_updated_by='{$_SESSION['admin_name']}',".
                          " last_updated_stamp=now() ,distributor_id = {$distributor_id} ,shipping_id = {$shipping_id} ,shipping_name = '{$shipping_name}'  where mapping_id= {$mapping_id}";
             $result = $db->query($update_sql);
               if ($result){
              record_action("update",$update_sql,'更新商品转仓规则');
              $message = "编辑商品转仓规则【成功】。。。。。";
               }
          }
         }else {
          $message = "编辑商品转仓规则【无效】。。。。。";
         }
    } else if($is_exists_goods_facility){
        $message = "该商品转仓规则已设置，请勿重复设置！";
    }else {
      //insert  
      $insert_sql = " insert into ecshop.ecs_goods_facility_mapping ". 
                    " (outer_id,party_id,goods_name,facility_id,facility_name, distributor_id, shipping_id,  shipping_name, status,start_time,".
                    " end_time,created_by,created_stamp,last_updated_by,last_updated_stamp  )". 
                      " values ('{$outer_id}', {$_SESSION['party_id']}, '{$goods_name}', {$facility_id}, '{$facility_name}',".
                      " {$distributor_id}, {$shipping_id}, '{$shipping_name}', 'OK','{$start_time}','{$end_time}','{$_SESSION['admin_name']}',now(),'{$_SESSION['admin_name']}',now() )";
      $result = $db->query($insert_sql);
      if($result){
        record_action("add", $insert_sql,'添加商品转仓规则');
        $message = "添加新的商品转仓规则【成功】。。。。。";
      }else {
        $message = "添加新的商品转仓规则【失败】。。。。。";
      }
    }
  }
    return $message;
 }
  
  /**
  * 获得商品转仓规则列表
  */
 function get_goods_facility_mapping_list(){
    global $db;
    $party_id = intval($_SESSION['party_id']);
    $sql = "
      select gfm.start_time,gfm.end_time,gfm.facility_name,gfm.mapping_id,gfm.goods_name,gfm.distributor_id, gfm.shipping_id, gfm.shipping_name,  IF(gfm.distributor_id = 0,'',tsc.nick) as distributor_name,
           gfm.last_updated_by, gfm.outer_id,gfm.facility_id,gfm.created_stamp,gfm.last_updated_stamp
    from   ecshop.ecs_goods_facility_mapping gfm
    left join ecshop.taobao_shop_conf as tsc on tsc.distributor_id = gfm.distributor_id and tsc.party_id = gfm.party_id
    where  gfm.party_id = {$party_id}  and gfm.status='OK' and gfm.algorithm ='ONCE_CONTAIN' ;
    ";
    $result=$db->getAll($sql);
    return $result;
  }
  
  /**
  * 获得商品-区域转仓规则列表
  */
 function get_goods_region_facility_mapping_list(){
    global $db;
    $party_id = intval($_SESSION['party_id']);
    $sql = "
      select gfm.start_time,gfm.end_time,gfm.facility_name,gfm.mapping_id,gfm.goods_name,gfm.distributor_id, gfm.shipping_id, gfm.shipping_name,  IF(gfm.distributor_id = 0,'',tsc.nick) as distributor_name,
           gfm.last_updated_by, gfm.outer_id,gfm.facility_id,gfm.created_stamp,gfm.last_updated_stamp
    from   ecshop.ecs_goods_facility_mapping gfm
    left join ecshop.taobao_shop_conf as tsc on tsc.distributor_id = gfm.distributor_id and tsc.party_id = gfm.party_id
    where  gfm.party_id = {$party_id}  and gfm.status='OK' and gfm.algorithm ='ALL_CONTAIN' ;
    ";
    $mapping_list=$db->getAll($sql);
    
    
    
    foreach ($mapping_list as &$mapping){
        $mapping_id = $mapping['mapping_id'];
        $goods_sql = " select  goods_name,outer_id from ecshop.ecs_goods_facility_mapping_goods_ref where  mapping_id = {$mapping_id} ; " ;
        $region_sql = " select mapping_id , region_id,  region_name , region_type from   ecshop.ecs_goods_facility_mapping_region_ref where  mapping_id = {$mapping_id} ; " ;
        
        $goods_list = $db->getAll($goods_sql) ;
        $region_list = $db->getAll($region_sql) ;        
        
        $mapping['region_list'] = $region_list ;
        $mapping['goods_list'] = $goods_list ;
        
        $region_length = count($region_list);
        $goods_length =  count($goods_list) ;
        $arr_length = $region_length >=  $goods_length ? $region_length : $goods_length ;
        
        $mapping['arr_length'] = $arr_length ;
        
    }
    
//    var_dump($mapping_list);
    return $mapping_list;
  }
  
    
  /**
  * 获得店铺列表
  */
  function get_distributor_list(){
    global $db;
    $party_id = intval($_SESSION['party_id']);
    $sql = "
      select tsc.distributor_id, tsc.nick as distributor_name
    from   ecshop.taobao_shop_conf as tsc
    where  tsc.party_id = {$party_id} and tsc.status ='OK';
    ";
    $db->getAllRefby($sql,array('distributor_id'),$ref_fields, $refs, false);
    
    if(!empty($refs['distributor_id']) ){
	    foreach ($refs['distributor_id'] as $distributor_id => $distributor_list){
	        $result[$distributor_id] = $distributor_list[0]['distributor_name'];
	    }
    }
    unset($refs);
    return $result;
  }
  
  
  /**
  * 获得商品名或套餐名列表
  */
 function get_goods_and_taocan_list($keyword = '', $limit = 100) {
    $conditions_item = '';
    $conditions_taocan = '';
    if (trim($keyword)) {
        $keyword = mysql_like_quote($keyword);
        $conditions_item .= " AND g.goods_name LIKE '%{$keyword}%' "; 
        $conditions_taocan .= " AND (dg.code like '%{$keyword}%' or dg.name like '%{$keyword}%')";
    }
    $sql = "
      select  *
    from  (
        SELECT   
                g.goods_id as goods_id,
          IF(gs.style_id = '' OR gs.style_id is NULL ,CONCAT_WS('_',g.goods_id,'0'),CONCAT_WS('_',g.goods_id,gs.style_id)) as outer_id,
                CONCAT_WS(' ', g.goods_name, IF( gs.goods_color = '', s.color, gs.goods_color)) as goods_name,
          'item' as type
            FROM 
                ecshop.ecs_goods AS g 
                LEFT JOIN ecshop.ecs_goods_style AS gs ON gs.goods_id = g.goods_id and gs.is_delete=0
                left join ecshop.ecs_style as s on gs.style_id = s.style_id
            WHERE 
                ( g.is_on_sale = 1 AND g.is_delete = 0 AND g.goods_party_id={$_SESSION['party_id']} ) 
                     {$conditions_item}
        union all
        SELECT
          0 as goods_id, `code` as outer_id,
          `name` as goods_name, 'taocan' as type
        FROM
          ecshop.distribution_group_goods dg
        WHERE 1 {$conditions_taocan}
         AND dg.party_id={$_SESSION['party_id']}
         AND dg.status = 'OK'
         limit {$limit}
      ) t
    ";
    return $GLOBALS['db']->getAll($sql);
  }
   
  /**
  * 记录当前用户对商品转仓规则的操作详情
  */
 function record_action($action=null,$sql =null,$comment =null){
  global $db;
  if($action == null && $sql == null && $comment ==null ){
    return ;
  }
  $record_sql = "INSERT INTO ecshop.risky_actions (admin_user, type, timestamp, php_location, form_name, `sql`, comment) " .
                  "VALUES ('{$_SESSION['admin_name']}', '{$action}', NOW(), 'goods_facility_mapping.php', 'myform', '{$action}_sql: ". mysql_real_escape_string($sql)."' ,'{$comment}')";
    $db->query($record_sql);
 }

/**
 * 搜索商品转仓规则
 */
function search_goods_facility_list($party_id,$outer_id,$shop_id,$facility_id){
    $sql_add = " ";
    if(!empty($outer_id)){
        $sql_add .= " and gfm.outer_id = '{$outer_id}' ";
    }
    if(!empty($shop_id)){
        $sql_add .= " and gfm.distributor_id = '{$shop_id}' ";
    }
    if(!empty($facility_id)){
        $sql_add .= " and gfm.facility_id = '{$facility_id}' ";
    }
    $sql = "select gfm.start_time,gfm.end_time,gfm.facility_name,gfm.mapping_id,gfm.goods_name,gfm.distributor_id, gfm.shipping_id, gfm.shipping_name,  IF(gfm.distributor_id = 0,'',tsc.nick) as distributor_name,
           gfm.last_updated_by, gfm.outer_id,gfm.facility_id,gfm.created_stamp,gfm.last_updated_stamp
        from   ecshop.ecs_goods_facility_mapping gfm
        left join ecshop.taobao_shop_conf as tsc on tsc.distributor_id = gfm.distributor_id and tsc.party_id = gfm.party_id
        where  gfm.party_id = {$party_id}  and gfm.status='OK' and gfm.algorithm ='ONCE_CONTAIN' ".$sql_add."  
        ";
    Qlog::log($sql);
    return $GLOBALS['db']->getAll($sql);
}
 ?>

 

