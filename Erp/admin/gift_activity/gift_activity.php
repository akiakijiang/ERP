<?php
define('IN_ECS', true);
require_once __DIR__.'/lib_gift_activity.php';
require_once __DIR__.'/init_gift_activity.php';
require_once(ROOT_PATH . 'admin/includes/cls_pagination.php');
require_once(ROOT_PATH . 'includes/cls_page.php');

admin_priv('gifts_manage');

/*
*	以下的一大堆list是在新增活动中的自动填充的内容
*/
// 获取商品数据（模糊匹配）
$goods_gift_list = GiftNewAdd::CreateGiftNewAdd_Goods_Group();//取goods_id（商品编码）和goods_name
$goods_gift = array();
foreach ($goods_gift_list as $key => $value) {
	$goods_gift[] = $value;//取键值对中的值放在数组里，取商品
	$goods_gift_no_or_style[] = $value;//无样式特征描述的商品，整个类目
}
// 获取无样式商品数量
$goods_gift_no_style_list = GiftNewAdd::CreateGiftNewAdd_Goods_Group_No_Style();
// var_dump($goods_gift_no_style_list);
// $goods_gift_no_or_style = array();
foreach ($goods_gift_no_style_list as $key => $value) {
	// $goods_gift_no_style[] = $value;
	$goods_gift_no_or_style[] = $value;
}
// var_dump($goods_gift_no_or_style);
//仓库
$facility_gift_list = GiftNewAdd::CreateGiftNewAdd_facility();
$facility_gift = array();
foreach ($facility_gift_list as $key => $value) {
	$facility_gift[] = $value;
}
//分销商
$distributor_gift_list = GiftNewAdd::CreateGiftNewAdd_distributor();
// var_dump($distributor_gift_list);
$distributor_gift = array();
foreach($distributor_gift_list as $key => $value) {
	$distributor_gift[] = $value;
}
//区域
$region_gift_list = GiftNewAdd::CreateGiftNewAdd_region();
$region_gift = array();
foreach ($region_gift_list as $key => $value) {
	$region_gift[] = $value;
}

