<?php 
/**
 * 淘宝外包发货管理
 * 
 */
define('IN_ECS', true);
require('../includes/init.php');
require_once(ROOT_PATH . 'includes/cls_json.php');
require_once(ROOT_PATH . 'includes/cls_page.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'admin/function.php');
require_once(ROOT_PATH . 'includes/debug/lib_log.php');
require_once (ROOT_PATH . 'RomeoApi/lib_payment.php');
require_once (ROOT_PATH . 'includes/lib_order.php');
require_once (ROOT_PATH . 'includes/helper/array.php');
require_once (ROOT_PATH . 'includes/helper/uploader.php');
require_once (ROOT_PATH . 'includes/debug/lib_log.php');


$request = // 请求 
    isset($_REQUEST['request']) ? trim($_REQUEST['request']) : null ;
$act =     // 动作
    isset($_REQUEST['act']) && 
    in_array($_REQUEST['act'], array('add', 'update','check','upload','download_style','download_model_product' )) 
    ? $_REQUEST['act'] 
    : null ;
$page = 
    is_numeric($_REQUEST['page']) && ($_REQUEST['page'] > 0) 
    ? $_REQUEST['page'] 
    : 1 ;
$info = // 返回的信息
isset ( $_REQUEST ['info'] ) && trim ( $_REQUEST ['info'] ) ? urldecode ( $_REQUEST ['info'] ) : false;

$item_select =  // 调用模板
isset($_REQUEST['item_select'])  ? $_REQUEST['item_select'] : false ;

// 信息
if ($info) {
    $smarty->assign ( 'message', $info );
}

// 当前时间 
$now = date ( 'Y-m-d H:i:s' );

// excel读取设置
$tpl = 
array ('商品标签导入' => 
         array ('tag_name' => '标签名称', 
                'tag_category_id' => '标签类别', 
                'party_id' => '业务组', 
                ) );

 
 /* 处理ajax请求
 */
 
if ($request == 'ajax')
{
    switch ($act) 
    {
       
        case 'check':
        	 $outer_id = $_REQUEST['outer_id'];
        	 $tag_category_id = $_REQUEST['tag_category_id'];
        	 
			 $create_user = $_SESSION['admin_name'];
             $update_user = $_SESSION['admin_name'];
             $party_id = $_SESSION['party_id'];
             $sql ="INSERT INTO tags(tag_name,tag_category_id,party_id,creator,create_time,updater,update_time)
             VALUES('$outer_id','$tag_category_id',$party_id,'$create_user',NOW(),'$update_user',NOW())";
           
             $db->query($sql);
        break;
       
        case 'update':
             
        	 $outer_id = $_REQUEST['outer_id'];
        	 $old_name = $_REQUEST['old_name'];
        	 $tag_category_id = $_REQUEST['tag_category_id'];
        	 
             $update_user = $_SESSION['admin_name'];           
             $sql ="update tags set tag_name = '$old_name',tag_category_id='$tag_category_id'" .
             ",updater='$update_user',update_time=NOW() WHERE tag_id='".$outer_id."'"; 
             QLog::log($sql);           
             $db->query($sql);
            
        break;
    }
    	
    
    exit;
}

if ($_SERVER ['REQUEST_METHOD'] == 'POST' && $act) {
    
    switch ($act) {
        /**
         * 上传文件， 检查上传的excel格式，并读取数据提取并添加收款 
         */
        /* 检查数据  */
           
         case 'upload' :
        $uploader = new Helper_Uploader ();
            $max_size = $uploader->allowedUploadSize (); // 允许上传的最大值
            

            if (! $uploader->existsFile ( 'excel' )) {
                $smarty->assign ( 'message', '没有选择上传文件，或者文件上传失败' );
                echo'没有选择上传文件，或者文件上传失败';
                break;
            }
            
            // 取得要上传的文件句柄
            $file = $uploader->file ( 'excel' );
            
            // 检查上传文件
            if (! $file->isValid ( 'xls, xlsx', $max_size )) {
                $smarty->assign ( 'message', '非法的文件! 请检查文件类型类型(xls, xlsx), 并且系统限制的上传大小为' . $max_size / 1024 / 1024 . 'MB' );
                echo'非法的文件! 请检查文件类型类型(xls, xlsx),';
                break;
            }
            
            // 读取excel
            $result = excel_read ( $file->filepath (), $tpl, $file->extname (), $failed );
            if (! empty ( $failed )) {
                $smarty->assign ( 'message', reset ( $failed ) );
                var_dump($failed);
                break;
            }
         $rowset = $result ['商品标签导入'];
        $in_tag_name = Helper_Array::getCols ( $rowset, 'tag_name' );
            $in_tag_category_id = Helper_Array::getCols ( $rowset, 'tag_category_id' );
            $in_party_id = Helper_Array::getCols ( $rowset, 'party_id' );
        foreach ( $rowset as $key => $row ) {
            
            
            $create_user = $_SESSION['admin_name'];
            $update_user = $_SESSION['admin_name'];
            $aname=$row['tag_name'];
            $acategory=$row['tag_category_id'];
            $aparty=$row['party_id'];
            $sql ="INSERT INTO tags(tag_name,tag_category_id,party_id,creator,create_time,updater,update_time)
        VALUES('$aname','$aparty','$aparty','$create_user',NOW(),'$update_user',NOW()) ON DUPLICATE KEY 
        UPDATE tag_name='$aname',tag_category_id='$acategory',party_id='$aparty',updater='$update_user',update_time=NOW() ";

            $db->query($sql);
            
             
        }
        break;
        
        // exit ();
        
         case 'download_style' :
            {
//              Qlog::log('样式表下载');
                $sql = "select * from ecshop.tags";
                $tag_list = $db->getAll($sql);
                header ( "Content-type:application/vnd.ms-excel" );
                header ( "Content-Disposition:filename=" . iconv ( "UTF-8", "GB18030", "样式表" ) . ".csv" );
                $out = "标签名称,标签类别,业务组,标签创建者,创建时间,更新者,更新时间\n";
                foreach ( $tag_list as $key => $style ) {
                    $out .= $style ['tag_name'] . "," . $style ['tag_category_id'] . "," . $style ['party_id'] . "," . $style ['creator']. "," . $style ['create_time'] . "," . $style ['updater']."," . $style ['update_time']."\n" ;
                }
                echo iconv ( "UTF-8", "GB18030", $out );
                exit ();
            }
        case 'download_model_product' :
            {
                
                header ( "Content-type:application/vnd.ms-excel" );
                header ( "Content-Disposition:filename=" . iconv ( "UTF-8", "GB18030", "批量商品导入模板" ) . ".csv" );
                $out = "标签名称,标签类别,业务组\n";
                echo iconv ( "UTF-8", "GB18030", $out );
                exit ();
            }

    }
  
}


if (isset($_GET['id']) && is_numeric($_GET['id']) && 'findone'==$_GET['act']) {
	$update =  $db->getRow("SELECT t.tag_id,t.tag_name,ifnull(tc.tag_category_id,0) tag_category_id, tc.tag_category_name FROM ecshop.tags t left join ecshop.tag_category tc on t.tag_category_id=tc.tag_category_id WHERE tag_id = ".$_GET['id']);
	$smarty->assign('tag_id', $_GET['id']);
  	$smarty->assign('update', $update);
}

//用于模板<a>删除记录
if (isset($_GET['id']) && isset($_GET['act']) && 'delete'==$_GET['act']) {
	
	$sql = "delete from ecshop.tags where tag_id=".$_GET['id'];
	$db->query($sql);
}

//获取列表    IF(t.tag_category_id=0,tag_category_name,'')
function get_group_list($offset=0,$limit=0) {
	global  $db;
	$party_id= $_SESSION['party_id'];
//	$sql = "SELECT tag_id,tag_name,IF(t.tag_category_id!=0,tc.tag_category_name,'') tag_category_name,p.name,t.creator,t.create_time,t.updater,t.update_time " .
//			"from ecshop.tags t, ecshop.tag_category tc, romeo.party p " .
//			"WHERE t.tag_category_id = tc.tag_category_id AND t.party_id = p.PARTY_ID AND tc.party_id=".$party_id." limit ".$offset.",".$limit;	
	
	$sql = "SELECT tag_id,tag_name,ifnull(tc.tag_category_id,0) tag_category_id,IFNULL(tc.tag_category_name,'') tag_category_name,p.name,t.creator,t.create_time,t.updater,t.update_time " .
			"from ecshop.tags t inner join romeo.party p on t.party_id=p.party_id left join ecshop.tag_category tc on t.tag_category_id=tc.tag_category_id " .
			"WHERE t.party_id=".$party_id." limit ".$offset.",".$limit;	
	QLog::log($sql);	
	
	//select ecshop.tags.tag_name,romeo.party.name from ecshop.tags, romeo.party WHERE ecshop.tags.party_id =  romeo.party.PARTY_ID
	$group_list = $db->getAll($sql);
	QLog::log(var_export($group_list,true));
	return $db->getAll($sql);
}

function get_group_total() {
	global  $db;
	$party_id= $_SESSION['party_id'];
    $sql = "select count(*) from ecshop.tag_category where party_id=".$party_id ;
	return $db->getOne($sql);
}


//类别列表展示
$party_id= $_SESSION['party_id'];
$sql_category = "SELECT tag_category_id, tag_category_name FROM ecshop.tag_category, romeo.party " .
				"WHERE ecshop.tag_category.party_id = romeo.party.PARTY_ID and ecshop.tag_category.party_id =".$party_id;
$category_result = $db->getAll($sql_category);
$category = array();
foreach($category_result as $item){
	$category[$item['tag_category_id']] = $item['tag_category_name'];
}




//总记录数
$total = get_group_total();
// 分页 
$page_size = 30;  // 每页数量
$total_page = ceil($total/$page_size);  // 总页数
if ($page > $total_page) $page = $total_page;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $page_size;
$limit = $page_size;
//商品列表



$group_list = get_group_list($offset,$limit);
$pagination = new Pagination($total, $page_size, $page, 'page', $url = 'tags.php', null);
$smarty->assign('pagination', $pagination->get_simple_output());  
$smarty->assign('group_list', $group_list);
$smarty->assign('category', $category);  
$smarty->display("taobao/tags.htm");
?>