$goods_cat_included_gift_list = GiftNewAdd::CreateGiftNewAdd_GoodsCat_Included_Excluded();
$goods_cat_included_gift = array();
foreach ($goods_cat_included_gift_list as $key => $value) {
	$goods_cat_included_gift[] = $value;
}
/*
*	判别在新增活动中的筛选类别,哎哟我去,好麻烦,还得通过传过来的name值去匹配相应的id
*/
if(isset($_REQUEST['act'])){
	if($_REQUEST['act']=='delete'){
		$delete_id = $_REQUEST['detele_id'];//获取ajax传来的delete_id
		$flag = $_REQUEST['data-flag'];//如果flag为had就是等级活动
		$afx = GiftActivity::removeGiftActivity($delete_id);//执行数据库操作删除这个活动？？？
		if($afx!=1){
			echo "未能成功删除";
		}else{
			if($flag == 'had'){//had等于等级
				$delete_result = GiftActivityLevelModel::delete($delete_id);//等级活动，区别？
			}
			echo "成功删除";
		}
		exit();
	}else if($_REQUEST['act']=='save_all'){//将modal中的所有数据保存
	
	
	    
		$session_id_party = $_SESSION['party_id'];//业务组
		$gift_activity_id = $_REQUEST['id'];          //传回来的id好像并没有什么卵用
		$active_name = $_REQUEST['active_name'];//活动名称
		$each_max_num = $_REQUEST['each_max_num'];//每单最多赠送限量
		$each_num = $_REQUEST['each_num'];//每单赠送数量

		$first_gift = $_REQUEST['first_gift'];//第一赠品
		$second_gift = $_REQUEST['second_gift'];//第二赠品
		$third_gift = $_REQUEST['third_gift'];//第三赠品

		$gift_limit_first = $_REQUEST['gift_limit_first'];//第一赠品数量
		$gift_limit_second = $_REQUEST['gift_limit_second'];//第二赠品数量
		$gift_limit_third = $_REQUEST['gift_limit_third'];//第三赠品数量
		$start_time = $_REQUEST['start_time'];//活动开始时间
		$finally_time = $_REQUEST['finally_time'];//活动结束时间
		$least_number = $_REQUEST['least_number'];//满赠件数
		$least_payment = $_REQUEST['least_payment'];//满赠金额
		$repeat_type = $_REQUEST['repeat_type'];//叠加类型
		$activity_cue = $_REQUEST['activity_cue'];//活动暗号
		$activity_type = $_REQUEST['activity_type'];//活动类型：常规或者等级
		$class_activity_add_string = $_REQUEST['class_activity_add_string'];//如果为空则表示常规活动，如果不为空则为满赠活动中所有内容的组合
		if($class_activity_add_string == ""){//常规活动
			foreach ($goods_gift_list as $key => $value) {
				if($value == $first_gift){
					$first_gift_key = $key;//取第一赠品编码
				}
				if($value == $second_gift){
					$second_gift_key = $key;//取第二赠品编码
				}
				if($value == $third_gift){
					$third_gift_key = $key;//取第三赠品编码
				}
			}
		}else{//等级活动无叠加类型和每单赠送限量，所以这里设置为默认值
			$repeat_type = "ONCE";
			$each_max_num = 999;
		}

		$params['party_id'] = $session_id_party;//业务组ID存到params中，传到lib的PHP文件中进行数据库存储
		$params['gift_activity_name'] = $active_name;//活动ID
		$params['begin_time'] = $start_time;//开始时间
		$params['end_time'] = $finally_time;//结束时间
		$params['gift_first'] = $first_gift_key;//第一赠品编码
		$params['gift_second'] = $second_gift_key;//第二赠品编码
		$params['gift_third'] = $third_gift_key;//第三赠品编码
		$params['gift_limit_first'] = $gift_limit_first;//第一赠品数量
		$params['gift_limit_second'] = $gift_limit_second;//第二赠品数量
		$params['gift_limit_third'] = $gift_limit_third;//第三赠品数量
		$params['gift_number_once'] = $each_num;//每单赠送数量
		$params['gift_number_once_max'] = $each_max_num;//每单最多赠送限量
		$params['least_number'] = $least_number;//满赠数量
		$params['least_payment'] = $least_payment;//满赠金额
		$params['repeat_type'] = $repeat_type;//叠加类型
		$params['activity_type'] = $activity_type;//活动类型

		$facility_check_state = $_REQUEST['facility_check_state'];//仓库是否不限制
		$distributor_check_state = $_REQUEST['distributor_check_state'];//分销商是否不限制
		$region_check_state = $_REQUEST['region_check_state'];//地区是否不限制
		$goods_check_state = $_REQUEST['goods_check_state'];//参与商品是否不限制
		$goods_necessary_check_state = $_REQUEST['goods_necessary_check_state'];//固定商品
		$goods_necessary_limit_state = $_REQUEST['goods_necessary_limit_state'];//固定商品是否全部限制
		$goods_excluded_check_state = $_REQUEST['goods_excluded_check_state'];//排除商品
		$cat_included_check_state = $_REQUEST['cat_included_check_state'];//类目
		$cat_excluded_check_state = $_REQUEST['cat_excluded_check_state'];//排除类目
        if($goods_necessary_limit_state == 'true'){
			$params['necessary_all'] = 1;//存0表示不限制
		}else{
			$params['necessary_all'] = 0;
		}
		$facility = $_REQUEST['facility'];//仓库
		$distributor = $_REQUEST['distributor'];//分销商
		$region = $_REQUEST['region'];//区域
		$goods = $_REQUEST['goods'];//商品
		$goods_necessary = $_REQUEST['goods_necessary'];//固定商品		
		$goods_excluded_cache = $_REQUEST['goods_excluded_cache'];//参与商品缓存
		$cat_included_cache = $_REQUEST['cat_included_cache'];//参与类目缓存
		$cat_excluded_cache = $_REQUEST['cat_excluded_cache'];//排除类目缓存

		$facility_array = explode(',',$facility);//多个仓库用逗号划分
		$distributor_array = explode(',',$distributor);
		$region_array = explode(',',$region);
		$goods_array = explode(',',$goods);
		$goods_necessary_array = explode(',',$goods_necessary);
		$goods_excluded_cache_array = explode(',',$goods_excluded_cache);
		$cat_included_cache_array = explode(',',$cat_included_cache);
		$cat_excluded_cache_array = explode(',',$cat_excluded_cache);

		$lists['ANGOU'][] = $activity_cue; //活动暗号
		if($goods_necessary_check_state == 'checked'){//不限制
			$lists['GOODS_NECESSARY'][] = 0;//存0表示不限制
		}else{
			foreach ($goods_necessary_array as $key => $value) {//取固定商品名
				foreach ($goods_gift_list as $num => $name) {//取所有商品名
					if($value == $name){//对比名称
						$lists['GOODS_NECESSARY'][] = $num;//存储商品编码
					}
				}
			}
			if(empty($lists['GOODS_NECESSARY'])){
				$lists['GOODS_NECESSARY'] = array();
			}
		}
		//仓库，同上
		if($facility_check_state == 'checked'){
			$lists['FACILITY'][] = 0;
		}else{
			foreach ($facility_array as $key => $value) {
				foreach ($facility_gift_list as $num => $name) {
					if($value == $name){
						$lists['FACILITY'][] = $num;
					}
				}
			}
			if(empty($lists['FACILITY'])){
				$lists['FACILITY'] = array();
			}
		}
		//分销商
		if($distributor_check_state == 'checked'){
			$lists['DISTRIBUTOR'][] = 0;
		}else{
			foreach ($distributor_array as $key => $value) {
				foreach ($distributor_gift_list as $num => $name) {
					if($value == $name){
						$lists['DISTRIBUTOR'][] = $num;
					}
				}
			}
			if(empty($lists['DISTRIBUTOR'])){
				$lists['DISTRIBUTOR'] = array();
			}
		}
		//区域
		if($region_check_state == 'checked'){
			$lists['REGION'][] = 0;
		}else{
			foreach ($region_array as $key => $value) {
				foreach ($region_gift_list as $num => $name) {
					if($value == $name){
						$lists['REGION'][] = $num;
					}
				}
			}
			if(empty($lists['REGION'])){
				$lists['REGION'] = array();
			}
		}
		//参与商品
		if($goods_check_state == 'checked'){
			$lists['GOODS_INCLUDED'][] = 0;
		}else{
			foreach ($goods_array as $key => $value) {
				foreach ($goods_gift_list as $num => $name) {
					if($value == $name){
						$lists['GOODS_INCLUDED'][] = $num;
					}
				}
			}
			if(empty($lists['GOODS_INCLUDED'])){
				$lists['GOODS_INCLUDED'] = array();
			}
		}
		//排除商品
		if($goods_excluded_check_state == 'checked'){
			$lists['GOODS_EXCLUDED'][] = 0;
		}else{
			foreach ($goods_excluded_cache_array as $key => $value) {
				foreach ($goods_gift_list as $num => $name) {
					if($value == $name){
						$lists['GOODS_EXCLUDED'][] = $num;
					}
				}
			}
			if(empty($lists['GOODS_EXCLUDED'])){
				$lists['GOODS_EXCLUDED'] = array();
			}
		}
		//参与类目
		if($cat_included_check_state == 'checked'){
			$lists['GOODS_CAT_INCLUDED'][] = 0;
		}else{
			foreach ($cat_included_cache_array as $key => $value) {
				foreach ($goods_cat_included_gift_list as $num => $name) {
					if($value == $name){
						$lists['GOODS_CAT_INCLUDED'][] = $num;
					}
				}
			}
			if(empty($lists['GOODS_CAT_INCLUDED'])){
				$lists['GOODS_CAT_INCLUDED'] = array();
			}
		}
		//排除类目
		if($cat_excluded_check_state == 'checked'){
			$lists['GOODS_CAT_EXCLUDED'][] = 0;
		}else{
			foreach ($cat_excluded_cache_array as $key => $value) {
				foreach ($goods_cat_included_gift_list as $num => $name) {
					if($value == $name){
						$lists['GOODS_CAT_EXCLUDED'][] = $num;
					}
				}
			}
			if(empty($lists['GOODS_CAT_EXCLUDED'])){
				$lists['GOODS_CAT_EXCLUDED'] = array();
			}
		}
		$message='';
		global $db;
		if($gift_activity_id != ''){//活动ID。有ID表明是修改，没有表示新建			
			try{
				if($class_activity_add_string != ""){//等级活动数据修改
					$class_activity_add_string_array = explode('?',$class_activity_add_string);//通过问号拆分字符串
					for($i=0;$i<count($class_activity_add_string_array);$i++){
						$class_activity_add_string_local_array = explode(",",$class_activity_add_string_array[$i]);//再通过逗号拆分字符串
						$class_activity_add_string_finally_array[] = $class_activity_add_string_local_array;
					}
					for($m=0;$m<count($class_activity_add_string_finally_array);$m++){
						if(is_array($class_activity_add_string_finally_array[$m])){
							foreach ($goods_gift_list as $class_activity_add_update_key => $class_activity_add_update_value) {
								if($class_activity_add_update_value == $class_activity_add_string_finally_array[$m][0]){
									$class_activity_add_string_finally_array[$m][0] = $class_activity_add_update_key;
								}
							}
						}
					} 
					$params['level'] = true;
					$params['level_data'] = $class_activity_add_string_finally_array;
				}
				$update_result = GiftActivity::updateGiftActivity($gift_activity_id,$params,$lists,$message);//更新数据
				if($update_result){					
					echo "数据修改成功。".$message;
				}else{
					echo "数据修改失败。".$message;
				}
			}catch(Exception $e){
				echo "数据修改失败。";
				exit();
			}
		}else{
			try{
				if($class_activity_add_string != ""){//等级活动创建
					$class_activity_add_string_array = explode('?',$class_activity_add_string);
					for($i=0;$i<count($class_activity_add_string_array);$i++){
						$class_activity_add_string_local_array = explode(",",$class_activity_add_string_array[$i]);
						$class_activity_add_string_finally_array[] = $class_activity_add_string_local_array;
					}
					for($m=0;$m<count($class_activity_add_string_finally_array);$m++){
						if(is_array($class_activity_add_string_finally_array[$m])){
							foreach ($goods_gift_list as $keys => $values) {
								if($values == $class_activity_add_string_finally_array[$m][0]){
									$class_activity_add_string_finally_gift = $keys;
								}
							}
							$class_activity_add_params['gift'] = $class_activity_add_string_finally_gift;
							$class_activity_add_params['gift_limit'] = $class_activity_add_string_finally_array[$m][1];
							$class_activity_add_params['least_payment'] = $class_activity_add_string_finally_array[$m][2];
							$class_activity_add_params['least_number'] = $class_activity_add_string_finally_array[$m][3];
							$class_activity_add_params['gift_number'] = $class_activity_add_string_finally_array[$m][4];
							$class_activity_add_params_finally[] = $class_activity_add_params;
						}
					}
					$params['level'] = true;
					$params['level_data'] = $class_activity_add_params_finally;
				}
				$create_result = GiftActivity::createGiftActivity($params,$lists,$message);
				if($create_result){	
					echo "新建成功。".$message;
				}else{
					echo "新建失败。".$message;
				}
			}catch(Exception $e){
				echo "新建失败。";
				exit();
			}
		}
		exit();
	}
}

/*************************************************************/

// function testAddGA(){                 //这个是做测试生成的页面的
// 	$message='';
// 	$params=array();
// 	$params['party_id']=$_SESSION['party_id'];
// 	$params['gift_activity_name']="快来打我啊";
// 	$params['begin_time']="2015-09-08 09:23:12";
// 	$params['end_time']="2015-09-10 09:11:22";
// 	$params['gift_first']="195527";
// 	$params['gift_second']="";
// 	$params['gift_third']="";
// 	$params['gift_limit_first']="1000";
// 	$params['gift_limit_second']="";
// 	$params['gift_limit_third']="";
// 	$params['gift_number_once']="1";
// 	$params['gift_number_once_max']="5";
// 	$params['least_number']="";
// 	$params['least_payment']="1";
// 	$params['repeat_type']="BY_NUMBER";
// 	$lists=array(
// 		'DISTRIBUTOR'=>array(),
// 		'FACILITY'=>array(),
// 		'REGION'=>array(),
// 		'GOODS_INCLUDED'=>array(),
// 		'GOODS_EXCLUDED'=>array(),
// 		'GOODS_CAT_INCLUDED'=>array(),
// 		'GOODS_CAT_EXCLUDED'=>array(),
// 		);
// 	$newGAId=GiftActivity::createGiftActivity($params,$lists,$message);
// }
$params=array();
$params['party_id']=$_SESSION['party_id'];
// var_dump($params['party_id']);
// $goods_necessary_display = "";
// if($_SESSION['party_id'] == "65628"){           //  针对 LA MER海蓝之谜
	// $goods_necessary_display = "show";           // 这个现在已经没用了，因为不限制了，所有的都能看到固定商品
// }
$offset= 0;
$limit = 10;		//分页的话这里还得问清楚每一页到底显示几条
if(isset($_REQUEST['fenye'])){
	$page = $_REQUEST['page'];
	$offset = $limit * ($page-1);
}
// $form_search['active_name'] = "";
// $form_search['start_time'] = "";
// $form_search['end_time'] = "";
// $form_search['distributor_name'] = "";
// $form_search['activity_cue'] = "";
if(isset($_REQUEST['form_search_hidden'])){//查询信息
	foreach($distributor_gift_list as $key => $value){//分销商名字（店铺名称），循环进行匹配
		if(!empty($_REQUEST['form_search_distributor_name'])){
			if(strstr($value,$_REQUEST['form_search_distributor_name'])){//所有包含包含该分销商名称的值取出来（模糊匹配）
				$form_search_distributor_name[] = $key;//将所有匹配上的分销商所对应的编码存进数组
			}
		}
	}
	if(!empty($form_search_distributor_name)){//如果分销商数组不为空
		$form_search_distributor_name_final = implode(',',$form_search_distributor_name);//将数组切割
	}
	$_SESSION['form_search_active_name'] = $_REQUEST['form_search_active_name'];//请求活动名称，选择页码的时候会丢失筛选条件，所以将筛选条件放进session中
	$_SESSION['form_search_start_time'] = $_REQUEST['form_search_start_time'];//请求开始时间
	$_SESSION['form_search_end_time'] = $_REQUEST['form_search_end_time'];//请求结束时间
	$_SESSION['distributor_name'] = $form_search_distributor_name_final;//店铺名称
	$_SESSION['form_search_activity_cue'] = $_REQUEST['form_search_activity_cue'];//活动暗号
}
if(isset($_REQUEST['delete_session'])){//删除活动,清空值
	$_SESSION['form_search_active_name'] = "";
	$_SESSION['form_search_start_time'] = "";
	$_SESSION['form_search_end_time'] = "";
	$_SESSION['distributor_name'] = "";
	$_SESSION['form_search_activity_cue'] = "";
}
$form_search['active_name'] = $_SESSION['form_search_active_name'];//将对应的值分别保存到另一个数组中
$form_search['start_time'] = $_SESSION['form_search_start_time'];
$form_search['end_time'] = $_SESSION['form_search_end_time'];
$form_search['distributor_name'] = $_SESSION['distributor_name'];
$form_search['activity_cue'] = $_SESSION['form_search_activity_cue'];
$list=GiftActivity::listGiftActivity($params,$limit,$offset,$count, $form_search);//搜索并获取搜索值
// var_dump($list);
//分页
$extra_params = array('fenye'=>'link');
$total = $count;
$total_list = sizeof($list);
$page = is_numeric($_REQUEST['page']) && ($_REQUEST['page'] > 0) ? $_REQUEST['page'] : 1 ;//当前页数
$total_page = ceil($total/$limit);
$page = max(1,min($page,$total_page));
$pagination = new Pagination($total, $limit, $page, 'page', $url = 'gift_activity.php', null, $extra_params);
$simple_output = $pagination->get_simple_output();

$final_result = "";
// var_dump($list);
foreach ($list as $key => $value){//分解搜索值,value是对应的值
	if(is_array($value)){
		$goods_necessary_i = 0;
		$distributor_i = 0;
		$facility_i = 0;
		$region_i = 0;
		$goods_included_i = 0;
		$goods_excluded_i = 0;
		$goods_cat_included_i = 0;
		$goods_cat_excluded_i = 0;
        
        $necessary_all = $value['necessary_all'];
        
		if(is_array($value['_GOODS_NECESSARY'])){
			$goods_necessary_count = count($value['_GOODS_NECESSARY']);//固定商品个数
			for($i=0; $i < $goods_necessary_count-1; $i++) { 
				$goods_necessary_cache_array[] = $value['_GOODS_NECESSARY'][$i];//将固定商品放进数组
				$goods_necessary .= $value['_GOODS_NECESSARY'][$i]."<br><br>";//拼接标签和固定商品
			}
			$goods_necessary_cache_array[] = $value['_GOODS_NECESSARY'][$goods_necessary_count-1];//最后一个
			$goods_necessary .= $value['_GOODS_NECESSARY'][$goods_necessary_count-1];//再加上最后一个，此时无须br标签
		}
		if(is_array($value['_DISTRIBUTOR'])){
			$distributor_count = count($value['_DISTRIBUTOR']);
			if($distributor_count <=5){//分销商小于5个的时候用逗号拼接
				for($i=0; $i < $distributor_count-1; $i++) { 
					$distributor_cache_array[] = $value['_DISTRIBUTOR'][$i];
					$distributor .= $value['_DISTRIBUTOR'][$i].",";
				}
				$distributor_cache_array[] = $value['_DISTRIBUTOR'][$distributor_count-1];
				$distributor .= $value['_DISTRIBUTOR'][$distributor_count-1];
			}else{//如果大于5个，则前四个用逗号拼接，第五个用省略号拼接，
				for($i=0; $i < $distributor_count-1; $i++){
					$distributor_cache_array[] = $value['_DISTRIBUTOR'][$i];
					if($i<4){
						$distributor .= $value['_DISTRIBUTOR'][$i].",";
					}elseif ($i == 4) {
						$distributor .= $value['_DISTRIBUTOR'][$i]."...";
					}else{
						$distributor_title .= $value['_DISTRIBUTOR'][$i].",";//剩下的拼接到tips上，此处用title变量表示
					}
				}
				$distributor_cache_array[] = $value['_DISTRIBUTOR'][$distributor_count-1];
				$distributor_title .= $value['_DISTRIBUTOR'][$distributor_count-1];
			}
		}
		if(is_array($value['_FACILITY'])){//仓库拼接，解释同上
			$facility_count = count($value['_FACILITY']);
			if($facility_count <=5){
				for($i=0; $i < $facility_count-1; $i++) { 
					$facility_cache_array[] = $value['_FACILITY'][$i];
					$facility .= $value['_FACILITY'][$i].",";
				}
				$facility_cache_array[] = $value['_FACILITY'][$facility_count-1];
				$facility .= $value['_FACILITY'][$facility_count-1];
			}else{
				for($i=0; $i < $facility_count-1; $i++){
					$facility_cache_array[] = $value['_FACILITY'][$i];
					if($i<4){
						$facility .= $value['_FACILITY'][$i].",";
					}elseif ($i == 4) {
						$facility .= $value['_FACILITY'][$i]."...";
					}else{
						$facility_title .= $value['_FACILITY'][$i].",";
					}
				}
				$facility_cache_array[] = $value['_FACILITY'][$facility_count-1];
				$facility_title .= $value['_FACILITY'][$facility_count-1];
			}
		}
		if(is_array($value['_REGION'])){//地区拼接，解析同上
			$region_count = count($value['_REGION']);
			if($region_count <=5){
				for($i=0; $i < $region_count-1; $i++) { 
					$region_cache_array[] = $value['_REGION'][$i];
					$region .= $value['_REGION'][$i].",";
				}
				$region_cache_array[] = $value['_REGION'][$region_count-1];
				$region .= $value['_REGION'][$region_count-1];
			}else{
				for($i=0; $i < $region_count-1; $i++){
					$region_cache_array[] = $value['_REGION'][$i];
					if($i<4){
						$region .= $value['_REGION'][$i].",";
					}elseif ($i == 4) {
						$region .= $value['_REGION'][$i]."...";
					}else{
						$region_title .= $value['_REGION'][$i].",";
					}
				}
				$region_cache_array[] = $value['_REGION'][$region_count-1];
				$region_title .= $value['_REGION'][$region_count-1];
			}
		}
		if(is_array($value['_GOODS_INCLUDED'])){//参与商品名称拼接
			$goods_included_count = count($value['_GOODS_INCLUDED']);
			for($i=0; $i < $goods_included_count-1; $i++) { 
				$goods_included_cache_array[] = $value['_GOODS_INCLUDED'][$i];
				$goods_included .= $value['_GOODS_INCLUDED'][$i]."<br><br>";
			}
			$goods_included_cache_array[] = $value['_GOODS_INCLUDED'][$goods_included_count-1];
			$goods_included .= $value['_GOODS_INCLUDED'][$goods_included_count-1];
		}
		if(is_array($value['_GOODS_EXCLUDED'])){//排除商品拼接
			$goods_excluded_count = count($value['_GOODS_EXCLUDED']);
			for($i=0; $i < $goods_excluded_count-1; $i++) { 
				$goods_excluded_cache_array[] = $value['_GOODS_EXCLUDED'][$i];
				$goods_excluded .= $value['_GOODS_EXCLUDED'][$i]."<br><br>";
			}
			$goods_excluded_cache_array[] = $value['_GOODS_EXCLUDED'][$goods_excluded_count-1];
			$goods_excluded .= $value['_GOODS_EXCLUDED'][$goods_excluded_count-1];
		}
		if(is_array($value['_GOODS_CAT_INCLUDED'])){//参与类目拼接
			$goods_cat_included_count = count($value['_GOODS_CAT_INCLUDED']);
			for($i=0; $i < $goods_cat_included_count-1; $i++) { 
				$goods_cat_included_cache_array[] = $value['_GOODS_CAT_INCLUDED'][$i];
				$goods_cat_included .= $value['_GOODS_CAT_INCLUDED'][$i]."<br><br>";
			}
			$goods_cat_included_cache_array[] = $value['_GOODS_CAT_INCLUDED'][$goods_cat_included_count-1];
			$goods_cat_included .= $value['_GOODS_CAT_INCLUDED'][$goods_cat_included_count-1];
		}
		if(is_array($value['_GOODS_CAT_EXCLUDED'])){//排除类目拼接
			$goods_cat_excluded_count = count($value['_GOODS_CAT_EXCLUDED']);
			// if($goods_cat_excluded_count <=5){
			for($i=0; $i < $goods_cat_excluded_count-1; $i++) { 
				$goods_cat_excluded_cache_array[] = $value['_GOODS_CAT_EXCLUDED'][$i];
				$goods_cat_excluded .= $value['_GOODS_CAT_EXCLUDED'][$i]."<br><br>";
			}
			$goods_cat_excluded_cache_array[] = $value['_GOODS_CAT_EXCLUDED'][$goods_cat_excluded_count-1];
			$goods_cat_excluded .= $value['_GOODS_CAT_EXCLUDED'][$goods_cat_excluded_count-1];
			// }else{
			// 	for($i=0; $i < $goods_cat_excluded_count-1; $i++){
			// 		$goods_cat_excluded_cache_array[] = $value['_GOODS_CAT_EXCLUDED'][$i];
			// 		if($i<4){
			// 			$goods_cat_excluded .= $value['_GOODS_CAT_EXCLUDED'][$i].",";
			// 		}elseif ($i == 4) {
			// 			$goods_cat_excluded .= $value['_GOODS_CAT_EXCLUDED'][$i]."...";
			// 		}else{
			// 			$goods_cat_excluded_title .= $value['_GOODS_CAT_EXCLUDED'][$i].",";
			// 		}
			// 	}
			// 	$goods_cat_excluded_cache_array[] = $value['_GOODS_CAT_EXCLUDED'][$goods_cat_excluded_count-1];
			// 	$goods_cat_excluded_title .= $value['_GOODS_CAT_EXCLUDED'][$goods_cat_excluded_count-1];
			// }
		}
		if(is_array($value['ANGOU'])){//获取暗号
			$activity_cue_count = count($value['ANGOU']);
			for($i=0;$i<$activity_cue_count;$i++){
				$activity_cue .= $value['ANGOU'][$i];
			}	
		}

		if($value['repeat_type'] == 'BY_NUMBER'){
			$repeat_type_result = "按件数叠加";
		}else if($value['repeat_type'] == 'ONCE'){
			$repeat_type_result = "不叠加";
		}else if($value['repeat_type'] == 'BY_PAYMENT'){
			$repeat_type_result = "按价格叠加";
		}
		// 固定商品
		$goods_necessary_content = "";
		if($goods_necessary !="" && $goods_necessary != "[0]"){//[0]表示不限制
			$goods_necessary_content = "<p class='td_goods_necessary'>".$goods_necessary."</p>";
		}
		$activity_cue_content = "";
		if($activity_cue !=""){
			$activity_cue_content = "<p class='activity_cue' id='activity_cue_".$value['gift_activity_id']."'>".$activity_cue."</p>";//gift_activity_id表示活动ID

		}

		$now_date = date('Y-m-d H:i:s');
		$state = "";//根据时间判断活动状态再设置button样式
		if($now_date < $value['begin_time']){
			$state = "未开始";
			$change_button_state = "";
			$delete_button_state = "";
			$change_button_class="btn-primary";
			$delete_button_class="btn-danger";
		}else if($value['begin_time'] <= $now_date && $now_date <= $value['end_time']){
			$state = "进行中";
			$change_button_state = "";
			$delete_button_state = "disabled='disabled'";
			$change_button_class="btn-primary";
			$delete_button_class="disabled_delete_button_background";

		}else if($value['end_time'] < $now_date){
			$state = "已结束";
			$change_button_state = "disabled='disabled'";
			$delete_button_state = "";
			$change_button_class="btn disabled_change_button_background";
			$delete_button_class="btn-danger";

		}
		if($value['activity_type'] != 'NORMAL'){//活动类型是等级活动
			$list_class_add_result = GiftActivityLevelModel::select($value['gift_activity_id']);//根据活动ID取ecs_gift_activity_level表中的所有内容
			$normal_class_gift_local_content = "<div class='control_td_height'>";
			$normal_class_gift_number_once_local_content = "<div class='control_td_height'>";//每单赠送量
			$normal_class_gift_least_number_local_content = "<div class='control_td_height'>";//满赠件数
			$normal_class_gift_least_payment_local_content = "<div class='control_td_height'>";//满赠金额
			foreach ($list_class_add_result as $key => $list_class_add_resultvalue) {
				if(is_array($list_class_add_resultvalue)){
					foreach ($goods_gift_list as $goods_class_keys => $goods_class_values) {
						if($goods_class_keys == $list_class_add_resultvalue['gift']){//商品编码
							$goods_class_values_simple = $goods_class_values;
							$list_class_add_result[$key]['gift'] = $goods_class_values_simple;
						}
					}
					$normal_class_gift_local_content = $normal_class_gift_local_content."<p class='class_add_height'><span>".$goods_class_values_simple."</span><span style='margin-left:12px;'>".$list_class_add_resultvalue['gift_limit']."</span></p>";
					$normal_class_gift_number_once_local_content = $normal_class_gift_number_once_local_content."<p class='class_add_height'>".$list_class_add_resultvalue['gift_number']."</p>";
					$normal_class_gift_least_number_local_content = $normal_class_gift_least_number_local_content."<p class='class_add_height'>".$list_class_add_resultvalue['least_number']."</p>";
					$normal_class_gift_least_payment_local_content = $normal_class_gift_least_payment_local_content."<p class='class_add_height'>".$list_class_add_resultvalue['least_payment']."</p>";
				}
			}
			$normal_class_gift_content = $normal_class_gift_local_content."</div>";
			$normal_class_gift_number_once_content = $normal_class_gift_number_once_local_content."</div>";
			$normal_class_gift_least_number_content = $normal_class_gift_least_number_local_content."</div>";
			$normal_class_gift_least_payment_content = $normal_class_gift_least_payment_local_content."</div>";
			$flag_log = "data-flag='had'";        // 标记是用来判断是否是等级活动的
		}else{//常规活动
			$flag_log = "";
			$gift_second_display = "";
			$gift_third_display = "";
			if($value['_gift_second'] == '[]' && $value['gift_limit_second'] == 0){//如果为空或数量为零，则不显示
				$gift_second_display = "class='normal_second_display'";
			}
			if($value['_gift_third'] == '[]' && $value['gift_limit_third'] == 0){
				$gift_third_display = "class='normal_third_display'";
			}
			$normal_class_gift_content = "<div class='control_td_height'><p id='gift_first_".$value['gift_activity_id']."'><span class='gift_first_content'>".$value['_gift_first']."</span><span style='margin-left:12px;' class='gift_limit_first_number'>".$value['gift_limit_first']."</span></p><p id='gift_second_".$value['gift_activity_id']."' ".$gift_second_display."><span class='gift_second_content'>".$value['_gift_second']."</span><span style='margin-left:12px;' class='gift_limit_second_number'>".$value['gift_limit_second']."</span></p><p id='gift_third_".$value['gift_activity_id']."' ".$gift_third_display."><span class='gift_third_content'>".$value['_gift_third']."</span><span style='margin-left:12px;' class='gift_limit_third_number'>".$value['gift_limit_third']."</p></div>";
			$normal_class_gift_number_once_content = "<p class='control_td_height' id='gift_number_once_".$value['gift_activity_id']."'>".$value['gift_number_once']."</p>";
			$normal_class_gift_least_number_content = "<p class='control_td_height' id='least_number_".$value['gift_activity_id']."'>".$value['least_number']."</p>";
			$normal_class_gift_least_payment_content = "<p class='control_td_height' id='least_payment_".$value['gift_activity_id']."'>".$value['least_payment']."</p>";
		}
		$final_result .= "<tr class='tr_height_control'><td><p class='control_td_height'>".$value['gift_activity_id']."</td>"
		."<td><p class='control_td_height' id='gift_activity_name_".$value['gift_activity_id']."' >".$value['gift_activity_name']."</p>".$activity_cue_content."</td>"
		."<td><p class='control_td_height' id='begin_time_".$value['gift_activity_id']."'><span class='time_begin' style='margin-right:10px;'>".$value['begin_time']."</span><span class='time_end' style='margin-left:10px;'>".$value['end_time']."</span></p></td>"
		."<td><p class='tooltip_class control_td_height' data-title='".$distributor_title."'>".$distributor."</p></td>"
		."<td><p class='tooltip_class control_td_height' data-title='".$facility_title."'>".$facility."</p></td>"
		."<td><p class='tooltip_class control_td_height' data-title='".$region_title."'>".$region."</p></td>"
		."<td><p class='tooltip_class control_td_height' id='".'goods_necessary_limit'.$value['gift_activity_id']."'>".$necessary_all."</p></td>"
		."<td>".$normal_class_gift_content."</td>"
		."<td>".$normal_class_gift_number_once_content."</td>"
		."<td>".$normal_class_gift_least_number_content."</td>"
		."<td>".$normal_class_gift_least_payment_content."</td>"
		."<td><p class='control_td_height' id='repeat_type_".$value['gift_activity_id']."' data-type='".$value['repeat_type']."'>".$repeat_type_result."</p></td>"
		."<td><p class='control_td_height' id='gift_number_once_max_".$value['gift_activity_id']."'>".$value['gift_number_once_max']."</p></td>"
		."<td>".$goods_necessary_content."<div class='scrollbar1'><div class='scrollbar'><div class='track'><div class='thumb'><div class='end'></div></div></div></div><div class='viewport'><p class='tooltip_class overview' data-title='".$goods_included_title."'>".$goods_included."</p></div></div></td>"
		."<td><p class='tooltip_class control_td_height' data-title='".$goods_cat_included_title."'>".$goods_cat_included."</p></td>"
		."<td><div class='scrollbar1'><div class='scrollbar'><div class='track'><div class='thumb'><div class='end'></div></div></div></div><div class='viewport'><p class='tooltip_class overview' data-title='".$goods_excluded_title."'>".$goods_excluded."</p></div></div></td>"
		."<td><p class='tooltip_class control_td_height' data-title='".$goods_cat_excluded_title."'>".$goods_cat_excluded."</p></td>"
		."<td>".$state."</td>"
		."<td><button type='button' ".$change_button_state." class='btn  btn-sm change ".$change_button_class."' ".$flag_log." id='".$value['gift_activity_id']."' data-keyboard = 'false' data-backdrop='static' data-toggle='modal' data-target='.bs-example-modal-lg'>修改</button><a class='btn btn-sm delete_button ".$delete_button_class."' ".$flag_log." delete_id='".$value['gift_activity_id']."' href='javascript:void(0)' ".$delete_button_state.">删除</a></td></tr>";

		/*
		*	这里是清理缓存，不然的话下一次的循环进来数据会叠加
		*/

		$flag_log = "";
		$distributor_title = "";
		$distributor = "";
		$facility_title = "";
		$facility = "";
		$region_title = "";
		$region = "";
		$goods_included_title = "";
		$goods_included = "";
		$goods_necessary = "";
		$goods_cat_included_title = "";
		$goods_cat_included = "";
		$goods_excluded_title == "";
		$goods_excluded = "";
		$goods_cat_excluded_title = "";
		$goods_cat_excluded = "";
		$activity_cue = "";
		$normal_class_gift_local_content = "";
		$normal_class_gift_number_once_local_content = "";
		$normal_class_gift_least_number_local_content = "";
		$normal_class_gift_least_payment_local_content = "";
		$normal_class_gift_content = "";
		$normal_class_gift_number_once_content = "";
		$normal_class_gift_least_number_content = "";
		$normal_class_gift_least_payment_content = "";
		$goods_class_values_simple = "";
		// 所有的缓存数据包括仓库，分销商，地区等需要在灰色框上显示的内容
		$total_cache_array[$value['gift_activity_id']]['facility'] = $facility_cache_array;
		$total_cache_array[$value['gift_activity_id']]['distributor'] = $distributor_cache_array;
		$total_cache_array[$value['gift_activity_id']]['region'] = $region_cache_array;
		$total_cache_array[$value['gift_activity_id']]['goods_included'] = $goods_included_cache_array;
		$total_cache_array[$value['gift_activity_id']]['goods_excluded'] = $goods_excluded_cache_array;
		$total_cache_array[$value['gift_activity_id']]['cat_included'] = $goods_cat_included_cache_array;
		$total_cache_array[$value['gift_activity_id']]['cat_excluded'] = $goods_cat_excluded_cache_array;
		$total_cache_array[$value['gift_activity_id']]['goods_necessary'] = $goods_necessary_cache_array;
		$total_cache_array[$value['gift_activity_id']]['class_activity'] = $list_class_add_result;


		/*
		*	这里将数组清空，以便下一个循环进来数据正常，注销变量
		*/
		unset($facility_cache_array);
		unset($distributor_cache_array);
		unset($region_cache_array);
		unset($goods_included_cache_array);
		unset($goods_excluded_cache_array);
		unset($goods_cat_included_cache_array);
		unset($goods_cat_excluded_cache_array);
		unset($goods_necessary_cache_array);
	}

}
$region_selects="";
if(!empty($region_gift)){
	foreach($region_gift as $key => $value) {
		$region_select .= "<option value='".$value."'>".$value."</option>";        //省份使用下拉列表
	}
}

if($final_result !=''){
	$login_script = "<script src='gift_activity.js'></script>";
}
else{
	$login_script = "<script src='gift_activity.js'></script>";
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="content-type" content="text/html;charset=utf-8">
	<title>贈品活動 新時代版</title>
	<link rel="stylesheet" type="text/css" href="bootstrap/css/bootstrap.min.css">
	<link rel="stylesheet" type="text/css" href="bootstrap/css/bootstrap-theme.min.css">
	<!-- <link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css"> -->
	<link rel="stylesheet" type="text/css" href="gift_activity.css">
	<script src="bootstrap/js/jquery.min.js"></script>
	<script src="bootstrap/js/bootstrap.min.js"></script>
	<script src="bootstrap/js/jquery.tinyscrollbar.js"></script>
	<script src="bootstrap/js/jquery-ui.min.js"></script>
	<!-- // <script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script> -->
	<script src="../js/WdatePicker.js"></script>
	<script>
	/*
	*	这里是将列表中的数据暂存，当点击修改之后会通过js给传过去
	*/
	var total_cache_array_finally = <?php echo json_encode($total_cache_array);?>;
	var many_goods = <?php echo json_encode($goods_gift);?>;
	var goods_gift = <?php echo json_encode($goods_gift);?>;
	// var goods_necessary_display = <?php echo json_encode($goods_necessary_display);?>;
	</script>
</head>
<body>
	<div class="container-fluid"><!-- container-fluid -->
		<form class="form_search" action="gift_activity.php" method="post">
			<input type="hidden" name="form_search_hidden" value="form_search_hidden">
			<div class="base">
				<div class="inline_div word"><p>活动名称：</p>
					<div class="inline_div_small ui-widget">
						<input class="zengpin_gift new_active_input" id="form_search_active_name" name="form_search_active_name">
					</div>
				</div>
				<div class="inline_div word second_word"><p>活动时间：</p>
					<input class=" Wdate new_active_input form_search_time" name="form_search_start_time" onClick="WdatePicker({dateFmt:'yyyy-MM-dd HH:mm:ss'})">
					<span class="form_word_margin">至</span>
					<input class="Wdate new_active_input form_search_time" name="form_search_end_time" onClick="WdatePicker({dateFmt:'yyyy-MM-dd HH:mm:ss'})">
				</div>									
			</div>
			<div class="base">
				<div class="inline_div word"><p>店铺名称：</p>
					<div class="inline_div_small ui-widget">
						<input class="zengpin_gift new_active_input" id="form_search_distributor_name" name="form_search_distributor_name">
					</div>
				</div>
				<div class="inline_div word second_word"><p>活动暗号：</p>
					<input class="focus new_active_input zengpin_gift" id="form_search_activity_cue" name="form_search_activity_cue">
					<!-- <span class="form_word_margin">活动状态：</span>
					<select class='select_width' id="form_search_repeat_type">
						<option value="ONCE">未开始</option>
						<option value="BY_NUMBER">进行中</option>
						<option value="BY_PAYMENT">已结束</option>
					</select> -->
				</div>
				<div class="inline_div word second_word">
					<input type="submit" value="查询" class="btn btn-primary">
				</div>	
				<div class="inline_div word second_word"><button type="button" class="btn btn-primary modul_change" data-keyboard = "false" data-backdrop="static" data-toggle="modal" data-target=".bs-example-modal-lg">新建活动</button></div>							
			</div>
		</form>
		<!-- <h1>赠品活动管理</h1> -->
		<!-- <div class="float_button"><button type="button" class="btn btn-primary change" data-keyboard = "false" data-backdrop="static" data-toggle="modal" data-target=".bs-example-modal-lg">新建</button></div> -->
		<div class="nav_tab_margin">
			<!-- Nav tabs -->
			<!-- <ul class="nav nav-tabs" role="tablist">
				<li role="presentation" class="active"><a href="#active_list" aria-controls="active_list" role="tab" data-toggle="tab">活动列表</a></li>
			</ul> -->

			<!-- Tab panes -->
			<div class="tab-content">
				<div role="tabpanel" class="tab-pane active" id="active_list">
					<table class="max_table">
						<tr class="th_header">
							<th>活动ID</th>
							<th>活动名称</th>
							<th>时效</th>
							<th>分销商列表</th>
							<th>仓库列表</th>
							<th>省份列表</th>
							<th>固定商品限制</th>
							<th>第一,二,三赠品及余量</th>
							<th>赠送件数</th>
							<th>满赠件数</th>
							<th>满赠金额</th>
							<th>叠加类型</th>
							<th>每单最多赠送限量</th>
							<th>参与商品列表</th>
							<th>参与类目列表</th>
							<th>排除商品列表</th>
							<th>排除类目列表</th>
							<th>状态</th>
							<th>操作</th>
						</tr>
						<!-- 列表显示 -->
						<?php echo $final_result;?>
					</table>
					<!-- 分页 -->
					<?php echo $simple_output;?>
					<div class="tooltip_hover"></div>
				</div>
				<!-- Modal -->
				<div class="modal bs-example-modal-lg modal_over-x" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
					<div class="modal-dialog modal_self_design_width" role="document">
						<div class="modal-content">
							<div class="modal-header modal-header-back">
								<button type="button" class="close close_button" data-new="" data-dismiss="modal" aria-label="Close"><span class='glyphicon glyphicon_margin' aria-hidden='true'><img src='remove.png'></span></button>
							</div>
							<div class="modal-body" id="new_active">
								<div class="base">
									<div class="div_bottom">活动设置</div>
								</div>
								<div class="base">
									<div class="inline_div word"><p>活动名称：</p>
										<div class="inline_div_small ui-widget">
											<input class="zengpin_gift new_active_input" id="active_name" name="active_name">
											<span class="must_add">*</span>
										</div>
									</div>
									<div class="inline_div word second_word"><p>开始时间：</p>
										<input class=" Wdate new_active_input time" id="start_time"  onClick="WdatePicker({dateFmt:'yyyy-MM-dd HH:mm:ss'})">
										<span class="must_add">*</span>
										<p>结束时间：</p>
										<input class="Wdate new_active_input time" id="finally_time" onClick="WdatePicker({dateFmt:'yyyy-MM-dd HH:mm:ss'})">
										<span class="must_add">*</span>
									</div>									
								</div>
								<div class="base">
									<div class="div_bottom brother_div_bottom">商品、赠品设置</div>
								</div>
								<div class="choose_activity_type two_list border_bottom_dashed">
									<div>
										<div class="base inline_div half_width">
											<p class="font_change inline_div">选择活动类型：</p>
											<div class="inline_div_small"><a href="#" class="normal_activity choose class_check_color">常规活动</a></div>
											<div class="inline_div_small"><a href="#" class="class_activity choose">等级活动</a></div>
										</div>
									</div>
								</div>
								<p class="now_activity_type activity_type_font normal_now_activity_type">当前活动类型：常规活动</p>
								<p class="now_activity_type activity_type_font class_now_activity_type">当前活动类型：等级活动</p>
								<div class="two_list">
									<div class="goods_necessary_display">
										<div class="base goods_necessary inline_div half_width">
											<p class="font_change inline_div">固定商品：</p>
											<div class="inline_div_small ui-widget"><input type="text" class="focus goods_necessary_focus zengpin_gift new_active_input" id="goods_necessary_focus" name="goods_necessary_focus"></div>
											<div class="inline_div_small"><a href="#" class="goods_necessary_choose choose">增加</a></div>
											<div class="inline_div_small">
												<button type="button" class="btn btn-sm many_add" id="many_goods_necessary_button" data-keyboard = "false" data-backdrop="static" data-toggle="modal" data-target="#many_goods_necessary">批量增加</button>
											</div>
											<div class="checkbox inline_div">
												<label>
													<input type="checkbox" id="goods_necessary_check">默认无固定商品
												</label>
												<label>
													<input type="checkbox" id="goods_necessary_limit">固定商品限制全部
												</label>
											</div>
											<div class="show_contant show_detil"></div>
										</div>
									</div>
								</div>
								<div class="two_list">
									<div>
										<div class="base goods inline_div half_width">
											<p class="font_change inline_div">参与商品：</p>
											<div class="inline_div_small ui-widget"><input type="text" class="focus goods_focus zengpin_gift new_active_input" id="goods_focus" name="goods_focus"></div>
											<div class="inline_div_small"><a href="#" class="goods_choose choose">增加</a></div>
											<div class="inline_div_small">
												<button type="button" class="btn btn-sm many_add" id="many_goods_included_button" data-keyboard = "false" data-backdrop="static" data-toggle="modal" data-target="#many_goods_included">批量增加</button>
											</div>
											<div class="checkbox inline_div">
												<label>
													<input type="checkbox" id="goods_check">不限制
												</label>
											</div>
											<span class="must_add">*</span>
											<div class="show_contant show_detil"></div>
										</div>
										<div class="base goods_excluded inline_div half_width">
											<p class="font_change inline_div">排除商品：</p>
											<div class="inline_div_small ui-widget"><input type="text" class="focus goods_excluded_focus zengpin_gift new_active_input" id="goods_excluded_focus" name="goods_excluded"></div>
											<div class="inline_div_small"><a href="#" class="goods_excluded_choose choose">增加</a></div>
											<div class="inline_div_small">
												<button type="button" class="btn btn-sm many_add" id="many_goods_excluded_button" data-keyboard = "false" data-backdrop="static" data-toggle="modal" data-target="#many_goods_excluded">批量增加</button>
											</div>
											<div class="checkbox inline_div">
												<label>
													<input type="checkbox" id="goods_excluded_check">不限制
												</label>
											</div>
											<div class="show_contant show_detil"></div>
										</div>
									</div>
									<div>
										<div class="base cat_included inline_div half_width">
											<p class="font_change inline_div">参与类目：</p>
											<div class="inline_div_small ui-widget"><input type="text" class="focus cat_included_focus zengpin_gift new_active_input" id="cat_included_focus" name="cat_included_focus"></div>
											<div class="inline_div_small"><a href="#" class="cat_included_choose choose">增加</a></div>
											<div class="checkbox inline_div">
												<label>
													<input type="checkbox" id="cat_included_check">不限制
												</label>
											</div>
											<span class="must_add">*</span>
											<div class="show_contant show_detil"></div>
										</div>
										<div class="base cat_excluded inline_div half_width">
											<p class="font_change inline_div">排除类目：</p>
											<div class="inline_div_small ui-widget"><input type="text" class="focus cat_excluded_focus zengpin_gift new_active_input" id="cat_excluded_focus" name="cat_excluded_focus"></div>
											<div class="inline_div_small"><a href="#" class="cat_excluded_choose choose">增加</a></div>
											<div class="checkbox inline_div">
												<label>
													<input type="checkbox" id="cat_excluded_check">不限制
												</label>
											</div>
											<div class="show_contant show_detil"></div>
										</div>
									</div>
								</div>
								<div class="border_bottom_dashed"></div>
								<div class="normal_activity_display">
									<div class="two_list">
										<div class="base_gift">
											<div class="inline_div word ui-widget"><p>第一赠品：</p>
												<div class="inline_div_small ui-widget">
													<input class="zengpin_gift new_active_input" id="first_gift">
												</div>
												<span class="must_add">*</span>
											</div>
											<div class="inline_div word second_word"><p>第一赠品数量：</p><input class="new_active_input zengpin_gift" id="gift_limit_first"></div>
											<span class="must_add">*</span>
											<div class="inline_div word"><button class="btn choose" id="add_new" data-add="new_bulid_add">添加新赠品</button></div>
											<span class="gift_destory must_add">该赠品已废弃</span>
										</div>
										<div class="base_gift gift_second_show_2 gift_show">
											<div class="inline_div word ui-widget"><p>第二赠品：</p>
												<div class="inline_div_small ui-widget">
													<input type="text" class="zengpin_gift new_active_input" id="second_gift">
												</div>
												<span class="must_add">*</span>
											</div>
											<div class="inline_div word second_word"><p>第二赠品数量：</p><input class="new_active_input zengpin_gift" id="gift_limit_second"></div>
											<span class="must_add">*</span>
										</div>
										<div class="base_gift gift_third_show_3 gift_show">
											<div class="inline_div word ui-widget"><p>第三赠品：</p>
												<div class="inline_div_small ui-widget">
													<input type="text" class="zengpin_gift new_active_input third_gift_value" id="third_gift">
												</div>
												<span class="must_add">*</span>
											</div>
											<div class="inline_div word second_word"><p>第三赠品数量：</p><input class="new_active_input zengpin_gift" id="gift_limit_third"></div>
											<span class="must_add">*</span>
										</div>
										<div class="base">
											<p class="font_change inline_div">满赠金额：</p>
											<div class="inline_div_small ui-widget"><input class="focus new_active_input zengpin_gift" id="least_payment"></div>
											<span class="must_add_white">*</span>
											<div class="inline_div word second_word"><p>叠加类型：</p><select class='select_width zengpin_gift' id="repeat_type"><option value="ONCE">不叠加</option><option value="BY_NUMBER">按件数叠加</option><option value="BY_PAYMENT">按价格叠加</option></select></div>
											<span class="must_add">*</span>
											<div class="inline_div word show_max_gift"><p class="word_width">每单最多赠送限量：</p><input class="new_active_input zengpin_gift" id="each_max_num"></div>
										</div>
										<div class="base_gift">
											<div class="inline_div word ui-widget"><p>满赠件数：</p>
												<div class="inline_div_small ui-widget">
													<input type="text" class="focus new_active_input zengpin_gift" id="least_number">
												</div>
												<span class="must_add">*</span>
											</div>
											<div class="inline_div word second_word"><p>每单赠送件数：</p><input class="new_active_input zengpin_gift" id="each_num"></div>
											<span class="must_add">*</span>
										</div>
									</div>
								</div>
								<div class="class_activity_display">
									<div class="two_list">
										<div class="class_activity_add">
											<div class="base_gift">
												<div class="inline_div word ui-widget"><p>赠品1：</p>
													<div class="inline_div_small ui-widget">
														<input class="new_active_input class_activity_add_zengpin_gift_name class_activity_add_gift clear_class_activity_add class_activity_add_data class_activity_add_must_full">
													</div>
													<span class="must_add">*</span>
												</div>
												<div class="inline_div word second_word"><p>赠品1数量：</p><input class="new_active_input class_activity_add_zengpin_gift class_activity_add_gift_limit clear_class_activity_add class_activity_add_must_full class_activity_add_kucun"></div>
												<span class="must_add">*</span>
												<div class="inline_div word second_word"><p>满赠金额：</p><input class="new_active_input class_activity_add_zengpin_gift class_activity_add_gift_least_payment clear_class_activity_add class_activity_add_must_full"></div>
												<span class="must_add">*</span>
												<div class="inline_div word second_word"><p>满赠件数：</p><input class="new_active_input class_activity_add_zengpin_gift class_activity_add_gift_least_number clear_class_activity_add class_activity_add_must_full"></div>
												<span class="must_add">*</span>
												<div class="inline_div word second_word"><p>每单赠送件数：</p><input class="new_active_input class_activity_add_zengpin_gift class_activity_add_gift_each_num clear_class_activity_add class_activity_add_must_full class_activity_add_zengsongjianshu"></div>
												<span class="must_add">*</span>
												<div class="inline_div word"><button class="btn choose" id="class_activity_add_new">新增</button></div>
											</div>
											<div class="base_gift">
												<div class="inline_div word ui-widget"><p>赠品2：</p>
													<div class="inline_div_small ui-widget">
														<input type="text" class="class_activity_add_zengpin_gift_name new_active_input class_activity_add_gift clear_class_activity_add class_activity_add_data class_activity_add_must_full">
													</div>
													<span class="must_add">*</span>
												</div>
												<div class="inline_div word second_word"><p>赠品2数量：</p><input class="new_active_input class_activity_add_zengpin_gift class_activity_add_gift_limit clear_class_activity_add class_activity_add_must_full class_activity_add_kucun"></div>
												<span class="must_add">*</span>
												<div class="inline_div word second_word"><p>满赠金额：</p><input class="new_active_input class_activity_add_zengpin_gift class_activity_add_gift_least_payment clear_class_activity_add class_activity_add_must_full"></div>
												<span class="must_add">*</span>
												<div class="inline_div word second_word"><p>满赠件数：</p><input class="new_active_input class_activity_add_zengpin_gift class_activity_add_gift_least_number clear_class_activity_add class_activity_add_must_full"></div>
												<span class="must_add">*</span>
												<div class="inline_div word second_word"><p>每单赠送件数：</p><input class="new_active_input class_activity_add_zengpin_gift class_activity_add_gift_each_num clear_class_activity_add class_activity_add_must_full class_activity_add_zengsongjianshu"></div>
												<span class="must_add">*</span>
											</div>
										</div>
									</div>
								</div>
								<div class="two_list">
									<div class="base half_width">
										<p class="font_change inline_div">活动暗号：</p>
										<div class="inline_div_small ui-widget"><input class="focus new_active_input zengpin_gift" id="activity_cue" placeholder="如果没有活动暗号请不要填"></div>
									</div>
								</div>
								<div class="base">
									<div class="div_bottom brother_div_bottom">区域、仓库设置</div>
								</div>
								<div class="two_list">
									<div class="base facility inline_div one_third_width">
										<p class="font_change_small inline_div_small">仓库：</p>
										<div class="inline_div_small ui-widget"><input type="text" class="focus facility_focus zengpin_gift new_active_input" id="facility_focus" name="facility_focus" placeholder="请填写仓库"></div>
										<div class="inline_div_small"><a href="javascript:void(0)" class="facility_choose choose_small">增加</span></a></div>
										<div class="checkbox inline_div_small">
											<label>
												<input type="checkbox" id="facility_check">不限制
											</label>
										</div>
										<span class="must_add">*</span>
										<div class="show_contant facility_contant"></div>
									</div>
									<div class="base distributor inline_div one_third_width">
										<p class="inline_div_small">分销商：</p>
										<div class="inline_div_small ui-widget"><input type="text" class="focus distributor_focus zengpin_gift new_active_input" id="distributor_focus" name="distributor_focus" placeholder="请填写分销商"></div>
										<div class="inline_div_small"><a href="#" class="distributor_choose choose_small">增加</span></a></div>
										<div class="checkbox inline_div_small">
											<label>
												<input type="checkbox" id="distributor_check">不限制
											</label>
										</div>
										<span class="must_add">*</span>
										<div class="show_contant distributor_contant"></div>
									</div>
									<div class="base region inline_div one_third_width">
										<p class="font_change inline_div">省份：</p>
										<div class="inline_div_small"><select id="region_select_id" class="form-control"><?php echo $region_select;?></select></div>
										<!-- <div class="inline_div ui-widget"><input type="text" class="focus region_focus new_active_input" id="region_focus" name="region_focus"></div> -->
										<div class="inline_div_small"><a href="#" class="region_choose choose">增加</a></div>
										<div class="checkbox inline_div">
											<label>
												<input type="checkbox" id="region_check">不限制
											</label>
										</div>
										<span class="must_add">*</span>
										<div class="show_contant region_contant"></div>
									</div>
								</div>
							</div>
							<div class="modal-footer">
								<button type="button" class="btn btn-default close_button" data-new="" data-dismiss="modal">关闭</button>
								<button type="button" class="btn btn-primary save_all">保存所有</button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<!-- Modal -->
		<div class="modal fade" id="many_goods_necessary" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
			<div class="modal-dialog" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
						<h4 class="modal-title" id="myModalLabel">批量增加参与商品</h4>
					</div>
					<div class="modal-body">
						<!-- <p>请使用<span class="many_goods_word">英文逗号</span>将参与商品的编码隔开</p> -->
						<p class="many_goods_word">如果你的输入框中一直存在商品编码,请查看是否有重复提交或者错误编码</p>
						<textarea class="many_textarea many_goods_included_input" rows="7"></textarea>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
						<button type="button" class="btn btn-primary many_goods_necessary_save">保存</button>
					</div>
				</div>
			</div>
		</div>
		<!-- Modal -->
		<div class="modal fade" id="many_goods_included" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
			<div class="modal-dialog" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
						<h4 class="modal-title" id="myModalLabel">批量增加参与商品</h4>
					</div>
					<div class="modal-body">
						<!-- <p>请使用<span class="many_goods_word">英文逗号</span>将参与商品的编码隔开</p> -->
						<p class="many_goods_word">如果你的输入框中一直存在商品编码,请查看是否有重复提交或者错误编码</p>
						<textarea class="many_textarea many_goods_included_input" rows="7"></textarea>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
						<button type="button" class="btn btn-primary many_goods_included_save">保存</button>
					</div>
				</div>
			</div>
		</div>
		<!-- Modal -->
		<div class="modal fade" id="many_goods_excluded" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
			<div class="modal-dialog" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
						<h4 class="modal-title" id="myModalLabel">批量增加排除商品</h4>
					</div>
					<div class="modal-body">
						<p class="many_goods_word">如果你的输入框中一直存在商品编码,请查看是否有重复提交或者错误编码</p>
						<textarea class="many_textarea many_goods_excluded_input" rows="7"></textarea>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
						<button type="button" class="btn btn-primary many_goods_excluded_save">保存</button>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php echo $login_script;?>  <!-- 这里是引入js文件,因为有些节点是后来生成的,因而需要在这些节点生成之后才引入js文件,好吧，请忽略我 -->
	<script>
	$(document).ready(function(){

		$('.scrollbar1').tinyscrollbar();

		var facility_gift = <?php echo json_encode($facility_gift);?>;//仓库，直接从后台取出数据转JSON格式，为后续自动填充做准备
		var distributor_gift = <?php echo json_encode($distributor_gift);?>;//分销商
		var goods_cat_included_gift = <?php echo json_encode($goods_cat_included_gift);?>;//参与类目
		var goods_gift_no_or_style = <?php echo json_encode($goods_gift_no_or_style);?>; //无样式商品
		/*
		*	这里是设置自动填充的内容
		*/

		function split( val ) {
			return val.split( /,\s*/ );
		}
		function extractLast( term ) {
			return split( term ).pop();
		}
		// 自动填充仓库
		$("#facility_focus").autocomplete({
			source: facility_gift
		});
		// 自动填充分销商
		$("#distributor_focus").autocomplete({
			source: distributor_gift
		});
		// 自动填充参与商品
		$("#goods_focus").autocomplete({
			source: goods_gift
		});
		// 自动填充排除商品
		$("#goods_excluded_focus").autocomplete({
			source: goods_gift
		});
		// 自动填充排除类目
		$("#cat_included_focus").autocomplete({
			source: goods_cat_included_gift
		});
		// 自动填充参与类目
		$("#cat_excluded_focus").autocomplete({
			source: goods_cat_included_gift
		});
		// 自动填充第一赠品
		$("#first_gift").autocomplete({
			source: goods_gift
		});
		// 自动填充第二赠品
		$("#second_gift").autocomplete({
			source: goods_gift
		});
		// 自动填充第三赠品
		$("#third_gift").autocomplete({
			source: goods_gift
		});
		// 自动填充固定商品
		$("#goods_necessary_focus").autocomplete({
			source: goods_gift
		});
		// 自动填充批量商品
		$( ".many_goods_included_input" ).autocomplete({
			minLength: 0,
			source: function( request, response ) {
				response( $.ui.autocomplete.filter(
					goods_gift_no_or_style, extractLast( request.term ) ) );
			},
			focus: function() {
	          // prevent value inserted on focus
	          return false;
	      },
	      select: function( event, ui ) {
	      	var terms = split( this.value );
	          // remove the current input
	          terms.pop();
	          // add the selected item
	          terms.push( ui.item.value );
	          // add placeholder to get the comma-and-space at the end
	          terms.push( "" );
	          this.value = terms.join( "," );
	          return false;
	      }
	  });
		// 自动填充排除商品
		$(".many_goods_excluded_input" ).autocomplete({
			minLength: 0,
			source: function( request, response ) {
				response( $.ui.autocomplete.filter(
					goods_gift_no_or_style, extractLast( request.term ) ) );
			},
			focus: function() {
	          // prevent value inserted on focus
	          return false;
	      },
	      select: function( event, ui ) {
	      	var terms = split( this.value );
	          // remove the current input
	          terms.pop();
	          // add the selected item
	          terms.push( ui.item.value );
	          // add placeholder to get the comma-and-space at the end
	          terms.push( "" );
	          this.value = terms.join( "," );
	          return false;
	      }
	  });
		// 等级活动赠品自动填充，填充内容为参与商品
		$(".class_activity_add_data").autocomplete({
			source: goods_gift
		});

	});
</script>
</body>
</html>